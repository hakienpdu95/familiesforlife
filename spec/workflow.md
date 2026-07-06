# Workflow Automation Module — Specification
## Thiết kế cho hệ sinh thái đa-module: Survey → Lead → User → ...

> **Stack**: Laravel 13 · PHP 8.4 · SQLite (dev) / configurable (prod) · `nwidart/laravel-modules`
> **Packages**: `lorisleiva/laravel-actions` · `spatie/laravel-data` · `spatie/laravel-permission`
>              `rap2hpoutre/fast-excel`
>
> **Trạng thái module**: Survey (đã có) · Lead (đang plan) · User (đang plan)
> **Người dùng**: Admin qua UI (no-code) + Developer qua code (phức tạp)
> **Action targets**: Nội bộ (email, notification, update data) + Ngoài (webhook, CRM, Slack)
>
> **Triết lý thiết kế**:
> - Module không biết về nhau — chỉ nói chuyện qua Workflow Engine
> - Thêm module mới = implement 2 interface + đăng ký trong ServiceProvider
> - Admin tự cấu hình workflow qua UI; developer mở rộng trigger/action mới qua code
> - Không bao giờ dispatch trong DB transaction
> - Mỗi step fail độc lập, không dừng workflow
> - Mọi workflow đều scoped theo organization (multi-tenant)
>
> **Cập nhật**: 2026-05-27

---

## Mục lục

1. [Kiến trúc tổng quan](#1-kiến-trúc-tổng-quan)
2. [Contract Layer — 2 interface cốt lõi](#2-contract-layer)
3. [TriggerPayload — DTO trung tâm](#3-triggerpayload--dto-trung-tâm)
4. [Database Schema](#4-database-schema)
5. [Enums](#5-enums)
6. [Core Engine](#6-core-engine)
7. [Trigger Implementations — per module](#7-trigger-implementations)
8. [Action Executor Implementations](#8-action-executor-implementations)
9. [WorkflowDispatcher — điểm vào](#9-workflowdispatcher--điểm-vào)
10. [Actions với lorisleiva](#10-actions-với-lorisleiva)
11. [Routes & Controllers](#11-routes--controllers)
12. [Models](#12-models)
    - 12.1–12.8 Model classes với relationships
    - [12b. WorkflowApiController — Đầy đủ](#12b-workflowapicontroller--đầy-đủ)
13. [Admin UI — Workflow Builder](#13-admin-ui--workflow-builder)
14. [Cách tích hợp — Survey (đã có)](#14-cách-tích-hợp--survey-đã-có)
15. [Cách tích hợp — Lead (đang plan)](#15-cách-tích-hợp--lead-đang-plan)
16. [Cách tích hợp — User (đang plan)](#16-cách-tích-hợp--user-đang-plan)
17. [Cách thêm module hoàn toàn mới](#17-cách-thêm-module-hoàn-toàn-mới)
18. [Permissions & Config](#18-permissions--config)
19. [Tích hợp Sidebar & Navigation](#19-tích-hợp-sidebar--navigation)
20. [Anti-patterns phải tránh](#20-anti-patterns-phải-tránh)
21. [Migrations hoàn chỉnh](#21-migrations-hoàn-chỉnh)
22. [Seeders](#22-seeders)
23. [Thứ tự triển khai](#23-thứ-tự-triển-khai)
24. [Vận hành & Resilience](#24-vận-hành--resilience)

---

## 1. Kiến trúc tổng quan

### 1.1 Dependency graph — nguyên tắc không thể vi phạm

```
Module Survey ──┐
Module Lead  ──┼──► WorkflowEngine ──► ActionExecutors
Module User  ──┘         │
Module N...              └──► ActivityLog (ghi log)
                              Queue 'workflows' (async)

NGHIÊM CẤM:
WorkflowEngine ──► Module Survey   ✗  (không import chéo)
Module Survey  ──► Module Lead     ✗  (không biết nhau)
```

Module nguồn (Survey, Lead, User) phụ thuộc vào WorkflowEngine.
WorkflowEngine KHÔNG phụ thuộc vào bất kỳ module nguồn nào.
Các module nguồn KHÔNG phụ thuộc vào nhau.

### 1.2 Luồng hoàn chỉnh

```
[Sự kiện xảy ra trong module X]
         │
         │  WorkflowDispatcher::fire(TriggerPayload)   < 1ms, không block
         ▼
[Queue 'workflows']
         │
         │  ExecuteWorkflowAction (async job)
         ▼
    ┌────────────────────────────────────────┐
    │  0. Idempotency check (run_id unique)   │
    │  1. Match workflows by trigger_type     │
    │     + filter by organization_id         │
    │  2. CooldownGuard::allow()              │  → skip nếu trong cooldown
    │  3. ConditionEvaluator::pass()          │  → skip nếu condition fail
    │  4. Foreach step:                       │
    │     a. Resolve ActionExecutor           │
    │     b. Nếu delay > 0: log "scheduled"  │
    │        → dispatch delayed job, continue │
    │     c. executor->execute()              │  → try/catch riêng từng step
    │     d. Log step result                  │
    │  5. Log execution summary               │
    └────────────────────────────────────────┘
         │
         ▼
[ActivityLog + workflow_executions]
```

### 1.3 Cấu trúc module

```
Modules/WorkflowAutomation/
├── app/
│   ├── Contracts/
│   │   ├── TriggerSource.php
│   │   ├── ActionExecutor.php
│   │   └── WorkflowSubject.php
│   ├── Core/
│   │   ├── WorkflowDispatcher.php
│   │   ├── TriggerRegistry.php
│   │   ├── ActionRegistry.php
│   │   ├── SubjectRegistry.php
│   │   ├── ConditionEvaluator.php
│   │   └── CooldownGuard.php
│   ├── Data/
│   │   ├── TriggerPayload.php
│   │   └── ActionResult.php
│   ├── Enums/
│   │   ├── WorkflowStatus.php
│   │   ├── ConditionMatch.php
│   │   ├── CooldownType.php
│   │   └── OperatorType.php
│   ├── Actions/
│   │   ├── ExecuteWorkflowAction.php
│   │   ├── ExecuteWorkflowStepAction.php
│   │   └── PurgeOldExecutionsAction.php
│   ├── Triggers/
│   │   └── ManualTrigger.php
│   ├── Executors/
│   │   ├── SendEmailExecutor.php
│   │   ├── SendNotificationExecutor.php
│   │   ├── UpdateSubjectExecutor.php
│   │   └── CallWebhookExecutor.php
│   ├── Mail/
│   │   └── WorkflowMail.php
│   ├── Models/
│   │   ├── Workflow.php
│   │   ├── WorkflowCondition.php
│   │   ├── WorkflowStep.php
│   │   ├── WorkflowExecution.php
│   │   └── WorkflowExecutionStep.php
│   ├── Http/Controllers/
│   │   ├── WorkflowController.php
│   │   ├── WorkflowApiController.php
│   │   └── WorkflowExecutionController.php
│   └── Services/
│       └── WorkflowBuilderService.php
├── config/workflow_automation.php
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/views/
└── routes/
    ├── web.php
    └── console.php
```

---

## 2. Contract Layer

### 2.1 `TriggerSource`

```php
// Modules/WorkflowAutomation/app/Contracts/TriggerSource.php
interface TriggerSource
{
    public function type(): string;    // 'survey.submitted', 'lead.created'
    public function label(): string;   // Tiếng Việt cho admin UI
    public function module(): string;  // 'Survey', 'Lead', 'User'

    /**
     * Fields available trong payload — admin Builder đọc để render dropdown.
     * Format: [
     *   ['key' => 'extra.band_code', 'label' => 'Band code', 'type' => 'string'],
     *   ['key' => 'extra.score',     'label' => 'Điểm tổng', 'type' => 'number'],
     * ]
     */
    public function availableFields(): array;

    /**
     * Config fields admin điền khi chọn trigger.
     * Format: [
     *   ['key' => 'survey_id', 'label' => 'Survey', 'type' => 'model_select',
     *    'model' => 'surveys', 'required' => false, 'hint' => 'Để trống = tất cả'],
     * ]
     */
    public function configFields(): array;

    /**
     * Kiểm tra payload có match trigger_config không.
     * $parsedConfig là array typed values từ workflow_trigger_params (đã qua castValue()).
     */
    public function matches(TriggerPayload $payload, array $parsedConfig): bool;
}
```

### 2.2 `ActionExecutor`

```php
// Modules/WorkflowAutomation/app/Contracts/ActionExecutor.php
interface ActionExecutor
{
    public function type(): string;    // 'email.send', 'lead.create'
    public function label(): string;
    public function module(): string;  // 'Core', 'Lead', 'User'
    public function stepConfigFields(): array;

    /**
     * Thực thi action. KHÔNG throw exception ra ngoài.
     * Luôn return ActionResult.
     */
    public function execute(
        \Modules\WorkflowAutomation\Models\WorkflowStep $step,
        \Modules\WorkflowAutomation\Data\TriggerPayload $payload,
    ): \Modules\WorkflowAutomation\Data\ActionResult;
}
```

### 2.3 `WorkflowSubject`

```php
// Modules/WorkflowAutomation/app/Contracts/WorkflowSubject.php
interface WorkflowSubject
{
    public static function workflowSubjectType(): string; // 'SurveyResponse', 'Lead'

    /**
     * Fields admin có thể update từ Workflow.
     * Format: [['field' => 'status', 'label' => 'Trạng thái', 'type' => 'string']]
     */
    public static function workflowUpdatableFields(): array;

    public static function resolveFromPayload(
        \Modules\WorkflowAutomation\Data\TriggerPayload $payload,
    ): ?static;
}
```

---

## 3. TriggerPayload — DTO trung tâm

```php
// Modules/WorkflowAutomation/app/Data/TriggerPayload.php
use Spatie\LaravelData\Data;

class TriggerPayload extends Data
{
    public function __construct(
        // ── Định danh event ─────────────────────────────────────
        public readonly string  $triggerType,    // 'survey.submitted'
        public readonly string  $sourceModule,   // 'Survey'

        // ── Multi-tenancy ────────────────────────────────────────
        public readonly ?int    $organizationId, // từ TenantContext::getOrganizationId()

        // ── Actor ────────────────────────────────────────────────
        public readonly ?int    $actorId,
        public readonly ?string $actorEmail,
        public readonly ?string $actorName,
        public readonly ?string $actorRole,

        // ── Subject ──────────────────────────────────────────────
        public readonly ?string $subjectType,   // 'SurveyResponse' — KHÔNG dùng FQCN
        public readonly ?int    $subjectId,
        public readonly ?string $subjectLabel,

        // ── Extra: data đặc thù từng module ─────────────────────
        // Chỉ scalar values, không lồng nhau
        // ConditionEvaluator truy cập qua 'extra.field_name'
        public readonly array   $extra = [],

        // ── Tracking ─────────────────────────────────────────────
        public readonly string  $requestId,
        public readonly \DateTimeImmutable $firedAt = new \DateTimeImmutable(),
    ) {}

    // ── Factory methods ─────────────────────────────────────────

    public static function forSurveySubmit(
        \Modules\Survey\app\Models\SurveyResponse $response,
        ?int $actorId = null,
    ): self {
        return new self(
            triggerType:    'survey.submitted',
            sourceModule:   'Survey',
            organizationId: \App\Shared\Tenancy\TenantContext::getOrganizationId(),
            actorId:        $actorId ?? auth()->id(),
            actorEmail:     $response->respondent_ref,
            actorName:      null,
            actorRole:      null,
            subjectType:    'SurveyResponse',
            subjectId:      $response->id,
            subjectLabel:   "Response #{$response->id}",
            extra: [
                'survey_id'      => $response->survey_id,
                'survey_slug'    => $response->survey?->slug,
                'respondent_ref' => $response->respondent_ref,
            ],
            requestId: request()->header('X-Request-Id', (string) \Str::uuid()),
        );
    }

    public static function forSurveyResult(
        \Modules\Survey\app\Models\SurveyResult $result,
    ): self {
        return new self(
            triggerType:    'survey.result_calculated',
            sourceModule:   'Survey',
            organizationId: \App\Shared\Tenancy\TenantContext::getOrganizationId(),
            actorId:        null,
            actorEmail:     $result->response?->respondent_ref,
            actorName:      null,
            actorRole:      null,
            subjectType:    'SurveyResponse',
            subjectId:      $result->response_id,
            subjectLabel:   "Result #{$result->id}",
            extra: [
                'survey_id'     => $result->survey_id,
                'band_code'     => $result->band_code,
                'overall_score' => $result->overall_score,  // float, từ SurveyResult
                'weight_version'=> $result->weight_version,
            ],
            requestId: (string) \Str::uuid(),
        );
    }

    // ── Resolve helper ───────────────────────────────────────────

    /**
     * Truy cập field theo dot-notation.
     * $payload->resolve('extra.band_code')
     */
    public function resolve(string $field): mixed
    {
        return match(true) {
            $field === 'trigger.type'       => $this->triggerType,
            $field === 'trigger.module'     => $this->sourceModule,
            $field === 'actor.id'           => $this->actorId,
            $field === 'actor.email'        => $this->actorEmail,
            $field === 'actor.role'         => $this->actorRole,
            $field === 'subject.type'       => $this->subjectType,
            $field === 'subject.id'         => $this->subjectId,
            str_starts_with($field, 'extra.') => $this->extra[substr($field, 6)] ?? null,
            default                          => null,
        };
    }

    /**
     * Template rendering cho email subject, notification body, v.v.
     * render('{actor.email}') → 'user@example.com'
     */
    public function render(string $template): string
    {
        return preg_replace_callback('/\{([^}]+)\}/', function ($m) {
            $val = $this->resolve($m[1]);
            return $val !== null ? (string) $val : $m[0];
        }, $template);
    }
}

// Modules/WorkflowAutomation/app/Data/ActionResult.php
class ActionResult extends Data
{
    public function __construct(
        public readonly bool    $success,
        public readonly ?string $errorMessage = null,
        public readonly int     $durationMs   = 0,
        public readonly array   $meta         = [],
    ) {}

    public static function ok(int $ms = 0, array $meta = []): self
    {
        return new self(success: true, durationMs: $ms, meta: $meta);
    }

    public static function fail(string $error, int $ms = 0): self
    {
        return new self(success: false, errorMessage: $error, durationMs: $ms);
    }
}
```

---

## 4. Database Schema

Schema dùng Laravel migration format (`Schema::create`). SQLite-compatible — tránh dùng tính năng MySQL-only như `FULLTEXT` index hay `JSON` column type.

**Nguyên tắc**: Không dùng `TEXT`/`JSON` column cho dữ liệu cần query hoặc filter. Mọi config có cấu trúc đều được normalize thành bảng riêng với typed columns.

| Thay vì | Dùng |
|---------|------|
| `trigger_config TEXT (JSON blob)` | `workflow_trigger_params` table |
| `webhook_headers TEXT (key=value)` | `workflow_step_headers` table |

### 4.1 `workflows`

```php
Schema::create('workflows', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('organization_id')->index(); // multi-tenant scope

    $table->string('name', 191);
    $table->string('description', 500)->nullable();

    // Trigger
    $table->string('trigger_type', 64);
    // 'survey.submitted', 'lead.created', 'user.registered', 'manual'
    // trigger_config KHÔNG còn ở đây — xem workflow_trigger_params (4.6)

    // Condition logic
    $table->tinyInteger('condition_match')->unsigned()->default(1);
    // 1=ALL(AND)  2=ANY(OR)  3=NONE(luôn pass)

    // Cooldown
    $table->tinyInteger('cooldown_type')->unsigned()->default(0);
    // 0=none  1=once_per_subject  2=per_subject_per_day
    // 3=per_subject_per_hour  4=global_per_day

    $table->boolean('is_active')->default(false);
    $table->tinyInteger('priority')->unsigned()->default(5); // 1=cao nhất

    // Aggregate stats
    $table->unsignedInteger('run_count')->default(0);
    $table->dateTime('last_run_at')->nullable();
    $table->tinyInteger('last_run_status')->unsigned()->nullable();

    $table->unsignedBigInteger('created_by')->nullable();
    $table->unsignedBigInteger('updated_by')->nullable();
    $table->timestamps();

    $table->index(['organization_id', 'trigger_type', 'is_active'], 'idx_org_trigger');
    $table->index(['organization_id', 'is_active', 'priority'],     'idx_org_priority');
});
```

### 4.2 `workflow_conditions`

```php
Schema::create('workflow_conditions', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('workflow_id');
    $table->tinyInteger('sort_order')->unsigned()->default(0);

    $table->string('field', 128);
    // dot-notation: 'extra.band_code', 'actor.role', 'subject.type'

    $table->string('operator', 32);
    // '='  '!='  '>'  '>='  '<'  '<='
    // 'in'  'not_in'  'contains'  'starts_with'
    // 'is_empty'  'is_not_empty'

    $table->string('value', 500)->nullable();
    // Danh sách dùng '|' separator: 'AI_READY|DIGITAL_FOUNDATION'

    $table->tinyInteger('value_type')->unsigned()->default(1);
    // 1=string  2=integer  3=decimal  4=boolean

    $table->timestamp('created_at')->nullable();

    $table->index(['workflow_id', 'sort_order']);
});
```

### 4.3 `workflow_steps`

```php
Schema::create('workflow_steps', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('workflow_id');
    $table->tinyInteger('sort_order')->unsigned()->default(0);

    $table->string('action_type', 64);
    // 'email.send'  'notification.send'  'subject.update'  'webhook.call'
    // 'lead.create'  'lead.update_stage'  'user.assign_tag'

    // Email
    $table->string('email_to', 500)->nullable();        // template: '{actor.email}'
    $table->string('email_subject', 191)->nullable();   // template: 'Kết quả {extra.band_code}'
    $table->string('email_template', 128)->nullable();  // blade: 'survey::emails.result'

    // Notification
    $table->string('notif_title', 191)->nullable();
    $table->string('notif_body', 500)->nullable();
    $table->string('notif_target', 128)->nullable();
    // 'actor'  'admin'  'user:{id}'  'role:manager'

    // Subject update
    $table->string('update_model', 64)->nullable();   // 'SurveyResponse'  'Lead'
    $table->string('update_field', 64)->nullable();
    $table->string('update_value', 255)->nullable();  // template hoặc literal

    // Webhook
    $table->string('webhook_url', 2000)->nullable();
    $table->tinyInteger('webhook_method')->unsigned()->nullable(); // 1=GET 2=POST 3=PUT 4=PATCH
    $table->string('webhook_secret', 128)->nullable();
    // webhook_headers KHÔNG còn ở đây — xem workflow_step_headers (4.7)

    // Module-specific (thêm migration mới khi build module)
    $table->string('lead_status', 64)->nullable();
    $table->string('lead_source', 64)->nullable();
    $table->unsignedBigInteger('lead_assigned_to')->nullable();
    $table->string('user_tag', 64)->nullable();
    $table->string('user_status', 32)->nullable();

    $table->unsignedSmallInteger('delay_minutes')->default(0);

    $table->timestamps();

    $table->index(['workflow_id', 'sort_order']);
});
```

### 4.4 `workflow_executions`

```php
Schema::create('workflow_executions', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('workflow_id');
    $table->unsignedBigInteger('organization_id')->index(); // multi-tenant scope
    $table->char('run_id', 36)->unique();
    // run_id generate TRƯỚC khi dispatch — dùng để idempotency check

    $table->string('trigger_type', 64);
    $table->string('source_module', 64)->nullable();
    $table->string('subject_type', 64)->nullable();
    $table->unsignedBigInteger('subject_id')->nullable();
    $table->unsignedBigInteger('actor_id')->nullable();

    $table->tinyInteger('status')->unsigned();
    // 1=pass  2=skip  3=fail  4=partial  5=scheduled (delayed steps)

    $table->string('skip_reason', 64)->nullable();
    // 'cooldown'  'condition_failed'  'inactive'  'duplicate_run_id'

    $table->boolean('condition_result')->nullable();
    $table->tinyInteger('steps_total')->unsigned()->default(0);
    $table->tinyInteger('steps_success')->unsigned()->default(0);
    $table->tinyInteger('steps_failed')->unsigned()->default(0);
    $table->tinyInteger('steps_scheduled')->unsigned()->default(0); // delayed steps
    $table->unsignedSmallInteger('duration_ms')->nullable();

    $table->dateTime('triggered_at', 3);
    $table->dateTime('executed_at', 3)->nullable();
    $table->dateTime('finished_at', 3)->nullable();

    $table->timestamp('created_at')->nullable();

    $table->index(['workflow_id', 'triggered_at']);
    $table->index(['organization_id', 'triggered_at']);
    $table->index(['status', 'triggered_at']);
    $table->index(['subject_type', 'subject_id', 'triggered_at']);
});
```

### 4.5 `workflow_execution_steps`

```php
Schema::create('workflow_execution_steps', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('execution_id');
    $table->unsignedBigInteger('step_id');
    $table->tinyInteger('sort_order')->unsigned()->default(0);
    $table->string('action_type', 64);
    $table->tinyInteger('status')->unsigned();
    // 1=success  2=skipped  3=failed  4=scheduled
    $table->string('error_message', 500)->nullable();
    $table->unsignedSmallInteger('duration_ms')->nullable();
    $table->tinyInteger('attempts')->unsigned()->default(1);
    $table->dateTime('executed_at', 3)->nullable();
    $table->timestamp('created_at')->nullable();

    $table->index(['execution_id', 'sort_order']);
});
```

### 4.6 `workflow_trigger_params` — thay thế `trigger_config TEXT`

Mỗi workflow có thể có nhiều trigger params (survey_id, band_code, score_min...).
Normalized thay vì JSON blob → query được SQL, index được, type-safe.

```php
Schema::create('workflow_trigger_params', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('workflow_id');

    $table->string('param_key', 64);
    // 'survey_id'  'band_code'  'score_min'  'source'  'stage_to'

    $table->string('param_value', 255)->nullable();
    // Luôn lưu string; cast về đúng type khi đọc dựa vào param_type

    $table->tinyInteger('param_type')->unsigned()->default(1);
    // 1=string  2=integer  3=decimal  4=boolean

    $table->index(['workflow_id', 'param_key'], 'idx_trigger_params');
    // Index (workflow_id, param_key) cho phép query:
    // WHERE workflow_id = X AND param_key = 'survey_id' AND param_value = '5'
});
```

**Ưu điểm so với JSON**:
- `WHERE param_key = 'survey_id' AND param_value = '5'` — index hit trực tiếp
- Không cần decode string ở application layer
- Có thể thêm constraints (unique key per workflow, foreign keys)
- Admin UI đọc/ghi từng param rõ ràng, không cần JSON parser

### 4.7 `workflow_step_headers` — thay thế `webhook_headers TEXT`

```php
Schema::create('workflow_step_headers', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('step_id');

    $table->string('header_key', 128);
    // 'Authorization'  'X-Api-Key'  'Content-Type'

    $table->string('header_value', 500);
    // 'Bearer {extra.token}'  'application/json'

    $table->index('step_id');
});
```

---

## 5. Enums

```php
// WorkflowStatus.php
enum WorkflowStatus: int
{
    case Pass      = 1;
    case Skip      = 2;
    case Fail      = 3;
    case Partial   = 4;
    case Scheduled = 5; // tất cả steps đều là delayed

    public function label(): string {
        return match($this) {
            self::Pass      => 'Thành công',
            self::Skip      => 'Bỏ qua',
            self::Fail      => 'Lỗi',
            self::Partial   => 'Một phần',
            self::Scheduled => 'Đã lên lịch',
        };
    }
    public function badge(): string {
        return match($this) {
            self::Pass      => 'badge-success',
            self::Skip      => 'badge-ghost',
            self::Fail      => 'badge-error',
            self::Partial   => 'badge-warning',
            self::Scheduled => 'badge-info',
        };
    }
}

// ConditionMatch.php
enum ConditionMatch: int
{
    case All  = 1; // AND
    case Any  = 2; // OR
    case None = 3; // không condition → luôn pass
}

// CooldownType.php
enum CooldownType: int
{
    case None              = 0;
    case OncePerSubject    = 1;
    case PerSubjectPerDay  = 2;
    case PerSubjectPerHour = 3;
    case GlobalPerDay      = 4;

    public function ttlSeconds(): int {
        return match($this) {
            self::None              => 0,
            self::OncePerSubject    => 365 * 86400, // 1 năm — thực tế "mãi mãi" nhưng có TTL
            self::PerSubjectPerDay  => 86400,
            self::PerSubjectPerHour => 3600,
            self::GlobalPerDay      => 86400,
        };
    }
    public function label(): string {
        return match($this) {
            self::None              => 'Không giới hạn',
            self::OncePerSubject    => 'Mỗi đối tượng 1 lần duy nhất',
            self::PerSubjectPerDay  => 'Mỗi đối tượng 1 lần/ngày',
            self::PerSubjectPerHour => 'Mỗi đối tượng 1 lần/giờ',
            self::GlobalPerDay      => 'Toàn workflow 1 lần/ngày',
        };
    }
}

// OperatorType.php — dùng cho ConditionEvaluator và Builder UI
enum OperatorType: string
{
    case Eq          = '=';
    case Neq         = '!=';
    case Gt          = '>';
    case Gte         = '>=';
    case Lt          = '<';
    case Lte         = '<=';
    case In          = 'in';
    case NotIn       = 'not_in';
    case Contains    = 'contains';
    case StartsWith  = 'starts_with';
    case IsEmpty     = 'is_empty';
    case IsNotEmpty  = 'is_not_empty';

    /** Types mà operator này áp dụng — Builder UI dùng để filter dropdown */
    public function applicableTypes(): array {
        return match($this) {
            self::Eq, self::Neq               => ['string','integer','decimal','boolean'],
            self::Gt, self::Gte, self::Lt,
            self::Lte                          => ['integer','decimal'],
            self::In, self::NotIn,
            self::Contains, self::StartsWith,
            self::IsEmpty, self::IsNotEmpty    => ['string'],
        };
    }

    public function label(): string {
        return match($this) {
            self::Eq         => 'Bằng',
            self::Neq        => 'Khác',
            self::Gt         => 'Lớn hơn',
            self::Gte        => 'Lớn hơn hoặc bằng',
            self::Lt         => 'Nhỏ hơn',
            self::Lte        => 'Nhỏ hơn hoặc bằng',
            self::In         => 'Thuộc danh sách (|)',
            self::NotIn      => 'Không thuộc danh sách',
            self::Contains   => 'Chứa',
            self::StartsWith => 'Bắt đầu bằng',
            self::IsEmpty    => 'Trống',
            self::IsNotEmpty => 'Không trống',
        };
    }
}
```

---

## 6. Core Engine

### 6.1 `TriggerRegistry`

```php
// Modules/WorkflowAutomation/app/Core/TriggerRegistry.php
final class TriggerRegistry
{
    private array $triggers = [];

    public function register(TriggerSource $trigger): void
    {
        $this->triggers[$trigger->type()] = $trigger;
    }

    public function get(string $type): ?TriggerSource
    {
        return $this->triggers[$type] ?? null;
    }

    public function groupedByModule(): array
    {
        $grouped = [];
        foreach ($this->triggers as $trigger) {
            $grouped[$trigger->module()][] = [
                'type'             => $trigger->type(),
                'label'            => $trigger->label(),
                'config_fields'    => $trigger->configFields(),
                'available_fields' => $trigger->availableFields(),
            ];
        }
        return $grouped;
    }

    /**
     * Tìm tất cả workflows active match trigger_type + organization_id,
     * sau đó filter tiếp bằng matches() của từng TriggerSource.
     *
     * Eager-load triggerParams để tránh N+1 query.
     * Không còn JSON decode — params đã là typed values từ DB.
     */
    public function matchingWorkflows(TriggerPayload $payload): \Illuminate\Support\Collection
    {
        $query = Workflow::with('triggerParams')   // eager load — 1 extra query thay vì N
            ->where('is_active', 1)
            ->where('trigger_type', $payload->triggerType)
            ->orderBy('priority');

        if ($payload->organizationId) {
            $query->where('organization_id', $payload->organizationId);
        }

        return $query->get()->filter(function (Workflow $workflow) use ($payload) {
            $trigger = $this->get($workflow->trigger_type);
            if (!$trigger) return false;
            // parsedParams() trả array ['survey_id' => 5, 'band_code' => 'AI_READY']
            // giá trị đã được cast đúng type — không có JSON decode
            return $trigger->matches($payload, $workflow->parsedParams());
        });
    }
}
```

### 6.2 `ActionRegistry`

```php
// Modules/WorkflowAutomation/app/Core/ActionRegistry.php
final class ActionRegistry
{
    private array $executors = [];

    public function register(ActionExecutor $executor): void
    {
        $this->executors[$executor->type()] = $executor;
    }

    public function get(string $type): ?ActionExecutor
    {
        return $this->executors[$type] ?? null;
    }

    public function groupedByModule(): array
    {
        $grouped = [];
        foreach ($this->executors as $executor) {
            $grouped[$executor->module()][] = [
                'type'          => $executor->type(),
                'label'         => $executor->label(),
                'config_fields' => $executor->stepConfigFields(),
            ];
        }
        return $grouped;
    }
}
```

### 6.3 `SubjectRegistry`

```php
// Modules/WorkflowAutomation/app/Core/SubjectRegistry.php
final class SubjectRegistry
{
    private array $subjects = [];

    public function register(string $type, string $fqcn, string $label, array $updatableFields): void
    {
        $this->subjects[$type] = compact('fqcn', 'label', 'updatableFields');
    }

    public function get(string $type): ?array { return $this->subjects[$type] ?? null; }
    public function all(): array { return $this->subjects; }

    public function resolve(string $type, TriggerPayload $payload): ?object
    {
        $config = $this->get($type);
        if (!$config) return null;
        $class = $config['fqcn'];
        if (!class_exists($class)) return null;
        if (in_array(WorkflowSubject::class, class_implements($class), true)) {
            return $class::resolveFromPayload($payload);
        }
        return $class::find($payload->subjectId);
    }
}
```

### 6.4 `ConditionEvaluator`

```php
// Modules/WorkflowAutomation/app/Core/ConditionEvaluator.php
final class ConditionEvaluator
{
    public function evaluate(Workflow $workflow, TriggerPayload $payload): bool
    {
        $match      = ConditionMatch::from($workflow->condition_match);
        $conditions = $workflow->conditions;

        if ($match === ConditionMatch::None || $conditions->isEmpty()) return true;

        $results = $conditions->map(fn($c) => $this->check($c, $payload));

        return match ($match) {
            ConditionMatch::All => $results->every(fn($r) => $r),
            ConditionMatch::Any => $results->contains(fn($r) => $r),
            default             => true,
        };
    }

    private function check(WorkflowCondition $cond, TriggerPayload $payload): bool
    {
        $actual = $payload->resolve($cond->field);

        $expected = match ($cond->value_type) {
            2 => (int)   $cond->value,
            3 => (float) $cond->value,
            4 => in_array(strtolower((string) $cond->value), ['true', '1', 'yes']),
            default => $cond->value,
        };

        return match ($cond->operator) {
            '='           => $actual == $expected,
            '!='          => $actual != $expected,
            '>'           => is_numeric($actual) && $actual > $expected,
            '>='          => is_numeric($actual) && $actual >= $expected,
            '<'           => is_numeric($actual) && $actual < $expected,
            '<='          => is_numeric($actual) && $actual <= $expected,
            'in'          => in_array($actual, explode('|', (string) $cond->value)),
            'not_in'      => !in_array($actual, explode('|', (string) $cond->value)),
            'contains'    => str_contains((string) $actual, (string) $expected),
            'starts_with' => str_starts_with((string) $actual, (string) $expected),
            'is_empty'    => empty($actual),
            'is_not_empty'=> !empty($actual),
            default       => false,
        };
    }
}
```

### 6.5 `CooldownGuard`

```php
// Modules/WorkflowAutomation/app/Core/CooldownGuard.php
final class CooldownGuard
{
    public function __construct(
        private readonly \Illuminate\Contracts\Cache\Repository $cache
    ) {}

    public function allow(Workflow $workflow, TriggerPayload $payload): bool
    {
        $type = CooldownType::from($workflow->cooldown_type);
        if ($type === CooldownType::None) return true;

        $key = $this->key($type, $workflow, $payload);

        if ($this->cache->has($key)) return false;
        $this->cache->put($key, 1, $type->ttlSeconds()); // luôn dùng TTL, không dùng forever()
        return true;
    }

    private function key(CooldownType $type, Workflow $workflow, TriggerPayload $payload): string
    {
        $wid = $workflow->id;
        $sid = $payload->subjectId ?? 'null';
        $oid = $payload->organizationId ?? 'null';

        return match ($type) {
            CooldownType::OncePerSubject    => "wf:cd:once:{$oid}:{$wid}:{$sid}",
            CooldownType::PerSubjectPerDay  => "wf:cd:day:{$oid}:{$wid}:{$sid}:" . now()->format('Ymd'),
            CooldownType::PerSubjectPerHour => "wf:cd:hr:{$oid}:{$wid}:{$sid}:" . now()->format('YmdH'),
            CooldownType::GlobalPerDay      => "wf:cd:gday:{$oid}:{$wid}:" . now()->format('Ymd'),
            default                          => "wf:cd:none:{$wid}",
        };
    }
}
```

---

## 7. Trigger Implementations

### 7.1 Survey triggers (trong Modules/Survey)

```php
// Modules/Survey/app/WorkflowTriggers/SurveySubmittedTrigger.php
class SurveySubmittedTrigger implements TriggerSource
{
    public function type(): string   { return 'survey.submitted'; }
    public function label(): string  { return 'Survey được nộp'; }
    public function module(): string { return 'Survey'; }

    public function availableFields(): array
    {
        return [
            ['key' => 'extra.survey_id',      'label' => 'Survey ID',       'type' => 'integer'],
            ['key' => 'extra.respondent_ref',  'label' => 'Email người nộp', 'type' => 'string'],
            ['key' => 'actor.email',           'label' => 'Email actor',     'type' => 'string'],
        ];
    }

    public function configFields(): array
    {
        return [
            [
                'key'      => 'survey_id',
                'label'    => 'Áp dụng cho Survey',
                'type'     => 'model_select',
                'model'    => 'surveys',
                'required' => false,
                'hint'     => 'Để trống = tất cả survey',
            ],
        ];
    }

    public function matches(TriggerPayload $payload, array $config): bool
    {
        if (!empty($config['survey_id'])) {
            return (int) $config['survey_id'] === ($payload->extra['survey_id'] ?? null);
        }
        return true;
    }
}

// Modules/Survey/app/WorkflowTriggers/SurveyResultBandTrigger.php
class SurveyResultBandTrigger implements TriggerSource
{
    public function type(): string   { return 'survey.result_calculated'; }
    public function label(): string  { return 'Kết quả Survey — có band code'; }
    public function module(): string { return 'Survey'; }

    public function availableFields(): array
    {
        return [
            ['key' => 'extra.band_code',     'label' => 'Band code',    'type' => 'string'],
            ['key' => 'extra.overall_score', 'label' => 'Điểm tổng %', 'type' => 'decimal'],
            ['key' => 'extra.survey_id',     'label' => 'Survey ID',    'type' => 'integer'],
        ];
    }

    public function configFields(): array
    {
        return [
            ['key' => 'survey_id',  'label' => 'Survey',              'type' => 'model_select',
             'model' => 'surveys',  'required' => false],
            ['key' => 'band_code',  'label' => 'Band code',           'type' => 'text',
             'required' => false,   'hint' => 'Để trống = mọi band'],
            ['key' => 'score_min',  'label' => 'Score tối thiểu (%)', 'type' => 'number',
             'required' => false],
        ];
    }

    public function matches(TriggerPayload $payload, array $config): bool
    {
        if (!empty($config['survey_id'])
            && (int)$config['survey_id'] !== ($payload->extra['survey_id'] ?? null)) {
            return false;
        }
        if (!empty($config['band_code'])
            && $config['band_code'] !== ($payload->extra['band_code'] ?? null)) {
            return false;
        }
        if (!empty($config['score_min'])
            && ($payload->extra['overall_score'] ?? 0) < (float)$config['score_min']) {
            return false;
        }
        return true;
    }
}
```

### 7.2 Built-in: `ManualTrigger`

```php
// Modules/WorkflowAutomation/app/Triggers/ManualTrigger.php
class ManualTrigger implements TriggerSource
{
    public function type(): string   { return 'manual'; }
    public function label(): string  { return 'Kích hoạt thủ công'; }
    public function module(): string { return 'Core'; }

    public function availableFields(): array { return []; }
    public function configFields(): array    { return []; }
    public function matches(TriggerPayload $payload, array $config): bool { return true; }
}
```

### 7.3 Lead triggers (template — điền khi build module Lead)

```php
// Modules/Lead/app/WorkflowTriggers/LeadCreatedTrigger.php
class LeadCreatedTrigger implements TriggerSource
{
    public function type(): string   { return 'lead.created'; }
    public function label(): string  { return 'Lead mới được tạo'; }
    public function module(): string { return 'Lead'; }

    public function availableFields(): array
    {
        return [
            ['key' => 'extra.source',     'label' => 'Nguồn lead',    'type' => 'string'],
            ['key' => 'extra.lead_score', 'label' => 'Lead score',    'type' => 'integer'],
            ['key' => 'extra.stage',      'label' => 'Stage ban đầu', 'type' => 'string'],
            ['key' => 'actor.email',      'label' => 'Email',         'type' => 'string'],
        ];
    }

    public function configFields(): array
    {
        return [
            ['key' => 'source', 'label' => 'Nguồn', 'type' => 'select',
             'options' => ['survey', 'manual', 'import', 'api'], 'required' => false],
        ];
    }

    public function matches(TriggerPayload $payload, array $config): bool
    {
        if (!empty($config['source']) && $config['source'] !== ($payload->extra['source'] ?? null)) {
            return false;
        }
        return true;
    }
}
```

### 7.4 User triggers (template)

```php
// Modules/User/app/WorkflowTriggers/UserRegisteredTrigger.php
class UserRegisteredTrigger implements TriggerSource
{
    public function type(): string   { return 'user.registered'; }
    public function label(): string  { return 'Người dùng đăng ký mới'; }
    public function module(): string { return 'User'; }

    public function availableFields(): array
    {
        return [
            ['key' => 'actor.email', 'label' => 'Email',        'type' => 'string'],
            ['key' => 'actor.role',  'label' => 'Role',         'type' => 'string'],
            ['key' => 'extra.plan',  'label' => 'Gói đăng ký', 'type' => 'string'],
        ];
    }

    public function configFields(): array { return []; }
    public function matches(TriggerPayload $payload, array $config): bool { return true; }
}
```

---

## 8. Action Executor Implementations

### 8.1 Built-in: `SendEmailExecutor`

```php
// Modules/WorkflowAutomation/app/Executors/SendEmailExecutor.php
class SendEmailExecutor implements ActionExecutor
{
    public function type(): string   { return 'email.send'; }
    public function label(): string  { return 'Gửi email'; }
    public function module(): string { return 'Core'; }

    public function stepConfigFields(): array
    {
        return [
            ['key' => 'email_to',       'label' => 'Gửi đến',        'type' => 'text',
             'hint' => 'Template: {actor.email} hoặc địa chỉ cố định'],
            ['key' => 'email_subject',  'label' => 'Tiêu đề',        'type' => 'text',
             'hint' => 'Template: Kết quả {extra.band_code}'],
            ['key' => 'email_template', 'label' => 'Blade template',  'type' => 'text'],
        ];
    }

    public function execute(WorkflowStep $step, TriggerPayload $payload): ActionResult
    {
        $start = microtime(true);
        try {
            $to = $payload->render($step->email_to ?? '');
            if (empty($to)) return ActionResult::fail('email_to empty after render');

            $emails = array_filter(
                array_map('trim', explode(',', $to)),
                fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)
            );
            if (empty($emails)) return ActionResult::fail("No valid email: {$to}");

            $subject  = $payload->render($step->email_subject ?? '');
            $template = $step->email_template ?? 'workflowautomation::emails.generic';

            \Mail::to($emails)->queue(new WorkflowMail($subject, $template, $payload));
            return ActionResult::ok((int)((microtime(true) - $start) * 1000));
        } catch (\Throwable $e) {
            return ActionResult::fail($e->getMessage(), (int)((microtime(true) - $start) * 1000));
        }
    }
}
```

### 8.2 Built-in: `SendNotificationExecutor`

```php
// Modules/WorkflowAutomation/app/Executors/SendNotificationExecutor.php
class SendNotificationExecutor implements ActionExecutor
{
    public function type(): string   { return 'notification.send'; }
    public function label(): string  { return 'Gửi thông báo nội bộ'; }
    public function module(): string { return 'Core'; }

    public function stepConfigFields(): array
    {
        return [
            ['key' => 'notif_target', 'label' => 'Gửi đến', 'type' => 'text',
             'hint' => 'actor | admin | user:{id} | role:sales'],
            ['key' => 'notif_title',  'label' => 'Tiêu đề', 'type' => 'text'],
            ['key' => 'notif_body',   'label' => 'Nội dung','type' => 'textarea',
             'hint' => 'Template: {actor.email} đạt {extra.overall_score}%'],
        ];
    }

    public function execute(WorkflowStep $step, TriggerPayload $payload): ActionResult
    {
        $start = microtime(true);
        try {
            $target = $step->notif_target ?? 'admin';
            $title  = $payload->render($step->notif_title ?? '');
            $body   = $payload->render($step->notif_body  ?? '');

            $users = $this->resolveTargetUsers($target, $payload);
            foreach ($users as $user) {
                $user->notify(new \Modules\WorkflowAutomation\Notifications\WorkflowNotification(
                    $title, $body
                ));
            }

            return ActionResult::ok(
                (int)((microtime(true) - $start) * 1000),
                ['recipients' => count($users)]
            );
        } catch (\Throwable $e) {
            return ActionResult::fail($e->getMessage(), (int)((microtime(true) - $start) * 1000));
        }
    }

    private function resolveTargetUsers(string $target, TriggerPayload $payload): array
    {
        return match(true) {
            $target === 'actor' && $payload->actorId
                => [\App\Models\User::find($payload->actorId)],
            $target === 'admin'
                => \App\Models\User::role('system_admin')->get()->all(),
            str_starts_with($target, 'user:')
                => [\App\Models\User::find((int) substr($target, 5))],
            str_starts_with($target, 'role:')
                => \App\Models\User::role(substr($target, 5))->get()->all(),
            default => [],
        };
    }
}
```

### 8.3 Built-in: `UpdateSubjectExecutor`

```php
// Modules/WorkflowAutomation/app/Executors/UpdateSubjectExecutor.php
class UpdateSubjectExecutor implements ActionExecutor
{
    public function __construct(private readonly SubjectRegistry $subjects) {}

    public function type(): string   { return 'subject.update'; }
    public function label(): string  { return 'Cập nhật dữ liệu'; }
    public function module(): string { return 'Core'; }

    public function stepConfigFields(): array
    {
        $subjectOptions = collect($this->subjects->all())
            ->map(fn($s, $type) => ['value' => $type, 'label' => $s['label']])
            ->values()->all();

        return [
            ['key' => 'update_model', 'label' => 'Đối tượng', 'type' => 'select',
             'options_dynamic' => $subjectOptions],
            ['key' => 'update_field', 'label' => 'Field',     'type' => 'select',
             'options_from_model' => true],
            ['key' => 'update_value', 'label' => 'Giá trị',  'type' => 'text',
             'hint' => 'Template: {extra.band_code} hoặc giá trị cố định'],
        ];
    }

    public function execute(WorkflowStep $step, TriggerPayload $payload): ActionResult
    {
        $start = microtime(true);
        try {
            $subjectType = $step->update_model;
            $config      = $this->subjects->get($subjectType);
            if (!$config) return ActionResult::fail("Unknown subject type: {$subjectType}");

            $model = $this->subjects->resolve($subjectType, $payload);
            if (!$model) return ActionResult::fail("Cannot resolve {$subjectType} from payload");

            $field = $step->update_field;
            $allowedFields = array_column($config['updatableFields'], 'field');
            if (!in_array($field, $allowedFields)) {
                return ActionResult::fail("Field '{$field}' not updatable on {$subjectType}");
            }

            $value = $payload->render($step->update_value ?? '');
            $model->update([$field => $value]);

            return ActionResult::ok(
                (int)((microtime(true) - $start) * 1000),
                ['updated' => "{$subjectType}.{$field} = {$value}"]
            );
        } catch (\Throwable $e) {
            return ActionResult::fail($e->getMessage(), (int)((microtime(true) - $start) * 1000));
        }
    }
}
```

### 8.4 Built-in: `CallWebhookExecutor`

```php
// Modules/WorkflowAutomation/app/Executors/CallWebhookExecutor.php
class CallWebhookExecutor implements ActionExecutor
{
    public function type(): string   { return 'webhook.call'; }
    public function label(): string  { return 'Gọi webhook ngoài'; }
    public function module(): string { return 'Core'; }

    public function stepConfigFields(): array
    {
        return [
            ['key' => 'webhook_url',     'label' => 'URL',         'type' => 'url'],
            ['key' => 'webhook_method',  'label' => 'Method',      'type' => 'select',
             'options' => [['value' => 2, 'label' => 'POST'], ['value' => 3, 'label' => 'PUT'],
                           ['value' => 1, 'label' => 'GET']]],
            ['key' => 'webhook_secret',  'label' => 'HMAC secret', 'type' => 'password', 'required' => false],
            ['key' => 'webhook_headers', 'label' => 'Headers',     'type' => 'textarea',
             'hint' => 'key=value, mỗi dòng 1 header', 'required' => false],
        ];
    }

    public function execute(WorkflowStep $step, TriggerPayload $payload): ActionResult
    {
        $start = microtime(true);
        try {
            $url = $payload->render($step->webhook_url ?? '');
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return ActionResult::fail("Invalid URL: {$url}");
            }

            $body = json_encode([
                'workflow_trigger' => $payload->triggerType,
                'source_module'    => $payload->sourceModule,
                'organization_id'  => $payload->organizationId,
                'subject_type'     => $payload->subjectType,
                'subject_id'       => $payload->subjectId,
                'actor_email'      => $payload->actorEmail,
                'extra'            => $payload->extra,
                'fired_at'         => $payload->firedAt->format('c'),
            ]);

            // Load từ relationship — không parse TEXT
            $headers = $step->headers->pluck('header_value', 'header_key')->all();
            if ($step->webhook_secret) {
                $headers['X-Workflow-Signature'] = hash_hmac('sha256', $body, $step->webhook_secret);
            }

            $method   = match ($step->webhook_method) { 2 => 'POST', 3 => 'PUT', 4 => 'PATCH', default => 'GET' };
            // Rate limiting outbound: nếu nhiều workflow cùng gọi 1 domain,
            // dùng throttle cache key per domain để tránh flood.
            // Hiện tại: không implement — chấp nhận rủi ro với giả định
            // số workflow concurrent thấp (< 20/s). Nếu scale lớn hơn,
            // thêm: RateLimiter::attempt("webhook:{$host}", 10, fn() => ..., 60)
            $response = \Http::withHeaders($headers)->timeout(15)->retry(2, 500)
                ->{strtolower($method)}($url, json_decode($body, true));

            $ms = (int)((microtime(true) - $start) * 1000);
            if (!$response->successful()) {
                return ActionResult::fail("HTTP {$response->status()}: " . substr($response->body(), 0, 200), $ms);
            }
            return ActionResult::ok($ms, ['http_status' => $response->status()]);
        } catch (\Throwable $e) {
            return ActionResult::fail($e->getMessage(), (int)((microtime(true) - $start) * 1000));
        }
    }

}
```

### 8.5 Module Lead: `CreateLeadExecutor` (template)

```php
// Modules/Lead/app/WorkflowExecutors/CreateLeadExecutor.php
class CreateLeadExecutor implements ActionExecutor
{
    public function type(): string   { return 'lead.create'; }
    public function label(): string  { return 'Tạo Lead mới'; }
    public function module(): string { return 'Lead'; }

    public function stepConfigFields(): array
    {
        return [
            ['key' => 'lead_source',      'label' => 'Nguồn',      'type' => 'select',
             'options' => ['survey', 'manual', 'api']],
            ['key' => 'lead_assigned_to', 'label' => 'Assign cho', 'type' => 'user_select',
             'required' => false],
        ];
    }

    public function execute(WorkflowStep $step, TriggerPayload $payload): ActionResult
    {
        $start = microtime(true);
        try {
            $email = $payload->actorEmail;
            if ($email && Lead::where('email', $email)->exists()) {
                return ActionResult::ok(0, ['skipped' => 'lead already exists']);
            }
            Lead::create([
                'email'                  => $email,
                'name'                   => $payload->actorName,
                'source'                 => $step->lead_source ?? 'workflow',
                'assigned_to'            => $step->lead_assigned_to,
                'organization_id'        => $payload->organizationId,
                // Không dùng JSON meta — dùng typed columns trên bảng leads
                'workflow_trigger_type'  => $payload->triggerType,
                'workflow_subject_id'    => $payload->subjectId,
                'band_code'              => $payload->extra['band_code'] ?? null,
            ]);
            return ActionResult::ok((int)((microtime(true) - $start) * 1000));
        } catch (\Throwable $e) {
            return ActionResult::fail($e->getMessage(), (int)((microtime(true) - $start) * 1000));
        }
    }
}
```

### 8.6 `WorkflowMail`

```php
// Modules/WorkflowAutomation/app/Mail/WorkflowMail.php
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WorkflowMail extends Mailable
{
    public function __construct(
        public readonly string         $mailSubject,
        public readonly string         $template,
        public readonly TriggerPayload $payload,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: $this->template,
            with: [
                'payload'     => $this->payload,
                'extra'       => $this->payload->extra,
                'actorEmail'  => $this->payload->actorEmail,
                'actorName'   => $this->payload->actorName,
                'subjectType' => $this->payload->subjectType,
                'subjectId'   => $this->payload->subjectId,
            ]
        );
    }
}
```

---

## 9. WorkflowDispatcher — điểm vào duy nhất

```php
// Modules/WorkflowAutomation/app/Core/WorkflowDispatcher.php
final class WorkflowDispatcher
{
    /**
     * Fire-and-forget: dispatch job async, không block request.
     * KHÔNG gọi bên trong DB transaction.
     *
     * run_id được generate ở đây — trước khi dispatch —
     * để đảm bảo idempotency nếu job bị retry.
     */
    public static function fire(TriggerPayload $payload): void
    {
        try {
            $registry  = app(TriggerRegistry::class);
            $workflows = $registry->matchingWorkflows($payload);

            foreach ($workflows as $workflow) {
                $runId = (string) \Str::uuid();
                ExecuteWorkflowAction::dispatch($workflow->id, $payload, $runId)
                    ->onQueue(config('workflow_automation.queue', 'workflows'));
            }
        } catch (\Throwable $e) {
            logger()->error('[Workflow] Dispatcher failed', [
                'trigger' => $payload->triggerType,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Dùng khi cần đảm bảo DB đã commit trước khi fire.
     */
    public static function fireAfterCommit(TriggerPayload $payload): void
    {
        \DB::afterCommit(fn() => self::fire($payload));
    }
}
```

---

## 10. Actions với lorisleiva

### 10.1 `ExecuteWorkflowAction` — job chính

```php
// Modules/WorkflowAutomation/app/Actions/ExecuteWorkflowAction.php
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Core\ActivityLogger;

class ExecuteWorkflowAction
{
    use AsAction;

    public string $jobQueue   = 'workflows';
    public int    $jobTries   = 3;
    public array  $jobBackoff = [10, 60, 300];
    public int    $jobTimeout = 120;

    public function __construct(
        private readonly ConditionEvaluator $evaluator,
        private readonly CooldownGuard      $cooldown,
        private readonly ActionRegistry     $actions,
    ) {}

    /**
     * @param int            $workflowId
     * @param TriggerPayload $payload
     * @param string         $runId  — generate sebelum dispatch để idempotency
     */
    public function handle(int $workflowId, TriggerPayload $payload, string $runId): void
    {
        // Idempotency: nếu run_id đã tồn tại, bỏ qua (retry hoặc duplicate dispatch)
        if (\DB::table('workflow_executions')->where('run_id', $runId)->exists()) {
            return;
        }

        $workflow = Workflow::with(['conditions', 'steps'])->find($workflowId);
        if (!$workflow || !$workflow->is_active) return;

        $startedAt = now();

        // 1. Cooldown
        if (!$this->cooldown->allow($workflow, $payload)) {
            $this->persist($workflow, $payload, $runId, WorkflowStatus::Skip, 'cooldown', null, [], $startedAt);
            return;
        }

        // 2. Conditions
        $condPass = $this->evaluator->evaluate($workflow, $payload);
        if (!$condPass) {
            $this->persist($workflow, $payload, $runId, WorkflowStatus::Skip, 'condition_failed', false, [], $startedAt);
            return;
        }

        // 3. Execute steps
        $steps      = $workflow->steps->sortBy('sort_order');
        $stepLogs   = [];
        $success    = $failed = $scheduled = 0;

        foreach ($steps as $step) {
            if ($step->delay_minutes > 0) {
                // Log step là "scheduled" ngay — KHÔNG bỏ qua không ghi gì
                $stepLogs[] = $this->stepLog($step, 4, null, 0); // 4=scheduled
                $scheduled++;
                ExecuteWorkflowStepAction::dispatch($step->id, $payload)
                    ->delay(now()->addMinutes($step->delay_minutes))
                    ->onQueue('workflows');
                continue;
            }

            $executor = $this->actions->get($step->action_type);
            if (!$executor) {
                $stepLogs[] = $this->stepLog($step, 3, "Unknown action: {$step->action_type}", 0);
                $failed++;
                continue;
            }

            $result = $executor->execute($step, $payload);
            $result->success ? $success++ : $failed++;
            $stepLogs[] = $this->stepLog(
                $step,
                $result->success ? 1 : 3,
                $result->errorMessage,
                $result->durationMs
            );
        }

        $status = match(true) {
            $scheduled > 0 && $success === 0 && $failed === 0 => WorkflowStatus::Scheduled,
            $failed === 0                                      => WorkflowStatus::Pass,
            $success === 0 && $scheduled === 0                 => WorkflowStatus::Fail,
            default                                            => WorkflowStatus::Partial,
        };

        $totalMs = (int)($startedAt->diffInMilliseconds(now()));
        $this->persist($workflow, $payload, $runId, $status, null, true, $stepLogs, $startedAt, $totalMs, $scheduled);

        ActivityLogger::info('WorkflowAutomation', 'workflow_executed', null, [
            'workflow_id'     => $workflow->id,
            'workflow_name'   => $workflow->name,
            'run_id'          => $runId,
            'status'          => $status->value,
            'steps_success'   => $success,
            'steps_failed'    => $failed,
            'steps_scheduled' => $scheduled,
        ]);
    }

    private function persist(
        Workflow $workflow, TriggerPayload $payload, string $runId,
        WorkflowStatus $status, ?string $skipReason, ?bool $conditionResult,
        array $stepLogs, \Carbon\Carbon $startedAt, int $totalMs = 0, int $scheduled = 0,
    ): void {
        $workflow->increment('run_count');
        $workflow->update(['last_run_at' => now(), 'last_run_status' => $status->value]);

        $execId = \DB::table('workflow_executions')->insertGetId([
            'workflow_id'       => $workflow->id,
            'organization_id'   => $payload->organizationId,
            'run_id'            => $runId,
            'trigger_type'      => $payload->triggerType,
            'source_module'     => $payload->sourceModule,
            'subject_type'      => $payload->subjectType,
            'subject_id'        => $payload->subjectId,
            'actor_id'          => $payload->actorId,
            'status'            => $status->value,
            'skip_reason'       => $skipReason,
            'condition_result'  => $conditionResult,
            'steps_total'       => count($stepLogs),
            'steps_success'     => count(array_filter($stepLogs, fn($s) => $s['status'] === 1)),
            'steps_failed'      => count(array_filter($stepLogs, fn($s) => $s['status'] === 3)),
            'steps_scheduled'   => $scheduled,
            'duration_ms'       => $totalMs,
            'triggered_at'      => $payload->firedAt->format('Y-m-d H:i:s.v'),
            'executed_at'       => $startedAt->format('Y-m-d H:i:s.v'),
            'finished_at'       => now()->format('Y-m-d H:i:s.v'),
            'created_at'        => now(),
        ]);

        if ($execId && !empty($stepLogs)) {
            foreach ($stepLogs as &$log) { $log['execution_id'] = $execId; }
            \DB::table('workflow_execution_steps')->insert($stepLogs);
        }
    }

    private function stepLog(WorkflowStep $step, int $status, ?string $error, int $ms): array
    {
        return [
            'execution_id'  => 0, // set trong persist()
            'step_id'       => $step->id,
            'sort_order'    => $step->sort_order,
            'action_type'   => $step->action_type,
            'status'        => $status,
            'error_message' => $error ? substr($error, 0, 500) : null,
            'duration_ms'   => $ms,
            'attempts'      => 1,
            'executed_at'   => now()->format('Y-m-d H:i:s.v'),
            'created_at'    => now(),
        ];
    }

    public function jobFailed(\Throwable $e): void
    {
        logger()->error('[Workflow] ExecuteWorkflowAction permanently failed', [
            'error' => $e->getMessage(),
        ]);
    }
}
```

### 10.2 `ExecuteWorkflowStepAction` — delayed step

```php
// Modules/WorkflowAutomation/app/Actions/ExecuteWorkflowStepAction.php
use Lorisleiva\Actions\Concerns\AsAction;

class ExecuteWorkflowStepAction
{
    use AsAction;

    public string $jobQueue   = 'workflows';
    public int    $jobTries   = 3;
    public array  $jobBackoff = [30, 120, 600];

    public function __construct(
        private readonly ActionRegistry $actions,
    ) {}

    public function handle(int $stepId, TriggerPayload $payload, int $executionId = 0): void
    {
        $step = WorkflowStep::find($stepId);
        if (!$step) return;

        $executor = $this->actions->get($step->action_type);
        if (!$executor) {
            logger()->warning('[Workflow] Unknown action type in delayed step', ['action_type' => $step->action_type]);
            $this->updateStepLog($executionId, $stepId, 3, 'Unknown action type', 0);
            return;
        }

        $start  = microtime(true);
        $result = $executor->execute($step, $payload);
        $ms     = (int)((microtime(true) - $start) * 1000);

        // Cập nhật lại row trong workflow_execution_steps (trước đó là status=4 "scheduled")
        $this->updateStepLog(
            $executionId,
            $stepId,
            $result->success ? 1 : 3,
            $result->errorMessage,
            $ms,
        );

        if (!$result->success) {
            logger()->error('[Workflow] Delayed step failed', [
                'step_id'      => $stepId,
                'execution_id' => $executionId,
                'action_type'  => $step->action_type,
                'error'        => $result->errorMessage,
            ]);
        }
    }

    private function updateStepLog(int $executionId, int $stepId, int $status, ?string $error, int $ms): void
    {
        if ($executionId === 0) return; // executionId không được truyền vào (backward compat)

        \DB::table('workflow_execution_steps')
            ->where('execution_id', $executionId)
            ->where('step_id', $stepId)
            ->update([
                'status'        => $status,
                'error_message' => $error ? substr($error, 0, 500) : null,
                'duration_ms'   => $ms,
                'executed_at'   => now()->format('Y-m-d H:i:s.v'),
                'attempts'      => \DB::raw('attempts + 1'),
            ]);
    }
}
```

### 10.3 `PurgeOldExecutionsAction` — scheduled cleanup

```php
// Modules/WorkflowAutomation/app/Actions/PurgeOldExecutionsAction.php
use Lorisleiva\Actions\Concerns\AsAction;

class PurgeOldExecutionsAction
{
    use AsAction;

    public function handle(): void
    {
        $retainDays = (int) config('workflow_automation.retain_execution_days', 60);
        $cutoff     = now()->subDays($retainDays);

        $execIds = \DB::table('workflow_executions')
            ->where('created_at', '<', $cutoff)
            ->pluck('id');

        if ($execIds->isEmpty()) return;

        \DB::table('workflow_execution_steps')
            ->whereIn('execution_id', $execIds)
            ->delete();

        \DB::table('workflow_executions')
            ->whereIn('id', $execIds)
            ->delete();

        ActivityLogger::info('WorkflowAutomation', 'executions_purged', null, [
            'count'   => $execIds->count(),
            'cutoff'  => $cutoff->toDateString(),
        ]);
    }

    // Đăng ký trong console.php:
    // Schedule::call(PurgeOldExecutionsAction::make())->daily();
}
```

---

## 11. Routes & Controllers

```php
// Modules/WorkflowAutomation/routes/web.php
use App\Enums\PermissionEnum as P;

// Web routes — giao diện admin
Route::prefix('dashboard/workflows')
    ->middleware(['web', 'auth', 'can:' . P::WORKFLOW_MONITOR->value])
    ->name('workflows.')
    ->group(function () {
        Route::get('/',                [WorkflowController::class, 'index'])   ->name('index');
        Route::get('/create',          [WorkflowController::class, 'create'])  ->name('create')
            ->middleware('can:' . P::WORKFLOW_EDIT->value);
        Route::post('/',               [WorkflowController::class, 'store'])   ->name('store')
            ->middleware('can:' . P::WORKFLOW_EDIT->value);
        Route::get('/{workflow}',      [WorkflowController::class, 'show'])    ->name('show');
        Route::get('/{workflow}/edit', [WorkflowController::class, 'edit'])    ->name('edit')
            ->middleware('can:' . P::WORKFLOW_EDIT->value);
        Route::put('/{workflow}',      [WorkflowController::class, 'update'])  ->name('update')
            ->middleware('can:' . P::WORKFLOW_EDIT->value);
        Route::delete('/{workflow}',   [WorkflowController::class, 'destroy']) ->name('destroy')
            ->middleware('can:' . P::WORKFLOW_FULL_CONFIG->value);
        Route::patch('/{workflow}/toggle', [WorkflowController::class, 'toggle'])->name('toggle')
            ->middleware('can:' . P::WORKFLOW_EDIT->value);
        Route::post('/{workflow}/run',     [WorkflowController::class, 'manualRun'])->name('run')
            ->middleware('can:' . P::WORKFLOW_EDIT->value);
        Route::get('/{workflow}/executions',  [WorkflowExecutionController::class, 'index'])->name('executions');
        Route::get('/executions/{execution}', [WorkflowExecutionController::class, 'show']) ->name('executions.show');
    });

// API routes — Tabulator + Builder
Route::prefix('backend/api/workflows')
    ->middleware(['web', 'auth', 'can:' . P::WORKFLOW_MONITOR->value])
    ->name('backend.api.workflows.')
    ->group(function () {
        Route::get('/',                        [WorkflowApiController::class, 'index'])        ->name('index');
        Route::get('/meta',                    [WorkflowApiController::class, 'meta'])         ->name('meta');
        Route::get('/executions',              [WorkflowApiController::class, 'executions'])   ->name('executions');
        Route::get('/stats',                   [WorkflowApiController::class, 'stats'])        ->name('stats');
        Route::get('/subject-fields/{type}',   [WorkflowApiController::class, 'subjectFields'])->name('subject-fields');
    });
```

### 11.1 `WorkflowController`

```php
// Modules/WorkflowAutomation/app/Http/Controllers/WorkflowController.php
class WorkflowController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        return view('workflowautomation::workflows.index');
    }

    public function show(Workflow $workflow): \Illuminate\View\View
    {
        $this->authorizeForOrg($workflow);
        return view('workflowautomation::workflows.show', compact('workflow'));
    }

    public function create(): \Illuminate\View\View
    {
        return view('workflowautomation::workflows.create');
    }

    public function edit(Workflow $workflow): \Illuminate\View\View
    {
        $this->authorizeForOrg($workflow);
        return view('workflowautomation::workflows.edit', compact('workflow'));
    }

    public function store(Request $request, WorkflowBuilderService $builder): \Illuminate\Http\RedirectResponse
    {
        $workflow = $builder->createFromRequest($request);
        return redirect()->route('workflows.show', $workflow)->with('success', 'Workflow đã tạo.');
    }

    public function update(Request $request, Workflow $workflow, WorkflowBuilderService $builder): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeForOrg($workflow);
        $builder->updateFromRequest($request, $workflow);
        return redirect()->route('workflows.show', $workflow)->with('success', 'Đã cập nhật.');
    }

    public function destroy(Workflow $workflow): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeForOrg($workflow);
        $workflow->delete();
        return redirect()->route('workflows.index')->with('success', 'Đã xóa.');
    }

    public function toggle(Workflow $workflow): \Illuminate\Http\JsonResponse
    {
        $this->authorizeForOrg($workflow);
        $workflow->update(['is_active' => !$workflow->is_active]);
        return response()->json(['is_active' => $workflow->is_active]);
    }

    public function manualRun(Workflow $workflow): \Illuminate\Http\JsonResponse
    {
        $this->authorizeForOrg($workflow);
        $runId   = (string) \Str::uuid();
        $payload = new TriggerPayload(
            triggerType:    'manual',
            sourceModule:   'Core',
            organizationId: TenantContext::getOrganizationId(),
            actorId:        auth()->id(),
            actorEmail:     auth()->user()?->email,
            actorName:      auth()->user()?->name,
            actorRole:      null,
            subjectType:    null,
            subjectId:      null,
            subjectLabel:   null,
            requestId:      request()->header('X-Request-Id', $runId),
        );
        ExecuteWorkflowAction::dispatch($workflow->id, $payload, $runId)->onQueue('workflows');
        return response()->json(['queued' => true, 'run_id' => $runId]);
    }

    private function authorizeForOrg(Workflow $workflow): void
    {
        if (TenantContext::isSet() && $workflow->organization_id !== TenantContext::getOrganizationId()) {
            abort(403);
        }
    }
}
```

### 11.2 `WorkflowExecutionController`

```php
// Modules/WorkflowAutomation/app/Http/Controllers/WorkflowExecutionController.php
class WorkflowExecutionController extends Controller
{
    public function index(Workflow $workflow): \Illuminate\View\View
    {
        $this->authorizeForOrg($workflow);
        return view('workflowautomation::executions.index', compact('workflow'));
    }

    public function show(WorkflowExecution $execution): \Illuminate\View\View
    {
        if (TenantContext::isSet() && $execution->organization_id !== TenantContext::getOrganizationId()) {
            abort(403);
        }
        $execution->load('steps');
        return view('workflowautomation::executions.show', compact('execution'));
    }

    private function authorizeForOrg(Workflow $workflow): void
    {
        if (TenantContext::isSet() && $workflow->organization_id !== TenantContext::getOrganizationId()) {
            abort(403);
        }
    }
}
```

### 11.3 `WorkflowApiController::meta()`

```php
public function meta(): \Illuminate\Http\JsonResponse
{
    $orgId    = TenantContext::isSet() ? TenantContext::getOrganizationId() : null;
    $cacheKey = 'wf:meta:' . ($orgId ?? 'global');

    return response()->json(\Cache::remember($cacheKey, 600, function () {
        $triggerRegistry = app(TriggerRegistry::class);
        $actionRegistry  = app(ActionRegistry::class);
        $subjectRegistry = app(SubjectRegistry::class);

        return [
            'trigger_groups' => $triggerRegistry->groupedByModule(),
            'action_groups'  => $actionRegistry->groupedByModule(),
            'subjects'       => collect($subjectRegistry->all())
                ->map(fn($s) => ['label' => $s['label'], 'fields' => $s['updatableFields']])
                ->all(),
            'operators'      => collect(OperatorType::cases())
                ->map(fn($op) => ['value' => $op->value, 'label' => $op->label(), 'types' => $op->applicableTypes()])
                ->all(),
            'cooldown_types' => collect(CooldownType::cases())
                ->map(fn($c) => ['value' => $c->value, 'label' => $c->label()])
                ->all(),
        ];
    }));
}
```

### 11.4 `WorkflowBuilderService`

```php
// Modules/WorkflowAutomation/app/Services/WorkflowBuilderService.php
class WorkflowBuilderService
{
    public function createFromRequest(Request $request): Workflow
    {
        $data = $this->validate($request);
        $workflow = Workflow::create($this->workflowAttributes($data));
        $this->syncConditions($workflow, $data['conditions'] ?? []);
        $this->syncSteps($workflow, $data['steps'] ?? []);
        return $workflow;
    }

    public function updateFromRequest(Request $request, Workflow $workflow): Workflow
    {
        $data = $this->validate($request);
        $workflow->update($this->workflowAttributes($data));
        $this->syncConditions($workflow, $data['conditions'] ?? []);
        $this->syncSteps($workflow, $data['steps'] ?? []);
        return $workflow;
    }

    private function validate(Request $request): array
    {
        return $request->validate([
            'name'             => 'required|string|max:191',
            'description'      => 'nullable|string|max:500',
            'trigger_type'     => 'required|string|max:64',
            // trigger_params: array of {key, value, type} — không còn JSON blob
            'trigger_params'               => 'nullable|array',
            'trigger_params.*.param_key'   => 'required|string|max:64',
            'trigger_params.*.param_value' => 'nullable|string|max:255',
            'trigger_params.*.param_type'  => 'required|integer|in:1,2,3,4',
            'condition_match'  => 'required|integer|in:1,2,3',
            'cooldown_type'    => 'required|integer|in:0,1,2,3,4',
            'is_active'        => 'boolean',
            'priority'         => 'integer|min:1|max:10',
            'conditions'       => 'nullable|array',
            'conditions.*.field'      => 'required|string|max:128',
            'conditions.*.operator'   => 'required|string|max:32',
            'conditions.*.value'      => 'nullable|string|max:500',
            'conditions.*.value_type' => 'required|integer|in:1,2,3,4',
            'steps'            => 'nullable|array',
            'steps.*.action_type'    => 'required|string|max:64',
            'steps.*.delay_minutes'  => 'integer|min:0',
            // step headers: array of {header_key, header_value} per step
            'steps.*.headers'                  => 'nullable|array',
            'steps.*.headers.*.header_key'     => 'required|string|max:128',
            'steps.*.headers.*.header_value'   => 'required|string|max:500',
        ]);
    }

    private function workflowAttributes(array $data): array
    {
        return [
            'organization_id' => TenantContext::getOrganizationId(),
            'name'            => $data['name'],
            'description'     => $data['description'] ?? null,
            'trigger_type'    => $data['trigger_type'],
            // trigger_config đã bị bỏ — params lưu vào workflow_trigger_params
            'condition_match' => $data['condition_match'],
            'cooldown_type'   => $data['cooldown_type'],
            'is_active'       => $data['is_active'] ?? false,
            'priority'        => $data['priority'] ?? 5,
            'created_by'      => auth()->id(),
            'updated_by'      => auth()->id(),
        ];
    }

    private function syncTriggerParams(Workflow $workflow, array $params): void
    {
        $workflow->triggerParams()->delete();
        foreach ($params as $param) {
            $workflow->triggerParams()->create([
                'param_key'   => $param['param_key'],
                'param_value' => $param['param_value'] ?? null,
                'param_type'  => $param['param_type'] ?? 1,
            ]);
        }
    }

    private function syncConditions(Workflow $workflow, array $conditions): void
    {
        $workflow->conditions()->delete();
        foreach (array_values($conditions) as $i => $cond) {
            $workflow->conditions()->create(array_merge($cond, ['sort_order' => $i]));
        }
    }

    private function syncSteps(Workflow $workflow, array $steps): void
    {
        // Lưu ý race condition: xóa-rồi-insert có khoảng trống ~ms mà ExecuteWorkflowAction
        // có thể đọc steps rỗng. Rủi ro thấp (workflow update hiếm khi concurrent với execution),
        // nhưng nếu cần zero-downtime: soft delete steps cũ (thêm deleted_at), chỉ hard delete
        // sau khi insert xong. Hiện tại: chấp nhận rủi ro nhỏ này.
        $oldStepIds = $workflow->steps()->pluck('id');
        WorkflowStepHeader::whereIn('step_id', $oldStepIds)->delete();
        $workflow->steps()->delete();

        foreach (array_values($steps) as $i => $stepData) {
            $headers = $stepData['headers'] ?? [];
            unset($stepData['headers']);

            $step = $workflow->steps()->create(array_merge($stepData, ['sort_order' => $i]));

            foreach ($headers as $h) {
                $step->headers()->create([
                    'header_key'   => $h['header_key'],
                    'header_value' => $h['header_value'],
                ]);
            }
        }
    }

}
```

---

## 12. Models

### 12.1 `Workflow`

```php
// Modules/WorkflowAutomation/app/Models/Workflow.php
namespace Modules\WorkflowAutomation\Models;

use App\Foundation\Models\TenantAwareModel;
use Modules\WorkflowAutomation\Enums\WorkflowStatus;
use Modules\WorkflowAutomation\Enums\ConditionMatch;
use Modules\WorkflowAutomation\Enums\CooldownType;

class Workflow extends TenantAwareModel
{
    protected $fillable = [
        'organization_id', 'name', 'description',
        'trigger_type',
        // trigger_config đã bỏ — dùng triggerParams() relationship
        'condition_match', 'cooldown_type',
        'is_active', 'priority',
        'run_count', 'last_run_at', 'last_run_status',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'condition_match'  => 'integer',
        'cooldown_type'    => 'integer',
        'last_run_status'  => 'integer',
        'last_run_at'      => 'datetime',
    ];

    public function conditions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkflowCondition::class)->orderBy('sort_order');
    }

    public function steps(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('sort_order');
    }

    public function executions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkflowExecution::class)->latest('triggered_at');
    }

    public function triggerParams(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkflowTriggerParam::class);
    }

    /**
     * Trả array ['survey_id' => 5, 'band_code' => 'AI_READY'] đã cast đúng type.
     * Gọi sau khi đã eager-load triggerParams để tránh extra query.
     */
    public function parsedParams(): array
    {
        return $this->triggerParams->mapWithKeys(function (WorkflowTriggerParam $p) {
            return [$p->param_key => $p->castValue()];
        })->all();
    }

    public function getConditionMatchEnumAttribute(): ConditionMatch
    {
        return ConditionMatch::from($this->condition_match ?? 3);
    }

    public function getCooldownTypeEnumAttribute(): CooldownType
    {
        return CooldownType::from($this->cooldown_type ?? 0);
    }

    public function getLastRunStatusEnumAttribute(): ?WorkflowStatus
    {
        return $this->last_run_status ? WorkflowStatus::from($this->last_run_status) : null;
    }

    /** Scope: chỉ lấy workflows active */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }
}
```

### 12.2 `WorkflowCondition`

```php
// Modules/WorkflowAutomation/app/Models/WorkflowCondition.php
class WorkflowCondition extends \Illuminate\Database\Eloquent\Model
{
    public $timestamps = false;
    protected $fillable = [
        'workflow_id', 'sort_order',
        'field', 'operator', 'value', 'value_type',
        'created_at',
    ];
    protected $casts = [
        'value_type' => 'integer',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
    ];

    public function workflow(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
```

### 12.3 `WorkflowStep`

```php
// Modules/WorkflowAutomation/app/Models/WorkflowStep.php
class WorkflowStep extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'workflow_id', 'sort_order', 'action_type',
        'email_to', 'email_subject', 'email_template',
        'notif_title', 'notif_body', 'notif_target',
        'update_model', 'update_field', 'update_value',
        'webhook_url', 'webhook_method', 'webhook_secret',
        'lead_status', 'lead_source', 'lead_assigned_to',
        'user_tag', 'user_status',
        'delay_minutes',
    ];
    protected $casts = [
        'delay_minutes'  => 'integer',
        'webhook_method' => 'integer',
        'sort_order'     => 'integer',
    ];

    public function workflow(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
```

### 12.4 `WorkflowExecution`

```php
// Modules/WorkflowAutomation/app/Models/WorkflowExecution.php
use Modules\WorkflowAutomation\Enums\WorkflowStatus;

class WorkflowExecution extends \Illuminate\Database\Eloquent\Model
{
    public $timestamps = false;
    protected $fillable = [
        'workflow_id', 'organization_id', 'run_id',
        'trigger_type', 'source_module',
        'subject_type', 'subject_id', 'actor_id',
        'status', 'skip_reason', 'condition_result',
        'steps_total', 'steps_success', 'steps_failed', 'steps_scheduled',
        'duration_ms',
        'triggered_at', 'executed_at', 'finished_at', 'created_at',
    ];
    protected $casts = [
        'status'           => 'integer',
        'condition_result' => 'boolean',
        'triggered_at'     => 'datetime',
        'executed_at'      => 'datetime',
        'finished_at'      => 'datetime',
        'created_at'       => 'datetime',
    ];

    public function workflow(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function steps(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkflowExecutionStep::class, 'execution_id')->orderBy('sort_order');
    }

    public function getStatusEnumAttribute(): WorkflowStatus
    {
        return WorkflowStatus::from($this->status);
    }

    /** Scope: filter theo org (cho API controller) */
    public function scopeForOrganization(\Illuminate\Database\Eloquent\Builder $query, int $orgId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('organization_id', $orgId);
    }
}
```

### 12.5 `WorkflowExecutionStep`

```php
// Modules/WorkflowAutomation/app/Models/WorkflowExecutionStep.php
class WorkflowExecutionStep extends \Illuminate\Database\Eloquent\Model
{
    public $timestamps = false;
    protected $fillable = [
        'execution_id', 'step_id', 'sort_order',
        'action_type', 'status', 'error_message',
        'duration_ms', 'attempts', 'executed_at', 'created_at',
    ];
    protected $casts = [
        'status'      => 'integer',
        'duration_ms' => 'integer',
        'attempts'    => 'integer',
        'executed_at' => 'datetime',
        'created_at'  => 'datetime',
    ];

    public function execution(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WorkflowExecution::class);
    }
}
```

### 12.6 `WorkflowTriggerParam` — thay thế JSON trigger_config

```php
// Modules/WorkflowAutomation/app/Models/WorkflowTriggerParam.php
class WorkflowTriggerParam extends \Illuminate\Database\Eloquent\Model
{
    public $timestamps = false;
    protected $fillable = ['workflow_id', 'param_key', 'param_value', 'param_type'];
    protected $casts    = ['param_type' => 'integer'];

    public function workflow(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * Cast param_value về đúng type dựa theo param_type.
     * 1=string  2=integer  3=decimal  4=boolean
     */
    public function castValue(): mixed
    {
        return match ($this->param_type) {
            2 => (int)   $this->param_value,
            3 => (float) $this->param_value,
            4 => in_array(strtolower((string) $this->param_value), ['true', '1', 'yes']),
            default => $this->param_value,
        };
    }
}
```

### 12.7 `WorkflowStep` — cập nhật với `headers()` relationship

```php
// Thêm vào WorkflowStep model (bổ sung cho 12.3):

public function headers(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(WorkflowStepHeader::class, 'step_id');
}
```

### 12.8 `WorkflowStepHeader` — thay thế webhook_headers TEXT

```php
// Modules/WorkflowAutomation/app/Models/WorkflowStepHeader.php
class WorkflowStepHeader extends \Illuminate\Database\Eloquent\Model
{
    public $timestamps = false;
    protected $fillable = ['step_id', 'header_key', 'header_value'];

    public function step(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class);
    }
}
```

---

## 12b. WorkflowApiController — Đầy đủ

```php
// Modules/WorkflowAutomation/app/Http/Controllers/WorkflowApiController.php
use Modules\WorkflowAutomation\Core\{TriggerRegistry, ActionRegistry, SubjectRegistry};
use Modules\WorkflowAutomation\Enums\{WorkflowStatus, CooldownType, OperatorType};
use App\Shared\Tenancy\TenantContext;

class WorkflowApiController extends \Illuminate\Routing\Controller
{
    /** GET /backend/api/workflows — Tabulator listing */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $v = $request->validate([
            'search'          => 'nullable|string|max:100',
            'trigger_type'    => 'nullable|string|max:64',
            'is_active'       => 'nullable|in:0,1',
            'page'            => 'nullable|integer|min:1',
            'size'            => 'nullable|integer|min:1|max:100',
            'sorters'         => 'nullable|array',
            'sorters.*.field' => 'nullable|string',
            'sorters.*.dir'   => 'nullable|in:asc,desc',
        ]);

        $page = max(0, ((int) ($v['page'] ?? 1)) - 1);
        $size = (int) ($v['size'] ?? 20);

        $allowed = ['name', 'trigger_type', 'is_active', 'priority', 'run_count', 'last_run_at', 'created_at'];
        $sorter  = $v['sorters'][0] ?? null;
        $sortBy  = in_array($sorter['field'] ?? '', $allowed, true) ? $sorter['field'] : 'created_at';
        $sortDir = in_array(strtolower($sorter['dir'] ?? ''), ['asc', 'desc'], true) ? $sorter['dir'] : 'desc';

        $query = Workflow::query();
        if (TenantContext::isSet()) {
            $query->where('organization_id', TenantContext::getOrganizationId());
        }
        if (!empty($v['search'])) {
            $t = '%' . $v['search'] . '%';
            $query->where(fn($q) => $q->where('name', 'like', $t)->orWhere('trigger_type', 'like', $t));
        }
        if (isset($v['trigger_type'])) $query->where('trigger_type', $v['trigger_type']);
        if (isset($v['is_active']))    $query->where('is_active', (bool) $v['is_active']);

        $total = $query->count();
        $rows  = $query->orderBy($sortBy, $sortDir)->offset($page * $size)->limit($size)->get();

        return response()->json([
            'data'      => $rows->map(fn($w) => $this->normalizeWorkflow($w)),
            'total'     => $total,
            'last_page' => (int) ceil($total / $size),
        ]);
    }

    /** GET /backend/api/workflows/meta — Builder UI config */
    public function meta(): \Illuminate\Http\JsonResponse
    {
        $orgId    = TenantContext::isSet() ? TenantContext::getOrganizationId() : null;
        $cacheKey = 'wf:meta:' . ($orgId ?? 'global');

        return response()->json(\Cache::remember($cacheKey, 600, function () {
            return [
                'trigger_groups' => app(TriggerRegistry::class)->groupedByModule(),
                'action_groups'  => app(ActionRegistry::class)->groupedByModule(),
                'subjects'       => collect(app(SubjectRegistry::class)->all())
                    ->map(fn($s) => ['label' => $s['label'], 'fields' => $s['updatableFields']])
                    ->all(),
                'operators'      => collect(OperatorType::cases())
                    ->map(fn($op) => ['value' => $op->value, 'label' => $op->label(), 'types' => $op->applicableTypes()])
                    ->all(),
                'cooldown_types' => collect(CooldownType::cases())
                    ->map(fn($c) => ['value' => $c->value, 'label' => $c->label()])
                    ->all(),
            ];
        }));
    }

    /** GET /backend/api/workflows/executions — execution history Tabulator */
    public function executions(Request $request): \Illuminate\Http\JsonResponse
    {
        $v = $request->validate([
            'workflow_id'  => 'nullable|integer',
            'status'       => 'nullable|integer|in:1,2,3,4,5',
            'trigger_type' => 'nullable|string|max:64',
            'date_from'    => 'nullable|date',
            'date_to'      => 'nullable|date',
            'page'         => 'nullable|integer|min:1',
            'size'         => 'nullable|integer|min:1|max:100',
        ]);

        $page  = max(0, ((int) ($v['page'] ?? 1)) - 1);
        $size  = (int) ($v['size'] ?? 20);

        $query = WorkflowExecution::with('workflow:id,name');
        if (TenantContext::isSet()) {
            $query->forOrganization(TenantContext::getOrganizationId());
        }
        if (!empty($v['workflow_id'])) $query->where('workflow_id', $v['workflow_id']);
        if (!empty($v['status']))      $query->where('status', $v['status']);
        if (!empty($v['trigger_type'])) $query->where('trigger_type', $v['trigger_type']);
        if (!empty($v['date_from']))   $query->where('triggered_at', '>=', $v['date_from'] . ' 00:00:00');
        if (!empty($v['date_to']))     $query->where('triggered_at', '<=', $v['date_to'] . ' 23:59:59');

        $total = $query->count();
        $rows  = $query->orderByDesc('triggered_at')->offset($page * $size)->limit($size)->get();

        return response()->json([
            'data'      => $rows->map(fn($e) => $this->normalizeExecution($e)),
            'total'     => $total,
            'last_page' => (int) ceil($total / $size),
        ]);
    }

    /** GET /backend/api/workflows/stats */
    public function stats(Request $request): \Illuminate\Http\JsonResponse
    {
        $orgId    = TenantContext::isSet() ? TenantContext::getOrganizationId() : null;
        $cacheKey = 'wf:stats:' . ($orgId ?? 'all');

        return response()->json(\Cache::remember($cacheKey, 120, function () use ($orgId) {
            $base = WorkflowExecution::when($orgId, fn($q) => $q->where('organization_id', $orgId));

            return [
                'total_workflows' => Workflow::when($orgId, fn($q) => $q->where('organization_id', $orgId))->count(),
                'active_workflows'=> Workflow::when($orgId, fn($q) => $q->where('organization_id', $orgId))->where('is_active', true)->count(),
                'executions_today'=> (clone $base)->whereDate('triggered_at', today())->count(),
                'by_status'       => (clone $base)->whereDate('triggered_at', today())
                    ->selectRaw('status, COUNT(*) as count')->groupBy('status')->get(),
                'recent_failures' => (clone $base)->where('status', WorkflowStatus::Fail->value)
                    ->with('workflow:id,name')->latest('triggered_at')->limit(5)->get()
                    ->map(fn($e) => ['workflow_name' => $e->workflow?->name, 'triggered_at' => $e->triggered_at, 'run_id' => $e->run_id]),
            ];
        }));
    }

    /** GET /backend/api/workflows/subject-fields/{type} — fields dropdown cho UpdateSubjectExecutor */
    public function subjectFields(string $type): \Illuminate\Http\JsonResponse
    {
        $config = app(SubjectRegistry::class)->get($type);
        if (!$config) return response()->json(['fields' => []]);
        return response()->json(['fields' => $config['updatableFields']]);
    }

    private function normalizeWorkflow(Workflow $w): array
    {
        return [
            'id'               => $w->id,
            'name'             => $w->name,
            'trigger_type'     => $w->trigger_type,
            'is_active'        => $w->is_active,
            'priority'         => $w->priority,
            'run_count'        => $w->run_count,
            'last_run_at'      => $w->last_run_at,
            'last_run_status'  => $w->last_run_status,
            'last_run_badge'   => $w->last_run_status_enum?->badge(),
            'last_run_label'   => $w->last_run_status_enum?->label(),
            'created_at'       => $w->created_at,
        ];
    }

    private function normalizeExecution(WorkflowExecution $e): array
    {
        return [
            'id'             => $e->id,
            'run_id'         => $e->run_id,
            'workflow_name'  => $e->workflow?->name,
            'trigger_type'   => $e->trigger_type,
            'source_module'  => $e->source_module,
            'subject_type'   => $e->subject_type,
            'subject_id'     => $e->subject_id,
            'status'         => $e->status,
            'status_label'   => $e->status_enum->label(),
            'status_badge'   => $e->status_enum->badge(),
            'skip_reason'    => $e->skip_reason,
            'steps_total'    => $e->steps_total,
            'steps_success'  => $e->steps_success,
            'steps_failed'   => $e->steps_failed,
            'steps_scheduled'=> $e->steps_scheduled,
            'duration_ms'    => $e->duration_ms,
            'triggered_at'   => $e->triggered_at,
            'finished_at'    => $e->finished_at,
        ];
    }
}
```

---

## 13. Admin UI — Workflow Builder

Builder UI là form Alpine.js 5 phần, load config từ `/backend/api/workflows/meta`.

**Phần 1 — Thông tin cơ bản**: name, description, priority, is_active toggle.

**Phần 2 — Trigger** (dynamic theo module):
```html
<select x-model="selectedTriggerType">
    <template x-for="[module, triggers] in Object.entries(meta.trigger_groups)">
        <optgroup :label="module">
            <template x-for="t in triggers">
                <option :value="t.type" x-text="t.label"></option>
            </template>
        </optgroup>
    </template>
</select>

<!-- trigger_config fields động theo type đã chọn -->
<template x-for="field in currentTrigger?.config_fields ?? []">
    <div>
        <label x-text="field.label"></label>
        <template x-if="field.type === 'model_select'">
            <select x-model="triggerConfig[field.key]" x-init="loadModelOptions(field)">...</select>
        </template>
        <template x-if="field.type === 'text' || field.type === 'number'">
            <input :type="field.type" x-model="triggerConfig[field.key]" :placeholder="field.hint">
        </template>
    </div>
</template>
```

**Phần 3 — Conditions** (AND/OR builder):
```html
<select x-model="conditionMatch">
    <option value="1">TẤT CẢ điều kiện đúng (AND)</option>
    <option value="2">ÍT NHẤT 1 điều kiện đúng (OR)</option>
    <option value="3">Không cần điều kiện</option>
</select>

<template x-for="(cond, i) in conditions">
    <select x-model="cond.field">
        <template x-for="f in currentTrigger?.available_fields ?? []">
            <option :value="f.key" x-text="f.label"></option>
        </template>
    </select>
    <select x-model="cond.operator">
        <template x-for="op in filteredOperators(cond.field)">
            <option :value="op.value" x-text="op.label"></option>
        </template>
    </select>
    <input x-model="cond.value" :placeholder="cond.operator === 'in' ? 'AI_READY|DIGITAL_FOUNDATION' : 'Giá trị'">
</template>
```

**Phần 4 — Steps** (drag/drop reorder):
```html
<template x-for="(step, i) in steps">
    <div class="step-card">
        <select x-model="step.action_type" @change="resetStepConfig(step)">
            <template x-for="[module, actions] in Object.entries(meta.action_groups)">
                <optgroup :label="module">
                    <template x-for="a in actions">
                        <option :value="a.type" x-text="a.label"></option>
                    </template>
                </optgroup>
            </template>
        </select>
        <template x-for="field in currentActionFields(step.action_type)">
            <!-- render input theo field.type -->
        </template>
        <input type="number" x-model.number="step.delay_minutes" min="0" placeholder="Delay (phút)">
    </div>
</template>
```

**Phần 5 — Cooldown**: select từ `meta.cooldown_types`.

**Lưu ý**: khi submit form, `trigger_params` gửi lên là array `[{param_key, param_value, param_type}]`. Server lưu từng item vào `workflow_trigger_params` — không có JSON encoding.

---

## 14. Cách tích hợp — Survey (đã có)

### Bước 1: Đăng ký trong `SurveyServiceProvider`

```php
// Modules/Survey/app/Providers/SurveyServiceProvider.php
public function boot(): void
{
    if (app()->bound(TriggerRegistry::class)) {
        $triggerRegistry = app(TriggerRegistry::class);
        $triggerRegistry->register(new SurveySubmittedTrigger());
        $triggerRegistry->register(new SurveyResultBandTrigger());
    }

    if (app()->bound(SubjectRegistry::class)) {
        app(SubjectRegistry::class)->register(
            type:            'SurveyResponse',
            fqcn:            SurveyResponse::class,
            label:           'Survey Response',
            updatableFields: [
                ['field' => 'status', 'label' => 'Trạng thái', 'type' => 'integer'],
            ]
        );
    }
    // KHÔNG gọi Cache::forget('wf:meta') ở đây — xem Anti-pattern #6
}
```

### Bước 2: Gọi Dispatcher sau commit

```php
// Modules/Survey/app/Actions/SubmitSurveyAction.php
public function handle(Survey $survey, array $answers, Request $request): SurveyResponse
{
    $response = DB::transaction(function () use ($survey, $answers) {
        // ... lưu response và answers ...
        return $response;
    });

    // SAU commit — không bao giờ gọi bên trong transaction
    WorkflowDispatcher::fire(TriggerPayload::forSurveySubmit($response));

    return $response;
}

// Modules/Survey/app/Jobs/CalculateSurveyScoreJob.php
public function handle(): void
{
    // ... tính điểm ...
    $result = SurveyResult::create([...]);

    WorkflowDispatcher::fire(TriggerPayload::forSurveyResult($result));
}
```

> **Lưu ý field mapping `SurveyResult`**: `TriggerPayload::forSurveyResult()` đọc
> `$result->band_code`, `$result->overall_score`, `$result->weight_version`.
> Trước khi build phải verify các field này tồn tại trên model `SurveyResult`
> (kiểm tra migration `survey_results` table). Nếu tên khác (ví dụ `total_score`
> thay vì `overall_score`), sửa trong factory method, không sửa DB schema.

---

## 15. Cách tích hợp — Lead (đang plan)

### Bước 1: ServiceProvider

```php
// Modules/Lead/app/Providers/LeadServiceProvider.php
public function boot(): void
{
    if (app()->bound(TriggerRegistry::class)) {
        $registry = app(TriggerRegistry::class);
        $registry->register(new LeadCreatedTrigger());
        $registry->register(new LeadStageChangedTrigger());
    }

    if (app()->bound(ActionRegistry::class)) {
        $registry = app(ActionRegistry::class);
        $registry->register(new CreateLeadExecutor());
        $registry->register(new UpdateLeadStageExecutor());
        $registry->register(new AssignLeadExecutor());
    }

    if (app()->bound(SubjectRegistry::class)) {
        app(SubjectRegistry::class)->register(
            type:            'Lead',
            fqcn:            Lead::class,
            label:           'Lead',
            updatableFields: [
                ['field' => 'status',      'label' => 'Trạng thái', 'type' => 'string'],
                ['field' => 'stage',       'label' => 'Stage',      'type' => 'string'],
                ['field' => 'lead_score',  'label' => 'Lead score', 'type' => 'integer'],
                ['field' => 'assigned_to', 'label' => 'Assign cho', 'type' => 'integer'],
            ]
        );
    }
    // KHÔNG gọi Cache::forget('wf:meta') ở đây
}
```

### Bước 2: Implement `WorkflowSubject` trên Lead model

```php
class Lead extends Model implements WorkflowSubject
{
    public static function workflowSubjectType(): string { return 'Lead'; }

    public static function workflowUpdatableFields(): array
    {
        return [
            ['field' => 'status', 'label' => 'Trạng thái', 'type' => 'string'],
            ['field' => 'stage',  'label' => 'Stage',      'type' => 'string'],
        ];
    }

    public static function resolveFromPayload(TriggerPayload $payload): ?static
    {
        if ($payload->subjectType === 'Lead' && $payload->subjectId) {
            return static::find($payload->subjectId);
        }
        if ($payload->actorEmail) {
            return static::where('email', $payload->actorEmail)->first();
        }
        return null;
    }
}
```

### Bước 3: Gọi Dispatcher

```php
// Modules/Lead/app/Actions/CreateLeadAction.php
public function handle(array $data): Lead
{
    $lead = Lead::create($data);

    WorkflowDispatcher::fire(new TriggerPayload(
        triggerType:    'lead.created',
        sourceModule:   'Lead',
        organizationId: TenantContext::getOrganizationId(),
        actorId:        auth()->id(),
        actorEmail:     $lead->email,
        actorName:      $lead->name,
        actorRole:      null,
        subjectType:    'Lead',
        subjectId:      $lead->id,
        subjectLabel:   $lead->email,
        extra: [
            'source'     => $lead->source,
            'lead_score' => $lead->lead_score ?? 0,
            'stage'      => $lead->stage,
        ],
        requestId: request()->header('X-Request-Id', (string) \Str::uuid()),
    ));

    return $lead;
}
```

### Use case: Survey → tự động tạo Lead

Admin cấu hình workflow — payload gửi lên `POST /dashboard/workflows`:
```json
{
  "trigger_type": "survey.result_calculated",
  "trigger_params": [
    {"param_key": "band_code", "param_value": "AI_READY", "param_type": 1}
  ],
  "condition_match": 1,
  "conditions": [
    {"field": "extra.overall_score", "operator": ">=", "value": "60", "value_type": 3}
  ],
  "cooldown_type": 1,
  "steps": [
    {"action_type": "lead.create",        "lead_source": "survey",      "headers": []},
    {"action_type": "email.send",         "email_to": "{actor.email}",  "email_subject": "Chúc mừng!", "headers": []},
    {"action_type": "notification.send",  "notif_target": "role:sales", "notif_title": "Lead mới từ Survey AI Ready", "headers": []}
  ]
}
```

---

## 16. Cách tích hợp — User (đang plan)

```php
// Modules/User/app/Providers/UserServiceProvider.php
public function boot(): void
{
    if (app()->bound(TriggerRegistry::class)) {
        $registry = app(TriggerRegistry::class);
        $registry->register(new UserRegisteredTrigger());
        $registry->register(new UserRoleChangedTrigger());
    }

    if (app()->bound(ActionRegistry::class)) {
        $registry = app(ActionRegistry::class);
        $registry->register(new AssignUserTagExecutor());
        $registry->register(new ChangeUserStatusExecutor());
    }

    if (app()->bound(SubjectRegistry::class)) {
        app(SubjectRegistry::class)->register(
            type:            'User',
            fqcn:            \App\Models\User::class,
            label:           'Người dùng',
            updatableFields: [
                ['field' => 'status',            'label' => 'Trạng thái',   'type' => 'string'],
                ['field' => 'subscription_tier', 'label' => 'Gói đăng ký', 'type' => 'string'],
            ]
        );
    }
}
```

---

## 17. Cách thêm module hoàn toàn mới

Giả sử thêm module `Appointment`:

```
Checklist tích hợp Appointment vào Workflow:

□ 1. Tạo trigger classes trong Modules/Appointment/app/WorkflowTriggers/
     - AppointmentCreatedTrigger   (type: 'appointment.created')
     - AppointmentConfirmedTrigger (type: 'appointment.confirmed')

□ 2. Tạo executor classes trong Modules/Appointment/app/WorkflowExecutors/ (tuỳ chọn)
     - CreateAppointmentExecutor   (type: 'appointment.create')

□ 3. Implement WorkflowSubject trên Appointment model

□ 4. Đăng ký trong AppointmentServiceProvider::boot()
     $triggerRegistry->register(...)
     $actionRegistry->register(...)
     $subjectRegistry->register(...)
     // Không gọi Cache::forget — cache tự hết hạn sau 600 giây

□ 5. Gọi WorkflowDispatcher::fire() sau mỗi action quan trọng
     AppointmentCreatedAction::handle() → WorkflowDispatcher::fire(...)

□ 6. KHÔNG cần sửa bất kỳ file nào trong WorkflowAutomation module
```

---

## 18. Permissions & Config

### 17.1 Permissions — tích hợp với `PermissionEnum` và `config/permissions.php`

Workflow dùng `PermissionEnum` đã có trong hệ thống. Không tạo permission string thủ công.

```php
// app/Enums/PermissionEnum.php (đã có)
case WORKFLOW_MONITOR      = 'workflow.monitor';      // Xem list + execution history
case WORKFLOW_EDIT         = 'workflow.edit';         // Tạo/sửa/toggle workflow
case WORKFLOW_VIEW_LIMITED = 'workflow.view_limited'; // Xem workflow public (read-only)
case WORKFLOW_AI_CONFIG    = 'workflow.ai_config';    // Cấu hình AI-related steps
case WORKFLOW_FULL_CONFIG  = 'workflow.full_config';  // Xóa, force run, admin config
```

Mapping roles trong `config/permissions.php` (đã cấu hình):
```
CEO          → workflow.monitor            (xem, không sửa)
Ops          → workflow.monitor + workflow.edit
Sales/HR/Mkt → workflow.view_limited
AI Operator  → workflow.ai_config
System Admin → workflow.full_config (bao gồm tất cả quyền bên trên)
```

### 17.2 Module config

```php
// Modules/WorkflowAutomation/config/workflow_automation.php
return [
    'queue'                 => env('WORKFLOW_QUEUE', 'workflows'),
    'retain_execution_days' => env('WORKFLOW_RETAIN_DAYS', 60),
    'webhook_timeout'       => env('WORKFLOW_WEBHOOK_TIMEOUT', 15),
    'webhook_max_retries'   => env('WORKFLOW_WEBHOOK_RETRIES', 2),
    'allow_manual_trigger'  => env('WORKFLOW_ALLOW_MANUAL', true),
    'meta_cache_ttl'        => env('WORKFLOW_META_CACHE_TTL', 600),
];
```

### 17.3 ServiceProvider binding

```php
// Modules/WorkflowAutomation/app/Providers/WorkflowAutomationServiceProvider.php
public function register(): void
{
    $this->app->singleton(TriggerRegistry::class);
    $this->app->singleton(ActionRegistry::class);
    $this->app->singleton(SubjectRegistry::class);
    $this->app->singleton(ConditionEvaluator::class);
    $this->app->singleton(CooldownGuard::class, fn($app) =>
        new CooldownGuard($app->make(\Illuminate\Contracts\Cache\Repository::class))
    );
}

public function boot(): void
{
    // Đăng ký built-in triggers & executors
    $triggerRegistry = app(TriggerRegistry::class);
    $triggerRegistry->register(new ManualTrigger());

    $actionRegistry = app(ActionRegistry::class);
    $actionRegistry->register(app(SendEmailExecutor::class));
    $actionRegistry->register(app(SendNotificationExecutor::class));
    $actionRegistry->register(app(UpdateSubjectExecutor::class));
    $actionRegistry->register(app(CallWebhookExecutor::class));

    // KHÔNG gọi Cache::forget('wf:meta') ở đây
    // Cache tự expire sau meta_cache_ttl giây
    // Chỉ forget khi cần force refresh sau deploy: php artisan cache:forget wf:meta:*
}
```

---

## 19. Tích hợp Sidebar & Navigation

### 19.1 Thêm vào `RoleEnum::visibleModules()`

`workflow` đã có trong `visibleModules()` của các roles CEO, Ops, AI_OP, Admin (`app/Enums/RoleEnum.php`). Khi module được kích hoạt trong `modules_statuses.json`, sidebar tự render từ config này.

### 19.2 Kích hoạt module

```bash
# Bật module trong modules_statuses.json
php artisan module:enable WorkflowAutomation

# Chạy migrations
php artisan migrate --path=Modules/WorkflowAutomation/database/migrations

# Sync permissions
php artisan permissions:sync
```

### 19.3 Sidebar item (trong `resources/views/layouts/partials/sidebar.blade.php`)

Sidebar tự render từ `PermissionEnum` + `RoleEnum::visibleModules()`. Không cần sửa thủ công nếu đã follow pattern. Để rõ ràng, thêm entry vào config sidebar nếu dự án dùng config-driven sidebar:

```php
// Trong config hoặc ServiceProvider nơi sidebar được build:
// Module key: 'workflow'
// Route:      route('workflows.index')
// Icon:       'heroicons:bolt' hoặc tương đương
// Permission: PermissionEnum::WORKFLOW_MONITOR->value
// Label:      'Workflow'
```

### 19.4 Permissions — thêm vào `config/permissions.php`

`config/permissions.php` là **single source of truth** cho RBAC của hệ thống. Chạy `php artisan permissions:sync` sau deploy để sync tự động — không cần seeder riêng.

```php
// config/permissions.php — thêm vào các role tương ứng:

R::CEO->value => [
    // ... permissions hiện có ...
    P::WORKFLOW_MONITOR->value,          // Xem list + execution history (không sửa)
],

R::OPS->value => [
    // ... permissions hiện có ...
    P::WORKFLOW_MONITOR->value,
    P::WORKFLOW_EDIT->value,             // Tạo/sửa/toggle workflow
],

R::SALES->value => [
    // ... permissions hiện có ...
    P::WORKFLOW_VIEW_LIMITED->value,     // Chỉ xem workflow public
],

R::HR->value => [
    // ... permissions hiện có ...
    P::WORKFLOW_VIEW_LIMITED->value,
],

R::MARKETING->value => [
    // ... permissions hiện có ...
    P::WORKFLOW_VIEW_LIMITED->value,
],

R::AI_OP->value => [
    // ... permissions hiện có ...
    P::WORKFLOW_MONITOR->value,
    P::WORKFLOW_AI_CONFIG->value,        // Cấu hình AI-related steps
],

R::ADMIN->value => [
    // ... permissions hiện có ...
    P::WORKFLOW_MONITOR->value,
    P::WORKFLOW_EDIT->value,
    P::WORKFLOW_AI_CONFIG->value,
    P::WORKFLOW_FULL_CONFIG->value,      // Xóa, force run, admin config
],
```

```php
// app/Enums/PermissionEnum.php — thêm 5 cases mới:
case WORKFLOW_MONITOR      = 'workflow.monitor';
case WORKFLOW_EDIT         = 'workflow.edit';
case WORKFLOW_VIEW_LIMITED = 'workflow.view_limited';
case WORKFLOW_AI_CONFIG    = 'workflow.ai_config';
case WORKFLOW_FULL_CONFIG  = 'workflow.full_config';
```

```bash
# Deploy command — sync permissions sau khi thêm cases mới:
php artisan permissions:sync
```

### 19.5 Queue configuration

**Bước 0 — tạo queue tables** (nếu chưa có):
```bash
# Tạo bảng jobs, failed_jobs, job_batches
php artisan queue:table
php artisan migrate
```

```env
# .env
WORKFLOW_QUEUE=workflows
QUEUE_CONNECTION=database   # dev (SQLite-compatible)
# QUEUE_CONNECTION=redis    # prod
```

```bash
# Chạy queue worker cho workflow queue
php artisan queue:work --queue=workflows,default

# Hoặc dùng supervisor với config:
# [program:workflow-worker]
# command=php /var/www/html/minhan/artisan queue:work --queue=workflows --sleep=3 --tries=3
```

### 19.6 Cache invalidation sau deploy

Cache meta (triggers/actions/subjects) TTL 600s. Nếu deploy thêm trigger/executor mới, admin có thể thấy UI cũ tới 10 phút.

```bash
# Thêm vào deploy script (sau php artisan migrate):
php artisan cache:forget wf:meta:global
# Hoặc nếu multi-org:
php artisan tinker --execute="Cache::flush();"  # chỉ dùng khi cache là file/array driver
# Redis: redis-cli KEYS "wf:meta:*" | xargs redis-cli DEL
```

Nếu cần zero-downtime cache refresh, cân nhắc versioned cache key:
```php
// config/workflow_automation.php
'meta_cache_version' => env('WORKFLOW_META_VERSION', 1),

// Trong WorkflowApiController::meta():
$cacheKey = 'wf:meta:v' . config('workflow_automation.meta_cache_version') . ':' . ($orgId ?? 'global');
// Increment WORKFLOW_META_VERSION trong .env sau mỗi deploy có thay đổi triggers/actions
```

---

## 20. Anti-patterns phải tránh

### ❌ 1. Gọi Dispatcher bên trong DB transaction

```php
// SAI
DB::transaction(function () {
    $response = SurveyResponse::create([...]);
    WorkflowDispatcher::fire(...); // job đã queue, nếu transaction rollback → dữ liệu chưa tồn tại
});

// ĐÚNG
$response = DB::transaction(fn() => SurveyResponse::create([...]));
WorkflowDispatcher::fire(...); // sau commit
```

### ❌ 2. WorkflowEngine import module nguồn

```php
// SAI
use Modules\Survey\app\Models\SurveyResponse;
class ExecuteWorkflowAction {
    public function handle() {
        $response = SurveyResponse::find($payload->subjectId); // coupling
    }
}

// ĐÚNG — dùng SubjectRegistry
$model = app(SubjectRegistry::class)->resolve($payload->subjectType, $payload);
```

### ❌ 3. Module nguồn import nhau

```php
// SAI
use Modules\Lead\app\Models\Lead;
class SubmitSurveyAction {
    public function handle() {
        Lead::create([...]); // Survey biết về Lead
    }
}

// ĐÚNG
WorkflowDispatcher::fire(TriggerPayload::forSurveySubmit($response));
// Admin cấu hình: survey.submitted → lead.create
```

### ❌ 4. Một step fail dừng cả workflow

```php
// SAI
foreach ($steps as $step) {
    $result = $executor->execute($step, $payload);
    if (!$result->success) throw new \Exception('Step failed');
}

// ĐÚNG — tiếp tục dù step trước fail
foreach ($steps as $step) {
    $result = $executor->execute($step, $payload); // executor đã catch exception bên trong
    $result->success ? $success++ : $failed++;
}
```

### ❌ 5. `Cache::forget('wf:meta')` trong ServiceProvider::boot()

```php
// SAI — boot() chạy MỖI request, cache miss liên tục
public function boot(): void
{
    $registry->register(...);
    Cache::forget('wf:meta'); // ← xóa cache sau mỗi request!
}

// ĐÚNG — cache tự expire sau meta_cache_ttl (600s)
// Nếu cần force-refresh sau deploy:
// php artisan tinker → Cache::forget('wf:meta:global')
// Hoặc thêm vào deploy script: php artisan cache:clear --tag=workflow
```

### ❌ 6. `cache()->forever()` cho OncePerSubject cooldown

```php
// SAI — cache không có TTL, không thể dọn dẹp, gây memory leak trên Redis
if ($type === CooldownType::OncePerSubject) {
    $this->cache->forever($key, 1); // ← không bao giờ expire
}

// ĐÚNG — dùng TTL rất lớn (1 năm) thay vì forever()
$this->cache->put($key, 1, 365 * 86400);
// TTL đủ lớn để thực tế là "1 lần", nhưng có thể dọn dẹp
```

### ❌ 7. Lưu trigger config dưới bất kỳ dạng serialized nào (INI, JSON, text)

```php
// SAI — INI: parse_ini_string() có edge cases, không handle boolean/array
$config = "survey_id=5\nband_code=AI_READY";
parse_ini_string($config);

// SAI — JSON: không query được SQL, không index được
$workflow->trigger_config = json_encode(['survey_id' => 5, 'band_code' => 'AI_READY']);
// WHERE JSON_EXTRACT(...) — không portable, SQLite hỗ trợ kém

// ĐÚNG — normalize thành workflow_trigger_params (index hit, type-safe, query chuẩn SQL)
$workflow->triggerParams()->createMany([
    ['param_key' => 'survey_id', 'param_value' => '5',        'param_type' => 2],
    ['param_key' => 'band_code', 'param_value' => 'AI_READY', 'param_type' => 1],
]);
WorkflowTriggerParam::where('param_key', 'band_code')->where('param_value', 'AI_READY')->get();
```

### ❌ 9. Lưu config có cấu trúc vào TEXT/JSON column

```php
// SAI — không query được SQL, không index được, decode phí CPU
$workflow->trigger_config = json_encode(['survey_id' => 5, 'band_code' => 'AI_READY']);

// SAI — không thể WHERE trực tiếp
Workflow::whereRaw("JSON_EXTRACT(trigger_config, '$.band_code') = 'AI_READY'");
// SQLite không hỗ trợ JSON_EXTRACT tốt, MySQL mới có, không portable

// ĐÚNG — normalize thành workflow_trigger_params
$workflow->triggerParams()->createMany([
    ['param_key' => 'band_code', 'param_value' => 'AI_READY', 'param_type' => 1],
]);
// Query chuẩn SQL:
WorkflowTriggerParam::where('param_key', 'band_code')->where('param_value', 'AI_READY')->get();
```

### ❌ 8. Không generate run_id trước khi dispatch

```php
// SAI — job bị retry, không có cách check duplicate
ExecuteWorkflowAction::dispatch($workflowId, $payload);

// ĐÚNG — generate run_id trước, job check idempotency ở đầu
$runId = (string) Str::uuid();
ExecuteWorkflowAction::dispatch($workflowId, $payload, $runId);
// Job bắt đầu: if (DB::table('workflow_executions')->where('run_id', $runId)->exists()) return;
```

---

## 21. Migrations hoàn chỉnh

```
Modules/WorkflowAutomation/database/migrations/
├── 2026_01_01_000001_create_workflows_table.php
├── 2026_01_01_000002_create_workflow_trigger_params_table.php   ← thay trigger_config TEXT
├── 2026_01_01_000003_create_workflow_conditions_table.php
├── 2026_01_01_000004_create_workflow_steps_table.php
├── 2026_01_01_000005_create_workflow_step_headers_table.php     ← thay webhook_headers TEXT
├── 2026_01_01_000006_create_workflow_executions_table.php
└── 2026_01_01_000007_create_workflow_execution_steps_table.php

-- Khi build module Lead:
└── 2026_06_01_000006_add_lead_columns_to_workflow_steps.php
    Schema::table('workflow_steps', function (Blueprint $table) {
        $table->string('lead_status', 64)->nullable()->after('user_status');
        $table->string('lead_source', 64)->nullable()->after('lead_status');
        $table->unsignedBigInteger('lead_assigned_to')->nullable()->after('lead_source');
    });

-- Khi build module User:
└── 2026_07_01_000007_add_user_columns_to_workflow_steps.php
    Schema::table('workflow_steps', function (Blueprint $table) {
        $table->string('user_tag', 64)->nullable();
        $table->string('user_status', 32)->nullable();
    });
```

Các schema đầy đủ của từng migration xem tại [Section 4](#4-database-schema).

---

## 22. Seeders — workflow mẫu thực tế

```php
// Modules/WorkflowAutomation/database/seeders/SampleWorkflowsSeeder.php
class SampleWorkflowsSeeder extends Seeder
{
    public function run(): void
    {
        $orgId = \App\Shared\Tenancy\TenantContext::getOrganizationId() ?? 1;

        // 1. Survey AI_READY → Email + Lead + Notification
        $wf1 = Workflow::create([
            'organization_id' => $orgId,
            'name'            => '[Survey] AI Ready — chuyển đổi thành Lead',
            'description'     => 'Khi Survey có kết quả AI_READY và score >= 60, gửi email chúc mừng, tạo Lead, thông báo sales.',
            'trigger_type'    => 'survey.result_calculated',
            // trigger_config không còn — dùng triggerParams relationship
            'condition_match' => 1, // ALL
            'cooldown_type'   => 1, // once_per_subject
            'is_active'       => false,
            'priority'        => 1,
        ]);
        // Trigger params — normalized, không JSON
        $wf1->triggerParams()->createMany([
            ['param_key' => 'band_code', 'param_value' => 'AI_READY', 'param_type' => 1],
        ]);
        $wf1->conditions()->createMany([
            ['sort_order' => 0, 'field' => 'extra.overall_score', 'operator' => '>=', 'value' => '60', 'value_type' => 3],
        ]);
        $wf1->steps()->createMany([
            ['sort_order' => 0, 'action_type' => 'email.send',
             'email_to' => '{actor.email}',
             'email_subject' => 'Chúc mừng! Bạn đạt mức AI Ready',
             'email_template' => 'survey::emails.result_ai_ready',
             'delay_minutes' => 0],
            ['sort_order' => 1, 'action_type' => 'lead.create',
             'lead_source' => 'survey', 'delay_minutes' => 0],
            ['sort_order' => 2, 'action_type' => 'notification.send',
             'notif_target' => 'role:sales',
             'notif_title'  => 'Lead mới — AI Ready',
             'notif_body'   => '{actor.email} đạt {extra.overall_score}% — AI Ready',
             'delay_minutes' => 0],
        ]);

        // 2. User đăng ký → Welcome email + tạo Lead
        $wf2 = Workflow::create([
            'organization_id' => $orgId,
            'name'            => '[User] Đăng ký mới — Welcome flow',
            'trigger_type'    => 'user.registered',
            'condition_match' => 3,
            'cooldown_type'   => 1,
            'is_active'       => false,
            'priority'        => 2,
        ]);
        // Không có trigger params — trigger này match tất cả user.registered events
        $wf2->steps()->createMany([
            ['sort_order' => 0, 'action_type' => 'email.send',
             'email_to' => '{actor.email}',
             'email_subject' => 'Chào mừng {actor.name}!',
             'email_template' => 'auth::emails.welcome'],
            ['sort_order' => 1, 'action_type' => 'lead.create',
             'lead_source' => 'registration', 'delay_minutes' => 0],
        ]);

        // 3. Webhook CRM sau mỗi survey submit
        $wf3 = Workflow::create([
            'organization_id' => $orgId,
            'name'            => '[Survey] Push CRM — mọi submission',
            'trigger_type'    => 'survey.submitted',
            'condition_match' => 3,
            'cooldown_type'   => 0,
            'is_active'       => false,
            'priority'        => 5,
        ]);
        $step = $wf3->steps()->create([
            'sort_order'     => 0,
            'action_type'    => 'webhook.call',
            'webhook_url'    => 'https://crm.example.com/api/webhook/survey',
            'webhook_method' => 2,
            'webhook_secret' => 'change-me-in-production',
        ]);
        // Headers lưu vào workflow_step_headers — không còn TEXT column
        $step->headers()->createMany([
            ['header_key' => 'Content-Type',  'header_value' => 'application/json'],
            ['header_key' => 'X-Source',      'header_value' => 'minhan-workflow'],
        ]);
    }
}
```

---

## 23. Thứ tự triển khai

| # | Hạng mục | Effort | Ghi chú |
|---|----------|--------|---------|
| 1 | Contracts: `TriggerSource`, `ActionExecutor`, `WorkflowSubject` | Thấp | Nền tảng — phải xong trước |
| 2 | `TriggerPayload` + `ActionResult` (spatie/laravel-data) | Thấp | Thêm `organizationId` field |
| 3 | Enums: `WorkflowStatus`, `ConditionMatch`, `CooldownType`, `OperatorType` | Thấp | |
| 4 | 7 Migrations (Laravel Schema Builder, SQLite-compatible, không JSON column) | Thấp | |
| 5 | Models: `Workflow`, `WorkflowTriggerParam`, `WorkflowCondition`, `WorkflowStep`, `WorkflowStepHeader`, `WorkflowExecution`, `WorkflowExecutionStep` | Thấp | |
| 6 | Registries: `TriggerRegistry`, `ActionRegistry`, `SubjectRegistry` | Thấp | |
| 7 | `ConditionEvaluator` + `CooldownGuard` | Trung | CooldownGuard dùng TTL, không forever() |
| 8 | `WorkflowDispatcher` (generate run_id trước dispatch) | Thấp | |
| 9 | Built-in executors: Email, Notification, UpdateSubject, Webhook | Trung | |
| 10 | `WorkflowMail` mailable | Thấp | |
| 11 | `ExecuteWorkflowAction` (idempotency + delayed step logging) | Trung | lorisleiva/laravel-actions |
| 12 | `ExecuteWorkflowStepAction` + `PurgeOldExecutionsAction` | Thấp | |
| 13 | `WorkflowAutomationServiceProvider` (bind singletons, đăng ký built-ins) | Thấp | |
| 14 | **Survey integration** — triggers + ServiceProvider + Dispatcher | Thấp | Quick win |
| 15 | Workflow permissions vào `config/permissions.php` + `PermissionEnum` + `permissions:sync` | Thấp | Single source of truth |
| 16 | `WorkflowApiController` (meta + index + executions + stats) | Trung | |
| 17 | `WorkflowBuilderService` — validate + persist (trigger_params normalized) | Trung | |
| 18 | `WorkflowController` + `WorkflowExecutionController` | Trung | |
| 19 | View `workflows/index` + Tabulator | Trung | |
| 20 | View `workflows/create` + `edit` — Builder UI 5 phần | Cao | Alpine.js dynamic form |
| 21 | View `workflows/show` + execution history | Trung | |
| 22 | Sample workflows seeder | Thấp | |
| 23 | **Lead integration** — khi build module Lead | Thấp | Chỉ implement interface + đăng ký |
| 24 | **User integration** — khi build module User | Thấp | Chỉ implement interface + đăng ký |
| 25 | `PurgeOldExecutionsAction` scheduler (`console.php`) | Thấp | |
| 26 | Monitor `failed_jobs` + alert setup | Thấp | Xem Section 24 |

---

## 24. Vận hành & Resilience

### 24.1 Xử lý `failed_jobs`

Khi job vượt `jobTries=3`, Laravel chuyển vào bảng `failed_jobs`. Workflow không có built-in alerting — cần monitor chủ động.

```bash
# Xem failed jobs
php artisan queue:failed

# Retry tất cả failed jobs
php artisan queue:retry all

# Retry 1 job cụ thể
php artisan queue:retry <uuid>

# Xóa failed jobs cũ
php artisan queue:flush
```

**Khuyến nghị**:
- Thêm `Telescope` (dev) hoặc `Horizon` (prod, Redis) để monitor queue health.
- Hoặc đơn giản: schedule check `failed_jobs` count mỗi 5 phút, alert qua Slack/email nếu > 0.

```php
// Modules/WorkflowAutomation/routes/console.php
Schedule::command('queue:failed-table')->when(fn() =>
    \DB::table('failed_jobs')
        ->where('payload', 'like', '%ExecuteWorkflowAction%')
        ->where('failed_at', '>=', now()->subMinutes(10))
        ->count() > 0
)->runInBackground(); // placeholder — thay bằng notification thực tế
```

### 24.2 Retry policy theo action type

Không phải mọi executor đều nên retry như nhau:

| Action type | Retry phù hợp | Lý do |
|-------------|--------------|-------|
| `email.send` | 3 lần, backoff 10/60/300s | Mail server tạm thời unavailable |
| `webhook.call` | 2 lần, backoff 500ms/2s | Đã có `retry(2, 500)` trong Http client |
| `notification.send` | 3 lần | DB write, ít fail |
| `subject.update` | 3 lần | DB write |
| `lead.create` | 1 lần | Đã có idempotency check (email exists) |

Hiện tại `ExecuteWorkflowAction` dùng chung `jobTries=3`. Nếu cần retry per-executor, truyền `retry_count` vào `WorkflowStep` config.

### 24.3 Observability checklist

Trước khi go-live:

```
□ queue:table migration đã chạy
□ Queue worker đang chạy (supervisor hoặc systemd)
□ failed_jobs được monitor (alert nếu > 0)
□ workflow_executions.triggered_at index tồn tại (xem migration 4.4)
□ Cache driver không phải 'array' trên prod (cooldown sẽ không hoạt động)
□ WORKFLOW_QUEUE=workflows trong .env prod
□ php artisan permissions:sync đã chạy sau deploy
□ Cache meta invalidated sau deploy (php artisan cache:forget wf:meta:*)
□ SurveyResult field names verified (band_code, overall_score, weight_version)
□ PurgeOldExecutionsAction scheduled trong console.php
```

### 24.4 Scaling considerations

**Hiện tại (SQLite / database queue)**: phù hợp dev và prod nhỏ (< 1000 workflow executions/ngày).

**Khi cần scale**:
- Chuyển `QUEUE_CONNECTION=redis` + Laravel Horizon để có visibility và concurrency control.
- Tách queue riêng: `workflows` (low latency) và `workflows-heavy` (webhook, email bulk).
- Thêm index `(organization_id, status, triggered_at)` trên `workflow_executions` nếu query stats chậm.
- `PurgeOldExecutionsAction` chạy daily là đủ cho volume thấp; với volume cao, chạy hourly với chunk delete (`->limit(1000)`).
