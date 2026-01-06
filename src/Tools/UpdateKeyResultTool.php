<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class UpdateKeyResultTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.key_results.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /okr/key-results/{id} - Aktualisiert ein Key Result. Optional cycle_id zur Kontext-Validierung.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'KeyResult-ID (required).'],
                'cycle_id' => ['type' => 'integer', 'description' => 'Optional: Kontext-Validierung (KR muss zu diesem Cycle gehören).'],
                'objective_id' => ['type' => 'integer', 'description' => 'Optional: Validierungskontext (wird nicht geändert).'],
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'order' => ['type' => 'integer'],
                'manager_user_id' => ['type' => 'integer'],
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
                ->with('objective')
                ->find($id);
            if (!$kr) {
                return ToolResult::error('NOT_FOUND', "Key Result {$id} nicht gefunden (Team-ID: {$teamId}).");
            }

            $cycleId = $this->normalizeId($arguments['cycle_id'] ?? null);
            if ($cycleId && (int)($kr->objective?->cycle_id) !== (int)$cycleId) {
                return ToolResult::error('CONTEXT_MISMATCH', "Key Result {$id} gehört nicht zu cycle_id {$cycleId}.");
            }

            $objectiveId = $this->normalizeId($arguments['objective_id'] ?? null);
            if ($objectiveId && (int)$kr->objective_id !== (int)$objectiveId) {
                return ToolResult::error('CONTEXT_MISMATCH', "Key Result {$id} gehört nicht zu objective_id {$objectiveId}.");
            }

            $dirty = false;
            foreach (['title', 'description', 'order', 'manager_user_id'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $kr->{$field} = $arguments[$field];
                    $dirty = true;
                }
            }
            if ($dirty) {
                $kr->save();
            }

            return ToolResult::success([
                'id' => $kr->id,
                'uuid' => $kr->uuid,
                'objective_id' => $kr->objective_id,
                'title' => $kr->title,
                'order' => $kr->order,
                'message' => 'Key Result erfolgreich aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Key Results: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'key_results', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}


