<?php

namespace Modules\Approval\Database\Seeders;

use Illuminate\Database\Seeder;

class ApprovalDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            ApprovalPermissionSeeder::class,
            ContentModeratorSeeder::class,
            ContentReviewHierarchySeeder::class,
        ]);
    }
}
