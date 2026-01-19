<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\VisionImage;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class UpdateVisionImageTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.vision_images.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /okr/vision_images/{id} - Aktualisiert ein Zielbild.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'VisionImage-ID (required).'],
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'central_question' => ['type' => 'string', 'description' => 'Zentrale Frage zu diesem Zielbild.'],
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

            $visionImage = VisionImage::query()->where('team_id', $teamId)->find($id);
            if (!$visionImage) {
                return ToolResult::error('NOT_FOUND', "VisionImage {$id} nicht gefunden (Team-ID: {$teamId}).");
            }

            $dirty = false;
            foreach (['title', 'description', 'central_question', 'order'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $visionImage->{$field} = $arguments[$field];
                    $dirty = true;
                }
            }

            if ($dirty) {
                $visionImage->save();
            }

            return ToolResult::success([
                'id' => $visionImage->id,
                'uuid' => $visionImage->uuid,
                'focus_area_id' => $visionImage->focus_area_id,
                'title' => $visionImage->title,
                'order' => $visionImage->order,
                'message' => 'Zielbild erfolgreich aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Zielbilds: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'vision_images', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
