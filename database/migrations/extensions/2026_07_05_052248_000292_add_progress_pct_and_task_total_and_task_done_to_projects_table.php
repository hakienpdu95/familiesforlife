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
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'progress_pct')) {
                $table->unsignedSmallInteger('progress_pct')->default(0);
            }
            if (!Schema::hasColumn('projects', 'task_total')) {
                $table->unsignedSmallInteger('task_total')->default(0)->after('progress_pct');
            }
            if (!Schema::hasColumn('projects', 'task_done')) {
                $table->unsignedSmallInteger('task_done')->default(0)->after('task_total');
            }
            if (!Schema::hasColumn('projects', 'vertical_code')) {
                $table->string('vertical_code', 50)->nullable()->index()->after('task_done');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $cols = array_filter(['progress_pct', 'task_total', 'task_done', 'vertical_code'], fn($c) => Schema::hasColumn('projects', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};