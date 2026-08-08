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
        if (Schema::hasTable('post_comparison_rows')) {
            return;
        }

        Schema::create('post_comparison_rows', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('comparison_block_id')->constrained('post_comparison_blocks')->cascadeOnDelete();
            $table->string('label', 150);
            $table->json('values');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->index(['comparison_block_id', 'sort_order'], 'idx_post_comparison_row_block_order');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('post_comparison_rows');
    }
};