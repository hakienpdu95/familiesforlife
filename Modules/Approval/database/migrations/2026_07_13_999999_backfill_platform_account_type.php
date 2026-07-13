<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
        DB::table('users')
            ->whereNull('organization_id')
            ->where('account_type', 'free')
            ->update(['account_type' => 'platform']);
    }

    public function down(): void
    {
        DB::table('users')
            ->whereNull('organization_id')
            ->where('account_type', 'platform')
            ->update(['account_type' => 'free']);
    }
};
