<?php
namespace Modules\Customer\Data\Requests;

use Illuminate\Validation\Rule;
use Modules\Customer\Enums\CustomerLifecycleStage;
use Modules\Customer\Enums\CustomerType;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;

class StoreCustomerData extends Data
{
    public function __construct(
        #[Nullable, IntegerType]
        public readonly ?int    $organization_id,

        #[Required, IntegerType]
        public readonly int     $customer_type,

        // display_name không có input riêng trên form — được tự suy ra ở
        // CreateCustomerAction từ first_name+last_name (cá nhân) hoặc
        // company_name (doanh nghiệp).
        #[Nullable, StringType, Max(255)]
        public readonly ?string $display_name = null,

        #[Nullable, Email, Max(255)]
        public readonly ?string $primary_email = null,

        #[Nullable, StringType, Max(30)]
        public readonly ?string $primary_phone = null,

        #[Nullable, IntegerType]
        public readonly ?int    $lifecycle_stage = null,

        #[Nullable, IntegerType]
        public readonly ?int    $source_id = null,

        #[Nullable, IntegerType]
        public readonly ?int    $assigned_to = null,

        // Individual
        #[Nullable, StringType, Max(100)]
        public readonly ?string $first_name = null,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $last_name = null,

        // 'M'/'F'/'O' — khớp với option value trong _form.blade.php và so sánh
        // ở show.blade.php ($customer->gender === 'M'), không phải số.
        #[Nullable, StringType, Max(1)]
        public readonly ?string $gender = null,

        #[Nullable, Date]
        public readonly ?string $date_of_birth = null,

        // Business
        #[Nullable, StringType, Max(255)]
        public readonly ?string $company_name = null,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $tax_code = null,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $industry = null,

        #[Nullable, IntegerType]
        public readonly ?int    $company_size = null,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $representative_name = null,

        #[Nullable, StringType, Max(150)]
        public readonly ?string $representative_title = null,

        // Common
        #[Nullable, StringType, Max(10)]
        public readonly ?string $province_code = null,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $full_address = null,

        #[Nullable, Url, Max(500)]
        public readonly ?string $website = null,

        #[Nullable, StringType]
        public readonly ?string $description = null,

        #[Nullable, ArrayType]
        public readonly ?array  $tag_ids = null,

        #[Nullable, ArrayType]
        public readonly ?array  $meta = null,
    ) {}

    public static function rules(): array
    {
        return [
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'customer_type'   => ['required', 'integer', Rule::in(array_column(CustomerType::cases(), 'value'))],
            'gender'          => ['nullable', 'string', Rule::in(['M', 'F', 'O'])],
            'lifecycle_stage' => ['nullable', 'integer', Rule::in(array_column(CustomerLifecycleStage::cases(), 'value'))],
            'source_id'       => ['nullable', 'integer', 'exists:lead_sources,id'],
            'assigned_to'     => ['nullable', 'integer', 'exists:users,id'],
            'tag_ids'         => ['nullable', 'array'],
            'tag_ids.*'       => ['integer', 'min:1'],
            'meta'            => ['nullable', 'array'],
        ];
    }
}
