<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Province_Showcase_Technical_Specification.md §3.1 — provinces là bảng hành chính dùng
 * chung (Customer/Lead/Event), KHÔNG sửa trực tiếp migration gốc (đã tồn tại ở production) —
 * thêm cột qua migration riêng trong Modules/ProvinceShowcase, cùng lý do các migration "Trường
 * hợp 3" khác (docs/migration-guide.md) không biểu diễn được qua JSON schema DSL.
 *
 * Guard bằng hasColumn — cho phép chạy lại an toàn (cùng pattern
 * Modules/Post/database/migrations/2026_07_13_000001_drop_organization_id_from_post_articles_table.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('provinces', 'slug')) {
            Schema::table('provinces', function (Blueprint $table) {
                $table->string('slug', 255)->nullable()->unique()->after('name');
            });
        }
    }

    public function down(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
