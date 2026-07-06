<?php

namespace Modules\JobTitle\Data\Requests;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Validation\Rule;
use Modules\JobTitle\Enums\JobTitleCategory;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreJobTitleData extends Data
{
    public function __construct(
        public readonly int $organization_id,

        #[Required, StringType, Max(255)]
        public readonly string $name,

        #[Required, StringType, Max(50)]
        public readonly string $code,

        public readonly JobTitleCategory $category,

        #[Required, Min(1), Max(20)]
        public readonly int $level,

        #[Nullable, StringType]
        public readonly ?string $description,

        #[Nullable, BooleanType]
        public readonly bool $is_active = true,
    ) {}

    public static function rules(): array
    {
        $orgId = TenantContext::getOrganizationId();

        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('job_titles', 'code')
                    ->where('organization_id', $orgId),
            ],
            'level' => ['required', 'integer', 'between:1,20'],
        ];
    }
}
