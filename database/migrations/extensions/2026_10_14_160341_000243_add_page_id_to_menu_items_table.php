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
        Schema::table('menu_items', function (Blueprint $table) {
            if (!Schema::hasColumn('menu_items', 'page_id')) {
                $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            }
            if (!Schema::hasIndex('menu_items', 'idx_menu_item_page')) {
                $table->index(['page_id', 'location'], 'idx_menu_item_page');
            }
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            if (Schema::hasColumn('menu_items', 'page_id')) $table->dropForeign(['page_id']);
            $cols = array_filter(['page_id'], fn($c) => Schema::hasColumn('menu_items', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};