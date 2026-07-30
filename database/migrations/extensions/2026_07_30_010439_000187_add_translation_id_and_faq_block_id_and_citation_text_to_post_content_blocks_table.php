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
            if (!Schema::hasColumn('post_content_blocks', 'translation_id')) {
                $table->unsignedBigInteger('translation_id')->nullable();
            }
            if (!Schema::hasColumn('post_content_blocks', 'faq_block_id')) {
                $table->foreignId('faq_block_id')->nullable()->constrained('post_faq_blocks')->cascadeOnDelete()->after('translation_id');
            }
            if (!Schema::hasColumn('post_content_blocks', 'citation_text')) {
                $table->text('citation_text')->nullable()->after('faq_block_id')->comment('Nội dung trích dẫn/thống kê — chỉ dùng khi type=citation');
            }
            if (!Schema::hasColumn('post_content_blocks', 'citation_source_name')) {
                $table->string('citation_source_name', 200)->nullable()->after('citation_text')->comment('Tên nguồn (VD: Bộ Y tế, 2026) — chỉ dùng khi type=citation');
            }
            if (!Schema::hasColumn('post_content_blocks', 'citation_source_url')) {
                $table->string('citation_source_url', 500)->nullable()->after('citation_source_name')->comment('Link nguồn (không bắt buộc) — chỉ dùng khi type=citation');
            }
            if (!Schema::hasColumn('post_content_blocks', 'howto_block_id')) {
                $table->foreignId('howto_block_id')->nullable()->constrained('post_howto_blocks')->cascadeOnDelete()->after('citation_source_url');
            }
            if (!Schema::hasIndex('post_content_blocks', 'idx_post_cb_translation_order')) {
                $table->index(['translation_id', 'sort_order'], 'idx_post_cb_translation_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('post_content_blocks', function (Blueprint $table) {
            if (Schema::hasColumn('post_content_blocks', 'faq_block_id')) $table->dropForeign(['faq_block_id']);
            if (Schema::hasColumn('post_content_blocks', 'howto_block_id')) $table->dropForeign(['howto_block_id']);
            $cols = array_filter(['translation_id', 'faq_block_id', 'citation_text', 'citation_source_name', 'citation_source_url', 'howto_block_id'], fn($c) => Schema::hasColumn('post_content_blocks', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};