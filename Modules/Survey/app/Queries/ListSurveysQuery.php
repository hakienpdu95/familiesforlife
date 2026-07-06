<?php

namespace Modules\Survey\Queries;

use App\Shared\Contracts\QueryInterface;

class ListSurveysQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page      = 1,
        public readonly int     $perPage   = 25,
        public readonly string  $sortField = 'created_at',
        public readonly string  $sortDir   = 'desc',

        // Text search — matches title OR slug (OR)
        public readonly ?string $search    = null,

        // Exact status filter (SurveyStatus int value)
        public readonly ?int    $status    = null,

        // Date range on created_at (ISO format YYYY-MM-DD)
        public readonly ?string $dateFrom  = null,
        public readonly ?string $dateTo    = null,
    ) {}
}
