<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GameEvent;
use App\Models\Suspension;
use Illuminate\Support\Collection;

class DisciplineService
{
    /**
     * Procesa una tarjeta. Si es la 2ª amarilla del partido, genera expulsión
     * por doble amarilla y la suspensión según el reglamento del torneo.
     *
     * @return array{event: GameEvent, expulsion: ?GameEvent, suspension: ?Suspension, message: string}
     */
    public function registerCard(Game $game, array $data): array
    {
        $event = $game->events()->create($data);
        $expulsion = null;
        $suspension = null;
        $message = $event->label().' cargada.';

        if ($event->type === GameEvent::TYPE_YELLOW && $event->player_id) {
            $yellows = $game->events()
                ->where('player_id', $event->player_id)
                ->where('type', GameEvent::TYPE_YELLOW)
                ->count();

            if ($yellows >= 2) {
                $expulsion = $game->events()->create([
                    'team_id' => $event->team_id,
                    'player_id' => $event->player_id,
                    'type' => GameEvent::TYPE_RED,
                    'minute' => $event->minute,
                    'note' => 'Expulsión por doble amarilla',
                ]);

                $suspension = $this->createSuspension(
                    $game,
                    $expulsion,
                    'double_yellow',
                    (int) ($game->tournament->double_yellow_ban_matches ?: 1),
                    'Doble amarilla en el mismo partido'
                );

                $message = 'Segunda amarilla: expulsión automática y '.$suspension->matches_total.' fecha(s) de sanción.';
            }
        }

        if ($event->type === GameEvent::TYPE_RED && $event->player_id && ! str_contains((string) $event->note, 'doble amarilla')) {
            $suspension = $this->createSuspension(
                $game,
                $event,
                'red',
                (int) ($game->tournament->red_ban_matches ?: 1),
                $event->note ?: 'Roja directa'
            );
            $message = 'Roja cargada: '.$suspension->matches_total.' fecha(s) de sanción según reglamento.';
        }

        return compact('event', 'expulsion', 'suspension', 'message');
    }

    public function createSuspension(
        Game $game,
        GameEvent $event,
        string $cardType,
        int $matches,
        string $reason,
    ): Suspension {
        $matches = max(1, $matches);

        return Suspension::create([
            'tournament_id' => $game->tournament_id,
            'player_id' => $event->player_id,
            'team_id' => $event->team_id,
            'source_game_id' => $game->id,
            'source_event_id' => $event->id,
            'reason' => $reason,
            'card_type' => $cardType,
            'matches_total' => $matches,
            'matches_remaining' => $matches,
            'is_active' => true,
        ]);
    }

    public function removeEvent(Game $game, GameEvent $event): void
    {
        if (in_array($event->type, [GameEvent::TYPE_YELLOW, GameEvent::TYPE_RED], true) && $event->player_id) {
            Suspension::query()
                ->where('source_event_id', $event->id)
                ->delete();

            if ($event->type === GameEvent::TYPE_YELLOW) {
                $autoRed = $game->events()
                    ->where('player_id', $event->player_id)
                    ->where('type', GameEvent::TYPE_RED)
                    ->where('note', 'like', '%doble amarilla%')
                    ->first();

                if ($autoRed) {
                    Suspension::query()->where('source_event_id', $autoRed->id)->delete();
                    $autoRed->delete();
                }
            }
        }

        $event->delete();
    }

    /**
     * Al finalizar un partido, descuenta 1 fecha a los suspendidos de los equipos que jugaron.
     */
    public function consumeSuspensions(Game $game): void
    {
        Suspension::query()
            ->where('tournament_id', $game->tournament_id)
            ->where('is_active', true)
            ->where('matches_remaining', '>', 0)
            ->whereIn('team_id', [$game->home_team_id, $game->away_team_id])
            ->where(function ($query) use ($game) {
                $query->whereNull('source_game_id')
                    ->orWhere('source_game_id', '!=', $game->id);
            })
            ->get()
            ->each(function (Suspension $suspension) {
                $remaining = max(0, $suspension->matches_remaining - 1);
                $suspension->update([
                    'matches_remaining' => $remaining,
                    'is_active' => $remaining > 0,
                ]);
            });
    }

    /**
     * @return Collection<int, Suspension>
     */
    public function activeForTournament(int $tournamentId): Collection
    {
        return Suspension::query()
            ->with(['player', 'team'])
            ->where('tournament_id', $tournamentId)
            ->where('is_active', true)
            ->where('matches_remaining', '>', 0)
            ->latest()
            ->get();
    }

    /**
     * @return array<int, Suspension>
     */
    public function activeForPlayers(int $tournamentId, array $playerIds): array
    {
        return Suspension::query()
            ->where('tournament_id', $tournamentId)
            ->where('is_active', true)
            ->where('matches_remaining', '>', 0)
            ->whereIn('player_id', $playerIds)
            ->get()
            ->keyBy('player_id')
            ->all();
    }
}
