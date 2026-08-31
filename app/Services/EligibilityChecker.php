<?php

namespace App\Services;

use App\Models\EligibilityException;
use App\Models\Player;
use App\Models\Tournament;

class EligibilityChecker
{
    /**
     * @return array{eligible: bool, age: int|null, reason: string|null, warnings: array<int, string>, exception: ?EligibilityException}
     */
    public function check(Player $player, ?Tournament $tournament = null): array
    {
        $reference = $tournament?->start_date ?? now();
        $age = $player->age($reference);
        $warnings = [];
        $exception = null;

        if (! $player->birthdate) {
            return [
                'eligible' => false,
                'age' => null,
                'reason' => 'Falta la fecha de nacimiento.',
                'warnings' => ['Sin fecha de nacimiento no se puede validar el límite de edad.'],
                'exception' => null,
            ];
        }

        if (! $player->document_photo_path) {
            $warnings[] = 'Falta la foto del documento.';
        }

        if (! $player->photo_path) {
            $warnings[] = 'Falta la foto de la ficha.';
        }

        if (! $tournament) {
            return [
                'eligible' => true,
                'age' => $age,
                'reason' => null,
                'warnings' => $warnings,
                'exception' => null,
            ];
        }

        $exception = EligibilityException::query()
            ->where('tournament_id', $tournament->id)
            ->where('player_id', $player->id)
            ->first();

        $minAge = $tournament->effectiveMinAge();
        $maxAge = $tournament->effectiveMaxAge();
        $gender = $tournament->effectiveGenderRule();
        $label = $tournament->ageLabel();

        if ($minAge !== null && $age < $minAge) {
            if ($exception?->isApproved()) {
                $warnings[] = "Menor a la categoría ({$age} años). Autorizado por master.";

                return [
                    'eligible' => true,
                    'age' => $age,
                    'reason' => null,
                    'warnings' => $warnings,
                    'exception' => $exception,
                ];
            }

            $reason = "Tiene {$age} años y el reglamento ({$label}) pide mínimo {$minAge}.";
            if ($exception?->status === EligibilityException::STATUS_PENDING) {
                $warnings[] = 'Excepción de edad pendiente de aprobación del master.';
            }

            return [
                'eligible' => false,
                'age' => $age,
                'reason' => $reason,
                'warnings' => $warnings,
                'exception' => $exception,
            ];
        }

        if ($maxAge !== null && $age > $maxAge) {
            return [
                'eligible' => false,
                'age' => $age,
                'reason' => "Tiene {$age} años y supera el tope de {$maxAge} definido en {$label}.",
                'warnings' => $warnings,
                'exception' => $exception,
            ];
        }

        if ($gender !== 'mixto' && $player->gender !== $gender) {
            return [
                'eligible' => false,
                'age' => $age,
                'reason' => "El género del jugador no coincide con la regla del torneo ({$tournament->genderLabel()}).",
                'warnings' => $warnings,
                'exception' => $exception,
            ];
        }

        return [
            'eligible' => true,
            'age' => $age,
            'reason' => null,
            'warnings' => $warnings,
            'exception' => $exception,
        ];
    }
}
