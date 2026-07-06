<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_members')) {
            return;
        }

        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->string('role', 20)->default('member');
            $table->tinyInteger('is_lead')->default(0);
            $table->unsignedTinyInteger('contribution_pct')->nullable();
            $table->date('joined_at')->nullable();
            $table->date('left_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
            

            // Indexes
            $table->unique(['project_id', 'employee_id', 'left_at'], 'uq_project_member');
            $table->index(['project_id', 'left_at'], 'idx_pm_project');
            $table->index(['employee_id', 'left_at'], 'idx_pm_employee');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};