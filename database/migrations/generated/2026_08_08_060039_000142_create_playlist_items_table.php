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
        if (Schema::hasTable('playlist_items')) {
            return;
        }

        Schema::create('playlist_items', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('playlist_id')->constrained('playlists')->cascadeOnDelete();
            $table->string('itemable_type', 60);
            $table->unsignedBigInteger('itemable_id');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->index(['playlist_id', 'sort_order'], 'idx_playlist_item_sort');
            $table->index(['itemable_type', 'itemable_id'], 'idx_playlist_item_itemable');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('playlist_items');
    }
};