# Module Trang Tĩnh (Page)
**Đặc tả Kỹ thuật Chi tiết — Sẵn sàng Triển khai**

**Phiên bản:** 1.3 — bổ sung: chốt chặn kỹ thuật `PublishPageAction` (không chỉ dựa kỷ luật vận hành), ghi chú hiệu năng `Route::fallback()`, làm rõ soft-delete trang thường (`is_system = false`) và tính chất tham khảo của `view_count`; kế thừa v1.2 (`reserved_slugs`, xoá trang hệ thống, đổi `template` sau publish, ranh giới tin cậy HTML từ Jodit, Acceptance Criteria §9)
**Ngày:** 21/07/2026
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions
**Module liên quan:** `Modules/Post` (WYSIWYG Jodit + upload ảnh, quy ước SEO field), `Modules/Menu` (điều hướng — nơi trang tĩnh được gắn link), `Modules/Banner` (mô hình "tài sản nền tảng" + permission Lớp B tham chiếu trực tiếp), module mới **`Modules/Page`**

---

## 0. Quyết định đã chốt

| Chủ đề | Hiện trạng codebase | Quyết định spec này | Lý do |
|---|---|---|---|
| **Phạm vi tenant** | `PostArticle`/`PostArticleTranslation` vẫn có `organization_id` (đa tổ chức), nhưng `MenuItem` và `Banner` — 2 module gần nhất về bản chất "tài sản site" — đã **bỏ hẳn** `organization_id`, không extend `TenantAwareModel` | `pages` **không có `organization_id`**, không extend `TenantAwareModel` — cùng mô hình Menu/Banner | Trang Giới thiệu/Liên hệ/Điều khoản là nội dung **của cả site** (1 công ty, 1 website công khai), không phải dữ liệu nghiệp vụ khác nhau theo từng tổ chức thuê bao. Đặt `organization_id` vào đây sẽ buộc mỗi tổ chức phải tự tạo lại các trang giống hệt nhau — không có nhu cầu thật |
| **Đa ngôn ngữ** | `Modules/Menu` **chủ động không làm i18n** (menu hiện tại — cũng như `PostCategory` — chỉ phục vụ 1 locale); nội dung thực tế toàn hệ thống chỉ tiếng Việt | **Không** làm bảng dịch riêng (`page_translations`) ở v1 — 1 cột `content` duy nhất | Theo đúng quyết định đã áp dụng cho Menu. Nếu sau này cần đa ngôn ngữ, đi theo đúng pattern `PostArticleTranslation` (bảng con `locale` + `slug` + SEO field riêng) — đây là điểm mở rộng đã có tiền lệ, không cần thiết kế mới |
| **Trang chủ (Home)** | Không tồn tại khái niệm "trang chủ = 1 bản ghi nội dung" — trang chủ là 1 layout ghép nhiều khối (hero, banner, bài viết mới...) | **Ngoài phạm vi.** Module Page chỉ quản lý các trang nội dung đơn giản (1 khối WYSIWYG), không quản lý trang chủ | Trang chủ là bố cục ghép nhiều nguồn dữ liệu (Post, Banner, Ocop...), không phải 1 bản ghi content tĩnh — nhét vào cùng model `Page` sẽ làm sai bản chất 2 khái niệm khác nhau |
| **Quy trình duyệt bài (editorial workflow)** | `Modules/Post` có state machine đầy đủ (`draft/pending/published/scheduled`, `approved_by`, `scheduled_at`) qua Publishing Engine | **Không** áp dụng — Page chỉ có 2 trạng thái: `draft` / `published`, xuất bản trực tiếp, không qua duyệt | Trang tĩnh do rất ít người biên tập (Admin/Marketing), thay đổi không thường xuyên — thêm quy trình duyệt là overhead không cần thiết. Nếu sau này cần duyệt, tích hợp `Modules/WorkflowAutomation`/`Modules/Approval` sẵn có — không tự dựng lại trong Page |
| **Xoá trang hệ thống** (Giới thiệu, Liên hệ, Điều khoản, Chính sách bảo mật — được seed sẵn) | Không có tiền lệ "bản ghi được seed nhưng cấm xoá" trong các module đã khảo sát | Thêm cột `is_system` — trang seed sẵn **không thể xoá** (chỉ được sửa nội dung/ẩn qua đổi `status`) | Đây là các trang bắt buộc phải tồn tại về mặt pháp lý/UX (điều khoản, chính sách) — xoá nhầm gây gãy link pháp lý và 404 trên footer |
| **Đường dẫn công khai** | Route wildcard 1 cấp (`/{slug}`) rủi ro nuốt mất mọi route khác nếu đăng ký sai thứ tự (gotcha đã ghi nhận ở routing Post/Menu). Laravel có sẵn `Route::fallback()` — chỉ chạy khi **không route nào khác trong toàn app khớp**, bất kể thứ tự đăng ký | **URL gốc, không tiền tố**: `abc.com/gioi-thieu`, `abc.com/lien-he`... qua `Route::fallback()` + kiểm tra slug trùng "reserved list" ngay khi tạo/sửa (§4.1) | `Route::fallback()` loại bỏ hoàn toàn rủi ro xung đột thứ tự route mà **không cần** hy sinh SEO/thương hiệu của URL đẹp — đây là cách các CMS thực tế (WordPress...) vẫn đặt trang tĩnh ở gốc domain. Bản v1.0 dùng tiền tố `/trang/` chỉ vì thận trọng thừa — không còn cần thiết |
| **Thiết kế riêng theo từng trang** | `Modules/Post` có tiền lệ **block-composer** (`post_content_blocks`: chuỗi block `text`/`product` theo `sort_order`, không phải 1 ô textarea) — nhưng hẹp, chỉ phục vụ bài viết | Thêm cột **`template`** trên `pages` (mặc định `default`). Trang thường (Điều khoản, Chính sách...) dùng `default` → render qua WYSIWYG như v1.0. Trang cần thiết kế riêng (Giới thiệu, Liên hệ...) chọn 1 template do **developer code sẵn** (Blade view riêng, có hero/section/thiết kế tuỳ ý) — Page vẫn giữ vai trò quản lý slug/SEO/status/menu chung cho mọi template | Quyết định theo giai đoạn (xem §11): v1 chọn "template picker" — chi phí thấp, đáp ứng ngay nhu cầu "Giới thiệu có thiết kế riêng" mà không cần dựng page-builder. Nếu sau này Marketing cần tự ghép bố cục không cần dev, tổng quát hoá `post_content_blocks` thành `page_blocks` (đã phác thảo ở §11, chưa triển khai) |
| **Gắn trang vào Menu** | `MenuLinkType` hiện chỉ có 3 case: `Category`, `Url`, `None` — không có case trỏ tới 1 bản ghi Page | V1: Admin gắn trang vào menu bằng cách chọn `link_type = Url` và dán URL công khai của trang (form Page hiển thị sẵn URL để copy). **Không** sửa `Modules/Menu` ở v1 | Tránh đụng chạm cross-module ngay từ bản đầu. Thêm `MenuLinkType::Page` + cột `page_id` là 1 thay đổi nhỏ, gói gọn thành 1 migration bổ sung ở Phase 5 (§8) sau khi Page đã chạy ổn định — không chặn việc ra mắt v1 |
| **Phân quyền** | `BANNER_MANAGE`/`OCOP_MANAGE` dùng **Lớp B** (seeder riêng gán cho role nền tảng `platform_ops`/`platform_content_head`, KHÔNG qua `config/permissions.php`); `MENU_MANAGE` dùng **Lớp A** (`config/permissions.php`, chỉ System_Admin) | Dùng **Lớp B**, giống hệt Banner/Ocop: seeder riêng `PagePermissionSeeder` gán `page.manage` cho `platform_ops` + `platform_content_head` | Page là "tài sản nền tảng" không tổ chức hoá — cùng bản chất Banner/Ocop (nội dung site dùng chung), khác Menu (Menu spec đánh giá là việc thuần System_Admin do là cấu trúc điều hướng, còn nội dung trang thường do Marketing/content team biên tập) |
| **Form liên hệ trên trang "Liên hệ"** | `Modules/Lead`, `Modules/LeadSource` đã tồn tại (CRM) | **Ngoài phạm vi.** Trang "Liên hệ" ở v1 chỉ là nội dung tĩnh (địa chỉ, SĐT, email, bản đồ nhúng qua HTML, hoặc do template riêng tự dựng form tĩnh) — không có form submit tạo Lead | Tích hợp form → Lead là 1 tính năng CRM riêng (chọn `LeadSource`, validate, chống spam...) xứng đáng 1 đặc tả riêng, không nên bị kéo vào theo module Page |
| **Lịch sử phiên bản nội dung** | `Modules/Post` có đặc tả riêng `Post_VersionHistory_Technical_Specification.md` cho việc này | **Ngoài phạm vi** ở v1 | Trang tĩnh sửa không thường xuyên, rủi ro thấp hơp bài viết biên tập hàng ngày — nếu phát sinh nhu cầu, áp dụng lại đúng cơ chế đã có ở Post thay vì thiết kế mới |

---

## 1. Giới thiệu & Mục tiêu

Hiện tại hệ thống chưa có nơi quản lý các trang nội dung tĩnh kiểu "Giới thiệu", "Liên hệ", "Điều khoản sử dụng", "Chính sách bảo mật" — những trang này thường **không thay đổi thường xuyên**, không thuộc luồng biên tập bài viết (`Modules/Post`), và không cần cấu trúc phân loại/tag. Nếu dựng chúng như bài viết (`PostArticle`) sẽ kéo theo bộ máy không cần thiết: danh mục, tag, quy trình duyệt, lịch xuất bản, đa ngôn ngữ theo tổ chức.

Module **Page** giải quyết đúng nhu cầu tối thiểu này: 1 bảng `pages` phẳng, không phân cấp, không danh mục — mỗi bản ghi là 1 trang nội dung độc lập, có SEO field cơ bản, ảnh đại diện qua Media Library, và render công khai tại URL gốc (`abc.com/gioi-thieu`, không tiền tố — §0, §4.1). Nội dung **không bắt buộc** phải qua WYSIWYG: trang thường (Điều khoản, Chính sách...) biên tập bằng Jodit như bài viết Post, nhưng trang cần thiết kế riêng (Giới thiệu, Liên hệ...) chọn 1 `template` do developer dựng sẵn — xem cơ chế "template picker" ở §0/§3/§11.

**Không đổi:** `Modules/Post`, `Modules/Menu`, `Modules/Banner` giữ nguyên hoàn toàn — Page là 1 module độc lập mới, chỉ **đọc** URL công khai của nó để dán thủ công vào Menu (§0), không sửa code của các module đó ở v1.

---

## 2. Kiến trúc dữ liệu

### 2.1 ERD

```
Page (phẳng, không phân cấp, không organization_id — tài sản nền tảng)
  ├─ uuid, slug (unique), title
  ├─ template (string — 'default' | tên template dev tự đăng ký, xem §3.2/§0)
  ├─ content (longtext — HTML từ Jodit; CHỈ dùng khi template = 'default')
  ├─ excerpt (nullable — dùng làm mô tả ngắn/fallback meta description)
  ├─ status ('draft' | 'published'), published_at
  ├─ is_system (boolean — trang seed sẵn, cấm xoá)
  ├─ seo_title, seo_description, seo_noindex (nullable/boolean)
  ├─ view_count
  ├─ sort_order (dự phòng cho 1 trang mục lục "sitemap HTML" nếu cần sau này — xem §10)
  ├─ created_by, updated_by, timestamps, soft delete
  └─ media collection `cover` (ảnh đại diện — og:image, qua Spatie Media Library, cùng convention PostArticle)
```

### 2.2 Migration

```php
Schema::create('pages', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();

    $table->string('slug', 160)->unique();
    $table->string('title', 200);
    $table->string('template', 60)->default('default'); // xem PageTemplate registry §3.2
    $table->longText('content')->nullable();      // cho phép rỗng khi status=draft hoặc template != 'default'
    $table->string('excerpt', 500)->nullable();

    $table->string('status', 20)->default('draft'); // PageStatus enum — xem §3.1
    $table->timestamp('published_at')->nullable();

    $table->boolean('is_system')->default(false);   // trang seed sẵn — xem §3.3

    $table->string('seo_title', 200)->nullable();
    $table->string('seo_description', 300)->nullable();
    $table->boolean('seo_noindex')->default(false);

    $table->unsignedBigInteger('view_count')->default(0);
    $table->unsignedSmallInteger('sort_order')->default(0);

    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['status', 'published_at'], 'idx_page_status_published');
});
```

Không cần cột `organization_id` (§0). Không cần bảng con `page_translations` ở v1 (§0). `slug` unique toàn cục (không `unique(['organization_id', 'slug'])` như Post) vì không có chiều tổ chức.

---

## 3. Model & Business rules

### 3.1 `PageStatus` (enum)

```php
enum PageStatus: string
{
    case Draft     = 'draft';
    case Published = 'published';
}
```

Không có `pending`/`scheduled` như `TranslationStatus` của Post (§0 — không có editorial workflow).

### 3.2 `Modules/Page/app/Models/Page.php`

```php
class Page extends Model implements HasMedia
{
    use SoftDeletes;
    use LogsActivity;
    use HasTenantMedia; // trait dùng chung, không bắt buộc model phải tenant-scoped (§0)

    protected $table = 'pages';

    protected $fillable = [
        'uuid', 'slug', 'title', 'template', 'content', 'excerpt',
        'status', 'published_at', 'is_system',
        'seo_title', 'seo_description', 'seo_noindex',
        'sort_order', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'status'        => PageStatus::class,
        'published_at'  => 'datetime',
        'is_system'     => 'boolean',
        'seo_noindex'   => 'boolean',
        'view_count'    => 'integer',
        'sort_order'    => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug'; // URL công khai dùng slug, KHÔNG dùng uuid (khác Banner) — cần đường dẫn thân thiện SEO
    }

    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', PageStatus::Published)->whereNotNull('published_at');
    }

    /** Tiêu đề <title>/og:title — fallback về title khi seo_title trống. */
    public function metaTitle(): string
    {
        return $this->seo_title ?: $this->title;
    }

    /** Mô tả <meta description>/og:description — fallback về excerpt khi seo_description trống. */
    public function metaDescription(): ?string
    {
        return $this->seo_description ?: $this->excerpt;
    }

    /** Blade view thực render trang này — xem PageTemplate registry ngay dưới. */
    public function resolveView(): string
    {
        return PageTemplate::viewFor($this->template);
    }
}
```

Ảnh đại diện dùng collection Media **`cover`** — cùng tên collection quy ước đã dùng cho nội dung có ảnh đại diện/og:image (xem `spec/Media_Library_Technical_Specification.md`), không tạo collection riêng như Banner (Banner tách riêng vì banner không ép tỷ lệ khung hình — Page không có ràng buộc đó).

### 3.2.1 `PageTemplate` — registry template do developer khai báo (không phải bảng DB)

Cột `template` chỉ lưu 1 **khoá chuỗi** (`'default'`, `'about'`, `'contact'`...) — việc "khoá nào tồn tại, khoá nào render ra view nào" do **developer khai báo trong code**, không phải dữ liệu Admin nhập tự do (Admin chỉ **chọn** từ dropdown các khoá đã có sẵn, không tự gõ tên template — tránh trỏ tới 1 view không tồn tại rồi vỡ trang production):

```php
// Modules/Page/app/Features/PageManagement/PageTemplate.php
final class PageTemplate
{
    /**
     * key => ['label' => nhãn hiển thị dropdown admin, 'view' => tên Blade view].
     * Thêm 1 dòng ở đây + tạo view tương ứng khi cần 1 trang thiết kế riêng mới —
     * KHÔNG cần sửa Model/Controller/route.
     */
    private const MAP = [
        'default' => ['label' => 'Mặc định (nội dung WYSIWYG)', 'view' => 'page::public.show'],
        'about'   => ['label' => 'Giới thiệu (thiết kế riêng)',  'view' => 'page::public.templates.about'],
        'contact' => ['label' => 'Liên hệ (thiết kế riêng)',     'view' => 'page::public.templates.contact'],
    ];

    public static function viewFor(string $key): string
    {
        return self::MAP[$key]['view'] ?? self::MAP['default']['view'];
    }

    /** @return array<string, string> key => label, dùng đổ vào <select> form admin (§4.2). */
    public static function options(): array
    {
        return array_map(fn ($t) => $t['label'], self::MAP);
    }
}
```

Mỗi view template riêng (`page::public.templates.about`, `...contact`) là 1 file Blade **do developer code tay** — toàn quyền bố cục/hero/section/CSS, không bị ép qua khối `{!! $page->content !!}`. `Page` model vẫn cung cấp thống nhất `slug`, `metaTitle()`, `metaDescription()`, `status`, media `cover`... cho mọi template — các view riêng này **có thể bỏ qua** field `content`/`excerpt` nếu thiết kế không cần, hoặc dùng chúng cho 1 phần nhỏ (vd đoạn giới thiệu ngắn) — tuỳ ý.

### 3.3 Validate & business rules

| Rule | Lý do |
|---|---|
| `title` bắt buộc, max 200 | |
| `slug` bắt buộc, max 160, unique toàn cục, chỉ chữ thường/số/gạch ngang (`Str::slug()`), tự sinh từ `title` khi tạo mới nếu để trống | Cùng convention Post/Menu — auto-slug ở JS form, Action vẫn validate lại làm chốt chặn cuối |
| `template` bắt buộc phải là 1 khoá đã đăng ký trong `PageTemplate::MAP` (`Rule::in(array_keys(...))`), Admin chỉ chọn từ dropdown, không tự nhập chuỗi | Chặn việc trỏ tới 1 view Blade không tồn tại → lỗi 500 khi render public |
| `content` bắt buộc **khi chuyển `status = published` VÀ `template = 'default'`**, cho phép rỗng ở mọi trường hợp khác (draft, hoặc template riêng tự quản lý nội dung trong view) | Trang dùng template riêng không phụ thuộc cột `content` để có nội dung hiển thị |
| **`PublishPageAction` kiểm tra `View::exists($page->resolveView())` trước khi cho chuyển `status → published`** — nếu view chưa tồn tại (vd `template = about` nhưng `page::public.templates.about` chưa được dev tạo), **từ chối** publish, ném `ValidationException` với thông báo rõ "Chưa thể xuất bản: giao diện cho template này chưa sẵn sàng, liên hệ đội kỹ thuật" | Đây là **chốt chặn kỹ thuật**, không chỉ dựa vào kỷ luật vận hành (§6, §8 — Phase 2 seed trước, Phase 5a mới có view). Không kiểm tra khi `status` vẫn là `draft` (được lưu nháp thoải mái dù view chưa có) — chỉ chặn đúng ở bước chuyển sang `published`, vì đó là thời điểm duy nhất view thực sự cần tồn tại |
| **Không cho xoá khi `is_system = true`** — chặn **cả soft-delete lẫn hard-delete** (`DeletePageAction` throw `ValidationException` trước khi gọi `$page->delete()`, không riêng gì `forceDelete()`) | Bảo vệ các trang bắt buộc về pháp lý/UX (Điều khoản, Chính sách bảo mật...) khỏi bị xoá nhầm. Trang hệ thống chỉ có 2 cách "ẩn": chuyển `status = draft` (không hiện public, admin vẫn xem/sửa được trong danh sách — có thể publish lại bất kỳ lúc nào), hoặc bật `seo_noindex` (vẫn truy cập được nhưng không lên Google) — **không có khái niệm "xoá mềm rồi khôi phục"** cho riêng `is_system`, vì nếu cho soft-delete thì trang biến mất khỏi cả danh sách quản trị lẫn public cùng lúc, không có nút "khôi phục" rõ ràng ở UI hiện tại (list trang không có view "đã xoá") — dễ gây hiểu lầm là mất hẳn |
| **Soft-delete trang thường** (`is_system = false`): `DeletePageAction` gọi `$page->delete()` bình thường. Danh sách quản trị (`dashboard/pages`, `PageAdminController@index`) dùng query mặc định `Page::query()->...` — **không** `withTrashed()` — nên trang đã xoá mềm **biến mất khỏi cả 2 nơi cùng lúc**: danh sách quản trị lẫn URL công khai (route công khai vốn cũng không thấy bản ghi đã xoá mềm, do Eloquent tự loại `deleted_at` khỏi mọi query mặc định, kể cả bên trong scope `published()`) | V1 **không có UI khôi phục** (không có tab "Thùng rác"/nút Restore) — nhất quán với cách các module khác (Menu, Banner) đang xử lý soft-delete: xoá xong là "biến mất" khỏi thao tác thường ngày, chỉ khôi phục được qua `php artisan tinker` (`Page::withTrashed()->find($id)->restore()`) nếu thật sự cần, không phải 1 tính năng UI chính thức |
| Đổi `slug` của 1 trang đã `published` **được phép nhưng cảnh báo UI**: "Đổi đường dẫn sẽ khiến các liên kết cũ (menu, mạng xã hội, backlink) trỏ tới URL cũ bị lỗi 404" | V1 không có bảng redirect 301 tự động (xem §10 — ngoài phạm vi); cảnh báo là đủ cho tần suất sửa thấp của loại nội dung này |
| `published_at` tự set = `now()` khi Action chuyển `status` từ `draft` → `published` lần đầu (không cho client tự gửi giá trị) | Cùng nguyên tắc `depth` tự tính ở MenuItem — server tính, không tin client |
| Đổi `template` của 1 trang **đang `published`** — **cho phép nhưng cảnh báo UI** khi giá trị mới khác giá trị cũ: "Đổi thiết kế trang sẽ thay đổi cách hiển thị ngay khi lưu. Nếu đổi từ 'Mặc định' sang 1 thiết kế riêng, nội dung ở khối WYSIWYG bên dưới **sẽ không còn hiển thị** (trừ khi template đó có chủ động dùng lại `content`/`excerpt`, xem §3.2.1)." Không lưu lại "lịch sử template cũ" — nếu Admin đổi nhầm, chỉ cần đổi lại `template` như cũ, `content` cũ vẫn còn nguyên trong DB (chỉ ngừng hiển thị, không bị xoá) | Đổi `default` ↔ 1 template riêng đổi hẳn bản chất trang (từ "nội dung do Admin viết" sang "bố cục do dev code") — cảnh báo ngay lúc đổi tránh Admin ngỡ ngàng khi thấy trang public đổi giao diện đột ngột. Không chặn cứng (không bắt buộc phải về `draft` mới đổi được) vì tần suất đổi rất thấp và Admin là người có `page.manage` — đủ tin cậy để tự quyết, cảnh báo là đủ, giống cách xử lý đổi `slug` ở dòng trên |

### 3.4 Giới hạn đã biết ở v1 (accepted limitations)

- **`view_count` không chống bot/tăng ảo** — mỗi lượt `GET` vào trang `published` đều `increment('view_count')` vô điều kiện (§5), không có session/cookie/IP throttle để loại F5 liên tục hay crawler. Với trang tĩnh (Điều khoản, Chính sách...) con số này **chỉ mang tính tham khảo thô, không phải số liệu đáng tin cậy để đưa vào báo cáo chính thức hay dùng làm căn cứ ra quyết định kinh doanh** — nếu cần số liệu traffic thật (unique visitor, nguồn truy cập...), phải dùng công cụ phân tích riêng (GA, Meilisearch analytics...), không lấy từ cột này. Chấp nhận ở v1 vì trang tĩnh không phải KPI theo dõi chính (khác `PostArticle.view_count` vốn có ý nghĩa traffic content thật). Nếu sau này cần số liệu đáng tin cậy hơn: thêm check cookie "đã đếm trong 24h" (nhẹ, đủ dùng) hoặc **bỏ hẳn cột này** nếu xác nhận không ai xem báo cáo — không cần giải pháp phức tạp kiểu phân tích IP/user-agent.

---

## 4. Admin CRUD (`Modules/Page`)

Tổ chức theo Feature-folder, cùng pattern `Modules/Menu/app/Features/MenuManagement`:

```
Modules/Page/
  app/
    Models/Page.php
    Enums/PageStatus.php
    Features/PageManagement/
      Http/PageAdminController.php
      Actions/CreatePageAction.php
      Actions/UpdatePageAction.php
      Actions/DeletePageAction.php
      Actions/PublishPageAction.php     // chuyển draft → published, set published_at
      Actions/UnpublishPageAction.php   // chuyển published → draft
      Data/PageData.php
    Features/PublicReading/
      Http/PagePublicController.php
    Policies/PagePolicy.php
    Providers/PageServiceProvider.php
  database/
    migrations/2026_..._create_pages_table.php
    seeders/PagePermissionSeeder.php
    seeders/PageDatabaseSeeder.php       // seed 4 trang mặc định — xem §6
  routes/web.php
```

### 4.1 Routes

```php
// Quản trị
Route::middleware(['auth'])->prefix('dashboard/pages')->name('backend.page.')->group(function (): void {
    Route::resource('items', PageAdminController::class)->except(['show'])->parameters(['items' => 'page']);
    Route::patch('items/{page}/publish', [PageAdminController::class, 'publish'])->name('items.publish');
    Route::patch('items/{page}/unpublish', [PageAdminController::class, 'unpublish'])->name('items.unpublish');
});

// Công khai — URL gốc (abc.com/gioi-thieu), KHÔNG tiền tố. Route::fallback() chỉ chạy
// khi KHÔNG route nào khác trong toàn app khớp — an toàn tuyệt đối trước thứ tự đăng ký
// route giữa các module, không cần Route::get('/{slug}', ...) đặt cuối cùng thủ công.
Route::fallback(function (Request $request) {
    if ($request->method() !== 'GET') {
        abort(404);
    }

    $slug = trim($request->path(), '/');
    $page = Page::published()->where('slug', $slug)->first();

    abort_unless($page, 404);

    return app(PagePublicController::class)($page);
})->name('page.public.fallback');
```

**Chặn trùng ở nguồn, không chỉ trông chờ vào fallback**: `CreatePageAction`/`UpdatePageAction` validate `slug` **không được trùng** với danh sách slug gốc (1 segment) đã dùng bởi route khác — duy trì tại `config('page.reserved_slugs')` (vd `login`, `register`, `dashboard`, `api`, `home`, `email`, `profile`, `me`, `notifications`, `auth`, `storage`, `up`...). Danh sách này lấy từ `php artisan route:list --method=GET` (lọc route 1-segment ở gốc, ngoài `dashboard/*`, `api/*`) tại thời điểm cài đặt. 2 lớp (validate khi nhập + `Route::fallback()` chỉ chạy sau cùng) độc lập nhau, giống nguyên tắc UI-filter + Action-validate đã áp dụng ở Menu: thiếu validate khi nhập thì Admin tạo xong mới biết bị 404 vĩnh viễn (route thật luôn thắng fallback); thiếu `Route::fallback()` thì không có cách nào render URL gốc.

**Hiệu năng của `Route::fallback()`**: mỗi request GET không khớp bất kỳ route nào khác (không chỉ riêng request tới trang tĩnh — kể cả 1 URL sai chính tả/404 thật) đều chạy `Page::published()->where('slug', $slug)->first()` — 1 query có index (`idx_page_status_published`, §2.2), nên với traffic thấp/vừa (đúng quy mô nội dung ít thay đổi của trang tĩnh) là đủ nhanh, không cần tối ưu thêm ở v1. Nếu sau này traffic cao (nhiều bot/crawler dội 404 liên tục) khiến việc này tốn tài nguyên đáng kể, 2 hướng cải thiện khi cần: (a) cache danh sách `slug → page` đang `published` vào Redis (TTL ngắn, invalidate khi Action publish/unpublish/update), hoặc (b) bỏ `Route::fallback()`, quay lại đăng ký tường minh từng slug đã publish thành 1 route riêng (sinh động tại boot, hoặc cache route). Ghi nhận là điểm cần theo dõi, **không làm trước khi có tín hiệu traffic thật** đòi hỏi.

#### 4.1.1 Quy trình duy trì `config('page.reserved_slugs')`

Danh sách này **sẽ lệch dần** với route thật nếu không có quy trình rõ ràng — chốt như sau:

- **Trách nhiệm cập nhật**: quy tắc thuộc về người thêm 1 route GET mới ở **gốc domain** (không nằm dưới `dashboard/*`, `api/*` — 2 prefix này không bao giờ va với Page nên không cần khai báo). Ghi 1 dòng comment ở đầu `routes/web.php` và đầu mỗi `routes/web.php` của module có route công khai ở gốc: *"Thêm route GET mới ở gốc? Cập nhật `config/page.php:reserved_slugs`."* — đây là dòng nhắc tại điểm dễ quên nhất, không trông chờ ai nhớ tự giác.
- **Không chỉ review thủ công — bắt buộc có 1 test tự động** `PageReservedSlugsTest` chạy `Route::getRoutes()` lúc test, lọc ra mọi route GET 1-segment ở gốc đang tồn tại thật, rồi assert **tập hợp con** của `config('page.reserved_slugs')` (không cần bằng tuyệt đối — thừa 1 vài slug dự phòng chưa dùng là vô hại, nhưng **thiếu** 1 slug đang thật sự tồn tại route thì test phải fail). Test này chạy trong CI — nếu ai thêm route gốc mới mà quên cập nhật config, CI đỏ ngay thay vì âm thầm để 1 Admin tạo trang trùng tên rồi phát hiện lúc 404 trên production.

### 4.2 Giao diện quản trị

- **Trang danh sách** (`dashboard/pages`): bảng phẳng (không cây — Page không phân cấp), cột: tiêu đề, slug, badge trạng thái (Nháp/Đã xuất bản), badge "Hệ thống" nếu `is_system`, ngày cập nhật, thao tác (Sửa; nút Xoá **ẩn/disable** nếu `is_system = true`, kèm tooltip "Trang hệ thống — không thể xoá").
- **Form tạo/sửa**: `title`, `slug` (auto-fill từ title qua JS, cho sửa tay), **`template`** (select đổ từ `PageTemplate::options()` — khi chọn khác `default`, ẩn/thu gọn khối `content` kèm ghi chú "Trang này dùng thiết kế riêng do lập trình viên dựng sẵn — nội dung bên dưới có thể không hiển thị trực tiếp"), `excerpt` (textarea ngắn), `content` (Jodit — tái dùng đúng cấu hình + endpoint upload ảnh inline `MediaJoditUploadController` đã có ở Post; chỉ hiển thị đầy đủ/bắt buộc khi `template = default`), ảnh đại diện (component upload Media Library chuẩn — collection `cover`, xem `spec/Media_Library_Technical_Specification.md`), khối SEO thu gọn (`seo_title`, `seo_description`, checkbox `seo_noindex`), trạng thái (select Nháp/Xuất bản — đổi qua action `publish`/`unpublish` riêng, không sửa trực tiếp cột `status` qua update thường, để đảm bảo `published_at` luôn được set đúng lúc).
- Sau khi lưu, form hiển thị **URL công khai đầy đủ** (`https://abc.com/{slug}`) kèm nút "Sao chép" — phục vụ đúng thao tác thủ công dán vào Menu (§0).

### 4.3 Policy

`PagePolicy` dùng permission `page.manage` cho mọi hành động quản trị (create/update/delete/publish) — không tách granular như Post (Post tách vì có quy trình duyệt nhiều vai trò; Page chỉ 1 permission vì không có bước duyệt riêng, cùng mức độ đơn giản như `MENU_MANAGE`/`BANNER_MANAGE`).

**Ranh giới tin cậy nội dung**: `page.manage` là cổng duy nhất được phép nhập/sửa cột `content` (qua Jodit) — chỉ role nền tảng (`platform_ops`, `platform_content_head`, `super-admin`) có quyền này (§7), không mở cho vai trò tổ chức nào khác. Đây là tiền đề bắt buộc cho quyết định ở §5 (render `{!! $page->content !!}` không qua sanitize thêm) — nếu sau này permission `page.manage` bị cấp rộng hơn (vd cho vai trò tổ chức tự quản), phải bổ sung sanitize (`HTMLPurifier` hoặc tương đương) ở tầng lưu/render trước khi mở rộng, không được giữ nguyên giả định "chỉ nội bộ mới nhập được".

---

## 5. Render công khai

`PagePublicController` (single-action, `__invoke(Page $page)` qua route-model-binding `slug`):

```php
public function __invoke(Page $page)
{
    abort_unless($page->status === PageStatus::Published, 404);

    $page->increment('view_count');

    return view($page->resolveView(), ['page' => $page]);
}
```

View được chọn động qua `$page->resolveView()` (§3.2.1) — không hard-code `page::public.show`. Mọi template (kể cả template riêng) đều nên `@extends('layouts.frontend')` và set `@section('title', $page->metaTitle())`, `@section('meta_description', $page->metaDescription())`, `<meta name="robots" content="noindex">` khi `seo_noindex = true`, `og:image` từ `$page->getFirstMediaUrl('cover')` nếu có — phần SEO/meta này **thống nhất cho mọi template**, chỉ phần thân trang là khác nhau:

- `page::public.show` (template `default`): render `{!! $page->content !!}` (HTML đã qua Jodit — tin cậy từ người biên tập nội bộ, cùng mức tin cậy với `PostArticle->content`).
- `page::public.templates.about`, `...contact` (template riêng): developer tự viết markup/section/CSS, không bị ép qua `content`.

**Lưu ý bảo mật (để người review không thắc mắc khi thấy `{!! !!}`)**: `content` **không qua bước sanitize/whitelist HTML nào ở tầng lưu hay tầng render** — giống hệt cách `PostArticle->content` đang được render hiện tại trong `Modules/Post`. Điều này chấp nhận được **chỉ vì** người có thể ghi vào cột này bị giới hạn ở permission `page.manage` (§4.3) — tức chỉ nhân sự nội bộ được cấp quyền quản trị nền tảng, không phải input từ người dùng công khai hay vai trò tổ chức thông thường. Đây là 1 quyết định nhất quán trong toàn hệ thống (Post cũng vậy), không phải khoảng hở riêng của module Page.

---

## 6. Seed dữ liệu mặc định

`PageDatabaseSeeder` tạo 4 trang khởi tạo với `is_system = true`, `status = draft` (nội dung placeholder — Admin phải tự điền và xuất bản, seeder **không** tự publish để tránh trang rỗng lên public trước khi có nội dung thật):

| title | slug | template |
|---|---|---|
| Giới thiệu | `gioi-thieu` | `about` — cần thiết kế riêng (dev dựng `page::public.templates.about` trước khi publish) |
| Liên hệ | `lien-he` | `contact` — cần thiết kế riêng (dev dựng `page::public.templates.contact` trước khi publish) |
| Điều khoản sử dụng | `dieu-khoan-su-dung` | `default` — nội dung thuần văn bản, WYSIWYG là đủ |
| Chính sách bảo mật | `chinh-sach-bao-mat` | `default` — nội dung thuần văn bản, WYSIWYG là đủ |

Idempotent: dùng `Page::firstOrCreate(['slug' => ...], [...])` — chạy lại seeder không tạo trùng. **Lưu ý triển khai**: seeder chạy ở Phase 2 nhưng view `templates.about`/`templates.contact` chỉ có ở Phase 5a (§8) — trước đó 2 trang này ở trạng thái `draft` nên không bị lỗi (route chỉ render trang `published`). Nếu ai đó cố publish "Giới thiệu"/"Liên hệ" trước khi view template tương ứng tồn tại, `PublishPageAction` **tự chặn** (kiểm tra `View::exists()`, §3.3) — đây là chốt chặn kỹ thuật, không chỉ là 1 lưu ý quy trình để người vận hành tự nhớ.

---

## 7. Phân quyền

Thêm vào `app/Enums/PermissionEnum.php`:

```php
// ══ PAGE (Trang tĩnh — Giới thiệu/Liên hệ/Điều khoản..., tài sản nền tảng) ═══
// Modules/Page/database/seeders/PagePermissionSeeder.php — gán cho platform_ops +
// platform_content_head, KHÔNG qua config/permissions.php (Lớp B) — cùng nguyên tắc
// BANNER_MANAGE/OCOP_MANAGE (§0).
case PAGE_MANAGE = 'page.manage';
```

`Modules/Page/database/seeders/PagePermissionSeeder.php` — sao chép nguyên mẫu `BannerPermissionSeeder`:

```php
class PagePermissionSeeder extends Seeder
{
    private const PERMISSIONS = ['page.manage'];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        Role::where('name', 'platform_ops')->where('guard_name', 'web')->first()
            ?->givePermissionTo('page.manage');
        Role::where('name', 'platform_content_head')->where('guard_name', 'web')->first()
            ?->givePermissionTo('page.manage');

        Role::where('name', 'super-admin')->where('guard_name', 'web')->first()
            ?->syncPermissions(Permission::all());

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
```

Sidebar quản trị (`resources/views/layouts/partials/sidebar.blade.php`) thêm 1 mục "Trang tĩnh" gate bằng `@can('page.manage')`, cùng cách các mục Banner/Ocop hiện có.

---

## 8. Kế hoạch triển khai (phases)

| Phase | Nội dung | Rủi ro |
|---|---|---|
| 1 | Migration (gồm cột `template`) + Model + `PageTemplate` registry + Enum + Policy + Admin CRUD + `PagePermissionSeeder` | Không — chưa có gì công khai |
| 2 | `PageDatabaseSeeder` (4 trang mặc định, `draft` — xem lưu ý ở §6 về thứ tự publish) | Không |
| 3 | Route `Route::fallback()` + `PagePublicController` + view mặc định `page::public.show` + reserved-slug config (§4.1) | Thấp — `Route::fallback()` chỉ kích hoạt khi không route nào khác khớp, không đụng route cũ |
| 4 | Admin điền nội dung thật cho "Điều khoản sử dụng"/"Chính sách bảo mật" (template `default`), publish qua UI | Không (thao tác nghiệp vụ) |
| 5a | Developer dựng view `page::public.templates.about` + `...contact` (thiết kế riêng), sau đó Admin publish "Giới thiệu"/"Liên hệ" | Thấp — chỉ thêm view mới, không đổi route/model |
| 5b (tuỳ chọn, sau khi ổn định) | Gắn `MenuLinkType::Page` + `page_id` vào `Modules/Menu` để chọn trang trực tiếp trong dropdown thay vì dán URL tay (§0) | Trung bình — có đụng migration/model của Menu, cần spec bổ sung riêng |
| 6 (tương lai, chưa lên lịch) | `page_blocks` — tổng quát hoá `post_content_blocks` cho phép Admin/Marketing tự ghép bố cục không cần dev, xem §11 | Cao hơn hẳn — cần thiết kế lại từ đầu, chỉ làm khi thực tế phát sinh nhu cầu Marketing tự soạn trang không qua dev |

---

## 9. Acceptance Criteria

1. Tạo 1 trang mới với `template = default`, `content` có nội dung, `status = published` → truy cập đúng URL gốc (`abc.com/{slug}`) trả về 200, hiển thị đúng nội dung `content` đã nhập, `<title>` = `metaTitle()`, `view_count` tăng thêm 1 sau mỗi lần tải (số này chỉ mang tính tham khảo, không dùng cho báo cáo chính thức — §3.4).
2. Trang `status = draft` → truy cập URL công khai trả về **404** (không lộ nội dung nháp), nhưng vẫn hiển thị/sửa được bình thường trong `dashboard/pages`.
3. Tạo/sửa 1 trang với `slug` trùng 1 giá trị trong `config('page.reserved_slugs')` → bị chặn ngay ở validate (form báo lỗi rõ ràng "Đường dẫn này đã được hệ thống sử dụng"), **không** đợi tới lúc publish mới phát hiện xung đột.
4. `Route::fallback()` **không** nuốt mất route thật của module khác: gọi 1 route đã tồn tại (vd `/dashboard`, `/login`, `/notifications`) vẫn trả về đúng response của route đó, không bị `PagePublicController` chặn trước — kiểm chứng bằng test tạo 1 `Page` có `slug` cố ý trùng 1 route thật (bỏ qua bước validate reserved-slug bằng cách seed thẳng DB) và xác nhận route thật vẫn thắng.
5. `DeletePageAction` từ chối cả xoá cứng lẫn xoá mềm khi `is_system = true` (ném `ValidationException`, không có bản ghi nào bị đổi `deleted_at`). Cùng action gọi trên `is_system = false` xoá mềm thành công (`deleted_at` được set) — sau đó trang **biến mất đồng thời** khỏi cả danh sách `dashboard/pages` (query mặc định, không `withTrashed()`) lẫn URL công khai (404); không có UI khôi phục, chỉ khôi phục được qua `Page::withTrashed()->find($id)->restore()` ở tinker (§3.3).
6. Trang có `template = about` (hoặc bất kỳ khoá khác `default` đã đăng ký trong `PageTemplate::MAP`) → render đúng view riêng (`page::public.templates.about`), **không** render qua `page::public.show`/không hiển thị khối `content` mặc định.
7. Đặt `template` thành 1 chuỗi **không** có trong `PageTemplate::MAP` (giả lập request thủ công, bỏ qua UI) → bị chặn ở validate Action (`Rule::in`), không lưu được, không có khả năng gây lỗi 500 ở tầng render do view không tồn tại.
8. Gọi `PublishPageAction` trên 1 trang có `template` đã đăng ký nhưng **view Blade tương ứng chưa tồn tại** trên đĩa (vd `page::public.templates.about` chưa được tạo) → bị từ chối, ném `ValidationException`, `status` **không** đổi thành `published` (§3.3) — không dựa vào việc "Admin nhớ không được publish sớm".
9. `page.manage` được gán đúng cho `platform_ops` + `platform_content_head` qua `PagePermissionSeeder` (verify qua test hoặc `php artisan tinker`); user không có quyền này bị chặn ở mọi route `dashboard/pages/*` (403).
10. Seed 4 trang mặc định (§6) chạy lại `PageDatabaseSeeder` nhiều lần không tạo trùng bản ghi (idempotent qua `firstOrCreate`).

---

## 10. Ngoài phạm vi (out of scope)

- **Trang chủ (homepage)** — là layout ghép nhiều khối dữ liệu, không phải 1 bản ghi `Page`.
- **Đa ngôn ngữ** cho nội dung trang tĩnh — theo đúng quyết định hiện tại của Menu; nếu cần, áp dụng lại pattern `PostArticleTranslation`.
- **Form liên hệ / thu thập Lead** trên trang "Liên hệ" — cần đặc tả riêng tích hợp `Modules/Lead`/`Modules/LeadSource`. Template `contact` ở v1 có thể chứa 1 form tĩnh (không submit, hoặc submit `mailto:`/link ngoài) nếu cần, nhưng không tạo `Lead` trong DB.
- **Quy trình duyệt nội dung** (draft → pending → approved) — dùng thẳng `Modules/WorkflowAutomation`/`Modules/Approval` nếu phát sinh nhu cầu, không tự dựng trong Page.
- **Redirect 301 tự động** khi đổi `slug` — v1 chỉ cảnh báo UI (§3.3), không lưu bảng ánh xạ slug cũ → slug mới.
- **Lịch sử phiên bản nội dung** (revision history) — xem `Post_VersionHistory_Technical_Specification.md` làm tiền lệ nếu cần mở rộng sau.
- **Sitemap.xml tự động khai báo trang tĩnh** — nếu hệ thống đã/sẽ có cơ chế sinh sitemap chung, bổ sung `pages` (`status=published`, `seo_noindex=false`) vào nguồn dữ liệu của cơ chế đó ở 1 thay đổi nhỏ riêng, không thiết kế sitemap mới trong spec này.
- **Trang mục lục liệt kê tất cả trang tĩnh** (index dạng `/pages`) — trang tĩnh được truy cập qua Menu/footer, không cần 1 trang danh sách duyệt công khai riêng.
- **`page_blocks` (page-builder cho Admin/Marketing tự ghép bố cục)** — cố ý để dành cho phase sau (Phase 6, §8), xem định hướng thiết kế sơ bộ ở §11. Không triển khai ở v1 vì "template picker" (§0, §3.2.1) đã đáp ứng đủ nhu cầu hiện tại (Giới thiệu/Liên hệ có thiết kế riêng, do dev code sẵn).
- **Chống bot/tăng ảo cho `view_count`** — ghi nhận là giới hạn đã biết (§3.4), không phải lỗi.
- **Sanitize HTML bổ sung cho `content`** — v1 dựa hoàn toàn vào việc giới hạn quyền ghi qua `page.manage` (§4.3, §5); chỉ cần làm nếu phạm vi cấp quyền mở rộng.

---

## 11. Định hướng mở rộng (chưa triển khai): `page_blocks`

Ghi lại ở đây theo đúng quyết định "làm theo giai đoạn" (§0) — để phase sau không phải thiết kế lại từ đầu, nhưng **không code ở v1**.

**Vấn đề của "template picker" (§3.2.1)**: mỗi template mới đều cần developer — phù hợp khi số trang thiết kế riêng ít (Giới thiệu, Liên hệ), nhưng không mở rộng tốt nếu Marketing cần tự tạo nhiều trang landing có bố cục khác nhau (vd trang chiến dịch, trang sự kiện đặc biệt) mà không có dev hỗ trợ kịp thời.

**Hướng giải quyết (phác thảo, chưa chốt thiết kế)**: tổng quát hoá đúng ý tưởng đã chứng minh hiệu quả ở `post_content_blocks` (xem "Module liên quan" đầu tài liệu — chuỗi block theo `sort_order`, mỗi block có `type` riêng) thành 1 bảng `page_blocks`:

```
page_blocks
  ├─ page_id (FK pages, cascade delete)
  ├─ type ('hero' | 'text' | 'stats' | 'team' | 'gallery' | 'cta' | ...)
  ├─ sort_order
  ├─ data (json — cấu trúc khác nhau tuỳ `type`, mỗi type có Blade partial riêng render từ `data`)
  └─ timestamps
```

- `template = 'blocks'` (thêm 1 khoá mới vào `PageTemplate::MAP`) → view chung `page::public.templates.blocks` lặp qua `$page->blocks` theo `sort_order`, mỗi block `@include("page::blocks.{$block->type}", ['data' => $block->data])`.
- Admin có giao diện thêm/xoá/sắp xếp (kéo-thả) block, mỗi loại block có 1 form cấu hình riêng tương ứng cấu trúc `data` của nó (giống cách `post_product_blocks` có form riêng cho block loại `product`).
- Đây là 1 page-builder thật (không phải chỉnh sửa toàn văn tự do) — Marketing bị giới hạn trong các loại block **đã được dev định nghĩa trước**, không tự do kéo-thả pixel như Elementor/Webflow — giữ đúng tinh thần "đơn giản, không dựng lại cả 1 hệ page-builder tổng quát" của codebase.

**Không làm ở v1 vì**: chưa có bằng chứng nhu cầu thực tế vượt quá 2 template ban đầu (Giới thiệu, Liên hệ) + các trang nội dung thuần (Điều khoản, Chính sách) — xây `page_blocks` trước khi có nhu cầu cụ thể là đầu tư sai chỗ. Khi nhu cầu xuất hiện (vd Marketing xin thêm trang landing thứ 4-5 mà mỗi lần đều phải chờ dev), đây là lúc revisit mục này.

---

## Appendix A — Acceptance Criteria (English mirror of §9)

> Non-canonical translation snapshot, kept for stakeholders/tooling that need English. **§9 (Vietnamese) is the source of truth** — if this appendix and §9 ever disagree after an edit, §9 wins; update this block whenever §9 changes, or delete it if it goes stale rather than leaving it wrong.

1. Creating a new page with `template = default`, non-empty `content`, `status = published` → visiting the public root URL (`abc.com/{slug}`) returns 200, renders the entered `content` exactly, `<title>` equals `metaTitle()`, and `view_count` increments by 1 per page load (this counter is advisory only, not for official reporting — §3.4).
2. A page with `status = draft` → the public URL returns **404** (draft content is never exposed), while it remains visible/editable as normal in `dashboard/pages`.
3. Creating/editing a page with a `slug` that collides with a value in `config('page.reserved_slugs')` → rejected immediately at validation (clear form error, e.g. "This path is already used by the system"), not discovered only after publishing.
4. `Route::fallback()` never swallows another module's real route: hitting an existing route (e.g. `/dashboard`, `/login`, `/notifications`) still returns that route's own response, not intercepted by `PagePublicController` — verified by a test that seeds a `Page` whose `slug` intentionally collides with a real route (bypassing reserved-slug validation by inserting directly into the DB) and confirms the real route still wins.
5. `DeletePageAction` refuses **both** hard delete and soft delete when `is_system = true` (throws `ValidationException`, no record gets `deleted_at` set). The same action on `is_system = false` soft-deletes successfully (`deleted_at` is set) — the page then disappears **simultaneously** from both the `dashboard/pages` list (default query, no `withTrashed()`) and the public URL (404); there is no restore UI, only `Page::withTrashed()->find($id)->restore()` via tinker (§3.3).
6. A page with `template = about` (or any other key registered in `PageTemplate::MAP`) renders through its own dedicated view (`page::public.templates.about`), not through `page::public.show`, and does not display the default `content` block.
7. Setting `template` to a string absent from `PageTemplate::MAP` (simulating a raw request that bypasses the UI) is rejected at Action-level validation (`Rule::in`) — it can never be saved, so it can never cause a 500 at render time from a missing view.
8. Calling `PublishPageAction` on a page whose `template` is registered but whose corresponding Blade view **does not exist yet** on disk (e.g. `page::public.templates.about` not yet created) → rejected, throws `ValidationException`, `status` does **not** change to `published` (§3.3) — this does not rely on an operator remembering not to publish too early.
9. `page.manage` is granted correctly to `platform_ops` + `platform_content_head` via `PagePermissionSeeder` (verified via test or `php artisan tinker`); a user without this permission is blocked (403) on every `dashboard/pages/*` route.
10. Re-running `PageDatabaseSeeder` multiple times does not create duplicate records (idempotent via `firstOrCreate`).
