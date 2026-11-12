<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** spec/I18n_Specification.md §5.3 — bản dịch chuyên mục bài viết (cột gốc post_categories = bản vi). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_category_id')->constrained('post_categories')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 255)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['post_category_id', 'locale'], 'uq_post_category_trans_locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_category_translations');
    }
};
