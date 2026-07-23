# Breaking News / Hot News Manager — Tin nóng, tin chạy (ticker), pin đầu trang chủ
**Đặc tả Kỹ thuật Chi tiết — Sẵn sàng Triển khai**

**Phiên bản:** 1.0
**Ngày:** 23/07/2026
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions
**Vị trí:** Feature mới **bên trong `Modules/Post`** (không phải module NWIDART riêng) — `Modules/Post/app/Features/BreakingNews/`
**Module liên quan:** `Modules/Post` (nguồn bài viết, nơi duy nhất render — chỉ trang chủ)

> **Lịch sử phiên bản**
> - **v1.0** — 1 bảng quản trị "tin nóng" (chọn bài đã published, đặt lịch hiển thị theo giờ), render thành 1 dải ticker xoay vòng ghim đầu trang chủ, tự làm mới bằng polling JSON (không cần F5).
> - **v1.0.1** — bổ sung §10 (Rủi ro & Đánh giá thực tiễn) + §10.1 (cải thiện tuỳ chọn, không chặn v1), không đổi thiết kế/schema.

---

## 0. Quyết định đã chốt

| Chủ đề | Hiện trạng codebase | Quyết định spec này | Lý do |
|---|---|---|---|
| **Module mới hay Feature trong `Modules/Post`?** | Đã có tiền lệ `Modules/Post/app/Features/RelatedPosts/` (feature nội tại, chỉ đọc dữ liệu Post, chỉ render trong đúng 1 trang) | Làm **Feature `BreakingNews`** trong `Modules/Post`, KHÔNG tạo module riêng (khác Banner — Banner dùng chung cho cả Post lẫn Event nên phải tách module) | Ticker chỉ hiển thị ở **trang chủ Post** (quyết định dưới), chỉ đọc/ghi dữ liệu Post (`post_articles`) — không có lý do tách module, tránh boilerplate `module.json`/`ServiceProvider` riêng không cần thiết |
| **"Tin nóng" / "tin chạy" / "ticker" / "pin đầu trang chủ" — 1 tính năng hay nhiều?** | N/A | Gộp thành **1 tính năng duy nhất**: 1 danh sách "tin nóng" đang active, render thành **1 dải ticker xoay vòng từng dòng, ghim ở đầu trang chủ** (trên cả `<x-frontend.hero>`) | 4 cụm từ trong yêu cầu mô tả **cùng 1 UX quen thuộc** của báo điện tử (dải "tin nóng" chạy ở đầu trang, mỗi lúc hiện 1 tiêu đề, tự xoay vòng) — không phải 4 khu vực hiển thị độc lập. Tách thành nhiều khu vực riêng (ticker riêng + khối "pin" to riêng) sẽ tăng gấp đôi công sức UI mà không phục vụ thêm mục tiêu nào (tăng pageview/cảm giác cập nhật liên tục) so với 1 dải ticker làm tốt |
| **Vị trí hiển thị** | `home.blade.php` hiện bắt đầu bằng `@if($featured) <x-frontend.hero /> @endif` | Ticker chèn **NGAY TRÊN** `<x-frontend.hero>`, đầu `@section('content')` — **CHỈ trang chủ**, không phải site-wide (khác Banner `header_ad` nằm trong `frontend-header.blade.php`, áp dụng mọi trang) | Đã xác nhận với người yêu cầu: ticker chỉ cần ở trang chủ, không cần chiếm chỗ ở trang danh mục/chi tiết bài viết. Vị trí "trên cả hero" đúng nghĩa "tin nóng còn quan trọng hơn cả tin được biên tập chọn thủ công" |
| **Nguồn nội dung** | Không tái dùng `PostArticle.is_featured` — field đó chỉ chọn **đúng 1 bài** làm hero (`PublicCategoryController::featuredArticle()`, `-&gt;first()`), không có hạn, không badge riêng, mục đích khác hẳn ("bài nổi bật" biên tập chọn tay dài hạn, không phải "tin đang nóng" ngắn hạn) | Bảng **mới** `post_breaking_news` — tham chiếu tới `post_articles` đã published, KHÔNG thêm cột vào `post_articles` | `is_featured` là boolean đơn trên chính bài viết → chỉ biểu diễn được "có/không", không biểu diễn được lịch hiển thị (giờ bắt đầu/kết thúc), thứ tự ưu tiên, hay nhãn tuỳ chỉnh ("NÓNG"/"MỚI"/"KHẨN") — đúng những thứ ticker cần. Tách bảng riêng cũng cho phép 1 bài được "đánh dấu nóng" nhiều lần trong lịch sử (đợt 1 hết hạn, vài ngày sau tin lại nóng trở lại vẫn tạo dòng mới) mà không đụng tới field gốc của bài viết |
| **Độ chính xác lịch hiển thị** | Banner/Sponsored Article dùng cột `date` (chỉ chính xác theo NGÀY, xem `Banner::scopeActive()` và `PostArticle::isCurrentlySponsored()`) | Dùng cột **`datetime`** (`starts_at`/`ends_at`), KHÔNG phải `date` | Tin nóng thường chỉ "nóng" 24-48 giờ rồi rớt xuống — nếu chỉ chính xác theo ngày, 1 tin đăng 23h hôm nay và 1 tin đăng 00h05 hôm sau bị coi "cùng 1 ngày còn hạn" dù thực tế lệch nhau vài phút, sai hẳn bản chất "tính theo giờ" của breaking news |
| **Job dọn dẹp định kỳ** | `ExpireSponsoredArticlesJob` chạy `daily()` để tắt cờ `is_sponsored`; nhưng Banner **không có job nào cả** — nguyên tắc ghi rõ trong spec Banner: *"Không cần job/cron — mỗi lần trang được tải, `scopeActive()` tự lọc theo ngày hiện tại... tự động biến mất khỏi mọi placement mà không cần thao tác dọn dẹp"* | Theo đúng nguyên tắc **Banner** — **KHÔNG tạo job nào** | Ticker chỉ đọc `scopeActive()`/`isCurrentlyBreaking()` tại thời điểm render (giống Banner, không giống Sponsored Article) — tin hết hạn tự biến mất ngay khi qua `ends_at`, không cần đợi job. Không có báo cáo tổng hợp nào cần cột trạng thái "đã hết hạn" phải được job cập nhật trước (khác lý do Sponsored Article cần job: dọn `is_sponsored` cho danh sách lọc quản trị) |
| **Cache trang chủ** | `PublicCategoryController::index()`/`home.blade.php` hiện **KHÔNG** dùng `Cache::remember()` nào (khác `RelatedPosts` — có cache 6h vì self-join nặng) | **KHÔNG cache** danh sách tin nóng — query trực tiếp mỗi request | Đúng mục tiêu "cảm giác cập nhật liên tục": tin nóng phải xuất hiện NGAY khi admin bật, không trễ theo TTL. Trang chủ vốn đã không cache nên không có gì phải "thắng" thêm |
| **Tự làm mới không cần F5** | `Modules/Survey/resources/views/results/public.blade.php` đã có tiền lệ Alpine `x-data` + `setInterval` + `fetch` JSON để tự cập nhật nội dung trang public | Thêm 1 **endpoint JSON công khai nhẹ** (`GET /tin-nong/hien-tai`), ticker poll định kỳ (mặc định 60s, `config('post.breaking_news.poll_seconds')`), nếu danh sách đổi thì cập nhật lại phần xoay vòng mà KHÔNG reload cả trang | Trực tiếp phục vụ mục tiêu đề bài nêu ("cảm giác cập nhật liên tục") — không có polling thì ticker chỉ đổi khi user tự F5, mất đúng cái cảm giác "live" cần có. Tái dùng đúng pattern đã có tiền lệ, không phát minh cơ chế mới |
| **Định dạng hiển thị: marquee CSS chạy chữ liên tục hay xoay vòng từng dòng?** | N/A | **Xoay vòng từng dòng** (hiện 1 tiêu đề, sau N giây fade sang tiêu đề kế tiếp) — KHÔNG dùng marquee CSS/JS chạy chữ ngang liên tục | Marquee (chữ trôi ngang liên tục) khó đọc, khó responsive trên mobile, không thân thiện accessibility (khó dừng để đọc). Xoay vòng từng dòng là mẫu phổ biến hơn ở báo điện tử hiện đại (VD dải "tin nóng" của các báo lớn), dễ làm responsive, dễ thêm nút dừng/điều hướng thủ công |
| **Loại trừ `format=redirect`?** | Related Posts Engine loại `ArticleFormat::Redirect` khỏi ứng viên TỰ ĐỘNG (thuật toán chọn hộ, cần tránh gợi ý bài rời site ngay) | **KHÔNG loại trừ** — admin có thể chọn bất kỳ bài published nào, kể cả `format=redirect` | Khác Related Posts Engine (thuật toán tự động chọn hộ), ở đây **admin tự tay chọn từng bài** để đánh dấu "nóng" — đây là nội dung được duyệt thủ công, admin biết rõ mình đang chọn bài dẫn ra ngoài hay không, không cần rào chắn tự động thay họ quyết định |
| **Đo hiệu quả riêng (click-through) cho tin nóng** | Banner đếm `click_count` riêng; `post_article_redirect_clicks` log riêng theo ngày | **KHÔNG** đo riêng ở v1 — chỉ dựa vào `view_count` sẵn có của bài viết (tăng qua `IncrementArticleViewCountAction` khi user click vào đọc) | Giữ v1 gọn — mục tiêu đề bài là "tăng pageview", đã đo được gián tiếp qua `view_count` tổng của từng bài. Đo riêng "bao nhiêu click đến từ ticker" là cải tiến đo lường, để §9 (ngoài phạm vi) |
| **Quyền quản trị** | Banner/Ocop/Page/CoreIdeaExtractor/Related Posts đều theo Lớp A (`&lt;module&gt;.manage`, seed riêng, không qua `config/permissions.php`) | Permission mới `breaking_news.manage`, gán cho **`platform_ops`** + **`platform_content_head`** | Tin nóng là quyết định biên tập/vận hành nhanh (ai đó cần bật 1 tin lên đầu trang NGAY), đúng vai trò `platform_ops` (vận hành) + `platform_content_head` (toàn quyền nội dung nền tảng) — cùng lý do Banner, không phải nội dung cần qua quy trình duyệt nhiều bước như bài viết gốc (bài đã published từ trước rồi, ở đây chỉ là "đánh dấu thêm") |

---

## 1. Giới thiệu & Mục tiêu

Trang chủ hiện tại (`Modules/Post/resources/views/public/home.blade.php`) có đúng 1 cơ chế "ưu tiên hiển thị": `PostArticle.is_featured` — 1 boolean đơn, chỉ chọn **đúng 1 bài** làm khối hero lớn ở đầu trang (`PublicCategoryController::featuredArticle()`), không có hạn hiển thị, không badge riêng, và biên tập viên có thể tick bao nhiêu bài `is_featured=true` tuỳ ý nhưng chỉ bài mới nhất trong số đó thắng — cơ chế này phù hợp cho "bài nổi bật dài hạn", **không phù hợp cho tin đang nóng** (cần: nhiều tin cùng lúc, có hạn ngắn theo giờ, badge trực quan riêng, cảm giác "đang cập nhật").

**Breaking News Manager** giải quyết đúng khoảng trống này: cho phép platform_ops/platform_content_head chọn N bài viết **đã published**, đặt lịch hiển thị theo giờ (không phải ngày), và hiển thị thành **1 dải ticker xoay vòng ghim ở đầu trang chủ** (trên cả hero) — mỗi vài giây tự chuyển sang tiêu đề tiếp theo, badge "NÓNG" (tuỳ chỉnh được nhãn), và tự làm mới bằng polling JSON để cảm giác "cập nhật liên tục" ngay cả khi người dùng không tự tải lại trang.

**Nguyên tắc thiết kế cốt lõi:** không cần job/cron nào — tin hết hạn tự biến mất ngay khi qua `ends_at` (đúng nguyên tắc Banner), không có báo cáo/thống kê riêng ở v1 (giữ gọn, đo gián tiếp qua `view_count` sẵn có của Post).

---

## 2. Khảo sát hiện trạng

### 2.1 Trang chủ — thứ tự hiển thị hiện tại

`Modules/Post/resources/views/public/home.blade.php:34-51`:
```blade
@section('content')

@if($featured)
<x-frontend.hero :featured="$featured" :side="$heroSide" />
@endif

<x-frontend.promo-bar :categories="$categories" />

@if($isMagazineLayout)
<div class="container">
    @foreach($featureChunks as $chunk)
    <x-frontend.section-feature :lead="$chunk->first()" :side="$chunk->slice(1)" />
    @endforeach
</div>

<x-frontend.event-spotlight :events="$upcomingEvents" />
<x-frontend.cta-band :categories="$categories" />
@endif
```
Ticker mới chèn **NGAY TRƯỚC** `@if($featured)` — không đổi bất kỳ block nào hiện có.

### 2.2 `is_featured` — xác nhận KHÔNG phải cơ chế "tin nóng"

`PublicCategoryController::featuredArticle()` (`Modules/Post/app/Features/PublicReading/Http/PublicCategoryController.php:121-129`):
```php
private function featuredArticle(string $locale): ?PostArticleTranslation
{
    return PostArticleTranslation::published()
        ->where('locale', $locale)
        ->whereHas('article', fn ($q) => $q->where('is_featured', true))
        ->with('article.categories')
        ->orderByDesc('published_at')
        ->first();
}
```
Chỉ lấy **1 bài duy nhất** (`->first()`), không có `start_date`/`end_date`, không giới hạn số bài `is_featured=true` cùng lúc ở tầng nhập liệu. Comment trong `hero.blade.php:11-13` xác nhận rõ: badge "HOT" trong bản thiết kế tham khảo gốc (eva.vn) **chưa từng được làm** trong Post — team đã cố tình thay bằng badge category vì "không có tương đương trong Post".

### 2.3 Tiền lệ lịch chạy theo `is_active` + ngày — `Banner::scopeActive()`

`Modules/Banner/app/Models/Banner.php:90-97`:
```php
public function scopeActive(Builder $query): void
{
    $today = now()->toDateString();

    $query->where('is_active', true)
        ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $today))
        ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today));
}
```
Breaking News dùng lại đúng cấu trúc này nhưng đổi `toDateString()`/`date` thành `now()` (Carbon datetime)/`datetime` — xem §4.1.

### 2.4 Tiền lệ polling public không cần F5 — `Modules/Survey`

`Modules/Survey/resources/views/results/public.blade.php:66-72`:
```js
this.schedulePoll();
},
schedulePoll() {
    this.pollTimer = setInterval(() => {
        this.pollCount++;
        fetch('/api/v1/surveys/{{ $survey->slug }}/result?ref={{ urlencode($ref) }}', {
            headers: { 'Authorization': 'Bearer {{ $token }}', 'Accept': 'application/json' }
        })
        ...
```
Breaking News tái dùng đúng mẫu Alpine `x-data` + `setInterval` + `fetch` này (§7.3) — không cần header `Authorization` vì endpoint hoàn toàn công khai.

### 2.5 Layout dùng chung — xác nhận vị trí `header_ad` KHÔNG đụng độ

`resources/views/layouts/partials/frontend-header.blade.php` (đoạn `.site-header__content`) — `<x-frontend.banner-slot placement="header_ad" />` nằm **trong header, cột `col-lg-9` cạnh logo**, là 1 ô ảnh banner tĩnh, khác hẳn hình thức và vị trí với dải ticker text ghim ở đầu `<main>` (chỉ trang chủ) — không tranh chấp không gian.

---

## 3. Kiến trúc dữ liệu

### 3.1 ERD

```
PostBreakingNews (post_breaking_news)
  ├─ uuid
  ├─ article_id (FK post_articles, cascadeOnDelete)   — bài phải published mới chọn được (validate ở Action)
  ├─ headline_override (nullable string 300)          — tiêu đề ngắn tuỳ chỉnh cho ticker; null → dùng title thật của bài
  ├─ badge_label (nullable string 40)                  — "NÓNG"/"KHẨN"/"MỚI"...; null → dùng config('post.breaking_news.default_badge_label')
  ├─ starts_at (nullable datetime)                     — null = hiển thị ngay
  ├─ ends_at (nullable datetime)                        — null = không tự hết hạn (phải tắt is_active thủ công)
  ├─ sort_order (unsigned smallint, default 0)          — thứ tự trong vòng xoay, nhỏ hơn ưu tiên hiện trước
  ├─ is_active (bool, default true)                     — công tắc tắt/mở thủ công, độc lập với lịch ngày giờ
  ├─ created_by, updated_by, timestamps, soft delete
```

Không có bảng con — 1 dòng = 1 lượt "đánh dấu nóng" cho 1 bài (1 bài có thể được đánh dấu nhiều lần trong lịch sử, mỗi lần là 1 dòng riêng — không unique theo `article_id`).

### 3.2 Migration

`Modules/Post/database/migrations/2026_07_24_000001_create_post_breaking_news_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Breaking_News_Ticker_Technical_Specification.md §3 — "đánh dấu nóng" 1 bài viết đã
 * published, có lịch hiển thị theo GIỜ (không phải ngày, khác Banner/Sponsored) vì tin nóng
 * thường chỉ nóng 24-48h. 1 article có thể có nhiều dòng lịch sử (không unique article_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_breaking_news', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
            $table->string('headline_override', 300)->nullable();
            $table->string('badge_label', 40)->nullable();

            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'starts_at', 'ends_at'], 'idx_breaking_news_active_window');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_breaking_news');
    }
};
```

---

## 4. Model & cấu hình

### 4.1 `PostBreakingNews` model

`Modules/Post/app/Models/PostBreakingNews.php`:
```php
<?php

namespace Modules\Post\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * spec/Breaking_News_Ticker_Technical_Specification.md §3/§4 — "đánh dấu nóng" 1 PostArticle
 * đã published, tài sản nền tảng (không organization_id, cùng nguyên tắc Banner/Post §3.3 v3.0).
 */
class PostBreakingNews extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'post_breaking_news';

    protected $fillable = [
        'uuid', 'article_id', 'headline_override', 'badge_label',
        'starts_at', 'ends_at', 'sort_order', 'is_active',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'starts_at'   => 'datetime',
        'ends_at'     => 'datetime',
        'sort_order'  => 'integer',
        'is_active'   => 'boolean',
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
        return 'uuid';
    }

    // ── Relationships ────────────────────────────────────────────────

    public function article(): BelongsTo
    {
        return $this->belongsTo(PostArticle::class, 'article_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    /**
     * Cùng cấu trúc Banner::scopeActive() (Modules/Banner/app/Models/Banner.php:90-97) nhưng
     * so sánh theo DATETIME (now()), không phải toDateString() — §0 "Độ chính xác lịch hiển thị".
     */
    public function scopeActive(Builder $query): void
    {
        $now = now();

        $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }

    /**
     * Cùng tinh thần PostArticle::isCurrentlySponsored() — check tại thời điểm render, KHÔNG
     * phụ thuộc job dọn dẹp nào (§0 "Job dọn dẹp định kỳ": Breaking News không có job).
     */
    public function isCurrentlyBreaking(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->gt($now)) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->lt($now)) {
            return false;
        }

        return true;
    }

    /**
     * Danh sách tin đang active, đã sẵn sàng render (eager-load article+category, loại bài đã
     * bị xoá mềm). Dùng bởi cả PublicCategoryController::index() (render lần đầu) VÀ endpoint
     * polling JSON §7.3 (cùng 1 nguồn sự thật, tránh 2 nơi query khác nhau lệch kết quả).
     *
     * @return Collection<int, self>
     */
    public static function currentList(int $limit): Collection
    {
        return static::active()
            ->whereHas('article', fn ($q) => $q->whereHas('translations', fn ($t) => $t->published()))
            ->with(['article.categories', 'article.translations' => fn ($t) => $t->published()])
            ->orderBy('sort_order')
            ->orderByDesc('starts_at')
            ->limit($limit)
            ->get();
    }

    /** Tiêu đề hiển thị trên ticker — ưu tiên override, fallback title thật của bài. */
    public function displayHeadline(): string
    {
        return $this->headline_override ?: (string) $this->article?->mainTranslation()?->title;
    }

    /** Nhãn badge — ưu tiên tuỳ chỉnh, fallback config mặc định. */
    public function displayBadgeLabel(): string
    {
        return $this->badge_label ?: (string) config('post.breaking_news.default_badge_label', 'NÓNG');
    }
}
```

### 4.2 `config/post.php` (`Modules/Post/config/config.php`) — thêm khoá `breaking_news`

```php
// spec/Breaking_News_Ticker_Technical_Specification.md — dải ticker "tin nóng" ghim đầu
// trang chủ.
'breaking_news' => [
    'max_ticker_items'     => 8,   // tối đa số tin trong vòng xoay (thừa thì không hiển thị, không lỗi)
    'rotate_seconds'       => 5,   // mỗi tiêu đề hiện bao lâu trước khi chuyển sang tiêu đề kế tiếp
    'poll_seconds'         => 60,  // tần suất ticker tự kiểm tra danh sách mới qua JSON (§7.3)
    'default_badge_label'  => 'NÓNG',
    'default_duration_hours' => 48, // chỉ gợi ý prefill ends_at trên form admin, KHÔNG ép validate
],
```

---

## 5. Business rules

### 5.1 Điều kiện chọn bài

- Chỉ được chọn `PostArticle` có **ít nhất 1 bản dịch `published`** — validate ở Action (`CreateBreakingNewsAction`/`UpdateBreakingNewsAction`), không cho lưu nếu bài chưa/không còn published.
- `format = redirect` **được phép** chọn (§0 — admin tự chịu trách nhiệm, không phải thuật toán tự động).
- Không unique `article_id` — 1 bài có thể được đánh dấu nóng nhiều lần (nhiều dòng lịch sử); UI admin nên (không bắt buộc) cảnh báo mềm nếu bài đó đang có 1 dòng active khác, tránh trùng lặp vô ý.

### 5.2 Hiển thị

- Chỉ hiển thị tin thoả `scopeActive()` **tại đúng thời điểm request** — không có bất kỳ job nào bật/tắt `is_active` hộ (§0).
- Thứ tự vòng xoay: `sort_order` tăng dần, tin cùng `sort_order` thì tin mới bắt đầu (`starts_at`) hơn lên trước.
- Vượt quá `max_ticker_items` (config) → chỉ N tin đầu theo thứ tự trên được đưa vào ticker, các tin còn lại (nếu `is_active=true`) vẫn tồn tại trong DB, chỉ đơn giản chưa "lọt" vòng xoay — không phải lỗi, không cần cảnh báo tự động.
- Không có tin nào active → `<x-frontend.breaking-news-ticker>` không render gì (`@if($items->isNotEmpty())`), trang chủ trông y hệt như trước khi có module này.

---

## 6. Quản trị (Admin CRUD)

### 6.1 Routes — thêm vào `Modules/Post/routes/web.php`

```php
Route::middleware(['auth'])->prefix('dashboard/breaking-news')->name('backend.post.breaking-news.')
    ->group(function (): void {
        Route::resource('items', BreakingNewsAdminController::class)->except(['show'])
            ->parameters(['items' => 'breakingNews']);
    });

// Backend JSON API cho Tabulator — cùng pattern Modules/Banner/routes/web.php
Route::middleware(['auth'])->prefix('backend/api/breaking-news')->name('backend.api.breaking-news.')
    ->group(function () {
        Route::get('items', [BreakingNewsApiController::class, 'index'])->name('items');
    });

// Công khai — endpoint polling JSON (§7.3), KHÔNG yêu cầu đăng nhập
Route::get('tin-nong/hien-tai', [BreakingNewsPublicController::class, 'current'])->name('post.public.breaking-news.current');
```

### 6.2 Giao diện quản trị

- Danh sách: Tabulator (giống danh sách bài viết/banner) — cột: badge/nhãn, tiêu đề hiển thị (override hoặc title thật), bài viết gốc (link), `starts_at`/`ends_at`, `is_active`, thao tác Sửa/Xoá.
- Form tạo/sửa: input tìm bài viết (autocomplete theo tiêu đề, gọi lại endpoint danh sách bài viết đã có sẵn cho trang quản trị Post — không tạo API tìm-bài mới riêng), `headline_override` (tuỳ chọn), `badge_label` (tuỳ chọn, gợi ý sẵn "NÓNG"/"KHẨN"/"MỚI" qua datalist), `starts_at`/`ends_at` (datetime-local, `ends_at` prefill = now + `default_duration_hours` giờ nhưng sửa được), `sort_order`, `is_active`.

### 6.3 Permission

`app/Enums/PermissionEnum.php` — thêm case mới **ngay sau** `CORE_IDEA_EXTRACTOR_USE` (dòng 159, case cuối cùng hiện tại):
```php
// ══ BREAKING NEWS (Tin nóng/tin chạy ghim đầu trang chủ — tài sản nền tảng) ═══
// spec/Breaking_News_Ticker_Technical_Specification.md §6.3 — gán cho platform_ops +
// platform_content_head (BreakingNewsPermissionSeeder), KHÔNG qua config/permissions.php
// (Lớp B) — cùng nguyên tắc BANNER_MANAGE/OCOP_MANAGE/PAGE_MANAGE/CORE_IDEA_EXTRACTOR_USE.
case BREAKING_NEWS_MANAGE = 'breaking_news.manage';
```

Seeder `Modules/Post/database/seeders/BreakingNewsPermissionSeeder.php` — cùng cấu trúc `BannerPermissionSeeder.php` (gán `platform_ops` + `platform_content_head`, sync toàn bộ cho `super-admin`).

`BreakingNewsAdminController` dùng `$this->authorizeResource(PostBreakingNews::class, 'breakingNews')` (cùng pattern `BannerAdminController.php:27`), policy `PostBreakingNewsPolicy` — mọi method chỉ check `$user->can('breaking_news.manage')`, đăng ký qua `Gate::policy(PostBreakingNews::class, PostBreakingNewsPolicy::class)` trong `PostServiceProvider::boot()`.

---

## 7. Render công khai

### 7.1 Controller — `PublicCategoryController::index()`

Thêm 1 dòng lấy danh sách tin nóng, loại trừ khi đang tìm kiếm (cùng cách `$featured` bị đặt `null` khi `$search`):
```php
$breakingNews = $search ? collect() : PostBreakingNews::currentList(
    (int) config('post.breaking_news.max_ticker_items', 8)
);

return view('post::public.home', compact(
    'articles', 'categories', 'locale', 'featured', 'heroSide',
    'upcomingEvents', 'search', 'breakingNews', // MỚI
));
```

### 7.2 Blade component

`resources/views/components/frontend/breaking-news-ticker.blade.php`:
```blade
@props([
    'items', // Collection<PostBreakingNews>, đã with('article.categories', 'article.translations')
])

@if($items->isNotEmpty())
<div class="breaking-news-ticker bg-error text-error-content"
     x-data="breakingNewsTicker({{ Js::from([
         'items' => $items->map(fn ($n) => [
             'badge' => $n->displayBadgeLabel(),
             'headline' => $n->displayHeadline(),
             'url' => route('post.public.article', [
                 'slug' => $n->article->mainTranslation()->slug,
                 'id' => $n->article->mainTranslation()->id,
             ]),
         ])->values(),
         'pollUrl' => route('post.public.breaking-news.current'),
         'rotateMs' => config('post.breaking_news.rotate_seconds') * 1000,
         'pollMs' => config('post.breaking_news.poll_seconds') * 1000,
     ]) }})">
    <div class="container flex items-center gap-3 py-2">
        <span class="badge badge-sm badge-neutral shrink-0" x-text="current().badge"></span>
        <a :href="current().url" class="text-sm font-medium truncate hover:underline" x-text="current().headline"></a>
    </div>
</div>
@endif
```

### 7.3 Alpine component — xoay vòng + polling

`resources/js/frontend.js` (thêm 1 Alpine component mới, cùng chỗ khai báo `loadMoreArticles`/`frontendNav`):
```js
Alpine.data('breakingNewsTicker', (config) => ({
    items: config.items,
    index: 0,
    rotateTimer: null,
    pollTimer: null,

    init() {
        if (this.items.length > 1) {
            this.rotateTimer = setInterval(() => {
                this.index = (this.index + 1) % this.items.length;
            }, config.rotateMs);
        }

        this.pollTimer = setInterval(() => this.refresh(), config.pollMs);
    },

    current() {
        return this.items[this.index] ?? { badge: '', headline: '', url: '#' };
    },

    async refresh() {
        try {
            const res = await fetch(config.pollUrl, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            this.items = data.items;
            this.index = 0;
        } catch (e) {
            // Bỏ qua lỗi mạng tạm thời — vòng poll kế tiếp tự thử lại, không cần báo lỗi cho
            // người đọc (ticker chỉ là nội dung phụ trợ, không phải luồng nghiệp vụ chính).
        }
    },
}));
```

### 7.4 Endpoint polling JSON — `BreakingNewsPublicController::current()`

```php
public function current(): JsonResponse
{
    $items = PostBreakingNews::currentList((int) config('post.breaking_news.max_ticker_items', 8));

    return response()->json([
        'items' => $items->map(fn (PostBreakingNews $n) => [
            'badge'    => $n->displayBadgeLabel(),
            'headline' => $n->displayHeadline(),
            'url'      => route('post.public.article', [
                'slug' => $n->article->mainTranslation()->slug,
                'id'   => $n->article->mainTranslation()->id,
            ]),
        ])->values(),
    ]);
}
```
Dùng lại đúng `PostBreakingNews::currentList()` — cùng 1 nguồn sự thật với lần render đầu (§4.1), tránh lệch kết quả giữa server-render và polling.

---

## 8. Kế hoạch triển khai

1. Migration `post_breaking_news` + model `PostBreakingNews` (§3, §4.1).
2. Thêm khoá `breaking_news` vào `Modules/Post/config/config.php` (§4.2).
3. Permission `breaking_news.manage` + `BreakingNewsPermissionSeeder` + `PostBreakingNewsPolicy` (§6.3).
4. Admin CRUD: `BreakingNewsAdminController` + `BreakingNewsApiController` (Tabulator) + views (§6.1, §6.2).
5. `BreakingNewsPublicController::current()` + route polling JSON (§7.4).
6. Component `<x-frontend.breaking-news-ticker>` + Alpine `breakingNewsTicker` + tích hợp `PublicCategoryController::index()`/`home.blade.php` (§7.1-§7.3).
7. Test: `scopeActive()`/`isCurrentlyBreaking()` đúng theo giờ (không phải ngày), `currentList()` giới hạn đúng `max_ticker_items` và thứ tự, endpoint polling trả đúng dữ liệu, ticker ẩn hoàn toàn khi không có tin active, không hiển thị khi đang tìm kiếm (`$search`).

---

## 9. Ngoài phạm vi (v1)

- **Đo click-through riêng cho ticker** (phân biệt "click từ ticker" với các nguồn khác) — v1 chỉ dựa vào `view_count` tổng của bài viết.
- **Hiển thị ở trang danh mục/chi tiết bài viết** (site-wide) — v1 chỉ trang chủ, theo đúng quyết định đã chốt (§0).
- **Đẩy thông báo đẩy (push notification)/real-time WebSocket** khi có tin nóng mới — v1 chỉ polling JSON định kỳ, đủ cho mục tiêu "cảm giác cập nhật liên tục", không cần hạ tầng real-time phức tạp hơn.
- **Giới hạn/cảnh báo cứng khi 1 bài đang có nhiều dòng active cùng lúc** — v1 chỉ cảnh báo mềm ở UI (nếu làm), không chặn ở tầng validate.
- **Tự động đề xuất bài "nên đánh dấu nóng"** (dựa trên tốc độ tăng view_count đột biến) — v1 hoàn toàn thủ công, admin tự chọn bài.

---

## 10. Rủi ro & Đánh giá thực tiễn

Đối chiếu lại toàn bộ thiết kế ở §1-§9 dưới góc độ vận hành thực tế — không rủi ro nào ở mức chặn triển khai (blocker) ở v1.

| Rủi ro | Mức độ | Đánh giá |
|---|---|---|
| Polling 60s từ nhiều user trên trang chủ | Thấp – Trung bình | `BreakingNewsPublicController::current()` (§7.4) chỉ 1 query đơn giản qua `currentList()` (limit theo `max_ticker_items`, mặc định 8) — nhẹ, không self-join/không tính toán phức tạp như Related Posts Engine. Chấp nhận được ở quy mô hiện tại; nếu traffic tăng mạnh, có thể thêm cache ngắn (15-30s, `Cache::remember()`) hoặc dùng Page Visibility API để dừng poll khi tab không active (§10.1) mà không cần đổi schema |
| Không rate-limit endpoint JSON công khai | Thấp | Endpoint chỉ đọc (`GET`), không ghi gì, không gọi dịch vụ ngoài, tải nhẹ — rủi ro bị lạm dụng (spam request) thấp và hậu quả cũng thấp (chỉ tốn thêm DB query rẻ). Có thể bổ sung `throttle` middleware sau nếu phát hiện bị abuse thật, không cần làm trước khi có tín hiệu cụ thể |
| Nhiều dòng active cùng 1 bài | Thấp | Được phép có chủ đích (§3.1, §5.1 — cho phép lịch sử "đánh dấu nóng nhiều lần"), không phải lỗi dữ liệu. Chỉ cần cảnh báo MỀM ở UI admin (không chặn validate) để tránh trùng lặp vô ý, đúng như đã ghi ở §9 |
| Không đo click-through riêng từ ticker | Thấp (chấp nhận) | Đúng phạm vi đã chốt ở §0/§9 — `view_count` sẵn có của bài viết (tăng qua `IncrementArticleViewCountAction` khi đọc) đã đủ phản ánh gián tiếp hiệu quả "tăng pageview", mục tiêu chính đề bài nêu. Đo tách riêng nguồn click là cải tiến đo lường, không phải yêu cầu cốt lõi của module |
| Phụ thuộc timezone của app | Thấp | `scopeActive()`/`isCurrentlyBreaking()` (§4.1) dùng `now()` — Carbon tự theo `config('app.timezone')` toàn cục, cùng cách mọi so sánh datetime khác trong codebase (VD `PublishDueTranslationsJob`) hoạt động. Không phải rủi ro rêng của module này, chỉ là giả định chuẩn của Laravel — vấn đề chỉ phát sinh nếu `config('app.timezone')` cấu hình sai, không phải lỗi thiết kế của Breaking News |

### 10.1 Cải thiện tuỳ chọn (không chặn v1)

Các điểm sau là cải tiến UX/vận hành có thể làm ở v1.1 nếu cần, không phải thiếu sót của thiết kế v1:

- **Giữ nguyên `index` khi `refresh()` nếu tiêu đề đang xem vẫn còn trong danh sách mới** — hiện tại (§7.3) `refresh()` luôn reset `this.index = 0` sau mỗi lần poll, nghĩa là nếu người đọc đang dừng ở tiêu đề thứ 3 thì sau lần poll kế tiếp (dù danh sách không đổi hoặc chỉ đổi nhẹ) họ bị "kéo về" tiêu đề đầu — chấp nhận được vì tần suất poll (60s) đủ thưa để không gây khó chịu rõ rệt, nhưng có thể mượt hơn bằng cách so khớp `url` của tiêu đề đang xem trong danh sách mới, giữ nguyên vị trí nếu còn tồn tại.
- **Cảnh báo mềm khi admin chọn 1 bài đang có dòng active khác** — thêm ở form tạo/sửa (§6.2), kiểm tra nhanh `PostBreakingNews::active()->where('article_id', ...)->exists()` trước khi submit, không chặn lưu.
- **Cache ngắn (15-30s) trên endpoint JSON** hoặc **dừng polling khi tab ẩn (Page Visibility API, `document.hidden`)** — cả hai đều là tối ưu tải, có thể thêm độc lập nhau, không cần đổi cấu trúc `breakingNewsTicker()` (§7.3) ngoài việc bọc thêm điều kiện trong `init()`/`refresh()`.
- **Rate limiting nhẹ** (`throttle:60,1` hoặc tương tự) cho endpoint công khai — chỉ cần thêm nếu traffic tăng mạnh tới mức đáng lo, không cần làm trước (§10 dòng 2).
