<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('content_outlines')) {
            return;
        }

        Schema::create('content_outlines', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('label', 200);
            $table->string('topic', 300);
            $table->string('target_keyword', 150);
            $table->string('secondary_keywords', 500)->nullable();
            $table->string('search_intent', 20)->nullable();
            $table->foreignId('post_category_id')->nullable()->constrained('post_categories')->nullOnDelete();
            $table->string('target_audience', 500)->nullable();
            $table->text('content_goal')->nullable();
            $table->text('tone_style')->nullable();
            $table->text('competitor_urls')->nullable();
            $table->unsignedInteger('desired_word_count')->nullable();
            $table->string('language', 5)->default('vi');
            $table->text('additional_notes')->nullable();
            $table->longText('generated_prompt');
            $table->foreignId('linked_post_article_id')->nullable()->constrained('post_articles')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes
            $table->index('post_category_id');
            $table->index('created_at');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('content_outlines');
    }
};
