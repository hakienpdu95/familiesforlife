<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Media_Library_Technical_Specification.md §8 — ảnh banner chuyển sang Media (collection
 * `banner`, qua FilePond) thay 4 cột phẳng cũ. Giai đoạn phát triển hiện tại dùng
 * `migration:generate --fresh` thường xuyên (§7.3) — xoá cột ngay, không giữ fallback.
 *
 * Guard bằng hasColumn — cho phép chạy lại an toàn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $cols = array_filter(
                ['image_path', 'image_width', 'image_height', 'image_size_bytes'],
                fn ($c) => Schema::hasColumn('banners', $c)
            );

            if (! empty($cols)) {
                $table->dropColumn(array_values($cols));
            }
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            if (! Schema::hasColumn('banners', 'image_path')) {
                $table->string('image_path', 255)->nullable();
            }
            if (! Schema::hasColumn('banners', 'image_width')) {
                $table->unsignedInteger('image_width')->nullable();
            }
            if (! Schema::hasColumn('banners', 'image_height')) {
                $table->unsignedInteger('image_height')->nullable();
            }
            if (! Schema::hasColumn('banners', 'image_size_bytes')) {
                $table->unsignedInteger('image_size_bytes')->nullable();
            }
        });
    }
};
