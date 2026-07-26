<?php

namespace Platform\Okr\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Platform\Okr\Models\Objective;

/**
 * Persönliche Ziel-Zusammenfassung (user-scoped) für die persönliche Sicht (home).
 *
 * Kontrakt für „meine Ziele" — analog zu PersonTimeSummary (organization). Liefert
 * die Objectives, für die der User verantwortlich ist (als Owner user_id oder als
 * manager_user_id), inkl. Fortschritt (performance_score) und Zyklus. Kapselt den
 * Modellzugriff, damit home nicht am okr-Modell hängt.
 */
class PersonObjectiveSummary
{
    /**
     * @return array{
     *   items: array<int, array<string,mixed>>,
     *   total: int, mountains: int, avg_score: ?float
     * }
     */
    public function forUser(int $userId): array
    {
        $objectives = Objective::query()
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhere('manager_user_id', $userId);
            })
            ->with('cycle')
            ->orderByDesc('is_mountain')
            ->get();

        $hasRoute = Route::has('okr.objectives.show');

        $items = $objectives->map(function (Objective $o) use ($hasRoute) {
            $score = $o->performance_score !== null ? (float) $o->performance_score : null;

            return [
                'id'           => (int) $o->id,
                'title'        => $o->title ?: 'Ziel',
                'description'  => $o->description ? Str::limit($o->description, 140) : null,
                'is_mountain'  => (bool) $o->is_mountain,
                'score'        => $score,
                'score_label'  => $score !== null ? round($score * 100) . '%' : null,
                'score_variant' => match (true) {
                    $score === null => 'neutral',
                    $score >= 0.7   => 'success',
                    $score >= 0.4   => 'warning',
                    default         => 'danger',
                },
                'cycle'        => $o->cycle?->label,
                'url'          => $hasRoute ? route('okr.objectives.show', $o) : null,
            ];
        })->values()->all();

        $scored = array_filter($items, fn ($i) => $i['score'] !== null);

        return [
            'items'     => $items,
            'total'     => count($items),
            'mountains' => count(array_filter($items, fn ($i) => $i['is_mountain'])),
            'avg_score' => $scored
                ? round(array_sum(array_map(fn ($i) => $i['score'], $scored)) / count($scored), 3)
                : null,
        ];
    }
}
