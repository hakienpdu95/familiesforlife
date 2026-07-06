# Đặc tả Kỹ thuật: Competency Passport Platform
## Nền tảng Chứng nhận Năng lực Số — Hồ sơ Nghề nghiệp Xuyên Tổ chức

**Phiên bản:** 2.0  
**Ngày:** 13/06/2026  
**Trạng thái:** Baseline Specification — Đã đối chiếu hệ thống hiện tại  
**Phân loại:** Tài liệu Kiến trúc & Sản phẩm Nội bộ

---

## Mục lục

1. [Tầm nhìn & Định vị sản phẩm](#1-tầm-nhìn--định-vị-sản-phẩm)
2. [Kiểm kê hệ thống hiện tại](#2-kiểm-kê-hệ-thống-hiện-tại)
3. [Phân tích Gap & Chiến lược mở rộng](#3-phân-tích-gap--chiến-lược-mở-rộng)
4. [Kiến trúc tổng thể](#4-kiến-trúc-tổng-thể)
5. [Vòng đời tài khoản — Career Journal](#5-vòng-đời-tài-khoản--career-journal)
   - 5.1 [Trạng thái tài khoản](#51-trạng-thái-tài-khoản)
   - 5.2 [Quy tắc Email cá nhân](#52-quy-tắc-email-cá-nhân--enforce-at-input)
   - 5.3 [Sự kiện vòng đời](#53-sự-kiện-trong-vòng-đời-và-dữ-liệu-ghi-nhận)
   - 5.4 [Career Journal flow](#54-career-journal-flow--từ-góc-nhìn-cá-nhân)
   - 5.5 [Tài khoản do Org tạo](#55-tài-khoản-do-org-tạo--luồng-đơn-giản-nhất-quán)
   - 5.6 [Offboarding muộn — HR quên xác nhận](#56-offboarding-muộn--hr-quên-xác-nhận-nhân-viên-đã-nghỉ)
6. [Phase 0 — Identity Foundation](#6-phase-0--identity-foundation)
7. [Phase 1 — Competency Passport](#7-phase-1--competency-passport)
8. [Phase 2 — Portability & Sharing](#8-phase-2--portability--sharing)
9. [Phase 3 — eKYC Verified Identity](#9-phase-3--ekyc-verified-identity)
10. [Phase 4 — Open Assessment Marketplace](#10-phase-4--open-assessment-marketplace)
11. [Phase 5 — National Platform & Open API](#11-phase-5--national-platform--open-api)
12. [Schema Master — Tất cả bảng mới](#12-schema-master--tất-cả-bảng-mới)
13. [Nguyên tắc thiết kế & Ràng buộc](#13-nguyên-tắc-thiết-kế--ràng-buộc)
14. [Lộ trình & Milestone](#14-lộ-trình--milestone)

---

## 1. Tầm nhìn & Định vị sản phẩm

### 1.1 Bài toán

Năng lực số của một người được xây dựng qua **nhiều tổ chức, nhiều năm**. Hệ thống hiện tại lưu mọi thứ trong namespace của từng org — khi nhân viên rời đi, toàn bộ lịch sử bị "khóa" lại. Người lao động không có bằng chứng mang theo; tổ chức mới không có cơ sở đánh giá.

### 1.2 Tầm nhìn 5 năm

```
Năm 1   →  HRM nội bộ: quản lý năng lực nhân viên trong org
Năm 2   →  Personal Passport: hồ sơ cá nhân portable, tích lũy qua các org
Năm 3   →  Verified Identity: danh tính nghề nghiệp xác minh nhiều lớp
Năm 4   →  Talent Marketplace: kết nối ứng viên ↔ tổ chức qua năng lực thực
Năm 5   →  National Competency Index: chuẩn quốc gia về năng lực số
```

### 1.3 Metaphor thiết kế: Career Journal (Nhật ký Nghề nghiệp)

Mỗi giai đoạn làm việc tại một tổ chức là **một chương** trong nhật ký nghề nghiệp. Khi chương kết thúc, nội dung được đóng lại thành bản bất biến — không ai có thể sửa, không bị xóa khi rời org. Cá nhân tích lũy các chương qua thời gian, nhà tuyển dụng có thể đọc từng chương đã được tổ chức xác nhận.

```
[Nhật ký nghề nghiệp của Nguyễn Văn A]
│
├── Chương 1 · Công ty ABC · 2022–2023 · Nhân viên Sales
│   Điểm TDWCF: 54 → 62 · Certs: AI_SALES Foundation · 12h Sandbox
│   ✓ Xác nhận bởi Công ty ABC
│
├── Chương 2 · Công ty XYZ · 2024–2026 · Chuyên viên Sales
│   Điểm TDWCF: 63 → 74 · Certs: AI_SALES Practitioner · 28h Sandbox
│   ✓ Xác nhận bởi Công ty XYZ
│
└── [Đang viết] · Công ty MNO · 2026–nay ...
```

---

## 2. Kiểm kê hệ thống hiện tại

> Đây là nền tảng đã có — đặc tả này **mở rộng bên trên**, không sửa đổi.

### 2.1 Lớp Identity & Membership

| Bảng | Vai trò | Ghi chú |
|---|---|---|
| `users` | Tài khoản người dùng | `id, name, email, email_verified_at, password` — rất đơn giản |
| `organization_members` | Thành viên org | `org_id, user_id, role, joined_at` — **thiếu left_at, status, exit tracking** |

### 2.2 Lớp Workforce Profile (org-scoped, TenantAwareModel)

| Bảng | Vai trò | Điểm mạnh |
|---|---|---|
| `workforce_profiles` | Hồ sơ năng lực chính | 25 cột điểm: D1–D6, trust, ai_readiness, sandbox, cert, kpi, impact |
| `workforce_profile_histories` | **Event log / diary** | event_type, source_id/type (polymorphic), before/after score, delta, recorded_at |
| `workforce_portfolios` | Portfolio sản phẩm | item_type, approval workflow, link to KnowledgeCenter |
| `workforce_certifications` | Chứng nhận đã đạt | composite scoring, certificate_number, qr_code_url, digital_badge_url |
| `workforce_recommendations` | Gợi ý AI | **Có JSON field** — cần normalize ở Phase 0 |
| `matching_results` | Matching ứng viên ↔ JD | 5 chiều: competency, cert, experience, ai_readiness, career_goal |

### 2.3 Lớp Assessment Engine

| Bảng | Vai trò |
|---|---|
| `assessments` | Định nghĩa bài khảo sát |
| `assessment_domains` | 6 domain D1–D6 |
| `assessment_results` | Kết quả khảo sát (polymorphic subject) |
| `result_domain_scores` | Điểm từng domain |
| `result_question_scores` | Điểm từng câu hỏi |
| `maturity_levels` | Digital Beginner → Digital Leader |
| `score_rules`, `score_bands` | Quy tắc tính điểm |
| `personas`, `persona_conditions` | Phân nhóm người dùng |
| `recommendation_rules`, `roadmap_phases` | Luật sinh gợi ý + lộ trình |

### 2.4 Lớp Sandbox

| Bảng | Vai trò |
|---|---|
| `sandbox_environments` | 6 môi trường (global/org) |
| `sandbox_tasks` | Nhiệm vụ thực hành, scoring_rubric |
| `sandbox_sessions` | Phiên thực hành: quality/productivity/ai_adoption score |
| `sandbox_submissions` | Bài nộp |
| `sandbox_activities` | Activity log chi tiết trong session |

### 2.5 Lớp Certification

| Bảng | Vai trò |
|---|---|
| `certification_definitions` | 28 templates cert (global/org), yêu cầu đầu vào |
| `workforce_certifications` | Cert đã cấp, composite score, validity |

### 2.6 Lớp Recruitment (RC)

| Bảng | Vai trò | Ghi chú |
|---|---|---|
| `rc_candidates` | Ứng viên tuyển dụng | **Org-scoped, NOT linked to users** — chỉ lưu email/name thô |
| `rc_applications` | Đơn ứng tuyển | Có JSON `answers` — tồn tại nhưng nằm ngoài scope spec này |

### 2.7 Lớp AI & Config

| Bảng | Vai trò |
|---|---|
| `ai_agents` | Cấu hình AI model (Claude) |
| `career_pathway_steps` | Lộ trình từ level này → level khác |
| `job_title_domain_requirements` | Yêu cầu năng lực theo chức danh |
| `ai_impact_snapshots` | Ghi nhận tác động AI: baseline → achieved, ROI |

---

## 3. Phân tích Gap & Chiến lược mở rộng

### 3.1 Gap map

```
CẦN                                       HIỆN CÓ                   TRẠNG THÁI
──────────────────────────────────────────────────────────────────────────────
Email cá nhân là định danh bắt buộc       —                          ⚠ Cần enforce
Tài khoản cá nhân độc lập với org         users (cơ bản)             ⚠ Thiếu account_type
Theo dõi lịch sử vào/ra org              organization_members        ⚠ Thiếu left_at, status
Xác minh email                            email_verified_at           ✓ Có sẵn
Xác minh phone/CCCD                       —                           ✗ Chưa có
Event log thay đổi điểm                   workforce_profile_histories ✓ Có sẵn, tốt
Snapshot bất biến khi rời org             —                           ✗ Chưa có
Hồ sơ cá nhân xuyên org                  —                           ✗ Chưa có
Chia sẻ hồ sơ với nhà tuyển dụng         —                           ✗ Chưa có
Open Assessment Campaign                  —                           ✗ Chưa có
Normalize JSON recommendations            workforce_recommendations   ⚠ Cần tách ra
Kết nối rc_candidates → users             —                           ⚠ Gap tuyển dụng
```

### 3.2 Nguyên tắc định danh — Personal Email as Identity Anchor

> **Quy tắc bất biến:** `users.email` **luôn luôn là email cá nhân** của người dùng — không bao giờ là email tổ chức. Tài khoản thuộc về người, không thuộc về tổ chức.

Nguyên tắc này được áp dụng nhất quán trong tất cả luồng:

| Luồng | Cách áp dụng |
|---|---|
| **Người dùng tự đăng ký** | Nhập email cá nhân → verify → tài khoản thuộc về họ mãi mãi |
| **Org tạo tài khoản cho nhân viên** | HR nhập email cá nhân của nhân viên (bắt buộc) — hệ thống cảnh báo nếu domain trùng domain org |
| **Nhân viên rời org** | Snapshot → `account_type = 'free'` — không cần handover vì email luôn là cá nhân |
| **Nhân viên vào org mới** | `account_type = 'org_member'` — Career Journal tự động tích lũy chương mới |

Nguyên tắc này loại bỏ hoàn toàn sự phức tạp của "company email", "email handover", "pending_claim", "orphaned" — toàn bộ vòng đời chỉ còn: `free ↔ org_member`.

### 3.3 Chiến lược kỹ thuật

- **Enforce email cá nhân tại điểm nhập liệu:** Validate + warn khi HR nhập email có domain trùng org
- **Migration existing data:** Identify users có email domain trùng org → thông báo cập nhật
- **Additive only:** Không đổi schema bảng hiện có — chỉ thêm cột mới qua migration riêng
- **Zero JSON trong bảng mới:** Tất cả dữ liệu dạng list/array chuẩn hóa thành bảng con
- **workforce_profile_histories là trái tim:** Career diary đã có — Passport chỉ "đóng bìa" từng chương khi kết thúc
- **uuid trên mọi bảng mới:** Cho phép public URL và API mà không expose PK

---

## 4. Kiến trúc tổng thể

### 4.1 Phân tầng dữ liệu

```
┌─────────────────────────────────────────────────────────────────────┐
│  PERSONAL LAYER — user_id scope, không qua TenantContext            │
│                                                                     │
│  users (extended)  ·  org_memberships (extended)                   │
│  identity_verifications                                             │
│  passport_entries  ·  passport_domain_scores                       │
│  passport_certifications  ·  passport_impact_highlights            │
│  campaign_participations  ·  campaign_participation_scores         │
└────────────────────────┬────────────────────────────────────────────┘
                         │ Snapshot khi rời org
┌────────────────────────▼────────────────────────────────────────────┐
│  ORG WORKSPACE LAYER — organization_id scope, TenantAwareModel      │
│                                                                     │
│  workforce_profiles  ·  workforce_profile_histories ← career diary │
│  workforce_portfolios  ·  workforce_certifications                 │
│  sandbox_sessions  ·  ai_impact_snapshots                          │
│  workforce_recommendations → recommendation_items (mới)            │
└────────────────────────┬────────────────────────────────────────────┘
                         │ Dùng chung template
┌────────────────────────▼────────────────────────────────────────────┐
│  GLOBAL CONFIG LAYER — organization_id nullable (null = global)     │
│                                                                     │
│  assessment_domains  ·  certification_definitions                  │
│  sandbox_environments  ·  sandbox_tasks                            │
│  career_pathway_steps  ·  maturity_levels                          │
│  open_assessment_campaigns (Phase 4)                               │
└─────────────────────────────────────────────────────────────────────┘
```

### 4.2 Quan hệ dữ liệu cốt lõi

```
users ──────────────────────────────────────────────────────────────┐
  │                                                                  │
  ├─[1:N]─► org_memberships ──► organizations                       │
  │          (lịch sử vào/ra — extended)                            │
  │                                                                  │
  ├─[1:N]─► identity_verifications                                  │
  │          (email/phone/cccd — Phase 0 & 3)                       │
  │                                                                  │
  ├─[1:N]─► passport_entries  ◄── Snapshot từ org hoặc campaign    │
  │           │                                                      │
  │           ├─[1:6]─► passport_domain_scores                      │
  │           ├─[1:N]─► passport_certifications                     │
  │           └─[1:N]─► passport_impact_highlights                  │
  │                                                                  │
  └─[1:N]─► campaign_participations (Phase 4)                       │
              │                                                      │
              └─[1:N]─► campaign_participation_scores               │
                                                                     │
users ──► workforce_profiles (qua org_member context) ─────────────┘
            │
            ├─[1:N]─► workforce_profile_histories  ← EVENT DIARY
            ├─[1:N]─► workforce_portfolios
            ├─[1:N]─► workforce_certifications
            ├─[1:N]─► sandbox_sessions
            ├─[1:N]─► ai_impact_snapshots
            └─[1:1]─► workforce_recommendations
                          └─[1:N]─► workforce_recommendation_items (mới)
```

---

## 5. Vòng đời tài khoản — Career Journal

### 5.1 State machine — Đơn giản và nhất quán

Vì email luôn là cá nhân, vòng đời tài khoản chỉ còn 3 trạng thái chính:

```
  [Tự đăng ký]              [Org tạo tài khoản]
  email cá nhân             HR nhập email cá nhân
  của người dùng            của nhân viên (bắt buộc)
       │                           │
       ▼                           ▼
  ┌──────────────────────────────────────────┐
  │                  FREE                    │
  │  email cá nhân · trust_level ≥ 1        │
  │  Xem Passport · Tải PDF · Chia sẻ link  │
  └────────────────────┬─────────────────────┘
                       │
          HR gán vào org (invite / tạo mới)
                       │
                       ▼
  ┌──────────────────────────────────────────┐
  │             ORG_MEMBER (active)          │
  │  Workspace org · Khảo sát · Sandbox     │
  │  Cert · AI Impact · Career Pathway      │
  └──────┬───────────────────────────────────┘
         │
         ├── deactivate tạm ──► ORG_MEMBER (paused) ──► reactivate ──┐
         │                                                             │
         │◄────────────────────────────────────────────────────────────┘
         │
         │  HR offboard / nhân viên nghỉ
         │  ① SnapshotJob → passport_entries (chương mới)
         │  ② org_memberships.status = 'inactive'
         │  ③ users.account_type = 'free'
         │  ④ Email thông báo đến email cá nhân
         │
         ▼
  ┌──────────────────────────────────────────┐
  │                  FREE                    │
  │  Thấy Passport + chương vừa đóng        │
  │  Sẵn sàng vào org mới                   │
  └──────────────────────────────────────────┘

  [Bất kỳ trạng thái] ──► vi phạm ──► SUSPENDED
```

**Bảng trạng thái `account_type`:**

| Giá trị | Mô tả | Đăng nhập | Passport | Org workspace |
|---|---|---|---|---|
| `free` | Cá nhân tự do | ✓ | ✓ Full | ✗ |
| `org_member` | Đang thuộc org | ✓ | ✓ Full | ✓ (theo role) |
| `suspended` | Bị khóa | ✗ | ✗ | ✗ |

### 5.2 Quy tắc Email — Enforce tại mọi điểm nhập liệu

**Quy tắc:** `users.email` chỉ nhận email cá nhân. Hệ thống **cảnh báo cứng** nếu domain email trùng domain org.

```
Khi HR tạo tài khoản cho nhân viên:
  Input: nguyen.a@company.com  →  ⚠ CẢNH BÁO
  "Email này thuộc domain tổ chức (@company.com).
   Vui lòng nhập email cá nhân của nhân viên (Gmail, Yahoo, Outlook cá nhân...)
   để đảm bảo họ có thể truy cập Passport sau khi rời tổ chức."
   [Vẫn tiếp tục — tôi hiểu rủi ro]  [Nhập lại email]

  Input: nguyen.van.a@gmail.com  →  ✓ Hợp lệ
```

**Phát hiện domain org:** Lấy từ `organizations.domain` hoặc từ email của owner tổ chức. So sánh suffix domain khi HR nhập.

**Trường hợp nhân viên chưa có email cá nhân:**
> HR hướng dẫn nhân viên tạo email cá nhân (Gmail miễn phí) trước khi onboard. Đây là bước onboarding, không phải vấn đề hệ thống.

### 5.3 Sự kiện trong vòng đời và dữ liệu ghi nhận

| Sự kiện | Trigger | Dữ liệu ghi nhận |
|---|---|---|
| Tự đăng ký | User | `users` row (email cá nhân), gửi email verify |
| Org tạo tài khoản | HR nhập email cá nhân NV | `users` row, `account_was_org_created=true` trong `org_memberships` |
| Xác minh email | Click link | `users.email_verified_at`, `trust_level=1`, `identity_verifications` row |
| Gán vào org | HR invite/accept | `org_memberships` row (status=active, joined_at), `users.account_type='org_member'` |
| Làm khảo sát | User | `assessment_results`, `result_domain_scores`, cập nhật `workforce_profiles`, ghi `workforce_profile_histories` |
| Đạt cert | Admin cấp | `workforce_certifications` row, ghi `workforce_profile_histories` |
| Hoàn thành Sandbox | User | `sandbox_sessions` completed, ghi `workforce_profile_histories` |
| Ghi AI Impact | User | `ai_impact_snapshots` row, ghi `workforce_profile_histories` |
| Nghỉ việc / HR offboard | HR deactivate | `org_memberships` inactive → SnapshotJob → `passport_entries` + children → `users.account_type='free'` → email thông báo |
| Xác minh phone | OTP | `identity_verifications`, `users.trust_level=2` |
| Xác minh CCCD | Upload/scan | `identity_verifications`, `users.trust_level=3`, `national_id_hash` |
| Đổi email cá nhân | User | Verify email mới → swap, `identity_verifications` row mới |
| Tham gia campaign | User | `campaign_participations` row |
| Bị mời phỏng vấn | HR org | `campaign_participations.org_action='invited'` |

### 5.3 Career Journal flow — từ góc nhìn cá nhân

```
[Ngày 1] Đăng ký · Xác minh email
    └── identity_verifications: {method: 'email', status: 'verified'}
    └── users: {trust_level: 1}

[Ngày 7] Vào Công ty ABC (HR invite)
    └── org_memberships: {org_id: 1, joined_at: X, status: 'active', role: 'member'}
    └── workforce_profiles: {org_id: 1, user_id: X} — record mới, trống

[Tháng 1] Làm khảo sát TDWCF lần đầu
    └── assessment_results: {subject = workforce_profile}
    └── result_domain_scores: D1=45, D2=38, D3=28, D4=52, D5=41, D6=60
    └── workforce_profiles: {tdwcf_score: 44.3, maturity: 'DIGITAL_AWARE'}
    └── workforce_profile_histories: {event: 'assessment', delta: +44.3}  ← DIARY

[Tháng 2] Hoàn thành Sandbox AI_SALES Foundation
    └── sandbox_sessions: {status: 'completed', final_score: 78}
    └── workforce_profiles: {sandbox_hours_total: 3, sandbox_score_avg: 78}
    └── workforce_profile_histories: {event: 'sandbox', delta: +2.1}  ← DIARY

[Tháng 4] Đạt AI_SALES Foundation Cert
    └── workforce_certifications: {cert_code: 'AI_SALES_FOUNDATION', status: 'active'}
    └── workforce_profiles: {certifications_count: 1, highest_cert_level: 'FOUNDATION'}
    └── workforce_profile_histories: {event: 'certification', delta: +5.0}  ← DIARY

[Tháng 12] Nghỉ việc khỏi Công ty ABC
    └── org_memberships: {left_at: Y, status: 'inactive', exit_reason: 'resigned'}
    └── [JOB] SnapshotJob dispatch →
        └── passport_entries: {source_org_id: 1, tdwcf_score: 62, ...}  ← CHƯƠNG 1
        └── passport_domain_scores: 6 rows (D1-D6 tại thời điểm exit)
        └── passport_certifications: 1 row (AI_SALES_FOUNDATION)
        └── passport_impact_highlights: top 5 impacts
    └── users: {account_type: 'free', current_org_id: null}

[Ngày hôm nay] Đăng nhập với tài khoản Free
    └── Thấy Personal Dashboard
    └── Xem Chương 1 — Công ty ABC
    └── Tải PDF hoặc chia sẻ link
```

### 5.5 Tài khoản do Org tạo — Luồng đơn giản, nhất quán

Vì quy tắc đã rõ ràng (email luôn là cá nhân), luồng này hoàn toàn tương đương với "người dùng tự đăng ký":

```
[HR tạo tài khoản cho nhân viên mới]
  HR nhập: full_name, personal_email (bắt buộc là email cá nhân)
  Hệ thống:
    ① users.insert({name, email: personal_email, account_type: 'org_member'})
    ② org_memberships.insert({account_was_org_created: true, joined_at: now()})
    ③ Gửi email kích hoạt → nhân viên đặt mật khẩu lần đầu
    ④ Gán role phù hợp (member / ops / manager)

[Nhân viên nhận email, đặt mật khẩu, đăng nhập]
  → Xác minh email → trust_level = 1
  → Vào thẳng org workspace
  → Làm khảo sát TDWCF, Sandbox, v.v.

[Tháng 12 — Nghỉ việc]
  HR offboard → OrgMembershipService::deactivate()
  ① SnapshotJob → passport_entries (chương đóng lại)
  ② org_memberships.status = 'inactive'
  ③ users.account_type = 'free'
  ④ Email đến personal_email: "Passport đã được lưu, đăng nhập để xem"

[Nhân viên đăng nhập lại với email cá nhân của mình]
  → Thấy Personal Dashboard + Chương 1 đã đóng
  → Sẵn sàng vào org mới hoặc tham gia Open Assessment
```

**Không có gì đặc biệt cần xử lý** — vì email luôn là cá nhân từ đầu.

#### Validation khi HR tạo tài khoản

```php
// Trong CreateEmployeeAccountRequest
public function rules(): array
{
    return [
        'name'  => ['required', 'string', 'max:200'],
        'email' => [
            'required',
            'email',
            'unique:users,email',
            new NotOrgDomainEmail($this->organization), // Custom rule
        ],
    ];
}

// App\Rules\NotOrgDomainEmail
class NotOrgDomainEmail implements ValidationRule
{
    public function validate(string $attr, mixed $value, Closure $fail): void
    {
        $orgDomain = $this->organization->email_domain; // VD: 'company.com'
        if (!$orgDomain) return;

        $inputDomain = Str::after($value, '@');
        if (strtolower($inputDomain) === strtolower($orgDomain)) {
            $fail("Email @{$orgDomain} thuộc tổ chức. Vui lòng nhập email cá nhân của nhân viên.");
        }
    }
}
```

> **`organizations.email_domain`** — thêm cột này vào bảng `organizations` (nếu chưa có) để so sánh domain.

#### Migration Plan — Dữ liệu hiện tại

Nếu hệ thống đang có users với email công ty (trước khi enforce rule này):

```sql
-- Identify accounts cần migrate
SELECT u.id, u.email, o.name as org_name
FROM users u
JOIN organization_members om ON om.user_id = u.id AND om.status = 'active'
JOIN organizations o ON o.id = om.organization_id
WHERE u.email LIKE CONCAT('%@', o.email_domain)
  AND o.email_domain IS NOT NULL;
```

**Xử lý:** Gửi email thông báo đến từng người, yêu cầu cập nhật email cá nhân trong vòng 30 ngày. Có UI trong profile để đổi email (verify email mới trước khi swap).

#### Acceptance Criteria

- [ ] HR không thể tạo tài khoản với email có domain trùng org (validation error rõ ràng)
- [ ] Email kích hoạt gửi đúng, nhân viên đặt được mật khẩu lần đầu
- [ ] Offboarding: snapshot + free trong 1 transaction, email thông báo gửi ngay
- [ ] Nhân viên sau khi rời org đăng nhập bình thường với email cá nhân, thấy Passport đầy đủ
- [ ] Migration script identify được accounts cần cập nhật email

### 5.6 Offboarding muộn — HR quên xác nhận nhân viên đã nghỉ

#### Bài toán

```
Tháng 6/2026: Nhân viên Nguyễn Văn A nghỉ việc
Tháng 10/2026: HR mới phát hiện và click "Offboard"

Trong 4 tháng gap đó:
  ① Tài khoản vẫn có role 'member' → vào được sandbox, xem được dữ liệu org
  ② Nếu NVA vẫn dùng tài khoản → đây là truy cập bất hợp pháp
  ③ Snapshot Passport vào tháng 10 sẽ chứa dữ liệu sandbox/cert của tháng 7-10 → không hợp lệ
  ④ Không có cách biết: NVA có thực sự login trong khoảng đó không?
```

#### Giải pháp 3 lớp

**Lớp 1 — Phòng ngừa: `contract_end_date` + Auto-suspend**

Thêm `contract_end_date` vào `organization_members`. Khi đến ngày này, hệ thống tự suspend:

```sql
-- Thêm vào migration organization_members
ADD COLUMN contract_end_date    DATE         NULL
    COMMENT 'Ngày hết hợp đồng — hệ thống auto-suspend nếu HR không offboard trước',
ADD COLUMN auto_suspended_at    TIMESTAMP    NULL
    COMMENT 'Thời điểm hệ thống tự suspend do hết contract_end_date',
ADD COLUMN last_active_at       TIMESTAMP    NULL
    COMMENT 'Lần cuối user có activity trong org này (login + action)';
```

Job `AutoSuspendExpiredMembershipsJob` — chạy hàng ngày lúc 01:00:

```php
// Tìm members đã quá contract_end_date nhưng vẫn active
OrganizationMember::where('status', 'active')
    ->whereNotNull('contract_end_date')
    ->where('contract_end_date', '<', today())
    ->chunkById(100, function ($members) {
        foreach ($members as $member) {
            DB::transaction(function () use ($member) {
                // Suspend ngay — revoke role, block access
                $member->update([
                    'status'           => 'suspended',
                    'auto_suspended_at'=> now(),
                ]);
                // Revoke tất cả Spatie roles trong org scope
                $member->user->removeRole($member->role);
            });
            // Gửi cảnh báo cho HR
            Notification::send($member->organization->hrAdmins(),
                new MemberAutoSuspendedNotification($member));
        }
    });
```

Trạng thái `suspended` khác với `inactive`:
- `suspended` = tạm khóa do hết hợp đồng, chờ HR xác nhận → HR có thể "gia hạn" hoặc "offboard chính thức"
- `inactive` = đã offboard chính thức, Passport đã snapshot

**Lớp 2 — Phát hiện: Inactivity Monitoring**

Job `FlagInactiveMembersJob` — chạy hàng tuần:

```php
// Members active nhưng không có activity 45 ngày qua
$threshold = now()->subDays(45);

OrganizationMember::where('status', 'active')
    ->where(function ($q) use ($threshold) {
        $q->whereNull('last_active_at')
          ->orWhere('last_active_at', '<', $threshold);
    })
    ->with('user', 'organization')
    ->chunkById(200, function ($members) {
        // Gửi báo cáo tổng hợp cho HR admin (1 email/org, không spam từng người)
        $byOrg = $members->groupBy('organization_id');
        foreach ($byOrg as $orgId => $orgMembers) {
            Notification::send(
                $orgMembers->first()->organization->hrAdmins(),
                new InactiveMembersReportNotification($orgMembers)
            );
        }
    });
```

Email HR nhận:
```
Báo cáo thành viên không hoạt động — Công ty ABC
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
3 thành viên không có hoạt động > 45 ngày:

  • Nguyễn Văn A — lần cuối: 2026-06-01 (134 ngày trước)  [Offboard] [Gia hạn]
  • Trần Thị B  — lần cuối: 2026-08-15 (60 ngày trước)    [Offboard] [Gia hạn]
  • ...

→ Xem chi tiết tại: [link HR dashboard]
```

**Lớp 3 — Xử lý retroactive: Late Offboarding với `effective_left_at`**

Khi HR offboard muộn, form cho phép nhập ngày thực tế nhân viên nghỉ:

```
┌─────────────────────────────────────────────────────────────┐
│  Xác nhận nghỉ việc — Nguyễn Văn A                         │
│                                                             │
│  Ngày nghỉ việc thực tế: [01/06/2026 ▾]                   │
│  ┌────────────────────────────────────────────────────┐    │
│  │ ⚠️  Phát hiện gap 134 ngày (01/06 → 13/10/2026)   │    │
│  │                                                    │    │
│  │ Trong khoảng này, tài khoản vẫn active.            │    │
│  │ Hệ thống sẽ:                                       │    │
│  │  • Snapshot Passport đến ngày 01/06/2026           │    │
│  │  • Đánh dấu activity 01/06–13/10 là "post-exit"   │    │
│  │  • Tạo audit log về khoảng gap này                 │    │
│  └────────────────────────────────────────────────────┘    │
│                                                             │
│  Lý do nghỉ: [Tự nguyện ▾]                                │
│                                [Hủy]  [Xác nhận Offboard] │
└─────────────────────────────────────────────────────────────┘
```

Schema bổ sung:

```sql
-- Thêm vào organization_members
ADD COLUMN effective_left_at    TIMESTAMP    NULL
    COMMENT 'Ngày thực tế nghỉ việc — do HR nhập, có thể khác left_at (ngày click offboard)',
ADD COLUMN offboarded_at        TIMESTAMP    NULL
    COMMENT 'Ngày HR click offboard trên hệ thống (= thời điểm action thực tế)',
ADD COLUMN late_offboard_gap_days SMALLINT  UNSIGNED NULL
    COMMENT 'Số ngày gap giữa effective_left_at và offboarded_at — 0 nếu offboard đúng hạn';

-- Bảng audit riêng cho post-exit access window
CREATE TABLE member_post_exit_audits (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    organization_id     BIGINT UNSIGNED NOT NULL,
    user_id             BIGINT UNSIGNED NOT NULL,
    org_membership_id   BIGINT UNSIGNED NOT NULL,
    effective_left_at   TIMESTAMP NOT NULL,
    offboarded_at       TIMESTAMP NOT NULL,
    gap_days            SMALLINT UNSIGNED NOT NULL,
    -- Activity summary trong gap period
    login_count_in_gap  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    sandbox_sessions_in_gap SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    last_login_in_gap   TIMESTAMP NULL,
    -- HR xác nhận
    reviewed_by         BIGINT UNSIGNED NULL COMMENT 'FK users.id — HR đã review',
    reviewed_at         TIMESTAMP NULL,
    review_note         TEXT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_mpea_org_user (organization_id, user_id),
    INDEX idx_mpea_gap_days (gap_days)
);
```

`OrgMembershipService::deactivateLate()`:

```php
public function deactivateLate(
    OrganizationMember $member,
    Carbon $effectiveLeftAt,  // Ngày thực tế HR nhập
    string $exitReason
): void {
    $offboardedAt = now();
    $gapDays = $effectiveLeftAt->diffInDays($offboardedAt);

    DB::transaction(function () use ($member, $effectiveLeftAt, $offboardedAt, $gapDays, $exitReason) {
        // 1. Đếm activity trong gap để ghi vào audit
        $loginCount = $this->countLoginsInGap($member->user_id, $effectiveLeftAt, $offboardedAt);
        $sandboxCount = $this->countSandboxSessionsInGap(
            $member->organization_id, $member->user_id,
            $effectiveLeftAt, $offboardedAt
        );
        $lastLogin = $this->getLastLoginInGap($member->user_id, $effectiveLeftAt, $offboardedAt);

        // 2. Update membership với ngày thực tế
        $member->update([
            'status'                => 'inactive',
            'left_at'               => $effectiveLeftAt,  // Ngày thực tế
            'effective_left_at'     => $effectiveLeftAt,
            'offboarded_at'         => $offboardedAt,
            'late_offboard_gap_days'=> $gapDays,
            'exit_reason'           => $exitReason,
            'exit_initiated_by'     => 'hr',
        ]);

        // 3. Tạo audit record
        MemberPostExitAudit::create([
            'organization_id'        => $member->organization_id,
            'user_id'                => $member->user_id,
            'org_membership_id'      => $member->id,
            'effective_left_at'      => $effectiveLeftAt,
            'offboarded_at'          => $offboardedAt,
            'gap_days'               => $gapDays,
            'login_count_in_gap'     => $loginCount,
            'sandbox_sessions_in_gap'=> $sandboxCount,
            'last_login_in_gap'      => $lastLogin,
        ]);

        // 4. Snapshot Passport CUT OFF tại effectiveLeftAt
        //    (chỉ include data <= effectiveLeftAt)
        SnapshotPassportEntryJob::dispatch($member->user_id, $member->organization_id)
            ->with('snapshot_cutoff', $effectiveLeftAt);

        // 5. Revoke access ngay
        $member->user->removeRole($member->role);
        $member->user->update([
            'account_type'   => 'free',
            'current_org_id' => null,
        ]);
    });
}
```

#### Passport Snapshot với cutoff date

`SnapshotPassportEntryJob` nhận thêm tham số `snapshot_cutoff`:

```php
// Trong SnapshotPassportEntryJob::handle()
$cutoff = $this->snapshotCutoff ?? now(); // Default: now() nếu offboard đúng hạn

// Chỉ lấy assessment kết thúc trước cutoff
$latestAssessment = AssessmentResult::where('workforce_profile_id', $profile->id)
    ->where('completed_at', '<=', $cutoff)
    ->latest('completed_at')
    ->first();

// Chỉ lấy cert còn active tại thời điểm cutoff
$certsAtExit = WorkforceCertification::where('workforce_profile_id', $profile->id)
    ->where('issued_at', '<=', $cutoff)
    ->where(function ($q) use ($cutoff) {
        $q->whereNull('expires_at')->orWhere('expires_at', '>', $cutoff);
    })
    ->where('status', 'active')
    ->get();

// Sandbox hours chỉ tính đến cutoff
$sandboxHours = SandboxSession::where('workforce_profile_id', $profile->id)
    ->where('completed_at', '<=', $cutoff)
    ->where('status', 'completed')
    ->sum('duration_hours');

// Ghi rõ vào passport entry
PassportEntry::create([
    ...
    'snapshot_at'           => $cutoff,          // Đúng ngày NV nghỉ
    'offboarded_at'         => now(),            // Ngày hệ thống ghi nhận
    'has_late_offboard_gap' => $cutoff < now()->subHours(1),
]);
```

Thêm 2 cột vào `passport_entries`:

```sql
ADD COLUMN offboarded_at            TIMESTAMP NULL
    COMMENT 'Ngày HR action offboard — có thể khác snapshot_at nếu offboard muộn',
ADD COLUMN has_late_offboard_gap    TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1 nếu có gap giữa ngày nghỉ thực tế và ngày offboard';
```

#### UX: Dashboard HR — Cảnh báo offboard

Trong HR dashboard, thêm widget:

```
┌─────────────────────────────────────────────────────────┐
│  ⚠️  Cần xem xét (3)                                    │
│─────────────────────────────────────────────────────────│
│  🔴 Nguyễn Văn A  — không hoạt động 134 ngày            │
│  🟡 Trần Thị B    — hợp đồng hết hạn 15/09 (28 ngày)   │
│  🟡 Lê Văn C      — không hoạt động 52 ngày             │
│                                           [Xem tất cả] │
└─────────────────────────────────────────────────────────┘
```

Màu:
- `🔴` = không hoạt động > 90 ngày HOẶC đã qua `contract_end_date` > 30 ngày
- `🟡` = không hoạt động 45-90 ngày HOẶC còn < 30 ngày đến `contract_end_date`

#### Sơ đồ luồng tổng hợp

```
[Nhân viên nghỉ việc tháng 6]
          │
          ├── HR offboard ngay trong tháng 6
          │       └── deactivate() bình thường — snapshot tại T6
          │
          └── HR quên (phổ biến)
                  │
                  ├── [T6+1 ngày] last_active_at ngừng cập nhật
                  ├── [T6+45 ngày] FlagInactiveMembersJob → email cảnh báo HR
                  ├── [contract_end_date] AutoSuspendJob → status='suspended', revoke role
                  │       └── HR nhận notification ngay
                  └── [T10] HR nhớ ra → deactivateLate(effective_left_at='2026-06-01')
                          ├── Snapshot Passport cutoff tại T6 (đúng)
                          ├── Tạo member_post_exit_audits (gap 134 ngày, login_count, v.v.)
                          └── Revoke access ngay
```

#### Schema bổ sung tổng hợp cho Section 5.6

| Bảng | Cột mới | Mục đích |
|---|---|---|
| `organization_members` | `contract_end_date` | Input ngày hết hợp đồng → trigger auto-suspend |
| `organization_members` | `auto_suspended_at` | Ghi nhận auto-suspend |
| `organization_members` | `last_active_at` | Phát hiện inactivity |
| `organization_members` | `effective_left_at` | Ngày thực tế nghỉ (có thể khác `left_at`) |
| `organization_members` | `offboarded_at` | Ngày HR click offboard |
| `organization_members` | `late_offboard_gap_days` | Số ngày gap — analytics |
| `passport_entries` | `offboarded_at` | Để đối chiếu khi kiểm tra |
| `passport_entries` | `has_late_offboard_gap` | Flag để người xem Passport biết |
| **`member_post_exit_audits`** | *(bảng mới)* | Audit trail đầy đủ cho gap period |

#### Acceptance Criteria

- [ ] `contract_end_date` có thể set khi tạo membership hoặc chỉnh sửa sau
- [ ] `AutoSuspendExpiredMembershipsJob` chạy daily, suspend đúng, gửi notification HR
- [ ] `FlagInactiveMembersJob` chạy weekly, email HR report đúng danh sách
- [ ] Form "Late Offboard" cho phép HR nhập `effective_left_at`, hiện cảnh báo gap
- [ ] Snapshot Passport dùng đúng `effective_left_at` làm cutoff — không include data sau ngày đó
- [ ] `member_post_exit_audits` được tạo đúng với `login_count_in_gap`, `sandbox_sessions_in_gap`
- [ ] Trường hợp offboard đúng hạn: `effective_left_at = offboarded_at`, không tạo audit gap record
- [ ] `has_late_offboard_gap = 1` hiển thị note trong Passport viewer: "Hồ sơ này được xác nhận muộn N ngày"

---

## 6. Phase 0 — Identity Foundation

**Mục tiêu:** Tách biệt định danh cá nhân khỏi org. Mọi người dùng có thể tồn tại và đăng nhập dù không thuộc org nào.

**Ưu tiên:** Cao nhất — là nền tảng cho mọi phase sau.  
**Thời gian:** 2–3 tuần  
**Breaking changes:** Không — toàn bộ là additive migrations

### 6.1 Mở rộng bảng `users`

Tổng hợp tất cả cột cần thêm (gom thành 1 migration để rõ ràng, thực tế tách theo phase):

```sql
-- Migration: add_passport_identity_fields_to_users_table
ALTER TABLE users
    -- Identity & Account State
    ADD COLUMN account_type             VARCHAR(20)  NOT NULL DEFAULT 'free'
        COMMENT 'free | org_member | suspended',
    ADD COLUMN current_org_id           BIGINT       UNSIGNED NULL
        COMMENT 'NULL nếu free',

    -- eKYC Trust Level (Phase 3)
    ADD COLUMN trust_level              TINYINT      UNSIGNED NOT NULL DEFAULT 0
        COMMENT '0=unverified, 1=email, 2=phone, 3=cccd, 4=cccd_biometric',

    -- Phone (Phase 3)
    ADD COLUMN phone_number             VARCHAR(20)  NULL,
    ADD COLUMN phone_verified_at        TIMESTAMP    NULL,

    -- National ID (Phase 3)
    ADD COLUMN national_id_hash         VARCHAR(64)  NULL UNIQUE
        COMMENT 'SHA-256(số_CCCD) — check uniqueness, không lưu số thật',

    -- Indexes
    ADD INDEX idx_users_account_type    (account_type),
    ADD INDEX idx_users_trust_level     (trust_level);
```

> **email_verified_at đã có sẵn** — không thêm. Khi deploy, seed migration: `UPDATE users SET trust_level = 1 WHERE email_verified_at IS NOT NULL`.

> **Không cần email_owner_type, personal_email, claim_token** — vì quy tắc bắt buộc email cá nhân từ đầu loại bỏ toàn bộ handover flow.

### 6.2 Mở rộng bảng `organization_members`

```sql
-- Migration: add_exit_tracking_to_organization_members_table
ALTER TABLE organization_members
    ADD COLUMN status               VARCHAR(20)  NOT NULL DEFAULT 'active'
        COMMENT 'active | inactive | paused',
    ADD COLUMN left_at              TIMESTAMP    NULL,
    ADD COLUMN exit_reason          VARCHAR(50)  NULL
        COMMENT 'resigned | terminated | retired | contract_end | internal_transfer',
    ADD COLUMN exit_initiated_by    VARCHAR(20)  NULL
        COMMENT 'self | hr | system',
    ADD COLUMN job_title_at_exit    VARCHAR(200) NULL,
    ADD COLUMN department_at_exit   VARCHAR(200) NULL,
    ADD COLUMN role_at_exit         VARCHAR(50)  NULL,
    -- Audit: biết tài khoản này do org tạo hay user tự đăng ký
    ADD COLUMN account_was_org_created TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 nếu org tạo tài khoản này (không phải user tự đăng ký)',
    ADD INDEX idx_om_status (status),
    ADD INDEX idx_om_left_at (left_at);
```

> **joined_at và role đã có sẵn** — chỉ cần thêm exit tracking.  
> `account_was_org_created` chỉ dùng cho audit/analytics — không ảnh hưởng luồng (vì email luôn là cá nhân dù ai tạo).

### 6.3 Bảng mới: `identity_verifications`

```sql
CREATE TABLE identity_verifications (
    id                  BIGINT          UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id             BIGINT          UNSIGNED NOT NULL,
    method              VARCHAR(30)     NOT NULL
                        COMMENT 'email | phone_otp | cccd_ocr | cccd_chip | vne_id | passport',
    status              VARCHAR(20)     NOT NULL DEFAULT 'pending'
                        COMMENT 'pending | verified | rejected | expired',
    verified_at         TIMESTAMP       NULL,
    expires_at          TIMESTAMP       NULL,
    issuing_province_id BIGINT          UNSIGNED NULL
                        COMMENT 'FK provinces.id — phi nhạy cảm, dùng cho phân tích địa lý',
    rejection_reason    VARCHAR(300)    NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_iv_user_method (user_id, method),
    INDEX idx_iv_status (status)
);
```

### 6.4 Normalize bảng `workforce_recommendations`

Bảng `workforce_recommendations` hiện có cột `recommendations JSON` — cần tách ra:

```sql
-- Bảng mới: workforce_recommendation_items
CREATE TABLE workforce_recommendation_items (
    id                          BIGINT      UNSIGNED NOT NULL AUTO_INCREMENT,
    workforce_recommendation_id BIGINT      UNSIGNED NOT NULL,
    priority                    TINYINT     NOT NULL COMMENT '1=cao nhất, 5=thấp nhất',
    domain_code                 VARCHAR(20) NOT NULL COMMENT 'D1|D2|D3|D4|D5|D6',
    action_description          TEXT        NOT NULL,
    resource_type               VARCHAR(30) NOT NULL
                                COMMENT 'course | sandbox | certification | practice | reading',
    resource_name               VARCHAR(300) NULL,
    resource_url                VARCHAR(500) NULL,
    estimated_duration_hours    DECIMAL(4,1) NULL,
    created_at                  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_wri_recommendation (workforce_recommendation_id),
    INDEX idx_wri_domain (domain_code)
);
```

> Migration kèm theo: đọc JSON cũ → insert rows → xóa cột JSON (hoặc giữ để backward compat, deprecate sau).

### 6.5 Service: OrgMembershipService

```php
// Modules/Assessment/app/Services/OrgMembershipService.php
class OrgMembershipService
{
    public function deactivate(User $user, Organization $org, array $options = []): void
    {
        DB::transaction(function () use ($user, $org, $options) {

            // 1. Đóng membership
            OrganizationMember::where('user_id', $user->id)
                ->where('organization_id', $org->id)
                ->where('status', 'active')
                ->update([
                    'status'              => 'inactive',
                    'left_at'             => now(),
                    'exit_reason'         => $options['reason'] ?? 'resigned',
                    'exit_initiated_by'   => $options['initiated_by'] ?? 'hr',
                    'job_title_at_exit'   => $user->employee?->jobTitle?->name,
                    'department_at_exit'  => $user->employee?->department?->name,
                    'role_at_exit'        => $user->getRoleNames()->first(),
                ]);

            // 2. Thu hồi roles Spatie
            $user->syncRoles([]);

            // 3. Cập nhật users
            $user->update([
                'account_type'   => 'free',
                'current_org_id' => null,
            ]);

            // 4. Dispatch snapshot job (Phase 1)
            SnapshotPassportEntryJob::dispatch($user->id, $org->id)
                ->onQueue('passport');

            // 5. Thông báo
            Mail::to($user->email)->queue(new OrgExitPassportReadyMail($user, $org));
        });
    }
}
```

### 6.6 Màn hình tài khoản Free sau Phase 0

```
Route: /passport  (Personal Dashboard — truy cập khi account_type = 'free' | 'org_member')

┌──────────────────────────────────────────────────────────┐
│  Xin chào, Nguyễn Văn A                                  │
│  ✉ Email đã xác minh                                     │
│                                                          │
│  Bạn hiện không thuộc tổ chức nào.                      │
│  Competency Passport của bạn đang được bảo quản.        │
│                                                          │
│  [ Xem Nhật ký nghề nghiệp ]  [ Nâng cấp xác minh ]    │
└──────────────────────────────────────────────────────────┘
```

### 6.7 Acceptance Criteria Phase 0

- [ ] User mới đăng ký → `account_type = 'free'`, `trust_level = 0`
- [ ] Xác minh email → `trust_level = 1`, `identity_verifications` row inserted
- [ ] HR deactivate nhân viên → membership inactive + user free trong 1 transaction
- [ ] User free đăng nhập được, không thấy bất kỳ org workspace nào
- [ ] Không có existing org user bị ảnh hưởng (migration set default không break)
- [ ] `workforce_recommendation_items` được seed từ JSON cũ trước khi remove JSON column

---

## 7. Phase 1 — Competency Passport

**Mục tiêu:** Khi rời org, hồ sơ được snapshot thành 1 "chương" bất biến trong Career Journal. User free có thể xem toàn bộ lịch sử và tải PDF.

**Phụ thuộc:** Phase 0  
**Thời gian:** 3–4 tuần

### 7.1 Thiết kế bảng Passport (không có JSON)

**Nguyên tắc:** Mỗi snapshot = 1 `passport_entries` + các bảng con quan hệ 1:N. Không có column JSON.

#### Bảng `passport_entries` — Header của mỗi chương

```sql
CREATE TABLE passport_entries (
    id                      BIGINT      UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid                    CHAR(36)    NOT NULL UNIQUE
                            COMMENT 'Public ID — dùng cho URL, API',
    user_id                 BIGINT      UNSIGNED NOT NULL,
    entry_type              VARCHAR(30) NOT NULL
                            COMMENT 'org_tenure | campaign_result | self_declaration',
    source_org_id           BIGINT      UNSIGNED NULL
                            COMMENT 'NULL nếu type=self_declaration',
    source_org_name         VARCHAR(200) NULL
                            COMMENT 'Tên org tại thời điểm snapshot — bất biến',
    source_org_logo_path    VARCHAR(500) NULL,

    -- Thông tin nhiệm kỳ
    snapshot_at             TIMESTAMP   NOT NULL,
    tenure_start            DATE        NULL,
    tenure_end              DATE        NULL,
    tenure_months           SMALLINT    UNSIGNED NULL,
    job_title_at_exit       VARCHAR(200) NULL,
    department_at_exit      VARCHAR(200) NULL,
    role_at_exit            VARCHAR(50)  NULL

    -- Điểm tổng hợp tại thời điểm snapshot
    tdwcf_score             DECIMAL(5,2) NULL,
    tdwcf_maturity_level    VARCHAR(64)  NULL,
    workforce_trust_score   DECIMAL(5,2) NULL,
    ai_readiness_score      DECIMAL(5,2) NULL,
    sandbox_hours_total     SMALLINT    UNSIGNED DEFAULT 0,
    sandbox_score_avg       DECIMAL(5,2) NULL,
    certifications_count    TINYINT     UNSIGNED DEFAULT 0,
    highest_cert_level      VARCHAR(30)  NULL
                            COMMENT 'FOUNDATION|PRACTITIONER|PROFESSIONAL|LEADER',
    impact_entries_count    SMALLINT    UNSIGNED DEFAULT 0,

    -- Quyền riêng tư
    visibility              VARCHAR(20)  NOT NULL DEFAULT 'private'
                            COMMENT 'private | link_only | public',
    share_token             VARCHAR(64)  NULL UNIQUE,
    share_token_expires_at  TIMESTAMP    NULL,

    -- Xác nhận từ org (tùy chọn)
    org_verified            TINYINT(1)   NOT NULL DEFAULT 0,
    org_verified_at         TIMESTAMP    NULL,
    org_verified_by_user_id BIGINT       UNSIGNED NULL,

    -- Ghi chú cá nhân (user thêm vào, không thể sửa nội dung snapshot)
    personal_note           TEXT         NULL,

    created_at              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_pe_user           (user_id),
    INDEX idx_pe_source_org     (source_org_id),
    INDEX idx_pe_type           (entry_type),
    INDEX idx_pe_visibility     (visibility),
    INDEX idx_pe_snapshot_at    (snapshot_at)
);
```

#### Bảng `passport_domain_scores` — 6 điểm domain tại thời điểm snapshot

```sql
CREATE TABLE passport_domain_scores (
    id                  BIGINT      UNSIGNED NOT NULL AUTO_INCREMENT,
    passport_entry_id   BIGINT      UNSIGNED NOT NULL,
    domain_code         VARCHAR(10) NOT NULL COMMENT 'D1|D2|D3|D4|D5|D6',
    domain_name         VARCHAR(100) NOT NULL COMMENT 'Tên domain tại thời điểm snapshot',
    score               DECIMAL(5,2) NULL,
    required_score      DECIMAL(5,2) NULL
                        COMMENT 'Yêu cầu của job title tại thời điểm exit',
    gap                 DECIMAL(6,2) NULL
                        COMMENT 'score - required_score (âm = thiếu)',
    is_critical         TINYINT(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pds_entry_domain (passport_entry_id, domain_code),
    INDEX idx_pds_entry (passport_entry_id)
);
```

#### Bảng `passport_certifications` — Chứng nhận đã có tại thời điểm exit

```sql
CREATE TABLE passport_certifications (
    id                      BIGINT      UNSIGNED NOT NULL AUTO_INCREMENT,
    passport_entry_id       BIGINT      UNSIGNED NOT NULL,
    cert_definition_id      BIGINT      UNSIGNED NULL
                            COMMENT 'FK certification_definitions — nullable nếu def bị xóa',
    cert_code               VARCHAR(50)  NOT NULL
                            COMMENT 'Lưu code để bất biến kể cả khi def thay đổi',
    cert_name               VARCHAR(200) NOT NULL,
    cert_type_code          VARCHAR(30)  NOT NULL COMMENT 'AI_SALES | AI_HR | ...',
    level_code              VARCHAR(30)  NOT NULL COMMENT 'FOUNDATION | PRACTITIONER | ...',
    level_order             TINYINT      UNSIGNED NOT NULL,
    issued_at               DATE         NOT NULL,
    expires_at              DATE         NULL,
    certificate_number      VARCHAR(50)  NULL,
    composite_score_at_issue DECIMAL(5,2) NULL,
    PRIMARY KEY (id),
    INDEX idx_pc_entry      (passport_entry_id),
    INDEX idx_pc_cert_code  (cert_code)
);
```

#### Bảng `passport_impact_highlights` — Top impact đáng chú ý

```sql
CREATE TABLE passport_impact_highlights (
    id                      BIGINT      UNSIGNED NOT NULL AUTO_INCREMENT,
    passport_entry_id       BIGINT      UNSIGNED NOT NULL,
    source_impact_id        BIGINT      UNSIGNED NULL
                            COMMENT 'FK ai_impact_snapshots.id — nullable nếu record gốc bị xóa',
    title                   VARCHAR(300) NOT NULL,
    impact_category         VARCHAR(30)  NOT NULL
                            COMMENT 'learning|productivity|quality|ai_adoption|business',
    impact_type             VARCHAR(80)  NULL,
    baseline_value          DECIMAL(12,4) NULL,
    achieved_value          DECIMAL(12,4) NULL,
    improvement_pct         DECIMAL(7,2)  NULL,
    roi_pct                 DECIMAL(7,2)  NULL,
    period_label            VARCHAR(50)   NULL COMMENT 'VD: "Tháng 3/2026"',
    sort_order              TINYINT       UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    INDEX idx_pih_entry (passport_entry_id)
);
```

#### Bảng `passport_sandbox_summaries` — Tóm tắt sandbox theo môi trường

```sql
CREATE TABLE passport_sandbox_summaries (
    id                      BIGINT      UNSIGNED NOT NULL AUTO_INCREMENT,
    passport_entry_id       BIGINT      UNSIGNED NOT NULL,
    sandbox_env_id          BIGINT      UNSIGNED NULL,
    env_code                VARCHAR(50)  NOT NULL COMMENT 'Bất biến',
    env_name                VARCHAR(200) NOT NULL COMMENT 'Bất biến',
    sessions_completed      SMALLINT    UNSIGNED NOT NULL DEFAULT 0,
    hours_spent             DECIMAL(5,1) NULL,
    avg_score               DECIMAL(5,2) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pss_entry_env (passport_entry_id, env_code),
    INDEX idx_pss_entry (passport_entry_id)
);
```

### 7.2 Job: SnapshotPassportEntryJob

```php
// Modules/Assessment/app/Jobs/SnapshotPassportEntryJob.php
class SnapshotPassportEntryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function handle(): void
    {
        // Idempotent: không tạo 2 snapshot cùng ngày cho cùng user+org
        $existing = PassportEntry::where('user_id', $this->userId)
            ->where('source_org_id', $this->orgId)
            ->where('entry_type', 'org_tenure')
            ->whereDate('snapshot_at', today())
            ->first();

        if ($existing) return;

        $profile    = WorkforceProfile::where('organization_id', $this->orgId)
                        ->where('user_id', $this->userId)->first();
        $membership = OrganizationMember::where('user_id', $this->userId)
                        ->where('organization_id', $this->orgId)
                        ->where('status', 'inactive')
                        ->latest('left_at')->first();
        $org        = Organization::find($this->orgId);

        if (!$profile || !$org) return;

        DB::transaction(function () use ($profile, $membership, $org) {

            // 1. Header entry
            $entry = PassportEntry::create([
                'uuid'                  => Str::uuid(),
                'user_id'               => $this->userId,
                'entry_type'            => 'org_tenure',
                'source_org_id'         => $this->orgId,
                'source_org_name'       => $org->name,
                'source_org_logo_path'  => $org->logo_path,
                'snapshot_at'           => now(),
                'tenure_start'          => $membership?->joined_at?->toDateString(),
                'tenure_end'            => $membership?->left_at?->toDateString(),
                'tenure_months'         => $membership
                    ? (int) $membership->joined_at->diffInMonths($membership->left_at)
                    : null,
                'job_title_at_exit'     => $membership?->job_title_at_exit,
                'department_at_exit'    => $membership?->department_at_exit,
                'role_at_exit'          => $membership?->role_at_exit,
                'tdwcf_score'           => $profile->tdwcf_score,
                'tdwcf_maturity_level'  => $profile->tdwcf_maturity_level,
                'workforce_trust_score' => $profile->workforce_trust_score,
                'ai_readiness_score'    => $profile->ai_readiness_score,
                'sandbox_hours_total'   => $profile->sandbox_hours_total,
                'sandbox_score_avg'     => $profile->sandbox_score_avg,
                'certifications_count'  => $profile->certifications_count,
                'highest_cert_level'    => $profile->highest_cert_level,
                'visibility'            => 'private',
            ]);

            // 2. Domain scores (6 rows)
            $domainMap = [
                'D1' => ['name' => 'Năng lực số cơ bản',    'score' => $profile->score_d1_digital_literacy],
                'D2' => ['name' => 'Năng lực dữ liệu',       'score' => $profile->score_d2_data_literacy],
                'D3' => ['name' => 'Năng lực AI',            'score' => $profile->score_d3_ai_literacy],
                'D4' => ['name' => 'Quy trình & Tự động hoá','score' => $profile->score_d4_workflow],
                'D5' => ['name' => 'Đổi mới & Sáng kiến',   'score' => $profile->score_d5_innovation],
                'D6' => ['name' => 'Hiệu suất & KPI',        'score' => $profile->score_d6_performance],
            ];

            $requirements = JobTitleDomainRequirement::where('job_title_id',
                $profile->employee?->job_title_id)->get()->keyBy('domain_code');

            foreach ($domainMap as $code => $data) {
                $req = $requirements->get($code);
                PassportDomainScore::create([
                    'passport_entry_id' => $entry->id,
                    'domain_code'       => $code,
                    'domain_name'       => $data['name'],
                    'score'             => $data['score'],
                    'required_score'    => $req?->required_score,
                    'gap'               => $data['score'] && $req
                                            ? $data['score'] - $req->required_score
                                            : null,
                    'is_critical'       => $req?->is_critical ?? 0,
                ]);
            }

            // 3. Certifications (active tại thời điểm exit)
            WorkforceCertification::where('workforce_profile_id', $profile->id)
                ->where('status', 'active')
                ->with('definition')
                ->get()
                ->each(function ($cert) use ($entry) {
                    PassportCertification::create([
                        'passport_entry_id'      => $entry->id,
                        'cert_definition_id'     => $cert->cert_definition_id,
                        'cert_code'              => $cert->definition->cert_code,
                        'cert_name'              => $cert->definition->name,
                        'cert_type_code'         => $cert->definition->cert_type_code,
                        'level_code'             => $cert->definition->level_code,
                        'level_order'            => $cert->definition->level_order,
                        'issued_at'              => $cert->issued_at->toDateString(),
                        'expires_at'             => $cert->expires_at?->toDateString(),
                        'certificate_number'     => $cert->certificate_number,
                        'composite_score_at_issue' => $cert->composite_score_at_issue,
                    ]);
                });

            // 4. Impact highlights (top 5 theo improvement_pct)
            AiImpactSnapshot::where('organization_id', $this->orgId)
                ->where('employee_id', $profile->employee_id)
                ->orderByDesc('improvement_pct')
                ->limit(5)
                ->get()
                ->each(function ($impact, $idx) use ($entry) {
                    PassportImpactHighlight::create([
                        'passport_entry_id' => $entry->id,
                        'source_impact_id'  => $impact->id,
                        'title'             => $impact->notes ?? $impact->impact_type,
                        'impact_category'   => $impact->impact_category,
                        'impact_type'       => $impact->impact_type,
                        'baseline_value'    => $impact->baseline_value,
                        'achieved_value'    => $impact->achieved_value,
                        'improvement_pct'   => $impact->improvement_pct,
                        'roi_pct'           => $impact->roi_pct,
                        'period_label'      => optional($impact->period_start)->format('m/Y'),
                        'sort_order'        => $idx,
                    ]);
                });

            // 5. Sandbox summaries per environment
            SandboxSession::where('organization_id', $this->orgId)
                ->where('workforce_profile_id', $profile->id)
                ->where('status', 'completed')
                ->with('task.environment')
                ->get()
                ->groupBy(fn($s) => $s->task->sandbox_env_id)
                ->each(function ($sessions, $envId) use ($entry) {
                    $env = $sessions->first()->task->environment;
                    PassportSandboxSummary::create([
                        'passport_entry_id'  => $entry->id,
                        'sandbox_env_id'     => $envId,
                        'env_code'           => $env->env_code,
                        'env_name'           => $env->name,
                        'sessions_completed' => $sessions->count(),
                        'hours_spent'        => $sessions->sum(fn($s) =>
                            optional($s->completed_at)->diffInMinutes($s->started_at) / 60
                        ),
                        'avg_score'          => $sessions->avg('final_score'),
                    ]);
                });
        });
    }
}
```

### 7.3 Personal Dashboard — Routes

```
GET  /passport                          → Personal Dashboard (danh sách entries)
GET  /passport/{uuid}                   → Chi tiết 1 entry
GET  /passport/{uuid}/pdf               → Tải PDF
PUT  /passport/{uuid}/note              → Cập nhật personal_note
PUT  /passport/{uuid}/visibility        → Đổi visibility (private/link_only/public)
GET  /passport/current                  → Redirect đến workforce profile đang active (nếu org_member)
```

### 7.4 Acceptance Criteria Phase 1

- [ ] SnapshotJob chạy async, idempotent (chạy 2 lần không tạo 2 entries)
- [ ] Job fail → retry 3 lần với backoff 30s, log lỗi, alert admin
- [ ] Sau snapshot: 1 passport_entries + 6 domain_scores + N certs + N impacts + N sandbox_summaries
- [ ] Nếu nhân viên chưa có `workforce_profile` (chưa làm khảo sát), job ghi log và skip gracefully
- [ ] User free đăng nhập → thấy danh sách passport_entries đúng thứ tự snapshot_at desc
- [ ] Tải PDF không yêu cầu org context (không qua TenantContext)
- [ ] Org xóa/sửa workforce_profile gốc không ảnh hưởng snapshot

---

## 8. Phase 2 — Portability & Sharing

**Mục tiêu:** Chia sẻ hồ sơ với nhà tuyển dụng qua link, không yêu cầu đăng nhập phía người xem.

**Phụ thuộc:** Phase 1  
**Thời gian:** 1–2 tuần

### 8.1 Visibility & Share Token

Sử dụng `passport_entries.visibility` và `share_token` đã thiết kế ở Phase 1.

| Mode | URL | Ai xem | Indexable |
|---|---|---|---|
| `private` | Không có | Chỉ chính chủ | Không |
| `link_only` | `/p/{share_token}` | Ai có link | `noindex` |
| `public` | `/p/{uuid}` | Mọi người | `index` (Phase 5) |

```php
// Tạo share link
public function generateShareLink(PassportEntry $entry, int $daysValid = 365): string
{
    $entry->update([
        'visibility'            => 'link_only',
        'share_token'           => Str::random(48),
        'share_token_expires_at'=> now()->addDays($daysValid),
    ]);
    return route('passport.public', $entry->share_token);
}

// Thu hồi link
public function revokeShareLink(PassportEntry $entry): void
{
    $entry->update([
        'visibility'            => 'private',
        'share_token'           => null,
        'share_token_expires_at'=> null,
    ]);
}
```

### 8.2 Public Profile Page

```
Route: GET /p/{token_or_uuid}  — không yêu cầu auth
```

**Hiển thị (từ passport_entries + bảng con):**
- Tên người dùng, Trust Level badge, ngày snapshot
- Tên tổ chức + thời gian làm việc + chức danh
- TDWCF Score + Maturity Level
- Radar chart 6 domain (D1–D6 từ passport_domain_scores)
- Danh sách chứng nhận (từ passport_certifications, ẩn expired)
- Top 3 impact highlights (từ passport_impact_highlights, sort_order asc)
- Badge "Xác nhận bởi [Org Name]" nếu `org_verified = true`
- Nút "Tải PDF"

**Không hiển thị:** email, số điện thoại, số CCCD, thông tin nhạy cảm

**Middleware:**
```php
// Kiểm tra token hợp lệ và chưa hết hạn
if ($entry->visibility === 'link_only') {
    abort_if($entry->share_token !== $token, 404);
    abort_if($entry->share_token_expires_at < now(), 410); // 410 Gone
}
abort_if($entry->visibility === 'private', 404);
```

### 8.3 PDF Export — 2 loại

**PDF cá nhân** (từ `/passport/{uuid}/pdf`, yêu cầu auth + là chính chủ):
- Bìa: Tên đầy đủ, Trust badge, ngày xuất
- Thông tin nhiệm kỳ
- Điểm TDWCF + Radar chart
- Bảng 6 domain + gap
- Danh sách cert
- Top 5 impacts
- Footer: "Xuất từ [Platform] · {uuid} · Xác thực tại /p/{uuid}"

**PDF công khai** (từ `/p/{token}/pdf`, không cần auth):
- Giống trên nhưng không có thông tin nhạy cảm

### 8.4 Acceptance Criteria Phase 2

- [ ] Toggle visibility hoạt động, persist đúng
- [ ] Link `link_only` hết hạn → 410 Gone (không phải 404)
- [ ] Public page load < 500ms (không qua TenantContext, index đơn giản)
- [ ] PDF xuất đúng dữ liệu từ snapshot, không query org workspace
- [ ] `noindex` meta trên link_only pages
- [ ] Personal note của user không hiện trên public page

---

## 9. Phase 3 — eKYC Verified Identity

**Mục tiêu:** Xây dựng hệ thống xác minh danh tính nhiều lớp, tăng độ tin cậy của hồ sơ khi chia sẻ.

**Phụ thuộc:** Phase 0  
**Thời gian:** 3–4 tuần

### 9.1 Trust Level — Chi tiết từng bậc

| Level | Phương thức | Mở khoá | Badge hiển thị |
|---|---|---|---|
| 0 | Chưa gì | Đăng ký, xem Passport | (không có) |
| 1 | Email verified | Đầy đủ tính năng cơ bản | ✉ Email |
| 2 | Email | Tham gia Open Assessment (Phase 4) | 📱 Điện thoại |
| 3 | + CCCD hash | Hồ sơ có dấu "Danh tính xác minh" trên public page | 🪪 CCCD |
| 4 | + Biometrics (Phase 5) | API tích hợp doanh nghiệp, VNeID | ⭐ VNeID |

### 9.2 Luồng xác minh Phone OTP

```
POST /passport/verify/phone/request
  body: { phone_number: "0912345678" }
  → Rate limit: 3 requests/phone/hour
  → Gửi OTP 6 số, TTL 5 phút
  → identity_verifications.insert({method:'phone_otp', status:'pending'})

POST /passport/verify/phone/confirm
  body: { otp: "123456" }
  → Kiểm tra OTP hợp lệ + chưa hết hạn
  → identity_verifications.update({status:'verified', verified_at: now()})
  → users.update({phone_number, phone_verified_at: now(), trust_level: 2})
```

### 9.3 Luồng xác minh CCCD

```
POST /passport/verify/cccd
  body: multipart { front_image, back_image }

Server-side:
  1. OCR trích xuất: họ tên, ngày sinh, số CCCD, ngày cấp, tỉnh cấp
  2. Validate họ tên khớp với users.name (fuzzy match ≥ 85%)
  3. Hash số CCCD: national_id_hash = hash('sha256', cccd_number)
  4. Kiểm tra uniqueness: SELECT 1 FROM users WHERE national_id_hash = ?
     → Nếu trùng → 409 Conflict "Số CCCD đã liên kết với tài khoản khác"
  5. Lưu:
     → users.update({national_id_hash, trust_level: 3})
     → identity_verifications.insert({
           method: 'cccd_ocr',
           status: 'verified',
           verified_at: now(),
           issuing_province_id: [lookup từ tên tỉnh],
           expires_at: [10 năm, từ ngày cấp CCCD]
       })
  6. KHÔNG lưu: số CCCD thô, ảnh CCCD (chỉ process trong memory)

Response: { trust_level: 3, verified_at: "..." }
```

### 9.4 Hiển thị Trust Badge

Trust badge xuất hiện trên:
- Public profile page `/p/{token}`
- PDF export (góc trên phải)
- Màn hình cá nhân `/passport`
- Trong campaign ranking (Phase 4)

```
trust_level 1:  ✉ Email
trust_level 2:  📱 Điện thoại
trust_level 3:  🪪 Danh tính xác minh
trust_level 4:  ⭐ VNeID (Phase 5)
```

### 9.5 Acceptance Criteria Phase 3

- [ ] Rate limit OTP: 3/phone/hour, return 429 khi vượt
- [ ] OTP hết hạn sau 5 phút → phải request lại
- [ ] Một số CCCD không thể liên kết với 2 user khác nhau
- [ ] Số CCCD thô không được lưu vào bất kỳ table/log nào
- [ ] Ảnh CCCD không được lưu (chỉ OCR trong memory rồi discard)
- [ ] Trust badge hiển thị đúng mức trên public page và PDF

---

## 10. Phase 4 — Open Assessment Marketplace

**Mục tiêu:** Tổ chức đăng chiến dịch đánh giá mở, cá nhân tự do tham gia, kết quả vào Career Journal.

**Phụ thuộc:** Phase 1 + Phase 3 (trust_level ≥ 2 để tham gia)  
**Thời gian:** 6–8 tuần

### 10.1 Schema Marketplace (không có JSON)

#### Bảng `open_assessment_campaigns`

```sql
CREATE TABLE open_assessment_campaigns (
    id                      BIGINT      UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid                    CHAR(36)    NOT NULL UNIQUE,
    organization_id         BIGINT      UNSIGNED NOT NULL,
    title                   VARCHAR(200) NOT NULL,
    description             TEXT         NULL,
    target_job_title_id     BIGINT       UNSIGNED NULL,
    target_job_title_label  VARCHAR(200) NULL COMMENT 'Bất biến kể cả khi job title đổi',
    target_department_label VARCHAR(200) NULL,

    -- Điều kiện tham gia
    min_trust_level         TINYINT     UNSIGNED NOT NULL DEFAULT 2,
    min_tdwcf_score         DECIMAL(5,2) NULL,

    -- Cấu hình
    status                  VARCHAR(20) NOT NULL DEFAULT 'draft'
                            COMMENT 'draft | open | closed | archived',
    open_from               TIMESTAMP   NULL,
    open_until              TIMESTAMP   NULL,
    max_participants        SMALLINT    UNSIGNED NULL,
    is_anonymous_to_org     TINYINT(1)  NOT NULL DEFAULT 1
                            COMMENT '1=Org không thấy tên cho đến khi invite',

    -- Thống kê (denormalized, cập nhật qua observer)
    participants_count      SMALLINT    UNSIGNED NOT NULL DEFAULT 0,
    completed_count         SMALLINT    UNSIGNED NOT NULL DEFAULT 0,

    created_at              TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_oac_org       (organization_id),
    INDEX idx_oac_status    (status),
    INDEX idx_oac_open_until (open_until)
);
```

#### Bảng `campaign_domain_requirements` — thay thế JSON required_domains

```sql
CREATE TABLE campaign_domain_requirements (
    id              BIGINT      UNSIGNED NOT NULL AUTO_INCREMENT,
    campaign_id     BIGINT      UNSIGNED NOT NULL,
    domain_code     VARCHAR(10) NOT NULL,
    min_score       DECIMAL(5,2) NOT NULL DEFAULT 0,
    is_required     TINYINT(1)  NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cdr_campaign_domain (campaign_id, domain_code)
);
```

#### Bảng `campaign_sandbox_tasks` — thay thế JSON sandbox_env_ids

```sql
CREATE TABLE campaign_sandbox_tasks (
    id              BIGINT      UNSIGNED NOT NULL AUTO_INCREMENT,
    campaign_id     BIGINT      UNSIGNED NOT NULL,
    sandbox_task_id BIGINT      UNSIGNED NOT NULL,
    is_required     TINYINT(1)  NOT NULL DEFAULT 1,
    sort_order      TINYINT     UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cst_campaign_task (campaign_id, sandbox_task_id),
    INDEX idx_cst_campaign (campaign_id)
);
```

#### Bảng `campaign_participations`

```sql
CREATE TABLE campaign_participations (
    id                      BIGINT      UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid                    CHAR(36)    NOT NULL UNIQUE,
    campaign_id             BIGINT      UNSIGNED NOT NULL,
    user_id                 BIGINT      UNSIGNED NOT NULL,
    joined_at               TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at            TIMESTAMP   NULL,
    status                  VARCHAR(20) NOT NULL DEFAULT 'in_progress'
                            COMMENT 'in_progress | completed | abandoned',

    -- Kết quả đánh giá (denormalized summary)
    result_tdwcf_score      DECIMAL(5,2) NULL,
    result_maturity_level   VARCHAR(64)  NULL,
    result_sandbox_avg      DECIMAL(5,2) NULL,

    -- Passport entry được tạo từ campaign này
    passport_entry_id       BIGINT      UNSIGNED NULL,

    -- Hành động từ org (ẩn danh cho đến khi org invite)
    org_rating              TINYINT     UNSIGNED NULL COMMENT '1–5 sao',
    org_note                VARCHAR(500) NULL,
    org_action              VARCHAR(30)  NULL
                            COMMENT 'shortlisted | invited | hired | rejected',
    org_action_at           TIMESTAMP    NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_cp_campaign_user (campaign_id, user_id),
    INDEX idx_cp_user       (user_id),
    INDEX idx_cp_campaign   (campaign_id),
    INDEX idx_cp_status     (status)
);
```

#### Bảng `campaign_participation_scores` — thay thế JSON result_domains

```sql
CREATE TABLE campaign_participation_scores (
    id                      BIGINT      UNSIGNED NOT NULL AUTO_INCREMENT,
    participation_id        BIGINT      UNSIGNED NOT NULL,
    domain_code             VARCHAR(10) NOT NULL,
    score                   DECIMAL(5,2) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cps_part_domain (participation_id, domain_code)
);
```

### 10.2 Luồng tham gia campaign

```
Ứng viên (trust_level ≥ 2):
  GET /campaigns                  → Danh sách campaign đang open
  GET /campaigns/{uuid}           → Chi tiết (yêu cầu, sandbox tasks)
  POST /campaigns/{uuid}/join     → Tạo campaign_participations row
  GET /campaigns/{uuid}/workspace → Workspace sandbox của campaign
  POST /campaigns/{uuid}/submit   → Nộp bài → tính điểm → update participation
      → Nếu completed: dispatch CreateCampaignPassportEntryJob
         → passport_entries (entry_type='campaign_result')
         → passport_domain_scores, passport_sandbox_summaries

Org HR (trong workspace org):
  GET /workforce/campaigns                    → Danh sách campaign của org
  GET /workforce/campaigns/{id}/results       → Ranking ẩn danh
  POST /workforce/campaigns/{id}/invite/{pid} → Mời ứng viên
      → campaign_participations.org_action = 'invited'
      → reveal: lúc này org mới thấy tên/email ứng viên
      → gửi email mời ứng viên
```

### 10.3 Acceptance Criteria Phase 4

- [ ] Chỉ user trust_level ≥ campaign.min_trust_level mới join được
- [ ] Sandbox trong campaign chạy hoàn toàn tách biệt org workspace
- [ ] Kết quả campaign tạo `passport_entry` type='campaign_result' trong Career Journal
- [ ] Org xem ranking: chỉ thấy "Ứng viên #1, #2..." cho đến khi invite
- [ ] Sau khi invite: email gửi đến ứng viên, org thấy tên/email
- [ ] Ứng viên có thể từ chối lời mời (update participation.status = 'declined')

---

## 11. Phase 5 — National Platform & Open API

**Mục tiêu:** Mở rộng thành nền tảng quốc gia, tích hợp bên thứ ba, VNeID.

**Phụ thuộc:** Phase 1–4 hoàn thành  
**Thời gian:** 3–6 tháng

### 11.1 Bảng `passport_api_tokens` — API access cho bên thứ ba

```sql
CREATE TABLE passport_api_tokens (
    id                  BIGINT      UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id             BIGINT      UNSIGNED NOT NULL,
    name                VARCHAR(150) NOT NULL COMMENT 'VD: "Acme Corp ATS Integration"',
    token_hash          VARCHAR(64)  NOT NULL UNIQUE,
    abilities           VARCHAR(500) NULL
                        COMMENT 'pipe-delimited: read:summary|read:certs|read:domains',
    last_used_at        TIMESTAMP   NULL,
    expires_at          TIMESTAMP   NULL,
    revoked_at          TIMESTAMP   NULL,
    created_at          TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_pat_user (user_id)
);
```

### 11.2 Open API Endpoints

```
-- Public (không cần auth, chỉ với public entries)
GET  /api/v1/passport/{uuid}/summary
     → {tdwcf_score, maturity_level, trust_level, cert_count, snapshot_at}

GET  /api/v1/stats/national-index
     → Aggregate ẩn danh: avg TDWCF, % per level, top domains, by province

-- Authorized (user cấp token cho org ATS)
GET  /api/v1/passport/me/entries        → Danh sách entries của user
GET  /api/v1/passport/me/entries/{uuid} → Chi tiết 1 entry

-- Org API (để post campaigns, xem kết quả)
POST /api/v1/org/campaigns
GET  /api/v1/org/campaigns/{uuid}/results
```

### 11.3 National Competency Index

Dữ liệu aggregate ẩn danh, không có PII:

```sql
-- View / materialized table, refresh daily
CREATE TABLE national_competency_stats (
    stat_date               DATE        NOT NULL,
    province_id             BIGINT      UNSIGNED NULL COMMENT 'NULL = toàn quốc',
    industry_code           VARCHAR(50)  NULL,
    sample_size             INT          UNSIGNED NOT NULL,
    avg_tdwcf               DECIMAL(5,2) NULL,
    avg_d1                  DECIMAL(5,2) NULL,
    avg_d2                  DECIMAL(5,2) NULL,
    avg_d3                  DECIMAL(5,2) NULL,
    avg_d4                  DECIMAL(5,2) NULL,
    avg_d5                  DECIMAL(5,2) NULL,
    avg_d6                  DECIMAL(5,2) NULL,
    pct_beginner            DECIMAL(5,2) NULL,
    pct_aware               DECIMAL(5,2) NULL,
    pct_practitioner        DECIMAL(5,2) NULL,
    pct_professional        DECIMAL(5,2) NULL,
    pct_leader              DECIMAL(5,2) NULL,
    PRIMARY KEY (stat_date, province_id, industry_code)
);
```

---

## 12. Schema Master — Tất cả bảng mới

### 12.1 Tổng hợp theo phase

| Phase | Bảng mới | Bảng alter |
|---|---|---|
| **0** | `identity_verifications`, `workforce_recommendation_items`, `member_post_exit_audits` | `users` (+5 cols: account_type, current_org_id, trust_level, phone_*, national_id_hash), `organizations` (+1 col: email_domain), `organization_members` (+13 cols: status, left_at, exit_*, *_at_exit, contract_end_date, auto_suspended_at, last_active_at, effective_left_at, offboarded_at, late_offboard_gap_days, account_was_org_created) |
| **1** | `passport_entries`, `passport_domain_scores`, `passport_certifications`, `passport_impact_highlights`, `passport_sandbox_summaries` | `passport_entries` (+2 cols: offboarded_at, has_late_offboard_gap) |
| **2** | — | `passport_entries` (share_token cols đã có từ Phase 1) |
| **3** | — | `identity_verifications` dùng lại (các cột eKYC nâng cao) |
| **4** | `open_assessment_campaigns`, `campaign_domain_requirements`, `campaign_sandbox_tasks`, `campaign_participations`, `campaign_participation_scores` | — |
| **5** | `passport_api_tokens`, `national_competency_stats` | — |

> **Không có Phase 0.5** — vì quy tắc "email luôn là cá nhân" loại bỏ hoàn toàn Account Handover flow. Validation đơn giản: `NotOrgDomainEmail` rule khi HR tạo tài khoản.
>
> **Late Offboarding (Section 5.6)** được xử lý qua: `contract_end_date` auto-suspend, inactivity monitoring, `effective_left_at` retroactive, và `member_post_exit_audits` audit trail.

### 12.2 Quan hệ khóa ngoại toàn bộ bảng mới

```
identity_verifications.user_id              → users.id
identity_verifications.issuing_province_id  → provinces.id (nullable)

passport_entries.user_id                    → users.id
passport_entries.source_org_id              → organizations.id (nullable)
passport_entries.org_verified_by_user_id    → users.id (nullable)

passport_domain_scores.passport_entry_id    → passport_entries.id
passport_certifications.passport_entry_id   → passport_entries.id
passport_certifications.cert_definition_id  → certification_definitions.id (nullable)
passport_impact_highlights.passport_entry_id → passport_entries.id
passport_impact_highlights.source_impact_id  → ai_impact_snapshots.id (nullable)
passport_sandbox_summaries.passport_entry_id → passport_entries.id
passport_sandbox_summaries.sandbox_env_id    → sandbox_environments.id (nullable)

workforce_recommendation_items.workforce_recommendation_id → workforce_recommendations.id

member_post_exit_audits.organization_id     → organizations.id
member_post_exit_audits.user_id             → users.id
member_post_exit_audits.org_membership_id   → organization_members.id
member_post_exit_audits.reviewed_by         → users.id (nullable)

open_assessment_campaigns.organization_id   → organizations.id
open_assessment_campaigns.target_job_title_id → job_titles.id (nullable)
campaign_domain_requirements.campaign_id    → open_assessment_campaigns.id
campaign_sandbox_tasks.campaign_id          → open_assessment_campaigns.id
campaign_sandbox_tasks.sandbox_task_id      → sandbox_tasks.id
campaign_participations.campaign_id         → open_assessment_campaigns.id
campaign_participations.user_id             → users.id
campaign_participations.passport_entry_id   → passport_entries.id (nullable)
campaign_participation_scores.participation_id → campaign_participations.id

passport_api_tokens.user_id                 → users.id
```

---

## 13. Nguyên tắc thiết kế & Ràng buộc

### 13.1 Zero JSON trong bảng mới

Mọi dữ liệu dạng list phải là bảng con. Lý do:
- Query linh hoạt: `WHERE domain_code = 'D3' AND score < 40`
- Index được trên từng field
- Aggregate dễ: `AVG(score) GROUP BY domain_code`
- Audit trail rõ ràng: mỗi row có `id`, có thể trace

**Ngoại lệ tồn tại (kế thừa, không sửa trong spec này):**
- `workforce_recommendations.recommendations` JSON → đang normalize qua `workforce_recommendation_items`
- `rc_applications.answers` JSON → nằm ngoài scope

### 13.2 Bất biến của Passport

Passport entries là **append-only, immutable**:
- Sau khi `snapshot_at` được set, các cột điểm số **không được UPDATE**
- Chỉ cho phép update: `visibility`, `share_token`, `personal_note`, `org_verified*`
- Implement bằng Eloquent observer: chặn update các protected fields sau khi created

```php
class PassportEntryObserver
{
    private array $immutableFields = [
        'tdwcf_score', 'score_d1', ..., 'certifications_count',
        'source_org_id', 'snapshot_at', 'tenure_*'
    ];

    public function updating(PassportEntry $entry): void
    {
        foreach ($this->immutableFields as $field) {
            if ($entry->isDirty($field)) {
                throw new \LogicException("Cannot modify immutable field: {$field}");
            }
        }
    }
}
```

### 13.3 Additive-only schema changes

Không bao giờ DROP COLUMN, DROP TABLE, hay RENAME COLUMN trên bảng hiện có. Deprecate bằng comment, xóa trong version major tiếp theo.

### 13.4 UUID trên tất cả bảng public-facing

Bảng nào có thể xuất hiện trong URL hoặc API response phải có `uuid CHAR(36) UNIQUE`. Không expose `id` integer ra ngoài.

### 13.5 Denormalized counters

`passport_entries.certifications_count`, `open_assessment_campaigns.participants_count` là denormalized counter, cập nhật qua Eloquent observer. Nhanh hơn COUNT(*) khi render dashboard.

### 13.6 Soft reference cho dữ liệu bất biến

Khi snapshot lưu `cert_code VARCHAR(50) NOT NULL` thay vì chỉ `cert_definition_id`, đảm bảo snapshot không bị ảnh hưởng nếu `certification_definitions` bị sửa/xóa sau đó. Tương tự với `source_org_name`, `domain_name`, `env_name`, `env_code`.

---

## 14. Lộ trình & Milestone

### 14.1 Timeline

```
Q3/2026                Q4/2026              Q1/2027              Q2/2027+
   │                      │                    │                    │
   ▼                      ▼                    ▼                    ▼
Phase 0               Phase 1–2            Phase 3–4            Phase 5
Identity              Passport             eKYC +               National
Foundation            + Sharing            Marketplace          Platform
(2–3 tuần)            (4–6 tuần)           (9–12 tuần)          (3–6 tháng)
```

### 14.2 Milestone gate — điều kiện chuyển phase

| Gate | Điều kiện bắt buộc trước khi proceed |
|---|---|
| **0 → 1** | 100% existing users có `account_type + trust_level` đúng, SnapshotJob pass CI tests |
| **1 → 2** | ≥ 10 passport_entries thực tế từ offboarding, không có data loss report |
| **2 → 3** | Public link hoạt động ổn định 2 tuần, zero leaked PII |
| **3 → 4** | eKYC phone đạt ≥ 80% success rate, uniqueness CCCD không có collision |
| **4 → 5** | ≥ 3 org dùng campaign thật, ≥ 30 participations completed |

### 14.3 Câu hỏi mở — cần quyết định sản phẩm

| # | Câu hỏi | Tác động |
|---|---|---|
| 1 | Nhân viên có thể xem snapshot trước khi HR xác nhận không? | UX vs data trust |
| 2 | Org có quyền "thu hồi xác nhận" (org_verified = false) không? | Policy offboarding |
| 3 | Khi user yêu cầu xóa tài khoản (GDPR): xóa hay ẩn danh hóa passport? | Legal compliance |
| 4 | Campaign: tính phí org khi "mở khoá" danh tính ứng viên? | Business model |
| 5 | `rc_candidates` có được liên kết với `users.id` không? | ATS integration |
| 6 | National Competency Index: data public hay chỉ cho org đăng ký? | Go-to-market |

---

*Tài liệu này là Living Specification — cập nhật khi có quyết định sản phẩm mới.*  
*Mọi schema change phải có migration riêng, không sửa migration đã chạy.*

*Competency Passport Platform · v2.0 · 13/06/2026*
