# Workflow Automation — Specification v2
## ERD tổng quát · Đa mô hình · Thân thiện SME

> **Tham khảo**: Drupal Workflow Module (State Machine) · BPMN (User Task, Gateway) · Zapier (Event-Action)
> **Mục tiêu**: Một engine đủ linh hoạt cho mọi quy trình SME — không cần viết code
> **Cập nhật**: 2026-06-06

---

## 1. Hai mô hình tư duy — song song và bổ trợ

Phần lớn các bài toán quy trình thực tế thuộc một trong hai mô hình:

### Mô hình A — Event-Reaction (IFTTT/Zapier style)
> "Khi X xảy ra → tự động làm Y"

Phù hợp: Thông báo tự động, gửi email follow-up, tạo task, sync data, AI enrichment.

```
[Sự kiện] → [Điều kiện] → [Chuỗi hành động tự động]
Lead tạo mới → score > 70 → Assign + Notify + Create task
```

### Mô hình B — State Machine (Drupal Workflow style)
> "Đối tượng đi qua các trạng thái — mỗi chuyển trạng thái có thể kích hoạt automation"

Phù hợp: Approval flow, content moderation, vòng đời đơn hàng, quy trình HR.

```
[Entity có trạng thái] → [Ai được phép chuyển sang trạng thái nào] → [Khi chuyển → automation chạy]
Ticket: Open → In Progress → Resolved → Closed
Hóa đơn: Draft → Submitted → Approved → Paid
```

### Mô hình C — Human-in-the-Loop (BPMN User Task)
> "Workflow chờ con người phê duyệt/điền thông tin rồi mới tiếp tục"

Phù hợp: Approval flows, review content, sign-off, intake forms.

```
[Tạo yêu cầu] → [Gửi cho approver] → [CHỜ phê duyệt] → [Nếu OK → xử lý | Nếu từ chối → notify]
```

**Engine v2 hỗ trợ cả 3 mô hình trong cùng một workflow hoặc phối hợp.**

---

## 2. Taxonomy workflow thực tế — SME

| Lĩnh vực | Bài toán | Mô hình |
|---|---|---|
| **Sales** | Lead mới → score → assign → nurturing | A |
| **Sales** | Deal từ Proposal → Won: notify + tạo contract | B |
| **HR** | Nhân viên nghỉ: thu hồi quyền + offboarding | A + B |
| **HR** | Đơn nghỉ phép → chờ manager approve → xử lý | C |
| **Support** | Ticket tạo → auto-assign → 2h chưa xử lý → escalate | A |
| **Support** | Ticket chuyển Resolved → gửi satisfaction survey | B |
| **Finance** | Hóa đơn > 50tr → gửi CFO approve → khi approve → ERP | C |
| **Marketing** | User inactive 30 ngày → re-engage sequence | A (schedule) |
| **Ops/Ecom** | Đơn hàng đặt → parallel: email + kho + shipment | A (parallel) |
| **Content** | Bài viết Draft → Review → Publish: notify editor | B + C |

---

## 3. ERD Tổng quan — v2

```
organizations
     │ 1
     │ N
     ├──────────────────────────────────────────────────────────────────────┐
     │                                                                       │
  workflows ─────────────────────────────────────────────────────────────   │
  [Định nghĩa automation]                                                │   │
     │                                                                   │   │
     ├── workflow_trigger_params   (điều kiện match trigger)             │   │
     ├── workflow_conditions       (điều kiện entry workflow)            │   │
     ├── workflow_variables        (biến dùng chung toàn workflow)       │   │
     ├── workflow_input_fields     (form điền khi manual trigger)        │   │
     ├── workflow_step_groups      (nhóm bước: sequential | parallel)    │   │
     │       └── workflow_steps                                          │   │
     │               └── workflow_step_headers (webhook headers)        │   │
     └── workflow_executions       (mỗi lần chạy)                       │   │
             └── workflow_execution_steps                                │   │
             └── workflow_user_tasks (human-in-the-loop tasks)          │   │
                                                                         │   │
  workflow_templates               (thư viện template)                  │   │
                                                                         │   │
  ── STATE MACHINE (Mô hình B) ─────────────────────────────────────────┘   │
                                                                             │
  workflow_entity_states           (định nghĩa trạng thái per entity type) ─┘
     └── workflow_entity_transitions (ai được chuyển từ đâu đến đâu)
  workflow_entity_state_logs       (lịch sử chuyển trạng thái)
```

---

## 4. Schema chi tiết

### 4.1 `workflows` — bổ sung metadata SME

```php
Schema::create('workflows', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('organization_id')->index();

    // ── Định danh & hiển thị ─────────────────────────────────────
    $table->string('name', 191);
    $table->string('description', 500)->nullable();
    $table->string('category', 64)->nullable();
    // 'sales' | 'hr' | 'support' | 'finance' | 'marketing' | 'ops' | 'content' | 'custom'
    // Phân loại trong sidebar để SME dễ tìm kiếm

    $table->string('icon', 64)->nullable();
    // Tên icon (Heroicon, Lucide): 'user-plus', 'document-check', 'bell'
    // Hiển thị trong danh sách workflow

    $table->string('color', 7)->nullable();
    // Hex color: '#3B82F6' — màu dot indicator trong UI

    $table->text('tags')->nullable();
    // JSON array: ["lead", "email", "auto-assign"]
    // Để filter/search trong admin

    // ── Trạng thái định nghĩa workflow ──────────────────────────
    $table->tinyInteger('definition_status')->unsigned()->default(1);
    // 1=draft (đang xây dựng, chưa hoạt động)
    // 2=active (đang chạy)
    // 3=archived (ngừng dùng, giữ lịch sử)
    // Tách biệt với is_active (bật/tắt tạm thời)

    $table->tinyInteger('version')->unsigned()->default(1);
    // Auto-increment khi workflow được publish lại sau chỉnh sửa

    // ── Trigger ──────────────────────────────────────────────────
    $table->string('trigger_type', 64);
    // 'lead.created' | 'entity.state_changed' | 'schedule.daily' | 'manual' | 'webhook.received'
    // Thêm type mới: cấu hình trong config/workflow_automation.php, không cần migration

    // ── Điều kiện entry ─────────────────────────────────────────
    $table->tinyInteger('condition_match')->unsigned()->default(3);
    // 1=ALL(AND)  2=ANY(OR)  3=NONE(luôn pass)

    // ── Cooldown ─────────────────────────────────────────────────
    $table->tinyInteger('cooldown_type')->unsigned()->default(0);
    // 0=none 1=once_per_subject 2=per_subject_per_day 3=per_subject_per_hour
    // 4=global_per_day  5=once_per_actor  6=per_subject_custom

    $table->unsignedSmallInteger('cooldown_window_min')->nullable();
    // Dùng với cooldown_type=6: cửa sổ thời gian (phút)

    $table->tinyInteger('cooldown_count_max')->unsigned()->nullable();
    // Dùng với cooldown_type=6: tối đa N lần trong window

    // ── Quyền kích hoạt ─────────────────────────────────────────
    $table->text('allowed_trigger_roles')->nullable();
    // JSON array: ["sales_manager", "admin"] — ai được phép MANUAL trigger
    // NULL = mọi user có WORKFLOW_EDIT permission

    // ── Template nguồn ──────────────────────────────────────────
    $table->unsignedBigInteger('template_id')->nullable();
    // FK → workflow_templates.id — workflow được tạo từ template nào
    // NULL = tạo từ đầu

    // ── Thống kê ─────────────────────────────────────────────────
    $table->boolean('is_active')->default(false);
    $table->tinyInteger('priority')->unsigned()->default(5); // 1=cao nhất
    $table->unsignedInteger('run_count')->default(0);
    $table->dateTime('last_run_at')->nullable();
    $table->tinyInteger('last_run_status')->unsigned()->nullable();

    $table->unsignedBigInteger('created_by')->nullable();
    $table->unsignedBigInteger('updated_by')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['organization_id', 'trigger_type', 'is_active'], 'idx_org_trigger');
    $table->index(['organization_id', 'category'], 'idx_org_category');
});
```

---

### 4.2 `workflow_templates` — thư viện workflow *(NEW)*

Template là workflow blueprint được đóng gói, SME có thể cài một click và tùy chỉnh.

```php
Schema::create('workflow_templates', function (Blueprint $table) {
    $table->id();

    $table->string('name', 191);
    $table->string('slug', 128)->unique();
    // e.g. 'lead-nurturing-basic', 'hr-offboarding', 'invoice-approval'

    $table->string('description', 500)->nullable();
    $table->string('category', 64);
    // 'sales' | 'hr' | 'support' | 'finance' | 'marketing' | 'ops' | 'content'

    $table->string('icon', 64)->nullable();
    $table->string('color', 7)->nullable();
    $table->text('tags')->nullable(); // JSON

    $table->text('template_config');
    // JSON: full workflow definition (triggers, conditions, steps, variables)
    // Import script đọc file này để tạo workflow hoàn chỉnh cho org

    $table->string('trigger_type', 64);
    // Để admin filter template theo trigger type

    $table->boolean('is_public')->default(true);
    // true = tất cả orgs dùng được
    // false = chỉ org tạo ra (private custom template)

    $table->unsignedBigInteger('author_org_id')->nullable();
    // NULL = system template (built-in)
    // ID = org đã tạo và share template

    $table->tinyInteger('version')->unsigned()->default(1);
    $table->unsignedInteger('usage_count')->default(0);
    $table->decimal('rating', 2, 1)->nullable(); // 0.0–5.0

    $table->text('preview_description')->nullable();
    // Mô tả chi tiết cho trang template marketplace: steps, use case, ví dụ

    $table->timestamps();

    $table->index(['category', 'is_public']);
    $table->index('trigger_type');
});
```

**Template config JSON example** (`lead-nurturing-basic`):
```json
{
  "name": "Lead Nurturing Cơ Bản",
  "trigger_type": "lead.created",
  "condition_match": 3,
  "cooldown_type": 1,
  "category": "sales",
  "variables": [
    {"var_key": "company_name",   "var_value": "Tên công ty của bạn", "var_type": 1},
    {"var_key": "sales_email",    "var_value": "sales@yourcompany.com", "var_type": 5},
    {"var_key": "portal_url",     "var_value": "https://yourapp.com", "var_type": 4}
  ],
  "steps": [
    {"sort_order": 1, "label": "Gửi email chào mừng", "action_type": "email.send",
     "action_config": {"to": "{actor.email}", "subject": "Cảm ơn bạn đã quan tâm đến {var.company_name}"},
     "delay_minutes": 0},
    {"sort_order": 2, "label": "Nhắc lần 2 sau 3 ngày", "action_type": "email.send",
     "action_config": {"to": "{actor.email}", "subject": "Bạn có muốn tìm hiểu thêm?"},
     "delay_minutes": 4320}
  ]
}
```

---

### 4.3 `workflow_variables` — biến dùng chung *(NEW)*

```php
Schema::create('workflow_variables', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('workflow_id');

    $table->string('var_key', 64);
    // 'company_name' | 'support_email' | 'portal_url' | 'default_assignee_id'

    $table->text('var_value')->nullable();
    // Giá trị mặc định. Hỗ trợ template: '{actor.name}'

    $table->tinyInteger('var_type')->unsigned()->default(1);
    // 1=text  2=number  3=boolean  4=url  5=email  6=user_id

    $table->string('description', 191)->nullable();
    // "Email hỗ trợ khách hàng" — hiển thị trong builder UI

    $table->boolean('is_secret')->default(false);
    // true = mask trong UI (API tokens, secrets ngắn)

    $table->unique(['workflow_id', 'var_key'], 'uniq_wf_var');
    $table->index('workflow_id');
});
```

Dùng trong template: `{var.company_name}`, `{var.support_email}`.

---

### 4.4 `workflow_input_fields` — form cho manual trigger *(NEW)*

Khi admin kích hoạt workflow thủ công, hệ thống hiển thị form để điền thông tin. Dữ liệu điền vào trở thành `extra.*` trong TriggerPayload.

```php
Schema::create('workflow_input_fields', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('workflow_id');
    // Chỉ áp dụng khi trigger_type = 'manual'

    $table->tinyInteger('sort_order')->unsigned()->default(0);
    $table->string('field_key', 64);
    // Tên biến: 'customer_name', 'order_value', 'reason'
    // Được inject vào TriggerPayload.extra.{field_key}

    $table->string('field_label', 128);
    // "Tên khách hàng", "Giá trị đơn hàng", "Lý do"

    $table->tinyInteger('field_type')->unsigned()->default(1);
    // 1=text  2=textarea  3=number  4=select  5=date  6=boolean  7=user_select

    $table->text('field_options')->nullable();
    // JSON: cho select → [{"value":"high","label":"Cao"},{"value":"low","label":"Thấp"}]

    $table->string('placeholder', 191)->nullable();
    $table->string('default_value', 255)->nullable();
    $table->string('hint', 255)->nullable();
    $table->boolean('required')->default(false);

    $table->index(['workflow_id', 'sort_order']);
});
```

**Ví dụ dùng**: Workflow "Xử lý khiếu nại thủ công":
- Field 1: customer_name (text, required)
- Field 2: complaint_category (select: billing/technical/general, required)
- Field 3: priority (select: high/medium/low)
- Field 4: notes (textarea)

---

### 4.5 `workflow_step_groups` — nhóm bước *(NEW)*

```php
Schema::create('workflow_step_groups', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('workflow_id');

    $table->tinyInteger('sort_order')->unsigned()->default(0);
    // Thứ tự nhóm. Group thấp → chạy trước. Group kế tiếp chờ group hiện tại xong.

    $table->string('name', 128)->nullable();
    // "Gửi thông báo đa kênh", "Bước xử lý song song"

    $table->tinyInteger('execute_mode')->unsigned()->default(1);
    // 1 = sequential  (mặc định — các step chạy theo thứ tự)
    // 2 = parallel    (tất cả steps dispatch cùng lúc, chờ tất cả xong)
    // 3 = parallel_any(chỉ cần 1 step succeed → group tiếp tục)

    $table->unsignedSmallInteger('delay_minutes')->default(0);
    // Delay TRƯỚC KHI bắt đầu group này
    // Ví dụ: "Gửi follow-up 3 ngày sau" → delay_minutes = 4320

    $table->boolean('halt_workflow_on_fail')->default(false);
    // true = nếu cả group này fail (tất cả steps fail) → dừng workflow

    $table->index(['workflow_id', 'sort_order']);
});
```

---

### 4.6 `workflow_steps` — refactor hoàn toàn

```php
Schema::create('workflow_steps', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('workflow_id');
    $table->unsignedBigInteger('group_id')->nullable();
    // FK → workflow_step_groups. NULL = step độc lập trong sequential flow

    $table->tinyInteger('sort_order')->unsigned()->default(0);

    $table->string('step_key', 64)->nullable();
    // Human-readable ID: 'send_welcome', 'notify_manager', 'ai_score'
    // Unique per workflow. Dùng cho log readability.

    $table->string('label', 191)->nullable();
    // Tên hiển thị trong UI: "Gửi email chào mừng", "AI Phân tích lead"

    // ── Loại bước ────────────────────────────────────────────────
    $table->tinyInteger('step_type')->unsigned()->default(1);
    // 1 = automated  (system task — executor chạy tự động)
    // 2 = user_task  (human-in-the-loop — workflow chờ người phê duyệt/điền form)
    // 3 = control    (flow control: flow.log, không thực thi business logic)

    $table->string('action_type', 64);
    // automated:  email.send | sms.send | notification.send | slack.send
    //             subject.create | subject.update | subject.assign
    //             webhook.call | ai.call | ai.classify | ai.image
    //             platform.publish
    // user_task:  user_task.approve  (approve/reject)
    //             user_task.form     (điền form rồi tiếp tục)
    //             user_task.review   (review content rồi publish/reject)
    // control:    flow.log

    $table->text('action_config')->nullable();
    // JSON: config đặc thù của executor.
    // Schema không quy định cấu trúc → executor tự parse.
    // Mỗi executor có stepConfigFields() mô tả schema cho Builder UI.

    // ── Điều kiện per-step ────────────────────────────────────────
    $table->text('condition_config')->nullable();
    // JSON: điều kiện để CHẠY step này. Fail → skip (không fail workflow).
    // {
    //   "match": "ALL",
    //   "conditions": [
    //     {"field": "ctx.score", "operator": ">=", "value": "70", "type": "integer"}
    //   ]
    // }
    // Dùng "ctx.*" (RunContext), "extra.*", "actor.*", "var.*"

    // ── Pipeline output ───────────────────────────────────────────
    $table->string('step_output_key', 64)->nullable();
    // Executor return output → lưu vào RunContext với key này.
    // Template bước sau dùng: {ctx.KEY}

    // ── Kiểm soát luồng ──────────────────────────────────────────
    $table->boolean('halt_on_fail')->default(false);
    // true: step này fail → dừng toàn bộ pipeline, các step sau = "halted"

    $table->tinyInteger('retry_times')->unsigned()->default(3);
    // Per-step retry. AI steps: 1. Critical steps: 5. Thông thường: 3.

    $table->unsignedSmallInteger('timeout_seconds')->default(30);
    // Per-step timeout. AI steps: 60–120s. Email/notify: 10s. Webhook: 30s.

    $table->unsignedSmallInteger('delay_minutes')->default(0);
    // Delay riêng của step trong sequential flow (khi không thuộc group có delay).

    $table->timestamps();

    $table->index(['workflow_id', 'sort_order']);
    $table->index('group_id');
});
```

---

### 4.7 `workflow_conditions` — không đổi

Điều kiện ENTRY của toàn workflow. Phân biệt với `condition_config` trong steps (điều kiện SKIP một step cụ thể).

---

### 4.8 `workflow_trigger_params` — không đổi

Normalized params cho trigger matching. Vẫn tốt.

---

### 4.9 `workflow_step_headers` — không đổi

Webhook custom headers. Vẫn dùng.

---

### 4.10 `workflow_executions` — bổ sung

```php
// Thêm vào schema hiện có:
$table->tinyInteger('steps_skipped')->unsigned()->default(0);
// Steps bị skip do condition_config fail (khác với halted)

$table->tinyInteger('steps_halted')->unsigned()->default(0);
// Steps bị dừng do halt_on_fail của step trước

$table->tinyInteger('steps_waiting')->unsigned()->default(0);
// Steps đang chờ user_task hoàn thành (human-in-the-loop)

$table->text('run_context')->nullable();
// JSON: accumulated RunContext cuối pipeline
// Chứa output của tất cả steps đã chạy thành công
// Dùng cho: audit, debug, retry từ bước N

// Status enum mở rộng:
// 1=pass  2=skip  3=fail  4=partial  5=scheduled  6=halted  7=waiting_approval
```

---

### 4.11 `workflow_execution_steps` — bổ sung

```php
$table->boolean('condition_result')->nullable();
// Kết quả evaluation condition_config của step

$table->string('skip_reason', 64)->nullable();
// 'condition_failed' | 'halted_upstream' | 'parallel_skipped' | 'user_rejected'

$table->text('output_data')->nullable();
// JSON: ActionResult::output từ executor

// Status enum mở rộng:
// 1=success  2=skipped  3=failed  4=scheduled  5=halted  6=waiting (user_task)
```

---

### 4.12 `workflow_user_tasks` — human-in-the-loop *(NEW)*

Khi step có `step_type = 2 (user_task)`, engine tạo record này và **tạm dừng pipeline** tại đây. Khi user hoàn thành task → engine resume từ bước tiếp theo.

```php
Schema::create('workflow_user_tasks', function (Blueprint $table) {
    $table->id();
    $table->char('task_token', 36)->unique();
    // UUID token dùng trong URL: /workflow/tasks/{token}/respond
    // Tránh expose internal IDs trong email links

    $table->unsignedBigInteger('execution_id');
    // FK → workflow_executions
    $table->unsignedBigInteger('step_id');
    // FK → workflow_steps
    $table->unsignedBigInteger('workflow_id');
    $table->unsignedBigInteger('organization_id')->index();

    // ── Assignee ─────────────────────────────────────────────────
    $table->unsignedBigInteger('assignee_id')->nullable();
    // User cụ thể được assign task. Nếu null → dùng assignee_role

    $table->string('assignee_role', 64)->nullable();
    // Role được assign: 'sales_manager', 'cfo', 'hr_lead'
    // Tất cả users có role này đều nhận notification và có thể respond

    // ── Nội dung task ────────────────────────────────────────────
    $table->string('title', 191);
    // "Phê duyệt hóa đơn #1234", "Review bài viết: SEO Guide"

    $table->text('description')->nullable();
    // Mô tả chi tiết + context từ RunContext

    $table->text('context_snapshot')->nullable();
    // JSON: snapshot của RunContext lúc task được tạo
    // Dùng để hiển thị context cho user khi review

    // ── Form response (nếu user_task.form) ───────────────────────
    $table->text('form_config')->nullable();
    // JSON: các field user cần điền
    // Format tương tự workflow_input_fields
    // Output → lưu vào RunContext sau khi user submit

    // ── Quyết định ───────────────────────────────────────────────
    $table->text('allowed_decisions')->nullable();
    // JSON: ['approve', 'reject'] hoặc ['publish', 'request_revision', 'reject']
    // Mặc định: ['approve', 'reject']

    // ── Thời hạn ─────────────────────────────────────────────────
    $table->dateTime('due_at')->nullable();
    // Deadline. Quá hạn → on_timeout action được kích hoạt

    $table->string('on_timeout', 32)->default('fail');
    // 'fail'     = timeout → step failed → halt_on_fail quyết định tiếp
    // 'continue' = timeout → tự động approve (đặc biệt dùng cho SLA flows)
    // 'escalate' = timeout → assign lên level cao hơn

    // ── Kết quả ──────────────────────────────────────────────────
    $table->tinyInteger('status')->unsigned()->default(1);
    // 1=pending  2=completed  3=rejected  4=expired  5=cancelled

    $table->string('decision', 64)->nullable();
    // 'approve' | 'reject' | 'publish' | 'request_revision' | ...
    // Giá trị này được inject vào RunContext: {ctx.approval_decision}

    $table->text('form_response')->nullable();
    // JSON: user đã điền vào form_config
    // Được inject vào RunContext với key từ form_config.output_key

    $table->string('comment', 500)->nullable();
    // Ghi chú của người phê duyệt

    $table->unsignedBigInteger('completed_by')->nullable();
    $table->dateTime('completed_at')->nullable();
    $table->timestamp('created_at')->nullable();

    $table->index(['execution_id', 'step_id']);
    $table->index(['assignee_id', 'status']);
    $table->index(['assignee_role', 'status', 'organization_id']);
    $table->index(['status', 'due_at']); // để scheduled cleanup và timeout check
});
```

**action_config cho user_task.approve**:
```json
{
  "assignee": "role:cfo",
  "title": "Phê duyệt hóa đơn #{subject.id} — {ctx.amount} VNĐ",
  "description": "Nhà cung cấp: {ctx.vendor_name}\nMô tả: {ctx.description}\nSố tiền: {ctx.amount} VNĐ",
  "allowed_decisions": ["approve", "reject"],
  "due_hours": 48,
  "on_timeout": "escalate",
  "timeout_escalate_role": "director",
  "notification_template": "emails.approval-request"
}
```

**action_config cho user_task.form**:
```json
{
  "assignee": "user:{actor.id}",
  "title": "Hoàn thiện thông tin yêu cầu",
  "form_fields": [
    {"key": "priority", "label": "Mức độ ưu tiên", "type": "select", "options": ["high","medium","low"], "required": true},
    {"key": "notes",    "label": "Ghi chú thêm",   "type": "textarea"}
  ],
  "output_context_key": "user_form",
  "due_hours": 24
}
```

---

### 4.13 `workflow_entity_states` — State Machine *(NEW)*

Định nghĩa tập trạng thái cho từng loại entity (Lead, Ticket, Order, v.v.).

```php
Schema::create('workflow_entity_states', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('organization_id')->index();

    $table->string('entity_type', 64);
    // 'Lead' | 'Ticket' | 'Order' | 'Invoice' | 'Employee' | 'Content'
    // Mapping sang FQCN qua SubjectRegistry

    $table->string('state_key', 64);
    // Identifier code: 'new' | 'qualified' | 'proposal' | 'won' | 'lost'
    // Unique per org + entity_type

    $table->string('state_label', 128);
    // Tên hiển thị: "Mới", "Đã qualify", "Đang đề xuất", "Đã chốt", "Mất"

    $table->string('color', 7)->nullable();
    // Hex: '#10B981' (green = won), '#EF4444' (red = lost)

    $table->string('icon', 64)->nullable();
    // Tên icon hiển thị cạnh badge

    $table->string('description', 255)->nullable();
    // Giải thích trạng thái cho người dùng mới

    $table->boolean('is_initial')->default(false);
    // true = trạng thái bắt đầu mặc định khi entity được tạo

    $table->boolean('is_terminal')->default(false);
    // true = trạng thái cuối, không có outgoing transition

    $table->tinyInteger('sort_order')->unsigned()->default(0);

    $table->unique(['organization_id', 'entity_type', 'state_key'], 'uniq_entity_state');
    $table->index(['organization_id', 'entity_type', 'sort_order']);
});
```

---

### 4.14 `workflow_entity_transitions` — State Machine *(NEW)*

Định nghĩa ai được phép chuyển trạng thái từ đâu đến đâu, và automation nào chạy khi chuyển.

```php
Schema::create('workflow_entity_transitions', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('organization_id')->index();
    $table->string('entity_type', 64);

    $table->string('transition_key', 64);
    // Identifier: 'qualify' | 'submit_proposal' | 'close_won' | 'escalate'

    $table->string('transition_label', 128);
    // "Qualify lead", "Gửi đề xuất", "Chốt hợp đồng"

    $table->unsignedBigInteger('from_state_id')->nullable();
    // FK → workflow_entity_states. NULL = từ BẤT KỲ trạng thái nào
    // Dùng NULL cho: "archive" — có thể archive từ bất kỳ đâu

    $table->unsignedBigInteger('to_state_id');
    // FK → workflow_entity_states. Trạng thái đích

    // ── Phân quyền ──────────────────────────────────────────────
    $table->text('allowed_roles')->nullable();
    // JSON: ["sales_manager", "admin"] — ai được thực hiện transition này
    // NULL = mọi authenticated user của org

    $table->boolean('requires_comment')->default(false);
    // true = bắt buộc nhập lý do khi transition

    $table->boolean('requires_confirmation')->default(false);
    // true = hiện confirm dialog trước khi thực hiện

    // ── Automation ───────────────────────────────────────────────
    $table->unsignedBigInteger('triggers_workflow_id')->nullable();
    // FK → workflows: workflow nào tự động chạy khi transition này xảy ra
    // Tạo TriggerPayload với trigger_type='entity.state_changed'

    $table->tinyInteger('sort_order')->unsigned()->default(0);
    // Thứ tự hiển thị trong dropdown transitions

    $table->unique(['organization_id', 'entity_type', 'transition_key'], 'uniq_entity_trans');
    $table->index(['entity_type', 'from_state_id']);
    $table->index('triggers_workflow_id');
});
```

---

### 4.15 `workflow_entity_state_logs` — lịch sử trạng thái *(NEW)*

```php
Schema::create('workflow_entity_state_logs', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('organization_id')->index();
    $table->string('entity_type', 64);
    $table->unsignedBigInteger('entity_id');

    $table->string('from_state_key', 64)->nullable();
    // NULL = entity vừa được tạo (chuyển từ "không có" sang trạng thái đầu)

    $table->string('to_state_key', 64);

    $table->string('transition_key', 64)->nullable();
    // NULL = state được set trực tiếp (không qua defined transition)

    $table->unsignedBigInteger('actor_id')->nullable();
    // User thực hiện transition. NULL = system (workflow tự động set state)

    $table->string('comment', 500)->nullable();

    $table->unsignedBigInteger('triggered_execution_id')->nullable();
    // FK → workflow_executions: execution nào được tạo ra do transition này

    $table->timestamp('created_at')->nullable();

    $table->index(['entity_type', 'entity_id', 'created_at']);
    $table->index(['organization_id', 'entity_type', 'to_state_key']);
});
```

---

## 5. Tóm tắt delta — v1 → v2

```diff
NEW TABLES:
  + workflow_templates           (thư viện blueprint — cài 1-click)
  + workflow_variables           (biến dùng chung trong workflow)
  + workflow_input_fields        (form điền khi manual trigger)
  + workflow_step_groups         (nhóm bước: sequential | parallel | parallel_any)
  + workflow_user_tasks          (human-in-the-loop: approve, form, review)
  + workflow_entity_states       (State Machine: định nghĩa trạng thái per entity)
  + workflow_entity_transitions  (State Machine: ai chuyển từ đâu đến đâu)
  + workflow_entity_state_logs   (lịch sử chuyển trạng thái)

MODIFIED — workflows:
  + category, icon, color, tags  (metadata cho UX/filter)
  + definition_status            (draft | active | archived — cho chính workflow definition)
  + version                      (auto-increment khi republish)
  + allowed_trigger_roles        (ai được manual trigger)
  + template_id                  (tạo từ template nào)

MODIFIED — workflow_steps:
  + group_id FK nullable         (thuộc group nào)
  + step_key VARCHAR(64)         (human-readable ID)
  + label VARCHAR(191)           (display name)
  + step_type TINYINT            (1=automated, 2=user_task, 3=control)
  + action_config TEXT JSON      (thay toàn bộ flat typed columns)
  + condition_config TEXT JSON   (điều kiện skip per-step)
  + step_output_key VARCHAR(64)  (key lưu output vào RunContext)
  + halt_on_fail BOOL
  + retry_times TINYINT
  + timeout_seconds SMALLINT
  - email_to, email_subject, email_template   (→ action_config)
  - notif_title, notif_body, notif_target     (→ action_config)
  - update_model, update_field, update_value  (→ action_config)
  - webhook_url, webhook_method, webhook_secret (→ action_config)
  - lead_*, user_*                            (→ action_config)

MODIFIED — workflow_executions:
  + steps_skipped, steps_halted, steps_waiting
  + run_context TEXT (accumulated pipeline output)
  ~ status: thêm 6=halted, 7=waiting_approval

MODIFIED — workflow_execution_steps:
  + condition_result BOOL nullable
  + skip_reason VARCHAR(64)
  + output_data TEXT nullable
  ~ status: thêm 5=halted, 6=waiting
```

---

## 6. Trigger Types v2 — đầy đủ

```php
// config/workflow_automation.php
'triggers' => [

    // ── Event-based (Mô hình A) ──────────────────────────────────
    'lead.created'            => ['module' => 'Lead',    'label' => 'Lead mới được tạo'],
    'lead.updated'            => ['module' => 'Lead',    'label' => 'Lead được cập nhật'],
    'ticket.created'          => ['module' => 'Support', 'label' => 'Ticket mới'],
    'order.created'           => ['module' => 'Order',   'label' => 'Đơn hàng mới'],
    'order.payment_confirmed' => ['module' => 'Order',   'label' => 'Thanh toán xác nhận'],
    'survey.submitted'        => ['module' => 'Survey',  'label' => 'Survey được nộp'],
    'user.registered'         => ['module' => 'User',    'label' => 'User đăng ký mới'],
    'employee.created'        => ['module' => 'HR',      'label' => 'Nhân viên mới'],
    'employee.terminated'     => ['module' => 'HR',      'label' => 'Nhân viên nghỉ việc'],

    // ── State Machine (Mô hình B) ────────────────────────────────
    'entity.state_changed'    => [
        'module' => 'Core',
        'label'  => 'Đối tượng đổi trạng thái',
        'config_fields' => [
            ['key' => 'entity_type', 'label' => 'Loại đối tượng', 'type' => 'entity_type_select'],
            ['key' => 'from_state',  'label' => 'Từ trạng thái',  'type' => 'state_select', 'required' => false,
             'hint' => 'Để trống = từ bất kỳ trạng thái'],
            ['key' => 'to_state',    'label' => 'Đến trạng thái', 'type' => 'state_select'],
        ],
        'available_fields' => [
            ['key' => 'extra.entity_type',   'label' => 'Loại entity',         'type' => 'string'],
            ['key' => 'extra.from_state',    'label' => 'Trạng thái trước',    'type' => 'string'],
            ['key' => 'extra.to_state',      'label' => 'Trạng thái mới',      'type' => 'string'],
            ['key' => 'extra.transition_key','label' => 'Transition thực hiện', 'type' => 'string'],
            ['key' => 'extra.comment',       'label' => 'Lý do chuyển trạng thái', 'type' => 'string'],
        ],
    ],

    // ── Schedule (cron) ──────────────────────────────────────────
    'schedule.daily'   => ['module' => 'Schedule', 'label' => 'Hàng ngày'],
    'schedule.weekly'  => ['module' => 'Schedule', 'label' => 'Hàng tuần'],
    'schedule.monthly' => ['module' => 'Schedule', 'label' => 'Hàng tháng'],
    'schedule.hourly'  => ['module' => 'Schedule', 'label' => 'Mỗi giờ'],

    // ── Manual ───────────────────────────────────────────────────
    'manual'           => ['module' => 'Core', 'label' => 'Kích hoạt thủ công'],

    // ── Webhook inbound ──────────────────────────────────────────
    'webhook.received' => [
        'module' => 'Core',
        'label'  => 'Webhook từ hệ thống ngoài',
        'config_fields' => [
            ['key' => 'source_key', 'label' => 'Nguồn',        'type' => 'text'],
            ['key' => 'secret',     'label' => 'HMAC Secret',  'type' => 'password', 'required' => false],
        ],
    ],
],
```

---

## 7. Executor Types v2 — đầy đủ

| Type | Loại | Queue | Timeout | Mô tả |
|---|---|---|---|---|
| `email.send` | automated | default | 15s | Gửi email |
| `sms.send` | automated | default | 10s | Gửi SMS |
| `notification.send` | automated | default | 5s | In-app notification |
| `slack.send` | automated | default | 10s | Slack/Teams webhook |
| `subject.create` | automated | default | 10s | Tạo Eloquent record |
| `subject.update` | automated | default | 10s | Cập nhật field |
| `subject.assign` | automated | default | 10s | Gán cho user |
| `subject.state_set` | automated | default | 10s | Set state machine state trực tiếp |
| `webhook.call` | automated | default | 30s | Gọi HTTP endpoint ngoài |
| `ai.call` | automated | ai | 90s | Gọi LLM API |
| `ai.classify` | automated | ai | 30s | Phân loại text/category |
| `ai.summarize` | automated | ai | 30s | Tóm tắt nội dung |
| `ai.image` | automated | ai | 120s | Tạo ảnh từ prompt |
| `platform.publish` | automated | default | 30s | Đăng lên CMS ngoài |
| `user_task.approve` | user_task | — | (due_at) | Chờ approve/reject |
| `user_task.form` | user_task | — | (due_at) | Chờ điền form |
| `user_task.review` | user_task | — | (due_at) | Chờ review content |
| `flow.log` | control | default | 1s | Ghi log debug |

---

## 8. RunContext — template engine v2

```
resolve priority trong {template}:

{var.KEY}          → workflow_variables[KEY]
{ctx.KEY}          → RunContext.accumulated[KEY]
{ctx.KEY.nested.0} → nested dot-notation + array index
{step.N.KEY}       → stepOutputs[N][KEY]
{actor.email}      → payload.actorEmail
{actor.name}       → payload.actorName
{actor.role}       → payload.actorRole
{extra.FIELD}      → payload.extra[FIELD]
{subject.id}       → payload.subjectId
{subject.type}     → payload.subjectType
{input.KEY}        → manual trigger input form (extra.KEY alias)
{env:KEY}          → env('KEY') từ whitelist
{now}              → Carbon::now()->toDateTimeString()
{now:Y-m-d}        → Carbon::now()->format('Y-m-d')
{task.decision}    → decision của user_task vừa hoàn thành
{task.form.KEY}    → form response KEY từ user_task.form
```

---

## 9. Engine v2 — luồng thực thi

### 9.1 Xử lý step_type=automated (System Task)

```
execute_step(step, ctx, execId):
  1. Evaluate condition_config → skip nếu fail
  2. Executor::execute(step, ctx) → ActionResult
  3. if success: ctx->put(step_output_key, result.output)
  4. if fail && halt_on_fail: mark remaining as halted, BREAK
  5. Log step (output_data, condition_result, skip_reason, duration_ms)
```

### 9.2 Xử lý step_type=user_task (Human Task)

```
execute_step(step, ctx, execId) khi step_type=2:
  1. Tạo workflow_user_tasks record (status=pending, context_snapshot=ctx.toJSON())
  2. Gửi notification/email cho assignee
  3. Log step (status=waiting)
  4. PAUSE execution → return
  // Workflow execution status = waiting_approval

// Khi user respond (qua UI /workflow/tasks/{token}/respond):
  1. Update workflow_user_tasks (status, decision, form_response, completed_by, completed_at)
  2. Inject vào RunContext:
       ctx->put('task.decision', decision)
       ctx->put('task.form', form_response)
  3. RESUME ExecuteWorkflowAction từ step tiếp theo
  4. Update workflow_executions (status, steps_waiting--)
```

### 9.3 Xử lý State Machine transition

```
Khi user thực hiện transition (Lead: Pending → Qualified):
  1. Check workflow_entity_transitions.allowed_roles
  2. Validate from_state / to_state
  3. Update entity.current_state = to_state_key
  4. Log vào workflow_entity_state_logs
  5. if transition.triggers_workflow_id:
       WorkflowDispatcher::fire(TriggerPayload::forStateChange(
           entity_type, entity_id, from_state, to_state, transition_key, comment
       ))
```

---

## 10. Ví dụ workflow thực tế trên v2

### 10.1 Lead Qualification + Approval (kết hợp A + C)

```
Trigger: lead.created
Condition: extra.budget > 50000000 (budget > 50 triệu)

Step 1 — ai.call
  label: "AI Score Lead"
  step_output_key: "analysis"
  halt_on_fail: false
  action_config: {
    user_prompt: "Score lead {extra.name} ({extra.company}). JSON: {score, segment, next_action}",
    output_format: "json"
  }

Step 2 — subject.update
  label: "Ghi score vào lead"
  action_config: {model: "Lead", field: "score", value: "{ctx.analysis.score}"}

Step 3 — user_task.approve
  label: "Sales Manager phê duyệt qualified"
  step_type: 2 (user_task)
  halt_on_fail: true
  action_config: {
    assignee: "role:sales_manager",
    title: "Lead {extra.name} cần xem xét — AI Score: {ctx.analysis.score}/100",
    allowed_decisions: ["approve", "reject", "more_info"],
    due_hours: 24,
    on_timeout: "escalate",
    timeout_escalate_role: "sales_director"
  }

Step 4a — subject.state_set
  label: "Chuyển lead → Qualified"
  condition_config: {conditions: [{field: "task.decision", operator: "=", value: "approve"}]}
  action_config: {model: "Lead", state: "qualified"}

Step 4b — notification.send
  label: "Thông báo lead bị từ chối"
  condition_config: {conditions: [{field: "task.decision", operator: "=", value: "reject"}]}
  action_config: {
    target: "user:{actor.id}",
    title: "Lead {extra.name} không đủ điều kiện",
    body: "Lý do: {task.comment}"
  }
```

---

### 10.2 Nhân viên nghỉ việc — Offboarding (State Machine + Parallel)

```
// State Machine: Employee
States: Active → Terminating → Terminated
Transitions:
  - "Initiate Offboarding": Active → Terminating [allowed: hr_lead]
    triggers_workflow: "Employee Offboarding Automation"
  - "Complete Offboarding": Terminating → Terminated [allowed: hr_admin]

// Workflow: "Employee Offboarding Automation"
Trigger: entity.state_changed
  params: entity_type=Employee, from_state=active, to_state=terminating

Group 1 (parallel, delay=0):
  Step 1a — webhook.call [Thu hồi quyền truy cập hệ thống]
  Step 1b — subject.update [Update trạng thái lương → Pending Final]
  Step 1c — notification.send [Notify IT team]
  Step 1d — notification.send [Notify Finance team]

Group 2 (sequential, delay=0):
  Step 2 — subject.create
    label: "Tạo Offboarding Checklist"
    action_config: {
      model: "Task",
      fields: {title: "Offboarding: {extra.employee_name}", due_days: 14, type: "offboarding"}
    }

Group 3 (sequential, delay=10080):  ← 7 ngày sau
  Step 3 — user_task.approve
    label: "HR xác nhận hoàn tất offboarding"
    step_type: 2
    action_config: {
      assignee: "role:hr_admin",
      title: "Xác nhận offboarding {extra.employee_name} đã hoàn tất",
      allowed_decisions: ["complete", "extend"]
    }
```

---

### 10.3 Invoice Approval Flow (Mô hình C thuần túy)

```
Trigger: entity.state_changed
  params: entity_type=Invoice, from_state=draft, to_state=submitted

Step 1 — notification.send
  label: "Notify accounting team"
  action_config: {target: "role:accounting", title: "Hóa đơn mới #{subject.id} cần xử lý"}

Step 2 — user_task.approve
  step_type: 2
  label: "CFO phê duyệt"
  condition_config: {conditions: [{field: "extra.amount", operator: ">=", value: "50000000"}]}
  action_config: {
    assignee: "role:cfo",
    title: "Hóa đơn #{subject.id}: {extra.amount} VNĐ cần phê duyệt",
    description: "Nhà CC: {extra.vendor_name}\nMô tả: {extra.description}",
    due_hours: 48,
    on_timeout: "fail"
  }

Step 3a — subject.state_set
  condition_config: {conditions: [{field: "task.decision", operator: "=", value: "approve"}]}
  action_config: {model: "Invoice", state: "approved"}

Step 3b — webhook.call
  label: "Sync sang ERP"
  condition_config: {conditions: [{field: "task.decision", operator: "=", value: "approve"}]}
  action_config: {url: "{var.erp_webhook}", method: "POST"}

Step 4 — email.send
  label: "Thông báo kết quả"
  action_config: {
    to: "{extra.submitter_email}",
    subject: "Hóa đơn #{subject.id}: {task.decision}",
    template: "emails.invoice-decision"
  }
```

---

### 10.4 Customer Re-engagement (Schedule + Conditional)

```
Trigger: schedule.weekly (thứ Hai 09:00)
CooldownType: GlobalPerDay

Step 1 — ai.call
  label: "Lấy danh sách user inactive"
  step_output_key: "inactive_users"
  action_config: {
    user_prompt: "Danh sách users không active 30 ngày. Trả JSON: [{id,email,name,days_inactive}]",
    output_format: "json"
  }

Step 2 — webhook.call
  label: "Trigger batch email campaign"
  action_config: {
    url: "{var.email_platform_webhook}",
    body: {
      campaign_id: "{var.reengagement_campaign_id}",
      recipients: "{ctx.inactive_users}"
    }
  }

Step 3 — notification.send
  label: "Báo cáo cho Marketing Manager"
  action_config: {
    target: "role:marketing_manager",
    title: "Re-engagement campaign đã gửi: {ctx.inactive_users.length} users"
  }
```

---

## 11. CooldownType v2 — 7 loại

| Value | Label | Cache key | TTL |
|---|---|---|---|
| 0 | Không giới hạn | — | — |
| 1 | Mỗi subject 1 lần duy nhất | `cd:once:{org}:{wf}:{subject}` | 365 ngày |
| 2 | Mỗi subject 1 lần/ngày | `cd:day:{org}:{wf}:{subject}:{Ymd}` | 86400s |
| 3 | Mỗi subject 1 lần/giờ | `cd:hr:{org}:{wf}:{subject}:{YmdH}` | 3600s |
| 4 | Toàn workflow 1 lần/ngày | `cd:gday:{org}:{wf}:{Ymd}` | 86400s |
| 5 | Mỗi actor 1 lần duy nhất | `cd:actor:{org}:{wf}:{actor}` | 365 ngày |
| 6 | N lần/M phút per subject | `cd:custom:{org}:{wf}:{subject}:{bucket}` | window * 60s |

---

## 12. Thiết kế cố tình không đưa vào v2

| Feature | Lý do defer | Dấu hiệu cần implement |
|---|---|---|
| **Full DAG routing** (multi-branch với visual flow editor) | Cần graph execution engine + visual builder phức tạp | Khi có > 3 nhánh điều kiện trong 1 workflow |
| **Loop/Iteration** (lặp qua list, retry loop) | Cần stack để tránh infinite loop | Khi cần gửi email cho từng item trong list |
| **Sub-workflow** (gọi workflow khác như function) | Cần handle output mapping + prevent cycle | Khi có workflow logic tái sử dụng nhiều lần |
| **Event Correlation** (chờ 2 event xảy ra rồi mới tiếp tục) | Saga pattern, stateful wait | Khi cần "đơn được cả kho lẫn finance confirm" |
| **Compensation/Rollback** (undo nếu bước sau fail) | Cần idempotent executors + rollback log | Finance/ERP integration |

**V2 đáp ứng ~85% bài toán SME thực tế**. Các feature defer phù hợp enterprise edition (v3).

---

## 13. Migration path — v1 → v2

```
Phase 1 — Schema (không break, additive):
  Migration A: CREATE workflow_templates
  Migration B: CREATE workflow_variables
  Migration C: CREATE workflow_input_fields
  Migration D: CREATE workflow_step_groups
  Migration E: CREATE workflow_user_tasks
  Migration F: CREATE workflow_entity_states
  Migration G: CREATE workflow_entity_transitions
  Migration H: CREATE workflow_entity_state_logs
  Migration I: ALTER workflows  (add category, icon, color, tags, definition_status, ...)
  Migration J: ALTER workflow_steps (add group_id, step_key, step_type, action_config, ...)
  Migration K: ALTER workflow_executions (add run_context, steps_skipped, steps_halted, ...)
  Migration L: ALTER workflow_execution_steps (add condition_result, skip_reason, output_data)

Phase 2 — Data migration:
  Script: foreach WorkflowStep WHERE action_config IS NULL:
    → Build action_config JSON từ typed columns cũ
    → UPDATE

Phase 3 — Code:
  - Executors: đọc từ action_config, không còn typed columns
  - Engine: thêm RunContext, group execution, step conditions, user_task handling
  - State machine: WorkflowEntityStateService

Phase 4 — Cleanup:
  Migration M: DROP deprecated typed columns (sau verify phase 3)
```

---

*Spec v2 · 2026-06-06 · Tham khảo: Drupal Workflow Module + BPMN User Task + Zapier Event-Action*

Sources:
- [Drupal Workflow — Pantheon Learning Center](https://pantheon.io/learning-center/drupal/workflow)
- [Workflow Engine vs State Machine](https://workflowengine.io/blog/workflow-engine-vs-state-machine/)
- [Temporal: Workflow Engine Design Principles](https://temporal.io/blog/workflow-engine-principles)
