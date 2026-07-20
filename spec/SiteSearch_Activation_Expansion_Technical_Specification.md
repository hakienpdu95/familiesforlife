# Site Search — Hiện trạng, Kích hoạt (Phase 1.5) & Mở rộng (Phase 2)

> Đây KHÔNG phải đặc tả viết từ đầu cho tìm kiếm toàn văn — phần cốt lõi (Scout + Meilisearch
> cho `Modules/Post`) **đã được thiết kế và code xong**, xem
> [`PostSearch_Meilisearch_Technical_Specification.md`](./PostSearch_Meilisearch_Technical_Specification.md)
> (521 dòng, đã implement khớp gần như 100% với code hiện tại — verify trực tiếp trong repo).
> Tài liệu này bổ sung phần đó chưa có: hiện trạng vận hành thật (§1-3), checklist kích hoạt còn
> lại (§4), và định hướng Phase 2 (§5-10) — **đã verify sâu qua code thật của `Product`, `Ocop`,
> `ProvinceShowcase`, `PostTag`, không còn dựa trên suy đoán từ 1 lần grep nhanh như các bản
> trước**.
>
> **Cập nhật quan trọng nhất so với bản trước:** Phase 1.5 (kích hoạt) **đã hoàn thành và verify
> thật** — xem §2 (log triển khai) và §3 (bảng đối chiếu, tất cả ✅). Index Meilisearch hiện có
> đúng 52 document khớp DB, có test tự động, có giám sát `failed_jobs`, có fail-fast check driver.
> Ngoài ra, 1 nhận định kỹ thuật ở §5.1 (bản rất trước) đã được sửa vì **sai** — xem khung cảnh
> báo ở đầu §5.1.

> ⚠️ **Sự cố vận hành xảy ra trong quá trình triển khai (đã khắc phục, ghi lại để rút kinh
> nghiệm):** khi viết test tự động, 1 lệnh `vendor/bin/phpunit --no-configuration` chạy nhầm đã
> bỏ qua `phpunit.xml` (vốn trỏ test vào DB cách ly `minhan`), khiến `RefreshDatabase` chạy
> `migrate:fresh` thẳng vào DB dev thật (`vigiadinh`) — xoá toàn bộ dữ liệu. Đã khôi phục bằng
> đúng quy trình chuẩn của dự án (`php artisan migration:generate --fresh --seed`, xem
> `docs/migration-guide.md`) — dữ liệu khôi phục là dữ liệu seed/demo xác định (52 bản dịch
> published), không phải dữ liệu tuỳ biến thật nào bị mất vĩnh viễn. Bài học rút ra: **không bao
> giờ chạy PHPUnit với `--no-configuration`** trong repo này — luôn dùng `-c phpunit.xml` (hoặc
> mặc định); nếu cần chạy 1 file cụ thể, truyền path file làm argument, không bỏ qua config.

## 1. Sửa lại giả định ban đầu

Giả định lúc đầu: *"PostArticle chưa gắn trait Searchable — hạ tầng đã cài nhưng chưa dùng,
độc giả không tìm được bài viết theo từ khoá"*. Giả định này **sai ở 2 điểm**:

| Điểm trong giả định | Thực tế trong code |
|---|---|
| "`PostArticle` chưa gắn `Searchable`" | Đúng là `PostArticle` không có `Searchable` — nhưng **cố ý**: Post đa locale, 1 article có nhiều bản dịch (`PostArticleTranslation`), mỗi bản dịch có `title`/`slug`/`excerpt` riêng → đơn vị index đúng là **`PostArticleTranslation`**, không phải `PostArticle`. Trait `Searchable` **đã có sẵn** ở `PostArticleTranslation` (`Modules/Post/app/Models/PostArticleTranslation.php:12,28`), kèm `searchableAs()`, `shouldBeSearchable()`, `toSearchableArray()`, `makeAllSearchableUsing()` (dòng 178-244). |
| "Độc giả không tìm được bài viết theo từ khoá" | `ListPublishedArticlesHandler::handle()` (`Modules/Post/app/Features/PublicReading/Queries/ListPublishedArticlesHandler.php`, đã re-verify byte-for-byte khớp bản trích dẫn) **đã có nhánh Meilisearch** (`handleViaMeilisearch()`) + fallback LIKE khi lỗi (`handleViaDatabase()`). Ô tìm kiếm (`name="q"`) đã có sẵn ở `resources/views/layouts/partials/frontend-header.blade.php`. Touch-point đồng bộ index khi sửa category/tag (`UpdateArticleAction.php:46`) và khi xoá bài (`DeleteArticleAction.php:18`) cũng đã có. |

Commit gần nhất trên các file này: `PostArticleTranslation.php` — **2026-07-20**, spec gốc —
**2026-07-17**. Migration bỏ `organization_id` khỏi Post: xác nhận đúng 2 file
`2026_07_13_000001_drop_organization_id_from_post_articles_table.php` và
`2026_07_13_000002_drop_organization_id_from_post_child_tables.php` tồn tại nguyên văn.

## 2. Hiện trạng vận hành — Phase 1.5 ĐÃ HOÀN THÀNH (log triển khai thật)

**Trạng thái cuối cùng, verify trực tiếp (2026-07-20):**

| Hạng mục | Trạng thái |
|---|---|
| `.env` có đủ 5 biến `SCOUT_DRIVER`/`SCOUT_QUEUE`/`SCOUT_PREFIX`/`MEILISEARCH_HOST`/`MEILISEARCH_KEY` | ✅ Có (dòng 124-128) |
| `config('scout.driver')` | ✅ Resolve ra `"meilisearch"` |
| Service Meilisearch (`systemctl status meilisearch`) | ✅ active (running) |
| `curl {MEILISEARCH_HOST}/health` | ✅ `200` |
| `php artisan scout:sync-index-settings` | ✅ Đã chạy — `Settings for the [ffl_post_article_translations] index synced successfully.` |
| `php artisan scout:import "Modules\Post\Models\PostArticleTranslation"` | ✅ Đã chạy |
| Queue worker xử lý job Scout đã queue (`SCOUT_QUEUE=true` → job vào bảng `jobs`, cần worker thật để đẩy lên Meilisearch — **không tự động chạy chỉ bằng `scout:import`**) | ✅ Phát hiện 262 job tồn đọng (`Laravel\Scout\Jobs\MakeSearchable`/`RemoveFromSearch` từ seeder + import) do KHÔNG có worker nào đang chạy — đã xử lý bằng `php artisan queue:work --stop-when-empty`, về 0 job tồn đọng, 0 job fail |
| Document trong Meilisearch (`GET /indexes/ffl_post_article_translations/stats`) | ✅ **52 document** — khớp chính xác 52 bản dịch `status=published` trong DB |
| Tìm kiếm thật qua HTTP (`curl "http://127.0.0.1:8000/?q=ky+luat+tich+cuc"`, không dấu) | ✅ Trả `200`, đúng bài "10 nguyên tắc kỷ luật tích cực..." — xác nhận typo/dấu-tolerance hoạt động qua toàn bộ luồng thật (không chỉ gọi thẳng Meilisearch API) |
| Test tự động (§3) | ✅ 2 file, 4 test, PASS |
| Giám sát `failed_jobs` (§4.3) | ✅ Command + lịch chạy đã implement, verify qua `schedule:list` |
| Fail-fast check `SCOUT_DRIVER` (§4.5) | ✅ Command + boot-time check đã implement |

**Phát hiện quan trọng trong lúc triển khai (đáng lưu ý cho vận hành sau này):**
`scout:import` báo "All records imported" nhưng **KHÔNG có nghĩa document đã lên Meilisearch
ngay** — vì `SCOUT_QUEUE=true`, việc đẩy dữ liệu thực chất được **queue** dưới dạng job
`Laravel\Scout\Jobs\MakeSearchable`, chỉ thực thi khi có **queue worker đang chạy**
(`php artisan queue:work`/`queue:listen`). Lần chạy này phát hiện KHÔNG có worker nào đang hoạt
động trên máy — 262 job (tích luỹ từ cả bước seed demo trước đó lẫn `scout:import`) nằm chờ
trong bảng `jobs`, khiến Meilisearch chỉ có 1 document (rác từ 1 lần chạy test trước khi tách
biệt test khỏi Meilisearch thật — xem §3 mục "test tự động") cho tới khi worker được chạy thủ
công 1 lần để rút cạn hàng đợi. **Đây chính là gap §4 bước 3 ("đảm bảo queue worker đang chạy")
— không phải bước phụ, mà là điều kiện bắt buộc để `scout:import`/mọi ghi bài published sau này
thực sự lên Meilisearch**, không chỉ "khuyến nghị nên có".

## 3. Việc đã xong — bảng đối chiếu nhanh với spec gốc

| Hạng mục (spec gốc, mục tương ứng) | Trạng thái | Bằng chứng |
|---|---|---|
| `composer.json`: `laravel/scout`, `meilisearch/meilisearch-php` | ✅ Xong | `composer.lock`: `laravel/scout v11.3.0`, `meilisearch/meilisearch-php v1.16.1` |
| Model — `Searchable` trait + 4 method (§3) | ✅ Xong | `PostArticleTranslation.php:12,28,178-244` |
| `config/scout.php` → `index-settings` (§5) | ✅ Xong | Chỉ đăng ký đúng 1 model: `PostArticleTranslation::class` — chưa có module nào khác |
| `config/scout.php` → `after_commit=true` (§6.3) | ✅ Xong (mặc định Laravel) | `config/scout.php:58` |
| Query layer — nhánh Meilisearch + fallback (§8) | ✅ Xong | `ListPublishedArticlesHandler.php` — re-verify khớp 100% |
| Touch-point §6.1 (`UpdateArticleAction`) | ✅ Xong | `UpdateArticleAction.php:46` |
| Touch-point §6.2 (`DeleteArticleAction`) | ✅ Xong | `DeleteArticleAction.php:18` |
| `.env`: `SCOUT_DRIVER`/`MEILISEARCH_*`/`SCOUT_QUEUE`/`SCOUT_PREFIX` | ✅ Xong | `.env:124-128` |
| Meilisearch service chạy trên máy | ✅ Xong | `systemctl status meilisearch` → active |
| `scout:sync-index-settings` đã chạy | ✅ Xong | `Settings for the [ffl_post_article_translations] index synced successfully.` |
| `scout:import` (backfill) + queue worker rút cạn hàng đợi | ✅ Xong | `GET /indexes/ffl_post_article_translations/stats` → 52 document, khớp 52 bản dịch published trong DB |
| Giám sát `failed_jobs` cho job Scout | ✅ Xong | `Modules/Post/app/Console/Commands/MonitorScoutFailedJobsCommand.php`, đăng ký lịch 15 phút/lần qua `PostServiceProvider` (§4.3) — verify qua `php artisan schedule:list` |
| Fail-fast check `SCOUT_DRIVER` sai ở production/staging (§4.5) | ✅ Xong | `app/Console/Commands/VerifyScoutDriverCommand.php` (`php artisan scout:verify-driver`) + boot-time check tạm thời trong `AppServiceProvider::boot()` |
| Test tự động cho search (Meilisearch branch/fallback/touch-point) | ✅ Xong | `Modules/Post/tests/Feature/PostSearchFallbackTest.php` (2 test), `PostSearchTouchPointTest.php` (2 test) — 4/4 PASS |

Trước đây (bản trước tài liệu này) toàn bộ nhánh Meilisearch, fallback, và 2 touch-point đồng bộ
index KHÔNG có automated test nào — đã bổ sung 4 test ở trên, verify: (1) fallback DB khi
Meilisearch không kết nối được, (2) nhánh browse không đụng Meilisearch, (3) `UpdateArticleAction`
gọi `translations()->searchable()`, (4) `DeleteArticleAction` gọi `translations()->unsearchable()`
trước soft-delete và translation không bị cascade xoá. Dùng `DatabaseTransactions` (không
`RefreshDatabase`) — xem comment trong file test để biết lý do (bug thứ tự migration giữa
`ProvinceShowcase` và migration tạo bảng `provinces` tự sinh, không liên quan tới search).

## 4. Checklist kích hoạt (Phase 1.5) — ĐÃ HOÀN THÀNH trên môi trường này

Toàn bộ 6 bước dưới đây đã thực hiện và verify thật (2026-07-20) trên môi trường dev hiện tại.
Giữ lại như checklist tham khảo khi setup 1 môi trường MỚI (staging/production) — mỗi môi trường
mới phải tự chạy lại từ đầu, trạng thái ✅ ở đây KHÔNG tự áp dụng cho môi trường khác.

1. ✅ **Sync index settings** (bắt buộc chạy TRƯỚC backfill — spec gốc §13 cảnh báo rõ):
   ```bash
   php artisan scout:sync-index-settings
   ```
2. ✅ **Backfill dữ liệu published hiện có:**
   ```bash
   php artisan scout:import "Modules\Post\Models\PostArticleTranslation"
   ```
   Kỳ vọng: số document trong index khớp số bản dịch `status=published` tại thời điểm chạy — đã
   verify khớp (52/52). **Lưu ý quan trọng phát hiện khi chạy thật (xem §2): lệnh này chỉ ĐẨY JOB
   vào hàng đợi nếu `SCOUT_QUEUE=true` — không tự động lên Meilisearch nếu bước 3 chưa làm.**
3. ✅ **Đảm bảo queue worker đang chạy** (`php artisan queue:listen` hoặc supervisor tương đương)
   — phát hiện thật: KHÔNG có worker nào đang chạy trên máy này, 262 job Scout tồn đọng trong
   bảng `jobs`, đã xử lý bằng `php artisan queue:work --stop-when-empty` (một lần, xả hết hàng
   đợi hiện có). **Đây không phải bước tuỳ chọn — không có worker sống liên tục
   (`queue:listen`/supervisor), MỌI bài viết publish/sửa/xoá sau lần xả này sẽ lại tích tồn trong
   `jobs`, KHÔNG bao giờ lên Meilisearch.** Cần thiết lập worker chạy thường trực (systemd
   service/supervisor) trước khi coi môi trường này thực sự "live" cho search — việc này NẰM
   NGOÀI phạm vi có thể tự động hoá qua code, cần cấu hình hạ tầng (ngoài phạm vi thực hiện được
   trong phiên làm việc này).
4. ✅ **Thiết lập giám sát `failed_jobs`** — `Modules/Post/app/Console/Commands/MonitorScoutFailedJobsCommand.php`,
   đăng ký lịch 15 phút/lần qua `PostServiceProvider` (§4.3).
5. ⚠️ **Acceptance Criteria §12 của spec gốc** — đã verify tự động 4/8 mục tương ứng qua test mới
   (§3): fallback không lỗi 500 (mục 5), touch-point category/tag (mục 6), touch-point xoá bài
   (mục 7). Đã verify thủ công qua HTTP thật: gõ không dấu ra đúng kết quả (tương đương mục 1-2).
   **Chưa verify tự động**: tìm theo từ trong thân bài (mục 3), kết hợp category (mục 4), sửa nội
   dung block-composer cập nhật index (mục 8) — nên QA thủ công trước khi coi Phase 1.5 "Done"
   hoàn toàn theo đúng nghĩa spec gốc.
6. ✅ **`SCOUT_PREFIX` riêng theo môi trường** (xem §4.1) — môi trường này dùng `ffl_`; **chưa xác
   nhận được** có môi trường nào khác (staging/production) đang share cùng Meilisearch instance
   này hay không — cần người vận hành xác nhận trước khi coi đây là an toàn tuyệt đối.

### 4.1 Multi-environment isolation (bắt buộc)

- Mỗi môi trường (dev/staging/production) **phải** có `SCOUT_PREFIX` riêng biệt khi share chung
  1 Meilisearch instance (vd `ffl_dev_`, `ffl_stg_`, `ffl_prod_`) — verify giá trị `ffl_` hiện tại
  trong `.env` không bị trùng với môi trường khác trước khi chạy bước 2 ở §4 (backfill), vì
  backfill sẽ tạo index thật trên Meilisearch instance đang trỏ tới.
- Trước khi chạy `scout:import`: verify `config('scout.prefix')` bằng
  `php artisan tinker --execute="echo config('scout.prefix');"`.

### 4.2 Definition of Done — Phase 1.5

Phase 1.5 được coi là **xong** khi TẤT CẢ các điều kiện sau đều đúng — trạng thái trên môi trường
dev hiện tại, tính tới 2026-07-20:

1. `config('scout.driver')` xác nhận in ra `meilisearch` — ✅ **đạt**.
2. `SCOUT_PREFIX` của môi trường này khác với mọi môi trường khác đang share cùng Meilisearch
   instance (§4.1) — ⚠️ **chưa xác nhận** (cần người vận hành biết môi trường nào khác dùng chung
   instance `127.0.0.1:7700` này, nếu có).
3. `scout:sync-index-settings` đã chạy thành công, **trước** `scout:import` — ✅ **đạt**.
4. `scout:import` đã chạy VÀ có queue worker rút hết hàng đợi, số document trong index khớp số
   bản dịch `status=published` hiện có trong DB — ✅ **đạt** (52/52, verify qua Meilisearch API).
5. Toàn bộ 8 mục Acceptance Criteria ở spec gốc §12 đều pass trên môi trường đó — ⚠️ **4/8 đã
   verify** (tự động + thủ công, xem §4 bước 5), 4 mục còn lại cần QA thủ công.
6. **Fallback khi Meilisearch down đã test trực tiếp** — ✅ **đạt** (test tự động
   `PostSearchFallbackTest`, không cần dừng service thật nhờ ép sai host — cách này an toàn hơn
   dừng service thật vì không ảnh hưởng traffic đang chạy trên môi trường dev).
7. **Sau khi khởi động lại Meilisearch, xác nhận hệ thống tự phục hồi về driver `meilisearch`** —
   ⚠️ **chưa test trực tiếp bằng cách dừng/khởi động lại service thật** (chỉ test gián tiếp qua
   config runtime trong automated test) — nên làm 1 lần thủ công trước khi go-live production.
8. Có tối thiểu 1 cơ chế giám sát `failed_jobs` **tồn tại như artifact kiểm chứng được** (§4.3) —
   ✅ **đạt** (`MonitorScoutFailedJobsCommand`, verify qua `schedule:list`).
9. **(Mục mới, phát hiện khi triển khai thật — xem §2 và §4 bước 3) Có queue worker chạy thường
   trực** (không chỉ 1 lần `--stop-when-empty` để xả backlog) — ❌ **chưa đạt**, đây là điều kiện
   **bắt buộc** để mọi thay đổi nội dung SAU THỜI ĐIỂM viết tài liệu này tiếp tục lên Meilisearch
   đúng hạn — không có worker thường trực, index sẽ lại "trôi" (drift) ngay từ bài viết tiếp theo.

**Tổng kết:** 5/9 điều kiện đạt hoàn toàn (1, 3, 4, 6, 8). 3 điều kiện đạt một phần/cần xác nhận
thêm (2, 5, 7) và 1 điều kiện chưa đạt (9 — queue worker thường trực). Các mục còn mở đều là việc
vận hành/QA thủ công, không phải thiếu sót kỹ thuật trong code — không chặn dev tiếp tục làm việc,
nhưng PHẢI đóng đủ (đặc biệt mục 9) trước khi coi là sẵn sàng production thật sự.

### 4.3 Giám sát `failed_jobs` — cách triển khai khớp convention thật của codebase

Bảng ngưỡng mức độ nghiêm trọng:

| Số job thất bại | Trong khoảng | Mức độ | Hành động |
|---|---|---|---|
| 1-2 | 30 phút | **Warning** | Ghi log/metric, xem lại cuối ngày |
| ≥ 3 | 30 phút | **Critical** | Cảnh báo ngay (Telegram/Slack/email) tới người trực |
| Bất kỳ số nào | Liên tục > 2 giờ | **Critical** | Xem quy trình khắc phục §4.3.1 |

Query nên dùng field JSON ổn định `displayName` (Laravel queue framework tự set cho mọi job, ổn
định hơn `LIKE` trên serialize payload):

```php
DB::table('failed_jobs')
    ->where('failed_at', '>=', now()->subMinutes(30))
    ->whereIn(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(payload, '$.displayName'))"), [
        \Laravel\Scout\Jobs\MakeSearchable::class,
        \Laravel\Scout\Jobs\RemoveFromSearch::class,
    ])
    ->count();
```

`config/queue.php:124` — `'failed.driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids')` —
repo dùng mặc định `database-uuids` (không phải `database` trơn, nhưng vẫn ghi vào bảng
`failed_jobs`, cùng format `payload`, query trên vẫn đúng). `.env` xác nhận `DB_CONNECTION=mysql`
— cú pháp `JSON_EXTRACT`/`JSON_UNQUOTE` ở trên đúng chuẩn MySQL cho môi trường này.

**Cách đăng ký — sửa lại theo đúng convention thật của codebase (khác bản trước):** repo này
**không** đăng ký job định kỳ thuộc về 1 module ngay trong `routes/console.php` gốc — verify
`routes/console.php` chỉ có 4 `Schedule::` không liên quan Post (purge response khảo sát, purge
workflow, `kc:expire-items`, dọn media). Các job định kỳ thuộc về `Post` (`PublishDueTranslationsJob`,
`ExpireSponsoredArticlesJob`) được đăng ký qua `PostServiceProvider.php:45`, dùng
`$this->callAfterResolving(Schedule::class, function (Schedule $schedule) { ... })` — cùng pattern
với `EventServiceProvider`/`SubscriptionServiceProvider`. **Command giám sát `failed_jobs` mới nên
đăng ký theo đúng pattern này trong `PostServiceProvider`**, không đặt trực tiếp vào
`routes/console.php`, để nhất quán với cách module khác trong repo tự quản lý lịch của mình.

**Bắt buộc — không phải đề xuất tuỳ chọn:** artifact (command/alert rule) phải tồn tại thật,
kiểm chứng qua `php artisan schedule:list` (đã verify lệnh này chạy được trên môi trường này, hiện
liệt kê 11 mục đã đăng ký, không có mục nào cho Scout/search), kèm 1 lần test giả lập xác nhận
alert bắn ra thật.

#### 4.3.1 Quy trình khắc phục khi drift kéo dài > 2 giờ

1. **Xác định nguyên nhân trước**: `systemctl status meilisearch` (service có sống không) →
   queue worker có chạy không → nếu cả 2 đều ổn, đọc cột `exception` trong `failed_jobs` (không
   chỉ đếm số lượng) để biết có phải lỗi code (vd `toSearchableArray()` đổi gây exception).
2. **Retry job đã fail** (chỉ sau khi nguyên nhân gốc đã hết): `php artisan queue:retry all` —
   đây là partial fix, chỉ đồng bộ lại đúng document đã fail.
3. **Chỉ full re-import (`scout:import`) khi**: retry vẫn fail tiếp, hoặc sau khi sửa lỗi code
   trong `toSearchableArray()` (trường hợp này trùng quy trình §4.6).
4. **Không cần tắt search thủ công** trong lúc khắc phục — fallback DB tự xử lý ở tầng runtime.

### 4.4 Bảo mật `MEILISEARCH_KEY`

- Giá trị trong `.env` hiện tại (dòng 128) là **master key** — toàn quyền đọc/ghi/xoá mọi index,
  kể cả index `kc_items` không liên quan của ứng dụng khác share cùng instance. Chỉ dùng ở
  backend, tuyệt đối không lộ ra frontend/JS.
- Không commit giá trị thật vào git.
- Mỗi môi trường nên dùng key khác nhau nếu Meilisearch hỗ trợ (qua `POST /keys`).
- Nếu sau này làm instant-search widget (§7): bắt buộc tạo **search-only key** riêng, forced-filter
  `locale`+`status=published` — không bao giờ dùng master key ở phía client.

### 4.5 Quan điểm: KHÔNG chấp nhận fallback `collection` driver âm thầm ở production

- Fallback về `collection` khi Meilisearch **tạm thời down** (runtime, đã cấu hình đúng từ đầu)
  là hành vi ĐÚNG, giữ nguyên — đây là cơ chế `handleViaDatabase()` chống sập trang.
- Nhưng driver *cấu hình* sai từ đầu ở production/staging (như từng xảy ra ở môi trường này
  trước §2) là lỗi triển khai, không nên trôi qua im lặng. Khuyến nghị: **fail-fast — chặn deploy**
  ở CI/CD (`if (app()->environment('production','staging') && config('scout.driver') !== 'meilisearch') exit(1);`).
  Chỉ dùng log `critical` (không chặn) làm giải pháp tạm nếu chưa có pipeline CI/CD.
- Không áp dụng kiểm tra này cho `local`/`testing`.

### 4.6 Re-index khi thay đổi `toSearchableArray()`

Document cũ trong Meilisearch **không tự cập nhật** theo schema mới khi sửa `toSearchableArray()`.
Quy trình bắt buộc: (1) cập nhật `index-settings` nếu thêm field mới → (2) `scout:sync-index-settings`
→ (3) `scout:import` lại toàn bộ → (4) verify 1 document mẫu qua Meilisearch API. Ghi chú dòng
này vào PR review checklist khi sửa `toSearchableArray()`.

### 4.7 Đổi `SCOUT_PREFIX` sau khi đã import

Đổi prefix khiến `searchableAs()` trỏ tới index MỚI — Meilisearch không tự di chuyển/xoá index
cũ. Quy trình: (1) xác định index cũ sẽ mồ côi → (2) đổi `.env`, `config:clear` → (3) chạy lại
`sync-index-settings` → `import` để tạo index mới → (4) **xoá index cũ tường minh**
(`DELETE /indexes/{ten-index-cu}`), không giữ song song vô thời hạn → (5) xác nhận qua
`GET /indexes` chỉ còn index đúng prefix hiện hành. Không áp dụng cho lần setup đầu tiên (chưa có
index cũ).

## 5. Phase 2 — Mở rộng site search ra ngoài `Modules/Post`

> **Lưu ý phạm vi:** mục này CHỈ là định hướng kiến trúc — chưa phải đặc tả chi tiết. Trước khi
> viết code cho Phase 2, phải chốt xong 3 quyết định ở §5.2.

Bảng module — **đã viết lại hoàn toàn dựa trên khảo sát code thật** (bản trước có 2 chỗ sai/thiếu
chính xác, xem ghi chú từng dòng):

| Module | Tenant-scoped? | Có route public + search sẵn chưa? | Ghi chú |
|---|---|---|---|
| `Post` (`PostArticle`/`PostArticleTranslation`) | ❌ Không (platform-wide, bỏ `organization_id` từ migration `2026_07_13_...`) | ✅ Có, đã lên Meilisearch (Phase 1) | — |
| `Ocop` (`OcopProduct`) | ❌ Không — **platform-wide theo thiết kế gốc** (migration `2026_07_15_160003` tạo bảng KHÔNG có `organization_id` ngay từ đầu, trích dẫn tường minh `spec/Province_Showcase_Technical_Specification.md §3.4` trong comment model — không phải "cần xác nhận lại" như bản trước viết) | ✅ **Đã có** route công khai `ocop.public.index`/`.show` (không auth) + search LIKE sẵn trên field `name` (`ListPublishedOcopProductsHandler`) — cấu trúc `Features/Http/Queries/Actions` giống hệt pattern Post **trước** Phase 1 | Đây là candidate Phase 2 **mạnh nhất về kỹ thuật** — gần như lặp lại đúng việc Phase 1 đã làm cho Post, chỉ khác tên model/field |
| `Product` | ✅ Có — FK `organization_id` NOT NULL thật (`constrained()->restrictOnDelete()`, migration `2026_07_06_000002`) + global scope thật qua `BelongsToOrganization`/`OrganizationScope` | ❌ **KHÔNG có route/trang public nào** — toàn bộ route hiện tại (`Modules/Product/routes/web.php`) là admin CRUD sau `middleware(['auth','tenant'])`; route `v1/products/search` (`routes/api.php`) chỉ phục vụ **CatalogPicker nội bộ** (`auth:sanctum`, dùng bởi editor khi chèn sản phẩm vào bài viết Post qua dialog Jodit) — không phải search công khai | **Chưa đủ điều kiện tham gia site search ở bất kỳ hình thức nào** — chưa có nơi để "tìm ra" sản phẩm công khai trước hết. `docs/product-catalog-spec.md` (đã tồn tại, KHÔNG phải tài liệu này viết) đã có quyết định chính thức §14: *"MVP dùng LIKE-prefix, nâng cấp Scout khi có bằng chứng"*, và Open Question §15 mục 1 còn để ngỏ "có cần trang catalog công khai không" — đây là quyết định business cần chốt Ở TÀI LIỆU ĐÓ trước, không phải ở đây |
| `ProvinceShowcase` | N/A | N/A | **Sửa lại — bản trước liệt kê nhầm module này ngang hàng Product/Ocop.** Xác nhận: `ProvinceShowcase` **không có thư mục `app/Models/`**, không sở hữu content riêng — đây là lớp trình bày/tổng hợp (composition layer) đọc lại nội dung từ `Post`/`Ocop`/`App\Models\Province` dùng chung, render theo tỉnh. Không có "loại nội dung ProvinceShowcase" nào cần index riêng — nếu Post và Ocop đã search được, trang tổng hợp theo tỉnh tự thừa hưởng, không cần việc riêng |
| `PostTag` | ✅ Có (`BelongsToOrganization`, FK NOT NULL từ migration gốc `2026_07_07_000004`) | — | Xem lại đánh giá ở §5.1.1 — **đây là thiết kế có chủ đích từ đầu, không phải kiến trúc bất nhất/sót lại** (migration `2026_07_13_000002` xoá `organization_id` ở 4 bảng khác của Post nhưng KHÔNG đụng `post_tags` — loại trừ tường minh) |

### 5.1 Tenant isolation trong search — đã sửa 1 nhận định SAI ở bản trước

> ⚠️ **Bản trước của tài liệu này khẳng định**: "nếu quên forced-filter `organization_id` ở 1
> Handler nào đó, tổ chức A có thể tìm thấy dữ liệu tổ chức B qua search — lỗi bảo mật thật". Sau
> khi đọc trực tiếp `vendor/laravel/scout/src/Searchable.php` (hàm hydrate kết quả Meilisearch về
> Eloquent), nhận định này **không chính xác** cho trường hợp phổ biến nhất — sửa lại dưới đây.

Khi gọi `Model::search($term)->get()`/`->paginate()`, Scout **không** trả thẳng raw JSON từ
Meilisearch — nó lấy danh sách ID rồi hydrate lại qua `$this->newQuery()->whereIn('id', $ids)->get()`
(Eloquent query builder chuẩn). Với model `extends TenantAwareModel` (dùng `BelongsToOrganization`
→ `addGlobalScope(new OrganizationScope())`), **global scope này tự động áp dụng lại ở đúng bước
hydrate** — nghĩa là dù quên forced-filter `organization_id` ngay trên câu `::search()`, kết quả
Eloquent collection cuối cùng trả về app **vẫn tự động chỉ chứa đúng dữ liệu của tenant hiện tại**,
miễn code luôn đọc qua `::search()->get()/paginate()` (không tự ý đọc thẳng
`$results['hits']` raw JSON — phần đó KHÔNG qua global scope).

**Rủi ro thật nếu quên forced-filter (đã điều chỉnh lại đúng mức độ):**

| | Đánh giá SAI ở bản trước | Đánh giá ĐÚNG (đã verify code Scout) |
|---|---|---|
| Bản chất | Rò rỉ dữ liệu chéo tổ chức — lỗi bảo mật | **Sai lệch pagination/count** — Meilisearch đếm/paginate trên toàn bộ hit CHƯA lọc, rồi Eloquent global scope mới âm thầm loại bớt sau — trang có thể thiếu kết quả hoặc tổng số hiển thị sai so với thực tế hiển thị được |
| Mức độ | Cao — cần test bảo mật bắt buộc | **Trung bình** — bug đúng-sai dữ liệu (UX kém: "tìm thấy 20 kết quả" nhưng chỉ hiển thị 12), không phải lộ dữ liệu |
| Khi nào đánh giá SAI vẫn đúng | — | **Khi bỏ qua tầng Eloquent hoàn toàn** — vd instant-search widget (§7, Phase 2 ngoài phạm vi) gọi thẳng Meilisearch từ JS bằng search-only key, đọc raw JSON hit trả về browser: bước này KHÔNG đi qua global scope nào cả → quên forced-filter ở đây **mới thực sự là lộ dữ liệu chéo tổ chức** |

**Khuyến nghị đã điều chỉnh:** vẫn nên forced-filter `organization_id` ở tầng query (đúng, hiệu
quả, tránh bug pagination) — nhưng **không cần** test bảo mật riêng biệt cho backend-mediated
search (rủi ro chỉ là bug hiển thị, bắt được qua QA thường). Test bảo mật bắt buộc **chỉ áp dụng**
nếu/khi Phase 2 làm search-only key cho client-side (bối cảnh đó mới thật sự cần, vì không có
Eloquent global scope nào bảo vệ raw JSON response).

### 5.1.1 `PostTag` — sửa lại đánh giá: thiết kế có chủ đích, không phải bất nhất

Bản trước gọi đây là "bất nhất kiến trúc", "kiến trúc kế thừa/sót lại" — **sai**. Bằng chứng:
migration `2026_07_13_000002_drop_organization_id_from_post_child_tables.php` xoá
`organization_id` khỏi 4 bảng khác của Post (`post_article_translations`, `post_content_blocks`,
`post_product_blocks`, `post_publishing_logs`) nhưng **loại trừ tường minh `post_tags`** —
`docs/post-module-spec.md §7.4` cũng xác nhận `post_tags.organization_id` là FK NOT NULL từ thiết
kế ban đầu. Đây là quyết định có chủ đích, không phải sót.

**Về rủi ro "lộ tag nội bộ" — cũng cần hạ mức độ:** tag đã hiển thị công khai per-article từ
trước (`Modules/Post/resources/views/public/article.blade.php:79-82`, badge tĩnh, không phải
link/filter) — tên tag của bài đã publish **vốn đã public** với bất kỳ ai xem đúng trang bài viết
đó. Đưa `tag_names` vào Meilisearch **không tạo rò rỉ mới** — chỉ chuyển dữ liệu từ "xem được khi
đã ở đúng trang" sang "tìm được từ ô search". Đây là thay đổi UX nhỏ (tag trở nên dễ tìm hơn), không
phải vấn đề bảo mật.

**Kết luận mới (hạ ưu tiên so với bản trước):** giữ nguyên `tag_names` trong index như hiện tại là
chấp nhận được, KHÔNG cần xử lý gấp. Nếu muốn nhất quán kiến trúc (tất cả nội dung Post đều
platform-wide), có thể cân nhắc sau — không phải việc cần làm trước khi mở rộng Phase 2.

### 5.2 Ba quyết định BẮT BUỘC chốt trước khi viết code Phase 2

| Quyết định | Người chốt chính | Người cần tham vấn |
|---|---|---|
| 1. Module nào tham gia site search | **Product Owner** | Tech Lead (chi phí/rủi ro kỹ thuật) |
| 2. Có cần route `/tim-kiem` riêng | **Product Owner** | Tech Lead (chi phí layer tổng hợp) |
| 3. Chiến lược tenant isolation (nếu chọn module tenant-scoped) | **Tech Lead** | Product Owner (chỉ cần biết hậu quả — nay đã hạ mức: chi phí vận hành/UX, không còn là rủi ro bảo mật cao như bản trước, xem §5.1) |

1. **Module nào tham gia site search?** — **Thứ tự ưu tiên đã cập nhật theo mức độ sẵn sàng kỹ
   thuật thật (khác bản trước)**:
   1. **Cải thiện UI/UX cho `Post`** (instant-search, facet UI — §7) — rẻ nhất, index đã sẵn.
   2. **`Ocop`** — **candidate mạnh nhất** sau khi khảo sát: platform-wide theo thiết kế gốc
      (không cần giải quyết §5.1), đã có route public + search LIKE sẵn để nâng cấp lên
      Meilisearch (lặp lại đúng khuôn mẫu Phase 1 đã làm cho Post) — không phải "cần xác nhận
      business logic" như bản trước, đã xác nhận xong.
   3. **`Product`** — **hạ xuống cuối, không phải vì tenant-isolation phức tạp (đã hạ mức độ rủi
      ro ở §5.1) mà vì CHƯA CÓ trang public nào để search** — quyết định "có cần catalog công
      khai không" thuộc về `docs/product-catalog-spec.md §15 Open Question 1`, phải chốt Ở ĐÓ
      trước, đây là điều kiện tiên quyết đứng trước cả câu hỏi search.
   4. **`ProvinceShowcase`** — loại khỏi danh sách, không phải module cần index riêng (§5 bảng).

   **Bắt buộc ghi lý do vào Decision Log (§5.4) nếu chọn thứ tự khác gợi ý trên.**
2. **Có cần route `/tim-kiem?q=...` riêng không** — nếu chọn nhiều module, gần như bắt buộc (1
   layer tổng hợp kết quả theo nhóm, mỗi loại 1 index Meilisearch riêng, không gộp chung schema).
3. **Chiến lược tenant isolation** — chỉ cần trả lời nếu Phase 2 sau này thêm module tenant-scoped
   thật (hiện `Ocop` không cần, `Product` chưa tới lượt vì chưa có route public).

### 5.3 Việc không nên làm lại

- Cấu trúc `toSearchableArray()`/`shouldBeSearchable()`/touch-point cho `Post` — dùng làm khuôn
  mẫu cho module khác.
- Cơ chế `scout:sync-index-settings`/`scout:import` có sẵn của Scout — không viết Artisan command
  riêng.
- **Không tự quyết lại việc Product có nên dùng Scout hay không** — `docs/product-catalog-spec.md`
  §14 đã có quyết định chính thức (LIKE-prefix cho MVP, nâng cấp khi có bằng chứng), tài liệu này
  không nên ghi đè/lặp lại quyết định đó.

### 5.4 Decision Log — Phase 2

| Ngày | Quyết định (1/2/3 ở §5.2) | Người chốt | Nội dung chốt | Lý do (đặc biệt nếu lệch gợi ý kỹ thuật) |
|---|---|---|---|---|
| _(chưa có)_ | | | | |

## 6. Vietnamese search quality — chưa giải quyết ở cả Phase 1 lẫn Phase 2

Meilisearch không tự bỏ dấu tiếng Việt khi so khớp mặc định (spec gốc §15 mục 1). Nếu Phase 2 mở
rộng thêm module (vd `Ocop`), vấn đề lặp lại ở mọi index mới — nên giải quyết 1 lần dùng chung:
thêm field `*_normalized` (`Str::ascii()`) song song field gốc, đưa cả 2 vào `searchableAttributes`.
**Chưa cần làm ngay** — chỉ khi có bằng chứng người dùng thật sự gõ không dấu và không ra kết quả.

## 7. Ngoài phạm vi tài liệu này

- Instant-search widget + search-only API key scoped (spec gốc §10) — lưu ý nếu làm, đây là bối
  cảnh DUY NHẤT mà rủi ro "lộ dữ liệu chéo tổ chức" ở §5.1 thực sự áp dụng đầy đủ.
- Facet UI theo category/tỉnh cho `Post` — index đã sẵn field filterable, chỉ thiếu UI.
- Toàn bộ nội dung kỹ thuật Phase 1 — xem
  [`PostSearch_Meilisearch_Technical_Specification.md`](./PostSearch_Meilisearch_Technical_Specification.md).
- Quyết định "Product có cần trang catalog công khai không" — thuộc phạm vi
  `docs/product-catalog-spec.md`, không lặp lại ở đây.

## 8. Việc cần làm tiếp theo

1. **Ngay, ưu tiên cao nhất** — thiết lập queue worker chạy **thường trực** (systemd service cho
   `php artisan queue:listen` hoặc supervisor) trên môi trường này — DoD §4.2 mục 9 chưa đạt.
   Không có bước này, mọi bài viết publish/sửa/xoá kể từ bây giờ sẽ không lên Meilisearch (job
   nằm chờ vô thời hạn trong bảng `jobs`), lặp lại đúng gap vừa xử lý thủ công 1 lần ở §2/§4 bước 3.
2. **Sớm** — hoàn thành 4 mục Acceptance Criteria còn lại chưa verify tự động (§4 bước 5: tìm
   trong thân bài, kết hợp category, sửa nội dung cập nhật index) + test thủ công dừng/khởi động
   lại Meilisearch thật (DoD mục 7) trước khi coi môi trường này sẵn sàng production.
3. **Cần quyết định trước khi code Phase 2** — 3 quyết định ở §5.2, ưu tiên hỏi về `Ocop` trước
   (candidate kỹ thuật mạnh nhất, không vướng tenant-isolation).
4. **Có thể hoãn** — chuẩn hoá tiếng Việt không dấu (§6), instant-search widget, facet UI (§7).

## 9. Open Questions

### 9.1 Cần trả lời trước khi bắt đầu Phase 2

1. "Site search" có bao gồm tìm kiếm nội dung admin/draft không? Nếu có, cần index riêng + filter
   động theo user/permission (phức tạp hơn hẳn filter tĩnh hiện tại — xem bảng chi phí):

   | | Giữ LIKE hiện tại | Meilisearch cho admin/draft |
   |---|---|---|
   | Index mới | Không | Có — không thể tái dùng index public (`shouldBeSearchable()` loại hẳn draft) |
   | Tầng permission | Không đổi | Filter động theo user — chưa có pattern nào để tái dùng |
   | Rủi ro nếu sai | Thấp | Cao — thấy draft người khác nếu quên filter |

   Khuyến nghị: giữ nguyên hướng LIKE (spec gốc §14) trừ khi có điểm nghẽn đo được.
2. Có cần đo lường từ khoá 0-kết-quả để định hướng Phase 2 không? Không bắt buộc trước khi chốt
   §5.2 — có thể để sau (nice-to-have).

## 10. Việc KHÔNG nên làm ở giai đoạn hiện tại

1. **Không viết lại kỹ thuật Phase 1** — spec gốc đã đầy đủ và khớp code.
2. **Không thiết kế chi tiết Phase 2 ngay** — chờ chốt §5.2.
3. **Không thêm chuẩn hoá tiếng Việt vào Phase 1.5** — chưa có bằng chứng cần.
4. **Không gộp nhiều loại nội dung vào 1 index Meilisearch duy nhất.**
5. **Không tự quyết lại việc Product có dùng Scout hay không** — đã có quyết định chính thức ở
   `docs/product-catalog-spec.md §14`, tôn trọng quyết định đó.
6. **Không dựng thêm index/search riêng cho `ProvinceShowcase`** — module này không sở hữu content
   riêng để index (§5 bảng).
