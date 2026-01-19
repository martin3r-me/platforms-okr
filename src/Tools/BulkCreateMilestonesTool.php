<?php

namespace Platform\Okr\Tools;

use Illuminate\Support\Facades\DB;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class BulkCreateMilestonesTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'okr.milestones.bulk.POST';
    }

    public function getDescription(): string
    {
        return 'POST /okr/milestones/bulk - Body MUSS {milestones:[{focus_area_id,title,description?,target_year?,target_quarter?,order?}], defaults?} enthalten. Erstellt viele Meilensteine.';
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
                'milestones' => [
                    'type' => 'array',
                    'description' => 'Liste von Meilensteinen. Jedes Element entspricht den Parametern von okr.milestones.POST (mindestens focus_area_id, title).',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'focus_area_id' => ['type' => 'integer'],
                            'title' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'target_year' => ['type' => 'integer'],
                            'target_quarter' => ['type' => 'integer'],
                            'order' => ['type' => 'integer'],
                        ],
                        'required' => ['focus_area_id', 'title'],
                    ],
                ],
            ],
            'required' => ['milestones'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $milestones = $arguments['milestones'] ?? null;
            if (!is_array($milestones) || empty($milestones)) {
                return ToolResult::error('INVALID_ARGUMENT', 'milestones muss ein nicht-leeres Array sein.');
            }

            $defaults = $arguments['defaults'] ?? [];
            if (!is_array($defaults)) {
                $defaults = [];
            }

            $atomic = (bool)($arguments['atomic'] ?? false);
            $singleTool = new CreateMilestoneTool();

            $run = function() use ($milestones, $defaults, $singleTool, $context) {
                $results = [];
                $okCount = 0;
                $failCount = 0;

                foreach ($milestones as $idx => $ms) {
                    if (!is_array($ms)) {
                        $failCount++;
                        $results[] = ['index' => $idx, 'ok' => false, 'error' => ['code' => 'INVALID_ITEM', 'message' => 'Milestone-Item muss ein Objekt sein.']];
                        continue;
                    }

                    $payload = $defaults;
                    foreach ($ms as $k => $v) {
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

                return ['results' => $results, 'summary' => ['requested' => count($milestones), 'ok' => $okCount, 'failed' => $failCount]];
            };

            $payload = $atomic ? DB::transaction(fn() => $run()) : $run();
            return ToolResult::success($payload);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Create der Meilensteine: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return ['category' => 'bulk', 'tags' => ['okr', 'milestones', 'bulk', 'batch', 'create'], 'read_only' => false, 'requires_auth' => true, 'requires_team' => false, 'risk_level' => 'medium', 'idempotent' => false];
    }
}
