<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Tournament;
use Illuminate\Support\Collection;

class ProbabilityCalculator
{
    public function __construct(private readonly StandingCalculator $standings) {}

    /**
     * @return array{home_win: float, draw: float, away_win: float, home_strength: float, away_strength: float}
     */
    public function matchOdds(Game $game): array
    {
        $table = $this->standings->table($game->tournament)->keyBy('team_id');
        $home = $table->get($game->home_team_id);
        $away = $table->get($game->away_team_id);

        $homeStrength = $this->strength($home) * 1.12;
        $awayStrength = $this->strength($away);

        $homeExp = exp($homeStrength / 12);
        $awayExp = exp($awayStrength / 12);
        $drawExp = exp((($homeStrength + $awayStrength) / 2) / 14);
        $total = $homeExp + $awayExp + $drawExp;

        return [
            'home_win' => round($homeExp / $total * 100, 1),
            'draw' => round($drawExp / $total * 100, 1),
            'away_win' => round($awayExp / $total * 100, 1),
            'home_strength' => round($homeStrength, 2),
            'away_strength' => round($awayStrength, 2),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function titleOdds(Tournament $tournament, int $simulations = 400): Collection
    {
        $current = $this->standings->table($tournament);
        $remaining = $tournament->games()
            ->where('status', '!=', Game::STATUS_FINISHED)
            ->get();

        $wins = [];
        foreach ($current as $row) {
            $wins[$row->team_id] = 0;
        }

        $oddsByGame = [];
        foreach ($remaining as $game) {
            $oddsByGame[$game->id] = $this->matchOdds($game);
        }

        if ($remaining->isEmpty()) {
            $leader = $current->first();
            if ($leader) {
                $wins[$leader->team_id] = $simulations;
            }
        } else {
            for ($i = 0; $i < $simulations; $i++) {
                $points = [];
                foreach ($current as $row) {
                    $points[$row->team_id] = $row->points;
                }

                foreach ($remaining as $game) {
                    $odds = $oddsByGame[$game->id];
                    $roll = mt_rand(1, 1000) / 10;
                    if ($roll <= $odds['home_win']) {
                        $points[$game->home_team_id] += $tournament->points_win;
                    } elseif ($roll <= $odds['home_win'] + $odds['draw']) {
                        $points[$game->home_team_id] += $tournament->points_draw;
                        $points[$game->away_team_id] += $tournament->points_draw;
                    } else {
                        $points[$game->away_team_id] += $tournament->points_win;
                    }
                }

                arsort($points);
                $championId = array_key_first($points);
                $wins[$championId]++;
            }
        }

        return $current->map(function ($row) use ($wins, $simulations) {
            $row->title_probability = round(($wins[$row->team_id] ?? 0) / max($simulations, 1) * 100, 1);

            return $row;
        });
    }

    private function strength(?object $row): float
    {
        if (! $row || $row->played === 0) {
            return 8.0;
        }

        $formScore = collect($row->form)->sum(fn ($result) => match ($result) {
            'W' => 3,
            'D' => 1,
            default => 0,
        });

        return ($row->points / max($row->played, 1) * 4)
            + ($row->goal_difference * 0.35)
            + $formScore
            + 6;
    }
}
