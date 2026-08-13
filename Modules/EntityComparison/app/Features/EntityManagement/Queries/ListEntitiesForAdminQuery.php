<?php

namespace Modules\EntityComparison\Features\EntityManagement\Queries;

use App\Shared\Contracts\QueryInterface;

/** spec/Entity_Comparison_Module_Technical_Spec.md §0 mục 5 — đúng mẫu ListOcopProductsForAdminQuery. */
class ListEntitiesForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?int $entityTypeId = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly string $sortField = 'created_at',
        public readonly string $sortDir = 'desc',
    ) {}
}
