<?php

namespace Modules\Banner\Features\BannerManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Banner\Models\Banner;

class ListBannersForAdminHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListBannersForAdminQuery $query */
        return Banner::query()
            ->when($query->placement, fn ($q) => $q->where('placement', $query->placement))
            ->when($query->targetType === 'global', fn ($q) => $q->whereNull('target_type'))
            ->when($query->targetType === 'category', fn ($q) => $q->where('target_type', 'category'))
            ->orderBy('placement')
            ->orderBy('sort_order')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
