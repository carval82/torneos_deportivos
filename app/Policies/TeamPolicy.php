<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Team $team): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isOrganizer() || $user->isAdmin();
    }

    public function update(User $user, Team $team): bool
    {
        return $user->isAdmin()
            || $user->isOrganizer()
            || $user->managesTeam($team->id);
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->isAdmin() || $user->isOrganizer();
    }

    public function manageRoster(User $user, Team $team): bool
    {
        return $user->isAdmin()
            || $user->isOrganizer()
            || $user->managesTeam($team->id);
    }
}
