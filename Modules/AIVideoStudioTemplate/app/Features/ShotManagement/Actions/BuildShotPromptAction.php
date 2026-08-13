<?php

namespace Modules\AIVideoStudioTemplate\Features\ShotManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioProject;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioShot;

/**
 * spec/AIVideoStudioTemplate_Technical_Specification.md §3.1 — lõi của module: ghép field của Shot
 * (+ bối cảnh của Project, v1.7) thành 1 prompt hoàn chỉnh để người dùng copy sang tool AI ngoài.
 * Chỉ in ra field NÀO CÓ giá trị, bỏ qua field rỗng (không ép điền đủ field cho mọi shot).
 *
 * Thứ tự các lớp (v1.7 — theo hướng dẫn "thông tin quan trọng nhất nằm ở 20-30 từ đầu" của
 * deepreel.com, và ví dụ dẫn đầu bằng định dạng của byteplus "UGC-style 9:16 video, ..."):
 *   1. Câu dẫn vai trò  →  2. ĐỊNH DẠNG (ngắn, kỹ thuật)  →  3. Nội dung shot (phần nặng nhất)
 *   →  4. BỐI CẢNH CHIẾN DỊCH (đặt CUỐI, đánh dấu rõ "chỉ tham khảo" để AI không cố nhồi cả chiến
 *      dịch vào 1 shot — đúng sai lầm "overloading" mà hedra/deepreel/byteplus đều cảnh báo).
 *
 * Lịch sử bổ sung field: v1.3 Mood + Duration (deepreel), v1.4 Audio (byteplus), v1.6 CTA (LinkedIn),
 * v1.10 Timeline (bài LinkedIn, ví dụ Synthesia "kịch bản theo timeline 0-5s/5-15s/kết"), v1.16
 * `video_formula` (tulsainternetmarketingservice.com — công thức kịch bản PSA/BAB/Hook-Value-CTA,
 * vào BỐI CẢNH CHIẾN DỊCH giống `video_type`, không phải field riêng của Shot).
 * `model_tool`/`reference_assets`/`qc_notes` là metadata nội bộ — KHÔNG BAO GIỜ vào prompt.
 *
 * **Ghi chú prompt-injection (CLAUDE.md)**: quy ước bọc `<<<DELIMITER>>>` áp dụng cho text không tin
 * cậy đi vào prompt do CHÍNH APP gửi qua `app/Services/AI/`. Ở đây app không gọi AI — chuỗi này chỉ
 * được người dùng copy tay sang tool ngoài, và mọi giá trị đều do biên tập viên nội bộ tự gõ. Rủi ro
 * thực tế là **vỡ cấu trúc do giá trị nhiều dòng**, xử lý bằng `indentContinuationLines()` bên dưới.
 * Nếu sau này module tự gọi AI Provider thì PHẢI bọc delimiter theo đúng convention của CLAUDE.md.
 *
 * **v1.10 → v1.12 — `image_prompt`/`motion_prompt` (bài LinkedIn "step-by-step guide creating AI
 * marketing videos prompts", mục 3.2 "Image-to-Video")**: v1.10 từng có `buildImagePrompt()`/
 * `buildMotionPrompt()` TỰ SINH 2 prompt này từ field khác (Subject/Camera/Style/Action/...). PHẢN HỒI
 * NGƯỜI DÙNG (v1.12): không gõ được trực tiếp vào 2 ô này — đúng như thiết kế (readonly, tự sinh),
 * nhưng người dùng muốn ngược lại: **2 field NHẬP TAY tự do, KHÔNG tự sinh từ đâu cả** (khác hẳn
 * `compiled_prompt` ở `handle()` — field đó vẫn tự sinh, không đổi). Đã xoá `buildImagePrompt()`/
 * `buildMotionPrompt()`; `image_prompt`/`motion_prompt` giờ là field metadata nhập tay như
 * `model_tool`/`qc_notes` (xem `CreateShotAction`/`UpdateShotAction`), vẫn xuất hiện trong tài liệu
 * export nếu có điền (`CompileProjectDirectorPromptAction`), nhưng KHÔNG BAO GIỜ vào `compiled_prompt`.
 */
class BuildShotPromptAction
{
    use AsAction;

    private const INTRO = 'Bạn là chuyên gia video chuyên nghiệp, hãy tạo 1 cảnh quay (shot) chuyên nghiệp, chuyển cảnh mượt, theo đúng mô tả sau:';

    private const CAMPAIGN_HEADING = '--- BỐI CẢNH CHIẾN DỊCH (tham khảo để giữ đúng tông — KHÔNG cần thể hiện toàn bộ trong shot này) ---';

    /**
     * @param  AiVideoStudioProject|null  $project  Truyền sẵn khi caller đã có (tránh query thừa);
     *                                              bỏ trống thì tự lấy `$shot->project` (trả null an
     *                                              toàn với model chưa lưu — belongsTo khoá ngoại
     *                                              null KHÔNG chạm DB).
     * @return string Chuỗi rỗng nếu shot chưa điền field nội dung nào — caller lưu thành `null` để
     *                tài liệu xuất hiện placeholder "chưa có prompt" (§3.5) thay vì 1 câu dẫn trống.
     */
    public function handle(AiVideoStudioShot $shot, ?AiVideoStudioProject $project = null): string
    {
        $project ??= $shot->project;

        $shotLines = $this->buildShotLines($shot);

        // Shot rỗng → KHÔNG sinh prompt. Trước v1.7 hàm này luôn trả về câu dẫn, nên `compiled_prompt`
        // không bao giờ rỗng với shot tạo qua app: người dùng bấm "Thêm shot" rồi Copy ngay sẽ dán
        // sang AI một câu "hãy tạo cảnh quay theo mô tả sau:" hoàn toàn không có mô tả nào.
        if ($shotLines === []) {
            return '';
        }

        $lines = [self::INTRO, ''];

        if ($formatLine = $this->buildFormatLine($project)) {
            $lines[] = $formatLine;
            $lines[] = '';
        }

        $lines = [...$lines, ...$shotLines];

        if ($campaignLines = $this->buildCampaignContextLines($project)) {
            $lines[] = '';
            $lines = [...$lines, ...$campaignLines];
        }

        return trim(implode("\n", $lines));
    }

    /** @return string[] Các dòng mô tả shot — rỗng nghĩa là shot chưa có nội dung gì. */
    private function buildShotLines(AiVideoStudioShot $shot): array
    {
        $lines = [];

        $this->appendField($lines, 'CHỦ THỂ (Subject)', $shot->subject);
        $this->appendField($lines, 'HÀNH ĐỘNG (Action)', $shot->action);
        $this->appendField($lines, 'BỐI CẢNH (Environment)', $shot->environment);
        $this->appendField($lines, 'GÓC MÁY (Camera)', $shot->camera);
        $this->appendField($lines, 'PHONG CÁCH (Style)', $shot->style);
        $this->appendField($lines, 'TÂM TRẠNG (Mood)', $shot->mood);

        if (filled($shot->duration_seconds)) {
            $lines[] = "THỜI LƯỢNG (Duration): {$shot->duration_seconds} giây";
        }

        // v1.10 (bài LinkedIn, ví dụ Synthesia "kịch bản theo timeline") — đặt ngay sau Duration vì
        // đây là breakdown của chính con số đó, trước khi tới Audio/Script.
        $this->appendField($lines, 'TIMELINE NỘI DUNG (Content Timeline)', $shot->timeline_breakdown);

        $this->appendField($lines, 'ÂM THANH (Audio/Soundscape)', $shot->audio_direction);

        if (filled($shot->script_line)) {
            $lines[] = 'LỜI THOẠI: "'.$this->indentContinuationLines($shot->script_line).'"';
        }

        $this->appendField($lines, 'CALL-TO-ACTION (CTA)', $shot->cta_text);
        $this->appendField($lines, 'RÀNG BUỘC (Constraints)', $shot->constraints);

        return $lines;
    }

    /**
     * Định dạng kỹ thuật của Project (v1.7). Trước đó `aspect_ratio`/`resolution` được nhập ở
     * Creative Brief nhưng KHÔNG bao giờ tới prompt — tool AI ngoài mặc định 16:9 dù project khai
     * 9:16, phải sửa tay hoặc dựng lại. Giữ thật ngắn vì đứng trước phần nội dung chính.
     */
    private function buildFormatLine(?AiVideoStudioProject $project): ?string
    {
        if ($project === null) {
            return null;
        }

        $parts = [];
        if (filled($project->aspect_ratio)) {
            $parts[] = "khung hình {$project->aspect_ratio}";
        }
        if (filled($project->resolution)) {
            $parts[] = "độ phân giải {$project->resolution}";
        }

        return $parts === [] ? null : 'ĐỊNH DẠNG (Format): '.implode(', ', $parts);
    }

    /**
     * Bối cảnh chiến dịch (v1.7) — `video_type`/`target_audience`/`core_message` ảnh hưởng tới cách
     * dàn cảnh và tông của shot nên đưa vào prompt; `objective` (mục tiêu kinh doanh) thì KHÔNG, vì
     * nó không đổi thứ gì xuất hiện trên khung hình — chỗ của nó là khối Creative Brief dành cho
     * người đọc trong tài liệu xuất (§3.5).
     *
     * @return string[]
     */
    private function buildCampaignContextLines(?AiVideoStudioProject $project): array
    {
        if ($project === null) {
            return [];
        }

        $items = [];
        if (filled($project->video_type)) {
            $items[] = '- Loại video: '.$project->videoTypeLabel();
        }
        // v1.16 (tulsainternetmarketingservice.com) — công thức kịch bản là trục KHÁC video_type
        // (cấu trúc kể chuyện, không phải loại nội dung); AI cần biết cả 2 để dàn cảnh đúng vai trò
        // shot đang tạo (VD shot này là "Problem" hay "Solution" trong công thức PSA).
        if (filled($project->video_formula)) {
            $items[] = '- Công thức kịch bản: '.$project->videoFormulaLabel();
        }
        if (filled($project->target_audience)) {
            $items[] = '- Khán giả mục tiêu: '.$this->indentContinuationLines($project->target_audience);
        }
        if (filled($project->core_message)) {
            $items[] = '- Thông điệp xuyên suốt: '.$this->indentContinuationLines($project->core_message);
        }

        return $items === [] ? [] : [self::CAMPAIGN_HEADING, ...$items];
    }

    /** @param  string[]  $lines */
    private function appendField(array &$lines, string $label, ?string $value): void
    {
        if (filled($value)) {
            $lines[] = "{$label}: ".$this->indentContinuationLines($value);
        }
    }

    /**
     * Giữ cấu trúc `NHÃN: giá trị` không bị vỡ khi giá trị có nhiều dòng (v1.7).
     *
     * Prompt là các dòng `NHÃN: giá trị` nối bằng "\n", nên 1 giá trị dán từ Word/Docs xuống dòng
     * giữa chừng sẽ khiến dòng sau trông y hệt 1 field mới — cả AI lẫn người đọc đều không biết field
     * trước kết thúc ở đâu. Thụt lề dòng nối làm ranh giới rõ lại mà KHÔNG sửa nội dung người dùng
     * (khác với việc gộp hết về 1 dòng — sẽ phá lời thoại nhiều câu).
     */
    private function indentContinuationLines(string $value): string
    {
        $normalized = preg_replace("/\r\n?/", "\n", trim($value));

        return str_replace("\n", "\n    ", $normalized);
    }
}
