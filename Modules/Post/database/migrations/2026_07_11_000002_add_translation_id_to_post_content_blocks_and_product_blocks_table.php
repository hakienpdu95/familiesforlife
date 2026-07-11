<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 12a (spec/PublishingEngine_Technical_Specification.md §3.3) — bước 2/5.
 * Cột `translation_id` NULLABLE — 2 bảng con đã có dữ liệu (article_id NOT NULL vẫn giữ
 * nguyên). Backfill (post:backfill-translations, Phase 12b) sẽ điền cột này; Migration #4
 * (Phase 12c) mới set NOT NULL + drop `article_id`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_content_blocks', function (Blueprint $table) {
            $table->foreignId('translation_id')->nullable()->after('organization_id')
                ->constrained('post_article_translations')->cascadeOnDelete();
            $table->index(['translation_id', 'sort_order'], 'idx_post_cb_translation_order');
        });

        Schema::table('post_product_blocks', function (Blueprint $table) {
            $table->foreignId('translation_id')->nullable()->after('organization_id')
                ->constrained('post_article_translations')->cascadeOnDelete();
            $table->index(['organization_id', 'translation_id'], 'idx_post_pb_org_translation');
        });
    }

    public function down(): void
    {
        Schema::table('post_content_blocks', function (Blueprint $table) {
            $table->dropIndex('idx_post_cb_translation_order');
            $table->dropForeign(['translation_id']);
            $table->dropColumn('translation_id');
        });

        Schema::table('post_product_blocks', function (Blueprint $table) {
            $table->dropIndex('idx_post_pb_org_translation');
            $table->dropForeign(['translation_id']);
            $table->dropColumn('translation_id');
        });
    }
};
