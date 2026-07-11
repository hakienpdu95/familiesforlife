<?php

namespace Modules\Post\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryInterface;

class ListPublishedArticlesQuery implements QueryInterface
{
    public function __construct(
        public readonly string $locale,
        public readonly int $page = 1,
        public readonly int $perPage = 12,
        public readonly ?int $categoryId = null,
    ) {}
}
