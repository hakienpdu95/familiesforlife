# Survey Module Specification

**Module**: `Modules/Survey`
**Architecture**: Advanced Vertical Slice (AVSA) + CQRS-lite + Laravel Modules + Laravel Actions
**Mục tiêu**: Module khảo sát động (dynamic survey). Backend là single source of truth cho cấu trúc khảo sát; frontend build form dựa theo definition và submit data map đúng theo `field_key`/`option_value`. Chỉ nhận data + thống kê trong CRM backend. KHÔNG chấm điểm, KHÔNG AI scoring.

**Tech stack**:
- spatie/laravel-data (DTO + validate)
- lorisleiva/laravel-actions (mỗi use-case = 1 Action)
- nwidart/laravel-modules
- rap2hpoutre/fast-excel (export)
- spatie/laravel-activitylog (log thay đổi definition)

**Lưu ý quan trọng**: Toàn bộ 6 bảng DB ĐÃ TỒN TẠI (surveys, survey_sections, survey_fields, survey_field_options, survey_responses, survey_answers). Claude KHÔNG tạo migration, KHÔNG sửa schema. Chỉ build code dựa trên schema có sẵn.

## 1. Responsibilities
- Đọc/serve definition khảo sát (sections > fields > options) cho frontend
- Nhận và validate submit, map data động vào typed columns
- Thống kê/aggregate data theo từng field cho dashboard backend
- Export responses ra Excel
- Quản lý vòng đời definition (versioning bằng deactivate, không delete)

## 2. Directory Structure (AVSA)

Modules/Survey/
├── Actions/
│   ├── SubmitSurveyAction.php
│   ├── BuildSurveySchemaAction.php
│   └── ExportSurveyResponsesAction.php
├── Data/
│   ├── SurveySchemaData.php          # output GET /schema
│   ├── SurveyResponseData.php        # input POST /submit
│   └── SurveyAnswerData.php
├── Enums/
│   ├── FieldType.php                 # text=1, textarea=2, ... boolean=9
│   ├── ValueKind.php                 # string=1, text=2, ... option=6
│   └── SurveyStatus.php
├── Models/
│   ├── Survey.php
│   ├── SurveySection.php
│   ├── SurveyField.php
│   ├── SurveyFieldOption.php
│   ├── SurveyResponse.php
│   └── SurveyAnswer.php
├── Services/
│   └── SurveyStatsService.php
├── Support/
│   └── AnswerValueResolver.php       # quyết định cột lưu theo value_kind
├── Providers/
│   └── SurveyServiceProvider.php
├── Http/
│   ├── Controllers/
│   │   └── SurveyController.php
│   └── Requests/
│       └── SubmitSurveyRequest.php
├── Routes/
│   ├── api.php
│   └── web.php
├── Tests/
└── config.php

## 3. Key Rules (Claude PHẢI tuân thủ nghiêm ngặt)

- KHÔNG tạo/sửa migration. Schema đã cố định. Chỉ viết Model trỏ đúng bảng/cột có sẵn.
- KHÔNG dùng JSON để lưu hoặc query data. Mọi giá trị vào đúng typed column theo `value_kind`.
- Mapping `value_kind` → cột vật lý là DUY NHẤT một nơi quyết định: `AnswerValueResolver`. Không rải switch-case khắp nơi.
- `field_key` và `option_value` là machine name BẤT BIẾN. Frontend giao tiếp bằng key này, KHÔNG bằng label.
- Submit PHẢI validate đủ 5 lớp (mục 5) TRƯỚC KHI insert. Field/option lạ → reject, không lưu rác.
- Mọi insert khi submit phải nằm trong 1 DB transaction.
- checkbox/multi-choice: mỗi option được chọn = MỘT row riêng trong survey_answers (cùng response_id + field_id, khác option_id).
- option `is_other = 1` + user điền text → row có cả option_id (option "Khác") + value_string (nội dung).
- Versioning: sửa field đang có data → set `is_active = 0`, tạo field mới. KHÔNG UPDATE field, KHÔNG DELETE field/option đã có answer.
- Mọi query thống kê PHẢI index-backed (đi qua index `field_id, option_id` / `field_id, value_number` / `field_id, value_bool`). Không full table scan, không filter trên cột không index.
- field_type/value_kind đọc/ghi dưới dạng Enum (TINYINT trong DB), không dùng string literal rải rác.
- Tối ưu hiệu suất, dễ mở rộng, tuân thủ single responsibility.

## 4. Mapping field_type → value_kind → cột lưu (CHUẨN DUY NHẤT)

| field_type | value_kind | Cột lưu      | Ghi chú                        |
|------------|------------|--------------|--------------------------------|
| text       | string     | value_string | input ngắn, có index           |
| textarea   | text       | value_text   | mô tả dài, KHÔNG index         |
| number     | number     | value_number |                                |
| select     | option     | option_id    | dropdown 1 lựa chọn            |
| radio      | option     | option_id    | chọn 1                         |
| checkbox   | option     | option_id    | chọn nhiều — mỗi option 1 row  |
| rating     | number     | value_number | scale 1-5                      |
| date       | date       | value_date   |                                |
| boolean    | bool       | value_bool   | Có/Không đơn lẻ                |

`AnswerValueResolver` nhận `value_kind` + `value` → trả về mảng `[cột => giá trị]` để insert. Đây là điểm tập trung logic, mọi nơi khác chỉ gọi resolver này.

## 5. Validate Submit — 5 lớp bắt buộc (theo thứ tự)

1. field_key tồn tại trong survey này và `is_active = 1` → nếu không, reject.
2. field_type khớp kiểu value (checkbox phải nhận array; number phải numeric; date phải date hợp lệ).
3. option_value tồn tại trong survey_field_options của đúng field đó → không cho option tự chế.
4. is_required = 1 mà thiếu hoặc rỗng → reject với message rõ field nào.
5. rule_min / rule_max (number, độ dài text) và rule_max_select (số option tối đa của multi-choice) phải thỏa.

Validate đặt trong SubmitSurveyRequest (rule tĩnh) + SubmitSurveyAction (rule động dựa definition). Lỗi trả về 422 với key = field_key.

## 6. API Contract

### GET /api/surveys/{slug}/schema
- Chỉ trả survey `status = active`.
- Output: SurveySchemaData — survey info + sections[] (sort theo sort_order) + mỗi section có fields[] (chỉ is_active=1, sort theo sort_order) + mỗi field có options[] (sort theo sort_order).
- Eager load: sections.fields.options (chống N+1).

### POST /api/surveys/{slug}/submit
- Input: SurveyResponseData { respondent_ref?, answers[] }.
- answers[]: { field_key, value, other_text? }. value là scalar hoặc array (multi-choice).
- Flow: validate 5 lớp → tạo survey_response → loop answers qua AnswerValueResolver → insert survey_answers → trong 1 transaction.
- Output 201: { response_id }. Lỗi 422: { errors: { field_key: [...] } }.

### GET /api/surveys/{slug}/stats
- Output per field theo type:
  - choice (select/radio/checkbox): distribution [{ option_value, label, count, percent }]
  - number/rating: { count, avg, min, max }
  - boolean: { yes_count, no_count, total }
  - text/textarea: chỉ trả count (không aggregate nội dung)
- total_responses.
- Mọi query đi qua SurveyStatsService, index-backed.

### GET /api/surveys/{slug}/responses
- Danh sách responses, filter theo submitted_at range + respondent_ref.
- Query param ?export=xlsx → trả file Excel qua ExportSurveyResponsesAction.

## 7. Nguyên tắc kỹ thuật từng layer

- **Models**: chỉ relationship + casts (field_type/value_kind cast sang Enum) + scopes (scopeActive, scopeForSurvey). KHÔNG nhét business logic.
- **Actions**: mỗi Action một use-case, dùng `AsAction`, gọi được cả qua controller và CLI. Logic ghi data nằm ở đây.
- **Services**: SurveyStatsService chỉ chứa query đọc (CQRS-lite: tách read khỏi write). Không ghi DB.
- **Data (DTO)**: validate input/shape output. Không chứa query.
- **Support/AnswerValueResolver**: nơi DUY NHẤT map value_kind → cột. Pure, dễ test.
- **Enums**: nguồn sự thật cho field_type/value_kind/status. DB lưu TINYINT, code dùng Enum.

## 8. Priority Tasks (thực thi theo đúng thứ tự)

1. Tạo Enums: `FieldType`, `ValueKind`, `SurveyStatus` (backed enum TINYINT) + helper `FieldType::valueKind()` map type → kind.
2. Tạo 6 Models trỏ đúng bảng có sẵn + relationships đầy đủ + casts Enum + scopes. KHÔNG migration.
3. Tạo `AnswerValueResolver` — input (ValueKind, value) → output [column => value]. Viết test trước cho resolver này.
4. Tạo `BuildSurveySchemaAction` + `SurveySchemaData` → phục vụ GET /schema.
5. Tạo `SurveyResponseData` + `SurveyAnswerData` + `SubmitSurveyRequest` (validate tĩnh).
6. Tạo `SubmitSurveyAction` — validate động 5 lớp + transaction + loop resolver. Đây là task lõi.
7. Tạo `SurveyStatsService` — các method aggregate index-backed theo từng loại field.
8. Tạo `ExportSurveyResponsesAction` (fast-excel) — pivot answers theo field thành cột.
9. Tạo `SurveyController` + routes (api.php) nối 4 endpoint.
10. Viết Feature test cho submit (happy path + reject field lạ + reject option lạ + reject thiếu required).

**Bắt đầu ngay bằng Task 1 (Enums) và Task 2 (Models), sau đó dừng để tôi review trước khi sang Task 3.**

Claude chỉ được tạo/sửa file bên trong `Modules/Survey/`. KHÔNG động vào migration hay schema DB.