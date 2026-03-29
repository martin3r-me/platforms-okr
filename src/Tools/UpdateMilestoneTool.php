<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\Milestone;
use Platform\Okr\Models\Objective;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class UpdateMilestoneTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.milestones.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /okr/milestones/{id} - Aktualisiert einen Meilenstein.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Milestone-ID (required).'],
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'central_question' => ['type' => 'string', 'description' => 'Zentrale Frage zu diesem Meilenstein.'],
                'target_year' => ['type' => 'integer'],
                'target_quarter' => ['type' => 'integer', 'description' => 'Optional: Zielquartal (1-4). Kann nur gesetzt werden, wenn target_year gesetzt ist.'],
                'order' => ['type' => 'integer'],
                'objective_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Optional: Array von Objective-IDs zum Verknüpfen (ersetzt bestehende Verknüpfungen).'],
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

            $dirty = false;
            foreach (['title', 'description', 'central_question', 'order'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $value = $arguments[$field];
                    // Ignoriere leere Strings - diese bedeuten "nicht ändern"
                    if ($value === '' || $value === null) {
                        continue;
                    }
                    $milestone->{$field} = $value;
                    $dirty = true;
                }
            }

            if (array_key_exists('target_year', $arguments)) {
                $targetYearValue = $arguments['target_year'];
                // Ignoriere leere Strings und null - nur explizite Werte setzen
                if ($targetYearValue !== '' && $targetYearValue !== null) {
                    $targetYear = $this->normalizeId($targetYearValue);
                    $milestone->target_year = $targetYear;
                    $dirty = true;
                    
                    // If target_year is removed, also remove target_quarter
                    if (!$targetYear) {
                        $milestone->target_quarter = null;
                    }
                }
            }

            if (array_key_exists('target_quarter', $arguments)) {
                $targetQuarterValue = $arguments['target_quarter'];
                // Ignoriere leere Strings und null - nur explizite Werte setzen
                if ($targetQuarterValue !== '' && $targetQuarterValue !== null) {
                    $targetQuarter = $this->normalizeId($targetQuarterValue);
                    if ($targetQuarter && ($targetQuarter < 1 || $targetQuarter > 4)) {
                        return ToolResult::error('VALIDATION_ERROR', 'target_quarter muss zwischen 1 und 4 liegen.');
                    }
                    // Only set if target_year is set
                    if ($targetQuarter && !$milestone->target_year) {
                        return ToolResult::error('VALIDATION_ERROR', 'target_quarter kann nur gesetzt werden, wenn target_year gesetzt ist.');
                    }
                    $milestone->target_quarter = $targetQuarter;
                    $dirty = true;
                }
            }

            // objective_ids: Pivot-Tabelle synchronisieren
            if (array_key_exists('objective_ids', $arguments)) {
                $objectiveIds = array_map('intval', array_filter((array) ($arguments['objective_ids'] ?? []), fn($v) => $v !== null && $v !== '' && $v !== 0));
                if (!empty($objectiveIds)) {
                    $validCount = Objective::query()
                        ->where('team_id', $teamId)
                        ->whereIn('id', $objectiveIds)
                        ->count();
                    if ($validCount !== count($objectiveIds)) {
                        return ToolResult::error('VALIDATION_ERROR', 'Einige objective_ids sind ungültig (müssen existieren und zum Team gehören).');
                    }
                }
                $milestone->objectives()->sync($objectiveIds);
            }

            if ($dirty) {
                $milestone->save();
            }

            return ToolResult::success([
                'id' => $milestone->id,
                'uuid' => $milestone->uuid,
                'focus_area_id' => $milestone->focus_area_id,
                'title' => $milestone->title,
                'target_year' => $milestone->target_year,
                'target_quarter' => $milestone->target_quarter,
                'order' => $milestone->order,
                'objective_ids' => $milestone->objectives()->pluck('okr_objectives.id')->toArray(),
                'message' => 'Meilenstein erfolgreich aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Meilensteins: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'milestones', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
