<?php

namespace Modules\OrgChart\Policies;

use App\Models\User;
use Modules\OrgChart\Models\OrgChartConfig;

class OrgChartConfigPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'CEO', 'HR', 'Ops', 'Sales', 'Marketing', 'Viewer']);
    }

    public function view(User $user, OrgChartConfig $config): bool
    {
        return $user->hasAnyRole(['System_Admin', 'CEO', 'HR', 'Ops', 'Sales', 'Marketing', 'Viewer']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'CEO', 'HR', 'Ops']);
    }

    public function update(User $user, OrgChartConfig $config): bool
    {
        return $user->hasAnyRole(['System_Admin', 'CEO', 'HR', 'Ops']);
    }

    public function delete(User $user, OrgChartConfig $config): bool
    {
        return $user->hasAnyRole(['System_Admin', 'CEO']);
    }
}
