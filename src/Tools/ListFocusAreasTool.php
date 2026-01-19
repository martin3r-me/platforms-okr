<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Okr\Models\FocusArea;
use Platform\Okr\Models\Forecast;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class ListFocusAreasTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.focus_areas.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/focus_areas?forecast_id={id}&filters=[...]&search=... - Listet Fokusräume auf. WICHTIG: forecast_id ist erforderlich (Fokusräume gehören zu einer Regnose).';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'forecast_id' => [
                        'type' => 'integer',
                        'description' => 'Forecast-ID (required). Fokusräume sind immer forecast-bezogen.',
                    ],
                ],
                'required' => ['forecast_id'],
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $forecastId = $this->normalizeId($arguments['forecast_id'] ?? null);
            if (!$forecastId) {
                return ToolResult::error('VALIDATION_ERROR', 'forecast_id ist erforderlich.');
            }

            $teamId = $this->resolveOkrTeamId($context);
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden (OKR ist root-scoped).');
            }

            $forecast = Forecast::query()->where('team_id', $teamId)->find($forecastId);
            if (!$forecast) {
                return ToolResult::error('NOT_FOUND', "Forecast {$forecastId} nicht gefunden (Team-ID: {$teamId}).");
            }

            $query = FocusArea::query()
                ->where('forecast_id', $forecastId)
                ->where('team_id', $teamId)
                ->orderBy('order');

            $result = $this->applyStandardGetOperations($query, $arguments, $context);

            return ToolResult::success($result);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Auflisten der Fokusräume: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'read',
            'tags' => ['okr', 'focus_areas', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
