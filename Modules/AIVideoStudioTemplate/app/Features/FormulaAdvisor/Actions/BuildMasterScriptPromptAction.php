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
 *
 * spec/AIVideoStudioTemplate_Technical_Specification.md §13.6 (v1.29 — người dùng đưa tiếp 1 đoạn
 * tài liệu khác "Chuẩn hoá dataset đào tạo + Custom Instructions + Quy trình 3 bước viết kịch bản +
 * Meta-Prompting", cùng câu hỏi). Nguồn này mô tả 1 KIỂU CÔNG CỤ KHÁC HẲN — Claude Projects có bộ nhớ
 * BỀN VỮNG qua nhiều phiên (Project Knowledge lưu training dataset, Custom Instructions áp dụng cho
 * MỌI lần tạo sau) và quy trình HỘI THOẠI NHIỀU LƯỢT (idea → outline → draft, 3 lần gọi AI riêng
 * biệt) — trong khi Action này sinh ĐÚNG 1 PROMPT KHÔNG TRẠNG THÁI mỗi lần gọi (§0: "KHÔNG lưu lịch
 * sử", mỗi lần render tính lại từ đầu từ `$topic`/`$description`/`$isMotherBaby`, không có khái niệm
 * "nhớ" giữa các lần). Rà đủ 4 phần nguồn:
 *
 * Đánh giá LẦN 1 (ghi lại rồi SỬA ở LẦN 2 dưới đây, không xoá — người dùng phản hồi trực tiếp: "kiến
 * thức kỹ thuật và quy trình trên nhất định phải có điểm neo để bổ sung vào hoàn thiện, chứ không
 * liên quan gì đến các công cụ AI cả"): lần đầu chỉ trích ra bullet "khán giả mục tiêu cụ thể" rồi
 * loại 4 phần còn lại vì lý do chúng "cần Project Knowledge/Custom Instructions/hội thoại nhiều
 * lượt" — SAI Ở CHỖ lấy CÁCH Claude Projects triển khai kỹ thuật làm lý do loại, thay vì tách xem
 * NGUYÊN LÝ kỹ thuật đằng sau (độc lập với tool nào) có áp dụng được vào 1 prompt đơn hay không.
 *
 * Đánh giá LẦN 2 (đúng hướng người dùng chỉ ra — tách nguyên lý khỏi cách Claude Projects triển
 * khai nó, rồi mới xét có điểm neo trong 1 prompt đơn không):
 *
 * ÁP DỤNG — 3 điểm neo mới ở BƯỚC 2 (giữ nguyên bullet "khán giả mục tiêu cụ thể" đã thêm):
 * 1. **Khung sườn Hook → Build-up → Core Content → (Re-hook nếu dài) → CTA**, gộp từ 2 phần nguồn:
 *    nguyên lý gắn nhãn 5 nhịp của "chuẩn hoá dataset" (§ nguồn 4) VÀ nguyên lý "lập outline trước
 *    khi viết full draft" của quy trình 3 bước (§ nguồn 6) — bản chất cả 2 CÙNG nói 1 việc: đừng
 *    viết thẳng lời thoại đầy đủ, hãy PHÁC KHUNG NHỊP TRƯỚC. Việc "dataset hoá qua nhiều phiên" và
 *    "3 lượt hội thoại riêng biệt" mới là cơ chế RIÊNG của Claude Projects (cần bộ nhớ bền vững) —
 *    còn bản thân khung 5 nhịp (đặc biệt "Re-hook", khoảng trống thật: 3/7 công thức hiện có
 *    (Demo/Testimonial/Onboarding, 50-240s) không có bước giữ chân giữa video) áp dụng được trong
 *    CÙNG 1 prompt, chỉ cần AI tự phác khung rồi viết luôn, không cần lượt gọi riêng.
 * 2. **Cân nhắc ≥2 cách mở Hook rồi chọn cách mạnh nhất**, gộp vào bullet 1 — bản rút gọn, tương
 *    xứng với video ngắn, của nguyên lý "đề xuất nhiều lựa chọn trước khi chốt 1" (§ nguồn 6); bỏ
 *    quy mô "10-25 ý tưởng" của nguồn vì đó tương xứng video dài cần lên kế hoạch kỹ, không tương
 *    xứng 15s-4 phút module phục vụ.
 * 3. **Tự đánh giá & đề xuất cải thiện ngay trong CÙNG phản hồi** (thêm 1 mục ở cuối kịch bản, gộp
 *    vào "Hãy trả về kết quả...") — nguyên lý cốt lõi của Meta-Prompting (§ nguồn 7) là "bản nháp
 *    đầu hiếm khi hoàn hảo, cần tự phê bình tìm điểm yếu" — nguyên lý này KHÔNG bắt buộc phải đưa
 *    sang 1 AI/phiên khác mới làm được; yêu cầu chính AI đang viết tự rà lại và liệt kê điểm yếu
 *    NGAY trong cùng câu trả lời vẫn giữ đúng tinh thần, chỉ bỏ phần "2 tool tách biệt" (cơ chế
 *    riêng của quy trình multi-turn, không phải nguyên lý).
 *
 * CỐ Ý KHÔNG áp dụng (chỉ còn phần THỰC SỰ đòi hỏi trạng thái bền vững qua nhiều lần gọi — không
 * còn cách diễn giải tool-độc-lập nào khác):
 * 1. **Lưu transcript đã gắn nhãn thành Training Dataset tái sử dụng nhiều dự án sau** — đây đúng là
 *    hành vi LƯU TRỮ lâu dài, không phải chỉ dẫn cho 1 lần sinh kịch bản; tool này không lưu lịch sử
 *    giữa các lần gọi (§0). Bản thân khung 5 nhịp đã lấy ở mục ÁP DỤNG 1 phía trên rồi.
 * 2. **Tải tài liệu mẫu vào Project Knowledge + Custom Instructions áp dụng cho MỌI lần tạo sau** —
 *    cùng lý do 1: đây là cấu hình BỀN VỮNG giữa các lần gọi, đúng nghĩa đen cần 1 nền tảng có bộ
 *    nhớ dài hạn. 2 yếu tố nội dung của Custom Instructions ("Bạn là ai?"/"loại nội dung gì?") vẫn
 *    đã có sẵn ở dòng Vai trò như đánh giá lần 1.
 *
 * spec/AIVideoStudioTemplate_Technical_Specification.md §13.6 (v1.30 — người dùng đưa tiếp 1 đoạn
 * tài liệu khác "Khung 4 Bước Viết Kịch Bản Viral" (Packaging trước khi viết + Intro/Click
 * Confirmation/Curiosity Gap + Research/Shock Value + Body/Retention Dance/Sub-hook + Outro/Loop
 * Trap), không kèm câu hỏi rõ ràng — áp dụng cùng kỷ luật rà soát đã dùng cho mọi nguồn trước, chỉ
 * áp dụng phần khớp đúng vai trò BƯỚC 2 "viết kịch bản cho 1 công thức/topic đã chọn sẵn":
 *
 * ÁP DỤNG — 3 điểm neo mới ở BƯỚC 2:
 * 1. **Hook 3-6 giây đầu = xác nhận đúng kỳ vọng + mở khoảng trống tò mò** (chuyển thể "Click
 *    Confirmation" — nguồn dùng cặp Tiêu đề/Thumbnail làm chuẩn xác nhận, module này không có
 *    Packaging nên đổi chuẩn xác nhận sang `$topic`/`$description` đã nêu ở đầu prompt; và "Curiosity
 *    Gap" — giữ nguyên 5 kỹ thuật FOMO/đồng cảm/nỗi sợ/câu hỏi/ngắt mô thức, đều là kỹ thuật viết
 *    Hook thuần túy, không phụ thuộc nền tảng hay công cụ AI nào).
 * 2. **Core Content theo mạch Bối cảnh → Chi tiết → Mâu thuẫn → Cao trào-kết luận + xen kẽ nhịp độ +
 *    ngắt mô thức bằng ví dụ/hình ảnh bất ngờ** (gộp "Storytelling Method" + "Retention Dance" +
 *    "Pattern Interruption" của Bước 3 nguồn — cả 3 đều là chỉ dẫn CÁCH VIẾT thân bài, đúng vai trò
 *    sẵn có của BƯỚC 2, không đụng tới Bước 1/công thức đã chọn).
 * 3. **Sửa lại "Re-hook" (v1.29) thành "Sub-hook" số nhiều** — nguồn (Bước 3, "Chuyển đoạn qua
 *    Sub-hook") chỉ rõ hơn: nên chèn NHIỀU điểm móc giữa các luận điểm chính (do độ tập trung chỉ
 *    ~8 giây, tối đa 3 phút/1 mạch chuyện), không phải chỉ 1 lần cố định ở giữa video như v1.29 diễn
 *    giải — sửa lại khung sườn cho đúng hơn, không phải điểm neo hoàn toàn mới.
 *
 * CỐ Ý KHÔNG áp dụng (3 phần, có lý do, không mở lại):
 * 1. **Packaging (Ý tưởng/Tiêu đề/Thumbnail phải hoàn thiện trước khi viết kịch bản)** — đây là bước
 *    xảy ra TRƯỚC khi có `$topic`; Action này nhận `$topic`/`$description` làm INPUT đã quyết định
 *    sẵn (cùng nhóm lý do đã loại "chọn chủ đề 80/20" ở v1.28) — không có vai trò tư vấn Tiêu đề/
 *    Thumbnail, và module cũng không sinh nội dung dạng đó ở bất kỳ trang nào khác.
 * 2. **Research & Shock Value (Consuming/Doing/Analyzing để tăng độ sâu kiến thức)** — hoạt động
 *    NGHIÊN CỨU CON NGƯỜI làm TRƯỚC khi có nội dung để viết, cùng bản chất với "Dump Talking" đã loại
 *    ở v1.28 (khác vai trò "AI tự sáng tác kịch bản từ 1 topic/description đã có sẵn"); `$description`
 *    đã là nơi người dùng gói ghém kết quả nghiên cứu của họ trước khi vào Action.
 * 3. **Outro/"The Loop Trap" (tóm tắt → tạo nhu cầu mới → điều hướng sang video tiếp theo trong 1
 *    series)** — sai mục tiêu CTA: nguồn nhắm giữ chân xem tiếp 1 video KHÁC trong cùng kênh (channel
 *    growth), còn CTA của module là hành động AFFILIATE (mua/dùng thử sản phẩm) cho ĐÚNG video đang
 *    xem — không có khái niệm "video tiếp theo" trong 1 prompt sinh 1 kịch bản độc lập. Phần "tóm tắt
 *    lại" cũng trùng vai trò bullet "TAKEAWAY LỚN NHẤT" đã có từ v1.28 (đảm bảo người xem nhớ 1 điều
 *    cụ thể, không cần thêm 1 bước tóm tắt riêng).
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
        $lines[] = '- Trước khi viết: xác định rõ KHÁN GIẢ MỤC TIÊU CỤ THỂ (độ tuổi, hoàn cảnh, mối quan tâm) — tránh nhắm chung chung "tất cả mọi người", kịch bản sẽ thiếu cá tính và không chạm đúng ai.';
        $lines[] = '- Trước khi viết: xác định rõ TAKEAWAY LỚN NHẤT — 1 bài học/thay đổi cụ thể trong suy nghĩ hoặc hành vi mà người xem có thể ghi nhớ và áp dụng NGAY sau khi xem xong. Nội dung không tạo ra sự thay đổi này chỉ là quảng cáo suông, dù đúng công thức tới đâu.';
        $lines[] = '- Độ dài: theo đúng khoảng thời lượng khuyến nghị của công thức đã chọn (xem mô tả ở Bước 1).';
        $lines[] = '- Hình thức: bảng 3 cột (Thời lượng/Nhịp | Hình ảnh/Video Shot | Lời thoại/Audio).';
        $lines[] = '- Trước khi viết lời thoại đầy đủ: phác nhanh khung sườn theo nhịp Hook (mở đầu giữ chân) → Build-up (dẫn dắt) → Core Content (nội dung chính) → Sub-hook (nếu kịch bản dài hơn khoảng 60 giây: chèn 1-2 câu "móc lại" giữa các luận điểm chính, không dồn vào đúng 1 chỗ) → CTA. Với đoạn Hook, cân nhắc ít nhất 2 cách mở đầu khác nhau rồi chọn cách mạnh nhất.';
        $lines[] = '- Hook (3-6 giây đầu): xác nhận NGAY nội dung đúng với Chủ đề/Mô tả đã nêu ở trên (không đánh lừa kỳ vọng người xem), đồng thời mở 1 khoảng trống tò mò bằng 1 trong các cách: FOMO (sợ bỏ lỡ), đồng cảm (tình huống quen thuộc), nỗi sợ (cảnh báo 1 mất mát cụ thể), đặt câu hỏi hóc búa, hoặc đi ngược niềm tin thông thường của số đông.';
        $lines[] = '- Trong Core Content: dẫn dắt theo mạch Bối cảnh → Chi tiết cụ thể → Mâu thuẫn/thử thách → Cao trào-kết luận; xen kẽ nhịp độ thông tin hay/độc đáo với thông tin quen thuộc dễ hiểu (tránh dồn hết phần hay vào 1 chỗ), thỉnh thoảng chèn 1 ví dụ đời thường/hình ảnh ví von bất ngờ để tránh giọng điệu đều đều.';
        $lines[] = '- Ưu tiên kể 1 trải nghiệm/câu chuyện thực tế cụ thể (KHÔNG bịa) thay vì liệt kê số liệu/tính năng khô khan — người xem nhớ câu chuyện hơn số liệu.';
        $lines[] = '- Nếu phù hợp với công thức đã chọn, lồng 1 mẹo/framework nhỏ người xem áp dụng được NGAY, không chỉ dừng ở lời khen sản phẩm.';
        $lines[] = '- Giọng văn cần cảm xúc cá nhân thật (tiếc nuối, mừng rỡ, bất ngờ...), tránh văn phong quảng cáo vô cảm, sáo rỗng.';
        $lines[] = '- Sau khi viết xong: tự rà lại kịch bản 1 lượt, nêu ngắn gọn 1-2 điểm còn có thể yếu/khó hiểu và cách cải thiện, viết thành 1 mục riêng "Tự đánh giá & đề xuất cải thiện" ở cuối.';
        $lines[] = '';
        $lines[] = 'RANH GIỚI BẮT BUỘC:';

        foreach ($this->legalBoundaries($isMotherBaby) as $boundary) {
            $lines[] = '- '.$boundary;
        }

        $lines[] = '';
        $lines[] = 'Hãy trả về kết quả bao gồm: Công thức đã chọn, Lý do chọn (dựa trên cây quyết định ở Bước 1), Kịch bản chi tiết, và mục Tự đánh giá & đề xuất cải thiện.';

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
