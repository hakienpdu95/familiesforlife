<?php

namespace Modules\Subscription\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Organization\Events\OrganizationCreated;
use Modules\Subscription\Features\Payment\Events\InvoicePaid;
use Modules\Subscription\Features\Payment\Listeners\RenewSubscriptionOnInvoicePaid;
use Modules\Subscription\Features\Subscribe\Events\SubscriptionExpired;
use Modules\Subscription\Features\Subscribe\Events\SubscriptionExpiring;
use Modules\Subscription\Features\Subscribe\Listeners\AutoSubscribeOnOrgCreated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrganizationCreated::class => [
            AutoSubscribeOnOrgCreated::class,
        ],
        InvoicePaid::class => [
            RenewSubscriptionOnInvoicePaid::class,
        ],
        // Fired by ProcessExpiringSubscriptionsCommand — extend here to add more listeners
        SubscriptionExpired::class  => [],
        SubscriptionExpiring::class => [],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
