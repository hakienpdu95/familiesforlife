<?php

namespace Modules\Deployment\Data;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class AssignTargetEmployeeData extends Data
{
    public function __construct(
        public readonly ?int $assigned_employee_id,
    ) {}

    public static function rules(): array
    {
        // Ràng buộc nhân viên phải thuộc đúng tổ chức đang được triển khai của target —
        // chặn tự ghép request gán nhầm nhân viên tổ chức khác.
        $target = request()->route('target');
        $orgId  = $target?->target_organization_id;

        return [
            'assigned_employee_id' => [
                'nullable', 'integer',
                $orgId
                    ? Rule::exists('employees', 'id')->where('organization_id', $orgId)
                    : 'exists:employees,id',
            ],
        ];
    }

    public static function messages(): array
    {
        return [
            'assigned_employee_id.exists' => 'Nhân viên không hợp lệ.',
        ];
    }
}
