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
        if (Schema::hasTable('projects')) {
            return;
        }

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('owner_id')->constrained('employees')->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 50)->nullable();
            $table->string('status', 20)->default('planning');
            $table->string('priority', 10)->default('medium');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->char('currency', 3)->default('VND');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique(['organization_id', 'code'], 'uq_project_code');
            $table->index(['organization_id', 'status'], 'idx_projects_org_status');
            $table->index(['organization_id', 'status', 'priority'], 'idx_projects_org_priority');
            $table->index('branch_id', 'idx_projects_branch');
            $table->index('department_id', 'idx_projects_dept');
            $table->index('owner_id', 'idx_projects_owner');
            $table->index(['start_date', 'end_date'], 'idx_projects_dates');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};