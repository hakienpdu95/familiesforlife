<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §3 spec/Related_Posts_Engine_Technical_Specification.md — 1 dòng / lượt xem 1 bài viết công
 * khai, gắn với cookie ẩn danh (visitor_hash, KHÔNG phải user_id) — dùng để tính "đồng-xem trong
 * cùng phiên" (co-occurrence) cho thuật toán gợi ý liên quan. Cùng khuôn insert-only event log
 * với post_article_redirect_clicks (không soft-delete, không updated_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_article_view_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
            $table->string('visitor_hash', 64);
            $table->timestamp('viewed_at');

            $table->index(['article_id', 'viewed_at'], 'idx_rp_view_article_viewed');
            $table->index(['visitor_hash', 'viewed_at'], 'idx_rp_view_visitor_viewed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_article_view_events');
    }
};
