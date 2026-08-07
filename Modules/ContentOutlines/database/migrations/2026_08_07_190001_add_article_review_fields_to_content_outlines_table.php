<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// spec/ContentOutlines_Technical_Specification.md §4.20 (v1.16) — Feature ArticleReview ("Bước 3").
// drafted_article: biên tập viên dán bài viết ĐÃ VIẾT XONG (từ AI ngoài chạy article_draft_prompt,
// hoặc viết tay) vào đây. review_prompt: snapshot prompt soát lỗi/sửa đã sinh — ghi đè khi sinh
// lại, KHÔNG versioning, cùng nguyên tắc generated_prompt/article_draft_prompt.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_outlines', function (Blueprint $table) {
            $table->longText('drafted_article')->nullable()->after('article_draft_prompt');
            $table->longText('review_prompt')->nullable()->after('drafted_article');
        });
    }

    public function down(): void
    {
        Schema::table('content_outlines', function (Blueprint $table) {
            $table->dropColumn(['drafted_article', 'review_prompt']);
        });
    }
};
