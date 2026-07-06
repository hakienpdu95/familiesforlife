<?php

namespace Modules\Assessment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Assessment\Actions\CalculateCgiAction;
use Modules\Assessment\Actions\GenerateWorkforceRecommendationAction;
use Modules\Assessment\Models\CareerPathwayStep;
use Modules\Assessment\Models\JobTitleDomainRequirement;
use Modules\Assessment\Models\WorkforceCertification;
use Modules\Assessment\Models\WorkforcePortfolio;
use Modules\Assessment\Models\WorkforceProfile;
use Modules\Assessment\Models\WorkforceProfileHistory;
use Modules\Assessment\Models\WorkforceRecommendation;
use Modules\Assessment\Models\PassportEntry;
use Modules\Assessment\Models\SandboxSession;
use Modules\Department\Models\Department;
use Modules\Employee\Models\Employee;

class WorkforceProfileController extends Controller
{
    /** Dashboard cá nhân — Digital Twin của user đang đăng nhập */
    public function me(Request $request): View
    {
        $user     = $request->user();
        $orgId    = TenantContext::getOrganizationId();
        $employee = Employee::where('user_id', $user->id)->first();

        $profile = WorkforceProfile::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('user_id', $user->id)
            ->first();

        $certifications = $profile
            ? WorkforceCertification::withoutTenant()
                ->where('workforce_profile_id', $profile->id)
                ->with('definition')
                ->orderByDesc('issued_at')
                ->get()
            : collect();

        $recentHistory = $profile
            ? WorkforceProfileHistory::where('workforce_profile_id', $profile->id)
                ->orderByDesc('recorded_at')
                ->limit(8)
                ->get()
            : collect();

        $sandboxStats = $profile
            ? SandboxSession::withoutTenant()
                ->where('workforce_profile_id', $profile->id)
                ->where('status', 'completed')
                ->selectRaw('COUNT(*) as total, SUM(duration_minutes) as minutes, AVG(final_score) as avg_score')
                ->first()
            : null;

        $portfolios = $profile
            ? WorkforcePortfolio::withoutTenant()
                ->where('workforce_profile_id', $profile->id)
                ->orderBy('sort_order')
                ->get()
            : collect();

        $currentPathwayStep = $profile
            ? CareerPathwayStep::whereNull('organization_id')
                ->where('from_level', $profile->tdwcf_maturity_level)
                ->first()
            : null;

        $allPathwaySteps = CareerPathwayStep::whereNull('organization_id')
            ->orderBy('step_order')
            ->get();

        // Profile completeness — 5 dimensions × 20% each
        $completeness = 0;
        if ($profile) {
            if ($profile->tdwcf_score)          $completeness += 20;
            if ($profile->certifications_count) $completeness += 20;
            if ($profile->sandbox_sessions_total) $completeness += 20;
            if ($profile->impact_score)          $completeness += 20;
            if ($profile->kpi_achievement_avg)   $completeness += 20;

            if ($profile->profile_completeness_pct !== $completeness) {
                $profile->updateQuietly(['profile_completeness_pct' => $completeness]);
                $profile->profile_completeness_pct = $completeness;
            }
        }

        // Trust score breakdown for display
        $trustBreakdown = $profile ? $this->buildTrustBreakdown($profile) : [];

        // Competency Growth Index
        $cgi = $profile ? CalculateCgiAction::run($profile) : null;

        // Score history for trend chart (assessment events only, max 12)
        $scoreHistory = $profile
            ? WorkforceProfileHistory::where('workforce_profile_id', $profile->id)
                ->where('event_type', 'assessment')
                ->whereNotNull('tdwcf_score_after')
                ->orderBy('recorded_at')
                ->limit(12)
                ->get(['recorded_at', 'tdwcf_score_after'])
            : collect();

        // Skill gap benchmarks — next maturity level thresholds per domain
        $skillGapBenchmarks = $this->buildSkillGapBenchmarks($profile?->tdwcf_maturity_level);

        // Job title requirements and AI recommendation
        if ($employee) {
            $employee->loadMissing('jobTitle');
        }

        $jobTitleRequirements = $employee?->job_title_id
            ? JobTitleDomainRequirement::getForJobTitle($employee->job_title_id, $orgId)
            : [];

        $recommendation = $profile
            ? WorkforceRecommendation::withoutTenant()
                ->where('workforce_profile_id', $profile->id)
                ->where('is_stale', false)
                ->latest()
                ->first()
            : null;

        // Career Journal — most recent archived chapters (cross-org, personal)
        $passportEntries = PassportEntry::where('user_id', $user->id)
            ->orderByDesc('snapshot_at')
            ->limit(4)
            ->get();

        return view('assessment::workforce.me', compact(
            'user', 'employee', 'profile', 'certifications',
            'recentHistory', 'sandboxStats', 'currentPathwayStep', 'allPathwaySteps',
            'portfolios', 'completeness', 'trustBreakdown',
            'cgi', 'scoreHistory', 'skillGapBenchmarks',
            'jobTitleRequirements', 'recommendation', 'passportEntries'
        ));
    }

    /** Cập nhật career goal + learning path */
    public function updateGoal(Request $request): RedirectResponse
    {
        $user  = $request->user();
        $orgId = TenantContext::getOrganizationId();

        $data = $request->validate([
            'career_goal'          => 'nullable|string|max:500',
            'current_learning_path' => 'nullable|string|max:300',
        ]);

        $profile = WorkforceProfile::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('user_id', $user->id)
            ->first();

        if ($profile) {
            $profile->update($data);
        }

        return back()->with('success', 'Đã cập nhật mục tiêu nghề nghiệp.');
    }

    /**
     * Skill gap benchmarks — thresholds for each domain to advance to the next level.
     * Returns ['target' => float, 'next_level' => string] or null if at top.
     */
    private function buildSkillGapBenchmarks(?string $currentLevel): array
    {
        $thresholds = [
            'DIGITAL_BEGINNER'     => ['target' => 35.0, 'next_level' => 'DIGITAL_AWARE'],
            'DIGITAL_AWARE'        => ['target' => 55.0, 'next_level' => 'DIGITAL_PRACTITIONER'],
            'DIGITAL_PRACTITIONER' => ['target' => 70.0, 'next_level' => 'DIGITAL_PROFESSIONAL'],
            'DIGITAL_PROFESSIONAL' => ['target' => 85.0, 'next_level' => 'DIGITAL_LEADER'],
            'DIGITAL_LEADER'       => ['target' => 90.0, 'next_level' => null],
        ];

        return $thresholds[$currentLevel] ?? ['target' => 40.0, 'next_level' => 'DIGITAL_AWARE'];
    }

    private function buildTrustBreakdown(WorkforceProfile $profile): array
    {
        $certScore = match ($profile->highest_cert_level) {
            'LEADER'       => 100.0,
            'PROFESSIONAL' => 75.0,
            'PRACTITIONER' => 50.0,
            'FOUNDATION'   => 25.0,
            default        => 0.0,
        };

        return [
            ['label' => 'TDWCF Score',  'weight' => 30, 'raw' => $profile->tdwcf_score ?? 0,        'contribution' => round(($profile->tdwcf_score ?? 0) * 0.30, 1)],
            ['label' => 'Chứng nhận',   'weight' => 25, 'raw' => $certScore,                         'contribution' => round($certScore * 0.25, 1)],
            ['label' => 'KPI',          'weight' => 20, 'raw' => $profile->kpi_achievement_avg ?? 0, 'contribution' => round(($profile->kpi_achievement_avg ?? 0) * 0.20, 1)],
            ['label' => 'Sandbox',      'weight' => 15, 'raw' => $profile->sandbox_score_avg ?? 0,   'contribution' => round(($profile->sandbox_score_avg ?? 0) * 0.15, 1)],
            ['label' => 'Portfolio',    'weight' => 10, 'raw' => 0,                                   'contribution' => 0.0],
        ];
    }

    /** Danh sách workforce profiles — Admin / HR view */
    public function index(Request $request): View
    {
        $this->authorize('assessment.results');

        $maturityLevels = [
            'DIGITAL_BEGINNER', 'DIGITAL_AWARE', 'DIGITAL_PRACTITIONER',
            'DIGITAL_PROFESSIONAL', 'DIGITAL_LEADER',
        ];

        $total    = WorkforceProfile::count();
        $byLevel  = WorkforceProfile::selectRaw('tdwcf_maturity_level, count(*) as cnt')
            ->whereNotNull('tdwcf_maturity_level')
            ->groupBy('tdwcf_maturity_level')
            ->pluck('cnt', 'tdwcf_maturity_level');

        // Aggregate domain averages for the analytics bar chart
        $domainAvgs = WorkforceProfile::selectRaw('
            AVG(score_d1_digital_literacy) as d1,
            AVG(score_d2_data_literacy)    as d2,
            AVG(score_d3_ai_literacy)      as d3,
            AVG(score_d4_workflow)         as d4,
            AVG(score_d5_innovation)       as d5,
            AVG(score_d6_performance)      as d6
        ')->whereNotNull('tdwcf_score')->first();

        return view('assessment::workforce.index', compact(
            'maturityLevels', 'total', 'byLevel', 'domainAvgs'
        ));
    }

    /** Chi tiết 1 profile — Admin view */
    public function show(WorkforceProfile $workforceProfile): View
    {
        $this->authorize('assessment.results');

        $workforceProfile->load(['employee', 'certifications.definition', 'sandboxSessions']);
        $history = WorkforceProfileHistory::where('workforce_profile_id', $workforceProfile->id)
            ->orderByDesc('recorded_at')
            ->limit(20)
            ->get();

        $cgi                = CalculateCgiAction::run($workforceProfile);
        $trustBreakdown     = $this->buildTrustBreakdown($workforceProfile);
        $skillGapBenchmarks = $this->buildSkillGapBenchmarks($workforceProfile->tdwcf_maturity_level);

        // Job title requirements and AI recommendation
        $workforceProfile->employee?->loadMissing('jobTitle');

        $orgId                = TenantContext::getOrganizationId();
        $jobTitleRequirements = $workforceProfile->employee?->job_title_id
            ? JobTitleDomainRequirement::getForJobTitle($workforceProfile->employee->job_title_id, $orgId)
            : [];

        $recommendation = WorkforceRecommendation::withoutTenant()
            ->where('workforce_profile_id', $workforceProfile->id)
            ->where('is_stale', false)
            ->latest()
            ->first();

        return view('assessment::workforce.show', [
            'workforceProfile'    => $workforceProfile,
            'history'             => $history,
            'cgi'                 => $cgi,
            'trustBreakdown'      => $trustBreakdown,
            'skillGapBenchmarks'  => $skillGapBenchmarks,
            'jobTitleRequirements' => $jobTitleRequirements,
            'recommendation'      => $recommendation,
        ]);
    }

    /** Generate (or regenerate) AI recommendations for a workforce profile */
    public function generateRecommendation(Request $request, WorkforceProfile $workforceProfile): JsonResponse
    {
        $user = $request->user();

        if ($workforceProfile->user_id !== $user->id && ! $user->can('assessment.results')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        try {
            $rec = GenerateWorkforceRecommendationAction::run($workforceProfile, true);

            return response()->json([
                'success'          => true,
                'recommendations'  => $rec->recommendations,
                'generated_at'     => $rec->generated_at,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /** Dashboard tổng hợp năng lực theo đơn vị */
    public function unitDashboard(Request $request): View
    {
        $this->authorize('assessment.results');

        $orgId  = TenantContext::getOrganizationId();
        $deptId = $request->integer('dept_id') ?: null;

        $query = WorkforceProfile::with([
            'employee:id,full_name,department_id,job_title_id',
            'employee.department:id,name',
            'employee.jobTitle:id,name',
        ]);

        if ($deptId) {
            $query->whereHas('employee', fn($q) => $q->where('department_id', $deptId));
        }

        $profiles    = $query->get();
        $departments = Department::orderBy('name')->get(['id', 'name']);

        $maturityLevels = [
            'DIGITAL_BEGINNER', 'DIGITAL_AWARE', 'DIGITAL_PRACTITIONER',
            'DIGITAL_PROFESSIONAL', 'DIGITAL_LEADER',
        ];

        $maturityDist = collect($maturityLevels)->mapWithKeys(
            fn($lvl) => [$lvl => $profiles->where('tdwcf_maturity_level', $lvl)->count()]
        );

        $certifiedCount = $profiles->where('certifications_count', '>', 0)->count();
        $total          = $profiles->count();

        $stats = [
            'total'         => $total,
            'avg_tdwcf'     => $total ? round($profiles->avg('tdwcf_score'), 1) : 0,
            'avg_trust'     => $total ? round($profiles->avg('workforce_trust_score'), 1) : 0,
            'certified_pct' => $total ? round($certifiedCount / $total * 100, 1) : 0,
            'maturity_dist' => $maturityDist,
            'domain_avgs'   => [
                'D1' => $total ? round($profiles->avg('score_d1_digital_literacy'), 1) : 0,
                'D2' => $total ? round($profiles->avg('score_d2_data_literacy'), 1) : 0,
                'D3' => $total ? round($profiles->avg('score_d3_ai_literacy'), 1) : 0,
                'D4' => $total ? round($profiles->avg('score_d4_workflow'), 1) : 0,
                'D5' => $total ? round($profiles->avg('score_d5_innovation'), 1) : 0,
                'D6' => $total ? round($profiles->avg('score_d6_performance'), 1) : 0,
            ],
            'top10'           => $profiles->sortByDesc('workforce_trust_score')->take(10)->values(),
            'needs_attention' => $profiles->filter(fn($p) => ($p->tdwcf_score ?? 101) < 40)->values(),
        ];

        return view('assessment::workforce.unit-dashboard', compact(
            'profiles', 'departments', 'stats', 'deptId', 'maturityLevels'
        ));
    }

    /** API: danh sách profiles cho Tabulator */
    public function apiIndex(Request $request)
    {
        $this->authorize('assessment.results');

        $q = WorkforceProfile::query()
            ->with(['employee:id,user_id,full_name,department_id', 'employee.department:id,name', 'certifications:id,workforce_profile_id,status']);

        if ($level = $request->get('maturity_level')) {
            $q->where('tdwcf_maturity_level', $level);
        }
        if ($search = $request->get('search')) {
            $q->whereHas('employee', fn($e) => $e->where('full_name', 'like', "%{$search}%"));
        }

        $profiles = $q->orderByDesc('workforce_trust_score')
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'data' => $profiles->getCollection()->map(fn($p) => [
                'id'                     => $p->id,
                'employee_name'          => $p->employee?->full_name ?? '—',
                'department'             => $p->employee?->department?->name ?? null,
                'tdwcf_maturity_level'   => $p->tdwcf_maturity_level,
                'tdwcf_score'            => $p->tdwcf_score,
                'ai_readiness_score'     => $p->ai_readiness_score,
                'workforce_trust_score'  => $p->workforce_trust_score,
                'sandbox_sessions_total' => $p->sandbox_sessions_total ?? 0,
                'last_assessed_at'       => $p->tdwcf_assessed_at?->format('d/m/Y'),
            ])->values(),
            'last_page' => $profiles->lastPage(),
            'total'     => $profiles->total(),
        ]);
    }
}
