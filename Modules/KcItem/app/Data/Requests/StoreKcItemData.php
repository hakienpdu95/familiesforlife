<?php

namespace Modules\KcItem\Data\Requests;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreKcItemData extends Data
{
    public function __construct(
        #[Required]
        public readonly int $organization_id,

        #[Required, StringType, Max(300)]
        public readonly string $title,

        #[Required, StringType, Max(320)]
        public readonly string $slug,

        #[Required]
        public readonly int $category_id,

        #[Required, StringType]
        public readonly string $type,

        #[Nullable, StringType]
        public readonly string $visibility = 'internal',

        #[Nullable, StringType]
        public readonly ?string $summary = null,

        #[Nullable, StringType]
        public readonly ?string $content = null,

        #[Nullable, StringType, Max(5)]
        public readonly string $language = 'vi',

        #[Nullable, StringType, Max(10)]
        public readonly ?string $domain_code = null,

        public readonly ?int $difficulty = null,

        public readonly bool $is_featured = false,

        public readonly bool $is_pinned = false,

        public readonly ?string $effective_date = null,

        public readonly ?string $expired_date = null,

        public readonly array $tags = [],
    ) {}

    public static function rules(): array
    {
        $orgId = TenantContext::getOrganizationId();

        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'domain_code'     => ['nullable', 'string', Rule::in(['D1','D2','D3','D4','D5','D6'])],
            'difficulty'      => ['nullable', 'integer', Rule::in([1, 2, 3])],
            'slug' => [
                'required', 'string', 'max:320', 'regex:/^[a-z0-9\-]+$/',
                Rule::unique('kc_items', 'slug')
                    ->where('organization_id', $orgId)
                    ->whereNull('deleted_at'),
            ],
            'category_id' => ['required', 'integer', 'exists:kc_categories,id'],
            'type'        => ['required', 'string', Rule::in(['document', 'sop', 'video', 'form', 'faq', 'case_study', 'policy'])],
            'visibility'  => ['nullable', 'string', Rule::in(['public', 'internal', 'restricted', 'private'])],
            'effective_date' => ['nullable', 'date'],
            'expired_date'   => ['nullable', 'date', 'after_or_equal:effective_date'],
            'tags'           => ['nullable', 'array'],
            'tags.*'         => ['integer', 'exists:kc_tags,id'],
        ];
    }
}
