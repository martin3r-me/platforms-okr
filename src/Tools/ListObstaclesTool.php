<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Okr\Models\Obstacle;
use Platform\Okr\Models\FocusArea;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class ListObstaclesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.obstacles.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/obstacles?focus_area_id={id}&filters=[...]&search=... - Listet Hindernisse auf. WICHTIG: focus_area_id ist erforderlich (Hindernisse gehören zu einem Fokusraum).';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'focus_area_id' => [
                        'type' => 'integer',
                        'description' => 'FocusArea-ID (required). Hindernisse sind immer focus_area-bezogen.',
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

            $query = Obstacle::query()
                ->where('focus_area_id', $focusAreaId)
                ->where('team_id', $teamId)
                ->with(['focusArea']);

            $this->applyStandardFilters($query, $arguments, [
                'title', 'description', 'order', 'user_id', 'created_at', 'updated_at',
            ]);
            $this->applyStandardSearch($query, $arguments, ['title', 'description']);
            $this->applyStandardSort($query, $arguments, ['order', 'created_at', 'updated_at'], 'order', 'asc');
            $this->applyStandardPagination($query, $arguments);

            $obstacles = $query->get();
            $items = $obstacles->map(function (Obstacle $o) {
                return [
                    'id' => $o->id,
                    'uuid' => $o->uuid,
                    'focus_area_id' => $o->focus_area_id,
                    'title' => $o->title,
                    'description' => $o->description,
                    'order' => $o->order,
                    'created_at' => $this->dateToYmd($o->created_at),
                    'updated_at' => $this->dateToYmd($o->updated_at),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'obstacles' => $items,
                'count' => count($items),
                'message' => count($items) . ' Hindernis(se) gefunden.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Auflisten der Hindernisse: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'read',
            'tags' => ['okr', 'obstacles', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
