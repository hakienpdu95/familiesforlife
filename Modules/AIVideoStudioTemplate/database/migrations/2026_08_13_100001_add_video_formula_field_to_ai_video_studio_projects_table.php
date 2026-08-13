<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// spec/AIVideoStudioTemplate_Technical_Specification.md v1.16 — đọc
// tulsainternetmarketingservice.com/blog/video-marketing-formulas (3 công thức kịch bản: Problem-
// Solution-CTA, Before-After-Bridge, Hook-Value-CTA) + swarmify.com/blog/video-marketing-strategy,
// rà soát kỹ thuật còn thiếu so với v1.15. swarmify.com phần lớn NGOÀI phạm vi module (video SEO,
// CDN/hiệu suất web, đo hiệu suất phân phối sau đăng — trùng quyết định §0/§10 đã chốt) hoặc trùng
// field đã có (mục tiêu kinh doanh ~ objective, nghiên cứu khán giả ~ target_audience); "phân loại
// theo giai đoạn funnel" (awareness/consideration/conversion, tỷ lệ 60/25/15) KHÔNG áp dụng vì trùng
// lấn `video_type` đã có (product_demo/testimonial/offer_promo đã ngầm gắn với từng giai đoạn) mà
// không thêm hướng dẫn cụ thể nào khác cho việc viết prompt.
//
// Gap thật sự từ tulsainternetmarketingservice.com: 3 công thức kịch bản (narrative arc) là 1 TRỤC
// KHÁC với `video_type` — video_type nói VIDEO THUỘC LOẠI GÌ (nội dung), công thức nói VIDEO KỂ
// CHUYỆN THEO TRÌNH TỰ NÀO (cấu trúc). VD 1 video product_demo vẫn có thể dùng cấu trúc PSA hoặc
// Hook-Value-CTA tuỳ ý đồ — không trùng lặp. Thêm `video_formula` (nullable, cấp Project — cùng
// nhóm "bối cảnh chiến dịch" với `video_type`, RENDER vào compiled_prompt của mọi Shot để AI hiểu
// vai trò/mạch truyện của shot đang tạo, xem BuildShotPromptAction::buildCampaignContextLines()).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_video_studio_projects', function (Blueprint $table) {
            $table->string('video_formula', 20)->nullable()->after('video_type'); // psa|bab|hook_value_cta
        });
    }

    public function down(): void
    {
        Schema::table('ai_video_studio_projects', function (Blueprint $table) {
            $table->dropColumn('video_formula');
        });
    }
};
