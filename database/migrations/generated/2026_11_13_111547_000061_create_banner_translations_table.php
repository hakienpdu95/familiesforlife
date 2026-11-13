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
        if (Schema::hasTable('banner_translations')) {
            return;
        }

        Schema::create('banner_translations', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('banner_id')->constrained('banners')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('alt_text', 255)->nullable();
            $table->string('badge_label', 40)->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['banner_id', 'locale'], 'uq_banner_translations_locale');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('banner_translations');
    }
};