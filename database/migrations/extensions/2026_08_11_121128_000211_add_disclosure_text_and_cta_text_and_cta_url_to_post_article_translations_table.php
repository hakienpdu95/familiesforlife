<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_article_translations', function (Blueprint $table) {
            if (! Schema::hasColumn('post_article_translations', 'disclosure_text')) {
                $table->string('disclosure_text', 500)->nullable();
            }
            if (! Schema::hasColumn('post_article_translations', 'cta_text')) {
                $table->string('cta_text', 100)->nullable()->after('disclosure_text');
            }
            if (! Schema::hasColumn('post_article_translations', 'cta_url')) {
                $table->string('cta_url', 500)->nullable()->after('cta_text');
            }
            if (! Schema::hasColumn('post_article_translations', 'direct_answer')) {
                $table->string('direct_answer', 500)->nullable()->after('cta_url')->comment('AEO — câu trả lời trực tiếp hiển thị nổi bật đầu bài (khuyến nghị ~60 từ), khác excerpt (excerpt là mô tả/preview, direct_answer là câu TRẢ LỜI thẳng câu hỏi chính của bài)');
            }
            if (! Schema::hasIndex('post_article_translations', 'uq_post_trans_locale_slug')) {
                $table->unique(['locale', 'slug'], 'uq_post_trans_locale_slug');
            }
            if (! Schema::hasIndex('post_article_translations', 'idx_post_trans_status_pub')) {
                $table->index(['locale', 'status', 'published_at'], 'idx_post_trans_status_pub');
            }
            if (! Schema::hasColumn('post_article_translations', 'ga_views_30d')) {
                $table->unsignedInteger('ga_views_30d')->nullable()->after('direct_answer');
            }
            if (! Schema::hasColumn('post_article_translations', 'ga_synced_at')) {
                $table->timestamp('ga_synced_at')->nullable()->after('ga_views_30d');
            }
            if (! Schema::hasIndex('post_article_translations', 'post_article_translations_ga_views_30d_index')) {
                $table->index('ga_views_30d');
            }
        });
    }

    public function down(): void
    {
        Schema::table('post_article_translations', function (Blueprint $table) {
            $cols = array_filter(['disclosure_text', 'cta_text', 'cta_url', 'direct_answer', 'ga_views_30d', 'ga_synced_at'], fn ($c) => Schema::hasColumn('post_article_translations', $c));
            if (! empty($cols)) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
