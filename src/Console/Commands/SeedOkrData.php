<?php

namespace Platform\Okr\Console\Commands;

use Illuminate\Console\Command;
use Platform\Okr\Database\Seeders\OkrLookupSeeder;
use Platform\Okr\Database\Seeders\OkrDemoDataSeeder;

class SeedOkrData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'okr:seed-data 
                            {--lookup : Seed only lookup data (cycle templates)}
                            {--demo : Seed demo data (sample OKRs)}
                            {--force : Force the operation to run even in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the OKR module data (cycle templates and demo data)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Seeding OKR data...');
        
        try {
            // Lookup data (immer erlaubt)
            if ($this->option('lookup') || !$this->option('demo')) {
                $this->info('Seeding lookup data...');
                $seeder = new OkrLookupSeeder();
                $seeder->run();
                $this->info('✅ OKR lookup data seeded successfully!');
            }

            // Demo data (nur wenn explizit gewünscht oder in non-production)
            if ($this->option('demo') || (!$this->option('lookup') && !app()->environment('production'))) {
                if (app()->environment('production') && !$this->option('force')) {
                    $this->warn('Demo data seeding is disabled in production. Use --force to override.');
                } else {
                    $this->info('Seeding demo data...');
                    $seeder = new OkrDemoDataSeeder();
                    $seeder->run();
                    $this->info('✅ OKR demo data seeded successfully!');
                }
            }
            
            $this->line('');
            $this->line('Seeded data:');
            if ($this->option('lookup') || !$this->option('demo')) {
                $this->line('  • Cycle Templates (Q1-Q4 for 3 years)');
            }
            if ($this->option('demo') || (!$this->option('lookup') && !app()->environment('production'))) {
                $this->line('  • Demo OKR with Objectives and Key Results');
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to seed OKR data: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
