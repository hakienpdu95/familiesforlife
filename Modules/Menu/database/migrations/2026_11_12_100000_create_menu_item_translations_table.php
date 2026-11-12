<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** spec/I18n_Specification.md §5.3 — nhãn mục menu theo locale (cột gốc menu_items.label = bản vi). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_item_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('label', 255)->nullable();
            $table->timestamps();

            $table->unique(['menu_item_id', 'locale'], 'uq_menu_item_trans_locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_translations');
    }
};
