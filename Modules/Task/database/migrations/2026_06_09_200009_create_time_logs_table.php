<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('hours', 6, 2);
            $table->date('log_date');
            $table->string('description', 500)->nullable();
            $table->boolean('is_billable')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['task_id', 'log_date'], 'idx_timelog_task');
            $table->index(['project_id', 'log_date', 'employee_id'], 'idx_timelog_project');
            $table->index(['employee_id', 'log_date'], 'idx_timelog_employee');
            $table->index('organization_id', 'idx_timelog_org');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_logs');
    }
};
