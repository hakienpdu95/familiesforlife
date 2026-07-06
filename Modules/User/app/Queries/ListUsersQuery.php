<?php

namespace Modules\User\Queries;

use App\Shared\Contracts\QueryInterface;

class ListUsersQuery implements QueryInterface
{
    public function __construct(
        // Pagination
        public readonly int     $page          = 1,
        public readonly int     $perPage       = 25,

        // Sort
        public readonly string  $sortField     = 'created_at',
        public readonly string  $sortDir       = 'desc',

        // Text search (OR across name, email)
        public readonly ?string $search        = null,

        // Tenant scope — null = all (admin only), non-null = single org
        public readonly ?int    $organizationId = null,

        // Exact filters
        public readonly ?string $role          = null,
        public readonly ?string $status        = null,

        // Date range on created_at (ISO format YYYY-MM-DD)
        public readonly ?string $dateFrom      = null,
        public readonly ?string $dateTo        = null,
    ) {}
}
