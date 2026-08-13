<?php

namespace Modules\EntityComparison\Database\Seeders;

use Illuminate\Database\Seeder;

class EntityComparisonDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EntityComparisonPermissionSeeder::class,
        ]);
    }
}
