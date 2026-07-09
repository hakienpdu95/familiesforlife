<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_product_block_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained('post_product_blocks')->cascadeOnDelete();
            $table->string('item_key', 20);   // sinh 1 lần ở client, giữ nguyên qua các lần sửa
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete(); // FK cứng → Modules/Product

            // Override tuỳ chọn — null = fallback "sống" từ bảng products
            $table->string('title_override', 200)->nullable();
            $table->string('price_label_override', 100)->nullable();
            $table->text('description_override')->nullable();
            $table->string('image_url_override', 500)->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['block_id', 'item_key'], 'uq_post_pbi_block_key');
            $table->index(['block_id', 'sort_order'], 'idx_post_pbi_block_order');
            $table->index('product_id', 'idx_post_pbi_product');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_product_block_items');
    }
};
