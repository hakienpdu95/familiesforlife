<?php

namespace Modules\ProvinceShowcase\Features\PublicShowcase\Queries;

use App\Shared\Contracts\QueryInterface;

class ListFeaturedProvincesQuery implements QueryInterface
{
    public function __construct(
        public readonly int $limit = 5,
    ) {}
}
