<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\HasDisplayName;
use Platform\Core\Contracts\HasKeyResultAncestors;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Models\KeyResultContext;
use Platform\Okr\Services\KeyResultContextResolver;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class GetContextKeyResultsTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    protected ?KeyResultContextResolver $resolver = null;

    public function __construct(?KeyResultContextResolver $resolver = null)
    {
        $this->resolver = $resolver;
    }

    protected function getResolver(): KeyResultContextResolver
    {
        if ($this->resolver === null) {
            $this->resolver = app(KeyResultContextResolver::class);
        }
        return $this->resolver;
    }

    public function getName(): string
    {
        return 'okr.context.key_results.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/contexts/{type}/{id}/key-results - Findet alle KeyResults, die mit einem bestimmten Kontext verknüpft sind (z.B. Task, Project, Note, Meeting). Berücksichtigt auch übergeordnete Kontexte (z.B. Tasks sind über Projects abgedeckt).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'context_type' => [
                    'type' => 'string',
                    'description' => 'Vollständiger Klassenname des Kontexts (z.B. "Platform\\Planner\\Models\\PlannerTask" oder "Platform\\Planner\\Models\\PlannerProject").',
                ],
                'context_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Kontexts (required).',
                ],
                'include_covered' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Auch KeyResults anzeigen, die über übergeordnete Kontexte abgedeckt sind (z.B. wenn ein Project verknüpft ist, sind alle Tasks im Project abgedeckt). Default: true.',
                    'default' => true,
                ],
                'only_primary' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Nur primäre Verknüpfungen anzeigen (default: true).',
                    'default' => true,
                ],
            ],
            'required' => ['context_type', 'context_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $contextType = $arguments['context_type'] ?? null;
            $contextId = $this->normalizeId($arguments['context_id'] ?? null);

            if (!$contextType || !$contextId) {
                return ToolResult::error('VALIDATION_ERROR', 'context_type und context_id sind erforderlich.');
            }

            if (!class_exists($contextType)) {
                return ToolResult::error('INVALID_TYPE', "Kontext-Typ '{$contextType}' existiert nicht.");
            }

            // Prüfe ob Kontext existiert
            $contextModel = $contextType::find($contextId);
            if (!$contextModel) {
                return ToolResult::error('NOT_FOUND', "Kontext {$contextType} mit ID {$contextId} nicht gefunden.");
            }

            $teamId = $this->resolveOkrTeamId($context);
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden (OKR ist root-scoped).');
            }

            $includeCovered = (bool) ($arguments['include_covered'] ?? true);
            $onlyPrimary = (bool) ($arguments['only_primary'] ?? true);

            // Hole Display-Name des Kontexts
            $contextLabel = null;
            if ($contextModel instanceof HasDisplayName) {
                $contextLabel = $contextModel->getDisplayName();
            } else {
                $contextLabel = $this->getResolver()->resolveLabel($contextType, $contextId);
            }

            // 1. Direkt verknüpfte KeyResults
            $directKeyResultIds = $this->getDirectKeyResultIds($contextType, $contextId, $onlyPrimary, $teamId);

            // 2. Über übergeordnete Kontexte abgedeckte KeyResults (wenn include_covered = true)
            $coveredKeyResultIds = collect();
            if ($includeCovered && $contextModel instanceof HasKeyResultAncestors) {
                $ancestors = $contextModel->keyResultAncestors();
                
                foreach ($ancestors as $ancestor) {
                    // Nur Root-Kontexte berücksichtigen (diese decken alle Child-Kontexte ab)
                    if ($ancestor['is_root'] ?? false) {
                        $rootKeyResultIds = $this->getDirectKeyResultIds(
                            $ancestor['type'],
                            $ancestor['id'],
                            true, // Nur primäre für Root-Kontexte
                            $teamId
                        );
                        $coveredKeyResultIds = $coveredKeyResultIds->merge($rootKeyResultIds);
                    }
                }
            }

            // Kombiniere alle KeyResult-IDs (direkt + abgedeckt)
            $allKeyResultIds = $directKeyResultIds->merge($coveredKeyResultIds)->unique();

            // Lade alle KeyResults mit Relations
            $keyResults = [];
            if ($allKeyResultIds->isNotEmpty()) {
                $keyResults = KeyResult::whereIn('id', $allKeyResultIds)
                    ->where('team_id', $teamId)
                    ->with(['objective.cycle.okr', 'performance', 'user'])
                    ->get()
                    ->map(function (KeyResult $kr) use ($directKeyResultIds, $coveredKeyResultIds) {
                        $isDirect = $directKeyResultIds->contains($kr->id);
                        $isCovered = $coveredKeyResultIds->contains($kr->id) && !$isDirect;

                        return [
                            'id' => $kr->id,
                            'uuid' => $kr->uuid,
                            'title' => $kr->title,
                            'description' => $kr->description,
                            'objective' => $kr->objective ? [
                                'id' => $kr->objective->id,
                                'title' => $kr->objective->title,
                                'cycle' => $kr->objective->cycle ? [
                                    'id' => $kr->objective->cycle->id,
                                    'template' => $kr->objective->cycle->template?->label ?? null,
                                ] : null,
                            ] : null,
                            'performance_score' => $kr->performance_score,
                            'value_summary' => $this->buildKeyResultValueSummary($kr->performance),
                            'is_direct' => $isDirect,
                            'is_covered' => $isCovered,
                            'link_type' => $isDirect ? 'direct' : ($isCovered ? 'covered' : 'unknown'),
                        ];
                    })
                    ->values()
                    ->toArray();
            }

            // Erstelle Zusammenfassung
            $directCount = $directKeyResultIds->count();
            $coveredCount = $coveredKeyResultIds->diff($directKeyResultIds)->count();
            $totalCount = count($keyResults);

            $message = $this->buildMessage($contextLabel, $directCount, $coveredCount, $totalCount);

            return ToolResult::success([
                'context' => [
                    'type' => $contextType,
                    'id' => $contextId,
                    'label' => $contextLabel,
                ],
                'summary' => [
                    'total' => $totalCount,
                    'direct' => $directCount,
                    'covered' => $coveredCount,
                ],
                'key_results' => $keyResults,
                'message' => $message,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der KeyResults: ' . $e->getMessage());
        }
    }

    /**
     * Gibt alle KeyResult-IDs zurück, die direkt mit einem Kontext verknüpft sind.
     */
    protected function getDirectKeyResultIds(string $contextType, int $contextId, bool $onlyPrimary, int $teamId): \Illuminate\Support\Collection
    {
        $query = KeyResultContext::where('context_type', $contextType)
            ->where('context_id', $contextId);

        if ($onlyPrimary) {
            $query->where('is_primary', true);
        }

        return $query->pluck('key_result_id')
            ->filter()
            ->unique();
    }

    /**
     * Erstellt eine menschenlesbare Zusammenfassung.
     */
    protected function buildMessage(?string $contextLabel, int $directCount, int $coveredCount, int $totalCount): string
    {
        $contextPart = $contextLabel ? "\"{$contextLabel}\"" : 'diesem Kontext';
        
        if ($totalCount === 0) {
            return "Keine KeyResults mit {$contextPart} verknüpft.";
        }

        $parts = [];
        if ($directCount > 0) {
            $parts[] = "{$directCount} direkt verknüpft";
        }
        if ($coveredCount > 0) {
            $parts[] = "{$coveredCount} über übergeordneten Kontext abgedeckt";
        }

        return "Mit {$contextPart} sind " . implode(' und ', $parts) . " KeyResult(s) verknüpft.";
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['okr', 'key_result', 'context', 'relationships', 'reverse_lookup'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
