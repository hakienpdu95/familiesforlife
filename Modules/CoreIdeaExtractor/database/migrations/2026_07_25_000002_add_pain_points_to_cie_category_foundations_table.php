<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/CoreIdeaExtractor.md §12.6 (v1.10) — "pain_points": câu hỏi/khó khăn thường gặp của độc
 * giả, rút ra từ nghiên cứu thực tế (khảo sát/feedback/câu hỏi lặp lại) — tham khảo case study
 * B2B thought-leadership (khách hàng research mỗi 4 tháng làm NỀN cho content, không chỉ mô tả
 * trừu tượng). Đặt SAU `content_goals`, TRƯỚC `audience` trong form — cùng nhóm "3 thành phần
 * Business Foundation" với core_focus/unique_angle/content_goals (xem CategoryContentFoundation).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('cie_category_foundations', 'pain_points')) {
            return;
        }

        Schema::table('cie_category_foundations', function (Blueprint $table) {
            $table->text('pain_points')->nullable()->after('content_goals');
        });
    }

    public function down(): void
    {
        Schema::table('cie_category_foundations', function (Blueprint $table) {
            $table->dropColumn('pain_points');
        });
    }
};
