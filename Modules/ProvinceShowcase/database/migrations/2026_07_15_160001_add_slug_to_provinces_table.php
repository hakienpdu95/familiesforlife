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
 *
 * Guard thêm bằng hasTable — migration này (dated 2026-07-15) chạy TRƯỚC migration tạo bảng
 * `provinces` trong baseline snapshot (`database/migrations/generated/2026_07_20_145511_000075_
 * create_provinces_table.php`, dated 2026-07-20) trên 1 fresh install — bảng chưa tồn tại tại
 * thời điểm này chạy. Trên môi trường ĐÃ tồn tại từ trước (migration này đã ghi nhận "đã chạy" —
 * `provinces` khi đó có sẵn), không đổi hành vi. `database/migrations/extensions/2026_07_20_
 * 145511_000175_add_slug_to_provinces_table.php` (chạy SAU baseline snapshot, đúng thứ tự) đã tự
 * đảm nhiệm việc thêm cột `slug` cho fresh install — bỏ qua ở đây không làm mất tính năng.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('provinces') && ! Schema::hasColumn('provinces', 'slug')) {
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
