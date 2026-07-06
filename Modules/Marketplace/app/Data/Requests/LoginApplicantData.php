<?php
namespace Modules\Marketplace\Data\Requests;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class LoginApplicantData extends Data
{
    public function __construct(
        #[Required, Email, Max(150)]
        public readonly string $email,

        #[Required, StringType]
        public readonly string $password,

        public readonly bool $remember = false,
    ) {}
}
