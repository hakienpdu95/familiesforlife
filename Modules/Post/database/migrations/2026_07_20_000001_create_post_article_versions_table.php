<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Post_VersionHistory_Technical_Specification.md §6.1 — snapshot append-only theo
 * PostArticleTranslation (không phải PostArticle, xem §0/§4). Không có updated_at/soft-delete
 * — đúng tiền lệ post_publishing_logs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_article_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('translation_id')->constrained('post_article_translations')->cascadeOnDelete();
            $table->unsignedInteger('version_number'); // tăng dần RIÊNG theo từng translation (§6.2)
            $table->string('trigger', 20);             // VersionTrigger: save|publish|restore
            $table->json('snapshot');                  // cấu trúc §4
            $table->string('title_snapshot', 300);      // denormalize title để render danh sách không cần decode JSON
            $table->char('content_hash', 64);           // sha256(snapshot) — so trùng lặp trước khi ghi, §9.3
            $table->unsignedInteger('char_count')->default(0);
            $table->unsignedSmallInteger('block_count')->default(0);
            $table->foreignId('restored_from_version_id')->nullable()->constrained('post_article_versions')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

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
