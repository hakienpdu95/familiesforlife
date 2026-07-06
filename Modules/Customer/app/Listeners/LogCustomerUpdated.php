<?php
namespace Modules\Customer\Listeners;

use Modules\Customer\Events\CustomerUpdated;

class LogCustomerUpdated
{
    public function handle(CustomerUpdated $event): void
    {
        activity()
            ->performedOn($event->customer)
            ->log('customer.updated');
    }
}
