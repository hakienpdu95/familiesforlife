<?php

namespace Modules\VideoSeriesPromptStudio\Database\Seeders;

use Illuminate\Database\Seeder;

class VideoSeriesPromptStudioDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            VideoSeriesPromptStudioPermissionSeeder::class,
        ]);
    }
}
