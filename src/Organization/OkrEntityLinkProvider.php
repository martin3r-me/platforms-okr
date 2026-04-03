<?php

namespace Platform\Okr\Organization;

use Illuminate\Database\Eloquent\Builder;
use Platform\Organization\Contracts\EntityLinkProvider;

class OkrEntityLinkProvider implements EntityLinkProvider
{
    public function morphAliases(): array
    {
        return ['okr'];
    }

    public function linkTypeConfig(): array
    {
        return [
            'okr' => ['label' => 'OKR', 'icon' => 'chart-bar', 'route' => null],
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
}
