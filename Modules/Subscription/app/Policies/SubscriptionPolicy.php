<?php

namespace Modules\Subscription\Policies;

use App\Enums\PermissionEnum;
use App\Models\User;
use Modules\Subscription\Models\SubscriptionInvoice;

class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnum::SUBSCRIPTION_VIEW->value);
    }

    public function view(User $user, SubscriptionInvoice $invoice): bool
    {
        return $user->hasPermissionTo(PermissionEnum::SUBSCRIPTION_BILLING->value)
            && ($user->organization_id === $invoice->organization_id
                || $user->hasPermissionTo(PermissionEnum::SUBSCRIPTION_ADMIN->value));
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnum::SUBSCRIPTION_MANAGE->value);
    }

    public function admin(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnum::SUBSCRIPTION_ADMIN->value);
    }
}
