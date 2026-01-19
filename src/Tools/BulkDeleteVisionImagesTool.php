<?php

namespace Platform\Okr\Tools;

use Illuminate\Support\Facades\DB;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class BulkDeleteVisionImagesTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'okr.vision_images.bulk.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /okr/vision_images/bulk - Löscht (soft-delete) mehrere Zielbilder in einem Request.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'atomic' => ['type' => 'boolean'],
                'ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
            ],
            'required' => ['ids'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $ids = $arguments['ids'] ?? null;
            if (!is_array($ids) || empty($ids)) {
                return ToolResult::error('INVALID_ARGUMENT', 'ids muss ein nicht-leeres Array sein.');
            }

            $atomic = (bool)($arguments['atomic'] ?? false);
            $singleTool = new DeleteVisionImageTool();

            $run = function() use ($ids, $singleTool, $context) {
                $results = [];
                $okCount = 0;
                $failCount = 0;

                foreach ($ids as $idx => $id) {
                    $res = $singleTool->execute(['id' => $id], $context);
                    if ($res->success) {
                        $okCount++;
                        $results[] = ['index' => $idx, 'ok' => true, 'data' => $res->data];
                    } else {
                        $failCount++;
                        $results[] = ['index' => $idx, 'ok' => false, 'error' => ['code' => $res->errorCode, 'message' => $res->error]];
                    }
                }

                return ['results' => $results, 'summary' => ['requested' => count($ids), 'ok' => $okCount, 'failed' => $failCount]];
            };

            $payload = $atomic ? DB::transaction(fn() => $run()) : $run();
            return ToolResult::success($payload);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Delete der Zielbilder: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return ['category' => 'bulk', 'tags' => ['okr', 'vision_images', 'bulk', 'batch', 'delete'], 'read_only' => false, 'requires_auth' => true, 'requires_team' => false, 'risk_level' => 'high', 'idempotent' => false];
    }
}
