<?php

namespace Modules\Heritage\Database\Seeders;

use Illuminate\Database\Seeder;

class HeritageDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            HeritagePermissionSeeder::class,
        ]);
    }
}
