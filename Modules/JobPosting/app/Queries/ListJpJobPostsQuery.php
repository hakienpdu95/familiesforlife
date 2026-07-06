<?php

namespace Modules\JobPosting\Queries;

use App\Shared\Contracts\QueryInterface;

class ListJpJobPostsQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page           = 1,
        public readonly int     $perPage        = 25,
        public readonly string  $sortField      = 'created_at',
        public readonly string  $sortDir        = 'desc',

        public readonly ?string $search         = null,
        public readonly ?string $status         = null,
        public readonly ?string $employmentType = null,
        public readonly ?string $workArrangement= null,
        public readonly ?string $experienceLevel= null,
        public readonly ?string $industry       = null,
        public readonly ?int    $departmentId   = null,
        public readonly ?int    $ownerId        = null,

        public readonly ?string $dateFrom       = null,
        public readonly ?string $dateTo         = null,
    ) {}
}
