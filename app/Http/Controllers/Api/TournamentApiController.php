<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Player;
use App\Models\Tournament;
use App\Services\EligibilityChecker;
use App\Services\MatchSheetService;
use App\Services\ProbabilityCalculator;
use App\Services\StandingCalculator;
use Illuminate\Http\JsonResponse;

class TournamentApiController extends Controller
{
    public function __construct(
        private readonly StandingCalculator $standings,
        private readonly ProbabilityCalculator $probabilities,
        private readonly MatchSheetService $sheets,
        private readonly EligibilityChecker $eligibility,
    ) {}

    public function index(): JsonResponse
    {
        $user = request()->user();
        abort_unless($user, 401);

        $query = Tournament::with(['sport'])
            ->withCount(['teams', 'games'])
            ->orderByDesc('tournaments.id');

        if ($user->isAdmin()) {
            // Master: todos los torneos.
        } elseif ($user->role === \App\Models\User::ROLE_ORGANIZER) {
            // Organizador: solo los que creó.
            $query->where('user_id', $user->id);
        } else {
            // Delegado / jugador: solo torneos de sus equipos.
            $teamIds = $user->teams()->pluck('teams.id');
            if ($user->player?->team_id) {
                $teamIds->push($user->player->team_id);
            }
            $teamIds = $teamIds->unique()->filter()->values();
            if ($teamIds->isEmpty()) {
                return response()->json([]);
            }
            $query->whereHas('teams', fn ($q) => $q->whereIn('teams.id', $teamIds));
        }

        return response()->json(
            $query->get()->map(fn (Tournament $tournament) => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'public_slug' => $tournament->public_slug,
                'status' => $tournament->status,
                'status_label' => $tournament->statusLabel(),
                'season' => $tournament->season,
                'sport' => $tournament->sport?->name,
                'teams_count' => $tournament->teams_count,
                'games_count' => $tournament->games_count,
                'complex_name' => $tournament->complex_name,
            ])->values()
        );
    }

    public function show(Tournament $tournament): JsonResponse
    {
        $tournament->load(['sport', 'ageCategory', 'teams', 'games.homeTeam', 'games.awayTeam']);

        return response()->json([
            'tournament' => $tournament,
            'standings' => $this->standings->table($tournament)->values(),
            'scorers' => $this->standings->scorers($tournament)->values(),
            'curves' => $this->standings->performanceCurves($tournament),
            'title_odds' => $this->probabilities->titleOdds($tournament)->values(),
            'attendance' => $this->sheets->attendanceRanking($tournament->id)->values(),
        ]);
    }

    public function game(Game $game): JsonResponse
    {
        $game->load(['tournament', 'homeTeam', 'awayTeam', 'events.player', 'attendances.player']);

        return response()->json([
            'game' => $game,
            'odds' => $this->probabilities->matchOdds($game),
        ]);
    }

    public function player(Player $player): JsonResponse
    {
        $player->load('team');

        return response()->json([
            'player' => $player,
            'photo_url' => $player->photoUrl(),
            'document_photo_url' => $player->documentPhotoUrl(),
            'eligibility' => $this->eligibility->check($player),
            'goals' => $player->events()->where('type', 'goal')->count(),
        ]);
    }
}
