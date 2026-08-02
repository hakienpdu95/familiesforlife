<?php

namespace Modules\VideoIdeaExtractor\Database\Seeders;

use Illuminate\Database\Seeder;

class VideoIdeaExtractorDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            VideoIdeaExtractorPermissionSeeder::class,
        ]);
    }
}
