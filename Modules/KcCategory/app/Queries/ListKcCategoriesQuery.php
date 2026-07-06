<?php

namespace Modules\KcCategory\Queries;

use App\Shared\Contracts\QueryInterface;

class ListKcCategoriesQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page      = 1,
        public readonly int     $perPage   = 25,
        public readonly string  $sortField = 'sort_order',
        public readonly string  $sortDir   = 'asc',

        public readonly ?string $search   = null,
        public readonly ?bool   $isActive = null,
        public readonly ?int    $parentId = null,
    ) {}
}
