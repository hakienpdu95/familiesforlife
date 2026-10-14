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
        if (Schema::hasTable('post_product_block_buttons')) {
            return;
        }

        Schema::create('post_product_block_buttons', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('block_id')->constrained('post_product_blocks')->cascadeOnDelete();
            $table->foreignId('block_item_id')->nullable()->constrained('post_product_block_items')->cascadeOnDelete();
            $table->string('button_key', 20);
            $table->string('label', 60)->nullable();
            $table->string('url_type', 20);
            $table->string('url', 500)->nullable();
            $table->string('product_link_type', 30)->nullable();
            $table->string('target', 10)->default('_blank');
            $table->string('style', 20)->default('primary');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedInteger('click_count')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->unique(['block_id', 'button_key'], 'uq_post_pbb_block_key');
            $table->index(['block_id', 'block_item_id'], 'idx_post_pbb_block_item');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('post_product_block_buttons');
    }
};