<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_type', 30);
            $table->unsignedBigInteger('old_branch_id')->nullable();
            $table->unsignedBigInteger('new_branch_id')->nullable();
            $table->unsignedBigInteger('old_department_id')->nullable();
            $table->unsignedBigInteger('new_department_id')->nullable();
            $table->unsignedBigInteger('old_job_title_id')->nullable();
            $table->unsignedBigInteger('new_job_title_id')->nullable();
            $table->unsignedBigInteger('old_manager_id')->nullable();
            $table->unsignedBigInteger('new_manager_id')->nullable();
            $table->string('old_status', 20)->nullable();
            $table->string('new_status', 20)->nullable();
            $table->string('old_employment_type', 20)->nullable();
            $table->string('new_employment_type', 20)->nullable();
            $table->date('effective_date');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['organization_id', 'change_type', 'effective_date'], 'idx_emp_hist_org');
            $table->index(['employee_id', 'effective_date'], 'idx_emp_hist_employee');
            $table->index(['new_department_id', 'effective_date'], 'idx_emp_hist_new_dept');
            $table->index(['new_branch_id', 'effective_date'], 'idx_emp_hist_new_branch');
        });

        // SET NULL FKs for history references
        DB::statement('ALTER TABLE employee_history ADD CONSTRAINT fk_eh_old_branch FOREIGN KEY (old_branch_id) REFERENCES branches(id) ON DELETE SET NULL');
        DB::statement('ALTER TABLE employee_history ADD CONSTRAINT fk_eh_new_branch FOREIGN KEY (new_branch_id) REFERENCES branches(id) ON DELETE SET NULL');
        DB::statement('ALTER TABLE employee_history ADD CONSTRAINT fk_eh_old_dept FOREIGN KEY (old_department_id) REFERENCES departments(id) ON DELETE SET NULL');
        DB::statement('ALTER TABLE employee_history ADD CONSTRAINT fk_eh_new_dept FOREIGN KEY (new_department_id) REFERENCES departments(id) ON DELETE SET NULL');
        DB::statement('ALTER TABLE employee_history ADD CONSTRAINT fk_eh_old_title FOREIGN KEY (old_job_title_id) REFERENCES job_titles(id) ON DELETE SET NULL');
        DB::statement('ALTER TABLE employee_history ADD CONSTRAINT fk_eh_new_title FOREIGN KEY (new_job_title_id) REFERENCES job_titles(id) ON DELETE SET NULL');
        DB::statement('ALTER TABLE employee_history ADD CONSTRAINT fk_eh_old_mgr FOREIGN KEY (old_manager_id) REFERENCES employees(id) ON DELETE SET NULL');
        DB::statement('ALTER TABLE employee_history ADD CONSTRAINT fk_eh_new_mgr FOREIGN KEY (new_manager_id) REFERENCES employees(id) ON DELETE SET NULL');

        DB::statement("ALTER TABLE employee_history ADD CONSTRAINT chk_eh_change_type CHECK (change_type IN ('hire','branch_transfer','dept_transfer','promotion','demotion','manager_change','leave','return_from_leave','resign','terminate'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_history');
    }
};
