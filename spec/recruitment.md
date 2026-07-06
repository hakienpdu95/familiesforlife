# Đặc Tả Module: Recruitment Center

> **Hệ thống:** SaaS SME
> **Module:** Recruitment Center (ATS nội bộ thuần túy)
> **Phiên bản:** 3.1.0
> **Ngày:** 2026-06-05
> **Stack:** Laravel 13 · SQLite (dev) / MySQL 8+ / PostgreSQL 15+
> **Liên module:** Job Posting Center (upstream — nguồn tin tuyển dụng), Marketplace Center (upstream — ứng viên ngoài), Workforce Center (downstream — handoff employee)

---

## Mục lục

1. [Tổng quan](#1-tổng-quan)
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

## 1. Tổng quan

**Recruitment Center** là hệ thống **ATS (Applicant Tracking System) thuần túy nội bộ** — chuyên xử lý quy trình từ khi tiếp nhận ứng viên đến khi hire thành công. Module này KHÔNG quản lý tin tuyển dụng hay yêu cầu tuyển dụng — đó là nhiệm vụ của **Job Posting Center**.

### Định vị rõ ràng trong hệ thống

| Module | Nhiệm vụ |
|---|---|
| **Job Posting Center** | Soạn thảo, quản lý, phân phối tin tuyển dụng |
| **Recruitment Center** | Tiếp nhận ứng viên → pipeline → phỏng vấn → offer → hire |
| **Marketplace Center** | Cổng công khai cho ứng viên bên ngoài apply |
| **Workforce Center** | Onboarding sau khi hire thành công |

### Quan hệ upstream

- **Job Posting Center → Recruitment:** `RC_APPLICATION.jp_job_post_id` reference đến `jp_job_posts.uuid` (CHAR(36) soft ref) — biết ứng viên apply vào tin nào
- **Marketplace Center → Recruitment:** HR import `MKT_APPLICATION` → tạo `RC_CANDIDATE` + `RC_APPLICATION`

### Người dùng

| Vai trò | Quyền |
|---|---|
| **HR Admin** | Toàn quyền: cấu hình pipeline, quản lý tất cả candidate và application |
| **Recruiter** | Xử lý pipeline, lên lịch phỏng vấn, ghi chú, gửi offer |
| **Hiring Manager** | Xem ứng viên của job mình, duyệt offer |
| **Interviewer** | Xem thông tin ứng viên được assign, nộp đánh giá |

---

## 2. Phạm vi

### Trong phạm vi — chỉ nội bộ

- **Pipeline stages** tùy chỉnh per org — org tự định nghĩa
- **Candidate pool** (`RC_CANDIDATE`): hồ sơ ứng viên private per org
- **Application tracking**: di chuyển ứng viên qua stages, audit log bất biến
- **Screening answers**: lưu câu trả lời screening questions từ JP_SCREENING_QUESTION
- **Phỏng vấn**: lịch, interview panel (users nội bộ), đánh giá theo tiêu chí
- **Offer letter**: điều khoản, duyệt nội bộ, gửi, theo dõi phản hồi
- **Notes & attachments** trên ứng viên / đơn ứng tuyển
- **Handoff** sang `employees` khi offer accepted (Workforce Center)
- **Analytics nội bộ**: funnel conversion, time-to-hire, source effectiveness

### Ngoài phạm vi

- Quản lý / đăng tin tuyển dụng → **Job Posting Center**
- Career page / job board công khai → **Marketplace Center**
- Onboarding sau hire → **Workforce Center**
- Email marketing / tự động hóa email → service riêng

---

## 3. Kiến trúc tổng thể

```
Job Posting Center          Marketplace Center
  jp_job_posts                mkt_applications
       │                            │
       │ jp_job_post_id             │ import
       │ (CHAR(36), soft ref        │ (mkt_application_id
       │  → jp_job_posts.uuid)      │  CHAR(36) soft ref)
       └──────────────┬─────────────┘
                      ▼
┌─────────────────────────────────────────────┐
│           RECRUITMENT CENTER (ATS)          │
│                                             │
│  RC_CANDIDATE ────────────────────────────┐ │
│      │                                    │ │
│     1:N                                   │ │
│      ▼                                    │ │
│  RC_APPLICATION ──► RC_PIPELINE_STAGE     │ │
│      │                                    │ │
│      ├─1:N──► RC_APPLICATION_STAGE_LOG   │ │
│      ├─1:N──► RC_INTERVIEW               │ │
│      │            ├─ RC_INTERVIEW_PANELIST│ │
│      │            └─ RC_INTERVIEW_EVAL   │ │
│      ├─1:N──► RC_APPLICATION_ANSWER      │ │
│      └─1:N──► RC_OFFER                  │ │
│                                           │ │
│  RC_CANDIDATE_NOTE ◄──────────────────────┘ │
│  RC_CANDIDATE_ATTACHMENT                    │
└─────────────────────────────────────────────┘
                      │
                      ▼ offer accepted
               Workforce Center
                  employees
```

---

## 4. Enum Values

### RC_PIPELINE_STAGE

| Trường | Giá trị | Mô tả |
|---|---|---|
| `stage_type` | `screening` | Sàng lọc hồ sơ |
| | `assessment` | Bài test / assignment |
| | `interview` | Phỏng vấn |
| | `offer` | Giai đoạn offer |
| | `hired` | Đã nhận việc — terminal |
| | `rejected` | Từ chối — terminal |

### RC_CANDIDATE

| Trường | Giá trị |
|---|---|
| `status` | `active` \| `hired` \| `blacklisted` \| `inactive` |
| `source` | `direct` \| `linkedin` \| `referral` \| `job_board` \| `agency` \| `marketplace` \| `career_page` \| `other` |

### RC_APPLICATION

| Trường | Giá trị |
|---|---|
| `status` | `active` \| `hired` \| `rejected` \| `withdrawn` \| `on_hold` \| `disqualified` |
| `apply_source` | `direct` \| `marketplace` \| `career_page` \| `linkedin` \| `referral` \| `agency` \| `other` |

### RC_APPLICATION_STAGE_LOG

| Trường | Giá trị |
|---|---|
| `result` | `passed` \| `failed` \| `skipped` \| `moved_back` |

### RC_INTERVIEW

| Trường | Giá trị |
|---|---|
| `interview_type` | `phone_screen` \| `video` \| `onsite` \| `technical` \| `case_study` \| `panel` |
| `status` | `scheduled` \| `confirmed` \| `completed` \| `cancelled` \| `no_show` |

### RC_INTERVIEW_PANELIST

| Trường | Giá trị |
|---|---|
| `role` | `interviewer` \| `observer` \| `note_taker` |
| `response_status` | `pending` \| `accepted` \| `declined` |

### RC_INTERVIEW_EVALUATION

| Trường | Giá trị |
|---|---|
| `verdict` | `strong_yes` \| `yes` \| `neutral` \| `no` \| `strong_no` |

### RC_OFFER

| Trường | Giá trị |
|---|---|
| `status` | `draft` \| `pending_approval` \| `approved` \| `sent` \| `accepted` \| `rejected` \| `expired` \| `revoked` |

---

## 5. ERD — Quan hệ bảng

```
[users]       (existing — interviewer, recruiter, hiring manager)
[departments] (existing)
[job_titles]  (existing)
       │
RC_CANDIDATE (private per org)
    │ 1:N
    ▼
RC_APPLICATION
    ├─ jp_job_post_id     CHAR(36) soft ref → jp_job_posts.uuid
    ├─ mkt_application_id CHAR(36) soft ref → mkt_applications.uuid
    ├─ current_stage_id   BIGINT FK → RC_PIPELINE_STAGE.id
    │
    ├─1:N──► RC_APPLICATION_STAGE_LOG (immutable)
    │             └─FK (BIGINT)──► RC_PIPELINE_STAGE
    │
    ├─1:N──► RC_APPLICATION_ANSWER
    │             └─ jp_question_id CHAR(36) soft ref → jp_screening_questions.uuid
    │
    ├─1:N──► RC_INTERVIEW
    │             ├─1:N──► RC_INTERVIEW_PANELIST → [users]
    │             └─1:N──► RC_INTERVIEW_EVALUATION
    │                           └─1:N──► RC_EVALUATION_CRITERION
    │
    └─1:N──► RC_OFFER

RC_CANDIDATE ──1:N──► RC_CANDIDATE_NOTE
RC_CANDIDATE ──1:N──► RC_CANDIDATE_ATTACHMENT
```

### Quan hệ tổng hợp

| Bảng A | Quan hệ | Bảng B | Ghi chú |
|---|---|---|---|
| RC_CANDIDATE | 1:N | RC_APPLICATION | 1 ứng viên nhiều đơn (nhiều job) |
| RC_APPLICATION | N:1 | RC_PIPELINE_STAGE | Stage hiện tại — BIGINT FK |
| RC_APPLICATION | 1:N | RC_APPLICATION_STAGE_LOG | Audit trail |
| RC_APPLICATION | 1:N | RC_APPLICATION_ANSWER | Trả lời screening |
| RC_APPLICATION | 1:N | RC_INTERVIEW | Phỏng vấn |
| RC_INTERVIEW | 1:N | RC_INTERVIEW_PANELIST | Panel members |
| RC_INTERVIEW | 1:N | RC_INTERVIEW_EVALUATION | Đánh giá |
| RC_INTERVIEW_EVALUATION | 1:N | RC_EVALUATION_CRITERION | Điểm tiêu chí |
| RC_APPLICATION | 1:N | RC_OFFER | Offer |
| RC_CANDIDATE | 1:N | RC_CANDIDATE_NOTE | Ghi chú |
| RC_CANDIDATE | 1:N | RC_CANDIDATE_ATTACHMENT | File CV |

---

## 6. Đặc tả bảng dữ liệu

> **Quy ước FK:**
> - Bảng hệ thống (`organizations`, `users`, `departments`, `job_titles`): `UNSIGNED BIGINT`
> - Nội bộ `rc_*` ↔ `rc_*`: `UNSIGNED BIGINT` (FK → `id` cột BIGINT PK)
> - Soft ref cross-module: `CHAR(36)` tham chiếu `.uuid` của bảng đích, không FK constraint, không ON DELETE
>
> **Quy ước PK:** Mọi bảng đều có:
> ```php
> $table->id();                                          // BIGINT PK
> $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
> ```

---

### 6.1 RC_PIPELINE_STAGE — Giai đoạn tuyển dụng

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `org_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → organizations.id |
| `name` | VARCHAR(100) | NOT NULL | | | "Screening CV", "Technical Interview" |
| `stage_type` | ENUM | NOT NULL | INDEX | | Xem mục 4 |
| `sort_order` | SMALLINT | NOT NULL | | 0 | |
| `require_score` | BOOLEAN | NOT NULL | | FALSE | Bắt buộc có evaluation trước khi pass |
| `send_notification` | BOOLEAN | NOT NULL | | TRUE | Gửi email thông báo cho ứng viên |
| `color_hex` | CHAR(7) | NULL | | NULL | Màu kanban |
| `is_active` | BOOLEAN | NOT NULL | | TRUE | |

```sql
CREATE INDEX idx_rc_stage_org ON rc_pipeline_stages(org_id, sort_order)
  WHERE is_active = TRUE;
```

---

### 6.2 RC_CANDIDATE — Hồ sơ ứng viên (private)

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `org_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → organizations.id |
| `full_name` | VARCHAR(150) | NOT NULL | | | |
| `email` | VARCHAR(150) | NOT NULL | UNIQUE(org_id) | | Định danh unique per org |
| `phone` | VARCHAR(20) | NULL | | NULL | |
| `date_of_birth` | DATE | NULL | | NULL | |
| `gender` | ENUM | NULL | | NULL | male \| female \| other |
| `current_title` | VARCHAR(150) | NULL | | NULL | |
| `current_company` | VARCHAR(150) | NULL | | NULL | |
| `years_experience` | SMALLINT | NULL | | NULL | |
| `skills` | TEXT | NULL | | NULL | Plain text, phân cách phẩy |
| `source` | VARCHAR(30) | NOT NULL | INDEX | `direct` | |
| `referred_by` | UNSIGNED BIGINT | NULL | FK | NULL | FK → users.id |
| `mkt_applicant_id` | CHAR(36) | NULL | INDEX | NULL | Soft ref → mkt_applicants.uuid |
| `linkedin_url` | VARCHAR(300) | NULL | | NULL | |
| `portfolio_url` | VARCHAR(300) | NULL | | NULL | |
| `status` | ENUM | NOT NULL | INDEX | `active` | |
| `blacklist_reason` | TEXT | NULL | | NULL | |
| `created_by` | UNSIGNED BIGINT | NOT NULL | FK | | FK → users.id |
| `updated_by` | UNSIGNED BIGINT | NULL | FK | NULL | FK → users.id |
| `created_at` | TIMESTAMP | NOT NULL | | NOW() | |
| `updated_at` | TIMESTAMP | NOT NULL | | NOW() | |

```sql
CREATE UNIQUE INDEX idx_rc_cand_email  ON rc_candidates(org_id, email);
CREATE INDEX idx_rc_cand_status        ON rc_candidates(org_id, status, source);
CREATE INDEX idx_rc_cand_mkt           ON rc_candidates(mkt_applicant_id)
  WHERE mkt_applicant_id IS NOT NULL;
CREATE FULLTEXT INDEX idx_rc_cand_fts  ON rc_candidates(full_name, email, current_title, skills);
```

---

### 6.3 RC_APPLICATION — Đơn ứng tuyển

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `candidate_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → rc_candidates.id |
| `current_stage_id` | UNSIGNED BIGINT | NOT NULL | FK | | FK → rc_pipeline_stages.id — Stage hiện tại |
| `org_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → organizations.id — Denormalized từ candidate |
| `jp_job_post_id` | CHAR(36) | NULL | INDEX | NULL | Soft ref → jp_job_posts.uuid — biết apply vào tin nào |
| `mkt_application_id` | CHAR(36) | NULL | INDEX | NULL | Soft ref → mkt_applications.uuid — nếu import từ MKT |
| `status` | ENUM | NOT NULL | INDEX | `active` | |
| `apply_source` | VARCHAR(30) | NOT NULL | | `direct` | |
| `cover_letter` | TEXT | NULL | | NULL | |
| `expected_salary` | DECIMAL(15,2) | NULL | | NULL | |
| `notice_period_days` | SMALLINT | NULL | | NULL | |
| `is_disqualified` | BOOLEAN | NOT NULL | | FALSE | TRUE = fail screening question tự động |
| `disqualify_reason` | TEXT | NULL | | NULL | Câu hỏi nào gây disqualify |
| `assigned_to` | UNSIGNED BIGINT | NULL | FK | NULL | FK → users.id — Recruiter phụ trách |
| `rejection_reason` | TEXT | NULL | | NULL | |
| `applied_at` | TIMESTAMP | NOT NULL | INDEX | NOW() | |
| `updated_at` | TIMESTAMP | NOT NULL | | NOW() | |

```sql
CREATE INDEX idx_rc_app_candidate ON rc_applications(candidate_id, status);
CREATE INDEX idx_rc_app_stage     ON rc_applications(current_stage_id, status);
CREATE INDEX idx_rc_app_jp        ON rc_applications(jp_job_post_id, status)
  WHERE jp_job_post_id IS NOT NULL;
CREATE INDEX idx_rc_app_assigned  ON rc_applications(assigned_to, status);
-- Tránh 1 ứng viên apply cùng 1 tin 2 lần
CREATE UNIQUE INDEX idx_rc_app_unique ON rc_applications(candidate_id, jp_job_post_id)
  WHERE jp_job_post_id IS NOT NULL AND status != 'withdrawn';
```

---

### 6.4 RC_APPLICATION_ANSWER — Trả lời câu hỏi sàng lọc

Lưu câu trả lời từ ứng viên cho các `JP_SCREENING_QUESTION` của tin tuyển dụng.

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `application_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → rc_applications.id CASCADE DELETE |
| `jp_question_id` | CHAR(36) | NOT NULL | INDEX | | Soft ref → jp_screening_questions.uuid |
| `question_text` | VARCHAR(500) | NOT NULL | | | Snapshot câu hỏi tại thời điểm apply |
| `question_type` | VARCHAR(30) | NOT NULL | | | Snapshot question_type |
| `answer_text` | TEXT | NULL | | NULL | Trả lời dạng text / number |
| `answer_bool` | BOOLEAN | NULL | | NULL | Trả lời Yes/No |
| `answer_choices` | VARCHAR(500) | NULL | | NULL | Các lựa chọn đã chọn — phân cách phẩy |
| `is_disqualifying` | BOOLEAN | NOT NULL | | FALSE | Câu trả lời này gây disqualify |

```sql
CREATE INDEX idx_rc_answer_app   ON rc_application_answers(application_id);
CREATE INDEX idx_rc_answer_disq  ON rc_application_answers(application_id, is_disqualifying)
  WHERE is_disqualifying = TRUE;
```

---

### 6.5 RC_APPLICATION_STAGE_LOG — Audit trail (immutable)

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `application_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → rc_applications.id |
| `stage_id` | UNSIGNED BIGINT | NOT NULL | FK | | FK → rc_pipeline_stages.id |
| `result` | ENUM | NOT NULL | | | passed \| failed \| skipped \| moved_back |
| `note` | TEXT | NULL | | NULL | |
| `actioned_by` | UNSIGNED BIGINT | NOT NULL | FK | | FK → users.id |
| `actioned_at` | TIMESTAMP | NOT NULL | INDEX | NOW() | |

---

### 6.6 RC_INTERVIEW

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `application_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → rc_applications.id |
| `stage_id` | UNSIGNED BIGINT | NOT NULL | FK | | FK → rc_pipeline_stages.id |
| `interview_type` | ENUM | NOT NULL | | `video` | |
| `title` | VARCHAR(200) | NULL | | NULL | |
| `scheduled_at` | TIMESTAMP | NOT NULL | INDEX | | |
| `duration_minutes` | SMALLINT | NOT NULL | | 60 | |
| `location` | VARCHAR(300) | NULL | | NULL | |
| `meeting_url` | TEXT | NULL | | NULL | |
| `meeting_id` | VARCHAR(100) | NULL | | NULL | |
| `status` | ENUM | NOT NULL | INDEX | `scheduled` | |
| `interviewer_note` | TEXT | NULL | | NULL | Note nội bộ |
| `created_by` | UNSIGNED BIGINT | NOT NULL | FK | | FK → users.id |
| `created_at` | TIMESTAMP | NOT NULL | | NOW() | |
| `updated_at` | TIMESTAMP | NOT NULL | | NOW() | |

```sql
CREATE INDEX idx_rc_interview_app      ON rc_interviews(application_id, status);
CREATE INDEX idx_rc_interview_schedule ON rc_interviews(scheduled_at, status)
  WHERE status = 'scheduled';
```

---

### 6.7 RC_INTERVIEW_PANELIST

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `interview_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → rc_interviews.id CASCADE DELETE |
| `user_id` | UNSIGNED BIGINT | NOT NULL | FK | | FK → users.id — user nội bộ |
| `role` | ENUM | NOT NULL | | `interviewer` | |
| `response_status` | ENUM | NOT NULL | | `pending` | |
| `responded_at` | TIMESTAMP | NULL | | NULL | |

```sql
CREATE UNIQUE INDEX idx_rc_panelist_unique ON rc_interview_panelists(interview_id, user_id);
CREATE INDEX idx_rc_panelist_user          ON rc_interview_panelists(user_id, response_status);
```

---

### 6.8 RC_INTERVIEW_EVALUATION

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `interview_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → rc_interviews.id |
| `evaluator_id` | UNSIGNED BIGINT | NOT NULL | FK | | FK → users.id |
| `overall_score` | SMALLINT | NOT NULL | | | 1–10 |
| `strengths` | TEXT | NULL | | NULL | |
| `weaknesses` | TEXT | NULL | | NULL | |
| `recommendation` | TEXT | NULL | | NULL | |
| `verdict` | ENUM | NOT NULL | | | strong_yes \| yes \| neutral \| no \| strong_no |
| `is_submitted` | BOOLEAN | NOT NULL | | FALSE | |
| `submitted_at` | TIMESTAMP | NULL | | NULL | |

```sql
CREATE UNIQUE INDEX idx_rc_eval_unique ON rc_interview_evaluations(interview_id, evaluator_id);
```

---

### 6.9 RC_EVALUATION_CRITERION

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `evaluation_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → rc_interview_evaluations.id CASCADE DELETE |
| `criterion_name` | VARCHAR(100) | NOT NULL | | | "Technical Skills", "Communication" |
| `score` | SMALLINT | NOT NULL | | | 1–10 |
| `comment` | TEXT | NULL | | NULL | |

---

### 6.10 RC_OFFER

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `application_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → rc_applications.id |
| `salary_offered` | DECIMAL(15,2) | NOT NULL | | | |
| `currency` | CHAR(3) | NOT NULL | | `VND` | |
| `start_date` | DATE | NOT NULL | | | |
| `probation_days` | SMALLINT | NOT NULL | | 60 | |
| `benefits_note` | TEXT | NULL | | NULL | |
| `expire_at` | DATE | NULL | | NULL | |
| `status` | ENUM | NOT NULL | INDEX | `draft` | |
| `approved_by` | UNSIGNED BIGINT | NULL | FK | NULL | FK → users.id |
| `approved_at` | TIMESTAMP | NULL | | NULL | |
| `sent_at` | TIMESTAMP | NULL | | NULL | |
| `responded_at` | TIMESTAMP | NULL | | NULL | |
| `rejection_reason` | TEXT | NULL | | NULL | |
| `created_by` | UNSIGNED BIGINT | NOT NULL | FK | | FK → users.id |
| `created_at` | TIMESTAMP | NOT NULL | | NOW() | |
| `updated_at` | TIMESTAMP | NOT NULL | | NOW() | |

```sql
CREATE INDEX idx_rc_offer_app    ON rc_offers(application_id, status);
CREATE INDEX idx_rc_offer_expire ON rc_offers(expire_at, status)
  WHERE expire_at IS NOT NULL AND status = 'sent';
```

---

### 6.11 RC_CANDIDATE_NOTE

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `candidate_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → rc_candidates.id |
| `application_id` | UNSIGNED BIGINT | NULL | FK | NULL | FK → rc_applications.id; NULL = ghi chú chung ứng viên |
| `content` | TEXT | NOT NULL | | | |
| `note_type` | ENUM | NOT NULL | | `general` | general \| interview_note \| concern \| follow_up \| reference_check |
| `is_private` | BOOLEAN | NOT NULL | | FALSE | TRUE = chỉ người tạo và HR Admin |
| `created_by` | UNSIGNED BIGINT | NOT NULL | FK | | FK → users.id |
| `created_at` | TIMESTAMP | NOT NULL | INDEX | NOW() | |

---

### 6.12 RC_CANDIDATE_ATTACHMENT

| Trường | Kiểu | Null | Key | Default | Mô tả |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK | AUTO_INCREMENT | |
| `uuid` | CHAR(36) | NULL | UNIQUE | NULL | Public UUID |
| `candidate_id` | UNSIGNED BIGINT | NOT NULL | FK, INDEX | | FK → rc_candidates.id |
| `application_id` | UNSIGNED BIGINT | NULL | FK | NULL | FK → rc_applications.id |
| `file_type` | ENUM | NOT NULL | | `cv` | cv \| cover_letter \| portfolio \| test_result \| certificate \| other |
| `file_name` | VARCHAR(255) | NOT NULL | | | |
| `file_url` | TEXT | NOT NULL | | | |
| `file_size_kb` | INT | NOT NULL | | | |
| `storage_provider` | VARCHAR(20) | NOT NULL | | `s3` | |
| `storage_key` | VARCHAR(500) | NOT NULL | | | |
| `uploaded_by` | UNSIGNED BIGINT | NOT NULL | FK | | FK → users.id |
| `uploaded_at` | TIMESTAMP | NOT NULL | | NOW() | |

---

## 7. Luồng nghiệp vụ

### 7.1 Tiếp nhận ứng viên

```
[Từ career page / direct]
  Ứng viên apply → POST /api/careers/:org_slug/jobs/:slug/apply
    → Tìm RC_CANDIDATE theo email + org_id (dedup)
    → INSERT RC_APPLICATION (jp_job_post_id = slug's post.uuid, apply_source='career_page')
    → Xử lý JP_SCREENING_QUESTION:
        INSERT RC_APPLICATION_ANSWER per câu hỏi
        Kiểm tra is_disqualifying → nếu có: UPDATE RC_APPLICATION.is_disqualified=TRUE

[Import từ Marketplace]
  HR click "Import" trên MKT_APPLICATION
    → Tìm/tạo RC_CANDIDATE từ mkt_applicant data (mkt_applicant_id = soft ref → mkt_applicants.uuid)
    → INSERT RC_APPLICATION (apply_source='marketplace', mkt_application_id = mkt_application.uuid)

[Thêm thủ công]
  Recruiter thêm tay:
    → INSERT RC_CANDIDATE (source='direct')
    → INSERT RC_APPLICATION
```

### 7.2 Pipeline kanban

```
Recruiter di chuyển ứng viên:
  Validate require_score nếu stage hiện tại cần
  INSERT RC_APPLICATION_STAGE_LOG (immutable)
  UPDATE RC_APPLICATION.current_stage_id = new_stage.id

Nếu stage terminal (hired/rejected):
  UPDATE RC_APPLICATION.status = 'hired'/'rejected'
  Nếu hired: trigger RC_OFFER → handoff
```

### 7.3 Phỏng vấn

```
INSERT RC_INTERVIEW + RC_INTERVIEW_PANELIST (user_id → nội bộ)
Interviewer nộp RC_INTERVIEW_EVALUATION + RC_EVALUATION_CRITERION
Tổng hợp: AVG(overall_score), COUNT verdicts per interview
```

### 7.4 Offer & Handoff

```
RC_OFFER accepted:
  1. Resolve branch_id: department.branch_id → fallback org first active branch
  2. INSERT users nếu chưa có
  3. INSERT employees:
       full_name, email, department_id, job_title_id
       join_date = offer.start_date, salary_base = offer.salary_offered
       probation_end = join_date + probation_days
       status = 'probation'
  4. INSERT employee_history (change_type='hire')
  5. Init leave_balances per active leave_policies
  6. UPDATE RC_APPLICATION.status = 'hired'
  7. UPDATE RC_CANDIDATE.status = 'hired'
  8. App layer: UPDATE jp_job_posts SET hired_count += 1
       WHERE uuid = rc_application.jp_job_post_id
  Idempotent: skip nếu employee email đã tồn tại trong org
```

---

## 8. Query Patterns

### 8.1 Kanban board theo job post

```sql
SELECT
    ps.id, ps.name, ps.sort_order, ps.color_hex,
    COUNT(a.id) AS candidate_count
FROM rc_pipeline_stages ps
LEFT JOIN rc_applications a
       ON a.current_stage_id = ps.id
      AND a.jp_job_post_id = :jp_job_post_uuid
      AND a.status = 'active'
WHERE ps.org_id = :org_id AND ps.is_active = TRUE
GROUP BY ps.id, ps.name, ps.sort_order, ps.color_hex
ORDER BY ps.sort_order;
```

### 8.2 Danh sách ứng viên trong stage

```sql
SELECT
    a.id, a.uuid, a.status, a.apply_source, a.applied_at, a.is_disqualified,
    c.full_name, c.email, c.current_title, c.years_experience,
    u.name AS assigned_recruiter
FROM rc_applications a
JOIN rc_candidates c   ON c.id = a.candidate_id
LEFT JOIN users u      ON u.id = a.assigned_to
WHERE a.jp_job_post_id    = :jp_job_post_uuid
  AND a.current_stage_id  = :stage_id
  AND a.status             = 'active'
ORDER BY a.applied_at DESC;
```

### 8.3 Funnel conversion

```sql
SELECT
    ps.name, ps.sort_order,
    COUNT(DISTINCT sl.application_id)                                     AS total,
    COUNT(DISTINCT sl.application_id) FILTER (WHERE sl.result='passed')   AS passed,
    ROUND(
      COUNT(DISTINCT sl.application_id) FILTER (WHERE sl.result='passed')
      * 100.0 / NULLIF(COUNT(DISTINCT sl.application_id), 0), 1
    )                                                                      AS pass_rate
FROM rc_pipeline_stages ps
LEFT JOIN rc_application_stage_logs sl ON sl.stage_id = ps.id
LEFT JOIN rc_applications a            ON a.id = sl.application_id
  AND a.jp_job_post_id = :jp_job_post_uuid
WHERE ps.org_id = :org_id
GROUP BY ps.id, ps.name, ps.sort_order
ORDER BY ps.sort_order;
```

### 8.4 Time-to-hire

```sql
SELECT
    jp.title AS job_title,
    jp.code  AS job_code,
    COUNT(o.id)                                                   AS hired,
    ROUND(AVG(EXTRACT(DAY FROM o.responded_at - a.applied_at)), 1) AS avg_days_to_hire
FROM rc_offers o
JOIN rc_applications a ON a.id = o.application_id
JOIN jp_job_posts jp   ON jp.uuid = a.jp_job_post_id
WHERE a.org_id    = :org_id
  AND o.status    = 'accepted'
  AND o.responded_at BETWEEN :start AND :end
GROUP BY jp.id, jp.title, jp.code
ORDER BY avg_days_to_hire;
```

---

## 9. API Endpoints

### Pipeline

| Method | Endpoint | Mô tả |
|---|---|---|
| GET | `/api/recruitment/pipeline-stages` | Danh sách stages |
| POST | `/api/recruitment/pipeline-stages` | Tạo stage |
| PUT | `/api/recruitment/pipeline-stages/reorder` | Sắp xếp |

### Applications & Candidates

| Method | Endpoint | Mô tả |
|---|---|---|
| GET | `/api/recruitment/jobs/:jpPostUuid/board` | Kanban board |
| GET | `/api/recruitment/jobs/:jpPostUuid/applications` | Danh sách đơn |
| POST | `/api/recruitment/candidates` | Thêm candidate thủ công |
| POST | `/api/recruitment/candidates/import-from-marketplace` | Import từ MKT |
| GET | `/api/recruitment/candidates/:id` | Hồ sơ + lịch sử |
| POST | `/api/recruitment/applications/:id/move` | Chuyển stage |
| POST | `/api/recruitment/applications/:id/reject` | Từ chối |
| PATCH | `/api/recruitment/applications/:id/assign` | Gán recruiter |
| GET | `/api/recruitment/applications/:id/answers` | Xem screening answers |

### Interviews

| Method | Endpoint | Mô tả |
|---|---|---|
| POST | `/api/recruitment/applications/:id/interviews` | Tạo lịch |
| GET | `/api/recruitment/interviews/my-schedule` | Lịch của tôi |
| POST | `/api/recruitment/interviews/:id/evaluations` | Nộp đánh giá |
| GET | `/api/recruitment/interviews/:id/evaluations` | Tổng hợp đánh giá |

### Offers

| Method | Endpoint | Mô tả |
|---|---|---|
| POST | `/api/recruitment/applications/:id/offers` | Tạo offer |
| POST | `/api/recruitment/offers/:id/approve` | Duyệt |
| POST | `/api/recruitment/offers/:id/send` | Gửi ứng viên |
| POST | `/api/recruitment/offers/:id/accept` | Accept → trigger handoff |
| POST | `/api/recruitment/offers/:id/reject` | Từ chối |

### Analytics

| Method | Endpoint | Mô tả |
|---|---|---|
| GET | `/api/recruitment/analytics/funnel?jp_job_post_uuid=` | Funnel per tin |
| GET | `/api/recruitment/analytics/time-to-hire` | Thời gian trung bình |
| GET | `/api/recruitment/analytics/source` | Source effectiveness |
| GET | `/api/recruitment/analytics/overview` | Tổng quan |

---

## 10. Business Rules

### BR-RC-001: Candidate dedup
- Email unique per org — không tạo 2 candidate cùng email
- Import MKT: tìm theo email → reuse nếu có, tạo mới nếu chưa
- 1 candidate không apply cùng 1 jp_job_post (CHAR(36) uuid) 2 lần (unique index, trừ `withdrawn`)

### BR-RC-002: Screening disqualify
- Khi apply: hệ thống tự kiểm tra `is_disqualifying` trên JP_SCREENING_QUESTION
- Nếu có câu trả lời gây disqualify → `RC_APPLICATION.is_disqualified = TRUE`
- Disqualified applications vẫn hiển thị để HR review — không hard reject tự động

### BR-RC-003: Pipeline movement
- Không skip nhiều stage cùng lúc
- `require_score = TRUE`: phải có ít nhất 1 evaluation submitted
- Stage `hired`/`rejected`: terminal
- `RC_APPLICATION_STAGE_LOG`: immutable

### BR-RC-004: Interview
- Panelist phải là user nội bộ của org
- Mỗi panelist 1 evaluation per interview
- Không tạo interview khi application `rejected`/`withdrawn`

### BR-RC-005: Offer
- 1 offer active per application
- Expire cron: quét `expire_at < NOW()` và `status='sent'`
- Sau `accepted`: application locked, trigger handoff

### BR-RC-006: Handoff idempotent
- Skip nếu employee với email này đã tồn tại trong org
- Bắt buộc có `jp_job_post_id` trước khi handoff (nhắc HR nếu null — cần biết vị trí tuyển)

---

## 11. Indexes & Caching

```sql
-- Kanban board (hot path)
CREATE INDEX idx_rc_app_board
  ON rc_applications(jp_job_post_id, current_stage_id, status)
  WHERE jp_job_post_id IS NOT NULL AND status = 'active';

-- Candidate search
CREATE INDEX idx_rc_cand_active
  ON rc_candidates(org_id, status, created_at DESC);

-- Offer expiry
CREATE INDEX idx_rc_offer_expiry
  ON rc_offers(expire_at, status)
  WHERE status = 'sent' AND expire_at IS NOT NULL;

-- My interview schedule
CREATE INDEX idx_rc_panelist_schedule
  ON rc_interview_panelists(user_id, response_status);

-- Disqualified — HR review queue
CREATE INDEX idx_rc_app_disqualified
  ON rc_applications(org_id, is_disqualified, applied_at DESC)
  WHERE is_disqualified = TRUE AND status = 'active';
```

### Caching

| Cache key | TTL | Invalidate khi |
|---|---|---|
| `recruitment:org:{id}:pipeline` | 30 phút | Thêm/sửa stage |
| `recruitment:job:{jp_post_uuid}:board-counts` | 2 phút | Move application |
| `recruitment:analytics:{id}:{month}` | 1 giờ | Offer accepted |

---

## 12. Lộ trình triển khai

### Phase 1 — Pipeline & Core (tuần 1–2)
- [ ] Migration: `rc_pipeline_stages`, `rc_candidates`, `rc_applications`, `rc_application_stage_logs`
- [ ] Seed pipeline stages mặc định per org
- [ ] Tiếp nhận ứng viên thủ công + từ career page (apply với jp_job_post_id = jp_job_posts.uuid)
- [ ] Kanban board: hiển thị theo jp_job_post_uuid

### Phase 2 — Screening & Import (tuần 3)
- [ ] Migration: `rc_application_answers`
- [ ] Xử lý screening answers khi apply (check disqualifying)
- [ ] Import từ Marketplace (mkt_applicant_id soft ref → mkt_applicants.uuid)
- [ ] Notes + attachments

### Phase 3 — Interview & Evaluation (tuần 4–5)
- [ ] Migration: `rc_interviews`, `rc_interview_panelists`, `rc_interview_evaluations`, `rc_evaluation_criteria`
- [ ] Lịch phỏng vấn + panel assignment
- [ ] View "Lịch của tôi"
- [ ] Đánh giá + tổng hợp verdict

### Phase 4 — Offer & Handoff (tuần 6–7)
- [ ] Migration: `rc_offers`
- [ ] Luồng offer đầy đủ + expiry cron
- [ ] HandoffAction → INSERT employees + employee_history + leave_balances
- [ ] Analytics: funnel, time-to-hire, source

---

*Version 3.1.0 — Recruitment Center (ATS thuần túy)*
*Thay đổi v3.1: (1) Sửa tất cả id UUID PK → BIGINT PK + uuid CHAR(36) riêng biệt; (2) Sửa FK nội bộ rc_* → BIGINT thay vì UUID; (3) Cross-module soft ref (jp_job_post_id, mkt_application_id, mkt_applicant_id) dùng CHAR(36) tham chiếu .uuid của bảng đích; (4) Chuẩn hóa query dùng jp_job_posts.uuid thay vì .id trong join*
*Thay đổi v3.0: Loại bỏ hoàn toàn RC_HIRING_REQUEST; thêm RC_APPLICATION_ANSWER (screening); jp_job_post_id soft ref trên RC_APPLICATION; kanban group theo jp_job_post_id thay vì hiring_request_id*
