<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Services\KeyResultContextLinks;
use Platform\Okr\Tools\Concerns\ResolvesKeyResultContext;

class LinkKeyResultContextTool implements ToolContract, ToolMetadataContract
{
    use ResolvesKeyResultContext;

    public function getName(): string
    {
        return 'okr.key_result.contexts.POST';
    }

    public function getDescription(): string
    {
        return 'POST /okr/key-results/{id}/contexts - Verknüpft ein Erfolgskriterium (Key Result) mit einem Kontext, z. B. einer Planner-Aufgabe oder einem Projekt. '
            . 'Eine Aufgabe kann auf mehrere KRs einzahlen. Ist das Projekt der Aufgabe schon mit dem KR verknüpft, ist die Aufgabe bereits abgedeckt (Fehler ALREADY_COVERED). '
            . 'Idempotent: eine bestehende Verknüpfung bleibt unverändert. Lesen: okr.key_result.contexts.GET bzw. okr.context.key_results.GET.';
    }

    public function getSchema(): array
    {
        return $this->contextSchema();
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            [$kr, $type, $model, $error] = $this->resolveLinkTarget($arguments, $context);
            if ($error) {
                return $error;
            }

            $links = app(KeyResultContextLinks::class);

            $coveredVia = $links->coveredFor($type, $model->getKey())
                ->first(fn ($row) => $row['key_result']->id === $kr->id);
            if ($coveredVia) {
                return ToolResult::error('ALREADY_COVERED', "Erfolgskriterium {$kr->id} ist bereits über \"{$coveredVia['via']}\" verknüpft — der Kontext zahlt schon darauf ein.");
            }

            $row = $links->link($kr, $type, $model->getKey());

            return ToolResult::success([
                'key_result_id' => $kr->id,
                'key_result_title' => $kr->title,
                'context_type' => $type,
                'context_id' => $model->getKey(),
                'context_label' => $row->context_label,
                'linked_key_results' => $links->linkedTo($type, $model->getKey())->map(fn ($k) => ['id' => $k->id, 'title' => $k->title])->values()->all(),
                'message' => "\"{$row->context_label}\" zahlt jetzt auf \"{$kr->title}\" ein.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Verknüpfen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'key_result', 'contexts', 'planner', 'link'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => true,
        ];
    }
}
