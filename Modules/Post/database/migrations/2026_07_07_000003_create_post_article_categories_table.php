<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_article_categories', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('post_categories')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);   // dùng cho breadcrumb + URL canonical
            $table->primary(['article_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_article_categories');
    }
};
