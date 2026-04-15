<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\Objective;
use Platform\Okr\Models\StrategicDocument;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class GetObjectiveTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.objective.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/objectives/{id} - Ruft ein Objective ab (inkl. Erfolgskriterien).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Objective-ID (required).'],
                'include_key_results' => ['type' => 'boolean', 'description' => 'Optional: Erfolgskriterien laden. Default: true.'],
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

            $includeKrs = (bool)($arguments['include_key_results'] ?? true);
            $q = Objective::query()->where('team_id', $teamId);
            if ($includeKrs) {
                $q->with(['keyResults.performance', 'performance', 'vision', 'milestones.focusArea']);
            } else {
                $q->with(['vision', 'milestones.focusArea']);
            }
            $obj = $q->find($id);
            if (!$obj) {
                return ToolResult::error('NOT_FOUND', "Objective {$id} nicht gefunden (Team-ID: {$teamId}).");
            }

            $krs = null;
            if ($includeKrs) {
                $krs = $obj->keyResults->map(function ($kr) {
                    return [
                        'id' => $kr->id,
                        'uuid' => $kr->uuid,
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
                            'performance_score' => $kr->performance->performance_score,
                        ] : null,
                    ];
                })->values()->toArray();
            }

            return ToolResult::success([
                'objective' => [
                    'id' => $obj->id,
                    'uuid' => $obj->uuid,
                    'okr_id' => $obj->okr_id,
                    'cycle_id' => $obj->cycle_id,
                    'team_id' => $obj->team_id,
                    'title' => $obj->title,
                    'description' => $obj->description,
                    'is_mountain' => (bool)$obj->is_mountain,
                    'performance_score' => $obj->performance_score,
                    'order' => $obj->order,
                    'vision' => $obj->vision ? [
                        'id' => $obj->vision->id,
                        'type' => $obj->vision->type,
                        'title' => $obj->vision->title,
                        'version' => $obj->vision->version,
                        'is_active' => (bool)$obj->vision->is_active,
                        'valid_from' => $this->dateToYmd($obj->vision->valid_from),
                    ] : null,
                    'performance' => $obj->performance ? [
                        'performance_date' => $this->dateToYmd($obj->performance->performance_date),
                        'performance_score' => $obj->performance->performance_score,
                        'completion_percentage' => $obj->performance->completion_percentage,
                    ] : null,
                    'milestones' => $obj->milestones->map(fn($m) => [
                        'id' => $m->id,
                        'title' => $m->title,
                        'focus_area' => $m->focusArea ? $m->focusArea->title : null,
                        'target_year' => $m->target_year,
                        'target_quarter' => $m->target_quarter,
                    ])->values()->toArray(),
                ],
                'key_results' => $krs,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Objectives: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['okr', 'objective', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}


