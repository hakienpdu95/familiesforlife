<?php
namespace Modules\Customer\Listeners;

use Modules\Customer\Events\CustomerConverted;

class LogCustomerConverted
{
    public function handle(CustomerConverted $event): void
    {
        activity()
            ->performedOn($event->customer)
            ->withProperties([
                'lead_id'   => $event->lead->id,
                'lead_title' => $event->lead->title ?? $event->lead->contact_name,
            ])
            ->log('Chuyển đổi từ Lead #' . $event->lead->id);
    }
}
