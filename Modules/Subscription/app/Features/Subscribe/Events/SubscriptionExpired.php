<?php

namespace Modules\Subscription\Features\Subscribe\Events;

use App\Shared\Tenancy\Models\Organization;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Laravelcm\Subscriptions\Models\Subscription;

class SubscriptionExpired
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Organization  $organization,
        public readonly Subscription  $subscription,
    ) {}
}
