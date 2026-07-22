<?php

namespace Modules\Banner\Features\BannerManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Banner\Models\Banner;

class ListBannersForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['placement', 'sort_order', 'click_count', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListBannersForAdminQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'sort_order';
        $sortDir   = $query->sortDir === 'desc' ? 'desc' : 'asc';

        return Banner::query()
            ->when($query->placement, fn ($q) => $q->where('placement', $query->placement))
            ->when($query->targetType === 'global', fn ($q) => $q->whereNull('target_type'))
            ->when($query->targetType === 'category', fn ($q) => $q->where('target_type', 'category'))
            ->orderBy($sortField, $sortDir)
            ->orderBy('id')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
