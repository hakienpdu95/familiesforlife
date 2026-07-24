<?php

namespace Modules\Post\Features\AuthorHub\Queries;

use App\Shared\Contracts\QueryInterface;

class ListPublicAuthorsQuery implements QueryInterface
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 24,
    ) {}
}
