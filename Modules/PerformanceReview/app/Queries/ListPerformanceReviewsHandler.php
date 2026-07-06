<?php

namespace Modules\PerformanceReview\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\PerformanceReview\Models\PerformanceReview;

class ListPerformanceReviewsHandler implements QueryHandlerInterface
{
    private const SORTABLE = [
        'created_at', 'period', 'status', 'overall_score', 'overall_rating',
        'snap_job_title', 'reviewed_at',
    ];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListPerformanceReviewsQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField
            : 'created_at';

        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = PerformanceReview::withoutTenant()
            ->select('performance_reviews.*')
            ->with([
                'employee:id,full_name,employee_code,snap_branch_name,snap_dept_name',
                'reviewer:id,full_name,employee_code',
                'template:id,name,period_type',
            ])
            ->where('performance_reviews.organization_id', TenantContext::getOrganizationId());

        // ── Text search (OR) ─────────────────────────────────────────────────
        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->whereHas('employee', function (Builder $emp) use ($term): void {
                        $emp->where('full_name', 'like', $term)
                            ->orWhere('employee_code', 'like', $term);
                    })
                    ->orWhereHas('reviewer', function (Builder $rev) use ($term): void {
                        $rev->where('full_name', 'like', $term);
                    })
                    ->orWhere('performance_reviews.period', 'like', $term);
            });
        }

        // ── Exact filters ────────────────────────────────────────────────────
        if ($query->status !== null && $query->status !== '') {
            $q->where('performance_reviews.status', $query->status);
        }

        if ($query->employeeId !== null) {
            $q->where('performance_reviews.employee_id', $query->employeeId);
        }

        if ($query->reviewerId !== null) {
            $q->where('performance_reviews.reviewer_id', $query->reviewerId);
        }

        if ($query->templateId !== null) {
            $q->where('performance_reviews.template_id', $query->templateId);
        }

        if ($query->period !== null && $query->period !== '') {
            $q->where('performance_reviews.period', $query->period);
        }

        // ── Date range on created_at ─────────────────────────────────────────
        if ($query->dateFrom !== null && $query->dateFrom !== '') {
            $q->whereDate('performance_reviews.created_at', '>=', $query->dateFrom);
        }

        if ($query->dateTo !== null && $query->dateTo !== '') {
            $q->whereDate('performance_reviews.created_at', '<=', $query->dateTo);
        }

        // ── Sort ─────────────────────────────────────────────────────────────
        $q->orderBy('performance_reviews.' . $sortField, $sortDir);

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
