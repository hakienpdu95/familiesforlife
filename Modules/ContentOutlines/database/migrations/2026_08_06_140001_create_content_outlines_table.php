<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// spec/ContentOutlines_Technical_Specification.md §2.1 — công cụ nghiên cứu nội dung nền tảng,
// KHÔNG organization_id, KHÔNG TenantAwareModel — cùng nhóm content_foundations/cie_layer2_runs.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_outlines', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // route key — cùng quy ước PostCategory::booted()

            $table->string('label', 200);
            $table->string('topic', 300);
            $table->string('target_keyword', 150);
            $table->string('secondary_keywords', 500)->nullable();
            $table->string('search_intent', 20)->nullable(); // §3.2 — null = để AI tự xác định
            $table->foreignId('post_category_id')->nullable()->constrained('post_categories')->nullOnDelete();

            // Override phiên làm việc — KHÁC field cùng tên trên content_foundations (đó là ngữ
            // cảnh BỀN VỮNG theo category, đây là brief riêng của outline này — §2.1).
            $table->string('target_audience', 500)->nullable();
            $table->text('content_goal')->nullable();
            $table->text('tone_style')->nullable();

            $table->text('competitor_urls')->nullable(); // 1 URL/dòng, tự do — KHÔNG validate url() (§7.1)
            $table->unsignedInteger('desired_word_count')->nullable();
            $table->string('language', 5)->default('vi');
            $table->text('additional_notes')->nullable();

            $table->longText('generated_prompt'); // ghi đè khi "Sinh lại" — không versioning (§0)
            $table->foreignId('linked_post_article_id')->nullable()->constrained('post_articles')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('post_category_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_outlines');
    }
};
