<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Heritage_Technical_Specification.md §3.7 — many-to-many Post↔HeritageSite, mirror nguyên
 * post_article_ocop_products. Bảng pivot đặt trong Modules/Heritage (module phụ thuộc biết về
 * Post, không ngược lại — Post không cần biết Heritage tồn tại để hoạt động bình thường).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_article_heritage_sites', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
            $table->foreignId('heritage_site_id')->constrained('heritage_sites')->cascadeOnDelete();
            $table->primary(['article_id', 'heritage_site_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_article_heritage_sites');
    }
};
