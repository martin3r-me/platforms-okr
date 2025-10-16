<?php

namespace Platform\Okr\Policies;

use Platform\Core\Models\User;
use Platform\Okr\Models\Cycle;

class CyclePolicy
{
    public function view(User $user, Cycle $cycle): bool
    {
        if ($cycle->team_id !== $user->current_team_id) {
            return false;
        }
        if (method_exists($user, 'isTeamOwner') && $user->isTeamOwner($user->current_team_id)) {
            return true;
        }
        if (method_exists($user, 'hasTeamRole') && $user->hasTeamRole('admin', $user->current_team_id)) {
            return true;
        }
        // Owner/Manager des zugehörigen OKR oder Cycle-Mitglied
        if ($cycle->okr && ($cycle->okr->user_id === $user->id || $cycle->okr->manager_user_id === $user->id)) {
            return true;
        }
        if (method_exists($cycle, 'members') && $cycle->members()->where('user_id', $user->id)->exists()) {
            return true;
        }
        return false;
    }

    public function update(User $user, Cycle $cycle): bool
    {
        if ($cycle->team_id !== $user->current_team_id) {
            return false;
        }
        if (method_exists($user, 'isTeamOwner') && $user->isTeamOwner($user->current_team_id)) {
            return true;
        }
        if (method_exists($user, 'hasTeamRole') && $user->hasTeamRole('admin', $user->current_team_id)) {
            return true;
        }
        if ($cycle->okr && ($cycle->okr->user_id === $user->id || $cycle->okr->manager_user_id === $user->id)) {
            return true;
        }
        return method_exists($cycle, 'members') && $cycle->members()->where('user_id', $user->id)->exists();
    }

    public function invite(User $user, Cycle $cycle): bool
    {
        // Gleiche Kriterien wie update
        return $this->update($user, $cycle);
    }

    public function removeMember(User $user, Cycle $cycle): bool
    {
        // Gleiche Kriterien wie update
        return $this->update($user, $cycle);
    }

    public function changeRole(User $user, Cycle $cycle): bool
    {
        // Gleiche Kriterien wie update
        return $this->update($user, $cycle);
    }
}


