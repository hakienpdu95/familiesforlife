# Media Library — Hợp nhất quản lý file/ảnh dùng chung

> **Yêu cầu gốc:** "spatie/laravel-medialibrary đã cài nhưng chưa dùng ở đâu; `cover_image_url`
> hiện chỉ là 1 field string đơn giản. Nếu muốn tái sử dụng ảnh giữa Post/Banner/Newsletter và tự
> sinh thumbnail/responsive image, cần một Media module trung tâm."
>
> **Tiền đề này SAI ở điểm quan trọng nhất — đã verify qua code thật (2026-07-21):**
> `spatie/laravel-medialibrary` (`composer.json`: `^11.22`, resolved `11.23.2`) **không phải
> "chưa dùng ở đâu"** — đã có 1 hệ thống Media tập trung khá hoàn chỉnh (`App\Models\Media`,
> `MediaUploadService`, `MediaUrlService`, config, 2 luồng upload FilePond/Jodit, lệnh dọn rác)
> **đang chạy thật** cho `Modules\Organization` (logo) và cho ảnh chèn trong nội dung bài viết
> Post qua Jodit editor. Vấn đề thật không phải "xây Media module từ số 0", mà là: **4 cách quản
> lý ảnh khác nhau đang tồn tại song song** trong cùng codebase (xem §4), và hệ thống Media tập
> trung có sẵn **có ít nhất 2 lỗ hổng kỹ thuật nghiêm trọng** cần giải quyết trước khi mở rộng cho
> thêm module (xem §5) — nếu không, mở rộng thêm sẽ nhân bản lỗi thay vì hợp nhất.

## Critical Path — ✅ ĐÃ IMPLEMENT XONG (2026-07-21)

Tài liệu này dày, đọc lướt phần này là đủ để biết thứ tự làm — chi tiết từng bước ở các mục tương ứng:

1. ✅ **Sửa §5.1** (tenant isolation — ảnh biến mất giữa tổ chức) — code sketch ở §7.1, DoD ở §5.4.
2. ✅ **Sửa §5.2** (`reassociateOrphans()` + lớp phòng thủ trong `media:cleanup-orphans`) — DoD ở §5.4.
3. ✅ **Tích hợp Post cover image** (§8) — gap rõ nhất trong yêu cầu gốc.
4. ✅ **Tích hợp Post — ảnh content block (Jodit)** — §5.2 DoD đã đạt.
5. ✅ **Tích hợp Ocop/Banner** (§8) — collection `cover` cho Ocop, `banner` mới cho Banner.

**Xem §13 (log triển khai thật) để biết chi tiết implement + 3 bug thật phát hiện thêm trong quá
trình làm (2 bug ở `reassociateOrphans()`/path sau reassociate, chưa từng lộ ra vì trước đó hàm
này không ai gọi).**

## 1. Sửa lại tiền đề ban đầu

| Điểm trong yêu cầu gốc | Thực tế trong code |
|---|---|
| "spatie/laravel-medialibrary đã cài nhưng chưa dùng ở đâu" | **Sai.** Đã tích hợp đầy đủ ở tầng `App\` — bảng `media` đã tạo và có dữ liệu thật, `App\Models\Media extends SpatieMedia` (`app/Models/Media.php:22`), `app/Services/Media/{MediaUploadService,MediaUrlService,MediaPathGenerator,MediaMigrateService}.php`, trait drop-in `app/Traits/HasTenantMedia.php`, 2 controller upload (`app/Http/Controllers/Api/{MediaUploadController,MediaJoditUploadController}.php`), 3 command vận hành (`media:cleanup-orphans`, `media:migrate-disk`, `media:stats`). Consumer thật: `Modules\Organization\Models\Organization` (logo) + mọi ảnh chèn trong Jodit mini-editor toàn app (bao gồm content block của Post — `resources/js/modules/jodit.js:121` post thẳng tới `/api/v1/media/jodit-upload`). |
| "cover_image_url hiện chỉ là 1 field string đơn giản" | **Đúng, xác nhận.** `Modules/Post/database/migrations/2026_07_07_000002_create_post_articles_table.php:21` — `$table->string('cover_image_url', 500)->nullable();`. Form admin (`Modules/Post/resources/views/admin/articles/edit.blade.php:473`) là **input text thô** (`type="text"`), biên tập viên tự dán URL — **không có UI upload nào** cho cover image. |
| "cần một Media module trung tâm" | **Không cần viết mới** — cần **hợp nhất 3 hệ thống thủ công đang tồn tại song song** (Ocop/Banner dùng Intervention Image thủ công, Event dùng pattern riêng thứ 3, Post dùng text field thô) vào hệ thống Media tập trung **đã có sẵn** — xem §4, §6. |

## 2. Hiện trạng thật — hệ thống Media tập trung đã build (verify code thật)

### 2.1 Kiến trúc đã có

| Thành phần | File | Vai trò |
|---|---|---|
| Model | `app/Models/Media.php:22` | `extends Spatie\MediaLibrary\...\Media`, thêm `BelongsToOrganization` — **tenant-scoped** (xem §5.1 vì sao đây là vấn đề) |
| Config Spatie | `config/media-library.php` | `media_model` tùy biến, `path_generator` tùy biến, `image_driver=gd`, `queue_conversions_by_default=false` (chạy đồng bộ) |
| Config domain | `config/media.php` | 7 collection: `avatar`/`logo`/`thumbnail`/`cover`/`attachments`/`attachments_private`/`jodit_content` — mỗi collection có `max_size_kb`/`allowed_mime`/`is_public`/`conversions`; `conversion_settings`: `thumb` (150×150 crop), `medium` (800w scale), `preview` (1200w scale), encode WebP |
| Service upload | `app/Services/Media/MediaUploadService.php` | `upload()` (dòng 25-61), `delete()` (69-83), `reassociateOrphans()` (131-155), `reassociateFilePondDrafts()` (166-190), `runConversions()` (197-266, Intervention Image v4 thủ công — **không dùng** API `addMediaConversion()` gốc của Spatie) |
| Service URL | `app/Services/Media/MediaUrlService.php` | `url()` — resolve theo thứ tự: file private → presigned `temporaryUrl()`; `disk=external` → trả thẳng `file_name` (backward-compat); có `MEDIA_CDN_URL` → ghép CDN; mặc định → `Storage::disk()->url()` |
| Path generator | `app/Services/Media/MediaPathGenerator.php` | `media/{org_id}/{module}/{entity_type}/{entity_id}/{uuid}/` |
| Trait drop-in | `app/Traits/HasTenantMedia.php` | Model chỉ cần `implements HasMedia` + `use HasTenantMedia;` là dùng được `getMediaUrl()`/`getFirstMediaUrl()` ngay |
| Draft holder | `app/Models/JoditDraft.php`, `app/Models/FilePondDraft.php` | Giữ ảnh tạm trước khi form/bài viết được lưu |
| Cleanup | `app/Console/Commands/MediaCleanupOrphansCommand.php` | `media:cleanup-orphans` — đã đăng ký lịch `everyFourHours()` (`routes/console.php:28-31`) |

### 2.2 Hai luồng upload đã hoạt động

**Luồng A — Jodit inline-image** (dùng cho content block Post, và bất kỳ nơi nào dùng chung
`resources/js/modules/jodit.js`):

1. Người soạn dán/chèn ảnh vào Jodit mini-editor → JS post file tới
   `POST /api/v1/media/jodit-upload` (`resources/js/modules/jodit.js:121`).
2. `MediaJoditUploadController::store()` (`app/Http/Controllers/Api/MediaJoditUploadController.php:38-75`)
   gọi `MediaUploadService::upload($file, $draft, 'jodit_content')` — `$draft` là 1
   `JoditDraft` (tạo mới hoặc tái dùng theo `X-Context-Type`/`X-Context-Id`), set
   `last_touched_at = now()`.
3. HTML trả về `<img src="{url}" data-media-uuid="{uuid}">` — URL này được nhúng thẳng vào
   `post_content_blocks.text_html` khi lưu bài (không phải quan hệ Eloquent, chỉ là URL string
   trong HTML).
4. **Thiết kế dự kiến** (theo docblock `MediaUploadService::reassociateOrphans()` dòng 123-130 và
   `JoditDraft.php:15`): khi lưu bài, gọi `reassociateOrphans($translation, $uuids)` để chuyển
   `model_type`/`model_id` của các Media này từ `JoditDraft` sang chính bài viết thật, đồng thời
   xoá các ảnh không còn được nhắc tới trong content mới.
5. **Thực tế xác nhận qua `grep -rn "reassociateOrphans" Modules/ app/`: hàm này KHÔNG được gọi ở
   BẤT KỲ đâu ngoài định nghĩa/docblock của chính nó.** Xem hậu quả ở §5.2.

**Luồng B — FilePond** (avatar/logo/thumbnail/cover/attachments):

1. `POST /api/v1/media/upload` với header `X-Collection` (bắt buộc) + `X-Context-Type`/
   `X-Context-Id` (tuỳ chọn).
2. Có context (form sửa, entity đã tồn tại) → gắn thẳng vào entity thật ngay
   (`MediaUploadController::resolveModel()`, dòng 166-181).
3. Không có context (form tạo mới) → gắn tạm vào `FilePondDraft`, gọi
   `reassociateFilePondDrafts()` khi form submit thành công.
4. Collection thuộc `SINGLE_FILE_COLLECTIONS` (`avatar`/`logo`/`thumbnail`/`cover`) → ảnh cũ tự
   xoá khi ảnh mới lên thành công (dòng 98-102).

### 2.3 Consumer duy nhất hiện tại — `Modules\Organization`

`grep -rn "implements HasMedia\|use HasTenantMedia" --include="*.php"` toàn repo chỉ ra đúng 3 kết
quả: `App\Models\JoditDraft`, `App\Models\FilePondDraft`, `Modules\Organization\Models\Organization`
(logo). **Post/Banner/Ocop/Event hoàn toàn chưa khai báo `HasMedia` ở model nào** — dù ảnh chèn
trong Jodit content block của Post đã đi qua luồng A ở trên (chỉ là chưa được "nhận" vào entity
thật do thiếu bước 4 ở §2.2).

## 3. Vì sao Post/Banner/Ocop/Event KHÔNG dùng hệ thống Media — quyết định có chủ đích, đã ghi lại

Comment tường minh trong `Modules/Ocop/app/Features/OcopProductManagement/Actions/StoreOcopProductImageAction.php:12-15`:

> "copy nguyên pattern `StoreBannerImageAction` (Intervention Image v4...) — **KHÔNG dùng Spatie
> MediaLibrary, tránh 2 cách quản lý ảnh song song trong cùng codebase**."

Đây là quyết định đã cân nhắc, không phải sơ suất. Nhưng trớ trêu: **quyết định "tránh 2 hệ song
song" lại chính là nguyên nhân tạo ra ít nhất 4 hệ song song thật** (xem §4) — vì mỗi module sau
đó lại copy pattern thủ công riêng thay vì dùng chung 1 hệ nào, kể cả hệ Spatie đã có sẵn.

## 4. Bốn cách quản lý ảnh khác nhau đang tồn tại song song

| Module | Cách lưu | Field | Resize/thumbnail | File |
|---|---|---|---|---|
| `Organization` (logo) | Spatie MediaLibrary qua `HasTenantMedia` | Quan hệ `media` (bảng `media`) | `thumb`/`medium` (WebP, Intervention Image thủ công trong `MediaUploadService::runConversions()`) | Model chỉ cần `implements HasMedia` |
| Ảnh chèn trong content block Post (Jodit) | Spatie MediaLibrary, collection `jodit_content`, qua `JoditDraft` (**chưa reassociate — xem §5.2**) | URL nhúng thẳng trong `post_content_blocks.text_html` (không phải quan hệ) | `medium` | `App\Models\JoditDraft` |
| `PostArticle.cover_image_url` | **Không upload — input text thô**, biên tập viên tự dán URL | `varchar(500)` string đơn | Không có | `Modules/Post/database/migrations/2026_07_07_000002_create_post_articles_table.php:21` |
| `OcopProduct` (ảnh sản phẩm) | Intervention Image v4 thủ công, `getimagesize()` + `scaleDown()` nếu > `config('ocop.max_image_width')` | `image_path`/`image_width`/`image_height`/`image_size_bytes` — 4 cột phẳng | 1 bản resize duy nhất (không phải nhiều "conversion") | `Modules/Ocop/app/Features/OcopProductManagement/Actions/StoreOcopProductImageAction.php` |
| `Banner` (ảnh banner) | Cùng pattern Ocop hệt (copy nguyên văn) | `image_path`/`image_width`/`image_height`/`image_size_bytes` | 1 bản resize duy nhất | `Modules/Banner/Features/BannerManagement/Actions/StoreBannerImageAction.php` |
| `Event` (poster sự kiện) | Pattern thứ 3 — chỉ validate (chặn ảnh chân dung), không resize | `poster_path`/`poster_width`/`poster_height`/`poster_size_bytes` | Không có | `Modules/Event/Features/EventModeration/Actions/StoreEventPosterAction.php` |
| `Newsletter` | Chưa có field/xử lý ảnh nào | — | — | Xác nhận: `spec/Newsletter_Technical_Specification.md` không nhắc gì tới ảnh/media |

**Hệ quả của việc phân mảnh:** không có nơi nào tái sử dụng được ảnh giữa các module (đúng như
băn khoăn ban đầu của yêu cầu), mỗi module tự viết lại logic đọc kích thước ảnh + resize + xoá file
mồ côi, và **không có collection ảnh nào dùng chung** — muốn dùng lại 1 ảnh banner cho Post CTA
hay ngược lại là không thể làm được mà không chép file thủ công.

## 5. Ba vấn đề kỹ thuật cần giải quyết TRƯỚC khi mở rộng — không phải "nice to have"

### 5.1 Tenant isolation — ảnh có thể "biến mất" giữa các tổ chức (bug chức năng thật)

`App\Models\Media` dùng `BelongsToOrganization` → `OrganizationScope` lọc **mọi query** theo
`organization_id` của tenant hiện tại (`app/Shared/Tenancy/OrganizationScope.php`), trừ
super-admin. `MediaUploadService::upload()` (dòng 53) **luôn luôn** gán
`$media->organization_id = TenantContext::getOrganizationId()`, **bất kể model đích có
tenant-scoped hay không**.

Nhưng `Post`/`Ocop`/`Banner` là **platform-wide** — không có `organization_id`, xem
`spec/SiteSearch_Activation_Expansion_Technical_Specification.md §1` và migration
`2026_07_13_000001_drop_organization_id_from_post_articles_table.php`. Kịch bản lỗi cụ thể:

1. Nhân viên tổ chức A soạn 1 bài Post, upload cover image qua Media system → `media.organization_id = A`.
2. Nhân viên tổ chức B (không phải super-admin) xem CÙNG bài viết đó (Post platform-wide, công
   khai cho mọi tổ chức) → gọi `$article->getMediaUrl('cover')` → `getFirstMedia()` chạy qua
   `OrganizationScope`, **lọc mất** record vì `organization_id != B` → ảnh biến mất với nhân viên B,
   dù bài viết vốn công khai cho tất cả.

**Đây là bug chức năng thật (khác case Meilisearch pagination ở spec Site Search — case đó chỉ
lệch pagination, case này ảnh biến mất hẳn), phải chốt cách giải quyết trước khi viết code tích
hợp cho Post/Ocop/Banner** — xem §7 quyết định 1.

### 5.2 `reassociateOrphans()` không bao giờ được gọi — rủi ro xoá nhầm ảnh đang live trong bài đã publish

Đã xác nhận (§2.2 luồng A): `reassociateOrphans()` không có lời gọi nào trong toàn bộ
`Modules/`/`app/` ngoài chính định nghĩa của nó. Hệ quả:

- Mọi ảnh chèn qua Jodit vào content block của Post **mãi mãi giữ `model_type=JoditDraft`**, không
  bao giờ được "nhận" vào `PostArticleTranslation` thật.
- `MediaCleanupOrphansCommand` (`app/Console/Commands/MediaCleanupOrphansCommand.php:55-62`) xoá
  MỌI media `collection_name=jodit_content` + `model_type=JoditDraft` có `last_touched_at` cũ hơn
  `config('media.jodit_orphan_ttl_hours')` (mặc định 72h) — **không phân biệt** ảnh đó có đang
  được 1 bài viết ĐÃ PUBLISH tham chiếu hay không.
- `last_touched_at` chỉ được set **1 lần duy nhất lúc upload** (`MediaJoditUploadController.php:52`)
  — endpoint `PATCH /api/v1/media/jodit-touch` (dùng để gia hạn TTL khi đang soạn dài) **không hề
  được gọi từ frontend** (`grep -n "touch" resources/js/modules/jodit.js` → 0 kết quả).
- Lệnh `media:cleanup-orphans` **đã được lên lịch chạy thật** mỗi 4 giờ
  (`routes/console.php:28-31`).

→ **Kết luận: bất kỳ ảnh nào được chèn vào nội dung bài viết Post qua Jodit sẽ bị xoá khỏi ổ đĩa
trong vòng 72 giờ sau khi upload, kể cả khi bài viết đã publish và ảnh đang hiển thị sống** (link
`<img src>` sẽ gãy). Đây là 1 bug thiết kế có thật trong code.

**Verify mức độ ảnh hưởng thật tại thời điểm viết tài liệu này (2026-07-21):**
- `Media::where('collection_name','jodit_content')->count()` = **0** — chưa có bài viết thật nào
  từng chèn ảnh qua Jodit, nên bug **chưa gây hậu quả thật** cho nội dung hiện có.
- `crontab -l` trên máy này **không có** dòng `schedule:run` — nghĩa là scheduler Laravel (bao gồm
  `media:cleanup-orphans` VÀ mọi job định kỳ khác của app, xem
  `spec/SiteSearch_Activation_Expansion_Technical_Specification.md §4.3`) **chưa thực sự tự chạy**
  trên môi trường dev này dù đã đăng ký đúng.

→ Bug có thật trong code, hiện đang "ngủ" vì 2 điều kiện chưa xảy ra cùng lúc (chưa có ảnh Jodit
thật trong content + scheduler chưa chạy tự động) — **phải sửa trước khi coi Post sẵn sàng dùng
ảnh chèn qua Jodit cho content thật**, không phải chờ tới khi xảy ra sự cố mới sửa.

### 5.3 "Responsive image" theo đúng nghĩa chưa từng được dùng

`config/media-library.php:54-57` khai báo `responsive_images` (tính năng gốc của Spatie — tự sinh
nhiều breakpoint + `srcset`), nhưng API tương ứng (`$media->responsive()`,
`registerMediaConversions()` gọi `->withResponsiveImages()`) **không được gọi ở bất kỳ đâu**. Toàn
bộ "resize" hiện tại chỉ là 3 conversion cố định (`thumb`/`medium`/`preview`) làm thủ công qua
Intervention Image trong `MediaUploadService::runConversions()` — đúng yêu cầu ban đầu ("tự sinh
thumbnail") nhưng KHÔNG phải "responsive image" theo nghĩa `srcset` đa độ phân giải thật.

### 5.4 Definition of Done cho §5.1 và §5.2 — điều kiện tiên quyết trước khi tích hợp module mới

Không coi §5.1/§5.2 là "xong" chỉ vì đã sửa code — phải đạt đủ các mục kiểm chứng được dưới đây
trước khi chuyển sang tích hợp Post cover/Ocop/Banner (Critical Path bước 3+).

**§5.1 — Tenant isolation, "xong" khi:**

1. `MediaUploadService::upload()` không gán `organization_id` khi `$model` không dùng
   `BelongsToOrganization` (code review + unit test trực tiếp gọi `upload()` với 1 model
   platform-wide giả lập, assert `$media->organization_id === null`).
2. `Media::newQuery()` trả về đúng record có `organization_id IS NULL` bất kể tenant nào đang
   active trong `TenantContext` (unit test: set tenant A, query `Media::find($idOfNullOrgMedia)`
   → phải tìm thấy).
3. **Test tự động end-to-end:** tổ chức A upload cover cho 1 `PostArticle` → set tenant context
   sang tổ chức B (khác, không phải super-admin) → gọi `$article->getFirstMediaUrl('cover')` →
   phải trả về URL thật (không rỗng). Đây là test bắt buộc, không phải tuỳ chọn — chính là kịch
   bản lỗi mô tả ở §5.1.
4. **Test hồi quy (regression):** 1 model tenant-scoped thật (`Modules\Organization\Models\Organization`
   logo) vẫn bị lọc đúng theo tổ chức như trước khi sửa — tổ chức B KHÔNG thấy logo của tổ chức A.
   Bắt buộc có, để đảm bảo fix cho platform-wide không vô tình phá tenant isolation cho model vẫn
   cần cách ly.

**§5.2 — `reassociateOrphans()` + lớp phòng thủ cleanup, "xong" khi:**

1. `reassociateOrphans()` có ít nhất 1 lời gọi thật trong action lưu translation (code review xác
   nhận touch-point tồn tại, không chỉ định nghĩa hàm).
2. **Test tự động:** tạo 1 translation, giả lập 1 media `jodit_content` gắn tạm vào `JoditDraft`
   với UUID xuất hiện trong nội dung HTML sắp lưu → gọi action lưu bài → assert
   `media.model_type`/`model_id` đã đổi sang đúng translation (không còn `JoditDraft`).
3. **Test tự động:** sửa bài, bỏ 1 ảnh khỏi nội dung (UUID không còn xuất hiện trong HTML mới) →
   assert media tương ứng đã bị xoá (không còn orphan treo mãi).
4. `media:cleanup-orphans` đã thêm bước kiểm tra tham chiếu thật trước khi xoá — **2 test tự động
   bắt buộc, cả trường hợp dương lẫn âm (không chỉ 1 chiều):**
   - **(dương — lớp phòng thủ hoạt động):** 1 media `jodit_content` có UUID còn xuất hiện trong
     `post_content_blocks.text_html` của 1 bài dù `last_touched_at` đã quá TTL → chạy
     `media:cleanup-orphans` → media đó **không** bị xoá.
   - **(âm — lớp phòng thủ không vô tình chặn hết cleanup):** 1 media `jodit_content` khác, UUID
     **không** xuất hiện ở bất kỳ content nào, cũng quá TTL → chạy `media:cleanup-orphans` → media
     đó **vẫn bị xoá bình thường**. Thiếu test âm này thì không loại trừ được khả năng lớp phòng
     thủ (b) code sai kiểu khiến MỌI orphan đều bị coi là "còn tham chiếu" (vd so sánh UUID sai
     kiểu dữ liệu, query luôn trả true) — khi đó `media:cleanup-orphans` coi như vô hiệu hoàn toàn
     mà không ai nhận ra vì không có báo lỗi gì.
5. **Verify thủ công 1 lần trên môi trường có Meilisearch/queue worker thật** (tương tự cách Phase
   1.5 Site Search đã verify restart Meilisearch thật): chèn 1 ảnh Jodit vào 1 bài, publish, chờ
   qua mốc TTL (hoặc set TTL ngắn tạm thời để test nhanh), chạy `media:cleanup-orphans` thật → ảnh
   vẫn hiển thị được trên trang public.

Chỉ khi cả 2 mục DoD trên đạt đủ mới chuyển sang bước 3 của Critical Path.

## 6. Đề xuất kiến trúc — hợp nhất vào hệ đã có, KHÔNG viết module mới

**Khuyến nghị chính:** không tạo `Modules/Media` (NWIDART module) mới — hệ thống Media đã nằm ở
tầng `App\` (dùng chung toàn app, đúng vị trí cho 1 concern cross-cutting như file upload, không
thuộc riêng 1 domain module nào). Việc cần làm là:

1. **Sửa 2 bug ở §5.1/§5.2 trước** (bắt buộc, xem §7 quyết định 1/2).
2. **Thêm entity vào `MediaUploadController::ENTITY_MAP`** (`app/Http/Controllers/Api/MediaUploadController.php:54-56`)
   cho từng model muốn dùng FilePond trực tiếp — vd `'post_article' => PostArticle::class`,
   `'ocop_product' => OcopProduct::class`, `'banner' => Banner::class`.
3. **Model đích thêm `implements HasMedia` + `use HasTenantMedia;`** (tối thiểu, đúng pattern
   `Modules\Organization\Models\Organization` đã làm).
4. **Đổi form** — cover image Post (hiện input text) và ảnh Ocop/Banner (hiện input file thường +
   Action riêng) sang FilePond + header `X-Collection`/`X-Context-Type`/`X-Context-Id`, cùng
   pattern Organization logo đang dùng.
5. **Xoá cột cũ ngay khi module đó chuyển sang Media** (`cover_image_url`/`image_path`/`poster_path`)
   — không cần giữ fallback (xem §7.3, quyết định 3/4 đã bỏ qua vì lý do giai đoạn phát triển).
6. **Bật `reassociateOrphans()` thật** cho luồng lưu bài Post (touch-point mới, tương tự
   `UpdateArticleAction.php:46` gọi `translations()->searchable()` — cùng nguyên tắc "đổi dữ liệu
   ở 1 action thì phải tự đồng bộ side-effect liên quan").

## 7. Quyết định BẮT BUỘC phải chốt trước khi viết code

| # | Quyết định | Người chốt chính | Trạng thái |
|---|---|---|---|
| 1 | Chiến lược tenant isolation cho Media khi gắn vào model platform-wide (§5.1) | **Tech Lead** | ✅ **Đã chốt** — xem §7.1 |
| 2 | Có bật `reassociateOrphans()` cho Post ngay, hay tạm thời chặn tính năng chèn ảnh Jodit trong content block cho tới khi sửa xong §5.2 | **Tech Lead** | ✅ **Đã chốt** — xem §7.2 |
| 3 | Thứ tự module migrate sang Media system (Post cover trước hay Ocop/Banner trước) | **Product Owner** | ⏭️ **Bỏ qua** — xem §7.3 |
| 4 | Có xoá hẳn cột `image_path`/`cover_image_url`/`poster_path` sau khi migrate, hay giữ vĩnh viễn làm fallback | **Tech Lead** | ⏭️ **Bỏ qua** — xem §7.3 |
| 5 | Vòng đời media của Post (theo vòng đời bài viết hay độc lập/dùng lại được) | **Tech Lead** | ✅ **Đã chốt** — xem §7.4 |
| 6 | Collection/conversion cho Ocop và Banner (dùng chung `cover` hay cần collection riêng) | **Tech Lead** | ✅ **Đã chốt** — xem §7.5 |

### 7.1 Quyết định 1 (§5.1) — ĐÃ CHỐT: Hướng A, tinh chỉnh — bypass động ở tầng `Media`, không sửa `OrganizationScope` dùng chung

**Chọn Hướng A (bypass ở `Media::newQuery()`), KHÔNG chọn Hướng B (sửa `OrganizationScope`
dùng chung).** Lý do quyết định:

- `OrganizationScope` là scope dùng chung cho **mọi** model tenant-scoped trong toàn app, không
  riêng `Media`. Sửa nó để chấp nhận `organization_id IS NULL` cho "mọi tenant" mở ra rủi ro thật:
  nếu BẤT KỲ model tenant-scoped nào khác (không phải Media) có 1 row `organization_id` null do
  bug/migration lỗi, toàn bộ tenant sẽ tự nhiên thấy được row đó — biến 1 quyết định phạm vi hẹp
  (Media cho content platform-wide) thành thay đổi hành vi bảo mật của **toàn bộ hệ tenant
  isolation**. Bán kính ảnh hưởng quá lớn so với vấn đề cần giải quyết.
- `Media::newQuery()` (`app/Models/Media.php:31-40`) **đã có tiền lệ bypass có điều kiện** (khi
  `!TenantContext::isSet()`) — mở rộng thêm 1 điều kiện bypass nữa ở đúng chỗ này giữ nguyên
  nguyên tắc "thay đổi hành vi tenant isolation của Media chỉ nằm trong chính model Media", không
  lan ra scope dùng chung.

**Tinh chỉnh so với đề xuất gốc ở bản trước (bỏ nhược điểm "phải duy trì danh sách platform-wide
riêng"):** thay vì hardcode danh sách model platform-wide ở tầng Media (trùng lặp thông tin), xác
định "platform-wide hay không" bằng cách kiểm tra ĐỘNG chính model đích (`$media->model_type`) có
dùng trait `BelongsToOrganization`/extends `TenantAwareModel` hay không — dùng lại đúng tín hiệu
đã tồn tại sẵn (migration đã xác lập model nào có `organization_id`), không tạo thêm 1 nguồn sự
thật mới cần đồng bộ tay.

**Cụ thể 2 điểm cần sửa (thực hiện cùng lúc, không phải chọn 1):**

1. **Ghi (`MediaUploadService::upload()`, dòng 53):** chỉ gán
   `$media->organization_id = TenantContext::getOrganizationId()` nếu `$model` dùng
   `BelongsToOrganization` (kiểm bằng `in_array(BelongsToOrganization::class, class_uses_recursive($model))`);
   ngược lại (model platform-wide) để `organization_id = null` — đúng bản chất dữ liệu: ảnh của nội
   dung platform-wide thì không thuộc tổ chức nào.
2. **Đọc (`Media::newQuery()`):** mở rộng điều kiện bypass hiện có — bypass `OrganizationScope`
   không chỉ khi `!TenantContext::isSet()`, mà còn khi `organization_id` của chính record đang
   null (kiểm qua 1 query con nhẹ, hoặc đơn giản hơn: bypass toàn bộ scope này rồi tự thêm điều
   kiện `where(organization_id = current OR organization_id IS NULL)` ngay trong `newQuery()` của
   `Media` — thay vì đụng `OrganizationScope` gốc).

**Code sketch cụ thể** (hướng triển khai, chưa chạy/test — người implement điều chỉnh chi tiết khi
viết thật, nhưng KHÔNG đổi 2 nguyên tắc ở trên: không đụng `OrganizationScope`, kiểm tra động thay
vì hardcode danh sách model):

```php
// app/Models/Media.php
class Media extends SpatieMedia
{
    use BelongsToOrganization;

    public function newQuery(): Builder
    {
        $query = parent::newQuery();

        if (! TenantContext::isSet()) {
            $query->withoutGlobalScope(OrganizationScope::class);
            return $query;
        }

        // Cho phép đọc thêm media platform-wide (organization_id NULL) CÙNG LÚC với media
        // của chính tenant hiện tại. Bỏ scope gốc rồi tự viết lại điều kiện tương đương +
        // nới thêm "hoặc NULL" — không sửa OrganizationScope dùng chung cho model khác.
        return $query->withoutGlobalScope(OrganizationScope::class)
            ->where(function (Builder $q) {
                $q->where('organization_id', TenantContext::getOrganizationId())
                  ->orWhereNull('organization_id');
            });
    }

    /**
     * True nếu model đích (FQCN hoặc instance) dùng tenant scoping — quyết định
     * MediaUploadService::upload() có set organization_id hay không.
     */
    public static function targetIsTenantScoped(string|Model $modelOrClass): bool
    {
        $class = is_string($modelOrClass) ? $modelOrClass : get_class($modelOrClass);

        return in_array(BelongsToOrganization::class, class_uses_recursive($class), true);
    }
}
```

```php
// app/Services/Media/MediaUploadService.php — trong upload(), thay dòng 53
$media->organization_id = Media::targetIsTenantScoped($model)
    ? TenantContext::getOrganizationId()
    : null;
```

**Lưu ý khi implement:** `newQuery()` hiện tại (`app/Models/Media.php:31-40`) chỉ có nhánh
`!TenantContext::isSet()`; sketch trên THÊM nhánh `else` mới, không xoá nhánh cũ. Kiểm tra kỹ
`class_uses_recursive()` hoạt động đúng với `$model` là 1 STRING class name (không phải instance)
trong `targetIsTenantScoped()` — hàm này của Laravel nhận cả string class name lẫn object, không
cần `get_class()` thêm nếu truyền thẳng string, nhưng sketch trên giữ tường minh cho dễ đọc.

**Đã xác nhận — `MediaPathGenerator` KHÔNG cần sửa cho trường hợp `organization_id = null`:**
`app/Services/Media/MediaPathGenerator.php:35` đã có sẵn `$orgId = $media->organization_id ?? 0;`
— path cho media platform-wide sẽ là `media/0/{module}/{entity_type}/{entity_id}/{uuid}/` (thư
mục `0` dùng chung cho mọi nội dung platform-wide, không đụng số ID tổ chức thật nào vì
`organizations.id` bắt đầu từ 1). Không có gì cần sửa ở file này khi triển khai quyết định 1 —
ghi lại ở đây để không ai phải đi verify lại lần nữa.

**Khuyến nghị thêm index cho cột `organization_id`:** bảng `media` hiện **chưa có index** trên cột
`organization_id` (xác nhận qua `database/migrations/extensions/..._000153_add_organization_id_and_uploaded_at_and_last_touched_at_to_media_table.php:15`
— chỉ `$table->unsignedBigInteger('organization_id')->nullable();`, không `->index()`). Điều kiện
`WHERE organization_id = ? OR organization_id IS NULL` ở sketch trên sẽ cần full scan nếu bảng
`media` lớn. Vì đây chỉ là ĐỀ XUẤT thêm cột/index (không phải bảng mới), thêm entry vào
`render_extension_file.json` (Trường hợp 2, `docs/migration-guide.md`) cho bảng `media`, cột
`organization_id`, thêm `->index()` — nên làm cùng lúc với PR sửa §5.1, không để riêng thành việc
"làm sau" vì chi phí gần như bằng 0 lúc này (bảng còn nhỏ, chưa có dữ liệu thật nhiều).

### 7.2 Quyết định 2 (§5.2) — ĐÃ CHỐT: Làm CẢ (a) lẫn (b), không phải chọn 1

**Không coi đây là lựa chọn either/or — mức độ nghiêm trọng của bug (nội dung publish có thể mất
ảnh) đáng để làm cả 2 lớp phòng thủ:**

1. **(a) là fix chính, bắt buộc:** wire `reassociateOrphans()` vào đúng chỗ đã thiết kế sẵn
   (docblock `MediaUploadService::reassociateOrphans()` dòng 123-130 đã mô tả rõ ý định, chỉ chưa
   ai gọi) — thêm bước parse `data-media-uuid` từ HTML vừa lưu trong
   `UpdateTranslationAction::handle()`/action tạo bản dịch tương ứng, gọi `reassociateOrphans($translation, $uuids)`
   ngay sau `syncContentBlocks->handle()`. Đây là fix đúng gốc rễ: ảnh phải được "nhận" vào entity
   thật thay vì mãi mãi là orphan.
2. **(b) là lớp phòng thủ thêm, chi phí thấp, không thay thế (a):** trong
   `MediaCleanupOrphansCommand`, trước khi xoá 1 media `jodit_content`, kiểm tra thêm UUID đó có
   xuất hiện trong bất kỳ `post_content_blocks.text_html` nào hiện tại không (1 query `LIKE` rẻ
   trên tổng số orphan candidate, không phải trên toàn bộ bảng) — nếu có, bỏ qua không xoá dù đã
   quá TTL. Lý do làm thêm lớp này dù đã có (a): phòng trường hợp (a) bị bỏ sót ở 1 luồng lưu nào
   đó sau này (vd khôi phục version cũ tái dùng UUID ảnh, hoặc 1 Action mới thêm sau không gọi
   đúng touch-point) — an toàn hơn là tin tưởng tuyệt đối vào 1 điểm chạm duy nhất, cùng tinh thần
   "lớp phòng thủ thứ 2" mà `PostArticleTranslation::shouldBeSearchable()` đã áp dụng cho Meilisearch
   (xem comment gốc trong file đó, dòng 183-190).

**Hệ quả cho §8 (kế hoạch tích hợp):** không cần "tạm chặn tính năng chèn ảnh Jodit" nữa — làm (a)
trước khi mở lại/tiếp tục cho phép nội dung thật dùng ảnh chèn Jodit là đủ, (b) triển khai song
song không chặn tiến độ (a).

**Ghi nhận rõ: (b) là giải pháp TẠM THỜI cho giai đoạn hiện tại, không phải thiết kế dài hạn.**
`LIKE '%uuid%'` trên `post_content_blocks.text_html` chấp nhận được khi số lượng orphan candidate
+ tổng số content block còn nhỏ (đúng quy mô hiện tại — xem §5.2, `jodit_content` đang có 0 record
thật). Sẽ chậm dần khi nội dung Post tăng lên (scan text lớn, không có index nào hỗ trợ tìm kiếm
theo UUID nhúng trong HTML). **Hướng cải thiện dài hạn (chưa cần làm ngay, ghi lại để không quên):**
thêm 1 bảng quan hệ riêng (vd `content_media_references`: `media_uuid`, `model_type`, `model_id`)
được ghi lại MỖI LẦN lưu content (cùng lúc với bước (a) parse `data-media-uuid`) — khi đó
`media:cleanup-orphans` chỉ cần `whereIn`/join trên bảng quan hệ này thay vì `LIKE` toàn văn HTML,
tương tự cách `post_article_categories`/`post_article_tag` đã tách quan hệ many-to-many riêng thay
vì nhúng ID trong text. Không làm ngay vì thêm 1 bảng mới cho 1 lớp "phòng thủ thêm" là quá mức cần
thiết ở quy mô dữ liệu hiện tại — chỉ nên làm khi đo được `media:cleanup-orphans` thật sự chậm.

### 7.3 Quyết định 3 và 4 — BỎ QUA, không cần chốt trong giai đoạn phát triển hiện tại

Theo xác nhận trực tiếp từ người phụ trách (2026-07-21): dự án đang trong giai đoạn phát triển,
quy trình chuẩn khi đổi schema là `php artisan migration:generate --fresh` (xem
`docs/migration-guide.md`) — xoá DB, sinh lại migration từ `render_migration_file.json`/
`render_extension_file.json`, chạy lại từ đầu. Không có dữ liệu production thật cần bảo toàn qua
nhiều bước trung gian, nên 2 quyết định này không còn ý nghĩa thực tế ở giai đoạn này:

- **Quyết định 3 (thứ tự migrate module)** — không quan trọng: `migration:generate --fresh` dựng
  lại toàn bộ schema 1 lần, không có khái niệm "module A xong trước module B" gây rủi ro gì —
  module nào code xong Action/form trước thì tự nhiên "xong trước", không cần lên kế hoạch thứ tự.
- **Quyết định 4 (giữ cột cũ hay xoá hẳn)** — chọn **xoá hẳn ngay** khi 1 module chuyển sang Media
  (đã cập nhật §6 điểm 5) — không cần giữ fallback "phòng dữ liệu cũ chưa migrate", vì mỗi lần
  `migration:generate --fresh` vốn đã seed lại dữ liệu demo/seed xác định (xem tiền lệ ở
  `spec/SiteSearch_Activation_Expansion_Technical_Specification.md §2`, mục sự cố vận hành đầu
  tiên) — không có dữ liệu thật đang chạy cần đường lui.

**Lưu ý duy nhất cần giữ:** nếu tới lúc lên production thật (có dữ liệu người dùng thật không thể
`--fresh` được nữa), quay lại chốt 2 quyết định này theo đúng quy trình migration cho **PROD**
ở `docs/migration-guide.md` (`migration:generate` + `migrate`, không `--fresh`) — khi đó mới thật
sự cần cột fallback + thứ tự migrate cẩn thận. Không áp dụng ngay bây giờ.

### 7.4 Quyết định 5 — ĐÃ CHỐT: Media của Post đi theo vòng đời entity sở hữu nó, không phải pool dùng lại độc lập

**Chốt: media (cover + ảnh content block) đi theo vòng đời của chính entity nó gắn vào
(`PostArticle`/`PostArticleTranslation`), KHÔNG phải 1 "pool" ảnh dùng chung độc lập có thể tái sử
dụng tự do giữa nhiều bài viết.** Lý do:

- Kiến trúc hiện tại (`Media` morph `model_type`+`model_id` — mỗi record thuộc đúng 1 model
  instance) vốn đã không hỗ trợ "1 ảnh, nhiều entity cùng dùng" — muốn vậy cần pivot many-to-many
  hoặc Media Picker (Open Question còn lại, §11), ngoài phạm vi quyết định này.
  Xem thêm §7.6 — cùng lý do đó buộc phải điều chỉnh lại AC#5.
- **Phát hiện quan trọng khi kiểm tra code Spatie thật** (`vendor/spatie/laravel-medialibrary/src/InteractsWithMedia.php:49-62`,
  hàm `bootInteractsWithMedia()`): Spatie **đã có sẵn** đúng hành vi cần — hook `static::deleting()`
  chỉ gọi `$model->deleteAllMedia()` khi `in_array(SoftDeletes::class, class_uses_recursive($model))`
  **VÀ** `$model->forceDeleting === true`. Nghĩa là:
  - **Soft-delete** (`$article->delete()`, cách `DeleteArticleAction` hiện đang dùng) → **Spatie tự
    động BỎ QUA**, media giữ nguyên, khôi phục bài viết thì ảnh vẫn còn — **không cần viết thêm
    code gì cho trường hợp này**, chỉ cần model đích dùng `HasTenantMedia` là tự động đúng.
  - Chỉ **hard-delete** (`forceDelete()`) mới xoá media thật (`deleteAllMedia()`).
- Đây chính xác là hành vi mong muốn (khớp AC#4 đã làm rõ ở §9) — quyết định 5 thực chất là
  **xác nhận dùng hành vi mặc định của Spatie, không override**, không phải viết logic mới.

**Điều KHÔNG cần làm thêm:** không cần job riêng "dọn media của bài viết đã soft-delete lâu ngày"
— vòng đời media đã gắn chặt với vòng đời chính bài viết (soft-delete/restore/force-delete của
bài viết đã có sẵn cơ chế riêng, không thuộc phạm vi tài liệu Media này); khi bài viết bị
force-delete thật (bất kể do job dọn rác nào của Post gọi), Spatie tự dọn media theo.

### 7.5 Quyết định 6 — ĐÃ CHỐT: `cover` dùng nguyên trạng cho Ocop, thêm collection mới `banner` cho Banner

Đã verify trực tiếp cách hiển thị ảnh thật của cả 2 module trước khi chốt (không đoán):

- **Ocop** — cả trang danh sách (`Modules/Ocop/resources/views/public/index.blade.php:39-41`) lẫn
  trang chi tiết (`.../show.blade.php:18-20`) đều hiển thị ảnh trong khung `aspect-square` +
  `object-cover` — ảnh LUÔN hiển thị vuông, crop qua CSS. → **Collection `cover` hiện tại dùng
  nguyên trạng, không cần đổi** — `thumb` (150×150 crop) khớp đúng nhu cầu vuông, `medium`/`preview`
  (scale, không crop) vẫn hiển thị đúng nhờ CSS `object-cover` tự crop phần thừa.
- **Banner** — `resources/views/components/frontend/banner-slot.blade.php:18-20` +
  CSS thật `resources/css/frontend.css:919`: `.banner-slot__img { width:100%; height:auto; }` —
  **không ép aspect ratio cố định nào**, ảnh giữ nguyên tỷ lệ gốc tuỳ placement. Nếu dùng chung
  collection `cover` (có `thumb` = crop cứng 150×150 vuông), banner ngang sẽ bị cắt sai bố cục
  ngay khi hiển thị ở bất kỳ nơi nào dùng biến thể `thumb`.

**Chốt:** thêm 1 collection MỚI tên `banner` trong `config/media.php` — cùng `max_size_kb`/
`allowed_mime` với `cover`, nhưng **`conversions => ['medium', 'preview']`, KHÔNG có `thumb`**
(tránh crop vuông làm méo banner). Không tái dùng collection `cover` cho Banner.

```php
// config/media.php — thêm vào mảng 'collections'
'banner' => [
    'max_size_kb'  => 10240,
    'allowed_mime' => ['image/jpeg', 'image/png', 'image/webp'],
    'is_public'    => true,
    'conversions'  => ['medium', 'preview'], // KHÔNG có 'thumb' — banner giữ nguyên tỷ lệ, không crop vuông
],
```

## 8. Kế hoạch tích hợp theo từng module

| Module | Việc cần làm | Ưu tiên |
|---|---|---|
| Post — cover image | Thêm `implements HasMedia` cho `PostArticle`, đổi input text `cover_image_url` sang FilePond (collection `cover`), **xoá cột `cover_image_url` ngay** (không giữ fallback — §7.3) | Cao — đây là gap rõ nhất trong yêu cầu gốc |
| Post — ảnh content block (Jodit) | **Bắt buộc làm xong §7.2 (a)** trước khi coi luồng này an toàn cho nội dung thật | Cao nhất — đang có bug thiết kế thật |
| Ocop — ảnh sản phẩm | Thêm `implements HasMedia` cho `OcopProduct`, thay `StoreOcopProductImageAction` bằng FilePond (collection `cover` — dùng nguyên trạng, đã chốt ở §7.5), **xoá `image_path`/`image_width`/`image_height`/`image_size_bytes` ngay** | Trung bình |
| Banner — ảnh banner | Thêm `implements HasMedia` cho `Banner`, thay `StoreBannerImageAction` bằng FilePond (collection **`banner`** mới — đã chốt ở §7.5, KHÔNG dùng `cover`), xoá 4 cột phẳng tương ứng ngay | Trung bình |
| Event — poster | Cân nhắc thêm sau — pattern hiện tại đã tách biệt, không có module nào khác phụ thuộc vào nó | Thấp |
| Newsletter | Chưa có use case ảnh thật — không cần làm gì cho tới khi có yêu cầu cụ thể (banner email, ảnh trong nội dung) | Hoãn |

**Thứ tự thực hiện:** không cần lên kế hoạch thứ tự giữa các module (§7.3, quyết định 3 bỏ qua) —
làm module nào trước cũng được, miễn **Post — ảnh content block (Jodit)** hoàn thành §7.2(a) trước
khi coi là "xong" cho riêng phần đó.

**Dữ liệu cũ:** vì đang dùng `migration:generate --fresh` (seed lại demo/seed data mỗi lần), không
cần viết Artisan command migrate dữ liệu cũ sang `Media` — xoá cột cũ, code mới chạy trên dữ liệu
seed lại từ đầu là đủ cho giai đoạn này (xem §7.3 — chỉ cần command migrate dữ liệu thật khi lên
production, ngoài phạm vi tài liệu này).

## 9. Acceptance Criteria

1. Upload cover image cho 1 bài Post qua FilePond → `media` row tạo đúng, `thumb`/`medium`/`preview`
   sinh ra, `PostArticle::getFirstMediaUrl('cover', 'medium')` trả đúng URL.
2. Nhân viên tổ chức B (không phải super-admin) xem 1 bài Post có cover image do tổ chức A upload
   → vẫn thấy ảnh (xác nhận đã giải quyết §5.1, không bị `OrganizationScope` lọc mất).
3. Chèn ảnh vào content block Post qua Jodit, lưu bài, publish → sau > 72h + chạy
   `media:cleanup-orphans` thủ công → ảnh **vẫn còn**, không bị xoá (xác nhận đã giải quyết §5.2).
4. **Soft-delete 1 bài Post** (`$article->delete()`, cách `DeleteArticleAction` hiện dùng) → ảnh
   cover + ảnh content block liên quan **KHÔNG bị xoá** — test xác nhận
   `Media::where('model_type', PostArticleTranslation::class)->where('model_id', $translation->id)->count()`
   **không đổi** trước/sau soft-delete. Đây là hành vi MẶC ĐỊNH của Spatie
   (`vendor/spatie/laravel-medialibrary/src/InteractsWithMedia.php:49-62` — chỉ gọi
   `deleteAllMedia()` khi `$model->forceDeleting === true`), **không cần viết thêm code** — chỉ
   cần đúng model dùng `HasTenantMedia`. **Hard-delete** (`forceDelete()`) → test riêng xác nhận
   media bị xoá theo (count = 0). Quyết định vòng đời đã chốt ở §7.4.
5. **Ocop và Banner dùng chung 1 pipeline** upload/validate/resize/dọn rác (`MediaUploadService`)
   thay vì 3 Action riêng biệt như hiện tại (`StoreOcopProductImageAction`/`StoreBannerImageAction`/
   `StoreEventPosterAction`) — đây là mức "tái sử dụng" đạt được thật ở giai đoạn này (dùng lại
   CODE/pipeline, không phải dùng lại 1 FILE vật lý). **Không** kỳ vọng 1 ảnh vật lý được 2 entity
   khác nhau (vd 1 bài Post và 1 banner) cùng tham chiếu — kiến trúc `model_type`+`model_id` hiện
   tại (1 media → đúng 1 entity) không hỗ trợ việc này tự nhiên (xem §7.4); tái sử dụng đúng nghĩa
   "1 file, nhiều entity" là tính năng Media Picker riêng, để ở Open Question §11 (ngoài phạm vi
   quyết định của tài liệu này).

## 10. Việc KHÔNG nên làm

1. **Không viết `Modules/Media` (NWIDART module) mới** — hệ thống đã nằm đúng chỗ ở tầng `App\`,
   viết thêm module tạo hệ thứ 5 song song, đúng vấn đề tài liệu này muốn giải quyết.
2. **Không mở rộng `ENTITY_MAP` cho Post/Ocop/Banner trước khi giải quyết §5.1** — sẽ tái tạo bug
   "ảnh biến mất giữa tổ chức" ngay khi có tổ chức thứ 2 dùng chung nội dung platform-wide.
3. **Không bật tính năng chèn ảnh Jodit cho nội dung thật của Post trước khi giải quyết §5.2** —
   rủi ro ảnh trong bài đã publish bị xoá tự động sau 72h.
4. ~~Không xoá `cover_image_url`/`image_path`/`poster_path` ngay~~ — **đã đổi quyết định (§7.3,
   2026-07-21):** giai đoạn phát triển hiện tại dùng `migration:generate --fresh` thường xuyên,
   không cần giữ fallback — xoá cột cũ ngay khi module chuyển sang Media là đủ. Chỉ áp dụng lại
   nguyên tắc "giữ fallback" này khi lên production có dữ liệu thật.
5. **Không tự quyết định responsive image (srcset thật) là việc cần làm ngay** — 3 conversion cố
   định hiện tại (`thumb`/`medium`/`preview`) đã đáp ứng đúng yêu cầu gốc ("tự sinh thumbnail"),
   srcset đa độ phân giải là cải tiến UX/performance riêng, để Product Owner quyết định độ ưu tiên.

## 11. Open Questions

> 2 câu hỏi trước đây ở mục này (vòng đời media của Post; collection cho Ocop/Banner) **đã được
> nâng thành quyết định và chốt** — xem §7.4 (quyết định 5) và §7.5 (quyết định 6). Chỉ còn 1 câu
> hỏi thật sự còn mở:

1. Có cần UI "Thư viện Media" riêng (duyệt/tìm/tái sử dụng ảnh đã upload trước, không phải upload
   mới mỗi lần, và cho phép thật sự dùng lại 1 file vật lý giữa nhiều entity — vd cùng 1 ảnh cho cả
   Post lẫn Banner) hay chỉ cần upload-per-entity như hiện tại? Đây là phần "trung tâm" thật sự
   theo nghĩa thư viện — hiện hệ thống chỉ là "dịch vụ upload dùng chung", chưa có màn hình duyệt
   lại, và kiến trúc hiện tại (§7.4) chưa hỗ trợ 1 media dùng chung nhiều entity.

   **Khuyến nghị mặc định cho giai đoạn hiện tại (không phải quyết định, chỉ để tránh hiểu nhầm đây
   là việc bắt buộc làm sớm): upload-per-entity là ĐỦ.** Chưa có bằng chứng nhu cầu thật (chưa ai
   phàn nàn phải upload lại ảnh đã có, số lượng module dùng Media còn ít). Media Picker là công
   sức đáng kể (UI duyệt/tìm/chọn lại, cộng thêm phải giải quyết bài toán kiến trúc "1 file, nhiều
   entity" ở §7.4) — chỉ nên làm khi Product Owner thấy có tín hiệu thật cần (vd nhiều người dùng
   lặp lại yêu cầu, hoặc số lần upload trùng 1 ảnh tăng cao đo được qua `media:stats`). Không đưa
   vào phạm vi Critical Path (đầu tài liệu) vì lý do này.

## 12. Decision Log

| Ngày | Quyết định (# ở §7) | Người chốt | Nội dung chốt | Lý do |
|---|---|---|---|---|
| 2026-07-21 | 1. Chiến lược tenant isolation | Tech Lead (qua phiên làm việc này) | Hướng A tinh chỉnh — bypass động ở `Media::newQuery()` + không set `organization_id` khi model đích platform-wide; KHÔNG sửa `OrganizationScope` dùng chung | Bán kính ảnh hưởng nhỏ nhất — thay đổi hành vi chỉ nằm trong model `Media`, không lan ra scope dùng chung cho mọi model tenant-scoped khác. Xem §7.1 |
| 2026-07-21 | 2. `reassociateOrphans()` vs chặn tính năng | Tech Lead (qua phiên làm việc này) | Làm cả (a) wire `reassociateOrphans()` đúng chỗ đã thiết kế, VÀ (b) thêm kiểm tra tham chiếu thật trong `media:cleanup-orphans` làm lớp phòng thủ thứ 2 | Mức độ nghiêm trọng (nội dung publish có thể mất ảnh) đáng để có 2 lớp phòng thủ thay vì tin tưởng 1 điểm chạm duy nhất — cùng nguyên tắc "lớp phòng thủ thứ 2" đã áp dụng cho `shouldBeSearchable()` ở Post Search. Xem §7.2 |
| 2026-07-21 | 3. Thứ tự migrate module | Người phụ trách dự án | Bỏ qua — không cần lên kế hoạch thứ tự | Đang trong giai đoạn phát triển, dùng `migration:generate --fresh` thường xuyên, không có ràng buộc dữ liệu thật cần bảo toàn qua nhiều bước. Xem §7.3 |
| 2026-07-21 | 4. Giữ cột cũ hay xoá hẳn | Người phụ trách dự án | Xoá hẳn ngay khi module chuyển sang Media, không giữ fallback | Cùng lý do quyết định 3 — sẽ chốt lại theo hướng bảo toàn dữ liệu khi lên production thật (ngoài phạm vi hiện tại). Xem §7.3 |
| 2026-07-21 | 5. Vòng đời media của Post | Tech Lead (qua phiên làm việc này) | Media đi theo vòng đời entity sở hữu (không phải pool dùng lại độc lập) — dựa vào hành vi mặc định của Spatie (soft-delete an toàn, chỉ hard-delete mới xoá media) | Kiến trúc `model_type`+`model_id` hiện tại không hỗ trợ dùng chung 1 media cho nhiều entity; Spatie đã có sẵn đúng hành vi cần, không cần code thêm. Xem §7.4 |
| 2026-07-21 | 6. Collection cho Ocop/Banner | Tech Lead (qua phiên làm việc này) | Ocop dùng nguyên collection `cover`; Banner dùng collection MỚI `banner` (chỉ `medium`/`preview`, không có `thumb`) | Verify code thật: Ocop hiển thị vuông (`aspect-square`+`object-cover`) mọi nơi, khớp `cover`; Banner không ép aspect ratio (`width:100%;height:auto`), crop vuông của `cover.thumb` sẽ làm méo banner. Xem §7.5 |

## 13. Log triển khai thật (2026-07-21) — đã implement xong toàn bộ Critical Path

Verify bằng test tự động (47/47 PASS, chạy lặp lại 3 lần) + verify sống qua HTTP thật trên
`vigiadinh` (DB dev thật) cho cả upload/reassociate/hiển thị.

### 13.1 §5.1 — Tenant isolation

- `app/Models/Media.php` — `newQuery()` sửa đúng theo sketch §7.1 (giữ nhánh `!TenantContext::isSet()`
  cũ, thêm nhánh mới cho phép đọc `organization_id = current OR NULL`); thêm
  `targetIsTenantScoped()`. **Tinh chỉnh so với sketch:** thêm nhánh super-admin bypass toàn bộ
  (giữ đúng hành vi cũ của `OrganizationScope` — super-admin xem mọi tổ chức — vì `newQuery()` giờ
  bypass hẳn `OrganizationScope`, không còn để scope gốc tự xử lý nhánh này nữa).
- `app/Services/Media/MediaUploadService.php:upload()` — chỉ set `organization_id` khi
  `targetIsTenantScoped($model)`.
- Index `idx_media_organization` thêm qua `render_extension_file.json` (Trường hợp 2 chuẩn) +
  `extension:generate` + `migrate`.
- Test: `tests/Feature/MediaTenantIsolationTest.php` (4 test) — bao gồm regression cho
  `Modules\Organization\Models\Organization` (logo).
- **Phát hiện phụ (ngoài phạm vi, không sửa):** `Modules\Organization\Models\Organization` +
  `MediaUploadService::upload()` hiện **crash thật** (`BadMethodCallException:
  registerAllMediaConversions()`) do morph map (`Modules/Approval/config/approval.php:19-25`, có
  chủ đích cho Approval) ánh xạ `'organization'` về class GỐC (không có `InteractsWithMedia`), lệch
  với subclass thật dùng để upload. Test regression né đường `addMedia()` bằng cách tạo thẳng
  `Media` record — verify đúng phần mình đổi (`Media::newQuery()` scoping), không đụng bug morph
  map (thuộc phạm vi Organization/Approval, không phải Media).

### 13.2 §5.2 — `reassociateOrphans()` + lớp phòng thủ

- `Modules/Post/app/Features/ArticleAuthoring/Actions/UpdateTranslationAction.php` — wire
  `reassociateOrphans()` sau `syncContentBlocks->handle()`, parse `data-media-uuid` bằng regex trên
  `text_html` các content block Text.
- `Modules/Post/app/Models/PostArticleTranslation.php` — thêm `implements HasMedia` +
  `use HasTenantMedia;`.
- `app/Console/Commands/MediaCleanupOrphansCommand.php` — thêm `isUuidStillReferencedInContent()`
  (query `DB::table('post_content_blocks')->where('text_html','like',...)`) trước khi xoá orphan.
- **Bug thật phát hiện #1 (đã sửa):** `MediaUploadService::reassociateOrphans()` bản gốc `return`
  sớm khi `$uuids` rỗng — xoá HẾT ảnh khỏi nội dung thì bước xoá stale media bên dưới không bao
  giờ chạy, orphan vĩnh viễn. Tách bước reassociate (cần `$uuids`) khỏi bước xoá stale (luôn chạy).
- Test: `Modules/Post/tests/Feature/MediaReassociationTest.php` (2),
  `tests/Feature/MediaCleanupOrphansDefenseTest.php` (2, gồm cả test âm bắt buộc theo §5.4).
- Verify sống trên `vigiadinh`: upload thật qua `MediaJoditUploadController`, lưu bài qua
  `UpdateTranslationAction` thật, set `last_touched_at` quá hạn, chạy `media:cleanup-orphans` thật
  → ảnh vẫn còn, vẫn truy cập được qua HTTP (200).

### 13.3 §8 — Tích hợp Post/Ocop/Banner

Theo đúng kế hoạch §8, cho cả 3 module: model thêm `implements HasMedia` + `HasTenantMedia`, entity
map thêm ở `MediaUploadController::ENTITY_MAP`, form đổi sang FilePond (`resources/js/modules/
filepond.js` — có sẵn, chưa ai dùng cho collection nào ngoài sản phẩm Product cũ không tích hợp
Media), cột ảnh cũ xoá bằng migration guard `hasColumn`. Chi tiết khác biệt đáng chú ý:

- **Post**: thêm accessor `PostArticle::getCoverImageUrlAttribute()` — mọi view cũ đọc
  `$article->cover_image_url` (article-card/hero/hero-story) **không cần sửa gì**, tiếp tục hoạt
  động qua accessor thay cột DB đã xoá.
- **Ocop**: xoá hẳn `StoreOcopProductImageAction.php` (không còn Action nào dùng). 4 blade view sửa
  trực tiếp sang `getFirstMediaUrl('cover', ...)` (không có accessor tương thích ngược vì tên field
  gốc `image_path` không map tự nhiên sang URL). `DeleteOcopProductAction` bỏ luôn code
  `Storage::delete()` thủ công — dựa vào hành vi mặc định Spatie (§7.4).
- **Banner**: xoá hẳn `StoreBannerImageAction.php`. Thêm collection `banner` mới đúng như §7.5 đã
  chốt, cộng thêm cập nhật `MediaUploadController::ALLOWED_COLLECTIONS`/`SINGLE_FILE_COLLECTIONS`
  và `resources/js/modules/filepond.js` (`COLLECTION_MAX_SIZE`/`COLLECTION_MIME`/
  `SINGLE_FILE_COLLECTIONS`) — các hằng số này chưa từng có entry cho collection ngoài
  avatar/logo/thumbnail/cover, phải thêm thủ công cho `banner`.
- **Bug thật phát hiện #2 — nghiêm trọng, phát hiện qua verify sống (không phải test tự động):**
  `MediaPathGenerator` tính path dựa trên `model_type`/`model_id` **hiện tại** của record
  (`media/{org}/{module}/{entity_type}/{entity_id}/{uuid}`), không phải path cố định đã lưu lúc
  upload. Sau khi `reassociateOrphans()`/`reassociateFilePondDrafts()` đổi `model_type`/`model_id`
  (từ `JoditDraft`/`FilePondDraft` sang entity thật), path TÍNH LẠI đổi theo, nhưng file vật lý vẫn
  nằm ở path CŨ (`moves_media_on_update=false` — Spatie không tự di chuyển). Hệ quả: ảnh cover/banner
  tạo qua form "tạo mới" (đường FilePondDraft) bị **vỡ URL (404) ngay sau khi lưu**, dù cột DB hoàn
  toàn đúng — bug không lộ ra qua test dùng `Media::forceCreate()` (đặt sẵn `model_type` đúng ngay
  từ đầu, không đi qua bước reassociate thật), chỉ lộ ra khi verify bằng upload thật qua HTTP.
  **Đã sửa:** thêm `MediaUploadService::moveMediaFiles()` — di chuyển file gốc + mọi file conversion
  từ path cũ sang path mới ngay sau khi đổi `model_type`/`model_id`, gọi trong cả
  `reassociateOrphans()` lẫn `reassociateFilePondDrafts()`. Test:
  `tests/Feature/MediaPathAfterReassociationTest.php` — đã verify test này THẬT SỰ bắt được bug
  (revert tạm fix, xác nhận test fail đúng lý do, rồi khôi phục lại).

### 13.4 Kết quả cuối cùng

- 47/47 test PASS, chạy lặp lại 3 lần liên tiếp không flaky:
  `tests/Feature/{MediaTenantIsolationTest,MediaCleanupOrphansDefenseTest,MediaPathAfterReassociationTest}.php`,
  `Modules/Post/tests/Feature/{MediaReassociationTest,PostCoverImageMediaTest}.php`,
  `Modules/Ocop/tests/Feature/OcopCoverImageMediaTest.php`,
  `Modules/Banner/tests/Feature/BannerCoverImageMediaTest.php`, cộng toàn bộ suite có sẵn
  (Post Search, Ocop Search, Approval, root Unit/Feature).
- `npx vite build --config vite.config.backend.js` build sạch, có `filepond.js` cho cả 3 module.
- Verify sống qua HTTP thật (curl 200) cho: trang public Post/Ocop/Banner, upload thật qua
  `MediaUploadController`/`MediaJoditUploadController`, ảnh + conversion (`medium.webp`) truy cập
  được sau khi reassociate.
- Dữ liệu demo/seed cũ (10 sản phẩm Ocop, banner nếu có) **không có ảnh** sau khi xoá cột cũ — đúng
  quyết định §7.3 (không viết command migrate dữ liệu cũ ở giai đoạn dev này), không phải bug.
