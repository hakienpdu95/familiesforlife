<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('post_product_block_items')) {
            return;
        }

        Schema::create('post_product_block_items', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('block_id')->constrained('post_product_blocks')->cascadeOnDelete();
            $table->string('item_key', 20);
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('title_override', 200)->nullable();
            $table->string('price_label_override', 100)->nullable();
            $table->text('description_override')->nullable();
            $table->string('image_url_override', 500)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            

            // Indexes
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