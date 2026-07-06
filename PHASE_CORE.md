# PHASE_CORE — Hoàn thiện hệ thống cốt lõi

> **Phiên bản:** 3.0 | **Ngày cập nhật:** 2026-07-02
> **Ngôn ngữ / Stack:** PHP 8.4 · Laravel 13 · Blade + Alpine.js · TailwindCSS 4 + DaisyUI 5
> **Không sử dụng:** Python, OLLAMA, n8n, ChromaDB, PhoBERT, xAPI, e-Office

---

## 1. Tổng quan — Trạng thái hệ thống hiện tại

### 1.1 Đã hoàn thành (không cần triển khai thêm)

| Hạng mục | Trạng thái | File / Route chính |
|---|---|---|
| WorkforceProfile + 6 domains D1–D6 | ✅ Hoàn chỉnh | `Modules/Assessment/app/Models/WorkforceProfile.php` |
| Digital Twin dashboard (`/dashboard/workforce/me`) | ✅ Hoàn chỉnh | `resources/views/workforce/me.blade.php` |
| SVG radar chart (server-rendered PHP math) | ✅ Hoàn chỉnh | `me.blade.php` line 172–237 |
| 7 KPI stat cards (TDWCF, Trust, AI, CGI, Impact, KPI, Sandbox) | ✅ Hoàn chỉnh | `me.blade.php` line 113–159 |
| Skill gap vs job title + vs next maturity level | ✅ Hoàn chỉnh | `me.blade.php` line 370–519 |
| Score trend chart (ECharts, dữ liệu từ `$scoreHistory`) | ✅ Hoàn chỉnh | `me.blade.php` line 814–945 |
| AI recommendations (Alpine.js AJAX, provider-agnostic) | ✅ Hoàn chỉnh | `me.blade.php` line 522+ |
| Excel export tổ chức (4 sheets) — FastExcel | ✅ Hoàn chỉnh | `WorkforceExportController::organizationReport()` |
| PDF export tổ chức + cá nhân — spatie/laravel-pdf | ✅ Hoàn chỉnh | `WorkforceExportController::{organizationPdf,profilePdf}()` |
| Campaign CRUD + workspace người dùng | ✅ Hoàn chỉnh | `CampaignAdminController`, `CampaignController` |
| Campaign results page + anonymous reveal | ✅ Hoàn chỉnh | `campaigns/org/results.blade.php` |
| `CampaignInviteNotification` (email, queued) | ✅ Hoàn chỉnh | `Notifications/CampaignInviteNotification.php` |
| Sandbox system (admin + user + auto-score) | ✅ Hoàn chỉnh | `SandboxScoringService`, routes `backend.sandbox.*` |
| Certification admin + issue + revoke | ✅ Hoàn chỉnh | `CertificationAdminController`, routes `backend.certs-admin.*` |
| WorkforceCertification (`qr_code_url`, composite_score formula) | ✅ Field có sẵn | `WorkforceCertification.php` |
| CareerPathwayStep admin + user view + check-level API | ✅ Hoàn chỉnh | `CareerLevelService`, routes `backend.career-pathway*` |
| AI Impact tracking + import CSV | ✅ Hoàn chỉnh | routes `backend.ai-impact.*` |
| Passport list + detail + visibility + share token + PDF | ✅ Hoàn chỉnh | `PassportController`, routes `passport.*` |
| Public passport via share token (`/p/{token}`) | ✅ Hoàn chỉnh | `PublicPassportController`, route `passport.public` |
| SnapshotPassportEntryJob (5 bước: entry, domain, cert, impact, sandbox) | ✅ Hoàn chỉnh | `Jobs/SnapshotPassportEntryJob.php` |
| CreateCampaignPassportEntryJob (idempotent) | ✅ Hoàn chỉnh | `Jobs/CreateCampaignPassportEntryJob.php` |
| Event-Listener pipeline (AssessmentCompleted → WorkforceProfile) | ✅ Hoàn chỉnh | `EventServiceProvider.php`, 6 listeners |
| TrustScore formula (TDWCF×30% + Cert×25% + KPI×20% + Sandbox×15% + Portfolio×10%) | ✅ Hoàn chỉnh | `WorkforceProfile::recalculateTrustScore()` |
| OrgMembershipService (deactivate, deactivateLate, suspend) | ✅ Hoàn chỉnh | `Services/OrgMembershipService.php` |
| KcItem full CRUD (tags, attachments, feedback, view logs, versions, access control) | ✅ Hoàn chỉnh | `Modules/KcItem/` |
| Survey module (5-layer validation, scoring, tokens, webhooks, responses export) | ✅ Hoàn chỉnh | `Modules/Survey/` |
| Report module (HR headcount/leave/recruitment/performance, Sales, Project, KPI) | ✅ Partial | `Modules/Report/` |
| Workflow Automation (builder, executor, human-in-loop, cooldown, conditions) | ✅ Hoàn chỉnh | `Modules/WorkflowAutomation/` |
| PerformanceReview + FinalizeReviewAction → SyncWorkforceProfileListener | ✅ Hoàn chỉnh | `Modules/PerformanceReview/` |
| WorkforceRecommendation context hash + stale check | ✅ Hoàn chỉnh | `WorkforceRecommendation::isStillFresh()` |

### 1.2 Còn thiếu — Phạm vi PHASE_CORE

| ID | Hạng mục | Độ ưu tiên |
|---|---|---|
| C1 | Report Module: Báo cáo năng lực số tổ chức | P1 — Cao |
| C2 | Dashboard cấp đơn vị (unit-level workforce view) | P1 — Cao |
| C3 | Campaign: Bulk invite + Reminder scheduler + Export + Unit breakdown | P2 — Trung |
| C4 | KcItem ↔ RoadmapMilestone: Pivot + Admin UI | P2 — Trung |
| C5 | Learning Progress Tracking per user | P2 — Trung |
| C6 | KcItem metadata: `domain_code` + `difficulty` | P3 — Thấp |

---

## 2. Trình tự triển khai — Thứ tự logic và ổn định

### 2.1 Nguyên tắc sắp xếp

**Quy tắc cứng (bắt buộc theo thứ tự):**
- Migration luôn chạy trước khi thêm relationship vào model
- Model relationship phải có trước khi controller dùng eager-load
- C6 phải xong trước C4 (C4 admin UI lọc KcItem theo `domain_code`)
- C4 phải xong trước C5 (`kc_learning_progress.roadmap_milestone_id` FK vào `roadmap_milestones`)

**Quy tắc mềm (tối ưu giá trị):**
- Ưu tiên các item không cần migration trước — deploy ngay, không rủi ro
- Nhóm các item cùng module vào một sprint — giảm context switch
- Mỗi sprint phải deploy được độc lập và không phá vỡ tính năng hiện có

**Sơ đồ dependency:**
```
C6 (migration kc_items) ──► C4 (migration pivot + model + admin)
                                       │
                                       ▼
                              C5 (migration kc_learning_progress + model + routes)
                                       │
                                       ▼
                              Hiển thị tiến độ trong Career Pathway (view update)

C2 (method + route + view)  ── độc lập, không dependency
C1 (controller + 4 views)   ── độc lập, không dependency
C3 (4 sub-items)            ── độc lập, không dependency
C7 (package + service)      ── độc lập, không dependency
```

---

### 2.2 Sprint 1 — Nền tảng không rủi ro (C2 + C6)

**Thời gian ước tính:** 1.5–2 ngày
**Không cần chạy migration** (C2 hoàn toàn không có migration; C6 chỉ thêm 2 cột nullable vào bảng hiện có)

**Bước 1 — C2: Unit Workforce Dashboard**
1. Thêm method `unitDashboard()` vào `WorkforceProfileController`
2. Thêm route `backend.workforce.unit-dashboard` vào `routes/web.php`
3. Tạo view `workforce/unit-dashboard.blade.php`
4. Test: truy cập `/dashboard/workforce/unit`, lọc theo phòng ban, kiểm tra stat cards

**Lý do làm trước:** Không có migration → deploy an toàn bất kỳ lúc nào. Cho phép team test view layer trước khi đụng vào database.

**Bước 2 — C6: KcItem domain_code + difficulty**
1. Tạo migration `add_domain_and_difficulty_to_kc_items`
2. Chạy `php artisan migrate`
3. Cập nhật `$fillable`, `casts()`, thêm 2 scopes vào `KcItem.php`
4. Thêm 2 trường vào form create/edit + validation rules trong controller
5. Test: tạo KC item mới với domain_code + difficulty, kiểm tra lưu đúng

**Lý do làm ngay sau C2:** Cột nullable → không ảnh hưởng dữ liệu cũ. Đây là tiền đề bắt buộc cho C4 (admin UI cần lọc KC theo domain).

**Checkpoint Sprint 1:** Unit dashboard hoạt động, form KC item có 2 trường mới, dữ liệu cũ không bị ảnh hưởng.

---

### 2.3 Sprint 2 — Lộ trình học tập (C4 → C5)

**Thời gian ước tính:** 3 ngày
**Yêu cầu:** Sprint 1 đã hoàn thành (C6 phải xong trước)
**Có migration** — chạy theo thứ tự C4 trước, C5 sau

**Bước 1 — C4: Pivot RoadmapMilestone ↔ KcItem**
1. Tạo migration `create_roadmap_milestone_kc_items_table`
2. Chạy `php artisan migrate`
3. Thêm `kcItems()` BelongsToMany vào `RoadmapMilestone`
4. Thêm `roadmapMilestones()` BelongsToMany vào `KcItem`
5. Tạo `RoadmapAdminController` (5 methods: index, phase, milestoneKc, attachKc, detachKc)
6. Đăng ký routes `backend.roadmap-admin.*`
7. Tạo 3 views (index, phase, milestone-kc)
8. Test: vào admin, gắn 1 KC item vào milestone, kiểm tra quan hệ DB

**Bước 2 — C5: Learning Progress**
1. Tạo migration `create_kc_learning_progress_table` (FK vào `roadmap_milestones` — bảng đã tồn tại từ trước)
2. Chạy `php artisan migrate`
3. Tạo model `KcLearningProgress`
4. Tạo `KcProgressController` (3 methods: start, complete, update)
5. Đăng ký 3 routes
6. Cập nhật `CareerPathwayController::index()` để load `$progress`
7. Cập nhật view `career-pathway/index.blade.php`: trạng thái KC + nút + Alpine.js component
8. Test: user bắt đầu KC → trạng thái "in_progress", hoàn thành → "completed", F5 lại vẫn giữ trạng thái

**Checkpoint Sprint 2:** Admin gắn được KC vào milestone. User thấy tài liệu trong Career Pathway và theo dõi được tiến độ học.

---

### 2.4 Sprint 3 — Phân tích tổ chức (C1)

**Thời gian ước tính:** 2.5–3 ngày
**Yêu cầu:** Không phụ thuộc vào Sprint 1/2 — có thể làm song song nếu có 2 dev
**Không cần migration** — dùng hoàn toàn dữ liệu từ `workforce_profiles`

**Bước 1 — Routes + Controller**
1. Thêm 5 routes `report.competency.*` vào `Modules/Report/routes/web.php`
2. Tạo `CompetencyReportController` với 5 methods (index, heatmap, skillGap, trends, export)
3. Test controller trả về đúng data (dd hoặc return json trước khi làm view)

**Bước 2 — Views theo thứ tự đơn giản → phức tạp**
1. `competency/index.blade.php` — stat cards + maturity bars + nav buttons (đơn giản nhất)
2. `competency/skill-gap.blade.php` — table với filter Alpine.js
3. `competency/heatmap.blade.php` — table color-coded (phức tạp nhất về CSS logic)
4. `competency/trends.blade.php` — ECharts line chart (theo pattern `workforce/me.blade.php`)

**Bước 3 — Kiểm tra quyền**
- Xác nhận CEO và ADMIN vào được (có `reports.full`)
- Xác nhận SALES/VIEWER không vào được (403)

**Checkpoint Sprint 3:** Menu Báo cáo có tab "Năng lực số". Ban lãnh đạo xem được heatmap, skill gap, xu hướng 12 tháng, xuất Excel.

---

### 2.5 Sprint 4 — Vận hành Campaign (C3)

**Thời gian ước tính:** 2.5 ngày
**Yêu cầu:** Không phụ thuộc sprint nào — có thể làm song song với Sprint 3
**Không cần migration**

**Thứ tự trong Sprint 4 (từ nhỏ đến lớn):**

1. **C3c — Export Excel** (0.5 ngày): Đơn giản nhất — thêm 1 method + 1 route + 1 nút trong view
2. **C3d — Results by Unit** (0.5 ngày): 1 method + 1 route + 1 view mới
3. **C3b — Reminder Job** (0.5 ngày): Tạo `SendCampaignReminderJob` + `CampaignReminderNotification` + đăng ký schedule
4. **C3a — Bulk Invite** (1 ngày): Phức tạp nhất — method + route + cập nhật view existing với Alpine.js checkbox

**Lý do thứ tự này:** Export và by-unit không đụng vào view hiện có (an toàn). Reminder job chạy background (không ảnh hưởng UI). Bulk invite đụng vào `results.blade.php` đang dùng — để cuối để giảm risk khi sửa file.

**Checkpoint Sprint 4:** HR có thể xuất Excel, xem kết quả theo đơn vị, mời hàng loạt, và hệ thống tự nhắc nhở ứng viên.

---

### 2.6 Sprint 5 — Tin cậy & Xác minh (C7)

**Thời gian ước tính:** 1 ngày
**Yêu cầu:** Không phụ thuộc sprint nào — nhưng nên làm sau cùng vì cần cài package mới
**Cần chạy:** `composer require simplesoftwareio/simple-qrcode`

**Bước 1 — Cài package + kiểm tra môi trường**
1. `composer require simplesoftwareio/simple-qrcode`
2. Kiểm tra `storage/app/public` có symlink (`php artisan storage:link`)
3. Tạo thư mục `storage/app/public/qrcodes/certs/`

**Bước 2 — Route xác minh công khai**
1. Thêm route `GET /verify/cert/{number}` (withoutMiddleware auth)
2. Tạo `CertVerifyController::show()`
3. Tạo view `certifications/verify.blade.php` (public layout)
4. Test: truy cập `/verify/cert/FAKE-NUMBER` → 404; số thật → thấy trang xác minh

**Bước 3 — Service + Trigger**
1. Tạo `CertificationQrService::generateAndStore()`
2. Gắn vào `CertificationAdminController::issue()` sau khi tạo cert
3. Test: issue cert mới → file SVG xuất hiện trong storage → `qr_code_url` được cập nhật trong DB

**Bước 4 — Hiển thị trong PDF**
1. Cập nhật PDF template của `profilePdf()` để render QR
2. Test: tải PDF → thấy QR code → quét bằng điện thoại → mở trang xác minh đúng

**Checkpoint Sprint 5:** Mỗi chứng nhận được cấp mới đều có QR code. Người nhận chứng nhận quét mã → thấy ngay trang xác minh công khai.

---

### 2.7 Bảng tổng hợp trình tự

| Sprint | Items | Effort | Migration? | Deploy điều kiện |
|---|---|---|---|---|
| Sprint 1 | C2 → C6 | 1.5–2 ngày | C6: +2 cột nullable | Bất kỳ lúc nào |
| Sprint 2 | C4 → C5 | 3 ngày | C4: +1 bảng pivot, C5: +1 bảng | Sau C6 |
| Sprint 3 | C1 | 2.5–3 ngày | Không | Bất kỳ lúc nào |
| Sprint 4 | C3c → C3d → C3b → C3a | 2.5 ngày | Không | Bất kỳ lúc nào |
| Sprint 5 | C7 | 1 ngày | Không (cài package) | Bất kỳ lúc nào |

**Tổng:** 10.5–11.5 ngày công

**Nếu có 2 developer:** Sprint 2 và Sprint 3 chạy song song, Sprint 4 và Sprint 5 chạy song song → rút xuống còn ~6–7 ngày thực tế.
| C7 | QR Code cho WorkforceCertification | P3 — Thấp |

---

## C1 — Report Module: Báo cáo năng lực số tổ chức

### Mục tiêu

Bổ sung sub-section `report.competency.*` vào `Modules/Report/`, cho phép CEO/Admin xem phân tích năng lực số toàn tổ chức (maturity heatmap theo phòng ban, skill gap, xu hướng phát triển) mà không cần vào Assessment module.

### 1.1 Routes — thêm vào `Modules/Report/routes/web.php`

```php
use Modules\Report\Http\Controllers\CompetencyReportController;

// Thêm trong group auth/verified/tenant
Route::middleware('can:reports.hr,reports.full')
    ->prefix('competency')->name('competency.')
    ->group(function () {
        Route::get('/',          [CompetencyReportController::class, 'index']     )->name('index');
        Route::get('/heatmap',   [CompetencyReportController::class, 'heatmap']   )->name('heatmap');
        Route::get('/skill-gap', [CompetencyReportController::class, 'skillGap']  )->name('skill-gap');
        Route::get('/trends',    [CompetencyReportController::class, 'trends']    )->name('trends');
        Route::get('/export',    [CompetencyReportController::class, 'export']    )->name('export');
    });
```

### 1.2 Controller — `Modules/Report/app/Http/Controllers/CompetencyReportController.php`

```php
<?php

namespace Modules\Report\Http\Controllers;

use App\Shared\Tenancy\TenantContext;
use Illuminate\View\View;
use Modules\Assessment\Models\WorkforceProfile;
use Rap2hpoutre\FastExcel\FastExcel;

class CompetencyReportController extends Controller
{
    public function index(): View
    {
        $orgId = TenantContext::organization()->id;

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
        $orgId = TenantContext::organization()->id;

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

        return view('report::competency.heatmap', compact('heatmap'));
    }

    public function skillGap(): View
    {
        $orgId = TenantContext::organization()->id;

        $profiles = WorkforceProfile::where('organization_id', $orgId)
            ->with('employee:id,full_name,department_id', 'employee.department:id,name')
            ->get();

        // Benchmark ngưỡng theo maturity level kế tiếp (từ TDWCF spec)
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
            if (!$bench) return null;
            return [
                'profile'   => $p,
                'nextLevel' => $nextLevel,
                'gaps'      => [
                    'D1' => max(0, $bench['D1'] - ($p->score_d1_digital_literacy ?? 0)),
                    'D2' => max(0, $bench['D2'] - ($p->score_d2_data_literacy ?? 0)),
                    'D3' => max(0, $bench['D3'] - ($p->score_d3_ai_literacy ?? 0)),
                    'D4' => max(0, $bench['D4'] - ($p->score_d4_workflow ?? 0)),
                    'D5' => max(0, $bench['D5'] - ($p->score_d5_innovation ?? 0)),
                    'D6' => max(0, $bench['D6'] - ($p->score_d6_performance ?? 0)),
                ],
            ];
        })->filter()->values();

        return view('report::competency.skill-gap', compact('gaps'));
    }

    public function trends(): View
    {
        $orgId = TenantContext::organization()->id;

        $trendData = \DB::table('workforce_profile_histories')
            ->join('workforce_profiles', 'workforce_profile_histories.workforce_profile_id', '=', 'workforce_profiles.id')
            ->where('workforce_profiles.organization_id', $orgId)
            ->where('workforce_profile_histories.event_type', 'assessment')
            ->where('workforce_profile_histories.recorded_at', '>=', now()->subMonths(12))
            ->selectRaw("DATE_FORMAT(recorded_at, '%Y-%m') as month, AVG(tdwcf_score) as avg_score, COUNT(*) as assessments")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('report::competency.trends', compact('trendData'));
    }

    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $orgId = TenantContext::organization()->id;

        $rows = WorkforceProfile::where('organization_id', $orgId)
            ->with('employee:id,full_name,department_id,job_title_id', 'employee.department:id,name')
            ->get()
            ->map(fn($p) => [
                'Họ tên'        => $p->employee?->full_name ?? '—',
                'Phòng ban'     => $p->employee?->department?->name ?? '—',
                'Maturity'      => $p->tdwcf_maturity_level ?? '—',
                'TDWCF Score'   => $p->tdwcf_score,
                'Trust Score'   => $p->workforce_trust_score,
                'D1 Số hóa'     => $p->score_d1_digital_literacy,
                'D2 Dữ liệu'    => $p->score_d2_data_literacy,
                'D3 AI'         => $p->score_d3_ai_literacy,
                'D4 Quy trình'  => $p->score_d4_workflow,
                'D5 Đổi mới'    => $p->score_d5_innovation,
                'D6 Hiệu suất'  => $p->score_d6_performance,
                'Chứng nhận'    => $p->certifications_count,
                'Cấp cao nhất'  => $p->highest_cert_level ?? '—',
            ]);

        return (new FastExcel($rows))->download('competency-report-'.now()->format('Ymd').'.xlsx');
    }

    private function nextLevel(string $current): string
    {
        return match($current) {
            'DIGITAL_BEGINNER'     => 'DIGITAL_AWARE',
            'DIGITAL_AWARE'        => 'DIGITAL_PRACTITIONER',
            'DIGITAL_PRACTITIONER' => 'DIGITAL_PROFESSIONAL',
            'DIGITAL_PROFESSIONAL' => 'DIGITAL_LEADER',
            default                => 'DIGITAL_LEADER',
        };
    }
}
```

### 1.3 Views — `Modules/Report/resources/views/competency/`

**`index.blade.php`** — Tổng quan năng lực tổ chức
- `@extends('layouts.backend')`
- Header: `$orgAvgTdwcf`, `$orgAvgTrust`, `$totalCerts`, tổng nhân sự
- 6 DaisyUI stat cards theo domain D1–D6 với `$domainAvgs`, màu threshold: `<40` error, `40–60` warning, `>60` success
- Maturity distribution: progress bars (% mỗi level trong tổng profiles)
- Button bar: "Heatmap" → `report.competency.heatmap`, "Skill Gap" → `report.competency.skill-gap`, "Xu hướng" → `report.competency.trends`, "Xuất Excel" → `report.competency.export`

**`heatmap.blade.php`** — Bảng nhiệt phòng ban × domain
- Table responsive: rows = phòng ban, cols = [Đơn vị, Nhân sự, D1, D2, D3, D4, D5, D6, Trust]
- Color-coding mỗi ô: `<40` → `bg-error/20 text-error`, `40–59` → `bg-warning/20`, `60–79` → `bg-info/20`, `≥80` → `bg-success/20 text-success`
- Alpine.js: hover hàng highlight, click column sort
- Footer row: trung bình toàn tổ chức

**`skill-gap.blade.php`** — Phân tích khoảng cách kỹ năng
- Filter phòng ban (Alpine `x-model` → lọc danh sách)
- Table: Họ tên | Phòng ban | Maturity hiện tại | Mục tiêu | Gap D1 | D2 | D3 | D4 | D5 | D6 | Tổng gap
- Sắp xếp theo tổng gap giảm dần mặc định
- Badge cho gap: `>20` → badge-error, `10–20` → badge-warning, `<10` → badge-success

**`trends.blade.php`** — Xu hướng 12 tháng
- ECharts line chart (event `echarts:ready` như pattern `workforce/me.blade.php`)
- Dữ liệu inject: `@json($trendData)` → `{ month, avg_score, assessments }`
- Dual Y-axis: TDWCF trung bình (trái) + số đợt đánh giá (phải)

### 1.4 Permission

Dùng lại gate `reports.hr,reports.full` — không cần migration thêm. CEO và ADMIN đã có `reports.full`.

---

## C2 — Dashboard cấp đơn vị (Unit Workforce Dashboard)

### Mục tiêu

Nâng cấp `backend.workforce.index` (hiện là danh sách đơn giản) thành dashboard có bộ lọc phòng ban và thống kê tổng hợp. Cho phép trưởng phòng / CEO xem tình trạng năng lực đơn vị mình phụ trách.

### 2.1 Controller — thêm method vào `WorkforceProfileController`

```php
// Thêm vào Modules/Assessment/app/Http/Controllers/WorkforceProfileController.php

public function unitDashboard(Request $request): View
{
    $this->authorize('assessment.results');
    $orgId  = TenantContext::organization()->id;
    $deptId = $request->integer('dept_id') ?: null;

    $query = WorkforceProfile::where('organization_id', $orgId)
        ->with([
            'employee:id,full_name,department_id,job_title_id',
            'employee.department:id,name',
            'employee.jobTitle:id,name',
        ]);

    if ($deptId) {
        $query->whereHas('employee', fn($q) => $q->where('department_id', $deptId));
    }

    $profiles = $query->get();

    $departments = \App\Modules\HR\Models\Department::where('organization_id', $orgId)
        ->active()->orderBy('name')->get(['id', 'name']);

    $stats = [
        'total'         => $profiles->count(),
        'avg_tdwcf'     => round($profiles->avg('tdwcf_score') ?? 0, 1),
        'avg_trust'     => round($profiles->avg('workforce_trust_score') ?? 0, 1),
        'certified_pct' => $profiles->count()
            ? round($profiles->where('certifications_count', '>', 0)->count() / $profiles->count() * 100, 1)
            : 0,
        'maturity_dist' => $profiles->groupBy('tdwcf_maturity_level')->map->count(),
        'domain_avgs'   => [
            'D1' => round($profiles->avg('score_d1_digital_literacy') ?? 0, 1),
            'D2' => round($profiles->avg('score_d2_data_literacy') ?? 0, 1),
            'D3' => round($profiles->avg('score_d3_ai_literacy') ?? 0, 1),
            'D4' => round($profiles->avg('score_d4_workflow') ?? 0, 1),
            'D5' => round($profiles->avg('score_d5_innovation') ?? 0, 1),
            'D6' => round($profiles->avg('score_d6_performance') ?? 0, 1),
        ],
        'top10'           => $profiles->sortByDesc('workforce_trust_score')->take(10)->values(),
        'needs_attention' => $profiles->filter(fn($p) => ($p->tdwcf_score ?? 0) < 40)->values(),
    ];

    return view('assessment::workforce.unit-dashboard', compact('profiles', 'departments', 'stats', 'deptId'));
}
```

### 2.2 Route — thêm vào `Modules/Assessment/routes/web.php`

```php
Route::get('/dashboard/workforce/unit',
    [WorkforceProfileController::class, 'unitDashboard'])
    ->name('backend.workforce.unit-dashboard')
    ->middleware(['auth', 'verified', 'can:ASSESSMENT_RESULTS']);
```

### 2.3 View — `Modules/Assessment/resources/views/workforce/unit-dashboard.blade.php`

```
@extends('layouts.backend')
@section('title', 'Dashboard năng lực đơn vị')

{{-- Filter bar: dropdown phòng ban, submit GET --}}
<form method="GET">
    <select name="dept_id">
        <option value="">Toàn tổ chức</option>
        @foreach($departments as $d)
        <option value="{{ $d->id }}" @selected($deptId == $d->id)>{{ $d->name }}</option>
        @endforeach
    </select>
    <button type="submit">Lọc</button>
</form>

{{-- 4 stat cards --}}
Nhân sự: $stats['total']
TDWCF TB: $stats['avg_tdwcf']
Trust TB: $stats['avg_trust']
Có chứng nhận: $stats['certified_pct']%

{{-- Maturity distribution: DaisyUI progress bars --}}
@foreach(['DIGITAL_BEGINNER','DIGITAL_AWARE','DIGITAL_PRACTITIONER','DIGITAL_PROFESSIONAL','DIGITAL_LEADER'] as $level)
    {{ $stats['maturity_dist'][$level] ?? 0 }} / {{ $stats['total'] }}
@endforeach

{{-- 6 domain averages: horizontal progress bars với màu threshold --}}

{{-- Top 10 leaderboard: tên | dept | TDWCF | Trust | Level | Link --}}

{{-- Cần hỗ trợ (score < 40): tên | dept | TDWCF | Nút xem profile --}}

{{-- Export button: → backend.workforce.export.organization?dept_id=$deptId --}}
```

---

## C3 — Campaign: Bulk Invite + Reminder + Export + Unit Breakdown

### 3.1 Bulk Invite

**Route mới — thêm vào `Modules/Assessment/routes/web.php`:**
```php
Route::post('/dashboard/campaigns/{campaign}/invite-bulk',
    [CampaignAdminController::class, 'inviteBulk'])
    ->name('campaigns.admin.invite-bulk');
```

**Method `inviteBulk()` — thêm vào `CampaignAdminController`:**
```php
public function inviteBulk(Request $request, OpenAssessmentCampaign $campaign): RedirectResponse
{
    $this->authorizeOrg($campaign);
    $request->validate([
        'participation_ids'   => ['required', 'array', 'min:1', 'max:100'],
        'participation_ids.*' => ['integer', 'exists:campaign_participations,id'],
    ]);

    $participations = $campaign->participations()
        ->whereIn('id', $request->participation_ids)
        ->where('status', ParticipationStatus::Completed)
        ->with('user')
        ->get();

    foreach ($participations as $participation) {
        $participation->update(['org_action' => 'invited', 'org_action_at' => now()]);
        $participation->user->notify(new CampaignInviteNotification($campaign, $participation));
    }

    return back()->with('flash_success', "Đã gửi lời mời đến {$participations->count()} ứng viên.");
}
```

**Cập nhật view `campaigns/org/results.blade.php`:**
- Bọc bảng trong `<form method="POST" action="{{ route('campaigns.admin.invite-bulk', $campaign) }}">`
- Thêm `@csrf`
- Mỗi hàng ứng viên completed + chưa invited: `<input type="checkbox" name="participation_ids[]" value="{{ $p->id }}">`
- Alpine.js: `x-data="{ selected: [] }"`, nút "Mời đã chọn" disabled khi `selected.length === 0`
- Nút "Chọn tất cả" / "Bỏ chọn tất cả"

### 3.2 Campaign Reminder — Job + Notification

**File mới: `Modules/Assessment/app/Jobs/SendCampaignReminderJob.php`**
```php
<?php

namespace Modules\Assessment\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Assessment\Enums\CampaignStatus;
use Modules\Assessment\Enums\ParticipationStatus;
use Modules\Assessment\Models\OpenAssessmentCampaign;
use Modules\Assessment\Notifications\CampaignReminderNotification;

class SendCampaignReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        OpenAssessmentCampaign::where('status', CampaignStatus::Open)
            ->whereNotNull('open_until')
            ->whereBetween('open_until', [now(), now()->addDays(3)])
            ->each(function (OpenAssessmentCampaign $campaign) {
                $campaign->participations()
                    ->where('status', ParticipationStatus::InProgress)
                    ->with('user')
                    ->each(function ($participation) use ($campaign) {
                        $participation->user->notify(
                            new CampaignReminderNotification($campaign, $participation)
                        );
                    });
            });
    }
}
```

**File mới: `Modules/Assessment/app/Notifications/CampaignReminderNotification.php`**
```php
<?php

namespace Modules\Assessment\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Assessment\Models\CampaignParticipation;
use Modules\Assessment\Models\OpenAssessmentCampaign;

class CampaignReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly OpenAssessmentCampaign $campaign,
        public readonly CampaignParticipation  $participation,
    ) {}

    public function via(mixed $notifiable): array { return ['mail']; }

    public function toMail(mixed $notifiable): MailMessage
    {
        $daysLeft = (int) now()->diffInDays($this->campaign->open_until);
        return (new MailMessage)
            ->subject("Nhắc nhở: Còn {$daysLeft} ngày — {$this->campaign->title}")
            ->line("Bạn đã tham gia nhưng chưa hoàn thành đánh giá **{$this->campaign->title}**.")
            ->line("Hạn chót: {$this->campaign->open_until->format('d/m/Y H:i')}")
            ->action('Tiếp tục đánh giá', url("/campaigns/{$this->campaign->uuid}/workspace"));
    }
}
```

**Đăng ký schedule trong `routes/console.php` hoặc `bootstrap/app.php`:**
```php
Schedule::job(SendCampaignReminderJob::class)->dailyAt('08:00');
```

### 3.3 Campaign Export (Excel)

**Route mới:**
```php
Route::get('/dashboard/campaigns/{campaign}/export',
    [CampaignAdminController::class, 'exportResults'])
    ->name('campaigns.admin.export');
```

**Method `exportResults()` — thêm vào `CampaignAdminController`:**
```php
public function exportResults(OpenAssessmentCampaign $campaign): \Symfony\Component\HttpFoundation\StreamedResponse
{
    $this->authorizeOrg($campaign);

    $rows = $campaign->participations()
        ->with(['user:id,name,email'])
        ->orderByDesc('result_tdwcf_score')
        ->get()
        ->map(fn($p) => [
            'Ứng viên'        => $campaign->is_anonymous_to_org && !$p->isInvited()
                                    ? $p->anonymousLabel() : $p->user->name,
            'Email'           => $campaign->is_anonymous_to_org && !$p->isInvited() ? '—' : $p->user->email,
            'Trạng thái'      => $p->status->label(),
            'TDWCF Score'     => $p->result_tdwcf_score,
            'Maturity Level'  => $p->result_maturity_level,
            'Sandbox Avg'     => $p->result_sandbox_avg,
            'Đánh giá org'    => $p->org_rating,
            'Ghi chú'         => $p->org_note,
            'Hành động'       => $p->org_action,
            'Tham gia'        => $p->joined_at?->format('d/m/Y'),
            'Hoàn thành'      => $p->completed_at?->format('d/m/Y'),
        ]);

    return (new \Rap2hpoutre\FastExcel\FastExcel($rows))
        ->download("campaign-{$campaign->uuid}-results.xlsx");
}
```

**Cập nhật view `campaigns/org/results.blade.php`:** Thêm nút "Xuất Excel" trỏ tới route `campaigns.admin.export`.

### 3.4 Campaign Results by Unit

**Route mới:**
```php
Route::get('/dashboard/campaigns/{campaign}/results/by-unit',
    [CampaignAdminController::class, 'resultsByUnit'])
    ->name('campaigns.admin.results.by-unit');
```

**Method `resultsByUnit()` — thêm vào `CampaignAdminController`:**
```php
public function resultsByUnit(OpenAssessmentCampaign $campaign): View
{
    $this->authorizeOrg($campaign);

    $unitStats = $campaign->participations()
        ->with(['user.employee.department:id,name'])
        ->where('status', ParticipationStatus::Completed)
        ->get()
        ->groupBy(fn($p) => $p->user->employee?->department?->name ?? 'Không xác định')
        ->map(fn($group) => [
            'count'     => $group->count(),
            'invited'   => $group->where('org_action', 'invited')->count(),
            'avg_tdwcf' => round($group->avg('result_tdwcf_score') ?? 0, 1),
            'avg_sb'    => round($group->avg('result_sandbox_avg') ?? 0, 1),
        ]);

    return view('assessment::campaigns.org.results-by-unit', compact('campaign', 'unitStats'));
}
```

**View `assessment::campaigns.org.results-by-unit`:**
- Breadcrumb: Campaigns → [title] → Kết quả → Theo đơn vị
- Table: Đơn vị | Tham gia | Đã mời | TDWCF TB | Sandbox TB
- Nút "← Xem danh sách" trở về `campaigns.admin.results`

---

## C4 — KcItem ↔ RoadmapMilestone: Pivot Table + Admin UI

### Mục tiêu

Liên kết tài liệu KC (`kc_items`) với mốc học tập (`roadmap_milestones`) để người dùng biết tài liệu nào thuộc giai đoạn nào trong lộ trình phát triển TDWCF.

### 4.1 Migration

**File: `database/migrations/2026_07_02_000001_create_roadmap_milestone_kc_items_table.php`**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roadmap_milestone_kc_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('roadmap_milestone_id');
            $table->unsignedBigInteger('kc_item_id');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['roadmap_milestone_id', 'kc_item_id']);
            $table->foreign('roadmap_milestone_id')
                ->references('id')->on('roadmap_milestones')->cascadeOnDelete();
            $table->foreign('kc_item_id')
                ->references('id')->on('kc_items')->cascadeOnDelete();
            $table->index('kc_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roadmap_milestone_kc_items');
    }
};
```

### 4.2 Model Changes

**`Modules/Assessment/app/Models/RoadmapMilestone.php`** — thêm relationship:
```php
use Modules\KcItem\Models\KcItem;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

public function kcItems(): BelongsToMany
{
    return $this->belongsToMany(
        KcItem::class,
        'roadmap_milestone_kc_items',
        'roadmap_milestone_id',
        'kc_item_id'
    )->withPivot('sort_order')->orderByPivot('sort_order');
}
```

**`Modules/KcItem/app/Models/KcItem.php`** — thêm relationship ngược:
```php
use Modules\Assessment\Models\RoadmapMilestone;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

public function roadmapMilestones(): BelongsToMany
{
    return $this->belongsToMany(
        RoadmapMilestone::class,
        'roadmap_milestone_kc_items',
        'kc_item_id',
        'roadmap_milestone_id'
    )->withPivot('sort_order');
}
```

### 4.3 Routes + Controller Admin

**Routes — thêm vào `Modules/Assessment/routes/web.php`:**
```php
Route::middleware(['auth', 'verified', 'can:ASSESSMENT_CONFIG'])
    ->prefix('/dashboard/roadmap-admin')
    ->name('backend.roadmap-admin.')
    ->group(function () {
        Route::get('/',
            [RoadmapAdminController::class, 'index'])->name('index');
        Route::get('/{phase}',
            [RoadmapAdminController::class, 'phase'])->name('phase');
        Route::get('/{phase}/milestones/{milestone}/kc',
            [RoadmapAdminController::class, 'milestoneKc'])->name('milestone.kc');
        Route::post('/{phase}/milestones/{milestone}/kc',
            [RoadmapAdminController::class, 'attachKc'])->name('milestone.kc.attach');
        Route::delete('/{phase}/milestones/{milestone}/kc/{kcItem}',
            [RoadmapAdminController::class, 'detachKc'])->name('milestone.kc.detach');
    });
```

**Controller: `Modules/Assessment/app/Http/Controllers/RoadmapAdminController.php`**
```php
<?php

namespace Modules\Assessment\Http\Controllers;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Assessment\Models\RoadmapMilestone;
use Modules\Assessment\Models\RoadmapPhase;
use Modules\KcItem\Models\KcItem;

class RoadmapAdminController extends Controller
{
    public function index(): View
    {
        $phases = RoadmapPhase::with('milestones')->orderBy('sort_order')->get();
        return view('assessment::roadmap.index', compact('phases'));
    }

    public function phase(RoadmapPhase $phase): View
    {
        $phase->load('milestones.kcItems');
        return view('assessment::roadmap.phase', compact('phase'));
    }

    public function milestoneKc(RoadmapPhase $phase, RoadmapMilestone $milestone): View
    {
        $milestone->load('kcItems');
        $availableKc = KcItem::approved()
            ->where('organization_id', TenantContext::organization()->id)
            ->orWhereNull('organization_id')
            ->get(['id', 'title', 'type']);
        return view('assessment::roadmap.milestone-kc', compact('phase', 'milestone', 'availableKc'));
    }

    public function attachKc(
        \Illuminate\Http\Request $request,
        RoadmapPhase $phase,
        RoadmapMilestone $milestone
    ): RedirectResponse {
        $request->validate(['kc_item_id' => ['required', 'exists:kc_items,id']]);
        $milestone->kcItems()->syncWithoutDetaching([
            $request->kc_item_id => ['sort_order' => $milestone->kcItems()->count()],
        ]);
        return back()->with('flash_success', 'Đã gắn tài liệu vào mốc học tập.');
    }

    public function detachKc(
        RoadmapPhase $phase,
        RoadmapMilestone $milestone,
        KcItem $kcItem
    ): RedirectResponse {
        $milestone->kcItems()->detach($kcItem->id);
        return back()->with('flash_success', 'Đã gỡ tài liệu.');
    }
}
```

**Views:**
- `assessment::roadmap.index` — danh sách phases (maturity level, title, số milestones)
- `assessment::roadmap.phase` — milestones trong phase + số KC items mỗi milestone
- `assessment::roadmap.milestone-kc` — list KC đã gắn (title, type, Gỡ) + form thêm KC (select dropdown + Gắn)

---

## C5 — Learning Progress Tracking per User

### Mục tiêu

Theo dõi từng người dùng đã hoàn thành KC nào trong lộ trình, hiển thị tiến độ trong career-pathway view.

### 5.1 Migration

**File: `database/migrations/2026_07_02_000002_create_kc_learning_progress_table.php`**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kc_learning_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('kc_item_id');
            $table->unsignedBigInteger('roadmap_milestone_id')->nullable();
            $table->string('status', 20)->default('in_progress'); // in_progress | completed | skipped
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedSmallInteger('completion_pct')->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'kc_item_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('kc_item_id')->references('id')->on('kc_items')->cascadeOnDelete();
            $table->foreign('roadmap_milestone_id')
                ->references('id')->on('roadmap_milestones')->nullOnDelete();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kc_learning_progress');
    }
};
```

### 5.2 Model — `Modules/KcItem/app/Models/KcLearningProgress.php`

```php
<?php

namespace Modules\KcItem\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Assessment\Models\RoadmapMilestone;

class KcLearningProgress extends Model
{
    protected $table = 'kc_learning_progress';

    protected $fillable = [
        'user_id', 'kc_item_id', 'roadmap_milestone_id',
        'status', 'started_at', 'completed_at', 'completion_pct', 'note',
    ];

    protected function casts(): array
    {
        return [
            'started_at'     => 'datetime',
            'completed_at'   => 'datetime',
            'completion_pct' => 'integer',
        ];
    }

    public function user(): BelongsTo      { return $this->belongsTo(User::class); }
    public function kcItem(): BelongsTo    { return $this->belongsTo(KcItem::class); }
    public function milestone(): BelongsTo { return $this->belongsTo(RoadmapMilestone::class, 'roadmap_milestone_id'); }

    public function isCompleted(): bool { return $this->status === 'completed'; }
}
```

### 5.3 Routes + Controller

**Routes — thêm vào `Modules/KcItem/routes/web.php` hoặc Assessment routes:**
```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/kc-items/{kcItem}/progress/start',
        [KcProgressController::class, 'start'])->name('kc.progress.start');
    Route::patch('/kc-items/{kcItem}/progress/complete',
        [KcProgressController::class, 'complete'])->name('kc.progress.complete');
    Route::patch('/kc-items/{kcItem}/progress/update',
        [KcProgressController::class, 'update'])->name('kc.progress.update');
});
```

**Controller: `Modules/KcItem/app/Http/Controllers/KcProgressController.php`**
```php
<?php

namespace Modules\KcItem\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\KcItem\Models\KcItem;
use Modules\KcItem\Models\KcLearningProgress;

class KcProgressController extends Controller
{
    public function start(Request $request, KcItem $kcItem): JsonResponse
    {
        $progress = KcLearningProgress::firstOrCreate(
            ['user_id' => $request->user()->id, 'kc_item_id' => $kcItem->id],
            [
                'status'               => 'in_progress',
                'started_at'           => now(),
                'completion_pct'       => 0,
                'roadmap_milestone_id' => $request->input('milestone_id'),
            ]
        );
        return response()->json(['status' => $progress->status, 'pct' => $progress->completion_pct]);
    }

    public function complete(Request $request, KcItem $kcItem): JsonResponse
    {
        $progress = KcLearningProgress::updateOrCreate(
            ['user_id' => $request->user()->id, 'kc_item_id' => $kcItem->id],
            [
                'status'       => 'completed',
                'completed_at' => now(),
                'completion_pct' => 100,
            ]
        );
        if (!$progress->started_at) {
            $progress->update(['started_at' => now()]);
        }
        return response()->json([
            'status'       => $progress->status,
            'completed_at' => $progress->completed_at->toIso8601String(),
        ]);
    }

    public function update(Request $request, KcItem $kcItem): JsonResponse
    {
        $data = $request->validate(['completion_pct' => ['required', 'integer', 'min:0', 'max:100']]);
        $progress = KcLearningProgress::updateOrCreate(
            ['user_id' => $request->user()->id, 'kc_item_id' => $kcItem->id],
            array_merge($data, ['started_at' => now()])
        );
        return response()->json(['pct' => $progress->completion_pct]);
    }
}
```

### 5.4 UI trong Career Pathway View

**Cập nhật `CareerPathwayController::index()`:**
```php
$milestoneIds = $steps->flatMap->milestones->pluck('id');
$progress = KcLearningProgress::where('user_id', auth()->id())
    ->whereIn('roadmap_milestone_id', $milestoneIds)
    ->get()
    ->keyBy('kc_item_id');
```

**Trong view `career-pathway/index.blade.php`:** Với mỗi KC trong milestone:
- `✅ Hoàn thành` nếu `$progress[$item->id]?->isCompleted()`
- Progress bar `completion_pct` nếu `in_progress`
- Nút "Bắt đầu" (Alpine.js AJAX → `kc.progress.start`) nếu chưa có

**Alpine.js component:**
```js
Alpine.data('kcProgress', (itemId, milestoneId, initStatus, initPct) => ({
    status: initStatus,
    pct: initPct,
    loading: false,
    async markComplete() {
        this.loading = true;
        const res = await fetch(`/kc-items/${itemId}/progress/complete`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content }
        });
        const data = await res.json();
        this.status = data.status;
        this.pct = 100;
        this.loading = false;
    },
}));
```

---

## C6 — KcItem Metadata: `domain_code` + `difficulty`

### Mục tiêu

Gắn nhãn domain TDWCF (D1–D6) và mức độ khó cho tài liệu KC, phục vụ filter trong career-pathway và báo cáo competency.

### 6.1 Migration

**File: `Modules/KcItem/database/migrations/2026_07_02_000003_add_domain_and_difficulty_to_kc_items.php`**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kc_items', function (Blueprint $table) {
            $table->string('domain_code', 10)->nullable()->after('category_id');
            $table->unsignedTinyInteger('difficulty')->nullable()->after('domain_code');
            $table->index('domain_code');
            $table->index('difficulty');
        });
    }

    public function down(): void
    {
        Schema::table('kc_items', function (Blueprint $table) {
            $table->dropIndex(['domain_code']);
            $table->dropIndex(['difficulty']);
            $table->dropColumn(['domain_code', 'difficulty']);
        });
    }
};
```

### 6.2 Model Changes — `KcItem.php`

Thêm vào `$fillable`:
```php
'domain_code',
'difficulty',
```

Thêm vào `casts()`:
```php
'difficulty' => 'integer',
```

Thêm scopes:
```php
public function scopeForDomain($query, string $domainCode)
{
    return $query->where('domain_code', $domainCode);
}

public function scopeForDifficulty($query, int $difficulty)
{
    return $query->where('difficulty', $difficulty);
}
```

### 6.3 Form Fields (create.blade.php + edit.blade.php)

Thêm vào section metadata trong form KC:
```html
<div class="grid grid-cols-2 gap-4">
    {{-- Domain TDWCF --}}
    <div class="form-control">
        <label class="label"><span class="label-text">Domain TDWCF</span></label>
        <select name="domain_code" class="select select-bordered">
            <option value="">— Không chọn —</option>
            @foreach(['D1'=>'D1 — Năng lực số cơ bản','D2'=>'D2 — Dữ liệu','D3'=>'D3 — AI','D4'=>'D4 — Quy trình','D5'=>'D5 — Đổi mới','D6'=>'D6 — Hiệu suất'] as $code => $label)
            <option value="{{ $code }}" @selected(old('domain_code', $item->domain_code ?? '') === $code)>{{ $label }}</option>
            @endforeach
        </select>
        @error('domain_code')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    {{-- Mức độ khó --}}
    <div class="form-control">
        <label class="label"><span class="label-text">Mức độ</span></label>
        <select name="difficulty" class="select select-bordered">
            <option value="">— Không chọn —</option>
            <option value="1" @selected(old('difficulty', $item->difficulty ?? '') == 1)>1 — Cơ bản</option>
            <option value="2" @selected(old('difficulty', $item->difficulty ?? '') == 2)>2 — Trung cấp</option>
            <option value="3" @selected(old('difficulty', $item->difficulty ?? '') == 3)>3 — Nâng cao</option>
        </select>
        @error('difficulty')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
    </div>
</div>
```

Validation trong `KcItemController::store()` và `update()`:
```php
'domain_code' => ['nullable', 'in:D1,D2,D3,D4,D5,D6'],
'difficulty'  => ['nullable', 'integer', 'in:1,2,3'],
```

---

## C7 — QR Code cho WorkforceCertification

### Mục tiêu

Sinh QR code chứa URL xác minh, lưu vào `qr_code_url` (field đã tồn tại), hiển thị trong trang chi tiết và PDF chứng nhận. Cung cấp trang xác minh công khai không cần đăng nhập.

### 7.1 Cài đặt package

```bash
composer require simplesoftwareio/simple-qrcode
```

Package `simplesoftwareio/simple-qrcode` v4.x — thuần PHP, không cần extension Node/Python.

### 7.2 Route xác minh công khai

**Thêm vào `Modules/Assessment/routes/web.php`:**
```php
Route::get('/verify/cert/{number}',
    [CertVerifyController::class, 'show'])
    ->name('assessment.cert.verify')
    ->withoutMiddleware(['auth', 'verified']);
```

### 7.3 Service — `Modules/Assessment/app/Services/CertificationQrService.php`

```php
<?php

namespace Modules\Assessment\Services;

use Illuminate\Support\Facades\Storage;
use Modules\Assessment\Models\WorkforceCertification;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificationQrService
{
    public function generateAndStore(WorkforceCertification $cert): string
    {
        $verifyUrl = route('assessment.cert.verify', ['number' => $cert->certificate_number]);

        $qrSvg = QrCode::format('svg')->size(200)->generate($verifyUrl);
        $path  = "qrcodes/certs/{$cert->uuid}.svg";

        Storage::disk('public')->put($path, $qrSvg);

        $publicUrl = Storage::disk('public')->url($path);
        $cert->update(['qr_code_url' => $publicUrl]);

        return $publicUrl;
    }
}
```

### 7.4 Controller — `Modules/Assessment/app/Http/Controllers/CertVerifyController.php`

```php
<?php

namespace Modules\Assessment\Http\Controllers;

use Illuminate\View\View;
use Modules\Assessment\Models\WorkforceCertification;

class CertVerifyController extends Controller
{
    public function show(string $number): View
    {
        $cert = WorkforceCertification::where('certificate_number', $number)
            ->with(['definition:id,name,level_code,cert_type_code', 'profile.employee:id,full_name'])
            ->firstOrFail();

        return view('assessment::certifications.verify', compact('cert'));
    }
}
```

### 7.5 View `assessment::certifications.verify` (public, không cần login)

```
@extends('layouts.public') {{-- hoặc layouts.minimal nếu có --}}

{{-- Không yêu cầu đăng nhập --}}

Tiêu đề: "Xác minh chứng nhận"

Badge:
- "HỢP LỆ" (badge-success) nếu $cert->status === 'active' && (!$cert->expires_at || $cert->expires_at->isFuture())
- "HẾT HẠN" (badge-error) nếu $cert->status === 'expired'
- "ĐÃ THU HỒI" (badge-error) nếu $cert->status === 'revoked'

Thông tin hiển thị:
- Tên người được cấp (không hiện điểm số chi tiết)
- Loại chứng nhận: $cert->definition->name
- Cấp độ: $cert->definition->level_code
- Ngày cấp: $cert->issued_at->format('d/m/Y')
- Ngày hết hạn: $cert->expires_at?->format('d/m/Y') ?? 'Không có hạn'
- Số chứng nhận: $cert->certificate_number

QR code: <img src="{{ $cert->qr_code_url }}" width="120">
```

### 7.6 Trigger tạo QR

**Trong `CertificationAdminController::issue()` sau khi save cert:**
```php
$cert = WorkforceCertification::create([...]);
app(CertificationQrService::class)->generateAndStore($cert);
```

**Trong `WorkforceExportController::profilePdf()`** — hiển thị QR trong PDF:
```php
// Trong Blade template PDF của individual profile
@foreach($certifications->where('qr_code_url', '!=', null) as $cert)
<img src="{{ $cert->qr_code_url }}" width="60" height="60" />
@endforeach
```

---

## Tóm tắt triển khai theo thứ tự ưu tiên

| Thứ tự | ID | Nội dung | Effort ước tính |
|---|---|---|---|
| 1 | C1 | Report Competency: routes + controller + 4 views | 2–3 ngày |
| 2 | C2 | Unit Workforce Dashboard: 1 method + 1 route + 1 view | 1 ngày |
| 3 | C3a | Campaign Bulk Invite: route + method + checkbox UI | 1 ngày |
| 4 | C3b | Campaign Reminder Job + Notification | 0.5 ngày |
| 5 | C3c | Campaign Export Excel | 0.5 ngày |
| 6 | C3d | Campaign Results by Unit: route + method + view | 0.5 ngày |
| 7 | C4 | KcItem ↔ RoadmapMilestone: migration + 2 models + admin | 1.5 ngày |
| 8 | C5 | Learning Progress: migration + model + 3 routes + UI | 1.5 ngày |
| 9 | C6 | KcItem domain_code + difficulty: migration + form | 0.5 ngày |
| 10 | C7 | QR Code: package + service + verify controller + view | 1 ngày |

**Tổng ước tính:** 10–12 ngày công triển khai

---

## Tham chiếu — Công thức & Hằng số hệ thống

### Trust Score
```
workforce_trust_score = TDWCF×30% + CertComposite×25% + KPI×20% + Sandbox×15% + Portfolio×10%
```
Implemented tại: `WorkforceProfile::recalculateTrustScore()`

### Certification Composite Score
```
composite_score = Assessment×30% + Sandbox×25% + Impact×25% + Portfolio×20%
```
Implemented tại: `WorkforceCertification::calculateCompositeScore()`

### CGI (Competency Growth Index)
```
CGI = (current_tdwcf - previous_tdwcf) / previous_tdwcf × 100  (%)
```
Implemented tại: `CalculateCgiAction`

### Sandbox Auto-Score (SandboxScoringService)
```
final = quality×40% + productivity×35% + aiAdoption×25%
pass_threshold = 60.0
```

### Maturity Levels — 5 cấp
| Code | Tên | TDWCF Range |
|---|---|---|
| `DIGITAL_BEGINNER` | Người mới | 0–29 |
| `DIGITAL_AWARE` | Nhận thức | 30–44 |
| `DIGITAL_PRACTITIONER` | Thực hành | 45–59 |
| `DIGITAL_PROFESSIONAL` | Chuyên nghiệp | 60–79 |
| `DIGITAL_LEADER` | Lãnh đạo | 80–100 |

### 6 Domains TDWCF
| Code | Tên | Column DB |
|---|---|---|
| D1 | Năng lực số cơ bản | `score_d1_digital_literacy` |
| D2 | Dữ liệu & Phân tích | `score_d2_data_literacy` |
| D3 | AI & Tự động hóa | `score_d3_ai_literacy` |
| D4 | Quy trình & TĐH | `score_d4_workflow` |
| D5 | Đổi mới & Sáng tạo | `score_d5_innovation` |
| D6 | Hiệu suất & Tác động | `score_d6_performance` |

### Packages đã cài (không cần thêm)
- `rap2hpoutre/fast-excel` — Excel export
- `spatie/laravel-pdf` — PDF export
- `spatie/laravel-activitylog` — Activity log
- `spatie/laravel-permission` — RBAC
- `php-ml/php-ml` — ML (LeadScoringModelService)

### Package cần cài (C7)
```bash
composer require simplesoftwareio/simple-qrcode
```

### Event-Listener Pipeline (đã hoàn chỉnh)
```
Survey Submit → CalculateSurveyScoreJob → RunAssessmentAction → AssessmentCompleted
    → UpdateEmployeeDigitalCompetencyListener (employee fields)
    → UpdateWorkforceProfileOnAssessmentListener (6 domains + trust + history)

SandboxScoringService::autoScore() → SandboxCompleted
    → UpdateWorkforceProfileOnSandboxListener (stats + trust + CheckCertEligibility + CareerLevelService)

CertificationAdminController::issue() → CertificationIssued
    → UpdateWorkforceProfileOnCertificationListener (highest cert + trust + CareerLevelService)

FinalizeReviewAction → PerformanceReviewFinalized
    → SyncWorkforceProfileOnPerformanceReviewFinalizedListener (domain scores từ criteria)

OrgMembershipService::deactivate() → SnapshotPassportEntryJob → OrgExitPassportReadyMail
```
