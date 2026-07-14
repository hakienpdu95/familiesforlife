# Đặc Tả Kỹ Thuật — Module Quản Lý Sự Kiện (Event Management)

**Nguồn:** `spec/event.png` — form "Submit Event" của HoneyKids (Honeycombers network), chụp lại giao diện public cho phép độc giả tự nộp sự kiện lên cổng thông tin.
**Ngày soạn:** 14/07/2026
**Trạng thái:** Phase 1 đã triển khai (Migration/Model/Enum/Policy/Permission) + phần "staff tự nhập sự kiện" của Phase 2 (`EventCategoryManagement` CRUD, `Event` create/update qua dashboard — xem §13). Còn lại của Phase 2 (`PublicSubmission`: form public, Turnstile, rate limit) và Approve/Reject/Publish/Archive chưa làm.
**Tài liệu liên quan:** `spec/PublishingEngine_Technical_Specification.md` (Post), `spec/Platform_RBAC_Phase2_Specification.md` (vai trò nền tảng), `spec/Workflow_Approval_Technical_Specification.md` (module `Modules/Approval` — lý do KHÔNG dùng cho Event, xem §3.2).

---

## 1. Mục đích & bối cảnh

Trang chủ cổng thông tin công khai (`Modules/Post/resources/views/public/home.blade.php`) hiện có khối **"Đối Tác Đồng Hành"** (`x-frontend.sponsor-spotlight`) — đây là **giải pháp tạm** dùng cơ chế bài viết tài trợ (`post_articles.is_sponsored`) để lấp chỗ cho khối "Sự Kiện Cho Bé" của bản mẫu tĩnh `spec/honeykids/honeykids-home.html`, vì tại thời điểm đó **hệ thống chưa có domain "sự kiện" nào**. Đặc tả này thiết kế domain đó cho thật, để:

1. Độc giả công khai (không cần tài khoản) tự nộp sự kiện qua form (`spec/event.png`).
2. Nhân sự toà soạn (vai trò đã có sẵn — xem §3.1) duyệt/từ chối/xuất bản.
3. Cổng thông tin hiển thị danh sách/chi tiết sự kiện thật, thay `x-frontend.sponsor-spotlight` bằng dữ liệu Event thật (§12).

### 1.1 Các trường trong form nguồn (`spec/event.png`)

| Trường | Bắt buộc | Ghi chú từ UI |
|---|---|---|
| Event Title | ✓ | "Do not use all caps" |
| Event Title (Short Version) | ✓ | Tối đa 55 ký tự, có bộ đếm ký tự |
| Event Description | ✓ | "Do not add any links, or else we'll think you're a spammer!" |
| Start Date / End Date | ✓ | date picker |
| Start Time / End Time | ✓ | HH / MM / AM-PM |
| Location | ✓ | radio: Physical / Online. **Physical** → hiện thêm: chọn **Tỉnh/Thành phố** + **Phường/Xã** (cascading select), **Venue Name\*** , **Venue Address\*** |
| Website | ✓ | link mua vé/đăng ký, hoặc website chính thức |
| Price | ✓ | radio: Free / Single Price / Price Range. **Single Price** → hiện thêm input **Single Price\***. **Price Range** → hiện thêm 2 input **Từ\*** / **Đến\*** |
| Category | ✓ | dropdown 1 lựa chọn (vd "Arts, Exhibits & Culture") |
| Poster | ✓ | JPG/PNG ≤ 1MB, khuyến nghị 1400×1000 (landscape) |
| Your Name | ✓ | First / Last |
| Your E-mail Address | ✓ | "sẽ KHÔNG hiển thị công khai ở bất kỳ đâu" |
| Consent | ✓ | checkbox đồng ý nhận bản tin |
| CAPTCHA | ✓ | reCAPTCHA (Google) |

Ghi chú quan trọng cho thiết kế DB: **email người nộp tuyệt đối không hiển thị công khai** → bắt buộc phải tách bảng PII khỏi bảng nội dung công khai (§5.3), không thể gộp chung 1 bảng rồi "chỉ không SELECT cột đó" (rủi ro rò rỉ nếu sau này có ai `SELECT *` hoặc `toArray()` nhầm).

---

## 2. So sánh với codebase hiện tại (Gap Analysis)

| Hạ tầng cần | Đã có sẵn? | Vị trí | Quyết định |
|---|---|---|---|
| Module riêng cho content type mới | ✗ | — | Tạo `Modules/Event` mới (§3.2) |
| Cây danh mục (category tree, root+children, active/sort_order) | ✓ | `Modules/Post/app/Models/PostCategory.php` | Nhân bản pattern (KHÔNG dùng chung bảng — §3.3) |
| Enum trạng thái vòng đời + `canTransitionTo()` validate ở tầng Action | ✓ | `Modules\Post\Enums\TranslationStatus` | Nhân bản pattern cho `EventStatus` (§6) |
| Engine duyệt nội dung dùng chung (polymorphic) | ✓ nhưng KHÔNG phù hợp | `Modules/Approval` (`HasApproval`, `ApprovalSubject`) | Không dùng — `approval_subjects.organization_id` là `NOT NULL` (module này thiết kế cho nội dung **thuộc về 1 tổ chức**, vd Product; Event giống Post — tài sản nền tảng, không tổ chức nào sở hữu) — xem §3.2 |
| Vai trò nền tảng duyệt nội dung (không theo tổ chức) | ✓ | `platform_content_editor`, `platform_content_head` (`app/Enums/RoleEnum.php` + `Platform_RBAC_Phase2_Specification.md`) | Tái dùng nguyên, không tạo role mới (§9) |
| Xác thực CAPTCHA cho form public | ✓ | `Modules\Auth\Fortify\ValidateTurnstile` (Cloudflare Turnstile, gói `ryangjchandler/laravel-cloudflare-turnstile`, đã có trong `composer.json`) | Tái dùng pattern **bản đơn giản** (1 site key toàn cục), KHÔNG dùng bản multi-site của `Modules\Survey\Http\Middleware\ValidateSurveyTurnstile` (Event chỉ có 1 form, không cần nhúng nhiều domain) — thay Google reCAPTCHA trong ảnh gốc vì hệ thống đã tích hợp sẵn Turnstile, không cần thêm dependency mới |
| Rate limit cho endpoint submit công khai | ✓ (pattern) | `Modules\Survey\Providers\RouteServiceProvider` (`RateLimiter::for('survey-submit', ...)`) | Nhân bản: `RateLimiter::for('event-submit', ...)` |
| Upload ảnh có kiểm soát kích thước/định dạng | ✓ (2 pattern khác nhau) | `app/Services/Media/MediaUploadService.php` (Spatie MediaLibrary, dùng cho entity có login — vd `Organization`) VÀ `Modules\Post\Models\PostArticle.cover_image_url` (cột string đơn giản) | Dùng pattern **cột string đơn giản** như Post cho `events.poster_path` (form public không có phiên đăng nhập để gắn FilePond draft context) — xem §5.2, §10.4 |
| Job nền tự động hết hạn nội dung theo ngày | ✓ (pattern) | `Modules\Post\Jobs\ExpireSponsoredArticlesJob` | Nhân bản: `ExpirePastEventsJob` (§11.1) |
| `organization_id` trên nội dung công khai xuyên nền tảng | ✗ (đã bị DROP khỏi Post — v3.0) | `Platform_RBAC_Phase2_Specification.md §3.3` | Event **không có cột `organization_id`** ngay từ đầu — không lặp lại việc phải thêm-rồi-drop như Post đã trải qua |
| Dữ liệu hành chính VN (Tỉnh/Thành, Phường/Xã) + UI chọn cascading | ✓ | `app/Models/Province.php`, `app/Models/Ward.php` (bảng dùng chung toàn hệ thống, **không có `organization_id`** — khớp thẳng nhu cầu nội dung nền tảng của Event), component `<x-address-picker>` (`app/View/Components/AddressPicker.php`), endpoint `GET /api/provinces/{provinceCode}/wards` (`Modules\Organization\Actions\GetWardsByProvinceAction` — route đã đánh dấu **"public — không cần auth"**, gọi được thẳng từ form Event dù không đăng nhập) | Tái dùng nguyên component + endpoint (§5.2, §10.6) — không tự chế lại `city`/`postal_code` tự do như bản nháp đầu |

**Kết luận kiến trúc:** Event là **module Nwidart mới, độc lập, tài sản nền tảng** (giống Post), tái dùng tối đa pattern đã có (category tree, status enum, platform role, Turnstile, rate limiter, expire job) nhưng **không dùng chung bảng/engine** với Post hay Approval — lý do chi tiết ở §3.

---

## 3. Quyết định kiến trúc

### 3.1 Vai trò xử lý — tái dùng nguyên, không tạo role mới

Theo đúng mô hình đã áp dụng cho Post (`Platform_RBAC_Phase2_Specification.md §3`):

- **`platform_content_creator`**: không cần cho Event — người "viết" là độc giả ẩn danh qua form public, không phải nhân sự nội bộ.
- **`platform_content_editor`**: sơ duyệt — xem hàng chờ (`Submitted`), Approve/Reject.
- **`platform_content_head`**: duyệt cuối + xuất bản (`Approved → Published`), có toàn quyền editor cộng thêm.
- **`platform_ops`**: nhận thông báo vận hành (job hết hạn — giống cách Post báo `ExpireSponsoredArticlesJob`).

Không cần "Điều phối viên nộp sự kiện" hay role mới nào khác — quy mô nghiệp vụ (duyệt 1 loại nội dung đơn giản hơn bài viết) không đủ lớn để tách thêm tầng vai trò, tránh lặp lại vướng mắc §2.4 của `RBAC_NewsPortal_Gap_Analysis.md` (càng nhiều role càng khó chứng minh khác biệt thật).

### 3.2 Vì sao không dùng `Modules/Approval` (`HasApproval`)

`Modules/Approval` là engine duyệt nội dung polymorphic dùng chung — nhưng theo đúng thiết kế hiện tại của nó (`approval_subjects.organization_id` là **`constrained()->restrictOnDelete()`**, tức bắt buộc NOT NULL trỏ vào 1 `Organization` có thật), nó được xây **cho nội dung thuộc về 1 tổ chức khách hàng** (ví dụ điển hình trong code: Product). Event — giống Post — là tài sản của nền tảng, không tổ chức nào sở hữu, không có `organization_id` nào hợp lệ để gán. Ép dùng `HasApproval` sẽ tái diễn đúng vấn đề Post từng gặp và đã sửa (drop `organization_id` khỏi `post_articles` — `Platform_RBAC_Phase2_Specification.md §3.3 v3.0`), chỉ khác là lần này phát hiện TRƯỚC khi code thay vì sau.

→ Event tự xây state machine riêng (`EventStatus`, §6), theo đúng pattern `TranslationStatus` của Post — đã chứng minh hoạt động tốt cho đúng use-case "nội dung nền tảng, không tổ chức nào sở hữu".

### 3.3 Vì sao `event_categories` là bảng riêng, không dùng chung `post_categories`

Cả hai đều là "cây danh mục nội dung nền tảng" về mặt kỹ thuật, nhưng khác nhau về nghiệp vụ:

- `post_categories` phục vụ điều hướng bài viết (Nuôi Dạy Con, Trường Học...), do biên tập viên phụ trách theo `post_category_editors`.
- Category của Event (theo form nguồn: "Arts, Exhibits & Culture", "Classes & Camps"...) mô tả **loại hình hoạt động** — dùng để lọc lịch sự kiện, hiển thị icon/màu trên calendar UI (nhu cầu khác thuần navigation).

Trộn chung sẽ buộc mọi thay đổi danh mục bài viết ảnh hưởng luôn tới lọc sự kiện (và ngược lại) dù 2 nhu cầu hiển thị khác nhau. Bảng riêng, **nhân bản cấu trúc** `PostCategory` (root/children, `is_active`, `sort_order`) để nhất quán cách quản trị, nhưng độc lập dữ liệu. Nếu tương lai phát sinh nhu cầu "1 bài viết gắn với 1 sự kiện" (vd bài PR cho 1 hội chợ), dùng bảng liên kết riêng (`event_id` nullable trên `post_articles`, hoặc bảng pivot) — không phải lý do để gộp category.

### 3.4 Slug & routing

Theo đúng pattern Post: model dùng `uuid` làm route key cho route quản trị (`dashboard/events/{event}`), `slug` cho route công khai — resolve thủ công trong controller (không dùng implicit binding), tránh đúng bug Post đã ghi chú (`PublicArticleController` — implicit binding thất bại ném `ModelNotFoundException` giữa middleware pipeline, không có cơ hội chạy fallback).

---

## 4. Sơ đồ quan hệ (ERD tổng quan)

```
event_categories (1) ───────< (N) events
                                   │ 1:1
                                   ▼
                            event_submissions   (PII người nộp — KHÔNG public)
                                   │
                                   │ N:1 (nullable — chỉ khi staff tự tạo, không qua public form)
                                   ▼
                                users (created_by/updated_by/approved_by/rejected_by)
```

- `events` — bảng nội dung công khai (những gì hiển thị lên cổng thông tin).
- `event_submissions` — bảng riêng chứa thông tin người nộp (PII), quan hệ 1:1 với `events`, **KHÔNG bao giờ join/expose ra route công khai**.
- `event_categories` — cây danh mục, độc lập `post_categories` (§3.3).

---

## 5. Thiết kế bảng dữ liệu

### 5.1 `event_categories`

```php
Schema::create('event_categories', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('parent_id')->nullable()->constrained('event_categories')->nullOnDelete();
    $table->string('name', 100);
    $table->string('slug', 120)->unique();
    $table->string('icon', 50)->nullable();      // icon hiển thị trên calendar/filter chip
    $table->string('color_hex', 7)->nullable();  // màu chip/badge, đồng bộ UI danh mục
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['parent_id', 'sort_order']);
    $table->index(['is_active', 'sort_order']);
});
```

Danh mục gợi ý khởi tạo (theo đúng "Đi Chơi Đâu" của nav cổng thông tin — xem §12): Nghệ Thuật & Triển Lãm, Lớp Học & Trại Hè, Khu Vui Chơi Trong Nhà, Hoạt Động Ngoài Trời, Ngày Lễ, Hội Chợ Gia Đình.

### 5.2 `events`

```php
Schema::create('events', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();               // route key cho dashboard/events/{event}
    $table->foreignId('category_id')->constrained('event_categories')->restrictOnDelete();

    // ── Nội dung ─────────────────────────────────────────────────────
    $table->string('title', 150);
    $table->string('short_title', 55);             // đúng giới hạn 55 ký tự của form nguồn
    $table->string('slug', 180)->unique();
    $table->text('description');

    // ── Thời gian ────────────────────────────────────────────────────
    $table->date('start_date');
    $table->date('end_date');
    $table->time('start_time')->nullable();        // nullable = sự kiện cả ngày / không cố định giờ
    $table->time('end_time')->nullable();

    // ── Địa điểm ─────────────────────────────────────────────────────
    // location_type=physical: dùng đúng pattern địa chỉ VN đã có (Customer/Organization/Lead) —
    // <x-address-picker> + provinces/wards (§2, §10.6) thay vì tự chế city/postal_code tự do.
    $table->string('location_type', 10);           // EventLocationType: physical | online
    $table->string('venue_name', 150)->nullable();     // "Venue Name*" — bắt buộc khi physical
    $table->string('venue_address', 255)->nullable();  // "Venue Address*" — số nhà/tên đường, bắt buộc khi physical
    $table->char('province_code', 2)->nullable();
    $table->foreign('province_code')->references('province_code')->on('provinces')->nullOnDelete();
    $table->string('province_name', 255)->nullable(); // denormalized tại thời điểm chọn — cùng lý do Customer đã làm: hiển thị không cần join, ổn định nếu địa giới hành chính đổi tên sau này
    $table->char('ward_code', 5)->nullable();
    $table->foreign('ward_code')->references('ward_code')->on('wards')->nullOnDelete();
    $table->string('ward_name', 255)->nullable();      // denormalized, cùng lý do trên
    $table->string('full_address', 500)->nullable();   // chuỗi hiển thị dựng sẵn: venue_address, ward_name, province_name — tránh phải concat lại mỗi nơi render
    $table->decimal('latitude', 10, 7)->nullable();    // dành cho tích hợp bản đồ (Phase 4 — §13)
    $table->decimal('longitude', 10, 7)->nullable();
    $table->string('online_url', 500)->nullable();     // bắt buộc khi online — link Zoom/Meet/livestream

    // ── Vé / giá & liên kết chính thức ───────────────────────────────
    $table->string('website_url', 500);            // bắt buộc luôn — link vé/đăng ký hoặc site chính thức
    $table->string('price_type', 10);               // EventPriceType: free | single | range
    $table->decimal('price_amount', 10, 2)->nullable(); // dùng khi single
    $table->decimal('price_min', 10, 2)->nullable();    // dùng khi range
    $table->decimal('price_max', 10, 2)->nullable();
    // KHÔNG có cột currency — đã xác nhận VND cố định, không đa tiền tệ (§14.1). Cột luôn
    // mang đúng 1 giá trị không phải cột (YAGNI) — "₫" hard-code ở tầng hiển thị (Blade),
    // thêm cột lại nếu sau này thật sự cần đa tiền tệ.

    // ── Poster ───────────────────────────────────────────────────────
    // Cột string đơn giản (không Spatie MediaLibrary) — lý do §2 hàng "Upload ảnh".
    $table->string('poster_path', 255);
    $table->string('poster_alt', 150)->nullable();      // SEO/accessibility — alt text, không bắt buộc (fallback = title)
    $table->unsignedInteger('poster_width')->nullable();
    $table->unsignedInteger('poster_height')->nullable();
    $table->unsignedInteger('poster_size_bytes')->nullable();

    // ── Vòng đời & kiểm duyệt (§6) ───────────────────────────────────
    $table->string('status', 20)->default('submitted'); // EventStatus
    $table->boolean('is_featured')->default(false);      // ghim hero, giống post_articles.is_featured
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('approved_at')->nullable();
    $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('rejected_at')->nullable();
    $table->string('rejected_reason', 255)->nullable();
    $table->timestamp('published_at')->nullable();

    // ── Audit ────────────────────────────────────────────────────────
    // Nullable — bài do độc giả nộp qua form public không có created_by (không đăng nhập).
    // Chỉ set khi staff tự tạo sự kiện trực tiếp trong dashboard (không qua public form).
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

    $table->timestamps();
    $table->softDeletes();

    // ── Index phục vụ query thật ─────────────────────────────────────
    $table->index(['status', 'start_date']);                 // hàng chờ duyệt theo thời gian
    $table->index(['status', 'end_date']);                    // ExpirePastEventsJob quét theo end_date
    $table->index(['category_id', 'status', 'start_date']);   // lọc theo danh mục ở trang danh sách công khai
    $table->index(['status', 'is_featured', 'start_date']);   // query hero trang chủ — status trước vì luôn lọc published trước tiên
    $table->index(['province_code', 'status', 'start_date']); // lọc "sự kiện gần đây theo tỉnh/thành"
});

// MySQL 8.0.16+ — CHECK constraint (Schema Builder không có API cho CHECK động theo cột
// khác nhau tuỳ enum, dùng raw statement). Đây là lớp phòng thủ THỨ HAI (defense-in-depth),
// KHÔNG thay thế validate ở FormRequest/Action (§5.5) — chỉ chặn dữ liệu bẩn nếu tầng Action
// bị bypass (import tay, sửa trực tiếp DB, bug ở code sau này).
DB::statement("
    ALTER TABLE events ADD CONSTRAINT chk_events_physical_fields CHECK (
        location_type <> 'physical'
        OR (venue_name IS NOT NULL AND venue_address IS NOT NULL AND province_code IS NOT NULL)
    )
");
DB::statement("
    ALTER TABLE events ADD CONSTRAINT chk_events_price_range CHECK (
        price_type <> 'range' OR (price_min IS NOT NULL AND price_max IS NOT NULL AND price_max >= price_min)
    )
");
```

**Không có cột `organization_id`** — quyết định có chủ đích, xem §3.2/§2.

**Chính sách xoá danh mục:** `category_id` dùng `restrictOnDelete()` — không xoá được 1 `event_category` còn `events` tham chiếu (kể cả đã soft-delete, vì FK không phân biệt soft-delete). Admin phải chuyển hết sự kiện sang danh mục khác trước khi xoá/deactivate danh mục gốc. Đây là lựa chọn có chủ đích: từ chối thao tác rõ ràng (lỗi FK) tốt hơn xoá âm thầm/để `category_id` mồ côi.

### 5.3 `event_submissions` — PII người nộp, tách biệt hoàn toàn

```php
Schema::create('event_submissions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('event_id')->unique()->constrained('events')->cascadeOnDelete(); // 1:1

    $table->string('submitter_first_name', 100);
    $table->string('submitter_last_name', 100);
    $table->string('submitter_email', 255);

    $table->boolean('newsletter_consent')->default(false);
    $table->timestamp('consented_at')->nullable();

    // Nguồn gốc bản ghi — phân biệt độc giả tự nộp vs staff tạo thẳng trong dashboard
    // (staff tạo thẳng thì bảng này có thể không tồn tại — quan hệ optional 1:1, xem §5.2).
    $table->string('source', 20)->default('public_form'); // public_form | admin

    // Dấu vết chống spam/audit — KHÔNG dùng để hiển thị, chỉ phục vụ điều tra lạm dụng.
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->boolean('turnstile_verified')->default(false);

    $table->timestamps();

    $table->index('submitter_email'); // dò lạm dụng: 1 email nộp quá nhiều sự kiện/khoảng thời gian
    $table->index('ip_address');
});
```

**Nguyên tắc bắt buộc:** không route/query công khai nào được `with('submission')` hay select bất kỳ cột nào của bảng này. Model `EventSubmission` không có `resource`/`toArray()` public nào — chỉ dùng nội bộ ở `Features/EventModeration` (dashboard).

### 5.4 Enums

```php
namespace Modules\Event\Enums;

enum EventLocationType: string
{
    case Physical = 'physical';
    case Online   = 'online';
}

enum EventPriceType: string
{
    case Free   = 'free';
    case Single = 'single';
    case Range  = 'range';
}

enum EventStatus: string
{
    case Submitted = 'submitted'; // vừa nộp qua form public, chờ platform_content_editor sơ duyệt
    case Approved  = 'approved';  // editor duyệt xong, chờ platform_content_head xuất bản
    case Published = 'published'; // hiển thị công khai
    case Rejected  = 'rejected';  // terminal — độc giả không có tài khoản để sửa & nộp lại, phải nộp submission mới
    case Expired   = 'expired';   // tự động chuyển bởi ExpirePastEventsJob khi end_date đã qua (§11.1)
    case Archived  = 'archived';  // staff gỡ thủ công (spam phát hiện sau publish, trùng lặp, vi phạm chính sách)

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Submitted => in_array($target, [self::Approved, self::Rejected], true),
            self::Approved  => in_array($target, [self::Published, self::Rejected, self::Archived], true),
            self::Published => in_array($target, [self::Expired, self::Archived], true),
            self::Rejected, self::Expired, self::Archived => false,
        };
    }
}
```

Validate transition **ở tầng Action** (`abort_unless($event->status->canTransitionTo($target))`), không chỉ ở UI — đúng nguyên tắc đã áp dụng cho `TranslationStatus`/`ApprovalStatus`.

### 5.5 Ma trận điều kiện theo `location_type` / `price_type`

Không ràng buộc được ở tầng DB (SQLite/MySQL không có CHECK constraint linh hoạt theo enum khác cột dễ bảo trì) — validate ở `FormRequest`/Action:

| `location_type` | Bắt buộc | Không dùng |
|---|---|---|
| `physical` | `venue_name` ("Venue Name\*"), `venue_address` ("Venue Address\*"), `province_code` + `province_name`, `ward_code` + `ward_name` (chọn qua `<x-address-picker>` — §10.6) | `online_url` |
| `online` | `online_url` | `venue_name`, `venue_address`, `province_code`, `ward_code`, `latitude`, `longitude` |

`full_address` luôn tự dựng ở tầng Action khi `location_type=physical` (không nhập tay): `"{$venue_address}, {$ward_name}, {$province_name}"` — cùng nguyên tắc denormalize đã dùng cho `Customer`.

| `price_type` | Bắt buộc trên form | Cột lưu | Không dùng |
|---|---|---|---|
| `free` | — | — | `price_amount`, `price_min`, `price_max` |
| `single` | input **"Single Price\*"** | `price_amount` | `price_min`, `price_max` |
| `range` | 2 input **"Từ\*" / "Đến\*"** | `price_min` / `price_max` (`price_max >= price_min`) | `price_amount` |

---

## 6. Vòng đời & luồng duyệt

```
                 ┌────────────┐
   form public → │ Submitted  │
                 └─────┬──────┘
                       │ platform_content_editor: Approve / Reject
             ┌─────────┴─────────┐
             ▼                   ▼
       ┌───────────┐       ┌───────────┐
       │ Approved  │       │ Rejected  │  (terminal)
       └─────┬─────┘       └───────────┘
             │ platform_content_head: Publish
             ▼
       ┌───────────┐
       │ Published │
       └─────┬─────┘
             │ ExpirePastEventsJob (tự động, end_date < today)
             ▼
       ┌───────────┐
       │ Expired   │
       └─────┬─────┘
             │ staff: Archive (thủ công, dọn dẹp)
             ▼
       ┌───────────┐
       │ Archived  │  (terminal)
       └───────────┘
```

`Approved`/`Published` đều có thể `Archived` thủ công bất kỳ lúc nào (spam phát hiện muộn, vi phạm chính sách) — xem ma trận `canTransitionTo()` ở §5.4.

### 6.1 `platform_content_editor` được sửa nội dung trước khi Approve

**Quyết định:** có — độc giả nộp qua form public thường viết hoa toàn bộ tiêu đề, sai chính tả, hoặc thiếu thông tin (venue chưa rõ, mô tả cụt). Vì submission là ẩn danh (không tài khoản để tự sửa & nộp lại — §5.4, `Rejected` là terminal), buộc `platform_content_editor` phải có khả năng chuẩn hoá nội dung TRƯỚC khi Approve thay vì chỉ Approve/Reject nguyên trạng.

- `UpdateEventAction` (sửa `title`, `short_title`, `description`, địa điểm, giá...) và `ApproveEventAction` (chuyển `Submitted → Approved`) là **2 action tách biệt** — không gộp chung 1 form-submit duy nhất:
  - Cho phép sửa nhiều lần trong lúc còn `Submitted` mà không đổi status.
  - `ApproveEventAction` không nhận input nội dung nào — chỉ đổi status + ghi `approved_by`/`approved_at`, giữ đúng nguyên tắc tách "sửa nội dung" khỏi "quyết định duyệt" (cùng tinh thần `HasApproval::approvalWatchedAttributes()` của `Modules/Approval`, dù Event không dùng chung engine đó — xem §3.2).
- `UpdateEventAction` cho phép gọi khi `status` ∈ {`Submitted`, `Approved`} (sửa tiếp sau sơ duyệt nếu `platform_content_head` phát hiện vấn đề trước khi Publish); **không** cho phép khi `Published` trở đi — sự kiện đã công khai muốn sửa nội dung phải qua quy trình khác (ngoài phạm vi đặc tả này, đề xuất: `platform_content_head` tự sửa trực tiếp qua `EventAdminController::update()` thông thường, không qua lại vòng duyệt).
- Quyền: `platform_content_editor` cần thêm `EVENT_EDIT` (mới, §9) — tách khỏi `EVENT_MODERATE` (quyết định Approve/Reject), đúng triết lý RBAC đã áp dụng cho Post (`post_article.edit` tách khỏi `post_article.publish`).

---

## 7. Cấu trúc module & feature folder

Theo đúng convention `Modules/Post` (CQRS-nhẹ: `Actions/`, `Queries/`, `Http/`, `Data/` theo từng feature):

```
Modules/Event/
├── app/
│   ├── Enums/ (EventStatus, EventLocationType, EventPriceType)
│   ├── Models/ (Event, EventCategory, EventSubmission)
│   ├── Policies/ (EventPolicy, EventCategoryPolicy)
│   ├── Features/
│   │   ├── PublicSubmission/
│   │   │   ├── Http/EventSubmissionController.php       // GET form, POST submit
│   │   │   ├── Actions/SubmitEventAction.php             // tạo Event + EventSubmission trong 1 transaction
│   │   │   ├── Actions/StorePosterAction.php              // validate + lưu file, trả poster_path/width/height
│   │   │   └── Http/Middleware/ValidateEventTurnstile.php // bản đơn giản, xem §2/§10.1
│   │   ├── EventModeration/           (mirror ArticleAuthoring)
│   │   │   ├── Http/EventAdminController.php
│   │   │   ├── Actions/UpdateEventAction.php               // sửa nội dung — tách khỏi Approve, xem §6.1
│   │   │   ├── Actions/ApproveEventAction.php
│   │   │   ├── Actions/RejectEventAction.php
│   │   │   ├── Actions/PublishEventAction.php
│   │   │   ├── Actions/ArchiveEventAction.php
│   │   │   └── Queries/ListPendingEventsHandler.php + Query
│   │   ├── EventCategoryManagement/    (mirror CategoryManagement)
│   │   │   ├── Http/EventCategoryAdminController.php
│   │   │   └── Actions/{Create,Update,Delete,Reorder}CategoryAction.php
│   │   └── PublicReading/               (mirror Post's PublicReading)
│   │       ├── Http/PublicEventController.php   // index (danh sách+lọc), show (chi tiết)
│   │       ├── Http/EventSitemapController.php  // event-sitemap.xml, mirror SitemapController của Post
│   │       └── Queries/ListPublishedEventsHandler.php + Query
│   ├── Jobs/ExpirePastEventsJob.php
│   └── Providers/ (EventServiceProvider, RouteServiceProvider)
├── database/migrations/
├── resources/views/
│   ├── admin/ (events/*, categories/*)
│   └── public/ (index, category, show, submit-form, submit-success)
└── routes/{web,api}.php
```

---

## 8. Routes

```php
// ── Admin (dashboard/events, prefix + middleware giống Post) ─────────────
Route::middleware(['auth'])->prefix('dashboard/events')->name('backend.event.')->group(function () {
    Route::resource('categories', EventCategoryAdminController::class)->except(['show']);
    Route::post('categories/reorder', ...)->name('categories.reorder');

    Route::get('pending-review', [EventAdminController::class, 'pendingReview'])->name('pending-review'); // ĐẶT TRƯỚC resource, cùng lý do đã ghi ở Post routes
    Route::resource('/', EventAdminController::class); // update() = UpdateEventAction, dùng bởi editor để chuẩn hoá nội dung trước Approve (§6.1) — KHÔNG tự đổi status
    Route::post('{event}/approve', ...)->name('approve');
    Route::post('{event}/reject', ...)->name('reject');
    Route::post('{event}/publish', ...)->name('publish');
    Route::post('{event}/archive', ...)->name('archive');
});

// ── Public (không {locale} — đồng nhất quyết định đã áp dụng cho Post, xem hội thoại trước) ──
Route::get('su-kien', [PublicEventController::class, 'index'])->name('event.public.home');
Route::get('su-kien/danh-muc/{category:slug}', [PublicEventController::class, 'category'])->name('event.public.category');
Route::get('su-kien/{slug}', [PublicEventController::class, 'show'])->name('event.public.show');

Route::get('su-kien/gui-su-kien', [EventSubmissionController::class, 'create'])->name('event.public.submit.form');
Route::post('su-kien/gui-su-kien', [EventSubmissionController::class, 'store'])
    ->middleware(['throttle:event-submit', ValidateEventTurnstile::class])
    ->name('event.public.submit.store');

// SEO (§10.7/§13 Phase 3) — nhân bản đúng route pattern của Post.
Route::get('event-sitemap.xml', [EventSitemapController::class, 'index'])->name('event.public.sitemap');
```

`RateLimiter::for('event-submit', ...)` đăng ký trong `EventServiceProvider` (giống `Modules\Survey\Providers\RouteServiceProvider`) — đề xuất **5 request / giờ / IP** (sự kiện không cần submit nhiều lần liên tục như survey; ngưỡng thấp hơn để giảm spam).

---

## 9. Permissions (bổ sung `app/Enums/PermissionEnum.php`)

```php
// ══ EVENT (Quản lý sự kiện — độc giả nộp công khai, toà soạn duyệt) ═════
case EVENT_CATEGORY_MANAGE = 'event_category.manage';
case EVENT_VIEW            = 'event.view';           // xem hàng chờ + đã publish
case EVENT_EDIT             = 'event.edit';           // sửa nội dung trước Approve (§6.1) — tách khỏi quyết định duyệt
case EVENT_MODERATE        = 'event.moderate';        // Approve/Reject (platform_content_editor)
case EVENT_PUBLISH         = 'event.publish';         // Publish (platform_content_head)
case EVENT_UNPUBLISH       = 'event.unpublish';       // Archive sau khi đã publish — tách quyền như post_article.unpublish
case EVENT_DELETE          = 'event.delete';
```

Gán quyền — tái dùng đúng cấu trúc Post đã có trong `config/permissions.php`:

| Role | Quyền |
|---|---|
| `platform_content_editor` | `EVENT_VIEW`, `EVENT_EDIT`, `EVENT_MODERATE`, `EVENT_CATEGORY_MANAGE` |
| `platform_content_head` | Toàn bộ quyền trên + `EVENT_PUBLISH`, `EVENT_UNPUBLISH`, `EVENT_DELETE` |
| `platform_ops` | `EVENT_VIEW` (chỉ xem, phục vụ theo dõi vận hành) |

---

## 10. Bảo mật & chống spam

### 10.1 Cloudflare Turnstile (thay Google reCAPTCHA trong ảnh gốc)

Nhân bản `Modules\Auth\Fortify\ValidateTurnstile` (bản đơn giản — 1 site key toàn cục qua `config('services.turnstile.*')`, tự bật khi có key, skip local/testing). Không dùng bản multi-site của Survey vì Event chỉ có đúng 1 form, không có nhu cầu nhúng nhiều domain khác nhau.

### 10.2 Rate limiting

`throttle:event-submit` — 5 request/giờ/IP (xem §8). Không đủ chống spam có tổ chức (proxy xoay IP) nhưng kết hợp Turnstile là đủ cho quy mô 1 form submit sự kiện.

### 10.3 "Không chứa link" trong mô tả

Validate ở `SubmitEventAction`/FormRequest: reject (không phải âm thầm strip) nếu `description` khớp regex `(https?:\/\/|www\.)`, trả lỗi rõ ràng "Vui lòng không chèn liên kết trong mô tả sự kiện." — nhất quán với cách Post xử lý nội dung ở tầng nhập liệu (validate rõ ràng, không "sửa hộ" dữ liệu người dùng nhập).

### 10.4 Ràng buộc file poster

Validate ở `StorePosterAction` (không phải DB constraint):
- Mime: `jpg, jpeg, png` (theo đúng ghi chú "Accepted file types: jpg, jpeg, png, gif" của form gốc — **loại `gif`** khỏi danh sách chấp nhận: GIF động không phù hợp poster tĩnh, và ghi chú kích thước khuyến nghị landscape của chính form gốc ngụ ý ảnh tĩnh).
- Kích thước file: ≤ 1MB.
- Khuyến nghị 1400×1000 (landscape) — validate mềm (cảnh báo, không chặn cứng nếu lệch tỷ lệ, vì độc giả không phải nhà thiết kế); **chặn cứng** nếu `width < height` (chân dung) vì poster hiển thị dạng card ngang trên listing.
- Lưu vào disk `public` (hoặc S3 nếu cấu hình), path dạng `events/posters/{uuid}.{ext}` — không dùng tên file gốc (tránh path traversal / trùng tên).
- `poster_alt` (nullable, §5.2): form public không có ô nhập riêng (không tăng thêm field bắt buộc cho độc giả) — mặc định `null`, fallback hiển thị `alt="{{ $event->title }}"` ở tầng view; `platform_content_editor` có thể bổ sung `poster_alt` riêng (mô tả ảnh chi tiết hơn title) khi sửa nội dung trước Approve (§6.1) nếu cần tối ưu SEO/accessibility cho ảnh cụ thể.

### 10.5 Email người nộp — không hiển thị công khai

Đã tách bảng (§5.3). Bổ sung: `EventSubmission` **không** có route-model-binding công khai nào, không `Http\Resources` nào serialize model này ra JSON công khai. Review code khi thêm field mới vào bất kỳ API công khai nào của Event phải xác nhận không `with('submission')`.

### 10.6 Chọn địa chỉ (Physical) — tái dùng nguyên `<x-address-picker>`

Form submit công khai dùng thẳng component có sẵn, không viết lại:

```blade
<x-address-picker
    :required="true"
    instance-id="event-venue"
    name-province="province_code"
    name-ward="ward_code"
/>
<input type="text" name="venue_name" required placeholder="Venue Name">
<input type="text" name="venue_address" required placeholder="Venue Address">
```

Component tự gọi `GET /api/provinces/{provinceCode}/wards` khi đổi tỉnh/thành (JS `initOrgAddress`, `resources/js/modules/tom-select.js`) — route này **đã** đánh dấu public/không-auth (`Modules/Organization/routes/api.php`), nên form Event (không đăng nhập) gọi thẳng được, không cần thêm route mới hay nới lỏng middleware nào.

Ràng buộc bundle: `tom-select.js` hiện chỉ có trong `vite.config.backend.js` (dùng lazy-load cho các trang admin). Trang submit-event thuộc cổng thông tin công khai (`vite.config.frontend.js`, xem hội thoại xây layout `layouts/frontend.blade.php`) — cần thêm `resources/js/modules/tom-select.js` làm 1 entry riêng trong `vite.config.frontend.js` (input list, giống cách backend đã làm), rồi `@vite(..., 'build/frontend')` **chỉ** ở trang submit-event, không load site-wide — giữ đúng nguyên tắc bundle portal tối giản đã đặt ra khi dựng `resources/css/frontend.css`/`resources/js/frontend.js`.

Khi tạo/sửa `province_name`/`ward_name` denormalized ở Action (§5.5): đọc tên hiển thị từ chính option đã chọn trên `<select>` (submit kèm hidden input `province_name`/`ward_name`, hoặc lookup lại `Province`/`Ward` theo code ở tầng Action để tránh tin dữ liệu client gửi lên — **khuyến nghị lookup lại server-side**, không tin hidden input, vì đây là form public không xác thực).

### 10.7 Giới hạn số sự kiện `Published` theo `submitter_email` (chống spam quảng cáo trá hình)

**Quyết định:** có giới hạn — tối đa **3 sự kiện đang ở trạng thái `Published` VÀ `end_date >= hôm nay`** cho cùng 1 `submitter_email`, tính trong cửa sổ **90 ngày** gần nhất (theo `event_submissions.created_at`). Chặn ở `SubmitEventAction` (validate TRƯỚC khi tạo record, không phải sau) — vượt ngưỡng thì từ chối submit kèm thông báo rõ ràng, không lặng lẽ đưa vào hàng chờ rồi reject.

```php
// SubmitEventAction — chạy trước khi tạo Event/EventSubmission mới
$activeCount = EventSubmission::where('submitter_email', $email)
    ->where('created_at', '>=', now()->subDays(90))
    ->whereHas('event', fn ($q) => $q->where('status', EventStatus::Published)->where('end_date', '>=', now()->toDateString()))
    ->count();

abort_if($activeCount >= 3, 422, 'Bạn đã có 3 sự kiện đang hiển thị. Vui lòng chờ sự kiện cũ kết thúc trước khi nộp thêm.');
```

Tận dụng đúng `index('submitter_email')` đã có ở `event_submissions` (§5.3) — không cần thêm index mới. Ngưỡng "3 / 90 ngày" là cấu hình được (`config('event.submission_limit')`), không hard-code — dễ điều chỉnh khi có dữ liệu thực tế về mức độ lạm dụng.

### 10.8 SEO — sitemap & Open Graph

Nhân bản đúng pattern Post đã có (`Modules\Post\Features\PublicReading\Http\SitemapController` + `post::public.sitemap`), làm **ngay từ Phase 3** (§13), không lùi:

- `event-sitemap.xml` (`EventSitemapController`) — liệt kê `Event::where('status', Published)`, `<lastmod>` = `updated_at`.
- Mỗi trang `event.public.show` bổ sung thẻ `<meta property="og:*">`: `og:title` (= `title`), `og:description` (= 160 ký tự đầu `description`), `og:image` (= URL đầy đủ `poster_path`), `og:type=event` — cùng khối `@push('meta')` đã dùng cho canonical/hreflang ở Post (`layouts/frontend.blade.php` đã có `@stack('meta')` sẵn trong `<head>`, không cần sửa layout).

---

## 11. Jobs nền & thông báo

### 11.1 `ExpirePastEventsJob` (nhân bản `ExpireSponsoredArticlesJob`)

```php
Event::where('status', EventStatus::Published)
    ->where('end_date', '<', now()->toDateString())
    ->chunkById(100, function ($events) {
        foreach ($events as $event) {
            try {
                $event->update(['status' => EventStatus::Expired]);
            } catch (\Throwable $e) {
                Log::error('ExpirePastEventsJob: lỗi xử lý event', ['event_id' => $event->id, 'exception' => $e->getMessage()]);
                // Không rethrow — event lỗi vẫn còn end_date cũ, job ngày mai tự thử lại.
            }
        }
    });
```

Chạy `daily` (đăng ký trong `EventServiceProvider::boot()`, giống Post đăng ký `ExpireSponsoredArticlesJob`) — hết hạn tính theo ngày, không cần `everyMinute`.

### 11.2 Thông báo

| Sự kiện hệ thống | Người nhận | Kênh |
|---|---|---|
| Có submission mới (`Submitted`) | `platform_content_editor` | Notification nội bộ (giống `SponsorshipExpiredNotification` pattern) |
| Approved/Rejected | Email `event_submissions.submitter_email` (KHÔNG lộ nội bộ nào khác) | Mail — nội dung: kết quả duyệt, nếu rejected kèm `rejected_reason` |
| Published | Không cần báo — độc giả không có tài khoản để "thấy" thông báo, xác nhận đã nằm ở bước Approved/Rejected |

---

## 12. Tích hợp với cổng thông tin công khai đã build

Thay `x-frontend.sponsor-spotlight` (đang dùng `post_articles.is_sponsored` làm placeholder) bằng dữ liệu Event thật:

1. `PublicCategoryController::index()` (Post) nạp thêm `$upcomingEvents` (top N `Event::where('status', Published)->where('end_date', '>=', today)->orderBy('start_date')`) truyền cho `post::public.home`.
2. Component mới `x-frontend.event-spotlight` (hoặc đổi tên `sponsor-spotlight` thành tổng quát hơn) — giữ đúng bố cục 1 khối lớn + list bên cạnh của bản mẫu tĩnh, nhưng nội dung là sự kiện thật (ngày, địa điểm, giá) thay vì bài tài trợ.
3. Nav danh mục (`frontend-nav.blade.php`) có thể thêm mục "Sự Kiện" trỏ `route('event.public.home')`, song song danh mục Post — 2 domain nội dung độc lập, cùng hiển thị trên 1 layout dùng chung (`layouts/frontend.blade.php`).
4. `x-frontend.promo-bar` có thể nhận thêm 1 biến thể liên kết sang `event.public.category` cho các danh mục sự kiện nổi bật.

**Không đổi gì ở `Modules/Post`** ngoài việc bớt phụ thuộc vào `sponsor-spotlight` làm placeholder — đây là dọn dẹp kỹ thuật (technical debt) đã được ghi chú rõ trong code khi xây dựng cổng thông tin.

---

## 13. Lộ trình triển khai theo pha

| Pha | Nội dung | Phụ thuộc |
|---|---|---|
| **Phase 1** ✅ | Migration + Model + Enum (§5, §6) + `EventPolicy` + permission seeder (§9) | Không phụ thuộc gì thêm, làm trước |
| **Phase 2a** ✅ | `EventCategoryManagement` (CRUD danh mục, sidebar) + `Event` create/update qua dashboard cho staff tự nhập trực tiếp (`CreateEventAction`/`UpdateEventAction`/`BuildEventAttributesAction`/`StoreEventPosterAction`, form đầy đủ location/price/poster) — EventPolicy::create()/update() cho editor/head/**ops** | Phase 1 |
| **Phase 2b** ✅ | `PublicSubmission` (form public + Turnstile + rate limit) + Approve/Reject/Publish/Archive (`EventModeration` phần duyệt) | Phase 1, 2a |
| **Phase 3** ✅ | `PublicReading` (danh sách/chi tiết/lọc danh mục công khai) + `event-sitemap.xml` + Open Graph tags (§10.8) + tích hợp trang chủ (§12) | Phase 1, 2a, 2b |
| **Phase 4** (nâng cao, chưa cấp thiết) | Sự kiện nhiều buổi/lặp lại (bảng `event_occurrences` riêng — 1 event có N khung giờ khác nhau), xuất file `.ics`, tích hợp bản đồ (`latitude`/`longitude` đã có cột sẵn ở §5.2), thống kê lượt xem theo danh mục | Phase 3 |

---

## 14. Quyết định đã chốt & câu hỏi còn mở

### 14.1 Đã chốt (review 15/07/2026)

| # | Câu hỏi | Quyết định | Đã áp dụng ở |
|---|---|---|---|
| 1 | Giới hạn số sự kiện `Published` đồng thời / 1 email? | **Có** — tối đa 3 sự kiện `Published` + `end_date >= hôm nay` / email / cửa sổ 90 ngày | §10.7 |
| 2 | Sự kiện nhiều khung giờ/lặp lại? | **Không làm ngay** — giữ nguyên Phase 4, chỉ triển khai khi business xác nhận nhu cầu thật | §13 Phase 4 |
| 3 | `platform_content_editor` có được sửa nội dung trước Approve? | **Có** — `UpdateEventAction` tách biệt `ApproveEventAction`, quyền `EVENT_EDIT` riêng | §6.1, §9 |
| 4 | Đơn vị tiền tệ — VND cố định hay đa tiền tệ? | **VND cố định** — không cần cột `currency` (bỏ hẳn khỏi schema, YAGNI) | §5.2 |

### 14.2 Còn mở

Không còn câu hỏi nào — toàn bộ điểm mở đã chốt ở §14.1. Đặc tả sẵn sàng chuyển sang Phase 1 (§13).
