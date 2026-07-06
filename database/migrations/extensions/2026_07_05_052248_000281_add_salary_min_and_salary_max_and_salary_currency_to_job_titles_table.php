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
        Schema::table('job_titles', function (Blueprint $table) {
            if (!Schema::hasColumn('job_titles', 'salary_min')) {
                $table->decimal('salary_min', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('job_titles', 'salary_max')) {
                $table->decimal('salary_max', 15, 2)->nullable()->after('salary_min');
            }
            if (!Schema::hasColumn('job_titles', 'salary_currency')) {
                $table->char('salary_currency', 3)->default('VND')->after('salary_max');
            }
            if (!Schema::hasColumn('job_titles', 'is_manager_role')) {
                $table->tinyInteger('is_manager_role')->default(0)->after('salary_currency');
            }
            if (!Schema::hasColumn('job_titles', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->after('is_manager_role')->comment('Thời gian xóa mềm');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_titles', function (Blueprint $table) {
            $cols = array_filter(['salary_min', 'salary_max', 'salary_currency', 'is_manager_role', 'deleted_at'], fn($c) => Schema::hasColumn('job_titles', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};