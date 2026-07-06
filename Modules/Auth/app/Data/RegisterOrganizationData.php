<?php

namespace Modules\Auth\Data;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/**
 * DTO: dữ liệu đầu vào khi đăng ký tổ chức mới.
 * Được tạo từ $input của Fortify::createUsersUsing() pipeline.
 */
class RegisterOrganizationData extends Data
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
