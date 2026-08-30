<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Tournament;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class FixtureGenerator
{
    public function generate(Tournament $tournament): int
    {
        $teamIds = $tournament->teams()->pluck('teams.id')->all();

        if (count($teamIds) < 2) {
            throw new \RuntimeException('Se necesitan al menos 2 equipos para armar el fixture.');
        }

        if ($tournament->max_teams && count($teamIds) > $tournament->max_teams) {
            throw new \RuntimeException("Hay más equipos inscriptos ({$tournament->max_teams} es el tope).");
        }

        if ($tournament->games()->exists()) {
            throw new \RuntimeException('Este torneo ya tiene partidos. Borrá el fixture actual si querés regenerarlo.');
        }

        $pairs = $tournament->format === Tournament::FORMAT_KNOCKOUT
            ? $this->knockoutPairs($teamIds)
            : $this->leaguePairs($teamIds, (bool) $tournament->double_round);

        $fields = $tournament->fieldList();
        $playDays = $tournament->playDayList();
        $start = ($tournament->start_date?->copy() ?? now())->startOfDay();
        $time = $tournament->match_start_time
            ? Carbon::parse($tournament->match_start_time)->format('H:i')
            : '09:00';
        $interval = max(30, (int) ($tournament->match_interval_minutes ?: 90));
        $complex = $tournament->complex_name ?: $tournament->venue;

        $matchdayDates = $this->matchdayDates(
            $start,
            $playDays,
            (int) collect($pairs)->max('matchday'),
            (int) ($tournament->days_between_rounds ?: 7)
        );

        return DB::transaction(function () use ($tournament, $pairs, $fields, $matchdayDates, $time, $interval, $complex) {
            $created = 0;
            $slotCounters = [];

            foreach ($pairs as $pair) {
                $matchday = $pair['matchday'];
                $slotIndex = $slotCounters[$matchday] ?? 0;
                $slotCounters[$matchday] = $slotIndex + 1;

                $fieldIndex = $slotIndex % count($fields);
                $wave = intdiv($slotIndex, count($fields));
                $date = $matchdayDates[$matchday] ?? $matchdayDates[1];
                [$hour, $minute] = array_map('intval', explode(':', $time));
                $scheduled = $date->copy()->setTime($hour, $minute)->addMinutes($wave * $interval);

                Game::create([
                    'tournament_id' => $tournament->id,
                    'home_team_id' => $pair['home'],
                    'away_team_id' => $pair['away'],
                    'matchday' => $matchday,
                    'round_name' => $pair['round_name'] ?? 'Fecha '.$matchday,
                    'scheduled_at' => $scheduled,
                    'original_scheduled_at' => $scheduled,
                    'venue' => $complex,
                    'field_name' => $fields[$fieldIndex],
                    'is_tentative' => true,
                    'status' => Game::STATUS_SCHEDULED,
                ]);
                $created++;
            }

            $tournament->update(['status' => Tournament::STATUS_ONGOING]);

            return $created;
        });
    }

    /**
     * @param  list<int>  $playDays
     * @return array<int, CarbonInterface>
     */
    public function matchdayDates(CarbonInterface $start, array $playDays, int $matchdays, int $daysBetween = 7): array
    {
        $daysBetween = max(1, $daysBetween);
        $cursor = $start->copy()->startOfDay();
        $dates = [];
        $guard = 0;

        while (! in_array($cursor->dayOfWeek, $playDays, true) && $guard < 21) {
            $cursor->addDay();
            $guard++;
        }

        for ($i = 1; $i <= max($matchdays, 1); $i++) {
            $dates[$i] = $cursor->copy();
            $cursor = $cursor->copy()->addDays($daysBetween);
            $hop = 0;
            while (! in_array($cursor->dayOfWeek, $playDays, true) && $hop < 21) {
                $cursor->addDay();
                $hop++;
            }
        }

        return $dates;
    }

    /**
     * @param  list<int>  $teamIds
     * @return list<array{home: int, away: int, matchday: int, round_name?: string}>
     */
    public function leaguePairs(array $teamIds, bool $double): array
    {
        $ids = array_values($teamIds);

        if (count($ids) % 2 !== 0) {
            $ids[] = null;
        }

        $n = count($ids);
        $rounds = $n - 1;
        $half = (int) ($n / 2);
        $games = [];

        for ($round = 0; $round < $rounds; $round++) {
            for ($i = 0; $i < $half; $i++) {
                $home = $ids[$i];
                $away = $ids[$n - 1 - $i];

                if ($home === null || $away === null) {
                    continue;
                }

                if ($round % 2 === 1) {
                    [$home, $away] = [$away, $home];
                }

                $games[] = [
                    'home' => $home,
                    'away' => $away,
                    'matchday' => $round + 1,
                    'round_name' => 'Fecha '.($round + 1),
                ];
            }

            $fixed = $ids[0];
            $rest = array_slice($ids, 1);
            array_unshift($rest, array_pop($rest));
            $ids = array_merge([$fixed], $rest);
        }

        if ($double) {
            $return = [];
            foreach ($games as $game) {
                $return[] = [
                    'home' => $game['away'],
                    'away' => $game['home'],
                    'matchday' => $game['matchday'] + $rounds,
                    'round_name' => 'Fecha '.($game['matchday'] + $rounds),
                ];
            }
            $games = array_merge($games, $return);
        }

        return $games;
    }

    /**
     * @param  list<int>  $teamIds
     * @return list<array{home: int, away: int, matchday: int, round_name?: string}>
     */
    public function knockoutPairs(array $teamIds): array
    {
        $ids = array_values($teamIds);
        shuffle($ids);

        $games = [];
        $matchday = 1;

        for ($i = 0; $i < count($ids); $i += 2) {
            if (! isset($ids[$i + 1])) {
                continue;
            }

            $games[] = [
                'home' => $ids[$i],
                'away' => $ids[$i + 1],
                'matchday' => $matchday,
                'round_name' => $this->knockoutRoundName(count($ids)),
            ];
        }

        return $games;
    }

    private function knockoutRoundName(int $teams): string
    {
        return match (true) {
            $teams <= 2 => 'Final',
            $teams <= 4 => 'Semifinal',
            $teams <= 8 => 'Cuartos',
            $teams <= 16 => 'Octavos',
            default => 'Eliminatoria',
        };
    }
}
