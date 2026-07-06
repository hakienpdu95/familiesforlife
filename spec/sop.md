# Đặc Tả: SOP Flowchart Engine — Relational Design (No JSON)

> **Hệ thống:** SaaS SME
> **Stack:** Laravel 13 + Alpine.js  
> **Phiên bản:** 3.0.0 — BIGINT PK, FK aligned với hệ thống, Bổ sung bảng thiếu  
> **Ngày:** 2026-06-03

---

## Mục lục

1. [Tại sao không dùng JSON](#1-tại-sao-không-dùng-json)
2. [Kiến trúc tổng thể](#2-kiến-trúc-tổng-thể)
3. [ERD — Sơ đồ quan hệ](#3-erd--sơ-đồ-quan-hệ)
4. [Đặc tả bảng dữ liệu](#4-đặc-tả-bảng-dữ-liệu)
   - 4.0 [SOP_PROCESS — Quy trình SOP chính](#40-sop_process--quy-trình-sop-chính)
   - 4.1 [SOP_STEP — Bước quy trình](#41-sop_step--bước-quy-trình-core)
   - 4.2 [SOP_STEP_CONNECTOR — Kết nối bước](#42-sop_step_connector--kết-nối-giữa-các-bước)
   - 4.3 [SOP_VERSION — Header phiên bản](#43-sop_version--header-phiên-bản)
   - 4.4 [SOP_STEP_VERSION — Snapshot bước](#44-sop_step_version--snapshot-bước-immutable)
   - 4.5 [SOP_STEP_RACI_VERSION — Snapshot RACI](#45-sop_step_raci_version--snapshot-raci-immutable)
   - 4.6 [SOP_STEP_CONNECTOR_VERSION — Snapshot connector](#46-sop_step_connector_version--snapshot-connector-immutable)
   - 4.7 [SOP_STEP_RACI — Phân công RACI live](#47-sop_step_raci--phân-công-raci-live)
   - 4.8 [SOP_STEP_ATTACHMENT — File đính kèm bước](#48-sop_step_attachment--file-đính-kèm-bước)
   - 4.9 [SOP_APPROVAL_FLOW — Luồng phê duyệt phiên bản](#49-sop_approval_flow--luồng-phê-duyệt-phiên-bản)
   - 4.10 [SOP_RELATION — Quan hệ giữa các SOP](#410-sop_relation--quan-hệ-giữa-các-sop)
5. [Enum Values đầy đủ](#5-enum-values-đầy-đủ)
6. [Migration SQL (Laravel)](#6-migration-sql-laravel)
7. [Query Patterns — Truy vấn tối ưu](#7-query-patterns--truy-vấn-tối-ưu)
8. [Eloquent Models & Relationships](#8-eloquent-models--relationships)
9. [Versioning Logic — Không JSON](#9-versioning-logic--không-json)
10. [Render Flowchart — Alpine.js + SVG](#10-render-flowchart--alpinejs--svg)
11. [Business Rules & Constraints](#11-business-rules--constraints)
12. [Indexes & Performance](#12-indexes--performance)
13. [Lộ trình triển khai](#13-lộ-trình-triển-khai)

---

## Thay đổi từ v2.0.0 → v3.0.0

| Hạng mục | v2.0.0 | v3.0.0 | Lý do |
|---|---|---|---|
| **PK type** | UUID làm PK trực tiếp | BIGINT AUTO_INCREMENT + cột `uuid` riêng | Khớp chuẩn toàn hệ thống (`$table->id()` + `$table->uuid()`) |
| **FK type** | UUID FK | BIGINT FK | Khớp với `users.id`, `organizations.id`, `branches.id`, `departments.id` (tất cả đều BIGINT) |
| **SOP_PROCESS** | **Thiếu hoàn toàn** — referenced nhưng không có spec | Thêm đầy đủ (section 4.0) | Bảng gốc, parent của toàn bộ module |
| **SOP_APPROVAL_FLOW** | Có trong ERD nhưng không có table spec | Thêm đầy đủ (section 4.9) | Multi-step approval tracking |
| **SOP_RELATION** | Có trong ERD nhưng không có table spec | Thêm đầy đủ (section 4.10) | SOP dependencies / references |
| **RACI.assignee_id** | UUID | BIGINT | `users.id` và `roles.id` (Spatie) đều BIGINT |
| **Snapshot FK refs** | UUID snapshots | BIGINT snapshots | Nhất quán với PK BIGINT của các bảng nguồn |

---

## 1. Tại sao không dùng JSON

### Vấn đề với JSON column

| Vấn đề | Hệ quả thực tế |
|---|---|
| **Không index được** | Lọc `step_type = 'decision'` → full table scan toàn bộ SOP |
| **Không JOIN được** | Lấy RACI kèm role name phải load toàn JSON → merge ở app layer → N+1 |
| **Không có FK constraint** | `ref_sop_id` trong JSON không enforce → SOP bị xóa nhưng reference vẫn tồn tại |
| **Version diff tốn memory** | So sánh 2 version phải deserialize JSON rồi diff ở app — SOP 50 bước = 2 JSON lớn |
| **Analytics không khả thi** | `AVG(duration_minutes) GROUP BY step_type` → không query được từ JSON column |
| **Cascade delete không hoạt động** | DB không biết relationship → phải xử lý thủ công ở app layer |
| **Full-text search không hiệu quả** | MySQL JSON search dùng function-based, không dùng được FULLTEXT index |

### Nguyên tắc thiết kế

1. **Mọi entity là một bảng riêng** — steps, connectors, RACI, attachments đều là bảng độc lập
2. **Connector là first-class citizen** — `sop_step_connectors` bảng riêng, không nhúng trong step
3. **Versioning relational** — `sop_step_versions` mirror cấu trúc `sop_steps`, không JSON blob
4. **Position thay vì step_number** — dùng `SMALLINT position` + floating point ordering để tránh renumber hàng loạt
5. **Branch logic tách biệt** — `branch_yes_position` / `branch_no_position` là column riêng, có thể index
6. **BIGINT PK + UUID column** — theo chuẩn toàn hệ thống; UUID expose ra API, BIGINT dùng nội bộ

---

## 2. Kiến trúc tổng thể

```
┌─────────────────────────────────────────────────────────┐
│                    SOP FLOWCHART ENGINE                 │
├─────────────────────────┬───────────────────────────────┤
│     LIVE (Active)       │      VERSIONED (History)      │
├─────────────────────────┼───────────────────────────────┤
│ SOP_PROCESS             │ SOP_VERSION                   │
│   └─ SOP_STEP           │   └─ SOP_STEP_VERSION         │
│       ├─ SOP_STEP_      │       └─ SOP_STEP_RACI_       │
│       │   CONNECTOR     │           VERSION             │
│       ├─ SOP_STEP_RACI  │   └─ SOP_STEP_CONNECTOR_      │
│       └─ SOP_STEP_      │           VERSION             │
│           ATTACHMENT    │   └─ SOP_APPROVAL_FLOW        │
│   └─ SOP_RELATION       │                               │
└─────────────────────────┴───────────────────────────────┘
         │                              │
         │ snapshot on approve          │ rollback creates new draft
         └──────────────────────────────┘
```

### Hai vùng dữ liệu

**LIVE zone** — dữ liệu đang active, luôn được query khi render flowchart:
- `sop_steps` — bước hiện tại (is_active = TRUE)
- `sop_step_connectors` — kết nối hiện tại
- `sop_step_raci` — phân công hiện tại

**VERSION zone** — bất biến (immutable) sau khi tạo, không bao giờ UPDATE:
- `sop_versions` — header phiên bản
- `sop_step_versions` — snapshot bước tại thời điểm approve
- `sop_step_raci_versions` — snapshot RACI
- `sop_step_connector_versions` — snapshot connector
- `sop_approval_flows` — lịch sử phê duyệt từng bước

---

## 3. ERD — Sơ đồ quan hệ

```
organizations (BIGINT PK — hệ thống hiện có)
    │
    │ 1:N
    ▼
SOP_PROCESS (BIGINT PK + uuid)
  ├─ branch_id (FK → branches, BIGINT)
  ├─ department_id (FK → departments, BIGINT)
  ├─ owner_id (FK → users, BIGINT)
  │
  ├─1:N─► SOP_STEP ──────────────────────────────────────┐
  │           ├─1:N─► SOP_STEP_RACI                      │
  │           ├─1:N─► SOP_STEP_ATTACHMENT                 │
  │           └─ (ref_sop_id → SOP_PROCESS, BIGINT FK)    │
  │                                                       │
  ├─1:N─► SOP_STEP_CONNECTOR ◄──(from/to step_id BIGINT)─┘
  │
  ├─1:N─► SOP_VERSION
  │           ├─1:N─► SOP_STEP_VERSION
  │           │           └─1:N─► SOP_STEP_RACI_VERSION
  │           ├─1:N─► SOP_STEP_CONNECTOR_VERSION
  │           └─1:N─► SOP_APPROVAL_FLOW
  │
  └─1:N─► SOP_RELATION (sop_id + related_sop_id → SOP_PROCESS)

Quan hệ đặc biệt (tất cả FK đều BIGINT):
  sop_steps.sop_id                   → sop_processes.id (CASCADE DELETE)
  sop_steps.ref_sop_id               → sop_processes.id (SET NULL on delete)
  sop_step_connectors.from_step_id   → sop_steps.id (CASCADE DELETE)
  sop_step_connectors.to_step_id     → sop_steps.id (CASCADE DELETE)
  sop_step_versions.original_step_id → sop_steps.id (SET NULL — giữ history)
  sop_step_raci.assignee_id          → users.id hoặc roles.id (BIGINT, polymorphic)
```

---

## 4. Đặc tả bảng dữ liệu

> **Quy ước chung — Chuẩn dự án:**
> ```php
> $table->id();  // BIGINT UNSIGNED AUTO_INCREMENT — PK nội bộ
> $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
> ```

---

### 4.0 SOP_PROCESS — Quy trình SOP chính

**Mục đích:** Bảng gốc của toàn bộ module SOP. Mỗi SOP là một quy trình chuẩn vận hành của tổ chức, được định danh bằng `code` duy nhất trong org.

> **Lưu ý v3.0.0:** Bảng này thiếu hoàn toàn trong spec trước nhưng được reference khắp nơi trong code. Spec này bổ sung đầy đủ.

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK AUTO_INCREMENT | | Khóa chính nội bộ |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID — expose ra API, không phải PK |
| `organization_id` | BIGINT UNSIGNED | NOT NULL | FK (organizations), INDEX | | Tenant isolation |
| `branch_id` | BIGINT UNSIGNED | NULL | FK (branches) | NULL | Chi nhánh áp dụng — NULL = toàn org |
| `department_id` | BIGINT UNSIGNED | NULL | FK (departments) | NULL | Phòng ban áp dụng |
| `owner_id` | BIGINT UNSIGNED | NOT NULL | FK (users) | | Người chịu trách nhiệm SOP |
| `code` | VARCHAR(50) | NOT NULL | UNIQUE(org, code) | | Mã SOP duy nhất trong org, vd: `SOP-HR-001` |
| `title` | VARCHAR(300) | NOT NULL | | | Tiêu đề quy trình |
| `description` | TEXT | NULL | | NULL | Mô tả tổng quan quy trình |
| `type` | ENUM | NOT NULL | INDEX | `internal` | Xem mục 5.4 |
| `status` | ENUM | NOT NULL | INDEX | `draft` | draft / pending_review / approved / rejected / archived |
| `version` | SMALLINT UNSIGNED | NOT NULL | | 0 | Số phiên bản đã approved hiện tại (0 = chưa có version nào được approve) |
| `approved_by` | BIGINT UNSIGNED | NULL | FK (users) | NULL | Người approve version hiện tại |
| `approved_at` | TIMESTAMP | NULL | | NULL | Thời điểm approve version hiện tại |
| `effective_date` | DATE | NULL | | NULL | Ngày SOP bắt đầu có hiệu lực |
| `expired_date` | DATE | NULL | INDEX | NULL | Ngày SOP hết hiệu lực — cron tự chuyển archived |
| `created_by` | BIGINT UNSIGNED | NOT NULL | FK (users) | | |
| `updated_by` | BIGINT UNSIGNED | NULL | FK (users) | NULL | |
| `created_at` | TIMESTAMP | NOT NULL | | NOW() | |
| `updated_at` | TIMESTAMP | NOT NULL | | NOW() | |
| `deleted_at` | TIMESTAMP | NULL | | NULL | Soft delete |

**Indexes:**

```sql
CREATE UNIQUE INDEX idx_sop_proc_code ON sop_processes(organization_id, code);
CREATE INDEX idx_sop_proc_status ON sop_processes(organization_id, status);
CREATE INDEX idx_sop_proc_dept ON sop_processes(organization_id, department_id) WHERE department_id IS NOT NULL;
CREATE INDEX idx_sop_proc_expired ON sop_processes(expired_date) WHERE expired_date IS NOT NULL;
```

---

### 4.1 SOP_STEP — Bước quy trình (core)

**Thay đổi quan trọng so với thiết kế cũ:**
- `id` đổi từ UUID PK sang BIGINT AUTO_INCREMENT PK + cột `uuid` riêng
- Tất cả FK (sop_id, ref_sop_id, created_by, updated_by) đổi sang BIGINT
- `step_number` → đổi thành `position SMALLINT`
- Thêm `is_active` — soft delete từng bước mà không ảnh hưởng version history

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK AUTO_INCREMENT | | Khóa chính nội bộ |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID — expose ra ngoài |
| `sop_id` | BIGINT UNSIGNED | NOT NULL | FK (sop_processes), INDEX | | CASCADE DELETE |
| `position` | SMALLINT UNSIGNED | NOT NULL | INDEX | | Vị trí trong flow (1, 2, 3...) |
| `title` | VARCHAR(200) | NOT NULL | | | Tên bước ngắn gọn |
| `description` | TEXT | NULL | | NULL | Hướng dẫn chi tiết |
| `expected_output` | TEXT | NULL | | NULL | Kết quả đầu ra mong đợi |
| `warning_note` | TEXT | NULL | | NULL | Cảnh báo đặc biệt |
| `step_type` | ENUM | NOT NULL | INDEX | `action` | Xem mục 5.1 |
| `ref_sop_id` | BIGINT UNSIGNED | NULL | FK (sop_processes) | NULL | SET NULL on delete. Dùng khi step_type = sub_sop |
| `branch_yes_position` | SMALLINT UNSIGNED | NULL | | NULL | Nếu decision: position của bước khi Yes |
| `branch_no_position` | SMALLINT UNSIGNED | NULL | | NULL | Nếu decision: position của bước khi No |
| `duration_minutes` | SMALLINT UNSIGNED | NULL | | NULL | Thời gian ước tính |
| `is_mandatory` | BOOLEAN | NOT NULL | | TRUE | FALSE = bước tùy chọn |
| `is_active` | BOOLEAN | NOT NULL | INDEX | TRUE | FALSE = soft delete — bước đã xóa khỏi flow active |
| `created_by` | BIGINT UNSIGNED | NOT NULL | FK (users) | | |
| `updated_by` | BIGINT UNSIGNED | NULL | FK (users) | NULL | |
| `created_at` | TIMESTAMP | NOT NULL | | NOW() | |
| `updated_at` | TIMESTAMP | NOT NULL | | NOW() | |

**Indexes:**

```sql
CREATE UNIQUE INDEX idx_sop_step_position ON sop_steps(sop_id, position)
  WHERE is_active = TRUE;
CREATE INDEX idx_sop_step_render ON sop_steps(sop_id, is_active, position);
CREATE INDEX idx_sop_step_type ON sop_steps(sop_id, step_type, is_active);
CREATE INDEX idx_sop_step_ref ON sop_steps(ref_sop_id)
  WHERE ref_sop_id IS NOT NULL;
```

**Tại sao `position` thay vì `step_number`:**

```
Kịch bản: Insert bước mới giữa bước 3 và bước 4.

  Với step_number INT:
    UPDATE sop_steps SET step_number = step_number + 1
    WHERE sop_id = X AND step_number >= 4;
    → Lock nhiều row, tốn I/O

  Với position SMALLINT + bulk update (approach chọn ở đây):
    Batch update toàn bộ trong 1 transaction khi drag & drop
    → Predictable, dễ debug, dễ hiểu
```

---

### 4.2 SOP_STEP_CONNECTOR — Kết nối giữa các bước

**Đây là bảng first-class citizen** — không nhúng trong step. Tách connector ra bảng riêng cho phép:
- Query tất cả connector của 1 SOP trong 1 query
- Thêm custom label và màu sắc cho từng connector
- Version snapshot connector độc lập với step
- Analytics: bước nào có nhiều connector nhất (hub trong flow)

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK AUTO_INCREMENT | | Khóa chính nội bộ |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `sop_id` | BIGINT UNSIGNED | NOT NULL | FK (sop_processes), INDEX | | để query 1 lần lấy hết connector |
| `from_step_id` | BIGINT UNSIGNED | NOT NULL | FK (sop_steps), INDEX | | CASCADE DELETE |
| `to_step_id` | BIGINT UNSIGNED | NOT NULL | FK (sop_steps), INDEX | | CASCADE DELETE |
| `connector_type` | ENUM | NOT NULL | INDEX | `sequence` | Xem mục 5.2 |
| `label` | VARCHAR(60) | NULL | | NULL | Nhãn trên mũi tên: "Có", "Không", "Lỗi" |
| `color_hex` | CHAR(7) | NULL | | NULL | Override màu, nếu NULL thì dùng màu default |
| `sort_order` | SMALLINT | NOT NULL | | 0 | Thứ tự render khi nhiều connector cùng source |

**Indexes:**

```sql
CREATE UNIQUE INDEX idx_connector_unique
  ON sop_step_connectors(from_step_id, to_step_id, connector_type);
CREATE INDEX idx_connector_sop ON sop_step_connectors(sop_id);
CREATE INDEX idx_connector_from ON sop_step_connectors(from_step_id);
CREATE INDEX idx_connector_to ON sop_step_connectors(to_step_id);
```

---

### 4.3 SOP_VERSION — Header phiên bản

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK AUTO_INCREMENT | | Khóa chính nội bộ |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `sop_id` | BIGINT UNSIGNED | NOT NULL | FK (sop_processes), INDEX | | CASCADE DELETE |
| `version_number` | INT UNSIGNED | NOT NULL | | | Tăng dần: 1, 2, 3... |
| `status` | ENUM | NOT NULL | INDEX | `draft` | draft / submitted / approved / rejected |
| `change_summary` | TEXT | NULL | | NULL | Mô tả thay đổi so với version trước |
| `total_steps` | SMALLINT UNSIGNED | NOT NULL | | 0 | Denormalized — tổng số bước tại snapshot |
| `total_duration_minutes` | INT UNSIGNED | NOT NULL | | 0 | Denormalized — tổng thời gian tại snapshot |
| `created_by` | BIGINT UNSIGNED | NOT NULL | FK (users) | | Người tạo/sửa version |
| `approved_by` | BIGINT UNSIGNED | NULL | FK (users) | NULL | Người approve version này |
| `created_at` | TIMESTAMP | NOT NULL | | NOW() | |
| `approved_at` | TIMESTAMP | NULL | | NULL | |

```sql
CREATE UNIQUE INDEX idx_sop_version_num ON sop_versions(sop_id, version_number);
CREATE INDEX idx_sop_version_status ON sop_versions(sop_id, status);
```

---

### 4.4 SOP_STEP_VERSION — Snapshot bước (immutable)

**Nguyên tắc:** Sau khi INSERT, bảng này KHÔNG BAO GIỜ được UPDATE. Immutable by design.

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK AUTO_INCREMENT | | Khóa chính nội bộ |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `sop_version_id` | BIGINT UNSIGNED | NOT NULL | FK (sop_versions), INDEX | | CASCADE DELETE |
| `original_step_id` | BIGINT UNSIGNED | NULL | FK (sop_steps) | NULL | SET NULL khi step bị xóa. Dùng để trace "đây là snapshot của bước nào" khi rollback |
| `position` | SMALLINT UNSIGNED | NOT NULL | | | Vị trí bước tại thời điểm snapshot |
| `title` | VARCHAR(200) | NOT NULL | | | |
| `description` | TEXT | NULL | | NULL | |
| `expected_output` | TEXT | NULL | | NULL | |
| `warning_note` | TEXT | NULL | | NULL | |
| `step_type` | ENUM | NOT NULL | | | |
| `ref_sop_id` | BIGINT UNSIGNED | NULL | | NULL | Snapshot BIGINT — không có FK constraint (SOP đích có thể bị xóa sau) |
| `ref_sop_code` | VARCHAR(50) | NULL | | NULL | Snapshot code của SOP đích — hiển thị ngay cả khi SOP đích bị xóa |
| `branch_yes_position` | SMALLINT UNSIGNED | NULL | | NULL | |
| `branch_no_position` | SMALLINT UNSIGNED | NULL | | NULL | |
| `duration_minutes` | SMALLINT UNSIGNED | NULL | | NULL | |
| `is_mandatory` | BOOLEAN | NOT NULL | | TRUE | |
| `change_type` | ENUM | NOT NULL | | `unchanged` | added / modified / deleted / unchanged |

```sql
CREATE INDEX idx_step_ver_version ON sop_step_versions(sop_version_id, position);
CREATE INDEX idx_step_ver_original ON sop_step_versions(original_step_id)
  WHERE original_step_id IS NOT NULL;
CREATE INDEX idx_step_ver_change ON sop_step_versions(sop_version_id, change_type)
  WHERE change_type != 'unchanged';
```

---

### 4.5 SOP_STEP_RACI_VERSION — Snapshot RACI (immutable)

| Trường | Kiểu | Null | Key | Mô tả |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK AUTO_INCREMENT | Khóa chính nội bộ |
| `uuid` | CHAR(36) | NULL | UNIQUE | Public UUID |
| `sop_version_id` | BIGINT UNSIGNED | NOT NULL | FK (sop_versions), INDEX | CASCADE DELETE |
| `step_version_id` | BIGINT UNSIGNED | NOT NULL | FK (sop_step_versions) | CASCADE DELETE |
| `step_position` | SMALLINT UNSIGNED | NOT NULL | | Denormalized position — tránh JOIN thêm khi query RACI matrix |
| `assignee_type` | ENUM | NOT NULL | | user / role |
| `assignee_id` | BIGINT UNSIGNED | NOT NULL | | Snapshot BIGINT — không FK. Khớp với `users.id` hoặc `roles.id` (Spatie, đều BIGINT) |
| `assignee_name` | VARCHAR(150) | NOT NULL | | Snapshot tên — hiển thị ngay cả khi user/role bị xóa |
| `raci_type` | ENUM | NOT NULL | | R / A / C / I |

```sql
CREATE INDEX idx_raci_ver_version ON sop_step_raci_versions(sop_version_id);
CREATE INDEX idx_raci_ver_step ON sop_step_raci_versions(step_version_id);
```

---

### 4.6 SOP_STEP_CONNECTOR_VERSION — Snapshot connector (immutable)

| Trường | Kiểu | Null | Key | Mô tả |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK AUTO_INCREMENT | Khóa chính nội bộ |
| `uuid` | CHAR(36) | NULL | UNIQUE | Public UUID |
| `sop_version_id` | BIGINT UNSIGNED | NOT NULL | FK (sop_versions), INDEX | CASCADE DELETE |
| `from_position` | SMALLINT UNSIGNED | NOT NULL | | Position bước nguồn tại thời điểm snapshot |
| `to_position` | SMALLINT UNSIGNED | NOT NULL | | Position bước đích tại thời điểm snapshot |
| `connector_type` | ENUM | NOT NULL | | |
| `label` | VARCHAR(60) | NULL | | |
| `color_hex` | CHAR(7) | NULL | | |

```sql
CREATE INDEX idx_conn_ver_version ON sop_step_connector_versions(sop_version_id);
```

**Lý do dùng `from_position` / `to_position` thay vì FK:**

Version connector chỉ cần biết "bước số mấy nối với bước số mấy" để render lại flowchart. Dùng position đơn giản hơn và không cần JOIN thêm.

---

### 4.7 SOP_STEP_RACI — Phân công RACI live

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK AUTO_INCREMENT | | Khóa chính nội bộ |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `step_id` | BIGINT UNSIGNED | NOT NULL | FK (sop_steps), INDEX | | CASCADE DELETE |
| `assignee_type` | ENUM | NOT NULL | | `role` | user / role |
| `assignee_id` | BIGINT UNSIGNED | NOT NULL | INDEX | | FK tới `users.id` hoặc `roles.id` — đều BIGINT trong hệ thống hiện có |
| `raci_type` | ENUM | NOT NULL | | | R / A / C / I |
| `notes` | TEXT | NULL | | NULL | Ghi chú bổ sung |

```sql
CREATE UNIQUE INDEX idx_raci_unique
  ON sop_step_raci(step_id, assignee_type, assignee_id, raci_type);
CREATE INDEX idx_raci_assignee
  ON sop_step_raci(assignee_type, assignee_id);
```

---

### 4.8 SOP_STEP_ATTACHMENT — File đính kèm bước

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK AUTO_INCREMENT | | Khóa chính nội bộ |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `step_id` | BIGINT UNSIGNED | NOT NULL | FK (sop_steps), INDEX | | CASCADE DELETE |
| `file_name` | VARCHAR(255) | NOT NULL | | | Tên file gốc |
| `file_url` | TEXT | NOT NULL | | | URL đầy đủ |
| `file_type` | VARCHAR(50) | NOT NULL | | | MIME type |
| `file_size_kb` | INT UNSIGNED | NOT NULL | | | |
| `storage_provider` | VARCHAR(20) | NOT NULL | | `s3` | s3 / gcs / local |
| `storage_key` | VARCHAR(500) | NOT NULL | | | Object key nội bộ |
| `alt_text` | VARCHAR(300) | NULL | | NULL | Mô tả file (accessibility) |
| `sort_order` | SMALLINT | NOT NULL | | 0 | |
| `uploaded_by` | BIGINT UNSIGNED | NOT NULL | FK (users) | | |
| `uploaded_at` | TIMESTAMP | NOT NULL | | NOW() | |

```sql
CREATE INDEX idx_attachment_step ON sop_step_attachments(step_id, sort_order);
```

---

### 4.9 SOP_APPROVAL_FLOW — Luồng phê duyệt phiên bản

**Mục đích:** Track từng bước trong luồng phê duyệt multi-level của một phiên bản SOP. Cho phép cấu hình flow duyệt nhiều cấp (Trưởng phòng → Giám đốc → CEO).

> **Lưu ý v3.0.0:** Bảng này có trong ERD nhưng thiếu table spec trong v2.0.0. Được bổ sung đầy đủ ở đây.

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK AUTO_INCREMENT | | Khóa chính nội bộ |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `sop_version_id` | BIGINT UNSIGNED | NOT NULL | FK (sop_versions), INDEX | | CASCADE DELETE |
| `step_order` | TINYINT UNSIGNED | NOT NULL | | 1 | Bước trong flow duyệt (1, 2, 3...) |
| `required_role` | VARCHAR(100) | NULL | | NULL | Role được yêu cầu duyệt tại bước này (tên Spatie role) |
| `approver_id` | BIGINT UNSIGNED | NULL | FK (users) | NULL | Người thực tế thực hiện hành động tại bước này |
| `action` | ENUM | NULL | | NULL | `approved` / `rejected` / `forwarded` |
| `comment` | TEXT | NULL | | NULL | Ghi chú khi duyệt / từ chối / chuyển tiếp |
| `acted_at` | TIMESTAMP | NULL | | NULL | Thời điểm hành động |
| `created_at` | TIMESTAMP | NOT NULL | | NOW() | |
| `updated_at` | TIMESTAMP | NOT NULL | | NOW() | |

```sql
CREATE INDEX idx_approval_version ON sop_approval_flows(sop_version_id, step_order);
CREATE INDEX idx_approval_approver ON sop_approval_flows(approver_id) WHERE approver_id IS NOT NULL;
```

**Logic:**

```
Khi submit phiên bản SOP:
  1. INSERT sop_approval_flows records theo cấu hình org
     (step_order = 1: Trưởng phòng, step_order = 2: Giám đốc)
  2. Gửi notification cho approver ở step_order = 1

Khi Approver hành động:
  - Approve → UPDATE action='approved', acted_at=NOW()
              → Nếu còn step tiếp theo: gửi notification cho step kế
              → Nếu là step cuối: update sop_versions.status = 'approved'
  - Reject   → UPDATE action='rejected', comment=...
              → update sop_versions.status = 'rejected'
              → Gửi notification về cho creator
```

---

### 4.10 SOP_RELATION — Quan hệ giữa các SOP

**Mục đích:** Track dependencies và relationships giữa các SOP. Cho phép biết SOP nào là tiền điều kiện, SOP nào được thay thế bởi SOP nào.

> **Lưu ý v3.0.0:** Bảng này có trong ERD nhưng thiếu table spec trong v2.0.0. Được bổ sung đầy đủ ở đây.

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK AUTO_INCREMENT | | Khóa chính nội bộ |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `sop_id` | BIGINT UNSIGNED | NOT NULL | FK (sop_processes), INDEX | | SOP gốc — RESTRICT ON DELETE |
| `related_sop_id` | BIGINT UNSIGNED | NOT NULL | FK (sop_processes) | | SOP liên quan — RESTRICT ON DELETE |
| `relation_type` | ENUM | NOT NULL | INDEX | | Xem bên dưới |
| `note` | TEXT | NULL | | NULL | Ghi chú về mối quan hệ |
| `created_by` | BIGINT UNSIGNED | NOT NULL | FK (users) | | |
| `created_at` | TIMESTAMP | NOT NULL | | NOW() | |

**relation_type values:**

| Giá trị | Mô tả |
|---|---|
| `prerequisite` | `sop_id` cần hoàn thành TRƯỚC khi thực hiện `related_sop_id` |
| `related` | Hai SOP liên quan chủ đề, nên đọc kèm nhau |
| `replaces` | `sop_id` thay thế `related_sop_id` (version mới hơn) |
| `replaced_by` | `sop_id` đã bị thay thế bởi `related_sop_id` |

```sql
CREATE UNIQUE INDEX idx_sop_relation_unique
  ON sop_relations(sop_id, related_sop_id, relation_type);
CREATE INDEX idx_sop_relation_related
  ON sop_relations(related_sop_id);
```

**Ràng buộc:** `sop_id <> related_sop_id` — không cho phép SOP tự tham chiếu chính nó.

---

## 5. Enum Values đầy đủ

### 5.1 SOP_STEP.step_type

| Giá trị | Shape trên flowchart | Mô tả |
|---|---|---|
| `start` | Hình tròn / oval | Điểm bắt đầu quy trình |
| `end` | Hình tròn / oval viền đôi | Điểm kết thúc quy trình |
| `action` | Hình chữ nhật | Bước thực hiện thông thường |
| `decision` | Hình thoi | Rẽ nhánh Yes/No hoặc nhiều điều kiện |
| `sub_sop` | Hình chữ nhật viền đôi | Gọi đến SOP khác (ref_sop_id) |
| `notification` | Hình bình hành | Gửi thông báo/email, không chờ phản hồi |
| `wait` | Hình chữ nhật bo góc mạnh | Chờ điều kiện hoặc khoảng thời gian |

### 5.2 SOP_STEP_CONNECTOR.connector_type

| Giá trị | Màu default | Style | Mô tả |
|---|---|---|---|
| `sequence` | #B4B2A9 | Mũi tên liền | Luồng tuần tự thông thường |
| `yes_branch` | #639922 | Mũi tên liền xanh | Nhánh Yes từ decision |
| `no_branch` | #E24B4A | Mũi tên liền đỏ | Nhánh No từ decision |
| `trigger` | #7F77DD | Mũi tên đứt | Kích hoạt bước không tuần tự |
| `return` | #EF9F27 | Mũi tên cong quay về | Vòng lặp quay về bước trước |
| `exception` | #E24B4A | Mũi tên đứt đỏ | Luồng xử lý lỗi / exception |

### 5.3 SOP_STEP_VERSION.change_type

| Giá trị | Màu diff | Mô tả |
|---|---|---|
| `unchanged` | — | Bước không thay đổi so với version trước |
| `added` | #639922 (xanh) | Bước mới được thêm vào version này |
| `modified` | #EF9F27 (vàng) | Bước đã được chỉnh sửa |
| `deleted` | #E24B4A (đỏ) | Bước đã bị xóa trong version này |

### 5.4 SOP_PROCESS.type

| Giá trị | Mô tả |
|---|---|
| `internal` | Quy trình vận hành nội bộ thông thường |
| `regulatory` | Quy trình tuân thủ quy định pháp luật / ISO |
| `training` | Quy trình đào tạo nhân viên mới |
| `emergency` | Quy trình xử lý sự cố / khẩn cấp |

### 5.5 SOP_APPROVAL_FLOW.action

| Giá trị | Mô tả |
|---|---|
| `approved` | Người duyệt chấp thuận tại bước này |
| `rejected` | Người duyệt từ chối — dừng toàn bộ flow |
| `forwarded` | Chuyển tiếp sang người khác cùng bước |

---

## 6. Migration SQL (Laravel)

### 6.0 SOP_PROCESS

```php
Schema::create('sop_processes', function (Blueprint $table) {
    $table->id();
    $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
    $table->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
    $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
    $table->string('code', 50);
    $table->string('title', 300);
    $table->text('description')->nullable();
    $table->enum('type', ['internal', 'regulatory', 'training', 'emergency'])->default('internal')->index();
    $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected', 'archived'])
          ->default('draft')->index();
    $table->smallInteger('version')->unsigned()->default(0);
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('approved_at')->nullable();
    $table->date('effective_date')->nullable();
    $table->date('expired_date')->nullable();
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->unique(['organization_id', 'code'], 'idx_sop_proc_code');
    $table->index(['organization_id', 'status'], 'idx_sop_proc_status');
    $table->index('expired_date', 'idx_sop_proc_expired');
});
```

### 6.1 SOP_STEP

```php
Schema::create('sop_steps', function (Blueprint $table) {
    $table->id();
    $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
    $table->foreignId('sop_id')->constrained('sop_processes')->cascadeOnDelete();
    $table->smallInteger('position')->unsigned();
    $table->string('title', 200);
    $table->text('description')->nullable();
    $table->text('expected_output')->nullable();
    $table->text('warning_note')->nullable();
    $table->enum('step_type', [
        'start', 'end', 'action', 'decision',
        'sub_sop', 'notification', 'wait'
    ])->default('action')->index();
    $table->foreignId('ref_sop_id')
          ->nullable()
          ->constrained('sop_processes')
          ->nullOnDelete();
    $table->smallInteger('branch_yes_position')->unsigned()->nullable();
    $table->smallInteger('branch_no_position')->unsigned()->nullable();
    $table->smallInteger('duration_minutes')->unsigned()->nullable();
    $table->boolean('is_mandatory')->default(true);
    $table->boolean('is_active')->default(true)->index();
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();

    $table->unique(['sop_id', 'position'], 'idx_sop_step_pos_unique');
    $table->index(['sop_id', 'is_active', 'position'], 'idx_step_render');
    $table->index(['sop_id', 'step_type', 'is_active'], 'idx_step_type_filter');
    $table->index('ref_sop_id', 'idx_step_ref_sop');
});
```

### 6.2 SOP_STEP_CONNECTOR

```php
Schema::create('sop_step_connectors', function (Blueprint $table) {
    $table->id();
    $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
    $table->foreignId('sop_id')->constrained('sop_processes')->cascadeOnDelete();
    $table->foreignId('from_step_id')->constrained('sop_steps')->cascadeOnDelete();
    $table->foreignId('to_step_id')->constrained('sop_steps')->cascadeOnDelete();
    $table->enum('connector_type', [
        'sequence', 'yes_branch', 'no_branch',
        'trigger', 'return', 'exception'
    ])->default('sequence')->index();
    $table->string('label', 60)->nullable();
    $table->char('color_hex', 7)->nullable();
    $table->smallInteger('sort_order')->default(0);

    $table->unique(['from_step_id', 'to_step_id', 'connector_type'], 'idx_conn_unique');
    $table->index('sop_id', 'idx_conn_sop');
    $table->index('from_step_id', 'idx_conn_from');
    $table->index('to_step_id', 'idx_conn_to');
});
```

### 6.3 SOP_VERSION

```php
Schema::create('sop_versions', function (Blueprint $table) {
    $table->id();
    $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
    $table->foreignId('sop_id')->constrained('sop_processes')->cascadeOnDelete();
    $table->unsignedInteger('version_number');
    $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft')->index();
    $table->text('change_summary')->nullable();
    $table->smallInteger('total_steps')->unsigned()->default(0);
    $table->unsignedInteger('total_duration_minutes')->default(0);
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('approved_at')->nullable();
    $table->timestamp('created_at')->useCurrent();

    $table->unique(['sop_id', 'version_number'], 'idx_version_num');
    $table->index(['sop_id', 'status'], 'idx_version_status');
});
```

### 6.4 SOP_STEP_VERSION

```php
Schema::create('sop_step_versions', function (Blueprint $table) {
    $table->id();
    $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
    $table->foreignId('sop_version_id')->constrained('sop_versions')->cascadeOnDelete();
    $table->foreignId('original_step_id')
          ->nullable()
          ->constrained('sop_steps')
          ->nullOnDelete(); // SET NULL — giữ history kể cả khi bước gốc bị xóa
    $table->smallInteger('position')->unsigned();
    $table->string('title', 200);
    $table->text('description')->nullable();
    $table->text('expected_output')->nullable();
    $table->text('warning_note')->nullable();
    $table->enum('step_type', [
        'start', 'end', 'action', 'decision',
        'sub_sop', 'notification', 'wait'
    ]);
    // Snapshot — không có FK constraint, SOP đích có thể bị xóa sau khi snapshot
    $table->unsignedBigInteger('ref_sop_id')->nullable();
    $table->string('ref_sop_code', 50)->nullable(); // Snapshot code để hiển thị
    $table->smallInteger('branch_yes_position')->unsigned()->nullable();
    $table->smallInteger('branch_no_position')->unsigned()->nullable();
    $table->smallInteger('duration_minutes')->unsigned()->nullable();
    $table->boolean('is_mandatory')->default(true);
    $table->enum('change_type', ['added', 'modified', 'deleted', 'unchanged'])->default('unchanged');

    $table->index(['sop_version_id', 'position'], 'idx_step_ver_pos');
    $table->index('original_step_id', 'idx_step_ver_orig');
    $table->index(['sop_version_id', 'change_type'], 'idx_step_ver_diff');
});
```

### 6.5 SOP_STEP_RACI_VERSION

```php
Schema::create('sop_step_raci_versions', function (Blueprint $table) {
    $table->id();
    $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
    $table->foreignId('sop_version_id')->constrained('sop_versions')->cascadeOnDelete();
    $table->foreignId('step_version_id')->constrained('sop_step_versions')->cascadeOnDelete();
    $table->smallInteger('step_position')->unsigned();
    $table->enum('assignee_type', ['user', 'role']);
    $table->unsignedBigInteger('assignee_id'); // Snapshot BIGINT — không FK
    $table->string('assignee_name', 150);       // Snapshot tên — hiển thị khi user/role bị xóa
    $table->enum('raci_type', ['R', 'A', 'C', 'I']);

    $table->index('sop_version_id', 'idx_raci_ver_version');
    $table->index('step_version_id', 'idx_raci_ver_step');
});
```

### 6.6 SOP_STEP_CONNECTOR_VERSION

```php
Schema::create('sop_step_connector_versions', function (Blueprint $table) {
    $table->id();
    $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
    $table->foreignId('sop_version_id')->constrained('sop_versions')->cascadeOnDelete();
    $table->smallInteger('from_position')->unsigned();
    $table->smallInteger('to_position')->unsigned();
    $table->enum('connector_type', [
        'sequence', 'yes_branch', 'no_branch', 'trigger', 'return', 'exception'
    ]);
    $table->string('label', 60)->nullable();
    $table->char('color_hex', 7)->nullable();

    $table->index('sop_version_id', 'idx_conn_ver_version');
});
```

### 6.7 SOP_STEP_RACI

```php
Schema::create('sop_step_raci', function (Blueprint $table) {
    $table->id();
    $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
    $table->foreignId('step_id')->constrained('sop_steps')->cascadeOnDelete();
    $table->enum('assignee_type', ['user', 'role'])->default('role');
    // Polymorphic — tới users.id hoặc roles.id, đều là BIGINT
    $table->unsignedBigInteger('assignee_id')->index();
    $table->enum('raci_type', ['R', 'A', 'C', 'I']);
    $table->text('notes')->nullable();

    $table->unique(['step_id', 'assignee_type', 'assignee_id', 'raci_type'], 'idx_raci_unique');
    $table->index(['assignee_type', 'assignee_id'], 'idx_raci_assignee');
});
```

### 6.8 SOP_STEP_ATTACHMENT

```php
Schema::create('sop_step_attachments', function (Blueprint $table) {
    $table->id();
    $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
    $table->foreignId('step_id')->constrained('sop_steps')->cascadeOnDelete();
    $table->string('file_name', 255);
    $table->text('file_url');
    $table->string('file_type', 50);
    $table->unsignedInteger('file_size_kb');
    $table->string('storage_provider', 20)->default('s3');
    $table->string('storage_key', 500);
    $table->string('alt_text', 300)->nullable();
    $table->smallInteger('sort_order')->default(0);
    $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
    $table->timestamp('uploaded_at')->useCurrent();

    $table->index(['step_id', 'sort_order'], 'idx_attachment_step');
});
```

### 6.9 SOP_APPROVAL_FLOW

```php
Schema::create('sop_approval_flows', function (Blueprint $table) {
    $table->id();
    $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
    $table->foreignId('sop_version_id')->constrained('sop_versions')->cascadeOnDelete();
    $table->tinyInteger('step_order')->unsigned()->default(1);
    $table->string('required_role', 100)->nullable();
    $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
    $table->enum('action', ['approved', 'rejected', 'forwarded'])->nullable();
    $table->text('comment')->nullable();
    $table->timestamp('acted_at')->nullable();
    $table->timestamps();

    $table->index(['sop_version_id', 'step_order'], 'idx_approval_version');
    $table->index('approver_id', 'idx_approval_approver');
});
```

### 6.10 SOP_RELATION

```php
Schema::create('sop_relations', function (Blueprint $table) {
    $table->id();
    $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
    $table->foreignId('sop_id')->constrained('sop_processes')->restrictOnDelete();
    $table->foreignId('related_sop_id')->constrained('sop_processes')->restrictOnDelete();
    $table->enum('relation_type', ['prerequisite', 'related', 'replaces', 'replaced_by'])->index();
    $table->text('note')->nullable();
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->timestamp('created_at')->useCurrent();

    $table->unique(['sop_id', 'related_sop_id', 'relation_type'], 'idx_sop_rel_unique');
    $table->index('related_sop_id', 'idx_sop_rel_related');
});
```

---

## 7. Query Patterns — Truy vấn tối ưu

### 7.1 Render toàn bộ flowchart — 4 queries

```php
public function getFlowchartData(int $sopId): array
{
    // Query 1: Steps (chỉ lấy active) — dùng BIGINT id
    $steps = DB::table('sop_steps as s')
        ->leftJoin('sop_processes as ref', 'ref.id', '=', 's.ref_sop_id')
        ->where('s.sop_id', $sopId)
        ->where('s.is_active', true)
        ->orderBy('s.position')
        ->select([
            's.id', 's.uuid', 's.position', 's.title', 's.description',
            's.expected_output', 's.warning_note',
            's.step_type', 's.duration_minutes', 's.is_mandatory',
            's.branch_yes_position', 's.branch_no_position',
            'ref.code as ref_sop_code',
            'ref.title as ref_sop_title',
        ])
        ->get();

    // Query 2: Connectors — from/to dùng BIGINT step id
    $connectors = DB::table('sop_step_connectors')
        ->where('sop_id', $sopId)
        ->orderBy('sort_order')
        ->select(['id', 'uuid', 'from_step_id', 'to_step_id',
                  'connector_type', 'label', 'color_hex'])
        ->get();

    // Query 3: RACI — 1 query, không N+1
    $stepIds = $steps->pluck('id');
    $raci = DB::table('sop_step_raci as r')
        ->whereIn('r.step_id', $stepIds)
        ->leftJoin('users as u',
            fn($j) => $j->on('u.id', '=', 'r.assignee_id')
                        ->where('r.assignee_type', 'user'))
        ->leftJoin('roles as ro',
            fn($j) => $j->on('ro.id', '=', 'r.assignee_id')
                        ->where('r.assignee_type', 'role'))
        ->select([
            'r.step_id', 'r.raci_type', 'r.assignee_type',
            DB::raw("COALESCE(u.name, ro.name) as assignee_name"),
        ])
        ->get()
        ->groupBy('step_id');

    // Query 4: Attachment count per step
    $attachmentCounts = DB::table('sop_step_attachments')
        ->whereIn('step_id', $stepIds)
        ->select('step_id', DB::raw('COUNT(*) as count'))
        ->groupBy('step_id')
        ->pluck('count', 'step_id');

    $stepsWithData = $steps->map(fn($step) => [
        ...(array) $step,
        'raci'             => $raci->get($step->id, collect())->values(),
        'attachment_count' => $attachmentCounts->get($step->id, 0),
    ]);

    return [
        'steps'      => $stepsWithData,
        'connectors' => $connectors,
        'total_duration'  => $steps->sum('duration_minutes'),
        'mandatory_count' => $steps->where('is_mandatory', true)->count(),
    ];
}
```

**Kết quả: 4 queries, không N+1.**

---

### 7.2 Reorder bước khi drag & drop

```php
public function reorderSteps(int $sopId, array $orderedIds): void
{
    // orderedIds = [3, 1, 2] (BIGINT ids theo thứ tự mới)
    DB::transaction(function () use ($sopId, $orderedIds) {
        $cases = collect($orderedIds)
            ->map(fn($id, $i) => "WHEN $id THEN " . ($i + 1))
            ->implode(' ');

        DB::statement("
            UPDATE sop_steps
            SET position = CASE id $cases END,
                updated_at = NOW()
            WHERE sop_id = ?
              AND id IN (?" . str_repeat(', ?', count($orderedIds) - 1) . ")",
            [$sopId, ...$orderedIds]
        );
    });
}
```

---

### 7.3 Lấy SOP có bước sub_sop trỏ đến 1 SOP cụ thể

```sql
SELECT DISTINCT
    sp.code,
    sp.title,
    sp.status,
    ss.position,
    ss.title AS step_title
FROM sop_steps ss
JOIN sop_processes sp ON sp.id = ss.sop_id
WHERE ss.ref_sop_id = :target_sop_id   -- BIGINT FK
  AND ss.step_type = 'sub_sop'
  AND ss.is_active = TRUE
  AND sp.status = 'approved';
```

---

### 7.4 Analytics — Bước nào mất nhiều thời gian nhất

```sql
SELECT
    s.step_type,
    COUNT(*) as step_count,
    AVG(s.duration_minutes) as avg_duration,
    MAX(s.duration_minutes) as max_duration
FROM sop_steps s
JOIN sop_processes sp ON sp.id = s.sop_id
WHERE sp.organization_id = :org_id    -- BIGINT FK
  AND sp.status = 'approved'
  AND s.is_active = TRUE
  AND s.duration_minutes IS NOT NULL
GROUP BY s.step_type
ORDER BY avg_duration DESC;
```

---

### 7.5 Diff 2 phiên bản — chỉ lấy bước thay đổi

```php
public function getDiff(int $sopId, int $fromVersion, int $toVersion): array
{
    $changed = DB::table('sop_step_versions as sv')
        ->join('sop_versions as v', 'v.id', '=', 'sv.sop_version_id')
        ->where('v.sop_id', $sopId)
        ->where('v.version_number', $toVersion)
        ->where('sv.change_type', '!=', 'unchanged')
        ->select(['sv.*'])
        ->orderBy('sv.position')
        ->get();

    $originalPositions = $changed->pluck('position');
    $originals = DB::table('sop_step_versions as sv')
        ->join('sop_versions as v', 'v.id', '=', 'sv.sop_version_id')
        ->where('v.sop_id', $sopId)
        ->where('v.version_number', $fromVersion)
        ->whereIn('sv.position', $originalPositions)
        ->select(['sv.position', 'sv.title', 'sv.description',
                  'sv.step_type', 'sv.duration_minutes'])
        ->get()
        ->keyBy('position');

    return $changed->map(fn($step) => [
        'position'    => $step->position,
        'change_type' => $step->change_type,
        'new'         => $step,
        'old'         => $originals->get($step->position),
    ])->all();
}
```

---

### 7.6 Rollback về version cũ

```php
public function rollbackToVersion(int $sopId, int $targetVersion): void
{
    DB::transaction(function () use ($sopId, $targetVersion) {
        $version = SopVersion::where('sop_id', $sopId)
                             ->where('version_number', $targetVersion)
                             ->firstOrFail();

        $stepVersions = SopStepVersion::where('sop_version_id', $version->id)
                                       ->orderBy('position')
                                       ->get();

        // 1. Soft delete tất cả bước active hiện tại
        SopStep::where('sop_id', $sopId)
               ->where('is_active', true)
               ->update(['is_active' => false, 'updated_at' => now()]);

        // 2. Xóa connector hiện tại
        SopStepConnector::where('sop_id', $sopId)->delete();

        // 3. Insert lại bước từ snapshot
        $newStepMap = []; // old_position => new_step id (BIGINT)
        foreach ($stepVersions as $sv) {
            $newStep = SopStep::create([
                'sop_id'               => $sopId,
                'position'             => $sv->position,
                'title'                => $sv->title,
                'description'          => $sv->description,
                'expected_output'      => $sv->expected_output,
                'warning_note'         => $sv->warning_note,
                'step_type'            => $sv->step_type,
                'ref_sop_id'           => $sv->ref_sop_id,   // BIGINT snapshot
                'branch_yes_position'  => $sv->branch_yes_position,
                'branch_no_position'   => $sv->branch_no_position,
                'duration_minutes'     => $sv->duration_minutes,
                'is_mandatory'         => $sv->is_mandatory,
                'is_active'            => true,
                'created_by'           => auth()->id(), // BIGINT
            ]);
            $newStepMap[$sv->position] = $newStep->id; // BIGINT
        }

        // 4. Rebuild connectors từ snapshot version
        $connVersions = SopStepConnectorVersion::where('sop_version_id', $version->id)->get();
        foreach ($connVersions as $cv) {
            $fromId = $newStepMap[$cv->from_position] ?? null;
            $toId   = $newStepMap[$cv->to_position] ?? null;
            if ($fromId && $toId) {
                SopStepConnector::create([
                    'sop_id'         => $sopId,
                    'from_step_id'   => $fromId,   // BIGINT
                    'to_step_id'     => $toId,     // BIGINT
                    'connector_type' => $cv->connector_type,
                    'label'          => $cv->label,
                    'color_hex'      => $cv->color_hex,
                ]);
            }
        }

        // 5. Rebuild RACI từ snapshot
        $raciVersions = SopStepRaciVersion::where('sop_version_id', $version->id)->get();
        foreach ($raciVersions as $rv) {
            $newStepId = $newStepMap[$rv->step_position] ?? null;
            if ($newStepId) {
                SopStepRaci::create([
                    'step_id'       => $newStepId,        // BIGINT
                    'assignee_type' => $rv->assignee_type,
                    'assignee_id'   => $rv->assignee_id,  // BIGINT snapshot
                    'raci_type'     => $rv->raci_type,
                ]);
            }
        }

        // 6. Đặt SOP về draft để duyệt lại
        SopProcess::where('id', $sopId)->update([
            'status'     => 'draft',
            'updated_at' => now(),
        ]);
    });
}
```

---

## 8. Eloquent Models & Relationships

```php
// Modules/Sop/app/Models/SopProcess.php
class SopProcess extends Model
{
    use SoftDeletes;

    protected $casts = [
        'status'         => SopStatus::class,
        'type'           => SopType::class,
        'effective_date' => 'date',
        'expired_date'   => 'date',
    ];

    // Route model binding dùng UUID (không expose BIGINT id)
    public function getRouteKeyName(): string { return 'uuid'; }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(SopStep::class, 'sop_id')->orderBy('position');
    }

    public function activeSteps(): HasMany
    {
        return $this->steps()->where('is_active', true);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(SopVersion::class, 'sop_id');
    }

    public function relations(): HasMany
    {
        return $this->hasMany(SopRelation::class, 'sop_id');
    }
}

// Modules/Sop/app/Models/SopStep.php
class SopStep extends Model
{
    protected $casts = [
        'step_type'    => StepType::class,
        'is_mandatory' => 'boolean',
        'is_active'    => 'boolean',
    ];

    public function getRouteKeyName(): string { return 'uuid'; }

    public function sop(): BelongsTo
    {
        return $this->belongsTo(SopProcess::class, 'sop_id');
    }

    public function refSop(): BelongsTo
    {
        return $this->belongsTo(SopProcess::class, 'ref_sop_id');
    }

    public function raci(): HasMany
    {
        return $this->hasMany(SopStepRaci::class, 'step_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SopStepAttachment::class, 'step_id')
                    ->orderBy('sort_order');
    }

    public function outgoingConnectors(): HasMany
    {
        return $this->hasMany(SopStepConnector::class, 'from_step_id');
    }

    public function incomingConnectors(): HasMany
    {
        return $this->hasMany(SopStepConnector::class, 'to_step_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

// Modules/Sop/app/Models/SopStepConnector.php
class SopStepConnector extends Model
{
    public $timestamps = false;

    protected $casts = [
        'connector_type' => ConnectorType::class,
    ];

    public function fromStep(): BelongsTo
    {
        return $this->belongsTo(SopStep::class, 'from_step_id');
    }

    public function toStep(): BelongsTo
    {
        return $this->belongsTo(SopStep::class, 'to_step_id');
    }

    public function getDisplayColorAttribute(): string
    {
        if ($this->color_hex) return $this->color_hex;
        return match($this->connector_type) {
            ConnectorType::YesBranch  => '#639922',
            ConnectorType::NoBranch   => '#E24B4A',
            ConnectorType::Trigger    => '#7F77DD',
            ConnectorType::Return     => '#EF9F27',
            ConnectorType::Exception  => '#E24B4A',
            default                   => '#B4B2A9',
        };
    }
}
```

---

## 9. Versioning Logic — Không JSON

### Snapshot khi Approve

```php
public function createSnapshot(int $sopId, int $approverId): SopVersion
{
    return DB::transaction(function () use ($sopId, $approverId) {
        $steps = SopStep::where('sop_id', $sopId)
                        ->active()
                        ->orderBy('position')
                        ->with(['raci'])
                        ->get();

        $connectors = SopStepConnector::where('sop_id', $sopId)->get();

        $prevVersion = SopVersion::where('sop_id', $sopId)
                                  ->where('status', 'approved')
                                  ->latest('version_number')
                                  ->first();

        $newVersionNum = ($prevVersion?->version_number ?? 0) + 1;

        $version = SopVersion::create([
            'sop_id'                  => $sopId,
            'version_number'          => $newVersionNum,
            'status'                  => 'approved',
            'total_steps'             => $steps->count(),
            'total_duration_minutes'  => $steps->sum('duration_minutes'),
            'approved_by'             => $approverId, // BIGINT
            'approved_at'             => now(),
            'created_by'              => auth()->id(), // BIGINT
        ]);

        $prevStepsByPos = $prevVersion
            ? SopStepVersion::where('sop_version_id', $prevVersion->id)
                             ->get()->keyBy('position')
            : collect();

        $stepVersionMap = []; // position => step_version id (BIGINT)
        foreach ($steps as $step) {
            $prev = $prevStepsByPos->get($step->position);
            $changeType = $this->detectChangeType($step, $prev);

            $sv = SopStepVersion::create([
                'sop_version_id'       => $version->id,
                'original_step_id'     => $step->id,      // BIGINT FK
                'position'             => $step->position,
                'title'                => $step->title,
                'description'          => $step->description,
                'expected_output'      => $step->expected_output,
                'warning_note'         => $step->warning_note,
                'step_type'            => $step->step_type,
                'ref_sop_id'           => $step->ref_sop_id,   // BIGINT snapshot
                'ref_sop_code'         => $step->refSop?->code,
                'branch_yes_position'  => $step->branch_yes_position,
                'branch_no_position'   => $step->branch_no_position,
                'duration_minutes'     => $step->duration_minutes,
                'is_mandatory'         => $step->is_mandatory,
                'change_type'          => $changeType,
            ]);
            $stepVersionMap[$step->position] = $sv->id;

            foreach ($step->raci as $r) {
                SopStepRaciVersion::create([
                    'sop_version_id'  => $version->id,
                    'step_version_id' => $sv->id,
                    'step_position'   => $step->position,
                    'assignee_type'   => $r->assignee_type,
                    'assignee_id'     => $r->assignee_id,  // BIGINT snapshot
                    'assignee_name'   => $r->assigneeName(),
                    'raci_type'       => $r->raci_type,
                ]);
            }
        }

        // Snapshot connectors — dùng position, không step id
        $stepPositionMap = $steps->pluck('position', 'id'); // BIGINT id => position
        foreach ($connectors as $conn) {
            SopStepConnectorVersion::create([
                'sop_version_id' => $version->id,
                'from_position'  => $stepPositionMap[$conn->from_step_id],
                'to_position'    => $stepPositionMap[$conn->to_step_id],
                'connector_type' => $conn->connector_type,
                'label'          => $conn->label,
                'color_hex'      => $conn->color_hex,
            ]);
        }

        SopProcess::where('id', $sopId)->update([
            'status'      => 'approved',
            'version'     => $newVersionNum,
            'approved_by' => $approverId,
            'approved_at' => now(),
        ]);

        return $version;
    });
}

private function detectChangeType(SopStep $step, ?SopStepVersion $prev): string
{
    if (!$prev) return 'added';
    $fields = ['title', 'description', 'expected_output', 'step_type',
               'duration_minutes', 'is_mandatory', 'warning_note'];
    foreach ($fields as $f) {
        if ($step->$f !== $prev->$f) return 'modified';
    }
    return 'unchanged';
}
```

---

## 10. Render Flowchart — Alpine.js + SVG

### Controller endpoint

```php
// Modules/Sop/app/Http/Controllers/SopFlowchartController.php
public function data(SopProcess $sop): JsonResponse
{
    // Route model binding dùng uuid — SopProcess::getRouteKeyName() = 'uuid'
    $this->authorize('view', $sop);

    $data = app(SopFlowchartRepository::class)->getFlowchartData($sop->id);

    $shapeMap = [
        'start'        => ['shape' => 'oval',         'color' => '#1D9E75', 'fill' => '#E1F5EE'],
        'end'          => ['shape' => 'oval_double',   'color' => '#1D9E75', 'fill' => '#E1F5EE'],
        'action'       => ['shape' => 'rect',          'color' => '#378ADD', 'fill' => '#E6F1FB'],
        'decision'     => ['shape' => 'diamond',       'color' => '#EF9F27', 'fill' => '#FAEEDA'],
        'sub_sop'      => ['shape' => 'rect_double',   'color' => '#1D9E75', 'fill' => '#E1F5EE'],
        'notification' => ['shape' => 'parallelogram', 'color' => '#7F77DD', 'fill' => '#EEEDFE'],
        'wait'         => ['shape' => 'rounded_rect',  'color' => '#888780', 'fill' => '#F1EFE8'],
    ];

    $data['steps'] = $data['steps']->map(fn($step) => array_merge(
        (array) $step,
        $shapeMap[$step['step_type']] ?? $shapeMap['action']
    ));

    return response()->json($data);
}
```

### Alpine.js component (tích hợp vào Blade)

```html
{{-- Modules/Sop/resources/views/partials/flowchart.blade.php --}}
<div
    x-data="sopFlowchart('{{ route('sop.flowchart.data', $sop->uuid) }}')"
    x-init="load()"
    class="relative"
>
    {{-- Toolbar --}}
    <div class="flex items-center gap-3 mb-3 text-sm">
        <span class="text-gray-400" x-text="`${meta.step_count} bước · ${meta.total_duration} phút`"></span>
        <button @click="zoomIn()" class="btn-icon" title="Phóng to">+</button>
        <button @click="zoomOut()" class="btn-icon" title="Thu nhỏ">−</button>
        <button @click="fitToScreen()" class="btn-icon" title="Vừa màn hình">⊡</button>
        <label class="flex items-center gap-1 text-gray-400 cursor-pointer">
            <input type="checkbox" x-model="showDuration" class="rounded">
            Hiện thời gian
        </label>
    </div>

    {{-- SVG Canvas --}}
    <div class="overflow-auto border rounded-lg bg-gray-50" style="max-height: 420px">
        <svg
            :width="canvas.width"
            :height="canvas.height"
            :viewBox="`0 0 ${canvas.width} ${canvas.height}`"
            x-ref="svg"
        >
            <defs>
                <marker id="arr-gray" viewBox="0 0 10 10" refX="8" refY="5"
                    markerWidth="5" markerHeight="5" orient="auto-start-reverse">
                    <path d="M2 1L8 5L2 9" fill="none" stroke="#B4B2A9" stroke-width="1.5" stroke-linecap="round"/>
                </marker>
                <marker id="arr-green" viewBox="0 0 10 10" refX="8" refY="5"
                    markerWidth="5" markerHeight="5" orient="auto-start-reverse">
                    <path d="M2 1L8 5L2 9" fill="none" stroke="#639922" stroke-width="1.5" stroke-linecap="round"/>
                </marker>
                <marker id="arr-red" viewBox="0 0 10 10" refX="8" refY="5"
                    markerWidth="5" markerHeight="5" orient="auto-start-reverse">
                    <path d="M2 1L8 5L2 9" fill="none" stroke="#E24B4A" stroke-width="1.5" stroke-linecap="round"/>
                </marker>
            </defs>

            {{-- Connectors --}}
            <template x-for="conn in layout.connectors" :key="conn.id">
                <g>
                    <path :d="conn.path" fill="none" :stroke="conn.color"
                          stroke-width="1.2" :stroke-dasharray="conn.dashed ? '5 3' : 'none'"
                          :marker-end="`url(#arr-${conn.markerColor})`"/>
                    <text x-show="conn.label" :x="conn.labelX" :y="conn.labelY"
                          text-anchor="middle" font-size="10" :fill="conn.color"
                          font-family="sans-serif" x-text="conn.label"/>
                </g>
            </template>

            {{-- Nodes --}}
            <template x-for="node in layout.nodes" :key="node.id">
                <g class="cursor-pointer" @click="selectStep(node)"
                   :opacity="selected && selected.id !== node.id ? 0.55 : 1"
                   style="transition: opacity 0.15s">
                    <template x-if="node.shape === 'rect' || node.shape === 'rect_double' || node.shape === 'rounded_rect'">
                        <g>
                            <rect :x="node.x" :y="node.y" :width="node.w" :height="node.h"
                                  :rx="node.shape === 'rounded_rect' ? 16 : 6"
                                  :fill="node.fill" :stroke="node.color"
                                  :stroke-width="selected?.id === node.id ? 2 : 0.8"/>
                            <template x-if="node.shape === 'rect_double'">
                                <rect :x="node.x + 3" :y="node.y + 3"
                                      :width="node.w - 6" :height="node.h - 6"
                                      rx="3" fill="none" :stroke="node.color" stroke-width="0.5"/>
                            </template>
                        </g>
                    </template>
                    <template x-if="node.shape === 'diamond'">
                        <path :d="`M${node.cx},${node.y} L${node.x+node.w},${node.cy} L${node.cx},${node.y+node.h} L${node.x},${node.cy} Z`"
                              :fill="node.fill" :stroke="node.color"
                              :stroke-width="selected?.id === node.id ? 2 : 0.8"/>
                    </template>
                    <template x-if="node.shape === 'oval' || node.shape === 'oval_double'">
                        <ellipse :cx="node.cx" :cy="node.cy" :rx="node.w / 2" :ry="node.h / 2"
                                 :fill="node.fill" :stroke="node.color"
                                 :stroke-width="selected?.id === node.id ? 2 : 1"/>
                    </template>
                    <text :x="node.cx"
                          :y="node.cy - (showDuration && node.duration_minutes ? 7 : 0)"
                          text-anchor="middle" dominant-baseline="central"
                          font-size="10" font-weight="500" :fill="node.color"
                          font-family="sans-serif" x-text="node.shortTitle"/>
                    <text x-show="showDuration && node.duration_minutes"
                          :x="node.cx" :y="node.cy + 10"
                          text-anchor="middle" dominant-baseline="central"
                          font-size="9" :fill="node.color" font-family="sans-serif"
                          opacity="0.7" x-text="`${node.duration_minutes} ph`"/>
                </g>
            </template>
        </svg>
    </div>

    {{-- Step Detail Panel --}}
    <div x-show="selected"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="mt-3 p-3 rounded-lg border-l-2 bg-gray-50"
         :style="`border-color: ${selected?.color}`">
        <template x-if="selected">
            <div>
                <div class="flex justify-between items-center mb-2">
                    <span class="font-medium text-sm"
                          x-text="`Bước ${selected.position}: ${selected.title}`"></span>
                    <button @click="selected = null" class="text-gray-400 hover:text-gray-600 text-lg leading-none">&times;</button>
                </div>
                <p class="text-xs text-gray-500 leading-relaxed mb-2" x-text="selected.description"></p>
                <div x-show="selected.expected_output" class="text-xs text-gray-400 mb-2"
                     x-text="`→ ${selected.expected_output}`"></div>
                <div x-show="selected.warning_note"
                     class="text-xs text-amber-600 bg-amber-50 p-2 rounded mb-2"
                     x-text="`⚠ ${selected.warning_note}`"></div>
                <div class="flex gap-1 flex-wrap">
                    <template x-for="r in selected.raci" :key="r.raci_type + r.assignee_name">
                        <span class="text-xs px-2 py-0.5 rounded font-medium"
                              :class="{
                                'bg-blue-50 text-blue-700': r.raci_type === 'R',
                                'bg-amber-50 text-amber-700': r.raci_type === 'A',
                                'bg-teal-50 text-teal-700': r.raci_type === 'C',
                                'bg-gray-100 text-gray-600': r.raci_type === 'I',
                              }"
                              x-text="`${r.raci_type}: ${r.assignee_name}`"/>
                    </template>
                </div>
                <div x-show="selected.step_type === 'sub_sop' && selected.ref_sop_code" class="mt-2">
                    <a :href="`/sop/${selected.ref_sop_code}`"
                       class="text-xs text-blue-600 hover:underline"
                       x-text="`↗ Xem SOP con: ${selected.ref_sop_code} — ${selected.ref_sop_title}`">
                    </a>
                </div>
            </div>
        </template>
    </div>
</div>
```

### Alpine.js Layout Engine

```js
// Modules/Sop/resources/js/components/sop-flowchart.js
document.addEventListener('alpine:init', () => {
    Alpine.data('sopFlowchart', (dataUrl) => ({
        steps: [], connectors: [], meta: {},
        layout: { nodes: [], connectors: [] },
        canvas: { width: 800, height: 200 },
        selected: null, showDuration: false, loading: true,

        NODE_W: 96, NODE_H: 64, DIA_W: 90, DIA_H: 58,
        OVAL_W: 72, OVAL_H: 44, GAP_X: 40,
        START_X: 24, START_Y: 28, BRANCH_DROP: 86,

        async load() {
            const res = await fetch(dataUrl);
            const data = await res.json();
            this.steps = data.steps;
            this.connectors = data.connectors;
            this.meta = { step_count: data.steps.length, total_duration: data.total_duration };
            this.buildLayout();
            this.loading = false;
        },

        buildLayout() {
            const nodes = this.computeNodes();
            const connLines = this.computeConnectors(nodes);
            const maxX = Math.max(...nodes.map(n => n.x + n.w)) + this.START_X;
            const maxY = Math.max(...nodes.map(n => n.y + n.h)) + this.BRANCH_DROP + 40;
            this.canvas = { width: maxX, height: maxY };
            this.layout = { nodes, connectors: connLines };
        },

        computeNodes() {
            const nodes = [];
            let cx = this.START_X;
            this.steps.forEach(step => {
                const isDec  = step.step_type === 'decision';
                const isOval = ['start', 'end'].includes(step.step_type);
                const w = isDec ? this.DIA_W : isOval ? this.OVAL_W : this.NODE_W;
                const h = isDec ? this.DIA_H : isOval ? this.OVAL_H : this.NODE_H;
                const x = cx, y = this.START_Y;
                nodes.push({
                    ...step, x, y, w, h,
                    cx: x + w / 2, cy: y + h / 2,
                    shortTitle: step.title.length > 14 ? step.title.slice(0, 13) + '…' : step.title,
                });
                cx += w + this.GAP_X;
            });
            return nodes;
        },

        computeConnectors(nodes) {
            // from_step_id / to_step_id đều là BIGINT, map bằng id số nguyên
            const nodeById = Object.fromEntries(nodes.map(n => [n.id, n]));
            const lines = [];
            this.connectors.forEach(conn => {
                const from = nodeById[conn.from_step_id];
                const to   = nodeById[conn.to_step_id];
                if (!from || !to) return;
                const colorMap = {
                    sequence: '#B4B2A9', yes_branch: '#639922', no_branch: '#E24B4A',
                    trigger: '#7F77DD', return: '#EF9F27', exception: '#E24B4A',
                };
                const color = conn.color_hex || colorMap[conn.connector_type] || '#B4B2A9';
                const dashed = ['trigger', 'exception'].includes(conn.connector_type);
                const markerColor = { yes_branch: 'green', no_branch: 'red', exception: 'red' }[conn.connector_type] || 'gray';
                let path, labelX, labelY;
                if (conn.connector_type === 'no_branch') {
                    const dropY = from.y + from.h + this.BRANCH_DROP;
                    path = `M${from.cx},${from.y + from.h} L${from.cx},${dropY}`;
                    labelX = from.cx + 12; labelY = from.y + from.h + 14;
                } else if (to.x > from.x + from.w) {
                    path = `M${from.x + from.w},${from.cy} L${to.x},${to.cy}`;
                    labelX = (from.x + from.w + to.x) / 2; labelY = from.cy - 8;
                } else {
                    const midY = from.cy - 30;
                    path = `M${from.cx},${from.y} L${from.cx},${midY} L${to.cx},${midY} L${to.cx},${to.y}`;
                    labelX = (from.cx + to.cx) / 2; labelY = midY - 6;
                }
                lines.push({ ...conn, path, color, dashed, markerColor, labelX, labelY });
            });
            return lines;
        },

        selectStep(node) { this.selected = this.selected?.id === node.id ? null : node; },
        zoomIn()  {},
        zoomOut() {},
        fitToScreen() {},
    }));
});
```

---

## 11. Business Rules & Constraints

### BR-FC-001: Tính toàn vẹn connector

```sql
-- Trigger: Không cho phép connector trỏ đến bước đã soft-delete
CREATE TRIGGER trg_connector_check_active
BEFORE INSERT ON sop_step_connectors
FOR EACH ROW
BEGIN
    IF (SELECT is_active FROM sop_steps WHERE id = NEW.from_step_id) = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'from_step is inactive';
    END IF;
    IF (SELECT is_active FROM sop_steps WHERE id = NEW.to_step_id) = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'to_step is inactive';
    END IF;
END;
```

### BR-FC-002: Decision step phải có đúng 2 connector (Yes + No)

```php
public function validateDecisionSteps(int $sopId): array
{
    $errors = [];
    $decisionSteps = SopStep::where('sop_id', $sopId)
                             ->where('step_type', 'decision')
                             ->where('is_active', true)
                             ->with('outgoingConnectors')
                             ->get();

    foreach ($decisionSteps as $step) {
        $connTypes = $step->outgoingConnectors->pluck('connector_type');
        if (!$connTypes->contains('yes_branch')) {
            $errors[] = "Bước '{$step->title}' (pos {$step->position}) thiếu nhánh Yes";
        }
        if (!$connTypes->contains('no_branch')) {
            $errors[] = "Bước '{$step->title}' (pos {$step->position}) thiếu nhánh No";
        }
    }
    return $errors;
}
```

### BR-FC-003: Flow phải có start và end

```php
public function validateFlowStructure(int $sopId): array
{
    $types = SopStep::where('sop_id', $sopId)
                    ->where('is_active', true)
                    ->pluck('step_type');
    $errors = [];
    if (!$types->contains('start')) $errors[] = 'Flow chưa có bước Start';
    if (!$types->contains('end'))   $errors[] = 'Flow chưa có bước End';
    return $errors;
}
```

### BR-FC-004: ref_sop_id chỉ trỏ đến SOP đã Approved

```php
// Validation rule khi save step có step_type = sub_sop
Rule::exists('sop_processes', 'id')
    ->where('status', 'approved')
    ->where('organization_id', $currentOrgId),
```

### BR-FC-005: SOP_RELATION không được tự tham chiếu

```php
// Trong FormRequest validation
'related_sop_id' => [
    'required',
    'exists:sop_processes,id',
    Rule::notIn([$request->route('sop')->id]), // sop_id !== related_sop_id
],
```

### BR-FC-006: Multi-tenancy

- Tất cả query SOP phải có điều kiện `organization_id = :current_org_id` (BIGINT)
- `ref_sop_id` trong sub_sop step chỉ được trỏ đến SOP trong cùng org

---

## 12. Indexes & Performance

```sql
-- Index tổng hợp cho render flowchart (query quan trọng nhất)
CREATE INDEX idx_step_render_full
  ON sop_steps(sop_id, is_active, position)
  INCLUDE (title, step_type, duration_minutes, branch_yes_position, branch_no_position);

-- Index cho connector lookup
CREATE INDEX idx_conn_full
  ON sop_step_connectors(sop_id)
  INCLUDE (from_step_id, to_step_id, connector_type, label, color_hex);

-- Index diff view — chỉ lấy bước đã thay đổi
CREATE INDEX idx_step_ver_changed
  ON sop_step_versions(sop_version_id, change_type)
  WHERE change_type != 'unchanged';

-- Index RACI bulk fetch
CREATE INDEX idx_raci_bulk
  ON sop_step_raci(step_id, raci_type)
  INCLUDE (assignee_type, assignee_id);

-- Index analytics (step_type phổ biến, duration)
CREATE INDEX idx_step_analytics
  ON sop_steps(step_type, is_active, duration_minutes)
  WHERE is_active = TRUE AND duration_minutes IS NOT NULL;

-- Index SOP list per org
CREATE INDEX idx_sop_proc_org_status
  ON sop_processes(organization_id, status, created_at DESC);
```

### Caching strategy

```php
// Cache flowchart data — invalidate khi SOP thay đổi
Cache::tags(["sop:{$sopId}"])->remember(
    "flowchart:{$sopId}",
    now()->addMinutes(30),
    fn() => $this->getFlowchartData($sopId)
);

// Invalidate khi step, connector, raci thay đổi
Cache::tags(["sop:{$sopId}"])->flush();
```

---

## 13. Lộ trình triển khai

> **Nguyên tắc thứ tự:** Data layer → Service layer → API/Controller → UI → Validation → Test.  
> Mỗi phase phải hoàn thiện và test được trước khi sang phase tiếp theo.  
> Module Sop chưa tồn tại — cần scaffold trước Phase 1.

---

### Phase 0 — Module Scaffold & RBAC (tiên quyết)

**Mục tiêu:** Tạo xương sống module, đăng ký permissions, tích hợp sidebar — không có phase này thì không phase nào chạy được.

- [ ] `php artisan module:make Sop` — scaffold cấu trúc NWIDART
- [ ] Đăng ký `SopServiceProvider` trong `config/app.php` (hoặc auto-discover)
- [ ] Định nghĩa permissions trong `config/permissions.php`:
  - `sop.view`, `sop.create`, `sop.edit`, `sop.delete`
  - `sop.submit_review`, `sop.approve`, `sop.reject`
  - `sop.manage_raci`, `sop.manage_attachment`
- [ ] Gán permissions cho roles: CEO (full), Ops/Sales (view + submit), System_Admin (full), Viewer (view only)
- [ ] Seeder: `SopPermissionSeeder` — chạy sau `AuthDatabaseSeeder`
- [ ] `SopPolicy` — map abilities sang permissions Spatie
- [ ] Sidebar entry trong `config/permissions.php` (module `sop`) với icon + route
- [ ] Routes stub: `GET /dashboard/sop` → 503 placeholder (giống các module chưa active)
- [ ] Module status: đánh dấu `enabled: false` trong `modules_statuses.json` đến khi Phase 1 xong

---

### Phase 1 — Database Foundation (tuần 1)

**Mục tiêu:** Toàn bộ schema live-layer lên DB, test migration up/down.

- [ ] Migration `2026_XX_XX_create_sop_processes_table` (section 6.0)
- [ ] Migration `2026_XX_XX_create_sop_steps_table` (section 6.1)
- [ ] Migration `2026_XX_XX_create_sop_step_connectors_table` (section 6.2)
- [ ] Migration `2026_XX_XX_create_sop_step_raci_table` (section 6.7)
- [ ] Migration `2026_XX_XX_create_sop_step_attachments_table` (section 6.8)
- [ ] Migration `2026_XX_XX_create_sop_relations_table` (section 6.10)
- [ ] Enum PHP: `SopStatus`, `SopType`, `StepType`, `ConnectorType` (backed enum, `string`)
- [ ] Eloquent models: `SopProcess`, `SopStep`, `SopStepConnector`, `SopStepRaci`, `SopStepAttachment`, `SopRelation` với đầy đủ relationships (section 8)
- [ ] `SopProcess` extend `TenantAwareModel` — global scope `organization_id`
- [ ] `SopProcess::getRouteKeyName()` trả về `'uuid'` (không expose BIGINT)
- [ ] Test: `php artisan migrate --path=Modules/Sop/Database/Migrations` + rollback
- [ ] Test: factory + `SopProcess::create()` với org scope hoạt động đúng

---

### Phase 2 — SOP Process CRUD (tuần 1–2)

**Mục tiêu:** Người dùng có thể tạo/xem/sửa/xóa SOP. Đây là entry point cho toàn module.

**Controllers & Actions:**
- [ ] `SopProcessController` — resourceful: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`
- [ ] `StoreSopProcessAction` — validate code unique trong org, gán `created_by`, `organization_id`
- [ ] `UpdateSopProcessAction` — không cho sửa code khi status = approved
- [ ] `ArchiveSopProcessAction` — chuyển `status = archived`, soft delete
- [ ] `ListSopProcessesQuery` — filter: status, type, department_id, branch_id, owner_id, keyword

**Validation:**
- [ ] `StoreSopProcessRequest` + `UpdateSopProcessRequest` — unique code trong org, date logic (`effective_date < expired_date`)
- [ ] Policy gate: `create`, `update`, `delete`, `view` ánh xạ đúng permissions

**Views (Blade + Alpine.js):**
- [ ] `sop/index.blade.php` — danh sách SOP: table với filter bar (status, type, search), pagination, badge status, nút tạo mới
- [ ] `sop/create.blade.php` / `sop/edit.blade.php` — form: code, title, description, type, department, branch, owner, effective_date, expired_date
- [ ] `sop/show.blade.php` — layout 2 cột: meta SOP (trái) + flowchart placeholder (phải), tab: Overview / Flowchart / Versions / Relations
- [ ] Xóa mềm + restore (nếu System_Admin)

**Routes:**
- [ ] `GET/POST /dashboard/sop` → index + store
- [ ] `GET /dashboard/sop/create` → create form
- [ ] `GET/PUT/DELETE /dashboard/sop/{sop:uuid}` → show + update + destroy

---

### Phase 3 — Flowchart View (Read-only) (tuần 2)

**Mục tiêu:** Render flowchart SVG từ dữ liệu DB — không cần editor trước.

- [ ] `SopFlowchartRepository::getFlowchartData(int $sopId): array` — 4 queries (section 7.1), không N+1
- [ ] `SopFlowchartController::data(SopProcess $sop): JsonResponse` — endpoint JSON (section 10)
- [ ] Route: `GET /dashboard/sop/{sop:uuid}/flowchart/data`
- [ ] Alpine.js component `sopFlowchart` (section 10) — `computeNodes()` + `computeConnectors()` + `buildLayout()`
- [ ] Render: rect, diamond, oval, parallelogram, rounded_rect, rect_double
- [ ] Render connectors: sequence, yes_branch, no_branch — với màu đúng theo `connector_type`
- [ ] Step detail panel (click node): title, description, expected_output, warning_note, RACI badges, attachment count
- [ ] Toolbar: zoom in/out, fit to screen (stub OK cho phase này), show/hide duration
- [ ] Cache: `Cache::tags(["sop:{$sopId}"])->remember("flowchart:{$sopId}", 30min, ...)`
- [ ] Tích hợp vào tab "Flowchart" của `sop/show.blade.php`

---

### Phase 4 — Flowchart Editor (tuần 3–4)

**Mục tiêu:** Người dùng có thể xây dựng flowchart qua UI — thêm/sửa/xóa bước, kéo thả thứ tự, quản lý connector.

**Step CRUD:**
- [ ] `SopStepController` — `store`, `update`, `destroy` (no index/show — flowchart là UI)
- [ ] `StoreSopStepAction` — gán position tự động (max + 1), tạo uuid
- [ ] `UpdateSopStepAction` — không cho sửa position trực tiếp (dùng reorder)
- [ ] `DestroySopStepAction` — soft delete (`is_active = false`), xóa connector liên quan
- [ ] API: `POST /backend/api/sop/{sop}/steps`, `PUT .../steps/{step:uuid}`, `DELETE .../steps/{step:uuid}`
- [ ] `SopStepReorderController::update` — nhận mảng `[id, ...]` theo thứ tự mới, bulk UPDATE (section 7.2)
- [ ] API: `PUT /backend/api/sop/{sop}/steps/reorder`

**Connector CRUD:**
- [ ] `SopStepConnectorController` — `store`, `update`, `destroy`
- [ ] `StoreSopStepConnectorAction` — validate: from_step + to_step thuộc cùng sop_id
- [ ] Validate: unique constraint `(from_step_id, to_step_id, connector_type)` tại DB + app layer
- [ ] API: `POST/PUT/DELETE /backend/api/sop/{sop}/connectors`

**Editor UI (Alpine.js):**
- [ ] Component `sopEditor` — extend `sopFlowchart` với mode `edit`
- [ ] Panel thêm bước: chọn step_type (icon palette), nhập title/description/etc.
- [ ] Click step → edit panel (drawer phải): sửa title, description, expected_output, warning_note, step_type, duration_minutes, is_mandatory
- [ ] Drag & drop reorder bước (horizontal sort — dùng SortableJS hoặc Alpine drag, cái nào tối ưu nhất thì triển khai)
- [ ] Nút xóa bước (với confirm dialog)
- [ ] Add connector: click source → click target → chọn connector_type + label
- [ ] Xóa connector: click connector → nút delete
- [ ] Validation inline: quy tắc BR-FC-002 (decision cần Yes+No), BR-FC-003 (start+end)
- [ ] Auto-invalidate cache khi lưu bước/connector: `Cache::tags(["sop:{$sopId}"])->flush()`
- [ ] Lock editor khi SOP status = `approved` (chỉ được sửa khi `draft`)

**Decision & Sub-SOP:**
- [ ] Render diamond shape cho `step_type = decision`
- [ ] yes_branch / no_branch connector render với màu xanh/đỏ
- [ ] Sub-SOP step: dropdown chọn `ref_sop_id` từ danh sách SOP đã approved trong cùng org (TomSelect async)
- [ ] Click sub-SOP node → link "Xem SOP con" trong detail panel

---

### Phase 5 — RACI & Attachments (tuần 4)

**Mục tiêu:** Phân công trách nhiệm và đính kèm tài liệu tham chiếu cho từng bước.

**RACI:**
- [ ] `SopStepRaciController` — `store`, `destroy` (per step)
- [ ] `StoreSopStepRaciAction` — validate unique `(step_id, assignee_type, assignee_id, raci_type)`
- [ ] API: `POST /backend/api/sop-steps/{step:uuid}/raci`, `DELETE .../raci/{raci:uuid}`
- [ ] API: `GET /backend/api/users/search?q=` + `GET /backend/api/roles` — để TomSelect chọn assignee
- [ ] UI: RACI panel trong step edit drawer — 4 cột R/A/C/I, mỗi cột là multi-select user/role
- [ ] RACI matrix view: `GET /dashboard/sop/{sop:uuid}/raci` — bảng bước × (R/A/C/I) per person/role
- [ ] Validate BR-RACI: mỗi bước action/decision phải có ít nhất 1 R (cảnh báo, không block)

**Attachments:**
- [ ] `config/sop.php` — max file size (20MB), allowed MIME types, storage driver
- [ ] `SopStepAttachmentController` — `store`, `destroy`
- [ ] `StoreSopStepAttachmentAction` — validate MIME + size + upload to storage + ghi DB
- [ ] `DestroySopStepAttachmentAction` — xóa file trên storage + xóa record
- [ ] API: `POST /backend/api/sop-steps/{step:uuid}/attachments`, `DELETE .../attachments/{attachment:uuid}`
- [ ] UI: attachment panel trong step detail — danh sách file (tên, size, download), nút upload, nút xóa
- [ ] Drag & drop upload zone trong step edit drawer

---

### Phase 6 — SOP Relations (tuần 4–5)

**Mục tiêu:** Track dependencies giữa các SOP — prerequisite, replaces, related.

- [ ] `SopRelationController` — `store`, `destroy`
- [ ] `StoreSopRelationAction` — validate: `sop_id <> related_sop_id` (BR-FC-005), không duplicate relation
- [ ] API: `POST /backend/api/sop/{sop:uuid}/relations`, `DELETE .../relations/{relation:uuid}`
- [ ] UI: tab "Quan hệ SOP" trong `sop/show.blade.php` — danh sách relations theo type, form thêm mới (chọn SOP liên quan + relation_type)
- [ ] Hiển thị SOP nào là prerequisite khi viewer xem SOP (warning banner)
- [ ] Hiển thị "Đã được thay thế bởi" khi SOP có relation_type = `replaced_by`

---

### Phase 7 — Versioning Database (tuần 5)

**Mục tiêu:** Tạo version-layer schema — bất biến sau khi insert.

- [ ] Migration `sop_versions` (section 6.3)
- [ ] Migration `sop_step_versions` (section 6.4)
- [ ] Migration `sop_step_raci_versions` (section 6.5)
- [ ] Migration `sop_step_connector_versions` (section 6.6)
- [ ] Migration `sop_approval_flows` (section 6.9)
- [ ] Models: `SopVersion`, `SopStepVersion`, `SopStepRaciVersion`, `SopStepConnectorVersion`, `SopApprovalFlow` với đầy đủ relationships
- [ ] Đảm bảo không có `UPDATE` nào trên bảng `sop_step_versions` / `sop_step_raci_versions` / `sop_step_connector_versions` (immutable by convention — thêm comment vào model)

---

### Phase 8 — Versioning Logic & Approval Flow (tuần 5–6)

**Mục tiêu:** Submit SOP để duyệt, multi-level approval, snapshot khi approve, rollback.

**Services:**
- [ ] `SopVersioningService::createSnapshot(int $sopId, int $approverId): SopVersion` — đầy đủ per section 9
- [ ] `SopVersioningService::rollbackToVersion(int $sopId, int $targetVersion): void` — rebuild live data từ snapshot (section 7.6)
- [ ] `SopVersioningService::detectChangeType(SopStep, ?SopStepVersion): string` — diff logic
- [ ] `SopApprovalService::submitForReview(SopProcess $sop, User $submitter): SopVersion` — tạo `sop_versions` + insert `sop_approval_flows` theo cấu hình org
- [ ] `SopApprovalService::approve(SopApprovalFlow $flow, User $approver, ?string $comment)` — UPDATE action + kiểm tra step tiếp theo hoặc finalize
- [ ] `SopApprovalService::reject(SopApprovalFlow $flow, User $approver, string $comment)` — UPDATE status = rejected

**Controllers:**
- [ ] `SopVersionController` — `index` (lịch sử), `show` (xem snapshot version)
- [ ] `SopApprovalController` — `store` (submit review), `approve`, `reject`
- [ ] Routes: `POST .../sop/{sop}/submit-review`, `POST .../approval-flows/{flow}/approve`, `POST .../approval-flows/{flow}/reject`

**UI:**
- [ ] Nút "Gửi duyệt" (khi status = draft, user có `sop.submit_review`) — confirm modal với change summary
- [ ] Tab "Phiên bản" trong `sop/show.blade.php` — timeline versions (version_number, status badge, total_steps, approved_at, change_summary)
- [ ] Trang duyệt SOP: `GET /dashboard/sop/{sop}/versions/{version}/review` — xem snapshot flowchart + form approve/reject
- [ ] Diff view: render flowchart version với màu change_type (added=xanh, modified=vàng, deleted=đỏ, unchanged=xám)
- [ ] Nút "Rollback về version này" trong timeline (chỉ System_Admin / owner)
- [ ] Approval pending list: `GET /dashboard/sop/pending-approvals` — danh sách SOP cần duyệt của user hiện tại (theo `required_role`)

---

### Phase 9 — Notifications (tuần 6)

**Mục tiêu:** Thông báo in-app và email khi có hành động trong approval flow.

- [ ] `SopSubmittedNotification` — gửi cho approver ở `step_order = 1` khi submit
- [ ] `SopApprovedNotification` — gửi cho creator khi version được approve hoàn toàn
- [ ] `SopRejectedNotification` — gửi cho creator kèm comment người reject
- [ ] `SopNextApproverNotification` — gửi cho approver bước tiếp theo khi bước trước approved
- [ ] Channels: `database` (in-app) + `mail`
- [ ] Tích hợp với notification bell hiện có trong sidebar (nếu có) hoặc dùng `database` channel + badge
- [ ] Queue: tất cả notification dispatch qua queue (`implements ShouldQueue`)
- [ ] `SopJob extends TenantAwareJob` — đảm bảo tenant context restore trong worker

---

### Phase 10 — Cron & Automation (tuần 7)

**Mục tiêu:** Tự động hóa trạng thái SOP theo thời gian.

- [ ] `ArchiveExpiredSopCommand` (`sop:archive-expired`) — query `sop_processes` có `expired_date < today AND status = approved`, chuyển `status = archived`, ghi log
- [ ] Đăng ký trong `routes/console.php`: `Schedule::command('sop:archive-expired')->daily()`
- [ ] `SopExpiryWarningCommand` (`sop:expiry-warning`) — gửi notification cho owner những SOP sắp hết hạn trong 7 ngày
- [ ] Đăng ký: `Schedule::command('sop:expiry-warning')->weeklyOn(1)`

---

### Phase 11 — Analytics & Reporting (tuần 7–8)

**Mục tiêu:** Dashboard thống kê và RACI matrix cross-SOP.

- [ ] `SopAnalyticsController::dashboard` — stats: tổng SOP theo status, SOP hết hạn sắp tới, avg steps per SOP, avg duration per step_type
- [ ] Query analytics: `AVG(duration_minutes) GROUP BY step_type` per org (section 7.4)
- [ ] UI: `sop/analytics.blade.php` — cards stats + bar chart (Alpine.js + Chart.js hoặc inline SVG)
- [ ] RACI matrix view: `GET /dashboard/sop/{sop}/raci` — bảng bước × người/role, highlight R/A/C/I
- [ ] Version history timeline: visual timeline trong tab Versions của `sop/show.blade.php`
- [ ] Route: `GET /dashboard/sop/analytics`

---

### Phase 12 — Export & Print (tuần 8–9)

**Mục tiêu:** Xuất SOP ra file để in, chia sẻ ngoài hệ thống.

- [ ] `SopExportController` — `exportPdf`, `exportPng`
- [ ] Export PDF: server-side render Blade view `sop/export/pdf.blade.php` (text-based, không SVG) → dùng `barryvdh/laravel-dompdf` hoặc Browsershot
- [ ] Export PNG: render SVG flowchart → convert sang PNG via Browsershot (headless Chrome) — queue job `ExportSopFlowchartJob`
- [ ] Print-friendly view: `GET /dashboard/sop/{sop}/print` — Blade full-page không sidebar, `@media print` CSS
- [ ] Routes: `GET /dashboard/sop/{sop}/export/pdf`, `GET /dashboard/sop/{sop}/export/png`
- [ ] UI: nút Export trong `sop/show.blade.php` toolbar (dropdown: PDF / PNG / Print)

---

### Phase 13 — SVG Editor Enhancement (tuần 9)

**Mục tiêu:** Nâng cao trải nghiệm flowchart — pan/zoom, layout tự động.

- [ ] Zoom in/out: implement `zoomIn()` / `zoomOut()` / `fitToScreen()` trong Alpine.js (dùng SVG `viewBox` manipulation)
- [ ] Pan: drag canvas khi giữ space + click (mouse event handler)
- [ ] Auto-layout: vertical layout option (top-down thay vì left-right)
- [ ] Minimap: thumbnail overview khi SOP có nhiều bước (> 10 bước)
- [ ] Keyboard shortcuts: `Delete` xóa selected step, `Escape` deselect
- [ ] Touch support: pinch-to-zoom trên mobile

---

### Phase 14 — Performance & Hardening (tuần 10)

**Mục tiêu:** Đảm bảo hệ thống chịu tải và bảo mật ở mức production.

- [ ] Review và bổ sung tất cả indexes theo section 12
- [ ] Cache strategy hoàn chỉnh: cache flowchart + invalidate đúng chỗ (step/connector/raci changes)
- [ ] DB trigger `trg_connector_check_active` (BR-FC-001 — section 11) — SQLite skip, MySQL apply
- [ ] Multi-tenancy audit: mọi query SOP đều có `organization_id` scope — không thể cross-tenant
- [ ] Authorization audit: mọi controller action đều có `$this->authorize()` hoặc Policy gate
- [ ] Rate limiting: API endpoints editor (store/update/delete) — `throttle:60,1`
- [ ] Input validation: tất cả FormRequest đầy đủ rule, không trust client về `sop_id` (lấy từ route)
- [ ] `BR-FC-006` enforcement: `ref_sop_id` trong sub_sop chỉ trỏ SOP cùng org (section 11)

---

### Phase 15 — Testing (song song từ Phase 2)

**Mục tiêu:** Đảm bảo correctness — feature tests phủ các business rule quan trọng.

- [ ] `SopProcessCrudTest` — create/update/delete/soft-delete với các role khác nhau
- [ ] `SopStepEditorTest` — thêm/sửa/xóa/reorder bước, validate position uniqueness
- [ ] `SopFlowchartRepositoryTest` — `getFlowchartData()` không N+1, đúng output shape
- [ ] `SopVersioningTest` — `createSnapshot()` tạo đúng records immutable, `rollbackToVersion()` rebuild đúng
- [ ] `SopApprovalFlowTest` — submit → multi-step approve → finalize; reject flow; forward flow
- [ ] `SopPolicyTest` — từng role chỉ làm được đúng action được phép
- [ ] `SopTenancyTest` — không thể query/modify SOP của org khác dù biết UUID
- [ ] `SopBusinessRulesTest` — BR-FC-002 (decision branches), BR-FC-003 (start/end), BR-FC-005 (no self-relation)
- [ ] `SopCacheInvalidationTest` — cache flush đúng lúc khi step/connector thay đổi

---

### Dependency Map

```
Phase 0 (Scaffold)
  └─► Phase 1 (DB)
        └─► Phase 2 (SOP CRUD)
              ├─► Phase 3 (Flowchart View)
              │     └─► Phase 4 (Flowchart Editor)
              │           ├─► Phase 5 (RACI & Attachments)
              │           ├─► Phase 6 (SOP Relations)
              │           └─► Phase 7 (Versioning DB)
              │                 └─► Phase 8 (Approval Flow)
              │                       └─► Phase 9 (Notifications)
              └─► Phase 10 (Cron) ← sau Phase 8
Phase 8 ──────► Phase 11 (Analytics)
Phase 3 ──────► Phase 12 (Export)
Phase 4 ──────► Phase 13 (SVG Enhancement)
Phase 1–14 ──► Phase 14 (Hardening)
Phase 2+ ────► Phase 15 (Testing — chạy song song)
```

---

*Version 3.0.0 — SOP Flowchart Engine Specification*  
*Stack: Laravel 13 · MySQL 8+ / SQLite (dev) · Alpine.js 3*  
*PK Convention: BIGINT AUTO_INCREMENT (id) + CHAR(36) UUID (uuid) — theo chuẩn toàn hệ thống*  
*FK Convention: BIGINT — khớp với users.id, organizations.id, branches.id, departments.id, roles.id*
