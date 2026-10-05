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
        Schema::table('organization_members', function (Blueprint $table) {
            if (!Schema::hasColumn('organization_members', 'status')) {
                $table->string('status', 20)->default('active')->comment('active | inactive | paused | suspended');
            }
            if (!Schema::hasColumn('organization_members', 'left_at')) {
                $table->timestamp('left_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('organization_members', 'exit_reason')) {
                $table->string('exit_reason', 50)->nullable()->after('left_at')->comment('resigned | terminated | retired | contract_end | internal_transfer');
            }
            if (!Schema::hasColumn('organization_members', 'exit_initiated_by')) {
                $table->string('exit_initiated_by', 20)->nullable()->after('exit_reason')->comment('self | hr | system');
            }
            if (!Schema::hasColumn('organization_members', 'job_title_at_exit')) {
                $table->string('job_title_at_exit', 200)->nullable()->after('exit_initiated_by');
            }
            if (!Schema::hasColumn('organization_members', 'department_at_exit')) {
                $table->string('department_at_exit', 200)->nullable()->after('job_title_at_exit');
            }
            if (!Schema::hasColumn('organization_members', 'role_at_exit')) {
                $table->string('role_at_exit', 50)->nullable()->after('department_at_exit');
            }
            if (!Schema::hasColumn('organization_members', 'account_was_org_created')) {
                $table->tinyInteger('account_was_org_created')->default(0)->after('role_at_exit')->comment('1 nếu org tạo tài khoản này (không phải user tự đăng ký)');
            }
            if (!Schema::hasColumn('organization_members', 'contract_end_date')) {
                $table->date('contract_end_date')->nullable()->after('account_was_org_created')->comment('Ngày hết hợp đồng — hệ thống auto-suspend nếu HR không offboard trước');
            }
            if (!Schema::hasColumn('organization_members', 'auto_suspended_at')) {
                $table->timestamp('auto_suspended_at')->nullable()->after('contract_end_date')->comment('Thời điểm hệ thống tự suspend do hết contract_end_date');
            }
            if (!Schema::hasColumn('organization_members', 'last_active_at')) {
                $table->timestamp('last_active_at')->nullable()->after('auto_suspended_at')->comment('Lần cuối user có activity trong org này (login + action)');
            }
            if (!Schema::hasColumn('organization_members', 'effective_left_at')) {
                $table->timestamp('effective_left_at')->nullable()->after('last_active_at')->comment('Ngày thực tế nghỉ việc — do HR nhập, có thể khác left_at');
            }
            if (!Schema::hasColumn('organization_members', 'offboarded_at')) {
                $table->timestamp('offboarded_at')->nullable()->after('effective_left_at')->comment('Ngày HR click offboard trên hệ thống');
            }
            if (!Schema::hasColumn('organization_members', 'late_offboard_gap_days')) {
                $table->unsignedSmallInteger('late_offboard_gap_days')->nullable()->after('offboarded_at')->comment('Số ngày gap giữa effective_left_at và offboarded_at — 0 nếu offboard đúng hạn');
            }
            if (!Schema::hasIndex('organization_members', 'om_status_index')) {
                $table->index('status', 'om_status_index');
            }
            if (!Schema::hasIndex('organization_members', 'om_left_at_index')) {
                $table->index('left_at', 'om_left_at_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('organization_members', function (Blueprint $table) {
            $cols = array_filter(['status', 'left_at', 'exit_reason', 'exit_initiated_by', 'job_title_at_exit', 'department_at_exit', 'role_at_exit', 'account_was_org_created', 'contract_end_date', 'auto_suspended_at', 'last_active_at', 'effective_left_at', 'offboarded_at', 'late_offboard_gap_days'], fn($c) => Schema::hasColumn('organization_members', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};