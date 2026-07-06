<?php

namespace Modules\OrganizationSolution\Database\Seeders;

use Illuminate\Database\Seeder;

class OrganizationSolutionDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OrganizationSolutionPermissionSeeder::class,
        ]);
    }
}
