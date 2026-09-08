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
        if (Schema::hasTable('lead_tag_map')) {
            return;
        }

        Schema::create('lead_tag_map', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('lead_id');
            $table->unsignedSmallInteger('tag_id');
            

            // Indexes
            $table->index(['tag_id', 'lead_id'], 'idx_tag_map_tag');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_tag_map');
    }
};