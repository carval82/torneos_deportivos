<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\Roster;
use Illuminate\Support\Collection;

class MatchSheetService
{
    public function __construct(
        private readonly StandingCalculator $standings,
        private readonly DisciplineService $discipline,
    ) {}

    public function addEvent(Game $game, array $data): GameEvent
    {
        if (in_array($data['type'] ?? '', [GameEvent::TYPE_YELLOW, GameEvent::TYPE_RED], true)) {
            $result = $this->discipline->registerCard($game->loadMissing('tournament'), $data);
            $this->syncScoreFromEvents($game);

            return $result['event'];
        }

        $event = $game->events()->create($data);
        $this->syncScoreFromEvents($game);

        return $event;
    }

    /**
     * @return array{event: GameEvent, expulsion: ?GameEvent, suspension: ?\App\Models\Suspension, message: string}
     */
    public function addCard(Game $game, array $data): array
    {
        $result = $this->discipline->registerCard($game->loadMissing('tournament'), $data);
        $this->syncScoreFromEvents($game);

        return $result;
    }

    public function syncScoreFromEvents(Game $game): void
    {
        $home = 0;
        $away = 0;

        foreach ($game->events()->get() as $event) {
            if ($event->type === GameEvent::TYPE_GOAL) {
                if ($event->team_id === $game->home_team_id) {
                    $home++;
                } else {
                    $away++;
                }
            }

            if ($event->type === GameEvent::TYPE_OWN_GOAL) {
                if ($event->team_id === $game->home_team_id) {
                    $away++;
                } else {
                    $home++;
                }
            }
        }

        if ($game->events()->whereIn('type', [GameEvent::TYPE_GOAL, GameEvent::TYPE_OWN_GOAL])->exists()) {
            $game->update([
                'home_score' => $home,
                'away_score' => $away,
            ]);
        }
    }

    public function finish(Game $game): void
    {
        $this->syncScoreFromEvents($game);

        $game->update([
            'status' => Game::STATUS_FINISHED,
            'home_score' => $game->home_score ?? 0,
            'away_score' => $game->away_score ?? 0,
        ]);

        $this->discipline->consumeSuspensions($game);
        $this->standings->snapshotMatchday($game->tournament, (int) $game->matchday);

        $pending = $game->tournament->games()->where('status', '!=', Game::STATUS_FINISHED)->exists();
        if (! $pending) {
            $game->tournament->update(['status' => \App\Models\Tournament::STATUS_FINISHED]);
        }
    }

    /**
     * @param  array<int, array{player_id: int, team_id: int, status: string, minutes_played?: int|null}>  $rows
     */
    public function saveAttendance(Game $game, array $rows): void
    {
        foreach ($rows as $row) {
            Attendance::updateOrCreate(
                [
                    'game_id' => $game->id,
                    'player_id' => $row['player_id'],
                ],
                [
                    'team_id' => $row['team_id'],
                    'status' => $row['status'],
                    'minutes_played' => $row['minutes_played'] ?? null,
                ]
            );
        }
    }

    /**
     * @return Collection<int, object>
     */
    public function attendanceRanking(int $tournamentId): Collection
    {
        return Attendance::query()
            ->whereHas('game', fn ($query) => $query->where('tournament_id', $tournamentId))
            ->with('player.team')
            ->get()
            ->groupBy('player_id')
            ->map(function ($items) {
                $present = $items->filter(fn (Attendance $row) => $row->countsAsPresent())->count();
                $total = $items->count();

                return (object) [
                    'player' => $items->first()->player,
                    'present' => $present,
                    'absent' => $total - $present,
                    'total' => $total,
                    'rate' => $total > 0 ? round($present / $total * 100, 1) : 0,
                ];
            })
            ->sortByDesc('present')
            ->values();
    }

    public function enrollTeamRoster(int $tournamentId, int $teamId): int
    {
        $players = \App\Models\Player::query()->where('team_id', $teamId)->get();
        $count = 0;

        foreach ($players as $player) {
            Roster::updateOrCreate(
                [
                    'tournament_id' => $tournamentId,
                    'player_id' => $player->id,
                ],
                [
                    'team_id' => $teamId,
                    'jersey_number' => $player->jersey_number,
                    'position' => $player->position,
                    'is_active' => true,
                ]
            );
            $count++;
        }

        return $count;
    }
}
