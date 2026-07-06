<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Services\KeyResultMetricRegistry;

class ListKeyResultMetricsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'okr.kr_metrics.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/kr-metrics - Discovery: listet alle verfügbaren KR-Metriken aus allen Modulen (Katalog der KeyResultMetricRegistry). Jeder Eintrag enthält metric_key, value_type, default_polarity, supported_roles, binding und selector_schema (was beim Anhängen ausgewählt werden muss). Nutze das Ergebnis, um mit okr.kr_measures.POST ein Measure an ein Key Result zu hängen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'module' => ['type' => 'string', 'description' => 'Optional: nur Metriken dieses Moduls (z.B. "planner").'],
                'binding' => ['type' => 'string', 'description' => 'Optional: instance | kr_entity | team.'],
                'search' => ['type' => 'string', 'description' => 'Optional: Freitext in metric_key/label.'],
            ],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $catalog = resolve(KeyResultMetricRegistry::class)->catalog();

            $module = isset($arguments['module']) ? strtolower(trim((string) $arguments['module'])) : null;
            $binding = isset($arguments['binding']) ? strtolower(trim((string) $arguments['binding'])) : null;
            $search = isset($arguments['search']) ? strtolower(trim((string) $arguments['search'])) : null;

            $metrics = array_values(array_filter($catalog, function (array $def) use ($module, $binding, $search) {
                if ($module !== null && strtolower((string) ($def['module'] ?? '')) !== $module) {
                    return false;
                }
                if ($binding !== null && strtolower((string) ($def['binding'] ?? '')) !== $binding) {
                    return false;
                }
                if ($search !== null) {
                    $hay = strtolower(($def['metric_key'] ?? '') . ' ' . ($def['label'] ?? ''));
                    if (! str_contains($hay, $search)) {
                        return false;
                    }
                }

                return true;
            }));

            return ToolResult::success([
                'metrics' => $metrics,
                'count' => count($metrics),
                'message' => count($metrics) . ' KR-Metrik(en) verfügbar.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Metrik-Katalogs: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['okr', 'key_results', 'metrics', 'discovery'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
