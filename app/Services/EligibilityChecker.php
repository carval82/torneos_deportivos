<?php

namespace App\Services;

use App\Models\Player;
use App\Models\Tournament;

class EligibilityChecker
{
    /**
     * @return array{eligible: bool, age: int|null, reason: string|null, warnings: array<int, string>}
     */
    public function check(Player $player, ?Tournament $tournament = null): array
    {
        $reference = $tournament?->start_date ?? now();
        $age = $player->age($reference);
        $warnings = [];

        if (! $player->birthdate) {
            return [
                'eligible' => false,
                'age' => null,
                'reason' => 'Falta la fecha de nacimiento.',
                'warnings' => ['Sin fecha de nacimiento no se puede validar el límite de edad.'],
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
            ];
        }

        $minAge = $tournament->effectiveMinAge();
        $maxAge = $tournament->effectiveMaxAge();
        $gender = $tournament->effectiveGenderRule();
        $label = $tournament->ageLabel();

        if ($minAge !== null && $age < $minAge) {
            return [
                'eligible' => false,
                'age' => $age,
                'reason' => "Tiene {$age} años y el reglamento ({$label}) pide mínimo {$minAge}.",
                'warnings' => $warnings,
            ];
        }

        if ($maxAge !== null && $age > $maxAge) {
            return [
                'eligible' => false,
                'age' => $age,
                'reason' => "Tiene {$age} años y supera el tope de {$maxAge} definido en {$label}.",
                'warnings' => $warnings,
            ];
        }

        if ($gender !== 'mixto' && $player->gender !== $gender) {
            return [
                'eligible' => false,
                'age' => $age,
                'reason' => "El género del jugador no coincide con la regla del torneo ({$tournament->genderLabel()}).",
                'warnings' => $warnings,
            ];
        }

        return [
            'eligible' => true,
            'age' => $age,
            'reason' => null,
            'warnings' => $warnings,
        ];
    }
}
