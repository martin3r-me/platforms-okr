<?php

namespace Platform\Okr\Console\Commands;

use Illuminate\Console\Command;
use Platform\Okr\Models\Okr;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Models\Objective;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Models\ObjectivePerformance;
use Platform\Okr\Models\CyclePerformance;
use Platform\Okr\Models\OkrPerformance;
use Platform\Okr\Models\TeamPerformance;
use Platform\Core\Models\Team;

class UpdateOkrPerformance extends Command
{
    protected $signature = 'okr:update-performance';
    protected $description = 'Update OKR performance statistics for all levels';

    public function handle(): int
    {
        $this->info('Starting OKR performance update...');
        
        $this->updateObjectivePerformances();
        $this->updateCyclePerformances();
        $this->updateOkrPerformances();
        $this->updateTeamPerformances();
        
        $this->info('OKR performance update completed successfully!');
        return 0;
    }

    private function updateObjectivePerformances(): void
    {
        $this->info('Updating Objective performances...');
        
        $objectives = Objective::with(['keyResults.performance', 'keyResults.performances', 'cycle.okr'])
            ->whereHas('cycle')
            ->get();

        $today = today();
        $this->info("Today's date: {$today->format('Y-m-d')}");
        $this->info("Found {$objectives->count()} objectives to process");
        
        foreach ($objectives as $objective) {
            $this->info("Processing Objective ID: {$objective->id} - {$objective->title}");
            
            // Prüfe, ob bereits ein Performance-Eintrag für heute existiert
            $existingPerformance = ObjectivePerformance::where('objective_id', $objective->id)
                ->whereDate('created_at', $today)
                ->first();
                
            if ($existingPerformance) {
                $this->info("  → Objective {$objective->id} already has performance for today, updating...");
            } else {
                $this->info("  → Objective {$objective->id} has no performance for today, creating new...");
            }
            
            $keyResults = $objective->keyResults;
            $totalKeyResults = $keyResults->count();
            $completedKeyResults = $keyResults->where('performance.is_completed', true)->count();
            
            $this->info("  → Key Results: {$totalKeyResults} total, {$completedKeyResults} completed");
            
            // Berechne durchschnittlichen Fortschritt basierend auf Key Result Performances
            $totalProgress = 0;
            $keyResultsWithProgress = 0;

            foreach ($keyResults as $keyResult) {
                if ($keyResult->performance) {
                    $target = $keyResult->performance->target_value ?? 0;
                    $current = $keyResult->performance->current_value ?? 0;
                    $type = $keyResult->performance->type;

                    // Hole den ersten Performance-Wert als Ausgangswert
                    $firstPerformance = $keyResult->performances()->orderBy('created_at', 'asc')->first();
                    $startValue = $firstPerformance?->current_value ?? 0;

                    if ($type === 'boolean') {
                        $totalProgress += $keyResult->performance->is_completed ? 100 : 0;
                    } elseif ($type === 'percentage' || $type === 'absolute') {
                        if ($target > $startValue) {
                            $progressRange = $target - $startValue;
                            $currentProgress = $current - $startValue;
                            $progressPercent = min(100, max(0, round(($currentProgress / $progressRange) * 100)));
                        } elseif ($target < $startValue) {
                            $progressRange = $startValue - $target;
                            $currentProgress = $startValue - $current;
                            $progressPercent = min(100, max(0, round(($currentProgress / $progressRange) * 100)));
                        } else {
                            $progressPercent = $current >= $target ? 100 : 0;
                        }
                        $totalProgress += $progressPercent;
                    }
                    $keyResultsWithProgress++;
                }
            }

            $averageProgress = $keyResultsWithProgress > 0 ? round($totalProgress / $keyResultsWithProgress) : 0;
            $completionPercentage = $totalKeyResults > 0 ? round(($completedKeyResults / $totalKeyResults) * 100) : 0;
            $isCompleted = $completionPercentage >= 100;
            
            // Debug: Check user_id values
            $userId = $objective->cycle->okr->user_id ?? $objective->cycle->user_id ?? \Platform\Core\Models\User::first()?->id ?? 1;
            
            $this->info("  → Calculated: Average Progress: {$averageProgress}%, Completion: {$completionPercentage}%, User ID: {$userId}");
            
            $result = ObjectivePerformance::updateOrCreate(
                [
                    'objective_id' => $objective->id,
                    'created_at' => $today,
                ],
                [
                    'team_id' => $objective->cycle->team_id,
                    'user_id' => $userId,
                    'performance_score' => $averageProgress,
                    'completion_percentage' => $completionPercentage,
                    'completed_key_results' => $completedKeyResults,
                    'total_key_results' => $totalKeyResults,
                    'average_progress' => $averageProgress,
                    'is_completed' => $isCompleted,
                    'completed_at' => $isCompleted ? now() : null,
                ]
            );
            
            $this->info("  → Performance " . ($result->wasRecentlyCreated ? 'CREATED' : 'UPDATED') . " for Objective {$objective->id}");
        }
        
        $this->info("Updated {$objectives->count()} Objective performances");
    }

    private function updateCyclePerformances(): void
    {
        $this->info('Updating Cycle performances...');
        
        $cycles = Cycle::with(['objectives.performance', 'okr'])
            ->get();

        $today = today();
        $this->info("Today's date: {$today->format('Y-m-d')}");
        $this->info("Found {$cycles->count()} cycles to process");
        
        foreach ($cycles as $cycle) {
            $this->info("Processing Cycle ID: {$cycle->id} - OKR: {$cycle->okr?->title}");
            
            // Prüfe, ob bereits ein Performance-Eintrag für heute existiert
            $existingPerformance = CyclePerformance::where('cycle_id', $cycle->id)
                ->whereDate('created_at', $today)
                ->first();
                
            if ($existingPerformance) {
                $this->info("  → Cycle {$cycle->id} already has performance for today, updating...");
            } else {
                $this->info("  → Cycle {$cycle->id} has no performance for today, creating new...");
            }
            $objectives = $cycle->objectives;
            $totalObjectives = $objectives->count();
            $completedObjectives = $objectives->where('status', 'completed')->count();
            
            $this->info("  → Objectives: {$totalObjectives} total, {$completedObjectives} completed");
            
            // Berechne durchschnittlichen Fortschritt basierend auf Objective Performances
            $totalObjectiveProgress = 0;
            $objectivesWithProgress = 0;

            foreach ($objectives as $objective) {
                if ($objective->performance) {
                    $totalObjectiveProgress += $objective->performance->performance_score ?? 0;
                    $objectivesWithProgress++;
                }
            }

            $averageObjectiveProgress = $objectivesWithProgress > 0 ? round($totalObjectiveProgress / $objectivesWithProgress) : 0;
            $completionPercentage = $totalObjectives > 0 ? round(($completedObjectives / $totalObjectives) * 100) : 0;
            $isCompleted = $completionPercentage >= 100;
            
            // Debug: Check user_id values
            $userId = $cycle->okr->user_id ?? $cycle->user_id ?? \Platform\Core\Models\User::first()?->id ?? 1;
            
            $this->info("  → Calculated: Average Objective Progress: {$averageObjectiveProgress}%, Completion: {$completionPercentage}%, User ID: {$userId}");
            
            $result = CyclePerformance::updateOrCreate(
                [
                    'cycle_id' => $cycle->id,
                    'created_at' => $today,
                ],
                [
                    'team_id' => $cycle->team_id,
                    'user_id' => $userId,
                    'performance_score' => $averageObjectiveProgress,
                    'completion_percentage' => $completionPercentage,
                    'completed_objectives' => $completedObjectives,
                    'total_objectives' => $totalObjectives,
                    'completed_key_results' => 0, // Wird nicht mehr direkt berechnet
                    'total_key_results' => 0, // Wird nicht mehr direkt berechnet
                    'average_objective_progress' => $averageObjectiveProgress,
                    'average_key_result_progress' => 0, // Wird nicht mehr direkt berechnet
                    'is_completed' => $isCompleted,
                    'completed_at' => $isCompleted ? now() : null,
                ]
            );
            
            $this->info("  → Performance " . ($result->wasRecentlyCreated ? 'CREATED' : 'UPDATED') . " for Cycle {$cycle->id}");
        }
        
        $this->info("Updated {$cycles->count()} Cycle performances");
    }

    private function updateOkrPerformances(): void
    {
        $this->info('Updating OKR performances...');
        
        $okrs = Okr::with(['cycles.objectives.keyResults.performance'])
            ->get();

        $today = today();
        
        foreach ($okrs as $okr) {
            // Prüfe, ob bereits ein Performance-Eintrag für heute existiert
            $existingPerformance = OkrPerformance::where('okr_id', $okr->id)
                ->whereDate('created_at', $today)
                ->first();
            $cycles = $okr->cycles;
            $totalCycles = $cycles->count();
            $completedCycles = $cycles->where('status', 'completed')->count();
            
            $totalObjectives = $cycles->sum(fn($cycle) => $cycle->objectives->count());
            $completedObjectives = $cycles->sum(fn($cycle) => $cycle->objectives->where('status', 'completed')->count());
            
            $totalKeyResults = $cycles->sum(fn($cycle) => $cycle->objectives->sum(fn($obj) => $obj->keyResults->count()));
            $completedKeyResults = $cycles->sum(fn($cycle) => $cycle->objectives->sum(fn($obj) => $obj->keyResults->where('performance.is_completed', true)->count()));
            
            // Berechne durchschnittlichen Fortschritt
            $totalCycleProgress = 0;
            $totalObjectiveProgress = 0;
            $totalKeyResultProgress = 0;
            $cyclesWithProgress = 0;
            $objectivesWithProgress = 0;
            $keyResultsWithProgress = 0;
            
            foreach ($cycles as $cycle) {
                $cycleObjectives = $cycle->objectives;
                $cycleCompletedObjectives = $cycleObjectives->where('status', 'completed')->count();
                $cycleTotalObjectives = $cycleObjectives->count();
                $cycleProgress = $cycleTotalObjectives > 0 ? round(($cycleCompletedObjectives / $cycleTotalObjectives) * 100) : 0;
                $totalCycleProgress += $cycleProgress;
                $cyclesWithProgress++;
                
                foreach ($cycleObjectives as $objective) {
                    $objKeyResults = $objective->keyResults;
                    $objCompleted = $objKeyResults->where('performance.is_completed', true)->count();
                    $objTotal = $objKeyResults->count();
                    $objProgress = $objTotal > 0 ? round(($objCompleted / $objTotal) * 100) : 0;
                    $totalObjectiveProgress += $objProgress;
                    $objectivesWithProgress++;
                    
                    foreach ($objKeyResults as $keyResult) {
                        if ($keyResult->performance) {
                            $target = $keyResult->performance->target_value ?? 0;
                            $current = $keyResult->performance->current_value ?? 0;
                            $type = $keyResult->performance->type;
                            
                            $firstPerformance = $keyResult->performances()->orderBy('created_at', 'asc')->first();
                            $startValue = $firstPerformance?->current_value ?? 0;
                            
                            if ($type === 'boolean') {
                                $totalKeyResultProgress += $keyResult->performance->is_completed ? 100 : 0;
                            } elseif ($type === 'percentage' || $type === 'absolute') {
                                if ($target > $startValue) {
                                    $progressRange = $target - $startValue;
                                    $currentProgress = $current - $startValue;
                                    $progressPercent = min(100, max(0, round(($currentProgress / $progressRange) * 100)));
                                } elseif ($target < $startValue) {
                                    $progressRange = $startValue - $target;
                                    $currentProgress = $startValue - $current;
                                    $progressPercent = min(100, max(0, round(($currentProgress / $progressRange) * 100)));
                                } else {
                                    $progressPercent = $current >= $target ? 100 : 0;
                                }
                                $totalKeyResultProgress += $progressPercent;
                            }
                            $keyResultsWithProgress++;
                        }
                    }
                }
            }
            
            $averageCycleProgress = $cyclesWithProgress > 0 ? round($totalCycleProgress / $cyclesWithProgress) : 0;
            $averageObjectiveProgress = $objectivesWithProgress > 0 ? round($totalObjectiveProgress / $objectivesWithProgress) : 0;
            $averageKeyResultProgress = $keyResultsWithProgress > 0 ? round($totalKeyResultProgress / $keyResultsWithProgress) : 0;
            $completionPercentage = $totalCycles > 0 ? round(($completedCycles / $totalCycles) * 100) : 0;
            $isCompleted = $completionPercentage >= 100;
            
            // Debug: Check user_id values
            $userId = $okr->user_id ?? \Platform\Core\Models\User::first()?->id ?? 1;
            
            OkrPerformance::updateOrCreate(
                [
                    'okr_id' => $okr->id,
                    'created_at' => $today,
                ],
                [
                    'team_id' => $okr->team_id,
                    'user_id' => $userId,
                    'performance_score' => $averageKeyResultProgress,
                    'completion_percentage' => $completionPercentage,
                    'completed_cycles' => $completedCycles,
                    'total_cycles' => $totalCycles,
                    'completed_objectives' => $completedObjectives,
                    'total_objectives' => $totalObjectives,
                    'completed_key_results' => $completedKeyResults,
                    'total_key_results' => $totalKeyResults,
                    'average_cycle_progress' => $averageCycleProgress,
                    'average_objective_progress' => $averageObjectiveProgress,
                    'average_key_result_progress' => $averageKeyResultProgress,
                    'is_completed' => $isCompleted,
                    'completed_at' => $isCompleted ? now() : null,
                ]
            );
        }
        
        $this->info("Updated {$okrs->count()} OKR performances");
    }

    private function updateTeamPerformances(): void
    {
        $this->info('Updating Team performances...');
        
        $teams = Team::all();
        $today = today();
        $this->info("Today's date: {$today->format('Y-m-d')}");
        $this->info("Found {$teams->count()} teams to process");

        foreach ($teams as $team) {
            $this->info("Processing Team ID: {$team->id} - {$team->name}");
            
            // Get all OKRs for this team
            $okrs = Okr::where('team_id', $team->id)->get();
            $this->info("  → Found {$okrs->count()} OKRs for team {$team->id}");
            
            // Get active cycles
            $activeCycles = Cycle::where('team_id', $team->id)
                ->whereIn('status', ['current', 'active'])
                ->get();

            // Get all objectives and key results from active cycles
            $objectives = $activeCycles->flatMap->objectives;
            $keyResults = $objectives->flatMap->keyResults;

            // Calculate metrics
            $averageScore = $okrs->where('performance_score', '!=', null)->avg('performance_score') ?? 0;
            $successfulOkrs = $okrs->where('performance_score', '>=', 80)->count();
            
            $this->info("  → OKR Metrics: Average Score: {$averageScore}, Successful OKRs: {$successfulOkrs}");
            
            $achievedObjectives = $objectives->where('status', 'completed')->count();
            $achievedKeyResults = $keyResults->where('status', 'completed')->count();
            $openKeyResults = $keyResults->where('status', '!=', 'completed')->count();
            
            $this->info("  → Objectives: {$objectives->count()} total, {$achievedObjectives} achieved");
            $this->info("  → Key Results: {$keyResults->count()} total, {$achievedKeyResults} achieved, {$openKeyResults} open");

            // Calculate trends (vs. previous snapshot)
            $previousPerformance = TeamPerformance::forTeam($team->id)
                ->where('created_at', '<', $today)
                ->latest()
                ->first();

            $scoreTrend = 0;
            $okrTrend = 0;
            $achievementTrend = 0;

            if ($previousPerformance) {
                $scoreTrend = $averageScore - $previousPerformance->average_score;
                $okrTrend = $okrs->count() - $previousPerformance->total_okrs;
                $achievementTrend = $achievedObjectives - $previousPerformance->achieved_objectives;
            }

            $this->info("  → Creating/Updating Team Performance for team {$team->id}");
            
            $result = TeamPerformance::updateOrCreate(
                [
                    'team_id' => $team->id,
                    'created_at' => $today,
                ],
                [
                    'average_score' => $averageScore,
                    'total_okrs' => $okrs->count(),
                    'active_okrs' => $okrs->where('status', 'active')->count(),
                    'successful_okrs' => $successfulOkrs,
                    'draft_okrs' => $okrs->where('status', 'draft')->count(),
                    'completed_okrs' => $okrs->where('status', 'completed')->count(),
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
            
            $this->info("  → Team Performance " . ($result->wasRecentlyCreated ? 'CREATED' : 'UPDATED') . " for team {$team->id}");
        }
        
        $this->info("Updated {$teams->count()} Team performances");
    }
}
