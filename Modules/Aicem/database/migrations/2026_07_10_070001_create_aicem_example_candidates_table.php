<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 (tuỳ chọn, mục 11/15) — hàng chờ duyệt thủ công trước khi thành 1
 * aicem_knowledge_documents(type=example_good) thật. AicemServiceProvider lắng nghe
 * Modules\Post\Features\ArticleAuthoring\Events\ArticlePublished, nếu bài viết
 * is_featured=true thì tạo 1 hàng pending ở đây — KHÔNG ghi thẳng vào knowledge_documents
 * ("cần duyệt thủ công bởi AI_Operator trước khi lưu").
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('aicem_example_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();

            $table->string('subject_type', 50);
            // subject_id KHÔNG khai FK cứng — cùng lý do aicem_generation_runs (mục 7): polymorphic
            // tự quản qua config/aicem_subjects.php, tránh phải migrate lại khi thêm module thứ 3.
            $table->unsignedBigInteger('subject_id');

            $table->string('suggested_title', 255);
            $table->longText('suggested_content');
            $table->json('suggested_scope')->nullable();

            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            // Set khi approve — trỏ tới knowledge document thật vừa tạo, để truy vết + tránh tạo lại.
            $table->foreignId('created_knowledge_document_id')->nullable()
                ->constrained('aicem_knowledge_documents')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // 1 subject chỉ có tối đa 1 candidate (mọi status) — publish lại không tạo trùng.
            $table->unique(['organization_id', 'subject_type', 'subject_id'], 'aicem_ec_org_subject_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aicem_example_candidates');
    }
};
