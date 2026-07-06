<?php

namespace Modules\KcItem\Policies;

use App\Models\User;
use Modules\KcItem\Models\KcItem;

class KcItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'CEO', 'Ops', 'HR', 'AI_Operator', 'Sales', 'Marketing', 'Viewer']);
    }

    public function view(User $user, KcItem $kcItem): bool
    {
        return $user->hasAnyRole(['System_Admin', 'CEO', 'Ops', 'HR', 'AI_Operator', 'Sales', 'Marketing', 'Viewer']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'Ops', 'AI_Operator', 'HR', 'Marketing']);
    }

    public function update(User $user, KcItem $kcItem): bool
    {
        if ($user->hasRole('System_Admin')) {
            return true;
        }

        if ($user->hasAnyRole(['Ops', 'AI_Operator', 'HR', 'Marketing'])) {
            return $kcItem->isEditable();
        }

        return false;
    }

    public function delete(User $user, KcItem $kcItem): bool
    {
        return $user->hasRole('System_Admin');
    }

    public function submit(User $user, KcItem $kcItem): bool
    {
        return $kcItem->canSubmit()
            && $user->hasAnyRole(['System_Admin', 'Ops', 'AI_Operator', 'HR', 'Marketing']);
    }

    public function approve(User $user, KcItem $kcItem): bool
    {
        if (! $kcItem->canApprove()) {
            return false;
        }

        if (! $user->hasAnyRole(['System_Admin', 'Ops', 'AI_Operator'])) {
            return false;
        }

        // Tác giả không được tự duyệt (trừ admin)
        if ($user->id === $kcItem->owner_id && ! $user->hasRole('System_Admin')) {
            return false;
        }

        return true;
    }

    public function reject(User $user, KcItem $kcItem): bool
    {
        return $this->approve($user, $kcItem);
    }

    public function archive(User $user, KcItem $kcItem): bool
    {
        return $user->hasAnyRole(['System_Admin', 'Ops', 'AI_Operator']);
    }

    public function rollback(User $user, KcItem $kcItem): bool
    {
        if ($user->hasRole('System_Admin')) {
            return true;
        }

        if ($user->id === $kcItem->owner_id) {
            return true;
        }

        return $user->hasRole('Ops');
    }
}
