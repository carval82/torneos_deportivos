<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Roster;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\EligibilityChecker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlayerController extends Controller
{
    public function __construct(private readonly EligibilityChecker $eligibility) {}

    public function index(Request $request): View
    {
        $players = Player::with('team')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('document_number', 'like', $term);
                });
            })
            ->orderBy('last_name')
            ->paginate(20)
            ->withQueryString();

        return view('players.index', compact('players'));
    }

    public function create(Request $request): View
    {
        return view('players.create', [
            'teams' => Team::orderBy('name')->get(),
            'teamId' => $request->integer('team_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $player = Player::create($this->validated($request));
        $this->syncRosters($player);

        return redirect()
            ->route('players.show', $player)
            ->with('status', 'Jugador cargado en la planilla.');
    }

    public function show(Request $request, Player $player): View
    {
        $player->load(['team', 'events.game.tournament', 'attendances.game']);
        $tournament = $request->filled('tournament_id')
            ? Tournament::with('ageCategory')->find($request->integer('tournament_id'))
            : $player->team?->tournaments()->with('ageCategory')->orderByDesc('tournaments.id')->first();

        return view('players.show', [
            'player' => $player,
            'eligibility' => $this->eligibility->check($player, $tournament),
            'tournament' => $tournament,
            'goals' => $player->events()->where('type', 'goal')->count(),
        ]);
    }

    public function edit(Player $player): View
    {
        return view('players.edit', [
            'player' => $player,
            'teams' => Team::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Player $player): RedirectResponse
    {
        $player->update($this->validated($request, $player));
        $this->syncRosters($player->fresh());

        return redirect()
            ->route('players.show', $player)
            ->with('status', 'Ficha del jugador actualizada.');
    }

    public function destroy(Player $player): RedirectResponse
    {
        $player->delete();

        return redirect()->route('players.index')->with('status', 'Jugador eliminado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Player $player = null): array
    {
        $data = $request->validate([
            'team_id' => ['nullable', 'exists:teams,id'],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'document_type' => ['required', 'in:DNI,Pasaporte,Cédula'],
            'document_number' => ['required', 'string', 'max:30', 'unique:players,document_number,'.($player?->id ?? 'NULL')],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'gender' => ['required', 'in:masculino,femenino,mixto'],
            'nationality' => ['nullable', 'string', 'max:80'],
            'position' => ['nullable', 'string', 'max:40'],
            'jersey_number' => ['nullable', 'integer', 'min:1', 'max:99'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'document_photo' => ['nullable', 'image', 'max:5120'],
        ]);

        unset($data['photo'], $data['document_photo']);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('players/photos', 'public');
        }

        if ($request->hasFile('document_photo')) {
            $data['document_photo_path'] = $request->file('document_photo')->store('players/documents', 'public');
        }

        return $data;
    }

    private function syncRosters(Player $player): void
    {
        if (! $player->team_id) {
            return;
        }

        $player->load('team.tournaments');

        foreach ($player->team?->tournaments ?? [] as $tournament) {
            Roster::updateOrCreate(
                [
                    'tournament_id' => $tournament->id,
                    'player_id' => $player->id,
                ],
                [
                    'team_id' => $player->team_id,
                    'jersey_number' => $player->jersey_number,
                    'position' => $player->position,
                    'is_active' => true,
                ]
            );
        }
    }
}
