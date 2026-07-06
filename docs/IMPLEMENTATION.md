# Platform Implementation Blueprint

> **Phiên bản:** v1.0 — 2026-06-16
> **Mục đích:** Single source of truth để implement — thay thế PLATFORM_DESIGN.md + TXNG_IMPL_SPEC.md
> **Nguyên tắc:** Mỗi vertical là một bộ config trên top của Platform Core, không phải một hệ thống riêng

---

## Mục lục

- [Part A — Deployment Engine (Core, viết một lần)](#part-a--deployment-engine)
  - A.1 Ba nguyên tắc · A.2 VerticalDefinition interface · A.3 VerticalRegistry · A.4 Middleware
  - A.5 DB: organization_verticals · A.6 DB: deployment_targets · A.7 Checklist/Issues/Progress
  - A.8 ExportAdapterInterface · A.9 Platform Core reuse map · A.10 Organization creation flow
- [Part B — Traceability Vertical (first implementation)](#part-b--txng-vertical)
  - B.1 Traceability Template Seeder · B.2 Roles & permissions · B.3 Phase state machine
  - B.4 Readiness Assessment · B.5 Physical staging tables · B.6 AI Agents · B.7 Academy
  - B.8 Export adapter · B.9 Notifications · B.10 Vertical Settings
  - **B.11 UI Screen Specs** · **B.12 Report Formats**
- [Part C — Build Order (sprint by sprint)](#part-c--build-order)
- [Appendix — Template thêm vertical mới](#appendix--new-vertical-template)

---

## Tổng quan tư duy thiết kế

`chitiettxng.docx` không mô tả hệ thống TXNG — nó mô tả **4 năng lực phổ quát** mà bất kỳ tổ chức nào làm "deployment/enablement" đều cần:

```
Năng lực 1 — Đánh giá sẵn sàng   → Survey Engine + Assessment Engine  (Core: đã có)
Năng lực 2 — Quản lý triển khai   → Deployment Engine                  (Core: cần build)
Năng lực 3 — Vận hành dữ liệu AI  → AiCopilot                          (Core: đã có)
Năng lực 4 — Đào tạo + chứng nhận → Sandbox + Certification             (Core: đã có)
```

**TXNG** = một bộ config áp lên 4 năng lực này + **Survey-based data collection** (thu thập dữ liệu thực địa qua form) + CheckVn export adapter đọc từ survey answers + organizations.

**V2 Consulting** = bộ config khác của 4 năng lực đó, không cần thu thập dữ liệu, export ra SOW PDF.

> **Nguyên tắc từ Sprint 4 trở đi:**
> - Thông tin hồ sơ tổ chức → lưu trong **`organizations`** (module Organization đã có)
> - Thu thập dữ liệu thực địa → **Survey module** (tạo survey template, Surveyor điền form ngoài thực địa)
> - Export CheckVN → đọc từ `organizations` + `survey_answers` → sinh file Excel
> - **KHÔNG** tạo bảng `production_*` riêng — tránh xây dựng lại tính năng của hệ thống đối tác

```
THUCHOCVN Platform
├── Platform Core (Survey, Assessment, AiCopilot, Sandbox, Cert, DeploymentEngine)
│     └── Dùng chung cho tất cả vertical
└── Vertical Layer (opt-in per org)
      ├── Template "traceability": target='Tổ chức', phases=8, export=CheckVn adapter   ← seeded vào DB
      ├── Template "consulting":   target='Doanh nghiệp', phases=5, export=PDF adapter  ← seeded vào DB
      ├── Template "manufacturing":target='Nhà máy', phases=6, export=ISO adapter       ← seeded vào DB
      └── Bất kỳ template nào:    Platform admin tạo qua UI → seed row vào vertical_templates
      — Không có PHP class per vertical. DatabaseVertical đọc từ DB.
```

---

## Part A — Deployment Engine

### A.1 Ba nguyên tắc

1. **Deployment Engine là Platform Core** — không thuộc bất kỳ vertical nào. Bất kỳ org nào quản lý việc triển khai cho bên thứ 3 đều dùng được.
2. **Labels đến từ vertical config** — route path dùng generic (`/targets`), label hiển thị (HTX / Client / Trường) đến từ `VerticalDefinition`.
3. **Export là adapter pattern** — mỗi vertical implement `ExportAdapterInterface` riêng. Core không biết gì về CheckVn hay ISO format.

---

### A.2 VerticalDefinition Interface

```php
// app/Foundation/VerticalDefinition.php
interface VerticalDefinition
{
    /** Mã định danh duy nhất — dùng trong route prefix và DB */
    public function code(): string;

    /** Tên hiển thị đầy đủ */
    public function label(): string;

    /** Label cho entity "deployment target" (HTX / Doanh nghiệp / Trường / Nhà máy) */
    public function targetLabel(): string;

    /**
     * Organization category dùng khi tự động tạo Organization record cho target mới.
     * Lưu vào organizations.industry hoặc một tag tương đương.
     * VD: 'cooperative' (HTX), 'company' (Consulting), 'school' (Education), 'factory' (Manufacturing)
     */
    public function targetOrgCategory(): string;

    /**
     * DEFAULT labels cho physical asset hierarchy — null nếu vertical không dùng physical assets.
     * ⚠️  KHÔNG đọc trực tiếp ở runtime — luôn dùng VerticalConfigService::hierarchyLabels($orgId, ...).
     *     Mỗi org có thể override qua Settings UI → tab "Danh mục phân cấp" (config_group='hierarchy').
     *
     * Ví dụ: cùng vertical TXNG nhưng labels khác nhau per-org:
     *   HTX trồng chè:   area='Khu',          lot='Lô',    item='Cây'      (mặc định)
     *   HTX chăn nuôi:   area='Chuồng',       lot='Ô',     item='Con'      (override)
     *   HTX nuôi cá:     area='Ao',           lot='Ô',     item='Con cá'   (override)
     *   HTX trồng lúa:   area='Cánh đồng',    lot='Thửa',  item='Cây lúa' (override)
     */
    public function defaultSiteLabel(): ?string;      // 'Vùng sản xuất' / 'Nhà máy' / 'Trang trại'
    public function areaLabel(): ?string;              // 'Khu' / 'Xưởng' / 'Ao'           → DEFAULT
    public function lotLabel(): ?string;               // 'Lô' / 'Dây chuyền' / 'Ô'        → DEFAULT
    public function itemLabel(): ?string;              // 'Cây' / 'Máy' / 'Con'             → DEFAULT
    public function defaultItemCodePrefix(): ?string;  // 'C' (Cây) / 'M' (Máy) / 'A' (Animal) — tiền tố auto-code

    /** Danh sách phases theo thứ tự — tên bất kỳ, platform không fix cứng */
    public function phases(): array;        // ['surveying','collecting','standardizing',...]

    /**
     * Checklist items mặc định cho từng phase.
     * Key = slug bất biến, label đến từ lang file vertical.
     * Format: ['phase_name' => [['key' => 'slug', 'label' => 'Mô tả', 'required' => true], ...]]
     */
    public function defaultChecklist(): array;

    /** Slug survey template readiness — null nếu không cần */
    public function readinessTemplateSlag(): ?string;

    /** Class name của ExportAdapter — null nếu vertical không export ra file */
    public function exportAdapter(): ?string;

    /**
     * DEFAULT activity types — dùng để SEED vào vertical_config_items khi org kích hoạt vertical.
     * KHÔNG đọc trực tiếp ở runtime — dùng VerticalConfigService::activityTypes($orgId, $verticalCode).
     * null nếu vertical không cần activity_type (ví dụ: consulting).
     *
     * TXNG:           watering, fertilizing, spraying, harvesting...
     * Manufacturing:  maintenance, inspection, production, calibration...
     * Education:      class_visit, survey, equipment_check...
     */
    public function defaultActivityTypes(): ?array;

    /**
     * DEFAULT document/compliance types — dùng để SEED vào vertical_config_items khi org kích hoạt vertical.
     * KHÔNG đọc trực tiếp ở runtime — dùng VerticalConfigService::legalDocTypes($orgId, $verticalCode).
     * null nếu vertical không dùng legal docs.
     *
     * TXNG:           business_registration, ocop_cert, food_safety_cert, vietgap_cert...
     * Manufacturing:  iso_cert, haccp_cert, fire_safety, environmental_cert...
     * Consulting:     contract, nda, sow, completion_cert...
     */
    public function defaultLegalDocTypes(): ?array;

    /** Danh sách role codes đặc thù của vertical */
    public function verticalRoles(): array;

    /** Cấu trúc sidebar — group => [menu items] */
    public function sidebarGroups(): array;
}
```

---

### A.3 VerticalRegistry

```php
// app/Foundation/VerticalRegistry.php
class VerticalRegistry
{
    /**
     * Verticals are DB-driven — không đăng ký PHP class, không hardcode.
     * Thêm vertical mới = seed row vào vertical_templates, không deploy code.
     */
    public static function resolve(string $code): ?VerticalDefinition
    {
        $template = Cache::remember(
            "vertical_template:{$code}",
            now()->addHour(),
            fn() => VerticalTemplate::where('code', $code)->where('is_active', true)->first()
        );
        return $template ? new DatabaseVertical($template) : null;
    }

    public static function all(): array
    {
        $templates = Cache::remember(
            'vertical_templates_all',
            now()->addHour(),
            fn() => VerticalTemplate::where('is_active', true)->get()
        );
        return $templates->mapWithKeys(fn($t) => [$t->code => new DatabaseVertical($t)])->all();
    }

    public static function exists(string $code): bool
    {
        return static::resolve($code) !== null;
    }

    /** Gọi khi admin tạo/sửa/xóa template — bust cache */
    public static function clearCache(?string $code = null): void
    {
        if ($code) Cache::forget("vertical_template:{$code}");
        Cache::forget('vertical_templates_all');
    }
}
```

```
// VerticalServiceProvider không còn cần thiết để đăng ký PHP class.
// Xóa VerticalServiceProvider hoặc giữ lại để đăng ký gates/policies nếu cần.
```

---

### A.3.1 Table: `vertical_templates`

> **Core idea:** Vertical không còn là PHP class — là data. Thêm vertical mới = seed row, không deploy code.
> Platform admin quản lý qua UI tại `/admin/vertical-templates`.

```sql
CREATE TABLE vertical_templates (
    id                       BIGINT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
    code                     VARCHAR(50)      NOT NULL UNIQUE, -- 'traceability' | 'manufacturing' | 'consulting'
    label                    VARCHAR(100)     NOT NULL,         -- 'Triển khai Truy xuất nguồn gốc'
    target_label             VARCHAR(50)      NOT NULL DEFAULT 'Tổ chức', -- label cho deployment target
    target_org_category      VARCHAR(30)      NOT NULL DEFAULT 'organization', -- ghi vào organizations.industry
    has_physical_assets      TINYINT(1)       NOT NULL DEFAULT 0, -- reserved: false cho Survey-based collection; không dùng để tạo production_* tables
    data_collection_template_slug VARCHAR(100) NULL,              -- slug survey template thu thập dữ liệu thực địa (ví dụ: 'data_collection_v1')
    export_adapter           VARCHAR(200)     NULL,   -- FQCN của ExportAdapterInterface, null nếu không cần
    readiness_template_slug  VARCHAR(100)     NULL,   -- slug survey template readiness
    phases                   JSON             NOT NULL, -- ['draft','surveying','collecting',...]
    default_checklist        JSON             NOT NULL, -- {phase: [{key, label, required}]}
    default_activity_types   JSON             NULL,   -- {code: label} — seed vào vertical_config_items
    default_legal_doc_types  JSON             NULL,   -- {code: label}
    default_hierarchy        JSON             NOT NULL, -- {site,area,lot,item,item_plural,item_prefix}
    default_roles            JSON             NOT NULL, -- ['pm','surveyor','data_ops','data_entry','trainer']
    sidebar_config           JSON             NOT NULL, -- {group: [{label,route}]}
    is_active                TINYINT(1)       NOT NULL DEFAULT 1,
    created_at               TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at               TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX (is_active)
);
```

---

### A.3.2 `DatabaseVertical` — Implementation duy nhất của VerticalDefinition

```php
// app/Foundation/Vertical/DatabaseVertical.php
class DatabaseVertical implements VerticalDefinition
{
    public function __construct(private readonly VerticalTemplate $template) {}

    public function code(): string              { return $this->template->code; }
    public function label(): string             { return $this->template->label; }
    public function targetLabel(): string       { return $this->template->target_label; }
    public function targetOrgCategory(): string { return $this->template->target_org_category; }
    public function defaultSiteLabel(): ?string { return $this->template->default_hierarchy['site'] ?? null; }
    public function areaLabel(): ?string        { return $this->template->has_physical_assets ? ($this->template->default_hierarchy['area'] ?? null) : null; }
    public function lotLabel(): ?string         { return $this->template->has_physical_assets ? ($this->template->default_hierarchy['lot'] ?? null) : null; }
    public function itemLabel(): ?string        { return $this->template->has_physical_assets ? ($this->template->default_hierarchy['item'] ?? null) : null; }
    public function defaultItemCodePrefix(): ?string { return $this->template->default_hierarchy['item_prefix'] ?? null; }
    public function phases(): array             { return $this->template->phases; }
    public function defaultChecklist(): array   { return $this->template->default_checklist; }
    public function readinessTemplateSlag(): ?string { return $this->template->readiness_template_slug; }
    public function exportAdapter(): ?string    { return $this->template->export_adapter; }
    public function defaultActivityTypes(): ?array  { return $this->template->default_activity_types; }
    public function defaultLegalDocTypes(): ?array  { return $this->template->default_legal_doc_types; }
    public function verticalRoles(): array      { return $this->template->default_roles; }
    public function sidebarGroups(): array      { return $this->template->sidebar_config; }

    /** Raw template — dùng khi cần edit qua admin UI */
    public function template(): VerticalTemplate { return $this->template; }
}
```

**Model `VerticalTemplate`:**
```php
// app/Foundation/Vertical/VerticalTemplate.php  (KHÔNG phải TenantAwareModel — đây là platform-level)
class VerticalTemplate extends Model
{
    protected $fillable = ['code','label','target_label','target_org_category',
        'has_physical_assets','export_adapter','readiness_template_slug',
        'phases','default_checklist','default_activity_types','default_legal_doc_types',
        'default_hierarchy','default_roles','sidebar_config','is_active'];

    protected $casts = [
        'phases'                  => 'array',
        'default_checklist'       => 'array',
        'default_activity_types'  => 'array',
        'default_legal_doc_types' => 'array',
        'default_hierarchy'       => 'array',
        'default_roles'           => 'array',
        'sidebar_config'          => 'array',
        'has_physical_assets'     => 'boolean',
        'is_active'               => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn($t)   => VerticalRegistry::clearCache($t->code));
        static::deleted(fn($t) => VerticalRegistry::clearCache($t->code));
    }
}
```

---

### A.4 RequireVertical Middleware

```php
// app/Http/Middleware/RequireVertical.php
public function handle(Request $request, Closure $next, string $code): Response
{
    $vertical = VerticalRegistry::resolve($code);
    if (! $vertical) abort(404);

    $org = TenantContext::organization();
    $active = $org->verticals()->where('vertical_code', $code)->where('status', 'active')->exists();
    if (! $active) abort(403, "Vertical '{$code}' chưa được kích hoạt cho tổ chức này.");

    // Đưa vertical vào request để controllers dùng
    $request->merge(['_vertical' => $vertical]);

    return $next($request);
}

// Đăng ký alias trong bootstrap/app.php:
// ->withMiddleware(fn($m) => $m->alias(['vertical' => RequireVertical::class]))
```

---

### A.5 Database: Vertical Management

```sql
-- Org nào đã bật vertical nào
CREATE TABLE organization_verticals (
    id               BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    organization_id  BIGINT UNSIGNED NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    vertical_code    VARCHAR(30)     NOT NULL,          -- 'txng' | 'consulting' | 'manufacturing'
    status           VARCHAR(20)     NOT NULL DEFAULT 'active', -- active | suspended
    config           TEXT            NULL,              -- metadata JSON (không phải business data)
    activated_at     TIMESTAMP       NOT NULL,
    activated_by     BIGINT UNSIGNED NULL REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE (organization_id, vertical_code)
);
```

---

### A.5.1 Org-Level Enum Config — `vertical_config_items`

> **Vấn đề:** `defaultActivityTypes()` trong class PHP là CATALOG mặc định cho toàn vertical.
> Nhưng mỗi tổ chức có nghiệp vụ khác nhau:
> - HTX Trà hoa vàng: Tưới nước, Bón phân, Tỉa cành, Thu hoạch chè
> - HTX Gà Tiên Yên: Cho ăn, Tiêm phòng, Kiểm tra sức khỏe, Thu hoạch gia cầm
> - Nhà máy ABC: Bảo trì máy, Hiệu chuẩn, Kiểm tra chất lượng
>
> → Cùng vertical TXNG, mỗi org define danh sách riêng. Lưu vào bảng DB, không JSON.

```sql
-- Cấu hình enum per-org per-vertical: activity_type, doc_type, item_type, v.v.
-- Seed từ defaultActivityTypes() / defaultLegalDocTypes() khi org kích hoạt vertical.
-- Org admin có thể thêm/sửa/xóa sau đó qua Settings UI.
CREATE TABLE vertical_config_items (
    id              BIGINT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
    organization_id BIGINT UNSIGNED  NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    vertical_code   VARCHAR(30)      NOT NULL,  -- 'txng' | 'manufacturing' | 'consulting'
    config_group    VARCHAR(50)      NOT NULL,  -- 'activity_type' | 'doc_type' | 'item_type'
    code            VARCHAR(50)      NOT NULL,  -- slug lưu vào DB: 'watering', 'ocop_cert'
    label           VARCHAR(255)     NOT NULL,  -- label hiển thị: 'Tưới nước', 'Chứng nhận OCOP'
    is_required     TINYINT(1)       NOT NULL DEFAULT 0,  -- bắt buộc phải có không
    is_active       TINYINT(1)       NOT NULL DEFAULT 1,  -- có dùng không (soft-disable)
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    UNIQUE (organization_id, vertical_code, config_group, code),
    INDEX  (organization_id, vertical_code, config_group, is_active)
);
```

**Service layer — runtime resolver:**

```php
// app/Foundation/Vertical/VerticalConfigService.php
class VerticalConfigService
{
    /**
     * Lấy danh sách activity types của org — từ DB, fallback về vertical defaults.
     * Đây là method duy nhất cần gọi ở form/blade/controller.
     */
    public static function activityTypes(int $orgId, string $verticalCode, VerticalDefinition $vertical): array
    {
        $rows = VerticalConfigItem::where('organization_id', $orgId)
            ->where('vertical_code', $verticalCode)
            ->where('config_group', 'activity_type')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('label', 'code')
            ->toArray();

        return $rows ?: ($vertical->defaultActivityTypes() ?? []);
    }

    public static function legalDocTypes(int $orgId, string $verticalCode, VerticalDefinition $vertical): array
    {
        $rows = VerticalConfigItem::where('organization_id', $orgId)
            ->where('vertical_code', $verticalCode)
            ->where('config_group', 'doc_type')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('label', 'code')
            ->toArray();

        return $rows ?: ($vertical->defaultLegalDocTypes() ?? []);
    }

    /** Dùng khi cần list + metadata (is_required, sort_order) — ví dụ trang Settings */
    public static function configItems(int $orgId, string $verticalCode, string $configGroup): Collection
    {
        return VerticalConfigItem::where('organization_id', $orgId)
            ->where('vertical_code', $verticalCode)
            ->where('config_group', $configGroup)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Xem A.5.2 để biết đầy đủ — đặt ở đây để dễ tham chiếu chéo.
     * Trả về ['site', 'area', 'lot', 'item', 'item_plural', 'item_prefix'].
     * LUÔN dùng method này thay vì gọi $vertical->areaLabel() trực tiếp.
     */
    public static function hierarchyLabels(int $orgId, string $verticalCode, VerticalDefinition $vertical): array
    {
        // → Xem implementation đầy đủ tại A.5.2
        $db = VerticalConfigItem::where('organization_id', $orgId)
            ->where('vertical_code', $verticalCode)
            ->where('config_group', 'hierarchy')
            ->where('is_active', true)
            ->pluck('label', 'code')
            ->toArray();

        $itemDefault = $vertical->itemLabel() ?? 'Đơn vị';

        return [
            'site'        => $db['site_label']       ?? $vertical->defaultSiteLabel()      ?? 'Vùng sản xuất',
            'area'        => $db['area_label']        ?? $vertical->areaLabel()             ?? 'Khu',
            'lot'         => $db['lot_label']         ?? $vertical->lotLabel()              ?? 'Lô',
            'item'        => $db['item_label']        ?? $itemDefault,
            'item_plural' => $db['item_label_plural'] ?? $itemDefault,
            'item_prefix' => $db['item_code_prefix']  ?? $vertical->defaultItemCodePrefix() ?? 'I',
        ];
    }
}
```

**Seed khi org kích hoạt vertical:**

```php
// app/Foundation/Vertical/ActivateVerticalAction.php
class ActivateVerticalAction
{
    public function execute(int $orgId, VerticalDefinition $vertical): void
    {
        // 1. Đánh dấu vertical active
        OrganizationVertical::firstOrCreate(
            ['organization_id' => $orgId, 'vertical_code' => $vertical->code()],
            ['status' => 'active', 'activated_at' => now(), 'activated_by' => auth()->id()]
        );

        // 2. Seed activity_type defaults (chỉ seed nếu chưa có — idempotent)
        foreach ($vertical->defaultActivityTypes() ?? [] as $code => $label) {
            VerticalConfigItem::firstOrCreate(
                ['organization_id' => $orgId, 'vertical_code' => $vertical->code(),
                 'config_group' => 'activity_type', 'code' => $code],
                ['label' => $label, 'is_active' => true, 'sort_order' => 0]
            );
        }

        // 3. Seed doc_type defaults
        foreach ($vertical->defaultLegalDocTypes() ?? [] as $code => $label) {
            VerticalConfigItem::firstOrCreate(
                ['organization_id' => $orgId, 'vertical_code' => $vertical->code(),
                 'config_group' => 'doc_type', 'code' => $code],
                ['label' => $label, 'is_active' => true, 'sort_order' => 0]
            );
        }

        // 4. Seed hierarchy labels (tên danh mục phân cấp — per-org override)
        // Xem A.5.2 để biết danh sách codes và logic fallback.
        $itemDefault = $vertical->itemLabel() ?? 'Đơn vị';
        $hierarchyDefaults = [
            'site_label'        => $vertical->defaultSiteLabel()      ?? 'Vùng sản xuất',
            'area_label'        => $vertical->areaLabel()             ?? 'Khu',
            'lot_label'         => $vertical->lotLabel()              ?? 'Lô',
            'item_label'        => $itemDefault,
            'item_label_plural' => $itemDefault,
            'item_code_prefix'  => $vertical->defaultItemCodePrefix() ?? 'I',
        ];
        foreach ($hierarchyDefaults as $code => $label) {
            VerticalConfigItem::firstOrCreate(
                ['organization_id' => $orgId, 'vertical_code' => $vertical->code(),
                 'config_group' => 'hierarchy', 'code' => $code],
                ['label' => $label, 'is_active' => true, 'sort_order' => 0]
            );
        }
    }
}
```

**Cách dùng trong controller/blade:**

```php
// Controller truyền vào view:
$activityTypes = VerticalConfigService::activityTypes(
    TenantContext::organizationId(),
    $vertical->code(),
    $vertical
);

// Blade:
<select name="activity_type">
    @foreach($activityTypes as $code => $label)
        <option value="{{ $code }}">{{ $label }}</option>
    @endforeach
</select>
```

**Settings UI — `/txng/settings` tab "Loại hoạt động":**

```
┌─────────────────────────────────────────────────────────────┐
│ Cài đặt Vertical TXNG                                        │
├──────────┬──────────────────┬─────────────┬─────────────────┤
│ Tổng quan│ Loại hoạt động   │ Loại tài liệu│ Vai trò        │
└──────────┴──────────────────┴─────────────┴─────────────────┘

  Loại hoạt động canh tác / sản xuất
  Áp dụng cho nhật ký hoạt động của vùng sản xuất.

  ┌──────────────────────────────────┬──────────┬───────┐
  │ Tên loại                         │ Mã slug  │       │
  ├──────────────────────────────────┼──────────┼───────┤
  │ ⠿ Tưới nước                      │ watering │ ✎ ✕  │
  │ ⠿ Bón phân                       │fertilizing│ ✎ ✕  │
  │ ⠿ Phun thuốc / phun nước         │ spraying │ ✎ ✕  │
  │ ⠿ Thu hoạch                      │harvesting│ ✎ ✕  │
  │ ⠿ [custom] Kiểm tra sâu bệnh     │ pest_check│ ✎ ✕  │
  └──────────────────────────────────┴──────────┴───────┘
  ⠿ Kéo thả để sắp xếp

  [+ Thêm loại mới]
```

> Mỗi org tự maintain danh sách riêng.
> Xóa (✕) = `is_active = 0`, dữ liệu cũ vẫn giữ nguyên label cũ trong DB (slug bất biến).
> Sửa label (✎) = chỉ thay label hiển thị, slug/code KHÔNG đổi.

---

### A.5.2 Per-Org Hierarchy Labels — Tên danh mục phân cấp

> **Vấn đề:** `areaLabel()` / `lotLabel()` / `itemLabel()` trong class PHP là DEFAULT cho toàn vertical.
> Nhưng trong cùng một vertical TXNG, mỗi tổ chức có cơ cấu quản lý hoàn toàn khác nhau:
> - HTX trồng chè Bình Liêu:    **Khu → Lô → Cây**              (mặc định TXNG)
> - HTX chăn nuôi gà Tiên Yên:  **Chuồng → Ô → Con**            (override)
> - HTX nuôi cá nước ngọt:      **Ao → Ô → Con cá**             (override)
> - Hợp tác xã trồng lúa:       **Cánh đồng → Thửa → Cây lúa** (override)
>
> → Cùng vertical TXNG, mỗi org define tên phân cấp riêng.
> → Lưu vào bảng `vertical_config_items` với `config_group = 'hierarchy'`.

**Codes quy ước cho `config_group = 'hierarchy'`:**

| code | Ý nghĩa | Default TXNG | Ví dụ HTX gà | Ví dụ HTX cá |
|------|---------|-------------|-------------|-------------|
| `site_label` | Tên đơn vị tổng thể | Vùng sản xuất | Trang trại | Trang trại |
| `area_label` | Tên cấp 1 | Khu | Chuồng | Ao |
| `lot_label` | Tên cấp 2 | Lô | Ô | Ô |
| `item_label` | Tên cấp 3 (đơn vị nhỏ nhất) | Cây/Đơn vị | Con | Con cá |
| `item_label_plural` | Số nhiều của item | Cây | Con vật | Con cá |
| `item_code_prefix` | Tiền tố mã tự sinh cho item | C | GC | CA |

**Service layer — hierarchy label resolver (bổ sung vào `VerticalConfigService`):**

```php
// app/Foundation/Vertical/VerticalConfigService.php (mở rộng)
class VerticalConfigService
{
    /**
     * Trả về tất cả hierarchy labels của một org (target org, KHÔNG phải org đang login).
     * Fallback: DB per-org → vertical default → hardcoded fallback.
     */
    public static function hierarchyLabels(int $orgId, string $verticalCode, VerticalDefinition $vertical): array
    {
        $db = VerticalConfigItem::where('organization_id', $orgId)
            ->where('vertical_code', $verticalCode)
            ->where('config_group', 'hierarchy')
            ->where('is_active', true)
            ->pluck('label', 'code')
            ->toArray();

        $itemDefault = $vertical->itemLabel() ?? 'Đơn vị';

        return [
            'site'        => $db['site_label']        ?? $vertical->defaultSiteLabel()      ?? 'Vùng sản xuất',
            'area'        => $db['area_label']         ?? $vertical->areaLabel()             ?? 'Khu',
            'lot'         => $db['lot_label']          ?? $vertical->lotLabel()              ?? 'Lô',
            'item'        => $db['item_label']         ?? $itemDefault,
            'item_plural' => $db['item_label_plural']  ?? $itemDefault,
            'item_prefix' => $db['item_code_prefix']   ?? $vertical->defaultItemCodePrefix() ?? 'I',
        ];
    }

    // ... (các method activityTypes, legalDocTypes, configItems giữ nguyên)
}
```

**Seed khi org kích hoạt vertical (bổ sung Bước 4 vào `ActivateVerticalAction`):**

```php
// Bước 4: Seed hierarchy labels (idempotent — chỉ seed nếu chưa có)
$itemDefault = $vertical->itemLabel() ?? 'Đơn vị';
$hierarchyDefaults = [
    'site_label'        => $vertical->defaultSiteLabel()      ?? 'Vùng sản xuất',
    'area_label'        => $vertical->areaLabel()             ?? 'Khu',
    'lot_label'         => $vertical->lotLabel()              ?? 'Lô',
    'item_label'        => $itemDefault,
    'item_label_plural' => $itemDefault,
    'item_code_prefix'  => $vertical->defaultItemCodePrefix() ?? 'I',
];

foreach ($hierarchyDefaults as $code => $label) {
    VerticalConfigItem::firstOrCreate(
        ['organization_id' => $orgId, 'vertical_code' => $vertical->code(),
         'config_group' => 'hierarchy', 'code' => $code],
        ['label' => $label, 'is_active' => true, 'sort_order' => 0]
    );
}
```

**Cách dùng trong controller/blade — quy tắc quan trọng:**

```php
// Controller — lấy labels của TARGET ORG (HTX/nhà máy), KHÔNG phải org đang login
$labels = VerticalConfigService::hierarchyLabels(
    $target->targetOrganization->id,   // ← orgId của HTX/nhà máy
    $vertical->code(),
    $vertical
);
// $labels = ['site' => 'Trang trại', 'area' => 'Chuồng', 'lot' => 'Ô', 'item' => 'Con', ...]
return view('txng::targets.show', compact('target', 'labels', ...));

// Blade — dùng $labels thay vì $vertical->areaLabel()
@php $L = $labels; @endphp

<h3>{{ $L['area'] }}</h3>                                 {{-- "Chuồng" hoặc "Khu" --}}
<span>{{ $areaCount }} {{ $L['area'] }}</span>            {{-- "3 Chuồng" / "3 Khu" --}}
<span>{{ $itemCount }} {{ $L['item_plural'] }}</span>     {{-- "120 Con" / "1.200 Cây" --}}
<th>Tên {{ $L['lot'] }}</th>                              {{-- cột header "Tên Ô" / "Tên Lô" --}}
```

> **Không bao giờ gọi `$vertical->areaLabel()` trong blade/controller.**
> Interface method chỉ là seed source — runtime luôn qua `VerticalConfigService::hierarchyLabels()`.

---

### A.6 Database: Deployment Engine Core

> **Nguyên tắc:** Mọi "đối tượng được triển khai đến" (HTX, doanh nghiệp, trường, nhà máy...) đều là một `Organization`.
> Thông tin tên, địa chỉ, MST, liên hệ lưu trong `organizations` — không duplicate sang `deployment_targets`.
> `deployment_targets` chỉ lưu trạng thái triển khai: đang ở phase nào, ai phụ trách, ghi chú.

```sql
-- Bảng trung tâm: liên kết project ↔ organization (target)
-- Mỗi row = một tổ chức đang được triển khai trong project đó
CREATE TABLE deployment_targets (
    id                     BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    organization_id        BIGINT UNSIGNED NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
                           -- ↑ org đang QUẢN LÝ project này (THUCHOCVN hoặc tổ chức tư vấn)
    project_id             BIGINT UNSIGNED NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    vertical_code          VARCHAR(30)     NOT NULL,

    -- Org ĐƯỢC triển khai đến (HTX / doanh nghiệp / trường / nhà máy)
    -- Luôn là 1 Organization record — xem flow tạo mới tại A.10
    target_organization_id BIGINT UNSIGNED NOT NULL REFERENCES organizations(id) ON DELETE RESTRICT,

    current_phase          VARCHAR(50)     NOT NULL DEFAULT 'draft',
    assigned_employee_id   BIGINT UNSIGNED NULL REFERENCES employees(id) ON DELETE SET NULL,
    notes                  TEXT            NULL,
    created_by             BIGINT UNSIGNED NOT NULL REFERENCES users(id),
    created_at             TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE (project_id, target_organization_id),   -- 1 org chỉ vào project 1 lần
    INDEX  (organization_id, vertical_code),
    INDEX  (project_id),
    INDEX  (current_phase)
);

-- Checklist items từng phase — 1 row/item
CREATE TABLE deployment_checklist_items (
    id                  BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    organization_id     BIGINT UNSIGNED NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    deployment_target_id BIGINT UNSIGNED NOT NULL REFERENCES deployment_targets(id) ON DELETE CASCADE,
    phase               VARCHAR(50)     NOT NULL,
    item_key            VARCHAR(100)    NOT NULL,   -- slug cố định, ví dụ: 'gps_collected'
    item_label          VARCHAR(255)    NOT NULL,
    is_required         TINYINT(1)      NOT NULL DEFAULT 1,
    is_done             TINYINT(1)      NOT NULL DEFAULT 0,
    done_by             BIGINT UNSIGNED NULL REFERENCES users(id) ON DELETE SET NULL,
    done_at             TIMESTAMP       NULL,
    notes               TEXT            NULL,

    INDEX (deployment_target_id, phase)
);

-- Issues phát sinh trong quá trình triển khai
CREATE TABLE deployment_issues (
    id                  BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    organization_id     BIGINT UNSIGNED NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    deployment_target_id BIGINT UNSIGNED NOT NULL REFERENCES deployment_targets(id) ON DELETE CASCADE,
    project_id          BIGINT UNSIGNED NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    title               VARCHAR(255)    NOT NULL,
    description         TEXT            NULL,
    severity            VARCHAR(20)     NOT NULL DEFAULT 'medium', -- critical|high|medium|low
    status              VARCHAR(20)     NOT NULL DEFAULT 'open',   -- open|in_progress|resolved|closed
    owner_id            BIGINT UNSIGNED NULL REFERENCES users(id) ON DELETE SET NULL,
    resolved_at         TIMESTAMP       NULL,
    created_by          BIGINT UNSIGNED NOT NULL REFERENCES users(id),
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX (deployment_target_id, status)
);

-- Nhật ký tiến độ — immutable log
CREATE TABLE deployment_progress_logs (
    id                  BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    organization_id     BIGINT UNSIGNED NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    deployment_target_id BIGINT UNSIGNED NOT NULL REFERENCES deployment_targets(id) ON DELETE CASCADE,
    phase               VARCHAR(50)     NOT NULL,
    percent             TINYINT UNSIGNED NOT NULL DEFAULT 0,
    remark              TEXT            NULL,
    logged_by           BIGINT UNSIGNED NOT NULL REFERENCES users(id),
    logged_at           TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX (deployment_target_id)
);
```

---

### A.7 Route Pattern (generic)

Mỗi vertical dùng cùng route shape — `{vertical}` resolve từ URL prefix:

```php
// routes/web.php (hoặc Modules/Deployment/routes/web.php)
Route::prefix('{vertical}')
    ->middleware(['auth', 'vertical:{vertical}'])
    ->name('{vertical}.')
    ->group(function () {
        Route::get('/dashboard',        [DeploymentDashboardController::class, 'index'])->name('dashboard');
        Route::resource('/targets',     DeploymentTargetController::class);
        Route::get('/readiness',        [ReadinessController::class, 'index'])->name('readiness.index');
        Route::resource('/tasks',       DeploymentTaskController::class);
        Route::resource('/issues',      DeploymentIssueController::class);
        Route::get('/progress',         [DeploymentProgressController::class, 'index'])->name('progress.index');
        Route::get('/handover',         [HandoverController::class, 'index'])->name('handover.index');
        Route::post('/handover/{target}/complete', [HandoverController::class, 'complete'])->name('handover.complete');
        Route::get('/reports',          [DeploymentReportController::class, 'index'])->name('reports.index');
        Route::get('/academy',          [AcademyController::class, 'index'])->name('academy.index');
        Route::get('/settings',         [VerticalSettingsController::class, 'index'])->name('settings.index');

        // Export — chỉ available nếu vertical có exportAdapter()
        Route::get('/export',           [ExportController::class, 'index'])->name('export.index');
        Route::post('/export/{target}', [ExportController::class, 'generate'])->name('export.generate');
    });
```

Controller lấy vertical từ request:
```php
public function index(Request $request): View
{
    $vertical = $request->_vertical; // VerticalDefinition instance
    $targetLabel = $vertical->targetLabel(); // "HTX" | "Client" | "School"
    // ...
}
```

---

### A.8 ExportAdapterInterface

```php
// app/Foundation/ExportAdapterInterface.php
interface ExportAdapterInterface
{
    /** Tên hiển thị cho UI */
    public function label(): string;

    /** Danh sách file sẽ export (tên file => mô tả user-facing).
     *  Nhận $labels từ VerticalConfigService::hierarchyLabels() để render đúng per-org. */
    public function fileManifest(array $labels = []): array;

    /** Generate và trả về path ZIP file */
    public function generate(DeploymentTarget $target): string;
}
```

---

### A.9 Platform Core Modules — Reuse Map

| Platform Core | Deployment Engine dùng như thế nào |
|---|---|
| **`organizations`** | **Lưu mọi thông tin tổ chức target** (HTX / doanh nghiệp / trường / nhà máy) — tên, MST, địa chỉ, liên hệ |
| `organization_members` | Người đại diện / liên hệ của org target — member với role 'representative' |
| `projects` + `project_members` | Mỗi deployment project là một Project với `project_type = vertical_code` |
| `tasks` | Công việc gắn với deployment_target (taskable_type = DeploymentTarget) |
| `Survey Engine` | Readiness assessment survey — slug từ `vertical->readinessTemplateSlag()` |
| `Assessment Engine` | Chấm điểm readiness, domain scores, gap analysis — kết quả gắn với target org |
| `AiCopilot` | AI agents: OCR, Standardize, Validator, Coach — config per vertical |
| `Sandbox` | Training environments — scenarios seeded per vertical |
| `Certification` | Certifications unlocked khi pass sandbox — vertical defines levels |
| `KcItem` (Knowledge) | Hướng dẫn sử dụng hệ thống đối tác — vertical tạo KB entries cho từng partner system |
| `WorkflowAutomation` | Notification triggers — vertical registers triggers vào engine |
| `ActivityLog` | Audit trail cho mọi action trong deployment flow |

---

### A.10 Tạo Deployment Target — Organization Flow

Mỗi lần PM thêm một tổ chức vào project, hệ thống chạy `CreateDeploymentTargetAction`:

```php
// app/Actions/Deployment/CreateDeploymentTargetAction.php
class CreateDeploymentTargetAction
{
    public function execute(CreateDeploymentTargetData $data, VerticalDefinition $vertical): DeploymentTarget
    {
        // Bước 1: tìm org theo tax_code, nếu chưa có → tạo mới
        $targetOrg = Organization::where('tax_code', $data->taxCode)->first()
            ?? Organization::create([
                'name'          => $data->name,
                'tax_code'      => $data->taxCode,
                'phone'         => $data->phone,
                'email'         => $data->email,
                'province_code' => $data->provinceCode,
                'ward_code'     => $data->wardCode,
                'full_address'  => $data->fullAddress,
                'industry'      => $vertical->targetOrgCategory(), // 'cooperative'|'company'|'school'...
                'status'        => 'active',
                'source'        => 'vertical_created',             // phân biệt với tenant tự đăng ký
            ]);

        // Bước 2: nếu có người đại diện, thêm vào organization_members
        if ($data->representativeName) {
            $targetOrg->members()->firstOrCreate(
                ['role' => 'representative'],
                ['name' => $data->representativeName, 'phone' => $data->representativePhone]
            );
        }

        // Bước 3: tạo deployment_target
        return DeploymentTarget::create([
            'organization_id'        => TenantContext::organizationId(),
            'project_id'             => $data->projectId,
            'vertical_code'          => $vertical->code(),
            'target_organization_id' => $targetOrg->id,
            'current_phase'          => 'draft',
            'created_by'             => auth()->id(),
        ]);
    }
}
```

**Quick-add UI (form tối giản khi thêm HTX/Client vào project):**
```
Tên tổ chức *       [________________]
Mã số thuế          [________________]   ← hệ thống tự search org có sẵn theo MST
Người đại diện      [________________]
Số điện thoại       [________________]
Tỉnh/thành          [select]
Địa chỉ             [________________]
```

> Nếu nhập MST đã tồn tại → hệ thống show org tìm được, confirm thay vì tạo mới.
> Toàn bộ thông tin HTX có thể xem/sửa tại trang Organization của org đó (`/organizations/{id}`) — không duplicate màn hình chỉnh sửa.

---

## Part B — TXNG Vertical

### B.1 Seeder: Default "Traceability" Template

> Thay vì PHP class, TXNG-use-case được seed vào `vertical_templates` với code `'traceability'`.
> Không có `TxngVertical.php`. Không có `Modules/Txng/`. Tất cả trong `Modules/Deployment/`.
>
> Tổ chức muốn gọi vertical này là gì tùy họ — code `'traceability'` là định danh kỹ thuật trong DB,
> label hiển thị ('Triển khai TXNG', 'Quản lý trang trại', v.v.) org admin đặt khi kích hoạt.

```php
// Modules/Deployment/Database/Seeders/TraceabilityTemplateSeeder.php
VerticalTemplate::updateOrCreate(['code' => 'traceability'], [
    'label'                           => 'Triển khai Truy xuất nguồn gốc',
    'target_label'                    => 'Tổ chức',
    'target_org_category'             => 'cooperative',
    'has_physical_assets'             => false,   // ← KHÔNG dùng production_* tables; dùng Survey module
    'export_adapter'                  => null,     // Modules\Deployment\Adapters\CheckVnExportAdapter::class — bật ở Sprint 5
    'readiness_template_slug'         => 'readiness_v1',
    'data_collection_template_slug'   => 'data_collection_v1', // ← Survey template thu thập dữ liệu thực địa

    'phases' => ['draft','surveying','collecting','standardizing','exporting','training','handover','completed'],
    // surveying   = đi thực địa, điền survey thu thập dữ liệu
    // collecting  = thu thập hồ sơ pháp lý (upload vào Organization MediaLibrary)
    // standardizing = Data Ops review, chuẩn hóa dữ liệu, AI Validator
    // exporting   = xuất gói file CheckVN từ org + survey answers
    // training    = đào tạo HTX dùng hệ thống
    // handover    = bàn giao, ký biên bản

    'default_hierarchy' => [
        'site'        => 'Vùng sản xuất',
        'area'        => 'Khu',        // per-org override qua Settings: Chuồng / Ao / Cánh đồng
        'lot'         => 'Lô',         // per-org override: Ô / Thửa / Luống
        'item'        => 'Đơn vị',     // per-org override: Cây / Con / Con cá
        'item_plural' => 'Đơn vị',
        'item_prefix' => 'DV',         // per-org override: C / GC / CA
        // Dùng làm nhãn hiển thị trong form survey + export — không gắn với bảng DB riêng
    ],

    'default_checklist' => [
        // Phase: Khảo sát thực địa (Surveyor đến HTX, điền form survey)
        'surveying'     => [
            ['key' => 'entity_profile_verified',  'label' => 'Xác nhận hồ sơ tổ chức trong hệ thống',  'required' => true],
            ['key' => 'data_collection_assigned',  'label' => 'Giao form thu thập dữ liệu cho Surveyor', 'required' => true],
            ['key' => 'field_survey_done',         'label' => 'Hoàn thành khảo sát thực địa (survey)',  'required' => true],
            ['key' => 'gps_captured',              'label' => 'Thu thập GPS vùng sản xuất',              'required' => true],
            ['key' => 'photos_uploaded',           'label' => 'Upload hình ảnh thực địa',                'required' => false],
        ],
        // Phase: Thu hồ sơ pháp lý (upload vào Organization MediaLibrary)
        'collecting'    => [
            ['key' => 'business_license_uploaded', 'label' => 'Upload ĐKKD / Giấy phép kinh doanh',     'required' => true],
            ['key' => 'tax_id_verified',           'label' => 'Xác nhận Mã số thuế (MST)',              'required' => true],
            ['key' => 'representative_id_uploaded','label' => 'Upload CCCD/CMND người đại diện',        'required' => true],
            ['key' => 'quality_cert_uploaded',     'label' => 'Upload chứng nhận chất lượng (OCOP/ATTP/VietGAP)', 'required' => false],
            ['key' => 'logo_uploaded',             'label' => 'Upload logo tổ chức',                    'required' => false],
        ],
        // Phase: Chuẩn hóa (Data Ops review survey answers + org data)
        'standardizing' => [
            ['key' => 'org_data_standardized',     'label' => 'Chuẩn hóa thông tin tổ chức',           'required' => true],
            ['key' => 'area_data_standardized',    'label' => 'Chuẩn hóa dữ liệu vùng sản xuất',       'required' => true],
            ['key' => 'product_data_standardized', 'label' => 'Chuẩn hóa danh mục sản phẩm',           'required' => true],
            ['key' => 'history_data_entered',      'label' => 'Nhập lịch sử hoạt động (nếu có)',        'required' => false],
            ['key' => 'ai_validator_passed',       'label' => 'AI Validator đạt ≥ 95%',                 'required' => true],
        ],
        // Phase: Xuất file CheckVN (đọc từ organizations + survey_answers)
        'exporting'     => [
            ['key' => 'export_package_generated',  'label' => 'Xuất gói dữ liệu (Chủ thể + Vùng SX + Sản phẩm + Hồ sơ)', 'required' => true],
            ['key' => 'export_reviewed',           'label' => 'Data Ops review gói export',             'required' => true],
            ['key' => 'partner_import_confirmed',  'label' => 'Xác nhận đã nhập vào hệ thống đối tác', 'required' => true],
        ],
        // Phase: Đào tạo HTX dùng CheckVN
        'training'      => [
            ['key' => 'login_trained',             'label' => 'Đào tạo đăng nhập hệ thống đối tác',    'required' => true],
            ['key' => 'data_view_trained',         'label' => 'Đào tạo xem và kiểm tra dữ liệu',       'required' => true],
            ['key' => 'activity_log_trained',      'label' => 'Đào tạo nhập nhật ký canh tác',         'required' => true],
            ['key' => 'photo_upload_trained',      'label' => 'Đào tạo upload ảnh',                    'required' => true],
            ['key' => 'qr_mgmt_trained',           'label' => 'Đào tạo quản lý mã QR / định danh',     'required' => false],
        ],
        // Phase: Bàn giao
        'handover'      => [
            ['key' => 'docs_handedover',           'label' => 'Bàn giao hồ sơ pháp lý (bản gốc)',     'required' => true],
            ['key' => 'data_handover_confirmed',   'label' => 'Xác nhận dữ liệu đã nhập đối tác',     'required' => true],
            ['key' => 'user_guide_provided',       'label' => 'Cung cấp tài liệu hướng dẫn sử dụng',  'required' => true],
            ['key' => 'handover_minutes_signed',   'label' => 'Ký biên bản bàn giao',                  'required' => true],
            ['key' => 'account_transfer_done',     'label' => 'Chuyển giao tài khoản hệ thống đối tác','required' => true],
        ],
    ],

    // default_activity_types / default_legal_doc_types: giữ lại để seed vào vertical_config_items
    // dùng làm nhãn cho các select field trong survey form (không gắn với production_* tables)
    'default_activity_types' => [
        'watering'    => 'Tưới nước',
        'fertilizing' => 'Bón phân',
        'spraying'    => 'Phun thuốc / phun nước',
        'harvesting'  => 'Thu hoạch',
        'pruning'     => 'Tỉa cành / tỉa tán',
        'inspection'  => 'Kiểm tra định kỳ',
        'replanting'  => 'Trồng bổ sung',
        'other'       => 'Khác',
    ],

    'default_legal_doc_types' => [
        'business_registration' => 'Đăng ký kinh doanh (ĐKKD)',
        'personal_id'           => 'CCCD / CMND người đại diện',
        'quality_cert'          => 'Chứng nhận chất lượng (OCOP/ATTP/VietGAP)',
        'organic_cert'          => 'Chứng nhận hữu cơ',
        'logo'                  => 'Logo tổ chức',
        'product_photo'         => 'Ảnh sản phẩm',
        'other'                 => 'Khác',
    ],

    'default_roles' => ['pm', 'surveyor', 'data_ops', 'data_entry', 'trainer'],

    'sidebar_config' => [
        'TRIỂN KHAI'       => [
            ['label' => 'Dashboard',        'route' => '{vertical}.dashboard'],
            ['label' => 'Dự án',            'route' => '{vertical}.projects.index'],
            ['label' => '{target}',         'route' => '{vertical}.targets.index'],
            ['label' => 'Khảo sát năng lực','route' => '{vertical}.readiness.index'],
        ],
        'THU THẬP DỮ LIỆU' => [
            // Dữ liệu thu thập qua Survey module + Organization media — không có route cho production_*
            ['label' => 'Form khảo sát',    'route' => '{vertical}.data-collection.index'],
            ['label' => 'Hồ sơ pháp lý',   'route' => '{vertical}.org-docs.index'],
            ['label' => 'Export',           'route' => '{vertical}.export.index'],
        ],
        'CÔNG VIỆC'        => [
            ['label' => 'Công việc', 'route' => '{vertical}.tasks.index'],
            ['label' => 'Tiến độ',   'route' => '{vertical}.progress.index'],
            ['label' => 'Issues',    'route' => '{vertical}.issues.index'],
            ['label' => 'Bàn giao',  'route' => '{vertical}.handover.index'],
        ],
        'BÁO CÁO'          => [['label' => 'Báo cáo', 'route' => '{vertical}.reports.index']],
        'ĐÀO TẠO'          => [
            ['label' => 'Academy',    'route' => '{vertical}.academy.index'],
            ['label' => 'Chứng nhận', 'route' => '{vertical}.certifications.index'],
        ],
        'CẤU HÌNH'         => [['label' => 'Cấu hình', 'route' => '{vertical}.settings.index']],
    ],

    'is_active' => true,
]);
```

> Sidebar `{vertical}`, `{target}`, `{area}`, `{lot}`, `{item}`, `{site}` là placeholders được resolve
> bằng `$vertical->code()` và `VerticalConfigService::hierarchyLabels($orgId, ...)` khi render menu.

---

### B.2 Roles & Permissions

> Role names = `{vertical_code}_{suffix}`, ví dụ vertical code='traceability' → role `traceability_pm`.
> Tạo tự động khi org kích hoạt vertical, từ `VerticalTemplate.default_roles`.

| Quyền | system_admin | {v}_pm | {v}_surveyor | {v}_data_ops | {v}_data_entry | {v}_trainer | viewer |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Tạo / xóa dự án | ✅ | ✅ | | | | | |
| Thêm / xóa target khỏi dự án | ✅ | ✅ | | | | | |
| Phân công nhân sự | ✅ | ✅ | | | | | |
| Xem dashboard | ✅ | ✅ toàn bộ | ✅ target mình | ✅ target mình | ✅ target mình | ✅ target mình | ✅ |
| Nhập {area} / {lot} / {item} (staging) | ✅ | ✅ | ✅ | | | | |
| Upload ảnh / GPS | ✅ | ✅ | ✅ | | | | |
| Upload hồ sơ pháp lý | ✅ | ✅ | ✅ | | | | |
| Nhập lịch sử hoạt động | ✅ | ✅ | ✅ | ✅ | | | |
| Chuẩn hóa dữ liệu | ✅ | ✅ | | ✅ | | | |
| Chạy AI Validator | ✅ | ✅ | | ✅ | | | |
| Xem / tải file export | ✅ | ✅ | | ✅ | ✅ | | |
| Tick checklist "Đã nhập vào đối tác" | ✅ | ✅ | | ✅ | ✅ | | |
| Tạo / đóng Issue | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | |
| Tick checklist Đào tạo / Bàn giao | ✅ | ✅ | | | | ✅ | |
| Xem báo cáo | ✅ | ✅ | | | | | ✅ |

> `{v}_data_entry` chỉ có 3 quyền trong THUCHOCVN: xem data đã chuẩn hóa, tải file Excel, tick checklist "đã nhập vào hệ thống đối tác". Họ thao tác thực tế trên hệ thống đối tác bên ngoài.

```php
// Modules/Deployment/Policies/DeploymentTargetPolicy.php
public function view(User $user, DeploymentTarget $target): bool
{
    $v = $target->vertical_code;
    if ($user->hasRole(['system_admin', "{$v}_pm"])) return true;

    return $target->project->members()
        ->where('user_id', $user->id)
        ->whereIn('role', ["{$v}_surveyor", "{$v}_data_ops", "{$v}_data_entry", "{$v}_trainer"])
        ->exists();
}
```

---

### B.3 Phase State Machine

```
draft ──→ surveying ──→ collecting ──→ standardizing ──→ exporting ──→ training ──→ handover ──→ completed
  └──────────────────────────────────────────────────────────────────────────────────────────→ cancelled
```

| Phase | Trigger chuyển | Actor | Việc xảy ra tự động |
|---|---|---|---|
| `draft → surveying` | PM click "Bắt đầu khảo sát" | {v}_pm | Tạo checklist phase `surveying`; **`AssignDataCollectionSurveyAction`** clone survey `data_collection_v1`; tạo Task "Khảo sát {target} {name}" giao cho Surveyor |
| `surveying → collecting` | PM tick đủ checklist surveying | {v}_pm | Tạo checklist phase `collecting` |
| `collecting → standardizing` | PM tick đủ checklist collecting | {v}_pm | Tạo checklist phase `standardizing`; unlock tab "Chuẩn hóa" cho Data Ops |
| `standardizing → exporting` | Data Ops pass AI Validator (score ≥ 95%) | {v}_data_ops | Tạo checklist phase `exporting`; enable nút "Export CheckVN" |
| `exporting → training` | PM tick đủ checklist exporting | {v}_pm | Tạo checklist phase `training`; giao cho Trainer |
| `training → handover` | Trainer tick đủ training checklist | {v}_trainer | Tạo checklist phase `handover` |
| `handover → completed` | PM upload biên bản + click "Hoàn tất" | {v}_pm | Phase = `completed`; `survey_responses.status` → readonly; `media` (legal_docs) lock — không xoá được |

---

### B.4 Module 1 — Readiness Assessment

**Survey template slug:** `readiness_v1` — seeded 1 lần, `is_template = true` (slug khớp với `TraceabilityTemplateSeeder.readiness_template_slug`)

**4 domains, 20 câu, 100 điểm:**

| Domain | Trọng số | Số câu | Nội dung |
|---|---|---|---|
| Hạ tầng | 25% | 5 | Smartphone, Internet tại vùng trồng, máy tính, kết nối ổn định |
| Nhân sự | 25% | 5 | Người phụ trách TXNG, biết Excel, biết chụp ảnh, cam kết thời gian |
| Dữ liệu hiện có | 25% | 5 | Có nhật ký sẵn, có ảnh vùng trồng, có GPS, có hồ sơ pháp lý |
| Quy trình | 25% | 5 | Có quy trình canh tác chuẩn, cam kết cập nhật định kỳ |

**Scoring bands:**
```
≥ 80 → Sẵn sàng triển khai ngay
60–79 → Sẵn sàng với hỗ trợ thêm
40–59 → Cần chuẩn bị thêm 4–8 tuần
< 40  → Chưa đủ điều kiện — lập kế hoạch nền tảng trước
```

**Gap analysis output:** Mỗi domain thiếu điểm → AI Coach gợi ý ưu tiên hành động.

---

### B.5 Survey-based Data Collection (TXNG-specific)

> **Nguyên tắc:** THUCHOCVN thu thập dữ liệu từ HTX thông qua **Survey Engine** — không xây dựng lại
> hệ thống quản lý khu/lô/cây riêng (đó là nhiệm vụ của CheckVN sau khi bàn giao).
>
> Dữ liệu thu thập = input để **chuẩn bị** file import cho CheckVN, không phải quản lý production hàng ngày.
> Sau khi bàn giao: CheckVN sẽ là nơi lưu trữ và vận hành dữ liệu sản xuất chính thức.

---

#### B.5.1 Survey Template: `data_collection_v1`

Seeder: `Modules/Deployment/Database/Seeders/DataCollectionV1Seeder.php`

```
Survey: "Thu thập dữ liệu triển khai TXNG v1"
├── Section A — Xác nhận thông tin tổ chức   (section_code: 'org_profile')
│   Mục đích: verify/bổ sung thông tin vào organizations table
│   ├── org_name          (Text)     Tên tổ chức
│   ├── tax_code          (Text)     Mã số thuế / Mã hợp tác xã
│   ├── province          (Select)   Tỉnh/Thành phố
│   ├── full_address      (Textarea) Địa chỉ đầy đủ
│   ├── representative    (Text)     Họ tên người đại diện
│   ├── rep_phone         (Text)     SĐT người đại diện
│   └── main_product_type (Select)   Loại sản xuất chính (chè/cà phê/gà/cá/lúa/khác)
│
├── Section B — Thông tin vùng sản xuất      (section_code: 'production_info')
│   Mục đích: snapshot tổng quan để chuẩn bị DM_VUNSANXUAT + DM_DONVI
│   ├── site_name         (Text)     Tên vùng sản xuất
│   ├── total_area_sqm    (Number)   Tổng diện tích (m²)
│   ├── area_count        (Number)   Số khu / chuồng / ao   [bắt buộc]
│   ├── lot_count         (Number)   Tổng số lô / ô / thửa  [bắt buộc]
│   ├── item_count        (Number)   Tổng số cây / con / đơn vị
│   ├── gps_lat           (Text)     GPS trung tâm — Vĩ độ  [bắt buộc]
│   │                                👉 Mobile: nút "Lấy vị trí hiện tại" (Alpine.js + navigator.geolocation)
│   │                                   → auto-fill field; người dùng vẫn có thể gõ thủ công
│   ├── gps_lng           (Text)     GPS trung tâm — Kinh độ [bắt buộc, cùng nút với gps_lat]
│   └── area_notes        (Textarea) Mô tả sơ đồ vùng sản xuất
│
│   📷 Ảnh thực địa (ngoài survey form):
│   Upload ảnh qua route riêng → attach vào Organization MediaLibrary,
│   collection = 'field_photos', custom_properties = {deployment_target_id: X}
│   (Không phải SurveyField vì FieldType không hỗ trợ file upload)
│
├── Section C — Danh mục sản phẩm            (section_code: 'products')
│   Mục đích: chuẩn bị DM_SANPHAM
│   ├── product_count     (Number)   Số loại sản phẩm
│   ├── product_list      (Textarea) Danh sách sản phẩm — mỗi dòng: "Tên SP | Mã SP | Đơn vị"
│   │                                Ví dụ: "Trà hoa vàng | TH001 | Hộp 100g"
│   └── main_product      (Text)     Sản phẩm chủ lực [bắt buộc]
│
└── Section D — Lịch sử hoạt động            (section_code: 'history')
    Mục đích: xác nhận có dữ liệu lịch sử cần nhập vào DM_NHATKY không
    ├── has_history       (Boolean)  Có lịch sử canh tác cần nhập không?
    ├── history_years     (Number)   Số năm có lịch sử (nếu có)
    └── history_notes     (Textarea) Ghi chú lịch sử — nếu có, Surveyor sẽ upload file Excel riêng
                                     (thông qua Tab "Hồ sơ pháp lý" → collection = 'history_files')
```

> **Tại sao không dùng survey field cho file upload?**
> `FieldType` enum hiện có: Text, Textarea, Number, Select, Radio, Checkbox, Rating, Date, Boolean, Matrix, Ranking, Nps — **không có File**.
> Tất cả file upload (ĐKKD, ảnh, Excel lịch sử) đi qua **Organization MediaLibrary** với các collection riêng biệt:
> - `legal_docs` — hồ sơ pháp lý (ĐKKD, CCCD, OCOP...)
> - `field_photos` — ảnh thực địa
> - `history_files` — file Excel lịch sử hoạt động
> - `donvi_files` — file Excel danh sách đơn vị (DM_DONVI template)

**Seeder tạo Survey bằng SurveyAnswer fields:**
```php
// Modules/Deployment/Database/Seeders/DataCollectionV1Seeder.php
$survey = Survey::updateOrCreate(
    ['slug' => 'data_collection_v1', 'organization_id' => null],
    [
        'title'       => 'Thu thập dữ liệu triển khai TXNG v1',
        'description' => 'Form khảo sát thực địa dành cho Surveyor — điền trực tiếp tại HTX',
        'status'      => SurveyStatus::Active,
        'is_template' => true,
    ]
);

// Section A
$sectionA = $survey->sections()->updateOrCreate(
    ['section_code' => 'org_profile'],
    ['title' => 'Thông tin tổ chức', 'sort_order' => 1]
);
// ... tạo SurveyField cho từng field trong section
```

---

#### B.5.2 Gán Survey cho Deployment Target

Khi PM chuyển target sang phase `surveying` → **auto-gán** survey cho target:

```php
// Modules/Deployment/app/Actions/AssignDataCollectionSurveyAction.php
class AssignDataCollectionSurveyAction
{
    use AsAction;

    public function handle(DeploymentTarget $target, VerticalDefinition $vertical): SurveyResponse
    {
        $templateSlug = $vertical->dataCollectionTemplateSlag() ?? 'data_collection_v1';

        // Clone survey template → response riêng cho target này
        return CloneSurveyAction::make()->handle(
            survey: Survey::where('slug', $templateSlug)->firstOrFail(),
            respondentRef: "deployment_target:{$target->id}",
            respondentOrganizationId: $target->organization_id,
        );
    }
}
```

`AssignDataCollectionSurveyAction` được gọi từ `TransitionPhaseAction` khi phase = `surveying`.

---

#### B.5.3 Hồ sơ pháp lý — Organization MediaLibrary

Tài liệu pháp lý (ĐKKD, CCCD, OCOP, ATTP...) **upload trực tiếp vào Organization** của target:

```php
// Model Organization implements HasMedia (Spatie MediaLibrary)
$targetOrg->addMedia($request->file('doc_file'))
    ->withCustomProperties(['doc_type' => $request->doc_type, 'issued_at' => $request->issued_at])
    ->toMediaCollection('legal_docs');
```

| Loại dữ liệu | Nguồn | Upload khi nào |
|---|---|---|
| Tên, MST, địa chỉ, SĐT | `organizations` | Sync từ Section A survey |
| Người đại diện | `organization_members` (role=representative) | Sync từ Section A survey |
| ĐKKD, CCCD, OCOP, ATTP, Logo | `media` collection=`legal_docs` | Phase `collecting` |
| Ảnh thực địa | `media` collection=`field_photos` | Phase `surveying`, custom_prop: `deployment_target_id` |
| File Excel lịch sử (DM_NHATKY) | `media` collection=`history_files` | Phase `standardizing` nếu has_history=true |
| File Excel đơn vị (DM_DONVI) | `media` collection=`donvi_files` | Phase `standardizing` |
| Thông tin vùng SX, sản phẩm | `survey_answers` | Surveyor điền form |

**Routes upload media (ngoài survey form):**
```
POST /{vertical}/targets/{target}/media          — upload bất kỳ media type
DELETE /{vertical}/targets/{target}/media/{id}  — xoá (chỉ khi phase chưa completed)
GET  /{vertical}/targets/{target}/media          — list tất cả media theo collection
```
`request->collection` = `legal_docs | field_photos | history_files | donvi_files`

---

#### B.5.4 Sync Organization từ Survey Answers

**Trigger:** `SyncOrgFromSurveyAction` được gọi từ 2 nơi:
1. `DeploymentDataCollectionController@submit` — khi Surveyor bấm "Lưu & tiếp tục" sau mỗi section
2. `SurveyResponse` Observer `updated` — khi `status` chuyển sang `completed`

Section A sync **ngay lập tức** khi Surveyor submit Section A (không cần đợi toàn bộ survey xong).

```php
// Modules/Deployment/app/Actions/SyncOrgFromSurveyAction.php
class SyncOrgFromSurveyAction
{
    use AsAction;

    public function handle(SurveyResponse $response, DeploymentTarget $target): void
    {
        $answers = $response->answers->keyBy('field_code');

        // Chỉ sync nếu Section A có dữ liệu (idempotent — gọi nhiều lần an toàn)
        $target->targetOrganization->update(array_filter([
            'name'         => $answers['org_name']?->value_string,
            'tax_code'     => $answers['tax_code']?->value_string,
            'full_address' => $answers['full_address']?->value_text,
            'phone'        => $answers['rep_phone']?->value_string,
        ]));

        if ($repName = $answers['representative']?->value_string) {
            $target->targetOrganization->members()->updateOrCreate(
                ['role' => 'representative'],
                ['name' => $repName, 'phone' => $answers['rep_phone']?->value_string]
            );
        }

        // Auto-tick checklist items
        ChecklistAutoTickAction::make()->handle($target, $response);
    }
}
```

---

#### B.5.5 Checklist Auto-tick từ Survey Completion

`ChecklistAutoTickAction` — gọi sau mỗi lần sync (idempotent, chỉ tick nếu chưa tick):

```php
// Modules/Deployment/app/Actions/ChecklistAutoTickAction.php
class ChecklistAutoTickAction
{
    use AsAction;

    public function handle(DeploymentTarget $target, SurveyResponse $response): void
    {
        $answers = $response->answers->keyBy('field_code');
        $tick    = fn(string $key) => $this->tick($target, $key);

        // data_collection_assigned: SurveyResponse tồn tại = đã gán
        if ($response->exists) $tick('data_collection_assigned');

        // entity_profile_verified: Section A có org_name không rỗng
        if (filled($answers['org_name']?->value_string)) $tick('entity_profile_verified');

        // gps_captured: Section B gps_lat + gps_lng đều có giá trị
        if (filled($answers['gps_lat']?->value_string) && filled($answers['gps_lng']?->value_string))
            $tick('gps_captured');

        // field_survey_done: toàn bộ survey status = completed
        if ($response->status === ResponseStatus::Completed) $tick('field_survey_done');
    }

    private function tick(DeploymentTarget $target, string $key): void
    {
        DeploymentChecklistItem::where('deployment_target_id', $target->id)
            ->where('item_key', $key)
            ->where('is_done', false)
            ->update(['is_done' => true, 'done_by' => auth()->id(), 'done_at' => now()]);
    }
}
```

| Checklist key | Điều kiện auto-tick | Phase |
|---|---|---|
| `data_collection_assigned` | `SurveyResponse` tạo thành công | `surveying` |
| `entity_profile_verified` | Section A: `org_name` không rỗng | `surveying` |
| `gps_captured` | Section B: `gps_lat` + `gps_lng` cả hai có giá trị | `surveying` |
| `field_survey_done` | Toàn bộ survey `status = completed` | `surveying` |

---

#### B.5.6 Export CheckVN — Nguồn dữ liệu

| File CheckVN | Nguồn dữ liệu | Loại export |
|---|---|---|
| `DM_CHUTHET.xlsx` | `organizations` (tên, MST, địa chỉ) + `organization_members` (đại diện) + `media.custom_props` (số ĐKKD) | Auto-generate |
| `DM_VUNSANXUAT.xlsx` | Survey Section B: site_name, area_count, lot_count, item_count, GPS | Auto-generate (1 dòng tổng hợp) |
| `DM_SANPHAM.xlsx` | Survey Section C: parse `product_list` textarea — tách theo dòng, pipe `\|` làm delimiter | Auto-generate |
| `DM_HOSO.xlsx` | `media` collection `legal_docs` (doc_type, issued_at, expires_at, file URL) | Auto-generate |
| `DM_DONVI.xlsx` | `media` collection `donvi_files` — parse file Excel Surveyor upload theo template chuẩn | Parse-from-upload |
| `DM_NHATKY.xlsx` | `media` collection `history_files` — parse file Excel Surveyor upload (khi has_history=true) | Parse-from-upload |

**Flow cho DM_DONVI + DM_NHATKY (Parse-from-upload):**
```
1. Data Ops click "Tải mẫu DM_DONVI" → download Excel template với đúng cột CheckVN
2. Surveyor / Data Entry điền dữ liệu vào template offline
3. Upload file → POST /{vertical}/targets/{id}/media (collection=donvi_files)
4. Khi generate export: CheckVnExportAdapter đọc media → parse Excel → đưa vào ZIP
5. Nếu chưa có file upload → export vẫn chạy, DM_DONVI/NHATKY để trống (không block)
```

**Template columns:**
- `DM_DONVI_template.xlsx`: MA_CHUTHE | MA_KHU | MA_LO | MA_DV | LOAI_DV | GPS_LAT | GPS_LNG | NGAY_TRONG
- `DM_NHATKY_template.xlsx`: MA_CHUTHE | MA_LO | NGAY | LOAI_HOATDONG | SO_LUONG | DON_VI | NGUOI_THUCHIEN | GHI_CHU

Templates lưu tại `Modules/Deployment/resources/excel/` — download qua route:
`GET /{vertical}/export/template/{type}` (type = `donvi` | `nhatky`)

---

---

### B.6 Module 3 — AI Agents

4 agents đăng ký trong `AiCopilot` với driver `claude` (production) / `mock` (test):

> Agent codes được seed khi org kích hoạt vertical, format `{vertical_code}-{type}-agent`.

| Agent code | Trigger | Input | Output |
|---|---|---|---|
| `{vertical_code}-ocr-agent` | Upload hồ sơ pháp lý | Ảnh CCCD/ĐKKD/giấy tờ | JSON fields (tên, số, ngày cấp) |
| `{vertical_code}-standardize-agent` | Data Ops click "Chuẩn hóa" | Raw area/lot names | Tên chuẩn + mã đề xuất |
| `{vertical_code}-validator-agent` | Click "Kiểm tra dữ liệu" | `survey_answers` + `organizations` + `media` | Issue list + quality score |
| `{vertical_code}-coach-agent` | Chat hoặc auto-trigger | Deployment status | Priority list + Q&A |

**Validator agent — logic kiểm tra (không cần AI thực, dùng rule engine trước):**
```
Nguồn: survey_answers (data_collection_v1) + Organization media (legal_docs) + organizations table

Quy tắc:
  Section B gps_lat/gps_lng trống        → issue severity=high   ("Chưa thu thập GPS vùng sản xuất")
  Section B area_count=0 hoặc rỗng       → issue severity=high   ("Chưa khai báo số khu sản xuất")
  Section C product_list trống           → issue severity=high   ("Chưa có danh mục sản phẩm")
  Required doc type chưa upload vào media → issue severity=critical (business_registration, personal_id)
  organizations.tax_code NULL            → issue severity=critical ("Chưa có MST")
  organizations.name quá ngắn (<5 ký tự) → issue severity=medium  ("Tên tổ chức có vẻ chưa đầy đủ")
  GPS outlier (>50km từ province center)  → issue severity=medium  ("GPS có thể không chính xác")
  Section D has_history=true mà không có file lịch sử → issue severity=low
```

---

### B.7 Module 4 — Academy

> **Cập nhật Sprint 5:** Không còn dùng physical asset hierarchy (khu/lô/cây). Tất cả dữ liệu
> thu thập qua Survey (`data_collection_v1`) — 4 sections có cấu trúc sẵn. Export = field picker
> → single Excel streaming (không ZIP, không queue). Sandbox phản ánh đúng luồng thực tế này.

**5 Sandboxes (môi trường demo với dữ liệu giả):**

| # | Tên | Mục tiêu học | Pass | Unlock cert |
|---|---|---|---|---|
| S1 | Legal Document Collector | Upload hồ sơ pháp lý đúng collection + phân loại doc_type | ≥ 70đ | {vertical_label} Foundation |
| S2 | Field Survey | Điền form `data_collection_v1` (4 sections) + GPS capture + ảnh thực địa | ≥ 70đ | {vertical_label} Foundation |
| S3 | Data Quality & Export | Kiểm tra survey completeness → chạy validator → export Excel (field picker) | ≥ 70đ | {vertical_label} Practitioner |
| S4 | Data Validator | Phát hiện issues từ survey + org data + tạo + đóng Issues đúng severity | ≥ 70đ | {vertical_label} Practitioner |
| S5 | Deployment Coach | PM end-to-end: draft → surveying → ... → completed (8 phases, full checklist) | ≥ 70đ | {vertical_label} Professional |

**Rubric S1 (100đ):** Upload đúng collection (legal_docs)=20 / Phân loại doc_type chính xác=30 / Bộ hồ sơ bắt buộc đầy đủ (ĐKKD + CCCD)=30 / custom_properties (issued_at, expires_at) điền đúng=20

**Rubric S2 (100đ):** Section A hoàn thành (org_name + tax_code)=20 / Section B hoàn thành (GPS bắt buộc)=30 / Section C hoàn thành (product_list ≥ 1 dòng)=25 / Section D điền + ảnh upload=25

**Rubric S3 (100đ):** Survey answers không thiếu required fields=25 / Validator chạy không có critical issues=25 / Org data sync đúng từ Section A (tên, MST, địa chỉ)=25 / Export Excel field picker: chọn đúng trường + file download thành công=25

**Rubric S4 (100đ):** Phát hiện đúng ≥ 80% issues đã inject=40 / Tạo Issue đúng severity (critical/high/medium)=30 / Đóng issue đúng sau khi sửa=30

**Rubric S5 (100đ):** Hoàn thành đúng checklist mỗi phase=40 / Xử lý issues (create + resolve ≥ 1)=30 / Export Excel từ phase `exporting` thành công=30

**3 Certifications:**

> Cert names = `{vertical.label} {level}`, tạo động khi org kích hoạt vertical.

| Cert | Điều kiện | Scope |
|---|---|---|
| {vertical_label} Foundation | Pass S1 + S2 | Khảo sát thực địa + số hóa hồ sơ pháp lý |
| {vertical_label} Practitioner | Foundation + Pass S3 + S4 | Kiểm tra chất lượng dữ liệu + export |
| {vertical_label} Professional | Practitioner + Pass S5 + 1 dự án thực tế | PM triển khai độc lập end-to-end |

**Career Pathway:**
```
B1 Nhập liệu cơ bản     → role: {v}_surveyor  + cert: Foundation
B2 Quản trị dữ liệu     → role: {v}_data_ops  + cert: Practitioner
B3 Chuyên viên          → cả 2 role            + cert: Professional
B4 Chuyên gia triển khai → {v}_pm + ≥ 5 dự án completed
B5 Quản lý dự án        → {v}_pm + PM KPI score ≥ 85%
```

---

### B.8 Export — Survey-native Field Picker

> **Thực tế triển khai (Sprint 5):** Không còn dùng `ExportAdapterInterface` hay ZIP. Export là
> **field picker** → single Excel streaming qua `rap2hpoutre/fast-excel`. Linh hoạt với mọi vertical
> vì column names lấy từ survey field labels, không hardcode.

**Kiến trúc:**

```
SurveyExportBuilder::fieldCatalog($target)
    → groups: "Thông tin tổ chức" (org.* + settings.*)
            + mỗi SurveySection (survey.{section}.{field})

SurveyExportBuilder::buildRows($targets, $selectedSources, $labelMap)
    → Collection<array>  — mỗi row = 1 target, mỗi cột = 1 field được chọn

ExportColumnResolver::resolve($target, $source)
    → source path syntax:
       org.{field}                    → organizations.{field}
       settings.{key}                 → organizations.settings JSON key
       survey.{section_code}.{field_key} → survey_answers (data_collection_response)
       media.{collection}.{doc_type}.{prop} → spatie media custom_properties
```

**Routes export (hiện tại):**

| Route | Mô tả |
|---|---|
| `GET  /{vertical}/export/template/{type}` | Download blank Excel template (donvi \| nhatky) |
| `GET  /{vertical}/export/targets/{target}` | Field picker UI — chọn trường cho 1 target |
| `POST /{vertical}/export/targets/{target}/download` | Export Excel 1 target → stream download |
| `GET  /{vertical}/export/projects/{project}` | Field picker UI — chọn trường cho toàn dự án |
| `POST /{vertical}/export/projects/{project}/download` | Export Excel N targets → stream download |

**Đặc điểm:**
- Cột = field labels từ survey (không cần đổi code khi đổi survey template)
- Per-project: mỗi row = 1 org, header = field label chung cho tất cả targets
- Không queue, không polling, download ngay qua `FastExcel::download()`
- Security: `resolveSelected()` validate sources chỉ chấp nhận keys trong `labelMap` — không inject arbitrary source path
- Template Excel (donvi, nhatky) vẫn còn cho Surveyor điền offline rồi upload vào `donvi_files` / `history_files`

---

### B.9 Notification Triggers (top 10 quan trọng nhất)

> **Cập nhật Sprint 5:** Export không còn dùng queue job → bỏ trigger "Export ready".
> Thêm 2 triggers mới từ survey flow.

| Trigger | Channel | Recipient |
|---|---|---|
| Phase chuyển (any) | in-app + email | {v}_pm |
| Issue severity=critical tạo mới | in-app + email | {v}_pm + issue owner |
| Checklist 100% một phase | in-app | {v}_pm |
| Survey section submitted (Surveyor submit 1 section) | in-app | {v}_pm |
| Survey completed (status=Complete — tất cả sections xong) | in-app + email | {v}_pm |
| AI Validator: quality score < 80% | in-app | {v}_data_ops |
| Deployment completed | in-app + email | {v}_pm + {v}_trainer |
| Sandbox pass (unlock cert) | in-app | người học |
| Readiness score < 40 | in-app | {v}_pm |
| Task quá hạn 1 ngày | in-app | assignee |
| Biên bản bàn giao upload | in-app + email | {v}_pm + {v}_trainer |

---

### B.10 Vertical Settings (/txng/settings)

Nội dung màn hình "Cấu hình Vertical":

| Setting | Mô tả | Config group |
|---|---|---|
| **Danh mục phân cấp** | Tên gọi Site / Area / Lot / Item + tiền tố mã — per-org | `hierarchy` |
| Loại hoạt động | Danh sách loại hoạt động canh tác / sản xuất — per-org | `activity_type` |
| Loại tài liệu | Danh sách loại hồ sơ pháp lý — per-org | `doc_type` |
| Loại vật phẩm | Phân loại item (cây lâu năm / ngắn ngày / con giống...) — per-org | `item_type` |
| Phase checklist | Thêm / sửa / ẩn checklist items mặc định từng phase | — |
| Survey template | Chọn version survey thu thập dữ liệu (`data_collection_v1`, v2...) | — |
| Readiness template | Chọn version survey đánh giá sẵn sàng (readiness_v1, v2...) | — |
| Notification preferences | Bật/tắt từng trigger per role | — |
| Sandbox scenarios | Xem / reload demo data cho sandboxes | — |
| Vertical roles | Assign roles cho members trong org | — |

> **Export không có settings riêng** — field picker là per-export-session (người dùng chọn trực tiếp
> mỗi lần export), không cần config lưu. Nếu cần preset, thêm vào config group `export_preset` sau.

**Settings UI — `/txng/settings` tab "Danh mục phân cấp":**

```
┌─────────────────────────────────────────────────────────────────┐
│ Cài đặt Vertical TXNG                                            │
├──────────┬──────────────┬──────────────────┬──────────────────── ─┤
│ Tổng quan│ Phân cấp ◀  │ Loại hoạt động   │ Loại tài liệu  ... │
└──────────┴──────────────┴──────────────────┴─────────────────────┘

  Danh mục phân cấp vùng sản xuất
  Tùy chỉnh tên gọi các cấp quản lý phù hợp với loại hình của tổ chức.

  ┌───────────────────────────────────────┬──────────────────────────┐
  │ Cấp                                   │ Tên hiển thị             │
  ├───────────────────────────────────────┼──────────────────────────┤
  │ Đơn vị tổng thể (Site)               │ [Vùng sản xuất      ] ✎  │
  │ Cấp 1 (Area)                          │ [Khu                ] ✎  │
  │ Cấp 2 (Lot)                           │ [Lô                 ] ✎  │
  │ Cấp 3 (Item — đơn vị nhỏ nhất)       │ [Cây/Đơn vị         ] ✎  │
  │ Tiền tố mã cấp 3 (auto-code prefix)  │ [C                  ] ✎  │
  └───────────────────────────────────────┴──────────────────────────┘

  Xem trước mã sinh tự động:
  Site: "Vùng sản xuất Hoa Sơn" → Cấp 1: "Khu A" → Cấp 2: "Lô A01" → Cấp 3: "A01-C001"

  ──── Ví dụ cho HTX chăn nuôi: ────────────────────────────────────
  │ Đơn vị tổng thể  │ [Trang trại         ] ✎  │
  │ Cấp 1            │ [Chuồng             ] ✎  │
  │ Cấp 2            │ [Ô                  ] ✎  │
  │ Cấp 3            │ [Con                ] ✎  │
  │ Tiền tố mã       │ [GC                 ] ✎  │
  → Trang trại Tiên Yên → Chuồng A → Ô A01 → A01-GC001
  ───────────────────────────────────────────────────────────────────

  ⚠️ Thay đổi tên hiển thị không ảnh hưởng dữ liệu đã nhập.
     Mã code trong DB không đổi — chỉ thay nhãn hiển thị trên UI và báo cáo.
     Tiền tố mã chỉ áp dụng cho item tạo MỚI sau khi lưu.
```

---

## B.11 UI Screen Specifications

> Mockups dùng ASCII. Labels trong `[]` lấy từ `vertical->targetLabel()` / `areaLabel()` / `lotLabel()` tại runtime — không hardcode.

---

### B.11.1 Dashboard

**Route:** `GET /{vertical}/dashboard`
**Data source:** `DeploymentTarget::where(org)->with(targetOrg, issues, project)->get()`

```
┌─ Dashboard [Triển khai TXNG] ──────────────────────────────────────────┐
│                                                                          │
│  ┌──────────┐  ┌──────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │    2     │  │    8     │  │      5       │  │      3       │       │
│  │  Dự án   │  │[HTX]tổng │  │ Đang triển   │  │  Hoàn thành  │       │
│  └──────────┘  └──────────┘  └──────────────┘  └──────────────┘       │
│                                                                          │
│  TIẾN ĐỘ THEO GIAI ĐOẠN                                                │
│  Khảo sát   ████░░  3     Thu hồ sơ  ██░░░░  2                        │
│  Chuẩn hóa  ██░░░░  1     Nhập liệu  ██░░░░  1                        │
│  Đào tạo    █░░░░░  1     Bàn giao   ░░░░░░  0                        │
│                                                                          │
│  TIẾN ĐỘ NHÂN SỰ                         ISSUES                        │
│  ┌──────────────────────────────┐  ┌──────────────────────────────┐   │
│  │ Nguyễn Hà   Surveyor  5 HTX │  │ 🔴 Critical  2               │   │
│  │ 2.500 {item}  KPI: 95%      │  │ 🟡 High      5               │   │
│  │ Trần Lan    Data Ops  3 HTX │  │ 🟢 Medium    3               │   │
│  │ 1.500 {item}  KPI: 88%      │  │ ✅ Closed    12              │   │
│  └──────────────────────────────┘  └──────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────────────┘
```

**Computed fields:**
```php
$stats = DeploymentTarget::where('organization_id', $orgId)
    ->where('vertical_code', $vertical->code())
    ->selectRaw('current_phase, COUNT(*) as cnt')
    ->groupBy('current_phase')
    ->get();
// KPI nhân sự: project_members LEFT JOIN deployment_targets ON assigned_employee_id
```

---

### B.11.2 Target List (`/{vertical}/targets`)

**Filterable:** project, phase, province, assigned_employee
**Columns:** Tên [target], Mã số, Tỉnh, Giai đoạn, Tiến độ %, Phụ trách, Action

```
┌─ [HTX] ──────────────────────────────────────────────────────────────────┐
│  [Dự án: TXN-2026-0001 ▼]  [Giai đoạn: Tất cả ▼]  [Tỉnh ▼]           │
│  [🔍 Tìm theo tên / MST]                            [+ Thêm [HTX]]      │
│                                                                           │
│  Tên                  Mã số          Tỉnh    Giai đoạn    Tiến độ  Phụ trách │
│  ─────────────────────────────────────────────────────────────────────── │
│  HTX Hoa Sơn          0301234567     QN      Chuẩn hóa   ████░  60%  Hà │
│  HTX Sơn Hải          0301234568     QN      Khảo sát    ██░░░  30%  Hà │
│  Công ty ABC          0301234569     LC      Thu hồ sơ   ███░░  45%  Lan│
│                                                                 [Xem]    │
└───────────────────────────────────────────────────────────────────────────┘
```

**Tên "HTX" / "Doanh nghiệp" / "Trường"** lấy từ `$vertical->targetLabel()` — không hardcode trong blade.

---

### B.11.3 Target Organization Profile ← Screen quan trọng nhất

**Route:** `GET /{vertical}/targets/{id}`
**Data source:** `DeploymentTarget → targetOrg (Organization) + productionSite → areas → lots → items`

```
┌─ HTX Hoa Sơn ─────────────────────────────── [Chỉnh sửa thông tin org] ┐
│                                                                           │
│  MST: 0301234567  |  Tỉnh: Quảng Ninh  |  SĐT: 0912 345 678            │
│  Đại diện: Nguyễn Văn A  |  Dự án: TXN-2026-0001  |  Phụ trách: Hà    │
│                                                                           │
│  GIAI ĐOẠN TRIỂN KHAI                                                   │
│  ✅ Khảo sát → ✅ Thu hồ sơ → ⏳ Chuẩn hóa → ○ Nhập liệu → ○ Đào tạo │
│                                                                           │
│  TỔNG QUAN DỮ LIỆU                        ĐÁNH GIÁ SẴN SÀNG            │
│  ┌─────────────────────────────────────┐  ┌──────────────────────────────┐  │
│  │  3 {L.area}  │ 12 {L.lot}  │ 1.200 {L.item_plural}       │  │ Readiness Score: 62/100      │  │
│  │  GPS:  92% (11/12 {L.lot})         │  │ Hạ tầng   ████░░  80        │  │
│  │  Ảnh:  75% ( 9/12 {L.lot})         │  │ Nhân sự   ██░░░░  50        │  │
│  │  Lịch sử: 8/12 {L.lot} có dữ liệu │  │ Dữ liệu   ███░░░  55        │  │
│  │  Data Quality: 97%                 │  │ Quy trình ████░░  65        │  │
│  └─────────────────────────────────────┘  └──────────────────────────────┘  │
│                                                                           │
│  [Tab: Checklist] [Tab: Thu thập dữ liệu] [Tab: Hồ sơ] [Tab: Issues]  │
│  ─────────────────────────────────────────────────────────────────────   │
│  Tab: Checklist (phase hiện tại = standardizing)                        │
│    ✅ entity_profile_verified     Xác nhận hồ sơ tổ chức               │
│    ✅ field_survey_done           Hoàn thành khảo sát thực địa          │
│    ✅ gps_captured                GPS vùng sản xuất                     │
│    ☐  org_data_standardized       Chuẩn hóa thông tin tổ chức          │
│    ☐  product_data_standardized   Chuẩn hóa danh mục sản phẩm         │
│    ☐  ai_validator_passed         AI Validator ≥ 95%     [Chạy ngay]   │
│                                                                           │
│  Tab: Thu thập dữ liệu  ← Survey form completion + Organization docs   │
│    Survey: data_collection_v1                   [Mở form khảo sát]     │
│    ──────────────────────────────────────────────────────────────       │
│    Section A: Thông tin tổ chức  ✅ (7/7 fields)                       │
│    Section B: Vùng sản xuất      ✅ GPS: 21.03°N 105.8°E               │
│      Tổng: 3 khu · 12 lô · 1.200 {item_plural}                         │
│    Section C: Danh mục sản phẩm  ✅ 3 sản phẩm                        │
│    Section D: Lịch sử hoạt động  ☐ Chưa điền   [Điền ngay]            │
│                          [Export CheckVN]                               │
│                                                                           │
│  Tab: Hồ sơ pháp lý  ← Organization MediaLibrary (collection=legal_docs)│
│    ✅ ĐKKD — Số 0123/HTX — Cấp: 01/01/2020                            │
│       📎 DKKD_HTX_Hoa_Son.pdf                    [Tải] [Xoá]           │
│    ✅ CCCD đại diện — Nguyễn Văn A                                     │
│       📎 CCCD_NguyenVanA.jpg                      [Tải] [Xoá]           │
│    ⚠️ OCOP — Chưa upload                 [+ Upload OCOP]               │
│    ⚠️ ATTP — Chưa upload                 [+ Upload ATTP]               │
│                                                                           │
│  Tab: Issues                                                             │
│    🔴 GPS Section B để trống              High     Hà       Open  [Xem] │
│    🟡 Sản phẩm chưa có mã chuẩn hóa     Medium   Hà       Open  [Xem] │
└───────────────────────────────────────────────────────────────────────────┘
```

**Data source cho profile card:**
```php
// Controller
$target  = DeploymentTarget::with('targetOrg', 'project', 'assignedEmployee')->findOrFail($id);
$site    = $target->productionSite?->load('areas.lots.items');
$docs    = $target->legalDocs;
$issues  = $target->issues()->where('status', '!=', 'closed')->get();
$readiness = AssessmentResult::forOrg($target->target_organization_id)
                ->latestByTemplate($vertical->readinessTemplateSlag())->first();

// GPS completeness:
$lotsWithGps = $site?->areas->flatMap->lots->where('lat', '!=', null)->count();
$totalLots   = $site?->areas->flatMap->lots->count();
```

> **Link "Chỉnh sửa thông tin org"** → `/organizations/{target_organization_id}/edit` — tận dụng luôn màn hình Organization CRUD có sẵn, không viết màn hình trùng.

---

### B.11.4 Project Detail — 5 Tabs

**Route:** `GET /{vertical}/projects/{id}`

```
Dự án: TXN-2026-0001 — TXNG Bình Liêu 2026          [Sửa] [Xoá]
Tỉnh: Quảng Ninh  |  PM: Nguyễn Minh  |  Bắt đầu: 01/06  |  Mục tiêu: 30/09

[Tab: Targets(8)] [Tab: Checklist] [Tab: Tasks(12)] [Tab: Issues(7)] [Tab: Bàn giao]
────────────────────────────────────────────────────────────────────────────────────

Tab: Targets — danh sách HTX trong dự án
  Tên                Giai đoạn       Tiến độ    Phụ trách    Action
  HTX Hoa Sơn        Chuẩn hóa       ████░  60%  Hà           [Xem]
  HTX Sơn Hải        Khảo sát        ██░░░  30%  Hà           [Xem]
  HTX Đông Triều     Bàn giao        █████  90%  Thu          [Xem]
                                                        [+ Thêm HTX]

Tab: Checklist — tổng hợp checklist mọi HTX theo phase
  HTX Hoa Sơn — Phase: Chuẩn hóa
    ✅ Chuẩn hóa thông tin chủ thể    ✅ Mã hóa {area}    ☐ AI Validator
  HTX Sơn Hải — Phase: Khảo sát
    ✅ Khảo sát thực địa    ☐ GPS đầy đủ    ☐ Upload ảnh

Tab: Tasks — kanban (To Do | In Progress | Done)
  [To Do]               [In Progress]           [Done]
  Khảo sát HTX ABC      Chuẩn hóa Hoa Sơn       Khảo sát Hoa Sơn
  Thu hồ sơ ABC         —                        Thu hồ sơ Sơn Hải

Tab: Issues — bảng severity
  Tiêu đề              HTX          Severity   Status    Owner    Action
  Lô B02 thiếu GPS     HTX Hoa Sơn  🔴 High    Open      Hà       [Xem]
  OCOP chưa có         HTX Sơn Hải  🟡 Medium  Open      Hà       [Xem]

Tab: Bàn giao — trạng thái hoàn tất từng HTX
  HTX                Status bàn giao     Ngày BG      Biên bản
  HTX Hoa Sơn        ⏳ Đang tiến hành   —            —
  HTX Đông Triều     ✅ Hoàn thành       15/06/2026   [Download PDF]
```

---

### B.11.5 Màn hình Thêm Target mới (Quick-add)

**Route:** `POST /{vertical}/targets`

```
Thêm [HTX] mới vào dự án TXN-2026-0001
─────────────────────────────────────────────────────────────────

  Mã số (MST / ID)   [0301234567  ]  [🔍 Tìm]
                      ↓ Nếu tìm thấy:
                      ┌──────────────────────────────────────────┐
                      │ ✅ Tìm thấy: HTX Hoa Sơn — QN           │
                      │ Dùng tổ chức này?  [Có, thêm vào dự án] │
                      └──────────────────────────────────────────┘
                      ↓ Nếu không tìm thấy — hiện form tạo mới:

  Tên tổ chức *      [                    ]
  Người đại diện     [                    ]
  Số điện thoại      [                    ]
  Tỉnh/thành *       [Chọn tỉnh         ▼]
  Phường/Xã          [Chọn              ▼]
  Địa chỉ            [                    ]

  Phụ trách          [Chọn nhân viên    ▼]

                                    [Huỷ]  [Thêm vào dự án]
```

---

## B.12 Report Format

### B.12.1 Báo cáo PM

**Route:** `GET /{vertical}/reports/pm?project_id=&from=&to=`
**Export:** Excel + PDF

```
┌─ BÁO CÁO TỔNG QUAN DỰ ÁN ──────────────────────────────────────────────┐
│  Dự án: TXN-2026-0001  |  Kỳ: 01/06 – 16/06/2026  |  Xuất: 16/06/2026 │
│                                                                           │
│  A. TỔNG QUAN                                                            │
│  ┌────────┬────────┬────────┬────────┬────────┬────────┐                │
│  │{target}│ {lot}  │ {item} │GPS%    │ Ảnh%   │ Hoàn%  │                │
│  │   8    │  96    │ 4.800  │ 91%    │  78%   │  60%   │                │
│  └────────┴────────┴────────┴────────┴────────┴────────┘                │
│                                                                           │
│  B. TIẾN ĐỘ TỪNG HTX                                                    │
│  Tên HTX        Giai đoạn      Tiến độ   Issues   Phụ trách  Dự kiến BG │
│  HTX Hoa Sơn    Chuẩn hóa      60%       2        Hà         30/07      │
│  HTX Sơn Hải    Khảo sát       30%       1        Hà         15/08      │
│  HTX Đông Triều Hoàn thành    100%       0        Thu        15/06 ✅   │
│                                                                           │
│  C. ISSUES TỔNG HỢP                                                      │
│  🔴 Critical: 2  🟡 High: 5  🟢 Medium: 3  ✅ Closed: 12               │
│  Lô B02 thiếu GPS — HTX Hoa Sơn — Hà — Open                            │
│  OCOP chưa có — HTX Sơn Hải — Hà — Open                                │
│                                                                           │
│  D. HIỆU SUẤT NHÂN SỰ                                                   │
│  Nhân viên      Role  {target} phụ trách  {item} đã xử lý  KPI          │
│  Nguyễn Hà      Surveyor   5              2.500          95%             │
│  Trần Lan       Data Ops   3              1.500          88%             │
│  Lê Thu         Trainer    2              800            92%             │
│                                                        [Excel] [PDF]     │
└───────────────────────────────────────────────────────────────────────────┘
```

**Data queries:**
```php
// Tổng hợp
$targets = DeploymentTarget::where('project_id', $projectId)
    ->with(['targetOrg', 'productionSite.areas.lots.items', 'issues', 'assignedEmployee'])
    ->get();

// GPS completeness per target
$gpsRate = $lots->whereNotNull('lat')->count() / max($lots->count(), 1) * 100;

// Issue grouping
$issuesByTargetAndSeverity = DeploymentIssue::where('project_id', $projectId)
    ->selectRaw('deployment_target_id, severity, status, COUNT(*) as cnt')
    ->groupBy('deployment_target_id', 'severity', 'status')
    ->get();
```

---

### B.12.2 Báo cáo Tỉnh / Khu vực

**Route:** `GET /{vertical}/reports/regional?province_code=&year=`
**Mục đích:** Cơ quan quản lý / lãnh đạo xem tiến độ toàn tỉnh.

```
┌─ BÁO CÁO TRIỂN KHAI TỈNH QUẢNG NINH — 2026 ──────────────────────────┐
│  Tổng hợp đến: 16/06/2026                                              │
│                                                                         │
│  A. TỔNG QUAN TỈNH                                                     │
│  Đang triển khai: 20 HTX  |  Hoàn thành: 5 HTX  |  Sản phẩm: 45      │
│  {item_plural} đã lên hệ thống: 18.500                                  │
│                                                                         │
│  B. PHÂN BỔ THEO HUYỆN                                                 │
│  Huyện         HTX đang TK   HTX xong   Sản phẩm   Đặc thù            │
│  Bình Liêu         8            2          12       Trà hoa vàng       │
│  Đầm Hà            7            2          18       Gà Tiên Yên        │
│  Tiên Yên          5            1          15       Miến dong          │
│                                                                         │
│  C. TIẾN ĐỘ THEO GIAI ĐOẠN (toàn tỉnh)                               │
│  Khảo sát ████░░░░  6     Chuẩn hóa ████░░░░  4                       │
│  Thu hồ sơ ████░░░░  5    Nhập liệu ██░░░░░░  3                       │
│  Đào tạo   ██░░░░░░  2    Hoàn thành ████░░░░  5                      │
│                                                                 [PDF]   │
└─────────────────────────────────────────────────────────────────────────┘
```

**Data queries:**
```php
// Group by province của target org
$targets = DeploymentTarget::where('vertical_code', $vertical->code())
    ->whereHas('targetOrg', fn($q) => $q->where('province_code', $province))
    ->with('targetOrg', 'productionSite.products')
    ->get();

// Sản phẩm count qua survey answers Section C
$productCount = SurveyAnswer::whereIn('survey_response_id',
        SurveyResponse::whereIn('respondent_ref',
            $targets->map(fn($t) => "deployment_target:{$t->id}")->toArray()
        )->pluck('id')
    )
    ->where('field_code', 'product_count')
    ->sum('value_number');
```

---

## Part C — Build Order

### C.0 Prerequisite (đã có — không cần build)

Tất cả Platform Core modules đã DONE: Auth, Organization, Branch, Department, Employee, Survey Engine, Assessment Engine, AiCopilot, Sandbox, Certification, KpiGoal, WorkforceProfile, Project, Task, ActivityLog, WorkflowAutomation.

---

### C.1 Sprint 0 — Infrastructure (3 ngày)

**Mục tiêu:** Deployment Engine chạy được, Traceability template seeded vào DB, DatabaseVertical resolve thành công.

| Ngày | Việc cần làm | Test condition |
|---|---|---|
| 1 | Migration: `organization_verticals` + `deployment_targets` + `deployment_checklist_items` | `php artisan migrate` không lỗi |
| 1 | Migration: `deployment_issues` + `deployment_progress_logs` | Như trên |
| 2 | Migration `vertical_templates` + `VerticalTemplate` model + `DatabaseVertical` class | `VerticalRegistry::resolve('traceability')` trả về `DatabaseVertical` |
| 2 | `VerticalDefinition` interface + `VerticalRegistry` (DB-backed) + `RequireVertical` middleware | Middleware `vertical:traceability` không throw 403 |
| 2 | `TraceabilityTemplateSeeder` (B.1) — seed row vào `vertical_templates` | `VerticalTemplate::where('code','traceability')->exists()` = true |
| 3 | Route group `/{vertical}/*` + cơ bản Controller stubs | `GET /traceability/dashboard` → 200 (blade trống) |
| 3 | Sidebar render từ `vertical->sidebarGroups()` | Menu hiện đúng 6 nhóm, labels dynamic |

---

### C.2 Sprint 1 — Core Ops (5 ngày)

**Mục tiêu:** PM tạo dự án → thêm target → track tiến độ — luồng cơ bản chạy được.

| Ngày | Việc cần làm | Test condition |
|---|---|---|
| 1 | `ALTER TABLE projects ADD project_type` + filter theo vertical_code | `ProjectController` list chỉ show projects của vertical đang active |
| 1 | `CreateDeploymentTargetAction` (A.10): lookup org by tax_code → tạo Org nếu chưa có → tạo target | Nhập MST "0123456789" → Org tạo + target link `target_organization_id` |
| 2 | `DeploymentTargetController` CRUD + `DeploymentTargetPolicy` (dynamic roles) | PM thêm target → hiển thị tên từ `target_org.name` |
| 2 | Quick-add form (A.10 UI): search by tax_code → suggest existing org | Nhập MST có sẵn → show confirm "HTX Hoa Sơn đã tồn tại — Dùng org này?" |
| 3 | Phase transition action + auto-create checklists từ `DatabaseVertical::defaultChecklist()` | Phase `draft → surveying` → tạo checklist items đúng keys |
| 3 | Tick checklist UI (AJAX) + `DeploymentProgressLog` tự động | Tick item → `deployment_progress_logs` insert |
| 4 | `DeploymentIssueController` CRUD + severity/status | Create issue severity=high → list show đúng màu |
| 4 | Dashboard: tổng quan dự án, targets theo phase, issue summary | Tên HTX lấy từ `target_org.name`, không hardcode |
| 5 | Task integration: tạo Task khi phase chuyển | Phase → surveying → Task tự tạo |
| 5 | Bàn giao flow: upload biên bản + completed → phase chuyển `completed` | `completed` → `deployment_targets.current_phase = 'completed'`; media + survey answers readonly |

---

### C.3 Sprint 2 — Intelligence (5 ngày)

**Mục tiêu:** Dashboard thực sự có dữ liệu ý nghĩa, báo cáo xuất được.

| Ngày | Việc cần làm | Test condition |
|---|---|---|
| 1 | AI Validator rule engine (không cần AI thực) — scan survey_answers + media → tạo Issues | Run validator → issues tạo đúng type/severity |
| 1 | Data Quality Score tính từ validator results | Score 100% nếu không có issues, giảm theo severity |
| 2 | Dashboard TXNG: 4 KPI cards + progress bar HTX + issues widget | Số liệu live, refresh khi tick checklist |
| 2 | Dashboard TXNG: nhân sự widget (ai đang làm gì, bao nhiêu HTX) | Hiện đúng từ `project_members` |
| 3 | Báo cáo PM: tổng dự án + tiến độ HTX + issues + nhân sự | Export PDF/Excel (dùng DomPDF + Maatwebsite Excel) |
| 3 | Báo cáo tỉnh: HTX đang triển khai + hoàn thành + sản phẩm | Filter theo `target_org.province_code` + survey answers |
| 4 | Mobile-first layout `layouts/mobile.blade.php` — GPS capture cho survey form | Surveyor mobile: tap GPS → gps_lat/gps_lng Section B tự điền |
| 4 | Photo upload (Spatie MediaLibrary trên Organization) + resize client-side 1920px | Upload → thumbnail hiện ngay, attach vào legal_docs collection |
| 5 | Notification triggers (10 triggers quan trọng nhất — B.9) | Phase change → in-app notification < 30s |

---

### C.4 Sprint 3 — Readiness Assessment (3 ngày)

| Ngày | Việc cần làm | Test condition |
|---|---|---|
| 1 | Survey template seeder `readiness_v1` (20 câu, 4 domain — slug khớp với `TraceabilityTemplateSeeder.readiness_template_slug`) | Template xuất hiện trong Survey list |
| 1 | `CloneSurveyAction` — clone template cho từng HTX | Clone → response riêng biệt theo HTX |
| 2 | Kết quả readiness: domain scores + tổng + scoring band | Submit survey → score hiện ngay |
| 2 | Gap analysis: domain thiếu điểm → AI Coach gợi ý ưu tiên | Score domain Nhân sự = 40 → Coach gợi ý đúng |
| 3 | Readiness score hiện trên card HTX + dashboard | HTX card: "Readiness: 62/100 — Sẵn sàng với hỗ trợ" |

---

### C.5 Sprint 4 — Data Collection Flow (4 ngày)

> **Mục tiêu:** Surveyor dùng được form khảo sát thực địa, dữ liệu tự động sync vào Organization, checklist tự tick.
> Tận dụng Survey Engine + Organization module đã có — không tạo bảng mới.

| Ngày | Việc cần làm | Test condition |
|---|---|---|
| 1 | `DataCollectionV1Seeder` — tạo survey template `data_collection_v1` với 4 sections + 20 fields (B.5.1) | `Survey::where('slug','data_collection_v1')->exists()` = true |
| 1 | Migration: `ALTER TABLE vertical_templates ADD data_collection_template_slug VARCHAR(100) NULL` | Column tồn tại, không lỗi migrate |
| 1 | `UpdateTraceabilityTemplateSeeder` — chạy lại seeder với `data_collection_template_slug='data_collection_v1'`, `has_physical_assets=false`, phases mới | DB row cập nhật đúng |
| 2 | `AssignDataCollectionSurveyAction` (B.5.2) — clone survey khi phase → `surveying` | Phase change → SurveyResponse tạo với respondent_ref=`deployment_target:{id}` |
| 2 | `SyncOrgFromSurveyAction` (B.5.4) — đọc Section A → update organizations + organization_members | Submit Section A → org.name cập nhật |
| 2 | `ChecklistAutoTickAction` (B.5.5) — auto-tick dựa trên survey section completion | GPS filled → checklist `gps_captured` tự tick |
| 3 | Route + Controller: `GET /{vertical}/targets/{id}/collect` — hiển thị form survey mobile-first | Truy cập route → form survey hiện đúng 4 sections |
| 3 | GPS capture button (Alpine.js + navigator.geolocation) — auto-fill gps_lat/gps_lng | Nhấn GPS → lat/lng điền vào field ngay lập tức |
| 4 | Tab "Hồ sơ pháp lý" trong target detail — upload docs vào Organization MediaLibrary (B.5.3) | Upload ĐKKD.pdf → media attach vào targetOrg, collection='legal_docs' |
| 4 | Route `POST /{vertical}/targets/{id}/docs` + `DELETE .../{mediaId}` — quản lý legal docs | Upload → hiện trong danh sách; Delete → xoá media |

---

### C.6 Sprint 5 — Export CheckVN (4 ngày)

> **Nguồn dữ liệu:** `organizations` + `organization_members` + `survey_answers` + `media` (legal_docs).
> Không đọc từ production_* tables — xem B.5.6 để biết mapping file ↔ nguồn.

| Ngày | Việc cần làm | Test condition |
|---|---|---|
| 1 | `composer require rap2hpoutre/fast-excel` + `CheckVnExportAdapter` implements `ExportAdapterInterface` | Adapter instantiate không lỗi |
| 1 | `DmChuthetExport` — đọc từ `organizations` + `organization_members` + `media.custom_properties` (số ĐKKD) | Generate → file có tên, MST, địa chỉ, người đại diện đúng |
| 2 | `DmVunsanxuatExport` — đọc từ survey Section B answers (site_name, area_count, lot_count, GPS) | Generate → file có tên vùng SX, GPS đúng |
| 2 | `DmSanphamExport` — đọc từ survey Section C, parse `product_list` textarea (pipe-separated per dòng) | Generate → file có đúng số sản phẩm, mã, đơn vị |
| 2 | `DmHosoExport` — đọc từ `media` collection `legal_docs` của targetOrg (doc_type, issued_at, URL) | Generate → file có list tài liệu |
| 3 | Route `GET /{vertical}/export/template/{type}` (type=`donvi`\|`nhatky`) → download template Excel chuẩn | Download → file đúng cột tiêu đề CheckVN |
| 3 | `DmDonviExport` + `DmNhatkyExport` — parse file từ `media` collections `donvi_files` / `history_files` (B.5.6) | Upload file → generate → rows đúng cột; nếu chưa upload → file trống không block |
| 4 | ZIP 6 files → `GenerateCheckVnExportJob` (queue) + UTF-8 BOM | Download ZIP trên Windows Excel → không lỗi font |
| 4 | Enable `export_adapter = CheckVnExportAdapter::class` trong `UpdateTraceabilityTemplateSeeder` | Nút "Export CheckVN" hiện trên target detail; click → job dispatch → ZIP download khi xong |

---

### C.7 Sprint 6 — AI Agents ~~(BỎ QUA)~~

> **Quyết định:** Sprint 6 bỏ qua. Với luồng survey-based có cấu trúc sẵn (4 sections, fields định
> kiểu rõ ràng), OCR auto-fill và standardize agent không còn là điểm nghẽn chính. Triển khai khi
> thực sự cần (ví dụ: vertical mới có nhiều free-text hoặc scan tài liệu hàng loạt).

---

### C.8 Sprint 7 — Academy (5 ngày)

**Mục tiêu:** Nhân sự có thể tự học và được chứng nhận đúng luồng thực tế (survey-based, field picker export). Sandbox dùng lại hoàn toàn Survey Engine + DeploymentEngine — không cần hạ tầng riêng.

| Ngày | Việc cần làm | Test condition |
|---|---|---|
| 1 | `TraceabilitySandboxSeeder` — tạo 5 sandbox environments: mỗi env = 1 SurveyResponse fixture (pre-filled `data_collection_v1`) + 1 DeploymentTarget sandbox + legal_docs seeded | `php artisan db:seed --class=TraceabilitySandboxSeeder` → 5 sandboxes xuất hiện trong Academy |
| 1 | `SandboxResetAction` — reset sandbox về trạng thái ban đầu (xóa answers, media, issues) → dùng lại fixture gốc | Reset sandbox → survey answers sạch, media sạch; fixture org data giữ nguyên |
| 2 | **S1 (Legal Document Collector)** — sandbox: upload docs vào `legal_docs` collection + rubric auto-score (B.7) | Thiếu ĐKKD → trừ 30đ; phân loại sai doc_type → trừ 30đ; pass ≥70 → cert Foundation unlock |
| 2 | **S2 (Field Survey)** — sandbox: điền form `data_collection_v1` (4 sections) trong sandbox env; GPS mock (coordinates giả nhưng valid format) | Thiếu GPS → trừ 30đ; Section C trống → trừ 25đ; pass ≥70 → cert Foundation unlock |
| 3 | **S3 (Data Quality & Export)** — sandbox: survey fixture có 3 lỗi inject sẵn (GPS outlier, product_list trống, tax_code thiếu); người học review + sửa + export field picker | Validator còn critical issues → block export trong sandbox; sau khi sửa đủ → export thành công; pass ≥70 → cert Practitioner unlock |
| 3 | **S4 (Data Validator)** — sandbox: fixture có 5 lỗi inject (2 critical, 2 high, 1 medium); người học tạo Issues đúng severity + đóng issues | Phát hiện đúng ≥80% lỗi → 40đ; severity đúng → 30đ; đóng đúng → 30đ; pass ≥70 → cert Practitioner unlock |
| 4 | **S5 (Deployment Coach)** — sandbox: PM simulation, 1 target từ `draft → completed` (8 phases), mỗi phase phải tick đủ checklist + xử lý issue inject | Hoàn thành ≥ 6/8 phases → 40đ; resolve ≥ 1 issue → 30đ; export Excel phase `exporting` → 30đ; pass ≥70 → cert Professional unlock |
| 4 | Certification unlock logic — `CertificationUnlockAction`: check sandbox scores → tạo cert record khi đủ điều kiện (B.7) | Pass S1+S2 → Foundation cert tự cấp; Pass S3+S4 → Practitioner; Pass S5 + 1 dự án thực tế → Professional |
| 5 | Academy dashboard: progress bar từng sandbox, cert đã đạt, career pathway map | Dashboard hiện đúng score + cert + pathway level; cert có thể download (PDF generated) |

---

### C.9 Sprint 8 — Notifications & Polish (4 ngày)

**Mục tiêu:** Hệ thống thông báo đủ để PM không cần F5 theo dõi; UX mobile hoàn chỉnh; Settings UI cho phép org tự cấu hình mà không cần deploy code.

| Ngày | Việc cần làm | Test condition |
|---|---|---|
| 1 | **Notification triggers (B.9)** — 11 triggers qua Laravel Notifications (in-app + email): phase change, issue critical, checklist 100%, survey section submit, survey completed, validator score <80%, deployment completed, sandbox pass, readiness <40, task overdue, biên bản upload | Phase `draft→surveying` → PM nhận in-app < 30s; survey completed → PM nhận email < 2 phút |
| 1 | `NotificationPreferencesController` — lưu bật/tắt từng trigger per role vào `vertical_config_items` (config_group='notification_pref') | Tắt trigger "survey section submit" → không gửi notification khi Surveyor submit; các trigger khác không ảnh hưởng |
| 2 | **Vertical Settings UI** (`/{vertical}/settings`) — 5 tabs: Danh mục phân cấp / Loại hoạt động / Loại tài liệu / Phase checklist / Survey template (B.10) | Thêm loại hoạt động mới → hiện trong select khi tạo activity; xóa loại (is_active=false) → ẩn khỏi form |
| 2 | Checklist editor trong Settings — thêm / sửa / reorder / ẩn items per phase; chỉ áp dụng cho target TẠO MỚI sau khi lưu | Thêm checklist item mới vào phase `surveying` → target mới có item đó; target cũ không bị ảnh hưởng |
| 3 | **Mobile UX polish**: touch targets ≥ 44px trên survey form + media upload + checklist tick; GPS indicator (spinner khi đang lấy tọa độ → ✓ khi xong); upload progress bar (XMLHttpRequest onprogress) | Lighthouse mobile Performance ≥ 75; GPS spinner hiện < 500ms sau khi tap; upload bar cập nhật real-time |
| 4 | **Báo cáo export polish** — reports/pm và reports/province: export PDF (DomPDF) + Excel (Fast-Excel); filter by date range + project | `GET /reports/pm?from=2026-01-01&to=2026-06-30&project_id=5` → PDF đúng dữ liệu; Excel đúng columns |

---

## Appendix A — Per-Org Personalization Audit

Xác nhận rõ ràng: dữ liệu nào cá nhân hóa theo **từng tổ chức**, dữ liệu nào chung theo **vertical**.

| Datum | Lưu ở đâu | Per-org? | Ghi chú |
|-------|-----------|----------|---------|
| Tên, MST, địa chỉ, SĐT tổ chức | `organizations` | ✅ Mỗi org 1 record | Không duplicate vào deployment |
| Trạng thái kích hoạt vertical | `organization_verticals` | ✅ Mỗi org tự bật/tắt | |
| Danh sách loại hoạt động | `vertical_config_items (activity_type)` | ✅ Org tự customize | Seed từ vertical defaults |
| Danh sách loại tài liệu pháp lý | `vertical_config_items (doc_type)` | ✅ Org tự customize | Seed từ vertical defaults |
| Loại vật phẩm / item | `vertical_config_items (item_type)` | ✅ Có thể thêm khi cần | Ví dụ: cây/con/máy |
| Nhân viên phụ trách (PM) | `deployment_targets.assigned_employee_id` | ✅ Mỗi target khác nhau | |
| Ghi chú triển khai | `deployment_targets.notes` | ✅ Per-target | |
| Trạng thái checklist | `deployment_checklist_items` | ✅ Per-deployment | |
| Issues phát sinh | `deployment_issues` | ✅ Per-deployment | |
| Dữ liệu thu thập thực địa | `survey_answers` (data_collection_v1) | ✅ Per-target, per-response | Surveyor điền form → answers gắn với respondent_ref |
| Phase templates | `vertical_templates.phases` (JSON) | ❌ Per-vertical | Tất cả org dùng chung; đọc qua `DatabaseVertical::phases()` |
| Nhãn Site/Area/Lot/Item | `vertical_config_items (hierarchy)` | ✅ Per-org | Seed từ `vertical_templates.default_hierarchy`; org override qua Settings → "Danh mục phân cấp". Runtime: `VerticalConfigService::hierarchyLabels()` |
| Checklist template (seed) | `vertical_templates.default_checklist` (JSON) | ❌ Per-vertical (seed) | Sau khi seed, từng target độc lập |
| Export adapter | `vertical_templates.export_adapter` (FQCN) | ❌ Per-vertical | Một vertical → một adapter; null nếu không cần |

> **Kết luận:** Business data (loại hoạt động, loại tài liệu, trạng thái) đều per-org.
> **Labels phân cấp** (Site/Area/Lot/Item) cũng là per-org — không còn hard-code theo vertical.
> Chỉ **cấu trúc workflow** (phases, checklist keys, roles, export adapter) là per-vertical — đây là blueprint.
>
> **Runtime rule:** Mọi nơi cần hiển thị tên cấp → `VerticalConfigService::hierarchyLabels($targetOrgId, ...)`.
> Không bao giờ đọc `$vertical->areaLabel()` / `->lotLabel()` / `->itemLabel()` trực tiếp ở view/controller.

---

## Appendix B — Demo: ManufacturingVertical (Vertical `mfg`)

**Bài toán:** THUCHOCVN triển khai hệ thống vận hành / giám sát chất lượng cho 2 nhà máy khách hàng.
Cùng vertical `mfg`, nhưng 2 nhà máy ngành nghề hoàn toàn khác nhau → activity types khác nhau.

### B.1 Seeder: Default "Manufacturing" Template

> Thay vì PHP class, MFG-use-case được seed vào `vertical_templates` với code `'manufacturing'`.
> Không có `MfgVertical.php`. Không có `Modules/Manufacturing/`. Tất cả trong `Modules/Deployment/`.

```php
// Modules/Deployment/Database/Seeders/ManufacturingTemplateSeeder.php
VerticalTemplate::updateOrCreate(['code' => 'manufacturing'], [
    'label'                   => 'Triển khai Nhà máy',
    'target_label'            => 'Nhà máy',
    'target_org_category'     => 'factory',
    'has_physical_assets'     => true,
    'export_adapter'          => null, // không export ra hệ thống đối tác
    'readiness_template_slug' => 'factory_readiness_v1',

    'phases' => ['draft','assessment','setup','integration','testing','training','handover','completed'],

    'default_hierarchy' => [
        'site'        => 'Nhà máy',
        'area'        => 'Xưởng',
        'lot'         => 'Dây chuyền',
        'item'        => 'Máy / Thiết bị',
        'item_plural' => 'Máy / Thiết bị',
        'item_prefix' => 'M',   // M001, M002...
    ],

    'default_checklist' => [
        'assessment'  => [
            ['key' => 'factory_profile_created', 'label' => 'Tạo hồ sơ nhà máy',        'required' => true],
            ['key' => 'floor_plan_uploaded',     'label' => 'Sơ đồ mặt bằng xưởng',     'required' => true],
            ['key' => 'machine_list_collected',  'label' => 'Danh sách máy thiết bị',     'required' => true],
            ['key' => 'production_flow_mapped',  'label' => 'Sơ đồ quy trình sản xuất',  'required' => true],
        ],
        'setup'       => [
            ['key' => 'area_structure_created',  'label' => 'Tạo cơ cấu {area}/{lot}',   'required' => true],
            ['key' => 'machine_codes_generated', 'label' => 'Sinh mã thiết bị',           'required' => true],
            ['key' => 'iso_docs_uploaded',       'label' => 'Upload hồ sơ ISO/HACCP',     'required' => false],
        ],
        'integration' => [
            ['key' => 'sensor_connected',        'label' => 'Kết nối cảm biến IoT',       'required' => false],
            ['key' => 'erp_linked',              'label' => 'Liên kết ERP (nếu có)',       'required' => false],
            ['key' => 'data_schema_validated',   'label' => 'Validate cấu trúc dữ liệu',  'required' => true],
        ],
        'testing'     => [
            ['key' => 'test_run_completed',      'label' => 'Chạy thử toàn hệ thống',    'required' => true],
            ['key' => 'ai_validator_passed',     'label' => 'AI Validator ≥ 95%',         'required' => true],
        ],
        'training'    => [
            ['key' => 'supervisor_trained',      'label' => 'Đào tạo quản đốc',           'required' => true],
            ['key' => 'operator_trained',        'label' => 'Đào tạo vận hành viên',      'required' => true],
        ],
        'handover'    => [
            ['key' => 'handover_minutes_signed', 'label' => 'Ký biên bản bàn giao',       'required' => true],
            ['key' => 'account_transfer_done',   'label' => 'Chuyển giao tài khoản',      'required' => true],
        ],
    ],

    'default_activity_types' => [
        'maintenance'  => 'Bảo trì định kỳ',
        'inspection'   => 'Kiểm tra / Kiểm định',
        'calibration'  => 'Hiệu chuẩn thiết bị',
        'repair'       => 'Sửa chữa',
        'production'   => 'Vận hành sản xuất',
        'cleaning'     => 'Vệ sinh công nghiệp',
        'other'        => 'Khác',
    ],

    'default_legal_doc_types' => [
        'business_license' => 'Giấy phép kinh doanh',
        'iso_cert'         => 'Chứng nhận ISO',
        'haccp_cert'       => 'Chứng nhận HACCP',
        'fire_safety'      => 'Nghiệm thu PCCC',
        'electrical_cert'  => 'An toàn điện',
        'machine_manual'   => 'Tài liệu kỹ thuật máy',
        'other'            => 'Khác',
    ],

    'default_roles'  => ['pm', 'engineer', 'trainer'],
    'sidebar_config' => [
        'TRIỂN KHAI'       => [
            ['label' => 'Dashboard',        'route' => '{vertical}.dashboard'],
            ['label' => 'Dự án',            'route' => '{vertical}.projects.index'],
            ['label' => '{target}',         'route' => '{vertical}.targets.index'],
            ['label' => 'Đánh giá sẵn sàng','route' => '{vertical}.readiness.index'],
        ],
        'CHUẨN BỊ DỮ LIỆU' => [
            ['label' => '{site}',                  'route' => '{vertical}.sites.index'],
            ['label' => '{area} – {lot} – {item}', 'route' => '{vertical}.areas.index'],
            ['label' => 'Nhật ký hoạt động',       'route' => '{vertical}.activity-logs.index'],
            ['label' => 'Hồ sơ kỹ thuật',          'route' => '{vertical}.legal-docs.index'],
        ],
        'CÔNG VIỆC'        => [
            ['label' => 'Công việc', 'route' => '{vertical}.tasks.index'],
            ['label' => 'Tiến độ',   'route' => '{vertical}.progress.index'],
            ['label' => 'Issues',    'route' => '{vertical}.issues.index'],
            ['label' => 'Bàn giao',  'route' => '{vertical}.handover.index'],
        ],
        'BÁO CÁO'          => [['label' => 'Báo cáo', 'route' => '{vertical}.reports.index']],
        'ĐÀO TẠO'          => [
            ['label' => 'Academy',    'route' => '{vertical}.academy.index'],
            ['label' => 'Chứng nhận', 'route' => '{vertical}.certifications.index'],
        ],
        'CẤU HÌNH'         => [['label' => 'Cấu hình', 'route' => '{vertical}.settings.index']],
    ],

    'is_active' => true,
]);
```

### B.2 Hai org kích hoạt vertical `mfg` — data khác nhau hoàn toàn

**Nhà máy A — Dệt may Long An** (org_id = 101)

```
ActivateVerticalAction::execute(101, VerticalRegistry::resolve('manufacturing'))
↓
Seed vertical_config_items (org=101, vertical=manufacturing):

┌─────────────────┬────────────────────────────┬──────────────┐
│ config_group    │ code                        │ label        │
├─────────────────┼────────────────────────────┼──────────────┤
│ activity_type   │ maintenance                 │ Bảo trì định kỳ    │
│ activity_type   │ inspection                  │ Kiểm tra / Kiểm định│
│ activity_type   │ calibration                 │ Hiệu chuẩn thiết bị │
│ activity_type   │ repair                      │ Sửa chữa            │
│ activity_type   │ production                  │ Vận hành sản xuất   │
│ activity_type   │ cleaning                    │ Vệ sinh công nghiệp │
│ activity_type   │ other                       │ Khác                │
└─────────────────┴────────────────────────────┴──────────────┘

Sau đó org admin tùy chỉnh qua /mfg/settings:
→ Thêm: 'thread_inspect' => 'Kiểm tra sợi nguyên liệu'
→ Thêm: 'weaving_run'    => 'Chạy thử dệt vải'
→ Thêm: 'dyeing_check'   => 'Kiểm tra màu / nhuộm'
→ Xóa:  'cleaning' (is_active = 0)
```

**Nhà máy B — Thực phẩm Sạch Hà Nội** (org_id = 202)

```
ActivateVerticalAction::execute(202, VerticalRegistry::resolve('manufacturing'))
↓
Seed vertical_config_items (org=202, vertical=manufacturing):
[giống A — cùng defaults]

Sau đó org admin tùy chỉnh:
→ Thêm: 'receive_material'  => 'Tiếp nhận nguyên liệu đầu vào'
→ Thêm: 'mixing'            => 'Phối trộn / Chế biến'
→ Thêm: 'pasteurization'    => 'Thanh trùng / Tiệt trùng'
→ Thêm: 'packaging'         => 'Đóng gói thành phẩm'
→ Thêm: 'microbio_test'     => 'Kiểm tra vi sinh'
→ Xóa:  'calibration', 'repair' (không phù hợp ngành thực phẩm)
```

### B.3 Runtime — cùng controller, cùng blade, data khác nhau

```php
// Modules/Deployment/Http/Controllers/ActivityLogController.php
public function create(DeploymentTarget $target): View
{
    $vertical = VerticalRegistry::resolve($target->vertical_code); // DatabaseVertical (manufacturing)

    // Query DB theo org của target — không query vertical class
    $activityTypes = VerticalConfigService::activityTypes(
        $target->targetOrganization->id,  // 101 hoặc 202
        $target->vertical_code,
        $vertical
    );

    return view('deployment::activity-logs.create', compact('target', 'activityTypes'));
}
```

**Kết quả trên form:**

```
Nhà máy Dệt Long An                   Thực phẩm Sạch Hà Nội
────────────────────────────           ──────────────────────────────
Loại hoạt động:                        Loại hoạt động:
  ○ Bảo trì định kỳ                      ○ Bảo trì định kỳ
  ○ Kiểm tra / Kiểm định                 ○ Kiểm tra / Kiểm định
  ○ Hiệu chuẩn thiết bị                  ○ Vận hành sản xuất
  ○ Sửa chữa                             ○ Tiếp nhận nguyên liệu đầu vào
  ○ Vận hành sản xuất                    ○ Phối trộn / Chế biến
  ○ Kiểm tra sợi nguyên liệu  ← custom  ○ Thanh trùng / Tiệt trùng   ← custom
  ○ Chạy thử dệt vải          ← custom  ○ Đóng gói thành phẩm         ← custom
  ○ Kiểm tra màu / nhuộm      ← custom  ○ Kiểm tra vi sinh            ← custom
  ○ Khác                                 ○ Khác
```

**Cùng một blade template — data khác nhau hoàn toàn do `VerticalConfigService` đọc đúng org.**

### B.4 So sánh Traceability vs Manufacturing — cùng platform, khác hoàn toàn về nghiệp vụ

| | Traceability (traceability) | Manufacturing (manufacturing) |
|-|-------------|---------------------|
| Target | HTX nông nghiệp | Nhà máy sản xuất |
| Area/Lot/Item | Khu / Lô / Đơn vị | Xưởng / Dây chuyền / Máy |
| Default activities | Tưới, Bón, Thu hoạch | Bảo trì, Hiệu chuẩn, Sản xuất |
| Export adapter | CheckVnExportAdapter (optional) | null (không cần) |
| Phases | 8 phases nông nghiệp | 8 phases nhà máy |
| Org A custom | HTX chè: Tỉa cành, hái búp | Dệt: Kiểm tra sợi, Chạy dệt |
| Org B custom | HTX gà: Cho ăn, Tiêm phòng | Thực phẩm: Thanh trùng, Vi sinh |

> Tất cả dùng **cùng 4 bảng Core** (`deployment_targets`, `deployment_checklist_items`, `deployment_issues`, `deployment_progress_logs`) + **Survey Engine** để thu thập dữ liệu + **Organization MediaLibrary** cho hồ sơ.
> Khác nhau chỉ ở `vertical_code`, `data_collection_template_slug`, `phases`, và `vertical_config_items` per-org.
> Cả hai đều là `DatabaseVertical` instances đọc từ `vertical_templates` — không có PHP class riêng.

---

## Appendix C — Thêm Vertical Template Mới

Để thêm vertical mới (ví dụ: Consulting, Education, Retail...):

**Bước 1:** Chạy seeder (hoặc tạo qua Admin UI `/admin/vertical-templates`):

```php
// database/seeders/ConsultingTemplateSeeder.php
VerticalTemplate::updateOrCreate(['code' => 'consulting'], [
    'label'                   => 'Triển khai Tư vấn',
    'target_label'            => 'Doanh nghiệp',
    'target_org_category'     => 'company',
    'has_physical_assets'     => false,  // không dùng production_* tables
    'export_adapter'          => null,
    'readiness_template_slug' => 'consulting_readiness_v1',

    'phases' => ['discovery', 'proposal', 'kick_off', 'delivery', 'review', 'completed'],

    'default_hierarchy' => [
        'site' => null, 'area' => null, 'lot' => null, 'item' => null,
        'item_plural' => null, 'item_prefix' => null,
    ],  // has_physical_assets=false → production_* tables không dùng

    'default_checklist' => [
        'discovery'  => [
            ['key' => 'stakeholder_interview', 'label' => 'Phỏng vấn stakeholder', 'required' => true],
            ['key' => 'as_is_mapped',          'label' => 'Mapping quy trình hiện tại', 'required' => true],
        ],
        'proposal'   => [
            ['key' => 'sow_drafted',  'label' => 'Draft SOW', 'required' => true],
            ['key' => 'sow_signed',   'label' => 'Ký SOW',    'required' => true],
        ],
        // ...
    ],

    'default_activity_types' => null,   // không dùng activity log
    'default_legal_doc_types' => [
        'contract' => 'Hợp đồng dịch vụ',
        'nda'      => 'NDA',
        'sow'      => 'Statement of Work',
        'other'    => 'Khác',
    ],

    'default_roles'  => ['pm', 'analyst', 'trainer'],
    'sidebar_config' => [
        'TRIỂN KHAI'  => [
            ['label' => 'Dashboard', 'route' => '{vertical}.dashboard'],
            ['label' => 'Dự án',     'route' => '{vertical}.projects.index'],
            ['label' => '{target}',  'route' => '{vertical}.targets.index'],
        ],
        'CÔNG VIỆC'   => [
            ['label' => 'Công việc', 'route' => '{vertical}.tasks.index'],
            ['label' => 'Issues',    'route' => '{vertical}.issues.index'],
            ['label' => 'Bàn giao',  'route' => '{vertical}.handover.index'],
        ],
        'BÁO CÁO'     => [['label' => 'Báo cáo', 'route' => '{vertical}.reports.index']],
        'CẤU HÌNH'    => [['label' => 'Cấu hình', 'route' => '{vertical}.settings.index']],
    ],
    'is_active'      => true,
]);
```

**Bước 2:** `php artisan db:seed --class=ConsultingTemplateSeeder`

**Bước 3:** Nếu vertical cần export file riêng: viết class implement `ExportAdapterInterface`, đặt tên FQCN vào field `export_adapter`.

**Bước 4:** Nếu vertical cần sandbox scenarios: seed vào Sandbox module với `vertical_code = 'consulting'`.

**Không cần làm gì khác** — Deployment Engine, routes, controllers, middleware, CRUD đều tái sử dụng nguyên vẹn.

---

## Migration Checklist

Thứ tự chạy migration:

```
Sprint 0:
  1. create_vertical_templates_table               ← platform-level, không tenant-scoped
  2. create_organization_verticals_table
  3. create_vertical_config_items_table       ← per-org enum config (activity_type, doc_type, item_type)
  4. alter_projects_add_project_type
  5. create_deployment_targets_table          ← chỉ có target_organization_id, không có client_*
  6. create_deployment_checklist_items_table
  7. create_deployment_issues_table
  8. create_deployment_progress_logs_table

  Không cần migration cho organizations — bảng đã có: name, tax_code, phone, email,
  province_code, ward_code, full_address, industry. Thêm source='vertical_created'
  để phân biệt org do vertical tạo vs org tenant tự đăng ký.

Sprint 4 (Survey-based data collection — dùng cho mọi vertical):
  9. add_data_collection_template_slug_to_vertical_templates  ← ALTER TABLE thêm column
  (Không có migration bảng mới — dữ liệu lưu trong survey_answers + media đã có sẵn)

  Survey seeders:
  - DataCollectionV1Seeder (survey template 4 sections + 20 fields)
  - UpdateTraceabilityTemplateSeeder (set has_physical_assets=false, phases mới, data_collection_template_slug)
```

---

> **Tài liệu này thay thế:** `docs/PLATFORM_DESIGN.md` (architecture) + `docs/verticals/TXNG_IMPL_SPEC.md` (TXNG spec)
> Khi thêm vertical mới: seed một row vào `vertical_templates` theo Appendix C — không viết PHP class, không sửa file này.
