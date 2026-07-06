# Đặc Tả Module: Công Việc (Task Center)

> **Hệ thống:** SaaS SME
> **Module:** Task Center — thuộc Module Dự án & Công việc
**Architecture**: Advanced Vertical Slice (AVSA) + CQRS-lite + Laravel Modules + Laravel Actions
> **Phiên bản:** 1.2.0
> **Ngày cập nhật:** 2026-06-09
> **Stack:** Laravel 13 · Alpine.js · SQLite (dev) / MySQL 8+ / PostgreSQL 15+
> **Liên module:** Project (upstream — nguồn gốc task), Employee/Workforce (assignee → employees)
> **Tech stack**:
- spatie/laravel-data (DTO + validate)
- lorisleiva/laravel-actions (mỗi use-case = 1 Action)
- nwidart/laravel-modules
- rap2hpoutre/fast-excel (export)
- spatie/laravel-activitylog (log thay đổi definition)
---

## Mục lục

1. [Tổng quan & định vị](#1-tổng-quan--định-vị)
2. [Phạm vi](#2-phạm-vi)
3. [Kiến trúc tổng thể](#3-kiến-trúc-tổng-thể)
4. [Enum Values](#4-enum-values)
5. [ERD — Quan hệ bảng](#5-erd--quan-hệ-bảng)
6. [Đặc tả bảng dữ liệu](#6-đặc-tả-bảng-dữ-liệu)
7. [Luồng nghiệp vụ](#7-luồng-nghiệp-vụ)
8. [Tính tiến độ dự án từ task](#8-tính-tiến-độ-dự-án-từ-task)
9. [Query Patterns](#9-query-patterns)
10. [API Endpoints](#10-api-endpoints)
11. [Business Rules](#11-business-rules)
12. [Indexes](#12-indexes)
13. [Lộ trình triển khai](#13-lộ-trình-triển-khai)

---

## 1. Tổng quan & định vị

**Task Center** là sub-module quản lý công việc thuộc **Module Dự án & Công việc**. Task luôn thuộc về một project; tiến độ project được tính ngược lên từ trạng thái task.

### Quan hệ Project ↔ Task

```
PROJECT (module sẵn có — Modules/Project)
    │  id: BIGINT UNSIGNED
    │
    └─1:N──► TASK (module này)
                  │
                  └─ self-ref (parent_id)
                       Epic(0) → Story(1) → Task(2) → Subtask(3)
```

**Project không tự quản lý tiến độ.** `projects.progress_pct` là trường denormalized sẽ được thêm vào bảng `projects` qua extension migration khi triển khai Task module.

> **Chú ý tích hợp:** Project model hiện tại (`Modules/Project/app/Models/Project.php`) chưa có
> `progress_pct`, `task_total`, `task_done`. Cần thêm qua extension migration `database/migrations/extensions/`.

### Quy ước khoá chính (khớp với Project module)

- **PK:** `id` BIGINT UNSIGNED AUTO_INCREMENT
- **UUID công khai:** cột `uuid CHAR(36)` riêng biệt (dùng trong URL, API response)
- **FK nội bộ:** `UNSIGNED BIGINT` khớp với `id` của bảng tham chiếu
- **Soft delete:** `deleted_at TIMESTAMP NULL` (qua `TenantAwareModel`)
- **Sắp xếp:** `sort_order INT UNSIGNED` — mỗi task mới nhận `MAX(sort_order)+1` trong scope (project_id, parent_id)

### Tenant isolation

`Task` extends `TenantAwareModel` — tự động gắn `organization_id` khi tạo, tự động filter theo org qua global `OrganizationScope`. Mọi query đều scoped theo tenant mà không cần gọi thủ công.

### Nguyên tắc: Không lưu JSON trong bảng Task

Toàn bộ module **cấm dùng cột kiểu JSON** (bao gồm cả `TEXT` dùng để chứa JSON). Lý do: JSON không thể index, không thể JOIN, không thể filter hiệu quả, gây khó khăn cho migration khi schema thay đổi.

| Nhu cầu | Không làm | Làm đúng |
|---|---|---|
| Lưu nhiều giá trị cũ/mới khi audit | `old_value = '[1,2,3]'` (JSON trong TEXT) | Bảng riêng `task_label_histories` |
| Lưu @mention trong comment | `metadata = {"mentions":[1,2]}` (JSON column) | Bảng riêng `task_comment_mentions` |
| Metadata tùy chọn của attachment | `custom_properties` trong Spatie media | Cột rõ ràng trong `tasks` hoặc bảng riêng |
| Lưu cấu hình task phức tạp | `settings JSON` | Tách thành các cột riêng biệt |

---

## 2. Phạm vi

### Trong phạm vi

- Hierarchy 4 cấp: **Epic → Story → Task → Subtask** qua `parent_id` self-ref
- `project_id` denormalized trực tiếp trên **mọi cấp** task — tránh CTE đệ quy khi query
- List / Table view với filter, sort, group by (Tabulator — khớp chuẩn `ListProjectsHandler`)
- Gán assignee (`employee_id`), priority, due date, story points, estimated hours
- Label / tag tự do per project
- Comment thread (reply, @mention — lưu mentions vào bảng riêng)
- File attachment tích hợp **MediaUploadService + FilePond** (không dùng bảng custom)
- Time log (giờ làm việc, billable/non-billable)
- Watcher: theo dõi thông báo
- Audit trail: lịch sử thay đổi từng trường — scalar values only
- Tiến độ dự án tự động từ % task done (chỉ tính leaf task có `is_leaf = TRUE`)

### Ngoài phạm vi

- Kanban board — phase sau
- Sprint planning — phase sau
- Gantt chart — phase sau
- Notification engine — module riêng

---

## 3. Kiến trúc tổng thể

```
projects (existing — Modules/Project)
    │ id: BIGINT UNSIGNED
    │
    ▼
tasks (core — self-ref hierarchy, extends TenantAwareModel)
    │
    ├─ task_label_maps ◄──► task_labels       (N:M — tag tự do per project)
    ├─ task_comments                           (thread với reply, self-ref)
    │   └─ task_comment_mentions              (bảng riêng — không dùng JSON)
    ├─ [Media via Spatie MediaLibrary]         (attachments — collection 'attachments')
    ├─ task_watchers                           (người theo dõi)
    ├─ time_logs                               (nhật ký giờ làm)
    └─ task_histories                          (audit trail — immutable, scalar only)
        └─ task_label_histories                (riêng cho thay đổi labels — N:M)

TaskObserver::updated()
    → Khi isDirty('status'):
        UpdateTaskProgressJob::dispatch(parent_id)    ← bubble up (withoutOverlapping)
        UpdateProjectProgressJob::dispatch(project_id) ← idempotent (withoutOverlapping)
    → Khi isDirty('parent_id'):
        RecalcTaskDepthJob::dispatch(task_id)         ← tái tính depth descendants
```

### Kiến trúc module (NWIDART — khớp Modules/Project)

```
Modules/Task/
├── app/
│   ├── Actions/Backend/
│   │   ├── StoreTaskAction.php
│   │   ├── UpdateTaskAction.php
│   │   ├── MoveTaskAction.php          ← đổi parent_id + tái tính depth
│   │   └── DestroyTaskAction.php
│   ├── Data/Requests/
│   │   ├── StoreTaskData.php
│   │   └── UpdateTaskData.php
│   ├── Enums/
│   │   ├── TaskType.php
│   │   ├── TaskStatus.php
│   │   └── TaskPriority.php
│   ├── Http/Controllers/
│   │   ├── TaskController.php
│   │   └── Api/TaskApiController.php
│   ├── Jobs/
│   │   ├── UpdateTaskProgressJob.php    (ShouldQueue, withoutOverlapping)
│   │   ├── UpdateProjectProgressJob.php (ShouldQueue, withoutOverlapping)
│   │   └── RecalcTaskDepthJob.php       (ShouldQueue — tái tính depth khi move)
│   ├── Models/
│   │   ├── Task.php
│   │   ├── TaskLabel.php
│   │   ├── TaskComment.php
│   │   ├── TaskCommentMention.php
│   │   ├── TaskWatcher.php
│   │   ├── TimeLog.php
│   │   ├── TaskHistory.php
│   │   └── TaskLabelHistory.php
│   ├── Observers/
│   │   ├── TaskObserver.php
│   │   └── TimeLogObserver.php          ← sync logged_hours (create/update/delete)
│   ├── Policies/TaskPolicy.php
│   └── Queries/
│       ├── ListTasksQuery.php
│       └── ListTasksHandler.php
├── database/migrations/
└── routes/
    ├── web.php
    └── api.php
```

---

## 4. Enum Values

### TaskType (string VARCHAR 20)

| Giá trị | Biểu tượng | Mô tả |
|---|---|---|
| `epic` | Tia sét | Nhóm lớn, chứa nhiều story |
| `story` | Bookmark | User story / feature |
| `task` | Checkbox | Công việc thực hiện |
| `subtask` | Dấu cộng nhỏ | Công việc con |
| `bug` | Bọ | Lỗi cần xử lý |
| `improvement` | Mũi tên lên | Cải tiến tính năng |

### TaskStatus (string VARCHAR 20)

| Giá trị | Mô tả | Tính vào done? |
|---|---|---|
| `backlog` | Chưa lên kế hoạch | Không |
| `todo` | Đã lên kế hoạch | Không |
| `in_progress` | Đang thực hiện | Không |
| `in_review` | Đang review | Không |
| `done` | Hoàn thành | **Có** |
| `cancelled` | Đã hủy | Không — loại khỏi denominator |
| `blocked` | Bị chặn | Không |

### TaskPriority (string VARCHAR 10)

Khớp với `ProjectPriority` enum hiện có:

| Giá trị | Màu | Thứ tự sắp xếp |
|---|---|---|
| `critical` | Đỏ đậm | 1 |
| `high` | Đỏ | 2 |
| `medium` | Vàng | 3 |
| `low` | Xanh | 4 |
| `none` | Xám | 5 |

---

## 5. ERD — Quan hệ bảng

```
[projects] (existing — id: BIGINT UNSIGNED)
    │
    │ project_id UNSIGNED BIGINT (FK → projects.id)
    ▼
tasks (self-ref — id: BIGINT UNSIGNED)
    ├─ organization_id  → organizations.id  (tenant scope)
    ├─ parent_id        → tasks.id (nullable)
    ├─ employee_id      → employees.id (nullable)
    ├─ created_by       → users.id
    ├─ updated_by       → users.id (nullable)
    │
    ├─ N:M ──► task_labels         (qua task_label_maps)
    ├─ 1:N ──► task_comments
    │               └─ 1:N → task_comment_mentions
    ├─ 1:N ──► [media]             (Spatie MediaLibrary — collection 'attachments')
    ├─ 1:N ──► task_watchers
    ├─ 1:N ──► time_logs
    ├─ 1:N ──► task_histories      (scalar fields only)
    └─ 1:N ──► task_label_histories (N:M changes)

time_logs.project_id      → projects.id       (denormalized)
time_logs.organization_id → organizations.id  (tenant scope)
```

### Quan hệ tổng hợp

| Bảng A | Quan hệ | Bảng B | Ghi chú |
|---|---|---|---|
| projects | 1:N | tasks | project_id BIGINT UNSIGNED |
| tasks | N:1 | tasks (self) | parent_id — max depth 3 |
| tasks | N:M | task_labels | qua task_label_maps |
| tasks | 1:N | task_comments | |
| task_comments | N:1 | task_comments | reply thread |
| task_comments | 1:N | task_comment_mentions | @mention targets |
| tasks | 1:N | [media] | collection='attachments' |
| tasks | 1:N | task_watchers | |
| tasks | 1:N | time_logs | |
| tasks | 1:N | task_histories | scalar audit only |
| tasks | 1:N | task_label_histories | label add/remove audit |

---

## 6. Đặc tả bảng dữ liệu

> **Quy ước FK:**
> - `organizations`, `users`, `employees`, `projects`: `UNSIGNED BIGINT`
> - PK bảng nội bộ: `BIGINT UNSIGNED AUTO_INCREMENT` + `uuid CHAR(36) UNIQUE`
> - Soft delete: bảng chính có `deleted_at TIMESTAMP NULL`
> - Migrations: `Modules/Task/database/migrations/`

---

### 6.1 tasks — Công việc (core table)

| Trường | Kiểu | Null | Default | Mô tả |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | auto_increment | PK |
| `uuid` | CHAR(36) | NOT NULL | | UUID công khai (URL, API) |
| `organization_id` | BIGINT UNSIGNED | NOT NULL | | FK → organizations.id |
| `project_id` | BIGINT UNSIGNED | NOT NULL | | FK → projects.id |
| `parent_id` | BIGINT UNSIGNED | NULL | NULL | FK self. NULL = top-level |
| `employee_id` | BIGINT UNSIGNED | NULL | NULL | FK → employees.id |
| `title` | VARCHAR(500) | NOT NULL | | |
| `description` | TEXT | NULL | NULL | Markdown — plain text only, không nhúng JSON |
| `task_type` | VARCHAR(20) | NOT NULL | `task` | epic\|story\|task\|subtask\|bug\|improvement |
| `status` | VARCHAR(20) | NOT NULL | `todo` | Xem mục 4 |
| `priority` | VARCHAR(10) | NOT NULL | `medium` | critical\|high\|medium\|low\|none |
| `story_points` | TINYINT UNSIGNED | NULL | NULL | Fibonacci: 1,2,3,5,8,13,21 |
| `start_date` | DATE | NULL | NULL | |
| `due_date` | DATE | NULL | NULL | |
| `completed_at` | TIMESTAMP | NULL | NULL | Set khi status→done, clear khi rời done |
| `estimated_hours` | DECIMAL(6,2) | NULL | NULL | Ước tính giờ |
| `logged_hours` | DECIMAL(8,2) | NOT NULL | 0 | Tổng giờ đã log — sync bởi TimeLogObserver |
| `progress_pct` | TINYINT UNSIGNED | NOT NULL | 0 | 0–100 — cập nhật bởi UpdateTaskProgressJob |
| `is_leaf` | BOOLEAN | NOT NULL | TRUE | FALSE khi có ≥1 child chưa bị soft delete |
| `subtask_total` | SMALLINT UNSIGNED | NOT NULL | 0 | Số direct children active (không bị soft delete) |
| `subtask_done` | SMALLINT UNSIGNED | NOT NULL | 0 | Số direct children có status=done |
| `comment_count` | SMALLINT UNSIGNED | NOT NULL | 0 | Sync bởi TaskCommentObserver |
| `attachment_count` | SMALLINT UNSIGNED | NOT NULL | 0 | Sync sau reassociateFilePondDrafts |
| `sort_order` | INT UNSIGNED | NOT NULL | 0 | `MAX(sort_order)+1` trong (project_id, parent_id) |
| `depth` | TINYINT UNSIGNED | NOT NULL | 0 | 0=Epic, 1=Story, 2=Task, 3=Subtask |
| `is_archived` | BOOLEAN | NOT NULL | FALSE | Archive ≠ delete: vẫn tồn tại, ẩn khỏi list active |
| `created_by` | BIGINT UNSIGNED | NOT NULL | | FK → users.id |
| `updated_by` | BIGINT UNSIGNED | NULL | NULL | FK → users.id |
| `created_at` | TIMESTAMP | NOT NULL | | |
| `updated_at` | TIMESTAMP | NOT NULL | | |
| `deleted_at` | TIMESTAMP | NULL | NULL | Soft delete (TenantAwareModel) |

**Phân biệt `is_archived` vs `deleted_at`:**

| | `is_archived = TRUE` | `deleted_at IS NOT NULL` |
|---|---|---|
| Hiển thị trong list active | Không | Không |
| Hiển thị trong archive view | **Có** | Không |
| Tính vào progress | Không | Không |
| Có thể khôi phục | Có (unarchive) | Có (restore) |
| Ý nghĩa | Đóng băng / hoàn thành cũ | Xóa / không cần nữa |

**Counter sync rules (không dùng read-modify-write — dùng atomic DB increment):**

```php
// Đúng: atomic increment qua DB
DB::table('tasks')->where('id', $parentId)->increment('subtask_total');
DB::table('tasks')->where('id', $taskId)->increment('comment_count');

// Sai: read-modify-write (race condition)
$task->subtask_total = $task->subtask_total + 1;
$task->save();
```

---

### 6.2 task_labels — Nhãn task

| Trường | Kiểu | Null | Default | Mô tả |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | auto_increment | PK |
| `uuid` | CHAR(36) | NOT NULL | | |
| `organization_id` | BIGINT UNSIGNED | NOT NULL | | Tenant scope |
| `project_id` | BIGINT UNSIGNED | NOT NULL | | FK → projects.id |
| `name` | VARCHAR(80) | NOT NULL | | |
| `color_hex` | CHAR(7) | NOT NULL | `#B4B2A9` | |
| `description` | VARCHAR(200) | NULL | NULL | |
| `created_by` | BIGINT UNSIGNED | NOT NULL | | FK → users.id |
| `created_at` | TIMESTAMP | NOT NULL | | |
| `deleted_at` | TIMESTAMP | NULL | NULL | |

```php
$table->unique(['project_id', 'name'], 'uq_task_label_name');
$table->index('project_id');
```

---

### 6.3 task_label_maps — Pivot task ↔ label

| Trường | Kiểu | Mô tả |
|---|---|---|
| `task_id` | BIGINT UNSIGNED | PK, FK → tasks.id, CASCADE DELETE |
| `label_id` | BIGINT UNSIGNED | PK, FK → task_labels.id, CASCADE DELETE |
| `created_at` | TIMESTAMP | NOT NULL — dùng cho audit |

```php
$table->primary(['task_id', 'label_id']);
$table->index('label_id', 'idx_label_map_label');
```

---

### 6.4 task_comments — Bình luận

| Trường | Kiểu | Null | Default | Mô tả |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | auto_increment | PK |
| `uuid` | CHAR(36) | NOT NULL | | |
| `task_id` | BIGINT UNSIGNED | NOT NULL | | FK → tasks.id, CASCADE DELETE |
| `user_id` | BIGINT UNSIGNED | NOT NULL | | FK → users.id |
| `parent_id` | BIGINT UNSIGNED | NULL | NULL | FK self — reply. NULL = top-level |
| `content` | TEXT | NOT NULL | | Markdown — plain text, không nhúng JSON |
| `is_edited` | BOOLEAN | NOT NULL | FALSE | |
| `created_at` | TIMESTAMP | NOT NULL | | |
| `updated_at` | TIMESTAMP | NOT NULL | | |
| `deleted_at` | TIMESTAMP | NULL | NULL | Soft delete — giữ thread structure |

```php
$table->index(['task_id', 'created_at'], 'idx_comment_task');
$table->index('parent_id', 'idx_comment_parent');
```

---

### 6.5 task_comment_mentions — @mention targets (thay thế JSON)

Thay vì lưu `mentions = [1, 3, 7]` vào JSON trong `task_comments`, mỗi @mention tạo 1 row riêng — có thể index, JOIN, query.

| Trường | Kiểu | Null | Mô tả |
|---|---|---|---|
| `comment_id` | BIGINT UNSIGNED | NOT NULL | PK, FK → task_comments.id, CASCADE DELETE |
| `user_id` | BIGINT UNSIGNED | NOT NULL | PK, FK → users.id — người được mention |
| `created_at` | TIMESTAMP | NOT NULL | |

```php
$table->primary(['comment_id', 'user_id']);
$table->index('user_id', 'idx_mention_user');
// Cho phép query: "tôi được mention ở đâu?"
// SELECT * FROM task_comment_mentions WHERE user_id = :me
```

---

### 6.6 Attachment — Tích hợp Spatie MediaLibrary

**Không có bảng `task_attachments` riêng.** Task sử dụng hệ thống media hiện có:

```php
class Task extends TenantAwareModel implements HasMedia
{
    use HasTenantMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->acceptsMimeTypes(['application/pdf', 'image/*', 'application/zip', ...]);
        // Không dùng custom_properties của Spatie để lưu metadata nghiệp vụ
        // Nếu cần thêm metadata (e.g. is_shared, category): dùng bảng riêng
    }
}
```

**Luồng upload:**
```
1. FilePond → POST /api/v1/media/upload (collection='attachments')
2. File lưu vào filepond_drafts
3. Form submit → uuids[]
4. MediaUploadService::reassociateFilePondDrafts($task, $uuids, 'attachments')
5. TimeLogObserver sau khi reassociate: DB::increment('attachment_count')
```

---

### 6.7 task_watchers — Người theo dõi

| Trường | Kiểu | Mô tả |
|---|---|---|
| `task_id` | BIGINT UNSIGNED | PK, FK → tasks.id, CASCADE DELETE |
| `user_id` | BIGINT UNSIGNED | PK, FK → users.id |
| `watched_at` | TIMESTAMP | NOT NULL |

```php
$table->primary(['task_id', 'user_id']);
$table->index('user_id', 'idx_watcher_user');
```

---

### 6.8 time_logs — Nhật ký giờ làm việc

| Trường | Kiểu | Null | Default | Mô tả |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | auto_increment | PK |
| `uuid` | CHAR(36) | NOT NULL | | |
| `organization_id` | BIGINT UNSIGNED | NOT NULL | | Tenant scope |
| `task_id` | BIGINT UNSIGNED | NOT NULL | | FK → tasks.id |
| `project_id` | BIGINT UNSIGNED | NOT NULL | | Denormalized — báo cáo không JOIN qua tasks |
| `employee_id` | BIGINT UNSIGNED | NOT NULL | | FK → employees.id |
| `hours` | DECIMAL(6,2) | NOT NULL | | (0, 24] — validate trong StoreTimeLogData |
| `log_date` | DATE | NOT NULL | | ≤ today() |
| `description` | VARCHAR(500) | NULL | NULL | **VARCHAR 500 không phải TEXT** — không cần full-text |
| `is_billable` | BOOLEAN | NOT NULL | TRUE | |
| `created_at` | TIMESTAMP | NOT NULL | | |
| `updated_at` | TIMESTAMP | NOT NULL | | |
| `deleted_at` | TIMESTAMP | NULL | NULL | |

```php
$table->index(['task_id', 'log_date'], 'idx_timelog_task');
$table->index(['project_id', 'log_date', 'employee_id'], 'idx_timelog_project');
$table->index(['employee_id', 'log_date'], 'idx_timelog_employee');
$table->index('organization_id');
```

**`TimeLogObserver` — sync `tasks.logged_hours` (atomic, xử lý cả 3 sự kiện):**

```php
class TimeLogObserver
{
    public function created(TimeLog $log): void
    {
        DB::table('tasks')->where('id', $log->task_id)
            ->increment('logged_hours', $log->hours);
    }

    public function updated(TimeLog $log): void
    {
        $diff = $log->hours - $log->getOriginal('hours');
        if ($diff == 0) return;
        if ($diff > 0) {
            DB::table('tasks')->where('id', $log->task_id)->increment('logged_hours', $diff);
        } else {
            DB::table('tasks')->where('id', $log->task_id)->decrement('logged_hours', abs($diff));
        }
    }

    public function deleted(TimeLog $log): void
    {
        DB::table('tasks')->where('id', $log->task_id)
            ->decrement('logged_hours', $log->hours);
    }
}
```

---

### 6.9 task_histories — Lịch sử thay đổi scalar (immutable)

Chỉ INSERT — không UPDATE, không DELETE. Chỉ dùng cho **scalar fields** (1 field = 1 giá trị cũ + 1 giá trị mới). Fields phức tạp (labels) dùng `task_label_histories`.

| Trường | Kiểu | Null | Mô tả |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK |
| `task_id` | BIGINT UNSIGNED | NOT NULL | FK → tasks.id |
| `actor_id` | BIGINT UNSIGNED | NOT NULL | FK → users.id |
| `field_changed` | VARCHAR(60) | NOT NULL | status\|employee_id\|priority\|due_date\|title\|description\|parent_id |
| `old_value` | VARCHAR(500) | NULL | Scalar string — không bao giờ là JSON |
| `new_value` | VARCHAR(500) | NULL | Scalar string — không bao giờ là JSON |
| `changed_at` | TIMESTAMP | NOT NULL | |

> **Giới hạn `old_value`/`new_value` = VARCHAR(500)** (không phải TEXT): ép buộc chỉ lưu scalar, không thể nhét JSON dài. `description` change chỉ log `"changed"` (không log nội dung đầy đủ).

```php
// Index tối ưu cho cả 2 use case:
// 1. "Lịch sử task X" → task_id, changed_at DESC
// 2. "Lịch sử status của task X" → task_id, field_changed, changed_at DESC
$table->index(['task_id', 'field_changed', 'changed_at'], 'idx_history_task_field');
```

---

### 6.10 task_label_histories — Lịch sử thay đổi labels (immutable)

Labels là N:M — không thể dùng `task_histories` với old/new scalar. Bảng riêng, mỗi label add/remove = 1 row.

| Trường | Kiểu | Null | Mô tả |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK |
| `task_id` | BIGINT UNSIGNED | NOT NULL | FK → tasks.id |
| `label_id` | BIGINT UNSIGNED | NOT NULL | FK → task_labels.id |
| `actor_id` | BIGINT UNSIGNED | NOT NULL | FK → users.id |
| `action` | VARCHAR(10) | NOT NULL | `added` \| `removed` |
| `changed_at` | TIMESTAMP | NOT NULL | |

```php
$table->index(['task_id', 'changed_at'], 'idx_lhist_task');
```

---

### 6.11 Extension migration cho bảng projects

```php
// database/migrations/extensions/YYYY_MM_DD_add_task_progress_to_projects_table.php
Schema::table('projects', function (Blueprint $table) {
    $table->unsignedSmallInteger('progress_pct')->default(0)->after('completed_at');
    $table->unsignedSmallInteger('task_total')->default(0)->after('progress_pct');
    $table->unsignedSmallInteger('task_done')->default(0)->after('task_total');
});
```

---

## 7. Luồng nghiệp vụ

### 7.1 Tạo task

```
StoreTaskAction::handle(StoreTaskData $data)
  ├─ organization_id: từ TenantContext (auto)
  ├─ Nếu có parent_id:
  │   Validate: parent.project_id = data.project_id
  │   Validate: parent.depth < 3
  │   depth = parent.depth + 1
  │   project_id = parent.project_id (override từ route — không nhận từ client)
  │   DB::increment('subtask_total') trên parent
  │   DB::update('is_leaf', FALSE) trên parent
  ├─ sort_order = MAX(sort_order)+1 trong (project_id, parent_id)
  ├─ created_by = auth()->id()
  └─ INSERT task_histories (field_changed='created', old_value=NULL, new_value=task.uuid)
```

### 7.2 Thay đổi status

```
UpdateTaskAction / PATCH status
  ├─ INSERT task_histories (field='status', old=old_status, new=new_status)
  ├─ new_status='done'   → completed_at = NOW()
  ├─ new_status!='done' AND old='done' → completed_at = NULL
  ├─ Nếu is_leaf=TRUE:
  │   parent_id != NULL → UpdateTaskProgressJob::dispatch(parent_id)
  │   UpdateProjectProgressJob::dispatch(project_id)
  └─ Nếu parent_id != NULL (dù không phải leaf):
      Sync subtask_done của parent qua atomic:
      DB::table('tasks')->where('id', parent_id)
        ->update(['subtask_done' => Task::where('parent_id', parent_id)
                                       ->where('status', 'done')->count()])
```

### 7.3 Di chuyển task (đổi parent_id)

```
MoveTaskAction::handle(Task $task, ?int $newParentId)
  ├─ Validate newParent.project_id = task.project_id
  ├─ Validate newParent.depth < 3 (nếu có parent mới)
  │
  ├─ Cập nhật parent CŨ (nếu có):
  │   DB::decrement('subtask_total') + recalc is_leaf + subtask_done
  │
  ├─ Cập nhật parent MỚI (nếu có):
  │   DB::increment('subtask_total') + is_leaf=FALSE
  │
  ├─ task.parent_id = newParentId
  ├─ task.depth = newParent ? newParent.depth + 1 : 0
  ├─ INSERT task_histories (field='parent_id', old=old_pid, new=newParentId)
  │
  └─ RecalcTaskDepthJob::dispatch(task.id)
     → Tái tính depth toàn bộ descendants (BFS, max 3 level)
```

### 7.4 Soft delete task

```
DestroyTaskAction::handle(Task $task)
  ├─ $task->delete() (soft delete)
  ├─ Nếu task.parent_id != NULL:
  │   DB::decrement('subtask_total') trên parent
  │   DB::decrement('subtask_done') nếu task.status='done'
  │   Recalc parent.is_leaf: is_leaf = (subtask_total == 0)
  │   UpdateTaskProgressJob::dispatch(parent_id)
  └─ UpdateProjectProgressJob::dispatch(project_id)
```

### 7.5 Log time

```
INSERT time_logs → TimeLogObserver::created → DB::increment tasks.logged_hours
UPDATE time_logs → TimeLogObserver::updated → DB::increment/decrement delta
DELETE time_logs → TimeLogObserver::deleted → DB::decrement tasks.logged_hours
```

### 7.6 Comment + @mention

```
POST task_comments
  ├─ INSERT task_comments
  ├─ DB::increment tasks.comment_count
  └─ Parse @mention từ content (regex /@\[(.+?)\]\((\d+)\)/)
     → Bulk INSERT task_comment_mentions (comment_id, user_id)
     → (Notification engine xử lý riêng — ngoài phạm vi)

DELETE task_comments (soft)
  └─ DB::decrement tasks.comment_count
```

---

## 8. Tính tiến độ dự án từ task

### Nguyên tắc

- Chỉ tính từ **leaf tasks** (`is_leaf = TRUE`) — tránh double counting
- Task `cancelled` bị loại khỏi denominator — không phạt tiến độ
- Task `is_archived = TRUE` hoặc `deleted_at IS NOT NULL` — loại khỏi cả tử số lẫn mẫu số

### Công thức

```
leaf_total = COUNT(*) WHERE project_id=X AND is_leaf=TRUE
                       AND status!='cancelled'
                       AND is_archived=FALSE AND deleted_at IS NULL

leaf_done  = COUNT(*) WHERE project_id=X AND is_leaf=TRUE
                       AND status='done'
                       AND deleted_at IS NULL

progress_pct = leaf_total > 0 ? ROUND(leaf_done / leaf_total * 100) : 0
```

### Implementation — Job với withoutOverlapping()

```php
// UpdateProjectProgressJob — idempotent + race-condition safe
class UpdateProjectProgressJob implements ShouldQueue
{
    use AsAction, Queueable, SerializesModels;

    public function __construct(private readonly int $projectId) {}

    public function middleware(): array
    {
        // Nếu nhiều jobs cùng project_id dispatch đồng thời,
        // chỉ 1 chạy, các job sau skip (idempotent — kết quả cuối đúng)
        return [new WithoutOverlapping("proj_progress_{$this->projectId}", 30)];
    }

    public function handle(): void
    {
        $stats = DB::table('tasks')
            ->where('project_id', $this->projectId)
            ->where('is_leaf', true)
            ->where('is_archived', false)
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status != 'cancelled' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status  = 'done'      THEN 1 ELSE 0 END) as done
            ")
            ->first();

        $pct = $stats->active > 0
            ? (int) round($stats->done / $stats->active * 100)
            : 0;

        DB::table('projects')->where('id', $this->projectId)->update([
            'progress_pct' => $pct,
            'task_total'   => $stats->total,
            'task_done'    => $stats->done,
        ]);
    }
}

// UpdateTaskProgressJob — bubble up, cũng idempotent
class UpdateTaskProgressJob implements ShouldQueue
{
    use AsAction, Queueable, SerializesModels;

    public function __construct(private readonly int $parentId) {}

    public function middleware(): array
    {
        return [new WithoutOverlapping("task_progress_{$this->parentId}", 30)];
    }

    public function handle(): void
    {
        $parent = Task::find($this->parentId);
        if (!$parent) return;

        $stats = DB::table('tasks')
            ->where('parent_id', $this->parentId)
            ->where('is_archived', false)
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status != 'cancelled' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status  = 'done'      THEN 1 ELSE 0 END) as done
            ")
            ->first();

        $pct = $stats->active > 0
            ? (int) round($stats->done / $stats->active * 100)
            : 0;

        DB::table('tasks')->where('id', $this->parentId)->update([
            'progress_pct' => $pct,
            'subtask_done' => $stats->done,
            'is_leaf'      => $stats->total === 0,
        ]);

        // Bubble up — max 3 lần (depth max = 3)
        if ($parent->parent_id) {
            self::dispatch($parent->parent_id);
        }
    }
}
```

### Ví dụ

```
Epic "Login"            is_leaf=FALSE
  Story "Màn hình"      is_leaf=FALSE
    Task "Thiết kế UI"  [done]      ← đếm
    Task "Viết API"     [done]      ← đếm
    Task "Unit test"    [todo]      ← đếm
  Story "Quên MK"       is_leaf=FALSE
    Task "Giao diện"    [done]      ← đếm
    Task "Gửi email"    [cancelled] ← loại khỏi mẫu số

active = 4, done = 3 → progress = 75%
```

---

## 9. Query Patterns

> **Join note:** `employees.name` là tên hiển thị. `users.name` cho actor audit/comment.

### 9.1 List view task của project (Tabulator)

```sql
SELECT
    t.id, t.uuid, t.title, t.task_type, t.status, t.priority,
    t.due_date, t.story_points, t.estimated_hours, t.logged_hours,
    t.progress_pct, t.subtask_total, t.subtask_done,
    t.depth, t.parent_id, t.sort_order,
    t.comment_count, t.attachment_count,
    e.name AS assignee_name
FROM tasks t
LEFT JOIN employees e ON e.id = t.employee_id
WHERE t.project_id     = :project_id
  AND t.is_archived    = FALSE
  AND t.deleted_at     IS NULL
  AND (:status   IS NULL OR t.status      = :status)
  AND (:assignee IS NULL OR t.employee_id = :assignee)
  AND (:type     IS NULL OR t.task_type   = :type)
ORDER BY t.task_type, t.sort_order, t.created_at;
-- Index hit: idx_task_list (project_id, task_type, status, sort_order)
```

### 9.2 Subtasks của 1 task (lazy load)

```sql
SELECT t.*, e.name AS assignee_name
FROM tasks t
LEFT JOIN employees e ON e.id = t.employee_id
WHERE t.parent_id  = :task_id
  AND t.is_archived = FALSE
  AND t.deleted_at  IS NULL
ORDER BY t.sort_order;
-- Index hit: idx_task_parent (parent_id)
```

### 9.3 My tasks — dashboard cá nhân (cross-project)

```sql
SELECT
    t.id, t.uuid, t.title, t.status, t.priority, t.due_date, t.task_type,
    p.name AS project_name, p.status AS project_status
FROM tasks t
JOIN projects p ON p.id = t.project_id
WHERE t.employee_id    = :employee_id
  AND t.organization_id = :org_id
  AND t.status         NOT IN ('done', 'cancelled')
  AND t.is_archived    = FALSE
  AND t.deleted_at     IS NULL
ORDER BY
    CASE t.priority
        WHEN 'critical' THEN 1 WHEN 'high' THEN 2
        WHEN 'medium'   THEN 3 ELSE 4
    END,
    t.due_date NULLS LAST
LIMIT 30;
-- Index hit: idx_task_assignee (employee_id, status, due_date)
```

### 9.4 Overdue tasks — cron

```sql
SELECT t.id, t.title, t.due_date, t.priority,
       e.name AS assignee_name, p.name AS project_name
FROM tasks t
JOIN employees e ON e.id  = t.employee_id
JOIN projects  p ON p.id  = t.project_id
WHERE t.due_date       < CURRENT_DATE
  AND t.status         NOT IN ('done', 'cancelled')
  AND t.is_archived    = FALSE
  AND t.deleted_at     IS NULL
  AND t.employee_id    IS NOT NULL
  AND t.organization_id = :org_id
ORDER BY t.due_date, t.priority;
-- Index hit: idx_task_due (project_id, due_date, status)
```

### 9.5 Time report theo project

```sql
SELECT
    e.name                                                          AS employee_name,
    SUM(tl.hours)                                                   AS total_hours,
    SUM(CASE WHEN tl.is_billable = 1 THEN tl.hours ELSE 0 END)    AS billable_hours,
    COUNT(DISTINCT tl.task_id)                                      AS tasks_worked,
    COUNT(DISTINCT tl.log_date)                                     AS days_worked
FROM time_logs tl
JOIN employees e ON e.id = tl.employee_id
WHERE tl.project_id     = :project_id
  AND tl.organization_id = :org_id
  AND tl.log_date       BETWEEN :start AND :end
  AND tl.deleted_at     IS NULL
GROUP BY e.id, e.name
ORDER BY total_hours DESC;
-- Index hit: idx_timelog_project (project_id, log_date, employee_id)
```

### 9.6 Progress breakdown — dashboard project

```sql
SELECT
    task_type,
    COUNT(*)                                                    AS total,
    SUM(CASE WHEN status = 'done'        THEN 1 ELSE 0 END)   AS done,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END)   AS in_progress,
    SUM(CASE WHEN status = 'todo'        THEN 1 ELSE 0 END)   AS todo,
    SUM(CASE WHEN status = 'blocked'     THEN 1 ELSE 0 END)   AS blocked,
    ROUND(AVG(progress_pct), 1)                                AS avg_progress
FROM tasks
WHERE project_id   = :project_id
  AND is_leaf      = TRUE
  AND is_archived  = FALSE
  AND deleted_at   IS NULL
  AND status      != 'cancelled'
GROUP BY task_type;
-- Index hit: idx_task_leaf (project_id, is_leaf, status)
```

### 9.7 Lịch sử thay đổi status của task

```sql
SELECT h.old_value, h.new_value, h.changed_at, u.name AS actor_name
FROM task_histories h
JOIN users u ON u.id = h.actor_id
WHERE h.task_id       = :task_id
  AND h.field_changed = 'status'
ORDER BY h.changed_at DESC;
-- Index hit: idx_history_task_field (task_id, field_changed, changed_at)
```

---

## 10. API Endpoints

> **Quy ước URL** khớp với Project module:
> - Web (Blade): `/dashboard/tasks/*` → `tasks.*`
> - Backend API (Tabulator): `/backend/api/tasks` → `backend.api.tasks`
> - API v1 (Sanctum): `/api/v1/projects/{project}/tasks` → `task.*`

### Web routes

| Method | URL | Route name | Mô tả |
|---|---|---|---|
| GET | `/dashboard/tasks` | `tasks.index` | List cross-project |
| GET | `/dashboard/tasks/create` | `tasks.create` | Form tạo |
| POST | `/dashboard/tasks` | `tasks.store` | Lưu |
| GET | `/dashboard/tasks/{task}` | `tasks.show` | Chi tiết |
| GET | `/dashboard/tasks/{task}/edit` | `tasks.edit` | Form sửa |
| PUT | `/dashboard/tasks/{task}` | `tasks.update` | Cập nhật |
| DELETE | `/dashboard/tasks/{task}` | `tasks.destroy` | Soft delete |

### Backend API (Tabulator)

| Method | URL | Mô tả |
|---|---|---|
| GET | `/backend/api/tasks` | List cross-project cho Tabulator |
| GET | `/backend/api/projects/{project}/tasks` | Task của 1 project |

### API v1 (Sanctum)

**Task CRUD:**

| Method | URL | Mô tả |
|---|---|---|
| GET | `/api/v1/projects/{project}/tasks` | List với filter |
| POST | `/api/v1/projects/{project}/tasks` | Tạo top-level |
| GET | `/api/v1/tasks/my` | My tasks cross-project |
| GET | `/api/v1/tasks/{task}` | Chi tiết |
| GET | `/api/v1/tasks/{task}/subtasks` | Subtasks trực tiếp |
| GET | `/api/v1/tasks/{task}/breadcrumb` | Path Epic→task |
| POST | `/api/v1/tasks/{task}/subtasks` | Tạo subtask |
| PUT | `/api/v1/tasks/{task}` | Cập nhật |
| PATCH | `/api/v1/tasks/{task}/status` | Đổi status |
| PATCH | `/api/v1/tasks/{task}/assignee` | Đổi assignee |
| PATCH | `/api/v1/tasks/{task}/move` | Di chuyển parent |
| PATCH | `/api/v1/tasks/reorder` | Bulk sort_order |
| DELETE | `/api/v1/tasks/{task}` | Soft delete |

**Comments:**

| Method | URL | Mô tả |
|---|---|---|
| GET | `/api/v1/tasks/{task}/comments` | List + replies |
| POST | `/api/v1/tasks/{task}/comments` | Thêm (parse @mention → insert mentions) |
| PUT | `/api/v1/tasks/{task}/comments/{comment}` | Sửa (re-parse mentions) |
| DELETE | `/api/v1/tasks/{task}/comments/{comment}` | Soft delete |

**Attachments, Watchers, Time:**

| Method | URL | Mô tả |
|---|---|---|
| POST | `/api/v1/media/upload` | Upload FilePond (collection=attachments) |
| DELETE | `/api/v1/media/upload/{uuid}` | Revert |
| POST | `/api/v1/tasks/{task}/watch` | Follow |
| DELETE | `/api/v1/tasks/{task}/watch` | Unfollow |
| GET | `/api/v1/tasks/{task}/time-logs` | Log của task |
| POST | `/api/v1/tasks/{task}/time-logs` | Thêm |
| PUT | `/api/v1/time-logs/{log}` | Sửa |
| DELETE | `/api/v1/time-logs/{log}` | Xóa |
| GET | `/api/v1/projects/{project}/time-report` | Báo cáo |
| GET | `/api/v1/projects/{project}/progress` | Tiến độ + breakdown |
| GET | `/api/v1/projects/{project}/stats` | Tổng hợp |

---

## 11. Business Rules

### BR-TASK-001: Hierarchy depth
- Tối đa 4 cấp: depth 0→3. Validate `parent.depth < 3` trước INSERT child
- `parent_id` phải cùng `project_id` — không cross-project
- `task_type = 'subtask'`: depth = 3, không tạo con
- `project_id` auto-inherit từ parent — không nhận từ client

### BR-TASK-002: Tenant isolation
- `OrganizationScope` tự động scope tất cả query — không query thủ công
- `project_id`, `employee_id`, `label_id` phải cùng `organization_id` — validate trong Action

### BR-TASK-003: Atomic counter updates
- Tất cả counter (`subtask_total`, `subtask_done`, `logged_hours`, `comment_count`, `attachment_count`) cập nhật qua `DB::increment`/`DB::decrement` — không dùng read-modify-write
- Counter cập nhật đủ 3 sự kiện: create, update, delete (kể cả soft delete)

### BR-TASK-004: Progress calculation
- Chỉ tính leaf tasks (`is_leaf=TRUE`) không archived, không soft-deleted
- `cancelled` loại khỏi mẫu số
- Jobs dùng `withoutOverlapping()` — tránh race condition khi nhiều tasks đổi status đồng thời
- Khi task bị soft delete: cập nhật counters của parent → dispatch progress jobs

### BR-TASK-005: Move task (đổi parent)
- Recalc depth cho task và **toàn bộ descendants** qua `RecalcTaskDepthJob` (BFS, max 3 iterations)
- Cập nhật counters của cả parent cũ lẫn parent mới
- Log `task_histories` với `field_changed='parent_id'`

### BR-TASK-006: Time log
- `hours` ∈ (0, 24] — validate trong StoreTimeLogData
- `log_date` ≤ today() — validate
- Chỉ employee log hoặc Project Owner mới được sửa/xóa
- `logged_hours` trên task sync qua `TimeLogObserver` (create/update/delete)

### BR-TASK-007: Task history — scalar only, no JSON
- Không UPDATE/DELETE row nào trong `task_histories` hay `task_label_histories`
- `old_value`/`new_value` là VARCHAR(500) — chỉ scalar
- Changes N:M (labels) dùng `task_label_histories`, không nhét JSON vào `task_histories`
- `description` change: chỉ log `new_value = 'updated'` — không log nội dung đầy đủ

### BR-TASK-008: @mention — no JSON
- Parse @mention từ comment content, INSERT từng row vào `task_comment_mentions`
- Không lưu mentions dưới bất kỳ dạng JSON/array nào trong `task_comments`

### BR-TASK-009: sort_order initialization
- Task mới: `sort_order = SELECT COALESCE(MAX(sort_order),0)+1 FROM tasks WHERE project_id=X AND parent_id=Y`
- Bulk reorder: 1 UPDATE duy nhất với CASE WHEN, không N queries riêng lẻ

### BR-TASK-010: Archive vs Soft delete
- Archive (`is_archived=TRUE`): task đóng băng, ẩn khỏi list active, vẫn xuất hiện trong archive view, không tính progress
- Soft delete (`deleted_at IS NOT NULL`): xóa logic, không xuất hiện ở đâu cả, không tính progress
- Cả hai đều trigger cập nhật counters và progress của parent/project

### BR-TASK-011: Spatie MediaLibrary custom_properties
- **Không dùng** `custom_properties` (JSON column của Spatie) để lưu metadata nghiệp vụ
- Nếu cần thêm metadata cho attachment: thêm cột riêng vào bảng `tasks` hoặc tạo bảng `task_attachment_meta`

---

## 12. Indexes

```php
// ── tasks ────────────────────────────────────────────────────────────────────

// List view theo project — query phổ biến nhất
// Bao phủ: (project_id, task_type, status) — index idx_task_type không cần thiết nữa
$table->index(['project_id', 'task_type', 'status', 'sort_order'], 'idx_task_list');

// Filter theo project + status (không có task_type)
$table->index(['project_id', 'status', 'is_archived'], 'idx_task_project');

// Parent lookup (load subtasks, bubble up)
$table->index('parent_id', 'idx_task_parent');

// My tasks + overdue (cross-project, theo employee)
$table->index(['employee_id', 'status', 'due_date'], 'idx_task_assignee');

// Overdue query (theo project + due_date)
$table->index(['project_id', 'due_date', 'status'], 'idx_task_due');

// Progress recalculation (leaf tasks chỉ)
$table->index(['project_id', 'is_leaf', 'status'], 'idx_task_leaf');

// Tenant-level query (cross-project)
$table->index(['organization_id', 'employee_id', 'status'], 'idx_task_org');

// ── time_logs ────────────────────────────────────────────────────────────────
$table->index(['task_id', 'log_date'],              'idx_timelog_task');
$table->index(['project_id', 'log_date', 'employee_id'], 'idx_timelog_project');
$table->index(['employee_id', 'log_date'],          'idx_timelog_employee');
$table->index('organization_id',                    'idx_timelog_org');

// ── task_histories ───────────────────────────────────────────────────────────
// Bao phủ cả query "tất cả history" và query "history của 1 field"
$table->index(['task_id', 'field_changed', 'changed_at'], 'idx_history_task_field');

// ── task_label_histories ─────────────────────────────────────────────────────
$table->index(['task_id', 'changed_at'], 'idx_lhist_task');

// ── task_comment_mentions ────────────────────────────────────────────────────
$table->index('user_id', 'idx_mention_user');
// → Dùng cho: "tôi được mention ở đâu" — query notification
```

> **Index nào đã loại bỏ so với bản trước:**
> - `idx_task_type (project_id, task_type, status)` — **thừa**, đã được `idx_task_list` bao phủ hoàn toàn
> - `idx_task_org_status (organization_id, status)` — **thay bằng** `idx_task_org` rộng hơn

> **DB note:** MySQL-compatible. SQLite hỗ trợ toàn bộ. PostgreSQL có thể bổ sung partial index và GIN full-text trong extension migration riêng khi cần.

---

## 13. Lộ trình triển khai

### Phase 1 — Core Task (tuần 1–2)
- [ ] Extension migration: `progress_pct`, `task_total`, `task_done` vào `projects`
- [ ] Module migration: `tasks`, `task_labels`, `task_label_maps`
- [ ] Enums: `TaskType`, `TaskStatus`, `TaskPriority`
- [ ] Task model (TenantAwareModel, HasMedia, HasTenantMedia)
- [ ] `StoreTaskAction`, `UpdateTaskAction`, `MoveTaskAction`, `DestroyTaskAction`
- [ ] CRUD + hierarchy validation + sort_order initialization
- [ ] List / Tabulator (khớp `ListProjectsHandler` pattern)
- [ ] `TaskObserver` + `UpdateTaskProgressJob` (withoutOverlapping) + `UpdateProjectProgressJob`
- [ ] `TaskPolicy` (khớp `ProjectPolicy`)

### Phase 2 — Collaboration (tuần 3–4)
- [ ] Module migration: `task_comments`, `task_comment_mentions`, `task_watchers`
- [ ] Comment thread + reply + @mention (parse → insert `task_comment_mentions`)
- [ ] File attachment qua FilePond + MediaUploadService
- [ ] `TaskCommentObserver` → sync `comment_count`
- [ ] `task_histories` + `task_label_histories` — audit trail
- [ ] `RecalcTaskDepthJob` — cho MoveTask
- [ ] Watcher follow/unfollow

### Phase 3 — Time & Analytics (tuần 5–6)
- [ ] Module migration: `time_logs`
- [ ] `TimeLogObserver` → sync `logged_hours` (create/update/delete)
- [ ] Time report per project/employee (query 9.5)
- [ ] Dashboard: progress breakdown (query 9.6) + My tasks (query 9.3)
- [ ] Cron: overdue alert (query 9.4)

---

*Version 1.2.0 — Task Center Module Specification*
*Tối ưu: loại bỏ JSON, atomic counters, race-condition safe progress jobs, index hợp lý*
*Stack: Laravel 13 · Alpine.js · Tabulator · FilePond · Spatie MediaLibrary*
