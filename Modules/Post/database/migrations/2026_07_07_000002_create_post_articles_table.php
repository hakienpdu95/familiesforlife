<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_articles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('title', 300);
            $table->string('slug', 320);
            $table->string('excerpt', 500)->nullable();
            $table->longText('content')->nullable();               // HTML từ Jodit, sau này chứa placeholder product-block (Phase 5)
            $table->string('format', 20)->default('article');       // ArticleFormat: article|video|activity|tip|step_by_step
            $table->string('status', 20)->default('draft');         // ArticleStatus: draft|pending_review|published|scheduled|archived
            $table->string('cover_image_url', 500)->nullable();
            $table->timestamp('published_at')->nullable();          // set khi publish; > now() khi scheduled
            $table->string('seo_title', 200)->nullable();
            $table->string('seo_description', 300)->nullable();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'slug'], 'uq_post_article_org_slug');
            $table->index(['organization_id', 'status', 'published_at'], 'idx_post_article_org_status_pub');
            $table->index(['organization_id', 'format'], 'idx_post_article_org_format');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_articles');
    }
};
