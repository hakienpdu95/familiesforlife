<?php

namespace Modules\OcopSubject\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class OcopSubjectPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'ocop_subject.manage', 'guard_name' => 'web']);

        foreach (['platform_ops', 'platform_content_head'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()?->givePermissionTo('ocop_subject.manage');
        }

        Role::where('name', 'super-admin')->where('guard_name', 'web')->first()?->syncPermissions(Permission::all());

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info('  ✓ OcopSubject permissions seeded — platform_ops/platform_content_head.');
    }
}
