<?php

namespace Modules\Marketplace\Queries;

use App\Shared\Contracts\QueryInterface;

class ListMktListingQuery implements QueryInterface
{
    public function __construct(
        // Pagination
        public readonly int     $page         = 1,
        public readonly int     $perPage      = 25,

        // Sort
        public readonly string  $sortField    = 'created_at',
        public readonly string  $sortDir      = 'desc',

        // Text search
        public readonly ?string $search       = null,

        // Filters
        public readonly ?string $status       = null,
        public readonly ?string $listingType  = null,
        public readonly ?string $posterType   = null,
        public readonly ?string $workType     = null,
        public readonly ?string $experienceLevel = null,

        // Date range
        public readonly ?string $dateFrom     = null,
        public readonly ?string $dateTo       = null,

        // Admin scope — bypass tenant
        public readonly bool    $adminScope   = false,
    ) {}
}
