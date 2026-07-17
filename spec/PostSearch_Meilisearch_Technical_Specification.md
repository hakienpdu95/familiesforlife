# Post — Tích hợp Meilisearch cho tìm kiếm công khai (Phase 1)

> Phạm vi: CHỈ `PublicReading` (cổng thông tin công khai — trang chủ `/`, trang danh mục
> `/bai-viet/danh-muc/{slug}`) của `Modules/Post`. Không đụng tìm kiếm admin
> (`ListArticlesForAdminHandler`, `ListPendingReviewTranslationsHandler`), không đụng module
> khác (`Product`, `Ocop`, `ProvinceShowcase`...) — các module đó là Phase riêng, xem §14.

## 0. Quyết định đã chốt

1. **Index ở cấp `PostArticleTranslation`, không phải `PostArticle`.** Post là đa locale
   (§2 `PostArticleTranslation`), mỗi bản dịch có `title`/`excerpt`/`slug` riêng — 1 document
   Meilisearch = 1 bản dịch, filter theo `locale` khi query. Ngày cổng công khai chỉ phục vụ
   `config('post.default_locale')` ('vi' — `Modules/Post/routes/web.php` dòng comment "KHÔNG
   còn {locale} trong URL"), nhưng field `locale` vẫn filterable để không phải đập lại schema
   nếu sau này mở thêm locale công khai.
2. **1 index duy nhất** (`post_article_translations`, có `SCOUT_PREFIX`), không tách theo
   locale/tổ chức — `Post` không tenant-scoped (đã bỏ hẳn `organization_id` từ migration
   `2026_07_13_000002_drop_organization_id_from_post_child_tables.php`), nên không có vấn đề
   rò dữ liệu chéo-tổ chức cần lo ở module này (khác hẳn `Product`/`Lead`/... — xem đánh giá
   kiến trúc tổng thể đã trao đổi trước đó).
3. **Chỉ thay nhánh có từ khoá tìm kiếm** (`$query->search` truthy) trong
   `ListPublishedArticlesHandler` bằng Meilisearch. Nhánh duyệt bình thường (không search — trang
   chủ, trang danh mục, "Xem thêm" qua `LoadMoreArticlesHandler`) **giữ nguyên 100% DB query
   hiện tại**. Lý do: Meilisearch mặc định giới hạn `1000` hit/lần trả (`maxTotalHits`) và không
   hợp với cursor pagination (`published_at`,`id`) mà `LoadMoreArticlesHandler` đang dùng; trong
   khi lợi ích chính của Meilisearch (relevance-ranking, typo-tolerance) chỉ có giá trị khi có
   từ khoá thật.
4. **Giữ nguyên chữ ký `ListPublishedArticlesQuery`/`ListPublishedArticlesHandler`** — 2 nơi gọi
   (`PublicCategoryController::index()`, `::show()`) không đổi 1 dòng nào. Chỉ đổi phần thân bên
   trong Handler.
5. **Fallback về LIKE query khi Meilisearch lỗi/timeout** — route công khai không được phép sập
   vì search engine down.
6. **Bắt buộc bật `SCOUT_QUEUE=true` + `scout.after_commit=true`** — không phải tối ưu tuỳ chọn,
   mà để tránh 1 bug cụ thể đã xác nhận trong codebase, xem §6.3.

## 1. Hiện trạng

| | Hiện tại | Sau Phase 1 |
|---|---|---|
| Nơi xử lý | `ListPublishedArticlesHandler::handle()` | cùng file, thêm nhánh Meilisearch |
| Trường tìm được | chỉ `title`, `excerpt` (2 cột) | `title`, `excerpt`, nội dung block `text`, tên category/tag |
| Cách so khớp | `LIKE '%...%'` — cần đúng chuỗi con, không chịu lỗi chính tả, không hiểu dấu tiếng Việt tổ hợp/dựng sẵn khác nhau | full-text + typo-tolerance của Meilisearch |
| Relevance | không có (chỉ `orderByDesc('published_at')`) | Meilisearch ranking rules (§5) |
| Hiệu năng khi data lớn | `LIKE '%x%'` không dùng được index B-tree, full scan | Meilisearch (chuyên biệt cho search, không quét bảng) |
| Route/Controller/View | không đổi | không đổi |

Search box đã có sẵn ở `resources/views/layouts/partials/frontend-header.blade.php` (2 form GET,
`name="q"`, action = `url()->current()`) — Phase 1 **không cần sửa gì ở frontend** (Blade/JS), vì
nó chỉ submit `q` tới đúng route hiện tại, và Handler là nơi duy nhất cần đổi.

## 2. Sơ đồ luồng

```
Đọc (search):
  Browser → GET /?q=... hoặc /bai-viet/danh-muc/{slug}?q=...
    → PublicCategoryController::index()/show()   (KHÔNG ĐỔI)
      → ListPublishedArticlesHandler::handle()   (ĐỔI — §8)
          search rỗng?  → DB query cũ (KHÔNG ĐỔI)
          search có?    → PostArticleTranslation::search($term)
                             → Meilisearch HTTP (127.0.0.1:7700)
                             → OK: hydrate Eloquent (kèm with() cũ) → LengthAwarePaginator
                             → LỖI: catch → fallback DB LIKE query cũ, Log::warning()

Ghi (đồng bộ index):
  TranslationController::update() → UpdateTranslationAction
    DB::transaction {
      $translation->update([title, excerpt, ...])   → Eloquent "saved" event
      SyncContentBlocksAction::handle()              → post_content_blocks đổi (KHÔNG fire event
                                                        trên $translation)
    }
    → after_commit=true nên job Scout CHỈ enqueue SAU KHI transaction commit xong cả 2 bước trên
    → Queue worker chạy job → fetch LẠI $translation từ DB (fresh) → toSearchableArray()
      → PUT document vào Meilisearch

  ArticleAdminController::update() → UpdateArticleAction (categories/tags/province/format —
    nằm trên PostArticle, KHÔNG trên Translation, không tự fire event nào lên translations)
    → PHẢI gọi thêm `$article->translations()->searchable();` (§6.2, touch-point bắt buộc)

  DeleteArticleAction (soft-delete PostArticle, KHÔNG cascade DB xuống translations vì soft-delete
    không kích hoạt FK cascadeOnDelete)
    → PHẢI gọi thêm `$article->translations()->unsearchable();` (§6.2, touch-point bắt buộc)
```

## 3. Model — `Modules/Post/app/Models/PostArticleTranslation.php`

Thêm trait `Laravel\Scout\Searchable`, giữ nguyên toàn bộ phần hiện có
(`HasFactory`/`SoftDeletes`/`LogsActivity`/`resolveRouteBinding()`/scopes...).

```php
use Laravel\Scout\Searchable;

class PostArticleTranslation extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;
    use Searchable;

    // ... giữ nguyên toàn bộ code hiện có ...

    /** Tên index Meilisearch — tường minh, không để Scout tự suy ra từ table name để tránh vỡ khi đổi $table. */
    public function searchableAs(): string
    {
        return 'post_article_translations';
    }

    /**
     * Chỉ đẩy vào Meilisearch bản dịch ĐANG published CỦA 1 article CHƯA bị soft-delete.
     * `$this->article` đi qua BelongsTo::article() — PostArticle có SoftDeletes nên global
     * scope của quan hệ này đã tự loại record đã xoá, `$this->article` trả null nếu cha bị
     * xoá → điều kiện dưới tự đúng KHI shouldBeSearchable() được Scout gọi lại (vd translation
     * tự được save/touch lần sau). Đây là lớp phòng thủ thứ 2 — lớp thứ 1 (chính) là gọi
     * unsearchable() tường minh ở DeleteArticleAction (§6.2), vì xoá article không tự đụng gì
     * tới translation nên shouldBeSearchable() không tự được Scout gọi lại ngay lúc đó.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->status === \Modules\Post\Enums\TranslationStatus::Published
            && $this->article !== null;
    }

    /**
     * Payload đẩy lên Meilisearch. QUERY LẠI quan hệ (contentBlocks()->get(), không phải
     * property $this->contentBlocks) — bắt buộc, vì SyncContentBlocksAction xoá-tạo-lại toàn
     * bộ post_content_blocks TRONG CÙNG transaction với $translation->update() (§6.3); nếu
     * dùng property đã cache trước đó có thể dính bản cũ.
     */
    public function toSearchableArray(): array
    {
        $article = $this->article; // BelongsTo — 1 query, đã lọc soft-delete

        $bodyText = $this->contentBlocks()
            ->where('type', \Modules\Post\Enums\ContentBlockType::Text)
            ->orderBy('sort_order')
            ->pluck('text_html')
            ->map(fn ($html) => trim(strip_tags((string) $html)))
            ->filter()
            ->implode(' ');

        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid,
            'locale'           => $this->locale,
            'title'            => $this->title,
            'excerpt'          => (string) $this->excerpt,
            'body_text'        => \Illuminate\Support\Str::limit($bodyText, 5000, ''),
            'slug'             => $this->slug,
            'status'           => $this->status->value,
            'published_at'     => $this->published_at?->timestamp,
            'article_id'       => $this->article_id,
            'format'           => $article?->format?->value,
            'is_featured'      => (bool) $article?->is_featured,
            'province_code'    => $article?->province_code,
            'category_names'   => $article?->categories->pluck('name')->all() ?? [],
            'category_slugs'   => $article?->categories->pluck('slug')->all() ?? [],
            'tag_names'        => $article?->tags->pluck('name')->all() ?? [],
        ];
    }
}
```

Ghi chú field:
- Không đưa `sponsor_*`/`disclosure_text`/`cta_*` vào index — đây là dữ liệu hiển thị/CTA, không
  phục vụ tìm kiếm, và tránh document phình to không cần thiết.
- Không đưa nội dung `ContentBlockType::Product` (khối sản phẩm) vào `body_text` — thuộc phạm vi
  `Modules\Product`, để Phase 2 nếu cần liên kết chéo (vd tìm bài viết theo tên sản phẩm được
  gắn).
- `category_names`/`tag_names` dùng để relevance match theo tên danh mục/nhãn khi gõ tìm kiếm
  (vd gõ "mẹo ăn dặm" khớp cả tên category), không chỉ để filter.

### 3.1 Hiệu năng `scout:import` — chưa cần ở Phase 1, nhưng đã có sẵn chỗ vá

`toSearchableArray()` truy cập `$this->article` (BelongsTo, lazy-load) rồi `$article->categories`/
`$article->tags` (2 lazy-load nữa) — nếu `scout:import` chạy full-import mà không eager-load
trước, mỗi record trong batch (`config('scout.chunk.searchable')` = 500) tự bắn thêm query riêng
→ N+1 thật sự trong lúc import (khác hẳn `slugForCategoryId()` ở §8, chỉ gọi 1 lần/request — xem
ghi chú ở đó). Không xử lý ở Phase 1 vì số bản dịch published hiện tại còn nhỏ, nhưng nếu sau này
`scout:import` chạy chậm (theo dõi thời gian ở lần backfill §7 hoặc lần re-import khi đổi
`index-settings`), Scout đã có sẵn hook đúng chỗ — override `makeAllSearchableUsing()` (chỉ ảnh
hưởng đường `scout:import`/`makeAllSearchable()`, KHÔNG ảnh hưởng đường queue 1-record khi user
publish/sửa 1 bài — đường đó đã tự eager-load đủ trong `toSearchableArray()` vì chỉ chạy 1 lần):

```php
protected function makeAllSearchableUsing($query)
{
    return $query->with(['article.categories', 'article.tags']);
}
```

## 4. Cấu hình

### 4.1 `.env` — thêm/sửa

```dotenv
SCOUT_DRIVER=meilisearch          # đã có
MEILISEARCH_HOST=http://127.0.0.1:7700   # đã có
MEILISEARCH_KEY=...                       # đã có (master key — KHÔNG dùng key này ở phía client/JS)
SCOUT_QUEUE=true                  # MỚI — bắt buộc, xem §6.3
SCOUT_PREFIX=ffl_                 # MỚI — tuỳ chọn nhưng nên có, tránh trùng index nếu sau này
                                   # chạy nhiều môi trường (staging/prod) trỏ cùng 1 Meilisearch
```

### 4.2 `config/scout.php` — sửa 1 giá trị

```php
'after_commit' => env('SCOUT_AFTER_COMMIT', true),   // trước: false — xem §6.3 vì sao BẮT BUỘC
```

`QUEUE_CONNECTION=database` đã có sẵn (`.env`), worker đã chạy qua
`php artisan queue:listen` (README/CLAUDE.md) — không cần thêm hạ tầng queue mới, job Scout
(`Laravel\Scout\Jobs\MakeSearchable`) chạy trên đúng queue connection/driver hiện tại của app.

## 5. Cấu hình index Meilisearch (`searchableAttributes`/`filterableAttributes`/...)

**Sửa lại so với bản nháp trước** — không tự viết Artisan command riêng. Scout v11.3.0 (bản đang
cài — xem `composer.lock`) đã có sẵn cơ chế khai báo settings qua config +
`php artisan scout:sync-index-settings` (`vendor/laravel/scout/src/Console/SyncIndexSettingsCommand.php`),
đọc từ `config('scout.meilisearch.index-settings')` — key là FQCN model, value là mảng settings
truyền thẳng vào `$index->updateSettings()` của Meilisearch (không có lớp transform nào ở giữa,
nên tên field trong mảng PHẢI đúng camelCase của Meilisearch API:
`vendor/laravel/scout/src/Engines/MeilisearchEngine.php::updateIndexSettings()`). Dùng cơ chế có
sẵn của framework thay vì viết lại — không cần thêm 1 command mới trong `PostServiceProvider`.

`config/scout.php` — thêm vào mảng `'meilisearch' => ['index-settings' => [...]]` đã có sẵn (hiện
đang để trống, comment mẫu):

```php
'meilisearch' => [
    'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
    'key' => env('MEILISEARCH_KEY'),
    'index-settings' => [
        \Modules\Post\Models\PostArticleTranslation::class => [
            'searchableAttributes' => ['title', 'category_names', 'tag_names', 'excerpt', 'body_text'],
            'filterableAttributes' => ['locale', 'status', 'category_slugs', 'province_code', 'format', 'is_featured'],
            'sortableAttributes'   => ['published_at'],
            'rankingRules'         => ['words', 'typo', 'proximity', 'attribute', 'sort', 'exactness'],
        ],
    ],
],
```

Áp dụng: `php artisan scout:sync-index-settings` (chạy 1 lần lúc deploy, idempotent — giống
migration nhưng KHÔNG phải migration, vì đây là HTTP call tới service ngoài, tách khỏi vòng đời
`php artisan migrate`). Chạy lại mỗi khi sửa mảng `index-settings` ở trên.

| Setting | Giá trị | Vì sao |
|---|---|---|
| `searchableAttributes` (có thứ tự) | `title` → `category_names`/`tag_names` → `excerpt` → `body_text` | thứ tự = độ ưu tiên relevance; khớp tiêu đề luôn đáng giá hơn khớp trong thân bài |
| `filterableAttributes` | `locale`, `status`, `category_slugs`, `province_code`, `format`, `is_featured` | Handler luôn filter `locale`/`status=published`; `category_slugs` cho phép kết hợp search + danh mục (đúng hành vi `PublicCategoryController::show()` hiện tại) |
| `sortableAttributes` | `published_at` | dự phòng nếu sau này cần "mới nhất" thay vì thuần relevance |
| `rankingRules` | mặc định Meilisearch (`words, typo, proximity, attribute, sort, exactness`) | đây LÀ danh sách mặc định của Meilisearch (đã xác nhận qua SDK, không có transform/validate riêng ở tầng Scout) — hợp lý cho blog/tin tức, không cần tinh chỉnh sâu ở Phase 1. **Lưu ý:** có đề xuất tách `attribute` thành 2 rule `attributeRank`/`wordPosition` cho bản Meilisearch mới hơn — không xác minh được tên rule này trong SDK/tài liệu đang có, khuyến nghị KHÔNG áp dụng nếu chưa kiểm tra trực tiếp changelog của đúng version server đang chạy (`meilisearch --version` trên server hiện là **1.49.0** — đổi ranking rule sai tên sẽ bị Meilisearch từ chối ở `PUT /settings`, nên test ở staging trước khi áp dụng) |

`typoTolerance` (mức chịu lỗi chính tả) có thể tinh chỉnh thêm trong cùng mảng settings ở trên
nếu sau này thấy typo-tolerance mặc định quá lỏng/chặt với tiếng Việt (vd chỉnh
`minWordSizeForTypos`) — không cần thiết ở Phase 1, mặc định của Meilisearch đã ổn cho quy mô
hiện tại.

**Lưu ý vận hành:** instance Meilisearch dùng chung (`127.0.0.1:7700`) hiện đã có sẵn 1 index
khác không liên quan (`kc_items`, thuộc ứng dụng/dự án khác trên cùng máy) — càng khẳng định
`SCOUT_PREFIX` ở §4.1 là bắt buộc, không phải tuỳ chọn, để tránh nhầm lẫn/đụng tên index.

## 6. Điểm cần sửa để đồng bộ index đúng — bảng touch-point

`Searchable` tự động push/xoá document khi **chính** `PostArticleTranslation` được save/delete
(Eloquent model events). 3 nơi dưới đây thay đổi dữ liệu ảnh hưởng tới document nhưng KHÔNG tự
fire event trên translation — phải gọi tường minh.

| # | File | Hành động hiện tại | Vấn đề | Cần thêm |
|---|---|---|---|---|
| 6.1 | `Modules/Post/app/Features/ArticleAuthoring/Actions/UpdateArticleAction.php` | `syncCategories()`/`syncTags()`/đổi `format`/`province_code` — toàn bộ trên `PostArticle`, không trên `Translation` | Đổi tên category/tag/tỉnh của 1 bài đã publish không cập nhật vào Meilisearch → search theo category mới sẽ KHÔNG ra bài này | Cuối `handle()`, trong cùng `DB::transaction`, thêm `$article->translations()->searchable();` (Scout builder-macro, tự chạy `get()->searchable()`) |
| 6.2 | `Modules/Post/app/Features/ArticleAuthoring/Actions/DeleteArticleAction.php` | `$article->delete()` (soft-delete `PostArticle`) | Soft-delete KHÔNG kích hoạt `cascadeOnDelete` ở DB (cascade FK chỉ chạy khi hard-delete) → các `PostArticleTranslation` con vẫn còn `status=published` nguyên vẹn, không ai đụng tới nên Scout không tự biết để xoá khỏi index → bài đã "xoá" (theo UI admin) **vẫn ra trong kết quả search công khai** | Thêm `$article->translations()->unsearchable();` **trước hoặc sau** `$article->delete()` (không phụ thuộc thứ tự — chỉ cần cùng có mặt) |
| 6.3 | `Modules/Post/app/Features/ArticleAuthoring/Actions/UpdateTranslationAction.php` | `$translation->update([title, excerpt, ...])` rồi **mới** gọi `$this->syncContentBlocks->handle($translation, $data->blocks)` — cả 2 trong 1 `DB::transaction` | Nếu `scout.after_commit=false` (giá trị hiện tại của repo), Scout push document ngay tại thời điểm `update()` fire event — **TRƯỚC KHI** content blocks được sync — `body_text` trong document sẽ là bản CŨ (trước khi sửa nội dung), lệch với `title`/`excerpt` mới | Đây chính là lý do §0 mục 6 bắt buộc `after_commit=true` — không phải optimize, mà để job chỉ enqueue SAU khi cả `update()` và `SyncContentBlocksAction` cùng commit xong. Không cần sửa code Action này, chỉ cần đổi config (§4.2) |

Không cần sửa `SyncContentBlocksAction`, `PublishArticleAction`, `UnpublishArticleTranslationAction`,
`TakeDownArticleTranslationAction`, `ArchiveArticleAction`, `ScheduleArticleAction`,
`CancelScheduleAction` — tất cả đều gọi `$translation->update(...)` trực tiếp trên chính
translation, tự fire event, Scout tự đồng bộ đúng (kể cả bật/tắt hiển thị khi đổi status).

## 7. Backfill dữ liệu có sẵn

```bash
php artisan scout:sync-index-settings        # set index settings (§5) — chạy trước import
php artisan scout:import "Modules\Post\Models\PostArticleTranslation"
```

`scout:import` tự gọi `shouldBeSearchable()` cho từng record — chỉ bản dịch `published` của
article chưa xoá được đẩy lên, khớp đúng logic §3. Chạy lại an toàn (Meilisearch upsert theo
`id`), không cần cờ đặc biệt.

## 8. Query layer — sửa `ListPublishedArticlesHandler`

```php
<?php

namespace Modules\Post\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Modules\Post\Models\PostArticleTranslation;
use Throwable;

class ListPublishedArticlesHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListPublishedArticlesQuery $query */
        if ($query->search) {
            try {
                return $this->handleViaMeilisearch($query);
            } catch (Throwable $e) {
                Log::warning('Meilisearch search thất bại, fallback về LIKE query.', [
                    'search' => $query->search,
                    'error'  => $e->getMessage(),
                ]);
            }
        }

        return $this->handleViaDatabase($query);
    }

    /** Memoize trong vòng đời 1 request — Handler được resolve mới mỗi request (không phải singleton), nên property thường (không cần static) là đủ, không rò rỉ giữa các request. */
    private array $categorySlugCache = [];

    private function handleViaMeilisearch(ListPublishedArticlesQuery $query): LengthAwarePaginator
    {
        return PostArticleTranslation::search($query->search)
            ->where('locale', $query->locale)
            ->where('status', 'published')
            ->when($query->categoryId, fn ($s, $categoryId) => $s->where('category_slugs', $this->slugForCategoryId($categoryId)))
            ->query(fn ($q) => $q->with(['article.categories', 'article.createdBy']))
            ->paginate($query->perPage, 'page', $query->page)
            ->withQueryString();
    }

    /**
     * Đổi `int $categoryId` (hợp đồng cũ của `ListPublishedArticlesQuery`, không đổi — §0 mục 4)
     * sang `slug` mà Meilisearch filter được (`category_slugs` trong document là mảng slug,
     * không phải id — xem §3). Trong 1 request, Handler chỉ gọi hàm này tối đa 1 lần (mỗi
     * request gọi `handle()` đúng 1 lần), property `$categorySlugCache` chủ yếu để an toàn nếu
     * sau này `handle()` được gọi lặp trong cùng vòng đời Handler (vd test), không phải tối ưu
     * bắt buộc cho luồng hiện tại.
     *
     * KHÔNG dùng `findOrFail()`: `$categoryId` luôn đến từ `PostCategory` đã resolve qua route
     * model binding ở `PublicCategoryController::show()` (route `danh-muc/{category:slug}`) —
     * không có đường gọi nào truyền categoryId không tồn tại, nên throw ở đây không có ý nghĩa
     * xử lý gì thêm ngoài việc bị `catch (Throwable $e)` ở `handle()` nuốt rồi fallback DB — cùng
     * hành vi với để `?->slug` trả `null` như hiện tại, chỉ phức tạp hoá code không cần thiết.
     *
     * KHÔNG cần cache toàn cục (Cache::remember qua request) thay cho property tạm này: đây là 1
     * lượt `WHERE id = ?` theo khoá chính (`post_categories` có index PK) — KHÔNG phải N+1 (N+1
     * là lặp query trong vòng lặp qua nhiều record; ở đây gọi đúng 1 lần/request). Cache toàn cục
     * đổi 1 query rẻ lấy thêm 1 vấn đề thật (phải tự bust cache khi admin đổi slug category qua
     * `CategoryAdminController`) — không đáng đánh đổi cho 1 lookup theo PK.
     */
    private function slugForCategoryId(int $categoryId): ?string
    {
        return $this->categorySlugCache[$categoryId] ??= \Modules\Post\Models\PostCategory::find($categoryId)?->slug;
    }

    private function handleViaDatabase(ListPublishedArticlesQuery $query): LengthAwarePaginator
    {
        // ── Y NGUYÊN toàn bộ logic cũ (không search) ──
        $q = PostArticleTranslation::published()
            ->where('locale', $query->locale)
            ->with(['article.categories', 'article.createdBy'])
            ->whereHas('article');

        if ($query->categoryId) {
            $categoryId = $query->categoryId;
            $q->whereHas('article.categories', fn ($sub) => $sub->where('post_categories.id', $categoryId));
        }

        if ($query->excludeArticleIds) {
            $q->whereNotIn('article_id', $query->excludeArticleIds);
        }

        return $q->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
```

Ghi chú quan trọng — **`categoryId` + `search` cùng lúc** (đường `PublicCategoryController::show()`
đang hỗ trợ): Meilisearch filter theo `category_slugs` (mảng string), không theo `category_id`
(int) như DB — Handler dùng `slugForCategoryId()` (query `PostCategory::find($id)?->slug`, memoize
qua `$categorySlugCache` — xem code §8 ở trên) để đổi từ `int $categoryId` (hợp đồng cũ của
`ListPublishedArticlesQuery`, không đổi) sang `slug` mà Meilisearch filter được — chi tiết này
KHÔNG lộ ra ngoài Handler, giữ đúng cam kết ở §0 mục 4 (không đổi Query/Controller).

`excludeArticleIds` (dùng ở trang chủ để loại bài hero khỏi lưới, xem
`PublicCategoryController::index()`) — **bỏ qua ở nhánh Meilisearch**: nhánh này chỉ chạy khi có
`search`, và khi có `search` thì `PublicCategoryController::index()` đã tự không tính `$featured`/
`$heroSide` (`$featured = $search ? null : ...`) → `$heroArticleIds` luôn rỗng trong trường hợp
này, nên field này không cần xử lý ở nhánh Meilisearch.

## 9. `LoadMoreArticlesHandler` — không đổi

"Xem thêm" (trang chủ) không nhận tham số `search` (`PublicCategoryController::loadMore()` luôn
gọi `LoadMoreArticlesQuery` không có `search`) — ngoài phạm vi thay đổi, giữ nguyên cursor-based
query hiện tại.

## 10. Frontend cổng thông tin

**Không cần sửa Blade/JS nào ở Phase 1.** 2 form tìm kiếm hiện có
(`resources/views/layouts/partials/frontend-header.blade.php` dòng 30-33 và 73-76) submit GET
`?q=...` tới `url()->current()`, luôn rơi vào `PublicCategoryController::index()` hoặc `::show()`
— cả 2 đều đã gọi `ListPublishedArticlesHandler`, nơi duy nhất bị đổi. Người dùng gõ có dấu/gõ sai
nhẹ chính tả sẽ tự thấy kết quả tốt hơn ngay khi Phase 1 xong, không cần biết có Meilisearch phía
sau.

**Không làm ở Phase 1** (ghi nhận cho Phase 2 nếu có nhu cầu thật, không tự triển khai trước):
- Ô gợi ý tức thời (instant-search dropdown, gõ-tới-đâu-gợi-ý-tới-đó) — cần thêm JS gọi trực tiếp
  Meilisearch bằng 1 **search-only API key có phạm vi giới hạn** (không phải `MEILISEARCH_KEY`
  hiện tại trong `.env` — đó là master key, tuyệt đối không lộ ra frontend). Vì `Post` không
  tenant-scoped, key này đơn giản hơn hẳn so với các module có `organization_id` (không cần
  forced-filter theo tổ chức), chỉ cần forced-filter `locale`+`status=published`.
- Facet UI (bộ lọc theo category/tỉnh cạnh kết quả search) — index đã có `category_slugs`/
  `province_code` filterable sẵn từ Phase 1 (§5), nên làm UI facet ở Phase 2 không cần đổi gì
  bên index/backend.

## 11. Rủi ro & Edge case

| Rủi ro | Xử lý |
|---|---|
| Meilisearch service down (`systemctl status meilisearch` fail) | Fallback LIKE tại `ListPublishedArticlesHandler::handle()` (§8) — cổng công khai vẫn chạy, chỉ mất relevance-ranking tạm thời |
| Đổi `searchableAttributes`/`rankingRules` sau khi đã có data | Sửa mảng `index-settings` trong `config/scout.php` → chạy lại `php artisan scout:sync-index-settings` rồi `scout:import` lại toàn bộ — Meilisearch KHÔNG tự re-rank data cũ theo settings mới nếu chỉ đổi settings mà không re-import |
| Bài sponsored hết hạn (`sponsored_end_date` đã qua) | Không ảnh hưởng kết quả search — `isCurrentlySponsored()` chỉ quyết định hiển thị badge, không phải điều kiện `published`, và không nằm trong `toSearchableArray()` |
| Giới hạn `maxTotalHits` (mặc định 1000) của Meilisearch | Không chạm tới ở Phase 1 vì chỉ dùng `paginate()` với `perPage` nhỏ (12-14, xem `PublicCategoryController`) qua nhánh có search; browsing sâu (không search) vẫn đi DB (§0 mục 3) |
| Job đồng bộ thất bại (Meilisearch tạm mất kết nối/queue worker chậm lúc job Scout chạy) | Xem "Vận hành" ngay dưới — **bắt buộc thiết lập trước khi lên production, không hoãn sang sau** |

**Vận hành — bắt buộc thiết lập từ ngày đầu Phase 1 (không phải "nên có sau"):**
- **Vì sao không thể hoãn:** fallback LIKE (§8) khiến trang công khai LUÔN trông "bình thường"
  ngay cả khi Meilisearch đứng hoặc job đồng bộ lỗi hàng loạt — không ai (kể cả biên tập viên)
  nhận ra bằng mắt thường rằng index đang lệch/cũ, vì trang chủ vẫn chạy được qua nhánh DB. Nếu
  không theo dõi `failed_jobs`, index có thể trôi (drift) âm thầm nhiều ngày trước khi ai đó tình
  cờ phát hiện search "thiếu tính năng" (mất typo-tolerance/relevance) mà không biết lý do.
- Theo dõi `failed_jobs` riêng cho job `Laravel\Scout\Jobs\MakeSearchable`/`RemoveFromSearch` —
  đây là job duy nhất Phase 1 thêm vào queue hiện có, tách biệt để không lẫn với job khác
  (`PublishDueTranslationsJob`, `ExpireSponsoredArticlesJob`) khi debug.
- **Lưu ý số lần retry:** repo hiện chưa thấy cấu hình `--tries`/`--backoff` tường minh cho queue
  worker (`php artisan queue:listen`, xem CLAUDE.md) — nghĩa là hành vi retry phụ thuộc vào cách
  worker thật sự được khởi chạy ở production (chưa xác minh được trong phạm vi spec này, cần
  người vận hành xác nhận). Dù retry nhiều lần hay chỉ 1 lần rồi vào thẳng `failed_jobs`, kết quả
  cuối cùng như nhau nếu không ai theo dõi: index lệch mà không ai biết — nên bước 6 ở §13 (thiết
  lập theo dõi) phải xong TRƯỚC khi Phase 1 lên production, không phải việc làm "khi rảnh".
- Cân nhắc alert nếu nhánh fallback LIKE (`Log::warning('Meilisearch search thất bại...')` ở §8)
  xảy ra quá thường xuyên trong 1 khoảng thời gian ngắn — dấu hiệu Meilisearch service (đang chạy
  qua systemd, xem `systemctl status meilisearch`) không ổn định, cần xử lý ở tầng hạ tầng chứ
  không phải ở code.
- Meilisearch Cloud (thay vì self-hosted qua systemd như hiện tại) là lựa chọn có thể cân nhắc
  sau này để giảm gánh vận hành, nhưng KHÔNG cần thiết cho Phase 1 — instance self-hosted hiện tại
  (`127.0.0.1:7700`) đã đủ dùng, đổi sang cloud chỉ là đổi `MEILISEARCH_HOST`/`MEILISEARCH_KEY`,
  không ảnh hưởng gì tới code trong spec này.

## 12. Acceptance Criteria

1. Gõ đúng tiêu đề 1 bài đã publish (có dấu tiếng Việt) vào ô tìm kiếm trang chủ → bài đó xuất
   hiện đầu kết quả.
2. Gõ sai 1-2 ký tự (typo nhẹ, vd thiếu dấu 1 chữ) → Meilisearch vẫn trả đúng bài (khác hành vi
   `LIKE` cũ — phải xác nhận đây là điểm khác biệt thấy được).
3. Gõ 1 từ chỉ xuất hiện trong THÂN bài (không có ở `title`/`excerpt`) → bài đó vẫn ra (khác hành
   vi cũ — `LIKE` cũ chỉ khớp `title`/`excerpt`).
4. Tìm kiếm kết hợp trong 1 trang danh mục (`/bai-viet/danh-muc/{slug}?q=...`) → chỉ trả bài
   thuộc đúng danh mục đó.
5. Dừng `systemctl stop meilisearch` tạm thời → tìm kiếm trang chủ vẫn trả kết quả (qua fallback
   LIKE), không lỗi 500.
6. Sửa category của 1 bài đã publish qua admin (`ArticleAdminController::update`) → tìm theo tên
   category MỚI ra đúng bài ngay (không cần đợi `scout:import` chạy lại) — xác nhận touch-point
   §6.1 hoạt động.
7. Xoá 1 bài đã publish qua admin (`DeleteArticleAction`) → bài biến mất khỏi kết quả search
   công khai ngay lập tức — xác nhận touch-point §6.2 hoạt động.
8. Sửa nội dung thân bài (block-composer) của 1 bài đã publish → tìm theo từ khoá MỚI trong thân
   bài ra đúng bài, từ khoá CŨ (đã bị xoá khỏi bài) không còn ra bài này — xác nhận
   `after_commit=true` (§6.3) hoạt động đúng.

## 13. Kế hoạch triển khai

| Bước | Nội dung | Kiểm tra được |
|---|---|---|
| 1 | `.env` + `config/scout.php` (§4) | `php artisan tinker` → `config('scout.after_commit')` trả `true` |
| 2 | Model (§3) | `php artisan model:show "Modules\Post\Models\PostArticleTranslation"` thấy dùng trait `Searchable` |
| 3a | `index-settings` trong `config/scout.php` (§5) | `php artisan scout:sync-index-settings` chạy không lỗi |
| 3b | Backfill (§7) — **PHẢI chạy SAU bước 3a, không được đảo thứ tự** | `php artisan scout:import ...` — số record import khớp số bản dịch `published` hiện có |
| 4 | Handler (§8) | Acceptance §12 mục 1-5 pass |
| 5 | Touch-point (§6.1, §6.2) | Acceptance §12 mục 6-8 pass |
| 6 | Thiết lập theo dõi `failed_jobs` cho job Scout (§11 "Vận hành") | Đã có alert/dashboard riêng cho `Laravel\Scout\Jobs\MakeSearchable`/`RemoveFromSearch` TRƯỚC khi lên production — không để tới lúc phát sinh sự cố mới thêm |
| 7 | QA thủ công trên staging | Toàn bộ §12 |

> **⚠️ Bước 3a → 3b là thứ tự bắt buộc, không phải gợi ý.** `scout:import` (3b) đẩy document lên
> theo `searchableAttributes`/`filterableAttributes` đang có hiệu lực tại THỜI ĐIỂM import — nếu
> chạy trước khi `scout:sync-index-settings` (3a) áp dụng xong, document bị đẩy lên với settings
> mặc định/cũ của Meilisearch (hoặc settings của lần chạy trước, nếu đây là lần re-deploy), rồi
> phải `scout:import` lại lần nữa mới đúng — dễ quên vì 2 lệnh nằm 2 dòng riêng trong §7 và trông
> như độc lập nhau. Nên gộp chung 1 dòng trong script deploy, KHÔNG tách 2 bước deploy riêng biệt
> có thể chạy xen kẽ với thao tác khác ở giữa.

## 14. Ngoài phạm vi Phase 1

- Search admin (`ListArticlesForAdminHandler`, `ListPendingReviewTranslationsHandler`) — giữ
  `LIKE`, quy mô nội bộ nhỏ, không cấp thiết.
- Module khác (`Product`, `Ocop`, `ProvinceShowcase`, `Lead`, `Customer`...) — kiến trúc khác
  (tenant-scoped, cần filter `organization_id` — xem đánh giá tổng thể đã trao đổi), làm Phase
  riêng nếu có nhu cầu.
- Instant-search widget + scoped public API key (§10).
- Facet UI theo category/tỉnh (§10) — index đã sẵn sàng, chỉ còn thiếu UI.

## 15. Open Questions

1. Dấu tiếng Việt: người dùng gõ KHÔNG dấu ("tim mon an dam") có cần khớp bài CÓ dấu ("tìm món ăn
   dặm") không? Meilisearch không tự bỏ dấu khi so khớp mặc định. Nếu có yêu cầu thật từ người
   dùng portal, Phase 2 nên thêm 2 field song song — `title_normalized`/`body_text_normalized`
   (bỏ dấu qua `Str::ascii()`, giữ nguyên `title`/`body_text` có dấu để ưu tiên exact-match cao
   hơn khi người dùng gõ đúng dấu) — đưa cả 2 field mới vào `searchableAttributes` (§5). Chưa làm
   ở Phase 1 vì chưa có bằng chứng cần.
2. Có cần trang kết quả tìm kiếm riêng (`/tim-kiem?q=...`) thay vì tái dùng trang chủ/danh mục
   không? Hiện tại "Kết quả tìm kiếm: "..."" chỉ là 1 heading khác trên CÙNG layout trang chủ/danh
   mục (`home.blade.php`/`category.blade.php`) — Phase 1 giữ nguyên cách này, không tạo route mới.
