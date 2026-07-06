# Đặc tả thiết kế 5 Module/Hạng mục mới — THUCHOCVN Vertical AI Platform

> **Pattern stack:** AVSA + CQRS-lite + Laravel Modules (NWIDART 13) + Laravel Actions (lorisleiva 2.x)

> Tài liệu đặc tả kỹ thuật chi tiết cho 5 module/hạng mục đã đề xuất tại [`01-SO-SANH-HE-THONG-HIEN-TAI-VA-DE-XUAT-MODULE-MOI.md`](./01-SO-SANH-HE-THONG-HIEN-TAI-VA-DE-XUAT-MODULE-MOI.md), theo đúng thứ tự phụ thuộc:
>
> 1. `BusinessSolution`
> 2. `BusinessBlueprint`
> 3. `OrganizationSolution`
> 4. Deployment Engine (mở rộng `Modules/Deployment` hiện có)
> 5. `SolutionCatalog`
>
> Mỗi module được đặc tả theo đúng **quy ước code hiện có** của repo (NWIDART Laravel Modules + CQRS Feature-folder + Lorisleiva Actions + Spatie Laravel Data + TenantAwareModel), để đội dev có thể bắt tay code ngay mà không cần suy đoán convention. Tài liệu tham chiếu ngược lại các Business Rules (BR/DP/RR/OR/DA/DB) trong 14 tài liệu gốc để dev biết "vì sao" mỗi ràng buộc tồn tại.

---

## Phần 0. Quy ước dùng chung cho cả 5 module

Toàn bộ code mới phải theo đúng pattern đã quan sát được trong `Modules/OcopRubric` (module mới nhất, đúng chuẩn nhất hiện có) và `app/Shared/*`:

```
Modules/{ModuleName}/
├── app/
│   ├── Enums/                              # backed string enum + method label()
│   ├── Features/{FeatureName}/
│   │   ├── Actions/                        # Lorisleiva Action (use AsAction), 1 action = 1 use case ghi
│   │   ├── Data/                           # Spatie\LaravelData\Data + validation attributes
│   │   ├── Events/                         # domain event (Published, Activated...)
│   │   ├── Http/Controllers/               # controller mỏng, gọi Action/Query
│   │   └── Queries/                        # {X}Query implements QueryInterface
│   │                                       # {X}Handler implements QueryHandlerInterface
│   ├── Models/                              # Eloquent — TenantAwareModel nếu tenant-scoped, Model thường nếu system-level
│   ├── Policies/
│   └── Providers/
│       ├── {ModuleName}ServiceProvider.php  # extends Nwidart ModuleServiceProvider, đăng ký Gate::policy
│       ├── RouteServiceProvider.php
│       └── EventServiceProvider.php
├── config/config.php
├── database/migrations/
├── database/seeders/
├── resources/views/
└── routes/{web,api}.php
```

**Quy tắc bắt buộc áp dụng cho mọi bảng mới** (đúng A09.1 §1, A09.3 §1):
- `id` bigint auto-increment (giữ nguyên chuẩn hiện tại của repo, **không đổi sang UUID** — repo đã dùng bigint xuyên suốt; chỉ thêm cột `uuid` phụ nếu cần lộ ra ngoài route/API, đúng pattern `OcopRubricVersion`/`DeploymentTarget`).
- `created_at`, `updated_at` bắt buộc; `deleted_at` (soft delete) cho các bảng "định nghĩa"/"cấu hình" quan trọng theo danh sách ở A09.2 §5.1.
- Bảng **Definition Data cấp hệ thống** (verticals, business_solutions, blueprints, blueprint_versions và toàn bộ bảng con của blueprint_version) — **KHÔNG** dùng `TenantAwareModel`/`BelongsToOrganization` (không có `organization_id` global scope), giữ đúng pattern `OcopRubricVersion` (`extends Model` thường) — vì đây là dữ liệu dùng chung/thư viện, không thuộc về 1 tổ chức. Ngoại lệ: `blueprints`/`business_solutions` có thể có `organization_id` **nullable** để hỗ trợ "Blueprint riêng của 1 tổ chức" (tương tự cách `vertical_templates` hiện tại cho phép `organization_id NULL` = thư viện dùng chung).
- Bảng **Configuration Data / Runtime Data cấp tổ chức** (organization_solutions, organization_*_configs, deployments...) — dùng `TenantAwareModel` (tự động có `organization_id`, soft delete, activity log).
- `status` lưu dạng `string` ánh xạ enum (đúng pattern `RubricVersionStatus`), **không dùng `tinyint`** để dễ đọc trực tiếp trong DB khi debug (giữ đúng convention đã thấy ở `ocop_rubric_versions.status`).
- Mọi migration mới đặt tên file theo mẫu `{YYYY_MM_DD}_{HHMMSS}_create_{table}_table.php`, dùng `Schema::create` riêng biệt từng bảng (không gộp nhiều bảng 1 file), đúng cách `OcopRubric` đã làm.
- Permissions mới khai báo trong `app/Enums/PermissionEnum.php` rồi map vào role tại `config/permissions.php` (không sửa trực tiếp DB permissions — chạy `php artisan permissions:sync` sau khi sửa, đúng comment đầu file `config/permissions.php`).

---

## Phần 1. Module `BusinessSolution`

### 1.1 Mục đích & phạm vi

Tạo danh mục **Business Solution** tách biệt khỏi Vertical — đúng A01 §6.2, A02 §6.2, A03 §3.3. Đây là bảng "cha" mà `BusinessBlueprint` (Phần 2) và `OrganizationSolution` (Phần 3) đều tham chiếu tới. Module này **không thay thế** `Modules/Deployment`/`Assessment`/`OcopRubric` — nó chỉ là lớp danh mục đăng ký các Solution đã/sẽ tồn tại.

### 1.2 Vị trí trong kiến trúc

```
verticals (mới)
    └── business_solutions (mới)
            ├── business_solution_versions (mới)
            ├── business_solution_categories (mới)
            ├── business_solution_tags (mới)
            └── blueprints (Phần 2) ────┐
                                        │
organizations (đã có)                  │
    └── organization_solutions (Phần 3, tham chiếu business_solutions + blueprint_versions)
```

### 1.3 Database schema

```php
// 2026_08_01_000001_create_verticals_table.php
Schema::create('verticals', function (Blueprint $table) {
    $table->id();
    $table->string('code', 50)->unique();      // agriculture, insurance, workforce, education...
    $table->string('name', 255);
    $table->text('description')->nullable();
    $table->string('icon', 100)->nullable();
    $table->string('status', 20)->default('active'); // VerticalStatus: active|inactive
    $table->json('metadata')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

// 2026_08_01_000002_create_business_solutions_table.php
Schema::create('business_solutions', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('vertical_id')->constrained('verticals')->restrictOnDelete();
    $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete(); // NULL = solution dùng chung toàn platform
    $table->string('code', 50)->unique();       // AI-TXNG, AI-OCOP, AI-WORKFORCE
    $table->string('name', 255);
    $table->string('slug', 255)->unique();
    $table->text('short_description')->nullable();
    $table->longText('description')->nullable();
    $table->json('target_customers')->nullable();      // ["htx", "sme", ...]
    $table->string('status', 20)->default('draft');     // BusinessSolutionStatus: draft|published|archived
    $table->string('visibility', 20)->default('private'); // private|public|marketplace
    $table->string('thumbnail_url', 500)->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['vertical_id', 'status']);
});

// 2026_08_01_000003_create_business_solution_versions_table.php
Schema::create('business_solution_versions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_solution_id')->constrained('business_solutions')->cascadeOnDelete();
    $table->string('version', 30);              // "1.0.0"
    $table->string('status', 20)->default('draft'); // draft|published|deprecated
    $table->text('release_note')->nullable();
    $table->timestamp('published_at')->nullable();
    $table->unsignedBigInteger('published_by')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();

    $table->unique(['business_solution_id', 'version']);
});

// 2026_08_01_000004_create_business_solution_categories_table.php
Schema::create('business_solution_categories', function (Blueprint $table) {
    $table->id();
    $table->string('name', 255);
    $table->string('slug', 255)->unique();
    $table->text('description')->nullable();
    $table->string('status', 20)->default('active');
    $table->timestamps();
});

// 2026_08_01_000005_create_business_solution_tags_table.php
Schema::create('business_solution_tags', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_solution_id')->constrained('business_solutions')->cascadeOnDelete();
    $table->string('tag', 100);
    $table->timestamps();

    $table->index(['business_solution_id', 'tag']);
});
```

### 1.4 Models (`Modules/BusinessSolution/app/Models/`)

- `Vertical extends Model` — `hasMany(BusinessSolution::class)`.
- `BusinessSolution extends Model` (không tenant-aware; `organization_id` chỉ để đánh dấu solution riêng của 1 tổ chức nếu có) — quan hệ: `belongsTo(Vertical::class)`, `hasMany(BusinessSolutionVersion::class)`, `hasMany(BusinessSolutionTag::class)`, `hasMany(Blueprint::class)` (từ module Phần 2), `hasMany(OrganizationSolution::class)` (từ module Phần 3).
- `BusinessSolutionVersion extends Model`.
- `BusinessSolutionCategory`, `BusinessSolutionTag`.

### 1.5 Enums (`Modules/BusinessSolution/app/Enums/`)

```php
enum VerticalStatus: string { case Active = 'active'; case Inactive = 'inactive'; }

enum BusinessSolutionStatus: string
{
    case Draft = 'draft'; case Published = 'published'; case Archived = 'archived';
    public function label(): string { /* Nháp / Đã phát hành / Đã lưu trữ */ }
}

enum BusinessSolutionVisibility: string
{
    case Private = 'private'; case Public = 'public'; case Marketplace = 'marketplace';
}
```

### 1.6 Actions & Queries

**Feature `SolutionCatalogManagement`** (quản trị — System Admin):
- `Actions/CreateBusinessSolutionAction` — nhận `CreateBusinessSolutionData` (code, name, vertical_id, short_description, target_customers[]), tạo bản ghi `status=draft`.
- `Actions/UpdateBusinessSolutionAction`.
- `Actions/PublishBusinessSolutionAction` — chuyển `status → published`; **điều kiện tiên quyết**: phải tồn tại ít nhất 1 `blueprint` với `blueprints.status = published` thuộc solution này (enforce OR-006 tương đương ở tầng Solution — không cho publish Solution rỗng không có Blueprint nào).
- `Actions/ArchiveBusinessSolutionAction`.
- `Queries/ListBusinessSolutionsQuery` (filter: vertical_id, status, visibility, search) + Handler.
- `Queries/GetBusinessSolutionQuery` + Handler (eager load `versions`, `tags`, `vertical`).

### 1.7 Permissions mới (`app/Enums/PermissionEnum.php`)

```
SOLUTION_CATALOG_VIEW      // xem danh mục Business Solution
SOLUTION_CATALOG_MANAGE    // tạo/sửa/publish/archive Business Solution (System Admin, Product Owner)
```
Gán `SOLUTION_CATALOG_MANAGE` cho role `System_Admin`; `SOLUTION_CATALOG_VIEW` cho tất cả role còn lại (mọi role cần xem danh mục để biết tổ chức có thể kích hoạt Solution nào).

### 1.8 Routes (`routes/web.php`)

```php
Route::middleware(['web', 'auth', 'can:' . P::SOLUTION_CATALOG_MANAGE->value])
    ->prefix('dashboard/business-solutions/admin')
    ->name('business_solutions.admin.')
    ->group(function (): void {
        Route::resource('/', BusinessSolutionController::class);
        Route::post('{businessSolution}/publish', [BusinessSolutionController::class, 'publish'])->name('publish');
        Route::post('{businessSolution}/archive', [BusinessSolutionController::class, 'archive'])->name('archive');
    });
```

### 1.9 Tích hợp với hệ thống hiện có

- Seeder `BusinessSolutionCatalogSeeder` đăng ký **3 solution bespoke hiện có** làm dữ liệu khởi tạo:
  - `code=AI-TXNG, vertical=agriculture` → tương ứng `Modules/Deployment` (+ `Modules/Project`, `Modules/Survey`).
  - `code=AI-OCOP, vertical=agriculture` → tương ứng `Modules/OcopRubric`.
  - `code=AI-WORKFORCE, vertical=workforce` → tương ứng `Modules/Assessment`.
  - Các bản ghi này **chỉ mang tính khai báo/catalog** — không thay đổi logic vận hành của 3 module đó ở giai đoạn này.
- Không sửa `config/modules.php`/`modules_statuses.json` của 3 module hiện có.

---

## Phần 2. Module `BusinessBlueprint`

### 2.1 Mục đích & phạm vi

Đây là module **quan trọng nhất** — lấp khoảng trống lớn nhất được nêu ở Phần 2 của tài liệu so sánh. Hiện thực hoá đầy đủ 8 thành phần Blueprint (A04.1 §5.2) + cơ chế Versioning/Publish (A04.3) + Blueprint Builder API (A04.2), thay thế dần vai trò "thiết kế" hiện đang nằm rải rác trong `app/Foundation/Vertical/*`.

### 2.2 Vị trí trong kiến trúc

```
business_solutions (Phần 1)
    └── blueprints
            └── blueprint_versions  (immutable sau khi published; parent_version_id = lineage của Clone)
                    ├── blueprint_outcomes
                    ├── blueprint_capabilities
                    ├── blueprint_workflows
                    │       └── blueprint_phases
                    │               └── blueprint_checklists
                    ├── blueprint_resource_links   (→ Sop::sop_processes, KcItem::kc_items)
                    ├── blueprint_ai_capabilities   (→ AiCopilot::ai_agents, ai_prompts)
                    ├── blueprint_analytics
                    └── blueprint_deployment_settings
```

### 2.3 Database schema

```php
Schema::create('blueprints', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_solution_id')->constrained('business_solutions')->restrictOnDelete();
    $table->string('code', 50)->unique();
    $table->string('name', 255);
    $table->text('description')->nullable();
    $table->foreignId('current_version_id')->nullable(); // FK thêm sau khi blueprint_versions tồn tại (xem migration ALTER dưới)
    $table->string('status', 20)->default('draft');       // draft|published|archived (trạng thái tổng quát của "chuỗi version")
    $table->json('metadata')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

Schema::create('blueprint_versions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('blueprint_id')->constrained('blueprints')->cascadeOnDelete();
    $table->string('version', 30);                 // semantic version "1.0.0"
    $table->string('status', 20)->default('draft'); // BlueprintVersionStatus (9 trạng thái, xem 2.5)
    $table->text('release_note')->nullable();
    $table->timestamp('published_at')->nullable();
    $table->unsignedBigInteger('published_by')->nullable();
    $table->foreignId('parent_version_id')->nullable()->constrained('blueprint_versions')->nullOnDelete(); // lineage Clone
    $table->json('snapshot')->nullable();          // optional full-tree snapshot tại thời điểm publish
    $table->json('metadata')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->unique(['blueprint_id', 'version']);
    $table->index(['blueprint_id', 'status']);
});

// ALTER sau khi blueprint_versions tồn tại — thêm FK current_version_id
Schema::table('blueprints', function (Blueprint $table) {
    $table->foreign('current_version_id')->references('id')->on('blueprint_versions')->nullOnDelete();
});

Schema::create('blueprint_outcomes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->cascadeOnDelete();
    $table->string('code', 50);
    $table->string('name', 255);
    $table->text('description')->nullable();
    $table->string('success_metric', 255)->nullable();
    $table->unsignedInteger('sort_order')->default(0);
    $table->string('status', 20)->default('active');
    $table->timestamps();
});

Schema::create('blueprint_capabilities', function (Blueprint $table) {
    $table->id();
    $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->cascadeOnDelete();
    $table->foreignId('outcome_id')->nullable()->constrained('blueprint_outcomes')->nullOnDelete();
    $table->string('code', 50);
    $table->string('name', 255);
    $table->text('description')->nullable();
    $table->string('capability_type', 50)->nullable();
    $table->unsignedInteger('sort_order')->default(0);
    $table->string('status', 20)->default('active');
    $table->timestamps();
});

Schema::create('blueprint_workflows', function (Blueprint $table) {
    $table->id();
    $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->cascadeOnDelete();
    $table->foreignId('capability_id')->nullable()->constrained('blueprint_capabilities')->nullOnDelete();
    $table->string('code', 50);
    $table->string('name', 255);
    $table->text('description')->nullable();
    $table->unsignedInteger('sort_order')->default(0);
    $table->string('status', 20)->default('active');
    $table->timestamps();
});

Schema::create('blueprint_phases', function (Blueprint $table) {
    $table->id();
    $table->foreignId('workflow_id')->constrained('blueprint_workflows')->cascadeOnDelete();
    $table->string('code', 50);
    $table->string('name', 255);
    $table->text('description')->nullable();
    $table->unsignedInteger('sort_order')->default(0);
    $table->text('entry_condition')->nullable();
    $table->text('exit_condition')->nullable();
    $table->boolean('is_initial')->default(false);          // migrate từ vertical_phases.is_initial
    $table->boolean('auto_assign_data_collection')->default(false); // migrate từ vertical_phases
    $table->string('status', 20)->default('active');
    $table->timestamps();
});

Schema::create('blueprint_checklists', function (Blueprint $table) {
    $table->id();
    $table->foreignId('phase_id')->constrained('blueprint_phases')->cascadeOnDelete();
    $table->string('code', 50);
    $table->string('name', 255);
    $table->text('description')->nullable();
    $table->text('input_description')->nullable();
    $table->text('action_description')->nullable();
    $table->text('output_description')->nullable();
    $table->boolean('required')->default(true);              // migrate từ vertical_checklist_items.is_required
    $table->string('default_priority', 20)->default('normal'); // low|normal|high
    $table->decimal('estimated_hours', 6, 2)->nullable();
    $table->boolean('need_approval')->default(false);
    $table->unsignedInteger('sort_order')->default(0);
    $table->string('status', 20)->default('active');
    $table->timestamps();
});

Schema::create('blueprint_resource_links', function (Blueprint $table) {
    $table->id();
    $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->cascadeOnDelete();
    $table->foreignId('checklist_id')->nullable()->constrained('blueprint_checklists')->nullOnDelete();
    $table->string('resource_type', 50);  // 'sop' | 'knowledge' | 'dataset' | 'template' — polymorphic mềm
    $table->unsignedBigInteger('resource_id'); // trỏ tới sop_processes.id hoặc kc_items.id tuỳ resource_type
    $table->boolean('is_required')->default(false);
    $table->unsignedInteger('sort_order')->default(0);
    $table->json('metadata')->nullable();
    $table->timestamps();

    $table->index(['resource_type', 'resource_id']);
});

Schema::create('blueprint_ai_capabilities', function (Blueprint $table) {
    $table->id();
    $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->cascadeOnDelete();
    $table->foreignId('checklist_id')->nullable()->constrained('blueprint_checklists')->nullOnDelete();
    $table->string('capability_code', 100); // 'ocr' | 'document_validation' | 'summary' | 'recommendation' | 'scoring'...
    $table->string('name', 255);
    $table->text('description')->nullable();
    $table->unsignedBigInteger('ai_agent_id')->nullable();   // FK mềm → Modules\AiCopilot\Models\AiAgent (module khác, không constrained() cứng qua DB để giữ module rời)
    $table->unsignedBigInteger('ai_prompt_id')->nullable();  // FK mềm → Modules\AiCopilot\Models\AiPrompt
    $table->string('trigger_event', 100)->nullable(); // 'on_checklist_upload' | 'on_checklist_complete' ...
    $table->string('status', 20)->default('active');
    $table->json('metadata')->nullable();
    $table->timestamps();
});

Schema::create('blueprint_analytics', function (Blueprint $table) {
    $table->id();
    $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->cascadeOnDelete();
    $table->string('metric_code', 100);
    $table->string('name', 255);
    $table->text('description')->nullable();
    $table->string('metric_type', 50)->nullable();   // count|percentage|average|sum
    $table->text('formula')->nullable();
    $table->string('source_type', 50)->nullable();   // checklist|task|ai_result|file
    $table->string('status', 20)->default('active');
    $table->timestamps();
});

Schema::create('blueprint_deployment_settings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->cascadeOnDelete();
    $table->string('setting_key', 100);   // 'default_roles' | 'sidebar_config' | ...
    $table->json('setting_value')->nullable();
    $table->text('description')->nullable();
    $table->timestamps();

    $table->unique(['blueprint_version_id', 'setting_key']);
});
```

> Ghi chú kỹ thuật: `blueprint_ai_capabilities.ai_agent_id`/`ai_prompt_id` **cố ý không đặt `foreignId()->constrained()`** (không ràng buộc khoá ngoại DB cứng sang bảng của module `AiCopilot`) để giữ đúng triết lý Modular Monolith "không phụ thuộc chặt schema chéo module" — validate tính hợp lệ ở tầng Action (`UpsertBlueprintAiCapabilityAction` gọi `AiAgent::findOrFail()`/`AiPrompt::findOrFail()` trước khi lưu). Tương tự cho `blueprint_resource_links.resource_id`.

### 2.4 Models (`Modules/BusinessBlueprint/app/Models/`)

`Blueprint`, `BlueprintVersion`, `BlueprintOutcome`, `BlueprintCapability`, `BlueprintWorkflow`, `BlueprintPhase`, `BlueprintChecklist`, `BlueprintResourceLink`, `BlueprintAiCapability`, `BlueprintAnalytic`, `BlueprintDeploymentSetting` — tất cả `extends Model` thường (system-level, không tenant-scoped), theo đúng pattern `OcopRubricVersion`.

Quan hệ chính cần khai báo:
```php
// BlueprintVersion
public function outcomes(): HasMany { return $this->hasMany(BlueprintOutcome::class); }
public function capabilities(): HasMany { return $this->hasMany(BlueprintCapability::class); }
public function workflows(): HasMany { return $this->hasMany(BlueprintWorkflow::class); }
public function resourceLinks(): HasMany { return $this->hasMany(BlueprintResourceLink::class); }
public function aiCapabilities(): HasMany { return $this->hasMany(BlueprintAiCapability::class); }
public function analytics(): HasMany { return $this->hasMany(BlueprintAnalytic::class); }
public function deploymentSettings(): HasMany { return $this->hasMany(BlueprintDeploymentSetting::class); }
public function parentVersion(): BelongsTo { return $this->belongsTo(self::class, 'parent_version_id'); }

// BlueprintWorkflow
public function phases(): HasMany { return $this->hasMany(BlueprintPhase::class, 'workflow_id')->orderBy('sort_order'); }

// BlueprintPhase
public function checklists(): HasMany { return $this->hasMany(BlueprintChecklist::class, 'phase_id')->orderBy('sort_order'); }
```

### 2.5 Enum trạng thái (`BlueprintVersionStatus`, theo A04.3 §3)

```php
enum BlueprintVersionStatus: string
{
    case Draft            = 'draft';
    case InDesign          = 'in_design';
    case ReadyForReview     = 'ready_for_review';
    case Reviewing          = 'reviewing';
    case Approved           = 'approved';
    case Published          = 'published';
    case Deprecated         = 'deprecated';
    case Archived           = 'archived';

    /** Version ở các trạng thái này KHÔNG ĐƯỢC sửa nội dung — chỉ Clone. */
    public function isImmutable(): bool
    {
        return in_array($this, [self::Published, self::Deprecated, self::Archived], true);
    }

    /** Chỉ trạng thái Published mới được Deploy (BR-003 A04.1, OR-006 A07). */
    public function isDeployable(): bool
    {
        return $this === self::Published;
    }
}
```

### 2.6 Actions (Feature `BlueprintAuthoring`)

- `CreateBlueprintAction` — tạo `blueprints` + version đầu tiên `1.0.0` status=`draft`.
- `UpsertOutcomeAction`, `UpsertCapabilityAction`, `UpsertWorkflowAction`, `UpsertPhaseAction`, `UpsertChecklistAction`, `UpsertResourceLinkAction`, `UpsertAiCapabilityAction`, `UpsertAnalyticAction`, `UpsertDeploymentSettingAction` — mỗi Action **bắt buộc kiểm tra** `blueprintVersion->status` chưa `isImmutable()` trước khi cho sửa (ném `BlueprintVersionLockedException` nếu vi phạm — enforce BR-004 A04.1/Principle 01 A04.3).
- `CloneBlueprintVersionAction` — nhận `BlueprintVersion $source`, tạo bản ghi `blueprint_versions` mới với `parent_version_id = $source->id`, tăng version theo `IncrementSemVerAction` (tham số `level: major|minor|patch`), **deep-clone** toàn bộ outcomes/capabilities/workflows/phases/checklists/resource_links/ai_capabilities/analytics/deployment_settings sang bản mới — **không clone bất kỳ dữ liệu Runtime nào** (đúng Chương 5 A04.3: "Clone gồm Workflow/Capability/Resource Reference/AI Capability/Analytics, không Clone Runtime").
- `ValidateBlueprintReadinessAction` (hoặc Query — xem 2.7) — chạy 12 tiêu chí Readiness Checklist (A04.1 §7.4): có Business Solution, Overview đầy đủ, ≥1 Outcome, ≥1 Capability, ≥1 Workflow, Workflow có Phase, Phase có Checklist, có Resource tham chiếu, có Deployment Settings, có Version, có Author, Status hiện tại = `ready_for_review`/`approved`.
- `PublishBlueprintVersionAction` — chạy `ValidateBlueprintReadinessAction` trước; nếu pass → chuyển `status → published`, set `published_at/published_by`, ghi `current_version_id` trên `blueprints`, và **nếu version trước đó đang `published`** thì tự động chuyển nó sang `deprecated` (không xoá — RR/BR yêu cầu Runtime cũ vẫn đọc được version cũ).
- `ArchiveBlueprintVersionAction`.
- `CompareBlueprintVersionsAction` (trả `BlueprintVersionDiff` Data: `added[]`, `removed[]`, `changed[]` theo từng nhóm outcomes/capabilities/workflows/checklists) — hiện thực Chương 6 A04.3.

### 2.7 Queries

- `GetBlueprintTreeQuery` (blueprint_version_id) → trả full tree (outcome→capability→workflow→phase→checklist→resource/ai) dùng cho UI master-detail giống `RubricAuthoringController@tree` hiện có trong `OcopRubric` (đã có sẵn pattern tương tự, tái sử dụng ý tưởng).
- `ValidateBlueprintIntegrityQuery` (tương tự `ValidateRubricIntegrityQuery` đã có ở OcopRubric — kiểm tra không có node mồ côi, không trùng `code` trong cùng version).
- `ListBlueprintVersionsQuery` (theo `blueprint_id`, kèm đếm số `organization_solutions` đang dùng mỗi version — phục vụ màn hình Version Manager ở A04.3 §12).

### 2.8 Blueprint Readiness Checklist — bảng đối chiếu triển khai

| # | Tiêu chí (A04.1 §7.4) | Cách kiểm tra trong code |
|---|---|---|
| 1 | Có Business Solution | `blueprint->business_solution_id` not null |
| 2 | Overview đầy đủ | `blueprint->name`, `description` không rỗng |
| 3 | ≥1 Business Outcome | `blueprintVersion->outcomes()->count() > 0` |
| 4 | ≥1 Business Capability | `blueprintVersion->capabilities()->count() > 0` |
| 5 | ≥1 Workflow | `blueprintVersion->workflows()->count() > 0` |
| 6 | Workflow có Phase | mỗi `workflow->phases()->count() > 0` |
| 7 | Phase có Checklist | mỗi `phase->checklists()->count() > 0` |
| 8 | Có Resource tham chiếu | `blueprintVersion->resourceLinks()->count() > 0` |
| 9 | Có Deployment Settings | `blueprintVersion->deploymentSettings()->count() > 0` |
| 10 | Có Version | `blueprintVersion->version` hợp lệ semver |
| 11 | Có Author | `blueprint->created_by` not null |
| 12 | Status = sẵn sàng | `blueprintVersion->status === ReadyForReview` hoặc `Approved` |

### 2.9 Permissions mới

```
BLUEPRINT_VIEW, BLUEPRINT_CREATE, BLUEPRINT_EDIT, BLUEPRINT_DELETE,
BLUEPRINT_PUBLISH, BLUEPRINT_ARCHIVE, BLUEPRINT_CLONE
```
Ma trận quyền theo đúng A04.2 §6:

| Hành động | Business Analyst | Product Owner | System_Admin |
|---|---|---|---|
| BLUEPRINT_CREATE/EDIT/DELETE/CLONE | ✔ | ✔ | ✔ |
| BLUEPRINT_PUBLISH/ARCHIVE | ✖ | ✔ | ✔ |

*(Vai trò "Business Analyst" chưa tồn tại trong 8 role hiện có của hệ thống — xem khuyến nghị 2.11.)*

### 2.10 Routes

```php
Route::middleware(['web', 'auth', 'can:' . P::BLUEPRINT_VIEW->value])
    ->prefix('dashboard/business-blueprint/admin')
    ->name('business_blueprint.admin.')
    ->group(function (): void {
        Route::resource('blueprints', BlueprintController::class);
        Route::prefix('blueprints/{blueprint}/versions')->name('versions.')->group(function (): void {
            Route::get('/', [BlueprintVersionController::class, 'index'])->name('index');       // Version Manager (A04.3 §12)
            Route::get('{version}/tree', [BlueprintAuthoringController::class, 'tree'])->name('tree');
            Route::get('{version}/validate', [BlueprintAuthoringController::class, 'validateIntegrity'])->name('validate');
            Route::post('{version}/clone', [BlueprintVersionController::class, 'clone'])->middleware('can:' . P::BLUEPRINT_CLONE->value)->name('clone');
            Route::post('{version}/publish', [BlueprintVersionController::class, 'publish'])->middleware('can:' . P::BLUEPRINT_PUBLISH->value)->name('publish');
            Route::post('{version}/archive', [BlueprintVersionController::class, 'archive'])->middleware('can:' . P::BLUEPRINT_ARCHIVE->value)->name('archive');
            Route::get('compare', [BlueprintVersionController::class, 'compare'])->name('compare'); // ?from=1&to=2
        });
        // CRUD lồng nhau cho outcome/capability/workflow/phase/checklist/resource-link/ai-capability/analytic
        // theo đúng pattern RubricAuthoringController hiện có trong OcopRubric.
    });
```

### 2.11 Tích hợp với hệ thống hiện có & khuyến nghị đi kèm

- **Adapter tương thích ngược**: viết `BlueprintToVerticalDefinitionAdapter implements App\Foundation\VerticalDefinition` — bọc 1 `BlueprintVersion` để nó "giả dạng" một `VerticalDefinition` như code cũ đang mong đợi (`phases()`, `defaultChecklist()`, `verticalRoles()`, `sidebarGroups()` đọc từ `blueprint_phases`/`blueprint_checklists`/`blueprint_deployment_settings`). Nhờ đó `Modules/Deployment` (Phần 4) và các code đang gọi `VerticalRegistry::resolve()` **không cần sửa ngay** khi chuyển dần sang Blueprint mới.
- **Migration dữ liệu 1 lần**: script `MigrateVerticalTemplatesToBlueprintsCommand` (Artisan command) đọc toàn bộ `vertical_templates` có `organization_id IS NULL` (bản thư viện), tạo tương ứng `business_solutions` (nếu chưa có ở Phần 1) + `blueprints` + `blueprint_versions` (`version=1.0.0`, `status=published`) + copy `vertical_phases`→`blueprint_phases`, `vertical_checklist_items`→`blueprint_checklists`, `vertical_templates.sidebar_config`/`default_roles`→`blueprint_deployment_settings`.
- **Khuyến nghị bổ sung Role "Business Analyst"** vào `app/Enums/RoleEnum.php` nếu muốn tách bạch đúng ma trận quyền A04.2 §6 (hiện 8 role không có role này — có thể tạm dùng `Ops` hoặc `System_Admin` thay thế nếu không muốn thêm role mới, nhưng nên ghi chú rõ trong `config/permissions.php` để tránh nhầm lẫn quyền hạn).
- **Không sửa** `Sop`, `KcItem`, `AiCopilot` — chỉ tham chiếu qua `blueprint_resource_links`/`blueprint_ai_capabilities` như đã thiết kế ở 2.3.

---

## Phần 3. Module `OrganizationSolution`

### 3.1 Mục đích & phạm vi

Tách bạch **kích hoạt** (activation) và **cấu hình overlay** ra khỏi cơ chế hiện tại "Clone toàn bộ `VerticalTemplate` cho từng tổ chức rồi sửa trực tiếp". Thay thế bằng: 1 bản ghi kích hoạt (`organization_solutions`) + 5 bảng cấu hình override — **không bao giờ sửa Blueprint gốc** (đúng DP-04, BR-013).

### 3.2 Vị trí trong kiến trúc

```
organizations (đã có)
    └── organization_solutions (mới)
            ├── organization_solution_configs (mới)
            ├── organization_capability_configs (mới)
            ├── organization_workflow_configs (mới)
            ├── organization_checklist_configs (mới)
            ├── organization_role_mappings (mới)
            └── organization_ai_configs (mới)

business_solutions (Phần 1) ──┐
blueprint_versions (Phần 2)  ─┴──→ organization_solutions.business_solution_id / blueprint_version_id
```

### 3.3 Database schema

```php
Schema::create('organization_solutions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
    $table->foreignId('business_solution_id')->constrained('business_solutions')->restrictOnDelete();
    $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->restrictOnDelete(); // PIN cứng — bắt buộc published
    $table->string('name', 255);                 // tên riêng trong tổ chức, VD "AI Truy xuất HTX Tiên Dương"
    $table->unsignedBigInteger('owner_id');       // users.id
    $table->string('status', 20)->default('draft'); // OrganizationSolutionStatus (xem 3.5)
    $table->timestamp('activated_at')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->unique(['organization_id', 'business_solution_id']); // 1 tổ chức chỉ có 1 activation "chính" / 1 solution
                                                                  // (nếu cần nhiều instance, dùng project trong Phần 4, không nhân bản activation)
});

Schema::create('organization_solution_configs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_solution_id')->constrained('organization_solutions')->cascadeOnDelete();
    $table->string('config_key', 100);
    $table->json('config_value')->nullable();
    $table->text('description')->nullable();
    $table->timestamps();

    $table->unique(['organization_solution_id', 'config_key']);
});

Schema::create('organization_capability_configs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_solution_id')->constrained('organization_solutions')->cascadeOnDelete();
    $table->foreignId('blueprint_capability_id')->constrained('blueprint_capabilities')->cascadeOnDelete();
    $table->boolean('enabled')->default(true);
    $table->string('override_name', 255)->nullable();
    $table->json('override_config')->nullable();
    $table->timestamps();

    $table->unique(['organization_solution_id', 'blueprint_capability_id']);
});

Schema::create('organization_workflow_configs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_solution_id')->constrained('organization_solutions')->cascadeOnDelete();
    $table->foreignId('blueprint_workflow_id')->constrained('blueprint_workflows')->cascadeOnDelete();
    $table->boolean('enabled')->default(true);
    $table->unsignedBigInteger('default_owner_id')->nullable();
    $table->unsignedSmallInteger('sla_days')->nullable();
    $table->json('override_config')->nullable();
    $table->timestamps();

    $table->unique(['organization_solution_id', 'blueprint_workflow_id']);
});

Schema::create('organization_checklist_configs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_solution_id')->constrained('organization_solutions')->cascadeOnDelete();
    $table->foreignId('blueprint_checklist_id')->constrained('blueprint_checklists')->cascadeOnDelete();
    $table->boolean('enabled')->default(true);
    $table->unsignedBigInteger('default_assignee_id')->nullable();
    $table->unsignedBigInteger('default_reviewer_id')->nullable();
    $table->unsignedSmallInteger('due_days')->nullable();
    $table->json('override_config')->nullable();
    $table->timestamps();

    $table->unique(['organization_solution_id', 'blueprint_checklist_id']);
});

Schema::create('organization_role_mappings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_solution_id')->constrained('organization_solutions')->cascadeOnDelete();
    $table->string('blueprint_role_code', 100);   // "field_officer" | "supervisor" | "manager" (trừu tượng, từ blueprint_deployment_settings)
    $table->unsignedBigInteger('organization_role_id')->nullable(); // FK mềm → Spatie roles.id
    $table->unsignedBigInteger('user_id')->nullable();              // gán trực tiếp 1 user cụ thể nếu cần
    $table->string('mapping_type', 30)->default('role'); // 'role' | 'user'
    $table->json('metadata')->nullable();
    $table->timestamps();

    $table->unique(['organization_solution_id', 'blueprint_role_code']);
});

Schema::create('organization_ai_configs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_solution_id')->constrained('organization_solutions')->cascadeOnDelete();
    $table->string('ai_capability_code', 100);    // khớp blueprint_ai_capabilities.capability_code
    $table->boolean('enabled')->default(true);
    $table->unsignedBigInteger('ai_agent_id')->nullable();  // override agent khác agent mặc định của Blueprint
    $table->unsignedBigInteger('ai_prompt_id')->nullable();
    $table->string('provider', 50)->nullable();
    $table->decimal('cost_limit', 10, 2)->nullable();
    $table->json('config')->nullable();
    $table->timestamps();

    $table->unique(['organization_solution_id', 'ai_capability_code']);
});
```

### 3.4 Models (`Modules/OrganizationSolution/app/Models/`)

Tất cả **`extends TenantAwareModel`** (dữ liệu Configuration Data cấp tổ chức — theo đúng A08 §Chương 13 "Tenant Data"): `OrganizationSolution`, `OrganizationSolutionConfig`, `OrganizationCapabilityConfig`, `OrganizationWorkflowConfig`, `OrganizationChecklistConfig`, `OrganizationRoleMapping`, `OrganizationAiConfig`.

```php
// OrganizationSolution
public function businessSolution(): BelongsTo { return $this->belongsTo(BusinessSolution::class); }
public function blueprintVersion(): BelongsTo { return $this->belongsTo(BlueprintVersion::class); }
public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
public function capabilityConfigs(): HasMany { return $this->hasMany(OrganizationCapabilityConfig::class); }
public function workflowConfigs(): HasMany { return $this->hasMany(OrganizationWorkflowConfig::class); }
public function checklistConfigs(): HasMany { return $this->hasMany(OrganizationChecklistConfig::class); }
public function roleMappings(): HasMany { return $this->hasMany(OrganizationRoleMapping::class); }
public function aiConfigs(): HasMany { return $this->hasMany(OrganizationAiConfig::class); }
```

### 3.5 Enum trạng thái (A07 §15)

```php
enum OrganizationSolutionStatus: string
{
    case Draft = 'draft'; case Configuring = 'configuring'; case Ready = 'ready';
    case Deploying = 'deploying'; case Running = 'running';
    case Suspended = 'suspended'; case Archived = 'archived';
}
```

### 3.6 Actions (Feature `SolutionActivation`, wizard 8 bước theo A07 §4)

- `ActivateBusinessSolutionAction` — Bước 1+2: nhận `business_solution_id`, `blueprint_version_id` (phải `status=published`), tạo `organization_solutions` với `status=draft`.
- `ConfigureCapabilitiesAction` — Bước 3: upsert `organization_capability_configs` (bulk enable/disable).
- `ConfigureWorkflowsAction` — Bước 4.
- `ConfigureChecklistsAction` — Bước 4b (Checklist Configuration, A07 §8).
- `ConfigureResourcesAction` — Bước 5 (chỉ thay reference, VD `BM-01 → BM-01-HTX`, ghi vào `organization_solution_configs.config_key='resource_override'`).
- `ConfigureAiAction` — Bước 6.
- `ConfigureDashboardAction` — Bước 7 (`organization_solution_configs.config_key='dashboard'`).
- `MapRolesAction` — bổ sung riêng (A07 §12, chương "rất quan trọng") — upsert `organization_role_mappings`.
- `ConfigureNotificationsAction` — Bước tuỳ chọn (A07 §13).
- `MarkSolutionReadyAction` — Bước 8 (Review): chạy `ValidatePreDeployAction` (bảng điều kiện A07 §14) → nếu pass, `status: configuring → ready`.
- `SuspendOrganizationSolutionAction`, `ArchiveOrganizationSolutionAction`.

### 3.7 Validation trước Deploy (A07 §14) — bảng đối chiếu

| Điều kiện | Cách kiểm tra |
|---|---|
| Có Blueprint | `organization_solution->blueprint_version_id` not null + version `status=published` |
| Có Capability | ≥1 `organization_capability_configs.enabled=true` |
| Có Workflow | ≥1 `organization_workflow_configs.enabled=true` |
| Có Resource | Blueprint version có `resourceLinks` và không bị override thành rỗng |
| Role Mapping hoàn chỉnh | mọi `blueprint_deployment_settings` role trừu tượng đều có `organization_role_mappings` tương ứng |
| AI Config hợp lệ | mọi `organization_ai_configs.enabled=true` có `ai_agent_id`/`ai_prompt_id` hợp lệ (tồn tại trong `AiCopilot`) |
| Dashboard hợp lệ | `organization_solution_configs.config_key='dashboard'` tồn tại |

### 3.8 Permissions & Routes

```
SOLUTION_ACTIVATE, SOLUTION_CONFIGURE, SOLUTION_SUSPEND, SOLUTION_ARCHIVE
```

```php
Route::middleware(['web', 'auth', 'tenant', 'can:' . P::SOLUTION_ACTIVATE->value])
    ->prefix('dashboard/organization-solutions')
    ->name('organization_solutions.')
    ->group(function (): void {
        Route::get('/', [OrganizationSolutionController::class, 'index'])->name('index');
        Route::post('activate', [OrganizationSolutionController::class, 'activate'])->name('activate');
        Route::prefix('{organizationSolution}/wizard')->name('wizard.')->group(function (): void {
            Route::post('capabilities', [SolutionWizardController::class, 'capabilities'])->name('capabilities');
            Route::post('workflows', [SolutionWizardController::class, 'workflows'])->name('workflows');
            Route::post('checklists', [SolutionWizardController::class, 'checklists'])->name('checklists');
            Route::post('resources', [SolutionWizardController::class, 'resources'])->name('resources');
            Route::post('ai', [SolutionWizardController::class, 'ai'])->name('ai');
            Route::post('roles', [SolutionWizardController::class, 'roles'])->name('roles');
            Route::post('dashboard', [SolutionWizardController::class, 'dashboard'])->name('dashboard');
            Route::post('review', [SolutionWizardController::class, 'markReady'])->name('review');
        });
        Route::post('{organizationSolution}/suspend', [OrganizationSolutionController::class, 'suspend'])->name('suspend');
    });
```

### 3.9 Tích hợp với hệ thống hiện có

- `RoleScope` module **không đổi** — `organization_role_mappings.organization_role_id` trỏ tới role Spatie đã tồn tại; `RoleScope` (`user_role_scopes`) tiếp tục xử lý phạm vi role theo branch/department như hiện tại. Hai bảng bổ trợ, không chồng nhau (xem giải thích ở tài liệu so sánh, mục 3.3).
- **Migration dữ liệu**: `MigrateOrgVerticalTemplatesToOrganizationSolutionsCommand` đọc `vertical_templates` có `organization_id SET` (bản đã "activate" theo tổ chức hiện tại), tạo `organization_solutions` tương ứng trỏ về `blueprint_version_id` đã tạo ở Phần 2, và suy ra `organization_checklist_configs`/`organization_capability_configs` từ phần khác biệt (diff) giữa bản clone của tổ chức và bản thư viện gốc (nếu tổ chức đã xoá/thêm checklist thủ công trước đây → ghi nhận thành `enabled=false`/override tương ứng thay vì mất dữ liệu).

---

## Phần 4. Deployment Engine — mở rộng `Modules/Deployment` hiện có

### 4.1 Mục đích & phạm vi

**Không tạo module mới, không đổi tên `Modules/Deployment`.** Bổ sung tầng "ghi nhận hành động deploy" đứng **trước** luồng hiện tại (`CreateVerticalProjectAction`), theo đúng 6 bước xử lý của Deployment Engine (A05 §3.1) và điền khoảng trống nêu ở mục 1.4/3.4 của tài liệu so sánh.

### 4.2 Vị trí trong kiến trúc

```
organization_solutions (Phần 3, status=ready)
    │
    ▼
DeployOrganizationSolutionAction  (MỚI — 6 bước)
    │  1. Validate blueprint_version.status === Published
    │  2. Đọc organization_*_configs
    │  3. Sinh Runtime → gọi CreateVerticalProjectAction (ĐÃ CÓ, không sửa) để tạo Project
    │  4. Khởi tạo Dashboard  (đọc blueprint_analytics + organization config dashboard)
    │  5. Khởi tạo AI Context (đọc blueprint_ai_capabilities + organization_ai_configs)
    │  6. Ghi deployments + deployment_logs + deployment_snapshots, set organization_solutions.status = running
    ▼
Project (Modules/Project, ĐÃ CÓ)
    │
DeploymentTarget (Modules/Deployment, ĐÃ CÓ) — thêm cột deployment_id + blueprint_version_id
    ├── DeploymentChecklistItem (ĐÃ CÓ)
    ├── DeploymentIssue (ĐÃ CÓ)
    └── DeploymentProgressLog (ĐÃ CÓ)
```

### 4.3 Database schema bổ sung

```php
// database/migrations trong chính Modules/Deployment (module hiện có)
Schema::create('deployments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
    $table->foreignId('organization_solution_id')->constrained('organization_solutions')->restrictOnDelete();
    $table->foreignId('business_solution_id')->constrained('business_solutions')->restrictOnDelete();
    $table->foreignId('blueprint_id')->constrained('blueprints')->restrictOnDelete();
    $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->restrictOnDelete();
    $table->unsignedBigInteger('project_id')->nullable(); // FK mềm → Modules\Project\Models\Project (module khác)
    $table->unsignedBigInteger('deployed_by');
    $table->string('status', 20)->default('pending'); // pending|running|completed|failed|rolled_back
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
});

Schema::create('deployment_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('deployment_id')->constrained('deployments')->cascadeOnDelete();
    $table->string('step', 100);      // 'validate_blueprint' | 'read_config' | 'generate_runtime' | 'init_dashboard' | 'init_ai_context' | 'complete'
    $table->text('message')->nullable();
    $table->string('level', 20)->default('info'); // info|warning|error
    $table->json('payload')->nullable();
    $table->timestamp('created_at')->nullable();
});

Schema::create('deployment_snapshots', function (Blueprint $table) {
    $table->id();
    $table->foreignId('deployment_id')->constrained('deployments')->cascadeOnDelete();
    $table->string('snapshot_type', 50); // blueprint|organization_config|runtime_mapping|permission|ai_context
    $table->json('snapshot_data');
    $table->timestamp('created_at')->nullable();
});

// ALTER bảng đã có — pin Runtime vào đúng Blueprint Version đã deploy (RR-002 A05, BR-010 A04.3)
Schema::table('deployment_targets', function (Blueprint $table) {
    $table->foreignId('deployment_id')->nullable()->after('id')->constrained('deployments')->nullOnDelete();
    $table->foreignId('blueprint_version_id')->nullable()->after('deployment_id')->constrained('blueprint_versions')->nullOnDelete();
    // vertical_code (string, cột cũ) GIỮ NGUYÊN trong giai đoạn chuyển tiếp — không xoá ngay, xem 4.6
});
```

### 4.4 Model & Enum

`Modules/Deployment/app/Models/Deployment.php`, `DeploymentLog.php`, `DeploymentSnapshot.php` — `extends TenantAwareModel` (có `organization_id`).

```php
enum DeploymentStatus: string
{
    case Pending = 'pending'; case Running = 'running'; case Completed = 'completed';
    case Failed = 'failed'; case RolledBack = 'rolled_back';
}
```

### 4.5 Action chính — `DeployOrganizationSolutionAction`

```php
namespace Modules\Deployment\Features\DeploymentEngine\Actions;

class DeployOrganizationSolutionAction
{
    use AsAction;

    public function handle(OrganizationSolution $orgSolution): Deployment
    {
        return DB::transaction(function () use ($orgSolution) {
            $deployment = Deployment::create([
                'organization_id'            => $orgSolution->organization_id,
                'organization_solution_id'   => $orgSolution->id,
                'business_solution_id'       => $orgSolution->business_solution_id,
                'blueprint_id'               => $orgSolution->blueprintVersion->blueprint_id,
                'blueprint_version_id'       => $orgSolution->blueprint_version_id,
                'deployed_by'                => auth()->id(),
                'status'                     => DeploymentStatus::Pending->value,
                'started_at'                 => now(),
            ]);

            $this->log($deployment, 'validate_blueprint', fn () => $this->validateBlueprint($orgSolution));
            $this->log($deployment, 'read_config', fn () => $config = $this->readOrganizationConfig($orgSolution));
            $this->log($deployment, 'generate_runtime', fn () => $project = app(CreateVerticalProjectAction::class)->handle(...));
            $this->log($deployment, 'init_dashboard', fn () => $this->initDashboard($orgSolution, $project));
            $this->log($deployment, 'init_ai_context', fn () => $this->initAiContext($orgSolution, $project));

            DeploymentSnapshot::create(['deployment_id' => $deployment->id, 'snapshot_type' => 'blueprint', 'snapshot_data' => /* toàn bộ tree Blueprint tại thời điểm này */]);
            DeploymentSnapshot::create(['deployment_id' => $deployment->id, 'snapshot_type' => 'organization_config', 'snapshot_data' => /* toàn bộ config org tại thời điểm này */]);

            $deployment->update(['status' => DeploymentStatus::Completed->value, 'project_id' => $project->id, 'completed_at' => now()]);
            $orgSolution->update(['status' => OrganizationSolutionStatus::Running->value]);

            return $deployment;
        });
    }

    private function log(Deployment $deployment, string $step, \Closure $fn): mixed
    {
        try {
            $result = $fn();
            DeploymentLog::create(['deployment_id' => $deployment->id, 'step' => $step, 'level' => 'info', 'message' => "{$step} OK"]);
            return $result;
        } catch (\Throwable $e) {
            DeploymentLog::create(['deployment_id' => $deployment->id, 'step' => $step, 'level' => 'error', 'message' => $e->getMessage()]);
            $deployment->update(['status' => DeploymentStatus::Failed->value]);
            throw $e;
        }
    }
}
```

**Nguyên tắc bắt buộc**: Action này **gọi lại** `CreateVerticalProjectAction` đã có sẵn (không viết lại logic tạo Project/DeploymentTarget) — chỉ bọc thêm validate/log/snapshot ở ngoài. `DeploymentTarget` sau khi tạo phải được `update(['deployment_id' => ..., 'blueprint_version_id' => ...])` ngay sau bước `generate_runtime`.

### 4.6 Chiến lược tương thích ngược (không phá vỡ luồng cũ)

- Cột `vertical_code` trên `DeploymentTarget` **giữ nguyên** trong giai đoạn chuyển tiếp — dùng song song với `blueprint_version_id` mới. `VerticalRegistry::resolveForOrganization()` (code cũ) tiếp tục hoạt động không đổi.
- Route/Controller hiện có (`DeploymentProjectController`, `DeploymentTargetController`...) **không cần sửa ngay** — chỉ có luồng "tạo mới" (activate solution qua wizard ở Phần 3 → deploy qua Action mới ở đây) mới đi qua `DeployOrganizationSolutionAction`. Các Deployment Target đã tồn tại trước đó tiếp tục vận hành với `deployment_id = NULL` (được coi là "legacy, chưa migrate").
- Command `BackfillDeploymentRecordsCommand` (tuỳ chọn, chạy sau) tạo `deployments`/`deployment_snapshots` hồi tố cho các `DeploymentTarget` cũ đang chạy, để dần đưa 100% Runtime về có `deployment_id` hợp lệ.

### 4.7 Permissions & Routes

```
DEPLOYMENT_RUN, DEPLOYMENT_VIEW_LOGS
```

```php
Route::middleware(['web', 'auth', 'tenant', 'can:' . P::DEPLOYMENT_RUN->value])
    ->prefix('dashboard/deployments')
    ->name('deployments.')
    ->group(function (): void {
        Route::post('organization-solutions/{organizationSolution}/deploy', [DeploymentEngineController::class, 'deploy'])->name('deploy');
        Route::get('{deployment}/logs', [DeploymentEngineController::class, 'logs'])->name('logs');
        Route::get('{deployment}/snapshots', [DeploymentEngineController::class, 'snapshots'])->name('snapshots');
    });
```

---

## Phần 5. Module `SolutionCatalog`

### 5.1 Mục đích & phạm vi

UI danh mục Business Solution cho System Admin/Organization Admin duyệt và kích hoạt — **module UI mỏng**, không có bảng dữ liệu riêng (đọc hoàn toàn từ `business_solutions`/`business_solution_versions` của Phần 1). Tên module **cố ý khác** `Marketplace` hiện có (đang là marketplace tuyển dụng — xem mục 1.7 tài liệu so sánh) để tránh nhầm domain.

### 5.2 Vị trí trong kiến trúc

```
business_solutions (Phần 1, đọc-only từ góc nhìn module này)
        │
        ▼
SolutionCatalog (UI: danh sách + chi tiết + nút "Kích hoạt")
        │  nút Kích hoạt gọi thẳng
        ▼
OrganizationSolution::ActivateBusinessSolutionAction (Phần 3)
```

### 5.3 Cấu trúc thư mục (không có `Models/`, không có `database/migrations` — module thuần UI/Query)

```
Modules/SolutionCatalog/
├── app/
│   ├── Http/Controllers/
│   │   ├── SolutionCatalogController.php      # index (danh sách), show (chi tiết Solution)
│   │   └── SolutionActivationController.php   # nút "Kích hoạt" → redirect sang wizard OrganizationSolution
│   ├── Queries/
│   │   ├── ListPublishedSolutionsQuery.php     # filter: vertical, category, tag, search — chỉ business_solutions.status=published
│   │   └── ListPublishedSolutionsHandler.php
│   └── Providers/
├── resources/views/
│   ├── index.blade.php     # dạng "card" theo từng Business Solution — tham khảo layout Marketplace/listings/index.blade.php hiện có
│   └── show.blade.php      # chi tiết: outcomes, capabilities, AI capabilities (đọc từ blueprint_version hiện hành), nút Kích hoạt
└── routes/web.php
```

### 5.4 Queries

```php
class ListPublishedSolutionsQuery implements QueryInterface
{
    public function __construct(
        public readonly ?int $verticalId = null,
        public readonly ?string $categorySlug = null,
        public readonly ?string $search = null,
    ) {}
}

class ListPublishedSolutionsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        return BusinessSolution::query()
            ->where('status', BusinessSolutionStatus::Published->value)
            ->whereIn('visibility', [BusinessSolutionVisibility::Public->value, BusinessSolutionVisibility::Marketplace->value])
            ->with(['vertical', 'tags', 'blueprints' => fn ($q) => $q->where('status', 'published')])
            ->when($query->verticalId, fn ($q) => $q->where('vertical_id', $query->verticalId))
            ->when($query->search, fn ($q) => $q->where('name', 'like', "%{$query->search}%"))
            ->get();
    }
}
```

### 5.5 Nội dung hiển thị mỗi Solution (đúng A02 §10.1 — "Marketplace không nên bán workflow, nên hiển thị Business Solution")

Mỗi thẻ/trang chi tiết hiển thị: Tên giải pháp, Vertical, Đối tượng phù hợp (`target_customers`), Business Outcomes (đọc `blueprint_version->outcomes`), AI Capabilities (đọc `blueprint_version->aiCapabilities`), Version hiện hành, Author/Owner, nút **"Kích hoạt"** (chỉ hiện nếu tổ chức hiện tại **chưa có** `organization_solutions` cho solution này — theo unique constraint ở 3.3).

**Không làm ở giai đoạn này** (đúng phạm vi loại trừ A01 §12): demo/trial, rating/review, license, pricing — giữ đúng MVP "Solution Catalog cơ bản".

### 5.6 Permissions & Routes

Tái sử dụng `P::SOLUTION_CATALOG_VIEW` (đã khai báo ở Phần 1) cho xem danh mục; nút Kích hoạt yêu cầu `P::SOLUTION_ACTIVATE` (Phần 3).

```php
Route::middleware(['web', 'auth', 'tenant', 'can:' . P::SOLUTION_CATALOG_VIEW->value])
    ->prefix('dashboard/solution-catalog')
    ->name('solution_catalog.')
    ->group(function (): void {
        Route::get('/', [SolutionCatalogController::class, 'index'])->name('index');
        Route::get('{businessSolution:slug}', [SolutionCatalogController::class, 'show'])->name('show');
        Route::post('{businessSolution:slug}/activate', [SolutionActivationController::class, 'activate'])
            ->middleware('can:' . P::SOLUTION_ACTIVATE->value)
            ->name('activate');
    });
```

### 5.7 Tích hợp với sidebar (`config/permissions.php`)

Thêm mục sidebar mới `solution_catalog` vào cấu hình hiển thị theo role (file `config/permissions.php` hiện đang map permission → sidebar module theo comment đầu file) — hiển thị cho các role có `SOLUTION_CATALOG_VIEW` (khuyến nghị: tất cả 8 role, vì đây chỉ là danh mục xem/kích hoạt, không phải trang quản trị nhạy cảm).

---

## Phần 6. Tổng hợp thứ tự triển khai kỹ thuật (chi tiết hoá Phần 4 của tài liệu so sánh)

| Bước | Module | Việc chính | Điều kiện tiên quyết |
|---|---|---|---|
| 1 | `BusinessSolution` | 5 migration + Model + Action/Query CRUD + seed 3 solution bespoke | Không có |
| 2 | `BusinessBlueprint` | 11 migration + Model + Enum + Actions (CRUD từng phần + Clone/Publish/Compare) + Adapter tương thích `VerticalDefinition` + Command migrate dữ liệu từ `vertical_templates` (thư viện) | Bước 1 xong (cần `business_solution_id`) |
| 3 | `OrganizationSolution` | 7 migration + Model + Enum + Actions wizard 8 bước + Command migrate dữ liệu từ `vertical_templates` (theo tổ chức) | Bước 1, 2 xong |
| 4 | Deployment Engine | 3 migration mới + 2 cột ALTER trên `deployment_targets` + Action `DeployOrganizationSolutionAction` (bọc quanh Action cũ, không sửa) | Bước 1, 2, 3 xong |
| 5 | `SolutionCatalog` | Module UI thuần, không migration | Bước 1 xong (chỉ cần đọc `business_solutions`); nên làm sau Bước 3 để nút "Kích hoạt" có chỗ để trỏ tới |

**Nguyên tắc xuyên suốt khi code**: mỗi bước đều phải **chạy song song, không tắt** `Modules/Deployment`/`Assessment`/`OcopRubric` hiện có — chỉ khi lớp mới (Bước 1–4) đã chứng minh vận hành đúng cho ít nhất 1 Organization Solution thật (khuyến nghị: pilot lại chính "AI Truy xuất nguồn gốc" vì đã có dữ liệu/luồng nghiệp vụ rõ nhất), mới cân nhắc lộ trình ngừng dùng cơ chế `VerticalTemplate` Clone cũ.
