<?php

namespace Modules\Sop\Queries;

use App\Shared\Contracts\QueryInterface;

class ListSopProcessesQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page        = 1,
        public readonly int     $perPage     = 25,
        public readonly string  $sortField   = 'created_at',
        public readonly string  $sortDir     = 'desc',
        public readonly ?string $search      = null,
        public readonly ?string $status      = null,
        public readonly ?string $type        = null,
        public readonly ?int    $departmentId = null,
        public readonly ?int    $branchId    = null,
        public readonly ?int    $ownerId     = null,
    ) {}
}
