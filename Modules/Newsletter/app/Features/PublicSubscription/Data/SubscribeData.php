<?php

namespace Modules\Newsletter\Features\PublicSubscription\Data;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/** spec/Newsletter_Technical_Specification.md §0 mục 2 — chỉ thu thập full_name + email. */
class SubscribeData extends Data
{
    public function __construct(
        #[Required, Max(150)]
        public readonly string $full_name,

        #[Required, Email, Max(255)]
        public readonly string $email,
    ) {}
}
