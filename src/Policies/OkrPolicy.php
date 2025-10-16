<?php

namespace Platform\Okr\Policies;

use Platform\Core\Models\User;
use Platform\Core\Enums\StandardRole;
use Platform\Core\Policies\RolePolicy;
use Platform\Okr\Models\Okr;

class OkrPolicy extends RolePolicy
{
    public function viewAny(User $user): bool
    {
        return !is_null($user->current_team_id);
    }

    public function create(User $user): bool
    {
        return !is_null($user->current_team_id);
    }

    public function delete(User $user, $model): bool
    {
        // Erbt Löschlogik aus RolePolicy (Owner/Admin Rollen)
        return parent::delete($user, $model);
    }

    /**
     * Darf der Nutzer Mitglieder zum OKR einladen?
     */
    public function invite(User $user, Okr $okr): bool
    {
        // Teamzugang + Schreibrolle (member/admin/owner) per StandardRole
        if (! $this->isInTeam($user, $okr)) {
            return false;
        }
        if ($this->hasRole($user, $okr, StandardRole::getWriteRoles())) {
            return true;
        }
        // Zusätzlich: Manager oder Model-Owner
        return $okr->manager_user_id === $user->id || $this->isOwner($user, $okr);
    }

    /**
     * Darf der Nutzer Mitglieder aus dem OKR entfernen?
     */
    public function removeMember(User $user, Okr $okr): bool
    {
        return $this->invite($user, $okr);
    }

    /**
     * Darf der Nutzer die Rolle eines OKR-Mitglieds ändern?
     */
    public function changeRole(User $user, Okr $okr): bool
    {
        // Admin-Rollen dürfen Rollen ändern
        if (! $this->isInTeam($user, $okr)) {
            return false;
        }
        if ($this->hasRole($user, $okr, StandardRole::getAdminRoles())) {
            return true;
        }
        // Zusätzlich: Model-Owner
        return $this->isOwner($user, $okr);
    }

    /**
     * Darf der Nutzer das Ownership des OKR übertragen?
     */
    public function transferOwnership(User $user, Okr $okr): bool
    {
        if (! $this->isInTeam($user, $okr)) {
            return false;
        }
        // Nur Admin-Rollen oder aktueller Owner/Manager
        if ($this->hasRole($user, $okr, StandardRole::getAdminRoles())) {
            return true;
        }
        return $this->isOwner($user, $okr) || $okr->manager_user_id === $user->id;
    }

    /**
     * Liefert die Nutzerrolle aus der Pivot-Relation `members`.
     */
    protected function getUserRole(User $user, $model): ?string
    {
        if (method_exists($model, 'members')) {
            $relation = $model->members()->where('user_id', $user->id)->first();
            return $relation?->pivot?->role ?? $relation?->role ?? null;
        }
        return null;
    }
}
