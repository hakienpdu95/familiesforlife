# Notification System — Đặc tả triển khai

> **Version:** 1.0 — 2026-06-10  
> **Stack:** Laravel 13 · Alpine.js 3 · Vite 8  
> **Scope:** In-app (bell dropdown + notification center) + Browser Push  
> **Không trong scope:** Email notification (đã có sẵn), WebSocket real-time (phase sau)

---

## Mục lục

1. [Trạng thái hiện tại](#1-trạng-thái-hiện-tại)
2. [Kiến trúc tổng thể](#2-kiến-trúc-tổng-thể)
3. [Database — thay đổi cần thiết](#3-database)
4. [Data schema chuẩn hóa](#4-data-schema-chuẩn-hóa)
5. [API Endpoints](#5-api-endpoints)
6. [Frontend — Bell Dropdown](#6-frontend-bell-dropdown)
7. [Notification Center Page](#7-notification-center-page)
8. [Browser Push Notifications](#8-browser-push-notifications)
9. [Catalog thông báo theo module](#9-catalog-thông-báo-theo-module)
10. [Refactor các notification class hiện có](#10-refactor-notification-classes-hiện-có)
11. [Lộ trình triển khai](#11-lộ-trình-triển-khai)
12. [Checklist trước khi merge](#12-checklist)

---

## 1. Trạng thái hiện tại

### 1.1 Đã có

| Thành phần | Trạng thái |
|---|---|
| Bảng `notifications` (migration) | ✅ Đã có — schema gần chuẩn Laravel |
| `User` model dùng `Notifiable` trait | ✅ |
| 11 notification class dùng `database` channel | ✅ SOP (5), KcItem (4), JobPosting (1), WorkflowAutomation (1) |
| Queue database (async) + ShouldQueue | ✅ |
| Bell icon trong header | ✅ UI shell đã có — **data hardcode** |
| Scheduled commands gửi notification | ✅ sop, jp, kc, subscription |

### 1.2 Thiếu / Cần xây

| Thiếu | Ghi chú |
|---|---|
| **API endpoints** để fetch/đọc notification | Chưa có — bell icon hiện show mockup |
| **Bell dropdown động** | Cần AJAX + Alpine state |
| **Notification center page** | `/dashboard/notifications` — chưa tồn tại |
| **Data schema nhất quán** | Mỗi class dùng key khác nhau (`message` vs `body`, không có `type` ở KcItem...) |
| **`organization_id`** trên bảng notifications | Multi-tenant gap |
| **Browser Push** | Chưa có |
| **Mark as read / dismiss** | Chưa có UI |
| **Unread count badge** thật | Hiện là static `.badge-dot` |
| `routes/api.php` chưa tồn tại | File không có — 5 endpoints cần tạo mới hoàn toàn |
| `admin-shell.js` là vanilla JS thuần | `notifBell()` Alpine component phải đăng ký trong `app.js` (`alpine:init` block), không phải trong `admin-shell.js` |
| `WorkflowNotification` dùng `toArray()` thay `toDatabase()` | Laravel fallback vẫn ghi DB nhưng payload thiếu `url`, `icon`, `severity` |
| `RenewalReminderNotification` chỉ có `mail` channel | Subscription sắp hết hạn không rung chuông trong app |
| `kc_submitted` notification chưa có | Approver KC không biết có tài liệu chờ duyệt (chỉ có approved/rejected) |
| Thông báo cho 20+ module còn lại | Lead, Customer, Task, Project, Employee, Leave... |

### 1.3 28 Modules hiện có

```
ActivityLog, Assessment, Auth, Branch, Customer, Department, Employee,
JobPosting*, JobTitle, KcCategory, KcItem*, KpiGoal, Lead, LeadPipelineStage,
LeadSource, Leave, Marketplace, OrgChart, Organization, PerformanceReview,
Project, Recruitment, RoleScope, Sop*, Subscription, Survey, Task, User,
WorkflowAutomation*
```
`*` = đã có notification class

---

## 2. Kiến trúc tổng thể

```
┌─────────────────────────────────────────────────────────────────┐
│  Modules/[Name]/app/Notifications/XyzNotification.php           │
│  → toDatabase() dùng NotificationData::make(type, title, ...) │
│  → via() = ['database']  hoặc  ['database','mail']             │
└──────────────────────────────┬──────────────────────────────────┘
                               │ dispatch to queue
                               ▼
                    ┌──────────────────┐
                    │  notifications   │  (database table)
                    │  table           │
                    └────────┬─────────┘
                             │
              ┌──────────────┴──────────────────┐
              │                                 │
              ▼                                 ▼
   GET /api/notifications            GET /api/notifications/unread-count
   POST /api/notifications/read-all
   PATCH /api/notifications/{uuid}/read
   DELETE /api/notifications/{uuid}
              │
              ▼
   ┌─────────────────────────────┐
   │  Bell Dropdown (polling 30s) │   Alpine x-data, topbar header
   │  • Unread badge count        │
   │  • 6 most recent items       │
   │  • "Xem tất cả" → /notifs   │
   └─────────────────────────────┘
              +
   ┌─────────────────────────────┐
   │  Notification Center         │   /dashboard/notifications
   │  • Paginated full list       │
   │  • Filter: all/unread/type   │
   │  • Bulk mark read / dismiss  │
   └─────────────────────────────┘
              +
   ┌─────────────────────────────┐
   │  Browser Push (Phase 2)      │   Service Worker + Push API
   │  • User subscribes once      │
   │  • Server sends via queue    │
   └─────────────────────────────┘
```

**Nguyên tắc kiến trúc:**
- Notification class vẫn **nằm trong từng module** (domain ownership)
- Shared helper `app/Shared/Notifications/NotificationData.php` chuẩn hóa payload
- API tập trung tại `app/Http/Controllers/Api/NotificationController.php`
- Frontend: polling mỗi 30s (không cần WebSocket cho phase 1)

---

## 3. Database

### 3.1 Thay đổi cần thiết

Bảng `notifications` hiện thiếu `organization_id` cho multi-tenancy. Cần thêm 1 migration:

```php
// database/migrations/2026_xx_xx_add_org_to_notifications_table.php
Schema::table('notifications', function (Blueprint $table) {
    $table->unsignedBigInteger('organization_id')->nullable()->after('id');
    $table->index(['organization_id', 'notifiable_id', 'read_at']);
    $table->index('created_at');  // pagination ORDER BY
});
```

**Lý do `nullable`:** các notification hệ thống (SubscriptionExpired) không thuộc org cụ thể.

### 3.2 Schema bảng sau migration

```
notifications
├── id              bigint PK auto-increment
├── uuid            varchar(36) unique nullable      -- expose ra API, không dùng id
├── organization_id bigint nullable FK organizations  -- tenant scope
├── order_column    int nullable
├── type            varchar(255)                     -- FQCN notification class
├── notifiable_type varchar(255)                     -- 'App\Models\User'
├── notifiable_id   bigint
├── data            text (JSON)                      -- payload chuẩn hóa (Section 4)
├── read_at         timestamp nullable
├── created_at
└── updated_at

Index: [notifiable_type, notifiable_id]
Index: [organization_id, notifiable_id, read_at]
Index: [created_at]
```

### 3.3 User preferences (Phase 2)

```sql
-- notification_preferences
CREATE TABLE notification_preferences (
    id              bigint PK,
    user_id         bigint FK users,
    organization_id bigint FK organizations,
    event_type      varchar(100),    -- 'sop_submitted', 'task_assigned', ...
    channel_db      tinyint(1) default 1,
    channel_mail    tinyint(1) default 1,
    channel_push    tinyint(1) default 0,
    created_at, updated_at
);
```

---

## 4. Data Schema chuẩn hóa

### 4.1 Vấn đề hiện tại

Các class hiện dùng key khác nhau, không nhất quán:

```php
// SOP — có 'type', 'message', 'url' nhưng không có 'title'
['type' => 'sop_submitted', 'message' => '...', 'url' => '...', 'sop_id' => 1]

// KcItem — không có 'type', dùng 'message'
['item_id' => 1, 'title' => '...', 'url' => '...', 'message' => '...']

// WorkflowAutomation — dùng 'title', 'body', 'type'
['type' => 'workflow', 'title' => '...', 'body' => '...']
```

### 4.2 Schema chuẩn — bắt buộc từ giờ

```php
// app/Shared/Notifications/NotificationData.php

class NotificationData
{
    public static function make(
        string $type,        // snake_case, unique toàn hệ thống. VD: 'sop_submitted'
        string $title,       // Tiêu đề ngắn ≤ 80 ký tự — hiện trong bell dropdown
        string $body,        // Nội dung đầy đủ — hiện trong notification center
        string $url  = '',   // URL điều hướng khi click (có thể rỗng)
        string $icon = 'bell', // key icon: bell|check|warning|error|info|user|task|sop|kc|lead
        string $severity = 'info', // info|success|warning|error
        array  $meta = [],   // data context: IDs, slugs — không render UI, dùng cho filter/debug
    ): array {
        return compact('type', 'title', 'body', 'url', 'icon', 'severity', 'meta');
    }
}
```

**Ví dụ dùng trong notification class:**

```php
public function toDatabase(object $notifiable): array
{
    return NotificationData::make(
        type:     'sop_submitted',
        title:    "SOP [{$this->sop->code}] chờ duyệt",
        body:     "SOP \"{$this->sop->title}\" v{$this->version->version_number} vừa được gửi để duyệt.",
        url:      route('backend.sop.pending-approvals'),
        icon:     'sop',
        severity: 'info',
        meta:     ['sop_id' => $this->sop->id, 'sop_uuid' => $this->sop->uuid],
    );
}
```

### 4.3 Icon key → CSS class mapping

| key | Màu nền | Icon SVG |
|---|---|---|
| `bell` | `bg-primary/10 text-primary` | bell |
| `check` / `success` | `bg-success/10 text-success` | checkmark |
| `warning` | `bg-warning/10 text-warning` | exclamation |
| `error` | `bg-error/10 text-error` | x-circle |
| `info` | `bg-info/10 text-info` | info |
| `user` | `bg-primary/10 text-primary` | user |
| `task` | `bg-secondary/10 text-secondary` | clipboard |
| `sop` | `bg-accent/10 text-accent` | document |
| `kc` | `bg-accent/10 text-accent` | book |
| `lead` | `bg-primary/10 text-primary` | briefcase |

### 4.4 Type key registry — toàn hệ thống

Naming: `{module}_{event}` — lowercase snake_case.

```
Module SOP:          sop_submitted, sop_approved, sop_rejected, sop_expiry_warning, sop_next_approver
Module KcItem:       kc_submitted, kc_approved, kc_rejected, kc_expiring_soon, kc_archived
Module JobPosting:   jp_expiry_warning
Module WorkflowAuto: workflow_notification
Module Lead:         lead_assigned, lead_status_changed, lead_overdue
Module Customer:     customer_assigned, customer_status_changed
Module Task:         task_assigned, task_due_soon, task_overdue, task_completed, task_commented
Module Project:      project_member_added, project_milestone_due, project_status_changed
Module Employee:     employee_onboarded, employee_leave_approved, employee_leave_rejected, employee_review_due
Module Leave:        leave_submitted, leave_approved, leave_rejected
Module Assessment:   assessment_assigned, assessment_submitted
Module KpiGoal:      kpi_target_approaching, kpi_completed, kpi_missed
Module Survey:       survey_assigned, survey_deadline_approaching
Module Recruitment:  recruitment_application_received, recruitment_interview_scheduled
Module PerformReview: review_period_started, review_submitted, review_completed
Module Subscription: subscription_expiring_db, subscription_expired_db
```

---

## 5. API Endpoints

Tất cả đặt trong `app/Http/Controllers/Api/` — không phải trong module cụ thể.

### 5.1 Routes

```php
// routes/api.php — nhóm auth:sanctum + tenant middleware
Route::prefix('notifications')->group(function () {
    Route::get('/',                        [NotificationController::class, 'index']);
    Route::get('/unread-count',            [NotificationController::class, 'unreadCount']);
    Route::patch('/{uuid}/read',           [NotificationController::class, 'markRead']);
    Route::post('/read-all',               [NotificationController::class, 'markAllRead']);
    Route::delete('/{uuid}',               [NotificationController::class, 'destroy']);
    Route::post('/push-subscribe',         [NotificationController::class, 'pushSubscribe']);
    Route::delete('/push-unsubscribe',     [NotificationController::class, 'pushUnsubscribe']);
});
```

### 5.2 Response format

**GET /api/notifications**

Query params: `?page=1&per_page=20&filter=unread&type=sop_submitted`

```json
{
  "data": [
    {
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "type": "sop_submitted",
      "title": "SOP [SOP-001] chờ duyệt",
      "body": "SOP \"Quy trình tuyển dụng\" v2 vừa được gửi để duyệt.",
      "url": "/dashboard/sop/pending-approvals",
      "icon": "sop",
      "severity": "info",
      "read": false,
      "created_at": "2026-06-10T08:30:00Z",
      "time_ago": "2 phút trước"
    }
  ],
  "meta": {
    "total": 45,
    "unread": 12,
    "current_page": 1,
    "last_page": 3
  }
}
```

**GET /api/notifications/unread-count**

```json
{ "count": 12 }
```

### 5.3 Controller

```php
// app/Http/Controllers/Api/NotificationController.php

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = $user->notifications()
            ->when($request->filter === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->when($request->type, fn ($q, $t) => $q->whereJsonContains('data->type', $t))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => $query->map(fn ($n) => $this->formatNotification($n)),
            'meta' => [
                'total'        => $query->total(),
                'unread'       => $user->unreadNotifications()->count(),
                'current_page' => $query->currentPage(),
                'last_page'    => $query->lastPage(),
            ],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $uuid): JsonResponse
    {
        $n = $request->user()->notifications()->where('uuid', $uuid)->firstOrFail();
        $n->markAsRead();
        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $request->user()->notifications()->where('uuid', $uuid)->delete();
        return response()->json(['ok' => true]);
    }

    private function formatNotification(\Illuminate\Notifications\DatabaseNotification $n): array
    {
        $data = $n->data;
        return [
            'uuid'       => $n->uuid ?? $n->id,
            'type'       => $data['type'] ?? 'unknown',
            'title'      => $data['title'] ?? $data['message'] ?? '(Thông báo)',
            'body'       => $data['body']  ?? $data['message'] ?? '',
            'url'        => $data['url']   ?? '',
            'icon'       => $data['icon']  ?? 'bell',
            'severity'   => $data['severity'] ?? 'info',
            'read'       => ! is_null($n->read_at),
            'created_at' => $n->created_at->toISOString(),
            'time_ago'   => $n->created_at->diffForHumans(),
        ];
    }
}
```

> **Lưu ý tenant scope:** `$user->notifications()` scope theo `notifiable_id` đã đủ cho user 1 org. Nếu user multi-org (tương lai), thêm `.where('organization_id', TenantContext::getOrganizationId())`. Notification class cần set `organization_id` trong payload `meta` — khi migration thêm cột này vào bảng, override trong từng class hoặc dùng observer.

> **Rate limiting:** Thêm trong `AppServiceProvider::boot()` trước khi dùng route throttle:
>
> ```php
> RateLimiter::for('notifications', fn (Request $request) =>
>     Limit::perMinute(60)->by($request->user()?->id ?: $request->ip())
> );
> ```
>
> Và thêm `->middleware(['throttle:notifications'])` vào route group trong §5.1.

---

## 6. Frontend — Bell Dropdown

### 6.1 Header partial thay đổi

Thay toàn bộ block `dd-wrap` của bell icon trong `resources/views/layouts/partials/header.blade.php`:

```blade
{{-- Bell Dropdown — Alpine component --}}
<div x-data="notifBell()" @click.outside="open = false" class="dd-wrap">

    {{-- Trigger button --}}
    <button class="icon-btn" @click="toggle()" title="Thông báo">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        {{-- Unread badge --}}
        <span x-show="unread > 0"
              x-text="unread > 99 ? '99+' : unread"
              class="absolute -top-1 -right-1 min-w-[16px] h-4 px-0.5 rounded-full
                     bg-error text-white text-[10px] font-bold flex items-center justify-center
                     leading-none pointer-events-none"></span>
    </button>

    {{-- Panel --}}
    <div x-show="open" x-transition.opacity.duration.150ms
         class="dd-panel notif-panel" style="width:360px">

        {{-- Header --}}
        <div class="notif-hdr flex items-center justify-between px-4 py-3 border-b border-base-200">
            <span class="font-semibold text-sm">Thông báo
                <span x-show="unread > 0" x-text="'(' + unread + ' chưa đọc)'"
                      class="text-primary font-normal text-xs ml-1"></span>
            </span>
            <div class="flex items-center gap-2">
                <button x-show="unread > 0" @click="readAll()"
                        class="text-xs text-base-content/50 hover:text-primary transition-colors">
                    Đọc tất cả
                </button>
                <a href="/dashboard/notifications"
                   class="text-xs text-primary hover:underline">Xem tất cả</a>
            </div>
        </div>

        {{-- Loading state --}}
        <div x-show="loading" class="p-6 flex justify-center">
            <span class="loading loading-spinner loading-sm text-primary"></span>
        </div>

        {{-- Empty state --}}
        <div x-show="!loading && items.length === 0"
             class="px-4 py-8 text-center text-sm text-base-content/40">
            Không có thông báo mới
        </div>

        {{-- List --}}
        <div x-show="!loading && items.length > 0" class="divide-y divide-base-200 max-h-80 overflow-y-auto">
            <template x-for="n in items" :key="n.uuid">
                <a :href="n.url || '#'"
                   @click="markRead(n)"
                   class="notif-item flex items-start gap-3 px-4 py-3 hover:bg-base-50 transition-colors"
                   :class="{ 'bg-primary/5': !n.read }">
                    {{-- Icon --}}
                    <div class="notif-icon w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                         :class="iconClass(n.icon)">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                             x-html="iconSvgPath(n.icon)"></svg>
                    </div>
                    {{-- Text --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm leading-snug line-clamp-2"
                           :class="n.read ? 'text-base-content/70' : 'text-base-content font-medium'"
                           x-text="n.title"></p>
                        <p class="text-xs text-base-content/40 mt-0.5" x-text="n.time_ago"></p>
                    </div>
                    {{-- Unread dot --}}
                    <div x-show="!n.read"
                         class="w-2 h-2 rounded-full bg-primary mt-1.5 shrink-0"></div>
                </a>
            </template>
        </div>

        {{-- Footer --}}
        <div x-show="!loading" class="px-4 py-2 border-t border-base-200 text-center">
            <a href="/dashboard/notifications"
               class="text-xs text-base-content/50 hover:text-primary transition-colors">
                Xem tất cả thông báo →
            </a>
        </div>

    </div>
</div>
```

### 6.2 Alpine component — `notifBell()`

Đăng ký trong `resources/js/admin-shell.js`:

```js
// resources/js/admin-shell.js

document.addEventListener('alpine:init', () => {
    Alpine.data('notifBell', () => ({
        open:    false,
        loading: false,
        items:   [],
        unread:  0,
        _pollingTimer: null,

        async init() {
            await this.fetchCount();
            this._startPolling();
        },

        async toggle() {
            this.open = !this.open;
            if (this.open && this.items.length === 0) await this.fetchItems();
        },

        async fetchCount() {
            try {
                const r = await fetch('/api/notifications/unread-count', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (r.ok) this.unread = (await r.json()).count;
            } catch {}
        },

        async fetchItems() {
            this.loading = true;
            try {
                const r = await fetch('/api/notifications?per_page=8', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (r.ok) {
                    const res = await r.json();
                    this.items  = res.data;
                    this.unread = res.meta.unread;
                }
            } finally {
                this.loading = false;
            }
        },

        async markRead(n) {
            if (n.read) return;
            n.read = true;
            this.unread = Math.max(0, this.unread - 1);
            fetch(`/api/notifications/${n.uuid}/read`, {
                method:  'PATCH',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
            }).catch(() => {});
        },

        async readAll() {
            this.items.forEach(n => { n.read = true; });
            this.unread = 0;
            fetch('/api/notifications/read-all', {
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
            }).catch(() => {});
        },

        iconClass(icon) {
            const map = {
                check: 'bg-success/15 text-success', success: 'bg-success/15 text-success',
                warning: 'bg-warning/15 text-warning',
                error: 'bg-error/15 text-error',
                info: 'bg-info/15 text-info',
                sop: 'bg-accent/15 text-accent', kc: 'bg-accent/15 text-accent',
                task: 'bg-secondary/15 text-secondary',
            };
            return map[icon] ?? 'bg-primary/15 text-primary';
        },

        iconSvgPath(icon) {
            // Return SVG path d="" string per icon key
            const paths = {
                bell:    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>',
                check:   '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                warning: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 110 18A9 9 0 0112 3z"/>',
                error:   '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                task:    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
                user:    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
                sop:     '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
                kc:      '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
                lead:    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
            };
            return paths[icon] ?? paths.bell;
        },

        _startPolling() {
            // Polling unread count mỗi 30 giây; skip khi tab ẩn để tránh lãng phí request
            this._pollingTimer = setInterval(() => {
                if (!document.hidden) this.fetchCount();
            }, 30_000);
            // Fetch ngay khi user quay lại tab — không chờ hết interval
            this._visibilityHandler = () => {
                if (!document.hidden) this.fetchCount();
            };
            document.addEventListener('visibilitychange', this._visibilityHandler);
        },

        destroy() {
            clearInterval(this._pollingTimer);
            document.removeEventListener('visibilitychange', this._visibilityHandler);
        },
    }));
});
```

> **Lưu ý tích hợp Alpine.js:** Alpine đã được setup trong `resources/js/app.js` (`window.Alpine`, `alpine:init` block). Đăng ký `notifBell()` **trong `alpine:init` của `app.js`**, không phải trong `admin-shell.js` (file đó là vanilla JS thuần). Đồng thời xóa handler `toggleDD('notifPanel')` tại `admin-shell.js:104-105` vì Alpine sẽ quản lý dropdown từ đây.

### 6.3 CSS bổ sung cho `app.css`

```css
/* Notification bell badge positioning */
#notifBtn { position: relative; }

/* Override badge-dot → dynamic badge */
.notif-badge {
    position: absolute; top: -4px; right: -4px;
    min-width: 16px; height: 16px;
    border-radius: 9999px;
    background: var(--color-error);
    color: #fff;
    font-size: 10px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    padding: 0 3px;
    border: 2px solid var(--color-base-100);
    pointer-events: none;
}

/* Unread item highlight */
.notif-item.unread { background: color-mix(in oklch, var(--color-primary) 5%, transparent); }
```

---

## 7. Notification Center Page

### 7.1 Route

```php
// Modules/Auth/routes/web.php hoặc routes/web.php
Route::get('/dashboard/notifications', [NotificationCenterController::class, 'index'])
    ->name('notifications.index')
    ->middleware(['auth', 'verified']);
```

### 7.2 Controller

```php
// app/Http/Controllers/NotificationCenterController.php
class NotificationCenterController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = $user->notifications()
            ->when($request->filter === 'unread', fn($q) => $q->whereNull('read_at'))
            ->when($request->type, fn($q, $t) => $q->whereJsonContains('data->type', $t))
            ->latest();

        $notifications = $query->paginate(25)->withQueryString();
        $unreadCount   = $user->unreadNotifications()->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }
}
```

### 7.3 View layout

```
┌──────────────────────────────────────────────────────────┐
│ Thông báo                              [Đọc tất cả]      │
│                                                          │
│ [Tất cả (45)] [Chưa đọc (12)] [SOP] [Task] [Lead] ...  │
│                                                          │
│ ┌────────────────────────────────────────────────────┐  │
│ │ 🔵 [icon] SOP [SOP-001] chờ duyệt          2p trước│  │
│ │          SOP "Quy trình tuyển dụng" v2 đang...     │  │
│ ├────────────────────────────────────────────────────┤  │
│ │ ○  [icon] Task "Chuẩn bị báo cáo Q2" được giao    │  │
│ │          Bạn được giao task mới bởi Nguyễn A...    │  │
│ └────────────────────────────────────────────────────┘  │
│                                                          │
│                  [← Prev] 1 / 3 [Next →]               │
└──────────────────────────────────────────────────────────┘
```

**Tính năng:**
- Filter tabs: Tất cả / Chưa đọc / theo loại module
- Click vào item → mark read + navigate to `url`
- Bulk: "Đọc tất cả", "Xóa đã đọc"
- Pagination 25/trang

---

## 8. Browser Push Notifications

> **Phase 2** — triển khai sau khi in-app hoàn chỉnh.

### 8.1 Package

```bash
composer require laravel-notification-channels/webpush
```

### 8.2 Migration bổ sung

```php
// push_subscriptions table (do package tạo)
Schema::create('push_subscriptions', function (Blueprint $table) {
    $table->id();
    $table->morphs('subscribable');
    $table->string('endpoint')->unique();
    $table->string('public_key')->nullable();
    $table->string('auth_token')->nullable();
    $table->string('content_encoding')->nullable();
    $table->timestamps();
});
```

### 8.3 User model thêm trait

```php
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable {
    use HasPushSubscriptions;
}
```

### 8.4 Thêm channel vào notification class

```php
public function via(object $notifiable): array
{
    $channels = ['database'];
    if ($notifiable->pushSubscriptions()->exists()) {
        $channels[] = 'webpush';
    }
    return $channels;
}

public function toWebPush(object $notifiable, mixed $notification): WebPushMessage
{
    $data = $this->toDatabase($notifiable);
    return (new WebPushMessage)
        ->title($data['title'])
        ->body($data['body'])
        ->icon('/icons/notification-icon.png')
        ->action('Xem ngay', $data['url']);
}
```

### 8.5 Frontend — subscribe

```js
// resources/js/modules/push-notifications.js
async function subscribePush() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') return;

    const sw = await navigator.serviceWorker.register('/sw.js');
    const vapidKey = document.querySelector('meta[name="vapid-public-key"]')?.content;

    const sub = await sw.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: vapidKey,
    });

    await fetch('/api/notifications/push-subscribe', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
        body:    JSON.stringify(sub.toJSON()),
    });
}
```

---

## 9. Catalog thông báo theo module

### Ưu tiên Phase 1 (cần notification class mới)

#### Module Task

| Event | Người nhận | Trigger |
|---|---|---|
| `task_assigned` | Assignee | Task được giao |
| `task_due_soon` | Assignee | D-1 trước deadline (scheduled command) |
| `task_overdue` | Assignee + Creator | Quá deadline (scheduled command) |
| `task_commented` | Assignee + Creator | Có comment mới |

```php
// Modules/Task/app/Notifications/TaskAssignedNotification.php
class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public function __construct(private readonly Task $task, private readonly User $assigner) {}
    
    public function via($n): array { return ['database']; }
    
    public function toDatabase($n): array
    {
        return NotificationData::make(
            type:     'task_assigned',
            title:    "Task mới được giao: {$this->task->title}",
            body:     "{$this->assigner->name} đã giao cho bạn task \"{$this->task->title}\".",
            url:      route('task.show', $this->task),
            icon:     'task',
            severity: 'info',
            meta:     ['task_id' => $this->task->id],
        );
    }
}
```

#### Module Lead

| Event | Người nhận | Trigger |
|---|---|---|
| `lead_assigned` | Assignee | Lead được assign |
| `lead_status_changed` | Assignee + Creator | Status thay đổi |
| `lead_overdue` | Assignee | Follow-up date qua (scheduled) |

#### Module Customer

| Event | Người nhận | Trigger |
|---|---|---|
| `customer_assigned` | Assignee | Customer được assign |

#### Module Employee / Leave

| Event | Người nhận | Trigger |
|---|---|---|
| `leave_submitted` | Manager | Nhân viên gửi đơn xin phép |
| `leave_approved` | Employee | Manager duyệt |
| `leave_rejected` | Employee | Manager từ chối + lý do |

#### Module PerformanceReview

| Event | Người nhận | Trigger |
|---|---|---|
| `review_period_started` | Tất cả user trong org | Kỳ đánh giá bắt đầu |
| `review_submitted` | Reviewer | Nhân viên nộp self-assessment |
| `review_completed` | Employee | Đánh giá hoàn tất |

#### Module Project

| Event | Người nhận | Trigger |
|---|---|---|
| `project_member_added` | Member mới | Thêm vào project |
| `project_milestone_due` | PM | Milestone sắp đến hạn (D-3) |

#### Module Assessment

| Event | Người nhận | Trigger |
|---|---|---|
| `assessment_assigned` | Người được giao | Assessment được phân công |
| `assessment_submitted` | Người tạo | Có submission mới |

#### Module KpiGoal

| Event | Người nhận | Trigger |
|---|---|---|
| `kpi_target_approaching` | Owner | Đạt 80% target |
| `kpi_completed` | Owner + Manager | Đạt 100% |
| `kpi_missed` | Owner + Manager | Kỳ KPI kết thúc mà chưa đạt target (scheduled end-of-period) |

#### Module KcItem — trigger `kc_submitted` (bổ sung)

| Event | Người nhận | Trigger |
|---|---|---|
| `kc_submitted` | Users có quyền `kc.approve` trong org | Nhân viên nộp tài liệu để duyệt |

> Tương tự luồng SOP: dispatch trong service/action submit KC, query user có permission `kc.approve` trong cùng `organization_id`. Hiện tại KC chỉ có approved/rejected — approver không nhận chuông khi có tài liệu chờ.

#### Module Subscription — thêm `database` channel (bổ sung)

| Event | Người nhận | Trigger |
|---|---|---|
| `subscription_expiring_db` | System Admin của org | 30/7/3 ngày trước hết hạn (scheduled) |
| `subscription_expired_db` | System Admin của org | Ngay khi hết hạn |

> `RenewalReminderNotification` hiện chỉ gửi `mail` — admin không thấy chuông trong app. Thêm `'database'` vào `via()` và implement `toDatabase()` với `NotificationData::make()`.

#### Module Employee — trigger `employee_onboarded` (bổ sung)

| Event | Người nhận | Trigger |
|---|---|---|
| `employee_onboarded` | System Admin | HR tạo bản ghi nhân viên mới |

> Giúp admin biết cấp tài khoản / thiết bị kịp thời sau khi HR hoàn tất onboarding.

### Ưu tiên Phase 2

- Survey: `survey_assigned`, `survey_deadline_approaching`
- Recruitment: `recruitment_application_received`, `recruitment_interview_scheduled`
- Subscription db channel: `subscription_expiring_db`, `subscription_expired_db`

---

## 10. Refactor Notification Classes hiện có

Các class hiện tại cần cập nhật `toDatabase()` để dùng `NotificationData::make()`:

### 10.1 KcItemApprovedNotification (thiếu `type`)

```php
// Trước
return [
    'item_id' => $this->kcItem->id,
    'title'   => $this->kcItem->title,
    'url'     => route('backend.kc-items.show', $this->kcItem->id),
    'message' => 'Tài liệu "' . $this->kcItem->title . '" đã được duyệt.',
];

// Sau
return NotificationData::make(
    type:     'kc_approved',
    title:    "Tài liệu \"{$this->kcItem->title}\" đã được duyệt",
    body:     "Tài liệu \"{$this->kcItem->title}\" vừa được duyệt thành công.",
    url:      route('backend.kc-items.show', $this->kcItem->id),
    icon:     'kc',
    severity: 'success',
    meta:     ['item_id' => $this->kcItem->id],
);
```

### 10.2 WorkflowNotification (`toArray()` → `toDatabase()` + thiếu `url`)

> **Lưu ý:** Class hiện dùng `toArray()` — Laravel fallback vẫn ghi vào DB nhưng là anti-pattern và payload thiếu `url`, `icon`, `severity`. Phải đổi tên method sang `toDatabase()`.

```php
// Sau
return NotificationData::make(
    type:     'workflow_notification',
    title:    $this->title,
    body:     $this->body,
    url:      '',
    icon:     'bell',
    severity: 'info',
);
```

### 10.3 SopSubmittedNotification (cần thêm `icon`, `severity`)

```php
return NotificationData::make(
    type:     'sop_submitted',
    title:    "SOP [{$this->sop->code}] chờ duyệt",
    body:     "SOP \"{$this->sop->title}\" v{$this->version->version_number} đang chờ duyệt.",
    url:      route('backend.sop.pending-approvals'),
    icon:     'sop',
    severity: 'info',
    meta:     ['sop_id' => $this->sop->id, 'sop_uuid' => $this->sop->uuid],
);
```

> Áp dụng tương tự cho: `SopApprovedNotification`, `SopRejectedNotification`, `SopExpiryWarningNotification`, `SopNextApproverNotification`, `KcItemRejectedNotification`, `KcItemExpiringSoonNotification`, `KcItemArchivedNotification`, `JpJobPostExpiryWarningNotification`.

---

## 11. Lộ trình triển khai

### Phase 1 — In-app (ưu tiên cao)

> **Lý do thứ tự:** Refactor class hiện có (Bước 2) phải xong trước khi API lên (Bước 3) để `formatNotification()` parse đúng ngay từ đầu. Bell Dropdown (Bước 4) cần API sẵn để test thật.

**Bước 1 — Database & shared helper (1 ngày)**
- [ ] Migration thêm `organization_id` vào `notifications`
- [ ] Tạo `app/Shared/Notifications/NotificationData.php`

**Bước 2 — Refactor 11 class hiện có (1 ngày)**
- [ ] Cập nhật toàn bộ class dùng `NotificationData::make()` (xem §10)
- [ ] `WorkflowNotification`: đổi `toArray()` → `toDatabase()`
- [ ] `RenewalReminderNotification`: thêm `'database'` vào `via()` + implement `toDatabase()`

**Bước 3 — API (1 ngày)**
- [ ] Tạo `routes/api.php` (file chưa tồn tại)
- [ ] `NotificationController` với 5 endpoints + rate limiting `throttle:notifications`
- [ ] Thêm `RateLimiter::for('notifications', ...)` trong `AppServiceProvider::boot()`

**Bước 4 — Bell Dropdown (1 ngày)**
- [ ] Update `header.blade.php` — Alpine `x-data="notifBell()"` + template
- [ ] Đăng ký `notifBell()` trong `alpine:init` block của `app.js` (không phải `admin-shell.js`)
- [ ] Xóa `toggleDD('notifPanel')` handler tại `admin-shell.js:104-105`
- [ ] CSS bổ sung trong `app.css`
- [ ] Xóa `badge-dot` static, thay bằng dynamic Alpine badge

**Bước 5 — Notification Center Page (1 ngày)**
- [ ] `NotificationCenterController`
- [ ] View `resources/views/notifications/index.blade.php`
- [ ] Route đăng ký
- [ ] Sidebar menu link (trong `config/permissions.php`)

**Bước 6 — New notification classes Phase 1 (2–3 ngày)**
- [x] Task: `TaskAssignedNotification`, `TaskDueSoonNotification`, `TaskOverdueNotification`
- [x] Lead: `LeadAssignedNotification`
- [x] Leave: `LeaveSubmittedNotification`, `LeaveApprovedNotification`, `LeaveRejectedNotification`
- [x] KcItem: `KcItemSubmittedNotification` — gửi cho approvers khi tài liệu submitted
- [x] PerformanceReview: `ReviewPeriodStartedNotification`, `ReviewSubmittedNotification`, `ReviewCompletedNotification`
- [x] Employee: `EmployeeOnboardedNotification`
- [x] KpiGoal: `KpiMissedNotification` + `notifications:kpi-missed` scheduled command
- [x] Scheduled commands: `notifications:task-due-soon` (08:00), `notifications:task-overdue` (08:30), `notifications:kpi-missed` (09:00)

### Phase 2 — Browser Push

- [x] Install `minishlink/web-push` v10 (trực tiếp, không wrapper)
- [x] Migration `push_subscriptions` (endpoint unique, user_id index)
- [x] `PushSubscription` model + `User::pushSubscriptions()` relation
- [x] `WebPushService` — `sendToUser()`, `sendToUsers()`, expired sub cleanup
- [x] `WebPushChannel` — custom channel đăng ký qua `ChannelManager::extend('webpush')`
- [x] `config/webpush.php` — VAPID keys, timeout, batch_size
- [x] `php artisan webpush:vapid` — generate + ghi vào .env
- [x] Service Worker `/public/sw.js` — push event, notificationclick, skipWaiting
- [x] `resources/js/modules/push-notifications.js` — subscribe/unsubscribe, Alpine `pushToggle` component
- [x] VAPID meta tag trong `layouts/backend.blade.php`
- [x] Push toggle UI trong notification center
- [x] `toWebPush()` + conditional `webpush` channel trong 5 notification class: `TaskAssigned`, `LeaveSubmitted`, `LeaveApproved`, `LeaveRejected`, `KcItemSubmitted`
- [x] Rate limiter `push-subscribe` (10 req/min)
- [x] API: `POST /api/notifications/push-subscribe`, `DELETE /api/notifications/push-unsubscribe`

### Phase 3 — Real-time (optional)

- [ ] Evaluate: Laravel Reverb vs polling đã đủ tốt
- [ ] Nếu Reverb: thêm `broadcast` channel, frontend WebSocket listener

---

## 12. Checklist

### Database
- [ ] Migration `add_organization_id_to_notifications` đã chạy
- [ ] `notifications` table có đủ indexes cho query performance

### Shared
- [ ] `NotificationData::make()` có PHPDoc đầy đủ
- [ ] Unit test cho `NotificationData`

### API
- [ ] `routes/api.php` đã tạo với nhóm `auth:sanctum` + tenant middleware
- [ ] Rate limiting: `RateLimiter::for('notifications', ...)` trong `AppServiceProvider`
- [ ] Route group dùng `->middleware(['throttle:notifications'])`
- [ ] Response format nhất quán (có `uuid`, không expose `id`)

### Notification classes
- [ ] Mọi class dùng `NotificationData::make()`
- [ ] `type` key unique toàn hệ thống (kiểm tra catalog §9)
- [ ] `url` là route hợp lệ, không hardcode domain
- [ ] Implements `ShouldQueue` + `use Queueable`
- [ ] `via()` khai báo đúng channels

### Frontend Bell
- [ ] `notifBell()` đăng ký trong `alpine:init` của `app.js` (không phải `admin-shell.js`)
- [ ] `toggleDD('notifPanel')` đã xóa khỏi `admin-shell.js`
- [ ] Polling 30s hoạt động, không gây memory leak (`clearInterval` + `removeEventListener` khi destroy)
- [ ] Polling pause khi tab ẩn (Page Visibility API — `document.hidden`)
- [ ] Fetch lại ngay khi tab quay lại (`visibilitychange` event)
- [ ] `X-CSRF-TOKEN` đúng trên mọi mutating request
- [ ] Badge ẩn khi `unread = 0`
- [ ] Dropdown đóng khi click ra ngoài (`@click.outside`)
- [ ] Không break layout khi notification text dài (line-clamp)

### Notification Center
- [ ] Pagination hoạt động với query string preservation
- [ ] Filter "Chưa đọc" đúng
- [ ] "Đọc tất cả" update cả count lẫn UI
- [ ] Accessible: keyboard navigation, focus management

### Push (Phase 2)
- [ ] VAPID keys trong `.env` (không commit)
- [ ] Service Worker scope đúng
- [ ] Fallback graceful khi browser không support

### Queue
- [ ] `php artisan queue:listen` (dev) / Supervisor (production) đang chạy
- [ ] Failed jobs có alert
