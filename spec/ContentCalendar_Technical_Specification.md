# Content Calendar — Đặc tả Kỹ thuật (Module đề xuất)

> Trạng thái: **Đề xuất — chưa triển khai.** Đặc tả này viết trước khi có code, mục đích để thống nhất thiết kế trước khi `php artisan module:make ContentCalendar`. Đối chiếu trực tiếp với `Modules/CoreIdeaExtractor`, `Modules/Aicem`, `Modules/Post`, `Modules/Approval` — tái dùng đúng quy ước đã có, không phát minh pattern mới.

## Mục lục

1. [Giới thiệu & Mục tiêu](#1)
2. [Bối cảnh — khoảng trống hiện có](#2)
3. [Phạm vi Phase 1](#3)
4. [Vị trí trong hệ thống — Lớp A, không phải Lớp B](#4)
5. [Database Schema & Model](#5)
6. [RBAC — Permission & Policy](#6)
7. [Cấu trúc module & Actions/Queries](#7)
8. [Routes & Controllers](#8)
9. [UI — Board & Calendar view](#9)
10. [Tích hợp với CoreIdeaExtractor / Aicem / Post](#10)
11. [Config](#11)
12. [Module scaffolding](#12)
13. [Permission Seeder](#13)
14. [Acceptance Criteria](#14)
15. [Roadmap](#15)
16. [Ngoài phạm vi Phase 1](#16)
17. [Validation & Edge Cases](#17)

---

<a name="1"></a>
## 1. Giới thiệu & Mục tiêu

Toà soạn nền tảng hiện có đủ 2 đầu của pipeline biên tập:

- **Đầu vào ý tưởng**: `CoreIdeaExtractor` trích ý tưởng từ đối thủ (Layer 2 — tối đa ~25 ý tưởng kèm lý do, xem `RunLayer2ExtractionAction`) và lưu bối cảnh biên tập bền vững theo category (`CategoryContentFoundation` — audience/pain_points/unique_angle/rejected_ideas).
- **Đầu ra bài viết**: `Post` quản lý toàn bộ vòng đời bài viết thật (Draft → Submitted → Approved → Scheduled/Published, xem `TranslationStatus`), có phân cấp duyệt 2 tầng (`platform_content_editor` → `platform_content_head`) và biên tập viên phụ trách theo category (`post_category_editors`).
- **Trợ lý AI khi viết**: `Aicem` sinh/tối ưu nội dung cho 1 bài viết/sản phẩm **đã tồn tại**.

**Không có gì ở giữa.** Ý tưởng do Layer 2 sinh ra là **dữ liệu phù du** — trả thẳng về client dưới dạng Markdown để copy tay dán vào chat AI (xem docblock `CoreIdeaExtractorController::runLayer2()`), bảng `cie_layer2_runs` chỉ ghi **chi phí** của lần chạy (`cost_usd`, `model_used`), không lưu lại DANH SÁCH Ý TƯỞNG đã sinh. Không ai biết ý tưởng nào đã được chọn, ai sẽ viết, dự kiến đăng khi nào, cho tới khi có 1 `PostArticle` thật xuất hiện trong hệ thống — lúc đó thì bài đã viết xong rồi, không còn "kế hoạch" nữa.

**Mục tiêu của module này**: thêm đúng 1 lớp mỏng — "hàng đợi ý tưởng đã chọn + lịch xuất bản" — nối giữa 2 đầu đã có, mà **không** xây lại bất kỳ phần nào đã tồn tại (không sinh ý tưởng, không duyệt bài, không viết bài).

---

<a name="2"></a>
## 2. Bối cảnh — khoảng trống hiện có

Đối chiếu cụ thể để không đề xuất trùng chức năng đã có:

| Đã có | Ở đâu | Còn thiếu |
|---|---|---|
| Sinh ý tưởng từ nội dung đối thủ | `CoreIdeaExtractor` Layer 2 | Ý tưởng sinh ra không được lưu — chỉ hiện 1 lần trên UI rồi mất |
| Bối cảnh biên tập theo category (audience, pain points, góc nhìn riêng) | `CategoryContentFoundation` | Không có nơi theo dõi **tiến độ** áp dụng bối cảnh đó thành bài viết cụ thể |
| Ngăn AI đề xuất trùng ý tưởng | `rejected_ideas` (Decision Log thủ công) + `ListCategoryExistingArticlesAction` (bài đã publish) | Không biết ý tưởng nào **đang được lên kế hoạch nhưng chưa publish** — vẫn có thể bị đề xuất lại |
| Biên tập viên phụ trách theo category | `post_category_editors` (`platform_section_editor`) | Họ không có cách nào xem "sắp tới category mình có gì cần viết" — chỉ thấy bài đã có sẵn trong hàng chờ duyệt |
| Trạng thái vòng đời 1 bài viết thật | `TranslationStatus` (Draft…Published) | Không có trạng thái cho giai đoạn **trước khi có bài** (mới là ý tưởng/đã lên kế hoạch/đang phân công) |
| Trợ lý AI chỉnh sửa bài đã có | `Aicem` (`AicemSuggestion`, `AicemGenerationRun`) | Không sinh/theo dõi ý tưởng **chủ đề mới**, chỉ tối ưu bài đã tồn tại |

Đây cũng là điểm chung của cả 4 bài viết tham khảo (azonixgrowthlab, targetinternet, recommend.studio) về "AI content planning": **lên kế hoạch nội dung (topic backlog, lịch xuất bản, phân công)** luôn là bước riêng biệt, đứng trước bước "viết bài bằng AI prompt" — đúng khoảng trống đã xác định ở trên.

---

<a name="3"></a>
## 3. Phạm vi Phase 1

**Trong phạm vi:**
- 1 model mới: `ContentCalendarEntry` — bản ghi "ý tưởng đã chọn, sẽ viết".
- Board dạng cột theo trạng thái (Idea → Planned → Drafting → Ready → Done/Dropped) + lịch theo tháng (group theo `target_publish_date`).
- Gán người viết (`assigned_to`), gán category, ngày dự kiến đăng.
- Liên kết 1 entry với 1 `PostArticle` thật khi bắt đầu viết (không bắt buộc — vẫn có thể còn ở dạng ý tưởng chưa ai nhận).
- Khi đã liên kết, hiển thị trạng thái THẬT của bài viết (`TranslationStatus`) thay vì trạng thái tự quản của entry — tránh 2 state machine chạy song song.
- 1 action tra cứu tiêu đề "đang lên kế hoạch, chưa publish" theo category (đối xứng với `ListCategoryExistingArticlesAction` đã có) — expose qua API để `CoreIdeaExtractor` có thể gọi ở Phase 2 (xem §10), **không sửa `CoreIdeaExtractor` trong Phase 1**.

**Không trong phạm vi Phase 1** — xem chi tiết lý do ở [§16](#16): năng lực/SLA nhóm viết, cụm chủ đề (pillar/cluster), comment thread, nhắc hạn tự động, tự động đẩy dedup vào Layer 2.

---

<a name="4"></a>
## 4. Vị trí trong hệ thống — Lớp A, không phải Lớp B

`spec/Platform_RBAC_Technical_Specification.md` §2 định nghĩa 2 lớp RBAC song song:

```
Lớp A — Platform Roles (organization_id = null, xuyên mọi tổ chức)
  super-admin, platform_content_head, platform_content_editor,
  platform_content_moderator, platform_content_creator, platform_section_editor,
  platform_ops, platform_viewer

Lớp B — Organization Roles (organization_id = của tổ chức)
  ceo, sales, ops, marketing, hr, ai_operator, system_admin, viewer
```

`Post`, `Aicem`, `CoreIdeaExtractor` đều là tài sản **của nền tảng** (toà soạn dùng chung), không thuộc Organization nào — `PostArticle`/`PostCategory` đã bỏ hẳn `organization_id` khỏi global scope (migration `2026_07_13_000001_drop_organization_id_from_post_articles_table`, `2026_07_13_000003_make_post_categories_platform_wide`). Vai trò `marketing` ở Lớp B (trong `config/permissions.php`) là role của **nhân sự 1 tổ chức khách hàng** dùng CRM/Sales AI riêng tư của tổ chức đó — khác hoàn toàn với `platform_content_editor`/`platform_content_head` là nhân sự **nội bộ nền tảng** vận hành toà soạn chung.

→ **Content Calendar thuộc Lớp A**, giống hệt `CoreIdeaExtractor`:
- Model **không** extends `TenantAwareModel`, **không** có `organization_id`, **không** global scope theo tổ chức.
- Quyền seed **trực tiếp** vào role `platform_*` (seeder độc lập, giống `CoreIdeaExtractorPermissionSeeder`/`AicemPermissionSeeder`) — **không** đi qua `config/permissions.php` (file đó chỉ map Lớp B).

---

<a name="5"></a>
## 5. Database Schema & Model

### 5.1. Migration

```php
// Modules/ContentCalendar/database/migrations/2026_08_01_000001_create_content_calendar_entries_table.php

Schema::create('content_calendar_entries', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();

    // 1 entry luôn thuộc đúng 1 category — cùng đơn vị scope với post_category_editors,
    // để platform_section_editor lọc "kế hoạch của category mình" không cần bảng nối riêng.
    $table->foreignId('post_category_id')->constrained('post_categories')->cascadeOnDelete();

    $table->string('title', 255);
    $table->text('brief')->nullable(); // góc nhìn/tóm tắt ngắn — KHÔNG phải nội dung bài

    // Nguồn gốc ý tưởng — thuần audit/hiển thị, không dùng để rẽ nhánh logic.
    $table->string('origin', 30)->default('manual'); // manual | core_idea_extractor | aicem
    $table->text('origin_note')->nullable(); // vd dán tay dòng ý tưởng+lý do từ bảng Layer 2

    $table->string('status', 20)->default('idea'); // xem CalendarEntryStatus §5.3
    $table->date('target_publish_date')->nullable();

    $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

    // Set khi bắt đầu viết thật — từ lúc này trạng thái HIỂN THỊ ưu tiên đọc từ
    // postArticle->mainTranslation->status (§5.4), không đọc cột `status` ở trên nữa.
    $table->foreignId('post_article_id')->nullable()->unique()->constrained('post_articles')->nullOnDelete();

    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

    $table->timestamps();
    $table->softDeletes();

    $table->index(['post_category_id', 'target_publish_date'], 'cc_entries_category_date_idx');
    $table->index('status', 'cc_entries_status_idx');
    // 2 index dưới phục vụ đúng 2 lát cắt Policy::view() lọc theo ownership (§6.3) —
    // "entry của tôi, chưa xong" và "entry tôi được gán, chưa xong" đều WHERE kèm status.
    $table->index(['assigned_to', 'status'], 'cc_entries_assignee_status_idx');
    $table->index(['created_by', 'status'], 'cc_entries_creator_status_idx');
});
```

Không cột `organization_id` — nhất quán với `post_categories`/`post_articles`/`cie_category_foundations`.

### 5.2. Model

```php
// Modules/ContentCalendar/app/Models/ContentCalendarEntry.php

namespace Modules\ContentCalendar\Models;

class ContentCalendarEntry extends Model // KHÔNG extends TenantAwareModel — xem §4
{
    use SoftDeletes, LogsActivity; // cùng khuôn PostCategory — Activitylog thay cho bảng log riêng

    protected $table = 'content_calendar_entries';

    protected $fillable = [
        'post_category_id', 'title', 'brief', 'origin', 'origin_note',
        'status', 'target_publish_date', 'assigned_to', 'post_article_id',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'origin'               => CalendarEntryOrigin::class,
        'status'                => CalendarEntryStatus::class,
        'target_publish_date'   => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string { return 'uuid'; }

    public function category(): BelongsTo { return $this->belongsTo(PostCategory::class, 'post_category_id'); }
    public function assignedTo(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function postArticle(): BelongsTo { return $this->belongsTo(PostArticle::class, 'post_article_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }

    /**
     * Khi đã liên kết bài viết thật, trạng thái HIỂN THỊ luôn ưu tiên đọc từ
     * PostArticleTranslation::status (nguồn sự thật duy nhất cho vòng đời xuất bản) — cột
     * `status` ở bảng này bị "đóng băng" tại thời điểm liên kết, chỉ còn ý nghĩa lịch sử.
     * Tránh lặp lại đúng cái bẫy mà spec/AICEM đã tránh (không tạo 2 nguồn sự thật cho 1 khái niệm).
     */
    public function displayStatusLabel(): string
    {
        if ($this->post_article_id && $translation = $this->postArticle?->mainTranslation()) {
            return $translation->status->label();
        }

        return $this->status->label();
    }
}
```

### 5.3. Enum trạng thái — chỉ áp dụng TRƯỚC khi có bài viết thật

```php
// Modules/ContentCalendar/app/Enums/CalendarEntryStatus.php

enum CalendarEntryStatus: string
{
    case Idea     = 'idea';     // mới ghi nhận, chưa phân công
    case Planned  = 'planned';  // đã có ngày dự kiến + người viết
    case Drafting = 'drafting'; // đang viết (thường đi kèm post_article_id đã set)
    case Blocked  = 'blocked';  // vướng, cần theo dõi (thiếu tư liệu, chờ phê duyệt hướng đi...)
    case Ready    = 'ready';    // bản thảo xong, chờ vào luồng duyệt Post
    case Done     = 'done';     // bài đã publish — set tự động khi translation status = Published
    case Dropped  = 'dropped';  // huỷ kế hoạch — nên đồng bộ tay vào rejected_ideas (§10)

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Idea     => in_array($target, [self::Planned, self::Dropped], true),
            self::Planned  => in_array($target, [self::Drafting, self::Dropped], true),
            self::Drafting => in_array($target, [self::Blocked, self::Ready, self::Dropped], true),
            self::Blocked  => in_array($target, [self::Drafting, self::Dropped], true),
            self::Ready    => in_array($target, [self::Drafting, self::Done], true),
            self::Done, self::Dropped => false, // terminal
        };
    }
}
```

### 5.3.1. Khoá transition thủ công sau khi liên kết `PostArticle` (bắt buộc — §3.1)

`canTransitionTo()` ở trên là đồ thị trạng thái THUẦN, không biết gì về `post_article_id` — cố tình giữ enum đơn giản. Việc khoá xảy ra ở tầng Action (`ChangeCalendarEntryStatusAction`), là nơi DUY NHẤT được ghi cột `status` (§7), theo đúng 2 quy tắc sau — **không có ngoại lệ nào khác**:

1. **Nếu `entry->post_article_id !== null`**: `ChangeCalendarEntryStatusAction` (gọi qua API, tức là do người dùng thao tác) chỉ chấp nhận đúng 1 target là `Dropped` — mọi target khác bị từ chối (`InvalidTransitionException`), bất kể `canTransitionTo()` của enum cho phép gì. Lý do: sau khi liên kết, tiến độ THẬT đã do `TranslationStatus` quyết định (§5.2 `displayStatusLabel()`) — cho phép user tự tay đẩy `status` sang `Ready`/`Drafting` sẽ tạo cảm giác "đang cập nhật tiến độ" trong khi không có ý nghĩa gì, đúng rủi ro "cột chết nhưng vẫn sửa được" đã nêu.
2. **`Done` không bao giờ là target hợp lệ của `ChangeCalendarEntryStatusAction`** (loại trừ tường minh trong Action, không chỉ dựa vào `canTransitionTo()`) — `Done` CHỈ được set bởi `MarkLinkedEntryAsDoneListener` (§5.3.2) thao tác thẳng lên Eloquent (`forceFill()->save()`), không đi qua Action công khai. Nhờ vậy 1 dòng kiểm tra duy nhất (`if ($target === Done) reject`) trong Action là đủ để đảm bảo "chỉ hệ thống được set Done", không cần phân biệt "ai" gọi.

`LinkCalendarEntryToArticleAction` (§7): ngay khi set `post_article_id`, cũng tự chuyển `status` sang `Drafting` nếu đang `Idea`/`Planned` (transition này hợp lệ theo đồ thị enum, chạy TRƯỚC khi khoá ở trên có hiệu lực — tại thời điểm gọi, `post_article_id` vừa được set trong cùng 1 transaction).

UI (Kanban): khi entry đã liên kết, cột "Trạng thái" hiển thị badge `displayStatusLabel()` **readonly** kèm link "Xem bài viết" — thẻ vẫn kéo-thả được giữa các cột **chỉ khi** thao tác đó là đổi `assigned_to`/`target_publish_date` (không đổi cột `status`); nút "Huỷ kế hoạch" (chuyển `Dropped`) vẫn hiện riêng, không qua kéo-thả.

**Khoá chỉ áp dụng cho cột `status`** — `Policy::update()` (§6.3) và `UpdateCalendarEntryAction` **không** khoá thêm field nào khác sau khi liên kết: `title`, `brief`, `assigned_to`, `target_publish_date` vẫn sửa được bình thường (vd đổi người phụ trách khi người cũ nghỉ, dời ngày dự kiến khi bài trễ) — đây là lựa chọn có chủ đích, không phải sót: chỉ `status` mang rủi ro "2 nguồn sự thật" (§5.2), các field còn lại là metadata quản lý thuần tuý của bản thân entry, không phản ánh tiến độ xuất bản nên không cần khoá theo `TranslationStatus`.

### 5.3.2. Auto-transition sang `Done` — tái dùng event `ArticlePublished` có sẵn, không sửa `Post` (§3.2)

Đã xác nhận trong `Modules/Post/app/Features/ArticleAuthoring/Actions/PublishArticleAction.php`: **cả 2 đường publish** (thủ công qua `PublishArticleAction::handle()`, và tự động qua `PublishDueTranslationsJob` — vốn cũng gọi thẳng `PublishArticleAction::handle()`) đều kết thúc bằng `event(new ArticlePublished($translation))`, vô điều kiện (chỉ bỏ qua nếu translation đã `Published` sẵn — no-op idempotent). Sự kiện này đã tồn tại, đã được khai báo trong `Modules/Post/app/Providers/EventServiceProvider.php::$listen` nhưng **hiện chưa có listener nào** (`ArticlePublished::class => []`) — không cần sửa 1 dòng nào trong `Post`, chỉ cần đăng ký listener ở phía `ContentCalendar`:

```php
// Modules/ContentCalendar/app/Providers/EventServiceProvider.php
use Modules\Post\Features\ArticleAuthoring\Events\ArticlePublished;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ArticlePublished::class => [
            MarkLinkedEntryAsDoneListener::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false; // cùng convention Post/Aicem
}
```

```php
// Modules/ContentCalendar/app/Listeners/MarkLinkedEntryAsDoneListener.php
class MarkLinkedEntryAsDoneListener
{
    public function handle(ArticlePublished $event): void
    {
        // article_id, không phải translation_id — 1 PostArticle có nhiều PostArticleTranslation
        // (đa ngôn ngữ), nhưng content_calendar_entries.post_article_id trỏ tới BÀI VIẾT
        // (khái niệm đa ngôn ngữ), không tới 1 bản dịch cụ thể — publish BẤT KỲ bản dịch nào
        // của bài đã liên kết cũng coi là "xong" (đúng ngữ nghĩa displayStatusLabel() đang đọc
        // mainTranslation(), không đọc theo locale).
        // Query Builder ->update() (không phải Eloquent save() từng model) — bỏ qua model event
        // (nên KHÔNG chạy LogsActivity) và bất kỳ observer nào sau này thêm vào model. Chấp
        // nhận được: đây là cập nhật hệ thống hàng loạt, không phải hành động biên tập viên cần
        // audit trail cá nhân — nếu sau này cần thấy "khi nào entry chuyển Done" trong lịch sử,
        // đổi sang lặp `get()->each->update()` (Eloquent, có event) khi có nhu cầu thật, không
        // làm trước khi cần (YAGNI, cùng tinh thần §16).
        ContentCalendarEntry::where('post_article_id', $event->translation->article_id)
            ->where('status', '!=', CalendarEntryStatus::Done->value)
            ->update(['status' => CalendarEntryStatus::Done->value]);
    }
}
```

Không cần job định kỳ/quét nền — event đã bắn đúng lúc, đúng chỗ, cho cả publish thủ công lẫn `Scheduled → Published` tự động. Chỉ cân nhắc thêm job quét bù (`chunkById` các entry `post_article_id IS NOT NULL AND status != Done` mà bài liên kết đã `Published`) nếu về sau phát sinh đường publish KHÁC không đi qua `PublishArticleAction::handle()` — hiện tại (đã grep toàn bộ `TranslationStatus::Published` trong `Modules/Post/app`) không có đường nào khác, nên **không thêm job bù ở Phase 1** (YAGNI — thêm cơ chế dự phòng cho tình huống chưa từng xảy ra).

### 5.4. Origin — chỉ để hiển thị, không rẽ nhánh logic

```php
enum CalendarEntryOrigin: string
{
    case Manual             = 'manual';
    case CoreIdeaExtractor  = 'core_idea_extractor';
    case Aicem              = 'aicem';
}
```

Không dùng `subject_type`/`subject_id` kiểu polymorphic như `AicemGenerationRun::subject()` vì Layer 2 **không có bản ghi nào để trỏ tới** (§2) — biên tập viên copy tay tiêu đề + lý do vào `origin_note` khi tạo entry. Đây là lựa chọn có chủ đích: giữ đúng triết lý "copy/paste, con người quyết định" mà `CoreIdeaExtractor` đang theo (xem docblock `RunLayer2ExtractionAction`: "kết quả dùng để copy nguyên JSON dán vào chat AI"), không cố tự động hoá quá tay ở Phase 1.

---

<a name="6"></a>
## 6. RBAC — Permission & Policy

### 6.1. 2 permission — seed trực tiếp vào role Lớp A

| Permission | Ý nghĩa |
|---|---|
| `content_calendar.view` | Xem board/lịch |
| `content_calendar.manage` | Tạo/sửa/gán/đổi trạng thái/liên kết bài viết (phạm vi cụ thể do Policy quyết định — xem 6.2) |

Không thêm permission `content_calendar.delete` riêng — xoá là 1 trường hợp đặc biệt của `manage`, phân biệt bằng Policy (giống cách `PostArticlePolicy::delete()` tái dùng `post_article.delete` + check ownership thay vì có permission riêng cho "xoá bài của người khác").

### 6.2. Ma trận role (Lớp A)

| Role | view | manage | Ghi chú |
|---|---|---|---|
| `platform_content_creator` | ✅ (entry của mình: `created_by`/`assigned_to` = user) | ✅ (chỉ entry của mình, không xoá nếu đã `post_article_id` liên kết) | Cộng tác viên — tự nhận ý tưởng, tự cập nhật tiến độ |
| `platform_section_editor` | ✅ (entry thuộc category được gán qua `post_category_editors`) | ✅ (cùng phạm vi category) | Y hệt phạm vi họ đã có ở `core_idea_extractor.manage_category_foundation` |
| `platform_content_editor` | ✅ (mọi category) | ✅ (mọi category, kể cả xoá) | |
| `platform_content_head` | ✅ | ✅ | |
| `platform_viewer` | ✅ | ❌ | Chỉ xem — không có nút Sửa/Xoá/Gán, đúng vai trò read-only đã định nghĩa ở §3.3 spec RBAC |
| `platform_ops` | ❌ | ❌ | Không liên quan biên tập nội dung |
| `super-admin` | ✅ (toàn quyền, bypass) | ✅ | |

### 6.3. Policy

```php
// Modules/ContentCalendar/app/Policies/ContentCalendarEntryPolicy.php

class ContentCalendarEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('content_calendar.view');
    }

    public function view(User $user, ContentCalendarEntry $entry): bool
    {
        if (! $user->can('content_calendar.view')) return false;
        if ($user->isPlatformContentEditor() || $user->isPlatformContentHead()) return true;
        if ($user->isPlatformSectionEditor()) {
            return $user->postCategoryEditorships()->where('post_categories.id', $entry->post_category_id)->exists();
        }
        return $entry->created_by === $user->id || $entry->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('content_calendar.manage');
    }

    public function update(User $user, ContentCalendarEntry $entry): bool
    {
        return $this->view($user, $entry) && $user->can('content_calendar.manage');
    }

    /** Xoá: chỉ editor/head, hoặc chủ entry khi CHƯA liên kết bài viết thật. */
    public function delete(User $user, ContentCalendarEntry $entry): bool
    {
        if (! $user->can('content_calendar.manage')) return false;
        if ($user->isPlatformContentEditor() || $user->isPlatformContentHead()) return true;

        return $entry->post_article_id === null
            && ($entry->created_by === $user->id || $this->view($user, $entry));
    }
}
```

Đăng ký trong `ContentCalendarServiceProvider::boot()` qua `Gate::policy(ContentCalendarEntry::class, ContentCalendarEntryPolicy::class)` — không đụng policy của `PostCategory` (khác với `CoreIdeaExtractor`, module này có model riêng nên dùng `Gate::policy()` bình thường, không cần `Gate::define()` ability rời).

### 6.4. Giới hạn category lúc TẠO entry — trách nhiệm của Action, không phải Policy (§3.3)

`Policy::create()` chỉ trả lời được "user này có được tạo entry NÓI CHUNG không" (`content_calendar.manage`) — tại thời điểm gọi `create()`, chưa có `ContentCalendarEntry` nào để Policy kiểm tra `post_category_id` thuộc phạm vi ai. Nếu dừng ở đó, 1 `platform_section_editor` chỉ được gán category X vẫn có thể POST `entries` với `post_category_id` = category Y (ngoài phạm vi) — lỗ hổng thật, không phải lý thuyết.

**Bắt buộc chặn ở 2 lớp, không chỉ 1:**

1. **Form/dropdown category** (UI) — nếu user `isPlatformSectionEditor()` (và không đồng thời là `content_editor`/`content_head`), danh sách category trong `<select>` CHỈ populate từ `$user->postCategoryEditorships()`, giống hệt cách form `CategoryFoundationController` giới hạn dropdown hiện có.
2. **`CreateCalendarEntryAction`** (server, bắt buộc — không tin form client) — validate tường minh:

```php
// Modules/ContentCalendar/app/Features/CalendarPlanning/Actions/CreateCalendarEntryAction.php
public function handle(User $actor, CalendarEntryData $data): ContentCalendarEntry
{
    if (
        $actor->isPlatformSectionEditor()
        && ! $actor->isPlatformContentEditor()
        && ! $actor->isPlatformContentHead()
        && ! $actor->postCategoryEditorships()->where('post_categories.id', $data->post_category_id)->exists()
    ) {
        throw new AuthorizationException('Category ngoài phạm vi phụ trách.');
    }

    return ContentCalendarEntry::create([...]);
}
```

Cùng 1 điều kiện y hệt `CoreIdeaExtractorServiceProvider::boot()`'s `Gate::define('core_idea_extractor.manage_category_foundation', ...)` — tái dùng đúng logic đã kiểm chứng, không viết lại theo cách khác. `UpdateCalendarEntryAction` khi cho phép ĐỔI `post_category_id` của 1 entry đã có (nếu UI cho phép) phải chạy lại đúng check này cho category MỚI, không chỉ check ở lúc tạo.

---

<a name="7"></a>
## 7. Cấu trúc module & Actions/Queries

Theo đúng convention `Features/{TênFeature}/{Actions,Data,Http,Queries}` đã dùng ở `CoreIdeaExtractor`/`Post`:

```
Modules/ContentCalendar/app/
├── Models/ContentCalendarEntry.php
├── Enums/CalendarEntryStatus.php
├── Enums/CalendarEntryOrigin.php
├── Policies/ContentCalendarEntryPolicy.php
├── Listeners/MarkLinkedEntryAsDoneListener.php   // §5.3.2 — nghe ArticlePublished, KHÔNG sửa gì bên Post
├── Providers/{ContentCalendarServiceProvider,RouteServiceProvider,EventServiceProvider}.php
└── Features/CalendarPlanning/
    ├── Actions/
    │   ├── CreateCalendarEntryAction.php         // §6.4 — tự check postCategoryEditorships(), không chỉ dựa Policy
    │   ├── UpdateCalendarEntryAction.php
    │   ├── ChangeCalendarEntryStatusAction.php   // §5.3.1 — canTransitionTo() + khoá khi đã link + chặn target Done
    │   ├── LinkCalendarEntryToArticleAction.php  // §17.3 — set post_article_id, auto status→Drafting nếu đang Idea/Planned
    │   └── ListCategoryPlannedTitlesAction.php   // §10 — đối xứng ListCategoryExistingArticlesAction
    ├── Queries/
    │   └── ListCalendarEntriesAction.php         // §7.1 — filter + eager-load + phân trang bắt buộc
    ├── Data/
    │   ├── CalendarEntryData.php                 // DTO tương tự CategoryFoundationData, validation §17.1
    │   └── CalendarBoardFilterData.php            // categoryId/assignedTo/from/to/includeDone (mặc định false, §7.1)
    └── Http/
        └── CalendarEntryController.php
```

`ChangeCalendarEntryStatusAction` là nơi DUY NHẤT được phép ghi cột `status` — mọi UI action (kéo-thả Kanban, nút chuyển trạng thái) đi qua đây để `canTransitionTo()` luôn được kiểm tra ở tầng Action, không chỉ ở UI (đúng nguyên tắc đã thấy ở `TranslationStatus::canTransitionTo()` — "validate ở tầng Action, KHÔNG chỉ ở UI"), và tự loại trừ `Done`/mọi target khi đã liên kết bài viết (§5.3.1).

### 7.1. `ListCalendarEntriesAction` — eager-load & giới hạn bắt buộc (§3.4)

`displayStatusLabel()` (§5.2) đọc `$this->postArticle?->mainTranslation()` cho MỖI entry hiển thị trên board — nếu query danh sách không eager-load, board N entry đã liên kết bài viết sẽ tạo N+1 query (`postArticle`) cộng thêm N query nữa (`mainTranslation()` bên trong, tự query `translations()` nếu chưa load) — vừa là bug hiệu năng vừa là rủi ro timeout khi board có vài trăm entry. **Bắt buộc:**

```php
// Modules/ContentCalendar/app/Features/CalendarPlanning/Queries/ListCalendarEntriesAction.php
public function handle(User $viewer, CalendarBoardFilterData $filter): LengthAwarePaginator
{
    return ContentCalendarEntry::query()
        ->with(['category', 'assignedTo', 'postArticle.translations']) // mainTranslation() lọc trong PHP trên collection đã load, KHÔNG query lại
        ->when(! $filter->includeDone, fn ($q) => $q->whereNotIn('status', [CalendarEntryStatus::Done, CalendarEntryStatus::Dropped]))
        ->when($filter->categoryId, fn ($q) => $q->where('post_category_id', $filter->categoryId))
        ->when($filter->assignedTo, fn ($q) => $q->where('assigned_to', $filter->assignedTo))
        ->when($filter->from, fn ($q) => $q->where('target_publish_date', '>=', $filter->from))
        ->when($filter->to, fn ($q) => $q->where('target_publish_date', '<=', $filter->to))
        ->tap(fn ($q) => $this->applyOwnershipScope($q, $viewer)) // §6.3 — section_editor/content_creator chỉ thấy phạm vi của mình, ngay trong SQL chứ không lọc collection sau khi đã load
        ->orderBy('target_publish_date')
        ->paginate(50); // board KHÔNG load toàn bộ bảng 1 lần — mỗi cột Kanban tự phân trang phía client qua cùng endpoint kèm filter status
}
```

Điểm bắt buộc, không phải tuỳ chọn:

- **Mặc định loại `Done`/`Dropped`** (`includeDone = false` mặc định) — board là công cụ nhìn về TƯƠNG LAI, không phải kho lưu trữ; xem lại việc đã xong/đã huỷ là thao tác chủ động (`includeDone=true` tường minh), không load kèm mỗi lần mở board.
- **Ownership scope áp dụng ở tầng SQL** (`applyOwnershipScope()`, cùng điều kiện với `Policy::view()` §6.3), không phải "query hết rồi lọc bằng PHP" — nếu không, `platform_content_creator` với hàng nghìn entry toàn hệ thống sẽ tải hết về rồi mới lọc, vừa chậm vừa có thể rò rỉ dữ liệu qua API response trung gian (vd log request) trước khi lọc.
- **Phân trang bắt buộc** (`paginate(50)`, không `get()`) — không có giả định "board sẽ luôn nhỏ".

Index `cc_entries_category_date_idx`/`cc_entries_status_idx`/`cc_entries_assignee_status_idx`/`cc_entries_creator_status_idx` (§5.1) được chọn khớp đúng 4 điều kiện `when()` + ownership scope ở trên — mỗi index phục vụ đúng 1 nhánh lọc thật, không thêm index suy đoán.

**Ghi chú triển khai — vì sao `->with(['postArticle.translations'])` là đủ, không cần sửa `mainTranslation()`:** đã đối chiếu `Modules/Post/app/Models/PostArticle.php` — `mainTranslation()` gọi `translation($locale)`, và `translation()` đọc qua **property** `$this->translations` (không phải gọi `translations()` như method quan hệ) — Eloquent `__get` cho property trùng tên quan hệ luôn ưu tiên collection ĐÃ eager-load nếu `relationLoaded('translations')` là `true`, chỉ tự lazy-query khi chưa load. Do đó khi `ListCalendarEntriesAction` đã `with('postArticle.translations')`, gọi `mainTranslation()` trên từng entry chắc chắn dùng lại collection có sẵn, không bắn thêm query — không cần sửa gì ở `PostArticle`, chỉ cần đảm bảo Action không quên nhánh `with()` này. Đây cũng chính là lý do `PostArticlePolicy::update()` phải gọi `loadMissing('article')` trước khi đọc `$translation->article` (docblock method đó) — cùng 1 lớp bẫy "lazy-load khi Model::shouldBeStrict() bật ở môi trường non-production sẽ ném `LazyLoadingViolationException`" mà spec này tránh bằng eager-load tường minh thay vì chỉ dựa vào `loadMissing()` phòng ngừa.

---

<a name="8"></a>
## 8. Routes & Controllers

```php
// Modules/ContentCalendar/routes/web.php

Route::middleware(['auth', 'can:content_calendar.view'])
    ->prefix('dashboard/content-calendar')
    ->name('backend.contentcalendar.')
    ->group(function (): void {
        Route::get('/', [CalendarEntryController::class, 'board'])->name('board');     // Kanban
        Route::get('/schedule', [CalendarEntryController::class, 'calendar'])->name('calendar'); // lịch tháng
    });

Route::middleware(['auth', 'can:content_calendar.view'])
    ->prefix('backend/api/content-calendar')
    ->name('backend.api.contentcalendar.')
    ->group(function (): void {
        Route::get('entries', [CalendarEntryController::class, 'list'])->name('entries.list');
        Route::post('entries', [CalendarEntryController::class, 'store'])->name('entries.store'); // authorize('create') trong controller
        Route::put('entries/{entry:uuid}', [CalendarEntryController::class, 'update'])->name('entries.update');
        Route::patch('entries/{entry:uuid}/status', [CalendarEntryController::class, 'changeStatus'])->name('entries.change-status');
        Route::post('entries/{entry:uuid}/link-article', [CalendarEntryController::class, 'linkArticle'])->name('entries.link-article');
        Route::delete('entries/{entry:uuid}', [CalendarEntryController::class, 'destroy'])->name('entries.destroy');
        Route::get('categories/{category}/planned-titles', [CalendarEntryController::class, 'plannedTitles'])->name('categories.planned-titles');
    });
```

Route model binding theo `uuid` — nhất quán với `PostArticle`/`PostCategory` (`getRouteKeyName()` trả `uuid`, không lộ ID tự tăng).

Middleware chỉ gate `content_calendar.view` ở tầng route (ai xem được board thì vào được trang) — quyền ghi cụ thể theo từng entry (`create`/`update`/`delete`) check bằng `$this->authorize()` trong từng method controller, đúng pattern `ArticleAdminController`.

---

<a name="9"></a>
## 9. UI — Board & Calendar view

2 view, cùng 1 tập dữ liệu (`ListCalendarEntriesAction`), lọc theo category mà user có quyền xem (Policy §6.3):

- **Board (Kanban)** — cột theo `CalendarEntryStatus`, thẻ hiển thị `title`, category badge, avatar `assigned_to`, `target_publish_date`. Khi entry đã có `post_article_id`, badge trạng thái đổi màu/nhãn theo `displayStatusLabel()` (đọc từ `TranslationStatus`) thay vì cột status thường — kéo thẻ đó giữa các cột Kanban vẫn được phép (đổi ngày/người phụ trách), nhưng ô "trạng thái" hiển thị readonly kèm link "Xem bài viết" trỏ sang `Post` thật.
- **Calendar (lịch tháng)** — group theo `target_publish_date`, entry chưa có ngày rơi vào cột "Chưa xếp lịch" riêng ở đầu trang.
- Form tạo/sửa entry: chọn category (giới hạn theo `postCategoryEditorships()` nếu là `platform_section_editor`), origin (select: Thủ công / CoreIdeaExtractor / Aicem — khi chọn 2 loại sau hiện thêm textarea `origin_note` để dán ý tưởng+lý do), ngày dự kiến, người phụ trách.

Menu sidebar — thêm cạnh mục AICEM (`resources/views/layouts/partials/sidebar.blade.php`, gần dòng 331), gate bằng `@can('content_calendar.view')`, cùng nhóm điều hướng với CoreIdeaExtractor/AICEM (dòng ~285-353).

---

<a name="10"></a>
## 10. Tích hợp với CoreIdeaExtractor / Aicem / Post

**Post** — điểm liên kết chính thức duy nhất: trang tạo/sửa `PostArticle` (`ArticleAdminController`) thêm 1 dropdown tuỳ chọn "Gắn với kế hoạch nội dung" (liệt kê entry có `status` chưa `Done`/`Dropped`, cùng category, chưa có `post_article_id`) — gọi `LinkCalendarEntryToArticleAction`. **Không bắt buộc** — bài viết vẫn tạo được bình thường không qua Content Calendar, module này chỉ là lớp kế hoạch tuỳ chọn, không chặn luồng viết bài hiện có.

**CoreIdeaExtractor** — `ListCategoryPlannedTitlesAction::handle($category)` trả về tiêu đề các entry đang hoạt động (status ∈ `idea/planned/drafting/blocked/ready`) của 1 category, cùng hình dạng trả về với `ListCategoryExistingArticlesAction` (mảng string, giới hạn theo config §11). Expose qua route `categories/{category}/planned-titles` (§8) để client-side prompt builder của CoreIdeaExtractor (nơi đang build `buildLayer2Prompt()`) CÓ THỂ gọi thêm endpoint này và nối vào cùng đoạn "tránh trùng ý tưởng" hiện đang chỉ có `rejected_ideas` + bài đã publish. **Việc sửa `CoreIdeaExtractorController`/JS để thực sự gọi endpoint này để ở Phase 2** (§16) — Phase 1 chỉ đảm bảo endpoint tồn tại và đúng permission, không đụng code CoreIdeaExtractor.

**Aicem** — không có điểm tích hợp bắt buộc ở Phase 1 (Aicem thao tác trên bài viết đã tồn tại, ngoài phạm vi "trước khi có bài" của module này). `origin = aicem` chỉ dùng khi biên tập viên muốn ghi chú rằng ý tưởng bài viết mới nảy sinh từ 1 gợi ý Aicem trên bài khác (thuần audit, không có FK thật tới `AicemSuggestion`).

---

<a name="11"></a>
## 11. Config

```php
// Modules/ContentCalendar/config/content_calendar.php

return [
    // Dùng bởi ListCategoryPlannedTitlesAction (§10) — cùng khuôn
    // core_idea_extractor.existing_articles để 2 nguồn dedup có giới hạn nhất quán.
    'dedup' => [
        'db_fetch_limit'  => 100,
        'max_titles'      => 30,
        'active_statuses' => ['idea', 'planned', 'drafting', 'blocked', 'ready'],
    ],

    // Mặc định số ngày nhìn về phía trước khi mở view Calendar lần đầu (không lưu DB).
    'board' => [
        'default_lookahead_days' => 60,
    ],
];
```

Không thêm key nào chưa có Action nào đọc tới (tránh cấu hình chết) — capacity/SLA (§16) sẽ có config riêng khi thực sự triển khai ở Phase 2.

---

<a name="12"></a>
## 12. Module scaffolding

```json
// Modules/ContentCalendar/module.json
{
    "name": "ContentCalendar",
    "alias": "content-calendar",
    "description": "Lịch biên tập & kế hoạch nội dung nền tảng — cầu nối giữa ý tưởng (CoreIdeaExtractor/Aicem) và bài viết thật (Post): theo dõi ý tưởng đã chọn, người viết, ngày dự kiến đăng, tới khi có PostArticle thật.",
    "keywords": [],
    "priority": 0,
    "providers": [
        "Modules\\ContentCalendar\\Providers\\ContentCalendarServiceProvider"
    ],
    "files": []
}
```

```php
// Modules/ContentCalendar/app/Providers/ContentCalendarServiceProvider.php

class ContentCalendarServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'ContentCalendar';
    protected string $nameLower = 'contentcalendar';

    protected array $providers = [RouteServiceProvider::class, EventServiceProvider::class]; // §5.3.2

    public function register(): void
    {
        parent::register();
        $this->mergeConfigFrom(__DIR__.'/../../config/content_calendar.php', 'content_calendar');
    }

    public function boot(): void
    {
        parent::boot();
        Gate::policy(ContentCalendarEntry::class, ContentCalendarEntryPolicy::class);
    }
}
```

---

<a name="13"></a>
## 13. Permission Seeder

```php
// Modules/ContentCalendar/database/seeders/ContentCalendarPermissionSeeder.php
// Chạy: php artisan db:seed --class="Modules\ContentCalendar\Database\Seeders\ContentCalendarPermissionSeeder"

class ContentCalendarPermissionSeeder extends Seeder
{
    private const ROLE_MAP = [
        'platform_content_creator' => ['content_calendar.view', 'content_calendar.manage'],
        'platform_section_editor'  => ['content_calendar.view', 'content_calendar.manage'],
        'platform_content_editor'  => ['content_calendar.view', 'content_calendar.manage'],
        'platform_content_head'    => ['content_calendar.view', 'content_calendar.manage'],
        'platform_viewer'          => ['content_calendar.view'],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['content_calendar.view', 'content_calendar.manage'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (self::ROLE_MAP as $roleName => $perms) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->givePermissionTo($perms);
        }

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        $superAdmin?->syncPermissions(Permission::all());

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->command->info('  ✓ ContentCalendar permissions seeded.');
    }
}
```

Cùng khuôn chính xác với `AicemPermissionSeeder`/`CoreIdeaExtractorPermissionSeeder` — không cần sửa `config/permissions.php` (file đó chỉ dành cho Lớp B).

---

<a name="14"></a>
## 14. Acceptance Criteria

- [ ] Tạo entry mới (thủ công hoặc dán từ Layer 2) → xuất hiện ở cột "Idea" trên board.
- [ ] `platform_section_editor` chỉ thấy/sửa được entry thuộc category mình được gán qua `post_category_editors`; không thấy entry của category khác.
- [ ] `platform_content_creator` chỉ thấy/sửa entry của chính mình (`created_by`/`assigned_to`); không xoá được entry đã có `post_article_id`.
- [ ] Chuyển trạng thái sai luồng (vd `Idea` → `Ready` thẳng) bị `ChangeCalendarEntryStatusAction` từ chối dù gọi thẳng API, không chỉ chặn ở UI.
- [ ] Chuyển sang `Planned` khi thiếu `target_publish_date` HOẶC thiếu `assigned_to` (1 trong 2, không chỉ khi thiếu cả 2) đều bị từ chối — đúng định nghĩa "đã có ngày dự kiến + người viết" ở §5.3 (§17.1).
- [ ] Liên kết entry với 1 `PostArticle` → board hiển thị trạng thái thật (`TranslationStatus`) của bài, không còn dùng cột `status` riêng của entry để hiển thị.
- [ ] Bài viết liên kết chuyển `Published` (thủ công hoặc qua `PublishDueTranslationsJob`) → entry tự chuyển `Done` qua `MarkLinkedEntryAsDoneListener` nghe `ArticlePublished` (§5.3.2), không cần job quét nền.
- [ ] Sau khi entry đã có `post_article_id`: gọi `ChangeCalendarEntryStatusAction` với bất kỳ target nào khác `Dropped` đều bị từ chối — kể cả gọi thẳng API, không chỉ ẩn nút ở UI (§5.3.1).
- [ ] Gọi `ChangeCalendarEntryStatusAction` với target `Done` luôn bị từ chối bất kể ai gọi — `Done` chỉ được set bởi listener (§5.3.1 mục 2).
- [ ] `platform_section_editor` KHÔNG được tạo entry với `post_category_id` ngoài `postCategoryEditorships()` của mình — kể cả khi tự gửi request `POST /entries` với category id tuỳ ý, không chỉ khi thao tác qua dropdown UI (§6.4).
- [ ] `platform_viewer` xem được board nhưng không thấy nút Tạo/Sửa/Xoá/Gán.
- [ ] `ListCategoryPlannedTitlesAction` trả đúng danh sách tiêu đề đang hoạt động, tôn trọng `dedup.max_titles`.
- [ ] Xoá 1 `PostCategory` (nếu từng xảy ra) không để lại entry mồ côi tham chiếu category đã mất (cascadeOnDelete).
- [ ] Board mặc định (`includeDone=false`) không trả entry `Done`/`Dropped`; endpoint list luôn phân trang (không có request nào trả toàn bộ bảng trong 1 lần) (§7.1).
- [ ] Tạo entry với `origin` khác `manual` mà bỏ trống `origin_note` → validation lỗi, không lưu được (§17.1).
- [ ] Gọi `LinkCalendarEntryToArticleAction` với 1 `PostArticle` đã được entry khác liên kết → trả lỗi validation rõ ràng, không phải `QueryException` 500 (§17.3).

---

<a name="15"></a>
## 15. Roadmap

**Phase 1 (đặc tả này)**: CRUD entry, board + calendar view, RBAC theo category/ownership, liên kết `PostArticle`, action tra cứu tiêu đề đang lên kế hoạch (endpoint sẵn sàng, chưa được `CoreIdeaExtractor` gọi tới).

**Phase 2**: `CoreIdeaExtractor` client-side thực sự gọi `categories/{category}/planned-titles` khi build prompt Layer 2 (đóng vòng dedup); nhắc hạn (`target_publish_date` sắp tới) qua notification có sẵn (`Modules/Approval/app/Notifications` làm mẫu); cụm chủ đề — thêm `parent_entry_id` tự tham chiếu để nhóm nhiều entry quanh 1 pillar, phục vụ đúng "Topic Cluster & Authority Map" (bài targetinternet).

**Phase 3**: năng lực nhóm viết (đếm entry `assigned_to` theo tuần → cảnh báo quá tải) + SLA đơn giản (số ngày tối đa mỗi trạng thái trước khi coi là trễ) — tương ứng "Content Operations Capacity & SLA Planner", nhưng cần dữ liệu thật từ Phase 1/2 chạy một thời gian mới thiết kế ngưỡng hợp lý, không đoán số ở giai đoạn này.

---

<a name="16"></a>
## 16. Ngoài phạm vi Phase 1

| Ý tưởng (từ 4 bài tham khảo) | Vì sao chưa làm ngay |
|---|---|
| 90-Day Roadmap + chấm điểm RICE | Cần dữ liệu lịch sử (bao nhiêu entry/tháng, tỷ lệ Dropped) mà hệ thống hiện chưa có — làm ngay sẽ là đoán số, không phải thiết kế dựa trên dữ liệu thật |
| Persona-to-Journey Objection Matrix (gắn nội dung theo funnel CRM) | Đòi hỏi nối `Lead`/`LeadPipelineStage` (Lớp B, theo Organization) với nội dung toà soạn (Lớp A, xuyên tổ chức) — 2 lớp RBAC khác nhau, cần thiết kế riêng, không nhét vào Phase 1 của module này |
| Comment thread trên từng entry | `ApprovalLog` (module gần nhất về mặt chức năng) cũng không có comment thread, chỉ có `reason` khi chuyển trạng thái — Activitylog đã đủ cho MVP, thêm bảng comment riêng là speculative |
| Capacity/SLA nhóm viết | Xem Phase 3 — cần dữ liệu vận hành thật trước khi đặt ngưỡng |
| Tự động đẩy dedup vào Layer 2 | Cần sửa code `CoreIdeaExtractor` (module khác, ngoài phạm vi đặc tả 1 module) — xem Phase 2 |
| Đa kênh (repurpose sang social/newsletter) | Đã có sẵn 1 phần ở `CoreIdeaExtractor` (`rewrite` — FB/LinkedIn/Twitter) và `Newsletter` — không phải khoảng trống của riêng module này |

---

<a name="17"></a>
## 17. Validation & Edge Cases

### 17.1. Validation rule — `CalendarEntryData` (§3.5)

```php
'post_category_id'     => ['required', 'integer', 'exists:post_categories,id'],
'title'                 => ['required', 'string', 'max:255'],
'brief'                 => ['nullable', 'string', 'max:2000'],
'origin'                => ['required', Rule::enum(CalendarEntryOrigin::class)],
// Bắt buộc khi origin khác thủ công — không cho tạo entry gắn nhãn "từ CoreIdeaExtractor"
// mà không kèm ghi chú gì để sau này biết ý tưởng gốc là gì (mất hết ngữ cảnh nếu để trống).
'origin_note'           => ['required_unless:origin,manual', 'nullable', 'string', 'max:5000'],
'target_publish_date'   => ['nullable', 'date'],
'assigned_to'           => ['nullable', 'integer', 'exists:users,id'],
```

**`target_publish_date` VÀ `assigned_to` khi chuyển `status = Planned`**: không chặn ở validation tầng field (2 field này luôn `nullable` vì entry `Idea` hợp lệ không cần ngày/người viết) — chặn ở `ChangeCalendarEntryStatusAction::handle()`: từ chối transition sang `Planned` nếu **1 trong 2** field `target_publish_date`/`assigned_to` đang null. Định nghĩa `Planned` ở §5.3 là "đã có ngày dự kiến **+** người viết" — enforce đúng cả 2 vế, không chỉ 1 vế, để tên trạng thái không nói dối (1 entry `Planned` nhưng chưa có ai nhận thì thực chất vẫn là `Idea`). Validate ĐIỀU KIỆN NGỮ NGHĨA của trạng thái tại nơi duy nhất được phép đổi trạng thái, không lặp lại rule này ở tầng field (field-level không biết trạng thái đang/sắp là gì):

```php
// ChangeCalendarEntryStatusAction::handle() — trích đoạn guard trước khi gọi $entry->update()
if ($target === CalendarEntryStatus::Planned && (! $entry->target_publish_date || ! $entry->assigned_to)) {
    throw ValidationException::withMessages([
        'status' => 'Cần có ngày dự kiến đăng và người phụ trách trước khi chuyển sang Planned.',
    ]);
}
```

### 17.2. SoftDeletes — không lộ entry đã xoá

- Route model binding theo `uuid` (§5.2) dùng default Eloquent binding — Laravel tự loại `deleted_at IS NOT NULL` khỏi truy vấn binding, nên URL trỏ tới 1 entry đã xoá mềm tự trả 404 mà không cần code thêm. **Không** dùng `withTrashed()` ở bất kỳ route/query nào trong Phase 1 (không có tính năng khôi phục — nếu cần xem lại entry đã xoá, dùng Activitylog `LogsActivity` đã bật sẵn trên model, không phải mở lại chính entry đó).
- `ListCalendarEntriesAction` (§7.1) không cần thêm `whereNull('deleted_at')` tường minh — global scope của `SoftDeletes` tự áp dụng; chỉ ghi chú ở đây để người đọc sau này không tưởng nhầm là thiếu.

### 17.3. `LinkCalendarEntryToArticleAction` — validate trước khi chạm constraint DB

Ràng buộc `content_calendar_entries.post_article_id` UNIQUE (§5.1) đã chặn việc 2 entry cùng trỏ 1 bài viết **ở tầng DB**, nhưng để lỗi đó rơi thẳng thành `QueryException` ra UI là trải nghiệm kém — Action phải tự kiểm tra trước và trả lỗi rõ ràng:

```php
// Modules/ContentCalendar/app/Features/CalendarPlanning/Actions/LinkCalendarEntryToArticleAction.php
public function handle(ContentCalendarEntry $entry, PostArticle $article): ContentCalendarEntry
{
    if (ContentCalendarEntry::where('post_article_id', $article->id)->exists()) {
        throw ValidationException::withMessages([
            'post_article_id' => 'Bài viết này đã được gắn với 1 kế hoạch khác.',
        ]);
    }

    // Cùng kiểu check PostArticlePolicy::approve() dùng cho platform_section_editor (§6, đối
    // chiếu category bài viết với category entry) — CHẶN CỨNG (ValidationException, không phải
    // cảnh báo) nếu bài viết không thuộc category của entry, với thông báo đủ rõ để editor tự
    // sửa (đổi category entry hoặc chọn bài khác) — chấp nhận GIAO nhau với category PHỤ của bài
    // (không đòi trùng category CHÍNH/`primaryCategory()`), vì 1 bài có thể gắn nhiều category.
    $article->loadMissing('categories');
    if (! $article->categories->pluck('id')->contains($entry->post_category_id)) {
        throw ValidationException::withMessages([
            'post_article_id' => 'Bài viết không thuộc category của kế hoạch này — kiểm tra lại trước khi gắn.',
        ]);
    }

    $entry->update(['post_article_id' => $article->id]);

    if (in_array($entry->status, [CalendarEntryStatus::Idea, CalendarEntryStatus::Planned], true)) {
        $entry->update(['status' => CalendarEntryStatus::Drafting]); // §5.3.1
    }

    return $entry->refresh();
}
```
