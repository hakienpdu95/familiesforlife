<?php

namespace Modules\Deployment\Policies;

use App\Models\User;
use Modules\Deployment\Models\DeploymentTarget;

class DeploymentTargetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DeploymentTarget $target): bool
    {
        return true; // tenant scope already ensures org match
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['CEO', 'Ops', 'System_Admin'])
            || $user->roles->contains(fn ($r) => str_ends_with($r->name, '_pm'));
    }

    public function update(User $user, DeploymentTarget $target): bool
    {
        return $user->hasAnyRole(['CEO', 'Ops', 'System_Admin'])
            || $user->hasRole($target->vertical_code . '_pm');
    }

    /**
     * Tick checklist là việc của người thực địa (surveyor/data_entry/data_ops), không chỉ PM —
     * tách riêng khỏi update() vì update() còn dùng cho sửa target (đổi PM, ghi chú...).
     */
    public function toggleChecklist(User $user, DeploymentTarget $target): bool
    {
        if ($user->hasAnyRole(['CEO', 'Ops', 'System_Admin'])) {
            return true;
        }

        $fieldRoleSuffixes = ['pm', 'surveyor', 'data_entry', 'data_ops'];

        return $user->roles->contains(
            fn ($r) => in_array($r->name, array_map(
                fn ($suffix) => $target->vertical_code . '_' . $suffix,
                $fieldRoleSuffixes
            ))
        );
    }

    public function advance(User $user, DeploymentTarget $target): bool
    {
        return $user->hasAnyRole(['CEO', 'System_Admin'])
            || $user->hasRole($target->vertical_code . '_pm');
    }
}
