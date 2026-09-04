<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Player;
use App\Models\Sport;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        $user = Auth::user();

        if ($user->isPlayer() && $user->player_id) {
            return redirect()->route('player.home');
        }

        if ($user->role === 'delegate' && ! $user->isOrganizer()) {
            return redirect()->route('delegate.index');
        }

        if (($user->isReferee() || $user->isRefereeCoordinator()) && ! $user->isOrganizer()) {
            return redirect()->route('referee.desk');
        }

        $tournamentQuery = Tournament::query();
        $teamQuery = Team::query();
        $playerQuery = Player::query();
        $gameQuery = Game::query();

        if (! $user->isAdmin()) {
            $tournamentQuery->where('user_id', $user->id);
            $ownedIds = (clone $tournamentQuery)->pluck('id');
            $gameQuery->whereIn('tournament_id', $ownedIds);
        }

        return view('dashboard', [
            'stats' => [
                'tournaments' => (clone $tournamentQuery)->count(),
                'teams' => $teamQuery->count(),
                'players' => $playerQuery->count(),
                'games' => (clone $gameQuery)->count(),
            ],
            'sports' => Sport::withCount('tournaments')->get(),
            'activeTournaments' => (clone $tournamentQuery)->with(['sport', 'ageCategory'])
                ->latest()
                ->take(6)
                ->get(),
            'upcomingGames' => (clone $gameQuery)->with(['homeTeam', 'awayTeam', 'tournament'])
                ->where('status', '!=', Game::STATUS_FINISHED)
                ->orderBy('scheduled_at')
                ->take(6)
                ->get(),
            'recentResults' => (clone $gameQuery)->with(['homeTeam', 'awayTeam', 'tournament'])
                ->where('status', Game::STATUS_FINISHED)
                ->latest('updated_at')
                ->take(6)
                ->get(),
        ]);
    }
}
