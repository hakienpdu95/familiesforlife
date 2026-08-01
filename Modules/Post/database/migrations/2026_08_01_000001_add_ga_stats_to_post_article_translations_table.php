<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/ga-dashboard-statistics.md §3/§9.1 — cột denormalized cho lượt xem GA4 (30 ngày gần
 * nhất), đồng bộ định kỳ bởi SyncGoogleAnalyticsStatsCommand — KHÔNG gọi Google Analytics API
 * trực tiếp trong request danh sách bài viết (Tabulator remote sort/pagination sẽ vượt rate
 * limit nếu gọi live). Index trên ga_views_30d để ORDER BY không full table scan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_article_translations', function (Blueprint $table) {
            $table->unsignedInteger('ga_views_30d')->nullable()->after('view_count');
            $table->timestamp('ga_synced_at')->nullable()->after('ga_views_30d');
            $table->index('ga_views_30d');
        });
    }

    public function down(): void
    {
        Schema::table('post_article_translations', function (Blueprint $table) {
            $table->dropIndex(['ga_views_30d']);
            $table->dropColumn(['ga_views_30d', 'ga_synced_at']);
        });
    }
};
