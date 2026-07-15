<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Province_Showcase_Technical_Specification.md §3.2 — copy nguyên convention đã dùng ở
 * events (province_code/ward_code không FK cứng, denormalize tên tại thời điểm chọn, tránh
 * join provinces/wards mỗi lần render). Cả 2 cột nullable, không bắt buộc validate ở Action
 * layer — không phá luồng viết bài hiện tại khi tác giả chưa chọn tỉnh (spec §0 mục 4).
 *
 * Guard bằng hasColumn/hasIndex — cho phép chạy lại an toàn, cùng pattern các migration "Trường
 * hợp 3" khác của Post (docs/migration-guide.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('post_articles', 'province_code')) {
            Schema::table('post_articles', function (Blueprint $table) {
                $table->char('province_code', 2)->nullable()->after('cover_image_url')
                    ->comment('Mã tỉnh/thành — không FK cứng, cùng pattern events.province_code');
                $table->string('province_name', 255)->nullable()->comment('Tên tỉnh denormalized');
                $table->char('ward_code', 5)->nullable()->comment('Mã phường/xã — tuỳ chọn, chỉ điền khi bài gắn 1 địa điểm cụ thể');
                $table->string('ward_name', 255)->nullable();
            });
        }

        // post_articles KHÔNG còn cột published_at (đã chuyển sang post_article_translations
        // từ migration 2026_07_11_000003) — chỉ index province_code, lọc "đã publish" luôn phải
        // join qua PostArticleTranslation::published() (xem §3.2.1 spec).
        if (! collect(Schema::getIndexes('post_articles'))->contains('name', 'idx_post_article_province')) {
            Schema::table('post_articles', function (Blueprint $table) {
                $table->index('province_code', 'idx_post_article_province');
            });
        }
    }

    public function down(): void
    {
        Schema::table('post_articles', function (Blueprint $table) {
            $table->dropIndex('idx_post_article_province');
        });

        Schema::table('post_articles', function (Blueprint $table) {
            $table->dropColumn(['province_code', 'province_name', 'ward_code', 'ward_name']);
        });
    }
};
