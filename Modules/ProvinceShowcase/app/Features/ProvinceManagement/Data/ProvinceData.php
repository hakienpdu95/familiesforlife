<?php

namespace Modules\ProvinceShowcase\Features\ProvinceManagement\Data;

use Spatie\LaravelData\Data;

class ProvinceData extends Data
{
    public function __construct(
        public readonly string $short_name,
        public readonly ?int $order_column = null,
        public readonly bool $is_active = true,
        public readonly bool $is_featured = false,
    ) {}
}
