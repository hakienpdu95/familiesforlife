<?php

namespace Modules\Organization\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class OrganizationSettingsData extends Data
{
    public function __construct(
        /** @var array<string, mixed> */
        #[Required, ArrayType]
        public readonly array $settings,
    ) {}
}
