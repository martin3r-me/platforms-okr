<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Models\Milestone;
use Platform\Okr\Models\Objective;
use Platform\Okr\Models\StrategicDocument;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class CreateObjectiveTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.objectives.POST';
    }

    public function getDescription(): string
    {
        return 'POST /okr/objectives - Erstellt ein Objective. WICHTIG: cycle_id ist erforderlich (Objectives sind immer cycle-bezogen).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'cycle_id' => ['type' => 'integer', 'description' => 'Cycle-ID (required).'],
                'title' => ['type' => 'string', 'description' => 'Titel (required).'],
                'description' => ['type' => 'string'],
                'is_mountain' => ['type' => 'boolean'],
                'order' => ['type' => 'integer', 'description' => 'Optional: Reihenfolge. Wenn nicht gesetzt, wird ans Ende gehängt.'],
                'manager_user_id' => ['type' => 'integer'],
                'vision_id' => ['type' => 'integer', 'description' => 'Optional: StrategicDocument-ID vom Typ vision.'],
                'milestone_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Optional: Array von Milestone-IDs zum Verknüpfen.'],
            ],
            'required' => ['cycle_id', 'title'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $cycleId = $this->normalizeId($arguments['cycle_id'] ?? null);
            $title = $arguments['title'] ?? null;
            if (!$cycleId || !is_string($title) || trim($title) === '') {
                return ToolResult::error('VALIDATION_ERROR', 'cycle_id und title sind erforderlich.');
            }

            $teamId = $this->resolveOkrTeamId($context);
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden (OKR ist root-scoped).');
            }

            $cycle = Cycle::query()->where('team_id', $teamId)->find($cycleId);
            if (!$cycle) {
                return ToolResult::error('NOT_FOUND', "Cycle {$cycleId} nicht gefunden (Team-ID: {$teamId}).");
            }

            $visionId = $this->normalizeId($arguments['vision_id'] ?? null);
            if ($visionId) {
                $ok = StrategicDocument::query()
                    ->where('team_id', $teamId)
                    ->where('type', 'vision')
                    ->where('id', $visionId)
                    ->exists();
                if (!$ok) {
                    return ToolResult::error('VALIDATION_ERROR', "vision_id={$visionId} ist ungültig (muss existieren, Typ=vision, Team-ID={$teamId}).");
                }
            }

            $order = array_key_exists('order', $arguments) ? $this->normalizeId($arguments['order']) : null;
            if ($order === null) {
                $max = Objective::where('cycle_id', $cycleId)->max('order');
                $order = ($max ?? 0) + 1;
            }

            $objective = Objective::create([
                'okr_id' => $cycle->okr_id,
                'cycle_id' => $cycle->id,
                'team_id' => $teamId,
                'user_id' => $context->user->id,
                'manager_user_id' => $this->normalizeId($arguments['manager_user_id'] ?? null),
                'title' => trim($title),
                'description' => $arguments['description'] ?? null,
                'is_mountain' => (bool)($arguments['is_mountain'] ?? false),
                'order' => $order,
                'vision_id' => $visionId,
            ]);

            // milestone_ids: Pivot-Tabelle synchronisieren
            if (array_key_exists('milestone_ids', $arguments)) {
                $milestoneIds = array_map('intval', array_filter((array) ($arguments['milestone_ids'] ?? []), fn($v) => $v !== null && $v !== '' && $v !== 0));
                if (!empty($milestoneIds)) {
                    $validCount = Milestone::query()
                        ->where('team_id', $teamId)
                        ->whereIn('id', $milestoneIds)
                        ->count();
                    if ($validCount !== count($milestoneIds)) {
                        return ToolResult::error('VALIDATION_ERROR', 'Einige milestone_ids sind ungültig (müssen existieren und zum Team gehören).');
                    }
                }
                $objective->milestones()->sync($milestoneIds);
            }

            return ToolResult::success([
                'id' => $objective->id,
                'uuid' => $objective->uuid,
                'okr_id' => $objective->okr_id,
                'cycle_id' => $objective->cycle_id,
                'title' => $objective->title,
                'order' => $objective->order,
                'milestone_ids' => $objective->milestones()->pluck('okr_milestones.id')->toArray(),
                'message' => 'Objective erfolgreich erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Objectives: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'objectives', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}


