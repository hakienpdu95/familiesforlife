# Module Quản lý Banner (Banner Management)
**Đặc tả Kỹ thuật Chi tiết — Sẵn sàng Triển khai**

**Phiên bản:** 1.1 — bổ sung targeting theo ngữ cảnh (contextual targeting), xem §7.5
**Ngày:** 15/07/2026
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions
**Module mới:** `Modules/Banner`
**Module liên quan:** `Modules/Post` (trang chủ, danh mục, chi tiết bài viết), `Modules/Event` (trang danh sách/chi tiết sự kiện), `Modules/Menu` (tiền lệ kiến trúc — xem §0)

> **Lịch sử phiên bản**
> - **v1.0** — CRUD banner + placement linh hoạt (chuỗi tự do theo config) + lịch chạy + đếm click.
> - **v1.1** — thêm targeting theo danh mục bài viết (`target_type`/`target_value`, §3–§7.5). Các loại targeting khác (trang cụ thể, thiết bị, khu vực, phân khúc người dùng...) **chưa** làm ở v1.1, xem §9.

---

## 0. Quyết định đã chốt

| Chủ đề | Hiện trạng codebase | Quyết định spec này | Lý do |
|---|---|---|---|
| **Banner cạnh logo (site-header)** | `frontend-header.blade.php` đang render 1 ô cố định (`.site-header__content` cột phải) — hiện là placeholder tĩnh (`.ph`) hoặc 1 ảnh đơn cấu hình qua `config('post.header_banner_url')`, KHÔNG quản lý được từ dashboard, KHÔNG tái sử dụng được ở trang khác | Thay bằng **1 vị trí banner** (`placement = header_ad`) do module `Banner` quản lý — render qua `<x-frontend.banner-slot placement="header_ad" />` | Đúng yêu cầu "đặt banner linh hoạt nhiều nơi" — nếu vẫn hard-code riêng cho site-header thì mỗi vị trí mới lại phải tự chế lại từ đầu |
| **Số lượng/loại vị trí đặt banner** | Không tồn tại khái niệm chung — mỗi nơi (nếu có) tự làm riêng | 1 bảng `banners` với cột `placement` (chuỗi tự do, validate theo `config('banner.placements')`), **KHÔNG dùng PHP Enum cố định cho placement** | Danh sách vị trí sẽ tăng dần theo thời gian (trang chủ, danh mục, chi tiết, và các trang tương lai) — Enum cố định buộc phải sửa code (thêm case, migration nếu enum ở DB) mỗi lần thêm 1 chỗ mới; validate qua config là 1 dòng thêm vào mảng, không cần deploy lại logic nghiệp vụ |
| **Số banner hiển thị cùng lúc tại 1 vị trí** | N/A | KHÔNG cố định "1 vị trí = 1 banner" — `<x-frontend.banner-slot placement="..." :limit="N" />` nhận `limit` tuỳ nơi gọi (header_ad dùng `limit=1`, sidebar có thể `limit=3`), model chỉ trả về danh sách banner đang active theo `sort_order`, KHÔNG tự quyết định số lượng | Vị trí hẹp (cạnh logo) chỉ cần 1 banner; vị trí rộng (giữa danh sách bài viết, sidebar tương lai) có thể xếp nhiều — để cứng "1:1" sẽ phải sửa schema khi có nhu cầu xếp chồng |
| **Nhiều banner cùng vị trí, cùng đang active — chọn hiển thị cái nào khi limit < số banner active** | N/A | Sắp theo `sort_order` (tăng dần), banner nào `sort_order` nhỏ hơn ưu tiên hiển thị trước — **KHÔNG làm carousel/rotate tự động bằng JS trong v1** | Giữ đơn giản, đúng quy mô hiện tại; carousel xoay vòng có thể thêm sau (Alpine đã có sẵn trong stack, xem §9) mà không cần đổi schema |
| **Lịch chạy banner** | N/A | Cột `start_date`/`end_date` nullable — banner tự ẩn/hiện theo ngày qua scope query (`WHERE (start_date IS NULL OR start_date <= today) AND (end_date IS NULL OR end_date >= today)`), KHÔNG cần job/cron dọn dẹp | Cùng nguyên tắc `isCurrentlySponsored()` (`Modules/Post/app/Models/PostArticle.php`) — kiểm tra ngày trực tiếp lúc query, không phụ thuộc job chạy định kỳ mới ẩn đúng lúc |
| **Đo hiệu quả banner** | N/A | v1 CHỈ đếm **click** (`click_count`, tăng qua redirect có kiểm soát) — **KHÔNG đo impression (lượt xem)** | Đếm click theo đúng pattern đã có (`PostProductBlockButton.click_count`, `RecordProductBlockClickAction`); đo impression cần JS beacon/gọi API mỗi lần banner vào viewport — phức tạp hơn hẳn, để §9 (ngoài phạm vi v1) |
| **Ai quản lý banner** | N/A | Permission mới `banner.manage`, gán cho **`platform_ops`** + **`platform_content_head`** (super-admin luôn có toàn quyền qua `syncPermissions`) | Banner là nội dung vận hành/quảng cáo (đối tác, ads nội bộ), giống lý do `platform_ops` được cấp `event.edit` (Modules/Event/database/seeders/EventPermissionSeeder) — không phải nội dung biên tập cần `platform_content_creator` |
| **Quy trình duyệt** | N/A | **Không có** state machine submitted → approved → published như Post/Event | Banner do nội bộ platform tạo trực tiếp qua dashboard, không nhận nộp từ công chúng (khác Event có `EventSubmission` từ độc giả ẩn danh) — không có lý do bắt buộc 2 tầng duyệt |
| **Ảnh banner do ai/đâu cấp** | `public/banner-treemviet.png` (banner thật của 1 tổ chức khác — VACR) đã bị từ chối dùng | Module chỉ định nghĩa **cơ chế upload/lưu trữ** (`Storage::disk('public')`, cùng pattern `StoreEventPosterAction`) — **không kèm banner mẫu nào lấy từ nguồn ngoài**; seeder demo (nếu có) dùng ảnh khối màu trung tính tự sinh, không dùng ảnh/logo của bất kỳ tổ chức thật nào | Tránh lặp lại vấn đề đã gặp — xem hội thoại trước: banner của 1 tổ chức thật ngụ ý liên kết/tài trợ không có thật nếu hiển thị trên site này |
| **Minh bạch quảng cáo** | N/A | Cột `badge_label` (nullable, vd "Quảng cáo", "Tài trợ") hiển thị đè góc banner khi có giá trị | Cùng tinh thần `disclosure_text` ở bài viết tài trợ (`spec/dac-ta-ky-thuat-bai-viet-tai-tro.md`) — banner trả phí/đối tác nên minh bạch, không bắt buộc (để trống nếu là banner nội bộ/trang trí thuần) |
| **Targeting theo ngữ cảnh (v1.1)** | N/A | Thêm `target_type`/`target_value` — banner có thể gắn với **1 danh mục bài viết cụ thể** (`target_type='category'`) hoặc **toàn site** (`target_type=null`, mặc định). 1 placement được phép **trộn lẫn** banner global và banner theo category cùng lúc — banner theo category ưu tiên hiển thị trước, global lấp chỗ trống còn lại (§7.5) | Ban đầu (v1.0) mọi banner ở 1 placement hiển thị đồng nhất bất kể trang nào gọi tới — không đáp ứng được nhu cầu thật (vd banner đối tác chỉ muốn xuất hiện ở danh mục "Sức khỏe", không muốn xuất hiện ở danh mục "Tài chính"). `target_value` cố tình để `string` phẳng (không FK riêng từng loại) để tái dùng cho các `target_type` khác sau này (trang cụ thể, v.v.) mà không cần thêm cột |

---

## 1. Giới thiệu & Mục tiêu

Hiện tại cổng thông tin **không có khái niệm banner dùng chung** — nơi duy nhất từng cần đến (ô quảng cáo cạnh logo trong `site-header`, copy cấu trúc từ `spec/header.html`) đang tạm dùng 1 giá trị cấu hình tĩnh (`config('post.header_banner_url')`) hoặc placeholder trang trí (`.ph`). Cách này có 3 vấn đề:

1. **Không sửa được từ dashboard** — đổi banner phải sửa `.env`/config rồi deploy lại, không phải việc của Ops/Marketing.
2. **Không tái sử dụng được** — mỗi vị trí mới (trang chủ, danh mục, chi tiết bài viết...) sẽ phải tự chế lại cơ chế hiển thị/lưu trữ ảnh từ đầu, trùng lặp logic.
3. **Không có lịch chạy, không đếm click** — banner đối tác/quảng cáo thường cần bật/tắt theo đợt (theo hợp đồng, theo mùa) và cần số liệu click để báo cáo hiệu quả, cả hai đều không có sẵn.

Module **Banner** giải quyết cả 3 vấn đề bằng 1 bảng `banners` tự quản lý, có CRUD admin riêng, và 1 Blade component dùng chung (`<x-frontend.banner-slot>`) để bất kỳ trang nào trong `Modules/Post`, `Modules/Event`, hay layout dùng chung (`layouts/frontend.blade.php`) cũng gọi được — chỉ cần biết **placement key** của vị trí đó.

**Nguyên tắc thiết kế cốt lõi:** đặt banner ở 1 vị trí mới **không cần sửa migration/model/Action** — chỉ cần (a) thêm 1 dòng vào `config('banner.placements')`, (b) gọi `<x-frontend.banner-slot placement="key-moi" />` tại đúng chỗ trong Blade của trang đó, (c) admin tạo banner mới chọn đúng placement đó trong dashboard.

---

## 2. Khảo sát vị trí đặt banner trên site hiện tại

Liệt kê các trang công khai đang tồn tại (`Modules/Post`, `Modules/Event`) và vị trí banner đề xuất cho mỗi trang — đây là danh sách **khởi tạo** cho `config('banner.placements')`, có thể bổ sung sau mà không cần sửa schema.

| Placement key | Trang | Vị trí cụ thể trong Blade | Kích thước đề xuất (px) | `limit` đề xuất | Targeting theo category? |
|---|---|---|---|---|---|
| `header_ad` | Mọi trang (layout dùng chung) | `frontend-header.blade.php` — `.site-header__content` cột phải cạnh logo (thay chỗ đang là `.ph`/config cũ) | 970×90 (giống chuẩn leaderboard ads) | 1 | ❌ Không có ngữ cảnh category (hiển thị ở mọi trang, kể cả trang chủ/sự kiện) — luôn nhận banner `global` |
| `home_top` | Trang chủ (`Modules/Post/.../home.blade.php`) | Ngay dưới `<x-frontend.hero>` (5 tin), trước `<x-frontend.promo-bar>` | 1200×150 | 1 | ❌ Trang chủ không gắn với 1 category — chỉ `global` |
| `home_between_features` | Trang chủ | Xen giữa 2 `<x-frontend.section-feature>` (bố cục tạp chí) | 1200×150 | 1 | ❌ Chỉ `global` (cùng lý do `home_top`) |
| `category_top` | Danh mục bài viết (`post::public.category`) | Dưới breadcrumb/tiêu đề danh mục, trước lưới bài viết | 1200×150 | 1 | ✅ Truyền `:context="['category_slug' => $category->slug]"` (§7.2) |
| `article_inline` | Chi tiết bài viết (`post::public.article`) | Chèn giữa nội dung bài viết (sau 1 số content block nhất định — xem §7.3 về giới hạn kỹ thuật hiện tại) | 728×90 | 1 | ✅ Dùng category chính của bài viết (`$article->categories->first()->slug`) làm context |
| `event_list_top` | Danh sách sự kiện (`event::public.index`) | Dưới tiêu đề trang, trước lưới sự kiện | 1200×150 | 1 | ❌ Event dùng `EventCategory`, không phải `PostCategory` — targeting theo category ở v1.1 chỉ hỗ trợ `PostCategory` (xem §7.5); để `global` |
| `event_show_top` | Chi tiết sự kiện (`event::public.show`) | Trước phần mô tả sự kiện | 728×90 | 1 | ❌ Cùng lý do `event_list_top` |

> Trang chi tiết bài viết/sự kiện hiện là **layout 1 cột** (`max-w-3xl`, không có sidebar) — placement kiểu "sidebar" (thường thấy ở báo điện tử) **chưa khả thi** cho tới khi có 1 spec riêng đổi layout 2 cột. Không đưa vào bảng trên để tránh hứa hẹn 1 vị trí chưa dựng được khung sườn.
>
> **Targeting v1.1 chỉ hỗ trợ `PostCategory`** (không phải `EventCategory`) — 2 placement của Event (`event_list_top`/`event_show_top`) hiện chỉ nhận banner `global`. Mở rộng sang `EventCategory` là 1 `target_type` mới (vd `event_category`) có thể thêm sau mà không đổi schema (§7.5).

---

## 3. Kiến trúc dữ liệu

### 3.1 ERD

```
Banner
  ├─ uuid
  ├─ placement (string, index)              — khoá vị trí, validate theo config('banner.placements')
  ├─ target_type (nullable string)           — null = global (mọi nơi gọi placement này), 'category' = theo 1 PostCategory
  ├─ target_value (nullable string)          — giá trị tương ứng target_type (vd slug category khi target_type='category');
  │                                             string phẳng để tái dùng cho target_type khác sau này, không JSON ở v1.1
  ├─ title (nullable string)                 — tên NỘI BỘ để admin nhận diện trong danh sách, KHÔNG hiển thị public
  ├─ image_path, image_width, image_height, image_size_bytes
  ├─ alt_text (nullable string)              — SEO/accessibility, hiển thị public
  ├─ link_url (nullable string)              — banner có thể chỉ trang trí, không bắt buộc click-through
  ├─ open_in_new_tab (bool)
  ├─ badge_label (nullable string)           — "Quảng cáo"/"Tài trợ" — để trống nếu không cần minh bạch
  ├─ start_date, end_date (nullable date)    — lịch chạy
  ├─ sort_order (unsigned smallint)
  ├─ is_active (bool)
  ├─ click_count (unsigned int, default 0)
  ├─ created_by, updated_by, timestamps, soft delete
```

Không có bảng con — banner là 1 thực thể phẳng (ảnh + link + lịch chạy), khác `MenuItem`/`PostCategory` không cần cây phân cấp.

### 3.2 Migration

```php
Schema::create('banners', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();

    $table->string('placement', 60);           // xem BannerPlacementList (§4.2) — validate ở Action, không FK/enum DB
    $table->string('target_type', 30)->nullable()->after('placement');  // null|'category' (v1.1) — xem §7.5
    $table->string('target_value', 255)->nullable()->after('target_type'); // slug category khi target_type='category'
    $table->string('title', 150)->nullable();   // ghi chú nội bộ cho admin, KHÔNG render public

    $table->string('image_path', 255);
    $table->unsignedInteger('image_width')->nullable();
    $table->unsignedInteger('image_height')->nullable();
    $table->unsignedInteger('image_size_bytes')->nullable();
    $table->string('alt_text', 255)->nullable();

    $table->string('link_url', 2048)->nullable();
    $table->boolean('open_in_new_tab')->default(false);
    $table->string('badge_label', 40)->nullable();

    $table->date('start_date')->nullable();
    $table->date('end_date')->nullable();

    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->unsignedInteger('click_count')->default(0);

    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['placement', 'is_active', 'sort_order'], 'idx_banner_placement_active');
    $table->index(['placement', 'start_date', 'end_date'], 'idx_banner_placement_schedule');
    $table->index(['placement', 'target_type', 'target_value'], 'idx_banner_targeting'); // Banner::forPlacement() lọc theo context (§7.5)
});
```

Không có `organization_id` — banner là tài sản nền tảng (platform), cùng nguyên tắc đã áp dụng cho `PostArticle`/`Event`/`MenuItem` (spec/Platform_RBAC_Phase2_Specification.md §3.3): không tổ chức (tenant) nào sở hữu banner, banner phục vụ đồng nhất cho toàn bộ cổng thông tin.

---

## 4. Model & cấu hình

### 4.1 `Modules\Banner\Models\Banner`

```php
class Banner extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid', 'placement', 'target_type', 'target_value', 'title',
        'image_path', 'image_width', 'image_height', 'image_size_bytes', 'alt_text',
        'link_url', 'open_in_new_tab', 'badge_label',
        'start_date', 'end_date', 'sort_order', 'is_active', 'click_count',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'open_in_new_tab' => 'boolean',
        'is_active'       => 'boolean',
        'start_date'      => 'date',
        'end_date'        => 'date',
        'sort_order'      => 'integer',
        'click_count'     => 'integer',
        // target_type/target_value KHÔNG cần cast — cả 2 đều là string phẳng (target_type
        // null|'category'; target_value = slug category khi có). Chỉ cần cast nếu sau này
        // target_value chứa JSON (§9 — chưa cần ở v1.1, mọi target_type hiện tại chỉ lưu 1
        // giá trị đơn giản, ép cast 'array' sớm sẽ vỡ khi target_type=null/target_value=null).
    ];

    public function scopeActive(Builder $query): void
    {
        $today = now()->toDateString();

        $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $today))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today));
    }

    /**
     * Dùng bởi <x-frontend.banner-slot> — cùng tinh thần MenuItem::tree()/PostCategory::navTree().
     *
     * $context — dữ liệu ngữ cảnh trang đang gọi, hiện chỉ đọc key 'category_slug' (§7.5).
     * Không có key này (hoặc $context rỗng) → chỉ trả banner global, KHÔNG lỗi, KHÔNG cần nơi
     * gọi kiểm tra trước (những placement không có ngữ cảnh category — §2 — cứ gọi
     * forPlacement($placement, limit: N) như v1.0, không đổi cách gọi).
     *
     * Ưu tiên: banner target_type='category' khớp target_value === category_slug trước, banner
     * global (target_type NULL) lấp phần còn lại của $limit — xem §7.5 để biết lý do thứ tự này.
     */
    public static function forPlacement(string $placement, array $context = [], ?int $limit = null): Collection
    {
        $categorySlug = $context['category_slug'] ?? null;

        $targeted = collect();

        if ($categorySlug) {
            $targeted = static::active()
                ->where('placement', $placement)
                ->where('target_type', 'category')
                ->where('target_value', $categorySlug)
                ->orderBy('sort_order')
                ->get();
        }
        // else ($categorySlug === null, vd gọi forPlacement('header_ad') không truyền $context):
        // bỏ qua hẳn khối if trên — KHÔNG query bảng banners lần nào cho phần "targeted", $targeted
        // giữ nguyên collect() rỗng. 2 dòng return bên dưới chạy đúng như v1.0: bước early-return
        // không khớp (0 >= $limit chỉ đúng khi $limit=0), $remainingLimit = $limit (trừ đi 0), nên
        // hàm tự nhiên rơi thẳng vào query global lấy đủ $limit — không cần nhánh if/else riêng
        // nào cho "trường hợp không context", cùng 1 luồng code phục vụ cả 2 trường hợp.

        // Đã đủ (hoặc thừa) banner theo context — không cần query thêm banner global.
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

    public function isExternalUrl(): bool
    {
        if (! $this->link_url) {
            return false;
        }

        $host = parse_url($this->link_url, PHP_URL_HOST);

        return $host !== null && $host !== request()->getHost();
    }

    /** Nhãn hiển thị (dropdown form admin §6.2, cột "placement" ở trang danh sách). */
    public static function getPlacementLabel(string $key): ?string
    {
        return config("banner.placements.{$key}.label");
    }

    /** Gợi ý kích thước — hiển thị ở form admin (§7.4), KHÔNG chặn cứng validate. */
    public static function getPlacementRecommendedSize(string $key): ?string
    {
        return config("banner.placements.{$key}.recommended_size");
    }

    /** @return string[] Danh sách key hợp lệ — dùng trong Rule::in() khi validate (§5.1). */
    public static function validPlacementKeys(): array
    {
        return array_keys(config('banner.placements', []));
    }
}
```

### 4.2 `config/banner.php` (module config)

Mỗi placement là **1 entry duy nhất** gộp cả nhãn hiển thị lẫn kích thước khuyến nghị — tránh tình trạng 2 mảng song song (`placements` + `recommended_sizes`) dễ lệch nhau khi thêm placement mới mà quên cập nhật mảng còn lại.

```php
return [
    'name' => 'Banner',

    // Danh sách placement hợp lệ — THÊM 1 ENTRY MỚI Ở ĐÂY khi cần 1 vị trí mới, không sửa gì
    // khác (không migration, không Enum). Key = giá trị lưu trong banners.placement.
    // Đọc qua Banner::getPlacementLabel()/getPlacementRecommendedSize()/validPlacementKeys()
    // (§4.1) thay vì config('banner.placements.xxx') rải rác nhiều nơi.
    'placements' => [
        'header_ad' => [
            'label'             => 'Banner cạnh logo (mọi trang)',
            'recommended_size'  => '970×90',
        ],
        'home_top' => [
            'label'             => 'Trang chủ — dưới khối tin nổi bật',
            'recommended_size'  => '1200×150',
        ],
        'home_between_features' => [
            'label'             => 'Trang chủ — xen giữa các khối tin (bố cục tạp chí)',
            'recommended_size'  => '1200×150',
        ],
        'category_top' => [
            'label'             => 'Danh mục bài viết — đầu trang',
            'recommended_size'  => '1200×150',
        ],
        'article_inline' => [
            'label'             => 'Chi tiết bài viết — giữa nội dung',
            'recommended_size'  => '728×90',
        ],
        'event_list_top' => [
            'label'             => 'Danh sách sự kiện — đầu trang',
            'recommended_size'  => '1200×150',
        ],
        'event_show_top' => [
            'label'             => 'Chi tiết sự kiện — đầu trang',
            'recommended_size'  => '728×90',
        ],
    ],

    // Loại targeting hợp lệ (v1.1) — dùng cho dropdown target_type ở form admin (§6.2) và
    // validate (§5.1). Key 'global' chỉ tồn tại ở TẦNG FORM/UI — khi lưu, BannerData chuyển
    // 'global' → target_type=null trong DB (không lưu chuỗi 'global' theo đúng nghĩa "không có
    // targeting nào"). THÊM 1 DÒNG MỚI Ở ĐÂY khi có target_type mới (vd 'page', 'event_category'
    // — xem ghi chú §2), không cần sửa migration/Enum.
    'target_types' => [
        'global'   => 'Toàn site (Global)',
        'category' => 'Theo danh mục bài viết',
        // 'page'   => 'Theo trang cụ thể',       // có thể mở sau — xem §9
        // 'event_category' => 'Theo danh mục sự kiện', // có thể mở sau — xem ghi chú §2
    ],
];
```

`placements` và `target_types` là 2 khái niệm **độc lập** — không phải mọi placement đều cần/nên cho chọn `target_type=category` (xem cột "Targeting theo category?" ở bảng §2: `header_ad`/`home_top`/`home_between_features`/`event_list_top`/`event_show_top` không có ngữ cảnh category nên form admin **vẫn hiển thị** lựa chọn "Theo danh mục bài viết" cho các placement này (không chặn ở tầng config), nhưng banner tạo ra sẽ **không bao giờ hiển thị** vì trang gọi các placement đó không truyền `context` — nơi gọi component mới là nơi quyết định placement nào thực sự "có ngữ cảnh" để tận dụng targeting, không phải config).

---

## 5. Business rules

### 5.1 Validate khi tạo/sửa (`BannerData` + Action)

- `placement`: bắt buộc, phải là 1 key tồn tại trong `config('banner.placements')` — validate bằng `Rule::in(Banner::validPlacementKeys())` (§4.1).
- `image`: bắt buộc khi tạo mới (không bắt buộc khi sửa nếu giữ ảnh cũ), `image|max:2048` (2MB) — quy trình lưu:
  1. Đọc `width`/`height` gốc qua `getimagesize()`, cùng pattern `StoreEventPosterAction`.
  2. **Resize nếu ảnh gốc rộng hơn 1200px** — dùng `intervention/image-laravel` (`^4.0`, đã có sẵn trong `composer.json`, cách dùng thực tế xem `App\Services\Media\MediaUploadService::runConversions()`): `Image::decode($content)->scaleDown(width: 1200)` rồi `encode()` lại trước khi lưu. Mục đích **giảm dung lượng file** (banner nặng làm chậm mọi trang có banner đó, kể cả `header_ad` xuất hiện trên toàn site) — không phải yêu cầu nghiệp vụ về kích thước hiển thị.
  3. Lưu qua `$file->storeAs('banners', $filename, 'public')` (hoặc ghi trực tiếp buffer đã resize nếu bước 2 có chạy), cùng `image_size_bytes` lấy từ kích thước file **sau khi resize** (không phải `$file->getSize()` gốc nếu đã resize).
- `link_url`: nullable, `url` nếu có nhập (banner có thể không click được — thuần trang trí/thông báo).
- `start_date`/`end_date`: nullable date, nếu cả hai đều có thì `end_date >= start_date`.
- `badge_label`: nullable, max 40 ký tự.
- **`target_type`** (v1.1): nullable, `Rule::in(array_keys(config('banner.target_types')))` khi có nhập. Form gửi lên giá trị `'global'`/`'category'` (key của `target_types`) — `BannerData`/Action chuyển `'global'` thành `target_type = null` trước khi lưu DB (§4.2 giải thích lý do không lưu chuỗi `'global'`).
- **`target_value`** (v1.1):
  - Nếu `target_type = 'category'` → **bắt buộc**, phải là `slug` của 1 `PostCategory` đang `is_active = true` (`Rule::exists('post_categories', 'slug')->where('is_active', true)`).
  - Nếu `target_type = null` (global) → **bắt buộc phải để trống** (`prohibited_if:target_type,null` hoặc validate thủ công trong Action) — tránh trạng thái vô nghĩa "global nhưng vẫn có target_value".

### 5.1.1 Trộn banner global và banner theo category cùng 1 placement

Cùng 1 `placement` được phép có **đồng thời** banner `target_type=null` (global) và nhiều banner `target_type='category'` (mỗi banner gắn 1 category khác nhau, hoặc nhiều banner cùng gắn 1 category) — không có ràng buộc loại trừ lẫn nhau ở tầng dữ liệu. Ví dụ hợp lệ tại placement `category_top`:

| Banner | target_type | target_value | Hiển thị khi nào |
|---|---|---|---|
| Banner A | `null` | `null` | Mọi trang danh mục KHÔNG có banner riêng cho category đó |
| Banner B | `category` | `suc-khoe-gia-dinh` | Chỉ trang danh mục "Sức khỏe gia đình" |
| Banner C | `category` | `tai-chinh-gia-dinh` | Chỉ trang danh mục "Tài chính gia đình" |

Khi xem trang danh mục "Sức khỏe gia đình": Banner B hiển thị (khớp target_value); Banner A **không** hiển thị thêm nếu `limit=1` đã đủ (xem thứ tự ưu tiên §7.5). Khi xem danh mục "Du lịch gia đình" (không có banner riêng): chỉ Banner A (global) hiển thị.

### 5.2 Nhiều banner cùng vị trí

`Banner::forPlacement($placement, $context, $limit)` trả về banner đang active (theo `scopeActive`) sắp theo `sort_order` — banner khớp `$context` (nếu có) ưu tiên trước, banner global lấp phần còn lại của `$limit` (§5.1.1/§7.5). Không có cơ chế "chỉ 1 banner active tại 1 thời điểm mỗi vị trí" ở tầng dữ liệu — nơi gọi (`<x-frontend.banner-slot>`) tự quyết định `limit` phù hợp với không gian hiển thị của vị trí đó (xem bảng §2).

### 5.3 Lịch chạy

Không cần job/cron — mỗi lần trang được tải, `scopeActive()` tự lọc theo ngày hiện tại. Banner hết hạn (`end_date` đã qua) tự động biến mất khỏi mọi placement mà không cần thao tác dọn dẹp; admin có thể xem lại banner đã hết hạn trong danh sách quản trị (không lọc theo ngày ở trang admin, chỉ lọc ở trang công khai).

### 5.4 Đếm click

Cùng pattern `RecordProductBlockClickAction`/`ProductBlockClickController`:

```php
// routes — public, không yêu cầu đăng nhập
Route::get('banners/{banner:uuid}/click', [BannerClickController::class, 'redirect'])->name('banner.click');
```

```php
class RecordBannerClickAction
{
    use AsAction;

    public function handle(Banner $banner): ?string
    {
        if (! $banner->link_url) {
            return null;
        }

        $banner->increment('click_count');

        return $banner->link_url;
    }
}
```

`<x-frontend.banner-slot>` render `<a href="{{ route('banner.click', $banner) }}">` thay vì trỏ thẳng `link_url` — cùng lý do Post CTA button không link trực tiếp (đếm được click trước khi rời trang).

### 5.5 Minh bạch quảng cáo

Khi `badge_label` có giá trị, hiển thị 1 nhãn nhỏ (DaisyUI `badge badge-neutral badge-xs`) đè góc banner — cùng tinh thần `disclosure_text` ở bài viết tài trợ. Để trống nếu banner là nội dung nội bộ/trang trí, không bắt buộc điền.

---

## 6. Admin CRUD (`Modules/Banner`)

### 6.1 Routes

```php
Route::middleware(['auth'])->prefix('dashboard/banners')->name('backend.banner.')->group(function () {
    Route::resource('items', BannerAdminController::class)->parameters(['items' => 'banner']);
    Route::post('items/reorder', [BannerAdminController::class, 'reorder'])->name('items.reorder');
});
```

### 6.2 Giao diện quản trị

- **Trang danh sách**: bảng phẳng (không cần cây — banner không phân cấp), có **filter theo placement** (dropdown, nguồn từ `config('banner.placements')`), **filter theo `target_type`** (dropdown, nguồn từ `config('banner.target_types')` — thêm ở v1.1), và filter theo trạng thái (đang chạy / đã hết hạn / chưa tới ngày / tắt thủ công — tính toán ở tầng view từ `is_active` + `start_date`/`end_date`, không cần cột "status" riêng). Cột hiển thị: ảnh thumbnail, placement (nhãn từ config), **cột Target** (chi tiết format ngay dưới đây), lịch chạy, click_count, is_active toggle nhanh.
  - **Format cột/badge Target** (v1.1, DaisyUI `badge`) — dùng ĐÚNG 1 format này ở mọi nơi hiển thị target (trang danh sách, và bất kỳ màn hình khác sau này liệt kê banner) để nhất quán UI:
    | `target_type` | Class badge | Text hiển thị | Nguồn text |
    |---|---|---|---|
    | `null` | `badge-ghost` | `Toàn site` | Chuỗi tĩnh, không tra DB |
    | `category` | `badge-info` | `Danh mục: {tên category}` | `PostCategory::where('slug', $banner->target_value)->value('name')` — vd `Danh mục: Sức khỏe gia đình` |
    | `category` nhưng category đã bị xoá/đổi slug (không tìm thấy) | `badge-warning` | `Danh mục: (đã xoá)` | Fallback khi query trên trả `null` — tránh hiển thị badge rỗng/gây hiểu nhầm là "Toàn site" |
- **Form tạo/sửa**: upload ảnh (kèm gợi ý kích thước từ `Banner::getPlacementRecommendedSize($placement)`, cập nhật động khi đổi `placement` — Alpine, xem §7.4), `title` (nội bộ), `alt_text`, `link_url`, `open_in_new_tab`, `badge_label`, `start_date`/`end_date` (date picker — dùng lại `resources/js/modules/flatpickr.js` đã có sẵn trong bundle frontend/backend), `sort_order`.
  - **Target (v1.1)**: radio/select `target_type` (nguồn `config('banner.target_types')` — "Toàn site (Global)" / "Theo danh mục bài viết"). Chọn "Theo danh mục bài viết" → hiện thêm 1 select `target_value` populate từ `PostCategory::active()->get(['slug', 'name'])` (cùng nguồn dữ liệu form admin Menu đã dùng — `PostCategory::active()`). Select category **ẩn** khi `target_type = global` (Alpine `x-show`, xem mẫu bên dưới) — tránh gửi lên 1 giá trị `target_value` không còn ý nghĩa.
  - **Validate phía UI (Alpine)**: nút submit disable (hoặc hiện lỗi inline) khi `target_type = 'category'` mà chưa chọn `target_value` — chặn sớm trước khi submit, KHÔNG thay thế validate server (§5.1) vẫn là nguồn sự thật cuối cùng.

    ```js
    Alpine.data('bannerTargetForm', () => ({
        targetType: 'global',
        targetValue: '',
        get needsCategory() {
            return this.targetType === 'category';
        },
        get isValid() {
            return ! this.needsCategory || this.targetValue !== '';
        },
    }));
    ```
- **Không có bước duyệt** — tạo xong hiển thị ngay (nếu `is_active=true` và trong khoảng ngày hợp lệ).

### 6.3 Permission

Thêm vào `app/Enums/PermissionEnum.php`:

```php
case BANNER_MANAGE = 'banner.manage';
```

`BannerPermissionSeeder` (theo đúng pattern `EventPermissionSeeder`):

```php
private const PERMISSIONS = ['banner.manage'];

// platform_ops — vận hành/đối tác quảng cáo, cùng lý do được cấp event.edit
$ops->givePermissionTo('banner.manage');

// platform_content_head — toàn quyền nội dung nền tảng
$head->givePermissionTo('banner.manage');

// super-admin: syncPermissions(Permission::all()) như các seeder khác
```

---

## 7. Render công khai

### 7.1 `<x-frontend.banner-slot>`

```blade
@props([
    'placement',
    'context' => [], // v1.1 — vd ['category_slug' => $category->slug], xem §7.5
    'limit' => 1,
])

@php($banners = \Modules\Banner\Models\Banner::forPlacement($placement, $context, $limit))

@if($banners->isNotEmpty())
<div class="banner-slot banner-slot--{{ $placement }}">
    @foreach($banners as $banner)
    <a href="{{ route('banner.click', $banner) }}"
       @if($banner->open_in_new_tab) target="_blank" @endif
       @if($banner->open_in_new_tab || $banner->isExternalUrl())
       rel="{{ trim(($banner->open_in_new_tab ? 'noopener ' : '') . ($banner->isExternalUrl() ? 'nofollow' : '')) }}"
       @endif
       class="banner-slot__link">
        <img src="{{ Storage::url($banner->image_path) }}" alt="{{ $banner->alt_text ?? '' }}" class="banner-slot__img" loading="lazy">
        @if($banner->badge_label)
        <span class="badge badge-neutral badge-xs banner-slot__badge">{{ $banner->badge_label }}</span>
        @endif
    </a>
    @endforeach
</div>
@endif
```

Banner không có `link_url` vẫn render (dùng `<div>` thay `<a>`) — nhánh này thêm khi implement, không ảnh hưởng cấu trúc props.

### 7.2 Nhúng vào từng trang (không context / có context)

```blade
{{-- frontend-header.blade.php — thay khối .ph/config cũ. Không có ngữ cảnh category (§2) nên
     KHÔNG truyền :context — forPlacement() chỉ trả banner global. --}}
<div class="col-12 col-lg-9">
    <div class="text-right m-none">
        <x-frontend.banner-slot placement="header_ad" />
    </div>
</div>
```

```blade
{{-- post::public.category — CÓ ngữ cảnh category, truyền :context để nhận banner theo
     danh mục (nếu có) trước, global mới lấp phần còn lại (§7.5). --}}
<x-frontend.banner-slot
    placement="category_top"
    :context="['category_slug' => $category->slug]"
    :limit="1" />
```

```blade
{{-- post::public.article — category CHÍNH của bài viết (không phải mọi category bài viết đó
     thuộc, giống cách breadcrumb hiện tại chỉ lấy $article->categories->first()). --}}
<x-frontend.banner-slot
    placement="article_inline"
    :context="['category_slug' => $article->categories->first()?->slug]"
    :limit="1" />
```

Tương tự cho `home_top`/`event_list_top`/... (không có ngữ cảnh category — §2) — chỉ 1 dòng `<x-frontend.banner-slot placement="..." />` không cần `:context`, đặt đúng chỗ trong Blade của trang đó (xem vị trí cụ thể ở bảng §2).

### 7.3 Giới hạn kỹ thuật — `article_inline`

Nội dung bài viết hiện render qua `ArticleContentRenderer::render()` — nối chuỗi HTML từ `post_content_blocks` theo `sort_order`, không có khái niệm "chèn thêm 1 block lạ giữa chừng".

**v1 (implement trước, giữ đơn giản):** chèn `article_inline` cố định sau block nội dung đầu tiên — `<x-frontend.banner-slot placement="article_inline" />` gọi 1 lần, ngay sau khi render block đầu, không cấu hình gì thêm.

**Điểm mở rộng đã tính trước (chưa implement ở v1, không cần đổi schema khi làm sau):**
- Component nhận thêm prop tuỳ chọn `after_block_order` (vd `after_block_order="2"`) để chọn chèn sau block thứ N thay vì luôn luôn là block đầu — `ArticleContentRenderer` cần lặp qua block theo `sort_order` và chèn banner xen kẽ thay vì nối chuỗi 1 lần như hiện tại.
- Hoặc: `ArticleContentRenderer` bắn 1 sự kiện/callback sau mỗi block (`afterRenderingBlock($block, $index)`), nơi gọi tự quyết định có chèn banner sau block đó không — linh hoạt hơn nhưng thay đổi lớn hơn cách render hiện tại (nối chuỗi HTML thuần).

Cả 2 hướng trên để **§9 (ngoài phạm vi v1)** — ghi lại ở đây để khi có nhu cầu thật (vd muốn chèn theo % độ dài bài, hoặc admin tự chọn vị trí) thì biết bắt đầu sửa từ đâu, không phải thiết kế lại từ đầu.

### 7.4 Alpine — form admin (gợi ý kích thước + target_type)

1 component Alpine duy nhất cho form tạo/sửa banner, gộp cả 2 mối quan tâm đã nêu ở §6.2 (gợi ý kích thước theo placement, và ẩn/hiện + validate select category theo `target_type`) — cùng 1 form nên dùng chung 1 scope thay vì 2 component tách rời:

```js
Alpine.data('bannerForm', (recommendedSizes) => ({
    placement: '',
    targetType: 'global',
    targetValue: '',

    get recommendedSize() {
        return recommendedSizes[this.placement] ?? null;
    },
    get needsCategory() {
        return this.targetType === 'category';
    },
    get isValid() {
        return ! this.needsCategory || this.targetValue !== '';
    },
}));
```

Đây là tương tác DUY NHẤT cần Alpine trong module này (form admin, không phải trang công khai) — `<x-frontend.banner-slot>` là HTML tĩnh render server-side, không cần JS để hiển thị.

### 7.5 Targeting theo ngữ cảnh (Contextual Targeting) — v1.1

**Khái niệm:** 1 banner có thể được gắn (`target_type`/`target_value`) để **chỉ** xuất hiện khi trang đang xem khớp với 1 điều kiện cụ thể — v1.1 chỉ hỗ trợ 1 điều kiện: **danh mục bài viết** (`target_type = 'category'`, `target_value` = slug danh mục). Banner không gắn điều kiện nào (`target_type = null`) là banner **global**, luôn đủ điều kiện hiển thị ở bất kỳ trang nào gọi đúng `placement` của nó.

**Cách nơi gọi cung cấp ngữ cảnh:** mỗi trang Blade tự biết ngữ cảnh của chính nó (đang xem danh mục nào, bài viết nào...) — truyền xuống qua prop `context` (mảng key-value, key hiện hỗ trợ duy nhất: `category_slug`) khi gọi `<x-frontend.banner-slot>` (ví dụ §7.2). Trang không có ngữ cảnh category (`header_ad`, `home_top`...) đơn giản là không truyền `context` — không cần biết gì về cơ chế targeting bên dưới.

**Thứ tự ưu tiên khi có ngữ cảnh** (`Banner::forPlacement()`, §4.1):

1. Query banner có `target_type='category'` VÀ `target_value` khớp `category_slug` truyền vào — sắp theo `sort_order`.
2. Nếu số banner ở bước 1 đã **đủ** `$limit` → dừng, trả về luôn (không query thêm).
3. Nếu **chưa đủ** → query thêm banner `target_type IS NULL` (global), sắp theo `sort_order`, lấy vừa đủ số còn thiếu (`$limit - count(bước 1)`).
4. Kết quả cuối = banner theo context (đứng trước) nối banner global (đứng sau, lấp chỗ trống).

**Ví dụ cụ thể** — dùng lại đúng 3 banner ở bảng §5.1.1 (Banner A = global, Banner B = `category`/`suc-khoe-gia-dinh`, Banner C = `category`/`tai-chinh-gia-dinh`), placement `category_top`, `limit=1`:

| Gọi component tại | `forPlacement()` nhận | Bước 1 (query targeted) | Bước 2 (đủ `$limit` chưa?) | Bước 3 (query global) | Kết quả trả về |
|---|---|---|---|---|---|
| Trang danh mục **"Sức khỏe gia đình"** | `context = ['category_slug' => 'suc-khoe-gia-dinh']`, `limit = 1` | `[Banner B]` (1 dòng khớp `target_value`) | `count(1) >= limit(1)` → **đủ** | **Bỏ qua** — không chạy | `[Banner B]` |
| Trang danh mục **"Du lịch gia đình"** (không có banner riêng) | `context = ['category_slug' => 'du-lich-gia-dinh']`, `limit = 1` | `[]` (không banner nào có `target_value = 'du-lich-gia-dinh'`) | `count(0) >= limit(1)` → **chưa đủ** | `remainingLimit = 1 - 0 = 1` → lấy 1 banner global mới nhất theo `sort_order` → `[Banner A]` | `[Banner A]` |
| `header_ad` (mọi trang, không truyền `context`) | `context = []`, `limit = 1` | Bỏ qua hẳn (không có `category_slug`) → `[]` | `count(0) >= limit(1)` → **chưa đủ** | `remainingLimit = 1` → `[Banner A]` (banner global duy nhất đang active cho `header_ad`, nếu có) | `[Banner A]` (hoặc rỗng nếu chưa có banner global nào cho `header_ad`) |

Chỉ khi `limit >= 2` mới thấy CẢ banner theo category lẫn banner global cùng xuất hiện 1 lúc (banner theo category luôn đứng trước) — với `limit=1` (giá trị mặc định ở hầu hết placement, §2) thì kết quả luôn là **hoặc** 1 banner theo category (nếu có khớp) **hoặc** 1 banner global (nếu không), không bao giờ trộn cả 2 trong cùng 1 kết quả 1 phần tử.

**Vì sao ưu tiên banner theo context trước global** (không phải ngược lại, không phải trộn ngẫu nhiên theo `sort_order` chung): banner gắn `target_type=category` thường là cam kết cụ thể hơn với 1 đối tác/chiến dịch cho ĐÚNG danh mục đó — nếu để global chen vào trước sẽ làm giảm cơ hội hiển thị của banner đã "trả tiền đúng chỗ". Global đóng vai trò **lấp chỗ trống** khi không có banner nào target đúng ngữ cảnh, không phải cạnh tranh ngang hàng.

**Không có ngữ cảnh** (`context` rỗng hoặc không truyền, ví dụ `header_ad`): bước 1 bị bỏ qua hoàn toàn (không có `category_slug` để so khớp), kết quả luôn chỉ là banner global — banner `target_type=category` sẽ **không bao giờ** xuất hiện ở những placement không có ngữ cảnh, kể cả khi admin lỡ tạo banner target category cho 1 placement kiểu đó (§4.2 đã nêu: form không chặn việc này, chỉ đơn giản là banner đó không có cơ hội hiển thị).

---

## 8. Kế hoạch triển khai (phases)

1. **Migration + Model + config/banner.php** — bảng `banners` (đã bao gồm `target_type`/`target_value` ngay từ migration đầu, §3.2 — module chưa ship v1.0 nên không cần 1 migration ALTER riêng cho v1.1), `Banner` model (kèm `forPlacement()` có context, §4.1), danh sách placement + target_types khởi tạo (§2/§4.2).
2. **`BannerPermissionSeeder`** + đăng ký vào `SystemDataSeeder` (theo đúng vị trí `EventDatabaseSeeder` — sau `AicemDatabaseSeeder`, trước `ApprovalDatabaseSeeder` nếu cần role platform đã tồn tại, hoặc sau `ApprovalDatabaseSeeder` nếu chỉ cần gán permission cho role có sẵn — xác nhận thứ tự khi implement).
3. **Admin CRUD** (`BannerAdminController`, `StoreBannerAction`/`UpdateBannerAction` tái dùng logic upload ảnh từ `StoreEventPosterAction`, `BannerData` — validate cả `target_type`/`target_value`, §5.1 — views danh sách/form kèm select target_type + category, §6.2).
4. **`RecordBannerClickAction` + `BannerClickController` + route** (§5.4).
5. **`<x-frontend.banner-slot>`** (kèm prop `context`, §7.1) + nhúng vào `header_ad` trước (thay chỗ đang tạm dùng config cũ), sau đó lần lượt các placement còn lại trong bảng §2 — 2 placement có targeting (`category_top`, `article_inline`) nhúng kèm `:context` ngay từ đầu, không tách thành 1 bước riêng sau này.
6. **(Tuỳ chọn) Demo seeder** — vài banner mẫu dùng ảnh khối màu tự sinh (không lấy từ nguồn ngoài), minh hoạ **cả 2 trường hợp**: ít nhất 1 banner global cho `header_ad`, và 1 cặp banner tại `category_top` gồm 1 global + 1 banner `target_type=category` gắn 1 category demo thật (đã seed sẵn qua `PostDemoSeeder`) — để khi xem thử thấy rõ hành vi ưu tiên/fallback (§7.5) chứ không chỉ thấy banner global đơn thuần. Chạy thủ công như `PostReviewDemoSeeder`, KHÔNG tự động trong `SystemDataSeeder` (tương tự lý do `OrganizationDemoSeeder` không tự chạy).

---

## 9. Ngoài phạm vi (out of scope) — ghi rõ để tránh hiểu nhầm khi review

- **Đo impression (lượt xem)** — chỉ đếm click ở v1 (§0).
- **Carousel/rotate tự động nhiều banner cùng vị trí** — hiển thị tĩnh theo `sort_order`, không tự xoay vòng bằng JS.
- **A/B testing banner** (2 ảnh cùng vị trí, chia % lượt xem) — không có trong v1.
- **Targeting theo trang cụ thể, thiết bị, khu vực, hoặc phân khúc người dùng** (v1.1 CHỈ hỗ trợ targeting theo **danh mục bài viết** — §7.5) — mọi banner khác vẫn hiển thị đồng nhất cho mọi khách truy cập bất kể thiết bị/vị trí địa lý.
- **Targeting theo `EventCategory`** — v1.1 chỉ đọc `PostCategory` qua `category_slug`; `event_list_top`/`event_show_top` hiện chỉ nhận banner global (ghi chú ở bảng §2).
- **Layout 2 cột (sidebar) cho trang chi tiết bài viết/sự kiện** — cần 1 spec riêng đổi layout trước khi có placement kiểu sidebar (§2).
- **Chèn `article_inline` linh hoạt theo vị trí admin tự chọn trong nội dung** — v1 cố định sau block đầu tiên (§7.3).
- **Quy trình duyệt banner** (submitted → approved → published) — không cần vì banner không nhận nộp từ công chúng (§0).
- **`target_value` dạng JSON** (nhiều điều kiện phức hợp cho 1 banner, vd vừa theo category vừa theo khoảng ngày tuần) — v1.1 mỗi banner chỉ 1 `target_type`/`target_value` đơn giản (§3.1/§4.1 model casts).
