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
        Schema::table('scoring_feedback', function (Blueprint $table) {
            if (!Schema::hasColumn('scoring_feedback', 'submitted_by')) {
                $table->unsignedBigInteger('submitted_by')->nullable();
            }
            if (!Schema::hasColumn('scoring_feedback', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('scoring_feedback', function (Blueprint $table) {
            $cols = array_filter(['submitted_by', 'submitted_at'], fn($c) => Schema::hasColumn('scoring_feedback', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};