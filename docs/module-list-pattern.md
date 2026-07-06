# Module List Pattern — Tài liệu kỹ thuật tổng quan

> **Phạm vi áp dụng:** Hệ thống `/var/www/html/minhan`  
> **Nguồn tham chiếu:** Module `Organization` — đã triển khai và kiểm chứng hoàn chỉnh  
> **Mục đích:** Template chuẩn để nhân rộng cho các module khác (CRM, Tasks, SOP, v.v.)

---

## 1. Kiến trúc tổng quan

Mỗi tính năng **danh sách + lọc** trong hệ thống tuân theo stack sau:

```
[Browser] ─── Tabulator AJAX ──► [OrganizationApiController]
                                         │
                                         ▼
                                 [ListXxxQuery]  ← DTO chứa filter params
                                         │
                                         ▼
                                 [ListXxxHandler] ← Query builder + pagination
                                         │
                                         ▼
                                 [Eloquent Model.withoutTenant()]
                                         │
                                         ▼
                                 [JSON Response] ──► Tabulator renders rows
```

**Nguyên tắc cốt lõi:**
- Toàn bộ filter / sort / pagination xử lý **server-side** — không có client-side filter
- CQRS-lite: Query tách khỏi Command, Handler không biết gì về HTTP
- Alpine.js chỉ quản lý UI state (filters, chips, hidden cols) — không chứa business logic
- Tabulator là "view layer" thuần túy: nhận JSON, render rows, gửi AJAX params

---

## 2. Backend — CQRS-lite

### 2.1 Query DTO

File: `Modules/Xxx/app/Queries/ListXxxQuery.php`

```php
<?php

namespace Modules\Xxx\Queries;

use App\Shared\Contracts\QueryInterface;

class ListXxxQuery implements QueryInterface
{
    public function __construct(
        // Pagination
        public readonly int     $page         = 1,
        public readonly int     $perPage      = 25,

        // Sort
        public readonly string  $sortField    = 'created_at',
        public readonly string  $sortDir      = 'desc',

        // Text search (OR across multiple cols)
        public readonly ?string $search       = null,

        // Exact filters
        public readonly ?string $provinceCode = null,
        public readonly ?string $wardCode     = null,
        public readonly ?string $status       = null,

        // Date range (ISO format YYYY-MM-DD)
        public readonly ?string $dateFrom     = null,
        public readonly ?string $dateTo       = null,
    ) {}
}
```

**Quy tắc:**
- Tất cả properties là `readonly` — DTO là immutable sau khi tạo
- Nullable với default `null` cho filter params — `''` và `null` đều được coi là "không lọc"
- Không inject Request, không biết về HTTP
- Tên field dùng `camelCase` nhất quán với PHP convention

### 2.2 Query Handler

File: `Modules/Xxx/app/Queries/ListXxxHandler.php`

```php
<?php

namespace Modules\Xxx\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Xxx\Models\Xxx;

class ListXxxHandler implements QueryHandlerInterface
{
    // Whitelist sort fields — PHẢI explicit, không bao giờ pass user input thẳng vào ORDER BY
    private const SORTABLE = [
        'name', 'status', 'created_at',
        // Thêm 'related_name' nếu cần join sort (xem phần 2.3)
    ];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListXxxQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField
            : 'created_at';

        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = Xxx::withoutTenant()
            ->select('table.*')           // explicit select — bắt buộc để tránh column collision khi leftJoin được áp dụng
            ->withCount('relatedModel')   // withCount dùng addSelect() → append vào select hiện tại, không overwrite
            ->with(['province:province_code,name']); // Chỉ select columns cần thiết

        // ── Text search (OR) ────────────────────────────────────────
        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->where('table.name',     'like', $term)
                    ->orWhere('table.email',   'like', $term)
                    ->orWhere('table.tax_code', 'like', $term);
            });
        }

        // ── Exact filters ───────────────────────────────────────────
        if ($query->provinceCode !== null && $query->provinceCode !== '') {
            $q->where('table.province_code', $query->provinceCode);
        }

        if ($query->status !== null && $query->status !== '') {
            $q->where('table.status', $query->status);
        }

        // ── Date range ──────────────────────────────────────────────
        if ($query->dateFrom !== null && $query->dateFrom !== '') {
            $q->whereDate('table.created_at', '>=', $query->dateFrom);
        }

        if ($query->dateTo !== null && $query->dateTo !== '') {
            $q->whereDate('table.created_at', '<=', $query->dateTo);
        }

        // ── Sort ────────────────────────────────────────────────────
        match ($sortField) {
            'related_count' => $q->orderBy('related_count', $sortDir),
            // LEFT JOIN để sort theo tên thực (không dùng proxy code)
            'province_name' => $q->leftJoin('provinces as prov_sort', 'table.province_code', '=', 'prov_sort.province_code')
                                  ->orderBy('prov_sort.name', $sortDir),
            default         => $q->orderBy('table.' . $sortField, $sortDir),
        };

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
```

**Lưu ý quan trọng:**
- `withoutTenant()` — **bắt buộc** cho admin listing để bypass `OrganizationScope`
- `->select('table.*')` — **bắt buộc ngay sau `withoutTenant()`**: khi sort `province_name` thêm `leftJoin`, nếu không có explicit select thì `paginate(['*'])` sẽ kéo cả columns của bảng join (kể cả `id`), gây model hydration sai
- `withCount` dùng `addSelect()` → append subquery vào select list hiện tại, không xung đột với `->select('table.*')`
- Sort whitelist ngăn SQL injection qua sort field
- `leftJoin` cho sort theo related table name: dùng alias (`prov_sort`) tránh column conflict với `with()` eager load
- `with(['province:province_code,name'])` — eager load với **column selection** để tránh N+1 và không kéo dư dữ liệu

### 2.3 Pattern sort qua related table

Khi cần sort theo tên của bảng liên kết (không phải foreign key code):

```php
// ❌ Sai: sort theo code, không phải tên hiển thị
'province_name' => $q->orderBy('organizations.province_code', $sortDir),

// ❌ Sai: thiếu ->select('table.*') ở đầu query → leftJoin làm paginate(['*']) kéo cả
//         columns của provinces (gồm 'id'), ghi đè organizations.id khi hydrate model
'province_name' => $q->leftJoin('provinces as prov_sort', 'table.province_code', '=', 'prov_sort.province_code')
                      ->orderBy('prov_sort.name', $sortDir),

// ✓ Đúng: ->select('table.*') đặt ở đầu query (trước withCount), leftJoin chỉ dùng để sort
'province_name' => $q->leftJoin('provinces as prov_sort', 'table.province_code', '=', 'prov_sort.province_code')
                      ->orderBy('prov_sort.name', $sortDir),
// ^ an toàn vì query đã có ->select('table.*') từ bước khởi tạo $q
```

`leftJoin` không ảnh hưởng `with()` — eager load chạy query riêng sau khi paginate.

---

## 3. Backend — API Controller + Resource

### 3.1 Resource class

File: `Modules/Xxx/app/Http/Resources/XxxListResource.php`

```php
<?php

namespace Modules\Xxx\Http\Resources;

use App\Shared\Tenancy\Enums\XxxStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class XxxListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status;

        return [
            'id'           => $this->id,
            'name'         => $this->name,
            // Enum: lấy cả value (cho filter/class) và label (cho display)
            'status'       => $status instanceof XxxStatus ? $status->value : $status,
            'status_label' => $status instanceof XxxStatus ? $status->label() : $status,
            'created_at'   => $this->created_at?->format('d/m/Y'),
            // Pre-build URLs server-side — không để client tự xây URL
            'show_url'     => route('backend.xxx.show', $this->resource),
            'edit_url'     => route('backend.xxx.edit', $this->resource),
            'delete_url'   => route('backend.xxx.destroy', $this->resource),
        ];
    }
}
```

**Lý do dùng Resource thay cho `formatRow()` closure:**
- Reusable: cùng một Resource có thể dùng cho `show`, `store`, `update` response
- Testable: `XxxListResource::make($model)->toArray($request)` test trực tiếp
- Convention: Laravel API Resource là standard, dễ onboard developer mới
- Tách biệt: controller chỉ điều phối, không biết về serialization logic

### 3.2 Controller

File: `Modules/Xxx/app/Http/Controllers/Api/XxxApiController.php`

```php
<?php

namespace Modules\Xxx\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Xxx\Http\Resources\XxxListResource;
use Modules\Xxx\Queries\ListXxxHandler;
use Modules\Xxx\Queries\ListXxxQuery;

class XxxApiController extends Controller
{
    public function index(Request $request, ListXxxHandler $handler): JsonResponse
    {
        // Policy check — dùng 'viewAny' cho list endpoint
        $this->authorize('viewAny', \Modules\Xxx\Models\Xxx::class);

        // Tabulator gửi sort dạng sort[0][field] / sort[0][dir]
        $sort      = $request->input('sort', []);
        $sortField = $sort[0]['field'] ?? 'created_at';
        $sortDir   = $sort[0]['dir']   ?? 'desc';

        $query = new ListXxxQuery(
            page:         max(1, $request->integer('page', 1)),
            perPage:      min(100, max(5, $request->integer('size', 25))),
            sortField:    $sortField,
            sortDir:      $sortDir,
            search:       $request->input('search'),
            provinceCode: $request->input('province_code'),
            wardCode:     $request->input('ward_code'),
            dateFrom:     $request->input('date_from'),
            dateTo:       $request->input('date_to'),
            status:       $request->input('status'),
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => XxxListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
```

**Response format chuẩn cho Tabulator remote pagination:**
```json
{
  "data": [...],
  "last_page": 10,
  "total": 243
}
```

**Route registration:**
```php
// Modules/Xxx/routes/web.php

Route::middleware(['auth'])->prefix('backend/api')->name('backend.api.')->group(function () {
    Route::get('xxx', [XxxApiController::class, 'index'])->name('xxx');
});
```

---

## 4. Backend — DTOs với Spatie Laravel Data

File: `Modules/Xxx/app/Data/Requests/StoreXxxData.php`

```php
<?php

namespace Modules\Xxx\Data\Requests;

use App\Shared\Tenancy\Enums\XxxStatus;
use Spatie\LaravelData\Attributes\Validation\{Email, Exists, Max, Nullable, Regex, Required, StringType, Unique, Url};
use Spatie\LaravelData\Data;

class StoreXxxData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $name,

        // Enum cast tự động — Spatie Data tự map string → enum
        public readonly XxxStatus $status,

        #[Nullable, StringType, Max(255), Regex('/^[a-z0-9\-]+$/'), Unique('xxx_table', 'slug')]
        public readonly ?string $slug,

        #[Required, StringType, Exists('provinces', 'province_code')]
        public readonly string $province_code,

        // ... các field khác
    ) {}
}
```

File: `Modules/Xxx/app/Data/Requests/UpdateXxxData.php`

```php
class UpdateXxxData extends Data
{
    public function __construct(/* same fields */) {}

    // Override rules() để exclude current record khỏi unique check
    public static function rules(): array
    {
        $currentId = request()->route('xxx')?->id;
        return [
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/',
                \Illuminate\Validation\Rule::unique('xxx_table', 'slug')->ignore($currentId)],
        ];
    }
}
```

**Sử dụng trong controller:**
```php
public function store(Request $request, StoreXxxAction $action): RedirectResponse
{
    $data = StoreXxxData::validateAndCreate($request->all());
    $item = $action->handle($data);
    return redirect()->route('backend.xxx.show', $item)->with('success', '...');
}
```

---

## 5. Backend — Actions + Events

### Action pattern (Lorisleiva AsAction)

```php
<?php

namespace Modules\Xxx\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Xxx\Data\Requests\StoreXxxData;
use Modules\Xxx\Events\XxxCreated;
use Modules\Xxx\Models\Xxx;

class StoreXxxAction
{
    use AsAction;

    public function handle(StoreXxxData $data): Xxx
    {
        $item = Xxx::create([
            'name'   => $data->name,
            'status' => $data->status->value,
            // ...
        ]);

        event(new XxxCreated($item)); // ← fire event, không log trực tiếp
        return $item;
    }
}
```

### Event + Listener (tách activity logging)

```php
// Events/XxxCreated.php
class XxxCreated
{
    use Dispatchable, SerializesModels;
    public function __construct(public readonly Xxx $xxx) {}
}

// Listeners/LogXxxCreated.php
class LogXxxCreated
{
    public function handle(XxxCreated $event): void
    {
        activity()->on($event->xxx)->log('xxx.created');
    }
}
```

**EventServiceProvider:**
```php
protected $listen = [
    XxxCreated::class => [LogXxxCreated::class],
    XxxUpdated::class => [LogXxxUpdated::class],
];
protected static $shouldDiscoverEvents = false; // explicit registration
```

**Lý do tách Events:** Action không phụ thuộc vào Spatie Activity Log. Listener có thể thay đổi (email, webhook, queue) mà không sửa Action.

---

## 6. Backend — Database Indexes

Migration bắt buộc cho bảng có 100k+ records:

```php
// database/migrations/YYYY_MM_DD_add_xxx_search_indexes.php

Schema::table('xxx_table', function (Blueprint $table) {
    // Filter columns → B-tree index
    $table->index('province_code', 'idx_xxx_province');
    $table->index('status',        'idx_xxx_status');
    $table->index('created_at',    'idx_xxx_created_at');

    // Full-text search (MySQL production — không dùng được SQLite dev)
    // $table->fullText(['name', 'email', 'tax_code'], 'ft_xxx_search');
});
```

**SQLite dev:** `LIKE '%term%'` không dùng index — chấp nhận được ở môi trường dev.  
**MySQL production:** Thêm fulltext index + dùng `MATCH(col) AGAINST(term IN BOOLEAN MODE)` trong Handler nếu cần scale.

---

## 7. Frontend — Cấu trúc Alpine.js Component

### 7.1 Blade template structure

```blade
@section('content')
<div x-data="xxxListPage">  {{-- dùng Alpine.data name, KHÔNG có () --}}
    {{-- Filter bar --}}
    {{-- Table container --}}
</div>
@endsection

@push('styles')
<style>/* Tabulator + TomSelect theme overrides */</style>
@endpush

@push('scripts')
@vite(['resources/js/modules/tabulator.js', 'resources/js/modules/tom-select.js', 'resources/js/modules/flatpickr.js'], 'build/backend')
<script>
// 1. Utility functions (esc, isoDate, ...)
// 2. Column definitions (COLUMNS array)
// 3. Alpine.data('xxxListPage', ...) trong alpine:init listener
</script>
@endpush
```

**Thứ tự script quan trọng:**
- `@vite(...)` của backend bundles phải là dòng **đầu tiên** trong `@push('scripts')`
- Inline `<script>` chứa `alpine:init` listener chạy **ngay** khi parser gặp nó (không defer)
- `app.js` (defer module) chạy → fire `alpine:init` → Alpine component registered
- Backend bundles (tabulator, tom-select, flatpickr) là defer modules → chạy sau `app.js`
- `DOMContentLoaded` fire **sau tất cả** defer modules → đây là điểm an toàn để init libraries

### 7.2 Script loading timing

```
HTML parsed
    │
    ├─ Inline <script> runs (registers alpine:init listener)
    │
    └─ Deferred modules execute IN ORDER:
          app.js ─── Alpine.start() ─── fires alpine:init
                                              │
                                              └─► Alpine.data('xxxListPage', ...) registered
                                                  init() called → registers DOMContentLoaded listener
          tabulator.js  ─── window.Tabulator set
          tom-select.js ─── window.TomSelect set
          flatpickr.js  ─── window.initDateRangePicker set
                │
                └─► DOMContentLoaded fires ─── _setup() runs
                    (all window.* globals guaranteed available)
```

### 7.3 Alpine component skeleton

```javascript
document.addEventListener('alpine:init', function () {
    // ── Closure-scoped lib instances (NON-reactive) ──────────────────────
    // Không đặt vào Alpine state — chúng không cần reactivity,
    // đặt trong closure để tránh Alpine proxy overhead
    var tableInst    = null;
    var provTsInst   = null;
    var wardTsInst   = null;
    var statusTsInst = null;
    var dateFpInst   = null;
    var wardsCache   = {}; // { province_code: [{ward_code, name}] }
    var settingPreset = false; // guard cho flatpickr onChange

    // ── Constants (từ Blade server-side) ─────────────────────────────────
    var API_URL   = '{{ route('backend.api.xxx') }}';
    var WARDS_API = '{{ url('/api/provinces') }}';  // dùng url() helper, KHÔNG hardcode
    var PROVINCES = @json($provinces);
    var STATUSES  = @json($statuses);
    var LS_COLS   = 'xxx-list-hidden-cols'; // key localStorage — unique per module

    Alpine.data('xxxListPage', function () {
        return {
            // ── Alpine reactive state (chỉ những gì cần reactivity) ──────
            filters: {
                search: '', province_code: '', ward_code: '',
                status: '', date_from: '', date_to: ''
            },
            wards:           [],  // async-loaded, cần reactivity cho chip label
            activeDatePreset: '',
            hiddenCols:      [],

            // ── Computed getters ─────────────────────────────────────────
            get hasFilters() { /* ... */ },
            get activeChips() { /* ... dùng PROVINCES closure var, không this.provinces */ },
            get toggleableCols() { /* ... */ },

            // ── Lifecycle ────────────────────────────────────────────────
            init: function () {
                var self = this;
                self.loadState();
                try { self.hiddenCols = JSON.parse(localStorage.getItem(LS_COLS) || '[]'); } catch (e) {}
                document.addEventListener('DOMContentLoaded', function () {
                    self._setup();
                }, { once: true });
            },

            _setup: function () {
                // Khởi tạo Tabulator, TomSelects, Flatpickr
                // Restore hidden cols, date picker, ward list từ URL state
            },

            // ── State persistence ─────────────────────────────────────────
            loadState: function () { /* đọc URLSearchParams */ },
            saveState: function () { /* ghi URLSearchParams + history.replaceState */ },

            // ── Actions ──────────────────────────────────────────────────
            refresh: function () { if (tableInst) tableInst.replaceData(); },
            // ...
        };
    });
});
```

---

## 8. Frontend — Security: HTML Escaping

**Quy tắc bắt buộc:** Mọi dữ liệu từ API đều phải qua `esc()` trước khi nối vào chuỗi HTML trong formatter.

```javascript
// Đặt NGOÀI alpine:init, ở top-level script
function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
```

**Áp dụng trong mọi Tabulator formatter:**
```javascript
// ❌ Sai — XSS nếu name = '<script>alert(1)</script>'
return '<a href="' + d.show_url + '">' + d.name + '</a>';

// ✓ Đúng
return '<a href="' + esc(d.show_url) + '">' + esc(d.name) + '</a>';
```

**Lý do `esc()` an toàn cho URL:** URLs không chứa `<`, `>`, `"` (percent-encoded). `&` trong URL thành `&amp;` trong HTML attribute — trình duyệt decode lại khi render href.

**Delete button dùng data-* attributes:**
```javascript
// ❌ Sai — HTML entities không decode trong script block
' onclick="doDelete(\'' + row.name + '\')"'

// ✓ Đúng — data-* attributes + textContent decode entities an toàn
'<button data-url="' + esc(row.delete_url) + '" data-name="' + esc(row.name) + '"'
+  ' onclick="window.confirmDelete(this.dataset.url, this.dataset.name)">'
```

---

## 9. Frontend — Tabulator Configuration

### 9.1 Cấu hình chuẩn cho server-side list

```javascript
tableInst = new window.Tabulator('#xxx-table', {
    // ── Data source ──────────────────────────────────────────────────
    ajaxURL:    API_URL,
    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
    ajaxParams: function () {
        // Function syntax — re-evaluated mỗi request, đọc filter state mới nhất
        var p = {}, f = self.filters;
        if (f.search)        p.search        = f.search;
        if (f.province_code) p.province_code = f.province_code;
        if (f.status)        p.status        = f.status;
        if (f.date_from)     p.date_from     = f.date_from;
        if (f.date_to)       p.date_to       = f.date_to;
        return p;
    },
    ajaxResponse: function (_url, _params, res) {
        // Tabulator expects { data, last_page }
        // API trả thêm 'total' cho counter
        return res;
    },

    // ── Pagination ───────────────────────────────────────────────────
    pagination:             true,
    paginationMode:         'remote',   // PHẢI là 'remote' cho server-side
    paginationSize:         25,
    paginationSizeSelector: [10, 25, 50, 100],
    paginationCounter:      'rows',

    // ── Sort ────────────────────────────────────────────────────────
    sortMode:    'remote',              // PHẢI là 'remote'
    initialSort: [{ column: 'created_at', dir: 'desc' }],

    // ── Layout ──────────────────────────────────────────────────────
    layout:           'fitColumns',
    responsiveLayout: 'collapse',
    movableColumns:   true,
    height:           '68vh',          // Fixed height để có scroll + frozen cols

    // ── i18n ────────────────────────────────────────────────────────
    locale: 'vi-VN',
    langs: {
        'vi-VN': {
            pagination: {
                page_size: 'Dòng/trang', page_title: 'Trang',
                first: '«', last: '»', prev: '‹', next: '›',
                counter: { showing: '', of: 'trong', rows: 'dòng', pages: 'trang' },
            },
        },
    },

    columns: COLUMNS,
});
```

### 9.2 Column definition patterns

```javascript
var COLUMNS = [
    // Frozen column (luôn hiển thị khi scroll ngang)
    {
        title: 'Tên', field: 'name', minWidth: 220, sorter: 'string', frozen: true,
        formatter: function (cell) {
            var d = cell.getRow().getData();
            return '<a href="' + esc(d.show_url) + '">' + esc(d.name) + '</a>';
        },
    },

    // Enum status với badge color
    {
        title: 'Trạng thái', field: 'status', width: 130, hozAlign: 'center',
        formatter: function (cell) {
            var s = cell.getValue(), label = esc(cell.getRow().getData().status_label);
            if (s === 'active')    return '<span class="badge badge-success badge-sm">' + label + '</span>';
            if (s === 'suspended') return '<span class="badge badge-error badge-sm">'   + label + '</span>';
            return                        '<span class="badge badge-ghost badge-sm">'   + label + '</span>';
        },
    },

    // Nullable field với fallback
    {
        title: 'Ngành nghề', field: 'industry',
        formatter: function (cell) {
            return esc(cell.getValue()) || '<span class="opacity-30">—</span>';
        },
    },

    // Action column — frozen, không sort
    {
        title: 'Thao tác', field: 'id', width: 110, hozAlign: 'center',
        headerSort: false, frozen: true,
        formatter: function (cell) {
            var d = cell.getRow().getData();
            return '<div class="flex gap-1">'
                + '<a href="' + esc(d.show_url) + '" class="btn btn-ghost btn-xs btn-square">...</a>'
                + '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square">...</a>'
                + '<button class="btn btn-ghost btn-xs btn-square text-error"'
                +   ' data-url="' + esc(d.delete_url) + '" data-name="' + esc(d.name) + '"'
                +   ' onclick="window.confirmDelete(this.dataset.url,this.dataset.name)">...</button>'
                + '</div>';
        },
    },
];
```

---

## 10. Frontend — TomSelect Configuration

### 10.1 Pattern chuẩn

```javascript
var tsInst = new window.TomSelect('#filter-province', {
    dropdownParent: 'body',   // QUAN TRỌNG: tránh bị clip bởi overflow:hidden của .card
    placeholder:    'Tất cả...',
    maxOptions:     null,     // Hiển thị tất cả options (không limit)
    searchField:    ['text'],
    plugins:        ['clear_button'],
    options:        data.map(function (item) { return { value: item.code, text: item.name }; }),
    items:          existingValue ? [existingValue] : [],  // restore từ URL state
    onChange: function (val) {
        self.filters.field = val || '';
        self.saveState();
        self.refresh();
    },
    render: {
        no_results: function () { return '<div class="no-results p-3 text-sm opacity-50">Không tìm thấy</div>'; },
    },
});
```

**`dropdownParent: 'body'` là bắt buộc** khi TomSelect nằm trong element có `overflow: hidden` (DaisyUI `.card` luôn có thuộc tính này). Nếu không, dropdown bị clip và user không chọn được.

### 10.2 Update placeholder đúng cách

```javascript
// ❌ Sai — chỉ update settings, không update DOM
tsInst.settings.placeholder = 'New text';

// ✓ Đúng — update cả settings lẫn DOM input element
function tsSetPlaceholder(ts, text) {
    ts.settings.placeholder = text;
    if (ts.control_input) ts.control_input.setAttribute('placeholder', text);
}
```

### 10.3 Ward cascade pattern (province → ward)

```javascript
// Biến cache để tránh fetch lại khi user switch province
var wardsCache = {};

function loadWards(code, pendingWard, silent) {
    // Cache hit: không cần fetch
    if (wardsCache[code]) {
        applyWards(wardsCache[code], pendingWard, silent);
        return;
    }
    tsSetPlaceholder(wardTsInst, 'Đang tải...');
    fetch(WARDS_API + '/' + code + '/wards')
        .then(function (r) { return r.json(); })
        .then(function (data) {
            wardsCache[code] = data; // lưu cache
            applyWards(data, pendingWard, silent);
        })
        .catch(function () { tsSetPlaceholder(wardTsInst, 'Lỗi tải dữ liệu'); });
}

function applyWards(data, pendingWard, silent) {
    self.wards = data;
    data.forEach(function (w) { wardTsInst.addOption({ value: w.ward_code, text: w.name }); });
    tsSetPlaceholder(wardTsInst, 'Tất cả phường/xã...');
    wardTsInst.enable();
    if (pendingWard) {
        wardTsInst.setValue(pendingWard, true); // true = silent, không trigger onChange
        self.filters.ward_code = pendingWard;
    }
    if (!silent) self.refresh(); // silent=true khi restore URL state (tránh double refresh)
}
```

**`silent` parameter quan trọng:**
- `false` (interactive): user chọn province → refresh table ngay sau khi wards load
- `true` (URL restore): Tabulator đã gửi request đúng filters từ đầu → không cần refresh lần 2

---

## 11. Frontend — Filter State Management

### 11.1 URL state persistence

```javascript
var URL_PARAM_MAP = {
    search:        'q',
    province_code: 'prov',
    ward_code:     'ward',
    status:        'st',
    date_from:     'from',
    date_to:       'to',
    // activeDatePreset lưu riêng
};

loadState: function () {
    var p = new URLSearchParams(location.search);
    if (p.has('q'))    this.filters.search        = p.get('q');
    if (p.has('prov')) this.filters.province_code = p.get('prov');
    if (p.has('ward')) this.filters.ward_code     = p.get('ward');
    if (p.has('st'))   this.filters.status        = p.get('st');
    if (p.has('from')) this.filters.date_from     = p.get('from');
    if (p.has('to'))   this.filters.date_to       = p.get('to');
    if (p.has('dpre')) this.activeDatePreset      = p.get('dpre');
},

saveState: function () {
    var p = new URLSearchParams(), f = this.filters;
    if (f.search)        p.set('q',    f.search);
    if (f.province_code) p.set('prov', f.province_code);
    if (f.ward_code)     p.set('ward', f.ward_code);
    if (f.status)        p.set('st',   f.status);
    if (f.date_from)     p.set('from', f.date_from);
    if (f.date_to)       p.set('to',   f.date_to);
    if (this.activeDatePreset) p.set('dpre', this.activeDatePreset);
    var qs = p.toString();
    history.replaceState(null, '', qs ? '?' + qs : location.pathname);
},
```

### 11.2 localStorage cho UI preferences

```javascript
var LS_COLS = 'xxx-list-hidden-cols'; // unique key per module

// Restore trong init()
try { self.hiddenCols = JSON.parse(localStorage.getItem(LS_COLS) || '[]'); } catch (e) {}

// Áp dụng trong _setup() sau khi table khởi tạo
self.hiddenCols.forEach(function (field) { tableInst.hideColumn(field); });

// Lưu khi toggle
toggleCol: function (field) {
    if (this.hiddenCols.includes(field)) {
        this.hiddenCols = this.hiddenCols.filter(function (f) { return f !== field; });
        tableInst.showColumn(field);
    } else {
        this.hiddenCols.push(field);
        tableInst.hideColumn(field);
    }
    try { localStorage.setItem(LS_COLS, JSON.stringify(this.hiddenCols)); } catch (e) {}
},
```

**Nguyên tắc phân biệt URL vs localStorage:**
- **URL params:** filter state (search, province, date) — shareable link, refresh giữ đúng kết quả lọc
- **localStorage:** UI preferences (hidden cols, page size) — per-device, không ảnh hưởng kết quả

### 11.3 Active filter chips pattern

```javascript
get activeChips() {
    var chips = [], f = this.filters;
    // Text search
    if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
    // Enum filter — lookup label từ closure array (không từ reactive state)
    if (f.status) {
        var st = STATUSES.find(function (s) { return s.value === f.status; });
        chips.push({ key: 'status', label: st ? st.text : f.status });
    }
    // Async-loaded data — lookup từ this.wards (reactive, vì load async)
    if (f.ward_code) {
        var ward = this.wards.find(function (w) { return w.ward_code === f.ward_code; });
        chips.push({ key: 'ward', label: ward ? ward.name : f.ward_code });
    }
    return chips;
},

removeChip: function (key) {
    var self = this;
    var clearActions = {
        search:   function () { self.filters.search = ''; },
        province: function () {
            self.filters.province_code = ''; if (provTsInst) provTsInst.clear(true);
            self.filters.ward_code = '';     if (wardTsInst) { wardTsInst.clear(true); wardTsInst.clearOptions(); wardTsInst.disable(); }
            self.wards = [];
        },
        ward:     function () { self.filters.ward_code = ''; if (wardTsInst) wardTsInst.clear(true); },
        status:   function () { self.filters.status = '';    if (statusTsInst) statusTsInst.clear(true); },
        date:     function () { self.clearDate(); return; }, // early return để tránh saveState/refresh 2 lần
    };
    if (clearActions[key]) clearActions[key]();
    this.saveState();
    this.refresh();
},
```

---

## 12. Frontend — Flatpickr Date Range

```javascript
dateFpInst = window.initDateRangePicker('#filter-date', {
    disableMobile: true,
    onChange: function (dates) {
        if (settingPreset) return; // guard khi preset button đặt ngày
        self.activeDatePreset = '';
        if (dates.length === 2) {
            self.filters.date_from = isoDate(dates[0]);
            self.filters.date_to   = isoDate(dates[1]);
        } else {
            self.filters.date_from = '';
            self.filters.date_to   = '';
        }
        self.saveState();
        self.refresh();
    },
});

// Date preset buttons
var DATE_PRESETS = {
    today: function (y, m, d) { return [new Date(y,m,d), new Date(y,m,d)]; },
    week:  function (y, m, d) {
        var dow = new Date().getDay() === 0 ? 6 : new Date().getDay() - 1;
        return [new Date(y,m,d-dow), new Date(y,m,d-dow+6)];
    },
    month: function (y, m)    { return [new Date(y,m,1), new Date(y,m+1,0)]; },
    year:  function (y)       { return [new Date(y,0,1), new Date(y,11,31)]; },
};

setDatePreset: function (preset) {
    var now = new Date();
    var range = DATE_PRESETS[preset](now.getFullYear(), now.getMonth(), now.getDate());
    if (!range) return;
    settingPreset = true; // ngăn onChange fire thêm lần nữa
    this.activeDatePreset  = preset;
    this.filters.date_from = isoDate(range[0]);
    this.filters.date_to   = isoDate(range[1]);
    dateFpInst.setDate(range, false); // false = không trigger onChange
    settingPreset = false;
    this.saveState();
    this.refresh();
},
```

---

## 13. Frontend — CSS Theme Overrides

Đặt trong `@push('styles')` (layout hỗ trợ `@stack('styles')`), **không** nhúng trong `@push('scripts')`:

```html
@push('styles')
<style>
/* ── Tabulator ─────────────────────────────────────────────────────────── */
#xxx-table .tabulator { border:none; border-radius:0; background:transparent; font-size:.8125rem; }
#xxx-table .tabulator-header { background:oklch(var(--b2)); border-bottom:1px solid oklch(var(--b3)); color:oklch(var(--bc)/.65); font-weight:600; font-size:.75rem; text-transform:uppercase; letter-spacing:.04em; }
#xxx-table .tabulator-col { background:transparent; border-right:1px solid oklch(var(--b3)); }
#xxx-table .tabulator-row { background:oklch(var(--b1)); border-bottom:1px solid oklch(var(--b2)); }
#xxx-table .tabulator-row:hover { background:oklch(var(--b2)/.6); }
#xxx-table .tabulator-row .tabulator-cell { border-right:1px solid oklch(var(--b2)); color:oklch(var(--bc)); padding:.5rem .75rem; }
#xxx-table .tabulator-footer { background:oklch(var(--b2)/.5); border-top:1px solid oklch(var(--b3)); }
#xxx-table .tabulator-page { background:transparent; border:1px solid oklch(var(--b3)); color:oklch(var(--bc)); border-radius:.375rem; padding:.2rem .5rem; margin:0 1px; }
#xxx-table .tabulator-page.active { background:oklch(var(--p)); color:oklch(var(--pc)); border-color:oklch(var(--p)); }
#xxx-table .tabulator-page[disabled] { opacity:.35; }
#xxx-table .tabulator-page-size { background:oklch(var(--b1)); border:1px solid oklch(var(--b3)); color:oklch(var(--bc)); border-radius:.375rem; padding:.2rem .4rem; }
#xxx-table .tabulator-frozen.tabulator-frozen-right { box-shadow:-2px 0 4px oklch(var(--b3)/.5); }
#xxx-table .tabulator-frozen.tabulator-frozen-left  { box-shadow: 2px 0 4px oklch(var(--b3)/.5); }
#xxx-table .tabulator-tableholder::-webkit-scrollbar { width:6px; height:6px; }
#xxx-table .tabulator-tableholder::-webkit-scrollbar-track { background:oklch(var(--b2)); }
#xxx-table .tabulator-tableholder::-webkit-scrollbar-thumb { background:oklch(var(--b3)); border-radius:3px; }

/* ── TomSelect ─────────────────────────────────────────────────────────── */
.ts-wrapper.single .ts-control { background:oklch(var(--b1)); border-color:oklch(var(--b3)); color:oklch(var(--bc)); border-radius:.375rem; min-height:2rem; padding:.25rem .5rem; font-size:.875rem; }
.ts-wrapper.single.focus .ts-control { border-color:oklch(var(--p)); outline:none; box-shadow:0 0 0 2px oklch(var(--p)/.2); }
.ts-dropdown { background:oklch(var(--b1)); border:1px solid oklch(var(--b3)); border-radius:.5rem; box-shadow:0 4px 16px rgba(0,0,0,.15); z-index:9999; }
.ts-dropdown .ts-option { color:oklch(var(--bc)); padding:.4rem .75rem; font-size:.875rem; }
.ts-dropdown .ts-option:hover, .ts-dropdown .ts-option.active { background:oklch(var(--b2)); }
.ts-dropdown .ts-option.selected { background:oklch(var(--p)/.15); color:oklch(var(--p)); }
.ts-wrapper .clear-button { color:oklch(var(--bc)/.4); }
.ts-wrapper .clear-button:hover { color:oklch(var(--bc)); }
.ts-control input { color:oklch(var(--bc)) !important; }
</style>
@endpush
```

**Lưu ý:** Dùng `oklch(var(--b1))` thay vì màu cố định — tự động đổi theo DaisyUI theme (dark/light).

---

## 14. Checklist triển khai module mới

### Backend

- [ ] Tạo `ListXxxQuery` (QueryInterface) với đầy đủ filter params
- [ ] Tạo `ListXxxHandler` (QueryHandlerInterface) với sort whitelist + filter logic
  - [ ] `->select('table.*')` **ngay sau** `withoutTenant()` — bắt buộc khi có leftJoin sort
  - [ ] `->withCount(...)` sau `->select(...)` — dùng `addSelect()` nên an toàn
- [ ] Tạo `XxxListResource` (JsonResource) — serialization tách khỏi controller
- [ ] Đăng ký API route trong `routes/web.php` với prefix `backend/api`
- [ ] Tạo `XxxApiController` với `authorize('viewAny')` + `XxxListResource::collection(...)`
- [ ] Tạo `StoreXxxData` + `UpdateXxxData` (Spatie Laravel Data)
- [ ] Sửa `UpdateXxxData::rules()` cho unique-ignore khi update
- [ ] Tạo migration thêm index cho filter/sort columns
- [ ] Tạo Events + Listeners, đăng ký trong EventServiceProvider
- [ ] Thêm EventServiceProvider vào `$providers` trong `XxxServiceProvider`
- [ ] Fix route model binding trong `RouteServiceProvider` nếu dùng `withoutTenant()`

### Frontend

- [ ] `@push('styles')` chứa Tabulator + TomSelect theme CSS
- [ ] `@push('scripts')` bắt đầu bằng `@vite([...], 'build/backend')`
- [ ] `esc()` helper ở top-level script, áp dụng trong mọi formatter
- [ ] `WARDS_API = '{{ url('/api/provinces') }}'` — không hardcode path
- [ ] `PROVINCES = @json($provinces)` từ controller, không inline PHP trong JS
- [ ] `STATUSES = @json($statuses)` tương tự
- [ ] `wardsCache = {}` cho province→ward cascade
- [ ] `LS_COLS = 'xxx-list-hidden-cols'` — tên unique per module
- [ ] Alpine state chỉ chứa dữ liệu reactive: `filters`, `wards`, `hiddenCols`, `activeDatePreset`
- [ ] Lib instances (`tableInst`, `tsInst`) trong closure scope, không Alpine state
- [ ] `_loadWards(code, pendingWard, silent)` với `silent` param
- [ ] `tsSetPlaceholder(ts, text)` helper để update DOM đúng cách
- [ ] `DOMContentLoaded` listener trong `init()` với `{ once: true }`
- [ ] Restore `hiddenCols` từ localStorage trước khi `_setup()`
- [ ] Áp dụng `hiddenCols` sau khi Tabulator khởi tạo trong `_setup()`
- [ ] `onProvinceChange()` gọi `refresh()` ngay (không chờ ward load xong)
- [ ] `_loadWards(..., true)` khi restore URL state (silent)
- [ ] Mọi TomSelect có `dropdownParent: 'body'`

---

## 15. Các quyết định kỹ thuật quan trọng

| Vấn đề | Quyết định | Lý do |
|--------|-----------|-------|
| Sort field validation | Whitelist `const SORTABLE` | SQL injection prevention |
| Province sort | `leftJoin` + `orderBy('provinces.name')` | Sort theo tên thực, không phải code |
| `withCount` + `leftJoin` cùng query | An toàn nhờ `->select('table.*')` | `withCount` dùng `addSelect()` — append subquery, không overwrite `select`. Explicit select ngăn `paginate(['*'])` kéo columns của bảng join |
| `->select('table.*')` vị trí | Ngay sau `withoutTenant()`, trước `withCount` | Đảm bảo select scope được set trước khi `addSelect` chạy; leftJoin có thể được áp dụng bất kỳ lúc nào trong match block |
| Response serialization | `JsonResource` thay cho `formatRow()` closure | Reusable, testable, idiomatic Laravel; controller chỉ điều phối |
| Lib instances trong Alpine | Closure scope, không reactive | Tránh Alpine proxy overhead, không cần reactivity |
| `loadingWards` | Bỏ | Không dùng trong template — dead state |
| `provinces` reactive | Bỏ, dùng `PROVINCES` closure | Không cần reactivity cho static data |
| `wardsCache` | Object keyed by province_code | Tránh fetch lại khi user switch tỉnh |
| `silent` param trong `_loadWards` | Boolean flag | Tránh double-refresh khi restore URL state |
| URL state | `URLSearchParams` + `history.replaceState` | Shareable filter links |
| UI prefs (hidden cols) | `localStorage` | Per-device, không nên ảnh hưởng URL share |
| `dropdownParent: 'body'` | Bắt buộc | DaisyUI `.card` có `overflow:hidden` clip dropdown |
| `tsSetPlaceholder()` | Update cả settings + DOM | TomSelect không có `updatePlaceholder()` public API |
| Scripts/styles | `@push('styles')` + `@push('scripts')` | Separation of concerns, layout có cả 2 stacks |
| HTML escaping | `esc()` function + áp dụng nhất quán | XSS prevention trong Tabulator formatters |
| `data-*` + `textContent` cho delete confirm | `data-url`, `data-name` attributes | HTML entities không decode trong `<script>` block |

---

## 16. Giới hạn hiện tại & hướng mở rộng

### Fulltext search (MySQL production)
Hiện dùng `LIKE '%term%'` — hoạt động tốt với SQLite dev và MySQL ≤100k records. Với >500k records cần:
```sql
ALTER TABLE organizations ADD FULLTEXT ft_search (name, email, tax_code, phone);
```
```php
// Trong Handler, thay LIKE bằng:
$q->whereRaw('MATCH(name, email, tax_code, phone) AGAINST(? IN BOOLEAN MODE)', [$query->search . '*']);
```

### Export CSV/Excel
Thêm endpoint `GET /backend/api/xxx/export` — tái dùng `ListXxxHandler` với `perPage = PHP_INT_MAX`, bỏ pagination, stream response.

### Bulk actions
Tabulator hỗ trợ row selection — thêm `selectable: true` + bulk action bar (ẩn/hiện theo Alpine `selectedRows.length > 0`).

### Filter nâng cao
Mô hình hiện tại dễ mở rộng: thêm field trong `ListXxxQuery` → thêm filter clause trong `Handler` → thêm TomSelect/input trong blade → thêm vào `saveState`/`loadState`.
