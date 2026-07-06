# Subscription Module — Advanced Vertical Slice Architecture Specification

> **Pattern stack:** AVSA + CQRS-lite + Laravel Modules (NWIDART 13) + Laravel Actions (lorisleiva 2.x)  
> **Package:** `laravelcm/laravel-subscriptions ^1.8`  
> **Subscriber unit:** `Organization` (tenant root)  
> **Spec version:** 3.0 — 2026-06-09  
> **Reference module:** `Modules/Lead` (119 files, most complete)

---

## 1. Architecture Principles

### 1.1 Pattern Comparison

| Pattern | Existing (Lead module) | Subscription module |
|---|---|---|
| **Trong-module tổ chức** | Layer-first (`Actions/`, `Queries/`, ...) | Feature-first (`Features/{Slice}/`) |
| **Write side** | `*Action` với `AsAction` trait | `*Action` với `AsAction` trait (nhất quán) |
| **Read side** | `*Query` + `*Handler` (QueryInterface) | `*Query` + `*Handler` (QueryInterface) |
| **DTOs** | `Spatie\LaravelData\Data` | `Spatie\LaravelData\Data` (nhất quán) |
| **Events** | `Dispatchable, SerializesModels` | `Dispatchable, SerializesModels` (nhất quán) |
| **Models** | Extends `TenantAwareModel` | Extends `TenantAwareModel` (custom tables) |
| **Controller** | 1 controller nhiều method | 1 controller per slice (thin, focused) |
| **Observer** | Extends `BaseModelObserver` | Extends `BaseModelObserver` (nhất quán) |
| **Authorization** | `PermissionEnum` + `Gate::policy()` | `PermissionEnum` + `Gate::policy()` (nhất quán) |
| **Cache** | Per-module (array/Redis tùy module) | **Không dùng** — 1 DB query per request |
| **Queue** | Có dùng cho async jobs | **Không dùng** — scheduled Artisan command |
| **DB JSON** | Có một số trường JSON | **Không dùng** — tất cả normalized columns |

### 1.2 AVSA — Vertical Slice

Mỗi "slice" là một tính năng hoàn chỉnh, self-contained, từ HTTP → DB:

```
Features/Subscribe/
├── SubscribeOrganizationAction.php  ← write (Command side)
├── SubscribeData.php                ← DTO + validation
├── GetActiveSubscriptionHandler.php ← read (Query side)
├── SubscriptionCreated.php          ← domain event
└── Http/SubscribeController.php     ← thin HTTP adapter
```

### 1.3 CQRS-lite

```
Write:  Action (AsAction)  →  DB::transaction  →  Domain Event  →  return
Read:   Handler            →  pure query, no side effects, no events
```

Contracts từ `app/Shared/Contracts/`: `QueryInterface`, `QueryHandlerInterface`.

### 1.4 Request lifecycle

```
Request
  │
  ├─ IdentifyOrganization (alias: tenant) → TenantContext::set($org)
  │
  ├─ CheckSubscription (NEW)              → SubscriptionContext::boot($org)
  │     load Subscription + Plan features + Overrides (1 DB query)
  │     store trong static in-process array (no Redis, no cache)
  │
  ├─ RequireFeature (NEW, alias: feature:{slug})
  │     SubscriptionContext::canUse($slug) → false → 402 upgrade wall
  │
  └─ Controller / Action
```

### 1.5 Quy tắc thiết kế

| Quy tắc | Lý do |
|---|---|
| **Không cache feature map** | Plan thay đổi hiếm (tháng 1 lần). 1 DB query per request (<1ms với eager load). Cache tăng complexity, sinh bug stale data. |
| **Không dùng queue cho subscription** | Upgrade/cancel là hành động đồng bộ — user cần biết kết quả ngay. Scheduled work dùng Artisan command, không phải Job. |
| **Không dùng JSON trong DB** | JSON không index được, không migrate được an toàn, không thể query WHERE. Dùng normalized columns thay thế. |

---

## 2. Directory Structure (AVSA)

```
Modules/Subscription/
│
├── app/
│   ├── Features/
│   │   ├── Plans/                             ← Slice: Admin quản lý plan
│   │   │   ├── Actions/
│   │   │   │   ├── CreatePlanAction.php
│   │   │   │   ├── UpdatePlanAction.php
│   │   │   │   ├── TogglePlanAction.php
│   │   │   │   └── SyncPlanFeaturesAction.php
│   │   │   ├── Queries/
│   │   │   │   ├── ListPlansQuery.php
│   │   │   │   ├── ListPlansHandler.php
│   │   │   │   ├── GetPlanQuery.php
│   │   │   │   └── GetPlanHandler.php
│   │   │   ├── Data/
│   │   │   │   ├── PlanData.php
│   │   │   │   └── PlanFeatureData.php
│   │   │   └── Http/
│   │   │       └── PlanController.php
│   │   │
│   │   ├── Subscribe/                         ← Slice: Subscribe lifecycle
│   │   │   ├── Actions/
│   │   │   │   ├── SubscribeOrganizationAction.php
│   │   │   │   └── RenewSubscriptionAction.php
│   │   │   ├── Events/
│   │   │   │   ├── SubscriptionCreated.php
│   │   │   │   └── SubscriptionRenewed.php
│   │   │   ├── Listeners/
│   │   │   │   └── AutoSubscribeOnOrgCreated.php
│   │   │   ├── Queries/
│   │   │   │   ├── GetActiveSubscriptionQuery.php
│   │   │   │   └── GetActiveSubscriptionHandler.php
│   │   │   ├── Data/
│   │   │   │   └── SubscribeData.php
│   │   │   └── Http/
│   │   │       └── SubscribeController.php
│   │   │
│   │   ├── ChangePlan/                        ← Slice: Upgrade / downgrade
│   │   │   ├── Actions/
│   │   │   │   ├── UpgradePlanAction.php
│   │   │   │   └── DowngradePlanAction.php
│   │   │   ├── Events/
│   │   │   │   └── PlanChanged.php
│   │   │   ├── Data/
│   │   │   │   └── ChangePlanData.php
│   │   │   └── Http/
│   │   │       └── ChangePlanController.php
│   │   │
│   │   ├── Cancel/                            ← Slice: Cancel / resume
│   │   │   ├── Actions/
│   │   │   │   ├── CancelSubscriptionAction.php
│   │   │   │   └── ResumeSubscriptionAction.php
│   │   │   ├── Events/
│   │   │   │   ├── SubscriptionCanceled.php
│   │   │   │   └── SubscriptionResumed.php
│   │   │   └── Http/
│   │   │       └── CancelController.php
│   │   │
│   │   ├── FeatureGate/                       ← Slice: Feature check engine
│   │   │   ├── Support/
│   │   │   │   └── SubscriptionContext.php
│   │   │   ├── Queries/
│   │   │   │   ├── GetFeatureMapQuery.php
│   │   │   │   └── GetFeatureMapHandler.php
│   │   │   ├── Actions/
│   │   │   │   ├── RecordFeatureUsageAction.php
│   │   │   │   └── OverrideFeatureAction.php
│   │   │   └── Http/Middleware/
│   │   │       ├── CheckSubscription.php
│   │   │       └── RequireFeature.php
│   │   │
│   │   ├── Billing/                           ← Slice: Invoices & payments
│   │   │   ├── Actions/
│   │   │   │   ├── GenerateInvoiceAction.php
│   │   │   │   ├── MarkInvoicePaidAction.php
│   │   │   │   └── VoidInvoiceAction.php
│   │   │   ├── Queries/
│   │   │   │   ├── ListInvoicesQuery.php
│   │   │   │   ├── ListInvoicesHandler.php
│   │   │   │   ├── GetInvoiceQuery.php
│   │   │   │   └── GetInvoiceHandler.php
│   │   │   ├── Services/
│   │   │   │   └── InvoiceNumberService.php
│   │   │   ├── Data/
│   │   │   │   └── InvoiceData.php
│   │   │   └── Http/
│   │   │       ├── Admin/InvoiceController.php
│   │   │       └── Portal/InvoicePortalController.php
│   │   │
│   │   └── Portal/                            ← Slice: Self-serve billing UI
│   │       ├── Queries/
│   │       │   ├── GetBillingDashboardQuery.php
│   │       │   └── GetBillingDashboardHandler.php
│   │       └── Http/
│   │           └── BillingPortalController.php
│   │
│   ├── Models/
│   │   ├── SubscriptionInvoice.php            ← TenantAwareModel
│   │   ├── SubscriptionChange.php             ← TenantAwareModel (audit)
│   │   └── OrganizationFeatureOverride.php    ← TenantAwareModel
│   │
│   ├── Observers/
│   │   ├── SubscriptionInvoiceObserver.php
│   │   └── SubscriptionChangeObserver.php
│   │
│   ├── Policies/
│   │   └── SubscriptionPolicy.php
│   │
│   ├── Console/                               ← Artisan commands (thay vì Jobs)
│   │   ├── ProcessExpiringSubscriptionsCommand.php
│   │   └── SendRenewalRemindersCommand.php
│   │
│   └── Providers/
│       ├── SubscriptionServiceProvider.php
│       ├── EventServiceProvider.php
│       └── RouteServiceProvider.php
│
├── config/subscription.php
├── database/
│   ├── migrations/
│   └── seeders/
│       ├── PlanSeeder.php
│       └── FeatureSeeder.php
└── resources/views/
    ├── admin/plans/
    ├── admin/invoices/
    ├── portal/
    └── partials/
        ├── upgrade-wall.blade.php
        └── quota-bar.blade.php
```

---

## 3. Domain Models

### 3.1 Organization (augment)

```php
// app/Shared/Tenancy/Models/Organization.php — chỉ thêm trait
use Laravelcm\Subscriptions\Traits\HasPlanSubscriptions;

class Organization extends Model
{
    use HasPlanSubscriptions;
    // ...existing code unchanged
}
```

### 3.2 SubscriptionInvoice

```php
namespace Modules\Subscription\Models;

use App\Foundation\Models\TenantAwareModel;
use Modules\Subscription\Enums\InvoiceStatus;

class SubscriptionInvoice extends TenantAwareModel
{
    protected $table = 'subscription_invoices';

    protected $fillable = [
        'organization_id',
        'subscription_id',
        'plan_id',
        'invoice_number',
        'amount',
        'currency',
        'status',
        'billing_period_start',
        'billing_period_end',
        'due_date',
        'paid_at',
        'payment_method',
        'payment_ref',
        'notes',
        'idempotent_key',
    ];

    protected function casts(): array
    {
        return [
            'status'               => InvoiceStatus::class,
            'amount'               => 'decimal:2',
            'due_date'             => 'date',
            'paid_at'              => 'datetime',
            'billing_period_start' => 'date',
            'billing_period_end'   => 'date',
        ];
    }

    public function isPaid(): bool    { return $this->status === InvoiceStatus::Paid; }
    public function isOverdue(): bool { return $this->status === InvoiceStatus::Pending && $this->due_date?->isPast(); }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(config('laravel-subscriptions.models.subscription'));
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(config('laravel-subscriptions.models.plan'));
    }
}
```

### 3.3 SubscriptionChange

```php
class SubscriptionChange extends TenantAwareModel
{
    protected $table = 'subscription_changes';

    protected $fillable = [
        'organization_id', 'subscription_id',
        'from_plan_id', 'to_plan_id', 'changed_by',
        'change_type',   // ChangeType enum
        'reason', 'effective_at', 'prorate_credit',
    ];

    protected function casts(): array
    {
        return [
            'change_type'    => ChangeType::class,
            'effective_at'   => 'datetime',
            'prorate_credit' => 'decimal:2',
        ];
    }
}
```

### 3.4 OrganizationFeatureOverride

```php
class OrganizationFeatureOverride extends TenantAwareModel
{
    protected $table = 'organization_feature_overrides';

    protected $fillable = [
        'organization_id', 'feature_slug',
        'value', 'override_reason',
        'expires_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
```

---

## 4. Feature Slices — Chi tiết

### Slice 1: Plans

#### Data DTO

```php
// Features/Plans/Data/PlanData.php
class PlanData extends Data
{
    public function __construct(
        #[Required] public readonly string $slug,
        #[Required] public readonly string $name,
        public readonly ?string  $description,
        #[Numeric, Min(0)] public readonly float $price,
        public readonly ?float   $annual_price,
        public readonly string   $currency         = 'VND',
        public readonly string   $invoice_interval = 'month',
        public readonly int      $invoice_period   = 1,
        public readonly int      $trial_period     = 0,
        public readonly string   $trial_interval   = 'day',
        public readonly int      $grace_period     = 3,
        public readonly string   $grace_interval   = 'day',
        public readonly bool     $is_active        = true,
        public readonly bool     $is_public        = true,
        public readonly string   $tier             = 'growth',
        public readonly ?string  $tag_line         = null,   // e.g. "Most popular"
        public readonly ?string  $badge_color      = null,   // Tailwind class
    ) {}
}
```

#### Actions

```php
// Features/Plans/Actions/CreatePlanAction.php
class CreatePlanAction
{
    use AsAction;

    public function handle(PlanData $data): Plan
    {
        return DB::transaction(function () use ($data) {
            $plan = Plan::create([
                'slug'             => $data->slug,
                'name'             => $data->name,
                'description'      => $data->description,
                'price'            => $data->price,
                'currency'         => $data->currency,
                'invoice_interval' => $data->invoice_interval,
                'invoice_period'   => $data->invoice_period,
                'trial_period'     => $data->trial_period,
                'trial_interval'   => $data->trial_interval,
                'grace_period'     => $data->grace_period,
                'grace_interval'   => $data->grace_interval,
                'is_active'        => $data->is_active,
            ]);

            // Augmented columns (our custom migration)
            $plan->forceFill([
                'tier'         => $data->tier,
                'is_public'    => $data->is_public,
                'annual_price' => $data->annual_price,
                'badge_color'  => $data->badge_color,
                'tag_line'     => $data->tag_line,
            ])->save();

            return $plan;
        });
    }
}
```

```php
// Features/Plans/Actions/SyncPlanFeaturesAction.php
class SyncPlanFeaturesAction
{
    use AsAction;

    /** @param array<PlanFeatureData> $features */
    public function handle(Plan $plan, array $features): void
    {
        DB::transaction(function () use ($plan, $features) {
            Feature::where('plan_id', $plan->id)->delete();

            foreach ($features as $i => $f) {
                Feature::create([
                    'plan_id'             => $plan->id,
                    'slug'                => $f->slug,
                    'name'                => $f->name,
                    'value'               => $f->value,
                    'resettable_period'   => $f->resettable_period ?? 0,
                    'resettable_interval' => $f->resettable_interval ?? 'month',
                    'sort_order'          => $i,
                ]);
            }
        });
        // Không có cache để flush — context tự load lại ở request tiếp theo
    }
}
```

#### Queries

```php
// Features/Plans/Queries/ListPlansHandler.php
class ListPlansHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var ListPlansQuery $query */
        return Plan::query()
            ->when($query->activeOnly,   fn ($q) => $q->where('is_active', true))
            ->when($query->publicOnly,   fn ($q) => $q->where('is_public', true))
            ->when($query->withFeatures, fn ($q) => $q->with('features'))
            ->orderBy('sort_order')
            ->get();
    }
}
```

#### Controller

```php
// Features/Plans/Http/PlanController.php
class PlanController extends Controller
{
    public function __construct(private readonly ListPlansHandler $list) {}

    public function index()
    {
        $plans = $this->list->handle(new ListPlansQuery(activeOnly: false));
        return view('subscription::admin.plans.index', compact('plans'));
    }

    public function store(PlanRequest $request, CreatePlanAction $action)
    {
        $plan = $action->handle(PlanData::from($request->validated()));
        return redirect()->route('subscription.admin.plans.index')
            ->with('success', "Plan '{$plan->name}' đã được tạo.");
    }

    public function update(PlanRequest $request, Plan $plan, UpdatePlanAction $action)
    {
        $action->handle($plan, PlanData::from($request->validated()));
        return back()->with('success', 'Cập nhật thành công.');
    }

    public function toggle(Plan $plan, TogglePlanAction $action)
    {
        $action->handle($plan);
        return back();
    }
}
```

---

### Slice 2: Subscribe

```php
// Features/Subscribe/Actions/SubscribeOrganizationAction.php
class SubscribeOrganizationAction
{
    use AsAction;

    public function handle(Organization $org, SubscribeData $data): Subscription
    {
        // Idempotency check
        if ($data->idempotentKey) {
            $existing = $org->planSubscriptions()
                ->where('slug', 'like', '%' . $data->idempotentKey . '%')
                ->first();
            if ($existing) return $existing;
        }

        $plan = Plan::findOrFail($data->planId);

        $subscription = DB::transaction(function () use ($org, $plan, $data) {
            $current = $org->planSubscription('main');
            if ($current && $current->active()) {
                $current->cancel();
            }

            $subscription = $org->newPlanSubscription(
                $data->slug ?? 'main',
                $plan,
                $data->startDate
            );

            SubscriptionChange::create([
                'organization_id' => $org->id,
                'subscription_id' => $subscription->id,
                'from_plan_id'    => $current?->plan_id,
                'to_plan_id'      => $plan->id,
                'change_type'     => ChangeType::Subscribe,
                'effective_at'    => now(),
            ]);

            return $subscription;
        });

        // Reset in-process context ngay lập tức
        SubscriptionContext::flush($org->id);

        // Fire event AFTER transaction
        SubscriptionCreated::dispatch($org, $subscription, $plan);

        return $subscription;
    }
}
```

```php
// Features/Subscribe/Listeners/AutoSubscribeOnOrgCreated.php
// Listener đồng bộ — KHÔNG implement ShouldQueue
class AutoSubscribeOnOrgCreated
{
    public function handle(OrganizationCreated $event): void
    {
        $starterPlan = Plan::where('slug', config('subscription.default_plan', 'starter'))
            ->where('is_active', true)
            ->first();

        if (!$starterPlan) return;

        SubscribeOrganizationAction::run($event->organization, new SubscribeData(
            planId:        $starterPlan->id,
            idempotentKey: 'auto-' . $event->organization->id,
        ));
    }
}
```

```php
// Features/Subscribe/Queries/GetActiveSubscriptionHandler.php
class GetActiveSubscriptionHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): ?Subscription
    {
        /** @var GetActiveSubscriptionQuery $query */
        return Subscription::query()
            ->where('subscriber_type', Organization::class)
            ->where('subscriber_id', $query->organizationId)
            ->where('slug', 'like', '%main%')
            ->whereNull('canceled_at')
            ->with(['plan.features'])
            ->latest('starts_at')
            ->first();
    }
}
```

---

### Slice 3: ChangePlan

```php
// Features/ChangePlan/Actions/UpgradePlanAction.php
class UpgradePlanAction
{
    use AsAction;

    public function handle(Organization $org, ChangePlanData $data): Subscription
    {
        $newPlan    = Plan::findOrFail($data->newPlanId);
        $currentSub = $org->planSubscription('main');

        if (!$currentSub || !$currentSub->active()) {
            throw new SubscriptionException('Không có subscription active để upgrade.');
        }

        $previousPlanId = $currentSub->plan_id;
        $credit         = $this->calcProrateCredit($currentSub);

        $subscription = DB::transaction(function () use ($currentSub, $newPlan, $previousPlanId, $credit, $data, $org) {
            $subscription = $currentSub->changePlan($newPlan);

            SubscriptionChange::create([
                'organization_id' => $org->id,
                'subscription_id' => $subscription->id,
                'from_plan_id'    => $previousPlanId,
                'to_plan_id'      => $newPlan->id,
                'change_type'     => ChangeType::Upgrade,
                'reason'          => $data->reason,
                'effective_at'    => now(),
                'prorate_credit'  => $credit,
            ]);

            GenerateInvoiceAction::run($org, $subscription, $newPlan, credit: $credit);

            return $subscription;
        });

        // Reset context đồng bộ — request tiếp theo sẽ load plan mới từ DB
        SubscriptionContext::flush($org->id);

        PlanChanged::dispatch($org, $subscription, $previousPlanId, $newPlan->id);

        return $subscription;
    }

    private function calcProrateCredit(Subscription $sub): float
    {
        if (!$sub->ends_at || $sub->plan->isFree()) return 0.0;

        $daysRemaining = now()->diffInDays($sub->ends_at, absolute: true);
        $totalDays     = $sub->starts_at->diffInDays($sub->ends_at, absolute: true);

        return $totalDays > 0
            ? round(($sub->plan->price * $daysRemaining) / $totalDays, 2)
            : 0.0;
    }
}
```

---

### Slice 4: Cancel

```php
// Features/Cancel/Actions/CancelSubscriptionAction.php
class CancelSubscriptionAction
{
    use AsAction;

    public function handle(Organization $org, string $reason = ''): Subscription
    {
        $sub = $org->planSubscription('main');

        if (!$sub || $sub->canceled()) {
            throw new SubscriptionException('Không có subscription active để hủy.');
        }

        DB::transaction(function () use ($sub, $org, $reason) {
            $sub->cancel();   // sets canceled_at = now(), active until ends_at

            SubscriptionChange::create([
                'organization_id' => $org->id,
                'subscription_id' => $sub->id,
                'from_plan_id'    => $sub->plan_id,
                'to_plan_id'      => $sub->plan_id,
                'change_type'     => ChangeType::Cancel,
                'reason'          => $reason,
                'effective_at'    => $sub->ends_at ?? now(),
            ]);
        });

        SubscriptionContext::flush($org->id);
        SubscriptionCanceled::dispatch($org, $sub);

        return $sub->fresh();
    }
}
```

---

### Slice 5: FeatureGate

**Core engine — không dùng Redis, không dùng Queue.**

#### SubscriptionContext

```php
// Features/FeatureGate/Support/SubscriptionContext.php
namespace Modules\Subscription\Features\FeatureGate\Support;

final class SubscriptionContext
{
    /**
     * In-process store — tồn tại trong phạm vi 1 request.
     * Không persist sang request khác, không cần invalidate cache.
     * Key: organization_id (int)
     */
    private static array $store = [];

    private array $featureMap   = [];
    private bool  $active       = false;
    private bool  $onTrial      = false;
    private bool  $gracePeriod  = false;

    private function __construct(
        private readonly int           $orgId,
        private readonly ?Subscription $subscription,
    ) {}

    // ── Boot ────────────────────────────────────────────────────────────────

    public static function boot(Organization $org): self
    {
        if (isset(static::$store[$org->id])) {
            return static::$store[$org->id];
        }

        $instance = new self($org->id, $org->planSubscription('main'));
        $instance->buildFeatureMap($org);

        static::$store[$org->id] = $instance;

        return $instance;
    }

    public static function get(): ?self
    {
        $orgId = TenantContext::getOrganizationId();
        return $orgId ? (static::$store[$orgId] ?? null) : null;
    }

    /**
     * Flush in-process entry cho org — gọi ngay sau khi plan thay đổi
     * trong cùng request (SubscribeOrganizationAction, UpgradePlanAction, ...)
     * Request tiếp theo sẽ tự load lại từ DB.
     */
    public static function flush(int $orgId): void
    {
        unset(static::$store[$orgId]);
    }

    public static function flushAll(): void
    {
        static::$store = [];
    }

    // ── Public API ───────────────────────────────────────────────────────────

    public function canUse(string $featureSlug): bool
    {
        if (!$this->active && !$this->gracePeriod) return false;

        // Grace period: chỉ block premium flags, không block module cơ bản
        if ($this->gracePeriod && str_starts_with($featureSlug, 'flag.')) {
            return false;
        }

        $value = $this->featureMap[$featureSlug] ?? null;
        if ($value === null) return false;

        return $value === '1' || $value === 'true';
    }

    /** 0 = unlimited */
    public function limitOf(string $limitSlug): int
    {
        return (int) ($this->featureMap[$limitSlug] ?? 0);
    }

    public function atLimit(string $limitSlug, int $currentCount): bool
    {
        $limit = $this->limitOf($limitSlug);
        return $limit > 0 && $currentCount >= $limit;
    }

    public function quotaRemaining(string $quotaSlug): int
    {
        if (!$this->subscription) return 0;
        return (int) $this->subscription->getFeatureRemainings($quotaSlug);
    }

    public function isActive(): bool      { return $this->active; }
    public function isOnTrial(): bool     { return $this->onTrial; }
    public function isGracePeriod(): bool { return $this->gracePeriod; }
    public function planSlug(): string    { return $this->subscription?->plan->slug ?? 'none'; }
    public function plan(): ?Plan         { return $this->subscription?->plan; }

    // ── Internal ─────────────────────────────────────────────────────────────

    private function buildFeatureMap(Organization $org): void
    {
        $sub = $this->subscription;

        if (!$sub) {
            $this->active = false;
            return;
        }

        // Eager load đã được handle trong GetActiveSubscriptionHandler
        // nhưng planSubscription() từ trait không eager load → load thủ công
        $sub->loadMissing('plan.features');

        $this->active      = $sub->active();
        $this->onTrial     = $sub->onTrial();
        $this->gracePeriod = !$sub->active()
            && $sub->plan?->hasGrace()
            && !$sub->ended();

        // 1. Plan features (base)
        $map = [];
        foreach ($sub->plan->features ?? [] as $feature) {
            $map[$feature->slug] = $feature->value;
        }

        // 2. Active overrides WIN over plan features
        OrganizationFeatureOverride::where('organization_id', $org->id)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->each(function ($override) use (&$map) {
                $map[$override->feature_slug] = $override->value;
            });

        $this->featureMap = $map;
    }
}
```

#### CheckSubscription Middleware

```php
// Features/FeatureGate/Http/Middleware/CheckSubscription.php
class CheckSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!TenantContext::isSet()) {
            return $next($request);
        }

        $org = TenantContext::resolve();
        $ctx = SubscriptionContext::boot($org);

        if (!$ctx->isActive() && !$ctx->isGracePeriod()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'subscription_expired',
                    'message' => 'Subscription đã hết hạn.',
                ], 402);
            }
            return redirect()->route('subscription.portal.billing')
                ->with('warning', 'Subscription đã hết hạn. Vui lòng gia hạn.');
        }

        return $next($request);
    }
}
```

#### RequireFeature Middleware

```php
// Features/FeatureGate/Http/Middleware/RequireFeature.php
class RequireFeature
{
    public function handle(Request $request, Closure $next, string $featureSlug): Response
    {
        $ctx     = SubscriptionContext::get();
        $allowed = $ctx?->canUse($featureSlug) ?? false;

        if (!$allowed) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'       => 'feature_not_available',
                    'feature'     => $featureSlug,
                    'upgrade_url' => route('subscription.portal.plans'),
                ], 402);
            }

            return response()->view('subscription::partials.upgrade-wall', [
                'feature'    => $featureSlug,
                'plan'       => $ctx?->plan(),
                'upgradeUrl' => route('subscription.portal.plans'),
            ], 402);
        }

        return $next($request);
    }
}
```

#### Helper Functions

```php
// app/Helpers/subscription.php  ← đặt ở app/Helpers để autoload toàn cục

if (!function_exists('org_can')) {
    function org_can(string $featureSlug): bool
    {
        return SubscriptionContext::get()?->canUse($featureSlug) ?? false;
    }
}

if (!function_exists('org_limit')) {
    function org_limit(string $limitSlug): int
    {
        return SubscriptionContext::get()?->limitOf($limitSlug) ?? 0;
    }
}

if (!function_exists('org_at_limit')) {
    function org_at_limit(string $limitSlug, int $current): bool
    {
        return SubscriptionContext::get()?->atLimit($limitSlug, $current) ?? false;
    }
}

if (!function_exists('org_quota')) {
    function org_quota(string $quotaSlug): int
    {
        return SubscriptionContext::get()?->quotaRemaining($quotaSlug) ?? 0;
    }
}
```

---

### Slice 6: Billing

#### GenerateInvoiceAction

```php
// Features/Billing/Actions/GenerateInvoiceAction.php
class GenerateInvoiceAction
{
    use AsAction;

    public function handle(
        Organization $org,
        Subscription $subscription,
        Plan         $plan,
        float        $credit = 0.0,
        string       $idempotentKey = ''
    ): SubscriptionInvoice {

        if ($idempotentKey) {
            $existing = SubscriptionInvoice::where('idempotent_key', $idempotentKey)->first();
            if ($existing) return $existing;
        }

        $invoiceNumber = InvoiceNumberService::generate($org->id);
        $amount        = max(0.0, $plan->price - $credit);

        return SubscriptionInvoice::create([
            'organization_id'      => $org->id,
            'subscription_id'      => $subscription->id,
            'plan_id'              => $plan->id,
            'invoice_number'       => $invoiceNumber,
            'amount'               => $amount,
            'currency'             => $plan->currency ?? config('subscription.currency', 'VND'),
            'status'               => InvoiceStatus::Pending,
            'billing_period_start' => $subscription->starts_at?->toDateString(),
            'billing_period_end'   => $subscription->ends_at?->toDateString(),
            'due_date'             => now()->addDays(7)->toDateString(),
            'idempotent_key'       => $idempotentKey ?: null,
        ]);
    }
}
```

#### InvoiceNumberService — DB-based, không dùng Redis

```php
// Features/Billing/Services/InvoiceNumberService.php
class InvoiceNumberService
{
    /**
     * Generate invoice number với sequential counter per org per year.
     * Dùng DB transaction + SELECT COUNT với pessimistic lock thay vì Redis.
     * Format: INV-{YEAR}-{ORG:04d}-{SEQ:04d}
     */
    public static function generate(int $orgId): string
    {
        $year = now()->year;

        $seq = DB::transaction(function () use ($orgId, $year) {
            // lockForUpdate đảm bảo không có race condition khi concurrent request
            $count = SubscriptionInvoice::where('organization_id', $orgId)
                ->whereYear('created_at', $year)
                ->lockForUpdate()
                ->count();
            return $count + 1;
        });

        return sprintf('INV-%d-%04d-%04d', $year, $orgId, $seq);
    }
}
```

---

## 5. Scheduled Commands (không dùng Job/Queue)

```php
// Console/ProcessExpiringSubscriptionsCommand.php
namespace Modules\Subscription\Console;

use Illuminate\Console\Command;

class ProcessExpiringSubscriptionsCommand extends Command
{
    protected $signature   = 'subscription:process-expiring';
    protected $description = 'Kiểm tra và xử lý subscription sắp hết hạn hoặc đã hết hạn';

    public function handle(): int
    {
        $this->processExpired();
        $this->processExpiring(days: 1);

        return self::SUCCESS;
    }

    private function processExpired(): void
    {
        // Subscription đã ended (ends_at < now) và chưa bị cancel
        $ended = Subscription::findEndedPeriod()
            ->whereNull('canceled_at')
            ->get();

        foreach ($ended as $sub) {
            $org = Organization::find($sub->subscriber_id);
            if (!$org) continue;

            TenantContext::runForOrganization($org, function () use ($org, $sub) {
                // Flush in-process context (trong context của command, không có HTTP request)
                SubscriptionContext::flush($org->id);

                SubscriptionExpired::dispatch($org, $sub);

                $this->line("  [expired] org:{$org->id} plan:{$sub->plan->slug}");
            });
        }
    }

    private function processExpiring(int $days): void
    {
        $expiring = Subscription::findEndingPeriod($days)->get();

        foreach ($expiring as $sub) {
            $org = Organization::find($sub->subscriber_id);
            if (!$org) continue;

            TenantContext::runForOrganization($org, function () use ($org, $sub, $days) {
                SubscriptionExpiring::dispatch($org, $sub, $days);

                $this->line("  [expiring in {$days}d] org:{$org->id}");
            });
        }
    }
}
```

```php
// Console/SendRenewalRemindersCommand.php
class SendRenewalRemindersCommand extends Command
{
    protected $signature   = 'subscription:send-reminders';
    protected $description = 'Gửi email nhắc gia hạn (7, 3, 1 ngày trước khi hết hạn)';

    public function handle(): int
    {
        $reminderDays = config('subscription.renewal_reminder_days', [7, 3, 1]);

        foreach ($reminderDays as $days) {
            $subscriptions = Subscription::findEndingPeriod($days)->get();

            foreach ($subscriptions as $sub) {
                $org = Organization::find($sub->subscriber_id);
                if (!$org) continue;

                // Gửi đồng bộ — không queue
                $owner = $org->owner;
                if ($owner) {
                    $owner->notify(new RenewalReminderNotification($org, $sub, $days));
                    $this->line("  [reminder {$days}d] org:{$org->id} → {$owner->email}");
                }
            }
        }

        return self::SUCCESS;
    }
}
```

#### Đăng ký schedule trong ServiceProvider

```php
// Providers/SubscriptionServiceProvider.php — trong boot()
Schedule::command('subscription:process-expiring')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->runInBackground(false);   // chạy đồng bộ trong scheduler process

Schedule::command('subscription:send-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->runInBackground(false);
```

---

## 6. Events & Listeners

```
EventServiceProvider:

SubscriptionCreated     → (không có listener — context đã flush ở Action)
SubscriptionRenewed     → SendSubscriptionWelcomeNotification (sync)
PlanChanged             → (context đã flush ở Action)
SubscriptionCanceled    → SendCancellationConfirmation (sync)
SubscriptionExpired     → SendExpiryNotification (sync)
SubscriptionExpiring    → (handled inline trong SendRenewalRemindersCommand)

OrganizationCreated     → AutoSubscribeOnOrgCreated (sync, không ShouldQueue)
```

**Tất cả listener đều synchronous** — không implement `ShouldQueue`.  
Context invalidation không cần listener riêng: `SubscriptionContext::flush()` được gọi trực tiếp trong từng Action.

---

## 7. Permissions

### Thêm vào PermissionEnum.php

```php
case SUBSCRIPTION_VIEW    = 'subscription.view';
case SUBSCRIPTION_MANAGE  = 'subscription.manage';
case SUBSCRIPTION_BILLING = 'subscription.billing';
case SUBSCRIPTION_ADMIN   = 'subscription.admin';
```

### Thêm vào config/permissions.php

```php
R::CEO->value   => [/* existing */ P::SUBSCRIPTION_VIEW->value, P::SUBSCRIPTION_MANAGE->value, P::SUBSCRIPTION_BILLING->value],
R::ADMIN->value => [/* existing */ P::SUBSCRIPTION_VIEW->value, P::SUBSCRIPTION_MANAGE->value, P::SUBSCRIPTION_BILLING->value, P::SUBSCRIPTION_ADMIN->value],
R::OPS->value   => [/* existing */ P::SUBSCRIPTION_VIEW->value, P::SUBSCRIPTION_BILLING->value],
```

---

## 8. Routes

```php
// Modules/Subscription/routes/web.php
use App\Enums\PermissionEnum as P;

// ── Admin (system_admin only) ─────────────────────────────────────────────
Route::middleware(['web', 'auth', 'can:' . P::SUBSCRIPTION_ADMIN->value])
    ->prefix('dashboard/subscription/admin')
    ->name('subscription.admin.')
    ->group(function () {
        Route::resource('plans', PlanController::class)->except(['show']);
        Route::post('plans/{plan}/toggle',           [PlanController::class, 'toggle'])->name('plans.toggle');
        Route::post('plans/{plan}/features/sync',    [PlanController::class, 'syncFeatures'])->name('plans.features.sync');

        Route::get('subscriptions',                  [AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::post('subscriptions/{organization}/assign',   [AdminSubscriptionController::class, 'assign'])->name('subscriptions.assign');
        Route::post('subscriptions/{organization}/override', [AdminSubscriptionController::class, 'override'])->name('subscriptions.override');

        Route::get('invoices',                       [InvoiceController::class, 'index'])->name('invoices.index');
        Route::post('invoices/{invoice}/mark-paid',  [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
        Route::post('invoices/{invoice}/void',       [InvoiceController::class, 'void'])->name('invoices.void');
    });

// ── Portal (CEO / billing permission) ────────────────────────────────────
Route::middleware(['web', 'auth', 'can:' . P::SUBSCRIPTION_VIEW->value])
    ->prefix('billing')
    ->name('subscription.portal.')
    ->group(function () {
        Route::get('/',       [BillingPortalController::class, 'index'])->name('billing');
        Route::get('plans',   [BillingPortalController::class, 'plans'])->name('plans');

        Route::middleware('can:' . P::SUBSCRIPTION_MANAGE->value)->group(function () {
            Route::post('subscribe/{plan}',  [SubscribeController::class, 'store'])->name('subscribe');
            Route::post('upgrade/{plan}',    [ChangePlanController::class, 'upgrade'])->name('upgrade');
            Route::post('downgrade/{plan}',  [ChangePlanController::class, 'downgrade'])->name('downgrade');
            Route::post('cancel',            [CancelController::class, 'store'])->name('cancel');
            Route::post('resume',            [CancelController::class, 'resume'])->name('resume');
        });

        Route::middleware('can:' . P::SUBSCRIPTION_BILLING->value)->group(function () {
            Route::get('invoices',                  [InvoicePortalController::class, 'index'])->name('invoices');
            Route::get('invoices/{invoice}',        [InvoicePortalController::class, 'show'])->name('invoices.show');
            Route::get('invoices/{invoice}/pdf',    [InvoicePortalController::class, 'pdf'])->name('invoices.pdf');
        });
    });
```

---

## 9. Middleware Registration

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {

    $middleware->alias([
        'tenant'        => IdentifyOrganization::class,
        'assert.tenant' => AssertTenant::class,
        // ADD:
        'feature'       => \Modules\Subscription\Features\FeatureGate\Http\Middleware\RequireFeature::class,
    ]);

    // CheckSubscription chạy sau IdentifyOrganization — append vào web group
    $middleware->appendToGroup('web',
        \Modules\Subscription\Features\FeatureGate\Http\Middleware\CheckSubscription::class
    );
})
```

### Apply feature gate vào module routes

```php
// Modules/Lead/routes/web.php
Route::middleware(['web', 'auth', 'feature:module.crm'])
    ->prefix('leads')->name('lead.')
    ->group(function () { ... });

// Modules/WorkflowAutomation/routes/web.php
Route::middleware(['web', 'auth', 'can:...', 'feature:module.workflow'])
    ->prefix('dashboard/workflows')->name('workflows.')
    ->group(function () { ... });
```

---

## 10. Database Schema

> **Nguyên tắc:** Không dùng JSON column. Mỗi thuộc tính là 1 column riêng, có thể index và migrate an toàn.

### 10.1 Alter plans table

```php
Schema::table('plans', function (Blueprint $table) {
    // Thay vì JSON highlight → 1 varchar duy nhất
    $table->string('tag_line', 120)->nullable()->after('slug');    // "Most popular"
    $table->string('tier', 32)->default('growth')->after('tag_line');
    $table->string('badge_color', 64)->nullable()->after('tier');  // Tailwind class
    $table->boolean('is_public')->default(true)->after('badge_color');
    $table->decimal('annual_price', 15, 2)->nullable()->after('price');
    $table->string('currency_local', 10)->default('VND');
    $table->decimal('price_local', 15, 2)->nullable();
});
```

### 10.2 subscription_invoices

```php
Schema::create('subscription_invoices', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
    $table->unsignedBigInteger('subscription_id');
    $table->unsignedBigInteger('plan_id')->nullable();
    $table->string('invoice_number', 32)->unique();
    $table->decimal('amount', 15, 2);
    $table->string('currency', 10)->default('VND');
    $table->tinyInteger('status')->default(1);          // InvoiceStatus
    $table->date('billing_period_start')->nullable();
    $table->date('billing_period_end')->nullable();
    $table->date('due_date')->nullable();
    $table->timestamp('paid_at')->nullable();
    $table->string('payment_method', 64)->nullable();
    $table->string('payment_ref', 191)->nullable();
    $table->text('notes')->nullable();
    $table->string('idempotent_key', 128)->nullable()->unique();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['organization_id', 'status', 'created_at'], 'idx_inv_org_status');
    $table->index(['status', 'due_date'],                       'idx_inv_due');
});
```

### 10.3 subscription_changes

```php
Schema::create('subscription_changes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
    $table->unsignedBigInteger('subscription_id');
    $table->unsignedBigInteger('from_plan_id')->nullable();
    $table->unsignedBigInteger('to_plan_id');
    $table->unsignedBigInteger('changed_by')->nullable();
    $table->string('change_type', 32);     // subscribe|upgrade|downgrade|cancel|resume|renew
    $table->string('reason', 255)->nullable();
    $table->timestamp('effective_at');
    $table->decimal('prorate_credit', 15, 2)->default(0);
    $table->timestamp('created_at')->useCurrent();

    $table->index(['organization_id', 'created_at'], 'idx_chg_org');
    $table->index('subscription_id',                 'idx_chg_sub');
});
```

### 10.4 organization_feature_overrides

```php
Schema::create('organization_feature_overrides', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
    $table->string('feature_slug', 128);
    $table->string('value', 255);           // '1'|'0' cho bool, số cho limit
    $table->string('override_reason', 255)->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->unsignedBigInteger('created_by')->nullable();
    $table->timestamps();

    $table->unique(['organization_id', 'feature_slug'],  'uq_org_feature');
    $table->index(['organization_id', 'expires_at'],     'idx_override_active');
});
```

---

## 11. config/subscription.php

```php
return [

    'subscription_slug' => 'main',
    'default_plan'      => env('SUBSCRIPTION_DEFAULT_PLAN', 'starter'),

    // Feature slug → module name (dùng cho sidebar gating)
    'module_features' => [
        'crm'         => 'module.crm',
        'workflow'    => 'module.workflow',
        'sop'         => 'module.sop',
        'hr'          => 'module.hr',
        'recruitment' => 'module.recruitment',
        'assessment'  => 'module.assessment',
        'project'     => 'module.project',
        'kc'          => 'module.kc',
        'marketplace' => 'module.marketplace',
        'ai'          => 'module.ai',
    ],

    // Limit slugs → Model để auto-count current usage
    'limit_models' => [
        'limit.employees' => \Modules\Employee\Models\Employee::class,
        'limit.members'   => \App\Models\User::class,
        'limit.workflows' => \Modules\WorkflowAutomation\Models\Workflow::class,
        'limit.projects'  => \Modules\Project\Models\Project::class,
    ],

    'limit_labels' => [
        'limit.employees'  => 'Nhân viên',
        'limit.members'    => 'Người dùng',
        'limit.workflows'  => 'Workflow',
        'limit.projects'   => 'Dự án',
        'limit.storage_gb' => 'Dung lượng (GB)',
    ],

    'quota_slugs' => [
        'quota.ai_requests',
        'quota.workflow_runs',
        'quota.email_notifications',
    ],

    'quota_labels' => [
        'quota.ai_requests'          => 'AI requests / tháng',
        'quota.workflow_runs'        => 'Workflow executions / tháng',
        'quota.email_notifications'  => 'Email notifications / tháng',
    ],

    'on_expire'              => 'restrict',  // restrict | suspend | grace_only
    'renewal_reminder_days'  => [7, 3, 1],
    'currency'               => env('SUBSCRIPTION_CURRENCY', 'VND'),

    'gateways' => [
        'default' => env('PAYMENT_GATEWAY', 'manual'),
        'vnpay'   => [
            'tmn_code' => env('VNPAY_TMN_CODE'),
            'secret'   => env('VNPAY_SECRET'),
            'url'      => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
        ],
    ],
];
```

---

## 12. ServiceProvider

```php
// Providers/SubscriptionServiceProvider.php
class SubscriptionServiceProvider extends ModuleServiceProvider
{
    protected $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(
            module_path($this->name, 'config/subscription.php'),
            'subscription'
        );

        // Đăng ký helper functions toàn cục
        $helpersPath = module_path($this->name, 'app/Helpers/subscription.php');
        if (file_exists($helpersPath)) {
            require_once $helpersPath;
        }
    }

    public function boot(): void
    {
        // Authorization
        Gate::policy(SubscriptionInvoice::class, SubscriptionPolicy::class);

        // Activity logging
        SubscriptionInvoice::observe(SubscriptionInvoiceObserver::class);
        SubscriptionChange::observe(SubscriptionChangeObserver::class);

        // Blade directives
        Blade::if('canFeature', fn (string $slug) => org_can($slug));
        Blade::if('overLimit',  fn (string $slug, int $count) => org_at_limit($slug, $count));

        // Artisan commands
        $this->commands([
            ProcessExpiringSubscriptionsCommand::class,
            SendRenewalRemindersCommand::class,
        ]);

        // Scheduled commands — không dùng Job/Queue
        if ($this->app->runningInConsole()) {
            Schedule::command('subscription:process-expiring')
                ->dailyAt('00:05')
                ->withoutOverlapping();

            Schedule::command('subscription:send-reminders')
                ->dailyAt('08:00')
                ->withoutOverlapping();
        }
    }
}
```

---

## 13. Feature Slug Taxonomy

| Slug | Type | Starter | Growth | Scale | Enterprise |
|---|---|---|---|---|---|
| `module.task` | bool | `1` | `1` | `1` | `1` |
| `module.sop` | bool | `1` | `1` | `1` | `1` |
| `module.hr` | bool | `1` | `1` | `1` | `1` |
| `module.crm` | bool | `0` | `1` | `1` | `1` |
| `module.workflow` | bool | `0` | `1` | `1` | `1` |
| `module.ai` | bool | `0` | `1` | `1` | `1` |
| `module.recruitment` | bool | `0` | `1` | `1` | `1` |
| `module.assessment` | bool | `0` | `1` | `1` | `1` |
| `module.project` | bool | `0` | `0` | `1` | `1` |
| `module.kc` | bool | `0` | `0` | `1` | `1` |
| `module.marketplace` | bool | `0` | `0` | `1` | `1` |
| `limit.employees` | int | `5` | `50` | `200` | `0` |
| `limit.members` | int | `3` | `15` | `50` | `0` |
| `limit.workflows` | int | `2` | `20` | `0` | `0` |
| `limit.projects` | int | `0` | `0` | `0` | `0` |
| `limit.storage_gb` | int | `1` | `10` | `50` | `0` |
| `flag.api_access` | bool | `0` | `0` | `1` | `1` |
| `flag.audit_log` | bool | `0` | `1` | `1` | `1` |
| `flag.advanced_reports` | bool | `0` | `1` | `1` | `1` |
| `flag.sso` | bool | `0` | `0` | `0` | `1` |
| `flag.white_label` | bool | `0` | `0` | `0` | `1` |
| `flag.custom_domain` | bool | `0` | `0` | `0` | `1` |
| `quota.ai_requests` | int | `20` | `500` | `5000` | `0` |
| `quota.workflow_runs` | int | `50` | `2000` | `0` | `0` |

---

## 14. Plan Tiers

| | Starter | Growth | Scale | Enterprise |
|---|---|---|---|---|
| **VND/tháng** | 0 | 990,000 | 2,490,000 | Liên hệ |
| **VND/năm** | — | 9,900,000 | 24,900,000 | Liên hệ |
| **Trial** | 14 ngày | — | — | — |
| **Grace** | 3 ngày | 3 ngày | 7 ngày | 7 ngày |
| **tag_line** | `null` | `"Phổ biến nhất"` | `"Tăng trưởng mạnh"` | `"Không giới hạn"` |
| **badge_color** | `null` | `"badge-primary"` | `"badge-success"` | `"badge-secondary"` |

---

## 15. Phased Implementation

### Phase 1 — Core Foundation

- [ ] `php artisan module:make Subscription`
- [ ] Publish package migrations: `vendor:publish --tag=laravel-subscriptions-migrations`
- [ ] Viết 4 custom migrations (alter plans, invoices, changes, overrides)
- [ ] Add `HasPlanSubscriptions` trait vào `Organization` model
- [ ] Tạo `SubscriptionContext` (in-process static array, không cache)
- [ ] Tạo `CheckSubscription` + `RequireFeature` middleware
- [ ] Đăng ký middleware alias `feature:` trong `bootstrap/app.php`
- [ ] Append `CheckSubscription` vào web group
- [ ] Viết `SubscribeOrganizationAction`
- [ ] Viết `AutoSubscribeOnOrgCreated` listener (synchronous, không ShouldQueue)
- [ ] Tạo `EventServiceProvider`
- [ ] Viết `PlanSeeder` + `FeatureSeeder`
- [ ] Seed existing orgs với Starter plan
- [ ] Thêm `SUBSCRIPTION_*` vào `PermissionEnum` + `config/permissions.php`
- [ ] `php artisan permissions:sync`
- [ ] Unit tests: `SubscriptionContextTest`

### Phase 2 — Feature Gate Enforcement

- [ ] Apply `feature:{slug}` middleware vào Lead, Workflow, SOP, AI, ... routes
- [ ] Viết helper functions + đăng ký autoload trong `app/Helpers/subscription.php`
- [ ] Đăng ký Blade directives `@canFeature` / `@overLimit`
- [ ] `upgrade-wall.blade.php` — 402 UI + plan comparison CTA
- [ ] `quota-bar.blade.php` — progress bar usage
- [ ] Apply limit check vào `store()` của Employee, User, Workflow, Project
- [ ] `RecordFeatureUsageAction` cho AI requests + workflow executions
- [ ] Integration tests: 402 khi Starter → CRM, pass khi Growth → CRM

### Phase 3 — Admin Management UI

- [ ] `PlanController` — CRUD + toggle + syncFeatures
- [ ] `AdminSubscriptionController` — list, assign, extend, override
- [ ] Views: `admin/plans/index`, `admin/plans/_form`
- [ ] Subscription status badge trên Organization list
- [ ] Subscription info card trên Organization detail
- [ ] Thêm "Subscription" vào sidebar system_admin

### Phase 4 — Billing & Invoices

- [ ] `GenerateInvoiceAction` + `InvoiceNumberService` (DB sequential, không Redis)
- [ ] `MarkInvoicePaidAction` + `VoidInvoiceAction`
- [ ] `ProcessExpiringSubscriptionsCommand` (Artisan, không Job)
- [ ] `SendRenewalRemindersCommand` (Artisan, không Job)
- [ ] Đăng ký schedule trong ServiceProvider
- [ ] Notification: `RenewalReminderNotification`, `ExpiryNotification`
- [ ] `InvoiceController` (admin): list, mark-paid, void
- [ ] `SubscriptionChangeObserver` + `SubscriptionInvoiceObserver` → ActivityLog

### Phase 5 — Self-Serve Portal

- [ ] `BillingPortalController` — billing dashboard, pricing page
- [ ] `GetBillingDashboardHandler` — usage stats + quota stats + invoices
- [ ] `SubscribeController`, `ChangePlanController`, `CancelController`
- [ ] `InvoicePortalController` — list, show, PDF
- [ ] Views: `portal/billing.blade.php`, `portal/plans.blade.php`
- [ ] `UpgradePlanAction` với prorate credit
- [ ] E2E tests: upgrade flow, cancel, invoice download

### Phase 6 — Payment Gateway (Optional)

- [ ] VNPay webhook handler: `POST /billing/webhook/vnpay`
- [ ] `MarkInvoicePaidAction` triggered bởi webhook (synchronous, không queue)
- [ ] Admin analytics: MRR, plan distribution, churn
- [ ] CLI debug: `php artisan subscription:status {org_id}`

---

## 16. Testing Strategy

```
Tests/Unit/Features/FeatureGate/SubscriptionContextTest.php
    ✓ canUse() true khi feature value = '1'
    ✓ canUse() false khi feature value = '0'
    ✓ canUse() false khi không có subscription
    ✓ canUse() true khi có override active
    ✓ canUse() false khi override hết hạn
    ✓ grace period: module.* accessible, flag.* blocked
    ✓ limitOf() = 0 cho Scale plan (unlimited)
    ✓ atLimit() đúng khi count >= limit
    ✓ flush() xóa entry khỏi in-process store
    ✓ boot() trả về same instance nếu gọi lại trong cùng request

Tests/Unit/Features/Billing/InvoiceNumberServiceTest.php
    ✓ format INV-YYYY-ORGID-SEQ
    ✓ sequential tăng dần theo năm

Tests/Feature/FeatureGateMiddlewareTest.php
    ✓ Starter org → 402 khi GET /leads
    ✓ Growth org → 200 khi GET /leads
    ✓ Expired org → redirect /billing
    ✓ Expired org + JSON request → 402 JSON
    ✓ Grace period → core module accessible

Tests/Feature/SubscriptionLifecycleTest.php
    ✓ subscribe → trial active → ends_at tính đúng
    ✓ upgrade → plan_id thay đổi + SubscriptionChange logged
    ✓ upgrade → prorate credit tính đúng
    ✓ cancel → canceled_at set, active đến ends_at
    ✓ resume → canceled_at cleared
    ✓ SubscriptionContext flush sau mỗi action

Tests/Feature/BillingPortalTest.php
    ✓ CEO xem /billing được (200)
    ✓ VIEWER không xem /billing (403)
    ✓ Upgrade flow: submit → invoice generated
    ✓ Invoice PDF download
```

---

## 17. Key Design Decisions

| Quyết định | Lý do | Tradeoff |
|---|---|---|
| **Không dùng Redis cache cho feature map** | Plan thay đổi hiếm. 1 DB query (<1ms với index + eager load). Tránh bug stale cache và complexity. | Mỗi request đều query DB nếu không có in-process hit. Chấp nhận được. |
| **In-process static array thay vì cache** | Trong 1 request, feature map chỉ cần load 1 lần. Static array tự reset khi request kết thúc — không cần invalidation logic. | Không shared across workers; đây là điều mong muốn (stateless request). |
| **SubscriptionContext::flush() trực tiếp trong Action** | Không cần listener trung gian. Action biết chính xác khi nào context thay đổi → gọi flush() ngay. | Coupling nhẹ giữa Action và Context. Acceptable vì cùng trong 1 module. |
| **Artisan command thay vì Job** | Job cần queue worker process. Command chạy trong scheduler process — ít infra hơn, dễ debug, dễ monitor qua `schedule:list`. | Không chạy song song được nếu có nhiều org; dùng `withoutOverlapping()` để đảm bảo. |
| **Notification đồng bộ trong command** | Với vài chục org, gửi đồng bộ trong command đủ nhanh (<1s/org). Không cần queue riêng. | Nếu scale lên hàng nghìn org, cần xem lại. Dễ refactor sau. |
| **Không dùng JSON column** | JSON không index được (trừ MySQL 8+ virtual column), không migrate safe, không query WHERE chuẩn SQL. `tag_line VARCHAR` đủ cho marketing copy. | `tag_line` chỉ chứa 1 dòng text thay vì array. Nếu cần nhiều bullet point → tạo `plan_highlights` table riêng. |
| **InvoiceNumberService dùng DB lockForUpdate** | Không cần Redis. `lockForUpdate()` trong transaction đảm bảo atomic sequential counter. | Chậm hơn Redis atomic counter ~2-3ms. Acceptable với tần suất generate invoice thấp. |
| **Subscriber = Organization** | Multi-tenant: plan thuộc về org, không phải user cá nhân. | 1 org chỉ có 1 active subscription. Enforce bằng slug `main`. |
| **Không extend package models** | Plan/Subscription là global (không phải tenant-scoped). Extend `TenantAwareModel` sẽ thêm OrganizationScope sai. | Phải wrap qua `HasPlanSubscriptions` trait khi access từ Organization. |
