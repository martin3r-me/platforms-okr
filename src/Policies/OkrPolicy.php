<?php

namespace Platform\Okr\Policies;

use Platform\Core\Models\User;
use Platform\Okr\Models\Okr;

class OkrPolicy
{
    public function viewAny(User $user): bool
    {
        return !is_null($user->current_team_id);
    }

    public function view(User $user, Okr $okr): bool
    {
        if ($okr->team_id !== $user->current_team_id) {
            return false;
        }

        // Owner/Admin des Teams
        if (method_exists($user, 'isTeamOwner') && $user->isTeamOwner($user->current_team_id)) {
            return true;
        }
        if (method_exists($user, 'hasTeamRole') && $user->hasTeamRole('admin', $user->current_team_id)) {
            return true;
        }

        // Manager oder Owner des OKR oder explizite Freigabe via relation (optional)
        if ($okr->manager_user_id === $user->id) {
            return true;
        }
        if ($okr->user_id === $user->id) {
            return true;
        }

        // Optional: contributors/viewers relation auf OKR
        if (method_exists($okr, 'members') && $okr->members()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return !is_null($user->current_team_id);
    }

    public function update(User $user, Okr $okr): bool
    {
        if ($okr->team_id !== $user->current_team_id) {
            return false;
        }
        if (method_exists($user, 'isTeamOwner') && $user->isTeamOwner($user->current_team_id)) {
            return true;
        }
        if (method_exists($user, 'hasTeamRole') && $user->hasTeamRole('admin', $user->current_team_id)) {
            return true;
        }
        return $okr->manager_user_id === $user->id || $okr->user_id === $user->id;
    }

    public function delete(User $user, Okr $okr): bool
    {
        return $this->update($user, $okr);
    }
}
