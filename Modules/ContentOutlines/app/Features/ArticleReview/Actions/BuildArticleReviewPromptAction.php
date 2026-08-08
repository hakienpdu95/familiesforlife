<?php

namespace Modules\ContentOutlines\Features\ArticleReview\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentFoundation\Models\CategoryContentFoundation;
use Modules\ContentOutlines\Features\Concerns\BuildsSharedPromptBlocks;
use Modules\ContentOutlines\Features\OutlineGeneration\Data\ContentOutlineInputData;

/**
 * spec/ContentOutlines_Technical_Specification.md §4.20 (v1.16, tham khảo
 * junia.ai/blog/ai-blog-writing-prompts) — "Bước 3": sinh 1 prompt SOÁT LỖI/SỬA cho 1 bài viết
 * ĐÃ VIẾT XONG (`$draftedArticle`, biên tập viên dán vào sau khi chạy `article_draft_prompt` ở AI
 * ngoài — hoặc viết tay, KHÔNG bắt buộc phải qua Bước 2). KHÔNG gọi AI Provider — cùng nguyên tắc
 * §0 mục 1. Gộp 3 loại prompt REVIEW của nguồn thành 1 prompt duy nhất (giữ đúng mô hình "sinh 1
 * prompt, chạy 1 lần" của module — KHÔNG tách 3 lượt gọi AI riêng như nguồn gợi ý): (1) SEO
 * Optimization — khớp tiêu đề/ý định, heading chưa rõ, từ khoá gượng, gợi ý FAQ; (2) Readability
 * Editing — câu/đoạn dài, filler, chuyển đoạn yếu, KHÔNG đơn giản hoá quá mức làm mất chính xác kỹ
 * thuật; (3) Final Editing — đoạn chưa rõ/lặp ý/thiếu dẫn chứng/thiếu ví dụ/giọng máy móc. Nguồn
 * nhấn mạnh "request precise edits, without full rewrites" — output YÊU CẦU liệt kê vấn đề + đề
 * xuất SỬA CHÍNH XÁC từng đoạn, KHÔNG viết lại toàn bài (khác `BuildArticleDraftPromptAction` —
 * Action đó sinh bài MỚI từ outline, Action này SỬA bài đã có).
 *
 * Tái dùng NGUYÊN `ContentOutlineInputData` (không tạo DTO mới), cùng kỹ thuật
 * `ContentOutlineInputData::from($contentOutline)` đã dùng ở `ArticleDrafting` (§4.17).
 *
 * §4.25 (v1.22, đối chiếu spec/giadinh.md — Moz Whiteboard Friday, Chima Mmeje) — mở rộng mục
 * "Rà soát cuối" chỉ rõ 3 dấu hiệu văn phong "lộ AI" cụ thể (em-dash lạm dụng/từ chuyển ý sáo
 * mòn lặp lại/chuỗi câu ngắn cùng cấu trúc) thay vì chỉ nói chung "robotic" như trước — cùng
 * guardrail đã thêm ở BuildArticleDraftPromptAction.
 *
 * §4.26 (v1.23) — mở rộng THÊM mục "2. Đánh giá độ dễ đọc": ngưỡng cụ thể ~20 từ/câu (thay vì
 * "quá dài" mơ hồ) + rà thêm thuật ngữ chung chung thiếu ngữ cảnh cụ thể.
 */
class BuildArticleReviewPromptAction
{
    use AsAction;
    use BuildsSharedPromptBlocks;

    /**
     * Ngưỡng RIÊNG, cao hơn cả `BuildArticleDraftPromptAction::WORD_COUNT_WARNING_THRESHOLD`
     * (10.000) vì prompt này nhúng NGUYÊN 1 bài viết hoàn chỉnh (thường 1.000-3.000+ từ) — dài hơn
     * là BÌNH THƯỜNG.
     */
    public const WORD_COUNT_WARNING_THRESHOLD = 12000;

    public function handle(ContentOutlineInputData $input, ?CategoryContentFoundation $foundation, string $draftedArticle): string
    {
        $intentNote = $input->search_intent
            ? "Ý định tìm kiếm đã xác định trước: {$input->search_intent}."
            : 'Ý định tìm kiếm chưa xác định trước — bạn tự đánh giá bài viết có phục vụ đúng 1 ý định rõ ràng không.';

        $blocks = [];

        $blocks[] = <<<MARKDOWN
Bạn là 1 biên tập viên/SEO editor giàu kinh nghiệm về chủ đề "{$input->target_keyword}". Nhiệm vụ của bạn là RÀ SOÁT bài viết dưới đây và ĐỀ XUẤT SỬA — KHÔNG viết lại toàn bộ bài trừ khi 1 đoạn cụ thể có quá nhiều lỗi cần viết lại hoàn toàn (nếu vậy, nêu rõ đoạn nào + vì sao).

## Bài viết cần rà soát

{$draftedArticle}

## Yêu cầu rà soát

**1. Đánh giá SEO** — {$intentNote} Tiêu đề có khớp đúng ý định tìm kiếm/từ khoá mục tiêu "{$input->target_keyword}" không? Heading (H2/H3) nào chưa rõ nghĩa, cần viết lại? Có đoạn nào dùng từ khoá gượng/nhồi (keyword stuffing) không? Gợi ý 2-3 câu hỏi FAQ dựa trên câu hỏi độc giả THẬT có thể có về chủ đề này (nếu bài chưa có khối FAQ).

**2. Đánh giá độ dễ đọc (readability)** — câu nào dài quá khoảng 20 từ nên tách thành 2 câu ngắn hơn? Đoạn nào có từ ngữ dư thừa (filler) có thể bỏ mà KHÔNG mất nghĩa? Chuyển đoạn (transition) nào lỏng lẻo, cần câu nối tốt hơn? Có cụm từ chung chung/mơ hồ nào (VD "giải pháp hiệu quả", "chiến lược phù hợp") thiếu ngữ cảnh cụ thể (áp dụng cho ai/trường hợp nào) không? LƯU Ý: không đề xuất đơn giản hoá quá mức làm mất chính xác kỹ thuật của nội dung.

**3. Rà soát cuối** — đoạn nào đọc chưa rõ nghĩa; đoạn nào lặp ý với đoạn khác; câu chuyển tiếp yếu ở đâu; khẳng định/số liệu nào chưa có dẫn chứng rõ ràng; đoạn nào thiếu ví dụ cụ thể; đoạn nào đọc "robotic"/máy móc, không tự nhiên như người viết thật — cụ thể tìm 3 dấu hiệu "lộ AI" phổ biến: (a) lạm dụng dấu gạch ngang em-dash "—" nối 2 vế câu ở nhiều câu liên tiếp; (b) nhiều đoạn liên tiếp mở đầu bằng cùng 1 từ chuyển ý sáo mòn ("Hơn nữa"/"Bên cạnh đó"/"Không chỉ vậy"/"Tóm lại"...); (c) chuỗi câu ngắn liên tiếp lặp lại cùng 1 cấu trúc ngữ pháp.

**4. Đề xuất sửa** — với MỖI vấn đề tìm được ở Bước 1-3, đề xuất ĐOẠN VĂN THAY THẾ CỤ THỂ (không chỉ nói "nên sửa lại") để biên tập viên copy-paste trực tiếp.
MARKDOWN;

        $familyValuesBlock = $this->buildFamilyValuesBlock($foundation);
        if ($familyValuesBlock !== '') {
            $blocks[] = $familyValuesBlock."\n\nKiểm tra bài viết ở trên có đi ngược lại các giá trị này không — nếu có, nêu rõ đoạn nào cần sửa.";
        }

        $blocks[] = <<<'MARKDOWN'
## Định dạng output

Trả lời theo ĐÚNG 4 mục "Đánh giá SEO"/"Đánh giá độ dễ đọc"/"Rà soát cuối"/"Đề xuất sửa" ở trên — KHÔNG dán lại toàn văn bài viết trong câu trả lời (chỉ trích đoạn CẦN sửa khi đề xuất thay thế).
MARKDOWN;

        return implode("\n\n", $blocks);
    }
}
