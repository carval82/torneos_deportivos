<?php

namespace App\Policies;

use App\Models\Tournament;
use App\Models\User;

class TournamentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOrganizer() || $user->isDelegate() || $user->isAdmin();
    }

    public function view(User $user, Tournament $tournament): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($tournament->user_id === $user->id) {
            return true;
        }

        if ((int) $tournament->referee_coordinator_id === (int) $user->id) {
            return true;
        }

        return $tournament->teams()
            ->whereIn('teams.id', $user->teams()->pluck('teams.id'))
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->isOrganizer() || $user->isAdmin();
    }

    public function update(User $user, Tournament $tournament): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($tournament->isReadOnly()) {
            return false;
        }

        return $tournament->user_id === $user->id;
    }

    public function delete(User $user, Tournament $tournament): bool
    {
        return $user->isAdmin() || $tournament->user_id === $user->id;
    }

    public function manage(User $user, Tournament $tournament): bool
    {
        return $this->update($user, $tournament);
    }

    public function invite(User $user, Tournament $tournament): bool
    {
        return $this->update($user, $tournament);
    }

    public function renew(User $user, Tournament $tournament): bool
    {
        return $user->isAdmin() || $tournament->user_id === $user->id;
    }
}
