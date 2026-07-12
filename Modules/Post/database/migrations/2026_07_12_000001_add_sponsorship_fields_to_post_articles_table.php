<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sponsored Content Phase A (spec/dac-ta-ky-thuat-bai-viet-tai-tro.md §3.1) — field dùng chung
 * mọi bản dịch, cộng cột nullable/default thuần tuý, không đụng dữ liệu cũ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_articles', function (Blueprint $table) {
            $table->boolean('is_sponsored')->default(false)->after('is_featured');
            $table->string('sponsor_name', 255)->nullable()->after('is_sponsored');
            $table->string('sponsor_logo_url', 500)->nullable()->after('sponsor_name');
            $table->string('sponsor_label', 30)->nullable()->after('sponsor_logo_url');
            $table->string('campaign_code', 50)->nullable()->after('sponsor_label');
            $table->date('sponsored_start_date')->nullable()->after('campaign_code');
            $table->date('sponsored_end_date')->nullable()->after('sponsored_start_date');
            $table->timestamp('sponsored_published_at')->nullable()->after('sponsored_end_date');

            // §13 hiệu suất — job hết hạn (chạy daily) + màn hình danh sách lọc "chỉ bài tài trợ"
            // đều query theo is_sponsored + sponsored_end_date, cần composite index.
            $table->index(['organization_id', 'is_sponsored', 'sponsored_end_date'], 'idx_post_article_sponsored');
        });
    }

    public function down(): void
    {
        Schema::table('post_articles', function (Blueprint $table) {
            $table->dropIndex('idx_post_article_sponsored');
            $table->dropColumn([
                'is_sponsored',
                'sponsor_name',
                'sponsor_logo_url',
                'sponsor_label',
                'campaign_code',
                'sponsored_start_date',
                'sponsored_end_date',
                'sponsored_published_at',
            ]);
        });
    }
};
