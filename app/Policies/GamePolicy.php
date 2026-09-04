<?php

namespace App\Policies;

use App\Models\Game;
use App\Models\User;

class GamePolicy
{
    public function view(User $user, Game $game): bool
    {
        return $user->canManageMatchSheet($game) || $user->canAssignReferees($game->tournament);
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
