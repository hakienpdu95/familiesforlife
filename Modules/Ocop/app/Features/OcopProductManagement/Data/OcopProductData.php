<?php

namespace Modules\Ocop\Features\OcopProductManagement\Data;

use Spatie\LaravelData\Data;

/**
 * Validate thật nằm ở OcopProductAdminController::validated() — DTO chỉ hydrate, cùng nguyên
 * tắc BannerData/ArticleData.
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

        // spec/Heritage_Technical_Specification.md §8.2 — làng nghề/di tích liên quan, tuỳ chọn.
        public readonly ?int $heritage_site_id = null,

        /**
         * spec/Media_Library_Technical_Specification.md §8 — UUID media FilePond (collection
         * `cover`) chờ gắn vào sản phẩm vừa tạo — CHỈ dùng ở luồng tạo mới (create form, chưa có
         * product.id để attach trực tiếp). Form sửa gắn ảnh thẳng qua context header.
         */
        public readonly ?string $cover_media_uuid = null,

        public readonly ?string $purchase_url = null,
        public readonly string $status = 'draft',
        public readonly bool $is_featured = false,
        public readonly int $sort_order = 0,
    ) {}
}
