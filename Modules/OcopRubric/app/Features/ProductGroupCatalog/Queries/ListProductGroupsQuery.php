<?php

namespace Modules\OcopRubric\Features\ProductGroupCatalog\Queries;

use App\Shared\Contracts\QueryInterface;

class ListProductGroupsQuery implements QueryInterface
{
    public function __construct(
        public readonly bool    $activeOnly   = false,
        public readonly ?string $industryCode = null,
        public readonly ?string $search       = null,
    ) {}
}
