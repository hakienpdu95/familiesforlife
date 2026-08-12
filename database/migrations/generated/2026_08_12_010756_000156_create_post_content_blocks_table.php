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
        if (Schema::hasTable('post_content_blocks')) {
            return;
        }

        Schema::create('post_content_blocks', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
            $table->string('type', 20);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->longText('text_html')->nullable();
            $table->foreignId('product_block_id')->nullable()->constrained('post_product_blocks')->cascadeOnDelete();
            $table->timestamps();
            

            // Indexes
            $table->index(['article_id', 'sort_order'], 'idx_post_cb_article_order');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('post_content_blocks');
    }
};