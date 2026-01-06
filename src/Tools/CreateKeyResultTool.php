<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Models\Objective;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class CreateKeyResultTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.key_results.POST';
    }

    public function getDescription(): string
    {
        return 'POST /okr/key-results - Erstellt ein Key Result. WICHTIG: cycle_id + objective_id sind erforderlich (Kontext/Relation).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'cycle_id' => ['type' => 'integer', 'description' => 'Cycle-ID (required, Kontext).'],
                'objective_id' => ['type' => 'integer', 'description' => 'Objective-ID (required). Muss zu cycle_id gehören.'],
                'title' => ['type' => 'string', 'description' => 'Titel (required).'],
                'description' => ['type' => 'string'],
                'order' => ['type' => 'integer', 'description' => 'Optional: Reihenfolge. Wenn nicht gesetzt, ans Ende.'],
                'manager_user_id' => ['type' => 'integer'],
            ],
            'required' => ['cycle_id', 'objective_id', 'title'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $cycleId = $this->normalizeId($arguments['cycle_id'] ?? null);
            $objectiveId = $this->normalizeId($arguments['objective_id'] ?? null);
            $title = $arguments['title'] ?? null;
            if (!$cycleId || !$objectiveId || !is_string($title) || trim($title) === '') {
                return ToolResult::error('VALIDATION_ERROR', 'cycle_id, objective_id und title sind erforderlich.');
            }

            $teamId = $this->resolveOkrTeamId($context);
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden (OKR ist root-scoped).');
            }

            $objective = Objective::query()
                ->where('team_id', $teamId)
                ->where('id', $objectiveId)
                ->first();
            if (!$objective) {
                return ToolResult::error('NOT_FOUND', "Objective {$objectiveId} nicht gefunden (Team-ID: {$teamId}).");
            }
            if ((int)$objective->cycle_id !== (int)$cycleId) {
                return ToolResult::error('CONTEXT_MISMATCH', "Objective {$objectiveId} gehört nicht zu cycle_id {$cycleId}.");
            }

            $order = array_key_exists('order', $arguments) ? $this->normalizeId($arguments['order']) : null;
            if ($order === null) {
                $max = KeyResult::where('objective_id', $objectiveId)->max('order');
                $order = ($max ?? 0) + 1;
            }

            $kr = KeyResult::create([
                'objective_id' => $objective->id,
                'team_id' => $teamId,
                'user_id' => $context->user->id,
                'manager_user_id' => $this->normalizeId($arguments['manager_user_id'] ?? null),
                'title' => trim($title),
                'description' => $arguments['description'] ?? null,
                'order' => $order,
            ]);

            return ToolResult::success([
                'id' => $kr->id,
                'uuid' => $kr->uuid,
                'objective_id' => $kr->objective_id,
                'cycle_id' => $cycleId,
                'title' => $kr->title,
                'order' => $kr->order,
                'message' => 'Key Result erfolgreich erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Key Results: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'key_results', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}


