# Hướng dẫn sử dụng Migration System

## Tổng quan hệ thống

```
render_migration_file.json  ──[migration:generate]──→  database/migrations/generated/
render_extension_file.json  ──[extension:generate]──→  database/migrations/extensions/
Modules/*/database/migrations/  ←── chỉ dùng khi JSON không đủ (xem Trường hợp 3)
```

**Nguyên tắc**: Luôn sửa JSON trước, generate file sau — không sửa thẳng vào `generated/` hay `extensions/`.

---

## Trường hợp 1 — Tạo bảng mới

### Bước 1: Thêm entry vào `render_migration_file.json`

Mở file, thêm một array mới vào cuối danh sách (trước dấu `]` cuối cùng):

```json
[
  "tasks///__///__///Bảng quản lý công việc",
  "organization_id///unsignedBigInteger///__///NOT_NULL///__///->constrained('organizations')->onDelete('cascade')///FK tổ chức",
  "assigned_to///unsignedBigInteger///__///_NULL///NULL///->constrained('users')->onDelete('set null')///Người được giao",
  "title///string///255///NOT_NULL///__///__///Tiêu đề công việc",
  "description///text///__///_NULL///NULL///__///Mô tả chi tiết",
  "status///enum///[todo,in_progress,done,cancelled]///NOT_NULL///'todo'///->index()///Trạng thái",
  "due_date///date///__///_NULL///NULL///__///Hạn chót",
  "created_by///unsignedBigInteger///__///NOT_NULL///__///->constrained('users')->onDelete('restrict')///Người tạo",
  "created_at///timestamp///__///_NULL///NULL///__///Thời gian tạo",
  "updated_at///timestamp///__///_NULL///NULL///__///Thời gian cập nhật",
  "deleted_at///timestamp///__///_NULL///NULL///__///Thời gian xóa mềm",
  "__index///index///__///__///__///organization_id,status,due_date|idx_tasks_org_status///__",
  "__index///index///__///__///__///assigned_to,status|idx_tasks_assignee///__"
]
```

### Bước 2: Generate và áp dụng

**Môi trường DEV** (xóa DB, tạo lại từ đầu):
```bash
php artisan migration:generate --fresh
```

**Môi trường PROD** (chỉ chạy file mới):
```bash
php artisan migration:generate   # sinh file vào generated/
php artisan migrate              # áp dụng vào DB
```

### Bước 3: Commit

```bash
git add render_migration_file.json database/migrations/generated/
git commit -m "add tasks table"
```

---

## Trường hợp 2 — Thêm cột vào bảng đã có

### Bước 1: Thêm entry vào `render_extension_file.json`

Mở file, thêm array mới (hoặc **cập nhật entry hiện có** nếu bảng đã có extension):

**Bảng chưa có extension** — thêm entry mới:
```json
[
  "tasks///add///__///Thêm priority và parent_task vào tasks",
  "priority///unsignedTinyInteger///__///NOT_NULL///2///__///1=urgent 2=normal 3=low",
  "parent_task_id///unsignedBigInteger///__///_NULL///NULL///->constrained('tasks')->onDelete('set null')///Task cha (subtask)",
  "__index///index///__///__///__///organization_id,priority,status|idx_tasks_priority///__"
]
```

> Cột anchor `__` (trường thứ 3 trong header) nghĩa là không dùng `->after()` — cột sẽ được append vào cuối bảng.
> Nếu muốn chèn sau một cột cụ thể đã tồn tại, thay `__` bằng tên cột đó, ví dụ: `tasks///add///due_date///...`

**Bảng đã có extension** — tìm entry cũ và append thêm cột vào cuối array đó:
```json
[
  "users///add///email///...",
  "organization_id///...",
  "is_active///...",
  "avatar_url///string///500///_NULL///NULL///__///URL ảnh đại diện"   ← thêm vào đây
]
```

### Bước 2: Generate và áp dụng

```bash
php artisan extension:generate
php artisan migrate
```

### Bước 3: Commit

```bash
git add render_extension_file.json database/migrations/extensions/
git commit -m "add priority and parent_task_id to tasks"
```

---

## Trường hợp 3 — Module migration (ngoại lệ)

Chỉ dùng khi JSON **không thể biểu diễn** được:

| Tình huống | Ví dụ |
|-----------|-------|
| Virtual/generated column | `col AS (expr) VIRTUAL` |
| Prefix index MySQL | `DB::raw('company(32)')` |
| Data migration | Seed dữ liệu kèm schema |
| CHECK constraint | `ALTER TABLE ... ADD CHECK (...)` |
| Đổi tên cột, thay đổi kiểu dữ liệu | `->change()` |

### Bước 1: Tạo migration trong Module

```bash
php artisan make:migration add_computed_score_to_leads \
  --path=Modules/Lead/database/migrations
```

### Bước 2: Viết migration

```php
public function up(): void
{
    Schema::table('leads', function (Blueprint $table) {
        $table->decimal('computed_score', 5, 2)->nullable()->after('lead_score');
    });

    // Data backfill
    DB::statement('UPDATE leads SET computed_score = lead_score * 1.0 WHERE lead_score IS NOT NULL');
}
```

### Bước 3: Chạy migrate và sync JSON

```bash
php artisan migrate

# Sync để giữ JSON đồng bộ
php artisan migration:sync
```

### Bước 4: Review output của sync

```
=== EXTENSIONS MỚI → render_extension_file.json ===
  + leads (1 cols)
```

Kiểm tra entry mới trong `render_extension_file.json` có đúng không trước khi commit.

### Bước 5: Commit cả hai

```bash
git add Modules/Lead/database/migrations/
git add render_extension_file.json
git commit -m "add computed_score to leads with backfill"
```

---

## Tra cứu nhanh — Format cột

```
"tên_cột  ///  kiểu  ///  độ_dài  ///  null  ///  default  ///  modifier  ///  comment"
```

### Kiểu dữ liệu phổ biến

| PHP/JSON | Ý nghĩa |
|----------|---------|
| `string` | VARCHAR, cần length (vd: `255`) |
| `text` | TEXT, length để `__` |
| `boolean` | TINYINT(1), default `true`/`false` |
| `unsignedBigInteger` | BIGINT UNSIGNED, dùng cho FK |
| `unsignedInteger` | INT UNSIGNED |
| `unsignedTinyInteger` | TINYINT UNSIGNED (0–255) |
| `decimal` | DECIMAL, length dạng `10,2` |
| `enum` | ENUM, length dạng `[val1,val2,val3]` |
| `timestamp` | TIMESTAMP |
| `date` | DATE |
| `json` | JSON |

### Nullability và default

| Muốn gì | `null` field | `default` field |
|---------|------------|----------------|
| NOT NULL, không có default | `NOT_NULL` | `__` |
| NOT NULL, có default | `NOT_NULL` | giá trị (vd: `'active'`, `0`, `true`) |
| Nullable, không có default | `_NULL` | `NULL` |
| Nullable, có default | `_NULL` | giá trị |

### Modifier phổ biến

```
__                                              ← không có modifier
->index()                                       ← thêm index đơn
->unique()                                      ← thêm unique constraint
->constrained('table')->onDelete('cascade')     ← FK với cascade
->constrained('table')->onDelete('set null')    ← FK nullable
->constrained('table')->onDelete('restrict')    ← FK restrict (mặc định)
->constrained()->onDelete('cascade')            ← FK, tên bảng tự suy từ tên cột
```

### Ví dụ thực tế

```json
"title///string///255///NOT_NULL///__///__///Tiêu đề"
"slug///string///160///NOT_NULL///__///->unique()///Slug URL"
"status///enum///[draft,active,closed]///NOT_NULL///'draft'///->index()///Trạng thái"
"score///decimal///5,2///_NULL///NULL///__///Điểm số"
"is_active///boolean///__///NOT_NULL///true///->index()///Đang hoạt động"
"org_id///unsignedBigInteger///__///NOT_NULL///__///->constrained('organizations')->onDelete('cascade')///FK org"
"parent_id///unsignedBigInteger///__///_NULL///NULL///->constrained('categories')->onDelete('set null')///FK cha"
"data///json///__///_NULL///NULL///__///Metadata dạng JSON"
```

### Index có tên (bắt buộc khi > 2 cột hoặc tên auto quá dài)

```json
"__index///unique///__///__///__///org_id,code|uq_org_code///__"
"__index///index///__///__///__///org_id,status,created_at|idx_org_status///__"
"__index///index///__///__///__///assigned_to,due_date|idx_assigned_due///__"
```

---

## Kiểm tra drift (trước khi push/PR)

```bash
php artisan migration:sync --dry-run
```

**Kết quả mong đợi:**
```
Scanning 133 migration files..
Nothing new to add.
```

**Nếu có drift:**
```
=== TABLES MỚI → render_migration_file.json ===
  + some_table (5 rows)
```
→ Chạy `php artisan migration:sync` rồi review và commit JSON.

---

## Reset DB hoàn toàn (DEV)

```bash
php artisan migration:generate --fresh
```

Lệnh này tự động:
1. Generate lại toàn bộ file từ 2 JSON
2. Xóa DB và tạo lại từ đầu
3. Chạy tất cả vendor + generated + extensions migrations

> **Lưu ý**: Lệnh này xóa toàn bộ dữ liệu. Chỉ dùng ở local/staging.

---

## Cheat sheet — Tóm tắt 1 trang

```
Thay đổi schema?
├── Bảng mới                → sửa render_migration_file.json  → migration:generate [--fresh]
├── Thêm cột/index          → sửa render_extension_file.json  → extension:generate → migrate
├── Ngoại lệ phức tạp       → tạo Module migration            → migrate → migration:sync → commit JSON
└── Kiểm tra drift          → migration:sync --dry-run

Không bao giờ sửa thẳng vào:  generated/  extensions/
Luôn commit JSON cùng migration file.
```
