<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Đối chiếu bài context-engineering (animalz.co) — "audience model" cần tách "objection" (phản
 * đối/nghi ngờ thường gặp khiến độc giả CHƯA tin/CHƯA hành động) và "decision criteria" (tiêu chí
 * độc giả dùng để quyết định) ra khỏi `pain_points` (vốn chỉ là câu hỏi/khó khăn thực tế, KHÁC
 * bản chất với "lý do còn nghi ngờ" hay "điều gì khiến họ chọn A thay vì B"). Gộp chung vào 1 cột
 * tự do trước đây sẽ làm editor bỏ sót 1 trong 2 khi viết, hoặc viết lẫn lộn không tách được ý nào
 * dùng cho mục đích nào khi build prompt (xem index.blade.php phần build "top" — mỗi field hiện
 * đang map thẳng 1 dòng ngữ nghĩa riêng trong prompt).
 *
 * Cùng convention rejected_ideas/writer_insights (migration additive riêng, không đổi cột cũ).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('cie_category_foundations', 'objections')) {
            return;
        }

        Schema::table('cie_category_foundations', function (Blueprint $table) {
            $table->text('objections')->nullable()->after('pain_points');
            $table->text('decision_criteria')->nullable()->after('objections');
        });
    }

    public function down(): void
    {
        Schema::table('cie_category_foundations', function (Blueprint $table) {
            $table->dropColumn(['objections', 'decision_criteria']);
        });
    }
};
