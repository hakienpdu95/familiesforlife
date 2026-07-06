<?php

namespace Modules\Survey\Database\Seeders;

use Illuminate\Database\Seeder;

class SurveyDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SurveyPermissionSeeder::class,
            AiReadinessSurveySeeder::class,
            ScoringConfigSeeder::class,
            SpecializedSurveySetSeeder::class,
        ]);
    }
}
