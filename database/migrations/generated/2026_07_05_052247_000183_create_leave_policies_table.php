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
        if (Schema::hasTable('leave_policies')) {
            return;
        }

        Schema::create('leave_policies', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained('organizations');
            $table->string('leave_type', 20);
            $table->string('name', 100);
            $table->decimal('days_per_year', 5, 1);
            $table->decimal('carry_over_days', 5, 1)->default(0);
            $table->smallInteger('min_advance_days')->default(1);
            $table->smallInteger('max_consecutive_days')->nullable();
            $table->boolean('requires_approval')->default(true);
            $table->foreignId('job_title_id')->nullable()->constrained('job_titles')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->date('effective_from');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique(['organization_id', 'leave_type', 'job_title_id', 'department_id'], 'idx_leave_policies_scope');
            $table->index(['organization_id', 'leave_type', 'is_active'], 'idx_leave_policies_org_type');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_policies');
    }
};