<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('post_articles')) {
            return;
        }

        Schema::create('post_articles', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('title', 300);
            $table->string('slug', 320);
            $table->string('excerpt', 500)->nullable();
            $table->longText('content')->nullable();
            $table->string('format', 20)->default('article');
            $table->string('status', 20)->default('draft');
            $table->string('cover_image_url', 500)->nullable();
            $table->timestamp('published_at')->nullable();
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
            

            // Indexes
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