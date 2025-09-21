<?php

namespace Platform\Okr\Console\Commands;

use Platform\Okr\Models\CycleTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateCycleTemplates extends Command
{
    protected $signature = 'okr:generate-cycle-templates 
                            {--type=quarter : Type of cycles to generate (quarter, annual, monthly)}
                            {--years=3 : Number of years to generate}
                            {--update-current : Update current cycle status}';

    protected $description = 'Generate cycle templates for OKR system (quarters, annual, or monthly)';

    public function handle()
    {
        $type = $this->option('type');
        $years = (int) $this->option('years');
        $updateCurrent = $this->option('update-current');

        $this->info("Generating {$type} cycle templates for {$years} years...");

        try {
            match ($type) {
                'quarter' => $this->generateQuarterTemplates($years),
                'annual' => $this->generateAnnualTemplates($years),
                'monthly' => $this->generateMonthlyTemplates($years),
                default => $this->error("Unknown cycle type: {$type}. Supported types: quarter, annual, monthly")
            };

            if ($updateCurrent) {
                $this->updateCurrentCycles($type);
            }

            $this->info("✅ {$type} cycle templates generated successfully!");
            $this->line("Generated templates for {$years} years");

        } catch (\Exception $e) {
            $this->error("❌ Failed to generate cycle templates: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function generateQuarterTemplates(int $years): void
    {
        $startYear = now()->year;
        $endYear = $startYear + $years - 1;

        foreach (range($startYear, $endYear) as $year) {
            foreach ([1, 2, 3, 4] as $q) {
                [$start, $end] = $this->quarterDates($year, $q);
                $label = "Q$q/$year";

                if (CycleTemplate::where('label', $label)->exists()) {
                    continue;
                }

                CycleTemplate::create([
                    'label' => $label,
                    'starts_at' => $start,
                    'ends_at' => $end,
                    'type' => 'quarter',
                    'is_standard' => true,
                    'is_current' => false,
                ]);
            }
        }
    }

    protected function generateAnnualTemplates(int $years): void
    {
        $startYear = now()->year;
        $endYear = $startYear + $years - 1;

        foreach (range($startYear, $endYear) as $year) {
            $start = Carbon::create($year, 1, 1);
            $end = Carbon::create($year, 12, 31);
            $label = "Jahr $year";

            if (CycleTemplate::where('label', $label)->exists()) {
                continue;
            }

            CycleTemplate::create([
                'label' => $label,
                'starts_at' => $start,
                'ends_at' => $end,
                'type' => 'annual',
                'is_standard' => true,
                'is_current' => false,
            ]);
        }
    }

    protected function generateMonthlyTemplates(int $years): void
    {
        $startDate = now()->startOfYear();
        $endDate = $startDate->copy()->addYears($years)->endOfYear();

        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $monthStart = $currentDate->copy()->startOfMonth();
            $monthEnd = $currentDate->copy()->endOfMonth();
            
            $label = $monthStart->format('M Y');
            $year = $monthStart->year;
            $month = $monthStart->month;

            if (CycleTemplate::where('label', $label)->where('starts_at', $monthStart)->exists()) {
                $currentDate->addMonth();
                continue;
            }

            CycleTemplate::create([
                'label' => $label,
                'starts_at' => $monthStart,
                'ends_at' => $monthEnd,
                'type' => 'monthly',
                'is_standard' => true,
                'is_current' => false,
            ]);

            $currentDate->addMonth();
        }
    }

    protected function updateCurrentCycles(string $type): void
    {
        // Alle aktuellen Cycles zurücksetzen
        CycleTemplate::where('type', $type)->update(['is_current' => false]);

        // Aktuellen Cycle setzen
        $current = CycleTemplate::where('type', $type)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->first();

        if ($current) {
            $current->update(['is_current' => true]);
            $this->line("Current cycle set to: {$current->label}");
        } else {
            $this->warn("No current cycle found for type: {$type}");
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
