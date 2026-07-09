<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_product_blocks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();      // = placeholder key nhúng trong content HTML
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
            $table->string('template', 30)->default('single_card'); // ProductBlockTemplate
            $table->string('heading', 200)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['organization_id', 'article_id'], 'idx_post_pb_org_article');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_product_blocks');
    }
};
