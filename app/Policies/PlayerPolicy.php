<?php

namespace App\Policies;

use App\Models\Player;
use App\Models\User;

class PlayerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Player $player): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isOrganizer() || $user->isAdmin() || $user->isDelegate();
    }

    public function update(User $user, Player $player): bool
    {
        if ($user->isAdmin() || $user->isOrganizer()) {
            return true;
        }

        return $player->team_id && $user->managesTeam((int) $player->team_id);
    }

    public function delete(User $user, Player $player): bool
    {
        return $this->update($user, $player);
    }
}
