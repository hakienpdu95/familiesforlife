<?php

namespace Modules\OcopSubject\Database\Seeders;

use Illuminate\Database\Seeder;

class OcopSubjectDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OcopSubjectPermissionSeeder::class,
        ]);
    }
}
