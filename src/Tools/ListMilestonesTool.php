<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Okr\Models\Milestone;
use Platform\Okr\Models\FocusArea;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class ListMilestonesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.milestones.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/milestones?focus_area_id={id}&filters=[...]&search=... - Listet Meilensteine auf. WICHTIG: focus_area_id ist erforderlich (Meilensteine gehören zu einem Fokusraum).';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'focus_area_id' => [
                        'type' => 'integer',
                        'description' => 'FocusArea-ID (required). Meilensteine sind immer focus_area-bezogen.',
                    ],
                ],
                'required' => ['focus_area_id'],
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $focusAreaId = $this->normalizeId($arguments['focus_area_id'] ?? null);
            if (!$focusAreaId) {
                return ToolResult::error('VALIDATION_ERROR', 'focus_area_id ist erforderlich.');
            }

            $teamId = $this->resolveOkrTeamId($context);
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden (OKR ist root-scoped).');
            }

            $focusArea = FocusArea::query()->where('team_id', $teamId)->find($focusAreaId);
            if (!$focusArea) {
                return ToolResult::error('NOT_FOUND', "FocusArea {$focusAreaId} nicht gefunden (Team-ID: {$teamId}).");
            }

            $query = Milestone::query()
                ->where('focus_area_id', $focusAreaId)
                ->where('team_id', $teamId)
                ->with(['focusArea']);

            $this->applyStandardFilters($query, $arguments, [
                'title', 'description', 'target_year', 'target_quarter', 'order', 'user_id', 'created_at', 'updated_at',
            ]);
            $this->applyStandardSearch($query, $arguments, ['title', 'description']);
            $this->applyStandardSort($query, $arguments, ['order', 'target_year', 'target_quarter', 'created_at', 'updated_at'], 'order', 'asc');
            $this->applyStandardPagination($query, $arguments);

            $milestones = $query->get();
            $items = $milestones->map(function (Milestone $m) {
                return [
                    'id' => $m->id,
                    'uuid' => $m->uuid,
                    'focus_area_id' => $m->focus_area_id,
                    'title' => $m->title,
                    'description' => $m->description,
                    'target_year' => $m->target_year,
                    'target_quarter' => $m->target_quarter,
                    'order' => $m->order,
                    'created_at' => $this->dateToYmd($m->created_at),
                    'updated_at' => $this->dateToYmd($m->updated_at),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'milestones' => $items,
                'count' => count($items),
                'message' => count($items) . ' Meilenstein(e) gefunden.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Auflisten der Meilensteine: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'read',
            'tags' => ['okr', 'milestones', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
