# Publishing Engine Module
**Đặc tả Kỹ thuật Chi tiết – Sẵn sàng Triển khai**

**Phiên bản:** 2.0 (hợp nhất với codebase thật + `docs/post-module-spec.md`)
**Ngày:** 11/07/2026
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions
**Hệ thống:** Nền tảng multi-tenant (Organization-scoped)
**Module liên quan:** `Modules/Post` (đã tồn tại — xem `docs/post-module-spec.md`), `Modules/Product`, `Modules/Aicem`

> **v2.0 thay đổi so với v1.0**: v1.0 là bản khung tổng quát, viết độc lập, không đối chiếu code thật và không tham chiếu `docs/post-module-spec.md` (spec gốc của `Modules/Post`, đã implement tới Phase 4-8). Bản này **hợp nhất 2 tài liệu**, chốt lại các điểm mà v1.0 để ngỏ (multi-site, workflow engine, cách ly product-block đa ngôn ngữ) dựa trên thảo luận trực tiếp với stakeholder — xem §0.

---

## 0. Quyết định đã chốt (thay thế phần tương ứng của v1.0)

| Chủ đề | v1.0 nói gì | Quyết định v2.0 | Lý do |
|---|---|---|---|
| **Multi-language** | "Linh hoạt & ổn định", bảng translation riêng | **Làm đầy đủ, ngay đợt này** | Có nhu cầu đa ngôn ngữ thật, không phải điều khoản tương lai |
| **Multi-site** (§5 của v1.0) | Publish độc lập theo site | **Loại khỏi phạm vi** | Hệ thống không có khái niệm "site" nào ngoài `Organization` (đã là ranh giới tenant/xuất bản); không có nhu cầu cụ thể |
| **State machine** | 7 trạng thái, ghi là "global" nhưng đá với §4.2 (mỗi ngôn ngữ trạng thái riêng) | **7 trạng thái, áp dụng PER-LOCALE** (trên `post_article_translations`, không phải trên `post_articles`) | Khớp đúng tinh thần "publish độc lập theo ngôn ngữ" của chính v1.0 §4.2, xoá mâu thuẫn nội tại |
| **Workflow duyệt** | "Kết nối mượt mà với Workflow & Approval" — không rõ có nghĩa `Modules/WorkflowAutomation` hay không | **State nội bộ trong `Modules/Post`**, KHÔNG tích hợp engine `Modules/WorkflowAutomation` | Post hiện đã tự quản lý field `status` nội bộ (`SubmitArticleForReviewAction`...), không có `WorkflowSubject`/`SubjectRegistry` nào đăng ký — giữ nguyên pattern đang chạy, tránh việc lớn không cần thiết |
| **Product CTA Block đa ngôn ngữ** | Không đề cập | **Tách riêng theo từng locale** — `post_product_blocks`/`post_content_blocks` chuyển FK từ `article_id` sang `translation_id` | Cho phép nội dung/box sản phẩm khác nhau hoàn toàn giữa các bản dịch (đúng thực tế biên tập đa ngôn ngữ, không ép "dịch y hệt cấu trúc") |
| **Đăng ngay/Gỡ bài** | Mô tả chung chung | Map 1:1 vào action cụ thể đã có/cần thêm trong `Modules/Post` (§7) | Tái dùng khung Action đã có, không tạo pattern song song |

---

## 1. Giới thiệu & Mục tiêu

Publishing Engine **không phải module riêng** — đây là phần mở rộng của `Modules/Post` (đã tồn tại, xem `docs/post-module-spec.md`), bổ sung:

1. **Multi-language thật** cho `PostArticle` (hiện tại module chỉ hỗ trợ 1 ngôn ngữ/bài).
2. **Trạng thái xuất bản đầy đủ** theo từng ngôn ngữ: `draft → submitted → approved → scheduled/published → unpublished → archived`.
3. **Gỡ bài có kiểm soát** (unpublish/takedown, bắt buộc lý do) — hiện chưa tồn tại.
4. **Audit log xuất bản** (`post_publishing_logs`) — hiện chưa tồn tại.
5. **Tự động publish khi tới lịch** (queue job + Scheduler) — hiện tại `ScheduleArticleAction` chỉ set `status=scheduled` + `published_at` tương lai, **không có gì tự chuyển sang `published` khi tới giờ** (xem comment trong chính code: *"Chưa có command `post:publish-due`... phải publish tay khi đến hạn"*). Đây là gap thật, không phải điểm mới của spec.

**Không thay đổi**: cấu trúc Category/Tag, Product CTA Box template rendering, cơ chế sanitize HTML, quan hệ với `Modules/Product` — mọi thứ ở `docs/post-module-spec.md` §5-§9 giữ nguyên, chỉ đổi **chủ sở hữu FK** của `post_content_blocks`/`post_product_blocks*` (xem §3).

---

## 2. Kiến trúc dữ liệu — ERD

```
PostArticle (KHÔNG còn title/slug/status/published_at/seo_*/approved_*)
  ├─ organization_id, uuid, main_locale, format, cover_image_url,
  │  is_featured, sort_order, created_by, updated_by, timestamps, soft delete
  ├─< post_article_categories >── PostCategory   (dùng chung mọi ngôn ngữ)
  ├─< post_article_tag >── PostTag                (dùng chung mọi ngôn ngữ)
  │
  └──< (1:n) PostArticleTranslation  [locale, title, slug, excerpt, seo_*,
             status (7-state), published_at, scheduled_at, unpublish_reason,
             approved_by, approved_at]
                │
                ├──< (1:n) PostContentBlock   [type, sort_order, text_html, product_block_id]
                │              (đổi FK: translation_id thay vì article_id)
                │
                └──< (1:n) PostProductBlock  [template, heading, sort_order]
                               (đổi FK: translation_id thay vì article_id)
                               ├──< PostProductBlockItem [product_id FK cứng → products, *_override]
                               └──< PostProductBlockButton [url_type, url, product_link_type, click_count]

PostArticleTranslation ──< (1:n) PostPublishingLog [action, reason, performed_by, created_at]
```

**Không đổi**: `post_categories`, `post_tags`, `post_article_categories`, `post_article_tag`, `post_product_block_items`, `post_product_block_buttons` (chỉ đổi bảng cha `post_product_blocks` trỏ `translation_id`, còn cấu trúc 2 bảng con này giữ nguyên FK `block_id`).

---

## 3. Migrations

> **Nguyên tắc bắt buộc (sửa sau review)**: KHÔNG được gộp "thêm cột FK NOT NULL" + "bảng đã có dữ liệu" trong cùng 1 bước — `post_articles`/`post_content_blocks`/`post_product_blocks` đã có dữ liệu (demo seeder + có thể có dữ liệu thật do người dùng tạo), thêm `foreignId()->constrained()` mặc định là NOT NULL sẽ **fail ngay khi migrate**. Phải tách thành **5 migration file riêng biệt**, chạy tuần tự, mỗi file 1 trách nhiệm — cho phép rollback từng bước nếu backfill sai:

| # | Migration file | Việc làm | Rollback an toàn? |
|---|---|---|---|
| 1 | `..._create_post_article_translations_table.php` | Tạo bảng mới (§3.2), thêm cột `main_locale` vào `post_articles`. Chưa đụng dữ liệu cũ. | Có — `dropIfExists` |
| 2 | `..._add_translation_id_to_post_content_blocks_and_product_blocks_table.php` | Thêm `translation_id` **nullable** (chưa `NOT NULL`, chưa drop `article_id`) vào 2 bảng | Có — `dropColumn` |
| 3 | *(không phải migration — xem §3.5)* `php artisan post:backfill-translations` | Đọc dữ liệu cũ từ `post_articles`/`article_id`, ghi vào `post_article_translations` + set `translation_id` trên 2 bảng con | Idempotent (xem §3.5), chạy lại an toàn |
| 4 | `..._finalize_post_translations_schema.php` | Set `translation_id` **NOT NULL**, drop `article_id` khỏi 2 bảng con, drop cột cũ khỏi `post_articles` (title/slug/status/...) | **Không** dễ rollback (mất cột cũ) — chỉ chạy sau khi xác nhận bước 3 chạy đúng trên **mọi** môi trường (staging trước, production sau) |
| 5 | `..._create_post_publishing_logs_table.php` | Tạo bảng audit log (§3.4) | Có |

Lý do dùng **command riêng** (`post:backfill-translations`) thay vì nhét logic backfill thẳng vào migration (như bản v2.0 trước): migration chạy trong `migrate` không dễ re-run/dry-run/log tiến độ; tách thành Artisan command cho phép `--dry-run` xem trước, chạy lại nếu lỗi giữa chừng (idempotent theo `article_id` đã có translation hay chưa), và tách rõ trách nhiệm "đổi schema" (migration) khỏi "chuyển dữ liệu" (command) — dễ audit trong code review hơn 1 migration file làm cả 2 việc.

### 3.1 Migration #1 — tạo bảng translation + cột `main_locale`

```php
Schema::table('post_articles', function (Blueprint $table) {
    $table->string('main_locale', 10)->default('vi')->after('uuid');
});
```
Cột `title/slug/status/published_at/seo_*/approved_*/view_count` của `post_articles` **giữ nguyên ở bước này** — chỉ drop ở Migration #4, sau khi đã backfill xong và xác nhận đúng. `view_count` chuyển xuống translation (đo theo bản dịch cụ thể người đọc xem, không phải bài viết trừu tượng).

### 3.2 `post_article_translations` (Migration #1, tiếp)

```php
Schema::create('post_article_translations', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();  // route-model-binding công khai, không lộ id số
    $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
    $table->string('locale', 10);    // 'vi', 'en', ...  — danh sách hợp lệ: config('post.locales')

    $table->string('title', 300);
    $table->string('slug', 320);
    $table->string('excerpt', 500)->nullable();
    $table->string('seo_title', 200)->nullable();
    $table->string('seo_description', 300)->nullable();

    $table->string('status', 20)->default('draft'); // TranslationStatus, xem §5
    $table->timestamp('published_at')->nullable();
    $table->timestamp('scheduled_at')->nullable();   // thời điểm dự kiến publish khi status=scheduled
    $table->string('unpublish_reason', 500)->nullable(); // bắt buộc khi status=unpublished, xem §7
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('approved_at')->nullable();

    $table->unsignedBigInteger('view_count')->default(0);
    $table->timestamps();
    $table->softDeletes();

    $table->unique(['article_id', 'locale'], 'uq_post_trans_article_locale');
    $table->unique(['organization_id', 'locale', 'slug'], 'uq_post_trans_org_locale_slug');
    $table->index(['organization_id', 'locale', 'status', 'published_at'], 'idx_post_trans_org_status_pub');

    $table->foreignId('organization_id')->constrained()->restrictOnDelete();
});
```
`organization_id` denormalize xuống translation (thay vì join qua `article_id`) — bắt buộc vì `TenantAwareModel` global scope lọc trực tiếp theo cột này trên mọi bảng, đúng pattern hiện có của `post_content_blocks`/`post_product_blocks` (đã denormalize `organization_id` dù có `article_id`).

### 3.3 Đổi FK của `post_content_blocks` / `post_product_blocks`

**Migration #2 — thêm cột mới, nullable, KHÔNG đụng `article_id`:**
```php
Schema::table('post_content_blocks', function (Blueprint $table) {
    $table->foreignId('translation_id')->nullable()->after('organization_id')
        ->constrained('post_article_translations')->cascadeOnDelete();
});
Schema::table('post_product_blocks', function (Blueprint $table) {
    $table->foreignId('translation_id')->nullable()->after('organization_id')
        ->constrained('post_article_translations')->cascadeOnDelete();
});
```

**→ chạy `php artisan post:backfill-translations` (§3.5) ở đây, giữa Migration #2 và #4 — không phải trong file migration.**

**Migration #4 — sau khi backfill xác nhận đúng (100% dòng có `translation_id`), finalize:**
```php
Schema::table('post_content_blocks', function (Blueprint $table) {
    $table->foreignId('translation_id')->nullable(false)->change();
    $table->dropConstrainedForeignId('article_id');
});
Schema::table('post_product_blocks', function (Blueprint $table) {
    $table->foreignId('translation_id')->nullable(false)->change();
    $table->dropConstrainedForeignId('article_id');
});
Schema::table('post_articles', function (Blueprint $table) {
    $table->dropColumn([
        'title', 'slug', 'excerpt', 'status', 'published_at',
        'seo_title', 'seo_description', 'approved_by', 'approved_at', 'view_count',
    ]);
    $table->dropUnique('uq_post_article_org_slug');
    $table->dropIndex('idx_post_article_org_status_pub');
});
```
Index `idx_post_cb_article_order`/`idx_post_pb_org_article` đổi tương ứng sang `translation_id` (thêm ở Migration #2, drop bản cũ ở #4).

### 3.4 `post_publishing_logs` (mới — audit)

```php
Schema::create('post_publishing_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();
    $table->foreignId('translation_id')->constrained('post_article_translations')->cascadeOnDelete();
    $table->string('action', 20); // publish|schedule|cancel_schedule|unpublish|takedown|archive|approve
    $table->string('reason', 500)->nullable();
    $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('created_at')->useCurrent();

    $table->index(['translation_id', 'created_at'], 'idx_post_pub_log_translation');
});
```
Không có `updated_at` — log là append-only, không sửa.

### 3.5 Data backfill — `php artisan post:backfill-translations` (command, không phải migration)

> **Sửa 2 lỗi của bản v2.0 trước** (phát hiện khi rà lại theo review): (1) copy thẳng `'status' => $a->status` sẽ ghi giá trị `pending_review` xuống cột mới — nhưng `TranslationStatus` **không có case này** (đổi tên thành `submitted`), Eloquent cast sẽ crash khi đọc lại dòng đó; (2) bài đang `status=scheduled` ở schema cũ dùng `published_at` để lưu **ngày dự kiến** (chưa publish thật) — copy thẳng sang `published_at` mới là sai, phải chuyển vào `scheduled_at` và để `published_at = null`.

```php
// Modules/Post/app/Console/Commands/BackfillPostTranslationsCommand.php
class BackfillPostTranslationsCommand extends Command
{
    protected $signature = 'post:backfill-translations {--dry-run}';
    // Không tự định nghĩa --verbose riêng — Artisan command đã có sẵn cờ verbosity chuẩn
    // (`-v`/`-vv`/`php artisan post:backfill-translations --dry-run -v`), dùng $this->output->isVerbose()
    // để bật log chi tiết thay vì thêm 1 option trùng tên, tránh xung đột với cờ built-in của Symfony Console.

    private const STATUS_MAP = [
        'draft'          => 'draft',
        'pending_review' => 'submitted',   // đổi tên — KHÔNG copy thẳng chuỗi
        'published'      => 'published',
        'scheduled'      => 'scheduled',
        'archived'       => 'archived',
    ];

    public function handle(): void
    {
        $dryRun = $this->option('dry-run');
        $count = 0;

        DB::table('post_articles')->whereNull('deleted_at')->orderBy('id')
            ->chunkById(200, function ($articles) use ($dryRun, &$count) {
                foreach ($articles as $a) {
                    // Idempotent: bỏ qua nếu article này đã có translation (cho phép chạy lại an toàn
                    // sau khi sửa lỗi giữa chừng, không tạo trùng).
                    if (DB::table('post_article_translations')->where('article_id', $a->id)->exists()) {
                        continue;
                    }

                    $newStatus   = self::STATUS_MAP[$a->status] ?? 'draft';
                    $isScheduled = $newStatus === 'scheduled';

                    if ($dryRun) {
                        $this->line("[dry-run] article #{$a->id} ({$a->title}) → status={$newStatus}");
                        $count++;
                        continue;
                    }

                    $translationId = DB::table('post_article_translations')->insertGetId([
                        'uuid'              => (string) Str::uuid(),
                        'article_id'        => $a->id,
                        'organization_id'   => $a->organization_id,
                        'locale'            => $a->main_locale ?? 'vi',
                        'title'             => $a->title,
                        'slug'              => $a->slug,
                        'excerpt'           => $a->excerpt,
                        'seo_title'         => $a->seo_title,
                        'seo_description'   => $a->seo_description,
                        'status'            => $newStatus,
                        'published_at'      => $isScheduled ? null : $a->published_at,
                        'scheduled_at'      => $isScheduled ? $a->published_at : null,
                        'approved_by'       => $a->approved_by,
                        'approved_at'       => $a->approved_at,
                        'view_count'        => $a->view_count,
                        'created_at'        => $a->created_at,
                        'updated_at'        => $a->updated_at,
                    ]);

                    $cbCount = DB::table('post_content_blocks')->where('article_id', $a->id)->update(['translation_id' => $translationId]);
                    $pbCount = DB::table('post_product_blocks')->where('article_id', $a->id)->update(['translation_id' => $translationId]);

                    if ($this->output->isVerbose()) {
                        $this->line("  article #{$a->id}: {$cbCount} content_blocks, {$pbCount} product_blocks → translation #{$translationId}");
                    }

                    $count++;
                }
            });

        $this->info(($dryRun ? '[dry-run] ' : '') . "Đã xử lý {$count} bài viết.");
    }
}
```

**Quy trình chạy**: `--dry-run` trên staging trước → soát log → chạy thật trên staging (thêm `-v` nếu muốn xem chi tiết số block/product-block cập nhật từng article, hữu ích khi soát trên production) → verify 100% `post_content_blocks`/`post_product_blocks` có `translation_id` không null (`SELECT COUNT(*) FROM post_content_blocks WHERE translation_id IS NULL` phải = 0) → mới chạy Migration #4 (finalize, drop cột cũ). Trên production lặp lại đúng quy trình, **không bỏ qua bước dry-run**.

**Rủi ro thấp cho public traffic**: `PublicReading` (trang công khai xem bài) **chưa triển khai** (`docs/post-module-spec.md` §17 Phase 9-10 chưa làm — route `post.public.*` không tồn tại trong `routes/web.php` hiện tại), nên chưa có traffic/SEO link công khai nào phụ thuộc slug cũ có thể vỡ. Backfill chỉ ảnh hưởng dữ liệu admin nội bộ.

---

## 4. Config mới

`Modules/Post/config/config.php` thêm:
```php
'locales' => [
    'vi' => 'Tiếng Việt',
    'en' => 'English',
],
'default_locale' => 'vi',
```
`ArticleData`/validation dùng danh sách này thay vì hardcode — thêm ngôn ngữ mới (vd `th`, `ja`) chỉ cần thêm 1 dòng vào mảng, không cần đổi enum/migration/code (locale là `string`, không phải PHP enum, đúng nguyên tắc "mở rộng không phá schema" đã dùng cho `ProductBlockTemplate` ở `docs/post-module-spec.md` §8).

---

## 5. Enums

```php
// Modules/Post/app/Enums/TranslationStatus.php — THAY THẾ ArticleStatus (đổi tên + tách khỏi PostArticle)
enum TranslationStatus: string
{
    case Draft       = 'draft';
    case Submitted   = 'submitted';    // chờ duyệt — thay cho 'pending_review' cũ
    case Approved    = 'approved';     // đã duyệt, chưa publish
    case Scheduled   = 'scheduled';
    case Published   = 'published';
    case Unpublished = 'unpublished';  // gỡ tạm — có thể publish lại
    case Archived    = 'archived';     // lưu trữ vĩnh viễn — không quay lại được

    public function label(): string { /* Nháp/Chờ duyệt/Đã duyệt/Đã lên lịch/Đã xuất bản/Đã gỡ/Lưu trữ */ }
    public function badgeClass(): string { /* DaisyUI badge-* tương ứng */ }

    /** Transition hợp lệ — validate ở tầng Action, KHÔNG chỉ ở UI. */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft       => in_array($target, [self::Submitted, self::Scheduled]),
            self::Submitted   => in_array($target, [self::Approved, self::Draft]), // Draft = reject
            self::Approved    => in_array($target, [self::Scheduled, self::Published]),
            self::Scheduled   => in_array($target, [self::Published, self::Draft]), // Draft = cancel schedule
            self::Published   => in_array($target, [self::Unpublished, self::Archived]),
            self::Unpublished => in_array($target, [self::Published, self::Archived]),
            self::Archived    => false,
        };
    }
}
```
`ArticleStatus` cũ **xoá hẳn** (không phải deprecate-giữ-lại) — không còn cột `status` nào trên `PostArticle` dùng nó.

**Không đổi**: `ArticleFormat`, `ProductBlockTemplate`, `ButtonUrlType`, `ButtonTarget`, `ButtonStyle`, `ContentBlockType` — vẫn ở cấp `PostContentBlock`/`PostProductBlock`, giờ chỉ đổi cha (`translation_id`), không đổi enum.

---

## 6. Models

**`PostArticle`** — bỏ mọi field publishing (`status`, `published_at`, `approved_*` khỏi `$fillable`/`$casts`); thêm:
```php
public function translations(): HasMany { return $this->hasMany(PostArticleTranslation::class, 'article_id'); }
public function translation(string $locale): ?PostArticleTranslation {
    return $this->translations->firstWhere('locale', $locale);
}
public function mainTranslation(): ?PostArticleTranslation { return $this->translation($this->main_locale); }

/** Eager-load sẵn bản dịch chính — dùng ở ListArticlesForAdminQuery để tránh N+1 khi hiển thị cột "Tiêu đề" cho mỗi dòng danh sách. */
public function scopeWithMainTranslation(Builder $query): void
{
    $query->with(['translations' => fn ($q) => $q->whereColumn('locale', 'post_articles.main_locale')]);
}
```

**`PostArticleTranslation`** (mới) — `TenantAwareModel`, route key `uuid`, `$casts = ['status' => TranslationStatus::class, 'published_at' => 'datetime', 'scheduled_at' => 'datetime', 'approved_at' => 'datetime']`. Quan hệ: `article()` (BelongsTo), `contentBlocks()`/`productBlocks()` (HasMany, đổi FK), `publishingLogs()` (HasMany). Thêm:
```php
public function scopePublished(Builder $query): void
{
    $query->where('status', TranslationStatus::Published);
}

/** Điều kiện đủ để bấm nút Publish — dùng để enable/disable nút ở UI lẫn guard trong PublishArticleAction. */
public function isPublishable(): bool
{
    return in_array($this->status, [TranslationStatus::Approved, TranslationStatus::Scheduled], true);
}

/** Dòng log gần nhất theo 1 action cụ thể — dùng để lấy "người publish gần nhất" cho notification takedown (§7.6), tránh query lặp lại ở nhiều nơi. */
public function latestPublishLog(): ?PostPublishingLog
{
    return $this->publishingLogs()->where('action', 'publish')->latest('created_at')->first();
}
```

**`PostContentBlock` / `PostProductBlock`** — đổi `belongsTo(PostArticle::class, 'article_id')` → `belongsTo(PostArticleTranslation::class, 'translation_id')`, đổi `$fillable`.

**`ArticleContentRenderer`** — nhận `PostArticleTranslation` thay vì `PostArticle` làm tham số `render()`/`toComposerPayload()`.

---

## 7. Actions & Jobs

### 7.1 Đổi tham số (breaking, cần sửa `ArticleAdminController` + Blade view)

| Action | Trước | Sau |
|---|---|---|
| `PublishArticleAction` | `handle(PostArticle $article)` | `handle(PostArticleTranslation $translation)` |
| `ScheduleArticleAction` | `handle(PostArticle $article, Carbon $publishAt)` | `handle(PostArticleTranslation $translation, Carbon $publishAt)` |
| `SubmitArticleForReviewAction` | `handle(PostArticle $article)` | `handle(PostArticleTranslation $translation)` |
| `ArchiveArticleAction` | `handle(PostArticle $article)` | `handle(PostArticleTranslation $translation)` |

Mọi action validate `canTransitionTo()` trước khi update, ném `InvalidTransitionException` nếu sai — tránh double-submit qua 2 tab trình duyệt đưa trạng thái vào ô không hợp lệ.

### 7.2 Action mới

- **`ApproveArticleTranslationAction`** — `submitted → approved`; set `approved_by/approved_at`; log.
- **`UnpublishArticleTranslationAction`** — `published → unpublished`; **bắt buộc** `string $reason` (validate `required|min:10`); set `unpublish_reason`; giữ `published_at` (lịch sử) nhưng bài không còn hiển thị công khai (`PublicReading` Query lọc `status=published` only); log action=`unpublish`.
- **`TakeDownArticleTranslationAction`** — giống Unpublish nhưng đích là `archived` thẳng (gỡ vĩnh viễn, "hard take down" theo v1.0 §3); bắt buộc `reason`; log action=`takedown`.
- **`CancelScheduleAction`** — `scheduled → draft`; xoá `scheduled_at`.
- **`PublishAllTranslationsAction`** — lặp qua mọi `translation` của 1 `article` đang `isPublishable()` (§6), gọi `PublishArticleAction::handle($t)` cho **từng translation** bên trong 1 `DB::transaction` bao ngoài toàn bộ vòng lặp (all-or-nothing — nếu 1 translation lỗi, rollback hết, không publish nửa chừng). Vì `PublishArticleAction` tự ghi `post_publishing_logs` (§7.4) mỗi lần gọi, kết quả tự nhiên là **mỗi translation có 1 dòng log riêng** (không phải 1 dòng log gộp chung cho "publish all") — đúng yêu cầu review, không cần thêm code ghi log riêng.
- **`CreateTranslationAction`** — tạo bản dịch mới cho 1 `article` ở locale chưa có (`status=draft` luôn, kể cả nguồn copy đang `published`, vì bản dịch mới chưa qua duyệt riêng):
  - **Slug**: **bắt buộc auto-generate** từ `title` qua `Str::slug()`, kiểm tra unique theo `(organization_id, locale, slug)` (§3.2), tự thêm hậu tố `-2`, `-3`... nếu trùng — không bắt Marketing tự nghĩ slug tay (tránh trùng giữa hàng nghìn bài).
  - **Content/product blocks**: **copy làm bản nháp khởi điểm** từ `mainTranslation()` (hoặc từ 1 translation khác do user chọn nếu `main_locale` cũng chưa có nội dung) — deep-copy từng `PostContentBlock`/`PostProductBlock` (+ items/buttons con) sang translation mới, **không link ngược** (sửa bản copy không ảnh hưởng bản gốc). Lý do chọn copy thay vì để trống: đỡ công dịch giả dựng lại cấu trúc block từ đầu (nhất là product block nhiều item/button); vẫn tôn trọng quyết định "tách riêng theo locale" vì sau khi copy, 2 bản hoàn toàn độc lập, có thể sửa/xoá riêng không đụng nhau.
    - **Quy tắc deep-copy cụ thể (để dev implement đúng, tránh đoán sai)**:
      - Mọi bản ghi copy (`PostContentBlock`, `PostProductBlock`, `PostProductBlockItem`, `PostProductBlockButton`) lấy **`id` mới** (auto-increment) và **`uuid`/`item_key`/`button_key` mới** (`Str::uuid()`/nanoid mới) — không tái dùng khoá của bản gốc, tránh đụng `unique(['block_id','item_key'])`/`unique(['block_id','button_key'])` (`docs/post-module-spec.md` §7.6/§7.7) khi 2 bản dịch tồn tại song song.
      - **`click_count`** trên mọi `PostProductBlockButton` copy **reset về `0`** — đây là số liệu đo hiệu quả CTA của riêng bản dịch mới, không kế thừa lịch sử click của bản nguồn.
      - **Media/ảnh nhúng trong `text_html`** (`<img src="...">` do FilePond/Jodit upload) **giữ nguyên URL tuyệt đối**, không tải lại/nhân bản file vật lý — 2 bản dịch cùng trỏ tới 1 file ảnh trên storage là bình thường (khác với việc nhân bản dòng DB, ảnh là tài nguyên tĩnh dùng chung được).
      - `product_id` trên `PostProductBlockItem` giữ nguyên (tham chiếu cùng 1 sản phẩm ở `Modules/Product`, không nhân bản catalog).
      - Toàn bộ deep-copy (tạo translation + copy hết block/item/button con) chạy trong **1 `DB::transaction`** — nếu copy giữa chừng lỗi (vd 1 block sai dữ liệu), rollback hết, không để lại translation "nửa vời" thiếu block.
  - `title` mặc định = title của bản nguồn (dịch giả sửa lại), không để trống.
- **`DeleteTranslationAction`** — xoá 1 bản dịch (chặn nếu là `main_locale` và còn >1 translation khác — main locale phải chuyển trước khi xoá).

### 7.7 Validation — `TranslationData` + unique slug

```php
// Modules/Post/app/Features/ArticleAuthoring/Data/TranslationData.php
class TranslationData extends Data
{
    public function __construct(
        #[Required, Max(300)] public readonly string $title,
        public readonly ?string $slug = null, // null → auto-generate từ title (CreateTranslationAction/UpdateTranslationAction)
        public readonly ?string $excerpt = null,
        public readonly ?string $seo_title = null,
        public readonly ?string $seo_description = null,
        public readonly array $blocks = [],
    ) {}
}
```
Form Request / controller validate slug thủ công (Spatie Data không tự biết `$translation->id` hiện tại để loại trừ khi update):
```php
Rule::unique('post_article_translations', 'slug')
    ->where(fn ($q) => $q->where('organization_id', tenant()->id)->where('locale', $translation->locale))
    ->ignore($translation?->id), // null khi tạo mới
```
**Không** validate unique theo `article_id` đơn thuần (đã đúng ở bản trước) — điểm review nhấn mạnh đúng: unique phải luôn đi kèm cặp `(organization_id, locale)`, vì 2 locale khác nhau của 2 article khác nhau vẫn có thể trùng slug nếu không có locale trong điều kiện.

### 7.3 Job & Command (gap có sẵn từ trước, làm ở đợt này)

```php
// PublishDueTranslationsJob — chạy mỗi phút qua Schedule::job(...)->everyMinute()
class PublishDueTranslationsJob implements ShouldQueue
{
    public function handle(): void
    {
        PostArticleTranslation::where('status', TranslationStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->chunkById(100, function ($translations) {
                foreach ($translations as $t) {
                    app(PublishArticleAction::class)->handle($t); // set performed_by = null (system)
                }
            });
    }
}
```
`routes/console.php` của `Modules/Post`: `Schedule::job(new PublishDueTranslationsJob)->everyMinute()->withoutOverlapping();`

### 7.4 Ghi log — mọi action publish/schedule/unpublish/takedown/archive/approve gọi chung

```php
// Modules/Post/app/Features/ArticleAuthoring/Actions/Concerns/LogsPublishingActions.php
trait LogsPublishingActions {
    private function log(PostArticleTranslation $t, string $action, ?string $reason = null): void {
        PostPublishingLog::create([
            'organization_id' => $t->organization_id,
            'translation_id'  => $t->id,
            'action'          => $action,
            'reason'          => $reason,
            'performed_by'    => auth()->id(), // null nếu chạy từ Job hệ thống
        ]);
    }
}
```

---

## 7.5 Cache & CDN — không áp dụng

Hệ thống hiện **không có tầng page-cache hay CDN** cho nội dung công khai: `CACHE_STORE=database` (chỉ dùng cho cache framework thông thường — session/config/query nhỏ lẻ), không có Redis tagging, không tích hợp Cloudflare/CloudFront purge nào trong codebase (các chỗ nhắc "cloudflare/cdn" hiện có đều thuộc `MediaUrlService`/Turnstile captcha, không liên quan trang bài viết). `PublicReading` (§11) render trực tiếp từ DB mỗi request, không có cache trung gian.

→ **Quyết định**: không thêm `InvalidatePostCacheJob`/CDN purge ở đợt này — không có gì để invalidate. Nếu sau này có nhu cầu cache trang công khai (vd. full-page cache hay `Cache::remember` theo `{organization_id}:post:{uuid}:{locale}`), bổ sung thành 1 spec riêng lúc đó, không đưa vào phạm vi Publishing Engine.

## 7.6 Notification khi Take down khẩn cấp

`TakeDownArticleTranslationAction` (§7.2), sau khi chuyển translation sang `archived` + ghi `post_publishing_logs` (action=`takedown`), gửi 1 notification tới:
- Mọi user có role `ceo` hoặc `ai_operator` trong cùng `organization_id` (`User::role('ceo')->get()`/`User::role('ai_operator')->get()`, lọc theo tenant — cùng pattern `SendNotificationExecutor` của `Modules/WorkflowAutomation`).
- Người publish gần nhất của translation đó (`$translation->latestPublishLog()->performed_by` — §6 — không phải `approved_by`, vì người duyệt và người bấm publish có thể khác nhau).

```php
// Modules/Post/app/Features/ArticleAuthoring/Notifications/ArticleTakenDownNotification.php
class ArticleTakenDownNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences; // app/Notifications/Concerns — kênh database+broadcast+mail theo preference user, giống Modules\WorkflowAutomation\Notifications\WorkflowNotification

    public function __construct(
        private readonly PostArticleTranslation $translation,
        private readonly string $reason,
        private readonly int $takenDownBy,
    ) {}

    protected function notificationType(): string { return 'post_article_taken_down'; }

    public function toDatabase(object $notifiable): array
    {
        return NotificationData::make(
            type: 'post_article_taken_down',
            title: "Bài viết \"{$this->translation->title}\" ({$this->translation->locale}) đã bị gỡ khẩn cấp",
            body: $this->reason,
            url: route('backend.post.articles.edit', $this->translation->article_id),
            icon: 'alert-triangle',
            severity: 'critical',
        );
    }

    public function toMail(object $notifiable): MailMessage { /* subject + reason + link, cùng cấu trúc toDatabase */ }
}
```
Kênh `broadcast` (Reverb, đã cấu hình sẵn `BROADCAST_CONNECTION=reverb`) tự động bật qua `RespectsNotificationPreferences` — không cần code thêm hạ tầng realtime, chỉ cần class Notification implement đúng `toDatabase()`. Gửi trong action bằng `Notification::send($recipients, new ArticleTakenDownNotification(...))`, dispatch **sau** khi transaction DB commit (dùng `DB::afterCommit()` hoặc queue với `ShouldQueue` — đã có sẵn).

---

## 8. Policies & Permissions

Thêm vào `app/Enums/PermissionEnum.php`:
```php
case POST_ARTICLE_UNPUBLISH = 'post_article.unpublish'; // tách riêng khỏi publish — Ops có thể publish nhưng không được gỡ bừa
```
Giữ nguyên `POST_ARTICLE_PUBLISH` cho publish/schedule/approve/archive (như policy hiện tại đang gộp). `POST_ARTICLE_UNPUBLISH` seed vào role `system_admin` + `ceo` (gỡ bài là quyết định nhạy cảm hơn publish thường).

**Xác nhận rõ (câu hỏi mở từ review): `ai_operator` KHÔNG được cấp `post_article.unpublish`.** Theo `PostPermissionSeeder` hiện tại, `ai_operator` chỉ có `post_article.view` ở mọi nơi trong module Post (không có create/edit/delete/publish) — giữ nhất quán, không có lý do nghiệp vụ nào để role này có quyền gỡ bài. `ai_operator` **vẫn là recipient của notification takedown** (§7.6) — biết tin để AI Operator có thể điều chỉnh workflow AI liên quan (vd dừng gợi ý dùng bài đã gỡ làm example), nhưng đó là quyền *xem thông báo*, tách biệt hoàn toàn khỏi quyền *thực hiện hành động*.

`PostArticlePolicy` — đổi mọi method nhận `PostArticleTranslation $translation` thay vì `PostArticle`, thêm:
```php
public function unpublish(User $user, PostArticleTranslation $t): bool { return $user->can('post_article.unpublish'); }
public function approve(User $user, PostArticleTranslation $t): bool { return $user->can('post_article.publish'); }
```
`view()` đổi điều kiện: `$translation->status === TranslationStatus::Published || $user->can('post_article.edit')`.

---

## 9. Routes

```php
// Sửa route hiện có — nhận translation UUID thay vì article UUID cho các action publishing
Route::post('articles/{article}/translations', [TranslationController::class, 'store'])->name('articles.translations.store');
Route::delete('translations/{translation}', [TranslationController::class, 'destroy'])->name('translations.destroy');

Route::post('translations/{translation}/submit', ...)->name('translations.submit');
Route::post('translations/{translation}/approve', ...)->name('translations.approve');
Route::post('translations/{translation}/publish', ...)->name('translations.publish');
Route::post('translations/{translation}/schedule', ...)->name('translations.schedule');
Route::post('translations/{translation}/cancel-schedule', ...)->name('translations.cancel-schedule');
Route::post('translations/{translation}/unpublish', ...)->name('translations.unpublish');   // body: reason
Route::post('translations/{translation}/takedown', ...)->name('translations.takedown');     // body: reason
Route::post('articles/{article}/publish-all', ...)->name('articles.publish-all');
```
`ArticleAdminController::edit()` load `$article->translations` để render tab; `create()`/`store()` chỉ tạo `PostArticle` (vỏ, chưa có translation) → redirect sang `edit` → user tạo translation đầu tiên (`main_locale`) ngay trên trang edit.

---

## 10. UI/UX

- Trang edit bài viết: tab ngang theo `config('post.locales')`, mỗi tab = 1 form độc lập (title/slug/excerpt/SEO/content blocks/product blocks) + cụm nút trạng thái (Gửi duyệt/Duyệt/Đăng ngay/Lên lịch/Gỡ bài) + badge trạng thái theo `TranslationStatus::badgeClass()`.
- Tab chưa có translation hiển thị nút "+ Tạo bản dịch [locale]" thay vì form.
- Nút "Publish All Languages" chỉ enable khi **có ít nhất 1 translation ở trạng thái `approved`**.
- Dialog Unpublish/Takedown bắt buộc textarea `reason` (client-side required, server validate lại).

---

## 11. Fallback & SEO công khai (áp dụng khi `PublicReading` được triển khai — hiện chưa có route)

### 11.1 Route binding — vì sao bắt buộc có `{locale}` tường minh trong URL

`post_article_translations` unique theo `(organization_id, locale, slug)` (§3.2) — nghĩa là **2 locale khác nhau được phép trùng slug** (vd `vi` và `en` cùng đặt slug `gioi-thieu`). Route kiểu cũ `Route::get('{article:slug}')` (route-model-binding đơn giản trên 1 model, như `docs/post-module-spec.md` §12 gốc dự kiến) **không còn xác định duy nhất 1 bản dịch** nếu chỉ dựa vào `slug` — bắt buộc phải có `locale` là 1 segment riêng trong URL:

```php
// routes/web.php — PublicReading (Phase 16)
Route::prefix('{locale}/bai-viet')->name('post.public.')->group(function () {
    Route::get('/', [PublicCategoryController::class, 'index'])->name('home');
    Route::get('danh-muc/{category:slug}', [PublicCategoryController::class, 'show'])->name('category');
    Route::get('{translation:slug}', [PublicArticleController::class, 'show'])->name('article');
});
```

Vì Laravel implicit route-model-binding chỉ nhận **giá trị của đúng 1 segment** làm khoá tra cứu (không tự động biết `locale` là điều kiện lọc thêm), `PostArticleTranslation` cần override `resolveRouteBinding()` để đọc segment `locale` từ chính route hiện tại (route parameter `locale` đã được Laravel gán trước khi resolve segment kế tiếp, nên đọc được qua `request()->route('locale')`):

```php
// Modules/Post/app/Models/PostArticleTranslation.php
public function resolveRouteBinding($value, $field = null): ?self
{
    // Bảo vệ route admin (`translation:uuid`, dùng getRouteKeyName() mặc định) — override bên dưới
    // CHỈ áp dụng cho binding qua `slug` ở route công khai (§11.1), không được đụng vào route nội bộ.
    if ($field !== 'slug') {
        return parent::resolveRouteBinding($value, $field);
    }

    $locale = request()->route('locale');

    return $this->where('slug', $value)
        ->where('locale', $locale)
        ->where('status', TranslationStatus::Published) // route công khai chỉ bind bài đã publish — 404 tự nhiên nếu không
        ->first();
}
```
`ArticleAdminController` (nội bộ, dùng `translation:uuid`) đi qua nhánh `parent::resolveRouteBinding()` ở trên, hành vi giữ nguyên như mọi model khác (`getRouteKeyName()` vẫn là `uuid`) — override chỉ có tác dụng khi route công khai gọi binding bằng `{translation:slug}`.

### 11.2 Fallback

Thứ tự resolve khi user truy cập `{locale}/bai-viet/{slug}`:
1. Translation đúng `locale` đang `published` → trả về (đã tự lọc trong `resolveRouteBinding()` ở trên).
2. Không khớp (404 tự nhiên từ binding) → **fallback tầng controller**: tra `PostArticle` qua `slug` bất kỳ locale nào đang published → nếu tìm thấy bản `main_locale` đã `published`, redirect 302 sang URL đúng locale đó kèm `<link rel="canonical">` trỏ chính nó, thêm `hreflang` cho mọi locale đã `published`.
3. Không có bản nào `published` ở bất kỳ locale nào → **404** (không fallback sang bản `draft`/`unpublished` cho khách vãng lai; nếu user có `post_article.edit`, cho phép xem qua link riêng `?preview=1` kèm `translation_uuid`, không phải qua route công khai chuẩn theo slug).

`sitemap.xml` theo locale: chỉ liệt kê translation `status=published`, route theo đúng `{locale}/bai-viet/{slug}`.

---

## 12. Testing & Acceptance Criteria (bổ sung cho §18 của `docs/post-module-spec.md`)

1. Tạo bài viết → tạo 2 translation (`vi`, `en`) → publish riêng `vi`, để `en` ở `draft` → trang công khai `vi` hiển thị, `en` 404.
2. Approve `en` → Schedule `en` cho +1 phút → chờ job chạy → `en` tự chuyển `published`, `post_publishing_logs` có dòng `action=publish`, `performed_by=null`.
3. Unpublish `vi` không kèm `reason` → bị chặn validate (400), có `reason` → chuyển `unpublished`, trang công khai `vi` trả 404 nhưng dữ liệu vẫn còn trong DB (không xoá).
4. Cố gắng `publish` khi đang ở `draft` (bỏ qua `submitted`/`approved`) → action ném `InvalidTransitionException`, không đổi state.
5. Xoá `PostArticle` (soft-delete) → mọi `translation` + `content_blocks`/`product_blocks` cascade xoá theo (cascade qua `article_id`/`translation_id`), không mồ côi dữ liệu.
6. Migration backfill chạy trên dữ liệu demo hiện có (`PostDatabaseSeeder`) không mất dữ liệu — mọi `PostArticle` cũ có đúng 1 `translation` ở `main_locale`, `content_blocks` cũ trỏ đúng `translation_id` mới.
7. Tạo 1 `PostArticle` với 2 translation: `vi` đã `published`, `en` đang `scheduled` (`scheduled_at` = quá khứ hoặc vừa qua) → chạy `schedule:run` (kích hoạt `PublishDueTranslationsJob`) → `en` chuyển `published` với `published_at` mới set, **`vi` giữ nguyên `published`** (không bị job động vào vì không thoả điều kiện `status=scheduled`) → `post_publishing_logs` có đúng **1 dòng mới** (action=`publish`, `translation_id` = id của `en`, `performed_by=null`), không sinh dòng nào cho `vi`.
8. `TakeDownArticleTranslationAction` trên 1 translation đang `published` bởi user A → chạy xong, user có role `ceo`/`ai_operator` trong cùng tổ chức và user A (người publish gần nhất) đều nhận được notification (kiểm `Notification::fake()` → assert đúng danh sách recipient, `toDatabase()` chứa đúng `reason` + `url` trỏ `backend.post.articles.edit`); user thuộc tổ chức khác **không** nhận được dù cùng role.

---

## 13. Phased Implementation Plan (nối tiếp `docs/post-module-spec.md` §17, đánh số tiếp Phase 12+)

| Phase | Nội dung | Output kiểm tra được |
|---|---|---|
| **Phase 12a — Schema (nullable)** | Migration #1-#2 (§3.1-3.3): tạo `post_article_translations`, `main_locale`, cột `translation_id` nullable trên 2 bảng con, enum `TranslationStatus`, model `PostArticleTranslation` | `php artisan migrate` sạch trên DB có dữ liệu demo; chưa đổi hành vi hiện tại (article cũ vẫn còn cột title/status) |
| **Phase 12b — Backfill** | `BackfillPostTranslationsCommand` (§3.5) chạy `--dry-run` rồi chạy thật | 100% `post_articles` có translation tương ứng; `post_content_blocks`/`post_product_blocks` không còn dòng `translation_id IS NULL` |
| **Phase 12c — Finalize schema** | Migration #4-#5 (§3.3, §3.4): NOT NULL + drop cột cũ, tạo `post_publishing_logs` | `php artisan migrate` sạch; xoá được các cột cũ khỏi `post_articles` không lỗi FK |
| **Phase 13 — Actions & Policy** | Sửa 4 action cũ nhận `Translation`, thêm action mới (§7.2, gồm `CreateTranslationAction` copy-content + auto-slug), `TranslationData`+validation (§7.7), `LogsPublishingActions`, permission `post_article.unpublish` (không cấp `ai_operator`), sửa `PostArticlePolicy`, model helper (§6: `scopePublished`/`isPublishable`/`latestPublishLog`/`scopeWithMainTranslation`) | Test transition hợp lệ/không hợp lệ đều đúng (§12 mục 4); tạo translation mới → slug tự sinh, content copy từ main_locale, sửa không ảnh hưởng bản gốc |
| **Phase 14 — Job & Scheduler** | `PublishDueTranslationsJob`, đăng ký `Schedule::job()` | Tạo translation `scheduled` quá khứ → chạy `schedule:run` → tự chuyển `published` (§12 mục 7) |
| **Phase 15 — Admin UI đa ngôn ngữ** | Tab locale trong `edit.blade.php`, dialog Unpublish/Takedown có `reason`, nút Publish All, notification takedown (§7.6) | Thao tác đủ luồng §12 mục 1-3, 8 qua UI thật |
| **Phase 16 — Public reading + fallback** | Route `{locale}/bai-viet/{slug}` (§11.1 — chưa từng làm, Phase 9-10 cũ của `docs/post-module-spec.md` cũng chưa xong), `resolveRouteBinding()` theo locale, fallback §11.2, hreflang, sitemap theo locale | §12 mục 1 pass qua trình duyệt thật; truy cập slug đúng nhưng sai locale → 302 redirect đúng locale có bản published |

---

## 14. Edge Cases & Risks (bổ sung theo review)

| Tình huống | Rủi ro | Xử lý |
|---|---|---|
| 2 editor sửa 2 translation khác nhau (`vi`/`en`) của cùng 1 `article` cùng lúc | Race condition ở phần dữ liệu **dùng chung** (categories/tags trên `post_articles`) nếu cả 2 form cùng submit `category_ids` — người sau ghi đè người trước | Chấp nhận "last write wins" cho categories/tags (giống hành vi hiện tại của `UpdateArticleAction`, không đổi) — vì mỗi translation là 1 form riêng, chỉ phần *shared* mới có xung đột, tần suất thấp; không cần optimistic locking cho MVP |
| `CreateTranslationAction` copy content khi bản nguồn đang có `post_product_blocks` tham chiếu sản phẩm đã bị xoá/`discontinued` ở `Modules/Product` | Bản dịch mới copy nguyên `product_id` đã không còn hợp lệ để hiển thị | Không chặn khi copy (là bản nháp, chưa publish) — validate `product_id` hợp lệ vẫn chạy bình thường ở bước publish/save như luồng hiện tại, không cần thêm logic riêng |
| Xoá `main_locale` translation khi chỉ còn đúng 1 translation (không phải >1) | `DeleteTranslationAction` chặn nếu là main_locale và còn >1 bản khác (§7.2) — nhưng nếu **chỉ có đúng 1** bản (chính là main_locale) thì sao? | Cho phép xoá — kéo theo xoá cả `PostArticle` luôn (không thể tồn tại "vỏ" không có translation nào). Xem code cụ thể ngay bên dưới bảng. |
| `PublishDueTranslationsJob` chạy trùng lặp (2 worker cùng lúc, hoặc job bị retry) | Publish 2 lần → có thể ghi 2 dòng `post_publishing_logs` cho cùng 1 lần "tới hạn" | `PublishArticleAction` kiểm `canTransitionTo()` trước khi update (đã có ở §7.1) — lần chạy thứ 2 thấy `status` đã là `published` (không còn `scheduled`) → transition `Published→Published` không hợp lệ theo `canTransitionTo()` → action no-op, không ghi log trùng. `withoutOverlapping()` ở scheduler (§7.3) giảm khả năng này xảy ra ngay từ đầu |
| Import hàng loạt (nếu sau này có script import content cũ) sinh trùng slug trong cùng `(org, locale)` | Vi phạm unique constraint §3.2, insert lỗi | Không nằm trong phạm vi đợt này (chưa có tính năng import) — nếu làm sau, tái dùng logic `uniqueSlug()` auto-suffix `-2`/`-3` đã có sẵn trong `CreateArticleAction`/`CreateTranslationAction`, không viết logic riêng |
| Timezone của `scheduled_at`/`published_at` khi tổ chức ở múi giờ khác server | Job so sánh `scheduled_at <= now()` dùng giờ server (UTC hoặc theo `config('app.timezone')`) | Không đổi so với hành vi hiện tại của `ScheduleArticleAction` (đã lưu UTC theo config Laravel mặc định) — không phải vấn đề mới phát sinh từ multi-language, ngoài phạm vi spec này |

**Code cụ thể cho dòng "xoá translation cuối cùng"** ở trên — đặt điều kiện này **trước** điều kiện chặn "main_locale mà còn >1 bản khác" (§7.2), vì trường hợp "chỉ còn đúng 1 bản" luôn thoả main_locale (không có bản nào khác để so sánh):
```php
// Modules/Post/app/Features/ArticleAuthoring/Actions/DeleteTranslationAction.php
public function handle(PostArticleTranslation $translation): void
{
    if ($this->isLastTranslation($translation)) {
        app(DeleteArticleAction::class)->handle($translation->article);
        return;
    }

    // ... chặn nếu $translation->locale === $translation->article->main_locale
    //     và còn >1 translation khác (logic cũ, §7.2) ...

    $translation->delete();
}

private function isLastTranslation(PostArticleTranslation $translation): bool
{
    return $translation->article->translations()->count() === 1;
}
```

---

**File này hợp nhất với `docs/post-module-spec.md`** — mọi phần không nhắc tới ở đây (Category tree, Product CTA Box template/sanitize/click-tracking) giữ nguyên như spec gốc, không đổi.
