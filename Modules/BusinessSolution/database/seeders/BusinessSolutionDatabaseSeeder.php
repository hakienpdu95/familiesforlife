<?php

namespace Modules\BusinessSolution\Database\Seeders;

use Illuminate\Database\Seeder;

class BusinessSolutionDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BusinessSolutionPermissionSeeder::class,
            VerticalSeeder::class,
            BusinessSolutionCatalogSeeder::class,
        ]);
    }
}
