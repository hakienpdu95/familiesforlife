# PLATFORM DESIGN — Workforce Intelligence Platform

> **Phiên bản:** v2.0 — 2026-06-16
> **Mục đích:** Single source of truth cho thiết kế, phát triển và cải tiến nền tảng.
> **Tài liệu liên quan:** `docs/thuchoc-system-spec.md`

---

## Mục lục

1. [Tầm nhìn & Định vị Nền tảng](#1-tầm-nhìn--định-vị-nền-tảng)
2. [Mô hình Kiến trúc](#2-mô-hình-kiến-trúc)
3. [Technology Stack](#3-technology-stack)
4. [Bản đồ Module](#4-bản-đồ-module)
5. [Mô hình Dữ liệu Cốt lõi](#5-mô-hình-dữ-liệu-cốt-lõi)
6. [Vòng lặp Nghiệp vụ Nền tảng](#6-vòng-lặp-nghiệp-vụ-nền-tảng)
7. [Vertical Extension Model](#7-vertical-extension-model)
8. [Custom Assessment Framework](#8-custom-assessment-framework)
9. [Lộ trình Phát triển — Phases](#9-lộ-trình-phát-triển--phases)
10. [Thiết kế Cơ sở Dữ liệu](#10-thiết-kế-cơ-sở-dữ-liệu)
11. [Cấu hình theo Loại hình Tổ chức](#11-cấu-hình-theo-loại-hình-tổ-chức)
12. [Quy ước Phát triển](#12-quy-ước-phát-triển)

---

## 1. Tầm nhìn & Định vị Nền tảng

### 1.1 Tuyên ngôn

> **THUCHOCVN Platform** là hệ thống SaaS đa thuê bao giúp bất kỳ tổ chức nào — dù là doanh nghiệp sản xuất, công ty dịch vụ, HTX nông nghiệp, trường học hay tổ chức phi lợi nhuận — **đo lường năng lực số, đánh giá hiệu suất nhân viên và xây dựng lộ trình phát triển đội ngũ** thông qua dữ liệu thực tế và AI.

Các tính năng chuyên biệt theo ngành (truy xuất nguồn gốc, quản lý tài sản vật lý, tuân thủ tiêu chuẩn...) được tổ chức như **Vertical Extensions** — plugin opt-in, bật/tắt theo nhu cầu từng tổ chức, không ảnh hưởng đến nền tảng lõi.

### 1.2 Ba lớp giá trị

```
╔══════════════════════════════════════════════════════════════════╗
║  LỚP 1 — PLATFORM CORE (universal — mọi loại tổ chức)          ║
║                                                                  ║
║  Khảo sát → Đánh giá năng lực → Digital Twin → Hiệu suất        ║
║  (Survey Engine + Assessment Engine + KPI + Review + Passport)   ║
╠══════════════════════════════════════════════════════════════════╣
║  LỚP 2 — VERTICAL EXTENSIONS (opt-in per org)                   ║
║                                                                  ║
║  V1: TXNG         V2: Consulting     V3: Manufacturing           ║
║  Truy xuất        Triển khai dự án   Quản lý dây chuyền          ║
║  nguồn gốc        cho khách hàng     sản xuất + ISO              ║
║                                                                  ║
║  V4: Education    V5: Healthcare     Vn: Custom...               ║
║  Chương trình     Năng lực           (future)                    ║
║  đào tạo          lâm sàng                                       ║
╠══════════════════════════════════════════════════════════════════╣
║  LỚP 3 — ECOSYSTEM (tích hợp ngoài)                             ║
║                                                                  ║
║  CheckVN API | Gov Portals | ERP | LMS | HR Systems | Webhooks  ║
╚══════════════════════════════════════════════════════════════════╝
```

### 1.3 Mục tiêu đo lường được (Platform OKRs)

| Mục tiêu | Metric | Module thực thi |
|---|---|---|
| Đánh giá năng lực số từng nhân viên | TDWCF score / 6 domain ≥ 80% tổ chức | Survey → Assessment → WorkforceProfile |
| Đánh giá hiệu suất nhân viên | KPI achievement % + Review score | KpiGoal + PerformanceReview |
| Xây dựng Digital Twin nhân viên | WorkforceProfile completeness ≥ 80% | Workforce module |
| Hỗ trợ nhiều loại hình tổ chức | ≥ 3 vertical khác nhau trong production | Vertical Extension system |
| Custom framework theo ngành | Org tự define domain/weight | AssessmentFrameworkConfig |

### 1.4 Đối tượng tổ chức mục tiêu

| Loại tổ chức | Đặc điểm | Vertical phù hợp |
|---|---|---|
| SME / Startup | <200 nhân viên, chuyển đổi số | Core + (Consulting nếu là đơn vị tư vấn) |
| HTX / Nông nghiệp | Nhân sự thực địa, truy xuất nguồn gốc | Core + V1:TXNG |
| Sản xuất / Chế biến | Dây chuyền, ISO, chứng nhận chất lượng | Core + V3:Manufacturing |
| Công ty tư vấn / IT | Triển khai dự án cho nhiều khách hàng | Core + V2:Consulting |
| Trường học / Đào tạo | Giáo viên, chương trình, năng lực giảng dạy | Core + V4:Education |
| Phòng khám / Bệnh viện | Năng lực lâm sàng, tuân thủ quy trình | Core + V5:Healthcare |
| Tổ chức phi lợi nhuận | Dự án, tình nguyện viên, impact | Core thuần |

---

## 2. Mô hình Kiến trúc

### 2.1 Kiến trúc phân lớp

```
┌─────────────────────────────────────────────────────────────────────┐
│  LAYER C — Ecosystem Integration                                    │
│  External APIs | Webhooks | ERP Connectors | Gov Portals            │
├─────────────────────────────────────────────────────────────────────┤
│  LAYER B — Reporting & Analytics                                    │
│  Report module | Cross-org Analytics | Export Adapters              │
├─────────────────────────────────────────────────────────────────────┤
│  LAYER A — AI & Automation                                          │
│  AiCopilot | WorkflowAutomation | SOP | AI Scoring Engine           │
├───────────────────────────────┬─────────────────────────────────────┤
│  LAYER 8 — Vertical: Ops Data │  LAYER 8v — Vertical: Project Mgmt  │
│  (V1/V3: physical assets)     │  (V2: client deployment)            │
│  production_sites/areas/lots  │  deployment_targets/checklists      │
├───────────────────────────────┴─────────────────────────────────────┤
│  LAYER 7 — Vertical Config                                          │
│  organization_verticals | vertical_configs | VerticalRegistry       │
├─────────────────────────────────────────────────────────────────────┤
│  LAYER 6 — Digital Twin & Certification                             │
│  WorkforceProfile | Sandbox | Certification | Career | Passport     │
├─────────────────────────────────────────────────────────────────────┤
│  LAYER 5 — Performance Management                                   │
│  KpiGoal | PerformanceReview | OrgChart | Leave                     │
├─────────────────────────────────────────────────────────────────────┤
│  LAYER 4 — Survey & Assessment Engine                               │
│  Survey (12 field types + templates) | Assessment (custom + built-in)│
├─────────────────────────────────────────────────────────────────────┤
│  LAYER 3 — People & Organization Structure                          │
│  Employee | Branch | Department | JobTitle | RoleScope              │
├─────────────────────────────────────────────────────────────────────┤
│  LAYER 2 — Organization & Tenancy                                   │
│  Organization | OrganizationMember | TenantContext                  │
├─────────────────────────────────────────────────────────────────────┤
│  LAYER 1 — Foundation                                               │
│  Auth (Fortify+Sanctum) | RBAC (Spatie) | ActivityLog | Subscription│
└─────────────────────────────────────────────────────────────────────┘
```

### 2.2 Nguyên tắc kiến trúc

| Nguyên tắc | Áp dụng |
|---|---|
| **Multi-tenancy** | `TenantAwareModel` — `organization_id` global scope trên mọi domain model |
| **AVSA+CQRS-lite** | Action → Validator → Service → (Event). Không có logic trong Controller |
| **No JSON storage** | Dữ liệu có cấu trúc luôn có bảng riêng, không dùng JSON column |
| **No business prefix** | Tên bảng generic (`production_sites`, không phải `thv_zones`) |
| **Vertical isolation** | Vertical chỉ thêm bảng và routes mới, không sửa Platform Core |
| **Plugin OPT-IN** | Tổ chức chỉ thấy và dùng vertical mà họ đã đăng ký (qua `organization_verticals`) |
| **Soft deletes** | Mọi entity quan trọng dùng `SoftDeletes` |
| **UUID public** | `uuid` cho mọi bảng expose qua API/URL |
| **Immutable snapshot** | Dữ liệu lịch sử không sửa — tạo bản ghi mới |

### 2.3 Hệ thống Roles

```
Platform Roles (built-in, tất cả org đều có):
  system_admin    → Toàn quyền cấu hình platform
  ceo             → Toàn quyền xem + approve
  hr              → Quản lý nhân sự, review, leave
  ops             → Vận hành, KPI, workflow
  sales           → CRM, leads
  marketing       → Nội dung, marketplace
  ai_operator     → AI tools, copilot
  viewer          → Chỉ xem

Vertical Roles (thêm qua permissions khi bật vertical):
  [V1] txng_pm / txng_surveyor / txng_data_ops / txng_trainer
  [V2] consulting_pm / consulting_analyst
  [V3] production_manager / quality_inspector
  [V4] curriculum_admin / instructor       (future)
  [V5] clinical_manager / care_coordinator (future)
```

---

## 3. Technology Stack

| Lớp | Công nghệ | Phiên bản | Ghi chú |
|---|---|---|---|
| Backend | Laravel | 13 | PHP 8.4 |
| Module system | NWIDART Laravel Modules | latest | Mỗi domain = 1 module |
| Database | MySQL 8 (prod) / SQLite (dev) | — | |
| Frontend bundler | Vite | 8 | |
| CSS | Tailwind CSS + DaisyUI | 4/5 | |
| Reactive UI | Alpine.js | 3 | Không cần Vue/React |
| Table/Grid | Tabulator | latest | Server-side pagination, sort, filter |
| Dropdown | TomSelect | latest | |
| Date picker | Flatpickr | latest | |
| Charts | ApexCharts | latest | KPI, dashboard |
| Auth | Laravel Fortify + Sanctum | — | |
| RBAC | Spatie Laravel Permissions | — | Roles + Permissions |
| File storage | Spatie MediaLibrary | — | Polymorphic, GPS metadata |
| Queue | Laravel Queue (Redis/DB) | — | AI jobs, exports |
| AI drivers | Claude (primary), OpenAI (fallback), Mock (test) | — | AiCopilot module |
| Export | Maatwebsite Laravel Excel | 3.x | Cần cài — dùng cho mọi vertical export |

---

## 4. Bản đồ Module

### Ký hiệu
- `✅ DONE` — Hoàn chỉnh, production-ready
- `⚠️ PARTIAL` — Có cơ sở, còn gap cụ thể
- `🔧 NEEDED` — Cần xây, đã có design
- `📋 PLANNED` — Roadmap tương lai

---

### PLATFORM CORE — Áp dụng mọi loại tổ chức

#### Foundation (Layer 1)

| Module | Status | Mô tả |
|---|---|---|
| `Auth` | ✅ DONE | Login, register, 2FA, email verify, Fortify + Sanctum |
| `ActivityLog` | ✅ DONE | Audit trail có cấu trúc, level-based, org-scoped |

#### Tenancy & Organization (Layer 2)

| Module | Status | Mô tả |
|---|---|---|
| `Organization` | ✅ DONE | Org CRUD + settings + media + province/ward + members |
| `User` | ✅ DONE | User management, profile, org assignment |
| `Subscription` | ✅ DONE | Plan subscriptions, feature flags |
| `RoleScope` | ✅ DONE | Scoped RBAC: org/branch/dept level |
| `VerticalConfig` | 🔧 NEEDED | `organization_verticals` + registry — bật/tắt extensions |

#### People & Structure (Layer 3)

| Module | Status | Mô tả |
|---|---|---|
| `Branch` | ✅ DONE | Chi nhánh — materialized path, GPS, 3 cấp độ |
| `Department` | ✅ DONE | Phòng ban — parent/child, head |
| `JobTitle` | ✅ DONE | Chức danh — locked policy |
| `Employee` | ✅ DONE | Nhân viên — AVSA, SoftDeletes, Observer+snapshot, History trail |
| `OrgChart` | ✅ DONE | Sơ đồ tổ chức — views, group-by |
| `Leave` | ✅ DONE | Nghỉ phép — atomic, 3 tables, 16 routes |

#### Survey & Assessment Engine (Layer 4)

| Module | Status | Mô tả |
|---|---|---|
| `Survey` (engine) | ✅ DONE | 12 field types, sections, conditions, tokens, webhooks, draft |
| `Survey` (templates) | 🔧 NEEDED | `is_template` flag + `CloneSurveyAction` + gallery UI + seeders |
| `Assessment` (engine) | ✅ DONE | Custom frameworks, domains, bands, rules, personas, roadmap |
| `Assessment` (built-in: TDWCF) | ✅ DONE | 6 domain digital competency framework |
| `Assessment` (built-in: 5-Pillar) | ✅ DONE | Org maturity assessment |
| `AssessmentFrameworkConfig` | 🔧 NEEDED | Org-level custom framework definition (domain, weight, scoring) |
| `KcCategory` / `KcItem` | ✅ DONE | Kho kiến thức — 8 tables, versioning, approval |

#### Performance Management (Layer 5)

| Module | Status | Mô tả |
|---|---|---|
| `KpiGoal` | ✅ DONE | KPI thủ công — immutable snapshot, weight=100, leaderboard |
| `PerformanceReview` | ✅ DONE | Template + criteria (weighted) + 360° + snapshot |

#### Digital Twin & Certification (Layer 6)

| Module | Status | Mô tả |
|---|---|---|
| `WorkforceProfile` | ✅ DONE | Digital Twin — domain scores, trust score, history |
| `Sandbox` | ✅ DONE | Environments, tasks, sessions |
| `Certification` | ✅ DONE | Definitions, issuance, career pathway |
| `Passport` | ✅ DONE | Competency Passport — immutable snapshots, share token |

#### AI & Automation (Layer A)

| Module | Status | Mô tả |
|---|---|---|
| `AiCopilot` | ✅ DONE | Agents + prompts + drivers (Claude/OpenAI/Mock) |
| `WorkflowAutomation` | ✅ DONE | Trigger/condition/action engine |
| `Sop` | ✅ DONE | Processes + steps + RACI + versioning + approval |

#### Reporting (Layer B)

| Module | Status | Mô tả |
|---|---|---|
| `Report` | ⚠️ PARTIAL | HR report, Project KPI, Sales — cần mở rộng |
| `CrossOrgAnalytics` | 📋 PLANNED | Benchmarking ẩn danh giữa các tổ chức cùng ngành |

---

### VERTICAL EXTENSIONS — Opt-in per Organization

#### V1: TXNG — Truy xuất Nguồn gốc

| Component | Status | Mô tả |
|---|---|---|
| `production_sites` | 🔧 NEEDED | Cơ sở sản xuất — farm/factory/orchard/workshop |
| `production_areas` | 🔧 NEEDED | Khu vực — materialized path, GPS |
| `production_lots` | 🔧 NEEDED | Lô/phân khu |
| `production_items` | 🔧 NEEDED | Đơn vị — tree/machine/animal/station |
| `production_activity_logs` | 🔧 NEEDED | Nhật ký hoạt động — polymorphic subject |
| `production_legal_docs` | 🔧 NEEDED | Hồ sơ pháp lý — ĐKKD, OCOP, VietGAP... |
| `CheckVN Export Adapter` | 🔧 NEEDED | DM_CHUTHE/KHU/LO/CAY/NHATKY.xlsx |
| `CheckVN API Adapter` | 📋 PLANNED | Sync trực tiếp (thay Excel) |
| Survey template: `txng_readiness_v1` | 🔧 NEEDED | 20 câu — Hạ tầng, Nhân sự, Dữ liệu, Quy trình |
| Assessment: `TXNG_READINESS` | 🔧 NEEDED | 4 domains, scoring bands |
| Sandbox: 5 AI Agents TXNG | 🔧 NEEDED | Legal Collector, Farm Survey, Standardizer, Validator, Coach |
| Certification: TXNG Foundation/Practitioner/Pro | 🔧 NEEDED | 3 cấp độ chứng nhận |

#### V2: Consulting — Quản lý Triển khai Dự án Khách hàng

> Dùng cho: Công ty tư vấn, đơn vị dịch vụ IT, tổ chức NGO triển khai dự án cho nhiều bên thụ hưởng.

| Component | Status | Mô tả |
|---|---|---|
| `deployment_targets` | 🔧 NEEDED | Tổ chức khách hàng trong dự án — phase, progress |
| `deployment_checklist_items` | 🔧 NEEDED | Checklist từng phase — 1 row/item |
| `deployment_issues` | 🔧 NEEDED | Vấn đề phát sinh — severity, owner |
| `deployment_progress_logs` | 🔧 NEEDED | Nhật ký tiến độ |
| Survey template: `client_readiness_v1` | 🔧 NEEDED | Đánh giá sẵn sàng khách hàng |
| Survey template: `project_retrospective_v1` | 🔧 NEEDED | Retrospective sau dự án |

*Lưu ý: Project + Task modules (core) đã done — V2 chỉ thêm 4 bảng extension.*

#### V3: Manufacturing — Quản lý Sản xuất & Tuân thủ

> Dùng cho: Nhà máy, xưởng sản xuất, cơ sở chế biến, OCOP.

| Component | Status | Mô tả |
|---|---|---|
| `production_sites/areas/lots/items` | 🔧 NEEDED | Dùng chung với V1 (cùng schema, khác config) |
| `production_activity_logs` | 🔧 NEEDED | Dùng chung với V1 |
| `compliance_checklists` | 📋 PLANNED | ISO 9001, HACCP, ATTP checklist — per batch |
| Survey template: `iso_audit_v1` | 📋 PLANNED | Audit nội bộ ISO 9001 |
| Survey template: `haccp_inspection_v1` | 📋 PLANNED | Kiểm tra HACCP theo điểm kiểm soát |
| Assessment: `MFG_CAPABILITY` | 📋 PLANNED | Năng lực vận hành máy, kỹ thuật sản xuất |

#### V4: Education — Quản lý Đào tạo

> Dùng cho: Trường học, trung tâm đào tạo, nội bộ L&D.

| Component | Status | Mô tả |
|---|---|---|
| `learning_programs` | 📋 PLANNED | Chương trình đào tạo — modules, outcomes |
| `learning_enrollments` | 📋 PLANNED | Đăng ký khóa học — status, completion |
| Assessment: `TEACHING_COMPETENCY` | 📋 PLANNED | Năng lực giảng dạy, thiết kế chương trình |
| Survey template: `learner_feedback_v1` | 📋 PLANNED | Phản hồi học viên |
| Survey template: `instructor_360_v1` | 📋 PLANNED | Đánh giá 360° giảng viên |

#### V5: Healthcare — Năng lực Lâm sàng

> Dùng cho: Phòng khám, bệnh viện, cơ sở y tế.

| Component | Status | Mô tả |
|---|---|---|
| `care_pathways` | 📋 PLANNED | Quy trình chăm sóc theo ca bệnh |
| Assessment: `CLINICAL_COMPETENCY` | 📋 PLANNED | Năng lực lâm sàng theo chuyên khoa |
| Survey template: `patient_feedback_v1` | 📋 PLANNED | Phản hồi bệnh nhân |
| Survey template: `clinical_audit_v1` | 📋 PLANNED | Audit quy trình lâm sàng |

---

## 5. Mô hình Dữ liệu Cốt lõi

### 5.1 Quan hệ Platform Core (universal)

```
Organization (bất kỳ loại hình)
│
├── organization_verticals[]     ← V1/V2/V3/... đã bật cho org này
├── assessment_framework_configs[] ← custom frameworks của org
│
├── OrganizationMember → User
│
├── Branch → Department → Employee
│                 ├── EmployeeHistory
│                 ├── KpiGoal
│                 ├── PerformanceReview
│                 ├── Leave
│                 └── WorkforceProfile
│                       ├── WorkforceProfileHistory
│                       ├── WorkforceCertification
│                       ├── WorkforcePortfolio
│                       └── SandboxSession
│
├── Survey
│     ├── SurveySection → SurveyField → SurveyFieldOption
│     ├── SurveyResponse → SurveyAnswer
│     └── AssessmentResult
│           ├── ResultDomainScore   (1 row/domain)
│           ├── ResultSignalFlag
│           ├── ResultPainPoint
│           ├── ResultRecommendation
│           └── ResultClassification
│
└── Project → Task → ProjectMember
```

### 5.2 Quan hệ Vertical Extensions (per vertical)

```
[V1/V3] Production Vertical:
  Organization
  └── ProductionSite
        └── ProductionArea (materialized path, GPS)
              └── ProductionLot
                    ├── ProductionItem
                    └── ProductionActivityLog

[V2] Consulting Vertical:
  Project
  └── DeploymentTarget → Organization (client org)
        ├── DeploymentChecklistItem (per phase)
        ├── DeploymentIssue
        └── DeploymentProgressLog
```

### 5.3 Assessment Framework — Built-in vs Custom

```
AssessmentFramework
├── [built-in] TDWCF          — 6 domains, individual digital competency
├── [built-in] ORG_5PILLAR    — 5 pillars, org maturity
├── [built-in] TXNG_READINESS — 4 domains, V1 vertical
└── [custom]   org-defined    — org define riêng domain/weight/scoring
      stored in: assessment_framework_configs
```

### 5.4 Công thức điểm

**Workforce Trust Score** (cá nhân):
```
TrustScore = TDWCF_score × 0.30
           + Certification_score × 0.25
           + KPI_achievement × 0.20
           + Sandbox_score × 0.15
           + Portfolio_score × 0.10
```

*Với Custom Framework: thay TDWCF_score bằng custom_framework_score.*

**Composite Certification Score**:
```
CertScore = Assessment × 0.30 + Sandbox × 0.25
          + AI_Impact × 0.25  + Portfolio × 0.20
```

---

## 6. Vòng lặp Nghiệp vụ Nền tảng

Vòng lặp này áp dụng cho mọi loại tổ chức — không gắn với ngành cụ thể.

```
Bước 1 — ONBOARD
  Tổ chức đăng ký → kích hoạt Vertical phù hợp → cài đặt Assessment Framework
           ↓
Bước 2 — STRUCTURE
  Xây cơ cấu: Branch → Department → JobTitle → Employee
           ↓
Bước 3 — SURVEY
  Chạy khảo sát (từ template hoặc tự xây) → multi-source: self / manager / peer / AI
           ↓
Bước 4 — SCORE
  AI Scoring Engine tính: domain scores → weighted total → maturity band → gap
           ↓
Bước 5 — TWIN
  Tạo / cập nhật Workforce Digital Twin (WorkforceProfile + history)
           ↓
Bước 6 — DEVELOP
  Tham gia Sandbox → thực hành → AI feedback → KPI tracking
           ↓
Bước 7 — MEASURE
  Đo lường kết quả: KpiGoal achievement + AiImpactSnapshot + Review cycle
           ↓
Bước 8 — CERTIFY
  Xét duyệt Certification → cấp → Career Pathway cập nhật
           ↓
Bước 9 — PUBLISH
  Passport entry → Marketplace profile (nếu muốn public)
           ↓
Bước 10 — REPORT
  Dashboard tổ chức → cross-org benchmarking → action plan
           ↓
           └──────────────────────────────────────┐
                                                  ↓ (quay lại Bước 3 — chu kỳ mới)
```

### 6.1 Vertical Loop — Tích hợp vào vòng lặp chính

Mỗi vertical bổ sung thêm bước chuyên biệt vào vòng lặp, không thay thế vòng lõi:

```
[V1: TXNG] chèn vào sau Bước 2:
  2a. Khảo sát TXNG Readiness → điểm → kế hoạch triển khai
  2b. Kiểm kê vùng sản xuất (Site → Area → Lot → Item + GPS + Media)
  2c. Thu hồ sơ pháp lý
  2d. Chuẩn hóa → Export → CheckVN

[V2: Consulting] chèn vào Bước 6:
  6a. Tạo DeploymentTarget per khách hàng
  6b. Chạy 6-phase checklist
  6c. Track issues + progress
  6d. Bàn giao + Certification TXNG cho khách hàng

[V3: Manufacturing] chèn vào Bước 6:
  6a. Ghi nhật ký sản xuất (production_activity_logs)
  6b. Compliance checklist theo lô/mẻ
  6c. Export báo cáo chất lượng
```

---

## 7. Vertical Extension Model

### 7.1 Vertical Registry

Mỗi Vertical được định nghĩa bằng một `VerticalDefinition` — một PHP class trong `app/Foundation/Verticals/`:

```php
// app/Foundation/Verticals/TxngVertical.php
class TxngVertical implements VerticalDefinition {
    public string $code = 'txng';
    public string $name = 'Truy xuất Nguồn gốc';
    public array $permissions = [
        'production.view', 'production.manage', 'production.export',
        'txng.view', 'txng.manage', 'txng.survey',
    ];
    public array $surveyTemplates = ['txng_readiness_v1', 'production_survey_v1'];
    public array $assessmentCodes = ['TXNG_READINESS'];
    public array $sandboxCampaigns = ['txng-legal-collector', 'txng-farm-survey', ...];
    public array $sidebarModules = ['production', 'txng-projects'];
    public array $roles = ['txng_pm', 'txng_surveyor', 'txng_data_ops', 'txng_trainer'];
}
```

### 7.2 Bảng `organization_verticals`

```sql
id               BIGINT UNSIGNED PK AUTO_INCREMENT
organization_id  BIGINT UNSIGNED NOT NULL FK→organizations RESTRICT
vertical_code    VARCHAR(30)     NOT NULL  -- 'txng' | 'consulting' | 'manufacturing'
status           VARCHAR(20)     NOT NULL DEFAULT 'active'
activated_at     TIMESTAMP       NOT NULL
activated_by     BIGINT UNSIGNED NULL FK→users SET NULL
config           TEXT            NULL      -- JSON là OK ở đây: chỉ metadata, không lưu data

UNIQUE (organization_id, vertical_code)
INDEX  (vertical_code, status)
```

*Lưu ý: `config` ở đây chỉ chứa metadata (active plan tier, feature flags) — không phải dữ liệu nghiệp vụ, nên JSON là hợp lệ.*

### 7.3 Middleware kiểm tra Vertical

```php
// app/Http/Middleware/RequireVertical.php
class RequireVertical {
    public function handle($request, Closure $next, string $verticalCode) {
        if (!TenantContext::org()->hasVertical($verticalCode)) {
            abort(403, 'Vertical không được kích hoạt cho tổ chức này.');
        }
        return $next($request);
    }
}

// Dùng trong routes:
Route::middleware(['auth', 'tenant', 'vertical:txng'])
     ->prefix('production')
     ->group(fn() => require __DIR__.'/verticals/txng.php');
```

### 7.4 Sidebar tự động theo Vertical

`config/permissions.php` đã có cấu trúc sidebar per role. Mở rộng để sidebar filter theo vertical đã bật:

```php
// config/verticals.php
'txng' => [
    'sidebar_modules' => ['production_sites', 'txng_projects', 'checkvn_export'],
    'dashboard_widgets' => ['production_summary', 'txng_progress'],
],
'consulting' => [
    'sidebar_modules' => ['deployment_projects', 'client_assessments'],
    'dashboard_widgets' => ['deployment_status', 'client_readiness_avg'],
],
```

---

## 8. Custom Assessment Framework

### 8.1 Tại sao cần Custom Framework

TDWCF đo năng lực số. Nhưng:
- Nhà máy cần đo: Vận hành máy, An toàn lao động, Kỹ năng bảo trì
- Trường học cần đo: Thiết kế bài giảng, Quản lý lớp, Đánh giá học sinh
- Phòng khám cần đo: Kỹ năng lâm sàng, Giao tiếp bệnh nhân, Tuân thủ quy trình

Framework engine đã generic (domain/weight/band). Chỉ cần cho phép org tạo config riêng.

### 8.2 Bảng `assessment_framework_configs`

```sql
id               BIGINT UNSIGNED PK AUTO_INCREMENT
uuid             CHAR(36)        UNIQUE
organization_id  BIGINT UNSIGNED NOT NULL FK→organizations RESTRICT
code             VARCHAR(50)     NOT NULL  -- 'MFG_OPS_V1', 'TEACH_COMP_V1'
name             VARCHAR(255)    NOT NULL
description      TEXT            NULL
aggregation_mode VARCHAR(30)     NOT NULL DEFAULT 'weighted_domain'
is_active        TINYINT(1)      NOT NULL DEFAULT 1
source           VARCHAR(20)     NOT NULL DEFAULT 'custom'  -- 'built_in' | 'custom'
created_by       BIGINT UNSIGNED NULL FK→users SET NULL
created_at       TIMESTAMP
updated_at       TIMESTAMP
deleted_at       TIMESTAMP NULL

UNIQUE (organization_id, code)
```

### 8.3 Bảng `framework_domains`

```sql
id                         BIGINT UNSIGNED PK AUTO_INCREMENT
framework_config_id        BIGINT UNSIGNED NOT NULL FK→assessment_framework_configs CASCADE
domain_code                VARCHAR(50)     NOT NULL
domain_name                VARCHAR(255)    NOT NULL
weight                     DECIMAL(5,4)    NOT NULL  -- tổng các domain = 1.0000
description                TEXT            NULL
sort_order                 TINYINT UNSIGNED NOT NULL DEFAULT 0

UNIQUE (framework_config_id, domain_code)
CONSTRAINT chk_weight CHECK (weight > 0 AND weight <= 1)
```

### 8.4 Bảng `framework_score_bands`

```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
framework_config_id BIGINT UNSIGNED NOT NULL FK→assessment_framework_configs CASCADE
band_code           VARCHAR(50)     NOT NULL
band_label          VARCHAR(100)    NOT NULL
score_min           DECIMAL(5,2)    NOT NULL
score_max           DECIMAL(5,2)    NOT NULL
color               VARCHAR(10)     NULL  -- hex color cho badge
sort_order          TINYINT UNSIGNED NOT NULL DEFAULT 0
```

### 8.5 Ví dụ Custom Framework — Sản xuất

```yaml
code: MFG_OPS_V1
name: Năng lực Vận hành Sản xuất
organization: Nhà máy ABC

domains:
  - code: D1_MACHINE_OP    weight: 0.25   # Vận hành máy móc
  - code: D2_SAFETY        weight: 0.25   # An toàn lao động
  - code: D3_QUALITY       weight: 0.20   # Kiểm soát chất lượng
  - code: D4_MAINTENANCE   weight: 0.15   # Bảo trì thiết bị
  - code: D5_PROCESS       weight: 0.15   # Tuân thủ quy trình SOP

score_bands:
  - Chưa đạt (0-49) | Cơ bản (50-69) | Thành thạo (70-84) | Chuyên gia (85-100)

survey_template: mfg_ops_assessment_v1  (tự tạo từ Survey builder)
```

### 8.6 Ví dụ Custom Framework — Giáo dục

```yaml
code: TEACH_COMP_V1
name: Năng lực Giảng dạy
organization: Trường XYZ

domains:
  - code: D1_CURRICULUM    weight: 0.20   # Thiết kế chương trình
  - code: D2_DELIVERY      weight: 0.25   # Phương pháp giảng dạy
  - code: D3_ASSESSMENT    weight: 0.20   # Đánh giá học sinh
  - code: D4_CLASSROOM     weight: 0.20   # Quản lý lớp học
  - code: D5_DEVELOPMENT   weight: 0.15   # Tự phát triển chuyên môn

score_bands:
  - Tập sự (0-59) | Đạt (60-74) | Khá (75-84) | Giỏi (85-100)
```

---

## 9. Lộ trình Phát triển — Phases

### Ký hiệu ưu tiên
- `[P0]` Blocking — làm ngay
- `[P1]` Core feature — sprint tiếp theo
- `[P2]` Enhancement
- `[P3]` Future

---

### Phase 0 — Infrastructure Foundation `✅ DONE`

Auth, Multi-tenancy, RBAC, UI Stack, ActivityLog, Subscription.

---

### Phase 1 — HR Core `✅ DONE`

Branch, Department, JobTitle, Employee, RoleScope, Leave, OrgChart.

---

### Phase 2A — Survey Engine `✅ DONE`

12 field types, sections, conditions, tokens, webhooks, export, draft.

---

### Phase 2B — Survey Templates `[P0 — NGAY]`

**Vấn đề:** Form tạo survey chỉ có 3 fields. Người dùng phải xây từ đầu. Template là điều kiện để mọi vertical hoạt động (TXNG readiness, Manufacturing audit, v.v.).

**Files cần tạo/sửa:**

```
Modules/Survey/database/migrations/
  XXXX_add_template_fields_to_surveys_table.php    ← is_template, template_category,
                                                      template_sort_order, template_description,
                                                      template_thumbnail

Modules/Survey/app/Enums/
  TemplateCategoryCode.php                          ← NEW

Modules/Survey/app/Actions/
  CloneSurveyAction.php                             ← NEW (deep-copy + tenant reassign)

Modules/Survey/app/Http/Controllers/
  SurveyController.php                              ← thêm clone() method

Modules/Survey/resources/views/surveys/
  create.blade.php                                  ← Step 0: template gallery trước form
  _template-gallery.blade.php                       ← NEW partial (Tabulator grid)

Modules/Survey/database/seeders/
  SurveyTemplateSeeder.php                          ← 10 templates (xem bên dưới)
```

**Template Categories:**

| Code | Nhóm | Áp dụng |
|---|---|---|
| `readiness` | Đánh giá sẵn sàng | TXNG, AI, Chuyển đổi số |
| `maturity` | Mức độ trưởng thành | 5-Pillar, năng lực ngành |
| `satisfaction` | Hài lòng | Nhân viên, học viên, bệnh nhân |
| `feedback` | Phản hồi | Onboarding, exit, retrospective |
| `audit` | Kiểm toán | ISO, HACCP, ATTP, quy trình nội bộ |
| `inventory` | Kiểm kê | Vùng sản xuất, tài sản, cơ sở vật chất |
| `performance` | Hiệu suất | 360°, đánh giá kỹ năng |
| `custom` | Tổ chức tự tạo | Lưu thành template tái dùng |

**10 Survey Templates cần seed (Platform Core + Verticals):**

| Code | Tên | Category | Vertical |
|---|---|---|---|
| `ai_readiness_v1` | AI & Workflow Readiness (TDWCF) | readiness | Core |
| `org_5pillar_v1` | Đánh giá 5 Trụ cột Tổ chức | maturity | Core |
| `employee_satisfaction_v1` | Khảo sát Hài lòng Nhân viên | satisfaction | Core |
| `exit_interview_v1` | Phỏng vấn Thôi việc | feedback | Core |
| `onboarding_feedback_v1` | Phản hồi Onboarding | feedback | Core |
| `performance_360_v1` | Đánh giá 360° | performance | Core |
| `txng_readiness_v1` | TXNG Readiness Assessment | readiness | V1 |
| `production_inventory_v1` | Khảo sát Vùng Sản xuất | inventory | V1/V3 |
| `client_readiness_v1` | Đánh giá Sẵn sàng Khách hàng | readiness | V2 |
| `project_retrospective_v1` | Retrospective Dự án | feedback | V2 |

---

### Phase 3 — Assessment Engine `✅ DONE`

Engine, TDWCF, 5-Pillar, Digital Twin, Sandbox, Certification, Career Pathway, Passport.

**Gap cần bổ sung [P1]:**
- `assessment_framework_configs` + `framework_domains` + `framework_score_bands` — enable Custom Framework
- Seed nội dung V1: TXNG_READINESS config + 5 Sandbox campaigns + 3 Cert definitions

---

### Phase 4 — Performance Management `✅ DONE`

KpiGoal, PerformanceReview.

**Gap cần bổ sung [P2]:**
- Link `PerformanceReview` → `WorkforceProfile`: khi finalize review → cập nhật `score_d6_performance`
- Liên kết `KpiGoal achievement` → `workforce_profiles.kpi_achievement_avg`

---

### Phase 5 — Vertical Config System `[P1]`

**Mục tiêu:** Cơ chế org bật/tắt vertical. Không có Phase này thì mọi org đều thấy mọi thứ.

```
app/Foundation/Verticals/
  VerticalDefinition.php        ← interface
  VerticalRegistry.php          ← singleton, load all vertical definitions
  TxngVertical.php              ← V1
  ConsultingVertical.php        ← V2

Modules/Organization/database/migrations/
  XXXX_create_organization_verticals_table.php

Modules/Organization/app/Http/Middleware/
  RequireVertical.php

Modules/Organization/resources/views/settings/
  verticals.blade.php           ← org admin bật/tắt verticals

config/verticals.php            ← sidebar + widgets per vertical
```

---

### Phase 6 — Custom Assessment Framework `[P1]`

**Mục tiêu:** Org tự define framework theo ngành (Manufacturing, Education, Healthcare).

```
Modules/Assessment/database/migrations/
  XXXX_create_assessment_framework_configs_table.php
  XXXX_create_framework_domains_table.php
  XXXX_create_framework_score_bands_table.php

Modules/Assessment/app/Actions/
  StoreFrameworkConfigAction.php
  CloneBuiltinFrameworkAction.php   ← clone TDWCF/5-Pillar làm điểm xuất phát

Modules/Assessment/resources/views/
  frameworks/
    index.blade.php
    create.blade.php              ← wizard: domains → weights → bands → preview
    edit.blade.php
```

---

### Phase 7 — V1: TXNG Production Data `[P1]`

**Module NWIDART:** `ProductionData`
**Route prefix:** `/dashboard/production`

Bao gồm: `production_sites`, `production_areas`, `production_lots`, `production_items`, `production_activity_logs`, `production_legal_docs`.

Xem schema chi tiết tại [Section 10.3](#103-production-data-v1v3).

---

### Phase 8 — V2: Consulting Deployment `[P1]`

**Extend `Project` module — không tạo module mới.**

Bao gồm: `deployment_targets`, `deployment_checklist_items`, `deployment_issues`, `deployment_progress_logs`.

6 deployment phases: `surveying → collecting → standardizing → importing → training → handover`

Xem schema chi tiết tại [Section 10.4](#104-consulting-deployment-v2).

---

### Phase 9 — Export Adapters `[P1]`

**Mục tiêu:** Export đúng format cho mỗi external system.

```bash
composer require maatwebsite/excel
```

**Export Adapter per Vertical:**

| Vertical | Adapter | Output |
|---|---|---|
| V1: TXNG | `CheckVnExportAdapter` | 5 Excel files (DM_CHUTHE/KHU/LO/CAY/NHATKY) → ZIP |
| V2: Consulting | `DeploymentReportAdapter` | PDF báo cáo tiến độ per khách hàng |
| V3: Manufacturing | `QualityReportAdapter` | Excel báo cáo chất lượng lô sản xuất |
| Core | `WorkforceReportAdapter` | Excel HR report, KPI dashboard |

**Pattern:** Mỗi adapter implement `ExportAdapterContract` — cùng interface, khác nội dung.

---

### Phase 10 — Vertical Content Seeding `[P2]`

Seed nội dung đầy đủ cho từng vertical đã build:

| Vertical | Nội dung cần seed |
|---|---|
| V1: TXNG | TXNG_READINESS assessment config, 5 Sandbox AI agents, 3 Cert definitions, 2 survey templates |
| V2: Consulting | client_readiness + retrospective templates, deployment SOP |
| V3: Manufacturing | MFG_OPS_V1 framework example, production_inventory template |

---

### Phase 11 — Advanced Features `[P3]`

| Hạng mục | Mô tả |
|---|---|
| Cross-org benchmarking | So sánh ẩn danh TDWCF/custom scores giữa các org cùng ngành |
| AI recommendations 2.0 | Fine-tuned trên dữ liệu thực từ nhiều org |
| PWA / Offline mode | Nhập liệu thực địa không cần internet |
| CheckVN API sync | Thay Excel bằng API trực tiếp |
| V4: Education | Learning programs, enrollment, teaching competency |
| V5: Healthcare | Care pathway, clinical competency |
| Mobile app | React Native cho field officers |

---

## 10. Thiết kế Cơ sở Dữ liệu

### 10.1 Quy ước chung

```
- Mọi bảng: id (PK), uuid (unique public), organization_id (FK, tenant scope)
- Soft deletes: deleted_at trên entity quan trọng
- Audit: created_by, updated_by (FK → users)
- Timestamps: created_at, updated_at
- Không lưu dữ liệu nghiệp vụ có cấu trúc trong JSON
- Tên bảng: snake_case, số nhiều, không prefix ngành
- Enum: PHP 8.1 Enum class + CHECK constraint MySQL
- Index: (organization_id, status), (organization_id, type)
```

### 10.2 Survey Template Extension

```sql
-- Thêm vào bảng surveys:
ALTER TABLE surveys
  ADD COLUMN is_template          TINYINT(1)    NOT NULL DEFAULT 0  AFTER slug,
  ADD COLUMN template_category    VARCHAR(50)   NULL                AFTER is_template,
  ADD COLUMN template_sort_order  SMALLINT UNSIGNED DEFAULT 0      AFTER template_category,
  ADD COLUMN template_description TEXT          NULL                AFTER template_sort_order,
  ADD COLUMN template_thumbnail   VARCHAR(255)  NULL                AFTER template_description;

CREATE INDEX idx_surveys_template ON surveys (is_template, template_category);
```

### 10.3 Production Data (V1/V3)

#### `production_sites`
```sql
id               BIGINT UNSIGNED PK AUTO_INCREMENT
uuid             CHAR(36)        UNIQUE
organization_id  BIGINT UNSIGNED NOT NULL FK→organizations RESTRICT
code             VARCHAR(50)     NOT NULL
name             VARCHAR(255)    NOT NULL
site_type        VARCHAR(30)     NOT NULL
  -- CHECK: farm|factory|workshop|orchard|fishery|livestock_farm|processing_plant|warehouse|other
status           VARCHAR(20)     NOT NULL DEFAULT 'active'
lat              DECIMAL(10,7)   NULL
lng              DECIMAL(10,7)   NULL
altitude         DECIMAL(8,2)    NULL
area_sqm         DECIMAL(12,2)   NULL
address          VARCHAR(500)    NULL
province_code    CHAR(2)         NULL
ward_code        CHAR(5)         NULL
established_at   DATE            NULL
manager_id       BIGINT UNSIGNED NULL FK→employees SET NULL
created_by       BIGINT UNSIGNED NULL FK→users SET NULL
updated_by       BIGINT UNSIGNED NULL FK→users SET NULL
created_at       TIMESTAMP
updated_at       TIMESTAMP
deleted_at       TIMESTAMP NULL

UNIQUE (organization_id, code)
INDEX  (organization_id, site_type, status)
```

#### `production_areas`
```sql
id               BIGINT UNSIGNED PK AUTO_INCREMENT
uuid             CHAR(36)        UNIQUE
organization_id  BIGINT UNSIGNED NOT NULL FK→organizations RESTRICT
site_id          BIGINT UNSIGNED NOT NULL FK→production_sites RESTRICT
parent_area_id   BIGINT UNSIGNED NULL FK→production_areas RESTRICT
path             VARCHAR(255)    NOT NULL DEFAULT '/'   -- materialized path
depth            TINYINT UNSIGNED NOT NULL DEFAULT 0
code             VARCHAR(50)     NOT NULL
name             VARCHAR(255)    NOT NULL
area_type        VARCHAR(30)     NOT NULL
  -- CHECK: growing_zone|processing_zone|storage_zone|work_area|cage_zone|nursery|production_line|other
status           VARCHAR(20)     NOT NULL DEFAULT 'active'
lat              DECIMAL(10,7)   NULL
lng              DECIMAL(10,7)   NULL
area_sqm         DECIMAL(12,2)   NULL
created_by       BIGINT UNSIGNED NULL FK→users SET NULL
updated_by       BIGINT UNSIGNED NULL FK→users SET NULL
created_at       TIMESTAMP
updated_at       TIMESTAMP
deleted_at       TIMESTAMP NULL

UNIQUE (site_id, code)
INDEX  (organization_id, path)
```

#### `production_lots`
```sql
id                  BIGINT UNSIGNED PK AUTO_INCREMENT
uuid                CHAR(36)        UNIQUE
organization_id     BIGINT UNSIGNED NOT NULL FK→organizations RESTRICT
area_id             BIGINT UNSIGNED NOT NULL FK→production_areas RESTRICT
code                VARCHAR(50)     NOT NULL
name                VARCHAR(255)    NOT NULL
lot_type            VARCHAR(30)     NOT NULL
  -- CHECK: cultivation_lot|processing_batch|work_station|cage_lot|machine_group|artisan_unit|other
status              VARCHAR(20)     NOT NULL DEFAULT 'active'
lat                 DECIMAL(10,7)   NULL
lng                 DECIMAL(10,7)   NULL
area_sqm            DECIMAL(12,2)   NULL
item_count_target   SMALLINT UNSIGNED NULL
item_count_actual   SMALLINT UNSIGNED NULL DEFAULT 0
started_at          DATE            NULL
certified_at        DATE            NULL
created_by          BIGINT UNSIGNED NULL FK→users SET NULL
updated_by          BIGINT UNSIGNED NULL FK→users SET NULL
created_at          TIMESTAMP
updated_at          TIMESTAMP
deleted_at          TIMESTAMP NULL

UNIQUE (area_id, code)
INDEX  (organization_id, lot_type, status)
```

#### `production_items`
```sql
id               BIGINT UNSIGNED PK AUTO_INCREMENT
uuid             CHAR(36)        UNIQUE
organization_id  BIGINT UNSIGNED NOT NULL FK→organizations RESTRICT
lot_id           BIGINT UNSIGNED NOT NULL FK→production_lots RESTRICT
code             VARCHAR(50)     NOT NULL
name             VARCHAR(255)    NULL
item_type        VARCHAR(30)     NOT NULL
  -- CHECK: plant|tree|machine|equipment|animal|artisan_station|other
age_category     VARCHAR(20)     NULL
  -- CHECK: seedling|juvenile|mature|aged|unknown
condition_status VARCHAR(20)     NOT NULL DEFAULT 'good'
  -- CHECK: excellent|good|fair|poor|damaged
lat              DECIMAL(10,7)   NULL
lng              DECIMAL(10,7)   NULL
height_cm        DECIMAL(8,2)    NULL
weight_kg        DECIMAL(8,3)    NULL
born_at          DATE            NULL
planted_at       DATE            NULL
last_inspected_at DATE           NULL
is_active        TINYINT(1)      NOT NULL DEFAULT 1
created_by       BIGINT UNSIGNED NULL FK→users SET NULL
updated_by       BIGINT UNSIGNED NULL FK→users SET NULL
created_at       TIMESTAMP
updated_at       TIMESTAMP
deleted_at       TIMESTAMP NULL

UNIQUE (lot_id, code)
INDEX  (organization_id, item_type, condition_status)
```

#### `production_activity_logs`
```sql
id                        BIGINT UNSIGNED PK AUTO_INCREMENT
uuid                      CHAR(36)        UNIQUE
organization_id           BIGINT UNSIGNED NOT NULL FK→organizations RESTRICT
subject_type              VARCHAR(30)     NOT NULL  -- 'site'|'area'|'lot'|'item'
subject_id                BIGINT UNSIGNED NOT NULL
activity_type             VARCHAR(30)     NOT NULL
  -- CHECK: watering|fertilizing|pesticide|harvesting|pruning|transplanting|maintenance
  --        |inspection|processing|packaging|certification_check|feeding|vet_check|other
performed_at              DATE            NOT NULL
performed_by_employee_id  BIGINT UNSIGNED NULL FK→employees SET NULL
quantity                  DECIMAL(12,3)   NULL
unit                      VARCHAR(20)     NULL
cost                      DECIMAL(15,2)   NULL
currency                  CHAR(3)         NOT NULL DEFAULT 'VND'
notes                     TEXT            NULL
verified_by_employee_id   BIGINT UNSIGNED NULL FK→employees SET NULL
verified_at               TIMESTAMP       NULL
created_by                BIGINT UNSIGNED NULL FK→users SET NULL
created_at                TIMESTAMP
updated_at                TIMESTAMP

INDEX (organization_id, activity_type, performed_at)
INDEX (subject_type, subject_id, performed_at)
```

#### `production_legal_docs`
```sql
id               BIGINT UNSIGNED PK AUTO_INCREMENT
uuid             CHAR(36)        UNIQUE
organization_id  BIGINT UNSIGNED NOT NULL FK→organizations RESTRICT
site_id          BIGINT UNSIGNED NULL FK→production_sites SET NULL
doc_type         VARCHAR(50)     NOT NULL
  -- CHECK: business_registration|personal_id|tax_code|ocop_cert|food_safety_cert
  --        |vietgap_cert|organic_cert|export_cert|land_use_right|other
doc_number       VARCHAR(100)    NULL
doc_name         VARCHAR(255)    NOT NULL
issued_by        VARCHAR(255)    NULL
issued_at        DATE            NULL
expires_at       DATE            NULL
status           VARCHAR(20)     NOT NULL DEFAULT 'active'
notes            TEXT            NULL
created_by       BIGINT UNSIGNED NULL FK→users SET NULL
updated_by       BIGINT UNSIGNED NULL FK→users SET NULL
created_at       TIMESTAMP
updated_at       TIMESTAMP
deleted_at       TIMESTAMP NULL

INDEX (organization_id, doc_type, status)
INDEX (organization_id, expires_at)
```

### 10.4 Consulting Deployment (V2)

#### `deployment_targets`
```sql
id                      BIGINT UNSIGNED PK AUTO_INCREMENT
project_id              BIGINT UNSIGNED NOT NULL FK→projects RESTRICT
organization_id         BIGINT UNSIGNED NOT NULL FK→organizations RESTRICT
current_phase           VARCHAR(30)     NOT NULL DEFAULT 'surveying'
  -- CHECK: surveying|collecting|standardizing|importing|training|handover|completed
status                  VARCHAR(20)     NOT NULL DEFAULT 'active'
deployed_by_employee_id BIGINT UNSIGNED NULL FK→employees SET NULL
notes                   TEXT            NULL
started_at              DATE            NULL
completed_at            DATE            NULL
created_by              BIGINT UNSIGNED NULL FK→users SET NULL
updated_by              BIGINT UNSIGNED NULL FK→users SET NULL
created_at              TIMESTAMP
updated_at              TIMESTAMP
deleted_at              TIMESTAMP NULL

UNIQUE (project_id, organization_id)
INDEX  (project_id, current_phase)
```

#### `deployment_checklist_items`
```sql
id                        BIGINT UNSIGNED PK AUTO_INCREMENT
deployment_target_id      BIGINT UNSIGNED NOT NULL FK→deployment_targets CASCADE
phase_code                VARCHAR(30)     NOT NULL
item_code                 VARCHAR(50)     NOT NULL
label                     VARCHAR(255)    NOT NULL
is_completed              TINYINT(1)      NOT NULL DEFAULT 0
completed_by_employee_id  BIGINT UNSIGNED NULL FK→employees SET NULL
completed_at              TIMESTAMP       NULL
notes                     VARCHAR(500)    NULL
sort_order                TINYINT UNSIGNED NOT NULL DEFAULT 0
created_at                TIMESTAMP
updated_at                TIMESTAMP

UNIQUE (deployment_target_id, item_code)
INDEX  (deployment_target_id, phase_code)
```

#### `deployment_issues`
```sql
id                    BIGINT UNSIGNED PK AUTO_INCREMENT
uuid                  CHAR(36)        UNIQUE
organization_id       BIGINT UNSIGNED NOT NULL FK→organizations RESTRICT
project_id            BIGINT UNSIGNED NOT NULL FK→projects RESTRICT
deployment_target_id  BIGINT UNSIGNED NOT NULL FK→deployment_targets CASCADE
title                 VARCHAR(255)    NOT NULL
description           TEXT            NULL
severity              VARCHAR(20)     NOT NULL DEFAULT 'medium'
  -- CHECK: low|medium|high|critical
status                VARCHAR(20)     NOT NULL DEFAULT 'open'
  -- CHECK: open|in_progress|resolved|closed
phase_code            VARCHAR(30)     NULL
owner_id              BIGINT UNSIGNED NULL FK→employees SET NULL
resolved_at           TIMESTAMP       NULL
resolution_notes      TEXT            NULL
created_by            BIGINT UNSIGNED NULL FK→users SET NULL
updated_by            BIGINT UNSIGNED NULL FK→users SET NULL
created_at            TIMESTAMP
updated_at            TIMESTAMP
deleted_at            TIMESTAMP NULL

INDEX (project_id, severity, status)
INDEX (deployment_target_id, status)
```

#### `deployment_progress_logs`
```sql
id                    BIGINT UNSIGNED PK AUTO_INCREMENT
project_id            BIGINT UNSIGNED NOT NULL FK→projects RESTRICT
deployment_target_id  BIGINT UNSIGNED NOT NULL FK→deployment_targets CASCADE
phase_code            VARCHAR(30)     NOT NULL
percent               TINYINT UNSIGNED NOT NULL DEFAULT 0
remark                TEXT            NULL
created_by            BIGINT UNSIGNED NULL FK→users SET NULL
created_at            TIMESTAMP

INDEX (deployment_target_id, phase_code, created_at)
CONSTRAINT chk_percent CHECK (percent BETWEEN 0 AND 100)
```

### 10.5 Custom Assessment Framework

```sql
-- assessment_framework_configs (xem Section 8.2)
-- framework_domains (xem Section 8.3)
-- framework_score_bands (xem Section 8.4)
```

### 10.6 Vertical Config

```sql
-- organization_verticals (xem Section 7.2)
```

### 10.7 Permissions mới cần thêm

```php
// Platform Core — Vertical System:
VERTICAL_MANAGE         = 'vertical.manage'       // org admin bật/tắt vertical

// Custom Framework:
FRAMEWORK_VIEW          = 'assessment.framework.view'
FRAMEWORK_MANAGE        = 'assessment.framework.manage'

// V1 Production Data:
PRODUCTION_VIEW         = 'production.view'
PRODUCTION_MANAGE       = 'production.manage'
PRODUCTION_EXPORT       = 'production.export'
TXNG_SURVEY             = 'txng.survey'
TXNG_DATA               = 'txng.data'
TXNG_TRAIN              = 'txng.train'

// V2 Consulting Deployment:
DEPLOYMENT_VIEW         = 'deployment.view'
DEPLOYMENT_MANAGE       = 'deployment.manage'

// V3 Manufacturing:
COMPLIANCE_VIEW         = 'compliance.view'
COMPLIANCE_MANAGE       = 'compliance.manage'
```

---

## 11. Cấu hình theo Loại hình Tổ chức

### 11.1 SME / Startup (mọi ngành)

```
Verticals bật:  none (Core thuần)
Assessment:     TDWCF (cá nhân) + ORG_5PILLAR (tổ chức)
Survey path:    ai_readiness_v1 → org_5pillar_v1 → employee_satisfaction_v1
KPI focus:      Digital adoption rate, Task completion, Revenue per employee
Sandbox:        AI Workflow, Data Analysis, Process Optimization
Cert:           Digital Practitioner, AI Operator
```

### 11.2 HTX / Nông nghiệp

```
Verticals bật:  V1:txng
Assessment:     TDWCF (nhân viên) + TXNG_READINESS (org)
Survey path:    txng_readiness_v1 → production_inventory_v1 → employee_satisfaction_v1
Production:     site_type=farm, area_type=growing_zone, item_type=tree|plant|animal
KPI focus:      TXNG compliance %, harvest volume, doc upload rate
Sandbox:        TXNG Legal Collector, Farm Survey, Standardizer, Validator, Coach
Cert:           TXNG Foundation → Practitioner → Professional
Export:         DM_CHUTHE/KHU/LO/CAY/NHATKY.xlsx → CheckVN
```

### 11.3 Nhà máy / Sản xuất / Chế biến

```
Verticals bật:  V3:manufacturing (+ V1:txng nếu có truy xuất)
Assessment:     TDWCF + MFG_OPS_V1 (custom — tự tạo)
Survey path:    org_5pillar_v1 → production_inventory_v1 → performance_360_v1
Production:     site_type=factory|processing_plant, item_type=machine|equipment
KPI focus:      OEE (Overall Equipment Effectiveness), defect rate, safety incidents
Sandbox:        Process Optimizer, Quality Inspector (custom campaigns)
Cert:           Lean Operator, Quality Inspector, Safety Officer
Export:         Quality Report per lô, ISO audit report
```

### 11.4 Công ty Tư vấn / IT Services

```
Verticals bật:  V2:consulting
Assessment:     TDWCF + custom framework (Consulting Skills, Client Management)
Survey path:    client_readiness_v1 → project_retrospective_v1 → employee_satisfaction_v1
Production:     (không dùng)
KPI focus:      Project delivery rate, client satisfaction NPS, utilization rate
Sandbox:        Solution Design, Stakeholder Management (custom campaigns)
Deployment:     deployment_targets per client org, 6-phase checklist
Export:         Progress report PDF per client, Retrospective summary
```

### 11.5 Trường học / Đào tạo

```
Verticals bật:  V4:education (khi sẵn sàng)
Assessment:     TDWCF (cho staff) + TEACH_COMP_V1 (custom — cho giảng viên)
Survey path:    onboarding_feedback_v1 → performance_360_v1 → learner_feedback_v1
Production:     (không dùng)
KPI focus:      Student completion rate, instructor rating, curriculum coverage
Sandbox:        Curriculum Design, Assessment Design (custom campaigns)
Cert:           Junior Instructor → Senior Instructor → Master Trainer
```

### 11.6 Phòng khám / Y tế

```
Verticals bật:  V5:healthcare (khi sẵn sàng)
Assessment:     TDWCF (digital skills) + CLINICAL_COMP_V1 (custom — lâm sàng)
Survey path:    org_5pillar_v1 → performance_360_v1 → patient_feedback_v1
Production:     (không dùng)
KPI focus:      Patient satisfaction, protocol compliance, incident rate
Sandbox:        Clinical Procedure, Patient Communication (custom campaigns)
Cert:           Clinical Practitioner (by specialty)
```

### 11.7 Tổ chức Phi lợi nhuận / NGO

```
Verticals bật:  V2:consulting (nếu triển khai dự án cho bên thứ 3)
Assessment:     TDWCF + custom framework (Impact Management, Stakeholder Engagement)
Survey path:    employee_satisfaction_v1 → exit_interview_v1 + custom impact surveys
Production:     (không dùng)
KPI focus:      Beneficiary count, project impact score, volunteer retention
Sandbox:        Grant Writing, Community Engagement (custom campaigns)
Cert:           Social Impact Practitioner
```

### 11.8 Production Data — Mapping đầy đủ

| Loại hình | site_type | area_type | lot_type | item_type | activity_type chính |
|---|---|---|---|---|---|
| Nông nghiệp (trà, cà phê) | `farm` | `growing_zone` | `cultivation_lot` | `plant` / `tree` | watering, fertilizing, harvesting |
| Chăn nuôi | `livestock_farm` | `cage_zone` | `cage_lot` | `animal` | feeding, vet_check, inspection |
| Thủy sản | `fishery` | `growing_zone` | `cage_lot` | `animal` | feeding, harvesting, inspection |
| Sản xuất / Chế biến | `factory` | `production_line` | `machine_group` | `machine` | maintenance, processing, inspection |
| Thủ công mỹ nghệ | `workshop` | `work_area` | `artisan_unit` | `artisan_station` | processing, quality_check, packaging |
| Kho lạnh / OCOP | `warehouse` | `storage_zone` | `processing_batch` | `equipment` | processing, packaging, certification_check |

---

## 12. Quy ước Phát triển

### 12.1 Cấu trúc Module mới

```
Modules/ModuleName/
├── app/
│   ├── Actions/Backend/           # Store/Update/Destroy Actions
│   ├── Data/Requests/             # Spatie Data DTOs
│   ├── Enums/                     # PHP 8.1 Enums có label() + badgeClass()
│   ├── Events/                    # Domain events
│   ├── Http/
│   │   ├── Controllers/           # Web controllers (thin — only dispatch)
│   │   ├── Controllers/Api/       # JSON API for Tabulator
│   │   └── Resources/             # API Resources
│   ├── Listeners/                 # ActivityLogger calls
│   ├── Models/                    # extend TenantAwareModel
│   ├── Observers/                 # auto-code, materialized path, history trail
│   ├── Policies/                  # Authorization per role
│   ├── Providers/                 # ServiceProvider, EventServiceProvider, RouteServiceProvider
│   └── Queries/                   # ListQuery + ListHandler (CQRS-lite)
├── config/config.php
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── assets/js/                 # Alpine + Tabulator JS
│   └── views/                     # Blade templates
└── routes/
    ├── web.php
    └── api.php
```

### 12.2 Đặt tên

| Loại | Convention | Ví dụ |
|---|---|---|
| Bảng DB | `snake_case`, số nhiều, không prefix | `production_items` |
| Model | `PascalCase`, số ít | `ProductionItem` |
| Enum | `PascalCase`, values `snake_case` | `ItemType::plant` |
| Action | `VerbNounAction` | `StoreProductionItemAction` |
| Query/Handler | `ListNounsQuery` + `ListNounsHandler` | `ListProductionItemsQuery` |
| Event | `NounVerbed` | `ProductionItemCreated` |
| Route name | `backend.noun.action` | `backend.production.items.store` |
| Route path | `/dashboard/resource` | `/dashboard/production/items` |
| Vertical route | `/dashboard/vertical-code/resource` | `/dashboard/txng/sites` |

### 12.3 Vertical development checklist

Khi xây một Vertical mới:

- [ ] Tạo `VerticalDefinition` class trong `app/Foundation/Verticals/`
- [ ] Đăng ký trong `VerticalRegistry::$verticals`
- [ ] Tạo migration `organization_verticals` (bảng đã có, chỉ seed vertical_code mới)
- [ ] Thêm permissions mới vào `config/permissions.php` + `PermissionEnum`
- [ ] Tạo route file riêng trong `routes/verticals/{code}.php`, wrapped trong `RequireVertical` middleware
- [ ] Thêm sidebar config vào `config/verticals.php`
- [ ] Seed survey templates phù hợp với vertical
- [ ] Seed assessment framework config (built-in hoặc custom)
- [ ] Viết test: `VerticalAccessTest` — org không có vertical thì 403

### 12.4 Migration checklist

- [ ] Có `uuid`, `organization_id`, `created_by`, `updated_by`
- [ ] Enum là VARCHAR với CHECK constraint (không dùng MySQL ENUM type)
- [ ] `deleted_at` nếu entity cần SoftDeletes
- [ ] Index: `(organization_id, status)`, `(organization_id, type)` cho bảng lớn
- [ ] UNIQUE constraint đúng scope (thường include `organization_id`)

### 12.5 Thứ tự Seeder

```
1.  OrganizationDemoSeeder
2.  AuthDatabaseSeeder (roles, permissions)
3.  VerticalConfigSeeder          ← NEW (Phase 5)
4.  BranchDatabaseSeeder
5.  DepartmentDatabaseSeeder
6.  JobTitleDatabaseSeeder
7.  EmployeeDatabaseSeeder
8.  SurveyTemplateSeeder          ← Phase 2B (10 templates)
9.  AssessmentFrameworkSeeder     ← Phase 6 (TDWCF, 5-Pillar, + custom examples)
10. TdwcfAssessmentSeeder
11. FivePillarAssessmentSeeder
12. TxngReadinessAssessmentSeeder ← V1 vertical content
13. CertificationDefinitionSeeder
14. SandboxEnvironmentSeeder
15. CareerPathwaySeeder
16. WorkforceProfileSeeder        ← demo profiles
```

### 12.6 Trước khi merge

- [ ] Không có business logic trong Controller
- [ ] Enum có `label()` + `badgeClass()` + `color()`
- [ ] Policy đăng ký + test cover happy + unauthorized paths
- [ ] Observer sinh auto-code nếu cần
- [ ] ActivityLog listener đã có
- [ ] Không dùng `->select('*')` — dùng explicit columns
- [ ] Filter/sort đi qua `ListHandler`, không inline trong Controller
- [ ] Vertical routes có `RequireVertical` middleware
- [ ] Vite entry đăng ký trong `vite.config.backend.js`

---

## Phụ lục — Tiến độ Tổng thể

```
PLATFORM CORE:
  Phase 0  Foundation             ████████████ 100%  DONE
  Phase 1  HR Core                ████████████ 100%  DONE
  Phase 2A Survey Engine          ████████████ 100%  DONE
  Phase 2B Survey Templates       ░░░░░░░░░░░░   0%  ← P0: LÀM NGAY
  Phase 3  Assessment + Twin      ████████████ 100%  DONE (gap: custom framework)
  Phase 4  Performance Mgmt       ████████████ 100%  DONE (gap: link WorkforceProfile)
  Phase 5  Vertical Config System ░░░░░░░░░░░░   0%  P1
  Phase 6  Custom Framework       ░░░░░░░░░░░░   0%  P1

VERTICAL EXTENSIONS:
  Phase 7  V1: Production Data    ░░░░░░░░░░░░   0%  P1
  Phase 8  V2: Consulting Deploy  ████░░░░░░░░  30%  P1 (Project done, 4 bảng chờ)
  Phase 9  Export Adapters        ░░░░░░░░░░░░   0%  P1
  Phase 10 Vertical Content Seed  ░░░░░░░░░░░░   0%  P2

ADVANCED:
  Phase 11 Cross-org, PWA, AI 2.0 ░░░░░░░░░░░░   0%  P3
```

### Sprint gợi ý

| Sprint | Thời gian | Mục tiêu | Output |
|---|---|---|---|
| **A** | 4 ngày | Phase 2B: Survey Templates | 10 templates, CloneSurveyAction, gallery UI |
| **B** | 3 ngày | Phase 5: Vertical Config System | organization_verticals, middleware, sidebar |
| **C** | 3 ngày | Phase 6: Custom Framework | 3 bảng mới, wizard UI, 2 ví dụ seed |
| **D** | 5 ngày | Phase 7: V1 Production Data | 6 bảng, models, CRUD mobile-friendly |
| **E** | 3 ngày | Phase 8: V2 Consulting Deploy | 4 bảng, project extension views |
| **F** | 2 ngày | Phase 9: Export Adapters | maatwebsite/excel, CheckVN ZIP export |
| **G** | 2 ngày | Phase 10: Vertical Content | TXNG + Consulting seeders |
| **H** | 2 ngày | Phase 4 gap: PR ↔ WorkforceProfile | Link finalized review → D6 score |

**MVP sau Sprint A+B:** Survey templates hoạt động + vertical system cơ bản.
**Multi-org ready sau Sprint A–C:** Custom framework + vertical isolation.
**V1 TXNG complete sau Sprint A–G:** Toàn bộ vertical truy xuất nguồn gốc.
