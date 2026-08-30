<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameEvent;
use App\Models\Roster;
use App\Models\Team;
use App\Services\CompetitionRulesService;
use App\Services\DisciplineService;
use App\Services\MatchSheetService;
use App\Services\ProbabilityCalculator;
use App\Services\WalkoverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameController extends Controller
{
    public function __construct(
        private readonly MatchSheetService $sheets,
        private readonly ProbabilityCalculator $probabilities,
        private readonly DisciplineService $discipline,
        private readonly WalkoverService $walkovers,
        private readonly CompetitionRulesService $competitionRules,
    ) {}

    public function show(Game $game): View
    {
        $game->load([
            'tournament.sport',
            'tournament.teams',
            'homeTeam',
            'awayTeam',
            'walkoverAgainstTeam',
            'events.player',
            'events.team',
            'attendances.player',
        ]);

        $rosters = Roster::with('player')
            ->where('tournament_id', $game->tournament_id)
            ->whereIn('team_id', [$game->home_team_id, $game->away_team_id])
            ->where('is_active', true)
            ->get()
            ->groupBy('team_id');

        $playerIds = $rosters->flatten()->pluck('player_id')->all();
        $attendance = $game->attendances->keyBy('player_id');
        $goals = $game->events->whereIn('type', ['goal', 'own_goal', 'assist']);
        $cards = $game->events->whereIn('type', ['yellow', 'red']);

        return view('games.show', [
            'game' => $game,
            'rosters' => $rosters,
            'attendance' => $attendance,
            'odds' => $this->probabilities->matchOdds($game),
            'goals' => $goals,
            'cards' => $cards,
            'suspensions' => $this->discipline->activeForPlayers($game->tournament_id, $playerIds),
            'competitionRules' => $this->competitionRules->for($game->tournament),
        ]);
    }

    public function walkover(Request $request, Game $game): RedirectResponse
    {
        $data = $request->validate([
            'absent_team_id' => ['required', 'in:'.$game->home_team_id.','.$game->away_team_id],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $result = $this->walkovers->registerNoShow(
                $game,
                Team::findOrFail($data['absent_team_id']),
                $data['note'] ?? null
            );
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['walkover' => $exception->getMessage()]);
        }

        return redirect()
            ->route('games.show', $game)
            ->with('status', $result['message']);
    }

    public function updateScore(Request $request, Game $game): RedirectResponse
    {
        $data = $request->validate([
            'home_score' => ['required', 'integer', 'min:0', 'max:99'],
            'away_score' => ['required', 'integer', 'min:0', 'max:99'],
            'status' => ['required', 'in:scheduled,live,finished,postponed'],
            'notes' => ['nullable', 'string'],
        ]);

        $game->update($data);

        if ($data['status'] === Game::STATUS_FINISHED) {
            $this->sheets->finish($game->fresh());
        }

        return back()->with('status', 'Resultado actualizado.');
    }

    public function storeEvent(Request $request, Game $game): RedirectResponse
    {
        $data = $request->validate([
            'team_id' => ['required', 'in:'.$game->home_team_id.','.$game->away_team_id],
            'player_id' => ['nullable', 'exists:players,id'],
            'type' => ['required', 'in:goal,own_goal,assist,yellow,red,substitution'],
            'minute' => ['nullable', 'integer', 'min:1', 'max:130'],
            'note' => ['nullable', 'string', 'max:160'],
        ]);

        if (in_array($data['type'], ['yellow', 'red'], true)) {
            $result = $this->sheets->addCard($game, $data);

            return back()->with('status', $result['message']);
        }

        $this->sheets->addEvent($game, $data);

        return back()->with('status', 'Evento cargado en la planilla.');
    }

    public function destroyEvent(Game $game, GameEvent $event): RedirectResponse
    {
        abort_unless($event->game_id === $game->id, 404);
        $this->discipline->removeEvent($game, $event);
        $this->sheets->syncScoreFromEvents($game->fresh());

        return back()->with('status', 'Evento eliminado.');
    }

    public function saveAttendance(Request $request, Game $game): RedirectResponse
    {
        $data = $request->validate([
            'rows' => ['required', 'array'],
            'rows.*.player_id' => ['required', 'exists:players,id'],
            'rows.*.team_id' => ['required', 'exists:teams,id'],
            'rows.*.status' => ['required', 'in:starter,substitute,present,absent,injured'],
            'rows.*.minutes_played' => ['nullable', 'integer', 'min:0', 'max:130'],
        ]);

        $this->sheets->saveAttendance($game, $data['rows']);

        return back()->with('status', 'Asistencia guardada.');
    }

    public function reschedule(Request $request, Game $game): RedirectResponse
    {
        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'field_name' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:scheduled,postponed,live,finished'],
            'is_tentative' => ['sometimes', 'boolean'],
            'postpone_reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        if (! $game->original_scheduled_at) {
            $game->original_scheduled_at = $game->scheduled_at;
        }

        $game->fill([
            'scheduled_at' => $data['scheduled_at'],
            'field_name' => $data['field_name'] ?: $game->field_name,
            'status' => $data['status'],
            'is_tentative' => $request->boolean('is_tentative', $data['status'] !== Game::STATUS_FINISHED),
            'postpone_reason' => $data['status'] === Game::STATUS_POSTPONED
                ? ($data['postpone_reason'] ?: 'Aplazado por organización')
                : null,
            'notes' => $data['notes'] ?? $game->notes,
        ])->save();

        return back()->with('status', $data['status'] === Game::STATUS_POSTPONED
            ? 'Partido aplazado. La nueva fecha quedó como tentativa.'
            : 'Fecha y cancha actualizadas.');
    }
}
