<?php

namespace Modules\JobTitle\Queries;

use App\Shared\Contracts\QueryInterface;

class ListJobTitlesQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page      = 1,
        public readonly int     $perPage   = 25,
        public readonly string  $sortField = 'level',
        public readonly string  $sortDir   = 'asc',

        // Text search — name, code (OR)
        public readonly ?string $search   = null,

        // Exact filters
        public readonly ?string $category = null,
        public readonly ?bool   $isActive = null,

        // Level range
        public readonly ?int    $levelMin = null,
        public readonly ?int    $levelMax = null,
    ) {}
}
