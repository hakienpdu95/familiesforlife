<?php

namespace Modules\KcCategory\Data\Requests;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateKcCategoryData extends Data
{
    public function __construct(
        #[Required, StringType, Max(150)]
        public readonly string $name,

        #[Required, StringType, Max(160)]
        public readonly string $slug,

        #[Nullable, StringType]
        public readonly ?string $description = null,

        #[Nullable, StringType, Max(80)]
        public readonly ?string $icon = null,

        #[Nullable, StringType]
        public readonly ?string $color_hex = null,

        public readonly ?int $parent_id = null,

        public readonly int $sort_order = 0,

        #[Nullable, BooleanType]
        public readonly bool $is_active = true,
    ) {}

    public static function rules(): array
    {
        $orgId     = TenantContext::getOrganizationId();
        $currentId = request()->route('kc_category')?->id;

        return [
            'slug' => [
                'required', 'string', 'max:160', 'regex:/^[a-z0-9\-]+$/',
                Rule::unique('kc_categories', 'slug')
                    ->where('organization_id', $orgId)
                    ->whereNull('deleted_at')
                    ->ignore($currentId),
            ],
            'parent_id' => ['nullable', 'integer', 'exists:kc_categories,id'],
            'color_hex' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
