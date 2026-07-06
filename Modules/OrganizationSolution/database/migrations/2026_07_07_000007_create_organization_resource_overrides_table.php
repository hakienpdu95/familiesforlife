<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thay thế `organization_solution_configs.config_key='resource_override'` (JSON,
     * spec §3.6 "chỉ thay reference, VD BM-01 → BM-01-HTX") bằng bảng quan hệ tường
     * minh — mỗi override 1 dòng, FK trực tiếp tới blueprint_resource_links.
     */
    public function up(): void
    {
        Schema::create('organization_resource_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('organization_solution_id')->constrained('organization_solutions', 'id', 'org_resource_overrides_org_sol_fk')->cascadeOnDelete();
            $table->foreignId('blueprint_resource_link_id')->constrained('blueprint_resource_links', 'id', 'org_resource_overrides_link_fk')->cascadeOnDelete();
            $table->string('override_reference', 255); // VD "BM-01-HTX"
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_solution_id', 'blueprint_resource_link_id'], 'org_resource_overrides_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_resource_overrides');
    }
};
