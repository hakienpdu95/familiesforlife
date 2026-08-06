<?php

namespace Modules\ContentOutlines\Database\Seeders;

use Illuminate\Database\Seeder;

class ContentOutlinesDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ContentOutlinesPermissionSeeder::class,
        ]);
    }
}
