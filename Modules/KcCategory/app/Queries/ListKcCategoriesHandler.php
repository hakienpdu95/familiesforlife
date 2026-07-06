<?php

namespace Modules\KcCategory\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\KcCategory\Models\KcCategory;

class ListKcCategoriesHandler implements QueryHandlerInterface
{
    private const SORTABLE = [
        'name', 'slug', 'sort_order', 'is_active', 'created_at',
    ];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListKcCategoriesQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField
            : 'sort_order';

        $sortDir = $query->sortDir === 'desc' ? 'desc' : 'asc';

        $q = KcCategory::withoutTenant()
            ->select('kc_categories.*')
            ->withCount('children')
            ->with(['parent:id,name'])
            ->where('kc_categories.organization_id', TenantContext::getOrganizationId());

        // ── Text search ─────────────────────────────────────────────────────
        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('kc_categories.name', 'like', $term)
                    ->orWhere('kc_categories.slug', 'like', $term);
            });
        }

        // ── Filters ─────────────────────────────────────────────────────────
        if ($query->isActive !== null) {
            $q->where('kc_categories.is_active', $query->isActive);
        }

        if ($query->parentId !== null) {
            if ($query->parentId === 0) {
                $q->whereNull('kc_categories.parent_id');
            } else {
                $q->where('kc_categories.parent_id', $query->parentId);
            }
        }

        // ── Sort ────────────────────────────────────────────────────────────
        $q->orderBy('kc_categories.' . $sortField, $sortDir);

        if ($sortField !== 'id') {
            $q->orderBy('kc_categories.id', $sortDir);
        }

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
