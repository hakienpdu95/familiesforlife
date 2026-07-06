<?php
namespace Modules\Marketplace\Data\Requests;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Modules\Marketplace\Enums\ApplicantAvailability;

class UpdateApplicantProfileData extends Data
{
    public function __construct(
        #[Nullable, StringType, Max(150)]
        public readonly ?string $display_name = null,

        #[Nullable, StringType, Max(200)]
        public readonly ?string $headline = null,

        #[Nullable, StringType]
        public readonly ?string $bio = null,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $phone = null,

        #[Nullable, StringType, Max(150)]
        public readonly ?string $location = null,

        #[Nullable, StringType, Max(300)]
        public readonly ?string $website_url = null,

        #[Nullable, StringType, Max(300)]
        public readonly ?string $linkedin_url = null,

        #[Nullable]
        public readonly ?int $years_experience = null,

        #[Nullable]
        public readonly ?float $expected_salary_min = null,

        #[Nullable]
        public readonly ?float $expected_salary_max = null,

        public readonly string $salary_currency = 'VND',

        #[Nullable]
        public readonly ?ApplicantAvailability $availability = null,

        public readonly bool $is_profile_public = true,
        public readonly bool $is_email_public = false,
    ) {}
}
