<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workflow_executions', function (Blueprint $table) {
            if (!Schema::hasColumn('workflow_executions', 'steps_skipped')) {
                $table->tinyInteger('steps_skipped')->unsigned()->default(0)->after('steps_scheduled');
            }
            if (!Schema::hasColumn('workflow_executions', 'steps_halted')) {
                $table->tinyInteger('steps_halted')->unsigned()->default(0)->after('steps_skipped');
            }
            if (!Schema::hasColumn('workflow_executions', 'steps_waiting')) {
                $table->tinyInteger('steps_waiting')->unsigned()->default(0)->after('steps_halted');
            }
            if (!Schema::hasColumn('workflow_executions', 'run_context')) {
                $table->text('run_context')->nullable()->after('steps_waiting');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workflow_executions', function (Blueprint $table) {
            foreach (['steps_skipped','steps_halted','steps_waiting','run_context'] as $col) {
                if (Schema::hasColumn('workflow_executions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
