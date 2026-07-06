<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_goals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained('organizations');
            $table->foreignId('employee_id')->constrained('employees');
            $table->string('cycle_label', 30);
            $table->date('cycle_start');
            $table->date('cycle_end');
            $table->foreignId('parent_goal_id')->nullable()->constrained('kpi_goals')->nullOnDelete();
            $table->string('title', 300);
            $table->text('description')->nullable();
            $table->string('goal_type', 20)->default('manual');
            $table->decimal('target_value', 15, 4);
            $table->decimal('current_value', 15, 4)->default(0);
            $table->string('unit', 30)->nullable();
            $table->string('direction', 20)->default('higher_better');
            $table->decimal('achievement_pct', 6, 2)->default(0);
            $table->smallInteger('weight_percent')->default(10);
            $table->string('status', 20)->default('draft');
            $table->timestamp('last_synced_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'cycle_label', 'status'], 'idx_kpi_goals_emp_cycle');
            $table->index(['organization_id', 'cycle_end', 'status'], 'idx_kpi_goals_cycle_end');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_goals');
    }
};
