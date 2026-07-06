# Đặc Tả Module: Marketplace Center

> **Hệ thống:** SaaS SME
> **Module:** Marketplace Center
> **Phiên bản:** 2.1.0
> **Ngày:** 2026-06-05
> **Stack:** Laravel 13 · SQLite (dev) / MySQL 8+ / PostgreSQL 15+
> **Liên module:** Job Posting Center (upstream — nguồn tin tuyển dụng), Recruitment Center (downstream — import ứng viên vào pipeline)

---

## Mục lục

1. [Tổng quan & Bản chất module](#1-tổng-quan--bản-chất-module)
2. [Phạm vi](#2-phạm-vi)
3. [Kiến trúc & luồng liên thông](#3-kiến-trúc--luồng-liên-thông)
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

## 1. Tổng quan & Bản chất module

**Marketplace Center** là **cổng thông tin công khai** của hệ sinh thái SME — nơi doanh nghiệp đăng tin tuyển dụng/dự án, và người tìm việc/freelancer tìm cơ hội. Module phục vụ **2 chiều** hoàn toàn rõ ràng.

### Bản chất đúng trong hệ thống SME

Marketplace KHÔNG phải extension của Recruitment. Recruitment là ATS nội bộ. Marketplace là **cổng công khai**:

| Khía cạnh | Recruitment Center | Marketplace Center |
|---|---|---|
| Người dùng | HR, Interviewer — có `users` account trong org | MKT_APPLICANT (auth guard riêng) + Org HR |
| Dữ liệu ứng viên | `rc_candidates` — private, per org | `mkt_applicants` — public profile, tách `users` |
| Tin đăng | Nội bộ, không public | `mkt_listings` — hiển thị công khai |
| Org đăng tin | Không cần — chỉ nội bộ | **Có 3 loại người đăng** (xem bên dưới) |

### Ba loại người đăng tin (`poster_type`)

| poster_type | Mô tả | org_id | Duyệt? |
|---|---|---|---|
| `org` | Tenant đã xác thực trong hệ thống — `organization.status='active'` | NOT NULL | Active ngay |
| `pending_org` | Doanh nghiệp vừa đăng ký, chờ admin duyệt — lưu vào `organizations` với `status='pending'` | NOT NULL (pending) | Sau khi org được duyệt |
| `individual` | Cá nhân / freelancer đăng profile tìm dự án | NULL | Active ngay |

> **Thay đổi so với v1:** `guest_company` bị bỏ. Doanh nghiệp muốn đăng tin phải đăng ký Organization — dữ liệu lưu vào `organizations` sẵn có với `status='pending'`, chờ admin hệ thống duyệt. Sau khi duyệt (`status='active'`), `poster_type` tự động chuyển thành `org`.

### MKT_APPLICANT — người tìm việc (tách users)

`mkt_applicants` là bảng riêng, **không phải extension của `users`**. Lý do:
- Người tìm việc đến từ bên ngoài hệ thống — không có `users` account
- Auth riêng qua Laravel Guard `marketplace` (email/password riêng biệt)
- Public profile — có thể xem mà không cần đăng nhập
- Phân quyền rõ ràng: applicant chỉ thấy job `status='active'`, chỉ xem đơn mình đã nộp

---

## 2. Phạm vi

### Trong phạm vi

- Listing đa loại: `job` (việc làm), `project` (dự án outsource), `resource` (freelancer tìm việc)
- **Luồng đăng ký doanh nghiệp** → lưu vào `organizations` (`status='pending'`) → admin duyệt → active
- **MKT_APPLICANT**: auth riêng (marketplace guard), profile công khai, tách `users` table
- Phân quyền applicant: chỉ xem listing active, chỉ thấy đơn mình nộp
- Apply và track đơn ứng tuyển qua Marketplace
- Bookmark listing
- Review & rating sau hợp tác
- Publish job từ Job Posting Center ra Marketplace (jp_job_post_id soft ref + Observer)
- Import ứng viên ngược lại vào Recruitment
- Analytics: view count, apply count, conversion per listing

### Ngoài phạm vi

- Messaging / Chat real-time — deferred (giao tiếp qua email ngoài hệ thống)
- Payment / hợp đồng thuê freelancer — module riêng
- AI matching / recommendation engine — mở rộng sau

---

## 3. Kiến trúc & luồng liên thông

### 3.1 Luồng đăng ký doanh nghiệp (pending_org)

```
Doanh nghiệp chưa là tenant trên hệ thống:
  1. Truy cập Marketplace → "Đăng tin tuyển dụng"
  2. Điền thông tin doanh nghiệp (tên, địa chỉ, website, email liên hệ)
  3. Hệ thống:
       INSERT organizations (name, email, status='pending', source='marketplace_signup')
       INSERT users (org_id=org.id, role='org_admin', email=submitted_email)
       INSERT mkt_listings (poster_type='pending_org', org_id=org.id, status='pending_review')
  4. Admin hệ thống nhận thông báo → xem xét
  5. Admin duyệt:
       UPDATE organizations SET status='active'
       UPDATE mkt_listings SET poster_type='org', status='active'
         WHERE org_id = org.id AND poster_type='pending_org'
  6. Doanh nghiệp nhận email xác nhận → đăng nhập vào hệ thống như tenant bình thường
     (tiếp tục dùng Marketplace hoặc khám phá các module khác)
```

> **Lý do không dùng guest_company_name trên mkt_listings:** Lưu thông tin DN vào `organizations` đảm bảo single source of truth. Khi duyệt, không cần migrate data — chỉ cập nhật status.

### 3.2 Luồng publish: Job Posting Center → Marketplace

```
JP_JOB_POST (status='published' và publish_to_marketplace=TRUE)
       │
       ▼ JpJobPostObserver::updated() — tự động khi status → 'published'
INSERT mkt_listings:
  poster_type             = 'org'
  org_id                  = job_post.org_id (BIGINT)
  posted_by               = current_user.id (BIGINT)
  jp_job_post_id          = job_post.uuid  (CHAR(36) — soft ref, không FK)
  jp_sync_status          = 'synced'
  auto_close_on_jp        = TRUE
  listing_type            = 'job'
  title, description, requirements, salary_*, employment_type, headcount
  work_type               = job_post.work_arrangement
  experience_level        = job_post.experience_level
  expire_at               = job_post.expire_at
  status                  = 'active'

Sau đó 2 bản ghi độc lập:
  JP_JOB_POST thay đổi nội dung sau khi đã publish
    → JpJobPostObserver::updated()
    → UPDATE mkt_listings SET jp_sync_status='out_of_sync'
       WHERE jp_job_post_id = job_post.uuid

  HR click "Re-sync":
    → Overwrite trường nội dung từ JP_JOB_POST
    → UPDATE jp_sync_status = 'synced'

  JP_JOB_POST status → 'closed'/'archived' và auto_close_on_jp=TRUE:
    → UPDATE mkt_listings SET status='closed', closed_at=NOW()
       WHERE jp_job_post_id = job_post.uuid
```

### 3.3 Luồng import: Marketplace → Recruitment

```
MKT_APPLICATION (status='submitted' hoặc 'shortlisted')
       │ HR quan tâm
       ▼ "Import vào Recruitment pipeline"

Điều kiện: mkt_listing.jp_job_post_id IS NOT NULL

  1. Tìm rc_candidates theo email + org_id → reuse hoặc INSERT mới
     (ghi mkt_applicant_id = mkt_applicant.uuid như soft ref)
  2. INSERT rc_applications:
       jp_job_post_id      = mkt_listing.jp_job_post_id  (CHAR(36), soft ref)
       candidate_id        = rc_candidate.id
       apply_source        = 'marketplace'
       mkt_application_id  = mkt_application.uuid  (CHAR(36), không FK)
  3. UPDATE mkt_applications SET import_status='imported'
```

---

## 4. Enum Values

### MKT_LISTING

| Trường | Giá trị |
|---|---|
| `listing_type` | `job` \| `project` \| `resource` |
| `poster_type` | `org` \| `pending_org` \| `individual` |
| `status` | `draft` \| `pending_review` \| `active` \| `paused` \| `closed` \| `expired` |
| `work_type` | `onsite` \| `remote` \| `hybrid` \| `flexible` |
| `employment_type` | `full_time` \| `part_time` \| `contractor` \| `freelance` \| `intern` |
| `experience_level` | `entry` \| `junior` \| `mid` \| `senior` \| `lead` \| `any` |
| `visibility` | `public` \| `unlisted` \| `members_only` |
| `jp_sync_status` | `synced` \| `out_of_sync` \| NULL |

### MKT_APPLICANT

| Trường | Giá trị |
|---|---|
| `account_type` | `individual` \| `team` \| `agency` |
| `status` | `active` \| `inactive` \| `suspended` |
| `availability` | `immediate` \| `2_weeks` \| `1_month` \| `negotiable` \| `not_available` |

### MKT_APPLICATION

| Trường | Giá trị |
|---|---|
| `status` | `submitted` \| `viewed` \| `shortlisted` \| `rejected` \| `hired` \| `withdrawn` |
| `import_status` | `not_imported` \| `imported` \| `skipped` |

### MKT_REVIEW

| Trường | Giá trị |
|---|---|
| `reviewer_type` | `org` \| `applicant` |
| `relation_type` | `hired` \| `project_completed` \| `collaboration` |

---

## 5. ERD — Quan hệ bảng

```
[organizations] (existing — bao gồm cả pending_org)
       │ 0:N (NULL khi individual)
       ▼
MKT_LISTING ◄──── jp_job_post_id (CHAR(36), soft ref → jp_job_posts.uuid)
    │
    ├─1:N──► MKT_APPLICATION ◄──── MKT_APPLICANT
    │                                    │
    │                                   1:N──► MKT_APPLICANT_SKILL
    │                                   1:N──► MKT_APPLICANT_EXPERIENCE
    │                                   1:N──► MKT_APPLICANT_PORTFOLIO
    │
    ├─N:M──► MKT_TAG (qua MKT_LISTING_TAG)
    │
    ├─1:N──► MKT_LISTING_BOOKMARK
    │
    └─1:N──► MKT_REVIEW

MKT_APPLICATION.import_status → rc_candidates (soft ref CHAR(36) → rc_candidates.uuid)
```

### Quan hệ tổng hợp

| Bảng A | Quan hệ | Bảng B | Ghi chú |
|---|---|---|---|
| organizations | 0:N | MKT_LISTING | BIGINT FK — NULL khi poster_type='individual' |
| jp_job_posts | 0:1 | MKT_LISTING | CHAR(36) soft ref → jp_job_posts.uuid (không FK) |
| MKT_LISTING | 1:N | MKT_APPLICATION | Đơn ứng tuyển |
| MKT_APPLICANT | 1:N | MKT_APPLICATION | Ứng viên apply |
| MKT_APPLICANT | 1:N | MKT_APPLICANT_SKILL | Kỹ năng |
| MKT_APPLICANT | 1:N | MKT_APPLICANT_EXPERIENCE | Kinh nghiệm |
| MKT_APPLICANT | 1:N | MKT_APPLICANT_PORTFOLIO | Portfolio |
| MKT_LISTING | N:M | MKT_TAG | Qua MKT_LISTING_TAG |
| MKT_LISTING | 1:N | MKT_LISTING_BOOKMARK | Bookmark |
| MKT_LISTING | 1:N | MKT_REVIEW | Review sau hợp tác |

---

## 6. Đặc tả bảng dữ liệu

> **Quy ước FK:**
> - FK → bảng hệ thống sẵn có (`organizations`, `users`, `departments`, `job_titles`): `UNSIGNED BIGINT`
> - `jp_job_post_id`: `CHAR(36)` soft ref → `jp_job_posts.uuid` — không FK constraint, không ON DELETE
> - Nội bộ `mkt_*` ↔ `mkt_*`: `UNSIGNED BIGINT` (FK → `id` cột BIGINT PK)
> - Ref lỏng sang RC (`imported_rc_candidate_id`, `mkt_application_id`): CHAR(36) tham chiếu `.uuid`, không FK
>
> **Quy ước PK:** Mọi bảng đều có:
> ```php
> $table->id();                                          // BIGINT PK
> $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
> ```

---

### 6.1 MKT_LISTING — Tin đăng công khai

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `org_id` | UNSIGNED BIGINT | NULL | FK, INDEX | NULL | FK → organizations.id — NULL chỉ khi poster_type='individual' |
| `posted_by` | UNSIGNED BIGINT | NOT NULL | FK | | FK → users.id — người tạo listing |
| `poster_type` | ENUM | NOT NULL | INDEX | `org` | org \| pending_org \| individual |
| `listing_type` | ENUM | NOT NULL | INDEX | `job` | job \| project \| resource |
| `title` | VARCHAR(300) | NOT NULL | | | |
| `slug` | VARCHAR(320) | NOT NULL | UNIQUE | | Globally unique, URL-friendly |
| `description` | TEXT | NOT NULL | | | |
| `requirements` | TEXT | NULL | | NULL | |
| `benefits` | TEXT | NULL | | NULL | |
| `status` | ENUM | NOT NULL | INDEX | `draft` | |
| `visibility` | ENUM | NOT NULL | | `public` | |
| `work_type` | ENUM | NOT NULL | | `flexible` | |
| `employment_type` | ENUM | NULL | | NULL | Chỉ dùng khi listing_type='job' |
| `experience_level` | ENUM | NOT NULL | | `any` | |
| `salary_min` | DECIMAL(15,2) | NULL | | NULL | |
| `salary_max` | DECIMAL(15,2) | NULL | | NULL | |
| `salary_currency` | CHAR(3) | NOT NULL | | `VND` | |
| `salary_is_negotiable` | BOOLEAN | NOT NULL | | FALSE | |
| `salary_is_visible` | BOOLEAN | NOT NULL | | TRUE | |
| `budget_min` | DECIMAL(15,2) | NULL | | NULL | Ngân sách (listing_type='project') |
| `budget_max` | DECIMAL(15,2) | NULL | | NULL | |
| `duration_days` | INT | NULL | | NULL | Thời gian dự án |
| `location` | VARCHAR(200) | NULL | | NULL | |
| `department_id` | UNSIGNED BIGINT | NULL | FK | NULL | FK → departments.id ON DELETE SET NULL |
| `position_id` | UNSIGNED BIGINT | NULL | FK | NULL | FK → job_titles.id ON DELETE SET NULL |
| `headcount` | SMALLINT | NOT NULL | | 1 | |
| `application_count` | INT | NOT NULL | | 0 | Denormalized |
| `view_count` | INT | NOT NULL | | 0 | Denormalized |
| `bookmark_count` | INT | NOT NULL | | 0 | Denormalized |
| `jp_job_post_id` | CHAR(36) | NULL | INDEX | NULL | Soft ref → jp_job_posts.uuid (không FK) |
| `jp_sync_status` | ENUM | NULL | | NULL | synced \| out_of_sync — NULL khi không từ JP |
| `auto_close_on_jp` | BOOLEAN | NOT NULL | | TRUE | Tự đóng khi JP_JOB_POST đóng |
| `expire_at` | TIMESTAMP | NULL | INDEX | NULL | |
| `closed_at` | TIMESTAMP | NULL | | NULL | |
| `created_at` | TIMESTAMP | NOT NULL | INDEX | NOW() | |
| `updated_at` | TIMESTAMP | NOT NULL | | NOW() | |

```sql
CREATE UNIQUE INDEX idx_mkt_listing_slug         ON mkt_listings(slug);
CREATE INDEX idx_mkt_listing_browse              ON mkt_listings(listing_type, status, created_at DESC)
  WHERE status = 'active';
CREATE INDEX idx_mkt_listing_org                 ON mkt_listings(org_id, status);
CREATE INDEX idx_mkt_listing_poster_type         ON mkt_listings(poster_type, status);
CREATE INDEX idx_mkt_listing_jp_source           ON mkt_listings(jp_job_post_id)
  WHERE jp_job_post_id IS NOT NULL;
CREATE INDEX idx_mkt_listing_jp_sync             ON mkt_listings(org_id, jp_sync_status)
  WHERE jp_sync_status = 'out_of_sync';
CREATE INDEX idx_mkt_listing_pending_review      ON mkt_listings(poster_type, status)
  WHERE status = 'pending_review';
CREATE INDEX idx_mkt_listing_expire              ON mkt_listings(expire_at, status)
  WHERE expire_at IS NOT NULL AND status = 'active';
CREATE UNIQUE INDEX idx_mkt_listing_jp_unique    ON mkt_listings(jp_job_post_id)
  WHERE jp_job_post_id IS NOT NULL AND status != 'closed';
CREATE FULLTEXT INDEX idx_mkt_listing_fts        ON mkt_listings(title, description, requirements, location);
```

---

### 6.2 MKT_APPLICANT — Hồ sơ người tìm việc (tách users)

Auth riêng qua Laravel Guard `marketplace`. Lưu tách `users` table. Đây là public profile — ai cũng xem được nếu `is_profile_public=TRUE`.

**Phân quyền của MKT_APPLICANT:**
- Xem listing: chỉ `status='active'` và `visibility='public'` (hoặc `members_only` nếu đã đăng nhập)
- Xem đơn: chỉ đơn của chính mình (`applicant_id = auth()->id()`)
- Xem profile org: thông tin công khai từ `organizations` (name, logo, website)
- Không thể xem RC pipeline, RC candidate khác, hay thông tin nội bộ của org

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `email` | VARCHAR(150) | NOT NULL | UNIQUE | | Định danh đăng nhập Marketplace |
| `password_hash` | VARCHAR(255) | NOT NULL | | | Auth riêng, không dùng users table |
| `email_verified_at` | TIMESTAMP | NULL | | NULL | |
| `account_type` | ENUM | NOT NULL | | `individual` | individual \| team \| agency |
| `display_name` | VARCHAR(150) | NOT NULL | | | Tên hiển thị công khai |
| `slug` | VARCHAR(160) | NOT NULL | UNIQUE | | URL profile |
| `headline` | VARCHAR(200) | NULL | | NULL | "Senior Laravel Developer · 5 năm" |
| `bio` | TEXT | NULL | | NULL | |
| `phone` | VARCHAR(20) | NULL | | NULL | |
| `location` | VARCHAR(150) | NULL | | NULL | |
| `avatar_url` | TEXT | NULL | | NULL | |
| `website_url` | VARCHAR(300) | NULL | | NULL | |
| `linkedin_url` | VARCHAR(300) | NULL | | NULL | |
| `years_experience` | SMALLINT | NULL | | NULL | |
| `expected_salary_min` | DECIMAL(15,2) | NULL | | NULL | |
| `expected_salary_max` | DECIMAL(15,2) | NULL | | NULL | |
| `salary_currency` | CHAR(3) | NOT NULL | | `VND` | |
| `status` | ENUM | NOT NULL | INDEX | `active` | |
| `availability` | ENUM | NOT NULL | | `negotiable` | |
| `is_profile_public` | BOOLEAN | NOT NULL | | TRUE | FALSE = chỉ org đã nhận đơn mới xem |
| `is_email_public` | BOOLEAN | NOT NULL | | FALSE | |
| `profile_complete_pct` | SMALLINT | NOT NULL | | 0 | Tính bằng Observer |
| `total_applications` | INT | NOT NULL | | 0 | Denormalized |
| `hired_count` | INT | NOT NULL | | 0 | |
| `avg_rating` | DECIMAL(3,2) | NULL | | NULL | |
| `remember_token` | VARCHAR(100) | NULL | | NULL | Laravel auth |
| `created_at` | TIMESTAMP | NOT NULL | | NOW() | |
| `updated_at` | TIMESTAMP | NOT NULL | | NOW() | |

```sql
CREATE UNIQUE INDEX idx_mkt_appl_email  ON mkt_applicants(email);
CREATE UNIQUE INDEX idx_mkt_appl_slug   ON mkt_applicants(slug);
CREATE INDEX idx_mkt_appl_status        ON mkt_applicants(status, availability);
CREATE FULLTEXT INDEX idx_mkt_appl_fts  ON mkt_applicants(display_name, headline, bio, location);
```

**Phân quyền chi tiết (implement bằng Policy):**

```php
// app/Policies/MktListingPolicy.php
class MktListingPolicy
{
    public function view(MktApplicant $applicant, MktListing $listing): bool
    {
        if ($listing->status !== 'active') return false;
        if ($listing->visibility === 'public') return true;
        if ($listing->visibility === 'members_only') return true; // đã login
        return false; // unlisted — chỉ có link trực tiếp
    }

    public function apply(MktApplicant $applicant, MktListing $listing): bool
    {
        return $listing->status === 'active';
    }
}

// app/Policies/MktApplicationPolicy.php
class MktApplicationPolicy
{
    public function view(MktApplicant $applicant, MktApplication $application): bool
    {
        return $application->applicant_id === $applicant->id;
    }
}
```

---

### 6.3 MKT_APPLICANT_SKILL

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `applicant_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → mkt_applicants.id |
| `skill_name` | VARCHAR(100) | NOT NULL | INDEX | | "Laravel", "React", "Figma" |
| `proficiency_level` | ENUM | NOT NULL | | `intermediate` | beginner \| intermediate \| advanced \| expert |
| `years_used` | SMALLINT | NULL | | NULL | |
| `sort_order` | SMALLINT | NOT NULL | | 0 | |

```sql
CREATE UNIQUE INDEX idx_mkt_skill_unique ON mkt_applicant_skills(applicant_id, skill_name);
CREATE INDEX idx_mkt_skill_name          ON mkt_applicant_skills(skill_name, proficiency_level);
```

---

### 6.4 MKT_APPLICANT_EXPERIENCE

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `applicant_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → mkt_applicants.id |
| `company_name` | VARCHAR(200) | NOT NULL | | | |
| `title` | VARCHAR(150) | NOT NULL | | | |
| `description` | TEXT | NULL | | NULL | |
| `start_month` | SMALLINT | NOT NULL | | | 1–12 |
| `start_year` | SMALLINT | NOT NULL | | | |
| `end_month` | SMALLINT | NULL | | NULL | NULL = hiện tại |
| `end_year` | SMALLINT | NULL | | NULL | NULL = hiện tại |
| `is_current` | BOOLEAN | NOT NULL | | FALSE | |
| `sort_order` | SMALLINT | NOT NULL | | 0 | |

---

### 6.5 MKT_APPLICANT_PORTFOLIO

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `applicant_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → mkt_applicants.id |
| `title` | VARCHAR(200) | NOT NULL | | | |
| `description` | TEXT | NULL | | NULL | |
| `project_url` | VARCHAR(300) | NULL | | NULL | |
| `thumbnail_url` | TEXT | NULL | | NULL | |
| `tech_stack` | VARCHAR(300) | NULL | | NULL | Plain text |
| `completed_year` | SMALLINT | NULL | | NULL | |
| `sort_order` | SMALLINT | NOT NULL | | 0 | |

---

### 6.6 MKT_APPLICATION — Đơn ứng tuyển

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `listing_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → mkt_listings.id |
| `applicant_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → mkt_applicants.id |
| `status` | ENUM | NOT NULL | INDEX | `submitted` | |
| `cover_letter` | TEXT | NULL | | NULL | |
| `expected_salary` | DECIMAL(15,2) | NULL | | NULL | |
| `available_from` | DATE | NULL | | NULL | |
| `portfolio_url` | VARCHAR(300) | NULL | | NULL | |
| `import_status` | ENUM | NOT NULL | | `not_imported` | |
| `imported_rc_candidate_id` | CHAR(36) | NULL | | NULL | Soft ref → rc_candidates.uuid |
| `imported_rc_application_id` | CHAR(36) | NULL | | NULL | Soft ref → rc_applications.uuid |
| `imported_at` | TIMESTAMP | NULL | | NULL | |
| `imported_by` | UNSIGNED BIGINT | NULL | FK | NULL | FK → users.id |
| `viewed_at` | TIMESTAMP | NULL | | NULL | Org xem lần đầu |
| `applied_at` | TIMESTAMP | NOT NULL | INDEX | NOW() | |
| `updated_at` | TIMESTAMP | NOT NULL | | NOW() | |

```sql
CREATE UNIQUE INDEX idx_mkt_app_unique ON mkt_applications(listing_id, applicant_id);
CREATE INDEX idx_mkt_app_listing       ON mkt_applications(listing_id, status);
CREATE INDEX idx_mkt_app_applicant     ON mkt_applications(applicant_id, status);
CREATE INDEX idx_mkt_app_import        ON mkt_applications(import_status, listing_id)
  WHERE import_status = 'not_imported';
```

---

### 6.7 MKT_TAG

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `name` | VARCHAR(80) | NOT NULL | UNIQUE | | "Laravel", "UI/UX" |
| `slug` | VARCHAR(90) | NOT NULL | UNIQUE | | |
| `listing_type` | ENUM | NULL | INDEX | NULL | NULL = dùng cho mọi loại |
| `use_count` | INT | NOT NULL | | 0 | |

### MKT_LISTING_TAG — Pivot

| Trường | Kiểu | Key |
|---|---|---|
| `listing_id` | BIGINT UNSIGNED | PK, FK → mkt_listings.id CASCADE DELETE |
| `tag_id` | BIGINT UNSIGNED | PK, FK → mkt_tags.id |

---

### 6.8 MKT_LISTING_BOOKMARK

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `listing_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → mkt_listings.id |
| `applicant_id` | UNSIGNED BIGINT | NOT NULL | FK | | FK → mkt_applicants.id |
| `note` | VARCHAR(300) | NULL | | NULL | |
| `created_at` | TIMESTAMP | NOT NULL | | NOW() | |

```sql
CREATE UNIQUE INDEX idx_mkt_bookmark_unique ON mkt_listing_bookmarks(listing_id, applicant_id);
```

---

### 6.9 MKT_REVIEW — Đánh giá sau hợp tác

`reviewer_id` là polymorphic: users.id khi `reviewer_type='org'`, mkt_applicants.id khi `reviewer_type='applicant'`. Cả 2 đều là BIGINT.

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `listing_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → mkt_listings.id |
| `application_id` | UNSIGNED BIGINT | NOT NULL | FK | | FK → mkt_applications.id |
| `reviewer_type` | ENUM | NOT NULL | | | org \| applicant |
| `reviewer_id` | UNSIGNED BIGINT | NOT NULL | | | users.id (org) hoặc mkt_applicants.id (applicant) |
| `relation_type` | ENUM | NOT NULL | | `hired` | |
| `overall_rating` | SMALLINT | NOT NULL | | | 1–5 |
| `title` | VARCHAR(200) | NULL | | NULL | |
| `content` | TEXT | NULL | | NULL | |
| `rating_quality` | SMALLINT | NULL | | NULL | |
| `rating_communication` | SMALLINT | NULL | | NULL | |
| `rating_punctuality` | SMALLINT | NULL | | NULL | |
| `is_public` | BOOLEAN | NOT NULL | | TRUE | |
| `created_at` | TIMESTAMP | NOT NULL | INDEX | NOW() | |

```sql
CREATE UNIQUE INDEX idx_mkt_review_unique ON mkt_reviews(application_id, reviewer_type, reviewer_id);
```

---

## 7. Luồng nghiệp vụ

### 7.1 Doanh nghiệp đăng ký và đăng tin

```
[Doanh nghiệp mới — chưa là tenant]
  ├─ Truy cập /portal/employer/register
  ├─ Điền: tên công ty, website, email HR, mô tả, quy mô
  ├─ Hệ thống INSERT organizations (status='pending', source='marketplace_signup')
  ├─ INSERT users (email, org_id, role='org_admin')
  ├─ INSERT mkt_listings (poster_type='pending_org', status='pending_review')
  └─ Admin nhận alert

Admin duyệt tổ chức:
  UPDATE organizations SET status='active', approved_by, approved_at
  UPDATE mkt_listings SET poster_type='org', status='active'
    WHERE org_id = org.id AND poster_type='pending_org'
  Gửi email thông báo cho doanh nghiệp

Tenant đã xác thực đăng tin thêm:
  INSERT mkt_listings (poster_type='org', status='active') — active ngay
```

### 7.2 Applicant đăng ký và apply

```
Applicant đăng ký:
  POST /portal/auth/register
  INSERT mkt_applicants (email, password_hash, display_name)
  Gửi email verify

Applicant apply:
  Check: listing.status = 'active'
  Check: chưa có application với listing_id + applicant_id
  INSERT mkt_applications (status='submitted')
  UPDATE mkt_listings.application_count += 1
  Notify org HR
```

### 7.3 Org xem và xử lý ứng viên Marketplace

```
Org HR vào dashboard Marketplace:
  Xem danh sách mkt_applications của listing
  ├─ viewed_at = NOW() (lần đầu click vào)
  ├─ UPDATE status = 'shortlisted' | 'rejected'
  └─ "Import vào Recruitment":
       Xem mục 3.3 — chỉ khi jp_job_post_id IS NOT NULL
```

---

## 8. Query Patterns

### 8.1 Browse listing công khai

```sql
SELECT
    l.id, l.uuid, l.slug, l.title, l.listing_type,
    l.work_type, l.employment_type, l.experience_level,
    l.salary_min, l.salary_max, l.salary_is_visible,
    l.location, l.application_count, l.created_at,
    COALESCE(o.name, 'Cá nhân') AS poster_name,
    o.logo_path AS org_logo
FROM mkt_listings l
LEFT JOIN organizations o ON o.id = l.org_id
WHERE l.status     = 'active'
  AND l.visibility = 'public'
  AND (:type IS NULL OR l.listing_type = :type)
  AND (:work_type IS NULL OR l.work_type = :work_type)
  AND (:level IS NULL OR l.experience_level = :level)
ORDER BY l.created_at DESC
LIMIT 20 OFFSET :offset;
```

### 8.2 Pending review — admin duyệt tổ chức

```sql
SELECT
    l.id, l.title, l.created_at,
    o.name AS org_name, o.email AS org_email,
    o.website, o.status AS org_status,
    u.email AS hr_email
FROM mkt_listings l
JOIN organizations o ON o.id = l.org_id
JOIN users u ON u.organization_id = o.id AND u.created_at = o.created_at
WHERE l.status = 'pending_review' AND l.poster_type = 'pending_org'
ORDER BY l.created_at;
```

### 8.3 Dashboard org — ứng viên chưa xử lý

```sql
SELECT
    a.id, a.uuid, a.status, a.applied_at, a.import_status,
    ap.display_name, ap.headline, ap.avg_rating,
    ap.years_experience, ap.availability,
    l.title AS listing_title
FROM mkt_applications a
JOIN mkt_applicants ap ON ap.id = a.applicant_id
JOIN mkt_listings l    ON l.id  = a.listing_id
WHERE l.org_id       = :org_id
  AND a.import_status = 'not_imported'
  AND a.status NOT IN ('rejected', 'withdrawn')
ORDER BY a.applied_at DESC;
```

### 8.4 Applicant — đơn của tôi

```sql
SELECT
    a.id, a.uuid, a.status, a.applied_at,
    l.title, l.work_type, l.employment_type,
    COALESCE(o.name, 'Cá nhân') AS org_name,
    o.logo_path
FROM mkt_applications a
JOIN mkt_listings l     ON l.id = a.listing_id
LEFT JOIN organizations o ON o.id = l.org_id
WHERE a.applicant_id = :applicant_id
ORDER BY a.applied_at DESC;
```

### 8.5 Out-of-sync badge — dashboard Job Posting Center

```sql
SELECT COUNT(*) AS out_of_sync_count
FROM mkt_listings
WHERE org_id = :org_id AND jp_sync_status = 'out_of_sync';
```

---

## 9. API Endpoints

### Public (không cần auth)

| Method | Endpoint | Mô tả |
|---|---|---|
| GET | `/api/portal/listings` | Browse listings |
| GET | `/api/portal/listings/:slug` | Chi tiết |
| GET | `/api/portal/listings/:slug/similar` | Listing tương tự |
| GET | `/api/portal/profiles/:slug` | Profile applicant công khai |
| GET | `/api/portal/tags` | Tags phổ biến |

### Applicant Auth (guard: marketplace)

| Method | Endpoint | Mô tả |
|---|---|---|
| POST | `/api/portal/auth/register` | Đăng ký |
| POST | `/api/portal/auth/login` | Đăng nhập |
| POST | `/api/portal/auth/logout` | Đăng xuất |
| GET | `/api/portal/me/profile` | Profile của tôi |
| PUT | `/api/portal/me/profile` | Cập nhật |
| POST | `/api/portal/me/skills` | Thêm skill |
| POST | `/api/portal/me/experiences` | Thêm kinh nghiệm |
| POST | `/api/portal/me/portfolios` | Thêm portfolio |
| GET | `/api/portal/me/applications` | **Chỉ đơn của tôi** |
| POST | `/api/portal/listings/:slug/apply` | Nộp đơn |
| POST | `/api/portal/applications/:id/withdraw` | Rút đơn |
| POST | `/api/portal/listings/:slug/bookmark` | Bookmark |
| GET | `/api/portal/me/bookmarks` | Danh sách bookmark |

### Org (guard: web — users nội bộ)

| Method | Endpoint | Mô tả |
|---|---|---|
| GET | `/api/marketplace/org/listings` | Listings của org |
| POST | `/api/marketplace/org/listings` | Tạo listing trực tiếp |
| PUT | `/api/marketplace/org/listings/:id` | Cập nhật |
| POST | `/api/marketplace/org/listings/:id/close` | Đóng |
| POST | `/api/marketplace/org/listings/:id/resync` | Re-sync từ JP_JOB_POST |
| GET | `/api/marketplace/org/listings/:id/applicants` | Xem ứng viên |
| POST | `/api/marketplace/org/applications/:id/shortlist` | Shortlist |
| POST | `/api/marketplace/org/applications/:id/reject` | Từ chối |
| POST | `/api/marketplace/org/applications/:id/import` | Import vào Recruitment |

### Employer Registration (đăng ký tổ chức mới)

| Method | Endpoint | Mô tả |
|---|---|---|
| POST | `/api/portal/employer/register` | Đăng ký DN mới → INSERT organizations (pending) |
| GET | `/api/portal/employer/status` | Kiểm tra trạng thái duyệt |

### Admin

| Method | Endpoint | Mô tả |
|---|---|---|
| GET | `/api/admin/marketplace/pending-orgs` | DN chờ duyệt |
| POST | `/api/admin/marketplace/orgs/:id/approve` | Duyệt → UPDATE organizations + listings |
| POST | `/api/admin/marketplace/orgs/:id/reject` | Từ chối |

### Reviews

| Method | Endpoint | Mô tả |
|---|---|---|
| POST | `/api/marketplace/applications/:id/review` | Gửi review |
| GET | `/api/portal/profiles/:slug/reviews` | Reviews công khai |

---

## 10. Business Rules

### BR-MKT-001: Poster type và trạng thái listing

- `org`: `org_id NOT NULL`, org.status='active' → listing active ngay
- `pending_org`: `org_id NOT NULL`, org.status='pending' → listing vào `pending_review`
- `individual`: `org_id NULL`, `listing_type` phải là `resource` → listing active ngay
- Khi admin duyệt org: UPDATE tất cả listing `poster_type='pending_org'` → `'org'`, `status='pending_review'` → `'active'`
- Mỗi `jp_job_post_id` (CHAR(36)) chỉ có 1 listing không phải `closed` tại 1 thời điểm (partial unique index)

### BR-MKT-002: Applicant auth độc lập

- `mkt_applicants.email` UNIQUE toàn hệ thống — không share với `users.email`
- Password hash riêng — không dùng `users` table để auth
- Laravel Guard `marketplace` xử lý: `config/auth.php` thêm guard + provider riêng
- Phân quyền bằng Policy: applicant chỉ xem listing active/public, chỉ xem đơn mình nộp

### BR-MKT-003: Application

- 1 applicant 1 lần per listing (unique index)
- Không apply listing `closed`/`expired`
- Rút đơn: chỉ khi `submitted` hoặc `viewed`
- Import vào RC: idempotent, chỉ khi `jp_job_post_id IS NOT NULL`

### BR-MKT-004: Quan hệ với Job Posting Center

- `mkt_listings.jp_job_post_id` nullable CHAR(36), soft ref → `jp_job_posts.uuid`, không FK constraint
- Nếu JP_JOB_POST bị xóa: listing vẫn tồn tại, `jp_job_post_id` trở thành dangling ref — app layer handle
- Khi JP_JOB_POST `status='published'` và `publish_to_marketplace=TRUE`: `JpJobPostObserver` tự INSERT/UPDATE mkt_listing
- Khi JP_JOB_POST thay đổi nội dung sau publish: `jp_sync_status = 'out_of_sync'`
- Import RC chỉ khả dụng khi `jp_job_post_id IS NOT NULL`
- Observer pattern: JP module chủ động đẩy dữ liệu, không phải MKT module poll

### BR-MKT-005: Review

- Chỉ review khi `application.status = 'hired'` hoặc project completed
- Mỗi bên 1 lần per application
- Không xóa review — chỉ admin ẩn (`is_public = FALSE`)

---

## 11. Indexes & Caching

```sql
-- Browse public (hot path)
CREATE INDEX idx_mkt_browse_main
  ON mkt_listings(status, listing_type, created_at DESC)
  WHERE status = 'active' AND visibility = 'public';

-- Org manage listings
CREATE INDEX idx_mkt_org_listings
  ON mkt_listings(org_id, status, created_at DESC);

-- Pending org approval (admin queue)
CREATE INDEX idx_mkt_pending_org
  ON mkt_listings(poster_type, status, created_at)
  WHERE status = 'pending_review' AND poster_type = 'pending_org';

-- Out-of-sync badge (JP_JOB_POST sync)
CREATE INDEX idx_mkt_outofsync
  ON mkt_listings(org_id, jp_sync_status)
  WHERE jp_sync_status = 'out_of_sync';

-- Applicant: đơn của tôi
CREATE INDEX idx_mkt_app_my
  ON mkt_applications(applicant_id, status, applied_at DESC);

-- Org: ứng viên chưa xử lý
CREATE INDEX idx_mkt_app_pending_import
  ON mkt_applications(listing_id, import_status, applied_at DESC)
  WHERE import_status = 'not_imported';

-- Skill matching
CREATE INDEX idx_mkt_skill_match
  ON mkt_applicant_skills(skill_name, proficiency_level);

-- Expire cron
CREATE INDEX idx_mkt_expire_cron
  ON mkt_listings(expire_at, status)
  WHERE expire_at IS NOT NULL AND status = 'active';

-- Unique jp_job_post active listing
CREATE UNIQUE INDEX idx_mkt_jp_unique_active
  ON mkt_listings(jp_job_post_id)
  WHERE jp_job_post_id IS NOT NULL AND status != 'closed';
```

### Caching

| Cache key | TTL | Invalidate khi |
|---|---|---|
| `mkt:listings:browse:{hash}` | 2 phút | Listing mới, status change |
| `mkt:listing:{slug}` | 5 phút | Cập nhật listing |
| `mkt:profile:{slug}` | 10 phút | Cập nhật applicant profile |
| `mkt:tags:popular` | 30 phút | use_count thay đổi |
| `mkt:org:{id}:dashboard` | 3 phút | Apply mới, status change |
| `mkt:admin:pending-count` | 1 phút | Org mới đăng ký, duyệt xong |

---

## 12. Lộ trình triển khai

### Phase 1 — Listing & Employer Registration (tuần 1–2)
- [ ] Migration: `mkt_listings` + `mkt_tags` + `mkt_listing_tags`
- [ ] **Employer registration flow**: DN đăng ký → `organizations` (pending) → admin duyệt → active
- [ ] Admin panel: duyệt/từ chối tổ chức
- [ ] Public browse API (không cần auth): filter, search, tag
- [ ] Tenant dashboard: tạo/sửa/đóng listing trực tiếp (không từ JP)
- [ ] Observer: `JpJobPostObserver` → sync status với mkt_listings khi JP publish/close

### Phase 2 — Applicant Auth & Apply (tuần 3–4)
- [ ] Migration: `mkt_applicants` (với `password_hash`, `email_verified_at`)
- [ ] **Laravel Guard `marketplace`**: register, login, logout, verify email
- [ ] Migration: `mkt_applicant_skills`, `mkt_applicant_experiences`, `mkt_applicant_portfolios`
- [ ] Migration: `mkt_applications` + `mkt_listing_bookmarks`
- [ ] Apply flow + phân quyền Policy
- [ ] Org dashboard: xem applicants, shortlist, reject, import vào Recruitment

### Phase 3 — Review, Analytics & Polish (tuần 5–6)
- [ ] Migration: `mkt_reviews`
- [ ] Review flow (unlock khi application.status='hired')
- [ ] Analytics: view/apply/conversion per listing
- [ ] Listing expiry cron
- [ ] JP sync badge: hiển thị `jp_sync_status='out_of_sync'` khi JP thay đổi
- [ ] Trending tags, top listings

---

*Version 2.1.0 — Marketplace Center*
*Thay đổi v2.1: (1) Sửa tất cả id UUID PK → BIGINT PK + uuid CHAR(36) riêng biệt; (2) Sửa FK nội bộ mkt_* → BIGINT thay vì UUID; (3) Đổi rc_hiring_request_id → jp_job_post_id (soft ref → jp_job_posts.uuid) vì RC v3.0 đã loại bỏ RC_HIRING_REQUEST; (4) Đổi rc_sync_status → jp_sync_status, auto_close_on_rc → auto_close_on_jp; (5) Cập nhật luồng 3.2 thành JP→MKT Observer thay vì RC→MKT; (6) Cập nhật BR-MKT-004 và indexes theo jp_job_post_id*
*Thay đổi v2.0: (1) Bỏ guest_company — DN đăng ký qua organizations (pending); (2) Làm rõ MKT_APPLICANT auth riêng biệt users + phân quyền bằng Policy; (3) Xóa MKT_CONVERSATION khỏi ERD; (4) Thêm luồng employer registration chi tiết*
