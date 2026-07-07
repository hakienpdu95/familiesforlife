<?php

namespace Modules\Product\Features\CatalogManagement\Data;

use Modules\Product\Enums\ProductStatus;
use Modules\Product\Enums\ProductType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class ProductData extends Data
{
    public function __construct(
        #[Required, Max(250)]
        public readonly string $name,

        public readonly ProductType $type = ProductType::Physical,
        public readonly ProductStatus $status = ProductStatus::Active,
        public readonly ?int $category_id = null,
        public readonly ?string $sku = null,
        public readonly ?string $short_description = null,
        public readonly ?string $description = null,
        public readonly ?float $price = null,
        public readonly ?string $price_label = null,
        public readonly string $currency = 'VND',
        public readonly ?string $cover_image_url = null,
        public readonly ?string $shopee_url = null,
        public readonly ?string $tiktok_url = null,
        public readonly ?string $supplier_url = null,
        public readonly ?string $supplier_homepage_url = null,
        public readonly bool $is_featured = false,
        public readonly int $sort_order = 0,
    ) {}
}
