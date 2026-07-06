<?php
namespace Modules\Customer\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Customer\Models\Customer;

class SyncCustomerTagsAction
{
    use AsAction;

    public function handle(Customer $customer, array $tagIds): void
    {
        $customer->tags()->sync(array_filter(array_map('intval', $tagIds)));
    }
}
