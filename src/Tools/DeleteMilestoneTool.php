<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\Milestone;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class DeleteMilestoneTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.milestones.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /okr/milestones/{id} - Löscht (soft-delete) einen Meilenstein.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Milestone-ID (required).'],
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

            $milestone = Milestone::query()->where('team_id', $teamId)->find($id);
            if (!$milestone) {
                return ToolResult::error('NOT_FOUND', "Milestone {$id} nicht gefunden (Team-ID: {$teamId}).");
            }

            $milestone->delete();

            return ToolResult::success([
                'id' => $id,
                'message' => 'Meilenstein erfolgreich gelöscht (soft-delete).',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Meilensteins: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'milestones', 'delete'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'high',
            'idempotent' => false,
        ];
    }
}
