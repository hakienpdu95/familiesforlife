<?php

namespace Modules\KcItem\Policies;

use App\Models\User;
use Modules\KcItem\Models\KcTag;

class KcTagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'CEO', 'Ops', 'HR', 'AI_Operator', 'Sales', 'Marketing', 'Viewer']);
    }

    public function view(User $user, KcTag $kcTag): bool
    {
        return $user->hasAnyRole(['System_Admin', 'CEO', 'Ops', 'HR', 'AI_Operator', 'Sales', 'Marketing', 'Viewer']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'Ops', 'AI_Operator', 'HR', 'Marketing']);
    }

    public function update(User $user, KcTag $kcTag): bool
    {
        return $user->hasAnyRole(['System_Admin', 'Ops', 'AI_Operator', 'HR', 'Marketing']);
    }

    public function delete(User $user, KcTag $kcTag): bool
    {
        return $user->hasAnyRole(['System_Admin', 'Ops', 'AI_Operator']);
    }
}
