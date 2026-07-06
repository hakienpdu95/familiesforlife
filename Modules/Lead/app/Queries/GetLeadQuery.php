<?php

namespace Modules\Lead\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\Lead\Models\Lead;

class GetLeadQuery implements QueryInterface
{
    public function __construct(public readonly Lead $lead) {}
}
