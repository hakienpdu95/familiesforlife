<?php

namespace Modules\N8n\Features\ConnectionManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class ListN8nConnectionsForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly bool $includeTrashed = false,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly string $sortField = 'created_at',
        public readonly string $sortDir = 'desc',
    ) {}
}
