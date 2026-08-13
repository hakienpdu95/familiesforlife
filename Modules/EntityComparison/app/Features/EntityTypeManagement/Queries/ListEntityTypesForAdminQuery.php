<?php

namespace Modules\EntityComparison\Features\EntityTypeManagement\Queries;

use App\Shared\Contracts\QueryInterface;

/** Đúng mẫu ListArticlesForAdminQuery (Modules\Post) — remote pagination/sort qua Tabulator. */
class ListEntityTypesForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly int $page = 1,
        public readonly int $perPage = 25,
        public readonly string $sortField = 'sort_order',
        public readonly string $sortDir = 'asc',
    ) {}
}
