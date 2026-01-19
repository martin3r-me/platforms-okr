<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\Milestone;
use Platform\Okr\Models\FocusArea;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class CreateMilestoneTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.milestones.POST';
    }

    public function getDescription(): string
    {
        return 'POST /okr/milestones - Erstellt einen Meilenstein. WICHTIG: focus_area_id ist erforderlich (Meilensteine gehören zu einem Fokusraum).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'focus_area_id' => ['type' => 'integer', 'description' => 'FocusArea-ID (required).'],
                'title' => ['type' => 'string', 'description' => 'Titel (required).'],
                'description' => ['type' => 'string'],
                'central_question' => ['type' => 'string', 'description' => 'Zentrale Frage zu diesem Meilenstein.'],
                'target_year' => ['type' => 'integer', 'description' => 'Optional: Zieljahr.'],
                'target_quarter' => ['type' => 'integer', 'description' => 'Optional: Zielquartal (1-4). Kann nur gesetzt werden, wenn target_year gesetzt ist.'],
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

            $targetYear = $this->normalizeId($arguments['target_year'] ?? null);
            $targetQuarter = $this->normalizeId($arguments['target_quarter'] ?? null);
            
            // Ensure target_quarter is null if target_year is not set
            if (!$targetYear) {
                $targetQuarter = null;
            } elseif ($targetQuarter && ($targetQuarter < 1 || $targetQuarter > 4)) {
                return ToolResult::error('VALIDATION_ERROR', 'target_quarter muss zwischen 1 und 4 liegen.');
            }

            $order = array_key_exists('order', $arguments) ? $this->normalizeId($arguments['order']) : null;
            if ($order === null) {
                $max = Milestone::where('focus_area_id', $focusAreaId)->max('order');
                $order = ($max ?? 0) + 1;
            }

            $milestone = Milestone::create([
                'focus_area_id' => $focusArea->id,
                'team_id' => $teamId,
                'user_id' => $context->user->id,
                'title' => trim($title),
                'description' => $arguments['description'] ?? null,
                'central_question' => $arguments['central_question'] ?? null,
                'target_year' => $targetYear,
                'target_quarter' => $targetQuarter,
                'order' => $order,
            ]);

            return ToolResult::success([
                'id' => $milestone->id,
                'uuid' => $milestone->uuid,
                'focus_area_id' => $milestone->focus_area_id,
                'title' => $milestone->title,
                'target_year' => $milestone->target_year,
                'target_quarter' => $milestone->target_quarter,
                'order' => $milestone->order,
                'message' => 'Meilenstein erfolgreich erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Meilensteins: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'milestones', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
