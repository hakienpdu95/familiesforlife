<?php

namespace Modules\Post\Features\ArticleAuthoring\Support;

/**
 * Checklist GEO/AEO tham khảo cho tab "GEO Checklist" ở trình soạn bài — nội dung tổng hợp từ
 * spec/blog.md qua nhiều nguồn: checklist của totheweb.com, "27 best practices" của Okara.ai
 * (nhóm "On-page structure" + mục author byline ở "Entity clarity"), "GEO Audit Checklist for
 * Agencies" của Wellows.com (nhóm "Content Structure & Citation Readiness" + phần liên quan
 * E-E-A-T), framework CITABLE của Crowdstax.com (riêng nguyên tắc "Trustworthy" — nêu rõ phạm vi/
 * giới hạn đi kèm số liệu), framework "4 Pillars of GEO" của Lumar.io (riêng phần "Content GEO" —
 * entity clarity trong đoạn văn, nội dung đa phương tiện có alt text/transcript), checklist E-E-A-T
 * của Wellows.com (đối chiếu số liệu AI hỗ trợ soạn với ≥2 nguồn độc lập), và "How to Structure
 * Content for LLM Citation" của SEO Circular (tiêu đề khớp nội dung, ưu tiên tính giáo dục hơn
 * quảng cáo). CHỈ giữ lại các mục áp dụng được ở cấp 1 bài viết — bỏ các mục cấp toàn site/công ty
 * (robots.txt, Knowledge Graph, đồng nhất brand trên LinkedIn/Crunchbase, phân tích trích dẫn đối
 * thủ, đo lường referral AI, hồ sơ sản phẩm SaaS, PR/giải thưởng ngành/tài trợ...) vì không thuộc
 * phạm vi 1 bài viết trong module Post. Phần schema kỹ thuật (Organization/Article/FAQPage/HowTo)
 * module đã tự sinh sẵn (xem ArticleStructuredDataBuilder), không cần liệt kê lại thành tiêu chí
 * thủ công.
 *
 * Đây là danh sách THAM KHẢO thuần tuý để biên tập viên tự đối chiếu bằng mắt trước khi xuất bản
 * — không tự động kiểm tra, không lưu trạng thái tick (yêu cầu người dùng 2026-07-28: giữ đơn
 * giản, không cần auto-detect/checkbox).
 */
class GeoChecklist
{
    /** @return array<string, array<int, array{label: string, hint: string}>> tên nhóm => danh sách tiêu chí */
    public static function groups(): array
    {
        return [
            'Cấu trúc & câu trả lời' => [
                [
                    'label' => 'Mở đầu bằng câu trả lời trực tiếp, tự thân đầy đủ (~40–80 từ) trước khi đi vào bối cảnh',
                    'hint'  => 'Đây chính là mục đích của trường "Câu trả lời trực tiếp (AEO)" ở tab Nội dung — kiểm tra lại độ dài và tính tự thân của câu trả lời đó.',
                ],
                [
                    'label' => 'Heading (H2/H3) đặt dạng câu hỏi tự nhiên, đúng từ ngữ người đọc sẽ hỏi AI',
                    'hint'  => 'VD "Kỷ luật tích cực là gì?" thay vì "Giới thiệu chung" — dùng từ ngữ thật người đọc gõ khi hỏi ChatGPT/Gemini.',
                ],
                [
                    'label' => 'Tiêu đề phản ánh đúng nội dung bài, không giật tít/đánh lừa người đọc',
                    'hint'  => 'Tiêu đề không khớp nội dung khiến cả người đọc lẫn AI đánh giá thấp độ tin cậy của cả bài, kể cả khi phần thân bài viết tốt.',
                ],
                [
                    'label' => 'Mỗi phần/đoạn đủ tự thân để trích riêng ra khỏi ngữ cảnh vẫn hiểu trọn ý',
                    'hint'  => 'AI thường chỉ trích 1 đoạn duy nhất làm câu trả lời — đoạn phụ thuộc ngữ cảnh trước/sau sẽ khó được chọn.',
                ],
                [
                    'label' => 'Tránh dùng đại từ ("nó", "họ", "điều này") thay cho tên thực thể chính trong đoạn — nhắc lại tên cụ thể',
                    'hint'  => 'Đoạn văn dùng đại từ thay tên sẽ mất nghĩa nếu bị trích riêng ra khỏi đoạn trước — nhắc lại tên giữ đoạn tự thân đúng nghĩa.',
                ],
                [
                    'label' => 'Đoạn văn ngắn gọn, khoảng 60–100 từ, tập trung đúng 1 ý',
                    'hint'  => 'Đoạn dài, gộp nhiều ý sẽ khó để AI trích 1 đoạn hoàn chỉnh làm câu trả lời.',
                ],
                [
                    'label' => 'Trình bày so sánh/các bước thực hiện dạng bảng hoặc danh sách, không viết văn xuôi dài dòng',
                    'hint'  => 'Bảng/danh sách dễ được AI trích và giữ nguyên cấu trúc hơn văn xuôi diễn giải.',
                ],
                [
                    'label' => 'Viết bằng ngôn ngữ đơn giản, rõ ràng — không cần suy luận thêm mới hiểu được ý',
                    'hint'  => 'AI trích dẫn nguyên văn dễ dàng nhất khi không phải "diễn giải lại" câu chữ phức tạp.',
                ],
                [
                    'label' => 'Nội dung đã trả lời cả những câu hỏi tiếp theo người đọc có thể hỏi',
                    'hint'  => 'VD sau câu hỏi chính, người đọc thường hỏi tiếp "nên bắt đầu từ đâu?", "áp dụng thế nào?"',
                ],
                [
                    'label' => 'Có ít nhất 1 khối FAQ, với câu hỏi THẬT SỰ người đọc hay hỏi (không tự nghĩ ra cho có)',
                    'hint'  => 'Dùng khối "Câu hỏi thường gặp" ở trình soạn — tự động xuất FAQPage schema, giúp AI trích thẳng câu trả lời.',
                ],
                [
                    'label' => 'Định nghĩa rõ thuật ngữ chuyên môn/từ viết tắt ngay lần đầu xuất hiện trong bài',
                    'hint'  => 'Thuật ngữ dùng mà không giải thích buộc AI phải tự suy đoán nghĩa — dễ trích sai hoặc bỏ qua đoạn đó.',
                ],
                [
                    'label' => 'Có 1 khối "Ý chính cần nhớ" tóm tắt câu trả lời cốt lõi, tách riêng khỏi phần diễn giải',
                    'hint'  => 'Một khối tóm tắt ngắn, độc lập luôn là phần dễ được AI trích nguyên văn nhất trên trang.',
                ],
                [
                    'label' => 'Ảnh/video minh họa có alt text mô tả rõ nội dung; video/audio nhúng có transcript nếu có',
                    'hint'  => 'AI hiện đọc được văn bản/transcript tốt hơn nhiều so với hiểu trực tiếp nội dung ảnh/âm thanh/video.',
                ],
                [
                    'label' => 'Phần lớn bài mang tính thông tin/hướng dẫn thật sự — nội dung quảng cáo/CTA chỉ đặt ở cuối bài hoặc tách riêng, không xen giữa phần nội dung chính',
                    'hint'  => 'Áp dụng cả khi bài có tài trợ — dùng đúng khối CTA/công bố tài trợ sẵn có (tab Nội dung) thay vì lồng quảng cáo vào giữa đoạn văn, tránh làm loãng phần trả lời AI có thể trích.',
                ],
            ],
            'Uy tín & bằng chứng' => [
                [
                    'label' => 'Dùng tên riêng cụ thể (thương hiệu/sản phẩm/chuyên gia) thay vì từ ngữ chung chung',
                    'hint'  => 'Giúp AI gắn nội dung với thực thể (entity) đã biết, dễ được nhắc tới trong câu trả lời.',
                ],
                [
                    'label' => 'Khẳng định rõ ràng, có dẫn chứng — tránh ngôn ngữ mơ hồ ("có thể", "một số người cho rằng")',
                    'hint'  => 'AI ưu tiên trích dẫn câu khẳng định cụ thể hơn câu rào đón.',
                ],
                [
                    'label' => 'Số liệu/kết quả cụ thể có nêu rõ phạm vi đi kèm (khảo sát bao nhiêu người, thời điểm, cách đo) — không nêu số liệu trần trụi',
                    'hint'  => 'VD "80% phụ huynh đồng ý" không đáng tin bằng "khảo sát 80% trong 50 phụ huynh tại 1 trường, tháng 6/2026" — phạm vi rõ giúp cả người đọc lẫn AI đánh giá đúng mức độ tin cậy.',
                ],
                [
                    'label' => 'Nếu có dùng AI hỗ trợ soạn thảo (CoreIdeaExtractor/Aicem), số liệu/thông tin AI đưa ra đã được đối chiếu lại với ít nhất 2 nguồn độc lập trước khi đăng',
                    'hint'  => 'AI tạo sinh có thể "ảo giác" (hallucinate) số liệu nghe hợp lý nhưng sai — bắt buộc người biên tập tự kiểm chứng lại, không đăng thẳng nguyên văn AI trả về.',
                ],
                [
                    'label' => 'Có ít nhất 1 insight/dữ liệu/kinh nghiệm thực tế không nơi nào khác có',
                    'hint'  => 'Nội dung soạn lại chung chung (dù trình bày tốt) ít có khả năng được AI trích dẫn hơn nội dung có góc nhìn/dữ liệu riêng, độc quyền.',
                ],
                [
                    'label' => 'Có tên tác giả kèm chức danh/chứng chỉ chuyên môn thật, đặc biệt với bài mang tính chuyên môn',
                    'hint'  => 'Khai đầy đủ ở hồ sơ tác giả (chức danh, chứng chỉ) — AI đánh giá độ tin cậy nội dung một phần qua tín hiệu tác giả.',
                ],
                [
                    'label' => 'Đã kiểm chứng lại thông tin liên quan sức khỏe/tài chính/pháp lý — không để sai lệch tồn tại',
                    'hint'  => 'Nội dung nhóm chủ đề này nếu sai sẽ ảnh hưởng trực tiếp tới quyết định thật của độc giả, không chỉ là 1 bài viết dở.',
                ],
            ],
            'Liên kết & cập nhật' => [
                [
                    'label' => 'Đã liên kết tới bài viết liên quan cùng chủ đề (topic cluster)',
                    'hint'  => 'Giúp AI hiểu chuyên mục có chiều sâu chủ đề, không phải 1 bài viết đơn lẻ.',
                ],
                [
                    'label' => 'Không trùng/giẫm chân chủ đề với bài đã có trên site (đã kiểm tra bài cũ cùng đề tài)',
                    'hint'  => 'Nhiều bài mỏng cùng chủ đề làm loãng uy tín, khiến AI khó biết nên trích bài nào.',
                ],
                [
                    'label' => 'Ngày cập nhật hiển thị rõ trên trang, rà soát lại nội dung định kỳ (khuyến nghị mỗi quý)',
                    'hint'  => 'AI ưu tiên trích nội dung mới được xác nhận còn đúng hơn nội dung để lâu không rà soát.',
                ],
            ],
        ];
    }
}
