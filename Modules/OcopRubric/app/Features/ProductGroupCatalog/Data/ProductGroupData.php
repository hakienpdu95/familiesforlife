<?php

namespace Modules\OcopRubric\Features\ProductGroupCatalog\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class ProductGroupData extends Data
{
    public function __construct(
        #[Required, Max(60), Regex('/^[a-z0-9\-]+$/')]
        public readonly string $code,

        #[Required, Max(255)]
        public readonly string $name,

        #[Required, Max(10)]
        public readonly string $industry_code,

        #[Required, Max(255)]
        public readonly string $industry_name,

        public readonly ?string $group_label     = null,
        public readonly ?string $managing_agency = null,
        public readonly bool    $requires_sample_product = true,
        public readonly bool    $is_active               = true,
        public readonly int     $sort_order              = 0,
    ) {}
}
