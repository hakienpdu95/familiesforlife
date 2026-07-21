<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/ContentBrief_Technical_Specification.md §2.2.1 — append-only, KHÔNG soft delete (audit
 * trail bất biến, cùng nguyên tắc post_article_versions). Toàn bộ nội dung brief nằm ở
 * `snapshot` (json) — "hiện tại" = version mới nhất theo version_number (Document-oriented).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_brief_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('content_brief_id')->constrained('content_briefs')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();

            $table->unsignedInteger('version_number');
            $table->string('status', 20)->default('draft'); // BriefVersionStatus — §3.1

            $table->json('snapshot');           // toàn bộ nội dung brief — §2.3
            $table->string('content_hash', 64); // sha256(canonical json) — chặn ghi trùng (§3.5)

            $table->string('trigger', 20);      // BriefVersionTrigger — §3.2
            $table->foreignId('restored_from_version_id')->nullable()
                ->constrained('content_brief_versions')->nullOnDelete();

            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejected_reason', 500)->nullable();

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['content_brief_id', 'version_number'], 'uq_brief_version_number');
            $table->index(['content_brief_id', 'status'], 'idx_brief_version_status');
        });

        Schema::table('content_briefs', function (Blueprint $table) {
            $table->foreign('current_version_id')->references('id')->on('content_brief_versions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('content_briefs', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });

        Schema::dropIfExists('content_brief_versions');
    }
};
