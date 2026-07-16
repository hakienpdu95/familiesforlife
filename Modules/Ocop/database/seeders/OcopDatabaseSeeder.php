<?php

namespace Modules\Ocop\Database\Seeders;

use Illuminate\Database\Seeder;

class OcopDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OcopPermissionSeeder::class,
            OcopCategorySeeder::class,
        ]);
    }
}
