<?php

namespace Modules\KcItem\Data\Requests;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateKcTagData extends Data
{
    public function __construct(
        #[Required, StringType, Max(80)]
        public readonly string $name,

        #[Required, StringType, Max(90)]
        public readonly string $slug,

        #[Nullable, StringType]
        public readonly ?string $color_hex = null,
    ) {}

    public static function rules(): array
    {
        $orgId     = TenantContext::getOrganizationId();
        $currentId = request()->route('kc_tag')?->id;

        return [
            'slug' => [
                'required', 'string', 'max:90', 'regex:/^[a-z0-9\-]+$/',
                Rule::unique('kc_tags', 'slug')
                    ->where('organization_id', $orgId)
                    ->ignore($currentId),
            ],
            'color_hex' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
