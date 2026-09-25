<?php

namespace Modules\ProvinceShowcase\Features\PublicShowcase\Queries;

use App\Models\Province;
use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Collection;

class ListFeaturedProvincesHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var ListFeaturedProvincesQuery $query */
        return Province::query()
            ->where('is_featured', true)
            ->where('is_active', true)
            ->whereNotNull('slug')
            ->orderByRaw('order_column IS NULL')
            ->orderBy('order_column')
            ->orderBy('name')
            ->limit($query->limit)
            ->get(['id', 'name', 'short_name', 'slug', 'place_type', 'theme_color']);
    }
}
