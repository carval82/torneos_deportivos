<?php

namespace App\Services;

use App\Models\Tournament;

class CompetitionRulesService
{
    /**
     * @return array{
     *     walkover_goals_for: int,
     *     walkover_goals_against: int,
     *     max_no_shows_before_dq: int,
     *     on_disqualification: string,
     *     count_wo_in_standings: bool
     * }
     */
    public function defaults(): array
    {
        return [
            'walkover_goals_for' => 3,
            'walkover_goals_against' => 0,
            'max_no_shows_before_dq' => 2,
            'on_disqualification' => 'wo_remaining',
            'count_wo_in_standings' => true,
        ];
    }

    /**
     * @return array{
     *     walkover_goals_for: int,
     *     walkover_goals_against: int,
     *     max_no_shows_before_dq: int,
     *     on_disqualification: string,
     *     count_wo_in_standings: bool
     * }
     */
    public function for(Tournament $tournament): array
    {
        $rules = array_merge($this->defaults(), $tournament->competition_rules ?? []);

        return [
            'walkover_goals_for' => max(0, (int) ($rules['walkover_goals_for'] ?? 3)),
            'walkover_goals_against' => max(0, (int) ($rules['walkover_goals_against'] ?? 0)),
            'max_no_shows_before_dq' => max(1, (int) ($rules['max_no_shows_before_dq'] ?? 2)),
            'on_disqualification' => ($rules['on_disqualification'] ?? 'wo_remaining') === 'bye_rest'
                ? 'bye_rest'
                : 'wo_remaining',
            'count_wo_in_standings' => (bool) ($rules['count_wo_in_standings'] ?? true),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function normalize(array $input): array
    {
        $defaults = $this->defaults();

        return [
            'walkover_goals_for' => max(0, min(20, (int) ($input['walkover_goals_for'] ?? $defaults['walkover_goals_for']))),
            'walkover_goals_against' => max(0, min(20, (int) ($input['walkover_goals_against'] ?? $defaults['walkover_goals_against']))),
            'max_no_shows_before_dq' => max(1, min(20, (int) ($input['max_no_shows_before_dq'] ?? $defaults['max_no_shows_before_dq']))),
            'on_disqualification' => ($input['on_disqualification'] ?? 'wo_remaining') === 'bye_rest'
                ? 'bye_rest'
                : 'wo_remaining',
            'count_wo_in_standings' => (bool) ($input['count_wo_in_standings'] ?? true),
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

        return "W.O. por no presentación: resultado {$score} a favor del equipo presente. "
            ."Tras {$n} inasistencia(s) el equipo queda descalificado y {$dq}.";
    }
}
