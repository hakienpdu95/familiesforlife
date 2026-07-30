<?php

namespace Modules\AccessTrade\Database\Seeders;

use Illuminate\Database\Seeder;

class AccessTradeDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AccessTradePermissionSeeder::class,
        ]);
    }
}
