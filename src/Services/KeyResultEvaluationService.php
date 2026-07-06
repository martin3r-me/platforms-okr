<?php

namespace Platform\Okr\Services;

use Platform\Okr\Models\KeyResult;
use Platform\Okr\Models\KeyResultMeasure;

/**
 * Bewertet ein Key Result aus seinen Measures: normalisiert jeden Measure auf
 * Zielerreichung (Gap-Closure) und aggregiert nach Rolle zu Erreichungsquote +
 * erreicht/nicht. Schreibt das Ergebnis als KeyResultPerformance-Snapshot.
 *
 * Reine Bewertungs-Logik — liest KEINE externen Quellen (das macht der Sync).
 */
class KeyResultEvaluationService
{
    /** "erreicht" = volle Zielerreichung UND alle Gates. */
    public float $completionThreshold = 1.0;

    /**
     * Effektive Baseline: explizit gewinnt; sonst Default je Typ/Polarität.
     * up+ratio/boolean → 0 (absolute Zielerreichung ab 0). Sonst der eingefrorene
     * Startwert (vom Sync), bis dahin current_value.
     */
    public function baselineFor(KeyResultMeasure $m): ?float
    {
        if ($m->baseline_value !== null) {
            return (float) $m->baseline_value;
        }
        // up misst absolute Zielerreichung ab 0 (Quoten UND Counter wachsen von 0);
        // down misst ab dem eingefrorenen Startwert (Auto-Freeze im Sync).
        if ($m->polarity === 'up') {
            return 0.0;
        }
        return $m->current_value !== null ? (float) $m->current_value : null;
    }

    /** Gap-Closure-Normalisierung eines Measures → [0,1] oder null (N/A). */
    public function achievement(KeyResultMeasure $m): ?float
    {
        if (! $m->is_available || $m->current_value === null) {
            return null;
        }
        $c = (float) $m->current_value;

        if ($m->value_type === 'boolean') {
            $t = $m->target_value !== null ? (float) $m->target_value : 1.0;
            return $c >= $t ? 1.0 : 0.0;
        }

        if ($m->target_value === null) {
            return null; // ohne Ziel keine Zielerreichung
        }
        $t = (float) $m->target_value;
        $b = $this->baselineFor($m) ?? 0.0;

        $denom = $t - $b;
        if (abs($denom) < 1e-9) {
            // Ziel == Baseline: binär je Richtung
            return $m->polarity === 'down' ? ($c <= $t ? 1.0 : 0.0) : ($c >= $t ? 1.0 : 0.0);
        }

        // Richtung ist implizit über die Ordnung target vs. baseline kodiert.
        $a = ($c - $b) / $denom;

        return max(0.0, min(1.0, $a));
    }

    /**
     * Aggregiert alle Measures → {progress, completed} und schreibt einen
     * KeyResultPerformance-Snapshot (type=calculated). Aktualisiert den
     * performance_score-Cache am KR.
     *
     * @return array{progress: float|null, completed: bool, measures: int}
     */
    public function evaluate(KeyResult $keyResult): array
    {
        $measures = $keyResult->measures()->get();

        $scoredNum = 0.0;
        $scoredDen = 0.0;
        $scoredCount = 0;
        $scoreMeasures = [];
        $caps = [];
        $hasGate = false;
        $gatesPass = true;

        foreach ($measures as $m) {
            $a = $this->achievement($m);

            // berechnete Zielerreichung am Measure persistieren (null = N/A)
            $m->achievement = $a;
            $m->saveQuietly();

            if ($a === null) {
                continue; // N/A → raus aus der Mathe
            }

            switch ($m->role) {
                case KeyResultMeasure::ROLE_SCORE:
                    $w = max(0.0, (float) $m->weight);
                    $scoredNum += $w * $a;
                    $scoredDen += $w;
                    $scoredCount++;
                    $scoreMeasures[] = $m;
                    break;
                case KeyResultMeasure::ROLE_CAP:
                    $caps[] = $a;
                    break;
                case KeyResultMeasure::ROLE_GATE:
                    $hasGate = true;
                    if ($a < 1.0) {
                        $gatesPass = false;
                    }
                    break;
                // ROLE_INFO: ignoriert
            }
        }

        if ($scoredCount > 0 && $scoredDen > 0) {
            $progress = $scoredNum / $scoredDen;
        } elseif ($hasGate) {
            $progress = $gatesPass ? 1.0 : 0.0; // reine Gate-KRs
        } else {
            $progress = null; // nichts messbar
        }

        if ($progress !== null && ! empty($caps)) {
            $progress = min($progress, min($caps));
        }

        $completed = $progress !== null && $progress >= $this->completionThreshold && $gatesPass;

        if ($progress !== null) {
            // Anzeige: bei genau EINER Score-Metrik (ohne Cap-Deckelung) die echten
            // Quellwerte durchreichen (z.B. "38 von 500") statt der abstrakten Quote.
            // performance_score/is_completed bleiben die aggregierte Zielerreichung.
            $type = 'calculated';
            $currentValue = round($progress, 4);
            $targetValue = 1.0;
            $calculatedValue = round($progress, 4);

            if (count($scoreMeasures) === 1 && empty($caps)) {
                $h = $scoreMeasures[0];
                $type = match ($h->value_type) {
                    'boolean' => 'boolean',
                    'ratio' => 'percentage',
                    default => 'absolute',
                };
                $currentValue = (float) $h->current_value;
                $targetValue = $h->target_value !== null ? (float) $h->target_value : 1.0;
                $calculatedValue = null;
            }

            $keyResult->performances()->create([
                'type' => $type,
                'target_value' => $targetValue,
                'current_value' => $currentValue,
                'calculated_value' => $calculatedValue,
                'is_completed' => $completed,
                'performance_score' => round($progress, 4),
                'team_id' => $keyResult->team_id,
                'user_id' => null, // system
            ]);

            $keyResult->performance_score = round($progress, 4);
            $keyResult->saveQuietly();
        }

        return [
            'progress' => $progress,
            'completed' => $completed,
            'measures' => $measures->count(),
        ];
    }
}
