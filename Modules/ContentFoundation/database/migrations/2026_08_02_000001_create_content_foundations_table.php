<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/CoreIdeaExtractor.md §12 — tách "Category Content Foundation" ra khỏi CoreIdeaExtractor
 * (bảng cũ `cie_category_foundations`/`cie_foundation_categories`) thành module dùng chung
 * `ContentFoundation`. Gộp thẳng schema đầy đủ đã tích luỹ qua các version (core_focus..
 * style_sample, family_values_focus, family_conduct_focus, created_by/updated_by) thay vì chạy
 * lại chuỗi migration cũ — dữ liệu giai đoạn dev, chưa có bản ghi thật cần giữ (xem chú thích tương
 * tự ở migration cũ 2026_07_28_000001_make_cie_category_foundations_shared_across_categories.php
 * bên CoreIdeaExtractor).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_foundations', function (Blueprint $table) {
            $table->id();
            $table->text('core_focus')->nullable();
            $table->text('writer_insights')->nullable();
            $table->text('unique_angle')->nullable();
            $table->text('content_goals')->nullable();
            $table->text('pain_points')->nullable();
            $table->text('objections')->nullable();
            $table->text('decision_criteria')->nullable();
            $table->json('family_values_focus')->nullable();
            // spec/CoreIdeaExtractor.md §12.11 — TẬP KEY con của config('content_foundation.
            // family_conduct_standards.items'), cùng nguyên tắc với family_values_focus phía trên
            // (không lưu lại nhãn/mô tả, tránh 2 nơi lệch nhau).
            $table->json('family_conduct_focus')->nullable();
            $table->text('rejected_ideas')->nullable();
            $table->string('audience', 500)->nullable();
            $table->string('constraints', 500)->nullable();
            $table->text('style_sample')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('content_foundation_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('foundation_id')->constrained('content_foundations')->cascadeOnDelete();
            $table->foreignId('post_category_id')->unique()->constrained('post_categories')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_foundation_categories');
        Schema::dropIfExists('content_foundations');
    }
};
