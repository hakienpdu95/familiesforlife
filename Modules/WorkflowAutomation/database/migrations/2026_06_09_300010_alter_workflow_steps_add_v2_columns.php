<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workflow_steps', function (Blueprint $table) {
            if (!Schema::hasColumn('workflow_steps', 'group_id')) {
                $table->unsignedBigInteger('group_id')->nullable()->after('workflow_id');
            }
            if (!Schema::hasColumn('workflow_steps', 'step_key')) {
                $table->string('step_key', 64)->nullable()->after('group_id');
            }
            if (!Schema::hasColumn('workflow_steps', 'label')) {
                $table->string('label', 191)->nullable()->after('step_key');
            }
            if (!Schema::hasColumn('workflow_steps', 'step_type')) {
                $table->tinyInteger('step_type')->unsigned()->default(1)->after('label');
                // 1=automated 2=user_task 3=control
            }
            if (!Schema::hasColumn('workflow_steps', 'action_config')) {
                $table->text('action_config')->nullable()->after('action_type');
            }
            if (!Schema::hasColumn('workflow_steps', 'condition_config')) {
                $table->text('condition_config')->nullable()->after('action_config');
            }
            if (!Schema::hasColumn('workflow_steps', 'step_output_key')) {
                $table->string('step_output_key', 64)->nullable()->after('condition_config');
            }
            if (!Schema::hasColumn('workflow_steps', 'halt_on_fail')) {
                $table->boolean('halt_on_fail')->default(false)->after('step_output_key');
            }
            if (!Schema::hasColumn('workflow_steps', 'retry_times')) {
                $table->tinyInteger('retry_times')->unsigned()->default(3)->after('halt_on_fail');
            }
            if (!Schema::hasColumn('workflow_steps', 'timeout_seconds')) {
                $table->unsignedSmallInteger('timeout_seconds')->default(30)->after('retry_times');
            }
        });
        try {
            Schema::table('workflow_steps', function (Blueprint $table) {
                $table->index('group_id');
            });
        } catch (\Throwable) {}
    }

    public function down(): void
    {
        Schema::table('workflow_steps', function (Blueprint $table) {
            $cols = ['group_id','step_key','label','step_type','action_config',
                     'condition_config','step_output_key','halt_on_fail','retry_times','timeout_seconds'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('workflow_steps', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
