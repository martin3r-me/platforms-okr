<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\FocusArea;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class UpdateFocusAreaTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.focus_areas.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /okr/focus_areas/{id} - Aktualisiert einen Fokusraum.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'FocusArea-ID (required).'],
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'content' => ['type' => 'string'],
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

            $focusArea = FocusArea::query()->where('team_id', $teamId)->find($id);
            if (!$focusArea) {
                return ToolResult::error('NOT_FOUND', "FocusArea {$id} nicht gefunden (Team-ID: {$teamId}).");
            }

            $dirty = false;
            foreach (['title', 'description', 'content', 'order'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $focusArea->{$field} = $arguments[$field];
                    $dirty = true;
                }
            }

            if ($dirty) {
                $focusArea->save();
            }

            return ToolResult::success([
                'id' => $focusArea->id,
                'uuid' => $focusArea->uuid,
                'forecast_id' => $focusArea->forecast_id,
                'title' => $focusArea->title,
                'order' => $focusArea->order,
                'message' => 'Fokusraum erfolgreich aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Fokusraums: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'focus_areas', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
