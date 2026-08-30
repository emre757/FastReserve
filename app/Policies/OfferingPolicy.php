<?php

namespace App\Policies;

use App\Enums\TeamPermission;
use App\Models\Offering;
use App\Models\Team;
use App\Models\User;

class OfferingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Offering $offering): bool
    {
        if (! $offering->trashed()) {
            return true;
        }

        // if soft deleted then only allow those with delete offering permission
        return $this->hasCurrentTeamPermission($user, $offering->team, TeamPermission::DeleteOffering);
    }

    private function hasCurrentTeamPermission(
        User $user,
        Team $team,
        ?TeamPermission $permission
    ): bool {
        return $user->current_team_id === $team->id
            && ($permission === null || $user->hasTeamPermission($team, $permission));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Team $team): bool
    {
        return $this->hasCurrentTeamPermission($user, $team, TeamPermission::CreateOffering);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Offering $offering): bool
    {
        return $this->hasCurrentTeamPermission($user, $offering->team, TeamPermission::UpdateOffering);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Offering $offering): bool
    {
        return $this->hasCurrentTeamPermission($user, $offering->team, TeamPermission::DeleteOffering);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Offering $offering): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Offering $offering): bool
    {
        return false;
    }
}
