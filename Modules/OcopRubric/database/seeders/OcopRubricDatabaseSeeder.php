<?php

namespace Modules\OcopRubric\Database\Seeders;

use Illuminate\Database\Seeder;

class OcopRubricDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            OcopRubricPermissionSeeder::class,
            OcopStarBandSeeder::class,
            OcopProductGroupSeeder::class,
            OcopRubricVersionSeeder::class,
        ]);
    }
}
