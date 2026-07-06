<?php

namespace Modules\KcItem\Queries;

use App\Shared\Contracts\QueryInterface;

class ListKcItemsQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page       = 1,
        public readonly int     $perPage    = 25,
        public readonly string  $sortField  = 'created_at',
        public readonly string  $sortDir    = 'desc',
        public readonly ?string $search     = null,
        public readonly ?string $status     = null,
        public readonly ?string $type       = null,
        public readonly ?int    $categoryId = null,
        public readonly ?string $visibility = null,
        public readonly ?string $dateFrom   = null,
        public readonly ?string $dateTo     = null,
        public readonly ?int    $tagId      = null,
    ) {}
}
