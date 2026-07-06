<?php

namespace Modules\Organization\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Organization\Models\Organization;

class ListOrganizationsHandler implements QueryHandlerInterface
{
    private const SORTABLE = [
        'name', 'industry', 'status', 'members_count', 'province_name', 'created_at',
    ];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListOrganizationsQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField
            : 'created_at';

        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = Organization::withoutTenant()
            ->select('organizations.*')
            ->withCount('members')
            ->with([
                'province:province_code,name',
                'ward:ward_code,name',
                'planSubscriptions' => fn ($q) => $q->with('plan:id,name,slug')->latest('starts_at')->limit(1),
            ]);

        // ── Text search (OR across multiple fields) ──────────────────
        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('organizations.name',      'like', $term)
                    ->orWhere('organizations.tax_code', 'like', $term)
                    ->orWhere('organizations.email',    'like', $term)
                    ->orWhere('organizations.phone',    'like', $term);
            });
        }

        // ── Exact filters ─────────────────────────────────────────────
        if ($query->provinceCode !== null && $query->provinceCode !== '') {
            $q->where('organizations.province_code', $query->provinceCode);
        }

        if ($query->wardCode !== null && $query->wardCode !== '') {
            $q->where('organizations.ward_code', $query->wardCode);
        }

        if ($query->status !== null && $query->status !== '') {
            $q->where('organizations.status', $query->status);
        }

        // ── Date range ────────────────────────────────────────────────
        // Explicit bounds — whereDate() wraps DATE() which prevents index use
        if ($query->dateFrom !== null && $query->dateFrom !== '') {
            $q->where('organizations.created_at', '>=', $query->dateFrom . ' 00:00:00');
        }

        if ($query->dateTo !== null && $query->dateTo !== '') {
            $q->where('organizations.created_at', '<=', $query->dateTo . ' 23:59:59');
        }

        // ── Sort ──────────────────────────────────────────────────────
        match ($sortField) {
            'members_count' => $q->orderBy('members_count', $sortDir),
            'province_name' => $q->leftJoin('provinces as prov_sort', 'organizations.province_code', '=', 'prov_sort.province_code')
                                  ->orderBy('prov_sort.name', $sortDir),
            default         => $q->orderBy('organizations.' . $sortField, $sortDir),
        };

        // Secondary sort by id for stable pagination
        if ($sortField !== 'id') {
            $q->orderBy('organizations.id', $sortDir);
        }

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
