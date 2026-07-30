<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Data;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;

class ExtractBatchRequestData extends Data
{
    /**
     * @param  string[]  $urls  Validate mảng ở controller (required|array|min:1|max:core_idea_extractor.batch.max_urls,
     *   urls.* => url|distinct) — rule động theo config nên không khai bằng attribute Spatie tĩnh.
     */
    public function __construct(
        public readonly array $urls,
        /**
         * Từ khóa nghiên cứu do người dùng nhập — echo lại trong response để nhận diện payload
         * khi dán vào chat AI, KHÔNG dùng để thay đổi việc fetch URL nào/parse HTML ra sao (mọi
         * nguồn vẫn được crawl và extract đầy đủ y hệt nhau, không có SSRF/logic rẽ nhánh nào phụ
         * thuộc topic). Có 1 ngoại lệ hẹp: khi main_content 1 nguồn dài hơn ngân sách ký tự phải
         * cắt bớt để paste vào chat AI, topic được dùng làm tín hiệu chọn ĐOẠN VĂN nào ưu tiên
         * giữ lại (xem CoreIdeaExtractorController::selectRelevantContent()) — vẫn chỉ ảnh hưởng
         * tới việc HIỂN THỊ phần nào của nội dung đã trích được, không thay đổi kết quả trích xuất
         * gốc (title/headings/keywords/confidence... tính trên toàn bộ main_content chưa cắt).
         */
        #[Nullable, Max(255)]
        public readonly ?string $topic = null,
        /**
         * Ngữ cảnh phía người viết (audience/goal/constraints) — thuần metadata do người dùng tự
         * gõ, echo lại trong response dưới `brief`, KHÔNG qua AI xử lý. Lý do thêm: nội dung
         * sources[] chỉ là ngữ cảnh PHÍA NGUỒN (source-side) — nếu dán JSON vào chat AI mà thiếu
         * hẳn ngữ cảnh phía người viết (đối tượng đọc, mục tiêu, ràng buộc), AI vẫn trả lời chung
         * chung dù nguồn tham khảo có sâu đến đâu.
         */
        #[Nullable, Max(500)]
        public readonly ?string $audience = null,
        // max:2000 khớp giới hạn thật của content_goals (CategoryFoundationData) — field này được
        // prefill trực tiếp từ foundation.content_goals, không phải input ngắn người tự gõ.
        #[Nullable, Max(2000)]
        public readonly ?string $goal = null,
        #[Nullable, Max(500)]
        public readonly ?string $constraints = null,
        /**
         * Đoạn văn mẫu người dùng tự dán (VD 1 đoạn họ từng viết) — mô tả giọng văn bằng LỜI
         * (constraints ở trên) kém hiệu quả hơn nhiều so với đưa VÍ DỤ THẬT cho AI học theo.
         * Thuần passthrough, không xử lý gì thêm ở Layer 1.
         */
        #[Nullable, Max(3000)]
        public readonly ?string $style_sample = null,
        /**
         * Selector MẶC ĐỊNH/fallback khi 1 URL không có override riêng ở `main_content_selectors`
         * — xem ExtractRequestData::$main_content_selector.
         */
        #[Nullable, Max(255)]
        public readonly ?string $main_content_selector = null,
        /**
         * Selector RIÊNG cho từng URL, cùng thứ tự/vị trí (index) với `urls` — VD `urls[2]` dùng
         * `main_content_selectors[2]` nếu có giá trị. Lý do cần riêng field này: nhiều nguồn trong
         * 1 batch thường thuộc NHIỀU DOMAIN KHÁC NHAU, mỗi domain có bố cục CSS/template riêng —
         * 1 selector áp dụng chung cho mọi URL (như `main_content_selector` ở trên) hiếm khi đúng
         * cho tất cả. Vị trí không có override (thiếu phần tử, hoặc giá trị null/rỗng) → rơi về
         * `main_content_selector` chung, rồi mới tới tự động — xem
         * `CoreIdeaExtractorController::resolveSelectorForUrl()`. KHÔNG bắt buộc cùng độ dài với
         * `urls` (validate ở controller: nullable|array, mỗi phần tử nullable|string|max:255).
         *
         * @var array<int, string|null>|null
         */
        public readonly ?array $main_content_selectors = null,
        /** Áp dụng chung cho mọi URL trong batch — xem ExtractRequestData::$force_refresh. */
        public readonly bool $force_refresh = false,
        /** Áp dụng chung cho mọi URL trong batch — xem ExtractRequestData::$source_language. */
        #[Nullable, In(['vi', 'en', 'th', 'id'])]
        public readonly ?string $source_language = null,
    ) {}
}
