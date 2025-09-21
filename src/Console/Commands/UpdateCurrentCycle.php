<?php

namespace Platform\Okr\Console\Commands;

use Platform\Okr\Models\CycleTemplate;
use Illuminate\Console\Command;

class UpdateCurrentCycle extends Command
{
    protected $signature = 'okr:update-current-cycle 
                            {--type=quarter : Type of cycle to update (quarter, annual, monthly)}
                            {--force : Force update even if no current cycle found}';

    protected $description = 'Update the current cycle based on today\'s date';

    public function handle()
    {
        $type = $this->option('type');
        $force = $this->option('force');

        $this->info("Updating current {$type} cycle...");

        try {
            // Alle aktuellen Cycles zurücksetzen
            CycleTemplate::where('type', $type)->update(['is_current' => false]);

            // Aktuellen Cycle finden
            $current = CycleTemplate::where('type', $type)
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now())
                ->first();

            if ($current) {
                $current->update(['is_current' => true]);
                $this->info("✅ Current cycle updated to: {$current->label}");
                $this->line("Period: {$current->starts_at->format('d.m.Y')} - {$current->ends_at->format('d.m.Y')}");
            } else {
                if ($force) {
                    // Nächsten verfügbaren Cycle setzen
                    $next = CycleTemplate::where('type', $type)
                        ->where('starts_at', '>', now())
                        ->orderBy('starts_at')
                        ->first();

                    if ($next) {
                        $next->update(['is_current' => true]);
                        $this->warn("⚠️  No current cycle found. Set next cycle as current: {$next->label}");
                    } else {
                        $this->error("❌ No cycles found for type: {$type}");
                        return 1;
                    }
                } else {
                    $this->warn("⚠️  No current cycle found for type: {$type}");
                    $this->line("Use --force to set the next available cycle as current");
                    return 1;
                }
            }

        } catch (\Exception $e) {
            $this->error("❌ Failed to update current cycle: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
