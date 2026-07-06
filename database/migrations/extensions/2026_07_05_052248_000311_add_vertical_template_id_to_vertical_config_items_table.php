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
        Schema::table('vertical_config_items', function (Blueprint $table) {
            if (!Schema::hasColumn('vertical_config_items', 'vertical_template_id')) {
                $table->foreignId('vertical_template_id')->constrained('vertical_templates')->cascadeOnDelete()->after('sort_order')->comment('FK -> vertical_templates — thay cho (organization_id, vertical_code)');
            }
            if (!Schema::hasIndex('vertical_config_items', 'uq_vertical_config_item_template')) {
                $table->unique(['vertical_template_id', 'config_group', 'code'], 'uq_vertical_config_item_template');
            }
            if (!Schema::hasIndex('vertical_config_items', 'idx_vertical_config_lookup_template')) {
                $table->index(['vertical_template_id', 'config_group', 'is_active'], 'idx_vertical_config_lookup_template');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vertical_config_items', function (Blueprint $table) {
            if (Schema::hasColumn('vertical_config_items', 'vertical_template_id')) $table->dropForeign(['vertical_template_id']);
            $cols = array_filter(['vertical_template_id'], fn($c) => Schema::hasColumn('vertical_config_items', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};