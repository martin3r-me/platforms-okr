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
        
        $this->info('OKR performance update completed successfully!');
        return 0;
    }

    private function updateObjectivePerformances(): void
    {
        $this->info('Updating Objective performances...');
        
        $objectives = Objective::with(['keyResults.performance', 'keyResults.performances', 'cycle.okr'])
            ->whereHas('cycle')
            ->get();

        foreach ($objectives as $objective) {
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
            
            ObjectivePerformance::create([
                'objective_id' => $objective->id,
                'team_id' => $objective->cycle->team_id,
                'user_id' => $objective->cycle->okr->user_id ?? $objective->cycle->user_id,
                'performance_score' => $averageProgress,
                'completion_percentage' => $completionPercentage,
                'completed_key_results' => $completedKeyResults,
                'total_key_results' => $totalKeyResults,
                'average_progress' => $averageProgress,
                'is_completed' => $isCompleted,
                'completed_at' => $isCompleted ? now() : null,
            ]);
        }
        
        $this->info("Updated {$objectives->count()} Objective performances");
    }

    private function updateCyclePerformances(): void
    {
        $this->info('Updating Cycle performances...');
        
        $cycles = Cycle::with(['objectives.keyResults.performance', 'okr'])
            ->get();

        foreach ($cycles as $cycle) {
            $objectives = $cycle->objectives;
            $totalObjectives = $objectives->count();
            $completedObjectives = $objectives->where('performance.is_completed', true)->count();
            
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
            
            CyclePerformance::create([
                'cycle_id' => $cycle->id,
                'team_id' => $cycle->team_id,
                'user_id' => $cycle->okr->user_id ?? $cycle->user_id,
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
            ]);
        }
        
        $this->info("Updated {$cycles->count()} Cycle performances");
    }

    private function updateOkrPerformances(): void
    {
        $this->info('Updating OKR performances...');
        
        $okrs = Okr::with(['cycles.objectives.keyResults.performance'])
            ->get();

        foreach ($okrs as $okr) {
            $cycles = $okr->cycles;
            $totalCycles = $cycles->count();
            $completedCycles = $cycles->where('performance.is_completed', true)->count();
            
            $totalObjectives = $cycles->sum(fn($cycle) => $cycle->objectives->count());
            $completedObjectives = $cycles->sum(fn($cycle) => $cycle->objectives->where('performance.is_completed', true)->count());
            
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
                $cycleCompletedObjectives = $cycleObjectives->where('performance.is_completed', true)->count();
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
            
            OkrPerformance::create([
                'okr_id' => $okr->id,
                'team_id' => $okr->team_id,
                'user_id' => $okr->user_id,
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
            ]);
        }
        
        $this->info("Updated {$okrs->count()} OKR performances");
    }
}
