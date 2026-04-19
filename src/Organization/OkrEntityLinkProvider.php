<?php

namespace Platform\Okr\Organization;

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
        return [];
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

        return $result;
    }

    public function metricDefinitions(): array
    {
        return [
            'okr_objectives_total'  => ['label' => 'Objectives (gesamt)', 'group' => 'okr', 'direction' => 'neutral', 'unit' => 'count'],
            'okr_objectives_done'   => ['label' => 'Objectives (erledigt)', 'group' => 'okr', 'direction' => 'up', 'unit' => 'count', 'pair' => 'okr_objectives_total'],
            'okr_key_results_total' => ['label' => 'Key Results (gesamt)', 'group' => 'okr', 'direction' => 'neutral', 'unit' => 'count'],
            'okr_key_results_done'  => ['label' => 'Key Results (erledigt)', 'group' => 'okr', 'direction' => 'up', 'unit' => 'count', 'pair' => 'okr_key_results_total'],
            'okr_performance_sum'   => ['label' => 'Performance (Summe)', 'group' => 'okr', 'direction' => 'up', 'unit' => 'score'],
            'okr_performance_count' => ['label' => 'Performance (Anzahl)', 'group' => 'okr', 'direction' => 'neutral', 'unit' => 'count'],
        ];
    }
}
