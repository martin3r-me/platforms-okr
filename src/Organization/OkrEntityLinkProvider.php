<?php

namespace Platform\Okr\Organization;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Platform\Okr\Models\Okr;
use Platform\Organization\Contracts\EntityLinkProvider;
use Platform\Organization\Contracts\HasMetricDefinitions;

class OkrEntityLinkProvider implements EntityLinkProvider, HasMetricDefinitions
{
    public function morphAliases(): array
    {
        return ['okr'];
    }

    public function linkTypeConfig(): array
    {
        return [
            'okr' => ['label' => 'Zielsteuerung', 'singular' => 'Zielsteuerung', 'icon' => 'chart-bar', 'route' => null],
        ];
    }

    public function applyEagerLoading(Builder $query, string $morphAlias, string $fqcn): void
    {
        $query->withCount(['objectives', 'cycles']);
    }

    public function extractMetadata(string $morphAlias, mixed $model): array
    {
        return [
            'objective_count' => (int) ($model->objectives_count ?? 0),
            'cycle_count' => (int) ($model->cycles_count ?? 0),
            'performance_score' => $model->performance_score ? round((float) $model->performance_score * 100) : null,
        ];
    }

    public function metadataDisplayRules(): array
    {
        return [
            'okr' => [
                ['field' => 'objective_count', 'format' => 'count', 'suffix' => 'Objectives'],
                ['field' => 'cycle_count', 'format' => 'count', 'suffix' => 'Zyklen'],
                ['field' => 'performance_score', 'format' => 'percentage'],
            ],
        ];
    }

    public function timeTrackableCascades(): array
    {
        // Zeit wird auf dem OKR-Container selbst gebucht (Steuerungszeit).
        // Keine Child-Cascade: Umsetzungsarbeit an Objectives/KRs läuft als Tasks.
        return [
            'okr' => [Okr::class, []],
        ];
    }

    public function activityChildren(string $morphAlias, array $linkableIds): array
    {
        return [];
    }

    public function metrics(string $morphAlias, array $linksByEntity): array
    {
        if ($morphAlias !== 'okr') {
            return [];
        }

        // Collect all OKR IDs across entities
        $allIds = [];
        foreach ($linksByEntity as $ids) {
            $allIds = array_merge($allIds, $ids);
        }
        $allIds = array_values(array_unique($allIds));

        if (empty($allIds)) {
            return [];
        }

        // Load OKRs with objective + key result counts
        $okrs = Okr::whereIn('id', $allIds)
            ->withCount([
                'objectives',
                'objectives as objectives_done_count' => fn ($q) => $q->where('performance_score', '>=', 0.7),
                'keyResults',
                'keyResults as key_results_done_count' => fn ($q) => $q->where('okr_key_results.performance_score', '>=', 0.7),
            ])
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($linksByEntity as $entityId => $ids) {
            $objectivesTotal = 0;
            $objectivesDone = 0;
            $krTotal = 0;
            $krDone = 0;
            $scoreSum = 0;
            $scoreCount = 0;

            foreach ($ids as $id) {
                $okr = $okrs[$id] ?? null;
                if (!$okr) {
                    continue;
                }
                $objectivesTotal += $okr->objectives_count;
                $objectivesDone += $okr->objectives_done_count;
                $krTotal += $okr->key_results_count;
                $krDone += $okr->key_results_done_count;

                if ($okr->performance_score !== null) {
                    $scoreSum += (float) $okr->performance_score;
                    $scoreCount++;
                }
            }

            $result[$entityId] = [
                'okr_objectives_total' => $objectivesTotal,
                'okr_objectives_done' => $objectivesDone,
                'okr_key_results_total' => $krTotal,
                'okr_key_results_done' => $krDone,
                // Store sum + count separately so hierarchy cascade can average correctly
                'okr_performance_sum' => $scoreCount > 0 ? round($scoreSum, 3) : 0,
                'okr_performance_count' => $scoreCount,
            ];
        }

        // Milestone metrics: resolve objective + KR IDs per entity, then query pivot tables
        $allObjectiveIds = [];
        $allKrIds = [];

        // Batch load objective IDs per OKR
        $objectivesByOkr = DB::table('okr_objectives')
            ->whereIn('okr_id', $allIds)
            ->whereNull('deleted_at')
            ->select('id', 'okr_id')
            ->get()
            ->groupBy('okr_id');

        // Build objective IDs per entity and collect all objective IDs
        $objectiveIdsPerEntity = [];
        foreach ($linksByEntity as $entityId => $ids) {
            $objIds = [];
            foreach ($ids as $id) {
                if (isset($objectivesByOkr[$id])) {
                    foreach ($objectivesByOkr[$id] as $obj) {
                        $objIds[] = $obj->id;
                    }
                }
            }
            $objectiveIdsPerEntity[$entityId] = array_unique($objIds);
            $allObjectiveIds = array_merge($allObjectiveIds, $objIds);
        }
        $allObjectiveIds = array_values(array_unique($allObjectiveIds));

        // Batch load KR IDs per objective
        $krsByObjective = [];
        if (!empty($allObjectiveIds)) {
            $krsByObjective = DB::table('okr_key_results')
                ->whereIn('objective_id', $allObjectiveIds)
                ->whereNull('deleted_at')
                ->select('id', 'objective_id')
                ->get()
                ->groupBy('objective_id');
        }

        // Build KR IDs per entity
        $krIdsPerEntity = [];
        foreach ($objectiveIdsPerEntity as $entityId => $objIds) {
            $entityKrIds = [];
            foreach ($objIds as $objId) {
                if (isset($krsByObjective[$objId])) {
                    foreach ($krsByObjective[$objId] as $kr) {
                        $entityKrIds[] = $kr->id;
                    }
                }
            }
            $krIdsPerEntity[$entityId] = array_unique($entityKrIds);
            $allKrIds = array_merge($allKrIds, $entityKrIds);
        }
        $allKrIds = array_values(array_unique($allKrIds));

        // Load milestone IDs via objective pivot
        $milestonesByObjective = [];
        if (!empty($allObjectiveIds)) {
            $milestonesByObjective = DB::table('okr_objective_milestone')
                ->whereIn('objective_id', $allObjectiveIds)
                ->select('objective_id', 'milestone_id')
                ->get()
                ->groupBy('objective_id');
        }

        // Load milestone IDs via KR pivot
        $milestonesByKr = [];
        if (!empty($allKrIds)) {
            $milestonesByKr = DB::table('okr_key_result_milestone')
                ->whereIn('key_result_id', $allKrIds)
                ->select('key_result_id', 'milestone_id')
                ->get()
                ->groupBy('key_result_id');
        }

        // Collect unique milestone IDs per entity
        $milestoneIdsPerEntity = [];
        $allMilestoneIds = [];
        foreach ($linksByEntity as $entityId => $ids) {
            $mIds = [];
            foreach ($objectiveIdsPerEntity[$entityId] ?? [] as $objId) {
                if (isset($milestonesByObjective[$objId])) {
                    foreach ($milestonesByObjective[$objId] as $row) {
                        $mIds[] = $row->milestone_id;
                    }
                }
            }
            foreach ($krIdsPerEntity[$entityId] ?? [] as $krId) {
                if (isset($milestonesByKr[$krId])) {
                    foreach ($milestonesByKr[$krId] as $row) {
                        $mIds[] = $row->milestone_id;
                    }
                }
            }
            $mIds = array_values(array_unique($mIds));
            $milestoneIdsPerEntity[$entityId] = $mIds;
            $allMilestoneIds = array_merge($allMilestoneIds, $mIds);
        }
        $allMilestoneIds = array_values(array_unique($allMilestoneIds));

        // Load milestone target_dates
        $milestoneTargetDates = [];
        if (!empty($allMilestoneIds)) {
            $milestoneTargetDates = DB::table('okr_milestones')
                ->whereIn('id', $allMilestoneIds)
                ->whereNull('deleted_at')
                ->select('id', 'target_date')
                ->get()
                ->keyBy('id');
        }

        $now = Carbon::now();
        $in30days = $now->copy()->addDays(30);

        foreach ($milestoneIdsPerEntity as $entityId => $mIds) {
            $total = 0;
            $overdue = 0;
            $due30d = 0;

            foreach ($mIds as $mId) {
                $milestone = $milestoneTargetDates[$mId] ?? null;
                if (!$milestone) {
                    continue; // deleted
                }
                $total++;
                if ($milestone->target_date !== null) {
                    $targetDate = Carbon::parse($milestone->target_date);
                    if ($targetDate->lt($now)) {
                        $overdue++;
                    }
                    if ($targetDate->lte($in30days)) {
                        $due30d++;
                    }
                }
            }

            $result[$entityId] = array_merge($result[$entityId] ?? [], [
                'okr_milestones_total' => $total,
                'okr_milestones_overdue' => $overdue,
                'okr_milestones_due_30d' => $due30d,
            ]);
        }

        return $result;
    }

    public function metricDefinitions(): array
    {
        return [
            'okr_objectives_total'  => ['label' => 'Objectives (gesamt)', 'group' => 'okr', 'direction' => 'neutral', 'unit' => 'count', 'dimension' => 'complexity', 'type' => 'stock', 'aggregation_mode' => 'rolled_up'],
            'okr_objectives_done'   => ['label' => 'Objectives (erledigt)', 'group' => 'okr', 'direction' => 'up', 'unit' => 'count', 'pair' => 'okr_objectives_total', 'dimension' => 'throughput', 'type' => 'flow', 'aggregation_mode' => 'rolled_up'],
            'okr_key_results_total' => ['label' => 'Key Results (gesamt)', 'group' => 'okr', 'direction' => 'neutral', 'unit' => 'count', 'dimension' => 'complexity', 'type' => 'stock', 'aggregation_mode' => 'rolled_up'],
            'okr_key_results_done'  => ['label' => 'Key Results (erledigt)', 'group' => 'okr', 'direction' => 'up', 'unit' => 'count', 'pair' => 'okr_key_results_total', 'dimension' => 'throughput', 'type' => 'flow', 'aggregation_mode' => 'rolled_up'],
            'okr_performance_sum'   => ['label' => 'Performance (Summe)', 'group' => 'okr', 'direction' => 'up', 'unit' => 'score', 'dimension' => 'quality', 'type' => 'modulator', 'aggregation_mode' => 'rolled_up'],
            'okr_performance_count' => ['label' => 'Performance (Anzahl)', 'group' => 'okr', 'direction' => 'neutral', 'unit' => 'count', 'dimension' => 'quality', 'type' => 'stock', 'aggregation_mode' => 'rolled_up'],
            'okr_milestones_total'   => ['label' => 'Meilensteine (gesamt)', 'group' => 'okr', 'direction' => 'neutral', 'unit' => 'count', 'dimension' => 'complexity', 'type' => 'stock', 'aggregation_mode' => 'rolled_up'],
            'okr_milestones_overdue' => ['label' => 'Meilensteine (überfällig)', 'group' => 'okr', 'direction' => 'down', 'unit' => 'count', 'dimension' => 'quality', 'type' => 'modulator', 'aggregation_mode' => 'rolled_up'],
            'okr_milestones_due_30d' => ['label' => 'Meilensteine (nächste 30d)', 'group' => 'okr', 'direction' => 'neutral', 'unit' => 'count', 'dimension' => 'energy', 'type' => 'flow', 'aggregation_mode' => 'rolled_up', 'basis' => 'window_30d'],
        ];
    }
}
