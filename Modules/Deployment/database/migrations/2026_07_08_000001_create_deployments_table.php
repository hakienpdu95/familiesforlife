<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trường hợp 3 (docs/migration-guide.md) — bảng mới thuộc riêng tính năng
 * Deployment Engine (spec 02-DAC-TA-THIET-KE-5-MODULE-MOI.md §4.3), tạo trực tiếp
 * trong Module theo đúng chỉ định của người dùng, không qua render_migration_file.json.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('organization_solution_id')->constrained('organization_solutions')->restrictOnDelete();
            $table->foreignId('business_solution_id')->constrained('business_solutions')->restrictOnDelete();
            $table->foreignId('blueprint_id')->constrained('blueprints')->restrictOnDelete();
            $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->restrictOnDelete();
            $table->unsignedBigInteger('project_id')->nullable(); // FK mềm → Modules\Project\Models\Project (module khác)
            $table->unsignedBigInteger('deployed_by');
            $table->string('status', 20)->default('pending'); // DeploymentStatus: pending|running|completed|failed|rolled_back
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployments');
    }
};
