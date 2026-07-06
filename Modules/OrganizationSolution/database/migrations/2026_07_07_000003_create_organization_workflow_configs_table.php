<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_workflow_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('organization_solution_id')->constrained('organization_solutions', 'id', 'org_workflow_configs_org_sol_fk')->cascadeOnDelete();
            $table->foreignId('blueprint_workflow_id')->constrained('blueprint_workflows', 'id', 'org_workflow_configs_workflow_fk')->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->unsignedBigInteger('default_owner_id')->nullable();
            $table->unsignedSmallInteger('sla_days')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_solution_id', 'blueprint_workflow_id'], 'org_workflow_configs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_workflow_configs');
    }
};
