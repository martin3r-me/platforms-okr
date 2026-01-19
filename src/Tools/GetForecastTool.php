<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\Forecast;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class GetForecastTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.forecast.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/forecasts/{id} - Ruft eine einzelne Regnose (Forecast) ab.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Forecast-ID (required).',
                ],
            ],
            'required' => ['id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $id = $this->normalizeId($arguments['id'] ?? null);
            if (!$id) {
                return ToolResult::error('VALIDATION_ERROR', 'id ist erforderlich.');
            }

            $teamId = $this->resolveOkrTeamId($context);
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden (OKR ist root-scoped).');
            }

            $forecast = Forecast::query()
                ->where('team_id', $teamId)
                ->with(['currentVersion', 'focusAreas.visionImages', 'focusAreas.obstacles', 'focusAreas.milestones'])
                ->find($id);

            if (!$forecast) {
                return ToolResult::error('NOT_FOUND', "Forecast {$id} nicht gefunden (Team-ID: {$teamId}).");
            }

            return ToolResult::success([
                'id' => $forecast->id,
                'uuid' => $forecast->uuid,
                'title' => $forecast->title,
                'target_date' => $this->dateToYmd($forecast->target_date),
                'content' => $forecast->content,
                'current_version' => $forecast->currentVersion ? [
                    'id' => $forecast->currentVersion->id,
                    'version' => $forecast->currentVersion->version,
                    'content' => $forecast->currentVersion->content,
                    'change_note' => $forecast->currentVersion->change_note,
                    'created_at' => $this->dateToYmd($forecast->currentVersion->created_at),
                ] : null,
                'focus_areas' => $forecast->focusAreas->map(function ($fa) {
                    return [
                        'id' => $fa->id,
                        'uuid' => $fa->uuid,
                        'title' => $fa->title,
                        'description' => $fa->description,
                        'order' => $fa->order,
                        'vision_images_count' => $fa->visionImages->count(),
                        'obstacles_count' => $fa->obstacles->count(),
                        'milestones_count' => $fa->milestones->count(),
                    ];
                })->values()->toArray(),
                'created_at' => $this->dateToYmd($forecast->created_at),
                'updated_at' => $this->dateToYmd($forecast->updated_at),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Abrufen der Regnose: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'read',
            'tags' => ['okr', 'forecasts', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
