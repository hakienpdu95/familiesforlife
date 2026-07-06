<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_ai_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('organization_solution_id')->constrained('organization_solutions', 'id', 'org_ai_configs_org_sol_fk')->cascadeOnDelete();
            $table->string('ai_capability_code', 100); // khớp blueprint_ai_capabilities.capability_code
            $table->boolean('enabled')->default(true);
            $table->unsignedBigInteger('ai_agent_id')->nullable();  // override agent khác agent mặc định của Blueprint
            $table->unsignedBigInteger('ai_prompt_id')->nullable();
            $table->string('provider', 50)->nullable();
            $table->decimal('cost_limit', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_solution_id', 'ai_capability_code'], 'org_ai_configs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_ai_configs');
    }
};
