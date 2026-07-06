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
        if (Schema::hasTable('roadmap_milestone_kc_items')) {
            return;
        }

        Schema::create('roadmap_milestone_kc_items', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('roadmap_milestone_id');
            $table->unsignedBigInteger('kc_item_id');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->unique(['roadmap_milestone_id', 'kc_item_id'], 'rm_kc_unique');
            $table->index('kc_item_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('roadmap_milestone_kc_items');
    }
};