<?php

namespace Modules\Branch\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Branch\Models\Branch;

class ListBranchesHandler implements QueryHandlerInterface
{
    private const SORTABLE = [
        'name', 'code', 'type', 'status', 'depth', 'path', 'created_at', 'province_name',
    ];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListBranchesQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField
            : 'path';

        $sortDir = $query->sortDir === 'desc' ? 'desc' : 'asc';

        $q = Branch::withoutTenant()
            ->select('branches.*')
            ->with(['province:province_code,name', 'parent:id,name,code'])
            ->withCount('children')
            ->where('branches.organization_id', TenantContext::getOrganizationId());

        // ── Text search (OR) ────────────────────────────────────────────────
        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('branches.name',     'like', $term)
                    ->orWhere('branches.code',     'like', $term)
                    ->orWhere('branches.tax_code', 'like', $term)
                    ->orWhere('branches.email',    'like', $term)
                    ->orWhere('branches.phone',    'like', $term);
            });
        }

        // ── Exact filters ───────────────────────────────────────────────────
        if ($query->type !== null && $query->type !== '') {
            $q->where('branches.type', $query->type);
        }

        if ($query->status !== null && $query->status !== '') {
            $q->where('branches.status', $query->status);
        }

        if ($query->provinceCode !== null && $query->provinceCode !== '') {
            $q->where('branches.province_code', $query->provinceCode);
        }

        if ($query->parentId !== null) {
            $q->where('branches.parent_id', $query->parentId);
        }

        // ── Date range ──────────────────────────────────────────────────────
        if ($query->dateFrom !== null && $query->dateFrom !== '') {
            $q->where('branches.created_at', '>=', $query->dateFrom . ' 00:00:00');
        }

        if ($query->dateTo !== null && $query->dateTo !== '') {
            $q->where('branches.created_at', '<=', $query->dateTo . ' 23:59:59');
        }

        // ── Sort ────────────────────────────────────────────────────────────
        match ($sortField) {
            'province_name' => $q->leftJoin('provinces as prov_sort', 'branches.province_code', '=', 'prov_sort.province_code')
                                  ->orderBy('prov_sort.name', $sortDir),
            default         => $q->orderBy('branches.' . $sortField, $sortDir),
        };

        // Secondary sort by id for stable pagination
        if ($sortField !== 'id') {
            $q->orderBy('branches.id', $sortDir);
        }

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
