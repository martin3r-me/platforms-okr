<?php

namespace Platform\Okr\Console\Commands;

use Illuminate\Console\Command;
use Platform\Okr\Services\KeyResultMeasureSyncService;

class SyncKeyResultMeasuresCommand extends Command
{
    protected $signature = 'okr:sync-measures';

    protected $description = 'Synct alle dynamischen KR-Measures gegen ihre Provider und bewertet betroffene Key Results neu.';

    public function handle(KeyResultMeasureSyncService $sync): int
    {
        $updated = $sync->syncAll();
        $this->info("KR-Measures synchronisiert: {$updated} aktualisiert.");

        return self::SUCCESS;
    }
}
