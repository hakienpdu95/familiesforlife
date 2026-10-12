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
        Schema::table('post_content_blocks', function (Blueprint $table) {
            if (Schema::hasColumn('post_content_blocks', 'article_id')) $table->dropForeign(['article_id']);
            if (Schema::hasIndex('post_content_blocks', 'idx_post_cb_article_order')) {
                $table->dropIndex('idx_post_cb_article_order');
            }
            $cols = array_filter(['article_id'], fn($c) => Schema::hasColumn('post_content_blocks', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }

    public function down(): void
    {
        Schema::table('post_content_blocks', function (Blueprint $table) {
            // TODO: $table->unsignedBigInteger('article_id')->...; // add lại 'article_id'
        });
    }
};