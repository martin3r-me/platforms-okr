<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Models\User;
use Platform\Okr\Models\Okr;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class UpdateOkrTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.okrs.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /okr/okrs/{id} - Aktualisiert einen OKR-Container (Zielsteuerung): title, description, owner_user_id, manager_user_id, auto_transfer, is_template. OKRs sind root-scoped. Nur übergebene Felder werden geändert.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'OKR-ID (required).'],
                'title' => ['type' => 'string', 'description' => 'Optional: neuer Titel.'],
                'description' => ['type' => 'string', 'description' => 'Optional: neue Beschreibung.'],
                'owner_user_id' => ['type' => 'integer', 'description' => 'Optional: neuer Owner (User-ID).'],
                'manager_user_id' => ['type' => 'integer', 'description' => 'Optional: neuer Manager (User-ID). null/0 entfernt den Manager.'],
                'auto_transfer' => ['type' => 'boolean', 'description' => 'Optional: offene Ziele automatisch in den nächsten Zyklus übertragen.'],
                'is_template' => ['type' => 'boolean', 'description' => 'Optional: Template-Flag.'],
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

            $okr = Okr::query()->where('team_id', $teamId)->find($id);
            if (!$okr) {
                return ToolResult::error('NOT_FOUND', "OKR {$id} nicht gefunden (Team-ID: {$teamId}).");
            }

            $dirty = false;

            if (array_key_exists('title', $arguments)) {
                $title = is_string($arguments['title']) ? trim($arguments['title']) : '';
                if ($title === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'title darf nicht leer sein.');
                }
                $okr->title = $title;
                $dirty = true;
            }

            if (array_key_exists('description', $arguments)) {
                $okr->description = $arguments['description'];
                $dirty = true;
            }

            if (array_key_exists('owner_user_id', $arguments)) {
                $ownerUserId = $this->normalizeId($arguments['owner_user_id']);
                if (!$ownerUserId) {
                    return ToolResult::error('VALIDATION_ERROR', 'owner_user_id muss eine gültige User-ID sein.');
                }
                if (!User::query()->whereKey($ownerUserId)->exists()) {
                    return ToolResult::error('NOT_FOUND', "owner_user_id {$ownerUserId} nicht gefunden.");
                }
                $okr->user_id = $ownerUserId;
                $dirty = true;
            }

            if (array_key_exists('manager_user_id', $arguments)) {
                $managerUserId = $this->normalizeId($arguments['manager_user_id']);
                if ($managerUserId && !User::query()->whereKey($managerUserId)->exists()) {
                    return ToolResult::error('NOT_FOUND', "manager_user_id {$managerUserId} nicht gefunden.");
                }
                $okr->manager_user_id = $managerUserId; // null entfernt den Manager
                $dirty = true;
            }

            if (array_key_exists('auto_transfer', $arguments)) {
                $okr->auto_transfer = (bool)$arguments['auto_transfer'];
                $dirty = true;
            }

            if (array_key_exists('is_template', $arguments)) {
                $okr->is_template = (bool)$arguments['is_template'];
                $dirty = true;
            }

            if ($dirty) {
                $okr->save();
            }

            $okr->load('managerUser');

            return ToolResult::success([
                'id' => $okr->id,
                'uuid' => $okr->uuid,
                'title' => $okr->title,
                'description' => $okr->description,
                'team_id' => $okr->team_id,
                'owner_user_id' => $okr->user_id,
                'manager_user_id' => $okr->manager_user_id,
                'manager_name' => $okr->managerUser?->name,
                'is_template' => (bool)$okr->is_template,
                'auto_transfer' => (bool)$okr->auto_transfer,
                'performance_score' => $okr->performance_score,
                'message' => 'OKR-Container erfolgreich aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des OKR: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'okrs', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
