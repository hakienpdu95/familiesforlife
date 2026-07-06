<?php

namespace Modules\OrgChart\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\OrgChart\Models\OrgChartConfig;

class GetOrgChartTreeQuery implements QueryInterface
{
    public function __construct(
        public readonly OrgChartConfig $config,
    ) {}
}
