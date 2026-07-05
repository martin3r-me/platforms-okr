<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Models\User;
use Platform\Okr\Models\Okr;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class CreateOkrTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.okrs.POST';
    }

    public function getDescription(): string
    {
        return 'POST /okr/okrs - Erstellt einen OKR-Container (Zielsteuerung), z.B. pro Venture oder Service. OKRs sind root-scoped. team_id wird aus dem Kontext abgeleitet, owner_user_id defaultet auf den aktuellen User. Cycles/Objectives/KeyResults werden anschließend über die jeweiligen POST-Tools angelegt.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string', 'description' => 'Titel des OKR-Containers (required), z.B. "BANKETT.DIGITAL".'],
                'description' => ['type' => 'string', 'description' => 'Optional: Beschreibung.'],
                'owner_user_id' => ['type' => 'integer', 'description' => 'Optional: Owner-User-ID. Default: aktueller User.'],
                'manager_user_id' => ['type' => 'integer', 'description' => 'Optional: Verantwortlicher Manager (User-ID).'],
                'auto_transfer' => ['type' => 'boolean', 'description' => 'Optional: Offene Ziele automatisch in den nächsten Zyklus übertragen. Default: false.'],
                'is_template' => ['type' => 'boolean', 'description' => 'Optional: Als Template anlegen. Default: false.'],
            ],
            'required' => ['title'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $title = is_string($arguments['title'] ?? null) ? trim($arguments['title']) : '';
            if ($title === '') {
                return ToolResult::error('VALIDATION_ERROR', 'title ist erforderlich.');
            }

            $teamId = $this->resolveOkrTeamId($context);
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden (OKR ist root-scoped).');
            }

            $ownerUserId = $this->normalizeId($arguments['owner_user_id'] ?? null) ?? $context->user->id;
            if ($ownerUserId && !User::query()->whereKey($ownerUserId)->exists()) {
                return ToolResult::error('NOT_FOUND', "owner_user_id {$ownerUserId} nicht gefunden.");
            }

            $managerUserId = $this->normalizeId($arguments['manager_user_id'] ?? null);
            if ($managerUserId && !User::query()->whereKey($managerUserId)->exists()) {
                return ToolResult::error('NOT_FOUND', "manager_user_id {$managerUserId} nicht gefunden.");
            }

            $okr = Okr::create([
                'title' => $title,
                'description' => $arguments['description'] ?? null,
                'team_id' => $teamId,
                'user_id' => $ownerUserId,
                'manager_user_id' => $managerUserId,
                'auto_transfer' => (bool)($arguments['auto_transfer'] ?? false),
                'is_template' => (bool)($arguments['is_template'] ?? false),
            ]);

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
                'message' => 'OKR-Container erfolgreich erstellt. Nächster Schritt: Cycle via okr.cycles.POST anlegen.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des OKR: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'okrs', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
