<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Models\Objective;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class ListObjectivesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.objectives.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/objectives?cycle_id={id}&filters=[...]&search=... - Listet Objectives eines Cycles. WICHTIG: cycle_id ist erforderlich.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'cycle_id' => [
                        'type' => 'integer',
                        'description' => 'Cycle-ID (required). Objectives sind immer cycle-bezogen.',
                    ],
                    'include_key_results' => [
                        'type' => 'boolean',
                        'description' => 'Optional: Key Results mitladen (Default false).',
                    ],
                ],
                'required' => ['cycle_id'],
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $cycleId = $this->normalizeId($arguments['cycle_id'] ?? null);
            if (!$cycleId) {
                return ToolResult::error('VALIDATION_ERROR', 'cycle_id ist erforderlich.');
            }

            $teamId = $this->resolveOkrTeamId($context);
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden (OKR ist root-scoped).');
            }

            $cycle = Cycle::query()->where('team_id', $teamId)->find($cycleId);
            if (!$cycle) {
                return ToolResult::error('NOT_FOUND', "Cycle {$cycleId} nicht gefunden (Team-ID: {$teamId}).");
            }

            $includeKrs = (bool)($arguments['include_key_results'] ?? false);

            $query = Objective::query()
                ->where('cycle_id', $cycleId)
                ->where('team_id', $teamId);

            if ($includeKrs) {
                $query->with(['keyResults.performance']);
            }

            $this->applyStandardFilters($query, $arguments, [
                'okr_id', 'cycle_id', 'title', 'is_mountain', 'performance_score', 'order', 'manager_user_id', 'user_id', 'created_at', 'updated_at',
            ]);
            $this->applyStandardSearch($query, $arguments, ['title', 'description']);
            $this->applyStandardSort($query, $arguments, ['order', 'performance_score', 'created_at', 'updated_at'], 'order', 'asc');
            $this->applyStandardPagination($query, $arguments);

            $objectives = $query->get();
            $items = $objectives->map(function (Objective $o) use ($includeKrs) {
                return [
                    'id' => $o->id,
                    'uuid' => $o->uuid,
                    'okr_id' => $o->okr_id,
                    'cycle_id' => $o->cycle_id,
                    'team_id' => $o->team_id,
                    'title' => $o->title,
                    'description' => $o->description,
                    'is_mountain' => (bool)$o->is_mountain,
                    'performance_score' => $o->performance_score,
                    'order' => $o->order,
                    'key_results' => $includeKrs ? $o->keyResults->map(function ($kr) {
                        return [
                            'id' => $kr->id,
                            'uuid' => $kr->uuid,
                            'title' => $kr->title,
                            'order' => $kr->order,
                            'performance_score' => $kr->performance_score,
                            'value_summary' => $this->buildKeyResultValueSummary($kr->performance),
                            'latest_performance' => $kr->performance ? [
                                'type' => $kr->performance->type,
                                'is_completed' => (bool)$kr->performance->is_completed,
                                'current_value' => $kr->performance->current_value,
                                'target_value' => $kr->performance->target_value,
                                'performance_score' => $kr->performance->performance_score,
                            ] : null,
                        ];
                    })->values()->toArray() : null,
                ];
            })->values()->toArray();

            return ToolResult::success([
                'cycle_id' => $cycleId,
                'objectives' => $items,
                'count' => count($items),
                'message' => count($items) . ' Objective(s) gefunden (cycle_id: ' . $cycleId . ').',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Objectives: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['okr', 'objectives', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}


