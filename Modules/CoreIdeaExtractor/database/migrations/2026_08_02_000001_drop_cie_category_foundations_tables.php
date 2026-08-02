<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/CoreIdeaExtractor.md §12 — Category Content Foundation đã tách sang module dùng chung
 * Modules\ContentFoundation (bảng mới `content_foundations`/`content_foundation_categories`, xem
 * Modules/ContentFoundation/database/migrations/2026_08_02_000001_create_content_foundations_table.php).
 * Drop thẳng 2 bảng cũ ở đây — dữ liệu giai đoạn dev, chưa có bản ghi thật cần giữ/backfill (cùng
 * tiền lệ đã ghi ở migration 2026_07_28_000001_make_cie_category_foundations_shared_across_categories.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('cie_foundation_categories');
        Schema::dropIfExists('cie_category_foundations');
    }

    public function down(): void
    {
        // Không khôi phục lại — schema cũ đã thay thế hoàn toàn bởi Modules\ContentFoundation.
    }
};
