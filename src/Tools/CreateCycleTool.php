<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Models\CycleTemplate;
use Platform\Okr\Models\Okr;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class CreateCycleTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.cycles.POST';
    }

    public function getDescription(): string
    {
        return 'POST /okr/cycles - Erstellt einen Cycle für ein OKR (okr_id + cycle_template_id). Cycles sind root-scoped.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'okr_id' => ['type' => 'integer', 'description' => 'OKR-ID (required).'],
                'cycle_template_id' => ['type' => 'integer', 'description' => 'CycleTemplate-ID (required).'],
                'status' => [
                    'type' => 'string',
                    'description' => 'Optional: Status (default draft).',
                    'enum' => ['draft', 'active', 'ending_soon', 'completed', 'past'],
                ],
                'notes' => ['type' => 'string'],
                'description' => ['type' => 'string'],
            ],
            'required' => ['okr_id', 'cycle_template_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $okrId = $this->normalizeId($arguments['okr_id'] ?? null);
            $tplId = $this->normalizeId($arguments['cycle_template_id'] ?? null);
            if (!$okrId || !$tplId) {
                return ToolResult::error('VALIDATION_ERROR', 'okr_id und cycle_template_id sind erforderlich.');
            }

            $teamId = $this->resolveOkrTeamId($context);
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden (OKR ist root-scoped).');
            }

            $okr = Okr::query()->where('team_id', $teamId)->find($okrId);
            if (!$okr) {
                return ToolResult::error('NOT_FOUND', "OKR {$okrId} nicht gefunden (Team-ID: {$teamId}).");
            }

            $tpl = CycleTemplate::find($tplId);
            if (!$tpl) {
                return ToolResult::error('NOT_FOUND', "CycleTemplate {$tplId} nicht gefunden.");
            }

            $status = $arguments['status'] ?? 'draft';
            if (!is_string($status) || $status === '') $status = 'draft';

            $cycle = Cycle::create([
                'okr_id' => $okr->id,
                'cycle_template_id' => $tpl->id,
                'team_id' => $teamId,
                'user_id' => $context->user->id,
                'status' => $status,
                'notes' => $arguments['notes'] ?? null,
                'description' => $arguments['description'] ?? null,
            ]);

            // Template-Beziehung laden für type
            $cycle->load('template');

            return ToolResult::success([
                'id' => $cycle->id,
                'uuid' => $cycle->uuid,
                'okr_id' => $cycle->okr_id,
                'team_id' => $cycle->team_id,
                'cycle_template_id' => $cycle->cycle_template_id,
                'type' => $cycle->template?->type,
                'status' => $cycle->status,
                'message' => 'Cycle erfolgreich erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Cycles: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'mutate',
            'tags' => ['okr', 'cycles', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}


