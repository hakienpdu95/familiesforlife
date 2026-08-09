<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// spec/AIVideoStudioTemplate_Technical_Specification.md v1.3 (đọc deepreel.com/blog/ai-video-prompts,
// rà soát kỹ thuật còn thiếu so với v1.2) — công thức nguồn "Subject + Style + Camera + Mood +
// Duration" thiếu 2 thành phần Mood/Duration so với schema hiện có (Subject/Camera/Style đã có từ
// v1.0). ALTER riêng vì bảng gốc đã chạy trước đó — cùng pattern migration
// 2026_08_09_190001_add_hedra_technique_fields_to_ai_video_studio_tables.php.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_video_studio_shots', function (Blueprint $table) {
            $table->text('mood')->nullable()->after('style'); // tâm trạng/tông cảm xúc (năng lượng, trầm lắng, chuyên nghiệp...)
            $table->unsignedSmallInteger('duration_seconds')->nullable()->after('mood'); // thời lượng ước tính của shot (giây)
        });
    }

    public function down(): void
    {
        Schema::table('ai_video_studio_shots', function (Blueprint $table) {
            $table->dropColumn(['mood', 'duration_seconds']);
        });
    }
};
