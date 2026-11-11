<?php

namespace Platform\Okr\Services;

use Illuminate\Support\Facades\DB;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Models\KeyResultContext;

class StoreKeyResultContext
{
    public function __construct(
        protected KeyResultContextResolver $resolver
    ) {
    }

    /**
     * Verknüpft einen KeyResult mit einem Kontext (z.B. Task, Project) mit automatischer Kontext-Kaskade.
     * Ein KeyResult kann mit MEHREREN verschiedenen Kontexten verknüpft sein.
     * Jeder Kontext hat seinen eigenen Root-Kontext in der KeyResultContext Tabelle.
     *
     * @param int $keyResultId KeyResult-ID
     * @param string $contextType Kontext-Typ (z.B. 'Platform\Planner\Models\PlannerTask')
     * @param int $contextId Kontext-ID
     * @return KeyResultContext
     */
    public function store(int $keyResultId, string $contextType, int $contextId): KeyResultContext
    {
        return DB::transaction(function () use ($keyResultId, $contextType, $contextId) {
            // 1. Prüfe ob KeyResult existiert
            $keyResult = KeyResult::findOrFail($keyResultId);

            // 2. Vorfahren-Kontexte auflösen (vor dem Primärkontext, um zu wissen ob dieser Root ist)
            $ancestors = $this->resolver->resolveAncestors($contextType, $contextId);
            $firstRoot = null;
            
            // Prüfe ob Ancestors vorhanden sind
            $hasAncestors = !empty($ancestors);
            
            // Wenn keine Ancestors vorhanden sind, ist der primäre Kontext selbst der Root
            $isPrimaryRoot = !$hasAncestors;

            // 3. Root-Kontext bestimmen
            if ($hasAncestors) {
                // Suche ersten Root-Kontext in Ancestors
                foreach ($ancestors as $ancestor) {
                    if (($ancestor['is_root'] ?? false)) {
                        $firstRoot = [
                            'type' => $ancestor['type'],
                            'id' => $ancestor['id'],
                        ];
                        break;
                    }
                }
            } else {
                // Keine Ancestors = primärer Kontext ist selbst der Root
                $firstRoot = [
                    'type' => $contextType,
                    'id' => $contextId,
                ];
            }

            // 4. Primärkontext in Contexts-Tabelle anlegen (depth=0, is_primary=true)
            // Jeder Kontext hat seinen eigenen root_context_type/root_context_id
            $primaryLabel = $this->resolver->resolveLabel($contextType, $contextId);
            $primaryContext = KeyResultContext::updateOrCreate(
                [
                    'key_result_id' => $keyResultId,
                    'context_type' => $contextType,
                    'context_id' => $contextId,
                ],
                [
                    'depth' => 0,
                    'is_primary' => true,
                    'is_root' => $isPrimaryRoot,
                    'root_context_type' => $firstRoot['type'],
                    'root_context_id' => $firstRoot['id'],
                    'context_label' => $primaryLabel,
                ]
            );

            // 5. Vorfahren-Kontexte auflösen und anlegen
            // Alle Ancestors bekommen den gleichen root_context_type/root_context_id wie der primäre Kontext
            foreach ($ancestors as $depth => $ancestor) {
                $ancestorDepth = $depth + 1;
                $isRoot = $ancestor['is_root'] ?? false;
                $ancestorLabel = $ancestor['label'] ?? $this->resolver->resolveLabel($ancestor['type'], $ancestor['id']);

                // Direkt verknüpfter Vorfahre (z. B. Projekt selbst am KR) bleibt primär
                $alreadyPrimary = KeyResultContext::where('key_result_id', $keyResultId)
                    ->where('context_type', $ancestor['type'])
                    ->where('context_id', $ancestor['id'])
                    ->where('is_primary', true)
                    ->exists();
                if ($alreadyPrimary) {
                    continue;
                }

                KeyResultContext::updateOrCreate(
                    [
                        'key_result_id' => $keyResultId,
                        'context_type' => $ancestor['type'],
                        'context_id' => $ancestor['id'],
                    ],
                    [
                        'depth' => $ancestorDepth,
                        'is_primary' => false,
                        'is_root' => $isRoot,
                        'root_context_type' => $firstRoot['type'],
                        'root_context_id' => $firstRoot['id'],
                        'context_label' => $ancestorLabel,
                    ]
                );
            }

            return $primaryContext->fresh();
        });
    }

    /**
     * Entfernt eine Verknüpfung zwischen KeyResult und Kontext.
     *
     * Vorfahren-Zeilen (z. B. das Projekt einer Aufgabe) fallen mit weg, sobald
     * kein anderer primärer Kontext des KR mehr an ihnen hängt. Hängen am gelösten
     * Kontext selbst noch Kinder (Projekt gelöst, Aufgaben bleiben), wird er zur
     * Vorfahren-Zeile herabgestuft statt gelöscht.
     */
    public function detach(int $keyResultId, string $contextType, int $contextId): bool
    {
        return DB::transaction(function () use ($keyResultId, $contextType, $contextId) {
            $row = KeyResultContext::where('key_result_id', $keyResultId)
                ->where('context_type', $contextType)
                ->where('context_id', $contextId)
                ->where('is_primary', true)
                ->first();

            if (! $row) {
                return false;
            }

            $hasChildren = $this->primaryRowsUnder($keyResultId, $contextType, $contextId, $row->id)->exists();
            if ($hasChildren) {
                $row->update(['is_primary' => false, 'depth' => 1]);
            } else {
                $row->delete();
            }

            if ($row->root_context_type && ! ($row->root_context_type === $contextType && (int) $row->root_context_id === $contextId)) {
                $rootStillUsed = $this->primaryRowsUnder($keyResultId, $row->root_context_type, (int) $row->root_context_id)->exists();
                if (! $rootStillUsed) {
                    KeyResultContext::where('key_result_id', $keyResultId)
                        ->where('context_type', $row->root_context_type)
                        ->where('context_id', $row->root_context_id)
                        ->where('is_primary', false)
                        ->delete();
                }
            }

            return true;
        });
    }

    /** Primäre Zeilen des KR, deren Root der angegebene Kontext ist (ohne ihn selbst). */
    protected function primaryRowsUnder(int $keyResultId, string $type, int $id, ?int $exceptId = null)
    {
        return KeyResultContext::where('key_result_id', $keyResultId)
            ->where('is_primary', true)
            ->where('root_context_type', $type)
            ->where('root_context_id', $id)
            ->where(fn ($q) => $q->where('context_type', '!=', $type)->orWhere('context_id', '!=', $id))
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId));
    }
}
