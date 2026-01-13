<?php

namespace Platform\Okr\Tools\Concerns;

use Platform\Core\Contracts\ToolContext;
use Platform\Core\Models\Module;

trait ResolvesOkrScope
{
    /**
     * OKR ist root-scoped (scope_type=parent). Daher müssen Tools IMMER im Root-Team laufen.
     * Gibt IMMER das Root-Team zurück, unabhängig davon, in welchem Kind-Team der User ist.
     */
    protected function resolveOkrTeamId(ToolContext $context): ?int
    {
        $user = $context->user;
        if (!$user) {
            return null;
        }

        // currentTeamRelation ist das Basis-Team (kann Kind-Team sein)
        $baseTeam = $user->currentTeamRelation ?? null;
        if (!$baseTeam) {
            return null;
        }

        // OKR ist root-scoped: IMMER Root-Team zurückgeben
        $module = Module::where('key', 'okr')->first();
        if ($module && method_exists($module, 'isRootScoped') && $module->isRootScoped()) {
            if (method_exists($baseTeam, 'getRootTeam')) {
                $rootTeam = $baseTeam->getRootTeam();
                return $rootTeam->id ?? $baseTeam->id;
            }
        }

        // Fallback: sollte nicht passieren, da OKR root-scoped ist
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

    protected function dateToYmd($v): ?string
    {
        if ($v === null) return null;
        if ($v instanceof \DateTimeInterface) {
            return $v->format('Y-m-d');
        }
        if (is_string($v) && $v !== '') {
            // assume already Y-m-d or ISO date; keep as-is to avoid exceptions in tools
            return $v;
        }
        return null;
    }

    /**
     * Normalisiert den KR-Typ auf die Begriffe, die Nutzer typischerweise verwenden:
     * - boolean
     * - absolute
     * - relative (== percentage)
     *
     * Intern wird der Typ aktuell in okr_key_result_performances.type gespeichert
     * (boolean|absolute|percentage|calculated).
     */
    protected function normalizeKeyResultValueType(?string $performanceType): ?string
    {
        if (!$performanceType) return null;
        return match ($performanceType) {
            'boolean' => 'boolean',
            'absolute' => 'absolute',
            'percentage' => 'relative',
            // calculated ist ein interner Typ (z.B. Counter Sync), den wir nach außen als absolute behandeln
            'calculated' => 'absolute',
            default => null,
        };
    }

    protected function buildKeyResultValueSummary($performance): ?array
    {
        if (!$performance) return null;

        $rawType = $performance->type ?? null;
        $valueType = $this->normalizeKeyResultValueType(is_string($rawType) ? $rawType : null);
        $isCompleted = (bool)($performance->is_completed ?? false);

        $current = $performance->current_value;
        $target = $performance->target_value;
        $calc = $performance->calculated_value ?? null;

        $progressPercent = null;
        if ($valueType === 'boolean') {
            $progressPercent = $isCompleted ? 100.0 : 0.0;
        } else {
            $t = is_numeric($target) ? (float)$target : 0.0;
            $c = is_numeric($current) ? (float)$current : 0.0;
            if ($t > 0) {
                $progressPercent = max(0.0, min(100.0, round(($c / $t) * 100.0, 2)));
            }
        }

        $value = null;
        if ($valueType === 'boolean') {
            $value = [
                'completed' => $isCompleted,
            ];
        } else {
            $value = [
                'current' => is_numeric($current) ? (float)$current : null,
                'target' => is_numeric($target) ? (float)$target : null,
                'calculated' => is_numeric($calc) ? (float)$calc : (is_null($calc) ? null : $calc),
            ];
        }

        return [
            'value_type' => $valueType,
            'raw_type' => $rawType,
            'is_completed' => $isCompleted,
            'progress_percent' => $progressPercent,
            'value' => $value,
        ];
    }

    protected function mapValueTypeToPerformanceType(?string $valueType): ?string
    {
        if (!$valueType) return null;
        return match ($valueType) {
            'boolean' => 'boolean',
            'absolute' => 'absolute',
            'relative' => 'percentage',
            default => null,
        };
    }
}


