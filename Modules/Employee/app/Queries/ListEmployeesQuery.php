<?php

namespace Modules\Employee\Queries;

use App\Shared\Contracts\QueryInterface;

class ListEmployeesQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page           = 1,
        public readonly int     $perPage        = 25,
        public readonly string  $sortField      = 'full_name',
        public readonly string  $sortDir        = 'asc',

        // Text search — full_name, employee_code, email, phone (OR)
        public readonly ?string $search         = null,

        // Exact filters
        public readonly ?string $status         = null,
        public readonly ?string $employmentType = null,
        public readonly ?int    $branchId       = null,
        public readonly ?int    $departmentId   = null,
        public readonly ?int    $jobTitleId     = null,

        // Date range on hired_at
        public readonly ?string $dateFrom       = null,
        public readonly ?string $dateTo         = null,
    ) {}
}
