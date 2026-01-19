<?php

namespace Platform\Okr\Tools;

use Illuminate\Support\Facades\DB;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class BulkCreateVisionImagesTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'okr.vision_images.bulk.POST';
    }

    public function getDescription(): string
    {
        return 'POST /okr/vision_images/bulk - Body MUSS {vision_images:[{focus_area_id,title,description?,order?}], defaults?} enthalten. Erstellt viele Zielbilder.';
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
                'vision_images' => [
                    'type' => 'array',
                    'description' => 'Liste von Zielbildern. Jedes Element entspricht den Parametern von okr.vision_images.POST (mindestens focus_area_id, title).',
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
            'required' => ['vision_images'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $visionImages = $arguments['vision_images'] ?? null;
            if (!is_array($visionImages) || empty($visionImages)) {
                return ToolResult::error('INVALID_ARGUMENT', 'vision_images muss ein nicht-leeres Array sein.');
            }

            $defaults = $arguments['defaults'] ?? [];
            if (!is_array($defaults)) {
                $defaults = [];
            }

            $atomic = (bool)($arguments['atomic'] ?? false);
            $singleTool = new CreateVisionImageTool();

            $run = function() use ($visionImages, $defaults, $singleTool, $context) {
                $results = [];
                $okCount = 0;
                $failCount = 0;

                foreach ($visionImages as $idx => $vi) {
                    if (!is_array($vi)) {
                        $failCount++;
                        $results[] = ['index' => $idx, 'ok' => false, 'error' => ['code' => 'INVALID_ITEM', 'message' => 'VisionImage-Item muss ein Objekt sein.']];
                        continue;
                    }

                    $payload = $defaults;
                    foreach ($vi as $k => $v) {
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

                return ['results' => $results, 'summary' => ['requested' => count($visionImages), 'ok' => $okCount, 'failed' => $failCount]];
            };

            $payload = $atomic ? DB::transaction(fn() => $run()) : $run();
            return ToolResult::success($payload);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Create der Zielbilder: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return ['category' => 'bulk', 'tags' => ['okr', 'vision_images', 'bulk', 'batch', 'create'], 'read_only' => false, 'requires_auth' => true, 'requires_team' => false, 'risk_level' => 'medium', 'idempotent' => false];
    }
}
