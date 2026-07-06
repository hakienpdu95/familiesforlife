<?php

namespace Modules\LeadSource\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Integer;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class CreateSourceData extends Data
{
    public static function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
        ];
    }

    public function __construct(
        public readonly int $organization_id,

        #[Required, StringType, Max(32)]
        public readonly string $code,

        #[Required, StringType, Max(64)]
        public readonly string $label,

        #[Nullable, StringType, Max(64)]
        public readonly ?string $icon = null,

        #[Nullable, StringType, Max(16)]
        public readonly ?string $color = null,

        #[Required, Integer, Min(0), Max(255)]
        public readonly int $sort_order = 0,
    ) {}
}
