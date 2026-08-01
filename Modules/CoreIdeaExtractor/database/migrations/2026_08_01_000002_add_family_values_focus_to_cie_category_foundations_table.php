<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/giadinh.md + config('core_idea_extractor.family_values') — Hệ giá trị gia đình Việt Nam
 * (4 trụ cột: ấm no/hạnh phúc/tiến bộ/văn minh, Quyết định 1189/QĐ-TTg 02/07/2026) là CHUẨN NỀN
 * TẢNG cố định của platform, KHÔNG lưu định nghĩa vào DB (nguồn sự thật duy nhất là config, xem
 * docblock ở đó) — cột mới `family_values_focus` chỉ lưu TẬP KEY editor tick chọn (VD
 * ["hanh_phuc","tien_bo"]) cho biết chuyên mục này ưu tiên phục vụ giá trị nào, KHÔNG lưu lại
 * nhãn/mô tả (tránh 2 nơi có thể lệch nhau nếu sau này chỉnh câu chữ mô tả trong config).
 *
 * Cùng convention objections/decision_criteria (migration additive riêng, không đổi cột cũ).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('cie_category_foundations', 'family_values_focus')) {
            return;
        }

        Schema::table('cie_category_foundations', function (Blueprint $table) {
            $table->json('family_values_focus')->nullable()->after('decision_criteria');
        });
    }

    public function down(): void
    {
        Schema::table('cie_category_foundations', function (Blueprint $table) {
            $table->dropColumn(['family_values_focus']);
        });
    }
};
