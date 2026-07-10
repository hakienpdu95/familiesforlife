<?php

namespace Modules\Aicem\Database\Seeders;

use Illuminate\Database\Seeder;

class AicemDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AicemPermissionSeeder::class,
            AicemDefaultWorkflowSeeder::class,
        ]);
    }
}
