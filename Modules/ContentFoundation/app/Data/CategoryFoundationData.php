<?php

namespace Modules\ContentFoundation\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;

/**
 * spec/CoreIdeaExtractor.md §12 — ngữ cảnh biên tập bền vững theo 1 PostCategory ("Category
 * Content Foundation"). core_focus/unique_angle/content_goals ánh xạ 3 thành phần "Business
 * Foundation Document" (core offering/UVP/goals) sang ngữ cảnh biên tập; pain_points là câu hỏi/
 * khó khăn thường gặp của độc giả rút ra từ NGHIÊN CỨU THỰC TẾ; rejected_ideas là "Decision Log"
 * — ý tưởng đã cân nhắc và QUYẾT ĐỊNH KHÔNG viết kèm lý do; objections là LÝ DO CÒN NGHI NGỜ/CHƯA
 * TIN khiến độc giả chưa hành động; decision_criteria là TIÊU CHÍ họ dùng để so sánh/chọn giữa các
 * lựa chọn; audience_behavior (§12.12) là tầng HÀNH VI THẬT (Level 3 của "3 cấp độ hiểu đối tượng" —
 * lindapophal.substack.com: demographic/psychographic/behavioral) — ngày của họ trông thế nào, họ
 * đang tìm kiếm/tiêu thụ nội dung gì, KHÁC `audience` vốn chỉ là mô tả nhân khẩu học (Level 1);
 * family_values_focus là TẬP KEY (con của danh sách CỐ ĐỊNH
 * config('content_foundation.family_values.items')) cho biết chuyên mục ưu tiên phục vụ giá trị
 * nào trong Hệ giá trị gia đình Việt Nam; family_conduct_focus (spec §12.11) cùng cơ chế cho 4 cặp
 * quan hệ của Bộ tiêu chí ứng xử trong gia đình (config('content_foundation.family_conduct_
 * standards.items')). Validate danh sách key hợp lệ nằm ở CategoryFoundationController::upsert()
 * ('in:...' đọc động từ config). `product_service_docs`/`best_example_content` (§12.13, martech.org/
 * how-to-build-an-ai-content-system-that-works) là tài liệu mô tả chi tiết sản phẩm/dịch vụ và ví dụ
 * nội dung/dàn ý mẫu TỐT NHẤT đã có — 2 phần "Constants" còn thiếu so với mô hình AI content system
 * chuẩn, khác `style_sample` (chỉ là mẫu giọng văn ngắn).
 */
class CategoryFoundationData extends Data
{
    public function __construct(
        #[Nullable, Max(2000)]
        public readonly ?string $core_focus = null,
        #[Nullable, Max(1500)]
        public readonly ?string $writer_insights = null,
        #[Nullable, Max(2000)]
        public readonly ?string $unique_angle = null,
        #[Nullable, Max(2000)]
        public readonly ?string $content_goals = null,
        #[Nullable, Max(2000)]
        public readonly ?string $pain_points = null,
        #[Nullable, Max(2000)]
        public readonly ?string $objections = null,
        #[Nullable, Max(2000)]
        public readonly ?string $decision_criteria = null,
        #[Nullable]
        public readonly ?array $family_values_focus = null,
        #[Nullable]
        public readonly ?array $family_conduct_focus = null,
        #[Nullable, Max(2000)]
        public readonly ?string $rejected_ideas = null,
        #[Nullable, Max(500)]
        public readonly ?string $audience = null,
        #[Nullable, Max(2000)]
        public readonly ?string $audience_behavior = null,
        #[Nullable, Max(500)]
        public readonly ?string $constraints = null,
        #[Nullable, Max(3000)]
        public readonly ?string $style_sample = null,
        #[Nullable, Max(4000)]
        public readonly ?string $product_service_docs = null,
        #[Nullable, Max(4000)]
        public readonly ?string $best_example_content = null,
    ) {}
}
