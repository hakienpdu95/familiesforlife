<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GEO đợt 6 (2026-08-08) — khối "So sánh" trong content-block composer, phục vụ "Comparison
 * fan-out" (spec/giadinh.md — Moz "A Guide to Web Guide: Our Hybrid Search Future"). Trước đây
 * chỉ có 1 gợi ý checklist thủ công ("trình bày so sánh dạng bảng") ở GeoChecklist.php, không có
 * block/dữ liệu thật — biên tập viên phải tự dựng bảng HTML trong khối Text.
 *
 * Cùng nguyên tắc PostHowtoBlock/PostFaqBlock: không soft-delete, không tenant-scoped, không
 * override/fallback (dữ liệu tĩnh nhập tay, không tham chiếu entity ngoài). Đặt ở
 * database/migrations/extensions/ (không phải Modules/Post/database/migrations/) vì đây là nơi
 * MỌI thay đổi schema gần đây của post_content_blocks/post_howto_blocks/post_faq_blocks thực sự
 * đang sống (xem MEMORY "Migration generated/ vs module drift") — tránh tạo thêm 1 nguồn schema
 * thứ 3 cho cùng nhóm bảng.
 *
 * Thiết kế 2 bảng con thay vì 1 bảng "cell" chuẩn hoá đầy đủ: `post_comparison_rows.values` lưu
 * thẳng mảng JSON (thứ tự khớp post_comparison_columns.sort_order) — bảng so sánh là dữ liệu
 * TĨNH nhập tay 1 lần (không có ô nào cần query/lọc riêng lẻ), chuẩn hoá tới bảng "cell" thứ 4 sẽ
 * tăng độ phức tạp CRUD (SyncContentBlocksAction) mà không đổi lấy được lợi ích thực tế nào.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('post_comparison_blocks')) {
            Schema::create('post_comparison_blocks', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('translation_id')->constrained('post_article_translations')->cascadeOnDelete();
                $table->string('name', 255)->nullable();
                $table->text('description')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['translation_id', 'sort_order'], 'idx_post_comparison_block_translation_order');
            });
        }

        if (! Schema::hasTable('post_comparison_columns')) {
            Schema::create('post_comparison_columns', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('comparison_block_id')->constrained('post_comparison_blocks')->cascadeOnDelete();
                $table->string('label', 150);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['comparison_block_id', 'sort_order'], 'idx_post_comparison_column_block_order');
            });
        }

        if (! Schema::hasTable('post_comparison_rows')) {
            Schema::create('post_comparison_rows', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('comparison_block_id')->constrained('post_comparison_blocks')->cascadeOnDelete();
                $table->string('label', 150); // tên tiêu chí so sánh, VD "Giá", "Trọng lượng"
                // Mảng string, PHẦN TỬ THỨ i khớp post_comparison_columns có sort_order = i (0-based)
                // — validate độ dài khớp số cột nằm ở SyncContentBlocksAction::validateComparisonBlocks().
                $table->json('values');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['comparison_block_id', 'sort_order'], 'idx_post_comparison_row_block_order');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('post_comparison_rows');
        Schema::dropIfExists('post_comparison_columns');
        Schema::dropIfExists('post_comparison_blocks');
    }
};
