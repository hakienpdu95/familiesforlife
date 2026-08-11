<?php

namespace Modules\Heritage\Features\HeritageSiteManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Heritage\Models\HeritageSite;

class ListHeritageSitesForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['name', 'heritage_type', 'rank', 'status', 'sort_order', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListHeritageSitesForAdminQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'created_at';
        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        return HeritageSite::query()
            ->when($query->search, fn ($q) => $q->where('name', 'like', "%{$query->search}%"))
            ->when($query->heritageType, fn ($q) => $q->where('heritage_type', $query->heritageType))
            ->when($query->status, fn ($q) => $q->where('status', $query->status))
            ->when($query->provinceCode, fn ($q) => $q->where('province_code', $query->provinceCode))
            ->orderBy($sortField, $sortDir)
            ->orderBy('id')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
