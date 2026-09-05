<?php

namespace App\Policies;

use App\Models\Game;
use App\Models\User;

class GamePolicy
{
    public function view(User $user, Game $game): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $tournament = $game->relationLoaded('tournament')
            ? $game->tournament
            : $game->tournament()->first();

        if ($tournament && ((int) $tournament->user_id === (int) $user->id
            || (int) $tournament->referee_coordinator_id === (int) $user->id)) {
            return true;
        }

        return $game->referees()->where('users.id', $user->id)->exists();
    }

    public function updateSheet(User $user, Game $game): bool
    {
        return $user->canManageMatchSheet($game);
    }

    public function organize(User $user, Game $game): bool
    {
        return $user->canAssignReferees($game->tournament);
    }

    public function assignReferees(User $user, Game $game): bool
    {
        return $user->canAssignReferees($game->tournament);
    }
}
