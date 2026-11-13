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
        if (Schema::hasTable('post_category_translations')) {
            return;
        }

        Schema::create('post_category_translations', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('post_category_id')->constrained('post_categories')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 255)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['post_category_id', 'locale'], 'uq_post_category_trans_locale');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('post_category_translations');
    }
};