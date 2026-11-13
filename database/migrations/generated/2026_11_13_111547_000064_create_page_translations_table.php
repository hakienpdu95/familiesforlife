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
        if (Schema::hasTable('page_translations')) {
            return;
        }

        Schema::create('page_translations', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title', 255)->nullable();
            $table->string('slug', 255)->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('seo_title', 255)->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['page_id', 'locale'], 'uq_page_trans_locale');
            $table->unique(['locale', 'slug'], 'uq_page_trans_locale_slug');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('page_translations');
    }
};