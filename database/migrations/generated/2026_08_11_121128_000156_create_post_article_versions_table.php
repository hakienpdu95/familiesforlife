<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('post_article_versions')) {
            return;
        }

        Schema::create('post_article_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('translation_id')->constrained('post_article_translations')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('trigger', 20);
            $table->json('snapshot');
            $table->string('title_snapshot', 300);
            $table->char('content_hash', 64);
            $table->unsignedInteger('char_count')->default(0);
            $table->unsignedSmallInteger('block_count')->default(0);
            $table->foreignId('restored_from_version_id')->nullable()->constrained('post_article_versions')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            // Indexes
            $table->unique(['translation_id', 'version_number'], 'uq_post_version_translation_number');
            $table->index(['translation_id', 'created_at'], 'idx_post_version_translation_created');
            $table->index(['translation_id', 'content_hash'], 'idx_post_version_translation_hash');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('post_article_versions');
    }
};
