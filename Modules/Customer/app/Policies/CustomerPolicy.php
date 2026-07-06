<?php
namespace Modules\Customer\Policies;

use App\Enums\PermissionEnum as P;
use App\Models\User;
use Modules\Customer\Models\Customer;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(P::CUSTOMERS_VIEW_ALL->value)
            || $user->can(P::CUSTOMERS_VIEW_ASSIGNED->value);
    }

    public function view(User $user, Customer $customer): bool
    {
        if ($user->can(P::CUSTOMERS_VIEW_ALL->value)) return true;

        if ($user->can(P::CUSTOMERS_VIEW_ASSIGNED->value)) {
            return (int) $customer->assigned_to === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can(P::CUSTOMERS_CREATE->value);
    }

    public function update(User $user, Customer $customer): bool
    {
        if (! $user->can(P::CUSTOMERS_EDIT->value)) return false;
        // SALES chỉ edit record assigned cho mình
        if (! $user->can(P::CUSTOMERS_VIEW_ALL->value)) {
            return (int) $customer->assigned_to === $user->id;
        }
        return true;
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->can(P::CUSTOMERS_DELETE->value);
    }

    public function export(User $user): bool
    {
        return $user->can(P::CUSTOMERS_EXPORT->value);
    }

    public function config(User $user): bool
    {
        return $user->can(P::CUSTOMERS_CONFIG->value);
    }
}
