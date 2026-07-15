<?php

namespace Modules\Ocop\Features\OcopCategoryManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Collection;
use Modules\Ocop\Models\OcopCategory;

class ListOcopCategoriesForAdminHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var ListOcopCategoriesForAdminQuery $query */
        return OcopCategory::withCount('products')
            ->when($query->search, fn ($q) => $q->where('name', 'like', "%{$query->search}%"))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
