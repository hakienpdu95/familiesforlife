# Module Chuyên đề Tỉnh (Province Showcase) + Catalog Sản phẩm OCOP

> Phát sinh từ đề xuất "xây dựng cộng đồng CTV/KOL-KOC quảng bá di sản Huế" + "định vị
> thương hiệu địa phương Cà Mau" (câu chuyện Cà Mau, tôm/cua/muối Cà Mau, Mũi Cà Mau...).
> Sau thảo luận, v1 KHÔNG xây toàn bộ các ý tưởng gốc cùng lúc — chỉ xây **nền tảng nội
> dung chuyên đề theo tỉnh** (di sản – văn hóa – ẩm thực – OCOP – sự kiện), dùng **Huế** và
> **Cà Mau** làm 2 tỉnh demo. Các ý tưởng còn lại (cộng đồng CTV/KOL, gợi ý hành trình AI,
> đo lường tác động truyền thông→du lịch) được ghi nhận ở §9 làm roadmap, không nằm trong
> phạm vi spec này.

## 0. Quyết định đã chốt

| # | Câu hỏi | Quyết định |
|---|---|---|
| 1 | Tỉnh có phải 1 tenant/Organization riêng không? | **Không** — dùng chung 1 Organization hiện tại. `Province` chỉ là 1 chiều taxonomy để lọc nội dung công khai, không phát sinh RBAC/permission theo tỉnh. |
| 2 | Có trang landing riêng theo tỉnh không? | **Có** — mỗi tỉnh có 1 trang tổng quan `/tinh/{slug}` làm "mặt tiền" thương hiệu, bên dưới mới rẽ nhánh qua trang con theo chủ đề. |
| 3 | Kiến trúc trang landing | **1 template chung** (Hero + Di sản nổi bật + Chuỗi ẩm thực + Catalog OCOP + Sự kiện), tỉnh nào cần tùy biến phong cách riêng (font/màu/layout theo văn hóa vùng miền) thì **chỉ định thủ công + code view riêng** — không xây UI admin cấu hình theme kéo-thả. |
| 4 | `province_code`/`ward_code` trên `post_articles` | Thêm cả 2 cột, **nullable**, không bắt buộc validate ở Action layer — không phá luồng viết bài hiện tại khi tác giả chưa chọn tỉnh. |
| 5 | Dữ liệu hành chính (tỉnh/phường-xã) | **Tái dùng nguyên trạng** `provinces`/`wards` đã có sẵn trong app (dùng cho Customer/Lead/Event) — không tạo bảng mới. Chỉ bổ sung cột `slug` vào `provinces` để làm route công khai. |
| 6 | Sản phẩm OCOP | Module mới hoàn toàn (`Modules/Ocop`) — dữ liệu có cấu trúc (hạng sao, nhà sản xuất, ngành hàng) khác hẳn 1 bài viết, không nhét vào Post dưới dạng tag. |
| 7 | Banner theo tỉnh | Mở rộng `BannerTargetType` (đã xây ở Banner module) thêm case `Province`, tái dùng nguyên cơ chế targeting đã có thay vì xây lại. |
| 8 | Kiến trúc code | AVSA + CQRS-lite + Laravel Modules + Laravel Actions — cùng pattern đã áp dụng cho Banner. View admin `@extends('layouts.backend')`, có sidebar riêng cho OCOP. |

## 1. Giới thiệu & Mục tiêu

Xây dựng hạ tầng để familiesforlife có thể **chuyên đề hóa nội dung theo tỉnh/thành**,
phục vụ các chiến dịch định vị thương hiệu địa phương (di sản – văn hóa – ẩm thực – sản
phẩm OCOP – sự kiện) — lấy cảm hứng từ 2 case thực tế: Festival Huế (di sản, ẩm thực,
Hue Culinary) và Cà Mau (câu chuyện vùng đất, tôm/cua/muối Cà Mau, danh thắng Mũi Cà Mau).

Mục tiêu v1: có 1 trang `/tinh/hue` và `/tinh/ca-mau` chạy thật, tổng hợp nội dung đã có
(Post/Event) + nội dung mới (OCOP), mỗi trang mang bản sắc riêng của tỉnh đó.

## 2. Khảo sát hiện trạng (những gì đã có, tái dùng được)

| Thành phần | Trạng thái | Ghi chú |
|---|---|---|
| `App\Models\Province` / `Ward` | Có sẵn, bảng `provinces`/`wards` (cấu trúc hành chính 2 cấp mới: Tỉnh/Thành → Phường/Xã) | Đang dùng cho `Customer`, `Lead`, `Event`. Bảng `provinces` **hiện trống dữ liệu** — cần chạy `php artisan import:provinces-wards` từ `datafiles/provinces.json` (đã xác nhận có "Thành phố Huế" và "Cà Mau" trong file). |
| `<x-address-picker>` | Có sẵn | Component chọn tỉnh/phường-xã, tái dùng được cho form Ocop/Event, không cần cho Post (xem §4.2). |
| `Modules\Event\Models\Event` | Có sẵn `province_code`+`province_name`+`ward_code`+`ward_name` (denormalized, không FK cứng) | Đây chính là convention cần copy nguyên cho `post_articles` — không phải thiết kế mới. |
| `Modules\Post\Models\PostArticle` | Bài viết đã tách locale (`PostArticleTranslation`), bản thân `post_articles` **không có** trường tỉnh | Cần bổ sung. |
| `Modules\Post\Models\PostCategory` | Đã có category gốc `du-lich-gia-dinh` (Du lịch gia đình) | Sẽ thêm 2 category con: **Di sản văn hóa** (`di-san-van-hoa`), **Ẩm thực vùng miền** (`am-thuc-vung-mien`) — nội dung chuyên đề tỉnh vẫn nằm trong đúng định vị "gia đình", không tạo category rời rạc ngoài mục đích site. |
| `Modules\Banner` | Đã có targeting theo category (`BannerTargetType::Category`) | Mở rộng thêm `Province` — xem §3.5/§4.4. |
| OCOP (sản phẩm đặc trưng) | **Chưa có gì** | Module mới — §3.4. |

## 3. Kiến trúc dữ liệu

### 3.1 Bổ sung cột `slug` vào `provinces`

Bảng `provinces` hiện chỉ có `name`/`short_name`/`province_code`, không có slug — cần
thiết để route công khai gọn (`/tinh/hue` thay vì `/tinh/46`). Thực hiện qua migration
trong `Modules/Province` (không sửa trực tiếp migration gốc, vì bảng đã tồn tại ở
production):

```php
// Modules/Province/database/migrations/xxxx_add_slug_to_provinces_table.php
Schema::table('provinces', function (Blueprint $table) {
    $table->string('slug', 255)->nullable()->unique()->after('name');
});
```

Backfill slug cho toàn bộ tỉnh hiện có (kể cả tỉnh chưa "chuyên đề hóa") bằng
`Str::slug($province->name)`, chạy 1 lần trong `ProvinceDatabaseSeeder` hoặc 1 command
riêng — **không** override `getRouteKeyName()` toàn cục trên `App\Models\Province` (model
này đang được dùng ở nhiều nơi bind theo `id`/`province_code`), route công khai tự resolve
theo `slug` thủ công trong controller (xem §7.1).

### 3.2 Thêm cột tỉnh/phường-xã vào `post_articles`

Copy nguyên convention đã dùng ở `events` (không FK cứng, denormalize tên tại thời điểm
chọn — tránh join `provinces`/`wards` mỗi lần render):

```php
// Modules/Post/database/migrations/xxxx_add_province_ward_to_post_articles_table.php
Schema::table('post_articles', function (Blueprint $table) {
    $table->char('province_code', 2)->nullable()->after('cover_image_url')
        ->comment('Mã tỉnh/thành — không FK cứng, cùng pattern events.province_code');
    $table->string('province_name', 255)->nullable()->comment('Tên tỉnh denormalized');
    $table->char('ward_code', 5)->nullable()->comment('Mã phường/xã — tuỳ chọn, chỉ điền khi bài gắn 1 địa điểm cụ thể');
    $table->string('ward_name', 255)->nullable();

    // Chỉ index province_code — post_articles KHÔNG còn cột published_at (đã chuyển sang
    // post_article_translations từ migration 2026_07_11_000003, xem PostArticle model hiện
    // tại không có published_at trong $fillable/$casts). Lọc "đã publish" luôn phải join qua
    // PostArticleTranslation::published() — xem query thật ở §3.2.1 ngay dưới.
    $table->index('province_code', 'idx_post_article_province');
});
```

Đặt ở cấp `post_articles` (không phải `post_article_translations`) — 1 địa điểm không đổi
theo ngôn ngữ.

#### 3.2.1 Denormalize tên tỉnh — Action layer

Copy đúng nguyên tắc đã ghi rõ trong `BuildEventAttributesAction` ("LUÔN tra lại tên thật
từ bảng provinces/wards ở tầng Action, không tin tên gửi từ client"):

```php
// Modules/Post/app/Features/ArticleAuthoring/Actions/CreateArticleAction.php (và Update...)
$provinceName = $data->province_code
    ? Province::where('province_code', $data->province_code)->value('name')
    : null;

PostArticle::create([
    ...
    'province_code' => $data->province_code,
    'province_name' => $provinceName,
    'ward_code'     => $data->ward_code,
    'ward_name'     => $data->ward_code
        ? Ward::where('ward_code', $data->ward_code)->value('name')
        : null,
]);
```

`ArticleData` thêm 2 field: `public readonly ?string $province_code = null` và
`public readonly ?string $ward_code = null` — hydrate thuần qua `Data::from()`, validate
(nếu có) vẫn nằm ở `ArticleAdminController::validated()` cùng nguyên tắc hiện tại của DTO
này (xem docblock `ArticleData`).

#### 3.2.2 Query lọc theo tỉnh — dùng ở đâu, `where` hay `whereIn`

- **Trang chuyên đề tỉnh** (`<x-province.section-heritage>`/`section-cuisine>`, §7.2): luôn
  lọc đúng **1 tỉnh** (trang đang đứng) → dùng `where('province_code', $province->province_code)`,
  không phải `whereIn` — không có nhu cầu xem nhiều tỉnh cùng lúc trên 1 trang landing.
- **Trang danh mục/danh sách Post hiện có** (`PublicCategoryController`,
  `ListPublishedArticlesQuery`): **không** thêm filter theo tỉnh ở v1 — out of scope, vì
  trang danh mục hiện tại phục vụ mục đích khác (duyệt theo category, không phải theo địa
  lý). Nếu sau này cần "lọc bài viết theo tỉnh" ở trang danh mục chung, mới thêm
  `?province=` query param → `whereIn('province_code', $codes)` khi cho phép chọn nhiều
  tỉnh; nhưng đó là mở rộng riêng, không thuộc phạm vi spec này.
- Query thực tế cho section (viết trong Blade component class, xem §7.2):
  ```php
  PostArticleTranslation::published()
      ->where('locale', config('post.default_locale'))
      ->whereHas('article', fn ($q) => $q->where('province_code', $province->province_code)
          ->whereHas('categories', fn ($c) => $c->where('slug', 'di-san-van-hoa')))
      ->with('article')
      ->orderByDesc('published_at')
      ->limit(config('province.section_limit'))
      ->get();
  ```

### 3.3 Category mới cho Post (seeder, không phải schema)

Thêm 2 `PostCategory` con của `du-lich-gia-dinh`:

- `di-san-van-hoa` — "Di sản văn hóa"
- `am-thuc-vung-mien` — "Ẩm thực vùng miền"

### 3.4 Module OCOP mới — ERD

```
ocop_categories                      ocop_products
─────────────────                    ──────────────────────────
id (PK)                              id (PK)
uuid                                 uuid
name                                 category_id (FK → ocop_categories)
slug                                 name
icon                                 slug
sort_order                           star_rating (tinyint, 3–5)
is_active                            description
created_by / updated_by              province_code (nullable, denorm)
timestamps, softDeletes              province_name
                                      ward_code (nullable, denorm)
                                      ward_name
                                      producer_name
                                      producer_address
                                      image_path / width / height / size_bytes
                                      purchase_url (nullable — link sàn TMĐT/liên hệ mua)
                                      status (draft|published)
                                      is_featured
                                      sort_order
                                      created_by / updated_by
                                      timestamps, softDeletes
```

Migration `ocop_products`:

```php
Schema::create('ocop_products', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->foreignId('category_id')->constrained('ocop_categories')->restrictOnDelete();
    $table->string('name', 150);
    $table->string('slug', 180)->unique();
    $table->unsignedTinyInteger('star_rating'); // validate 3–5 ở Data/Action, không CHECK constraint (SQLite dev)
    $table->text('description')->nullable();
    $table->char('province_code', 2)->nullable();
    $table->string('province_name', 255)->nullable();
    $table->char('ward_code', 5)->nullable();
    $table->string('ward_name', 255)->nullable();
    $table->string('producer_name', 150)->nullable();
    $table->string('producer_address', 255)->nullable();
    $table->string('image_path', 255)->nullable();
    $table->unsignedInteger('image_width')->nullable();
    $table->unsignedInteger('image_height')->nullable();
    $table->unsignedInteger('image_size_bytes')->nullable();
    $table->string('purchase_url', 500)->nullable();
    $table->string('status', 20)->default('draft'); // OcopProductStatus: draft|published
    $table->boolean('is_featured')->default(false);
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['status', 'province_code'], 'idx_ocop_status_province');
    $table->index(['category_id', 'status'], 'idx_ocop_category_status');
    $table->index(['status', 'is_featured'], 'idx_ocop_status_featured');
});
```

Bảng liên kết Post ↔ OCOP (many-to-many, tuỳ chọn — xem lý do ở §3.4.1):

```php
Schema::create('post_article_ocop_products', function (Blueprint $table) {
    $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
    $table->foreignId('ocop_product_id')->constrained('ocop_products')->cascadeOnDelete();
    $table->primary(['article_id', 'ocop_product_id']);
});
```

#### 3.4.1 Liên kết OCOP ↔ Post — chọn many-to-many có cấu trúc, không phải link thủ công

Quyết định: **many-to-many** (`post_article_ocop_products`), không phải chèn link thủ công
trong nội dung bài viết. Lý do: 1 bài "Ẩm thực Huế" thường nhắc tới nhiều sản phẩm (mè
xửng + tôm chua) và 1 sản phẩm OCOP có thể được nhắc trong nhiều bài (bài tổng quan +
bài chuyên sâu) — quan hệ N-N thực tế, không phải 1-1. Cách dùng:

- Form sửa bài viết thêm 1 multi-select "Sản phẩm OCOP liên quan" (liệt kê
  `OcopProduct::published()`, lọc theo `province_code` của bài nếu bài đã gắn tỉnh, để
  tránh danh sách quá dài).
- Không bắt buộc — bài viết không gắn OCOP nào vẫn publish bình thường.
- Dùng ở 2 chiều hiển thị: (a) cuối bài viết công khai, hiển thị card "Sản phẩm liên
  quan" nếu có; (b) `<x-province.section-ocop>` có thể ưu tiên hiển thị sản phẩm **đã
  được nhắc trong ít nhất 1 bài viết của tỉnh đó** trước, làm fallback sang toàn bộ sản
  phẩm published theo tỉnh nếu chưa đủ số lượng `section_limit` — cùng tinh thần
  targeted-trước/global-sau đã dùng ở Banner (§4.1), không phải rule bắt buộc, chỉ là gợi ý
  sắp xếp, không nằm trong migration/business rule bắt buộc của v1 (ghi ở đây để Dev biết
  hướng mở rộng, chưa cần code ngay Phase 2).

### 3.5 Mở rộng Banner — targeting theo tỉnh

`Modules/Banner/app/Enums/BannerTargetType.php` thêm case:

```php
enum BannerTargetType: string
{
    case Category = 'category';
    case Province = 'province';   // MỚI — target_value = provinces.province_code

    public function label(): string
    {
        return match ($this) {
            self::Category => 'Theo danh mục bài viết',
            self::Province => 'Theo tỉnh/thành',
        };
    }
}
```

`config/banner.php` — thêm `'province' => 'Theo tỉnh/thành'` vào `target_types`, và thêm
1 placement mới `province_top` ("Banner đầu trang chuyên đề tỉnh").

## 4. Model & Config

### 4.1 `Banner::forPlacement()` — tổng quát hóa đa chiều targeting

Logic hiện tại (spec Banner §4.1/§7.5) chỉ biết 1 chiều (`category_slug`). Cần tổng quát
để hỗ trợ thêm `province_code` mà không phải viết lại từ đầu:

```php
private const CONTEXT_DIMENSIONS = [
    'category_slug'  => BannerTargetType::Category,
    'province_code'  => BannerTargetType::Province,
];

public static function forPlacement(string $placement, array $context = [], ?int $limit = null): Collection
{
    $targeted = collect();

    foreach (self::CONTEXT_DIMENSIONS as $contextKey => $targetType) {
        $value = $context[$contextKey] ?? null;
        if (! $value) {
            continue;
        }

        $targeted = $targeted->concat(
            static::active()
                ->where('placement', $placement)
                ->where('target_type', $targetType)
                ->where('target_value', $value)
                ->orderBy('sort_order')
                ->get()
        );
    }

    if ($limit !== null && $targeted->count() >= $limit) {
        return $targeted->take($limit)->values();
    }

    $remainingLimit = $limit !== null ? $limit - $targeted->count() : null;
    $global = static::active()
        ->where('placement', $placement)
        ->whereNull('target_type')
        ->orderBy('sort_order')
        ->when($remainingLimit, fn ($q) => $q->limit($remainingLimit))
        ->get();

    return $targeted->concat($global)->values();
}
```

1 trang chỉ truyền 1 context key thực tế có ý nghĩa (trang category truyền
`category_slug`, trang tỉnh truyền `province_code`) nên không xảy ra xung đột giữa 2
chiều trong thực tế v1.

### 4.2 `OcopProduct` model — điểm cần lưu ý

- **Không** dùng `<x-address-picker>` (chọn tỉnh/phường-xã bằng dropdown) cho form Post
  (vì `province_code` trên Post là tuỳ chọn, gắn lỏng cho mục đích lọc chuyên đề, không
  phải "địa chỉ" thật) — nhưng **CÓ** dùng cho form OCOP, vì 1 sản phẩm OCOP luôn có 1 nhà
  sản xuất ở 1 địa chỉ thật (province+ward bắt buộc chọn khi tạo sản phẩm).
- `star_rating` validate `in:3,4,5` ở `OcopProductData` — chương trình OCOP quốc gia chỉ
  chấm từ 3 sao trở lên mới được công nhận sản phẩm chính thức.
- Ảnh sản phẩm dùng lại đúng pattern `StoreBannerImageAction` (Intervention Image v4,
  `decodePath()->scaleDown()->encode()`, resize nếu > `config('ocop.max_image_width')`) —
  **không** dùng Spatie MediaLibrary. Lý do: codebase hiện tại (Banner, Event poster) đều
  dùng pattern "1 ảnh/bản ghi, lưu path+width+height+size_bytes trực tiếp trên bảng chính",
  không nơi nào cài MediaLibrary — thêm 1 package/pattern ảnh thứ 2 chỉ cho riêng OCOP sẽ
  tạo 2 cách quản lý ảnh song song không cần thiết trong cùng 1 codebase.

### 4.3 `config/province.php` — khai báo tỉnh có chuyên đề + tùy biến theo code

```php
return [
    // Tỉnh nào bật trang chuyên đề — khoá là `provinces.slug`. Thêm tỉnh mới = thêm 1 dòng
    // + (tuỳ chọn) tạo view riêng, KHÔNG cần thao tác DB nào khác ngoài seed nội dung.
    'showcase_provinces' => [
        'hue' => [
            'tagline'      => 'Di sản, văn hóa và ẩm thực Cố đô',
            'accent_color' => '#7a1f2b', // đỏ son cung đình — dùng khi có custom view
        ],
        'ca-mau' => [
            'tagline'      => 'Đất Mũi, rừng ngập mặn và hương vị phương Nam',
            'accent_color' => '#0f6e4f', // xanh rừng đước
        ],
    ],

    // Số item tối đa mỗi khối trên trang landing
    'section_limit' => 6,
];
```

Resolve view: `ProvincePublicController::show()` render
`view('province::public.show', [...])` — Blade tự tìm `resources/views/public/custom/{slug}.blade.php`
trước, nếu không tồn tại thì fallback `resources/views/public/show.blade.php` (template
chung):

```php
$customView = "province::public.custom.{$province->slug}";
return View::exists($customView)
    ? view($customView, $data)
    : view('province::public.show', $data);
```

Tỉnh nào cần phong cách riêng (font/màu/bố cục theo văn hóa vùng miền) → dev tạo file
`resources/views/public/custom/hue.blade.php` thủ công, **kế thừa** các section
component chung (`<x-province.section-heritage>`, `<x-province.section-cuisine>`,
`<x-province.section-ocop>`, `<x-province.section-events>`) chỉ đổi hero/màu/layout bọc
ngoài — đúng tinh thần "chỉ định + code", không phải cấu hình qua admin UI.

**Giới hạn override — cái gì được đổi, cái gì không:**

- **Được đổi tự do**: hero (ảnh/màu/font/bố cục), thứ tự các section, spacing/layout bọc
  ngoài, thêm section trang trí thuần hiển thị (vd 1 khối "trích dẫn văn hóa" tĩnh).
- **Bắt buộc dùng lại nguyên component** `<x-province.section-*>` cho phần dữ liệu động
  (Di sản/Ẩm thực/OCOP/Sự kiện) — **không** được viết lại query bên trong custom view. Lý
  do: logic lọc theo tỉnh + trạng thái publish (§3.2.2/§5) chỉ nên tồn tại ở 1 chỗ; nếu mỗi
  tỉnh tự viết lại query sẽ lệch nhau khi sau này sửa rule lọc (vd thêm điều kiện ẩn bài hết
  hạn) — 1 nơi sửa, N tỉnh cùng đúng.
  - Nếu 1 tỉnh thực sự cần trình bày dữ liệu khác hẳn (vd OCOP hiển thị dạng carousel thay
    vì grid), thêm 1 **prop tuỳ chọn** vào component sẵn có (`<x-province.section-ocop
    :layout="'carousel'" .../>`) thay vì viết component mới — giữ đúng 1 nguồn logic, chỉ
    khác cách trình bày.

**Hiệu năng khi nhiều tỉnh có custom view:**

- Blade tự compile view ra PHP thuần và cache vào `storage/framework/views/` (cơ chế mặc
  định của Laravel, không cần cấu hình thêm) — số lượng file custom view (dự kiến vài chục
  tỉnh tối đa) không ảnh hưởng hiệu năng runtime, chỉ tốn thêm dung lượng compile cache
  không đáng kể.
- Chi phí thật nằm ở **query bên trong mỗi section component** (4 query/trang: Post×2,
  OCOP, Event) — mỗi component nên bọc `Cache::remember("province:{$province->slug}:{$section}", now()->addMinutes(10), fn () => ...)`
  vì nội dung landing page thay đổi không thường xuyên (thêm bài/sản phẩm mới không cần
  hiển thị ngay lập tức trong vòng vài phút). TTL 10 phút đủ để giảm tải DB khi có traffic
  cao mà không làm nội dung "cứng" quá lâu khi biên tập viên vừa publish bài mới.

## 5. Business rules

- Trang `/tinh/{slug}` chỉ tồn tại (200 OK) nếu `slug` có mặt trong
  `config('province.showcase_provinces')` **và** khớp 1 dòng trong bảng `provinces` —
  404 nếu 1 trong 2 điều kiện không thỏa (tránh lộ URL cho tỉnh chưa có nội dung).
- Mỗi section trên trang landing tự ẩn nếu rỗng (không có bài Di sản → không render
  khối đó) — cùng nguyên tắc "ẩn khi rỗng" đã áp dụng cho `<x-frontend.banner-slot>`.
- **Workflow duyệt OCOP — Post-style, không phải Event-style**: `OcopProduct` chỉ có 2
  trạng thái `draft`/`published` (`OcopProductStatus` enum), set trực tiếp bởi người có
  quyền `ocop.manage`, không qua bước "chờ duyệt/approve/reject" như Event. Lý do: Event có
  luồng duyệt nhiều bước vì **độc giả ẩn danh tự nộp** sự kiện (`EventSubmission`, cần
  kiểm duyệt trước khi công khai) — OCOP thì ngược lại, chỉ đội biên tập nội bộ (đã có
  permission `ocop.manage`) mới tạo được sản phẩm ngay từ đầu, không có nguồn nộp public
  nào cần kiểm soát thêm. Do đó áp dụng đúng mức độ đơn giản của Post
  (draft/published), không copy máy móc luồng Event.
- `OcopProduct` chỉ hiển thị công khai khi `status = published`.
- Xoá `OcopCategory` đang có sản phẩm: chặn (`restrictOnDelete` ở DB + confirm rõ ràng ở
  Action, cùng pattern `EventCategory`).

## 6. Admin CRUD

### 6.1 Module OCOP (`Modules/Ocop`) — cấu trúc AVSA

```
Modules/Ocop/app/Features/
├── OcopCategoryManagement/{Actions,Data,Http,Queries}
└── OcopProductManagement/{Actions,Data,Http,Queries}
    Actions: CreateOcopProductAction, UpdateOcopProductAction, DeleteOcopProductAction,
             StoreOcopProductImageAction (copy StoreBannerImageAction)
```

Routes: `dashboard/ocop/categories` (resource, except show) + `dashboard/ocop/products`
(resource, except show) — cùng convention `CategoryAdminController`/`BannerAdminController`.

Permission mới trong `PermissionEnum`: `case OCOP_MANAGE = 'ocop.manage';` — cấp cho
`platform_ops` + `platform_content_head` + `super-admin`, cùng seeder pattern đã dùng cho
`BannerPermissionSeeder` (không qua `config/permissions.php` vì đây là platform role, không
phải Lớp B).

Sidebar: thêm mục "OCOP" (single nav-link) ngay dưới mục Banner, cùng
`@can(\App\Enums\PermissionEnum::OCOP_MANAGE->value)`.

Form admin OCOP dùng `<x-address-picker :province-value="..." :ward-value="...">` để chọn
tỉnh/phường-xã của nhà sản xuất.

### 6.2 Module Province (`Modules/Province`) — KHÔNG có admin CRUD

Vì §0 đã chốt "tỉnh nào chuyên biệt thì chỉ định + code", module này **không có** giao
diện quản trị nào — chỉ có: migration (slug), config (`showcase_provinces`), route công
khai, và các view/component render landing page. Không cần permission mới, không cần
sidebar entry.

### 6.3 Post admin — bổ sung chọn tỉnh (tùy chọn)

Post **không có** `_form.blade.php` dùng chung — `create.blade.php` và `edit.blade.php` là
2 file riêng (khác Banner) — nên thêm field mới vào **cả 2 file**, đặt ngay sau khối "Ảnh
đại diện (URL)", cùng style `form-control`/`label`/`select-bordered select-sm` đang dùng
trong `create.blade.php`:

```blade
<div class="form-control">
    <label class="label py-0 pb-1">
        <span class="label-text text-xs font-medium">Tỉnh/thành liên quan</span>
    </label>
    <select name="province_code" class="select select-bordered select-sm w-full @error('province_code') select-error @enderror">
        <option value="">— Không gắn tỉnh —</option>
        @foreach($provinces as $p)
        <option value="{{ $p->province_code }}" {{ old('province_code', $article->province_code ?? '') === $p->province_code ? 'selected' : '' }}>
            {{ $p->name }}
        </option>
        @endforeach
    </select>
    @error('province_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
</div>
```

`ArticleAdminController::create()`/`edit()` truyền thêm
`'provinces' => Province::orderBy('name')->get(['province_code', 'name'])` vào view — **không**
dùng `<x-address-picker>` đầy đủ (không chọn ward ở bước viết bài — ward chỉ có ý nghĩa cho
1 địa điểm cụ thể, out of scope UI Post v1; nếu cần gắn ward, sửa trực tiếp qua DB/tinker
cho demo, không xây UI riêng). `ArticleData`/`CreateArticleAction`/`UpdateArticleAction`
nhận thêm `province_code`, tự denormalize `province_name` khi lưu (§3.2.1).

## 7. Render công khai

### 7.1 Route

```php
// Modules/Province/routes/web.php
Route::prefix('tinh')->name('province.public.')->group(function () {
    Route::get('/', [ProvincePublicController::class, 'index'])->name('index'); // danh sách tỉnh có chuyên đề
    Route::get('{slug}', [ProvincePublicController::class, 'show'])->name('show');
});
```

`show()` tự resolve `Province::where('slug', $slug)->firstOrFail()` (không dùng implicit
route-model-binding — lý do đã nêu ở §3.1).

### 7.2 Trang landing — cấu trúc section

```
Hero (tagline + accent_color từ config, ảnh cover — field mới trên Province hoặc dùng
      ảnh custom trong view riêng)
  → <x-frontend.banner-slot placement="province_top" :context="['province_code' => $province->province_code]" />
  → <x-province.section-heritage :province="$province" />   (bài viết category=di-san-van-hoa)
  → <x-province.section-cuisine  :province="$province" />   (bài viết category=am-thuc-vung-mien)
  → <x-province.section-ocop     :province="$province" />   (OcopProduct::published(), top N + link "Xem tất cả OCOP {tỉnh}")
  → <x-province.section-events   :province="$province" />   (Event::published()->upcoming(), province_code=...)
```

Mỗi component tự query, tự ẩn nếu rỗng (§5), giới hạn `config('province.section_limit')`
item — không cần Query/Handler CQRS riêng cho mỗi section (đơn giản, đọc trực tiếp trong
Blade component class giống `AddressPicker`), vì đây là hiển thị thuần, không có logic
nghiệp vụ phức tạp.

## 8. Kế hoạch triển khai (phases)

1. **Phase 1 — Nền dữ liệu**: import `provinces`/`wards` (`php artisan import:provinces-wards`),
   migration thêm `slug` vào `provinces`, migration thêm `province_code`/`ward_code` vào
   `post_articles`, seeder 2 category Post mới.
2. **Phase 2 — Module OCOP**: scaffold module, migration, model, admin CRUD, permission,
   sidebar, upload ảnh.
3. **Phase 3 — Module Province**: scaffold module, config, route, controller, template
   chung + 4 section component, trang `/tinh` (index).
4. **Phase 4 — Mở rộng Banner**: thêm `BannerTargetType::Province`, tổng quát hóa
   `forPlacement()`, thêm placement `province_top`.
5. **Phase 5 — Demo dữ liệu**: nội dung cụ thể cho từng tỉnh (danh sách dưới) — mỗi mục là
   1 bản ghi thật cần seed, không phải placeholder "Lorem ipsum", để trang landing demo
   trông đúng như 1 sản phẩm thật khi review.
6. **Phase 6 (tuỳ chọn)** — custom view riêng cho 1 trong 2 tỉnh (ví dụ Huế) để làm mẫu
   tham khảo cho các tỉnh sau.

### 8.1 Nội dung demo — Huế (`province_code` tra theo `datafiles/provinces.json`)

| Loại | Số lượng | Ví dụ tiêu đề |
|---|---|---|
| Bài Di sản (`di-san-van-hoa`) | 3 | Đại Nội Huế — dấu ấn triều Nguyễn; Chùa Thiên Mụ bên dòng sông Hương; Lăng Tự Đức — chốn thơ mộng giữa lòng Huế |
| Bài Ẩm thực (`am-thuc-vung-mien`) | 3 | Bún bò Huế — hồn cốt ẩm thực Cố đô; Cơm hến Vỹ Dạ, món ăn dân dã trứ danh; Bánh khoái Huế chấm nước lèo gan |
| Sản phẩm OCOP | 5 | Mè xửng Huế (4 sao); Tôm chua Huế (3 sao); Trà cung đình Huế (3 sao); Nón lá bài thơ Huế (4 sao); Dầu tràm Huế (3 sao) |
| Sự kiện | 2 | Festival Huế 2026; Lễ hội Áo dài Huế |

### 8.2 Nội dung demo — Cà Mau

| Loại | Số lượng | Ví dụ tiêu đề |
|---|---|---|
| Bài Di sản (`di-san-van-hoa`) | 3 | Mũi Cà Mau — điểm cực Nam Tổ quốc; Vườn quốc gia Mũi Cà Mau và rừng ngập mặn; Khu Ramsar Mũi Cà Mau — lá phổi xanh miền Tây |
| Bài Ẩm thực (`am-thuc-vung-mien`) | 3 | Lẩu mắm Cà Mau đậm vị miền sông nước; Ba khía Rạch Gốc trứ danh; Cá lóc nướng trui kiểu Nam Bộ |
| Sản phẩm OCOP | 5 | Tôm khô Cà Mau (4 sao); Cua Cà Mau (5 sao); Mắm cá đồng Cà Mau (3 sao); Muối Cà Mau (3 sao); Khô cá kèo Cà Mau (3 sao) |
| Sự kiện | 2 | Lễ hội Nghinh Ông Sông Đốc; Ngày hội Cua Cà Mau |

**Tổng cộng**: 6 bài Di sản + 6 bài Ẩm thực + 10 sản phẩm OCOP + 4 sự kiện + 2 banner
`province_top` (1 mỗi tỉnh) = 28 bản ghi demo.

**Tiêu chí "xong"** (Definition of Done cho Phase 5, verify bằng Playwright cùng cách đã
làm với Banner):

1. `/tinh/hue` và `/tinh/ca-mau` trả 200, cả 4 section (Di sản/Ẩm thực/OCOP/Sự kiện) đều
   hiển thị dữ liệu (không rỗng).
2. Banner `province_top` gắn tỉnh Huế **chỉ** hiện ở `/tinh/hue`, không hiện ở
   `/tinh/ca-mau` (và ngược lại) — xác nhận targeting đúng, cùng cách đã verify Banner
   category targeting.
3. `/tinh` (trang index) liệt kê đúng 2 tỉnh, link tới đúng trang landing tương ứng.
4. Trang danh mục Post hiện có (`/bai-viet/danh-muc/di-san-van-hoa`,
   `/bai-viet/danh-muc/am-thuc-vung-mien`) vẫn hoạt động bình thường, hiển thị đủ bài của
   cả 2 tỉnh trộn chung (vì §3.2.2 xác nhận trang danh mục chung không lọc theo tỉnh).
5. Trang chi tiết 1 sản phẩm OCOP hiển thị đúng thông tin nhà sản xuất + hạng sao.

## 9. Ngoài phạm vi (out of scope) — roadmap từ ý tưởng gốc, KHÔNG làm ở v1

- **Cộng đồng CTV/KOL-KOC** (đăng ký, nộp bài/ảnh, quy trình duyệt, chấm điểm/thưởng) —
  cần module "Contributor" riêng (đăng ký, hồ sơ, submission workflow), phức tạp tương
  đương module Event hiện tại nhưng cho người ngoài đóng góp nội dung có định danh, không
  làm chung đợt này.
- **Gợi ý hành trình cá nhân hóa bằng AI** + bản đồ tương tác — cần model POI (điểm đến cụ
  thể, khác OCOP product), quyết định kiến trúc AI (rule-based hay gọi LLM), tích hợp bản
  đồ — để lại roadmap riêng.
- **Đo lường tác động truyền thông → du lịch** (search→booking→đến→chi tiêu→quay lại) —
  đòi hỏi dữ liệu từ hệ thống bên thứ 3 (booking engine, Google Analytics, POS) mà
  familiesforlife không sở hữu; ngoài khả năng đo bằng nội bộ hệ thống.
- **Đa ngôn ngữ đầy đủ cho nội dung chuyên đề tỉnh** — hạ tầng `PostArticleTranslation`
  (vi/en) đã có sẵn và dùng được ngay nếu cần, nhưng v1 không bắt buộc dịch toàn bộ nội
  dung demo.
- **UI admin cấu hình theme/landing kéo-thả theo tỉnh** — theo quyết định §0.3, tùy biến
  chỉ làm qua code, không xây trình soạn thảo giao diện.
