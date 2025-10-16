<?php

namespace Platform\Okr\Policies;

use Platform\Core\Models\User;
use Platform\Core\Enums\StandardRole;
use Platform\Core\Policies\RolePolicy;
use Platform\Okr\Models\Cycle;

class CyclePolicy extends RolePolicy
{
    public function view(User $user, Cycle $cycle): bool
    {
        return parent::view($user, $cycle);
    }

    public function update(User $user, Cycle $cycle): bool
    {
        return parent::update($user, $cycle);
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
        if (! $this->isInTeam($user, $cycle)) {
            return false;
        }
        return $this->hasRole($user, $cycle, StandardRole::getAdminRoles());
    }

    protected function getUserRole(User $user, $model): ?string
    {
        if (method_exists($model, 'members')) {
            $relation = $model->members()->where('user_id', $user->id)->first();
            return $relation?->pivot?->role ?? $relation?->role ?? null;
        }
        return null;
    }
}


