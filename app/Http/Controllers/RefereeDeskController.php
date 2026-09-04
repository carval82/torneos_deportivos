<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Tournament;
use App\Models\User;
use App\Services\RefereeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RefereeDeskController extends Controller
{
    public function __construct(private readonly RefereeService $referees) {}

    public function index(): View
    {
        $user = Auth::user();
        abort_unless(
            $user && ($user->isMatchOfficial() || $user->isOrganizer()),
            403
        );

        $assigned = Game::query()
            ->with(['homeTeam', 'awayTeam', 'tournament', 'referees'])
            ->whereHas('referees', fn ($q) => $q->where('users.id', $user->id))
            ->where('status', '!=', Game::STATUS_FINISHED)
            ->orderBy('scheduled_at')
            ->get();

        $recent = Game::query()
            ->with(['homeTeam', 'awayTeam', 'tournament', 'referees'])
            ->whereHas('referees', fn ($q) => $q->where('users.id', $user->id))
            ->where('status', Game::STATUS_FINISHED)
            ->latest('updated_at')
            ->take(8)
            ->get();

        $coordinated = collect();
        if ($user->isRefereeCoordinator() || $user->isAdmin() || $user->isOrganizer()) {
            $tournamentQuery = Tournament::query()->with(['games.homeTeam', 'games.awayTeam', 'games.referees']);
            if ($user->isRefereeCoordinator() && ! $user->isAdmin()) {
                $tournamentQuery->where('referee_coordinator_id', $user->id);
            } elseif ($user->isOrganizer() && ! $user->isAdmin()) {
                $tournamentQuery->where('user_id', $user->id);
            }
            $coordinated = $tournamentQuery
                ->latest()
                ->get()
                ->flatMap(function (Tournament $tournament) {
                    return $tournament->games
                        ->where('status', '!=', Game::STATUS_FINISHED)
                        ->sortBy('scheduled_at')
                        ->values();
                })
                ->values();
        }

        return view('referee.desk', [
            'assigned' => $assigned,
            'recent' => $recent,
            'coordinated' => $coordinated,
            'officials' => User::query()
                ->whereIn('role', [User::ROLE_REFEREE, User::ROLE_REFEREE_COORDINATOR])
                ->orderBy('name')
                ->get(),
            'refereeService' => $this->referees,
        ]);
    }
}
