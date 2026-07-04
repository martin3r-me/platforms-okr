<?php

namespace Platform\Okr\Organization;

use Illuminate\Database\Eloquent\Builder;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Models\Objective;
use Platform\Organization\Contracts\MilestoneContributor;

/**
 * OKR contribution provider for the Transformations Map.
 *
 * Registers Objectives and KeyResults as morph aliases that can be linked
 * to a Milestone via organization_milestone_contributions. The
 * Transformations Map uses this to render OKR items on milestones and
 * to summarize contributor status per milestone.
 */
class OkrMilestoneContributor implements MilestoneContributor
{
    private const DONE_THRESHOLD = 0.7;

    public function morphAliases(): array
    {
        return ['okr_objective', 'okr_key_result'];
    }

    public function linkTypeConfig(): array
    {
        return [
            'okr_objective' => [
                'label'    => 'OKR-Objectives',
                'singular' => 'Objective',
                'icon'     => 'target',
                'route'    => null,
            ],
            'okr_key_result' => [
                'label'    => 'Key Results',
                'singular' => 'Key Result',
                'icon'     => 'chart-bar',
                'route'    => null,
            ],
        ];
    }

    public function applyEagerLoading(Builder $query, string $morphAlias, string $fqcn): void
    {
        if ($morphAlias === 'okr_objective') {
            $query->with(['cycle:id,title']);
        }
        if ($morphAlias === 'okr_key_result') {
            $query->with(['objective:id,title']);
        }
    }

    public function extractMetadata(string $morphAlias, mixed $model): array
    {
        $score = $model->performance_score !== null ? (float) $model->performance_score : null;

        $base = [
            'title'             => $model->title ?? null,
            'performance_score' => $score !== null ? round($score, 3) : null,
            'is_done'           => $score !== null && $score >= self::DONE_THRESHOLD,
        ];

        if ($morphAlias === 'okr_objective') {
            $base['cycle_title'] = $model->cycle?->title;
        }
        if ($morphAlias === 'okr_key_result') {
            $base['objective_title'] = $model->objective?->title;
        }

        return $base;
    }

    public function metrics(string $morphAlias, array $linksByMilestone): array
    {
        // Collect unique IDs across all milestones
        $allIds = [];
        foreach ($linksByMilestone as $ids) {
            foreach ($ids as $id) {
                $allIds[$id] = true;
            }
        }
        if (empty($allIds)) {
            return [];
        }

        $modelClass = match ($morphAlias) {
            'okr_objective'  => Objective::class,
            'okr_key_result' => KeyResult::class,
            default          => null,
        };
        if ($modelClass === null) {
            return [];
        }

        // Batch load: only the columns we need for metrics
        $models = $modelClass::whereIn('id', array_keys($allIds))
            ->select(['id', 'performance_score'])
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($linksByMilestone as $milestoneId => $ids) {
            $total = 0;
            $done = 0;
            $scoreSum = 0.0;
            $scoreCount = 0;

            foreach ($ids as $id) {
                $model = $models[$id] ?? null;
                if (!$model) {
                    continue; // deleted or filtered
                }
                $total++;

                $score = $model->performance_score;
                if ($score !== null) {
                    $score = (float) $score;
                    $scoreSum += $score;
                    $scoreCount++;
                    if ($score >= self::DONE_THRESHOLD) {
                        $done++;
                    }
                }
            }

            $result[$milestoneId] = [
                'contributors_total'   => $total,
                'contributors_done'    => $done,
                'contributors_perf_sum'   => $scoreCount > 0 ? round($scoreSum, 3) : 0,
                'contributors_perf_count' => $scoreCount,
            ];
        }

        return $result;
    }
}
