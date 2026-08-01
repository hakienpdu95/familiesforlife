<?php

namespace Modules\ContentCalendar\Database\Seeders;

use Illuminate\Database\Seeder;

class ContentCalendarDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ContentCalendarPermissionSeeder::class,
        ]);
    }
}
