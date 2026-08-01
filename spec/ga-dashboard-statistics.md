# Đặc tả Module: Dashboard & Thống kê Google Analytics (GA4)

**Phiên bản:** 2.1 — bổ sung theo review kỹ thuật (xử lý lỗi, giới hạn maxResults, stale data, index, acceptance criteria). Bản 2.0 đã đối chiếu khung codebase nhưng còn thiếu phần "điều gì xảy ra khi API lỗi/dữ liệu cũ" — bản này bổ sung đầy đủ trước khi implement.
**Ngày:** 01/08/2026
**Package sử dụng:** `spatie/laravel-analytics` `^5.7` — **đã cài đặt**, `config/analytics.php` đã publish (giá trị mặc định, chưa chỉnh). Package chỉ hỗ trợ GA4 (Universal Analytics đã bị bỏ từ v5).
**Mục tiêu:** Lấy dữ liệu thống kê từ Google Analytics 4 về hệ thống Laravel để hiển thị 1 trang tổng quan traffic + cột "Lượt xem GA" trong danh sách bài viết (`Modules/Post`).

---

## 0. Đối chiếu với codebase — các điểm bản 1.0 SAI/thiếu, đã sửa

Bản 1.0 viết theo README tổng quát của package, giả định 1 app Laravel "trơn" — không khớp với dự án này ở các điểm sau:

1. **Không có bảng/model `posts`** — bài viết là `Modules\Post\Models\PostArticleTranslation` (đa ngôn ngữ, mỗi bản dịch 1 dòng), không phải bảng `posts` phẳng.
2. **URL bài viết KHÔNG phải `/bai-viet/{slug}`** — thực tế là `{slug}-d{id}.html` đặt ở **root** (không tiền tố, xem `Modules/Post/routes/web.php` dòng ~161-167), `id` chỉ để phân biệt path với `danh-muc/*`, **không dùng để tra cứu** (tra theo `slug`). Toàn bộ ví dụ/regex mapping ở mục 6 bản 1.0 phải viết lại.
3. **Không còn prefix ngôn ngữ trong URL** (`/vi/`, `/en/`) — Post đã bỏ hẳn `{locale}` khỏi URL, chỉ dùng `config('post.default_locale')` nội bộ. Mục "hỗ trợ prefix đa ngôn ngữ" ở bản 1.0 không áp dụng, đã bỏ.
4. **Không có "trang Dashboard" chung để nhét thêm số liệu GA** — `/dashboard` hiện tại (`App\Http\Controllers\Backend\DashboardController` + `DashboardService`) là dashboard **CRM/Sales/Workflow theo tổ chức** (Lead, WorkflowExecution — lọc `organization_id` qua `TenantContext`), khác hoàn toàn phạm vi 1 property GA4 duy nhất cho TOÀN site. Nhét traffic site vào đây sẽ sai ngữ nghĩa. Đặt trang này làm **trang riêng trong `Modules/Post` admin** (xem mục 1).
5. **Laravel/PHP version sai** — dự án dùng Laravel 13, PHP 8.4 (CLAUDE.md), không phải "10.x/11.x/12.x".
6. Thiếu: RBAC, quy ước CQRS Query/Handler của dự án, cơ chế cache/đồng bộ cụ thể, phân biệt `view_count` nội bộ với `screenPageViews` của GA — **và** (bổ sung ở 2.1) xử lý lỗi API, giới hạn `maxResults`, dữ liệu cũ (stale), index, acceptance criteria.

---

## 1. Vị trí đặt module & quyền truy cập

**Không tạo Module NWIDART mới.** Đặt thành 1 Feature mới trong `Modules/Post` (giống cách `VersionHistory`, `AuthorHub` đã là Feature riêng trong cùng module Post, không tách module con) — vì:
- Đối tượng dùng chính là đội biên tập (platform roles của Post), không phải tổ chức/doanh nghiệp.
- Cần tích hợp trực tiếp vào danh sách bài viết đã có (`Modules/Post/resources/views/admin/articles/index.blade.php`).

```
Modules/Post/app/Features/ContentAnalytics/
  Support/GoogleAnalyticsPageMatcher.php     — parse pagePath → slug, khớp PostArticleTranslation
  Queries/GetAnalyticsOverviewQuery.php       + Handler (tổng quan + time-series + traffic sources)
  Queries/GetTopViewedArticlesQuery.php       + Handler (top N bài theo ga_views_30d ĐÃ đồng bộ — mục 3)
  Http/ContentAnalyticsDashboardController.php — trang tổng quan + API JSON cho chart (ECharts)
  Console/Commands/SyncGoogleAnalyticsStatsCommand.php — đồng bộ định kỳ (mục 3)
```

**Route** — thêm vào `Modules/Post/routes/web.php`, cùng nhóm `dashboard/posts` đã có (`['auth']`, **không** `'tenant'` — giống toàn bộ route Post khác):

```php
Route::get('articles/analytics', [ContentAnalyticsDashboardController::class, 'index'])->name('articles.analytics');
Route::prefix('api/articles/analytics')->name('articles.analytics.')->group(function () {
    Route::get('timeseries', [ContentAnalyticsDashboardController::class, 'timeseries'])->name('timeseries');
    Route::get('top-content', [ContentAnalyticsDashboardController::class, 'topContent'])->name('top-content');
});
```

Đặt route `articles/analytics` **trước** `Route::resource('articles', ...)` — cùng lý do `articles/pending-review` và `articles/needs-freshness-review` đã phải đặt trước (tránh khớp nhầm `articles/{article}`).

**Quyền (RBAC)** — thêm permission mới `post_analytics.view` vào `Modules/Post/database/seeders/PostPermissionSeeder.php` (mirror đúng cấu trúc seeder đã có: `Permission::firstOrCreate(...)` → cấp cho platform roles → `super-admin` vẫn nhận tất cả qua `syncPermissions(Permission::all())`). Tính năng **chỉ đọc**, cấp rộng cho đội biên tập + vận hành, không cấp 8 role tổ chức (Lớp B):

| Role | Cấp `post_analytics.view`? |
|---|---|
| `platform_content_editor`, `platform_content_head`, `platform_section_editor` | ✅ |
| `platform_ops`, `platform_viewer` | ✅ |
| `platform_content_creator`, `platform_content_moderator` | ❌ |
| 8 role tổ chức (Lớp B) | ❌ |

**Authorization — chốt 1 cách duy nhất** (bản 2.0 để mở 2 lựa chọn, gây mơ hồ): dùng inline check, KHÔNG thêm method vào `PostArticlePolicy` (không liên quan CRUD bài viết), mirror đúng style `ArticleAdminController::pendingReview()`:

```php
public function index(Request $request, ...): View
{
    abort_unless($request->user()->can('post_analytics.view'), 403);
    ...
}
```

---

## 2. Phạm vi chức năng

### 2.1. Trang "Thống kê traffic" (`Modules/Post` admin)

Dùng lại đúng khuôn mẫu đã có ở `Modules/Post/resources/views/admin/articles/clicks.blade.php` (summary cards + biểu đồ ECharts theo ngày) — không phát minh layout mới.

**Summary cards** — dùng **1 lệnh `Analytics::get()` duy nhất** với dimension `date`, rồi `sum()` trên Collection trả về (KHÔNG dùng trực tiếp `fetchTotalVisitorsAndPageViews()` cho tổng — hàm đó trả về Collection **theo từng ngày**, không phải số tổng; dùng nó mà quên `sum()` sẽ ra sai số):

```php
$rows = Analytics::get(
    period: $currentPeriod,
    metrics: ['activeUsers', 'screenPageViews', 'sessions'],
    dimensions: ['date'],
);

$totalActiveUsers = $rows->sum('activeUsers');
$totalPageViews   = $rows->sum('screenPageViews');
$totalSessions    = $rows->sum('sessions');
```

**So sánh với kỳ trước — định nghĩa rõ ràng để tránh off-by-one**: `Period::days($days)` (package) tính `startDate = today()->subDays($days)`, `endDate = today()` — GA4 coi cả 2 mốc là NGÀY (inclusive 2 đầu), nên kỳ trước phải lùi thêm 1 ngày để không đếm trùng ranh giới:

```php
$currentPeriod = Period::days($days);                                    // [today-$days .. today]
$previousEnd   = Carbon::today()->subDays($days + 1);                    // ngày liền trước điểm bắt đầu kỳ hiện tại
$previousStart = $previousEnd->copy()->subDays($days);
$previousPeriod = Period::create($previousStart, $previousEnd);          // [today-2*$days-1 .. today-$days-1]
```

**Biểu đồ theo thời gian** (ECharts line chart, đúng pattern `resources/js/modules/echarts.js` + `echarts:ready` event): Active Users & Page Views theo ngày, chọn 7/30/tuỳ chỉnh qua query string `?days=`.

**Top nội dung** — đọc **thẳng từ cột `ga_views_30d` đã đồng bộ sẵn** (`GetTopViewedArticlesQuery`: `PostArticleTranslation::whereNotNull('ga_views_30d')->orderByDesc('ga_views_30d')->limit(10)->get()`), **KHÔNG gọi `Analytics::fetchMostVisitedPages()`** — hàm đó dùng dimension `fullPageUrl`/`pageTitle` (khác `pagePath` dùng để đồng bộ ở mục 3), trộn 2 nguồn dữ liệu khác nhau cho cùng 1 khái niệm "bài xem nhiều" sẽ ra 2 con số lệch nhau, gây khó hiểu khi đối chiếu với cột "Lượt xem GA" ở danh sách bài viết. Dùng chung 1 nguồn (`ga_views_30d`) cho cả "Top nội dung" và cột danh sách — nhất quán, và không tốn thêm lượt gọi GA API khi mở trang.

**Nguồn traffic** — dùng thẳng hàm có sẵn của package:
- Top Referrers → `Analytics::fetchTopReferrers()`
- Top Quốc gia → `Analytics::fetchTopCountries()`
- Top Trình duyệt → `Analytics::fetchTopBrowsers()`
- New vs Returning → `Analytics::fetchUserTypes()`
- Thiết bị (`deviceCategory`, không có hàm dựng sẵn) → `Analytics::get(dimensions: ['deviceCategory'], metrics: ['screenPageViews'])`.

**Ghi chú độ trễ dữ liệu (UI)**: GA4 có độ trễ xử lý ~24-48h với 1 phần dữ liệu (đặc biệt dữ liệu "hôm nay"/"hôm qua"). Trang tổng quan cần hiển thị 1 dòng ghi chú nhỏ dưới tiêu đề: *"Dữ liệu Google Analytics có thể trễ 1-2 ngày so với thời gian thực."* — tránh admin hiểu nhầm "hôm nay = 0 view" là lỗi hệ thống.

### 2.2. Cột "Lượt xem GA" trong danh sách bài viết

- Thêm cột vào Tabulator `Modules/Post/resources/assets/js/pages/article-index.js` (mảng `COLUMNS`): `{ title: 'Lượt xem GA', field: 'ga_views_30d', width: 110, hozAlign: 'center', sorter: 'number', formatter: (cell) => cell.getValue() ?? '—' }` — hiển thị `—` khi `null` (phân biệt "chưa có dữ liệu" với "có dữ liệu, bằng 0", xem mục 6.2).
- Backend: thêm field `ga_views_30d` vào `ArticleListResource.php`, cho phép `ListArticlesForAdminQuery`/`Handler` `orderBy('ga_views_30d')` khi FE gửi `sort=ga_views_30d` — đọc từ cột denormalized đã đồng bộ sẵn (mục 3), KHÔNG gọi GA API trực tiếp trong request list.

### 2.3. Phân biệt với `view_count` nội bộ đã có sẵn

`PostArticleTranslation::view_count` (tăng ở `IncrementArticleViewCountAction`) đã tồn tại — nhưng đây là **bộ đếm request thô**, tăng ở MỌI lượt gọi trang public kể cả bot/crawler (kể cả AI bot vừa được allow trong `robots.txt` — GPTBot/ClaudeBot/PerplexityBot...). GA4 lọc bot tốt hơn và có `activeUsers`/`sessions`. **Không thay thế `view_count`** (vẫn dùng cho `RelatedPosts`) — cột GA views là số liệu BỔ SUNG, hiển thị song song.

---

## 3. Đồng bộ & lưu trữ dữ liệu

**2 lớp cache riêng biệt, không nhầm lẫn:**

1. **HTTP cache của package** (`config/analytics.php::cache_lifetime_in_minutes`, mặc định 1440 phút = 24h) — cache response thô từ Google API, tự động, không cần đụng vào.
2. **Cache dữ liệu cho UI** (phải tự xây):
   - Thêm 2 cột nullable vào `post_article_translations`: `ga_views_30d` (integer), `ga_synced_at` (timestamp) — migration mới (`migration:generate --fresh`, dev phase, không cần lo backward-compat) — **kèm 1 index thường (không unique) trên `ga_views_30d`** để `ORDER BY` của Tabulator (mục 2.2) không full table scan khi số bài viết lớn.
   - **KHÔNG cần thêm index riêng cho `slug`** — WHERE clause của command đồng bộ (bên dưới) LUÔN kèm `locale` cùng `slug`, tận dụng đúng index unique composite `(locale, slug)` đã có sẵn trên bảng (xem mục 6.1 về việc `slug` chỉ unique THEO locale, không unique toàn hệ thống như bản 2.0 giả định sai).

### 3.1. `SyncGoogleAnalyticsStatsCommand`

```php
namespace Modules\Post\Console\Commands;

class SyncGoogleAnalyticsStatsCommand extends Command
{
    protected $signature   = 'post:sync-ga-stats';
    protected $description = 'Đồng bộ lượt xem GA4 (30 ngày) về post_article_translations.ga_views_30d';

    public function handle(): int
    {
        $syncedAt = now();

        try {
            $rows = Analytics::get(
                period: Period::days(30),
                metrics: ['screenPageViews'],
                dimensions: ['pagePath'],
                maxResults: 1000,
                orderBy: [OrderBy::metric('screenPageViews', true)],
            );
        } catch (\Throwable $e) {
            // Xem mục 4 — không throw ra ngoài, log + thoát FAILURE, KHÔNG được đụng tới dữ liệu
            // ga_views_30d đã có (giữ nguyên số liệu cũ, hơn là xoá sạch vì 1 lần gọi API lỗi).
            Log::error('[GA Sync] Gọi Google Analytics API thất bại — giữ nguyên dữ liệu ga_views_30d hiện có.', [
                'message' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }

        $matched = 0;
        foreach ($rows as $row) {
            $slug = app(GoogleAnalyticsPageMatcher::class)->extractSlug($row['pagePath']);
            if (! $slug) {
                continue; // pagePath không phải bài viết (trang chủ, danh-muc/*, module khác...)
            }

            // locale bắt buộc trong WHERE — slug chỉ unique THEO locale (mục 6.1), và tận dụng
            // đúng index composite (locale, slug) đã có sẵn, không cần index slug riêng.
            $updated = PostArticleTranslation::where('locale', config('post.default_locale'))
                ->where('slug', $slug)
                ->update(['ga_views_30d' => (int) $row['screenPageViews'], 'ga_synced_at' => $syncedAt]);

            $matched += $updated; // 0 nếu slug không khớp bài nào (đã xoá/đổi slug) — bỏ qua, không lỗi batch
        }

        // Stale reset: bài KHÔNG xuất hiện trong lần đồng bộ này (rớt khỏi top 1000, hoặc 0 view
        // trong 30 ngày) phải về lại null — nếu không, số liệu cũ sẽ hiển thị "mãi mãi đúng" dù
        // thực tế bài đã hết traffic (dùng Eloquent model, KHÔNG DB::table — để soft-delete tự
        // loại trừ bản ghi đã xoá mềm, không cần where('deleted_at', null) thủ công).
        PostArticleTranslation::whereNotNull('ga_views_30d')
            ->where('ga_synced_at', '<', $syncedAt)
            ->update(['ga_views_30d' => null, 'ga_synced_at' => $syncedAt]);

        $this->info("Đồng bộ xong: {$matched}/{$rows->count()} pagePath khớp bài viết.");

        return self::SUCCESS;
    }
}
```

**Đăng ký lệnh + lịch chạy — trong `Modules/Post/app/Providers/PostServiceProvider.php`, KHÔNG phải `routes/console.php`** (bản 2.0 ghi sai chỗ — Post tự đăng ký lịch của module mình, đúng pattern `PublishDueTranslationsJob`/`ExpireSponsoredArticlesJob`/`MonitorScoutFailedJobsCommand` đã có):

```php
// trong boot(), cùng $this->commands([...]) và callAfterResolving(Schedule::class, ...) đã có
$this->commands([
    ...
    SyncGoogleAnalyticsStatsCommand::class,
]);

$this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
    ...
    $schedule->command(SyncGoogleAnalyticsStatsCommand::class)->hourly()->withoutOverlapping();
});
```

**Known limitation (MVP) — `maxResults: 1000`**: chỉ đồng bộ được top 1000 `pagePath` theo lượt xem trong kỳ. Bài nằm ngoài top 1000 sẽ có `ga_views_30d = null` (không phải sai — đúng nghĩa "không nằm trong nhóm xem nhiều nhất", nhưng nếu tổng số bài viết vượt 1000 và phân bổ traffic đồng đều, một số bài có view thật vẫn có thể bị bỏ sót). **Hướng mở rộng sau** (không làm ở v1, chỉ ghi nhận): package `Analytics::get()` đã hỗ trợ sẵn `offset` (phân trang) và `dimensionFilter` (`?FilterExpression`) — có thể lọc ngay tại GA4 Data API bằng regex `dimensionFilter` khớp pattern `.*-d[0-9]+\.html$` trên `pagePath` (loại các path không phải bài viết NGAY từ phía Google, không tốn quota cho path không cần) kết hợp lặp `offset` để lấy hết thay vì giới hạn cứng 1000.

---

## 4. Xử lý lỗi & Graceful Degradation (bắt buộc trước khi implement)

| Tình huống | Xử lý |
|---|---|
| Thiếu `ANALYTICS_PROPERTY_ID` / file credentials không tồn tại | `ContentAnalyticsDashboardController` bọc mọi lời gọi `Analytics::` trong try/catch; nếu lỗi, trả về `null`/collection rỗng cho view, KHÔNG để lộ exception 500 ra người dùng. |
| Google API trả 403 (sai quyền Service Account) / 429 (rate limit) | Cùng cơ chế try/catch trên — `Log::warning('[GA Dashboard] ...', ['message' => $e->getMessage()])`, view hiển thị **empty state** (xem dưới). |
| `SyncGoogleAnalyticsStatsCommand` lỗi giữa chừng (mất mạng, hết quota) | Đã xử lý ở mục 3.1: catch quanh lời gọi `Analytics::get()` DUY NHẤT (1 lần gọi cho cả batch, không phải N lần/bài — nên "giữa chừng" không thực sự xảy ra ở tầng gọi API; nếu lỗi khi `update()` từng dòng thì để exception thoát tự nhiên, Laravel Scheduler tự log job fail, KHÔNG bọc try/catch quanh vòng lặp update — 1 dòng update lỗi (vd DB mất kết nối) nên dừng hẳn, không âm thầm bỏ qua). |
| View "Thống kê traffic" khi không có dữ liệu (API lỗi hoặc trả rỗng) | Empty state thay vì bảng/chart trống trơn khó hiểu — đoạn giữa trang: `<div class="alert alert-warning">Không thể tải dữ liệu Google Analytics. Kiểm tra cấu hình ANALYTICS_PROPERTY_ID / Service Account trong .env.</div>` (dùng đúng class `alert` daisyUI đã dùng ở các trang admin khác, vd `pending-review.blade.php`). |
| Cột "Lượt xem GA" khi chưa từng sync lần nào | `ga_views_30d`/`ga_synced_at` đều `null` — cột hiển thị `—`, KHÔNG phải lỗi. |

Không cần thêm channel log/notification mới (Slack, email...) — dùng `Log::warning`/`Log::error` kênh mặc định (`LOG_STACK`), đã sẵn hỗ trợ đẩy Slack qua `.env` nếu cần sau này (cùng cách `MonitorScoutFailedJobsCommand` đã ghi chú).

---

## 5. Yêu cầu kỹ thuật

### 5.1. Package & phiên bản (đã cài — chỉ cần cấu hình)
- `spatie/laravel-analytics` `^5.7` (đã có trong `composer.json`), Laravel 13, PHP 8.4.

### 5.2. Cấu hình bắt buộc (`.env`, `config/analytics.php` giữ mặc định)

| Cấu hình | Mô tả | Ghi chú |
|---------|------|--------|
| `ANALYTICS_PROPERTY_ID` | Property ID GA4 | GA4 → Admin → Property Settings |
| Service Account JSON | File credentials | `storage/app/analytics/service-account-credentials.json` (thư mục cần tạo, không commit file này — thêm vào `.gitignore` nếu chưa có) |
| Quyền Service Account | Viewer/Analyst | Thêm vào GA4 Property Access Management |
| Timezone GA property | Kiểm tra khớp `config('app.timezone')` | Xem rủi ro timezone ở mục 7 |

### 5.3. API thực tế của package đã cài (v5.7 — dùng trực tiếp, không viết lại)

```php
use Spatie\Analytics\Facades\Analytics;
use Spatie\Analytics\Period;
use Spatie\Analytics\OrderBy;

Analytics::fetchTopReferrers(Period::days(30));    // pageReferrer, screenPageViews
Analytics::fetchTopCountries(Period::days(30));    // country, screenPageViews
Analytics::fetchTopBrowsers(Period::days(30));     // browser, screenPageViews
Analytics::fetchUserTypes(Period::days(30));       // newVsReturning, activeUsers

// Tổng quan/time-series: dùng get() tường minh (mục 2.1), KHÔNG dùng fetchTotalVisitorsAndPageViews()
// trực tiếp làm "số tổng" (nó trả Collection theo ngày, phải tự sum()).
Analytics::get(
    period: Period::days(30),
    metrics: ['activeUsers', 'screenPageViews', 'sessions'],
    dimensions: ['date'],
);

// Mapping bài viết (mục 3): pagePath, KHÔNG dùng fetchMostVisitedPages() (dimension fullPageUrl/pageTitle,
// khác nguồn với cột "Lượt xem GA" ở danh sách bài viết — xem mục 2.1).
Analytics::get(
    period: Period::days(30),
    metrics: ['screenPageViews'],
    dimensions: ['pagePath'],
    maxResults: 1000,
    orderBy: [OrderBy::metric('screenPageViews', true)],
);
```

---

## 6. Logic map dữ liệu với bài viết (Post)

### 6.1. Quy tắc so khớp

- Khoá so khớp: **`slug` + `locale`** rút ra từ `pagePath` (KHÔNG chỉ `slug` — xem lý do dưới).
- Regex: `^/(?<slug>[a-z0-9\-]+)-d(?<id>[0-9]+)\.html$` (khớp đúng ràng buộc route `->where(['slug' => '[a-z0-9\-]+', 'id' => '[0-9]+'])`); `id` chỉ dùng để NHẬN DIỆN đây là path bài viết, không dùng để tra cứu.
- **`slug` chỉ unique THEO locale, KHÔNG unique toàn hệ thống** — đã xác nhận trực tiếp trong code (`Modules/Post/app/Features/ArticleAuthoring/Http/TranslationController.php:193-198`): `Rule::unique('post_article_translations', 'slug')->where(fn ($q) => $q->where('locale', $locale))` — có `->where('locale', ...)` rõ ràng. (Comment ở `routes/web.php:163` nói "đã đảm bảo duy nhất toàn hệ thống" là **KHÔNG chính xác** — có thể đã lỗi thời so với lúc validation được scope lại theo locale; bản 2.1 dựa vào code validation thật, không dựa vào comment.) Vì route public hiện chỉ phục vụ 1 locale (`config('post.default_locale')`), việc này KHÔNG gây lỗi tra cứu bài đang publish — nhưng command đồng bộ (mục 3.1) BẮT BUỘC lọc thêm `where('locale', config('post.default_locale'))` để không vô tình khớp nhầm 1 bản dịch khác locale có cùng slug (nếu tồn tại ở dạng nháp/chưa publish).
- `pagePath` KHÔNG khớp regex trên (trang chủ `/`, `danh-muc/*`, `tac-gia/*`, hoặc module khác như `/anland`, `/su-kien/*`) → bỏ qua, ngoài phạm vi spec này.
- Không tìm thấy `PostArticleTranslation` nào khớp → bỏ qua dòng đó, không lỗi cả batch (xem mục 3.1, `$matched` đếm số khớp thật để log).

### 6.2. Xử lý khác
- Bỏ qua query string — `pagePath` (khác `fullPageUrl`) GA đã tự loại query string.
- Trailing slash: route không tạo URL có `/` cuối (kết thúc bằng `.html`), không cần xử lý riêng.
- Bài không có dữ liệu GA (mới đăng, chưa đủ dữ liệu, ngoài top 1000, hoặc vừa bị stale-reset — mục 3.1) → `ga_views_30d = null`, FE hiển thị `—` (không phải `0`, phân biệt "chưa có dữ liệu" với "có dữ liệu, bằng 0").
- Soft-delete: command dùng Eloquent (`PostArticleTranslation::where(...)`), KHÔNG `DB::table(...)` — mặc định Eloquent tự loại bản ghi đã `deleted_at` (SoftDeletes), không cần thêm điều kiện thủ công.

---

## 7. Rủi ro & Edge case

| Rủi ro | Mức độ | Xử lý/ghi chú |
|---|---|---|
| GA4 dữ liệu trễ 24-48h | Trung bình | Tooltip/ghi chú trên UI (mục 2.1) — KHÔNG phải bug. |
| Timezone lệch (`config('app.timezone')` của app khác timezone khai báo trên GA4 Property) | Trung bình | `Period::days()` dùng `Carbon::today()` theo timezone app — nếu GA property đặt timezone khác, ranh giới "ngày" có thể lệch tối đa 1 ngày giữa 2 hệ thống. Không tự động bù trừ ở v1 — ghi rõ trong tài liệu vận hành: nên đặt timezone GA4 Property trùng `config('app.timezone')` khi setup. |
| Đổi slug sau khi GA đã ghi nhận `pagePath` cũ | Thấp–Trung bình | View lịch sử GA cho slug cũ không map được nữa (dữ liệu "mồ côi", GA vẫn giữ pagePath cũ tới hết retention). Chấp nhận được — bài liên tục đổi slug vốn đã mất SEO/lịch sử traffic, không phải vấn đề riêng của tính năng này. |
| Nhiều `PostArticleTranslation` cùng `slug` (khác locale) | Thấp | Đã xác nhận CÓ THỂ xảy ra (mục 6.1) — xử lý bằng cách LUÔN lọc thêm `locale` trong command đồng bộ. |
| Bản ghi đã soft-delete | Thấp | Dùng Eloquent (không `DB::table`) → tự động loại trừ, không cần xử lý thêm (mục 6.2). |
| Property GA4 đổi ID / thu hồi quyền Service Account giữa chừng | Thấp | Rơi vào nhánh lỗi API ở mục 4 — không crash, giữ dữ liệu cũ, log warning. |

---

## 8. Acceptance Criteria

- [ ] `php artisan post:sync-ga-stats` chạy thành công, cập nhật `ga_views_30d`/`ga_synced_at` cho các bài có traffic trong 30 ngày.
- [ ] Chạy lại lệnh sync lần 2 với dữ liệu GA giả lập ít bài hơn lần 1 → các bài KHÔNG còn trong kết quả GA lần 2 phải về lại `ga_views_30d = null` (không giữ số cũ — mục 3.1 "stale reset").
- [ ] Cột "Lượt xem GA" hiển thị đúng trong danh sách bài viết, sort tăng/giảm hoạt động (remote sort qua Tabulator), bài chưa sync hiển thị `—` không phải `0`.
- [ ] User không có `post_analytics.view` (vd role `platform_content_creator`) truy cập `articles/analytics` → 403.
- [ ] User có `post_analytics.view` (vd `platform_content_editor`) → xem được trang, đúng dữ liệu.
- [ ] 8 role tổ chức (CEO/Sales/Ops...) không thấy link "Thống kê traffic" ở sidebar, và bị 403 nếu cố truy cập trực tiếp URL.
- [ ] Xoá/đổi tên `service-account-credentials.json` rồi tải lại trang `articles/analytics` → hiển thị empty-state cảnh báo, KHÔNG lỗi 500, không lộ stack trace.
- [ ] Summary cards ra đúng số tổng (so tay với GA4 UI thật cho cùng property/kỳ) — xác nhận không bị nhầm "Collection theo ngày" với "số tổng" (mục 2.1).
- [ ] "Top nội dung" trên trang tổng quan và cột "Lượt xem GA" ở danh sách bài viết cho CÙNG 1 con số với cùng 1 bài (cùng nguồn `ga_views_30d`, mục 2.1).
- [ ] Bài viết có 2 bản dịch (locale khác nhau) cùng slug (nếu dựng được dữ liệu test) → sync chỉ cập nhật đúng bản dịch thuộc `config('post.default_locale')`.

---

## 9. Việc cần làm (tóm tắt theo file)

1. Migration: thêm `ga_views_30d` (integer, nullable) + `ga_synced_at` (timestamp, nullable) + index trên `ga_views_30d` vào `post_article_translations`.
2. `Modules/Post/app/Features/ContentAnalytics/Support/GoogleAnalyticsPageMatcher.php` — regex parse pagePath → slug (trả `null` nếu không khớp).
3. `Modules/Post/app/Console/Commands/SyncGoogleAnalyticsStatsCommand.php` (mục 3.1) + đăng ký trong `PostServiceProvider::boot()` (`$this->commands([...])` + `callAfterResolving(Schedule::class, ...)`).
4. `Queries/GetAnalyticsOverviewQuery.php` + Handler, `Queries/GetTopViewedArticlesQuery.php` + Handler (theo đúng `App\Shared\Contracts\QueryInterface`/`QueryHandlerInterface`).
5. `Http/ContentAnalyticsDashboardController.php` (bọc try/catch theo mục 4) + route (mục 1) + view mới `Modules/Post/resources/views/admin/articles/analytics.blade.php` (mirror layout `clicks.blade.php`, chart theo pattern `resources/js/modules/echarts.js`, có empty-state).
6. `Modules/Post/database/seeders/PostPermissionSeeder.php` — thêm `post_analytics.view` (mục 1).
7. `ArticleListResource.php` + `ListArticlesForAdminQuery/Handler` — thêm field/sort `ga_views_30d`.
8. `Modules/Post/resources/assets/js/pages/article-index.js` — thêm cột Tabulator (formatter hiển thị `—` khi null).
9. Sidebar (`resources/views/layouts/partials/sidebar.blade.php`) — thêm link "Thống kê traffic" trong nhóm "Bài viết" đã có, gate bằng `@can('post_analytics.view')`.

--------------------------------------
Hướng dẫn cấu hình từng bước:

  1. Lấy ANALYTICS_PROPERTY_ID (GA4)

  1. Vào analytics.google.com → chọn đúng property của site (nếu chưa có, tạo property GA4 mới và nhúng đoạn code đo lường vào site trước — nếu site đã có GA4 rồi thì bỏ qua bước này)
  2. Góc dưới trái → Admin/Quản trị → cột Property → Property Settings/Cài đặt tài sản
  3. Copy số PROPERTY ID (chỉ là dãy số, ví dụ 123456789 — không phải Measurement ID dạng G-XXXXXXX, hai cái khác nhau)

  2. Tạo Service Account (Google Cloud) để app gọi API thay bạn

  1. Vào console.cloud.google.com → tạo project mới (hoặc dùng project có sẵn nếu bạn đã có)
  2. Bật API: tìm "Google Analytics Data API" → bấm Enable
  3. Menu trái → IAM & Admin → Service Accounts → Create Service Account
    - Đặt tên bất kỳ (vd ga-dashboard-reader), bỏ qua phần gán role ở bước này (không cần role trên GCP, quyền thật sự cấp ở bước 3 dưới)
  4. Sau khi tạo xong, bấm vào service account vừa tạo → tab Keys → Add Key → Create new key → chọn JSON → tải file JSON về máy

  3. Cấp quyền cho Service Account trong GA4

  1. Quay lại GA4 → Admin/Quản trị → Property Access Management/Quản lý quyền truy cập tài sản
  2. Bấm dấu + → Add users
  3. Dán email của service account (dạng ga-dashboard-reader@ten-project.iam.gserviceaccount.com, xem trong file JSON vừa tải, field client_email)
  4. Chọn role Viewer (Người xem) → Save
 
  4. Đưa file JSON + property ID vào server

  Ran 1 shell command 

● Đã tạo sẵn thư mục storage/app/analytics/. Bạn cần:

  1. Upload file JSON vừa tải ở bước 2 lên server, đặt đúng tên/đường dẫn:
  storage/app/analytics/service-account-credentials.json
  2. Thêm vào file .env (chưa có dòng này):
  ANALYTICS_PROPERTY_ID=123456789
  2. (thay bằng số Property ID thật lấy ở bước 1)

  Thư mục storage/ đã nằm trong .gitignore sẵn rồi nên file credentials này sẽ không bị commit nhầm — không cần thêm gì nữa.
  5. Kiểm tra sau khi cấu hình xong

  Sau khi bạn đã điền ANALYTICS_PROPERTY_ID và đặt file JSON đúng chỗ, chạy thử:

  php artisan post:sync-ga-stats

  Nếu thấy Đồng bộ xong: X/Y pagePath khớp bài viết. là đã kết nối thành công. Nếu vẫn lỗi, báo tôi log lỗi cụ thể (storage/logs/laravel.log, tìm dòng [GA Sync]) — thường là 1 trong 3 nguyên nhân: sai
  Property ID, service account chưa được add Viewer vào đúng property, hoặc đường dẫn file JSON sai.

  Sau đó vào Bài viết → Thống kê traffic trên sidebar để xem trang thật (sẽ hết empty-state, hiện số liệu thật).