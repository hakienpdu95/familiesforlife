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
        if (Schema::hasTable('performance_reviews')) {
            return;
        }

        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('reviewer_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('template_id')->constrained('review_templates')->restrictOnDelete();
            $table->string('period', 20);
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('status', 20)->default('draft');
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->string('overall_rating', 20)->nullable();
            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->text('goals_next_period')->nullable();
            $table->text('employee_comment')->nullable();
            $table->string('snap_branch_name')->nullable();
            $table->string('snap_dept_name')->nullable();
            $table->string('snap_job_title')->nullable();
            $table->unsignedTinyInteger('snap_job_level')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique(['employee_id', 'template_id', 'period'], 'uq_review_period');
            $table->index(['organization_id', 'period', 'status'], 'idx_reviews_org_period');
            $table->index(['employee_id', 'period'], 'idx_reviews_employee');
            $table->index('reviewer_id', 'idx_reviews_reviewer');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};