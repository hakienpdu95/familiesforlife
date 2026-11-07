<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ocop_products', function (Blueprint $table) {
            $table->string('shopee_url', 500)->nullable()->comment('Link affiliate Shopee');
            $table->string('tiktok_url', 500)->nullable()->after('shopee_url')->comment('Link affiliate TikTok Shop');
        });
    }

    public function down(): void
    {
        Schema::table('ocop_products', function (Blueprint $table) {
            $table->dropColumn(['shopee_url', 'tiktok_url']);
        });
    }
};
