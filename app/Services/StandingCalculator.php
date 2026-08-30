<?php

namespace App\Services;

use App\Models\Game;
use App\Models\StandingSnapshot;
use App\Models\Tournament;
use Illuminate\Support\Collection;

class StandingCalculator
{
    /**
     * @return Collection<int, object>
     */
    public function table(Tournament $tournament, ?int $untilMatchday = null): Collection
    {
        $rows = [];

        foreach ($tournament->teams as $team) {
            $rows[$team->id] = (object) [
                'team_id' => $team->id,
                'team' => $team,
                'disqualified' => ($team->pivot?->status ?? 'active') === 'disqualified',
                'no_show_count' => (int) ($team->pivot?->no_show_count ?? 0),
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'goal_difference' => 0,
                'points' => 0,
                'form' => [],
            ];
        }

        $games = $tournament->games()
            ->where('status', Game::STATUS_FINISHED)
            ->when($untilMatchday, fn ($query) => $query->where('matchday', '<=', $untilMatchday))
            ->orderBy('matchday')
            ->orderBy('id')
            ->get();

        foreach ($games as $game) {
            $this->applyGame($rows, $game, $tournament);
        }

        $sorted = collect($rows)
            ->sortByDesc(fn ($row) => sprintf(
                '%d-%05d-%05d-%05d-%05d',
                $row->disqualified ? 0 : 1,
                $row->points,
                5000 + $row->goal_difference,
                $row->goals_for,
                $row->won
            ))
            ->values();

        $activePos = 0;

        return $sorted->map(function ($row) use (&$activePos) {
            $row->form = array_slice($row->form, -5);
            if ($row->disqualified) {
                $row->position = null;
            } else {
                $activePos++;
                $row->position = $activePos;
            }

            return $row;
        });
    }

    /**
     * @return Collection<int, object>
     */
    public function scorers(Tournament $tournament): Collection
    {
        $unit = $tournament->sport?->scoring_unit ?? 'goles';

        return $tournament->games()
            ->where('status', Game::STATUS_FINISHED)
            ->with(['events.player.team'])
            ->get()
            ->pluck('events')
            ->flatten()
            ->where('type', 'goal')
            ->groupBy('player_id')
            ->filter(fn ($events, $playerId) => $playerId)
            ->map(function ($events) use ($unit) {
                $player = $events->first()->player;

                return (object) [
                    'player' => $player,
                    'team' => $player?->team,
                    'goals' => $events->count(),
                    'unit' => $unit,
                ];
            })
            ->sortByDesc('goals')
            ->values()
            ->map(function ($row, $index) {
                $row->position = $index + 1;

                return $row;
            });
    }

    public function snapshotMatchday(Tournament $tournament, int $matchday): void
    {
        foreach ($this->table($tournament, $matchday) as $row) {
            StandingSnapshot::updateOrCreate(
                [
                    'tournament_id' => $tournament->id,
                    'team_id' => $row->team_id,
                    'matchday' => $matchday,
                ],
                [
                    'position' => $row->position,
                    'points' => $row->points,
                    'played' => $row->played,
                    'won' => $row->won,
                    'drawn' => $row->drawn,
                    'lost' => $row->lost,
                    'goals_for' => $row->goals_for,
                    'goals_against' => $row->goals_against,
                    'goal_difference' => $row->goal_difference,
                ]
            );
        }
    }

    /**
     * @return array{labels: list<int>, series: list<array{team_id: int, name: string, color: string|null, positions: list<int|null>}>}
     */
    public function performanceCurves(Tournament $tournament): array
    {
        $maxMatchday = (int) $tournament->games()->max('matchday');
        $labels = range(1, max($maxMatchday, 1));
        $series = [];

        foreach ($tournament->teams as $team) {
            $positions = [];
            foreach ($labels as $matchday) {
                $snapshot = $tournament->snapshots()
                    ->where('team_id', $team->id)
                    ->where('matchday', $matchday)
                    ->first();

                $positions[] = $snapshot?->position;
            }

            $series[] = [
                'team_id' => $team->id,
                'name' => $team->name,
                'color' => $team->primary_color,
                'positions' => $positions,
            ];
        }

        return [
            'labels' => $labels,
            'series' => $series,
        ];
    }

    /**
     * @param  array<int, object>  $rows
     */
    private function applyGame(array &$rows, Game $game, Tournament $tournament): void
    {
        if (! isset($rows[$game->home_team_id], $rows[$game->away_team_id])) {
            return;
        }

        $home = $rows[$game->home_team_id];
        $away = $rows[$game->away_team_id];
        $homeScore = (int) $game->home_score;
        $awayScore = (int) $game->away_score;

        $home->played++;
        $away->played++;
        $home->goals_for += $homeScore;
        $home->goals_against += $awayScore;
        $away->goals_for += $awayScore;
        $away->goals_against += $homeScore;
        $home->goal_difference = $home->goals_for - $home->goals_against;
        $away->goal_difference = $away->goals_for - $away->goals_against;

        $wo = $game->result_type === Game::RESULT_WALKOVER;

        if ($homeScore > $awayScore) {
            $home->won++;
            $away->lost++;
            $home->points += $tournament->points_win;
            $away->points += $tournament->points_loss;
            $home->form[] = $wo ? 'WO' : 'W';
            $away->form[] = $wo ? 'L' : 'L';
        } elseif ($homeScore < $awayScore) {
            $away->won++;
            $home->lost++;
            $away->points += $tournament->points_win;
            $home->points += $tournament->points_loss;
            $home->form[] = $wo ? 'L' : 'L';
            $away->form[] = $wo ? 'WO' : 'W';
        } else {
            $home->drawn++;
            $away->drawn++;
            $home->points += $tournament->points_draw;
            $away->points += $tournament->points_draw;
            $home->form[] = 'D';
            $away->form[] = 'D';
        }
    }
}
