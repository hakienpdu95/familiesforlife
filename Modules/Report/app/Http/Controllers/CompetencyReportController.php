<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Assessment\Models\WorkforceProfile;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompetencyReportController extends Controller
{
    public function index(): View
    {
        $orgId = TenantContext::getOrganizationId();

        $profiles = WorkforceProfile::where('organization_id', $orgId)
            ->with(['employee:id,full_name,department_id', 'employee.department:id,name'])
            ->get();

        $maturityDist = $profiles->groupBy('tdwcf_maturity_level')->map->count();

        $domainAvgs = [
            'D1' => round($profiles->avg('score_d1_digital_literacy') ?? 0, 1),
            'D2' => round($profiles->avg('score_d2_data_literacy') ?? 0, 1),
            'D3' => round($profiles->avg('score_d3_ai_literacy') ?? 0, 1),
            'D4' => round($profiles->avg('score_d4_workflow') ?? 0, 1),
            'D5' => round($profiles->avg('score_d5_innovation') ?? 0, 1),
            'D6' => round($profiles->avg('score_d6_performance') ?? 0, 1),
        ];

        $orgAvgTrust = round($profiles->avg('workforce_trust_score') ?? 0, 1);
        $orgAvgTdwcf = round($profiles->avg('tdwcf_score') ?? 0, 1);
        $totalCerts  = $profiles->sum('certifications_count');

        return view('report::competency.index', compact(
            'profiles', 'maturityDist', 'domainAvgs', 'orgAvgTrust', 'orgAvgTdwcf', 'totalCerts'
        ));
    }

    public function heatmap(): View
    {
        $orgId = TenantContext::getOrganizationId();

        $byDept = WorkforceProfile::where('organization_id', $orgId)
            ->with('employee.department:id,name')
            ->get()
            ->groupBy(fn($p) => $p->employee?->department?->name ?? 'Chưa phân công');

        $heatmap = $byDept->map(fn($group) => [
            'count' => $group->count(),
            'D1'    => round($group->avg('score_d1_digital_literacy') ?? 0, 1),
            'D2'    => round($group->avg('score_d2_data_literacy') ?? 0, 1),
            'D3'    => round($group->avg('score_d3_ai_literacy') ?? 0, 1),
            'D4'    => round($group->avg('score_d4_workflow') ?? 0, 1),
            'D5'    => round($group->avg('score_d5_innovation') ?? 0, 1),
            'D6'    => round($group->avg('score_d6_performance') ?? 0, 1),
            'trust' => round($group->avg('workforce_trust_score') ?? 0, 1),
        ]);

        // Overall averages for footer row
        $allProfiles = $byDept->flatten(1);
        $overall = [
            'count' => $allProfiles->count(),
            'D1'    => round($allProfiles->avg('score_d1_digital_literacy') ?? 0, 1),
            'D2'    => round($allProfiles->avg('score_d2_data_literacy') ?? 0, 1),
            'D3'    => round($allProfiles->avg('score_d3_ai_literacy') ?? 0, 1),
            'D4'    => round($allProfiles->avg('score_d4_workflow') ?? 0, 1),
            'D5'    => round($allProfiles->avg('score_d5_innovation') ?? 0, 1),
            'D6'    => round($allProfiles->avg('score_d6_performance') ?? 0, 1),
            'trust' => round($allProfiles->avg('workforce_trust_score') ?? 0, 1),
        ];

        return view('report::competency.heatmap', compact('heatmap', 'overall'));
    }

    public function skillGap(): View
    {
        $orgId = TenantContext::getOrganizationId();

        $profiles = WorkforceProfile::where('organization_id', $orgId)
            ->with('employee:id,full_name,department_id', 'employee.department:id,name')
            ->get();

        $benchmarks = [
            'DIGITAL_BEGINNER'     => ['D1'=>30,'D2'=>20,'D3'=>10,'D4'=>20,'D5'=>10,'D6'=>20],
            'DIGITAL_AWARE'        => ['D1'=>45,'D2'=>35,'D3'=>25,'D4'=>35,'D5'=>20,'D6'=>30],
            'DIGITAL_PRACTITIONER' => ['D1'=>60,'D2'=>55,'D3'=>45,'D4'=>55,'D5'=>40,'D6'=>50],
            'DIGITAL_PROFESSIONAL' => ['D1'=>75,'D2'=>70,'D3'=>65,'D4'=>70,'D5'=>60,'D6'=>65],
            'DIGITAL_LEADER'       => ['D1'=>90,'D2'=>85,'D3'=>80,'D4'=>85,'D5'=>80,'D6'=>80],
        ];

        $gaps = $profiles->map(function ($p) use ($benchmarks) {
            $nextLevel = $this->nextLevel($p->tdwcf_maturity_level);
            $bench     = $benchmarks[$nextLevel] ?? null;
            if (! $bench) return null;

            $gapValues = [
                'D1' => max(0, $bench['D1'] - ($p->score_d1_digital_literacy ?? 0)),
                'D2' => max(0, $bench['D2'] - ($p->score_d2_data_literacy ?? 0)),
                'D3' => max(0, $bench['D3'] - ($p->score_d3_ai_literacy ?? 0)),
                'D4' => max(0, $bench['D4'] - ($p->score_d4_workflow ?? 0)),
                'D5' => max(0, $bench['D5'] - ($p->score_d5_innovation ?? 0)),
                'D6' => max(0, $bench['D6'] - ($p->score_d6_performance ?? 0)),
            ];

            return [
                'profile'   => $p,
                'nextLevel' => $nextLevel,
                'gaps'      => $gapValues,
                'totalGap'  => array_sum($gapValues),
            ];
        })->filter()->sortByDesc('totalGap')->values();

        $departments = $profiles->map(fn($p) => $p->employee?->department?->name)
            ->filter()->unique()->sort()->values();

        return view('report::competency.skill-gap', compact('gaps', 'departments'));
    }

    public function trends(): View
    {
        $orgId = TenantContext::getOrganizationId();

        $trendData = DB::table('workforce_profile_histories')
            ->join('workforce_profiles', 'workforce_profile_histories.workforce_profile_id', '=', 'workforce_profiles.id')
            ->where('workforce_profiles.organization_id', $orgId)
            ->where('workforce_profile_histories.event_type', 'assessment')
            ->where('workforce_profile_histories.recorded_at', '>=', now()->subMonths(12))
            ->selectRaw("DATE_FORMAT(workforce_profile_histories.recorded_at, '%Y-%m') as month, AVG(workforce_profile_histories.tdwcf_score_after) as avg_score, COUNT(*) as assessments")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('report::competency.trends', compact('trendData'));
    }

    public function export(): StreamedResponse
    {
        $orgId = TenantContext::getOrganizationId();

        $rows = WorkforceProfile::where('organization_id', $orgId)
            ->with('employee:id,full_name,department_id,job_title_id', 'employee.department:id,name')
            ->get()
            ->map(fn($p) => [
                'Họ tên'       => $p->employee?->full_name ?? '—',
                'Phòng ban'    => $p->employee?->department?->name ?? '—',
                'Maturity'     => $p->tdwcf_maturity_level ?? '—',
                'TDWCF Score'  => $p->tdwcf_score,
                'Trust Score'  => $p->workforce_trust_score,
                'D1 Số hóa'    => $p->score_d1_digital_literacy,
                'D2 Dữ liệu'   => $p->score_d2_data_literacy,
                'D3 AI'        => $p->score_d3_ai_literacy,
                'D4 Quy trình' => $p->score_d4_workflow,
                'D5 Đổi mới'   => $p->score_d5_innovation,
                'D6 Hiệu suất' => $p->score_d6_performance,
                'Chứng nhận'   => $p->certifications_count,
            ]);

        return (new FastExcel($rows))->download('competency-'.now()->format('Ymd').'.xlsx');
    }

    private function nextLevel(string $current): string
    {
        return match($current) {
            'DIGITAL_BEGINNER'     => 'DIGITAL_AWARE',
            'DIGITAL_AWARE'        => 'DIGITAL_PRACTITIONER',
            'DIGITAL_PRACTITIONER' => 'DIGITAL_PROFESSIONAL',
            default                => 'DIGITAL_LEADER',
        };
    }
}
