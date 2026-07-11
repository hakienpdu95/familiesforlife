<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 12a (spec/PublishingEngine_Technical_Specification.md §3.1-3.2) — bước 1/5.
 * Chỉ tạo bảng mới + thêm cột `main_locale`, KHÔNG đụng dữ liệu/cột cũ của `post_articles`.
 * Cột title/slug/status/... trên post_articles giữ nguyên tới Migration #4 (finalize).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_articles', function (Blueprint $table) {
            $table->string('main_locale', 10)->default('vi')->after('uuid');
        });

        Schema::create('post_article_translations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('locale', 10); // danh sách hợp lệ: config('post.locales')

            $table->string('title', 300);
            $table->string('slug', 320);
            $table->string('excerpt', 500)->nullable();
            $table->string('seo_title', 200)->nullable();
            $table->string('seo_description', 300)->nullable();

            $table->string('status', 20)->default('draft'); // TranslationStatus
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->string('unpublish_reason', 500)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['article_id', 'locale'], 'uq_post_trans_article_locale');
            $table->unique(['organization_id', 'locale', 'slug'], 'uq_post_trans_org_locale_slug');
            $table->index(['organization_id', 'locale', 'status', 'published_at'], 'idx_post_trans_org_status_pub');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_article_translations');

        Schema::table('post_articles', function (Blueprint $table) {
            $table->dropColumn('main_locale');
        });
    }
};
