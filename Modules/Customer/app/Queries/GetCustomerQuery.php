<?php
namespace Modules\Customer\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\Customer\Models\Customer;

class GetCustomerQuery implements QueryInterface
{
    public function __construct(public readonly Customer $customer) {}
}
