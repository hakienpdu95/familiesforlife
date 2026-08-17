<?php

namespace Modules\AIVideoStudioTemplate\Features\FormulaAdvisor\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\AIVideoStudioTemplate\Features\ProjectManagement\Actions\CompileProjectDirectorPromptAction;

/**
 * spec/AIVideoStudioTemplate_Technical_Specification.md §13 (v1.25) — thay cho
 * `FormulaDecisionTree` (v1.23/v1.24, ĐÃ XOÁ ở v1.25 theo xác nhận người dùng). Quyết định kiến trúc
 * đã ĐẢO NGƯỢC so với v1.23/v1.24: AI ngoài (ChatGPT/Claude, dán tay — module vẫn KHÔNG gọi AI
 * Provider, đúng §0) giờ tự chạy cây quyết định 5 câu hỏi VÀ viết kịch bản trong 1 lượt, thay vì PHP
 * tính hộ công thức rồi chỉ đưa gợi ý. Action này CHỈ render text — không có logic quyết định nào.
 *
 * Lý do đảo ngược (người dùng xác nhận trực tiếp, ghi lại để không lặp lại tranh luận): cách gọi AI
 * ở đây vẫn là copy-paste thủ công — con người đọc lại trước khi dùng, đúng workflow "KHÔNG gọi AI
 * Provider" mà cả module đã theo từ v1.0 (§0) cho ảnh/video — nên rủi ro thấp hơn kịch bản app tự
 * động gọi API rồi tin thẳng kết quả (rủi ro pháp lý nêu ở §13.0 bản v1.23/v1.24 chủ yếu nhắm vào
 * trường hợp tự động, không áp dụng nguyên vẹn cho copy-paste có người soát lại).
 *
 * Phạm vi sản phẩm KHÔNG còn giới hạn 36 sản phẩm Mẹ & Bé — `topic`/`description` tự do cho MỌI
 * ngành hàng. Khối ranh giới pháp lý Mẹ & Bé (Nghị định 100/2014/NĐ-CP...) chỉ chèn vào prompt khi
 * `$isMotherBaby = true` — bản nháp gốc người dùng cung cấp gắn CỨNG khối này dù đã nói mở rộng phạm
 * vi ra "đồ gia dụng, thời trang", tự mâu thuẫn nên KHÔNG copy nguyên văn.
 *
 * Không bọc `<<<DELIMITER>>>` cho `topic`/`description` — cùng ghi chú prompt-injection đã có ở
 * `BuildShotPromptAction`: module này không gọi AI, chuỗi chỉ copy tay sang tool ngoài, rủi ro thực
 * tế là VỠ CẤU TRÚC (giá trị nhiều dòng phá dòng `NHÃN: giá trị`) chứ không phải injection — xử lý
 * bằng `indentContinuationLines()` (đúng kỹ thuật `BuildShotPromptAction` §3.1 đã dùng).
 *
 * spec/AIVideoStudioTemplate_Technical_Specification.md §13.6 (v1.28 — người dùng đưa nguyên văn 1
 * đoạn tài liệu "Bản chất Nội dung Giá trị + Quy trình 5 bước sản xuất video", hỏi có nên chèn vào
 * Master Prompt không). Rà theo đúng kỷ luật đã áp dụng xuyên suốt module (chỉ áp dụng phần THẬT SỰ
 * khác biệt, từ chối có lý do — không mở lại): tài liệu gồm 4 phần, chỉ 1 phần khớp phạm vi.
 *
 * ÁP DỤNG — 4 bullet mới ở BƯỚC 2 (định nghĩa "Nội dung giá trị" + phần "Thêm vào" của "Biên tập
 * kịch bản chữ", Bước 4 nguồn): "Takeaway lớn nhất" (định nghĩa "nội dung giá trị = tạo sự chuyển
 * hoá", nguồn), "câu chuyện thực tế thay vì số liệu"/"framework thực hành ngay"/"cảm xúc cá nhân
 * thật" (3 gạch đầu dòng con của "Thêm vào", Bước 4 nguồn) — cả 4 đều là chỉ dẫn CHẤT LƯỢNG VIẾT áp
 * dụng được cho MỌI công thức đã chọn ở Bước 1, không riêng 1 công thức nào, đúng vai trò BƯỚC 2 sẵn
 * có (hướng dẫn cách viết, không phải chọn công thức).
 *
 * CỐ Ý KHÔNG áp dụng (3 phần còn lại của nguồn, có lý do, không mở lại):
 * 1. **"Chia sẻ điều bạn giỏi nhất" (Competence, quy tắc 80/20 Leila Hormozi)** — đây là tiêu chí
 *    CHỌN CHỦ ĐỀ (content strategy), không phải cách viết kịch bản cho 1 chủ đề đã chọn. Action này
 *    nhận `$topic` làm INPUT đã quyết định sẵn trước khi vào form — không có vai trò tư vấn nên làm
 *    chủ đề gì, cùng nhóm lý do đã loại các nội dung content-strategy cấp kênh trước đây.
 * 2. **Bước 3 nguồn "Dump Talking"** (nói nháp tự do bằng giọng nói, chuyển thành văn bản) — hoạt
 *    động CON NGƯỜI làm TRƯỚC khi có nội dung, không áp dụng được vào 1 prompt yêu cầu AI TỰ SÁNG
 *    TÁC kịch bản từ đầu (khác vai trò "biên tập lại 1 bản nháp có sẵn").
 * 3. **Bước 5 nguồn "Quay, dựng, đăng tải và tối ưu... theo dõi số liệu thực tế"** — trùng quyết
 *    định "không theo dõi hiệu suất phân phối" đã chốt nhiều lần trong lịch sử module (§10, từ
 *    v1.5/v1.10/v1.14/v1.16 — không mở lại).
 */
class BuildMasterScriptPromptAction
{
    use AsAction;

    public function handle(string $topic, ?string $description, bool $isMotherBaby): string
    {
        $lines = [
            'Bạn là một chuyên gia Copywriter chuyên nghiệp mảng Affiliate Video ngắn (TikTok/Reels).',
            'Nhiệm vụ của bạn là tư vấn công thức và viết kịch bản dựa trên thông tin tôi cung cấp dưới đây.',
            '',
            'THÔNG TIN ĐẦU VÀO:',
            '- Chủ đề/Sản phẩm: '.$this->indentContinuationLines($topic),
            '- Mô tả bổ sung: '.$this->indentContinuationLines($this->descriptionOrFallback($description)),
            '',
            'BƯỚC 1: PHÂN TÍCH VÀ CHỌN CÔNG THỨC (CONTENT TYPE FORMAT)',
            'Sử dụng Cây quyết định sau để chọn công thức phù hợp nhất (áp dụng theo thứ tự, dừng ở câu đầu tiên cho ra kết quả):',
            '1. Sản phẩm/chủ đề có thuộc nhóm CẤM/HẠN CHẾ quảng cáo không? (VD: sữa công thức <24 tháng, bình bú, ti giả, thực phẩm chức năng, thuốc). Nếu CÓ → chỉ chọn Onboarding hoặc Hook–Value–CTA, KHÔNG có CTA bán hàng trực tiếp.',
            '2. Dùng sai có gây hậu quả sức khỏe/an toàn không? (VD: nhộng chũn, dụng cụ hút mũi, ghế ô tô trẻ em, thiết bị điện, hoá chất). Nếu CÓ → chọn Onboarding, tập trung vào phần "Những lỗi thường gặp".',
            '3. Giá cao (trên khoảng 800.000đ) HOẶC quyết định mua cần nhiều cân nhắc? Nếu CÓ → chọn Testimonial 5 phần (organic) hoặc ABCD (nếu chạy quảng cáo trả phí).',
            '4. Thay đổi/kết quả có quan sát được bằng mắt trong 1 khung hình không? (VD: vết bẩn, độ thấm hút, không gian gọn lại). Nếu CÓ → chọn Before–After–Bridge. TUYỆT ĐỐI không dùng before/after trên cơ thể người/trẻ em — nếu thay đổi nằm trên cơ thể người, chuyển sang câu hỏi 5.',
            '5. Khán giả mục tiêu đã tự biết mình có vấn đề này chưa? Nếu RỒI → chọn Problem–Solution–CTA. Nếu CHƯA (cần giáo dục trước) → chọn Hook–Value–CTA.',
            '',
            'Mô tả chi tiết từng công thức (chọn đúng 1 công thức phù hợp nhất theo cây quyết định trên; có thể đề xuất công thức khác trong danh sách dưới đây thay thế nếu bạn thấy phù hợp hơn — miễn giải thích rõ lý do):',
        ];

        foreach (CompileProjectDirectorPromptAction::FORMULA_TIPS_BY_VIDEO_FORMULA as $tip) {
            $lines[] = '- '.$tip;
        }

        $lines[] = '';
        $lines[] = 'BƯỚC 2: VIẾT KỊCH BẢN';
        $lines[] = 'Dựa vào công thức đã chọn ở Bước 1, hãy viết kịch bản chi tiết:';
        $lines[] = '- Trước khi viết: xác định rõ TAKEAWAY LỚN NHẤT — 1 bài học/thay đổi cụ thể trong suy nghĩ hoặc hành vi mà người xem có thể ghi nhớ và áp dụng NGAY sau khi xem xong. Nội dung không tạo ra sự thay đổi này chỉ là quảng cáo suông, dù đúng công thức tới đâu.';
        $lines[] = '- Độ dài: theo đúng khoảng thời lượng khuyến nghị của công thức đã chọn (xem mô tả ở Bước 1).';
        $lines[] = '- Hình thức: bảng 3 cột (Thời lượng/Nhịp | Hình ảnh/Video Shot | Lời thoại/Audio).';
        $lines[] = '- Ưu tiên kể 1 trải nghiệm/câu chuyện thực tế cụ thể (KHÔNG bịa) thay vì liệt kê số liệu/tính năng khô khan — người xem nhớ câu chuyện hơn số liệu.';
        $lines[] = '- Nếu phù hợp với công thức đã chọn, lồng 1 mẹo/framework nhỏ người xem áp dụng được NGAY, không chỉ dừng ở lời khen sản phẩm.';
        $lines[] = '- Giọng văn cần cảm xúc cá nhân thật (tiếc nuối, mừng rỡ, bất ngờ...), tránh văn phong quảng cáo vô cảm, sáo rỗng.';
        $lines[] = '';
        $lines[] = 'RANH GIỚI BẮT BUỘC:';

        foreach ($this->legalBoundaries($isMotherBaby) as $boundary) {
            $lines[] = '- '.$boundary;
        }

        $lines[] = '';
        $lines[] = 'Hãy trả về kết quả bao gồm: Công thức đã chọn, Lý do chọn (dựa trên cây quyết định ở Bước 1), và Kịch bản chi tiết.';

        return implode("\n", $lines);
    }

    private function descriptionOrFallback(?string $description): string
    {
        $trimmed = trim((string) $description);

        return $trimmed !== '' ? $trimmed : 'Không có mô tả chi tiết';
    }

    /** @return string[] */
    private function legalBoundaries(bool $isMotherBaby): array
    {
        if (! $isMotherBaby) {
            return [
                'Không đưa ra cam kết/tuyên bố hiệu quả chưa được kiểm chứng (đặc biệt với sản phẩm liên quan sức khỏe, tài chính, thực phẩm).',
                'Không dùng thủ pháp thổi phồng nỗi sợ hoặc tạo áp lực tâm lý quá mức ở phần mở đầu.',
                'Tuân thủ chính sách quảng cáo của nền tảng đăng video (TikTok/Facebook/YouTube...) — kiểm tra lại nếu sản phẩm thuộc ngành hàng bị hạn chế quảng cáo riêng của nền tảng đó.',
            ];
        }

        // spec/ma-tran-cong-thuc-kich-ban.md Phần 6/Phần 8 — nguyên văn ranh giới ngách Mẹ & Bé.
        return [
            'Không dùng hình ảnh trẻ khóc/đau để tạo áp lực tâm lý ở phần mở đầu.',
            'TUYỆT ĐỐI không dùng before/after trên cơ thể trẻ em (da/vùng hăm...) — chỉ dùng before/after cho đồ vật, vải, không gian.',
            'Với TPCN/sản phẩm da liễu/sức khỏe: KHÔNG cam kết chữa bệnh/hiệu quả điều trị, chỉ chia sẻ dưới góc độ trải nghiệm cá nhân, kèm khuyến nghị khám chuyên khoa khi cần.',
            'Sản phẩm thuộc nhóm cấm/hạn chế quảng cáo theo Nghị định 100/2014/NĐ-CP (sữa công thức <24 tháng, bình bú, ti giả...) hoặc TPCN: KHÔNG có CTA bán hàng trực tiếp, chỉ nội dung giáo dục/hướng dẫn kỹ thuật.',
        ];
    }

    /**
     * spec/AIVideoStudioTemplate_Technical_Specification.md §13.1 (v1.7 gốc, dùng lại nguyên văn kỹ
     * thuật của `BuildShotPromptAction::indentContinuationLines()`) — giữ ranh giới dòng `NHÃN: giá
     * trị` không vỡ khi `topic`/`description` có nhiều dòng.
     */
    private function indentContinuationLines(string $value): string
    {
        $normalized = preg_replace("/\r\n?/", "\n", trim($value));

        return str_replace("\n", "\n    ", $normalized);
    }
}
