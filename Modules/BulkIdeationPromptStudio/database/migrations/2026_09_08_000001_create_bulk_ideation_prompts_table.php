<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Công cụ nội bộ đội content, KHÔNG organization_id, KHÔNG TenantAwareModel — cùng nhóm
// generated_prompts (PromptFrameworkStudio)/video_series_prompts (VideoSeriesPromptStudio): chỉ
// LƯU LẠI văn bản prompt đã ghép, KHÔNG gọi AI Provider trong app.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_ideation_prompts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // route key

            $table->foreignId('post_category_id')->nullable()->constrained('post_categories')->nullOnDelete();

            $table->string('label', 150); // tên người dùng tự đặt để nhận diện trong danh sách quản lý
            $table->text('raw_inputs'); // kho nguyên liệu thô (từ khóa/câu hỏi/ý tưởng vụn vặt) — spec §2
            $table->string('business_goal', 500)->nullable();

            $table->longText('rendered_prompt');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('post_category_id');
            $table->index('label'); // tìm lại prompt cũ theo tên người dùng tự đặt (cùng generated_prompts)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_ideation_prompts');
    }
};
