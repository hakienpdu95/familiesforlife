<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aicem_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();

            $table->string('subject_type', 50);
            // slug: headline | seo_audit | full_optimization — không ràng buộc enum cứng ở DB để
            // không phải migrate lại khi thêm loại workflow mới.
            $table->string('slug', 50);
            $table->string('name', 255);
            $table->text('prompt_template');

            // filters: bộ lọc phụ theo subject_type (mục 7) — post_article: {"formats":[...]},
            // product: {"category_ids":[...]}. null = áp dụng mọi bài/sản phẩm.
            $table->json('filters')->nullable();

            $table->foreignId('context_template_id')->constrained('aicem_context_templates')->restrictOnDelete();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'subject_type', 'slug'], 'aicem_wf_org_subject_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aicem_workflows');
    }
};
