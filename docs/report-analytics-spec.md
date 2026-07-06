# Đặc tả Module Report / Analytics
## Báo cáo tổng hợp Cross-Module: HR · Sales/CRM · Project KPI

> **Phiên bản**: 1.0 | **Ngày**: 2026-06-13
> **Trạng thái**: Specification — chưa triển khai
> **Scope**: Per-Organization (orgId), RBAC-gated, read-only aggregation

---

## 1. Tổng quan hệ thống

### 1.1 Mục tiêu

Module Report cung cấp **dashboard tổng hợp và báo cáo cross-module** cho phép:

- CEO / Admin xem toàn bộ chỉ số của tổ chức
- HR xem số liệu nhân sự, tuyển dụng, nghỉ phép, đánh giá hiệu suất
- Sales xem pipeline, conversion rate, doanh thu kỳ vọng
- Ops / PM xem tiến độ project, KPI cycle

Tất cả dữ liệu được **scoped theo `organization_id`**, không có cross-org data leak.

### 1.2 Nguyên tắc thiết kế

| Nguyên tắc | Triển khai |
|---|---|
| **Read-only** | Module chỉ đọc DB — không write, không trigger side effect |
| **Per-orgId strict** | Mọi query phải kèm `WHERE organization_id = ?` từ `TenantContext` |
| **RBAC-gated** | Từng section kiểm tra permission trước khi trả dữ liệu |
| **Idempotent API** | GET endpoints, kết quả cache-able theo `orgId + filters` |
| **No raw SQL** | Dùng Eloquent Query Builder; aggregate qua scope chứ không raw |
| **Snap fields ưu tiên** | Dùng `snap_dept_name`, `snap_job_title` trên bản ghi lịch sử thay vì JOIN realtime |

### 1.3 Vị trí trong hệ thống

```
Modules/
└── Report/
    ├── app/
    │   ├── Http/Controllers/Backend/
    │   │   ├── ReportDashboardController.php     ← Trang chủ Report
    │   │   ├── HrReportController.php
    │   │   ├── SalesReportController.php
    │   │   └── ProjectKpiReportController.php
    │   ├── Queries/                               ← CQRS-lite read-only queries
    │   │   ├── Hr/
    │   │   ├── Sales/
    │   │   └── ProjectKpi/
    │   └── Http/Middleware/
    │       └── AuthorizeReport.php
    ├── resources/
    │   ├── views/
    │   └── assets/js/pages/
    └── routes/
        ├── web.php
        └── api.php
```

---

## 2. Phân quyền truy cập (RBAC)

### 2.1 Permission mapping

Dựa theo `PermissionEnum` và `RoleEnum` hiện có:

| Permission | Role mặc định | Scope dữ liệu |
|---|---|---|
| `REPORTS_FULL` | CEO, System Admin | Toàn bộ org — tất cả section |
| `REPORTS_HR` | HR | Section HR: headcount, leave, recruitment, performance |
| `REPORTS_TEAM` | Sales | Section Sales: pipeline của team được phân công |
| `REPORTS_PERSONAL` | Sales | Section Sales: pipeline cá nhân (assigned_to = self) |
| `REPORTS_OPS` | Ops | Section Project & KPI: toàn bộ org |
| `REPORTS_MARKETING` | Marketing | Section Sales: leads từ marketing channels |
| `REPORTS_AI_USAGE` | AI Operator | Section Assessment & AI: kết quả khảo sát, scoring |
| `REPORTS_SHARED` | Tất cả roles | Dashboard tóm tắt cá nhân (my tasks, my leaves, my KPI) |

### 2.2 Logic kiểm tra trong controller

```php
// AuthorizeReport middleware + per-section gate
Gate::authorize('viewReportSection', ['hr']);          // kiểm tra REPORTS_HR hoặc REPORTS_FULL
Gate::authorize('viewReportSection', ['sales']);       // REPORTS_TEAM | REPORTS_PERSONAL | REPORTS_FULL
Gate::authorize('viewReportSection', ['project_kpi']); // REPORTS_OPS | REPORTS_FULL
```

### 2.3 Data scope theo role

```
CEO / Admin         → WHERE organization_id = {orgId}   (tất cả)
HR                  → WHERE organization_id = {orgId}   (chỉ HR sections)
Sales (TEAM)        → WHERE organization_id = {orgId}   (team của manager)
Sales (PERSONAL)    → WHERE assigned_to = {userId}      (chỉ của mình)
Ops                 → WHERE organization_id = {orgId}   (project + KPI)
Marketing           → WHERE source.channel IN (marketing channels)
```

---

## 3. Cấu trúc tổng thể Dashboard

### 3.1 URL Map

```
/report                          ← Dashboard tổng quan (REPORTS_FULL | REPORTS_SHARED)
/report/hr                       ← Báo cáo HR (REPORTS_HR | REPORTS_FULL)
/report/hr/headcount             ← Biến động nhân sự
/report/hr/leave                 ← Nghỉ phép
/report/hr/recruitment           ← Tuyển dụng
/report/hr/performance           ← Đánh giá hiệu suất
/report/sales                    ← Báo cáo Sales (REPORTS_TEAM | REPORTS_FULL)
/report/sales/pipeline           ← Pipeline & Funnel
/report/sales/conversion         ← Conversion rate
/report/sales/revenue            ← Doanh thu kỳ vọng
/report/sales/activity           ← Hoạt động Sales
/report/project                  ← Báo cáo Project (REPORTS_OPS | REPORTS_FULL)
/report/project/overview         ← Tổng quan project
/report/project/tasks            ← Tiến độ task
/report/project/timelog          ← Time tracking
/report/kpi                      ← Báo cáo KPI (REPORTS_OPS | REPORTS_FULL)
/report/kpi/cycle                ← Theo cycle
/report/kpi/employee             ← Theo nhân viên
/report/kpi/snapshot             ← Lịch sử snapshot
```

### 3.2 Bộ lọc chung (global filters)

Tất cả sections hỗ trợ filter:

| Filter | Type | Nguồn |
|---|---|---|
| `date_from` / `date_to` | daterange | Input |
| `branch_id` | TomSelect remote | `GET /api/v1/branches/options` |
| `department_id` | TomSelect remote | `GET /api/v1/departments/options` |
| `granularity` | select | `day / week / month / quarter / year` |

Filter state lưu trong URL query string để share-able.

---

## 4. Section HR — Báo cáo Nhân sự

### 4.1 Nguồn dữ liệu

| Bảng | Model | Mục đích |
|---|---|---|
| `employees` | `Employee` | Headcount, turnover, demographics |
| `employee_history` | `EmployeeHistory` | Biến động lịch sử (transfer, promotion, resign) |
| `employee_departments` | `EmployeeDepartment` | Multi-dept membership |
| `departments` | `Department` | Group by dept tree |
| `branches` | `Branch` | Group by branch |
| `job_titles` | `JobTitle` | Group by level/category |
| `leave_requests` | `LeaveRequest` | Số ngày nghỉ theo loại |
| `leave_balances` | `LeaveBalance` | Số dư nghỉ phép |
| `leave_policies` | `LeavePolicy` | Quy định nghỉ |
| `performance_reviews` | `PerformanceReview` | Điểm đánh giá |
| `review_scores` | `ReviewScore` | Chi tiết điểm từng tiêu chí |
| `rc_candidates` | `RcCandidate` | Pipeline tuyển dụng |
| `rc_applications` | `RcApplication` | Đơn ứng tuyển |
| `rc_offers` | `RcOffer` | Offer được gửi |
| `jp_job_posts` | `JpJobPost` | JD đã đăng |

### 4.2 Báo cáo Headcount — Biến động nhân sự

**Endpoint**: `GET /api/v1/report/hr/headcount`

**Query parameters**: `date_from`, `date_to`, `branch_id`, `department_id`, `granularity`

**Response structure**:
```json
{
  "summary": {
    "total_active": 120,
    "total_probation": 8,
    "total_on_leave": 3,
    "total_resigned": 5,
    "net_change": 2
  },
  "by_status": [
    { "status": "active", "count": 120 },
    { "status": "probation", "count": 8 }
  ],
  "by_department": [
    { "department_id": 1, "name": "Sales", "count": 32, "headcount_limit": 40 }
  ],
  "by_branch": [
    { "branch_id": 1, "name": "Hà Nội", "count": 75 }
  ],
  "by_job_level": [
    { "level": 1, "label": "Junior", "count": 45 }
  ],
  "by_employment_type": [
    { "type": "full_time", "count": 105 },
    { "type": "part_time", "count": 15 }
  ],
  "trend": [
    { "period": "2026-05", "hired": 3, "resigned": 1, "net": 2, "closing_count": 120 }
  ],
  "new_hires": [
    { "employee_id": 1, "full_name": "Nguyễn Văn A", "department": "Sales", "hired_at": "2026-06-01" }
  ],
  "resigned": [
    { "employee_id": 2, "full_name": "Trần Thị B", "department": "IT", "left_at": "2026-06-05" }
  ]
}
```

**Query logic**:
```php
// Headcount tại thời điểm hiện tại
Employee::whereIn('status', ['active', 'probation', 'on_leave'])
    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
    ->when($deptId,   fn($q) => $q->where('department_id', $deptId))
    ->selectRaw('status, COUNT(*) as count')
    ->groupBy('status')
    ->get();

// Biến động theo thời gian — từ employee_history
EmployeeHistory::whereBetween('effective_date', [$from, $to])
    ->whereIn('change_type', ['hired', 'resigned', 'terminated'])
    ->selectRaw('DATE_FORMAT(effective_date, "%Y-%m") as period, change_type, COUNT(*) as count')
    ->groupBy('period', 'change_type')
    ->get();
```

**Chú ý multi-tenant**:
- `Employee` dùng `TenantAwareModel` → `organization_id` auto-scope
- `EmployeeHistory` khai báo `organization_id` thủ công → thêm `where('organization_id', TenantContext::getOrganizationId())`

---

### 4.3 Báo cáo Leave — Nghỉ phép

**Endpoint**: `GET /api/v1/report/hr/leave`

**Query parameters**: `year`, `leave_type`, `branch_id`, `department_id`

**Response structure**:
```json
{
  "summary": {
    "total_requests": 45,
    "total_days_taken": 128.5,
    "pending_requests": 3,
    "avg_days_per_employee": 3.2
  },
  "by_type": [
    { "leave_type": "annual", "requests": 30, "days": 90.0 },
    { "leave_type": "sick",   "requests": 12, "days": 30.5 },
    { "leave_type": "unpaid", "requests": 3,  "days": 8.0  }
  ],
  "by_department": [
    { "department_id": 1, "name": "Sales", "requests": 12, "days": 38.0 }
  ],
  "by_status": [
    { "status": "approved", "count": 40 },
    { "status": "pending",  "count": 3 },
    { "status": "rejected", "count": 2 }
  ],
  "top_requesters": [
    { "employee_id": 5, "full_name": "Lê Văn C", "total_days": 12.0 }
  ],
  "monthly_trend": [
    { "month": "2026-01", "days_taken": 22.5 }
  ],
  "balance_summary": [
    {
      "employee_id": 1, "full_name": "Nguyễn Văn A",
      "leave_type": "annual", "entitled": 12, "used": 3, "remaining": 9
    }
  ]
}
```

**Query logic**:
```php
// Tổng hợp leave requests
LeaveRequest::join('employees', 'employees.id', '=', 'leave_requests.employee_id')
    ->where('employees.organization_id', $orgId)
    ->whereYear('date_from', $year)
    ->when($leaveType, fn($q) => $q->where('leave_type', $leaveType))
    ->selectRaw('leave_type, status, COUNT(*) as requests, SUM(days_count) as days')
    ->groupBy('leave_type', 'status')
    ->get();

// Số dư theo nhân viên — từ leave_balances
LeaveBalance::join('employees', 'employees.id', '=', 'leave_balances.employee_id')
    ->where('employees.organization_id', $orgId)
    ->where('year', $year)
    ->selectRaw('employee_id, leave_type, entitled_days, used_days, 
                 (entitled_days + carried_over + adjusted - used_days - pending_days) as remaining')
    ->get();
```

---

### 4.4 Báo cáo Recruitment — Tuyển dụng

**Endpoint**: `GET /api/v1/report/hr/recruitment`

**Query parameters**: `date_from`, `date_to`, `department_id`, `status`

**Response structure**:
```json
{
  "summary": {
    "open_positions": 5,
    "total_applications": 120,
    "shortlisted": 30,
    "interviewed": 18,
    "offered": 8,
    "hired": 5,
    "rejected": 72,
    "avg_days_to_hire": 21.3
  },
  "funnel": [
    { "stage_code": "applied",     "label": "Applied",     "count": 120, "conversion_pct": 100 },
    { "stage_code": "screened",    "label": "Sàng lọc",    "count": 80,  "conversion_pct": 66.7 },
    { "stage_code": "interviewed", "label": "Phỏng vấn",  "count": 18,  "conversion_pct": 22.5 },
    { "stage_code": "offered",     "label": "Đã offer",   "count": 8,   "conversion_pct": 10.0 },
    { "stage_code": "hired",       "label": "Đã nhận",    "count": 5,   "conversion_pct": 6.3  }
  ],
  "by_source": [
    { "source": "website", "applications": 40, "hires": 2 },
    { "source": "referral", "applications": 30, "hires": 2 }
  ],
  "by_department": [
    { "department": "Engineering", "open_positions": 2, "applications": 55, "hires": 2 }
  ],
  "open_jobs": [
    {
      "job_post_id": 1, "title": "Senior Developer", "department": "Engineering",
      "posted_at": "2026-05-01", "applications": 55, "days_open": 42
    }
  ],
  "offer_acceptance_rate": 62.5,
  "monthly_applications": [
    { "month": "2026-05", "applications": 45, "hires": 2 }
  ]
}
```

**Chú ý**: `RcCandidate` và `RcApplication` dùng `org_id` (không phải `organization_id`).

```php
// Funnel — đếm theo pipeline stage
RcApplication::where('org_id', $orgId)
    ->whereBetween('applied_at', [$from, $to])
    ->join('rc_pipeline_stages', 'rc_pipeline_stages.id', '=', 'rc_applications.current_stage_id')
    ->selectRaw('rc_pipeline_stages.code as stage_code, rc_pipeline_stages.name as label, COUNT(*) as count')
    ->groupBy('stage_code', 'label')
    ->orderBy('rc_pipeline_stages.sort_order')
    ->get();

// Số ngày tuyển dụng trung bình (applied_at → offer sent_at)
RcOffer::join('rc_applications', 'rc_applications.id', '=', 'rc_offers.application_id')
    ->where('rc_applications.org_id', $orgId)
    ->whereNotNull('rc_offers.sent_at')
    ->selectRaw('AVG(DATEDIFF(rc_offers.sent_at, rc_applications.applied_at)) as avg_days')
    ->first();
```

---

### 4.5 Báo cáo Performance Review — Đánh giá hiệu suất

**Endpoint**: `GET /api/v1/report/hr/performance`

**Query parameters**: `period`, `department_id`, `branch_id`

**Response structure**:
```json
{
  "summary": {
    "total_reviews": 85,
    "completed": 72,
    "pending": 10,
    "draft": 3,
    "avg_overall_score": 3.8,
    "completion_rate_pct": 84.7
  },
  "score_distribution": [
    { "rating": "excellent",      "count": 15, "pct": 20.8 },
    { "rating": "above_expected", "count": 30, "pct": 41.7 },
    { "rating": "meets",          "count": 22, "pct": 30.6 },
    { "rating": "below",          "count": 5,  "pct": 6.9  }
  ],
  "by_department": [
    { "department": "Sales", "avg_score": 4.1, "completed": 20, "total": 22 }
  ],
  "criteria_breakdown": [
    { "criteria_key": "kpi_achievement", "label": "Đạt KPI", "avg_score": 3.9, "avg_weight": 40 },
    { "criteria_key": "teamwork",        "label": "Teamwork",  "avg_score": 4.2, "avg_weight": 20 }
  ],
  "top_performers": [
    { "employee_id": 3, "full_name": "Nguyễn A", "department": "Sales", "overall_score": 4.8 }
  ],
  "low_performers": [
    { "employee_id": 7, "full_name": "Trần B", "department": "Ops", "overall_score": 2.1 }
  ],
  "period_comparison": [
    { "period": "Q1-2026", "avg_score": 3.6 },
    { "period": "Q2-2026", "avg_score": 3.8 }
  ]
}
```

```php
// Phân phối điểm — dùng snap fields tránh JOIN
PerformanceReview::where('status', 'completed')
    ->when($period,   fn($q) => $q->where('period', $period))
    ->when($deptId,   fn($q) => $q->whereRaw("JSON_EXTRACT(snap_dept_name, '$') = ?", [$deptId]))
    ->selectRaw('overall_rating, COUNT(*) as count')
    ->groupBy('overall_rating')
    ->get();

// Chi tiết tiêu chí — JOIN review_scores
ReviewScore::join('performance_reviews', 'performance_reviews.id', '=', 'review_scores.review_id')
    ->where('performance_reviews.status', 'completed')
    ->selectRaw('criteria_key, criteria_name, AVG(score) as avg_score, AVG(weight) as avg_weight')
    ->groupBy('criteria_key', 'criteria_name')
    ->orderByDesc('avg_weight')
    ->get();
```

---

## 5. Section Sales/CRM — Báo cáo Kinh doanh

### 5.1 Nguồn dữ liệu

| Bảng | Model | Mục đích |
|---|---|---|
| `leads` | `Lead` | Pipeline, conversion, giá trị kỳ vọng |
| `lead_pipeline_stages` | `LeadPipelineStage` | Stage funnel |
| `lead_sources` | `LeadSource` | Channel attribution |
| `lead_activities` | `LeadActivity` | Hoạt động Sales |
| `lead_stage_history` | `LeadStageHistory` | Thời gian ở từng stage |
| `customers` | `Customer` | Khách hàng chuyển đổi |
| `assessment_results` | `AssessmentResult` | Lead scoring từ TDWCF |

### 5.2 Báo cáo Pipeline & Funnel

**Endpoint**: `GET /api/v1/report/sales/pipeline`

**Query parameters**: `date_from`, `date_to`, `assigned_to`, `source_id`, `granularity`

**Response structure**:
```json
{
  "summary": {
    "total_leads": 350,
    "total_expected_value": 4500000000,
    "currency": "VND",
    "avg_lead_score": 62.4,
    "hot_leads_count": 45,
    "stale_leads_count": 28
  },
  "funnel": [
    {
      "stage_id": 1, "stage_code": "new", "label": "Mới",
      "count": 120, "value": 1200000000,
      "avg_days_in_stage": 3.2,
      "conversion_to_next_pct": 65.0
    },
    {
      "stage_id": 2, "stage_code": "contacted", "label": "Đã liên hệ",
      "count": 78, "value": 900000000,
      "avg_days_in_stage": 5.1,
      "conversion_to_next_pct": 48.7
    }
  ],
  "by_source": [
    {
      "source_id": 1, "source_code": "website", "label": "Website",
      "count": 85, "value": 1100000000, "win_rate_pct": 12.4
    }
  ],
  "by_assignee": [
    {
      "user_id": 3, "name": "Nguyễn Sales", "total": 45,
      "won": 8, "lost": 5, "win_rate_pct": 24.2, "pipeline_value": 800000000
    }
  ],
  "win_loss_summary": {
    "won": 42, "won_value": 520000000,
    "lost": 35, "lost_value": 280000000,
    "overall_win_rate_pct": 54.5
  },
  "trend": [
    { "period": "2026-05", "new_leads": 65, "won": 8, "lost": 6, "value_won": 95000000 }
  ]
}
```

**Query logic**:
```php
// Funnel — join với stage để lấy sort_order
Lead::active()
    ->join('lead_pipeline_stages', 'lead_pipeline_stages.id', '=', 'leads.stage_id')
    ->whereBetween('leads.created_at', [$from, $to])
    ->when($assignedTo, fn($q) => $q->where('leads.assigned_to', $assignedTo))
    ->selectRaw('
        leads.stage_id,
        lead_pipeline_stages.code as stage_code,
        lead_pipeline_stages.label,
        lead_pipeline_stages.sort_order,
        COUNT(leads.id) as count,
        SUM(leads.expected_value) as value
    ')
    ->groupBy('leads.stage_id', 'stage_code', 'label', 'sort_order')
    ->orderBy('sort_order')
    ->get();

// Thời gian trung bình trong stage — từ lead_stage_history
LeadStageHistory::join('leads', 'leads.id', '=', 'lead_stage_history.lead_id')
    ->where('leads.organization_id', $orgId)
    ->whereNotNull('lead_stage_history.exited_at')
    ->selectRaw('
        lead_stage_history.stage_id,
        AVG(TIMESTAMPDIFF(HOUR, lead_stage_history.entered_at, lead_stage_history.exited_at) / 24) as avg_days
    ')
    ->groupBy('lead_stage_history.stage_id')
    ->get();
```

---

### 5.3 Báo cáo Conversion Rate

**Endpoint**: `GET /api/v1/report/sales/conversion`

**Response structure**:
```json
{
  "overall": {
    "total_leads": 350,
    "converted_to_customer": 42,
    "conversion_rate_pct": 12.0,
    "avg_days_to_convert": 18.4
  },
  "by_source": [
    {
      "source_code": "referral", "label": "Giới thiệu",
      "leads": 80, "converted": 18, "rate_pct": 22.5
    }
  ],
  "by_lead_score_band": [
    { "band": "hot (80-100)",  "leads": 45, "converted": 20, "rate_pct": 44.4 },
    { "band": "warm (50-79)",  "leads": 120, "converted": 18, "rate_pct": 15.0 },
    { "band": "cold (0-49)",   "leads": 185, "converted": 4,  "rate_pct": 2.2  }
  ],
  "monthly_cohort": [
    { "cohort_month": "2026-03", "leads_created": 65, "converted_within_30d": 8, "rate_30d_pct": 12.3 }
  ]
}
```

```php
// Conversion — leads đã thành customer
Lead::whereNotNull('customer_id')
    ->whereBetween('created_at', [$from, $to])
    ->join('lead_sources', 'lead_sources.id', '=', 'leads.source_id')
    ->selectRaw('
        leads.source_id,
        lead_sources.code as source_code,
        lead_sources.label,
        COUNT(leads.id) as converted,
        AVG(DATEDIFF(leads.actual_close_date, leads.created_at)) as avg_days
    ')
    ->groupBy('leads.source_id', 'source_code', 'label')
    ->get();
```

---

### 5.4 Báo cáo Sales Activity

**Endpoint**: `GET /api/v1/report/sales/activity`

**Response structure**:
```json
{
  "summary": {
    "total_activities": 520,
    "calls": 180,
    "emails": 210,
    "meetings": 85,
    "demos": 45
  },
  "by_assignee": [
    {
      "user_id": 3, "name": "Nguyễn Sales",
      "activities": 85, "calls": 30, "emails": 40, "meetings": 15
    }
  ],
  "by_day": [
    { "date": "2026-06-10", "count": 28 }
  ],
  "lead_response_time": {
    "avg_first_response_hours": 4.2,
    "pct_responded_within_1h": 35.0,
    "pct_responded_within_24h": 78.0
  }
}
```

---

## 6. Section Project & KPI

### 6.1 Nguồn dữ liệu

| Bảng | Model | Mục đích |
|---|---|---|
| `projects` | `Project` | Trạng thái, tiến độ, ngân sách |
| `project_members` | `ProjectMember` | Thành viên, utilization |
| `tasks` | `Task` | Tiến độ task, story points |
| `time_logs` | `TimeLog` | Giờ thực tế vs estimate |
| `kpi_goals` | `KpiGoal` | Mục tiêu KPI theo cycle |
| `kpi_snapshots` | `KpiSnapshot` | Kết quả cuối cycle (immutable) |
| `employees` | `Employee` | Link task/KPI → nhân viên |

### 6.2 Báo cáo Project Overview

**Endpoint**: `GET /api/v1/report/project/overview`

**Query parameters**: `date_from`, `date_to`, `branch_id`, `department_id`, `status`

**Response structure**:
```json
{
  "summary": {
    "total_projects": 18,
    "active": 10,
    "completed": 6,
    "on_hold": 1,
    "cancelled": 1,
    "on_time_pct": 70.0,
    "total_budget": 500000000,
    "currency": "VND"
  },
  "by_status": [
    { "status": "active", "count": 10, "budget": 300000000 }
  ],
  "by_priority": [
    { "priority": "high", "count": 5 }
  ],
  "by_department": [
    { "department": "Engineering", "count": 8, "active": 5, "completed": 3 }
  ],
  "projects_at_risk": [
    {
      "project_id": 3, "name": "CRM Phase 2", "status": "active",
      "end_date": "2026-06-30", "days_overdue": 0, "completion_pct": 45,
      "tasks_total": 80, "tasks_done": 36, "is_behind_schedule": true
    }
  ],
  "completion_rate": [
    { "period": "2026-Q2", "completed": 3, "total_ended": 4, "rate_pct": 75.0 }
  ]
}
```

**Query logic**:
```php
// Task completion theo project
Task::where('tasks.is_archived', false)
    ->join('projects', 'projects.id', '=', 'tasks.project_id')
    ->where('projects.organization_id', $orgId)
    ->where('tasks.is_leaf', true)  // chỉ đếm leaf tasks
    ->selectRaw('
        tasks.project_id,
        COUNT(*) as tasks_total,
        SUM(tasks.status = "done") as tasks_done,
        ROUND(SUM(tasks.status = "done") * 100.0 / COUNT(*), 1) as completion_pct,
        SUM(tasks.estimated_hours) as estimated_hours,
        SUM(tasks.logged_hours) as logged_hours
    ')
    ->groupBy('tasks.project_id')
    ->get();
```

---

### 6.3 Báo cáo Task & Time Tracking

**Endpoint**: `GET /api/v1/report/project/tasks`

**Response structure**:
```json
{
  "summary": {
    "total_tasks": 450,
    "done": 280,
    "in_progress": 120,
    "todo": 50,
    "overdue": 35,
    "completion_pct": 62.2,
    "total_estimated_hours": 2400,
    "total_logged_hours": 1850,
    "time_variance_pct": -22.9
  },
  "by_assignee": [
    {
      "employee_id": 5, "full_name": "Lê Dev A",
      "tasks_total": 45, "tasks_done": 30,
      "estimated_hours": 200, "logged_hours": 185, "overdue": 3
    }
  ],
  "by_priority": [
    { "priority": "urgent", "count": 12, "done": 8, "overdue": 4 }
  ],
  "overdue_tasks": [
    {
      "task_id": 55, "title": "Deploy staging", "project": "CRM Phase 2",
      "assignee": "Lê Dev A", "due_date": "2026-06-10", "days_overdue": 3
    }
  ],
  "velocity": [
    { "week": "2026-W22", "story_points_done": 42 }
  ]
}
```

---

### 6.4 Báo cáo KPI Goals

**Endpoint**: `GET /api/v1/report/kpi/cycle`

**Query parameters**: `cycle_label`, `department_id`, `employee_id`

**Response structure**:
```json
{
  "cycle": "2026-Q2",
  "summary": {
    "total_goals": 85,
    "achieved": 42,
    "partial": 28,
    "missed": 15,
    "avg_achievement_pct": 78.4,
    "avg_weighted_score": 82.1,
    "employees_with_goals": 35
  },
  "achievement_distribution": [
    { "band": "≥100%", "count": 20, "pct": 23.5 },
    { "band": "80-99%", "count": 35, "pct": 41.2 },
    { "band": "60-79%", "count": 20, "pct": 23.5 },
    { "band": "<60%",   "count": 10, "pct": 11.8 }
  ],
  "by_goal_type": [
    { "goal_type": "sales_target", "count": 20, "avg_achievement": 92.1, "avg_weight": 40 },
    { "goal_type": "project_delivery", "count": 15, "avg_achievement": 85.0, "avg_weight": 30 }
  ],
  "by_department": [
    {
      "department": "Sales", "employee_count": 12,
      "avg_achievement": 95.0, "avg_weighted_score": 89.4
    }
  ],
  "top_performers": [
    {
      "employee_id": 3, "full_name": "Nguyễn A", "department": "Sales",
      "weighted_score": 108.4, "goals_count": 4
    }
  ],
  "at_risk": [
    {
      "employee_id": 9, "full_name": "Hoàng C", "department": "Ops",
      "current_achievement_pct": 42.0, "days_remaining": 18
    }
  ]
}
```

**Query logic — KPI Goals đang hoạt động**:
```php
KpiGoal::where('status', 'active')
    ->where('cycle_label', $cycleLabel)
    ->join('employees', 'employees.id', '=', 'kpi_goals.employee_id')
    ->join('departments', 'departments.id', '=', 'employees.department_id')
    ->when($deptId, fn($q) => $q->where('employees.department_id', $deptId))
    ->selectRaw('
        departments.name as department,
        COUNT(*) as total_goals,
        AVG(achievement_pct) as avg_achievement,
        SUM(achievement_pct * weight_percent / 100) as weighted_score
    ')
    ->groupBy('departments.name')
    ->get();

// Dùng KpiSnapshot cho cycle đã kết thúc (immutable, chính xác hơn)
KpiSnapshot::where('cycle_label', $cycleLabel)
    ->join('employees', 'employees.id', '=', 'kpi_snapshots.employee_id')
    ->where('employees.organization_id', $orgId)
    ->selectRaw('
        employee_id,
        SUM(weighted_score) as total_weighted,
        AVG(achievement_pct) as avg_achievement,
        kpi_total_score
    ')
    ->groupBy('employee_id', 'kpi_total_score')
    ->get();
```

---

### 6.5 Báo cáo KPI Snapshot — Lịch sử cycle

**Endpoint**: `GET /api/v1/report/kpi/snapshot`

**Response structure**:
```json
{
  "cycles": [
    {
      "cycle_label": "2026-Q1",
      "employee_count": 30,
      "avg_kpi_score": 81.4,
      "score_distribution": {
        "A (≥90)": 8, "B (75-89)": 14, "C (60-74)": 6, "D (<60)": 2
      }
    }
  ],
  "employee_trend": [
    {
      "employee_id": 3, "full_name": "Nguyễn A",
      "history": [
        { "cycle": "2026-Q1", "kpi_score": 88.4 },
        { "cycle": "2026-Q2", "kpi_score": 94.2 }
      ]
    }
  ]
}
```

---

## 7. Cấu trúc API đầy đủ

### 7.1 Web routes (`Modules/Report/routes/web.php`)

```php
Route::middleware(['auth', 'tenant'])->prefix('report')->name('report.')->group(function () {
    Route::get('/', [ReportDashboardController::class, 'index'])->name('index');

    Route::middleware('can:REPORTS_HR,REPORTS_FULL')->prefix('hr')->group(function () {
        Route::get('/',            [HrReportController::class, 'index'])->name('hr.index');
        Route::get('/headcount',   [HrReportController::class, 'headcount'])->name('hr.headcount');
        Route::get('/leave',       [HrReportController::class, 'leave'])->name('hr.leave');
        Route::get('/recruitment', [HrReportController::class, 'recruitment'])->name('hr.recruitment');
        Route::get('/performance', [HrReportController::class, 'performance'])->name('hr.performance');
    });

    Route::middleware('can:REPORTS_TEAM,REPORTS_PERSONAL,REPORTS_FULL')->prefix('sales')->group(function () {
        Route::get('/',            [SalesReportController::class, 'index'])->name('sales.index');
        Route::get('/pipeline',    [SalesReportController::class, 'pipeline'])->name('sales.pipeline');
        Route::get('/conversion',  [SalesReportController::class, 'conversion'])->name('sales.conversion');
        Route::get('/activity',    [SalesReportController::class, 'activity'])->name('sales.activity');
    });

    Route::middleware('can:REPORTS_OPS,REPORTS_FULL')->prefix('project')->group(function () {
        Route::get('/',           [ProjectKpiReportController::class, 'projectIndex'])->name('project.index');
        Route::get('/tasks',      [ProjectKpiReportController::class, 'tasks'])->name('project.tasks');
        Route::get('/timelog',    [ProjectKpiReportController::class, 'timelog'])->name('project.timelog');
    });

    Route::middleware('can:REPORTS_OPS,REPORTS_FULL')->prefix('kpi')->group(function () {
        Route::get('/',          [ProjectKpiReportController::class, 'kpiIndex'])->name('kpi.index');
        Route::get('/cycle',     [ProjectKpiReportController::class, 'kpiCycle'])->name('kpi.cycle');
        Route::get('/employee',  [ProjectKpiReportController::class, 'kpiEmployee'])->name('kpi.employee');
        Route::get('/snapshot',  [ProjectKpiReportController::class, 'kpiSnapshot'])->name('kpi.snapshot');
    });
});
```

### 7.2 API routes (`Modules/Report/routes/api.php`)

```php
Route::middleware(['auth:sanctum', 'tenant'])->prefix('v1/report')->name('api.report.')->group(function () {

    // HR
    Route::get('/hr/headcount',   [HrReportApiController::class, 'headcount'])->middleware('can:REPORTS_HR');
    Route::get('/hr/leave',       [HrReportApiController::class, 'leave'])->middleware('can:REPORTS_HR');
    Route::get('/hr/recruitment', [HrReportApiController::class, 'recruitment'])->middleware('can:REPORTS_HR');
    Route::get('/hr/performance', [HrReportApiController::class, 'performance'])->middleware('can:REPORTS_HR');

    // Sales
    Route::get('/sales/pipeline',   [SalesReportApiController::class, 'pipeline'])->middleware('can:REPORTS_TEAM,REPORTS_PERSONAL');
    Route::get('/sales/conversion', [SalesReportApiController::class, 'conversion'])->middleware('can:REPORTS_TEAM');
    Route::get('/sales/activity',   [SalesReportApiController::class, 'activity'])->middleware('can:REPORTS_TEAM');

    // Project + KPI
    Route::get('/project/overview', [ProjectKpiApiController::class, 'overview'])->middleware('can:REPORTS_OPS');
    Route::get('/project/tasks',    [ProjectKpiApiController::class, 'tasks'])->middleware('can:REPORTS_OPS');
    Route::get('/project/timelog',  [ProjectKpiApiController::class, 'timelog'])->middleware('can:REPORTS_OPS');
    Route::get('/kpi/cycle',        [ProjectKpiApiController::class, 'kpiCycle'])->middleware('can:REPORTS_OPS');
    Route::get('/kpi/snapshot',     [ProjectKpiApiController::class, 'kpiSnapshot'])->middleware('can:REPORTS_OPS');

    // Export
    Route::post('/export', [ReportExportController::class, 'export'])->middleware('can:REPORTS_FULL');
});
```

---

## 8. Data Models (Read-only Query Objects)

### 8.1 Pattern: CQRS Query Handler

Mỗi report section có một Query class tương ứng:

```
Modules/Report/app/Queries/
├── Hr/
│   ├── HeadcountQuery.php
│   ├── LeaveReportQuery.php
│   ├── RecruitmentFunnelQuery.php
│   └── PerformanceReportQuery.php
├── Sales/
│   ├── PipelineFunnelQuery.php
│   ├── ConversionRateQuery.php
│   └── SalesActivityQuery.php
└── ProjectKpi/
    ├── ProjectOverviewQuery.php
    ├── TaskProgressQuery.php
    ├── KpiCycleQuery.php
    └── KpiSnapshotHistoryQuery.php
```

**Template query class**:
```php
namespace Modules\Report\Queries\Hr;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Modules\Employee\Models\Employee;

final class HeadcountQuery
{
    public function __construct(
        private readonly int    $orgId,
        private readonly string $dateFrom,
        private readonly string $dateTo,
        private readonly ?int   $branchId     = null,
        private readonly ?int   $departmentId = null,
    ) {}

    public static function fromRequest(array $params): self
    {
        return new self(
            orgId:        TenantContext::getOrganizationId(),
            dateFrom:     $params['date_from'] ?? now()->startOfMonth()->toDateString(),
            dateTo:       $params['date_to']   ?? now()->toDateString(),
            branchId:     $params['branch_id']     ?? null,
            departmentId: $params['department_id'] ?? null,
        );
    }

    public function summary(): array { ... }
    public function byDepartment(): Collection { ... }
    public function byBranch(): Collection { ... }
    public function trend(): Collection { ... }
}
```

---

## 9. Frontend — UI Components

### 9.1 Tech stack frontend

| Component | Library | Ghi chú |
|---|---|---|
| Charts | ECharts 6 | Đã có trong `package.json` |
| Tables | Tabulator 6 | Đã dùng ở các module khác |
| Filters | Alpine.js + TomSelect | TomSelect remote cho branch/dept |
| Date range | Flatpickr | Đã có trong `package.json` |
| Export | Tải về file (trigger backend) | |

### 9.2 Chart types per section

| Section | Chart |
|---|---|
| Headcount trend | Line chart (closing headcount theo tháng) |
| Headcount by dept | Bar chart hoặc treemap |
| Leave by type | Donut chart |
| Recruitment funnel | Horizontal funnel bar |
| Performance distribution | Bar chart (rating bands) |
| Pipeline funnel | Funnel chart |
| Sales trend | Line chart (new leads, won, lost) |
| Pipeline value by stage | Stacked bar |
| Project status | Pie + Gantt-lite |
| KPI achievement | Gauge hoặc radial bar per employee |
| KPI cycle comparison | Grouped bar chart |

### 9.3 Alpine.js state pattern

```javascript
// Shared report filter state
Alpine.data('reportFilter', () => ({
    dateFrom: dayjs().startOf('month').format('YYYY-MM-DD'),
    dateTo:   dayjs().format('YYYY-MM-DD'),
    branchId: null,
    deptId:   null,
    loading:  false,

    async fetch(endpoint) {
        this.loading = true;
        const params = new URLSearchParams({
            date_from:     this.dateFrom,
            date_to:       this.dateTo,
            branch_id:     this.branchId ?? '',
            department_id: this.deptId   ?? '',
        });
        try {
            const res = await fetch(`/api/v1/report/${endpoint}?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            return res.ok ? await res.json() : null;
        } finally {
            this.loading = false;
        }
    },
}));
```

---

## 10. Caching Strategy

| Endpoint | Cache key | TTL | Invalidate on |
|---|---|---|---|
| HR Headcount summary | `report:hr:headcount:{orgId}:{from}:{to}:{branch}:{dept}` | 15 phút | Employee create/update |
| Sales Pipeline | `report:sales:pipeline:{orgId}:{from}:{to}:{assigned}` | 5 phút | Lead stage change |
| KPI Cycle | `report:kpi:cycle:{orgId}:{cycle}:{dept}` | 30 phút | KpiGoal update |
| KPI Snapshot | `report:kpi:snapshot:{orgId}:{cycle}` | 24h | KpiSnapshot insert |

**Pattern**:
```php
Cache::remember("report:hr:headcount:{$orgId}:{$from}:{$to}", 900, fn() => $query->summary());
```

KpiSnapshot cache lâu hơn vì đây là **immutable** — không có write sau khi snap.

---

## 11. Export

### 11.1 Supported formats

| Format | Use case |
|---|---|
| Excel (.xlsx) | Tất cả sections — dùng `maatwebsite/excel` hoặc `spatie/simple-excel` |
| CSV | Raw data export |
| PDF | Summary report — dùng `barryvdh/laravel-dompdf` |

### 11.2 Export endpoint

```
POST /api/v1/report/export
{
  "section":  "hr.headcount",
  "format":   "xlsx",
  "filters":  { "date_from": "2026-01-01", "date_to": "2026-06-30" }
}
```

Response: `202 Accepted` + job queued → download link qua notification khi xong.

---

## 12. Multi-Tenancy — Checklist per orgId

Tất cả query trong Report module phải pass checklist sau:

| Check | Cách thực thi |
|---|---|
| ✅ TenantAwareModel auto-scope | `Employee`, `Lead`, `Project`, `KpiGoal`, `Survey` — scope tự động |
| ✅ Manual scope cho non-tenant models | `LeaveRequest` JOIN employees + `WHERE employees.organization_id = ?` |
| ✅ Recruitment dùng `org_id` | `RcApplication`, `RcCandidate` dùng `WHERE org_id = ?` (không phải `organization_id`) |
| ✅ Global LeadPipelineStage | `WHERE (organization_id = ? OR (is_global = 1 AND organization_id IS NULL))` |
| ✅ KpiSnapshot immutable | Chỉ đọc — không ghi vào snapshot từ report |
| ✅ AssessmentResult polymorphic | `WHERE subject_type = 'Employee' AND subject_id IN (SELECT id FROM employees WHERE organization_id = ?)` |
| ✅ Cache key chứa orgId | Không bao giờ cache chung giữa nhiều orgs |

---

## 13. Seeder

Module không cần seeder dữ liệu riêng — đọc từ các module khác.

Cần đăng ký permission mới vào `RolePermissionSeeder` nếu có permission mới ngoài danh sách hiện tại.

---

## 14. Lộ trình triển khai (Phase)

| Phase | Scope | Priority |
|---|---|---|
| **Phase 1** | HR Dashboard: Headcount + Leave (API + basic charts) | Cao |
| **Phase 1** | Sales Pipeline Funnel + Conversion Rate | Cao |
| **Phase 2** | Recruitment Funnel, Performance Review report | Trung bình |
| **Phase 2** | Project Overview + Task Progress | Trung bình |
| **Phase 3** | KPI Cycle + Snapshot history | Trung bình |
| **Phase 3** | Export Excel/PDF | Thấp |
| **Phase 4** | Sales Activity, Time Tracking deep dive | Thấp |
| **Phase 4** | Caching layer đầy đủ | Thấp |

---

## 15. Điểm tích hợp với module hiện có

| Module | Dữ liệu lấy | Ghi chú |
|---|---|---|
| `Employee` | headcount, demographic, status | TenantAware, multi-dept qua `employee_departments` |
| `Department` | group-by hierachy, materialized path | Dùng `path` LIKE query cho subtree |
| `Branch` | group-by location | Hierarchical via `path` |
| `Leave` | leave stats, balance | `leave_requests` non-TenantAware → JOIN employees |
| `PerformanceReview` | scores, ratings | `snap_*` fields tránh JOIN realtime |
| `Recruitment` | funnel, source | `org_id` field khác convention |
| `JobPosting` | open positions, headcount demand | `jp_job_posts.headcount` vs `hired_count` |
| `Lead` | pipeline, conversion | `lead_stage_history` cho avg time-in-stage |
| `LeadPipelineStage` | stage labels, is_won/is_lost | Global + org-specific stages |
| `Customer` | lifecycle, conversion from lead | `converted_from_lead_id` link |
| `Project` | status, budget | owner_id → Employee |
| `Task` | progress, overdue, velocity | `is_leaf = true` cho đúng count |
| `KpiGoal` | in-progress achievement | Live data |
| `KpiSnapshot` | final cycle results | Immutable — cache 24h |
| `AssessmentResult` | digital maturity, lead scoring | Polymorphic subject |
