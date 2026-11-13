<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ocop_product_translations')) {
            return;
        }

        Schema::create('ocop_product_translations', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('ocop_product_id')->constrained('ocop_products')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 150)->nullable();
            $table->string('slug', 180)->nullable();
            $table->text('description')->nullable();
            $table->longText('story')->nullable();
            $table->string('origin', 255)->nullable();
            $table->text('ingredients')->nullable();
            $table->text('usage_instructions')->nullable();
            $table->text('storage_instructions')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['ocop_product_id', 'locale'], 'uq_ocop_product_translations_locale');
            $table->unique(['locale', 'slug'], 'uq_ocop_product_translations_slug');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_product_translations');
    }
};