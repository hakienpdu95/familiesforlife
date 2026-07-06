# Assessment Registry — Kế hoạch Implementation

> **Ngày tạo:** 2026-06-30  
> **Liên quan:** `spec/plan_assessment.md` (consolidation engine — lộ trình dài hơn)

---

## Bối cảnh & Quyết định thiết kế

### Vấn đề phát hiện

Sau khi phân tích toàn bộ consumer của Assessment engine:

| Consumer | Cách dùng |
|---|---|
| `SurveyResponse` | `survey.assessment_code` → linked khi enable scoring |
| `Lead` | `organization.lead_assessment_code` → org config |
| `WorkflowAutomation` | lắng nghe `AssessmentCompleted` event, filter theo `assessment_code` |
| `PerformanceReview` | `SyncWorkforceProfileOnPerformanceReviewFinalizedListener` |
| Future: HR Eval, Recruitment, Customer scoring | cần assessment độc lập, không có survey |

**Bug listing:** `AssessmentController::index()` dùng `Assessment::orderBy()->paginate()` đi qua
`TenantAwareModel → OrganizationScope`. Scope lọc `organization_id = TenantContext::getOrganizationId()`.
Global assessments (TDWCF, FivePillar, readiness_v1) có `organization_id = NULL` → **không bao giờ hiển thị**.

**UX confusion:** Tạo assessment qua UI rồi không biết nó dùng ở đâu vì thiếu thông tin nguồn.

### Quyết định: Assessment là Scoring Profile Registry độc lập

**Không** gắn Assessment phụ thuộc hoàn toàn vào Survey. Assessment = entity độc lập mà
nhiều module có thể borrow qua `assessment_code` string key.

```
Assessment (Scoring Profile Registry)
    ├── global_template → org_id = NULL, seeded, dùng chung toàn platform
    ├── survey_linked   → auto-tạo khi enable scoring trong Survey
    ├── lead_scoring    → standalone, gán qua organizations.lead_assessment_code
    └── standalone      → tạo thủ công cho future modules (HR eval, Recruitment…)
```

**Nguyên tắc bất biến (không thay đổi):**
- Assessment module không import model của domain modules
- Domain modules implement `ScoringSubjectInterface` và tự trigger scoring
- `assessment_code` string key là khớp nối duy nhất giữa các modules

---

## Tổng quan các Phase

| Phase | Tên | Migration | Ưu tiên | Trạng thái |
|---|---|---|---|---|
| R1 | Fix listing + Scope column | Không | 🔴 Ngay | ⬜ Chưa làm |
| R2 | source_type migration + Create context | Có | 🟠 Sau R1 | ⬜ Chưa làm |

---

## Phase R1 — Fix Listing + Scope Indicator

> **Migration:** Không  
> **Rủi ro:** Thấp — chỉ sửa query + view  
> **Thời gian ước tính:** 1-2 giờ

### Mục tiêu

- Hiển thị đúng tất cả assessments: global + org hiện tại
- Phân biệt trực quan global template vs org-specific trên UI
- Không còn "tạo xong không thấy trong danh sách"

---

### R1.1 — Fix `AssessmentController::index()` query

**File:** `Modules/Assessment/app/Http/Controllers/AssessmentController.php`

```php
// TRƯỚC — chỉ thấy org-scoped, thiếu global (org_id = NULL):
public function index(): View
{
    $this->authorize('assessment.view');
    $assessments = Assessment::orderBy('name')->paginate(20);
    return view('assessment::assessments.index', compact('assessments'));
}

// SAU — thấy cả global lẫn org-scoped:
public function index(): View
{
    $this->authorize('assessment.view');

    $orgId = TenantContext::isSet() ? TenantContext::getOrganizationId() : null;

    // withoutTenant() bypass OrganizationScope để lấy cả global (org_id NULL)
    // lẫn assessments thuộc org hiện tại.
    $assessments = Assessment::withoutTenant()
        ->where(function ($q) use ($orgId) {
            $q->whereNull('organization_id');
            if ($orgId) {
                $q->orWhere('organization_id', $orgId);
            }
        })
        ->orderBy('name')
        ->paginate(20);

    return view('assessment::assessments.index', compact('assessments'));
}
```

**Import cần thêm:** `use App\Shared\Tenancy\TenantContext;`

---

### R1.2 — Thêm cột "Phạm vi" vào index view

**File:** `Modules/Assessment/resources/views/assessments/index.blade.php`

Thêm column sau "Assessment Code":

```blade
{{-- Trong <thead> --}}
<th>Phạm vi</th>

{{-- Trong <tbody>, sau td assessment_code --}}
<td>
    @if(is_null($a->organization_id))
        <span class="badge badge-xs badge-info">Global</span>
    @else
        <span class="badge badge-xs badge-ghost">Org</span>
    @endif
</td>
```

**Xóa nút "Thêm Assessment":** Tạm thời ẩn hoặc xóa nút `+ Thêm Assessment` ở đầu trang
(sẽ thay bằng create form có context hơn ở Phase R2):

```blade
{{-- Xóa dòng này --}}
<a href="{{ route('assessments.create') }}" class="btn btn-primary btn-sm">+ Thêm Assessment</a>
```

---

### R1 Verify checklist

```bash
# 1. Mở /dashboard/assessments
# ✓ Thấy TDWCF, FivePillar, readiness_v1 với badge "Global"
# ✓ Thấy assessments của org hiện tại với badge "Org"
# ✓ Không còn danh sách trống khi mới tạo assessment qua Survey

# 2. Tạo survey mới → Enable Scoring → Quay về /dashboard/assessments
# ✓ Assessment vừa tạo xuất hiện trong danh sách với badge "Org"
```

---

## Phase R2 — source_type Migration + Create Context

> **Migration:** Có (thêm 2 columns nullable, không breaking)  
> **Rủi ro:** Thấp  
> **Thời gian ước tính:** 3-4 giờ  
> **Dependency:** R1 phải xong trước

### Mục tiêu

- Mỗi Assessment biết mình được tạo ra vì mục đích gì và từ đâu
- Index view hiển thị "Nguồn" chi tiết: Survey slug / Lead / Global / Standalone
- Create form có context để người dùng không bị confuse

---

### R2.1 — Migration thêm `source_type` và `source_ref`

```bash
php artisan make:migration add_source_type_to_assessments_table \
    --path=Modules/Assessment/database/migrations
```

```php
public function up(): void
{
    Schema::table('assessments', function (Blueprint $table) {
        // Enum dạng string để linh hoạt mở rộng về sau
        $table->string('source_type', 30)
              ->default('standalone')
              ->after('is_active')
              ->comment('global_template|survey_linked|lead_scoring|standalone');

        // slug/code của entity nguồn — điền khi biết
        $table->string('source_ref', 255)
              ->nullable()
              ->after('source_type')
              ->comment('VD: survey slug nếu survey_linked');
    });
}

public function down(): void
{
    Schema::table('assessments', function (Blueprint $table) {
        $table->dropColumn(['source_type', 'source_ref']);
    });
}
```

**Ý nghĩa `source_type`:**

| Giá trị | Ai set | Khi nào |
|---|---|---|
| `global_template` | Seeder | Platform-wide templates (TDWCF, FivePillar…) |
| `survey_linked` | `EnsureAssessmentLinkedAction` | Auto khi Survey enable scoring |
| `lead_scoring` | Admin tạo thủ công | Dùng cho `org.lead_assessment_code` |
| `standalone` | Admin tạo thủ công | Custom / future module use case |

---

### R2.2 — Update fillable trong Assessment model

**File:** `Modules/Assessment/app/Models/Assessment.php`

```php
protected $fillable = [
    'organization_id',
    'assessment_code',
    'name',
    'version',
    'is_active',
    'has_scoring',
    'aggregation_model',
    'classification_type',
    'source_type',    // thêm
    'source_ref',     // thêm
];
```

---

### R2.3 — Update Seeders: set `source_type = global_template`

Các seeder cần cập nhật:

**`Modules/Assessment/database/seeders/TdwcfAssessmentSeeder.php`**
```php
Assessment::firstOrCreate(
    ['assessment_code' => self::CODE],
    [
        // ... fields hiện tại ...
        'source_type' => 'global_template',
    ]
);
```

**`Modules/Assessment/database/seeders/FivePillarAssessmentSeeder.php`** — tương tự

**`Modules/Deployment/database/seeders/ReadinessV1SurveySeeder.php`** — tương tự (nếu có tạo Assessment record)

**Data migration cho rows đã có** (trong migration up hoặc seeder riêng):
```php
// Cập nhật rows cũ dựa theo pattern assessment_code
DB::table('assessments')->whereNull('organization_id')
    ->update(['source_type' => 'global_template']);

// Rows có organization_id nhưng chưa biết nguồn → giữ 'standalone' (default)
```

---

### R2.4 — Update `EnsureAssessmentLinkedAction`

**File:** `Modules/Survey/app/Actions/EnsureAssessmentLinkedAction.php`

```php
Assessment::firstOrCreate(
    ['assessment_code' => $code],
    [
        'name'                => $survey->title,
        'aggregation_model'   => 'weighted_domain',
        'classification_type' => 'score_band',
        'has_scoring'         => true,
        'is_active'           => true,
        'source_type'         => 'survey_linked',  // thêm
        'source_ref'          => $survey->slug,    // thêm
    ]
);
```

---

### R2.5 — Cập nhật Assessment `store()` nhận `source_type`

**File:** `Modules/Assessment/app/Http/Controllers/AssessmentController.php`

```php
public function store(Request $request)
{
    $this->authorize('assessment.config');

    $data = $request->validate([
        'organization_id'    => 'required|integer|exists:organizations,id',
        'name'               => 'required|string|max:255',
        'aggregation_model'  => 'required|in:weighted_domain,flat_sum,sectioned',
        'classification_type'=> 'required|in:score_band,pass_fail,persona_match,none',
        'has_scoring'        => 'boolean',
        'source_type'        => 'required|in:lead_scoring,standalone',  // thêm; chỉ 2 loại admin tạo thủ công
        'source_ref'         => 'nullable|string|max:255',              // thêm
    ]);

    $data['assessment_code'] = $this->generateUniqueCode($data['name']);
    $data['has_scoring']     = $request->boolean('has_scoring');

    Assessment::create($data);

    return redirect()->route('assessments.index')->with('success', 'Đã tạo assessment.');
}
```

---

### R2.6 — Update Create form: thêm field "Mục đích sử dụng"

**File:** `Modules/Assessment/resources/views/assessments/create.blade.php`

Thêm field trước nút Submit:

```blade
{{-- Mục đích sử dụng --}}
<div class="form-control mb-4">
    <label class="label py-0 pb-1.5">
        <span class="label-text font-medium text-sm">Mục đích sử dụng <span class="text-error">*</span></span>
    </label>
    <select name="source_type"
            class="select select-bordered select-sm w-full @error('source_type') select-error @enderror">
        <option value="standalone" {{ old('source_type') === 'standalone' ? 'selected' : '' }}>
            Standalone — custom / future modules
        </option>
        <option value="lead_scoring" {{ old('source_type') === 'lead_scoring' ? 'selected' : '' }}>
            Lead Scoring — dùng cho organizations.lead_assessment_code
        </option>
    </select>
    <p class="mt-1 text-xs text-base-content/40">
        Survey-linked và Global template được tạo tự động — không tạo thủ công.
    </p>
    @error('source_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
</div>

{{-- Ghi chú nguồn --}}
<div class="form-control mb-4">
    <label class="label py-0 pb-1.5">
        <span class="label-text font-medium text-sm">Ghi chú nguồn</span>
        <span class="label-text-alt text-xs text-base-content/40">Tùy chọn</span>
    </label>
    <input type="text" name="source_ref"
           value="{{ old('source_ref') }}"
           placeholder="VD: crm-lead-q3, hr-eval-2026"
           class="input input-bordered input-sm w-full @error('source_ref') input-error @enderror">
    @error('source_ref')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
</div>
```

**Khôi phục nút "Thêm Assessment"** (đã ẩn ở R1) với label rõ hơn:

```blade
{{-- Trong index.blade.php --}}
@can('assessment.config')
<a href="{{ route('assessments.create') }}" class="btn btn-primary btn-sm">
    + Tạo Scoring Profile
</a>
@endcan
```

---

### R2.7 — Thay cột "Phạm vi" (R1) bằng cột "Nguồn" chi tiết hơn

**File:** `Modules/Assessment/resources/views/assessments/index.blade.php`

```blade
{{-- Trong <thead> — đổi tên cột --}}
<th>Nguồn</th>

{{-- Trong <tbody> — thay logic badge --}}
<td>
    @switch($a->source_type ?? 'standalone')
        @case('global_template')
            <span class="badge badge-xs badge-info">Global template</span>
            @break
        @case('survey_linked')
            <span class="badge badge-xs badge-success font-mono text-xs">
                Survey: {{ $a->source_ref ?? '—' }}
            </span>
            @break
        @case('lead_scoring')
            <span class="badge badge-xs badge-warning">Lead scoring</span>
            @break
        @default
            <span class="badge badge-xs badge-ghost">Standalone</span>
    @endswitch
</td>
```

---

### R2 Verify checklist

```bash
# 1. Chạy migration
php artisan migrate

# 2. Re-seed global assessments
php artisan db:seed --class="Modules\Assessment\Database\Seeders\TdwcfAssessmentSeeder"
php artisan db:seed --class="Modules\Assessment\Database\Seeders\FivePillarAssessmentSeeder"

# 3. Kiểm tra /dashboard/assessments
# ✓ TDWCF, FivePillar: badge "Global template"
# ✓ Assessments tạo từ Survey: badge "Survey: <slug>"
# ✓ Assessments mới tạo thủ công: badge "Lead scoring" hoặc "Standalone"

# 4. Tạo Survey mới → Enable Scoring → kiểm tra assessment mới:
# ✓ source_type = survey_linked
# ✓ source_ref = slug của survey

# 5. Tạo assessment thủ công qua form:
# ✓ Form có field "Mục đích sử dụng"
# ✓ Chỉ hiện 2 option: standalone và lead_scoring
# ✓ Sau tạo, hiển thị đúng badge trong index
```

---

## Sơ đồ toàn cảnh sau R1 + R2

```
dashboard/surveys/{survey}/edit
    └── [Cấu hình Scoring] button
            │
            ▼
    SurveyScoringRedirectController
            │
            ▼
    EnsureAssessmentLinkedAction
        → Assessment::firstOrCreate(
            assessment_code = str_replace('-','_', slug),
            source_type     = 'survey_linked',
            source_ref      = survey.slug
          )
            │
            ▼
    redirect → dashboard/assessments/{code}/config


dashboard/assessments/create  (thủ công)
    └── source_type: [lead_scoring | standalone]
            │
            ▼
    AssessmentController::store()
            │
            ▼
    Assessment record mới


php artisan db:seed  (global templates)
    └── source_type: global_template
    └── organization_id: NULL


dashboard/assessments/  (index — sau R1+R2)
    ┌─────────────────────────────────────────────────────────────┐
    │ Code       │ Tên         │ Nguồn              │ Scope  │ … │
    ├─────────────────────────────────────────────────────────────┤
    │ tdwcf      │ TDWCF       │ [Global template]  │ Global │   │
    │ ai-ready-x │ AI Ready    │ [Survey: ai-ready] │ Org    │   │
    │ lead-score │ Lead Score  │ [Lead scoring]     │ Org    │   │
    │ custom-x   │ HR Eval     │ [Standalone]       │ Org    │   │
    └─────────────────────────────────────────────────────────────┘
```

---

## Pattern cho module mới muốn dùng Assessment

Khi muốn chấm điểm một entity mới (ví dụ: HR Evaluation):

1. **Model implement `ScoringSubjectInterface`**
2. **`getAssessmentCode()`** đọc từ org config (`organization.hr_assessment_code`)
3. **Tạo assessment** qua `dashboard/assessments/create` với `source_type = standalone`
4. **Gán code** vào `organizations.hr_assessment_code`
5. **Cấu hình scoring** tại `dashboard/assessments/{code}/config`
6. **Listener trigger** `RunAssessmentAction::dispatch($entity)` khi entity thay đổi

> Chi tiết pattern đầy đủ xem Phase 8 trong `spec/plan_assessment.md`.
