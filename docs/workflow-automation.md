# Workflow Automation — Hướng dẫn đầy đủ

> Module tự động hoá quy trình nội bộ. Kích hoạt từ sự kiện hệ thống, đánh giá điều kiện, thực thi chuỗi hành động (email / thông báo / cập nhật dữ liệu / webhook). Thiết kế contract-based, không JSON trong DB, đa tenant.

---

## Mục lục

1. [Tổng quan kiến trúc](#1-tổng-quan-kiến-trúc)
2. [Database Schema](#2-database-schema)
3. [Enums — Các kiểu liệt kê](#3-enums)
4. [TriggerPayload — Dữ liệu kích hoạt](#4-triggerpayload)
5. [Trigger Types — Nguồn kích hoạt](#5-trigger-types)
6. [Action Executors — Hành động](#6-action-executors)
7. [Điều kiện (Conditions)](#7-điều-kiện-conditions)
8. [Cooldown — Chống kích hoạt lặp](#8-cooldown)
9. [Phân quyền theo vai trò](#9-phân-quyền-theo-vai-trò)
10. [Hướng dẫn sử dụng giao diện](#10-hướng-dẫn-sử-dụng-giao-diện)
11. [API Endpoints](#11-api-endpoints)
12. [Tích hợp module mới](#12-tích-hợp-module-mới)
13. [WorkflowDispatcher — Kích hoạt từ code](#13-workflowdispatcher)
14. [Cấu hình môi trường](#14-cấu-hình-môi-trường)
15. [Queue Worker và Scheduler](#15-queue-worker-và-scheduler)
16. [Luồng thực thi chi tiết](#16-luồng-thực-thi-chi-tiết)

---

## 1. Tổng quan kiến trúc

```
Sự kiện xảy ra (e.g. survey submit)
        │
        ▼
WorkflowDispatcher::fire(TriggerPayload)
        │
        ▼ dispatch job async (queue: workflows)
ExecuteWorkflowAction::handle()
        │
        ├─ TriggerRegistry::matchingWorkflows()     → lấy workflows khớp trigger_type
        ├─ CooldownGuard::allow()                   → kiểm tra cooldown
        ├─ ConditionEvaluator::evaluate()           → kiểm tra conditions
        │
        └─ Với mỗi step:
                └─ ExecuteWorkflowStepAction::handle()
                        └─ ActionRegistry::find(type)->execute(step, payload)
```

### Các thành phần chính

| Thành phần | Vị trí | Vai trò |
|---|---|---|
| `WorkflowDispatcher` | `Core/` | Entry point — fire & fireAfterCommit |
| `TriggerRegistry` | `Core/` | Quản lý TriggerSource; tìm workflows khớp |
| `ActionRegistry` | `Core/` | Quản lý ActionExecutor theo type string |
| `SubjectRegistry` | `Core/` | Map subject type → model + updatable fields |
| `ConditionEvaluator` | `Core/` | Đánh giá 12 toán tử điều kiện |
| `CooldownGuard` | `Core/` | Cache-based cooldown check |
| `WorkflowBuilderService` | `Services/` | Validate + persist workflow từ form |
| `TriggerPayload` | `Data/` | DTO dữ liệu kích hoạt + template rendering |
| `ActionResult` | `Data/` | DTO kết quả mỗi bước thực thi |

---

## 2. Database Schema

7 bảng, **không có cột JSON**. Tất cả cấu hình dạng KV hoặc cột rời.

### `workflows` — Định nghĩa workflow

| Cột | Kiểu | Mô tả |
|---|---|---|
| `id` | bigint PK | |
| `organization_id` | bigint | Tenant scope |
| `name` | varchar(191) | Tên hiển thị |
| `description` | varchar(500) | Mô tả (nullable) |
| `trigger_type` | varchar(64) | VD: `manual`, `survey.submitted` |
| `condition_match` | tinyint | 1=All, 2=Any, 3=None (bỏ qua điều kiện) |
| `cooldown_type` | tinyint | 0–4 (xem Enum CooldownType) |
| `is_active` | boolean | Bật/tắt workflow |
| `priority` | tinyint | 1–10, ưu tiên thực thi (nhỏ hơn = cao hơn) |
| `run_count` | int | Tổng số lần đã chạy |
| `last_run_at` | datetime | Thời điểm chạy gần nhất |
| `last_run_status` | tinyint | Status lần cuối (WorkflowStatus enum) |
| `created_by`, `updated_by` | bigint | Audit trail |

**Index quan trọng:**
- `idx_org_trigger` (`organization_id`, `trigger_type`, `is_active`) — query chính khi fire
- `idx_org_priority` (`organization_id`, `is_active`, `priority`) — listing UI

---

### `workflow_trigger_params` — Tham số trigger

Thay thế JSON bằng bảng KV. Dùng để lưu các filter cấu hình của trigger (VD: filter theo `band_code`).

| Cột | Kiểu | Mô tả |
|---|---|---|
| `workflow_id` | bigint | FK → workflows |
| `param_key` | varchar(64) | Tên tham số |
| `param_value` | varchar(255) | Giá trị |
| `param_type` | tinyint | 1=string, 2=int, 3=float, 4=bool |

---

### `workflow_conditions` — Điều kiện lọc

| Cột | Kiểu | Mô tả |
|---|---|---|
| `workflow_id` | bigint | FK → workflows |
| `sort_order` | tinyint | Thứ tự đánh giá |
| `field` | varchar(128) | Tên field payload, VD: `extra.band_code` |
| `operator` | varchar(32) | Toán tử (xem Điều kiện) |
| `value` | varchar(500) | Giá trị so sánh |
| `value_type` | tinyint | 1=string, 2=int, 3=float, 4=bool |

---

### `workflow_steps` — Các bước thực thi

Mỗi step là một action. Cột lưu config theo từng action type (cột nào không dùng để null).

| Nhóm | Cột | Dùng cho |
|---|---|---|
| **Chung** | `sort_order`, `action_type`, `delay_minutes` | Mọi step |
| **Email** | `email_to`, `email_subject`, `email_template` | `email.send` |
| **Notification** | `notif_title`, `notif_body`, `notif_target` | `notification.send` |
| **Subject update** | `update_model`, `update_field`, `update_value` | `subject.update` |
| **Webhook** | `webhook_url`, `webhook_method`, `webhook_secret` | `webhook.call` |

---

### `workflow_step_headers` — HTTP headers cho webhook

| Cột | Kiểu | Mô tả |
|---|---|---|
| `step_id` | bigint | FK → workflow_steps |
| `header_key` | varchar(128) | Tên header, VD: `Authorization` |
| `header_value` | varchar(500) | Giá trị header |

---

### `workflow_executions` — Lịch sử thực thi

Mỗi lần workflow chạy = 1 dòng.

| Cột | Kiểu | Mô tả |
|---|---|---|
| `run_id` | char(36) UNIQUE | UUID — idempotency guard |
| `trigger_type` | varchar(64) | Snapshot trigger type |
| `subject_type`, `subject_id` | | Đối tượng kích hoạt |
| `actor_id` | bigint | User thực hiện (nếu có) |
| `status` | tinyint | WorkflowStatus enum |
| `skip_reason` | varchar(64) | `cooldown` / `condition_false` / `no_steps` |
| `condition_result` | boolean | Kết quả điều kiện |
| `steps_total/success/failed/scheduled` | tinyint | Thống kê step |
| `duration_ms` | smallint | Tổng thời gian thực thi |
| `triggered_at`, `executed_at`, `finished_at` | datetime(3) | Mốc thời gian |

---

### `workflow_execution_steps` — Chi tiết từng bước

| Cột | Kiểu | Mô tả |
|---|---|---|
| `execution_id` | bigint | FK → workflow_executions |
| `step_id` | bigint | FK → workflow_steps (snapshot) |
| `action_type` | varchar(64) | Snapshot action type |
| `status` | tinyint | WorkflowStatus enum |
| `error_message` | varchar(500) | Thông báo lỗi (nếu fail) |
| `duration_ms` | smallint | Thời gian step này |
| `attempts` | tinyint | Số lần thử |
| `executed_at` | datetime(3) | Thời điểm thực thi |

---

## 3. Enums

### `WorkflowStatus` — Trạng thái thực thi

| Value | Name | Mô tả |
|---|---|---|
| 1 | `Pass` | Thành công |
| 2 | `Skip` | Bỏ qua (cooldown / condition false / không có steps) |
| 3 | `Fail` | Thất bại |
| 4 | `Partial` | Một số step thành công, một số thất bại |
| 5 | `Scheduled` | Đang chờ (step có delay) |

---

### `CooldownType` — Kiểu cooldown

| Value | Name | Mô tả | TTL Cache |
|---|---|---|---|
| 0 | `None` | Không giới hạn | — |
| 1 | `OncePerSubject` | Mỗi subject chỉ 1 lần (mãi mãi) | 10 năm |
| 2 | `PerSubjectPerDay` | Mỗi subject tối đa 1 lần/ngày | Đến cuối ngày |
| 3 | `PerSubjectPerHour` | Mỗi subject tối đa 1 lần/giờ | Đến cuối giờ |
| 4 | `GlobalPerDay` | Toàn bộ org tối đa 1 lần/ngày | Đến cuối ngày |

> Cache key không bao giờ `forever()`. TTL tự hết hạn → Redis không tích lũy key thừa.

---

### `ConditionMatch` — Kiểu đánh giá điều kiện

| Value | Name | Mô tả |
|---|---|---|
| 1 | `All` | Tất cả điều kiện phải đúng (AND) |
| 2 | `Any` | Ít nhất một điều kiện đúng (OR) |
| 3 | `None` | Bỏ qua điều kiện, luôn thực thi |

---

### `OperatorType` — 12 toán tử điều kiện

| Operator | Mô tả | Ví dụ value |
|---|---|---|
| `=` | Bằng (loose) | `active` |
| `!=` | Không bằng | `inactive` |
| `>` | Lớn hơn (numeric) | `80` |
| `>=` | Lớn hơn hoặc bằng | `70` |
| `<` | Nhỏ hơn | `50` |
| `<=` | Nhỏ hơn hoặc bằng | `60` |
| `in` | Nằm trong danh sách | `bronze\|silver\|gold` (ngăn cách `\|`) |
| `not_in` | Không nằm trong danh sách | `inactive\|deleted` |
| `contains` | Chuỗi con | `@gmail` |
| `starts_with` | Bắt đầu bằng | `VN` |
| `is_empty` | Rỗng / null | *(không cần value)* |
| `is_not_empty` | Không rỗng | *(không cần value)* |

---

## 4. TriggerPayload

DTO truyền dữ liệu sự kiện qua toàn bộ pipeline. Hỗ trợ **template rendering** `{field.path}` trong config text.

### Các field có thể dùng trong template

| Template path | Mô tả |
|---|---|
| `{trigger.type}` | Type trigger, VD: `survey.submitted` |
| `{trigger.module}` | Module nguồn, VD: `Survey` |
| `{actor.id}` | ID user thực hiện |
| `{actor.email}` | Email user thực hiện |
| `{actor.role}` | Role user thực hiện |
| `{subject.type}` | Loại đối tượng, VD: `SurveyResponse` |
| `{subject.id}` | ID đối tượng |
| `{extra.*}` | Bất kỳ field trong mảng `extra` |

### Ví dụ sử dụng template

```
# email_to
{actor.email}

# email_subject
[Kết quả khảo sát] Đạt mức {extra.band_code}

# notif_body
Người dùng {actor.email} vừa hoàn thành khảo sát với điểm {extra.overall_score}%

# webhook_url
https://hooks.example.com/notify?org={actor.id}
```

> Nếu field không tồn tại trong payload, placeholder `{field}` giữ nguyên (không bị xóa).

---

## 5. Trigger Types

### Triggers hiện có

#### `manual` — Kích hoạt thủ công

Trigger dùng để chạy workflow từ giao diện. Không có config fields bổ sung.

| Field payload | Mô tả |
|---|---|
| `actor.email` | Email user bấm nút |
| `actor.id` | ID user |
| `subject.type` | `null` |
| `subject.id` | `null` |

---

#### `survey.submitted` — Khi có bài khảo sát được nộp

Kích hoạt sau khi `SubmitSurveyAction` hoàn thành transaction.

| Field payload | Mô tả |
|---|---|
| `actor.email` | `respondent_ref` của response |
| `subject.type` | `SurveyResponse` |
| `subject.id` | ID của SurveyResponse |
| `{extra.survey_id}` | ID khảo sát |
| `{extra.survey_slug}` | Slug khảo sát |
| `{extra.respondent_ref}` | Email/ref người làm khảo sát |

**Config fields (lưu trong trigger_params):**
- `survey_id` — lọc theo ID khảo sát cụ thể (để trống = mọi khảo sát)

---

#### `survey.result_calculated` — Khi điểm khảo sát được tính

Kích hoạt sau khi `CalculateSurveyScoreAction` hoàn thành.

| Field payload | Mô tả |
|---|---|
| `actor.email` | `respondent_ref` của response |
| `subject.type` | `SurveyResponse` |
| `subject.id` | ID của SurveyResponse |
| `{extra.survey_id}` | ID khảo sát |
| `{extra.band_code}` | Mức đánh giá (maturity_level), VD: `bronze` |
| `{extra.overall_score}` | Điểm tổng (0–100) |
| `{extra.weight_version}` | Phiên bản trọng số |

**Config fields (lưu trong trigger_params):**
- `band_code` — lọc theo mức đánh giá cụ thể (để trống = mọi mức)

---

## 6. Action Executors

### `email.send` — Gửi email

Gửi email qua queue (không block luồng chính).

| Config field | Mô tả | Hỗ trợ template |
|---|---|---|
| `email_to` | Địa chỉ đích, nhiều địa chỉ ngăn cách `,` | ✓ (`{actor.email}`) |
| `email_subject` | Tiêu đề email | ✓ |
| `email_template` | Blade view path (để trống dùng template mặc định) | — |

> Template mặc định: `workflowautomation::emails.generic`
> File: `Modules/WorkflowAutomation/resources/views/emails/generic.blade.php`

**Lưu ý:**
- `email_to` có thể chứa nhiều email, ngăn cách bằng dấu phẩy
- Email không hợp lệ (không pass `FILTER_VALIDATE_EMAIL`) tự động loại bỏ
- Nếu sau filter không còn email hợp lệ → step fail với lý do rõ ràng

---

### `notification.send` — Gửi thông báo nội bộ

Gửi Laravel Notification đến user trong hệ thống.

| Config field | Mô tả | Giá trị hợp lệ |
|---|---|---|
| `notif_target` | Đối tượng nhận | `actor`, `admin`, `user:{id}`, `role:{slug}` |
| `notif_title` | Tiêu đề thông báo | Hỗ trợ template |
| `notif_body` | Nội dung thông báo | Hỗ trợ template |

**Cú pháp `notif_target`:**

| Giá trị | Nhận |
|---|---|
| `actor` | User kích hoạt workflow |
| `admin` | Tất cả user role `system_admin` |
| `user:42` | User có ID = 42 |
| `role:sales` | Tất cả user có role `sales` |

---

### `subject.update` — Cập nhật dữ liệu

Cập nhật field của model liên kết với payload.

| Config field | Mô tả |
|---|---|
| `update_model` | Loại subject (VD: `SurveyResponse`) |
| `update_field` | Field cần cập nhật (chỉ các field trong `updatableFields`) |
| `update_value` | Giá trị mới, hỗ trợ template |

> Chỉ các field được khai báo trong `SubjectRegistry` mới được phép cập nhật — ngăn chặn mass assignment ngoài ý muốn.

---

### `webhook.call` — Gọi webhook ngoài

Gửi HTTP request đến URL ngoài với payload tự động.

| Config field | Mô tả |
|---|---|
| `webhook_url` | URL đích, hỗ trợ template |
| `webhook_method` | 1=GET, 2=POST, 3=PUT |
| `webhook_secret` | HMAC-SHA256 secret (tùy chọn) |

**HTTP Headers:** Thêm trong phần "Custom Headers" của form — lưu vào bảng `workflow_step_headers`.

**Payload tự động gửi (POST/PUT):**
```json
{
  "workflow_trigger": "survey.result_calculated",
  "source_module": "Survey",
  "organization_id": 1,
  "subject_type": "SurveyResponse",
  "subject_id": 123,
  "actor_email": "user@example.com",
  "extra": { "band_code": "gold", "overall_score": 85 },
  "fired_at": "2026-05-28T10:00:00+07:00"
}
```

**HMAC Signature:** Nếu cấu hình `webhook_secret`, header `X-Workflow-Signature` sẽ tự động thêm vào:
```
X-Workflow-Signature: sha256=<hmac-hex>
```

**Timeout & Retry:** Lấy từ config (`webhook_timeout` = 15s, `webhook_max_retries` = 2).

---

## 7. Điều kiện (Conditions)

Workflow chỉ thực thi khi điều kiện thoả mãn. Bỏ qua bằng cách đặt `condition_match = None`.

### Cách hoạt động

1. Load tất cả conditions của workflow (eager load từ DB)
2. Với mỗi condition, gọi `payload.resolve(field)` để lấy giá trị thực tế
3. Cast `value` sang đúng kiểu (`value_type`)
4. Đánh giá toán tử
5. Tổng hợp theo `condition_match` (All/Any)

### Ví dụ điều kiện thực tế

```
# Chỉ chạy khi điểm đạt từ 70%
field: extra.overall_score | operator: >= | value: 70 | value_type: int

# Chỉ chạy khi band là gold hoặc platinum
field: extra.band_code | operator: in | value: gold|platinum

# Chỉ chạy khi email không rỗng
field: actor.email | operator: is_not_empty
```

---

## 8. Cooldown

Ngăn workflow chạy quá nhiều lần trong thời gian ngắn. Sử dụng cache (Redis/file) với TTL tự hết hạn.

### Cache key pattern

| CooldownType | Cache key |
|---|---|
| `OncePerSubject` | `wf:cd:once:{org}:{workflow}:{subject}` |
| `PerSubjectPerDay` | `wf:cd:day:{org}:{workflow}:{subject}:{Ymd}` |
| `PerSubjectPerHour` | `wf:cd:hr:{org}:{workflow}:{subject}:{YmdH}` |
| `GlobalPerDay` | `wf:cd:gday:{org}:{workflow}:{Ymd}` |

> `{org}` = organization_id, `{workflow}` = workflow_id, `{subject}` = subject_id

---

## 9. Phân quyền theo vai trò

| Permission | Được làm |
|---|---|
| `workflow.monitor` | Xem danh sách, xem chi tiết, xem lịch sử |
| `workflow.edit` | Tạo mới, chỉnh sửa, bật/tắt, chạy thủ công |
| `workflow.full_config` | Xoá workflow |

### Phân quyền theo role

| Role | Quyền workflow |
|---|---|
| CEO | `workflow.monitor` |
| Sales | — |
| Ops | `workflow.monitor`, `workflow.edit` |
| Marketing | — |
| HR | — |
| AI Operator | `workflow.monitor`, (không có edit) |
| System Admin | `workflow.monitor`, `workflow.edit`, `workflow.full_config` |
| Viewer | — |

> Cấu hình tại `config/permissions.php`. Thay đổi → chạy `php artisan permissions:sync` để đồng bộ.

---

## 10. Hướng dẫn sử dụng giao diện

### Truy cập module

**Sidebar → Hệ thống → Workflow**

Cần quyền `workflow.monitor` để thấy menu.

---

### Danh sách Workflow

URL: `/dashboard/workflows`

Hiển thị Tabulator table với:
- Tên workflow + badge trạng thái (Active/Inactive)
- Trigger type
- Số lần chạy (`run_count`)
- Lần chạy gần nhất + badge status
- Nút: Xem / Sửa / Bật-Tắt / Xoá

**Bộ lọc:**
- Ô tìm kiếm theo tên
- Dropdown lọc theo trạng thái (Active / Inactive / Tất cả)

---

### Tạo / Sửa Workflow

URL: `/dashboard/workflows/create` hoặc `/dashboard/workflows/{id}/edit`

Form chia 5 phần:

#### Phần 1 — Thông tin cơ bản
- **Tên workflow** (bắt buộc, max 191 ký tự)
- **Mô tả** (tùy chọn)
- **Ưu tiên** (1–10, mặc định 5; thấp hơn = chạy trước)
- **Trạng thái** (Active / Inactive)

#### Phần 2 — Nguồn kích hoạt (Trigger)
- Chọn trigger type từ dropdown (nhóm theo module)
- Các config fields hiện ra tùy theo trigger đã chọn
- VD: trigger `survey.result_calculated` → hiện field `band_code` (lọc mức đánh giá)

#### Phần 3 — Điều kiện (Conditions)
- Chọn `condition_match`: Tất cả (AND) / Bất kỳ (OR) / Không cần
- Thêm/xóa điều kiện: chọn field → operator → value
- Kéo để sắp xếp thứ tự (sort_order)

#### Phần 4 — Các bước thực thi (Steps)
- Thêm nhiều step, kéo để sắp xếp thứ tự
- Mỗi step: chọn action type → các config field hiện ra tùy loại
- **Trì hoãn (Delay)**: nhập số phút — step sẽ dispatch delay job thay vì chạy ngay
- Step `webhook.call`: thêm HTTP header custom (key/value pairs)

#### Phần 5 — Cooldown
- Chọn kiểu cooldown từ dropdown
- Giải thích ngắn hiện dưới dropdown

---

### Chi tiết Workflow

URL: `/dashboard/workflows/{id}`

Hiển thị:
- Thẻ thống kê: tổng runs, lần cuối, status lần cuối
- Cấu hình trigger params
- Danh sách conditions
- Danh sách steps (với config fields)
- Nút: **Bật/Tắt** (AJAX) | **Chạy thủ công** (AJAX, cần `workflow.edit`)

---

### Lịch sử thực thi

URL: `/dashboard/workflows/{id}/executions`

Tabulator table với:
- `run_id` (UUID rút gọn)
- Trigger type, subject
- Status badge (Pass / Skip / Fail / Partial / Scheduled)
- Thời gian chạy, duration
- Lọc theo status, date range

Click vào dòng → xem chi tiết từng step tại `/dashboard/workflows/executions/{id}`.

---

### Chi tiết Execution

URL: `/dashboard/workflows/executions/{id}`

- Thông tin chung: trigger, actor, subject, timing
- Bảng step logs: action type, status, duration, error message

---

## 11. API Endpoints

Tất cả API dưới prefix `/backend/api/workflows`, yêu cầu xác thực + quyền `workflow.monitor`.

### `GET /backend/api/workflows` — Danh sách (Tabulator)

Query params:
| Param | Mô tả |
|---|---|
| `search` | Tìm theo tên |
| `is_active` | `1` / `0` |
| `sort` | Tên cột |
| `order` | `asc` / `desc` |
| `page`, `size` | Phân trang |

Response:
```json
{
  "data": [...],
  "last_page": 3,
  "total": 25
}
```

---

### `GET /backend/api/workflows/meta` — Metadata builder

Cached 600 giây. Dùng để populate dropdowns trong form builder.

Response:
```json
{
  "trigger_groups": {
    "Core": [{ "type": "manual", "label": "Thủ công", "config_fields": [] }],
    "Survey": [
      { "type": "survey.submitted", "label": "...", "config_fields": [...] },
      { "type": "survey.result_calculated", "label": "...", "config_fields": [...] }
    ]
  },
  "action_groups": {
    "Core": [
      { "type": "email.send", "label": "Gửi email", "config_fields": [...] },
      ...
    ]
  },
  "subjects": {
    "SurveyResponse": { "label": "Survey Response", "updatableFields": [...] }
  },
  "operators": [...],
  "cooldown_types": [...]
}
```

---

### `GET /backend/api/workflows/executions` — Lịch sử (Tabulator)

Query params: `workflow_id`, `status`, `date_from`, `date_to`, `page`, `size`

---

### `GET /backend/api/workflows/stats` — Dashboard stats

Cached 120 giây.

```json
{
  "total": 12,
  "active": 8,
  "runs_today": 45,
  "fails_today": 2,
  "recent_failures": [...]
}
```

---

### `GET /backend/api/workflows/subject-fields/{type}` — Fields của subject

Trả về danh sách updatable fields cho dropdown khi chọn action `subject.update`.

---

## 12. Tích hợp module mới

Để một module mới phát sinh trigger hoặc action, cần 3 bước:

### Bước 1 — Tạo TriggerSource

```php
// Modules/YourModule/app/WorkflowTriggers/YourTrigger.php
namespace Modules\YourModule\WorkflowTriggers;

use Modules\WorkflowAutomation\Contracts\TriggerSource;
use Modules\WorkflowAutomation\Data\TriggerPayload;
use Modules\WorkflowAutomation\Models\Workflow;

class YourTrigger implements TriggerSource
{
    public function type(): string  { return 'yourmodule.event_name'; }
    public function label(): string { return 'Mô tả ngắn gọn'; }
    public function module(): string { return 'YourModule'; }

    // Config fields hiện trong form builder
    public function configFields(): array
    {
        return [
            ['key' => 'filter_field', 'label' => 'Lọc theo', 'type' => 'text'],
        ];
    }

    // Xác định workflow này có khớp không
    public function matches(Workflow $workflow, TriggerPayload $payload): bool
    {
        if ($payload->triggerType !== $this->type()) return false;
        // Kiểm tra trigger_params nếu cần
        $param = $workflow->triggerParams->firstWhere('param_key', 'filter_field');
        if ($param && $param->param_value) {
            return ($payload->extra['your_field'] ?? null) === $param->param_value;
        }
        return true;
    }
}
```

---

### Bước 2 — Đăng ký trong ServiceProvider của module

```php
// Modules/YourModule/app/Providers/YourModuleServiceProvider.php

public function boot(): void
{
    parent::boot();

    // Guard: chỉ đăng ký khi WorkflowAutomation đã boot
    if (app()->bound(\Modules\WorkflowAutomation\Core\TriggerRegistry::class)) {
        app(\Modules\WorkflowAutomation\Core\TriggerRegistry::class)
            ->register(new \Modules\YourModule\WorkflowTriggers\YourTrigger());
    }

    // Nếu cần đăng ký subject cho action subject.update
    if (app()->bound(\Modules\WorkflowAutomation\Core\SubjectRegistry::class)) {
        app(\Modules\WorkflowAutomation\Core\SubjectRegistry::class)->register(
            'YourModel',
            label: 'Tên hiển thị',
            updatableFields: [
                ['field' => 'status', 'label' => 'Trạng thái', 'type' => 'string'],
            ],
            resolver: fn($payload) =>
                \Modules\YourModule\Models\YourModel::find($payload->subjectId),
        );
    }
}
```

---

### Bước 3 — Kích hoạt trigger từ action/service

```php
use Modules\WorkflowAutomation\Core\WorkflowDispatcher;
use Modules\WorkflowAutomation\Data\TriggerPayload;

// Sau khi hoàn thành logic nghiệp vụ (ngoài transaction)
WorkflowDispatcher::fire(new TriggerPayload(
    triggerType:    'yourmodule.event_name',
    sourceModule:   'YourModule',
    organizationId: TenantContext::getOrganizationId(),
    actorId:        auth()->id(),
    actorEmail:     auth()->user()?->email,
    actorName:      auth()->user()?->name,
    actorRole:      null,
    subjectType:    'YourModel',
    subjectId:      $model->id,
    subjectLabel:   "YourModel #{$model->id}",
    extra: [
        'your_field' => $model->field_value,
    ],
));
```

---

### Tạo ActionExecutor riêng (tùy chọn)

```php
namespace Modules\YourModule\WorkflowExecutors;

use Modules\WorkflowAutomation\Contracts\ActionExecutor;

class YourActionExecutor implements ActionExecutor
{
    public function type(): string   { return 'yourmodule.action_name'; }
    public function label(): string  { return 'Mô tả hành động'; }
    public function module(): string { return 'YourModule'; }

    public function stepConfigFields(): array { return [...]; }

    public function execute(WorkflowStep $step, TriggerPayload $payload): ActionResult
    {
        // Logic thực thi
    }
}
```

Đăng ký trong ServiceProvider:
```php
app(\Modules\WorkflowAutomation\Core\ActionRegistry::class)
    ->register(app(\Modules\YourModule\WorkflowExecutors\YourActionExecutor::class));
```

---

## 13. WorkflowDispatcher

Entry point duy nhất để kích hoạt workflow từ code.

### `WorkflowDispatcher::fire(TriggerPayload $payload)`

Dispatch ngay lập tức. Dùng khi gọi **ngoài** database transaction.

```php
WorkflowDispatcher::fire($payload);
```

### `WorkflowDispatcher::fireAfterCommit(TriggerPayload $payload)`

Dispatch sau khi transaction commit thành công. Dùng khi gọi **trong** transaction.

```php
DB::transaction(function() use ($payload) {
    // ... database operations ...
    WorkflowDispatcher::fireAfterCommit($payload);
});
```

> **Lưu ý:** Luôn ưu tiên `fireAfterCommit` khi trong transaction để tránh workflow chạy trước khi data được commit.

### Idempotency

Mỗi lần `fire()` tạo một `run_id` UUID ngẫu nhiên. Khi job chạy, kiểm tra `run_id` có tồn tại trong `workflow_executions` chưa. Nếu có → bỏ qua (không chạy lại). Điều này bảo vệ khỏi job bị enqueue hai lần do retry queue.

---

## 14. Cấu hình môi trường

File config: `config/workflow_automation.php`

| Env variable | Default | Mô tả |
|---|---|---|
| `WORKFLOW_QUEUE` | `workflows` | Tên queue xử lý workflow jobs |
| `WORKFLOW_RETAIN_DAYS` | `60` | Số ngày giữ lịch sử execution |
| `WORKFLOW_WEBHOOK_TIMEOUT` | `15` | Timeout HTTP webhook (giây) |
| `WORKFLOW_WEBHOOK_RETRIES` | `2` | Số lần retry nếu webhook thất bại |
| `WORKFLOW_ALLOW_MANUAL` | `true` | Cho phép kích hoạt thủ công từ UI |
| `WORKFLOW_META_CACHE_TTL` | `600` | TTL cache metadata builder (giây) |
| `WORKFLOW_META_VERSION` | `1` | Tăng khi thêm trigger/action mới để bust cache |

### Cài đặt `.env` tối thiểu cho production

```dotenv
QUEUE_CONNECTION=redis          # Bắt buộc: KHÔNG dùng sync
WORKFLOW_QUEUE=workflows
WORKFLOW_RETAIN_DAYS=60
WORKFLOW_WEBHOOK_TIMEOUT=15
WORKFLOW_WEBHOOK_RETRIES=2
```

> **Quan trọng:** `QUEUE_CONNECTION=sync` sẽ khiến workflow chạy đồng bộ, block request. Chỉ chấp nhận trong dev/test.

---

## 15. Queue Worker và Scheduler

### Khởi động queue worker (development)

```bash
php artisan queue:listen --queue=workflows,default
```

### Production (Supervisor)

```ini
[program:workflow-worker]
command=php /var/www/html/minhan/artisan queue:work redis --queue=workflows --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=2
```

### Scheduler — Dọn dẹp lịch sử cũ

`PurgeOldExecutionsAction` chạy hàng ngày lúc 02:00, xóa các execution cũ hơn `WORKFLOW_RETAIN_DAYS` ngày.

Đã đăng ký trong `routes/console.php`:
```php
Schedule::call(\Modules\WorkflowAutomation\Actions\PurgeOldExecutionsAction::make())
    ->name('workflow:purge-executions')
    ->dailyAt('02:00')
    ->onOneServer();
```

Khởi động scheduler:
```bash
php artisan schedule:work    # development
# hoặc cron: * * * * * cd /var/www/html/minhan && php artisan schedule:run
```

---

## 16. Luồng thực thi chi tiết

```
1. Sự kiện xảy ra (VD: user submit survey)
   └─ SubmitSurveyAction::handle() gọi WorkflowDispatcher::fire(payload)

2. WorkflowDispatcher
   ├─ Tạo run_id = UUID::generate()
   └─ Dispatch ExecuteWorkflowAction::dispatch(payload, run_id) → queue: workflows

3. ExecuteWorkflowAction::handle() [chạy trong queue worker]
   ├─ Kiểm tra idempotency: DB::table('workflow_executions')->where('run_id')->exists()
   │     Nếu đã tồn tại → return (bỏ qua)
   ├─ TriggerRegistry::matchingWorkflows(payload)
   │     → SELECT workflows WHERE org + trigger_type + is_active=1
   │     → Eager load triggerParams
   │     → Gọi trigger->matches(workflow, payload) để filter
   └─ Với mỗi workflow khớp:
         ├─ CooldownGuard::allow() → nếu không → skip với reason=cooldown
         ├─ ConditionEvaluator::evaluate() → nếu false → skip với reason=condition_false
         ├─ Ghi workflow_executions (status=pending)
         └─ Với mỗi step theo sort_order:
               └─ ExecuteWorkflowStepAction::handle()
                     ├─ Nếu delay_minutes > 0:
                     │     Ghi execution_step (status=Scheduled)
                     │     Dispatch self()->delay(minutes) → job mới
                     └─ Nếu không có delay:
                           ├─ Ghi execution_step (status=pending)
                           ├─ ActionRegistry::find(step.action_type)->execute(step, payload)
                           └─ Cập nhật execution_step (Pass / Fail + error_message + duration_ms)

4. Sau khi tất cả steps xong:
   └─ Cập nhật workflow_executions (status, steps counts, duration_ms, finished_at)
   └─ Cập nhật workflows.run_count++, last_run_at, last_run_status
   └─ ActivityLogger::info() ghi audit log
```

---

## Checklist triển khai

- [ ] Chạy migration: `php artisan migrate`
- [ ] Chạy seeder (nếu cần reset quyền): `php artisan db:seed --class=RolePermissionSeeder`
- [ ] Cấu hình `.env`: `QUEUE_CONNECTION=redis`, `WORKFLOW_QUEUE=workflows`
- [ ] Khởi động queue worker với queue `workflows`
- [ ] Khởi động scheduler (`cron` hoặc `php artisan schedule:work`)
- [ ] Tăng `WORKFLOW_META_VERSION` mỗi khi thêm trigger/action mới để bust cache
- [ ] Kiểm tra user có đúng role để thấy sidebar Workflow (cần `workflow.monitor`)

---

*Module WorkflowAutomation — `/Modules/WorkflowAutomation/`*
*Spec gốc: `spec/workflow.md`*
