<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\VisionImage;
use Platform\Okr\Models\FocusArea;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class CreateVisionImageTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.vision_images.POST';
    }

    public function getDescription(): string
    {
        return 'POST /okr/vision_images - Erstellt ein Zielbild. WICHTIG: focus_area_id ist erforderlich (Zielbilder gehören zu einem Fokusraum).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'focus_area_id' => ['type' => 'integer', 'description' => 'FocusArea-ID (required).'],
                'title' => ['type' => 'string', 'description' => 'Titel (required).'],
                'description' => ['type' => 'string'],
                'order' => ['type' => 'integer', 'description' => 'Optional: Reihenfolge. Wenn nicht gesetzt, wird ans Ende gehängt.'],
            ],
            'required' => ['focus_area_id', 'title'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $focusAreaId = $this->normalizeId($arguments['focus_area_id'] ?? null);
            $title = $arguments['title'] ?? null;
            if (!$focusAreaId || !is_string($title) || trim($title) === '') {
                return ToolResult::error('VALIDATION_ERROR', 'focus_area_id und title sind erforderlich.');
            }

            $teamId = $this->resolveOkrTeamId($context);
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden (OKR ist root-scoped).');
            }

            $focusArea = FocusArea::query()->where('team_id', $teamId)->find($focusAreaId);
            if (!$focusArea) {
                return ToolResult::error('NOT_FOUND', "FocusArea {$focusAreaId} nicht gefunden (Team-ID: {$teamId}).");
            }

            $order = array_key_exists('order', $arguments) ? $this->normalizeId($arguments['order']) : null;
            if ($order === null) {
                $max = VisionImage::where('focus_area_id', $focusAreaId)->max('order');
                $order = ($max ?? 0) + 1;
            }

            $visionImage = VisionImage::create([
                'focus_area_id' => $focusArea->id,
                'team_id' => $teamId,
                'user_id' => $context->user->id,
                'title' => trim($title),
                'description' => $arguments['description'] ?? null,
                'order' => $order,
            ]);

            return ToolResult::success([
                'id' => $visionImage->id,
                'uuid' => $visionImage->uuid,
                'focus_area_id' => $visionImage->focus_area_id,
                'title' => $visionImage->title,
                'order' => $visionImage->order,
                'message' => 'Zielbild erfolgreich erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Zielbilds: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'vision_images', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
