<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** spec/I18n_Specification.md §5.3 — bản dịch banner (cột gốc banners = bản vi). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banner_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banner_id')->constrained('banners')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('alt_text', 255)->nullable();
            $table->string('badge_label', 40)->nullable();
            $table->timestamps();

            $table->unique(['banner_id', 'locale'], 'uq_banner_translations_locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banner_translations');
    }
};
