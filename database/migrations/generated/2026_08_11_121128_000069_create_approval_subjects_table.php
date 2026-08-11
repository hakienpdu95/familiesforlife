<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('approval_subjects')) {
            return;
        }

        Schema::create('approval_subjects', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->json('public_snapshot')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['subject_type', 'subject_id']);
            $table->unique(['subject_type', 'subject_id'], 'uq_approval_subject');
            $table->index(['organization_id', 'status'], 'idx_approval_org_status');
            $table->index(['organization_id', 'subject_type', 'status'], 'idx_approval_org_type_status');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('approval_subjects');
    }
};
