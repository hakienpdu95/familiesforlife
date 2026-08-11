<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('post_article_translations')) {
            return;
        }

        Schema::create('post_article_translations', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('locale', 10);
            $table->string('title', 300);
            $table->string('slug', 320);
            $table->string('excerpt', 500)->nullable();
            $table->string('seo_title', 200)->nullable();
            $table->string('seo_description', 300)->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->string('unpublish_reason', 500)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['article_id', 'locale'], 'uq_post_trans_article_locale');
            $table->unique(['organization_id', 'locale', 'slug'], 'uq_post_trans_org_locale_slug');
            $table->index(['organization_id', 'locale', 'status', 'published_at'], 'idx_post_trans_org_status_pub');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('post_article_translations');
    }
};
