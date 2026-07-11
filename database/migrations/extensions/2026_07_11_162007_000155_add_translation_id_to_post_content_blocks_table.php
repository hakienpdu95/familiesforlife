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
            if (!Schema::hasIndex('post_content_blocks', 'idx_post_cb_translation_order')) {
                $table->index(['translation_id', 'sort_order'], 'idx_post_cb_translation_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('post_content_blocks', function (Blueprint $table) {
            $cols = array_filter(['translation_id'], fn($c) => Schema::hasColumn('post_content_blocks', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};