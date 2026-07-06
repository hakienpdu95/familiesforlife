<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'personal_email')) {
                $table->string('personal_email', 150)->nullable()->after('email');
            }
            if (! Schema::hasColumn('employees', 'address')) {
                $table->text('address')->nullable()->after('personal_email');
            }
            if (! Schema::hasColumn('employees', 'national_id_issued')) {
                $table->date('national_id_issued')->nullable()->after('national_id');
            }
            if (! Schema::hasColumn('employees', 'bank_account')) {
                $table->string('bank_account', 30)->nullable()->after('tax_code');
            }
            if (! Schema::hasColumn('employees', 'bank_name')) {
                $table->string('bank_name', 100)->nullable()->after('bank_account');
            }
            if (! Schema::hasColumn('employees', 'probation_end_date')) {
                $table->date('probation_end_date')->nullable()->after('hired_at');
            }
            if (! Schema::hasColumn('employees', 'contract_start')) {
                $table->date('contract_start')->nullable()->after('probation_end_date');
            }
            if (! Schema::hasColumn('employees', 'contract_end')) {
                $table->date('contract_end')->nullable()->after('contract_start');
            }
            if (! Schema::hasColumn('employees', 'salary_base')) {
                $table->decimal('salary_base', 15, 2)->nullable()->after('left_at');
            }
            if (! Schema::hasColumn('employees', 'salary_currency')) {
                $table->char('salary_currency', 3)->default('VND')->after('salary_base');
            }
            if (! Schema::hasColumn('employees', 'work_location')) {
                $table->string('work_location', 20)->nullable()->after('salary_currency');
            }
            if (! Schema::hasColumn('employees', 'emergency_contact_name')) {
                $table->string('emergency_contact_name', 150)->nullable()->after('work_location');
            }
            if (! Schema::hasColumn('employees', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone', 20)->nullable()->after('emergency_contact_name');
            }
            if (! Schema::hasColumn('employees', 'resigned_at')) {
                $table->date('resigned_at')->nullable()->after('emergency_contact_phone');
            }
            if (! Schema::hasColumn('employees', 'resignation_reason')) {
                $table->text('resignation_reason')->nullable()->after('resigned_at');
            }
            if (! Schema::hasColumn('employees', 'notes')) {
                $table->text('notes')->nullable()->after('resignation_reason');
            }
        });

        // Cập nhật dữ liệu: đổi 'contract' → 'contractor'
        DB::table('employees')->where('employment_type', 'contract')->update(['employment_type' => 'contractor']);

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // Drop nếu tồn tại, sau đó add mới với giá trị đầy đủ
            try { DB::statement('ALTER TABLE employees DROP CHECK chk_employee_status'); } catch (\Throwable) {}
            try { DB::statement('ALTER TABLE employees DROP CHECK chk_employee_type'); } catch (\Throwable) {}

            DB::statement("ALTER TABLE employees ADD CONSTRAINT chk_employee_status CHECK (status IN ('active','probation','on_leave','resigned','terminated'))");
            DB::statement("ALTER TABLE employees ADD CONSTRAINT chk_employee_type CHECK (employment_type IN ('full_time','part_time','contractor','probation','intern'))");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE employees DROP CONSTRAINT IF EXISTS chk_employee_status');
            DB::statement('ALTER TABLE employees DROP CONSTRAINT IF EXISTS chk_employee_type');
            DB::statement("ALTER TABLE employees ADD CONSTRAINT chk_employee_status CHECK (status IN ('active','probation','on_leave','resigned','terminated'))");
            DB::statement("ALTER TABLE employees ADD CONSTRAINT chk_employee_type CHECK (employment_type IN ('full_time','part_time','contractor','probation','intern'))");
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA writable_schema = ON');

            $row = DB::selectOne("SELECT sql FROM sqlite_schema WHERE type='table' AND name='employees'");
            if ($row) {
                $sql = str_replace(
                    "'full_time','part_time','contract','intern'",
                    "'full_time','part_time','contractor','probation','intern'",
                    $row->sql
                );
                $sql = str_replace(
                    "'active','on_leave','resigned','terminated'",
                    "'active','probation','on_leave','resigned','terminated'",
                    $sql
                );
                DB::statement("UPDATE sqlite_schema SET sql = ? WHERE type = 'table' AND name = 'employees'", [$sql]);
            }

            DB::statement('PRAGMA writable_schema = OFF');
            DB::statement('PRAGMA integrity_check');
        }

        // Index bổ sung
        DB::statement('CREATE INDEX idx_employees_contract_end ON employees (organization_id, contract_end, status)');
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'personal_email', 'address', 'national_id_issued',
                'bank_account', 'bank_name',
                'probation_end_date', 'contract_start', 'contract_end',
                'salary_base', 'salary_currency', 'work_location',
                'emergency_contact_name', 'emergency_contact_phone',
                'resigned_at', 'resignation_reason', 'notes',
            ]);
        });

        DB::table('employees')->where('employment_type', 'contractor')->update(['employment_type' => 'contract']);
    }
};
