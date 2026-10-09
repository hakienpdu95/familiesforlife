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
        public readonly string $name,
        public readonly int $star_rating,
        public readonly ?int $category_id = null,
        public readonly ?string $description = null,

        public readonly ?string $story = null,
        public readonly ?string $origin = null,
        public readonly ?string $production_date = null,
        public readonly ?string $shelf_life = null,
        public readonly ?string $ingredients = null,
        public readonly ?string $usage_instructions = null,
        public readonly ?string $storage_instructions = null,

        public readonly ?int $ocop_subject_id = null,

        // spec/Heritage_Technical_Specification.md §8.2 — làng nghề/di tích liên quan, tuỳ chọn.
        public readonly ?int $heritage_site_id = null,

        /**
         * spec/Media_Library_Technical_Specification.md §8 — UUID media FilePond (collection
         * `ocop_gallery`) chờ gắn vào sản phẩm vừa tạo — CHỈ dùng ở luồng tạo mới (create form,
         * chưa có product.id để attach trực tiếp). Form sửa gắn ảnh thẳng qua context header.
         */
        public readonly array $media_uuids = [],

        /** UUID ảnh hiện có bị editor đánh dấu xoá — CHỈ dùng ở form sửa. */
        public readonly array $remove_media_uuids = [],

        public readonly ?string $purchase_url = null,
        public readonly string $status = 'draft',
        public readonly bool $is_featured = false,
        public readonly int $sort_order = 0,
    ) {}
}
