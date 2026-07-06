# So sánh hệ thống hiện tại với kiến trúc THUCHOCVN Vertical AI Platform — Đề xuất Module mới

> Tài liệu này so sánh codebase Laravel hiện tại (`/var/www/html/minhan`) với kiến trúc chuẩn được mô tả trong `docs/thuchoc/*` (đã tổng hợp tại [`00-TONG-HOP-TAI-LIEU.md`](./00-TONG-HOP-TAI-LIEU.md)), từ đó xác định **module nào cần bổ sung mới**, module nào cần **mở rộng/refactor**, và các **điểm lệch kiến trúc** cần lưu ý để đúng luồng logic đã chốt.
>
> Phương pháp: đọc trực tiếp code trong `Modules/*`, `app/Foundation/*`, `app/Shared/*`, `config/permissions.php`, `config/modules.php`, `modules_statuses.json` — đối chiếu từng khái niệm/nguyên tắc trong 14 tài liệu gốc.

---

## Phần 0. Tóm tắt kết luận (đọc nhanh)

Hệ thống hiện tại là một Laravel Modular Monolith **đã triển khai rất nhiều** (34 module, ~99 permission, RBAC 8 role) — nhiều hơn phạm vi MVP mà tài liệu THUCHOCVN đề xuất. Tuy nhiên, kiến trúc hiện tại được xây dựng theo hướng **"mỗi Vertical/Solution = một module Laravel viết tay riêng"** (Deployment cho TXNG, Assessment cho Workforce, OcopRubric cho OCOP), **chứ không phải** theo mô hình chuẩn của tài liệu: một **Blueprint Engine dữ liệu hoá dùng chung** mà mọi Vertical đều tái sử dụng.

Điều đó có nghĩa: 6 khái niệm lõi của tài liệu —

```
Vertical → Business Solution → Business Blueprint → Organization Configuration → Deployment → Project Runtime
```

— hiện chỉ được hiện thực **một phần và bị trộn lẫn vào nhau**. Cụ thể:

| Khái niệm tài liệu | Trạng thái hiện tại |
|---|---|
| Vertical | ✅ Có (`app/Foundation/VerticalRegistry`, `VerticalTemplate`) nhưng bị **trộn chung với Business Blueprint** |
| Business Solution | ❌ **Không tồn tại** như một thực thể độc lập — mỗi "solution" là 1 module code cứng |
| Business Blueprint (+ Version/Publish) | ⚠️ Có bản rút gọn (`VerticalTemplate` + `VerticalPhase` + `VerticalChecklistItem`) nhưng **thiếu Outcomes, Capabilities, AI Capabilities, Analytics Definition, Versioning/Publish lifecycle** |
| Organization Solution (kích hoạt) | ❌ **Không tồn tại** như bảng riêng — trạng thái kích hoạt bị gắn thẳng vào `VerticalTemplate` (bản sao/clone theo tổ chức) |
| Organization Configuration (overlay) | ❌ **Không tồn tại** như lớp tách biệt — "cấu hình" hiện tại = **sửa trực tiếp lên bản clone**, vi phạm nguyên tắc DP-04/BR-013 của tài liệu |
| Deployment (Engine + log + snapshot) | ⚠️ Có `Modules/Deployment` nhưng đây thực chất là **Project Runtime**, không phải Deployment Engine; không có `deployments/deployment_logs/deployment_snapshots` |
| Project Runtime | ✅ Có, khá đầy đủ (`Project`, `DeploymentTarget`, `DeploymentChecklistItem`, `DeploymentIssue`, `DeploymentProgressLog`) |
| AI Capability (Blueprint-level) | ❌ Không có bảng liên kết Blueprint↔AI; `AiCopilot` có Agent/Prompt nhưng đứng độc lập |
| Knowledge | ✅ Có, ánh xạ tốt (`KcCategory`, `KcItem`) |
| SOP | ✅ Có, ánh xạ tốt và đầy đủ hơn tài liệu mô tả (`Sop`) |
| Dashboard/Report (Blueprint-driven) | ⚠️ Có `Report` nhưng là **report hard-code theo từng domain** (HR/Sales/ProjectKpi/Competency), không phải Dashboard Definition → Runtime chung |
| Billing/Subscription | ✅ Có, đầy đủ hơn tài liệu (`Subscription`) |
| Activity Log | ✅ Có, đầy đủ (`ActivityLog`) |
| Marketplace (Business Solution) | ⚠️ **Trùng tên nhưng khác domain** — `Marketplace` hiện tại là marketplace tuyển dụng (job/candidate), không phải nơi phân phối Business Solution |
| Workflow (Blueprint Process) | ⚠️ **Trùng tên nhưng khác khái niệm** — `WorkflowAutomation` là automation engine (trigger/condition/action kiểu BPMN), không phải Workflow/Phase/Checklist của Blueprint |

**5 module MỚI cần bổ sung** (chi tiết ở Phần 3): `BusinessSolution`, `BusinessBlueprint`, `OrganizationSolution`, mở rộng hình thức hoá `Deployment` thành Deployment Engine thật, và một `SolutionCatalog` (đổi tên khái niệm) để tránh đá tên với `Marketplace` tuyển dụng hiện tại.

---

## Phần 1. Kiểm kê hệ thống hiện tại

### 1.1 Danh sách 34 module đang bật (`modules_statuses.json`)

```
Auth, Organization, User, Survey, ActivityLog, WorkflowAutomation,
Lead, LeadPipelineStage, LeadSource, Assessment, Branch, Department,
JobTitle, Employee, RoleScope, PerformanceReview, Project, OrgChart,
KcCategory, KcItem, Sop, Leave, KpiGoal, JobPosting, Recruitment,
Marketplace, Task, Subscription, Customer, AiCopilot, Report,
Deployment, OcopRubric
```

Ghi chú quan trọng: **CLAUDE.md hiện ghi "Other domain modules (CRM, Tasks, SOP, Workflow, etc.) have placeholder routes returning 503 stubs and are not yet implemented"** — thông tin này **đã lỗi thời**. Thực tế Sop, Task, WorkflowAutomation, Lead/Customer (CRM) đều đã cài đặt đầy đủ Model/Controller/migration/view, không còn là stub 503. Nên cập nhật lại CLAUDE.md ở dịp khác.

### 1.2 Nhóm module theo domain thực tế

| Nhóm | Module | Vai trò thực tế |
|---|---|---|
| **Nền tảng danh tính/tenant** | Auth, User, Organization, RoleScope | Identity + Organization Module (khớp A02 §5.1–5.2) |
| **Vertical/Blueprint (rút gọn)** | `app/Foundation/Vertical/*`, `app/Foundation/VerticalRegistry`, `app/Foundation/VerticalDefinition` | Vertical + Blueprint bị gộp làm một |
| **Runtime vận hành (TXNG)** | Deployment (+ Project, Survey) | Đóng vai trò "Project Runtime" cho vertical truy xuất nguồn gốc/OCOP-readiness |
| **Solution bespoke #2** | Assessment | Toàn bộ "AI Workforce Intelligence" (Assessment/Certification/Sandbox/Career Pathway/Passport) — code cứng riêng |
| **Solution bespoke #3** | OcopRubric | Rubric OCOP + Scoring Session — code cứng riêng, mới nhất |
| **Automation** | WorkflowAutomation | Rule engine (trigger→condition→action), KHÔNG phải Blueprint Workflow |
| **Tri thức** | KcCategory, KcItem | Knowledge Module |
| **SOP** | Sop | SOP Module (đầy đủ version/RACI/approval/flowchart) |
| **AI** | AiCopilot | AI Module (Agent/Prompt/Request/Usage, đa driver Claude/OpenAI/Mock) |
| **Report/Dashboard** | Report | Report/Dashboard Module — nhưng hard-code theo domain, không theo Blueprint Analytics |
| **Billing** | Subscription | Billing & Subscription Module — đầy đủ (Plan/Invoice/Payment Gateway/Feature Gate) |
| **Audit** | ActivityLog | Activity Log Module |
| **Tuyển dụng (không thuộc phạm vi tài liệu)** | Marketplace, JobPosting, Recruitment | Marketplace tuyển dụng — **khác domain** với "Business Solution Marketplace" của tài liệu |
| **HR vận hành** | Branch, Department, JobTitle, Employee, Leave, PerformanceReview, OrgChart, KpiGoal | Không nằm trong phạm vi 14 tài liệu — nghiệp vụ HR nội bộ |
| **CRM** | Lead, LeadSource, LeadPipelineStage, Customer, Task | Không nằm trong phạm vi 14 tài liệu — nghiệp vụ CRM |

### 1.3 Cấu trúc "Vertical" hiện tại — chi tiết kỹ thuật

File cốt lõi: `app/Foundation/VerticalDefinition.php` (interface), `app/Foundation/VerticalRegistry.php`, `app/Foundation/Vertical/VerticalTemplate.php`, `VerticalPhase.php`, `VerticalChecklistItem.php`, `VerticalConfigItem.php`, `DatabaseVertical.php`, `CloneVerticalFromTemplateAction.php`.

```
VerticalTemplate (bảng vertical_templates)
├── organization_id  NULL  → bản mẫu thư viện dùng chung (system-wide)
├── organization_id  SET   → bản đã "kích hoạt/nhân bản" cho 1 tổ chức cụ thể
├── source_template_id     → FK trỏ về bản mẫu gốc (lineage của Clone)
├── status, is_active, activated_at, activated_by
├── default_roles (json)   → tương đương "verticalRoles()" trong Blueprint
├── sidebar_config (json)  → tương đương "sidebarGroups()"
├── phases()  → HasMany VerticalPhase (Process layer)
│     └── checklistItems() → HasMany VerticalChecklistItem
└── configItems() → HasMany VerticalConfigItem (hierarchy/activity_type/doc_type labels)
```

**Nhận xét đối chiếu với tài liệu (A04.1, A04.3, A08):**

1. Bảng này **gộp chung 3 khái niệm** mà tài liệu tách riêng: **Vertical** (phân loại ngành) + **Business Blueprint** (thiết kế Process/Phase/Checklist/Role) + **Organization Configuration** (bản đã tùy biến của tổ chức). Cùng một bảng `vertical_templates` vừa chứa bản mẫu dùng chung, vừa chứa bản đã "activate" theo tổ chức.
2. `CloneVerticalFromTemplateAction` clone **toàn bộ** cấu trúc phases/checklist sang bản ghi mới thuộc về tổ chức — đây chính là cơ chế "Clone" mà tài liệu A04.3 mô tả cho **Blueprint Version**, nhưng ở đây nó được dùng để **Clone cho từng Organization** (không phải để tạo version mới của Blueprint). Hệ quả: mỗi tổ chức có **bản sao độc lập, có thể sửa tùy ý (kể cả sau khi "publish")** — vi phạm trực tiếp nguyên tắc:
   - DP-04 (Configuration over Customization — nguyên tắc tài liệu yêu cầu: thay đổi theo tổ chức phải qua Configuration, không sửa Blueprint)
   - BR-004/BR-013 (Blueprint Published không được sửa trực tiếp; Organization chỉ cấu hình, không sửa Blueprint gốc)
   - Vì không có bảng `organization_*_configs` riêng, muốn thêm/bớt checklist cho 1 tổ chức thì phải **sửa trực tiếp `VerticalChecklistItem` trên bản clone của tổ chức đó** → cách này hoạt động được nhưng **không audit được rõ ràng "phần nào là chuẩn gốc, phần nào tổ chức tự đổi"**, và khi thư viện gốc có Blueprint version mới, tổ chức đã clone sẽ **không nhận được cập nhật** (không có khái niệm Upgrade/Compare Version như A04.3).
3. **Không có Business Outcomes, Business Capabilities, AI Capabilities, Analytics Definition** — 4 trong số 8 phần cấu trúc Blueprint theo A04.1 §5.2 hoàn toàn vắng mặt. `VerticalTemplate` chỉ hiện thực **Process Layer** (Workflow/Phase/Checklist rút gọn) + một phần **Deployment Layer** (`sidebar_config`, `default_roles`).
4. **Không có Semantic Version** (`1.0.0`), không có trạng thái lifecycle `Draft → In Design → Ready for Review → Reviewing → Approved → Published → Deprecated → Archived` (chỉ có `status` tự do + `is_active` boolean) — không có Compare Version, không có Release Note, không có Upgrade Wizard cho tổ chức đã deploy.

### 1.4 Cấu trúc "Deployment" hiện tại — chi tiết kỹ thuật

Bảng chính: `deployment_targets` (model `DeploymentTarget`), `deployment_checklist_items`, `deployment_issues`, `deployment_progress_logs`. Action tạo runtime: `CreateVerticalProjectAction` (`Modules/Deployment/app/Actions/CreateVerticalProjectAction.php`) — tạo thẳng bản ghi `Project` (module `Project`) với `vertical_code`, **không** thông qua bất kỳ bước validate Blueprint / đọc Organization Configuration / ghi log riêng nào.

```
Project (Modules/Project)          ← được tạo trực tiếp bởi CreateVerticalProjectAction
   ↑ project_id
DeploymentTarget (Modules/Deployment)
   ├── vertical_code            (string, không phải FK blueprint_version_id)
   ├── target_organization_id
   ├── current_phase
   ├── readiness_response_id → Survey (readiness assessment)
   ├── data_collection_response_id → Survey (thu thập dữ liệu)
   └── checklistItems() → DeploymentChecklistItem (= Checklist Runtime)
```

**Nhận xét đối chiếu (A05, A09.2):**

- `DeploymentTarget` + `DeploymentChecklistItem` + `DeploymentProgressLog` + `DeploymentIssue` **chính là "Project Runtime"** theo đúng nghĩa tài liệu (A05 §4, A06) — phần này làm khá tốt và đầy đủ (có Activity log riêng qua `LogsActivity`, có Issue tracking, Progress log, Readiness scoring qua `ReadinessScoreService`/`GapAnalysisService`/`DataQualityScoreService`).
- Tuy nhiên **"Deployment" đúng nghĩa tài liệu (Deployment Engine — quá trình chuyển Blueprint+Config → Runtime, có validate/log/snapshot)** **không tồn tại**. Không có bảng `deployments` (record 1 lần triển khai), không có `deployment_logs` (log từng bước xử lý deploy), không có `deployment_snapshots` (chụp lại Blueprint+Config tại thời điểm deploy). Việc "deploy" hiện tại = gọi thẳng `CreateVerticalProjectAction` để tạo `Project`, sau đó tạo `DeploymentTarget` gắn với `vertical_code` (string tham chiếu lỏng lẻo, không phải FK tới `blueprint_versions.id` — vì bảng đó không tồn tại).
- Hệ quả trực tiếp: **không thể trả lời chính xác** câu hỏi mà tài liệu coi là bắt buộc (BR-010 của A04.3, RR-002 của A05): *"Project Runtime này được deploy từ Blueprint Version nào, tại thời điểm nào, config nào?"* — vì không có cột `blueprint_version_id` neo trên `DeploymentTarget`/`Project`, chỉ có `vertical_code` (string).
- Tên gọi `Modules/Deployment` do đó bị lệch nghĩa so với tài liệu: nó nên được hiểu là **"Project Runtime module cho vertical truy xuất nguồn gốc/OCOP-readiness"**, không phải "Deployment Engine dùng chung cho mọi Vertical" như A05 mô tả.

### 1.5 AI, Knowledge, SOP — các module ánh xạ tốt

- **AiCopilot** (`ai_agents`, `ai_prompts`, `ai_requests`, `ai_monthly_usages`) ánh xạ khá sát với "AI Module" (A02 §8.2): đa driver (Claude/OpenAI/Mock qua `AiDriverManager`), tenant-aware (`AiAgent`/`AiPrompt` dùng `TenantAwareModel`, hỗ trợ agent hệ thống dùng chung `organization_id = NULL` + agent riêng theo tổ chức), có usage tracking/cost (`AiMonthlyUsage`, `RecordAiUsageAction`), có quota (`QuotaExceededException`). Đây là phần **làm tốt hơn** so với mô tả tối thiểu của tài liệu.
  - **Thiếu duy nhất**: không có bảng tương đương `blueprint_ai_capabilities` để khai báo "AI hỗ trợ ở checklist/workflow nào" theo đúng A04.1 §5.8/DP-06. Hiện tại việc gọi AI ở đâu là do code cứng trong từng module bespoke (Deployment/Assessment/OcopRubric) tự quyết định, không khai báo tập trung.
- **KcCategory + KcItem** ánh xạ đúng "Knowledge Module" (A02 §8.1): có category, tag, attachment, access control, feedback, learning progress, version history — đầy đủ hơn cả yêu cầu tối thiểu của tài liệu.
- **Sop** ánh xạ đúng "SOP" resource type mà Blueprint tham chiếu (A04.1 §5.7) — có version, RACI, approval flow, flowchart export, connector versioning — rất đầy đủ, vượt cả yêu cầu MVP.
- Cả hai module này **đã sẵn sàng** để trở thành "Resource" cho `blueprint_resource_links` (`resource_type = knowledge | sop`) một khi có `BusinessBlueprint` module.

### 1.6 Report/Dashboard — lệch mô hình

`Modules/Report` hiện triển khai theo hướng **domain-specific controller/query** (`HrReportController`, `SalesReportController`, `ProjectKpiReportController`, `CompetencyReportController`) — mỗi domain nghiệp vụ có 1 bộ query/view riêng, viết tay. Điều này **không sai** cho MVP nhưng khác hẳn mô hình tài liệu (A04.1 §5.9, A08 §DA-009): `blueprint_analytics` (Definition) → Dashboard Runtime (đọc Runtime data theo metric đã khai báo ở Blueprint). Nếu muốn Vertical mới tự động có Dashboard mà không cần code tay, cần tách phần "định nghĩa chỉ số" ra khỏi report code cứng.

### 1.7 Xung đột đặt tên cần lưu ý (không phải thiếu sót, nhưng dễ gây nhầm khi đọc tài liệu song song với code)

| Tên trong code | Ý nghĩa thực tế trong code | Ý nghĩa trong tài liệu THUCHOCVN | Rủi ro |
|---|---|---|---|
| `Modules/Marketplace` | Marketplace tuyển dụng (job listing, candidate, application, review) | "Business Solution Marketplace" — nơi phân phối/giới thiệu Business Solution (A02 §10.1, A07) | Dev mới đọc tài liệu dễ nhầm tưởng `Modules/Marketplace` hiện tại chính là chỗ cần mở rộng cho Solution Marketplace — **không phải**, đây là 2 domain hoàn toàn khác nhau (tuyển dụng vs. phân phối giải pháp phần mềm) |
| `Modules/WorkflowAutomation` | Rule engine tổng quát: Trigger (event/manual) → Condition → Action Executor (webhook/email/AI call/notification/update subject) | "Business Process" trong Blueprint: Workflow → Phase → Checklist (A04.1 §5.6, A06 §4) | Tài liệu A01 §12 liệt kê rõ **"Workflow engine quá phức tạp kiểu BPMN"** là điều **không nên làm ở giai đoạn đầu** — nhưng `WorkflowAutomation` hiện tại chính là một automation/rule engine khá gần BPMN (Trigger/Condition/Action/EntityState/Transition). Đây không phải Blueprint Workflow — nó là tầng "automation" bổ trợ, phục vụ mục đích khác (tự động hoá phản ứng theo sự kiện: gửi email, gọi AI, cập nhật trạng thái). Cần ghi chú rõ trong tài liệu kỹ thuật nội bộ để nhóm dev không nhầm 2 khái niệm khi thiết kế `blueprint_workflows` sau này |
| `Modules/Deployment` | Project Runtime (Project + Checklist + Issue + Progress cho từng tổ chức) | "Deployment Engine" — quá trình chuyển Blueprint → Runtime, có log/snapshot (A05 §3) | Như phân tích ở 1.4 — tên trùng nhưng phạm vi thực tế gần với "Project Runtime" hơn là "Deployment Engine" |

---

## Phần 2. Ánh xạ chi tiết: 50 bảng chuẩn (A09.1–A09.3) ↔ bảng hiện có

| # | Bảng chuẩn (tài liệu) | Tương đương gần nhất hiện tại | Mức khớp |
|---|---|---|---|
| 01–10 | organizations, organization_members, organization_settings, departments, job_titles, users, roles, permissions, model_has_roles, role_has_permissions | `organizations`, `organization_members`, `organization_settings` (Organization), `departments` (Department), `job_titles` (JobTitle), `users` (User), Spatie roles/permissions + `user_role_scopes` (RoleScope) | ✅ Khớp cao |
| 11 verticals | `vertical_templates` (organization_id NULL) | ⚠️ Khớp một phần — bị gộp thêm nội dung Blueprint |
| 12–15 business_solutions, versions, categories, tags | **Không có** | ❌ Thiếu hoàn toàn |
| 16 organization_solutions | `vertical_templates` (organization_id SET) đóng vai trò gần đúng, nhưng thiếu trường `status` chuẩn hoá theo lifecycle Organization Solution, thiếu `solution_version_id` | ❌ Thiếu bảng độc lập |
| 17–18 blueprints, blueprint_versions | `vertical_templates` (không versioned đúng nghĩa — không có version number, không có blueprint gốc bất biến tách khỏi bản tổ chức) | ❌ Thiếu |
| 19 blueprint_outcomes | Không có | ❌ Thiếu |
| 20 blueprint_capabilities | Không có | ❌ Thiếu |
| 21 blueprint_workflows | `vertical_phases`.template — gần đúng nhưng thiếu tầng Workflow phía trên Phase (hiện Phase thuộc thẳng về Template, không có Workflow ở giữa) | ⚠️ Thiếu 1 tầng |
| 22 blueprint_phases | `vertical_phases` | ✅ Khớp |
| 23 blueprint_checklists | `vertical_checklist_items` | ✅ Khớp (thiếu input/action/output description, priority, estimated_hours, need_approval) |
| 24 blueprint_resource_links | Không có — Sop/KcItem không được liên kết chính thức vào Vertical Template | ❌ Thiếu |
| 25 blueprint_ai_capabilities | Không có | ❌ Thiếu |
| 26 blueprint_analytics | Không có (Report hard-code thay thế) | ❌ Thiếu |
| 27 blueprint_deployment_settings | `vertical_templates.sidebar_config`, `default_roles` (một phần) | ⚠️ Thiếu phần lớn |
| 28–33 organization_*_configs, organization_role_mappings, organization_ai_configs | Không có bảng riêng — cấu hình = sửa trực tiếp bản clone `vertical_templates`/`vertical_phases`/`vertical_checklist_items` | ❌ Thiếu hoàn toàn (vi phạm nguyên tắc overlay) |
| 34 deployments | Không có | ❌ Thiếu |
| 35 deployment_logs | Không có (có `deployment_progress_logs` nhưng là log tiến độ Runtime, không phải log của hành động deploy) | ❌ Thiếu |
| 36 deployment_snapshots | Không có | ❌ Thiếu |
| 37 projects | `projects` (Project module) | ✅ Khớp |
| 38 project_workflows | Không có tầng riêng — `deployment_targets.current_phase` là string, không phải bảng | ⚠️ Thiếu |
| 39 project_phases | Gần đúng: field `current_phase` string trên `DeploymentTarget`, không phải bảng con | ⚠️ Thiếu bảng riêng |
| 40 project_checklists | `deployment_checklist_items` | ✅ Khớp |
| 41 tasks | `tasks` (module Task — CRM/PM, không dùng cho Deployment checklist) | ⚠️ Có nhưng chưa liên kết với `deployment_checklist_items` |
| 42 comments | Chưa thấy bảng comment polymorphic dùng chung cho Runtime (Task module có `task_comments` riêng) | ⚠️ Rời rạc theo từng module |
| 43 approvals | `sop_approval_flows` (riêng cho SOP); Deployment/OcopRubric có cờ trạng thái riêng, không dùng bảng approval chung | ⚠️ Rời rạc |
| 44 activities | `activity_log` (Spatie, qua `ActivityLog` module) + `deployment_progress_logs` | ✅ Khớp (2 cơ chế song song) |
| 45 files | Chưa xác định bảng file chung — `kc_item_attachments`, `sop_step_attachments`, `rc_candidate_attachments` rời rạc theo module | ⚠️ Rời rạc, không có bảng `files` polymorphic chung |
| 46 knowledge_items | `kc_items` | ✅ Khớp tốt |
| 47 ai_agents | `ai_agents` | ✅ Khớp tốt |
| 48 ai_prompts | `ai_prompts` | ✅ Khớp tốt |
| 49 ai_results | `ai_requests` (gần đúng — lưu request/response, chưa tách biệt input/output theo đúng schema `ai_results`) | ⚠️ Gần khớp |
| 50 reports | Không có bảng `reports` chung — Report module chỉ có Controller/Query, không lưu Report Output vào DB | ❌ Thiếu (thực chất render on-the-fly, không lưu output) |

**Tóm tắt định lượng**: trong 50 bảng chuẩn, khoảng **18 bảng khớp tốt (36%)**, **10 bảng khớp một phần (20%)**, **22 bảng thiếu hoàn toàn (44%)** — phần lớn số thiếu tập trung ở **Module 03 (Business Blueprint)** và **Module 04 (Organization Configuration)**, đúng như dự đoán ở Phần 0.

---

## Phần 3. Module MỚI cần bổ sung (theo thứ tự ưu tiên/phụ thuộc)

> Nguyên tắc đề xuất: **không đập bỏ** những gì đang chạy tốt (Deployment/Assessment/OcopRubric vẫn tiếp tục vận hành các Vertical hiện có). Mục tiêu là bổ sung **lớp trừu tượng còn thiếu ở phía trên**, để các Vertical **tương lai** có thể được tạo bằng cấu hình dữ liệu thay vì viết module mới mỗi lần — đúng lời hứa cốt lõi của tài liệu ("một Blueprint có thể phục vụ hàng trăm tổ chức mà không cần nhân bản hay sửa mã nguồn" — A07 §18).

### 3.1 Module mới #1 — `BusinessSolution` (ưu tiên cao nhất)

**Vì sao cần**: Hiện tại không có khái niệm "Business Solution" tách biệt khỏi Vertical. Một Vertical (`agriculture`) có thể có nhiều Solution khác nhau (AI Truy xuất nguồn gốc, AI OCOP Readiness...) nhưng hiện tại mỗi cái lại là 1 module code riêng không có "cha chung". Module này là điều kiện tiên quyết để có Solution Catalog / Marketplace giải pháp sau này (A01 §6.2, A02 §6.2).

**Phạm vi (bảng dữ liệu, theo A09.1 §12–16)**:
- `business_solutions` (id, vertical_id FK→verticals, code, name, slug, short_description, description, target_customers json, status, visibility, thumbnail_url, metadata)
- `business_solution_versions` (id, business_solution_id, version, status, release_note, published_at, published_by)
- `business_solution_categories`, `business_solution_tags`
- `organization_solutions` — **có thể đặt ở đây hoặc ở module `OrganizationSolution` riêng** (xem 3.3)

**Quan hệ với module hiện có**:
- `verticals` — tạo mới bảng `verticals` chính thức (tách khỏi `vertical_templates`), migrate dữ liệu từ danh sách code hiện có trong `VerticalDefinition`/`VerticalTemplate.code` (`traceability`, `ocop`, ...).
- Ba "Solution bespoke" hiện có (Deployment=TXNG, Assessment=Workforce, OcopRubric=OCOP) trở thành **3 bản ghi `business_solutions`** đầu tiên — không cần viết lại logic vận hành, chỉ cần đăng ký chúng vào catalog mới này để UI/Marketplace/Report có một nguồn danh mục thống nhất.

### 3.2 Module mới #2 — `BusinessBlueprint` (ưu tiên cao nhất, phụ thuộc #1)

**Vì sao cần**: Đây là khoảng trống lớn nhất theo Phần 2. Cần tách phần "thiết kế nghiệp vụ" ra khỏi `VerticalTemplate` hiện tại và bổ sung đầy đủ 8 thành phần theo A04.1 §5.2, cùng cơ chế Versioning/Publish theo A04.3.

**Phạm vi (bảng dữ liệu, theo A09.1 §17–27)**:
```
blueprints (id, business_solution_id, code, name, current_version_id, status)
blueprint_versions (id, blueprint_id, version, status, release_note, published_at, published_by, parent_version_id, snapshot)
blueprint_outcomes
blueprint_capabilities
blueprint_workflows      ← tầng còn thiếu giữa blueprint_version và phase
blueprint_phases         ← migrate dữ liệu từ vertical_phases
blueprint_checklists     ← migrate dữ liệu từ vertical_checklist_items (bổ sung input/action/output description, priority, estimated_hours, need_approval)
blueprint_resource_links ← liên kết chính thức tới Sop (resource_type=sop) và KcItem (resource_type=knowledge)
blueprint_ai_capabilities ← liên kết chính thức tới AiCopilot (ai_agent_id, ai_prompt_id)
blueprint_analytics       ← định nghĩa chỉ số, để Report module đọc theo config thay vì hard-code
blueprint_deployment_settings ← migrate dữ liệu từ vertical_templates.sidebar_config / default_roles
```

**Quy tắc bắt buộc cần đưa vào ngay từ đầu (tránh lặp lại lỗi của `VerticalTemplate`)**:
1. `blueprint_versions.status = published` → **khoá ghi (immutable)** ở tầng Model/Policy — mọi sửa đổi phải tạo bản ghi `blueprint_versions` mới qua Clone (BR-004 A04.1, Principle 01 A04.3).
2. Semantic version (`major.minor.patch`) bắt buộc, tăng theo quy tắc A04.3 §4.
3. Blueprint Readiness Checklist (12 tiêu chí, A04.1 §7.4) triển khai như 1 Query/Validator trước khi cho phép chuyển status → `published`.
4. Không được xoá `blueprint_versions` đã publish — chỉ chuyển `deprecated`/`archived` (DB-007/DB-009 A09.3).

**Quan hệ với module hiện có**: `Sop` và `KcItem` **không cần sửa gì** — chỉ cần bảng `blueprint_resource_links` trỏ tới `sop_processes.id` / `kc_items.id` qua polymorphic `resource_type/resource_id`. `AiCopilot` cũng không cần sửa — chỉ cần `blueprint_ai_capabilities.ai_agent_id/ai_prompt_id` trỏ tới bảng có sẵn.

### 3.3 Module mới #3 — `OrganizationSolution` (phụ thuộc #1, #2)

**Vì sao cần**: Đây là lớp thay thế cách làm hiện tại "clone toàn bộ VerticalTemplate cho từng tổ chức rồi sửa trực tiếp". Cần tách bạch: **kích hoạt** (activation record) + **cấu hình override** (không đụng vào Blueprint gốc).

**Phạm vi (bảng dữ liệu, theo A09.1 §16, §28–33)**:
```
organization_solutions            (thay thế "vertical_templates với organization_id SET")
organization_solution_configs
organization_capability_configs
organization_workflow_configs
organization_checklist_configs    ← đây là chỗ ghi "tổ chức X tắt checklist Y", thay vì sửa/xoá thẳng vertical_checklist_items
organization_role_mappings        ← ánh xạ role trừu tượng Blueprint (Field Officer/Supervisor/Manager) sang role thật của tổ chức — RoleScope module hiện tại chỉ scope role có sẵn theo branch/dept, CHƯA có ánh xạ "role trừu tượng Blueprint → role tổ chức"
organization_ai_configs
```

**Trạng thái lifecycle cần thêm** (A07 §15): `draft → configuring → ready → deploying → running → suspended → archived` — khác với lifecycle của `blueprint_versions`.

**Quan hệ với module hiện có**: `RoleScope` (`user_role_scopes`) tiếp tục dùng để gán vai trò thực tế cho user trong phạm vi tổ chức/chi nhánh — `organization_role_mappings` là tầng **phía trên**, ánh xạ vai trò trừu tượng của Blueprint (VD "Field Officer") sang role hệ thống cụ thể (VD role "Nhân viên thị trường" đã tồn tại qua Spatie) — hai bảng bổ trợ nhau, không trùng nhau.

### 3.4 Hình thức hoá `Deployment` thành Deployment Engine thật (nâng cấp module hiện có, không tạo module mới)

**Vì sao**: Giữ nguyên `Modules/Deployment` (đang là Project Runtime tốt), nhưng bổ sung **tầng ghi nhận hành động deploy** đứng trước nó, theo đúng A05 §3 (6 bước xử lý) và A09.2 §Phase 5.

**Phạm vi bổ sung**:
```
deployments          (organization_id, organization_solution_id, business_solution_id, blueprint_id,
                       blueprint_version_id, project_id, deployed_by, status, started_at, completed_at)
deployment_logs       (deployment_id, step, message, level, payload)
deployment_snapshots  (deployment_id, snapshot_type, snapshot_data json)
```
Và bổ sung cột `deployment_id` (FK tới `deployments.id`, không phải `vertical_code` string) vào `DeploymentTarget`/`Project` để pin cứng Runtime vào đúng Blueprint Version đã dùng lúc deploy — thoả RR-002 (A05), BR-010 (A04.3).

**Không cần đổi tên module** — tránh xáo trộn code đang chạy; chỉ cần thêm 3 bảng trên + 1 Action mới `DeployOrganizationSolutionAction` (validate Blueprint published → đọc Organization Configuration → gọi `CreateVerticalProjectAction` hiện có → ghi `deployment_snapshots`) đứng **trước** luồng hiện tại, không thay thế nó.

### 3.5 Module mới #4 — `SolutionCatalog` (ưu tiên trung bình, có thể làm sau)

**Vì sao cần**: Tránh việc tái sử dụng nhầm `Modules/Marketplace` (đang là marketplace tuyển dụng) cho mục đích "giới thiệu/phân phối Business Solution" như tài liệu A02 §10.1 mô tả. Đề xuất tên module mới hoàn toàn (VD `SolutionCatalog` hoặc `SolutionMarketplace`) để không đụng namespace với `Marketplace` hiện tại.

**Phạm vi (MVP, theo A01 §12 — "Solution Catalog cơ bản")**: danh sách + trang chi tiết Business Solution (đọc từ `business_solutions`), nút "Kích hoạt" tạo bản ghi `organization_solutions`, chưa cần license/rating/demo (đúng phạm vi loại trừ giai đoạn đầu — A01 §12).

### 3.6 Bổ sung nhỏ, không cần module riêng (mở rộng module hiện có)

| Việc cần làm | Module bị ảnh hưởng | Ghi chú |
|---|---|---|
| Thêm bảng `blueprint_ai_capabilities` liên kết tới `ai_agents`/`ai_prompts` | AiCopilot (chỉ thêm bảng, không sửa logic driver) | Thuộc phạm vi module `BusinessBlueprint` ở 3.2 |
| Thêm bảng `blueprint_analytics` + refactor dần Report Controllers đọc theo Definition thay vì hard-code | Report | Có thể làm sau, không chặn các module ưu tiên cao |
| Thêm bảng `files` dùng chung polymorphic (fileable_type/fileable_id) để thay thế các bảng attachment rời rạc (`kc_item_attachments`, `sop_step_attachments`...) | Nhiều module | Rủi ro cao (breaking change), nên đánh giá riêng, KHÔNG phải ưu tiên cho việc "đúng theo tài liệu THUCHOCVN" |
| Ghi rõ trong tài liệu kỹ thuật nội bộ: `WorkflowAutomation` ≠ `blueprint_workflows` để tránh nhầm khi triển khai `BusinessBlueprint` | WorkflowAutomation | Chỉ cần làm rõ tài liệu, không cần đổi code |

---

## Phần 4. Thứ tự triển khai đề xuất (phụ thuộc)

```
Bước 1: BusinessSolution (bảng verticals + business_solutions + versions)
           │  đăng ký 3 solution bespoke hiện có (TXNG/Workforce/OCOP) vào catalog
           ▼
Bước 2: BusinessBlueprint (blueprints + versions + outcomes + capabilities +
           workflows + phases + checklists + resource_links + ai_capabilities + analytics)
           │  migrate dữ liệu vertical_templates (thư viện, organization_id NULL) sang đây
           ▼
Bước 3: OrganizationSolution (organization_solutions + 5 bảng config + role_mappings)
           │  migrate dữ liệu vertical_templates (organization_id SET) sang đây
           │  ngừng dùng cơ chế "Clone toàn bộ template cho tổ chức" — chuyển sang overlay
           ▼
Bước 4: Deployment Engine (deployments + deployment_logs + deployment_snapshots
           │  + cột deployment_id/blueprint_version_id trên Project/DeploymentTarget)
           │  Modules/Deployment (Project Runtime) giữ nguyên, chỉ thêm tầng phía trước
           ▼
Bước 5: SolutionCatalog (UI danh mục Business Solution + nút Kích hoạt)
           (độc lập, có thể làm song song với Bước 3–4)
```

**Lưu ý triển khai**: Bước 1–4 có thể làm **không phá vỡ hệ thống đang chạy** vì `Modules/Deployment`, `Modules/Assessment`, `Modules/OcopRubric` tiếp tục hoạt động y nguyên trong lúc xây lớp mới song song; việc "migrate" ở mỗi bước là **sao chép dữ liệu sang bảng mới + trỏ tham chiếu**, không xoá bảng cũ ngay. Chỉ sau khi lớp mới đã chứng minh chạy đúng cho ít nhất 1 Vertical thật, mới cân nhắc ngừng dùng `vertical_templates`/cơ chế Clone cũ.

---

## Phần 5. Rủi ro & lưu ý khi hiện thực hoá

1. **Không nên coi đây là việc viết lại từ đầu.** Cả 3 "Vertical bespoke" hiện tại (Deployment/Assessment/OcopRubric) đang chạy tốt và có dữ liệu thật — mục tiêu của việc thêm `BusinessSolution`/`BusinessBlueprint`/`OrganizationSolution` là để **các Vertical MỚI trong tương lai** không phải viết lại 1 module Laravel đầy đủ như 3 cái hiện có, chứ không phải để thay thế ngay lập tức những gì đang chạy.
2. **`VerticalTemplate` không nên bị xoá ngay** — nó vẫn là nguồn cấu hình sống cho Deployment hiện tại (`VerticalRegistry::resolveForOrganization`). Cần giữ tương thích ngược (adapter) trong giai đoạn chuyển tiếp, ví dụ: `BusinessBlueprint` sinh ra một `VerticalDefinition` tương đương để code cũ (`DeploymentTarget`, `CreateVerticalProjectAction`) không phải sửa ngay.
3. **Đặt tên tránh trùng khái niệm** — không dùng lại tên `Marketplace` hay `Workflow` cho các thực thể mới liên quan đến Blueprint, vì 2 tên này đã có nghĩa khác trong codebase (xem bảng ở mục 1.7). Đề xuất: `blueprint_workflows` (bảng, không phải module) là ổn vì nó nằm trong ngữ cảnh `BusinessBlueprint`, nhưng **không tạo module Laravel tên `Workflow`** để tránh nhầm với `WorkflowAutomation`.
4. **RBAC**: cần bổ sung permission mới trong `config/permissions.php`/`app/Enums/PermissionEnum.php` cho các module mới (VD `BLUEPRINT_CREATE`, `BLUEPRINT_PUBLISH`, `SOLUTION_ACTIVATE`, `SOLUTION_CONFIGURE`, `DEPLOYMENT_RUN` — tài liệu A09.1 §08 đã gợi ý ví dụ `blueprint.create`, `blueprint.publish`, `deployment.run`, `project.view`, `ai.use`) và gán vào 8 role hiện có (CEO, Sales, Ops, Marketing, HR, AI_Operator, System_Admin, Viewer) theo đúng quy tắc phân quyền ở A04.2 §6 (BA/PO/Admin).
5. **Không cần động vào** `Subscription`, `ActivityLog`, `KcCategory/KcItem`, `Sop`, `RoleScope` — các module này đã khớp tốt với tài liệu, chỉ cần **liên kết thêm** (foreign key/reference), không cần sửa logic nội bộ.
