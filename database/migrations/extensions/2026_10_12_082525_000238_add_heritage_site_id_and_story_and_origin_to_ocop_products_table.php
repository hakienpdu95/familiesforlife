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
            if (!Schema::hasColumn('ocop_products', 'ingredients')) {
                $table->text('ingredients')->nullable()->after('shelf_life')->comment('Thành phần');
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
        });
    }

    public function down(): void
    {
        Schema::table('ocop_products', function (Blueprint $table) {
            if (Schema::hasColumn('ocop_products', 'heritage_site_id')) $table->dropForeign(['heritage_site_id']);
            if (Schema::hasColumn('ocop_products', 'ocop_subject_id')) $table->dropForeign(['ocop_subject_id']);
            $cols = array_filter(['heritage_site_id', 'story', 'origin', 'production_date', 'shelf_life', 'ingredients', 'usage_instructions', 'storage_instructions', 'ocop_subject_id'], fn($c) => Schema::hasColumn('ocop_products', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};