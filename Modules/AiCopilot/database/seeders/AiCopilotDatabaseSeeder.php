<?php

namespace Modules\AiCopilot\Database\Seeders;

use Illuminate\Database\Seeder;

class AiCopilotDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SystemAgentsSeeder::class,
            SystemPromptsSeeder::class,
        ]);
    }
}
