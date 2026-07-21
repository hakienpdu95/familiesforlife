<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Media_Library_Technical_Specification.md §8 — cover image chuyển sang Media (collection
 * `cover`, qua FilePond) thay cho field string đơn `cover_image_url`. Giai đoạn phát triển hiện
 * tại dùng `migration:generate --fresh` thường xuyên (§7.3) — xoá cột ngay, không giữ fallback.
 *
 * Guard bằng hasColumn — cho phép chạy lại an toàn, cùng pattern
 * 2026_07_13_000001_drop_organization_id_from_post_articles_table.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('post_articles', 'cover_image_url')) {
            Schema::table('post_articles', function (Blueprint $table) {
                $table->dropColumn('cover_image_url');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('post_articles', 'cover_image_url')) {
            Schema::table('post_articles', function (Blueprint $table) {
                $table->string('cover_image_url', 500)->nullable();
            });
        }
    }
};
