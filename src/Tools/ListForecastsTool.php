<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Okr\Models\Forecast;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class ListForecastsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.forecasts.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/forecasts?filters=[...]&search=... - Listet Regnosen (Forecasts) auf. Unterstützt Filter/Search/Sort/Pagination.';
    }

    public function getSchema(): array
    {
        return $this->getStandardGetSchema();
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $teamId = $this->resolveOkrTeamId($context);
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden (OKR ist root-scoped).');
            }

            $query = Forecast::query()
                ->where('team_id', $teamId)
                ->with(['currentVersion', 'focusAreas']);

            $this->applyStandardFilters($query, $arguments, [
                'title', 'target_date', 'user_id', 'created_at', 'updated_at',
            ]);
            $this->applyStandardSearch($query, $arguments, ['title']);
            $this->applyStandardSort($query, $arguments, ['target_date', 'created_at', 'updated_at'], 'target_date', 'desc');
            $this->applyStandardPagination($query, $arguments);

            $forecasts = $query->get();
            $items = $forecasts->map(function (Forecast $f) {
                return [
                    'id' => $f->id,
                    'uuid' => $f->uuid,
                    'title' => $f->title,
                    'target_date' => $this->dateToYmd($f->target_date),
                    'focus_areas_count' => $f->focusAreas->count(),
                    'current_version' => $f->currentVersion ? [
                        'version' => $f->currentVersion->version,
                        'created_at' => $this->dateToYmd($f->currentVersion->created_at),
                    ] : null,
                    'created_at' => $this->dateToYmd($f->created_at),
                    'updated_at' => $this->dateToYmd($f->updated_at),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'forecasts' => $items,
                'count' => count($items),
                'message' => count($items) . ' Regnose(n) gefunden.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Auflisten der Regnosen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'read',
            'tags' => ['okr', 'forecasts', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
