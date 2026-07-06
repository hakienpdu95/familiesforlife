<?php

namespace Modules\KcCategory\Policies;

use App\Models\User;
use Modules\KcCategory\Models\KcCategory;

class KcCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'CEO', 'Ops', 'HR', 'AI_Operator', 'Sales', 'Marketing', 'Viewer']);
    }

    public function view(User $user, KcCategory $kcCategory): bool
    {
        return $user->hasAnyRole(['System_Admin', 'CEO', 'Ops', 'HR', 'AI_Operator', 'Sales', 'Marketing', 'Viewer']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'Ops', 'AI_Operator']);
    }

    public function update(User $user, KcCategory $kcCategory): bool
    {
        return $user->hasAnyRole(['System_Admin', 'Ops', 'AI_Operator']);
    }

    public function delete(User $user, KcCategory $kcCategory): bool
    {
        return $user->hasRole('System_Admin');
    }
}
