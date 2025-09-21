<?php

namespace Platform\Okr\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Okr\Models\CycleTemplate;
use Illuminate\Support\Carbon;

class OkrCycleTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $startYear = now()->year;
        $endYear = $startYear + 2; // 3 Jahre voraus

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

        // Aktuelles Quartal markieren
        CycleTemplate::where('type', 'quarter')->update(['is_current' => false]);

        CycleTemplate::where('type', 'quarter')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->update(['is_current' => true]);
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
