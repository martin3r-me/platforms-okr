<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Models\KeyResultPerformance;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class UpdateKeyResultTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.key_results.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /okr/key-results/{id} - Aktualisiert ein Key Result. Optional cycle_id zur Kontext-Validierung.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'KeyResult-ID (required).'],
                'cycle_id' => ['type' => 'integer', 'description' => 'Optional: Kontext-Validierung (KR muss zu diesem Cycle gehören).'],
                'objective_id' => ['type' => 'integer', 'description' => 'Optional: Validierungskontext (wird nicht geändert).'],
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'order' => ['type' => 'integer'],
                'manager_user_id' => ['type' => 'integer'],
                // Optional: neue Performance-Version schreiben (wie UI)
                'value_type' => [
                    'type' => 'string',
                    'enum' => ['boolean', 'absolute', 'relative'],
                    'description' => 'Optional: Wenn gesetzt (oder target/current/is_completed), wird eine neue Performance-Version erzeugt. relative wird intern als percentage gespeichert.',
                ],
                'target_value' => [
                    'type' => 'number',
                    'description' => 'Optional: Zielwert (required, wenn value_type != boolean und du eine Performance-Version erzeugen willst).',
                ],
                'current_value' => [
                    'description' => 'Optional: Aktueller Wert. Bei boolean true/false; sonst Zahl.',
                    'oneOf' => [
                        ['type' => 'number'],
                        ['type' => 'boolean'],
                    ],
                ],
                'is_completed' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Nur relevant für boolean.',
                ],
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

            $kr = KeyResult::query()
                ->where('team_id', $teamId)
                ->with('objective')
                ->find($id);
            if (!$kr) {
                return ToolResult::error('NOT_FOUND', "Key Result {$id} nicht gefunden (Team-ID: {$teamId}).");
            }

            $cycleId = $this->normalizeId($arguments['cycle_id'] ?? null);
            if ($cycleId && (int)($kr->objective?->cycle_id) !== (int)$cycleId) {
                return ToolResult::error('CONTEXT_MISMATCH', "Key Result {$id} gehört nicht zu cycle_id {$cycleId}.");
            }

            $objectiveId = $this->normalizeId($arguments['objective_id'] ?? null);
            if ($objectiveId && (int)$kr->objective_id !== (int)$objectiveId) {
                return ToolResult::error('CONTEXT_MISMATCH', "Key Result {$id} gehört nicht zu objective_id {$objectiveId}.");
            }

            $dirty = false;
            foreach (['title', 'description', 'order', 'manager_user_id'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $kr->{$field} = $arguments[$field];
                    $dirty = true;
                }
            }
            if ($dirty) {
                $kr->save();
            }

            // Optional: neue Performance-Version erzeugen
            $hasPerfInput = array_key_exists('value_type', $arguments)
                || array_key_exists('target_value', $arguments)
                || array_key_exists('current_value', $arguments)
                || array_key_exists('is_completed', $arguments);
            if ($hasPerfInput) {
                $valueType = array_key_exists('value_type', $arguments)
                    ? ($arguments['value_type'] ?? null)
                    : ($kr->performance?->type ? $this->normalizeKeyResultValueType($kr->performance->type) : null);

                $perfType = $this->mapValueTypeToPerformanceType(is_string($valueType) ? $valueType : null);
                if (!$perfType) {
                    return ToolResult::error('VALIDATION_ERROR', "value_type muss einer der Werte sein: boolean|absolute|relative.");
                }

                $target = $arguments['target_value'] ?? ($kr->performance?->target_value ?? null);
                if ($perfType !== 'boolean' && !is_numeric($target)) {
                    return ToolResult::error('VALIDATION_ERROR', "target_value ist erforderlich (number), wenn value_type != boolean und eine Performance gesetzt wird.");
                }

                $currentArg = array_key_exists('current_value', $arguments)
                    ? $arguments['current_value']
                    : ($kr->performance?->current_value ?? 0);

                $isCompleted = (bool)($arguments['is_completed'] ?? ($kr->performance?->is_completed ?? false));

                $current = null;
                if ($perfType === 'boolean') {
                    // boolean: target=1, current=1/0, is_completed spiegelt current
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
                    'is_completed' => $perfType === 'boolean' ? $isCompleted : (bool)($kr->performance?->is_completed ?? false),
                    'performance_score' => $perfType === 'boolean' ? ($isCompleted ? 1.0 : 0.0) : (float)($kr->performance?->performance_score ?? 0.0),
                    'team_id' => $kr->team_id,
                    'user_id' => $context->user->id,
                ]);
            }

            return ToolResult::success([
                'id' => $kr->id,
                'uuid' => $kr->uuid,
                'objective_id' => $kr->objective_id,
                'title' => $kr->title,
                'order' => $kr->order,
                'message' => 'Key Result erfolgreich aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Key Results: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'key_results', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}


