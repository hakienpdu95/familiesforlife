<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// spec/AIVideoStudioTemplate_Technical_Specification.md §2.1/§0 — công cụ nội bộ nền tảng cho team
// content, KHÔNG organization_id, KHÔNG TenantAwareModel — cùng nhóm content_outlines/content_foundations.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_video_studio_projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // route key

            $table->string('name', 200);
            $table->text('description')->nullable();

            // Anchoring — prefill vào Shot mới lúc TẠO, KHÔNG bắt buộc, KHÔNG khoá cứng (§0).
            $table->text('default_subject')->nullable();
            $table->text('default_style')->nullable();
            $table->text('default_constraints')->nullable();

            $table->string('status', 10)->default('draft'); // draft|active|archived

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_video_studio_projects');
    }
};
