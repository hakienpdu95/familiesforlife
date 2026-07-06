<?php

namespace Modules\Project\Queries;

use App\Shared\Contracts\QueryInterface;

class ListProjectsQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page        = 1,
        public readonly int     $perPage     = 25,
        public readonly string  $sortField   = 'created_at',
        public readonly string  $sortDir     = 'desc',

        // Text search — name, code, description (OR)
        public readonly ?string $search      = null,

        // Exact filters
        public readonly ?string $status      = null,
        public readonly ?string $priority    = null,
        public readonly ?string $category    = null,
        public readonly ?int    $branchId    = null,
        public readonly ?int    $departmentId= null,
        public readonly ?int    $ownerId     = null,

        // Date range on start_date
        public readonly ?string $dateFrom    = null,
        public readonly ?string $dateTo      = null,
    ) {}
}
