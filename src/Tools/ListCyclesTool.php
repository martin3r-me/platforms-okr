<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class ListCyclesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.cycles.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/cycles?okr_id={id}&filters=[...]&search=...&sort=[...] - Listet Cycles (root-scoped) inkl. Template.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'Optional (root-scoped): Team-ID. Wenn nicht gesetzt, wird sie aus dem Kontext abgeleitet.',
                    ],
                    'okr_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Filter nach OKR-ID.',
                    ],
                    'only_current' => [
                        'type' => 'boolean',
                        'description' => 'Optional: Nur Cycles deren Template is_current=true hat.',
                    ],
                    'include_objectives_count' => [
                        'type' => 'boolean',
                        'description' => 'Optional: Wenn true, zählt Objectives/KeyResults (kann teurer sein). Default: false.',
                    ],
                ],
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $teamId = $this->normalizeId($arguments['team_id'] ?? null) ?? $this->resolveOkrTeamId($context);
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden (OKR ist root-scoped).');
            }

            $okrId = $this->normalizeId($arguments['okr_id'] ?? null);
            $onlyCurrent = (bool)($arguments['only_current'] ?? false);
            $includeCounts = (bool)($arguments['include_objectives_count'] ?? false);

            $query = Cycle::query()
                ->where('team_id', $teamId)
                ->with(['template', 'okr']);

            if ($okrId) {
                $query->where('okr_id', $okrId);
            }
            if ($onlyCurrent) {
                $query->whereHas('template', fn($q) => $q->where('is_current', true));
            }

            $this->applyStandardFilters($query, $arguments, [
                'okr_id', 'cycle_template_id', 'type', 'status', 'created_at', 'updated_at',
            ]);
            $this->applyStandardSearch($query, $arguments, ['notes', 'description']);
            $this->applyStandardSort($query, $arguments, ['status', 'type', 'created_at', 'updated_at'], 'created_at', 'desc');
            $this->applyStandardPagination($query, $arguments);

            $cycles = $query->get();

            $items = $cycles->map(function (Cycle $c) use ($includeCounts) {
                $objCount = null;
                $krCount = null;
                if ($includeCounts) {
                    $objCount = $c->objectives()->count();
                    $krCount = $c->keyResults()->count();
                }

                return [
                    'id' => $c->id,
                    'uuid' => $c->uuid,
                    'okr_id' => $c->okr_id,
                    'team_id' => $c->team_id,
                    'type' => $c->type,
                    'status' => $c->status,
                    'notes' => $c->notes,
                    'description' => $c->description,
                    'cycle_template_id' => $c->cycle_template_id,
                    'template' => $c->template ? [
                        'id' => $c->template->id,
                        'label' => $c->template->label,
                        'type' => $c->template->type,
                        'starts_at' => $this->dateToYmd($c->template->starts_at),
                        'ends_at' => $this->dateToYmd($c->template->ends_at),
                        'is_current' => (bool)$c->template->is_current,
                    ] : null,
                    'counts' => $includeCounts ? [
                        'objectives' => $objCount,
                        'key_results' => $krCount,
                    ] : null,
                    'created_at' => $c->created_at?->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'cycles' => $items,
                'count' => count($items),
                'team_id' => $teamId,
                'message' => count($items) . ' Cycle(s) gefunden (Team-ID: ' . $teamId . ').',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Cycles: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['okr', 'cycles', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}


