<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_history')) {
            return;
        }

        Schema::create('employee_history', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
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
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->index(['organization_id', 'change_type', 'effective_date'], 'idx_emp_hist_org');
            $table->index(['employee_id', 'effective_date'], 'idx_emp_hist_employee');
            $table->index(['new_department_id', 'effective_date'], 'idx_emp_hist_new_dept');
            $table->index(['new_branch_id', 'effective_date'], 'idx_emp_hist_new_branch');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_history');
    }
};