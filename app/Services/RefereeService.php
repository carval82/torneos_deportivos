<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GameReferee;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RefereeService
{
    public function __construct(private readonly CompetitionRulesService $rules) {}

    /**
     * @param  array{name: string, email: string, document_type: string, document_number: string, role?: string}  $data
     */
    public function createOfficial(array $data): User
    {
        $document = trim($data['document_number']);
        $role = $data['role'] ?? User::ROLE_REFEREE;
        if (! in_array($role, [User::ROLE_REFEREE, User::ROLE_REFEREE_COORDINATOR], true)) {
            $role = User::ROLE_REFEREE;
        }

        $user = User::query()->where('email', $data['email'])->first();
        $payload = [
            'name' => $data['name'],
            'document_type' => $data['document_type'],
            'document_number' => $document,
            'password' => $document,
            'role' => $role,
        ];

        if ($user) {
            if (in_array($user->role, [User::ROLE_ADMIN, User::ROLE_ORGANIZER], true)) {
                unset($payload['role']);
            }
            $user->update($payload);
        } else {
            $user = User::create([
                ...$payload,
                'email' => $data['email'],
                'email_verified_at' => now(),
            ]);
        }

        return $user->fresh();
    }

    /**
     * @param  array<string, int|string|null>  $assignments  duty => user_id
     */
    public function assignToGame(Game $game, array $assignments): void
    {
        $crew = $this->rules->for($game->tournament)['referee_crew'] ?? 'single';
        $allowed = $crew === 'trio'
            ? [GameReferee::DUTY_MAIN, GameReferee::DUTY_ASSISTANT_1, GameReferee::DUTY_ASSISTANT_2]
            : [GameReferee::DUTY_MAIN];

        $used = [];
        $rows = [];

        foreach ($allowed as $duty) {
            $userId = (int) ($assignments[$duty] ?? 0);
            if ($userId < 1) {
                continue;
            }
            if (in_array($userId, $used, true)) {
                throw ValidationException::withMessages([
                    'referees' => 'El mismo árbitro no puede ocupar dos cargos en el partido.',
                ]);
            }
            $official = User::query()->find($userId);
            if (! $official || ! $official->isMatchOfficial()) {
                throw ValidationException::withMessages([
                    'referees' => 'Elegí un árbitro o coordinador válido.',
                ]);
            }
            $used[] = $userId;
            $rows[] = [
                'game_id' => $game->id,
                'user_id' => $userId,
                'duty' => $duty,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        GameReferee::query()->where('game_id', $game->id)->delete();
        if ($rows !== []) {
            GameReferee::query()->insert($rows);
        }
    }

    public function crewSize(Tournament $tournament): int
    {
        return ($this->rules->for($tournament)['referee_crew'] ?? 'single') === 'trio' ? 3 : 1;
    }

    public function duties(Tournament $tournament): array
    {
        return $this->crewSize($tournament) === 3
            ? [
                GameReferee::DUTY_MAIN => 'Árbitro central',
                GameReferee::DUTY_ASSISTANT_1 => 'Asistente 1',
                GameReferee::DUTY_ASSISTANT_2 => 'Asistente 2',
            ]
            : [GameReferee::DUTY_MAIN => 'Árbitro'];
    }
}
