<?php

namespace Platform\Okr\Console\Commands;

use Illuminate\Console\Command;
use Platform\Okr\Database\Seeders\OkrLookupSeeder;

class SeedOkrLookupData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'okr:seed-lookup-data 
                            {--force : Force the operation to run even in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the OKR module lookup data (cycle templates)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Seeding OKR lookup data...');
        
        try {
            $seeder = new OkrLookupSeeder();
            $seeder->run();
            
            $this->info('✅ OKR lookup data seeded successfully!');
            $this->line('');
            $this->line('Seeded data:');
            $this->line('  • Cycle Templates (Q1-Q4 for 3 years)');
            $this->line('  • Current quarter marked as active');
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to seed OKR lookup data: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
