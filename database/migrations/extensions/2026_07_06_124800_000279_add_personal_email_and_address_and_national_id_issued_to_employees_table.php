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
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'personal_email')) {
                $table->string('personal_email', 150)->nullable();
            }
            if (!Schema::hasColumn('employees', 'address')) {
                $table->text('address')->nullable()->after('personal_email');
            }
            if (!Schema::hasColumn('employees', 'national_id_issued')) {
                $table->date('national_id_issued')->nullable()->after('address');
            }
            if (!Schema::hasColumn('employees', 'bank_account')) {
                $table->string('bank_account', 30)->nullable()->after('national_id_issued');
            }
            if (!Schema::hasColumn('employees', 'bank_name')) {
                $table->string('bank_name', 100)->nullable()->after('bank_account');
            }
            if (!Schema::hasColumn('employees', 'probation_end_date')) {
                $table->date('probation_end_date')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('employees', 'contract_start')) {
                $table->date('contract_start')->nullable()->after('probation_end_date');
            }
            if (!Schema::hasColumn('employees', 'contract_end')) {
                $table->date('contract_end')->nullable()->after('contract_start');
            }
            if (!Schema::hasColumn('employees', 'salary_base')) {
                $table->decimal('salary_base', 15, 2)->nullable()->after('contract_end');
            }
            if (!Schema::hasColumn('employees', 'salary_currency')) {
                $table->char('salary_currency', 3)->default('VND')->after('salary_base');
            }
            if (!Schema::hasColumn('employees', 'work_location')) {
                $table->string('work_location', 20)->nullable()->after('salary_currency');
            }
            if (!Schema::hasColumn('employees', 'emergency_contact_name')) {
                $table->string('emergency_contact_name', 150)->nullable()->after('work_location');
            }
            if (!Schema::hasColumn('employees', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone', 20)->nullable()->after('emergency_contact_name');
            }
            if (!Schema::hasColumn('employees', 'resigned_at')) {
                $table->date('resigned_at')->nullable()->after('emergency_contact_phone');
            }
            if (!Schema::hasColumn('employees', 'resignation_reason')) {
                $table->text('resignation_reason')->nullable()->after('resigned_at');
            }
            if (!Schema::hasColumn('employees', 'notes')) {
                $table->text('notes')->nullable()->after('resignation_reason');
            }
            if (!Schema::hasColumn('employees', 'digital_competency_score')) {
                $table->decimal('digital_competency_score', 5, 2)->nullable()->after('notes')->comment('Điểm năng lực số — model đã khai báo trong $casts nhưng thiếu migration, gây MissingAttributeException khi tạo/đọc Employee');
            }
            if (!Schema::hasColumn('employees', 'digital_maturity_level')) {
                $table->string('digital_maturity_level', 64)->nullable()->after('digital_competency_score')->comment('band_code/persona_code mức độ trưởng thành số — set bởi UpdateEmployeeDigitalCompetencyListener');
            }
            if (!Schema::hasColumn('employees', 'latest_assessment_result_id')) {
                $table->foreignId('latest_assessment_result_id')->nullable()->constrained('assessment_results')->nullOnDelete()->after('digital_maturity_level')->comment('Kết quả assessment TDWCF gần nhất');
            }
            if (!Schema::hasColumn('employees', 'last_assessed_at')) {
                $table->timestamp('last_assessed_at')->nullable()->after('latest_assessment_result_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'latest_assessment_result_id')) $table->dropForeign(['latest_assessment_result_id']);
            $cols = array_filter(['personal_email', 'address', 'national_id_issued', 'bank_account', 'bank_name', 'probation_end_date', 'contract_start', 'contract_end', 'salary_base', 'salary_currency', 'work_location', 'emergency_contact_name', 'emergency_contact_phone', 'resigned_at', 'resignation_reason', 'notes', 'digital_competency_score', 'digital_maturity_level', 'latest_assessment_result_id', 'last_assessed_at'], fn($c) => Schema::hasColumn('employees', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};