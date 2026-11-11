<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Services\KeyResultContextLinks;
use Platform\Okr\Tools\Concerns\ResolvesKeyResultContext;

class UnlinkKeyResultContextTool implements ToolContract, ToolMetadataContract
{
    use ResolvesKeyResultContext;

    public function getName(): string
    {
        return 'okr.key_result.contexts.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /okr/key-results/{id}/contexts - Löst die direkte Verknüpfung zwischen Erfolgskriterium (Key Result) und Kontext (z. B. Planner-Aufgabe). '
            . 'Über das Projekt abgedeckte Verknüpfungen lassen sich nur am Projekt lösen.';
    }

    public function getSchema(): array
    {
        return $this->contextSchema();
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            [$kr, $type, $model, $error] = $this->resolveLinkTarget($arguments, $context, requireModel: false);
            if ($error) {
                return $error;
            }

            $contextId = (int) $arguments['context_id'];
            $removed = app(KeyResultContextLinks::class)->unlink($kr, $type, $contextId);
            if (! $removed) {
                return ToolResult::error('NOT_FOUND', "Keine direkte Verknüpfung zwischen Erfolgskriterium {$kr->id} und {$type} #{$contextId}.");
            }

            return ToolResult::success([
                'key_result_id' => $kr->id,
                'context_type' => $type,
                'context_id' => $contextId,
                'message' => 'Verknüpfung gelöst.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Lösen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'key_result', 'contexts', 'planner', 'unlink'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => true,
        ];
    }
}
