<?php

namespace Modules\Branch\Data\Requests;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Validation\Rule;
use Modules\Branch\Enums\BranchStatus;
use Modules\Branch\Enums\BranchType;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreBranchData extends Data
{
    public function __construct(
        #[Required]
        public readonly int $organization_id,

        #[Required, StringType, Max(255)]
        public readonly string $name,

        #[Required, StringType, Max(50)]
        public readonly string $code,

        public readonly BranchType $type,

        public readonly BranchStatus $status,

        #[Nullable]
        public readonly ?int $parent_id,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $tax_code,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $phone,

        #[Nullable, Email, Max(255)]
        public readonly ?string $email,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $fax,

        #[Nullable, StringType, Max(2)]
        public readonly ?string $province_code,

        #[Nullable, StringType, Max(5)]
        public readonly ?string $ward_code,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $address,

        #[Nullable]
        public readonly ?float $lat,

        #[Nullable]
        public readonly ?float $lng,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $timezone,

        #[Nullable, StringType, Max(3)]
        public readonly ?string $currency,

        #[Nullable]
        public readonly ?string $opened_at,

        #[Nullable]
        public readonly ?string $closed_at,
    ) {}

    public static function rules(): array
    {
        $orgId = TenantContext::getOrganizationId();

        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('branches', 'code')
                    ->where('organization_id', $orgId)
                    ->whereNull('deleted_at'),
            ],
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('branches', 'id')
                    ->where('organization_id', $orgId)
                    ->whereNull('deleted_at'),
            ],
            'province_code' => ['nullable', 'string', 'exists:provinces,province_code'],
            'ward_code'     => ['nullable', 'string', 'exists:wards,ward_code'],
            'opened_at'     => ['nullable', 'date'],
            'closed_at'     => ['nullable', 'date'],
            'lat'           => ['nullable', 'numeric', 'between:-90,90'],
            'lng'           => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
