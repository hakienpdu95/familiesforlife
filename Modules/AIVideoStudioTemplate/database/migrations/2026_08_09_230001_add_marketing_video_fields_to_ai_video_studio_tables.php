<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// spec/AIVideoStudioTemplate_Technical_Specification.md v1.6 (đọc bài LinkedIn "step-by-step guide
// creating AI marketing videos prompts", rà soát kỹ thuật còn thiếu so với v1.5) — Step 1 của nguồn
// ("Define Objectives") tách riêng video_type (explainer/testimonial/product demo/storytelling) và
// core message (thông điệp/lời hứa cụ thể, VD "Tăng năng suất 40%") — 2 khái niệm module chưa có dù
// đã có objective/target_audience (v1.2). Nguồn cũng liệt kê CTA specifications (button text,
// countdown) là 1 thành phần cấu trúc prompt riêng — module chưa có field nào cho lời kêu gọi hành
// động. ALTER riêng vì các bảng gốc đã chạy trước đó.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_video_studio_projects', function (Blueprint $table) {
            $table->string('video_type', 20)->nullable()->after('target_audience'); // explainer|testimonial|product_demo|storytelling|other
            $table->text('core_message')->nullable()->after('video_type'); // thông điệp/lời hứa cụ thể, VD "Tăng năng suất 40%"
        });

        Schema::table('ai_video_studio_shots', function (Blueprint $table) {
            // Nội dung prompt thật (overlay text/graphic AI cần vẽ) — đưa vào compiled_prompt.
            $table->string('cta_text', 200)->nullable()->after('script_line'); // lời kêu gọi hành động hiển thị trên màn hình
        });
    }

    public function down(): void
    {
        Schema::table('ai_video_studio_projects', function (Blueprint $table) {
            $table->dropColumn(['video_type', 'core_message']);
        });

        Schema::table('ai_video_studio_shots', function (Blueprint $table) {
            $table->dropColumn('cta_text');
        });
    }
};
