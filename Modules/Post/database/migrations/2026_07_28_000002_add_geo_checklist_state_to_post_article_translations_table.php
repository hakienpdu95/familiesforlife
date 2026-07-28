<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Checklist GEO/AEO (spec/blog.md — totheweb.com GEO checklist, 2026-07-28) — chỉ lưu các tiêu
 * chí THỦ CÔNG mà không thể tự suy ra từ dữ liệu sẵn có (VD "heading có dạng câu hỏi?", "có dùng
 * ngôn ngữ mơ hồ không?"), do biên tập viên tự tick ở tab "GEO Checklist" trong trình soạn bài —
 * xem GeoChecklist::manualGroups(). Tiêu chí TỰ ĐỘNG suy ra được (có FAQ block, có trích dẫn,
 * direct_answer đủ dài, mới cập nhật gần đây...) KHÔNG lưu ở đây — tính lại mỗi lần render, xem
 * GeoChecklist::autoResults().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_article_translations', function (Blueprint $table) {
            $table->json('geo_checklist_state')->nullable()->after('direct_answer');
        });
    }

    public function down(): void
    {
        Schema::table('post_article_translations', function (Blueprint $table) {
            $table->dropColumn('geo_checklist_state');
        });
    }
};
