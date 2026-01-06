<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class GetKeyResultTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.key_result.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/key-results/{id} - Ruft ein Key Result ab (inkl. Objective + Performance).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'KeyResult-ID (required).'],
                'cycle_id' => ['type' => 'integer', 'description' => 'Optional: Kontext-Validierung (Key Result muss zu diesem Cycle gehören).'],
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

            $kr = KeyResult::query()
                ->where('team_id', $teamId)
                ->with(['objective', 'performance'])
                ->find($id);
            if (!$kr) {
                return ToolResult::error('NOT_FOUND', "Key Result {$id} nicht gefunden (Team-ID: {$teamId}).");
            }

            $cycleId = $this->normalizeId($arguments['cycle_id'] ?? null);
            if ($cycleId && (int)($kr->objective?->cycle_id) !== (int)$cycleId) {
                return ToolResult::error('CONTEXT_MISMATCH', "Key Result {$id} gehört nicht zu cycle_id {$cycleId}.");
            }

            return ToolResult::success([
                'key_result' => [
                    'id' => $kr->id,
                    'uuid' => $kr->uuid,
                    'objective_id' => $kr->objective_id,
                    'objective' => $kr->objective ? [
                        'id' => $kr->objective->id,
                        'title' => $kr->objective->title,
                        'cycle_id' => $kr->objective->cycle_id,
                        'okr_id' => $kr->objective->okr_id,
                    ] : null,
                    'team_id' => $kr->team_id,
                    'title' => $kr->title,
                    'description' => $kr->description,
                    'order' => $kr->order,
                    'performance_score' => $kr->performance_score,
                    // Normalisierte Sicht: value_type = boolean|absolute|relative (relative == percentage)
                    'value_summary' => $this->buildKeyResultValueSummary($kr->performance),
                    // Raw: bleibt kompatibel zu bestehenden Clients
                    'latest_performance' => $kr->performance ? [
                        'type' => $kr->performance->type,
                        'is_completed' => (bool)$kr->performance->is_completed,
                        'current_value' => $kr->performance->current_value,
                        'target_value' => $kr->performance->target_value,
                        'calculated_value' => $kr->performance->calculated_value,
                        'performance_score' => $kr->performance->performance_score,
                        'tendency' => $kr->performance->tendency,
                    ] : null,
                ],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Key Results: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['okr', 'key_result', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}


