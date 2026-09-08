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
        if (Schema::hasTable('post_article_tag')) {
            return;
        }

        Schema::create('post_article_tag', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('post_tags')->cascadeOnDelete();
            
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('post_article_tag');
    }
};