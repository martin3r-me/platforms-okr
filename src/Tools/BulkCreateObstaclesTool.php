<?php

namespace Platform\Okr\Tools;

use Illuminate\Support\Facades\DB;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class BulkCreateObstaclesTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'okr.obstacles.bulk.POST';
    }

    public function getDescription(): string
    {
        return 'POST /okr/obstacles/bulk - Body MUSS {obstacles:[{focus_area_id,title,description?,order?}], defaults?} enthalten. Erstellt viele Hindernisse.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'atomic' => ['type' => 'boolean', 'description' => 'Optional: Wenn true, werden alle Creates in einer DB-Transaktion ausgeführt. Standard: false.'],
                'defaults' => [
                    'type' => 'object',
                    'description' => 'Optional: Default-Werte, die auf jedes Item angewendet werden.',
                    'properties' => ['focus_area_id' => ['type' => 'integer']],
                ],
                'obstacles' => [
                    'type' => 'array',
                    'description' => 'Liste von Hindernissen. Jedes Element entspricht den Parametern von okr.obstacles.POST (mindestens focus_area_id, title).',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'focus_area_id' => ['type' => 'integer'],
                            'title' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'order' => ['type' => 'integer'],
                        ],
                        'required' => ['focus_area_id', 'title'],
                    ],
                ],
            ],
            'required' => ['obstacles'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $obstacles = $arguments['obstacles'] ?? null;
            if (!is_array($obstacles) || empty($obstacles)) {
                return ToolResult::error('INVALID_ARGUMENT', 'obstacles muss ein nicht-leeres Array sein.');
            }

            $defaults = $arguments['defaults'] ?? [];
            if (!is_array($defaults)) {
                $defaults = [];
            }

            $atomic = (bool)($arguments['atomic'] ?? false);
            $singleTool = new CreateObstacleTool();

            $run = function() use ($obstacles, $defaults, $singleTool, $context) {
                $results = [];
                $okCount = 0;
                $failCount = 0;

                foreach ($obstacles as $idx => $ob) {
                    if (!is_array($ob)) {
                        $failCount++;
                        $results[] = ['index' => $idx, 'ok' => false, 'error' => ['code' => 'INVALID_ITEM', 'message' => 'Obstacle-Item muss ein Objekt sein.']];
                        continue;
                    }

                    $payload = $defaults;
                    foreach ($ob as $k => $v) {
                        $payload[$k] = $v;
                    }

                    $res = $singleTool->execute($payload, $context);
                    if ($res->success) {
                        $okCount++;
                        $results[] = ['index' => $idx, 'ok' => true, 'data' => $res->data];
                    } else {
                        $failCount++;
                        $results[] = ['index' => $idx, 'ok' => false, 'error' => ['code' => $res->errorCode, 'message' => $res->error]];
                    }
                }

                return ['results' => $results, 'summary' => ['requested' => count($obstacles), 'ok' => $okCount, 'failed' => $failCount]];
            };

            $payload = $atomic ? DB::transaction(fn() => $run()) : $run();
            return ToolResult::success($payload);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Create der Hindernisse: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return ['category' => 'bulk', 'tags' => ['okr', 'obstacles', 'bulk', 'batch', 'create'], 'read_only' => false, 'requires_auth' => true, 'requires_team' => false, 'risk_level' => 'medium', 'idempotent' => false];
    }
}
