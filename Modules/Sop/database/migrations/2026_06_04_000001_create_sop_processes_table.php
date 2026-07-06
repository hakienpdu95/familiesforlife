<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sop_processes', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->string('code', 50);
            $table->string('title', 300);
            $table->text('description')->nullable();
            $table->enum('type', ['internal', 'regulatory', 'training', 'emergency'])->default('internal')->index();
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected', 'archived'])
                  ->default('draft')->index();
            $table->smallInteger('version')->unsigned()->default(0);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expired_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code'], 'idx_sop_proc_code');
            $table->index(['organization_id', 'status'], 'idx_sop_proc_status');
            $table->index('expired_date', 'idx_sop_proc_expired');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sop_processes');
    }
};
