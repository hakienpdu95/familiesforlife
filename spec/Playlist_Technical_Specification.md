# Module Playlist (Danh sách phát đa nội dung)
**Đặc tả Kỹ thuật Chi tiết — Sẵn sàng Triển khai**

**Phiên bản:** 1.1
**Ngày:** 08/08/2026
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions + Spatie Laravel Data
**Module mới:** `Modules/Playlist`
**Module tham chiếu kiến trúc:**
- `Modules/Video` (`spec/Video_Management_Technical_Specification.md`) — module phẳng, tài sản nền tảng, không qua duyệt (cùng tinh thần §0)
- `Modules/Approval` (`spec/Workflow_Approval_Technical_Specification.md` §5, `ApprovalServiceProvider::boot()`) — nguồn của pattern `Relation::morphMap()` build từ config, dùng cho quan hệ polymorphic xuyên module

> **Lịch sử phiên bản**
> - **v1.0** — Bản thảo đầu: `playlists` + `playlist_items` (polymorphic qua `morphTo()`+`morphMap()`), `PlaylistableContract`, Video/Post implement, SEO đầy đủ, ô tìm kiếm hợp nhất.
> - **v1.1** — Sau review nội bộ, siết lại 1 số điểm formal/vận hành: (1) `scopeSearchablePlaylistItems` chuyển từ "quy ước docblock" sang **bắt buộc qua interface** (PHP throws Fatal Error nếu thiếu, không còn là giới hạn ngôn ngữ như bản thảo đầu tưởng); (2) thêm accessor gộp `visible_itemable` để không còn rải `?->`/check `isPlaylistCardVisible()` thủ công ở nhiều nơi (giảm rủi ro quên → lỗi production); (3) `config('playlist.itemables')` đổi cấu trúc để mang theo cả eager-load relation (`morphWith()`) mà không phá nguyên tắc "không phụ thuộc cứng"; (4) ghi rõ `sort_order` khi attach = `max+1` tự động, không bắt người dùng nhập tay lúc thêm mới; (5) `SearchPlaylistableItemsAction` lọc thêm `isPlaylistCardVisible()` (phòng thủ lớp 2) + loại trừ item đã có trong playlist; (6) thêm badge cảnh báo item ẩn/mồ côi ở trang sửa playlist (chi phí thấp, không phải banner tổng đã để ngoài phạm vi); (7) thêm `PlaylistData` validate rules, activity log cho `PlaylistItem`, ghi chú vận hành khi `post.default_locale` đổi, và làm rõ `cascadeOnDelete` trên `playlist_items` chỉ kích hoạt khi `forceDelete()` — không mâu thuẫn với soft-delete mặc định; (8) mở rộng bộ test bắt buộc (§8) và sửa lỗi đặt tên `ToggleplaylistActiveAction` → `TogglePlaylistActiveAction`.

---

## 0. Quyết định đã chốt

| Chủ đề | Quyết định spec này | Lý do |
|---|---|---|
| **Phạm vi loại nội dung** | v1 chỉ gom **Video** (`Modules\Video\Models\Video`) và **bài viết** (`Modules\Post\Models\PostArticle`) — không phải khung tổng quát nhận mọi loại content ngay từ đầu | Xác nhận trực tiếp với người yêu cầu. Thiết kế polymorphic (§4.2) khiến việc thêm loại thứ 3 sau này chỉ là 1 dòng config + 1 class implement contract — không cần migrate lại, nhưng v1 **không** làm sẵn UI/copy cho loại chưa có nhu cầu thật |
| **Module mới hay Feature trong Post/Video?** | Module NWIDART **riêng** `Modules/Playlist` | Đúng tiền lệ `Banner` (dùng chung ≥2 module, tách riêng để không module nào "sở hữu" cái kia) |
| **Không phụ thuộc cứng vào Video/Post** | `Modules/Playlist` không `use` trực tiếp class `Video`/`PostArticle` ở tầng lõi — chỉ qua `config('playlist.itemables')` + interface `PlaylistableContract` | Cùng nguyên tắc `Modules/Approval`. Giữ nguyên tắc module hoá: xoá `Modules/Video` không làm `Modules/Playlist` vỡ |
| **Quan hệ polymorphic: `morphTo()` thật + `morphMap()` config-driven** | `PlaylistServiceProvider::boot()` gọi `Relation::morphMap(..., merge: true)` — **không** `enforceMorphMap()` (1 module con không có thẩm quyền bật cờ toàn cục cho cả app) | Khác `Modules/Aicem` (né `morphTo()`, quan hệ 1-1) — Playlist cần danh sách nhiều item xếp thứ tự thuộc nhiều loại, đúng bài toán `morphTo()` sinh ra để giải; `MorphTo::with()`/`morphWith()` tự gom theo loại rồi query 1 lần/loại, không N+1 |
| **`PlaylistItem` là Model tường minh, không phải pivot ẩn** | `belongsTo(Playlist::class)` + `morphTo('itemable')` + cột `sort_order` riêng | Cần cột phụ xếp thứ tự trong từng playlist + cần model để `->with('itemable')`, `LogsActivity` (xem hàng activity-log riêng bên dưới) |
| **Hợp đồng `scopeSearchablePlaylistItems` bắt buộc qua interface, không chỉ docblock** | `PlaylistableContract` khai báo thẳng `public function scopeSearchablePlaylistItems(Builder $query, string $keyword): void;` cùng các method hiển thị khác (§4.3) | **Sửa so với bản thảo đầu**: Eloquent local scope về bản chất chỉ là 1 public instance method có tiền tố `scope` — hoàn toàn khai báo được trong interface như method thường, PHP tự ném `Fatal Error: Class ... contains 1 abstract method` nếu 1 class trong `config('playlist.itemables')` quên implement. Không còn là "giới hạn ngôn ngữ chấp nhận được", mà là lỗi bắt được ngay lúc autoload thay vì runtime exception/kết quả rỗng im lặng khi thêm loại nội dung thứ 3 |
| **`config('playlist.itemables')` mang theo cả eager-load relation, không chỉ FQCN** | Mỗi entry là `['model' => FQCN, 'with' => [...quan hệ cần eager-load khi hiển thị public...]]` — dùng để build cả `morphMap()` (§4.7) lẫn `MorphTo::morphWith()` (§7.4) | Post cần eager-load `translations` (constrained theo `default_locale`+`published`) để tránh N+1 khi hiển thị nhiều item bài viết trong 1 playlist; nếu để `Modules/Playlist` tự đoán quan hệ cần load cho từng loại thì lại phá nguyên tắc "không phụ thuộc cứng" — khai báo ở config (do chính module Video/Post tự biết mình cần load gì) giữ được cả 2 mục tiêu |
| **`sort_order` khi ATTACH item = tự động `max+1`, không bắt nhập tay** | `AddItemToPlaylistAction` tự tính `($playlist->items()->max('sort_order') ?? 0) + 1` — người dùng chỉ "Thêm vào playlist", không phải điền số thứ tự ngay lúc thêm | Yêu cầu nhập tay `sort_order` (§0 dòng "Sắp xếp") chỉ áp dụng cho hành động **sắp xếp lại sau đó** (`ReorderPlaylistItemsAction`, form liệt kê từng dòng kèm ô số) — bắt nhập số ngay lúc thêm mới là ma sát UX không cần thiết, hành vi mặc định hợp lý nhất là "thêm vào cuối danh sách" |
| **`SearchPlaylistableItemsAction` lọc `isPlaylistCardVisible()` + loại trừ item đã có trong playlist** | Action nhận thêm `?Playlist $playlist` — nếu truyền vào, loại khỏi kết quả những `(type,id)` đã tồn tại trong `$playlist->items`; luôn lọc `isPlaylistCardVisible()` sau khi hydrate, kể cả khi scope DB đã lọc tương tự | Phòng thủ lớp 2 (cùng tinh thần `Video::getWatchUrlAttribute()` tự kiểm tra lại host dù đã validate khi lưu) — tránh trường hợp `scopeSearchablePlaylistItems` của 1 loại nào đó lệch điều kiện với `isPlaylistCardVisible()` do sửa sau này ở 2 chỗ khác nhau. Loại trừ item đã có tránh hiển thị kết quả mà bấm vào sẽ báo lỗi "đã tồn tại" — trước đó review chỉ rejects ở tầng `Add`, nay chặn sớm hơn ở tầng hiển thị picker |
| **Badge cảnh báo item ẩn/mồ côi ở trang SỬA playlist (không phải banner tổng)** | Mỗi dòng item trong `admin/playlists/edit.blade.php` hiển thị badge "Ẩn"/"Nguồn đã xoá" khi `$item->resolved_itemable === null \|\| ! $item->resolved_itemable->isPlaylistCardVisible()` | Chi phí thấp (chỉ 1 điều kiện Blade có sẵn dữ liệu đã load), giá trị vận hành cao — Ops phát hiện ngay playlist đang "rỗng thầm lặng" một phần thay vì phải đoán qua trang public. Banner tổng "N item đang ẩn" toàn hệ thống vẫn để ngoài phạm vi v1 (§9) |
| **`PlaylistItem` có `LogsActivity`** | `PlaylistItem extends Model { use LogsActivity; }`, log khi attach/detach (create/delete) — khác `Playlist` (log cả sửa thông tin), `PlaylistItem` chỉ có ý nghĩa log ở 2 sự kiện tạo/xoá | Trước đó chỉ `Playlist` có activity log — 1 hành vi biên tập quan trọng ("ai đã thêm/gỡ video X khỏi playlist Y") lại không có vết tích nếu chỉ dựa vào log của `Playlist` (log đó không bắt được thay đổi trên bảng con) |
| **`playlist_items.cascadeOnDelete()` chỉ áp dụng khi `forceDelete()`, không mâu thuẫn với soft-delete** | `Playlist` dùng `SoftDeletes` — xoá thường chỉ set `deleted_at` (UPDATE), **không** kích hoạt FK cascade của DB (cascade chỉ chạy trên `DELETE` thật). `playlist_items` của 1 playlist đã soft-delete vẫn còn nguyên trong DB nhưng không bao giờ lộ ra ngoài vì `Playlist::items()` luôn truy vấn qua playlist cha (đã bị global scope `SoftDeletes` ẩn) | Làm rõ để tránh hiểu nhầm khi review là "cascadeOnDelete mâu thuẫn với triết lý giữ-hàng-khi-xoá-mềm" — thực ra 2 cơ chế không giao nhau: cascade chỉ có tác dụng ở đường xoá cứng (`Playlist::forceDelete()`), lúc đó dọn luôn `playlist_items` là đúng (không còn playlist cha để trỏ vào) |
| **Bài viết đưa vào playlist PHẢI có bản dịch published ở `post.default_locale`** | `PostArticle::isPlaylistCardVisible()` kiểm tra `default_locale_translation !== null` — chặn từ `AddItemToPlaylistAction` VÀ lọc khỏi kết quả tìm kiếm (§0 dòng trên) | `PublicArticleController` chỉ phục vụ đúng 1 locale mặc định — bài chỉ có bản dịch locale khác sẽ không có URL public nào, trỏ vào sẽ 404 nếu không chặn từ gốc |
| **Thay đổi `post.default_locale` sau khi đã có dữ liệu là sự kiện vận hành hiếm, không có migration path tự động** | `isPlaylistCardVisible()`/`getPlaylistCardUrl()` đọc `config('post.default_locale')` **động mỗi lần gọi**, không cache — nếu giá trị này đổi, các bài chỉ có bản dịch ở locale cũ sẽ tự động biến mất khỏi mọi playlist công khai (đúng logic, không phải bug), nhưng **không có cảnh báo chủ động** nào báo Ops biết điều đó vừa xảy ra | Ghi rõ ràng buộc vận hành: đây là quyết định cấu hình cấp toàn site (ảnh hưởng cả `Modules/Post`, không riêng Playlist), nằm ngoài thẩm quyền xử lý của 1 module — nếu đổi `post.default_locale`, người thực hiện thao tác đó cần tự chạy rà soát thủ công (hoặc dùng lệnh audit orphan ở §9 nếu được làm sau) |
| **Đơn vị nền tảng, không tenant / Không quy trình duyệt / Sắp xếp playlist nhập tay** | Giữ nguyên như bản thảo đầu | Nhất quán Video/Banner/Post (đã bỏ `organization_id`) |
| **Quyền hạn: `playlist.manage` riêng (Lớp B)** | Seed qua `PlaylistPermissionSeeder`, cấp `platform_ops` + `platform_content_head` — không mượn `video.manage` | Tránh 1 tài khoản chỉ cần quản video lại vô tình quản được playlist chứa bài viết, và ngược lại |
| **Không dedup cứng ở DB, không ràng buộc trùng item** | Validate tránh trùng ở tầng Action + UI (picker loại trừ item đã có, §0 dòng trên), không có `unique` constraint DB | Giữ đơn giản đúng tinh thần Video §0; UI đã chặn từ nguồn nên rủi ro trùng thực tế thấp, không đáng đổi lấy độ phức tạp của composite unique trên bảng polymorphic |

---

## 1. Giới thiệu & Mục tiêu

Cổng thông tin hiện có 2 nguồn nội dung tách rời hoàn toàn: `Modules/Video` (thư viện video phẳng, 1 trang lưới `/videos`) và `Modules/Post` (bài viết, phân theo category/tag). Không có cách nào để **biên tập 1 bộ sưu tập nội dung theo chủ đề trộn cả video lẫn bài viết** — ví dụ "Chuỗi nội dung: Ăn dặm cho bé 6 tháng" muốn gồm 2 video hướng dẫn + 3 bài viết chuyên sâu, hiển thị theo đúng 1 thứ tự biên tập.

Module **Playlist** giải quyết bằng 1 bảng `playlists` + 1 bảng quan hệ đa hình `playlist_items` (trỏ tới Video hoặc PostArticle qua `morphTo()`), với:

- Trang quản trị: tạo/sửa playlist, tìm và thêm item qua 1 ô tìm kiếm hợp nhất, sắp thứ tự bằng `sort_order`, thấy ngay badge cảnh báo nếu 1 item đã ẩn/mồ côi.
- Trang công khai: `/playlists` + `/playlists/{slug}` — card từng item render đúng theo loại (video mở lightbox, bài viết dẫn sang trang riêng), SEO đầy đủ (OG + JSON-LD).

**Nguyên tắc thiết kế cốt lõi:** `Modules/Playlist` không biết trước Video/Post là gì ở tầng lõi — nó định nghĩa 1 hợp đồng (`PlaylistableContract`, bắt buộc qua interface PHP thật, không chỉ quy ước) + 1 bảng cấu hình; Video/Post tự "đăng ký tham gia". Thêm loại nội dung thứ 3 sau này không đổi schema, chỉ thêm config + implement contract — và nếu implement thiếu, PHP báo lỗi ngay lúc load class thay vì để lỗi trôi tới runtime.

---

## 2. Khảo sát điểm vào (entry points)

| Vị trí | Route | Ghi chú |
|---|---|---|
| Sidebar dashboard (mục quản trị mới) | `backend.playlist.items.index` | Hiển thị nếu `@can('playlist.manage')`, cùng pattern Video/Banner |
| Trang công khai — danh sách playlist | `GET /playlists` → `playlist.public.index` | Layout dùng chung `layouts/frontend.blade.php` |
| Trang công khai — chi tiết 1 playlist | `GET /playlists/{slug}` → `playlist.public.show` | Card polymorphic qua `PlaylistableContract`, SEO đầy đủ (§7.3) |
| API nội bộ cho bảng Tabulator ở trang quản trị | `GET backend/api/playlists/items` → `backend.api.playlists.items` | Cùng pattern `VideoApiController`/`BannerApiController` |
| API tìm kiếm hợp nhất khi thêm item | `GET backend/api/playlists/{playlist}/searchable-items?q=...` → `backend.api.playlists.searchable-items` | Trả mảng chuẩn hoá trộn Video + PostArticle, đã loại item khả dụng nhưng không hiển thị được hoặc đã có trong playlist (§6.3) |

Không có vị trí nhúng Playlist vào `Modules/Post` (content block) hay `Modules/Event` ở v1 — ngoài phạm vi (§9).

---

## 3. Kiến trúc dữ liệu

### 3.1 ERD

```
Playlist
  ├─ uuid
  ├─ name (string, 255)
  ├─ slug (string, 255, unique)
  ├─ description (text, nullable)
  ├─ cover_image_url (string, 2048, nullable)      — nếu trống, trang public fallback thumbnail item hợp lệ đầu tiên (§4.1)
  ├─ meta_title (string, 255, nullable)             — SEO, fallback `name` (§0/§7.3)
  ├─ meta_description (string, 500, nullable)       — SEO, fallback `description` (§0/§7.3)
  ├─ sort_order (unsigned smallint)
  ├─ is_active (bool)
  ├─ created_by, updated_by, timestamps, soft delete
  └─ items() : HasMany → PlaylistItem, orderBy sort_order

PlaylistItem   (LogsActivity — log attach/detach)
  ├─ playlist_id (FK → playlists, cascadeOnDelete — chỉ có tác dụng khi forceDelete(), §0)
  ├─ itemable_type (string — alias qua morphMap, KHÔNG lưu FQCN thô)
  ├─ itemable_id (unsigned bigint)
  ├─ sort_order (unsigned smallint)                 — thứ tự RIÊNG trong playlist này, mặc định max+1 khi attach (§0)
  ├─ timestamps
  └─ itemable() : MorphTo → Video | PostArticle (mở rộng được qua config, §4.6)
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

    $table->string('meta_title', 255)->nullable();
    $table->string('meta_description', 500)->nullable();

    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);

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

    // cascadeOnDelete CHỈ kích hoạt khi Playlist::forceDelete() (xoá cứng thật) — soft-delete
    // mặc định (set deleted_at) không chạm tới bảng này, không mâu thuẫn với "giữ hàng khi xoá
    // mềm" (§0). Sau forceDelete(), dọn luôn playlist_items là đúng vì không còn playlist cha.
    $table->foreignId('playlist_id')->constrained('playlists')->cascadeOnDelete();

    // Không có FK thật (itemable_id trỏ 1 trong nhiều bảng khác nhau tuỳ itemable_type) — item
    // "mồ côi" (nguồn đã xoá/ẩn) được lọc ở tầng Query khi đọc, không ở tầng DB (§0/§5.2).
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

    public function getEffectiveMetaTitleAttribute(): string
    {
        return $this->meta_title ?: $this->name;
    }

    public function getEffectiveMetaDescriptionAttribute(): ?string
    {
        return $this->meta_description ?: $this->description;
    }

    /**
     * Danh sách item ĐÃ LỌC hợp lệ (bỏ mồ côi/ẩn) — dùng ở MỌI nơi hiển thị công khai, KHÔNG chỉ
     * lấy $this->items thẳng ra (đó là danh sách THÔ, dùng cho trang admin để còn thấy được item
     * cần cảnh báo, §0/§6.7). Đây là điểm hội tụ DUY NHẤT để tránh mỗi view/handler tự viết lại
     * `?->`/`isPlaylistCardVisible()` rải rác — giảm rủi ro 1 chỗ quên gây lỗi production (đã bị
     * flag ở review nội bộ v1.0).
     *
     * @return \Illuminate\Support\Collection<int, \Modules\Playlist\Contracts\PlaylistableContract>
     */
    public function getVisibleItemablesAttribute(): \Illuminate\Support\Collection
    {
        return $this->items
            ->map(fn (PlaylistItem $item) => $item->visible_itemable)
            ->filter()
            ->values();
    }

    /** Ảnh đại diện — cover_image_url tự nhập, fallback thumbnail item hợp lệ đầu tiên. */
    public function getEffectiveCoverImageUrlAttribute(): ?string
    {
        return $this->cover_image_url ?: $this->visible_itemables->first()?->getPlaylistCardThumbnailUrl();
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
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class PlaylistItem extends Model
{
    use LogsActivity;

    protected $fillable = ['playlist_id', 'itemable_type', 'itemable_id', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    /**
     * Chỉ log lúc TẠO/XOÁ (attach/detach) — 1 item không có "sửa thông tin" nào khác ngoài
     * sort_order (đổi qua ReorderPlaylistItemsAction hàng loạt, không đáng ghi log từng dòng).
     * logOnlyDirty() vẫn giữ cho nhất quán codebase dù ít khi có update thực sự trên bản ghi này.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    /**
     * $itemable_type là ALIAS đã đăng ký qua Relation::morphMap() (§4.7, ví dụ "video",
     * "post_article") — KHÔNG phải FQCN thô. morphTo() eager-load ('itemable') tự gom các hàng
     * theo từng loại rồi query 1 lần/loại (hành vi mặc định của Eloquent).
     */
    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Ép kiểu về contract — null nếu item mồ côi (nguồn đã xoá cứng). */
    public function getResolvedItemableAttribute(): ?PlaylistableContract
    {
        return $this->itemable instanceof PlaylistableContract ? $this->itemable : null;
    }

    /**
     * Điểm hội tụ null-discipline: null nếu mồ côi HOẶC còn tồn tại nhưng đang ẩn/chưa publish
     * (`isPlaylistCardVisible() === false`). Mọi nơi hiển thị công khai PHẢI đọc qua property
     * này thay vì tự gọi resolved_itemable + isPlaylistCardVisible() riêng lẻ (§0).
     */
    public function getVisibleItemableAttribute(): ?PlaylistableContract
    {
        $itemable = $this->resolved_itemable;

        return $itemable?->isPlaylistCardVisible() ? $itemable : null;
    }
}
```

### 4.3 `Modules\Playlist\Contracts\PlaylistableContract`

```php
namespace Modules\Playlist\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Hợp đồng mà mọi model muốn "tham gia" làm item của Playlist phải implement (§0). Video/Post
 * implement trực tiếp trên Model của chúng (§4.4/§4.5) — Modules/Playlist không cần biết
 * Video/Post là gì, chỉ gọi qua interface này.
 *
 * scopeSearchablePlaylistItems() CỐ Ý được khai trong interface dù về mặt Eloquent nó là 1
 * "local scope" — bản chất chỉ là 1 public instance method thường (tiền tố `scope` chỉ có ý
 * nghĩa với Eloquent query builder qua __call magic), nên khai báo được như method bình thường.
 * Nhờ vậy, 1 class trong config('playlist.itemables') QUÊN implement sẽ bị PHP báo
 * `Fatal error: Class ... contains 1 abstract method` NGAY khi autoload — bắt lỗi sớm hơn hẳn
 * so với để runtime tự __call() báo "method không tồn tại" hoặc tệ hơn là kết quả rỗng im lặng.
 */
interface PlaylistableContract
{
    public function getPlaylistCardTitle(): string;

    public function getPlaylistCardDescription(): ?string;

    public function getPlaylistCardThumbnailUrl(): ?string;

    /** Link "an toàn" luôn dùng được — trang chi tiết/watch_url. */
    public function getPlaylistCardUrl(): string;

    /**
     * URL nhúng lightbox — CHỈ Video trả khác null (§0). Bài viết trả null → view điều hướng
     * sang getPlaylistCardUrl() thay vì mở modal.
     */
    public function getPlaylistCardEmbedUrl(): ?string;

    /** Nhãn phân loại hiển thị ở badge — "Video" / "Bài viết". */
    public function getPlaylistCardTypeLabel(): string;

    /**
     * Có còn hợp lệ để hiển thị công khai không (is_active/published). Query công khai VÀ
     * SearchPlaylistableItemsAction (phòng thủ lớp 2, §0) đều lọc qua method này.
     */
    public function isPlaylistCardVisible(): bool;

    /**
     * Scope tìm kiếm dùng bởi ô tìm kiếm hợp nhất (§6.3) — mỗi model tự biết search đúng cột/
     * quan hệ của mình (Video theo `name`, PostArticle qua `translations.title`).
     */
    public function scopeSearchablePlaylistItems(Builder $query, string $keyword): void;
}
```

### 4.4 Implement ở `Modules\Video\Models\Video` (thay đổi cross-module)

> Thay đổi DUY NHẤT cần chạm vào `Modules/Video` — thêm `implements PlaylistableContract` + 6 method ngắn, tái dùng nguyên accessor đã có, không đổi logic nghiệp vụ hiện tại.

```php
use Illuminate\Database\Eloquent\Builder;
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

    public function scopeSearchablePlaylistItems(Builder $query, string $keyword): void
    {
        $query->active()->where('name', 'like', "%{$keyword}%");
    }
}
```

### 4.5 Implement ở `Modules\Post\Models\PostArticle` (thay đổi cross-module)

```php
use Illuminate\Database\Eloquent\Builder;
use Modules\Playlist\Contracts\PlaylistableContract;

class PostArticle extends Model implements HasMedia, PlaylistableContract
{
    // ... giữ nguyên toàn bộ code hiện tại ...

    /**
     * Bản dịch DUY NHẤT được phép dùng cho mọi output công khai xuyên-module (§0 — chống 404
     * vì PublicArticleController chỉ phục vụ đúng locale này). Đọc từ collection `translations`
     * ĐÃ ĐƯỢC EAGER-LOAD đúng điều kiện qua config('playlist.itemables.post_article.with') —
     * KHÔNG tự query lại ở đây (tránh N+1 khi hiển thị nhiều PostArticle trong 1 playlist, §7.4).
     */
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

    public function scopeSearchablePlaylistItems(Builder $query, string $keyword): void
    {
        $query->whereHas('translations', fn ($q) => $q
            ->where('locale', config('post.default_locale'))
            ->where('status', TranslationStatus::Published)
            ->where('title', 'like', "%{$keyword}%"));
    }
}
```

### 4.6 `Modules/Playlist/config/config.php`

```php
return [
    'name' => 'Playlist',

    // Mỗi entry: 'model' (FQCN thật, NGUỒN DUY NHẤT cho morphMap §4.7) + 'with' (quan hệ cần
    // eager-load khi hiển thị công khai, dùng cho MorphTo::morphWith() §7.4 — TRÁNH N+1 mà
    // KHÔNG buộc Modules/Playlist phải tự biết Post cần load 'translations'; chính Post khai báo
    // nhu cầu của mình ở đây). Thêm loại nội dung mới chỉ cần thêm 1 entry + 1 class implement
    // PlaylistableContract, KHÔNG migrate lại playlist_items.
    'itemables' => [
        'video' => [
            'model' => \Modules\Video\Models\Video::class,
            'with'  => [],
        ],
        'post_article' => [
            'model' => \Modules\Post\Models\PostArticle::class,
            'with'  => ['translations'],
        ],
    ],

    // Số kết quả tối đa MỖI nguồn khi tìm kiếm hợp nhất ở modal "Thêm item" (§6.3) — tránh 1
    // nguồn có nhiều bản ghi khớp từ khoá lấn át hoàn toàn nguồn còn lại.
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

        // Cùng quyết định ApprovalServiceProvider::boot() — merge: true, KHÔNG enforceMorphMap().
        Relation::morphMap(
            collect(config('playlist.itemables', []))->map(fn (array $cfg) => $cfg['model'])->all(),
            merge: true,
        );
    }
}
```

---

## 5. Business rules

### 5.1 Thêm item

```php
namespace Modules\Playlist\Features\PlaylistManagement\Actions;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Playlist\Contracts\PlaylistableContract;
use Modules\Playlist\Models\Playlist;
use Modules\Playlist\Models\PlaylistItem;

class AddItemToPlaylistAction
{
    use AsAction;

    public function handle(Playlist $playlist, string $itemableType, int $itemableId): PlaylistItem
    {
        $modelClass = config("playlist.itemables.{$itemableType}.model");

        if (! $modelClass) {
            throw ValidationException::withMessages(['itemable_type' => 'Loại nội dung không hợp lệ.']);
        }

        /** @var PlaylistableContract|null $itemable */
        $itemable = $modelClass::find($itemableId);

        if (! $itemable instanceof PlaylistableContract || ! $itemable->isPlaylistCardVisible()) {
            throw ValidationException::withMessages([
                'itemable_id' => 'Nội dung này hiện đang ẩn hoặc chưa xuất bản, không thể thêm vào playlist.',
            ]);
        }

        // Mặc định "thêm vào cuối danh sách" — KHÔNG bắt nhập sort_order lúc thêm mới (§0).
        // Sắp xếp lại vị trí là thao tác riêng, sau đó, qua ReorderPlaylistItemsAction.
        $nextSortOrder = ($playlist->items()->max('sort_order') ?? 0) + 1;

        return $playlist->items()->create([
            'itemable_type' => $itemableType,
            'itemable_id'   => $itemableId,
            'sort_order'    => $nextSortOrder,
        ]);
    }
}
```

### 5.2 Xoá & sắp xếp lại item

- **Xoá**: `RemoveItemFromPlaylistAction::handle(PlaylistItem $item)` — xoá cứng hàng `playlist_items` (đây chỉ là 1 liên kết, không phải nội dung gốc); `LogsActivity` trên `PlaylistItem` tự ghi lại sự kiện xoá.
- **Sắp xếp lại**: `ReorderPlaylistItemsAction::handle(Playlist $playlist, array $orderedItemIds)` — cập nhật `sort_order` hàng loạt theo thứ tự mảng truyền vào; validate mọi ID trong mảng đều thuộc đúng `$playlist` (chặn 1 ID của playlist khác lọt vào request thủ công) trước khi update, nếu có ID lạ → `ValidationException`, không âm thầm bỏ qua.

### 5.3 Lọc item hợp lệ khi đọc

`Playlist::items` (thô) dùng cho **admin** — cần thấy cả item mồ côi/ẩn để hiển thị badge cảnh báo (§6.7). `Playlist::visible_itemables` (§4.1) dùng cho **mọi nơi công khai** — đã lọc sẵn qua `PlaylistItem::visible_itemable` (§4.2), không nơi nào khác được tự ý gọi `?->isPlaylistCardVisible()` rời rạc.

Không sửa `sort_order`/xoá hàng `playlist_items` khi lọc — chỉ ẩn khỏi kết quả trả về; khi nguồn được bật lại, item tự xuất hiện lại đúng vị trí cũ.

### 5.4 Ảnh đại diện & bật/tắt playlist

`Playlist::effective_cover_image_url` (§4.1) ưu tiên `cover_image_url` tự nhập, fallback `visible_itemables->first()`. `sort_order`/`is_active` của chính playlist hoạt động như Video §5.4 (ẩn khỏi public, vẫn thấy ở admin).

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
│   │   │   │   ├── TogglePlaylistActiveAction.php
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

### 6.2 `PlaylistData` & validate

Theo đúng pattern `VideoAdminController::validated()` — validate ở Controller, build DTO sau:

```php
private function validated(Request $request): array
{
    return $request->validate([
        'name'              => ['required', 'string', 'max:255'],
        'slug'              => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('playlists', 'slug')->ignore($this->playlist)],
        'description'       => ['nullable', 'string', 'max:2000'],
        'cover_image_url'   => ['nullable', 'url', 'max:2048'],
        'meta_title'        => ['nullable', 'string', 'max:255'],
        'meta_description'  => ['nullable', 'string', 'max:500'],
        'sort_order'        => ['nullable', 'integer', 'min:0'],
        'is_active'         => ['boolean'],
    ]);
}
```

`PlaylistData` (Spatie Laravel Data) chỉ hydrate dữ liệu đã validate, không validate lại — cùng nguyên tắc `VideoData`/`BannerData`.

### 6.3 Routes

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

### 6.4 `SearchPlaylistableItemsAction` — trái tim của ô tìm kiếm hợp nhất

```php
namespace Modules\Playlist\Features\PlaylistManagement\Actions;

use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Playlist\Contracts\PlaylistableContract;
use Modules\Playlist\Models\Playlist;

class SearchPlaylistableItemsAction
{
    use AsAction;

    /**
     * Gọi lần lượt từng nguồn khai trong config('playlist.itemables') qua chính
     * scopeSearchablePlaylistItems() mà PlaylistableContract bắt buộc (§4.3) — Action này KHÔNG
     * viết SQL trực tiếp lên bảng cụ thể, chỉ điều phối + chuẩn hoá + lọc kết quả.
     *
     * $playlist (nullable): nếu truyền vào, loại khỏi kết quả các (type,id) ĐÃ có trong
     * playlist đó — tránh hiển thị trong picker 1 item mà bấm vào sẽ không thêm được nữa.
     *
     * @return Collection<int, array{type: string, id: int, title: string, thumbnail_url: ?string, type_label: string}>
     */
    public function handle(string $keyword, ?Playlist $playlist = null): Collection
    {
        $limit = config('playlist.search_limit_per_type', 10);

        $existingKeys = $playlist
            ? $playlist->items->map(fn ($item) => "{$item->itemable_type}:{$item->itemable_id}")
            : collect();

        $results = collect();

        foreach (config('playlist.itemables', []) as $typeKey => $cfg) {
            /** @var class-string<PlaylistableContract> $modelClass */
            $modelClass = $cfg['model'];

            $matches = $modelClass::query()
                ->searchablePlaylistItems($keyword)
                ->limit($limit)
                ->get();

            foreach ($matches as $item) {
                // Phòng thủ lớp 2 (§0) — dù scope DB thường đã lọc active/published tương tự,
                // kiểm tra lại qua contract để không lệch nếu 1 trong 2 nơi đổi điều kiện sau này.
                if (! $item->isPlaylistCardVisible()) {
                    continue;
                }

                if ($existingKeys->contains("{$typeKey}:{$item->getKey()}")) {
                    continue;
                }

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

`PlaylistApiController::searchableItems()`:

```php
public function searchableItems(Request $request, Playlist $playlist, SearchPlaylistableItemsAction $search): JsonResponse
{
    $this->authorize('update', $playlist);

    $request->validate(['q' => ['required', 'string', 'min:2', 'max:255']]);

    return response()->json(['data' => $search->handle($request->string('q'), $playlist)]);
}
```

### 6.5 Permission (`PlaylistPermissionSeeder`)

Sao chép nguyên khuôn `VideoPermissionSeeder` (§0 — permission riêng `playlist.manage`, cấp `platform_ops` + `platform_content_head`, sync `super-admin`).

### 6.6 Chính sách (`PlaylistPolicy`)

`authorizeResource(Playlist::class, 'playlist')` áp policy cho toàn bộ 5 action CRUD; `toggleActive`/`attachItem`/`detachItem`/`reorderItems` không nằm trong 7 ability RESTful mặc định nên gọi `authorize('update', $playlist)` thủ công trong từng method (cùng cách `VideoAdminController::toggleActive()` làm) — **không có endpoint nào bỏ sót `authorize()`**, đây là điểm bắt buộc kiểm tra ở test (§8).

### 6.7 Badge cảnh báo item ẩn/mồ côi ở trang sửa

`admin/playlists/edit.blade.php` lặp qua `$playlist->items` (danh sách THÔ, không phải `visible_itemables`):

```blade
@foreach($playlist->items as $item)
    <tr>
        <td>{{ $item->resolved_itemable?->getPlaylistCardTitle() ?? '(Nội dung đã bị xoá)' }}</td>
        <td>
            @if(! $item->visible_itemable)
                <span class="badge badge-warning">
                    {{ $item->resolved_itemable ? 'Đang ẩn' : 'Nguồn đã xoá' }}
                </span>
            @endif
        </td>
        {{-- ... sort_order input, nút xoá ... --}}
    </tr>
@endforeach
```

Banner tổng "playlist X có N item đang ẩn" (quét toàn bộ playlist) để ngoài phạm vi v1 (§9) — badge từng dòng ở đây đã đủ để Ops phát hiện khi đang sửa trực tiếp playlist đó.

---

## 7. Trang công khai

### 7.1 `/playlists` — danh sách

Lưới card playlist (`effective_cover_image_url`, `name`, `description` rút gọn, `visible_itemables->count()`). Empty state nếu chưa có playlist active nào.

### 7.2 `/playlists/{slug}` — chi tiết

```blade
@php $visibleItems = $playlist->visible_itemables; @endphp

@if($visibleItems->isEmpty())
    {{-- Playlist còn active nhưng MỌI item đã bị ẩn/xoá nguồn — tránh trang trắng gây hiểu nhầm
         lỗi, cùng cách Video xử lý empty state ở /videos. --}}
    <div class="text-center py-16 text-base-content/60">
        <p>Playlist này hiện chưa có nội dung khả dụng.</p>
    </div>
@else
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
@endif
```

Lightbox modal dùng chung 1 instance đặt ngoài vòng lặp — copy nguyên cấu trúc Alpine đã kiểm chứng ở `Modules/Video/resources/views/public/index.blade.php`.

### 7.3 SEO

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

JSON-LD — `CollectionPage` lồng `ItemList` (schema.org không có type chuyên biệt cho "playlist trộn nhiều loại nội dung"; `ItemList` là type tổng quát đúng ngữ nghĩa, nhất quán với AEO/GEO đã dùng cho Post — xem [[project_post_aeo_geo_structured_data]]). Khi `$visibleItems` rỗng, vẫn phát `CollectionPage` nhưng `itemListElement: []` — không bỏ hẳn script JSON-LD (giữ nhất quán structured data có mặt trên mọi trang, tránh crawler thấy trang "biến mất" bất thường):

```php
[
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $playlist->effective_meta_title,
    'description' => $playlist->effective_meta_description, // có thể null — OK với schema.org
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

### 7.4 Eager-load bắt buộc (chống N+1)

`GetPlaylistForPublicHandler`:

```php
$playlist = Playlist::active()
    ->where('slug', $slug)
    ->with(['items' => fn ($q) => $q->orderBy('sort_order')])
    ->firstOrFail();

$playlist->load(['items.itemable' => function (MorphTo $morphTo) {
    // morphWith() — build TỪ CONFIG, không hard-code Video::class/PostArticle::class ở đây,
    // giữ nguyên nguyên tắc "không phụ thuộc cứng" (§0/§4.6) dù đang tối ưu N+1 cho từng loại.
    $morphTo->morphWith(
        collect(config('playlist.itemables'))
            ->mapWithKeys(fn (array $cfg) => [$cfg['model'] => $cfg['with']])
            ->all()
    );
}]);
```

Nhờ đó `PostArticle::translations` được load sẵn 1 lần cho toàn bộ item bài viết trong playlist — `getDefaultLocaleTranslationAttribute()` (§4.5) chỉ lọc trong collection đã có sẵn trong bộ nhớ, không query lại.

`/playlists` (trang danh sách) cần cùng cấu trúc `with()` này cho MỌI playlist trả về (không chỉ 1 playlist), vì `effective_cover_image_url` gọi `visible_itemables->first()` — thiếu eager-load ở đây là nguồn N+1 dễ bị bỏ sót nhất (nhiều playlist × nhiều item mỗi cái).

---

## 8. Kiểm thử bắt buộc trước khi coi là xong

**Đã có ở bản thảo đầu:**
- `PlaylistItem::itemable` resolve đúng cả 2 loại qua `morphMap`.
- `AddItemToPlaylistAction` từ chối thêm: video `is_active=false`, bài viết chưa có bản dịch published ở `default_locale`, `itemableType` không nằm trong config (input giả mạo).
- Trang công khai KHÔNG render item mồ côi (nguồn đã xoá cứng).
- Trang công khai hiển thị đúng khi playlist có **cả 2 loại item trộn lẫn** theo đúng `sort_order` chung.
- `SearchPlaylistableItemsAction` trả đúng giới hạn `search_limit_per_type` cho từng nguồn.
- Xoá mềm `Video`/`PostArticle` → `PlaylistItem::itemable` trả `null`, không throw exception ở bất kỳ nơi gọi `getPlaylistCard*()`.

**Bổ sung sau review v1.1:**
- `PlaylistPolicy` áp dụng đúng trên **mọi** endpoint, kể cả 4 action ngoài RESTful mặc định (`toggleActive`/`attachItem`/`detachItem`/`reorderItems`) — test riêng cho user không có `playlist.manage` bị 403 ở từng route.
- Thêm trùng 1 item vào cùng playlist 2 lần liên tiếp (không đi qua UI, gọi thẳng Action 2 lần) — xác nhận hành vi đúng như §0 (được phép, không constraint DB) và không gây lỗi.
- `ReorderPlaylistItemsAction` với 1 ID không thuộc playlist (hoặc không tồn tại) trong mảng truyền vào → `ValidationException`, không âm thầm bỏ qua hay cập nhật nhầm playlist khác.
- `effective_cover_image_url` khi item ĐẦU TIÊN theo `sort_order` là item mồ côi/ẩn — phải fallback đúng sang item hợp lệ TIẾP THEO, không trả `null` chỉ vì phần tử đầu tiên trong mảng thô không hợp lệ.
- JSON-LD khi `meta_description`/`description` đều `null` — không được ném lỗi khi `json_encode`, và không in ra khoá `description: null` gây nhiễu (cân nhắc `array_filter` trước encode).
- Tình huống "race" search → attach: item được search picker trả về hợp lệ, nhưng bị người khác tắt `is_active`/unpublish NGAY TRƯỚC khi request `attach-item` tới server — `AddItemToPlaylistAction` vẫn phải tự re-check `isPlaylistCardVisible()` tại thời điểm ghi (đã đúng theo code §5.1, test xác nhận không tin dữ liệu cũ từ phía client).
- `scopeSearchablePlaylistItems` thiếu ở 1 class mới thêm vào `config('playlist.itemables')` trong lúc phát triển (không phải test hành vi, mà xác nhận **PHP tự chặn từ lúc autoload** — viết 1 test dựng 1 class giả không implement đủ contract, assert `Error` được ném ra).

---

## 9. Ngoài phạm vi (out of scope) — ghi rõ để tránh hiểu nhầm khi review

- **Thêm loại nội dung thứ 3+ (Product, Event, RealEstate...)** — schema đã sẵn sàng mở rộng (§0/§4.6), v1 chỉ wiring Video + Post theo yêu cầu đã xác nhận.
- **Kéo-thả sắp xếp (drag-and-drop reorder)** cho cả playlist lẫn item — v1 chỉ có input số `sort_order` nhập tay khi sắp xếp lại (không phải lúc attach, §0), cùng quyết định Video.
- **Nút "di chuyển lên/xuống" (move up/down) cho từng item** — tiện ích UX nhỏ có thể thêm sau mà không đổi schema, chưa cấp thiết khi đã có `ReorderPlaylistItemsAction` nhập hàng loạt.
- **Playlist lồng playlist (nested)** — 1 playlist không thể chứa 1 playlist khác làm item.
- **Đếm lượt xem playlist** — không có yêu cầu đo lường nào ở v1.
- **Danh mục/gắn thẻ cho chính Playlist** — v1 là danh sách phẳng các playlist.
- **Banner cảnh báo tổng "N playlist đang có item ẩn" trên toàn hệ thống** — v1 chỉ có badge từng dòng khi đang mở đúng trang sửa playlist đó (§6.7), chưa có dashboard tổng hợp.
- **Lệnh audit/dọn orphan tự động (`playlist:audit-orphans` hay tương tự)** — hữu ích khi `post.default_locale` đổi (§0) hoặc dữ liệu nguồn bị xoá hàng loạt, nhưng chưa có yêu cầu vận hành thật ở v1; rà soát thủ công qua badge §6.7 là đủ cho quy mô hiện tại.
- **Giao diện khôi phục bản ghi đã xoá mềm (soft-delete restore UI)** — cùng quyết định Video/Banner, thao tác qua DB/tinker ở v1.
