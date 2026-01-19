<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\Obstacle;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class UpdateObstacleTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.obstacles.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /okr/obstacles/{id} - Aktualisiert ein Hindernis.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Obstacle-ID (required).'],
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'order' => ['type' => 'integer'],
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

            $obstacle = Obstacle::query()->where('team_id', $teamId)->find($id);
            if (!$obstacle) {
                return ToolResult::error('NOT_FOUND', "Obstacle {$id} nicht gefunden (Team-ID: {$teamId}).");
            }

            $dirty = false;
            foreach (['title', 'description', 'order'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $obstacle->{$field} = $arguments[$field];
                    $dirty = true;
                }
            }

            if ($dirty) {
                $obstacle->save();
            }

            return ToolResult::success([
                'id' => $obstacle->id,
                'uuid' => $obstacle->uuid,
                'focus_area_id' => $obstacle->focus_area_id,
                'title' => $obstacle->title,
                'order' => $obstacle->order,
                'message' => 'Hindernis erfolgreich aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Hindernisses: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'obstacles', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
