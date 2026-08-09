<?php

namespace Modules\AIVideoStudioTemplate\Database\Seeders;

use Illuminate\Database\Seeder;

class AIVideoStudioTemplateDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AIVideoStudioTemplatePermissionSeeder::class,
        ]);
    }
}
