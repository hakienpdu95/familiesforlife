<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * province_name/ward_name là denormalize thừa — tên tỉnh/phường đã tra được trực tiếp từ
 * province_code/ward_code qua bảng provinces/wards (App\Models\Province, App\Models\Ward) mỗi
 * khi cần hiển thị, không cần lưu lại 1 bản sao trong events. Cùng pattern đã áp dụng cho
 * post_articles (xem Modules/Post/database/migrations/2026_07_16_090000_drop_province_name_
 * and_ward_name_from_post_articles_table.php).
 *
 * Guard bằng hasColumn — cho phép chạy lại an toàn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $cols = array_filter(['province_name', 'ward_name'], fn ($c) => Schema::hasColumn('events', $c));
            if (! empty($cols)) {
                $table->dropColumn(array_values($cols));
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (! Schema::hasColumn('events', 'province_name')) {
                $table->string('province_name', 255)->nullable()->after('province_code');
            }
            if (! Schema::hasColumn('events', 'ward_name')) {
                $table->string('ward_name', 255)->nullable()->after('ward_code');
            }
        });
    }
};
