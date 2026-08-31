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
use App\Services\MatchSheetService;
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
        private readonly MatchSheetService $sheets,
    ) {}

    public function teams(Request $request): JsonResponse
    {
        $user = $request->user();
        $with = [
            'tournaments' => fn ($q) => $q
                ->orderBy('tournaments.name')
                ->select([
                    'tournaments.id',
                    'tournaments.name',
                    'tournaments.public_slug',
                    'tournaments.status',
                ]),
        ];

        $teams = $user->isAdmin() || $user->role === \App\Models\User::ROLE_ORGANIZER
            ? Team::with($with)->withCount('players')->orderBy('name')->get()
            : $user->teams()
                ->with($with)
                ->withCount('players')
                ->withPivot(['role', 'is_disciplinary_committee'])
                ->orderBy('name')
                ->get();

        return response()->json([
            'teams' => $teams->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'short_name' => $team->short_name,
                'city' => $team->city,
                'players_count' => $team->players_count,
                'tournaments' => $team->tournaments->map(fn (Tournament $tournament) => [
                    'id' => $tournament->id,
                    'name' => $tournament->name,
                    'public_slug' => $tournament->public_slug,
                    'status' => $tournament->status,
                ])->values(),
            ])->values(),
        ]);
    }

    public function tournaments(Request $request): JsonResponse
    {
        $user = $request->user();
        $teamIds = $user->isAdmin()
            ? Team::query()->pluck('id')
            : $user->teams()->pluck('teams.id');

        if ($teamIds->isEmpty()) {
            return response()->json(['tournaments' => []]);
        }

        $tournaments = Tournament::query()
            ->with('sport')
            ->withCount(['teams', 'games'])
            ->whereHas('teams', fn ($q) => $q->whereIn('teams.id', $teamIds))
            ->orderByDesc('tournaments.id')
            ->get()
            ->map(fn (Tournament $tournament) => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'public_slug' => $tournament->public_slug,
                'status' => $tournament->status,
                'status_label' => $tournament->statusLabel(),
                'sport' => $tournament->sport?->name,
                'teams_count' => $tournament->teams_count,
                'games_count' => $tournament->games_count,
            ])
            ->values();

        return response()->json(['tournaments' => $tournaments]);
    }

    public function roster(Request $request, Team $team): JsonResponse
    {
        $this->authorize('manageRoster', $team);

        $tournament = $request->filled('tournament_id')
            ? Tournament::findOrFail($request->integer('tournament_id'))
            : $team->tournaments()->orderByDesc('tournaments.id')->first();

        $team->load(['players' => fn ($q) => $q->orderBy('last_name')->orderBy('first_name')]);
        $eligibility = [];
        if ($tournament) {
            foreach ($team->players as $player) {
                try {
                    $check = $this->eligibility->check($player, $tournament);
                } catch (\Throwable) {
                    $check = [
                        'eligible' => true,
                        'age' => $player->age(),
                        'reason' => null,
                        'warnings' => [],
                        'exception' => null,
                    ];
                }
                $eligibility[(string) $player->id] = [
                    'eligible' => $check['eligible'],
                    'age' => $check['age'],
                    'reason' => $check['reason'],
                    'warnings' => $check['warnings'] ?? [],
                    'exception_status' => is_object($check['exception'] ?? null)
                        ? $check['exception']->status
                        : null,
                ];
            }
        }

        $players = $team->players->map(fn (Player $player) => $this->playerPayload($player))->values();
        $user = $request->user();
        $isCommittee = $user->isAdmin()
            || ($tournament && (int) $tournament->user_id === (int) $user->id);

        if (! $isCommittee) {
            try {
                $isCommittee = (bool) $user->teams()
                    ->where('teams.id', $team->id)
                    ->first()
                    ?->pivot
                    ?->is_disciplinary_committee;
            } catch (\Throwable) {
                $isCommittee = false;
            }
        }

        return response()->json([
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'short_name' => $team->short_name,
                'city' => $team->city,
                'players' => $players,
                'players_count' => $players->count(),
            ],
            'players' => $players,
            'tournament' => $tournament ? [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'public_slug' => $tournament->public_slug,
                'status' => $tournament->status,
            ] : null,
            'tournaments' => $team->tournaments()
                ->orderBy('tournaments.name')
                ->get(['tournaments.id', 'tournaments.name', 'tournaments.public_slug', 'tournaments.status']),
            'roster_status' => $tournament ? $this->rosterLock->status($tournament) : null,
            'eligibility' => (object) $eligibility,
            'is_disciplinary_committee' => $isCommittee,
        ]);
    }

    public function storePlayer(Request $request, Team $team): JsonResponse
    {
        $this->authorize('manageRoster', $team);
        $this->normalizePlayerInput($request);

        $data = $request->validate($this->playerRules());

        if (! empty($data['tournament_id'])) {
            $this->rosterLock->assertOpen(Tournament::findOrFail($data['tournament_id']));
        }

        if (Player::query()
            ->where('document_type', $data['document_type'])
            ->where('document_number', $data['document_number'])
            ->exists()) {
            return response()->json(['message' => 'Ya existe un jugador con ese documento.'], 422);
        }

        $player = Player::create([
            ...collect($data)->except(['tournament_id', 'photo', 'document_photo'])->all(),
            'team_id' => $team->id,
            'nationality' => $data['nationality'] ?? 'Colombia',
        ]);

        if ($request->hasFile('photo')) {
            $this->media->storePhoto($player, $request->file('photo'));
        }
        if ($request->hasFile('document_photo')) {
            $this->media->storeDocumentPhoto($player, $request->file('document_photo'));
        }

        $player = $player->fresh();
        $this->syncPlayerRosters($team, $player, $data['tournament_id'] ?? null);

        return response()->json(['player' => $this->playerPayload($player)], 201);
    }

    public function updatePlayer(Request $request, Team $team, Player $player): JsonResponse
    {
        $this->authorize('manageRoster', $team);
        abort_unless((int) $player->team_id === (int) $team->id, 403);
        $this->normalizePlayerInput($request);

        $data = $request->validate($this->playerRules(updating: true));

        if (! empty($data['tournament_id'])) {
            $this->rosterLock->assertOpen(Tournament::findOrFail($data['tournament_id']));
        }

        $player->update(collect($data)->except(['tournament_id', 'photo', 'document_photo'])->all());

        if ($request->hasFile('photo')) {
            $this->media->storePhoto($player, $request->file('photo'));
        }
        if ($request->hasFile('document_photo')) {
            $this->media->storeDocumentPhoto($player, $request->file('document_photo'));
        }

        $player = $player->fresh();
        $this->syncPlayerRosters($team, $player, $data['tournament_id'] ?? null);

        return response()->json(['player' => $this->playerPayload($player)]);
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
            'player' => $this->playerPayload($player),
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

    /**
     * @return array<string, mixed>
     */
    private function playerPayload(Player $player): array
    {
        return [
            'id' => $player->id,
            'team_id' => $player->team_id,
            'first_name' => $player->first_name,
            'last_name' => $player->last_name,
            'display_name' => $player->displayName(),
            'document_type' => $player->document_type,
            'document_number' => $player->document_number,
            'birthdate' => $player->birthdate?->format('Y-m-d'),
            'age' => $player->age(),
            'gender' => $player->gender,
            'nationality' => $player->nationality,
            'position' => $player->position,
            'jersey_number' => $player->jersey_number,
            'phone' => $player->phone,
            'email' => $player->email,
            'photo_url' => $player->photoUrl(),
            'document_photo_url' => $player->documentPhotoUrl(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function playerRules(bool $updating = false): array
    {
        $req = $updating ? 'sometimes' : 'required';

        return [
            'tournament_id' => ['nullable', 'exists:tournaments,id'],
            'first_name' => [$req, 'string', 'max:80'],
            'last_name' => [$req, 'string', 'max:80'],
            'document_type' => [$req, 'in:DNI,Pasaporte,Cédula,Cedula'],
            'document_number' => [$req, 'string', 'max:40'],
            'birthdate' => [$req, 'date'],
            'gender' => [$req, 'in:masculino,femenino,mixto'],
            'nationality' => ['nullable', 'string', 'max:80'],
            'position' => ['nullable', 'string', 'max:60'],
            'jersey_number' => ['nullable', 'integer', 'min:0', 'max:99'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'],
            'photo' => ['nullable', 'image', 'max:8192'],
            'document_photo' => ['nullable', 'image', 'max:8192'],
        ];
    }

    private function normalizePlayerInput(Request $request): void
    {
        $merge = [];

        foreach (['position', 'phone', 'email', 'nationality', 'jersey_number', 'tournament_id'] as $key) {
            if ($request->input($key) === '') {
                $merge[$key] = null;
            }
        }

        $type = $request->input('document_type');
        if (is_string($type)) {
            $normalized = str_ireplace(['é', 'É'], 'e', $type);
            if (strcasecmp(trim($normalized), 'Cedula') === 0) {
                $merge['document_type'] = 'Cédula';
            }
        }

        if ($merge !== []) {
            $request->merge($merge);
        }
    }

    private function syncPlayerRosters(Team $team, Player $player, mixed $tournamentId = null): void
    {
        if ($tournamentId) {
            Roster::updateOrCreate(
                ['tournament_id' => (int) $tournamentId, 'player_id' => $player->id],
                [
                    'team_id' => $team->id,
                    'jersey_number' => $player->jersey_number,
                    'position' => $player->position,
                    'is_active' => true,
                ]
            );

            return;
        }

        foreach ($team->tournaments as $tournament) {
            $this->sheets->enrollTeamRoster($tournament->id, $team->id);
        }
    }
}
