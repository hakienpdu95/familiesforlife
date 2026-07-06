<?php

namespace Modules\Department\Queries;

use App\Shared\Contracts\QueryInterface;

class ListDepartmentsQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page      = 1,
        public readonly int     $perPage   = 25,
        public readonly string  $sortField = 'path',
        public readonly string  $sortDir   = 'asc',

        // Text search — name, code (OR)
        public readonly ?string $search   = null,

        // Exact filters
        public readonly ?int    $branchId  = null,
        public readonly ?string $function  = null,
        public readonly ?string $status    = null,
        public readonly ?int    $parentId  = null,

        // Date range on created_at (ISO format YYYY-MM-DD)
        public readonly ?string $dateFrom  = null,
        public readonly ?string $dateTo    = null,
    ) {}
}
