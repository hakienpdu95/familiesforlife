<?php

namespace Modules\EntityComparison\Policies;

use App\Models\User;
use Modules\EntityComparison\Models\Criterion;

/** spec/Entity_Comparison_Module_Technical_Spec.md §10 — 1 permission thô cho mọi action CRUD, đúng mẫu OcopProductPolicy. */
class CriterionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('entity_comparison.manage');
    }

    public function view(User $user, Criterion $criterion): bool
    {
        return $user->can('entity_comparison.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('entity_comparison.manage');
    }

    public function update(User $user, Criterion $criterion): bool
    {
        return $user->can('entity_comparison.manage');
    }

    public function delete(User $user, Criterion $criterion): bool
    {
        return $user->can('entity_comparison.manage');
    }
}
