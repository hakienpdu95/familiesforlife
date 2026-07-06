# THUCHOCVN Vertical AI Platform — Tổng hợp tài liệu docs/thuchoc/

> Tài liệu này tổng hợp đầy đủ nội dung của toàn bộ 14 tài liệu trong `docs/thuchoc/*`, dùng làm bản tham chiếu nhanh (single-entry-point) khi cần tra cứu kiến trúc, khái niệm nghiệp vụ hoặc thiết kế dữ liệu của nền tảng THUCHOCVN Vertical AI Platform.
>
> Nguồn: 14 file `.docx` gốc trong `docs/thuchoc/`. Không có file nào bị sửa đổi trong quá trình tổng hợp.

## Danh mục tài liệu nguồn

| # | Tài liệu | Chủ đề chính |
|---|----------|--------------|
| 1 | TÀI LIỆU 01 – PRODUCT VISION | Tầm nhìn sản phẩm |
| 2 | TÀI LIỆU 02 – CORE MODULE ARCHITECTURE | Kiến trúc module lõi |
| 3 | TÀI LIỆU 03 – PRODUCT DOMAIN MODEL & BUSINESS CONCEPTS | Từ điển khái niệm nghiệp vụ |
| 4 | Sơ đồ kiến trúc hệ thống THUCHOCVN Vertical AI Platform | Sơ đồ tổng quan (ASCII) |
| 5 | A04.1 – BUSINESS BLUEPRINT ARCHITECTURE | Kiến trúc Business Blueprint |
| 6 | A04.2 – BUSINESS BLUEPRINT BUILDER SPECIFICATION | Đặc tả công cụ xây dựng Blueprint |
| 7 | A04.3 – BLUEPRINT VERSIONING & PUBLISHING SPECIFICATION | Versioning & Publishing |
| 8 | A05 – DEPLOYMENT & RUNTIME ARCHITECTURE | Kiến trúc Deployment & Runtime |
| 9 | A06 – RUNTIME OPERATION SPECIFICATION | Đặc tả vận hành Runtime |
| 10 | A07 – SOLUTION BUILDER & ORGANIZATION CONFIGURATION SPECIFICATION | Kích hoạt & cấu hình Solution theo tổ chức |
| 11 | A08 – PLATFORM DATA ARCHITECTURE & META MODEL | Kiến trúc dữ liệu nền tảng (5 lớp dữ liệu) |
| 12 | A09.1 – CORE DATABASE ERD | ERD 50 bảng lõi |
| 13 | A09.2 – Database Relationship, Migration Order & Implementation Guide | Thứ tự migration & quan hệ khóa ngoại |
| 14 | A09.3 – Core Tables Detail Specification | Đặc tả chi tiết field từng bảng |

---

## Phần 1. Tầm nhìn & Định vị sản phẩm (từ TÀI LIỆU 01)

### 1.1 Bối cảnh

THUCHOCVN ra đời từ thực tế: nhiều HTX, SME, đội bảo hiểm, đơn vị đào tạo tại Việt Nam **có công cụ phần mềm nhưng thiếu "bản thiết kế vận hành"** phù hợp với ngành nghề của mình. Thị trường đã có nhiều phần mềm CRM/ERP/workflow/chatbot AI, nhưng đều là công cụ rời rạc — người dùng vẫn phải tự hiểu nghiệp vụ, tự thiết kế quy trình, tự chọn biểu mẫu, tự phân quyền.

### 1.2 Tầm nhìn

> THUCHOCVN Vertical AI Platform là nền tảng giúp xây dựng, tùy biến và triển khai các Business Solution theo từng lĩnh vực/ngành nghề, vận hành bằng Business Blueprint, dữ liệu tổ chức, quy trình nghiệp vụ và AI.

**THUCHOCVN không chỉ cung cấp phần mềm — nó cung cấp cách một tổ chức nên vận hành một bài toán nghiệp vụ cụ thể bằng dữ liệu, quy trình và AI.**

Các giải pháp ngành dự kiến: AI Truy xuất nguồn gốc (nông sản), AI OCOP (HTX), AI Workforce Intelligence (nhân sự), AI Insurance (tư vấn bảo hiểm), AI Onboarding, AI Recruitment, AI Learning, AI Project Intelligence. Tất cả **dùng chung một nền tảng lõi, cùng kiến trúc module**, chỉ khác nhau ở Business Solution/Blueprint và phần tùy biến theo tổ chức.

### 1.3 Định vị sản phẩm

THUCHOCVN **không phải** CRM, ERP, workflow software, project management hay chatbot AI. THUCHOCVN là **AI Business Solution Platform** — bắt đầu từ bài toán kinh doanh (Business Problem), không bắt đầu từ task.

Chuỗi logic cốt lõi (xuất hiện xuyên suốt toàn bộ 14 tài liệu):

```
Vertical
   ↓
Business Solution
   ↓
Business Blueprint
   ↓
Organization Configuration
   ↓
Deployment
   ↓
Project Runtime
```

Điều này giúp THUCHOCVN không bị so sánh trực tiếp với Trello/Asana/Jira/Odoo — THUCHOCVN cạnh tranh bằng khả năng **đóng gói tri thức ngành thành giải pháp có thể triển khai**.

### 1.4 Sáu nhóm vấn đề khách hàng mục tiêu gặp phải

1. **Thiếu bản thiết kế vận hành** — không biết bắt đầu từ đâu, cần biểu mẫu gì, ai chịu trách nhiệm.
2. **Quy trình & dữ liệu rời rạc** — nằm ở Zalo, Excel, Word, Drive, email, ảnh chụp, giấy tờ.
3. **Có phần mềm nhưng không biết cấu hình theo nghiệp vụ** — SME/HTX không có đội BA/IT để tự thiết kế workflow.
4. **AI chưa gắn với quy trình thật** — AI mới dừng ở hỏi đáp/tóm tắt, chưa biết checklist nào thiếu, form nào chưa đủ.
5. **Mỗi tổ chức trong cùng ngành vẫn khác nhau** — không thể dùng một template cứng cho tất cả.
6. **Thiếu khả năng đo lường kết quả** — dừng ở "đã triển khai phần mềm" chứ chưa đo hiệu quả thật.

### 1.5 Đối tượng khách hàng mục tiêu (giai đoạn đầu)

- **HTX/doanh nghiệp OCOP**: AI Truy xuất nguồn gốc, AI OCOP Readiness, AI Hồ sơ sản phẩm, AI Marketing cho OCOP, AI Quản lý HTX.
- **SME**: AI Onboarding, AI SOP, AI Project Management, AI Recruitment, AI Sales Enablement, AI Knowledge Base.
- **Đội kinh doanh bảo hiểm**: AI Insurance Assessment, AI Sales Coaching, AI Workforce Dashboard, AI Training Pathway.
- **Trường học/đào tạo**: AI Readiness Assessment, AI Career Pathway, AI Competency Passport, AI Sandbox, AI Learning Dashboard.

### 1.6 Sáu khái niệm cốt lõi (định nghĩa lần đầu ở A01, thống nhất toàn bộ nền tảng)

| Khái niệm | Định nghĩa ngắn |
|---|---|
| **Vertical** | Lĩnh vực/ngành áp dụng (Nông nghiệp, Bảo hiểm, Nhân sự, Giáo dục...). Không phải sản phẩm, chỉ là phạm vi phân loại. |
| **Business Solution** | Giải pháp giải quyết một bài toán cụ thể trong Vertical. Khách hàng **mua/kích hoạt** Business Solution, không mua Blueprint. |
| **Business Blueprint** | Bản thiết kế nghiệp vụ đứng sau Business Solution — mô tả workflow, phase, checklist, form, SOP, knowledge, AI capability, dashboard, report, deployment setting. Không lưu dữ liệu vận hành. |
| **Organization Configuration** | Lớp tùy chỉnh Business Solution/Blueprint cho từng tổ chức, không sửa Blueprint gốc. |
| **Deployment** | Quá trình đưa Solution đã cấu hình vào Organization — sinh Project Runtime, Workflow Runtime, Checklist Runtime, Task Runtime, Permission Mapping, Dashboard Runtime, AI Context. |
| **Project Runtime** | Dữ liệu vận hành thực tế sau triển khai — nơi người dùng làm việc hằng ngày. Runtime thay đổi hằng ngày; Blueprint thì không. |

### 1.7 Tám nguyên tắc sản phẩm (Product Principles)

1. Business Solution là trung tâm thương mại.
2. Blueprint là trung tâm cấu hình.
3. Organization Configuration là trung tâm tùy biến.
4. Deployment là trung tâm triển khai.
5. Runtime là trung tâm vận hành.
6. AI phải theo ngữ cảnh (Solution, Blueprint, Configuration, Runtime).
7. Không hard-code nghiệp vụ theo từng khách hàng.
8. Tái sử dụng nhưng không áp đặt.

### 1.8 Phạm vi KHÔNG làm ở giai đoạn đầu

- Marketplace thương mại hoàn chỉnh
- AI tự tạo toàn bộ Blueprint từ đầu
- Multi-language đầy đủ
- Billing phức tạp
- Public API cho đối tác
- Mobile app riêng
- Microservices
- Workflow engine kiểu BPMN phức tạp

Giai đoạn đầu tập trung: Solution Catalog cơ bản, Blueprint Builder cơ bản, Organization Configuration, Deployment, Project Runtime, Dashboard tiến độ, AI Copilot ngữ cảnh đơn giản.

### 1.9 Roadmap 4 giai đoạn

1. **Foundation**: Solution Catalog, Business Blueprint, Organization Solutions, Clone/Customize, Deploy cơ bản, Runtime Project cơ bản.
2. **Vertical đầu tiên** (AI Truy xuất nguồn gốc hoặc AI OCOP): Workflow/Phase/Checklist, Form liên kết, SOP liên kết, File upload, Dashboard tiến độ, Báo cáo bàn giao, AI kiểm tra hồ sơ cơ bản.
3. **AI Layer**: Prompt Library, AI Agent, Knowledge reference, AI Validation, AI Summary, AI Report Generator.
4. **Marketplace**: Marketplace listing, Solution card, Demo, Rating, License, Install/Activate flow.

---

## Phần 2. Kiến trúc Module lõi (từ TÀI LIỆU 02)

### 2.1 Lựa chọn kiến trúc: Modular Monolith

THUCHOCVN **không đi theo microservices**, không tách core domain giao tiếp qua API nội bộ — cách đó phù hợp với đội kỹ thuật lớn có DevOps mạnh, không phù hợp giai đoạn hiện tại. Thay vào đó: **một ứng dụng Laravel thống nhất**, dùng chung database/authentication/tenant context/permission, chia module theo trách nhiệm nghiệp vụ.

Nguyên tắc bắt buộc:
- Không microservices ở giai đoạn hiện tại
- Không giao tiếp nội bộ qua HTTP API
- Không duplicate User/Organization/Permission
- Không hard-code nghiệp vụ theo khách hàng
- Không để Controller chứa business logic chính
- Không để Blueprint lưu dữ liệu vận hành
- Không để Runtime sửa ngược Blueprint

Luồng xử lý: `Laravel Application → Modules → Actions/Services → Models → Events/Jobs`.

### 2.2 Năm nhóm module chính

```
THUCHOCVN Vertical AI Platform
│
├── Core Platform Modules
├── Business Solution Modules
├── Runtime Operation Modules
├── AI & Knowledge Modules
└── System & Commercial Modules
```

#### A. Core Platform Modules (nền tảng bắt buộc, dùng chung cho mọi Business Solution)

- **Identity Module**: User, Authentication, Session, Role, Permission, Invitation, API Token. Không chứa nghiệp vụ theo vertical.
- **Organization Module**: Organization (tenant chính), Branch, Department, Team, Member, Employee, Position, Organization Setting.
- **Permission & Access Module**: Permission phân theo nhiều tầng: `System Level → Organization Level → Business Solution Level → Project Runtime Level`.

#### B. Business Solution Modules (nhóm quan trọng nhất — phản ánh định vị sản phẩm)

- **Vertical Module**: phân loại ngành (Agriculture, OCOP, Insurance, Workforce, Education, Manufacturing, Retail, Public Sector). Phục vụ catalog/marketplace/filter.
- **Business Solution Module**: lớp khách hàng nhìn thấy — mô tả dành cho ai, giải quyết vấn đề gì, outcome gì, capability nào, Blueprint nào phía sau. Một Business Solution có thể dùng nhiều Blueprint.
- **Business Blueprint Module**: trung tâm cấu hình nghiệp vụ — quản lý Workflow/Phase/Checklist Definition, Form/SOP/Knowledge/AI Capability/Dashboard/Report Reference, Deployment Settings, Version. Không lưu dữ liệu vận hành.
- **Organization Solution Module**: bản kích hoạt của Business Solution cho một Organization cụ thể — lưu trạng thái kích hoạt, version đang dùng, đã tùy biến gì, đã deploy chưa, owner, role mapping. Trạng thái: `Not Activated → Activated → Configuring → Ready to Deploy → Deployed → Suspended → Archived`.
- **Organization Configuration Module**: lưu tùy chỉnh riêng của tổ chức (checklist bật/tắt, form, SOP thay thế, dashboard, AI capability, notification, deadline, ngôn ngữ, phân quyền). Hoạt động như lớp overlay: `Business Blueprint gốc + Organization Configuration = Organization Blueprint View`.

#### C. Runtime Operation Modules

- **Deployment Module**: biến Organization Solution đã cấu hình thành Runtime — kiểm tra điều kiện, tạo Project/Workflow/Phase/Checklist/Task Runtime, Dashboard Runtime, gắn permission/notification, ghi log.
- **Project Runtime Module**: Project, Project Status, Runtime Phase/Checklist/Task, Assignee, Due Date, Progress, Comment, Activity, Approval, Attachment.
- **Workflow Runtime Module**: bản vận hành cụ thể của workflow trong một project. Trạng thái riêng: `Not Started → In Progress → Waiting Review → Completed / Blocked / Cancelled`.
- **Task Module**: Checklist là định nghĩa việc cần làm; Task là việc giao cho người thật. Không phải checklist nào cũng sinh task.
- **Document & Media Runtime Module**: file upload, ảnh, PDF, Word, Excel, version file, AI extraction status, liên kết Project/Checklist/Organization.

#### D. AI & Knowledge Modules

- **Knowledge Module**: Knowledge Category/Item, FAQ, SOP Article, Law/Regulation, Standard, Best Practice, Video, Guide, Tag — dùng reference, không copy vào từng Blueprint.
- **AI Module**: AI Provider, Model, Prompt, Prompt Version, AI Agent, Conversation, AI Log, Usage, Evaluation, Cost Tracking. Không hard-code theo 1 vertical — cùng 1 Agent dùng được cho nhiều Solution nhưng prompt/knowledge/context khác nhau.
- **AI Capability Module**: khả năng AI khai báo ở cấp Solution/Blueprint (OCR, Document Validation, Data Standardization, Risk Detection, Recommendation, Summary, Report Generation, Scoring, Prediction, Coaching) — không gắn cứng với model/prompt cụ thể.
- **AI Copilot Module**: giao diện tương tác AI, phải đọc context (Organization, Solution, Project Runtime, Checklist, Blueprint liên quan, Knowledge được phép dùng).

#### E. Analytics & Experience Modules

- **Dashboard Module**: 2 cấp — `Blueprint Dashboard Definition → Runtime Dashboard`.
- **Report Module**: Report Template (Blueprint/Solution) vs Report Output (Runtime).
- **KPI Module**: tỷ lệ hoàn thành checklist, thời gian xử lý, số lỗi hồ sơ, tỷ lệ AI validation passed, task quá hạn, mức hoàn thành Business Outcome.

#### F. System & Commercial Modules

- **Marketplace Module**: giới thiệu Business Solution (không bán workflow) — tên, vertical, đối tượng phù hợp, outcomes, AI capabilities, demo, version, author, license, pricing, rating.
- **Notification Module**: In-app/Email/Zalo/SMS/Webhook; kích hoạt bởi task due, checklist completed, AI validation failed, deployment completed, report generated, approval requested.
- **Activity Log Module**: ghi ai tạo solution, sửa blueprint, publish, deploy, upload, AI kiểm tra gì, checklist đổi trạng thái khi nào.
- **Billing & Subscription Module**: Organization subscription, solution-based pricing, AI usage pricing, storage pricing, user-based pricing.
- **Settings Module**: system/organization/solution/AI/notification settings, feature flags.

### 2.3 Ranh giới trách nhiệm giữa các module (rất quan trọng — tránh code chồng chéo)

| Module | KHÔNG được làm |
|---|---|
| Business Solution | Lưu task, lưu file upload runtime, xử lý AI request trực tiếp, xuất báo cáo runtime, tạo user |
| Business Blueprint | Lưu dữ liệu khách hàng, lưu trạng thái task, lưu file upload runtime, tự deploy |
| Deployment | Sửa Blueprint/Solution gốc, thay thế Project Runtime, chứa UI vận hành hằng ngày |
| Project Runtime | Sửa Blueprint/Business Solution, quản lý Marketplace, quản lý Prompt gốc |
| AI | Tự quyết định nghiệp vụ, tự truy cập dữ liệu không có context, tự thay đổi runtime nếu không qua Action được phép |

### 2.4 Luồng xử lý chuẩn

1. **Tạo Business Solution**: nhập thông tin → chọn Vertical → khai báo Outcomes → gắn Blueprint → lưu Draft.
2. **Tạo Business Blueprint**: khai báo workflow → tạo phase → tạo checklist → gắn form/SOP/knowledge/AI → cấu hình dashboard/report → Publish.
3. **Kích hoạt Solution cho Organization**: chọn Solution → tạo Organization Solution → chạy Configuration Wizard → tùy chỉnh → Ready to Deploy.
4. **Deploy**: tạo Project Runtime → Workflow Runtime → Checklist Runtime → Task (nếu có) → Dashboard Runtime → ghi Deployment Log.
5. **Vận hành**: mở Project Runtime → cập nhật checklist → upload tài liệu → AI kiểm tra → Dashboard cập nhật → Report được sinh.

### 2.5 Gợi ý cấu trúc thư mục Laravel Modules (NWIDART)

```
Modules/
├── Identity/
├── Organization/
├── BusinessSolution/
├── BusinessBlueprint/
├── OrganizationSolution/
├── Deployment/
├── ProjectRuntime/
├── Workflow/
├── Forms/
├── SOP/
├── Knowledge/
├── AI/
├── Dashboard/
├── Report/
├── Marketplace/
├── Notification/
├── Billing/
└── Settings/
```

Có thể gộp `BusinessSolution + BusinessBlueprint + OrganizationSolution` thành module tạm gọi `Vertical` ở MVP, nhưng bên trong vẫn phải giữ rõ 3 khái niệm Solution / Blueprint / Organization Solution.

### 2.6 MVP module scope (10 mục)

1. Business Solution / Vertical Catalog
2. Business Blueprint Builder cơ bản
3. Organization Solution
4. Configuration Wizard đơn giản
5. Deployment cơ bản
6. Project Runtime cơ bản
7. Checklist Runtime
8. File Upload
9. Dashboard tiến độ
10. AI Copilot cơ bản

---

## Phần 3. Từ điển khái niệm nghiệp vụ (từ TÀI LIỆU 03)

Tài liệu A03 là **từ điển nghiệp vụ** chuẩn hóa cách hiểu, không mô tả chi tiết chức năng module. Mô hình tổng thể:

```
Business Problem → Vertical → Business Solution → Business Blueprint
→ Organization Solution → Organization Configuration → Deployment
→ Project Runtime → Business Outcome
```

### 3.1 Định nghĩa formal từng khái niệm

- **Vertical**: lĩnh vực/ngành, chỉ phân loại, không chứa nghiệp vụ.
- **Business Problem**: vấn đề/nhu cầu thực tế của khách hàng (điểm bắt đầu của mọi Solution). Nguyên tắc: một Business Solution chỉ nên giải quyết một nhóm Business Problem liên quan.
- **Business Solution**: giải pháp hoàn chỉnh, đối tượng khách hàng nhìn thấy/lựa chọn/kích hoạt. Gồm Business Value, Business Outcome, Business Blueprint, AI Capability, Dashboard, Report, Deployment Setting.
- **Business Blueprint**: bản thiết kế nghiệp vụ, gồm Workflow/Phase/Checklist/Forms/SOP/Knowledge/AI Capability/Dashboard/Report/Deployment Setting. Không chứa dữ liệu phát sinh của khách hàng.
- **Organization**: đơn vị sử dụng nền tảng (VD: HTX Tiên Dương, Manulife CMC34). Một Organization có thể dùng nhiều Business Solution.
- **Organization Solution**: một Business Solution đã kích hoạt cho một Organization cụ thể — lưu Version, trạng thái, owner, ngày kích hoạt, Deployment History.
- **Organization Configuration**: lớp cấu hình riêng từng Organization (bỏ checklist, thêm form, đổi dashboard, thêm vai trò) — Blueprint gốc vẫn giữ nguyên.
- **Deployment**: quá trình tạo môi trường vận hành từ Organization Solution — sinh Project/Workflow/Checklist/Dashboard Runtime + Permission Mapping. Không làm thay đổi Blueprint.
- **Project Runtime**: dữ liệu vận hành thực tế — dự án, task, checklist, file, comment, nhật ký, dashboard, báo cáo.

### 3.2 Tám nguyên tắc thiết kế (Principle 01–08, A03)

1. Business Problem là điểm khởi đầu của mọi Business Solution.
2. Business Solution là đơn vị giá trị mà khách hàng sử dụng.
3. Business Blueprint chỉ mô tả thiết kế nghiệp vụ, không lưu dữ liệu vận hành.
4. Mỗi Organization có thể kích hoạt nhiều Business Solution.
5. Organization Configuration chỉ tùy chỉnh giải pháp cho tổ chức, không thay đổi Blueprint gốc.
6. Deployment có nhiệm vụ chuyển Blueprint thành Runtime.
7. Runtime chỉ chứa dữ liệu thực tế, không sửa ngược Blueprint.
8. Mọi AI Capability phải được gắn với Business Solution và Blueprint, không hard-code vào Runtime.

### 3.3 Ví dụ minh họa xuyên suốt: HTX Tiên Dương triển khai AI Truy xuất nguồn gốc

```
Business Problem: Hồ sơ sản xuất phân tán
   → Vertical: Nông nghiệp
   → Business Solution: AI Truy xuất nguồn gốc
   → Business Blueprint: Workflow khảo sát, thu thập hồ sơ, Forms, SOP, AI kiểm tra hồ sơ, Dashboard
   → Organization: HTX Tiên Dương
   → Organization Configuration: thêm biểu mẫu BM-31, bỏ Checklist C-05, thêm Dashboard riêng
   → Deployment
   → Project Runtime: Dự án TXNG 2026, 126 lô sản xuất, 420 checklist, 1.200 tài liệu, Dashboard tiến độ
```

---

## Phần 4. Sơ đồ kiến trúc hệ thống (ASCII, tài liệu gốc)

```
USER / ADMIN / BA
        │
        ▼
WEB UI / ADMIN UI
        │
        ▼
LARAVEL APPLICATION (Modular Monolith)
        │
┌───────┼────────────────┬───────────────────┐
▼                        ▼                    ▼
Identity & Org      Business Solution    Business Blueprint
User/Role/Org       Vertical/Solution    Workflow/Phase
Permission/Member   Marketplace/Catalog  Checklist/AI/Report
        │                        │                    │
        └────────────┬───────────┴─────────┬──────────┘
                      ▼                     ▼
           Organization Solution   Organization Configuration
                      │
                      ▼
              Deployment Engine
     (Chuyển Blueprint + Config thành Runtime)
                      │
                      ▼
              Runtime Operation
  Project/Workflow Runtime/Checklist/Task
  File/Comment/Approval/Activity/Report
                      │
        ┌─────────────┼──────────────┐
        ▼              ▼              ▼
   AI Engine       Analytics     Notification
 Agent/Prompt    Dashboard/KPI  Email/Zalo/In-app
 AI Result/OCR   Report Metrics Reminder/Alert
        └─────────────┴──────────────┘
                      ▼
                  DATABASE
     (One DB / Multi-tenant / Module-based Tables)
```

**Câu chốt gửi dev**: *THUCHOCVN là một Laravel Modular Monolith. Hệ thống dùng chung một database, chia theo module. Business Solution là lớp sản phẩm, Business Blueprint là lớp thiết kế, Organization Configuration là lớp tùy chỉnh, Deployment là lớp khởi tạo, Runtime là lớp vận hành thực tế.*

---

## Phần 5. Kiến trúc Business Blueprint (A04.1)

### 5.1 Tại sao cần Business Blueprint

Phần lớn phần mềm hiện nay bắt đầu từ Project/Workflow/Task — phù hợp doanh nghiệp có đội IT/BA riêng, nhưng là rào cản với SME/HTX/trường học. Họ **không thiếu công cụ, họ thiếu một bản thiết kế nghiệp vụ đã chuẩn hóa**. Đồng thời, cùng loại hình doanh nghiệp vẫn có quy trình khác nhau — nên không thể làm hệ thống cố định cho từng khách hàng, cũng không thể quá tổng quát bắt khách tự cấu hình hết. Blueprint là công cụ để **chuẩn hóa phần chuẩn hóa được, tùy biến phần cần tùy biến**.

Bốn mục tiêu của Blueprint: **Chuẩn hóa tri thức, Tái sử dụng, Hỗ trợ AI (cung cấp ngữ cảnh nghiệp vụ), Tách biệt thiết kế và vận hành**.

### 5.2 Vị trí trong chuỗi kiến trúc

```
Business Problem → Business Solution → Business Blueprint
→ Organization Configuration → Deployment → Project Runtime
```
Mỗi tầng trả lời một câu hỏi riêng: Solution = "giải pháp nào?"; Blueprint = "thiết kế thế nào?"; Configuration = "tổ chức muốn điều chỉnh gì?"; Deployment = "tạo môi trường vận hành ra sao?"; Runtime = "đang hoạt động thế nào?"

### 5.3 Sáu nguyên tắc kiến trúc Blueprint (Principle 01–06)

1. Business Solution là chủ sở hữu của Blueprint.
2. Blueprint chỉ mô tả thiết kế (quy trình, quy tắc, thành phần, AI Capability, Dashboard, Deployment Setting) — không lưu Task/Comment/File upload/tiến độ thực tế.
3. Runtime không sửa Blueprint — muốn thay đổi quy trình chuẩn phải qua version mới.
4. Organization chỉ cấu hình, không thay đổi Blueprint gốc.
5. Blueprint luôn có khả năng tái sử dụng cho nhiều Organization.
6. Blueprint là nguồn dữ liệu duy nhất của thiết kế nghiệp vụ (Single Source of Truth) — Workflow/Form/SOP/Dashboard/AI Capability chỉ nên tham chiếu từ Blueprint.

### 5.4 Cấu trúc 8 thành phần của Blueprint

```
Business Blueprint
├── 1. Overview
├── 2. Business Outcomes
├── 3. Business Capabilities
├── 4. Business Process
├── 5. Resources
├── 6. AI Capabilities
├── 7. Analytics
└── 8. Deployment Settings
```

- **Overview**: thông tin nhận diện (Name, Code, Business Solution, Vertical, Version, Status, Description, Author, Last Updated) — không chứa logic nghiệp vụ, không tham gia Runtime.
- **Business Outcomes**: kết quả đầu ra (VD: "Hồ sơ vùng trồng đầy đủ", "QR hoạt động"). THUCHOCVN đặt Outcome **trước** Workflow — vì khách hàng mua kết quả, không mua quy trình. Dùng để đánh giá hiệu quả triển khai, xây Dashboard, đo KPI.
- **Business Capabilities**: khả năng mà Solution cung cấp (VD: Quản lý vùng trồng, Quản lý lô, AI Validation) — không phải màn hình, không phải Workflow. Là cầu nối `Business Outcome → Workflow`.
- **Business Process**: trình tự hoạt động để tạo Outcome, bắt đầu từ Workflow (không từ Task):
  ```
  Workflow
    ├── Phase
    │     ├── Checklist
    │     └── Milestone
    └── Approval
  ```
  Task chỉ xuất hiện khi Runtime được tạo. Blueprint chỉ lưu Workflow Definition, không lưu Workflow Runtime.
- **Resources**: Forms, SOP, Knowledge, Dataset, Document Template, Report Template, Media Template — Blueprint **chỉ Reference, không copy**.
- **AI Capabilities**: Blueprint chỉ định nghĩa "AI cần hỗ trợ ở đâu" (VD: Checklist "Thu thập hồ sơ" → AI Capability "Document Validation"), không định nghĩa Prompt trực tiếp. Chuỗi: `Capability → Prompt → Agent → LLM` (do AI Platform xử lý) — giúp Blueprint độc lập với nhà cung cấp AI.
- **Analytics**: định nghĩa "cần đo cái gì" (Tiến độ, Checklist hoàn thành, Hồ sơ thiếu, Lỗi AI, Tỷ lệ QR hoạt động) — khác Dashboard (chỉ là cách hiển thị).
- **Deployment Settings**: định nghĩa sau khi Deploy cần sinh ra đối tượng gì (Project Runtime, Workflow Runtime, Checklist Runtime, Permission Mapping, Notification, Dashboard Runtime).

### 5.5 Kiến trúc phân tầng (6 tầng)

```
Business Blueprint
├── Strategic Layer     → Business Value, Business Outcomes, Success Metrics
├── Capability Layer     → Business Capabilities
├── Process Layer        → Workflow, Phase, Checklist, Approval
├── Resource Layer        → Forms, SOP, Knowledge, Dataset, Templates
├── Intelligence Layer     → AI Capabilities, Analytics, Business Rules
└── Deployment Layer        → Runtime Mapping, Permission, Notification, Initialization
```

### 5.6 Mười Design Principles (DP-01 → DP-10)

| Mã | Tên | Nội dung ngắn gọn |
|---|---|---|
| DP-01 | Business First | Bắt đầu từ Business Problem/Outcome, không từ Workflow/Database |
| DP-02 | Outcome Driven | Xác định Outcome trước khi thiết kế quy trình |
| DP-03 | Capability Before Process | Xác định Capability trước Process |
| DP-04 | Configuration over Customization | Thay đổi theo khách hàng qua Configuration, không sửa Blueprint |
| DP-05 | Reusable by Design | Blueprint phải dùng được cho nhiều tổ chức |
| DP-06 | AI Native | Xác định rõ điểm AI hỗ trợ ngay khi thiết kế, không bổ sung sau |
| DP-07 | Separation of Design and Runtime | Thiết kế và vận hành tách biệt hoàn toàn |
| DP-08 | Reference Instead of Copy | Blueprint chỉ tham chiếu Resource |
| DP-09 | Version Controlled | Mọi Blueprint đều phải có Version |
| DP-10 | Deployable | Blueprint sau Publish phải deploy được, nếu chưa đủ thông tin thì không được Publish |

### 5.7 Hai mươi Business Rules (BR-001 → BR-020)

Các quy tắc chính: một Business Solution phải có ≥1 Blueprint (BR-001); Blueprint chỉ thuộc 1 Solution tại 1 thời điểm (BR-002); Blueprint Draft không được Deploy (BR-003); Blueprint Published không sửa trực tiếp — phải tạo Version mới (BR-004); mỗi Blueprint phải có ≥1 Business Outcome (BR-005); Outcome phải liên kết ≥1 Capability (BR-006); Capability phải hiện thực bằng ≥1 Process (BR-007); Workflow phải có ≥1 Phase (BR-008); Phase phải có ≥1 Checklist (BR-009); Checklist tham chiếu Form/SOP/Knowledge/AI nhưng không chứa trực tiếp nội dung (BR-010); Blueprint không lưu Task/Comment/Attachment Runtime/Activity/Approval History (BR-011); chỉ tham chiếu Resource, không copy (BR-012); Organization chỉ cấu hình, không sửa Blueprint gốc (BR-013); Deployment luôn tạo Runtime mới, không ghi đè (BR-014); Runtime không ghi ngược Blueprint (BR-015); Blueprint phải khai báo AI Capability nếu có dùng AI (BR-016); Analytics phải định nghĩa trước khi tạo Dashboard (BR-017); mỗi Blueprint phải có ≥1 Deployment Setting (BR-018); chỉ Publish khi đạt "Ready for Deployment" (BR-019); mọi thay đổi phải ghi Version History (BR-020).

### 5.8 Blueprint Readiness Checklist (trước khi Publish)

12 tiêu chí bắt buộc: có Business Solution, Overview đầy đủ, ≥1 Business Outcome, ≥1 Business Capability, ≥1 Workflow, Workflow có Phase, Phase có Checklist, có Resource tham chiếu, có Deployment Settings, có Version, có Author, Status = Ready for Deployment. Thiếu bất kỳ tiêu chí nào → không cho Publish.

---

## Phần 6. Đặc tả Blueprint Builder — công cụ thiết kế (A04.2)

Blueprint Builder là công cụ dành cho **Product Owner, Business Analyst, Solution Architect** (không phục vụ end-user). Vai trò/quyền:

| Vai trò | Quyền |
|---|---|
| Super Admin | Toàn quyền |
| Product Owner | Tạo và Publish Blueprint |
| Business Analyst | Thiết kế Blueprint |
| Solution Architect | Thiết kế Solution |
| Reviewer | Review |
| Viewer | Chỉ xem |

### 6.1 Vòng đời làm việc

```
Tạo Blueprint → Thiết kế Business Outcome → Thiết kế Capability → Thiết kế Workflow
→ Gắn Resources → Khai báo AI → Thiết lập Dashboard → Thiết lập Deployment → Review → Publish
```

### 6.2 Giao diện: 9 tab trên một màn hình duy nhất (không chia nhỏ)

`Overview | Outcome | Capability | Workflow | Resources | AI | Analytics | Deployment | Review`

Chi tiết từng tab:
1. **Overview**: Name (text, bắt buộc), Code (auto), Business Solution (dropdown, bắt buộc), Vertical (auto), Version (auto), Status (Draft), Description (rich text, tối thiểu 50 ký tự), Owner, Tags.
2. **Business Outcomes**: Outcome/Tên/Mô tả/KPI/Priority/Success Metric — thêm/sửa/xóa/sắp xếp.
3. **Business Capabilities**: Capability/Mô tả/Nhóm/Outcome liên quan — 1 Outcome liên kết nhiều Capability.
4. **Workflow**: kéo thả Workflow → Phase → Checklist; Checklist gồm Tên/Mô tả/Owner/Estimate/Priority/Required/Approval. Không tạo Task ở đây (Task chỉ có ở Runtime).
5. **Resources**: chỉ Search & Reference Forms/SOP/Knowledge/Dataset/Template — không duplicate.
6. **AI**: chọn AI Capability (OCR, Document Validation, Summary, Recommendation, Scoring) rồi gắn vào Workflow/Checklist cụ thể.
7. **Analytics**: định nghĩa điều cần đo (Progress, Completion, Errors, Pending, Quality) — Dashboard Runtime sẽ sinh từ đây sau.
8. **Deployment**: định nghĩa Blueprint sẽ sinh đối tượng gì khi deploy (Project, Workflow, Checklist, Dashboard, Notification, Permission) — không tạo Runtime tại bước này.
9. **Review**: hiển thị Blueprint Score (%) theo từng phần đã hoàn thành; nếu Score < 100% thì không cho Publish.

### 6.3 Trạng thái Blueprint (Builder-level)

```
Draft → In Design → Ready for Review → Reviewing → Published → Archived
```
Draft không Deploy được; Published không Edit được — muốn sửa phải Create Version.

### 6.4 Ma trận quyền

| Hành động | BA | PO | Admin |
|---|---|---|---|
| Create | ✔ | ✔ | ✔ |
| Edit | ✔ | ✔ | ✔ |
| Delete | ✔ | ✔ | ✔ |
| Publish | ✖ | ✔ | ✔ |
| Archive | ✖ | ✔ | ✔ |

### 6.5 Kiến trúc UI: Master-detail thay vì tab rời rạc

Điểm khác biệt so với phần mềm BPM thông thường: người dùng luôn thấy mối liên kết trực tiếp giữa các thành phần:

```
Business Outcome → Business Capability → Workflow → Checklist
                                                         ├── Forms
                                                         ├── SOP
                                                         ├── Knowledge
                                                         ├── AI Capability
                                                         └── KPI
```

Chọn 1 Outcome → hiện ngay Capability hỗ trợ nó; chọn Capability → hiện Workflow liên quan; chọn Checklist → hiện Form/SOP/Knowledge/AI đang tham chiếu. BA không cần mở nhiều màn hình để kiểm tra quan hệ; Dev cũng dễ code theo hướng master-detail.

---

## Phần 7. Versioning & Publishing (A04.3)

### 7.1 Năm nguyên tắc Versioning

1. Blueprint không chỉnh sửa trực tiếp sau khi Publish — muốn sửa phải tạo Version mới.
2. Một Runtime luôn gắn với đúng một Blueprint Version (không tự động chuyển version).
3. Blueprint Version đã Published là bất biến — chỉ được xem/deploy/compare, không Edit.
4. Organization quyết định Upgrade — không có Auto Upgrade (VD: HTX Tiên Dương vẫn dùng v1.0 trong khi HTX Ba Vì đã dùng v2.0).
5. Version luôn kế thừa version trước qua Clone (`v1.0 → Clone → v1.1 → Edit → Review → Publish`), không tạo Version rỗng.

### 7.2 Lifecycle đầy đủ

```
Draft → In Design → Ready for Review → Reviewing → Approved → Published → Deprecated → Archived
```

- **Deprecated**: không khuyến nghị dùng, nhưng Runtime cũ vẫn hoạt động.
- **Archived**: ngừng sử dụng, không Deploy mới.

### 7.3 Semantic Versioning (Major.Minor.Patch)

- **Major**: thay đổi lớn (VD: Workflow mới) — `1.x.x → 2.0.0`
- **Minor**: thêm Capability — `1.0.x → 1.1.0`
- **Patch**: sửa lỗi checklist — `1.0.0 → 1.0.1`

### 7.4 Clone (cơ chế tạo version mới)

Clone gồm: Workflow, Capability, Resource Reference, AI Capability, Analytics. **Không Clone Runtime.**

### 7.5 Compare Version

Blueprint Builder hỗ trợ so sánh 2 version, hiển thị `+ Capability mới / - Checklist bị xóa / ~ Workflow sửa`.

### 7.6 Điều kiện Publish thành công

Overview ✔, Outcome ✔, Capability ✔, Workflow ✔, Deployment ✔, Version ✔ — thiếu bất kỳ điều kiện nào thì Reject. Publish ghi nhận: Published By, Published Date, Version, Release Note.

### 7.7 Upgrade (tính năng Enterprise)

```
Blueprint v2.0 Published, HTX đang dùng v1.0
→ Admin thấy "Có Version mới" → chọn Upgrade
→ Clone Runtime → Migration → Done
(hoặc "Continue v1.0" — không bắt buộc)
```

### 7.8 Rollback

Nếu Upgrade lỗi có thể Rollback về version cũ — **không xóa dữ liệu Runtime**, chỉ đổi con trỏ Blueprint Version.

### 7.9 Mười Business Rules (BR-001 → BR-010, A04.3)

Published không Edit (BR-001); Edit luôn tạo Version mới (BR-002); Runtime chỉ dùng 1 Blueprint Version (BR-003); Archived không được Deploy (BR-004); Deprecated vẫn được Runtime cũ dùng (BR-005); Upgrade không tự động (BR-006); Rollback không mất dữ liệu Runtime (BR-007); mọi lần Publish phải có Release Note (BR-008); mọi Version lưu Change Log (BR-009); mọi Runtime phải lưu Blueprint ID + Version + Deploy Date + Deploy By (BR-010).

### 7.10 Đề xuất UI: Version Manager

Màn hình riêng hiển thị bảng Version/Status/Published/Runtime count, cùng các action: Create Version, Compare, View Changes, Release Notes, Upgrade Wizard.

---

## Phần 8. Deployment & Runtime Architecture (A05)

### 8.1 Design Time vs Run Time

- **Design Time**: Business Solution, Business Blueprint, Workflow/Checklist/Form Definition, AI Capability, Dashboard Definition — chỉ mang tính mô tả, end-user chưa làm việc trực tiếp.
- **Run Time**: Project, Workflow/Checklist Runtime, Task, Comment, File, Dashboard Runtime, AI Runtime Context — nơi làm việc hằng ngày.

### 8.2 Deployment là gì

Deployment = quá trình chuyển đổi Business Blueprint thành Project Runtime dành riêng cho một Organization: đọc Blueprint → áp dụng Organization Configuration → sinh đối tượng Runtime → khởi tạo dữ liệu ban đầu → gán quyền và AI Context → sẵn sàng vận hành.

> Ẩn dụ: Blueprint là bản thiết kế tòa nhà; Deployment là quá trình xây; Runtime là tòa nhà đã hoàn thành đang sử dụng.

Ba mục tiêu: **Tách biệt thiết kế/vận hành, Tái sử dụng (1 Blueprint deploy nhiều lần cho nhiều HTX), Khởi tạo nhất quán**.

### 8.3 Deployment Engine — 6 bước xử lý

```
1. Kiểm tra Blueprint (Published? có Version? có Deployment Settings?)
2. Đọc Organization Configuration (checklist tắt, form thay thế, SLA riêng, notification riêng)
3. Sinh Runtime
4. Khởi tạo Dashboard
5. Khởi tạo AI Context
6. Hoàn thành Deployment
```
Deployment Engine không lưu dữ liệu Runtime — chỉ khởi tạo.

### 8.4 Runtime Architecture — Static vs Dynamic

```
Project
├── Workflow Runtime
│      ├── Phase Runtime
│      │       ├── Checklist Runtime
│      │       └── Milestone
│      └── Task
├── Files
├── Comments
├── Activities
├── Dashboard
└── AI Context
```

- **Static Runtime** (sinh ngay khi Deploy): Workflow, Phase, Checklist, Dashboard, KPI.
- **Dynamic Runtime** (tạo trong quá trình vận hành): Task, Comment, File, AI Result, Activity, Approval.

### 8.5 Runtime Mapping Table (bảng dev code theo)

| Blueprint | Runtime |
|---|---|
| Workflow Definition | Workflow Runtime |
| Phase Definition | Phase Runtime |
| Checklist Definition | Checklist Runtime |
| Form Reference | Form Instance |
| Dashboard Definition | Dashboard Runtime |
| AI Capability | AI Context |
| KPI Definition | KPI Runtime |

### 8.6 Runtime Lifecycle

```
Created → Started → Running → Paused → Completed → Closed
```
(độc lập với vòng đời Blueprint)

### 8.7 Runtime Synchronization

Blueprint có version mới **không tự động đồng bộ** vào Runtime đang chạy — Runtime chỉ cập nhật khi Organization chủ động thực hiện Upgrade.

### 8.8 Tám Runtime Business Rules (RR-001 → RR-008, A05)

Runtime luôn thuộc 1 Organization (RR-001); luôn gắn 1 Blueprint Version (RR-002); 1 Deployment tạo 1 Runtime mới (RR-003); không ghi đè Runtime cũ (RR-004); Runtime không sửa Blueprint (RR-005); Runtime có thể phát sinh Task, Blueprint thì không (RR-006); mọi File lưu ở Runtime, không lưu Blueprint (RR-007); mọi AI Result là Runtime Data (RR-008).

### 8.9 Runtime như một "Instance" của Business Solution

```
Business Solution → Business Blueprint → Deployment → Runtime Instance
                                                          ├── Project
                                                          ├── Workflow
                                                          ├── Checklist
                                                          ├── Files
                                                          ├── AI
                                                          ├── Dashboard
                                                          ├── KPI
                                                          └── Reports
```

Một Organization có thể có **nhiều Runtime Instance** của cùng một Business Solution (VD: nhiều dự án truy xuất nguồn gốc theo từng vụ sản xuất/khách hàng), tất cả dùng chung Blueprint/Version đã Publish.

---

## Phần 9. Runtime Operation Specification (A06)

### 9.1 Runtime Model tổng thể

```
Runtime
├── Project, Workflow, Phase, Checklist, Task
├── Files, Comments, Activity, Approval, Notification
├── AI
├── Dashboard
└── Reports
```
Runtime Architecture giống nhau across mọi Vertical (TXNG, Workforce, Insurance, Project, Recruitment...) dù dữ liệu khác nhau.

### 9.2 Chi tiết từng đối tượng Runtime

- **Project**: đơn vị vận hành lớn nhất, luôn thuộc 1 Organization. Fields: Tên, Mã, Organization, Blueprint Version, Status, Progress, Owner, Start Date, Due Date. Trạng thái: `Planning → Running → Paused → Completed → Closed`.
- **Workflow Runtime**: Workflow đang chạy (không còn là định nghĩa) — quản lý trạng thái/tiến độ/owner/phase.
- **Phase Runtime**: các bước trong workflow (VD: Khảo sát → Thu thập hồ sơ → Chuẩn hóa → Kiểm tra → Bàn giao) — quản lý trạng thái/tiến độ/người phụ trách.
- **Checklist Runtime**: nơi người dùng làm việc nhiều nhất. Fields: Tên, Status, Due Date, Assignee, Reviewer, Priority, Progress. Có thể Reference Form/SOP/Knowledge/AI. **Không lưu Task** trực tiếp.
- **Task**: khác Checklist — Checklist định nghĩa việc cần làm, Task là việc giao cho người thật cụ thể (VD: "Anh Nam upload ảnh vùng A"). Quản lý Assignee/Due Date/Status/Estimate/Actual; có thể phát sinh Comment/Activity/Notification.
- **Files**: mọi file (PDF, Word, Excel, GPS, Image, QR) phải biết thuộc Project nào/Checklist nào/Task nào/Uploaded By/AI Validation status.
- **Comments**: chỉ tồn tại ở Runtime (không gắn Blueprint), có thể gắn với Task/Checklist/Project.
- **Activity**: Audit Log của Runtime — **không cho phép Edit**.
- **Approval**: quản lý Người duyệt/Thời gian/Kết quả/Lý do — không sửa Blueprint.
- **Notification**: tự sinh khi Task đến hạn, Checklist quá hạn, AI phát hiện lỗi, có Comment mới, có Approval — gửi qua In-App/Email/Zalo.

### 9.3 AI Runtime — điểm khác biệt cốt lõi

AI không phải Chat — **AI đọc Runtime Context** (Project, Workflow, Checklist, File, History, Knowledge, Blueprint) rồi mới trả lời. AI Runtime gồm: OCR, Validation, Summary, Recommendation, Report, Risk Detection.

### 9.4 Dashboard & Report Runtime

Dashboard/Report **chỉ đọc Runtime, không đọc Blueprint** (VD: Project 85%, Checklist 90%, Task 120, Overdue 5, AI Error 12). Report ví dụ: báo cáo tuần, tháng, hoàn thành, AI, KPI.

### 9.5 Mười hai Runtime Business Rules (RR-001 → RR-012, A06)

Project luôn thuộc 1 Organization (RR-001); luôn biết Blueprint Version (RR-002); Task luôn thuộc Checklist (RR-003); Checklist luôn thuộc Phase (RR-004); Phase luôn thuộc Workflow (RR-005); Workflow luôn thuộc Project (RR-006); Comment không tồn tại độc lập (RR-007); Activity không được sửa (RR-008); Approval chỉ áp dụng Runtime (RR-009); Dashboard chỉ đọc Runtime (RR-010); Report chỉ đọc Runtime (RR-011); AI không được thay đổi Blueprint (RR-012).

### 9.6 Runtime User Journey chuẩn

```
Đăng nhập → Chọn Organization → Chọn Business Solution → Mở Project Runtime
→ Thực hiện Checklist → Upload File → AI kiểm tra → Hoàn thành Task
→ Reviewer phê duyệt → Dashboard cập nhật → Sinh Report
```
Đây là luồng vận hành chuẩn mà **mọi Vertical kế thừa** (OCOP, Workforce, Insurance...).

### 9.7 Mô hình Runtime thống nhất (chốt tư duy quan trọng nhất của A06)

> THUCHOCVN chỉ có **một Runtime Engine**, không phải mỗi Vertical xây một Runtime riêng.

```
Vertical → Business Solution → Business Blueprint → Deployment Engine
        → [Runtime Engine: Project/Workflow/Phase/Checklist/Task/File/AI/Dashboard/Report/Notification]
```

**Chỉ Blueprint thay đổi theo từng Vertical. Runtime Engine, AI Engine, Dashboard Engine, Report Engine luôn giữ nguyên.** Đây là điểm giúp THUCHOCVN là một Platform chứ không phải tập hợp nhiều phần mềm riêng lẻ.

---

## Phần 10. Solution Builder & Organization Configuration (A07)

### 10.1 Vai trò của lớp Organization Configuration

Là lớp kết nối giữa Business Blueprint và Project Runtime — giúp một Blueprint dùng cho nhiều khách hàng mà không cần sửa Blueprint gốc hoặc code riêng từng doanh nghiệp.

### 10.2 Kiến trúc Solution Builder

```
Marketplace → Business Solution → Organization Solution
→ Organization Configuration → Validation → Deployment → Runtime
```

Một Organization có thể có nhiều Solution độc lập, mỗi Solution có Blueprint/Configuration/Runtime riêng:
```
HTX Tiên Dương
├── AI Truy xuất nguồn gốc
├── AI OCOP
├── AI Marketing
└── AI Workforce
```

### 10.3 Wizard kích hoạt Solution — 8 bước

```
1. Chọn Blueprint Version → 2. Business Information → 3. Capabilities → 4. Workflow
→ 5. Resources → 6. AI → 7. Dashboard → 8. Review & Deploy
```

### 10.4 Chi tiết từng bước cấu hình

- **Business Information**: Tên Solution, Organization, Blueprint Version, Owner, Start Date, SLA, Status.
- **Capability Configuration**: chỉ bật/tắt Capability có sẵn trong Blueprint (VD: bật Quản lý vùng/lô/QR, tắt AI OCR/AI Dashboard) — không sửa Blueprint.
- **Workflow Configuration**: ẩn workflow, đổi SLA, thêm reviewer — **không được đổi logic gốc**.
- **Checklist Configuration**: bật/tắt checklist, đổi deadline mặc định, đổi người chịu trách nhiệm mặc định, gắn biểu mẫu khác.
- **Resource Configuration**: thay thế tài nguyên tham chiếu (VD: BM-01 → BM-01-HTX) — không copy, chỉ reference.
- **AI Configuration**: bật/tắt OCR/Image Validation, đổi Prompt Pack, chọn AI Provider/ngôn ngữ, giới hạn chi phí — chỉ áp dụng cho Organization, không ảnh hưởng Blueprint.
- **Dashboard Configuration**: thêm/ẩn dashboard cụ thể (VD: thêm "Chi phí", ẩn "AI Usage").
- **Role Mapping** (chương rất quan trọng): Blueprint chỉ biết vai trò trừu tượng (Field Officer, Supervisor, Manager); Organization phải ánh xạ sang vai trò thực tế (VD: Field Officer → Nhân viên thị trường, Supervisor → Tổ trưởng vùng, Manager → Giám đốc HTX). Nhờ đó 1 Blueprint dùng được cho nhiều tổ chức khác nhau.
- **Notification Configuration**: cấu hình kênh Email/Zalo/In-App/SMS theo sự kiện (VD: Task quá hạn → gửi người thực hiện + quản lý + admin).

### 10.5 Validation trước khi Deploy

Bảng điều kiện bắt buộc: có Blueprint ✔, có Capability ✔, có Workflow ✔, có Resource ✔, Role Mapping hoàn chỉnh ✔, AI Config hợp lệ ✔, Dashboard hợp lệ ✔ — thiếu bất kỳ điều kiện nào thì không cho Deploy.

### 10.6 Trạng thái Organization Solution (khác vòng đời Blueprint)

```
Draft → Configured → Ready → Deploying → Running → Suspended → Archived
```

### 10.7 Mười Business Rules (OR-001 → OR-010, A07)

Một Organization kích hoạt nhiều Solution (OR-001); một Solution dùng bởi nhiều Organization (OR-002); Organization không sửa Blueprint gốc (OR-003); Configuration chỉ hiệu lực trong Organization hiện tại (OR-004); Role phải map đầy đủ trước khi Deploy (OR-005); chỉ Deploy Blueprint đã Publish (OR-006); một lần Deploy tạo một Runtime mới (OR-007); Configuration không ảnh hưởng Organization khác (OR-008); mọi thay đổi Configuration phải ghi Audit Log (OR-009); sau khi Runtime tạo, thay đổi Configuration không tự động cập nhật Runtime đang chạy (OR-010).

### 10.8 Kiến trúc tổng thể chốt lại

```
Marketplace → Business Solution → Blueprint → Organization Solution
→ Configuration → Validation → Deployment → Runtime Engine
```
Mô hình này giúp một Blueprint phục vụ hàng trăm tổ chức mà không cần nhân bản hay sửa mã nguồn.

---

## Phần 11. Kiến trúc Dữ liệu Nền tảng — 5 lớp dữ liệu (A08)

A08 là bước trung gian trước khi làm ERD/Database Design/API Design/Data Permission/AI Context Design/Report-Dashboard Data Model. Nếu bỏ qua A08, dev dễ trộn lẫn dữ liệu mẫu, cấu hình, vận hành, AI, báo cáo.

### 11.1 Năm loại dữ liệu (mô hình nền tảng)

```
Master Data → Definition Data → Configuration Data → Runtime Data → Intelligence Data
```

1. **Master Data**: dữ liệu định danh/phân loại dùng chung toàn hệ thống, không phụ thuộc Project cụ thể. VD: User, Organization, Role, Permission, Vertical, Category, Industry, Tag, Department, Job Title, Branch. Nguyên tắc: quản lý tập trung, **không tạo bản sao User/Organization/Role trong từng Solution**.
2. **Definition Data**: mô tả cách một Business Solution được thiết kế (Design Time). VD: Business Solution, Business Blueprint, Blueprint Version, Workflow/Phase/Checklist/Form Definition, SOP/Knowledge Reference, AI Capability Definition, Dashboard Definition, Report Template, KPI Definition, Deployment Setting. Nguyên tắc: phải có version; Blueprint Published không sửa trực tiếp.
3. **Configuration Data**: tùy chỉnh Business Solution cho từng Organization — lớp giữa Definition và Runtime. VD: Organization Solution, Enabled/Disabled Capability/Workflow, Checklist Override, Custom SLA, Role Mapping, Permission Mapping, AI Setting, Dashboard Setting, Notification Setting, Custom Form/SOP Reference. Nguyên tắc: chỉ hiệu lực trong Organization hiện tại, không sửa Definition Data.
4. **Runtime Data**: dữ liệu phát sinh trong vận hành thực tế, thay đổi hằng ngày. VD: Runtime Project/Workflow/Phase/Checklist, Task, Comment, File Upload, Activity, Approval, Notification Instance, AI Result, Dashboard Runtime, Report Output. Nguyên tắc: không ghi ngược Blueprint; luôn biết Organization/Business Solution/Blueprint Version/deployment nào sinh ra nó.
5. **Intelligence Data**: dữ liệu do AI/phân tích/dashboard sinh ra hoặc dùng. VD: AI Conversation, AI Prompt Usage, AI Validation Result, OCR Result, Scoring Result, Recommendation, Risk Signal, Prediction, AI Summary, AI Report Draft, Dashboard Metric, KPI Score. Nguyên tắc: AI không được tự ý thay đổi Runtime Data nếu không qua Action được phép — AI chỉ đề xuất/đánh giá/cảnh báo/sinh bản nháp/ghi kết quả kiểm tra.

### 11.2 Meta Model tổng thể

```
Vertical → Business Solution → Business Blueprint → Blueprint Version
→ Organization Solution → Organization Configuration → Deployment
→ Runtime Project → Runtime Objects → Intelligence Data
```

### 11.3 Data Ownership

| Nhóm dữ liệu | Chủ sở hữu chính |
|---|---|
| Master Data | Platform / Organization Admin |
| Definition Data | Product Team / BA |
| Configuration Data | Organization Admin / Solution Admin |
| Runtime Data | Người dùng vận hành |
| Intelligence Data | AI Engine / Analytics Engine |

### 11.4 Data Flow

```
Product Team tạo Business Solution → BA tạo Business Blueprint → Blueprint Publish
→ Organization kích hoạt Solution → Organization cấu hình Solution → Deployment tạo Runtime
→ User vận hành Runtime → AI phân tích Runtime → Dashboard/Report hiển thị kết quả
```

Chi tiết: `Definition Data + Configuration Data → Deployment Engine → Runtime Data → AI/Analytics → Intelligence Data`

### 11.5 Data Boundary (ranh giới không được lưu chéo)

- **Blueprint không lưu**: Task, Comment, Uploaded File, Checklist Status, Approval History, AI Result, Report Output.
- **Runtime không lưu**: Blueprint Definition, Blueprint Version History, Marketplace Metadata, Solution Template, Form Template gốc.
- **Configuration không lưu**: Runtime Progress, Runtime Comment, Runtime File, Activity Log, AI Result vận hành.

### 11.6 Tenant Data

Bắt buộc có `organization_id` cho: Organization Solution, Organization Configuration, Deployment, Runtime Project/Workflow/Checklist, Task, File, Comment, Activity, Report Output. Definition Data có thể có `visibility = global | private | shared | organization`.

### 11.7 Data Audit

Nhóm cần audit bắt buộc: Blueprint publish, Blueprint version change, Organization configuration change, Deployment, Runtime status change, Checklist completion, File upload, Approval, AI validation result, Report generation. Cần ghi: ai thực hiện, thực hiện lúc nào, thay đổi gì, trước/sau, thuộc Organization nào, request id nếu có.

### 11.8 AI Context Data

AI không được đọc dữ liệu tùy tiện — AI Context phải tạo từ: User Context, Organization Context, Business Solution Context, Blueprint Context, Configuration Context, Runtime Context, Knowledge Context, Permission Context.

### 11.9 Mười hai Business Rules (DA-001 → DA-012, A08)

Mọi Runtime Data bắt buộc gắn Organization (DA-001); Runtime không sửa Definition Data (DA-002); Configuration không sửa Blueprint gốc (DA-003); Deployment phải lưu Blueprint Version (DA-004); AI Result là Intelligence Data, không phải Definition Data (DA-005); Report Template là Definition Data, Report Output là Runtime/Intelligence Data (DA-006); Form Template là Definition, Form Submission là Runtime (DA-007); Prompt Template là Definition, Prompt Usage là Intelligence (DA-008); Dashboard Definition là Definition, Dashboard Metric là Intelligence (DA-009); Activity Log không được chỉnh sửa (DA-010); Data Quality phải đo ở Runtime (DA-011); AI chỉ truy cập dữ liệu theo Permission Context (DA-012).

---

## Phần 12. Core Database ERD — 50 bảng lõi (A09.1, A09.2, A09.3)

### 12.1 Nguyên tắc chung

Một database duy nhất, chia bảng theo Module — không tách DB theo domain, không microservice, không giao tiếp API nội bộ. Các trường chuẩn cho mọi bảng: `id (bigint/uuid)`, `organization_id` (nếu tenant data), `created_at`, `updated_at`, `deleted_at` (nếu soft delete), `created_by`, `updated_by`, `status`, `metadata JSON`.

> Khuyến nghị: nếu codebase hiện tại đã dùng `id BIGINT auto increment` thì tiếp tục dùng để giảm refactor; không đổi giữa chừng.

### 12.2 Danh sách 50 bảng theo 9 module

**MODULE 01 – Identity & Organization**: `organizations, organization_members, organization_settings, departments, job_titles, users, roles, permissions, model_has_roles, role_has_permissions`

**MODULE 02 – Business Solution / Vertical**: `verticals, business_solutions, business_solution_versions, business_solution_categories, business_solution_tags, organization_solutions`

**MODULE 03 – Business Blueprint**: `blueprints, blueprint_versions, blueprint_outcomes, blueprint_capabilities, blueprint_workflows, blueprint_phases, blueprint_checklists, blueprint_resource_links, blueprint_ai_capabilities, blueprint_analytics, blueprint_deployment_settings`

**MODULE 04 – Organization Configuration**: `organization_solution_configs, organization_capability_configs, organization_workflow_configs, organization_checklist_configs, organization_role_mappings, organization_ai_configs`

**MODULE 05 – Deployment**: `deployments, deployment_logs, deployment_snapshots`

**MODULE 06 – Runtime Operation**: `projects, project_workflows, project_phases, project_checklists, tasks, comments, approvals, activities`

**MODULE 07 – File/Document**: `files`

**MODULE 08 – AI & Knowledge**: `knowledge_items, ai_agents, ai_prompts, ai_results`

**MODULE 09 – Analytics/Report/Notification**: `reports`

### 12.3 ERD tổng thể (quan hệ phân cấp)

```
organizations
  ├── organization_members
  ├── departments
  ├── users
  └── organization_solutions
        ├── organization_solution_configs
        ├── organization_capability_configs
        ├── organization_workflow_configs
        ├── organization_checklist_configs
        ├── organization_role_mappings
        ├── organization_ai_configs
        └── deployments
               ├── deployment_logs
               ├── deployment_snapshots
               └── projects
                      ├── project_workflows
                      │      └── project_phases
                      │             └── project_checklists
                      ├── tasks
                      ├── comments
                      ├── approvals
                      ├── activities
                      ├── files
                      ├── ai_results
                      └── reports

verticals
  └── business_solutions
         ├── business_solution_versions
         ├── blueprints
         │      └── blueprint_versions
         │             ├── blueprint_outcomes
         │             ├── blueprint_capabilities
         │             ├── blueprint_workflows
         │             │      └── blueprint_phases
         │             │             └── blueprint_checklists
         │             ├── blueprint_resource_links
         │             ├── blueprint_ai_capabilities
         │             ├── blueprint_analytics
         │             └── blueprint_deployment_settings
         └── organization_solutions
```

### 12.4 Chi tiết field theo bảng (tổng hợp A09.1 + A09.3)

#### MODULE 01 – Identity & Organization

**01. organizations** — tổ chức/tenant. `id, code(unique), name, short_name, organization_type(company/htx/school/gov), tax_code, phone, email, website, address, logo_url, status(active/inactive/suspended), metadata`

**02. organization_members** — quan hệ user–tổ chức. `id, organization_id, user_id, member_type(owner/admin/member/external), status(active/invited/inactive), joined_at, left_at`. Unique: `organization_id + user_id`

**03. organization_settings** — cấu hình theo tổ chức. `id, organization_id, setting_key, setting_value(json/text), group(general/ai/notification/storage)`. Unique: `organization_id + setting_key`

**04. departments** — phòng ban. `id, organization_id, parent_id(self), code, name, manager_id, status`

**05. job_titles** — chức danh. `id, organization_id, department_id, code, name, description, status`

**06. users** — tài khoản. `id, name, email(unique), phone, password, avatar_url, status(active/locked/inactive), last_login_at, metadata`. *Ghi chú*: nếu hệ thống cũ có `users.organization_id` vẫn giữ tương thích, dài hạn ưu tiên `organization_members`.

**07. roles** — theo Spatie hoặc nội bộ. `id, name, guard_name, scope(platform/organization/project), description`

**08. permissions** — danh sách quyền. `id, name(vd: blueprint.create), guard_name, module(blueprint/runtime/ai), description`

**09. model_has_roles** — pivot Spatie. `role_id, model_type, model_id, organization_id(nullable)`

**10. role_has_permissions** — pivot role-permission. `permission_id, role_id`

#### MODULE 02 – Business Solution / Vertical

**11. verticals** — phân loại ngành. `id, code(unique, vd: agriculture/insurance), name, description, icon, status`

**12. business_solutions** — giải pháp khách hàng thấy. `id, vertical_id, code(unique), name, slug(unique), short_description, description, target_customers(json), status(draft/published/archived), visibility(private/public/marketplace), thumbnail_url, metadata`

**13. business_solution_versions** — phiên bản solution. `id, business_solution_id, version, status(draft/published/deprecated), release_note, published_at, published_by, metadata`. Unique: `business_solution_id + version`

**14. business_solution_categories** — danh mục. `id, name, slug, description, status`

**15. business_solution_tags** — tag. `id, business_solution_id, tag`. Index: `business_solution_id, tag`

**16. organization_solutions** — solution đã kích hoạt cho tổ chức. `id, organization_id, business_solution_id, solution_version_id, name, owner_id, status(draft/configuring/ready/deployed/suspended/archived), activated_at, metadata`. Unique: `organization_id + business_solution_id`

#### MODULE 03 – Business Blueprint

**17. blueprints** — blueprint gốc. `id, business_solution_id, code(unique), name, description, current_version_id, status(draft/published/archived), metadata`

**18. blueprint_versions** — phiên bản blueprint. `id, blueprint_id, version, status(draft/review/approved/published/deprecated/archived), release_note, published_at, published_by, parent_version_id(self), snapshot(json), metadata`. Unique: `blueprint_id + version`

**19. blueprint_outcomes** — kết quả đầu ra. `id, blueprint_version_id, code, name, description, success_metric, sort_order, status, metadata`

**20. blueprint_capabilities** — năng lực. `id, blueprint_version_id, outcome_id(nullable), code, name, description, capability_type, sort_order, status, metadata`

**21. blueprint_workflows** — workflow definition. `id, blueprint_version_id, capability_id(nullable), code, name, description, sort_order, status, metadata`

**22. blueprint_phases** — phase definition. `id, workflow_id, code, name, description, sort_order, entry_condition, exit_condition, status, metadata`

**23. blueprint_checklists** — checklist definition (không lưu task runtime). `id, phase_id, code, name, description, input_description, action_description, output_description, required, default_priority(low/normal/high), estimated_hours, need_approval, sort_order, status, metadata`

**24. blueprint_resource_links** — liên kết resource. `id, blueprint_version_id, checklist_id(nullable), resource_type(form/sop/knowledge/dataset/template), resource_id, is_required, sort_order, metadata`

**25. blueprint_ai_capabilities** — AI capability. `id, blueprint_version_id, checklist_id(nullable), capability_code, name, description, ai_agent_id, ai_prompt_id, trigger_event, status, metadata`

**26. blueprint_analytics** — chỉ số cần đo. `id, blueprint_version_id, metric_code, name, description, metric_type, formula, source_type, status`

**27. blueprint_deployment_settings** — cấu hình deploy mặc định. `id, blueprint_version_id, setting_key, setting_value(json), description`

#### MODULE 04 – Organization Configuration

**28. organization_solution_configs** — config tổng. `id, organization_solution_id, config_key, config_value(json), description`

**29. organization_capability_configs** — bật/tắt capability. `id, organization_solution_id, blueprint_capability_id, enabled, override_name, override_config(json)`

**30. organization_workflow_configs** — tùy chỉnh workflow. `id, organization_solution_id, blueprint_workflow_id, enabled, default_owner_id, sla_days, override_config`

**31. organization_checklist_configs** — tùy chỉnh checklist. `id, organization_solution_id, blueprint_checklist_id, enabled, default_assignee_id, default_reviewer_id, due_days, override_config`

**32. organization_role_mappings** — ánh xạ role. `id, organization_solution_id, blueprint_role_code, organization_role_id(nullable), user_id(nullable), mapping_type`

**33. organization_ai_configs** — AI config riêng tổ chức. `id, organization_solution_id, ai_capability_code, enabled, ai_agent_id, ai_prompt_id, provider, cost_limit, config`

#### MODULE 05 – Deployment

**34. deployments** — một lần triển khai. `id, organization_id, organization_solution_id, business_solution_id, blueprint_id, blueprint_version_id, project_id(nullable), deployed_by, status(pending/running/completed/failed/rolled_back), started_at, completed_at, metadata`

**35. deployment_logs** — log từng bước. `id, deployment_id, step, message, level, payload, created_at`

**36. deployment_snapshots** — snapshot lúc deploy. `id, deployment_id, snapshot_type(blueprint/organization_config/runtime_mapping/permission/ai_context), snapshot_data(json), created_at`

#### MODULE 06 – Runtime Operation

**37. projects** — project runtime. `id, organization_id, organization_solution_id, deployment_id(nullable), name, code, description, owner_id, status, start_date, due_date, completed_at, progress, metadata`

**38. project_workflows** — workflow runtime. `id, project_id, blueprint_workflow_id, name, status, owner_id, progress, sort_order, metadata`

**39. project_phases** — phase runtime. `id, project_workflow_id, blueprint_phase_id, name, status, owner_id, progress, sort_order, metadata`

**40. project_checklists** — checklist runtime. `id, project_phase_id, blueprint_checklist_id, name, description, status(todo/in_progress/waiting_review/completed/rejected/skipped), assignee_id, reviewer_id, due_date, completed_at, progress, ai_status, metadata`

**41. tasks** — task runtime. `id, organization_id, project_id, checklist_id(nullable, FK project_checklists), title, description, assignee_id, reviewer_id, priority, status, start_date, due_date, completed_at, metadata`

**42. comments** — polymorphic. `id, organization_id, commentable_type, commentable_id, user_id, content, metadata`

**43. approvals** — polymorphic. `id, organization_id, approvable_type, approvable_id, requested_by, approver_id, status, comment, requested_at, decided_at, metadata`

**44. activities** — polymorphic, audit log bất biến. `id, organization_id, actor_id(nullable), subject_type, subject_id, action, description, old_values(json), new_values(json), created_at`

#### MODULE 07 – File/Document

**45. files** — file upload. `id, organization_id, project_id(nullable), checklist_id(nullable), uploaded_by, file_name, file_path, file_type, mime_type, file_size, storage_disk, ai_status, metadata`

#### MODULE 08 – AI & Knowledge

**46. knowledge_items** — tri thức. `id, organization_id(nullable), business_solution_id(nullable), title, content, knowledge_type, status, visibility(global/organization/private/shared), metadata`

**47. ai_agents** — agent AI. `id, code, name, description, agent_type, default_model, status, config`

**48. ai_prompts** — prompt template. `id, code, name, prompt_type, system_prompt, user_prompt_template, version, status, metadata`

**49. ai_results** — kết quả AI runtime. `id, organization_id, project_id(nullable), checklist_id(nullable), file_id(nullable), ai_agent_id(nullable), ai_prompt_id(nullable), result_type, input_data(json), output_data(json), confidence_score, status, created_by, metadata`

#### MODULE 09 – Analytics/Report

**50. reports** — report template/output (MVP dùng chung 1 bảng). `id, organization_id(nullable), project_id(nullable), business_solution_id(nullable), report_type, name, template_content, output_content, status, generated_by, generated_at, metadata`. *Ghi chú*: sau này nên tách `report_templates` / `report_outputs`.

### 12.5 Thứ tự Migration chuẩn (7 phase, A09.2)

```
Phase 1 – Identity & Organization        (bảng 01–10, tạo trước vì mọi module khác phụ thuộc organizations/users)
Phase 2 – Business Solution / Vertical   (bảng 11–16)
Phase 3 – Business Blueprint             (bảng 17–27)
Phase 4 – Organization Configuration     (bảng 28–33)
Phase 5 – Deployment                     (bảng 34–36)
Phase 6 – Runtime Operation              (bảng 37–44)
Phase 7 – File / AI / Report             (bảng 45–50)
```

*Lưu ý Phase 1*: nếu đang dùng Spatie Permission thì giữ đúng bảng mặc định của Spatie, không tự chế lại RBAC.

### 12.6 Bảy quan hệ lõi cần khóa chặt

1. `business_solutions.id → blueprints.business_solution_id` (1 Solution có thể có nhiều Blueprint, VD: Blueprint cơ bản/nâng cao/đào tạo)
2. `blueprints.id → blueprint_versions.blueprint_id`, và `blueprints.current_version_id → blueprint_versions.id` (biết bản đang published/latest)
3. `blueprint_versions.id → blueprint_workflows.blueprint_version_id → blueprint_phases.workflow_id → blueprint_checklists.phase_id` (chuỗi thiết kế nghiệp vụ, không lưu task ở đây)
4. `organizations.id → organization_solutions.organization_id`
5. `organization_solutions.id → organization_solution_configs / organization_capability_configs / organization_workflow_configs / organization_checklist_configs / organization_ai_configs .organization_solution_id`
6. `organization_solutions.id → deployments.organization_solution_id → projects.deployment_id` (1 Organization Solution có thể deploy nhiều lần, mỗi lần sinh 1 Project Runtime)
7. `projects.id → project_workflows.project_id → project_phases.project_workflow_id → project_checklists.project_phase_id → tasks.checklist_id`

### 12.7 Quy tắc Foreign Key

- **Dùng FK thật** cho: `organization_id, business_solution_id, blueprint_id, blueprint_version_id, organization_solution_id, deployment_id, project_id`.
- **Polymorphic (không FK cứng)** cho: `comments (commentable_type/commentable_id), approvals (approvable_type/approvable_id), activities (subject_type/subject_id)`.

### 12.8 Quy tắc xóa dữ liệu

- **Soft delete** cho: `organizations, users, business_solutions, blueprints, blueprint_versions, organization_solutions, projects, tasks, files`.
- **Không xóa Published Blueprint** — chỉ archive hoặc deprecated.
- **Runtime không xóa khi Blueprint đổi version** — Runtime cũ giữ nguyên.

### 12.9 Index & Unique bắt buộc

**Index**: `organization_id, business_solution_id, blueprint_id, blueprint_version_id, organization_solution_id, deployment_id, project_id, status, code, version, created_at`

**Unique**: `organizations.code`, `verticals.code`, `business_solutions.code`, `blueprints.code`, `blueprint_versions(blueprint_id + version)`, `organization_solutions(organization_id + business_solution_id)`

### 12.10 Mapping Blueprint → Runtime khi Deploy (A09.2, Ch.7)

| Design Time | Runtime |
|---|---|
| blueprint_workflows | project_workflows |
| blueprint_phases | project_phases |
| blueprint_checklists | project_checklists |
| blueprint_ai_capabilities | ai_results / ai context |
| blueprint_analytics | reports / dashboard metrics |
| blueprint_deployment_settings | deployments / deployment_snapshots |

Runtime phải lưu ID gốc để truy xuất nguồn thiết kế: `project_workflows.blueprint_workflow_id`, `project_phases.blueprint_phase_id`, `project_checklists.blueprint_checklist_id`, `projects.deployment_id`, `deployments.blueprint_version_id`.

### 12.11 Cấu trúc trạng thái chuẩn (state machines)

| Đối tượng | Trạng thái |
|---|---|
| Blueprint Version | draft → review → approved → published → deprecated → archived |
| Organization Solution | draft → configuring → ready → deployed → suspended → archived |
| Deployment | pending → running → completed → failed → rolled_back |
| Project | planning → running → paused → completed → closed → cancelled |
| Checklist / Task | todo → in_progress → waiting_review → completed → rejected → skipped |

### 12.12 Ghi chú triển khai cho Dev (A09.2, Ch.9)

1. Tạo migration đúng theo Phase, không đảo thứ tự.
2. Không để Blueprint chứa Runtime data.
3. Không để Project sửa ngược Blueprint.
4. Dùng `metadata JSON` cho phần mở rộng, không lạm dụng để thay bảng quan hệ chính.
5. Các bảng cấu hình Organization nên dùng `override_config JSON` để linh hoạt.
6. Deployment bắt buộc tạo `deployment_snapshots`.
7. Runtime bắt buộc lưu `blueprint_version_id` gián tiếp qua `deployment_id`.
8. AI Result không cập nhật trực tiếp checklist nếu chưa có action/rule rõ ràng.

### 12.13 Mười Business Rules DB (DB-001 → DB-010, hợp nhất A09.1 + A09.3)

Business Solution không deploy trực tiếp (DB-001); Deployment luôn dùng `blueprint_version_id` (DB-002); Blueprint không lưu Runtime Data (DB-003); Runtime không sửa Definition Data (DB-004); Organization Configuration không sửa Blueprint gốc (DB-005); Runtime Data bắt buộc có `organization_id` (DB-006); Published Blueprint không sửa trực tiếp (DB-007); Deployment phải tạo `deployment_snapshots` (DB-008); AI Result không tự đổi trạng thái checklist nếu chưa có Action hợp lệ (DB-009); Activity không được sửa/xóa (DB-010).

---

## Phần 13. Tổng kết — Các nguyên tắc xuyên suốt toàn bộ 14 tài liệu

Những nguyên tắc dưới đây lặp lại nhất quán trong mọi tài liệu và nên được coi là **luật bất biến** khi thiết kế/triển khai bất kỳ phần nào của THUCHOCVN:

1. **Chuỗi kiến trúc cố định**: `Vertical → Business Solution → Business Blueprint → Blueprint Version → Organization Solution → Organization Configuration → Deployment → Project Runtime → Intelligence Data (AI/Dashboard/Report)`.
2. **Blueprint không lưu dữ liệu vận hành** (Task, Comment, File, Approval, AI Result) — Blueprint chỉ mô tả thiết kế; Runtime chỉ chứa dữ liệu thực tế.
3. **Blueprint Published là bất biến** — mọi thay đổi phải qua Version mới (Clone → Edit → Review → Publish), theo Semantic Versioning.
4. **Runtime luôn gắn cứng với 1 Blueprint Version cụ thể** tại thời điểm Deploy — không tự động nâng cấp khi Blueprint có version mới; Upgrade là hành động chủ động, không bắt buộc, không mất dữ liệu khi Rollback.
5. **Organization Configuration là lớp overlay** — tùy biến (bật/tắt capability/workflow/checklist, role mapping, AI setting...) mà không bao giờ sửa Blueprint gốc, giúp 1 Blueprint phục vụ nhiều tổ chức.
6. **Deployment luôn tạo Runtime mới, không ghi đè** — một Organization Solution có thể deploy nhiều lần, sinh nhiều Runtime Instance độc lập.
7. **AI luôn phải theo ngữ cảnh** (Business Solution, Blueprint, Configuration, Runtime, Knowledge, Permission Context) — không hard-code, không tự ý thay đổi Runtime nếu không qua Action được phép rõ ràng.
8. **Một Runtime Engine / AI Engine / Dashboard Engine / Report Engine duy nhất dùng chung cho mọi Vertical** — chỉ Blueprint thay đổi theo ngành, phần lõi kỹ thuật giữ nguyên.
9. **Năm loại dữ liệu phải tách biệt rõ ràng**: Master Data, Definition Data, Configuration Data, Runtime Data, Intelligence Data — không trộn lẫn để tránh hệ thống khó mở rộng/bảo trì.
10. **Kiến trúc Modular Monolith** (Laravel + NWIDART Modules) — một database, chia bảng/module theo trách nhiệm nghiệp vụ, giao tiếp nội bộ qua Action/Service/Event/Job, không microservices, không API nội bộ.
11. **Mọi Runtime/Configuration Data bắt buộc có `organization_id`** (multi-tenant); mọi thao tác quan trọng phải được audit qua `activities`/Activity Log bất biến.

Đây là bộ nguyên tắc nền tảng để bất kỳ module Laravel nào (VD: OcopRubric, hoặc các module tương lai như BusinessBlueprint, Deployment, ProjectRuntime...) được xây dựng đúng theo định hướng kiến trúc đã chốt của THUCHOCVN Vertical AI Platform.
