<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Entry point mặc định của Laravel (`php artisan db:seed`).
 * Delegate hoàn toàn vào SystemDataSeeder để đảm bảo chỉ có 1 nguồn sự thật.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SystemDataSeeder::class);
    }
}
