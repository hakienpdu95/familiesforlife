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
        if (Schema::hasTable('departments')) {
            return;
        }

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('departments')->restrictOnDelete();
            $table->string('path', 255)->default('/');
            $table->unsignedTinyInteger('depth')->default(0);
            $table->string('name');
            $table->string('code', 50);
            $table->string('function', 50)->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('merged_into_id')->nullable()->constrained('departments')->restrictOnDelete();
            $table->string('budget_code', 50)->nullable();
            $table->unsignedSmallInteger('headcount_limit')->nullable();
            $table->text('description')->nullable();
            $table->string('internal_phone', 20)->nullable();
            $table->string('internal_email', 255)->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique(['organization_id', 'code'], 'uq_dept_code');
            $table->index(['organization_id', 'status'], 'idx_depts_org_status');
            $table->index(['branch_id', 'status'], 'idx_depts_branch');
            $table->index(['organization_id', 'path'], 'idx_depts_org_path');
            $table->index('parent_id', 'idx_depts_parent');
            $table->index('merged_into_id', 'idx_depts_merged');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};