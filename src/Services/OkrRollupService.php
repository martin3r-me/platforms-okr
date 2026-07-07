<?php

namespace Platform\Okr\Services;

use Illuminate\Support\Carbon;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Models\CyclePerformance;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Models\Objective;
use Platform\Okr\Models\ObjectivePerformance;
use Platform\Okr\Models\Okr;
use Platform\Okr\Models\OkrPerformance;

/**
 * Rollt Zielerreichung von unten nach oben: KR → Objective → Cycle → OKR.
 *
 * EINE Aggregations-Semantik über alle Ebenen (dieselbe wie am Measure im
 * KeyResultEvaluationService, nur eine Etage höher):
 *   progress = Σ(gewicht · score) / Σgewicht   über die score-Rollen
 *   gate-Elemente blocken Completion, verdünnen den Score aber NICHT
 *   info-Elemente zählen gar nicht
 *   completed = progress ≥ 1.0 UND alle Gates erreicht
 *
 * Der KR-Score ist kanonisch: measure-getriebene KRs liefern den Engine-Score
 * (der Gates/Caps bereits berücksichtigt), manuelle KRs eine Gap-Closure aus
 * ihrer Performance-Historie. So gibt es genau EINE Wahrheit pro Ebene.
 *
 * Interne Rechnung in [0,1]. Model-Caches (performance_score) werden in [0,1]
 * geschrieben; die *_performances-Snapshots in 0–100 (bestehende Spalten-Semantik).
 */
class OkrRollupService
{
    /** "erreicht" ab voller Zielerreichung. */
    public float $completionThreshold = 1.0;

    // ── KR-Ebene: kanonischer Score ───────────────────────────────

    /**
     * Zielerreichung eines KR in [0,1] oder null (nichts messbar).
     * Priorität: gesetzter performance_score (Engine/Measure/Counter) → sonst
     * Gap-Closure aus target/current der jüngsten Performance (manuelle KRs).
     */
    public function keyResultScore(KeyResult $keyResult): ?float
    {
        $latest = $this->latestPerformance($keyResult);
        if (! $latest) {
            return null;
        }

        if ($latest->performance_score !== null) {
            return $this->clamp((float) $latest->performance_score);
        }

        return $this->manualScore($keyResult, $latest);
    }

    /**
     * Ist der KR erreicht? Measure-getriebene KRs vertrauen dem Engine-Flag
     * (kennt Gates/Caps); manuelle KRs leiten es aus dem Score ab.
     */
    public function keyResultCompleted(KeyResult $keyResult): bool
    {
        $latest = $this->latestPerformance($keyResult);
        if (! $latest) {
            return false;
        }

        if ($this->isMeasureDriven($keyResult)) {
            return (bool) $latest->is_completed;
        }

        $score = $this->keyResultScore($keyResult);

        return $score !== null && $score >= $this->completionThreshold;
    }

    /** Gap-Closure für manuelle KRs: Startwert = erste Performance. */
    protected function manualScore(KeyResult $keyResult, $latest): ?float
    {
        if ($latest->type === 'boolean') {
            return $latest->is_completed ? 1.0 : 0.0;
        }

        $target = $latest->target_value !== null ? (float) $latest->target_value : null;
        $current = $latest->current_value !== null ? (float) $latest->current_value : null;
        if ($target === null || $current === null) {
            return null;
        }

        $first = $this->firstPerformance($keyResult);
        $baseline = $first && $first->current_value !== null ? (float) $first->current_value : 0.0;

        $denom = $target - $baseline;
        if (abs($denom) < 1e-9) {
            return $current >= $target ? 1.0 : 0.0;
        }

        // Richtung implizit über die Ordnung target vs. baseline.
        return $this->clamp(($current - $baseline) / $denom);
    }

    // ── Objective-Ebene ───────────────────────────────────────────

    /**
     * Bewertet ein Objective aus seinen KRs und schreibt Cache + Snapshot.
     *
     * @return array{progress: float|null, completed: bool, total: int, completed_count: int}
     */
    public function evaluateObjective(Objective $objective, ?Carbon $date = null): array
    {
        $date ??= today();
        $keyResults = $objective->relationLoaded('keyResults')
            ? $objective->keyResults
            : $objective->keyResults()->get();

        $agg = $this->aggregate($keyResults, fn (KeyResult $kr) => [
            'score' => $this->keyResultScore($kr),
            'completed' => $this->keyResultCompleted($kr),
            'weight' => max(0.0, (float) $kr->weight),
            'role' => $kr->role ?: KeyResult::ROLE_SCORE,
        ]);

        // Nichts Messbares (0 KRs) → bestehenden, evtl. manuellen Wert nicht auf 0 überschreiben.
        if ($agg['total'] === 0) {
            return $agg;
        }

        $this->writeCache($objective, $agg['progress']);

        ObjectivePerformance::updateOrCreate(
            ['objective_id' => $objective->id, 'performance_date' => $date],
            [
                'team_id' => $objective->team_id ?? $objective->cycle?->team_id,
                'user_id' => $this->ownerId($objective),
                'performance_score' => $this->toPercent($agg['progress']),
                'completion_percentage' => $this->completionPercent($agg),
                'completed_key_results' => $agg['completed_count'],
                'total_key_results' => $agg['total'],
                'average_progress' => $this->toPercent($agg['progress']),
                'is_completed' => $agg['completed'],
                'completed_at' => $agg['completed'] ? now() : null,
            ]
        );

        return $agg;
    }

    // ── Cycle-Ebene ───────────────────────────────────────────────

    /**
     * Bewertet einen Cycle. Kaskadiert in die Objectives (schreibt deren
     * Snapshots gleich mit) und aggregiert deren Ergebnisse gewichtet.
     */
    public function evaluateCycle(Cycle $cycle, ?Carbon $date = null): array
    {
        $date ??= today();
        $objectives = $cycle->relationLoaded('objectives')
            ? $cycle->objectives
            : $cycle->objectives()->get();

        $results = [];
        $krTotal = 0;
        $krCompleted = 0;
        foreach ($objectives as $objective) {
            $objective->setRelation('cycle', $cycle); // Owner-Auflösung ohne Extra-Query
            $r = $this->evaluateObjective($objective, $date);
            $results[] = ['obj' => $objective, 'r' => $r];
            $krTotal += $r['total'];
            $krCompleted += $r['completed_count'];
        }

        $agg = $this->aggregateResults($results);

        // Cycle ohne Objectives → keinen 0-Snapshot schreiben (wie bisher).
        if ($agg['total'] === 0) {
            return $agg + ['kr_total' => $krTotal, 'kr_completed' => $krCompleted];
        }

        $this->writeCache($cycle, $agg['progress']);

        CyclePerformance::updateOrCreate(
            ['cycle_id' => $cycle->id, 'performance_date' => $date],
            [
                'team_id' => $cycle->team_id,
                'user_id' => $this->ownerId($cycle),
                'performance_score' => $this->toPercent($agg['progress']),
                'completion_percentage' => $this->completionPercent($agg),
                'completed_objectives' => $agg['completed_count'],
                'total_objectives' => $agg['total'],
                'completed_key_results' => $krCompleted,
                'total_key_results' => $krTotal,
                'average_objective_progress' => $this->toPercent($agg['progress']),
                'average_key_result_progress' => $krTotal > 0 ? round($krCompleted / $krTotal * 100, 2) : 0,
                'is_completed' => $agg['completed'],
                'completed_at' => $agg['completed'] ? now() : null,
            ]
        );

        return $agg + ['kr_total' => $krTotal, 'kr_completed' => $krCompleted];
    }

    // ── OKR-Ebene ─────────────────────────────────────────────────

    /** Bewertet ein ganzes OKR über seine Cycles (kaskadiert komplett nach unten). */
    public function evaluateOkr(Okr $okr, ?Carbon $date = null): array
    {
        $date ??= today();
        $cycles = $okr->relationLoaded('cycles') ? $okr->cycles : $okr->cycles()->get();

        $results = [];
        $objTotal = 0;
        $objCompleted = 0;
        $krTotal = 0;
        $krCompleted = 0;
        foreach ($cycles as $cycle) {
            $cycle->setRelation('okr', $okr); // Owner-Auflösung ohne Extra-Query
            $r = $this->evaluateCycle($cycle, $date);
            // Cycles ohne Objectives verdünnen den OKR-Score nicht.
            if ($r['total'] === 0) {
                continue;
            }
            $results[] = ['obj' => $cycle, 'r' => $r];
            $objTotal += $r['total'];
            $objCompleted += $r['completed_count'];
            $krTotal += $r['kr_total'] ?? 0;
            $krCompleted += $r['kr_completed'] ?? 0;
        }

        $agg = $this->aggregateResults($results);

        // OKR ohne messbare Struktur → manuellen Score in Ruhe lassen.
        if ($agg['total'] === 0) {
            return $agg;
        }

        $this->writeCache($okr, $agg['progress']);

        OkrPerformance::updateOrCreate(
            ['okr_id' => $okr->id, 'performance_date' => $date],
            [
                'team_id' => $okr->team_id,
                'user_id' => $okr->user_id ?? \Platform\Core\Models\User::first()?->id ?? 1,
                'performance_score' => $this->toPercent($agg['progress']),
                'completion_percentage' => $this->completionPercent($agg),
                'completed_cycles' => $agg['completed_count'],
                'total_cycles' => $agg['total'],
                'completed_objectives' => $objCompleted,
                'total_objectives' => $objTotal,
                'completed_key_results' => $krCompleted,
                'total_key_results' => $krTotal,
                'average_cycle_progress' => $this->toPercent($agg['progress']),
                'average_objective_progress' => $objTotal > 0 ? round($objCompleted / $objTotal * 100, 2) : 0,
                'average_key_result_progress' => $krTotal > 0 ? round($krCompleted / $krTotal * 100, 2) : 0,
                'is_completed' => $agg['completed'],
                'completed_at' => $agg['completed'] ? now() : null,
            ]
        );

        return $agg;
    }

    // ── Aggregations-Kern (rollenbewusst, gewichtet) ──────────────

    /**
     * Aggregiert eine Menge bewertbarer Elemente nach Rolle/Gewicht.
     *
     * @param  iterable  $items
     * @param  callable(mixed):array{score:?float,completed:bool,weight:float,role:string}  $extract
     * @return array{progress: float|null, completed: bool, total: int, completed_count: int}
     */
    protected function aggregate(iterable $items, callable $extract): array
    {
        $num = 0.0;
        $den = 0.0;
        $scoreCount = 0;
        $total = 0;
        $completedCount = 0;
        $hasGate = false;
        $gatesPass = true;

        foreach ($items as $item) {
            $e = $extract($item);
            $role = $e['role'];

            if ($role === KeyResult::ROLE_INFO) {
                continue;
            }

            $total++;
            if ($e['completed']) {
                $completedCount++;
            }

            if ($role === KeyResult::ROLE_GATE) {
                $hasGate = true;
                if (! $e['completed']) {
                    $gatesPass = false;
                }

                continue; // Gate verdünnt den Score nicht
            }

            // score-Rolle
            if ($e['score'] === null) {
                continue; // N/A → raus aus der Mathe
            }
            $w = $e['weight'] > 0 ? $e['weight'] : 1.0;
            $num += $w * $e['score'];
            $den += $w;
            $scoreCount++;
        }

        if ($scoreCount > 0 && $den > 0) {
            $progress = $num / $den;
        } elseif ($hasGate) {
            $progress = $gatesPass ? 1.0 : 0.0; // reine Gate-Ebene
        } else {
            $progress = null;
        }

        $completed = $progress !== null && $progress >= $this->completionThreshold && $gatesPass;

        return [
            'progress' => $progress,
            'completed' => $completed,
            'total' => $total,
            'completed_count' => $completedCount,
        ];
    }

    /** Aggregiert bereits bewertete Kinder (Objectives/Cycles) über deren Gewicht. */
    protected function aggregateResults(array $results): array
    {
        return $this->aggregate($results, function (array $row) {
            /** @var Objective|Cycle $node */
            $node = $row['obj'];
            $r = $row['r'];

            return [
                'score' => $r['progress'],
                'completed' => $r['completed'],
                'weight' => max(0.0, (float) ($node->weight ?? 1.0)),
                'role' => KeyResult::ROLE_SCORE, // Objectives/Cycles haben (noch) keine Gates
            ];
        });
    }

    // ── Helpers ───────────────────────────────────────────────────

    protected function isMeasureDriven(KeyResult $keyResult): bool
    {
        if (isset($keyResult->measures_count)) {
            return $keyResult->measures_count > 0;
        }
        if ($keyResult->relationLoaded('measures')) {
            return $keyResult->measures->isNotEmpty();
        }

        return $keyResult->measures()->exists();
    }

    protected function latestPerformance(KeyResult $keyResult)
    {
        if ($keyResult->relationLoaded('performances')) {
            return $keyResult->performances->sortByDesc('id')->first();
        }

        return $keyResult->performance()->first();
    }

    protected function firstPerformance(KeyResult $keyResult)
    {
        if ($keyResult->relationLoaded('performances')) {
            return $keyResult->performances->sortBy('id')->first();
        }

        return $keyResult->performances()->orderBy('id')->first();
    }

    protected function ownerId(Objective|Cycle $node): int
    {
        if ($node instanceof Objective) {
            return $node->cycle?->okr?->user_id ?? $node->cycle?->user_id ?? $node->user_id
                ?? \Platform\Core\Models\User::first()?->id ?? 1;
        }

        return $node->okr?->user_id ?? $node->user_id ?? \Platform\Core\Models\User::first()?->id ?? 1;
    }

    protected function writeCache(Objective|Cycle|Okr $node, ?float $progress): void
    {
        // okr_cycles hat keine performance_score-Cache-Spalte → nur Snapshot.
        if ($node instanceof Cycle) {
            return;
        }
        // Cache-Spalte ist decimal(4,3) NOT NULL → "nichts messbar" wird zu 0.0.
        $node->performance_score = $progress !== null ? round($progress, 3) : 0.0;
        $node->saveQuietly();
    }

    protected function completionPercent(array $agg): float
    {
        return $agg['total'] > 0 ? round($agg['completed_count'] / $agg['total'] * 100, 2) : 0.0;
    }

    protected function toPercent(?float $progress): float
    {
        return $progress !== null ? round($progress * 100, 2) : 0.0;
    }

    protected function clamp(float $v): float
    {
        return max(0.0, min(1.0, $v));
    }
}
