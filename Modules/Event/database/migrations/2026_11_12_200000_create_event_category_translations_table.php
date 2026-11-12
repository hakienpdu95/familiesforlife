<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** spec/I18n_Specification.md §5.3 — bản dịch danh mục sự kiện (cột gốc event_categories = bản vi). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_category_id')->constrained('event_categories')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 100)->nullable();
            $table->timestamps();

            $table->unique(['event_category_id', 'locale'], 'uq_event_category_translations_locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_category_translations');
    }
};
