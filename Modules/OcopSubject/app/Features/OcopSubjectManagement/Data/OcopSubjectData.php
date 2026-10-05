<?php

namespace Modules\OcopSubject\Features\OcopSubjectManagement\Data;

use Spatie\LaravelData\Data;

class OcopSubjectData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $tax_code,
        public readonly string $organization_type,
        public readonly string $legal_representative,
        public readonly string $address,
        public readonly string $province_code,
        public readonly string $ward_code,
        public readonly string $gps_coordinates,

        public readonly ?string $name_en = null,
        public readonly ?string $position = null,
        public readonly ?string $factory_code = null,
        public readonly bool $is_food_business = false,

        public readonly ?int $ocop_star = null,
        public readonly ?string $ocop_cert_expiry = null,

        public readonly ?string $hotline = null,
        public readonly ?string $email = null,
        public readonly ?string $website = null,
        public readonly ?string $story = null,
        public readonly bool $is_active = true,

        public readonly array $media_uuids = [],

        public readonly array $remove_media_uuids = [],
    ) {}
}
