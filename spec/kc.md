# Đặc Tả Module: Knowledge Center (Quản lý Kho Tri Thức)

> **Hệ thống:** SaaS SME  
> **Module:** Knowledge Center  
> **Phiên bản đặc tả:** 2.0.0  
> **Ngày cập nhật:** 2026-06-03  
> **Trạng thái:** Draft — Đã tối ưu cấu trúc (BIGINT PK, FK align với hệ thống hiện có)

---

## Mục lục

1. [Tổng quan module](#1-tổng-quan-module)
2. [Phạm vi & mục tiêu](#2-phạm-vi--mục-tiêu)
3. [Các sub-module](#3-các-sub-module)
4. [Enum & Constant Values](#4-enum--constant-values)
5. [ERD — Entity Relationship Diagram](#5-erd--entity-relationship-diagram)
6. [Đặc tả bảng dữ liệu](#6-đặc-tả-bảng-dữ-liệu)
   - 6.1 [KC_CATEGORY — Danh mục tài liệu](#61-kc_category--danh-mục-tài-liệu)
   - 6.2 [KC_ITEM — Tài liệu / Tri thức](#62-kc_item--tài-liệu--tri-thức)
   - 6.3 [KC_ITEM_ATTACHMENT — Tệp đính kèm](#63-kc_item_attachment--tệp-đính-kèm)
   - 6.4 [KC_TAG — Nhãn tag](#64-kc_tag--nhãn-tag)
   - 6.5 [KC_ITEM_TAG — Quan hệ Item–Tag](#65-kc_item_tag--quan-hệ-itemtag)
   - 6.6 [KC_VERSION_HISTORY — Lịch sử phiên bản](#66-kc_version_history--lịch-sử-phiên-bản)
   - 6.7 [KC_ACCESS_CONTROL — Phân quyền truy cập](#67-kc_access_control--phân-quyền-truy-cập)
   - 6.8 [KC_FEEDBACK — Phản hồi & đánh giá](#68-kc_feedback--phản-hồi--đánh-giá)
   - 6.9 [KC_VIEW_LOG — Nhật ký lượt xem](#69-kc_view_log--nhật-ký-lượt-xem)
7. [Luồng nghiệp vụ](#7-luồng-nghiệp-vụ)
   - 7.1 [Tạo & duyệt tài liệu](#71-tạo--duyệt-tài-liệu)
   - 7.2 [Versioning & rollback](#72-versioning--rollback)
   - 7.3 [Phân quyền truy cập](#73-phân-quyền-truy-cập)
   - 7.4 [Hết hiệu lực tự động](#74-hết-hiệu-lực-tự-động)
   - 7.5 [Tìm kiếm & lọc](#75-tìm-kiếm--lọc)
8. [API Endpoints (đề xuất)](#8-api-endpoints-đề-xuất)
9. [Business Rules & Ràng buộc](#9-business-rules--ràng-buộc)
10. [Indexes & Performance](#10-indexes--performance)
11. [Ghi chú triển khai cho SME](#11-ghi-chú-triển-khai-cho-sme)

---

## Thay đổi từ v1.0.0 → v2.0.0

| Hạng mục | v1.0.0 | v2.0.0 | Lý do |
|---|---|---|---|
| **PK type** | UUID làm PK | BIGINT AUTO_INCREMENT PK + cột `uuid` riêng | Khớp chuẩn toàn hệ thống (`$table->id()` + `$table->uuid()`) |
| **FK type** | UUID FK | BIGINT FK | Khớp với `users.id`, `organizations.id`, `departments.id` (tất cả đều BIGINT) |
| **KC_CATEGORY** | Thiếu `org_id`, `slug` UNIQUE toàn hệ | Thêm `org_id` FK, `slug` UNIQUE per org | Đây là resource per-org; slug global unique sẽ xung đột cross-org |
| **KC_ITEM_TAG** | Chỉ composite PK (item_id, tag_id) | Thêm `id` BIGINT + `uuid` | Chuẩn dự án yêu cầu mọi bảng đều có id + uuid |
| **KC_TAG** | Thiếu `updated_at` | Thêm `updated_at` | Nhất quán với các bảng khác |
| **KC_ACCESS_CONTROL.target_id** | UUID | BIGINT | `users.id`, `roles.id`, `departments.id` đều BIGINT |

---

## 1. Tổng quan module

**Knowledge Center** là module trung tâm quản lý toàn bộ tài sản tri thức của doanh nghiệp SME, bao gồm tài liệu nội bộ, quy trình vận hành, video hướng dẫn, biểu mẫu, FAQ, case study và chính sách/quy định.

### Mục đích chính

- Tập trung hóa tri thức doanh nghiệp vào một nơi duy nhất, dễ tìm kiếm
- Chuẩn hóa quy trình tạo — duyệt — phát hành tài liệu
- Kiểm soát phiên bản và lịch sử thay đổi nội dung
- Phân quyền truy cập linh hoạt theo user / vai trò / phòng ban
- Đo lường mức độ sử dụng và chất lượng tri thức

### Người dùng liên quan

| Vai trò | Quyền mặc định |
|---|---|
| **Admin** | Toàn quyền quản lý danh mục, tag, phân quyền, xem analytics |
| **Content Manager** | Tạo, chỉnh sửa, gửi duyệt, quản lý tài liệu trong phạm vi được phân |
| **Approver** | Duyệt / từ chối tài liệu được gửi lên |
| **Editor** | Tạo và sửa bản nháp, không tự duyệt |
| **Viewer** | Chỉ đọc tài liệu theo phạm vi visibility |

---

## 2. Phạm vi & mục tiêu

### Trong phạm vi (In Scope)

- Quản lý cây danh mục đa cấp (tối đa 3 cấp)
- Quản lý tài liệu với 7 loại (type): Tài liệu, SOP, Video, Biểu mẫu, FAQ, Case Study, Policy/Guideline
- Luồng duyệt: Draft → Pending Review → Approved / Rejected
- Versioning nội dung và rollback
- Đính kèm tệp (nhiều file/tài liệu), hỗ trợ S3 / GCS / local storage
- Gắn tag tự do
- Phân quyền xem/sửa theo user, role, department
- Tracking lượt xem và feedback
- Tìm kiếm full-text, lọc theo type, status, danh mục, tag
- Hết hiệu lực tự động (effective_date / expired_date)

### Ngoài phạm vi (Out of Scope)

- Tích hợp ký số điện tử
- Bình luận (comment thread) trên tài liệu — có thể mở rộng ở phiên bản sau
- Module học tập / LMS (nếu có, sẽ là module riêng tham chiếu KC_ITEM)

---

## 3. Các sub-module

### 3.1 Quản lý danh mục tài liệu (Category Management)

Cho phép tổ chức tài liệu thành cây phân cấp. Mỗi danh mục có thể có danh mục con, màu sắc và icon riêng để nhận diện trực quan.

**Chức năng:**

- Tạo / sửa / xóa mềm danh mục
- Kéo thả sắp xếp thứ tự (`sort_order`)
- Bật/tắt hiển thị danh mục (`is_active`)
- Xem số lượng tài liệu trong danh mục và các danh mục con

**Ràng buộc:**

- Không xóa danh mục khi còn tài liệu bên trong
- Cây danh mục tối đa 3 cấp
- `slug` là duy nhất trong phạm vi org (per org, không global)

### 3.2 Quản lý tài liệu / tri thức (Knowledge Item Management)

Cho phép tạo, quản lý và tìm kiếm toàn bộ tài liệu trong kho tri thức.

**Chức năng:**

- Tạo tài liệu mới với editor soạn thảo (Markdown / Rich Text)
- Chọn loại (type), danh mục, tag, visibility
- Đính kèm nhiều file
- Gửi duyệt và theo dõi trạng thái
- Xem lịch sử phiên bản, so sánh diff, rollback
- Ghim tài liệu nổi bật (`is_featured`) hoặc đầu danh sách (`is_pinned`)
- Đặt ngày hiệu lực và ngày hết hạn
- Phân quyền chi tiết từng tài liệu (override visibility mặc định)

### 3.3 Tìm kiếm & Khám phá (Search & Discovery)

- Full-text search trên `title`, `summary`, `content`
- Lọc đồng thời theo: type, status, category, tag, language, date range
- Sắp xếp: mới nhất, xem nhiều nhất, đánh giá cao nhất, tiêu đề A-Z
- Gợi ý tài liệu liên quan (cùng danh mục / tag)
- Trang "Nổi bật" và "Mới nhất"

### 3.4 Analytics & Báo cáo

- Tài liệu được xem nhiều nhất (7 ngày / 30 ngày)
- Tài liệu chưa được xem nào
- Thống kê theo loại (type)
- Đánh giá trung bình (average rating) theo tài liệu và danh mục
- Tài liệu sắp hết hạn (trong 30 ngày tới)

---

## 4. Enum & Constant Values

### 4.1 KC_ITEM.type — Loại tài liệu

| Giá trị | Nhãn hiển thị | Mô tả | Icon gợi ý |
|---|---|---|---|
| `document` | Tài liệu | Văn bản, báo cáo, hướng dẫn thông thường | ti-file-text |
| `sop` | SOP | Standard Operating Procedure — Quy trình chuẩn vận hành | ti-list-check |
| `video` | Video | Video hướng dẫn, đào tạo — lưu URL embed hoặc file | ti-video |
| `form` | Biểu mẫu | Template biểu mẫu, form điền sẵn, checklist | ti-forms |
| `faq` | FAQ | Câu hỏi thường gặp theo dạng Q&A | ti-help-circle |
| `case_study` | Case Study | Bài học, kinh nghiệm thực tế, tình huống điển hình | ti-bulb |
| `policy` | Policy/Guideline | Chính sách, quy định, hướng dẫn nội bộ | ti-scale |

### 4.2 KC_ITEM.status — Trạng thái luồng duyệt

| Giá trị | Nhãn | Mô tả | Màu gợi ý |
|---|---|---|---|
| `draft` | Bản nháp | Đang soạn, chưa gửi duyệt | Gray |
| `pending_review` | Chờ duyệt | Đã gửi, chờ approver xử lý | Amber |
| `approved` | Đã duyệt | Tài liệu chính thức, hiển thị theo visibility | Green |
| `rejected` | Bị từ chối | Approver từ chối, cần chỉnh sửa và gửi lại | Red |
| `archived` | Lưu trữ | Không còn hiệu lực, ẩn khỏi tìm kiếm nhưng vẫn truy cập được | Gray |

**Sơ đồ chuyển trạng thái:**

```
draft ──► pending_review ──► approved ──► archived
           │                    │
           ▼                    ▼
        rejected             archived
           │
           ▼
          draft (chỉnh sửa lại)
```

### 4.3 KC_ITEM.visibility — Phạm vi hiển thị

| Giá trị | Mô tả |
|---|---|
| `public` | Tất cả user đã đăng nhập trong org đều thấy |
| `internal` | Chỉ nhân viên chính thức (lọc theo role) |
| `restricted` | Chỉ những user / role / dept được cấp trong KC_ACCESS_CONTROL |
| `private` | Chỉ owner và admin hệ thống |

### 4.4 KC_ACCESS_CONTROL.permission — Mức phân quyền

| Giá trị | Mô tả |
|---|---|
| `view` | Chỉ xem nội dung |
| `edit` | Xem + chỉnh sửa, gửi duyệt |
| `manage` | Toàn quyền: xem, sửa, duyệt, xóa, phân quyền |

### 4.5 KC_ACCESS_CONTROL.target_type — Đối tượng phân quyền

| Giá trị | Bảng tham chiếu | PK type | Mô tả |
|---|---|---|---|
| `user` | `users` | BIGINT | Một user cụ thể |
| `role` | `roles` (Spatie) | BIGINT | Tất cả user có role này |
| `dept` | `departments` | BIGINT | Tất cả user thuộc phòng ban này |

> **Ghi chú:** `target_id` là BIGINT để khớp với PK của các bảng `users`, `roles`, `departments` trong hệ thống.

---

## 5. ERD — Entity Relationship Diagram

```
┌──────────────────────────────────────────────────────────────────┐
│                      KNOWLEDGE CENTER ERD                         │
│                  (Tất cả PK = BIGINT AUTO_INCREMENT)              │
└──────────────────────────────────────────────────────────────────┘

organizations (bảng có sẵn, BIGINT PK)
    │ 1
    │ has many
    │ N
KC_CATEGORY ──(parent_id FK → self)──► KC_CATEGORY (cây đa cấp)
┌──────────────────────────┐
│ id (BIGINT PK)           │
│ uuid (CHAR 36, UNIQUE)   │
│ org_id (FK → orgs)       │◄── BIGINT FK — tenant isolation
│ parent_id (FK → self)    │
│ name, slug (UNIQUE/org)  │
│ icon, color_hex          │
│ sort_order, is_active    │
│ created_by, updated_by   │◄── BIGINT FK → users
└──────────┬───────────────┘
           │ 1:N
           ▼
┌──────────────────────────────────────────────────────┐
│                      KC_ITEM                          │
│ id (BIGINT PK), uuid (UNIQUE)                        │
│ org_id (BIGINT FK → organizations)                   │
│ category_id (BIGINT FK → kc_categories)              │
│ owner_id, approved_by, created_by, updated_by        │◄── BIGINT FK → users
│ title, slug (UNIQUE/org), summary, content (LONGTEXT)│
│ type, status, visibility, language                   │
│ view_count, download_count                           │
│ is_featured, is_pinned                               │
│ version, effective_date, expired_date                │
│ approved_at, created_at, updated_at                  │
└──┬────┬────┬────┬────┬───────────────────────────────┘
   │    │    │    │    │
   │1   │1   │1   │1   │1
   ▼N   ▼N   ▼N   ▼N   ▼N
┌──────┐ ┌──────┐ ┌──────────┐ ┌──────────────┐ ┌──────────┐
│KC_   │ │KC_   │ │KC_       │ │KC_ACCESS_    │ │KC_VIEW_  │
│ITEM_ │ │ITEM_ │ │VERSION_  │ │CONTROL       │ │LOG       │
│ATTACH│ │TAG   │ │HISTORY   │ │              │ │          │
│MENT  │ │(pivot│ │          │ │target_id     │ │          │
│      │ │+id)  │ │          │ │(BIGINT —     │ │          │
│      │ └──┬───┘ │          │ │users/roles/  │ │          │
│      │    │N    │          │ │departments)  │ │          │
└──────┘    │     └──────────┘ └──────────────┘ └──────────┘
            │
          ┌─▼──────────┐
          │  KC_TAG     │
          │ id (BIGINT) │
          │ uuid        │
          │ org_id (FK) │◄── BIGINT FK
          └─────────────┘

KC_FEEDBACK (1 item → N feedbacks, user_id BIGINT FK)
```

### Quan hệ tổng hợp

| Bảng A | Quan hệ | Bảng B | FK type | Ghi chú |
|---|---|---|---|---|
| `organizations` | 1 → N | `kc_categories` | BIGINT | Tenant isolation |
| `kc_categories` | 1 → N | `kc_categories` | BIGINT | Tự tham chiếu — cây danh mục |
| `kc_categories` | 1 → N | `kc_items` | BIGINT | Mỗi tài liệu thuộc 1 danh mục |
| `kc_items` | 1 → N | `kc_item_attachments` | BIGINT | Nhiều tệp đính kèm |
| `kc_items` | N → M | `kc_tags` | BIGINT | Qua bảng `kc_item_tags` |
| `kc_items` | 1 → N | `kc_version_histories` | BIGINT | Lịch sử phiên bản |
| `kc_items` | 1 → N | `kc_access_controls` | BIGINT | Phân quyền chi tiết |
| `kc_items` | 1 → N | `kc_feedbacks` | BIGINT | Đánh giá từ người dùng |
| `kc_items` | 1 → N | `kc_view_logs` | BIGINT | Tracking lượt xem |

---

## 6. Đặc tả bảng dữ liệu

> **Quy ước chung:** Mọi bảng đều tuân theo chuẩn dự án:
> ```php
> $table->id();  // BIGINT AUTO_INCREMENT — PK nội bộ
> $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
> ```

---

### 6.1 KC_CATEGORY — Danh mục tài liệu

**Mục đích:** Tổ chức tài liệu thành cấu trúc cây phân cấp (tối đa 3 cấp). Hỗ trợ icon và màu sắc nhận diện.

**Thay đổi từ v1:** Thêm `org_id` (bắt buộc, FK BIGINT → organizations), đổi `slug` từ UNIQUE global sang UNIQUE per org.

| Trường | Kiểu dữ liệu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK AUTO_INCREMENT | | Khóa chính nội bộ |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID — expose ra API, không phải PK |
| `org_id` | BIGINT UNSIGNED | NOT NULL | FK (organizations), INDEX | | Tenant isolation — danh mục thuộc org nào |
| `parent_id` | BIGINT UNSIGNED | NULL | FK (self), INDEX | NULL | Danh mục cha — NULL nếu là cấp gốc |
| `name` | VARCHAR(150) | NOT NULL | | | Tên danh mục hiển thị |
| `slug` | VARCHAR(160) | NOT NULL | UNIQUE(org_id, slug) | | Định danh URL-friendly, unique trong org |
| `description` | TEXT | NULL | | NULL | Mô tả ngắn về nội dung danh mục |
| `icon` | VARCHAR(80) | NULL | | NULL | Tên Tabler Icon, vd: `ti-folder`, `ti-book` |
| `color_hex` | CHAR(7) | NULL | | NULL | Mã màu hex, vd: `#534AB7` |
| `sort_order` | INT | NOT NULL | | 0 | Thứ tự hiển thị trong cùng cấp cha |
| `is_active` | BOOLEAN | NOT NULL | | TRUE | FALSE = ẩn danh mục (soft disable) |
| `created_by` | BIGINT UNSIGNED | NOT NULL | FK (users) | | Người tạo |
| `updated_by` | BIGINT UNSIGNED | NULL | FK (users) | NULL | Người cập nhật lần cuối |
| `created_at` | TIMESTAMP | NOT NULL | | NOW() | |
| `updated_at` | TIMESTAMP | NOT NULL | | NOW() | |

**Migration:**

```php
Schema::create('kc_categories', function (Blueprint $table) {
    $table->id();
    $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
    $table->foreignId('org_id')->constrained('organizations')->restrictOnDelete();
    $table->foreignId('parent_id')->nullable()->constrained('kc_categories')->restrictOnDelete();
    $table->string('name', 150);
    $table->string('slug', 160);
    $table->text('description')->nullable();
    $table->string('icon', 80)->nullable();
    $table->char('color_hex', 7)->nullable();
    $table->integer('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();

    $table->unique(['org_id', 'slug']);
    $table->index(['org_id', 'parent_id', 'sort_order'], 'idx_kc_cat_sort');
    $table->index(['org_id', 'is_active'], 'idx_kc_cat_active');
});
```

**Ràng buộc:**

- `slug` phải là lowercase, alphanumeric và dấu gạch ngang (`-`)
- Không được đặt `parent_id` trỏ về chính mình (`id <> parent_id`)
- Trước khi xóa: kiểm tra không còn `kc_items` nào có `category_id` trỏ vào
- Trước khi xóa: kiểm tra không có danh mục con (`parent_id`) trỏ vào

---

### 6.2 KC_ITEM — Tài liệu / Tri thức

**Mục đích:** Bảng trung tâm lưu trữ toàn bộ nội dung tri thức với đầy đủ metadata, trạng thái duyệt và kiểm soát vòng đời.

**Thay đổi từ v1:** Đổi UUID PK → BIGINT + uuid; tất cả FK user/org sang BIGINT.

| Trường | Kiểu dữ liệu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK AUTO_INCREMENT | | Khóa chính nội bộ |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID — expose ra API |
| `category_id` | BIGINT UNSIGNED | NOT NULL | FK (kc_categories), INDEX | | Danh mục chứa tài liệu |
| `org_id` | BIGINT UNSIGNED | NOT NULL | FK (organizations), INDEX | | Tổ chức sở hữu — multi-tenant isolation |
| `title` | VARCHAR(300) | NOT NULL | | | Tiêu đề tài liệu |
| `slug` | VARCHAR(320) | NOT NULL | UNIQUE(org_id, slug) | | Định danh URL, unique trong org |
| `summary` | TEXT | NULL | | NULL | Tóm tắt ngắn — dùng cho danh sách, preview |
| `content` | LONGTEXT | NULL | FULLTEXT | NULL | Nội dung chính — Markdown hoặc HTML |
| `type` | ENUM | NOT NULL | INDEX | | Xem mục 4.1 |
| `status` | ENUM | NOT NULL | INDEX | `draft` | Xem mục 4.2 |
| `visibility` | ENUM | NOT NULL | | `internal` | Xem mục 4.3 |
| `language` | CHAR(5) | NULL | | `vi` | BCP 47: `vi`, `en`, `ja` |
| `view_count` | INT UNSIGNED | NOT NULL | | 0 | Denormalized từ kc_view_logs |
| `download_count` | INT UNSIGNED | NOT NULL | | 0 | Tổng lượt tải về |
| `is_featured` | BOOLEAN | NOT NULL | | FALSE | Hiển thị nổi bật trên trang chủ KC |
| `is_pinned` | BOOLEAN | NOT NULL | | FALSE | Ghim lên đầu trong danh mục |
| `owner_id` | BIGINT UNSIGNED | NOT NULL | FK (users) | | Người chịu trách nhiệm nội dung |
| `approved_by` | BIGINT UNSIGNED | NULL | FK (users) | NULL | Người đã duyệt tài liệu |
| `approved_at` | TIMESTAMP | NULL | | NULL | Thời điểm được duyệt |
| `version` | INT UNSIGNED | NOT NULL | | 1 | Số phiên bản hiện tại, tăng dần khi approve |
| `effective_date` | TIMESTAMP | NULL | | NULL | Ngày tài liệu bắt đầu có hiệu lực |
| `expired_date` | TIMESTAMP | NULL | INDEX | NULL | Ngày hết hiệu lực — cron tự chuyển archived |
| `created_by` | BIGINT UNSIGNED | NOT NULL | FK (users) | | Người tạo |
| `updated_by` | BIGINT UNSIGNED | NULL | FK (users) | NULL | Người cập nhật lần cuối |
| `created_at` | TIMESTAMP | NOT NULL | | NOW() | |
| `updated_at` | TIMESTAMP | NOT NULL | | NOW() | |

**Migration:**

```php
Schema::create('kc_items', function (Blueprint $table) {
    $table->id();
    $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
    $table->foreignId('category_id')->constrained('kc_categories')->restrictOnDelete();
    $table->foreignId('org_id')->constrained('organizations')->restrictOnDelete();
    $table->string('title', 300);
    $table->string('slug', 320);
    $table->text('summary')->nullable();
    $table->longText('content')->nullable();
    $table->enum('type', ['document', 'sop', 'video', 'form', 'faq', 'case_study', 'policy'])->index();
    $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected', 'archived'])
          ->default('draft')->index();
    $table->enum('visibility', ['public', 'internal', 'restricted', 'private'])->default('internal');
    $table->char('language', 5)->nullable()->default('vi');
    $table->unsignedInteger('view_count')->default(0);
    $table->unsignedInteger('download_count')->default(0);
    $table->boolean('is_featured')->default(false);
    $table->boolean('is_pinned')->default(false);
    $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('approved_at')->nullable();
    $table->unsignedInteger('version')->default(1);
    $table->timestamp('effective_date')->nullable();
    $table->timestamp('expired_date')->nullable();
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();

    $table->unique(['org_id', 'slug']);
    $table->index(['org_id', 'category_id'], 'idx_kc_item_org_cat');
    $table->index(['org_id', 'status', 'is_featured'], 'idx_kc_item_homepage');
    $table->index(['org_id', 'expired_date', 'status'], 'idx_kc_item_expiry');
    $table->fullText(['title', 'summary', 'content'], 'idx_kc_item_search');
});
```

**Ghi chú đặc biệt:**

- `type = 'video'`: `content` lưu embed URL hoặc video ID; file thực tế đặt trong `kc_item_attachments`
- `type = 'faq'`: `content` lưu nội dung dạng Markdown với cú pháp Q&A, **không dùng JSON column**
- `type = 'form'`: `content` chứa template biểu mẫu dạng Markdown/HTML; file `.docx`/`.xlsx` đặt trong `kc_item_attachments`
- `view_count` là denormalized counter — cập nhật async từ `kc_view_logs` để tránh lock tranh chấp

---

### 6.3 KC_ITEM_ATTACHMENT — Tệp đính kèm

**Mục đích:** Lưu trữ metadata của tất cả tệp đính kèm liên quan đến một tài liệu.

| Trường | Kiểu dữ liệu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK AUTO_INCREMENT | | Khóa chính nội bộ |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `item_id` | BIGINT UNSIGNED | NOT NULL | FK (kc_items), INDEX | | Tài liệu chứa file này |
| `file_name` | VARCHAR(255) | NOT NULL | | | Tên file gốc khi upload |
| `file_url` | TEXT | NOT NULL | | | URL đầy đủ để tải/xem file |
| `file_type` | VARCHAR(50) | NOT NULL | | | MIME type: `application/pdf`, `video/mp4`, ... |
| `file_size_kb` | INT UNSIGNED | NOT NULL | | | Kích thước file tính bằng KB |
| `storage_provider` | VARCHAR(20) | NOT NULL | | `s3` | Nơi lưu: `s3`, `gcs`, `local` |
| `storage_key` | VARCHAR(500) | NOT NULL | | | Object key / đường dẫn nội bộ trên storage |
| `sort_order` | INT | NOT NULL | | 0 | Thứ tự hiển thị file trong danh sách |
| `uploaded_by` | BIGINT UNSIGNED | NOT NULL | FK (users) | | Người upload |
| `uploaded_at` | TIMESTAMP | NOT NULL | | NOW() | Thời điểm upload |

**Migration:**

```php
Schema::create('kc_item_attachments', function (Blueprint $table) {
    $table->id();
    $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
    $table->foreignId('item_id')->constrained('kc_items')->cascadeOnDelete();
    $table->string('file_name', 255);
    $table->text('file_url');
    $table->string('file_type', 50);
    $table->unsignedInteger('file_size_kb');
    $table->string('storage_provider', 20)->default('s3');
    $table->string('storage_key', 500);
    $table->integer('sort_order')->default(0);
    $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
    $table->timestamp('uploaded_at')->useCurrent();

    $table->index(['item_id', 'sort_order'], 'idx_kc_attach_sort');
});
```

---

### 6.4 KC_TAG — Nhãn tag

**Mục đích:** Danh sách tag/nhãn tự do của tổ chức, dùng để gắn vào tài liệu nhằm tăng khả năng tìm kiếm chéo danh mục.

**Thay đổi từ v1:** Đổi UUID PK → BIGINT + uuid; thêm `updated_at`.

| Trường | Kiểu dữ liệu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK AUTO_INCREMENT | | Khóa chính nội bộ |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `org_id` | BIGINT UNSIGNED | NOT NULL | FK (organizations), INDEX | | Tổ chức sở hữu tag |
| `name` | VARCHAR(80) | NOT NULL | | | Tên tag hiển thị, vd: "ISO 9001", "Onboarding" |
| `slug` | VARCHAR(90) | NOT NULL | UNIQUE(org_id, slug) | | Định danh, vd: `iso-9001` |
| `color_hex` | CHAR(7) | NULL | | NULL | Màu hiển thị badge tag |
| `created_at` | TIMESTAMP | NOT NULL | | NOW() | |
| `updated_at` | TIMESTAMP | NOT NULL | | NOW() | |

**Migration:**

```php
Schema::create('kc_tags', function (Blueprint $table) {
    $table->id();
    $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
    $table->foreignId('org_id')->constrained('organizations')->restrictOnDelete();
    $table->string('name', 80);
    $table->string('slug', 90);
    $table->char('color_hex', 7)->nullable();
    $table->timestamps();

    $table->unique(['org_id', 'slug']);
    $table->index('org_id', 'idx_kc_tag_org');
});
```

---

### 6.5 KC_ITEM_TAG — Quan hệ Item–Tag

**Mục đích:** Bảng pivot nhiều–nhiều giữa `kc_items` và `kc_tags`.

**Thay đổi từ v1:** Thêm `id` BIGINT và `uuid` theo chuẩn dự án. Ràng buộc unique (item_id, tag_id) đảm bảo không trùng lặp.

| Trường | Kiểu dữ liệu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK AUTO_INCREMENT | | Khóa chính — theo chuẩn dự án |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `item_id` | BIGINT UNSIGNED | NOT NULL | FK (kc_items), INDEX | | Tài liệu |
| `tag_id` | BIGINT UNSIGNED | NOT NULL | FK (kc_tags), INDEX | | Tag được gắn |

**Migration:**

```php
Schema::create('kc_item_tags', function (Blueprint $table) {
    $table->id();
    $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
    $table->foreignId('item_id')->constrained('kc_items')->cascadeOnDelete();
    $table->foreignId('tag_id')->constrained('kc_tags')->cascadeOnDelete();

    $table->unique(['item_id', 'tag_id'], 'idx_kc_item_tag_unique');
    $table->index('tag_id', 'idx_kc_item_tag_tag');
});
```

---

### 6.6 KC_VERSION_HISTORY — Lịch sử phiên bản

**Mục đích:** Lưu snapshot nội dung mỗi lần tài liệu được duyệt, cho phép xem lại và rollback.

| Trường | Kiểu dữ liệu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK AUTO_INCREMENT | | Khóa chính nội bộ |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `item_id` | BIGINT UNSIGNED | NOT NULL | FK (kc_items), INDEX | | Tài liệu |
| `version_number` | INT UNSIGNED | NOT NULL | | | Số phiên bản (bằng KC_ITEM.version tại thời điểm snapshot) |
| `title_snapshot` | VARCHAR(300) | NOT NULL | | | Tiêu đề tại thời điểm snapshot |
| `content_snapshot` | LONGTEXT | NOT NULL | | | Toàn bộ nội dung tại thời điểm snapshot |
| `change_summary` | TEXT | NULL | | NULL | Ghi chú thay đổi so với version trước |
| `changed_by` | BIGINT UNSIGNED | NOT NULL | FK (users) | | Người thực hiện thay đổi |
| `changed_at` | TIMESTAMP | NOT NULL | | NOW() | Thời điểm snapshot được tạo |

**Migration:**

```php
Schema::create('kc_version_histories', function (Blueprint $table) {
    $table->id();
    $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
    $table->foreignId('item_id')->constrained('kc_items')->cascadeOnDelete();
    $table->unsignedInteger('version_number');
    $table->string('title_snapshot', 300);
    $table->longText('content_snapshot');
    $table->text('change_summary')->nullable();
    $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
    $table->timestamp('changed_at')->useCurrent();

    $table->unique(['item_id', 'version_number'], 'idx_kc_ver_unique');
    $table->index('item_id', 'idx_kc_ver_item');
});
```

**Ghi chú:**

- Snapshot được tạo tự động mỗi khi tài liệu chuyển sang trạng thái `approved`
- Với SME, nên giới hạn giữ lại tối đa **20 version gần nhất** / tài liệu để kiểm soát storage
- Rollback: tạo version mới với `content_snapshot` từ version cũ, không xóa lịch sử

---

### 6.7 KC_ACCESS_CONTROL — Phân quyền truy cập

**Mục đích:** Quản lý quyền truy cập chi tiết từng tài liệu khi `visibility = 'restricted'`.

**Thay đổi từ v1:** `target_id` đổi thành BIGINT (khớp với `users.id`, `roles.id`, `departments.id` — tất cả đều BIGINT trong hệ thống hiện có).

| Trường | Kiểu dữ liệu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK AUTO_INCREMENT | | Khóa chính nội bộ |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `item_id` | BIGINT UNSIGNED | NOT NULL | FK (kc_items), INDEX | | Tài liệu được phân quyền |
| `target_type` | ENUM | NOT NULL | INDEX | | `user` / `role` / `dept` |
| `target_id` | BIGINT UNSIGNED | NOT NULL | INDEX | | ID của user / role / dept — BIGINT khớp PK bảng tương ứng |
| `permission` | ENUM | NOT NULL | | `view` | `view` / `edit` / `manage` |
| `granted_at` | TIMESTAMP | NOT NULL | | NOW() | Thời điểm cấp quyền |
| `granted_by` | BIGINT UNSIGNED | NOT NULL | FK (users) | | Người cấp quyền |
| `expired_at` | TIMESTAMP | NULL | | NULL | Quyền hết hạn tự động (nếu có) |

**Migration:**

```php
Schema::create('kc_access_controls', function (Blueprint $table) {
    $table->id();
    $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
    $table->foreignId('item_id')->constrained('kc_items')->cascadeOnDelete();
    $table->enum('target_type', ['user', 'role', 'dept']);
    $table->unsignedBigInteger('target_id'); // Không dùng foreignId vì polymorphic
    $table->enum('permission', ['view', 'edit', 'manage'])->default('view');
    $table->timestamp('granted_at')->useCurrent();
    $table->foreignId('granted_by')->constrained('users')->restrictOnDelete();
    $table->timestamp('expired_at')->nullable();

    $table->unique(['item_id', 'target_type', 'target_id'], 'idx_kc_access_unique');
    $table->index(['target_type', 'target_id'], 'idx_kc_access_target');
    $table->index('item_id', 'idx_kc_access_item');
});
```

**Logic kiểm tra quyền (Permission Resolution):**

```
Khi user U truy cập item I:
1. Nếu U là admin → ALLOW (manage)
2. Nếu I.visibility = 'public' → ALLOW (view)
3. Nếu I.visibility = 'private' → chỉ owner hoặc admin
4. Nếu I.visibility = 'internal' → kiểm tra U có role nhân viên không
5. Nếu I.visibility = 'restricted':
   a. Tìm record trong kc_access_controls với:
      - target_type='user' AND target_id = U.id (BIGINT), OR
      - target_type='role' AND target_id IN (U.role_ids — BIGINT), OR
      - target_type='dept' AND target_id = U.department_id (BIGINT)
   b. Lấy permission cao nhất trong kết quả trả về
   c. Kiểm tra expired_at chưa quá hạn
6. Nếu không khớp → DENY
```

---

### 6.8 KC_FEEDBACK — Phản hồi & đánh giá

**Mục đích:** Thu thập đánh giá chất lượng tài liệu từ người dùng. Mỗi user chỉ được đánh giá 1 lần / tài liệu (có thể cập nhật).

| Trường | Kiểu dữ liệu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK AUTO_INCREMENT | | Khóa chính nội bộ |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `item_id` | BIGINT UNSIGNED | NOT NULL | FK (kc_items), INDEX | | Tài liệu được đánh giá |
| `user_id` | BIGINT UNSIGNED | NOT NULL | FK (users), INDEX | | Người đánh giá |
| `rating` | SMALLINT | NULL | | NULL | Điểm 1–5 sao (nullable nếu chỉ vote helpful) |
| `comment` | TEXT | NULL | | NULL | Nhận xét chi tiết |
| `is_helpful` | BOOLEAN | NULL | | NULL | Nhanh: "Tài liệu này có hữu ích không?" |
| `created_at` | TIMESTAMP | NOT NULL | | NOW() | |
| `updated_at` | TIMESTAMP | NOT NULL | | NOW() | |

**Migration:**

```php
Schema::create('kc_feedbacks', function (Blueprint $table) {
    $table->id();
    $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
    $table->foreignId('item_id')->constrained('kc_items')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->smallInteger('rating')->nullable();
    $table->text('comment')->nullable();
    $table->boolean('is_helpful')->nullable();
    $table->timestamps();

    $table->unique(['item_id', 'user_id'], 'idx_kc_feedback_unique');
    $table->index('item_id', 'idx_kc_feedback_item');
});
```

---

### 6.9 KC_VIEW_LOG — Nhật ký lượt xem

**Mục đích:** Ghi lại từng lượt xem tài liệu để phân tích mức độ sử dụng. Bảng này tăng trưởng nhanh — cần partition theo thời gian.

| Trường | Kiểu dữ liệu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK AUTO_INCREMENT | | Khóa chính nội bộ |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `item_id` | BIGINT UNSIGNED | NOT NULL | FK (kc_items), INDEX | | Tài liệu được xem |
| `user_id` | BIGINT UNSIGNED | NULL | FK (users), INDEX | NULL | User xem (NULL nếu anonymous/public) |
| `session_id` | VARCHAR(100) | NULL | | NULL | Session ID để dedup lượt xem |
| `ip_address` | VARCHAR(45) | NULL | | NULL | IP (IPv4 hoặc IPv6) |
| `user_agent` | TEXT | NULL | | NULL | Browser / device info |
| `viewed_at` | TIMESTAMP | NOT NULL | INDEX | NOW() | Thời điểm xem |

**Migration:**

```php
Schema::create('kc_view_logs', function (Blueprint $table) {
    $table->id();
    $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
    $table->foreignId('item_id')->constrained('kc_items')->cascadeOnDelete();
    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->string('session_id', 100)->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->timestamp('viewed_at')->useCurrent();

    $table->index(['item_id', 'viewed_at'], 'idx_kc_viewlog_item');
    $table->index(['user_id', 'viewed_at'], 'idx_kc_viewlog_user');
});
```

**Partitioning (khuyến nghị cho production):**

```sql
-- Partition by month để quản lý size (MySQL 8+)
ALTER TABLE kc_view_logs
PARTITION BY RANGE (YEAR(viewed_at) * 100 + MONTH(viewed_at)) (
  PARTITION p202601 VALUES LESS THAN (202602),
  PARTITION p202602 VALUES LESS THAN (202603),
  ...
);
```

---

## 7. Luồng nghiệp vụ

### 7.1 Tạo & duyệt tài liệu

```
[Editor/Content Manager]
       │
       ▼
  Tạo tài liệu mới
  (status = 'draft')
       │
       ├─► Lưu nháp (tiếp tục chỉnh sửa)
       │
       ▼
  Gửi duyệt
  (status → 'pending_review')
       │
       ▼
[Approver nhận notification]
       │
       ├─► Từ chối (status → 'rejected')
       │        └─► Gửi lý do → Editor chỉnh sửa lại → Gửi duyệt lại
       │
       └─► Duyệt (status → 'approved')
                 ├─► version += 1
                 ├─► approved_by = approver.id (BIGINT)
                 ├─► approved_at = NOW()
                 └─► Tạo bản ghi kc_version_histories
                           └─► Tài liệu hiển thị theo visibility
```

**Điều kiện gửi duyệt:**

- `title` không được để trống
- `category_id` phải hợp lệ và thuộc cùng org
- `type` phải được chọn
- Nếu `type = 'sop'` hoặc `type = 'policy'`: yêu cầu có `summary`

### 7.2 Versioning & rollback

```
Khi Approve:
  1. Tăng kc_items.version += 1
  2. INSERT INTO kc_version_histories:
     - version_number = kc_items.version (mới)
     - content_snapshot = kc_items.content (bản được duyệt)
     - title_snapshot = kc_items.title
     - changed_by = approver.id (BIGINT)
  3. Cập nhật kc_items (approved_by, approved_at, status)

Khi Rollback về version V:
  1. Tìm bản ghi kc_version_histories với version_number = V
  2. Tạo version mới từ snapshot:
     kc_items.content = snapshot.content_snapshot
     kc_items.title = snapshot.title_snapshot
     kc_items.version += 1 (không đặt lại về V)
     kc_items.status = 'draft' (cần duyệt lại)
  3. Ghi lại change_summary: "Rolled back to version V"
```

### 7.3 Phân quyền truy cập

Xem chi tiết tại mục **6.7 KC_ACCESS_CONTROL — Logic kiểm tra quyền**.

**Kịch bản thường gặp cho SME:**

| Tình huống | Cấu hình |
|---|---|
| Tài liệu dùng chung toàn công ty | `visibility = 'internal'` |
| Quy trình chỉ dành cho phòng Kỹ thuật | `visibility = 'restricted'` + cấp quyền `target_type='dept', target_id = departments.id` |
| Tài liệu nhạy cảm HR | `visibility = 'restricted'` + cấp quyền cho 2–3 user cụ thể |
| SOP cho team mới onboard | `visibility = 'public'` (trong org) |
| Bản nháp chưa muốn ai thấy | `visibility = 'private'` |

### 7.4 Hết hiệu lực tự động

```
[Cron Job — chạy mỗi ngày lúc 01:00]
  1. SELECT * FROM kc_items
     WHERE status = 'approved'
       AND expired_date IS NOT NULL
       AND expired_date <= NOW()

  2. Với mỗi tài liệu tìm thấy:
     a. UPDATE status = 'archived'
     b. INSERT notification → owner_id: "Tài liệu X đã hết hiệu lực"

  3. Với tài liệu SOP/Policy sắp hết hạn trong 30 ngày:
     a. Gửi cảnh báo trước cho owner_id
     b. INSERT reminder notification
```

### 7.5 Tìm kiếm & lọc

**Query logic đề xuất:**

```sql
SELECT i.*
FROM kc_items i
JOIN kc_categories c ON i.category_id = c.id
WHERE
  i.org_id = :org_id        -- BIGINT FK
  AND i.status = 'approved'
  AND (
    i.visibility = 'public'
    OR i.visibility = 'internal'
    OR (i.visibility = 'restricted' AND EXISTS (
      SELECT 1 FROM kc_access_controls ac
      WHERE ac.item_id = i.id
        AND (
          (ac.target_type = 'user' AND ac.target_id = :user_id)        -- BIGINT
          OR (ac.target_type = 'role' AND ac.target_id IN (:user_role_ids))  -- BIGINT[]
          OR (ac.target_type = 'dept' AND ac.target_id = :user_dept_id)    -- BIGINT
        )
        AND (ac.expired_at IS NULL OR ac.expired_at > NOW())
    ))
  )
  AND (
    :keyword IS NULL
    OR MATCH(i.title, i.summary, i.content) AGAINST(:keyword IN BOOLEAN MODE)
  )
  AND (:type IS NULL OR i.type = :type)
  AND (:category_id IS NULL OR i.category_id = :category_id)
ORDER BY
  CASE :sort
    WHEN 'newest' THEN i.created_at
    WHEN 'views'  THEN i.view_count
  END DESC;
```

---

## 8. API Endpoints (đề xuất)

> **Ghi chú:** Tất cả endpoint dùng `{uuid}` thay vì `{id}` khi trả về resource — expose UUID ra ngoài, không expose BIGINT nội bộ.

### Danh mục (Category)

| Method | Endpoint | Mô tả |
|---|---|---|
| GET | `/api/kc/categories` | Lấy toàn bộ cây danh mục |
| GET | `/api/kc/categories/{uuid}` | Lấy chi tiết 1 danh mục + danh mục con |
| POST | `/api/kc/categories` | Tạo danh mục mới |
| PUT | `/api/kc/categories/{uuid}` | Cập nhật danh mục |
| DELETE | `/api/kc/categories/{uuid}` | Xóa danh mục (có kiểm tra ràng buộc) |
| PUT | `/api/kc/categories/reorder` | Cập nhật thứ tự sắp xếp |

### Tài liệu (Items)

| Method | Endpoint | Mô tả |
|---|---|---|
| GET | `/api/kc/items` | Danh sách tài liệu (filter, search, sort, paging) |
| GET | `/api/kc/items/{uuid}` | Chi tiết tài liệu + track view |
| GET | `/api/kc/items/{uuid}/versions` | Lịch sử phiên bản |
| GET | `/api/kc/items/{uuid}/versions/{v}` | Nội dung tại version V |
| POST | `/api/kc/items` | Tạo tài liệu mới (status = draft) |
| PUT | `/api/kc/items/{uuid}` | Cập nhật tài liệu |
| POST | `/api/kc/items/{uuid}/submit` | Gửi duyệt |
| POST | `/api/kc/items/{uuid}/approve` | Duyệt tài liệu |
| POST | `/api/kc/items/{uuid}/reject` | Từ chối duyệt (kèm lý do) |
| POST | `/api/kc/items/{uuid}/archive` | Lưu trữ tài liệu |
| POST | `/api/kc/items/{uuid}/rollback/{version}` | Rollback về phiên bản cũ |
| DELETE | `/api/kc/items/{uuid}` | Xóa mềm tài liệu |

### Tệp đính kèm (Attachments)

| Method | Endpoint | Mô tả |
|---|---|---|
| GET | `/api/kc/items/{uuid}/attachments` | Danh sách file đính kèm |
| POST | `/api/kc/items/{uuid}/attachments` | Upload file đính kèm |
| DELETE | `/api/kc/items/{uuid}/attachments/{auuid}` | Xóa file đính kèm |

### Tags

| Method | Endpoint | Mô tả |
|---|---|---|
| GET | `/api/kc/tags` | Danh sách tag của org |
| POST | `/api/kc/tags` | Tạo tag mới |
| PUT | `/api/kc/tags/{uuid}` | Cập nhật tag |
| DELETE | `/api/kc/tags/{uuid}` | Xóa tag |

### Phân quyền

| Method | Endpoint | Mô tả |
|---|---|---|
| GET | `/api/kc/items/{uuid}/permissions` | Danh sách phân quyền của tài liệu |
| POST | `/api/kc/items/{uuid}/permissions` | Cấp quyền cho user/role/dept |
| DELETE | `/api/kc/items/{uuid}/permissions/{puuid}` | Thu hồi quyền |

### Feedback

| Method | Endpoint | Mô tả |
|---|---|---|
| POST | `/api/kc/items/{uuid}/feedback` | Gửi đánh giá |
| PUT | `/api/kc/items/{uuid}/feedback` | Cập nhật đánh giá |
| GET | `/api/kc/items/{uuid}/feedback/summary` | Tổng hợp rating của tài liệu |

### Analytics

| Method | Endpoint | Mô tả |
|---|---|---|
| GET | `/api/kc/analytics/top-viewed` | Tài liệu xem nhiều nhất |
| GET | `/api/kc/analytics/by-type` | Thống kê theo loại |
| GET | `/api/kc/analytics/expiring-soon` | Tài liệu sắp hết hạn |
| GET | `/api/kc/analytics/unread` | Tài liệu chưa được xem |

---

## 9. Business Rules & Ràng buộc

### BR-KC-001: Phân cấp danh mục

- Cây danh mục tối đa **3 cấp** (root → level 1 → level 2)
- Không cho phép di chuyển danh mục tạo ra chu kỳ (`parent_id` không được trỏ về con cháu của chính nó)
- Xóa danh mục chỉ được phép khi: không có tài liệu và không có danh mục con
- Danh mục thuộc về org — không chia sẻ cross-org

### BR-KC-002: Luồng duyệt

- Chỉ những user có role `approver` hoặc `admin` mới được thực hiện approve/reject
- Tác giả tài liệu không được tự duyệt tài liệu của mình (trừ admin)
- Khi reject: bắt buộc nhập lý do (trường `comment` trong response body)
- Tài liệu `approved` muốn chỉnh sửa: tạo `draft` mới từ version hiện tại, version cũ vẫn còn hiệu lực cho đến khi có version `approved` mới

### BR-KC-003: Versioning

- Mỗi lần `approved`: bắt buộc tạo snapshot vào `kc_version_histories`
- Giữ tối đa **20 versions** gần nhất / tài liệu; version cũ hơn xóa tự động (cron)
- Rollback chỉ tạo version mới từ snapshot cũ, không xóa lịch sử

### BR-KC-004: Tệp đính kèm

- Giới hạn kích thước mỗi file: **50MB** (configurable per org plan)
- Tổng kích thước tệp đính kèm / tài liệu: **200MB**
- File type được phép (configurable): `.pdf`, `.docx`, `.xlsx`, `.pptx`, `.png`, `.jpg`, `.mp4`, `.zip`

### BR-KC-005: Feedback

- Mỗi user chỉ có **1 feedback record / tài liệu** (upsert — unique constraint trên item_id + user_id)
- Không cho phép feedback tài liệu ở trạng thái `draft` hoặc `rejected`

### BR-KC-006: Counting & Analytics

- `view_count` chỉ tăng **1 lần / user / session / 24h** / tài liệu (dedup bằng `session_id` hoặc `user_id + item_id + ngày`)
- Cập nhật `view_count` bất đồng bộ (queue-based) để không block request đọc tài liệu
- `download_count` tăng mỗi khi user click download file từ `kc_item_attachments`

### BR-KC-007: Multi-tenancy

- Tất cả query đều phải có điều kiện `org_id = :current_org_id` (BIGINT)
- Tag và danh mục cũng là per-org (không chia sẻ giữa các org)
- Slug unique trong phạm vi từng org, không cần global unique

---

## 10. Indexes & Performance

### Index tổng hợp quan trọng

```sql
-- Trang chủ Knowledge Center: lấy tài liệu nổi bật, mới nhất
CREATE INDEX idx_kc_item_homepage
  ON kc_items(org_id, status, is_featured, created_at DESC)
  WHERE status = 'approved';

-- Lọc theo danh mục + type (thường dùng nhất)
CREATE INDEX idx_kc_item_category_type
  ON kc_items(org_id, category_id, type, status);

-- Tìm tài liệu sắp hết hạn (cron + alert dashboard)
CREATE INDEX idx_kc_item_expiry
  ON kc_items(org_id, expired_date, status)
  WHERE expired_date IS NOT NULL AND status = 'approved';

-- Phân quyền: lookup nhanh theo target
CREATE INDEX idx_kc_access_lookup
  ON kc_access_controls(item_id, target_type, target_id, permission)
  WHERE expired_at IS NULL OR expired_at > NOW();
```

### Lưu ý hiệu năng

| Vấn đề | Giải pháp đề xuất |
|---|---|
| `kc_view_logs` tăng trưởng vô hạn | Partition by month; chỉ giữ raw log 6 tháng, aggregate hàng ngày vào bảng stats riêng |
| Full-text search chậm khi data lớn | Kết hợp Elasticsearch / OpenSearch cho search; MySQL FULLTEXT dùng cho SME nhỏ |
| Visibility check phức tạp trong mỗi query | Cache danh sách `item_id` user có quyền xem vào Redis (TTL 5 phút) |
| `view_count` update gây lock | Dùng message queue (Redis + worker) để async update |
| Snapshot `content_snapshot` tốn storage | Nén bằng gzip trước khi lưu vào LONGTEXT; hoặc lưu chỉ diff |

---

## 11. Ghi chú triển khai cho SME

### Giai đoạn 1 — MVP (1–2 tháng)

- [ ] `kc_categories` + `kc_items` + `kc_item_attachments`
- [ ] `kc_tags` + `kc_item_tags`
- [ ] Luồng Draft → Approved (không cần Pending Review nếu org nhỏ)
- [ ] Tìm kiếm full-text cơ bản
- [ ] `visibility`: chỉ `public` và `internal`

### Giai đoạn 2 — Mở rộng (tháng 3–4)

- [ ] `kc_version_histories` + Rollback
- [ ] `kc_access_controls` + `visibility = 'restricted'`
- [ ] `kc_feedbacks` + Rating
- [ ] Luồng duyệt đầy đủ (Pending Review → Approve/Reject)
- [ ] Cron job hết hiệu lực tự động

### Giai đoạn 3 — Analytics & tối ưu (tháng 5+)

- [ ] `kc_view_logs` + Dashboard analytics
- [ ] Notification (sắp hết hạn, tài liệu mới, bị từ chối)
- [ ] Tích hợp Elasticsearch nếu data lớn
- [ ] Export tài liệu ra PDF/Word

### Cấu hình khuyến nghị cho SME quy mô nhỏ (< 50 nhân viên)

- Cây danh mục: 2 cấp là đủ, tránh phức tạp cho người dùng
- Luồng duyệt: có thể bỏ qua Pending Review, owner tự approve
- Giữ lại 10 version / tài liệu
- Log lượt xem 3 tháng, sau đó aggregate và xóa raw log
- Upload storage: bắt đầu với local storage / MinIO trước khi migrate lên S3

---

*Version 2.0.0 — Knowledge Center Module Specification*  
*Stack: Laravel 13 · MySQL 8+ / SQLite (dev) · Alpine.js 3*  
*PK Convention: BIGINT AUTO_INCREMENT (id) + CHAR(36) UUID (uuid) — theo chuẩn toàn hệ thống*
