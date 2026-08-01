<?php

namespace Modules\CoreIdeaExtractor\Features\CategoryFoundation\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;

/**
 * spec/CoreIdeaExtractor.md §12 (v1.4)/§12.6 (v1.10)/§12.7 (v1.11) — ngữ cảnh biên tập bền vững
 * theo 1 PostCategory ("Category Content Foundation"). core_focus/unique_angle/content_goals ánh
 * xạ 3 thành phần "Business Foundation Document" (core offering/UVP/goals) sang ngữ cảnh biên
 * tập; pain_points là câu hỏi/khó khăn thường gặp của độc giả rút ra từ NGHIÊN CỨU THỰC TẾ (khảo
 * sát/feedback/câu hỏi lặp lại); rejected_ideas là "Decision Log" — ý tưởng đã cân nhắc và QUYẾT
 * ĐỊNH KHÔNG viết kèm lý do (tribal knowledge editor tự ghi tay, không suy ra được từ dữ liệu có
 * sẵn — khác `ListCategoryExistingArticlesAction` chỉ liệt kê bài ĐÃ publish, không biết ý tưởng
 * nào từng bị CÂN NHẮC RỒI TỪ CHỐI); audience/constraints/style_sample giữ nguyên field ad-hoc đã
 * có ở form batch (ExtractBatchRequestData) — chỉ khác là được LƯU LẠI theo category thay vì gõ
 * tay mỗi lần.
 *
 * objections/decision_criteria (2026-08) — đối chiếu bài context-engineering (animalz.co), tách
 * riêng khỏi pain_points: pain_points là KHÓ KHĂN/câu hỏi thực tế độc giả gặp phải; objections là
 * LÝ DO CÒN NGHI NGỜ/CHƯA TIN khiến độc giả chưa hành động (VD "sợ tốn tiền mà không hiệu quả");
 * decision_criteria là TIÊU CHÍ họ dùng để so sánh/chọn giữa các lựa chọn (VD "giá, có bác sĩ tư
 * vấn hay không, đánh giá thật từ người dùng khác"). Gộp chung vào pain_points sẽ khiến editor bỏ
 * sót 1 trong 2 hoặc viết lẫn lộn không tách được ý nào dùng cho mục đích nào khi build prompt.
 *
 * family_values_focus (2026-08) — KHÁC BẢN CHẤT mọi field ở trên: không phải văn bản tự do editor
 * viết, mà là TẬP KEY (con của danh sách CỐ ĐỊNH config('core_idea_extractor.family_values.items'),
 * nguồn sự thật duy nhất — xem docblock ở đó) cho biết chuyên mục ưu tiên phục vụ giá trị nào
 * trong Hệ giá trị gia đình Việt Nam (ấm no/hạnh phúc/tiến bộ/văn minh, Quyết định 1189/QĐ-TTg).
 * Validate danh sách key hợp lệ nằm ở CategoryFoundationController::upsert() ('in:...' đọc động từ
 * config) — Data class chỉ giữ nullable array, không hardcode lại danh sách key ở đây.
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
        #[Nullable, Max(2000)]
        public readonly ?string $rejected_ideas = null,
        #[Nullable, Max(500)]
        public readonly ?string $audience = null,
        #[Nullable, Max(500)]
        public readonly ?string $constraints = null,
        #[Nullable, Max(3000)]
        public readonly ?string $style_sample = null,
    ) {}
}
