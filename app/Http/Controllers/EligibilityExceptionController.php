<?php

namespace App\Http\Controllers;

use App\Models\EligibilityException;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EligibilityExceptionController extends Controller
{
    public function store(Request $request, Tournament $tournament): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'player_id' => ['required', 'exists:players,id'],
            'team_id' => ['required', 'exists:teams,id'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $team = Team::query()->findOrFail($data['team_id']);
        $player = Player::query()->findOrFail($data['player_id']);

        abort_unless($tournament->teams()->where('teams.id', $team->id)->exists(), 422);
        abort_unless((int) $player->team_id === (int) $team->id, 422);
        abort_unless($user->isAdmin() || $user->managesTeam($team->id) || (int) $tournament->user_id === (int) $user->id, 403);

        EligibilityException::query()->updateOrCreate(
            [
                'tournament_id' => $tournament->id,
                'player_id' => $player->id,
            ],
            [
                'team_id' => $team->id,
                'reason' => $data['reason'],
                'status' => EligibilityException::STATUS_PENDING,
                'requested_by' => $user->id,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_notes' => null,
            ]
        );

        return back()->with('status', 'Solicitud de excepción enviada al master.');
    }

    public function review(Request $request, EligibilityException $exception): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'review_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $exception->update([
            'status' => $data['status'],
            'review_notes' => $data['review_notes'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with(
            'status',
            $data['status'] === 'approved'
                ? 'Excepción de edad aprobada.'
                : 'Excepción de edad rechazada.'
        );
    }
}
