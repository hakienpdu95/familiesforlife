# Spec: Module Survey — Database Schema

> **Mục đích kép**: Tài liệu thiết kế schema + hướng dẫn viết spec để sinh migration tự động.
> Khi Claude đọc file này, nó phải hiểu đủ để bổ sung trực tiếp vào `render_migration_file.json`
> mà không cần hỏi thêm.

---

## PHẦN 1 — QUY ƯỚC VIẾT SPEC CHO MIGRATION GENERATOR

### 1.1 File đầu ra: `render_migration_file.json`

Mỗi bảng là một **mảng JSON** (array of strings). Phần tử đầu tiên là header bảng,
các phần tử tiếp theo là cột hoặc directive đặc biệt.

```
render_migration_file.json
└── [ ...tables ]
    └── table = [ header, col1, col2, ..., directive1, directive2, ... ]
```

### 1.2 Format 7 phần (delimiter `///`)

Mỗi cột viết theo format **đúng 7 phần**, phân tách bằng `///`:

```
"tên_cột///type///length///nullable///default///modifier///comment"
```

| Vị trí | Tên      | Giá trị hợp lệ | Ví dụ |
|--------|----------|----------------|-------|
| 1 | `tên_cột`  | snake_case     | `survey_id` |
| 2 | `type`     | Xem bảng 1.3   | `unsignedBigInteger` |
| 3 | `length`   | Số, `15,2`, hoặc `__` nếu không có | `255`, `15,2`, `__` |
| 4 | `nullable` | `NOT_NULL` hoặc `_NULL` | `NOT_NULL` |
| 5 | `default`  | Giá trị hoặc `__` nếu không có | `0`, `true`, `'draft'`, `__` |
| 6 | `modifier` | Chuỗi Laravel chain hoặc `__` | `->unique()`, `->index()`, `->constrained('tbl')->onDelete('cascade')` |
| 7 | `comment`  | Mô tả ngắn hoặc `__` | `FK -> surveys` |

**Header bảng** cũng dùng format 7 phần (các phần 2–6 là `__`):

```json
"tên_bảng///__///__///Mô tả bảng"
```

### 1.3 Bảng kiểu dữ liệu — tên Laravel (KHÔNG dùng tên SQL thuần)

| Laravel type | Tương đương MySQL | Nhận `length`? | Ghi chú |
|---|---|---|---|
| `string` | VARCHAR | Có | mặc định 255 |
| `char` | CHAR | Có | |
| `text` | TEXT | Không | |
| `mediumText` | MEDIUMTEXT | Không | |
| `longText` | LONGTEXT | Không | |
| `tinyText` | TINYTEXT | Không | |
| `boolean` | TINYINT(1) | Không | dùng cho is_*, value_bool |
| `tinyInteger` | TINYINT | Không | có dấu |
| `unsignedTinyInteger` | TINYINT UNSIGNED | Không | status, type enum số |
| `smallInteger` | SMALLINT | Không | có dấu |
| `unsignedSmallInteger` | SMALLINT UNSIGNED | Không | sort_order, version |
| `integer` | INT | Không | rule_min, rule_max |
| `unsignedInteger` | INT UNSIGNED | Không | |
| `bigInteger` | BIGINT | Không | |
| `unsignedBigInteger` | BIGINT UNSIGNED | Không | FK field |
| `decimal` | DECIMAL | Có (`p,s`) | `15,2` |
| `float` | FLOAT | Không | |
| `double` | DOUBLE | Không | |
| `date` | DATE | Không | |
| `dateTime` | DATETIME | Không | |
| `timestamp` | TIMESTAMP | Không | |
| `time` | TIME | Không | |
| `json` | JSON | Không | |
| `binary` | VARBINARY | Có | binary(16) = VARBINARY(16) |
| `enum` | ENUM | `[val1,val2]` | |
| `uuid` | CHAR(36) | Không | |
| `ip` | VARCHAR(45) | Không | |

### 1.4 Cột tự động sinh — KHÔNG viết trong spec

Generator **tự prepend** 3 cột này vào mọi bảng:

```php
$table->id();                          // BIGINT UNSIGNED PK AUTO_INCREMENT
$table->uuid()->nullable()->unique();  // Public UUID expose ra ngoài
$table->unsignedInteger('order_column')->nullable()->index(); // Spatie Sortable
```

Ngoài ra, 3 cột timestamp được gom lại xử lý đặc biệt:

| Tên cột | Trong spec | Generator sinh ra |
|---|---|---|
| `created_at` + `updated_at` | Ghi cả hai | `$table->timestamps()` |
| chỉ `created_at` | Ghi một | `$table->timestamp('created_at')->nullable()` |
| `deleted_at` | Ghi là `timestamp` + `_NULL` | `$table->softDeletes()` |

> **Quy tắc**: Luôn viết `created_at` và `updated_at` vào spec nếu bảng cần. Viết `deleted_at`
> nếu cần soft delete. Không viết `id`, `uuid`, `order_column`.

### 1.5 FK — hai pattern

**Pattern A — foreignId shorthand** (dùng khi FK trỏ tới cột `id`):

```json
"survey_id///unsignedBigInteger///__///NOT_NULL///__///->constrained('surveys')->onDelete('cascade')///FK -> surveys"
```

Sinh ra: `$table->foreignId('survey_id')->constrained('surveys')->cascadeOnDelete();`

**Pattern B — FK trỏ tới cột không phải `id`** (custom column):

```json
"province_code///char///2///NOT_NULL///__///->references('province_code')->constrained('provinces')->onDelete('cascade')///FK -> provinces.province_code"
```

**Giá trị `onDelete` hợp lệ** (generator tự chuẩn hóa sang Laravel fluent):

| Viết trong spec | Sinh ra |
|---|---|
| `->onDelete('cascade')` | `->cascadeOnDelete()` |
| `->onDelete('set null')` | `->nullOnDelete()` |
| `->onDelete('restrict')` | `->restrictOnDelete()` |
| `->onDelete('no action')` | `->noActionOnDelete()` |

> FK nullable: thêm `_NULL` ở position 4, không đặt default.

### 1.6 Directives đặc biệt

Viết **sau tất cả các cột** trong cùng một bảng.

**`__index` — tạo index thường hoặc unique:**

```json
"__index///index///__///__///__///col1,col2///Mô tả index"
"__index///unique///__///__///__///col1,col2///Mô tả unique constraint"
"__index///fulltext///__///__///__///col1///Full-text search"
```

**`__primary` — composite primary key:**

```json
"__primary///__///__///__///col1,col2///__///Composite PK"
```

**`__initial_data` — seed dữ liệu mặc định ngay trong migration:**

```json
"__initial_data///__///__///__///name:Admin,email:admin@example.com,password:secret///__///__"
```

### 1.7 Quy tắc đặt thứ tự bảng — Topological Sort

Generator tự sắp xếp theo dependency. Tuy nhiên để spec dễ đọc, hãy liệt kê
**bảng cha trước bảng con**:

```
bảng độc lập → bảng phụ thuộc 1 cấp → bảng phụ thuộc 2 cấp → ...
```

Self-reference FK (cột trỏ về chính bảng đó) được bỏ qua khi tính dependency.

---

## PHẦN 2 — SCHEMA MODULE SURVEY

### Tổng quan dependency

```
surveys
 ├── survey_sections      (FK: survey_id)
 ├── survey_fields        (FK: survey_id, section_id, parent_field_id self-ref)
 │    └── survey_field_options  (FK: field_id)
 └── survey_responses     (FK: survey_id)
      └── survey_answers  (FK: response_id, field_id, option_id)
```

---

### Bảng `surveys`

Bảng gốc, không phụ thuộc bảng nào trong module.

| Cột | Type | Length | Nullable | Default | Modifier | Ghi chú |
|-----|------|--------|----------|---------|----------|---------|
| `title` | `string` | 255 | NOT_NULL | — | — | Tiêu đề khảo sát |
| `slug` | `string` | 160 | NOT_NULL | — | `->unique()` | Slug URL |
| `status` | `unsignedTinyInteger` | — | NOT_NULL | `0` | `->index()` | 0=draft 1=active 2=closed |
| `version` | `unsignedSmallInteger` | — | NOT_NULL | `1` | — | Phiên bản |
| `created_at` | `timestamp` | — | _NULL | — | — | |
| `updated_at` | `timestamp` | — | _NULL | — | — | |
| `deleted_at` | `timestamp` | — | _NULL | — | — | Soft delete |

Indexes: _(status đã có index qua modifier)_

---

### Bảng `survey_sections`

Phụ thuộc: `surveys`

| Cột | Type | Length | Nullable | Default | Modifier | Ghi chú |
|-----|------|--------|----------|---------|----------|---------|
| `survey_id` | `unsignedBigInteger` | — | NOT_NULL | — | `->constrained('surveys')->onDelete('cascade')` | FK |
| `title` | `string` | 255 | NOT_NULL | — | — | |
| `icon` | `string` | 16 | _NULL | — | — | Icon hiển thị |
| `sort_order` | `unsignedSmallInteger` | — | NOT_NULL | `0` | — | |
| `created_at` | `timestamp` | — | _NULL | — | — | |
| `updated_at` | `timestamp` | — | _NULL | — | — | |

Indexes:
- `INDEX (survey_id, sort_order)`

---

### Bảng `survey_fields`

Phụ thuộc: `surveys`, `survey_sections`, self-ref `survey_fields`

| Cột | Type | Length | Nullable | Default | Modifier | Ghi chú |
|-----|------|--------|----------|---------|----------|---------|
| `survey_id` | `unsignedBigInteger` | — | NOT_NULL | — | `->constrained('surveys')->onDelete('cascade')` | FK |
| `section_id` | `unsignedBigInteger` | — | _NULL | — | `->constrained('survey_sections')->onDelete('set null')` | FK nullable |
| `parent_field_id` | `unsignedBigInteger` | — | _NULL | — | `->constrained('survey_fields')->onDelete('set null')` | Self-ref nullable |
| `field_key` | `string` | 100 | NOT_NULL | — | — | `company_name`, `ai_tools_used` |
| `label` | `string` | 500 | NOT_NULL | — | — | Nhãn câu hỏi |
| `field_type` | `unsignedTinyInteger` | — | NOT_NULL | — | — | Loại field (enum số hóa) |
| `value_kind` | `unsignedTinyInteger` | — | NOT_NULL | — | — | Cột typed nào sẽ lưu giá trị |
| `is_required` | `boolean` | — | NOT_NULL | `false` | — | Bắt buộc điền |
| `is_active` | `boolean` | — | NOT_NULL | `true` | — | Đang hiển thị |
| `sort_order` | `unsignedSmallInteger` | — | NOT_NULL | `0` | — | |
| `rule_min` | `integer` | — | _NULL | — | — | Validation giá trị tối thiểu |
| `rule_max` | `integer` | — | _NULL | — | — | Validation giá trị tối đa |
| `rule_max_select` | `smallInteger` | — | _NULL | — | — | Giới hạn số lựa chọn multi-choice |
| `placeholder` | `string` | 255 | _NULL | — | — | |
| `created_at` | `timestamp` | — | _NULL | — | — | |
| `updated_at` | `timestamp` | — | _NULL | — | — | |

Indexes:
- `UNIQUE (survey_id, field_key)` — mỗi survey chỉ có 1 field_key
- `INDEX (survey_id, section_id, sort_order)`
- `INDEX (parent_field_id)`

---

### Bảng `survey_field_options`

Phụ thuộc: `survey_fields`

| Cột | Type | Length | Nullable | Default | Modifier | Ghi chú |
|-----|------|--------|----------|---------|----------|---------|
| `field_id` | `unsignedBigInteger` | — | NOT_NULL | — | `->constrained('survey_fields')->onDelete('cascade')` | FK |
| `option_value` | `string` | 150 | NOT_NULL | — | — | Machine value: `chatgpt` |
| `label` | `string` | 300 | NOT_NULL | — | — | Hiển thị: `ChatGPT` |
| `sort_order` | `unsignedSmallInteger` | — | NOT_NULL | `0` | — | |
| `is_other` | `boolean` | — | NOT_NULL | `false` | — | Lựa chọn "Khác" — cho nhập tay |
| `created_at` | `timestamp` | — | _NULL | — | — | |
| `updated_at` | `timestamp` | — | _NULL | — | — | |

Indexes:
- `INDEX (field_id, sort_order)`

---

### Bảng `survey_responses`

Phụ thuộc: `surveys`

| Cột | Type | Length | Nullable | Default | Modifier | Ghi chú |
|-----|------|--------|----------|---------|----------|---------|
| `survey_id` | `unsignedBigInteger` | — | NOT_NULL | — | `->constrained('surveys')->onDelete('cascade')` | FK |
| `respondent_ref` | `string` | 190 | _NULL | — | `->index()` | Email/phone match CRM |
| `respondent_ip` | `binary` | 16 | _NULL | — | — | INET6_ATON — VARBINARY(16) |
| `status` | `unsignedTinyInteger` | — | NOT_NULL | `0` | — | 0=partial 1=complete |
| `submitted_at` | `timestamp` | — | _NULL | — | — | Thời điểm nộp hoàn tất |
| `created_at` | `timestamp` | — | _NULL | — | — | |
| `updated_at` | `timestamp` | — | _NULL | — | — | |

Indexes:
- `INDEX (survey_id, status, submitted_at)` — thống kê responses
- `INDEX (respondent_ref)` — đã có qua modifier `->index()`

---

### Bảng `survey_answers`

Phụ thuộc: `survey_responses`, `survey_fields`, `survey_field_options`

**Thiết kế typed columns**: mỗi câu trả lời chỉ điền vào đúng 1 cột value_* tương ứng
với `value_kind` của field. Các cột còn lại NULL.

| Cột | Type | Length | Nullable | Default | Modifier | Ghi chú |
|-----|------|--------|----------|---------|----------|---------|
| `response_id` | `unsignedBigInteger` | — | NOT_NULL | — | `->constrained('survey_responses')->onDelete('cascade')` | FK CASCADE |
| `field_id` | `unsignedBigInteger` | — | NOT_NULL | — | `->constrained('survey_fields')->onDelete('cascade')` | FK CASCADE |
| `option_id` | `unsignedBigInteger` | — | _NULL | — | `->constrained('survey_field_options')->onDelete('set null')` | FK nullable |
| `value_string` | `string` | 500 | _NULL | — | — | Text ngắn — indexable |
| `value_text` | `text` | — | _NULL | — | — | Textarea dài — KHÔNG index |
| `value_number` | `decimal` | `15,2` | _NULL | — | — | Giá trị số |
| `value_date` | `date` | — | _NULL | — | — | Giá trị ngày |
| `value_bool` | `boolean` | — | _NULL | — | — | Giá trị Có/Không |
| `created_at` | `timestamp` | — | _NULL | — | — | Chỉ created — không updated |

> **Lưu ý**: `survey_answers` chỉ có `created_at`, không có `updated_at`.
> Câu trả lời đã nộp không được sửa — chỉ xóa và tạo lại.

Indexes:
- `INDEX (field_id, option_id)` — đếm lựa chọn
- `INDEX (field_id, value_number)` — avg / min / max / phân phối số
- `INDEX (field_id, value_bool)` — đếm Có/Không
- `INDEX (field_id, value_string)` — lọc text ngắn (prefix index 100 ký tự trong MySQL thực tế)
- `INDEX (response_id, field_id)` — dựng lại toàn bộ 1 response

---

## PHẦN 3 — CHECKLIST TRƯỚC KHI BỔ SUNG VÀO JSON

Trước khi Claude thêm bảng mới vào `render_migration_file.json`, kiểm tra:

- [ ] Không viết cột `id`, `uuid`, `order_column` (đã tự sinh)
- [ ] Dùng đúng tên type Laravel (không dùng SQL thuần như TINYINT, VARCHAR)
- [ ] Cột không nhận length → để `__` ở vị trí 3 (xem bảng 1.3)
- [ ] Nullable → `_NULL`, bắt buộc → `NOT_NULL`
- [ ] Không có default → `__`; có default → viết giá trị (`0`, `true`, `'draft'`)
- [ ] FK dùng `unsignedBigInteger` + `->constrained(...)` trong modifier
- [ ] `deleted_at` dùng type `timestamp` + `_NULL` (generator tự sinh `softDeletes()`)
- [ ] `__index` directive đặt sau tất cả cột thường
- [ ] Thứ tự bảng: cha trước con (topological sort tự xử lý nếu sai thứ tự)
- [ ] Bảng mới bổ sung **sau** `organization_settings` trong JSON
