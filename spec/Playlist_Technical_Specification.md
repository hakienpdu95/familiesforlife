# Module Playlist (Danh sách phát đa nội dung)
**Đặc tả Kỹ thuật Chi tiết — Sẵn sàng Triển khai**

**Phiên bản:** 1.0
**Ngày:** 08/08/2026
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions + Spatie Laravel Data
**Module mới:** `Modules/Playlist`
**Module tham chiếu kiến trúc:**
- `Modules/Video` (`spec/Video_Management_Technical_Specification.md`) — module phẳng, tài sản nền tảng, không qua duyệt (cùng tinh thần §0)
- `Modules/Approval` (`spec/Workflow_Approval_Technical_Specification.md` §5, `ApprovalServiceProvider::boot()`) — nguồn của pattern `Relation::morphMap()` build từ config, dùng cho quan hệ polymorphic xuyên module

---

## 0. Quyết định đã chốt

| Chủ đề | Quyết định spec này | Lý do |
|---|---|---|
| **Phạm vi loại nội dung** | v1 chỉ gom **Video** (`Modules\Video\Models\Video`) và **bài viết** (`Modules\Post\Models\PostArticle`) — không phải khung tổng quát nhận mọi loại content ngay từ đầu | Xác nhận trực tiếp với người yêu cầu. Thiết kế polymorphic (§4.2) khiến việc thêm loại thứ 3 (Product/Event/RealEstate...) sau này chỉ là 1 dòng config + 1 class implement contract — không cần migrate lại, nhưng v1 **không** làm sẵn UI/copy cho các loại chưa có nhu cầu thật (tránh over-engineering đoán trước) |
| **Module mới hay Feature trong Post/Video?** | Module NWIDART **riêng** `Modules/Playlist`, **không** đặt trong `Modules/Post` hay `Modules/Video` | Đúng tiền lệ `Banner` (dùng chung cho ≥2 module khác, tách riêng để không module nào "sở hữu" cái kia — xem `Related_Posts_Engine_Technical_Specification.md` §0 dòng "Module mới hay Feature"). Playlist đọc dữ liệu của CẢ Video LẪN Post, không thể là feature con của 1 trong 2 |
| **Không phụ thuộc cứng (hard dependency) vào Video/Post** | `Modules/Playlist` không `composer require` hay `use` trực tiếp class `Video`/`PostArticle` ở tầng lõi — chỉ biết đến chúng qua `config('playlist.itemables')` (map `type key → FQCN`) + 1 interface `PlaylistableContract` mà Video/Post tự implement | Cùng nguyên tắc `Modules/Approval` (`config('approval.subjects')` rỗng ở Phase 1, entity tự "đăng ký tham gia" — không phải Approval đi biết trước tất cả). Giữ được nguyên tắc module hoá: xoá `Modules/Video` không làm `Modules/Playlist` vỡ, chỉ mất 1 loại item khả dụng |
| **Quan hệ polymorphic: `morphTo()` thật + `morphMap()` config-driven** | Bảng `playlist_items` có `itemable_type`/`itemable_id`; `PlaylistServiceProvider::boot()` gọi `Relation::morphMap(config('playlist.itemables'), merge: true)` — **không** dùng `enforceMorphMap()` (cờ toàn cục, 1 module con không có thẩm quyền bật cho cả app — xem lý do y hệt đã ghi ở `ApprovalServiceProvider::boot()`) | Khác `Modules/Aicem` (né `morphTo()`, tự tra `config('aicem_subjects')` bằng tay) — Aicem chỉ cần quan hệ 1-1 (1 run → 1 subject); Playlist cần **danh sách nhiều item xếp thứ tự thuộc nhiều loại khác nhau**, đúng bài toán `morphTo()` sinh ra để giải, và Eloquent `MorphTo::with()` (eager load) đã tự gom theo từng loại rồi truy vấn 1 lần/loại — không có vấn đề N+1 hay "1 relation không trả được nhiều loại model" như lo ngại ban đầu, đó là cách `morphTo()` hoạt động sẵn |
| **Không dùng pivot "vô danh" (`belongsToMany`) — dùng Model `PlaylistItem` tường minh** | `playlist_items` là 1 Eloquent Model thật (`belongsTo(Playlist::class)` + `morphTo('itemable')`), không phải bảng pivot ẩn sau `belongsToMany` | Cần cột phụ `sort_order` xếp thứ tự RIÊNG trong từng playlist + cần model để gọi `->with('itemable')`. `Playlist::items(): HasMany` (không phải `morphedByMany`) — mỗi hàng `PlaylistItem` tự resolve đúng loại của chính nó qua `morphTo()`, không cần 1 relation riêng cho mỗi loại (`videos()`/`articles()`) |
| **Hợp đồng hiển thị thống nhất: `PlaylistableContract`** | Interface `Modules\Playlist\Contracts\PlaylistableContract` với `getPlaylistCardTitle()`, `getPlaylistCardDescription()`, `getPlaylistCardThumbnailUrl()`, `getPlaylistCardUrl()`, `getPlaylistCardEmbedUrl()` (nullable — chỉ Video trả khác null), `getPlaylistCardTypeLabel()`. `Video`/`PostArticle` implement (§4.3) | Trang public/`admin` chỉ gọi qua interface, không rải `@if($type === 'video') ... @elseif(...)` khắp Blade. `getPlaylistCardEmbedUrl()` là điểm khác biệt hành vi cốt lõi: Video mở lightbox phát trực tiếp (giống trang `/videos`), bài viết điều hướng sang trang riêng — 2 loại nội dung có UX click khác nhau, không thể dùng 1 "url" chung |
| **Bài viết đưa vào playlist PHẢI có bản dịch published ở `post.default_locale`** | `PostArticle::getPlaylistCardUrl()`/`getPlaylistCardTitle()` chỉ đọc `defaultLocaleTranslation` (bản dịch published ở đúng `config('post.default_locale')`) — nếu không có, bài đó **không được phép** thêm vào playlist (chặn ở `SearchPlaylistableItemsAction`, §6.5), và nếu 1 bài đã trong playlist bị unpublish translation đó sau này thì item bị ẩn khỏi trang công khai (§5.2), không lỗi 404 khi khách click | Phát hiện khi khảo sát `PublicArticleController` — route công khai bài viết **chỉ phục vụ đúng 1 locale mặc định** (`config('post.default_locale')`), bản dịch locale khác không có URL public nào trỏ tới. Nếu Playlist trỏ tới bài chỉ có bản dịch tiếng Anh (chưa publish bản mặc định), link sẽ 404 — phải chặn từ gốc, không phải xử lý lỗi khi khách bấm |
| **Item bị xoá/ẩn ở nguồn (Video/Post) không tự xoá khỏi `playlist_items`** | Không có `foreign key`/cascade thật (không thể FK polymorphic trỏ 2 bảng khác nhau) — hàng `playlist_items` vẫn tồn tại, nhưng Query lấy playlist cho trang công khai **luôn** loại các item mà: `itemable` là `null` (bị xoá cứng/soft-delete), hoặc Video có `is_active=false`, hoặc bài viết mất bản dịch published mặc định (điều trên) | Cùng tinh thần "ẩn thay vì xoá vết tích" — Ops có thể tạm ẩn 1 Video (`is_active=false`) mà không lo phải nhớ gỡ nó khỏi N playlist đang chứa nó; khi bật lại `is_active=true`, item tự xuất hiện lại đúng vị trí `sort_order` cũ mà không cần thao tác gì thêm |
| **Đơn vị nền tảng, không tenant** | Không `organization_id`, không global scope theo Organization | Nhất quán Video/Banner, và với Post (đã bỏ `organization_id` từ `2026_07_13_000001_drop_organization_id_from_post_articles_table` — Post cũng đã là tài sản nền tảng) |
| **Không có quy trình duyệt** | Tạo xong hiển thị ngay nếu `is_active=true`, không có state `submitted → approved → published` | Cùng lý do Video/Banner §0 — Playlist do nội bộ platform biên tập, không nhận nộp từ công chúng |
| **Sắp xếp item: nhập tay `sort_order`, không kéo-thả ở v1** | Cột `sort_order` (số nguyên) trên `playlist_items`, sắp theo `ORDER BY sort_order ASC` | Giữ đúng tiền lệ Video v1 (§0 spec Video: "kéo-thả là tiện ích UX có thể thêm sau, không đổi schema") — tránh làm phình phạm vi v1 khi bài toán cấp bách hơn là ghép được 2 nguồn dữ liệu khác nhau vào 1 danh sách trước |
| **Ô tìm kiếm hợp nhất khi thêm item (không phải 2 tab riêng)** | 1 input search ở modal "Thêm item", `SearchPlaylistableItemsAction` (§6.5) gọi tuần tự từng nguồn đã đăng ký trong `config('playlist.itemables')`, mỗi nguồn giới hạn top-N kết quả, trả về 1 mảng đã chuẩn hoá `{type, id, title, thumbnail_url, type_label}` để render chung 1 danh sách kèm badge phân loại | Xác nhận với người yêu cầu — đánh đổi lấy UX mượt hơn (không phải chuyển tab để tìm đúng loại), đổi lại cần 1 lớp gộp kết quả từ 2 nguồn dữ liệu có cấu trúc khác nhau (không phải SQL UNION vì khác bảng/khác cột) — xử lý ở tầng PHP (Collection), không ở tầng SQL |
| **SEO đầy đủ ngay từ v1** | `playlists` có thêm cột `meta_title`, `meta_description` (nullable, fallback về `name`/`description` nếu trống) + cover ảnh riêng (`cover_image_url`, fallback thumbnail item đầu tiên nếu trống). Trang chi tiết `/playlists/{slug}` có `<link rel="canonical">`, đầy đủ OG tags, JSON-LD `CollectionPage` lồng `ItemList` | Xác nhận với người yêu cầu — tránh phải migrate thêm cột sau nếu playlist thật sự được dùng làm landing page SEO (curated collection: "Top video phỏng vấn chuyên gia dinh dưỡng", "Chuỗi bài hướng dẫn ăn dặm"...). Theo đúng pattern AEO/GEO đã áp dụng cho Post (`ArticleStructuredDataBuilder`, xem [[project_post_aeo_geo_structured_data]]) |
| **Quyền hạn: `playlist.manage` riêng (Lớp B)** | 1 permission `playlist.manage`, seed qua `PlaylistPermissionSeeder`, cấp cho `platform_ops` + `platform_content_head` — **không** mượn `video.manage` | Playlist là module cross-cutting mới, không phải năng lực nội tại của Video — mượn permission của Video sẽ khiến 1 tài khoản chỉ cần quản video lại vô tình quản được cả playlist chứa bài viết (vượt phạm vi công việc), và ngược lại nếu sau này thu hồi `video.manage` của 1 người thì Playlist bị ảnh hưởng ngoài ý muốn |
| **Không dedup, không ràng buộc trùng item** | 1 video/bài viết có thể nằm trong nhiều playlist, và (ít có ý nghĩa nhưng không chặn) có thể thêm trùng 2 lần vào cùng 1 playlist | Cùng nguyên tắc Video §0 (không ép `unique` sớm khi chưa có yêu cầu nghiệp vụ rõ) — validate ở UI (input search disable item đã có trong danh sách) đủ để tránh trùng vô tình, không cần constraint DB cứng |

---

## 1. Giới thiệu & Mục tiêu

Cổng thông tin hiện có 2 nguồn nội dung media/bài viết tách rời hoàn toàn: `Modules/Video` (thư viện video phẳng, 1 trang lưới duy nhất `/videos`) và `Modules/Post` (bài viết, phân theo category/tag). Không có cách nào để **biên tập 1 bộ sưu tập nội dung theo chủ đề trộn cả video lẫn bài viết** — ví dụ "Chuỗi nội dung: Ăn dặm cho bé 6 tháng" muốn gồm 2 video hướng dẫn + 3 bài viết chuyên sâu, hiển thị theo đúng 1 thứ tự biên tập, không có nơi nào làm được.

Module **Playlist** giải quyết bằng 1 bảng `playlists` (thông tin danh sách) + 1 bảng quan hệ đa hình `playlist_items` (từng phần tử, trỏ tới Video hoặc PostArticle qua `morphTo()`), với:

- Trang quản trị: tạo/sửa playlist, tìm và thêm item (video hoặc bài viết) qua 1 ô tìm kiếm hợp nhất, sắp thứ tự bằng `sort_order`.
- Trang công khai: `/playlists` (danh sách playlist active) + `/playlists/{slug}` (chi tiết — card từng item render đúng theo loại, video mở lightbox phát ngay, bài viết dẫn sang trang bài viết), có đầy đủ SEO (OG + JSON-LD).

**Nguyên tắc thiết kế cốt lõi:** `Modules/Playlist` không biết trước Video/Post là gì ở tầng lõi — nó chỉ định nghĩa 1 hợp đồng (`PlaylistableContract`) và 1 bảng cấu hình (`config('playlist.itemables')`); Video/Post tự "đăng ký tham gia" bằng cách implement interface đó. Thêm loại nội dung thứ 3 sau này (nếu có nhu cầu thật) không đổi schema `playlist_items`, chỉ thêm 1 dòng config + 1 class.

---

## 2. Khảo sát điểm vào (entry points)

| Vị trí | Route | Ghi chú |
|---|---|---|
| Sidebar dashboard (mục quản trị mới) | `backend.playlist.items.index` | Hiển thị nếu `@can('playlist.manage')`, cùng pattern Video/Banner |
| Trang công khai — danh sách playlist | `GET /playlists` → `playlist.public.index` | Layout dùng chung `layouts/frontend.blade.php` |
| Trang công khai — chi tiết 1 playlist | `GET /playlists/{slug}` → `playlist.public.show` | Card polymorphic qua `PlaylistableContract`, SEO đầy đủ (§7.3) |
| API nội bộ cho bảng Tabulator ở trang quản trị | `GET backend/api/playlists/items` → `backend.api.playlists.items` | Cùng pattern `VideoApiController`/`BannerApiController` |
| API tìm kiếm hợp nhất khi thêm item | `GET backend/api/playlists/{playlist}/searchable-items?q=...` → `backend.api.playlists.searchable-items` | Trả mảng chuẩn hoá trộn Video + PostArticle (§6.5) |

Không có vị trí nhúng Playlist vào `Modules/Post` (làm content block) hay `Modules/Event` ở v1 — ngoài phạm vi (§9).

---

## 3. Kiến trúc dữ liệu

### 3.1 ERD

```
Playlist
  ├─ uuid
  ├─ name (string, 255)
  ├─ slug (string, 255, unique)
  ├─ description (text, nullable)
  ├─ cover_image_url (string, 2048, nullable)      — nếu trống, trang public fallback thumbnail item đầu tiên (§5.3)
  ├─ meta_title (string, 255, nullable)             — SEO, fallback `name` (§0/§7.3)
  ├─ meta_description (string, 500, nullable)       — SEO, fallback `description` (§0/§7.3)
  ├─ sort_order (unsigned smallint)
  ├─ is_active (bool)
  ├─ created_by, updated_by, timestamps, soft delete
  └─ items() : HasMany → PlaylistItem, orderBy sort_order

PlaylistItem
  ├─ playlist_id (FK → playlists)
  ├─ itemable_type (string — alias qua morphMap, KHÔNG lưu FQCN thô)
  ├─ itemable_id (unsigned bigint)
  ├─ sort_order (unsigned smallint)                 — thứ tự RIÊNG trong playlist này
  ├─ timestamps
  └─ itemable() : MorphTo → Video | PostArticle (mở rộng được qua config, §4.2)
```

Không có `organization_id` ở cả 2 bảng — tài sản nền tảng (§0).

### 3.2 Migrations

`Modules/Playlist/database/migrations/2026_08_08_000001_create_playlists_table.php`

```php
Schema::create('playlists', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();

    $table->string('name', 255);
    $table->string('slug', 255)->unique();
    $table->text('description')->nullable();
    $table->string('cover_image_url', 2048)->nullable();

    // SEO (§0/§7.3) — nullable, view fallback về name/description khi trống.
    $table->string('meta_title', 255)->nullable();
    $table->string('meta_description', 500)->nullable();

    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);

    // Cùng quyết định created_by/updated_by như Video/Banner (restrictOnDelete/nullOnDelete).
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['is_active', 'sort_order'], 'idx_playlist_active_sort');
});
```

`Modules/Playlist/database/migrations/2026_08_08_000002_create_playlist_items_table.php`

```php
Schema::create('playlist_items', function (Blueprint $table) {
    $table->id();

    $table->foreignId('playlist_id')->constrained('playlists')->cascadeOnDelete();

    // Không có FK thật (itemable_id trỏ 1 trong nhiều bảng khác nhau tuỳ itemable_type) —
    // xử lý item "mồ côi" (nguồn đã xoá/ẩn) ở tầng Query khi đọc, không ở tầng DB (§0/§5.2).
    $table->string('itemable_type', 60);
    $table->unsignedBigInteger('itemable_id');

    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->timestamps();

    $table->index(['playlist_id', 'sort_order'], 'idx_playlist_item_sort');
    $table->index(['itemable_type', 'itemable_id'], 'idx_playlist_item_itemable');
});
```

---

## 4. Model & cấu hình

### 4.1 `Modules\Playlist\Models\Playlist`

```php
namespace Modules\Playlist\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Playlist extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'uuid', 'name', 'slug', 'description', 'cover_image_url',
        'meta_title', 'meta_description', 'sort_order', 'is_active',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $playlist): void {
            $playlist->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PlaylistItem::class)->orderBy('sort_order');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** SEO — fallback về dữ liệu hiển thị nếu chưa nhập riêng (§0/§7.3). */
    public function getEffectiveMetaTitleAttribute(): string
    {
        return $this->meta_title ?: $this->name;
    }

    public function getEffectiveMetaDescriptionAttribute(): ?string
    {
        return $this->meta_description ?: $this->description;
    }

    /**
     * Ảnh đại diện — dùng cover_image_url nếu có, fallback thumbnail của item ĐẦU TIÊN còn hợp
     * lệ (bỏ qua item mồ côi/ẩn, §5.2) qua PlaylistableContract::getPlaylistCardThumbnailUrl().
     * Gọi ở trang danh sách playlist (§7.2) — cần eager-load 'items.itemable' trước khi gọi để
     * tránh N+1 khi liệt kê nhiều playlist cùng lúc.
     */
    public function getEffectiveCoverImageUrlAttribute(): ?string
    {
        if ($this->cover_image_url) {
            return $this->cover_image_url;
        }

        $firstValidItem = $this->items
            ->map(fn (PlaylistItem $item) => $item->itemable)
            ->filter()
            ->first();

        return $firstValidItem?->getPlaylistCardThumbnailUrl();
    }
}
```

### 4.2 `Modules\Playlist\Models\PlaylistItem`

```php
namespace Modules\Playlist\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Playlist\Contracts\PlaylistableContract;

class PlaylistItem extends Model
{
    protected $fillable = ['playlist_id', 'itemable_type', 'itemable_id', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    /**
     * $itemable_type là ALIAS đã đăng ký qua Relation::morphMap() (§4.4, ví dụ "video",
     * "post_article") — KHÔNG phải FQCN thô. morphTo() eager-load ('itemable') tự gom các hàng
     * theo từng loại rồi query 1 lần/loại (hành vi mặc định của Eloquent, không cần code thêm).
     */
    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Truy cập itemable đã ép kiểu về contract — dùng ở view thay vì $item->itemable trực tiếp
     * để IDE/static-analysis biết chắc có các method getPlaylistCard*(). Trả null nếu item mồ
     * côi (nguồn đã xoá cứng) — nơi gọi PHẢI lọc null trước khi render (§5.2).
     */
    public function getResolvedItemableAttribute(): ?PlaylistableContract
    {
        return $this->itemable instanceof PlaylistableContract ? $this->itemable : null;
    }
}
```

### 4.3 `Modules\Playlist\Contracts\PlaylistableContract`

```php
namespace Modules\Playlist\Contracts;

/**
 * Hợp đồng mà mọi model muốn "tham gia" làm item của Playlist phải implement (§0). Video/Post
 * implement interface này ngay trên chính Model của chúng (§4.4/§4.5) — Modules/Playlist không
 * cần biết Video/Post là gì, chỉ gọi qua interface.
 */
interface PlaylistableContract
{
    public function getPlaylistCardTitle(): string;

    public function getPlaylistCardDescription(): ?string;

    public function getPlaylistCardThumbnailUrl(): ?string;

    /** Link "an toàn" luôn dùng được kể cả khi không mở lightbox — trang chi tiết/watch_url. */
    public function getPlaylistCardUrl(): string;

    /**
     * URL nhúng lightbox — CHỈ Video trả khác null (§0). Bài viết trả null → view điều hướng
     * sang getPlaylistCardUrl() thay vì mở modal.
     */
    public function getPlaylistCardEmbedUrl(): ?string;

    /** Nhãn phân loại hiển thị ở badge — "Video" / "Bài viết". */
    public function getPlaylistCardTypeLabel(): string;

    /**
     * Có còn hợp lệ để hiển thị công khai không (is_active/published) — Query công khai (§5.2)
     * lọc theo method này thay vì đoán tên cột is_active/status khác nhau giữa các loại model.
     */
    public function isPlaylistCardVisible(): bool;
}
```

### 4.4 Implement ở `Modules\Video\Models\Video` (thay đổi cross-module)

> Đây là thay đổi DUY NHẤT cần chạm vào `Modules/Video` — thêm `implements PlaylistableContract` + 5 method ngắn, tái dùng nguyên các accessor đã có (`thumbnail_url`, `embed_url`, `watch_url`, `is_active`), không đổi logic nghiệp vụ hiện tại của Video.

```php
use Modules\Playlist\Contracts\PlaylistableContract;

class Video extends Model implements PlaylistableContract
{
    // ... giữ nguyên toàn bộ code hiện tại ...

    public function getPlaylistCardTitle(): string
    {
        return $this->name;
    }

    public function getPlaylistCardDescription(): ?string
    {
        return $this->description;
    }

    public function getPlaylistCardThumbnailUrl(): ?string
    {
        return $this->thumbnail_url;
    }

    public function getPlaylistCardUrl(): string
    {
        return $this->watch_url;
    }

    public function getPlaylistCardEmbedUrl(): ?string
    {
        return $this->embed_url;
    }

    public function getPlaylistCardTypeLabel(): string
    {
        return 'Video';
    }

    public function isPlaylistCardVisible(): bool
    {
        return $this->is_active;
    }
}
```

### 4.5 Implement ở `Modules\Post\Models\PostArticle` (thay đổi cross-module)

> Cần thêm 1 accessor `defaultLocaleTranslation` nếu Post chưa có sẵn quan hệ tương đương — tra đúng bản dịch published ở `config('post.default_locale')` (§0, lý do chống 404).

```php
use Modules\Playlist\Contracts\PlaylistableContract;

class PostArticle extends Model implements HasMedia, PlaylistableContract
{
    // ... giữ nguyên toàn bộ code hiện tại ...

    /** Bản dịch DUY NHẤT được phép dùng cho mọi output công khai xuyên-module (§0). */
    public function getDefaultLocaleTranslationAttribute(): ?PostArticleTranslation
    {
        return $this->translations
            ->where('locale', config('post.default_locale'))
            ->firstWhere('status', TranslationStatus::Published);
    }

    public function getPlaylistCardTitle(): string
    {
        return $this->default_locale_translation?->title ?? '';
    }

    public function getPlaylistCardDescription(): ?string
    {
        return $this->default_locale_translation?->excerpt;
    }

    public function getPlaylistCardThumbnailUrl(): ?string
    {
        return $this->cover_image_url ?: null;
    }

    public function getPlaylistCardUrl(): string
    {
        $translation = $this->default_locale_translation;

        return $translation
            ? route('post.public.article', ['slug' => $translation->slug, 'id' => $this->id])
            : '#';
    }

    public function getPlaylistCardEmbedUrl(): ?string
    {
        return null; // bài viết luôn điều hướng, không mở lightbox (§0)
    }

    public function getPlaylistCardTypeLabel(): string
    {
        return 'Bài viết';
    }

    public function isPlaylistCardVisible(): bool
    {
        return $this->default_locale_translation !== null;
    }
}
```

### 4.6 `Modules/Playlist/config/config.php`

```php
return [
    'name' => 'Playlist',

    // Map "type key" ngắn (lưu trong cột itemable_type) → FQCN model thật. NGUỒN DUY NHẤT dùng
    // để build Relation::morphMap() (§4.7) — thêm loại nội dung mới chỉ cần thêm 1 dòng ở đây +
    // 1 class implement PlaylistableContract, KHÔNG migrate lại playlist_items.
    'itemables' => [
        'video'        => \Modules\Video\Models\Video::class,
        'post_article' => \Modules\Post\Models\PostArticle::class,
    ],

    // Số kết quả tối đa MỖI nguồn khi tìm kiếm hợp nhất ở modal "Thêm item" (§6.5) — tránh 1
    // nguồn có nhiều bản ghi khớp từ khoá lấn át hoàn toàn nguồn còn lại trong danh sách gộp.
    'search_limit_per_type' => 10,

    'per_page' => 12,
];
```

### 4.7 `PlaylistServiceProvider::boot()`

```php
namespace Modules\Playlist\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Nwidart\Modules\Support\ModuleServiceProvider;

class PlaylistServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Playlist';
    protected string $nameLower = 'playlist';

    public function boot(): void
    {
        parent::boot();

        // Cùng quyết định ApprovalServiceProvider::boot() — merge: true, KHÔNG enforceMorphMap()
        // (xem lý do đầy đủ ở Modules/Approval/app/Providers/ApprovalServiceProvider.php).
        Relation::morphMap(config('playlist.itemables', []), merge: true);
    }
}
```

---

## 5. Business rules

### 5.1 Thêm/xoá/sắp xếp item

- **Thêm item**: `AddItemToPlaylistAction::handle(Playlist $playlist, string $itemableType, int $itemableId)` — validate `$itemableType` nằm trong `array_keys(config('playlist.itemables'))`, resolve model qua morphMap, gọi `isPlaylistCardVisible()` — từ chối thêm nếu `false` (vd bài viết chưa publish bản dịch mặc định, video đang `is_active=false`) kèm thông báo lỗi rõ ràng ("Video này đang tắt hiển thị, hãy bật lại trước khi thêm vào playlist").
- **Xoá item**: `RemoveItemFromPlaylistAction::handle(PlaylistItem $item)` — xoá cứng hàng `playlist_items` (không soft-delete, bản thân đây chỉ là 1 liên kết, không phải nội dung gốc).
- **Sắp xếp**: `ReorderPlaylistItemsAction::handle(Playlist $playlist, array $orderedItemIds)` — cập nhật `sort_order` hàng loạt theo thứ tự mảng truyền vào (form nhập tay từng số, §0 — không phải kéo-thả AJAX).

### 5.2 Lọc item hợp lệ khi đọc (cả admin lẫn public)

`Playlist::items()` trả VỀ TẤT CẢ hàng `playlist_items` kể cả mồ côi/ẩn (đúng dữ liệu thật trong DB, cần thiết để trang **admin** biết mà báo "1 item trong danh sách này đang ẩn/đã bị xoá nguồn, cân nhắc gỡ"). Trang **công khai** dùng thêm 1 lớp lọc:

```php
// GetPlaylistForPublicHandler — lọc item trước khi render
$visibleItems = $playlist->items
    ->map(fn (PlaylistItem $item) => $item->resolved_itemable)
    ->filter(fn (?PlaylistableContract $itemable) => $itemable?->isPlaylistCardVisible());
```

Không sửa lại `sort_order`/xoá hàng `playlist_items` tự động khi lọc — chỉ ẩn khỏi kết quả trả về, giữ nguyên dữ liệu gốc để khi nguồn được bật lại (`is_active=true`/publish lại bản dịch) thì item tự xuất hiện lại đúng vị trí cũ, không cần thao tác lại (§0).

### 5.3 Ảnh đại diện playlist

`Playlist::effective_cover_image_url` (§4.1) — ưu tiên `cover_image_url` tự nhập, fallback thumbnail item hợp lệ đầu tiên. Nếu playlist không có item hợp lệ nào và không nhập cover riêng → `null`, trang danh sách render placeholder tĩnh (cùng cách Video xử lý ảnh lỗi ở `public/index.blade.php`).

### 5.4 Sắp xếp & bật/tắt playlist

`sort_order` (nhập tay) quyết định thứ tự hiển thị playlist tại `/playlists`. `is_active=false` ẩn khỏi trang công khai nhưng vẫn thấy ở admin (cùng nguyên tắc Video §5.4).

---

## 6. Admin CRUD (`Modules/Playlist`)

### 6.1 Cấu trúc thư mục

```
Modules/Playlist/
├── app/
│   ├── Contracts/PlaylistableContract.php
│   ├── Features/
│   │   ├── PlaylistManagement/
│   │   │   ├── Actions/
│   │   │   │   ├── CreatePlaylistAction.php
│   │   │   │   ├── UpdatePlaylistAction.php
│   │   │   │   ├── DeletePlaylistAction.php
│   │   │   │   ├── ToggleplaylistActiveAction.php
│   │   │   │   ├── AddItemToPlaylistAction.php
│   │   │   │   ├── RemoveItemFromPlaylistAction.php
│   │   │   │   ├── ReorderPlaylistItemsAction.php
│   │   │   │   └── SearchPlaylistableItemsAction.php
│   │   │   ├── Data/PlaylistData.php
│   │   │   ├── Http/
│   │   │   │   ├── PlaylistAdminController.php
│   │   │   │   ├── PlaylistApiController.php
│   │   │   │   └── Resources/PlaylistListResource.php
│   │   │   └── Queries/
│   │   │       ├── ListPlaylistsForAdminQuery.php
│   │   │       └── ListPlaylistsForAdminHandler.php
│   │   └── PublicReading/
│   │       ├── Http/PlaylistPublicController.php
│   │       └── Queries/GetPlaylistForPublicHandler.php
│   ├── Models/{Playlist.php,PlaylistItem.php}
│   ├── Policies/PlaylistPolicy.php
│   └── Providers/{PlaylistServiceProvider.php,RouteServiceProvider.php}
├── config/config.php
├── database/
│   ├── migrations/{..._create_playlists_table.php,..._create_playlist_items_table.php}
│   └── seeders/{PlaylistDatabaseSeeder.php,PlaylistPermissionSeeder.php}
├── resources/views/
│   ├── admin/playlists/{index,create,edit,_form,_item-picker}.blade.php
│   └── public/{index,show}.blade.php
├── routes/web.php
├── composer.json
└── module.json
```

### 6.2 Routes

```php
Route::middleware(['auth'])->prefix('dashboard/playlists')->name('backend.playlist.')->group(function (): void {
    Route::resource('items', PlaylistAdminController::class)->except(['show'])->parameters(['items' => 'playlist']);
    Route::patch('items/{playlist}/toggle-active', [PlaylistAdminController::class, 'toggleActive'])->name('items.toggle-active');
    Route::post('items/{playlist}/attach-item', [PlaylistAdminController::class, 'attachItem'])->name('items.attach-item');
    Route::delete('playlist-items/{playlistItem}', [PlaylistAdminController::class, 'detachItem'])->name('items.detach-item');
    Route::patch('items/{playlist}/reorder-items', [PlaylistAdminController::class, 'reorderItems'])->name('items.reorder-items');
});

Route::middleware(['auth'])->prefix('backend/api/playlists')->name('backend.api.playlists.')->group(function (): void {
    Route::get('items', [PlaylistApiController::class, 'index'])->name('items');
    Route::get('{playlist}/searchable-items', [PlaylistApiController::class, 'searchableItems'])->name('searchable-items');
});

Route::name('playlist.public.')->group(function (): void {
    Route::get('playlists', [PlaylistPublicController::class, 'index'])->name('index');
    Route::get('playlists/{playlist:slug}', [PlaylistPublicController::class, 'show'])->name('show');
});
```

### 6.3 `SearchPlaylistableItemsAction` — trái tim của ô tìm kiếm hợp nhất

```php
namespace Modules\Playlist\Features\PlaylistManagement\Actions;

use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Playlist\Contracts\PlaylistableContract;

class SearchPlaylistableItemsAction
{
    use AsAction;

    /**
     * Gọi lần lượt từng nguồn khai trong config('playlist.itemables'), mỗi nguồn tự biết cách
     * search đúng bảng của mình (Video theo `name`, PostArticle theo translation.title) — Action
     * này KHÔNG viết SQL trực tiếp lên bảng cụ thể, chỉ điều phối + chuẩn hoá kết quả trả về.
     *
     * @return Collection<int, array{type: string, id: int, title: string, thumbnail_url: ?string, type_label: string}>
     */
    public function handle(string $keyword): Collection
    {
        $limit = config('playlist.search_limit_per_type', 10);
        $results = collect();

        foreach (config('playlist.itemables', []) as $typeKey => $modelClass) {
            /** @var class-string<PlaylistableContract> $modelClass */
            $matches = $modelClass::query()
                ->searchablePlaylistItems($keyword) // scope riêng mỗi model phải tự định nghĩa (§6.4)
                ->limit($limit)
                ->get();

            foreach ($matches as $item) {
                $results->push([
                    'type'          => $typeKey,
                    'id'            => $item->getKey(),
                    'title'         => $item->getPlaylistCardTitle(),
                    'thumbnail_url' => $item->getPlaylistCardThumbnailUrl(),
                    'type_label'    => $item->getPlaylistCardTypeLabel(),
                ]);
            }
        }

        return $results;
    }
}
```

### 6.4 Yêu cầu bổ sung ở model tham gia: scope `searchablePlaylistItems`

Mỗi model implement `PlaylistableContract` cũng phải tự định nghĩa `scopeSearchablePlaylistItems(Builder $query, string $keyword)` — Playlist không biết Video search theo cột `name` hay Post search qua `whereHas('translations', ...)`, việc đó thuộc về chính model:

```php
// Video
public function scopeSearchablePlaylistItems(Builder $query, string $keyword): void
{
    $query->active()->where('name', 'like', "%{$keyword}%");
}

// PostArticle
public function scopeSearchablePlaylistItems(Builder $query, string $keyword): void
{
    $query->whereHas('translations', fn ($q) => $q
        ->where('locale', config('post.default_locale'))
        ->where('status', TranslationStatus::Published)
        ->where('title', 'like', "%{$keyword}%"));
}
```

> Đây là lý do `PlaylistableContract` không thể chỉ là "marker interface" — cần ghi rõ trong docblock interface (§4.3) rằng model implement còn phải cung cấp scope này theo đúng tên, dù PHP không ép được "phải có scope X" qua interface thuần (giới hạn ngôn ngữ, chấp nhận được — kiểm bằng test tích hợp thay vì contract cứng, §8).

### 6.5 `PlaylistApiController::searchableItems()`

```php
public function searchableItems(Request $request, Playlist $playlist, SearchPlaylistableItemsAction $search): JsonResponse
{
    $this->authorize('update', $playlist);

    $request->validate(['q' => ['required', 'string', 'min:2', 'max:255']]);

    return response()->json(['data' => $search->handle($request->string('q'))]);
}
```

Modal "Thêm item" ở `admin/playlists/_item-picker.blade.php` gọi endpoint này (debounce input), render 1 danh sách trộn kèm badge `type_label`, bấm 1 dòng → gọi `items.attach-item` với `type`+`id` tương ứng.

### 6.6 Permission (`PlaylistPermissionSeeder`)

Sao chép nguyên khuôn `VideoPermissionSeeder` (§0 — permission riêng `playlist.manage`, cấp `platform_ops` + `platform_content_head`, sync `super-admin`).

---

## 7. Trang công khai

### 7.1 `/playlists` — danh sách

Lưới card playlist (`effective_cover_image_url`, `name`, `description` rút gọn, số lượng item hợp lệ) — click vào 1 card điều hướng sang `/playlists/{slug}`. Empty state nếu chưa có playlist active nào (cùng cách Video xử lý).

### 7.2 `/playlists/{slug}` — chi tiết

```blade
@foreach($visibleItems as $item)
    @if($item->getPlaylistCardEmbedUrl())
        {{-- Video — mở lightbox, TÁI DÙNG alpine x-data pattern từ Modules/Video/resources/views/public/index.blade.php --}}
        <button @click="open = true; activeUrl = '{{ $item->getPlaylistCardEmbedUrl() }}'; ...">
    @else
        {{-- Bài viết — link điều hướng thường --}}
        <a href="{{ $item->getPlaylistCardUrl() }}">
    @endif
        <img src="{{ $item->getPlaylistCardThumbnailUrl() ?? asset('images/post-cover-placeholder.svg') }}" ...>
        <span class="badge">{{ $item->getPlaylistCardTypeLabel() }}</span>
        <h3>{{ $item->getPlaylistCardTitle() }}</h3>
@endforeach
```

Lightbox modal dùng chung 1 instance đặt ngoài vòng lặp — copy nguyên cấu trúc Alpine đã kiểm chứng ở `Modules/Video/resources/views/public/index.blade.php` (không viết lại từ đầu).

### 7.3 SEO (§0)

```blade
@section('title', $playlist->effective_meta_title)
@if($playlist->effective_meta_description)
@section('meta_description', $playlist->effective_meta_description)
@endif

@push('meta')
<link rel="canonical" href="{{ $canonicalUrl }}">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $playlist->effective_meta_title }}">
@if($playlist->effective_meta_description)
<meta property="og:description" content="{{ $playlist->effective_meta_description }}">
@endif
<meta property="og:url" content="{{ $canonicalUrl }}">
@if($playlist->effective_cover_image_url)
<meta property="og:image" content="{{ $playlist->effective_cover_image_url }}">
@endif
@endpush
```

JSON-LD — `CollectionPage` lồng `ItemList` (schema.org không có type chuyên biệt cho "playlist trộn nhiều loại nội dung"; `ItemList` là type tổng quát đúng ngữ nghĩa "1 danh sách có thứ tự các nội dung khác nhau", nhất quán với cách Post đã dùng JSON-LD cho AEO/GEO — xem [[project_post_aeo_geo_structured_data]]):

```php
[
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $playlist->effective_meta_title,
    'description' => $playlist->effective_meta_description,
    'url' => $canonicalUrl,
    'mainEntity' => [
        '@type' => 'ItemList',
        'itemListElement' => $visibleItems->values()->map(fn ($item, $i) => [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'url' => $item->getPlaylistCardUrl(),
            'name' => $item->getPlaylistCardTitle(),
        ])->all(),
    ],
]
```

---

## 8. Kiểm thử bắt buộc trước khi coi là xong (§8, phase implementation)

- `PlaylistItem::itemable` resolve đúng cả 2 loại (`morphMap` hoạt động đúng, không bị fallback về FQCN thô).
- `AddItemToPlaylistAction` từ chối thêm: video `is_active=false`, bài viết chưa có bản dịch published ở `default_locale`, `itemableType` không nằm trong `config('playlist.itemables')` (input giả mạo qua request thủ công).
- Trang công khai KHÔNG render item mồ côi (video/bài viết đã xoá cứng khỏi DB nhưng `playlist_items` còn hàng trỏ tới id đó).
- Trang công khai vẫn hiển thị đúng khi playlist có **cả 2 loại item trộn lẫn** theo đúng `sort_order` chung (không bị Eloquent tự nhóm video lên trước/bài viết xuống sau).
- `SearchPlaylistableItemsAction` trả đúng giới hạn `search_limit_per_type` cho từng nguồn khi 1 nguồn có nhiều kết quả khớp hơn nguồn kia (không để 1 nguồn lấn át).
- Xoá mềm `Video`/`PostArticle` → `PlaylistItem::itemable` trả `null`, không throw exception ở bất kỳ nơi nào gọi `->getPlaylistCard*()` (đã bọc `?->` ở mọi nơi đọc, §5.2).

---

## 9. Ngoài phạm vi (out of scope) — ghi rõ để tránh hiểu nhầm khi review

- **Thêm loại nội dung thứ 3+ (Product, Event, RealEstate...)** — schema đã sẵn sàng mở rộng (§0/§4.6), nhưng v1 chỉ wiring Video + Post theo đúng yêu cầu đã xác nhận; wiring thêm loại khác là 1 thay đổi nhỏ (thêm config + implement contract) nhưng thuộc phạm vi khi có nhu cầu thật.
- **Kéo-thả sắp xếp (drag-and-drop reorder)** cho cả playlist lẫn item trong playlist — v1 chỉ có input số `sort_order` nhập tay, cùng quyết định Video §0/§9.
- **Playlist lồng playlist (nested)** — 1 playlist không thể chứa 1 playlist khác làm item, chỉ chứa Video/PostArticle trực tiếp.
- **Đếm lượt xem playlist** — không có yêu cầu đo lường nào ở v1 (khác Banner có `click_count`).
- **Danh mục/gắn thẻ cho chính Playlist** — v1 là 1 danh sách phẳng các playlist, không phân trang theo category playlist.
- **UI cảnh báo chủ động "playlist này có N item đang ẩn/mồ côi"** ở trang admin — §5.2 chỉ đảm bảo trang public lọc đúng, chưa làm badge cảnh báo ở trang quản trị; có thể thêm sau không đổi schema.
- **Giao diện khôi phục bản ghi đã xoá mềm (soft-delete restore UI)** — cùng quyết định Video/Banner, thao tác qua DB/tinker ở v1.
