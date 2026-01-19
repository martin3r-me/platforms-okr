<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\FocusArea;
use Platform\Okr\Models\Forecast;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class CreateFocusAreaTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.focus_areas.POST';
    }

    public function getDescription(): string
    {
        return 'POST /okr/focus_areas - Erstellt einen Fokusraum. WICHTIG: forecast_id ist erforderlich (Fokusräume gehören zu einer Regnose).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'forecast_id' => ['type' => 'integer', 'description' => 'Forecast-ID (required).'],
                'title' => ['type' => 'string', 'description' => 'Titel (required).'],
                'description' => ['type' => 'string'],
                'content' => ['type' => 'string', 'description' => 'Optional: Markdown-Content.'],
                'order' => ['type' => 'integer', 'description' => 'Optional: Reihenfolge. Wenn nicht gesetzt, wird ans Ende gehängt.'],
            ],
            'required' => ['forecast_id', 'title'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $forecastId = $this->normalizeId($arguments['forecast_id'] ?? null);
            $title = $arguments['title'] ?? null;
            if (!$forecastId || !is_string($title) || trim($title) === '') {
                return ToolResult::error('VALIDATION_ERROR', 'forecast_id und title sind erforderlich.');
            }

            $teamId = $this->resolveOkrTeamId($context);
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden (OKR ist root-scoped).');
            }

            $forecast = Forecast::query()->where('team_id', $teamId)->find($forecastId);
            if (!$forecast) {
                return ToolResult::error('NOT_FOUND', "Forecast {$forecastId} nicht gefunden (Team-ID: {$teamId}).");
            }

            $order = array_key_exists('order', $arguments) ? $this->normalizeId($arguments['order']) : null;
            if ($order === null) {
                $max = FocusArea::where('forecast_id', $forecastId)->max('order');
                $order = ($max ?? 0) + 1;
            }

            $focusArea = FocusArea::create([
                'forecast_id' => $forecast->id,
                'team_id' => $teamId,
                'user_id' => $context->user->id,
                'title' => trim($title),
                'description' => $arguments['description'] ?? null,
                'content' => $arguments['content'] ?? null,
                'order' => $order,
            ]);

            return ToolResult::success([
                'id' => $focusArea->id,
                'uuid' => $focusArea->uuid,
                'forecast_id' => $focusArea->forecast_id,
                'title' => $focusArea->title,
                'order' => $focusArea->order,
                'message' => 'Fokusraum erfolgreich erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Fokusraums: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'focus_areas', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
