<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// spec/PromptFrameworkStudio_Technical_Specification.md §3.1 — KHÔNG organization_id, KHÔNG
// TenantAwareModel, KHÔNG soft-delete — cùng nhóm content_outlines/content_foundations.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_prompts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // route key — cùng quy ước PostCategory/ContentOutline

            $table->string('framework_key', 30); // khớp key trong config('prompt_framework_studio.frameworks') — validate ở FormRequest (§5.1), KHÔNG FK vì nguồn là config chứ không phải bảng
            $table->string('label', 150); // tên người dùng tự đặt để nhận diện trong danh sách quản lý
            $table->json('field_values'); // {field_key: giá trị} — dùng để tải lại form khi sửa/sinh lại
            $table->longText('rendered_prompt'); // kết quả ghép cuối cùng — ghi đè khi "Sinh lại" (không versioning)

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('framework_key');
            $table->index('created_at');
            $table->index('label'); // §3.1 (v1.1) — trang quản lý tìm/sắp theo tên người dùng tự đặt
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_prompts');
    }
};
