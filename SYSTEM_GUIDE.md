# MinHan Platform — Hướng dẫn Vận hành & Luồng Người dùng

> Phiên bản: 2.0 | Cập nhật: 2026-06-25 | Bám sát codebase thực tế

---

## Mục lục

1. [Tổng quan Hệ thống](#1-tổng-quan-hệ-thống)
2. [Kiến trúc Kỹ thuật](#2-kiến-trúc-kỹ-thuật)
3. [Triển khai & Khởi động](#3-triển-khai--khởi-động)
4. [Luồng Xác thực Người dùng](#4-luồng-xác-thực-người-dùng)
5. [Multi-tenancy — Phân tách Tổ chức](#5-multi-tenancy--phân-tách-tổ-chức)
6. [Hệ thống Phân quyền RBAC](#6-hệ-thống-phân-quyền-rbac)
7. [Cấu trúc Điều hướng (Sidebar)](#7-cấu-trúc-điều-hướng-sidebar)
8. [Luồng HTTP Request End-to-End](#8-luồng-http-request-end-to-end)
9. [Luồng Người dùng theo Vai trò](#9-luồng-người-dùng-theo-vai-trò)
10. [Module CRM — Lead & Khách hàng](#10-module-crm--lead--khách-hàng)
11. [Module HR & Nhân sự](#11-module-hr--nhân-sự)
12. [Module Năng lực số & Assessment](#12-module-năng-lực-số--assessment)
13. [Module Tuyển dụng (ATS)](#13-module-tuyển-dụng-ats)
14. [Module Quy trình SOP & Workflow](#14-module-quy-trình-sop--workflow)
15. [Module Dự án & Công việc](#15-module-dự-án--công-việc)
16. [Module Kho tri thức](#16-module-kho-tri-thức)
17. [Module AI Copilot](#17-module-ai-copilot)
18. [Module Marketplace Center](#18-module-marketplace-center)
19. [Module Báo cáo & KPI](#19-module-báo-cáo--kpi)
20. [Hệ thống Thông báo](#20-hệ-thống-thông-báo)
21. [Hệ thống Queue & Job nền](#21-hệ-thống-queue--job-nền)
22. [Media & File Upload](#22-media--file-upload)
23. [Audit Log & Giám sát](#23-audit-log--giám-sát)
24. [Subscription & Billing](#24-subscription--billing)
25. [Tích hợp Bên ngoài](#25-tích-hợp-bên-ngoài)
26. [Vận hành Hàng ngày](#26-vận-hành-hàng-ngày)
27. [Troubleshooting Thực tế](#27-troubleshooting-thực-tế)
28. [Phụ lục: Artisan & Env](#28-phụ-lục-artisan--env)

---

## 1. Tổng quan Hệ thống

**MinHan** là nền tảng SaaS quản lý doanh nghiệp đa thuê bao (multi-tenant), tích hợp HR, CRM, tuyển dụng, tri thức tổ chức và tự động hóa vận hành, có thêm lớp đánh giá **năng lực số AI** cho nhân sự.

### Phạm vi chức năng thực tế (34 modules)

| Nhóm (sidebar) | Module | Mô tả |
|---|---|---|
| **Chính** | Survey, Assessment | Khảo sát cá nhân + chấm điểm năng lực |
| **CRM** | Lead, Customer, LeadPipelineStage, LeadSource | Quản lý cơ hội bán hàng, khách hàng |
| **Tổ chức** | Organization, Branch, Department, JobTitle, Employee, Leave, KpiGoal, PerformanceReview, Project, OrgChart, JobPosting, Marketplace, Recruitment, RoleScope, AiCopilot, Task | Toàn bộ vận hành nội bộ |
| **Kho tri thức** | KcCategory, KcItem (+ Tags, Analytics) | Knowledge base nội bộ |
| **Năng lực số** | Workforce, Sandbox, Certification, CareerPathway, AiImpact, Passport, Campaigns | Digital Twin & AI assessment ecosystem |
| **Vận hành** | Sop | Standard Operating Procedures |
| **Hệ thống** | ActivityLog, Subscription/Billing, Workflow, Settings, User | Quản trị hệ thống |
| **Phân tích** | Report (HR, Sales, Dự án, KPI) | BI & báo cáo phân tầng |
| **Triển khai** | Deployment | Hub triển khai theo vertical |

### Tech Stack chính xác

```
Backend:   Laravel 13 (PHP 8.4)
Database:  SQLite (dev) / MySQL / PostgreSQL (prod) — 266 migrations
Modules:   NWIDART Laravel Modules (34 modules, 32 có routes/web.php)
Auth:      Laravel Fortify (login/register/reset) + Sanctum (API/SPA)
RBAC:      Spatie Laravel Permissions — 8 roles, 90+ permissions
Frontend:  Vite 8 + Tailwind CSS 4 + DaisyUI 5 + Alpine.js 3 + jQuery
WebSocket: Laravel Reverb + Echo.js
Queue:     Redis + Laravel Horizon
AI:        Anthropic Claude API (anthropic-ai/sdk) + OpenAI PHP
Email:     Resend
SMS/OTP:   ZBS Zalo ZNS
CAPTCHA:   Cloudflare Turnstile
Media:     Spatie Media Library (local / S3)
Logging:   Spatie ActivityLog (automatic model tracking)
PDF/PNG:   Spatie Browsershot
Search:    Tabulator.js (client-side, AJAX-powered)
```

---

## 2. Kiến trúc Kỹ thuật

### 2.1 Cấu trúc Thư mục gốc

```
/var/www/html/minhan/
├── app/
│   ├── Enums/              # RoleEnum, PermissionEnum, AccountType
│   ├── Foundation/         # TenantAwareModel, TenantAwareJob (base classes)
│   ├── Http/Middleware/    # IdentifyOrganization, SecurityHeaders, CheckSubscription...
│   ├── Models/             # User, Media, ZbsOauthToken, NotificationPreference...
│   ├── Shared/Tenancy/     # TenantContext (singleton), Organization model, OrganizationScope
│   └── Providers/          # AppServiceProvider, AuthServiceProvider, EventServiceProvider
├── Modules/                # 34 feature modules (NWIDART)
├── bootstrap/app.php       # Middleware registration & ordering
├── config/permissions.php  # Role → Permission mapping (nguồn sự thật RBAC)
├── database/migrations/    # 217 generated + 49 extensions = 266 tổng
├── resources/
│   ├── js/app.js           # Entry: Alpine 3, jQuery, Echo, admin-shell
│   ├── js/admin-shell.js   # Sidebar, header, theme, keyboard shortcuts
│   └── views/layouts/      # backend.blade.php, auth.blade.php, partials/
├── routes/
│   ├── web.php             # Dashboard, media, notifications, integrations
│   └── api.php             # Sanctum-protected JSON endpoints
└── vite.config.backend.js  # Build → public/build/backend/
```

### 2.2 Module Structure (AVSA — Advanced Vertical Slice)

Mỗi module trong `Modules/{Name}/` tuân theo cấu trúc:

```
Modules/Lead/
├── app/
│   ├── Models/Lead.php             # extends TenantAwareModel (auto soft-delete + actlog)
│   ├── Http/Controllers/           # Request handlers (thin — gọi Actions/Queries)
│   ├── Actions/                    # Business logic thuần (CreateLeadAction, AssignLeadAction...)
│   ├── Queries/                    # Data retrieval tách biệt (LeadIndexQuery...)
│   ├── Events/ & Listeners/        # Domain events (LeadCreated, LeadStageChanged...)
│   ├── Jobs/                       # Async tasks
│   ├── Policies/LeadPolicy.php     # Fine-grained authorization
│   └── Enums/                      # LeadStatus, LeadSortField...
├── database/migrations/
├── routes/web.php                  # Route::resource, custom actions
└── resources/
    ├── views/                      # Blade templates (index, create, edit, show)
    └── assets/                     # lead.scss, lead.js (lazy-loaded per page)
```

**Pattern controller:**
```php
// Controller chỉ orchestrate — không chứa business logic
public function store(CreateLeadRequest $request)
{
    $this->authorize('leads.create');           // Policy check
    $lead = CreateLeadAction::run($request->validated());  // Action xử lý
    return redirect()->route('lead.show', $lead)->with('success', '...');
}
```

### 2.3 Middleware Stack thực tế (`bootstrap/app.php`)

**Thứ tự thực thi trên mọi Web request:**

```
[PREPEND — chạy đầu tiên]
1.  RemoveServerHeaders          → bỏ "Server:", "X-Powered-By" headers
2.  InjectRequestId              → gắn X-Request-Id UUID cho mỗi request (tracing)

[CORE LARAVEL — session, cookie, CSRF]
3.  EncryptCookies
4.  AddQueuedCookiesToResponse
5.  StartSession
6.  ShareErrorsFromSession
7.  VerifyCsrfToken              → except: billing/webhook/*

[WEB GROUP — theo thứ tự khai báo]
8.  IdentifyOrganization         ← QUAN TRỌNG NHẤT: resolve tenant, set TenantContext
9.  SubstituteBindings           → route model binding (sau khi có TenantContext)
10. Authenticate (auth)          → redirect /login nếu chưa đăng nhập

[WEB APPEND — cuối pipeline]
11. CaptureHttpContext           → ghi request/response vào audit context
12. CheckSubscription            → kiểm tra subscription còn hiệu lực
13. SecurityHeaders              → CSP, HSTS, X-Frame-Options, X-Content-Type-Options

[API PREPEND]
    EnsureFrontendRequestsAreStateful → cho phép session-based auth từ SPA (AJAX)

[API APPEND]
    ThrottleRequests (60/min)
    CaptureHttpContext
```

**Middleware aliases:**
```
tenant          → AssertTenant (throw nếu TenantContext chưa được set)
vertical        → RequireVertical (org phải kích hoạt vertical cụ thể)
feature         → CheckFeature
permission      → Spatie CheckPermission
role            → Spatie CheckRole
role_or_permission → Spatie CheckRoleOrPermission
```

---

## 3. Triển khai & Khởi động

### 3.1 Cài đặt lần đầu

```bash
# 1. Clone và dependencies
git clone <repo> /var/www/html/minhan
cd /var/www/html/minhan
composer install --no-dev --optimize-autoloader
npm install

# 2. Cấu hình môi trường
cp .env.example .env
php artisan key:generate
# → Chỉnh sửa .env: DB, Redis, Mail, AI keys...

# 3. Build frontend
npx vite build --config vite.config.backend.js
# Output: public/build/backend/ (CSS + JS bundles có hash)

# 4. Database
php artisan migrate
php artisan db:seed                  # → gọi SystemDataSeeder
php artisan permissions:sync         # Đồng bộ config/permissions.php → DB

# 5. Storage
php artisan storage:link             # public/storage → storage/app/public

# 6. Cache production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3.2 Development

```bash
# Terminal 1: Vite + serve + queue + Pail (tất cả trong một)
npm run dev

# Terminal 2: WebSocket (cần cho real-time notifications)
php artisan reverb:start --debug

# Terminal 3 (nếu dùng Redis queue):
php artisan horizon
```

### 3.3 Cấu hình Queue Worker (Production)

```ini
# /etc/supervisor/conf.d/minhan-worker.conf
[program:minhan-worker]
command=php /var/www/html/minhan/artisan queue:work redis \
        --queue=high,default,low,workflows,webhooks,ai,actlog,passport \
        --tries=3 --backoff=60 --memory=256

autostart=true
autorestart=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/minhan/worker.log
```

```bash
# Crontab (Artisan Scheduler)
* * * * * cd /var/www/html/minhan && php artisan schedule:run >> /dev/null 2>&1
```

### 3.4 Queue Priorities

| Queue | Loại Job | Ưu tiên |
|-------|---------|---------|
| `high` | OTP SMS, thông báo khẩn | Cao nhất |
| `default` | Email, CRUD side-effects | Bình thường |
| `low` | Export file, batch report | Thấp |
| `workflows` | Workflow execution steps | Bình thường |
| `webhooks` | Gửi webhook ra ngoài | Thấp |
| `ai` | Claude API requests | Bình thường |
| `actlog` | Ghi audit log (không block request) | Thấp nhất |
| `passport` | Assessment passport operations | Bình thường |

---

## 4. Luồng Xác thực Người dùng

### 4.1 Đăng nhập (Login)

```
Browser: GET /login
        → auth.blade.php layout (không có sidebar/header)
        → Form: email + password + Turnstile CAPTCHA

Browser: POST /login
        │
        ├─── [1] VerifyTurnstile Middleware → xác minh Cloudflare CAPTCHA
        ├─── [2] Fortify LoginController → bcrypt verify password
        ├─── [3] Kiểm tra email_verified_at (nếu bật email verification)
        ├─── [4] Tạo Session (Laravel Session → Redis hoặc file)
        │
        ▼ Success:
IdentifyOrganization Middleware
        ├─── User.organization_id → load Organization từ DB
        ├─── Cache org 2-5 phút (tránh query lặp)
        └─── TenantContext::set($organization)

        ▼
Redirect: GET /dashboard  (route: backend.dashboard)
```

**Fortify routes thực tế:**
```
GET  /login                 → Fortify (blade: auth::login)
POST /login                 → Fortify login processing
POST /logout                → Session invalidate + auth:sanctum guard
GET  /register              → Fortify (blade: auth::register)
POST /register              → Fortify register
GET  /forgot-password       → Fortify (blade: auth::forgot-password)
POST /forgot-password       → Gửi password reset email via Resend
GET  /reset-password/{token}→ Fortify (blade: auth::reset-password)
POST /reset-password        → Fortify reset + redirect /login
```

### 4.2 OAuth Social Login (Socialite)

```
GET /auth/social/{provider}        → SocialAuthController::redirect()
    provider = google | facebook | github
    Facebook → thêm scope: email
        │
        ▼ (redirect đến provider OAuth)

GET /auth/social/{provider}/callback → SocialAuthController::callback()
        │
        ├─── Lấy user từ Socialite
        ├─── Tìm social_accounts theo provider + provider_id
        │
        ├─── [Chưa có account] → Tạo User mới:
        │         name = provider.name
        │         email = provider.email (nếu không có → {id}@{provider}.noreply)
        │         password = random (không thể login bằng password)
        │         Tạo SocialAccount record
        │
        ├─── [Đã có account] → Đăng nhập user hiện tại
        │
        └─── Redirect → backend.dashboard

DELETE /auth/social/{provider}     → Unlink OAuth account (giữ lại account)
```

### 4.3 Đăng ký Tổ chức Mới (Register)

```
POST /register
        │
        ├─── Validate: name, email, password, password_confirmation
        ├─── Tạo User record (password được hash tự động)
        ├─── Tạo Organization:
        │         name = "{username}'s Organization" (hoặc từ form)
        │         slug = auto-generated từ name (unique, slug-1, slug-2...)
        │         uuid = generated
        │         owner_id = user.id
        ├─── user.organization_id = org.id
        ├─── Tạo OrganizationMember:
        │         role = 'owner'
        │         status = 'active'
        │         joined_at = now()
        ├─── Gán Role 'ceo' cho user (Spatie)
        ├─── Gửi email xác nhận (nếu bật)
        └─── Auto-login + Redirect → /dashboard
```

### 4.4 Trang Profile cá nhân

```
GET /auth/profile          → blade: auth::profile
    Chỉnh sửa: name, email, phone_number, avatar
    Đổi password
    Linked social accounts (xem + unlink)
    Notification preferences
    
GET /auth/me               → JSON (API): context hiện tại
    Response:
    {
        "user": { id, name, email, account_type, trust_level },
        "organization": { id, name, slug, status },
        "roles": ["ceo"],
        "permissions": [...]  // chỉ có nếu role = system_admin
    }
```

### 4.5 API Auth (Sanctum + SPA)

Tất cả AJAX requests từ frontend gửi kèm:
- Cookie session (tự động — browser gửi cùng domain)
- Header `X-CSRF-TOKEN` (lấy từ `<meta name="csrf-token">` trong layout)

```
Middleware EnsureFrontendRequestsAreStateful:
  → Cho phép session cookie auth với origin = APP_URL
  → Không cần Bearer token cho SPA trên cùng domain

API routes có rate limit: 60 requests/phút
```

---

## 5. Multi-tenancy — Phân tách Tổ chức

### 5.1 Model dữ liệu

```sql
organizations
  id, uuid, slug, name, status (active|inactive|suspended),
  is_system (bool), owner_id, email, email_domain,
  website, source, approved_by, approved_at,
  settings (JSON), created_at

users
  id, name, email, password, organization_id,
  account_type, trust_level, phone_number,
  is_active (bool), email_verified_at, last_active_at

organization_members
  id, organization_id, user_id,
  role (owner|admin|member),
  status (active|invited|inactive),
  invited_at, joined_at, exited_at, exit_reason
```

### 5.2 TenantContext — Static Singleton

```php
// app/Shared/Tenancy/TenantContext.php
TenantContext::set($org)            // IdentifyOrganization middleware gọi
TenantContext::get()                // → Organization | null
TenantContext::resolve()            // → Organization | throw TenantNotSetException
TenantContext::getOrganizationId()  // → int | null
TenantContext::isSet()              // → bool
TenantContext::flush()              // Jobs/Tests: xóa context sau khi xong
TenantContext::runForOrganization($org, fn() => ...) // Chạy callback trong context khác
```

### 5.3 Luồng Xác định Tổ chức (IdentifyOrganization)

Chạy trên **mọi request**, theo thứ tự ưu tiên:

```
1. SUBDOMAIN (ưu tiên cao nhất)
   acme.minhan.app → slug = "acme"
   → Cache key: "org:slug:acme" (TTL 2 phút — ngắn để reflect suspend nhanh)
   → Organization::whereSlug("acme")->where("status", "active")->first()

2. X-Organization-ID HEADER
   X-Organization-ID: 42
   → Kiểm tra auth user có OrganizationMember ở org đó không
   → Chỉ dành cho API clients biết org ID

3. AUTH USER (luồng web thông thường)
   auth()->user()->organization_id → Organization::find(id)

4. SESSION (fallback trong cùng browser session)
   session("organization_id") → từ request trước đó

5. SYSTEM ORG (super-admin fallback)
   User có role 'super-admin' (không thuộc org nào)
   → Organization::where("is_system", true)->first()

[Non-blocking]: Nếu không resolve được → request vẫn tiếp tục (guest pages)
[Assert tenant]: Route có middleware 'tenant' → throw 403 nếu không có context
```

### 5.4 TenantAwareModel — Auto Scoping

```php
// app/Foundation/TenantAwareModel.php
abstract class TenantAwareModel extends Model
{
    use HasFactory;
    use SoftDeletes;            // deleted_at soft delete
    use BelongsToOrganization;  // auto scope + auto fill organization_id
    use LogsActivity;           // Spatie: log mọi thay đổi fillable
}

// BelongsToOrganization trait:
// - Boot: thêm global scope OrganizationScope (WHERE organization_id = ?)
// - Creating: tự động gán organization_id = TenantContext::getOrganizationId()
// - Scope withoutTenant(): bypass filter (dùng cho system queries)

// Ví dụ:
Lead::all()
// → SELECT * FROM leads WHERE organization_id = 42 AND deleted_at IS NULL

Lead::withoutTenant()->all()
// → SELECT * FROM leads WHERE deleted_at IS NULL (bypass tenant)
```

### 5.5 Verticals — Feature Gates theo Tổ chức

```
Mỗi Organization kích hoạt "verticals" (nhóm tính năng):
  lead, recruitment, assessment, hr, marketplace, ...

Middleware 'vertical:{code}':
  → Kiểm tra org.verticals chứa code không
  → 403 nếu chưa kích hoạt

Route example:
  Route::middleware(['auth', 'vertical:lead'])->group(fn() => ...);
```

---

## 6. Hệ thống Phân quyền RBAC

### 6.1 Các Vai trò (Roles)

| Role Enum | Value DB | Label hiển thị | Badge |
|-----------|----------|----------------|-------|
| `RoleEnum::CEO` | `ceo` | CEO / Founder | badge-primary |
| `RoleEnum::SALES` | `sales` | Sales Team | badge-success |
| `RoleEnum::OPS` | `ops` | Operations | badge-warning |
| `RoleEnum::MARKETING` | `marketing` | Marketing | badge-accent |
| `RoleEnum::HR` | `hr` | HR / Admin Staff | badge-info |
| `RoleEnum::AI_OP` | `ai_operator` | AI Operator | badge-secondary |
| `RoleEnum::ADMIN` | `system_admin` | System Admin | badge-error |
| `RoleEnum::VIEWER` | `viewer` | Viewer / Partner | badge-ghost |

### 6.2 Ma trận Phân quyền Chi tiết

Nguồn sự thật: `config/permissions.php`. Sau khi thay đổi phải chạy `php artisan permissions:sync`.

#### CEO (`ceo`)
```
CEO Dashboard: Full (ceo_dash.full)
CRM Leads: View All + CRUD + Assign + Export (KHÔNG có manage_pipeline/sources/tags)
CRM Customers: View All + CRUD + Export
Sales AI: View + Use (KHÔNG config)
Tasks: View All + CRUD + Assign + Close
SOP: View + Approve (KHÔNG Create/Edit — CEO chỉ duyệt)
Workflow: Monitor Only (KHÔNG Edit)
AI Copilot: Use + View Usage
Users: View (KHÔNG manage — chỉ xem)
Reports: Full (tất cả loại)
Assessment: View + Results
Job Posting: Full (View/Create/Edit/Delete/Publish)
Recruitment: View
Marketplace: View
Subscription: View + Manage + Billing
```

#### SALES (`sales`)
```
CRM Leads: View Assigned + Create + Edit (Policy: chỉ lead assigned_to === user.id)
CRM Customers: View Assigned + Create + Edit
Sales AI: Use (không config)
Tasks: View Assigned + Create
SOP: View Related (dept liên quan)
Workflow: View Limited (public only)
AI Copilot: Use
Reports: Personal + Team
```

#### OPS (`ops`)
```
CEO Dashboard: View Limited
CRM Leads: View All + Export + Manage Tags (KHÔNG Edit/Delete/Assign)
CRM Customers: View All + Create + Edit + Export
Tasks: Full (View All + CRUD + Assign + Close)
SOP: View + Create + Edit (KHÔNG Approve)
Workflow: Monitor + Edit
AI Logs: View
AI Copilot: Use + View Usage
Reports: Operations scope
Assessment: View + Results
Job Posting: View only
Subscription: View + Billing
```

#### MARKETING (`marketing`)
```
CRM Leads: View Source (chỉ xem lead có source, ẩn phone/email)
CRM Customers: View All
Sales AI: View (không use/config)
Tasks: View Limited (public tasks only)
SOP: View Related
Workflow: View Limited
AI Copilot: Use
Reports: Marketing scope
Marketplace: View
```

#### HR (`hr`)
```
Tasks: View Dept (chỉ dept=hr) + Create
SOP: View + Create HR SOP (chỉ SOP liên quan HR)
Workflow: View Limited
AI Copilot: Use
Users: HR (tạo user onboarding)
Reports: HR scope
Job Posting: Full (View/Create/Edit/Publish)
Recruitment: Full (View/Create/Edit/Manage)
Marketplace: View + Create + Edit
```

#### AI Operator (`ai_operator`)
```
CEO Dashboard: View Limited
CRM Leads: View All (không edit)
CRM Customers: View All
Sales AI: Config Prompt
Tasks: View Limited
SOP: AI Config
Workflow: Monitor + AI Config
Prompt Management: Full
AI Logs: Full
AI Copilot: Use + Config + View Usage
Reports: AI Usage scope
Assessment: Full (View/Config/Results/Reprocess)
```

#### System Admin (`system_admin`)
```
Subscription: Full Admin (bao gồm manage plans toàn hệ thống)
Config trên TẤT CẢ modules: ceo_dash.config, leads.config, customers.config, tasks.config...
CRM: View All + manage_pipeline + manage_sources + manage_tags
Workflow: Full Config
Prompt: Admin Config
AI Logs: Full
AI Copilot: Use + Config + View Usage
Users: Manage (full CRUD)
Roles: Manage
System: Integration Manage + Audit View + System Config
Reports: Full
Assessment: Full
Job Posting: Manage
Recruitment: Full Manage
Marketplace: Full Manage (approve organizations)
```

#### VIEWER (`viewer`)
```
CEO Dashboard: View Limited
Tasks: View Limited (public only)
SOP: View (public/shared)
Reports: Shared only
— KHÔNG có: CRM, Leads, Workflow, Prompt, AI, Users
```

### 6.3 Kiểm tra Quyền trong Code

```php
// 1. Controller (HTTP layer)
$this->authorize('leads.create');
$this->authorize('update', $lead);  // Policy-based

// 2. Blade template
@can('leads.edit')
    <a href="{{ route('lead.edit', $lead) }}">Sửa</a>
@endcan

@if(auth()->user()->hasAnyPermission(['assessment.results', 'assessment.config']))
    {{-- Admin assessment UI --}}
@endif

// 3. Route middleware
Route::middleware(['permission:leads.view_all'])->group(fn() => ...);
Route::middleware(['role:ceo,system_admin'])->group(fn() => ...);

// 4. Policy (fine-grained data-level)
// Ví dụ LeadPolicy::update():
//   SALES chỉ update lead nếu lead.assigned_to === user.id
//   CEO/OPS có thể update mọi lead
```

### 6.4 Đồng bộ sau khi thay đổi

```bash
# Sau khi chỉnh config/permissions.php
php artisan permissions:sync

# Nếu thêm role mới
php artisan db:seed --class=RolePermissionSeeder

# Clear cache permission (Spatie cache)
php artisan permission:cache-reset
```

---

## 7. Cấu trúc Điều hướng (Sidebar)

Sidebar (`resources/views/layouts/partials/sidebar.blade.php`) render động theo quyền thực tế.

### 7.1 Sơ đồ Sidebar hoàn chỉnh

```
CHÍNH
├── Dashboard                     (mọi user)
├── Khảo sát [của tôi]            (auth — @auth)
└── Quản lý khảo sát              (@can survey.view)
    ├── Danh sách khảo sát        (route: backend.surveys.index)
    └── Tạo khảo sát              (@can survey.create)

    Chấm điểm (Assessment)        (hasAnyPermission assessment.view/config/results)
    ├── Danh sách Assessment      (route: assessments.index, @can assessment.view)
    └── Tạo Assessment mới        (route: assessments.create, @can assessment.config)

CRM                               (hasAnyPermission leads.view_all/view_assigned/view_source)
├── Cơ hội (Lead)
│   ├── Danh sách cơ hội          (route: lead.index)
│   ├── Thêm cơ hội               (@can leads.create)
│   ├── Pipeline stages           (@can leads.manage_pipeline)
│   ├── Nguồn cơ hội              (@can leads.manage_sources)
│   └── Tags                      (@can leads.manage_tags)
└── Khách hàng                    (hasAnyPermission customers.view_all/view_assigned)
    ├── Danh sách khách hàng      (route: customer.index)
    └── Thêm khách hàng           (@can customers.create)

TỔ CHỨC
├── Tổ chức                       (route: backend.organizations.*)
│   ├── Danh sách tổ chức
│   └── Thêm tổ chức
├── Chi nhánh                     (@can viewAny Branch)
├── Phòng ban                     (@can viewAny Department)
├── Chức danh                     (@can viewAny JobTitle)
├── Nhân viên                     (@can viewAny Employee)
├── Nghỉ phép                     (@can viewAny LeavePolicy)
│   ├── Đơn nghỉ phép             (route: backend.leave.requests.index)
│   ├── Số dư của tôi             (route: backend.leave.balances.me)
│   └── Chính sách nghỉ phép      (route: backend.leave.policies.index)
├── KPI Goals                     (@can viewAny KpiGoal)
│   ├── Mục tiêu KPI
│   ├── Bảng xếp hạng             (@can viewLeaderboard KpiGoal)
│   └── Thêm mục tiêu             (@can create KpiGoal)
├── Đánh giá hiệu suất            (@can viewAny PerformanceReview)
│   ├── Danh sách đánh giá
│   ├── Tạo đánh giá              (@can create PerformanceReview)
│   └── Mẫu đánh giá              (@can create PerformanceReview)
├── Dự án                         (@can viewAny Project)
│   ├── Danh sách dự án
│   └── Tạo dự án mới             (@can create Project)
├── AI Copilot                    (hasAnyPermission ai_copilot.use/config/prompt.full)
│   ├── Usage Dashboard           (@can ai_copilot.view_usage)
│   ├── Request Logs              (@can ai_logs.full)
│   ├── AI Agents                 (@can ai_copilot.config)
│   └── Prompt Library            (@can prompt.full)
├── Công việc (Task)              (@can viewAny Task)
│   ├── Danh sách công việc
│   └── Thêm công việc            (@can create Task)
├── Sơ đồ tổ chức                 (@can viewAny OrgChartConfig)
├── Tin tuyển dụng                (@can viewAny JpJobPost)
├── Marketplace Center            (@can marketplace.view)
│   ├── Tin đăng
│   ├── Đăng tin mới              (@can marketplace.create)
│   ├── Analytics
│   └── Duyệt tổ chức             (@can marketplace.manage)
│   [Badge: số tin out_of_sync — cached 60s]
├── Tuyển dụng (ATS)              (@can recruitment.view)
│   ├── Danh sách ứng viên
│   ├── Thêm ứng viên             (@can recruitment.create)
│   ├── Lịch phỏng vấn của tôi
│   ├── Analytics                 (@can recruitment.manage)
│   └── Pipeline Stages           (@can recruitment.manage)
└── Phân quyền phạm vi            (@can viewAny UserRoleScope)

KHO TRI THỨC                      (@can viewAny KcCategory)
├── Danh mục KC
├── Tài liệu KC
│   ├── Tất cả tài liệu
│   ├── Chờ duyệt                 (filter status=pending_review)
│   └── Tạo tài liệu              (@can create KcItem)
├── Tags KC                       (@can viewAny KcTag)
└── Analytics KC

NĂNG LỰC SỐ                       (@auth — mọi user đã login)
├── Hồ sơ Digital Twin            (route: backend.workforce.me — cá nhân)
├── AI Sandbox                    (route: backend.sandbox.index)
├── Chứng nhận AI                 (route: backend.certifications.index)
├── Lộ trình nghề nghiệp          (route: backend.career-pathway.index)
├── AI Impact Tracker             (route: backend.ai-impact.index)
├── Career Journal                (route: passport.index)
├── Assessment Marketplace        (route: campaigns.index)
├── Workforce Admin               (hasAnyPermission assessment.results/config)
├── Sandbox Admin                 (@can assessment.config)
├── Pathway Admin                 (@can assessment.config)
└── Certs Admin                   (@can assessment.config)

VẬN HÀNH                          (hasAnyPermission sop.*)
└── Quy trình SOP
    ├── Danh sách SOP
    └── Tạo SOP mới               (hasAnyPermission sop.create/create_hr/config)

TÀI KHOẢN
├── Tài khoản (Users)
│   ├── Danh sách tài khoản       (route: backend.users.index)
│   └── Thêm tài khoản
└── Thông báo                     (route: backend.notifications.index)

HỆ THỐNG
├── Activity Log                  (@can activitylog.view)
├── Billing (Subscription portal) (@can subscription.view)
│   ├── Subscription
│   ├── Xem các gói
│   └── Hóa đơn                   (@can subscription.billing)
├── Subscription Admin            (@can subscription.admin)
│   ├── Quản lý Plans
│   ├── Subscriptions
│   └── Invoices
├── Workflow                      (@can workflow.monitor)
│   ├── Danh sách workflow
│   └── Tạo workflow mới          (@can workflow.edit)
└── Cài đặt
    ├── Chung
    ├── Thanh toán
    ├── Vận chuyển
    ├── Email
    └── Zalo ZNS (OTP)            (hasRole super-admin|system_admin)

PHÂN TÍCH                         (hasAnyPermission reports.*)
└── Báo cáo
    ├── Tổng quan
    ├── Nhân sự (HR)              (hasAnyPermission reports.hr/full)
    ├── Sales & CRM               (hasAnyPermission reports.team/personal/full)
    ├── Dự án                     (hasAnyPermission reports.ops/full)
    └── KPI                       (hasAnyPermission reports.ops/full)

TRIỂN KHAI                        (@auth)
├── Hub triển khai                (route: deployment.landing)
└── [Per active vertical]         (route: deployment.dashboard, vertical={code})

USER CARD (cuối sidebar)
├── Avatar (DiceBear initials API)
├── Tên + Email
├── Link hồ sơ cá nhân            (route: auth.profile)
└── Nút đăng xuất                 (POST /logout)
```

---

## 8. Luồng HTTP Request End-to-End

### 8.1 Web Request (Server-rendered HTML)

```
Browser → Nginx → PHP-FPM
                    │
            ┌───────▼────────────────────────────────────────────┐
            │               MIDDLEWARE PIPELINE                    │
            │  RemoveServerHeaders → InjectRequestId              │
            │  → StartSession → VerifyCsrfToken                   │
            │  → IdentifyOrganization (SET TenantContext)         │
            │  → Authenticate (redirect /login if guest)          │
            │  → SubstituteBindings (route model binding)         │
            │  → CaptureHttpContext → CheckSubscription           │
            │  → SecurityHeaders                                   │
            └───────┬────────────────────────────────────────────┘
                    │
            ┌───────▼────────────────────────┐
            │         CONTROLLER              │
            │  1. authorize() / Policy check  │
            │  2. Validate Request            │
            │  3. Call Action (business logic)│
            │  4. Return view / redirect      │
            └───────┬────────────────────────┘
                    │
            ┌───────▼────────────────────────┐
            │       MODEL / DATABASE          │
            │  Auto-scope: WHERE org_id = ?   │
            │  Soft delete: WHERE deleted_at  │
            │  Auto-log: Spatie ActivityLog   │
            └───────┬────────────────────────┘
                    │
            ┌───────▼────────────────────────┐
            │      BLADE VIEW RENDER          │
            │  layouts/backend.blade.php      │
            │  → sidebar (dynamic per perms)  │
            │  → @yield('content')            │
            │  → @push('scripts') lazy JS     │
            └────────────────────────────────┘
```

### 8.2 AJAX / API Request

```
Browser JS (Alpine, Tabulator, jQuery)
  ↓ fetch/$.ajax với:
    Cookie: session (auto)
    Header: X-CSRF-TOKEN: {meta csrf-token}
    Accept: application/json

Nginx → PHP-FPM
  │
  ├── Route: /api/v1/... hoặc /api/dashboard/...
  │
  ├── Middleware:
  │     EnsureFrontendRequestsAreStateful (session auth cho SPA)
  │     auth:sanctum
  │     ThrottleRequests (60/min)
  │     tenant (nếu khai báo)
  │     CaptureHttpContext
  │
  └── Controller → JsonResource / plain JSON
```

### 8.3 Queue Job Flow

```
Controller → dispatch(new SomeJob(...))
                    │ (Redis: queue=high/default/ai/...)
                    ▼
Queue Worker (artisan queue:work)
                    │
            TenantAwareJob::handle():
            ├── TenantContext::set($this->organization)
            ├── Execute business logic
            ├── Fire events / send notifications
            └── TenantContext::flush()  ← cleanup
```

---

## 9. Luồng Người dùng theo Vai trò

### 9.1 CEO — Luồng điển hình

```
[Đăng nhập]
  /login → Dashboard (ceo_dash.full)
      │
      ├── Dashboard widgets:
      │     - Biểu đồ task throughput (ECharts)
      │     - Lead funnel chart
      │     - Workflow health
      │     - Headcount overview
      │
      ├── /leads → Xem TẤT CẢ leads của org
      │     Filter: stage, source, assignee, date range
      │     Export CSV
      │     Xem detail → activities, notes, score
      │
      ├── /dashboard/organizations → Quản lý org settings
      │
      ├── /report/* → Full reports (HR, Sales, Project, KPI)
      │
      └── /workflows → Monitor workflow executions (không edit)
```

### 9.2 SALES — Luồng điển hình

```
[Đăng nhập]
  /login → Dashboard
      │
      ├── /leads → Chỉ thấy leads có assigned_to = mình
      │     Policy: LeadPolicy::viewAny() → filter by assigned_to
      │     Thêm lead mới → điền thông tin contact, stage, source
      │     Chuyển stage → POST /leads/{id}/change-stage
      │     Ghi chú → POST /leads/{id}/notes
      │     Gắn file → FilePond upload
      │
      ├── /customers → Chỉ customers được assign
      │
      ├── /tasks → Chỉ tasks assigned cho mình
      │
      └── /report/sales/pipeline → Báo cáo pipeline cá nhân
```

### 9.3 HR — Luồng điển hình

```
[Đăng nhập]
  /login → Dashboard
      │
      ├── /dashboard/employees → Quản lý nhân viên
      │     - Tạo employee profile
      │     - Gắn với User account (onboarding)
      │     - Ghi lịch sử nhân sự (thay đổi lương, chức vụ)
      │
      ├── /dashboard/job-posts → Tạo tin tuyển dụng
      │     → Publish → xuất hiện trên Marketplace
      │
      ├── /dashboard/recruitment/candidates → ATS
      │     - Xem ứng viên apply
      │     - Di chuyển qua pipeline stages
      │     - Lên lịch phỏng vấn
      │     - Ghi đánh giá
      │
      ├── /dashboard/leave/requests → Duyệt đơn nghỉ phép
      │
      └── /dashboard/users → Tạo user mới (onboarding)
            Gán role phù hợp
```

### 9.4 System Admin — Luồng điển hình

```
[Đăng nhập]
  /login → Dashboard
      │
      ├── /dashboard/users → Full CRUD users + gán roles
      ├── /dashboard/organizations → Quản lý tất cả orgs
      ├── /activitylog → Xem audit logs toàn hệ thống
      ├── /subscription/admin/* → Quản lý plans & subscriptions
      ├── /dashboard/settings/zbs → Cấu hình Zalo ZNS
      ├── /workflows → Full workflow config
      └── /dashboard/integrations/zbs → ZBS OAuth setup
```

### 9.5 AI Operator — Luồng điển hình

```
[Đăng nhập]
  /login → Dashboard
      │
      ├── /ai/prompts → Quản lý Prompt Library
      │     - Tạo/chỉnh sửa system prompts
      │     - Gán prompts cho AI agents
      │
      ├── /ai/agents → Cấu hình AI Agents
      │     - Khai báo agent capabilities
      │     - Gắn prompt template
      │
      ├── /ai/logs → Xem toàn bộ AI request logs
      │
      ├── /ai/usage → Usage dashboard (tokens, cost)
      │
      ├── /assessments → Cấu hình Assessment engine
      │     - ScoreRules
      │     - Domains & Maturity levels
      │     - Reprocess results
      │
      └── /workflows → Monitor + AI config cho workflow steps
```

---

## 10. Module CRM — Lead & Khách hàng

### 10.1 Routes Leads

```
GET    /leads                           → lead.index    (danh sách)
GET    /leads/create                    → lead.create   (form tạo mới)
POST   /leads                           → lead.store    (lưu)
GET    /leads/{lead}                    → lead.show     (chi tiết)
GET    /leads/{lead}/edit               → lead.edit     (form sửa)
PUT    /leads/{lead}                    → lead.update   (cập nhật)
DELETE /leads/{lead}                    → lead.destroy  (xóa mềm)
POST   /leads/{lead}/change-stage       → Chuyển pipeline stage
POST   /leads/{lead}/assign             → Assign cho sales rep
GET    /leads/{lead}/notes              → Danh sách ghi chú
POST   /leads/{lead}/notes              → Tạo ghi chú
DELETE /leads/{lead}/notes/{note}       → Xóa ghi chú
GET    /leads/export                    → Export CSV (@can leads.export)
GET    /leads/tags                      → lead.tags.index (danh sách tags)
POST   /leads/tags                      → Tạo tag
PUT    /leads/tags/{tag}                → Sửa tag
DELETE /leads/tags/{tag}               → Xóa tag
GET    /lead-pipeline-stages            → Config stages
POST   /lead-pipeline-stages           → Tạo stage
PUT    /lead-pipeline-stages/{stage}    → Sửa stage (name, color, order)
DELETE /lead-pipeline-stages/{stage}   → Xóa stage
GET    /lead-sources                    → Config sources
POST   /lead-sources                   → Tạo source
```

### 10.2 Luồng Tạo Lead Mới

```
1. Sales click "Thêm cơ hội"
2. Form điền:
   - Tiêu đề cơ hội (title) *bắt buộc*
   - Thông tin liên hệ: tên, điện thoại, công ty
   - Giai đoạn (stage) — từ lead_pipeline_stages của org
   - Nguồn (source) — từ lead_sources của org
   - Assign cho (assigned_to) — Tom Select search users
   - Giá trị dự kiến + đơn vị tiền tệ
   - Ngày dự kiến chốt (flatpickr date picker)
   - Gắn khảo sát (survey_response_id) → tự động tính lead_score

3. POST /leads → CreateLeadAction::execute()
   ├── Validate idempotent_key (tránh duplicate submit)
   ├── Lead::create([...]) ← auto gán organization_id, created_by
   ├── event(LeadCreated) → Listener gửi notification cho assigned_to
   └── Redirect → lead.show hoặc lead.index

4. Theo dõi:
   - Activities (ghi log mọi thay đổi tự động qua Spatie)
   - Notes thêm thủ công
   - Attachments qua FilePond
```

### 10.3 Lead Scoring

```
survey_response_id → link đến Survey module
        │
        ▼ CalculateSurveyScoreJob
        │
        ├── Tính điểm từng câu hỏi theo score_rules
        ├── Cộng dồn theo section_code
        └── Cập nhật leads.lead_score

Hiển thị: badge màu trên lead card
  80-100: badge-success (hot)
  50-79:  badge-warning (warm)
  0-49:   badge-error (cold)
```

### 10.4 Routes Customers

```
GET    /customers                       → customer.index
GET    /customers/create                → customer.create
POST   /customers                       → customer.store
GET    /customers/{customer}            → customer.show
GET    /customers/{customer}/edit       → customer.edit
PUT    /customers/{customer}            → customer.update
DELETE /customers/{customer}           → customer.destroy
GET    /customers/export                → Export (@can customers.export)
POST   /customers/{customer}/notes     → Tạo ghi chú
POST   /customers/{customer}/tags      → Gắn tag
GET    /customers/{customer}/leads     → Leads liên quan
```

---

## 11. Module HR & Nhân sự

### 11.1 Employee (Nhân viên)

```
Routes:
GET  /dashboard/employees             → backend.employees.index
GET  /dashboard/employees/create      → backend.employees.create
POST /dashboard/employees             → backend.employees.store
GET  /dashboard/employees/{id}        → backend.employees.show
GET  /dashboard/employees/{id}/edit   → backend.employees.edit
PUT  /dashboard/employees/{id}        → backend.employees.update
DELETE /dashboard/employees/{id}      → backend.employees.destroy
POST /dashboard/employees/{id}/history → Ghi lịch sử nhân sự

Dữ liệu nhân viên:
  - Hồ sơ cá nhân: personal_email, national_id (CCCD), date_of_birth, gender
  - Địa chỉ
  - Liên kết User account (user_id) → cho phép đăng nhập
  - Chức danh hiện tại (current_title → JobTitle)
  - Phòng ban & Chi nhánh
  
Lịch sử nhân sự (employee_history):
  event_type: hired | promoted | transferred | salary_change | terminated
  old_salary_base, new_salary_base, old_title, new_title
  effective_date, note
```

### 11.2 Department & Branch

```
Departments:
GET  /dashboard/departments           → danh sách phòng ban
POST /dashboard/departments           → tạo (name, head_id, deputy_head_id)
  → head_id: Tom Select search users
  → Ảnh hưởng RBAC: HR role chỉ xem employees trong dept mình phụ trách

Branches:
GET  /dashboard/branches              → danh sách chi nhánh
POST /dashboard/branches             → tạo (name, manager_id, địa chỉ)
```

### 11.3 JobTitle (Chức danh)

```
GET  /dashboard/job-titles           → danh sách chức danh
POST /dashboard/job-titles           → tạo:
  name, salary_min, salary_max, salary_currency
  domain_requirements (JSON): kỹ năng yêu cầu
  → Dùng cho Employee.current_title + Recruitment.expected_salary
```

### 11.4 Leave (Nghỉ phép)

```
Routes:
GET  /dashboard/leave/requests        → Danh sách đơn (filter by status: pending/approved/rejected)
GET  /dashboard/leave/requests/create → Tạo đơn nghỉ phép mới
POST /dashboard/leave/requests       → Nộp đơn
POST /dashboard/leave/requests/{id}/approve → HR/Manager duyệt
POST /dashboard/leave/requests/{id}/reject  → Từ chối
GET  /dashboard/leave/balances/me    → Số dư nghỉ phép của mình
GET  /dashboard/leave/policies       → Danh sách chính sách
POST /dashboard/leave/policies       → Tạo chính sách (HR only)

Luồng nghỉ phép:
  Nhân viên tạo đơn → status: pending
  HR/Manager nhận notification → Duyệt/Từ chối
  Nhân viên nhận notification về kết quả
  leave_balances cập nhật tự động
```

### 11.5 PerformanceReview (Đánh giá hiệu suất)

```
Routes:
GET  /dashboard/performance-reviews              → Danh sách đánh giá
GET  /dashboard/performance-reviews/create       → Tạo đánh giá mới
POST /dashboard/performance-reviews             → Lưu
GET  /dashboard/performance-reviews/{id}         → Chi tiết + kết quả
GET  /dashboard/review-templates                → Danh sách template
POST /dashboard/review-templates               → Tạo template

Luồng đánh giá:
  Manager tạo review cycle → chọn template + danh sách nhân viên
  Nhân viên self-review → điền form
  Manager review → cho điểm + nhận xét
  Kết quả → lưu vào performance_reviews table
  Tích hợp với KpiGoal để so sánh mục tiêu vs thực tế
```

### 11.6 KPI Goals

```
Routes:
GET  /dashboard/kpi/goals            → Danh sách mục tiêu
GET  /dashboard/kpi/goals/create     → Tạo mục tiêu
POST /dashboard/kpi/goals           → Lưu (metric_type, target_value, unit, period)
POST /dashboard/kpi/goals/{id}/snapshot → Ghi snapshot tiến độ
GET  /dashboard/kpi/leaderboard     → Bảng xếp hạng (@can viewLeaderboard)

KPI Cycle reports:
GET  /report/kpi/cycle              → Báo cáo theo chu kỳ
```

---

## 12. Module Năng lực số & Assessment

### 12.1 Assessment Engine

```
Routes assessments.*:
GET  /assessments                    → Danh sách assessments
GET  /assessments/create             → Tạo assessment mới (@can assessment.config)
POST /assessments                   → Lưu assessment
GET  /assessments/{id}              → Chi tiết + domains + score rules
GET  /assessments/{id}/edit         → Sửa config
POST /assessments/{id}/score-rules  → Cấu hình score rules
POST /assessments/{id}/domains      → Cấu hình domains + maturity levels
GET  /assessments/{id}/results      → Xem kết quả (@can assessment.results)
POST /assessments/{id}/reprocess    → Tính lại điểm (@can assessment.reprocess)
```

### 12.2 Survey Module

```
Survey (khảo sát — input cho Assessment):
GET  /dashboard/surveys              → Danh sách surveys
GET  /dashboard/surveys/create       → Tạo survey (sections, fields)
GET  /dashboard/surveys/my           → Surveys cá nhân (mọi user)
POST /dashboard/surveys/{id}/share  → Tạo share token + QR code
GET  /surveys/take/{token}          → Public URL điền survey (không cần đăng nhập)

Survey structure:
  Survey → Sections → Fields (question types: text, radio, checkbox, scale...)
  Survey → Token → Public URL → Response → Answer

Jobs:
  CalculateSurveyScoreJob  → tính điểm sau khi submit
  ExportSurveyResponsesJob → export kết quả
  SurveyWebhookJob         → gửi webhook ra ngoài khi có response mới
```

### 12.3 Năng lực số (Digital Workforce)

```
Hồ sơ Digital Twin:
GET  /dashboard/workforce/me         → Profile cá nhân (mọi user)
  - Skills profile
  - Assessment history
  - Certifications
  - AI Impact score

AI Sandbox:
GET  /dashboard/sandbox              → Trang sandbox cá nhân
  - Thực hành AI tasks
  - Sandbox sessions được ghi lại
  - Tự động chấm điểm

GET  /dashboard/sandbox-admin        → Admin quản lý sandbox (@can assessment.config)

Certifications:
GET  /dashboard/certifications       → Chứng nhận cá nhân
GET  /dashboard/certs-admin          → Admin quản lý cert definitions (@can assessment.config)

Career Pathway:
GET  /dashboard/career-pathway       → Lộ trình nghề nghiệp cá nhân
GET  /dashboard/career-pathway-admin → Admin quản lý pathways (@can assessment.config)

AI Impact Tracker:
GET  /dashboard/ai-impact            → Track tác động AI cá nhân
  ai_impact_snapshots table → snapshot hàng tháng

Career Journal (Passport):
GET  /passport                       → Nhật ký nghề nghiệp (mọi user)
  Ghi lại các milestone, achievements

Assessment Marketplace (Campaigns):
GET  /campaigns                      → Các open campaigns (public assessments)
  → User có thể tham gia nhiều campaigns
  → Kết quả ghi vào workforce profile
```

### 12.4 Identity Verification (trong Assessment)

```
Trước khi submit một số assessment cần xác minh danh tính:

POST /assessment/verify/otp/send
  → SendPhoneOtpJob (queue: high)
  → ZBS ZNS API → SMS đến user.phone_number

POST /assessment/verify/otp/confirm
  → Xác nhận OTP (TTL 5 phút)
  → Ghi identity_verifications record
  → identity_verified_at = now()
  → Cho phép nộp bài assessment
```

---

## 13. Module Tuyển dụng (ATS)

### 13.1 Job Posting (Tin tuyển dụng)

```
Routes backend.job-posts.*:
GET  /dashboard/job-posts                → Danh sách tin (@can viewAny JpJobPost)
GET  /dashboard/job-posts/create         → Tạo tin mới (@can create JpJobPost)
POST /dashboard/job-posts               → Lưu
GET  /dashboard/job-posts/{id}/edit     → Sửa
PUT  /dashboard/job-posts/{id}          → Cập nhật
DELETE /dashboard/job-posts/{id}       → Xóa (soft delete)
POST /dashboard/job-posts/{id}/publish  → Đăng tuyển (@can job_posting.publish)
  → status chuyển draft → published
  → Có thể sync lên Marketplace (jp_sync_status)

Dữ liệu tin tuyển dụng:
  title, description (Jodit rich text), benefits
  job_title_id (liên kết JobTitle)
  branch_id, department_id
  skills (jp_skill_masters)
  benefits (jp_benefit_masters)
  screening_questions → ứng viên phải trả lời khi apply

```

### 13.2 Recruitment ATS

```
Routes backend.recruitment.*:
GET  /dashboard/recruitment/candidates              → Danh sách ứng viên
GET  /dashboard/recruitment/candidates/create       → Thêm ứng viên thủ công
POST /dashboard/recruitment/candidates             → Lưu
GET  /dashboard/recruitment/candidates/{id}         → Profile ứng viên
POST /dashboard/recruitment/candidates/{id}/applications → Tạo application
GET  /dashboard/recruitment/interviews/my-schedule  → Lịch phỏng vấn của mình
POST /dashboard/recruitment/interviews             → Lên lịch phỏng vấn
GET  /dashboard/recruitment/analytics              → Analytics (@can recruitment.manage)
GET  /dashboard/recruitment/pipeline-stages        → Cấu hình stages (@can recruitment.manage)

Luồng tuyển dụng:
  1. HR publish Job Post → Candidate apply từ Marketplace
     HOẶC HR nhập ứng viên thủ công
  2. Tạo rc_applications record (candidate ↔ job_post)
  3. Di chuyển qua pipeline stages:
     Applied → Screening → Interview → Offer → Hired
  4. Lên lịch phỏng vấn → rc_interviews record
     → Notification cho interviewer
  5. Ghi đánh giá (rc_evaluation_criteria)
  6. Tạo offer (rc_offers: expected_salary, notice_period_days)
  7. Accepted → Tạo Employee record (onboarding)

Marketplace Applicants:
  Khi Job Post được sync lên Marketplace (mkt_listings)
  → Applicant apply từ public marketplace
  → mkt_applicants → rc_applications (link)
```

---

## 14. Module Quy trình SOP & Workflow

### 14.1 SOP (Standard Operating Procedures)

```
Routes backend.sop.*:
GET  /dashboard/sop                  → Danh sách SOP (filter by dept, category)
GET  /dashboard/sop/create           → Tạo SOP mới
POST /dashboard/sop                 → Lưu
GET  /dashboard/sop/{id}            → Chi tiết SOP (flowchart view)
GET  /dashboard/sop/{id}/edit       → Sửa
PUT  /dashboard/sop/{id}            → Cập nhật
POST /dashboard/sop/{id}/approve    → CEO phê duyệt (@can sop.approve)
POST /dashboard/sop/{id}/versions   → Tạo version mới
GET  /dashboard/sop/{id}/analytics  → Xem analytics (view count, completion rate)
GET  /dashboard/sop/{id}/export     → Export PDF/PNG (Spatie Browsershot)

SOP Structure:
  SOP → Steps (sop_steps):
    step_type: start | end | action | decision | document | delay
    step có: RACI (responsible, accountable, consulted, informed)
    step có: connectors (đường kẻ nối giữa các bước)
    step có: attachments

Approval workflow:
  OPS/HR tạo SOP → status: draft
  CEO nhận notification → Review
  CEO approve → status: approved
  Sop::active → user thấy trong sidebar
  
Versioning:
  Mỗi lần tạo version → sop_versions record
  Có thể rollback về version cũ
```

### 14.2 WorkflowAutomation

```
Routes workflows.*:
GET  /workflows                       → Danh sách workflows (@can workflow.monitor)
GET  /workflows/create                → Tạo workflow (@can workflow.edit)
POST /workflows                      → Lưu
GET  /workflows/{id}                  → Chi tiết + execution history
GET  /workflows/{id}/edit             → Sửa (@can workflow.edit)
PUT  /workflows/{id}                  → Cập nhật
DELETE /workflows/{id}               → Xóa (soft delete)
GET  /workflows/{id}/executions       → Lịch sử thực thi
GET  /workflows/{id}/executions/{eid} → Chi tiết một lần chạy

Workflow Engine:
  Trigger types:
    model_event: Lead::created, Lead::stage_changed, Task::completed...
    scheduled:   cron expression
    manual:      user trigger
    webhook:     external trigger

  Steps:
    group_id: nhóm các bước song song
    step_key: định danh duy nhất
    action_type: send_notification | create_task | update_field | call_webhook | ai_process
    conditions (JSON): điều kiện để bước chạy

  Execution:
    status: pending | running | completed | failed | partial
    steps_skipped: số bước bỏ qua do condition false
    steps_halted: số bước dừng do lỗi
    context (JSON): data truyền giữa các bước

  Queue: workflows → WorkflowExecutionJob
```

---

## 15. Module Dự án & Công việc

### 15.1 Task Management

```
Routes backend.tasks.*:
GET  /dashboard/tasks                 → Danh sách tasks (Tabulator table)
GET  /dashboard/tasks/create          → Tạo task
POST /dashboard/tasks                → Lưu
GET  /dashboard/tasks/{id}            → Chi tiết task
GET  /dashboard/tasks/{id}/edit      → Sửa
PUT  /dashboard/tasks/{id}           → Cập nhật
DELETE /dashboard/tasks/{id}        → Xóa
POST /dashboard/tasks/{id}/comments  → Ghi chú
POST /dashboard/tasks/{id}/watchers  → Thêm watcher
POST /dashboard/tasks/{id}/labels    → Gắn label
POST /dashboard/tasks/{id}/close     → Đánh dấu hoàn thành (@can tasks.close)

Task Structure:
  parent_id → cây nhiệm vụ nhiều cấp
  project_id → gắn vào Project
  status: todo | in_progress | review | done | cancelled
  priority: low | medium | high | urgent
  progress_pct: 0-100
  assigned_to: User (Tom Select)
  start_date, due_date: flatpickr
  
Jobs:
  UpdateTaskProgressJob   → cập nhật progress khi sub-task thay đổi
  RecalcTaskDepthJob      → tính lại độ sâu cây task

Dashboard chart:
  GET /api/dashboard/charts/task-throughput → ECharts data
```

### 15.2 Project Management

```
Routes backend.projects.*:
GET  /dashboard/projects              → Danh sách dự án
GET  /dashboard/projects/create       → Tạo dự án
POST /dashboard/projects             → Lưu
GET  /dashboard/projects/{id}         → Chi tiết (task list + progress)
GET  /dashboard/projects/{id}/edit   → Sửa
PUT  /dashboard/projects/{id}        → Cập nhật
POST /dashboard/projects/{id}/members → Thêm thành viên
POST /dashboard/projects/{id}/kpi-goals → Gắn KPI goals

Project ↔ Task:
  projects.task_total → tổng tasks
  projects.task_done  → tasks đã done
  projects.progress_pct → = task_done/task_total * 100
  → Tự động cập nhật khi task thay đổi via UpdateProjectProgressJob

Report:
  GET /report/project/index → Báo cáo dự án (gantt-like view)
```

---

## 16. Module Kho tri thức

### 16.1 KcCategory & KcItem

```
Routes (Knowledge Base):
GET  /dashboard/kc-categories              → Danh sách danh mục
POST /dashboard/kc-categories            → Tạo danh mục (name, description, parent_id)
GET  /dashboard/kc-items                  → Tất cả tài liệu
GET  /dashboard/kc-items?status=pending_review → Chờ duyệt
GET  /dashboard/kc-items/create           → Tạo tài liệu
POST /dashboard/kc-items                 → Lưu:
  title, content (Jodit), category_id
  visibility: public | internal | restricted
  tags: tom-select multi
  attachments: FilePond
GET  /dashboard/kc-items/{id}             → Xem tài liệu
GET  /dashboard/kc-items/{id}/edit       → Sửa
PUT  /dashboard/kc-items/{id}            → Cập nhật
POST /dashboard/kc-items/{id}/publish    → Publish
POST /dashboard/kc-items/{id}/access    → Cấu hình access control (kc_access_controls)
GET  /dashboard/kc-tags                  → Danh sách tags
GET  /dashboard/kc/analytics             → Analytics (view count, popular items)

View Count:
  Mỗi lần user xem KcItem → UpdateKcViewCountJob (queue: actlog)
  Batch update để tránh write contention

Access Control (kc_access_controls):
  item_id, scope_type: role | department | user
  scope_id → chỉ user/dept/role đó mới xem được
```

---

## 17. Module AI Copilot

### 17.1 Routes

```
GET  /ai/usage                        → ai.usage.index (Usage Dashboard)
GET  /ai/logs                         → ai.logs.index (Request Logs)
GET  /ai/agents                       → ai.agents.index (AI Agents list)
GET  /ai/agents/create                → Tạo AI Agent
POST /ai/agents                      → Lưu agent config
GET  /ai/agents/{id}/edit            → Sửa agent
PUT  /ai/agents/{id}                 → Cập nhật
GET  /ai/prompts                      → ai.prompts.index (Prompt Library)
GET  /ai/prompts/create               → Tạo prompt
POST /ai/prompts                     → Lưu
GET  /ai/prompts/{id}/edit           → Sửa
PUT  /ai/prompts/{id}                → Cập nhật

API endpoints:
POST /api/ai/copilot/tasks           → Tạo AI task (gửi đến queue: ai)
GET  /api/ai/copilot/tasks/{id}      → Polling kết quả
```

### 17.2 Luồng AI Task

```
User mô tả yêu cầu (text) trong AI Copilot interface
        │
        ▼
POST /api/ai/copilot/tasks
  ├── Kiểm tra @can ai_copilot.use
  ├── Lưu ai_requests record (status: pending)
  ├── dispatch(ProcessAiRequestJob) → queue: ai
  └── Trả về task_id cho frontend

ProcessAiRequestJob::handle():
  ├── TenantContext::set(org)
  ├── Load agent config + prompt template
  ├── Build messages array (system prompt + user message + context)
  ├── Call Anthropic Claude API:
  │     model: claude-sonnet-4-6
  │     max_tokens: config per agent
  ├── Parse response
  ├── Cập nhật ai_requests.output + status: completed
  ├── Ghi ai_monthly_usages (token count, cost)
  └── Broadcast event → frontend nhận kết quả real-time

Frontend polling / Echo:
  GET /api/ai/copilot/tasks/{id} → polling mỗi 2s
  HOẶC Echo channel → nhận kết quả ngay khi xong
```

### 17.3 AI Monthly Usage Tracking

```
ai_monthly_usages table:
  organization_id, user_id, model, month (YYYY-MM)
  input_tokens, output_tokens, total_tokens, estimated_cost_usd

ai_impact_snapshots table:
  user_id, month, tasks_completed, time_saved_hours, productivity_score
  → Cập nhật hàng tháng via scheduled job
```

---

## 18. Module Marketplace Center

### 18.1 Luồng hoàn chỉnh

```
[HR tạo Job Post] → Publish
        │
        ▼ jp_sync_status = out_of_sync
Marketplace sidebar badge hiển thị số cần sync

[HR click Marketplace Center]
  GET /dashboard/marketplace/listings → Danh sách listings
  POST /dashboard/marketplace/listings/{id}/sync → Sync từ Job Post
  
[Tạo Listing mới trực tiếp]
  GET /dashboard/marketplace/listings/create → Form
  POST /dashboard/marketplace/listings      → Lưu mkt_listings

[Duyệt tổ chức] (System Admin only)
  GET /dashboard/marketplace/org-approvals → Danh sách orgs chờ duyệt
  POST /dashboard/marketplace/org-approvals/{id}/approve
  
[Public Marketplace] (không cần đăng nhập)
  → Người tìm việc xem listings
  → Click Apply → Điền form → mkt_applicants
  → System link sang rc_applications nếu có ATS

[Analytics]
  GET /dashboard/marketplace/analytics → View count, apply count, conversion rate
  
[Reviews]
  mkt_reviews: rating, comment, from applicants
```

---

## 19. Module Báo cáo & KPI

### 19.1 Routes Report

```
GET /report                           → report.index (tổng quan)
GET /report/hr/headcount              → HR headcount (hasAnyPermission reports.hr/full)
GET /report/sales/pipeline            → Sales pipeline (hasAnyPermission reports.team/personal/full)
GET /report/project                   → Dự án (hasAnyPermission reports.ops/full)
GET /report/kpi/cycle                 → KPI cycle (hasAnyPermission reports.ops/full)

Dashboard Charts (API):
GET /api/dashboard/charts/task-throughput  → ECharts data JSON
GET /api/dashboard/charts/lead-funnel      → Funnel chart data
GET /api/dashboard/charts/workflow-health  → Workflow stats
GET /api/dashboard/charts/headcount        → Headcount trend
```

### 19.2 Phân tầng Report theo Role

```
reports.full    → CEO, System Admin: tất cả báo cáo
reports.personal → Sales: chỉ báo cáo cá nhân (leads của mình)
reports.team    → Sales Manager: cả team
reports.ops     → OPS: dự án, workflow, task throughput
reports.marketing → Marketing: campaign performance
reports.hr      → HR: headcount, turnover, recruitment funnel
reports.ai_usage → AI Operator: token usage, cost
reports.shared  → Viewer: shared/public reports only
```

---

## 20. Hệ thống Thông báo

### 20.1 Notification Center

```
Routes:
GET  /dashboard/notifications              → Trang notification center
PATCH /dashboard/notifications/{uuid}/read → Đánh dấu đã đọc
POST /dashboard/notifications/read-all   → Đánh dấu tất cả đã đọc

API (AJAX):
GET  /api/notifications                   → Danh sách (paginated)
GET  /api/notifications/unread-count      → Số chưa đọc (badge trên header)
PATCH /api/notifications/{uuid}/read     → Mark read
POST /api/notifications/read-all        → Mark all read
GET  /api/notifications/preferences      → Lấy notification preferences
PATCH /api/notifications/preferences/{eventType} → Bật/tắt channel
POST /api/notifications/push-subscribe  → Register Web Push subscription
DELETE /api/notifications/push-unsubscribe → Unregister
```

### 20.2 Notification Channels

```
notification_preferences table:
  user_id, event_type, channel (email|sms|push|in-app), enabled

User có thể tắt từng channel cho từng event type:
  lead.assigned    → email: on, push: on, sms: off
  task.assigned    → email: on, push: on
  leave.approved   → email: on, sms: on
  ...
```

### 20.3 Real-time (Laravel Reverb + Echo)

```javascript
// Trong app.js:
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    enabledTransports: ['ws', 'wss'],
});

// Private channel per user:
Echo.private(`App.Models.User.${userId}`)
    .notification((notification) => {
        // Cập nhật badge đếm
        // Thêm vào dropdown notification
        // Toast notification
    });
```

### 20.4 Web Push (VAPID)

```
POST /api/notifications/push-subscribe:
  Body: { endpoint, keys: { auth, p256dh } }
  → Lưu vào push_subscriptions table
  
Server-side:
  Notification::send($user, new SomeNotification())
  → Driver WebPush → gửi đến push_subscriptions của user
  → Browser hiển thị OS-level notification (kể cả khi tab không mở)
```

---

## 21. Hệ thống Queue & Job nền

### 21.1 Job Registry theo Module

| Module | Job | Queue |
|--------|-----|-------|
| Assessment | RunAssessmentJob, SnapshotPassportEntryJob | passport |
| Assessment | SendPhoneOtpJob | high |
| Assessment | CreateCampaignPassportEntryJob | passport |
| Assessment | FlagInactiveMembersJob (scheduled) | default |
| Assessment | AutoSuspendExpiredMembershipsJob (scheduled) | default |
| Survey | CalculateSurveyScoreJob | default |
| Survey | ExportSurveyResponsesJob | low |
| Survey | SurveyWebhookJob | webhooks |
| Survey | PurgeDeletedResponsesJob (scheduled) | low |
| Task | UpdateProjectProgressJob | default |
| Task | UpdateTaskProgressJob | default |
| Task | RecalcTaskDepthJob | default |
| SOP | ExportSopFlowchartJob | low |
| AiCopilot | ProcessAiRequestJob | ai |
| KcItem | UpdateKcViewCountJob (batch) | actlog |
| Workflow | WorkflowExecutionJob | workflows |
| Workflow | WorkflowStepJob | workflows |
| Notifications | SendNotificationJob | default |
| Notifications | WebPushJob | high |

### 21.2 Horizon Dashboard

```
URL: /horizon (chỉ whitelist IP hoặc super-admin)

Metrics:
  - Jobs/min (throughput)
  - Failed jobs
  - Queue depth
  - Memory usage per worker
  - Runtime average

Cấu hình: config/horizon.php
  supervisors:
    - queue: high (2 workers)
    - queue: default,workflows,ai (4 workers)
    - queue: low,actlog,webhooks (2 workers)
    - queue: passport (2 workers)
```

### 21.3 Failed Jobs & Retry

```bash
# Xem failed jobs
php artisan queue:failed

# Retry tất cả
php artisan queue:retry all

# Retry specific job
php artisan queue:retry {id}

# Xóa failed jobs cũ
php artisan queue:flush

# After deploy — restart workers
php artisan queue:restart
```

---

## 22. Media & File Upload

### 22.1 FilePond Upload (Forms)

```
Khi user chọn file trong form:
  AJAX ngay lập tức: POST /api/v1/media/upload
    Headers: X-CSRF-TOKEN
    Body: multipart/form-data với file
    → Validate: mime type, max size
    → Lưu vào storage/temp/{uuid}
    ← Trả về: { uuid: "..." }
    
Form submit: gửi uuid trong payload
  Controller nhận uuid → MediaService::attachFromTemp(uuid, $model)
  → Move file từ temp → permanent storage/{collection}/
  → Tạo media record (spatie_media table):
      uuid, name, file_name, mime_type, size
      organization_id (tenant isolation)
      uploaded_at, last_touched_at

Cleanup orphans:
  DELETE /api/v1/media/upload/{uuid} → khi user cancel form
  Scheduled job → purge temp files > 24h tuổi
```

### 22.2 Jodit Rich Text (Content)

```
Editor Jodit được load cho KcItem.content, SOP description, v.v.

Khi paste/upload ảnh trong Jodit:
  POST /api/v1/media/jodit-upload
  ← { url: "/storage/..." }
  
  DELETE /api/v1/media/jodit-upload/{uuid}
  → Orphan cleanup (nếu user xóa ảnh trong editor)

Orphan cleanup job: chạy scheduled, purge jodit uploads không có reference
```

### 22.3 Storage Config

```env
# Development
FILESYSTEM_DISK=public
# Files: storage/app/public/{collection}/{file}
# URL: /storage/{collection}/{file}

# Production (S3)
FILESYSTEM_DISK=s3
AWS_BUCKET=minhan-prod-media
# Files: s3://minhan-prod-media/{org_id}/{collection}/{file}
# URL: https://minhan-prod-media.s3.{region}.amazonaws.com/{path}
```

---

## 23. Audit Log & Giám sát

### 23.1 Automatic Model Logging (Spatie ActivityLog)

```php
// TenantAwareModel có UseLogActivity → log tự động:
class Lead extends TenantAwareModel {
    // LogOptions:
    // logFillable() — log tất cả fillable fields
    // logOnlyDirty() — chỉ log fields thực sự thay đổi
    // dontLogEmptyChanges() — bỏ qua nếu không có thay đổi
}

// activity_log table:
{
  "log_name": "lead",
  "description": "updated",
  "subject_id": 42, "subject_type": "Modules\\Lead\\Models\\Lead",
  "causer_id": 7, "causer_type": "App\\Models\\User",
  "properties": {
    "old": { "status": "open", "stage_id": 1 },
    "attributes": { "status": "won", "stage_id": 4 }
  },
  "organization_id": 1,
  "module": "lead"
}
```

### 23.2 HTTP Request Context

```php
// CaptureHttpContext Middleware:
// → Ghi vào activity_log_contexts mỗi request:
{
  "request_id": "uuid",
  "method": "PUT",
  "url": "/leads/42",
  "ip": "192.168.1.1",
  "user_agent": "Mozilla/5.0...",
  "body": { "status": "won" }  // sanitized (no passwords)
}

// activity_log_http:
{
  "status_code": 200,
  "duration_ms": 145
}
```

### 23.3 Alert Rules

```
activity_log_alert_rules table:
  rule_name: "Failed Login Brute Force"
  log_name: "auth"
  description_pattern: "login_failed"
  count_threshold: 5
  time_window_minutes: 10
  notification_channel: email
  → Gửi notification cho system_admin
```

### 23.4 Xem Logs trong UI

```
GET /activitylog                     → activitylog.index
  Filter: log_name, causer (user), subject_type, date range, level
  Pagination (Tabulator)
  Chi tiết: xem properties diff (old vs new)
  Link sang HTTP context (request body, duration)
```

---

## 24. Subscription & Billing

### 24.1 Plan Structure

```
plans table:
  name: "Starter" | "Pro" | "Enterprise"
  tier: 1 | 2 | 3
  badge_color, tag_line

subscriptions table:
  subscriber_id, subscriber_type → Organization (morphable)
  plan_id → Plan
  status: active | trial | expired | cancelled
  starts_at, ends_at

subscription_invoices table:
  subscription_id, gateway (manual | stripe | ...)
  invoice_type: initial | renewal | upgrade
  new_plan_id → khi upgrade
  paid_at
```

### 24.2 Feature Gating

```php
// CheckSubscription Middleware:
// Mọi request → kiểm tra:
//   org có subscription status = active không?
//   Nếu không → redirect /subscription/portal/plans

// organization_feature_overrides:
//   Override bật/tắt feature cụ thể cho 1 org (beta, deal, trial)
//   organization_id, feature_key, enabled (bool)
```

### 24.3 Billing Routes

```
Portal (cho org owner/admin):
GET  /subscription/portal/billing     → Xem subscription hiện tại
GET  /subscription/portal/plans       → Xem & chọn gói
GET  /subscription/portal/invoices    → Lịch sử hóa đơn (@can subscription.billing)

Admin (System Admin only):
GET  /subscription/admin/plans        → CRUD plans
GET  /subscription/admin/subscriptions → Quản lý subscriptions của tất cả orgs
GET  /subscription/admin/invoices     → Tất cả invoices
POST /subscription/admin/subscriptions/{id}/extend → Gia hạn
```

---

## 25. Tích hợp Bên ngoài

### 25.1 Anthropic Claude API

```env
ANTHROPIC_API_KEY=sk-ant-api03-...

# Usage trong ProcessAiRequestJob:
$response = $client->messages()->create([
    'model' => 'claude-sonnet-4-6',
    'max_tokens' => 2048,
    'system' => $systemPrompt,
    'messages' => [
        ['role' => 'user', 'content' => $userMessage]
    ],
]);
```

### 25.2 ZBS Zalo ZNS (SMS OTP)

```
Cấu hình:
  OTP_CHANNEL_DRIVER=zbs_zns
  ZBS_CLIENT_ID=...
  ZBS_CLIENT_SECRET=...

OAuth setup (super-admin):
  GET  /dashboard/integrations/zbs        → Xem trạng thái
  POST /dashboard/integrations/zbs/test   → Gửi test SMS

Token lưu: zbs_oauth_tokens table
  access_token, refresh_token, expires_at

SendPhoneOtpJob → ZBS API:
  POST https://api.zalo.ai/v2/oa/message/cs
  Template: OTP code {code}
  TTL: 5 phút
```

### 25.3 Google OAuth (Socialite)

```env
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
# Callback: APP_URL + /auth/social/google/callback
```

### 25.4 Resend (Email)

```env
RESEND_KEY=re_...
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=noreply@minhan.vn
MAIL_FROM_NAME="MinHan Platform"

# Email classes:
# Fortify's default: VerifyEmail, ResetPassword
# Custom: LeadAssignedNotification, TaskAssignedNotification, ...
```

### 25.5 Cloudflare Turnstile

```env
TURNSTILE_SITE_KEY=...
TURNSTILE_SECRET_KEY=...

# Áp dụng trên: /login, /register, /forgot-password
# Middleware VerifyTurnstile: gọi /siteverify API
# Nếu fail → redirect back with error "captcha"
```

### 25.6 Web Push VAPID

```bash
# Generate keys (lần đầu)
php artisan webpush:vapid
```

```env
VAPID_PUBLIC_KEY=BNxj...   # Gắn vào <meta name="vapid-public-key"> trong layout
VAPID_PRIVATE_KEY=...
```

---

## 26. Vận hành Hàng ngày

### 26.1 Checklist Buổi sáng

```bash
# 1. Queue workers đang chạy?
php artisan horizon:status
# hoặc
ps aux | grep "queue:work" | grep -v grep

# 2. Failed jobs (cần xử lý ngay nếu có)
php artisan queue:failed
# → Nếu có: php artisan queue:retry all

# 3. Log errors
tail -100 storage/logs/laravel.log | grep -E "ERROR|CRITICAL"

# 4. Reverb WebSocket
# Browser: /dashboard → notification bell → click
# → Nếu không real-time → check reverb process
```

### 26.2 Thêm User Mới vào Tổ chức

```
System Admin → /dashboard/users → Thêm tài khoản
  Điền: name, email, role (chọn từ 8 roles)
  → Tạo User + gán role + gán organization_id
  → Gửi email set password
  
HOẶC:
  User tự đăng ký → Admin thêm vào org:
  /dashboard/organizations/{id}/members → Invite
  → Gửi invitation email → User accept → OrganizationMember.status = active
```

### 26.3 Tạo Tổ chức Mới (Admin)

```
GET /dashboard/organizations/create
  name, slug (auto-generated, có thể tùy chỉnh)
  email, email_domain (dùng cho SSO domain matching)
  source: marketing | direct | referral
  
POST /dashboard/organizations
  → Tạo org
  → Tạo owner user
  → Kích hoạt verticals mặc định
  → Seed: default pipeline stages, lead sources
  → Redirect → org detail page
  
Kích hoạt Verticals:
  GET /dashboard/organizations/{id}/verticals
  Toggle từng vertical on/off
  → Ảnh hưởng đến middleware 'vertical:{code}'
```

### 26.4 Sync Permissions sau thay đổi Config

```bash
# 1. Chỉnh sửa config/permissions.php
# 2. Sync vào database
php artisan permissions:sync
# → Tạo permissions mới nếu chưa có
# → Update mapping role → permission
# → KHÔNG xóa permissions cũ (an toàn)

# 3. Clear Spatie permission cache
php artisan permission:cache-reset

# 4. (Optional) Rebuild toàn bộ
php artisan optimize:clear && php artisan optimize
```

### 26.5 Deploy mới (zero-downtime)

```bash
# 1. Pull code
git pull origin main

# 2. Dependencies
composer install --no-dev --optimize-autoloader

# 3. Run migrations (không breaking)
php artisan migrate --force

# 4. Sync permissions nếu có thay đổi
php artisan permissions:sync

# 5. Build frontend
npm install && npx vite build --config vite.config.backend.js

# 6. Restart queue workers (workers sẽ reload code mới)
php artisan queue:restart

# 7. Clear + rebuild caches
php artisan optimize:clear && php artisan optimize

# 8. Reload PHP-FPM
sudo systemctl reload php8.4-fpm
```

### 26.6 Backup

```bash
# SQLite (dev)
cp database/database.sqlite backups/db-$(date +%Y%m%d-%H%M).sqlite

# MySQL (prod)
mysqldump -u {user} -p {db} | gzip > backups/db-$(date +%Y%m%d).sql.gz

# S3 Media backup
aws s3 sync s3://minhan-prod-media/ backups/s3/

# Cronjob tự động (daily):
# 0 2 * * * /var/www/html/minhan/scripts/backup.sh
```

---

## 27. Troubleshooting Thực tế

### 27.1 "No tenant context" / TenantNotSetException

```
Triệu chứng: 500 error "Tenant context has not been set"

Nguyên nhân thường gặp:
  A. Route thiếu middleware 'auth' → user chưa đăng nhập → org không resolve
  B. User.organization_id = null (user chưa thuộc org nào)
  C. Subdomain sai → org không tồn tại
  D. Org status = suspended → IdentifyOrganization trả về null

Debug:
  php artisan tinker
  >>> $user = User::find(1);
  >>> $user->organization_id;  // phải có giá trị
  >>> Organization::find($user->organization_id)->status;  // phải = 'active'

Fix:
  A. Thêm 'auth' vào route middleware group
  B. Gán organization_id cho user
  C. Kiểm tra .env APP_URL + subdomain DNS
  D. Active lại org: Organization::find(id)->update(['status' => 'active'])
```

### 27.2 Permission denied bất thường

```bash
# Kiểm tra user có permission gì
php artisan tinker
>>> $user = User::find(1);
>>> $user->getRoleNames();
>>> $user->getAllPermissions()->pluck('name')->sort()->values();

# Sync lại permissions
php artisan permissions:sync
php artisan permission:cache-reset

# Kiểm tra config đúng chưa
php artisan tinker
>>> config('permissions')['ceo'];  // xem list permission của CEO
```

### 27.3 Queue không xử lý

```bash
# Kiểm tra Redis
redis-cli ping  # → PONG

# Kiểm tra queue size
php artisan queue:size
php artisan queue:size ai
php artisan queue:size high

# Xem pending jobs
redis-cli llen laravel_database_queues:default

# Khởi động lại worker
php artisan queue:restart
# Chờ 10s rồi start lại supervisor hoặc chạy thủ công:
php artisan queue:work redis --queue=high,default,low,workflows,webhooks,ai,actlog,passport

# Xem failed jobs
php artisan queue:failed
# Retry
php artisan queue:retry all
```

### 27.4 Frontend không cập nhật sau deploy

```bash
# Rebuild Vite
npm run build
# hoặc
npx vite build --config vite.config.backend.js

# Clear view cache (quan trọng khi thay đổi layouts)
php artisan view:clear

# Hard reload browser: Ctrl+Shift+R
# Kiểm tra build hash: public/build/backend/manifest.json
cat public/build/backend/manifest.json | head -20
```

### 27.5 Email không gửi

```bash
# Test mail
php artisan tinker
>>> Mail::raw('Test email', function($m) {
...   $m->to('test@example.com')->subject('Test');
... });

# Kiểm tra .env
MAIL_MAILER=resend
RESEND_KEY=re_...

# Xem queue mail jobs
php artisan queue:size default

# Nếu dùng log driver (dev):
MAIL_MAILER=log
# → Emails ghi vào storage/logs/laravel.log
```

### 27.6 WebSocket / Real-time không hoạt động

```bash
# Kiểm tra Reverb đang chạy
php artisan reverb:start --debug

# Kiểm tra .env
BROADCAST_CONNECTION=reverb
REVERB_APP_KEY=...
VITE_REVERB_APP_KEY=${REVERB_APP_KEY}
VITE_REVERB_HOST=${REVERB_HOST}
VITE_REVERB_PORT=${REVERB_PORT}

# Browser Console:
# → Network tab → WS connections
# → Console: "Echo connected" log

# Firewall: mở port 8080 (hoặc config port khác)
sudo ufw allow 8080/tcp

# Nếu production dùng nginx proxy:
# location /app { proxy_pass http://localhost:8080; upgrade websocket; }
```

### 27.7 Upload file thất bại

```bash
# Kiểm tra symlink
ls -la public/storage
# → phải là symlink đến ../../storage/app/public

# Tạo lại nếu thiếu
php artisan storage:link

# Quyền thư mục
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage

# PHP upload limits (/etc/php/8.4/fpm/php.ini)
upload_max_filesize = 50M
post_max_size = 55M
memory_limit = 256M

# Reload PHP-FPM sau khi đổi config
sudo systemctl reload php8.4-fpm
```

### 27.8 Marketplace badge sync count sai

```bash
# Badge đếm số tin out_of_sync được cache 60s
# Clear cache để làm mới ngay
php artisan tinker
>>> Cache::forget('mkt:org:' . $orgId . ':sync-count');

# Hoặc clear tất cả
php artisan cache:clear
```

---

## 28. Phụ lục: Artisan & Env

### 28.1 Artisan Commands quan trọng

```bash
# ===== CORE =====
php artisan key:generate          # Generate APP_KEY
php artisan storage:link           # Symlink storage
php artisan optimize               # Cache config + routes + views
php artisan optimize:clear         # Clear tất cả caches

# ===== DATABASE =====
php artisan migrate                # Chạy migrations mới
php artisan migrate:status         # Xem trạng thái migrations
php artisan migrate:fresh --seed   # Reset toàn bộ + seed (dev only!)
php artisan migrate:rollback       # Rollback 1 batch
php artisan db:seed                # Chạy DatabaseSeeder → SystemDataSeeder
php artisan permissions:sync       # Đồng bộ config/permissions.php → DB
php artisan permission:cache-reset # Clear Spatie permission cache

# ===== MODULES =====
php artisan module:make {Name}     # Tạo module mới
php artisan module:list            # Danh sách modules + trạng thái
php artisan module:migrate {Name}  # Migrate chỉ 1 module
php artisan module:seed {Name}     # Seed chỉ 1 module

# ===== QUEUE =====
php artisan queue:work             # Start worker (foreground)
php artisan queue:listen           # Start + reload khi có code change (dev)
php artisan queue:size             # Số jobs pending
php artisan queue:size {queue}     # Theo queue name
php artisan queue:failed           # Danh sách failed jobs
php artisan queue:retry all        # Retry tất cả failed
php artisan queue:retry {id}       # Retry 1 job
php artisan queue:flush            # Xóa tất cả failed jobs
php artisan queue:restart          # Graceful restart workers

# ===== REAL-TIME =====
php artisan reverb:start           # WebSocket server
php artisan reverb:start --debug   # Debug mode

# ===== MONITORING =====
php artisan horizon                # Horizon dashboard worker
php artisan horizon:status         # Trạng thái Horizon
php artisan horizon:pause          # Tạm dừng
php artisan horizon:continue       # Tiếp tục

# ===== MEDIA =====
php artisan webpush:vapid          # Generate VAPID keys cho Web Push
php artisan media:clean            # Dọn orphan media files

# ===== DEBUG =====
php artisan tinker                 # REPL
php artisan route:list             # Danh sách routes
php artisan route:list --name=lead # Filter theo tên
php artisan config:show            # Xem config đã load
php artisan pail                   # Real-time log streaming
```

### 28.2 Environment Variables quan trọng

```env
# ===== APPLICATION =====
APP_NAME=MinHan
APP_ENV=production          # local | production
APP_DEBUG=false             # true chỉ cho dev
APP_KEY=base64:...          # php artisan key:generate
APP_URL=https://app.minhan.vn
APP_FRONTEND_URL=https://app.minhan.vn

# ===== DATABASE =====
DB_CONNECTION=mysql         # sqlite | mysql | pgsql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=minhan
DB_USERNAME=minhan
DB_PASSWORD=...

# ===== REDIS =====
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
QUEUE_CONNECTION=redis      # sync (dev) | redis (prod)
SESSION_DRIVER=redis        # file (dev) | redis (prod)
CACHE_DRIVER=redis          # file (dev) | redis (prod)

# ===== MAIL (Resend) =====
MAIL_MAILER=resend          # log (dev) | resend (prod)
RESEND_KEY=re_...
MAIL_FROM_ADDRESS=noreply@minhan.vn
MAIL_FROM_NAME="MinHan"

# ===== WEBSOCKET (Reverb) =====
BROADCAST_CONNECTION=reverb # log (dev) | reverb (prod)
REVERB_APP_ID=minhan
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=https
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"

# ===== AI =====
ANTHROPIC_API_KEY=sk-ant-...
OPENAI_API_KEY=sk-...       # fallback

# ===== ZBS ZALO ZNS (SMS OTP) =====
OTP_CHANNEL_DRIVER=zbs_zns
ZBS_CLIENT_ID=...
ZBS_CLIENT_SECRET=...
ZBS_BASE_URL=https://business.openapi.zalo.me

# ===== STORAGE =====
FILESYSTEM_DISK=public      # local dev
# production:
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=minhan-prod-media
AWS_URL=https://minhan-prod-media.s3.amazonaws.com

# ===== OAUTH =====
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GITHUB_CLIENT_ID=...
GITHUB_CLIENT_SECRET=...

# ===== WEB PUSH =====
VAPID_PUBLIC_KEY=...        # php artisan webpush:vapid
VAPID_PRIVATE_KEY=...
VITE_VAPID_PUBLIC_KEY="${VAPID_PUBLIC_KEY}"

# ===== CAPTCHA =====
TURNSTILE_SITE_KEY=...
TURNSTILE_SECRET_KEY=...

# ===== HORIZON =====
HORIZON_DARK_MODE=true
HORIZON_WHITELIST=127.0.0.1,::1

# ===== SCOUT (search, nếu dùng) =====
SCOUT_DRIVER=null
```

### 28.3 Ma trận Routes — Tổng hợp Nhanh

| URL Prefix | Route Names | Module |
|-----------|-------------|--------|
| `/` | backend.dashboard | DashboardController |
| `/auth/*` | auth.* | Auth module |
| `/login`, `/register` | — | Fortify |
| `/leads/*` | lead.* | Lead module |
| `/customers/*` | customer.* | Customer module |
| `/lead-pipeline-stages/*` | lead-pipeline-stage.* | LeadPipelineStage |
| `/lead-sources/*` | lead-source.* | LeadSource |
| `/dashboard/organizations/*` | backend.organizations.* | Organization |
| `/dashboard/branches/*` | backend.branches.* | Branch |
| `/dashboard/departments/*` | backend.departments.* | Department |
| `/dashboard/job-titles/*` | backend.job-titles.* | JobTitle |
| `/dashboard/employees/*` | backend.employees.* | Employee |
| `/dashboard/leave/*` | backend.leave.* | Leave |
| `/dashboard/kpi/*` | backend.kpi.* | KpiGoal |
| `/dashboard/performance-reviews/*` | backend.performance-reviews.* | PerformanceReview |
| `/dashboard/projects/*` | backend.projects.* | Project |
| `/dashboard/tasks/*` | backend.tasks.* | Task |
| `/dashboard/org-charts/*` | backend.org-charts.* | OrgChart |
| `/dashboard/job-posts/*` | backend.job-posts.* | JobPosting |
| `/dashboard/marketplace/*` | backend.marketplace.* | Marketplace |
| `/dashboard/recruitment/*` | backend.recruitment.* | Recruitment |
| `/dashboard/role-scopes/*` | backend.role-scopes.* | RoleScope |
| `/dashboard/kc-categories/*` | backend.kc-categories.* | KcCategory |
| `/dashboard/kc-items/*` | backend.kc-items.* | KcItem |
| `/dashboard/workforce/*` | backend.workforce.* | Assessment |
| `/dashboard/sandbox/*` | backend.sandbox.* | Assessment |
| `/dashboard/certifications/*` | backend.certifications.* | Assessment |
| `/dashboard/career-pathway/*` | backend.career-pathway.* | Assessment |
| `/dashboard/ai-impact/*` | backend.ai-impact.* | Assessment |
| `/passport/*` | passport.* | Assessment |
| `/campaigns/*` | campaigns.* | Assessment |
| `/assessments/*` | assessments.* | Assessment |
| `/dashboard/sop/*` | backend.sop.* | Sop |
| `/ai/*` | ai.* | AiCopilot |
| `/workflows/*` | workflows.* | WorkflowAutomation |
| `/report/*` | report.* | Report |
| `/activitylog/*` | activitylog.* | ActivityLog |
| `/subscription/*` | subscription.* | Subscription |
| `/deployment/*` | deployment.* | Deployment |
| `/dashboard/users/*` | backend.users.* | User |
| `/dashboard/notifications/*` | backend.notifications.* | core routes |
| `/api/v1/*` | — | API routes (Sanctum) |

---

*Tài liệu này được tổng hợp từ scan thực tế toàn bộ source code ngày 2026-06-25.*
*Cập nhật khi có thay đổi kiến trúc, thêm module, hoặc thay đổi permission matrix.*
