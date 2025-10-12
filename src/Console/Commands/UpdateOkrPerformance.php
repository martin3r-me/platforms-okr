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
        
        foreach ($objectives as $objective) {
            // Prüfe, ob bereits ein Performance-Eintrag für heute existiert
            $existingPerformance = ObjectivePerformance::where('objective_id', $objective->id)
                ->whereDate('created_at', $today)
                ->first();
            $keyResults = $objective->keyResults;
            $totalKeyResults = $keyResults->count();
            $completedKeyResults = $keyResults->where('performance.is_completed', true)->count();
            
            // Berechne durchschnittlichen Fortschritt
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
            
            ObjectivePerformance::updateOrCreate(
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
        }
        
        $this->info("Updated {$objectives->count()} Objective performances");
    }

    private function updateCyclePerformances(): void
    {
        $this->info('Updating Cycle performances...');
        
        $cycles = Cycle::with(['objectives.keyResults.performance', 'okr'])
            ->get();

        $today = today();
        
        foreach ($cycles as $cycle) {
            // Prüfe, ob bereits ein Performance-Eintrag für heute existiert
            $existingPerformance = CyclePerformance::where('cycle_id', $cycle->id)
                ->whereDate('created_at', $today)
                ->first();
            $objectives = $cycle->objectives;
            $totalObjectives = $objectives->count();
            $completedObjectives = $objectives->where('status', 'completed')->count();
            
            $totalKeyResults = $objectives->sum(fn($obj) => $obj->keyResults->count());
            $completedKeyResults = $objectives->sum(fn($obj) => $obj->keyResults->where('performance.is_completed', true)->count());
            
            // Berechne durchschnittlichen Fortschritt
            $totalObjectiveProgress = 0;
            $totalKeyResultProgress = 0;
            $objectivesWithProgress = 0;
            $keyResultsWithProgress = 0;
            
            foreach ($objectives as $objective) {
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
            
            $averageObjectiveProgress = $objectivesWithProgress > 0 ? round($totalObjectiveProgress / $objectivesWithProgress) : 0;
            $averageKeyResultProgress = $keyResultsWithProgress > 0 ? round($totalKeyResultProgress / $keyResultsWithProgress) : 0;
            $completionPercentage = $totalObjectives > 0 ? round(($completedObjectives / $totalObjectives) * 100) : 0;
            $isCompleted = $completionPercentage >= 100;
            
            // Debug: Check user_id values
            $userId = $cycle->okr->user_id ?? $cycle->user_id ?? \Platform\Core\Models\User::first()?->id ?? 1;
            
            CyclePerformance::updateOrCreate(
                [
                    'cycle_id' => $cycle->id,
                    'created_at' => $today,
                ],
                [
                    'team_id' => $cycle->team_id,
                    'user_id' => $userId,
                    'performance_score' => $averageKeyResultProgress,
                    'completion_percentage' => $completionPercentage,
                    'completed_objectives' => $completedObjectives,
                    'total_objectives' => $totalObjectives,
                    'completed_key_results' => $completedKeyResults,
                    'total_key_results' => $totalKeyResults,
                    'average_objective_progress' => $averageObjectiveProgress,
                    'average_key_result_progress' => $averageKeyResultProgress,
                    'is_completed' => $isCompleted,
                    'completed_at' => $isCompleted ? now() : null,
                ]
            );
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

        foreach ($teams as $team) {
            // Get all OKRs for this team
            $okrs = Okr::where('team_id', $team->id)->get();
            
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
            
            $achievedObjectives = $objectives->where('status', 'completed')->count();
            $achievedKeyResults = $keyResults->where('status', 'completed')->count();
            $openKeyResults = $keyResults->where('status', '!=', 'completed')->count();

            // Calculate trends (vs. previous snapshot)
            $previousPerformance = TeamPerformance::forTeam($team->id)
                ->where('performance_date', '<', $today)
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

            // Create or update team performance
            TeamPerformance::updateOrCreate(
                [
                    'team_id' => $team->id,
                    'performance_date' => $today,
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
        }
        
        $this->info("Updated {$teams->count()} Team performances");
    }
}
