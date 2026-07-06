<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Models\KeyResultMeasure;
use Platform\Okr\Services\KeyResultEvaluationService;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class DeleteKeyResultMeasureTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.kr_measures.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /okr/kr-measures/{id} - Löst ein Measure von einem Key Result (soft-delete) und bewertet das KR neu.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Measure-ID (required).'],
            ],
            'required' => ['id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (! $context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $id = $this->normalizeId($arguments['id'] ?? null);
            if (! $id) {
                return ToolResult::error('VALIDATION_ERROR', 'id ist erforderlich.');
            }

            $teamId = $this->resolveOkrTeamId($context);
            if (! $teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden.');
            }

            $measure = KeyResultMeasure::query()->where('team_id', $teamId)->find($id);
            if (! $measure) {
                return ToolResult::error('NOT_FOUND', "Measure {$id} nicht gefunden (Team-ID: {$teamId}).");
            }

            $krId = $measure->key_result_id;
            $measure->delete();

            // KR neu bewerten (ohne das gelöschte Measure)
            $eval = ['progress' => null, 'completed' => false];
            $kr = KeyResult::find($krId);
            if ($kr) {
                $eval = resolve(KeyResultEvaluationService::class)->evaluate($kr);
            }

            return ToolResult::success([
                'id' => $id,
                'key_result_id' => $krId,
                'kr_progress' => $eval['progress'],
                'kr_completed' => $eval['completed'],
                'message' => 'Measure gelöst und Key Result neu bewertet.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Measures: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'key_results', 'measures', 'delete'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
