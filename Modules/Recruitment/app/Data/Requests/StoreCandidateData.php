<?php

namespace Modules\Recruitment\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreCandidateData extends Data
{
    public function __construct(
        #[Required, IntegerType]
        public readonly int $organization_id,

        #[Required, StringType, Max(150)]
        public readonly string $full_name,

        #[Required, StringType, Max(150)]
        public readonly string $email,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $phone,

        #[Nullable, StringType, Max(150)]
        public readonly ?string $current_title,

        #[Nullable, StringType, Max(150)]
        public readonly ?string $current_company,

        #[Nullable]
        public readonly ?int $years_experience,

        #[Nullable]
        public readonly ?string $gender,

        #[Nullable]
        public readonly ?string $date_of_birth,

        #[Nullable]
        public readonly ?string $skills,

        #[Required, StringType]
        public readonly string $source,

        #[Nullable]
        public readonly ?int $referred_by,

        #[Nullable, StringType, Max(300)]
        public readonly ?string $linkedin_url,

        #[Nullable, StringType, Max(300)]
        public readonly ?string $portfolio_url,
    ) {}

    public static function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
        ];
    }
}
