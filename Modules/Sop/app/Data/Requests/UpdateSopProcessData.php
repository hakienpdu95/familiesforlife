<?php

namespace Modules\Sop\Data\Requests;

use Illuminate\Validation\Rule;
use Modules\Sop\Enums\SopType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateSopProcessData extends Data
{
    public function __construct(
        // Injected from existing SOP in controller — not from form field
        public readonly int $organization_id,

        #[Required, StringType, Max(300)]
        public readonly string $title,

        #[Nullable, StringType]
        public readonly ?string $description,

        public readonly SopType $type,

        #[Required]
        public readonly int $owner_id,

        #[Nullable]
        public readonly ?int $department_id,

        #[Nullable]
        public readonly ?int $branch_id,

        #[Nullable, StringType]
        public readonly ?string $effective_date,

        #[Nullable, StringType]
        public readonly ?string $expired_date,
    ) {}

    public static function rules(): array
    {
        $orgId = (int) request('organization_id');

        return [
            'owner_id'      => ['required', 'integer', Rule::exists('users', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('organization_id', $orgId)],
            'branch_id'     => ['nullable', 'integer', Rule::exists('branches', 'id')->where('organization_id', $orgId)],
            'effective_date' => ['nullable', 'date'],
            'expired_date'   => ['nullable', 'date', 'after_or_equal:effective_date'],
        ];
    }

    public static function messages(): array
    {
        return [
            'title.required'              => 'Tên quy trình là bắt buộc.',
            'title.max'                   => 'Tên quy trình không được vượt quá 300 ký tự.',
            'owner_id.required'           => 'Người phụ trách là bắt buộc.',
            'owner_id.exists'             => 'Người phụ trách không tồn tại trong hệ thống.',
            'department_id.exists'        => 'Phòng ban không hợp lệ.',
            'branch_id.exists'            => 'Chi nhánh không hợp lệ.',
            'effective_date.date'         => 'Ngày hiệu lực không đúng định dạng.',
            'expired_date.date'           => 'Ngày hết hạn không đúng định dạng.',
            'expired_date.after_or_equal' => 'Ngày hết hạn phải sau hoặc bằng ngày hiệu lực.',
        ];
    }
}
