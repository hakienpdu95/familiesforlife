<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_article_view_events', function (Blueprint $table) {
            $table->index('viewed_at', 'idx_post_view_events_viewed_at');
        });

        Schema::table('post_article_translations', function (Blueprint $table) {
            $table->index(['status', 'published_at'], 'idx_post_trans_status_published');
        });
    }

    public function down(): void
    {
        Schema::table('post_article_view_events', function (Blueprint $table) {
            $table->dropIndex('idx_post_view_events_viewed_at');
        });

        Schema::table('post_article_translations', function (Blueprint $table) {
            $table->dropIndex('idx_post_trans_status_published');
        });
    }
};
