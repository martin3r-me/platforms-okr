<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Models\Objective;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class ListKeyResultsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.key_results.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/key-results?cycle_id={id}&objective_id={id}&filters=[...]&search=... - Listet Key Results. WICHTIG: cycle_id ist erforderlich (Kontext).';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'cycle_id' => [
                        'type' => 'integer',
                        'description' => 'Cycle-ID (required). Key Results werden über Objectives dem Cycle zugeordnet.',
                    ],
                    'objective_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Filter auf ein Objective innerhalb des Cycles.',
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

            $objectiveId = $this->normalizeId($arguments['objective_id'] ?? null);

            // Filter: objective muss zu cycle gehören
            $objectiveQuery = Objective::query()
                ->where('team_id', $teamId)
                ->where('cycle_id', $cycleId);
            if ($objectiveId) {
                $objectiveQuery->where('id', $objectiveId);
                if (!$objectiveQuery->exists()) {
                    return ToolResult::error('NOT_FOUND', "Objective {$objectiveId} nicht gefunden oder gehört nicht zu cycle_id {$cycleId}.");
                }
            }

            $query = KeyResult::query()
                ->where('team_id', $teamId)
                ->with(['performance', 'objective']);

            if ($objectiveId) {
                $query->where('objective_id', $objectiveId);
            } else {
                $query->whereHas('objective', fn($q) => $q->where('cycle_id', $cycleId));
            }

            $this->applyStandardFilters($query, $arguments, [
                'objective_id', 'title', 'order', 'performance_score', 'manager_user_id', 'user_id', 'created_at', 'updated_at',
            ]);
            $this->applyStandardSearch($query, $arguments, ['title', 'description']);
            $this->applyStandardSort($query, $arguments, ['order', 'performance_score', 'created_at', 'updated_at'], 'order', 'asc');
            $this->applyStandardPagination($query, $arguments);

            $krs = $query->get();
            $items = $krs->map(function (KeyResult $kr) {
                return [
                    'id' => $kr->id,
                    'uuid' => $kr->uuid,
                    'objective_id' => $kr->objective_id,
                    'objective_title' => $kr->objective?->title,
                    'team_id' => $kr->team_id,
                    'title' => $kr->title,
                    'description' => $kr->description,
                    'order' => $kr->order,
                    'performance_score' => $kr->performance_score,
                    'value_summary' => $this->buildKeyResultValueSummary($kr->performance),
                    'latest_performance' => $kr->performance ? [
                        'type' => $kr->performance->type,
                        'is_completed' => (bool)$kr->performance->is_completed,
                        'current_value' => $kr->performance->current_value,
                        'target_value' => $kr->performance->target_value,
                        'calculated_value' => $kr->performance->calculated_value,
                        'performance_score' => $kr->performance->performance_score,
                        'tendency' => $kr->performance->tendency,
                    ] : null,
                ];
            })->values()->toArray();

            return ToolResult::success([
                'cycle_id' => $cycleId,
                'objective_id' => $objectiveId,
                'key_results' => $items,
                'count' => count($items),
                'message' => count($items) . ' Key Result(s) gefunden.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Key Results: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['okr', 'key_results', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}


