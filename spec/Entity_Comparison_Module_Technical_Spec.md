# Đặc tả Kỹ thuật Module: Entity Comparison Module

**Tên module:** Entity Comparison Module (`Modules/EntityComparison`)
**Tên tiếng Việt:** Module So sánh đối tượng theo tiêu chí
**Phiên bản:** 2.0 (đối chiếu lại với codebase thực tế — bản 1.0 viết theo boilerplate Laravel chung, chưa khớp convention của repo này)
**Ngày cập nhật:** 13/08/2026
**Framework:** Laravel 13.7+ / PHP ^8.4 (xác nhận từ `composer.json` gốc — bản 1.0 ghi "Laravel 10/11" là sai)
**Mục đích:** Xây dựng hệ thống so sánh động các đối tượng dựa trên tiêu chí linh hoạt, hỗ trợ filter và so sánh side-by-side.

> Bản 2.0 này đối chiếu bản 1.0 (boilerplate generic) với các module đã tồn tại trong repo —
> chủ yếu `Modules/Ocop` (module nội dung platform-wide gần nhất về hình dạng: category → product
> có is_active/sort_order/status), `Modules/Survey` (hệ thống field/answer động — tiền lệ gần nhất
> cho "Criteria/CriterionValue"), `Modules/RealEstate` (filter theo khoảng giá trị — tiền lệ cho
> trang lọc), `Modules/Post` (khối `PostComparisonBlock` tĩnh trong bài viết — **không** phải cùng
> tính năng, xem §12), và `Modules/Heritage` (mẫu spec §0 "Quyết định đã chốt" dùng lại ở đây).

---

## 0. Quyết định đã chốt

| # | Câu hỏi | Quyết định |
|---|---|---|
| 1 | Đường dẫn thư mục module | `Modules/EntityComparison/...` (NWIDART Laravel Modules) — **không** phải `app/Modules/...` như bản 1.0. Không có `Modules/*/composer.json` nào tự khai `require` PHP/Laravel riêng — mọi module kế thừa nguyên version của app gốc, không cần đồng bộ version thủ công. |
| 2 | Model có tenant-scoped (`organization_id`) không? | **Không.** Đây là module nội dung platform-wide phục vụ site công khai (so sánh trường/bệnh viện/khóa học...), cùng nhóm với `Post`/`Ocop`/`Event`/`Heritage` — **không** cùng nhóm với domain CRM/SaaS (`Lead`/`Customer`/`Workflow`, extend `App\Foundation\Models\TenantAwareModel`). Models extend `Illuminate\Database\Eloquent\Model` thuần, dùng `SoftDeletes` + `LogsActivity` (`Spatie\Activitylog\Models\Concerns\LogsActivity`) + cột audit `created_by`/`updated_by` — đúng shape `OcopProduct` (`Modules/Ocop/app/Models/OcopProduct.php`). |
| 3 | Ảnh đại diện Entity/EntityType | Spatie MediaLibrary qua trait `App\Traits\HasTenantMedia` ngay từ đầu (collection `cover`) — **không** đi qua giai đoạn cột phẳng `image`/`image_path` rồi xóa sau, đúng bài học đã ghi nhận ở `spec/Heritage_Technical_Specification.md` §0 mục 6 (rút kinh nghiệm từ `OcopProduct` — xem migration `2026_07_21_050100_drop_image_columns_from_ocop_products_table.php`). `HasTenantMedia` dùng được cho model không tenant-scoped (đã dùng trên `OcopProduct` dù tên trait có chữ "Tenant"). |
| 4 | DTO cho Create/Update | `spatie/laravel-data` (`^4.23`, đã cài) — class extends `Spatie\LaravelData\Data`, constructor-promoted readonly properties, **không** validate trong Data class (validate ở FormRequest/`$request->validate()`, Data chỉ hydrate) — đúng mẫu `OcopProductData` (`Modules/Ocop/app/Features/OcopProductManagement/Data/OcopProductData.php`). Bản 1.0 không đề cập DTO. |
| 5 | Đọc dữ liệu có filter/sort/pagination | Query object (`readonly` constructor, implements `App\Shared\Contracts\QueryInterface`) + Handler riêng (implements `QueryHandlerInterface`, method `handle()`) — controller gọi thẳng Handler, **không có Query Bus**. Đúng mẫu `ListOcopProductsForAdminQuery`/`Handler` và `ListPublicRealEstateListingsQuery`/`Handler`. |
| 6 | Business logic ghi (Create/Update/Delete) | `Lorisleiva\Actions\Concerns\AsAction` — 1 Action/1 use-case, đúng convention chung của CLAUDE.md và mẫu `CreateOcopProductAction`/`UpdateOcopProductAction`/`DeleteOcopProductAction`. |
| 7 | Lưu giá trị tiêu chí theo type (`criterion_values`) | Cột theo *kind* vật lý (`value_string`, `value_number`, `value_number_max`, `value_bool`, `value_date`, `option_id`) trên 1 hàng/`(entity_id, criterion_id)`, cộng bảng con `criterion_value_options` chỉ cho `multi_select`. Đây là bản rút gọn của pattern `FieldType`/`ValueKind` + cột theo kind của `Modules/Survey` (`SurveyAnswer`, `AnswerValueResolver`) — rút gọn vì ở đây mỗi `CriterionType` map 1-1 với 1 cách lưu (không có 2 loại field cùng chia sẻ 1 kind như Text/Textarea bên Survey), nên **không cần** enum `ValueKind` tách riêng như Survey. Xem chi tiết §3.5. |
| 8 | Options của `select`/`multi_select` | Bảng con `criterion_options` (hàng thật, có `id` để `criterion_values.option_id` trỏ tới) — **không** dùng cột JSON `options` như bản 1.0. Đúng mẫu `SurveyFieldOption` (`Modules/Survey/app/Models/SurveyFieldOption.php`): lưu hàng thật giúp validate FK, filter theo option, và tránh phải parse JSON mỗi lần render. |
| 9 | Phân quyền | **1 permission thô** `entity_comparison.manage` (không tách 4 permission `manage-types`/`manage-criteria`/`manage-entities`/`view` như bản 1.0) — gán cho role `platform_content_editor`/`platform_content_head`/`platform_ops` qua `EntityComparisonPermissionSeeder` dùng `Spatie\Permission\Models\{Permission,Role}` trực tiếp, **không** qua `App\Enums\RoleEnum`/`config/permissions.php` (hệ thống đó chỉ phục vụ 8 role core CRM/SaaS — xem CLAUDE.md). Đúng mẫu `OcopPermissionSeeder`. Trang lọc + so sánh phía người dùng công khai, **không** cần permission (giống `ocop.public.index` không có middleware `auth`). |
| 10 | `spatie/laravel-translatable` cho đa ngôn ngữ (mục 9 bản 1.0) | **Bỏ** — package này **không có trong `composer.json`**, không dùng ở đâu trong repo. Nếu cần đa ngôn ngữ ở phase sau, đi theo pattern thật của repo: bảng dịch riêng theo locale kiểu `PostArticleTranslation` (con trỏ `translation_id`), không phải cột JSON translatable. Ngoài phạm vi v1.0 — chưa có nhu cầu nội dung cụ thể (site hiện tại chỉ tiếng Việt). |
| 11 | Trang chi tiết SEO riêng cho từng Entity | **Ngoài phạm vi v1.0.** Các route public dùng slug + suffix marker để tránh đụng nhau (Post `-d`, Event `-sk`, Ocop `-op` — xem comment trong `Modules/Ocop/routes/web.php`); thêm 1 marker mới cho Entity kéo theo rủi ro đụng route ở trang chi tiết mà **chưa có nhu cầu nội dung thật** (chưa có domain cụ thể nào cần trang chi tiết riêng cho 1 "trường"/"bệnh viện" ngoài bảng so sánh) — đúng nguyên tắc đã áp dụng nhiều lần trong dự án (xem `ArticleStructuredDataBuilder.php`, Heritage spec §0 mục 8). V1.0 chỉ có: trang lọc/danh sách + trang so sánh side-by-side + API. Xem thêm vào roadmap §14. |
| 12 | Quan hệ với `PostComparisonBlock` (Modules/Post) | **Không liên quan, không dùng chung code/bảng.** `PostComparisonBlock` là khối nội dung tĩnh nhập tay trong 1 bài viết/1 locale (`translation_id`), không có Entity/Criterion thật đứng sau. Entity Comparison Module là hệ catalog động, tái dùng được nhiều nơi. Xem §12 để tránh nhầm lẫn khi triển khai. |
| 13 | Tìm kiếm (Meilisearch/Scout) | **Ngoài phạm vi v1.0.** Filter theo tiêu chí dùng Eloquent `->when()` với khoảng giá trị (`value_number >= / <=`) — đúng lý do `ListPublicRealEstateListingsQuery` đã chọn Eloquent thuần thay vì Meilisearch cho filter số/khoảng giá trị có cấu trúc (full-text không phù hợp cho range filter). Có thể bổ sung Scout cho tìm theo tên Entity ở phase sau nếu cần. |

---

## 1. Tổng quan

Module cho phép quản trị viên định nghĩa các **loại đối tượng** (Entity Type), các **đối tượng**
cụ thể (Entity), và các **tiêu chí so sánh** (Criteria) mang tính động. Người dùng cuối có thể lọc
và so sánh nhiều đối tượng với nhau theo dạng bảng ngang (side-by-side comparison).

**Ví dụ điển hình:** So sánh các trường học
- Entity Type: School
- Criteria: Telephone, Starting Age, School Hours, Grades, Tuition Fee, Location...
- Mỗi trường có giá trị tương ứng cho từng tiêu chí.

Module được thiết kế tổng quát, tái sử dụng được cho nhiều domain (trường học, bệnh viện, khóa
học, sản phẩm, nhà xe...), nhưng về mặt hạ tầng nó thuộc cùng nhóm **module nội dung platform-wide**
như `Ocop`/`Heritage`/`Event` — dữ liệu công khai trên site, không gắn với 1 `Organization` cụ thể
(xem §0 mục 2).

---

## 2. Mục tiêu & Phạm vi

### 2.1 Mục tiêu
- Quản lý động Entity Type, Entity và Criteria.
- Gán giá trị tiêu chí linh hoạt theo từng loại dữ liệu (text, number, select, multi_select,
  boolean, range, date).
- Hỗ trợ lọc mạnh theo Entity Type + giá trị tiêu chí.
- Cho phép so sánh từ 2 đến tối đa 5 đối tượng cùng lúc (config được qua `config/entity_comparison.php`).
- Hiển thị bảng so sánh rõ ràng, dễ nhìn, hỗ trợ highlight sự khác biệt.

### 2.2 Phạm vi (In Scope — v1.0)
- CRUD đầy đủ cho Entity Type, Criteria, Entity (admin, qua Action + FormRequest + Policy).
- Gán Criteria vào Entity Type (nhiều-nhiều, có `is_required`/`sort_order` riêng theo type).
- Lưu giá trị tiêu chí cho từng Entity, đúng kiểu dữ liệu (§3.5).
- Trang lọc + chọn đối tượng so sánh (public, không cần đăng nhập).
- Trang so sánh side-by-side (public).
- Phân quyền admin qua 1 permission Spatie (§0 mục 9).

### 2.3 Ngoài phạm vi (Out of Scope — v1.0)
- Tính năng đánh giá / rating của người dùng.
- Lưu lịch sử so sánh của user (Saved Comparisons).
- Export PDF/Excel (module `spatie/laravel-pdf` đã có sẵn trong repo, có thể tái dùng ở phase 2 —
  xem `spec/*_Technical_Specification.md` khác đang dùng nó).
- AI gợi ý đối tượng tương tự.
- Trang chi tiết SEO riêng/route suffix marker riêng cho từng Entity (§0 mục 11).
- Đa ngôn ngữ / `spatie/laravel-translatable` (§0 mục 10).
- Tích hợp Meilisearch/Scout (§0 mục 13).

---

## 3. Kiến trúc dữ liệu

### 3.1 `entity_types`

Lưu các loại đối tượng (School, Hospital, Course...).

```php
Schema::create('entity_types', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->string('name', 150);
    $table->string('slug', 180)->unique();
    $table->text('description')->nullable();
    $table->string('icon', 100)->nullable();
    $table->boolean('is_active')->default(true);
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index('is_active', 'idx_entity_types_active');
});
```

### 3.2 `entities`

Các đối tượng cụ thể (Trường A, Trường B...). Ảnh đại diện qua Media (collection `cover`), **không**
có cột `image` phẳng (§0 mục 3).

```php
Schema::create('entities', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->foreignId('entity_type_id')->constrained('entity_types')->restrictOnDelete();
    $table->string('name', 150);
    $table->string('slug', 180)->unique();
    $table->text('description')->nullable();
    $table->boolean('is_active')->default(true);
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->json('meta')->nullable(); // escape hatch cho field vặt không cần filter/compare
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['entity_type_id', 'is_active'], 'idx_entities_type_active');
});
```

`restrictOnDelete()` trên `entity_type_id`: soft-delete 1 `EntityType` không được chặn bởi FK (soft
delete không xóa hàng thật), business rule "không cho tạo Entity mới thuộc type đã xóa mềm" xử lý ở
Action (§9), không phải ở DB constraint — đúng cách `OcopProduct`/`category_id` xử lý.

### 3.3 `criteria`

Danh sách tiêu chí so sánh động.

```php
Schema::create('criteria', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->string('name', 150);
    $table->string('slug', 180)->unique();
    $table->string('type', 20); // CriterionType enum: text|number|select|multi_select|boolean|range|date
    $table->string('unit', 50)->nullable(); // đơn vị hiển thị: tuổi, giờ, VNĐ...
    $table->text('description')->nullable();
    $table->boolean('is_filterable')->default(true);
    $table->boolean('is_comparable')->default(true);
    $table->boolean('is_required')->default(false);
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();
});
```

### 3.4 `criterion_options`

Options cho `type = select|multi_select` — **hàng thật**, không phải JSON (§0 mục 8).

```php
Schema::create('criterion_options', function (Blueprint $table) {
    $table->id();
    $table->foreignId('criterion_id')->constrained('criteria')->cascadeOnDelete();
    $table->string('value', 100); // machine value, dùng trong filter query string
    $table->string('label', 150); // hiển thị
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->timestamps();

    $table->unique(['criterion_id', 'value'], 'uq_criterion_options_value');
});
```

### 3.5 `criterion_values` + `criterion_value_options`

Giá trị thực tế của tiêu chí theo từng Entity. 1 cột vật lý riêng theo *kind* thay vì 1 cột `value`
đa năng — cho phép filter/sort trực tiếp bằng SQL (`value_number >= ?`) mà không cần cast JSON mỗi
query. Rút gọn từ pattern `FieldType`/`ValueKind` của `Modules/Survey` (xem §0 mục 7).

```php
Schema::create('criterion_values', function (Blueprint $table) {
    $table->id();
    $table->foreignId('entity_id')->constrained('entities')->cascadeOnDelete();
    $table->foreignId('criterion_id')->constrained('criteria')->cascadeOnDelete();
    $table->string('value_string', 255)->nullable();      // text
    $table->decimal('value_number', 18, 4)->nullable();    // number, range (cận dưới)
    $table->decimal('value_number_max', 18, 4)->nullable(); // range (cận trên) — null với các type khác
    $table->boolean('value_bool')->nullable();              // boolean
    $table->date('value_date')->nullable();                 // date
    $table->foreignId('option_id')->nullable()               // select (đơn)
        ->constrained('criterion_options')->nullOnDelete();
    $table->json('value_json')->nullable();                  // escape hatch — KHÔNG dùng bởi type
                                                               // nào ở v1.0, dự phòng cho type phức
                                                               // tạp thêm sau (§3.5.2) để không phải
                                                               // migrate lại toàn bộ bảng lần nữa.
    $table->timestamps();

    $table->unique(['entity_id', 'criterion_id'], 'uq_criterion_values_entity_criterion');
    $table->index('criterion_id', 'idx_criterion_values_criterion');
    // Chưa thêm index (criterion_id, value_number)/(criterion_id, value_number_max) ở v1.0 — cân
    // nhắc thêm nếu sau này filter theo range (§3.5.1) chạy nhiều/chậm trên tập Entity lớn; index
    // riêng cho 1-2 criterion cụ thể hay dùng để filter thay vì toàn bộ bảng nếu cần (§11.1).
});

// multi_select: nhiều option cho cùng 1 (entity, criterion) — 1 header row ở criterion_values
// (mọi cột value_* NULL) + N hàng con ở đây.
Schema::create('criterion_value_options', function (Blueprint $table) {
    $table->id();
    $table->foreignId('criterion_value_id')->constrained('criterion_values')->cascadeOnDelete();
    $table->foreignId('option_id')->constrained('criterion_options')->cascadeOnDelete();
    $table->timestamps();

    $table->unique(['criterion_value_id', 'option_id'], 'uq_criterion_value_options');
});
```

**Bảng map `CriterionType` → cột lưu trữ:**

| `CriterionType` | Cột dùng | Ghi chú |
|---|---|---|
| `text` | `value_string` | |
| `number` | `value_number` | |
| `boolean` | `value_bool` | |
| `date` | `value_date` | |
| `select` | `option_id` | FK trực tiếp tới `criterion_options` |
| `multi_select` | (không dùng cột `value_*` nào) | Header row rỗng ở `criterion_values` + N hàng ở `criterion_value_options` — xem §3.5.1 |
| `range` | `value_number` (min) + `value_number_max` (max) | Xem ví dụ filter ở §3.5.1 |
| *(dự phòng)* | `value_json` | **Không dùng bởi type nào ở v1.0** — escape hatch nếu phase sau cần 1 type phức tạp mới (vd. tọa độ, khoảng ngày kép) mà không map được vào các cột hiện có. Thêm type mới thì cân nhắc dùng cột này trước khi phải ALTER TABLE lần nữa. |

#### 3.5.1 Ghi chú triển khai `CriterionValueResolver` cho `multi_select` và `range`

Đây là 2 type dễ bị hiểu sai nhất vì không map 1-1 vào 1 cột đơn — ghi rõ ở đây để dev không phải
đoán khi cài `CriterionValueResolver` (§4):

- **`multi_select`**: `write()` luôn `firstOrCreate` **1 header row** ở `criterion_values` cho
  `(entity_id, criterion_id)` (mọi cột `value_*`/`option_id` để `NULL` — hàng này chỉ tồn tại để
  làm điểm neo FK cho `criterion_value_options`), sau đó `sync()` danh sách `option_id` vào
  `criterion_value_options.criterion_value_id`. `read()` trả `CriterionValueResult::multiple($options)`
  (§4) — code gọi nơi khác dùng `$result->isMultiple()`/`asOptions()` thay vì tự so sánh
  `$criterion->type === CriterionType::MultiSelect` rải rác nhiều nơi.
- **`range`**: `write()` nhận input dạng `['min' => x, 'max' => y]`, ghi `min` → `value_number`,
  `max` → `value_number_max`. `format()` trả về chuỗi `"{min}–{max} {unit}"` (vd. `"5.000.000–10.000.000 VNĐ/tháng"`),
  không phải 2 giá trị rời.
- Filter theo `range`/`number` trên trang lọc dùng đúng 2 cột này trực tiếp, không cần parse JSON —
  ví dụ filter "Tuition từ 5–10 triệu" trong `ListEntitiesForPublicQuery`/Handler:

```php
$query->whereHas('criterionValues', function ($q) use ($tuitionCriterionId, $min, $max) {
    $q->where('criterion_id', $tuitionCriterionId)
        ->when($min, fn ($q) => $q->where('value_number', '>=', $min))
        ->when($max, fn ($q) => $q->where('value_number_max', '<=', $max));
});
```

#### 3.5.2 Nguyên tắc khi thêm `CriterionType` mới (phase sau)

Áp dụng khi cần thêm 1 `CriterionType` mới ngoài 7 type của v1.0:

1. **Ưu tiên cột typed có sẵn trước.** Xét xem type mới có map được vào 1 hoặc nhiều cột hiện có
   (`value_string`/`value_number`/`value_number_max`/`value_bool`/`value_date`/`option_id`) theo
   đúng cách `range` đã tái dùng `value_number`+`value_number_max` (§3.5.1) — không tự động thêm cột
   mới hay dùng `value_json` chỉ vì tiện.
2. **`value_json` chỉ dùng khi thật sự không map được** vào cột typed nào (vd. type cần lưu cấu trúc
   lồng nhau như tọa độ `{lat, lng}`, hoặc khoảng ngày kép) — vì mất khả năng filter/sort trực tiếp
   bằng SQL (`WHERE value_number >= ?`) là lý do cả bảng này được thiết kế theo cột typed thay vì 1
   cột `value` đa năng duy nhất ngay từ đầu (§0 mục 7). Dùng `value_json` tràn lan sẽ làm mất chính
   lợi ích đó. Cụ thể: **chỉ được dùng cột này khi đã có quyết định chính thức thêm 1 `CriterionType`
   mới** (ghi rõ trong PR/spec update, đúng tinh thần bảng "Quyết định đã chốt" §0) — không phải cột
   đa năng để lách validate hay tiện tay lưu tạm 1 giá trị chưa rõ thuộc type nào.
3. **`CriterionValueResolver` là nguồn sự thật duy nhất** cho việc đọc/ghi/format mọi type — mọi type
   mới bắt buộc thêm 1 nhánh `match()` tường minh trong cả 3 method (`read()`/`write()`/`format()`),
   kèm test riêng (đặc biệt bắt buộc với type không map 1-1 vào 1 cột scalar, theo đúng tinh thần
   §3.5.1 đã làm với `multi_select`/`range`) — không để code gọi nơi khác tự suy luận cột nào ứng
   với type nào.

### 3.6 `entity_type_criterion` (Pivot)

Gán tiêu chí nào thuộc loại đối tượng nào — giữ nguyên tên bảng theo convention pivot alphabetical
của bản 1.0.

```php
Schema::create('entity_type_criterion', function (Blueprint $table) {
    $table->id();
    $table->foreignId('entity_type_id')->constrained('entity_types')->cascadeOnDelete();
    $table->foreignId('criterion_id')->constrained('criteria')->cascadeOnDelete();
    $table->boolean('is_required')->default(false);
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->timestamps();

    $table->unique(['entity_type_id', 'criterion_id'], 'uq_entity_type_criterion');
});
```

---

## 4. Enum `CriterionType`

```php
// Modules/EntityComparison/app/Enums/CriterionType.php
enum CriterionType: string
{
    case Text        = 'text';
    case Number       = 'number';
    case Select       = 'select';
    case MultiSelect  = 'multi_select';
    case Boolean      = 'boolean';
    case Range        = 'range';
    case Date         = 'date';
}
```

String-backed, đúng convention phổ biến nhất trong repo (`OcopProductStatus`, v.v.) hơn là int-backed
như `Survey\FieldType` — ở đây không cần enum `ValueKind` tách riêng vì mỗi `CriterionType` đã map
1-1 với 1 cách lưu (§3.5), khác Survey nơi nhiều `FieldType` (Text/Textarea) chia sẻ chung 1
`ValueKind`.

Logic đọc/ghi đúng cột theo type nằm ở 1 class hỗ trợ duy nhất, mirror
`Modules/Survey/app/Support/AnswerValueResolver.php`. `read()` **không** trả `mixed` — trả về 1 Value
Object để code gọi nơi khác không phải tự `if ($criterion->type === CriterionType::MultiSelect)` mới
biết cách xử lý kết quả:

```php
// Modules/EntityComparison/app/Support/CriterionValueResult.php
final readonly class CriterionValueResult
{
    private function __construct(
        private mixed $scalar,               // string|float|bool|Carbon|null — null nếu isMultiple()
        private ?Collection $options = null,  // Collection<CriterionOption> — null nếu !isMultiple()
    ) {}

    public static function scalar(mixed $value): self { return new self($value); }
    public static function multiple(Collection $options): self { return new self(null, $options); }

    public function isMultiple(): bool { return $this->options !== null; }
    public function asScalar(): mixed { return $this->scalar; }              // throw nếu isMultiple()
    public function asOptions(): Collection { return $this->options; }        // throw nếu !isMultiple()
    public function isEmpty(): bool { return $this->isMultiple() ? $this->options->isEmpty() : $this->scalar === null; }
}

// Modules/EntityComparison/app/Support/CriterionValueResolver.php
final class CriterionValueResolver
{
    /** Xem §3.5.1 trước khi sửa — multi_select/range không map 1-1 vào 1 cột đơn như các type khác. */
    public function read(CriterionValue $value, Criterion $criterion): CriterionValueResult { /* match($criterion->type) { ... } */ }

    /** range nhận input dạng ['min' => x, 'max' => y] — xem §3.5.1. */
    public function write(CriterionValue $value, Criterion $criterion, mixed $input): void { /* ... */ }

    /** Gọi $result->isMultiple() nội bộ để chọn cách format (badge list vs text đơn) — kèm $criterion->unit. */
    public function format(CriterionValueResult $result, Criterion $criterion): string { /* ... */ }
}
```

---

## 5. Eloquent Models & quan hệ

```php
// Modules/EntityComparison/app/Models/EntityType.php
namespace Modules\EntityComparison\Models;

class EntityType extends Model
{
    use SoftDeletes, LogsActivity, HasTenantMedia;

    protected $table = 'entity_types';
    protected $fillable = ['uuid', 'name', 'slug', 'description', 'icon', 'is_active', 'sort_order', 'created_by', 'updated_by'];
    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->uuid ??= (string) Str::uuid());
    }

    public function getRouteKeyName(): string { return 'slug'; }

    public function entities(): HasMany { return $this->hasMany(Entity::class); }
    public function criteria(): BelongsToMany
    {
        return $this->belongsToMany(Criterion::class, 'entity_type_criterion')
            ->withPivot(['is_required', 'sort_order'])
            ->orderByPivot('sort_order');
    }
}
```

```php
// Modules/EntityComparison/app/Models/Entity.php
class Entity extends Model implements HasMedia
{
    use SoftDeletes, LogsActivity, HasTenantMedia;

    protected $table = 'entities';
    protected $fillable = ['uuid', 'entity_type_id', 'name', 'slug', 'description', 'is_active', 'sort_order', 'meta', 'created_by', 'updated_by'];
    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer', 'meta' => 'array'];

    public function entityType(): BelongsTo { return $this->belongsTo(EntityType::class); }
    public function criterionValues(): HasMany { return $this->hasMany(CriterionValue::class); }

    public function scopeOfType(Builder $q, int $entityTypeId): void { $q->where('entity_type_id', $entityTypeId); }
    public function scopeActive(Builder $q): void { $q->where('is_active', true); }
}
```

```php
// Modules/EntityComparison/app/Models/Criterion.php
class Criterion extends Model
{
    use SoftDeletes, LogsActivity;

    protected $casts = ['type' => CriterionType::class, 'is_filterable' => 'boolean', 'is_comparable' => 'boolean', 'is_required' => 'boolean'];

    public function options(): HasMany { return $this->hasMany(CriterionOption::class)->orderBy('sort_order'); }
    public function entityTypes(): BelongsToMany { return $this->belongsToMany(EntityType::class, 'entity_type_criterion'); }
    public function values(): HasMany { return $this->hasMany(CriterionValue::class); }
}
```

```php
// Modules/EntityComparison/app/Models/CriterionValue.php
class CriterionValue extends Model
{
    // Không SoftDeletes/LogsActivity — dữ liệu giá trị con, lifecycle theo Entity/Criterion cha
    // (đúng convention PostComparisonRow: khối con của 1 aggregate không tự log riêng).
    public function entity(): BelongsTo { return $this->belongsTo(Entity::class); }
    public function criterion(): BelongsTo { return $this->belongsTo(Criterion::class); }
    public function option(): BelongsTo { return $this->belongsTo(CriterionOption::class); } // select đơn
    public function options(): BelongsToMany                                                  // multi_select
    {
        return $this->belongsToMany(CriterionOption::class, 'criterion_value_options');
    }
}
```

---

## 6. Cấu trúc thư mục (NWIDART Module)

Theo đúng convention feature-based đã dùng ở `Modules/Ocop`/`Modules/CoreIdeaExtractor` — nhóm theo
use-case (`app/Features/<TênFeature>/{Actions,Data,Queries,Http}`), không nhóm theo layer kỹ thuật
phẳng như bản 1.0 (`Http/Controllers`, `Services/` chung chung).

```
Modules/EntityComparison/
├── app/
│   ├── Enums/
│   │   └── CriterionType.php
│   ├── Features/
│   │   ├── EntityTypeManagement/         # admin CRUD EntityType
│   │   │   ├── Actions/     (Create/Update/DeleteEntityTypeAction)
│   │   │   ├── Data/        (EntityTypeData)
│   │   │   └── Http/        (EntityTypeAdminController, Requests/)
│   │   ├── CriterionManagement/          # admin CRUD Criterion + options + gán vào EntityType
│   │   │   ├── Actions/     (Create/Update/DeleteCriterionAction, AssignCriterionToEntityTypeAction)
│   │   │   ├── Data/        (CriterionData, CriterionOptionData)
│   │   │   └── Http/
│   │   ├── EntityManagement/             # admin CRUD Entity + nhập giá trị tiêu chí
│   │   │   ├── Actions/     (Create/Update/DeleteEntityAction, SetCriterionValuesAction)
│   │   │   ├── Data/        (EntityData)
│   │   │   ├── Queries/     (ListEntitiesForAdminQuery + Handler)
│   │   │   └── Http/
│   │   ├── PublicFiltering/               # trang lọc + danh sách public
│   │   │   ├── Queries/     (ListEntitiesForPublicQuery + Handler)
│   │   │   └── Http/
│   │   └── Comparison/                    # trang so sánh side-by-side
│   │       ├── Actions/     (BuildComparisonTableAction)
│   │       └── Http/
│   ├── Models/
│   │   ├── EntityType.php
│   │   ├── Entity.php
│   │   ├── Criterion.php
│   │   ├── CriterionOption.php
│   │   ├── CriterionValue.php
│   │   └── EntityTypeCriterion.php        # pivot model nếu cần withPivot phức tạp
│   ├── Policies/
│   │   ├── EntityTypePolicy.php
│   │   ├── CriterionPolicy.php
│   │   └── EntityPolicy.php
│   ├── Providers/
│   │   ├── EntityComparisonServiceProvider.php  # extends Nwidart ModuleServiceProvider, Gate::policy() trong boot()
│   │   └── RouteServiceProvider.php
│   └── Support/
│       └── CriterionValueResolver.php
├── config/
│   └── config.php                         # max_compare_entities (default 5), min_compare_entities (default 2)
├── database/
│   ├── migrations/
│   └── seeders/
│       └── EntityComparisonPermissionSeeder.php
├── resources/views/
│   ├── admin/{entity-types,criteria,entities}/
│   └── public/{index,compare}.blade.php
├── routes/
│   ├── web.php
│   └── api.php
└── tests/Feature/
```

---

## 7. Luồng chức năng (Functional Flows)

### 7.1 Admin Side

1. **Quản lý Entity Type** — CRUD qua `EntityTypeAdminController` + `Create/Update/DeleteEntityTypeAction` (`AsAction`), validate qua FormRequest, hydrate `EntityTypeData`. Soft delete hỗ trợ.
2. **Quản lý Criteria** — tạo tiêu chí với `type` (`CriterionType`); nếu `select`/`multi_select`, quản lý `criterion_options` con qua form động (add/remove row). Đánh dấu `is_filterable`/`is_comparable`.
3. **Gán Criteria vào Entity Type** — `AssignCriterionToEntityTypeAction` ghi vào pivot `entity_type_criterion` (`is_required`, `sort_order` riêng theo type).
4. **Quản lý Entity + Gán giá trị** — tạo Entity thuộc 1 Entity Type; form chỉ hiện các tiêu chí đã gán cho type đó (query qua `EntityType::criteria()`); `SetCriterionValuesAction` ghi vào `criterion_values`/`criterion_value_options` qua `CriterionValueResolver`, validate theo `type` (§9).

### 7.2 Frontend (Public Side)

**Bước 1 — Trang danh sách + Filter:** chọn Entity Type (tab/dropdown) → form filter động dựa trên criteria `is_filterable = true` → `ListEntitiesForPublicQuery`/Handler build `->when()` chain giống `ListPublicRealEstateListingsHandler` (range filter cho `number`/`range`, exact match cho `select`/`boolean`, `LIKE` cho `text`).

**Bước 2 — Chọn đối tượng so sánh:** checkbox chọn 2–5 Entity (giới hạn đọc từ `config('entity_comparison.max_compare_entities')`), giữ state qua query string (`?compare[]=uuid1&compare[]=uuid2`) để share link được — không cần bảng "saved comparison" (ngoài phạm vi v1.0).

**Bước 3 — Trang So sánh:** `BuildComparisonTableAction` nhận list Entity UUID → eager-load `criterionValues.criterion`, `criterionValues.option`, `criterionValues.options` (tránh N+1 — `Model::shouldBeStrict()` chặn lazy-load ngoài production, đúng lưu ý đã ghi trong `OcopProduct::toSearchableArray()`) → chỉ render criteria `is_comparable = true` → highlight khác biệt (so sánh giá trị đã format qua `CriterionValueResolver::format()` giữa các cột).

---

## 8. Routes

Theo đúng convention `Modules/Ocop/routes/{web,api}.php` — không dùng prefix `/api/...` phẳng như
bản 1.0.

| Nhóm | Route | Tên | Ghi chú |
|---|---|---|---|
| Admin (web) | `dashboard/entity-comparison/entity-types` (resource, trừ `show`) | `backend.entity_comparison.entity_types.*` | `middleware(['auth'])` |
| Admin (web) | `dashboard/entity-comparison/criteria` (resource, trừ `show`) | `backend.entity_comparison.criteria.*` | |
| Admin (web) | `dashboard/entity-comparison/entities` (resource, trừ `show`) | `backend.entity_comparison.entities.*` | |
| Admin JSON (datatable) | `backend/api/entity-comparison/entities` | `backend.api.entity_comparison.entities` | Tabulator-style, dùng `ListEntitiesForAdminQuery` |
| Public (web) | `entity-comparison/{entityTypeSlug}` | `entity_comparison.public.index` | Trang lọc + danh sách, không `auth` |
| Public (web) | `entity-comparison/{entityTypeSlug}/compare` | `entity_comparison.public.compare` | Trang so sánh, đọc `compare[]` từ query string |
| Public API | `api/entity-comparison/compare` | `entity_comparison.public.compare-api` | `POST { entity_uuids: [...] }` → JSON cho AJAX highlight/re-render |

`EntityComparisonServiceProvider` extends `Nwidart\Modules\Support\ModuleServiceProvider`, khai
`RouteServiceProvider::class` trong `$providers`, đăng ký Policy qua `Gate::policy()` trong `boot()`
— đúng mẫu `OcopServiceProvider`.

---

## 9. Business Rules & Validation

- Số lượng Entity so sánh cùng lúc: **tối thiểu 2, tối đa 5**, đọc từ `config('entity_comparison.min_compare_entities')`/`max_compare_entities` (không hard-code).
- Validate `criterion_values` theo `type` (thực hiện trong Action, không phải DB constraint — SQLite dev không có CHECK constraint mạnh, đúng lý do `OcopProduct::star_rating` cũng validate ở tầng Action):
  - `text` → string, giới hạn độ dài theo cột `value_string` (255).
  - `number` → numeric, ghi `value_number`.
  - `boolean` → cast `0/1`/`true/false` → `value_bool`.
  - `select` → `value` phải tồn tại trong `criterion_options` của đúng `criterion_id` → ghi `option_id`.
  - `multi_select` → mảng value, mỗi phần tử phải tồn tại trong `criterion_options` → ghi N hàng `criterion_value_options`.
  - `range` → `{min, max}`, `min <= max` → ghi `value_number`/`value_number_max`.
  - `date` → format `Y-m-d` → `value_date`.
- Chỉ hiển thị criteria có `is_comparable = true` ở trang so sánh, `is_filterable = true` ở form lọc.
- Soft delete: `EntityType`, `Criterion`, `Entity` đều hỗ trợ (`SoftDeletes`).
- Khi Entity Type bị xóa mềm → chặn tạo Entity mới thuộc type đó ở **cả 2 lớp** (defense in depth,
  không chỉ 1 chỗ):
  1. `StoreEntityRequest`/`UpdateEntityRequest` (FormRequest): rule `exists:entity_types,id,deleted_at,NULL`
     (hoặc `Rule::exists('entity_types', 'id')->whereNull('deleted_at')`) — chặn sớm, trả lỗi 422 rõ
     ràng cho form.
  2. `CreateEntityAction`: kiểm tra lại `EntityType::query()->whereNull('deleted_at')->findOrFail($id)`
     trước khi ghi — vì Action có thể được gọi từ nơi khác ngoài HTTP request (seeder, console
     command, Action khác) không đi qua FormRequest, nên không được coi validate ở tầng Request là
     đủ.
- Xóa 1 `Criterion` (soft delete) → giữ nguyên `criterion_values` đã có (không cascade xóa dữ liệu
  lịch sử), chỉ ẩn khỏi form nhập mới. **Ở trang so sánh cũ** (đã build từ trước khi criterion bị
  xóa), UI hiện badge `"Tiêu chí đã ngừng sử dụng"` cạnh tên tiêu chí thay vì ẩn hẳn hàng đó — tránh
  bảng so sánh cũ (có thể đã được share link/bookmark — §7.2 bước 2) đột nhiên mất hàng dữ liệu mà
  không có giải thích. `BuildComparisonTableAction` phải query criteria **kèm `withTrashed()`** khi
  build bảng so sánh (khác với form nhập mới/trang filter — nơi luôn loại trừ criterion đã xóa).
  Style badge cố định: DaisyUI `badge badge-ghost badge-sm` (đúng convention "trạng thái mờ/không
  còn active" — `RoleEnum::VIEWER` dùng `badge-ghost` cho vai trò ít nổi bật nhất,
  `app/Enums/RoleEnum.php`), kèm `title`/tooltip giải thích ngắn — dùng đúng 1 class này ở mọi nơi
  hiển thị (trang so sánh, và trang lọc nếu sau này có hiển thị criterion đã xóa), không để mỗi view
  tự chọn màu riêng.

---

## 10. Phân quyền

1 permission duy nhất — đúng mẫu `OcopPermissionSeeder` (`ocop.manage`), tránh 4 permission rời rạc
như bản 1.0 (không có nhu cầu tách quyền theo entity/criteria/type ở v1.0, tách sau nếu thực tế cần):

```php
// Modules/EntityComparison/database/seeders/EntityComparisonPermissionSeeder.php
class EntityComparisonPermissionSeeder extends Seeder
{
    private const PERMISSIONS = ['entity_comparison.manage'];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        foreach (['platform_ops', 'platform_content_head', 'platform_content_editor'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->givePermissionTo('entity_comparison.manage');
        }
        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        $superAdmin?->syncPermissions(Permission::all());
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
```

Policy đơn giản, check cùng 1 permission cho mọi action CRUD — đúng mẫu `OcopProductPolicy`:

```php
class EntityPolicy
{
    public function viewAny(User $user): bool { return $user->can('entity_comparison.manage'); }
    public function create(User $user): bool { return $user->can('entity_comparison.manage'); }
    public function update(User $user, Entity $entity): bool { return $user->can('entity_comparison.manage'); }
    public function delete(User $user, Entity $entity): bool { return $user->can('entity_comparison.manage'); }
}
```

Trang lọc + so sánh public **không** cần permission gì (giống `ocop.public.*`).

---

## 11. Non-functional Requirements

- **Performance:**
  - Eager loading toàn bộ quan hệ khi build bảng so sánh (`criterionValues.criterion`, `.option`, `.options`) — `Model::shouldBeStrict()` (`app/Providers/AppServiceProvider.php`) chặn lazy-load ngoài production nên thiếu `with()`/`loadMissing()` sẽ fail test ngay, không phải chờ phát hiện ở production.
  - Cache danh sách criteria theo entity_type (`Cache::remember`, TTL ngắn — invalidate khi admin sửa gán criteria) nếu đo được cần thiết; **không** thêm cache tầng này ở v1.0 nếu chưa có số liệu cho thấy cần (đúng nguyên tắc "không thêm khi chưa có nhu cầu cụ thể" — CLAUDE.md, Heritage spec §0 mục 8).
  - Index đầy đủ cho các cột filter — đã liệt kê ở §3.

- **UI/UX:**
  - Bảng so sánh responsive, scroll ngang trên mobile (`overflow-x-auto` — Tailwind/DaisyUI, đúng stack frontend hiện có).
  - Highlight giá trị khác nhau giữa các cột (so sánh giá trị đã format qua `CriterionValueResolver`).
  - Loading state rõ ràng khi filter/compare (Alpine.js, đúng stack hiện có — không thêm framework JS mới).

- **Bảo mật:** 1 permission `entity_comparison.manage` (§10), Policy check ở mọi Action/Controller admin. Public routes không auth.

- **Đa ngôn ngữ:** ngoài phạm vi v1.0 (§0 mục 10) — site hiện chỉ tiếng Việt, không thêm hạ tầng dịch khi chưa có nhu cầu nội dung đa ngôn ngữ thật.

### 11.1 Anti-patterns cần tránh

Tổng hợp lại các quyết định "không làm X" đã rải rác ở trên, gom vào 1 chỗ để review code/PR sau
này đối chiếu nhanh:

- **Không** lưu `options` của `select`/`multi_select` bằng cột JSON — dùng bảng con `criterion_options` (§0 mục 8, §3.4).
- **Không** thêm cột ảnh phẳng (`image`/`image_path`/...) rồi mới chuyển qua Media Library sau — dùng Media Library (`HasTenantMedia`) ngay từ đầu (§0 mục 3).
- **Không** tạo trang chi tiết SEO riêng cho Entity ở v1.0 khi chưa có nhu cầu nội dung thật và chưa chọn route suffix marker mới (§0 mục 11, §14).
- **Không** tách nhiều permission nhỏ (`manage-types`/`manage-criteria`/`manage-entities`/`view`...) — 1 permission thô `entity_comparison.manage` là đủ ở v1.0, tách sau nếu thực tế cần (§10).
- **Không** dùng `spatie/laravel-translatable` — package không có trong repo; nếu cần đa ngôn ngữ, theo pattern bảng dịch riêng kiểu `PostArticleTranslation` (§0 mục 10).
- **Không** nhầm lẫn hoặc cố hợp nhất với `PostComparisonBlock` của `Modules/Post` — 2 hệ thống tách biệt hoàn toàn, không dùng chung bảng/model (§12).
- **Không** validate rule "EntityType đã xóa mềm thì không tạo Entity mới" chỉ ở 1 lớp — bắt buộc cả FormRequest **và** Action, vì Action có thể được gọi ngoài HTTP request (§9).
- **Không** cascade xóa `criterion_values` khi soft-delete 1 `Criterion` — giữ dữ liệu lịch sử cho bảng so sánh cũ, chỉ ẩn khỏi form nhập mới (§9).
- **Không** thêm cache (criteria list, bảng so sánh theo hash UUID...) khi chưa đo được bottleneck thật — đo trước, cache sau (§11, §14).
- **Không** tích hợp Meilisearch/Scout cho filter số/khoảng giá trị có cấu trúc — dùng Eloquent `->when()` thuần (§0 mục 13).
- **Không** dùng cột `value_json` khi chưa có quyết định chính thức thêm 1 `CriterionType` mới cần nó — không phải cột đa năng để lách validate hay lưu tạm giá trị chưa rõ type (§3.5.2).
- **Không** thêm index `(criterion_id, value_number)`/`(criterion_id, value_number_max)` ngay từ v1.0 khi chưa đo được filter range chậm thật — thêm sau, có chọn lọc theo criterion hay dùng nếu cần (§3.5).
- **Không** để `CriterionValueResolver::read()` trả `mixed`/scalar tùy type — trả `CriterionValueResult` (Value Object) để nơi gọi không phải tự `if ($criterion->type === CriterionType::MultiSelect)` (§4).
- **Không** tự chọn màu badge "Tiêu chí đã ngừng sử dụng" khác nhau ở mỗi view — cố định `badge badge-ghost badge-sm` (§9).

---

## 12. Phân biệt với `PostComparisonBlock` (Modules/Post)

Repo đã có sẵn 1 tính năng tên gần giống — `PostComparisonBlock`/`PostComparisonColumn`/`PostComparisonRow`
(`Modules/Post/app/Models/PostComparisonBlock.php`), thêm cho mục đích GEO ("Comparison fan-out" —
AI answer-engine dễ trích bảng so sánh dạng HTML ngữ nghĩa thuần từ 1 bài viết). **Hai tính năng
không liên quan, không dùng chung bảng/model:**

| | `PostComparisonBlock` (đã có) | Entity Comparison Module (spec này) |
|---|---|---|
| Phạm vi dữ liệu | 1 khối tĩnh trong 1 bài viết/1 locale (`translation_id`) | Catalog động, dùng lại được ở nhiều trang |
| Cách nhập giá trị | Nhập tay từng ô (`PostComparisonRow.values` — JSON string[], khớp vị trí cột) | Qua `Criterion`/`CriterionValue` có type, validate theo type |
| Có filter/lọc được không | Không | Có (`is_filterable`) |
| Có tái sử dụng Entity ở nhiều bài viết không | Không (dữ liệu chỉ tồn tại trong 1 bài) | Có — 1 Entity dùng lại được ở nhiều lần so sánh |
| JSON-LD | Cố ý không có (schema.org không có type phù hợp cho bảng tùy ý) | Ngoài phạm vi v1.0, có thể xét lại nếu cần sau |

Khi triển khai, **không** cố gắng hợp nhất 2 hệ thống hay tái dùng model của bên kia — giữ tách biệt.

---

## 13. Use Case ví dụ: So sánh Trường học

1. Admin tạo Entity Type = "School" (`slug: school`).
2. Tạo các Criteria: Starting Age (`number`, unit "tuổi"), School Hours (`text`), Grades (`select`, options: Preschool/Primary/Secondary), Tuition (`range`, unit "VNĐ/tháng"), Telephone (`text`)...
3. Gán các criteria trên vào Entity Type "School" qua `entity_type_criterion`.
4. Tạo các Entity: "ABC Kindergarten", "XYZ Primary School"... thuộc type "School".
5. Nhập giá trị tương ứng cho từng trường qua `SetCriterionValuesAction`.
6. User vào `/entity-comparison/school` → filter theo Grades = Primary, Tuition trong khoảng...
7. Tick chọn 3 trường → bấm "So sánh ngay" → `/entity-comparison/school/compare?compare[]=...` → xem bảng side-by-side.

---

## 14. Roadmap phát triển tiếp theo

**Phase 2:**
- Export bảng so sánh ra PDF/Excel (tái dùng `spatie/laravel-pdf` đã có trong repo).
- Lưu bộ so sánh của user (Saved Comparisons) — cần bảng mới, có thể tenant-scoped nếu gắn với user đăng nhập.
- Group criteria theo nhóm (Academic, Facilities, Fees...) — thêm bảng `criterion_groups` + cột `group_id` trên `criteria`.
- Trang chi tiết SEO riêng cho từng Entity (§0 mục 11) — cần chọn route suffix marker mới, phối hợp với danh sách marker hiện có (`-d` Post, `-sk` Event, `-op` Ocop).
- Rating/Review gắn với Entity.
- Đa ngôn ngữ theo pattern `PostArticleTranslation` nếu có nhu cầu nội dung đa ngôn ngữ thật.
- Cache kết quả bảng so sánh (`BuildComparisonTableAction`) theo hash của danh sách `entity_uuids`
  đã sort (`Cache::remember("entity_comparison:{$hash}", ttl: ..., ...)`), TTL ngắn (vài phút) — chỉ
  làm khi đo được bottleneck thật ở trang so sánh (5 Entity × nhiều tiêu chí), không làm trước (§11.1).

**Phase 3:**
- Gợi ý Entity tương tự dựa trên tiêu chí.
- Advanced filter (AND/OR logic phức tạp).
- Public share link bảng so sánh (đã có phần nào qua query string ở v1.0 — §7.2 bước 2 — phase 3 có thể rút gọn URL qua bảng lưu preset).
- Tích hợp Meilisearch nếu cần tìm theo tên Entity thay vì chỉ filter theo tiêu chí có cấu trúc.

---

## 15. Kết luận

Module **Entity Comparison Module** được thiết kế linh hoạt, dễ mở rộng, và bám sát convention hiện
có của repo (NWIDART module, feature-based folder, Action/Data/Query-Handler, permission thô qua
Spatie, model platform-wide không tenant-scoped, Media Library ngay từ đầu). Đặc tả này (v2.0) thay
thế bản 1.0 (boilerplate generic, chưa đối chiếu codebase) làm cơ sở triển khai development.

---

**Người soạn:** Team (đối chiếu bản 1.0 với codebase thực tế qua research trực tiếp trên `Modules/Ocop`, `Modules/Survey`, `Modules/RealEstate`, `Modules/Post`, `Modules/Heritage`)
**Ngày:** 13/08/2026
