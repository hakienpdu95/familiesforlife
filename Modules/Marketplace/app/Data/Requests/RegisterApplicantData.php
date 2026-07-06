<?php
namespace Modules\Marketplace\Data\Requests;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Confirmed;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class RegisterApplicantData extends Data
{
    public function __construct(
        #[Required, Email, Max(150)]
        public readonly string $email,

        #[Required, StringType, Min(8), Confirmed]
        public readonly string $password,

        #[Required, StringType, Max(150)]
        public readonly string $display_name,

        #[Nullable, StringType, Max(150)]
        public readonly ?string $location = null,

        #[Nullable, StringType, Max(200)]
        public readonly ?string $headline = null,
    ) {}
}
