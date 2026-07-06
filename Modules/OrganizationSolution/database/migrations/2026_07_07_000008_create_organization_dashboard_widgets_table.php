<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thay thế `organization_solution_configs.config_key='dashboard'` (JSON, spec §3.6
     * Bước 7 "Dashboard") bằng bảng quan hệ tường minh — mỗi widget 1 dòng, FK tuỳ chọn
     * tới blueprint_analytics (metric nguồn) để hiển thị trên Dashboard của tổ chức.
     */
    public function up(): void
    {
        Schema::create('organization_dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('organization_solution_id')->constrained('organization_solutions', 'id', 'org_dashboard_widgets_org_sol_fk')->cascadeOnDelete();
            $table->foreignId('blueprint_analytic_id')->nullable()->constrained('blueprint_analytics', 'id', 'org_dashboard_widgets_analytic_fk')->nullOnDelete();
            $table->string('widget_type', 50)->default('metric'); // metric|chart|list
            $table->string('title', 255);
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_solution_id', 'sort_order'], 'org_dashboard_widgets_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_dashboard_widgets');
    }
};
