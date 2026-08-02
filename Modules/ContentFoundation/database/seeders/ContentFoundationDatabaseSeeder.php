<?php

namespace Modules\ContentFoundation\Database\Seeders;

use Illuminate\Database\Seeder;

class ContentFoundationDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ContentFoundationPermissionSeeder::class,
        ]);
    }
}
