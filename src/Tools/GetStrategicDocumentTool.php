<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\StrategicDocument;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class GetStrategicDocumentTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.strategic_document.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/strategic-documents/{id} - Ruft ein strategisches Dokument (Mission/Vision/Regnose) ab.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Document-ID (required).'],
            ],
            'required' => ['id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $id = $this->normalizeId($arguments['id'] ?? null);
            if (!$id) {
                return ToolResult::error('VALIDATION_ERROR', 'id ist erforderlich.');
            }

            $teamId = $this->resolveOkrTeamId($context);
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden (OKR ist root-scoped).');
            }

            $doc = StrategicDocument::query()
                ->where('team_id', $teamId)
                ->find($id);

            if (!$doc) {
                return ToolResult::error('NOT_FOUND', "StrategicDocument {$id} nicht gefunden (Team-ID: {$teamId}).");
            }

            return ToolResult::success([
                'strategic_document' => [
                    'id' => $doc->id,
                    'uuid' => $doc->uuid,
                    'type' => $doc->type,
                    'title' => $doc->title,
                    'content' => $doc->content,
                    'version' => $doc->version,
                    'is_active' => (bool)$doc->is_active,
                    'valid_from' => $this->dateToYmd($doc->valid_from),
                    'change_note' => $doc->change_note,
                    'team_id' => $doc->team_id,
                    'created_by' => $doc->created_by,
                    'created_at' => $doc->created_at?->toISOString(),
                    'updated_at' => $doc->updated_at?->toISOString(),
                ],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des strategischen Dokuments: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['okr', 'strategic_documents', 'mission', 'vision', 'regnose', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}


