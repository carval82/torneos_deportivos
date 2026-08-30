<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Player;
use App\Models\Tournament;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PlayerPortalService
{
    public function __construct(private readonly StandingCalculator $standings) {}

    public function findByDocument(string $documentNumber): Player
    {
        $documentNumber = trim($documentNumber);

        if ($documentNumber === '') {
            throw ValidationException::withMessages([
                'document_number' => 'Ingresá tu cédula.',
            ]);
        }

        $player = Player::query()
            ->with('team')
            ->where('document_number', $documentNumber)
            ->first();

        if (! $player) {
            throw ValidationException::withMessages([
                'document_number' => 'Esa cédula no está registrada en ninguna plantilla.',
            ]);
        }

        return $player;
    }

    /**
     * Torneos donde el jugador está en roster (plantilla del torneo).
     *
     * @return Collection<int, Tournament>
     */
    public function linkedTournaments(Player $player): Collection
    {
        return $player->rosters()
            ->where('is_active', true)
            ->with(['tournament.sport', 'tournament.teams'])
            ->get()
            ->pluck('tournament')
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * @return array{
     *     player: Player,
     *     tournaments: Collection<int, Tournament>,
     *     upcoming: Collection<int, Game>,
     *     results: Collection<int, Game>,
     *     tables: Collection<int, Collection>,
     *     teamStanding: Collection<int, object|null>
     * }
     */
    public function dashboard(Player $player): array
    {
        $player->loadMissing('team');
        $tournaments = $this->linkedTournaments($player);
        $tournamentIds = $tournaments->pluck('id');
        $teamId = $player->team_id;

        $upcoming = collect();
        $results = collect();

        if ($teamId && $tournamentIds->isNotEmpty()) {
            $upcoming = Game::query()
                ->with(['homeTeam', 'awayTeam', 'tournament'])
                ->whereIn('tournament_id', $tournamentIds)
                ->where(function ($q) use ($teamId) {
                    $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId);
                })
                ->where('status', '!=', Game::STATUS_FINISHED)
                ->orderBy('scheduled_at')
                ->take(12)
                ->get();

            $results = Game::query()
                ->with(['homeTeam', 'awayTeam', 'tournament'])
                ->whereIn('tournament_id', $tournamentIds)
                ->where(function ($q) use ($teamId) {
                    $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId);
                })
                ->where('status', Game::STATUS_FINISHED)
                ->latest('updated_at')
                ->take(12)
                ->get();
        }

        $tables = $tournaments->mapWithKeys(function (Tournament $tournament) {
            return [$tournament->id => $this->standings->table($tournament->loadMissing('teams'))];
        });

        $teamStanding = $tournaments->mapWithKeys(function (Tournament $tournament) use ($tables, $teamId) {
            $row = ($tables[$tournament->id] ?? collect())->firstWhere('team_id', $teamId);

            return [$tournament->id => $row];
        });

        return [
            'player' => $player,
            'tournaments' => $tournaments,
            'upcoming' => $upcoming,
            'results' => $results,
            'tables' => $tables,
            'teamStanding' => $teamStanding,
        ];
    }
}
