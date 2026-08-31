<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\CompetitionRulesService;
use App\Services\StandingCalculator;
use Illuminate\View\View;

class PublicTournamentController extends Controller
{
    public function __construct(
        private readonly StandingCalculator $standings,
        private readonly CompetitionRulesService $competitionRules,
    ) {}

    public function show(string $slug): View
    {
        $tournament = $this->findPublic($slug);
        $tournament->load(['sport', 'teams', 'games']);

        return view('public.tournament', [
            'tournament' => $tournament,
            'table' => $this->standings->table($tournament)->take(8),
            'upcoming' => $tournament->games()
                ->with(['homeTeam', 'awayTeam'])
                ->where('status', '!=', 'finished')
                ->orderBy('scheduled_at')
                ->take(6)
                ->get(),
            'section' => 'home',
        ]);
    }

    public function fixture(string $slug): View
    {
        $tournament = $this->findPublic($slug);
        $tournament->load(['games.homeTeam', 'games.awayTeam', 'sport']);

        return view('public.tournament', [
            'tournament' => $tournament,
            'gamesByMatchday' => $tournament->games->groupBy('matchday'),
            'section' => 'fixture',
        ]);
    }

    public function standings(string $slug): View
    {
        $tournament = $this->findPublic($slug);
        $tournament->load(['sport', 'teams']);

        return view('public.tournament', [
            'tournament' => $tournament,
            'table' => $this->standings->table($tournament),
            'section' => 'tabla',
        ]);
    }

    public function scorers(string $slug): View
    {
        $tournament = $this->findPublic($slug);
        $tournament->load(['sport', 'teams']);

        return view('public.tournament', [
            'tournament' => $tournament,
            'scorers' => $this->standings->scorers($tournament),
            'section' => 'goleadores',
        ]);
    }

    public function rules(string $slug): View
    {
        $tournament = $this->findPublic($slug);
        abort_unless($tournament->rules_published, 404);
        $tournament->load('sport');

        return view('public.tournament', [
            'tournament' => $tournament,
            'competitionRules' => $this->competitionRules->for($tournament),
            'competitionNarrative' => $this->competitionRules->narrative($tournament),
            'section' => 'reglamento',
        ]);
    }

    private function findPublic(string $slug): Tournament
    {
        // App cerrada: solo usuarios logueados llegan acá.
        return Tournament::query()
            ->where('public_slug', $slug)
            ->firstOrFail();
    }
}
