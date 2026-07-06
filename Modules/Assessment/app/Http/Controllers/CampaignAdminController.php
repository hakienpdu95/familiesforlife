<?php

namespace Modules\Assessment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Assessment\Enums\CampaignStatus;
use Modules\Assessment\Enums\ParticipationStatus;
use Modules\Assessment\Models\CampaignDomainRequirement;
use Modules\Assessment\Models\CampaignParticipation;
use Modules\Assessment\Models\CampaignSandboxTask;
use Modules\Assessment\Models\OpenAssessmentCampaign;
use Modules\Assessment\Models\SandboxEnvironment;
use Modules\Assessment\Models\SandboxTask;
use Modules\Assessment\Notifications\CampaignInviteNotification;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CampaignAdminController extends Controller
{
    /**
     * GET /dashboard/campaigns — List org's campaigns
     */
    public function index(Request $request): View
    {
        $this->authorize('assessment.results');

        $orgId = TenantContext::getOrganizationId();

        $campaigns = OpenAssessmentCampaign::where('organization_id', $orgId)
            ->withCount('participations')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('assessment::campaigns.org.index', compact('campaigns'));
    }

    /**
     * GET /dashboard/campaigns/create
     */
    public function create(): View
    {
        $this->authorize('assessment.results');

        $sandboxEnvs = SandboxEnvironment::where('is_active', true)->get();
        $sandboxTasks = SandboxTask::where('is_active', true)
            ->with('environment')
            ->orderBy('sort_order')
            ->get();

        $domainCodes = ['D1', 'D2', 'D3', 'D4', 'D5', 'D6'];

        return view('assessment::campaigns.org.create', compact('sandboxEnvs', 'sandboxTasks', 'domainCodes'));
    }

    /**
     * POST /dashboard/campaigns
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('assessment.results');

        $data = $request->validate([
            'title'                  => ['required', 'string', 'max:200'],
            'description'            => ['nullable', 'string', 'max:2000'],
            'target_job_title_label' => ['nullable', 'string', 'max:200'],
            'target_department_label'=> ['nullable', 'string', 'max:200'],
            'min_trust_level'        => ['required', 'integer', 'min:0', 'max:2'],
            'min_tdwcf_score'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status'                 => ['required', 'in:draft,open'],
            'open_from'              => ['nullable', 'date'],
            'open_until'             => ['nullable', 'date', 'after:open_from'],
            'max_participants'       => ['nullable', 'integer', 'min:1'],
            'is_anonymous_to_org'    => ['boolean'],
            'domain_codes'           => ['nullable', 'array'],
            'domain_codes.*'         => ['string', 'in:D1,D2,D3,D4,D5,D6'],
            'domain_min_scores'      => ['nullable', 'array'],
            'sandbox_task_ids'       => ['nullable', 'array'],
            'sandbox_task_ids.*'     => ['integer', 'exists:sandbox_tasks,id'],
        ]);

        $orgId = TenantContext::getOrganizationId();

        DB::transaction(function () use ($data, $orgId, $request) {
            $campaign = OpenAssessmentCampaign::create([
                'organization_id'        => $orgId,
                'title'                  => $data['title'],
                'description'            => $data['description'] ?? null,
                'target_job_title_label' => $data['target_job_title_label'] ?? null,
                'target_department_label'=> $data['target_department_label'] ?? null,
                'min_trust_level'        => $data['min_trust_level'],
                'min_tdwcf_score'        => $data['min_tdwcf_score'] ?? null,
                'status'                 => $data['status'],
                'open_from'              => $data['open_from'] ?? null,
                'open_until'             => $data['open_until'] ?? null,
                'max_participants'       => $data['max_participants'] ?? null,
                'is_anonymous_to_org'    => $request->boolean('is_anonymous_to_org', true),
            ]);

            // Domain requirements
            foreach ($data['domain_codes'] ?? [] as $code) {
                CampaignDomainRequirement::create([
                    'campaign_id' => $campaign->id,
                    'domain_code' => $code,
                    'min_score'   => $data['domain_min_scores'][$code] ?? 0,
                    'is_required' => 1,
                ]);
            }

            // Sandbox tasks
            foreach ($data['sandbox_task_ids'] ?? [] as $i => $taskId) {
                CampaignSandboxTask::create([
                    'campaign_id'     => $campaign->id,
                    'sandbox_task_id' => $taskId,
                    'is_required'     => 1,
                    'sort_order'      => $i,
                ]);
            }
        });

        return redirect()->route('campaigns.admin.index')
            ->with('success', 'Campaign đã được tạo thành công.');
    }

    /**
     * GET /dashboard/campaigns/{campaign}
     */
    public function show(OpenAssessmentCampaign $campaign): View
    {
        $this->authorize('assessment.results');
        $this->authorizeOrg($campaign);

        $campaign->load(['domainRequirements', 'sandboxTasks.task', 'organization']);

        return view('assessment::campaigns.org.show', compact('campaign'));
    }

    /**
     * GET /dashboard/campaigns/{campaign}/results — Ranking ẩn danh
     */
    public function results(OpenAssessmentCampaign $campaign): View
    {
        $this->authorize('assessment.results');
        $this->authorizeOrg($campaign);

        $participations = CampaignParticipation::where('campaign_id', $campaign->id)
            ->where('status', ParticipationStatus::Completed->value)
            ->with(['user', 'scores'])
            ->orderByDesc('result_tdwcf_score')
            ->get();

        return view('assessment::campaigns.org.results', compact('campaign', 'participations'));
    }

    /**
     * POST /dashboard/campaigns/{campaign}/invite/{participation}
     * Mời ứng viên — lúc này reveal tên/email
     */
    public function invite(Request $request, OpenAssessmentCampaign $campaign, CampaignParticipation $participation): RedirectResponse
    {
        $this->authorize('assessment.results');
        $this->authorizeOrg($campaign);

        abort_if($participation->campaign_id !== $campaign->id, 404);
        abort_if($participation->status !== ParticipationStatus::Completed, 403, 'Chỉ invite ứng viên đã hoàn thành.');

        $request->validate([
            'org_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'org_note'   => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($participation, $request) {
            $participation->update([
                'org_action'    => 'invited',
                'org_action_at' => now(),
                'org_rating'    => $request->input('org_rating'),
                'org_note'      => $request->input('org_note'),
            ]);
        });

        // Send email to candidate (now revealed)
        $participation->user->notify(
            new CampaignInviteNotification($campaign, $participation)
        );

        return back()->with('success', "Đã gửi lời mời đến {$participation->user->name} ({$participation->user->email}).");
    }

    /**
     * PATCH /dashboard/campaigns/{campaign}/status — Change campaign status
     */
    public function updateStatus(Request $request, OpenAssessmentCampaign $campaign): RedirectResponse
    {
        $this->authorize('assessment.results');
        $this->authorizeOrg($campaign);

        $request->validate(['status' => ['required', 'in:draft,open,closed,archived']]);

        $campaign->update(['status' => $request->input('status')]);

        return back()->with('success', 'Trạng thái campaign đã được cập nhật.');
    }

    /**
     * POST /dashboard/campaigns/{campaign}/invite-bulk
     */
    public function inviteBulk(Request $request, OpenAssessmentCampaign $campaign): RedirectResponse
    {
        $this->authorize('assessment.results');
        $this->authorizeOrg($campaign);

        $request->validate([
            'participation_ids'   => ['required', 'array', 'min:1', 'max:100'],
            'participation_ids.*' => ['integer'],
        ]);

        $participations = $campaign->participations()
            ->whereIn('id', $request->participation_ids)
            ->where('status', ParticipationStatus::Completed->value)
            ->where(fn($q) => $q->whereNull('org_action')->orWhere('org_action', '!=', 'invited'))
            ->with('user')
            ->get();

        foreach ($participations as $p) {
            $p->update(['org_action' => 'invited', 'org_action_at' => now()]);
            $p->user->notify(new CampaignInviteNotification($campaign, $p));
        }

        return back()->with('success', "Đã gửi lời mời đến {$participations->count()} ứng viên.");
    }

    /**
     * GET /dashboard/campaigns/{campaign}/export
     */
    public function exportResults(OpenAssessmentCampaign $campaign): StreamedResponse
    {
        $this->authorize('assessment.results');
        $this->authorizeOrg($campaign);

        $rows = $campaign->participations()
            ->with(['user:id,name,email', 'scores'])
            ->orderByDesc('result_tdwcf_score')
            ->get()
            ->map(fn($p) => [
                'Ứng viên'       => (!$campaign->is_anonymous_to_org || $p->isInvited()) ? $p->user?->name : $p->anonymousLabel(),
                'Email'          => (!$campaign->is_anonymous_to_org || $p->isInvited()) ? $p->user?->email : '—',
                'Trạng thái'     => $p->status->label(),
                'TDWCF Score'    => $p->result_tdwcf_score,
                'Maturity Level' => $p->result_maturity_level,
                'Sandbox Avg'    => $p->result_sandbox_avg,
                'Đánh giá org'   => $p->org_rating,
                'Ghi chú'        => $p->org_note,
                'Hành động'      => $p->org_action,
                'Tham gia'       => $p->joined_at?->format('d/m/Y'),
                'Hoàn thành'     => $p->completed_at?->format('d/m/Y'),
            ]);

        return (new FastExcel($rows))->download("campaign-{$campaign->uuid}-results.xlsx");
    }

    /**
     * GET /dashboard/campaigns/{campaign}/results/by-unit
     */
    public function resultsByUnit(OpenAssessmentCampaign $campaign): View
    {
        $this->authorize('assessment.results');
        $this->authorizeOrg($campaign);

        $unitStats = $campaign->participations()
            ->with(['user.employee.department:id,name'])
            ->where('status', ParticipationStatus::Completed->value)
            ->get()
            ->groupBy(fn($p) => $p->user?->employee?->department?->name ?? 'Không xác định')
            ->map(fn($group) => [
                'count'     => $group->count(),
                'invited'   => $group->where('org_action', 'invited')->count(),
                'avg_tdwcf' => round($group->avg('result_tdwcf_score') ?? 0, 1),
                'avg_sb'    => round($group->avg('result_sandbox_avg') ?? 0, 1),
            ])
            ->sortByDesc('avg_tdwcf');

        return view('assessment::campaigns.org.results-by-unit', compact('campaign', 'unitStats'));
    }

    // ── Private helpers ──────────────────────────────────────────────

    private function authorizeOrg(OpenAssessmentCampaign $campaign): void
    {
        abort_if(
            $campaign->organization_id !== TenantContext::getOrganizationId(),
            403,
            'Không có quyền truy cập campaign này.'
        );
    }
}
