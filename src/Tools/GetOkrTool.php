<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\Okr;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class GetOkrTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.okr.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/okrs/{id} - Ruft ein OKR ab (inkl. Cycles/Templates optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'OKR-ID (required).'],
                'include_cycles' => ['type' => 'boolean', 'description' => 'Optional: Cycles inkl. Template laden. Default: true.'],
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

            $includeCycles = (bool)($arguments['include_cycles'] ?? true);
            $q = Okr::query()->where('team_id', $teamId)->with(['user', 'managerUser']);
            if ($includeCycles) {
                $q->with(['cycles.template']);
            }

            $okr = $q->find($id);
            if (!$okr) {
                return ToolResult::error('NOT_FOUND', "OKR {$id} nicht gefunden (Team-ID: {$teamId}).");
            }

            $cycles = null;
            if ($includeCycles) {
                $cycles = $okr->cycles->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'uuid' => $c->uuid,
                        'okr_id' => $c->okr_id,
                        'team_id' => $c->team_id,
                        'status' => $c->status,
                        'type' => $c->type,
                        'cycle_template_id' => $c->cycle_template_id,
                        'template' => $c->template ? [
                            'id' => $c->template->id,
                            'label' => $c->template->label,
                            'type' => $c->template->type,
                            'starts_at' => $c->template->starts_at?->toDateString(),
                            'ends_at' => $c->template->ends_at?->toDateString(),
                            'is_current' => (bool)$c->template->is_current,
                        ] : null,
                    ];
                })->values()->toArray();
            }

            return ToolResult::success([
                'okr' => [
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
                    'created_at' => $okr->created_at?->toIso8601String(),
                ],
                'cycles' => $cycles,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des OKR: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['okr', 'okr', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}


