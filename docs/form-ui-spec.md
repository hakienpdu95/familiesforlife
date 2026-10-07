# Form UI/UX Specification — Backend SaaS

> **Version:** 5.1  
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
8. **[Quyết định bố cục form — Flat vs Tab](#8-quyết-định-bố-cục-form)** ← NEW v5 · UPDATED v5.1 (8.4 vị trí nút Lưu)
9. **[Flat Form (≤ ~10 trường)](#9-flat-form)**
10. **[Tab-Based Form (> 10 trường / ≥ 3 nhóm)](#10-tab-based-form)** ← NEW v5
11. **[Sticky Submit Bar (Tab form)](#11-sticky-submit-bar)** ← v5.1 thay Sidebar Publish Block
12. [Card Section](#12-card-section)
13. [Grid & bố cục cột](#13-grid)
14. [Form Control — cấu trúc bắt buộc](#14-form-control)
15. [Input types](#15-input-types) ← UPDATED v5.1 (File & Image Upload)
16. [Validation](#16-validation)
17. **[Tab-Aware Submit Guard (JS)](#17-tab-aware-submit-guard)** ← NEW v5 · UPDATED v5.1 (17.7 Dirty Form Guard)
18. **[Slug Auto-fill (JS)](#18-slug-auto-fill)** ← NEW v5 · UPDATED v5.1 (debounce)
19. [Interactive states & Loading](#19-interactive-states)
20. [Submit Actions Bar](#20-submit-bar) ← UPDATED v5.1
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
Form width:      full-width — <form> không giới hạn max-width
```

> ⚠️ **v5.1 breaking change:** Bỏ Sidebar Publish Block và grid `xl:grid-cols-[1fr_300px]`.
> Mọi form dùng layout **1 cột full-width** (`<form>` không thêm class giới hạn chiều rộng); nút Lưu/Hủy + meta **luôn nằm dưới cùng** form —
> static với flat form ([Section 20](#20-submit-bar)), sticky với tab form ([Section 11](#11-sticky-submit-bar)).

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

### 8.4 Vị trí khối nút bấm (Submit / Hủy)

Nút Submit, Cancel và meta (trạng thái, ngày tạo/sửa) **LUÔN LUÔN nằm dưới cùng form** — một luồng code duy nhất cho mọi bố cục, thân thiện mobile (1 cột). Chỉ khác nhau ở cách hiển thị:

| Bố cục | Hiển thị khối nút | Cấu trúc dùng |
|---|---|---|
| **Flat Form** (ngắn, không cuộn) | **Static** — `.form-submit-bar`, ngay dưới các trường, ngăn cách bằng `border-top` | [Section 20 — Submit Actions Bar](#20-submit-bar) |
| **Tab-Based Form** (dài, cần cuộn) | **Sticky** — ghim ở mép dưới viewport (`sticky bottom-0`) | [Section 11 — Sticky Submit Bar](#11-sticky-submit-bar) |
| **Wizard** | Footer từng bước (Trước / Tiếp theo / Hoàn tất) | [Section 21](#21-wizard) |

> **Lý do sticky cho tab form:** user đang ở tab nào, cuộn tới đâu cũng luôn thấy nút "Lưu lại" — không phải cuộn xuống cuối mới tìm thấy. Footer của tab panel chỉ chứa nút điều hướng Prev/Next ([10.4](#104-tab-panels)), không chứa submit.

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
    <div class="form-submit-bar">
        <div class="submit-actions">
            <a href="{{ route('...index') }}" class="btn btn-ghost btn-sm">Hủy</a>
            <button type="submit" class="btn btn-primary btn-sm gap-1.5">Tạo [entity]</button>
        </div>
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
            ┌──────────────── full-width ───────────────┐
            │  [Tab 1] [Tab 2] [Tab 3]                  │
            │  ───────────────────────────────────────  │
            │                                           │
            │  Chỉ hiện 1 tab tại 1 thời điểm           │
            │                                           │
            │  [← Trước]                 [Tiếp theo →]  │
            └───────────────────────────────────────────┘
════════════╪═══════════════════════════════════════════╪════  ← mép dưới viewport
            │ Trạng thái / Meta          [Hủy] [Lưu lại]│  ← Sticky Submit Bar
            └───────────────────────────────────────────┘

Container: <form> full-width — 1 cột, không sidebar
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
<div class="border-b border-base-200 px-3">
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
<div class="p-3">

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
            <span class="text-xs text-base-content/40">Nhấn <strong>Lưu lại</strong> ở thanh dưới cùng khi xong</span>
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

    {{-- Card chính: tab nav + panels --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="border-b border-base-200 px-3">
            <nav class="flex -mb-px" role="tablist">
                {{-- Tab buttons --}}
            </nav>
        </div>
        <div class="p-3">
            {{-- Tab panels (x-show + data-tab-label) --}}
        </div>
    </div>

    {{-- Sticky Submit Bar (Section 11) — con TRỰC TIẾP, cuối cùng của <form> --}}
    <div class="form-submit-bar form-submit-bar--sticky">...</div>

</form>
</div>
@endsection
```

---

## 11. Sticky Submit Bar

Khối nút bấm của **tab form** — vẫn nằm dưới cùng form như flat form ([8.4](#84-vị-trí-khối-nút-bấm-submit--hủy)), nhưng ghim `sticky bottom-0` để luôn hiện ở mép dưới viewport trong khi user cuộn/chuyển tab.

> ⚠️ **v5.1:** Thay thế hoàn toàn Sidebar Publish Block (v5). Không còn cột sidebar trong form.

**Bố cục:** Trái — trạng thái hoặc meta (ngày tạo/sửa). Phải — Hủy (ghost) + Lưu (primary) trong `.submit-actions`.

**Class:** `form-submit-bar form-submit-bar--sticky` — định nghĩa trong `resources/scss/_form-patterns.scss` (mục 8). Module SCSS phải có `@use 'form-patterns'` ([3.2](#32-module-scss-entry)). Không viết lại chuỗi class Tailwind cho bar.

| Class | Vai trò |
|---|---|
| `.form-submit-bar` | Base: `flex`, `align-items: center`, `gap: .75rem`, `border-top` |
| `.form-submit-bar--sticky` | `sticky bottom-0`, `z-index: 50`, `flex-wrap`, `justify-content: space-between`, nền `base-100`, padding `1rem 1.5rem`, `margin-top: 1.25rem`, bóng hắt lên `0 -4px 6px -1px rgb(0 0 0 / .1)`, TomSelect dropdown mở lên trên |
| `.submit-actions` | Nhóm nút bên phải: `margin-left: auto`, `flex`, `gap: .5rem` |

### 11.1 Create form

```blade
<div class="form-submit-bar form-submit-bar--sticky">

    {{-- Trái: trạng thái (hoặc để trống nếu entity không có status) --}}
    <div class="flex items-center gap-2">
        <span class="text-xs font-medium text-base-content/60">Trạng thái <span class="text-error">*</span></span>
        <select id="ts-status" name="status"
                class="select select-bordered select-sm w-44 @error('status') select-error @enderror">
            <option value="active"   {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Hoạt động</option>
            <option value="inactive" {{ old('status') === 'inactive'         ? 'selected' : '' }}>Không hoạt động</option>
        </select>
    </div>

    {{-- Phải: Hủy + Lưu --}}
    <div class="submit-actions">
        <a href="{{ route('...index') }}" class="btn btn-ghost btn-sm">Hủy</a>
        <button type="submit" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-3.5 h-3.5" ...>+ icon</svg>
            Tạo mới
        </button>
    </div>

</div>
```

### 11.2 Edit form (thêm meta timestamps)

```blade
<div class="form-submit-bar form-submit-bar--sticky">

    <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
        {{-- Status select (như 11.1, old() fallback về model) --}}
        <div class="flex items-center gap-2">...</div>

        {{-- Meta: 1 dòng inline --}}
        <span class="text-xs text-base-content/40">
            Tạo {{ $model->created_at->format('d/m/Y') }} · Sửa {{ $model->updated_at->diffForHumans() }}
        </span>
    </div>

    <div class="submit-actions">
        <a href="{{ route('...show', $model) }}" class="btn btn-ghost btn-sm">Hủy</a>
        <button type="submit" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-3.5 h-3.5" ...>✓ icon</svg>
            Lưu lại
        </button>
    </div>

</div>
```

### 11.3 Nguyên tắc & điều kiện để sticky hoạt động

| Nguyên tắc | Lý do |
|---|---|
| Bar là **con trực tiếp, cuối cùng của `<form>`** (ngoài card chính) | `sticky` chỉ ghim trong phạm vi phần tử cha — đặt trong card/tab panel thì hết card là bar trôi mất; nút submit cũng phải nằm trong `<form>` |
| Không có ancestor nào giữa bar và `.main-area` dùng `overflow: hidden/auto` | Ancestor có overflow tạo scroll container mới → `sticky` mất tác dụng. Scroll container của backend là `.main-area` |
| Không override nền / shadow / z-index của bar bằng Tailwind | Nền `base-100` che nội dung cuộn phía sau, bóng hắt lên tách bar khỏi nội dung, `z-index: 50` nằm dưới topbar (`100`) và sidebar app (`200`) — đã có sẵn trong class |
| Nhóm nút phải bọc trong `.submit-actions` | `margin-left: auto` giữ nút căn phải kể cả khi không có khối trạng thái/meta bên trái; mobile: `flex-wrap` đẩy meta lên dòng trên |
| Nút **không** `flex-1` / full-width | Bar nằm ngang toàn khung — nút full-width trông thừa và nặng |
| Ghi chú "`*` là trường bắt buộc" | Bỏ khỏi bar (bar cần gọn 1 hàng) — đặt dưới tab nav hoặc đầu panel nếu cần |

**Select trạng thái trong bar — TomSelect phải mở lên trên.** Bar nằm sát mép dưới viewport nên dropdown mở xuống sẽ bị khuất. `.form-submit-bar--sticky .ts-dropdown` đã đảo hướng sẵn (`bottom: 100%`), nhưng chỉ có tác dụng khi dropdown render **trong** wrapper — mặc định factory render vào `<body>`. Vì vậy select trong bar **không** dùng `ts-init`, khởi tạo thủ công:

```js
createTs('#ts-status', { dropdownParent: null, placeholder: 'Chọn trạng thái' });
```

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

### 13.3 Container tổng thể form

```blade
{{-- Mọi form (flat + tab): 1 cột full-width — không max-width, không grid tổng thể, không sidebar --}}
<form>
    {{-- card(s) --}}
    {{-- Flat: Submit bar static (Section 20) | Tab: Sticky Submit Bar (Section 11) --}}
</form>
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

### File & Image Upload ← NEW v5.1

> Form có upload file phải có `enctype="multipart/form-data"` trên thẻ `<form>` (trừ khi dùng FilePond — file đã upload async, form chỉ submit uuid).

**Bảng chọn component:**

| Trường hợp | Component |
|---|---|
| 1 tài liệu (PDF, DOCX...) | `file-input` DaisyUI |
| 1 ảnh có preview (logo, ảnh đại diện, ảnh bìa) | `file-input` DaisyUI + khung preview Alpine |
| Nhiều ảnh / nhiều file cùng lúc | FilePond — `initFilePondUpload()` |

#### Upload cơ bản (tài liệu / PDF)

```blade
<div class="form-control">
    <label class="label py-0 pb-1.5">
        <span class="label-text font-medium">Tài liệu đính kèm</span>
        <span class="label-text-alt text-xs text-base-content/40">PDF, tối đa 10MB</span>
    </label>
    <input type="file" name="document" accept="application/pdf"
           class="file-input file-input-bordered file-input-sm w-full @error('document') file-input-error @enderror">
    @error('document')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
</div>
```

#### Upload hình ảnh có preview

- **Create:** bắt buộc có khung preview ảnh nhỏ **phía trên** nút upload — hiện ngay khi user chọn file.
- **Edit:** hiển thị lại ảnh đã lưu kèm nút X / icon thùng rác **"Xóa ảnh hiện tại"**. Khi bấm xóa → bật hidden input `name="remove_[field]" value="1"` (VD: `remove_image`) để báo backend xóa hẳn ảnh cũ.
- Chọn ảnh mới sau khi đã bấm xóa → tự hủy cờ xóa (ảnh mới thay thế ảnh cũ).

```blade
{{-- Edit: $model->image_url có thể null. Create: truyền null --}}
<div class="form-control"
     x-data="{
         preview: {{ Js::from($model?->image_url) }},
         remove: false,
         pick(e) {
             const f = e.target.files[0];
             if (!f) return;
             this.preview = URL.createObjectURL(f);
             this.remove  = false;
         },
         clear() {
             this.preview = null;
             this.remove  = true;
             this.$refs.file.value = '';
         },
     }">
    <label class="label py-0 pb-1.5">
        <span class="label-text font-medium">Ảnh đại diện</span>
        <span class="label-text-alt text-xs text-base-content/40">JPG, PNG, WEBP · tối đa 2MB</span>
    </label>

    {{-- Preview — phía trên nút upload --}}
    <div x-show="preview" x-cloak class="relative w-32 h-32 mb-2">
        <img :src="preview" alt="" class="w-full h-full object-cover rounded-lg border border-base-200">
        <button type="button" @click="clear()" title="Xóa ảnh hiện tại"
                class="btn btn-circle btn-xs btn-error absolute -top-2 -right-2">✕</button>
    </div>

    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" x-ref="file" @change="pick($event)"
           class="file-input file-input-bordered file-input-sm w-full @error('image') file-input-error @enderror">

    {{-- Edit only — Create không cần cờ xóa --}}
    <input type="hidden" name="remove_image" value="1" :disabled="!remove">

    @error('image')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
</div>
```

- Hidden input dùng `:disabled="!remove"` → chỉ được submit khi user thực sự bấm xóa.
- Backend: `'remove_image' => ['nullable', 'boolean']`; xử lý theo thứ tự **có file mới → thay thế**, **không có file mới + `remove_image` = 1 → xóa ảnh cũ**, còn lại giữ nguyên.

#### Nhiều ảnh / nhiều file — FilePond

Dự án đã có wrapper `resources/js/modules/filepond.js` — **thống nhất dùng `initFilePondUpload()`** (nối sẵn `MediaUploadService`, CSRF, giới hạn MIME/size theo collection). Không tự cấu hình `FilePond.create()` trong module.

```blade
<input type="file" id="gallery-filepond" multiple>
<input type="hidden" name="gallery_uuids" id="gallery-uuids">
```

```js
initFilePondUpload('#gallery-filepond', {
    collection: 'attachments',      // 'avatar'|'logo'|'thumbnail'|'cover'|'attachments'|'attachments_private'
    bindTo:     '#gallery-uuids',   // 1-n collection → JSON array uuid
    // Edit: thêm contextType + contextId để gắn thẳng vào entity
});
```

- Create: backend gọi `MediaUploadService::reassociateFilePondDrafts($model, $uuids, $collection)` sau khi lưu.
- Load `resources/js/modules/filepond.js` **trước** module JS trong `@push('scripts')`.

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

### 17.7 Dirty Form Guard — cảnh báo mất dữ liệu chưa lưu

**Vấn đề:** Tab form dài (hàng chục trường) — user lỡ bấm Back, đóng tab trình duyệt hoặc click link ở sidebar → mất sạch dữ liệu đã nhập.

**Giải pháp:**
1. Bắt `input` / `change` trên toàn form → set cờ `isDirty = true`.
2. Khi `isDirty === true` → gắn `beforeunload` để trình duyệt hiện hộp thoại xác nhận rời trang.
3. Khi user bấm Lưu/Submit (và submit **không** bị chặn bởi guard/validation) → `isDirty = false` để cho phép điều hướng.

```js
// Trong pages/[entity]-form.js

function _setupDirtyGuard(form) {
    let isDirty = false;

    const onBeforeUnload = (e) => {
        e.preventDefault();
        e.returnValue = '';   // Chrome/Edge cũ cần returnValue để hiện dialog
    };

    const markDirty = () => {
        if (isDirty) return;
        isDirty = true;
        window.addEventListener('beforeunload', onBeforeUnload);
    };

    const clearDirty = () => {
        isDirty = false;
        window.removeEventListener('beforeunload', onBeforeUnload);
    };

    form.addEventListener('input',  markDirty, { passive: true });
    form.addEventListener('change', markDirty, { passive: true });

    // Lắng nghe ở document (bubble) → chạy SAU Tab Guard (capture) và initFormValidation (bubble trên form).
    // Submit bị chặn (thiếu field) → defaultPrevented = true → giữ nguyên cờ dirty.
    document.addEventListener('submit', (e) => {
        if (e.target === form && !e.defaultPrevented) clearDirty();
    });
}
```

**Gọi SAU khi đã init TomSelect / Flatpickr / Jodit** — tránh việc khởi tạo component tự bắn `change` làm form bị đánh dấu dirty ngay khi load:

```js
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    _setupTabGuard(form);
    initAllTomSelects(form);
    window.initAllDatePickers?.(form);
    _setupSlugAutoFill(form);
    _setupDirtyGuard(form);          // ← luôn gọi cuối
});
```

**Lưu ý:**

| Tình huống | Xử lý |
|---|---|
| Nội dung hộp thoại | Trình duyệt hiện đại **bỏ qua** message tùy biến, chỉ hiện câu mặc định ("Rời khỏi trang web? Các thay đổi có thể chưa được lưu"). Không cố set text riêng. |
| TomSelect | `onChange` dispatch native `change` trên `<select>` gốc → tự được bắt |
| Jodit (rich text) | Jodit không luôn bắn `input` trên textarea gốc → nối thêm `editor.events.on('change', markDirty)` bên trong `_setupDirtyGuard` nếu form có Jodit |
| Gán value bằng JS (auto-fill slug, cascade ward reset) | Không bắn event → không đánh dấu dirty — đúng mong muốn |
| Nút "Hủy" (link về index) | Vẫn hiện cảnh báo nếu dirty — đúng mong muốn (user chủ động rời đi cần xác nhận) |
| Phạm vi áp dụng | **Bắt buộc** cho tab form; tùy chọn cho flat form ngắn |

---

## 18. Slug Auto-fill

### 18.1 Pattern

> **v5.1:** Tạo slug được bọc trong **debounce 300ms** — không chạy `_toSlug` trên từng phím gõ; user dừng tay ~0.3s mới gen slug và điền xuống ô bên dưới. Khi submit, slug đang chờ được **flush** ngay để không gửi slug cũ.

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

const SLUG_DEBOUNCE_MS = 300;

function _debounce(fn, wait) {
    let timer = null;
    const debounced = (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => { timer = null; fn(...args); }, wait);
    };
    debounced.cancel = () => { clearTimeout(timer); timer = null; };
    debounced.flush  = () => { if (timer) { debounced.cancel(); fn(); } };
    return debounced;
}

function _setupSlugAutoFill(form) {
    const nameInput = form.querySelector('[name="name"]');
    const slugInput = form.querySelector('[name="slug"]');
    if (!nameInput || !slugInput) return;

    // Edit: slug đã có giá trị → locked = true từ đầu
    let locked = slugInput.value.trim() !== '';

    // Kiểm tra locked tại thời điểm chạy (sau 300ms), không phải lúc gõ phím
    const fillSlug = _debounce(() => {
        if (!locked) slugInput.value = _toSlug(nameInput.value);
    }, SLUG_DEBOUNCE_MS);

    slugInput.addEventListener('input',  () => { locked = slugInput.value.trim() !== ''; if (locked) fillSlug.cancel(); }, { passive: true });
    slugInput.addEventListener('change', () => { if (!slugInput.value.trim()) locked = false; }, { passive: true });
    nameInput.addEventListener('input',  fillSlug, { passive: true });

    // capture → flush trước Tab Guard / initFormValidation
    form.addEventListener('submit', () => fillSlug.flush(), true);
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
| Create, slug trống | Auto-fill từ tên sau khi user **dừng gõ 300ms** (debounce) |
| Create, user submit khi debounce chưa chạy | `flush()` trong submit (capture) → gen slug ngay, không gửi slug cũ |
| Create, user gõ vào slug khi debounce đang chờ | `cancel()` → không ghi đè slug user vừa nhập |
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

Dùng cho **flat form** — khối nút **luôn nằm dưới cùng** form, sau card cuối cùng, là con trực tiếp cuối cùng của `<form>` ([8.4](#84-vị-trí-khối-nút-bấm-submit--hủy)).

**Class:** `.form-submit-bar` (base, static) — định nghĩa trong `resources/scss/_form-patterns.scss` (mục 8), cùng họ với `.form-submit-bar--sticky` của tab form. Module SCSS phải có `@use 'form-patterns'`. Không viết lại bằng chuỗi Tailwind.

| Class | Vai trò |
|---|---|
| `.form-submit-bar` | `flex`, `align-items: center`, `gap: .75rem`, `border-top`, `padding: 1rem 0 .5rem`, `margin-top: 1rem` |
| `.submit-actions` | Nhóm nút: `margin-left: auto` (căn phải), `flex`, `gap: .5rem` |

**Thứ tự nút:** Hủy (ghost) → Lưu (primary), căn phải — giống hệt Sticky Submit Bar.

> Tab form dùng cùng vị trí (dưới cùng form) và cùng class, thêm modifier `form-submit-bar--sticky` — xem [Section 11](#11-sticky-submit-bar). Form flat lớn dần thành tab form → chỉ cần thêm modifier. Không đặt 2 bar trong cùng 1 form.

```blade
{{-- Cơ bản --}}
<div class="form-submit-bar">
    <div class="submit-actions">
        <a href="{{ route('...index') }}" class="btn btn-ghost btn-sm">Hủy</a>
        <button type="submit" class="btn btn-primary btn-sm gap-1.5">Tạo [entity]</button>
    </div>
</div>

{{-- Có validation state (Alpine) — thông báo lỗi bên trái, nhóm nút vẫn căn phải --}}
<div class="form-submit-bar">
    <div x-show="attempted && !isValid" x-transition class="flex items-center gap-2 text-sm text-error">
        <svg class="w-4 h-4 shrink-0" .../>
        Vui lòng kiểm tra lại các trường bắt buộc
    </div>
    <div class="submit-actions">
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
| Form container (mọi form) | `<form>` full-width — không thêm `max-w-*` |
| Card chính (tab) | `card bg-base-100 shadow-sm border border-base-200` |
| Tab nav container | `border-b border-base-200 px-6` |
| Tab nav inner | `flex -mb-px` |
| Tab button active | `border-b-2 border-primary text-primary` |
| Tab button inactive | `border-b-2 border-transparent text-base-content/50 hover:text-base-content` |
| Tab panel | `x-show="tab === 'key'" data-tab-label="Label"` |
| Tab panel body | `p-6` |
| Tab footer nav | `flex items-center justify-between pt-2` |
| Sticky Submit Bar (tab form) | `form-submit-bar form-submit-bar--sticky` |
| Sticky bar — nhóm nút phải | `submit-actions` |
| Sticky bar — meta | `text-xs text-base-content/40` |
| Static Submit Bar (flat form) | `form-submit-bar` + nhóm nút `submit-actions` |

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
| Tab nav prev/next | `btn btn-ghost btn-sm gap-1.5` |
| Loading spinner | `loading loading-spinner loading-xs` |

---

## 25. Anti-patterns

### Layout

| ❌ Sai | ✅ Đúng |
|---|---|
| Grid `xl:grid-cols-[1fr_300px]` + sidebar | 1 cột full-width |
| Giới hạn `max-w-*` / `mx-auto` trên `<form>` | `<form>` full-width |
| Sidebar Publish Block (v5) | Submit bar dưới cùng form — static (flat) / sticky (tab) |
| Nút submit full-width / `flex-1` trong bar | Nút kích thước tự nhiên, nhóm căn phải |
| Icon box màu (`bg-primary/10 rounded-lg`) trong section header | Icon inline `w-4 h-4 text-primary` |
| Subtitle dưới section header | Bỏ — dùng hint dưới field nếu cần giải thích |
| Icon wrapper trong input (phone, email) | Input thông thường, không icon prefix |
| `input-md` hoặc không size | `input-sm` |
| 3+ separate cards cho các section khi dùng tab | 1 card với tab nav bên trong |
| Hardcode tab label trong JS (`const TAB_LABELS = {...}`) | Đọc từ `data-tab-label` attr trên panel |
| Tab form dùng submit bar static ở cuối → phải cuộn mới thấy nút Lưu | Sticky Submit Bar `sticky bottom-0` ([Section 11](#11-sticky-submit-bar)) |
| Sticky bar đặt trong card / tab panel | Con trực tiếp, cuối cùng của `<form>` — ngoài card |
| Tự viết bar bằng chuỗi Tailwind (`flex gap-2 pt-4 border-t ...`, `sticky bottom-0 z-50 ...`) | `form-submit-bar` (flat) / `form-submit-bar form-submit-bar--sticky` (tab) |
| Đặt nút Lưu ở đầu form / trong footer từng tab panel | Luôn dưới cùng form ([8.4](#84-vị-trí-khối-nút-bấm-submit--hủy)) |
| Select trạng thái trong sticky bar dùng `ts-init` (dropdown mở xuống bị khuất) | `createTs(..., { dropdownParent: null })` — `.form-submit-bar--sticky` tự mở dropdown lên trên |
| Edit ảnh không có cách xóa ảnh cũ | Nút X "Xóa ảnh hiện tại" + hidden `remove_[field]=1` |

### JS

| ❌ Sai | ✅ Đúng |
|---|---|
| Regex compile trong vòng lặp | Compile 1 lần ở scope module |
| Object thay đổi hình dạng | `Object.freeze()` cho lookup table |
| Query Alpine wrapper mỗi submit | Cache với `??=` sau lần query đầu |
| `forEach` không thể `break` sớm | `for...of` + `continue`/`break` |
| Không có `{ passive: true }` trên input listeners | Thêm `passive: true` khi không cần `preventDefault` |
| `Alpine.data(...)` trong `<script>` blade | Đăng ký trong JS file, event `alpine:init` |
| `_toSlug` chạy trên từng phím gõ | Debounce 300ms + `flush()` khi submit ([18.1](#181-pattern)) |
| Tab form không cảnh báo khi rời trang có dữ liệu chưa lưu | `_setupDirtyGuard(form)` ([17.7](#177-dirty-form-guard--cảnh-báo-mất-dữ-liệu-chưa-lưu)) |
| Clear cờ dirty ngay trong submit listener capture | Clear ở `document` (bubble) và chỉ khi `!e.defaultPrevented` |
| Tự gọi `FilePond.create()` trong module | `initFilePondUpload()` từ `resources/js/modules/filepond.js` |

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

- [ ] `<form>` full-width — không `max-w-*`, không sidebar
- [ ] `data-[entity]-form` hoặc `x-data` đúng pattern
- [ ] Submit bar `.form-submit-bar` + `.submit-actions` (Section 20) — con trực tiếp cuối cùng của `<form>`, **dưới** mọi card; thứ tự Hủy → Lưu
- [ ] Module SCSS có `@use 'form-patterns'`
- [ ] Edit form: tất cả value `old('field', $model->field)`

### Tab form (bổ sung)

- [ ] `x-data` inline đúng cấu trúc (tab, tabFields, errs, errCount, init)
- [ ] Mỗi tab panel có `data-tab-label="Tên tab tiếng Việt"`
- [ ] `[data-req]` trên mọi required field
- [ ] `toastify.js` load trước module JS trong `@push('scripts')`
- [ ] `_setupTabGuard(form)` được gọi trong page controller
- [ ] Nút Lưu/Hủy + trạng thái/meta nằm trong `.form-submit-bar.form-submit-bar--sticky` (Section 11) — con trực tiếp, cuối cùng của `<form>`; nhóm nút trong `.submit-actions`
- [ ] Module SCSS có `@use 'form-patterns'`
- [ ] Không ancestor nào của bar có `overflow-hidden/auto` (sticky phải ghim được khi cuộn)
- [ ] Select trong bar (nếu có): không `ts-init`, `createTs(..., { dropdownParent: null })`
- [ ] `_setupDirtyGuard(form)` được gọi **cuối cùng** trong page controller (sau TomSelect/Flatpickr/Jodit)
- [ ] `<form>` full-width — không grid sidebar
- [ ] Mỗi tab panel có footer nav (Prev/Next buttons)
- [ ] `init()` trong x-data tự chuyển tab có lỗi server

### Slug auto-fill (khi form có slug)

- [ ] Slug field đặt ngay dưới tên field trong cùng tab/section
- [ ] `_setupSlugAutoFill(form)` được gọi trong page controller
- [ ] `VI_MAP` được `Object.freeze()`
- [ ] Edit form: slug có giá trị → `locked = true` từ đầu
- [ ] Auto-fill bọc debounce 300ms, có `flush()` trong submit (capture) và `cancel()` khi user gõ vào slug

### File & Image Upload (khi form có upload)

- [ ] `file-input file-input-bordered file-input-sm w-full` cho upload đơn; form có `enctype="multipart/form-data"`
- [ ] Ảnh: khung preview phía trên nút upload (create + edit)
- [ ] Edit ảnh: nút X "Xóa ảnh hiện tại" + `<input type="hidden" name="remove_[field]" value="1" :disabled="!remove">`
- [ ] Backend validate `remove_[field]` (`nullable|boolean`) và xử lý: file mới → thay; không file + remove=1 → xóa; còn lại giữ nguyên
- [ ] Nhiều file: `initFilePondUpload()`, `filepond.js` load trước module JS

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
