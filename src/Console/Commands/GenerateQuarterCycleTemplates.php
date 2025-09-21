<?php

namespace Platform\Okr\Console\Commands;

use Platform\Okr\Models\CycleTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateQuarterCycleTemplates extends Command
{
    protected $signature = 'okr:generate-quarter-templates';
    protected $description = 'Generiert quartalsweise CycleTemplates für die nächsten 2 Jahre';

    public function handle()
    {
        $startYear = now()->year;
        $endYear = $startYear + 1; // → ergibt insgesamt 2 Jahre: z. B. 2025 + 2026

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
                    'is_current' => false, // wird später gesetzt
                ]);
            }
        }

        // Aktuelles Quartal markieren
        CycleTemplate::where('type', 'quarter')->update(['is_current' => false]);

        CycleTemplate::where('type', 'quarter')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->update(['is_current' => true]);

        $this->info('Quartalsweise CycleTemplates für 2 Jahre erfolgreich generiert.');
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
