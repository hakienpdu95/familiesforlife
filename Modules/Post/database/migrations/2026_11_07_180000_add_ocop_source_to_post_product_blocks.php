<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Khối sản phẩm dùng chung cho 2 nguồn: Modules/Product (source=product, item.product_id) và
 * Modules/Ocop (source=ocop, item.ocop_product_id) — cùng template/nút/lịch sử phiên bản.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_product_blocks', function (Blueprint $table) {
            $table->string('source', 20)->default('product')->after('template')->comment('product|ocop');
        });

        Schema::table('post_product_block_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->foreignId('ocop_product_id')->nullable()->after('product_id')->constrained('ocop_products')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('post_product_block_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ocop_product_id');
        });

        Schema::table('post_product_blocks', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
