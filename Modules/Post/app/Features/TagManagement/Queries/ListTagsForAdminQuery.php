<?php

namespace Modules\Post\Features\TagManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class ListTagsForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly int $page = 1,
        public readonly int $perPage = 25,
        public readonly string $sortField = 'name',
        public readonly string $sortDir = 'asc',
    ) {}
}
