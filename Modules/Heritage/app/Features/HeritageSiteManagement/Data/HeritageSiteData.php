<?php

namespace Modules\Heritage\Features\HeritageSiteManagement\Data;

use Spatie\LaravelData\Data;

/**
 * Validate thật nằm ở HeritageSiteAdminController::validated() — DTO chỉ hydrate, cùng nguyên
 * tắc OcopProductData.
 */
class HeritageSiteData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $slug = null,
        public readonly string $heritage_type = 'historical_monument',
        public readonly string $rank = 'unranked',
        public readonly ?string $era = null,
        public readonly ?string $description = null,

        public readonly ?string $province_code = null,
        public readonly ?string $ward_code = null,
        public readonly ?string $address = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,

        /**
         * spec/Media_Library_Technical_Specification.md §8 — UUID media FilePond (collection
         * `cover`) chờ gắn vào di tích vừa tạo — CHỈ dùng ở luồng tạo mới. Form sửa gắn ảnh
         * thẳng qua context header.
         */
        public readonly ?string $cover_media_uuid = null,

        public readonly string $visiting_status = 'unknown',
        public readonly string $status = 'draft',
        public readonly bool $is_featured = false,
        public readonly int $sort_order = 0,
    ) {}
}
