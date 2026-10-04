<?php

namespace Modules\OcopSubject\Features\OcopSubjectManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\OcopSubject\Models\OcopSubject;

class ListOcopSubjectsForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['name', 'tax_code', 'organization_type', 'ocop_star', 'products_count', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'created_at';
        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        return OcopSubject::query()
            ->withCount('products')
            ->when($query->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$query->search}%")
                ->orWhere('tax_code', 'like', "%{$query->search}%")))
            ->when($query->organizationType, fn ($q) => $q->where('organization_type', $query->organizationType))
            ->when($query->provinceCode, fn ($q) => $q->where('province_code', $query->provinceCode))
            ->orderBy($sortField, $sortDir)
            ->orderBy('id')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
