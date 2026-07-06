# Blueprint Tích hợp n8n — Hệ thống MinHan

> **Mục tiêu tài liệu**: Tổng hợp toàn diện các ý tưởng, giải pháp và lộ trình tích hợp n8n vào hệ thống MinHan, dựa trên thực tế nghiệp vụ và cấu trúc dữ liệu hiện có. Tài liệu này phục vụ làm căn cứ thiết kế, phân tích ROI, và kế hoạch triển khai từng giai đoạn.

---

## Mục lục

1. [Tổng quan kiến trúc](#1-tổng-quan-kiến-trúc)
2. [Nền tảng kỹ thuật kết nối](#2-nền-tảng-kỹ-thuật-kết-nối)
3. [Recruitment & ATS](#3-recruitment--ats)
4. [Nhân sự & Hành chính HR](#4-nhân-sự--hành-chính-hr)
5. [KPI & Đánh giá hiệu suất](#5-kpi--đánh-giá-hiệu-suất)
6. [Nghỉ phép](#6-nghỉ-phép)
7. [Tin tuyển dụng & Marketplace](#7-tin-tuyển-dụng--marketplace)
8. [Kho tri thức KC](#8-kho-tri-thức-kc)
9. [SOP & Quy trình nội bộ](#9-sop--quy-trình-nội-bộ)
10. [Lead & CRM](#10-lead--crm)
11. [Survey & Assessment](#11-survey--assessment)
12. [Dự án & Công việc](#12-dự-án--công-việc)
13. [Tự động hóa nội bộ (WorkflowAutomation bridge)](#13-tự-động-hóa-nội-bộ-workflowautomation-bridge)
14. [ActivityLog & Giám sát](#14-activitylog--giám-sát)
15. [Cross-module: Onboarding tích hợp](#15-cross-module-onboarding-tích-hợp)
16. [Bảng tổng hợp ưu tiên](#16-bảng-tổng-hợp-ưu-tiên)
17. [Lộ trình triển khai](#17-lộ-trình-triển-khai)
18. [Hướng dẫn kỹ thuật triển khai](#18-hướng-dẫn-kỹ-thuật-triển-khai)

---

## 1. Tổng quan kiến trúc

### 1.1 Vai trò của n8n trong hệ thống

```
┌─────────────────────────────────────────────────────────────────┐
│                        MinHan Platform                          │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐  │
│  │Recruitment│  │   Leave  │  │   KPI    │  │     Lead     │  │
│  │   Module │  │  Module  │  │  Module  │  │    Module    │  │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └──────┬───────┘  │
│       │              │              │                │           │
│       └──────────────┴──────────────┴────────────────┘           │
│                              │                                   │
│                   Laravel Event / Webhook                        │
│                         Dispatcher                               │
└───────────────────────────┬─────────────────────────────────────┘
                            │  HTTPS POST
                            ▼
              ┌─────────────────────────┐
              │          n8n            │
              │   Workflow Engine       │
              │                         │
              │  ┌─────────────────┐   │
              │  │  Trigger Nodes  │   │
              │  │  Logic/Filter   │   │
              │  │  Transform      │   │
              │  │  Action Nodes   │   │
              └──┴────────┬────────┴───┘
                          │
          ┌───────────────┼───────────────────────┐
          │               │                       │
          ▼               ▼                       ▼
   ┌─────────────┐ ┌─────────────┐       ┌─────────────────┐
   │  Email/Zalo │ │  Slack/Teams│       │  External APIs   │
   │  SMTP/API   │ │  Webhook    │       │  Google/LinkedIn │
   └─────────────┘ └─────────────┘       │  DocuSign/HRM   │
                                         └─────────────────┘
          │               │
          └───────────────┘
                  │  Ghi kết quả lại
                  ▼
         Laravel REST API
         (backend.api.*)
```

### 1.2 Hai luồng kết nối chính

| Chiều | Cơ chế | Mục đích |
|-------|--------|----------|
| **MinHan → n8n** | Webhook POST khi có sự kiện (model event, status change) | Trigger workflow |
| **n8n → MinHan** | REST API (`/backend/api/*`) hoặc dedicated webhook endpoint | Ghi dữ liệu, cập nhật trạng thái |

### 1.3 Nguyên tắc thiết kế

- **Loose coupling**: MinHan chỉ phát sự kiện; n8n xử lý toàn bộ integration logic
- **Idempotent**: Mỗi workflow n8n phải xử lý được trường hợp gọi trùng lặp
- **Traceable**: Mọi action do n8n thực hiện đều ghi vào `ActivityLog` qua API
- **Failsafe**: n8n thất bại không làm gián đoạn nghiệp vụ chính trong MinHan

---

## 2. Nền tảng kỹ thuật kết nối

### 2.1 Laravel — Phát sự kiện ra n8n

#### Tạo Webhook Dispatcher Service

```php
// app/Services/N8nDispatcher.php
class N8nDispatcher
{
    public function dispatch(string $event, array $payload): void
    {
        Http::async()->post(config('services.n8n.webhook_base') . '/' . $event, [
            'event'     => $event,
            'org_id'    => TenantContext::id(),
            'timestamp' => now()->toIso8601String(),
            'payload'   => $payload,
        ]);
    }
}
```

#### Đăng ký trong Laravel Observer

```php
// Ví dụ: app/Observers/RcApplicationObserver.php
class RcApplicationObserver
{
    public function updated(RcApplication $application): void
    {
        if ($application->wasChanged('status') || $application->wasChanged('current_stage_id')) {
            app(N8nDispatcher::class)->dispatch('recruitment.application.stage_changed', [
                'application_id'   => $application->uuid,
                'candidate_name'   => $application->candidate->full_name,
                'candidate_email'  => $application->candidate->email,
                'old_stage'        => $application->getOriginal('current_stage_id'),
                'new_stage'        => $application->current_stage_id,
                'status'           => $application->status->value,
                'job_title'        => $application->jobPost?->title,
                'assigned_to_email'=> $application->assignedTo?->email,
            ]);
        }
    }
}
```

#### Danh sách Events cần đăng ký

```
# Recruitment
recruitment.candidate.created
recruitment.application.stage_changed
recruitment.application.rejected
recruitment.interview.scheduled
recruitment.interview.status_changed      (completed / no_show)
recruitment.offer.submitted_for_approval
recruitment.offer.approved
recruitment.offer.sent
recruitment.offer.accepted
recruitment.offer.rejected

# Leave
leave.request.submitted
leave.request.approved
leave.request.rejected
leave.request.cancelled

# Employee
employee.created
employee.status_changed                   (active / resigned / terminated)
employee.probation_ending                 (cron: 7 ngày trước)

# KPI
kpi.goal.created
kpi.goal.progress_updated
kpi.cycle.closed

# Performance Review
performance.review.cycle_opened
performance.review.submitted
performance.review.acknowledged
performance.review.finalized

# Lead
lead.created
lead.stage_changed
lead.status_changed                       (converted / archived)

# Job Posting
job_post.published
job_post.closed
job_post.submit_review

# KC / SOP
kc_item.submitted_for_approval
kc_item.approved
kc_item.expiring_soon                     (cron)
sop.submitted_for_approval
sop.approved

# Survey
survey.response.completed
survey.assessment.scored
```

### 2.2 Dedicated Webhook Endpoint (nhận callback từ n8n)

```php
// routes/api.php
Route::middleware(['auth:sanctum'])->prefix('webhooks')->group(function () {
    Route::post('/n8n/callback', [N8nCallbackController::class, 'handle']);
});
```

```php
// app/Http/Controllers/Api/N8nCallbackController.php
class N8nCallbackController
{
    public function handle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action'  => 'required|string',
            'ref_id'  => 'required|string',
            'data'    => 'required|array',
        ]);

        match ($validated['action']) {
            'candidate.score_updated'      => $this->updateCandidateScore($validated),
            'leave.auto_approved'          => $this->autoApproveLeave($validated),
            'interview.calendar_created'   => $this->logCalendarEvent($validated),
            'offer.pdf_generated'          => $this->attachOfferPdf($validated),
            default                        => Log::warning('n8n: unknown action', $validated),
        };

        return response()->json(['ok' => true]);
    }
}
```

### 2.3 Cấu hình môi trường

```env
# .env
N8N_WEBHOOK_BASE=https://n8n.yourdomain.com/webhook
N8N_API_KEY=your_n8n_api_key
N8N_CALLBACK_SECRET=random_secret_for_hmac_verification
```

```php
// config/services.php
'n8n' => [
    'webhook_base' => env('N8N_WEBHOOK_BASE'),
    'api_key'      => env('N8N_API_KEY'),
    'secret'       => env('N8N_CALLBACK_SECRET'),
],
```

---

## 3. Recruitment & ATS

> **Bối cảnh**: Module ATS đầy đủ nhất trong hệ thống với pipeline: Candidate → Application → PipelineStage → Interview → Offer → Employee. Đây là khu vực có ROI tích hợp n8n cao nhất.

---

### WF-RC-01 · AI CV Screening tự động

**Vấn đề giải quyết**: HR phải đọc thủ công hàng trăm CV, mất 30–60 phút/buổi sàng lọc.

**Trigger**: `recruitment.candidate.created`

**Luồng n8n**:
```
Webhook (candidate.created)
  │
  ├─► Fetch CV URL từ rc_candidates.resume_url
  │
  ├─► HTTP GET — Download CV file (PDF)
  │
  ├─► OpenAI / Claude API — Phân tích CV:
  │     Prompt: "Extract: years_experience, skills[], education,
  │              current_salary, expected_salary, red_flags[].
  │              Score 0-100 vs job requirements: {job_title}"
  │
  ├─► IF score >= threshold (e.g. 70)
  │     └─► MinHan API PATCH /candidates/{uuid}
  │           { ai_score: 85, ai_summary: "...", ai_tags: [...] }
  │
  └─► IF score < threshold
        └─► MinHan API POST /candidates/{uuid}/notes
              { note: "AI screening: score thấp (45/100). Lý do: ..." }

  └─► Slack notify HR channel:
        "📋 CV mới: Nguyễn Văn A | Score: 85/100 | {link}"
```

**Trường dữ liệu cần thêm vào `rc_candidates`**:
```
ai_score          TINYINT UNSIGNED NULL
ai_summary        TEXT NULL
ai_screened_at    TIMESTAMP NULL
ai_tags           JSON NULL
```

**Kết quả**: Giảm 70% thời gian sơ vấn CV. HR chỉ focus vào candidates score ≥ threshold.

---

### WF-RC-02 · Tự động lên lịch phỏng vấn

**Vấn đề giải quyết**: Back-and-forth email xác nhận lịch phỏng vấn tốn 1–3 ngày.

**Trigger**: `recruitment.application.stage_changed` (khi stage mới có `stage_type = interview`)

**Luồng n8n**:
```
Webhook (application.stage_changed → interview stage)
  │
  ├─► Fetch interviewer list từ API
  │     GET /api/recruitment/applications/{id}/interviewers
  │
  ├─► Google Calendar API — Tìm free slot trong 3 ngày tới
  │     (free/busy query cho tất cả interviewer)
  │
  ├─► Tạo calendar event:
  │     Title: "Phỏng vấn: {candidate_name} — {job_title}"
  │     Attendees: interviewer emails + candidate email
  │     Location: Google Meet link (tự tạo)
  │
  ├─► MinHan API POST /recruitment/interviews
  │     { application_id, scheduled_at, meeting_url, ... }
  │
  ├─► Email candidate:
  │     Template: "Thư mời phỏng vấn" + calendar invite attachment
  │
  └─► Slack DM → interviewer(s):
        "🗓️ Bạn có lịch phỏng vấn mới: {candidate} | {datetime} | {meeting_url}"
```

**Kết quả**: Lịch phỏng vấn được xác nhận trong vòng 5 phút thay vì 1–3 ngày.

---

### WF-RC-03 · Nhắc nhở & Escalation phỏng vấn

**Trigger**: Cron — mỗi ngày 08:00

**Luồng n8n**:
```
Cron (daily 08:00)
  │
  ├─► MinHan API GET /api/recruitment/interviews
  │     filter: status=scheduled, scheduled_at=[now, +24h]
  │
  ├─► ForEach interview:
  │     ├─► Email reminder → candidate
  │     │     "Nhắc nhở: Phỏng vấn của bạn lúc {time} hôm nay"
  │     └─► Slack DM → interviewer
  │           "🔔 Nhắc: Phỏng vấn với {candidate} lúc {time}"
  │
  └─► MinHan API GET — Interviews quá hạn (status=scheduled, scheduled_at < now-1h)
        ForEach overdue:
          Slack → HR Manager: "⚠️ Phỏng vấn chưa được cập nhật kết quả: {candidate}"
```

---

### WF-RC-04 · Tổng hợp kết quả phỏng vấn & tự động chuyển stage

**Trigger**: `recruitment.interview.status_changed` (status = completed)

**Luồng n8n**:
```
Webhook (interview.completed)
  │
  ├─► Fetch tất cả evaluations của interview này
  │     GET /api/recruitment/interviews/{id}/evaluations
  │
  ├─► Tính average score từ tất cả evaluator
  │
  ├─► IF tất cả evaluator đã submit AND average >= pass_threshold:
  │     └─► MinHan API POST /recruitment/applications/{id}/move
  │           { stage: next_stage }
  │         Slack → HR: "✅ {candidate} PASS phỏng vấn — Chuyển sang {next_stage}"
  │
  └─► IF có evaluator chưa submit sau 24h:
        Slack DM → evaluator: "⚠️ Bạn chưa nộp đánh giá cho {candidate}"
```

---

### WF-RC-05 · Offer Letter — Tạo PDF & Quy trình ký

**Trigger**: `recruitment.offer.approved`

**Luồng n8n**:
```
Webhook (offer.approved)
  │
  ├─► Fetch offer data đầy đủ
  │     GET /api/recruitment/offers/{uuid}
  │
  ├─► Render PDF từ HTML template:
  │     - Tên ứng viên, vị trí, mức lương, ngày bắt đầu
  │     - Logo công ty, chữ ký giám đốc
  │     (dùng n8n HTML → PDF node hoặc Puppeteer webhook)
  │
  ├─► Upload PDF lên S3/Storage
  │     MinHan API PATCH /offers/{uuid} { offer_pdf_url: "..." }
  │
  ├─► DocuSign/eSign API — Gửi yêu cầu ký
  │     Signers: [candidate_email, hr_director_email]
  │
  └─► Email → candidate:
        Subject: "Thư đề nghị việc làm — {company_name}"
        Body: Link ký điện tử + nội dung offer tóm tắt
```

---

### WF-RC-06 · Follow-up Offer chưa phản hồi

**Trigger**: Cron — hàng ngày 09:00

**Luồng n8n**:
```
Cron (daily 09:00)
  │
  ├─► MinHan API GET /api/recruitment/offers
  │     filter: status=sent, sent_at < now-2d, expire_at > now
  │
  └─► ForEach offer chưa phản hồi:
        ├─► Email nhắc → candidate (template lịch sự)
        │     Day 2: "Bạn có câu hỏi gì về đề nghị việc làm không?"
        │     Day 4: "Đề nghị sẽ hết hạn vào {expire_at}"
        └─► Slack → HR: "📨 Offer {candidate} chờ {N} ngày, hết hạn {date}"
```

---

### WF-RC-07 · Sync ứng viên từ Marketplace → ATS

**Trigger**: `recruitment.application.created` (apply_source = marketplace)

**Luồng n8n**:
```
Webhook (application.created từ Marketplace)
  │
  ├─► Fetch MktApplicant profile đầy đủ
  │
  ├─► Enrich thêm từ LinkedIn API (nếu có linkedin_url)
  │
  ├─► MinHan API PATCH /candidates/{uuid}
  │     Cập nhật: skills, experience, portfolio nếu thiếu
  │
  └─► Slack → Recruiter phụ trách:
        "🆕 Ứng viên từ Marketplace: {name} | {job_title} | Score: {ai_score}"
```

---

### WF-RC-08 · Báo cáo tuyển dụng tuần

**Trigger**: Cron — Thứ Hai 08:00

**Luồng n8n**:
```
Cron (Monday 08:00)
  │
  ├─► MinHan API — Lấy số liệu tuần trước:
  │     - Candidates mới
  │     - Applications theo stage
  │     - Interviews scheduled / completed
  │     - Offers sent / accepted / rejected
  │     - Time-to-hire trung bình
  │
  ├─► Render HTML report (bảng + biểu đồ đơn giản)
  │
  └─► Email → HR Manager + CEO:
        Subject: "Báo cáo Tuyển dụng Tuần {W} — {ngày}"
```

---

## 4. Nhân sự & Hành chính HR

### WF-HR-01 · Onboarding tự động khi Employee tạo mới

**Trigger**: `employee.created`

**Luồng n8n**:
```
Webhook (employee.created)
  │
  ├─► PARALLEL:
  │   ├─► IT Ticket (Jira/Linear): "Setup tài khoản cho {name}"
  │   │     - Tạo email công ty
  │   │     - Cấp quyền hệ thống theo role
  │   │     - Laptop/thiết bị
  │   │
  │   ├─► Slack → Channel #hr-onboarding:
  │   │     "👋 Nhân viên mới: {name} | {department} | Bắt đầu: {date}"
  │   │
  │   └─► Email → nhân viên mới:
  │         - Welcome letter
  │         - Handbook link
  │         - Lịch tuần đầu (orientation schedule)
  │
  ├─► Google Calendar: Tạo event "Orientation — {name}" ngày đầu tiên
  │     Invite: HR, Manager trực tiếp, buddy (nếu có)
  │
  ├─► Tạo Google Drive folder: "Hồ sơ — {name}"
  │     Upload: Contract template, ID copy, etc.
  │
  └─► MinHan API POST /employees/{id}/notes
        "Onboarding checklist đã được khởi tạo qua n8n"
```

---

### WF-HR-02 · Nhắc nhở kết thúc thử việc

**Trigger**: Cron — hàng ngày 08:00

**Luồng n8n**:
```
Cron (daily 08:00)
  │
  ├─► MinHan API GET /api/employees
  │     filter: status=probation,
  │             probation_end_date IN [today+7, today+3, today+1, today]
  │
  └─► ForEach employee:
        ├─► Slack → HR Manager:
        │     "⏳ {name} kết thúc thử việc {N} ngày nữa — Cần quyết định confirm/terminate"
        │
        └─► IF probation_end_date = today:
              Email → Manager + HR:
              "🔔 HẾT HẠN THỬ VIỆC HÔM NAY: {name} | Vui lòng cập nhật trạng thái"
```

---

### WF-HR-03 · Nhắc sinh nhật & kỷ niệm công tác

**Trigger**: Cron — hàng ngày 08:30

**Luồng n8n**:
```
Cron (daily 08:30)
  │
  ├─► MinHan API GET /api/employees — filter: active
  │
  ├─► Filter: date_of_birth.month = today.month AND date_of_birth.day = today.day
  │     └─► Slack → #general: "🎂 Chúc mừng sinh nhật {name} — {department}!"
  │           Email → {name}: Thiệp chúc mừng sinh nhật
  │
  └─► Filter: hired_at.month = today.month AND hired_at.day = today.day
        └─► years = today.year - hired_at.year
            Slack → #general: "🎉 {name} kỷ niệm {years} năm làm việc tại công ty!"
```

---

### WF-HR-04 · Cảnh báo hợp đồng sắp hết hạn

**Trigger**: Cron — hàng ngày 08:00

**Luồng n8n**:
```
Cron (daily 08:00)
  │
  ├─► MinHan API GET /api/employees
  │     filter: contract_end BETWEEN [today+30, today+60]
  │
  └─► ForEach employee:
        ├─► Email → HR + Manager:
        │     "📋 Hợp đồng {name} hết hạn ngày {date} ({N} ngày nữa)"
        └─► Slack → HR channel (nếu còn < 14 ngày)
```

---

### WF-HR-05 · Đồng bộ khi nhân viên nghỉ việc

**Trigger**: `employee.status_changed` (→ Resigned / Terminated)

**Luồng n8n**:
```
Webhook (employee.status = resigned/terminated)
  │
  ├─► IT Ticket: "Offboarding {name} — Ngày cuối: {left_at}"
  │     - Thu hồi thiết bị
  │     - Disable tài khoản email (sau left_at)
  │     - Revoke system access
  │
  ├─► HR Checklist gửi qua email:
  │     - Bàn giao công việc
  │     - Hoàn trả tài sản
  │     - Phỏng vấn thôi việc (exit interview)
  │
  ├─► Google Calendar: Block calendar sau left_at
  │
  └─► Slack → HR + Manager:
        "👋 {name} sẽ rời công ty ngày {left_at} — Xem checklist offboarding"
```

---

## 5. KPI & Đánh giá hiệu suất

### WF-KPI-01 · Báo cáo KPI tuần cho Manager

**Trigger**: Cron — Thứ Sáu 17:00

**Luồng n8n**:
```
Cron (Friday 17:00)
  │
  ├─► MinHan API GET /api/kpi/goals
  │     filter: cycle hiện tại, status=active
  │     group_by: employee.manager_id
  │
  ├─► ForEach manager:
  │     ├─► Build bảng tóm tắt team KPI:
  │     │     Tên | Mục tiêu | Tiến độ | % hoàn thành | Xu hướng
  │     │
  │     └─► Email → manager:
  │           Subject: "KPI Team — Tuần {W} | {date}"
  │           Body: Bảng HTML + link hệ thống
  │
  └─► Email → CEO/Director: Bảng tổng hợp toàn công ty
```

---

### WF-KPI-02 · Cảnh báo KPI nguy hiểm

**Trigger**: `kpi.goal.progress_updated` HOẶC Cron — hàng ngày 09:00

**Luồng n8n**:
```
Webhook/Cron
  │
  ├─► MinHan API GET /api/kpi/goals
  │     filter: status=active, achievement_pct < 30, cycle gần kết thúc (< 20% thời gian)
  │
  └─► ForEach at-risk goal:
        ├─► Slack DM → employee:
        │     "⚠️ KPI '{title}' của bạn đang ở {pct}% — Cần hành động ngay"
        └─► Slack → manager:
              "🚨 {name}: KPI '{title}' nguy hiểm ({pct}%) — {deadline}"
```

---

### WF-KPI-03 · Nhắc nhở cập nhật tiến độ KPI

**Trigger**: Cron — hàng tuần, Thứ Tư 10:00

**Luồng n8n**:
```
Cron (Wednesday 10:00)
  │
  ├─► MinHan API GET /api/kpi/goals
  │     filter: status=active, goal_type=manual, updated_at < now-7d
  │
  └─► ForEach goal chưa cập nhật:
        Slack DM → employee:
        "📊 Nhắc: Hãy cập nhật tiến độ KPI '{title}' — Chưa cập nhật {N} ngày"
```

---

### WF-KPI-04 · Mở chu kỳ Performance Review

**Trigger**: `performance.review.cycle_opened` HOẶC Cron theo lịch tổ chức

**Luồng n8n**:
```
Webhook/Cron (cycle mở)
  │
  ├─► MinHan API GET — Danh sách review cần hoàn thành
  │
  ├─► Email → từng employee (người được review):
  │     "📋 Chu kỳ đánh giá {period} đã mở — Hạn nộp: {deadline}"
  │
  ├─► Email → từng reviewer (manager):
  │     "📝 Bạn cần hoàn thành {N} đánh giá trước {deadline}"
  │
  └─► Slack → #hr-announcements:
        "📣 Chu kỳ đánh giá hiệu suất {period} đã bắt đầu"
```

---

### WF-KPI-05 · Escalation đánh giá chưa hoàn thành

**Trigger**: Cron — hàng ngày trong thời gian review cycle mở

**Luồng n8n**:
```
Cron (daily trong review period)
  │
  ├─► MinHan API — Reviews chưa submitted, còn {N} ngày:
  │
  ├─► N = 7: Email nhắc lần 1 → reviewer
  ├─► N = 3: Email nhắc lần 2 + Slack DM → reviewer
  └─► N = 1: Slack → HR Manager: "⚠️ {reviewer} chưa hoàn thành {N} đánh giá — Hết hạn ngày mai"
```

---

## 6. Nghỉ phép

### WF-LV-01 · Thông báo duyệt đơn nghỉ phép

**Trigger**: `leave.request.submitted`

**Luồng n8n**:
```
Webhook (leave.request.submitted)
  │
  ├─► Fetch employee + manager info
  │
  ├─► Check: Có đủ leave balance không?
  │     GET /api/leave/balances/employee/{employee_id}
  │
  ├─► IF balance đủ:
  │     ├─► Slack DM → manager:
  │     │     "📩 {employee} xin nghỉ {leave_type} từ {from} đến {to} ({days} ngày)
  │     │      Lý do: {reason}
  │     │      👉 Duyệt: {approve_link} | Từ chối: {reject_link}"
  │     └─► Email → manager (backup nếu không dùng Slack)
  │
  └─► IF balance không đủ:
        Slack DM → employee:
        "❌ Bạn không đủ số ngày phép để xin nghỉ ({balance} ngày còn lại)"
        MinHan API: Auto-reject với lý do "Không đủ số dư phép"
```

---

### WF-LV-02 · Auto-approve khi manager không phản hồi

**Trigger**: Cron — hàng giờ

**Luồng n8n**:
```
Cron (hourly)
  │
  ├─► MinHan API GET /api/leave/requests
  │     filter: status=pending, created_at < now-48h
  │
  └─► ForEach đơn quá 48h chưa duyệt:
        ├─► Lần 1 (48h): Slack → manager: "⏰ Đơn nghỉ phép {name} chờ duyệt 48h rồi"
        ├─► Lần 2 (72h): Slack → manager + HR: "⚠️ Chưa duyệt sau 72h"
        └─► Lần 3 (96h): MinHan API POST — Auto-approve
              Note: "Tự động duyệt do manager không phản hồi sau 96h"
              Notify: Slack → employee + manager + HR
```

---

### WF-LV-03 · Thông báo kết quả & cập nhật lịch

**Trigger**: `leave.request.approved` / `leave.request.rejected`

**Luồng n8n**:
```
Webhook (leave.request.approved)
  │
  ├─► Email → employee:
  │     "✅ Đơn nghỉ phép của bạn đã được DUYỆT
  │      Từ: {date_from} đến {date_to} ({days} ngày {leave_type})"
  │
  ├─► Google Calendar:
  │     Tạo event: "Nghỉ phép — {employee_name}"
  │     Start: date_from, End: date_to, Mark as Out of Office
  │
  └─► Slack → #hr-leave hoặc team channel:
        "🏖️ {name} sẽ nghỉ từ {from} → {to}"

Webhook (leave.request.rejected)
  │
  └─► Email → employee:
        "❌ Đơn nghỉ phép bị từ chối. Lý do: {rejection_reason}
         Liên hệ HR nếu cần hỗ trợ."
```

---

### WF-LV-04 · Tổng hợp lịch nghỉ phép tuần tới

**Trigger**: Cron — Thứ Sáu 16:00

**Luồng n8n**:
```
Cron (Friday 16:00)
  │
  ├─► MinHan API GET — Leave requests approved, next week
  │
  ├─► Group by department/team
  │
  └─► Slack → từng channel team:
        "📅 Lịch nghỉ phép tuần tới ({date range}):
         • Nguyễn Văn A: Thứ 2–3 (Annual Leave)
         • Trần Thị B: Thứ 4 (Sick Leave)"
```

---

## 7. Tin tuyển dụng & Marketplace

### WF-JP-01 · Đăng tin đa kênh khi Job Post Published

**Trigger**: `job_post.published`

**Luồng n8n**:
```
Webhook (job_post.published)
  │
  ├─► PARALLEL — Đăng lên các kênh:
  │   ├─► LinkedIn Job API
  │   │     POST /jobs với: title, description, salary range, location
  │   │
  │   ├─► VietnamWorks API (nếu có)
  │   │
  │   ├─► TopCV API (nếu có)
  │   │
  │   └─► Slack → #recruiting:
  │         "📢 Tin tuyển dụng mới: {title} | {department}
  │          Share để tìm ứng viên! {public_url}"
  │
  └─► MinHan API PATCH /job-posts/{uuid}
        { external_job_ids: { linkedin: "...", vietnamworks: "..." } }
```

---

### WF-JP-02 · Cảnh báo Tin tuyển dụng sắp hết hạn

**Trigger**: Cron — hàng ngày 09:00

**Luồng n8n**:
```
Cron (daily 09:00)
  │
  ├─► MinHan API GET — Job posts: status=published, expire_at IN [today+7, today+3, today+1]
  │
  └─► ForEach sắp hết hạn:
        Slack → recruiter phụ trách + HR:
        "⏳ Tin '{title}' hết hạn {N} ngày nữa ({expire_at})
         Ứng viên đã apply: {count} | Đang phỏng vấn: {interview_count}
         👉 Gia hạn: {link}"
```

---

### WF-JP-03 · Báo cáo hiệu quả tin tuyển dụng

**Trigger**: Khi Job Post đóng (`job_post.closed`)

**Luồng n8n**:
```
Webhook (job_post.closed)
  │
  ├─► MinHan API GET /api/job-posts/{id}/analytics
  │
  ├─► MinHan API GET /api/recruitment/applications
  │     filter: jp_job_post_id = {id}
  │
  └─► Email → HR Manager:
        "📊 Báo cáo tin tuyển dụng: {title}
         ─────────────────────────
         Thời gian đăng: {days} ngày
         Lượt xem: {views}
         Ứng viên nộp: {total_applications}
         Đã phỏng vấn: {interviewed}
         Đã offer: {offered}
         Đã nhận: {accepted}
         Time-to-hire: {avg_days} ngày
         Chi phí/hire: {cost}"
```

---

### WF-MKT-01 · Duyệt tổ chức Marketplace

**Trigger**: Khi org đăng ký Marketplace (tạo MktApplicant với status=pending)

**Luồng n8n**:
```
Webhook (marketplace.org.registration_submitted)
  │
  ├─► Fetch org info + documents
  │
  ├─► Auto-check cơ bản:
  │     - Mã số thuế hợp lệ? (gọi API tra cứu MST)
  │     - Thông tin đầy đủ?
  │
  ├─► Slack → Admin channel:
  │     "🏢 Tổ chức mới đăng ký Marketplace:
  │      Tên: {org_name} | MST: {tax_code}
  │      Kết quả auto-check: {pass/fail}
  │      👉 Duyệt: {link}"
  │
  └─► Email → org: "Chúng tôi đang xem xét đơn đăng ký của bạn (1–2 ngày làm việc)"
```

---

## 8. Kho tri thức KC

### WF-KC-01 · Quy trình duyệt bài viết

**Trigger**: `kc_item.submitted_for_approval`

**Luồng n8n**:
```
Webhook (kc_item.submitted_for_approval)
  │
  ├─► Fetch item details + owner info
  │
  ├─► Slack → KC Admin / Reviewer:
  │     "📝 Bài viết mới chờ duyệt:
  │      Tiêu đề: {title} | Loại: {type} | Tác giả: {owner}
  │      👉 Xem & duyệt: {link}"
  │
  └─► Email → reviewer (backup):
        "Bài viết KC mới cần duyệt trong 48h"
```

**Trigger**: `kc_item.approved`

```
Webhook (kc_item.approved)
  │
  ├─► Email → owner/author:
  │     "✅ Bài viết '{title}' đã được duyệt và xuất bản"
  │
  └─► Slack → channel liên quan (theo category/department):
        "📚 Bài viết mới trong Kho tri thức: '{title}'
         Tác giả: {owner} | Loại: {type}
         👉 Đọc ngay: {link}"
```

---

### WF-KC-02 · Cảnh báo nội dung sắp hết hạn

**Trigger**: Cron — hàng ngày 08:00

**Luồng n8n**:
```
Cron (daily 08:00)
  │
  ├─► MinHan API GET /api/kc/analytics/expiring-soon
  │     (route backend.api.kc.analytics.expiring-soon đã có sẵn)
  │
  └─► ForEach item sắp hết hạn:
        ├─► N = 30 ngày: Email → owner: "Bài viết '{title}' hết hạn 30 ngày nữa — Review lại"
        ├─► N = 7 ngày:  Slack DM → owner + manager KC
        └─► N = 0 (đã hết hạn): Slack → KC Admin: "⚠️ '{title}' đã hết hạn — Cần xử lý"
```

---

### WF-KC-03 · Digest nội dung KC mới hàng tuần

**Trigger**: Cron — Thứ Hai 08:00

**Luồng n8n**:
```
Cron (Monday 08:00)
  │
  ├─► MinHan API GET /api/kc-items
  │     filter: status=approved, created_at >= last_week
  │     sort: view_count DESC
  │
  └─► Email → toàn bộ nhân viên (hoặc per-department):
        "📚 Nội dung KC mới tuần qua:
         • {title_1} — {author} ({views} lượt xem)
         • {title_2} — {author}
         ...
         Xem tất cả: {link}"
```

---

## 9. SOP & Quy trình nội bộ

### WF-SOP-01 · Quy trình duyệt SOP

**Trigger**: `sop.submitted_for_approval`

**Luồng n8n**:
```
Webhook (sop.submitted_for_approval)
  │
  ├─► Fetch SOP + RACI matrix + owner info
  │
  ├─► Gửi notify đến từng người trong RACI (role=Approver/Accountable):
  │     Slack DM + Email:
  │     "📋 SOP '{title}' (v{version}) đang chờ duyệt
  │      Phòng ban: {department} | Chi nhánh: {branch}
  │      Ngày hiệu lực dự kiến: {effective_date}
  │      👉 Xem & Duyệt: {link}"
  │
  └─► Theo dõi: Nếu sau 3 ngày chưa có quyết định → Escalate lên cấp trên
```

---

### WF-SOP-02 · Publish SOP đã duyệt → Thông báo team liên quan

**Trigger**: `sop.approved`

**Luồng n8n**:
```
Webhook (sop.approved)
  │
  ├─► Fetch RACI matrix — lấy danh sách Informed + Consulted
  │
  ├─► Email → tất cả người trong RACI (Informed):
  │     "📣 SOP mới có hiệu lực: '{title}' (v{version})
  │      Ngày hiệu lực: {effective_date}
  │      👉 Đọc ngay: {link}"
  │
  └─► Slack → channel department liên quan:
        "✅ SOP '{title}' phiên bản {version} đã được duyệt"
```

---

### WF-SOP-03 · Cảnh báo SOP sắp hết hạn

**Trigger**: Cron — hàng ngày 08:00

```
Cron → Check SOP.expired_date → Email/Slack owner + department head
N=60: Thông báo cần review
N=30: Nhắc lần 2 + assign task cho owner
N=7:  Cảnh báo cấp urgent
N=0:  SOP hết hạn — cần cập nhật khẩn
```

---

## 10. Lead & CRM

### WF-LEAD-01 · Phân công Lead tự động (Round-robin)

**Trigger**: `lead.created`

**Luồng n8n**:
```
Webhook (lead.created)
  │
  ├─► MinHan API GET /api/leads — Count leads per sales rep (workload)
  │
  ├─► Tìm sales rep ít lead nhất (round-robin by workload)
  │
  ├─► MinHan API PATCH /leads/{id}/assign
  │     { assigned_to: {user_id} }
  │
  ├─► Slack DM → sales rep:
  │     "🎯 Lead mới được giao cho bạn:
  │      Công ty: {contact_company} | Liên hệ: {contact_name}
  │      Giá trị dự kiến: {expected_value}
  │      👉 Xem chi tiết: {link}"
  │
  └─► IF lead có assessment_result (từ Survey):
        Thêm vào message: "Assessment Score: {score} | Profile: {persona}"
```

---

### WF-LEAD-02 · Follow-up Lead không hoạt động

**Trigger**: Cron — hàng ngày 09:00

**Luồng n8n**:
```
Cron (daily 09:00)
  │
  ├─► MinHan API GET /api/leads
  │     filter: status=active, updated_at < now-3d (không có activity 3 ngày)
  │
  └─► ForEach stale lead:
        ├─► Slack DM → assigned sales rep:
        │     "⏰ Lead '{company}' chưa có hoạt động {N} ngày — Cần follow-up"
        │
        └─► IF > 7 ngày không hoạt động:
              Slack → Sales Manager:
              "⚠️ Lead {company} ({assigned_to}) không hoạt động 7+ ngày"
```

---

### WF-LEAD-03 · Tự động chạy Assessment cho Lead mới

**Trigger**: `lead.created` (có contact_email)

**Luồng n8n**:
```
Webhook (lead.created)
  │
  ├─► IF survey_response_id IS NULL (chưa làm assessment):
  │
  ├─► MinHan API GET /api/surveys — Lấy survey phù hợp (e.g. "Lead Discovery")
  │
  ├─► Tạo SurveyToken cho lead
  │     MinHan API POST /surveys/{survey_id}/tokens
  │     { ref_type: 'lead', ref_id: lead.uuid }
  │
  └─► Email → contact_email:
        "Để hiểu nhu cầu của bạn tốt hơn, mời bạn hoàn thành khảo sát ngắn (5 phút):
         {survey_link}
         Chúng tôi sẽ phân tích và phản hồi trong vòng 24h"
```

---

### WF-LEAD-04 · Thông báo Lead converted → Tạo Employee/Project

**Trigger**: `lead.status_changed` (→ Converted)

**Luồng n8n**:
```
Webhook (lead.status = converted)
  │
  ├─► Slack → Sales Manager + CEO:
  │     "🎉 LEAD ĐÃ CHUYỂN ĐỔI THÀNH CÔNG!
  │      Khách hàng: {company} | Giá trị: {actual_value}
  │      Sales: {assigned_to}"
  │
  ├─► CRM/Billing: Tạo customer account (nếu tích hợp)
  │
  └─► MinHan API POST /projects (nếu lead → project)
        Tự tạo project với thông tin cơ bản từ lead
```

---

### WF-LEAD-05 · Báo cáo Sales Pipeline hàng tuần

**Trigger**: Cron — Thứ Sáu 17:00

**Luồng n8n**:
```
Cron (Friday 17:00)
  │
  ├─► MinHan API GET /api/leads — grouped by stage, assigned_to
  │
  ├─► Tính metrics:
  │     - Total pipeline value
  │     - Win rate tuần
  │     - Avg deal size
  │     - Lead velocity
  │
  └─► Email → Sales Manager + CEO:
        "📊 Pipeline Report — Tuần {W}
         Tổng giá trị pipeline: {value}
         Converted tuần này: {count} ({value})
         Top performer: {sales_rep}"
```

---

## 11. Survey & Assessment

### WF-SV-01 · Gửi Survey sau khi Phỏng vấn hoàn thành

**Trigger**: `recruitment.interview.status_changed` (→ completed)

**Luồng n8n**:
```
Webhook (interview.completed)
  │
  ├─► MinHan API GET — Interview Feedback Survey
  │     (survey với assessment_code = 'interview_feedback')
  │
  ├─► Tạo token cho candidate
  │
  ├─► Email → candidate (2 giờ sau phỏng vấn):
  │     "Cảm ơn bạn đã tham gia phỏng vấn!
  │      Chia sẻ trải nghiệm của bạn (2 phút):
  │      {survey_link}"
  │
  └─► Email → interviewer (ngay lập tức nếu chưa submit evaluation):
        "Vui lòng hoàn thành đánh giá ứng viên: {evaluation_link}"
```

---

### WF-SV-02 · Xử lý kết quả Assessment

**Trigger**: `survey.assessment.scored`

**Luồng n8n**:
```
Webhook (assessment.scored)
  │
  ├─► Fetch AssessmentResult đầy đủ (scores, recommendations, persona)
  │
  ├─► IF subject_type = 'lead':
  │     ├─► MinHan API PATCH /leads/{id}
  │     │     { lead_score: overall_score }
  │     └─► Slack → assigned sales:
  │           "📊 Assessment kết quả: {contact_name}
  │            Score: {score}/100 | Persona: {persona}
  │            Điểm mạnh: {strengths}
  │            Pain points: {pain_points}"
  │
  ├─► IF subject_type = 'employee':
  │     └─► Notify HR + Manager về kết quả assessment nhân viên
  │
  └─► IF score >= threshold:
        MinHan API POST — Tự động chuyển lead sang stage phù hợp
```

---

### WF-SV-03 · Nhắc hoàn thành Survey chưa submit

**Trigger**: Cron — hàng ngày 10:00

**Luồng n8n**:
```
Cron (daily 10:00)
  │
  ├─► MinHan API GET — Survey tokens: created_at < now-24h, used=false, revoked=false
  │
  └─► ForEach token chưa dùng:
        Email → token owner:
        "Bạn chưa hoàn thành khảo sát '{survey_title}' (mất khoảng {est_minutes} phút)
         {survey_link}
         (Link hết hạn sau {expire_date})"
```

---

## 12. Dự án & Công việc

### WF-PRJ-01 · Thông báo tạo dự án mới & phân công team

**Trigger**: `project.created`

**Luồng n8n**:
```
Webhook (project.created)
  │
  ├─► Slack → #projects:
  │     "🚀 Dự án mới: '{name}' | PM: {manager} | Deadline: {deadline}"
  │
  ├─► Email → tất cả thành viên được assign:
  │     "Bạn được thêm vào dự án '{name}'
  │      Vai trò: {role} | Bắt đầu: {start_date}
  │      👉 Xem dự án: {link}"
  │
  └─► Google Drive: Tạo thư mục dự án + share với team
```

---

### WF-PRJ-02 · Cảnh báo Deadline dự án

**Trigger**: Cron — hàng ngày 08:00

```
Cron → Check projects deadline → N=14/7/3/1 ngày
Notify: Slack → PM + team
Escalate: N=3 → CC Manager
```

---

## 13. Tự động hóa nội bộ (WorkflowAutomation bridge)

> **Module WorkflowAutomation** hiện có cấu trúc định nghĩa workflow (trigger + conditions + steps) nhưng chưa có execution engine mạnh. n8n là execution layer lý tưởng.

### Kiến trúc bridge

```
WorkflowAutomation (define & store rules)
        │
        │  Khi trigger_type match + conditions pass
        ▼
Laravel → POST webhook → n8n
        │
        │  n8n execute: gửi email / Slack / gọi API
        ▼
MinHan API ← Ghi WorkflowExecution (status, log)
```

### WF-WFA-01 · Execute workflow từ WorkflowAutomation qua n8n

**Luồng n8n** (generic execution workflow):
```
Webhook (workflow.triggered)
  │  payload: { workflow_id, trigger_type, subject_type, subject_id, context }
  │
  ├─► Fetch workflow definition từ MinHan API
  │     GET /api/workflows/{id}
  │
  ├─► Evaluate conditions (re-check trên n8n)
  │
  ├─► IF pass:
  │     ForEach step trong workflow.steps:
  │       ├─► step.type = 'send_email'     → Email node
  │       ├─► step.type = 'send_slack'     → Slack node
  │       ├─► step.type = 'update_record'  → MinHan API PATCH
  │       ├─► step.type = 'create_task'    → Task API
  │       └─► step.type = 'call_webhook'   → HTTP Request node
  │
  └─► MinHan API POST /api/workflows/{id}/executions
        { status: 'pass', log: [...], executed_at: now }
```

**Lợi ích**: Admin có thể định nghĩa workflow mới trong UI mà không cần deploy code hay tạo n8n flow mới.

---

## 14. ActivityLog & Giám sát

### WF-LOG-01 · Cảnh báo hoạt động bất thường

**Trigger**: Cron — mỗi 15 phút

**Luồng n8n**:
```
Cron (*/15 * * * *)
  │
  ├─► MinHan API GET /api/activity-logs
  │     filter: level=critical, created_at >= last_15min
  │
  └─► ForEach critical log:
        Slack → #security-alerts:
        "🚨 CRITICAL: [{module}] {action}
         User: {user} | IP: {ip} | {timestamp}
         Mô tả: {description}"
```

---

### WF-LOG-02 · Báo cáo bảo mật hàng ngày

**Trigger**: Cron — 07:00 hàng ngày

**Luồng n8n**:
```
Cron (daily 07:00)
  │
  ├─► MinHan API GET /api/activity-logs/stats (hôm qua)
  │
  └─► Email → System Admin:
        "📊 Security Report — {date}
         ─────────────────────────
         Tổng actions: {total}
         Warning: {warn_count}
         Critical: {critical_count}
         Top users: [...]
         Top modules: [...]
         Failed logins: {count}"
```

---

### WF-LOG-03 · Alert Rule trigger

**Trigger**: Alert Rule trong ActivityLog module khi threshold vượt

**Luồng n8n**:
```
Webhook (alert_rule.triggered)
  │  payload: { rule_name, condition, actual_value, threshold }
  │
  └─► Slack → #ops-alerts + Email → System Admin:
        "⚠️ Alert Rule triggered: '{rule_name}'
         Điều kiện: {condition}
         Giá trị hiện tại: {actual_value} (ngưỡng: {threshold})"
```

---

## 15. Cross-module: Onboarding tích hợp

> Đây là workflow phức tạp nhất, kết hợp nhiều module: Recruitment → Employee → Leave → KPI → KC.

### WF-ONBOARD-01 · Full Onboarding Pipeline khi Offer Accepted

**Trigger**: `recruitment.offer.accepted`

**Luồng n8n** (orchestration workflow):
```
Webhook (offer.accepted)
  │
  STEP 1 — Tạo Employee record
  ├─► MinHan API POST /employees
  │     Dữ liệu từ: RcCandidate + RcOffer (vị trí, lương, ngày bắt đầu)
  │     Status: probation (nếu có probation_days > 0)
  │
  STEP 2 — Khởi tạo Leave Balance
  ├─► MinHan API — Tìm LeavePolicy phù hợp (theo job_title, department)
  ├─► MinHan API POST /leave/balances
  │     { employee_id, policies: [...], year: current_year }
  │
  STEP 3 — Tạo KPI Goals chu kỳ đầu
  ├─► MinHan API POST /kpi/goals
  │     Template goals từ template của role/department
  │     Cycle: từ ngày bắt đầu đến cuối quarter
  │
  STEP 4 — Assign SOP cần đọc (Onboarding SOPs)
  ├─► MinHan API GET /api/sop — filter: type=training, department_id
  ├─► Tạo reading assignment cho employee mới
  │
  STEP 5 — Gửi thông tin hệ thống
  ├─► Email → nhân viên mới:
  │     - Link đăng nhập hệ thống + temporary password
  │     - Danh sách SOPs cần đọc trong tuần đầu
  │     - Link KC onboarding articles
  │     - Lịch tuần đầu tiên
  │
  STEP 6 — Notify các bên liên quan
  ├─► Slack → HR: "✅ Onboarding tự động hoàn tất cho {name}"
  ├─► Slack → Manager: "👋 {name} bắt đầu {start_date} — Checklist manager: {link}"
  └─► Slack → IT: "🖥️ Setup thiết bị cho {name} trước {start_date}"
```

---

## 16. Bảng tổng hợp ưu tiên

| # | Workflow | Module | ROI | Độ phức tạp | Ưu tiên |
|---|----------|--------|-----|-------------|---------|
| 1 | WF-RC-02 · Tự động lên lịch phỏng vấn | Recruitment | ⭐⭐⭐⭐⭐ | Trung bình | **P0** |
| 2 | WF-LV-01 · Thông báo duyệt nghỉ phép | Leave | ⭐⭐⭐⭐⭐ | Thấp | **P0** |
| 3 | WF-RC-01 · AI CV Screening | Recruitment | ⭐⭐⭐⭐⭐ | Cao | **P0** |
| 4 | WF-HR-01 · Onboarding tự động | Employee | ⭐⭐⭐⭐⭐ | Trung bình | **P0** |
| 5 | WF-ONBOARD-01 · Full Onboarding Pipeline | Cross-module | ⭐⭐⭐⭐⭐ | Rất cao | **P1** |
| 6 | WF-LV-03 · Thông báo kết quả + Google Calendar | Leave | ⭐⭐⭐⭐ | Thấp | **P1** |
| 7 | WF-RC-05 · Offer Letter PDF + eSign | Recruitment | ⭐⭐⭐⭐ | Cao | **P1** |
| 8 | WF-KPI-01 · Báo cáo KPI tuần | KPI | ⭐⭐⭐⭐ | Thấp | **P1** |
| 9 | WF-HR-02 · Nhắc kết thúc thử việc | Employee | ⭐⭐⭐⭐ | Thấp | **P1** |
| 10 | WF-LEAD-01 · Phân công Lead round-robin | Lead | ⭐⭐⭐⭐ | Thấp | **P1** |
| 11 | WF-RC-08 · Báo cáo tuyển dụng tuần | Recruitment | ⭐⭐⭐⭐ | Thấp | **P2** |
| 12 | WF-JP-01 · Đăng tin đa kênh | JobPosting | ⭐⭐⭐⭐ | Trung bình | **P2** |
| 13 | WF-KC-02 · Cảnh báo KC hết hạn | KcItem | ⭐⭐⭐ | Thấp | **P2** |
| 14 | WF-SOP-01 · Quy trình duyệt SOP | SOP | ⭐⭐⭐ | Thấp | **P2** |
| 15 | WF-LEAD-03 · Auto Assessment cho Lead | Lead + Survey | ⭐⭐⭐⭐ | Trung bình | **P2** |
| 16 | WF-KPI-05 · Escalation review chưa xong | KPI | ⭐⭐⭐ | Thấp | **P2** |
| 17 | WF-LV-02 · Auto-approve 96h | Leave | ⭐⭐⭐ | Thấp | **P2** |
| 18 | WF-WFA-01 · WorkflowAutomation bridge | Cross-module | ⭐⭐⭐⭐⭐ | Rất cao | **P3** |
| 19 | WF-LOG-01 · Cảnh báo hoạt động bất thường | ActivityLog | ⭐⭐⭐⭐ | Thấp | **P3** |
| 20 | WF-HR-03 · Sinh nhật & kỷ niệm công tác | Employee | ⭐⭐⭐ | Rất thấp | **P3** |
| 21 | WF-SV-01 · Survey sau phỏng vấn | Survey | ⭐⭐⭐ | Thấp | **P3** |
| 22 | WF-MKT-01 · Duyệt tổ chức Marketplace | Marketplace | ⭐⭐⭐ | Thấp | **P3** |
| 23 | WF-HR-04 · Cảnh báo hợp đồng hết hạn | Employee | ⭐⭐⭐ | Thấp | **P3** |
| 24 | WF-HR-05 · Offboarding tự động | Employee | ⭐⭐⭐⭐ | Trung bình | **P3** |

---

## 17. Lộ trình triển khai

### Giai đoạn 1 — Foundation (Tuần 1–2)

**Mục tiêu**: Xây dựng nền tảng kỹ thuật, test kết nối.

```
☐ Cài đặt n8n (self-hosted hoặc n8n Cloud)
☐ Tạo N8nDispatcher service trong Laravel
☐ Tạo N8nCallbackController + auth middleware
☐ Đăng ký Observer đầu tiên: LeaveRequest
☐ Tạo config services.n8n
☐ Test end-to-end: Leave submit → n8n webhook → Slack notify
☐ Implement WF-LV-01 (Leave approval notify) — đơn giản nhất
☐ Implement WF-HR-03 (Birthday) — zero risk, giá trị visible ngay
```

### Giai đoạn 2 — Quick Wins (Tuần 3–4)

**Mục tiêu**: Triển khai các workflow giá trị cao, độ phức tạp thấp.

```
☐ WF-LV-03 · Leave result + Google Calendar
☐ WF-HR-02 · Probation end reminder
☐ WF-KPI-01 · Weekly KPI report
☐ WF-HR-04 · Contract expiry alert
☐ WF-JP-02 · Job post expiry alert
☐ WF-KC-02 · KC item expiry alert
☐ WF-LEAD-02 · Stale lead follow-up
☐ WF-LOG-02 · Daily security report
```

### Giai đoạn 3 — Core Automation (Tuần 5–8)

**Mục tiêu**: Tự động hóa quy trình trọng tâm Recruitment + HR.

```
☐ WF-RC-02 · Interview scheduler (Google Calendar integration)
☐ WF-HR-01 · Employee onboarding automation
☐ WF-RC-03 · Interview reminder + escalation
☐ WF-RC-04 · Interview result aggregation
☐ WF-LEAD-01 · Lead round-robin assignment
☐ WF-SOP-01 · SOP approval notification
☐ WF-KC-01 · KC approval workflow
☐ WF-LV-02 · Auto-approve leave 96h
```

### Giai đoạn 4 — Advanced (Tuần 9–16)

**Mục tiêu**: AI integration, multi-channel, complex orchestration.

```
☐ WF-RC-01 · AI CV Screening (OpenAI/Claude integration)
☐ WF-RC-05 · Offer PDF generation + DocuSign
☐ WF-JP-01 · Multi-channel job posting
☐ WF-LEAD-03 · Auto survey for leads
☐ WF-SV-02 · Assessment result processing
☐ WF-ONBOARD-01 · Full onboarding pipeline
☐ WF-HR-05 · Offboarding automation
☐ WF-RC-08 · Weekly recruitment report
```

### Giai đoạn 5 — Intelligence Bridge (Tuần 17+)

**Mục tiêu**: Kết nối WorkflowAutomation module với n8n.

```
☐ WF-WFA-01 · Generic workflow execution bridge
☐ UI cho admin định nghĩa workflow → n8n execution
☐ Dashboard tổng hợp n8n execution history
☐ AI-powered lead scoring từ assessment data
☐ Predictive alerts cho KPI at-risk
```

---

## 18. Hướng dẫn kỹ thuật triển khai

### 18.1 Cài đặt n8n Self-hosted

```bash
# Docker Compose
version: '3.8'
services:
  n8n:
    image: n8nio/n8n
    restart: always
    ports:
      - "5678:5678"
    environment:
      - N8N_BASIC_AUTH_ACTIVE=true
      - N8N_BASIC_AUTH_USER=admin
      - N8N_BASIC_AUTH_PASSWORD=your_password
      - WEBHOOK_URL=https://n8n.yourdomain.com
      - N8N_ENCRYPTION_KEY=your_encryption_key
    volumes:
      - n8n_data:/home/node/.n8n
```

### 18.2 Bảo mật Webhook

Verify chữ ký HMAC trong N8nCallbackController:

```php
private function verifySignature(Request $request): bool
{
    $signature = $request->header('X-N8N-Signature');
    $expected  = hash_hmac('sha256', $request->getContent(), config('services.n8n.secret'));
    return hash_equals($expected, $signature ?? '');
}
```

### 18.3 Queue Webhook Dispatch (tránh blocking request)

```php
// app/Jobs/DispatchN8nWebhook.php
class DispatchN8nWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue;

    public function __construct(
        private string $event,
        private array  $payload
    ) {}

    public function handle(): void
    {
        Http::withHeaders(['X-Api-Key' => config('services.n8n.api_key')])
            ->timeout(10)
            ->retry(3, 1000)
            ->post(config('services.n8n.webhook_base') . '/' . $this->event, $this->payload);
    }
}

// Trong Observer:
DispatchN8nWebhook::dispatch('leave.request.submitted', $payload);
```

### 18.4 Ghi log execution vào ActivityLog

Mỗi n8n callback nên ghi vào ActivityLog để traceable:

```php
activity()
    ->causedByAnonymous()
    ->withProperties(['n8n_workflow' => $action, 'ref_id' => $refId])
    ->log("n8n automation: {$action}");
```

### 18.5 Template payload chuẩn gửi từ Laravel → n8n

```json
{
  "event": "leave.request.submitted",
  "org_id": "uuid-of-organization",
  "timestamp": "2026-06-06T08:00:00+07:00",
  "payload": {
    "id": "uuid",
    "employee": {
      "id": "uuid",
      "full_name": "Nguyễn Văn A",
      "email": "a@company.com",
      "department": "Kỹ thuật",
      "manager_email": "manager@company.com"
    },
    "leave_type": "annual",
    "date_from": "2026-06-10",
    "date_to": "2026-06-12",
    "days_count": 3,
    "reason": "Nghỉ du lịch gia đình",
    "links": {
      "approve": "https://app.domain.com/dashboard/leave/requests/uuid/approve?token=...",
      "reject":  "https://app.domain.com/dashboard/leave/requests/uuid/reject?token=...",
      "view":    "https://app.domain.com/dashboard/leave/requests/uuid"
    }
  }
}
```

### 18.6 Approve/Reject link không cần đăng nhập

Để manager có thể approve trực tiếp từ email/Slack mà không cần login:

```php
// Thêm signed URL vào payload
'links' => [
    'approve' => URL::signedRoute('backend.leave.requests.approve', ['request' => $leaveRequest->id]),
    'reject'  => URL::signedRoute('backend.leave.requests.reject',  ['request' => $leaveRequest->id]),
]
```

---

## Phụ lục — Các tích hợp dịch vụ bên ngoài gợi ý

| Dịch vụ | Dùng cho Workflow | n8n Node |
|---------|-------------------|----------|
| **Slack** | Tất cả notifications | Slack node |
| **Email (SMTP/SendGrid)** | Tất cả email | Email/SendGrid node |
| **Google Calendar** | Interview scheduling, Leave calendar | Google Calendar node |
| **Google Drive** | Onboarding docs, Project folders | Google Drive node |
| **OpenAI / Claude API** | CV screening, Summary | HTTP Request node |
| **LinkedIn Jobs API** | Multi-channel job posting | HTTP Request node |
| **DocuSign / eSign** | Offer letter signing | HTTP Request node |
| **Zalo OA API** | Notify qua Zalo (thị trường VN) | HTTP Request node |
| **Jira / Linear** | IT onboarding/offboarding tickets | Jira node |
| **Telegram Bot** | Alert khẩn cấp (ops/security) | Telegram node |
| **Puppeteer (PDF)** | Render offer letter PDF | HTTP Request → service |
| **MST API (Việt Nam)** | Verify tax code tổ chức Marketplace | HTTP Request node |

---

*Tài liệu được tổng hợp dựa trên cấu trúc thực tế của hệ thống MinHan — cập nhật lần cuối: 2026-06-06.*
