<?php

namespace Platform\Okr\Database\Seeders;

use Illuminate\Database\Seeder;

class OkrLookupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            OkrCycleTemplateSeeder::class,
        ]);
    }
}
