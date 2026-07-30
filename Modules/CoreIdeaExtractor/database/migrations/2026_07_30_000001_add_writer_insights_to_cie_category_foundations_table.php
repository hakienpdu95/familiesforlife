<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/CoreIdeaExtractor.md §12 — "Key insights for writers": tóm tắt 5-7 gạch đầu dòng đặt TRƯỚC
 * core_focus/unique_angle (thường dài 400-700 từ) để biên tập viên nội hóa nhanh một bộ tiêu chí
 * mà không phải đọc hết toàn bộ đoạn văn dài. Tách cột riêng (không nhét vào đầu core_focus) để
 * UI có thể hiển thị/style khác (vd danh sách gạch đầu dòng ngắn gọn) và không tính vào giới hạn
 * ký tự hiện có của core_focus.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('cie_category_foundations', 'writer_insights')) {
            return;
        }

        Schema::table('cie_category_foundations', function (Blueprint $table) {
            $table->text('writer_insights')->nullable()->after('core_focus');
        });
    }

    public function down(): void
    {
        Schema::table('cie_category_foundations', function (Blueprint $table) {
            $table->dropColumn('writer_insights');
        });
    }
};
