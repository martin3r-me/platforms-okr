<?php

namespace Platform\Okr\Services;

use Illuminate\Support\Facades\Schema;
use Platform\Core\Contracts\CounterKeyResultSyncer;
use Platform\Core\Models\TeamCounterDefinition;
use Platform\Core\Models\TeamCounterEvent;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Models\KeyResultContext;

class CounterKeyResultSyncService implements CounterKeyResultSyncer
{
    public function syncAll(bool $dryRun = false): int
    {
        // Defensive: Tabellen können in frühen Boot-Phasen fehlen
        if (
            !Schema::hasTable('okr_key_results') ||
            !Schema::hasTable('okr_key_result_contexts') ||
            !Schema::hasTable('okr_key_result_performances') ||
            !Schema::hasTable('team_counter_definitions') ||
            !Schema::hasTable('team_counter_events')
        ) {
            return 0;
        }

        $updated = 0;

        // Nur primäre Verknüpfungen betrachten (1:1, aber wir unterstützen n:1)
        $links = KeyResultContext::query()
            ->where('context_type', TeamCounterDefinition::class)
            ->where('is_primary', true)
            ->get(['key_result_id', 'context_id']);

        if ($links->isEmpty()) {
            return 0;
        }

        // Gruppieren: counter_definition_id => [key_result_ids...]
        $byCounter = $links->groupBy('context_id');

        foreach ($byCounter as $counterId => $rows) {
            $counter = TeamCounterDefinition::query()->find((int) $counterId);
            if (!$counter) {
                continue;
            }

            // Root-Team scope: Root + alle Descendants
            $rootTeam = $counter->scopeTeam()->first();
            if (!$rootTeam) {
                continue;
            }

            $teamIds = $rootTeam->getAllTeamIdsIncludingChildren();

            $sum = (int) TeamCounterEvent::query()
                ->where('team_counter_definition_id', (int) $counterId)
                ->whereIn('team_id', $teamIds)
                ->sum('delta');

            foreach ($rows as $row) {
                $krId = (int) $row->key_result_id;
                $keyResult = KeyResult::query()->with('performance')->find($krId);
                if (!$keyResult) {
                    continue;
                }

                // Safety: Counter-Scope und KR-Team sollten auf das gleiche Root-Team zeigen
                if ((int) $keyResult->team_id !== (int) $counter->scope_team_id) {
                    continue;
                }

                $currentPerf = $keyResult->performance;
                if (!$currentPerf) {
                    continue;
                }

                // Boolean KRs nicht überschreiben
                if ($currentPerf->type === 'boolean') {
                    continue;
                }

                $target = (float) ($currentPerf->target_value ?? 0);
                $score = $target > 0 ? ((float) $sum / $target) : 0.0;
                $isCompleted = $target > 0 ? ((float) $sum >= $target) : false;

                if ($dryRun) {
                    $updated++;
                    continue;
                }

                // Versionierte Performance schreiben (latestOfMany)
                $keyResult->performances()->create([
                    'type' => $currentPerf->type === 'calculated' ? 'absolute' : $currentPerf->type,
                    'target_value' => $currentPerf->target_value,
                    'current_value' => (float) $sum,
                    'calculated_value' => null,
                    'is_completed' => $isCompleted,
                    'performance_score' => $score,
                    'team_id' => $keyResult->team_id,
                    'user_id' => null, // system sync
                ]);

                $updated++;
            }
        }

        return $updated;
    }
}


