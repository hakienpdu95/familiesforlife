<?php

namespace Modules\ProvinceShowcase\Features\ProvinceManagement\Queries;

use App\Models\Province;
use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListProvincesForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['name', 'province_code', 'place_type', 'is_active', 'is_featured', 'order_column', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListProvincesForAdminQuery $query */
        $q = Province::query()->with('region:id,name');

        if ($query->search) {
            $term = '%'.$query->search.'%';
            $q->where(function ($sub) use ($term) {
                $sub->where('name', 'like', $term)
                    ->orWhere('short_name', 'like', $term)
                    ->orWhere('province_code', 'like', $term)
                    ->orWhere('slug', 'like', $term);
            });
        }

        if ($query->regionId) {
            $q->where('region_id', $query->regionId);
        }

        if ($query->placeType) {
            $q->where('place_type', $query->placeType);
        }

        if ($query->isActive !== null) {
            $q->where('is_active', $query->isActive);
        }

        if ($query->isFeatured !== null) {
            $q->where('is_featured', $query->isFeatured);
        }

        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'province_code';
        $sortDir = $query->sortDir === 'desc' ? 'desc' : 'asc';

        return $q->orderBy($sortField, $sortDir)
            ->orderBy('id')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
