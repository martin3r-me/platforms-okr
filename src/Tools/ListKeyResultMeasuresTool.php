<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class ListKeyResultMeasuresTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.kr_measures.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/kr-measures?key_result_id={id} - Listet die Measures eines Key Results inkl. aktuellem Rohwert, Zielerreichung (achievement) und Verfügbarkeit, plus die aggregierte KR-Erreichungsquote.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'key_result_id' => ['type' => 'integer', 'description' => 'Key-Result-ID (required).'],
            ],
            'required' => ['key_result_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (! $context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $krId = $this->normalizeId($arguments['key_result_id'] ?? null);
            if (! $krId) {
                return ToolResult::error('VALIDATION_ERROR', 'key_result_id ist erforderlich.');
            }

            $teamId = $this->resolveOkrTeamId($context);
            if (! $teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden.');
            }

            $kr = KeyResult::query()->where('team_id', $teamId)->with(['measures', 'performance'])->find($krId);
            if (! $kr) {
                return ToolResult::error('NOT_FOUND', "Key Result {$krId} nicht gefunden (Team-ID: {$teamId}).");
            }

            $measures = $kr->measures->map(fn ($m) => [
                'id' => $m->id,
                'metric_key' => $m->metric_key,
                'selector' => $m->selector,
                'role' => $m->role,
                'value_type' => $m->value_type,
                'polarity' => $m->polarity,
                'target_value' => $m->target_value,
                'baseline_value' => $m->baseline_value,
                'weight' => $m->weight,
                'window_mode' => $m->window_mode,
                'current_value' => $m->current_value,
                'achievement' => $m->achievement,
                'is_available' => (bool) $m->is_available,
                'label' => $m->label,
                'last_synced_at' => $m->last_synced_at?->toIso8601String(),
            ])->values()->toArray();

            $perf = $kr->performance;

            return ToolResult::success([
                'key_result_id' => $kr->id,
                'kr_progress' => $perf ? (float) $perf->performance_score : null,
                'kr_completed' => $perf ? (bool) $perf->is_completed : false,
                'measures' => $measures,
                'count' => count($measures),
                'message' => count($measures) . ' Measure(s) am Key Result.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Measures: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['okr', 'key_results', 'measures', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
