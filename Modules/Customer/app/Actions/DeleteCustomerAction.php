<?php
namespace Modules\Customer\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Customer\Models\Customer;

class DeleteCustomerAction
{
    use AsAction;

    public function handle(Customer $customer): void
    {
        $customer->delete();
    }
}
