# Customer Module Specification
> **Module:** `Modules/Customer` · **Feature flag:** `module.crm` · **Plans:** Growth, Professional, Enterprise
> **Status:** Design — ready to implement · **Date:** June 2026

---

## 1. Tổng quan

Module **Customer** quản lý tập trung tất cả khách hàng đã chuyển đổi của một tổ chức (`organization_id`), bất kể đó là cá nhân hay doanh nghiệp. Một entity duy nhất `Customer`, phân biệt loại hình qua `customer_type`.

### So sánh với Lead module

| Khía cạnh | Lead | Customer |
|-----------|------|----------|
| Mục đích | Pipeline bán hàng — prospecting | Hồ sơ khách hàng đã mua / quan hệ lâu dài |
| Entity | `Lead` (1 opportunity) | `Customer` (1 khách hàng) |
| Contact info | `LeadContact` — dedup nhúng trong Lead | Trực tiếp trên `Customer` |
| Công ty | `contact_company` string field | `customer_type = business` + `company_name` |
| Custom fields | `LeadMeta` key-value store | `CustomerFieldDefinition` (admin) + `CustomerMeta` |
| Activities | `LeadActivity` — gắn vào Lead | `CustomerActivity` — gắn vào Customer |

### Luồng Lead → Customer

```
Lead (status → Converted)
    └─► ConvertLeadToCustomerAction
            ├─ Tìm Customer theo dedup_hash (email / phone)
            ├─ Nếu chưa có → tạo mới từ Lead data
            ├─ Gán Lead.customer_id
            └─ Set Customer.lifecycle_stage = Active
```

---

## 2. Domain Models & Database Schema

### 2.1 customers

```
id                      bigint PK
uuid                    char(36) unique
organization_id         bigint FK → organizations       [TenantAware global scope]

customer_type           tinyint  NOT NULL               ← enum: Individual=1, Business=2

-- Chung cả 2 loại
display_name            varchar(255) NOT NULL           ← tên người / tên giao dịch công ty
primary_email           varchar(255) nullable
primary_phone           varchar(30)  nullable
province_code           varchar(10)  nullable
full_address            varchar(500) nullable
website                 varchar(500) nullable
description             text         nullable
avatar_url              varchar(500) nullable

-- Phân loại & quản lý
lifecycle_stage         tinyint default 1               ← enum: Prospect=1,Active=2,VIP=3,Inactive=4,Churned=5
source_id               bigint FK → lead_sources nullable
assigned_to             bigint FK → users nullable
last_activity_at        datetime nullable
activity_count          int default 0

-- Chỉ Individual (customer_type = 1)
first_name              varchar(100) nullable
last_name               varchar(100) nullable
gender                  tinyint nullable                ← enum: Male=1, Female=2, Other=3
date_of_birth           date nullable

-- Chỉ Business (customer_type = 2)
company_name            varchar(255) nullable           ← tên pháp lý đầy đủ
tax_code                varchar(50)  nullable
industry                varchar(100) nullable
company_size            tinyint nullable                ← enum: Micro=1..Enterprise=5
representative_name     varchar(255) nullable           ← người đại diện liên hệ
representative_title    varchar(150) nullable

-- Truy vết
dedup_hash              char(32) nullable               ← MD5(normalize(email|phone))
converted_from_lead_id  bigint FK → leads nullable
created_by              bigint FK → users nullable
updated_by              bigint FK → users nullable
created_at, updated_at, deleted_at

INDEXES:
  uq_customer_org_dedup   (organization_id, dedup_hash)
  idx_customer_email      (organization_id, primary_email)
  idx_customer_phone      (organization_id, primary_phone)
  idx_customer_name       (organization_id, display_name)           -- prefix 100
  idx_customer_list       (organization_id, lifecycle_stage, customer_type, assigned_to)
  idx_customer_activity   (organization_id, last_activity_at)
  idx_customer_source     (organization_id, source_id)
  idx_customer_province   (organization_id, province_code)
```

### 2.2 customer_field_definitions  ← Admin tự định nghĩa custom fields per org

```
id
organization_id         bigint FK → organizations
field_key               varchar(100)                   ← machine key: "zalo_id", "contract_number"
label                   varchar(255)                   ← hiển thị trên form
value_type              tinyint                        ← enum: String=1,Integer=2,Decimal=3,Boolean=4,Date=5
is_required             boolean default false
default_value           varchar(500) nullable
placeholder             varchar(255) nullable
sort_order              smallint default 0
applies_to              tinyint default 0              ← 0=Both, 1=Individual only, 2=Business only
is_active               boolean default true
created_at, updated_at

UNIQUE: (organization_id, field_key)
INDEX:  (organization_id, applies_to, is_active, sort_order)
```

> Admin vào **CRM → Cài đặt trường tùy chỉnh** → định nghĩa fields. Form Create/Edit tự động render thêm section dựa trên danh sách này.

### 2.3 customer_meta  ← Giá trị custom fields per customer

```
id
customer_id             bigint FK → customers (cascade delete)
definition_id           bigint FK → customer_field_definitions
val_string              varchar(1000) nullable
val_integer             bigint nullable
val_decimal             decimal(18,4) nullable
val_boolean             boolean nullable
val_date                date nullable
created_at, updated_at

UNIQUE: (customer_id, definition_id)
INDEX:  (customer_id)
INDEX:  (definition_id, val_string)                    ← search custom field value
```

### 2.4 customer_activities

```
id
organization_id         bigint
customer_id             bigint FK → customers
lead_id                 bigint FK → leads nullable
type                    tinyint                        ← enum: Call=1,Email=2,Meeting=3,Note=4,Task=5,Other=6
title                   varchar(255) NOT NULL
description             text nullable
outcome                 varchar(500) nullable
scheduled_at            datetime nullable
completed_at            datetime nullable
duration_minutes        int nullable
actor_id                bigint nullable
actor_name              varchar(255) nullable          ← snapshot
created_at              datetime                       ← manual, NO timestamps()

INDEXES:
  idx_ca_customer   (customer_id, created_at)
  idx_ca_org_type   (organization_id, type, created_at)
```

### 2.5 customer_notes

```
id, organization_id
customer_id             bigint FK → customers
content                 text
is_pinned               boolean default false
author_id               bigint nullable
author_name             varchar(255) nullable
created_at, updated_at, deleted_at

INDEX: (customer_id, is_pinned, created_at)
```

### 2.6 customer_tags / customer_tag_map

```
customer_tags:    id, organization_id, name varchar(100), color varchar(20)
  UNIQUE: (organization_id, name)

customer_tag_map: customer_id, tag_id
  PK: (customer_id, tag_id)
```

### 2.7 Thêm customer_id vào leads (Phase 2)

```
ALTER TABLE leads ADD COLUMN customer_id bigint nullable REFERENCES customers(id) ON DELETE SET NULL;
INDEX: (organization_id, customer_id)
```

---

## 3. Enums

```php
// Modules/Customer/app/Enums/CustomerType.php
enum CustomerType: int {
    case Individual = 1;  // "Cá nhân"
    case Business   = 2;  // "Doanh nghiệp"
    public function label(): string { ... }
    public function badgeClass(): string { ... }
}

// Modules/Customer/app/Enums/CustomerLifecycleStage.php
enum CustomerLifecycleStage: int {
    case Prospect = 1;  // "Tiềm năng"
    case Active   = 2;  // "Đang hoạt động"
    case VIP      = 3;  // "VIP"
    case Inactive = 4;  // "Không hoạt động"
    case Churned  = 5;  // "Đã rời bỏ"
    public function label(): string { ... }
    public function badgeClass(): string { ... }
}

// Modules/Customer/app/Enums/CustomerActivityType.php
enum CustomerActivityType: int {
    case Call    = 1;
    case Email   = 2;
    case Meeting = 3;
    case Note    = 4;
    case Task    = 5;
    case Other   = 6;
    public function label(): string { ... }
    public function icon(): string { ... }
}

// Modules/Customer/app/Enums/MetaValueType.php  ← giống LeadMeta
enum MetaValueType: int {
    case String   = 1;
    case Integer  = 2;
    case Decimal  = 3;
    case Boolean  = 4;
    case Date     = 5;
}

// Modules/Customer/app/Enums/CompanySize.php
enum CompanySize: int {
    case Micro      = 1;  // < 10
    case Small      = 2;  // 10–50
    case Medium     = 3;  // 50–250
    case Large      = 4;  // 250–1000
    case Enterprise = 5;  // 1000+
}
```

---

## 4. Kiến trúc AVSA — Cấu trúc thư mục

Pattern theo Lead module (flat directories, không sub-Features):

```
Modules/Customer/
├── app/
│   ├── Actions/                        ← Lorisleiva AsAction (command side)
│   │   ├── CreateCustomerAction.php
│   │   ├── UpdateCustomerAction.php
│   │   ├── DeleteCustomerAction.php
│   │   ├── LogActivityAction.php
│   │   ├── SyncCustomerTagsAction.php
│   │   ├── SyncCustomerMetaAction.php  ← upsert custom field values
│   │   ├── Notes/
│   │   │   ├── StoreNoteAction.php
│   │   │   ├── UpdateNoteAction.php
│   │   │   ├── DestroyNoteAction.php
│   │   │   └── TogglePinNoteAction.php
│   │   └── Conversion/
│   │       └── ConvertLeadToCustomerAction.php
│   │
│   ├── Data/
│   │   └── Requests/
│   │       ├── StoreCustomerData.php   ← Spatie Data DTO + validation
│   │       ├── UpdateCustomerData.php
│   │       ├── StoreActivityData.php
│   │       └── StoreNoteData.php
│   │
│   ├── Enums/
│   │   ├── CustomerType.php
│   │   ├── CustomerLifecycleStage.php
│   │   ├── CustomerActivityType.php
│   │   ├── MetaValueType.php
│   │   └── CompanySize.php
│   │
│   ├── Events/
│   │   ├── CustomerCreated.php         ← payload: Customer $customer
│   │   ├── CustomerUpdated.php
│   │   └── CustomerConverted.php      ← payload: Customer + Lead
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── CustomerController.php
│   │   │   ├── CustomerFieldDefinitionController.php   ← admin config
│   │   │   └── Api/
│   │   │       ├── CustomerApiController.php           ← Tabulator JSON
│   │   │       ├── CustomerActivityApiController.php
│   │   │       └── CustomerNoteApiController.php
│   │   └── Resources/
│   │       ├── CustomerListResource.php    ← JsonResource cho Tabulator
│   │       └── CustomerDetailResource.php
│   │
│   ├── Listeners/
│   │   ├── LogCustomerCreated.php
│   │   └── LogCustomerUpdated.php
│   │
│   ├── Models/
│   │   ├── Customer.php
│   │   ├── CustomerActivity.php
│   │   ├── CustomerNote.php
│   │   ├── CustomerTag.php
│   │   ├── CustomerMeta.php
│   │   └── CustomerFieldDefinition.php
│   │
│   ├── Observers/
│   │   └── CustomerObserver.php        ← extends BaseModelObserver
│   │
│   ├── Policies/
│   │   └── CustomerPolicy.php
│   │
│   ├── Providers/
│   │   ├── CustomerServiceProvider.php
│   │   ├── EventServiceProvider.php
│   │   └── RouteServiceProvider.php
│   │
│   └── Queries/                        ← CQRS read side
│       ├── ListCustomersQuery.php
│       ├── ListCustomersHandler.php
│       ├── GetCustomerQuery.php
│       ├── GetCustomerHandler.php
│       ├── ListCustomerActivitiesQuery.php
│       └── ListCustomerActivitiesHandler.php
│
├── config/customer.php
├── database/
│   ├── migrations/
│   └── seeders/CustomerDatabaseSeeder.php
├── resources/views/
│   ├── index.blade.php
│   ├── show.blade.php
│   └── _form.blade.php
└── routes/
    ├── web.php
    └── api.php
```

---

## 5. CQRS-lite — Query / Handler Pattern

```php
// Queries/ListCustomersQuery.php
class ListCustomersQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $organizationId,
        public readonly int     $page       = 1,
        public readonly int     $perPage    = 25,
        public readonly string  $sortField  = 'created_at',
        public readonly string  $sortDir    = 'desc',
        public readonly ?string $search     = null,       // display_name / email / phone
        public readonly ?int    $type       = null,       // CustomerType
        public readonly ?int    $stage      = null,       // CustomerLifecycleStage
        public readonly ?int    $sourceId   = null,
        public readonly ?int    $assignedTo = null,
        public readonly ?string $province   = null,
        public readonly ?int    $tagId      = null,
        public readonly ?string $dateFrom   = null,
        public readonly ?string $dateTo     = null,
        public readonly bool    $forExport  = false,
    ) {}
}

// Queries/ListCustomersHandler.php
class ListCustomersHandler implements QueryHandlerInterface
{
    private const SORTABLE = [
        'display_name', 'lifecycle_stage', 'last_activity_at', 'created_at',
    ];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListCustomersQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE)
            ? $query->sortField : 'created_at';

        $q = Customer::query()
            ->select('customers.*')
            ->with(['source:id,label,icon', 'assignee:id,name', 'tags:id,name,color']);

        if ($query->search) {
            $term = '%' . $query->search . '%';
            $q->where(fn ($w) => $w
                ->where('display_name', 'like', $term)
                ->orWhere('primary_email', 'like', $term)
                ->orWhere('primary_phone', 'like', $term)
                ->orWhere('company_name', 'like', $term)
            );
        }

        if ($query->type)       $q->where('customer_type', $query->type);
        if ($query->stage)      $q->where('lifecycle_stage', $query->stage);
        if ($query->sourceId)   $q->where('source_id', $query->sourceId);
        if ($query->assignedTo) $q->where('assigned_to', $query->assignedTo);
        if ($query->province)   $q->where('province_code', $query->province);
        if ($query->dateFrom)   $q->whereDate('created_at', '>=', $query->dateFrom);
        if ($query->dateTo)     $q->whereDate('created_at', '<=', $query->dateTo);

        if ($query->tagId) {
            $q->whereHas('tags', fn ($t) => $t->where('customer_tags.id', $query->tagId));
        }

        $q->orderBy($sortField, $query->sortDir);

        return $q->paginate($query->perPage, page: $query->page);
    }
}

// Queries/GetCustomerQuery.php
class GetCustomerQuery implements QueryInterface
{
    public function __construct(public readonly Customer $customer) {}
}

// Queries/GetCustomerHandler.php
class GetCustomerHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Customer
    {
        /** @var GetCustomerQuery $query */
        $query->customer->load([
            'source:id,label,icon',
            'assignee:id,name',
            'tags',
            'meta.definition',                                      // custom fields
            'notes'      => fn ($q) => $q->orderByDesc('is_pinned')->orderByDesc('created_at'),
            'activities' => fn ($q) => $q->orderByDesc('created_at')->limit(50),
            'leads:id,title,stage_id,status,expected_value,created_at',  // cross-module
        ]);

        return $query->customer;
    }
}
```

---

## 6. Actions (Write Side) — AsAction Pattern

```php
// Actions/CreateCustomerAction.php
class CreateCustomerAction
{
    use AsAction;

    public function handle(StoreCustomerData $data, int $orgId): Customer
    {
        $hash = $this->dedupHash($data->primary_email, $data->primary_phone);

        // Dedup check — không tạo trùng
        if ($hash) {
            $existing = Customer::where('organization_id', $orgId)
                ->where('dedup_hash', $hash)->first();
            if ($existing) {
                return $existing;
            }
        }

        $customer = DB::transaction(function () use ($data, $orgId, $hash): Customer {
            $customer = Customer::create([
                'organization_id'      => $orgId,
                'customer_type'        => $data->customer_type,
                'display_name'         => $data->display_name,
                'primary_email'        => $data->primary_email,
                'primary_phone'        => $data->primary_phone,
                'lifecycle_stage'      => $data->lifecycle_stage ?? CustomerLifecycleStage::Active,
                'source_id'            => $data->source_id,
                'assigned_to'          => $data->assigned_to,
                // Individual fields
                'first_name'           => $data->first_name,
                'last_name'            => $data->last_name,
                'gender'               => $data->gender,
                'date_of_birth'        => $data->date_of_birth,
                // Business fields
                'company_name'         => $data->company_name,
                'tax_code'             => $data->tax_code,
                'industry'             => $data->industry,
                'company_size'         => $data->company_size,
                'representative_name'  => $data->representative_name,
                'representative_title' => $data->representative_title,
                // Common
                'province_code'        => $data->province_code,
                'full_address'         => $data->full_address,
                'website'              => $data->website,
                'description'          => $data->description,
                'dedup_hash'           => $hash,
                'created_by'           => Auth::id(),
            ]);

            // Sync tags
            if (!empty($data->tag_ids)) {
                SyncCustomerTagsAction::run($customer, $data->tag_ids);
            }

            // Sync custom field values
            if (!empty($data->meta)) {
                SyncCustomerMetaAction::run($customer, $data->meta);
            }

            return $customer;
        });

        event(new CustomerCreated($customer));

        return $customer;
    }

    private function dedupHash(?string $email, ?string $phone): ?string
    {
        $key = strtolower(trim($email ?? ''))
            ?: preg_replace('/\D/', '', $phone ?? '');

        return $key ? md5($key) : null;
    }
}

// Actions/SyncCustomerMetaAction.php
class SyncCustomerMetaAction
{
    use AsAction;

    // $values = ['definition_id' => scalar_value, ...]
    public function handle(Customer $customer, array $values): void
    {
        foreach ($values as $definitionId => $rawValue) {
            $def = CustomerFieldDefinition::find($definitionId);
            if (!$def || !$def->is_active) continue;

            $payload = match ($def->value_type) {
                MetaValueType::Integer => ['val_integer' => (int) $rawValue],
                MetaValueType::Decimal => ['val_decimal' => (float) $rawValue],
                MetaValueType::Boolean => ['val_boolean' => (bool) $rawValue],
                MetaValueType::Date    => ['val_date'    => $rawValue],
                default                => ['val_string'  => (string) $rawValue],
            };

            CustomerMeta::updateOrCreate(
                ['customer_id' => $customer->id, 'definition_id' => $definitionId],
                $payload
            );
        }
    }
}
```

---

## 7. Data DTOs (Spatie Laravel Data)

```php
// Data/Requests/StoreCustomerData.php
class StoreCustomerData extends Data
{
    public function __construct(
        #[Required, IntegerType, Min(1)]
        public readonly int $customer_type,

        #[Required, StringType, Max(255)]
        public readonly string $display_name,

        #[Nullable, Email, Max(255)]
        public readonly ?string $primary_email = null,

        #[Nullable, StringType, Max(30)]
        public readonly ?string $primary_phone = null,

        #[Nullable, IntegerType]
        public readonly ?int $lifecycle_stage = null,

        #[Nullable, IntegerType]
        public readonly ?int $source_id = null,

        #[Nullable, IntegerType]
        public readonly ?int $assigned_to = null,

        // Individual
        #[Nullable, StringType, Max(100)]
        public readonly ?string $first_name = null,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $last_name = null,

        #[Nullable, IntegerType]
        public readonly ?int $gender = null,

        #[Nullable, Date]
        public readonly ?string $date_of_birth = null,

        // Business
        #[Nullable, StringType, Max(255)]
        public readonly ?string $company_name = null,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $tax_code = null,

        #[Nullable, StringType, Max(100)]
        public readonly ?string $industry = null,

        #[Nullable, IntegerType]
        public readonly ?int $company_size = null,

        #[Nullable, StringType, Max(255)]
        public readonly ?string $representative_name = null,

        #[Nullable, StringType, Max(150)]
        public readonly ?string $representative_title = null,

        // Common
        #[Nullable, StringType, Max(10)]
        public readonly ?string $province_code = null,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $full_address = null,

        #[Nullable, Url, Max(500)]
        public readonly ?string $website = null,

        #[Nullable, StringType]
        public readonly ?string $description = null,

        // Tags + custom meta
        #[Nullable, ArrayType]
        public readonly ?array $tag_ids = null,

        // ['definition_id' => value, ...] — từ custom field form inputs
        #[Nullable, ArrayType]
        public readonly ?array $meta = null,
    ) {}

    public static function rules(): array
    {
        return [
            'customer_type'    => ['required', 'integer', Rule::in(array_column(CustomerType::cases(), 'value'))],
            'lifecycle_stage'  => ['nullable', 'integer', Rule::in(array_column(CustomerLifecycleStage::cases(), 'value'))],
            'source_id'        => ['nullable', 'integer', 'exists:lead_sources,id'],
            'assigned_to'      => ['nullable', 'integer', 'exists:users,id'],
            'tag_ids'          => ['nullable', 'array'],
            'tag_ids.*'        => ['integer', 'min:1'],
            'meta'             => ['nullable', 'array'],
        ];
    }
}
```

---

## 8. API Resource (Tabulator serialization)

```php
// Http/Resources/CustomerListResource.php
class CustomerListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'display_name'     => $this->display_name,
            'customer_type'    => $this->customer_type->value,
            'type_label'       => $this->customer_type->label(),
            'type_badge'       => $this->customer_type->badgeClass(),
            'primary_email'    => $this->primary_email,
            'primary_phone'    => $this->primary_phone,
            'company_name'     => $this->company_name,
            'stage_value'      => $this->lifecycle_stage->value,
            'stage_label'      => $this->lifecycle_stage->label(),
            'stage_badge'      => $this->lifecycle_stage->badgeClass(),
            'source_label'     => $this->source?->label,
            'assignee_name'    => $this->assignee?->name,
            'province_code'    => $this->province_code,
            'last_activity_at' => $this->last_activity_at?->format('d/m/Y'),
            'activity_count'   => $this->activity_count,
            'tags'             => $this->whenLoaded('tags', fn () =>
                $this->tags->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'color' => $t->color])
            ),
            'created_at'       => $this->created_at->format('d/m/Y'),
            'show_url'         => route('customers.show', $this->resource),
            'edit_url'         => route('customers.edit', $this->resource),
            'delete_url'       => route('customers.destroy', $this->resource),
        ];
    }
}
```

---

## 9. Controller Pattern

```php
// Http/Controllers/CustomerController.php
class CustomerController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Customer::class);

        // Data cho filter dropdowns — nhỏ, cache ổn
        $sources = Cache::remember("lead_sources_{$orgId}", 600, fn () =>
            LeadSource::active()->orderBy('sort_order')->get(['id', 'label', 'icon'])
        );

        $tags = CustomerTag::orderBy('name')->get(['id', 'name', 'color']);

        return view('customer::index', compact('sources', 'tags'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Customer::class);

        $data = StoreCustomerData::validateAndCreate($request->all());
        $customer = CreateCustomerAction::run($data, TenantContext::getOrganizationId());

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Đã tạo khách hàng.');
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);

        $customer = app(GetCustomerHandler::class)
            ->handle(new GetCustomerQuery($customer));

        // Custom field definitions để render tab "Thông tin"
        $fieldDefs = CustomerFieldDefinition::where('organization_id', $customer->organization_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('customer::show', compact('customer', 'fieldDefs'));
    }
}
```

---

## 10. Form — flexible custom fields

### Blade view `_form.blade.php`

```blade
<div x-data="{ type: '{{ old('customer_type', $customer->customer_type->value ?? 1) }}' }">

    {{-- Type switch --}}
    <div class="form-control">
        <label class="label"><span class="label-text">Loại hình *</span></label>
        <div class="flex gap-4">
            <label class="label cursor-pointer gap-2">
                <input type="radio" name="customer_type" value="1" x-model="type" class="radio radio-primary">
                <span class="label-text">Cá nhân</span>
            </label>
            <label class="label cursor-pointer gap-2">
                <input type="radio" name="customer_type" value="2" x-model="type" class="radio radio-primary">
                <span class="label-text">Doanh nghiệp</span>
            </label>
        </div>
    </div>

    {{-- Thông tin chung --}}
    ...

    {{-- Individual only --}}
    <div x-show="type == '1'" x-cloak>
        {{-- first_name, last_name, gender, date_of_birth --}}
    </div>

    {{-- Business only --}}
    <div x-show="type == '2'" x-cloak>
        {{-- company_name, tax_code, industry, company_size, representative --}}
    </div>

    {{-- Custom fields — render từ CustomerFieldDefinition --}}
    @if($fieldDefs->isNotEmpty())
    <div class="divider">Thông tin bổ sung</div>
    @foreach($fieldDefs as $def)
        @if($def->applies_to === 0 || $def->applies_to == (old('customer_type', $customer->customer_type->value ?? 1)))
        <div class="form-control" @if($def->applies_to !== 0) x-show="type == '{{ $def->applies_to }}'" x-cloak @endif>
            <label class="label">
                <span class="label-text">{{ $def->label }}{{ $def->is_required ? ' *' : '' }}</span>
            </label>
            @switch($def->value_type)
                @case(1) {{-- String --}}
                    <input type="text" name="meta[{{ $def->id }}]"
                           value="{{ old('meta.'.$def->id, $customer->meta->firstWhere('definition_id', $def->id)?->val_string) }}"
                           placeholder="{{ $def->placeholder }}"
                           class="input input-bordered" @if($def->is_required) required @endif>
                    @break
                @case(4) {{-- Boolean --}}
                    <input type="checkbox" name="meta[{{ $def->id }}]" value="1"
                           class="checkbox" @checked(old('meta.'.$def->id, $customer->meta->firstWhere('definition_id', $def->id)?->val_boolean))>
                    @break
                @case(5) {{-- Date --}}
                    <input type="date" name="meta[{{ $def->id }}]"
                           value="{{ old('meta.'.$def->id, $customer->meta->firstWhere('definition_id', $def->id)?->val_date?->format('Y-m-d')) }}"
                           class="input input-bordered">
                    @break
                @default
                    <input type="{{ $def->value_type === 2 || $def->value_type === 3 ? 'number' : 'text' }}"
                           name="meta[{{ $def->id }}]"
                           value="{{ old('meta.'.$def->id, $customer->meta->firstWhere('definition_id', $def->id)?->getValue()) }}"
                           class="input input-bordered">
            @endswitch
        </div>
        @endif
    @endforeach
    @endif

</div>
```

---

## 11. RBAC

### PermissionEnum — thêm mới

```php
// ══ CUSTOMERS ══════════════════════════════════════════════════════════
// CEO=Full | Sales=Assigned | Ops=View+Edit | Marketing=View | Admin=Config
case CUSTOMERS_VIEW_ALL      = 'customers.view_all';
case CUSTOMERS_VIEW_ASSIGNED = 'customers.view_assigned';
case CUSTOMERS_CREATE        = 'customers.create';
case CUSTOMERS_EDIT          = 'customers.edit';
case CUSTOMERS_DELETE        = 'customers.delete';
case CUSTOMERS_EXPORT        = 'customers.export';
case CUSTOMERS_CONFIG        = 'customers.config';   // quản lý tags, field definitions
```

### config/permissions.php

```php
'customers' => [
    'sidebar_label' => 'Khách hàng',
    'feature_gate'  => 'module.crm',
    'roles' => [
        'CEO'       => [P::CUSTOMERS_VIEW_ALL, P::CUSTOMERS_CREATE, P::CUSTOMERS_EDIT,
                        P::CUSTOMERS_DELETE, P::CUSTOMERS_EXPORT],
        'SALES'     => [P::CUSTOMERS_VIEW_ASSIGNED, P::CUSTOMERS_CREATE, P::CUSTOMERS_EDIT],
        'OPS'       => [P::CUSTOMERS_VIEW_ALL, P::CUSTOMERS_CREATE, P::CUSTOMERS_EDIT,
                        P::CUSTOMERS_EXPORT],
        'MARKETING' => [P::CUSTOMERS_VIEW_ALL],
        'ADMIN'     => [P::CUSTOMERS_CONFIG],
    ],
],
```

---

## 12. Routes

```php
// routes/web.php
Route::middleware(['web', 'auth'])
    ->prefix('customers')
    ->name('customers.')
    ->group(function () {

    Route::get('/',               [CustomerController::class, 'index'])->name('index')
         ->middleware('can:customers.view_all|customers.view_assigned');
    Route::get('/create',         [CustomerController::class, 'create'])->name('create')
         ->middleware('can:customers.create');
    Route::post('/',              [CustomerController::class, 'store'])->name('store')
         ->middleware('can:customers.create');
    Route::get('/{customer}',     [CustomerController::class, 'show'])->name('show');
    Route::get('/{customer}/edit',[CustomerController::class, 'edit'])->name('edit')
         ->middleware('can:customers.edit');
    Route::put('/{customer}',     [CustomerController::class, 'update'])->name('update')
         ->middleware('can:customers.edit');
    Route::delete('/{customer}',  [CustomerController::class, 'destroy'])->name('destroy')
         ->middleware('can:customers.delete');

    // Activities (AJAX — trả về JSON cho timeline)
    Route::post('/{customer}/activities',    [CustomerActivityApiController::class, 'store'])->name('activities.store');
    Route::delete('/activities/{activity}',  [CustomerActivityApiController::class, 'destroy'])->name('activities.destroy');

    // Notes (AJAX)
    Route::post('/{customer}/notes',         [CustomerNoteApiController::class, 'store'])->name('notes.store');
    Route::put('/notes/{note}',              [CustomerNoteApiController::class, 'update'])->name('notes.update');
    Route::delete('/notes/{note}',           [CustomerNoteApiController::class, 'destroy'])->name('notes.destroy');
    Route::post('/notes/{note}/toggle-pin',  [CustomerNoteApiController::class, 'togglePin'])->name('notes.toggle-pin');

    // Admin: custom field definitions
    Route::prefix('admin/fields')
         ->middleware('can:customers.config')
         ->name('admin.fields.')
         ->group(function () {
        Route::get('/',           [CustomerFieldDefinitionController::class, 'index'])->name('index');
        Route::post('/',          [CustomerFieldDefinitionController::class, 'store'])->name('store');
        Route::put('/{def}',      [CustomerFieldDefinitionController::class, 'update'])->name('update');
        Route::delete('/{def}',   [CustomerFieldDefinitionController::class, 'destroy'])->name('destroy');
        Route::post('/reorder',   [CustomerFieldDefinitionController::class, 'reorder'])->name('reorder');
    });
});

// routes/api.php — Tabulator + TomSelect
Route::middleware(['web', 'auth'])
    ->prefix('backend/api/customers')
    ->name('backend.customers.')
    ->group(function () {
    Route::get('/',       [CustomerApiController::class, 'index'])->name('index');
    Route::get('/search', [CustomerApiController::class, 'search'])->name('search');
});
```

---

## 13. ServiceProvider

```php
// Providers/CustomerServiceProvider.php
class CustomerServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'Customer';
    protected string $nameLower = 'customer';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Customer::class, CustomerPolicy::class);
        Customer::observe(CustomerObserver::class);
    }
}

// Providers/EventServiceProvider.php
protected $listen = [
    CustomerCreated::class => [LogCustomerCreated::class],
    CustomerUpdated::class => [LogCustomerUpdated::class],
];
```

---

## 14. Kế hoạch triển khai

### Phase 1 — Customer CRUD + Custom Fields *(~4–5 ngày)*

1. `php artisan module:make Customer`
2. Migrations: `customers`, `customer_field_definitions`, `customer_meta`, `customer_tags`, `customer_tag_map`
3. Models + Enums
4. CQRS: `ListCustomersQuery/Handler`, `GetCustomerQuery/Handler`
5. Data DTOs: `StoreCustomerData`, `UpdateCustomerData`
6. Actions: `Create`, `Update`, `Delete`, `SyncTags`, `SyncMeta`
7. Resources: `CustomerListResource`
8. Controllers: `CustomerController`, `CustomerApiController`, `CustomerFieldDefinitionController`
9. Views: `index.blade.php` (Tabulator), `show.blade.php` (4 tabs), `_form.blade.php` (Alpine type-switch + meta render)
10. Routes + Policies + ServiceProvider
11. PermissionEnum + config/permissions.php
12. Sidebar entry với `@canFeature('module.crm')`

### Phase 2 — Lead Integration *(~1–2 ngày)*

1. Migration: `add_customer_id_to_leads`
2. `ConvertLeadToCustomerAction`
3. Hook trong Lead: `ChangeLeadStageAction` → Converted → dispatch action
4. Lead detail: card "Khách hàng" + link
5. Customer detail tab "Leads"

### Phase 3 — Activities & Notes *(~1–2 ngày)*

1. Migrations: `customer_activities`, `customer_notes`
2. Models + Actions (Note CRUD, LogActivity)
3. API controllers (AJAX endpoints)
4. Timeline + Notes tabs trong `show.blade.php`

### Phase 4 — Migrate từ LeadContact *(~1 ngày)*

```bash
php artisan customer:migrate-from-leads --dry-run
php artisan customer:migrate-from-leads
```

---

## 15. Checklist

- [ ] `php artisan module:make Customer`
- [ ] Migrations + migrate
- [ ] Models (TenantAware + SoftDeletes + LogsActivity cho Customer)
- [ ] Enums (CustomerType, CustomerLifecycleStage, CustomerActivityType, MetaValueType, CompanySize)
- [ ] Queries/Handlers với covering indexes
- [ ] Data DTOs với Spatie Laravel Data
- [ ] Actions với AsAction + dedup logic
- [ ] Resources (CustomerListResource)
- [ ] Controllers + Policies
- [ ] Views theo `docs/form-ui-spec.md` + Alpine type-switch + meta dynamic render
- [ ] Routes (web + api)
- [ ] PermissionEnum + config/permissions.php
- [ ] ServiceProvider + EventServiceProvider
- [ ] Sidebar entry
- [ ] Test: CRUD, dedup, custom fields, tenant isolation, feature gate, Lead conversion
