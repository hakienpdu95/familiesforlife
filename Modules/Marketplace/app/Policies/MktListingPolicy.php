<?php

namespace Modules\Marketplace\Policies;

use App\Models\User;
use Modules\Marketplace\Models\MktListing;

class MktListingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['marketplace.view', 'marketplace.manage']);
    }

    public function view(User $user, MktListing $listing): bool
    {
        return $user->hasAnyPermission(['marketplace.view', 'marketplace.manage'])
            && ($user->organization_id === $listing->org_id || $user->hasPermissionTo('marketplace.manage'));
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('marketplace.create');
    }

    public function update(User $user, MktListing $listing): bool
    {
        return $user->hasPermissionTo('marketplace.edit')
            && $user->organization_id === $listing->org_id;
    }

    public function delete(User $user, MktListing $listing): bool
    {
        return $user->hasPermissionTo('marketplace.manage')
            && $user->organization_id === $listing->org_id;
    }

    public function close(User $user, MktListing $listing): bool
    {
        return $user->hasAnyPermission(['marketplace.edit', 'marketplace.manage'])
            && ($user->organization_id === $listing->org_id || $user->hasPermissionTo('marketplace.manage'));
    }
}
