<?php

namespace Modules\Organization\Policies;

use App\Models\User;
use Modules\Organization\Models\Organization;

/**
 * Authorization policy cho Organization resource.
 *
 * Chỉ System_Admin và super-admin mới được phép CRUD qua backend panel.
 * Owner của org có thể edit/view org của mình (dùng cho frontend settings).
 */
class OrganizationPolicy
{
    /** Super-admin bypass toàn bộ — được xử lý bởi Gate::before() trong AppServiceProvider. */

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super-admin', 'System_Admin']);
    }

    public function view(User $user, Organization $organization): bool
    {
        if ($user->hasRole(['super-admin', 'System_Admin'])) {
            return true;
        }

        // Owner của tổ chức được xem thông tin của tổ chức mình
        return $organization->owner_id === $user->id
            || $organization->members()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['super-admin', 'System_Admin']);
    }

    public function update(User $user, Organization $organization): bool
    {
        if ($user->hasRole(['super-admin', 'System_Admin'])) {
            return true;
        }

        // Owner có thể edit tổ chức của mình
        return $organization->owner_id === $user->id;
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->hasRole(['super-admin', 'System_Admin']);
    }
}
