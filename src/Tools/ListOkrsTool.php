<?php

namespace Platform\Okr\Tools;

use Illuminate\Database\Eloquent\Builder;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Okr\Models\Okr;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class ListOkrsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesOkrScope;

    public function getName(): string
    {
        return 'okr.okrs.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/okrs?team_id={id}&filters=[...]&search=...&sort=[...] - Listet OKRs (root-scoped, alle Team-Mitglieder sehen alle OKRs). team_id ist optional und wird i.d.R. aus dem Root-Team des aktuellen Teams abgeleitet. WICHTIG: Um "meine OKRs" (die ich angelegt habe) zu finden, verwende filters=[{"field":"user_id","value":USER_ID}]. Um "OKRs die ich verwalte" zu finden, verwende filters=[{"field":"manager_user_id","value":USER_ID}]. Ohne Filter werden alle OKRs des Teams zurückgegeben.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Root-Team-ID für OKR (scope_type=parent). Wenn nicht angegeben, wird sie aus dem Kontext abgeleitet.',
                    ],
                    'include_templates' => [
                        'type' => 'boolean',
                        'description' => 'Optional: Wenn true, werden auch is_template OKRs gelistet. Default: true.',
                    ],
                    'my_okrs' => [
                        'type' => 'boolean',
                        'description' => 'Optional: Wenn true, werden nur OKRs zurückgegeben, die der aktuelle Benutzer angelegt hat (user_id = aktueller User). Default: false.',
                    ],
                    'managed_okrs' => [
                        'type' => 'boolean',
                        'description' => 'Optional: Wenn true, werden nur OKRs zurückgegeben, die der aktuelle Benutzer verwaltet (manager_user_id = aktueller User). Default: false.',
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

            $teamId = $this->normalizeId($arguments['team_id'] ?? null) ?? $this->resolveOkrTeamId($context);
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden (OKR ist root-scoped).');
            }

            $includeTemplates = (bool)($arguments['include_templates'] ?? true);
            $myOkrs = (bool)($arguments['my_okrs'] ?? false);
            $managedOkrs = (bool)($arguments['managed_okrs'] ?? false);

            $query = Okr::query()
                ->where('team_id', $teamId)
                ->with(['user', 'managerUser']);

            // Filter nach "meine OKRs" und/oder "OKRs die ich verwalte"
            if ($myOkrs || $managedOkrs) {
                $query->where(function ($q) use ($context, $myOkrs, $managedOkrs) {
                    if ($myOkrs && $managedOkrs) {
                        // Beide: OKRs die ich angelegt habe ODER die ich verwalte
                        $q->where('user_id', $context->user->id)
                          ->orWhere('manager_user_id', $context->user->id);
                    } elseif ($myOkrs) {
                        // Nur meine OKRs (die ich angelegt habe)
                        $q->where('user_id', $context->user->id);
                    } elseif ($managedOkrs) {
                        // Nur OKRs die ich verwalte
                        $q->where('manager_user_id', $context->user->id);
                    }
                });
            }

            if (!$includeTemplates) {
                $query->where('is_template', false);
            }

            $this->applyStandardFilters($query, $arguments, [
                'title', 'description', 'is_template', 'auto_transfer', 'performance_score', 'manager_user_id', 'user_id', 'created_at', 'updated_at',
            ]);
            $this->applyStandardSearch($query, $arguments, ['title', 'description']);
            $this->applyStandardSort($query, $arguments, [
                'title', 'performance_score', 'created_at', 'updated_at',
            ], 'updated_at', 'desc');
            $this->applyStandardPagination($query, $arguments);

            $okrs = $query->get();

            $items = $okrs->map(function (Okr $okr) {
                return [
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
                ];
            })->values()->toArray();

            return ToolResult::success([
                'okrs' => $items,
                'count' => count($items),
                'team_id' => $teamId,
                'message' => count($items) . ' OKR(s) gefunden (Team-ID: ' . $teamId . ').',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der OKRs: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['okr', 'okrs', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}


