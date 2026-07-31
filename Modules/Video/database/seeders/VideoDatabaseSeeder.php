<?php

namespace Modules\Video\Database\Seeders;

use Illuminate\Database\Seeder;

class VideoDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            VideoPermissionSeeder::class,
        ]);
    }
}
