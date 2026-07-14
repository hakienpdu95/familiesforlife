<?php

namespace Modules\Event\Features\EventModeration\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/**
 * Dữ liệu tạo/sửa Event do staff nhập trực tiếp trong dashboard (không qua form public —
 * spec/Event_Management_Technical_Specification.md §4: quan hệ EventSubmission optional,
 * KHÔNG tạo dòng nào khi tạo qua đường này). Ràng buộc chéo theo location_type/price_type
 * (§5.5) validate ở FormRequest (EventAdminController::validated()), không lặp ở đây — Spatie
 * Data không diễn đạt tốt "required nếu cột khác = X" nhiều điều kiện lồng nhau.
 */
class EventData extends Data
{
    public function __construct(
        #[Required]
        public readonly int $category_id,

        #[Required, Max(150)]
        public readonly string $title,

        #[Required, Max(55)]
        public readonly string $short_title,

        #[Required]
        public readonly string $description,

        #[Required]
        public readonly string $start_date,

        #[Required]
        public readonly string $end_date,

        public readonly ?string $start_time,
        public readonly ?string $end_time,

        #[Required]
        public readonly string $location_type,

        public readonly ?string $venue_name,
        public readonly ?string $venue_address,

        // KHÔNG có province_name/ward_name ở đây — <x-address-picker> chỉ submit code, và kể
        // cả có gửi tên cũng không nên tin (form public sau này không xác thực); tên thật luôn
        // tra lại từ bảng provinces/wards trong BuildEventAttributesAction (spec §10.6).
        public readonly ?string $province_code,
        public readonly ?string $ward_code,
        public readonly ?string $online_url,

        #[Required, Max(500)]
        public readonly string $website_url,

        #[Required]
        public readonly string $price_type,

        public readonly ?float $price_amount,
        public readonly ?float $price_min,
        public readonly ?float $price_max,

        public readonly ?string $poster_path,
        public readonly ?string $poster_alt,
        public readonly ?int $poster_width,
        public readonly ?int $poster_height,
        public readonly ?int $poster_size_bytes,

        public readonly bool $is_featured = false,
    ) {}
}
