<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username', 30)->nullable()->unique()->after('email')->comment('Tên đăng nhập: a-z 0-9 . _ - (lowercase)');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable()->after('username')->comment('Số điện thoại (0xxxxxxxxx)');
            }
            if (!Schema::hasColumn('users', 'job_title')) {
                $table->string('job_title', 100)->nullable()->after('phone')->comment('Chức danh');
            }
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department', 100)->nullable()->index()->after('job_title')->comment('Phòng ban');
            }
            if (!Schema::hasColumn('users', 'last_active_at')) {
                $table->timestamp('last_active_at')->nullable()->after('department')->comment('Lần hoạt động cuối cùng');
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->index()->after('last_active_at')->comment('Trạng thái tài khoản');
            }
            if (!Schema::hasColumn('users', 'account_type')) {
                $table->string('account_type', 20)->default('free')->after('is_active')->comment('free | platform | suspended');
            }
            if (!Schema::hasColumn('users', 'trust_level')) {
                $table->unsignedTinyInteger('trust_level')->default(0)->after('account_type')->comment('0=unverified, 1=email, 3=cccd, 4=cccd_biometric');
            }
            if (!Schema::hasColumn('users', 'national_id_hash')) {
                $table->string('national_id_hash', 64)->nullable()->unique()->after('trust_level')->comment('SHA-256(số_CCCD) — check uniqueness, không lưu số thật');
            }
            if (!Schema::hasIndex('users', 'users_account_type_index')) {
                $table->index('account_type', 'users_account_type_index');
            }
            if (!Schema::hasIndex('users', 'users_trust_level_index')) {
                $table->index('trust_level', 'users_trust_level_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = array_filter(['username', 'phone', 'job_title', 'department', 'last_active_at', 'is_active', 'account_type', 'trust_level', 'national_id_hash'], fn($c) => Schema::hasColumn('users', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};