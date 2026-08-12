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
        Schema::table('workflow_execution_steps', function (Blueprint $table) {
            if (!Schema::hasColumn('workflow_execution_steps', 'condition_result')) {
                $table->boolean('condition_result')->nullable();
            }
            if (!Schema::hasColumn('workflow_execution_steps', 'skip_reason')) {
                $table->string('skip_reason', 64)->nullable()->after('condition_result');
            }
            if (!Schema::hasColumn('workflow_execution_steps', 'output_data')) {
                $table->text('output_data')->nullable()->after('skip_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workflow_execution_steps', function (Blueprint $table) {
            $cols = array_filter(['condition_result', 'skip_reason', 'output_data'], fn($c) => Schema::hasColumn('workflow_execution_steps', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};