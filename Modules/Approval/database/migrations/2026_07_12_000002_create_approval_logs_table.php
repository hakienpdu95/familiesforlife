<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Workflow_Approval_Technical_Specification.md §4 — Migration #2.
 * Audit log append-only — không có updated_at, không sửa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('approval_subject_id')->constrained()->cascadeOnDelete();
            $table->string('action', 20); // submit|approve|reject|publish|archive|revise
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->string('reason', 500)->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['approval_subject_id', 'created_at'], 'idx_approval_log_subject');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_logs');
    }
};
