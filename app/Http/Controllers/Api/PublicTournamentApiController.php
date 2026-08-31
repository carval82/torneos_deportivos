<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Services\CompetitionRulesService;
use App\Services\StandingCalculator;
use Illuminate\Http\JsonResponse;

class PublicTournamentApiController extends Controller
{
    public function __construct(
        private readonly StandingCalculator $standings,
        private readonly CompetitionRulesService $competitionRules,
    ) {}

    public function show(string $slug): JsonResponse
    {
        $tournament = $this->findPublic($slug)->load(['sport', 'teams']);

        return response()->json([
            'tournament' => $tournament,
            'standings' => $this->standings->table($tournament)->values(),
            'scorers' => $this->standings->scorers($tournament)->values(),
            'upcoming' => $tournament->games()
                ->with(['homeTeam', 'awayTeam'])
                ->where('status', '!=', 'finished')
                ->orderBy('scheduled_at')
                ->take(20)
                ->get(),
        ]);
    }

    public function fixture(string $slug): JsonResponse
    {
        $tournament = $this->findPublic($slug);

        return response()->json([
            'tournament' => $tournament->only(['id', 'name', 'public_slug', 'status']),
            'games' => $tournament->games()->with(['homeTeam', 'awayTeam'])->orderBy('matchday')->orderBy('scheduled_at')->get(),
        ]);
    }

    public function standings(string $slug): JsonResponse
    {
        $tournament = $this->findPublic($slug)->load('teams');

        return response()->json([
            'standings' => $this->standings->table($tournament)->values(),
        ]);
    }

    public function scorers(string $slug): JsonResponse
    {
        $tournament = $this->findPublic($slug)->load(['sport', 'teams']);

        return response()->json([
            'scorers' => $this->standings->scorers($tournament)->values(),
        ]);
    }

    public function rules(string $slug): JsonResponse
    {
        $tournament = $this->findPublic($slug);
        abort_unless($tournament->rules_published, 404);

        return response()->json([
            'rules' => $tournament->rules,
            'rules_summary' => $tournament->rules_summary,
            'competition_rules' => $this->competitionRules->for($tournament),
            'narrative' => $this->competitionRules->narrative($tournament),
        ]);
    }

    private function findPublic(string $slug): Tournament
    {
        return Tournament::query()
            ->where('public_slug', $slug)
            ->firstOrFail();
    }
}
