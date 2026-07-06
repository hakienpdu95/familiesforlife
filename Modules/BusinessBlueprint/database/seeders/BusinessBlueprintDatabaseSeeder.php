<?php

namespace Modules\BusinessBlueprint\Database\Seeders;

use Illuminate\Database\Seeder;

class BusinessBlueprintDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BusinessBlueprintPermissionSeeder::class,
            TxngBlueprintSeeder::class,
        ]);
    }
}
