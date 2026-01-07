<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\StrategicDocument;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class UpdateStrategicDocumentTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.strategic_documents.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /okr/strategic-documents/{id} - Aktualisiert ein strategisches Dokument. Wenn title/content geändert werden, wird eine neue Version erstellt (UI-Parität).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Document-ID (required).'],
                'title' => ['type' => 'string'],
                'content' => ['type' => 'string'],
                'valid_from' => ['type' => 'string', 'description' => 'Optional: Y-m-d (für neue Version oder Metadaten).'],
                'change_note' => ['type' => 'string'],
                'is_active' => ['type' => 'boolean', 'description' => 'Optional: Aktivieren/Deaktivieren. (Aktivieren deaktiviert andere Versionen gleichen Typs im Team.)'],
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

            $doc = StrategicDocument::query()->where('team_id', $teamId)->find($id);
            if (!$doc) {
                return ToolResult::error('NOT_FOUND', "StrategicDocument {$id} nicht gefunden (Team-ID: {$teamId}).");
            }

            $newTitle = array_key_exists('title', $arguments) ? $arguments['title'] : $doc->title;
            $newContent = array_key_exists('content', $arguments) ? $arguments['content'] : $doc->content;
            $validFrom = array_key_exists('valid_from', $arguments) ? $arguments['valid_from'] : null;
            $validFrom = is_string($validFrom) && trim($validFrom) !== '' ? trim($validFrom) : now()->toDateString();
            $changeNote = $arguments['change_note'] ?? null;

            $createdNewVersion = false;
            $resultDoc = $doc;

            // UI-Parität: Wenn title/content geändert wurden, neue Version erzeugen
            if (
                (array_key_exists('title', $arguments) && $doc->title !== $newTitle) ||
                (array_key_exists('content', $arguments) && $doc->content !== $newContent)
            ) {
                $resultDoc = $doc->createNewVersion([
                    'title' => is_string($newTitle) ? trim($newTitle) : $doc->title,
                    'content' => is_string($newContent) ? $newContent : $doc->content,
                    'valid_from' => $validFrom,
                    'change_note' => $changeNote,
                ]);
                $createdNewVersion = true;
            } else {
                $dirty = false;
                if (array_key_exists('change_note', $arguments)) {
                    $doc->change_note = $changeNote;
                    $dirty = true;
                }
                if (array_key_exists('is_active', $arguments)) {
                    $doc->is_active = (bool)$arguments['is_active'];
                    $dirty = true;
                }
                if (array_key_exists('valid_from', $arguments)) {
                    $doc->valid_from = $validFrom;
                    $dirty = true;
                }
                if ($dirty) {
                    $doc->save();
                }
            }

            return ToolResult::success([
                'id' => $resultDoc->id,
                'uuid' => $resultDoc->uuid,
                'type' => $resultDoc->type,
                'title' => $resultDoc->title,
                'version' => $resultDoc->version,
                'is_active' => (bool)$resultDoc->is_active,
                'valid_from' => $this->dateToYmd($resultDoc->valid_from),
                'created_new_version' => $createdNewVersion,
                'message' => $createdNewVersion ? 'Neue Version erfolgreich erstellt.' : 'Strategisches Dokument erfolgreich aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des strategischen Dokuments: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'strategic_documents', 'mission', 'vision', 'regnose', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}


