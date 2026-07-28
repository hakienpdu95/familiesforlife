<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * spec/CoreIdeaExtractor.md §12 — nâng cấp Category Content Foundation từ 1-1 (mỗi category có
 * đúng 1 bộ tiêu chí riêng, `post_category_id` unique) sang N-N: 1 bộ tiêu chí (bảng
 * cie_category_foundations) có thể áp dụng chung cho NHIỀU category (VD 2 chuyên mục cùng đối
 * tượng độc giả/mục tiêu dùng chung 1 bộ, sửa 1 nơi áp dụng cho cả 2 — xem
 * UpsertCategoryFoundationAction). Bảng nối `cie_foundation_categories` vẫn giữ
 * unique(post_category_id): 1 category chỉ áp dụng ĐÚNG 1 bộ tiêu chí tại 1 thời điểm (tránh mơ hồ
 * khi build prompt AI ở CoreIdeaExtractorController::index()/ExtractBatchRequestData) — chiều
 * "1 foundation → nhiều category" mới là chiều N-N thực sự được mở khoá ở đây.
 *
 * "Trường hợp 3" (docs/migration-guide.md) — dropColumn không biểu diễn được qua JSON nên làm
 * module migration, sau đó `php artisan migration:sync` để đồng bộ lại render_migration_file.json.
 * Giai đoạn dev, chưa có dữ liệu thật (xem docs/migration-guide.md) — drop thẳng post_category_id,
 * không backfill/giữ fallback.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('cie_category_foundations', 'post_category_id')) {
            // Lưu ý: bản ghi thực tế trên DB dev được tạo từ database/migrations/generated/ (JSON
            // render_migration_file.json) — bản này CHỈ tạo index thường tên
            // `..._post_category_id_foreign` cho cột (không có FK constraint thật, không unique),
            // KHÁC với comment ở module migration 2026_07_25_000001 (không hề chạy trên DB thật vì
            // bị guard hasTable() bỏ qua do generated/ chạy trước theo thứ tự ngày). Kiểm tra
            // information_schema thay vì gọi thẳng dropForeign()/dropUnique() — cả 2 sẽ lỗi "không
            // tồn tại" nếu constraint thật sự chưa từng được tạo.
            $hasForeignKey = DB::selectOne(
                "SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cie_category_foundations'
                   AND COLUMN_NAME = 'post_category_id' AND REFERENCED_TABLE_NAME IS NOT NULL"
            ) !== null;

            if ($hasForeignKey) {
                Schema::table('cie_category_foundations', function (Blueprint $table) {
                    $table->dropForeign(['post_category_id']);
                });
            }

            Schema::table('cie_category_foundations', function (Blueprint $table) {
                $table->dropColumn('post_category_id');
            });
        }

        if (! Schema::hasTable('cie_foundation_categories')) {
            Schema::create('cie_foundation_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('foundation_id')->constrained('cie_category_foundations')->cascadeOnDelete();
                $table->foreignId('post_category_id')->unique()->constrained('post_categories')->cascadeOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cie_foundation_categories');

        if (! Schema::hasColumn('cie_category_foundations', 'post_category_id')) {
            Schema::table('cie_category_foundations', function (Blueprint $table) {
                $table->foreignId('post_category_id')->nullable()->unique()->constrained('post_categories')->cascadeOnDelete();
            });
        }
    }
};
