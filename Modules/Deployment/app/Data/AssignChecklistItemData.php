<?php

namespace Modules\Deployment\Data;

use Illuminate\Validation\Rule;
use Modules\Deployment\Models\DeploymentTarget;
use Spatie\LaravelData\Data;

class AssignChecklistItemData extends Data
{
    public function __construct(
        public readonly ?int $assigned_employee_id,
    ) {}

    public static function rules(): array
    {
        // Ràng buộc nhân viên phải thuộc đúng tổ chức đang được triển khai của target chứa
        // mục checklist này — chặn việc tự ghép request gán nhầm nhân viên tổ chức khác
        // (UI đã lọc đúng, nhưng backend trước đây không tự kiểm tra độc lập).
        // Dùng withoutTenant() tường minh thay vì quan hệ target() — quan hệ đó bị chính
        // OrganizationScope của DeploymentTarget chặn khi TenantContext của người request
        // không khớp organization_id (tenant vận hành) của target, âm thầm trả về null và
        // vô hiệu hoá ràng buộc này.
        $item  = request()->route('item');
        $orgId = $item
            ? DeploymentTarget::withoutTenant()->find($item->deployment_target_id)?->target_organization_id
            : null;

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
