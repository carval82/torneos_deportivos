<?php

namespace App\Services;

use App\Models\Tournament;

class CompetitionRulesService
{
    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'walkover_goals_for' => 3,
            'walkover_goals_against' => 0,
            'max_no_shows_before_dq' => 2,
            'on_disqualification' => 'wo_remaining',
            'count_wo_in_standings' => true,
            // open = libre hasta que el organizador configure tope
            'roster_lock_mode' => 'open',
            'roster_lock_until' => null,
            'roster_lock_matchday' => 1,
            'allow_roster_changes_round1' => true,
            'referee_crew' => 'single',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function for(Tournament $tournament): array
    {
        $rules = array_merge($this->defaults(), $tournament->competition_rules ?? []);
        $mode = $rules['roster_lock_mode'] ?? 'open';
        if (! in_array($mode, ['open', 'until_date', 'after_matchday'], true)) {
            $mode = 'open';
        }
        $crew = in_array($rules['referee_crew'] ?? 'single', ['single', 'trio'], true)
            ? ($rules['referee_crew'] ?? 'single')
            : 'single';

        return [
            'walkover_goals_for' => max(0, (int) ($rules['walkover_goals_for'] ?? 3)),
            'walkover_goals_against' => max(0, (int) ($rules['walkover_goals_against'] ?? 0)),
            'max_no_shows_before_dq' => max(1, (int) ($rules['max_no_shows_before_dq'] ?? 2)),
            'on_disqualification' => ($rules['on_disqualification'] ?? 'wo_remaining') === 'bye_rest'
                ? 'bye_rest'
                : 'wo_remaining',
            'count_wo_in_standings' => (bool) ($rules['count_wo_in_standings'] ?? true),
            'roster_lock_mode' => $mode,
            'roster_lock_until' => $rules['roster_lock_until'] ?? null,
            'roster_lock_matchday' => max(1, (int) ($rules['roster_lock_matchday'] ?? 1)),
            'allow_roster_changes_round1' => (bool) ($rules['allow_roster_changes_round1'] ?? true),
            'referee_crew' => $crew,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function normalize(array $input): array
    {
        $defaults = $this->defaults();
        $mode = $input['roster_lock_mode'] ?? $defaults['roster_lock_mode'];
        if (! in_array($mode, ['open', 'until_date', 'after_matchday'], true)) {
            $mode = 'open';
        }
        $crew = in_array($input['referee_crew'] ?? 'single', ['single', 'trio'], true)
            ? ($input['referee_crew'] ?? 'single')
            : 'single';

        return [
            'walkover_goals_for' => max(0, min(20, (int) ($input['walkover_goals_for'] ?? $defaults['walkover_goals_for']))),
            'walkover_goals_against' => max(0, min(20, (int) ($input['walkover_goals_against'] ?? $defaults['walkover_goals_against']))),
            'max_no_shows_before_dq' => max(1, min(20, (int) ($input['max_no_shows_before_dq'] ?? $defaults['max_no_shows_before_dq']))),
            'on_disqualification' => ($input['on_disqualification'] ?? 'wo_remaining') === 'bye_rest'
                ? 'bye_rest'
                : 'wo_remaining',
            'count_wo_in_standings' => (bool) ($input['count_wo_in_standings'] ?? true),
            'roster_lock_mode' => $mode,
            'roster_lock_until' => ! empty($input['roster_lock_until'])
                ? (string) $input['roster_lock_until']
                : null,
            'roster_lock_matchday' => max(1, min(40, (int) ($input['roster_lock_matchday'] ?? 1))),
            'allow_roster_changes_round1' => (bool) ($input['allow_roster_changes_round1'] ?? true),
            'referee_crew' => $crew,
        ];
    }

    public function narrative(Tournament $tournament): string
    {
        $rules = $this->for($tournament);
        $score = $rules['walkover_goals_for'].'-'.$rules['walkover_goals_against'];
        $n = $rules['max_no_shows_before_dq'];
        $dq = $rules['on_disqualification'] === 'wo_remaining'
            ? 'los partidos pendientes se dan por W.O. a favor del rival'
            : 'los rivales de partidos pendientes descansan (bye)';

        $roster = match ($rules['roster_lock_mode']) {
            'until_date' => $rules['roster_lock_until']
                ? 'Cambios de plantilla habilitados hasta el '.$rules['roster_lock_until'].'.'
                : 'Cambios de plantilla con fecha límite pendiente de definir.',
            'after_matchday' => 'Cambios de plantilla hasta antes de la Fecha '.$rules['roster_lock_matchday'].'.',
            default => 'Cambios de plantilla abiertos hasta que el organizador fije un tope.',
        };

        $crew = $rules['referee_crew'] === 'trio'
            ? 'Cada partido se cubre con terna arbitral (central y dos asistentes).'
            : 'Cada partido se cubre con un árbitro.';

        return "W.O. por no presentación: resultado {$score} a favor del equipo presente. "
            ."Tras {$n} inasistencia(s) el equipo queda descalificado y {$dq} "
            .$roster
            .' '.$crew
            .' Jugadores por debajo de la categoría requieren autorización del master.';
    }
}
