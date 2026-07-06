# Form UI/UX Specification — Backend SaaS

> **Version:** 5.0  
> **Stack:** Laravel 13 · DaisyUI 5 · Tailwind CSS 4 · Alpine.js 3 · TomSelect · SCSS (sass) · Vite 8  
> **Gold Standard:** `Modules/Organization/resources/views/`  
> **Build:** `vite.config.backend.js` — **một build duy nhất** cho toàn backend

---

## Mục lục

1. [Triết lý thiết kế](#1-triết-lý-thiết-kế)
2. [Kiến trúc tổng thể](#2-kiến-trúc-tổng-thể)
3. [SCSS — Shared partials & Module](#3-scss)
4. [JS — Shared utils & Module page controllers](#4-js)
5. [Vite build — Cách đăng ký module mới](#5-vite-build)
6. [Blade — Cách load asset](#6-blade)
7. [Page Shell](#7-page-shell)
8. **[Quyết định bố cục form — Flat vs Tab](#8-quyết-định-bố-cục-form)** ← NEW v5
9. **[Flat Form (≤ ~10 trường)](#9-flat-form)**
10. **[Tab-Based Form (> 10 trường / ≥ 3 nhóm)](#10-tab-based-form)** ← NEW v5
11. **[Sidebar Publish Block](#11-sidebar-publish-block)** ← NEW v5
12. [Card Section](#12-card-section)
13. [Grid & bố cục cột](#13-grid)
14. [Form Control — cấu trúc bắt buộc](#14-form-control)
15. [Input types](#15-input-types)
16. [Validation](#16-validation)
17. **[Tab-Aware Submit Guard (JS)](#17-tab-aware-submit-guard)** ← NEW v5
18. **[Slug Auto-fill (JS)](#18-slug-auto-fill)** ← NEW v5
19. [Interactive states & Loading](#19-interactive-states)
20. [Submit Actions Bar](#20-submit-bar)
21. [Wizard Multi-step](#21-wizard)
22. [TomSelect](#22-tomselect)
23. [Ngôn ngữ](#23-ngôn-ngữ)
24. [Class reference](#24-class-reference)
25. [Anti-patterns](#25-anti-patterns)
26. [Checklist trước khi merge](#26-checklist)
27. **[Org Selector — Multi-tenant Form Pattern](#27-org-selector--multi-tenant-form-pattern)** ← NEW

---

## 1. Triết lý thiết kế

| Nguyên tắc | Biểu hiện cụ thể |
|---|---|
| **Clarity first** | Label rõ, placeholder có ví dụ, hint đúng chỗ |
| **Progressive disclosure** | Form ≥ 3 nhóm không liên quan → tab-based; quy trình bắt buộc tuần tự → wizard |
| **Immediate feedback** | Lỗi hiện ngay tại field sau blur; lỗi tab ẩn → Toast + tự chuyển tab |
| **Spatial consistency** | Cùng loại element → cùng size, spacing, màu trên mọi module |
| **No scroll forms** | Form nhiều trường phải dùng tab để tránh cuộn trang |
| **Mobile first** | 1 cột mobile, 2 cột desktop |
| **Tag Select** | Luôn áp dụng thư viện TomSelect vào tag select trong giao diện |

**Hệ thống kích thước — không tùy biến theo module:**
```
Input height:    input-sm  = 2.25rem (36px)
Card gap:        space-y-5 = 1.25rem (20px)   ← giữa các card
Field gap:       gap-4     = 1rem    (16px)    ← giữa fields trong card
Label→Input:     pb-1.5    = 6px
Sidebar width:   268px     (xl:grid-cols-[1fr_268px])
```

> ⚠️ **v5 breaking change:** Không dùng `max-w-3xl` làm container form.
> Form dùng layout grid full-width + sidebar thay thế.

---

## 2. Kiến trúc tổng thể

### 2.1 Sơ đồ cây file

```
minhan/
├── vite.config.backend.js
│
├── resources/
│   ├── css/app.css
│   ├── scss/
│   │   ├── _tokens.scss
│   │   ├── _mixins.scss
│   │   ├── _form-patterns.scss
│   │   └── _tom-select.scss
│   └── js/
│       ├── app.js
│       ├── modules/
│       │   ├── tom-select.js
│       │   ├── jodit.js
│       │   ├── tabulator.js
│       │   ├── toastify.js          ← Toast.success/error/warning/info
│       │   └── ...
│       └── shared/
│           ├── form-controller.js
│           ├── wizard-controller.js
│           └── tom-select-factory.js
│
└── Modules/
    └── [Name]/
        └── resources/
            ├── assets/
            │   ├── sass/[name].scss
            │   └── js/
            │       ├── [name].js
            │       └── pages/
            │           ├── [entity]-form.js
            │           └── [entity]-index.js
            └── views/
                ├── create.blade.php
                ├── edit.blade.php
                ├── show.blade.php
                └── index.blade.php
```

### 2.2 Phân tầng bundle

```
Tầng 0 — Core (tải mọi trang)
    app.css + app.js
    → Tailwind, DaisyUI, shell layout, jQuery, Alpine, initFormValidation

Tầng 1 — Shared SCSS  resources/scss/_*.scss
    → @use bởi module SCSS, không build riêng

Tầng 2 — Shared JS    resources/js/shared/
    → Tree-shaken, build vào shared-utils.[hash].js

Tầng 3 — Widget libs  resources/js/modules/
    → Lazy per-page: TomSelect, Jodit, Tabulator, Toastify...

Tầng 4 — Module assets Modules/[Name]/resources/assets/
    → Lazy per-page: module CSS + JS
```

---

## 3. SCSS

### 3.1 Shared partials

| File | Nội dung |
|---|---|
| `_tokens.scss` | `$primary`, `$border`, `$text-muted`, `$input-h`, `$radius-*` |
| `_mixins.scss` | `input-base`, `focus-ring`, `card-base`, `md()`, `skeleton` |
| `_form-patterns.scss` | `.color-picker-combo`, `.field-readonly`, `.tag-checkbox-group`, `.wizard-step-dot`, `.form-submit-bar` |
| `_tom-select.scss` | Override TomSelect — tự follow DaisyUI dark/light |

### 3.2 Module SCSS entry

```scss
// Modules/Organization/resources/assets/sass/organization.scss
@use 'form-patterns';
@use 'tom-select';
// Không cần partial riêng khi chỉ dùng DaisyUI + shared
```

### 3.3 Quy tắc

- Không hardcode màu — dùng `$token` từ `_tokens.scss`
- Không `@import "tailwindcss"` trong module SCSS — chỉ ở `app.css`
- CSS đặc thù module → `_[name]-components.scss`

---

## 4. JS

### 4.1 Globals từ core (không cần import)

| Global | Nguồn | Mô tả |
|---|---|---|
| `window.Alpine` | `app.js` | Alpine.js 3 |
| `window.$` | `app.js` | jQuery |
| `window.initFormValidation` | `app.js` | Validate form bằng data-attr |
| `window.Toast` | `toastify.js` | `Toast.success/error/warning/info` |
| `window.TomSelect` | `tom-select.js` | TomSelect class |
| `window.initTomSelect` | `tom-select.js` | Factory helper |
| `window.initOrgAddress` | `tom-select.js` | Province/Ward cascade |
| `window.initJoditAll` | `jodit.js` | Khởi tạo rich text |
| `window.initAllDatePickers` | `flatpickr.js` | Auto-init mọi `input.fp-init` trong container (altInput `d/m/Y`, submit `Y-m-d`) |

> `window.Toast` chỉ có sau khi `@vite(['resources/js/modules/toastify.js'])` được load trong blade.

### 4.2 Module page controller — cấu trúc chuẩn

```js
// Modules/[Name]/resources/assets/js/pages/[entity]-form.js

// ── Constants ──────────────────────────────────────────────────────────────
const FORM_SEL = '[data-[entity]-form]';

// ── Entry point ────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);          // global từ app.js
    window.initAllDatePickers?.(form);    // chỉ nếu form có date field (fp-init)
    _initJodit(form);                     // chỉ nếu form có rich text
    _setupTabGuard(form);                 // chỉ nếu form dùng tab
    _setupSlugAutoFill(form);             // chỉ nếu form có slug
});
```

**Nguyên tắc:**
- Không viết logic JS > 5 dòng trong `<script>` của blade
- `Alpine.data(...)` đăng ký trong JS file, trong event `alpine:init`
- `initFormValidation` là global — không cần import

---

## 5. Vite build

### 5.1 Đăng ký module mới

**Bước 1 — Tạo file:**
```
Modules/[Name]/resources/assets/sass/[name].scss
Modules/[Name]/resources/assets/js/[name].js
Modules/[Name]/resources/assets/js/pages/[entity]-form.js
```

**Bước 2 — `vite.config.backend.js`:**
```js
const MODULE_ENTRIES = [
  // ...
  'Modules/[Name]/resources/assets/sass/[name].scss',
  'Modules/[Name]/resources/assets/js/[name].js',
];
// JS_OUTPUT:  '[name]': 'assets/modules/[name].[hash].js'
// CSS_OUTPUT: '[name].css': 'assets/modules/[name].[hash].css'
```

**Bước 3 — Blade:**
```blade
@push('styles')
    @vite(['Modules/[Name]/resources/assets/sass/[name].scss'], 'build/backend')
@endpush
@push('scripts')
    @vite(['Modules/[Name]/resources/assets/js/[name].js'], 'build/backend')
@endpush
```

### 5.2 Alias có sẵn

| Alias | Trỏ tới |
|---|---|
| `@` | `resources/` |
| `@js` | `resources/js/` |
| `@shared` | `resources/js/shared/` |

---

## 6. Blade

### 6.1 Load asset

```blade
@push('styles')
    @vite(['Modules/[Name]/resources/assets/sass/[name].scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',   ← nếu cần Toast
        'resources/js/modules/flatpickr.js',  ← nếu có date field (fp-init)
        'resources/js/modules/tom-select.js', ← nếu có select (ts-init)
        'resources/js/modules/jodit.js',      ← nếu có rich text
        'Modules/[Name]/resources/assets/js/[name].js',
    ], 'build/backend')
@endpush
```

**Thứ tự quan trọng:** `toastify` → `flatpickr` → `tom-select` → module JS.

### 6.2 Truyền server data vào Alpine

```blade
<div x-data="{
    tab: 'basic',
    errs: {{ Js::from($errors->keys()) }},
    ...
}">
```

Dùng `Js::from()` thay `@json()` khi trong attribute HTML — escape đúng ký tự đặc biệt.

### 6.3 Flash message

```php
// Controller — layout tự xử lý session flash
return redirect()->route('...index')->with('success', 'Tạo thành công');
return redirect()->back()->with('error', 'Có lỗi xảy ra');
```

---

## 7. Page Shell

```blade
@section('breadcrumb')
<nav class="breadcrumb-nav">
    <a href="{{ route('backend.dashboard') }}">Trang chủ</a>
    <span class="sep">›</span>
    <a href="{{ route('backend.[module].index') }}">Tên module</a>
    <span class="sep">›</span>
    <span class="current">Thêm mới</span>
</nav>
@endsection

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Tiêu đề trang</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Mô tả ngắn</p>
    </div>
    <a href="{{ route('...index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" ...>← arrow</svg>
        Quay lại
    </a>
</div>
```

---

## 8. Quyết định bố cục form

### 8.1 Decision tree

```
Đếm số trường thông tin cần nhập
        │
        ├── ≤ 10 trường, cùng ngữ cảnh
        │       └──► FLAT FORM  (Section 9)
        │            Ví dụ: form Tag, form cài đặt, form đổi mật khẩu
        │
        ├── 10–20 trường HOẶC từ 3 nhóm thông tin riêng biệt
        │       └──► TAB-BASED FORM  (Section 10)
        │            Ví dụ: form Tổ chức, form Khách hàng CRM, form Nhân viên HR
        │
        └── Quy trình bắt buộc tuần tự, có xác nhận từng bước
                └──► WIZARD  (Section 21)
                     Ví dụ: form Onboarding, tạo Workflow, Khảo sát
```

### 8.2 Tiêu chí phân loại chi tiết

| Tiêu chí | Flat | Tab | Wizard |
|---|:---:|:---:|:---:|
| Số trường | ≤ 10 | 10–30+ | bất kỳ |
| Số nhóm logic | 1–2 | 3–5 | 3+ (tuần tự) |
| User có thể bỏ qua nhóm | — | ✓ | ✗ |
| Cần xác nhận từng bước | — | — | ✓ |
| Có thể quay lại chỉnh | — | ✓ | ✓ |
| Không được submit thiếu bước | — | — | ✓ |

### 8.3 Ví dụ phân loại trong hệ thống

| Module / Form | Phân loại | Lý do |
|---|---|---|
| Tag — Tạo tag màu | Flat | 3 trường: tên, màu, trạng thái |
| User — Đổi mật khẩu | Flat | 2 trường: mật khẩu mới, xác nhận |
| User — Tạo tài khoản | Tab | 10+ trường: thông tin cá nhân + phân quyền + mật khẩu |
| Organization — Tạo/Sửa | Tab | 10+ trường: 3 nhóm rõ ràng |
| Lead/CRM — Tạo cơ hội | Tab | 15+ trường: thông tin + liên hệ + phân loại |
| HR — Hồ sơ nhân viên | Tab | 20+ trường: cơ bản + công việc + địa chỉ + liên hệ |
| Assessment — Tạo đánh giá | Wizard | Tuần tự: cấu hình → câu hỏi → phân phối |

---

## 9. Flat Form

Dùng khi ≤ 10 trường hoặc 1–2 nhóm cùng ngữ cảnh.

### 9.1 Cấu trúc blade

```blade
@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">...</div>

{{-- Error banner --}}
@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <svg class="w-5 h-5 shrink-0 mt-0.5" .../>
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('...store') }}" novalidate data-[entity]-form>
    @csrf

    {{-- Một hoặc vài card section --}}
    <div class="space-y-5">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-5">Tên nhóm</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- fields --}}
                </div>
            </div>
        </div>
    </div>

    {{-- Submit bar --}}
    <div class="flex gap-2 pt-4 mt-2 border-t border-base-200">
        <button type="submit" class="btn btn-primary btn-sm gap-1.5">Tạo [entity]</button>
        <a href="{{ route('...index') }}" class="btn btn-ghost btn-sm">Hủy</a>
    </div>

</form>
@endsection
```

### 9.2 `old()` — create vs edit

```blade
{{-- Create --}}
<input value="{{ old('name') }}">

{{-- Edit: old() fallback về giá trị model --}}
<input value="{{ old('name', $model->name) }}">
<option {{ old('status', $model->status->value) === 'active' ? 'selected' : '' }}>
```

**Quy tắc:** mọi field trong edit form phải dùng `old('field', $model->field)`.

---

## 10. Tab-Based Form

Dùng khi > 10 trường hoặc có từ 3+ nhóm thông tin riêng biệt.

### 10.1 Layout tổng thể

```
┌─────────────────────────────────┬──────────────────┐
│  [Tab 1] [Tab 2] [Tab 3]        │                  │
│  ─────────────────────────────  │   SIDEBAR        │
│                                 │   (sticky)       │
│  Chỉ hiện 1 tab tại 1 thời điểm│                  │
│  → không bao giờ scroll form    │   - Trạng thái   │
│                                 │   - Submit/Hủy   │
│  [← Trước]       [Tiếp theo →] │   - Meta         │
└─────────────────────────────────┴──────────────────┘

Grid: xl:grid-cols-[1fr_268px] gap-6 items-start
```

### 10.2 Alpine x-data — cấu trúc

Tab state quản lý bằng Alpine inline (đủ đơn giản, không cần file JS riêng):

```blade
<div x-data="{
    tab: 'basic',
    tabFields: {
        basic:   ['name', 'tax_code'],
        contact: ['email', 'phone'],
        address: ['province_code', 'ward_code'],
    },
    errs: {{ Js::from($errors->keys()) }},
    errCount(t) {
        return this.tabFields[t].filter(f => this.errs.includes(f)).length;
    },
    init() {
        // Tự chuyển tab có lỗi server đầu tiên khi page load
        const order = Object.keys(this.tabFields);
        for (const t of order) {
            if (this.errCount(t) > 0) { this.tab = t; break; }
        }
    }
}">
```

**Nguyên tắc:**
- `tabFields` khai báo đúng tên field của từng tab → dùng cho `errCount()`
- **Phải khai báo ĐẦY ĐỦ tất cả field có server-side validation** (required, format, exists...) — nếu thiếu, `errCount()` trả về 0 → badge lỗi không hiện → `init()` không tự chuyển đúng tab → user bị mắc
- Field tùy chọn (nullable, không validate) không cần khai báo
- `errs` là `$errors->keys()` từ server — tự động map về đúng tab

> **⚠️ Lỗi thường gặp:** Thêm field vào form nhưng quên thêm vào `tabFields` → server trả lỗi nhưng tab badge không hiện, người dùng không biết lỗi ở đâu. **Mỗi khi thêm rule validation server, kiểm tra lại `tabFields`.**

### 10.3 Tab navigation bar

```blade
<div class="border-b border-base-200 px-6">
    <nav class="flex -mb-px" role="tablist" aria-label="Form sections">

        <button type="button" role="tab" :aria-selected="tab === 'basic'"
                @click="tab = 'basic'"
                class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                :class="tab === 'basic'
                    ? 'border-primary text-primary'
                    : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
            Thông tin cơ bản
            <span x-show="errCount('basic') > 0" x-text="errCount('basic')"
                  class="badge badge-error badge-xs"></span>
        </button>

        {{-- Thêm tab tiếp theo với cùng pattern --}}

    </nav>
</div>
```

### 10.4 Tab panels

```blade
<div class="p-6">

    {{-- data-tab-label: JS đọc để hiện trong Toast thay vì hardcode --}}
    <div x-show="tab === 'basic'" data-tab-label="Thông tin cơ bản" class="space-y-4">
        {{-- fields --}}

        {{-- Footer điều hướng --}}
        <div class="flex justify-end pt-2">
            <button type="button" @click="tab = 'contact'" class="btn btn-ghost btn-sm gap-1.5">
                Tiếp theo: Liên hệ
                <svg class="w-4 h-4" ...>→ arrow</svg>
            </button>
        </div>
    </div>

    <div x-show="tab === 'contact'" data-tab-label="Liên hệ" class="space-y-4">
        {{-- fields --}}
        <div class="flex items-center justify-between pt-2">
            <button type="button" @click="tab = 'basic'" class="btn btn-ghost btn-sm gap-1.5">
                <svg ...>← arrow</svg> Thông tin cơ bản
            </button>
            <button type="button" @click="tab = 'address'" class="btn btn-ghost btn-sm gap-1.5">
                Tiếp theo: Địa chỉ <svg ...>→ arrow</svg>
            </button>
        </div>
    </div>

    <div x-show="tab === 'address'" data-tab-label="Địa chỉ" class="space-y-4">
        {{-- fields --}}
        <div class="flex items-center justify-between pt-2">
            <button type="button" @click="tab = 'contact'" class="btn btn-ghost btn-sm gap-1.5">
                <svg ...>← arrow</svg> Liên hệ
            </button>
            <span class="text-xs text-base-content/40">Nhấn <strong>Lưu</strong> ở bên phải khi xong</span>
        </div>
    </div>

</div>
```

> **Bắt buộc:** mỗi panel phải có `data-tab-label="..."` — JS đọc attribute này để hiện trong Toast thông báo lỗi. Không hardcode tên tab trong file JS.

### 10.5 Skeleton toàn bộ tab form

```blade
@section('content')
<div x-data="{ tab: 'basic', tabFields: {...}, errs: {{ Js::from($errors->keys()) }},
               errCount(t) { return this.tabFields[t].filter(f => this.errs.includes(f)).length; },
               init() { /* switch to first error tab */ } }">

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">...</div>

{{-- Error banner --}}
@if($errors->any())..@endif

<form method="POST" action="..." novalidate data-[entity]-form>
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- Card chính: tab nav + panels --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="border-b border-base-200 px-6">
                <nav class="flex -mb-px" role="tablist">
                    {{-- Tab buttons --}}
                </nav>
            </div>
            <div class="p-6">
                {{-- Tab panels (x-show + data-tab-label) --}}
            </div>
        </div>

        {{-- Sidebar sticky --}}
        <div class="xl:sticky xl:top-4 space-y-4">
            {{-- Publish block (Section 11) --}}
        </div>

    </div>
</form>
</div>
@endsection
```

---

## 11. Sidebar Publish Block

Block sidebar xuất hiện trong cả flat form (nếu cần sidebar) và tab form. Mục đích: tổng hợp trạng thái + action submit vào 1 vị trí cố định, không bao giờ bị cuộn khuất.

### 11.1 Create form

```blade
<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body p-4">

        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
            Xuất bản
        </p>

        <div class="form-control mb-4">
            <label class="label py-0 pb-1">
                <span class="label-text text-xs font-medium">
                    Trạng thái <span class="text-error">*</span>
                </span>
            </label>
            <select name="status"
                    class="select select-bordered select-sm w-full @error('status') select-error @enderror">
                <option value="active"    {{ old('status', 'active') === 'active'    ? 'selected' : '' }}>Hoạt động</option>
                <option value="inactive"  {{ old('status') === 'inactive'            ? 'selected' : '' }}>Không hoạt động</option>
                <option value="suspended" {{ old('status') === 'suspended'           ? 'selected' : '' }}>Tạm khóa</option>
            </select>
            @error('status')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </div>

        {{-- 2 nút ngang nhau — không full-width stacked --}}
        <div class="flex gap-2">
            <a href="{{ route('...index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
            <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                <svg class="w-3.5 h-3.5" ...>+ icon</svg>
                Tạo mới
            </button>
        </div>

        <p class="text-center text-xs text-base-content/30 mt-2.5">
            <span class="text-error">*</span> là trường bắt buộc
        </p>

    </div>
</div>
```

### 11.2 Edit form (thêm meta timestamps)

```blade
<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body p-4">

        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
            Xuất bản
        </p>

        {{-- Status select (như trên, old() fallback về model) --}}
        <div class="form-control mb-3">...</div>

        {{-- Meta: 1 dòng inline, không block riêng --}}
        <div class="flex justify-between text-xs text-base-content/40 mb-4 px-0.5">
            <span>Tạo {{ $model->created_at->format('d/m/Y') }}</span>
            <span>Sửa {{ $model->updated_at->diffForHumans() }}</span>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('...show', $model) }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
            <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                <svg class="w-3.5 h-3.5" ...>✓ icon</svg>
                Lưu lại
            </button>
        </div>

        <p class="text-center text-xs text-base-content/30 mt-2.5">
            <span class="text-error">*</span> là trường bắt buộc
        </p>

    </div>
</div>
```

### 11.3 Nguyên tắc thiết kế

| Nguyên tắc | Lý do |
|---|---|
| Title dùng `text-xs uppercase tracking-wide` (không phải `h3`) | Phân biệt rõ với section title trong card chính |
| Label trạng thái dùng `text-xs` | Sidebar nhỏ hơn main content |
| 2 nút `flex-1` nằm ngang | Full-width stacked button trông thừa và nặng |
| Meta timestamps trên 1 dòng | Không dùng `dl/dt/dd` block riêng — quá nặng cho sidebar |
| Padding `p-4` (không phải `card-body` default) | Card sidebar cần compact hơn card main |

---

## 12. Card Section

Card dùng trong cả flat form và tab panel:

```blade
<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body">

        <h2 class="card-title text-base mb-5">
            <svg class="w-4 h-4 text-primary" ...>icon</svg>
            Tên nhóm
        </h2>

        <div class="space-y-4">
            {{-- fields --}}
        </div>

    </div>
</div>
```

**Card title pattern:**
- `card-title text-base mb-5` — không dùng `mb-2` hay `mb-4`
- Icon inline `w-4 h-4 text-primary` — không dùng icon box có background màu
- Không cần subtitle dưới header — nếu cần giải thích thêm, dùng hint dưới từng field

**Card separator (khi dùng bên trong flat form):**
```blade
{{-- Giữa các nhóm trong cùng card body --}}
<div class="divider my-4 text-xs text-base-content/30">Địa chỉ</div>
```

---

## 13. Grid

### 13.1 Grid field trong card

```blade
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="form-control sm:col-span-2">  {{-- tên — full width --}}
    <div class="form-control">               {{-- mã số thuế --}}
    <div class="form-control">               {{-- ngành nghề --}}
</div>
{{-- Textarea ngoài grid --}}
<div class="form-control mt-4">
    <textarea ...>
</div>
```

### 13.2 Khi nào full-width vs half

| Field | Width |
|---|---|
| Tên, tiêu đề, URL, website, mô tả | Full (`sm:col-span-2`) |
| Email, phone, mã số, ngành, ngày | Half |
| Slug | Half (nằm dưới tên) |
| Textarea, rich text | Full (ngoài grid) |

### 13.3 Grid tổng thể form (tab + sidebar)

```blade
{{-- Tab form --}}
<div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">
    <div>{{-- card chính --}}</div>
    <div class="xl:sticky xl:top-4 space-y-4">{{-- sidebar --}}</div>
</div>

{{-- Flat form — không dùng grid tổng thể, để form chiếm full width --}}
<form class="space-y-5">...</form>
```

---

## 14. Form Control

Cấu trúc bắt buộc cho mọi field:

```blade
<div class="form-control">
    <label class="label py-0 pb-1.5">
        <span class="label-text font-medium">
            Tên field <span class="text-error">*</span>
        </span>
        <span class="label-text-alt text-base-content/40 text-xs">Gợi ý / tuỳ chọn</span>
    </label>
    <input type="text" name="field" value="{{ old('field') }}"
           class="input input-bordered input-sm w-full @error('field') input-error @enderror"
           placeholder="VD: ...">
    <p class="mt-1 text-xs text-base-content/40">Hint text nếu cần.</p>
    @error('field')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
</div>
```

---

## 15. Input types

### Text / Email / URL

```blade
<input type="text" name="name" value="{{ old('name') }}"
       class="input input-bordered input-sm w-full @error('name') input-error @enderror"
       placeholder="VD: Nguyễn Văn A">

<input type="email" name="email" value="{{ old('email') }}"
       data-val-email="Email không đúng định dạng"
       class="input input-bordered input-sm w-full @error('email') input-error @enderror"
       placeholder="contact@company.com">

<input type="url" name="website" value="{{ old('website') }}"
       data-val-url="URL phải bắt đầu bằng https://"
       class="input input-bordered input-sm w-full @error('website') input-error @enderror"
       placeholder="https://company.com">
```

### Slug

```blade
{{-- Đặt ngay dưới trường "Tên" để thể hiện mối quan hệ --}}
<div class="form-control">
    <label class="label py-0 pb-1.5">
        <span class="label-text font-medium">Slug</span>
        {{-- Create: --}} <span class="label-text-alt text-xs text-base-content/40">Tự động tạo nếu để trống</span>
        {{-- Edit:   --}} <span class="label-text-alt text-xs text-base-content/40">Thận trọng khi thay đổi</span>
    </label>
    <input type="text" name="slug" value="{{ old('slug') }}"
           class="input input-bordered input-sm w-full font-mono @error('slug') input-error @enderror"
           placeholder="ten-slug-vd">
    <p class="mt-1 text-xs text-base-content/40">
        Chỉ dùng chữ thường, số và dấu <code class="bg-base-200 px-1 rounded">-</code>
    </p>
    @error('slug')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
</div>
```

JS auto-fill: xem [Section 18](#18-slug-auto-fill).

### Select

> ⚠️ **Bắt buộc:** Mọi `<select>` trong form đều phải dùng TomSelect. Thêm class `ts-init` và `id="ts-[field]"` — xem [Section 22](#22-tomselect) để biết cách khởi tạo.

```blade
<select id="ts-status" name="status"
        class="select select-bordered select-sm w-full ts-init
               @error('status') select-error @enderror"
        data-ts-placeholder="— Chọn trạng thái —">
    <option value="">— Chọn trạng thái —</option>
    <option value="active"   {{ old('status', 'active') === 'active'   ? 'selected' : '' }}>Hoạt động</option>
    <option value="inactive" {{ old('status') === 'inactive'           ? 'selected' : '' }}>Không hoạt động</option>
</select>
```

- `id="ts-[field]"` — bắt buộc, dùng để `createTs` override khi cần config đặc biệt
- `class="... ts-init"` — trigger auto-init bởi `initAllTomSelects(form)`
- `data-ts-placeholder="..."` — placeholder cho TomSelect (nếu không có, đọc từ `<option value="">`)
- **Ngoại lệ:** Selects có cascade (VD: ward phụ thuộc province) — **không** thêm `ts-init`, khởi tạo thủ công trong JS

### Textarea / Rich text

```blade
{{-- Thuần --}}
<textarea name="note" rows="4"
          class="textarea textarea-bordered textarea-sm w-full"
          placeholder="Ghi chú...">{{ old('note') }}</textarea>

{{-- Jodit --}}
<textarea name="description"
          class="jodit-editor textarea textarea-bordered textarea-sm w-full"
          data-jodit-preset="compact">{{ old('description') }}</textarea>
```

### Checkbox

```blade
<label class="flex items-start gap-2.5 cursor-pointer select-none group">
    <input type="checkbox" name="is_active" value="1"
           class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
           {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
    <div>
        <span class="text-sm font-medium group-hover:text-primary transition-colors">Label</span>
        <p class="text-xs text-base-content/50 mt-0.5">Mô tả phụ</p>
    </div>
</label>
```

### Join (Input + Select)

```blade
<div class="join w-full">
    <input type="number" name="value"
           class="input input-bordered input-sm join-item flex-1" placeholder="0">
    <select name="currency" class="select select-bordered select-sm join-item w-24">
        <option value="VND" {{ old('currency', 'VND') === 'VND' ? 'selected' : '' }}>VND</option>
    </select>
</div>
```

### Date picker (Flatpickr) — `fp-init` pattern

Khi form có ≥ 1 date field, dùng class `fp-init` để auto-init toàn bộ:

```blade
{{-- Create --}}
<input type="text" name="opened_at" id="fp-opened-at"
       value="{{ old('opened_at') }}"
       class="input input-bordered input-sm w-full fp-init @error('opened_at') input-error @enderror"
       placeholder="DD/MM/YYYY">

{{-- Edit: truyền Y-m-d format để Flatpickr parse; altInput hiển thị d/m/Y --}}
<input type="text" name="opened_at" id="fp-opened-at"
       value="{{ old('opened_at', $model->opened_at?->format('Y-m-d') ?? '') }}"
       class="input input-bordered input-sm w-full fp-init @error('opened_at') input-error @enderror"
       placeholder="DD/MM/YYYY">
```

**Quy tắc:**
- `id="fp-[field-name]"` — bắt buộc, dùng để override config nếu cần
- `class="... fp-init"` — trigger auto-init bởi `initAllDatePickers(form)`
- `value` — truyền `Y-m-d` (edit) hoặc `old()` để Flatpickr parse chính xác
- Display luôn là `d/m/Y` — Flatpickr tự chuyển qua `altFormat`
- Submit luôn là `Y-m-d` — tương thích với Laravel `'nullable', 'date'` validation
- Không dùng `type="date"` native
- Không cần `readonly` — Flatpickr `altInput` tự xử lý

**Gọi trong page controller (1 lần, init toàn bộ fields):**

```js
window.initAllDatePickers?.(form);   // init tất cả input.fp-init trong form
```

**`data-fp-mode` (tùy chọn):**

| Giá trị | Hành vi |
|---|---|
| `single` (mặc định) | Chọn 1 ngày |
| `range` | Chọn khoảng ngày |
| `datetime` | Chọn ngày + giờ |

```blade
<input ... data-fp-mode="datetime" class="... fp-init">
```

**Override thủ công (1 field cần config đặc biệt — không thêm `fp-init`):**

```js
initDatePicker(form.querySelector('[name="special_date"]'), { minDate: 'today' });
```

**Thứ tự load scripts:**

```blade
@push('scripts')
    @vite([
        'resources/js/modules/flatpickr.js',   ← trước module JS
        'Modules/[Name]/resources/assets/js/[name].js',
    ], 'build/backend')
@endpush
```

### Address picker

```blade
<x-address-picker
    :province-value="old('province_code', $model->province_code ?? '')"
    :ward-value="old('ward_code', $model->ward_code ?? '')"
    instance-id="[unique-per-page-id]"
    :required="true"
/>
```
`instance-id` phải unique trên trang.

---

## 16. Validation

### 16.1 Khi nào dùng phương án nào

| Phương án | Dùng khi |
|---|---|
| `data-[entity]-form` + `initFormValidation` | Form đơn giản, validation basic |
| `makeFormController` (Alpine) | Form phức tạp, cross-field, real-time |
| Kết hợp | Cho phép — `initFormValidation` bắt HTML5 types, Alpine bắt logic phức tạp |

### 16.2 Server-side

```blade
<input class="input input-bordered input-sm @error('field') input-error @enderror">
@error('field')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
```

### 16.3 Custom messages tiếng Việt — bắt buộc

> **⚠️ Không bao giờ để Laravel dùng message mặc định tiếng Anh** (VD: "The impact category field is required."). Luôn truyền `$messages` vào `validate()`.

```php
// Controller
return $request->validate($rules, [
    // required
    'name.required'              => 'Vui lòng nhập tên.',
    'category.required'          => 'Vui lòng chọn danh mục.',
    'organization_id.required'   => 'Vui lòng chọn tổ chức.',
    // required_if
    'organization_id.required_if' => 'Vui lòng chọn tổ chức khi phạm vi là "Riêng tổ chức cụ thể".',
    // string
    'name.max'                   => 'Tên không được vượt quá :max ký tự.',
    'name.min'                   => 'Tên phải có ít nhất :min ký tự.',
    // number
    'amount.numeric'             => 'Số tiền phải là số.',
    'amount.min'                 => 'Số tiền phải ≥ :min.',
    'amount.max'                 => 'Số tiền không được vượt quá :max.',
    // date
    'period_start.date'          => 'Kỳ bắt đầu không đúng định dạng ngày.',
    'period_end.after_or_equal'  => 'Kỳ kết thúc phải sau hoặc bằng kỳ bắt đầu.',
    // relation
    'employee_id.exists'         => 'Nhân viên được chọn không hợp lệ.',
    'organization_id.exists'     => 'Tổ chức được chọn không hợp lệ.',
    // unique
    'code.unique'                => 'Mã này đã tồn tại trong hệ thống.',
    // email / url
    'email.email'                => 'Email không đúng định dạng.',
    'website.url'                => 'URL phải bắt đầu bằng https://',
]);
```

**Bảng rule → message template:**

| Rule | Template |
|---|---|
| `required` | `Vui lòng nhập/chọn [tên field].` |
| `required_if:field,value` | `Vui lòng nhập/chọn [tên field] khi [điều kiện].` |
| `max` (string) | `[Tên field] không được vượt quá :max ký tự.` |
| `min` (string) | `[Tên field] phải có ít nhất :min ký tự.` |
| `numeric` | `[Tên field] phải là số.` |
| `min` (number) | `[Tên field] không được âm.` hoặc `phải ≥ :min.` |
| `max` (number) | `[Tên field] không được vượt quá :max.` |
| `date` | `[Tên field] không đúng định dạng ngày.` |
| `after_or_equal` | `[Tên field] phải sau hoặc bằng [field kia].` |
| `exists` | `[Tên field] được chọn không hợp lệ.` |
| `unique` | `[Tên field] này đã tồn tại.` |
| `in` | `[Tên field] không hợp lệ.` |
| `email` | `[Tên field] không đúng định dạng email.` |
| `url` | `[Tên field] phải bắt đầu bằng https://.` |
| `integer` | `[Tên field] phải là số nguyên.` |
| `boolean` | `[Tên field] không hợp lệ.` |
| `mimes` | `File phải là định dạng: :values.` |
| `max` (file) | `File không được vượt quá :max KB.` |

### 16.4 Client-side — data attributes

```blade
data-req="Vui lòng nhập tên"
data-val-email="Email không đúng định dạng"
data-val-url="URL phải bắt đầu bằng https://"
data-val-maxlength="20"
data-val-minlength="3"
```

Kích hoạt trong page controller JS:
```js
document.addEventListener('DOMContentLoaded', () => {
    initFormValidation('[data-[entity]-form]');
});
```

### 16.5 Validation trong tab form

`initFormValidation` validate **toàn bộ form** khi submit, kể cả field ở tab ẩn. Kết hợp với Tab-Aware Submit Guard ([Section 17](#17-tab-aware-submit-guard)) để:
1. Guard chạy trước (capture phase) → phát hiện lỗi ở tab ẩn → chuyển tab + Toast
2. `initFormValidation` chạy sau (bubble phase) → highlight inline error trên tab đã visible

---

## 17. Tab-Aware Submit Guard

### 17.1 Vấn đề

Với tab form dùng `x-show` (không remove DOM), `initFormValidation` validate đúng nhưng `scrollIntoView` không tìm thấy field ẩn → user submit không được mà không biết lỗi ở đâu.

### 17.2 Giải pháp

```js
// Trong pages/[entity]-form.js

const RE_TAB_XSHOW = /tab\s*===\s*['"](\w+)['"]/; // compile 1 lần

function _setupTabGuard(form) {
    let wrapper = null; // cache Alpine wrapper

    form.addEventListener('submit', (e) => {
        const errors = _collectHiddenErrors(form);
        if (!errors.size) return; // Không có lỗi ở tab ẩn → initFormValidation lo

        e.preventDefault();
        wrapper ??= form.closest('[x-data]') ?? document.querySelector('[x-data]');
        _switchAlpineTab(wrapper, errors.keys().next().value);
        _toastHiddenErrors(errors);
    }, /* capture */ true); // capture=true → chạy TRƯỚC initFormValidation (bubble)
}

function _collectHiddenErrors(form) {
    const map = new Map();
    for (const field of form.querySelectorAll('[data-req]')) {
        if (field.value.trim()) continue;
        const panel = field.closest('[x-show]');
        if (!panel || panel.style.display !== 'none') continue;
        const tabKey = RE_TAB_XSHOW.exec(panel.getAttribute('x-show') ?? '')?.[1];
        if (!tabKey) continue;
        if (!map.has(tabKey)) map.set(tabKey, { label: panel.dataset.tabLabel ?? tabKey, fields: [] });
        map.get(tabKey).fields.push(_resolveFieldLabel(field));
    }
    return map;
}

function _resolveFieldLabel(field) {
    return field.closest('.form-control')?.querySelector('.label-text')
        ?.textContent.replace(/\s*\*\s*$/, '').trim()
        ?? field.placeholder ?? field.name ?? 'Trường bắt buộc';
}

function _switchAlpineTab(wrapper, tabKey) {
    if (!wrapper) return;
    try {
        const data = window.Alpine?.$data(wrapper);
        if (data?.tab !== undefined) data.tab = tabKey;
    } catch { /* Alpine not ready */ }
}

function _toastHiddenErrors(errors) {
    if (!window.Toast) return;
    const lines = Array.from(errors.values(), ({ label, fields }) => `${label}: ${fields.join(', ')}`);
    Toast.warning(`Còn thiếu thông tin bắt buộc:\n${lines.join('\n')}`, { duration: 5000 });
}
```

### 17.3 Yêu cầu

- Panel div phải có `data-tab-label="Tên tab"` — JS đọc từ DOM, không hardcode
- `[data-req]` trên mọi required field
- `toastify.js` phải được load trước trong `@push('scripts')`

### 17.4 Luồng thực thi khi submit

```
Submit
  → capture: _setupTabGuard chạy trước
      - Tìm lỗi ở tab ẩn?  No → return (initFormValidation lo)
      - Yes → e.preventDefault() + switch tab + Toast
  → bubble: initFormValidation chạy
      - Tab đã switch → field giờ visible
      - Validate, highlight inline error, scrollIntoView ✓
```

### 17.5 Giới hạn của Tab Guard — field visible nhưng conditionally required

`_collectHiddenErrors` chỉ bắt field nằm trong **x-show panel đang ẩn** (`style.display === 'none'`). Nó **không bắt** được:

- Field **visible** nhưng value trống (có `data-req` → `initFormValidation` sẽ highlight, nhưng không Toast)
- Field **visible** nhưng **conditionally required** (nằm trong `x-show="scope === 'org'"` — khi scope='org' element này visible, Guard bỏ qua)

Ví dụ điển hình: `#ts-organization_id` trong scope selector (`x-show="scope === 'org'"`). Khi user chọn scope='org' nhưng không chọn org, field này **visible** → Guard không bắt → form submit → server error.

**Giải pháp:** Guard riêng cho mỗi trường hợp conditional required.

### 17.6 Conditional Required Field Guard

Pattern cho field có điều kiện (không phải tab-hidden):

```js
// Dùng khi field luôn visible (không trong x-show ẩn của tab)
// nhưng required theo điều kiện (scope, type, checkbox...)

function _setupOrgValidation(form) {
    const orgEl = form.querySelector('#ts-organization_id');
    if (!orgEl) return; // không phải super-admin → bỏ qua

    form.addEventListener('submit', (e) => {
        if (orgEl.value.trim()) return; // đã chọn → pass
        e.preventDefault();
        if (window.Toast) {
            Toast.warning('Vui lòng chọn tổ chức.', { duration: 4000 });
        }
    }, true); // capture=true để chạy trước initFormValidation
}
```

**Khi có scope radio (global/org):**

```js
// Chỉ validate organization_id khi scope = 'org'
function _setupScopeOrgValidation(form) {
    const orgEl = form.querySelector('#ts-organization_id');
    if (!orgEl) return;

    form.addEventListener('submit', (e) => {
        // Đọc radio checked hoặc hidden input
        const scopeEl = form.querySelector('[name="scope"]:checked')
            ?? form.querySelector('[name="scope"][type="hidden"]');
        if (scopeEl?.value !== 'org') return; // scope global → không cần org

        if (orgEl.value.trim()) return;
        e.preventDefault();

        // Switch về tab chứa field org
        const wrapper = form.closest('[x-data]') ?? document.querySelector('[x-data]');
        _switchAlpineTab(wrapper, 'basic'); // tên tab key chứa field org

        if (window.Toast) {
            Toast.warning('Vui lòng chọn tổ chức khi phạm vi là "Riêng tổ chức cụ thể".', { duration: 4000 });
        }
    }, true);
}
```

**Gọi sau `_setupTabGuard`:**

```js
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    _setupTabGuard(form);            // bắt lỗi tab ẩn
    _setupScopeOrgValidation(form);  // bắt org khi scope=org (conditional visible)
    initAllTomSelects(form);
    _setupScopeOrgSelect(form);
});
```

> **Nguyên tắc:** Mỗi conditional required field cần 1 guard riêng. Đặt tên theo pattern `_setup[Field]Validation(form)`.

---

## 18. Slug Auto-fill

### 18.1 Pattern

```js
// Trong pages/[entity]-form.js

const VI_MAP = Object.freeze({
    à:'a', á:'a', ả:'a', ã:'a', ạ:'a',
    ă:'a', ằ:'a', ắ:'a', ẳ:'a', ẵ:'a', ặ:'a',
    â:'a', ầ:'a', ấ:'a', ẩ:'a', ẫ:'a', ậ:'a',
    è:'e', é:'e', ẻ:'e', ẽ:'e', ẹ:'e',
    ê:'e', ề:'e', ế:'e', ể:'e', ễ:'e', ệ:'e',
    ì:'i', í:'i', ỉ:'i', ĩ:'i', ị:'i',
    ò:'o', ó:'o', ỏ:'o', õ:'o', ọ:'o',
    ô:'o', ồ:'o', ố:'o', ổ:'o', ỗ:'o', ộ:'o',
    ơ:'o', ờ:'o', ớ:'o', ở:'o', ỡ:'o', ợ:'o',
    ù:'u', ú:'u', ủ:'u', ũ:'u', ụ:'u',
    ư:'u', ừ:'u', ứ:'u', ử:'u', ữ:'u', ự:'u',
    ỳ:'y', ý:'y', ỷ:'y', ỹ:'y', ỵ:'y',
    đ:'d',
});

function _setupSlugAutoFill(form) {
    const nameInput = form.querySelector('[name="name"]');
    const slugInput = form.querySelector('[name="slug"]');
    if (!nameInput || !slugInput) return;

    // Edit: slug đã có giá trị → locked = true từ đầu
    let locked = slugInput.value.trim() !== '';

    slugInput.addEventListener('input',  () => { locked = slugInput.value.trim() !== ''; }, { passive: true });
    slugInput.addEventListener('change', () => { if (!slugInput.value.trim()) locked = false; }, { passive: true });
    nameInput.addEventListener('input',  () => { if (!locked) slugInput.value = _toSlug(nameInput.value); }, { passive: true });
}

function _toSlug(str) {
    let out = '';
    for (const ch of str.toLowerCase()) out += VI_MAP[ch] ?? ch;
    return out.replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-').replace(/-{2,}/g, '-');
}
```

### 18.2 Hành vi

| Tình huống | Hành vi |
|---|---|
| Create, slug trống | Auto-fill từ tên khi user gõ |
| Create, user tự điền slug | `locked = true` → không auto-fill nữa |
| Create, user xoá hết slug | `locked = false` → auto-fill trở lại |
| Edit, slug đã có giá trị | `locked = true` từ đầu — không bao giờ auto-fill |

---

## 19. Interactive states

### `x-cloak`

```blade
{{-- [x-cloak] { display: none !important } đã có trong app.css --}}
{{-- Chỉ thêm khi thực sự thấy flash khi reload trang --}}
<div x-data="{ open: false }" x-cloak>
    <div x-show="open">...</div>
</div>
```

### Submit loading

```blade
<button type="submit" class="btn btn-primary btn-sm gap-2"
        :disabled="submitting" :class="{ 'btn-disabled': submitting }">
    <span x-show="submitting" class="loading loading-spinner loading-xs"></span>
    <span x-text="submitting ? 'Đang xử lý...' : 'Tạo mới'"></span>
</button>
```

### Skeleton

```blade
<div x-show="loading" class="space-y-3">
    <div class="skeleton h-4 w-full rounded"></div>
    <div class="skeleton h-4 w-3/4 rounded"></div>
</div>
```

---

## 20. Submit Actions Bar

Dùng cho flat form (không có sidebar). Tab form dùng sidebar publish block (Section 11).

```blade
{{-- Cơ bản --}}
<div class="flex gap-2 pt-4 mt-2 border-t border-base-200">
    <button type="submit" class="btn btn-primary btn-sm gap-1.5">Tạo [entity]</button>
    <a href="{{ route('...index') }}" class="btn btn-ghost btn-sm">Hủy</a>
</div>

{{-- Có validation state (Alpine) --}}
<div class="flex items-center gap-3 pt-4 mt-2 border-t border-base-200">
    <div x-show="attempted && !isValid" x-transition class="flex items-center gap-2 text-sm text-error">
        <svg class="w-4 h-4 shrink-0" .../>
        Vui lòng kiểm tra lại các trường bắt buộc
    </div>
    <div class="ml-auto flex gap-2">
        <a href="..." class="btn btn-ghost btn-sm">Hủy</a>
        <button type="submit" class="btn btn-sm gap-1.5 transition-all"
                :class="attempted && !isValid ? 'btn-error' : 'btn-primary'">
            Tạo [entity]
        </button>
    </div>
</div>
```

---

## 21. Wizard Multi-step

Dùng khi quy trình bắt buộc tuần tự, không thể bỏ qua bước.

### Step indicator

```blade
<div class="flex items-center gap-0 mb-8">
    <template x-for="(label, idx) in steps" :key="idx">
        <div class="flex items-center flex-1 last:flex-none">
            <div class="flex flex-col items-center gap-1">
                <div class="wizard-step-dot" :class="stepDotClass(idx)">
                    <template x-if="currentStep > idx + 1">
                        <svg ...>✓</svg>
                    </template>
                    <template x-if="currentStep <= idx + 1">
                        <span x-text="idx + 1"></span>
                    </template>
                </div>
                <span class="text-xs whitespace-nowrap"
                      :class="currentStep === idx + 1 ? 'text-primary font-semibold' : 'text-base-content/40'"
                      x-text="label"></span>
            </div>
            <template x-if="idx < steps.length - 1">
                <div class="wizard-step-line" :class="stepLineClass(idx)"></div>
            </template>
        </div>
    </template>
</div>
```

Classes `.wizard-step-dot`, `.wizard-step-line` từ `_form-patterns.scss`.
Methods `stepDotClass()`, `stepLineClass()`, `isFirstStep()`, `isLastStep()` từ `makeWizardController`.

---

## 22. TomSelect

> **Quy tắc bắt buộc:** Mọi `<select>` trong form phải render qua TomSelect — không dùng native `<select>` thuần.

### 22.1 Class dùng chung — `ts-init`

Class `ts-init` là trigger chuẩn để auto-khởi tạo TomSelect. Thêm vào mọi select tĩnh (static options):

```blade
<select id="ts-[field]" name="[field]"
        class="select select-bordered select-sm w-full ts-init
               @error('[field]') select-error @enderror"
        data-ts-placeholder="— Chọn —">
    <option value="">— Chọn —</option>
    @foreach($options as $opt)
        <option value="{{ $opt->id }}" {{ old('[field]') == $opt->id ? 'selected' : '' }}>
            {{ $opt->label }}
        </option>
    @endforeach
</select>
```

**Quy tắc đặt `id`:** `id="ts-[field-name]"` — ví dụ: `id="ts-status"`, `id="ts-parent"`, `id="ts-branch"`.

**Placeholder:** `data-ts-placeholder="..."` → `initAllTomSelects` đọc attribute này. Nếu thiếu, fallback về text của `<option value="">` đầu tiên.

### 22.2 Auto-init — `initAllTomSelects(form)`

Gọi 1 lần trong page controller, tự tìm và init tất cả `select.ts-init` trong form:

```js
// Modules/[Name]/resources/assets/js/pages/[entity]-form.js
import { createTs, createTsRemote, initAllTomSelects } from '@shared/tom-select-factory.js';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    _setupTabGuard(form);
    initAllTomSelects(form);      // ← init mọi select.ts-init trong form
    _initCascadeSelects(form);    // ← chỉ khi có cascade (province/ward, parent có filter...)
});
```

`initAllTomSelects` được export từ `@shared/tom-select-factory.js`:

```js
// resources/js/shared/tom-select-factory.js

export function initAllTomSelects(container = document) {
    if (!window.TomSelect) return;
    for (const el of container.querySelectorAll('select.ts-init')) {
        if (el.tomselect) continue;                          // đã init → bỏ qua
        const placeholder = el.dataset.tsPlaceholder
            || el.querySelector('option[value=""]')?.textContent.trim()
            || '— Chọn —';
        createTs(el, { placeholder });
    }
}
```

### 22.3 Override thủ công — `createTs(el, opts)`

Dùng khi select cần config đặc biệt (onChange callback, dropdownParent, maxItems...):

```js
// Override sau khi initAllTomSelects — hoặc KHÔNG thêm ts-init và init riêng
import { createTs } from '@shared/tom-select-factory.js';

createTs(form.querySelector('[name="status"]'), {
    placeholder: '— Chọn trạng thái —',
    onChange(val) { ctx.status = val; },
});
```

> Nếu select đã được `initAllTomSelects` init → nó có `.tomselect` property → `createTs` sẽ skip (do `el.tomselect` check). Vì vậy, select cần override thủ công thì **không** thêm `ts-init`.

### 22.4 Remote search — `createTsRemote(el, opts)`

```js
createTsRemote(form.querySelector('[name="assignee_id"]'), {
    url:         '/api/users',
    valueField:  'id',
    labelField:  'text',
    searchField: ['text', 'email'],
    placeholder: '— Chọn nhân viên —',
});
```

### 22.5 Cascade (province → ward)

Select cascade **không dùng `ts-init`** — JS quản lý lifecycle (destroy/recreate khi options thay đổi):

```blade
{{-- Province: ts-init vì options tĩnh --}}
<select id="ts-province" name="province_code"
        class="select select-bordered select-sm w-full ts-init
               @error('province_code') select-error @enderror"
        data-ts-placeholder="Chọn tỉnh/thành...">
    <option value="">Chọn tỉnh/thành...</option>
    @foreach($provinces as $prov)
    <option value="{{ $prov->province_code }}"
            {{ old('province_code') === $prov->province_code ? 'selected' : '' }}>
        {{ $prov->name }}
    </option>
    @endforeach
</select>

{{-- Ward: KHÔNG ts-init — JS destroy/recreate khi load xong --}}
<select id="ts-ward" name="ward_code"
        data-selected-ward="{{ old('ward_code') }}"
        class="select select-bordered select-sm w-full @error('ward_code') select-error @enderror"
        {{ !old('province_code') ? 'disabled' : '' }}>
    <option value="">Chọn phường/xã...</option>
</select>
```

```js
// JS: province có onChange trong initAllTomSelects không đủ → override thủ công
function _initCascadeSelects(form) {
    const wardEl = form.querySelector('[name="ward_code"]');
    // Ward TomSelect — module-level để cascade destroy/recreate được
    _tsWard = createTs(wardEl, { placeholder: '...' });

    // Override province với onChange cascade (province đã được ts-init init → skip)
    // → Không thêm ts-init trên province nếu cần onChange
    // → Hoặc lấy instance hiện tại: form.querySelector('[name="province_code"]').tomselect
    const prov = form.querySelector('[name="province_code"]');
    if (prov?.tomselect) {
        prov.tomselect.on('change', (val) => _loadWards(val, wardEl));
    }
}
```

### 22.6 TomSelect trong `x-show` ẩn — KHÔNG dùng `ts-init`

Khi select nằm trong `x-show` bắt đầu ở trạng thái **ẩn** (`display: none`), `initAllTomSelects` sẽ init TomSelect khi element không có kích thước → dropdown width = 0 → layout vỡ.

**Vấn đề:**
```blade
{{-- ❌ SAI: ts-init trên element ẩn --}}
<div x-show="scope === 'org'" x-cloak>
    <select id="ts-organization_id" name="organization_id"
            class="select select-bordered select-sm ts-init">  {{-- ts-init gây lỗi --}}
```

**Giải pháp:** Bỏ `ts-init`, init thủ công bằng `createTs()` sau khi Alpine reveal:

```blade
{{-- ✅ ĐÚNG: không có ts-init --}}
<div x-show="scope === 'org'" x-cloak>
    <select id="ts-organization_id" name="organization_id"
            class="select select-bordered select-sm"
            data-ts-placeholder="— Chọn tổ chức —">
```

```js
// pages/[entity]-form.js
import { createTs, initAllTomSelects } from '@shared/tom-select-factory.js';

function _setupScopeOrgSelect(form) {
    const orgEl = form.querySelector('#ts-organization_id');
    if (!orgEl) return;

    const scopeWrapper = orgEl.closest('[x-show]');

    // Trường hợp old('scope') = 'org' (validation lỗi redirect back)
    // → element đã visible khi page load → init ngay
    if (scopeWrapper && scopeWrapper.style.display !== 'none') {
        requestAnimationFrame(() => { if (!orgEl.tomselect) createTs(orgEl); });
        return;
    }

    // Lắng nghe scope radio change → init khi user chọn 'org'
    form.querySelectorAll('[name="scope"]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.value === 'org' && !orgEl.tomselect) {
                requestAnimationFrame(() => createTs(orgEl)); // rAF để Alpine render trước
            }
        });
    });
}
```

**Quy tắc:**
- `requestAnimationFrame` bắt buộc — đảm bảo Alpine đã set `display: block` trước khi TomSelect đo kích thước
- Guard `if (!orgEl.tomselect)` — tránh init lại khi radio toggle nhiều lần
- Xử lý cả trường hợp redirect back (scope đã='org' → element visible ngay từ đầu)

### 22.7 SCSS

Mọi module SCSS dùng TomSelect phải `@use 'tom-select'`:

```scss
// Modules/[Name]/resources/assets/sass/[name].scss
@use 'form-patterns';
@use 'tom-select';   // ← bắt buộc khi form có select
```

### 22.8 Blade scripts — thứ tự load

`tom-select.js` phải load **trước** module JS:

```blade
@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',   ← trước module js
        'Modules/[Name]/resources/assets/js/[name].js',
    ], 'build/backend')
@endpush
```

---

## 23. Ngôn ngữ

### Label

| ❌ | ✅ |
|---|---|
| `Assessment Code` | `Mã đánh giá` |
| `Is Active` | `Kích hoạt` |
| `Sort Order` | `Thứ tự hiển thị` |
| `Probability` | `Xác suất chốt (%)` |

### Placeholder

```
Text:  "VD: Công ty TNHH ABC"
Email: "contact@company.com"
URL:   "https://company.com"
Slug:  "ten-slug-vd"
```

### Nút bấm

| Hành động | Label |
|---|---|
| Tạo mới | `Tạo [tên thực thể]` |
| Lưu chỉnh sửa | `Lưu lại` hoặc `Lưu thay đổi` |
| Hủy / Quay lại | `Hủy` / `← Quay lại` |
| Bước kế tiếp | `Tiếp theo: [tên tab/bước] →` |
| Bước trước | `← [tên tab/bước]` |

---

## 24. Class reference

### Layout form

| Thành phần | Class |
|---|---|
| Grid tab form | `grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start` |
| Card chính (tab) | `card bg-base-100 shadow-sm border border-base-200` |
| Sidebar wrapper | `xl:sticky xl:top-4 space-y-4` |
| Tab nav container | `border-b border-base-200 px-6` |
| Tab nav inner | `flex -mb-px` |
| Tab button active | `border-b-2 border-primary text-primary` |
| Tab button inactive | `border-b-2 border-transparent text-base-content/50 hover:text-base-content` |
| Tab panel | `x-show="tab === 'key'" data-tab-label="Label"` |
| Tab panel body | `p-6` |
| Tab footer nav | `flex items-center justify-between pt-2` |
| Sidebar card | `card bg-base-100 shadow-sm border border-base-200` |
| Sidebar card body | `card-body p-4` |
| Sidebar title | `text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3` |
| Sidebar 2-btn row | `flex gap-2` + từng nút `btn btn-sm flex-1` |
| Sidebar meta | `flex justify-between text-xs text-base-content/40 mb-4 px-0.5` |

### Cards & sections

| Thành phần | Class |
|---|---|
| Card | `card bg-base-100 shadow-sm border border-base-200` |
| Card body | `card-body` |
| Card title | `card-title text-base mb-5` |
| Card title icon | `w-4 h-4 text-primary` (inline, không có box background) |
| Divider sub-section | `divider my-4 text-xs text-base-content/30` |

### Form fields

| Thành phần | Class |
|---|---|
| Form control | `form-control` |
| Grid field | `grid grid-cols-1 sm:grid-cols-2 gap-4` |
| Field full-width | `sm:col-span-2` |
| Label wrapper | `label py-0 pb-1.5` |
| Label text | `label-text font-medium` |
| Label hint | `label-text-alt text-base-content/40 text-xs` |
| Required mark | `<span class="text-error">*</span>` |
| Input | `input input-bordered input-sm w-full` |
| Input error | + `input-error` |
| Input mono (slug, MST) | + `font-mono` |
| Select | `select select-bordered select-sm w-full` |
| Select error | + `select-error` |
| Textarea | `textarea textarea-bordered textarea-sm w-full` |
| Checkbox | `checkbox checkbox-sm checkbox-primary` |
| Error message | `mt-1 text-xs text-error` |
| Hint text | `mt-1 text-xs text-base-content/40` |

### Buttons

| Thành phần | Class |
|---|---|
| Primary submit | `btn btn-primary btn-sm gap-1.5` |
| Cancel / ghost | `btn btn-ghost btn-sm` |
| Sidebar submit | `btn btn-primary btn-sm flex-1 gap-1.5` |
| Sidebar cancel | `btn btn-ghost btn-sm flex-1` |
| Tab nav prev/next | `btn btn-ghost btn-sm gap-1.5` |
| Loading spinner | `loading loading-spinner loading-xs` |

---

## 25. Anti-patterns

### Layout

| ❌ Sai | ✅ Đúng |
|---|---|
| `max-w-3xl` làm container form | Grid `xl:grid-cols-[1fr_268px]` + sidebar |
| Full-width stacked submit buttons trong sidebar | 2 nút `flex-1` nằm ngang |
| Icon box màu (`bg-primary/10 rounded-lg`) trong section header | Icon inline `w-4 h-4 text-primary` |
| Subtitle dưới section header | Bỏ — dùng hint dưới field nếu cần giải thích |
| Icon wrapper trong input (phone, email) | Input thông thường, không icon prefix |
| `input-md` hoặc không size | `input-sm` |
| 3+ separate cards cho các section khi dùng tab | 1 card với tab nav bên trong |
| Hardcode tab label trong JS (`const TAB_LABELS = {...}`) | Đọc từ `data-tab-label` attr trên panel |

### JS

| ❌ Sai | ✅ Đúng |
|---|---|
| Regex compile trong vòng lặp | Compile 1 lần ở scope module |
| Object thay đổi hình dạng | `Object.freeze()` cho lookup table |
| Query Alpine wrapper mỗi submit | Cache với `??=` sau lần query đầu |
| `forEach` không thể `break` sớm | `for...of` + `continue`/`break` |
| Không có `{ passive: true }` trên input listeners | Thêm `passive: true` khi không cần `preventDefault` |
| `Alpine.data(...)` trong `<script>` blade | Đăng ký trong JS file, event `alpine:init` |

### TomSelect

| ❌ Sai | ✅ Đúng |
|---|---|
| Native `<select>` không có TomSelect | Thêm `ts-init` + `id="ts-[field]"` → gọi `initAllTomSelects(form)` |
| `createTs('#ts-xxx', ...)` cho từng select thủ công | Dùng `ts-init` class + `initAllTomSelects(form)` một lần |
| `@use 'tom-select'` bị thiếu trong SCSS | Luôn `@use 'tom-select'` khi form có select |
| `tom-select.js` load sau module JS | Load `tom-select.js` trước module JS trong `@push('scripts')` |
| Thêm `ts-init` cho ward select trong cascade | Ward không có `ts-init` — JS tự destroy/recreate |
| Không có `data-ts-placeholder` hoặc `<option value="">` | Luôn có 1 trong 2 để `initAllTomSelects` đọc placeholder |

### Flat vs Tab

| ❌ Sai | ✅ Đúng |
|---|---|
| Dùng flat form cho 20+ trường → phải scroll | Tab form cho ≥ 10 trường / 3+ nhóm |
| Dùng tab form cho 5 trường cùng ngữ cảnh | Flat form — tab thừa với form nhỏ |
| Dùng tab khi thứ tự bắt buộc (không được skip) | Wizard — tab cho phép nhảy tự do |

### Validation

| ❌ Sai | ✅ Đúng |
|---|---|
| `return $request->validate($rules)` không có `$messages` | Luôn truyền `$messages` với message tiếng Việt |
| Message mặc định: "The X field is required." | Custom: "Vui lòng nhập/chọn X." |
| `tabFields` thiếu field so với server rules | `tabFields` khai báo đầy đủ mọi field có server validation |
| `tabFields.edit` giống `tabFields.create` (gồm `organization_id`) | Edit: bỏ `organization_id` khỏi tabFields (field không submit trong edit) |

### Org Selector — Multi-tenant

| ❌ Sai | ✅ Đúng |
|---|---|
| Dùng `TenantContext::resolve()` để lấy tên org trong edit controller | `Organization::withoutTenant()->find($model->organization_id)` |
| `#ts-organization_id` có `ts-init` khi nằm trong `x-show` ẩn | Bỏ `ts-init`, init thủ công qua `_setupScopeOrgSelect` |
| Dùng `'{{ old('scope') }}'` trong Alpine x-data | `scope: {{ Js::from(old('scope', 'global')) }}` — dùng `Js::from()` |
| Không có Toast khi org trống (chỉ rely vào server) | Guard `_setupScopeOrgValidation` / `_setupOrgValidation` cho client-side Toast |
| Form dùng shared `form.blade.php` cho cả create và edit | Tách riêng `create.blade.php` + `edit.blade.php` |
| Edit form có scope selector để đổi org | Org lock trong edit — chỉ hiển thị tên org, không cho đổi |

---

## 26. Checklist trước khi merge

### Build

- [ ] Module entry: `[name].scss` / `[name].js` (không phải `app.scss`)
- [ ] Đã thêm vào `MODULE_ENTRIES` trong `vite.config.backend.js`
- [ ] `npm run build` thành công

### SCSS

- [ ] `@use 'form-patterns'` trong module SCSS
- [ ] `@use 'tom-select'` trong module SCSS (bắt buộc khi form có `<select>`)
- [ ] Không hardcode màu — dùng token từ `_tokens.scss`

### JS

- [ ] Alpine component đăng ký trong JS file (event `alpine:init`), không inline blade
- [ ] `import @shared/*` dùng alias, không dùng đường dẫn tương đối
- [ ] Regex/lookup table compile 1 lần ở scope module, không trong callback

### Flatpickr (khi form có date field)

- [ ] Mọi date field dùng `class="... fp-init"` và `id="fp-[field]"`
- [ ] Edit form: `value="{{ old('field', $model->field?->format('Y-m-d') ?? '') }}"` (Y-m-d)
- [ ] `window.initAllDatePickers?.(form)` được gọi trong page controller
- [ ] `flatpickr.js` load trước module JS trong `@push('scripts')`
- [ ] Không dùng `type="date"` native
- [ ] Không dùng `data-fp-mode` trừ khi cần range hoặc datetime

### TomSelect (bắt buộc khi form có `<select>`)

- [ ] Mọi static select có `class="... ts-init"` và `id="ts-[field]"`
- [ ] `data-ts-placeholder="..."` hoặc `<option value="">` đầu tiên có text placeholder
- [ ] `initAllTomSelects(form)` được gọi trong page controller (import từ `@shared/tom-select-factory.js`)
- [ ] Select cascade (ward...) **không** có `ts-init` — JS tự quản lý lifecycle
- [ ] `tom-select.js` load trước module JS trong `@push('scripts')`
- [ ] `@use 'tom-select'` trong module SCSS

### Flat form

- [ ] Form không có `max-w-3xl`
- [ ] `data-[entity]-form` hoặc `x-data` đúng pattern
- [ ] Submit bar dùng Section 20 pattern
- [ ] Edit form: tất cả value `old('field', $model->field)`

### Tab form (bổ sung)

- [ ] `x-data` inline đúng cấu trúc (tab, tabFields, errs, errCount, init)
- [ ] Mỗi tab panel có `data-tab-label="Tên tab tiếng Việt"`
- [ ] `[data-req]` trên mọi required field
- [ ] `toastify.js` load trước module JS trong `@push('scripts')`
- [ ] `_setupTabGuard(form)` được gọi trong page controller
- [ ] Sidebar dùng Section 11 pattern (không full-width stacked buttons)
- [ ] Grid `xl:grid-cols-[1fr_268px]` với `xl:sticky xl:top-4` trên sidebar
- [ ] Mỗi tab panel có footer nav (Prev/Next buttons)
- [ ] `init()` trong x-data tự chuyển tab có lỗi server

### Slug auto-fill (khi form có slug)

- [ ] Slug field đặt ngay dưới tên field trong cùng tab/section
- [ ] `_setupSlugAutoFill(form)` được gọi trong page controller
- [ ] `VI_MAP` được `Object.freeze()`
- [ ] Edit form: slug có giá trị → `locked = true` từ đầu

### UX

- [ ] Không có form nào phải scroll để nhập liệu (tab hoặc flat ngắn)
- [ ] Mọi label tiếng Việt, placeholder có `VD:` hoặc ví dụ cụ thể
- [ ] Test dark mode
- [ ] Submit button có loading state nếu async

### Org Selector (khi form có field organization_id)

- [ ] Super-admin thấy org selector; business user thấy hidden input + tên org
- [ ] Select `#ts-organization_id` **không** có `ts-init` nếu nằm trong `x-show` ẩn — init thủ công qua `_setupScopeOrgSelect`
- [ ] Select `#ts-organization_id` **có** `ts-init` nếu luôn visible (không có scope radio)
- [ ] Có guard riêng (`_setupScopeOrgValidation` hoặc `_setupOrgValidation`) cho client-side Toast
- [ ] Controller: super-admin lấy org từ request, business user lấy từ `TenantContext`
- [ ] Edit form: org lấy từ `Organization::withoutTenant()->find($model->organization_id)` — không dùng `TenantContext::resolve()` (null với super-admin)
- [ ] Validation rules: `required_if:scope,org` hoặc `required` kèm custom message tiếng Việt
- [ ] `organization_id` trong `tabFields.basic` nếu form dùng tab (create) — **không có** trong edit tabFields

### Validation — custom messages

- [ ] Mọi `$request->validate($rules)` đều có `$messages` array tiếng Việt
- [ ] Không có message nào tiếng Anh trong `$errors->all()`

---

## 27. Org Selector — Multi-tenant Form Pattern ← NEW

### 27.1 Bối cảnh

Trong hệ thống multi-tenant, nhiều entity được gắn với `organization_id`. Khi super-admin tạo/sửa entity, họ cần chọn org sở hữu. Business user luôn gắn với org của mình (TenantContext).

### 27.2 Hai biến thể

| Biến thể | Dùng khi | Ví dụ |
|---|---|---|
| **Scope radio** (global / org) | Entity có thể thuộc toàn hệ thống HOẶC 1 org cụ thể | CareerPathwayStep, CertificationDefinition, SandboxEnvironment |
| **Org select trực tiếp** | Entity luôn thuộc 1 org (không có global) | AiImpactSnapshot, Employee, Branch |

### 27.3 Biến thể A — Scope radio (global / org)

**Blade (create.blade.php):**

```blade
{{-- Phạm vi block — nằm trong tab 'basic', trước các field chính --}}
<div class="p-4 rounded-lg {{ $isSuperAdmin ? 'bg-warning/5 border border-warning/20' : 'bg-base-200/50 border border-base-200' }}">
    <p class="text-xs font-semibold uppercase tracking-wide text-base-content/50 mb-3">Phạm vi</p>

    @if($isSuperAdmin)
    {{-- Radio chọn scope --}}
    <div class="flex gap-6 flex-wrap mb-3">
        <label class="flex items-start gap-2.5 cursor-pointer select-none">
            <input type="radio" name="scope" value="global"
                   class="radio radio-sm radio-info mt-0.5"
                   x-model="scope">
            <div>
                <p class="text-sm font-medium">Toàn hệ thống</p>
                <p class="text-xs text-base-content/40">Áp dụng cho tất cả tổ chức</p>
            </div>
        </label>
        <label class="flex items-start gap-2.5 cursor-pointer select-none">
            <input type="radio" name="scope" value="org"
                   class="radio radio-sm radio-primary mt-0.5"
                   x-model="scope">
            <div>
                <p class="text-sm font-medium">Riêng tổ chức cụ thể</p>
                <p class="text-xs text-base-content/40">Chỉ tổ chức được chọn mới thấy</p>
            </div>
        </label>
    </div>

    {{-- Select org — hiện khi scope='org', KHÔNG dùng ts-init (nằm trong x-show ẩn) --}}
    <div x-show="scope === 'org'" x-cloak>
        <div class="form-control max-w-xs">
            <label class="label py-0 pb-1.5">
                <span class="label-text font-medium">Tổ chức <span class="text-error">*</span></span>
            </label>
            <select id="ts-organization_id" name="organization_id"
                    class="select select-bordered select-sm w-full @error('organization_id') select-error @enderror"
                    data-ts-placeholder="— Chọn tổ chức —">
                <option value="">— Chọn tổ chức —</option>
                @foreach($organizations as $org)
                <option value="{{ $org->id }}" {{ old('organization_id') == $org->id ? 'selected' : '' }}>
                    {{ $org->name }}
                </option>
                @endforeach
            </select>
            @error('organization_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </div>
    </div>

    @else
    {{-- Business user: hidden input + hiển thị tên org --}}
    <input type="hidden" name="scope" value="org">
    <div class="flex items-center gap-2 text-sm text-base-content/70">
        <svg class="w-4 h-4 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/>
        </svg>
        Riêng cho: <strong>{{ $currentOrg?->name }}</strong>
    </div>
    @endif
</div>
```

**x-data Alpine (create form):**

```blade
<div x-data="{
    tab: 'basic',
    tabFields: {
        basic: ['title', 'from_level', 'to_level', 'organization_id'],  {{-- organization_id PHẢI có --}}
        ...
    },
    errs: {{ Js::from($errors->keys()) }},
    errCount(t) { return this.tabFields[t].filter(f => this.errs.includes(f)).length; },
    init() { ... },
    scope: {{ Js::from(old('scope', 'global')) }},  {{-- Dùng Js::from(), không dùng '{{ }}' trong attribute --}}
}">
```

**Blade (edit.blade.php):**

```blade
{{-- Edit: org đã lock, không thể đổi — hiện tên org trong header thay vì selector --}}
<p class="text-sm text-base-content/50 mt-0.5">
    {{ $stepOrgName ? "Tổ chức: {$stepOrgName}" : 'Toàn hệ thống' }}
</p>
```

> Edit form: **không có** scope radio, không có org selector, `organization_id` không có trong `tabFields`.

### 27.4 Biến thể B — Org select trực tiếp (luôn org-specific)

**Blade (create.blade.php):**

```blade
{{-- Tổ chức block — luôn visible, không cần scope radio --}}
<div class="form-control sm:col-span-2">
    <div class="p-4 rounded-lg {{ $isSuperAdmin ? 'bg-warning/5 border border-warning/20' : 'bg-base-200/50 border border-base-200' }}">
        <p class="text-xs font-semibold uppercase tracking-wide text-base-content/50 mb-3">Tổ chức</p>

        @if($isSuperAdmin)
        <div class="form-control max-w-xs">
            <label class="label py-0 pb-1.5">
                <span class="label-text font-medium">Chọn tổ chức <span class="text-error">*</span></span>
            </label>
            {{-- ts-init OK vì luôn visible --}}
            <select id="ts-organization_id" name="organization_id"
                    class="select select-bordered select-sm w-full ts-init @error('organization_id') select-error @enderror"
                    data-ts-placeholder="— Chọn tổ chức —"
                    data-req="Vui lòng chọn tổ chức">
                <option value="">— Chọn tổ chức —</option>
                @foreach($organizations as $org)
                <option value="{{ $org->id }}" {{ old('organization_id') == $org->id ? 'selected' : '' }}>
                    {{ $org->name }}
                </option>
                @endforeach
            </select>
            @error('organization_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </div>
        @else
        <div class="flex items-center gap-2 text-sm text-base-content/70">
            <svg class="w-4 h-4 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/>
            </svg>
            Thuộc: <strong>{{ $currentOrg?->name }}</strong>
        </div>
        @endif
    </div>
</div>
```

### 27.5 Controller — create()

```php
public function create(): View
{
    $this->authorize('[permission]');

    $isSuperAdmin  = request()->user()?->hasRole('super-admin');
    $currentOrg    = TenantContext::resolve();                    // org của business user
    $organizations = $isSuperAdmin
        ? Organization::where('is_system', false)->orderBy('name')->get()
        : collect();

    return view('[module]::[entity].create', [
        'isSuperAdmin'  => $isSuperAdmin,
        'currentOrg'    => $currentOrg,
        'organizations' => $organizations,
        // ... các variables khác
    ]);
}
```

### 27.6 Controller — store()

**Biến thể A (scope radio):**

```php
public function store(Request $request): RedirectResponse
{
    $isSuperAdmin = $request->user()?->hasRole('super-admin');
    $data = $this->validate($request);

    if ($isSuperAdmin) {
        $organizationId = $request->input('scope') === 'global'
            ? null
            : (int) $request->input('organization_id');
    } else {
        $organizationId = TenantContext::getOrganizationId();
    }

    Entity::create([...$data, 'organization_id' => $organizationId]);
    ...
}
```

**Biến thể B (luôn org-specific):**

```php
public function store(Request $request): RedirectResponse
{
    $isSuperAdmin = $request->user()?->hasRole('super-admin');
    $data = $this->validate($request);

    $data['organization_id'] = $isSuperAdmin
        ? (int) $request->input('organization_id')
        : TenantContext::getOrganizationId();

    Entity::create($data);
    ...
}
```

### 27.7 Controller — edit()

```php
public function edit(Entity $entity): View
{
    // Dùng withoutTenant() để bypass global OrganizationScope
    // TenantContext::resolve() trả null với super-admin → không dùng
    $orgName = $entity->organization_id
        ? (Organization::withoutTenant()->find($entity->organization_id)?->name ?? 'Không xác định')
        : null;  // null = global (biến thể A)

    return view('[module]::[entity].edit', [
        'entity'  => $entity,
        'orgName' => $orgName,
        // ... không truyền $organizations (org không thể đổi trong edit)
    ]);
}
```

> **⚠️ Không dùng `TenantContext::resolve()`** để lấy tên org trong edit controller — super-admin không có tenant context, trả về `null`.

### 27.8 Validation rules

**Biến thể A (scope radio):**

```php
// Chỉ áp dụng khi tạo mới (không có route model binding)
if ($isSuperAdmin && ! $request->route('[entity]')) {
    $rules['scope']           = 'required|in:global,org';
    $rules['organization_id'] = 'required_if:scope,org|nullable|exists:organizations,id';
}

$messages = [
    'organization_id.required_if' => 'Vui lòng chọn tổ chức khi phạm vi là "Riêng tổ chức cụ thể".',
    'organization_id.exists'      => 'Tổ chức được chọn không hợp lệ.',
];
```

**Biến thể B (luôn org-specific):**

```php
if ($isSuperAdmin && ! $request->route('[entity]')) {
    $rules['organization_id'] = 'required|exists:organizations,id';
}

$messages = [
    'organization_id.required' => 'Vui lòng chọn tổ chức.',
    'organization_id.exists'   => 'Tổ chức được chọn không hợp lệ.',
];
```

### 27.9 JS — đầy đủ cho biến thể A

```js
import { createTs, initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL     = '[data-[entity]-form]';
const RE_TAB_XSHOW = /tab\s*===\s*['"](\w+)['"]/;

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    _setupTabGuard(form);             // Section 17.2
    _setupScopeOrgValidation(form);   // Section 17.6 — conditional required guard
    initAllTomSelects(form);          // init tất cả select.ts-init (KHÔNG bao gồm #ts-organization_id)
    _setupScopeOrgSelect(form);       // Section 22.6 — init TomSelect cho org select ẩn
});

function _setupScopeOrgValidation(form) {
    const orgEl = form.querySelector('#ts-organization_id');
    if (!orgEl) return;
    form.addEventListener('submit', (e) => {
        const scopeEl = form.querySelector('[name="scope"]:checked')
            ?? form.querySelector('[name="scope"][type="hidden"]');
        if (scopeEl?.value !== 'org') return;
        if (orgEl.value.trim()) return;
        e.preventDefault();
        const wrapper = form.closest('[x-data]') ?? document.querySelector('[x-data]');
        _switchAlpineTab(wrapper, 'basic');
        window.Toast?.warning('Vui lòng chọn tổ chức khi phạm vi là "Riêng tổ chức cụ thể".', { duration: 4000 });
    }, true);
}

function _setupScopeOrgSelect(form) {
    const orgEl = form.querySelector('#ts-organization_id');
    if (!orgEl) return;
    const scopeWrapper = orgEl.closest('[x-show]');
    if (scopeWrapper && scopeWrapper.style.display !== 'none') {
        requestAnimationFrame(() => { if (!orgEl.tomselect) createTs(orgEl); });
        return;
    }
    form.querySelectorAll('[name="scope"]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.value === 'org' && !orgEl.tomselect) {
                requestAnimationFrame(() => createTs(orgEl));
            }
        });
    });
}
```

### 27.10 Tóm tắt khác biệt create vs edit

| | Create | Edit |
|---|---|---|
| Scope selector | ✅ Hiện (super-admin) | ❌ Không có |
| Org select | ✅ Hiện khi scope='org' | ❌ Không có |
| Org name display | Hiện tên org business user | Hiện `$orgName` trong subtitle |
| `organization_id` trong tabFields | ✅ Có trong `basic` | ❌ Không có |
| `#ts-organization_id` dùng `ts-init` | ❌ Không (nằm trong x-show ẩn — biến thể A) | ❌ Không có field |
| JS `_setupScopeOrgSelect` | ✅ Gọi | ❌ Không cần |
| JS `_setupScopeOrgValidation` | ✅ Gọi | ❌ Không cần |
