<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/CoreIdeaExtractor.md §12.13 — đối chiếu martech.org/how-to-build-an-ai-content-system-that-works:
 * "Constants" của 1 hệ thống nội dung AI B2C cần có ICP + brand voice (đã phủ qua audience/audience_
 * behavior/pain_points/style_sample...) VÀ tài liệu mô tả chi tiết sản phẩm/dịch vụ + ví dụ nội dung/
 * dàn ý mẫu tốt nhất — 2 phần này CHƯA có field riêng, editor phải tự nhét tạm vào `core_focus`/
 * `writer_insights` (lệch mục đích field). 2 cột mới, ALTER riêng (KHÔNG sửa migration gốc đã chạy).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_foundations', function (Blueprint $table) {
            $table->text('product_service_docs')->nullable()->after('style_sample');
            $table->text('best_example_content')->nullable()->after('product_service_docs');
        });
    }

    public function down(): void
    {
        Schema::table('content_foundations', function (Blueprint $table) {
            $table->dropColumn(['product_service_docs', 'best_example_content']);
        });
    }
};
