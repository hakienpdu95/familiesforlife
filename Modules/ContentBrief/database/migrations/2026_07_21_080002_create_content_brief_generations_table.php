<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/ContentBrief_Technical_Specification.md §2.2.2/§6 — bản ghi trạng thái "đã yêu cầu sinh
 * nội dung từ version nào". KHÔNG có cột nào tham chiếu sang bài viết/module khác — đây là
 * điểm dừng cuối cùng của dữ liệu trong phạm vi module (output JSON chuẩn hoá qua GenerationOutputData).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_brief_generations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('content_brief_version_id')->constrained('content_brief_versions')->restrictOnDelete();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();

            $table->string('status', 20)->default('pending'); // GenerationStatus — §6.0
            $table->json('output')->nullable();  // JSON đã chuẩn hoá theo GenerationOutputData (§6.1)
            $table->string('error_message', 500)->nullable();

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['content_brief_version_id'], 'idx_brief_gen_version');
            $table->index(['status'], 'idx_brief_gen_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_brief_generations');
    }
};
