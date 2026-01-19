<?php

namespace Platform\Okr\Policies;

use Platform\Core\Models\User;
use Platform\Okr\Models\Forecast;

class ForecastPolicy
{
    public function view(User $user, Forecast $forecast): bool
    {
        // User muss im selben Team sein (OKR ist root-scoped)
        $baseTeam = $user->currentTeamRelation;
        if (!$baseTeam) {
            return false;
        }

        $okrModule = \Platform\Core\Models\Module::where('key', 'okr')->first();
        $teamId = ($okrModule && method_exists($okrModule, 'isRootScoped') && $okrModule->isRootScoped()) 
            ? ($baseTeam->getRootTeam()->id ?? $baseTeam->id)
            : $baseTeam->id;

        return $forecast->team_id === $teamId;
    }

    public function update(User $user, Forecast $forecast): bool
    {
        // Gleiche Logik wie view
        return $this->view($user, $forecast);
    }

    public function delete(User $user, Forecast $forecast): bool
    {
        // Gleiche Logik wie view
        return $this->view($user, $forecast);
    }
}
