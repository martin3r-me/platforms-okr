<?php

namespace Platform\Okr\Tools\Concerns;

use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Services\KeyResultContextLinks;

/** Gemeinsame Eingabe-Prüfung der KR-Kontext-Schreibtools (POST/DELETE). */
trait ResolvesKeyResultContext
{
    use ResolvesOkrScope;

    protected function contextSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'key_result_id' => ['type' => 'integer', 'description' => 'KeyResult-ID (required).'],
                'context_type' => [
                    'type' => 'string',
                    'description' => 'Kontext-Typ als Morph-Alias ("planner_task", "project") oder Klassenname ("Platform\\Planner\\Models\\PlannerTask"). Default: planner_task.',
                    'default' => 'planner_task',
                ],
                'context_id' => ['type' => 'integer', 'description' => 'ID des Kontexts, z. B. Aufgaben-ID (required).'],
            ],
            'required' => ['key_result_id', 'context_id'],
        ];
    }

    /**
     * @return array{0: ?KeyResult, 1: ?string, 2: mixed, 3: ?ToolResult}
     */
    protected function resolveLinkTarget(array $arguments, ToolContext $context, bool $requireModel = true): array
    {
        $fail = fn (string $code, string $msg) => [null, null, null, ToolResult::error($code, $msg)];

        if (! $context->user) {
            return $fail('AUTH_ERROR', 'Kein User im Kontext gefunden.');
        }

        $krId = $this->normalizeId($arguments['key_result_id'] ?? null);
        $contextId = $this->normalizeId($arguments['context_id'] ?? null);
        if (! $krId || ! $contextId) {
            return $fail('VALIDATION_ERROR', 'key_result_id und context_id sind erforderlich.');
        }

        $rawType = (string) ($arguments['context_type'] ?? 'planner_task');
        $type = KeyResultContextLinks::resolveType($rawType);
        if (! $type) {
            return $fail('VALIDATION_ERROR', "Unbekannter context_type \"{$rawType}\".");
        }

        $teamId = $this->resolveOkrTeamId($context);
        if (! $teamId) {
            return $fail('MISSING_TEAM', 'Kein Team im Kontext gefunden (OKR ist root-scoped).');
        }

        $kr = KeyResult::where('team_id', $teamId)->find($krId);
        if (! $kr) {
            return $fail('NOT_FOUND', "Erfolgskriterium {$krId} nicht gefunden (Team-ID: {$teamId}).");
        }

        $model = $type::find($contextId);
        if (! $model && $requireModel) {
            return $fail('NOT_FOUND', "{$rawType} #{$contextId} nicht gefunden.");
        }

        // Kontext muss in einem Team liegen, in dem der User Mitglied ist
        if ($model && isset($model->team_id) && $model->team_id
            && method_exists($context->user, 'teams')
            && ! $context->user->teams()->where('teams.id', $model->team_id)->exists()) {
            return $fail('ACCESS_DENIED', "Kein Zugriff auf {$rawType} #{$contextId}.");
        }

        return [$kr, $type, $model, null];
    }
}
