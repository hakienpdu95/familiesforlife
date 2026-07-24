<?php

namespace Modules\Post\Features\AuthorHub\Queries;

use App\Shared\Contracts\QueryInterface;

class ListAuthorArticlesQuery implements QueryInterface
{
    public function __construct(
        public readonly int $userId,
        public readonly string $locale,
        public readonly int $page = 1,
        public readonly int $perPage = 12,
    ) {}
}
