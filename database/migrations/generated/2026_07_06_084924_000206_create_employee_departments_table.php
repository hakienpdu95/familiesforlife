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
        if (Schema::hasTable('employee_departments')) {
            return;
        }

        Schema::create('employee_departments', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->string('role_in_dept', 50)->nullable();
            $table->date('joined_at')->nullable();
            $table->date('left_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->unique(['employee_id', 'department_id', 'left_at'], 'uq_emp_dept_active');
            $table->index(['employee_id', 'left_at'], 'idx_emp_depts_emp');
            $table->index(['department_id', 'is_primary'], 'idx_emp_depts_dept');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_departments');
    }
};