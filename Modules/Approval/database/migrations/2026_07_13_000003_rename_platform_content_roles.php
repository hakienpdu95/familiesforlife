<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Đổi tên slug role platform để nhất quán tiền tố `platform_*` với `platform_ops`/
 * `platform_viewer` mới — spec/Platform_RBAC_Technical_Specification.md §3.5.
 * KHÔNG đổi `super-admin` (xem §0 — lý do: blast radius quá lớn, tên đã đủ rõ nghĩa).
 */
return new class extends Migration
{
    private const MAP = [
        'content_moderator' => 'platform_content_moderator',
        'content_editor'    => 'platform_content_editor',
        'content_head'      => 'platform_content_head',
    ];

    public function up(): void
    {
        foreach (self::MAP as $old => $new) {
            DB::table('roles')->where('name', $old)->where('guard_name', 'web')->update(['name' => $new]);
        }

        // BẮT BUỘC — update qua query builder không tự bắn Eloquent model event nên không tự
        // flush cache Spatie (config/permission.php: cache 24h). Xem §3.2.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (self::MAP as $old => $new) {
            DB::table('roles')->where('name', $new)->where('guard_name', 'web')->update(['name' => $old]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
