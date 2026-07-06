<?php

namespace Modules\Recruitment\Queries;

use App\Shared\Contracts\QueryInterface;

class ListApplicationsQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page         = 1,
        public readonly int     $perPage      = 25,
        public readonly string  $sortField    = 'applied_at',
        public readonly string  $sortDir      = 'desc',
        public readonly ?string $search       = null,
        public readonly ?string $status       = null,
        public readonly ?string $stageId      = null,
        public readonly ?string $jpJobPostId  = null,
        public readonly ?string $assignedTo   = null,
        public readonly ?string $dateFrom     = null,
        public readonly ?string $dateTo       = null,
    ) {}
}
