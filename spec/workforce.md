# Đặc Tả Module: Workforce Center

> **Hệ thống:** SaaS SME
> **Module:** Workforce Center
> **Phiên bản:** 2.0.0 (system-aligned)
> **Ngày:** 2026-06-04
> **Stack:** Laravel 13 · PHP 8.4 · SQLite (dev) / configurable prod
> **Pattern:** AVSA + CQRS-lite (NWIDART Modules)

---

## Mục lục

1. [Tổng quan](#1-tổng-quan)
2. [So sánh spec gốc vs hệ thống](#2-so-sánh-spec-gốc-vs-hệ-thống)
3. [Kiến trúc module](#3-kiến-trúc-module)
4. [Enum Values](#4-enum-values)
5. [ERD](#5-erd)
6. [Thay đổi schema](#6-thay-đổi-schema)
7. [Business Rules](#7-business-rules)
8. [AVSA Module Map](#8-avsa-module-map)
9. [Luồng nghiệp vụ](#9-luồng-nghiệp-vụ)
10. [Query Patterns](#10-query-patterns)
11. [API Endpoints](#11-api-endpoints)
12. [Indexes](#12-indexes)
13. [Lộ trình triển khai](#13-lộ-trình-triển-khai)

---

## 1. Tổng quan

**Workforce Center** quản lý nhân sự trong doanh nghiệp SME gồm 3 sub-module chính. Spec này được điều chỉnh hoàn toàn theo kiến trúc hệ thống hiện tại: BIGINT PK, TenantAwareModel, NWIDART Modules, AVSA+CQRS-lite.

| Sub-module | NWIDART Module | Trạng thái | Chức năng cốt lõi |
|---|---|---|---|
| **Nhân viên** | `Modules/Employee` | Đã có, cần mở rộng | Hồ sơ, hợp đồng, lịch sử, salary |
| **Nghỉ phép** | `Modules/Leave` | Tạo mới | Policy, balance, đơn xin nghỉ, duyệt |
| **KPI Goals** | `Modules/KpiGoal` | Tạo mới | Thiết lập mục tiêu, tiến độ thủ công, snapshot cuối kỳ |

### Người dùng và quyền

| Vai trò | Quyền |
|---|---|
| **HR Admin** | Toàn quyền 3 sub-module; xem salary/bank |
| **Manager** | Xem direct reports; duyệt nghỉ phép; đặt/duyệt KPI cho nhân viên trong team |
| **Employee** | Xem hồ sơ bản thân; đăng ký nghỉ; xem tiến độ KPI của mình |

### Phạm vi

**Trong phạm vi:**
- Mở rộng hồ sơ nhân viên: hợp đồng, salary, thông tin cá nhân bổ sung, lịch sử thay đổi vị trí/lương (immutable)
- Salary band trên `job_titles` (mở rộng bảng JobTitle có sẵn)
- Chính sách nghỉ phép theo org hoặc override theo chức danh/phòng ban
- Balance nghỉ phép: entitled, used, pending, carried-over, adjusted
- Luồng duyệt đơn nghỉ theo manager chain (atomic transaction)
- Mục tiêu KPI cá nhân thủ công (Phase 3A), auto-sync từ Project module (Phase 3B — defer)
- Điểm KPI tổng hợp theo trọng số, snapshot bất biến cuối kỳ

**Ngoài phạm vi:**
- Payroll & bảng lương chi tiết
- Tuyển dụng / ATS
- Chấm công / timekeeping
- Competency framework & performance review cycle
- Learning & Development
- KPI auto-sync từ Project module (defer đến Phase 3B khi Project module ổn định)

---

## 2. So sánh spec gốc vs hệ thống

| Hạng mục | Spec gốc v3 | Hệ thống hiện tại | Quyết định |
|---|---|---|---|
| **PK** | UUID (`gen_random_uuid()`) | BIGINT auto-increment + cột `uuid` secondary unique | **Dùng BIGINT PK** — giữ pattern hệ thống |
| **Bảng `wk_positions`** | Bảng riêng với `salary_min/max`, `level` 1-5 | Đã có `job_titles` với `level` 1-20, `category` | **Mở rộng `job_titles`** — thêm `salary_min/max/currency`, `is_manager_role` |
| **`department_id` trên position** | Có trên `wk_positions` | `job_titles` là org-wide (không gắn dept) | **Bỏ** — job_title scope là org, không phải dept |
| **`employment_type`** | `full_time/part_time/contractor/intern/probation` | `full_time/part_time/contract/intern` (thiếu `probation`, sai `contractor`) | **Đổi** `contract` → `contractor`, **thêm** `probation` |
| **`status`** | `active/probation/on_leave/resigned/terminated` | `active/on_leave/resigned/terminated` (thiếu `probation`) | **Thêm** `probation` vào enum |
| **History table** | `wk_position_history` mới | `employee_history` đã có với old_*/new_* pairs | **Mở rộng** `employee_history` — thêm `old_salary_base/new_salary_base`, `salary_change` change_type |
| **`employees.salary_base`** | Có | Không có | **Thêm migration** ALTER TABLE employees |
| **Bảng Leave** | `wk_leave_policies`, `wk_leave_balances`, `wk_timeoffs` | Không tồn tại | **Tạo mới** trong Leave module |
| **`leave_policies.position_id`** | FK → `wk_positions` | Không có `wk_positions` | **Đổi thành `job_title_id`** FK → `job_titles` |
| **Bảng KPI** | `wk_kpi_goals`, `wk_kpi_sources`, `wk_kpi_snapshots` | `performance_reviews` (stub), không có kpi_goals | **Tạo mới** trong KpiGoal module |
| **KPI auto-sync** | Phase 3 — KpiEntryObserver + TaskObserver | Project module chỉ là stub | **Defer** toàn bộ auto-sync sang Phase 3B |
| **Partial index syntax** | MySQL/PostgreSQL `WHERE` clause | SQLite dev không hỗ trợ partial index | **Dùng index tiêu chuẩn** thay thế |
| **`wk_timeoffs.approved_by`** | FK UUID | Cần FK → `employees.id` (BIGINT) | **FK BIGINT** → `employees.id` |
| **`kpi_snapshots` — 1 row per goal** | `UNIQUE(goal_id)` | — | **Giữ** — 1 snapshot per goal per cycle close |
| **`FILTER` trong GROUP BY** | PostgreSQL syntax | SQLite/MySQL không hỗ trợ | **Đổi thành** `SUM(CASE WHEN ... THEN 1 ELSE 0 END)` |

---

## 3. Kiến trúc module

```
Modules/
├── Employee/          ← đã có — mở rộng thêm fields + actions
│   ├── Enums/
│   │   ├── EmployeeStatus.php      (thêm Probation)
│   │   └── EmploymentType.php      (thêm Probation, đổi Contract→Contractor)
│   ├── Actions/
│   │   ├── StoreEmployeeAction.php (đã có)
│   │   ├── UpdateEmployeeAction.php (đã có)
│   │   ├── TransferEmployeeAction.php  ← mới
│   │   └── OffboardEmployeeAction.php  ← mới
│   ├── Data/
│   │   ├── StoreEmployeeData.php   (đã có)
│   │   ├── UpdateEmployeeData.php  (đã có)
│   │   └── EmployeeSalaryData.php  ← mới (HR-only fields)
│   └── database/migrations/
│       ├── ...alter_employees_add_contract_salary_fields.php  ← mới
│       ├── ...alter_job_titles_add_salary_band.php            ← mới
│       └── ...alter_employee_history_add_salary_columns.php   ← mới
│
├── Leave/             ← tạo mới: php artisan module:make Leave
│   ├── Models/
│   │   ├── LeavePolicy.php
│   │   ├── LeaveBalance.php
│   │   └── LeaveRequest.php
│   ├── Enums/
│   │   ├── LeaveType.php
│   │   └── LeaveRequestStatus.php
│   ├── Actions/
│   │   ├── StoreLeavePolicyAction.php
│   │   ├── UpdateLeavePolicyAction.php
│   │   ├── StoreLeaveRequestAction.php   ← atomic: insert + balance.pending+=
│   │   ├── ApproveLeaveAction.php        ← pending→approved, balance sync
│   │   ├── RejectLeaveAction.php         ← pending−, balance hoàn
│   │   └── CancelLeaveRequestAction.php
│   ├── Queries/
│   │   ├── ListLeaveRequestsQuery.php
│   │   ├── ListLeaveRequestsHandler.php
│   │   ├── ListPendingApprovalQuery.php
│   │   └── ListPendingApprovalHandler.php
│   ├── Observers/
│   │   └── LeaveRequestObserver.php
│   └── Policies/
│       ├── LeavePolicyPolicy.php
│       └── LeaveRequestPolicy.php
│
└── KpiGoal/           ← tạo mới: php artisan module:make KpiGoal
    ├── Models/
    │   ├── KpiGoal.php
    │   ├── KpiSnapshot.php       ← Phase 3A
    │   └── KpiSource.php         ← Phase 3B
    ├── Enums/
    │   ├── KpiGoalType.php       (manual | linked_source)
    │   ├── KpiGoalStatus.php     (draft | active | completed | cancelled)
    │   └── KpiDirection.php      (higher_better | lower_better)
    ├── Actions/
    │   ├── StoreKpiGoalAction.php
    │   ├── UpdateKpiGoalAction.php
    │   ├── ApproveKpiGoalAction.php     ← validate weight sum
    │   ├── UpdateKpiProgressAction.php  ← manual current_value + recalc achievement_pct
    │   └── CloseKpiCycleAction.php      ← sync + INSERT kpi_snapshots + mark completed
    ├── Queries/
    │   ├── ListKpiGoalsQuery.php
    │   ├── ListKpiGoalsHandler.php
    │   ├── KpiLeaderboardQuery.php
    │   └── KpiLeaderboardHandler.php
    ├── Observers/
    │   └── KpiGoalObserver.php          ← recalc achievement_pct on current_value change
    └── Policies/
        └── KpiGoalPolicy.php
```

---

## 4. Enum Values

### Module Employee

| Trường | Giá trị hiện tại | Giá trị sau cập nhật |
|---|---|---|
| `employees.status` | `active / on_leave / resigned / terminated` | `active / **probation** / on_leave / resigned / terminated` |
| `employees.employment_type` | `full_time / part_time / **contract** / intern` | `full_time / part_time / **contractor** / **probation** / intern` |
| `employee_history.change_type` | `hire / branch_transfer / dept_transfer / promotion / demotion / manager_change / leave / return_from_leave / resign / terminate` | thêm `**salary_change** / **separation**` |

### Module Leave

| Enum | Giá trị |
|---|---|
| `LeaveType` | `annual / sick / maternity / paternity / unpaid / compensatory / bereavement / other` |
| `LeaveRequestStatus` | `pending / approved / rejected / cancelled` |

### Module KpiGoal

| Enum | Giá trị |
|---|---|
| `KpiGoalType` | `manual` (Phase 3A) · `linked_source` (Phase 3B) |
| `KpiGoalStatus` | `draft / active / completed / cancelled` |
| `KpiDirection` | `higher_better / lower_better` |
| `KpiAggregationType` *(Phase 3B)* | `latest / sum / avg / percentage` |
| `KpiSourceType` *(Phase 3B)* | `project_tasks` (extend later) |
| `KpiDateRangeType` *(Phase 3B)* | `cycle_period / rolling_30d / rolling_90d` |

---

## 5. ERD

```
[organizations] ─── 1:N ──► [branches]
                    1:N ──► [departments]
                    1:N ──► [job_titles]  ← mở rộng salary band
                    1:N ──► [employees]
                                │
              ┌─────────────────┼────────────────────┐
              │                 │                    │
             1:N               1:N                  1:N
              │                 │                    │
              ▼                 ▼                    ▼
     [employee_history]   [leave_requests]     [kpi_goals]
     (immutable)          └─FK─► [leave_balances]   └─FK─► [kpi_snapshots]
                          └─FK─► [leave_policies]   └─FK─► [kpi_sources] (Phase 3B)

[employees] self-ref: manager_id → employees.id
[kpi_goals] self-ref: parent_goal_id → kpi_goals.id (OKR)

Cross-module (polymorphic — không FK cứng):
kpi_sources.source_id → projects.id  (Project module — Phase 3B)
```

### Quan hệ tổng hợp

| Bảng A | Quan hệ | Bảng B | Ghi chú |
|---|---|---|---|
| `employees` | N:1 | `job_titles` | `job_title_id` — salary band từ job_title |
| `employees` | N:1 | `departments` | `department_id` |
| `employees` | N:1 | `branches` | `branch_id` |
| `employees` | N:1 | `employees` (self) | `manager_id` |
| `employees` | 1:N | `employee_history` | Audit trail — immutable INSERT only |
| `employees` | 1:N | `leave_requests` | Đơn nghỉ phép |
| `employees` | 1:N | `kpi_goals` | Mục tiêu KPI |
| `leave_policies` | 1:N | `leave_balances` | Balance per employee/year |
| `leave_balances` | 1:N | `leave_requests` | Đơn dùng balance này |
| `kpi_goals` | 1:1 | `kpi_sources` | Cấu hình nguồn đo (Phase 3B) |
| `kpi_goals` | 1:N | `kpi_snapshots` | Snapshot bất biến cuối kỳ |

---

## 6. Thay đổi schema

> **Quy ước:**
> - Bảng đã có → viết dưới dạng `ALTER TABLE` (migration trong module hiện tại)
> - Bảng mới → viết dưới dạng `CREATE TABLE` (migration trong module mới)
> - PK: BIGINT AUTO_INCREMENT (SQLite: INTEGER PRIMARY KEY AUTOINCREMENT)
> - Cột `uuid` secondary unique — thêm theo pattern hệ thống nếu bảng mới

---

### 6.1 Mở rộng `job_titles` — salary band

```sql
-- Migration: alter_job_titles_add_salary_band_and_manager_role
ALTER TABLE job_titles
    ADD COLUMN salary_min      DECIMAL(15,2) NULL        DEFAULT NULL,
    ADD COLUMN salary_max      DECIMAL(15,2) NULL        DEFAULT NULL,
    ADD COLUMN salary_currency CHAR(3)       NOT NULL    DEFAULT 'VND',
    ADD COLUMN is_manager_role TINYINT(1)    NOT NULL    DEFAULT 0;
```

**Ràng buộc logic (app layer):** `salary_min <= salary_max` khi cả hai không NULL.

---

### 6.2 Mở rộng `employees` — hợp đồng, lương, thông tin cá nhân

```sql
-- Migration: alter_employees_add_contract_salary_personal_fields
ALTER TABLE employees
    -- Thông tin cá nhân bổ sung
    ADD COLUMN personal_email            VARCHAR(150)   NULL DEFAULT NULL,
    ADD COLUMN address                   TEXT           NULL DEFAULT NULL,
    ADD COLUMN national_id_issued        DATE           NULL DEFAULT NULL,
    ADD COLUMN bank_account              VARCHAR(30)    NULL DEFAULT NULL,
    ADD COLUMN bank_name                 VARCHAR(100)   NULL DEFAULT NULL,
    -- Hợp đồng / trạng thái
    ADD COLUMN probation_end_date        DATE           NULL DEFAULT NULL,
    ADD COLUMN contract_start            DATE           NULL DEFAULT NULL,
    ADD COLUMN contract_end              DATE           NULL DEFAULT NULL   COMMENT 'NULL = không thời hạn',
    -- Lương
    ADD COLUMN salary_base               DECIMAL(15,2)  NULL DEFAULT NULL,
    ADD COLUMN salary_currency           CHAR(3)        NOT NULL DEFAULT 'VND',
    -- Vị trí làm việc
    ADD COLUMN work_location             VARCHAR(20)    NULL DEFAULT NULL   COMMENT 'office | remote | hybrid',
    -- Liên hệ khẩn cấp
    ADD COLUMN emergency_contact_name    VARCHAR(150)   NULL DEFAULT NULL,
    ADD COLUMN emergency_contact_phone   VARCHAR(20)    NULL DEFAULT NULL,
    -- Thôi việc
    ADD COLUMN resigned_at               DATE           NULL DEFAULT NULL,
    ADD COLUMN resignation_reason        TEXT           NULL DEFAULT NULL,
    -- Ghi chú nội bộ HR (ẩn với Employee role)
    ADD COLUMN notes                     TEXT           NULL DEFAULT NULL;
```

**Cập nhật Enum tại app layer (PHP):**

```php
// Modules/Employee/Enums/EmployeeStatus.php
enum EmployeeStatus: string {
    case Active     = 'active';
    case Probation  = 'probation';   // thêm mới
    case OnLeave    = 'on_leave';
    case Resigned   = 'resigned';
    case Terminated = 'terminated';
}

// Modules/Employee/Enums/EmploymentType.php
enum EmploymentType: string {
    case FullTime   = 'full_time';
    case PartTime   = 'part_time';
    case Contractor = 'contractor';  // đổi từ 'contract'
    case Probation  = 'probation';   // thêm mới
    case Intern     = 'intern';
}
```

**Index bổ sung:**

```sql
CREATE INDEX idx_employees_contract_end
    ON employees (organization_id, contract_end, status);

CREATE INDEX idx_employees_status_dept
    ON employees (organization_id, department_id, status);
```

---

### 6.3 Mở rộng `employee_history` — salary snapshot

```sql
-- Migration: alter_employee_history_add_salary_columns
ALTER TABLE employee_history
    ADD COLUMN old_salary_base DECIMAL(15,2) NULL DEFAULT NULL,
    ADD COLUMN new_salary_base DECIMAL(15,2) NULL DEFAULT NULL;
```

**Cập nhật Enum `change_type` tại app layer:**

```php
// Modules/Employee/Enums/EmployeeHistoryChangeType.php
enum EmployeeHistoryChangeType: string {
    case Hire            = 'hire';
    case BranchTransfer  = 'branch_transfer';
    case DeptTransfer    = 'dept_transfer';
    case Promotion       = 'promotion';
    case Demotion        = 'demotion';
    case ManagerChange   = 'manager_change';
    case SalaryChange    = 'salary_change';    // thêm mới
    case Leave           = 'leave';
    case ReturnFromLeave = 'return_from_leave';
    case Resign          = 'resign';
    case Terminate       = 'terminate';
    case Separation      = 'separation';       // thêm mới (tổng hợp offboard)
}
```

---

### 6.4 Bảng mới: `leave_policies` — Module Leave

```sql
CREATE TABLE leave_policies (
    id                   INTEGER       PRIMARY KEY AUTOINCREMENT,
    uuid                 VARCHAR(36)   NOT NULL UNIQUE,
    organization_id      BIGINT        NOT NULL REFERENCES organizations(id),
    leave_type           VARCHAR(20)   NOT NULL,
        -- annual | sick | maternity | paternity | unpaid | compensatory | bereavement | other
    name                 VARCHAR(100)  NOT NULL,
    days_per_year        DECIMAL(5,1)  NOT NULL,
    carry_over_days      DECIMAL(5,1)  NOT NULL DEFAULT 0,
    min_advance_days     SMALLINT      NOT NULL DEFAULT 1,
    max_consecutive_days SMALLINT      NULL     DEFAULT NULL,
    requires_approval    TINYINT(1)   NOT NULL DEFAULT 1,
    job_title_id         BIGINT        NULL     DEFAULT NULL REFERENCES job_titles(id),
        -- NULL = áp dụng org-level; set = override cho chức danh này
    department_id        BIGINT        NULL     DEFAULT NULL REFERENCES departments(id),
        -- NULL = áp dụng org-level; set = override cho phòng ban này
        -- Ưu tiên: job_title_id > department_id > (cả hai NULL = org default)
    effective_from       DATE          NOT NULL,
    is_active            TINYINT(1)   NOT NULL DEFAULT 1,
    created_by           BIGINT        NOT NULL REFERENCES users(id),
    created_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Unique constraint: một loại nghỉ chỉ có 1 policy active per scope
CREATE UNIQUE INDEX idx_leave_policies_scope
    ON leave_policies (organization_id, leave_type, job_title_id, department_id);

CREATE INDEX idx_leave_policies_org_type
    ON leave_policies (organization_id, leave_type, is_active);
```

**Quy tắc ưu tiên policy (app layer):**
1. `job_title_id = employee.job_title_id` → ưu tiên cao nhất
2. `department_id = employee.department_id` AND `job_title_id IS NULL`
3. `job_title_id IS NULL` AND `department_id IS NULL` → org default

---

### 6.5 Bảng mới: `leave_balances` — Module Leave

```sql
CREATE TABLE leave_balances (
    id            INTEGER      PRIMARY KEY AUTOINCREMENT,
    employee_id   BIGINT       NOT NULL REFERENCES employees(id),
    policy_id     BIGINT       NOT NULL REFERENCES leave_policies(id),
    leave_type    VARCHAR(20)  NOT NULL,   -- denormalized từ policy, tránh JOIN khi query
    year          SMALLINT     NOT NULL,
    entitled_days DECIMAL(5,1) NOT NULL,
    used_days     DECIMAL(5,1) NOT NULL DEFAULT 0,
    pending_days  DECIMAL(5,1) NOT NULL DEFAULT 0,
    carried_over  DECIMAL(5,1) NOT NULL DEFAULT 0,
    adjusted      DECIMAL(5,1) NOT NULL DEFAULT 0,
        -- HR có thể điều chỉnh thủ công (cộng/trừ ngày)
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Công thức còn lại:
-- remaining = entitled_days + carried_over + adjusted - used_days - pending_days

CREATE UNIQUE INDEX idx_leave_balances_unique
    ON leave_balances (employee_id, policy_id, year);

CREATE INDEX idx_leave_balances_emp_year
    ON leave_balances (employee_id, year, leave_type);
```

---

### 6.6 Bảng mới: `leave_requests` — Module Leave

```sql
CREATE TABLE leave_requests (
    id               INTEGER      PRIMARY KEY AUTOINCREMENT,
    uuid             VARCHAR(36)  NOT NULL UNIQUE,
    organization_id  BIGINT       NOT NULL REFERENCES organizations(id),
    employee_id      BIGINT       NOT NULL REFERENCES employees(id),
    balance_id       BIGINT       NOT NULL REFERENCES leave_balances(id),
    leave_type       VARCHAR(20)  NOT NULL,   -- denormalized từ balance
    date_from        DATE         NOT NULL,
    date_to          DATE         NOT NULL,
    days_count       DECIMAL(5,1) NOT NULL,   -- tính server-side, không tin client
    status           VARCHAR(20)  NOT NULL DEFAULT 'pending',
        -- pending | approved | rejected | cancelled
    reason           TEXT         NULL DEFAULT NULL,
    attachment_url   TEXT         NULL DEFAULT NULL,
    approved_by      BIGINT       NULL DEFAULT NULL REFERENCES employees(id),
    approved_at      TIMESTAMP    NULL DEFAULT NULL,
    rejected_reason  TEXT         NULL DEFAULT NULL,
    created_by       BIGINT       NOT NULL REFERENCES users(id),
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_leave_requests_emp_status
    ON leave_requests (employee_id, status, date_from);

CREATE INDEX idx_leave_requests_approver
    ON leave_requests (approved_by, status);

CREATE INDEX idx_leave_requests_org_date
    ON leave_requests (organization_id, date_from, date_to);
```

---

### 6.7 Bảng mới: `kpi_goals` — Module KpiGoal

```sql
CREATE TABLE kpi_goals (
    id               INTEGER       PRIMARY KEY AUTOINCREMENT,
    uuid             VARCHAR(36)   NOT NULL UNIQUE,
    organization_id  BIGINT        NOT NULL REFERENCES organizations(id),
    employee_id      BIGINT        NOT NULL REFERENCES employees(id),
    cycle_label      VARCHAR(30)   NOT NULL,   -- "Q3-2024" | "H1-2024" | "2024"
    cycle_start      DATE          NOT NULL,
    cycle_end        DATE          NOT NULL,
    parent_goal_id   BIGINT        NULL DEFAULT NULL REFERENCES kpi_goals(id),
        -- OKR: Objective → Key Results
    title            VARCHAR(300)  NOT NULL,
    description      TEXT          NULL DEFAULT NULL,
    goal_type        VARCHAR(20)   NOT NULL DEFAULT 'manual',
        -- manual (Phase 3A) | linked_source (Phase 3B)
    target_value     DECIMAL(15,4) NOT NULL,
    current_value    DECIMAL(15,4) NOT NULL DEFAULT 0,
    unit             VARCHAR(30)   NULL DEFAULT NULL,   -- %, VND, tasks, điểm
    direction        VARCHAR(20)   NOT NULL DEFAULT 'higher_better',
        -- higher_better | lower_better
    achievement_pct  DECIMAL(6,2)  NOT NULL DEFAULT 0,
        -- tính tự động qua KpiGoalObserver khi current_value thay đổi
    weight_percent   SMALLINT      NOT NULL DEFAULT 10,
        -- tổng tất cả goal active+completed trong cycle/employee = 100
    status           VARCHAR(20)   NOT NULL DEFAULT 'draft',
        -- draft | active | completed | cancelled
    last_synced_at   TIMESTAMP     NULL DEFAULT NULL,
    approved_by      BIGINT        NULL DEFAULT NULL REFERENCES employees(id),
    approved_at      TIMESTAMP     NULL DEFAULT NULL,
    created_by       BIGINT        NOT NULL REFERENCES users(id),
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_kpi_goals_emp_cycle
    ON kpi_goals (employee_id, cycle_label, status);

CREATE INDEX idx_kpi_goals_cycle_end
    ON kpi_goals (organization_id, cycle_end, status);
```

---

### 6.8 Bảng mới: `kpi_sources` — Module KpiGoal (Phase 3B only)

> Bảng này chỉ tạo migration ở Phase 3B khi Project module ổn định.

```sql
CREATE TABLE kpi_sources (
    id                   INTEGER      PRIMARY KEY AUTOINCREMENT,
    goal_id              BIGINT       NOT NULL UNIQUE REFERENCES kpi_goals(id),
    source_type          VARCHAR(30)  NOT NULL,
        -- project_tasks (extend later khi có thêm nguồn)
    source_id            BIGINT       NOT NULL,
        -- Polymorphic — không FK cứng; validate ở app layer
    aggregation_type     VARCHAR(20)  NOT NULL DEFAULT 'latest',
        -- latest | sum | avg | percentage
    filter_assignee_only TINYINT(1)  NOT NULL DEFAULT 1,
    date_range_type      VARCHAR(20)  NOT NULL DEFAULT 'cycle_period',
        -- cycle_period | rolling_30d | rolling_90d
    multiplier           DECIMAL(8,4) NOT NULL DEFAULT 1.0,
    last_synced_at       TIMESTAMP    NULL DEFAULT NULL
);

CREATE INDEX idx_kpi_sources_lookup
    ON kpi_sources (source_type, source_id);
```

---

### 6.9 Bảng mới: `kpi_snapshots` — Module KpiGoal

```sql
CREATE TABLE kpi_snapshots (
    id              INTEGER       PRIMARY KEY AUTOINCREMENT,
    goal_id         BIGINT        NOT NULL REFERENCES kpi_goals(id),
    employee_id     BIGINT        NOT NULL REFERENCES employees(id),
        -- denormalized để query leaderboard không JOIN kpi_goals
    cycle_label     VARCHAR(30)   NOT NULL,   -- denormalized
    target_value    DECIMAL(15,4) NOT NULL,   -- freeze tại thời điểm chốt
    final_value     DECIMAL(15,4) NOT NULL,   -- freeze tại thời điểm chốt
    achievement_pct DECIMAL(6,2)  NOT NULL,
    weight_percent  SMALLINT      NOT NULL,   -- freeze
    weighted_score  DECIMAL(6,2)  NOT NULL,
        -- = achievement_pct * weight_percent / 100
    kpi_total_score DECIMAL(6,2)  NULL DEFAULT NULL,
        -- tổng weighted_score tất cả goals trong cycle; chỉ set trên row summary
    snapped_by      BIGINT        NOT NULL REFERENCES users(id),
    snapped_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Một goal chỉ có 1 snapshot (immutable — không UPDATE/DELETE sau INSERT)
CREATE UNIQUE INDEX idx_kpi_snapshots_goal
    ON kpi_snapshots (goal_id);

CREATE INDEX idx_kpi_snapshots_emp_cycle
    ON kpi_snapshots (employee_id, cycle_label);
```

---

## 7. Business Rules

### BR-WK-001: Immutable employee history

- Mọi thay đổi `job_title_id`, `department_id`, `branch_id`, `manager_id`, `salary_base`, `status` → bắt buộc INSERT `employee_history` (thực hiện trong `TransferEmployeeAction` / `OffboardEmployeeAction`)
- Không UPDATE/DELETE bất kỳ row nào trong `employee_history`
- `employee_code` bất biến sau khi tạo (validate ở `UpdateEmployeeAction`)

### BR-WK-002: Manager chain integrity

- Không tạo vòng tròn: A → B → C → A (kiểm tra đệ quy ở app layer trước khi UPDATE)
- Manager nên có `job_titles.is_manager_role = 1` hoặc HR override tường minh
- Trước khi offboard manager: bắt buộc reassign direct reports (kiểm tra trong `OffboardEmployeeAction` — từ chối nếu có direct reports chưa được reassign)

### BR-WK-003: Leave balance — atomic transaction

- INSERT `leave_requests` và UPDATE `leave_balances.pending_days` phải trong cùng 1 DB transaction
- `days_count` tính server-side — không tin input từ client
- Không hủy đơn `approved` khi `date_from < NOW()` (đã qua ngày nghỉ)
- Không tạo đơn nếu `remaining < days_count` (ngoại lệ: `sick`, `unpaid` không cần kiểm tra balance)
- Khi approve: `pending_days -= days_count`, `used_days += days_count` — cùng transaction
- Khi reject/cancel: `pending_days -= days_count` — cùng transaction

### BR-WK-004: KPI weight sum = 100

- Tổng `weight_percent` của tất cả goal `status IN ('active', 'completed')` trong cùng `cycle_label/employee_id` phải bằng 100
- Constraint chỉ validate khi `ApproveKpiGoalAction` hoặc `CloseKpiCycleAction` — không chặn khi draft
- Goal `draft` và `cancelled` không tính vào tổng weight

### BR-WK-005: KPI snapshot — immutable

- Snapshot chỉ tạo khi `CloseKpiCycleAction` — không tạo trước
- Sau khi INSERT snapshot: không UPDATE, không DELETE (enforce ở model: override `save()` và `delete()` nếu `exists`)
- Achievement cap: `higher_better` max 150%, `lower_better` min 0%
- `weighted_score = achievement_pct * weight_percent / 100`

### BR-WK-006: KPI source — no hard FK (Phase 3B)

- `kpi_sources.source_id` không có FK cứng sang Project module (tránh circular dependency giữa module)
- Validation khi tạo: kiểm tra `source_id` tồn tại ở app layer, không ở DB constraint
- Nếu source bị xóa ở Project module: set `goal_type = 'manual'`, xóa `kpi_sources` row, notify HR qua activity log

---

## 8. AVSA Module Map

### 8.1 Module Employee — extensions

#### Actions mới

| Action | Trách nhiệm |
|---|---|
| `TransferEmployeeAction` | UPDATE employees (job_title_id / department_id / branch_id / manager_id / salary_base) + INSERT employee_history với change_type phù hợp + effective_date |
| `OffboardEmployeeAction` | Kiểm tra direct reports → nếu còn thì từ chối; UPDATE employees status=resigned/terminated + resigned_at; INSERT employee_history (change_type=separation); cancel pending leave_requests + hoàn balance; ghi activity log |

#### Data (Spatie Laravel Data)

```php
// Modules/Employee/Data/EmployeeSalaryData.php
class EmployeeSalaryData extends Data {
    public function __construct(
        public readonly ?float  $salary_base,
        public readonly string  $salary_currency = 'VND',
        public readonly ?string $bank_account,
        public readonly ?string $bank_name,
    ) {}
    // Policy guard: chỉ HR Admin mới được populate trường này
}
```

#### Observers — cập nhật `EmployeeObserver`

- Khi `job_title_id` thay đổi: sync `snap_job_title`, `snap_job_level`
- Khi `department_id` thay đổi: sync `snap_dept_name`
- Khi `branch_id` thay đổi: sync `snap_branch_name`

---

### 8.2 Module Leave — mới hoàn toàn

#### Models

```php
// LeavePolicy extends TenantAwareModel
// LeaveBalance extends TenantAwareModel  (no SoftDeletes)
// LeaveRequest extends TenantAwareModel  (no SoftDeletes — dùng status=cancelled)
```

#### Actions

| Action | Input | Trách nhiệm |
|---|---|---|
| `StoreLeavePolicyAction` | `StoreLeavePolicyData` | Tạo policy + validate unique scope |
| `UpdateLeavePolicyAction` | `UpdateLeavePolicyData` | Cập nhật policy (không thay đổi existing balances) |
| `StoreLeaveRequestAction` | `StoreLeaveRequestData` | Lookup balance → validate remaining → DB::transaction { INSERT leave_requests + UPDATE leave_balances.pending_days += days_count } → notify manager |
| `ApproveLeaveAction` | `leave_request_id` | Validate status=pending → DB::transaction { UPDATE status=approved + balance: pending−=N, used+=N } |
| `RejectLeaveAction` | `leave_request_id`, `reason` | DB::transaction { UPDATE status=rejected + balance: pending−=N } |
| `CancelLeaveRequestAction` | `leave_request_id` | Validate date_from >= today + status=pending → DB::transaction { UPDATE status=cancelled + balance: pending−=N } |

#### Queries

| Query | Handler | Filter params |
|---|---|---|
| `ListLeaveRequestsQuery` | `ListLeaveRequestsHandler` | employee_id, status, date_from, date_to, leave_type |
| `ListPendingApprovalQuery` | `ListPendingApprovalHandler` | approved_by (manager employee_id) |

#### Observer: `LeaveRequestObserver`

- `updated()`: khi status → approved, log activity "leave_approved"
- `updated()`: khi status → rejected, log activity "leave_rejected"

---

### 8.3 Module KpiGoal — mới hoàn toàn

#### Models

```php
// KpiGoal extends TenantAwareModel  (SoftDeletes? No — dùng status=cancelled)
// KpiSnapshot — no SoftDeletes, no update sau insert
// KpiSource — Phase 3B
```

#### Actions

| Action | Input | Trách nhiệm |
|---|---|---|
| `StoreKpiGoalAction` | `StoreKpiGoalData` | Tạo goal với status=draft; validate cycle_start < cycle_end |
| `UpdateKpiGoalAction` | `UpdateKpiGoalData` | Chỉ update khi status=draft/active |
| `ApproveKpiGoalAction` | `kpi_goal_id` | Validate tổng weight_percent của active+completed goals = 100 → UPDATE status=active |
| `UpdateKpiProgressAction` | `kpi_goal_id`, `current_value` | UPDATE current_value → Observer tự recalc achievement_pct |
| `CloseKpiCycleAction` | `employee_id`, `cycle_label` | Validate weight sum = 100 → INSERT kpi_snapshots cho từng goal → UPDATE goals status=completed |

#### Observer: `KpiGoalObserver`

```php
// updated(): nếu current_value thay đổi → recalc achievement_pct
// higher_better: min(current_value / target_value * 100, 150)
// lower_better:  max((2 - current_value / target_value) * 100, 0)
// UPDATE kpi_goals.achievement_pct = calculated_pct
```

#### Queries

| Query | Handler | Filter params |
|---|---|---|
| `ListKpiGoalsQuery` | `ListKpiGoalsHandler` | employee_id, cycle_label, status, goal_type |
| `KpiLeaderboardQuery` | `KpiLeaderboardHandler` | cycle_label, organization_id, department_id |

---

## 9. Luồng nghiệp vụ

### 9.1 Onboarding nhân viên

```
HR tạo Employee (StoreEmployeeAction)
  ├─ Set: job_title_id, department_id, branch_id, manager_id
  ├─ Set: status = 'probation', employment_type = 'probation'
  ├─ Set: probation_end_date, contract_start, salary_base
  ├─ INSERT employee_history (change_type='hire', effective_date=hired_at)
  └─ Khởi tạo leave_balances cho năm hiện tại (per policy đang active)
       → Lookup policy theo job_title_id → department_id → org-level
       → INSERT leave_balance cho từng leave_type có policy active
```

### 9.2 Thay đổi vị trí / lương / phòng ban

```
HR / Manager thực hiện TransferEmployeeAction
  ├─ Nhận: employee_id, new_job_title_id / new_department_id / new_salary_base / new_manager_id
  ├─ Xác định change_type:
  │     job_title thay đổi + level tăng → 'promotion'
  │     job_title thay đổi + level giảm → 'demotion'
  │     department thay đổi             → 'dept_transfer'
  │     branch thay đổi                 → 'branch_transfer'
  │     chỉ salary thay đổi            → 'salary_change'
  │     chỉ manager thay đổi           → 'manager_change'
  ├─ DB::transaction {
  │     UPDATE employees (các trường thay đổi + updated_by)
  │     INSERT employee_history (old_* / new_* pairs, change_type, effective_date, note)
  │   }
  └─ Observer sync snap_* columns
```

### 9.3 Đơn nghỉ phép

```
Employee tạo StoreLeaveRequestAction
  ├─ Lookup leave_balance: (employee_id, leave_type, year hiện tại)
  ├─ Tính days_count server-side (trừ weekend; holiday list TBD)
  ├─ Kiểm tra remaining >= days_count (trừ sick, unpaid)
  ├─ DB::transaction {
  │     INSERT leave_requests (status='pending')
  │     UPDATE leave_balances: pending_days += days_count
  │   }
  └─ Notify manager (Event → Listener → notification)

Manager ApproveLeaveAction:
  DB::transaction {
    UPDATE leave_requests: status = 'approved', approved_by, approved_at
    UPDATE leave_balances: pending_days -= days_count, used_days += days_count
  }

Manager RejectLeaveAction:
  DB::transaction {
    UPDATE leave_requests: status = 'rejected', rejected_reason
    UPDATE leave_balances: pending_days -= days_count   ← hoàn lại
  }

Employee CancelLeaveRequestAction (chỉ pending + date_from >= today):
  DB::transaction {
    UPDATE leave_requests: status = 'cancelled'
    UPDATE leave_balances: pending_days -= days_count
  }
```

### 9.4 KPI Goals — thiết lập và duyệt (Phase 3A)

```
Manager / HR tạo StoreKpiGoalAction cho nhân viên
  ├─ goal_type = 'manual'
  ├─ Nhập: title, target_value, unit, direction, weight_percent, cycle_*
  └─ status = 'draft'

Manager ApproveKpiGoalAction:
  ├─ Tính tổng weight: SELECT SUM(weight_percent) FROM kpi_goals
  │     WHERE employee_id = ? AND cycle_label = ?
  │           AND status IN ('active', 'completed')
  │           AND id != current_goal_id
  │     + current weight_percent
  ├─ Nếu tổng != 100 → từ chối với message rõ ràng
  └─ UPDATE kpi_goals: status = 'active', approved_by, approved_at
```

### 9.5 KPI Goals — cập nhật tiến độ thủ công

```
Employee / Manager UpdateKpiProgressAction
  ├─ UPDATE kpi_goals: current_value = new_value
  └─ KpiGoalObserver::updated():
       achievement_pct = higher_better
           ? min(current_value / target_value * 100, 150)
           : max((2 - current_value / target_value) * 100, 0)
       UPDATE kpi_goals: achievement_pct = calculated
```

### 9.6 KPI — chốt kỳ

```
HR / Manager CloseKpiCycleAction (employee_id, cycle_label)
  ├─ Validate: tổng weight_percent goals active = 100
  ├─ Lấy tất cả goals: status IN ('active') AND cycle_label = ? AND employee_id = ?
  ├─ DB::transaction {
  │     Foreach goal:
  │       INSERT kpi_snapshots {
  │           goal_id, employee_id, cycle_label (denorm),
  │           target_value  = goal.target_value      (freeze),
  │           final_value   = goal.current_value     (freeze),
  │           achievement_pct = goal.achievement_pct (freeze),
  │           weight_percent  = goal.weight_percent  (freeze),
  │           weighted_score  = achievement_pct * weight_percent / 100,
  │           snapped_by, snapped_at = NOW()
  │       }
  │       UPDATE kpi_goals: status = 'completed'
  │
  │     kpi_total = SUM(weighted_score) của tất cả snapshots vừa INSERT
  │     -- kpi_total là số 0-100; /100*5 → thang 5 nếu cần
  │   }
  └─ Log activity: cycle_closed
```

**Ví dụ tính điểm:**

| Mục tiêu | Target | Actual | Achieve% | Weight% | Đóng góp |
|---|---|---|---|---|---|
| Doanh số tháng | 50tr | 52tr | 104% | 30 | 31.2 |
| Tỉ lệ chốt lead | 20% | 18% | 90% | 25 | 22.5 |
| Tài liệu kỹ thuật | 5 docs | 4 | 80% | 20 | 16.0 |
| Onboarding KH | 10 | 11 | 110% → cap 150% | 25 | 27.5 |
| **Tổng** | | | | **100** | **97.2** |

`kpi_score_5 = 97.2 / 100 * 5 = 4.86 / 5.0`

### 9.7 Offboarding

```
HR OffboardEmployeeAction (employee_id, separation_type, effective_date, reason)
  ├─ Kiểm tra: có direct reports (manager_id = employee_id) không?
  │     → Nếu có: từ chối, yêu cầu reassign trước
  ├─ DB::transaction {
  │     UPDATE employees:
  │         status = 'resigned' | 'terminated'
  │         resigned_at = effective_date
  │         resignation_reason = reason
  │     INSERT employee_history (change_type='separation', effective_date, note)
  │     -- Hủy pending leave_requests
  │     Foreach leave_request WHERE employee_id = ? AND status = 'pending':
  │         UPDATE leave_requests: status = 'cancelled'
  │         UPDATE leave_balances: pending_days -= leave_request.days_count
  │   }
  └─ Log activity: employee_offboarded
```

### 9.8 KPI auto-sync (Phase 3B — DEFERRED)

> **Trạng thái:** DEFERRED — chờ Project module đạt stable state
>
> Khi triển khai Phase 3B sẽ bổ sung:
> - `SyncGoalProgressJob` (queued) — tính current_value từ kpi_sources
> - ProjectTask Observer triggers SyncGoalProgressJob
> - `goal_type = 'linked_source'` + bảng `kpi_sources`
> - Endpoint `POST /kpi/goals/:id/sync` (manual trigger)

---

## 10. Query Patterns

> Tất cả query dùng BIGINT JOIN — không dùng partial index syntax, không dùng `FILTER` clause (SQLite không hỗ trợ).

### 10.1 Headcount theo phòng ban

```sql
SELECT
    d.name                                                         AS department,
    SUM(CASE WHEN e.status = 'active'     THEN 1 ELSE 0 END)      AS active,
    SUM(CASE WHEN e.status = 'probation'  THEN 1 ELSE 0 END)      AS probation,
    SUM(CASE WHEN e.employment_type = 'contractor' THEN 1 ELSE 0 END) AS contractors,
    COUNT(e.id)                                                    AS total
FROM departments d
LEFT JOIN employees e
       ON e.department_id = d.id
      AND e.organization_id = :org_id
      AND e.status NOT IN ('resigned', 'terminated')
WHERE d.organization_id = :org_id
GROUP BY d.id, d.name
ORDER BY active DESC;
```

### 10.2 Danh sách nhân viên với vị trí, phòng ban, manager

```sql
SELECT
    e.id,
    e.uuid,
    e.employee_code,
    e.full_name,
    e.status,
    e.employment_type,
    e.hired_at,
    e.work_location,
    e.contract_end,
    jt.name                AS job_title_name,
    jt.level               AS job_level,
    jt.category            AS job_category,
    d.name                 AS department_name,
    b.name                 AS branch_name,
    m.full_name            AS manager_name
FROM employees e
JOIN job_titles    jt ON jt.id = e.job_title_id
JOIN departments    d ON d.id  = e.department_id
JOIN branches       b ON b.id  = e.branch_id
LEFT JOIN employees m ON m.id  = e.manager_id
WHERE e.organization_id = :org_id
  AND e.deleted_at IS NULL
  AND e.status NOT IN ('resigned', 'terminated')
ORDER BY d.name, e.full_name;
```

### 10.3 Balance nghỉ phép nhân viên (không JOIN đơn)

```sql
SELECT
    lb.leave_type,
    lb.entitled_days,
    lb.carried_over,
    lb.adjusted,
    lb.used_days,
    lb.pending_days,
    (lb.entitled_days + lb.carried_over + lb.adjusted
     - lb.used_days - lb.pending_days)             AS remaining_days
FROM leave_balances lb
WHERE lb.employee_id = :employee_id
  AND lb.year        = :year
ORDER BY lb.leave_type;
```

### 10.4 Pending leave requests cho manager

```sql
SELECT
    lr.id,
    lr.uuid,
    lr.leave_type,
    lr.date_from,
    lr.date_to,
    lr.days_count,
    lr.reason,
    e.full_name        AS employee_name,
    e.employee_code,
    d.name             AS department_name
FROM leave_requests lr
JOIN employees e ON e.id = lr.employee_id
JOIN departments d ON d.id = e.department_id
WHERE lr.approved_by = :manager_employee_id
  AND lr.status      = 'pending'
ORDER BY lr.date_from ASC;
```

### 10.5 KPI goals dashboard — tiến độ realtime

```sql
SELECT
    g.id,
    g.uuid,
    g.title,
    g.goal_type,
    g.target_value,
    g.current_value,
    g.unit,
    g.direction,
    g.achievement_pct,
    g.weight_percent,
    g.status,
    g.last_synced_at,
    ROUND(g.achievement_pct * g.weight_percent / 100.0, 2) AS weighted_contribution
FROM kpi_goals g
WHERE g.employee_id  = :employee_id
  AND g.cycle_label  = :cycle_label
  AND g.status NOT IN ('draft', 'cancelled')
ORDER BY g.weight_percent DESC;
```

### 10.6 KPI leaderboard theo cycle

```sql
SELECT
    e.full_name,
    e.employee_code,
    d.name                                  AS department,
    SUM(s.weighted_score)                   AS kpi_raw_score,
    ROUND(SUM(s.weighted_score) / 100.0 * 5, 2) AS kpi_score_5,
    COUNT(s.goal_id)                        AS goal_count
FROM kpi_snapshots s
JOIN employees   e ON e.id = s.employee_id
JOIN departments d ON d.id = e.department_id
WHERE s.cycle_label     = :cycle_label
  AND e.organization_id = :org_id
GROUP BY s.employee_id, e.full_name, e.employee_code, d.name
ORDER BY kpi_raw_score DESC;
```

---

## 11. API Endpoints

### Module Employee

| Method | Endpoint | Mô tả | Quyền |
|---|---|---|---|
| GET | `/employees` | Danh sách (filter: dept, status, type, search) | HR, Manager |
| GET | `/employees/:uuid` | Hồ sơ đầy đủ | HR, Manager, self |
| GET | `/employees/:uuid/history` | Lịch sử thay đổi vị trí/lương | HR, Manager |
| POST | `/employees` | Tạo nhân viên | HR |
| PUT | `/employees/:uuid` | Cập nhật hồ sơ cơ bản | HR |
| PATCH | `/employees/:uuid/salary` | Cập nhật lương / bank (HR-only, EmployeeSalaryData) | HR |
| POST | `/employees/:uuid/transfer` | Thay đổi vị trí / phòng ban / lương | HR |
| POST | `/employees/:uuid/offboard` | Nghỉ việc / chấm dứt HĐ | HR |

### Module Leave

| Method | Endpoint | Mô tả | Quyền |
|---|---|---|---|
| GET | `/leave/policies` | Danh sách policy | HR |
| POST | `/leave/policies` | Tạo policy | HR |
| PUT | `/leave/policies/:id` | Cập nhật policy | HR |
| GET | `/leave/balances/me` | Balance nghỉ phép của tôi | Employee |
| GET | `/employees/:uuid/leave/balances` | Balance nhân viên cụ thể | HR, Manager |
| POST | `/leave/requests` | Đăng ký nghỉ | Employee |
| GET | `/leave/requests/pending` | Đơn chờ tôi duyệt | Manager |
| GET | `/leave/requests` | Lịch sử đơn của tôi | Employee |
| POST | `/leave/requests/:id/approve` | Duyệt đơn | Manager, HR |
| POST | `/leave/requests/:id/reject` | Từ chối đơn | Manager, HR |
| POST | `/leave/requests/:id/cancel` | Tự hủy đơn | Employee |

### Module KpiGoal

| Method | Endpoint | Mô tả | Quyền |
|---|---|---|---|
| GET | `/kpi/goals` | Mục tiêu của tôi theo cycle | Employee |
| GET | `/employees/:uuid/kpi/goals` | KPI nhân viên cụ thể | Manager, HR |
| POST | `/kpi/goals` | Tạo mục tiêu | Manager, HR |
| PUT | `/kpi/goals/:uuid` | Cập nhật (chỉ khi draft/active) | Manager, HR |
| PATCH | `/kpi/goals/:uuid/progress` | Cập nhật current_value (manual) | Employee, Manager |
| POST | `/kpi/goals/:uuid/approve` | Manager duyệt mục tiêu | Manager, HR |
| POST | `/kpi/cycles/:label/close` | Chốt kỳ — tạo snapshot | HR |
| GET | `/kpi/snapshots` | Lịch sử điểm KPI các kỳ | Employee, Manager, HR |
| GET | `/kpi/leaderboard` | Bảng xếp hạng KPI cycle | Manager, HR |

---

## 12. Indexes

```sql
-- employees: active theo department (query phổ biến nhất)
CREATE INDEX idx_employees_org_dept_status
    ON employees (organization_id, department_id, status);

-- employees: contract sắp hết hạn (cron job hàng ngày)
CREATE INDEX idx_employees_contract_alert
    ON employees (organization_id, contract_end, status);

-- employees: direct reports lookup
CREATE INDEX idx_employees_manager
    ON employees (manager_id);

-- employee_history: lịch sử theo nhân viên
CREATE INDEX idx_employee_history_emp_date
    ON employee_history (employee_id, effective_date);

-- leave_policies: lookup theo org + type
CREATE INDEX idx_leave_policies_org_type
    ON leave_policies (organization_id, leave_type, is_active);

-- leave_balances: lookup theo employee + năm
CREATE INDEX idx_leave_balances_emp_year
    ON leave_balances (employee_id, year, leave_type);

-- leave_requests: đơn theo nhân viên + status
CREATE INDEX idx_leave_requests_emp_status_date
    ON leave_requests (employee_id, status, date_from);

-- leave_requests: pending theo manager
CREATE INDEX idx_leave_requests_approver_status
    ON leave_requests (approved_by, status);

-- kpi_goals: goal theo nhân viên + cycle + status
CREATE INDEX idx_kpi_goals_emp_cycle_status
    ON kpi_goals (employee_id, cycle_label, status);

-- kpi_goals: cycle closing lookup
CREATE INDEX idx_kpi_goals_org_cycle_end
    ON kpi_goals (organization_id, cycle_end, status);

-- kpi_snapshots: leaderboard theo cycle
CREATE INDEX idx_kpi_snapshots_cycle_emp
    ON kpi_snapshots (cycle_label, employee_id);
```

### Cache strategy

| Cache key | TTL | Invalidate khi |
|---|---|---|
| `org:{id}:headcount` | 10 phút | Thay đổi status/department nhân viên |
| `emp:{id}:leave_balance:{year}` | Xóa ngay | Mỗi UPDATE leave_balances |
| `emp:{id}:kpi:{cycle}` | 5 phút | Sau mỗi UpdateKpiProgressAction |
| `org:{id}:kpi_leaderboard:{cycle}` | 15 phút | Sau mỗi CloseKpiCycleAction |
| `org:{id}:contract_alerts` | 60 phút | Sau mỗi cron job contract alert |

---

## 13. Lộ trình triển khai

### Phase 1A — Employee Enhancement (tuần 1) ✅ DONE

> Extends module `Employee` đã có

- [x] Migration: `alter_job_titles_add_salary_band` (salary_min/max/currency, is_manager_role)
- [x] Migration: `alter_employees_add_contract_salary_personal_fields` (idempotent — cột đã có từ extension migrations)
- [x] Migration: `alter_employee_history_add_salary_columns` (old/new salary_base)
- [x] Cập nhật Enum `EmployeeStatus` (thêm `Probation`)
- [x] Cập nhật Enum `EmploymentType` (thêm `Probation`, đổi `Contract` → `Contractor`)
- [x] Tạo mới Enum `EmployeeHistoryChangeType` (full 12 change types incl. `SalaryChange`, `Separation`)
- [x] Thêm `TransferEmployeeAction` + `TransferEmployeeData`
- [x] Thêm `OffboardEmployeeAction` + `OffboardEmployeeData`
- [x] Thêm `EmployeeSalaryData` với HR-only policy guard
- [x] Cập nhật `EmployeeObserver`: track salary_base, skipHistoryTracking flag, fix resolveChangeType
- [x] Cập nhật `Employee` model: fillable mới + `$skipHistoryTracking` flag + scopeWorking + hasDirectReports
- [x] Cập nhật `EmployeeHistory` model: fillable thêm old/new salary_base
- [x] Cập nhật `EmployeePolicy`: thêm transfer, offboard, viewSalary, updateSalary
- [x] Cập nhật `StoreEmployeeAction`, `UpdateEmployeeAction`: thêm new fields
- [x] Cập nhật `StoreEmployeeData`, `UpdateEmployeeData`: thêm new fields + validation

### Phase 1B — Employee UI (tuần 2) ✅ DONE

- [x] Form nhân viên: 4 tabs (Vị trí & Tổ chức / Hồ sơ cá nhân / Hợp đồng & Lương / Liên hệ & Ghi chú) — cả create và edit. Lương/bank/notes ẩn theo HR role.
- [x] Stat cards: 6 thẻ (Tổng / Đang làm / Thử việc / Nghỉ phép / Hợp đồng / Đã nghỉ) — EmployeeController truyền thêm totalProbation, totalContractor
- [x] Danh sách cảnh báo hợp đồng: collapsible panel trong index, badge 30/90 ngày, link tới show
- [x] View lịch sử nhân viên: timeline với dot + line, label tiếng Việt per change_type, hiển thị salary diff, show.blade.php refactor toàn bộ (thêm contract section, bank HR-only, ghi chú HR, emergency contact, contract_end alert)

### Phase 2 — Leave Management (tuần 3–4) ✅ DONE

> New module: `php artisan module:make Leave`

- [x] Migration: `create_leave_policies_table` (organization_id + softDeletes + unique scope index)
- [x] Migration: `create_leave_balances_table` (organization_id + softDeletes)
- [x] Migration: `create_leave_requests_table` (softDeletes, FK approved_by → employees)
- [x] Models: `LeavePolicy`, `LeaveBalance` (getRemainingDaysAttribute), `LeaveRequest` (extends TenantAwareModel)
- [x] Enums: `LeaveType` (8 values + label() + noBalanceCheck()), `LeaveRequestStatus` (badgeClass())
- [x] Actions: `StoreLeavePolicyAction`, `UpdateLeavePolicyAction`
- [x] Actions: `StoreLeaveRequestAction` (atomic + server-side days calc + balance check), `ApproveLeaveAction`, `RejectLeaveAction`, `CancelLeaveRequestAction`
- [x] Queries: `ListLeaveRequestsQuery/Handler`, `ListPendingApprovalQuery/Handler`
- [x] Observer: `LeaveRequestObserver` (log approved/rejected/cancelled)
- [x] Policies: `LeavePolicyPolicy`, `LeaveRequestPolicy`
- [x] Controllers: `LeavePolicyController`, `LeaveRequestController`, `LeaveBalanceController`
- [x] Routes: 16 routes (policies CRUD, requests CRUD + approve/reject/cancel, balances/me + employee)
- [x] Views: Policy index/create/edit, Request index/create/show/pending, Balance me/employee
- [x] Sidebar: Entry "Nghỉ phép" trong phần Tổ chức
- [x] Deviated from spec: `leave_balances` thêm `organization_id` để TenantAwareModel hoạt động

### Phase 3A — KPI Goals Manual (tuần 5–6) ✅ DONE

> New module: `php artisan module:make KpiGoal`

- [x] Migration: `create_kpi_goals_table` (self-ref parent_goal_id, softDeletes, 2 indexes)
- [x] Migration: `create_kpi_snapshots_table` (unique goal_id, no timestamps, immutable guard)
- [x] Models: `KpiGoal` (TenantAwareModel + Observer + helpers), `KpiSnapshot` (plain Model, immutable save()/delete())
- [x] Enums: `KpiGoalType`, `KpiGoalStatus` (weightedStatuses()), `KpiDirection` (calcAchievement() method)
- [x] Actions: `StoreKpiGoalAction`, `UpdateKpiGoalAction` (guard editable), `ApproveKpiGoalAction` (weight sum = 100), `UpdateKpiProgressAction`, `CloseKpiCycleAction` (transaction + DB::table insert + kpi_total_score backfill)
- [x] Observer: `KpiGoalObserver` (updated() → recalc achievement_pct via KpiDirection::calcAchievement, updateQuietly để tránh loop)
- [x] Queries: `ListKpiGoalsQuery/Handler`, `KpiLeaderboardQuery/Handler` (raw SQL SUM + GROUP BY)
- [x] Policy: `KpiGoalPolicy` (HR/Manager/CEO create; updateProgress = owner or manager; closeCycle = HR/CEO)
- [x] Controllers: `KpiGoalController` (index/create/store/show/edit/update/approve/updateProgress/closeCycle/leaderboard)
- [x] Routes: 10 routes
- [x] Views: goals/index, create, edit, show (inline progress modal Alpine.js), leaderboard (rank với medal emoji)
- [x] Sidebar: Entry "KPI Goals" trong phần Tổ chức
- [x] KpiSnapshot immutable: override save()/delete() throw RuntimeException

### Phase 3B — KPI Auto-sync (tuần 7+, DEFERRED)

> Phụ thuộc vào Project module đạt stable state

- [ ] Migration: `create_kpi_sources_table`
- [ ] Model: `KpiSource`
- [ ] Enum: `KpiAggregationType`, `KpiSourceType`, `KpiDateRangeType`
- [ ] Job: `SyncGoalProgressJob` (queued)
- [ ] Integration: ProjectTask Observer → dispatch SyncGoalProgressJob
- [ ] Endpoint: `POST /kpi/goals/:uuid/sync` (manual trigger)
- [ ] Cập nhật `goal_type = 'linked_source'` flow trong `StoreKpiGoalAction`

---

*Version 2.0.0 — Workforce Center (system-aligned)*
*Stack: Laravel 13 · PHP 8.4 · SQLite dev / configurable prod*
*NWIDART Modules · AVSA+CQRS-lite · TenantAwareModel · BIGINT PK*
