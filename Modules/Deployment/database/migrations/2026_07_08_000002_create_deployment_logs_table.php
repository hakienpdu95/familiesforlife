<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bỏ cột `payload` (JSON, spec §4.3) — pseudocode DeployOrganizationSolutionAction
     * (spec §4.5) không hề ghi payload khi log ("{$step} OK"), chỉ dùng step/level/message;
     * giữ đúng những gì thực sự được dùng, tránh field JSON tự do không ai đọc.
     */
    public function up(): void
    {
        Schema::create('deployment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('deployment_id')->constrained('deployments')->cascadeOnDelete();
            $table->string('step', 100); // 'validate_blueprint' | 'read_config' | 'generate_runtime' | 'init_dashboard' | 'init_ai_context' | 'complete'
            $table->text('message')->nullable();
            $table->string('level', 20)->default('info'); // info|warning|error
            $table->timestamp('created_at')->nullable();

            $table->index(['deployment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployment_logs');
    }
};
