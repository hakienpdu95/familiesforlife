<?php
namespace Modules\Customer\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Customer\Events\CustomerConverted;
use Modules\Customer\Events\CustomerCreated;
use Modules\Customer\Events\CustomerUpdated;
use Modules\Customer\Listeners\LogCustomerConverted;
use Modules\Customer\Listeners\LogCustomerCreated;
use Modules\Customer\Listeners\LogCustomerUpdated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        CustomerCreated::class   => [LogCustomerCreated::class],
        CustomerUpdated::class   => [LogCustomerUpdated::class],
        CustomerConverted::class => [LogCustomerConverted::class],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
