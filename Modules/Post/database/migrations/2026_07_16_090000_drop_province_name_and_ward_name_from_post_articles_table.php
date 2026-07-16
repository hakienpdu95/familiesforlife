<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * province_name/ward_name là denormalize thừa — tên tỉnh/phường đã tra được trực tiếp từ
 * province_code/ward_code qua bảng provinces/wards (App\Models\Province, App\Models\Ward) mỗi
 * khi cần hiển thị, không cần lưu lại 1 bản sao trong post_articles.
 *
 * Guard bằng hasColumn — cho phép chạy lại an toàn, cùng pattern migration "Trường hợp 3" khác
 * của Post (docs/migration-guide.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_articles', function (Blueprint $table) {
            $cols = array_filter(['province_name', 'ward_name'], fn ($c) => Schema::hasColumn('post_articles', $c));
            if (! empty($cols)) {
                $table->dropColumn(array_values($cols));
            }
        });
    }

    public function down(): void
    {
        Schema::table('post_articles', function (Blueprint $table) {
            if (! Schema::hasColumn('post_articles', 'province_name')) {
                $table->string('province_name', 255)->nullable()->after('province_code');
            }
            if (! Schema::hasColumn('post_articles', 'ward_name')) {
                $table->string('ward_name', 255)->nullable()->after('ward_code');
            }
        });
    }
};
