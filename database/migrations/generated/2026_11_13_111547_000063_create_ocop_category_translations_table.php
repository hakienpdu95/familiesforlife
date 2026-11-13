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
        if (Schema::hasTable('ocop_category_translations')) {
            return;
        }

        Schema::create('ocop_category_translations', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('ocop_category_id')->constrained('ocop_categories')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 150)->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['ocop_category_id', 'locale'], 'uq_ocop_category_translations_locale');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_category_translations');
    }
};