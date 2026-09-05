<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Tournament;
use Illuminate\Validation\ValidationException;

class FixtureEditor
{
    /**
     * @param  array{home_team_id:int, away_team_id:int, matchday:int, scheduled_at:string, field_name?:string, venue?:string, round_name?:string}  $data
     */
    public function create(Tournament $tournament, array $data): Game
    {
        $this->assertPair($tournament, (int) $data['home_team_id'], (int) $data['away_team_id']);

        $scheduled = $data['scheduled_at'];
        $matchday = max(1, (int) $data['matchday']);

        $game = Game::create([
            'tournament_id' => $tournament->id,
            'home_team_id' => (int) $data['home_team_id'],
            'away_team_id' => (int) $data['away_team_id'],
            'matchday' => $matchday,
            'round_name' => ($data['round_name'] ?? null) ?: 'Fecha '.$matchday,
            'scheduled_at' => $scheduled,
            'original_scheduled_at' => $scheduled,
            'venue' => ($data['venue'] ?? null) ?: ($tournament->complex_name ?: $tournament->venue),
            'field_name' => ($data['field_name'] ?? null) ?: ($tournament->fieldList()[0] ?? 'Cancha 1'),
            'is_tentative' => true,
            'status' => Game::STATUS_SCHEDULED,
        ]);

        $tournament->stretchCalendarTo($game->scheduled_at);
        if (in_array($tournament->status, [Tournament::STATUS_DRAFT, Tournament::STATUS_INSCRIPTION], true)) {
            $tournament->update(['status' => Tournament::STATUS_ONGOING]);
        }

        return $game;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Game $game, array $data): Game
    {
        if ($game->status === Game::STATUS_FINISHED) {
            throw ValidationException::withMessages([
                'game' => 'Un partido finalizado no se edita desde el fixture. Abrí la planilla si hace falta corregir el resultado.',
            ]);
        }

        $home = (int) ($data['home_team_id'] ?? $game->home_team_id);
        $away = (int) ($data['away_team_id'] ?? $game->away_team_id);
        $this->assertPair($game->tournament, $home, $away);

        $matchday = max(1, (int) ($data['matchday'] ?? $game->matchday));
        $scheduled = $data['scheduled_at'] ?? $game->scheduled_at;

        if (! $game->original_scheduled_at) {
            $game->original_scheduled_at = $game->scheduled_at;
        }

        $game->fill([
            'home_team_id' => $home,
            'away_team_id' => $away,
            'matchday' => $matchday,
            'round_name' => ($data['round_name'] ?? null) ?: ('Fecha '.$matchday),
            'scheduled_at' => $scheduled,
            'field_name' => ($data['field_name'] ?? null) ?: $game->field_name,
            'venue' => $data['venue'] ?? $game->venue,
            'is_tentative' => true,
            'status' => $data['status'] ?? $game->status,
            'postpone_reason' => $data['postpone_reason'] ?? $game->postpone_reason,
        ])->save();

        $game->tournament->stretchCalendarTo($game->scheduled_at);

        return $game->fresh();
    }

    public function delete(Game $game): void
    {
        if ($game->status === Game::STATUS_FINISHED) {
            throw ValidationException::withMessages([
                'game' => 'No se puede borrar un partido ya jugado.',
            ]);
        }

        $game->delete();
    }

    private function assertPair(Tournament $tournament, int $home, int $away): void
    {
        if ($home === $away) {
            throw ValidationException::withMessages([
                'away_team_id' => 'El local y el visitante tienen que ser equipos distintos.',
            ]);
        }

        $ids = $tournament->teams()->pluck('teams.id')->all();
        if (! in_array($home, $ids, true) || ! in_array($away, $ids, true)) {
            throw ValidationException::withMessages([
                'home_team_id' => 'Los dos equipos tienen que estar inscriptos en este torneo.',
            ]);
        }
    }
}
