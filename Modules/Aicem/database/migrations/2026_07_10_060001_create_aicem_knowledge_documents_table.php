<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aicem_knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();

            // type: không phải PHP enum đóng — hợp lệ theo
            // config('aicem_subjects.knowledge_slot_definitions') (mục 6.3.1).
            // subject_type = null CHỈ cho tầng DNA chung toàn org: skill/brand_guideline/audience_personas.
            // subject_type = 'post_article'|'product' bắt buộc cho tri thức chuyên môn (knowledge_slots)
            // và example_good/example_bad (mục 5.1).
            $table->string('type', 50);
            $table->string('subject_type', 50)->nullable();

            // scope = null → luôn khớp; != null → chỉ khớp instance có taxonomy() giao đúng key (mục 5.2/6.7).
            $table->json('scope')->nullable();
            $table->string('scope_match', 10)->default('any');
            $table->integer('priority')->default(100);

            $table->string('title', 255);
            $table->longText('content');
            $table->unsignedInteger('current_version')->default(1);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Khớp truy vấn ResolveApplicableKnowledgeAction (mục 6.7 bước 1).
            $table->index(['organization_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aicem_knowledge_documents');
    }
};
