# Lead Module — Final Specification v3

> **Vị trí**: `Modules/Lead/`
> **Stack**: Laravel 12 · PHP 8.4 · MySQL 8+ · Redis · `nwidart/laravel-modules` · Alpine.js
> **Packages**:
> - `lorisleiva/laravel-actions ^2.10` — Action as Job/Command
> - `spatie/laravel-data ^4.23` — typed DTOs
> - `spatie/laravel-permission ^7.4` — phân quyền
> - `rap2hpoutre/fast-excel ^5.7` — export
> - `laravelcm/laravel-subscriptions` — subscription context nếu cần
>
> **Tài liệu liên quan**:
> - `survey_feature_inventory.md` — module Survey hiện có
> - `activity_log_spec_v3.md` — module ActivityLog
> - `workflow_automation_spec_v2.md` — module Workflow Automation
>
> **Cập nhật**: 2026-05-28
>
> **Các thay đổi chính so với v2**:
> - `organization_id NOT NULL` trên `leads` — bắt buộc multi-tenant, single-tenant dùng `organization_id=1`
> - Tách `lead_sources` thành bảng riêng (cùng pattern với `lead_pipeline_stages`) — có `organization_id` + `is_global`
> - `dedup_hash` trên `lead_contacts` với `UNIQUE (organization_id, dedup_hash)` — enforce dedup theo phạm vi org
> - Counter cache: `activity_count` trên `leads`, `lead_count` trên `lead_contacts`
> - Bổ sung `lead_tag_definitions` + `lead_tag_map` — tag system normalized
> - Bổ sung `title` trên `leads` — tiêu đề cơ hội
> - Bổ sung `idempotent_key` trên `leads` — ngăn duplicate khi Workflow retry
> - `lead_activities` có `organization_id` denormalized + `duration_minutes`/`attendee_count` columns thay meta_key_N
> - Covering indexes tối ưu cho list view / kanban / "leads của tôi" / closing soon / stale / hot

---

## Mục lục

1. [Phân tích nghiệp vụ & mô hình dữ liệu](#1-phân-tích-nghiệp-vụ)
2. [Database Schema](#2-database-schema)
3. [Enums](#3-enums)
4. [DTOs với spatie/laravel-data](#4-dtos)
5. [Models](#5-models)
6. [Actions với lorisleiva](#6-actions)
7. [Services](#7-services)
8. [Routes & Controllers](#8-routes--controllers)
9. [Views — Admin UI](#9-views--admin-ui)
10. [Tích hợp Workflow Automation](#10-tích-hợp-workflow-automation)
11. [Tích hợp Survey](#11-tích-hợp-survey)
12. [Tích hợp ActivityLog](#12-tích-hợp-activitylog)
13. [Permissions](#13-permissions)
14. [Config](#14-config)
15. [Cache Strategy](#15-cache-strategy)
16. [Migrations hoàn chỉnh](#16-migrations-hoàn-chỉnh)
17. [Seeders](#17-seeders)
18. [Thứ tự triển khai](#18-thứ-tự-triển-khai)

---

## 1. Phân tích nghiệp vụ

### 1.1 Ba khái niệm cốt lõi

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   CONTACT       │    │      LEAD       │    │  ORGANIZATION   │
│  (Người LH)     │◄──►│   (Cơ hội)      │◄──►│   (Tổ chức)     │
│                 │    │                 │    │                 │
│ • full_name     │    │ • title         │    │ • code          │
│ • email         │    │ • stage_id      │    │ • name          │
│ • phone         │    │ • source_id     │    │                 │
│ • company       │    │ • assigned_to   │    │  (đã tồn tại    │
│ • địa chỉ       │    │ • expected_value│    │   trong hệ      │
│ • organization_id        │    │ • organization_id (req)  │    │   thống)        │
│ • dedup_hash    │    │ • idempotent_key│    │                 │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

**Tại sao tách Contact riêng?**
- Một Contact có thể gắn với nhiều Lead theo thời gian (lần tiếp xúc 1, 2...).
- Thông tin Contact (địa chỉ, công ty) ít đổi hơn thông tin cơ hội (stage, giá trị).
- Tái sử dụng Contact khi tạo Lead mới — không nhập lại.
- Phù hợp với pattern CRM thực tế (HubSpot, Salesforce đều tách Contact / Deal).

**Tại sao Lead vẫn có snapshot Contact?**
- Khi Contact cập nhật, lịch sử Lead cũ vẫn ghi nhận đúng thông tin tại thời điểm đó.
- Query list view có đủ thông tin liên hệ mà không cần JOIN.

### 1.2 Multi-tenant per Organization

```
Organization (org) ──┬── nhiều Lead thuộc về 1 org
                     │
                     ├── lead_pipeline_stages riêng per org (hoặc global)
                     ├── lead_sources riêng per org (hoặc global)
                     ├── lead_tag_definitions riêng per org
                     └── Lead của org A không thấy được từ org B
```

`organization_id` ánh xạ với khái niệm **Tổ chức** trong hệ thống — có thể là công ty, chi nhánh, team sales. Mỗi org có pipeline/source riêng hoặc dùng chung pipeline/source global (`is_global = 1`).

> **organization_id NOT NULL trên `leads`**: Cho phép NULL gây 2 vấn đề: (1) `WHERE organization_id = ?` không match NULL nên cần `COALESCE` làm hỏng index; (2) phân quyền multi-tenant dễ lỗi logic nếu quên kiểm tra NULL. Single-tenant: luôn dùng `organization_id = 1` cố định.

### 1.3 Pipeline mặc định

```
Mới → Đã liên hệ → Đủ điều kiện → Đã gửi đề xuất → Đang đàm phán → Thành công
                                                                    → Thất bại
                                                                    → Không phù hợp
```

### 1.4 Mapping trường yêu cầu → bảng DB

| Trường yêu cầu | Bảng | Column |
|----------------|------|--------|
| Tình trạng cơ hội | `leads` | `stage_id` → `lead_pipeline_stages` |
| Nguồn cơ hội | `leads` | `source_id` → `lead_sources` |
| Người phụ trách | `leads` | `assigned_to` → `users.id` |
| Tên người liên hệ | `lead_contacts` | `full_name` |
| Công ty | `lead_contacts` | `company` |
| Chức vụ | `lead_contacts` | `job_title` |
| Địa chỉ | `lead_contacts` | `address` |
| Email người liên hệ | `lead_contacts` | `email` |
| Website | `lead_contacts` | `website` |
| Điện thoại | `lead_contacts` | `phone` |
| Tỉnh/Thành phố | `lead_contacts` | `province_code` → `provinces` |
| Phường/Xã | `lead_contacts` | `ward_code` → `wards` |
| Giá trị dự kiến | `leads` | `expected_value` (DECIMAL) |
| Ngày chốt dự kiến | `leads` | `expected_close_date` (DATE) |
| Mô tả | `leads` | `description` (TEXT) |
| organization_id | `leads`, `lead_contacts`, `lead_activities`, ... | `organization_id` |

---

## 2. Database Schema

### 2.1 `organizations` — tổ chức sở hữu Lead

> **Lưu ý**: Nếu hệ thống đã có bảng `organizations` (hoặc `tenants`) thì **bỏ qua migration này** và chỉ dùng FK.

```sql
CREATE TABLE `organizations` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `code`       VARCHAR(32)  NOT NULL UNIQUE,
    `name`       VARCHAR(191) NOT NULL,
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.2 `lead_pipeline_stages` — cấu hình tình trạng cơ hội

```sql
CREATE TABLE `lead_pipeline_stages` (
    `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,

    `organization_id`      INT UNSIGNED      NULL,
    `is_global`   TINYINT(1)        NOT NULL DEFAULT 0,
    -- is_global=1 & organization_id=NULL  → dùng chung toàn hệ thống
    -- is_global=0 & organization_id=X     → riêng của org X
    -- Không cho phép is_global=1 & organization_id=NOT NULL (enforce ở app layer)

    `code`        VARCHAR(32)       NOT NULL,
    -- 'new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'

    `label`       VARCHAR(64)       NOT NULL,
    -- 'Mới', 'Đã liên hệ', 'Đủ điều kiện', ...

    `color`       VARCHAR(16)       NOT NULL DEFAULT 'gray',
    -- 'gray' | 'blue' | 'teal' | 'purple' | 'amber' | 'green' | 'red'

    `sort_order`  TINYINT UNSIGNED  NOT NULL DEFAULT 0,

    `is_won`      TINYINT(1)        NOT NULL DEFAULT 0,
    -- Stage kết thúc thắng — tính conversion rate, đánh dấu Lead là Converted

    `is_lost`     TINYINT(1)        NOT NULL DEFAULT 0,
    -- Stage kết thúc thua — đánh dấu Lead là Archived

    `probability` TINYINT UNSIGNED  NOT NULL DEFAULT 0,
    -- % xác suất chốt (0–100) — dùng để tính weighted pipeline value
    -- VD: Qualified=30, Proposal=60, Negotiation=80, Won=100

    `is_active`   TINYINT(1)        NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP NULL,
    `updated_at`  TIMESTAMP NULL,

    UNIQUE KEY `uq_org_code`      (`organization_id`, `code`),
    INDEX      `idx_org_order`    (`organization_id`, `sort_order`, `is_active`),
    INDEX      `idx_global_order` (`is_global`, `sort_order`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.3 `lead_sources` — nguồn cơ hội (per org / global)

```sql
CREATE TABLE `lead_sources` (
    `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,

    `organization_id`      INT UNSIGNED      NULL,
    `is_global`   TINYINT(1)        NOT NULL DEFAULT 0,
    -- is_global=1 & organization_id=NULL  → dùng chung toàn hệ thống
    -- is_global=0 & organization_id=X     → riêng của org X

    `code`        VARCHAR(32)       NOT NULL,
    -- 'manual', 'survey', 'import', 'api', 'workflow', 'referral', 'event', 'website'

    `label`       VARCHAR(64)       NOT NULL,
    -- 'Thủ công', 'Survey', 'Import file', ...

    `icon`        VARCHAR(32)       NULL,
    -- Tabler icon class: 'ti-pencil', 'ti-world', 'ti-upload', ...

    `color`       VARCHAR(16)       NOT NULL DEFAULT 'gray',
    `sort_order`  TINYINT UNSIGNED  NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1)        NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP NULL,
    `updated_at`  TIMESTAMP NULL,

    UNIQUE KEY `uq_org_code`      (`organization_id`, `code`),
    INDEX      `idx_org_order`    (`organization_id`, `sort_order`, `is_active`),
    INDEX      `idx_global_order` (`is_global`, `sort_order`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.4 `lead_contacts` — thông tin người liên hệ

```sql
CREATE TABLE `lead_contacts` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,

    `organization_id`          INT UNSIGNED    NOT NULL,
    -- Thuộc org nào (NOT NULL — buộc multi-tenant rõ ràng)

    -- ── Thông tin cá nhân ─────────────────────────────────────
    `full_name`       VARCHAR(191)    NOT NULL,
    `email`           VARCHAR(191)    NULL,
    -- Nullable vì có thể chỉ có phone (lead từ cuộc gọi)

    `phone`           VARCHAR(32)     NULL,
    -- Lưu dạng string để chứa mọi định dạng (+84, 0xxx, ...)

    `phone_alt`       VARCHAR(32)     NULL,

    -- ── Thông tin công ty ─────────────────────────────────────
    `company`         VARCHAR(191)    NULL,
    `job_title`       VARCHAR(128)    NULL,
    `website`         VARCHAR(500)    NULL,

    -- ── Địa chỉ ───────────────────────────────────────────────
    `address`         VARCHAR(500)    NULL,
    `ward_code`       VARCHAR(8)      NULL,
    `ward_name`       VARCHAR(64)     NULL,
    -- Snapshot tên Phường/Xã — tránh JOIN khi hiển thị
    `district_code`   VARCHAR(8)      NULL,
    `district_name`   VARCHAR(64)     NULL,
    `province_code`   VARCHAR(8)      NULL,
    `province_name`   VARCHAR(64)     NULL,
    `country_code`    CHAR(2)         NOT NULL DEFAULT 'VN',

    -- ── Dedup & Counter Cache ─────────────────────────────────
    `dedup_hash`      CHAR(32)        NULL,
    -- MD5(LOWER(TRIM(email))) hoặc MD5(phone_digits_only)
    -- Ưu tiên email, fallback phone (chỉ giữ chữ số)
    -- NULL khi không có cả email lẫn phone → bỏ qua dedup

    `lead_count`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    -- Counter cache — số lead gắn với contact này
    -- Tăng/giảm bởi CreateLeadAction / DeleteLeadAction

    -- ── Audit ─────────────────────────────────────────────────
    `created_by`      BIGINT UNSIGNED NULL,
    `created_at`      TIMESTAMP NULL,
    `updated_at`      TIMESTAMP NULL,
    `deleted_at`      TIMESTAMP NULL,

    -- ── Indexes ───────────────────────────────────────────────
    UNIQUE KEY `uq_org_dedup`  (`organization_id`, `dedup_hash`),
    -- Enforce dedup theo phạm vi organization_id
    -- Contact A tồn tại ở org 1, vẫn có thể tạo lại ở org 2 với cùng email/phone
    -- Trong cùng 1 org: không cho phép duplicate (DB constraint)

    INDEX `idx_email`     (`organization_id`, `email`),
    INDEX `idx_phone`     (`organization_id`, `phone`),
    INDEX `idx_full_name` (`organization_id`, `full_name`),
    INDEX `idx_company`   (`organization_id`, `company`(32)),
    INDEX `idx_province`  (`organization_id`, `province_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Tại sao snapshot `ward_name`, `province_name`?**
- Bảng đơn vị hành chính có thể thay đổi (sáp nhập, đổi tên). Snapshot giữ nguyên tên tại thời điểm nhập.
- Hiển thị danh sách Contact không cần JOIN 3 bảng địa chính → query nhanh hơn.

**Tại sao `email` nullable trên Contact?**
- Thực tế CRM: nhiều Lead được tạo từ cuộc gọi điện — chỉ có số điện thoại.
- Dedup vẫn hoạt động qua `dedup_hash` (ưu tiên email, fallback phone).

### 2.5 `leads` — cơ hội kinh doanh (bảng chính)

```sql
CREATE TABLE `leads` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,

    -- ── Multi-tenant ──────────────────────────────────────────
    `organization_id`              INT UNSIGNED    NOT NULL,
    -- BẮT BUỘC — single-tenant dùng organization_id=1


    -- ── Contact ──────────────────────────────────────────────
    `contact_id`          BIGINT UNSIGNED NOT NULL,

    -- Snapshot tối thiểu (3 fields — đủ để hiển thị list)
    `contact_name`        VARCHAR(191)    NOT NULL,
    `contact_phone`       VARCHAR(32)     NULL,
    `contact_company`     VARCHAR(191)    NULL,
    -- Không snapshot email — nếu cần dùng JOIN contact (email ít hiển thị trên list)

    -- ── Pipeline ─────────────────────────────────────────────
    `stage_id`            SMALLINT UNSIGNED NOT NULL,
    -- FK → lead_pipeline_stages.id (enforce ở app layer)

    `stage_changed_at`    DATETIME          NULL,
    -- Thời điểm chuyển stage gần nhất — tính "time in stage"

    -- ── Nguồn cơ hội ─────────────────────────────────────────
    `source_id`           SMALLINT UNSIGNED NULL,
    -- FK → lead_sources.id (enforce ở app layer)
    -- NULL = chưa phân loại nguồn

    `source_detail`       VARCHAR(191)      NULL,
    -- Mô tả chi tiết tự do: tên campaign, tên người giới thiệu...
    -- Không thể chuẩn hóa hết vào bảng lead_sources

    -- ── Người phụ trách ──────────────────────────────────────
    `assigned_to`         BIGINT UNSIGNED   NULL,
    `assigned_at`         DATETIME          NULL,

    -- ── Giá trị & thời gian ─────────────────────────────────
    `expected_value`      DECIMAL(15,2)     NULL,
    `currency`            CHAR(3)           NOT NULL DEFAULT 'VND',
    -- ISO 4217: 'VND', 'USD', 'EUR'

    `expected_close_date` DATE              NULL,
    -- DATE (3 bytes) thay vì DATETIME (8 bytes) — ngày chốt không cần giờ
    `actual_close_date`   DATE              NULL,
    `actual_value`        DECIMAL(15,2)     NULL,

    -- ── Nội dung ─────────────────────────────────────────────
    `title`               VARCHAR(255)      NULL,
    -- Tiêu đề cơ hội (VD: "Dự án ERP 2026 — Công ty ABC")
    -- NULL → display fallback về contact_name + contact_company

    `description`         TEXT              NULL,

    -- ── Survey integration ───────────────────────────────────
    `survey_response_id`  BIGINT UNSIGNED   NULL,
    `survey_band_code`    VARCHAR(64)       NULL,
    `survey_score`        DECIMAL(5,2)      NULL,

    -- ── Lead scoring ─────────────────────────────────────────
    `lead_score`          TINYINT UNSIGNED  NOT NULL DEFAULT 0,
    -- 0–100 — tính bởi ScoreLeadAction
    `score_updated_at`    DATETIME          NULL,

    -- ── Trạng thái tổng thể ──────────────────────────────────
    `status`              TINYINT UNSIGNED  NOT NULL DEFAULT 1,
    -- 1=active 2=converted 3=archived 4=on_hold

    -- ── Activity tracking ────────────────────────────────────
    `last_activity_at`    DATETIME          NULL,
    `activity_count`      INT UNSIGNED      NOT NULL DEFAULT 0,
    -- Counter cache — tránh COUNT(*) trên lead_activities

    -- ── Idempotency ──────────────────────────────────────────
    `idempotent_key`      CHAR(32)          NULL,
    -- MD5(organization_id || source_code || survey_response_id) hoặc
    -- MD5(organization_id || contact_email || source_code)
    -- Ngăn Workflow tạo lead trùng khi retry
    -- UNIQUE khi NOT NULL

    -- ── Audit ────────────────────────────────────────────────
    `created_by`          BIGINT UNSIGNED   NULL,
    `updated_by`          BIGINT UNSIGNED   NULL,
    `created_at`          TIMESTAMP         NULL,
    `updated_at`          TIMESTAMP         NULL,
    `deleted_at`          TIMESTAMP         NULL,

    -- ── Indexes (covering cho các query thường gặp) ───────────

    -- [1] List view mặc định: lọc theo org + status + stage, sort updated_at
    INDEX `idx_list_view`    (`organization_id`, `status`, `stage_id`, `updated_at`),

    -- [2] Kanban view: group by stage trong 1 org
    INDEX `idx_kanban`       (`organization_id`, `stage_id`, `status`, `lead_score`),

    -- [3] "Lead của tôi": assigned_to + org
    INDEX `idx_my_leads`     (`assigned_to`, `organization_id`, `status`, `stage_id`),

    -- [4] Leads sắp hết hạn (dashboard alert)
    INDEX `idx_closing_soon` (`organization_id`, `expected_close_date`, `status`),

    -- [5] Stale leads (chưa có activity)
    INDEX `idx_stale`        (`organization_id`, `last_activity_at`, `status`),

    -- [6] Hot leads (score cao)
    INDEX `idx_hot`          (`organization_id`, `lead_score`, `status`),

    -- [7] Source analytics
    INDEX `idx_source`       (`organization_id`, `source_id`, `created_at`),

    -- [8] Survey link
    INDEX `idx_survey`       (`survey_response_id`),

    -- [9] Contact cascade
    INDEX `idx_contact`      (`contact_id`),

    -- [10] Idempotency (UNIQUE chỉ khi NOT NULL — MySQL hỗ trợ qua nullable unique)
    UNIQUE KEY `uq_idempotent` (`idempotent_key`),

    -- [11] Value-based queries (top deals)
    INDEX `idx_value`        (`organization_id`, `expected_value`, `status`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC;
```

**Tại sao snapshot `contact_*` fields trên `leads`?**
- Khi Contact cập nhật thông tin, lịch sử Lead cũ vẫn ghi nhận đúng thông tin.
- Query list view có đủ thông tin liên hệ mà không cần JOIN sang `lead_contacts`.
- Pattern này giống `actor_name` trong ActivityLog — snapshot, không phụ thuộc bảng gốc.

**Tại sao chỉ snapshot 3 fields (name, phone, company)?**
- 3 fields này hiển thị trên list view / kanban card — query không cần JOIN.
- Email/job_title ít hiển thị, có thể JOIN khi vào trang chi tiết.
- Giảm storage và tăng cache efficiency.

### 2.6 `lead_tag_definitions` + `lead_tag_map` — tag system

```sql
CREATE TABLE `lead_tag_definitions` (
    `id`         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `organization_id`     INT UNSIGNED      NOT NULL,
    `name`       VARCHAR(50)       NOT NULL,
    `color`      VARCHAR(16)       NOT NULL DEFAULT 'gray',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,

    UNIQUE KEY `uq_org_tag` (`organization_id`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lead_tag_map` (
    `lead_id` BIGINT UNSIGNED   NOT NULL,
    `tag_id`  SMALLINT UNSIGNED NOT NULL,

    PRIMARY KEY (`lead_id`, `tag_id`),
    INDEX `idx_tag` (`tag_id`, `lead_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

> Không dùng TEXT/JSON để lưu tag vì không thể index, không filter nhanh, không đếm lead per tag.

### 2.7 `lead_activities` — nhật ký thao tác

```sql
CREATE TABLE `lead_activities` (
    `id`              BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `lead_id`         BIGINT UNSIGNED  NOT NULL,
    `organization_id`          INT UNSIGNED     NOT NULL,
    -- Denormalize organization_id để query "activity của org X" không cần JOIN leads

    `type`            TINYINT UNSIGNED NOT NULL,
    -- 1=call 2=email 3=meeting 4=note 5=stage_change
    -- 6=assign 7=score_update 8=system 9=task 10=visit

    `title`           VARCHAR(191)     NOT NULL,
    `description`     TEXT             NULL,

    `outcome`         VARCHAR(64)      NULL,
    -- 'interested' | 'not_now' | 'no_answer' | 'follow_up' | 'converted' | 'rejected'

    `scheduled_at`    DATETIME         NULL,
    `completed_at`    DATETIME         NULL,

    -- Metadata dùng columns có nghĩa rõ ràng (thay meta_key_N của v2)
    `duration_minutes` SMALLINT UNSIGNED NULL,
    -- Dùng cho call/meeting/visit

    `attendee_count`   TINYINT UNSIGNED NULL,
    -- Dùng cho meeting/event tracking

    `actor_id`        BIGINT UNSIGNED  NULL,
    `actor_name`      VARCHAR(191)     NULL,
    `created_at`      TIMESTAMP        NULL,

    INDEX `idx_lead`      (`lead_id`, `created_at`),
    INDEX `idx_org_type`  (`organization_id`, `type`, `created_at`),
    INDEX `idx_scheduled` (`scheduled_at`, `completed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

> Nếu cần metadata tùy ý ngoài 2 fields trên: dùng `lead_meta` (EAV pattern bên dưới) — nhất quán, không thêm meta_key_N columns.

### 2.8 `lead_notes` — ghi chú (có thể ghim)

```sql
CREATE TABLE `lead_notes` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `lead_id`     BIGINT UNSIGNED NOT NULL,
    `organization_id`      INT UNSIGNED    NOT NULL,
    `content`     TEXT            NOT NULL,
    `is_pinned`   TINYINT(1)      NOT NULL DEFAULT 0,
    `author_id`   BIGINT UNSIGNED NULL,
    `author_name` VARCHAR(191)    NULL,
    `created_at`  TIMESTAMP NULL,
    `updated_at`  TIMESTAMP NULL,
    `deleted_at`  TIMESTAMP NULL,

    INDEX `idx_lead` (`lead_id`, `is_pinned`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.9 `lead_stage_history` — lịch sử đổi tình trạng

```sql
CREATE TABLE `lead_stage_history` (
    `id`               BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `lead_id`          BIGINT UNSIGNED   NOT NULL,
    `organization_id`           INT UNSIGNED      NOT NULL,
    `stage_from_id`    SMALLINT UNSIGNED NULL,    -- NULL = lần đầu tiên
    `stage_to_id`      SMALLINT UNSIGNED NOT NULL,
    `stage_from_label` VARCHAR(64)       NULL,    -- snapshot
    `stage_to_label`   VARCHAR(64)       NOT NULL,-- snapshot
    `changed_by`       BIGINT UNSIGNED   NULL,
    `changed_by_name`  VARCHAR(191)      NULL,    -- snapshot
    `note`             VARCHAR(500)      NULL,
    `changed_at`       DATETIME          NOT NULL,
    `created_at`       TIMESTAMP NULL,

    INDEX `idx_lead`  (`lead_id`, `changed_at`),
    INDEX `idx_org`   (`organization_id`, `changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.10 `lead_meta` — metadata mở rộng (EAV, không JSON)

Cho phép lưu data tùy ý per lead mà không thêm column vào bảng chính.

```sql
CREATE TABLE `lead_meta` (
    `id`           BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `lead_id`      BIGINT UNSIGNED  NOT NULL,
    `key_name`     VARCHAR(64)      NOT NULL,
    `value_type`   TINYINT UNSIGNED NOT NULL DEFAULT 1,
    -- 1=string 2=integer 3=decimal 4=boolean 5=datetime

    `val_string`   VARCHAR(500)  NULL,
    `val_integer`  BIGINT        NULL,
    `val_decimal`  DECIMAL(20,6) NULL,
    `val_boolean`  TINYINT(1)    NULL,
    `val_datetime` DATETIME      NULL,
    `created_at`   TIMESTAMP NULL,
    `updated_at`   TIMESTAMP NULL,

    UNIQUE KEY `uq_lead_key`     (`lead_id`, `key_name`),
    INDEX `idx_key_string`       (`key_name`, `val_string`(64)),
    INDEX `idx_key_integer`      (`key_name`, `val_integer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.11 Quan hệ tổng quan

```
organizations (1)
    │
    ├── lead_pipeline_stages (N)  [organization_id NULL = global, hoặc per org]
    │
    ├── lead_sources (N)          [organization_id NULL = global, hoặc per org]
    │
    ├── lead_tag_definitions (N)  [per org]
    │
    ├── lead_contacts (N)         [organization_id NOT NULL, dedup_hash UNIQUE per org]
    │        │ contact_id
    │        ▼
    └── leads (N) ──────────── users (assigned_to)
              │   stage_id → lead_pipeline_stages
              │   source_id → lead_sources
              │   idempotent_key UNIQUE
              │
       ┌──────┼──────────────┬────────────────┬──────────────┐
       ▼      ▼              ▼                ▼              ▼
    lead_   lead_         lead_stage_       lead_tag_     lead_meta
  activities notes        history           map           (EAV)
                                            (→ lead_tag_definitions)
```

---

## 3. Enums

```php
// Modules/Lead/app/Enums/LeadSourceCode.php
// Chỉ dùng làm seed codes chuẩn cho LeadSourcesSeeder
// Không lưu xuống DB nữa (DB lưu source_id)
enum LeadSourceCode: string
{
    case Manual   = 'manual';
    case Survey   = 'survey';
    case Import   = 'import';
    case Api      = 'api';
    case Workflow = 'workflow';
    case Referral = 'referral';
    case Event    = 'event';
    case Website  = 'website';
}

// Modules/Lead/app/Enums/LeadStatus.php
enum LeadStatus: int
{
    case Active    = 1;
    case Converted = 2;  // Đã thành khách hàng (stage is_won = true)
    case Archived  = 3;  // Đã kết thúc thất bại (stage is_lost = true)
    case OnHold    = 4;  // Tạm dừng theo đuổi

    public function label(): string
    {
        return match($this) {
            self::Active    => 'Đang theo dõi',
            self::Converted => 'Đã chuyển đổi',
            self::Archived  => 'Lưu trữ',
            self::OnHold    => 'Tạm dừng',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Active    => 'badge-teal',
            self::Converted => 'badge-green',
            self::Archived  => 'badge-gray',
            self::OnHold    => 'badge-amber',
        };
    }
}

// Modules/Lead/app/Enums/LeadActivityType.php
enum LeadActivityType: int
{
    case Call        = 1;
    case Email       = 2;
    case Meeting     = 3;
    case Note        = 4;
    case StageChange = 5;
    case Assign      = 6;
    case ScoreUpdate = 7;
    case System      = 8;
    case Task        = 9;
    case Visit       = 10;

    public function label(): string
    {
        return match($this) {
            self::Call        => 'Cuộc gọi',
            self::Email       => 'Email',
            self::Meeting     => 'Cuộc họp',
            self::Note        => 'Ghi chú',
            self::StageChange => 'Đổi tình trạng',
            self::Assign      => 'Phân công',
            self::ScoreUpdate => 'Cập nhật điểm',
            self::System      => 'Hệ thống',
            self::Task        => 'Công việc',
            self::Visit       => 'Thăm khách hàng',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Call        => 'ti-phone',
            self::Email       => 'ti-mail',
            self::Meeting     => 'ti-calendar-event',
            self::Note        => 'ti-file-text',
            self::StageChange => 'ti-arrows-right-left',
            self::Assign      => 'ti-user-check',
            self::ScoreUpdate => 'ti-target-arrow',
            self::System      => 'ti-settings',
            self::Task        => 'ti-checkbox',
            self::Visit       => 'ti-map-pin',
        };
    }
}

// Modules/Lead/app/Enums/MetaValueType.php
enum MetaValueType: int
{
    case String   = 1;
    case Integer  = 2;
    case Decimal  = 3;
    case Boolean  = 4;
    case Datetime = 5;
}
```

---

## 4. DTOs

```php
// Modules/Lead/app/Data/ContactData.php
use Spatie\LaravelData\Data;

class ContactData extends Data
{
    public function __construct(
        public int     $orgId,
        public string  $fullName,
        public ?string $email,
        public ?string $phone,
        public ?string $phoneAlt,
        public ?string $company,
        public ?string $jobTitle,
        public ?string $website,
        public ?string $address,
        public ?string $wardCode,
        public ?string $wardName,
        public ?string $districtCode,
        public ?string $districtName,
        public ?string $provinceCode,
        public ?string $provinceName,
        public string  $countryCode = 'VN',
    ) {}

    public function dedupHash(): ?string
    {
        $email = strtolower(trim($this->email ?? ''));
        $phoneDigits = preg_replace('/\D/', '', $this->phone ?? '');

        $key = $email ?: $phoneDigits;
        return $key ? md5($key) : null;
    }
}

// Modules/Lead/app/Data/LeadData.php
class LeadData extends Data
{
    public function __construct(
        public int            $orgId,
        public ?int           $contactId,
        public ?ContactData   $contact,
        public int            $stageId,
        public ?int           $sourceId,
        public ?string        $sourceDetail,
        public ?int           $assignedTo,
        public ?float         $expectedValue,
        public string         $currency,
        public ?string        $expectedCloseDate,
        public ?string        $title,
        public ?string        $description,
        public ?int           $surveyResponseId,
        public ?string        $surveyBandCode,
        public ?float         $surveyScore,
        public ?string        $idempotentKey,
    ) {}
}

// Modules/Lead/app/Data/LeadFilterData.php
class LeadFilterData extends Data
{
    public function __construct(
        public int     $orgId,
        public ?int    $stageId,
        public ?int    $sourceId,
        public ?int    $assignedTo,
        public ?int    $status,
        public ?string $search,
        public ?array  $tagIds,
        public ?int    $minScore,
        public ?string $closingBefore,
        public ?string $closingAfter,
        public int     $perPage = 25,
        public string  $sortBy = 'updated_at',
        public string  $sortDir = 'desc',
    ) {}
}

// Modules/Lead/app/Data/LeadActivityData.php
class LeadActivityData extends Data
{
    public function __construct(
        public int     $leadId,
        public int     $orgId,
        public int     $type,
        public string  $title,
        public ?string $description,
        public ?string $outcome,
        public ?string $scheduledAt,
        public ?string $completedAt,
        public ?int    $durationMinutes,
        public ?int    $attendeeCount,
        public ?int    $actorId,
        public ?string $actorName,
    ) {}
}
```

---

## 5. Models

```php
// Modules/Lead/app/Models/Lead.php
class Lead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id', 'contact_id', 'contact_name', 'contact_phone', 'contact_company',
        'stage_id', 'stage_changed_at',
        'source_id', 'source_detail',
        'assigned_to', 'assigned_at',
        'expected_value', 'currency', 'expected_close_date', 'actual_close_date', 'actual_value',
        'title', 'description',
        'survey_response_id', 'survey_band_code', 'survey_score',
        'lead_score', 'score_updated_at',
        'status', 'last_activity_at', 'activity_count',
        'idempotent_key',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'stage_changed_at'    => 'datetime',
        'assigned_at'         => 'datetime',
        'expected_close_date' => 'date',
        'actual_close_date'   => 'date',
        'expected_value'      => 'decimal:2',
        'actual_value'        => 'decimal:2',
        'survey_score'        => 'decimal:2',
        'score_updated_at'    => 'datetime',
        'last_activity_at'    => 'datetime',
        'status'              => LeadStatus::class,
    ];

    public function contact()    { return $this->belongsTo(LeadContact::class, 'contact_id'); }
    public function stage()      { return $this->belongsTo(LeadPipelineStage::class, 'stage_id'); }
    public function source()     { return $this->belongsTo(LeadSource::class, 'source_id'); }
    public function assignee()   { return $this->belongsTo(User::class, 'assigned_to'); }
    public function activities() { return $this->hasMany(LeadActivity::class); }
    public function notes()      { return $this->hasMany(LeadNote::class); }
    public function meta()       { return $this->hasMany(LeadMeta::class); }
    public function stageHistory() { return $this->hasMany(LeadStageHistory::class); }
    public function tags()       {
        return $this->belongsToMany(LeadTagDefinition::class, 'lead_tag_map', 'lead_id', 'tag_id');
    }

    // Global scope tự động filter theo org context
    protected static function booted(): void
    {
        static::addGlobalScope('org_context', function (Builder $builder) {
            $orgContext = app(OrgContext::class);
            if ($orgId = $orgContext->current()) {
                $builder->where('leads.organization_id', $orgId);
            }
        });
    }

    public function displayTitle(): string
    {
        if ($this->title) return $this->title;

        $parts = array_filter([$this->contact_name, $this->contact_company]);
        return implode(' — ', $parts) ?: "Lead #{$this->id}";
    }
}

// Modules/Lead/app/Models/LeadContact.php
class LeadContact extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id', 'full_name', 'email', 'phone', 'phone_alt',
        'company', 'job_title', 'website',
        'address', 'ward_code', 'ward_name',
        'district_code', 'district_name',
        'province_code', 'province_name', 'country_code',
        'dedup_hash', 'lead_count', 'created_by',
    ];

    public function leads() { return $this->hasMany(Lead::class, 'contact_id'); }
}

// Modules/Lead/app/Models/LeadPipelineStage.php
class LeadPipelineStage extends Model
{
    protected $fillable = [
        'organization_id', 'is_global', 'code', 'label', 'color',
        'sort_order', 'is_won', 'is_lost', 'probability', 'is_active',
    ];

    protected $casts = [
        'is_global' => 'boolean',
        'is_won'    => 'boolean',
        'is_lost'   => 'boolean',
        'is_active' => 'boolean',
    ];
}

// Modules/Lead/app/Models/LeadSource.php
class LeadSource extends Model
{
    protected $fillable = [
        'organization_id', 'is_global', 'code', 'label', 'icon',
        'color', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_global' => 'boolean',
        'is_active' => 'boolean',
    ];
}

// Tương tự cho: LeadActivity, LeadNote, LeadMeta, LeadStageHistory, LeadTagDefinition
```

---

## 6. Actions với lorisleiva

```php
// Modules/Lead/app/Actions/CreateLeadAction.php
class CreateLeadAction
{
    use AsAction;

    public function __construct(
        private LeadContactService $contactService,
        private OrgContext $orgContext,
    ) {}

    public function handle(LeadData $data): Lead
    {
        return DB::transaction(function () use ($data) {
            // 1. Idempotency check trước khi tạo
            if ($data->idempotentKey) {
                $existing = Lead::withoutGlobalScopes()
                    ->where('idempotent_key', $data->idempotentKey)
                    ->first();
                if ($existing) return $existing;
            }

            // 2. Resolve/create Contact (dedup theo organization_id + dedup_hash)
            $contact = $data->contactId
                ? LeadContact::findOrFail($data->contactId)
                : $this->contactService->findOrCreate($data->contact);

            // 3. Tạo lead
            $lead = Lead::create([
                'organization_id'             => $data->orgId,
                'contact_id'         => $contact->id,
                'contact_name'       => $contact->full_name,
                'contact_phone'      => $contact->phone,
                'contact_company'    => $contact->company,
                'stage_id'           => $data->stageId,
                'stage_changed_at'   => now(),
                'source_id'          => $data->sourceId,
                'source_detail'      => $data->sourceDetail,
                'assigned_to'        => $data->assignedTo,
                'assigned_at'        => $data->assignedTo ? now() : null,
                'expected_value'     => $data->expectedValue,
                'currency'           => $data->currency,
                'expected_close_date'=> $data->expectedCloseDate,
                'title'              => $data->title,
                'description'        => $data->description,
                'survey_response_id' => $data->surveyResponseId,
                'survey_band_code'   => $data->surveyBandCode,
                'survey_score'       => $data->surveyScore,
                'status'             => LeadStatus::Active,
                'last_activity_at'   => now(),
                'idempotent_key'     => $data->idempotentKey,
                'created_by'         => Auth::id(),
            ]);

            // 4. Cập nhật counter cache trên Contact
            $contact->increment('lead_count');

            // 5. Ghi lead_stage_history (lần đầu)
            LeadStageHistory::create([
                'lead_id'        => $lead->id,
                'organization_id'         => $lead->organization_id,
                'stage_from_id'  => null,
                'stage_to_id'    => $lead->stage_id,
                'stage_to_label' => $lead->stage->label,
                'changed_by'     => Auth::id(),
                'changed_by_name'=> Auth::user()?->name,
                'changed_at'     => now(),
            ]);

            // 6. Log system activity
            LogLeadActivityAction::run(new LeadActivityData(
                leadId:          $lead->id,
                orgId:           $lead->organization_id,
                type:            LeadActivityType::System->value,
                title:           'Tạo lead mới',
                description:     "Lead được tạo từ nguồn: " . ($lead->source?->label ?? 'không rõ'),
                outcome:         null,
                scheduledAt:     null,
                completedAt:     now()->toDateTimeString(),
                durationMinutes: null,
                attendeeCount:   null,
                actorId:         Auth::id(),
                actorName:       Auth::user()?->name,
            ));

            // 7. Async: tính lead score
            ScoreLeadAction::dispatch($lead->id)->onQueue(config('lead.queue'));

            // 8. Invalidate cache
            Cache::tags(["org:{$lead->organization_id}", "leads"])->flush();

            return $lead;
        });
    }
}

// Modules/Lead/app/Actions/ChangeLeadStageAction.php
class ChangeLeadStageAction
{
    use AsAction;

    public function handle(Lead $lead, int $newStageId, ?string $note = null): Lead
    {
        return DB::transaction(function () use ($lead, $newStageId, $note) {
            $oldStageId = $lead->stage_id;
            if ($oldStageId === $newStageId) return $lead;

            $oldStage = $lead->stage;
            $newStage = LeadPipelineStage::findOrFail($newStageId);

            $lead->update([
                'stage_id'         => $newStageId,
                'stage_changed_at' => now(),
                'updated_by'       => Auth::id(),
                'status'           => match (true) {
                    $newStage->is_won  => LeadStatus::Converted,
                    $newStage->is_lost => LeadStatus::Archived,
                    default            => $lead->status,
                },
                'actual_close_date' => ($newStage->is_won || $newStage->is_lost)
                    ? now()->toDateString()
                    : $lead->actual_close_date,
            ]);

            LeadStageHistory::create([
                'lead_id'          => $lead->id,
                'organization_id'           => $lead->organization_id,
                'stage_from_id'    => $oldStageId,
                'stage_to_id'      => $newStageId,
                'stage_from_label' => $oldStage->label,
                'stage_to_label'   => $newStage->label,
                'changed_by'       => Auth::id(),
                'changed_by_name'  => Auth::user()?->name,
                'note'             => $note,
                'changed_at'       => now(),
            ]);

            LogLeadActivityAction::run(new LeadActivityData(
                leadId:          $lead->id,
                orgId:           $lead->organization_id,
                type:            LeadActivityType::StageChange->value,
                title:           "Đổi tình trạng: {$oldStage->label} → {$newStage->label}",
                description:     $note,
                outcome:         null,
                scheduledAt:     null,
                completedAt:     now()->toDateTimeString(),
                durationMinutes: null,
                attendeeCount:   null,
                actorId:         Auth::id(),
                actorName:       Auth::user()?->name,
            ));

            Cache::tags(["org:{$lead->organization_id}", "leads", "kanban"])->flush();

            return $lead;
        });
    }
}

// Modules/Lead/app/Actions/LogLeadActivityAction.php
class LogLeadActivityAction
{
    use AsAction;

    public function handle(LeadActivityData $data): LeadActivity
    {
        $activity = LeadActivity::create([
            'lead_id'          => $data->leadId,
            'organization_id'           => $data->orgId,
            'type'             => $data->type,
            'title'            => $data->title,
            'description'      => $data->description,
            'outcome'          => $data->outcome,
            'scheduled_at'     => $data->scheduledAt,
            'completed_at'     => $data->completedAt,
            'duration_minutes' => $data->durationMinutes,
            'attendee_count'   => $data->attendeeCount,
            'actor_id'         => $data->actorId,
            'actor_name'       => $data->actorName,
        ]);

        // Cập nhật counter + last_activity_at trên Lead
        Lead::withoutGlobalScopes()
            ->where('id', $data->leadId)
            ->update([
                'last_activity_at' => now(),
                'activity_count'   => DB::raw('activity_count + 1'),
            ]);

        return $activity;
    }
}

// Modules/Lead/app/Actions/AssignLeadAction.php
class AssignLeadAction
{
    use AsAction;

    public function handle(Lead $lead, ?int $userId): Lead
    {
        $oldAssignee = $lead->assigned_to;
        if ($oldAssignee === $userId) return $lead;

        $lead->update([
            'assigned_to' => $userId,
            'assigned_at' => $userId ? now() : null,
            'updated_by'  => Auth::id(),
        ]);

        LogLeadActivityAction::run(new LeadActivityData(
            leadId:          $lead->id,
            orgId:           $lead->organization_id,
            type:            LeadActivityType::Assign->value,
            title:           $userId
                ? "Phân công cho: " . User::find($userId)?->name
                : "Hủy phân công",
            description:     null,
            outcome:         null,
            scheduledAt:     null,
            completedAt:     now()->toDateTimeString(),
            durationMinutes: null,
            attendeeCount:   null,
            actorId:         Auth::id(),
            actorName:       Auth::user()?->name,
        ));

        return $lead;
    }
}

// Modules/Lead/app/Actions/ScoreLeadAction.php
class ScoreLeadAction
{
    use AsAction;

    public string $jobQueue;

    public function __construct()
    {
        $this->jobQueue = config('lead.queue');
    }

    public function handle(int $leadId): void
    {
        $lead = Lead::withoutGlobalScopes()->findOrFail($leadId);

        // Scoring rules — customize theo business
        $score = 0;
        if ($lead->survey_score)              $score += min(40, (int) $lead->survey_score);
        if ($lead->expected_value > 100000000) $score += 20;  // > 100M VND
        if ($lead->contact_phone)              $score += 10;
        if ($lead->contact_company)            $score += 10;
        if ($lead->assigned_to)                $score += 10;
        if ($lead->stage?->probability > 50)   $score += 10;

        $lead->update([
            'lead_score'       => min(100, $score),
            'score_updated_at' => now(),
        ]);
    }
}
```

---

## 7. Services

```php
// Modules/Lead/app/Services/OrgContext.php
class OrgContext
{
    private ?int $orgId = null;

    public function setCurrent(?int $orgId): void { $this->orgId = $orgId; }
    public function current(): ?int { return $this->orgId; }
    public function require(): int
    {
        if (! $this->orgId) {
            throw new \RuntimeException('Org context not set');
        }
        return $this->orgId;
    }
}

// Modules/Lead/app/Services/LeadContactService.php
class LeadContactService
{
    public function findOrCreate(ContactData $data): LeadContact
    {
        $hash = $data->dedupHash();

        // Có hash → check duplicate theo organization_id + dedup_hash
        if ($hash) {
            $existing = LeadContact::where('organization_id', $data->orgId)
                ->where('dedup_hash', $hash)
                ->first();
            if ($existing) {
                // Update các trường có giá trị mới (không overwrite bằng null)
                $this->mergeContactFields($existing, $data);
                return $existing;
            }
        }

        return LeadContact::create([
            'organization_id'        => $data->orgId,
            'full_name'     => $data->fullName,
            'email'         => $data->email,
            'phone'         => $data->phone,
            'phone_alt'     => $data->phoneAlt,
            'company'       => $data->company,
            'job_title'     => $data->jobTitle,
            'website'       => $data->website,
            'address'       => $data->address,
            'ward_code'     => $data->wardCode,
            'ward_name'     => $data->wardName,
            'district_code' => $data->districtCode,
            'district_name' => $data->districtName,
            'province_code' => $data->provinceCode,
            'province_name' => $data->provinceName,
            'country_code'  => $data->countryCode,
            'dedup_hash'    => $hash,
            'created_by'    => Auth::id(),
        ]);
    }

    private function mergeContactFields(LeadContact $contact, ContactData $data): void
    {
        $updates = array_filter([
            'company'       => $data->company,
            'job_title'     => $data->jobTitle,
            'website'       => $data->website,
            'address'       => $data->address,
            'phone_alt'     => $data->phoneAlt,
            'ward_code'     => $data->wardCode,
            'ward_name'     => $data->wardName,
            'district_code' => $data->districtCode,
            'district_name' => $data->districtName,
            'province_code' => $data->provinceCode,
            'province_name' => $data->provinceName,
        ], fn($v) => !is_null($v) && $v !== '');

        if ($updates) $contact->update($updates);
    }
}

// Modules/Lead/app/Services/PipelineStageRepository.php
class PipelineStageRepository
{
    public function getForOrg(int $orgId): Collection
    {
        return Cache::tags(["org:{$orgId}", "pipeline"])
            ->remember("pipeline_stages:{$orgId}", 600, function () use ($orgId) {
                return LeadPipelineStage::query()
                    ->where(function ($q) use ($orgId) {
                        $q->where('organization_id', $orgId)
                          ->orWhere('is_global', 1);
                    })
                    ->where('is_active', 1)
                    ->orderBy('sort_order')
                    ->get();
            });
    }

    public function defaultStage(int $orgId): LeadPipelineStage
    {
        return $this->getForOrg($orgId)->first()
            ?? throw new \RuntimeException("No active stage for org {$orgId}");
    }
}

// Modules/Lead/app/Services/LeadSourceRepository.php
class LeadSourceRepository
{
    public function getForOrg(int $orgId): Collection
    {
        return Cache::tags(["org:{$orgId}", "sources"])
            ->remember("lead_sources:{$orgId}", 600, function () use ($orgId) {
                return LeadSource::query()
                    ->where(function ($q) use ($orgId) {
                        $q->where('organization_id', $orgId)
                          ->orWhere('is_global', 1);
                    })
                    ->where('is_active', 1)
                    ->orderBy('sort_order')
                    ->get();
            });
    }

    public function findByCode(int $orgId, string $code): ?LeadSource
    {
        return $this->getForOrg($orgId)->firstWhere('code', $code);
    }
}

// Modules/Lead/app/Services/LeadQueryService.php
class LeadQueryService
{
    public function paginate(LeadFilterData $filter): LengthAwarePaginator
    {
        $query = Lead::query()
            ->where('leads.organization_id', $filter->orgId)
            ->with(['stage:id,label,color,probability', 'source:id,label,icon,color', 'assignee:id,name']);

        if ($filter->stageId)     $query->where('stage_id', $filter->stageId);
        if ($filter->sourceId)    $query->where('source_id', $filter->sourceId);
        if ($filter->assignedTo)  $query->where('assigned_to', $filter->assignedTo);
        if ($filter->status)      $query->where('status', $filter->status);
        if ($filter->minScore)    $query->where('lead_score', '>=', $filter->minScore);

        if ($filter->closingBefore) $query->where('expected_close_date', '<=', $filter->closingBefore);
        if ($filter->closingAfter)  $query->where('expected_close_date', '>=', $filter->closingAfter);

        if ($filter->search) {
            $s = "%{$filter->search}%";
            $query->where(function ($q) use ($s) {
                $q->where('contact_name', 'like', $s)
                  ->orWhere('contact_company', 'like', $s)
                  ->orWhere('contact_phone', 'like', $s)
                  ->orWhere('title', 'like', $s);
            });
        }

        if ($filter->tagIds) {
            $query->whereExists(function ($q) use ($filter) {
                $q->select(DB::raw(1))
                  ->from('lead_tag_map')
                  ->whereColumn('lead_tag_map.lead_id', 'leads.id')
                  ->whereIn('lead_tag_map.tag_id', $filter->tagIds);
            });
        }

        return $query->orderBy($filter->sortBy, $filter->sortDir)
                     ->paginate($filter->perPage);
    }

    public function kanbanData(int $orgId): array
    {
        return Cache::tags(["org:{$orgId}", "kanban"])
            ->remember("lead_kanban:{$orgId}", 60, function () use ($orgId) {
                $leads = Lead::where('organization_id', $orgId)
                    ->where('status', LeadStatus::Active)
                    ->select('id', 'stage_id', 'title', 'contact_name', 'contact_company',
                             'expected_value', 'lead_score', 'assigned_to', 'updated_at')
                    ->with('assignee:id,name')
                    ->orderByDesc('lead_score')
                    ->limit(500)
                    ->get();

                return $leads->groupBy('stage_id')->all();
            });
    }
}

// Modules/Lead/app/Services/LeadStatsService.php
class LeadStatsService
{
    public function dashboardStats(int $orgId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $cacheKey = sprintf('lead_stats:%d:%s:%s', $orgId,
            $from?->toDateString() ?? 'all', $to?->toDateString() ?? 'now');

        return Cache::tags(["org:{$orgId}", "stats"])->remember($cacheKey, 300, function () use ($orgId, $from, $to) {
            $base = Lead::where('organization_id', $orgId);
            if ($from) $base->where('created_at', '>=', $from);
            if ($to)   $base->where('created_at', '<=', $to);

            return [
                'total_count'         => (clone $base)->count(),
                'by_status'           => (clone $base)->groupBy('status')->selectRaw('status, COUNT(*) as cnt')->pluck('cnt', 'status'),
                'by_stage'            => (clone $base)->groupBy('stage_id')->selectRaw('stage_id, COUNT(*) as cnt')->pluck('cnt', 'stage_id'),
                'total_value'         => (clone $base)->where('status', LeadStatus::Active)->sum('expected_value'),
                'weighted_value'      => $this->weightedPipelineValue($orgId, $from, $to),
                'conversion_rate'     => $this->conversionRate($orgId, $from, $to),
                'avg_time_to_close'   => $this->avgTimeToClose($orgId, $from, $to),
            ];
        });
    }

    private function weightedPipelineValue(int $orgId, ?Carbon $from, ?Carbon $to): float
    {
        $q = DB::table('leads')
            ->join('lead_pipeline_stages', 'leads.stage_id', '=', 'lead_pipeline_stages.id')
            ->where('leads.organization_id', $orgId)
            ->where('leads.status', LeadStatus::Active->value)
            ->whereNull('leads.deleted_at');

        if ($from) $q->where('leads.created_at', '>=', $from);
        if ($to)   $q->where('leads.created_at', '<=', $to);

        return (float) $q->selectRaw('COALESCE(SUM(expected_value * probability / 100), 0) as v')->value('v');
    }

    private function conversionRate(int $orgId, ?Carbon $from, ?Carbon $to): float
    {
        $total = Lead::where('organization_id', $orgId)
            ->when($from, fn($q) => $q->where('created_at', '>=', $from))
            ->when($to,   fn($q) => $q->where('created_at', '<=', $to))
            ->count();
        if (! $total) return 0.0;

        $won = Lead::where('organization_id', $orgId)
            ->where('status', LeadStatus::Converted)
            ->when($from, fn($q) => $q->where('created_at', '>=', $from))
            ->when($to,   fn($q) => $q->where('created_at', '<=', $to))
            ->count();

        return round($won / $total * 100, 2);
    }

    private function avgTimeToClose(int $orgId, ?Carbon $from, ?Carbon $to): ?float
    {
        $q = Lead::where('organization_id', $orgId)
            ->where('status', LeadStatus::Converted)
            ->whereNotNull('actual_close_date');
        if ($from) $q->where('created_at', '>=', $from);
        if ($to)   $q->where('created_at', '<=', $to);

        return $q->selectRaw('AVG(DATEDIFF(actual_close_date, created_at)) as days')->value('days');
    }
}
```

---

## 8. Routes & Controllers

```php
// Modules/Lead/routes/web.php
Route::prefix('admin/leads')
    ->middleware(['web', 'auth', 'org.context'])
    ->name('backend.leads.')
    ->group(function () {
        Route::get('/',                 [LeadController::class, 'index'])->name('index');
        Route::get('/create',           [LeadController::class, 'create'])->name('create');
        Route::post('/',                [LeadController::class, 'store'])->name('store');
        Route::get('/{lead}',           [LeadController::class, 'show'])->name('show');
        Route::get('/{lead}/edit',      [LeadController::class, 'edit'])->name('edit');
        Route::put('/{lead}',           [LeadController::class, 'update'])->name('update');
        Route::delete('/{lead}',        [LeadController::class, 'destroy'])->name('destroy');

        Route::post('/{lead}/stage',    [LeadController::class, 'changeStage'])->name('stage');
        Route::post('/{lead}/assign',   [LeadController::class, 'assign'])->name('assign');
        Route::post('/{lead}/notes',    [LeadNoteController::class, 'store'])->name('notes.store');
        Route::post('/{lead}/activities',[LeadActivityController::class, 'store'])->name('activities.store');

        Route::get('/export/excel',     [LeadController::class, 'export'])->name('export');
    });

// Modules/Lead/routes/api.php
Route::prefix('api/leads')
    ->middleware(['api', 'auth:sanctum', 'org.context'])
    ->name('api.leads.')
    ->group(function () {
        Route::get('/',         [LeadApiController::class, 'index']);
        Route::get('/stats',    [LeadApiController::class, 'stats']);
        Route::get('/kanban',   [LeadApiController::class, 'kanban']);
        Route::get('/pipeline', [LeadApiController::class, 'pipelineConfig']);
        Route::get('/sources',  [LeadApiController::class, 'sources']);
    });
```

```php
// Modules/Lead/app/Http/Middleware/SetOrgContext.php
class SetOrgContext
{
    public function __construct(private OrgContext $orgContext) {}

    public function handle(Request $request, Closure $next)
    {
        $orgId = $request->user()?->current_organization_id
            ?? $request->header('X-Org-Id')
            ?? config('lead.default_organization_id', 1);

        $this->orgContext->setCurrent((int) $orgId);
        return $next($request);
    }
}
```

---

## 9. Views — Admin UI

> Chi tiết view giữ nguyên theo v2 (Tabulator list view, Alpine.js kanban, wizard 3 bước cho create form với địa chỉ cascade). Bổ sung:

- Trang `leads/index.blade.php`: filter dropdown `source_id` thay vì hardcode enum
- Trang `leads/create.blade.php`: load stages + sources qua `PipelineStageRepository::getForOrg($orgId)` và `LeadSourceRepository::getForOrg($orgId)`
- Card kanban hiển thị `displayTitle()` của Lead model

---

## 10. Tích hợp Workflow Automation

```php
// Modules/Lead/app/Workflow/CreateLeadExecutor.php
class CreateLeadExecutor implements StepExecutor
{
    public function __construct(
        private LeadSourceRepository $sourceRepo,
        private PipelineStageRepository $stageRepo,
    ) {}

    public function execute(WorkflowStep $step, WorkflowPayload $payload): ActionResult
    {
        $start = microtime(true);

        try {
            $orgId = $step->workflow->organization_id ?? config('lead.default_organization_id', 1);

            $contactData = new ContactData(
                orgId:         $orgId,
                fullName:      $payload->extra['contact_name']  ?? 'Khách hàng từ Survey',
                email:         $payload->extra['contact_email'] ?? null,
                phone:         $payload->extra['contact_phone'] ?? null,
                phoneAlt:      null,
                company:       $payload->extra['contact_company'] ?? null,
                jobTitle:      null,
                website:       null,
                address:       null,
                wardCode:      null, wardName:     null,
                districtCode:  null, districtName: null,
                provinceCode:  null, provinceName: null,
            );

            $workflowSource = $this->sourceRepo->findByCode($orgId, LeadSourceCode::Workflow->value);
            $defaultStage   = $this->stageRepo->defaultStage($orgId);

            // Idempotent key — Workflow retry sẽ không tạo lead trùng
            $idempotentKey = $payload->subjectType === 'SurveyResponse'
                ? md5($orgId . '|workflow|survey|' . $payload->subjectId)
                : md5($orgId . '|workflow|' . $step->id . '|' . ($payload->subjectId ?? 'none'));

            $data = new LeadData(
                orgId:             $orgId,
                contactId:         null,
                contact:           $contactData,
                stageId:           $defaultStage->id,
                sourceId:          $workflowSource?->id,
                sourceDetail:      "Workflow: {$step->workflow->name}",
                assignedTo:        $step->lead_assigned_to,
                expectedValue:     null,
                currency:          'VND',
                expectedCloseDate: null,
                title:             null,
                description:       null,
                surveyResponseId:  $payload->subjectType === 'SurveyResponse' ? $payload->subjectId : null,
                surveyBandCode:    $payload->extra['band_code']     ?? null,
                surveyScore:       $payload->extra['overall_score'] ?? null,
                idempotentKey:     $idempotentKey,
            );

            $lead = CreateLeadAction::run($data);
            $ms   = (int)((microtime(true) - $start) * 1000);

            return ActionResult::ok($ms, ['lead_id' => $lead->id]);
        } catch (\Throwable $e) {
            return ActionResult::fail(
                $e->getMessage(),
                (int)((microtime(true) - $start) * 1000)
            );
        }
    }
}
```

---

## 11. Tích hợp Survey

Survey không import Lead trực tiếp. Kết nối qua Workflow Engine. Trang `leads/show.blade.php` hiển thị link xem Survey result nếu Lead có `survey_response_id`:

```php
// LeadController::show()
$surveyResultUrl = null;
if ($lead->survey_response_id && \Route::has('backend.surveys.responses.show')) {
    $surveyResultUrl = route('backend.surveys.responses.show', $lead->survey_response_id);
}
```

---

## 12. Tích hợp ActivityLog

```php
// LeadObserver — ghi log CRUD tự động
class LeadObserver extends BaseModelObserver
{
    protected function module(): string       { return 'Lead'; }
    protected function resourceCode(): string { return 'lead'; }

    protected function updatedContext(\Illuminate\Database\Eloquent\Model $m): array
    {
        return [
            'changed_fields' => implode(',', array_keys($m->getChanges())),
            'stage_before'   => $m->getOriginal('stage_id'),
            'stage_after'    => $m->stage_id,
            'value_before'   => $m->getOriginal('expected_value'),
            'value_after'    => $m->expected_value,
            'organization_id'         => $m->organization_id,
        ];
    }
}
```

---

## 13. Permissions

```php
$permissions = [
    'lead.view',            // xem danh sách + chi tiết
    'lead.create',          // tạo Lead thủ công
    'lead.update',          // sửa thông tin, đổi stage
    'lead.delete',          // soft delete
    'lead.assign',          // phân công người phụ trách
    'lead.export',          // xuất Excel
    'lead.view_all',        // xem tất cả lead (không chỉ lead được assign)
    'lead.manage_pipeline', // thêm/sửa/xóa pipeline stages
    'lead.manage_sources',  // thêm/sửa/xóa lead sources
    'lead.manage_tags',     // thêm/sửa/xóa tag definitions
];
```

> Tất cả permission được scope theo organization_id ở app layer (Policy classes kiểm tra `$user->organization_id === $lead->organization_id`).

---

## 14. Config

```php
// Modules/Lead/config/lead.php
return [
    'queue'                     => env('LEAD_QUEUE', 'default'),
    'stale_days'                => env('LEAD_STALE_DAYS', 14),
    'hot_score_threshold'       => env('LEAD_HOT_SCORE', 70),
    'default_currency'          => env('LEAD_DEFAULT_CURRENCY', 'VND'),
    'stage_history_retain_days' => env('LEAD_STAGE_HISTORY_DAYS', 365),
    'default_assignee_id'       => env('LEAD_DEFAULT_ASSIGNEE', null),
    'default_organization_id'            => env('LEAD_DEFAULT_ORG_ID', 1),
    -- Single-tenant: organization_id mặc định khi không có org context

    'cache_ttl' => [
        'pipeline_stages' => 600,   // 10 phút
        'lead_sources'    => 600,
        'kanban'          => 60,    // 1 phút
        'stats'           => 300,   // 5 phút
        'score'           => 3600,  // 1 giờ
    ],
];
```

---

## 15. Cache Strategy

### 15.1 Cache keys & invalidation

| Key | TTL | Tags | Invalidate khi |
|---|---|---|---|
| `pipeline_stages:{organization_id}` | 600s | `org:{X}`, `pipeline` | Stage thêm/sửa/xóa per org X |
| `lead_sources:{organization_id}` | 600s | `org:{X}`, `sources` | Source thêm/sửa/xóa per org X |
| `lead_kanban:{organization_id}` | 60s | `org:{X}`, `kanban` | Lead thay đổi stage/status/assignee |
| `lead_stats:{organization_id}:{date}` | 300s | `org:{X}`, `stats` | Lead create/update/delete |
| `lead_score:{lead_id}` | 3600s | - | ScoreLeadAction chạy lại |

### 15.2 Invalidation pattern

```php
// Khi tạo/sửa lead → flush org's leads tag
Cache::tags(["org:{$orgId}", "leads"])->flush();

// Khi đổi stage → flush kanban
Cache::tags(["org:{$orgId}", "kanban"])->flush();

// Khi sửa pipeline stage → flush pipeline cache
Cache::tags(["org:{$orgId}", "pipeline"])->flush();

// Khi sửa source → flush sources cache
Cache::tags(["org:{$orgId}", "sources"])->flush();
```

> **Lưu ý**: Cache tags chỉ work với Redis/Memcached driver, không work với `file`/`database`. Đảm bảo `CACHE_DRIVER=redis` ở production.

### 15.3 Partition strategy (khi scale > 10M leads)

```sql
-- lead_activities và lead_stage_history có thể PARTITION BY RANGE (YEAR(created_at))
-- leads partition by organization_id nếu số org > 100 (sharding pattern)
-- Áp dụng khi số record > 5M, chưa cần từ đầu
```

---

## 16. Migrations hoàn chỉnh

```
Modules/Lead/database/migrations/
├── 2026_01_01_000001_create_organizations_table.php
│   -- BỎ QUA nếu đã có bảng organizations trong hệ thống
│
├── 2026_01_01_000002_create_lead_pipeline_stages_table.php
├── 2026_01_01_000003_create_lead_sources_table.php
├── 2026_01_01_000004_create_lead_contacts_table.php
├── 2026_01_01_000005_create_leads_table.php
├── 2026_01_01_000006_create_lead_tag_definitions_table.php
├── 2026_01_01_000007_create_lead_tag_map_table.php
├── 2026_01_01_000008_create_lead_activities_table.php
├── 2026_01_01_000009_create_lead_notes_table.php
├── 2026_01_01_000010_create_lead_stage_history_table.php
└── 2026_01_01_000011_create_lead_meta_table.php
```

**Thứ tự migration**: `organizations` → `lead_pipeline_stages` + `lead_sources` (song song) → `lead_contacts` → `leads` → các bảng con.

---

## 17. Seeders

```php
// LeadDatabaseSeeder
class LeadDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LeadPipelineStagesSeeder::class,
            LeadSourcesSeeder::class,
            LeadPermissionsSeeder::class,
        ]);
    }
}

// LeadPipelineStagesSeeder — pipeline mặc định (is_global = 1, organization_id = NULL)
class LeadPipelineStagesSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            ['code' => 'new',         'label' => 'Mới',                'color' => 'gray',   'sort_order' => 1, 'probability' => 10,  'is_won' => 0, 'is_lost' => 0],
            ['code' => 'contacted',   'label' => 'Đã liên hệ',         'color' => 'blue',   'sort_order' => 2, 'probability' => 20,  'is_won' => 0, 'is_lost' => 0],
            ['code' => 'qualified',   'label' => 'Đủ điều kiện',       'color' => 'teal',   'sort_order' => 3, 'probability' => 40,  'is_won' => 0, 'is_lost' => 0],
            ['code' => 'proposal',    'label' => 'Đã gửi đề xuất',     'color' => 'purple', 'sort_order' => 4, 'probability' => 60,  'is_won' => 0, 'is_lost' => 0],
            ['code' => 'negotiation', 'label' => 'Đang đàm phán',      'color' => 'amber',  'sort_order' => 5, 'probability' => 80,  'is_won' => 0, 'is_lost' => 0],
            ['code' => 'won',         'label' => 'Thành công',         'color' => 'green',  'sort_order' => 6, 'probability' => 100, 'is_won' => 1, 'is_lost' => 0],
            ['code' => 'lost',        'label' => 'Thất bại',           'color' => 'red',    'sort_order' => 7, 'probability' => 0,   'is_won' => 0, 'is_lost' => 1],
            ['code' => 'unqualified', 'label' => 'Không phù hợp',      'color' => 'gray',   'sort_order' => 8, 'probability' => 0,   'is_won' => 0, 'is_lost' => 1],
        ];

        foreach ($stages as $stage) {
            LeadPipelineStage::firstOrCreate(
                ['code' => $stage['code'], 'organization_id' => null],
                array_merge($stage, ['organization_id' => null, 'is_global' => 1, 'is_active' => 1])
            );
        }
    }
}

// LeadSourcesSeeder — nguồn mặc định (is_global = 1, organization_id = NULL)
class LeadSourcesSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            ['code' => 'manual',   'label' => 'Thủ công',           'color' => 'gray',   'icon' => 'ti-pencil',         'sort_order' => 1],
            ['code' => 'survey',   'label' => 'Survey',             'color' => 'purple', 'icon' => 'ti-clipboard-list', 'sort_order' => 2],
            ['code' => 'import',   'label' => 'Import file',        'color' => 'amber',  'icon' => 'ti-upload',         'sort_order' => 3],
            ['code' => 'api',      'label' => 'API',                'color' => 'blue',   'icon' => 'ti-api',            'sort_order' => 4],
            ['code' => 'workflow', 'label' => 'Tự động (Workflow)', 'color' => 'purple', 'icon' => 'ti-robot',          'sort_order' => 5],
            ['code' => 'referral', 'label' => 'Giới thiệu',         'color' => 'teal',   'icon' => 'ti-users',          'sort_order' => 6],
            ['code' => 'event',    'label' => 'Sự kiện',            'color' => 'teal',   'icon' => 'ti-calendar-event', 'sort_order' => 7],
            ['code' => 'website',  'label' => 'Website',            'color' => 'blue',   'icon' => 'ti-world',          'sort_order' => 8],
        ];

        foreach ($sources as $source) {
            LeadSource::firstOrCreate(
                ['code' => $source['code'], 'organization_id' => null],
                array_merge($source, ['organization_id' => null, 'is_global' => 1, 'is_active' => 1])
            );
        }
    }
}
```

---

## 18. Thứ tự triển khai

| # | Hạng mục | Effort | Ghi chú |
|---|---|---|---|
| 1 | Confirm `organizations` table đã tồn tại chưa | Thấp | Nếu chưa: migration minimal |
| 2 | Migration `lead_pipeline_stages` + `is_global` | Thấp | Chạy trước leads |
| 3 | Migration `lead_sources` + `is_global` | Thấp | Chạy trước leads |
| 4 | Migration `lead_contacts` + `dedup_hash` UNIQUE + `lead_count` | Thấp | |
| 5 | Migration `leads` với `organization_id NOT NULL`, `title`, `activity_count`, `idempotent_key` | Thấp | Bảng chính |
| 6 | Migration `lead_tag_definitions` + `lead_tag_map` | Thấp | |
| 7 | Migration `lead_activities` với `organization_id` + `duration_minutes` + `attendee_count` | Thấp | |
| 8 | Migration `lead_notes`, `lead_stage_history`, `lead_meta` | Thấp | |
| 9 | Enums: `LeadSourceCode`, `LeadStatus`, `LeadActivityType`, `MetaValueType` | Thấp | |
| 10 | DTOs: `ContactData`, `LeadData`, `LeadFilterData`, `LeadActivityData` | Thấp | spatie/laravel-data |
| 11 | Models: `Lead`, `LeadContact`, `LeadPipelineStage`, `LeadSource`, `LeadActivity`, `LeadNote`, `LeadMeta`, `LeadStageHistory`, `LeadTagDefinition` | Trung | |
| 12 | `OrgContext` service + `SetOrgContext` middleware | Thấp | Core multi-tenant |
| 13 | Seeders: pipeline stages + sources + permissions | Thấp | |
| 14 | `PipelineStageRepository` + `LeadSourceRepository` với cache | Trung | Core service |
| 15 | `LeadContactService` với dedup theo `organization_id + dedup_hash` | Trung | |
| 16 | `CreateLeadAction` — idempotent, tạo Contact đồng thời | Trung | Core nhất |
| 17 | `UpdateLeadAction` + `ChangeLeadStageAction` | Trung | |
| 18 | `AssignLeadAction` + `LogLeadActivityAction` | Thấp | |
| 19 | `ScoreLeadAction` (async job) | Trung | |
| 20 | **Workflow integration** — `LeadServiceProvider` đăng ký đầy đủ | Thấp | Quick win |
| 21 | `CreateLeadExecutor` — Workflow → tạo Lead từ Survey | Thấp | Use case chính |
| 22 | `LeadQueryService` + `LeadStatsService` với cache tags | Trung | |
| 23 | `LeadApiController` (index + stats + pipeline + sources + kanban) | Trung | |
| 24 | `LeadObserver` + ActivityLog integration | Thấp | |
| 25 | View `leads/index.blade.php` — List view (Tabulator) | Trung | |
| 26 | View `leads/index.blade.php` — Pipeline (Kanban Alpine.js) | Trung | |
| 27 | View `leads/create.blade.php` — wizard 3 bước + địa chỉ cascade | Cao | Form phức tạp nhất |
| 28 | View `leads/show.blade.php` — 2 cột đầy đủ | Cao | |
| 29 | `ExportLeadsAction` (FastExcel) | Thấp | |
| 30 | `LeadActivityController` + `LeadNoteController` | Thấp | |
| 31 | Admin UI quản lý pipeline stages per org | Trung | |
| 32 | Admin UI quản lý lead sources per org | Trung | |
| 33 | Admin UI quản lý tag definitions per org | Thấp | |

---

## Phụ lục A — Quick reference: thay đổi từ v2 sang v3

| Thay đổi | Lý do |
|---|---|
| `organization_id NOT NULL` trên `leads` | Tránh `COALESCE` làm hỏng index, đơn giản hóa phân quyền |
| Tách `lead_sources` thành bảng | Cho phép mỗi org có nguồn riêng, không phụ thuộc code release để thêm source |
| `UNIQUE (organization_id, dedup_hash)` | Enforce dedup contact theo phạm vi org tại DB layer |
| `is_global` flag thay vì chỉ dùng `organization_id IS NULL` | Index partial hiệu quả hơn, semantic rõ ràng |
| `activity_count` + `lead_count` counter cache | Tránh COUNT(*) queries trên list/kanban |
| `idempotent_key` UNIQUE | Ngăn Workflow tạo lead trùng khi retry |
| `title` nullable trên `leads` | Hỗ trợ tiêu đề cơ hội riêng, fallback về contact info |
| Snapshot contact giảm từ 5 → 3 fields | Giảm storage, cache efficient hơn |
| `duration_minutes` + `attendee_count` columns | Thay `meta_key_N` — có nghĩa rõ ràng, index được |
| `organization_id` denormalized trên bảng con | Reporting không cần JOIN ngược về `leads` |
| `lead_tag_definitions` + `lead_tag_map` | Tag system normalized, filter/count nhanh |
| Covering indexes (`idx_list_view`, `idx_kanban`, ...) | Query hot path không cần read row data |
| Cache tags Redis | Invalidation per org chính xác |
| Global scope `org_context` trên Model | Tự động filter, tránh quên kiểm tra org |