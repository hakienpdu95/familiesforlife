<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('post_product_blocks')) {
            return;
        }

        Schema::create('post_product_blocks', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
            $table->string('template', 30)->default('single_card');
            $table->string('heading', 200)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['organization_id', 'article_id'], 'idx_post_pb_org_article');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('post_product_blocks');
    }
};
