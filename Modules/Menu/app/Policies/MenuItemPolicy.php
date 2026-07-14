<?php

namespace Modules\Menu\Policies;

use App\Models\User;
use Modules\Menu\Models\MenuItem;

class MenuItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('menu.manage');
    }

    public function view(User $user, MenuItem $menuItem): bool
    {
        return $user->can('menu.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('menu.manage');
    }

    public function update(User $user, MenuItem $menuItem): bool
    {
        return $user->can('menu.manage');
    }

    public function delete(User $user, MenuItem $menuItem): bool
    {
        return $user->can('menu.manage');
    }
}
