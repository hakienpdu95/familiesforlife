<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Breaking_News_Ticker_Technical_Specification.md §3 — "đánh dấu nóng" 1 bài viết đã
 * published, có lịch hiển thị theo GIỜ (không phải ngày, khác Banner/Sponsored) vì tin nóng
 * thường chỉ nóng 24-48h. 1 article có thể có nhiều dòng lịch sử (không unique article_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_breaking_news', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
            $table->string('headline_override', 300)->nullable();
            $table->string('badge_label', 40)->nullable();

            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'starts_at', 'ends_at'], 'idx_breaking_news_active_window');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_breaking_news');
    }
};
