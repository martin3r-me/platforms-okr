<?php

namespace Platform\Okr\Tools;

use Illuminate\Support\Facades\DB;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class BulkCreateFocusAreasTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'okr.focus_areas.bulk.POST';
    }

    public function getDescription(): string
    {
        return 'POST /okr/focus_areas/bulk - Body MUSS {focus_areas:[{forecast_id,title,description?,content?,order?}], defaults?} enthalten. Erstellt viele Fokusräume.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'atomic' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Wenn true, werden alle Creates in einer DB-Transaktion ausgeführt (bei einem Fehler wird alles zurückgerollt). Standard: false.',
                ],
                'defaults' => [
                    'type' => 'object',
                    'description' => 'Optional: Default-Werte, die auf jedes Item angewendet werden (können pro Item überschrieben werden).',
                    'properties' => [
                        'forecast_id' => ['type' => 'integer'],
                    ],
                    'required' => [],
                ],
                'focus_areas' => [
                    'type' => 'array',
                    'description' => 'Liste von Fokusräumen. Jedes Element entspricht den Parametern von okr.focus_areas.POST (mindestens forecast_id, title).',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'forecast_id' => ['type' => 'integer'],
                            'title' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'content' => ['type' => 'string'],
                            'order' => ['type' => 'integer'],
                        ],
                        'required' => ['forecast_id', 'title'],
                    ],
                ],
            ],
            'required' => ['focus_areas'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $focusAreas = $arguments['focus_areas'] ?? null;
            if (!is_array($focusAreas) || empty($focusAreas)) {
                return ToolResult::error('INVALID_ARGUMENT', 'focus_areas muss ein nicht-leeres Array sein.');
            }

            $defaults = $arguments['defaults'] ?? [];
            if (!is_array($defaults)) {
                $defaults = [];
            }

            $atomic = (bool)($arguments['atomic'] ?? false);
            $singleTool = new CreateFocusAreaTool();

            $run = function() use ($focusAreas, $defaults, $singleTool, $context) {
                $results = [];
                $okCount = 0;
                $failCount = 0;

                foreach ($focusAreas as $idx => $fa) {
                    if (!is_array($fa)) {
                        $failCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => false,
                            'error' => ['code' => 'INVALID_ITEM', 'message' => 'FocusArea-Item muss ein Objekt sein.'],
                        ];
                        continue;
                    }

                    // Defaults anwenden, ohne explizite Werte zu überschreiben
                    $payload = $defaults;
                    foreach ($fa as $k => $v) {
                        $payload[$k] = $v;
                    }

                    $res = $singleTool->execute($payload, $context);
                    if ($res->success) {
                        $okCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => true,
                            'data' => $res->data,
                        ];
                    } else {
                        $failCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => false,
                            'error' => [
                                'code' => $res->errorCode,
                                'message' => $res->error,
                            ],
                        ];
                    }
                }

                return [
                    'results' => $results,
                    'summary' => [
                        'requested' => count($focusAreas),
                        'ok' => $okCount,
                        'failed' => $failCount,
                    ],
                ];
            };

            $payload = $atomic ? DB::transaction(fn() => $run()) : $run();

            return ToolResult::success($payload);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Create der Fokusräume: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'bulk',
            'tags' => ['okr', 'focus_areas', 'bulk', 'batch', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
