<?php

namespace Modules\OcopRubric\Features\RubricAuthoring\Data;

use Spatie\LaravelData\Data;

class RubricVersionData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly int $product_group_id,
        public readonly int $version_no,
        public readonly string $status,
        public readonly float $total_max_score,
        public readonly ?string $effective_from,
        public readonly ?string $effective_to,
    ) {}
}
