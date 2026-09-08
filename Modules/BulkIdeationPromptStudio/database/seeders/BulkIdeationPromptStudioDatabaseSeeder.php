<?php

namespace Modules\BulkIdeationPromptStudio\Database\Seeders;

use Illuminate\Database\Seeder;

class BulkIdeationPromptStudioDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BulkIdeationPromptStudioPermissionSeeder::class,
        ]);
    }
}
