<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * format = 'redirect' (ArticleFormat) — bài viết không có nội dung riêng, chỉ dẫn link ra
 * nguồn khác. redirect_url chỉ có ý nghĩa khi format = redirect, NULL ở mọi format khác
 * (validate + null-out ở ArticleAdminController/CreateArticleAction/UpdateArticleAction).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_articles', function (Blueprint $table) {
            $table->string('redirect_url', 500)->nullable()->after('format');
        });
    }

    public function down(): void
    {
        Schema::table('post_articles', function (Blueprint $table) {
            $table->dropColumn('redirect_url');
        });
    }
};
