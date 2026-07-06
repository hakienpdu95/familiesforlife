<?php

namespace Modules\Organization\Queries;

use App\Shared\Contracts\QueryInterface;

class ListOrganizationsQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page         = 1,
        public readonly int     $perPage      = 25,
        public readonly string  $sortField    = 'created_at',
        public readonly string  $sortDir      = 'desc',

        // Text search — matches name, tax_code, email, phone (OR)
        public readonly ?string $search       = null,

        // Exact filters
        public readonly ?string $provinceCode = null,
        public readonly ?string $wardCode     = null,

        // Date range on created_at (ISO format YYYY-MM-DD)
        public readonly ?string $dateFrom     = null,
        public readonly ?string $dateTo       = null,

        // Exact status filter
        public readonly ?string $status       = null,
    ) {}
}
