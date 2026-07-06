<?php

namespace Modules\OcopRubric\Features\ProductRegistry\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class ProductData extends Data
{
    public function __construct(
        #[Required]
        public readonly int $product_group_id,

        #[Required, Max(255)]
        public readonly string $name,

        #[Max(60)]
        public readonly ?string $product_code = null,
    ) {}
}
