<?php

namespace Modules\KcItem\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\KcItem\Models\KcTag;

class ListKcTagsHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['name', 'slug', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListKcTagsQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField : 'name';

        $sortDir = $query->sortDir === 'desc' ? 'desc' : 'asc';

        $q = KcTag::withoutTenant()
            ->withCount('items')
            ->where('organization_id', TenantContext::getOrganizationId());

        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function ($sub) use ($term): void {
                $sub->where('name', 'like', $term)
                    ->orWhere('slug', 'like', $term);
            });
        }

        $q->orderBy($sortField, $sortDir);
        if ($sortField !== 'id') {
            $q->orderBy('id', $sortDir);
        }

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
