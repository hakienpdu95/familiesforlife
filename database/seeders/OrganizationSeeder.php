<?php

namespace Database\Seeders;

use App\Shared\Tenancy\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        Organization::firstOrCreate(
            ['slug' => 'demo'],
            [
                'name'   => 'Demo Organization',
                'slug'   => 'demo',
                'status' => 'active',
                'settings' => [
                    'timezone' => 'Asia/Ho_Chi_Minh',
                    'locale'   => 'vi',
                ],
            ]
        );
    }
}