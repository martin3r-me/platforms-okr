<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Models\User;
use Platform\Okr\Models\Objective;
use Platform\Okr\Models\StrategicDocument;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class UpdateObjectiveTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.objectives.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /okr/objectives/{id} - Aktualisiert ein Objective. cycle_id optional nur zur Kontext-Validierung. Hinweis: manager_user_id nur setzen, wenn du den Owner wirklich ändern willst.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Objective-ID (required).'],
                'cycle_id' => ['type' => 'integer', 'description' => 'Optional: Validierungskontext (muss zum Objective passen).'],
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'is_mountain' => ['type' => 'boolean'],
                'order' => ['type' => 'integer'],
                'manager_user_id' => ['type' => 'integer'],
                'vision_id' => ['type' => 'integer', 'description' => 'Optional: StrategicDocument-ID vom Typ vision (0/"" => null).'],
                'regnose_id' => ['type' => 'integer', 'description' => 'Optional: StrategicDocument-ID vom Typ regnose (0/"" => null).'],
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

            $obj = Objective::query()->where('team_id', $teamId)->find($id);
            if (!$obj) {
                return ToolResult::error('NOT_FOUND', "Objective {$id} nicht gefunden (Team-ID: {$teamId}).");
            }

            $cycleId = $this->normalizeId($arguments['cycle_id'] ?? null);
            if ($cycleId && (int)$obj->cycle_id !== (int)$cycleId) {
                return ToolResult::error('CONTEXT_MISMATCH', "Objective {$id} gehört nicht zu cycle_id {$cycleId}.");
            }

            $dirty = false;
            foreach (['title', 'description', 'is_mountain', 'order'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $obj->{$field} = $arguments[$field];
                    $dirty = true;
                }
            }

            // manager_user_id: nur ändern, wenn explizit mitgegeben; 0/'' => null; sonst FK-validieren
            if (array_key_exists('manager_user_id', $arguments)) {
                $mgr = $this->normalizeId($arguments['manager_user_id'] ?? null); // 0 => null
                if ($mgr !== null) {
                    $exists = User::query()->where('id', $mgr)->exists();
                    if (!$exists) {
                        return ToolResult::error('VALIDATION_ERROR', "manager_user_id={$mgr} existiert nicht.");
                    }
                }
                $obj->manager_user_id = $mgr;
                $dirty = true;
            }

            // vision_id/regnose_id: optional setzen/entfernen (UI-Parität)
            if (array_key_exists('vision_id', $arguments)) {
                $visionId = $this->normalizeId($arguments['vision_id'] ?? null);
                if ($visionId !== null) {
                    $ok = StrategicDocument::query()
                        ->where('team_id', $teamId)
                        ->where('type', 'vision')
                        ->where('id', $visionId)
                        ->exists();
                    if (!$ok) {
                        return ToolResult::error('VALIDATION_ERROR', "vision_id={$visionId} ist ungültig (muss existieren, Typ=vision, Team-ID={$teamId}).");
                    }
                }
                $obj->vision_id = $visionId;
                $dirty = true;
            }

            if (array_key_exists('regnose_id', $arguments)) {
                $regnoseId = $this->normalizeId($arguments['regnose_id'] ?? null);
                if ($regnoseId !== null) {
                    $ok = StrategicDocument::query()
                        ->where('team_id', $teamId)
                        ->where('type', 'regnose')
                        ->where('id', $regnoseId)
                        ->exists();
                    if (!$ok) {
                        return ToolResult::error('VALIDATION_ERROR', "regnose_id={$regnoseId} ist ungültig (muss existieren, Typ=regnose, Team-ID={$teamId}).");
                    }
                }
                $obj->regnose_id = $regnoseId;
                $dirty = true;
            }
            if ($dirty) {
                $obj->save();
            }

            return ToolResult::success([
                'id' => $obj->id,
                'uuid' => $obj->uuid,
                'cycle_id' => $obj->cycle_id,
                'title' => $obj->title,
                'order' => $obj->order,
                'message' => 'Objective erfolgreich aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Objectives: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'objectives', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}


