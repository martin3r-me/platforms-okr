<?php

namespace Platform\Okr\Console\Commands;

use Platform\Okr\Models\CycleTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class MaintainCycleTemplates extends Command
{
    protected $signature = 'okr:maintain-cycles 
                            {--months-ahead=6 : Number of months to generate ahead}
                            {--update-current : Update current cycle status}';

    protected $description = 'Maintain OKR cycle templates - add new ones and update current cycle (for monthly cron job)';

    public function handle()
    {
        $monthsAhead = (int) $this->option('months-ahead');
        $updateCurrent = $this->option('update-current');

        $this->info('Maintaining OKR cycle templates...');

        try {
            // 1. Quarter-Templates auffüllen
            $this->maintainQuarterTemplates($monthsAhead);
            
            // 2. Annual-Templates auffüllen
            $this->maintainAnnualTemplates($monthsAhead);
            
            // 3. Monthly-Templates auffüllen
            $this->maintainMonthlyTemplates($monthsAhead);

            // 4. Current Cycles aktualisieren
            if ($updateCurrent) {
                $this->updateAllCurrentCycles();
            }

            $this->info('✅ OKR cycle templates maintained successfully!');

        } catch (\Exception $e) {
            $this->error("❌ Failed to maintain cycle templates: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function maintainQuarterTemplates(int $monthsAhead): void
    {
        $endDate = now()->addMonths($monthsAhead);
        $currentYear = now()->year;
        $endYear = $endDate->year;

        $added = 0;

        foreach (range($currentYear, $endYear) as $year) {
            foreach ([1, 2, 3, 4] as $q) {
                [$start, $end] = $this->quarterDates($year, $q);
                
                // Nur Templates für die Zukunft hinzufügen
                if ($start->isFuture()) {
                    $label = "Q$q/$year";

                    if (!CycleTemplate::where('label', $label)->exists()) {
                        CycleTemplate::create([
                            'label' => $label,
                            'starts_at' => $start,
                            'ends_at' => $end,
                            'type' => 'quarter',
                            'is_standard' => true,
                            'is_current' => false,
                        ]);
                        $added++;
                    }
                }
            }
        }

        if ($added > 0) {
            $this->line("Added {$added} new quarter templates");
        }
    }

    protected function maintainAnnualTemplates(int $monthsAhead): void
    {
        $endDate = now()->addMonths($monthsAhead);
        $currentYear = now()->year;
        $endYear = $endDate->year;

        $added = 0;

        foreach (range($currentYear, $endYear) as $year) {
            $start = Carbon::create($year, 1, 1);
            $end = Carbon::create($year, 12, 31);
            $label = "Jahr $year";

            // Nur Templates für die Zukunft hinzufügen
            if ($start->isFuture() && !CycleTemplate::where('label', $label)->exists()) {
                CycleTemplate::create([
                    'label' => $label,
                    'starts_at' => $start,
                    'ends_at' => $end,
                    'type' => 'annual',
                    'is_standard' => true,
                    'is_current' => false,
                ]);
                $added++;
            }
        }

        if ($added > 0) {
            $this->line("Added {$added} new annual templates");
        }
    }

    protected function maintainMonthlyTemplates(int $monthsAhead): void
    {
        $endDate = now()->addMonths($monthsAhead);
        $currentDate = now()->startOfMonth();
        $added = 0;

        while ($currentDate->lte($endDate)) {
            $monthStart = $currentDate->copy()->startOfMonth();
            $monthEnd = $currentDate->copy()->endOfMonth();
            $label = $monthStart->format('M Y');

            // Nur Templates für die Zukunft hinzufügen
            if ($monthStart->isFuture() && !CycleTemplate::where('label', $label)->where('starts_at', $monthStart)->exists()) {
                CycleTemplate::create([
                    'label' => $label,
                    'starts_at' => $monthStart,
                    'ends_at' => $monthEnd,
                    'type' => 'monthly',
                    'is_standard' => true,
                    'is_current' => false,
                ]);
                $added++;
            }

            $currentDate->addMonth();
        }

        if ($added > 0) {
            $this->line("Added {$added} new monthly templates");
        }
    }

    protected function updateAllCurrentCycles(): void
    {
        $types = ['quarter', 'annual', 'monthly'];
        $updated = 0;

        foreach ($types as $type) {
            // Alle aktuellen Cycles zurücksetzen
            CycleTemplate::where('type', $type)->update(['is_current' => false]);

            // Aktuellen Cycle finden
            $current = CycleTemplate::where('type', $type)
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now())
                ->first();

            if ($current) {
                $current->update(['is_current' => true]);
                $this->line("Updated current {$type} cycle: {$current->label}");
                $updated++;
            }
        }

        if ($updated === 0) {
            $this->warn('No current cycles found to update');
        }
    }

    protected function quarterDates(int $year, int $quarter): array
    {
        return match ($quarter) {
            1 => [Carbon::create($year, 1, 1), Carbon::create($year, 3, 31)],
            2 => [Carbon::create($year, 4, 1), Carbon::create($year, 6, 30)],
            3 => [Carbon::create($year, 7, 1), Carbon::create($year, 9, 30)],
            4 => [Carbon::create($year, 10, 1), Carbon::create($year, 12, 31)],
        };
    }
}
