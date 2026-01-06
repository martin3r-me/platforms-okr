<?php

namespace Platform\Okr\Tools;

use Illuminate\Database\Eloquent\Builder;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Okr\Models\Okr;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class ListOkrsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.okrs.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/okrs?team_id={id}&filters=[...]&search=...&sort=[...] - Listet OKRs (root-scoped). team_id ist optional und wird i.d.R. aus dem Root-Team des aktuellen Teams abgeleitet.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Root-Team-ID für OKR (scope_type=parent). Wenn nicht angegeben, wird sie aus dem Kontext abgeleitet.',
                    ],
                    'include_templates' => [
                        'type' => 'boolean',
                        'description' => 'Optional: Wenn true, werden auch is_template OKRs gelistet. Default: true.',
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

            $includeTemplates = (bool)($arguments['include_templates'] ?? true);

            $query = Okr::query()
                ->where('team_id', $teamId)
                ->with(['user', 'managerUser']);

            if (!$includeTemplates) {
                $query->where('is_template', false);
            }

            $this->applyStandardFilters($query, $arguments, [
                'title', 'description', 'is_template', 'auto_transfer', 'performance_score', 'manager_user_id', 'user_id', 'created_at', 'updated_at',
            ]);
            $this->applyStandardSearch($query, $arguments, ['title', 'description']);
            $this->applyStandardSort($query, $arguments, [
                'title', 'performance_score', 'created_at', 'updated_at',
            ], 'updated_at', 'desc');
            $this->applyStandardPagination($query, $arguments);

            $okrs = $query->get();

            $items = $okrs->map(function (Okr $okr) {
                return [
                    'id' => $okr->id,
                    'uuid' => $okr->uuid,
                    'title' => $okr->title,
                    'description' => $okr->description,
                    'team_id' => $okr->team_id,
                    'owner_user_id' => $okr->user_id,
                    'manager_user_id' => $okr->manager_user_id,
                    'manager_name' => $okr->managerUser?->name,
                    'is_template' => (bool)$okr->is_template,
                    'auto_transfer' => (bool)$okr->auto_transfer,
                    'performance_score' => $okr->performance_score,
                    'created_at' => $okr->created_at?->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'okrs' => $items,
                'count' => count($items),
                'team_id' => $teamId,
                'message' => count($items) . ' OKR(s) gefunden (Team-ID: ' . $teamId . ').',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der OKRs: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['okr', 'okrs', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}


