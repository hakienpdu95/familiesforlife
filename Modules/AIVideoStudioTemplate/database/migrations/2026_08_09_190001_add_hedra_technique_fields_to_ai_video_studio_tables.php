<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// spec/AIVideoStudioTemplate_Technical_Specification.md v1.2 (đọc hedra.com/blog/how-to-make-ai-video,
// rà soát kỹ thuật còn thiếu so với v1.0/v1.1) — bổ sung Creative Brief (§2.1) cho Project và
// Model/Tool + QC checklist (§2.2) cho Shot. Migration ALTER riêng vì 2 bảng gốc (create ở
// 2026_08_09_100001/100002) đã chạy trước khi có yêu cầu này — cùng pattern
// Modules/ContentOutlines/database/migrations/2026_08_07_170001_add_article_drafting_fields_to_content_outlines_table.php.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_video_studio_projects', function (Blueprint $table) {
            // Creative Brief (Hedra Workflow Step 1 "Define Creative Brief") — không bắt buộc.
            $table->text('objective')->nullable()->after('description'); // mục tiêu chính (bán hàng, nhận diện thương hiệu...)
            $table->text('target_audience')->nullable()->after('objective'); // đối tượng khán giả mục tiêu
            $table->string('aspect_ratio', 10)->nullable()->after('target_audience'); // 16:9|9:16|1:1|4:5

            // Reference image anchor (Hedra Step 2 "Prepare Source Materials" + magichour.ai —
            // bổ sung cho anchor bằng TEXT `default_subject` đã có từ v1.0).
            $table->text('reference_image_url')->nullable()->after('default_subject');
        });

        Schema::table('ai_video_studio_shots', function (Blueprint $table) {
            // Model Selection Criteria + Workflow Step 4 "test alternative models" — metadata riêng,
            // KHÔNG đưa vào compiled_prompt (không gửi cho AI generator ngoài).
            $table->string('model_tool', 150)->nullable()->after('script_line');

            // Workflow Step 3 "Generate First Draft" — checklist đánh giá output (subject accuracy,
            // motion smoothness, camera angle, style consistency, artifacts). Ghi chú tự do, không
            // ép cấu trúc — chỉ lưu ghi chú của người review, không có trạng thái pass/fail.
            $table->text('qc_notes')->nullable()->after('ai_result');
        });
    }

    public function down(): void
    {
        Schema::table('ai_video_studio_projects', function (Blueprint $table) {
            $table->dropColumn(['objective', 'target_audience', 'aspect_ratio', 'reference_image_url']);
        });

        Schema::table('ai_video_studio_shots', function (Blueprint $table) {
            $table->dropColumn(['model_tool', 'qc_notes']);
        });
    }
};
