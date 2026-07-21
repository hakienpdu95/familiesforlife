<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/ContentBrief_Technical_Specification.md §2.2 — bản ghi định danh, KHÔNG chứa nội dung
 * brief (toàn bộ nội dung nằm ở content_brief_versions.snapshot). Tenant-scoped (organization_id)
 * — cùng mô hình PostArticle, khác Page/Menu/Banner (platform-wide).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_briefs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();

            $table->string('title', 200);
            $table->string('target_keyword', 150);
            $table->string('category_label', 100)->nullable(); // gợi ý phân loại tự do — KHÔNG FK (§0)
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status', 20)->default('draft'); // BriefVersionStatus — denormalize (§3.3)
            $table->foreignId('current_version_id')->nullable(); // FK thêm ở migration kế tiếp (§2.2.1)

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status'], 'idx_brief_org_status');
            $table->index(['organization_id', 'target_keyword'], 'idx_brief_org_keyword');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_briefs');
    }
};
