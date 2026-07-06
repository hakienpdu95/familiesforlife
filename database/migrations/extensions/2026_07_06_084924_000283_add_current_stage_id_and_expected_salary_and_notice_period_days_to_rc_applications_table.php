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
        Schema::table('rc_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('rc_applications', 'current_stage_id')) {
                $table->unsignedBigInteger('current_stage_id')->nullable();
            }
            if (!Schema::hasColumn('rc_applications', 'expected_salary')) {
                $table->decimal('expected_salary', 15, 2)->nullable()->after('current_stage_id');
            }
            if (!Schema::hasColumn('rc_applications', 'notice_period_days')) {
                $table->smallInteger('notice_period_days')->nullable()->after('expected_salary');
            }
            if (!Schema::hasColumn('rc_applications', 'is_disqualified')) {
                $table->boolean('is_disqualified')->default(false)->after('notice_period_days');
            }
            if (!Schema::hasColumn('rc_applications', 'disqualify_reason')) {
                $table->text('disqualify_reason')->nullable()->after('is_disqualified');
            }
            if (!Schema::hasColumn('rc_applications', 'assigned_to')) {
                $table->unsignedBigInteger('assigned_to')->nullable()->after('disqualify_reason');
            }
            if (!Schema::hasColumn('rc_applications', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('assigned_to');
            }
            if (!Schema::hasColumn('rc_applications', 'applied_at')) {
                $table->timestamp('applied_at')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasIndex('rc_applications', 'idx_rc_app_stage')) {
                $table->index(['current_stage_id', 'status'], 'idx_rc_app_stage');
            }
            if (!Schema::hasIndex('rc_applications', 'idx_rc_app_assigned')) {
                $table->index(['assigned_to', 'status'], 'idx_rc_app_assigned');
            }
            if (!Schema::hasIndex('rc_applications', 'idx_rc_app_disq')) {
                $table->index(['org_id', 'is_disqualified', 'created_at'], 'idx_rc_app_disq');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rc_applications', function (Blueprint $table) {
            $cols = array_filter(['current_stage_id', 'expected_salary', 'notice_period_days', 'is_disqualified', 'disqualify_reason', 'assigned_to', 'rejection_reason', 'applied_at'], fn($c) => Schema::hasColumn('rc_applications', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};