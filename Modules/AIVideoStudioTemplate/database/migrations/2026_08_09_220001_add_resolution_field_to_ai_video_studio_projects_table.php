<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// spec/AIVideoStudioTemplate_Technical_Specification.md v1.5 (đọc pyxeljam.com "10 best practices
// for writing effective AI video prompts", rà soát kỹ thuật còn thiếu so với v1.4) — thực hành #7
// của nguồn "Set Video Length, Quality, and File Format Requirements": video length đã có qua
// `duration_seconds` (v1.3, cộng dồn ở CompileProjectDirectorPromptAction), nhưng CHẤT LƯỢNG/độ
// phân giải xuất bản (1080p/4K...) chưa có field nào — bổ sung `resolution` cạnh `aspect_ratio`
// (v1.2) trong khối Creative Brief. ALTER riêng vì bảng gốc đã chạy trước đó.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_video_studio_projects', function (Blueprint $table) {
            $table->string('resolution', 10)->nullable()->after('aspect_ratio'); // 720p|1080p|2K|4K
        });
    }

    public function down(): void
    {
        Schema::table('ai_video_studio_projects', function (Blueprint $table) {
            $table->dropColumn('resolution');
        });
    }
};
