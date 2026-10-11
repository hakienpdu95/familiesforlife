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
        Schema::table('post_article_view_events', function (Blueprint $table) {
            if (!Schema::hasIndex('post_article_view_events', 'idx_post_view_events_viewed_at')) {
                $table->index('viewed_at', 'idx_post_view_events_viewed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('post_article_view_events', function (Blueprint $table) {
            $cols = array_filter([], fn($c) => Schema::hasColumn('post_article_view_events', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};