<?php

namespace Modules\ContentOutlines\Features\ArticleDrafting\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentFoundation\Models\CategoryContentFoundation;
use Modules\ContentOutlines\Features\Concerns\BuildsSharedPromptBlocks;
use Modules\ContentOutlines\Features\OutlineGeneration\Data\ContentOutlineInputData;

/**
 * spec/ContentOutlines_Technical_Specification.md §4.17 (v1.14) — "Bước 2": sinh 1 prompt ĐỘC LẬP
 * (KHÔNG gọi AI Provider — cùng nguyên tắc §0 mục 1 của module) để biên tập viên copy sang 1 LƯỢT
 * CHAT MỚI, dùng nhúng SẴN outline THẬT (`$approvedOutline`, biên tập viên tự dán vào sau khi nhận
 * outline từ AI ngoài chạy `generated_prompt`) để viết bài hoàn chỉnh — thay cho template tĩnh có
 * placeholder tay ở v1.13 (đã XOÁ, xem docblock `BuildContentOutlinePromptAction::buildBottomBrief()`
 * §4.16).
 *
 * Tái dùng NGUYÊN `ContentOutlineInputData` (không tạo DTO mới) — mọi field cần
 * (`target_keyword`/`desired_word_count`/`tone_style`/`language`/`content_goal`) đã có sẵn ở đó,
 * hydrate từ `ContentOutline` model đã lưu qua `ContentOutlineInputData::from($contentOutline)`
 * (Spatie Laravel Data tự map theo tên property/cột trùng nhau) ở Controller — Action này KHÔNG
 * tự query, cùng nguyên tắc `BuildContentOutlinePromptAction`.
 *
 * §4.19 (v1.16, tham khảo junia.ai/blog/ai-blog-writing-prompts) — nguồn có 1 "Introduction
 * Rewriting Prompt" riêng, cấm cụ thể cụm mở đầu sáo rỗng kiểu "in today's fast-paced world" (1
 * cliché rất phổ biến của văn bản do AI viết) + yêu cầu nêu vấn đề trong "first two sentences".
 * Module trước đây (v1.15) chỉ nói chung "không mở đầu vòng vo" — CHƯA cấm cụ thể các cụm sáo rỗng
 * hay ràng buộc rõ VỊ TRÍ (1-2 câu đầu). Đã bổ sung: bullet "Đoạn mở bài" nêu rõ 1-2 câu đầu +
 * liệt kê cụm sáo rỗng tiếng Việt tương đương bị cấm. Đồng thời mở rộng "Không bịa số liệu" (đã có
 * §4.11) thêm "case study" (nguồn — Examples Enhancement Prompt — cấm riêng "fake case studies").
 * KHÔNG áp dụng ở Action này: SEO Optimization/Readability Editing/Final Editing Prompt của nguồn
 * — cả 3 đều REVIEW 1 bài ĐÃ VIẾT XONG (khác Action này, sinh prompt VIẾT MỚI từ outline) — thuộc
 * 1 Feature "Bước 3" khác nếu triển khai, xem quyết định phạm vi đã hỏi người dùng ở changelog v1.16.
 *
 * §4.25 (v1.22, đối chiếu spec/giadinh.md — Moz Whiteboard Friday "7 Tips for Writing Great
 * Content with ChatGPT or Gemini", Chima Mmeje) — thêm guardrail chống văn phong "lộ AI" cụ thể
 * hơn cliché-mở-bài đã có (§4.19): cấm lạm dụng em-dash, từ chuyển ý sáo mòn lặp lại, chuỗi câu
 * ngắn cùng cấu trúc liên tiếp. Rà lại ở BuildArticleReviewPromptAction (§4.20/§4.25).
 *
 * §4.26 (v1.23) — mở rộng THÊM cùng bullet §4.25: giới hạn câu ≤20 từ (tầng CÂU, khác "60-100
 * từ/đoạn" đã có ở §4.6 là tầng ĐOẠN) + cấm thuật ngữ mơ hồ không kèm ngữ cảnh cụ thể.
 */
class BuildArticleDraftPromptAction
{
    use AsAction;
    use BuildsSharedPromptBlocks;

    /**
     * §4.1-style soft warning — ngưỡng RIÊNG, cao hơn `BuildContentOutlinePromptAction::
     * WORD_COUNT_WARNING_THRESHOLD` (6.000) vì prompt này CHỦ ĐỘNG nhúng nguyên outline đã duyệt
     * (thường vài trăm-vài nghìn từ) + hướng dẫn văn phong — dài hơn là BÌNH THƯỜNG, không phải
     * dấu hiệu phình do lỗi cấu hình như ở prompt outline.
     */
    public const WORD_COUNT_WARNING_THRESHOLD = 10000;

    public function handle(ContentOutlineInputData $input, ?CategoryContentFoundation $foundation, string $approvedOutline): string
    {
        $wordCountNote = $input->desired_word_count
            ? "khoảng {$input->desired_word_count} từ"
            : 'độ dài hợp lý theo outline (dùng ước lượng số từ mỗi phần trong outline nếu có, nếu không thì tự đề xuất theo độ phức tạp chủ đề)';

        $tone = $input->tone_style ?: $foundation?->style_sample;
        $toneNote = $tone ? "\n- **Giọng văn:** {$tone}." : '';

        $goalNote = $input->content_goal ? "\n- **Mục tiêu bài viết:** {$input->content_goal}." : '';

        $languageNote = $input->language === 'en' ? 'English' : 'Tiếng Việt';

        // §4.18 (v1.15) — nếu có cta_url, đoạn kết bài PHẢI mời truy cập ĐÚNG URL đó (câu chuyển
        // tiếp tự nhiên, không dán trơ URL) — nếu không có, giữ chỉ dẫn CTA chung như v1.14.
        $ctaNote = $input->cta_url
            ? "kết thúc bằng 1 đoạn chuyển tiếp TỰ NHIÊN mời độc giả truy cập {$input->cta_url} (không dán trơ URL, nêu rõ lợi ích khi truy cập)"
            : 'kết thúc bằng 1 CTA/bước tiếp theo phù hợp mục tiêu bài viết (đã gợi ý ở outline, nếu có)';

        $blocks = [];

        $blocks[] = <<<MARKDOWN
Bạn là 1 người viết/biên tập viên giàu kinh nghiệm về chủ đề "{$input->target_keyword}", có khả năng viết bài blog/SEO chất lượng cao, tự nhiên như người viết chuyên nghiệp.

## Outline đã duyệt

{$approvedOutline}

## Yêu cầu bài viết

- Dùng ĐÚNG cấu trúc heading (H1/H2/H3) và các điểm/bullet trong outline ở trên — KHÔNG tự thêm/bỏ heading nào ngoài outline này; triển khai ĐẦY ĐỦ mỗi bullet thành đoạn văn liền mạch, không chỉ liệt lại nguyên văn bullet.
- **Độ dài:** {$wordCountNote}.
- **Ngôn ngữ:** {$languageNote}.{$toneNote}{$goalNote}
- **Đoạn mở bài** nêu ĐÚNG vấn đề/nỗi đau chính của đối tượng đọc ngay trong 1-2 câu đầu, rồi nêu rõ đọc xong bài sẽ biết/làm được gì — KHÔNG mở đầu bằng cụm sáo rỗng kiểu "trong thế giới hiện đại ngày nay"/"trong bối cảnh phát triển như hiện nay"/"chúng ta đều biết rằng" hay tương đương, KHÔNG mở đầu vòng vo/giới thiệu chung chung.
- Câu chủ động, ngắn gọn, tránh từ ngữ sáo rỗng/dư thừa (fluff); mỗi phần (H2) có ít nhất 1 điểm hành động cụ thể (actionable takeaway) độc giả áp dụng được ngay.
- **Tránh dấu hiệu văn phong "lộ AI" phổ biến:** không lạm dụng dấu gạch ngang em-dash "—" nối 2 vế câu (thỉnh thoảng 1 câu dùng được, KHÔNG lặp lại liên tục ở nhiều câu); không mở nhiều đoạn liên tiếp bằng cùng 1 từ chuyển ý sáo mòn ("Hơn nữa"/"Bên cạnh đó"/"Không chỉ vậy"/"Tóm lại"...); tránh chuỗi câu ngắn liên tiếp lặp lại cùng 1 cấu trúc ngữ pháp (chủ ngữ-động từ-tân ngữ) — xen kẽ độ dài câu tự nhiên như người viết thật; mỗi câu KHÔNG quá khoảng 20 từ, câu dài nhiều mệnh đề cần tách thành 2 câu ngắn hơn; không dùng cụm từ chung chung/mơ hồ (VD "giải pháp hiệu quả", "chiến lược phù hợp") mà không nói rõ áp dụng cho trường hợp/đối tượng cụ thể nào.
- Ưu tiên đoạn văn NGẮN (2-4 câu/đoạn), dùng bullet cho danh sách, in đậm (bold) cụm từ/khái niệm quan trọng — để bài dễ đọc lướt (scannable), không phải 1 khối văn bản dài liên tục.
- Nếu 1 đoạn dùng danh sách/bullet, có 1 câu dẫn nhập nêu ngữ cảnh trước đó, không thả bullet trơ trọi ngay sau heading.
- Nếu không chắc 1 số liệu/thống kê/case study cụ thể, ghi "[cần biên tập viên xác minh]" thay vì tự bịa — KHÔNG tạo số liệu/case study/dẫn chứng không kiểm chứng được.
- Không nhồi từ khoá (keyword stuffing) — dùng từ khoá mục tiêu/phụ tự nhiên xuyên suốt bài.
- **Đoạn kết bài** tóm lại 2-3 ý chính của bài rồi {$ctaNote}.
MARKDOWN;

        $familyValuesBlock = $this->buildFamilyValuesBlock($foundation);
        if ($familyValuesBlock !== '') {
            $blocks[] = $familyValuesBlock;
        }

        $blocks[] = <<<'MARKDOWN'
**Lưu ý EEAT:** nếu chủ đề cần độ chính xác cao (y tế/pháp lý/tài chính/an toàn trẻ em...), đề nghị chuyên gia/nguồn uy tín rà soát trước khi publish.

## Định dạng output

Trả lời ĐÚNG 1 bài viết hoàn chỉnh dạng Markdown (1 H1 + các H2/H3 theo outline ở trên), không thêm lời dẫn/giải thích trước hoặc sau bài viết.
MARKDOWN;

        return implode("\n\n", $blocks);
    }
}
