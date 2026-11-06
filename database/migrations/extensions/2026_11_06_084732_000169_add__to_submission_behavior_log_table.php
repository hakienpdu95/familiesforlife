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
        Schema::table('submission_behavior_log', function (Blueprint $table) {
            if (!Schema::hasIndex('submission_behavior_log', 'behavior_logs_response_occurred_idx')) {
                $table->index(['response_id', 'occurred_at'], 'behavior_logs_response_occurred_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('submission_behavior_log', function (Blueprint $table) {
            $cols = array_filter([], fn($c) => Schema::hasColumn('submission_behavior_log', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};