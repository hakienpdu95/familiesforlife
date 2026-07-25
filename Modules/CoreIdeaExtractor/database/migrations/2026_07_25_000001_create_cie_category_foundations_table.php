<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/CoreIdeaExtractor.md §12 (v1.4) — "Category Content Foundation": ngữ cảnh biên tập bền
 * vững theo TỪNG PostCategory (không phải Organization — Post là tài sản platform-wide, biên
 * tập viên được gán theo category qua post_category_editors, không theo tổ chức).
 *
 * post_category_id trỏ post_categories.id (khoá chính thật, KHÔNG phải cột uuid dùng cho route
 * binding) — cascadeOnDelete vì foundation vô nghĩa khi category gốc bị xoá hẳn (category dùng
 * SoftDeletes nên trường hợp này hiếm). unique() vì đúng 1 foundation / category (upsert theo
 * post_category_id, không có nhiều bản ghi cho cùng category).
 *
 * Bảng này thuộc CoreIdeaExtractor (module PHỤ THUỘC biết về Post), KHÔNG phải Post — cùng
 * hướng phụ thuộc 1 chiều với post_article_ocop_products (Modules/Ocop), Post module không cần
 * biết/sửa gì để hỗ trợ bảng này.
 *
 * Tên bảng rút gọn `cie_category_foundations` (KHÔNG dùng đầy đủ `core_idea_extractor_...`) —
 * tên bảng đầy đủ khiến tên constraint auto-gen của Laravel (vd
 * `..._post_category_id_foreign`/`..._unique`) vượt quá giới hạn 64 ký tự của MySQL
 * (lỗi thật đã gặp: "Identifier name ... is too long").
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cie_category_foundations')) {
            return;
        }

        Schema::create('cie_category_foundations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_category_id')->unique()->constrained('post_categories')->cascadeOnDelete();

            // 3 thành phần ánh xạ "Business Foundation Document" (core offering/UVP/goals) sang
            // ngữ cảnh biên tập của 1 chuyên mục — xem spec §12.
            $table->text('core_focus')->nullable();
            $table->text('unique_angle')->nullable();
            $table->text('content_goals')->nullable();

            // Tương ứng field ad-hoc đã có sẵn ở form batch (audience/goal/constraints/style_sample,
            // xem index.blade.php) — persist theo category thay vì phải gõ lại mỗi lần chạy.
            $table->string('audience', 500)->nullable();
            $table->string('constraints', 500)->nullable();
            $table->text('style_sample')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cie_category_foundations');
    }
};
