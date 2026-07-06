<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Services\KeyResultMetricRegistry;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Models\KeyResultMeasure;
use Platform\Okr\Services\KeyResultMeasureSyncService;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class CreateKeyResultMeasureTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.kr_measures.POST';
    }

    public function getDescription(): string
    {
        return 'POST /okr/kr-measures - Hängt eine dynamische Messgröße (Measure) an ein Key Result. metric_key + selector kommen aus okr.kr_metrics.GET. role: score (gewichteter Beitrag) | gate (Pass/Fail, blockt "erreicht") | cap (deckelt die Quote) | info. Die Engine synct den Wert sofort und rechnet die Erreichungsquote. Für value_type != boolean ist target_value erforderlich.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'key_result_id' => ['type' => 'integer', 'description' => 'Key-Result-ID (required).'],
                'metric_key' => ['type' => 'string', 'description' => 'Metrik-Key aus okr.kr_metrics.GET (z.B. "planner.project.tasks_done_ratio"). "manual" für handgepflegt.'],
                'selector' => ['type' => 'object', 'description' => 'Selector-Werte laut selector_schema, z.B. {"project_id": 193}. Leer bei kr_entity/team.'],
                'role' => ['type' => 'string', 'description' => 'score | gate | cap | info. Default: score.', 'enum' => ['score', 'gate', 'cap', 'info']],
                'target_value' => ['type' => 'number', 'description' => 'Zielwert (erforderlich außer bei boolean/info). Ratio als Anteil 0..1.'],
                'baseline_value' => ['type' => 'number', 'description' => 'Optional: Startwert. Ohne Angabe: 0 bei up+ratio/boolean, sonst Auto-Freeze beim ersten Sync.'],
                'weight' => ['type' => 'number', 'description' => 'Gewicht (nur role=score). Default 1.'],
                'polarity' => ['type' => 'string', 'description' => 'Optional: up | down (überschreibt default der Metrik).', 'enum' => ['up', 'down']],
                'window_mode' => ['type' => 'string', 'description' => 'Optional: cumulative | period (nur wenn die Metrik supports_window).', 'enum' => ['cumulative', 'period']],
                'label' => ['type' => 'string', 'description' => 'Optional: Anzeigelabel.'],
            ],
            'required' => ['key_result_id', 'metric_key'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (! $context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $krId = $this->normalizeId($arguments['key_result_id'] ?? null);
            $metricKey = is_string($arguments['metric_key'] ?? null) ? trim($arguments['metric_key']) : '';
            if (! $krId || $metricKey === '') {
                return ToolResult::error('VALIDATION_ERROR', 'key_result_id und metric_key sind erforderlich.');
            }

            $teamId = $this->resolveOkrTeamId($context);
            if (! $teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden (OKR ist root-scoped).');
            }

            $kr = KeyResult::query()->where('team_id', $teamId)->find($krId);
            if (! $kr) {
                return ToolResult::error('NOT_FOUND', "Key Result {$krId} nicht gefunden (Team-ID: {$teamId}).");
            }

            $registry = resolve(KeyResultMetricRegistry::class);
            $selector = is_array($arguments['selector'] ?? null) ? $arguments['selector'] : [];
            $role = $arguments['role'] ?? KeyResultMeasure::ROLE_SCORE;

            $valueType = null;
            $binding = 'instance';
            $polarity = $arguments['polarity'] ?? 'up';

            if ($metricKey !== 'manual') {
                $def = $registry->definition($metricKey);
                if (! $def) {
                    return ToolResult::error('NOT_FOUND', "metric_key '{$metricKey}' ist nicht registriert. Nutze okr.kr_metrics.GET.");
                }
                if (! in_array($role, $def['supported_roles'] ?? ['score', 'gate', 'cap', 'info'], true)) {
                    return ToolResult::error('VALIDATION_ERROR', "role '{$role}' wird von '{$metricKey}' nicht unterstützt.");
                }
                // Selector gegen selector_schema prüfen
                foreach (($def['selector_schema'] ?? []) as $field) {
                    if (! empty($field['required']) && ! array_key_exists($field['field'], $selector)) {
                        return ToolResult::error('VALIDATION_ERROR', "Selector-Feld '{$field['field']}' ist erforderlich (siehe selector_schema).");
                    }
                }
                $valueType = $def['value_type'] ?? null;
                $binding = $def['binding'] ?? 'instance';
                $polarity = $arguments['polarity'] ?? ($def['default_polarity'] ?? 'up');
            }

            $target = isset($arguments['target_value']) ? (float) $arguments['target_value'] : null;
            if ($valueType === 'boolean' && $target === null) {
                $target = 1.0;
            }
            if ($valueType !== 'boolean' && $role !== KeyResultMeasure::ROLE_INFO && $target === null) {
                return ToolResult::error('VALIDATION_ERROR', 'target_value ist erforderlich (außer bei boolean/info).');
            }

            $measure = KeyResultMeasure::create([
                'key_result_id' => $kr->id,
                'metric_key' => $metricKey,
                'selector' => $selector ?: null,
                'binding' => $binding,
                'role' => $role,
                'value_type' => $valueType,
                'polarity' => $polarity,
                'target_value' => $target,
                'baseline_value' => isset($arguments['baseline_value']) ? (float) $arguments['baseline_value'] : null,
                'weight' => isset($arguments['weight']) ? (float) $arguments['weight'] : 1,
                'window_mode' => $arguments['window_mode'] ?? null,
                'label' => $arguments['label'] ?? null,
                'order' => (int) (KeyResultMeasure::where('key_result_id', $kr->id)->max('order')) + 1,
                'team_id' => $kr->team_id,
                'user_id' => $context->user->id,
            ]);

            // Sofort synchronisieren + KR bewerten
            $eval = ['progress' => null, 'completed' => false];
            if ($metricKey !== 'manual') {
                $eval = resolve(KeyResultMeasureSyncService::class)->syncKeyResult($kr->fresh());
            }
            $measure->refresh();

            return ToolResult::success([
                'id' => $measure->id,
                'uuid' => $measure->uuid,
                'key_result_id' => $measure->key_result_id,
                'metric_key' => $measure->metric_key,
                'selector' => $measure->selector,
                'role' => $measure->role,
                'value_type' => $measure->value_type,
                'polarity' => $measure->polarity,
                'target_value' => $measure->target_value,
                'baseline_value' => $measure->baseline_value,
                'current_value' => $measure->current_value,
                'achievement' => $measure->achievement,
                'is_available' => (bool) $measure->is_available,
                'label' => $measure->label,
                'kr_progress' => $eval['progress'],
                'kr_completed' => $eval['completed'],
                'message' => 'Measure angehängt und gesynct.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Anhängen des Measures: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'key_results', 'measures', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
