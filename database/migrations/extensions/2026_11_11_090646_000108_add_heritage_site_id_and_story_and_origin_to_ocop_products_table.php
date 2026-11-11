<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ocop_products', function (Blueprint $table) {
            if (!Schema::hasColumn('ocop_products', 'heritage_site_id')) {
                $table->foreignId('heritage_site_id')->nullable()->constrained('heritage_sites')->nullOnDelete();
            }
            if (!Schema::hasColumn('ocop_products', 'story')) {
                $table->longText('story')->nullable()->after('heritage_site_id')->comment('Câu chuyện sản phẩm (HTML từ Jodit)');
            }
            if (!Schema::hasColumn('ocop_products', 'origin')) {
                $table->string('origin', 255)->nullable()->after('story')->comment('Xuất xứ');
            }
            if (!Schema::hasColumn('ocop_products', 'production_date')) {
                $table->string('production_date', 100)->nullable()->after('origin')->comment('Ngày sản xuất');
            }
            if (!Schema::hasColumn('ocop_products', 'shelf_life')) {
                $table->string('shelf_life', 100)->nullable()->after('production_date')->comment('Hạn sử dụng');
            }
            if (!Schema::hasColumn('ocop_products', 'shelf_life_value')) {
                $table->unsignedSmallInteger('shelf_life_value')->nullable()->after('shelf_life')->comment('Hạn sử dụng — giá trị số (1–9999)');
            }
            if (!Schema::hasColumn('ocop_products', 'shelf_life_unit')) {
                $table->string('shelf_life_unit', 10)->nullable()->after('shelf_life_value')->comment('Đơn vị hạn sử dụng: hour | day | week | month | year');
            }
            if (!Schema::hasColumn('ocop_products', 'ingredients')) {
                $table->text('ingredients')->nullable()->after('shelf_life_unit')->comment('Thành phần');
            }
            if (!Schema::hasColumn('ocop_products', 'usage_instructions')) {
                $table->text('usage_instructions')->nullable()->after('ingredients')->comment('Hướng dẫn sử dụng');
            }
            if (!Schema::hasColumn('ocop_products', 'storage_instructions')) {
                $table->text('storage_instructions')->nullable()->after('usage_instructions')->comment('Hướng dẫn bảo quản');
            }
            if (!Schema::hasColumn('ocop_products', 'ocop_subject_id')) {
                $table->foreignId('ocop_subject_id')->nullable()->constrained('ocop_subjects')->restrictOnDelete()->after('storage_instructions');
            }
            if (!Schema::hasColumn('ocop_products', 'shopee_url')) {
                $table->string('shopee_url', 500)->nullable()->after('ocop_subject_id')->comment('Link affiliate Shopee');
            }
            if (!Schema::hasColumn('ocop_products', 'tiktok_url')) {
                $table->string('tiktok_url', 500)->nullable()->after('shopee_url')->comment('Link affiliate TikTok Shop');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ocop_products', function (Blueprint $table) {
            if (Schema::hasColumn('ocop_products', 'heritage_site_id')) $table->dropForeign(['heritage_site_id']);
            if (Schema::hasColumn('ocop_products', 'ocop_subject_id')) $table->dropForeign(['ocop_subject_id']);
            $cols = array_filter(['heritage_site_id', 'story', 'origin', 'production_date', 'shelf_life', 'shelf_life_value', 'shelf_life_unit', 'ingredients', 'usage_instructions', 'storage_instructions', 'ocop_subject_id', 'shopee_url', 'tiktok_url'], fn($c) => Schema::hasColumn('ocop_products', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};