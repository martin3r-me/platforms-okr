<?php

namespace Platform\Okr\People;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\Relation;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Models\KeyResultContext;
use Platform\Okr\Models\Objective;
use Platform\People\Support\ImpactSourceBase;

/**
 * Wirkungs-Quelle OKR für people: Ausrichtung (woran zahlt ein Kontext ein?) und
 * eigene Ziele (Objectives) der Mitarbeiter. Lose gekoppelt — wird nur registriert,
 * wenn people geladen ist.
 *
 * Kein Team-Filter: OKR ist root-scoped (KRs liegen im Root-Team), Bezüge und
 * Subjekte sind bereits konkret.
 */
class OkrImpactSource extends ImpactSourceBase
{
    public function key(): string
    {
        return 'okr';
    }

    public function alignment(int $teamId, array $contextRefs): array
    {
        // "alias:id" → Klassenname (so steht context_type in okr_key_result_contexts)
        $byClass = [];   // class => [id => [ref, …]]
        foreach (array_unique($contextRefs) as $ref) {
            if (! is_string($ref) || ! str_contains($ref, ':')) {
                continue;
            }
            [$alias, $id] = explode(':', $ref, 2);
            if (! ctype_digit($id)) {
                continue;
            }
            $class = Relation::getMorphedModel($alias) ?? (class_exists($alias) ? $alias : null);
            if (! $class) {
                continue;
            }
            $byClass[$class][(int) $id][] = $ref;
        }

        if (! $byClass) {
            return [];
        }

        // Alle Tiefen: Verknüpfen einer Aufgabe schreibt ihr Projekt als Vorfahr mit
        $rows = KeyResultContext::query()
            ->where(function ($q) use ($byClass) {
                foreach ($byClass as $class => $ids) {
                    $q->orWhere(function ($w) use ($class, $ids) {
                        $w->where('context_type', $class)->whereIn('context_id', array_keys($ids));
                    });
                }
            })
            ->get(['key_result_id', 'context_type', 'context_id']);

        if ($rows->isEmpty()) {
            return [];
        }

        $objectiveOf = KeyResult::query()
            ->whereIn('id', $rows->pluck('key_result_id')->unique()->all())
            ->pluck('objective_id', 'id');

        $out = [];
        foreach ($rows as $row) {
            $krId = (int) $row->key_result_id;
            if (! $objectiveOf->has($krId)) {
                continue; // KR gelöscht
            }
            $targets = ['okr_key_result:'.$krId];
            if ($objectiveOf[$krId]) {
                // Objective mit — Meilenstein-Beiträge hängen oft am Objective
                $targets[] = 'okr_objective:'.(int) $objectiveOf[$krId];
            }
            foreach ($byClass[$row->context_type][(int) $row->context_id] ?? [] as $ref) {
                foreach ($targets as $target) {
                    $out[$ref][$target] = ['ref' => $target, 'weight' => 1.0];
                }
            }
        }

        return array_map('array_values', $out);
    }

    public function goals(int $teamId, array $subjects, CarbonInterface $from, CarbonInterface $to): array
    {
        // user_id → employee_id (erstes Subjekt gewinnt)
        $employeeOf = [];
        foreach ($subjects as $s) {
            $uid = $s['user_id'] ?? null;
            if ($uid && ! isset($employeeOf[(int) $uid])) {
                $employeeOf[(int) $uid] = (int) $s['employee_id'];
            }
        }
        if (! $employeeOf) {
            return [];
        }
        $userIds = array_keys($employeeOf);

        $objectives = Objective::query()
            ->where(function ($q) use ($userIds) {
                $q->whereIn('user_id', $userIds)->orWhereIn('manager_user_id', $userIds);
            })
            // Zyklus-Zeitraum (Template) überschneidet [from, to]
            ->whereHas('cycle.template', function ($t) use ($from, $to) {
                $t->whereDate('starts_at', '<=', $to->toDateString())
                    ->whereDate('ends_at', '>=', $from->toDateString());
            })
            ->whereNotNull('performance_score')
            ->with('cycle.template')
            ->get();

        $now = now();
        $out = [];
        foreach ($objectives as $o) {
            // Owner vor Manager
            $employeeId = $employeeOf[(int) $o->user_id] ?? $employeeOf[(int) $o->manager_user_id] ?? null;
            if ($employeeId === null) {
                continue;
            }
            // Bewusst kein OkrRollupService::evaluateObjective — der schreibt Cache + Snapshot.
            $endsAt = $o->cycle?->ends_at;

            $out[] = [
                'employee_id' => $employeeId,
                'ref' => 'okr_objective:'.$o->id,
                'title' => $o->title,
                'attainment' => max(0.0, min(1.0, (float) $o->performance_score)),
                'ends_at' => $endsAt ? $endsAt->copy()->endOfDay() : null,
                'closed' => $endsAt ? $endsAt->copy()->endOfDay()->lt($now) : false,
            ];
        }

        return $out;
    }
}
