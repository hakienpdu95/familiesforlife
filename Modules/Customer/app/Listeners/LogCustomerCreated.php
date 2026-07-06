<?php
namespace Modules\Customer\Listeners;

use Modules\Customer\Events\CustomerCreated;

class LogCustomerCreated
{
    public function handle(CustomerCreated $event): void
    {
        activity()
            ->performedOn($event->customer)
            ->log('customer.created');
    }
}
