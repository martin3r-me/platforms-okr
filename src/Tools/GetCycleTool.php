<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class GetCycleTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.cycle.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/cycles/{id} - Ruft einen Cycle ab (inkl. Template + Objectives + Key Results optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Cycle-ID (required).'],
                'include_tree' => ['type' => 'boolean', 'description' => 'Optional: Objectives + Key Results laden. Default: true.'],
            ],
            'required' => ['id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $id = $this->normalizeId($arguments['id'] ?? null);
            if (!$id) {
                return ToolResult::error('VALIDATION_ERROR', 'id ist erforderlich.');
            }

            $teamId = $this->resolveOkrTeamId($context);
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden (OKR ist root-scoped).');
            }

            $includeTree = (bool)($arguments['include_tree'] ?? true);

            $q = Cycle::query()
                ->where('team_id', $teamId)
                ->with(['template', 'okr']);

            if ($includeTree) {
                $q->with(['objectives.keyResults', 'objectives.performance', 'performance']);
            }

            $cycle = $q->find($id);
            if (!$cycle) {
                return ToolResult::error('NOT_FOUND', "Cycle {$id} nicht gefunden (Team-ID: {$teamId}).");
            }

            $tree = null;
            if ($includeTree) {
                $tree = $cycle->objectives->map(function ($o) {
                    return [
                        'id' => $o->id,
                        'uuid' => $o->uuid,
                        'title' => $o->title,
                        'description' => $o->description,
                        'is_mountain' => (bool)$o->is_mountain,
                        'order' => $o->order,
                        'performance_score' => $o->performance_score,
                        'performance' => $o->performance ? [
                            'performance_date' => $o->performance->performance_date?->toDateString(),
                            'performance_score' => $o->performance->performance_score,
                            'completion_percentage' => $o->performance->completion_percentage,
                        ] : null,
                        'key_results' => $o->keyResults->map(function ($kr) {
                            return [
                                'id' => $kr->id,
                                'uuid' => $kr->uuid,
                                'title' => $kr->title,
                                'description' => $kr->description,
                                'order' => $kr->order,
                                'performance_score' => $kr->performance_score,
                                'latest_performance' => $kr->performance ? [
                                    'type' => $kr->performance->type,
                                    'is_completed' => (bool)$kr->performance->is_completed,
                                    'current_value' => $kr->performance->current_value,
                                    'target_value' => $kr->performance->target_value,
                                    'performance_score' => $kr->performance->performance_score,
                                ] : null,
                            ];
                        })->values()->toArray(),
                    ];
                })->values()->toArray();
            }

            return ToolResult::success([
                'cycle' => [
                    'id' => $cycle->id,
                    'uuid' => $cycle->uuid,
                    'okr_id' => $cycle->okr_id,
                    'team_id' => $cycle->team_id,
                    'type' => $cycle->type,
                    'status' => $cycle->status,
                    'notes' => $cycle->notes,
                    'description' => $cycle->description,
                    'cycle_template_id' => $cycle->cycle_template_id,
                    'template' => $cycle->template ? [
                        'id' => $cycle->template->id,
                        'label' => $cycle->template->label,
                        'type' => $cycle->template->type,
                        'starts_at' => $cycle->template->starts_at?->toDateString(),
                        'ends_at' => $cycle->template->ends_at?->toDateString(),
                        'is_current' => (bool)$cycle->template->is_current,
                    ] : null,
                    'performance' => $cycle->performance ? [
                        'performance_date' => $cycle->performance->performance_date?->toDateString(),
                        'performance_score' => $cycle->performance->performance_score,
                        'completion_percentage' => $cycle->performance->completion_percentage,
                    ] : null,
                ],
                'objectives' => $tree,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Cycles: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['okr', 'cycle', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}


