<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_titles', function (Blueprint $table) {
            $table->decimal('salary_min', 15, 2)->nullable()->after('description');
            $table->decimal('salary_max', 15, 2)->nullable()->after('salary_min');
            $table->char('salary_currency', 3)->default('VND')->after('salary_max');
            $table->tinyInteger('is_manager_role')->default(0)->after('salary_currency');
        });
    }

    public function down(): void
    {
        Schema::table('job_titles', function (Blueprint $table) {
            $table->dropColumn(['salary_min', 'salary_max', 'salary_currency', 'is_manager_role']);
        });
    }
};
