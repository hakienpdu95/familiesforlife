<?php
namespace Modules\Customer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Customer\Models\Customer;

class CustomerCreated
{
    use Dispatchable, SerializesModels;
    public function __construct(public readonly Customer $customer) {}
}
