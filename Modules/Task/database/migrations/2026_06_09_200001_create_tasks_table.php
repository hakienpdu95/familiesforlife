<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('title', 500);
            $table->text('description')->nullable();
            $table->string('task_type', 20)->default('task');
            $table->string('status', 20)->default('todo');
            $table->string('priority', 10)->default('medium');
            $table->unsignedTinyInteger('story_points')->nullable();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('estimated_hours', 6, 2)->nullable();
            $table->decimal('logged_hours', 8, 2)->default(0);
            $table->unsignedTinyInteger('progress_pct')->default(0);
            $table->boolean('is_leaf')->default(true);
            $table->unsignedSmallInteger('subtask_total')->default(0);
            $table->unsignedSmallInteger('subtask_done')->default(0);
            $table->unsignedSmallInteger('comment_count')->default(0);
            $table->unsignedSmallInteger('attachment_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('depth')->default(0);
            $table->boolean('is_archived')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['project_id', 'task_type', 'status', 'sort_order'], 'idx_task_list');
            $table->index(['project_id', 'status', 'is_archived'], 'idx_task_project');
            $table->index('parent_id', 'idx_task_parent');
            $table->index(['employee_id', 'status', 'due_date'], 'idx_task_assignee');
            $table->index(['project_id', 'due_date', 'status'], 'idx_task_due');
            $table->index(['project_id', 'is_leaf', 'status'], 'idx_task_leaf');
            $table->index(['organization_id', 'employee_id', 'status'], 'idx_task_org');
        });

        DB::statement("ALTER TABLE tasks ADD CONSTRAINT chk_task_type CHECK (task_type IN ('epic','story','task','subtask','bug','improvement'))");
        DB::statement("ALTER TABLE tasks ADD CONSTRAINT chk_task_status CHECK (status IN ('backlog','todo','in_progress','in_review','done','cancelled','blocked'))");
        DB::statement("ALTER TABLE tasks ADD CONSTRAINT chk_task_priority CHECK (priority IN ('critical','high','medium','low','none'))");
        DB::statement("ALTER TABLE tasks ADD CONSTRAINT chk_task_depth CHECK (depth <= 3)");
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
