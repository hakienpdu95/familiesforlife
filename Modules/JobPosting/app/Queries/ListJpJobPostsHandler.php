<?php

namespace Modules\JobPosting\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\JobPosting\Models\JpJobPost;

class ListJpJobPostsHandler implements QueryHandlerInterface
{
    private const SORTABLE = [
        'title', 'status', 'employment_type', 'work_arrangement', 'experience_level',
        'industry', 'headcount', 'application_count', 'published_at', 'expire_at',
        'created_at', 'owner_name', 'department_name',
    ];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListJpJobPostsQuery $query */
        $orgId = TenantContext::getOrganizationId();

        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField
            : 'created_at';

        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = JpJobPost::withoutTenant()
            ->select('jp_job_posts.*')
            ->where('jp_job_posts.organization_id', $orgId)
            ->leftJoin('departments as dept', 'jp_job_posts.department_id', '=', 'dept.id')
            ->leftJoin('job_titles as jt', 'jp_job_posts.job_title_id', '=', 'jt.id')
            ->leftJoin('users as owner_user', 'jp_job_posts.owner_id', '=', 'owner_user.id')
            ->addSelect([
                'dept.name as department_name',
                'jt.name as position_name',
                'owner_user.name as owner_name',
            ]);

        // ── Text search ──────────────────────────────────────────────────────
        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('jp_job_posts.title', 'like', $term)
                    ->orWhere('jp_job_posts.code', 'like', $term)
                    ->orWhere('jp_job_posts.summary', 'like', $term);
            });
        }

        // ── Exact filters ────────────────────────────────────────────────────
        if ($query->status !== null && $query->status !== '') {
            $q->where('jp_job_posts.status', $query->status);
        }

        if ($query->employmentType !== null && $query->employmentType !== '') {
            $q->where('jp_job_posts.employment_type', $query->employmentType);
        }

        if ($query->workArrangement !== null && $query->workArrangement !== '') {
            $q->where('jp_job_posts.work_arrangement', $query->workArrangement);
        }

        if ($query->experienceLevel !== null && $query->experienceLevel !== '') {
            $q->where('jp_job_posts.experience_level', $query->experienceLevel);
        }

        if ($query->industry !== null && $query->industry !== '') {
            $q->where('jp_job_posts.industry', $query->industry);
        }

        if ($query->departmentId !== null) {
            $q->where('jp_job_posts.department_id', $query->departmentId);
        }

        if ($query->ownerId !== null) {
            $q->where('jp_job_posts.owner_id', $query->ownerId);
        }

        // ── Date range ───────────────────────────────────────────────────────
        if ($query->dateFrom !== null && $query->dateFrom !== '') {
            $q->whereDate('jp_job_posts.created_at', '>=', $query->dateFrom);
        }

        if ($query->dateTo !== null && $query->dateTo !== '') {
            $q->whereDate('jp_job_posts.created_at', '<=', $query->dateTo);
        }

        // ── Sort ─────────────────────────────────────────────────────────────
        match ($sortField) {
            'department_name' => $q->orderBy('dept.name', $sortDir),
            'owner_name'      => $q->orderBy('owner_user.name', $sortDir),
            default           => $q->orderBy('jp_job_posts.' . $sortField, $sortDir),
        };

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
