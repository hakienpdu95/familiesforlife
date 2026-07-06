<?php

namespace Modules\Lead\Queries;

use App\Shared\Contracts\QueryInterface;
use Carbon\Carbon;

class LeadStatsQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $orgId,
        public readonly ?Carbon $from        = null,
        public readonly ?Carbon $to          = null,
        public readonly ?int    $scopeUserId = null,
    ) {}
}
