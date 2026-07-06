<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $table->foreignId('job_title_id')->nullable()->constrained('job_titles')->nullOnDelete();
            // manager_id: self-referential, add as nullable unsignedBigInteger first
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('employee_code', 50);
            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->string('gender', 10)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('national_id', 20)->nullable();
            $table->string('tax_code', 20)->nullable();
            $table->string('locale', 10)->nullable();
            $table->string('avatar_url', 500)->nullable();
            $table->string('status', 20)->default('active');
            $table->string('employment_type', 20)->default('full_time');
            $table->date('hired_at')->nullable();
            $table->date('left_at')->nullable();
            // Snapshot columns
            $table->string('snap_branch_name')->nullable();
            $table->string('snap_dept_name')->nullable();
            $table->string('snap_job_title')->nullable();
            $table->unsignedTinyInteger('snap_job_level')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'employee_code'], 'uq_employee_code');
            $table->unique(['organization_id', 'email'], 'uq_employee_email');
            $table->unique(['organization_id', 'user_id'], 'uq_employee_user');

            $table->index(['organization_id', 'status'], 'idx_employees_org_status');
            $table->index(['branch_id', 'status'], 'idx_employees_branch');
            $table->index(['department_id', 'status'], 'idx_employees_dept');
            $table->index('manager_id', 'idx_employees_manager');
            $table->index('user_id', 'idx_employees_user');
            $table->index('job_title_id', 'idx_employees_job_title');
            $table->index('hired_at', 'idx_employees_hired_at');
        });

        // Self-referential FK for manager_id
        DB::statement('ALTER TABLE employees ADD CONSTRAINT fk_employees_manager FOREIGN KEY (manager_id) REFERENCES employees(id) ON DELETE SET NULL');

        // chk_employee_no_self_mgr: enforced at app layer (MySQL 8 disallows CHECK on auto-increment PK)
        DB::statement("ALTER TABLE employees ADD CONSTRAINT chk_employee_status CHECK (status IN ('active','on_leave','resigned','terminated'))");
        DB::statement("ALTER TABLE employees ADD CONSTRAINT chk_employee_type CHECK (employment_type IN ('full_time','part_time','contract','intern'))");
        DB::statement("ALTER TABLE employees ADD CONSTRAINT chk_employee_gender CHECK (gender IS NULL OR gender IN ('male','female','other'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
