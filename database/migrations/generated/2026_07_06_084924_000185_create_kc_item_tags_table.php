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
        if (Schema::hasTable('kc_item_tags')) {
            return;
        }

        Schema::create('kc_item_tags', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('item_id')->constrained('kc_items')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('kc_tags')->cascadeOnDelete();
            

            // Indexes
            $table->unique(['item_id', 'tag_id'], 'uq_kc_item_tag');
            $table->index('tag_id', 'idx_kc_item_tag_tag');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('kc_item_tags');
    }
};