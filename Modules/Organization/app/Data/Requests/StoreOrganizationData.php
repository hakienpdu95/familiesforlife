<?php

namespace Modules\Organization\Data\Requests;

use App\Shared\Tenancy\Enums\OrganizationStatus;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;

class StoreOrganizationData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $name,

        #[Nullable, StringType, Max(255), Regex('/^[a-z0-9\-]+$/'), Unique('organizations', 'slug')]
        public readonly ?string $slug,

        public readonly OrganizationStatus $status,

        #[Required, StringType, Max(20)]
        public readonly string $tax_code,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $phone,

        #[Nullable, Email, Max(255)]
        public readonly ?string $email,

        #[Nullable, Url, Max(255)]
        public readonly ?string $website,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $industry,

        #[Nullable, StringType]
        public readonly ?string $description,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $logo_path,

        #[Required, StringType, Size(2), Exists('provinces', 'province_code')]
        public readonly string $province_code,

        #[Required, StringType, Size(5), Exists('wards', 'ward_code')]
        public readonly string $ward_code,

        #[Nullable, StringType]
        public readonly ?string $full_address = null,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $address = null,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $city = null,

        #[Nullable, StringType, Size(2)]
        public readonly ?string $country = 'VN',

        #[Nullable, StringType, Max(20)]
        public readonly ?string $postal_code = null,
    ) {}
}
