<?php

namespace Modules\ProvinceShowcase\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ProvincePermissionSeeder extends Seeder
{
    private const PERMISSIONS = ['province.manage'];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        foreach (['platform_ops', 'platform_content_head'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()?->givePermissionTo('province.manage');
        }

        Role::where('name', 'super-admin')->where('guard_name', 'web')->first()?->syncPermissions(Permission::all());

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info('  ✓ Province permissions seeded — platform_ops/platform_content_head.');
    }
}
