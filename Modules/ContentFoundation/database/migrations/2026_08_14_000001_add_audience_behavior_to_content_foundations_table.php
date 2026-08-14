<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/CoreIdeaExtractor.md §12.12 — Tầng HÀNH VI (Behavioral) của "3 cấp độ hiểu đối tượng"
 * (lindapophal.substack.com/p/the-2026-content-marketing-imperative): `audience` (§12.2) chỉ mô tả
 * Level 1 (nhân khẩu học — họ LÀ ai); `pain_points`/`objections`/`decision_criteria` (§12.6/§12.6-v1.16)
 * đã phủ Level 2 (tâm lý — họ QUAN TÂM gì). Level 3 (hành vi — ngày của họ trông thế nào, họ đang
 * tiêu thụ/tìm kiếm nội dung gì) chưa có field riêng — cột mới `audience_behavior`, ALTER riêng
 * (KHÔNG sửa migration `2026_08_02_000001_create_content_foundations_table.php` đã chạy).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_foundations', function (Blueprint $table) {
            $table->text('audience_behavior')->nullable()->after('audience');
        });
    }

    public function down(): void
    {
        Schema::table('content_foundations', function (Blueprint $table) {
            $table->dropColumn('audience_behavior');
        });
    }
};
