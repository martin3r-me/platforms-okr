<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Models\KeyResultPerformance;
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
                // UI parity (optional): KR-Typ + initiale Performance-Version
                'value_type' => [
                    'type' => 'string',
                    'enum' => ['boolean', 'absolute', 'relative'],
                    'description' => 'Optional: Typ des Key Results. boolean=erledigt/offen, absolute=current/target, relative=current/target (intern percentage).',
                ],
                'target_value' => [
                    'type' => 'number',
                    'description' => 'Optional: Zielwert. Erforderlich, wenn du beim Erstellen eine Performance setzen willst und value_type != boolean.',
                ],
                'current_value' => [
                    'description' => 'Optional: Aktueller Wert. Bei boolean kannst du true/false senden; sonst Zahl.',
                    'oneOf' => [
                        ['type' => 'number'],
                        ['type' => 'boolean'],
                    ],
                ],
                'is_completed' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Nur relevant für boolean (erledigt/offen). Wenn gesetzt, wird current_value entsprechend normalisiert.',
                ],
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

            // Optional: initiale Performance-Version erzeugen (wie UI), aber nur wenn Felder mitgegeben wurden
            $valueType = array_key_exists('value_type', $arguments) ? ($arguments['value_type'] ?? null) : null;
            $hasPerfInput = array_key_exists('value_type', $arguments)
                || array_key_exists('target_value', $arguments)
                || array_key_exists('current_value', $arguments)
                || array_key_exists('is_completed', $arguments);
            if ($hasPerfInput) {
                $perfType = $this->mapValueTypeToPerformanceType(is_string($valueType) ? $valueType : null);
                if (!$perfType) {
                    return ToolResult::error('VALIDATION_ERROR', "value_type muss einer der Werte sein: boolean|absolute|relative.");
                }

                $isCompleted = (bool)($arguments['is_completed'] ?? false);

                $target = $arguments['target_value'] ?? null;
                if ($perfType !== 'boolean' && !is_numeric($target)) {
                    return ToolResult::error('VALIDATION_ERROR', "target_value ist erforderlich (number), wenn value_type != boolean und eine Performance gesetzt wird.");
                }

                $currentArg = $arguments['current_value'] ?? null;
                $current = null;
                if ($perfType === 'boolean') {
                    // UI: boolean target=1, current=1/0, is_completed spiegelt current
                    $completed = array_key_exists('is_completed', $arguments)
                        ? (bool)$arguments['is_completed']
                        : (bool)$currentArg;
                    $isCompleted = $completed;
                    $target = 1.0;
                    $current = $completed ? 1.0 : 0.0;
                } else {
                    $current = is_numeric($currentArg) ? (float)$currentArg : 0.0;
                }

                $kr->performances()->create([
                    'type' => $perfType,
                    'target_value' => $perfType === 'boolean' ? 1.0 : (float)$target,
                    'current_value' => $current,
                    'is_completed' => $perfType === 'boolean' ? $isCompleted : false,
                    'performance_score' => $perfType === 'boolean' ? ($isCompleted ? 1.0 : 0.0) : 0.0,
                    'team_id' => $kr->team_id,
                    'user_id' => $context->user->id,
                ]);
            }

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


