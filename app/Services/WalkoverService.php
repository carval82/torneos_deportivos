<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;

class WalkoverService
{
    public function __construct(
        private readonly CompetitionRulesService $rules,
        private readonly StandingCalculator $standings,
    ) {}

    /**
     * @return array{game: Game, disqualified: bool, message: string}
     */
    public function registerNoShow(Game $game, Team $absentTeam, ?string $note = null): array
    {
        $game->loadMissing(['tournament.teams', 'homeTeam', 'awayTeam']);
        $tournament = $game->tournament;

        if ($game->status === Game::STATUS_FINISHED) {
            throw new \RuntimeException('Este partido ya está finalizado.');
        }

        if (! in_array($absentTeam->id, [$game->home_team_id, $game->away_team_id], true)) {
            throw new \RuntimeException('El equipo no participa en este partido.');
        }

        if ($tournament->isTeamDisqualified($absentTeam->id)) {
            throw new \RuntimeException('El equipo ya está descalificado.');
        }

        $disqualified = false;

        DB::transaction(function () use ($game, $tournament, $absentTeam, $note, &$disqualified) {
            $this->applyWalkover($game, $tournament, $absentTeam, 'no_show', $note);

            $pivot = $tournament->teams()->where('teams.id', $absentTeam->id)->first()?->pivot;
            $count = (int) ($pivot?->no_show_count ?? 0) + 1;

            $tournament->teams()->updateExistingPivot($absentTeam->id, [
                'no_show_count' => $count,
            ]);

            $max = $this->rules->for($tournament)['max_no_shows_before_dq'];
            if ($count >= $max) {
                $this->disqualifyTeam(
                    $tournament->fresh(['teams']),
                    $absentTeam,
                    "Descalificado por {$count} inasistencia(s) (W.O.)"
                );
                $disqualified = true;
            }
        });

        $game->refresh();

        $message = $disqualified
            ? "W.O. cargado. {$absentTeam->name} quedó descalificado: sus partidos pendientes se dieron por W.O. al rival."
            : "W.O. cargado: no se presentó {$absentTeam->name}.";

        return [
            'game' => $game,
            'disqualified' => $disqualified,
            'message' => $message,
        ];
    }

    public function disqualifyTeam(Tournament $tournament, Team $team, string $reason): void
    {
        $tournament->teams()->updateExistingPivot($team->id, [
            'status' => 'disqualified',
            'disqualified_at' => now(),
            'disqualify_reason' => $reason,
        ]);

        $rules = $this->rules->for($tournament);

        if ($rules['on_disqualification'] !== 'wo_remaining') {
            return;
        }

        $pending = $tournament->games()
            ->where('status', '!=', Game::STATUS_FINISHED)
            ->where(function ($query) use ($team) {
                $query->where('home_team_id', $team->id)
                    ->orWhere('away_team_id', $team->id);
            })
            ->get();

        foreach ($pending as $game) {
            $this->applyWalkover($game, $tournament, $team, 'disqualification', $reason);
        }
    }

    private function applyWalkover(
        Game $game,
        Tournament $tournament,
        Team $againstTeam,
        string $reason,
        ?string $note = null,
    ): void {
        $rules = $this->rules->for($tournament);
        $for = $rules['walkover_goals_for'];
        $against = $rules['walkover_goals_against'];
        $homeWins = $againstTeam->id === $game->away_team_id;

        $notes = trim(($game->notes ? $game->notes."\n" : '').($note ?: "W.O.: {$againstTeam->name} — {$reason}"));

        $game->update([
            'home_score' => $homeWins ? $for : $against,
            'away_score' => $homeWins ? $against : $for,
            'status' => Game::STATUS_FINISHED,
            'result_type' => Game::RESULT_WALKOVER,
            'walkover_against_team_id' => $againstTeam->id,
            'walkover_reason' => $reason,
            'is_tentative' => false,
            'notes' => $notes,
        ]);

        $this->standings->snapshotMatchday($tournament, (int) $game->matchday);

        if (! $tournament->games()->where('status', '!=', Game::STATUS_FINISHED)->exists()) {
            $tournament->update(['status' => Tournament::STATUS_FINISHED]);
        }
    }
}
