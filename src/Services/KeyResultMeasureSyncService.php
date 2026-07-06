<?php

namespace Platform\Okr\Services;

use Illuminate\Support\Facades\Schema;
use Platform\Core\KeyResult\MetricRequest;
use Platform\Core\KeyResult\MetricValue;
use Platform\Core\Models\Team;
use Platform\Core\Services\KeyResultMetricRegistry;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Models\KeyResultMeasure;

/**
 * Löst dynamische Measures gegen ihre Provider auf (batch, N+1-frei), schreibt
 * den Rohwert + Baseline-Auto-Freeze ans Measure und triggert die Bewertung.
 *
 * Provider = dumm (Rohwert). Scope (Team-IDs) und Window (aus dem Cycle) baut die
 * Engine und übergibt sie fertig. Manuelle Measures (metric_key=manual) werden nicht
 * gesynct, gehen aber über dieselbe Evaluation.
 */
class KeyResultMeasureSyncService
{
    /** @var array<int, int[]> cache: root team id => team ids incl. children */
    protected array $teamScopeCache = [];

    public function __construct(
        protected KeyResultMetricRegistry $registry,
        protected KeyResultEvaluationService $evaluator,
    ) {
    }

    /**
     * Synct alle dynamischen Measures teamweit und bewertet betroffene KRs neu.
     *
     * @return int Anzahl aktualisierter Measures
     */
    public function syncAll(): int
    {
        if (! Schema::hasTable('okr_key_result_measures')) {
            return 0;
        }

        $measures = KeyResultMeasure::query()
            ->where('metric_key', '!=', 'manual')
            ->with(['keyResult.objective.cycle.template'])
            ->get();

        if ($measures->isEmpty()) {
            return 0;
        }

        $updated = 0;
        $affectedKrIds = [];

        // Nach metric_key gruppieren → ein resolveBatch pro Metrik
        foreach ($measures->groupBy('metric_key') as $metricKey => $group) {
            $provider = $this->registry->providerFor($metricKey);
            $def = $this->registry->definition($metricKey);
            if (! $provider || ! $def) {
                continue; // unbekannte Metrik → nicht anfassen (nicht 0 setzen)
            }

            $requests = [];
            foreach ($group as $m) {
                if (! $m->keyResult) {
                    continue;
                }
                $requests[$m->id] = new MetricRequest(
                    metricKey: $metricKey,
                    selector: $m->selector ?? [],
                    scope: $this->buildScope($m->keyResult),
                    window: $this->buildWindow($m, $def),
                    asOf: now()->toIso8601String(),
                    ref: $m->id,
                );
            }

            if (empty($requests)) {
                continue;
            }

            $values = $provider->resolveBatch($metricKey, $requests);

            foreach ($group as $m) {
                $value = $values[$m->id] ?? MetricValue::unavailable('no result');
                $this->applyValue($m, $value);
                $updated++;
                $affectedKrIds[$m->key_result_id] = true;
            }
        }

        // Betroffene KRs neu bewerten
        foreach (array_keys($affectedKrIds) as $krId) {
            $kr = KeyResult::find($krId);
            if ($kr) {
                $this->evaluator->evaluate($kr);
            }
        }

        return $updated;
    }

    /** Synct die Measures eines einzelnen KR und bewertet ihn. */
    public function syncKeyResult(KeyResult $keyResult): array
    {
        $measures = $keyResult->measures()->where('metric_key', '!=', 'manual')->get();

        foreach ($measures->groupBy('metric_key') as $metricKey => $group) {
            $provider = $this->registry->providerFor($metricKey);
            $def = $this->registry->definition($metricKey);
            if (! $provider || ! $def) {
                continue;
            }

            $requests = [];
            foreach ($group as $m) {
                $requests[$m->id] = new MetricRequest(
                    metricKey: $metricKey,
                    selector: $m->selector ?? [],
                    scope: $this->buildScope($keyResult),
                    window: $this->buildWindow($m, $def),
                    asOf: now()->toIso8601String(),
                    ref: $m->id,
                );
            }

            $values = $provider->resolveBatch($metricKey, $requests);

            foreach ($group as $m) {
                $this->applyValue($m, $values[$m->id] ?? MetricValue::unavailable('no result'));
            }
        }

        return $this->evaluator->evaluate($keyResult);
    }

    /** Schreibt Rohwert + Baseline-Auto-Freeze ans Measure. N/A → nie 0. */
    protected function applyValue(KeyResultMeasure $m, MetricValue $value): void
    {
        $m->is_available = $value->available;

        if ($value->available && $value->value !== null) {
            $m->current_value = $value->value;
            if ($value->label !== null) {
                $m->label = $value->label;
            }

            // Baseline auto-freeze: nur wenn nicht explizit und nicht up+ratio/boolean
            // (die messen ab 0 → Baseline bleibt 0, siehe Evaluation::baselineFor).
            $isAbsoluteFromZero = $m->polarity === 'up' && in_array($m->value_type, ['ratio', 'boolean'], true);
            if ($m->baseline_value === null && ! $isAbsoluteFromZero) {
                $m->baseline_value = $value->value;
            }
        }

        $m->last_synced_at = now();
        $m->saveQuietly();
    }

    /** Team-Scope: Root-Team + alle Child-Teams (wie beim Counter-Sync). */
    protected function buildScope(KeyResult $keyResult): array
    {
        $rootTeamId = (int) $keyResult->team_id;

        if (! isset($this->teamScopeCache[$rootTeamId])) {
            $team = Team::find($rootTeamId);
            $this->teamScopeCache[$rootTeamId] = $team && method_exists($team, 'getAllTeamIdsIncludingChildren')
                ? $team->getAllTeamIdsIncludingChildren()
                : [$rootTeamId];
        }

        return [
            'root_team_id' => $rootTeamId,
            'team_ids' => $this->teamScopeCache[$rootTeamId],
            // entity_id / entity_subtree_ids: kommen mit den kr_entity-Providern (nächste Slice)
            'entity_id' => null,
            'entity_subtree_ids' => [],
        ];
    }

    /** Window aus dem Cycle-Template, wenn die Metrik es unterstützt und periodisch gemessen wird. */
    protected function buildWindow(KeyResultMeasure $m, array $def): ?array
    {
        if (empty($def['supports_window']) || $m->window_mode !== 'period') {
            return null; // kumulativ / kein Zeitfenster
        }

        $template = $m->keyResult?->objective?->cycle?->template;
        if (! $template) {
            return null;
        }

        return [
            'mode' => 'period',
            'from' => $template->starts_at ? (string) $template->starts_at : null,
            'to' => $template->ends_at ? (string) $template->ends_at : null,
        ];
    }
}
