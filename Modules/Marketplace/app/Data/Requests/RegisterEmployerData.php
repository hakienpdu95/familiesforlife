<?php

namespace Modules\Marketplace\Data\Requests;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Email;

class RegisterEmployerData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $company_name,

        #[Required, Email, Max(150)]
        public readonly string $hr_email,

        #[Required, StringType, Min(8), Max(255)]
        public readonly string $password,

        #[Required, StringType, Max(255)]
        public readonly string $contact_name,

        #[Nullable, StringType, Max(300)]
        public readonly ?string $website,

        #[Nullable, StringType]
        public readonly ?string $company_description,

        #[Nullable, StringType, Max(300)]
        public readonly ?string $listing_title,

        #[Nullable, StringType]
        public readonly ?string $listing_description,

        #[Nullable, StringType, Max(200)]
        public readonly ?string $listing_location,
    ) {}
}
