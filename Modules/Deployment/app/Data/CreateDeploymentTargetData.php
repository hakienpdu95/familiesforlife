<?php

namespace Modules\Deployment\Data;

use Spatie\LaravelData\Data;

class CreateDeploymentTargetData extends Data
{
    public function __construct(
        public readonly int     $project_id,
        public readonly string  $name,
        public readonly ?string $tax_code,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly ?string $province_code,
        public readonly ?string $full_address,
        public readonly ?string $representative_name,
        public readonly ?string $representative_phone,
        public readonly ?int    $assigned_employee_id,
        public readonly ?string $notes,
    ) {}

    public static function rules(): array
    {
        return [
            'project_id'           => ['required', 'integer', 'exists:projects,id'],
            'name'                 => ['required', 'string', 'max:255'],
            'tax_code'             => ['nullable', 'string', 'max:20'],
            'phone'                => ['nullable', 'string', 'max:20'],
            'email'                => ['nullable', 'email', 'max:255'],
            'province_code'        => ['nullable', 'string', 'max:10'],
            'full_address'         => ['nullable', 'string', 'max:500'],
            'representative_name'  => ['nullable', 'string', 'max:255'],
            'representative_phone' => ['nullable', 'string', 'max:20'],
            'assigned_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'notes'                => ['nullable', 'string', 'max:2000'],
        ];
    }

    public static function messages(): array
    {
        return [
            'project_id.required'          => 'Vui lòng chọn dự án.',
            'project_id.exists'            => 'Dự án không hợp lệ.',
            'name.required'                => 'Vui lòng nhập tên tổ chức.',
            'name.max'                     => 'Tên tổ chức không được vượt quá 255 ký tự.',
            'tax_code.max'                 => 'Mã số thuế không được vượt quá 20 ký tự.',
            'phone.max'                    => 'Số điện thoại không được vượt quá 20 ký tự.',
            'email.email'                  => 'Email không đúng định dạng.',
            'email.max'                    => 'Email không được vượt quá 255 ký tự.',
            'full_address.max'             => 'Địa chỉ không được vượt quá 500 ký tự.',
            'representative_name.max'      => 'Tên người đại diện không được vượt quá 255 ký tự.',
            'representative_phone.max'     => 'SĐT người đại diện không được vượt quá 20 ký tự.',
            'assigned_employee_id.exists'  => 'Nhân viên phụ trách không hợp lệ.',
            'notes.max'                    => 'Ghi chú không được vượt quá 2000 ký tự.',
        ];
    }
}
