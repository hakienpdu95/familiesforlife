<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('post_article_view_events')) {
            return;
        }

        Schema::create('post_article_view_events', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
            $table->string('visitor_hash', 64);
            $table->timestamp('viewed_at')->useCurrent();

            // Indexes
            $table->index(['article_id', 'viewed_at'], 'idx_rp_view_article_viewed');
            $table->index(['visitor_hash', 'viewed_at'], 'idx_rp_view_visitor_viewed');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('post_article_view_events');
    }
};
