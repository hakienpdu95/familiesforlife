<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// spec/ContentOutlines_Technical_Specification.md §4.17 (v1.14) — Feature "ArticleDrafting":
// `approved_outline` (input, biên tập viên dán outline THẬT nhận về từ AI ngoài sau khi đã research
// bằng generated_prompt) → BuildArticleDraftPromptAction sinh `article_draft_prompt` (output, snapshot
// giống generated_prompt — ghi đè khi sinh lại, KHÔNG versioning, cùng nguyên tắc §0/§4.2).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_outlines', function (Blueprint $table) {
            $table->longText('approved_outline')->nullable()->after('generated_prompt');
            $table->longText('article_draft_prompt')->nullable()->after('approved_outline');
        });
    }

    public function down(): void
    {
        Schema::table('content_outlines', function (Blueprint $table) {
            $table->dropColumn(['approved_outline', 'article_draft_prompt']);
        });
    }
};
