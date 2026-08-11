<?php

namespace Modules\ContentCalendar\Features\CalendarPlanning\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/**
 * spec/ContentCalendar_Technical_Specification.md §7/§17.1 — DTO typed cho payload tạo/sửa entry.
 * Validate THẬT sự nằm ở $request->validate() trong CalendarEntryController (cùng convention
 * CategoryFoundationController::upsert() — Data::from($validated) chỉ là container chuyển dữ
 * liệu ĐÃ validate, không phải nơi enforce rule).
 */
class CalendarEntryData extends Data
{
    public function __construct(
        #[Required]
        public readonly int $post_category_id,
        #[Required, Max(255)]
        public readonly string $title,
        #[Nullable, Max(2000)]
        public readonly ?string $brief = null,
        #[Required]
        public readonly string $origin = 'manual',
        #[Nullable, Max(5000)]
        public readonly ?string $origin_note = null,
        #[Nullable]
        public readonly ?string $target_publish_date = null,
        #[Nullable]
        public readonly ?int $assigned_to = null,
        // (2026-08-11) — xem Modules\ContentCalendar\Enums\FunnelStage. Tuỳ chọn, cùng lý do
        // assigned_to/target_publish_date: không ép biên tập viên phân loại ngay lúc tạo.
        #[Nullable]
        public readonly ?string $funnel_stage = null,
    ) {}
}
