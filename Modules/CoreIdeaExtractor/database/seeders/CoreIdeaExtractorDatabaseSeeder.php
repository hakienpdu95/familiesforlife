<?php

namespace Modules\CoreIdeaExtractor\Database\Seeders;

use Illuminate\Database\Seeder;

class CoreIdeaExtractorDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CoreIdeaExtractorPermissionSeeder::class,
        ]);
    }
}
