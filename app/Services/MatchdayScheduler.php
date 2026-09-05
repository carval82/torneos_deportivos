<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Tournament;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class MatchdayScheduler
{
    /**
     * Aplaza una fecha completa (ej. por lluvia) y corre el calendario.
     * Usa el intervalo del torneo (ej. 7/8 días) y acomoda al próximo día de juego
     * permitido (domingo, etc.). Las fechas siguientes también se corren.
     *
     * @return array{moved: int, days: int, new_date: string|null}
     */
    public function postponeMatchday(Tournament $tournament, int $matchday, string $reason = 'Postergada por clima / cancha natural'): array
    {
        $playDays = $tournament->playDayList();

        $pendingOnMatchday = $tournament->games()
            ->where('matchday', $matchday)
            ->where('status', '!=', Game::STATUS_FINISHED)
            ->get();

        if ($pendingOnMatchday->isEmpty()) {
            throw new \RuntimeException('No hay partidos pendientes en esa fecha para aplazar.');
        }

        $referenceDay = $pendingOnMatchday
            ->map(fn (Game $game) => $game->scheduled_at?->toDateString())
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();

        $targetGames = $pendingOnMatchday->filter(
            fn (Game $game) => $game->scheduled_at?->toDateString() === $referenceDay
        )->values();

        if ($targetGames->isEmpty()) {
            throw new \RuntimeException('No hay partidos pendientes en esa fecha para aplazar.');
        }

        $reference = $targetGames->first()->scheduled_at?->copy() ?? now();
        // Lluvia / cancha natural: pasar al próximo día de juego (ej. próximo domingo),
        // no sumar el intervalo de 8 días (eso saltaría una semana de más).
        $newMatchdayDate = $this->nextPlayDate($reference->copy()->addDay(), $playDays);
        $deltaDays = (int) $reference->copy()->startOfDay()->diffInDays($newMatchdayDate->copy()->startOfDay());

        if ($deltaDays < 1) {
            $newMatchdayDate = $reference->copy()->addWeek();
            $deltaDays = 7;
        }

        $laterGames = $tournament->games()
            ->where('matchday', '>', $matchday)
            ->where('status', '!=', Game::STATUS_FINISHED)
            ->get();

        $moved = 0;
        $sampleDate = $newMatchdayDate->format('d/m/Y');

        DB::transaction(function () use ($targetGames, $laterGames, $deltaDays, $reason, &$moved) {
            foreach ($targetGames as $game) {
                $this->shiftGame($game, $deltaDays, $reason);
                $moved++;
            }

            foreach ($laterGames as $game) {
                $this->shiftGame(
                    $game,
                    $deltaDays,
                    trim(($game->postpone_reason ? $game->postpone_reason.' · ' : '').'Corrido por aplazo de fecha anterior')
                );
                $moved++;
            }
        });

        $latest = $tournament->games()->max('scheduled_at');
        if ($latest) {
            $tournament->stretchCalendarTo($latest);
        }

        return [
            'moved' => $moved,
            'days' => (int) $deltaDays,
            'new_date' => $sampleDate,
        ];
    }

    private function shiftGame(Game $game, int $deltaDays, string $reason): void
    {
        if (! $game->scheduled_at) {
            return;
        }

        if (! $game->original_scheduled_at) {
            $game->original_scheduled_at = $game->scheduled_at;
        }

        $game->update([
            'scheduled_at' => $game->scheduled_at->copy()->addDays($deltaDays),
            'status' => Game::STATUS_SCHEDULED,
            'is_tentative' => true,
            'postpone_reason' => $reason,
            'original_scheduled_at' => $game->original_scheduled_at,
        ]);
    }

    /**
     * @param  list<int>  $playDays
     */
    private function nextPlayDate(CarbonInterface $from, array $playDays): CarbonInterface
    {
        $cursor = $from->copy()->startOfDay();

        for ($i = 0; $i < 21; $i++) {
            if (in_array($cursor->dayOfWeek, $playDays, true)) {
                return $cursor;
            }
            $cursor->addDay();
        }

        return $from->copy();
    }
}
