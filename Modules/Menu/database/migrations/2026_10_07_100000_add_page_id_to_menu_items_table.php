<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** MenuLinkType::Page — mục menu trỏ tới trang tĩnh (Modules/Page), URL resolve theo slug hiện tại. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->foreignId('page_id')->nullable()->after('category_id')->constrained('pages')->nullOnDelete();
            $table->index(['page_id', 'location'], 'idx_menu_item_page');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropIndex('idx_menu_item_page');
            $table->dropConstrainedForeignId('page_id');
        });
    }
};
