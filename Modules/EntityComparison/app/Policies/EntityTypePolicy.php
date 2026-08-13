<?php

namespace Modules\EntityComparison\Policies;

use App\Models\User;
use Modules\EntityComparison\Models\EntityType;

/** spec/Entity_Comparison_Module_Technical_Spec.md §10 — 1 permission thô cho mọi action CRUD, đúng mẫu OcopProductPolicy. */
class EntityTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('entity_comparison.manage');
    }

    public function view(User $user, EntityType $entityType): bool
    {
        return $user->can('entity_comparison.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('entity_comparison.manage');
    }

    public function update(User $user, EntityType $entityType): bool
    {
        return $user->can('entity_comparison.manage');
    }

    public function delete(User $user, EntityType $entityType): bool
    {
        return $user->can('entity_comparison.manage');
    }
}
