<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hồ sơ sản phẩm OCOP — câu chuyện sản phẩm + thông số kỹ thuật. Tài liệu nhãn mác/bao bì và
 * hồ sơ chất lượng KHÔNG lưu cột riêng — đi qua Media (collection ocop_label_docs,
 * ocop_quality_declaration, ocop_test_reports, ocop_quality_certs).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ocop_products', function (Blueprint $table) {
            $table->longText('story')->nullable()->after('description');
            $table->string('origin', 255)->nullable()->after('story');
            $table->string('production_date', 100)->nullable()->after('origin');
            $table->string('shelf_life', 100)->nullable()->after('production_date');
            $table->text('ingredients')->nullable()->after('shelf_life');
            $table->text('usage_instructions')->nullable()->after('ingredients');
            $table->text('storage_instructions')->nullable()->after('usage_instructions');
        });
    }

    public function down(): void
    {
        Schema::table('ocop_products', function (Blueprint $table) {
            $table->dropColumn([
                'story', 'origin', 'production_date', 'shelf_life',
                'ingredients', 'usage_instructions', 'storage_instructions',
            ]);
        });
    }
};
