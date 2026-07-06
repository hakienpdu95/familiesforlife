<?php

namespace Modules\SolutionCatalog\Queries;

use App\Shared\Contracts\QueryInterface;

class ListPublishedSolutionsQuery implements QueryInterface
{
    public function __construct(
        public readonly ?int    $verticalId = null,
        public readonly ?string $tag        = null,
        public readonly ?string $search     = null,
    ) {}
}
