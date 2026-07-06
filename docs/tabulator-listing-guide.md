# Official Tabulator Listing Guide v2.0 — Áp dụng bắt buộc cho mọi module có danh sách bảng.

Hướng dẫn chuẩn để xây dựng trang danh sách dạng bảng (Tabulator + Alpine.js) cho bất kỳ module nào. Áp dụng cho toàn bộ stack: Laravel 13 / PHP 8.4 + DaisyUI 5 + Alpine.js 3 + Tabulator 6.

> **Nguồn gốc**: Đúc kết từ `Modules/Survey` (responses listing) và `Modules/Organization` (organizations listing) — cả hai đã production-ready.

---

## Mục lục

**Backend**
1. [Kiến trúc tổng quan](#1-kiến-trúc-tổng-quan)
2. [Cấu trúc file cần tạo](#2-cấu-trúc-file-cần-tạo)
3. [Model — Scope & Accessor](#3-model--scope--accessor)
4. [Query Layer — Hai biến thể](#4-query-layer--hai-biến-thể)
5. [API Controller](#5-api-controller)
6. [Resource](#6-resource)
7. [Web Controller](#7-web-controller)
8. [Routes](#8-routes)
9. [Database Index](#9-database-index)

**Frontend**
10. [CSS Theme — File dùng chung](#10-css-theme--file-dùng-chung)
11. [Blade View Skeleton](#11-blade-view-skeleton)
12. [Alpine.js Component — Pattern chuẩn](#12-alpinejs-component--pattern-chuẩn)
13. [Tabulator Config](#13-tabulator-config)
14. [Filter Widgets](#14-filter-widgets)
15. [AJAX Actions (Delete & Inline)](#15-ajax-actions-delete--inline)

**Nâng cao**
16. [Advanced UI — Column Toggle, Date Presets, Chained Dropdowns](#16-advanced-ui)

**Đảm bảo chất lượng**
17. [Error Handling & Logging](#17-error-handling--logging)
18. [Testing](#18-testing)
19. [Bảo mật — Checklist](#19-bảo-mật--checklist)
20. [Hiệu suất — Checklist](#20-hiệu-suất--checklist)
21. [Checklist triển khai từng bước](#21-checklist-triển-khai-từng-bước)

---

## 1. Kiến trúc tổng quan

```
Browser
  │
  ├─ GET /dashboard/{module}                    (Web route)
  │     └─▶ WebController::index()
  │              ├─ authorize()
  │              ├─ 1 query gộp → stat cards
  │              └─ return view (Blade)
  │
  └─ GET /backend/api/{module}                  (API route — XHR only)
        └─▶ ApiController::index()
                 ├─ authorize()
                 ├─ validate($request)
                 ├─ sort whitelist
                 ├─ query → paginate
                 └─ JsonResource → { data[], last_page, total }
```

**Nguyên tắc phân tách bắt buộc:**
| Lớp | Trách nhiệm | KHÔNG làm |
|-----|------------|-----------|
| WebController | Render Blade + stat cards | Trả JSON, redirect từ API |
| ApiController | Trả JSON cho Tabulator | Flash session, redirect |
| Alpine.js | Giữ filter state, sync URL | Render HTML, gọi non-AJAX link |
| Tabulator | Hiển thị + remote paginate/sort | Giữ business logic |

---

## 2. Cấu trúc file cần tạo

```
Modules/{Module}/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── {Module}Controller.php               ← Web CRUD
│   │   │   └── Api/
│   │   │       └── {Module}BackendApiController.php  ← JSON cho Tabulator
│   │   └── Resources/
│   │       └── {Model}ListResource.php               ← JSON shape mỗi row
│   ├── Models/
│   │   └── {Model}.php                               ← scopeFor*()
│   ├── Enums/
│   │   └── {Status}Enum.php                          ← label(), badgeClass()
│   ├── Policies/                                     ← (nếu dùng Policy)
│   │   └── {Model}Policy.php
│   └── Queries/                                      ← (nếu dùng CQRS)
│       ├── List{Models}Query.php
│       └── List{Models}Handler.php
├── resources/views/
│   └── index.blade.php
└── routes/web.php

resources/views/components/
└── tabulator-theme.blade.php                        ← CSS dùng chung (1 file toàn app)

database/migrations/
└── YYYY_MM_DD_add_listing_indexes_to_{table}.php
```

---

## 3. Model — Scope & Accessor

### Scope tenant / parent isolation

```php
// Tenant scope — mọi listing query bắt đầu bằng scope này
public function scopeForOrganization(Builder $query, int $orgId): Builder
{
    return $query->where('organization_id', $orgId);
    // SoftDeletes tự áp qua GlobalScope — không cần gọi withoutTrashed() thủ công
}

// Parent scope — khi resource thuộc về 1 parent cụ thể
public function scopeForSurvey(Builder $query, int $surveyId): Builder
{
    return $query->where('survey_id', $surveyId);
}
```

### Accessor cho binary / encoded fields

```php
use Illuminate\Database\Eloquent\Casts\Attribute;

// BINARY(16) IP address → human-readable IPv4/IPv6
protected function respondentIp(): Attribute
{
    return Attribute::make(
        get: function ($value) {
            if ($value === null) return null;
            $ip = inet_ntop($value);
            if ($ip === false) return null;
            // Strip IPv4-mapped IPv6 prefix "::ffff:"
            return str_starts_with($ip, '::ffff:') ? substr($ip, 7) : $ip;
        },
    );
}
```

### Enum với UI helpers — bắt buộc

```php
enum ItemStatus: int
{
    case Draft   = 0;
    case Active  = 1;
    case Closed  = 2;

    public function label(): string
    {
        return match ($this) {
            self::Draft  => 'Nháp',
            self::Active => 'Hoạt động',
            self::Closed => 'Đã đóng',
        };
    }

    // DaisyUI badge class — dùng trực tiếp trong JS formatter
    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft  => 'badge-ghost',
            self::Active => 'badge-success',
            self::Closed => 'badge-error',
        };
    }
}
```

### Policy-based authorization (khi logic phức tạp)

```php
// Modules/{Module}/app/Policies/{Model}Policy.php
class ItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['System_Admin', 'Manager']);
    }

    public function delete(User $user, Item $item): bool
    {
        // Logic phức tạp hơn simple permission check
        return $user->hasRole('System_Admin') || $item->owner_id === $user->id;
    }
}

// Đăng ký trong AuthServiceProvider hoặc PolicyServiceProvider của module
Gate::policy(Item::class, ItemPolicy::class);

// Dùng trong controller
$this->authorize('viewAny', Item::class);   // không có model instance
$this->authorize('delete', $item);          // có model instance
```

---

## 4. Query Layer — Hai biến thể

### Biến thể A: Direct Query trong ApiController (đơn giản)

Dùng khi logic filter/sort đơn giản, không cần tái sử dụng query ở nhiều nơi. Ví dụ: `SurveyBackendApiController::responses()`.

```php
$query = SurveyResponse::forSurvey($survey->id)
    ->select(['id', 'respondent_ref', 'status', 'submitted_at'])
    ->when(isset($validated['status']), fn ($q) => $q->where('status', $validated['status']))
    ->orderBy($sortField, $sortDir)
    ->when($sortField !== 'id', fn ($q) => $q->orderBy('id', $sortDir));

$paginator = $query->paginate($perPage, ['*'], 'page', $page);
```

### Biến thể B: CQRS Query + Handler (phức tạp)

Dùng khi: query phức tạp (JOIN để sort, eager load relations), hoặc cùng query được gọi từ nhiều controller/job. Ví dụ: `ListOrganizationsHandler`.

**Query DTO** — typed, immutable, không có logic:
```php
// Modules/{Module}/Queries/List{Models}Query.php
class ListItemsQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page      = 1,
        public readonly int     $perPage   = 25,
        public readonly string  $sortField = 'created_at',
        public readonly string  $sortDir   = 'desc',
        public readonly ?string $search    = null,
        public readonly ?string $status    = null,
        public readonly ?string $dateFrom  = null,
        public readonly ?string $dateTo    = null,
    ) {}
}
```

**Handler** — toàn bộ query logic ở đây:
```php
// Modules/{Module}/Queries/List{Models}Handler.php
class ListItemsHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['name', 'status', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListItemsQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField : 'created_at';
        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = Item::select('items.*')->withCount('children');

        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('name', 'like', $term)
                    ->orWhere('code', 'like', $term);
            });
        }

        if ($query->status !== null) {
            $q->where('status', $query->status);
        }

        // Explicit bounds — KHÔNG dùng whereDate()
        if ($query->dateFrom !== null) {
            $q->where('created_at', '>=', $query->dateFrom . ' 00:00:00');
        }
        if ($query->dateTo !== null) {
            $q->where('created_at', '<=', $query->dateTo . ' 23:59:59');
        }

        // Sort qua alias từ withCount — không cần table prefix
        match ($sortField) {
            'children_count' => $q->orderBy('children_count', $sortDir),
            default          => $q->orderBy('items.' . $sortField, $sortDir),
        };

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
```

**ApiController dùng Handler:**
```php
public function index(Request $request, ListItemsHandler $handler): JsonResponse
{
    // ... validate, sort ...
    $query = new ListItemsQuery(
        page:      max(1, (int) ($validated['page'] ?? 1)),
        perPage:   min(100, max(5, (int) ($validated['size'] ?? 25))),
        sortField: $sortField,
        sortDir:   $sortDir,
        search:    $validated['search'] ?? null,
        status:    isset($validated['status']) ? (string) $validated['status'] : null,
        dateFrom:  $validated['date_from'] ?? null,
        dateTo:    $validated['date_to']   ?? null,
    );

    $paginator = $handler->handle($query);
    // ...
}
```

**Khi nào chọn B thay A:**
- Query cần `leftJoin` để sort theo related column (xem `province_name` sort trong Organization)
- Cùng query được dùng trong scheduled job, export, hoặc test riêng
- Logic filter > 3 điều kiện và cần unit test độc lập

---

## 5. API Controller

```php
<?php

namespace Modules\{Module}\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\{Module}\Http\Resources\{Model}ListResource;
use Modules\{Module}\Models\{Model};

class {Module}BackendApiController extends Controller
{
    // Map field name Tabulator gửi → column thực trong DB
    // Chỉ liệt kê những column sortable và an toàn
    private const SORTABLE = [
        'id'         => 'id',
        'name'       => 'name',
        'status'     => 'status',
        'created_at' => 'created_at',
    ];

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', {Model}::class);
        // Hoặc: $this->authorize('{module}.view');

        // Validate TẤT CẢ query params — không dùng $request->all() raw
        $validated = $request->validate([
            'page'   => ['nullable', 'integer', 'min:1'],
            'size'   => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'integer', 'in:0,1,2'],
            'from'   => ['nullable', 'date_format:Y-m-d'],
            'to'     => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        // Sort field phải qua whitelist — KHÔNG đưa user input vào orderBy
        $sortRaw   = $request->input('sort.0');
        $sortKey   = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortField = self::SORTABLE[$sortKey] ?? 'created_at';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $page    = max(1, (int) ($validated['page'] ?? 1));
        $perPage = min(100, max(5, (int) ($validated['size'] ?? 25)));

        // Chỉ select cột cần thiết — KHÔNG select(*)
        $query = {Model}::select(['id', 'name', 'status', 'created_at'])
            ->when(
                isset($validated['search']) && $validated['search'] !== '',
                // PREFIX LIKE — dùng được index. KHÔNG '%term%' với dữ liệu lớn.
                fn ($q) => $q->where('name', 'like', $validated['search'] . '%')
            )
            ->when(isset($validated['status']),
                fn ($q) => $q->where('status', $validated['status'])
            )
            ->when(isset($validated['from']),
                // Explicit bounds — whereDate() wrap DATE() làm mất index
                fn ($q) => $q->where('created_at', '>=', $validated['from'] . ' 00:00:00')
            )
            ->when(isset($validated['to']),
                fn ($q) => $q->where('created_at', '<=', $validated['to'] . ' 23:59:59')
            )
            ->orderBy($sortField, $sortDir)
            // Secondary sort by id: stable pagination khi rows có cùng sort value
            ->when($sortField !== 'id', fn ($q) => $q->orderBy('id', $sortDir));

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'      => {Model}ListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
```

---

## 6. Resource

Resource định nghĩa JSON shape của mỗi row. Logic tính toán (URLs, permissions, labels) đặt ở đây — không trong controller, không trong Blade, không trong JS.

```php
<?php

namespace Modules\{Module}\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {Model}ListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,

            // Enum → value + label + badge class (để JS formatter dùng trực tiếp)
            'status_value' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_badge' => $this->status->badgeClass(),

            // Date: format ở server — không để JS format
            'created_at'   => $this->created_at?->format('d/m/Y H:i'),

            // URLs sinh sẵn — JS dùng thẳng, không expose route logic ra client
            'show_url'     => route('backend.{module}.show', $this->resource),
            'edit_url'     => route('backend.{module}.edit', $this->resource),
            'delete_url'   => route('backend.{module}.destroy', $this->resource),

            // Business logic delete condition — tính ở server
            'can_delete'   => $this->status !== StatusEnum::Active,
        ];
    }
}
```

---

## 7. Web Controller

```php
public function index(): View
{
    $this->authorize('viewAny', {Model}::class);

    // Gộp counts thành 1 query với SUM(CASE WHEN) — không query nhiều lần
    $counts = {Model}::selectRaw(
        'COUNT(*) as total_all,
         SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_active',
        [StatusEnum::Active->value]
    )->first();

    $totalAll    = (int) ($counts->total_all    ?? 0);
    $totalActive = (int) ($counts->total_active ?? 0);

    // Enum options cho TomSelect — serialize 1 lần ở server, inject vào JS
    $statuses = collect(StatusEnum::cases())
        ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
        ->all();

    return view('{module}::index', compact('totalAll', 'totalActive', 'statuses'));
}
```

---

## 8. Routes

```php
// Modules/{Module}/routes/web.php

// ── Web routes (CRUD) ────────────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('dashboard')->name('backend.')->group(function () {

    // Option A: Route::resource() (khi CRUD đầy đủ)
    Route::resource('{module}', {Module}Controller::class);

    // Option B: Route explicit (khi cần control tên route)
    Route::prefix('{module}')->name('{module}.')->group(function () {
        Route::get('/',            [{Module}Controller::class, 'index'])->name('index');
        Route::get('/create',      [{Module}Controller::class, 'create'])->name('create');
        Route::post('/',           [{Module}Controller::class, 'store'])->name('store');
        Route::get('/{item}',      [{Module}Controller::class, 'show'])->name('show');
        Route::get('/{item}/edit', [{Module}Controller::class, 'edit'])->name('edit');
        Route::put('/{item}',      [{Module}Controller::class, 'update'])->name('update');
        Route::delete('/{item}',   [{Module}Controller::class, 'destroy'])->name('destroy');
    });

});

// ── JSON API cho Tabulator ────────────────────────────────────────────────────
// PHẢI tách prefix 'backend/api' — KHÔNG nằm trong 'dashboard'
// Lý do: API routes không được redirect hay flash session
Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('{module}', [{Module}BackendApiController::class, 'index'])->name('{module}');
    // Nếu có sub-resource: Route::get('{module}/{parent}/items', ...)->name('{module}.items');
});
```

---

## 9. Database Index

```php
public function up(): void
{
    Schema::table('{table}', function (Blueprint $table) {

        // Pattern chuẩn: equality columns trước, range/sort sau
        // organization_id: equality (WHERE)
        // deleted_at: SoftDeletes filter (IS NULL)
        // created_at: ORDER BY / range filter
        $table->index(
            ['organization_id', 'deleted_at', 'created_at'],
            '{table}_listing_idx'
        );

        // Nếu có parent scope (responses thuộc survey):
        $table->index(
            ['survey_id', 'deleted_at', 'submitted_at'],
            '{table}_parent_sort_idx'
        );
    });
}

public function down(): void
{
    Schema::table('{table}', function (Blueprint $table) {
        $table->dropIndex('{table}_listing_idx');
    });
}
```

**Nguyên tắc:**
- Column `=` luôn đứng trước column `range` hoặc `ORDER BY` trong composite index.
- `deleted_at` phải có trong index khi model dùng `SoftDeletes`.
- Không đặt column cardinality thấp (boolean, status 2-3 giá trị) làm leading column.
- Verify bằng `EXPLAIN SELECT ... WHERE organization_id = ? ORDER BY created_at DESC LIMIT 25`.

---

## 10. CSS Theme — File dùng chung

Thay vì copy-paste CSS vào mỗi Blade view, tạo **1 Blade component** dùng chung với CSS class `.tabulator-daisy`.

### Tạo component

```php
// resources/views/components/tabulator-theme.blade.php
```

```html
{{-- Wrapper: thêm class "tabulator-daisy" vào div chứa Tabulator --}}
{{-- Usage: <x-tabulator-theme /> ở @push('styles') --}}
<style>
/* ── Tabulator — DaisyUI 5 theme ──────────────────────────────────────────── */
/* Áp dụng qua class .tabulator-daisy trên div wrapper của table */

.tabulator-daisy .tabulator { border:none; border-radius:0; background:transparent; font-size:.8125rem; }
.tabulator-daisy .tabulator-header { background:oklch(var(--b2)); border-bottom:1px solid oklch(var(--b3)); color:oklch(var(--bc)/.65); font-weight:600; font-size:.75rem; text-transform:uppercase; letter-spacing:.04em; }
.tabulator-daisy .tabulator-col { background:transparent; border-right:1px solid oklch(var(--b3)); }
.tabulator-daisy .tabulator-col:last-child { border-right:none; }
.tabulator-daisy .tabulator-col.tabulator-sortable:hover { background:oklch(var(--b3)); }
.tabulator-daisy .tabulator-row { background:oklch(var(--b1)); border-bottom:1px solid oklch(var(--b2)); }
.tabulator-daisy .tabulator-row:hover { background:oklch(var(--b2)/.6); }
.tabulator-daisy .tabulator-row .tabulator-cell { border-right:1px solid oklch(var(--b2)); color:oklch(var(--bc)); padding:.5rem .75rem; }
.tabulator-daisy .tabulator-row .tabulator-cell:last-child { border-right:none; }
.tabulator-daisy .tabulator-footer { background:oklch(var(--b2)/.5); border-top:1px solid oklch(var(--b3)); }
.tabulator-daisy .tabulator-paginator { color:oklch(var(--bc)/.7); }
.tabulator-daisy .tabulator-page { background:transparent; border:1px solid oklch(var(--b3)); color:oklch(var(--bc)); border-radius:.375rem; padding:.2rem .5rem; margin:0 1px; }
.tabulator-daisy .tabulator-page:hover:not([disabled]) { background:oklch(var(--b3)); }
.tabulator-daisy .tabulator-page.active { background:oklch(var(--p)); color:oklch(var(--pc)); border-color:oklch(var(--p)); }
.tabulator-daisy .tabulator-page[disabled] { opacity:.35; }
.tabulator-daisy .tabulator-page-size { background:oklch(var(--b1)); border:1px solid oklch(var(--b3)); color:oklch(var(--bc)); border-radius:.375rem; padding:.2rem .4rem; }
.tabulator-daisy .tabulator-frozen.tabulator-frozen-right { box-shadow:-2px 0 4px oklch(var(--b3)/.5); }
.tabulator-daisy .tabulator-frozen.tabulator-frozen-left  { box-shadow: 2px 0 4px oklch(var(--b3)/.5); }
.tabulator-daisy .tabulator-tableholder::-webkit-scrollbar { width:6px; height:6px; }
.tabulator-daisy .tabulator-tableholder::-webkit-scrollbar-track { background:oklch(var(--b2)); }
.tabulator-daisy .tabulator-tableholder::-webkit-scrollbar-thumb { background:oklch(var(--b3)); border-radius:3px; }
.tabulator-daisy .tabulator-loader { background:oklch(var(--b1)/.7) !important; }
.tabulator-daisy .tabulator-loader-msg { background:oklch(var(--b2)) !important; border:1px solid oklch(var(--b3)) !important; border-radius:.5rem !important; color:oklch(var(--bc)) !important; }

/* ── TomSelect — DaisyUI 5 theme (global — áp dụng cho mọi TomSelect) ─────── */
.ts-wrapper.single .ts-control { background:oklch(var(--b1)); border-color:oklch(var(--b3)); color:oklch(var(--bc)); border-radius:.375rem; min-height:2rem; padding:.25rem .5rem; font-size:.875rem; }
.ts-wrapper.single.focus .ts-control { border-color:oklch(var(--p)); outline:none; box-shadow:0 0 0 2px oklch(var(--p)/.2); }
.ts-dropdown { background:oklch(var(--b1)); border:1px solid oklch(var(--b3)); border-radius:.5rem; box-shadow:0 4px 16px rgba(0,0,0,.15); z-index:9999; }
.ts-dropdown .ts-option { color:oklch(var(--bc)); padding:.4rem .75rem; font-size:.875rem; }
.ts-dropdown .ts-option:hover, .ts-dropdown .ts-option.active { background:oklch(var(--b2)); color:oklch(var(--bc)); }
.ts-dropdown .ts-option.selected { background:oklch(var(--p)/.15); color:oklch(var(--p)); }
.ts-wrapper .clear-button { color:oklch(var(--bc)/.4); }
.ts-wrapper .clear-button:hover { color:oklch(var(--bc)); }
.ts-control input { color:oklch(var(--bc)) !important; }
</style>
```

### Dùng trong Blade view

```html
{{-- Trong @push('styles') --}}
<x-tabulator-theme />

{{-- Table wrapper phải có class "tabulator-daisy" --}}
<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body p-0 overflow-hidden rounded-2xl tabulator-daisy">
        <div id="{module}-table"></div>
    </div>
</div>
```

---

## 11. Blade View Skeleton

```blade
@extends('layouts.backend')
@section('title', 'Danh sách — {Module}')

@section('breadcrumb')
<nav class="breadcrumb-nav">
    <a href="{{ route('backend.dashboard') }}">Trang chủ</a>
    <span class="sep">›</span>
    <span class="current">{Module}</span>
</nav>
@endsection

@section('content')
<div x-data="{camelModule}ListPage">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Danh sách</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Mô tả ngắn về module</p>
        </div>
        <div class="flex items-center gap-2">
            {{-- Column toggle — xem section 16 --}}
            @can('{module}.create')
            <a href="{{ route('backend.{module}.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm mới
            </a>
            @endcan
        </div>
    </div>

    {{-- ── Flash messages ─────────────────────────────────────────────────── --}}
    @foreach(['success','info','error'] as $type)
    @if(session($type))
    <div class="alert alert-{{ $type }} text-sm py-2 px-4 rounded-lg mb-4">{{ session($type) }}</div>
    @endif
    @endforeach

    {{-- ── Stat cards (server-side — 1 query gộp) ────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-4">
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Tổng</div>
            <div class="stat-value text-2xl">{{ number_format($totalAll) }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Hoạt động</div>
            <div class="stat-value text-2xl text-success">{{ number_format($totalActive) }}</div>
        </div>
    </div>

    {{-- ── Filter bar ─────────────────────────────────────────────────────── --}}
    {{-- xem section 14 --}}

    {{-- ── Table ──────────────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-hidden rounded-2xl tabulator-daisy">
            <div id="{module}-table"></div>
        </div>
    </div>

</div>

{{-- ── Delete modal ─────────────────────────────────────────────────────────── --}}
<dialog id="deleteModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xóa</h3>
        <p class="py-3 text-sm text-base-content/70">
            Bạn có chắc muốn xóa
            <strong id="deleteItemLabel" class="text-base-content font-mono"></strong>?
        </p>
        <div class="modal-action mt-4">
            <button id="confirmDeleteBtn" class="btn btn-error btn-sm">Xóa</button>
            <button class="btn btn-ghost btn-sm" onclick="deleteModal.close()">Hủy</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endsection

@push('styles')
<x-tabulator-theme />
@endpush

@push('scripts')
@vite(['resources/js/modules/tabulator.js', 'resources/js/modules/tom-select.js', 'resources/js/modules/flatpickr.js'], 'build/backend')
<script>
// ── Constants injected from PHP (var = accessible before DOMContentLoaded) ───
var CSRF_TOKEN = '{{ csrf_token() }}';
var API_URL    = '{{ route('backend.api.{module}') }}';
var STATUSES   = @json($statuses);  {{-- [{ value: 0, text: 'Nháp' }, ...] --}}

// Permissions: tính server-side — không tính lại ở client
var CAN_EDIT   = {{ auth()->user()->can('{module}.edit')   ? 'true' : 'false' }};
var CAN_DELETE = {{ auth()->user()->can('{module}.delete') ? 'true' : 'false' }};

// ── Helpers ──────────────────────────────────────────────────────────────────
// Bắt buộc: dùng cho mọi user data trong innerHTML formatter
function esc(v) {
    if (v == null) return '';
    return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function isoDate(d) {
    return d.getFullYear() + '-'
        + String(d.getMonth() + 1).padStart(2,'0') + '-'
        + String(d.getDate()).padStart(2,'0');
}
function displayDate(iso) {
    if (!iso) return '';
    var p = iso.split('-'); return p[2] + '/' + p[1] + '/' + p[0];
}
</script>

{{-- Column definitions, Alpine component, delete logic — xem sections 12-15 --}}
@endpush
```

---

## 12. Alpine.js Component — Pattern chuẩn

**Vấn đề `var self = this`:** Pattern này cần thiết khi callback của thư viện bên ngoài (TomSelect, Flatpickr) gọi `this` không phải Alpine proxy. Giải pháp: khai báo `self` **một lần** ở đầu `_setup`, dùng arrow function ở những chỗ khác.

```js
document.addEventListener('alpine:init', function () {

    // ── Lib instances: đặt NGOÀI Alpine.data ─────────────────────────────
    // Lý do: không cần reactive, không muốn Alpine track thay đổi của chúng
    var tableInst    = null;
    var statusTsInst = null;
    var dateFpInst   = null;

    Alpine.data('{camelModule}ListPage', function () {
        return {
            // ── State ────────────────────────────────────────────────────
            filters: {
                search: '',
                status: '',   // string vì TomSelect value là string
                from:   '',
                to:     '',
            },

            // ── Computed getters ─────────────────────────────────────────
            get hasFilters() {
                return !!(this.filters.search || this.filters.status !== '' || this.filters.from);
            },

            get activeChips() {
                var chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.status !== '') {
                    var st = STATUSES.find(function (s) { return String(s.value) === f.status; });
                    chips.push({ key: 'status', label: st ? st.text : f.status });
                }
                if (f.from && f.to)
                    chips.push({ key: 'date', label: displayDate(f.from) + ' — ' + displayDate(f.to) });
                return chips;
            },

            // ── Lifecycle ────────────────────────────────────────────────
            init() {
                this.loadState();
                // DOMContentLoaded đảm bảo Tabulator/TomSelect/Flatpickr đã sẵn sàng
                document.addEventListener('DOMContentLoaded', () => this._setup(), { once: true });
            },

            _setup() {
                // var self cần thiết cho callbacks của lib bên ngoài (không phải arrow fn)
                var self = this;

                // ── Tabulator ────────────────────────────────────────────
                tableInst = new window.Tabulator('#{module}-table', {
                    ajaxURL:    API_URL,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },

                    // ajaxParams là function thường do Tabulator gọi — không phải arrow fn
                    // self được closure-capture từ _setup scope
                    ajaxParams: function () {
                        var p = {}, f = self.filters;
                        if (f.search)      p.search = f.search;
                        if (f.status !== '') p.status = f.status;
                        if (f.from)        p.from   = f.from;
                        if (f.to)          p.to     = f.to;
                        return p;
                    },

                    ajaxResponse: function (_url, _params, res) { return res; },

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'created_at', dir: 'desc' }],

                    layout:           'fitColumns',
                    responsiveLayout: 'collapse',
                    height:           '68vh',

                    locale: 'vi-VN',
                    langs: {
                        'vi-VN': {
                            pagination: {
                                page_size: 'Dòng/trang', page_title: 'Trang',
                                first: '«', last: '»', prev: '‹', next: '›',
                                first_title: 'Trang đầu', last_title: 'Trang cuối',
                                prev_title: 'Trang trước', next_title: 'Trang sau',
                                counter: { showing: '', of: 'trong', rows: 'dòng', pages: 'trang' },
                            },
                        },
                    },

                    columns: COLUMNS, // định nghĩa ở ngoài — xem section 13
                    placeholder: '<div class="py-16 text-center opacity-40">Không có dữ liệu</div>',
                });

                // Expose cho delete handler bên ngoài Alpine
                window._{camelModule}Table = tableInst;

                // ── Status TomSelect ─────────────────────────────────────
                statusTsInst = new window.TomSelect('#filter-status', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả trạng thái...',
                    plugins:        ['clear_button'],
                    options:        STATUSES.map(function (s) { return { value: String(s.value), text: s.text }; }),
                    items:          self.filters.status !== '' ? [self.filters.status] : [],
                    onChange:       function (val) {
                        self.filters.status = val || '';
                        self.saveState();
                        self.refresh();
                    },
                    render: { no_results: function () { return '<div class="p-3 text-sm opacity-50">Không tìm thấy</div>'; } },
                });

                // ── Flatpickr date range ─────────────────────────────────
                dateFpInst = window.initDateRangePicker('#filter-date', {
                    disableMobile: true,
                    onChange: function (dates) {
                        if (dates.length === 2) {
                            self.filters.from = isoDate(dates[0]);
                            self.filters.to   = isoDate(dates[1]);
                        } else {
                            self.filters.from = '';
                            self.filters.to   = '';
                        }
                        self.saveState();
                        self.refresh();
                    },
                });

                if (self.filters.from && self.filters.to) {
                    dateFpInst.setDate([self.filters.from, self.filters.to], false);
                }
            },

            // ── URL state persistence ────────────────────────────────────
            loadState() {
                var p = new URLSearchParams(location.search);
                if (p.has('q'))    this.filters.search = p.get('q');
                if (p.has('st'))   this.filters.status = p.get('st');
                if (p.has('from')) this.filters.from   = p.get('from');
                if (p.has('to'))   this.filters.to     = p.get('to');
            },

            saveState() {
                var p = new URLSearchParams(), f = this.filters;
                if (f.search)      p.set('q',    f.search);
                if (f.status !== '') p.set('st', f.status);
                if (f.from)        p.set('from', f.from);
                if (f.to)          p.set('to',   f.to);
                var qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            // ── Actions ─────────────────────────────────────────────────
            refresh() { if (tableInst) tableInst.replaceData(); },

            // Arrow function: this = Alpine proxy — không cần self
            onFilterChange() { this.saveState(); this.refresh(); },

            clearSearch() {
                this.filters.search = '';
                this.saveState();
                this.refresh();
            },

            clearDate() {
                this.filters.from = '';
                this.filters.to   = '';
                if (dateFpInst) dateFpInst.clear(false);
                this.saveState();
                this.refresh();
            },

            removeChip(key) {
                if (key === 'search') this.filters.search = '';
                if (key === 'status') { this.filters.status = ''; if (statusTsInst) statusTsInst.clear(true); }
                if (key === 'date')   { this.clearDate(); return; }
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', status: '', from: '', to: '' };
                if (statusTsInst) statusTsInst.clear(true);
                if (dateFpInst)   dateFpInst.clear(false);
                history.replaceState(null, '', location.pathname);
                this.refresh();
            },
        };
    });
});
```

**Quy tắc `this` vs `self`:**
| Ngữ cảnh | Dùng |
|----------|------|
| Method của Alpine object (shorthand `methodName() {}`) | `this` — Alpine proxy |
| Callback truyền vào TomSelect `onChange`, Flatpickr `onChange` | `self` — closure capture |
| `ajaxParams: function() {}` (Tabulator gọi) | `self` — closure capture |
| `tableBuilderCallback: () => {}` (arrow fn) | `this` — inherit từ outer |

---

## 13. Tabulator Config

### Column definitions — tách riêng khỏi Alpine component

```js
// Định nghĩa COLUMNS trước document.addEventListener('alpine:init')
// để có thể reference trong Tabulator config và các utility khác

var COLUMNS = [
    {
        title: '#', field: 'id', width: 75, sorter: 'number', hozAlign: 'center',
        formatter: function (cell) {
            return '<span class="font-mono text-xs text-base-content/40">' + esc(cell.getValue()) + '</span>';
        },
    },
    {
        title: 'Tên', field: 'name', minWidth: 200, sorter: 'string',
        frozen: true, // Cột đầu tiên: frozen left
        formatter: function (cell) {
            var d = cell.getRow().getData();
            return '<a href="' + esc(d.show_url) + '" class="font-semibold hover:text-primary">'
                + esc(d.name) + '</a>';
        },
    },
    {
        title: 'Trạng thái', field: 'status_value', width: 130, hozAlign: 'center', sorter: 'number',
        formatter: function (cell) {
            var d = cell.getRow().getData();
            return '<span class="badge badge-sm badge-soft ' + esc(d.status_badge) + '">'
                + esc(d.status_label) + '</span>';
        },
    },
    {
        title: 'Ngày tạo', field: 'created_at', width: 120, sorter: 'string',
    },
    {
        title: 'Thao tác', field: 'show_url', width: 110, hozAlign: 'center',
        headerSort: false,
        frozen: true, // Cột action: frozen right
        formatter: function (cell) {
            var d = cell.getRow().getData();
            var html = '<div class="flex items-center justify-center gap-1">';

            if (CAN_EDIT) {
                html += '<a href="' + esc(d.edit_url) + '"'
                    + ' class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-info"'
                    + ' title="Sửa">'
                    + '<!-- edit icon --></a>';
            }

            if (CAN_DELETE && d.can_delete) {
                html += '<button'
                    + ' class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error"'
                    + ' data-url="' + esc(d.delete_url) + '"'
                    + ' data-label="' + esc(d.name) + '"'
                    + ' onclick="window.deleteConfirm(this.dataset.url, this.dataset.label)"'
                    + ' title="Xóa">'
                    + '<!-- trash icon --></button>';
            }

            html += '</div>';
            return html;
        },
    },
];
```

**Quy ước đặt tên cột:**
| Trường | Field name | Ghi chú |
|--------|------------|---------|
| ID | `id` | sorter: 'number' |
| Enum value | `status_value` | dùng để sort; display lấy từ `status_label`/`status_badge` |
| Enum label | `status_label` | chỉ dùng trong formatter, không phải field sort |
| URL | `show_url`, `edit_url`, `delete_url` | không sort |
| Date | `created_at`, `submitted_at` | sorter: 'string' (đã format `dd/MM/yyyy HH:mm`) |

---

## 14. Filter Widgets

### Filter bar HTML

```html
<div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
    <div class="card-body py-3 px-4 space-y-3">

        <div class="flex flex-wrap gap-3 items-end">

            {{-- Text search với debounce --}}
            <div class="form-control flex-1 min-w-52">
                <label class="label py-0.5">
                    <span class="label-text text-xs font-medium">Tìm kiếm</span>
                    <span class="label-text-alt text-xs text-base-content/40">Theo prefix</span>
                </label>
                <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                    <!-- search icon -->
                    <input id="filter-search" type="text"
                           x-model="filters.search"
                           @input.debounce.350ms="onFilterChange()"
                           placeholder="Nhập từ khóa..."
                           class="grow bg-transparent outline-none text-sm"/>
                    <button x-show="filters.search" @click="clearSearch()"
                            class="text-base-content/30 hover:text-base-content transition-colors">
                        <!-- X icon -->
                    </button>
                </div>
            </div>

            {{-- Status TomSelect --}}
            <div class="form-control w-48">
                <label class="label py-0.5">
                    <span class="label-text text-xs font-medium">Trạng thái</span>
                </label>
                <select id="filter-status" class="select select-sm select-bordered w-full"></select>
            </div>

            {{-- Date range --}}
            <div class="form-control w-64">
                <label class="label py-0.5">
                    <span class="label-text text-xs font-medium">Ngày tạo</span>
                </label>
                <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                    <!-- calendar icon -->
                    <input id="filter-date" type="text" readonly
                           placeholder="Chọn khoảng ngày..."
                           class="grow bg-transparent outline-none text-sm cursor-pointer"/>
                    <button x-show="filters.from" @click="clearDate()"
                            class="text-base-content/30 hover:text-base-content transition-colors">
                        <!-- X icon -->
                    </button>
                </div>
            </div>

            {{-- Reset --}}
            <div class="form-control ml-auto">
                <label class="label py-0.5 invisible"><span>.</span></label>
                <button @click="reset()" x-show="hasFilters" x-transition
                        class="btn btn-ghost btn-sm gap-1.5 text-error">
                    <!-- reset icon --> Đặt lại
                </button>
            </div>

        </div>

        {{-- Active filter chips --}}
        <div x-show="activeChips.length > 0" x-transition
             class="flex flex-wrap gap-2 pt-1 border-t border-base-200">
            <span class="text-xs text-base-content/40 self-center">Đang lọc:</span>
            <template x-for="chip in activeChips" :key="chip.key">
                <span class="badge badge-sm gap-1 cursor-pointer hover:badge-error transition-colors"
                      @click="removeChip(chip.key)">
                    <span x-text="chip.label"></span>
                    <!-- X icon nhỏ -->
                </span>
            </template>
        </div>

    </div>
</div>
```

---

## 15. AJAX Actions (Delete & Inline)

### Delete với AJAX (không reload page)

```js
var pendingDeleteUrl = null;

// Expose toàn cục để formatter trong Tabulator có thể gọi
window.deleteConfirm = function (url, label) {
    pendingDeleteUrl = url;
    document.getElementById('deleteItemLabel').textContent = label;
    document.getElementById('deleteModal').showModal();
};

document.getElementById('confirmDeleteBtn').addEventListener('click', async function () {
    if (!pendingDeleteUrl) return;

    var btn = this;
    btn.disabled    = true;
    btn.textContent = 'Đang xóa...';

    try {
        var res = await fetch(pendingDeleteUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN':     CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept':           'application/json',
                'Content-Type':     'application/x-www-form-urlencoded',
            },
            body: '_method=DELETE',
        });

        if (res.ok) {
            document.getElementById('deleteModal').close();
            // Refresh bảng mà KHÔNG reload page
            if (window._{camelModule}Table) window._{camelModule}Table.replaceData();
        } else {
            var data = await res.json().catch(() => ({}));
            // Hiển thị lỗi từ server (403, 422, v.v.)
            alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
        }
    } catch (e) {
        console.error('[{module}] delete failed', e);
        alert('Lỗi kết nối. Vui lòng thử lại.');
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Xóa';
        pendingDeleteUrl = null;
    }
});
```

### Controller destroy — hỗ trợ cả redirect và JSON

```php
public function destroy(Request $request, {Model} $item): RedirectResponse|JsonResponse
{
    $this->authorize('delete', $item);

    $item->delete();

    // Tabulator gọi với Accept: application/json → trả JSON
    if ($request->expectsJson()) {
        return response()->json(['message' => 'Đã xóa thành công.']);
    }

    // Form submit thông thường → redirect với flash
    return redirect()
        ->route('backend.{module}.index')
        ->with('success', 'Đã xóa thành công.');
}
```

---

## 16. Advanced UI

### A) Column visibility toggle (localStorage)

```js
// Thêm vào Alpine.data:
hiddenCols: [],
LS_KEY: '{module}-hidden-cols',

get toggleableCols() {
    // Loại trừ cột bắt buộc (id, action)
    return COLUMNS
        .filter(c => c.field !== 'id' && !c.headerSort === false)
        .map(c => ({ field: c.field, title: c.title }));
},

// Sau khi tableInst được tạo, trong _setup():
// Restore từ localStorage
try { this.hiddenCols = JSON.parse(localStorage.getItem(LS_KEY) || '[]'); } catch(e) {}
this.hiddenCols.forEach(function (field) { tableInst.hideColumn(field); });

toggleCol(field) {
    if (this.hiddenCols.includes(field)) {
        this.hiddenCols = this.hiddenCols.filter(f => f !== field);
        if (tableInst) tableInst.showColumn(field);
    } else {
        this.hiddenCols.push(field);
        if (tableInst) tableInst.hideColumn(field);
    }
    try { localStorage.setItem(LS_KEY, JSON.stringify(this.hiddenCols)); } catch(e) {}
},
```

```html
{{-- Column toggle button trong header --}}
<div class="dropdown dropdown-end">
    <label tabindex="0" class="btn btn-ghost btn-sm gap-1.5"><!-- columns icon --> Cột</label>
    <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box shadow-lg border border-base-200 w-48 z-50 p-2">
        <template x-for="col in toggleableCols" :key="col.field">
            <li>
                <label class="flex items-center gap-2 cursor-pointer py-1.5 px-2 rounded-lg hover:bg-base-200">
                    <input type="checkbox" class="checkbox checkbox-xs"
                           :checked="!hiddenCols.includes(col.field)"
                           @change="toggleCol(col.field)"/>
                    <span x-text="col.title" class="text-sm"></span>
                </label>
            </li>
        </template>
    </ul>
</div>
```

### B) Date presets (Today / This week / This month / This year)

```js
// Helper function
function presetRange(preset) {
    var now = new Date(), y = now.getFullYear(), m = now.getMonth(), d = now.getDate();
    if (preset === 'today')  return [new Date(y, m, d), new Date(y, m, d)];
    if (preset === 'week') {
        var dow = now.getDay() === 0 ? 6 : now.getDay() - 1; // Monday = 0
        return [new Date(y, m, d - dow), new Date(y, m, d - dow + 6)];
    }
    if (preset === 'month') return [new Date(y, m, 1), new Date(y, m + 1, 0)];
    if (preset === 'year')  return [new Date(y, 0, 1), new Date(y, 11, 31)];
    return [null, null];
}

// Thêm vào Alpine data:
activeDatePreset: '',
settingPreset: false,  // flag ngăn Flatpickr onChange trigger khi set preset

setDatePreset(preset) {
    var range = presetRange(preset);
    if (!range[0]) return;
    this.settingPreset   = true;
    this.activeDatePreset  = preset;
    this.filters.from = isoDate(range[0]);
    this.filters.to   = isoDate(range[1]);
    if (dateFpInst) dateFpInst.setDate([range[0], range[1]], false);
    this.settingPreset = false;
    this.saveState();
    this.refresh();
},
```

```html
{{-- Preset buttons --}}
<div class="flex gap-1">
    <button @click="setDatePreset('today')"
            :class="activeDatePreset === 'today' ? 'btn-primary' : 'btn-ghost'"
            class="btn btn-xs">Hôm nay</button>
    <button @click="setDatePreset('week')"
            :class="activeDatePreset === 'week' ? 'btn-primary' : 'btn-ghost'"
            class="btn btn-xs">Tuần này</button>
    <button @click="setDatePreset('month')"
            :class="activeDatePreset === 'month' ? 'btn-primary' : 'btn-ghost'"
            class="btn btn-xs">Tháng này</button>
    <button @click="setDatePreset('year')"
            :class="activeDatePreset === 'year' ? 'btn-primary' : 'btn-ghost'"
            class="btn btn-xs">Năm nay</button>
</div>
```

### C) Chained dropdowns (Province → Ward)

Pattern từ Organization module: khi một filter phụ thuộc vào filter cha.

```js
// Trong closure của alpine:init:
var wardsCache = {}; // { province_code: [{value, text}] }

// Trong Alpine data:
onParentChange(parentCode) {
    var self = this;
    this.filters.child_code = '';
    if (childTsInst) { childTsInst.clear(true); childTsInst.clearOptions(); childTsInst.disable(); }
    this.saveState();
    this.refresh(); // refresh ngay — không chờ child load
    if (parentCode) this._loadChildren(parentCode, null);
},

_loadChildren(parentCode, pendingChild) {
    var self = this;
    if (wardsCache[parentCode]) {
        self._applyChildren(wardsCache[parentCode], pendingChild);
        return;
    }
    // Hiển thị loading state trong TomSelect
    childTsInst.settings.placeholder = 'Đang tải...';
    if (childTsInst.control_input) childTsInst.control_input.setAttribute('placeholder', 'Đang tải...');

    fetch('/api/parent/' + parentCode + '/children')
        .then(r => r.json())
        .then(data => { wardsCache[parentCode] = data; self._applyChildren(data, pendingChild); })
        .catch(() => { /* hiện lỗi trong placeholder */ });
},

_applyChildren(data, pendingChild) {
    if (!childTsInst) return;
    data.forEach(item => childTsInst.addOption({ value: item.code, text: item.name }));
    childTsInst.enable();
    if (pendingChild) {
        childTsInst.setValue(pendingChild, true); // silent — không trigger onChange
        this.filters.child_code = pendingChild;
    }
    this.refresh();
},
```

---

## 17. Error Handling & Logging

### API Controller — lỗi predictable vs lỗi unexpected

```php
public function index(Request $request): JsonResponse
{
    // Validation failure: Laravel tự trả 422 JSON với errors[]
    // Authorization failure: Laravel tự trả 403 JSON
    // Không cần try/catch cho hai loại này.

    try {
        $paginator = $query->paginate(...);
    } catch (\Exception $e) {
        // Log lỗi unexpected (DB timeout, query error...)
        \Log::error('[{module}] listing query failed', [
            'user_id'  => auth()->id(),
            'filters'  => $request->only(['search', 'status', 'from', 'to']),
            'message'  => $e->getMessage(),
        ]);

        return response()->json(
            ['message' => 'Không thể tải dữ liệu. Vui lòng thử lại.'],
            500
        );
    }

    return response()->json([...]);
}
```

### Custom Exception — tắt ERROR log cho expected exceptions

```php
// Khi exception là business rule (không phải lỗi hệ thống)
// thêm report(): false để không log vào error log

class ItemNotActiveException extends \RuntimeException
{
    public function __construct(mixed $status = null)
    {
        parent::__construct('Item chưa hoạt động.');
    }

    // Trả về false → Laravel không log exception này ở level ERROR
    public function report(): bool
    {
        return false;
    }

    // HTTP response khi exception không được catch
    public function render(): \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
    {
        return response()->json(['message' => $this->getMessage()], 422);
    }
}
```

### Frontend error states

```js
// Tabulator không có built-in error display — thêm ajaxError callback
tableInst = new window.Tabulator('#{module}-table', {
    // ...
    ajaxError: function (error) {
        console.error('[{module}] API error', error);
        // Có thể show toast ở đây
    },
});
```

### Logging có context — không log raw request

```php
// Tốt: log đủ để debug, không log PII
\Log::error('[{module}] export failed', [
    'survey_id' => $survey->id,
    'user_id'   => auth()->id(),
    'error'     => $e->getMessage(),
]);

// Tránh: log toàn bộ $request (có thể chứa password, token)
\Log::error('Request failed', $request->all()); // KHÔNG làm thế này
```

---

## 18. Testing

### Feature test cho API endpoint

```php
<?php

namespace Modules\{Module}\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\{Module}\Models\{Model};
use Tests\TestCase;

class {Module}BackendApiTest extends TestCase
{
    use RefreshDatabase;

    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        // Tạo user có quyền — dùng seeder hoặc factory
        $this->admin = \App\Models\User::factory()->create();
        $this->admin->givePermissionTo('{module}.view');
    }

    // ── Auth ─────────────────────────────────────────────────────────────────

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson(route('backend.api.{module}'))
             ->assertStatus(401);
    }

    public function test_unauthorized_returns_403(): void
    {
        $user = \App\Models\User::factory()->create(); // không có quyền
        $this->actingAs($user)
             ->getJson(route('backend.api.{module}'))
             ->assertStatus(403);
    }

    // ── Response shape ───────────────────────────────────────────────────────

    public function test_returns_paginated_json(): void
    {
        {Model}::factory()->count(5)->create();

        $this->actingAs($this->admin)
             ->getJson(route('backend.api.{module}'))
             ->assertOk()
             ->assertJsonStructure([
                 'data'      => [['id', 'name', 'status_value', 'status_label', 'created_at']],
                 'last_page',
                 'total',
             ]);
    }

    // ── Validation ───────────────────────────────────────────────────────────

    public function test_invalid_date_format_returns_422(): void
    {
        $this->actingAs($this->admin)
             ->getJson(route('backend.api.{module}') . '?from=not-a-date')
             ->assertStatus(422)
             ->assertJsonValidationErrors(['from']);
    }

    public function test_to_before_from_returns_422(): void
    {
        $this->actingAs($this->admin)
             ->getJson(route('backend.api.{module}') . '?from=2025-12-01&to=2025-01-01')
             ->assertStatus(422);
    }

    // ── Sort whitelist (SQL injection prevention) ────────────────────────────

    public function test_sort_by_unknown_field_falls_back_to_default(): void
    {
        // Không crash, không SQL inject — silently fallback
        $this->actingAs($this->admin)
             ->getJson(route('backend.api.{module}') . '?sort[0][field]=malicious;DROP TABLE')
             ->assertOk();
    }

    // ── Filters ──────────────────────────────────────────────────────────────

    public function test_status_filter_returns_matching_rows(): void
    {
        {Model}::factory()->create(['status' => 1]);
        {Model}::factory()->create(['status' => 0]);

        $res = $this->actingAs($this->admin)
                    ->getJson(route('backend.api.{module}') . '?status=1')
                    ->assertOk()
                    ->json();

        $this->assertCount(1, $res['data']);
        $this->assertEquals(1, $res['data'][0]['status_value']);
    }

    public function test_date_filter_returns_matching_rows(): void
    {
        {Model}::factory()->create(['created_at' => '2025-03-15 10:00:00']);
        {Model}::factory()->create(['created_at' => '2024-01-01 10:00:00']);

        $res = $this->actingAs($this->admin)
                    ->getJson(route('backend.api.{module}') . '?from=2025-01-01&to=2025-12-31')
                    ->assertOk()
                    ->json();

        $this->assertCount(1, $res['data']);
    }

    // ── Pagination ───────────────────────────────────────────────────────────

    public function test_pagination_works(): void
    {
        {Model}::factory()->count(30)->create();

        $res = $this->actingAs($this->admin)
                    ->getJson(route('backend.api.{module}') . '?page=2&size=10')
                    ->assertOk()
                    ->json();

        $this->assertCount(10, $res['data']);
        $this->assertEquals(3, $res['last_page']);
    }

    // ── Delete endpoint ──────────────────────────────────────────────────────

    public function test_delete_returns_json_when_xhr(): void
    {
        $item = {Model}::factory()->create();

        $this->actingAs($this->admin)
             ->withHeaders(['Accept' => 'application/json'])
             ->delete(route('backend.{module}.destroy', $item))
             ->assertOk()
             ->assertJson(['message' => 'Đã xóa thành công.']);

        $this->assertSoftDeleted($item);
    }
}
```

### Unit test cho Query Handler (biến thể CQRS)

```php
public function test_handler_applies_date_range(): void
{
    {Model}::factory()->create(['created_at' => '2025-06-01']);
    {Model}::factory()->create(['created_at' => '2024-01-01']);

    $result = (new List{Models}Handler())->handle(new List{Models}Query(
        dateFrom: '2025-01-01',
        dateTo:   '2025-12-31',
    ));

    $this->assertCount(1, $result->items());
}

public function test_handler_sort_field_whitelist(): void
{
    // Handler không throw khi nhận field không hợp lệ — fallback về default
    $result = (new List{Models}Handler())->handle(new List{Models}Query(
        sortField: 'ILLEGAL_FIELD; DROP TABLE',
    ));

    $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
}
```

---

## 19. Bảo mật — Checklist

### SQL Injection

- [ ] Sort field qua `SORTABLE` whitelist (map hoặc `in_array`) — không `orderBy(user_input)` bao giờ
- [ ] Sort direction: chỉ accept `'asc'`/`'desc'` — hardcode logic
- [ ] LIKE: Eloquent tự escape — không concat thủ công vào query string
- [ ] Không dùng `DB::statement()` hay `whereRaw()` với user input chưa bind

### Authorization

- [ ] `$this->authorize()` ở **đầu** mỗi action — cả Web và API controller
- [ ] Ownership check khi resource thuộc parent: `if ($item->parent_id !== $parent->id) abort(404)`
- [ ] Quyền `CAN_EDIT`, `CAN_DELETE` tính server-side inject vào JS — không tính ở client
- [ ] `can_delete` field trong Resource cho điều kiện business logic

### XSS

- [ ] `esc()` cho **mọi** user data trong Tabulator `formatter` dùng `innerHTML`
- [ ] Không `cell.getElement().innerHTML = userValue` trực tiếp
- [ ] Blade escaping tự động cho `{{ $var }}` — chỉ cần cẩn thận khi dùng `{!! !!}`

### CSRF

- [ ] `CSRF_TOKEN` inject từ PHP vào JS `var` (không `const` để accessible sớm hơn)
- [ ] Mọi mutating AJAX request gửi header `X-CSRF-TOKEN: CSRF_TOKEN`
- [ ] DELETE qua `body: '_method=DELETE'` + `method: 'POST'`

### Input Validation

- [ ] `$request->validate([...])` cho **tất cả** query params trong API controller
- [ ] Date: `'date_format:Y-m-d'` — ngăn invalid string vào query
- [ ] String max: `'max:200'` cho search fields
- [ ] Integer range: `'min:1', 'max:100'` cho page/size
- [ ] Enum in: `'in:0,1,2'` cho status filter

### Session & API

- [ ] API routes (`backend/api/*`) không dùng `session()->flash()` hoặc `redirect()`
- [ ] Flash messages chỉ từ Web controller redirect

---

## 20. Hiệu suất — Checklist

### Query — 500k rows

- [ ] `select(['col1', 'col2', ...])` tường minh — không `select('*')` khi có cột nhạy cảm/lớn
- [ ] **Không `whereDate()`** — thay bằng `>= 'date 00:00:00'` / `<= 'date 23:59:59'`
- [ ] LIKE prefix: `'term%'` dùng index; `'%term%'` full scan — chỉ dùng `'%term%'` khi thực sự cần full-text
- [ ] Gộp COUNT: `SUM(CASE WHEN status = ? THEN 1 ELSE 0 END)` thay vì nhiều `.count()` riêng
- [ ] Secondary sort by `id`: `->when($sortField !== 'id', fn ($q) => $q->orderBy('id', $dir))` — stable pagination
- [ ] Không load toàn bộ collection — luôn paginate

### Index

- [ ] Composite index: equality columns → range/sort columns
- [ ] `deleted_at` trong index khi dùng SoftDeletes
- [ ] Verify bằng `EXPLAIN SELECT` trên dữ liệu production-size
- [ ] Không index column boolean hay status (cardinality thấp) làm leading column

### Cache

- [ ] **Không cache listing query** — cache thêm latency mà index tốt đã đủ nhanh
- [ ] Cache chỉ dùng cho data cực expensive: chart aggregation, full-text search index
- [ ] Nếu bắt buộc dùng Redis cache: wrap trong `try/catch` — Redis down không crash request

### Frontend

- [ ] Debounce 350ms trên text input
- [ ] `paginationSize: 25` mặc định — không 200+
- [ ] Heavy libs (Tabulator, TomSelect, Flatpickr) qua backend Vite bundle — không trong core bundle
- [ ] `replaceData()` sau delete/inline edit — không reload page
- [ ] `wardsCache = {}` hoặc tương tự cho chained dropdown — không fetch lại mỗi lần chọn parent

---

## 21. Checklist triển khai từng bước

### Phase 1 — Backend

- [ ] Thêm `label()`, `badgeClass()` vào Status Enum
- [ ] Thêm scope isolation vào Model (`scopeForOrganization`, `scopeFor*`)
- [ ] Thêm Eloquent accessor cho encoded fields nếu cần (BINARY IP, encrypted field)
- [ ] Chọn biến thể query: Direct (section 4A) hay CQRS (section 4B)
- [ ] Tạo `{Model}ListResource` với đủ fields, URLs, can_delete
- [ ] Tạo `{Module}BackendApiController::index()` với sort whitelist, validate, paginate
- [ ] Update `{Module}Controller::index()`: gộp counts, truyền `$statuses` vào view
- [ ] Update `{Module}Controller::destroy()`: trả JSON khi `expectsJson()`
- [ ] Thêm 2 routes vào `routes/web.php` (web CRUD + backend API)
- [ ] Tạo migration index + chạy `php artisan migrate`
- [ ] `php artisan route:list --name=backend.api.{module}` — verify route tồn tại

### Phase 2 — Frontend

- [ ] Tạo `resources/views/components/tabulator-theme.blade.php` (chỉ lần đầu, dùng chung)
- [ ] Tạo `resources/views/index.blade.php` từ skeleton (section 11)
- [ ] Thêm class `tabulator-daisy` vào div wrapper của table
- [ ] Inject constants PHP → JS (`CSRF_TOKEN`, `API_URL`, `STATUSES`, `CAN_*`)
- [ ] Thêm helpers `esc()`, `isoDate()`, `displayDate()`
- [ ] Định nghĩa `COLUMNS` array với đúng field names và formatters
- [ ] Viết Alpine.js component theo pattern section 12
- [ ] TomSelect cho status filter
- [ ] Flatpickr date range, restore từ URL state
- [ ] Active chips + reset button
- [ ] Delete modal + `window.deleteConfirm()` + confirmDeleteBtn handler

### Phase 3 — Optional enhancements

- [ ] Column toggle (section 16A) nếu có nhiều cột
- [ ] Date presets (section 16B) nếu UX cần
- [ ] Chained dropdown (section 16C) nếu có filter phụ thuộc

### Phase 4 — Verify

- [ ] Tabulator load từ API đúng — F12 Network tab kiểm tra response
- [ ] Sort từng cột hoạt động (remote sort gửi `sort[0][field]` và `sort[0][dir]`)
- [ ] Filter text + status + date hoạt động; URL state được lưu khi reload
- [ ] Delete: confirm modal → AJAX → bảng refresh không reload page
- [ ] `php artisan test --filter={Module}` pass
- [ ] Dark mode: kiểm tra bảng render đúng màu với DaisyUI dark theme

---

*v2.0 — Đúc kết từ `Modules/Survey` + `Modules/Organization`. Cập nhật khi có pattern mới được chuẩn hóa.*
