<?php

namespace Platform\Okr\Console\Commands;

use Platform\Okr\Models\Cycle;
use Platform\Okr\Models\CycleTemplate;
use Illuminate\Console\Command;

class UpdateCycleStatuses extends Command
{
    protected $signature = 'okr:update-cycle-statuses 
                            {--dry-run : Show what would be updated without actually updating}
                            {--ending-soon-days=7 : Number of days before end to mark as ending_soon}';

    protected $description = 'Update Cycle statuses based on their template dates (draft, active, ending_soon, past, completed)';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $endingSoonDays = (int) $this->option('ending-soon-days');
        $today = now()->startOfDay();

        $this->info('Updating Cycle statuses based on template dates...');
        $this->line("Ending soon threshold: {$endingSoonDays} days");

        $cycles = Cycle::whereNotNull('cycle_template_id')
            ->with('template')
            ->get();

        $stats = [
            'active' => 0,
            'ending_soon' => 0,
            'past' => 0,
            'draft' => 0,
            'completed' => 0,
            'unchanged' => 0,
        ];

        foreach ($cycles as $cycle) {
            if (!$cycle->template) {
                continue;
            }

            // Respektiere manuell gesetzte "completed" Status
            if ($cycle->status === 'completed') {
                $stats['unchanged']++;
                continue;
            }

            $startsAt = $cycle->template->starts_at->startOfDay();
            $endsAt = $cycle->template->ends_at->endOfDay();
            $daysUntilEnd = $today->diffInDays($endsAt, false); // Negative wenn bereits vorbei

            $newStatus = null;

            // Vergangen: ends_at < heute
            if ($endsAt->lt($today)) {
                $newStatus = 'past';
            }
            // Aktiv oder Endet bald: starts_at <= heute <= ends_at
            elseif ($startsAt->lte($today) && $endsAt->gte($today)) {
                // Endet bald: wenn nur noch X Tage oder weniger bis zum Ende
                if ($daysUntilEnd <= $endingSoonDays && $daysUntilEnd >= 0) {
                    $newStatus = 'ending_soon';
                } else {
                    $newStatus = 'active';
                }
            }
            // Zukünftig: starts_at > heute
            elseif ($startsAt->gt($today)) {
                $newStatus = 'draft';
            }

            if ($newStatus && $cycle->status !== $newStatus) {
                if (!$dryRun) {
                    $cycle->update(['status' => $newStatus]);
                }
                $stats[$newStatus]++;
                $this->line("  {$cycle->id}: {$cycle->status} → {$newStatus} ({$cycle->template->label})");
            } else {
                $stats['unchanged']++;
            }
        }

        $this->newLine();
        $this->info('Summary:');
        $this->line("  Active: {$stats['active']}");
        $this->line("  Ending Soon: {$stats['ending_soon']}");
        $this->line("  Past: {$stats['past']}");
        $this->line("  Draft: {$stats['draft']}");
        $this->line("  Completed (unchanged): {$stats['completed']}");
        $this->line("  Unchanged: {$stats['unchanged']}");

        if ($dryRun) {
            $this->warn('  (Dry run - no changes were made)');
        } else {
            $this->info('✅ Cycle statuses updated successfully!');
        }

        return 0;
    }
}

