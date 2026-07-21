<?php

namespace Modules\ContentBrief\Database\Seeders;

use Illuminate\Database\Seeder;

class ContentBriefDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ContentBriefPermissionSeeder::class,
        ]);
    }
}
