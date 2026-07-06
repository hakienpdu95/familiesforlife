<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workflow_execution_steps', function (Blueprint $table) {
            if (!Schema::hasColumn('workflow_execution_steps', 'condition_result')) {
                $table->boolean('condition_result')->nullable()->after('action_type');
            }
            if (!Schema::hasColumn('workflow_execution_steps', 'skip_reason')) {
                $table->string('skip_reason', 64)->nullable()->after('condition_result');
                // 'condition_failed' | 'halted_upstream' | 'parallel_skipped' | 'user_rejected'
            }
            if (!Schema::hasColumn('workflow_execution_steps', 'output_data')) {
                $table->text('output_data')->nullable()->after('error_message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workflow_execution_steps', function (Blueprint $table) {
            foreach (['condition_result','skip_reason','output_data'] as $col) {
                if (Schema::hasColumn('workflow_execution_steps', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
