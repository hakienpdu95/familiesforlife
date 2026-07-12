<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('post_articles', function (Blueprint $table) {
            if (!Schema::hasColumn('post_articles', 'main_locale')) {
                $table->string('main_locale', 10)->default('vi');
            }
            if (!Schema::hasColumn('post_articles', 'is_sponsored')) {
                $table->boolean('is_sponsored')->default(false)->after('main_locale');
            }
            if (!Schema::hasColumn('post_articles', 'sponsor_name')) {
                $table->string('sponsor_name', 255)->nullable()->after('is_sponsored');
            }
            if (!Schema::hasColumn('post_articles', 'sponsor_logo_url')) {
                $table->string('sponsor_logo_url', 500)->nullable()->after('sponsor_name');
            }
            if (!Schema::hasColumn('post_articles', 'sponsor_label')) {
                $table->string('sponsor_label', 30)->nullable()->after('sponsor_logo_url');
            }
            if (!Schema::hasColumn('post_articles', 'campaign_code')) {
                $table->string('campaign_code', 50)->nullable()->after('sponsor_label');
            }
            if (!Schema::hasColumn('post_articles', 'sponsored_start_date')) {
                $table->date('sponsored_start_date')->nullable()->after('campaign_code');
            }
            if (!Schema::hasColumn('post_articles', 'sponsored_end_date')) {
                $table->date('sponsored_end_date')->nullable()->after('sponsored_start_date');
            }
            if (!Schema::hasColumn('post_articles', 'sponsored_published_at')) {
                $table->timestamp('sponsored_published_at')->nullable()->after('sponsored_end_date');
            }
            if (!Schema::hasIndex('post_articles', 'idx_post_article_sponsored')) {
                $table->index(['organization_id', 'is_sponsored', 'sponsored_end_date'], 'idx_post_article_sponsored');
            }
        });
    }

    public function down(): void
    {
        Schema::table('post_articles', function (Blueprint $table) {
            $cols = array_filter(['main_locale', 'is_sponsored', 'sponsor_name', 'sponsor_logo_url', 'sponsor_label', 'campaign_code', 'sponsored_start_date', 'sponsored_end_date', 'sponsored_published_at'], fn($c) => Schema::hasColumn('post_articles', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};