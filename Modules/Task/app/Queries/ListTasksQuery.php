<?php

namespace Modules\Task\Queries;

use App\Shared\Contracts\QueryInterface;

class ListTasksQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page        = 1,
        public readonly int     $perPage     = 25,
        public readonly string  $sortField   = 'created_at',
        public readonly string  $sortDir     = 'desc',

        public readonly ?string $search      = null,

        public readonly ?int    $projectId   = null,
        public readonly ?string $status      = null,
        public readonly ?string $priority    = null,
        public readonly ?string $taskType    = null,
        public readonly ?int    $employeeId  = null,

        public readonly ?string $dateFrom    = null,
        public readonly ?string $dateTo      = null,

        public readonly bool    $isArchived  = false,
    ) {}
}
