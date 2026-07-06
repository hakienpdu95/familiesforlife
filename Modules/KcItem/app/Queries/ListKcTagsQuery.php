<?php

namespace Modules\KcItem\Queries;

use App\Shared\Contracts\QueryInterface;

class ListKcTagsQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page      = 1,
        public readonly int     $perPage   = 50,
        public readonly string  $sortField = 'name',
        public readonly string  $sortDir   = 'asc',
        public readonly ?string $search    = null,
    ) {}
}
