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
        $query = Tournament::with(['sport', 'ageCategory'])
            ->withCount(['teams', 'games'])
            ->latest();

        if ($user && ! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return response()->json($query->get());
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
