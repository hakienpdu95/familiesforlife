<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Foundation\Vertical\VerticalTemplate;
use App\Models\User;

/**
 * Bản mẫu thư viện (organization_id = null): chỉ System Admin (permission
 * vertical_templates.manage) qua UI dashboard/vertical-templates.
 * Bản instance của tổ chức (organization_id != null): chủ tổ chức hoặc System Admin,
 * quản lý qua OrganizationVerticalController (dashboard/organizations/{organization}/verticals) —
 * builder phase/checklist (VerticalPhaseController/VerticalChecklistItemController) tái dùng
 * chung route/controller cho cả 2 trường hợp nên phải authorize đúng theo chủ sở hữu ở đây.
 */
class VerticalTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::VERTICAL_TEMPLATES_MANAGE->value);
    }

    public function view(User $user, VerticalTemplate $verticalTemplate): bool
    {
        return $this->owns($user, $verticalTemplate);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::VERTICAL_TEMPLATES_MANAGE->value);
    }

    public function update(User $user, VerticalTemplate $verticalTemplate): bool
    {
        return $this->owns($user, $verticalTemplate);
    }

    public function delete(User $user, VerticalTemplate $verticalTemplate): bool
    {
        return $this->owns($user, $verticalTemplate);
    }

    private function owns(User $user, VerticalTemplate $verticalTemplate): bool
    {
        if ($verticalTemplate->organization_id === null) {
            return $user->can(PermissionEnum::VERTICAL_TEMPLATES_MANAGE->value);
        }

        return $user->can('update', $verticalTemplate->organization);
    }
}
