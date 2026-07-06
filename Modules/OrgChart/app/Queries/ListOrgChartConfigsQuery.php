<?php

namespace Modules\OrgChart\Queries;

use App\Shared\Contracts\QueryInterface;

class ListOrgChartConfigsQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page      = 1,
        public readonly int     $perPage   = 25,
        public readonly string  $sortField = 'is_default',
        public readonly string  $sortDir   = 'desc',
        public readonly ?string $search    = null,
        public readonly ?string $viewType  = null,
        public readonly ?string $groupBy   = null,
    ) {}
}
