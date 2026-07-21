<?php

namespace Modules\Post\Features\TagManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class ListTagsForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $search = null,
    ) {}
}
