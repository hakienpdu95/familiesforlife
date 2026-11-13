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
        if (Schema::hasTable('menu_item_translations')) {
            return;
        }

        Schema::create('menu_item_translations', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('label', 255)->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['menu_item_id', 'locale'], 'uq_menu_item_trans_locale');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_translations');
    }
};