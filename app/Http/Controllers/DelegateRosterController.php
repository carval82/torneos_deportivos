<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Roster;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\EligibilityChecker;
use App\Services\MatchSheetService;
use App\Services\PlayerMediaService;
use App\Services\RosterLockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DelegateRosterController extends Controller
{
    public function __construct(
        private readonly MatchSheetService $sheets,
        private readonly EligibilityChecker $eligibility,
        private readonly RosterLockService $rosterLock,
        private readonly PlayerMediaService $media,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $teams = $user->isAdmin() || $user->isOrganizer()
            ? Team::orderBy('name')->get()
            : $user->teams()->orderBy('name')->get();

        return view('delegate.index', [
            'teams' => $teams,
        ]);
    }

    public function roster(Request $request, Team $team): View
    {
        $this->authorize('manageRoster', $team);

        $tournament = $request->filled('tournament')
            ? Tournament::findOrFail($request->integer('tournament'))
            : $team->tournaments()->latest()->first();

        $team->load('players');

        $eligibility = [];
        if ($tournament) {
            foreach ($team->players as $player) {
                $eligibility[$player->id] = $this->eligibility->check($player, $tournament);
            }
        }

        $rosterStatus = $tournament ? $this->rosterLock->status($tournament) : null;

        return view('delegate.roster', [
            'team' => $team,
            'tournament' => $tournament,
            'tournaments' => $team->tournaments()->orderBy('name')->get(),
            'eligibility' => $eligibility,
            'rosterStatus' => $rosterStatus,
        ]);
    }

    public function storePlayer(Request $request, Team $team): RedirectResponse
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
            'email' => ['nullable', 'email', 'max:150'],
            'photo' => ['nullable', 'image', 'max:8192'],
            'document_photo' => ['nullable', 'image', 'max:8192'],
        ]);

        if (! empty($data['tournament_id'])) {
            $this->rosterLock->assertOpen(Tournament::findOrFail($data['tournament_id']));
        }

        $exists = Player::query()
            ->where('document_type', $data['document_type'])
            ->where('document_number', $data['document_number'])
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'document_number' => 'Ya existe un jugador con ese documento.',
            ])->withInput();
        }

        $tournamentId = $data['tournament_id'] ?? null;
        unset($data['tournament_id']);

        $player = Player::create([
            ...collect($data)->except(['photo', 'document_photo', 'tournament_id'])->all(),
            'team_id' => $team->id,
            'nationality' => 'Argentina',
        ]);

        if ($request->hasFile('photo')) {
            $this->media->storePhoto($player, $request->file('photo'));
        }
        if ($request->hasFile('document_photo')) {
            $this->media->storeDocumentPhoto($player, $request->file('document_photo'));
        }

        if ($tournamentId && $team->tournaments()->where('tournaments.id', $tournamentId)->exists()) {
            Roster::updateOrCreate(
                [
                    'tournament_id' => $tournamentId,
                    'player_id' => $player->id,
                ],
                [
                    'team_id' => $team->id,
                    'jersey_number' => $player->jersey_number,
                    'position' => $player->position,
                    'is_active' => true,
                ]
            );
        } else {
            foreach ($team->tournaments as $tournament) {
                $this->sheets->enrollTeamRoster($tournament->id, $team->id);
            }
        }

        return back()->with('status', 'Jugador agregado a la plantilla.');
    }

    public function updatePlayer(Request $request, Team $team, Player $player): RedirectResponse
    {
        $this->authorize('manageRoster', $team);
        abort_unless((int) $player->team_id === (int) $team->id, 403);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'document_type' => ['required', 'in:DNI,Pasaporte,Cédula'],
            'document_number' => ['required', 'string', 'max:40'],
            'birthdate' => ['required', 'date'],
            'gender' => ['required', 'in:masculino,femenino,mixto'],
            'position' => ['nullable', 'string', 'max:60'],
            'jersey_number' => ['nullable', 'integer', 'min:0', 'max:99'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
            'tournament_id' => ['nullable', 'exists:tournaments,id'],
            'photo' => ['nullable', 'image', 'max:8192'],
            'document_photo' => ['nullable', 'image', 'max:8192'],
        ]);

        if ($request->filled('tournament_id')) {
            $this->rosterLock->assertOpen(Tournament::findOrFail($request->integer('tournament_id')));
        }

        $duplicate = Player::query()
            ->where('document_type', $data['document_type'])
            ->where('document_number', $data['document_number'])
            ->where('id', '!=', $player->id)
            ->exists();

        if ($duplicate) {
            return back()->withErrors([
                'document_number' => 'Ya existe otro jugador con ese documento.',
            ])->withInput();
        }

        $player->update(collect($data)->except(['is_active', 'tournament_id', 'photo', 'document_photo'])->all());

        if ($request->hasFile('photo')) {
            $this->media->storePhoto($player, $request->file('photo'));
        }
        if ($request->hasFile('document_photo')) {
            $this->media->storeDocumentPhoto($player, $request->file('document_photo'));
        }

        if ($request->filled('tournament_id')) {
            Roster::updateOrCreate(
                [
                    'tournament_id' => $request->integer('tournament_id'),
                    'player_id' => $player->id,
                ],
                [
                    'team_id' => $team->id,
                    'jersey_number' => $player->jersey_number,
                    'position' => $player->position,
                    'is_active' => $request->boolean('is_active', true),
                ]
            );
        }

        return back()->with('status', 'Jugador actualizado.');
    }
}
