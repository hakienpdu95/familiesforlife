<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_checklist_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('organization_solution_id')->constrained('organization_solutions', 'id', 'org_checklist_configs_org_sol_fk')->cascadeOnDelete();
            $table->foreignId('blueprint_checklist_id')->constrained('blueprint_checklists', 'id', 'org_checklist_configs_checklist_fk')->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->unsignedBigInteger('default_assignee_id')->nullable();
            $table->unsignedBigInteger('default_reviewer_id')->nullable();
            $table->unsignedSmallInteger('due_days')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_solution_id', 'blueprint_checklist_id'], 'org_checklist_configs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_checklist_configs');
    }
};
