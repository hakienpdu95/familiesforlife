<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** spec/I18n_Specification.md §5.3 — bản dịch danh mục OCOP chính thức (seed bởi OcopCategorySeeder). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocop_category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ocop_category_id')->constrained('ocop_categories')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 150)->nullable();
            $table->timestamps();

            $table->unique(['ocop_category_id', 'locale'], 'uq_ocop_category_translations_locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_category_translations');
    }
};
