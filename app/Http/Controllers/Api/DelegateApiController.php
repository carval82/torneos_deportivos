<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EligibilityException;
use App\Models\Player;
use App\Models\Roster;
use App\Models\Suspension;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\DisciplineService;
use App\Services\EligibilityChecker;
use App\Services\PlayerMediaService;
use App\Services\RosterLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DelegateApiController extends Controller
{
    public function __construct(
        private readonly RosterLockService $rosterLock,
        private readonly PlayerMediaService $media,
        private readonly EligibilityChecker $eligibility,
        private readonly DisciplineService $discipline,
    ) {}

    public function teams(Request $request): JsonResponse
    {
        $user = $request->user();
        $teams = $user->isAdmin() || $user->isOrganizer()
            ? Team::withCount('players')->orderBy('name')->get()
            : $user->teams()->withCount('players')->withPivot(['role', 'is_disciplinary_committee'])->orderBy('name')->get();

        return response()->json(['teams' => $teams]);
    }

    public function roster(Request $request, Team $team): JsonResponse
    {
        $this->authorize('manageRoster', $team);

        $tournament = $request->filled('tournament_id')
            ? Tournament::findOrFail($request->integer('tournament_id'))
            : $team->tournaments()->latest()->first();

        $team->load('players');
        $eligibility = [];
        if ($tournament) {
            foreach ($team->players as $player) {
                $check = $this->eligibility->check($player, $tournament);
                $eligibility[$player->id] = [
                    'eligible' => $check['eligible'],
                    'age' => $check['age'],
                    'reason' => $check['reason'],
                    'warnings' => $check['warnings'],
                    'exception_status' => $check['exception']?->status,
                ];
            }
        }

        return response()->json([
            'team' => $team,
            'tournament' => $tournament,
            'tournaments' => $team->tournaments()->get(['tournaments.id', 'name', 'public_slug', 'status']),
            'roster_status' => $tournament ? $this->rosterLock->status($tournament) : null,
            'eligibility' => $eligibility,
            'is_disciplinary_committee' => (bool) optional(
                $request->user()->teams()->where('teams.id', $team->id)->first()
            )->pivot?->is_disciplinary_committee
                || $request->user()->isAdmin()
                || ($tournament && (int) $tournament->user_id === (int) $request->user()->id),
        ]);
    }

    public function storePlayer(Request $request, Team $team): JsonResponse
    {
        $this->authorize('manageRoster', $team);

        $data = $request->validate([
            'tournament_id' => ['nullable', 'exists:tournaments,id'],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'document_type' => ['required', 'in:DNI,Pasaporte,Cédula'],
            'document_number' => ['required', 'string', 'max:40'],
            'birthdate' => ['required', 'date'],
            'gender' => ['required', 'in:masculino,femenino,mixto'],
            'position' => ['nullable', 'string', 'max:60'],
            'jersey_number' => ['nullable', 'integer', 'min:0', 'max:99'],
            'phone' => ['nullable', 'string', 'max:40'],
            'photo' => ['nullable', 'image', 'max:8192'],
            'document_photo' => ['nullable', 'image', 'max:8192'],
        ]);

        if (! empty($data['tournament_id'])) {
            $this->rosterLock->assertOpen(Tournament::findOrFail($data['tournament_id']));
        }

        if (Player::query()
            ->where('document_type', $data['document_type'])
            ->where('document_number', $data['document_number'])
            ->exists()) {
            return response()->json(['message' => 'Ya existe un jugador con ese documento.'], 422);
        }

        $tournamentId = $data['tournament_id'] ?? null;
        $player = Player::create([
            ...collect($data)->except(['tournament_id', 'photo', 'document_photo'])->all(),
            'team_id' => $team->id,
            'nationality' => 'Argentina',
        ]);

        if ($request->hasFile('photo')) {
            $this->media->storePhoto($player, $request->file('photo'));
        }
        if ($request->hasFile('document_photo')) {
            $this->media->storeDocumentPhoto($player, $request->file('document_photo'));
        }

        if ($tournamentId) {
            Roster::updateOrCreate(
                ['tournament_id' => $tournamentId, 'player_id' => $player->id],
                [
                    'team_id' => $team->id,
                    'jersey_number' => $player->jersey_number,
                    'position' => $player->position,
                    'is_active' => true,
                ]
            );
        }

        return response()->json(['player' => $player->fresh()], 201);
    }

    public function updatePlayer(Request $request, Team $team, Player $player): JsonResponse
    {
        $this->authorize('manageRoster', $team);
        abort_unless((int) $player->team_id === (int) $team->id, 403);

        $data = $request->validate([
            'tournament_id' => ['nullable', 'exists:tournaments,id'],
            'first_name' => ['sometimes', 'string', 'max:80'],
            'last_name' => ['sometimes', 'string', 'max:80'],
            'document_type' => ['sometimes', 'in:DNI,Pasaporte,Cédula'],
            'document_number' => ['sometimes', 'string', 'max:40'],
            'birthdate' => ['sometimes', 'date'],
            'gender' => ['sometimes', 'in:masculino,femenino,mixto'],
            'position' => ['nullable', 'string', 'max:60'],
            'jersey_number' => ['nullable', 'integer', 'min:0', 'max:99'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        if (! empty($data['tournament_id'])) {
            $this->rosterLock->assertOpen(Tournament::findOrFail($data['tournament_id']));
        }

        $player->update(collect($data)->except(['tournament_id'])->all());

        return response()->json(['player' => $player->fresh()]);
    }

    public function uploadPhotos(Request $request, Team $team, Player $player): JsonResponse
    {
        $this->authorize('manageRoster', $team);
        abort_unless((int) $player->team_id === (int) $team->id, 403);

        $request->validate([
            'tournament_id' => ['nullable', 'exists:tournaments,id'],
            'photo' => ['nullable', 'image', 'max:8192'],
            'document_photo' => ['nullable', 'image', 'max:8192'],
        ]);

        if ($request->filled('tournament_id')) {
            $this->rosterLock->assertOpen(Tournament::findOrFail($request->integer('tournament_id')));
        }

        if ($request->hasFile('photo')) {
            $this->media->storePhoto($player, $request->file('photo'));
        }
        if ($request->hasFile('document_photo')) {
            $this->media->storeDocumentPhoto($player, $request->file('document_photo'));
        }

        $player = $player->fresh();

        return response()->json([
            'player' => $player,
            'photo_url' => $player->photoUrl(),
            'document_photo_url' => $player->documentPhotoUrl(),
        ]);
    }

    public function requestException(Request $request, Tournament $tournament): JsonResponse
    {
        $data = $request->validate([
            'player_id' => ['required', 'exists:players,id'],
            'team_id' => ['required', 'exists:teams,id'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $team = Team::findOrFail($data['team_id']);
        $player = Player::findOrFail($data['player_id']);
        abort_unless($request->user()->managesTeam($team->id) || $request->user()->isAdmin(), 403);
        abort_unless((int) $player->team_id === (int) $team->id, 422);

        $exception = EligibilityException::query()->updateOrCreate(
            ['tournament_id' => $tournament->id, 'player_id' => $player->id],
            [
                'team_id' => $team->id,
                'reason' => $data['reason'],
                'status' => EligibilityException::STATUS_PENDING,
                'requested_by' => $request->user()->id,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_notes' => null,
            ]
        );

        return response()->json(['exception' => $exception], 201);
    }

    public function storeSentence(Request $request, Tournament $tournament): JsonResponse
    {
        abort_unless($request->user()->canIssueDisciplinarySentence($tournament), 403);

        $data = $request->validate([
            'player_id' => ['required', 'exists:players,id'],
            'matches' => ['required', 'integer', 'min:1', 'max:20'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $player = Player::with('team')->findOrFail($data['player_id']);
        abort_unless($tournament->teams()->where('teams.id', $player->team_id)->exists(), 422);

        $suspension = $this->discipline->issueCommitteeSentence(
            $tournament,
            $player,
            $request->user(),
            (int) $data['matches'],
            $data['reason'],
            $data['notes'] ?? null,
        );

        return response()->json(['suspension' => $suspension->load(['player', 'team'])], 201);
    }

    public function suspensions(Request $request, Tournament $tournament): JsonResponse
    {
        abort_unless(
            $request->user()->canIssueDisciplinarySentence($tournament)
            || $request->user()->teams()->whereHas('tournaments', fn ($q) => $q->where('tournaments.id', $tournament->id))->exists(),
            403
        );

        return response()->json([
            'suspensions' => Suspension::query()
                ->with(['player', 'team', 'issuer'])
                ->where('tournament_id', $tournament->id)
                ->where('is_active', true)
                ->latest()
                ->get(),
        ]);
    }
}
