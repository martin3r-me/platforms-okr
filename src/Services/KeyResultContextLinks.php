<?php

namespace Platform\Okr\Services;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Platform\Core\Contracts\HasKeyResultAncestors;
use Platform\Core\Models\Module;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Models\KeyResultContext;

/**
 * Eine Stelle für KR ↔ Kontext (Aufgabe, Projekt …): Panel-Reiter und MCP-Tools
 * lesen und schreiben hierüber. Kontext-Typ ist immer der Klassenname — so steht
 * er in okr_key_result_contexts; Morph-Aliase werden aufgelöst.
 */
class KeyResultContextLinks
{
    public function __construct(protected StoreKeyResultContext $store) {}

    /** Klassenname zu Alias/Klasse ("planner_task" → PlannerTask::class), null wenn unbekannt. */
    public static function resolveType(string $type): ?string
    {
        if (class_exists($type)) {
            return $type;
        }

        $class = Relation::getMorphedModel($type);

        return $class && class_exists($class) ? $class : null;
    }

    /** OKR ist root-scoped: KRs liegen im Root-Team. */
    public function okrTeamIdFor($user): ?int
    {
        $team = $user?->currentTeamRelation;
        if (! $team) {
            return null;
        }

        $module = Module::where('key', 'okr')->first();
        if ($module && method_exists($module, 'isRootScoped') && $module->isRootScoped() && method_exists($team, 'getRootTeam')) {
            return $team->getRootTeam()?->id ?? $team->id;
        }

        return $team->id;
    }

    /** Direkt (primär) mit dem Kontext verknüpfte KRs. */
    public function linkedTo(string $contextType, int $contextId): Collection
    {
        return KeyResultContext::where('context_type', $contextType)
            ->where('context_id', $contextId)
            ->where('is_primary', true)
            ->with(['keyResult.objective.cycle.template', 'keyResult.performance'])
            ->get()
            ->pluck('keyResult')
            ->filter();
    }

    /**
     * Über Vorfahren abgedeckte KRs (Aufgabe → Projekt): [['key_result' => KR, 'via' => Label]].
     */
    public function coveredFor(string $contextType, int $contextId): Collection
    {
        if (! class_exists($contextType)) {
            return collect();
        }

        $model = $contextType::find($contextId);
        if (! $model instanceof HasKeyResultAncestors) {
            return collect();
        }

        $rows = collect();
        foreach ($model->keyResultAncestors() as $ancestor) {
            if (! ($ancestor['is_root'] ?? false)) {
                continue;
            }
            KeyResultContext::where('context_type', $ancestor['type'])
                ->where('context_id', $ancestor['id'])
                ->where('is_primary', true)
                ->with(['keyResult.objective.cycle.template', 'keyResult.performance'])
                ->get()
                ->each(function ($ctx) use ($rows, $ancestor) {
                    if ($ctx->keyResult) {
                        $rows->push(['key_result' => $ctx->keyResult, 'via' => $ancestor['label'] ?? $ctx->context_label]);
                    }
                });
        }

        return $rows->unique(fn ($r) => $r['key_result']->id)->values();
    }

    /** KRs zum Verknüpfen — laufende Zyklen zuerst, Suche über KR- und Objective-Titel. */
    public function candidates(int $teamId, string $search, array $excludeIds = [], int $limit = 25): Collection
    {
        return KeyResult::query()
            ->where('team_id', $teamId)
            ->when($excludeIds, fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('title', 'like', "%{$search}%")
                        ->orWhereHas('objective', fn ($o) => $o->where('title', 'like', "%{$search}%"));
                });
            }, function ($q) {
                // Ohne Suche nur laufende Zyklen — abgeschlossene findet man per Suche
                $q->whereHas('objective.cycle', fn ($c) => $c->whereIn('status', ['current', 'ending_soon', 'future', 'draft']));
            })
            ->with(['objective.cycle.template', 'performance'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function link(KeyResult $kr, string $contextType, int $contextId): KeyResultContext
    {
        return $this->store->store($kr->id, $contextType, $contextId);
    }

    public function unlink(KeyResult $kr, string $contextType, int $contextId): bool
    {
        return $this->store->detach($kr->id, $contextType, $contextId);
    }

    /** Anzahl direkter Verknüpfungen eines Kontexts (Reiter-Badge). */
    public function countFor(string $contextType, int $contextId): int
    {
        return KeyResultContext::where('context_type', $contextType)
            ->where('context_id', $contextId)
            ->where('is_primary', true)
            ->count();
    }
}
