<?php

namespace Modules\Report\Queries\Hr;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Modules\Recruitment\Models\RcApplication;
use Modules\Recruitment\Models\RcOffer;
use Modules\JobPosting\Models\JobPost;

final class RecruitmentFunnelQuery
{
    public function __construct(
        private readonly int     $orgId,
        private readonly string  $dateFrom,
        private readonly string  $dateTo,
        private readonly ?int    $departmentId = null,
        private readonly ?string $status       = null,
    ) {}

    public static function fromRequest(array $params): self
    {
        return new self(
            orgId:        TenantContext::getOrganizationId(),
            dateFrom:     $params['date_from']     ?? now()->startOfMonth()->toDateString(),
            dateTo:       $params['date_to']       ?? now()->toDateString(),
            departmentId: $params['department_id'] ? (int) $params['department_id'] : null,
            status:       $params['status']        ?? null,
        );
    }

    private function base()
    {
        return RcApplication::withoutTenant()
            ->where('rc_applications.org_id', $this->orgId)
            ->whereBetween('rc_applications.applied_at', [
                $this->dateFrom . ' 00:00:00',
                $this->dateTo   . ' 23:59:59',
            ])
            ->when($this->status, fn ($q) => $q->where('rc_applications.status', $this->status));
    }

    public function summary(): array
    {
        $total = (clone $this->base())->count();

        $hired = (clone $this->base())->where('rc_applications.status', 'hired')->count();
        $rejected = (clone $this->base())->where('rc_applications.status', 'rejected')->count();

        $avgDays = RcOffer::join('rc_applications', 'rc_applications.id', '=', 'rc_offers.application_id')
            ->where('rc_applications.org_id', $this->orgId)
            ->whereNotNull('rc_offers.sent_at')
            ->whereBetween('rc_applications.applied_at', [$this->dateFrom, $this->dateTo])
            ->selectRaw('AVG(DATEDIFF(rc_offers.sent_at, rc_applications.applied_at)) as avg_days')
            ->value('avg_days');

        $openJobs = JobPost::where('organization_id', $this->orgId)
            ->where('status', 'published')
            ->count();

        return [
            'open_positions'     => $openJobs,
            'total_applications' => $total,
            'hired'              => $hired,
            'rejected'           => $rejected,
            'avg_days_to_hire'   => round((float) ($avgDays ?? 0), 1),
            'offer_acceptance_rate' => $this->offerAcceptanceRate(),
        ];
    }

    private function offerAcceptanceRate(): float
    {
        $sent = RcOffer::join('rc_applications', 'rc_applications.id', '=', 'rc_offers.application_id')
            ->where('rc_applications.org_id', $this->orgId)
            ->whereBetween('rc_applications.applied_at', [$this->dateFrom, $this->dateTo])
            ->count();
        if ($sent === 0) return 0.0;
        $accepted = RcOffer::join('rc_applications', 'rc_applications.id', '=', 'rc_offers.application_id')
            ->where('rc_applications.org_id', $this->orgId)
            ->whereBetween('rc_applications.applied_at', [$this->dateFrom, $this->dateTo])
            ->where('rc_offers.status', 'accepted')
            ->count();
        return round($accepted / $sent * 100, 1);
    }

    public function funnel(): Collection
    {
        return (clone $this->base())
            ->join('rc_pipeline_stages', 'rc_pipeline_stages.id', '=', 'rc_applications.current_stage_id')
            ->selectRaw('
                rc_pipeline_stages.id as stage_id,
                rc_pipeline_stages.code as stage_code,
                rc_pipeline_stages.name as label,
                rc_pipeline_stages.sort_order,
                COUNT(*) as count
            ')
            ->groupBy('rc_pipeline_stages.id', 'stage_code', 'label', 'sort_order')
            ->orderBy('sort_order')
            ->get()
            ->pipe(function ($stages) {
                $total = $stages->first()?->count ?? 1;
                return $stages->map(fn ($s) => [
                    'stage_id'         => $s->stage_id,
                    'stage_code'       => $s->stage_code,
                    'label'            => $s->label,
                    'count'            => (int) $s->count,
                    'conversion_pct'   => round($s->count / $total * 100, 1),
                ]);
            });
    }

    public function bySource(): Collection
    {
        return (clone $this->base())
            ->join('rc_candidates', 'rc_candidates.id', '=', 'rc_applications.candidate_id')
            ->selectRaw('
                COALESCE(rc_candidates.source, "unknown") as source,
                COUNT(*) as applications,
                SUM(rc_applications.status = "hired") as hires
            ')
            ->groupBy('source')
            ->orderByDesc('applications')
            ->get();
    }

    public function openJobs(): Collection
    {
        return JobPost::where('organization_id', $this->orgId)
            ->where('status', 'published')
            ->leftJoin('departments', 'departments.id', '=', 'job_posts.department_id')
            ->selectRaw('
                job_posts.id as job_post_id,
                job_posts.title,
                departments.name as department,
                job_posts.published_at as posted_at,
                DATEDIFF(NOW(), job_posts.published_at) as days_open
            ')
            ->orderByDesc('days_open')
            ->limit(10)
            ->get();
    }

    public function monthlyApplications(): Collection
    {
        return (clone $this->base())
            ->selectRaw("DATE_FORMAT(applied_at, '%Y-%m') as month, COUNT(*) as applications, SUM(status='hired') as hires")
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }
}
