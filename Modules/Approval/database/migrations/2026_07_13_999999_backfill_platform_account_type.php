<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill `account_type = platform` cho các tài khoản nền tảng (organization_id=null)
 * đã tạo trước khi có case `AccountType::Platform` — trước đây các seeder
 * (AuthDatabaseSeeder, ContentModeratorSeeder, ContentReviewHierarchySeeder,
 * PlatformOpsViewerSeeder) và PlatformUserController không set account_type nên các
 * tài khoản này đang mang giá trị mặc định `free`, giống hệt tài khoản độc giả vãng lai.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Guard — thứ tự chạy migration trong môi trường dùng snapshot đã consolidate
        // (database/migrations/generated + extensions) có thể đưa migration này lên TRƯỚC
        // migration thêm cột `organization_id`/`account_type` vào `users`. Không có gì để
        // backfill khi cột chưa tồn tại (fresh install).
        if (! Schema::hasColumn('users', 'organization_id') || ! Schema::hasColumn('users', 'account_type')) {
            return;
        }

        DB::table('users')
            ->whereNull('organization_id')
            ->where('account_type', 'free')
            ->update(['account_type' => 'platform']);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'organization_id') || ! Schema::hasColumn('users', 'account_type')) {
            return;
        }

        DB::table('users')
            ->whereNull('organization_id')
            ->where('account_type', 'platform')
            ->update(['account_type' => 'free']);
    }
};
