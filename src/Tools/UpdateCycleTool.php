<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class UpdateCycleTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.cycles.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /okr/cycles/{id} - Aktualisiert einen Cycle (notes/description/status).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Cycle-ID (required).'],
                'status' => [
                    'type' => 'string',
                    'enum' => ['draft', 'active', 'ending_soon', 'completed', 'past'],
                ],
                'notes' => ['type' => 'string'],
                'description' => ['type' => 'string'],
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

            $cycle = Cycle::query()->where('team_id', $teamId)->find($id);
            if (!$cycle) {
                return ToolResult::error('NOT_FOUND', "Cycle {$id} nicht gefunden (Team-ID: {$teamId}).");
            }

            $dirty = false;
            foreach (['status', 'notes', 'description'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $cycle->{$field} = $arguments[$field];
                    $dirty = true;
                }
            }
            if ($dirty) {
                $cycle->save();
            }

            return ToolResult::success([
                'id' => $cycle->id,
                'uuid' => $cycle->uuid,
                'status' => $cycle->status,
                'notes' => $cycle->notes,
                'description' => $cycle->description,
                'message' => 'Cycle erfolgreich aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Cycles: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'cycles', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}


