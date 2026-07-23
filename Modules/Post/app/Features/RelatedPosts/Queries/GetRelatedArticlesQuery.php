<?php

namespace Modules\Post\Features\RelatedPosts\Queries;

use App\Shared\Contracts\QueryInterface;

class GetRelatedArticlesQuery implements QueryInterface
{
    public function __construct(
        public readonly int $articleId,
        public readonly string $locale,
        public readonly int $limit = 6,
    ) {}
}
