<?php

namespace Modules\Auth\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class RegisterData extends Data
{
    public function __construct(
        #[Required, Max(255)]
        public readonly string $organization_name,

        #[Required, Max(255)]
        public readonly string $name,

        #[Required, Email, Max(255)]
        public readonly string $email,

        #[Required, Min(8)]
        public readonly string $password,
    ) {}
}
