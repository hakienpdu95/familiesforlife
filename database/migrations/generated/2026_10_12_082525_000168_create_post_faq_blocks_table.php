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
        if (Schema::hasTable('post_faq_blocks')) {
            return;
        }

        Schema::create('post_faq_blocks', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('translation_id')->constrained('post_article_translations')->cascadeOnDelete();
            $table->string('heading', 200)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->index(['translation_id', 'sort_order'], 'idx_post_fb_translation_order');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('post_faq_blocks');
    }
};