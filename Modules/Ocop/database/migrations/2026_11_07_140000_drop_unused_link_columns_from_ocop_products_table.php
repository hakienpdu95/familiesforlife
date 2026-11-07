<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = ['purchase_url', 'supplier_url', 'supplier_homepage_url'];

    public function up(): void
    {
        $existing = array_values(array_filter(self::COLUMNS, fn (string $c) => Schema::hasColumn('ocop_products', $c)));

        if ($existing) {
            Schema::table('ocop_products', fn (Blueprint $table) => $table->dropColumn($existing));
        }
    }

    public function down(): void
    {
        Schema::table('ocop_products', function (Blueprint $table) {
            $table->string('purchase_url', 500)->nullable();
        });
    }
};
