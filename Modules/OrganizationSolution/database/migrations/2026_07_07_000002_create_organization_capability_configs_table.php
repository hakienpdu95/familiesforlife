<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_capability_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('organization_solution_id')->constrained('organization_solutions', 'id', 'org_capability_configs_org_sol_fk')->cascadeOnDelete();
            $table->foreignId('blueprint_capability_id')->constrained('blueprint_capabilities', 'id', 'org_capability_configs_capability_fk')->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->string('override_name', 255)->nullable();
            $table->timestamps();
            $table->softDeletes(); // TenantAwareModel dùng SoftDeletes trait

            $table->unique(['organization_solution_id', 'blueprint_capability_id'], 'org_capability_configs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_capability_configs');
    }
};
