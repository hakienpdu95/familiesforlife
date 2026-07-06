<?php

namespace Modules\Assessment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Rap2hpoutre\FastExcel\FastExcel;
use Rap2hpoutre\FastExcel\SheetCollection;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\Enums\Format;
use Modules\Assessment\Actions\CalculateCgiAction;
use Modules\Assessment\Models\JobTitleDomainRequirement;
use Modules\Assessment\Models\WorkforceProfile;
use Modules\Assessment\Models\WorkforceRecommendation;
use Modules\Assessment\Models\WorkforceCertification;

class WorkforceExportController extends Controller
{
    private const DOMAIN_FIELDS = [
        'D1' => ['field' => 'score_d1_digital_literacy', 'label' => 'D1 — Năng lực số'],
        'D2' => ['field' => 'score_d2_data_literacy',    'label' => 'D2 — Dữ liệu'],
        'D3' => ['field' => 'score_d3_ai_literacy',      'label' => 'D3 — AI'],
        'D4' => ['field' => 'score_d4_workflow',         'label' => 'D4 — Quy trình'],
        'D5' => ['field' => 'score_d5_innovation',       'label' => 'D5 — Đổi mới'],
        'D6' => ['field' => 'score_d6_performance',      'label' => 'D6 — Hiệu suất'],
    ];

    private const LEVEL_LABELS = [
        'DIGITAL_BEGINNER'     => 'Khởi đầu',
        'DIGITAL_AWARE'        => 'Nhận thức',
        'DIGITAL_PRACTITIONER' => 'Thực hành',
        'DIGITAL_PROFESSIONAL' => 'Chuyên nghiệp',
        'DIGITAL_LEADER'       => 'Dẫn dắt',
    ];

    /** GET /dashboard/workforce/export/organization */
    public function organizationReport(Request $request)
    {
        $this->authorize('assessment.results');

        $orgId = TenantContext::getOrganizationId();
        $orgName = $request->user()?->organization?->name ?? 'Organization';

        $profiles = WorkforceProfile::with([
            'employee' => fn ($q) => $q->withoutGlobalScopes()->select('id', 'full_name', 'job_title_id', 'department_id', 'snap_job_title', 'snap_dept_name'),
        ])
            ->orderByDesc('workforce_trust_score')
            ->get();

        $sheets = new SheetCollection([
            'Tổng quan'              => $this->buildSummarySheet($profiles, $orgName),
            'Danh sách nhân viên'    => $this->buildProfilesSheet($profiles),
            'Phân tích Skill Gap'    => $this->buildSkillGapSheet($profiles),
            'Leaderboard'            => $this->buildLeaderboardSheet($profiles),
        ]);

        $filename = 'BaoCao_NangLucSo_' . now()->format('Ymd_Hi') . '.xlsx';

        return (new FastExcel($sheets))->download($filename);
    }

    /** GET /dashboard/workforce/{workforceProfile}/export */
    public function profileReport(WorkforceProfile $workforceProfile)
    {
        $this->authorize('assessment.results');

        $workforceProfile->load([
            'employee' => fn ($q) => $q->withoutGlobalScopes(),
            'employee.jobTitle',
            'certifications.definition',
        ]);

        $recommendation = WorkforceRecommendation::withoutTenant()
            ->where('workforce_profile_id', $workforceProfile->id)
            ->where('is_stale', false)
            ->latest()
            ->first();

        $cgi = CalculateCgiAction::run($workforceProfile);

        $sheets = new SheetCollection([
            'Hồ sơ năng lực'      => $this->buildIndividualSheet($workforceProfile, $cgi),
            'Gợi ý phát triển'    => $this->buildRecommendationsSheet($workforceProfile, $recommendation),
        ]);

        $safeName = preg_replace('/[^\w]/u', '_', $workforceProfile->employee?->full_name ?? 'profile');
        $filename = 'HoSo_' . $safeName . '_' . now()->format('Ymd') . '.xlsx';

        return (new FastExcel($sheets))->download($filename);
    }

    // ── Sheet builders ────────────────────────────────────────────────────────

    private function buildSummarySheet($profiles, string $orgName): \Illuminate\Support\Collection
    {
        $total   = $profiles->count();
        $avgTdwcf = $total ? round($profiles->avg('tdwcf_score'), 2) : 0;
        $avgAi    = $total ? round($profiles->avg('ai_readiness_score'), 2) : 0;
        $avgTrust = $total ? round($profiles->avg('workforce_trust_score'), 2) : 0;

        $rows = collect();

        // Header meta
        $rows->push(['Mục'     => 'Báo cáo năng lực số tổ chức',          'Giá trị' => $orgName]);
        $rows->push(['Mục'     => 'Ngày xuất báo cáo',                    'Giá trị' => now()->format('d/m/Y H:i')]);
        $rows->push(['Mục'     => '',                                     'Giá trị' => '']);

        // KPIs
        $rows->push(['Mục' => '── CHỈ SỐ TỔNG HỢP ──',                   'Giá trị' => '']);
        $rows->push(['Mục' => 'Tổng số hồ sơ năng lực',                  'Giá trị' => $total]);
        $rows->push(['Mục' => 'TDWCF trung bình toàn tổ chức',           'Giá trị' => $avgTdwcf]);
        $rows->push(['Mục' => 'AI Readiness trung bình',                 'Giá trị' => $avgAi]);
        $rows->push(['Mục' => 'Workforce Trust Score trung bình',        'Giá trị' => $avgTrust]);
        $rows->push(['Mục' => '',                                         'Giá trị' => '']);

        // Maturity distribution
        $rows->push(['Mục' => '── PHÂN BỔ CẤP ĐỘ TRƯỞNG THÀNH ──',       'Giá trị' => '']);
        foreach (self::LEVEL_LABELS as $code => $label) {
            $count = $profiles->where('tdwcf_maturity_level', $code)->count();
            $pct   = $total ? round($count / $total * 100) : 0;
            $rows->push(['Mục' => $label, 'Giá trị' => "{$count} người ({$pct}%)"]);
        }
        $rows->push(['Mục' => '', 'Giá trị' => '']);

        // Domain averages
        $rows->push(['Mục' => '── ĐIỂM TRUNG BÌNH 6 NĂNG LỰC ──',         'Giá trị' => '']);
        foreach (self::DOMAIN_FIELDS as $code => ['field' => $field, 'label' => $label]) {
            $avg = $total ? round($profiles->avg($field), 2) : 0;
            $rows->push(['Mục' => $label, 'Giá trị' => $avg]);
        }

        return $rows;
    }

    private function buildProfilesSheet($profiles): \Illuminate\Support\Collection
    {
        return $profiles->map(function ($p, $i) {
            $row = [
                'STT'                   => $i + 1,
                'Họ tên'                => $p->employee?->full_name ?? '—',
                'Chức danh'             => $p->employee?->snap_job_title ?? '—',
                'Phòng ban'             => $p->employee?->snap_dept_name ?? '—',
            ];

            foreach (self::DOMAIN_FIELDS as $code => ['field' => $field, 'label' => $label]) {
                $row[$label] = round((float) ($p->{$field} ?? 0), 1);
            }

            $row['TDWCF']            = round((float) ($p->tdwcf_score ?? 0), 2);
            $row['AI Readiness']     = round((float) ($p->ai_readiness_score ?? 0), 2);
            $row['Trust Score']      = round((float) ($p->workforce_trust_score ?? 0), 2);
            $row['Cấp độ']           = self::LEVEL_LABELS[$p->tdwcf_maturity_level] ?? ($p->tdwcf_maturity_level ?? '—');
            $row['Đánh giá gần nhất'] = $p->tdwcf_assessed_at?->format('d/m/Y') ?? '—';

            return $row;
        });
    }

    private function buildSkillGapSheet($profiles): \Illuminate\Support\Collection
    {
        return $profiles->map(function ($p) {
            $reqs = JobTitleDomainRequirement::getForJobTitle(
                $p->employee?->job_title_id,
                $p->organization_id,
            );

            $row = [
                'Họ tên'    => $p->employee?->full_name ?? '—',
                'Chức danh' => $p->employee?->snap_job_title ?? '—',
            ];

            $totalGap   = 0;
            $gapDomains = 0;

            foreach (self::DOMAIN_FIELDS as $code => ['field' => $field, 'label' => $label]) {
                $current  = round((float) ($p->{$field} ?? 0), 1);
                $required = round((float) ($reqs[$code] ?? 0), 1);
                $gap      = round(max(0, $required - $current), 1);

                $row["{$code} Hiện tại"]   = $current;
                $row["{$code} Yêu cầu"]    = $required;
                $row["{$code} Khoảng cách"] = $gap > 0 ? "-{$gap}" : '✓';

                if ($gap > 0) {
                    $totalGap += $gap;
                    $gapDomains++;
                }
            }

            $row['Tổng khoảng cách'] = $totalGap > 0 ? $totalGap : 0;
            $row['Số domain thiếu']  = $gapDomains;

            return $row;
        });
    }

    private function buildLeaderboardSheet($profiles): \Illuminate\Support\Collection
    {
        return $profiles->sortByDesc('workforce_trust_score')->values()->map(function ($p, $i) {
            // CGI: get from history
            $history = \Modules\Assessment\Models\WorkforceProfileHistory::where('workforce_profile_id', $p->id)
                ->where('event_type', 'assessment')
                ->whereNotNull('tdwcf_score_before')
                ->oldest('recorded_at')
                ->first();

            $cgi = null;
            if ($history && $history->tdwcf_score_before > 0) {
                $cgi = round(($p->tdwcf_score - $history->tdwcf_score_before) / $history->tdwcf_score_before * 100, 1);
            }

            return [
                'Hạng'        => $i + 1,
                'Họ tên'      => $p->employee?->full_name ?? '—',
                'Chức danh'   => $p->employee?->snap_job_title ?? '—',
                'TDWCF'       => round((float) ($p->tdwcf_score ?? 0), 2),
                'Trust Score' => round((float) ($p->workforce_trust_score ?? 0), 2),
                'AI Readiness' => round((float) ($p->ai_readiness_score ?? 0), 2),
                'CGI (%)'     => $cgi !== null ? $cgi : '—',
                'Cấp độ'      => self::LEVEL_LABELS[$p->tdwcf_maturity_level] ?? '—',
            ];
        });
    }

    private function buildIndividualSheet(WorkforceProfile $p, ?float $cgi): \Illuminate\Support\Collection
    {
        $reqs = JobTitleDomainRequirement::getForJobTitle(
            $p->employee?->job_title_id,
            $p->organization_id,
        );

        $rows = collect();

        $rows->push(['Mục' => 'Họ tên',      'Giá trị' => $p->employee?->full_name ?? '—', 'Yêu cầu vị trí' => '']);
        $rows->push(['Mục' => 'Chức danh',   'Giá trị' => $p->employee?->snap_job_title ?? '—', 'Yêu cầu vị trí' => '']);
        $rows->push(['Mục' => 'Phòng ban',   'Giá trị' => $p->employee?->snap_dept_name ?? '—', 'Yêu cầu vị trí' => '']);
        $rows->push(['Mục' => 'Cấp độ',      'Giá trị' => self::LEVEL_LABELS[$p->tdwcf_maturity_level] ?? '—', 'Yêu cầu vị trí' => '']);
        $rows->push(['Mục' => '',            'Giá trị' => '', 'Yêu cầu vị trí' => '']);
        $rows->push(['Mục' => '── ĐIỂM TDWCF ──', 'Giá trị' => '', 'Yêu cầu vị trí' => '']);
        $rows->push(['Mục' => 'Điểm tổng hợp',    'Giá trị' => round((float)($p->tdwcf_score ?? 0), 2), 'Yêu cầu vị trí' => '']);
        $rows->push(['Mục' => 'AI Readiness',      'Giá trị' => round((float)($p->ai_readiness_score ?? 0), 2), 'Yêu cầu vị trí' => '']);
        $rows->push(['Mục' => 'Trust Score',       'Giá trị' => round((float)($p->workforce_trust_score ?? 0), 2), 'Yêu cầu vị trí' => '']);
        $rows->push(['Mục' => 'CGI (%)',            'Giá trị' => $cgi !== null ? round($cgi, 1) : '—', 'Yêu cầu vị trí' => '']);
        $rows->push(['Mục' => '',                   'Giá trị' => '', 'Yêu cầu vị trí' => '']);
        $rows->push(['Mục' => '── 6 NĂNG LỰC ──',  'Giá trị' => '', 'Yêu cầu vị trí' => '']);

        foreach (self::DOMAIN_FIELDS as $code => ['field' => $field, 'label' => $label]) {
            $current  = round((float)($p->{$field} ?? 0), 1);
            $required = round((float)($reqs[$code] ?? 0), 1);
            $gap      = round(max(0, $required - $current), 1);

            $rows->push([
                'Mục'              => $label,
                'Giá trị'          => $current,
                'Yêu cầu vị trí'   => $required > 0 ? ($gap > 0 ? "Cần: {$required} (gap: -{$gap})" : "Đạt (yêu cầu: {$required})") : '—',
            ]);
        }

        $rows->push(['Mục' => '', 'Giá trị' => '', 'Yêu cầu vị trí' => '']);
        $rows->push(['Mục' => '── CHỨNG CHỈ ──', 'Giá trị' => '', 'Yêu cầu vị trí' => '']);

        $certCount = $p->certifications?->count() ?? 0;
        $rows->push(['Mục' => 'Số chứng chỉ đang hoạt động', 'Giá trị' => $certCount, 'Yêu cầu vị trí' => '']);

        foreach ($p->certifications ?? collect() as $cert) {
            if ($cert->status !== 'active') continue;
            $rows->push([
                'Mục'            => $cert->definition?->name ?? $cert->cert_name ?? '—',
                'Giá trị'        => $cert->issued_at?->format('d/m/Y') ?? '—',
                'Yêu cầu vị trí' => $cert->expires_at ? 'HSD: ' . $cert->expires_at->format('d/m/Y') : 'Không hết hạn',
            ]);
        }

        $rows->push(['Mục' => '', 'Giá trị' => '', 'Yêu cầu vị trí' => '']);
        $rows->push(['Mục' => 'Mục tiêu nghề nghiệp', 'Giá trị' => $p->career_goal ?? '—', 'Yêu cầu vị trí' => '']);
        $rows->push(['Mục' => 'Lộ trình học tập',      'Giá trị' => $p->current_learning_path ?? '—', 'Yêu cầu vị trí' => '']);

        return $rows;
    }

    private function buildRecommendationsSheet(WorkforceProfile $p, ?WorkforceRecommendation $rec): \Illuminate\Support\Collection
    {
        if (! $rec || empty($rec->recommendations)) {
            return collect([
                ['Thông tin' => 'Chưa có gợi ý phát triển. Vui lòng tạo gợi ý từ trang hồ sơ.'],
            ]);
        }

        return collect($rec->recommendations)->map(fn ($r) => [
            'Ưu tiên'          => $r['priority'] ?? '—',
            'Năng lực'         => $r['domain'] ?? '—',
            'Hành động'        => $r['action'] ?? '—',
            'Loại tài nguyên'  => match ($r['resource_type'] ?? '') {
                'course'        => 'Khoá học',
                'sandbox'       => 'Thực hành sandbox',
                'certification' => 'Chứng chỉ',
                'practice'      => 'Thực hành',
                default         => $r['resource_type'] ?? '—',
            },
            'Tài nguyên'       => $r['resource_name'] ?? '—',
            'Thời gian (tuần)' => $r['estimated_weeks'] ?? '—',
            'Lý do'            => $r['why'] ?? '—',
        ]);
    }

    // ── PDF exports ───────────────────────────────────────────────────────────

    /** GET /dashboard/workforce/pdf/organization */
    public function organizationPdf(Request $request)
    {
        $this->authorize('assessment.results');

        $orgName  = $request->user()?->organization?->name ?? 'Organization';
        $orgId    = TenantContext::getOrganizationId();

        $profiles = WorkforceProfile::with([
            'employee' => fn ($q) => $q->withoutGlobalScopes()->select('id', 'full_name', 'job_title_id', 'snap_job_title', 'snap_dept_name'),
        ])
            ->orderByDesc('workforce_trust_score')
            ->get();

        $total    = $profiles->count();
        $avgTdwcf = $total ? round($profiles->avg('tdwcf_score'), 1) : 0;
        $avgAi    = $total ? round($profiles->avg('ai_readiness_score'), 1) : 0;
        $avgTrust = $total ? round($profiles->avg('workforce_trust_score'), 1) : 0;

        $levelLabels = [
            'DIGITAL_BEGINNER'     => 'Khởi đầu',
            'DIGITAL_AWARE'        => 'Nhận thức',
            'DIGITAL_PRACTITIONER' => 'Thực hành',
            'DIGITAL_PROFESSIONAL' => 'Chuyên nghiệp',
            'DIGITAL_LEADER'       => 'Dẫn dắt',
        ];

        $levelDistribution = collect(array_keys($levelLabels))->mapWithKeys(function ($lvl) use ($profiles, $total, $levelLabels) {
            $count = $profiles->where('tdwcf_maturity_level', $lvl)->count();
            return [$lvl => [
                'label' => $levelLabels[$lvl],
                'count' => $count,
                'pct'   => $total ? (int) round($count / $total * 100) : 0,
            ]];
        });

        $domainAvgs = [
            'D1 — Số cơ bản'   => round($profiles->avg('score_d1_digital_literacy') ?? 0, 1),
            'D2 — Dữ liệu'     => round($profiles->avg('score_d2_data_literacy')    ?? 0, 1),
            'D3 — AI'           => round($profiles->avg('score_d3_ai_literacy')      ?? 0, 1),
            'D4 — Quy trình'   => round($profiles->avg('score_d4_workflow')         ?? 0, 1),
            'D5 — Đổi mới'     => round($profiles->avg('score_d5_innovation')       ?? 0, 1),
            'D6 — Hiệu suất'   => round($profiles->avg('score_d6_performance')      ?? 0, 1),
        ];

        $leaderboard = $profiles->sortByDesc('workforce_trust_score')->take(5)->values();

        // Skill gap per profile
        $skillGaps = $profiles->map(function ($p) {
            $reqs = JobTitleDomainRequirement::getForJobTitle($p->employee?->job_title_id, $p->organization_id);
            $fields = ['score_d1_digital_literacy','score_d2_data_literacy','score_d3_ai_literacy',
                       'score_d4_workflow','score_d5_innovation','score_d6_performance'];
            $codes  = ['D1','D2','D3','D4','D5','D6'];
            $gaps = []; $totalGap = 0;
            foreach (array_combine($codes, $fields) as $code => $field) {
                $cur = (float)($p->{$field} ?? 0);
                $req = (float)($reqs[$code] ?? 0);
                $g   = round(max(0, $req - $cur), 0);
                $gaps[$code] = $g;
                $totalGap += $g;
            }
            return [
                'name'      => $p->employee?->full_name ?? '—',
                'job_title' => $p->employee?->snap_job_title ?? '—',
                'gaps'      => $gaps,
                'total_gap' => $totalGap,
            ];
        });

        $filename = 'BaoCao_NangLucSo_' . now()->format('Ymd_Hi') . '.pdf';

        return Pdf::view('assessment::pdf.workforce-org-report', compact(
            'orgName', 'total', 'avgTdwcf', 'avgAi', 'avgTrust',
            'profiles', 'levelDistribution', 'domainAvgs', 'leaderboard', 'skillGaps'
        ))
            ->format(Format::A4)
            ->download($filename);
    }

    /** GET /dashboard/workforce/{workforceProfile}/pdf */
    public function profilePdf(WorkforceProfile $workforceProfile)
    {
        $this->authorize('assessment.results');

        $workforceProfile->load([
            'employee'              => fn ($q) => $q->withoutGlobalScopes(),
            'employee.jobTitle',
            'certifications.definition',
        ]);

        $employee   = $workforceProfile->employee;
        $profile    = $workforceProfile;
        $cgi        = CalculateCgiAction::run($profile);
        $certifications = $profile->certifications ?? collect();
        $certCount  = $certifications->where('status', 'active')->count();

        $jobTitleRequirements = JobTitleDomainRequirement::getForJobTitle(
            $employee?->job_title_id,
            $profile->organization_id,
        );

        $recommendation = WorkforceRecommendation::withoutTenant()
            ->where('workforce_profile_id', $profile->id)
            ->where('is_stale', false)
            ->latest()
            ->first();

        $trustBreakdown = $this->buildTrustBreakdown($profile);

        $levelLabels = [
            'DIGITAL_BEGINNER'     => 'Khởi đầu — Digital Beginner',
            'DIGITAL_AWARE'        => 'Nhận thức — Digital Aware',
            'DIGITAL_PRACTITIONER' => 'Thực hành — Digital Practitioner',
            'DIGITAL_PROFESSIONAL' => 'Chuyên nghiệp — Digital Professional',
            'DIGITAL_LEADER'       => 'Dẫn dắt — Digital Leader',
        ];
        $levelLabel = $levelLabels[$profile->tdwcf_maturity_level] ?? ($profile->tdwcf_maturity_level ?? '—');

        $safeName = preg_replace('/[^\w]/u', '_', $employee?->full_name ?? 'profile');
        $filename = 'HoSo_' . $safeName . '_' . now()->format('Ymd') . '.pdf';

        return Pdf::view('assessment::pdf.workforce-profile-report', compact(
            'profile', 'employee', 'cgi', 'certifications', 'certCount',
            'jobTitleRequirements', 'recommendation', 'trustBreakdown', 'levelLabel'
        ))
            ->format(Format::A4)
            ->download($filename);
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
            ['label' => 'TDWCF Score', 'weight' => 30, 'raw' => $profile->tdwcf_score ?? 0,        'contribution' => round(($profile->tdwcf_score ?? 0) * 0.30, 1)],
            ['label' => 'Chứng nhận',  'weight' => 25, 'raw' => $certScore,                         'contribution' => round($certScore * 0.25, 1)],
            ['label' => 'KPI',         'weight' => 20, 'raw' => $profile->kpi_achievement_avg ?? 0, 'contribution' => round(($profile->kpi_achievement_avg ?? 0) * 0.20, 1)],
            ['label' => 'Sandbox',     'weight' => 15, 'raw' => $profile->sandbox_score_avg ?? 0,   'contribution' => round(($profile->sandbox_score_avg ?? 0) * 0.15, 1)],
            ['label' => 'Portfolio',   'weight' => 10, 'raw' => 0,                                   'contribution' => 0.0],
        ];
    }
}
