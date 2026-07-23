# Related Posts Engine — Gợi ý bài viết liên quan thông minh
**Đặc tả Kỹ thuật Chi tiết — Sẵn sàng Triển khai**

**Phiên bản:** 1.0
**Ngày:** 23/07/2026
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions
**Vị trí:** Feature mới **bên trong `Modules/Post`** (không phải module NWIDART riêng) — `Modules/Post/app/Features/RelatedPosts/`
**Module liên quan:** `Modules/Post` (nguồn dữ liệu bài viết/category/tag, nơi duy nhất render), Scout/Meilisearch (đã tích hợp sẵn cho Post, không bắt buộc dùng ở v1)

> **Lịch sử phiên bản**
> - **v1.0** — thuật toán tính điểm liên quan theo 4 tín hiệu (category, tag, hành vi đồng-xem, độ phổ biến), tính realtime lúc request + cache TTL, render 1 khối "Bài viết liên quan" ở cuối trang chi tiết bài viết công khai.
> - **v1.0.1** — bổ sung §10 (Rủi ro & Đánh giá thực tiễn), không đổi thiết kế/schema.
> - **v1.0.2** — bổ sung §10.1 (ngưỡng theo dõi cụ thể cho self-join + ghi nhận thiếu dedup ở `RecordArticleViewEventAction` là đánh đổi có chủ đích), không đổi thiết kế/schema.

---

## 0. Quyết định đã chốt

| Chủ đề | Hiện trạng codebase | Quyết định spec này | Lý do |
|---|---|---|---|
| **Module mới hay Feature trong `Modules/Post`?** | `Modules/Post/app/Features/` đã có 5 feature con: `ArticleAuthoring`, `CategoryManagement`, `PublicReading`, `TagManagement`, `VersionHistory` — đều là năng lực **nội tại** của Post, không dùng chéo module khác | Làm **Feature mới `RelatedPosts`** trong `Modules/Post`, KHÔNG tạo module NWIDART riêng (khác Banner/Ocop/Page/CoreIdeaExtractor) | Khác Banner (dùng chung cho cả `Modules/Post` VÀ `Modules/Event`, nên tách module riêng để không module nào "sở hữu" cái kia), Related Posts Engine chỉ đọc dữ liệu Post, chỉ render trong đúng 1 trang của Post (`public.article`) — không có lý do tách module, chỉ tạo thêm boilerplate (`module.json`, `composer.json`, `ServiceProvider` riêng) không cần thiết. Đúng tinh thần `VersionHistory`/`PublicReading` đã có sẵn |
| **Thời điểm tính gợi ý** | N/A | Tính **realtime lúc request** (không precompute qua queue job đổ vào bảng riêng), kết quả cache theo `article_id` bằng `Cache::remember()`, TTL 6 giờ | Quy mô hiện tại (family/parenting content site) chưa cần precompute toàn bộ bảng — mỗi bài chỉ tính lại tối đa 4 lần/ngày (24h/6h) nhờ cache, đơn giản hơn hẳn 1 job quét toàn bộ `post_articles` định kỳ. Cùng tinh thần Banner (query trực tiếp mỗi request, không cache) nhưng ở đây thêm cache vì truy vấn có JOIN nặng hơn (self-join bảng hành vi, xem §5.3) |
| **Cache store** | `.env`: `CACHE_STORE=database`, `QUEUE_CONNECTION=database` — Redis đã cấu hình (`REDIS_HOST=127.0.0.1`) nhưng **CHƯA bật làm driver mặc định** (`# QUEUE_CONNECTION=redis` bị comment trong `.env`) | Dùng `Cache::remember()` với store **mặc định hiện tại** (`database`), KHÔNG giả định Redis đã bật | Không đúng thực tế môi trường triển khai hiện tại nếu giả định Redis sẵn sàng; `cache` store `database` vẫn đáp ứng đủ (TTL 6h, không cần tốc độ sub-ms) |
| **Tín hiệu "hành vi"** | **Không có** hạ tầng tracking hành vi đọc nào (đã grep toàn bộ `app/`, `Modules/`, `resources/views/` — không có bảng/cookie/session nào ghi nhận "user A xem bài B rồi xem bài C"). Chỉ có `view_count` (tổng cộng dồn, không theo thời gian, không theo phiên) và `post_article_redirect_clicks` (chỉ cho `format=redirect`, không áp dụng cho bài thường) | Xây **mới** 1 bảng log hành vi nhẹ `post_article_view_events` (article_id + cookie ẩn danh + thời điểm xem), dùng để tính "đồng-xem trong cùng phiên" (co-occurrence). Kết hợp CẢ 2 tín hiệu: co-occurrence (khi đủ dữ liệu) + độ phổ biến `view_count` (luôn có, dùng làm điểm phụ + fallback khi co-occurrence rỗng) | Đúng yêu cầu "gợi ý theo hành vi" — nếu chỉ dùng category/tag thì không khác gì lọc thông thường, không có gì "thông minh". Không dùng session Laravel (rotate khi hết hạn/đăng nhập, không hợp cho theo dõi dài hạn ẩn danh) mà dùng 1 cookie ẩn danh riêng, dài hạn (365 ngày) |
| **Định danh người xem cho co-occurrence** | Không có cookie tracking nào đang tồn tại; không có banner cookie-consent nào trong codebase (đã grep `cookie.consent`/`gdpr` — không có kết quả liên quan) | Cookie **ẩn danh, first-party**, tên `rp_vid` (`config('post.related_posts.visitor_cookie_name')`), giá trị là chuỗi ngẫu nhiên 64 ký tự (`Str::random(64)`, KHÔNG chứa thông tin định danh cá nhân), không gắn với `user_id`/email, không chia sẻ cross-domain | Đủ để nhóm các lượt xem "cùng 1 trình duyệt" phục vụ tính co-occurrence, không thu thập PII nên không cần thêm cơ chế consent phức tạp. Ghi rõ minh bạch ở đây để lần review sau không bị hiểu nhầm là tracking ẩn |
| **Bảng hành vi có theo `user_id` không?** | N/A | **Không** — v1 chỉ dùng `visitor_hash` (cookie), không phân biệt user đã đăng nhập hay khách | Đa số độc giả là khách vãng lai (site đọc báo công khai, không yêu cầu đăng nhập để đọc — xác nhận qua `Modules/Post/routes/web.php` route công khai không có middleware `auth`). Gắn thêm `user_id` cho ~5% người có tài khoản không đáng công sức xử lý 2 nguồn danh tính hợp nhất ở v1 — để ở §9 (ngoài phạm vi) |
| **Loại trừ khỏi danh sách gợi ý** | `ArticleFormat::Redirect` — bài không có nội dung riêng, click vào là `redirect()->away()` ngay (`PublicArticleController::show()` dòng 47-51, KHÔNG render `view('post::public.article', ...)`) | Luôn loại `format = redirect` khỏi **danh sách ứng viên được gợi ý** (dù vẫn tính vào pool "đã xem cùng phiên" nếu có) | Đưa 1 bài mà click vào sẽ rời khỏi site ngay lập tức vào khối "Bài viết liên quan" (kỳ vọng ở lại đọc thêm) là trải nghiệm gây khó hiểu; hơn nữa bài `redirect` không bao giờ tự hiển thị khối gợi ý của chính nó (route redirect trước khi tới `view()`) |
| **Quyền quản trị (permission)** | Banner/Ocop/Page/CoreIdeaExtractor đều có permission riêng dạng `<module>.manage` (Lớp A, xem `PermissionEnum.php:136-159`) vì có màn hình CRUD admin | **KHÔNG thêm permission mới** ở v1 — không có màn hình quản trị nào (thuật toán + trọng số cấu hình qua `config('post.related_posts.*')`, sửa file + deploy, không sửa qua UI) | Đúng tinh thần "chưa cần CRUD thì chưa tạo permission" — module hoàn toàn tự động, không có hành động nào của con người cần phân quyền. Nếu sau này có màn hình xem/ẩn thủ công 1 gợi ý sai (§9), lúc đó mới thêm `related_posts.manage` theo đúng khuôn Lớp A |
| **Vị trí hiển thị** | `Modules/Post/resources/views/public/article.blade.php` hiện kết thúc bằng khối tag (`@if($article->tags->isNotEmpty()) ... @endif`), sau đó đóng `</div> @endsection` — chưa có khối nào sau đó | Chèn `<x-frontend.related-posts>` **ngay sau khối tag**, trước khi đóng `</div>` | Vị trí tự nhiên nhất — độc giả đọc xong nội dung, thấy tag rồi tới gợi ý đọc tiếp, đúng thứ tự UX chuẩn của các trang báo |
| **Card hiển thị mỗi gợi ý** | `resources/views/components/frontend/article-card.blade.php` đã có sẵn, nhận `translation` (with `article.categories`), 3 size (`lg/md/sm`) | **Tái dùng trực tiếp** `<x-frontend.article-card :translation="$t" size="sm">`, không tạo component card mới | Tránh 2 kiểu thẻ bài viết khác nhau trên cùng 1 trang — nhất quán với card đã dùng ở trang chủ/danh mục |

---

## 1. Giới thiệu & Mục tiêu

Trang chi tiết bài viết công khai (`Modules/Post/resources/views/public/article.blade.php`) hiện **kết thúc đột ngột** sau nội dung + khối tag — không có bất kỳ gợi ý nào để giữ chân độc giả đọc tiếp. Đây là 1 cơ hội tăng pageview/thời gian ở lại site bị bỏ trống hoàn toàn: không có widget "Bài viết liên quan", không có "Có thể bạn quan tâm", không có bất kỳ tín hiệu cá nhân hoá nào.

**Related Posts Engine** giải quyết việc này bằng 1 thuật toán tính điểm kết hợp **3 nhóm tín hiệu**:

1. **Chuyên mục (category)** — bài cùng danh mục (ưu tiên danh mục chính, `is_primary=true`) nhiều khả năng cùng chủ đề độc giả đang quan tâm.
2. **Tag** — bài chia sẻ càng nhiều tag càng liên quan chặt hơn category (tag mô tả chi tiết hơn, vd 2 bài cùng category "Dinh dưỡng" nhưng khác tag "ăn dặm"/"sữa mẹ" thực ra khác chủ đề con).
3. **Hành vi đọc thực tế** — 2 tín hiệu con:
   - **Đồng-xem (co-occurrence)**: bài B được nhiều độc giả khác xem **trong cùng 1 phiên** với bài A đang đọc → tín hiệu "người đọc A cũng đọc B", mạnh hơn category/tag vì phản ánh hành vi thật, không chỉ phân loại nội dung.
   - **Độ phổ biến (popularity)**: `view_count` — dùng làm điểm phụ (tie-break) VÀ làm phương án dự phòng khi dữ liệu đồng-xem còn quá ít (site mới/bài mới đăng chưa có ai xem cùng phiên).

Kết quả **luôn có** (không bao giờ trả về rỗng trừ khi toàn site chỉ có đúng 1 bài published) nhờ cơ chế fallback theo tầng ở §5.4 — tinh thần nhất quán với `CoreIdeaExtractor` ("luôn trả kết quả có cấu trúc").

**Nguyên tắc thiết kế cốt lõi:** không có màn hình quản trị nào cần build ở v1 — toàn bộ vận hành tự động, admin chỉ chỉnh trọng số qua `config('post.related_posts.weights')` khi cần tinh chỉnh (không qua UI, không cần permission mới, xem §0).

---

## 2. Khảo sát hiện trạng — dữ liệu nguồn có thể tái dùng

### 2.1 Kiến trúc Post hiện tại (đã tách Article/Translation)

`post_articles` (`Modules/Post/app/Models/PostArticle.php`) chỉ còn là "vỏ" (format, cover ảnh qua Media Library, danh mục, tag, sponsorship) — **không extends `TenantAwareModel`** (Post là tài sản nền tảng, không thuộc tổ chức nào, `PostArticle.php:22-25`). Nội dung/SEO/trạng thái xuất bản/`view_count` nằm ở `post_article_translations` (`PostArticleTranslation.php`, 1 article có N bản dịch theo `locale`).

Relationship cần dùng cho thuật toán liên quan:

```php
// PostArticle.php:111-125
public function categories(): BelongsToMany
{
    return $this->belongsToMany(PostCategory::class, 'post_article_categories', 'article_id', 'category_id')
        ->withPivot('is_primary');
}

public function primaryCategory(): BelongsToMany
{
    return $this->categories()->wherePivot('is_primary', true);
}

public function tags(): BelongsToMany
{
    return $this->belongsToMany(PostTag::class, 'post_article_tag', 'article_id', 'tag_id');
}
```

`PostArticleTranslation` (`PostArticleTranslation.php:157-160`) có sẵn scope lọc bài đã xuất bản:

```php
public function scopePublished($query): void
{
    $query->where('status', TranslationStatus::Published);
}
```

`view_count` đã cast `integer` (dòng 77), tăng qua `IncrementArticleViewCountAction::handle()` (`Features/PublicReading/Actions/IncrementArticleViewCountAction.php`) — gọi ở **mỗi lần** `PublicArticleController::show()` chạy, không phân biệt bot/refresh-spam, không lưu theo ngày — dùng được làm tín hiệu "độ phổ biến tổng", nhưng KHÔNG dùng được để biết "2 bài có được xem cùng lúc không" (đó là lý do cần bảng hành vi mới, xem §3).

### 2.2 Category & Tag — platform-wide, phẳng

`PostCategory` (`PostCategory.php:21`) và `PostTag` (`PostTag.php:14`) đều **platform-wide**, không `organization_id` global-scope (Post đã ra khỏi phạm vi đa tenant từ `spec/Platform_RBAC_Phase2_Specification.md` v3.0). `post_article_categories` có cột `is_primary` (bool) — dùng để phân biệt "danh mục chính" (điểm cao hơn) với "danh mục phụ" trong thuật toán (§5.1).

### 2.3 Không có hạ tầng "hành vi đọc" nào để tái dùng

Đã khảo sát kỹ — **không tồn tại**: bảng lịch sử đọc theo user, session tracking, cookie ẩn danh, ma trận đồng-xem/đồng-click. `post_article_redirect_clicks` (migration `2026_07_22_031000_create_post_article_redirect_clicks_table.php`) chỉ ghi click cho bài `format=redirect`, không áp dụng cho luồng đọc bài thường:

```php
Schema::create('post_article_redirect_clicks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
    $table->string('referrer', 500)->nullable();
    $table->timestamp('created_at');
    $table->index(['article_id', 'created_at'], 'idx_post_redirect_click_article_created');
});
```

Đây là **tiền lệ cấu trúc** (bảng log rời, insert-only, không `updated_at`, index theo `(article_id, thời điểm)`) mà bảng hành vi mới (`post_article_view_events`, §3) sẽ theo đúng khuôn.

### 2.4 Truy vấn "cùng category, loại trừ N bài" đã có sẵn — tái dùng tinh thần, không tái dùng trực tiếp

`ListPublishedArticlesQuery`/`ListPublishedArticlesHandler` (`Features/PublicReading/Queries/`) đã có tham số `categoryId` + `excludeArticleIds`, dùng ở trang chủ để tránh lặp bài hero/feature. Related Posts Engine viết 1 Query/Handler **riêng** (`GetRelatedArticlesQuery`/`Handler`) theo đúng convention CQRS-lite này (`App\Shared\Contracts\QueryInterface`/`QueryHandlerInterface`), không tái dùng thẳng class cũ vì logic tính điểm đa tín hiệu khác hẳn phân trang đơn giản.

### 2.5 Meilisearch — có sẵn field liên quan, không bắt buộc dùng ở v1

`PostArticleTranslation::toSearchableArray()` đã index sẵn `category_names/category_slugs/tag_names`. Có thể tận dụng để tính "more like this" qua Meilisearch ở phiên bản sau nếu quy mô dữ liệu lớn hơn, nhưng **v1 dùng truy vấn SQL trực tiếp** (đơn giản hơn, đủ nhanh ở quy mô hiện tại, không phụ thuộc Meilisearch còn sống — nếu Meilisearch lỗi thì related posts vẫn chạy, khác nếu phụ thuộc thẳng vào nó).

---

## 3. Kiến trúc dữ liệu

### 3.1 ERD

```
PostArticleViewEvent (post_article_view_events)      — bảng MỚI, insert-only event log
  ├─ id
  ├─ article_id (FK post_articles, cascadeOnDelete)
  ├─ visitor_hash (string 64, index)                  — cookie ẩn danh rp_vid, KHÔNG phải user_id/PII
  ├─ viewed_at (timestamp, index)
  (KHÔNG soft delete, KHÔNG updated_at — log chỉ thêm, không sửa; tự dọn qua PruneArticleViewEventsJob §6.2)
```

Không cần bảng cache "related_article_id → suggested_ids" — kết quả tính realtime + cache ở tầng `Cache::remember()` (§0, §5.5), không phải bảng DB.

### 3.2 Migration

`Modules/Post/database/migrations/2026_07_23_000001_create_post_article_view_events_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §3 spec/Related_Posts_Engine_Technical_Specification.md — 1 dòng / lượt xem 1 bài viết công
 * khai, gắn với cookie ẩn danh (visitor_hash, KHÔNG phải user_id) — dùng để tính "đồng-xem trong
 * cùng phiên" (co-occurrence) cho thuật toán gợi ý liên quan. Cùng khuôn insert-only event log
 * với post_article_redirect_clicks (không soft-delete, không updated_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_article_view_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
            $table->string('visitor_hash', 64);
            $table->timestamp('viewed_at');

            $table->index(['article_id', 'viewed_at'], 'idx_rp_view_article_viewed');
            $table->index(['visitor_hash', 'viewed_at'], 'idx_rp_view_visitor_viewed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_article_view_events');
    }
};
```

2 index kép phục vụ đúng 2 chiều truy vấn cần ở §5.3: "các lượt xem của bài X" (self-join vế trái) và "các bài khác cùng `visitor_hash` với 1 lượt xem cụ thể" (self-join vế phải).

---

## 4. Model & cấu hình

### 4.1 `PostArticleViewEvent` model

`Modules/Post/app/Models/PostArticleViewEvent.php`:

```php
<?php

namespace Modules\Post\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Event log insert-only — KHÔNG soft-delete, KHÔNG updated_at (chỉ ghi, không sửa). Tự dọn qua
 * PruneArticleViewEventsJob theo config('post.related_posts.behavior_lookback_days') (§6.2).
 */
class PostArticleViewEvent extends Model
{
    protected $table = 'post_article_view_events';

    public $timestamps = false;

    protected $fillable = [
        'article_id',
        'visitor_hash',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(PostArticle::class, 'article_id');
    }
}
```

### 4.2 `config/post.php` (`Modules/Post/config/config.php`) — thêm khoá `related_posts`

Theo đúng tiền lệ `version_history` (khoá con lồng trong config module có sẵn, không tạo file config riêng):

```php
// spec/Related_Posts_Engine_Technical_Specification.md — thuật toán gợi ý bài viết liên quan.
// Đổi trọng số ở đây KHÔNG cần sửa code, chỉ cần deploy lại config (không có UI chỉnh ở v1, §0).
'related_posts' => [
    'max_results'            => 6,   // số bài hiển thị trong khối "Bài viết liên quan"
    'candidate_pool_limit'   => 200, // chặn trần số ứng viên đưa vào tính điểm PHP (§5.2), tránh quét toàn bảng khi site có hàng chục nghìn bài
    'cache_ttl_hours'        => 6,   // §0 "Thời điểm tính gợi ý" — TTL cache theo article_id
    'behavior_lookback_days' => 90,  // cửa sổ thời gian tính đồng-xem (§5.3) — cũng là retention của post_article_view_events (§6.2)
    'visitor_cookie_name'    => 'rp_vid',
    'visitor_cookie_days'    => 365,

    'weights' => [
        'category_primary'     => 40, // 2 bài cùng danh mục CHÍNH (is_primary=true)
        'category_secondary'   => 20, // chỉ trùng danh mục phụ (không phải is_primary)
        'tag_per_match'        => 15, // mỗi tag trùng — nhân với số tag trùng, chặn trần ở tag_match_cap
        'tag_match_cap'        => 3,  // trùng từ tag thứ 4 trở đi không cộng thêm điểm (tránh bài "nhồi tag" thắng áp đảo)
        'behavior_per_covisit' => 5,  // mỗi lượt "đồng-xem" (session khác nhau) — nhân với số lượt, chặn trần ở behavior_covisit_cap
        'behavior_covisit_cap' => 10, // trùng lượt đồng-xem thứ 11 trở đi không cộng thêm (chặn 1 bài viral áp đảo mọi gợi ý)
        'popularity'           => 8,  // nhân với log10(1 + view_count) — điểm phụ/tie-break, KHÔNG để 1 bài cực hot thắng mọi bài mới đúng chủ đề hơn
    ],
],
```

**Vì sao dùng `log10(1 + view_count)` chứ không phải `view_count` thô:** 1 bài có 50.000 view so với bài 500 view chỉ nên hơn ~2 điểm decade (log10(50000)≈4.7 vs log10(500)≈2.7 → chênh ~2), không phải hơn 100 lần — nếu dùng thô, độ phổ biến sẽ luôn át hẳn category/tag, biến "gợi ý liên quan" thành "bảng xếp hạng view" trá hình.

---

## 5. Thuật toán tính điểm liên quan

### 5.1 Điều kiện ứng viên (candidate pool)

Ứng viên hợp lệ phải thoả **tất cả**:
- Cùng `locale` với bài đang đọc (không gợi ý bài khác ngôn ngữ).
- `status = published` (qua `scopePublished()`).
- Không phải chính bài đang đọc.
- `article.format != ArticleFormat::Redirect` (§0 — không gợi ý bài dẫn ra ngoài site ngay khi click).
- Thoả **ít nhất 1** trong 3 điều kiện: cùng ít nhất 1 category, HOẶC cùng ít nhất 1 tag, HOẶC xuất hiện trong tập "đồng-xem" với bài hiện tại (§5.3) — giữ pool ở mức hợp lý (không tính điểm cho TOÀN BỘ bài published trên site), giới hạn thêm bởi `candidate_pool_limit`.

### 5.2 Truy vấn pool (SQL qua Eloquent)

`Modules/Post/app/Features/RelatedPosts/Queries/GetRelatedArticlesHandler.php` (phần dựng pool):

```php
private function candidatePool(PostArticleTranslation $source, array $coOccurringArticleIds): Collection
{
    $article       = $source->article; // đã eager-load categories/tags ở gọi ngoài
    $categoryIds   = $article->categories->pluck('id')->all();
    $tagIds        = $article->tags->pluck('id')->all();
    $poolLimit     = (int) config('post.related_posts.candidate_pool_limit', 200);

    return PostArticleTranslation::published()
        ->where('locale', $source->locale)
        ->where('article_id', '!=', $article->id)
        ->whereHas('article', function ($q) use ($categoryIds, $tagIds, $coOccurringArticleIds) {
            $q->where('format', '!=', ArticleFormat::Redirect->value)
                ->where(function ($sub) use ($categoryIds, $tagIds, $coOccurringArticleIds) {
                    if ($categoryIds !== []) {
                        $sub->orWhereHas('categories', fn ($c) => $c->whereIn('post_categories.id', $categoryIds));
                    }
                    if ($tagIds !== []) {
                        $sub->orWhereHas('tags', fn ($t) => $t->whereIn('post_tags.id', $tagIds));
                    }
                    if ($coOccurringArticleIds !== []) {
                        $sub->orWhereIn('id', $coOccurringArticleIds);
                    }
                });
        })
        ->with(['article.categories', 'article.tags'])
        ->limit($poolLimit)
        ->get();
}
```

### 5.3 Tín hiệu hành vi — truy vấn đồng-xem (co-occurrence)

```php
private function coOccurringArticleIds(int $articleId): \Illuminate\Support\Collection
{
    $lookbackDays = (int) config('post.related_posts.behavior_lookback_days', 90);
    $since        = now()->subDays($lookbackDays);

    // self-join: visitor_hash nào đã xem $articleId, họ CÒN xem bài nào khác trong cùng cửa sổ
    // thời gian → đếm số visitor_hash riêng biệt cho mỗi bài "cùng xem".
    return DB::table('post_article_view_events as e1')
        ->join('post_article_view_events as e2', 'e1.visitor_hash', '=', 'e2.visitor_hash')
        ->where('e1.article_id', $articleId)
        ->where('e2.article_id', '!=', $articleId)
        ->where('e1.viewed_at', '>=', $since)
        ->where('e2.viewed_at', '>=', $since)
        ->select('e2.article_id')
        ->selectRaw('COUNT(DISTINCT e1.visitor_hash) as co_views')
        ->groupBy('e2.article_id')
        ->orderByDesc('co_views')
        ->limit(50)
        ->pluck('co_views', 'e2.article_id');
}
```

Kết quả là `[article_id => số lượt đồng-xem]` — dùng cả để (a) mở rộng pool ở §5.2 (`coOccurringArticleIds`) và (b) tính `behavior_score` ở §5.4.

### 5.4 Công thức tính điểm

Với mỗi ứng viên `$candidate` (1 `PostArticleTranslation`, đã `with('article.categories', 'article.tags')`):

```php
private function score(
    PostArticleTranslation $candidate,
    array $sourceCategoryIds,
    array $sourceTagIds,
    \Illuminate\Support\Collection $coOccurrenceCounts,
    array $weights,
): float {
    $candidateArticle = $candidate->article;

    $sharedCategoryIds = collect($candidateArticle->categories->pluck('id'))->intersect($sourceCategoryIds);
    $hasPrimaryMatch   = $candidateArticle->categories
        ->wherePivot('is_primary', true)
        ->pluck('id')
        ->intersect($sourceCategoryIds)
        ->isNotEmpty();

    $categoryScore = match (true) {
        $hasPrimaryMatch                  => $weights['category_primary'],
        $sharedCategoryIds->isNotEmpty()   => $weights['category_secondary'],
        default                            => 0,
    };

    $tagMatches = min(
        $weights['tag_match_cap'],
        collect($candidateArticle->tags->pluck('id'))->intersect($sourceTagIds)->count(),
    );
    $tagScore = $tagMatches * $weights['tag_per_match'];

    $coViews       = (int) ($coOccurrenceCounts[$candidateArticle->id] ?? 0);
    $behaviorScore = min($coViews, $weights['behavior_covisit_cap']) * $weights['behavior_per_covisit'];

    $popularityScore = log10(1 + $candidate->view_count) * $weights['popularity'];

    return $categoryScore + $tagScore + $behaviorScore + $popularityScore;
}
```

Sắp xếp theo tổng điểm giảm dần; hoà điểm → `published_at` mới hơn thắng (tie-break đơn giản, tránh thứ tự không ổn định giữa các lần cache miss).

### 5.5 Fallback đảm bảo luôn có kết quả

Nếu sau khi tính điểm, số ứng viên **có điểm > 0** ít hơn `max_results` (site mới, bài mới đăng chưa có category/tag trùng hay lượt đồng-xem nào) → bổ sung thêm bằng bài published **phổ biến nhất** (`view_count` cao nhất, cùng locale, loại `format=redirect`, loại các id đã chọn) cho đủ số lượng, điểm các bài bổ sung này = 0 (không giả vờ là "liên quan", chỉ là lấp chỗ trống để khối không trống rỗng — đúng tinh thần luôn trả kết quả có cấu trúc, không bao giờ hiển thị "chưa có gợi ý nào" trừ khi toàn site chỉ có 1 bài published).

### 5.6 `GetRelatedArticlesQuery` (DTO)

```php
<?php

namespace Modules\Post\Features\RelatedPosts\Queries;

use App\Shared\Contracts\QueryInterface;

class GetRelatedArticlesQuery implements QueryInterface
{
    public function __construct(
        public readonly int $articleId,
        public readonly string $locale,
        public readonly int $limit = 6,
    ) {}
}
```

---

## 6. Ghi nhận hành vi & vòng đời dữ liệu

### 6.1 Ghi nhận lượt xem — tích hợp vào `PublicArticleController::show()`

`RecordArticleViewEventAction` (`Modules/Post/app/Features/RelatedPosts/Actions/RecordArticleViewEventAction.php`):

```php
<?php

namespace Modules\Post\Features\RelatedPosts\Actions;

use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Models\PostArticleViewEvent;

class RecordArticleViewEventAction
{
    use AsAction;

    public function handle(int $articleId): void
    {
        PostArticleViewEvent::create([
            'article_id'   => $articleId,
            'visitor_hash' => $this->resolveVisitorHash(),
            'viewed_at'    => now(),
        ]);
    }

    /**
     * Cookie ẩn danh first-party (§0 "Định danh người xem") — KHÔNG dùng session Laravel
     * (rotate khi hết hạn/đăng nhập, không hợp cho theo dõi dài hạn ẩn danh qua nhiều lượt ghé
     * site khác ngày). Cookie::queue() không cần Response object — AddQueuedCookiesToResponse
     * (middleware mặc định nhóm 'web') tự đính vào response cuối cùng.
     */
    private function resolveVisitorHash(): string
    {
        $cookieName = config('post.related_posts.visitor_cookie_name', 'rp_vid');
        $existing   = request()->cookie($cookieName);

        if ($existing) {
            return $existing;
        }

        $hash = Str::random(64);
        $days = (int) config('post.related_posts.visitor_cookie_days', 365);

        Cookie::queue(Cookie::make($cookieName, $hash, $days * 1440));

        return $hash;
    }
}
```

Gọi trong `PublicArticleController::show()`, ngay cạnh `IncrementArticleViewCountAction` đã có sẵn (dòng 38), và gọi `GetRelatedArticlesHandler` để lấy dữ liệu truyền ra view:

```php
public function show(
    string $slug,
    IncrementArticleViewCountAction $viewAction,
    RecordArticleRedirectClickAction $clickAction,
    RecordArticleViewEventAction $viewEventAction,     // MỚI
    GetRelatedArticlesHandler $relatedHandler,          // MỚI
    ArticleContentRenderer $renderer,
): View|RedirectResponse {
    $translation = PostArticleTranslation::published()
        ->where('locale', config('post.default_locale'))
        ->where('slug', $slug)
        ->with(['article.categories', 'article.tags', /* ...giữ nguyên các with() cũ... */])
        ->first();

    abort_unless($translation, 404);

    $viewAction->handle($translation);

    $article = $translation->article;
    if ($article?->isRedirect() && $article->redirect_url) {
        $clickAction->handle($article);
        return redirect()->away($article->redirect_url);
    }

    // MỚI — ghi nhận hành vi CHỈ khi bài thực sự được đọc (không ghi cho nhánh redirect ở trên,
    // vì redirect rời trang trước khi có "đọc" thật nào diễn ra).
    $viewEventAction->handle($article->id);

    $related = $relatedHandler->handle(new GetRelatedArticlesQuery(
        articleId: $article->id,
        locale: $translation->locale,
        limit: (int) config('post.related_posts.max_results', 6),
    ));

    return view('post::public.article', [
        'translation'      => $translation,
        'article'          => $article,
        'locale'           => $translation->locale,
        'content'          => $renderer->render($translation),
        'relatedArticles'  => $related,             // MỚI
    ]);
}
```

### 6.2 Dọn dữ liệu cũ — `PruneArticleViewEventsJob`

`Modules/Post/app/Jobs/PruneArticleViewEventsJob.php`, đăng ký trong `PostServiceProvider::boot()` cùng chỗ với `PublishDueTranslationsJob`/`ExpireSponsoredArticlesJob`:

```php
$schedule->job(new PruneArticleViewEventsJob(), 'low')->daily()->withoutOverlapping();
```

Xoá mọi dòng `viewed_at < now()->subDays(config('post.related_posts.behavior_lookback_days'))` — retention đúng bằng cửa sổ tính đồng-xem (§5.3), dữ liệu cũ hơn không còn dùng vào việc gì. Cùng nguyên tắc `ExpireSponsoredArticlesJob` (queue `'low'`, chạy `daily()`, `withoutOverlapping()`).

---

## 7. Render công khai

### 7.1 Blade component — tái dùng `<x-frontend.article-card>`

`resources/views/components/frontend/related-posts.blade.php`:

```blade
@props([
    'articles', // Collection<PostArticleTranslation>, đã with('article.categories')
])

@if($articles->isNotEmpty())
<section class="mt-10 pt-8 border-t border-base-300">
    <h2 class="text-lg font-bold text-base-content mb-4">Bài viết liên quan</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
        @foreach($articles as $translation)
        <x-frontend.article-card :translation="$translation" size="sm" />
        @endforeach
    </div>
</section>
@endif
```

### 7.2 Tích hợp vào `public/article.blade.php`

Chèn ngay sau khối tag, trước khi đóng `</div> @endsection` (§0):

```blade
    @if($article->tags->isNotEmpty())
    <div class="flex flex-wrap gap-1.5 mt-4">
        @foreach($article->tags as $tag)
        <span class="badge badge-sm badge-outline">#{{ $tag->name }}</span>
        @endforeach
    </div>
    @endif

    <x-frontend.related-posts :articles="$relatedArticles" />

</div>
@endsection
```

---

## 8. Kế hoạch triển khai

1. Migration `post_article_view_events` + model `PostArticleViewEvent`.
2. Thêm khoá `related_posts` vào `Modules/Post/config/config.php` (§4.2).
3. `RecordArticleViewEventAction` + gắn cookie ẩn danh (§6.1).
4. `GetRelatedArticlesQuery`/`GetRelatedArticlesHandler` — pool + tính điểm + fallback + cache (§5).
5. `PruneArticleViewEventsJob` + đăng ký lịch trong `PostServiceProvider::boot()` (§6.2).
6. Component `<x-frontend.related-posts>` + tích hợp `PublicArticleController::show()` + `public/article.blade.php` (§7).
7. Test: co-occurrence tính đúng, fallback khi pool rỗng/thiếu, loại `format=redirect`, cache TTL, prune job xoá đúng ngưỡng ngày.

---

## 9. Ngoài phạm vi (v1)

- **Hợp nhất danh tính user đăng nhập với `visitor_hash`** — v1 chỉ dùng cookie ẩn danh, không map sang `user_id` dù đã đăng nhập.
- **Màn hình quản trị** (xem/ẩn thủ công 1 gợi ý sai, ghim gợi ý thủ công cho 1 bài, chỉnh trọng số qua UI thay vì config) — nếu cần, thêm permission `related_posts.manage` theo đúng khuôn Lớp A (`platform_ops` + `platform_content_head`, seed riêng, không qua `config/permissions.php`).
- **Precompute qua queue job** cho toàn bộ bài published — chỉ cần nếu quy mô dữ liệu/lưu lượng tăng đủ lớn để truy vấn realtime (dù đã cache) trở thành nút thắt.
- **Cá nhân hoá theo lịch sử đọc dài hạn của 1 độc giả cụ thể** (không chỉ trong 1 phiên/cửa sổ 90 ngày) — cần hạ tầng nhận diện xuyên phiên mạnh hơn cookie đơn giản.
- **A/B test các bộ trọng số khác nhau** — v1 chỉ có 1 bộ trọng số cố định trong config.
- **Tận dụng Meilisearch "more like this"** thay SQL — cân nhắc nếu SQL self-join (§5.3) trở thành nút thắt hiệu năng ở quy mô lớn.

---

## 10. Rủi ro & Đánh giá thực tiễn

Đối chiếu lại toàn bộ thiết kế ở §1-§9 dưới góc độ vận hành thực tế — không rủi ro nào ở mức chặn triển khai (blocker) ở v1, nhưng cần theo dõi khi quy mô/thị trường thay đổi.

| Rủi ro | Mức độ | Đánh giá |
|---|---|---|
| Self-join co-occurrence (§5.3) chậm khi bảng `post_article_view_events` lớn dần | Trung bình | Đã có 2 index kép (`idx_rp_view_article_viewed`, `idx_rp_view_visitor_viewed`, §3.2), `LIMIT 50` trong truy vấn, và kết quả cuối cùng cache 6h theo `article_id` (§4.2, §5.5) — chỉ site có traffic đủ lớn mới thấy áp lực. §9 đã có đường thoát rõ ràng (chuyển sang precompute qua queue job, hoặc dùng Meilisearch "more like this") mà **không cần đổi schema** khi tới lúc cần |
| Cookie `rp_vid` không có banner xin sự đồng ý (consent) | Thấp – Trung bình | Hợp lý ở v1 vì cookie không chứa PII, không cross-domain, chỉ phục vụ đúng 1 chức năng nội bộ (nhóm lượt xem cùng trình duyệt để tính đồng-xem — §0 "Định danh người xem"). Nhiều khung pháp lý (gồm ePrivacy/GDPR) có miễn trừ cho cookie "thuần chức năng, không theo dõi liên trang", nhưng ranh giới này phụ thuộc diễn giải pháp lý theo từng thị trường. Nếu mở rộng sang thị trường có yêu cầu consent nghiêm ngặt hơn, cần bổ sung cơ chế opt-in trước khi `Cookie::queue()` chạy (§6.1) — spec đã minh bạch hoá cookie này ngay từ đầu (bảng §0) thay vì để ẩn, nên bổ sung sau không phải phát hiện lại từ đầu |
| Cache chỉ theo TTL, không invalidate khi admin sửa category/tag của 1 bài | Thấp | Chấp nhận được — cùng triết lý "eventual consistency" đã dùng nhất quán trong codebase (`ExpireSponsoredArticlesJob` chạy `daily()` chứ không real-time, xem `PostArticle::isCurrentlySponsored()`). Lệch tối đa 6h giữa lúc sửa và lúc gợi ý cập nhật không phải blocker cho 1 widget gợi ý đọc thêm — không phải luồng nghiệp vụ cần tính đúng ngay lập tức |
| Cold-start (bài mới đăng / site mới, chưa có lượt đồng-xem nào) | Thấp | Fallback ở §5.5 (bổ sung bằng bài phổ biến nhất khi pool có điểm rỗng/thiếu) đảm bảo khối luôn hiển thị đủ số lượng ngay từ bài đầu tiên — chất lượng gợi ý (độ liên quan thật) tự cải thiện dần khi tích luỹ đủ dữ liệu category/tag/hành vi, không cần can thiệp thủ công |
| Bot/refresh-spam làm phình bảng `post_article_view_events` | Thấp | Truy vấn đồng-xem (§5.3) dùng `COUNT(DISTINCT e1.visitor_hash)` — 1 `visitor_hash` refresh-spam 1 bài N lần vẫn chỉ tính **đúng 1 lần** trong điểm số, nên spam **không làm sai lệch kết quả gợi ý**, chỉ làm phình dung lượng bảng/chậm dần self-join theo thời gian (đúng rủi ro dòng đầu tiên ở trên). `PruneArticleViewEventsJob` (§6.2) đã chặn phình vô hạn bằng cách giới hạn dữ liệu tồn đọng trong đúng `behavior_lookback_days` (90 ngày mặc định). Thêm cooldown ghi log (vd bỏ qua nếu cùng `visitor_hash` đã có event cho đúng bài đó trong N phút gần nhất) là cải tiến rẻ, có thể thêm ở v1.1 mà không đổi schema — không cần chặn ở v1 |

### 10.1 Điểm còn tồn đọng (residual) cần theo dõi khi vận hành

2 rủi ro sau **không phải thiếu sót bị bỏ quên** — là đánh đổi có chủ đích để giữ v1 đơn giản (KISS), nhưng cần giám sát bằng số liệu cụ thể thay vì chỉ "chấp nhận được" chung chung:

- **Self-join (§5.3) là điểm nặng nhất về hiệu năng khi traffic tăng.** Index + `LIMIT 50` + `candidate_pool_limit=200` + cache 6h (§10 dòng 1) đủ cho quy mô hiện tại, nhưng đây là phần **duy nhất** trong toàn bộ thuật toán có độ phức tạp tăng theo bình phương lượng dữ liệu (self-join). Ngưỡng nên xem lại thiết kế (chuyển sang precompute theo §9): số dòng `post_article_view_events` vượt khoảng **1-2 triệu dòng** (ở retention 90 ngày, §6.2), HOẶC thời gian tính `GetRelatedArticlesHandler` (đo lúc cache-miss, vd qua `Log`/APM) vượt quá vài trăm ms thường xuyên. Cả 2 chỉ số này đo được trực tiếp, không cần đoán — nên thêm vào việc theo dõi vận hành ngay khi module lên production, không đợi tới lúc user report chậm mới biết.

- **`RecordArticleViewEventAction` (§6.1) không có cơ chế dedup khi ghi** — cùng 1 `visitor_hash` refresh trang liên tục sẽ tạo nhiều dòng cho cùng 1 bài trong cùng phiên đọc thực tế. Đây là hành vi **kế thừa nguyên trạng** từ `IncrementArticleViewCountAction` đã có sẵn trong codebase (cũng tăng `view_count` mỗi lần load trang, không phân biệt refresh/bot — §2.1), giữ nhất quán thay vì tự chế 1 quy tắc dedup riêng chỉ cho bảng mới. Vì `COUNT(DISTINCT visitor_hash)` ở §5.3 đã trung hoà tác động lên **điểm số** (dòng "Bot/refresh-spam" ở trên), đây thuần tuý là vấn đề **dung lượng lưu trữ + tốc độ self-join** — cùng 1 nguyên nhân gốc với điểm theo dõi self-join ở trên, không phải 2 rủi ro độc lập. Cải thiện rẻ nhất khi cần (v1.1, không đổi schema): trong `RecordArticleViewEventAction::handle()`, bỏ qua ghi nếu đã tồn tại 1 dòng cùng `(article_id, visitor_hash)` với `viewed_at` trong N phút gần nhất (vd 30 phút) — 1 câu `exists()` thêm trước `create()`, đánh đổi 1 lượt query đọc để giảm số dòng ghi khi có spam.