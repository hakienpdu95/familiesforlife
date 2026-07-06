<?php

namespace Modules\LeadSource\Queries;

use App\Shared\Contracts\QueryInterface;

class ListSourcesQuery implements QueryInterface
{
    public function __construct(
        public readonly int  $orgId,
        public readonly bool $activeOnly = true,
    ) {}
}
