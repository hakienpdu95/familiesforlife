<?php

namespace Modules\Lead\Queries;

use App\Shared\Contracts\QueryInterface;

class ListTagsQuery implements QueryInterface
{
    public function __construct(
        public readonly int $orgId,
    ) {}
}
