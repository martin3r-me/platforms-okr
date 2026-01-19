<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\VisionImage;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class DeleteVisionImageTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.vision_images.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /okr/vision_images/{id} - Löscht (soft-delete) ein Zielbild.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'VisionImage-ID (required).'],
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

            $visionImage = VisionImage::query()->where('team_id', $teamId)->find($id);
            if (!$visionImage) {
                return ToolResult::error('NOT_FOUND', "VisionImage {$id} nicht gefunden (Team-ID: {$teamId}).");
            }

            $visionImage->delete();

            return ToolResult::success([
                'id' => $id,
                'message' => 'Zielbild erfolgreich gelöscht (soft-delete).',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Zielbilds: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'vision_images', 'delete'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'high',
            'idempotent' => false,
        ];
    }
}
