<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\User;
use App\Services\DisciplineService;
use App\Services\MatchSheetService;
use App\Services\RefereeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefereeApiController extends Controller
{
    public function __construct(
        private readonly MatchSheetService $sheets,
        private readonly RefereeService $referees,
        private readonly DisciplineService $discipline,
    ) {}

    public function games(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->isMatchOfficial() || $user?->isOrganizer(), 403);

        $assigned = Game::query()
            ->with(['homeTeam', 'awayTeam', 'tournament', 'referees'])
            ->whereHas('referees', fn ($q) => $q->where('users.id', $user->id))
            ->orderByRaw("CASE WHEN status = 'finished' THEN 1 ELSE 0 END")
            ->orderBy('scheduled_at')
            ->get();

        $toAssign = collect();
        if ($user->isOrganizer() || $user->isRefereeCoordinator() || $user->isAdmin()) {
            $toAssign = Game::query()
                ->with(['homeTeam', 'awayTeam', 'tournament', 'referees'])
                ->where('status', '!=', Game::STATUS_FINISHED)
                ->when(! $user->isAdmin(), function ($q) use ($user) {
                    $q->whereHas('tournament', function ($tq) use ($user) {
                        $tq->where('user_id', $user->id)
                            ->orWhere('referee_coordinator_id', $user->id);
                    });
                })
                ->orderBy('scheduled_at')
                ->get();
        }

        return response()->json([
            'assigned' => $assigned->map(fn (Game $game) => $this->serializeGame($game, $user))->values(),
            'coordinated' => $toAssign->map(fn (Game $game) => $this->serializeGame($game, $user))->values(),
            'role' => $user->role,
            'role_label' => $user->roleLabel(),
        ]);
    }

    public function show(Request $request, Game $game): JsonResponse
    {
        $this->authorize('view', $game);
        $game->load(['homeTeam', 'awayTeam', 'tournament', 'referees', 'events.player']);

        return response()->json($this->serializeGame($game, $request->user(), true));
    }

    public function updateScore(Request $request, Game $game): JsonResponse
    {
        $this->authorize('updateSheet', $game);

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

        return response()->json([
            'message' => 'Resultado actualizado.',
            'game' => $this->serializeGame($game->fresh(['homeTeam', 'awayTeam', 'tournament', 'referees']), $request->user()),
        ]);
    }

    public function storeEvent(Request $request, Game $game): JsonResponse
    {
        $this->authorize('updateSheet', $game);

        $data = $request->validate([
            'team_id' => ['required', 'in:'.$game->home_team_id.','.$game->away_team_id],
            'player_id' => ['nullable', 'exists:players,id'],
            'type' => ['required', 'in:goal,own_goal,assist,yellow,red,substitution'],
            'minute' => ['nullable', 'integer', 'min:1', 'max:130'],
            'note' => ['nullable', 'string', 'max:160'],
        ]);

        if (in_array($data['type'], ['yellow', 'red'], true)) {
            $result = $this->sheets->addCard($game, $data);

            return response()->json(['message' => $result['message']]);
        }

        $this->sheets->addEvent($game, $data);

        return response()->json(['message' => 'Evento cargado.']);
    }

    public function destroyEvent(Request $request, Game $game, GameEvent $event): JsonResponse
    {
        $this->authorize('updateSheet', $game);
        abort_unless($event->game_id === $game->id, 404);
        $this->discipline->removeEvent($game, $event);
        $this->sheets->syncScoreFromEvents($game->fresh());

        return response()->json(['message' => 'Evento eliminado.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeGame(Game $game, ?User $user, bool $detail = false): array
    {
        $payload = [
            'id' => $game->id,
            'matchday' => $game->matchday,
            'round_name' => $game->round_name,
            'scheduled_at' => optional($game->scheduled_at)?->toIso8601String(),
            'venue' => $game->locationLabel(),
            'status' => $game->status,
            'status_label' => $game->statusLabel(),
            'home_score' => $game->home_score,
            'away_score' => $game->away_score,
            'scoreline' => $game->scoreline(),
            'home_team' => [
                'id' => $game->homeTeam?->id,
                'name' => $game->homeTeam?->name,
            ],
            'away_team' => [
                'id' => $game->awayTeam?->id,
                'name' => $game->awayTeam?->name,
            ],
            'tournament' => [
                'id' => $game->tournament?->id,
                'name' => $game->tournament?->name,
                'referee_crew' => $game->tournament && $this->referees->crewSize($game->tournament) === 3
                    ? 'trio'
                    : 'single',
            ],
            'referees' => $game->referees->map(fn (User $official) => [
                'id' => $official->id,
                'name' => $official->name,
                'duty' => $official->pivot->duty,
            ])->values(),
            'referees_label' => $game->refereesLabel(),
            'can_edit' => $user ? $user->canManageMatchSheet($game) : false,
            'can_assign' => $user && $game->tournament ? $user->canAssignReferees($game->tournament) : false,
        ];

        if ($detail) {
            $payload['events'] = $game->events->map(fn (GameEvent $event) => [
                'id' => $event->id,
                'type' => $event->type,
                'minute' => $event->minute,
                'team_id' => $event->team_id,
                'player' => $event->player?->displayName(),
                'note' => $event->note,
            ])->values();
        }

        return $payload;
    }
}
