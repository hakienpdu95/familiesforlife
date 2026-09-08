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
            if (!Schema::hasColumn('users', 'organization_id')) {
                $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete()->after('email')->comment('Thuộc tổ chức nào — null nếu super-admin');
            }
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department', 50)->nullable()->index()->after('organization_id')->comment('Phòng ban: hr, sales, ops, marketing');
            }
            if (!Schema::hasColumn('users', 'last_active_at')) {
                $table->timestamp('last_active_at')->nullable()->after('department')->comment('Lần hoạt động cuối cùng');
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->index()->after('last_active_at')->comment('Trạng thái tài khoản');
            }
            if (!Schema::hasIndex('users', 'users_organization_id_index')) {
                $table->index('organization_id');
            }
            if (!Schema::hasColumn('users', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('users', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('branch_id');
            }
            if (!Schema::hasColumn('users', 'account_type')) {
                $table->string('account_type', 20)->default('free')->after('department_id')->comment('free | org_member | suspended');
            }
            if (!Schema::hasColumn('users', 'current_org_id')) {
                $table->unsignedBigInteger('current_org_id')->nullable()->after('account_type')->comment('NULL nếu free');
            }
            if (!Schema::hasColumn('users', 'trust_level')) {
                $table->unsignedTinyInteger('trust_level')->default(0)->after('current_org_id')->comment('0=unverified, 1=email, 3=cccd, 4=cccd_biometric');
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
            if (Schema::hasColumn('users', 'organization_id')) $table->dropForeign(['organization_id']);
            $cols = array_filter(['organization_id', 'department', 'last_active_at', 'is_active', 'branch_id', 'department_id', 'account_type', 'current_org_id', 'trust_level', 'national_id_hash'], fn($c) => Schema::hasColumn('users', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};