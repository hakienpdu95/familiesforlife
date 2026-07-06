# Đặc Tả Module: Job Posting Center

> **Hệ thống:** SaaS SME
> **Module:** Job Posting Center (tách độc lập khỏi Recruitment)
> **Phiên bản:** 1.1.0
> **Ngày:** 2026-06-05
> **Stack:** Laravel 13 · SQLite (dev) / MySQL 8+ / PostgreSQL 15+
> **Liên module:** Recruitment Center (downstream — tiếp nhận ứng viên vào pipeline), Marketplace Center (downstream — phân phối tin ra cổng công khai)

---

## Mục lục

1. [Tổng quan & Định vị module](#1-tổng-quan--định-vị-module)
2. [Phạm vi](#2-phạm-vi)
3. [Kiến trúc tổng thể](#3-kiến-trúc-tổng-thể)
4. [Enum Values](#4-enum-values)
5. [ERD — Quan hệ bảng](#5-erd--quan-hệ-bảng)
6. [Đặc tả bảng dữ liệu](#6-đặc-tả-bảng-dữ-liệu)
7. [Luồng nghiệp vụ](#7-luồng-nghiệp-vụ)
8. [Query Patterns](#8-query-patterns)
9. [API Endpoints](#9-api-endpoints)
10. [Business Rules](#10-business-rules)
11. [Indexes & Caching](#11-indexes--caching)
12. [Lộ trình triển khai](#12-lộ-trình-triển-khai)

---

## 1. Tổng quan & Định vị module

**Job Posting Center** là module chuyên biệt để tạo, quản lý và phân phối tin tuyển dụng. Module này đứng độc lập, đóng vai trò **nguồn dữ liệu trung tâm** (single source of truth) cho mọi tin tuyển dụng của tổ chức.

### Lý do tách riêng khỏi Recruitment

Recruitment Center là ATS nội bộ (pipeline, phỏng vấn, offer). Job Posting là **sản phẩm nội dung** — một tin tuyển dụng được tạo một lần nhưng phân phối đến nhiều kênh:

```
JP_JOB_POST (nguồn gốc)
    ├── Marketplace Center (cổng công khai SME platform)
    ├── Career Page riêng của org (API public)
    ├── LinkedIn / job boards (webhook/export)
    └── Recruitment Center (tiếp nhận ứng viên vào pipeline)
```

### Người dùng

| Vai trò | Quyền |
|---|---|
| **HR Admin** | Toàn quyền: tạo, sửa, publish, đóng, xem analytics tất cả tin |
| **Hiring Manager** | Tạo tin cho vị trí mình phụ trách, gửi HR duyệt |
| **Content Reviewer** | Duyệt nội dung tin trước khi publish (optional) |

---

## 2. Phạm vi

### Trong phạm vi

- Soạn thảo tin tuyển dụng đầy đủ thông tin (tham chiếu Schema.org JobPosting + Internshala)
- Cấu hình câu hỏi sàng lọc tùy chỉnh (screening questions) per tin
- Quản lý skills yêu cầu (structured, không phải plain text)
- Phúc lợi (benefits) có danh mục chuẩn + tùy chỉnh
- Vòng đời tin: draft → review → published → paused → closed → archived
- Phân phối: publish ra Marketplace, generate public URL cho career page
- Analytics: view, apply, source breakdown per tin
- Lịch sử thay đổi (audit trail) nội dung tin

### Ngoài phạm vi

- Pipeline tuyển dụng (phỏng vấn, offer) — thuộc Recruitment Center
- Hiển thị công khai cho ứng viên bên ngoài — thuộc Marketplace Center
- Tích hợp ATS bên thứ ba

---

## 3. Kiến trúc tổng thể

```
┌───────────────────────────────────────────────────────────┐
│                   JOB POSTING CENTER                      │
│                                                           │
│  JP_JOB_POST ──────────────────────────────────────────┐  │
│      │                                                  │  │
│      ├─1:N──► JP_JOB_POST_SKILL      (kỹ năng yêu cầu)│  │
│      ├─1:N──► JP_JOB_POST_BENEFIT    (phúc lợi)        │  │
│      ├─1:N──► JP_SCREENING_QUESTION  (câu hỏi sàng lọc)│  │
│      ├─1:N──► JP_JOB_POST_HISTORY    (audit trail)     │  │
│      └─1:N──► JP_JOB_POST_STAT       (analytics daily) │  │
│                                                           │
│  JP_SKILL_MASTER  (danh mục kỹ năng chuẩn của org)      │
│  JP_BENEFIT_MASTER (danh mục phúc lợi chuẩn của org)    │
└───────────────────────────────────────────────────────────┘

Downstream (publish ra ngoài):
  JP_JOB_POST.uuid → mkt_listings.jp_job_post_id   (Marketplace, CHAR(36) soft ref)
  JP_JOB_POST.uuid → rc_applications.jp_job_post_id (Recruitment, CHAR(36) soft ref)
  JP_JOB_POST → public career page API
```

---

## 4. Enum Values

### JP_JOB_POST

| Trường | Giá trị | Mô tả |
|---|---|---|
| `status` | `draft` | Đang soạn thảo |
| | `pending_review` | Đã gửi, chờ HR/reviewer duyệt |
| | `published` | Đang hiển thị, nhận ứng viên |
| | `paused` | Tạm dừng (ẩn khỏi kênh, giữ ứng viên cũ) |
| | `closed` | Đã đóng tuyển, không nhận thêm |
| | `archived` | Lưu trữ — tham khảo, không active |
| | `cancelled` | Hủy hoàn toàn |
| `employment_type` | `full_time` | Toàn thời gian |
| | `part_time` | Bán thời gian |
| | `contract` | Hợp đồng có thời hạn |
| | `freelance` | Tự do / dự án |
| | `internship` | Thực tập |
| | `temporary` | Tạm thời theo mùa |
| `work_arrangement` | `onsite` | Làm việc tại văn phòng |
| | `remote` | Làm việc từ xa hoàn toàn |
| | `hybrid` | Kết hợp |
| | `flexible` | Linh hoạt |
| `experience_level` | `no_experience` | Không cần kinh nghiệm |
| | `entry` | Dưới 1 năm |
| | `junior` | 1–3 năm |
| | `mid` | 3–5 năm |
| | `senior` | 5–8 năm |
| | `lead` | 8+ năm / cấp quản lý |
| | `executive` | C-level / giám đốc |
| `salary_type` | `monthly` | Lương tháng |
| | `yearly` | Lương năm |
| | `hourly` | Lương giờ |
| | `project` | Theo dự án |
| `industry` | `technology` \| `finance` \| `healthcare` \| `education` \| `retail` \| `manufacturing` \| `marketing` \| `hr` \| `legal` \| `construction` \| `hospitality` \| `logistics` \| `other` | |
| `visibility` | `public` | Hiển thị công khai |
| | `unlisted` | Có link mới vào được |
| | `internal` | Chỉ trong hệ thống nội bộ |

### JP_SCREENING_QUESTION

| Trường | Giá trị | Mô tả |
|---|---|---|
| `question_type` | `yes_no` | Câu hỏi Có/Không |
| | `short_text` | Trả lời ngắn (< 500 ký tự) |
| | `long_text` | Trả lời dài (> 500 ký tự) |
| | `number` | Nhập số (vd: bao nhiêu năm kinh nghiệm?) |
| | `single_choice` | Chọn 1 trong nhiều |
| | `multiple_choice` | Chọn nhiều |
| | `file_upload` | Upload file (CV, portfolio) |

### JP_JOB_POST_SKILL

| Trường | Giá trị |
|---|---|
| `requirement_level` | `required` \| `preferred` \| `nice_to_have` |
| `proficiency` | `beginner` \| `intermediate` \| `advanced` \| `expert` |

### JP_JOB_POST_HISTORY

| Trường | Giá trị |
|---|---|
| `change_type` | `created` \| `updated` \| `status_changed` \| `published` \| `closed` \| `archived` |

---

## 5. ERD — Quan hệ bảng

```
[organizations] (existing)
[departments]   (existing)
[job_titles]    (existing)
[users]         (existing)
       │
       ▼
JP_JOB_POST (core)
    │
    ├─1:N──► JP_JOB_POST_SKILL     ◄──N:1── JP_SKILL_MASTER
    │
    ├─1:N──► JP_JOB_POST_BENEFIT   ◄──N:1── JP_BENEFIT_MASTER
    │
    ├─1:N──► JP_SCREENING_QUESTION
    │             └─1:N──► JP_SCREENING_CHOICE (nếu type = single/multiple_choice)
    │
    ├─1:N──► JP_JOB_POST_HISTORY   (immutable audit trail)
    │
    └─1:N──► JP_JOB_POST_STAT      (daily analytics grain)

Cross-module refs (soft ref — không FK cứng):
  mkt_listings.jp_job_post_id     → JP_JOB_POST.uuid  (CHAR(36))
  rc_applications.jp_job_post_id  → JP_JOB_POST.uuid  (CHAR(36))
```

### Quan hệ tổng hợp

| Bảng A | Quan hệ | Bảng B | Ghi chú |
|---|---|---|---|
| JP_JOB_POST | N:1 | organizations | BIGINT FK |
| JP_JOB_POST | N:1 | departments | BIGINT FK — nullable |
| JP_JOB_POST | N:1 | job_titles | BIGINT FK — nullable |
| JP_JOB_POST | 1:N | JP_JOB_POST_SKILL | Kỹ năng yêu cầu |
| JP_JOB_POST | 1:N | JP_JOB_POST_BENEFIT | Phúc lợi cụ thể |
| JP_JOB_POST | 1:N | JP_SCREENING_QUESTION | Câu hỏi sàng lọc |
| JP_JOB_POST | 1:N | JP_JOB_POST_HISTORY | Audit trail (immutable) |
| JP_JOB_POST | 1:N | JP_JOB_POST_STAT | Analytics theo ngày |
| JP_SKILL_MASTER | 1:N | JP_JOB_POST_SKILL | Skill dùng trong tin |
| JP_BENEFIT_MASTER | 1:N | JP_JOB_POST_BENEFIT | Benefit dùng trong tin |
| JP_SCREENING_QUESTION | 1:N | JP_SCREENING_CHOICE | Options của câu hỏi |

---

## 6. Đặc tả bảng dữ liệu

> **Quy ước FK:**
> - Bảng hệ thống sẵn có (`organizations`, `users`, `departments`, `job_titles`): `UNSIGNED BIGINT`
> - Nội bộ `jp_*` ↔ `jp_*`: `UNSIGNED BIGINT` (FK → `id` cột BIGINT PK)
> - Cross-module ref lỏng: `CHAR(36)` tham chiếu `.uuid` của bảng đích, không FK constraint
>
> **Quy ước PK:** Mọi bảng đều có:
> ```php
> $table->id();                                          // BIGINT PK
> $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
> ```

---

### 6.1 JP_JOB_POST — Tin tuyển dụng (core)

Bảng trung tâm. Lưu toàn bộ thông tin tin tuyển dụng theo chuẩn Schema.org JobPosting, tham chiếu Internshala và Google Jobs.

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID — expose ra ngoài |
| `org_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → organizations.id |
| `department_id` | UNSIGNED BIGINT | NULL | FK | NULL | FK → departments.id ON DELETE SET NULL |
| `job_title_id` | UNSIGNED BIGINT | NULL | FK | NULL | FK → job_titles.id ON DELETE SET NULL |
| `created_by` | UNSIGNED BIGINT | NOT NULL | FK | | FK → users.id — người tạo tin |
| `owner_id` | UNSIGNED BIGINT | NOT NULL | FK | | FK → users.id — Hiring Manager chịu trách nhiệm |
| `reviewed_by` | UNSIGNED BIGINT | NULL | FK | NULL | FK → users.id — HR/reviewer duyệt |
| **— THÔNG TIN CƠ BẢN —** | | | | | |
| `title` | VARCHAR(200) | NOT NULL | | | Tên vị trí tuyển dụng — ngắn gọn, rõ ràng |
| `code` | VARCHAR(30) | NOT NULL | UNIQUE(org_id) | | JP-2024-001 — tự sinh |
| `slug` | VARCHAR(250) | NOT NULL | UNIQUE(org_id) | | URL-friendly, unique trong org |
| `status` | ENUM | NOT NULL | INDEX | `draft` | Xem mục 4 |
| `visibility` | ENUM | NOT NULL | | `public` | public \| unlisted \| internal |
| **— PHÂN LOẠI —** | | | | | |
| `employment_type` | ENUM | NOT NULL | INDEX | `full_time` | Xem mục 4 |
| `work_arrangement` | ENUM | NOT NULL | INDEX | `onsite` | Xem mục 4 |
| `experience_level` | ENUM | NOT NULL | INDEX | `junior` | Xem mục 4 |
| `industry` | ENUM | NOT NULL | INDEX | `other` | Xem mục 4 |
| `headcount` | SMALLINT | NOT NULL | | 1 | Số lượng cần tuyển |
| `hired_count` | SMALLINT | NOT NULL | | 0 | Đã tuyển (denormalized, tăng khi RC offer accepted) |
| **— ĐỊA ĐIỂM —** | | | | | |
| `city` | VARCHAR(100) | NULL | | NULL | Thành phố làm việc |
| `province` | VARCHAR(100) | NULL | | NULL | Tỉnh / bang |
| `country` | CHAR(2) | NOT NULL | | `VN` | ISO 3166-1 alpha-2 |
| `address_detail` | VARCHAR(300) | NULL | | NULL | Địa chỉ chi tiết (số nhà, đường...) |
| `is_remote_allowed` | BOOLEAN | NOT NULL | | FALSE | Cho phép làm remote không? |
| `remote_countries` | TEXT | NULL | | NULL | Danh sách quốc gia cho phép remote — phân tách phẩy (ISO codes) |
| **— NỘI DUNG TIN —** | | | | | |
| `summary` | VARCHAR(500) | NULL | | NULL | Mô tả ngắn — hiển thị trên listing card |
| `description` | TEXT | NOT NULL | | | Mô tả công việc đầy đủ (Markdown/HTML) |
| `responsibilities` | TEXT | NULL | | NULL | Trách nhiệm / nhiệm vụ chính |
| `requirements` | TEXT | NOT NULL | | | Yêu cầu ứng viên (kỹ năng, kinh nghiệm, học vấn) |
| `nice_to_have` | TEXT | NULL | | NULL | Yêu cầu phụ — có thì tốt |
| `what_you_will_learn` | TEXT | NULL | | NULL | Bạn sẽ học được gì (quan trọng với intern/entry) |
| `about_company` | TEXT | NULL | | NULL | Giới thiệu công ty — override mặc định từ org profile |
| **— HỌC VẤN & KINH NGHIỆM —** | | | | | |
| `min_experience_years` | SMALLINT | NULL | | NULL | Số năm kinh nghiệm tối thiểu |
| `max_experience_years` | SMALLINT | NULL | | NULL | Số năm kinh nghiệm tối đa (NULL = không giới hạn) |
| `education_level` | ENUM | NULL | | NULL | none \| high_school \| associate \| bachelor \| master \| phd \| any |
| `education_field` | VARCHAR(200) | NULL | | NULL | Ngành học yêu cầu, vd: "Computer Science, IT" |
| `certifications_required` | TEXT | NULL | | NULL | Chứng chỉ bắt buộc |
| **— LƯƠNG & PHÚC LỢI —** | | | | | |
| `salary_type` | ENUM | NOT NULL | | `monthly` | Xem mục 4 |
| `salary_min` | DECIMAL(15,2) | NULL | | NULL | Mức lương tối thiểu |
| `salary_max` | DECIMAL(15,2) | NULL | | NULL | Mức lương tối đa |
| `salary_currency` | CHAR(3) | NOT NULL | | `VND` | ISO 4217 |
| `salary_is_negotiable` | BOOLEAN | NOT NULL | | FALSE | |
| `salary_is_visible` | BOOLEAN | NOT NULL | | TRUE | FALSE = hiển thị "Thỏa thuận" |
| `salary_note` | VARCHAR(300) | NULL | | NULL | Ghi chú thêm về lương, vd: "4–5.5 LPA fixed + 3 LPA variable" |
| `probation_duration_days` | SMALLINT | NULL | | NULL | Thời gian thử việc (ngày) |
| `probation_salary_pct` | SMALLINT | NULL | | NULL | % lương trong thời gian thử việc (vd: 85 = 85%) |
| **— THỜI HẠN —** | | | | | |
| `published_at` | TIMESTAMP | NULL | INDEX | NULL | Thời điểm publish |
| `expire_at` | TIMESTAMP | NULL | INDEX | NULL | Hạn nộp hồ sơ |
| `closed_at` | TIMESTAMP | NULL | | NULL | Thời điểm đóng |
| **— CẤU HÌNH ỨNG TUYỂN —** | | | | | |
| `application_email` | VARCHAR(150) | NULL | | NULL | Email nhận CV nếu apply ngoài hệ thống |
| `application_url` | TEXT | NULL | | NULL | URL ứng tuyển nếu redirect sang form ngoài |
| `allow_direct_apply` | BOOLEAN | NOT NULL | | TRUE | Cho phép apply trực tiếp qua hệ thống |
| `require_cover_letter` | BOOLEAN | NOT NULL | | FALSE | Bắt buộc nộp cover letter |
| `require_portfolio` | BOOLEAN | NOT NULL | | FALSE | Bắt buộc nộp portfolio |
| **— PHÂN PHỐI KÊNH —** | | | | | |
| `publish_to_marketplace` | BOOLEAN | NOT NULL | | FALSE | Đồng bộ ra MKT_LISTING |
| `publish_to_career_page` | BOOLEAN | NOT NULL | | TRUE | Hiển thị trên career page riêng của org |
| `mkt_listing_id` | CHAR(36) | NULL | INDEX | NULL | Soft ref → mkt_listings.uuid (không FK cứng) |
| `mkt_sync_status` | ENUM | NULL | | NULL | synced \| out_of_sync — NULL khi không publish MKT |
| **— ANALYTICS (denormalized) —** | | | | | |
| `view_count` | INT | NOT NULL | | 0 | |
| `application_count` | INT | NOT NULL | | 0 | |
| `share_count` | INT | NOT NULL | | 0 | |
| **— METADATA —** | | | | | |
| `tags` | VARCHAR(500) | NULL | | NULL | Tags phân tách phẩy — plain text, dùng cho SEO |
| `seo_title` | VARCHAR(200) | NULL | | NULL | Override title cho SEO |
| `seo_description` | VARCHAR(300) | NULL | | NULL | Meta description |
| `updated_by` | UNSIGNED BIGINT | NULL | FK | NULL | FK → users.id |
| `created_at` | TIMESTAMP | NOT NULL | | NOW() | |
| `updated_at` | TIMESTAMP | NOT NULL | | NOW() | |

```sql
CREATE UNIQUE INDEX idx_jp_post_code      ON jp_job_posts(org_id, code);
CREATE UNIQUE INDEX idx_jp_post_slug      ON jp_job_posts(org_id, slug);
CREATE INDEX idx_jp_post_status           ON jp_job_posts(org_id, status, published_at DESC);
CREATE INDEX idx_jp_post_dept             ON jp_job_posts(department_id, status);
CREATE INDEX idx_jp_post_owner            ON jp_job_posts(owner_id, status);
CREATE INDEX idx_jp_post_expire           ON jp_job_posts(expire_at, status)
  WHERE expire_at IS NOT NULL AND status = 'published';
CREATE INDEX idx_jp_post_type             ON jp_job_posts(employment_type, work_arrangement, experience_level, status);
CREATE INDEX idx_jp_post_location         ON jp_job_posts(country, province, city, status);
CREATE INDEX idx_jp_post_mkt              ON jp_job_posts(mkt_listing_id)
  WHERE mkt_listing_id IS NOT NULL;
CREATE FULLTEXT INDEX idx_jp_post_fts     ON jp_job_posts(title, summary, description, responsibilities, requirements);
```

---

### 6.2 JP_SKILL_MASTER — Danh mục kỹ năng chuẩn

Danh mục skills tập trung của org. Dùng cho cả Job Posting và Workforce profile để thống nhất tên gọi.

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `org_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → organizations.id; NULL = skill hệ thống toàn cục |
| `name` | VARCHAR(100) | NOT NULL | | | "Laravel", "React", "Kế toán quản trị" |
| `slug` | VARCHAR(110) | NOT NULL | UNIQUE(org_id) | | |
| `category` | VARCHAR(80) | NULL | | NULL | "Backend", "Frontend", "Soft Skills" |
| `is_active` | BOOLEAN | NOT NULL | | TRUE | |

```sql
CREATE UNIQUE INDEX idx_jp_skill_slug ON jp_skill_masters(org_id, slug);
CREATE INDEX idx_jp_skill_cat         ON jp_skill_masters(org_id, category, is_active);
CREATE FULLTEXT INDEX idx_jp_skill_fts ON jp_skill_masters(name, category);
```

---

### 6.3 JP_JOB_POST_SKILL — Kỹ năng yêu cầu của tin

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `job_post_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → jp_job_posts.id CASCADE DELETE |
| `skill_id` | UNSIGNED BIGINT | NULL | FK | NULL | FK → jp_skill_masters.id — NULL nếu skill tự nhập |
| `skill_name` | VARCHAR(100) | NOT NULL | | | Denormalized — hiển thị khi skill_id bị xóa |
| `requirement_level` | ENUM | NOT NULL | | `required` | required \| preferred \| nice_to_have |
| `proficiency` | ENUM | NULL | | NULL | beginner \| intermediate \| advanced \| expert |
| `min_years` | SMALLINT | NULL | | NULL | Số năm tối thiểu với kỹ năng này |
| `sort_order` | SMALLINT | NOT NULL | | 0 | |

```sql
CREATE INDEX idx_jp_skill_post ON jp_job_post_skills(job_post_id, requirement_level);
CREATE INDEX idx_jp_skill_ref  ON jp_job_post_skills(skill_id)
  WHERE skill_id IS NOT NULL;
CREATE INDEX idx_jp_skill_name ON jp_job_post_skills(skill_name, requirement_level);
```

---

### 6.4 JP_BENEFIT_MASTER — Danh mục phúc lợi chuẩn

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `org_id` | UNSIGNED BIGINT | NULL | FK | NULL | FK → organizations.id; NULL = benefit hệ thống toàn cục |
| `name` | VARCHAR(150) | NOT NULL | | | "Bảo hiểm sức khỏe cao cấp", "Thưởng KPI" |
| `icon` | VARCHAR(80) | NULL | | NULL | Tên Tabler icon: ti-heart, ti-building |
| `category` | ENUM | NOT NULL | | `other` | health \| finance \| learning \| work_life \| equipment \| other |
| `is_active` | BOOLEAN | NOT NULL | | TRUE | |

---

### 6.5 JP_JOB_POST_BENEFIT — Phúc lợi của tin

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `job_post_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → jp_job_posts.id CASCADE DELETE |
| `benefit_id` | UNSIGNED BIGINT | NULL | FK | NULL | FK → jp_benefit_masters.id — NULL nếu tự nhập |
| `benefit_name` | VARCHAR(150) | NOT NULL | | | Denormalized |
| `description` | VARCHAR(300) | NULL | | NULL | Mô tả thêm, vd: "Bảo hiểm Bảo Việt toàn diện" |
| `sort_order` | SMALLINT | NOT NULL | | 0 | |

```sql
CREATE INDEX idx_jp_benefit_post ON jp_job_post_benefits(job_post_id);
```

---

### 6.6 JP_SCREENING_QUESTION — Câu hỏi sàng lọc

Câu hỏi tùy chỉnh hiển thị cho ứng viên khi apply. Câu trả lời lưu trên `rc_applications` hoặc `mkt_applications`.

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID — downstream modules dùng uuid này làm soft ref |
| `job_post_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → jp_job_posts.id CASCADE DELETE |
| `question_text` | VARCHAR(500) | NOT NULL | | | Nội dung câu hỏi |
| `question_type` | ENUM | NOT NULL | | `yes_no` | Xem mục 4 |
| `is_required` | BOOLEAN | NOT NULL | | TRUE | Bắt buộc trả lời |
| `is_disqualifying` | BOOLEAN | NOT NULL | | FALSE | TRUE = câu trả lời "Không" → tự động disqualify |
| `disqualify_if_answer` | VARCHAR(100) | NULL | | NULL | Giá trị trả lời gây disqualify (vd: "no", "false") |
| `placeholder` | VARCHAR(200) | NULL | | NULL | Placeholder text cho input |
| `max_length` | INT | NULL | | NULL | Giới hạn ký tự (cho short_text/long_text) |
| `sort_order` | SMALLINT | NOT NULL | | 0 | |

```sql
CREATE INDEX idx_jp_sq_post ON jp_screening_questions(job_post_id, sort_order);
```

---

### 6.7 JP_SCREENING_CHOICE — Lựa chọn câu hỏi

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `question_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → jp_screening_questions.id CASCADE DELETE |
| `choice_text` | VARCHAR(200) | NOT NULL | | | |
| `is_disqualifying` | BOOLEAN | NOT NULL | | FALSE | Chọn đáp án này → disqualify |
| `sort_order` | SMALLINT | NOT NULL | | 0 | |

---

### 6.8 JP_JOB_POST_HISTORY — Lịch sử thay đổi (immutable)

Audit trail bất biến — chỉ INSERT, không UPDATE, không DELETE.

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `job_post_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → jp_job_posts.id |
| `change_type` | ENUM | NOT NULL | | | created \| updated \| status_changed \| published \| closed \| archived |
| `old_status` | ENUM | NULL | | NULL | Status trước khi thay đổi |
| `new_status` | ENUM | NULL | | NULL | Status sau khi thay đổi |
| `changed_fields` | TEXT | NULL | | NULL | Danh sách trường thay đổi, phân tách phẩy (không phải JSON) |
| `note` | TEXT | NULL | | NULL | Ghi chú khi thay đổi |
| `changed_by` | UNSIGNED BIGINT | NOT NULL | FK | | FK → users.id |
| `created_at` | TIMESTAMP | NOT NULL | INDEX | NOW() | |

```sql
CREATE INDEX idx_jp_hist_post ON jp_job_post_histories(job_post_id, created_at DESC);
```

---

### 6.9 JP_JOB_POST_STAT — Analytics theo ngày

Grain: 1 row = 1 ngày × 1 tin. Tổng hợp từ view/apply events.

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `job_post_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → jp_job_posts.id |
| `stat_date` | DATE | NOT NULL | INDEX | | Ngày thống kê |
| `source` | VARCHAR(30) | NOT NULL | | `direct` | Kênh: direct \| marketplace \| career_page \| linkedin \| referral \| other |
| `view_count` | INT | NOT NULL | | 0 | Lượt xem trong ngày |
| `unique_view_count` | INT | NOT NULL | | 0 | Lượt xem duy nhất (dedup bằng session) |
| `apply_count` | INT | NOT NULL | | 0 | Lượt apply trong ngày |
| `share_count` | INT | NOT NULL | | 0 | Lượt chia sẻ |
| `bookmark_count` | INT | NOT NULL | | 0 | Lượt bookmark |

```sql
CREATE UNIQUE INDEX idx_jp_stat_grain ON jp_job_post_stats(job_post_id, stat_date, source);
CREATE INDEX idx_jp_stat_date         ON jp_job_post_stats(stat_date, job_post_id);
```

---

## 7. Luồng nghiệp vụ

### 7.1 Tạo tin tuyển dụng

```
Hiring Manager / HR tạo JP_JOB_POST (status='draft')
  │
  ├─ Điền thông tin cơ bản: title, department, job_title, employment_type
  ├─ Soạn nội dung: description, responsibilities, requirements, nice_to_have
  ├─ Cấu hình lương: salary_type, min/max, visibility
  ├─ Cấu hình địa điểm: city, country, work_arrangement
  ├─ Cấu hình học vấn & kinh nghiệm: education_level, min/max years
  ├─ Thêm skills: INSERT JP_JOB_POST_SKILL (required / preferred / nice_to_have)
  ├─ Thêm benefits: INSERT JP_JOB_POST_BENEFIT
  ├─ Thêm screening questions (optional): INSERT JP_SCREENING_QUESTION
  └─ Lưu nháp

Gửi duyệt (nếu có luồng review):
  UPDATE status = 'pending_review'
  INSERT JP_JOB_POST_HISTORY (change_type='status_changed')
  Notify reviewer

HR / Reviewer duyệt:
  UPDATE status = 'published', published_at = NOW()
  INSERT JP_JOB_POST_HISTORY (change_type='published')
  Trigger: sync ra Marketplace nếu publish_to_marketplace=TRUE
```

### 7.2 Publish ra Marketplace

```
Khi JP_JOB_POST.publish_to_marketplace = TRUE và status → 'published':
  JpJobPostObserver::updated()
    → INSERT mkt_listings:
        jp_job_post_id   = job_post.uuid  (CHAR(36) — soft ref)
        org_id           = job_post.org_id
        poster_type      = 'org'
        listing_type     = 'job'
        title            = job_post.title
        description      = job_post.description
        requirements     = job_post.requirements
        salary_min/max   = job_post.salary_min/max
        employment_type  = job_post.employment_type
        work_type        = job_post.work_arrangement
        experience_level = job_post.experience_level
        location         = job_post.city + province
        headcount        = job_post.headcount
        expire_at        = job_post.expire_at
        status           = 'active'
    → UPDATE jp_job_posts SET mkt_listing_id = mkt_listing.uuid, mkt_sync_status = 'synced'

Khi tin thay đổi sau khi đã publish:
  JpJobPostObserver::updated()
    → UPDATE jp_job_posts SET mkt_sync_status = 'out_of_sync'
  HR click "Sync lại":
    → Overwrite mkt_listing từ jp_job_post data
    → UPDATE mkt_sync_status = 'synced'

Khi tin đóng (status → 'closed'/'archived'):
  JpJobPostObserver::updated()
    → UPDATE mkt_listings SET status='closed', closed_at=NOW()
       WHERE jp_job_post_id = job_post.uuid
```

### 7.3 Ứng viên từ Marketplace vào Recruitment

```
MKT_APPLICATION nộp qua Marketplace
  │ (listing có jp_job_post_id = jp_job_posts.uuid)
  ▼
HR import vào Recruitment:
  INSERT rc_candidates (từ mkt_applicant data)
  INSERT rc_applications:
    jp_job_post_id      = mkt_listing.jp_job_post_id  (CHAR(36), soft ref)
    apply_source        = 'marketplace'
    mkt_application_id  = mkt_application.uuid

Ứng viên apply trực tiếp qua career page:
  INSERT rc_applications:
    jp_job_post_id  = job_post.uuid  (CHAR(36), soft ref)
    apply_source    = 'career_page'

UPDATE JP_JOB_POST.application_count += 1 (async)
```

### 7.4 Vòng đời và đóng tin

```
Cron job hàng ngày:
  SELECT * FROM jp_job_posts
  WHERE expire_at < NOW() AND status = 'published'
  → UPDATE status = 'closed', closed_at = NOW()
  → INSERT JP_JOB_POST_HISTORY (change_type='closed', note='Auto-closed: expired')
  → UPDATE mkt_listings SET status='closed' (nếu có)

Khi hired_count >= headcount:
  UPDATE status = 'closed'
  INSERT JP_JOB_POST_HISTORY (change_type='closed', note='Auto-closed: headcount fulfilled')
```

---

## 8. Query Patterns

### 8.1 Danh sách tin đang mở của org

```sql
SELECT
    jp.id, jp.uuid, jp.code, jp.title, jp.status,
    jp.employment_type, jp.work_arrangement,
    jp.city, jp.country, jp.experience_level,
    jp.salary_min, jp.salary_max, jp.salary_is_visible,
    jp.headcount, jp.hired_count,
    jp.application_count, jp.view_count,
    jp.published_at, jp.expire_at,
    jp.mkt_sync_status,
    d.name  AS department_name,
    jt.name AS position_name,
    u.name AS owner_name
FROM jp_job_posts jp
LEFT JOIN departments d ON d.id = jp.department_id
LEFT JOIN job_titles jt  ON jt.id = jp.job_title_id
JOIN users u             ON u.id  = jp.owner_id
WHERE jp.org_id = :org_id
  AND jp.status = :status
ORDER BY jp.published_at DESC;
```

### 8.2 Chi tiết tin với skills và benefits

```sql
-- Tin chính
SELECT jp.* FROM jp_job_posts jp WHERE jp.id = :id;

-- Skills (tách query để tránh cartesian)
SELECT jps.*, sm.category AS skill_category
FROM jp_job_post_skills jps
LEFT JOIN jp_skill_masters sm ON sm.id = jps.skill_id
WHERE jps.job_post_id = :id
ORDER BY jps.requirement_level, jps.sort_order;

-- Benefits
SELECT jpb.*, bm.icon, bm.category AS benefit_category
FROM jp_job_post_benefits jpb
LEFT JOIN jp_benefit_masters bm ON bm.id = jpb.benefit_id
WHERE jpb.job_post_id = :id
ORDER BY jpb.sort_order;

-- Screening questions
SELECT sq.id, sq.uuid, sq.question_text, sq.question_type, sq.is_required,
       sq.is_disqualifying, sq.sort_order,
       GROUP_CONCAT(sc.choice_text ORDER BY sc.sort_order SEPARATOR '|||') AS choices
FROM jp_screening_questions sq
LEFT JOIN jp_screening_choices sc ON sc.question_id = sq.id
WHERE sq.job_post_id = :id
GROUP BY sq.id
ORDER BY sq.sort_order;
```

### 8.3 Analytics per tin — 30 ngày gần nhất

```sql
SELECT
    stat_date,
    source,
    SUM(view_count)        AS views,
    SUM(unique_view_count) AS unique_views,
    SUM(apply_count)       AS applies,
    ROUND(SUM(apply_count) * 100.0 / NULLIF(SUM(view_count), 0), 1) AS conversion_pct
FROM jp_job_post_stats
WHERE job_post_id = :id
  AND stat_date >= CURRENT_DATE - INTERVAL 30 DAY
GROUP BY stat_date, source
ORDER BY stat_date DESC;
```

### 8.4 Tìm kiếm tin theo skill (cho applicant trên career page)

```sql
SELECT DISTINCT jp.id, jp.uuid, jp.title, jp.city, jp.employment_type, jp.salary_min, jp.salary_max
FROM jp_job_posts jp
JOIN jp_job_post_skills jps ON jps.job_post_id = jp.id
WHERE jp.org_id    = :org_id
  AND jp.status    = 'published'
  AND jps.skill_name IN (:skill1, :skill2)
ORDER BY jp.published_at DESC;
```

### 8.5 Dashboard tổng hợp org

```sql
SELECT
    COUNT(*) FILTER (WHERE status = 'published')        AS active_posts,
    COUNT(*) FILTER (WHERE status = 'draft')            AS drafts,
    COUNT(*) FILTER (WHERE status = 'closed'
        AND closed_at >= DATE_TRUNC('month', NOW()))    AS closed_this_month,
    SUM(application_count) FILTER (WHERE status = 'published') AS total_applications,
    SUM(view_count) FILTER (WHERE status = 'published')         AS total_views,
    COUNT(*) FILTER (WHERE mkt_sync_status = 'out_of_sync')     AS out_of_sync_count,
    COUNT(*) FILTER (WHERE expire_at < NOW() + INTERVAL '7 days'
                    AND status = 'published')                    AS expiring_soon
FROM jp_job_posts
WHERE org_id = :org_id;
```

---

## 9. API Endpoints

### Job Posts (HR/Manager — auth web)

| Method | Endpoint | Mô tả |
|---|---|---|
| GET | `/api/job-posts` | Danh sách (filter: status, dept, type) |
| GET | `/api/job-posts/:id` | Chi tiết đầy đủ |
| GET | `/api/job-posts/:id/history` | Audit trail |
| GET | `/api/job-posts/:id/analytics` | Thống kê view/apply |
| POST | `/api/job-posts` | Tạo tin mới |
| PUT | `/api/job-posts/:id` | Cập nhật (chỉ khi draft/paused) |
| POST | `/api/job-posts/:id/submit-review` | Gửi duyệt |
| POST | `/api/job-posts/:id/publish` | Publish (HR Admin) |
| POST | `/api/job-posts/:id/pause` | Tạm dừng |
| POST | `/api/job-posts/:id/close` | Đóng tuyển |
| POST | `/api/job-posts/:id/archive` | Lưu trữ |
| POST | `/api/job-posts/:id/duplicate` | Nhân bản tin |
| POST | `/api/job-posts/:id/sync-marketplace` | Re-sync ra Marketplace |

### Skills & Benefits Master

| Method | Endpoint | Mô tả |
|---|---|---|
| GET | `/api/job-posts/skills` | Danh sách skill master (dùng cho autocomplete) |
| POST | `/api/job-posts/skills` | Thêm skill vào master |
| GET | `/api/job-posts/benefits` | Danh sách benefit master |
| POST | `/api/job-posts/benefits` | Thêm benefit vào master |

### Job Post Skills & Benefits

| Method | Endpoint | Mô tả |
|---|---|---|
| POST | `/api/job-posts/:id/skills` | Thêm skill vào tin |
| PUT | `/api/job-posts/:id/skills/:sid` | Cập nhật skill |
| DELETE | `/api/job-posts/:id/skills/:sid` | Xóa skill |
| POST | `/api/job-posts/:id/benefits` | Thêm benefit vào tin |
| DELETE | `/api/job-posts/:id/benefits/:bid` | Xóa benefit |

### Screening Questions

| Method | Endpoint | Mô tả |
|---|---|---|
| GET | `/api/job-posts/:id/questions` | Danh sách câu hỏi |
| POST | `/api/job-posts/:id/questions` | Thêm câu hỏi |
| PUT | `/api/job-posts/:id/questions/:qid` | Cập nhật |
| DELETE | `/api/job-posts/:id/questions/:qid` | Xóa |
| PUT | `/api/job-posts/:id/questions/reorder` | Sắp xếp lại |

### Public Career Page API (không cần auth)

| Method | Endpoint | Mô tả |
|---|---|---|
| GET | `/api/careers/:org_slug/jobs` | Danh sách tin đang mở của org |
| GET | `/api/careers/:org_slug/jobs/:slug` | Chi tiết tin |
| GET | `/api/careers/:org_slug/jobs/:slug/questions` | Câu hỏi sàng lọc |
| POST | `/api/careers/:org_slug/jobs/:slug/apply` | Nộp đơn qua career page |

---

## 10. Business Rules

### BR-JP-001: Vòng đời tin

- `draft → pending_review`: bắt buộc có `title`, `description`, `requirements`, `employment_type`, `headcount >= 1`
- `pending_review → published`: chỉ HR Admin hoặc reviewer được duyệt — Hiring Manager không tự publish
- Tin `published` chỉ sửa được khi có quyền HR Admin — thay đổi nội dung → `mkt_sync_status = 'out_of_sync'`
- `published → paused`: tin ẩn khỏi tất cả kênh, ứng viên đã apply vẫn giữ nguyên
- `closed`/`archived`: không nhận thêm application, không rollback

### BR-JP-002: Headcount & Auto-close

- Khi `hired_count >= headcount` → tự động `status = 'closed'` (trigger từ Recruitment Center khi offer accepted)
- Khi `expire_at < NOW()` → cron tự động `status = 'closed'`
- `hired_count` chỉ tăng khi `rc_offers.status = 'accepted'` — không tin client

### BR-JP-003: Skills

- `skill_name` là REQUIRED (luôn có giá trị) — `skill_id` là nullable (liên kết master nếu có)
- Khi thêm skill mới không có trong master: tự động INSERT JP_SKILL_MASTER (hoặc chỉ lưu tên tự do — tùy config)
- Tổng skills per tin: tối đa 20 (10 required + 10 preferred/nice_to_have)

### BR-JP-004: Screening Questions

- Tối đa 10 câu hỏi per tin
- `is_disqualifying = TRUE`: hệ thống tự động đánh dấu application là `disqualified` khi ứng viên trả lời sai — không hard reject ngay, vẫn để HR xem xét
- Câu hỏi `file_upload` count vào quota upload của org

### BR-JP-005: Marketplace sync

- Chỉ sync khi `publish_to_marketplace = TRUE` VÀ `status = 'published'`
- 1 tin (jp_job_post.uuid) chỉ có 1 mkt_listing active tại 1 thời điểm
- Re-sync chỉ overwrite nội dung — không reset `application_count`, `view_count` của listing
- Nếu mkt_listing bị xóa: `mkt_listing_id = NULL`, `mkt_sync_status = NULL` — tin vẫn tồn tại

### BR-JP-006: Duplicate

- Nhân bản tin: copy toàn bộ skills, benefits, screening questions — status = `draft`, published_at = NULL
- Code và slug tự sinh mới (không copy)

---

## 11. Indexes & Caching

```sql
-- Dashboard HR: tin đang active
CREATE INDEX idx_jp_dashboard
  ON jp_job_posts(org_id, status, published_at DESC)
  INCLUDE (title, employment_type, application_count, headcount, hired_count);

-- Public career page browse
CREATE INDEX idx_jp_public
  ON jp_job_posts(org_id, status, visibility, published_at DESC)
  WHERE status = 'published' AND visibility = 'public';

-- Expire cron
CREATE INDEX idx_jp_expire_cron
  ON jp_job_posts(expire_at, status)
  WHERE status = 'published' AND expire_at IS NOT NULL;

-- Out-of-sync badge
CREATE INDEX idx_jp_outofsync
  ON jp_job_posts(org_id, mkt_sync_status)
  WHERE mkt_sync_status = 'out_of_sync';

-- Analytics aggregation
CREATE INDEX idx_jp_stat_agg
  ON jp_job_post_stats(job_post_id, stat_date DESC)
  INCLUDE (view_count, apply_count, source);
```

### Caching

| Cache key | TTL | Invalidate khi |
|---|---|---|
| `jp:org:{id}:dashboard` | 5 phút | Tin mới publish, close, apply |
| `jp:post:{id}:detail` | 10 phút | Cập nhật tin |
| `jp:post:{id}:questions` | 30 phút | Thêm/sửa câu hỏi |
| `jp:org:{id}:skills-master` | 60 phút | Thêm skill mới |
| `jp:careers:{org_slug}:list` | 5 phút | Publish/close tin |
| `jp:careers:{org_slug}:post:{slug}` | 10 phút | Cập nhật tin |

---

## 12. Lộ trình triển khai

### Phase 1 — Core Post Builder (tuần 1–2)

- [ ] Migration: `jp_job_posts`, `jp_skill_masters`, `jp_benefit_masters`
- [ ] CRUD JP_JOB_POST: tạo, sửa, duplicate, xem lịch sử
- [ ] Seed skill masters và benefit masters mặc định
- [ ] Vòng đời: draft → pending_review → published → closed
- [ ] Audit trail: JP_JOB_POST_HISTORY tự động khi thay đổi status

### Phase 2 — Skills, Benefits & Questions (tuần 3)

- [ ] Migration: `jp_job_post_skills`, `jp_job_post_benefits`, `jp_screening_questions`, `jp_screening_choices`
- [ ] UI: thêm skills với autocomplete từ master (gõ tên → suggest → thêm)
- [ ] UI: thêm benefits từ danh mục hoặc tự nhập
- [ ] UI: Screening Question builder (thêm câu hỏi, chọn type, cấu hình disqualifying)

### Phase 3 — Phân phối kênh (tuần 4)

- [ ] `JpJobPostObserver`: sync ra mkt_listings khi publish/close
- [ ] Re-sync badge trên dashboard khi `mkt_sync_status = 'out_of_sync'`
- [ ] Public Career Page API: `GET /api/careers/:org_slug/jobs`
- [ ] Apply flow qua career page → INSERT RC_APPLICATION

### Phase 4 — Analytics (tuần 5)

- [ ] Migration: `jp_job_post_stats`
- [ ] Track view/apply events → upsert daily grain
- [ ] Dashboard analytics: view count, apply rate, source breakdown
- [ ] Expire cron: auto-close expired posts
- [ ] Notification: tin sắp hết hạn (D-7, D-3, D-1)

---

*Version 1.1.0 — Job Posting Center*
*Thay đổi v1.1: (1) Sửa tất cả id UUID PK → BIGINT PK + uuid CHAR(36) riêng biệt; (2) Sửa FK nội bộ jp_* → BIGINT thay vì UUID; (3) Cross-module soft ref dùng .uuid thay vì .id; (4) Chuẩn hóa convention FK theo hệ thống*
*Tham chiếu: Schema.org JobPosting, Google Jobs schema, Internshala job detail*
*Stack: Laravel 13 · SQLite (dev) / MySQL 8+ / PostgreSQL 15+*
