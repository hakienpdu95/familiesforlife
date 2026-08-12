<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// spec/AIVideoStudioTemplate_Technical_Specification.md v1.10/v1.11 — đọc lại bài LinkedIn
// "step-by-step guide creating AI marketing videos prompts" (nguồn của v1.6) theo yêu cầu rà soát kỹ
// thuật còn thiếu. 3 gap thật sự còn lại sau khi đối chiếu (mọi thứ khác — định dạng video, phân
// cảnh, script/CTA — đã có từ v1.2-v1.9):
// (1) Mục 3.2 "Image-to-Video" của nguồn tách riêng prompt ẢNH tĩnh (Midjourney/DALL-E tạo keyframe)
//     khỏi prompt MOTION (RunwayML hoạt hình hoá ảnh đó) — module trước đây chỉ gộp 1 prompt duy nhất
//     (BuildShotPromptAction). Thêm `image_prompt`/`motion_prompt` (tự sinh, cùng cơ chế `compiled_prompt`).
// (2) Ví dụ Kling AI của nguồn dùng 2 ảnh nguồn (đội ngũ ăn mừng + bảng số liệu) ghép thành 1 cảnh —
//     ban đầu (v1.10) module thêm 2 field ảnh nguồn (KOL + sản phẩm) + 1 Action tự sinh prompt "ghép
//     2 ảnh thành 1 ảnh mới". PHẢN HỒI NGƯỜI DÙNG (v1.11, cùng ngày): sai bài toán — người dùng đã tự
//     chuẩn bị SẴN ảnh KOL + sản phẩm ở NGOÀI tool trước khi vào bước này, không cần tool sinh prompt
//     "ghép ảnh" hộ. Cái cần chỉ là 1 Ô TEXT NGẮN để tự gõ mô tả ngữ cảnh của ảnh tham chiếu đã có sẵn
//     (không tự sinh, không đi vào compiled_prompt — thuần lưu trữ cho người đọc, cùng nhóm với
//     `objective`). Đổi lại thành `reference_context_prompt` — xoá 2 field ảnh nguồn.
// (3) Ví dụ Synthesia của nguồn viết kịch bản theo mốc thời gian (0-5s/5-15s/kết) — module chỉ có
//     1 số `duration_seconds`, không có breakdown theo mốc. Thêm `timeline_breakdown` (text tự do,
//     cùng tầng với `constraints`/`audio_direction`).
// File này SỬA TRONG NGÀY (chưa commit, chưa chạy ở môi trường nào khác) nên gộp thẳng thay đổi (2)
// vào đây thay vì thêm 1 migration ALTER mới chỉ để đảo ngược — tránh churn "thêm cột rồi xoá ngay"
// trong lịch sử. Quy tắc "không sửa migration đã chạy" (README) áp dụng cho migration đã RELEASE.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_video_studio_projects', function (Blueprint $table) {
            $table->text('reference_context_prompt')->nullable()->after('reference_image_url'); // v1.11 — mô tả ngắn tự gõ, KHÔNG tự sinh/vào compiled_prompt
        });

        Schema::table('ai_video_studio_shots', function (Blueprint $table) {
            $table->text('timeline_breakdown')->nullable()->after('duration_seconds'); // v1.10 — kịch bản theo mốc thời gian (0-5s/5-15s/...)
            $table->text('image_prompt')->nullable()->after('compiled_prompt'); // v1.10 — prompt ảnh tĩnh (keyframe) tách riêng
            $table->text('motion_prompt')->nullable()->after('image_prompt'); // v1.10 — prompt hoạt hình hoá ảnh tĩnh tách riêng
        });
    }

    public function down(): void
    {
        Schema::table('ai_video_studio_projects', function (Blueprint $table) {
            $table->dropColumn(['reference_context_prompt']);
        });

        Schema::table('ai_video_studio_shots', function (Blueprint $table) {
            $table->dropColumn(['timeline_breakdown', 'image_prompt', 'motion_prompt']);
        });
    }
};
