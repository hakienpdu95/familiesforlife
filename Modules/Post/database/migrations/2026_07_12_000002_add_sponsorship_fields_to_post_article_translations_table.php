<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sponsored Content Phase A (spec/dac-ta-ky-thuat-bai-viet-tai-tro.md §3.2) — field per-locale,
 * cộng cột nullable thuần tuý. Không cần index riêng — luôn đọc kèm article_id/locale đã có index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_article_translations', function (Blueprint $table) {
            $table->string('disclosure_text', 500)->nullable()->after('excerpt');
            $table->string('cta_text', 100)->nullable()->after('disclosure_text');
            $table->string('cta_url', 500)->nullable()->after('cta_text');
        });
    }

    public function down(): void
    {
        Schema::table('post_article_translations', function (Blueprint $table) {
            $table->dropColumn(['disclosure_text', 'cta_text', 'cta_url']);
        });
    }
};
