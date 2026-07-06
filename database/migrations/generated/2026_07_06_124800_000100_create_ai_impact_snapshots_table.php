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
        if (Schema::hasTable('ai_impact_snapshots')) {
            return;
        }

        Schema::create('ai_impact_snapshots', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('kpi_goal_id')->nullable();
            $table->string('impact_category', 20)->comment('learning|productivity|quality|ai_adoption|business');
            $table->string('impact_type', 50)->comment('Chỉ số cụ thể trong nhóm');
            $table->decimal('baseline_value', 12, 4)->comment('Giá trị trước AI');
            $table->decimal('achieved_value', 12, 4)->comment('Giá trị đạt được sau AI');
            $table->decimal('improvement_pct', 7, 2)->comment('(achieved-baseline)/baseline × 100');
            $table->decimal('investment_cost', 15, 2)->default(0);
            $table->decimal('benefit_value', 15, 2)->default(0);
            $table->decimal('roi_pct', 7, 2)->nullable()->comment('(benefit-cost)/cost × 100');
            $table->date('period_start');
            $table->date('period_end');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['organization_id', 'impact_category'], 'idx_ais_org_category');
            $table->index(['employee_id', 'period_start'], 'idx_ais_emp_period');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_impact_snapshots');
    }
};