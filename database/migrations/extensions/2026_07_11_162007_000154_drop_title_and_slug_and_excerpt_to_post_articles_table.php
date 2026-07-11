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
            if (Schema::hasColumn('post_articles', 'approved_by')) $table->dropForeign(['approved_by']);
            if (Schema::hasIndex('post_articles', 'uq_post_article_org_slug')) {
                $table->dropUnique('uq_post_article_org_slug');
            }
            if (Schema::hasIndex('post_articles', 'idx_post_article_org_status_pub')) {
                $table->dropIndex('idx_post_article_org_status_pub');
            }
            $cols = array_filter(['title', 'slug', 'excerpt', 'status', 'published_at', 'seo_title', 'seo_description', 'approved_by', 'approved_at', 'view_count'], fn($c) => Schema::hasColumn('post_articles', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }

    public function down(): void
    {
        Schema::table('post_articles', function (Blueprint $table) {
            // TODO: $table->string('title')->...; // add lại 'title'
            // TODO: $table->string('slug')->...; // add lại 'slug'
            // TODO: $table->string('excerpt')->...; // add lại 'excerpt'
            // TODO: $table->string('status')->...; // add lại 'status'
            // TODO: $table->timestamp('published_at')->...; // add lại 'published_at'
            // TODO: $table->string('seo_title')->...; // add lại 'seo_title'
            // TODO: $table->string('seo_description')->...; // add lại 'seo_description'
            // TODO: $table->unsignedBigInteger('approved_by')->...; // add lại 'approved_by'
            // TODO: $table->timestamp('approved_at')->...; // add lại 'approved_at'
            // TODO: $table->unsignedBigInteger('view_count')->...; // add lại 'view_count'
        });
    }
};