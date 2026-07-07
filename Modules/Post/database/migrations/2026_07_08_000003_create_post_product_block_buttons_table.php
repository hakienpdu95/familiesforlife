<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_product_block_buttons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained('post_product_blocks')->cascadeOnDelete();
            $table->foreignId('block_item_id')->nullable()->constrained('post_product_block_items')->cascadeOnDelete();
            $table->string('button_key', 20); // sinh 1 lần ở client, giữ nguyên qua các lần sửa — bảo toàn click_count
            $table->string('label', 60)->nullable();        // null khi url_type=use_product_link → lấy ProductLinkType::label()
            $table->string('url_type', 20);                  // ButtonUrlType
            $table->string('url', 500)->nullable();          // null khi url_type=use_product_link
            $table->string('product_link_type', 30)->nullable(); // ProductLinkType: chỉ set khi url_type=use_product_link
            $table->string('target', 10)->default('_blank');
            $table->string('style', 20)->default('primary');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedInteger('click_count')->default(0);
            $table->timestamps();

            $table->unique(['block_id', 'button_key'], 'uq_post_pbb_block_key');
            $table->index(['block_id', 'block_item_id'], 'idx_post_pbb_block_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_product_block_buttons');
    }
};
