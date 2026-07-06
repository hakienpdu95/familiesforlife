<?php

namespace Modules\Deployment\Data;

use Illuminate\Validation\Rule;
use Modules\Deployment\Models\DeploymentTarget;
use Spatie\LaravelData\Data;

class AssignIssueOwnerData extends Data
{
    public function __construct(
        public readonly ?int $owner_id,
    ) {}

    public static function rules(): array
    {
        // Ràng buộc owner phải thuộc đúng tổ chức đang triển khai của target chứa issue này.
        // withoutTenant() tường minh — quan hệ target() sẽ bị OrganizationScope của
        // DeploymentTarget chặn âm thầm nếu TenantContext không khớp (xem ghi chú ở
        // AssignChecklistItemData).
        $issue = request()->route('issue');
        $orgId = $issue
            ? DeploymentTarget::withoutTenant()->find($issue->deployment_target_id)?->target_organization_id
            : null;

        return [
            'owner_id' => [
                'nullable', 'integer',
                $orgId
                    ? Rule::exists('users', 'id')->where('organization_id', $orgId)
                    : 'exists:users,id',
            ],
        ];
    }

    public static function messages(): array
    {
        return [
            'owner_id.exists' => 'Người phụ trách không hợp lệ.',
        ];
    }
}
