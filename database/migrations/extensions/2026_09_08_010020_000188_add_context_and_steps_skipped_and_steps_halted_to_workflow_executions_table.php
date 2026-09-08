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
        Schema::table('workflow_executions', function (Blueprint $table) {
            if (!Schema::hasColumn('workflow_executions', 'context')) {
                $table->json('context')->nullable();
            }
            if (!Schema::hasColumn('workflow_executions', 'steps_skipped')) {
                $table->unsignedTinyInteger('steps_skipped')->default(0)->after('context');
            }
            if (!Schema::hasColumn('workflow_executions', 'steps_halted')) {
                $table->unsignedTinyInteger('steps_halted')->default(0)->after('steps_skipped');
            }
            if (!Schema::hasColumn('workflow_executions', 'steps_waiting')) {
                $table->unsignedTinyInteger('steps_waiting')->default(0)->after('steps_halted');
            }
            if (!Schema::hasColumn('workflow_executions', 'run_context')) {
                $table->text('run_context')->nullable()->after('steps_waiting');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workflow_executions', function (Blueprint $table) {
            $cols = array_filter(['context', 'steps_skipped', 'steps_halted', 'steps_waiting', 'run_context'], fn($c) => Schema::hasColumn('workflow_executions', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};