<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Workflow_Approval_Technical_Specification.md §4 — Migration #1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_subjects', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject'); // subject_type, subject_id + index tự động
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // Bản nội dung đã duyệt/đang hiển thị công khai — chỉ PublishAction (§8.2) được
            // ghi cột này. ReviseContentAction (§8.4) KHÔNG đụng vào, để cổng thông tin không
            // bị gián đoạn khi nội dung đang chờ duyệt lại (§1). NULL = entity chưa từng được
            // publish lần nào.
            $table->json('public_snapshot')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['subject_type', 'subject_id'], 'uq_approval_subject');
            $table->index(['organization_id', 'status'], 'idx_approval_org_status');
            // Phục vụ dashboard lọc theo loại entity (§12) — tránh full scan khi nhiều subject_type.
            $table->index(['organization_id', 'subject_type', 'status'], 'idx_approval_org_type_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_subjects');
    }
};
