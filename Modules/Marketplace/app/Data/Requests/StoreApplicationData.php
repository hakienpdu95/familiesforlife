<?php
namespace Modules\Marketplace\Data\Requests;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;

class StoreApplicationData extends Data
{
    public function __construct(
        #[Nullable, StringType]
        public readonly ?string $cover_letter = null,

        #[Nullable]
        public readonly ?float $expected_salary = null,

        #[Nullable]
        public readonly ?string $available_from = null,

        #[Nullable, StringType, Max(300)]
        public readonly ?string $portfolio_url = null,
    ) {}
}
