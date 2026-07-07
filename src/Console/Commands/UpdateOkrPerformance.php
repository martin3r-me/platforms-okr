<?php

namespace Platform\Okr\Console\Commands;

use Illuminate\Console\Command;
use Platform\Core\Models\Team;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Models\Okr;
use Platform\Okr\Models\OkrPerformance;
use Platform\Okr\Models\TeamPerformance;
use Platform\Okr\Services\OkrRollupService;

class UpdateOkrPerformance extends Command
{
    protected $signature = 'okr:update-performance';
    protected $description = 'Rollt Zielerreichung KR → Objective → Cycle → OKR hoch und schreibt Snapshots';

    public function handle(OkrRollupService $rollup): int
    {
        $this->info('Starting Zielsteuerung performance update...');

        $date = today();

        // Ein Pass, kaskadiert komplett nach unten. Eager-Loading hält es N+1-frei.
        $okrs = Okr::with([
            'cycles.objectives.keyResults' => fn ($q) => $q->withCount('measures'),
            'cycles.objectives.keyResults.performances',
        ])->get();

        $this->info("Rolling up {$okrs->count()} OKRs...");
        foreach ($okrs as $okr) {
            $agg = $rollup->evaluateOkr($okr, $date);
            $this->line("  → OKR {$okr->id}: " . round(($agg['progress'] ?? 0) * 100) . '%'
                . ($agg['completed'] ? ' ✓' : ''));
        }

        $this->updateTeamPerformances();

        $this->info('Zielsteuerung performance update completed successfully!');

        return self::SUCCESS;
    }

    /**
     * Team-Ebene: aggregiert die frisch geschriebenen OKR-Snapshots je Team.
     * (Unverändert — liest OkrPerformance, das der Rollup gerade erzeugt hat.)
     */
    private function updateTeamPerformances(): void
    {
        $this->info('Updating Team performances...');

        $teams = Team::all();
        $today = today();

        foreach ($teams as $team) {
            $okrs = Okr::where('team_id', $team->id)->get();

            $activeCycles = Cycle::where('team_id', $team->id)
                ->whereIn('status', ['current', 'active'])
                ->get();

            $objectives = $activeCycles->flatMap->objectives;
            $keyResults = $objectives->flatMap->keyResults;

            // Nur OKRs mit Cycles, Objectives und Key Results berücksichtigen
            $relevantOkrs = $okrs->filter(function ($okr) {
                return $okr->cycles->count() > 0
                    && $okr->cycles->sum(fn ($cycle) => $cycle->objectives->count()) > 0
                    && $okr->cycles->sum(fn ($cycle) => $cycle->objectives->sum(fn ($obj) => $obj->keyResults->count())) > 0;
            });

            $okrPerformances = OkrPerformance::whereIn('okr_id', $relevantOkrs->pluck('id'))
                ->whereDate('performance_date', $today)
                ->get();

            $averageScore = $okrPerformances->avg('performance_score') ?? 0;
            $successfulOkrs = $okrPerformances->where('performance_score', '>=', 80)->count();

            $achievedObjectives = $objectives->where('status', 'completed')->count();
            $achievedKeyResults = $keyResults->where('status', 'completed')->count();
            $openKeyResults = $keyResults->where('status', '!=', 'completed')->count();

            // Trends vs. letzter Snapshot
            $previousPerformance = TeamPerformance::forTeam($team->id)
                ->where('performance_date', '<', $today)
                ->latest()
                ->first();

            $scoreTrend = 0;
            $okrTrend = 0;
            $achievementTrend = 0;

            if ($previousPerformance) {
                $scoreTrend = $averageScore - $previousPerformance->average_score;
                $okrTrend = $relevantOkrs->count() - $previousPerformance->total_okrs;
                $achievementTrend = $achievedObjectives - $previousPerformance->achieved_objectives;
            }

            TeamPerformance::updateOrCreate(
                [
                    'team_id' => $team->id,
                    'performance_date' => $today,
                ],
                [
                    'performance_date' => $today,
                    'average_score' => $averageScore,
                    'total_okrs' => $relevantOkrs->count(),
                    'active_okrs' => $relevantOkrs->where('status', 'active')->count(),
                    'successful_okrs' => $successfulOkrs,
                    'draft_okrs' => $relevantOkrs->where('status', 'draft')->count(),
                    'completed_okrs' => $relevantOkrs->where('status', 'completed')->count(),
                    'total_objectives' => $objectives->count(),
                    'achieved_objectives' => $achievedObjectives,
                    'total_key_results' => $keyResults->count(),
                    'achieved_key_results' => $achievedKeyResults,
                    'open_key_results' => $openKeyResults,
                    'active_cycles' => $activeCycles->count(),
                    'current_cycles' => $activeCycles->where('status', 'current')->count(),
                    'score_trend' => $scoreTrend,
                    'okr_trend' => $okrTrend,
                    'achievement_trend' => $achievementTrend,
                ]
            );
        }

        $this->info("Updated {$teams->count()} Team performances");
    }
}
