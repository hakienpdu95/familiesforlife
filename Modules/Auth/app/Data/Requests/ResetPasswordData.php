<?php

namespace Modules\Auth\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class ResetPasswordData extends Data
{
    public function __construct(
        #[Required, Email, Max(255)]
        public readonly string $email,

        #[Required]
        public readonly string $token,

        #[Required, Min(8)]
        public readonly string $password,
    ) {}
}
