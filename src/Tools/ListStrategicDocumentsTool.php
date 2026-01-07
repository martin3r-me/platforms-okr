<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Okr\Models\StrategicDocument;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class ListStrategicDocumentsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.strategic_documents.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/strategic-documents?type=mission|vision|regnose&is_active=true|false - Listet strategische Dokumente (Mission/Vision/Regnose) im OKR-Root-Team.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'type' => [
                        'type' => 'string',
                        'enum' => ['mission', 'vision', 'regnose'],
                        'description' => 'Optional: Typ-Filter.',
                    ],
                    'is_active' => [
                        'type' => 'boolean',
                        'description' => 'Optional: Aktiv-Filter (true => nur aktive Version).',
                    ],
                    'include_content' => [
                        'type' => 'boolean',
                        'description' => 'Optional: content mitsenden (Default true).',
                    ],
                ],
                'required' => [],
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $teamId = $this->resolveOkrTeamId($context);
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden (OKR ist root-scoped).');
            }

            $type = $arguments['type'] ?? null;
            if ($type !== null && (!is_string($type) || !in_array($type, ['mission', 'vision', 'regnose'], true))) {
                return ToolResult::error('VALIDATION_ERROR', 'type muss mission|vision|regnose sein.');
            }

            $includeContent = (bool)($arguments['include_content'] ?? true);

            $query = StrategicDocument::query()->where('team_id', $teamId);
            if (is_string($type) && $type !== '') {
                $query->where('type', $type);
            }
            if (array_key_exists('is_active', $arguments)) {
                $query->where('is_active', (bool)$arguments['is_active']);
            }

            $this->applyStandardFilters($query, $arguments, [
                'type', 'is_active', 'version', 'valid_from', 'created_by', 'team_id', 'created_at', 'updated_at',
            ]);
            $this->applyStandardSearch($query, $arguments, ['title', 'content', 'change_note']);
            $this->applyStandardSort($query, $arguments, ['type', 'version', 'valid_from', 'created_at', 'updated_at'], 'version', 'desc');
            $this->applyStandardPagination($query, $arguments);

            $docs = $query->get();
            $items = $docs->map(function (StrategicDocument $d) use ($includeContent) {
                return [
                    'id' => $d->id,
                    'uuid' => $d->uuid,
                    'type' => $d->type,
                    'title' => $d->title,
                    'content' => $includeContent ? $d->content : null,
                    'version' => $d->version,
                    'is_active' => (bool)$d->is_active,
                    'valid_from' => $this->dateToYmd($d->valid_from),
                    'change_note' => $d->change_note,
                    'team_id' => $d->team_id,
                    'created_by' => $d->created_by,
                    'created_at' => $d->created_at?->toISOString(),
                    'updated_at' => $d->updated_at?->toISOString(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'team_id' => $teamId,
                'strategic_documents' => $items,
                'count' => count($items),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der strategischen Dokumente: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['okr', 'strategic_documents', 'mission', 'vision', 'regnose', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}


