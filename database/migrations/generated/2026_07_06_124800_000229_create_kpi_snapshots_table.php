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
        if (Schema::hasTable('kpi_snapshots')) {
            return;
        }

        Schema::create('kpi_snapshots', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('goal_id')->constrained('kpi_goals');
            $table->foreignId('employee_id')->constrained('employees');
            $table->string('cycle_label', 30);
            $table->decimal('target_value', 15, 4);
            $table->decimal('final_value', 15, 4);
            $table->decimal('achievement_pct', 6, 2);
            $table->smallInteger('weight_percent');
            $table->decimal('weighted_score', 6, 2);
            $table->decimal('kpi_total_score', 6, 2)->nullable();
            $table->foreignId('snapped_by')->constrained('users');
            $table->timestamp('snapped_at')->useCurrent();
            

            // Indexes
            $table->index(['employee_id', 'cycle_label'], 'idx_kpi_snapshots_emp_cycle');
            $table->index(['cycle_label', 'employee_id'], 'idx_kpi_snapshots_cycle_emp');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_snapshots');
    }
};