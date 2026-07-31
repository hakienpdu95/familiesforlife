# Module Quản lý Video (Video Management)
**Đặc tả Kỹ thuật Chi tiết — Sẵn sàng Triển khai**

**Phiên bản:** 1.4 — sửa lỗi CSP có sẵn của site chặn thumbnail/iframe YouTube, xem changelog dưới đây
**Ngày:** 31/07/2026
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions + Spatie Laravel Data
**Module mới:** `Modules/Video`
**Module tham chiếu kiến trúc:** `Modules/Banner` (module phẳng, không thuộc quy trình duyệt, cùng tinh thần "tài sản nền tảng" — xem §0)

> **Lịch sử phiên bản**
> - **v1.0** — Thư viện video độc lập: CRUD 4 trường (Tên, Mô tả, Link URL Video, Mã Embed video YouTube) + trang công khai liệt kê dạng lưới với lightbox phát video.
> - **v1.1** — Sau review bảo mật: (1) sửa lỗi nghiêm trọng — validate ở Controller từng bắt buộc `embed_code` trong khi business rule ở §0/§5.2 cho phép chỉ cần 1 trong 2 trường; (2) `watch_url` không còn tin thẳng `video_url` thô — thêm whitelist domain YouTube để chặn vector phishing nếu tài khoản `video.manage` bị chiếm; (3) mở rộng regex nhận diện ID (`/shorts/`, `/live/`, `/v/` legacy, `music.youtube.com`, `m.youtube.com`) + bắt buộc unit test adversarial; (4) bổ sung đầy đủ contract API Tabulator (`ListVideosForAdminQuery`/`Handler`/`VideoListResource`) và Action toggle `is_active` còn thiếu; (5) ghi chú CSP `frame-src`/`sandbox` (defense-in-depth), empty state, fallback thumbnail lỗi, a11y cơ bản cho trang công khai; (6) làm rõ chủ đích `created_by`/`updated_by` dùng 2 kiểu xoá khác nhau.
> - **v1.2** — Các điểm không-blocking còn lại: (1) gộp whitelist host YouTube về `config('video.allowed_hosts')` — 1 nguồn duy nhất cho cả Model lẫn Action, xoá 2 hằng số PHP trùng nhau; (2) sửa bảng §1 — `embed_code` không còn ghi "Có" (mâu thuẫn với business rule "1 trong 2" đã chốt); (3) viết đầy đủ code `UpdateVideoAction` (trước đây chỉ mô tả bằng lời); (4) xác nhận `@alpinejs/focus` **chưa** có trong bundle (`resources/js/app.js:38-39`) — ghi rõ đây là dependency mới cần cài, kèm phương án bỏ qua nếu muốn tối giản; (5) demo seeder có sẵn 3 ID YouTube công khai cụ thể để QA ngay; (6) ghi chú chấp nhận được của lệch nhỏ giữa `trim()` trong `extractFrom()` và rule `url` không tự trim.
> - **v1.3** — Implement xong module + chạy đúng bộ unit test đối kháng bắt buộc ở §4.3 (`Modules/Video/tests/Feature/ResolveYoutubeVideoIdActionTest.php`, 34 test case), phát hiện VÀ sửa 2 lỗi thật trong regex mà bản thảo v1.2 chưa từng chạy qua test: (1) pattern `watch?v=ID` (dạng URL YouTube phổ biến nhất) **luôn fail** vì yêu cầu `[?&]` xuất hiện lại ngay sau dấu `?` gốc — dấu `?` đó đã bị literal `\?` tiêu thụ nên không còn để khớp lại, sửa bằng `(?:[^"'\s]*&)?v=`; (2) mọi pattern URL trần (`watch`, `youtu.be`, `shorts`, `live`, `/v/`, `embed`) bị domain giả mạo qua mặt khi 1 URL YouTube "thật" bị NHÚNG bên trong 1 URL độc hại khác (vd `https://evil.com/?redirect=https://youtube.com/watch?v=ID`) — vì `preg_match` tìm khớp ở bất kỳ đâu trong chuỗi, không anchor vào đầu chuỗi; sửa bằng lookbehind `(?<![=\w])` bắt buộc ký tự ngay trước `https?://` không phải `=`/ký tự chữ-số (không cần cho pattern `<iframe>` vì đã tự anchor qua `src=["']`). Đây chính xác là lý do §4.3/§8 bắt buộc viết test đối kháng TRƯỚC khi ghép vào Create/UpdateVideoAction thay vì tin tưởng regex "trông có vẻ đúng".
> - **v1.4** — Phát hiện khi QA thật trên trình duyệt: site đã có sẵn `App\Http\Middleware\SecurityHeaders` gắn Content-Security-Policy cho TOÀN BỘ nhóm middleware `web` (áp dụng cho cả `/videos`) — `img-src`/`frame-src` trước đó KHÔNG whitelist domain YouTube nào, khiến thumbnail (`i.ytimg.com`) bị trình duyệt chặn tải, và nếu không sửa thì iframe lightbox (`youtube-nocookie.com`) cũng sẽ bị chặn tương tự khi bấm phát video. §7.4 (bản v1.0-1.3) mô tả sai mức độ nghiêm trọng — ghi "khuyến nghị, không bắt buộc cho v1" trong khi đây thực chất là **lỗi chức năng chặn cứng** do CSP có sẵn của site, không phải 1 lớp phòng thủ tuỳ chọn thêm vào sau. Đã sửa `app/Http/Middleware/SecurityHeaders.php::buildCsp()` — thêm `https://i.ytimg.com` vào `img-src`, thêm `https://www.youtube-nocookie.com` vào `frame-src` (khớp đúng `config('video.embed_domain')`).

---

## 0. Quyết định đã chốt

| Chủ đề | Quyết định spec này | Lý do |
|---|---|---|
| **Phạm vi module** | Thư viện video **độc lập** — 1 trang quản trị CRUD riêng (`dashboard/videos`) + 1 trang công khai liệt kê (`/videos`), **không** phải 1 loại content block chèn vào bài viết | Đã xác nhận với người yêu cầu — khác hướng "nhúng trong bài viết" (`Modules\Post\Enums\ContentBlockType` hiện chỉ có `Text/Product/Faq/Citation/Howto`, không có `Video` — thêm 1 case mới ở đó là phạm vi của 1 spec khác, không phải spec này) |
| **Không lưu file ảnh đại diện (thumbnail)** | Ảnh đại diện **tự suy ra** từ `youtube_video_id` qua CDN công khai của YouTube (`https://i.ytimg.com/vi/{id}/hqdefault.jpg`), **không** dùng Spatie Media Library, không có bảng/collection media nào | Khác `Banner` (phải tự upload ảnh vì banner không có "nguồn ảnh chính chủ" nào khác) — video YouTube luôn có sẵn thumbnail chính chủ, tải/lưu lại là dư thừa: tốn storage, và ảnh có thể lệch nếu chủ video đổi thumbnail sau này. Không cần `MediaUploadService`/`config/media.php`, giảm hẳn 1 tầng phức tạp so với Banner |
| **Không render trực tiếp `embed_code` do người dùng nhập** | `embed_code` (mã HTML/URL người quản trị dán vào) chỉ dùng để **trích xuất** `youtube_video_id` (11 ký tự) ngay khi lưu — trang công khai **luôn** dựng lại `<iframe>` từ `youtube_video_id` đã validate, không bao giờ in ra nguyên văn `embed_code` | **Chống stored-XSS**: `embed_code` là ô nhập tự do (người quản trị có thể dán nguyên khối `<iframe>`, hoặc lỡ dán nhầm 1 đoạn HTML khác) — nếu render thẳng ra trang công khai, bất kỳ ai có quyền `video.manage` (kể cả 1 tài khoản bị chiếm quyền) có thể chèn `<script>`/`onerror=`/iframe trỏ domain lạ ảnh hưởng MỌI khách truy cập trang `/videos`. Codebase hiện **chưa có** cơ chế sanitize iframe nào tái dùng được (`ArticleContentRenderer::sanitizeTextHtml()` chỉ xử lý text-block, không xử lý embed) — đây là quyết định thiết kế mới, không phải tái dùng pattern có sẵn |
| **Domain nhúng: `youtube-nocookie.com` thay vì `youtube.com`** | `<iframe src="https://www.youtube-nocookie.com/embed/{id}">` | Chế độ "privacy-enhanced" chính thức của YouTube — giảm cookie theo dõi khi khách chưa tương tác với video, không đánh đổi tính năng gì. Có thể đổi lại `youtube.com` bằng 1 dòng cấu hình nếu team không cần (§4.2) |
| **`video_url` VÀ `embed_code` đều nullable ở tầng validate** | Người quản trị có thể điền 1 trong 2 (không bắt buộc cả hai) — Controller chỉ validate **định dạng** (`nullable`), business rule "ít nhất 1 trong 2 phải trích xuất được ID hợp lệ" nằm ở Action (`ResolveYoutubeVideoIdAction`, §5.2), có `required_without` 2 chiều để báo lỗi sớm ở tầng form | **Sửa lỗi phát hiện khi review (Critical)**: bản thảo đầu ép `embed_code` là `required` ở Controller, mâu thuẫn trực tiếp với chính quyết định "ít nhất 1 trong 2" đã nêu ở đây — khiến người dùng KHÔNG THỂ chỉ dán `video_url` như spec đã hứa. §5.1/§5.2/§6.3 đã sửa đồng bộ |
| **`watch_url` KHÔNG tin thẳng `video_url` thô — validate whitelist domain YouTube** | `video_url` chỉ được lưu nếu host nằm trong danh sách domain YouTube hợp lệ (`youtube.com`, `www.youtube.com`, `m.youtube.com`, `music.youtube.com`, `youtu.be`) — kiểm tra bằng `parse_url() + so khớp chính xác host` (không phải regex tìm chuỗi con); `getWatchUrlAttribute()` tự kiểm tra lại host 1 lần nữa trước khi trả về (defense-in-depth), fallback sang URL dựng từ `youtube_video_id` nếu host không hợp lệ | **Chống phishing (phát hiện khi review)**: nếu tài khoản `video.manage` bị chiếm, kẻ tấn công có thể dán `embed_code` hợp lệ (để qua được bước resolve ID) kèm `video_url` trỏ tới domain lừa đảo bất kỳ — nút "Xem trên YouTube" khi đó dẫn người dùng thật sự rời trang tới nơi độc hại dù `embed_code`/iframe vẫn an toàn. Validate + defense-in-depth ở accessor đảm bảo `video_url` không đi vào DB (hoặc không được dùng làm href) nếu không phải domain YouTube thật, kể cả khi có ai đó ghi thẳng vào DB bỏ qua Action (migration lỗi, seeder sai, thao tác tay) |
| **Quyền hạn** | 1 permission duy nhất `video.manage` — seed trực tiếp qua `VideoPermissionSeeder` (**Lớp B**, giống `banner.manage`/`page.manage`), **không** qua `config/permissions.php` (Lớp A) | Video là tài sản vận hành/marketing đơn giản (như Banner), không có phân vai biên tập nhiều cấp (viết/duyệt/xuất bản) như `Post`/`RealEstate` — 1 permission "quản lý toàn quyền" là đủ, gán cho `platform_ops` + `platform_content_head` |
| **Không có quy trình duyệt** | Tạo xong hiển thị ngay nếu `is_active = true` (không có state `submitted → approved → published`) | Cùng lý do Banner (§0 của `Banner_Management_Technical_Specification.md`) — video do nội bộ platform tạo trực tiếp qua dashboard, không nhận nộp từ công chúng |
| **Trang công khai: lưới + lightbox, không có trang chi tiết riêng từng video** | 1 route duy nhất `/videos` (danh sách dạng lưới, thumbnail + tên + mô tả rút gọn); bấm vào 1 video mở modal (Alpine) phát trực tiếp, **không** điều hướng sang URL riêng `/videos/{video}` | Đúng phạm vi 4 trường đã yêu cầu — không cần SEO landing page riêng cho từng video ở v1 (không có slug, không có related videos). Có thể thêm sau mà không đổi schema (§9) |
| **Không ràng buộc trùng video (dedup)** | Cho phép nhiều bản ghi cùng trỏ 1 `youtube_video_id` — không có `unique` constraint | Không phải yêu cầu nghiệp vụ đã nêu; ép unique sớm có thể chặn nhầm trường hợp hợp lệ (vd 2 bản ghi mô tả cùng 1 video nhưng dùng cho 2 mục đích hiển thị khác nhau trong tương lai). `youtube_video_id` vẫn có index thường để tra cứu/lọc nhanh |
| **Sắp xếp hiển thị** | Cột `sort_order` (số nguyên, nhập tay trên form — **không** làm kéo-thả AJAX như Banner) | Giữ tối giản đúng phạm vi yêu cầu; kéo-thả là tiện ích UX có thể thêm sau (§9) mà không đổi schema |
| **Regex nhận diện ID phải phủ đủ format thực tế + có test đối kháng (adversarial)** | Mở rộng `ResolveYoutubeVideoIdAction` phủ thêm `/shorts/{id}`, `/live/{id}`, `/v/{id}` (legacy), host `music.youtube.com`/`m.youtube.com`; ràng buộc `v=` phải đứng sau `?`/`&` (không match nhầm giá trị chứa chuỗi con `v=`); bắt buộc viết unit test cho cả input hợp lệ, input rác, VÀ input cố ý độc hại (`<script>`, `javascript:`, `data:`, iframe giả mạo trỏ domain khác) trước khi ghép vào Action thật (§4.3/§8) | Bản thảo đầu chỉ cover 5 pattern phổ biến nhất, thiếu nhiều dạng URL YouTube thật (đặc biệt Shorts đang là format phổ biến) — thiếu unit test đối kháng thì không ai chứng minh được regex thực sự an toàn trước input cố tình phá hoại, chỉ mới "trông có vẻ đúng" với vài ví dụ hợp lệ |
| **API quản trị (Tabulator) có contract JSON rõ ràng + Action `toggle is_active`** | `ListVideosForAdminQuery`/`Handler`/`VideoListResource` định nghĩa đầy đủ field trả về (§6.5); thêm `ToggleVideoActiveAction` + route `PATCH items/{video}/toggle-active` (§6.4/§6.5) cho nút bật/tắt nhanh đã nhắc ở UI nhưng chưa có backend | Bản thảo đầu chỉ liệt tên class rỗng không có signature/response shape — frontend (Tabulator) và backend không thể làm song song nếu không thống nhất contract trước; UI "toggle nhanh `is_active`" (§6.5 cũ) được mô tả nhưng không có route/Action nào thực hiện được hành động đó |

---

## 1. Giới thiệu & Mục tiêu

Cổng thông tin hiện **chưa có khái niệm thư viện video dùng chung** — nếu Ops/Marketing muốn giới thiệu 1 video YouTube (phỏng vấn, video hướng dẫn, TVC...), không có nơi nào để quản lý tập trung: không có trang liệt kê công khai, không kiểm soát được nội dung nhúng, không tái sử dụng được giữa các trang.

Module **Video** giải quyết bằng 1 bảng `videos` tự quản lý với đúng 4 thông tin nghiệp vụ cốt lõi:

| Trường nghiệp vụ | Cột DB | Bắt buộc? |
|---|---|---|
| Tên | `name` | Có |
| Mô tả | `description` | Không |
| Link URL Video | `video_url` | Không (xem §0 — ít nhất 1 trong 2 với `embed_code`) |
| Mã Embed video YouTube | `embed_code` | Không (xem §0 — ít nhất 1 trong 2 với `video_url`) |

Cộng thêm các cột hạ tầng chuẩn của mọi module phẳng trong dự án này (`uuid`, `sort_order`, `is_active`, `created_by`/`updated_by`, soft delete) — xem §3.

**Nguyên tắc thiết kế cốt lõi:** người quản trị chỉ cần dán **URL** (`https://www.youtube.com/watch?v=...`, `https://youtu.be/...`) hoặc **nguyên khối mã nhúng** YouTube copy từ nút "Share → Embed" vào ô "Mã Embed video YouTube" — hệ thống tự nhận diện ID video hợp lệ, không yêu cầu người dùng tự tay cắt lấy 11 ký tự ID.

---

## 2. Khảo sát điểm vào (entry points)

| Vị trí | Route | Ghi chú |
|---|---|---|
| Sidebar dashboard (mục quản trị mới) | `backend.video.items.index` | Hiển thị nếu `@can('video.manage')`, cùng pattern Banner (`resources/views/layouts/partials/sidebar.blade.php`) |
| Trang công khai — danh sách video | `GET /videos` → `video.index` | Layout dùng chung `layouts/frontend.blade.php`, không phụ thuộc `Modules/Post` |
| API nội bộ cho bảng Tabulator ở trang quản trị | `GET backend/api/videos/items` → `backend.api.videos.items` | Trả JSON phân trang/sort, cùng pattern `BannerApiController` |

Không có vị trí nhúng vào `Modules/Post`/`Modules/Event` ở v1 (đúng phạm vi §0).

---

## 3. Kiến trúc dữ liệu

### 3.1 ERD

```
Video
  ├─ uuid
  ├─ name (string, 255)                      — Tên video, hiển thị công khai
  ├─ description (text, nullable)            — Mô tả, hiển thị công khai (rút gọn ở lưới, đầy đủ khi cần)
  ├─ video_url (nullable string, 2048)        — Link URL Video gốc (Youtube.com/youtu.be), PHẢI qua whitelist domain khi lưu (§0/§4.1) — dùng cho nút "Xem trên YouTube" (§7.1)
  ├─ embed_code (nullable text)               — Mã Embed YouTube RAW như người dùng dán (URL hoặc <iframe>...</iframe>) — nullable, chỉ bắt buộc "1 trong 2" với video_url (§0); CHỈ dùng để trích xuất, KHÔNG render trực tiếp (§0/§5.2)
  ├─ youtube_video_id (string, 20)            — Trích xuất + validate từ embed_code (fallback video_url) — nguồn DUY NHẤT dùng để dựng iframe/thumbnail công khai
  ├─ sort_order (unsigned smallint)
  ├─ is_active (bool)
  ├─ created_by, updated_by, timestamps, soft delete
```

Không có bảng con, không `organization_id` — video là tài sản nền tảng (platform), cùng nguyên tắc đã áp dụng cho `Banner`/`Event`/`MenuItem` (`spec/Platform_RBAC_Phase2_Specification.md` §3.3): không tổ chức (tenant) nào sở hữu video, phục vụ đồng nhất cho toàn cổng thông tin.

### 3.2 Migration

`Modules/Video/database/migrations/2026_07_31_000000_create_videos_table.php`

```php
Schema::create('videos', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();

    $table->string('name', 255);
    $table->text('description')->nullable();

    $table->string('video_url', 2048)->nullable();
    $table->text('embed_code')->nullable(); // nullable — chỉ bắt buộc "1 trong 2" với video_url, validate ở Action (§5.2)
    $table->string('youtube_video_id', 20);

    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);

    // created_by: restrictOnDelete — KHÔNG cho xoá 1 User nếu họ còn là người TẠO video nào đó
    // (giữ nguyên vẹn nguồn gốc/provenance để audit, muốn xoá User đó phải xử lý video liên quan
    // trước). updated_by: nullOnDelete — cho phép xoá User dù họ từng SỬA (không phải tạo) 1 video,
    // vì "người sửa gần nhất" chỉ là vết tích tham khảo phụ, không phải thông tin bắt buộc giữ lại.
    // Đây là quyết định CHỦ ĐÍCH (không phải thiếu nhất quán) — copy nguyên xi cách Banner đã làm
    // (Modules/Banner/database/migrations/2026_07_15_150003_create_banners_table.php).
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['is_active', 'sort_order'], 'idx_video_active_sort');
    $table->index('youtube_video_id', 'idx_video_youtube_id');
});
```

---

## 4. Model & cấu hình

### 4.1 `Modules\Video\Models\Video`

```php
namespace Modules\Video\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Video extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid', 'name', 'description', 'video_url', 'embed_code', 'youtube_video_id',
        'sort_order', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $video): void {
            $video->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Ảnh đại diện LUÔN suy ra từ youtube_video_id đã validate (§0) — không có cột lưu file,
     * không phụ thuộc Media Library. `hqdefault.jpg` tồn tại cho MỌI video YouTube (khác
     * `maxresdefault.jpg` không phải video nào cũng có bản độ phân giải cao).
     */
    public function getThumbnailUrlAttribute(): string
    {
        return "https://i.ytimg.com/vi/{$this->youtube_video_id}/hqdefault.jpg";
    }

    /**
     * URL nhúng an toàn — LUÔN dựng từ youtube_video_id đã validate, KHÔNG liên quan gì tới
     * chuỗi embed_code người dùng đã nhập (§0/§5.2 — lý do chống XSS).
     */
    public function getEmbedUrlAttribute(): string
    {
        $domain = config('video.embed_domain', 'www.youtube-nocookie.com');

        return "https://{$domain}/embed/{$this->youtube_video_id}";
    }

    /**
     * Link "Xem trên YouTube" — CHỈ dùng video_url gốc nếu host của nó khớp whitelist
     * (`config('video.allowed_hosts')`, §4.2 — NGUỒN DUY NHẤT, dùng chung với
     * ResolveYoutubeVideoIdAction::isWhitelistedHost() ở §4.3, tránh khai 2 danh sách trùng
     * nhau). Đã validate khi lưu ở §5.2, nhưng kiểm tra lại ở đây làm lớp phòng thủ thứ 2 — phòng
     * trường hợp dữ liệu vào DB không qua Action (vd sửa tay/migration/seeder sai). Không khớp
     * hoặc trống → LUÔN fallback về URL dựng từ youtube_video_id đã validate, KHÔNG BAO GIỜ trả
     * thẳng 1 chuỗi không rõ nguồn gốc — chống vector phishing nếu tài khoản video.manage bị
     * chiếm (§0). So khớp CHÍNH XÁC bằng parse_url()+in_array (không phải regex tìm chuỗi con) —
     * tránh bị qua mặt bởi URL kiểu `https://evil.com/?next=https://youtube.com/...` (host thật
     * ở đây là `evil.com`, không phải youtube.com, dù chuỗi "youtube.com" xuất hiện trong URL).
     */
    public function getWatchUrlAttribute(): string
    {
        if ($this->video_url) {
            $host = parse_url($this->video_url, PHP_URL_HOST);

            if ($host !== null && in_array($host, config('video.allowed_hosts', []), true)) {
                return $this->video_url;
            }
        }

        return "https://www.youtube.com/watch?v={$this->youtube_video_id}";
    }
}
```

### 4.2 `Modules/Video/config/config.php`

```php
return [
    'name' => 'Video',

    // Đổi thành 'www.youtube.com' nếu không cần chế độ privacy-enhanced (§0).
    'embed_domain' => 'www.youtube-nocookie.com',

    // Số video/trang ở trang công khai (§7.1) — tách khỏi code để đổi không cần deploy lại logic.
    'per_page' => 12,

    // Danh sách host YouTube hợp lệ — NGUỒN DUY NHẤT, đọc bởi CẢ 2 nơi cần whitelist domain:
    // Video::ALLOWED_VIDEO_URL_HOSTS (§4.1, accessor watch_url) VÀ
    // ResolveYoutubeVideoIdAction::isWhitelistedHost() (§4.3/§5.2, validate lúc lưu). Trước đây 2
    // nơi này khai báo 2 hằng số PHP trùng nhau (DRY violation phát hiện khi review) — nay chỉ
    // sửa 1 chỗ duy nhất nếu YouTube thêm/đổi domain (vd thêm 1 ccTLD mới).
    'allowed_hosts' => [
        'youtube.com', 'www.youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtu.be',
    ],
];
```

### 4.3 `ResolveYoutubeVideoIdAction` — trái tim của việc chống XSS

`Modules/Video/app/Features/VideoManagement/Actions/ResolveYoutubeVideoIdAction.php`

```php
namespace Modules\Video\Features\VideoManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;

class ResolveYoutubeVideoIdAction
{
    use AsAction;

    private const ID_PATTERN = '[A-Za-z0-9_-]{11}';

    /**
     * Thử trích xuất ID hợp lệ (11 ký tự) từ $embedCode trước (chấp nhận nguyên khối <iframe>,
     * URL watch/shorts/live/embed, hoặc ID trần), rồi fallback sang $videoUrl nếu $embedCode
     * không trích được gì (hoặc rỗng — cả 2 tham số đều nullable, §0/§5.1). Trả null nếu CẢ HAI
     * đều không có ID hợp lệ — nơi gọi (Create/UpdateVideoAction) chịu trách nhiệm quăng lỗi
     * validate, hàm này chỉ resolve thuần tuý, không side-effect.
     */
    public function handle(?string $embedCode, ?string $videoUrl = null): ?string
    {
        if ($embedCode && $id = $this->extractFrom($embedCode)) {
            return $id;
        }

        return $videoUrl ? $this->extractFrom($videoUrl) : null;
    }

    /**
     * Dùng bởi Action Create/Update (§5.2) để validate video_url trước khi lưu — so khớp host
     * CHÍNH XÁC qua parse_url(), không phải tìm chuỗi con trong URL (§4.1 giải thích lý do). Đọc
     * `config('video.allowed_hosts')` — CÙNG NGUỒN với `Video::getWatchUrlAttribute()` (§4.1), sửa
     * 1 chỗ duy nhất trong config/video.php nếu cần thêm/bớt domain (DRY — trước đây spec khai 2
     * hằng số PHP trùng nhau ở Model và Action, đã gộp lại).
     */
    public function isWhitelistedHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host !== null && in_array($host, config('video.allowed_hosts', []), true);
    }

    private function extractFrom(string $input): ?string
    {
        $input = trim($input);

        // (?<![=\w]) — bắt buộc ký tự NGAY TRƯỚC "https?://" (nếu có) không phải "=" hay ký tự
        // chữ/số/gạch dưới. Không có lookbehind này, preg_match (tìm khớp Ở BẤT KỲ ĐÂU trong
        // chuỗi) bị qua mặt bởi domain giả mạo kiểu
        // "https://evil.com/?redirect=https://youtube.com/watch?v=ID" — phát hiện bởi bộ test
        // đối kháng khi implement (v1.3), xem changelog đầu file. Không áp dụng cho pattern
        // <iframe> (đã tự anchor qua src=["'] ngay trước domain).
        $notEmbedded = '(?<![=\w])';

        $patterns = [
            // <iframe ... src="https://www.youtube.com/embed/ID" ...> (thứ tự attribute/loại quote bất kỳ nhờ [^>]+ quét cả thẻ)
            '~<iframe[^>]+src=["\']https?://(?:www\.)?youtube(?:-nocookie)?\.com/embed/(' . self::ID_PATTERN . ')~i',
            // https://www.youtube.com/watch?v=ID (v là param ĐẦU) hoặc watch?list=...&v=ID (v đứng sau param khác) —
            // (?:[^"'\s]*&)? bắt buộc "v=" phải đứng ngay sau "?" HOẶC ngay sau "&", không match nhầm 1 param khác
            // chứa chuỗi con "v=" (vd "?abv=xxxxxxxxxxx"). v1.3: SỬA LỖI — bản v1.0-1.2 yêu cầu "[?&]" xuất hiện lại
            // sau dấu "?" gốc, luôn fail khi v= là param đầu tiên (trường hợp watch?v=... phổ biến nhất) vì dấu "?"
            // đã bị literal "\?" tiêu thụ, phát hiện bởi bộ test đối kháng khi implement.
            '~' . $notEmbedded . 'https?://(?:www\.|m\.|music\.)?youtube\.com/watch\?(?:[^"\'\s]*&)?v=(' . self::ID_PATTERN . ')~i',
            // https://youtu.be/ID (kèm hoặc không kèm ?si=.../timestamp — capture group cố định 11 ký tự nên không ăn lẫn query phía sau)
            '~' . $notEmbedded . 'https?://youtu\.be/(' . self::ID_PATTERN . ')~i',
            // https://www.youtube.com/shorts/ID
            '~' . $notEmbedded . 'https?://(?:www\.)?youtube\.com/shorts/(' . self::ID_PATTERN . ')~i',
            // https://www.youtube.com/live/ID
            '~' . $notEmbedded . 'https?://(?:www\.)?youtube\.com/live/(' . self::ID_PATTERN . ')~i',
            // https://www.youtube.com/v/ID (legacy player URL)
            '~' . $notEmbedded . 'https?://(?:www\.)?youtube\.com/v/(' . self::ID_PATTERN . ')~i',
            // https://www.youtube.com/embed/ID hoặc youtube-nocookie.com/embed/ID (dán thẳng URL embed, không kèm thẻ iframe)
            '~' . $notEmbedded . 'https?://(?:www\.|m\.)?youtube(?:-nocookie)?\.com/embed/(' . self::ID_PATTERN . ')~i',
            // ID trần (người dùng chỉ dán đúng 11 ký tự, không kèm URL)
            '~^(' . self::ID_PATTERN . ')$~',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input, $matches) === 1) {
                return $matches[1];
            }
        }

        return null;
    }
}
```

> Action này **chỉ đọc chuỗi để tìm ID theo regex whitelist** — không bao giờ `Str::replace`/echo lại `$input`, không dùng `strip_tags` rồi render phần còn lại (dễ sót payload). Nếu không khớp bất kỳ pattern nào ở trên, coi như không hợp lệ, không cố "cứu" bằng cách nới lỏng regex.
>
> **Bắt buộc unit test đối kháng (adversarial) trước khi ghép vào Action Create/Update (§8, phase 2)** — tối thiểu phải cover:
>
> | Nhóm | Input mẫu | Kỳ vọng |
> |---|---|---|
> | Hợp lệ — các format URL | `watch?v=`, `youtu.be/`, `/shorts/`, `/live/`, `/v/` (legacy), `/embed/`, kèm `music.`/`m.`/`www.`/không prefix | Trích đúng 11 ký tự ID |
> | Hợp lệ — nhiễu xung quanh | `youtu.be/ID?si=xxxx`, `watch?list=PL...&v=ID`, `watch?v=ID&t=90s`, iframe attribute đảo thứ tự (`title=... src=...`), quote đơn `'...'` | Vẫn trích đúng ID, không bị lệch bởi tham số/attribute khác |
> | ID trần | Đúng 11 ký tự `[A-Za-z0-9_-]` | Trích đúng, KHÔNG cần bọc URL |
> | **Đối kháng — HTML/script injection** | `<script>alert(1)</script>`, `<iframe src="javascript:alert(1)">`, `<iframe src="data:text/html,...">`, `<img src=x onerror=alert(1)>` | Trả **null** (không có pattern nào khớp `https?://youtube...`) |
> | **Đối kháng — domain giả mạo** | `https://youtube.com.evil.com/watch?v=xxxxxxxxxxx`, `https://evil.com/?redirect=https://youtube.com/watch?v=xxxxxxxxxxx`, `https://notyoutube.com/embed/xxxxxxxxxxx` | Trả **null** — regex neo domain ngay sau `https?://(?:www\.\|...)?youtube...`, không match domain giả có "youtube" là substring |
> | **Đối kháng — tham số giả `v=`** | `https://youtube.com/watch?abv=xxxxxxxxxxx` (không có `?`/`&` thật trước `v=`) | Trả **null** nhờ `[?&]v=` bắt buộc ranh giới tham số |
> | **Rỗng/thiếu** | `''`, `null`, chuỗi ngắn hơn 11 ký tự | Trả **null**, không throw exception ở tầng Action này (Create/UpdateVideoAction mới là nơi quăng `ValidationException`, §5.2) |
>
> `isWhitelistedHost()` cũng cần test riêng cho đúng các case ở bảng "Đối kháng — domain giả mạo" — phải trả `false` cho `youtube.com.evil.com`/`evil.com?redirect=youtube.com` vì `parse_url()` trả host CHÍNH XÁC là `youtube.com.evil.com`/`evil.com`, không khớp whitelist dù chuỗi "youtube.com" xuất hiện trong URL.

---

## 5. Business rules

### 5.1 Validate khi tạo/sửa (`VideoAdminController::validated()` + Action)

Theo đúng pattern `BannerAdminController` — validate ngay trong Controller (không tách FormRequest riêng), build DTO sau khi validate:

```php
private function validated(Request $request): array
{
    return $request->validate([
        'name'        => ['required', 'string', 'max:255'],
        'description' => ['nullable', 'string', 'max:2000'],
        'video_url'   => ['nullable', 'url', 'max:2048', 'required_without:embed_code'],
        'embed_code'  => ['nullable', 'string', 'max:2000', 'required_without:video_url'],
        'sort_order'  => ['nullable', 'integer', 'min:0'],
        'is_active'   => ['boolean'],
    ]);
}
```

- **`video_url`/`embed_code` đều `nullable`** (sửa so với bản thảo đầu — xem changelog v1.1 đầu file): `required_without` 2 chiều chỉ đảm bảo **có nhập ít nhất 1 trong 2 field**, KHÔNG đảm bảo giá trị đó thực sự là YouTube hợp lệ — việc đó thuộc về `ResolveYoutubeVideoIdAction`/host-whitelist ở tầng Action (§5.2), tránh 2 nơi cùng kiểm tra 1 việc bằng 2 cách khác nhau dễ lệch kết quả (Laravel không có rule built-in để validate host của 1 URL).
- `embed_code`: giới hạn 2000 ký tự (đủ cho 1 khối `<iframe>` YouTube chuẩn kèm vài tham số, chặn việc dán nhầm cả trang HTML).
- `video_url`: `url` hợp lệ về **cú pháp** — domain YouTube thật hay không kiểm tra riêng ở Action (§5.2), vì rule `url` của Laravel không giới hạn được host.
- **Whitespace thừa (chấp nhận được ở v1, không blocking):** rule `url` của Laravel **không** tự `trim()` giá trị trước khi kiểm tra cú pháp, trong khi `ResolveYoutubeVideoIdAction::extractFrom()` (§4.3) có `trim()` — nếu người dùng dán `video_url` kèm khoảng trắng đầu/cuối (dễ xảy ra khi copy-paste), rule `url` có thể fail dù `extractFrom()` vẫn xử lý đúng nếu chuỗi tới được đó. Chấp nhận sự lệch nhỏ này ở v1 (validate fail thì người dùng chỉ cần xoá khoảng trắng, không phải lỗi nghiêm trọng); có thể đồng bộ sau bằng `prepareForValidation()` trong `VideoAdminController` (`trim()` cả `video_url`/`embed_code` trước khi validate) nếu phát sinh phàn nàn thật từ người dùng — chưa cần làm ngay.

### 5.2 Trích xuất & chống XSS (Action layer)

`CreateVideoAction`/`UpdateVideoAction` gọi `ResolveYoutubeVideoIdAction` **ngay sau** validate format, **trước khi** ghi DB:

```php
namespace Modules\Video\Features\VideoManagement\Actions;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Video\Features\VideoManagement\Data\VideoData;
use Modules\Video\Models\Video;

class CreateVideoAction
{
    use AsAction;

    public function __construct(
        private readonly ResolveYoutubeVideoIdAction $resolveYoutubeVideoId,
    ) {}

    public function handle(VideoData $data): Video
    {
        if ($data->video_url && ! $this->resolveYoutubeVideoId->isWhitelistedHost($data->video_url)) {
            throw ValidationException::withMessages([
                'video_url' => 'Link URL Video phải là 1 đường dẫn YouTube hợp lệ (youtube.com, youtu.be, m.youtube.com, music.youtube.com).',
            ]);
        }

        $youtubeVideoId = $this->resolveYoutubeVideoId->handle($data->embed_code, $data->video_url);

        if (! $youtubeVideoId) {
            throw ValidationException::withMessages([
                'embed_code' => 'Không nhận diện được video YouTube hợp lệ từ Mã Embed hoặc Link URL Video đã nhập. Vui lòng dán lại URL hoặc mã nhúng lấy trực tiếp từ YouTube.',
            ]);
        }

        return Video::create([
            'name'             => $data->name,
            'description'      => $data->description,
            'video_url'        => $data->video_url,
            'embed_code'       => $data->embed_code,
            'youtube_video_id' => $youtubeVideoId,
            'sort_order'       => $data->sort_order,
            'is_active'        => $data->is_active,
            'created_by'       => auth()->id(),
        ]);
    }
}
```

`UpdateVideoAction` cùng logic validate/whitelist, chỉ khác: chỉ re-resolve `youtube_video_id` khi `embed_code` hoặc `video_url` thực sự thay đổi (so với giá trị đang lưu — tránh gọi lại `ResolveYoutubeVideoIdAction` một cách vô ích mỗi lần sửa `name`/`description`/`sort_order`), và ghi `updated_by` thay vì `created_by`:

```php
namespace Modules\Video\Features\VideoManagement\Actions;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Video\Features\VideoManagement\Data\VideoData;
use Modules\Video\Models\Video;

class UpdateVideoAction
{
    use AsAction;

    public function __construct(
        private readonly ResolveYoutubeVideoIdAction $resolveYoutubeVideoId,
    ) {}

    public function handle(Video $video, VideoData $data): Video
    {
        $urlChanged = $data->video_url !== $video->video_url;
        $embedChanged = $data->embed_code !== $video->embed_code;

        $youtubeVideoId = $video->youtube_video_id;

        if ($urlChanged || $embedChanged) {
            if ($data->video_url && ! $this->resolveYoutubeVideoId->isWhitelistedHost($data->video_url)) {
                throw ValidationException::withMessages([
                    'video_url' => 'Link URL Video phải là 1 đường dẫn YouTube hợp lệ (youtube.com, youtu.be, m.youtube.com, music.youtube.com).',
                ]);
            }

            $resolved = $this->resolveYoutubeVideoId->handle($data->embed_code, $data->video_url);

            if (! $resolved) {
                throw ValidationException::withMessages([
                    'embed_code' => 'Không nhận diện được video YouTube hợp lệ từ Mã Embed hoặc Link URL Video đã nhập. Vui lòng dán lại URL hoặc mã nhúng lấy trực tiếp từ YouTube.',
                ]);
            }

            $youtubeVideoId = $resolved;
        }

        $video->update([
            'name'             => $data->name,
            'description'      => $data->description,
            'video_url'        => $data->video_url,
            'embed_code'       => $data->embed_code,
            'youtube_video_id' => $youtubeVideoId,
            'sort_order'       => $data->sort_order,
            'is_active'        => $data->is_active,
            'updated_by'       => auth()->id(),
        ]);

        return $video->fresh();
    }
}
```

So sánh trực tiếp `$data->video_url !== $video->video_url` (giá trị MỚI từ form vs giá trị ĐANG lưu trong DB) — không dùng `$video->isDirty()` vì `$video` ở đây **chưa** được gán giá trị mới (Action nhận `VideoData` riêng, chưa `fill()` vào Model), nên `isDirty()` lúc này luôn `false`.

`DeleteVideoAction` chỉ gọi `$video->delete()` (soft-delete) — không có tài nguyên phụ nào cần dọn (không file ảnh, không bảng con).

**Nguyên tắc bất biến của toàn bộ module:** không có bất kỳ Blade view nào (`admin/videos/*` lẫn `public/index`) được phép in `{{ $video->embed_code }}` hoặc `{!! $video->embed_code !!}` ra HTML. Trường này chỉ đọc lại trong **form sửa** (hiển thị trong `<textarea>` để người dùng thấy đúng những gì họ đã dán, giá trị text thuần qua `{{ }}` tự động escape của Blade — an toàn vì render trong `<textarea>` chứ không phải render thành iframe sống) — trang công khai không bao giờ chạm tới cột này.

### 5.3 Ảnh đại diện

`Video::thumbnail_url` (accessor, §4.1) trả thẳng URL CDN YouTube — không có Action/Job tải ảnh về, không có cột lưu file. Nếu video bị gỡ khỏi YouTube, ảnh trả 404 từ phía YouTube (không xử lý riêng ở v1 — xem §9).

### 5.4 Sắp xếp & bật/tắt

`sort_order` (số nguyên, nhập tay ở form) quyết định thứ tự hiển thị tại trang công khai (`ORDER BY sort_order ASC`). `is_active = false` ẩn khỏi trang công khai nhưng vẫn thấy trong danh sách quản trị (không lọc theo `is_active` ở trang admin, chỉ lọc ở trang công khai — cùng nguyên tắc Banner §5.3).

---

## 6. Admin CRUD (`Modules/Video`)

### 6.1 Cấu trúc thư mục

Theo đúng kiến trúc feature-sliced đã dùng cho `Modules/Banner`:

```
Modules/Video/
├── app/
│   ├── Features/
│   │   ├── VideoManagement/
│   │   │   ├── Actions/
│   │   │   │   ├── CreateVideoAction.php
│   │   │   │   ├── UpdateVideoAction.php
│   │   │   │   ├── DeleteVideoAction.php
│   │   │   │   ├── ToggleVideoActiveAction.php
│   │   │   │   └── ResolveYoutubeVideoIdAction.php
│   │   │   ├── Data/
│   │   │   │   └── VideoData.php
│   │   │   ├── Http/
│   │   │   │   ├── VideoAdminController.php
│   │   │   │   ├── VideoApiController.php
│   │   │   │   └── Resources/
│   │   │   │       └── VideoListResource.php
│   │   │   └── Queries/
│   │   │       ├── ListVideosForAdminQuery.php
│   │   │       └── ListVideosForAdminHandler.php
│   │   └── PublicReading/
│   │       └── Http/
│   │           └── VideoPublicController.php
│   ├── Models/Video.php
│   ├── Policies/VideoPolicy.php
│   └── Providers/
│       ├── VideoServiceProvider.php
│       └── RouteServiceProvider.php
├── config/config.php
├── database/
│   ├── migrations/2026_07_31_000000_create_videos_table.php
│   └── seeders/
│       ├── VideoDatabaseSeeder.php
│       └── VideoPermissionSeeder.php
├── resources/views/
│   ├── admin/videos/{index,create,edit,_form}.blade.php
│   └── public/index.blade.php
├── routes/web.php
├── composer.json
└── module.json
```

### 6.2 `VideoData` (Spatie Laravel Data)

```php
namespace Modules\Video\Features\VideoManagement\Data;

use Spatie\LaravelData\Data;

class VideoData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $video_url,
        public readonly ?string $embed_code,
        public readonly int $sort_order = 0,
        public readonly bool $is_active = true,
    ) {
        // Validate thật nằm ở VideoAdminController::validated() + ResolveYoutubeVideoIdAction —
        // DTO này chỉ hydrate dữ liệu đã validate, cùng nguyên tắc BannerData.
    }
}
```

### 6.3 `VideoAdminController`

```php
namespace Modules\Video\Features\VideoManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Video\Features\VideoManagement\Actions\CreateVideoAction;
use Modules\Video\Features\VideoManagement\Actions\DeleteVideoAction;
use Modules\Video\Features\VideoManagement\Actions\ToggleVideoActiveAction;
use Modules\Video\Features\VideoManagement\Actions\UpdateVideoAction;
use Modules\Video\Features\VideoManagement\Data\VideoData;
use Modules\Video\Models\Video;

class VideoAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Video::class, 'video');
    }

    public function index(): View
    {
        return view('video::admin.videos.index');
    }

    public function create(): View
    {
        return view('video::admin.videos.create');
    }

    public function store(Request $request, CreateVideoAction $createVideo): RedirectResponse
    {
        $createVideo->handle(VideoData::from($this->validated($request)));

        return to_route('backend.video.items.index')->with('success', 'Đã thêm video.');
    }

    public function edit(Video $video): View
    {
        return view('video::admin.videos.edit', compact('video'));
    }

    public function update(Request $request, Video $video, UpdateVideoAction $updateVideo): RedirectResponse
    {
        $updateVideo->handle($video, VideoData::from($this->validated($request)));

        return to_route('backend.video.items.index')->with('success', 'Đã cập nhật video.');
    }

    public function destroy(Video $video, DeleteVideoAction $deleteVideo): RedirectResponse
    {
        $deleteVideo->handle($video);

        return to_route('backend.video.items.index')->with('success', 'Đã xoá video.');
    }

    /** Toggle nhanh is_active từ bảng danh sách (nút switch ở cột trạng thái, §6.6) — trả JSON cho Tabulator cập nhật UI tại chỗ, không reload trang. */
    public function toggleActive(Video $video, ToggleVideoActiveAction $toggleActive): JsonResponse
    {
        $this->authorize('update', $video);

        $video = $toggleActive->handle($video);

        return response()->json(['is_active' => $video->is_active]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'video_url'   => ['nullable', 'url', 'max:2048', 'required_without:embed_code'],
            'embed_code'  => ['nullable', 'string', 'max:2000', 'required_without:video_url'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['boolean'],
        ]);
    }
}
```

`authorizeResource` tự áp `VideoPolicy` (mọi ability đều check permission `video.manage`, cùng cách `BannerPolicy` hoạt động) cho mọi action CRUD — `toggleActive` không nằm trong 7 action RESTful mặc định nên `authorize('update', $video)` được gọi thủ công ngay trong method (cùng ability `update` đã có sẵn ở Policy, không cần thêm ability mới).

`ToggleVideoActiveAction` (`Modules/Video/app/Features/VideoManagement/Actions/ToggleVideoActiveAction.php`):

```php
namespace Modules\Video\Features\VideoManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Video\Models\Video;

class ToggleVideoActiveAction
{
    use AsAction;

    public function handle(Video $video): Video
    {
        $video->update([
            'is_active'  => ! $video->is_active,
            'updated_by' => auth()->id(),
        ]);

        return $video->fresh();
    }
}
```

### 6.4 Routes

`Modules/Video/routes/web.php`

```php
Route::middleware(['auth'])->prefix('dashboard/videos')->name('backend.video.')->group(function (): void {
    Route::resource('items', VideoAdminController::class)->except(['show'])->parameters(['items' => 'video']);
    Route::patch('items/{video}/toggle-active', [VideoAdminController::class, 'toggleActive'])->name('items.toggle-active');
});

Route::middleware(['auth'])->prefix('backend/api/videos')->name('backend.api.videos.')->group(function (): void {
    Route::get('items', [VideoApiController::class, 'index'])->name('items');
});

Route::get('videos', [VideoPublicController::class, 'index'])->name('video.index');
```

### 6.5 API contract cho Tabulator (`VideoApiController`)

`ListVideosForAdminQuery`/`ListVideosForAdminHandler` theo đúng cặp CQRS Query đã dùng ở `Modules/Banner` (`implements QueryInterface`/`QueryHandlerInterface`, `App\Shared\Contracts\*`):

```php
namespace Modules\Video\Features\VideoManagement\Queries;

use App\Shared\Contracts\QueryInterface;

final class ListVideosForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?bool $isActive = null,
        public readonly string $sortBy = 'sort_order',
        public readonly string $sortDirection = 'asc',
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
```

```php
namespace Modules\Video\Features\VideoManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Video\Models\Video;

final class ListVideosForAdminHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        // $query instanceof ListVideosForAdminQuery — type thật do QueryInterface chỉ khai báo hợp đồng chung (cùng pattern Banner).
        return Video::query()
            ->when($query->search, fn ($q) => $q->where('name', 'like', "%{$query->search}%"))
            ->when($query->isActive !== null, fn ($q) => $q->where('is_active', $query->isActive))
            ->orderBy($query->sortBy, $query->sortDirection)
            ->paginate($query->perPage, page: $query->page);
    }
}
```

`VideoListResource` (`Modules/Video/app/Features/VideoManagement/Http/Resources/VideoListResource.php`) — **contract JSON chính thức** giữa backend và Tabulator ở `admin/videos/index.blade.php`:

```php
namespace Modules\Video\Features\VideoManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'              => $this->uuid,
            'name'              => $this->name,
            'thumbnail_url'     => $this->thumbnail_url,
            'video_url'         => $this->video_url,
            'is_active'         => $this->is_active,
            'sort_order'        => $this->sort_order,
            'created_at'        => $this->created_at?->format('d/m/Y H:i'),
            'edit_url'          => route('backend.video.items.edit', $this->uuid),
            'delete_url'        => route('backend.video.items.destroy', $this->uuid),
            'toggle_active_url' => route('backend.video.items.toggle-active', $this->uuid),
        ];
    }
}
```

`VideoApiController::index()`:

```php
namespace Modules\Video\Features\VideoManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Video\Features\VideoManagement\Http\Resources\VideoListResource;
use Modules\Video\Features\VideoManagement\Queries\ListVideosForAdminHandler;
use Modules\Video\Features\VideoManagement\Queries\ListVideosForAdminQuery;

class VideoApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:video.manage');
    }

    public function index(Request $request, ListVideosForAdminHandler $handler): AnonymousResourceCollection
    {
        $query = new ListVideosForAdminQuery(
            search: $request->string('search')->toString() ?: null,
            isActive: $request->has('is_active') ? $request->boolean('is_active') : null,
            sortBy: $request->string('sort_by', 'sort_order')->toString(),
            sortDirection: $request->string('sort_direction', 'asc')->toString(),
            page: $request->integer('page', 1),
            perPage: $request->integer('per_page', 20),
        );

        return VideoListResource::collection($handler->handle($query));
    }
}
```

Response phân trang giữ nguyên format mặc định của `AnonymousResourceCollection` (`data` + `meta`/`links`) — Tabulator đọc `response.data` làm rows, `response.meta.last_page`/`total` cho phân trang server-side (cùng cách bảng Banner đang đọc, không cần đổi format riêng cho Video).

### 6.6 Giao diện quản trị

- **Trang danh sách** (`admin/videos/index.blade.php`): bảng Tabulator (cùng pattern Banner — dữ liệu nạp qua `backend.api.videos.items`), cột: thumbnail nhỏ (dùng `thumbnail_url`), Tên, `is_active` (toggle nhanh), `sort_order`, ngày tạo, thao tác Sửa/Xoá.
- **Form tạo/sửa** (`admin/videos/_form.blade.php`, dùng chung cho `create`/`edit`):
  - `name` — input text, bắt buộc.
  - `description` — `<textarea>` thuần (không cần rich-text/Jodit — mô tả video là văn bản ngắn, không có nhu cầu định dạng phức tạp).
  - `video_url` — input text (không phải `<input type="url">` để tránh browser tự chặn khi người dùng dán kèm khoảng trắng thừa), placeholder `https://www.youtube.com/watch?v=...`.
  - `embed_code` — `<textarea>`, placeholder gợi ý "Dán URL video hoặc mã nhúng (Share → Embed) từ YouTube", helper text giải thích hệ thống tự nhận diện ID.
  - `sort_order` — input number.
  - `is_active` — checkbox/toggle.
  - Không có upload ảnh nào trong form (khác Banner) — đúng quyết định §0.
  - **Preview khi sửa** (tuỳ chọn, không bắt buộc để hoàn thành spec): hiển thị `<img :src="thumbnailUrl">` cạnh form dựa trên `youtube_video_id` đã lưu, giúp người quản trị xác nhận đúng video trước khi lưu tiếp.

### 6.7 Permission

Thêm vào `app/Enums/PermissionEnum.php` (nhóm cùng các permission "Lớp B" như `BANNER_MANAGE`, `PAGE_MANAGE`):

```php
// ══ VIDEO (Lớp B — quản lý trực tiếp qua VideoPermissionSeeder, không qua config/permissions.php) ══
case VIDEO_MANAGE = 'video.manage';
```

`Modules/Video/database/seeders/VideoPermissionSeeder.php` (nguyên văn theo pattern `BannerPermissionSeeder`):

```php
namespace Modules\Video\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class VideoPermissionSeeder extends Seeder
{
    private const PERMISSIONS = ['video.manage'];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $ops = Role::where('name', 'platform_ops')->where('guard_name', 'web')->first();
        if ($ops) {
            $ops->givePermissionTo('video.manage');
        }

        $head = Role::where('name', 'platform_content_head')->where('guard_name', 'web')->first();
        if ($head) {
            $head->givePermissionTo('video.manage');
        }

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
```

`VideoDatabaseSeeder::run()` chỉ gọi `$this->call([VideoPermissionSeeder::class])`. Đăng ký vào `database/seeders/SystemDataSeeder.php` **sau** `ApprovalDatabaseSeeder::class` (role `platform_ops`/`platform_content_head` được tạo ở đó — cùng ràng buộc thứ tự đã áp dụng cho `BannerDatabaseSeeder`).

Sidebar (`resources/views/layouts/partials/sidebar.blade.php`, thêm 1 khối mới cạnh khối Banner):

```blade
@can(\App\Enums\PermissionEnum::VIDEO_MANAGE->value)
<div class="nav-group">
    <a href="{{ route('backend.video.items.index') }}" class="nav-link {{ request()->routeIs('backend.video.items.*') ? 'active' : '' }}">
        <svg class="nav-icon">...</svg>
        <span class="nav-label">Video</span>
    </a>
</div>
@endcan
```

### 6.8 `VideoPolicy`

```php
namespace Modules\Video\Policies;

use App\Enums\PermissionEnum;
use App\Models\User;
use Modules\Video\Models\Video;

class VideoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::VIDEO_MANAGE->value);
    }

    public function view(User $user, Video $video): bool
    {
        return $user->can(PermissionEnum::VIDEO_MANAGE->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::VIDEO_MANAGE->value);
    }

    public function update(User $user, Video $video): bool
    {
        return $user->can(PermissionEnum::VIDEO_MANAGE->value);
    }

    public function delete(User $user, Video $video): bool
    {
        return $user->can(PermissionEnum::VIDEO_MANAGE->value);
    }
}
```

Đăng ký tại `VideoServiceProvider::boot()`: `Gate::policy(Video::class, VideoPolicy::class);` (cùng cách `BannerServiceProvider` làm).

---

## 7. Render công khai

### 7.1 `VideoPublicController`

```php
namespace Modules\Video\Features\PublicReading\Http;

use Illuminate\View\View;
use Modules\Video\Models\Video;

class VideoPublicController
{
    public function index(): View
    {
        $videos = Video::active()->orderBy('sort_order')->paginate(config('video.per_page', 12));

        return view('video::public.index', compact('videos'));
    }
}
```

`per_page` đọc từ `config('video.per_page')` (thêm vào `Modules/Video/config/config.php`, §4.2) thay vì hard-code — cùng lý do mọi giá trị "có thể cần đổi mà không sửa code" khác trong dự án này đi qua config.

### 7.2 `video::public.index` — lưới + lightbox

```blade
@extends('layouts.frontend')

@section('content')
<div class="container mx-auto py-10">
    <h1 class="text-2xl font-bold mb-6">Thư viện Video</h1>

    @if($videos->isEmpty())
        {{-- Empty state — trước đây thiếu ở bản thảo đầu, để trắng trang khi chưa có video active nào dễ gây hiểu nhầm là trang lỗi. --}}
        <div class="text-center py-16 text-base-content/60">
            <p>Chưa có video nào được đăng.</p>
        </div>
    @else
    <div x-data="{ open: false, activeUrl: null, activeTitle: '' }"
         class="video-gallery grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($videos as $video)
        <button type="button"
                @click="open = true; activeUrl = '{{ $video->embed_url }}'; activeTitle = @js($video->name)"
                aria-haspopup="dialog"
                class="video-card text-left rounded-lg overflow-hidden border border-base-300 hover:shadow-lg transition">
            {{-- onerror: thumbnail YouTube trả 404 nếu video bị gỡ/riêng tư (§5.3/§9) — thay bằng ảnh placeholder tĩnh thay vì để icon ảnh vỡ. --}}
            <img src="{{ $video->thumbnail_url }}" alt="{{ $video->name }}" loading="lazy"
                 onerror="this.onerror=null; this.src='{{ asset('images/video-thumbnail-placeholder.png') }}';"
                 class="w-full aspect-video object-cover">
            <div class="p-4">
                <h3 class="font-semibold">{{ $video->name }}</h3>
                @if($video->description)
                <p class="text-sm text-base-content/70 mt-1">{{ Str::limit($video->description, 120) }}</p>
                @endif
            </div>
        </button>
        @endforeach
    </div>

    {{-- Modal đặt NGOÀI vòng lặp @foreach (chỉ 1 instance dùng chung, không phải 1 modal/video) — x-data ở trên bọc cả lưới lẫn modal nên state open/activeUrl chia sẻ được giữa 2 khối. --}}
    <div x-show="open" x-cloak
         role="dialog" aria-modal="true" :aria-label="activeTitle"
         x-trap.noscroll.inert="open"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/80"
         @keydown.escape.window="open = false">
        <div class="absolute inset-0" @click="open = false"></div>
        <div class="relative w-full max-w-3xl aspect-video mx-4">
            <button type="button" @click="open = false" aria-label="Đóng video"
                    class="absolute -top-10 right-0 text-white text-2xl leading-none">&times;</button>
            <template x-if="open">
                <iframe :src="activeUrl + '?autoplay=1'" :title="activeTitle"
                        class="w-full h-full rounded-lg"
                        allow="autoplay; encrypted-media; picture-in-picture"
                        referrerpolicy="strict-origin-when-cross-origin"
                        allowfullscreen></iframe>
            </template>
        </div>
    </div>

    <div class="mt-8">{{ $videos->links() }}</div>
    @endif
</div>
@endsection
```

**Vì sao KHÔNG phải XSS dù dùng `:src` (Alpine attribute binding) với dữ liệu có nguồn gốc từ input người quản trị:** `activeUrl` luôn được gán từ `{{ $video->embed_url }}` — accessor này (§4.1) dựng chuỗi `https://{domain}/embed/{youtube_video_id}` hoàn toàn từ `youtube_video_id` (11 ký tự đã qua whitelist regex ở §4.3), **không** nội suy bất kỳ phần nào của `embed_code` gốc. Alpine `:src` chỉ gán 1 chuỗi URL vào thuộc tính `src` của `<iframe>` — không có `x-html`/`v-html` nào render HTML thô ở đây.

**A11y cơ bản** (bổ sung sau review — bản thảo đầu chỉ có `@keydown.escape.window`, chưa đủ cho 1 modal đúng chuẩn): `role="dialog"` + `aria-modal="true"` + `aria-label` động theo tên video cho phần tử modal; `x-trap.noscroll.inert` khoá focus bên trong modal khi mở (focus trap) và khoá scroll nền, tự trả focus về nút đã bấm khi đóng; nút đóng có `aria-label="Đóng video"` vì chỉ chứa ký tự `&times;` không đọc được bằng screen reader.

**Dependency MỚI cần thêm — đã xác nhận CHƯA có trong bundle:** `x-trap` là directive của plugin chính thức **Alpine Focus** (`@alpinejs/focus`), không phải core Alpine. Đã kiểm tra `resources/js/app.js:38-39` — comment ở đó ghi rõ dự án **chủ động chưa cài** `@alpinejs/collapse/focus/persist` ("thêm sau nếu cần bằng `npm install @alpinejs/...`"). Đây chính là lúc "cần" đó xảy ra — bước triển khai (§8) phải gồm:
1. `npm install @alpinejs/focus`.
2. Trong `resources/js/app.js`, thêm `import focus from '@alpinejs/focus'` và `Alpine.plugin(focus)` **trước** `Alpine.start()`.

Nếu team muốn giữ tối giản, không thêm dependency mới: bỏ `x-trap.noscroll.inert`, giữ nguyên `@keydown.escape.window` (đã có ở bản thảo đầu) — chấp nhận đánh đổi không có focus trap/khoá scroll nền, vẫn đóng được bằng Esc và đủ dùng cho v1. Quyết định cụ thể (thêm plugin hay bỏ qua) nên chốt ở bước implement, không phải nghĩa vụ bắt buộc của spec này.

### 7.3 Nút "Xem trên YouTube" (tuỳ chọn thêm vào modal/card)

```blade
<a href="{{ $video->watch_url }}" target="_blank" rel="noopener noreferrer" class="text-sm underline">
    Xem trên YouTube
</a>
```

`watch_url` đã tự bảo đảm an toàn ở tầng Model (§4.1 — whitelist host, fallback nếu không khớp) nên Blade ở đây không cần thêm kiểm tra gì, chỉ việc `{{ }}` (tự escape) như bình thường.

### 7.4 CSP `img-src`/`frame-src` — BẮT BUỘC (site đã có CSP sẵn) + cân nhắc `sandbox` trên iframe

**v1.4 — sửa lại mức độ nghiêm trọng so với bản v1.0-1.3**: site này đã có sẵn `App\Http\Middleware\SecurityHeaders` gắn `Content-Security-Policy` cho **toàn bộ** nhóm middleware `web` (`bootstrap/app.php` — `appendToGroup('web', SecurityHeaders::class)`), áp dụng cho cả route công khai `/videos`. Đây **không phải** 1 lớp phòng thủ tuỳ chọn thêm sau — nếu không whitelist đúng domain, trình duyệt tự chặn cứng:

- **`img-src`** (trước v1.4: `'self' data: blob: https://api.dicebear.com`, KHÔNG có `i.ytimg.com`) → thumbnail video (`Video::thumbnail_url`, §4.1) bị chặn tải, hiện lỗi vỡ ảnh trên trang danh sách admin lẫn trang công khai.
- **`frame-src`** (trước v1.4: chỉ `https://challenges.cloudflare.com`, KHÔNG có domain YouTube nào) → iframe lightbox (`Video::embed_url`, §4.1/§7.2) sẽ bị chặn tương tự ngay khi bấm phát video, dù mọi thứ ở tầng Model/Action đều đúng.

**Đã sửa `app/Http/Middleware/SecurityHeaders.php::buildCsp()`**:
```php
"img-src 'self' data: blob: https://api.dicebear.com https://i.ytimg.com", // i.ytimg.com — thumbnail video YouTube (Modules/Video)
"frame-src https://challenges.cloudflare.com https://www.youtube-nocookie.com", // youtube-nocookie.com — lightbox video công khai (Modules/Video, xem config/video.php embed_domain)
```

Domain `frame-src` phải khớp **chính xác** `config('video.embed_domain')` (mặc định `www.youtube-nocookie.com`, §4.2) — nếu team đổi config sang `www.youtube.com` (bỏ chế độ privacy-enhanced), **phải đổi lại `frame-src` trong `SecurityHeaders.php` tương ứng**, nếu không lightbox sẽ bị CSP chặn ngay cả khi mọi thứ khác đã đúng.

- **`sandbox` trên `<iframe>`**: **KHÔNG khuyến nghị áp dụng cho iframe YouTube** — YouTube player cần `allow-scripts allow-same-origin allow-presentation allow-popups` để hoạt động đầy đủ (autoplay, fullscreen, mở tab quảng cáo liên kết...), thêm `sandbox` với danh sách quyền tối thiểu dễ làm player hỏng chức năng mà không tăng thêm bảo mật đáng kể (nguồn `src` đã bị khoá cứng vào domain YouTube qua accessor `embed_url`, không phải input tự do). Ghi nhận là điểm đã cân nhắc và **chủ động không áp dụng**, tránh việc reviewer sau này tưởng bị bỏ sót.

---

## 8. Kế hoạch triển khai (phases)

1. **Migration + Model + config** — bảng `videos` (§3.2, `embed_code` nullable), `Video` model kèm accessors `thumbnail_url`/`embed_url`/`watch_url` (§4.1, `watch_url` có whitelist domain), `config/video.php` (§4.2).
2. **`ResolveYoutubeVideoIdAction`** (§4.3) — viết **TRƯỚC** khi ghép vào Action Create/Update, kèm đầy đủ unit test theo đúng bảng đối kháng ở §4.3 (hợp lệ, nhiễu xung quanh, ID trần, HTML/script injection, domain giả mạo, tham số `v=` giả, rỗng/thiếu) — đây là phần logic quan trọng nhất của cả module (§0), phải tự tin 100% trước khi cho phép ghi DB. **Đã thực hiện (v1.3)**: `Modules/Video/tests/Feature/ResolveYoutubeVideoIdActionTest.php` (34 test case, đăng ký trong `phpunit.xml` testsuite Feature) — phát hiện và sửa 2 lỗi thật trong regex trước khi ghép vào Action (xem changelog v1.3 đầu file), đúng như mục đích bước này đặt ra.
3. **`VideoPermissionSeeder`** + đăng ký vào `SystemDataSeeder` (sau `ApprovalDatabaseSeeder`, §6.7).
4. **Admin CRUD** — `CreateVideoAction`/`UpdateVideoAction` (có validate whitelist `video_url`, §5.2)/`DeleteVideoAction`/`ToggleVideoActiveAction`, `VideoData`, `VideoAdminController`, `VideoPolicy`, views `admin/videos/*` (§6.1-§6.4, §6.6, §6.8).
5. **API contract** — `ListVideosForAdminQuery`/`Handler`, `VideoListResource`, `VideoApiController::index()` (§6.5) — làm cùng lúc hoặc trước bước 4 để frontend (Tabulator) và backend làm song song được.
6. **Trang công khai** — `VideoPublicController` + `video::public.index` (lưới + lightbox, kèm empty state/fallback thumbnail lỗi/a11y cơ bản, §7), route `video.index`. Nếu chọn dùng `x-trap` (§7.2): chạy `npm install @alpinejs/focus` + đăng ký `Alpine.plugin(focus)` trong `resources/js/app.js` TRƯỚC bước này.
7. **Sidebar entry** (§6.7) + đăng ký module (`modules_statuses.json` thêm `"Video": true`).
8. **(Tuỳ chọn) Demo seeder** — 2-3 video mẫu để QA ngay được cả trang công khai lẫn thumbnail/lightbox mà không cần tự tay tìm URL YouTube, dùng ID video công khai lâu năm, không đại diện/liên quan tổ chức nào trong dự án (cùng tinh thần tránh dùng tài sản của 1 bên thứ ba ngụ ý liên kết — xem `Banner_Management_Technical_Specification.md` §0 hàng "Ảnh banner do ai/đâu cấp"):

   | `youtube_video_id` | Ghi chú |
   |---|---|
   | `jNQXAC9IVRw` | "Me at the zoo" — video đầu tiên từng đăng lên YouTube, luôn công khai, thường dùng làm ví dụ kỹ thuật |
   | `dQw4w9WgXcQ` | Video có độ nhận diện cao nhất, hay dùng làm ID mẫu trong tài liệu kỹ thuật để QA thumbnail/embed |
   | `aqz-KE-bpKQ` | "Big Buck Bunny" trailer — phim hoạt hình mã nguồn mở (Blender Foundation), an toàn về bản quyền |

   `VideoDatabaseSeeder` chạy thủ công (`php artisan db:seed --class=...`), **KHÔNG** tự động trong `SystemDataSeeder` — cùng lý do `OrganizationDemoSeeder`/`PostReviewDemoSeeder` không tự chạy.

---

## 9. Ngoài phạm vi (out of scope) — ghi rõ để tránh hiểu nhầm khi review

- **Nhúng video như 1 loại content block trong `Modules/Post`** — cần thêm case mới vào `ContentBlockType` và sửa `ArticleContentRenderer`, thuộc phạm vi 1 spec riêng nếu có nhu cầu sau này (§0).
- **Trang chi tiết riêng từng video** (`/videos/{video}`, có slug/SEO/related videos) — v1 chỉ có 1 trang lưới + lightbox (§0).
- **Kéo-thả sắp xếp (drag-and-drop reorder)** — v1 chỉ có input số `sort_order` nhập tay (§0/§5.4).
- **Ràng buộc chống trùng `youtube_video_id`** — không có `unique` constraint, không cảnh báo khi tạo trùng (§0).
- **Xử lý video bị gỡ/riêng tư trên YouTube** (kiểm tra tồn tại qua YouTube Data API, tự ẩn nếu video die) — v1 không gọi API bên ngoài nào, thumbnail 404 tự nhiên nếu video không còn tồn tại (§5.3).
- **Hỗ trợ nền tảng video khác** (Vimeo, TikTok, file MP4 tự host...) — v1 chỉ hỗ trợ YouTube, tên trường/logic (`youtube_video_id`, regex ở §4.3) gắn chặt với YouTube.
- **Đếm lượt xem/click** (khác Banner có `click_count`) — không có yêu cầu đo lường nào ở v1.
- **Danh mục/gắn thẻ video** (phân loại video theo chủ đề) — v1 là 1 danh sách phẳng duy nhất, không phân trang theo category.
- **Rate-limit/chống spam khi tạo video** — CRUD chỉ dành cho tài khoản nội bộ đã có `video.manage` (không phải form công khai nhận submit từ khách), severity thấp nên không thêm throttle riêng ở v1; có thể bọc `throttle` middleware sau nếu phát sinh nhu cầu thật.
- **Giao diện khôi phục bản ghi đã xoá mềm (soft-delete restore UI)** — cùng quyết định như `Banner` (không có trang "thùng rác"/khôi phục riêng) — muốn khôi phục phải thao tác trực tiếp qua DB/tinker ở v1.
