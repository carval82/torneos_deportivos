<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Suspension;
use App\Models\Tournament;
use App\Services\DisciplineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DisciplinarySentenceController extends Controller
{
    public function __construct(private readonly DisciplineService $discipline) {}

    public function store(Request $request, Tournament $tournament): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->canIssueDisciplinarySentence($tournament), 403);

        $data = $request->validate([
            'player_id' => ['required', 'exists:players,id'],
            'matches' => ['required', 'integer', 'min:1', 'max:20'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $player = Player::query()->with('team')->findOrFail($data['player_id']);
        abort_unless(
            $tournament->teams()->where('teams.id', $player->team_id)->exists(),
            422,
            'El jugador no pertenece a un equipo del torneo.'
        );

        $this->discipline->issueCommitteeSentence(
            $tournament,
            $player,
            $user,
            (int) $data['matches'],
            $data['reason'],
            $data['notes'] ?? null,
        );

        return back()->with('status', 'Sentencia del comité registrada.');
    }

    public function revoke(Request $request, Suspension $suspension): RedirectResponse
    {
        $tournament = $suspension->tournament;
        abort_unless($request->user()?->canIssueDisciplinarySentence($tournament), 403);
        abort_unless($suspension->source === 'committee', 422);

        $suspension->update([
            'is_active' => false,
            'matches_remaining' => 0,
            'notes' => trim(($suspension->notes ? $suspension->notes.' | ' : '').'Revocada por '.$request->user()->name),
        ]);

        return back()->with('status', 'Sentencia revocada.');
    }
}
