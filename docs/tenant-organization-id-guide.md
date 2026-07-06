# Chuẩn hóa `organization_id` cho module — Hướng dẫn toàn diện

> **Mục đích:** Đảm bảo mọi module đều có `organization_id` đúng chuẩn để  
> `TenantAwareModel` + `BelongsToOrganization` tự động scope dữ liệu theo tenant.  
> **Reference implementation:** `Modules/Branch` — gold standard cho toàn bộ pattern.  
> **Cập nhật lần cuối:** 2026-06-09 (sau khi hoàn thành Priority Groups 1, 2, 3)

---

## Mục lục

1. [Kiến trúc tenant](#1-kiến-trúc-tenant)
2. [Trạng thái hiện tại](#2-trạng-thái-hiện-tại)
3. [Checklist 6 lớp](#3-checklist-6-lớp)
4. [Code template từng lớp](#4-code-template)
5. [Trường hợp đặc biệt](#5-trường-hợp-đặc-biệt)
6. [Quy trình áp dụng cho module mới](#6-quy-trình-áp-dụng)
7. [Câu hỏi thường gặp](#7-câu-hỏi-thường-gặp)

---

## 1. Kiến trúc tenant

```
TenantAwareModel (app/Foundation/Models/TenantAwareModel.php)
    ├── SoftDeletes
    ├── LogsActivity
    └── BelongsToOrganization (app/Shared/Tenancy/Traits/)
            ├── bootBelongsToOrganization()
            │     ├── addGlobalScope(OrganizationScope)  ← auto filter WHERE organization_id = ?
            │     └── creating: auto-set organization_id từ TenantContext nếu chưa có
            ├── organization(): BelongsTo Organization
            └── scopeWithoutTenant() / scopeForOrganization()
```

**Quy tắc cốt lõi:**

- Model lưu dữ liệu theo tenant → **phải** extend `TenantAwareModel`
- Bảng thiếu cột `organization_id` khi model extend `TenantAwareModel` → **query sẽ fail**
- `BelongsToOrganization` trait cung cấp sẵn relationship `organization()` — **không** khai báo lại trong model
- Store Action **phải** truyền `organization_id` explicit từ `$data` (không dựa vào TenantContext auto-set) để đảm bảo đúng khi dùng trong queue job hoặc API không qua tenant middleware

---

## 2. Trạng thái hiện tại

> Cập nhật: 2026-06-09 — tất cả 3 nhóm đã hoàn thành.

| Module | TenantAwareModel | org_id migration | org_id fillable | StoreData field | StoreAction explicit | Controller + Views |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| **Branch** ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Department ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Employee ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| JobTitle ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| KcCategory ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| KpiGoal ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Leave ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| PerformanceReview ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Project ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Lead ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| LeadPipelineStage ✅ ⚠️ | plain Model* | ✅ | ✅ | ✅ | ✅ | ✅ |
| LeadSource ✅ ⚠️ | plain Model* | ✅ | ✅ | ✅ | ✅ | ✅ |
| JobPosting ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| KcItem ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Recruitment ✅ ⚠️ | ✅ | ✅ (org_id*) | ✅ | ✅ | ✅ | ✅ |
| Survey ✅ | ✅ | ✅ (migration added) | ✅ | ✅ | ✅ | ✅ |
| Sop ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Assessment ✅ | ✅ | ✅ (migration added + deleted_at) | ✅ | ✅ (inline validate) | ✅ | ✅ |

> ⚠️ Ghi chú đặc biệt: xem [Mục 5 — Trường hợp đặc biệt](#5-trường-hợp-đặc-biệt)

---

## 3. Checklist 6 lớp

Mỗi module cần hoàn thành **6 lớp** theo thứ tự:

### Lớp 0 — Migration (nếu cột chưa tồn tại)

- [ ] Bảng đã có cột `organization_id` — kiểm tra bằng `php artisan db:show --table=[table]`
- [ ] Nếu chưa: tạo migration alter riêng (xem template 4.1)
- [ ] Chạy `php artisan migrate`

### Lớp 1 — Model

- [ ] Extends `TenantAwareModel` (không phải `Model` thường)
- [ ] `'organization_id'` là **phần tử đầu tiên** trong `$fillable`
- [ ] **Không** khai báo thêm `organization()` — trait đã cung cấp

### Lớp 2 — Store Data (Spatie Laravel Data)

- [ ] `public readonly int $organization_id` là **tham số đầu tiên** trong constructor
- [ ] `rules()` có rule `'organization_id' => ['required', 'integer', 'exists:organizations,id']`
- [ ] `UpdateFooData` **không** cần `organization_id` — org không thay đổi sau khi tạo

### Lớp 3 — Store Action

- [ ] `'organization_id' => $data->organization_id` là **key đầu tiên** trong mảng `create([])`
- [ ] Không import / dùng `TenantContext` để lấy org_id

### Lớp 4 — Controller

- [ ] Import `use App\Shared\Tenancy\Models\Organization;`
- [ ] Có method `private function _resolveOrganizations(): array` trả về 3-tuple
- [ ] `create()` nhận `[$organizations, $defaultOrgId, $orgLocked]` và pass xuống view
- [ ] `edit()` nhận `[$organizations, , $orgLocked]` và pass xuống view

### Lớp 5 — View + JS + SCSS

- [ ] Field "Tổ chức" là **field đầu tiên** trong form (tab Thông tin cơ bản hoặc card đầu)
- [ ] Dùng `$orgLocked` (không dùng `$organizations->count() === 1`) để phân nhánh UI
- [ ] `tabFields.basic` trong Alpine `x-data` bao gồm `'organization_id'`
- [ ] SCSS entry point có `@use 'tom-select';` (bắt buộc nếu form có bất kỳ `<select>`)
- [ ] JS gọi `initAllTomSelects(form)` sau khi form được tìm thấy

---

## 4. Code template

### 4.1 Migration — thêm cột vào bảng đã tồn tại

```php
// Modules/[Name]/database/migrations/YYYY_MM_DD_XXXXXX_add_organization_id_to_[table].php
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('[table]', function (Blueprint $table) {
            if (!Schema::hasColumn('[table]', 'organization_id')) {
                $table->foreignId('organization_id')
                      ->nullable()          // nullable để không break dữ liệu cũ
                      ->after('id')
                      ->constrained()
                      ->restrictOnDelete(); // hoặc nullOnDelete() tùy use-case
            }
        });
    }

    public function down(): void
    {
        Schema::table('[table]', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });
    }
};
```

### 4.2 Model

```php
use App\Foundation\Models\TenantAwareModel;

class [Entity] extends TenantAwareModel
{
    protected $fillable = [
        'organization_id',  // ← luôn đầu tiên
        'name',
        // ... các field khác
    ];
}
```

### 4.3 Store Data (Spatie Laravel Data)

```php
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class Store[Entity]Data extends Data
{
    public function __construct(
        #[Required]
        public readonly int $organization_id,  // ← tham số đầu tiên

        // ... các tham số khác
    ) {}

    public static function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            // ... các rule khác
        ];
    }
}
```

### 4.4 Store Action

```php
use Lorisleiva\Actions\Concerns\AsAction;

class Store[Entity]Action
{
    use AsAction;

    public function handle(Store[Entity]Data $data): [Entity]
    {
        return [Entity]::create([
            'organization_id' => $data->organization_id,  // ← explicit, key đầu tiên
            // ... các field khác
        ]);
    }
}
```

### 4.5 Controller

```php
use App\Shared\Tenancy\Models\Organization;

class [Entity]Controller extends Controller
{
    public function create()
    {
        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();
        // ... lấy các data khác

        return view('[module]::[view]', compact(
            'organizations', 'defaultOrgId', 'orgLocked',
            // ... compact các var khác
        ));
    }

    public function edit([Entity] $entity)
    {
        [$organizations, , $orgLocked] = $this->_resolveOrganizations();
        // ... lấy các data khác

        return view('[module]::[view]', compact(
            'entity', 'organizations', 'orgLocked',
            // ... compact các var khác
        ));
    }

    /**
     * DN user (organization_id != null) → chỉ thấy org của họ, field bị locked.
     * Admin (organization_id = null)    → thấy tất cả org, chọn tự do qua TomSelect.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: int|null, 2: bool}
     */
    private function _resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;

        if ($userOrgId) {
            return [
                Organization::where('id', $userOrgId)->get(['id', 'name']),
                $userOrgId,
                true,  // orgLocked — DN user không thể thay đổi org
            ];
        }

        return [
            Organization::orderBy('name')->get(['id', 'name']),
            null,
            false, // orgLocked = false — Admin chọn tự do
        ];
    }
}
```

> **Quan trọng:** Dùng `auth()->user()->organization_id` — **KHÔNG** dùng `TenantContext::getOrganizationId()`.  
> TenantContext fallback về org đầu tiên khi admin (organization_id = null), làm admin bị nhận dạng sai thành DN user → field bị locked sai.

### 4.6 View — Create form (Blade)

**Tab-based form** — thêm `'organization_id'` vào `tabFields.basic` trong Alpine data:

```blade
x-data="{
    tab: 'basic',
    tabFields: {
        basic: ['organization_id', 'name', 'code', ...],
        ...
    },
    ...
}"
```

**Field HTML** — đặt đầu tiên trong grid, dùng `$orgLocked` để phân nhánh:

```blade
{{-- ── Tổ chức: LUÔN là field đầu tiên ─────────────────────────── --}}
<div class="form-control sm:col-span-2">
    <label class="label py-0 pb-1.5">
        <span class="label-text font-medium">Tổ chức <span class="text-error">*</span></span>
    </label>
    @if($orgLocked)
        {{-- DN user: không thể thay đổi --}}
        <input type="hidden" name="organization_id" value="{{ $organizations->first()->id }}">
        <input type="text" value="{{ $organizations->first()->name }}" readonly
               class="input input-bordered input-sm w-full bg-base-200 cursor-not-allowed">
        <p class="mt-1 text-xs text-base-content/40">Xác định từ tài khoản của bạn.</p>
    @else
        {{-- Admin: chọn tự do qua TomSelect --}}
        <select id="ts-organization" name="organization_id"
                class="select select-bordered select-sm w-full ts-init
                       @error('organization_id') select-error @enderror"
                data-ts-placeholder="— Chọn tổ chức —"
                data-req="Vui lòng chọn tổ chức">
            <option value="">— Chọn tổ chức —</option>
            @foreach($organizations as $org)
            <option value="{{ $org->id }}"
                {{ old('organization_id', $defaultOrgId ?? '') == $org->id ? 'selected' : '' }}>
                {{ $org->name }}
            </option>
            @endforeach
        </select>
        @error('organization_id')
            <p class="mt-1 text-xs text-error">{{ $message }}</p>
        @enderror
    @endif
</div>
```

### 4.7 View — Edit form (Blade)

Giống create, chỉ khác phần `selected`:

```blade
{{ old('organization_id', $entity->organization_id) == $org->id ? 'selected' : '' }}
```

`$orgLocked` vẫn dùng từ `_resolveOrganizations()` — không hardcode từ entity.

### 4.8 SCSS entry point

```scss
// Modules/[Name]/resources/assets/sass/[name].scss
@use 'tom-select';   // ← bắt buộc nếu form có bất kỳ <select> nào

// ... phần còn lại
```

### 4.9 JS — Entry point (thin wrapper)

```js
// Modules/[Name]/resources/assets/js/[name].js
import './pages/[entity]-form.js';
// Nếu có index page với Tabulator:
// import './pages/[entity]-index.js';
```

### 4.10 JS — Form page

```js
// Modules/[Name]/resources/assets/js/pages/[entity]-form.js
import { initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-[entity]-form]';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);   // global từ app.js
    initAllTomSelects(form);        // init tất cả [class="ts-init"] trong form
    // ... logic khác của form
});
```

---

## 5. Trường hợp đặc biệt

### 5.1 Model có bản ghi `is_global` — LeadPipelineStage, LeadSource

**Vấn đề:** Có bản ghi `is_global = 1` chia sẻ giữa tất cả org (template mặc định). Nếu extend `TenantAwareModel`, `OrganizationScope` filter `WHERE organization_id = ?` và **xóa** các bản ghi global khỏi kết quả. Ngoài ra, bảng chưa có cột `deleted_at` nên SoftDeletes cũng lỗi.

**Giải pháp:** Giữ `extends Model`. Quản lý `organization_id` explicit trong form và action.

```php
// ✅ Đúng cho model có is_global
class LeadPipelineStage extends Model
{
    protected $fillable = ['organization_id', 'name', ...];
    // Tự implement scoping trong queries khi cần
}
```

**Lưu ý:** Vẫn cần đủ 5 lớp còn lại (Data, Action, Controller, View, JS/SCSS). Chỉ khác ở lớp Model.

### 5.2 DB column khác tên chuẩn — Recruitment (`org_id`)

**Vấn đề:** Bảng `candidates` dùng cột `org_id` thay vì `organization_id`.

**Giải pháp:** Form field vẫn đặt tên `organization_id` (đúng chuẩn). Action map tường minh:

```php
// StoreCandidateAction.php
return Candidate::create([
    'org_id' => $data->organization_id,  // ← map: form field → DB column
    // ...
]);
```

Edit view — fallback dùng tên cột DB:

```blade
{{ old('organization_id', $candidate->org_id) == $org->id ? 'selected' : '' }}
```

**Không** đổi tên cột DB để tránh break migration và code hiện có.

### 5.3 Module chưa có cột `organization_id` — Survey

**Vấn đề:** Bảng `surveys` chưa có cột `organization_id`. Không thể thêm vào `$fillable` hay extend `TenantAwareModel` trước khi chạy migration.

**Quy trình bắt buộc:**

1. Tạo migration alter với `nullable()` (để không break dữ liệu cũ)
2. Chạy `php artisan migrate`
3. **Sau đó** mới chỉnh Model → Data → Action → Controller → Views

```php
// nullable để safe với dữ liệu cũ đã tồn tại
$table->foreignId('organization_id')
      ->nullable()
      ->after('id')
      ->constrained()
      ->restrictOnDelete();
```

### 5.4 JS dùng TomSelect trực tiếp (anti-pattern) — KcItem

**Vấn đề:** `kc-item-form.js` dùng `new window.TomSelect(el, {...})` trực tiếp theo ID, không dùng factory `initAllTomSelects`.

**Giải pháp tạm thời:** Thêm `'ts-organization'` vào danh sách ID trong forEach hiện có:

```js
['ts-organization', 'ts-type', 'ts-category', 'ts-visibility'].forEach(function (id) {
    var el = document.getElementById(id);
    if (!el) return;
    new window.TomSelect(el, { dropdownParent: 'body', create: false, plugins: ['clear_button'] });
});
```

**Việc cần làm (task riêng):** Refactor `kc-item-form.js` sang `initAllTomSelects(form)` + `createTs()` factory. Không ưu tiên vì hiện đang hoạt động đúng.

---

## 6. Quy trình áp dụng cho module mới

### Bước 1 — Kiểm tra trạng thái hiện tại

```bash
# Model extend gì?
grep -r "extends " Modules/[Name]/app/Models/

# Migration có organization_id không?
grep -r "organization_id" Modules/[Name]/database/migrations/

# Cột thực tế trong DB
php artisan db:show --table=[table_name]
```

### Bước 2 — Xác định trường hợp đặc biệt

Trả lời 3 câu hỏi:

1. **Model có bản ghi `is_global`?** → Giữ `extends Model`, quản lý explicit (xem 5.1)
2. **Bảng dùng tên cột khác `organization_id`?** → Map trong Action, dùng tên DB trong edit fallback (xem 5.2)
3. **Cột `organization_id` chưa tồn tại trong DB?** → Tạo migration nullable trước (xem 5.3)

### Bước 3 — Thực hiện theo thứ tự 6 lớp

```
[0] Migration (nếu cần)   → php artisan migrate
[1] Model                  → extend TenantAwareModel, organization_id đầu fillable
[2] StoreData              → $organization_id tham số đầu + rules()
[3] StoreAction            → 'organization_id' => $data->organization_id, key đầu create()
[4] Controller             → _resolveOrganizations() 3-tuple, cập nhật create() + edit()
[5] View + JS + SCSS       → field Tổ chức đầu form, $orgLocked, @use 'tom-select', initAllTomSelects()
```

### Bước 4 — Build và verify

```bash
npx vite build --config vite.config.backend.js
# Kiểm tra: build thành công, không có error
```

### Bước 5 — Test cases tối thiểu

| Test | Admin | DN user |
|---|---|---|
| Form create | Thấy TomSelect, có thể chọn org | Thấy readonly + hidden input |
| Submit không chọn org | Validation error | N/A (auto-locked) |
| Submit hợp lệ | Record tạo với org đã chọn | Record tạo với org của user |
| Form edit | TomSelect chọn đúng org record | Readonly đúng org |
| List records | Thấy theo org được filter | Chỉ thấy records của org mình |

---

## 7. Câu hỏi thường gặp

**Q: Tại sao `auth()->user()->organization_id` thay vì `TenantContext::getOrganizationId()`?**

A: `TenantContext` fallback về org đầu tiên khi user không có org (Admin). Điều này làm Admin bị nhận dạng sai thành DN user → field bị locked. `auth()->user()->organization_id` trả về `null` cho Admin → hiển thị TomSelect đúng.

**Q: Tại sao `$orgLocked` thay vì `$organizations->count() === 1`?**

A: Nếu hệ thống chỉ có 1 org tổng, Admin cũng bị locked — sai. `$orgLocked` là boolean explicit từ server, tách biệt intent (user bị giới hạn bởi account) khỏi data (số lượng org trong list trả về).

**Q: `BelongsToOrganization` trait có auto-set `organization_id` không?**

A: Có — nhưng vẫn phải truyền explicit từ `$data->organization_id` trong Action vì: (1) Queue job có thể chưa hydrate TenantContext, (2) API không qua tenant middleware → TenantContext null. Explicit > implicit.

**Q: `UpdateFooAction` có cần `organization_id` không?**

A: Không. Org của một record không thay đổi sau khi tạo. `UpdateFooData` không cần field này, `update([])` không include `organization_id`.

**Q: Dùng `withoutTenant()` khi nào?**

A: Khi cần query cross-tenant — ví dụ admin dashboard thống kê toàn hệ thống, hoặc lấy options với filter tùy chỉnh trên chính org mình: `Entity::withoutTenant()->where('organization_id', $orgId)->get()`.

**Q: TenantContext trong queue job thì sao?**

A: Extend `TenantAwareJob` (`app/Foundation/TenantAwareJob.php`). Nó serialize `organization_id` vào job payload và restore `TenantContext` khi job chạy.

**Q: Module có nhiều entity (KcItem + KcTag) thì sao?**

A: Mỗi entity cần đủ 6 lớp riêng. Không share StoreData hay StoreAction giữa các entity khác nhau.

**Q: SCSS cần `@use 'tom-select'` khi org field là readonly?**

A: Nếu form có nhánh `@else` (Admin case với TomSelect), cần. Để an toàn và nhất quán, include cho mọi module có form.
