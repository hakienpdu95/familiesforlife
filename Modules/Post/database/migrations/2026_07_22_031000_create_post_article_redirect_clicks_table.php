<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1 dòng / lượt click vào bài viết format=redirect (PublicArticleController::show() trước khi
 * redirect()->away()) — bảng rời thay vì chỉ cộng dồn `post_article_translations.view_count`,
 * để thống kê được XU HƯỚNG theo ngày (không chỉ 1 số tổng), phục vụ trang "Thống kê click"
 * (Modules/Post/app/Features/ArticleAuthoring/Http/ArticleAdminController::clicks()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_article_redirect_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
            $table->string('referrer', 500)->nullable();
            $table->timestamp('created_at');

            $table->index(['article_id', 'created_at'], 'idx_post_redirect_click_article_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_article_redirect_clicks');
    }
};
