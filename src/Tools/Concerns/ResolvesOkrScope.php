<?php

namespace Platform\Okr\Tools\Concerns;

use Platform\Core\Contracts\ToolContext;
use Platform\Core\Models\Module;

trait ResolvesOkrScope
{
    /**
     * OKR ist root-scoped (scope_type=parent). Daher müssen Tools i.d.R. im Root-Team laufen.
     */
    protected function resolveOkrTeamId(ToolContext $context): ?int
    {
        $user = $context->user;
        if (!$user) {
            return null;
        }

        // base team (kann child team sein)
        $baseTeam = $user->currentTeamRelation ?? $user->currentTeam ?? null;
        if (!$baseTeam) {
            return null;
        }

        $module = Module::where('key', 'okr')->first();
        if ($module && method_exists($module, 'isRootScoped') && $module->isRootScoped()) {
            if (method_exists($baseTeam, 'getRootTeam')) {
                return $baseTeam->getRootTeam()->id ?? $baseTeam->id;
            }
        }

        return $baseTeam->id ?? null;
    }

    protected function normalizeId($v): ?int
    {
        if ($v === 0 || $v === '0' || $v === '' || $v === null) {
            return null;
        }
        if (is_int($v)) return $v;
        if (is_string($v) && ctype_digit($v)) return (int)$v;
        return null;
    }
}


