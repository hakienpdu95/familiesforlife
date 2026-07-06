<?php

namespace Modules\ActivityLog\Database\Seeders;

use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ActivityLogPermissionsSeeder extends Seeder
{
    private const PERMISSIONS = [
        'activitylog.view',          // xem danh sách + chi tiết log
        'activitylog.export',        // xuất Excel
        'activitylog.manage_alerts', // quản lý alert rules
    ];

    private const ADMIN_ROLES = [
        RoleEnum::ADMIN->value, // 'system_admin'
        RoleEnum::CEO->value,   // 'ceo'
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        foreach (self::ADMIN_ROLES as $roleName) {
            $role = Role::where('name', $roleName)->first();
            $role?->givePermissionTo(self::PERMISSIONS);
        }
    }
}
