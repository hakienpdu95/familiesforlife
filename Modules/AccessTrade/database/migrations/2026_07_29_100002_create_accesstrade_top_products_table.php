<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot của AccessTrade Publisher API "top_products". merchant mặc định '' (KHÔNG null) khi
 * đồng bộ không filter theo merchant — tránh rủi ro NULL trong unique index khác nhau giữa các
 * driver DB (SQLite dev / MySQL production coi nhiều NULL là không trùng, còn '' thì luôn so
 * sánh được ổn định).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accesstrade_top_products', function (Blueprint $table) {
            $table->id();
            $table->string('external_product_id', 64);
            $table->string('merchant', 120)->default('');

            $table->string('name', 255);
            $table->string('category_id', 64)->nullable();
            $table->string('category_name', 255)->nullable();
            $table->decimal('price', 14, 2)->nullable();
            $table->decimal('discount', 14, 2)->nullable();
            $table->string('image', 2048)->nullable();
            $table->string('link', 2048)->nullable();
            $table->string('aff_link', 2048)->nullable();
            $table->text('desc')->nullable();
            $table->unsignedInteger('total')->nullable();
            $table->string('brand', 255)->nullable();
            $table->string('product_category', 255)->nullable();

            $table->date('synced_date_from')->nullable();
            $table->date('synced_date_to')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['merchant', 'external_product_id'], 'uk_accesstrade_top_products_merchant_product');
            $table->index('brand', 'idx_accesstrade_top_products_brand');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accesstrade_top_products');
    }
};
