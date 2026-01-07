<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\StrategicDocument;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class CreateStrategicDocumentTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.strategic_documents.POST';
    }

    public function getDescription(): string
    {
        return 'POST /okr/strategic-documents - Erstellt ein strategisches Dokument (Mission/Vision/Regnose). Versionierung erfolgt automatisch pro Typ.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'type' => [
                    'type' => 'string',
                    'enum' => ['mission', 'vision', 'regnose'],
                    'description' => 'Typ (required).',
                ],
                'title' => ['type' => 'string', 'description' => 'Titel (required).'],
                'content' => ['type' => 'string', 'description' => 'Optional: Markdown/Rich Text.'],
                'valid_from' => ['type' => 'string', 'description' => 'Optional: Y-m-d (Default heute).'],
                'change_note' => ['type' => 'string', 'description' => 'Optional: Änderungsnotiz.'],
                'is_active' => ['type' => 'boolean', 'description' => 'Optional: Soll diese Version aktiv sein? Default: true.'],
            ],
            'required' => ['type', 'title'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $type = $arguments['type'] ?? null;
            $title = $arguments['title'] ?? null;
            if (!is_string($type) || !in_array($type, ['mission', 'vision', 'regnose'], true)) {
                return ToolResult::error('VALIDATION_ERROR', 'type muss mission|vision|regnose sein.');
            }
            if (!is_string($title) || trim($title) === '') {
                return ToolResult::error('VALIDATION_ERROR', 'title ist erforderlich.');
            }

            $teamId = $this->resolveOkrTeamId($context);
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden (OKR ist root-scoped).');
            }

            $validFrom = $arguments['valid_from'] ?? null;
            $validFrom = is_string($validFrom) && trim($validFrom) !== '' ? trim($validFrom) : now()->toDateString();

            $doc = StrategicDocument::create([
                'type' => $type,
                'title' => trim($title),
                'content' => $arguments['content'] ?? null,
                'valid_from' => $validFrom,
                'change_note' => $arguments['change_note'] ?? null,
                'is_active' => array_key_exists('is_active', $arguments) ? (bool)$arguments['is_active'] : true,
                'team_id' => $teamId,
                'created_by' => $context->user->id,
            ]);

            return ToolResult::success([
                'id' => $doc->id,
                'uuid' => $doc->uuid,
                'type' => $doc->type,
                'title' => $doc->title,
                'version' => $doc->version,
                'is_active' => (bool)$doc->is_active,
                'valid_from' => $this->dateToYmd($doc->valid_from),
                'message' => 'Strategisches Dokument erfolgreich erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des strategischen Dokuments: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'strategic_documents', 'mission', 'vision', 'regnose', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}


