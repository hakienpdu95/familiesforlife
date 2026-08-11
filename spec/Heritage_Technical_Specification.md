# Module Di sản & Văn hóa (Heritage)

> Phát sinh từ đối chiếu tài liệu tham khảo `spec/kiendh-thamkhao.odt` (trích Bộ chỉ tiêu
> thống kê công nghiệp văn hóa Hà Nội — khái niệm "Hệ số hàm ý văn hóa", các chỉ tiêu
> CI102/CI501/CI504 đo doanh thu/lượt khách theo loại hình di sản) với hiện trạng
> `Modules/ProvinceShowcase`. Phần đo lường doanh thu/lượt khách tour thật **đã loại bỏ khỏi
> phạm vi** theo quyết định của người yêu cầu (site nội dung không thu doanh thu du lịch thật,
> không có gì để đo) — spec này chỉ giữ lại phần áp dụng được: **một thực thể có cấu trúc cho
> di tích/di sản**, làm trục liên kết Post/Event/Ocop lại với nhau.

## 0. Quyết định đã chốt

| # | Câu hỏi | Quyết định |
|---|---|---|
| 1 | Có đo doanh thu/lượt khách tour như tài liệu tham khảo không? | **Không.** Đã loại bỏ theo yêu cầu — site không vận hành dịch vụ du lịch, không có số liệu thật để đo, bịa ra sẽ vô nghĩa. |
| 2 | Module mới hay field mới trong Post? | **Module NWIDART mới** (`Modules/Heritage`) — mirror đúng quyết định đã áp dụng cho `Modules/Ocop` (spec §0 mục 6 của `Province_Showcase_Technical_Specification.md`): di tích cần field có cấu trúc (loại hình, xếp hạng, toạ độ) mà 1 bài viết Post không biểu diễn được, và cần làm điểm neo cho 3 module khác trỏ tới — nhét vào Post dưới dạng category/tag không đáp ứng được vai trò "trục liên kết". |
| 3 | Có bỏ category Post `di-san-van-hoa` hiện tại không? | **Không bỏ.** Category vẫn dùng cho bài viết mang tính tổng hợp/không gắn 1 di tích cụ thể (VD "5 lễ hội đáng xem miền Bắc"). `HeritageSite` là lớp **có cấu trúc** cộng thêm, không thay thế lớp biên tập tự do đã có. |
| 4 | Liên kết với Event/Ocop là bắt buộc hay tuỳ chọn? | **Tuỳ chọn (nullable)** ở cả 2 chiều — 1 lễ hội/sản phẩm OCOP có thể không gắn di tích nào, đúng thực tế (không phải lễ hội nào cũng diễn ra tại 1 di tích được ghi nhận, không phải sản phẩm OCOP nào cũng đến từ 1 làng nghề đã lên hồ sơ). Dữ liệu demo hiện có (Festival Huế, Lễ hội Áo dài Huế...) **không bị phá vỡ** — cột mới nullable, không migrate ngược dữ liệu cũ. |
| 5 | Dữ liệu hành chính tỉnh/phường-xã | Tái dùng nguyên `provinces`/`wards` + `province_code`/`ward_code` denormalize — đúng convention `Event`/`OcopProduct` đã áp dụng, không tạo cấu trúc mới. |
| 6 | Ảnh di tích | Spatie MediaLibrary (collection `cover`) ngay từ đầu — **không** đi qua giai đoạn cột phẳng `image_path`/`image_width`/... rồi xoá như `OcopProduct` đã từng làm (xem migration `2026_07_21_050100_drop_image_columns_from_ocop_products_table.php`); rút kinh nghiệm luôn, không lặp lại 2 bước. |
| 7 | Kiến trúc code | AVSA + CQRS-lite + Laravel Modules + Laravel Actions — cùng pattern `Modules/Ocop`/`Modules/Event`. |
| 8 | Tìm kiếm (Meilisearch), schema.org JSON-LD, mẫu prompt AI riêng cho di tích | **Ngoài phạm vi v1** — xem §10. Không phải vì khó, mà vì chưa có dữ liệu thật để cần tới (đúng nguyên tắc đã áp dụng nhiều lần trong dự án: không thêm khi chưa có nhu cầu nội dung cụ thể — xem `ArticleStructuredDataBuilder.php`). |

## 1. Giới thiệu & Mục tiêu

`Modules/ProvinceShowcase` hiện hiển thị 4 khối trên trang tỉnh (Di sản, Ẩm thực, OCOP, Sự
kiện), nhưng **hoàn toàn rời rạc**: khối "Di sản văn hóa" chỉ là danh sách bài viết gắn
category, không có toạ độ/loại hình/xếp hạng; lễ hội (Event) không biết mình diễn ra tại di
tích nào; sản phẩm OCOP của 1 làng nghề không biết làng nghề đó có phải di sản hay không.
Độc giả không thể "khám phá theo di tích" — chỉ có thể đọc từng bài rời rạc.

Mục tiêu v1: có 1 thực thể `HeritageSite` có cấu trúc thật (loại hình, xếp hạng, toạ độ, mô
tả), mỗi di tích có 1 trang chi tiết riêng tổng hợp **bài viết + lễ hội + sản phẩm OCOP** liên
quan tới đúng di tích đó, và khối "Di sản văn hóa" trên trang tỉnh đổi từ liệt kê bài viết
sang liệt kê di tích thật.

## 2. Khảo sát hiện trạng (đối chiếu, không lặp lại những gì đã có)

| Thành phần | Trạng thái | Ghi chú |
|---|---|---|
| Post category `di-san-van-hoa` | Có sẵn (seed bởi `ProvinceShowcaseCategorySeeder`) | Giữ nguyên — dùng cho bài viết không gắn 1 di tích cụ thể (xem §0 mục 3). |
| `App\View\Components\Province\SectionHeritage` | Có sẵn, query `PostArticleTranslation` theo `province_code` + category `di-san-van-hoa` | **Sẽ sửa** — đổi nguồn dữ liệu sang `HeritageSite`, xem §7. |
| `Modules\Event\Models\Event` | Có sẵn `province_code`/`ward_code`/`latitude`/`longitude` | Thêm cột `heritage_site_id` nullable — xem §8.1. |
| `Modules\Ocop\Models\OcopProduct` | Có sẵn `province_code`/`ward_code`, `producer_name` (text tự do, không phải entity làng nghề) | Thêm cột `heritage_site_id` nullable — xem §8.2. |
| `post_article_ocop_products` (pivot N-N) | Có sẵn, đặt trong `Modules/Ocop` | Mirror nguyên cấu trúc cho `post_article_heritage_sites`, đặt trong `Modules/Heritage` (module phụ thuộc biết về Post, không ngược lại — cùng lý do đã ghi trong migration Ocop). |
| `App\Traits\HasTenantMedia` | Có sẵn, dùng bởi `OcopProduct` | Tái dùng nguyên cho `HeritageSite`. |
| Thực thể "di tích/di sản" có cấu trúc | **Chưa có** | Trọng tâm spec này. |
| Thực thể "làng nghề" | **Chưa có** (chỉ là text `producer_name`) | Không tạo `CraftVillage` riêng ở v1 — `HeritageSite` với `heritage_type = intangible` (di sản văn hóa phi vật thể) đã đủ biểu diễn 1 làng nghề được ghi nhận là di sản; làng nghề CHƯA lên hồ sơ di sản vẫn giữ nguyên dạng text `producer_name` như hiện tại, không bắt buộc mọi làng nghề phải có `HeritageSite`. |

## 3. Kiến trúc dữ liệu

### 3.1 Enum `HeritageType` (loại hình di sản)

Theo đúng phân loại của Luật Di sản văn hóa Việt Nam (di tích lịch sử–văn hóa, di tích kiến
trúc nghệ thuật, di tích khảo cổ, danh lam thắng cảnh — nhóm "di sản vật thể"; cộng thêm "di
sản văn hóa phi vật thể" theo Công ước UNESCO 2003 mà Việt Nam là thành viên):

```php
// Modules/Heritage/app/Enums/HeritageType.php
enum HeritageType: string
{
    case HistoricalMonument = 'historical_monument';   // Di tích lịch sử - văn hóa
    case ArchitecturalArt   = 'architectural_art';      // Di tích kiến trúc nghệ thuật
    case Archaeological     = 'archaeological';         // Di tích khảo cổ
    case ScenicLandscape    = 'scenic_landscape';        // Danh lam thắng cảnh
    case Intangible         = 'intangible';              // Di sản văn hóa phi vật thể (làng nghề, lễ hội, nghệ thuật trình diễn...)
}
```

### 3.2 Enum `HeritageRank` (cấp xếp hạng)

```php
// Modules/Heritage/app/Enums/HeritageRank.php
enum HeritageRank: string
{
    case SpecialNational = 'special_national'; // Di tích quốc gia đặc biệt
    case National        = 'national';         // Di tích cấp quốc gia
    case Provincial       = 'provincial';       // Di tích cấp tỉnh/thành phố
    case Unranked         = 'unranked';         // Chưa xếp hạng — vẫn cho phép tạo, không bắt buộc đã có hồ sơ xếp hạng mới được đăng
}
```

`Unranked` tồn tại có chủ đích: biên tập viên có thể muốn viết về 1 địa điểm có giá trị văn
hóa/lịch sử nhưng chưa (hoặc không) được nhà nước xếp hạng chính thức (VD 1 ngôi làng cổ, 1
khu phố ẩm thực lâu đời) — không ép mọi bản ghi phải có hồ sơ xếp hạng mới tạo được.

### 3.3 Enum `HeritageVisitingStatus` (tình trạng tham quan — tuỳ chọn)

```php
// Modules/Heritage/app/Enums/HeritageVisitingStatus.php
enum HeritageVisitingStatus: string
{
    case Open       = 'open';       // Đang mở cửa đón khách
    case Restoring  = 'restoring';  // Đang trùng tu/hạn chế tham quan
    case Closed     = 'closed';     // Tạm đóng cửa
    case Unknown     = 'unknown';    // Chưa xác nhận — mặc định khi tạo mới
}
```

Field biên tập nhẹ, không phải hệ thống theo dõi thời gian thực — biên tập viên tự cập nhật
khi có thông tin (VD báo chí đưa tin trùng tu), không có tích hợp API bên ngoài.

### 3.4 Enum `HeritageSiteStatus` (trạng thái nội dung — bổ sung sau review, bản đầu bỏ sót)

Tách riêng khỏi 3 enum "mô tả di tích" ở trên (§3.1–§3.3) — đây là trạng thái **workflow biên
tập** (nháp/xuất bản), cùng bản chất với `OcopProductStatus`, không liên quan gì tới bản thân
di tích ngoài đời thật:

```php
// Modules/Heritage/app/Enums/HeritageSiteStatus.php
enum HeritageSiteStatus: string
{
    case Draft     = 'draft';     // Đang soạn, chưa hiển thị công khai
    case Published = 'published'; // Đã xuất bản — hiện trên trang tỉnh + trang chi tiết

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Nháp',
            self::Published => 'Đã xuất bản',
        };
    }
}
```

### 3.5 Ngôn ngữ, định dạng nội dung, quy tắc slug (bổ sung sau review)

3 điểm bản đầu chưa chốt rõ, dễ gây lệch chuẩn nếu để ngỏ tới lúc code:

- **Đơn ngôn ngữ (không có bảng translation)** — cùng `Event`/`OcopProduct` (2 module anh em gần
  nhất, không phải `Post`). `name`/`description`/`era`/`address` là cột phẳng trên chính bảng
  `heritage_sites`, KHÔNG tách bảng `*_translations`. Đây là lựa chọn có chủ đích để nhất quán
  với 2 module cùng tầng dữ liệu (Event, Ocop) — nếu sau này cần đa ngôn ngữ thật, làm đồng loạt
  cho cả 3 module cùng lúc, không lẻ tẻ riêng Heritage.
- **`description` là plain text, KHÔNG phải rich text/HTML** — dùng `<textarea>` thường (không
  Jodit), render ở view public qua `nl2br(e($site->description))`, cùng cách `Event::show.blade.
  php` đang xử lý field `description` của nó. Rich text editor (Jodit) trong dự án hiện chỉ dùng
  cho nội dung bài viết dài (`Post`) — di tích chỉ cần đoạn giới thiệu ngắn, không cần heading/
  bảng/ảnh chèn giữa văn bản.
- **Slug: unique toàn cục + tự thêm hậu tố khi trùng, KHÔNG unique composite theo tỉnh.** Tên di
  tích trùng lặp giữa các tỉnh RẤT phổ biến trong tiếng Việt ("Đình Làng", "Chùa Linh Ứng", "Đền
  Thượng"...) — nếu chỉ đơn giản `Str::slug($name)` rồi ép `unique()`, bản ghi thứ 2 sẽ vỡ
  validation. Unique composite `(slug, province_code)` KHÔNG giải quyết được vấn đề thật, vì URL
  công khai (`{slug}-ds{id}.html`, §5.2) không mang theo `province_code` — 2 di tích khác tỉnh
  nhưng cùng slug vẫn đụng route dù DB cho phép lưu cả hai. Xử lý ĐÚNG chỗ — vòng lặp cụ thể
  trong `CreateHeritageSiteAction` (chỉ chạy khi form để trống slug; nếu biên tập viên tự nhập,
  validate `unique:heritage_sites,slug` như bình thường, KHÔNG auto-suffix đè lên giá trị họ gõ):

  ```php
  private function generateUniqueSlug(string $name): string
  {
      $base = Str::slug($name);
      $slug = $base;
      $suffix = 2;

      while (HeritageSite::where('slug', $slug)->exists()) {
          $slug = "{$base}-{$suffix}";
          $suffix++;
      }

      return $slug;
  }
  ```

  Test bắt buộc (bổ sung §11): tạo 2 `HeritageSite` cùng tên "Đình Làng" ở 2 tỉnh khác nhau
  (không nhập slug tay) → bản ghi thứ 2 phải tự nhận `dinh-lang-2`, không ném lỗi validation.
- **Đổi slug sau khi đã publish → URL cũ 404, không có bảng redirect lịch sử.** Đây là giới hạn
  **CÓ SẴN Ở MỌI module khác** trong dự án (Post/Event/Ocop đều không có cơ chế redirect khi đổi
  slug) — Heritage không phát sinh vấn đề mới, chỉ kế thừa giới hạn chung. KHÔNG xây bảng
  redirect riêng cho một mình Heritage (sẽ lệch chuẩn, và đây là vấn đề toàn hệ thống nên xử lý
  đồng loạt nếu cần, không phải phạm vi spec này) — ghi nhận ở đây để biên tập viên được cảnh
  báo: hạn chế đổi slug sau khi đã publish và đã có nơi khác trỏ tới.

### 3.6 Model `HeritageSite` + migration

```php
// Modules/Heritage/database/migrations/xxxx_create_heritage_sites_table.php
Schema::create('heritage_sites', function (Blueprint $table) {
    $table->id();
    $table->uuid()->unique();
    $table->string('name', 200);
    $table->string('slug', 220)->unique();
    $table->string('heritage_type', 30);   // HeritageType
    $table->string('rank', 20)->default('unranked'); // HeritageRank
    $table->string('era', 100)->nullable(); // Niên đại — text tự do (VD "Thế kỷ 11", "Thời Lý - Trần"), không ép định dạng năm vì nhiều di tích không xác định chính xác
    $table->text('description')->nullable();
    $table->char('province_code', 2)->nullable();
    $table->string('province_name', 255)->nullable();
    $table->char('ward_code', 5)->nullable();
    $table->string('ward_name', 255)->nullable();
    $table->string('address', 255)->nullable(); // địa chỉ chi tiết, tự do — province/ward chỉ đủ cho lọc/hiển thị chuyên đề
    $table->decimal('latitude', 10, 7)->nullable();
    $table->decimal('longitude', 10, 7)->nullable();
    $table->string('visiting_status', 20)->default('unknown'); // HeritageVisitingStatus
    $table->string('status', 20)->default('draft'); // draft|published — cùng convention OcopProductStatus
    $table->boolean('is_featured')->default(false);
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['status', 'province_code'], 'idx_heritage_status_province');
    $table->index(['status', 'heritage_type'], 'idx_heritage_status_type');
    $table->index(['status', 'is_featured'], 'idx_heritage_status_featured');
});
```

```php
// Modules/Heritage/app/Models/HeritageSite.php
namespace Modules\Heritage\Models;

class HeritageSite extends Model implements HasMedia
{
    use SoftDeletes, LogsActivity, HasTenantMedia;

    protected $table = 'heritage_sites';

    protected $fillable = [
        'uuid', 'name', 'slug', 'heritage_type', 'rank', 'era', 'description',
        'province_code', 'province_name', 'ward_code', 'ward_name', 'address',
        'latitude', 'longitude', 'visiting_status', 'status', 'is_featured', 'sort_order',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'heritage_type'    => HeritageType::class,
        'rank'             => HeritageRank::class,
        'visiting_status'  => HeritageVisitingStatus::class,
        'status'           => HeritageSiteStatus::class, // draft|published, riêng biệt với 3 enum "nội dung" ở trên
        'latitude'         => 'decimal:7',
        'longitude'        => 'decimal:7',
        'is_featured'      => 'boolean',
        'sort_order'       => 'integer',
    ];

    // getRouteKeyName() = 'uuid' — cùng convention Event/OcopProduct (§3.4 Province_Showcase_
    // Technical_Specification.md): route công khai dùng slug, resolve thủ công trong controller.

    /**
     * CHỈ 1 relationship duy nhất trỏ RA module khác — vì Heritage SỞ HỮU bảng pivot này
     * (post_article_heritage_sites nằm trong Modules/Heritage, xem §3.7), đúng y hệt lý do
     * OcopProduct::articles() được chấp nhận là hard dependency 1 chiều sang Post trong toàn bộ
     * dự án (xem docblock OcopProduct model + migration pivot Ocop). KHÔNG có events()/
     * ocopProducts() ở đây — xem lý do ngay dưới.
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(PostArticle::class, 'post_article_heritage_sites', 'heritage_site_id', 'article_id');
    }

    public function scopePublished(Builder $query): void { $query->where('status', HeritageSiteStatus::Published); }
    public function scopeForProvince(Builder $query, string $provinceCode): void { $query->where('province_code', $provinceCode); }
}
```

> Media: collection `cover`, tái dùng nguyên `HasTenantMedia` — không thêm field ảnh phẳng nào
> (xem §0 mục 6).

> **Sửa sau review (không còn `events()`/`ocopProducts()` trên model — khác bản nháp đầu).**
> Bản nháp đầu có `hasMany(Event::class)`/`hasMany(OcopProduct::class)` trên `HeritageSite`,
> mâu thuẫn thẳng với tuyên bố "Heritage không require cứng Event/Ocop" ở §3.7: import
> `use Modules\Event\Models\Event;` + gọi `$site->events()` là phụ thuộc CỨNG thật — nếu module
> Event bị gỡ, bảng `events` không còn tồn tại, gọi quan hệ này sẽ ném lỗi query ngay, không
> "chỉ mất khối liên quan" như đã hứa.
>
> Sửa đúng bằng cách mirror pattern **đã có sẵn** trong chính dự án:
> `App\Models\Province` **không** có `events()`/`ocopProducts()` — `App\View\Components\Province\
> SectionEvents`/`SectionOcop` tự query `Event::where('province_code', ...)` /
> `OcopProduct::forProvince(...)` trực tiếp, Province model không biết Event/Ocop tồn tại.
> `HeritageSite` áp dụng NGUYÊN pattern đó: nơi cần "các Event/OcopProduct thuộc di tích này"
> (chỉ có `PublicHeritageController::show()`, xem §5.2) tự viết
> `Event::where('heritage_site_id', $site->id)->published()->...->get()` — quan hệ ngược nằm ở
> tầng CONTROLLER (vốn dĩ đã được phép biết cả 4 module vì là điểm tích hợp), không nằm trên
> Model. Nhờ vậy `HeritageSite.php` chỉ còn đúng 1 import phụ thuộc module khác (`PostArticle`),
> xoá được sạch phần còn lại — khớp đúng lời tuyên bố kiến trúc, không chỉ nói suông.

### 3.7 Liên kết từ 3 module khác (1 chiều VỀ `HeritageSite`)

Đúng nguyên tắc phụ thuộc 1 chiều đã áp dụng xuyên suốt dự án (`ContentFoundation`, `Ocop` →
`Post`): **`Heritage` không biết gì về Event/Ocop/Post khi chạy** (không có logic điều kiện
theo module khác trong `Modules/Heritage`); Event/Ocop/Post tự thêm cột trỏ VỀ `heritage_sites`.
`Modules/Heritage` không được require cứng 3 module kia — nếu 1 trong 3 module bị gỡ, di tích
vẫn hiển thị và hoạt động bình thường (chỉ khối liên quan tương ứng biến mất).

```php
// Modules/Event/database/migrations/xxxx_add_heritage_site_id_to_events_table.php
$table->foreignId('heritage_site_id')->nullable()->after('category_id')
    ->constrained('heritage_sites')->nullOnDelete();

// Modules/Ocop/database/migrations/xxxx_add_heritage_site_id_to_ocop_products_table.php
$table->foreignId('heritage_site_id')->nullable()->after('category_id')
    ->constrained('heritage_sites')->nullOnDelete();

// Modules/Heritage/database/migrations/xxxx_create_post_article_heritage_sites_table.php
Schema::create('post_article_heritage_sites', function (Blueprint $table) {
    $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
    $table->foreignId('heritage_site_id')->constrained('heritage_sites')->cascadeOnDelete();
    $table->primary(['article_id', 'heritage_site_id']);
});
```

`nullOnDelete()` (không phải `cascadeOnDelete()`) cho 2 cột FK ở Event/Ocop — xoá 1 di tích
**không được kéo theo xoá** lễ hội/sản phẩm đã tồn tại độc lập với nó; chỉ gỡ liên kết.

## 4. RBAC

Mirror đúng `OcopPermissionSeeder` — tài sản nền tảng (không `organization_id`), không owner-
based ACL:

```php
// Modules/Heritage/database/seeders/HeritagePermissionSeeder.php
const PERMISSIONS = ['heritage.manage'];
// platform_ops + platform_content_head được cấp; super-admin sync toàn bộ.
```

## 5. Routes

### 5.1 Admin CRUD (`dashboard/heritage`)

Mirror đúng cấu trúc `Modules/Ocop/Features/OcopProductManagement` — 1-1 tương ứng:

```
Modules/Heritage/app/Features/HeritageSiteManagement/
    Http/HeritageSiteAdminController.php     — resource except(['show']), cùng OcopProductAdminController
    Http/HeritageSiteApiController.php       — JSON cho Tabulator (backend/api/heritage/sites)
    Http/Resources/HeritageSiteListResource.php
    Data/HeritageSiteData.php                 — Spatie Laravel Data DTO
    Actions/CreateHeritageSiteAction.php
    Actions/UpdateHeritageSiteAction.php
    Actions/DeleteHeritageSiteAction.php
    Queries/ListHeritageSitesForAdminQuery.php + Handler
```

```php
Route::middleware(['auth'])->prefix('dashboard/heritage')->name('backend.heritage.')->group(function () {
    Route::resource('sites', HeritageSiteAdminController::class)->except(['show']);
});
Route::middleware(['auth'])->prefix('backend/api/heritage')->name('backend.api.heritage.')->group(function () {
    Route::get('sites', [HeritageSiteApiController::class, 'index'])->name('sites');
});
```

Form tạo/sửa (`_form.blade.php`, mirror `Ocop/resources/views/admin/products/_form.blade.php`):
tên, slug (auto-fill, cho phép sửa tay), loại hình + xếp hạng (2 `<select>` TomSelect), niên
đại, mô tả (`<textarea>` thường — KHÔNG Jodit, xem §3.5), địa chỉ + `<x-address-picker>` (tỉnh/
phường-xã, tái dùng nguyên component đã có), toạ độ (2 input số, có thể để trống), tình trạng
tham quan, ảnh bìa (FilePond, cùng convention `OcopCoverImageMediaTest`), nổi bật (checkbox),
thứ tự sắp xếp.

> **Media conversion — xác nhận cơ chế thật (bổ sung sau review):** dự án **không** dùng
> `registerMediaConversions()` kiểu Spatie chuẩn ở bất kỳ model nào (`HasTenantMedia::
> registerMediaCollections()` nói rõ "No registration needed here — Spatie accepts any
> collection name"). Conversion (`thumb`/`medium`/`preview`) khai báo **tập trung theo
> collection name trong `config/media.php`** (`conversions` + `conversion_settings`), `App\
> Services\Media\MediaUrlService` tự build URL `{dir}/{conversion}.webp`, fallback về ảnh gốc
> nếu conversion chưa generate. Việc CẦN LÀM: thêm entry cho collection `cover` của
> `heritage_sites` vào `config/media.php` — **dễ bị bỏ sót vì nằm ngoài `Modules/Heritage`**,
> nếu quên, ảnh vẫn hiển thị được (fallback gốc) nhưng không có responsive/nén đúng kích cỡ.
> Xem thêm checklist §11.

**Validation rules (`HeritageSiteData`/`StoreHeritageSiteRequest`)** — bổ sung sau review, bản
đầu chưa liệt kê cụ thể:

```php
'name'            => ['required', 'string', 'max:200'],
'slug'            => ['nullable', 'string', 'max:220', 'alpha_dash', 'unique:heritage_sites,slug'], // rỗng = tự sinh, xem §3.5
'heritage_type'   => ['required', 'string', Rule::in(array_column(HeritageType::cases(), 'value'))],
'rank'            => ['required', 'string', Rule::in(array_column(HeritageRank::cases(), 'value'))],
'era'             => ['nullable', 'string', 'max:100'],
'description'     => ['nullable', 'string', 'max:3000'],
'province_code'   => ['nullable', 'string', 'exists:provinces,province_code'],
'ward_code'       => ['nullable', 'string', 'exists:wards,ward_code'],
'address'         => ['nullable', 'string', 'max:255'],
'latitude'        => ['nullable', 'numeric', 'between:-90,90'],
'longitude'       => ['nullable', 'numeric', 'between:-180,180'],
'visiting_status' => ['nullable', 'string', Rule::in(array_column(HeritageVisitingStatus::cases(), 'value'))],
'status'          => ['required', 'string', Rule::in(array_column(HeritageSiteStatus::cases(), 'value'))],
'is_featured'     => ['boolean'],
'sort_order'      => ['integer', 'min:0'],
```

`alpha_dash` trên `slug` chỉ áp dụng khi biên tập viên **tự sửa tay** — trường hợp để trống,
`CreateHeritageSiteAction` tự sinh trước khi validate nên luôn hợp lệ. `latitude`/`longitude`
bắt buộc đi cùng nhau ở tầng Action (cả hai cùng có hoặc cùng trống) — không thêm rule
`required_with` chéo 2 chiều vì Laravel xử lý cặp `required_with` hai chiều dễ gây thông báo
lỗi lặp/khó hiểu hơn là tự kiểm tra đơn giản trong Action.

### 5.2 Public — trang chi tiết + trang danh sách

Cùng cơ chế hậu tố `.html` đã áp dụng cho Post (`-d{id}`), Event (`-sk{id}`), Ocop (`-op{id}`)
— marker mới **phải khác 3 marker trên** để không bị route đăng ký trước "nuốt" request:

```php
// Modules/Heritage/routes/web.php
Route::get('di-san', [PublicHeritageController::class, 'index'])->name('heritage.public.index');
Route::get('{slug}-ds{id}.html', [PublicHeritageController::class, 'show'])
    ->where(['slug' => '[a-z0-9\-]+', 'id' => '[0-9]+'])
    ->name('heritage.public.show');
```

`PublicHeritageController::show()` — tra theo `slug` (như Post/Event/Ocop, `{id}` chỉ để phân
biệt path, không dùng để lookup), load kèm:

```php
$site = HeritageSite::published()->where('slug', $slug)->firstOrFail();
$articles = $site->articles()->whereHas('translations', fn ($q) => $q->published())->limit(6)->get();

// KHÔNG dùng $site->events()/$site->ocopProducts() — HeritageSite model không có 2 quan hệ này
// (xem §3.6 "Sửa sau review"). Query trực tiếp Event/OcopProduct tại ĐÚNG nơi cần, giữ Model
// sạch phụ thuộc. published() ở cả 2 dòng dưới KHÔNG chỉ lọc trạng thái publish — Eloquent
// SoftDeletes tự áp global scope loại trừ bản ghi đã xoá mềm, nên "published()" ở HeritageSite
// robust hơn viết tay "!= trashed", nhưng ở ĐÂY published() là của Event/OcopProduct (lọc chính
// 2 model đó), không liên quan gì tới soft-delete của HeritageSite — soft-delete của
// HeritageSite được chặn từ TRƯỚC ĐÓ bởi chính dòng đầu tiên ($site = HeritageSite::published()
// ->...->firstOrFail() — nếu site đã bị xoá mềm hoặc là draft, firstOrFail() ném 404 ngay,
// không bao giờ tới được 2 dòng dưới).
$events   = Event::where('heritage_site_id', $site->id)->published()->upcoming()->orderBy('start_date')->limit(6)->get();
$products = OcopProduct::where('heritage_site_id', $site->id)->published()->orderBy('sort_order')->limit(6)->get();
```

> **Quy tắc bắt buộc cho MỌI nơi hiển thị link ngược tới `HeritageSite`** (từ trang Event, trang
> OcopProduct — xem §8.1/§8.2): luôn query qua `HeritageSite::published()`, KHÔNG dùng thẳng
> `HeritageSite::find($event->heritage_site_id)`. Lý do: `heritage_site_id` là FK `nullOnDelete`
> — chỉ tự động về `NULL` khi di tích bị **xoá cứng** (`forceDelete()`); nếu di tích chỉ bị **xoá
> mềm** (`SoftDeletes`) hoặc bị chuyển về `draft`, cột FK ở Event/Ocop **vẫn còn giá trị cũ**
> (không tự dọn) — nếu code hiển thị link ngược không lọc qua `published()`, độc giả sẽ thấy
> link dẫn tới trang 404 hoặc rò rỉ nội dung nháp chưa publish.

Trang hiển thị: hero (ảnh bìa + tên + badge loại hình/xếp hạng + tình trạng tham quan), mô tả,
bản đồ tĩnh/toạ độ (nếu có `latitude`/`longitude`), 3 khối liên quan (Bài viết / Lễ hội sắp
diễn ra / Sản phẩm OCOP của làng nghề này) — mỗi khối tự ẩn nếu rỗng, cùng nguyên tắc
`<x-frontend.banner-slot>`/`SectionHeritage` đã áp dụng toàn hệ thống.

**`PublicHeritageController::index()` (`/di-san`) — chốt rõ phạm vi v1 để tránh phình việc khi
code:** chỉ liệt kê `HeritageSite::published()` phân trang, sắp theo `is_featured` rồi
`sort_order`, có query param `?province=` (dùng bởi nút "Xem tất cả →" ở §7.1). **KHÔNG** làm
bộ lọc theo loại hình/xếp hạng (dropdown "lọc theo di tích lịch sử/danh lam thắng cảnh...") ở
v1 — số lượng di tích ban đầu (seed demo §9) còn quá ít để bộ lọc có giá trị thật; thêm khi có
đủ dữ liệu để cần lọc.

## 6. Luồng nghiệp vụ & sử dụng

### 6.1 Luồng biên tập viên (tạo mới 1 di tích và gắn nội dung liên quan)

1. Vào `dashboard/heritage/sites` → "Thêm di tích mới" → điền tên, chọn loại hình + xếp hạng,
   niên đại, mô tả, địa chỉ (tỉnh/phường-xã qua `<x-address-picker>`), toạ độ (tuỳ chọn), ảnh
   bìa → lưu ở trạng thái `draft`.
2. Xem lại, chuyển `status = published` khi sẵn sàng hiển thị công khai.
3. Khi viết 1 bài Post **về đúng di tích này**: trong form bài viết, chọn di tích liên quan
   (multi-select, giống cách chọn sản phẩm OCOP liên quan đã có ở ContentOutlines/ArticleForm)
   → tạo bản ghi `post_article_heritage_sites`.
4. Khi tạo 1 Event là lễ hội **diễn ra tại di tích này**: trong form Event, chọn "Di tích liên
   quan" (select, tuỳ chọn) → set `events.heritage_site_id`.
5. Khi thêm 1 sản phẩm OCOP **của làng nghề đã được ghi nhận là di sản**: trong form sản phẩm
   OCOP, chọn "Di tích/làng nghề liên quan" (select, tuỳ chọn) → set
   `ocop_products.heritage_site_id`.

Bước 3–5 đều **tuỳ chọn** — không có bước nào bị chặn nếu bỏ qua liên kết (đúng §0 mục 4).

### 6.2 Luồng độc giả (khám phá liên thông)

1. Vào trang tỉnh `/tinh/hue` → khối "Di sản văn hóa Huế" giờ hiện **danh sách di tích** (card
   có ảnh + tên + badge loại hình/xếp hạng), không còn là danh sách bài viết rời rạc.
2. Bấm vào 1 di tích (VD "Đại Nội Huế") → trang chi tiết `dai-noi-hue-ds12.html` → đọc mô tả,
   xem toạ độ, và thấy ngay: các bài viết đã viết về Đại Nội, lễ hội sắp diễn ra tại đây (VD
   Festival Huế nếu đã gắn `heritage_site_id`), sản phẩm OCOP của làng nghề quanh khu vực nếu
   có gắn.
3. Từ trang chi tiết 1 Event (lễ hội) hoặc 1 sản phẩm OCOP có `heritage_site_id`, hiện 1 khối
   nhỏ "Diễn ra tại di tích: [tên]" / "Làng nghề: [tên]" link ngược về trang chi tiết di tích
   (§8.1/§8.2) — tạo được điều hướng 2 chiều trong TRẢI NGHIỆM dù dữ liệu chỉ lưu FK 1 chiều.

Kết quả: độc giả có thể bắt đầu từ BẤT KỲ điểm nào (1 bài viết, 1 lễ hội, 1 sản phẩm OCOP,
hoặc trực tiếp trang di tích) và luôn tìm được đường quay lại/đi tiếp tới 3 loại nội dung còn
lại liên quan tới cùng 1 di tích — thay vì 4 khối cô lập như hiện tại.

## 7. Tích hợp với `ProvinceShowcase`

### 7.1 Sửa `App\View\Components\Province\SectionHeritage`

Đổi nguồn dữ liệu từ `PostArticleTranslation` sang `HeritageSite`:

```php
class SectionHeritage extends Component
{
    public Collection $sites;

    public function __construct(public Province $province)
    {
        $this->sites = HeritageSite::published()
            ->forProvince($province->province_code)
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit(config('provinceshowcase.section_limit'))
            ->get();
    }
}
```

`resources/views/components/province/section-heritage.blade.php` đổi từ
`<x-frontend.article-card>` sang 1 card mới hiển thị ảnh bìa + tên + badge loại hình/xếp hạng,
link tới `route('heritage.public.show', ...)`. Nút "Xem tất cả →" đổi từ
`route('post.public.category', ['category' => 'di-san-van-hoa'])` sang
`route('heritage.public.index', ['province' => $province->slug])` (trang danh sách di tích lọc
sẵn theo tỉnh — query param, không cần route riêng).

Nguyên tắc "tự ẩn nếu rỗng" giữ nguyên (`@if($sites->isNotEmpty())`).

> **Hệ quả có chủ đích khi `province_code = null`** (bổ sung sau review): `heritage_type =
> intangible` (di sản văn hóa phi vật thể) hợp lệ để KHÔNG gắn 1 tỉnh cụ thể — VD 1 loại hình
> nghệ thuật trình diễn phổ biến ở nhiều tỉnh miền Bắc, không thuộc riêng địa phương nào. Bản
> ghi đó vẫn tạo/publish bình thường, nhưng **sẽ không hiện ở `SectionHeritage` của bất kỳ tỉnh
> nào** (`forProvince()` luôn `WHERE province_code = ...`, không khớp `NULL`) — vẫn truy cập
> được qua `/di-san` (§5.2). Đây là hành vi ĐÚNG theo thiết kế, không phải bug: di sản không
> gắn 1 địa phương thì không nên xuất hiện ở trang chuyên đề CỦA 1 địa phương cụ thể.

### 7.2 `Modules/ProvinceShowcase` không cần sửa gì khác

`show.blade.php` chỉ gọi `<x-province.section-heritage :province="$province" />` — thay đổi
hoàn toàn nằm trong class component, `ProvincePublicController` và route không đổi.

## 8. Tích hợp với Event và Ocop (chi tiết field/UI)

### 8.1 `Modules/Event`

- Migration thêm `heritage_site_id` (xem §3.7).
- Form tạo/sửa Event (`admin/events/_form.blade.php` hoặc tương đương) thêm 1 `<select>` tuỳ
  chọn "Di tích liên quan" (TomSelect, nguồn dữ liệu từ `HeritageSite::published()`, có thể
  lọc theo tỉnh Event đang chọn để danh sách không quá dài).
- `public/show.blade.php` của Event: nếu `$event->heritage_site_id` có giá trị, hiện 1 dòng
  nhỏ trong khối "Địa điểm" — "Diễn ra tại di tích: [tên]" (link `heritage.public.show`).
- `EventData`/`StoreEventRequest`/`UpdateEventRequest` thêm rule
  `'heritage_site_id' => ['nullable', 'integer', 'exists:heritage_sites,id']`.

### 8.2 `Modules/Ocop`

- Migration thêm `heritage_site_id` (xem §3.7).
- Form sản phẩm OCOP (`admin/products/_form.blade.php`) thêm `<select>` tuỳ chọn "Làng nghề/di
  tích liên quan" — CHỈ hiện các `HeritageSite` có `heritage_type = intangible` hoặc
  `historical_monument` làm gợi ý mặc định (không ép cứng, editor vẫn chọn được loại khác nếu
  hợp lý — VD 1 sản phẩm ẩm thực gắn với 1 danh lam thắng cảnh có truyền thống ẩm thực riêng).
- `public/show.blade.php` của OcopProduct: tương tự Event, hiện link ngược nếu có gắn.
- `OcopProductData` thêm cùng rule `nullable exists:heritage_sites,id`.

### 8.3 `Modules/Post` — pivot bài viết ↔ di tích

> **Đính chính sau review**: bản đầu spec này viết "không sửa trực tiếp ArticleAuthoring của
> Post — Post không cần biết Heritage tồn tại". Kiểm tra lại cơ chế THẬT của picker "sản phẩm
> OCOP liên quan" đã có (bản mẫu cần mirror) cho thấy điều ngược lại: `Modules/Post` **hard-code
> import `Modules\Ocop\Models\OcopProduct` thẳng trong `ArticleAdminController`**, không qua
> View Composer/event/interface nào — Post ở tầng UI picker này CHỦ ĐỘNG biết và phụ thuộc vào
> Ocop, dù nguyên tắc "module phụ thuộc biết về module kia, không ngược lại" vẫn đúng ở tầng DỮ
> LIỆU (bảng pivot đặt trong Ocop, không đặt trong Post). Mirror ĐÚNG cơ chế thật thay vì nguyên
> tắc lý tưởng hoá — sửa cả 2 chỗ dưới đây theo đúng những gì `ArticleAdminController` đã làm
> với OCOP.

Cụ thể, mirror 1-1 4 điểm sau (tên file/dòng tham chiếu là của cơ chế OCOP hiện có):

1. **View** (`Modules/Post/resources/views/admin/articles/edit.blade.php`) — thêm 1 checkbox
   multi-select `name="heritage_site_ids[]"` cạnh khối `ocop_product_ids[]` đã có. Chỉ ở màn
   **edit**, không có ở **create** (đúng hành vi picker OCOP hiện tại — bài viết phải tồn tại
   trước mới gắn được thực thể liên quan).
2. **Nguồn dữ liệu ban đầu** (`ArticleAdminController`) — thêm
   `use Modules\Heritage\Models\HeritageSite;`, query lọc theo `province_code`/`ward_code` của
   bài viết (cùng cách lọc `OcopProduct` hiện tại), truyền xuống view qua `compact(...)`.
3. **Reload động qua API** — `Modules/Heritage/routes/api.php` thêm
   `GET /api/heritage-sites/picker` (mirror `ListOcopProductsForPickerAction` của Ocop), JS
   `article-form.js` thêm `_setupHeritageSitePicker` cạnh `_setupOcopProductPicker`.
4. **Lưu/pivot sync** (`Modules/Post/app/Features/ArticleAuthoring/Actions/Concerns/
   SyncsArticleRelations.php`) — thêm dòng
   `$article->heritageSites()->sync($data->heritage_site_ids);` cạnh dòng `ocopProducts()->sync(...)`
   đã có; `PostArticle` model thêm quan hệ
   `belongsToMany(HeritageSite::class, 'post_article_heritage_sites', 'article_id', 'heritage_site_id')`.
   Validation ở `ArticleAdminController`: `'heritage_site_ids' => ['array'], 'heritage_site_ids.*' => ['exists:heritage_sites,id']`.

Bảng pivot (`post_article_heritage_sites`) vẫn đặt migration trong `Modules/Heritage` (§3.7) —
chỉ tầng UI picker là Post chủ động import Heritage, không đảo ngược layer dữ liệu.

## 9. Seeder & dữ liệu demo

`Modules/Heritage/database/seeders/HeritageDemoSeeder.php` — tạo ~4-6 `HeritageSite` demo cho
đúng 2 tỉnh đã có chuyên đề (Huế, Cà Mau — xem `Province_Showcase_Technical_Specification.md`),
VD "Đại Nội Huế" (historical_monument, special_national), "Lăng Tự Đức" (architectural_art,
national), "Mũi Cà Mau" (scenic_landscape, national). Sau đó **cập nhật tay** (không tự động
suy luận) 1-2 Event demo đã có sẵn (VD "Festival Huế 2026") gắn `heritage_site_id` trỏ tới di
tích demo tương ứng, để trang chi tiết di tích có dữ liệu liên quan thật ngay từ đầu, không
trống trơn khi demo.

Dữ liệu Event/Ocop/Post đã tồn tại **không bị migrate/gán tự động** — `heritage_site_id` giữ
nguyên `NULL`, biên tập viên gắn thủ công dần dần khi rảnh (đúng tinh thần "bổ sung, không ép
làm lại toàn bộ dữ liệu cũ" đã áp dụng cho các cột nullable khác trong dự án, VD `funnel_stage`
ở `ContentCalendar`).

**Yêu cầu bắt buộc cho seeder (bổ sung sau review)** — không chỉ tạo bản ghi `HeritageSite`
trơn, mà PHẢI có đủ để trang chi tiết không trống rỗng khi demo:
- Mỗi `HeritageSite` demo có ảnh bìa thật (media `cover`) — không để trống, vì trang chi tiết
  (§5.2) lấy ảnh hero từ đây.
- Ít nhất 1 di tích demo có ĐỦ CẢ 3 loại cross-link (1 bài Post qua pivot, 1 Event qua
  `heritage_site_id`, 1 sản phẩm OCOP qua `heritage_site_id`) — để test/demo thấy được cả 3
  khối liên quan cùng lúc trên 1 trang, không chỉ test riêng lẻ từng loại.

## 10. Ngoài phạm vi v1 (có ghi nhận, không làm ngay)

| Ý tưởng | Vì sao chưa làm |
|---|---|
| Đo doanh thu/lượt khách tour (CI501/CI504 trong tài liệu tham khảo) | **Loại bỏ theo yêu cầu** — không có số liệu du lịch thật để đo (§0 mục 1). |
| Tìm kiếm Meilisearch cho `HeritageSite` | Chưa có khối lượng dữ liệu đủ lớn để cần — làm khi số di tích vượt qua mức duyệt bằng mắt được (cùng lý do Ocop từng bắt đầu không có, thêm sau ở `SiteSearch_Activation_Expansion_Technical_Specification.md`). |
| Schema.org JSON-LD (`LandmarksOrHistoricalBuildings`/`TouristAttraction`) cho trang chi tiết di tích | Theo đúng nguyên tắc đã chốt ở `ArticleStructuredDataBuilder.php` — Google xác nhận structured data không phải đòn bẩy chính cho AI search, chỉ thêm khi có nhu cầu cụ thể phát sinh, không thêm phòng hờ. |
| Mẫu prompt AI "Giới thiệu di tích" trong `PromptFrameworkStudio` (nhóm Chiến lược nội dung) | Ý tưởng hợp lý, cùng cơ chế `task_instructions` đã xây cho Content Strategy — để Phase 2 sau khi `HeritageSite` có dữ liệu thật để làm context, tránh viết prompt tham chiếu 1 model chưa ai dùng. |
| Bản đồ tương tác (Leaflet/Google Maps embed) thay vì chỉ hiện toạ độ dạng text/link | UI nâng cao, không chặn việc dùng được — có thể thêm sau khi có nhiều di tích/tỉnh hơn để bản đồ có giá trị (1-2 điểm/tỉnh chưa cần bản đồ). |
| `CraftVillage` là entity riêng tách khỏi `HeritageSite` | Chưa thấy nhu cầu — `heritage_type = intangible` đủ biểu diễn 1 làng nghề đã lên hồ sơ di sản (§2). Tách riêng nếu sau này làng nghề cần field không phù hợp với "di tích" (VD số hộ làm nghề, sản lượng) mà `HeritageSite` không nên gánh. |

## 11. Checklist trước khi triển khai

- [ ] Migration `heritage_sites` + 4 enum (`HeritageType`, `HeritageRank`, `HeritageVisitingStatus`, `HeritageSiteStatus`)
- [ ] Migration thêm `heritage_site_id` nullable vào `events`, `ocop_products`
- [ ] Migration `post_article_heritage_sites` (pivot, đặt trong `Modules/Heritage`)
- [ ] Model `HeritageSite` (`HasMedia`, `HasTenantMedia`, `LogsActivity`, `SoftDeletes`) — CHỈ 1 relationship `articles()`, KHÔNG có `events()`/`ocopProducts()` (xem §3.6)
- [ ] `CreateHeritageSiteAction` tự sinh + auto-suffix slug khi trùng (§3.5) — không chỉ `Str::slug()` trần
- [ ] Validation rules đầy đủ theo §5.1 (đặc biệt `latitude`/`longitude` between + cặp đi cùng nhau)
- [ ] `HeritagePermissionSeeder` (`heritage.manage` → `platform_ops` + `platform_content_head`)
- [ ] Admin CRUD đầy đủ (`HeritageSiteManagement`) — mirror cấu trúc `OcopProductManagement`
- [ ] `PublicHeritageController` (`index`/`show`) + route `.html` suffix (`-ds{id}`) + view — `index()` KHÔNG có bộ lọc loại hình/xếp hạng (§5.2)
- [ ] Mọi nơi hiển thị link ngược tới `HeritageSite` (trang Event/Ocop) đều qua `HeritageSite::published()` (§5.2 "Quy tắc bắt buộc") — không query thẳng bằng `find()`
- [ ] Sửa `App\View\Components\Province\SectionHeritage` + blade tương ứng — đổi nguồn Post → HeritageSite
- [ ] Form Event/OCOP/Post thêm field chọn di tích liên quan (đều tuỳ chọn)
- [ ] `HeritageDemoSeeder` cho Huế + Cà Mau — mỗi site demo có ảnh cover thật; ít nhất 1 site có đủ cả 3 cross-link (Post + Event + Ocop) — xem §9
- [ ] Thêm collection `cover` của `heritage_sites` vào `config/media.php` (`conversions` + `conversion_settings`) — dễ quên vì nằm ngoài `Modules/Heritage`, xem §5.1
- [ ] Picker "Di tích liên quan" trong `Modules/Post` (`ArticleAdminController`, `edit.blade.php`, `SyncsArticleRelations`, `article-form.js`) — mirror đúng 4 điểm đã liệt kê ở §8.3, không đoán mới
- [ ] Test: tạo di tích → published → hiện đúng trên trang tỉnh
- [ ] Test: 2 di tích cùng tên ("Đình Làng") khác tỉnh, không nhập slug tay → bản ghi thứ 2 tự nhận hậu tố `-2`, không lỗi validation (§3.5)
- [ ] Test: gắn Event/Ocop/Post → hiện đúng ở cả 2 chiều (trang di tích thấy nội dung liên quan; trang Event/Ocop thấy link ngược)
- [ ] Test: xoá mềm hoặc chuyển 1 di tích về `draft` sau khi đã gắn Event → link ngược ở trang Event KHÔNG còn hiện (không phải hiện link chết) — kiểm chứng đúng quy tắc §5.2
- [ ] Test: xoá cứng 1 di tích → Event/Ocop liên quan không bị xoá theo, chỉ mất liên kết (`heritage_site_id` về `NULL`)
- [ ] Test: trang chi tiết 1 di tích chưa gắn nội dung nào → cả 3 khối liên quan tự ẩn, không hiện khung rỗng
