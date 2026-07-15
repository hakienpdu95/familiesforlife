<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Province_Showcase_Technical_Specification.md §3.4/§3.4.1 — many-to-many Post↔OCOP: 1 bài
 * viết thường nhắc nhiều sản phẩm, 1 sản phẩm có thể được nhắc trong nhiều bài — quan hệ N-N
 * thực tế, không phải 1-1. Bảng pivot đặt trong Modules/Ocop (module phụ thuộc biết về Post,
 * không ngược lại — Post không cần biết OCOP tồn tại để hoạt động bình thường).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_article_ocop_products', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
            $table->foreignId('ocop_product_id')->constrained('ocop_products')->cascadeOnDelete();
            $table->primary(['article_id', 'ocop_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_article_ocop_products');
    }
};
