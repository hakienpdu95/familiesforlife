<?php

namespace Modules\JobTitle\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\JobTitle\Models\JobTitle;

class ListJobTitlesHandler implements QueryHandlerInterface
{
    private const SORTABLE = [
        'name', 'code', 'category', 'level', 'is_active', 'created_at',
    ];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListJobTitlesQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField
            : 'level';

        $sortDir = $query->sortDir === 'desc' ? 'desc' : 'asc';

        $q = JobTitle::withoutTenant()
            ->select('job_titles.*')
            ->where('job_titles.organization_id', TenantContext::getOrganizationId());

        // ── Text search (OR) ────────────────────────────────────────────────
        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('job_titles.name', 'like', $term)
                    ->orWhere('job_titles.code', 'like', $term);
            });
        }

        // ── Exact filters ───────────────────────────────────────────────────
        if ($query->category !== null && $query->category !== '') {
            $q->where('job_titles.category', $query->category);
        }

        if ($query->isActive !== null) {
            $q->where('job_titles.is_active', $query->isActive);
        }

        // ── Level range ─────────────────────────────────────────────────────
        if ($query->levelMin !== null) {
            $q->where('job_titles.level', '>=', $query->levelMin);
        }

        if ($query->levelMax !== null) {
            $q->where('job_titles.level', '<=', $query->levelMax);
        }

        // ── Sort ────────────────────────────────────────────────────────────
        $q->orderBy('job_titles.' . $sortField, $sortDir);

        if ($sortField !== 'id') {
            $q->orderBy('job_titles.id', $sortDir);
        }

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
