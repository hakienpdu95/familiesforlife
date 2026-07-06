<?php

namespace Modules\PerformanceReview\Queries;

use App\Shared\Contracts\QueryInterface;

class ListPerformanceReviewsQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page      = 1,
        public readonly int     $perPage   = 25,
        public readonly string  $sortField = 'created_at',
        public readonly string  $sortDir   = 'desc',

        // Text search — employee name, reviewer name, period
        public readonly ?string $search    = null,

        // Exact filters
        public readonly ?string $status    = null,
        public readonly ?int    $employeeId = null,
        public readonly ?int    $reviewerId = null,
        public readonly ?int    $templateId = null,
        public readonly ?string $period     = null,

        // Date range on created_at
        public readonly ?string $dateFrom  = null,
        public readonly ?string $dateTo    = null,
    ) {}
}
