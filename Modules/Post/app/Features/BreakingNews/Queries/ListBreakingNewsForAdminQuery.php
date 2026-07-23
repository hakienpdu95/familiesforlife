<?php

namespace Modules\Post\Features\BreakingNews\Queries;

use App\Shared\Contracts\QueryInterface;

class ListBreakingNewsForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?bool $isActive = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly string $sortField = 'sort_order',
        public readonly string $sortDir = 'asc',
    ) {}
}
