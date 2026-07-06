<?php

namespace Modules\Branch\Policies;

use App\Models\User;
use Modules\Branch\Models\Branch;

/**
 * Authorization policy cho Branch resource.
 * Super-admin bypass toàn bộ qua Gate::before() trong AppServiceProvider.
 */
class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'CEO', 'Viewer', 'Ops', 'Marketing']);
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'CEO', 'Viewer', 'Ops', 'Marketing']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR']);
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR']);
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->hasRole('System_Admin');
    }
}
