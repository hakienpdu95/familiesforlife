<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// spec/ContentOutlines_Technical_Specification.md §4.18 (v1.15) — URL CTA thật (khác content_goal/
// search_intent chỉ định hướng LOẠI CTA) để nhúng thẳng vào câu chuyển tiếp cuối outline + cuối
// bài viết (Bước 2). Validate url() ở FormRequest (KHÁC competitor_urls — đây là 1 URL DUY NHẤT
// dùng để chèn vào câu văn, không phải danh sách text tự do cho AI đọc).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_outlines', function (Blueprint $table) {
            $table->string('cta_url', 500)->nullable()->after('content_goal');
        });
    }

    public function down(): void
    {
        Schema::table('content_outlines', function (Blueprint $table) {
            $table->dropColumn('cta_url');
        });
    }
};
