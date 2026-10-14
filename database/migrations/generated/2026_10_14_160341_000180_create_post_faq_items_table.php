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
        if (Schema::hasTable('post_faq_items')) {
            return;
        }

        Schema::create('post_faq_items', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('faq_block_id')->constrained('post_faq_blocks')->cascadeOnDelete();
            $table->string('question', 500);
            $table->text('answer');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->index(['faq_block_id', 'sort_order'], 'idx_post_fi_block_order');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('post_faq_items');
    }
};