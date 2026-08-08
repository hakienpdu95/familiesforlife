<?php

namespace Modules\Playlist\Database\Seeders;

use Illuminate\Database\Seeder;

class PlaylistDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlaylistPermissionSeeder::class,
        ]);
    }
}
