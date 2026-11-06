<?php

namespace Modules\ActivityLog\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ActivityLogPermissionsSeeder extends Seeder
{
    private const PERMISSIONS = [
        'activitylog.view',          // xem danh sách + chi tiết log
        'activitylog.export',        // xuất Excel
        'activitylog.manage_alerts', // quản lý alert rules
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }
}
