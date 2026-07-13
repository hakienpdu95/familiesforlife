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
        Schema::table('post_article_translations', function (Blueprint $table) {
            if (!Schema::hasColumn('post_article_translations', 'disclosure_text')) {
                $table->string('disclosure_text', 500)->nullable();
            }
            if (!Schema::hasColumn('post_article_translations', 'cta_text')) {
                $table->string('cta_text', 100)->nullable()->after('disclosure_text');
            }
            if (!Schema::hasColumn('post_article_translations', 'cta_url')) {
                $table->string('cta_url', 500)->nullable()->after('cta_text');
            }
            if (!Schema::hasIndex('post_article_translations', 'uq_post_trans_locale_slug')) {
                $table->unique(['locale', 'slug'], 'uq_post_trans_locale_slug');
            }
            if (!Schema::hasIndex('post_article_translations', 'idx_post_trans_status_pub')) {
                $table->index(['locale', 'status', 'published_at'], 'idx_post_trans_status_pub');
            }
        });
    }

    public function down(): void
    {
        Schema::table('post_article_translations', function (Blueprint $table) {
            $cols = array_filter(['disclosure_text', 'cta_text', 'cta_url'], fn($c) => Schema::hasColumn('post_article_translations', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};