<?php

namespace Modules\Lead\Queries;

use App\Shared\Contracts\QueryInterface;

class GetLeadSourcesQuery implements QueryInterface
{
    public function __construct(public readonly int $orgId) {}
}
