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
        if (Schema::hasTable('event_category_translations')) {
            return;
        }

        Schema::create('event_category_translations', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('event_category_id')->constrained('event_categories')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 100)->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['event_category_id', 'locale'], 'uq_event_category_translations_locale');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('event_category_translations');
    }
};