<?php

namespace Modules\Lead\Database\Seeders;

use Illuminate\Database\Seeder;

class LeadDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            LeadPipelineStagesSeeder::class,
            LeadSourcesSeeder::class,
        ]);
    }
}
