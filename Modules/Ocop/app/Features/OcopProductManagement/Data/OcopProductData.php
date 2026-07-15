<?php

namespace Modules\Ocop\Features\OcopProductManagement\Data;

use Spatie\LaravelData\Data;

/**
 * Validate thật nằm ở OcopProductAdminController::validated() — DTO chỉ hydrate, cùng nguyên
 * tắc BannerData/ArticleData. image_* chỉ khác null khi request thực sự có ảnh mới (xem
 * StoreOcopProductImageAction + OcopProductAdminController::update()).
 */
class OcopProductData extends Data
{
    public function __construct(
        public readonly int $category_id,
        public readonly string $name,
        public readonly int $star_rating,
        public readonly ?string $description = null,

        public readonly ?string $province_code = null,
        public readonly ?string $ward_code = null,
        public readonly ?string $producer_name = null,
        public readonly ?string $producer_address = null,

        public readonly ?string $image_path = null,
        public readonly ?int $image_width = null,
        public readonly ?int $image_height = null,
        public readonly ?int $image_size_bytes = null,

        public readonly ?string $purchase_url = null,
        public readonly string $status = 'draft',
        public readonly bool $is_featured = false,
        public readonly int $sort_order = 0,
    ) {}
}
