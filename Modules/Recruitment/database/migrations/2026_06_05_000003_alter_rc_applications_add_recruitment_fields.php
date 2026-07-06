<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rc_applications', function (Blueprint $table) {
            $table->unsignedBigInteger('current_stage_id')->nullable()->after('org_id');
            $table->decimal('expected_salary', 15, 2)->nullable()->after('cover_letter');
            $table->smallInteger('notice_period_days')->nullable()->after('expected_salary');
            $table->boolean('is_disqualified')->default(false)->after('notice_period_days');
            $table->text('disqualify_reason')->nullable()->after('is_disqualified');
            $table->unsignedBigInteger('assigned_to')->nullable()->after('disqualify_reason');
            $table->text('rejection_reason')->nullable()->after('assigned_to');
            $table->timestamp('applied_at')->nullable()->after('rejection_reason');

            $table->index(['current_stage_id', 'status'], 'idx_rc_app_stage');
            $table->index(['assigned_to', 'status'], 'idx_rc_app_assigned');
            $table->index(['org_id', 'is_disqualified', 'created_at'], 'idx_rc_app_disq');
        });
    }

    public function down(): void
    {
        Schema::table('rc_applications', function (Blueprint $table) {
            $table->dropIndex('idx_rc_app_stage');
            $table->dropIndex('idx_rc_app_assigned');
            $table->dropIndex('idx_rc_app_disq');
            $table->dropColumn([
                'current_stage_id', 'expected_salary', 'notice_period_days',
                'is_disqualified', 'disqualify_reason', 'assigned_to',
                'rejection_reason', 'applied_at',
            ]);
        });
    }
};
