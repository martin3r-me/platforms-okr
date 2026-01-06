<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\CyclePerformance;
use Platform\Okr\Models\KeyResultPerformance;
use Platform\Okr\Models\ObjectivePerformance;
use Platform\Okr\Models\OkrPerformance;
use Platform\Okr\Models\TeamPerformance;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class ListPerformancesTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.performances.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/performances - READ-ONLY: Listet Performance-Snapshots. scope=team|okr|cycle|objective|key_result.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'scope' => [
                    'type' => 'string',
                    'enum' => ['team', 'okr', 'cycle', 'objective', 'key_result'],
                    'description' => 'Welche Performance du lesen willst.',
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional (root-scoped): Team-ID. Wenn nicht gesetzt, wird sie aus dem Kontext abgeleitet.',
                ],
                'okr_id' => ['type' => 'integer'],
                'cycle_id' => ['type' => 'integer'],
                'objective_id' => ['type' => 'integer'],
                'key_result_id' => ['type' => 'integer'],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Optional: Anzahl Einträge (Default 30, max 200).',
                ],
            ],
            'required' => ['scope'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $scope = $arguments['scope'] ?? null;
            if (!is_string($scope) || $scope === '') {
                return ToolResult::error('VALIDATION_ERROR', 'scope ist erforderlich.');
            }

            $teamId = $this->normalizeId($arguments['team_id'] ?? null) ?? $this->resolveOkrTeamId($context);
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden (OKR ist root-scoped).');
            }

            $limit = (int)($arguments['limit'] ?? 30);
            if ($limit <= 0) $limit = 30;
            $limit = min($limit, 200);

            $items = [];
            $meta = ['scope' => $scope, 'team_id' => $teamId, 'limit' => $limit];

            switch ($scope) {
                case 'team': {
                    $rows = TeamPerformance::query()
                        ->where('team_id', $teamId)
                        ->orderBy('performance_date', 'desc')
                        ->limit($limit)
                        ->get();
                    $items = $rows->map(fn(TeamPerformance $p) => [
                        'performance_date' => $p->performance_date?->toDateString(),
                        'average_score' => $p->average_score,
                        'active_cycles' => $p->active_cycles,
                        'current_cycles' => $p->current_cycles,
                        'total_okrs' => $p->total_okrs,
                        'active_okrs' => $p->active_okrs,
                        'successful_okrs' => $p->successful_okrs,
                        'total_objectives' => $p->total_objectives,
                        'achieved_objectives' => $p->achieved_objectives,
                        'total_key_results' => $p->total_key_results,
                        'achieved_key_results' => $p->achieved_key_results,
                        'open_key_results' => $p->open_key_results,
                        'score_trend' => $p->score_trend,
                        'okr_trend' => $p->okr_trend,
                        'achievement_trend' => $p->achievement_trend,
                    ])->toArray();
                    break;
                }
                case 'okr': {
                    $okrId = $this->normalizeId($arguments['okr_id'] ?? null);
                    if (!$okrId) return ToolResult::error('VALIDATION_ERROR', 'okr_id ist erforderlich für scope=okr.');
                    $rows = OkrPerformance::query()
                        ->where('team_id', $teamId)
                        ->where('okr_id', $okrId)
                        ->orderBy('performance_date', 'desc')
                        ->limit($limit)
                        ->get();
                    $items = $rows->map(fn(OkrPerformance $p) => [
                        'performance_date' => $p->performance_date?->toDateString(),
                        'performance_score' => $p->performance_score,
                        'completion_percentage' => $p->completion_percentage,
                        'completed_cycles' => $p->completed_cycles,
                        'total_cycles' => $p->total_cycles,
                        'completed_objectives' => $p->completed_objectives,
                        'total_objectives' => $p->total_objectives,
                        'completed_key_results' => $p->completed_key_results,
                        'total_key_results' => $p->total_key_results,
                    ])->toArray();
                    $meta['okr_id'] = $okrId;
                    break;
                }
                case 'cycle': {
                    $cycleId = $this->normalizeId($arguments['cycle_id'] ?? null);
                    if (!$cycleId) return ToolResult::error('VALIDATION_ERROR', 'cycle_id ist erforderlich für scope=cycle.');
                    $rows = CyclePerformance::query()
                        ->where('team_id', $teamId)
                        ->where('cycle_id', $cycleId)
                        ->orderBy('performance_date', 'desc')
                        ->limit($limit)
                        ->get();
                    $items = $rows->map(fn(CyclePerformance $p) => [
                        'performance_date' => $p->performance_date?->toDateString(),
                        'performance_score' => $p->performance_score,
                        'completion_percentage' => $p->completion_percentage,
                        'completed_objectives' => $p->completed_objectives,
                        'total_objectives' => $p->total_objectives,
                        'completed_key_results' => $p->completed_key_results,
                        'total_key_results' => $p->total_key_results,
                    ])->toArray();
                    $meta['cycle_id'] = $cycleId;
                    break;
                }
                case 'objective': {
                    $objectiveId = $this->normalizeId($arguments['objective_id'] ?? null);
                    if (!$objectiveId) return ToolResult::error('VALIDATION_ERROR', 'objective_id ist erforderlich für scope=objective.');
                    $rows = ObjectivePerformance::query()
                        ->where('team_id', $teamId)
                        ->where('objective_id', $objectiveId)
                        ->orderBy('performance_date', 'desc')
                        ->limit($limit)
                        ->get();
                    $items = $rows->map(fn(ObjectivePerformance $p) => [
                        'performance_date' => $p->performance_date?->toDateString(),
                        'performance_score' => $p->performance_score,
                        'completion_percentage' => $p->completion_percentage,
                        'completed_key_results' => $p->completed_key_results,
                        'total_key_results' => $p->total_key_results,
                        'average_progress' => $p->average_progress,
                    ])->toArray();
                    $meta['objective_id'] = $objectiveId;
                    break;
                }
                case 'key_result': {
                    $krId = $this->normalizeId($arguments['key_result_id'] ?? null);
                    if (!$krId) return ToolResult::error('VALIDATION_ERROR', 'key_result_id ist erforderlich für scope=key_result.');
                    $rows = KeyResultPerformance::query()
                        ->where('team_id', $teamId)
                        ->where('key_result_id', $krId)
                        ->latest()
                        ->limit($limit)
                        ->get();
                    $items = $rows->map(fn(KeyResultPerformance $p) => [
                        'id' => $p->id,
                        'uuid' => $p->uuid,
                        'type' => $p->type,
                        'is_completed' => (bool)$p->is_completed,
                        'current_value' => $p->current_value,
                        'target_value' => $p->target_value,
                        'calculated_value' => $p->calculated_value,
                        'performance_score' => $p->performance_score,
                        'tendency' => $p->tendency,
                        'created_at' => $p->created_at?->toIso8601String(),
                    ])->toArray();
                    $meta['key_result_id'] = $krId;
                    break;
                }
                default:
                    return ToolResult::error('VALIDATION_ERROR', 'Unbekannter scope. Erlaubt: team|okr|cycle|objective|key_result.');
            }

            return ToolResult::success([
                'meta' => $meta,
                'items' => $items,
                'count' => count($items),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Performance-Daten: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['okr', 'performance', 'read_only'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}


