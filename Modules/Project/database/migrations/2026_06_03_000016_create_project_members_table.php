<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->string('role', 20)->default('member');
            $table->tinyInteger('is_lead')->default(0);
            $table->unsignedTinyInteger('contribution_pct')->nullable();
            $table->date('joined_at')->nullable();
            $table->date('left_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['project_id', 'employee_id', 'left_at'], 'uq_project_member');

            $table->index(['project_id', 'left_at'], 'idx_pm_project');
            $table->index(['employee_id', 'left_at'], 'idx_pm_employee');
        });

        DB::statement("ALTER TABLE project_members ADD CONSTRAINT chk_pm_role CHECK (role IN ('lead','member','advisor','stakeholder'))");
        DB::statement('ALTER TABLE project_members ADD CONSTRAINT chk_pm_contribution CHECK (contribution_pct IS NULL OR (contribution_pct >= 0 AND contribution_pct <= 100))');
    }

    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};
