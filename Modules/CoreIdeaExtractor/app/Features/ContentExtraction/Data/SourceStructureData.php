<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Data;

use Spatie\LaravelData\Data;

/**
 * spec/CoreIdeaExtractor.md §5.2/§7 (v1.5) — tín hiệu CẤU TRÚC của nguồn (không phải nội dung),
 * tham khảo https://kime.ai/blog/structure-content-for-llm-extraction: nguồn dùng bảng/danh sách
 * số/heading dạng câu hỏi thường được AI answer engine (ChatGPT/Perplexity/AI Overviews) trích
 * dẫn nhiều hơn văn xuôi thường — hữu ích để người viết biết nguồn tham khảo này đã "tối ưu cho
 * AI search" tới đâu, cân nhắc chọn góc viết khác biệt thay vì lặp lại. Tín hiệu THÔ, khách quan,
 * KHÔNG quy về 1 nhãn "tốt/xấu" chủ quan (tránh thêm 1 tầng heuristic mờ vào spec đã version hoá
 * chặt chẽ ở §5.4/§9) — diễn giải để ở ghi chú (xem CoreIdeaExtractorController::appendStructureNote()).
 */
class SourceStructureData extends Data
{
    public function __construct(
        public readonly bool $has_tables,
        public readonly bool $has_numbered_lists,
        public readonly bool $has_bullet_lists,
        /** Tỉ lệ heading (đã loại noise/trang trí, xem §5.3) kết thúc bằng dấu "?" — 0.0 nếu không có heading nào. */
        public readonly float $question_heading_ratio,
    ) {}

    /**
     * Dùng khi không có HTML nào để phân tích (fetch thất bại hoàn toàn) — "không có tín hiệu cấu
     * trúc" là đúng bản chất, không phải giá trị giả. Đặt tên none() (không phải empty()) vì
     * Spatie\LaravelData\Data đã định nghĩa sẵn empty() với chữ ký khác (dùng cho OpenAPI/docs
     * generation) — trùng tên sẽ vi phạm signature compatibility của lớp cha.
     */
    public static function none(): self
    {
        return new self(
            has_tables: false,
            has_numbered_lists: false,
            has_bullet_lists: false,
            question_heading_ratio: 0.0,
        );
    }
}
