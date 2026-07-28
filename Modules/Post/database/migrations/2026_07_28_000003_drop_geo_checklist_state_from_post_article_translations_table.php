<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Đảo ngược 2026_07_28_000002 — tab "GEO Checklist" đổi lại thành danh sách tham khảo tĩnh
 * (yêu cầu người dùng 2026-07-28: bỏ auto-detect + checkbox tick, chỉ hiển thị danh mục), nên
 * không còn cần lưu trạng thái tick từng mục theo bản dịch nữa — xem GeoChecklist::groups().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_article_translations', function (Blueprint $table) {
            $table->dropColumn('geo_checklist_state');
        });
    }

    public function down(): void
    {
        Schema::table('post_article_translations', function (Blueprint $table) {
            $table->json('geo_checklist_state')->nullable()->after('direct_answer');
        });
    }
};
