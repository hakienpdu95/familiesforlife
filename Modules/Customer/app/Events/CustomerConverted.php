<?php
namespace Modules\Customer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Customer\Models\Customer;
use Modules\Lead\Models\Lead;

class CustomerConverted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Customer $customer,
        public readonly Lead     $lead,
    ) {}
}
