<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// spec/AIVideoStudioTemplate_Technical_Specification.md v1.4 (đọc byteplus.com "how to write AI
// video prompts", rà soát kỹ thuật còn thiếu so với v1.3) — công thức nguồn có thành phần Audio
// Direction (âm thanh môi trường + nhạc nền, KHÁC lời thoại `script_line` đã có) mà v1.0-v1.3 chưa
// có, và khái niệm multi-reference (ảnh/video/audio tham chiếu riêng cho từng shot, ngoài ảnh anchor
// chung ở Project). ALTER riêng vì bảng gốc đã chạy trước đó — cùng pattern 2 migration ALTER trước.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_video_studio_shots', function (Blueprint $table) {
            // Nội dung prompt thật — đưa vào compiled_prompt (BuildShotPromptAction).
            $table->text('audio_direction')->nullable()->after('duration_seconds'); // âm thanh môi trường + nhạc nền

            // Metadata — KHÔNG đưa vào compiled_prompt, cùng nhóm model_tool/qc_notes.
            $table->text('reference_assets')->nullable()->after('model_tool'); // link ảnh/video/audio tham chiếu bổ sung cho shot này
        });
    }

    public function down(): void
    {
        Schema::table('ai_video_studio_shots', function (Blueprint $table) {
            $table->dropColumn(['audio_direction', 'reference_assets']);
        });
    }
};
