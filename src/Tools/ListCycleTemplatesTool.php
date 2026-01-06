<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Okr\Models\CycleTemplate;

class ListCycleTemplatesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'okr.cycle_templates.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/cycle-templates - Listet CycleTemplates (Perioden) inkl. is_current Markierung.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'type' => [
                        'type' => 'string',
                        'description' => 'Optional: Filter nach type (z.B. quarter, annual, monthly).',
                    ],
                    'is_current' => [
                        'type' => 'boolean',
                        'description' => 'Optional: Nur aktuelle Templates (is_current=true).',
                    ],
                    'is_standard' => [
                        'type' => 'boolean',
                        'description' => 'Optional: Nur Standard-Templates (is_standard=true).',
                    ],
                ],
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $query = CycleTemplate::query();

            if (isset($arguments['type']) && is_string($arguments['type']) && $arguments['type'] !== '') {
                $query->where('type', $arguments['type']);
            }
            if (array_key_exists('is_current', $arguments)) {
                $query->where('is_current', (bool)$arguments['is_current']);
            }
            if (array_key_exists('is_standard', $arguments)) {
                $query->where('is_standard', (bool)$arguments['is_standard']);
            }

            $this->applyStandardSearch($query, $arguments, ['label', 'type']);
            $this->applyStandardSort($query, $arguments, ['sort_index', 'starts_at', 'ends_at', 'type', 'label'], 'sort_index', 'asc');
            $this->applyStandardPagination($query, $arguments);

            $templates = $query->get();
            $items = $templates->map(function (CycleTemplate $t) {
                return [
                    'id' => $t->id,
                    'uuid' => $t->uuid,
                    'label' => $t->label,
                    'type' => $t->type,
                    'starts_at' => ($t->starts_at instanceof \DateTimeInterface) ? $t->starts_at->format('Y-m-d') : (is_string($t->starts_at) ? $t->starts_at : null),
                    'ends_at' => ($t->ends_at instanceof \DateTimeInterface) ? $t->ends_at->format('Y-m-d') : (is_string($t->ends_at) ? $t->ends_at : null),
                    'sort_index' => $t->sort_index,
                    'is_standard' => (bool)$t->is_standard,
                    'is_current' => (bool)$t->is_current,
                ];
            })->values()->toArray();

            return ToolResult::success([
                'cycle_templates' => $items,
                'count' => count($items),
                'message' => count($items) . ' CycleTemplate(s) gefunden.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der CycleTemplates: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['okr', 'cycle_templates', 'templates', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}


