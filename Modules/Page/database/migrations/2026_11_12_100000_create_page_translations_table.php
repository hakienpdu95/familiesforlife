<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** spec/I18n_Specification.md §5.3 — bản dịch trang tĩnh (cột gốc pages = bản vi), slug riêng theo locale. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title', 255)->nullable();
            $table->string('slug', 255)->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('seo_title', 255)->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->timestamps();

            $table->unique(['page_id', 'locale'], 'uq_page_trans_locale');
            $table->unique(['locale', 'slug'], 'uq_page_trans_locale_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_translations');
    }
};
