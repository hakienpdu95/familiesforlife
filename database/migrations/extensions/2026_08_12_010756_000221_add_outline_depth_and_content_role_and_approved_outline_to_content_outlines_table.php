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
        Schema::table('content_outlines', function (Blueprint $table) {
            if (!Schema::hasColumn('content_outlines', 'outline_depth')) {
                $table->string('outline_depth', 10)->default('standard');
            }
            if (!Schema::hasColumn('content_outlines', 'content_role')) {
                $table->string('content_role', 10)->nullable()->after('outline_depth');
            }
            if (!Schema::hasColumn('content_outlines', 'approved_outline')) {
                $table->longText('approved_outline')->nullable()->after('content_role');
            }
            if (!Schema::hasColumn('content_outlines', 'article_draft_prompt')) {
                $table->longText('article_draft_prompt')->nullable()->after('approved_outline');
            }
            if (!Schema::hasColumn('content_outlines', 'cta_url')) {
                $table->string('cta_url', 500)->nullable()->after('article_draft_prompt');
            }
            if (!Schema::hasColumn('content_outlines', 'drafted_article')) {
                $table->longText('drafted_article')->nullable()->after('cta_url');
            }
            if (!Schema::hasColumn('content_outlines', 'review_prompt')) {
                $table->longText('review_prompt')->nullable()->after('drafted_article');
            }
        });
    }

    public function down(): void
    {
        Schema::table('content_outlines', function (Blueprint $table) {
            $cols = array_filter(['outline_depth', 'content_role', 'approved_outline', 'article_draft_prompt', 'cta_url', 'drafted_article', 'review_prompt'], fn($c) => Schema::hasColumn('content_outlines', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};