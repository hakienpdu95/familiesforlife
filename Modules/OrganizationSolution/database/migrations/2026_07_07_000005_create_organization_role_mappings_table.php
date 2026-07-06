<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_role_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('organization_solution_id')->constrained('organization_solutions', 'id', 'org_role_mappings_org_sol_fk')->cascadeOnDelete();
            $table->string('blueprint_role_code', 100); // "field_officer" | "supervisor" | "manager" (trừu tượng, từ blueprint_deployment_roles)
            $table->unsignedBigInteger('organization_role_id')->nullable(); // FK mềm → Spatie roles.id
            $table->unsignedBigInteger('user_id')->nullable(); // gán trực tiếp 1 user cụ thể nếu cần
            $table->string('mapping_type', 30)->default('role'); // 'role' | 'user'
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_solution_id', 'blueprint_role_code'], 'org_role_mappings_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_role_mappings');
    }
};
