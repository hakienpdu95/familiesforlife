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
        Schema::table('post_product_block_items', function (Blueprint $table) {
            if (!Schema::hasColumn('post_product_block_items', 'ocop_product_id')) {
                $table->foreignId('ocop_product_id')->nullable()->constrained('ocop_products')->restrictOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('post_product_block_items', function (Blueprint $table) {
            if (Schema::hasColumn('post_product_block_items', 'ocop_product_id')) $table->dropForeign(['ocop_product_id']);
            $cols = array_filter(['ocop_product_id'], fn($c) => Schema::hasColumn('post_product_block_items', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};