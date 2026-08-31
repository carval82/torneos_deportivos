<?php

namespace App\Services;

use App\Models\Tournament;
use Carbon\Carbon;

class RosterLockService
{
    public function __construct(private readonly CompetitionRulesService $rules) {}

    /**
     * @return array{open: bool, mode: string, message: string, until: ?string, matchday: ?int}
     */
    public function status(Tournament $tournament): array
    {
        $rules = $this->rules->for($tournament);
        $mode = $rules['roster_lock_mode'];

        if ($mode === 'open') {
            return [
                'open' => true,
                'mode' => $mode,
                'message' => 'La plantilla está abierta. El organizador aún no fijó un tope.',
                'until' => null,
                'matchday' => null,
            ];
        }

        if ($mode === 'until_date') {
            $until = $rules['roster_lock_until']
                ? Carbon::parse($rules['roster_lock_until'])->endOfDay()
                : null;

            if (! $until) {
                return [
                    'open' => true,
                    'mode' => $mode,
                    'message' => 'Falta definir la fecha límite de cambios de plantilla.',
                    'until' => null,
                    'matchday' => null,
                ];
            }

            $open = now()->lte($until);

            return [
                'open' => $open,
                'mode' => $mode,
                'message' => $open
                    ? 'Podés gestionar la plantilla hasta el '.$until->format('d/m/Y').'.'
                    : 'La plantilla cerró el '.$until->format('d/m/Y').'. Solo el master puede autorizar excepciones.',
                'until' => $until->toDateString(),
                'matchday' => null,
            ];
        }

        // after_matchday: open while no finished games at/after that matchday
        $lockMatchday = max(1, (int) $rules['roster_lock_matchday']);
        $started = $tournament->games()
            ->where('matchday', '>=', $lockMatchday)
            ->where('status', 'finished')
            ->exists();

        return [
            'open' => ! $started,
            'mode' => 'after_matchday',
            'message' => $started
                ? "La plantilla cerró al disputarse la Fecha {$lockMatchday}."
                : "Podés cambiar jugadores hasta antes de que se dispute la Fecha {$lockMatchday}.",
            'until' => null,
            'matchday' => $lockMatchday,
        ];
    }

    public function canModify(Tournament $tournament): bool
    {
        return $this->status($tournament)['open'];
    }

    public function assertOpen(Tournament $tournament): void
    {
        $status = $this->status($tournament);
        if (! $status['open']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'roster' => $status['message'],
            ]);
        }
    }
}
