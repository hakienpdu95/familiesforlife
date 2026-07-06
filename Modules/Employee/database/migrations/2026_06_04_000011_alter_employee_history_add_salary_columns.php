<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_history', function (Blueprint $table) {
            $table->decimal('old_salary_base', 15, 2)->nullable()->after('old_employment_type');
            $table->decimal('new_salary_base', 15, 2)->nullable()->after('old_salary_base');
        });

        // Mở rộng CHECK constraint change_type để thêm 'salary_change' và 'separation'
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            try { DB::statement('ALTER TABLE employee_history DROP CHECK chk_eh_change_type'); } catch (\Throwable) {}
            DB::statement("ALTER TABLE employee_history ADD CONSTRAINT chk_eh_change_type CHECK (change_type IN ('hire','branch_transfer','dept_transfer','promotion','demotion','manager_change','salary_change','leave','return_from_leave','resign','terminate','separation'))");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE employee_history DROP CONSTRAINT IF EXISTS chk_eh_change_type');
            DB::statement("ALTER TABLE employee_history ADD CONSTRAINT chk_eh_change_type CHECK (change_type IN ('hire','branch_transfer','dept_transfer','promotion','demotion','manager_change','salary_change','leave','return_from_leave','resign','terminate','separation'))");
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA writable_schema = ON');

            $row = DB::selectOne("SELECT sql FROM sqlite_schema WHERE type='table' AND name='employee_history'");
            if ($row) {
                $sql = str_replace(
                    "'hire','branch_transfer','dept_transfer','promotion','demotion','manager_change','leave','return_from_leave','resign','terminate'",
                    "'hire','branch_transfer','dept_transfer','promotion','demotion','manager_change','salary_change','leave','return_from_leave','resign','terminate','separation'",
                    $row->sql
                );
                DB::statement("UPDATE sqlite_schema SET sql = ? WHERE type = 'table' AND name = 'employee_history'", [$sql]);
            }

            DB::statement('PRAGMA writable_schema = OFF');
        }
    }

    public function down(): void
    {
        Schema::table('employee_history', function (Blueprint $table) {
            $table->dropColumn(['old_salary_base', 'new_salary_base']);
        });
    }
};
