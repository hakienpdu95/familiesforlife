<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('post_howto_blocks')) {
            return;
        }

        Schema::create('post_howto_blocks', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('translation_id')->constrained('post_article_translations')->cascadeOnDelete();
            $table->string('name', 200)->nullable()->comment('Tên hướng dẫn (VD: Cách pha sữa công thức đúng chuẩn) — dùng làm HowTo.name nếu có, fallback tiêu đề bài');
            $table->text('description')->nullable()->comment('Mô tả ngắn — dùng làm HowTo.description');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['translation_id', 'sort_order'], 'idx_post_hb_translation_order');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('post_howto_blocks');
    }
};
