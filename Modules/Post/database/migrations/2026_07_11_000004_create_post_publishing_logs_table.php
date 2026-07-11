<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 12c (spec/PublishingEngine_Technical_Specification.md §3.4) — Migration #5.
 * Audit log append-only cho mọi action publish/schedule/unpublish/takedown/archive/approve.
 * Không có updated_at — log không sửa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_publishing_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('translation_id')->constrained('post_article_translations')->cascadeOnDelete();
            $table->string('action', 20); // publish|schedule|cancel_schedule|unpublish|takedown|archive|approve
            $table->string('reason', 500)->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['translation_id', 'created_at'], 'idx_post_pub_log_translation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_publishing_logs');
    }
};
