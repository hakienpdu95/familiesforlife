<?php

namespace Modules\ContentOutlines\Features\OutlineGeneration\Queries;

use App\Shared\Contracts\QueryInterface;

class ListContentOutlinesForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?int $postCategoryId = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly string $sortField = 'created_at',
        public readonly string $sortDir = 'desc',
    ) {}
}
