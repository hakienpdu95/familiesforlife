# Post Module — Versioning & History (Lịch sử chỉnh sửa bài viết) — Đặc tả kỹ thuật

> **Trạng thái:** Đặc tả (chưa triển khai) — nối tiếp `docs/post-module-spec.md` (kiến trúc gốc) và
> `spec/PublishingEngine_Technical_Specification.md` v2.0 (mô hình `PostArticleTranslation` + lifecycle).
> Đọc 2 tài liệu đó trước khi implement — spec này **không lặp lại** phần đã đặc tả (ERD gốc, enum
> `TranslationStatus`, quy trình publish...), chỉ đặc tả phần bổ sung: lưu lịch sử phiên bản nội dung,
> xem lại, so sánh (diff) và khôi phục (restore).
> **Spec version:** 1.1 — 2026-07-18 (review nội bộ: đơn giản hoá diff v1, chuyển việc ghi version ra
> queue sau commit để không kéo dài transaction chính, làm rõ hành vi khi translation bị xoá, thêm
> giới hạn số version/config retention, thêm cấu trúc response API, cảnh báo concurrent-edit — xem
> changelog cuối mỗi mục liên quan).
> **Module:** `Modules/Post`, feature slice mới `Features/VersionHistory`.

---

## 0. Quyết định thiết kế đã chốt (tóm tắt, chi tiết ở §4/§9/§10/§12)

| Quyết định | Vì sao |
|---|---|
| Đơn vị version hoá là **`PostArticleTranslation`** (1 bản dịch/1 locale), không phải `PostArticle` | Nội dung thật (title/slug/content blocks/product blocks) nằm ở translation từ khi `PublishingEngine` v2.0 tách đa ngôn ngữ (xem `spec/PublishingEngine_Technical_Specification.md` §3). 2 locale của cùng 1 bài có lịch sử **độc lập**. |
| Snapshot lưu dạng **JSON** trong 1 bảng mới `post_article_versions`, **không** tách quan hệ theo từng bảng con | Duy nhất chỗ lệch nguyên tắc "No JSON storage" của module (`docs/post-module-spec.md` §4) — biện minh ở §4 bên dưới; đây là **kho lưu trữ bất biến** (immutable archive), không phải dữ liệu sống cần JOIN/lọc, nên không phạm tinh thần nguyên tắc gốc (nguyên tắc đó nhắm tới dữ liệu *editable*). |
| Snapshot lại toàn bộ tại **2 thời điểm**: mỗi lần **lưu** (`UpdateTranslationAction`) và mỗi lần **publish** (`PublishArticleAction`) | Đủ để "xem lại/so sánh/khôi phục" mà không tạo version ở các transition không đổi nội dung (submit/approve/schedule chỉ đổi `status`, không đổi content) — tránh phình bảng vô ích. |
| Đóng gói snapshot **đồng bộ** (đọc dữ liệu vừa ghi, cần cho tính đúng đắn) nhưng **ghi bảng `post_article_versions` bất đồng bộ qua queue**, dispatch sau khi transaction chính commit | Tách phần "cần thiết cho request" (lưu bài) khỏi phần "sổ sách phụ trợ" (lịch sử) — không kéo dài transaction/lock trên `post_article_translations` chỉ để hash+ghi version. Chi tiết + cách tránh race điều kiện do async ở §9. |
| Khôi phục (**restore**) tạo **version mới**, không ghi đè/xoá version cũ | Lịch sử luôn append-only, giống `post_publishing_logs` — khôi phục nhầm vẫn có đường quay lại. |
| Khôi phục **không đổi `status`/`published_at`** của translation | Restore chỉ là "nội dung", không phải "trạng thái xuất bản" — tránh 1 thao tác vô tình unpublish/publish lại bài. |
| **Không thêm permission mới** — tái dùng `post_article.view` (xem lịch sử/so sánh) và `post_article.edit` (khôi phục) | Đúng nguyên tắc tránh over-engineering; lịch sử là tính năng phụ trợ của màn soạn thảo, không phải 1 domain quyền riêng. |
| Phạm vi v1 chỉ version hoá field **cấp translation** (title/slug/excerpt/seo/disclosure/cta) + content/product blocks | Field cấp `PostArticle` (cover_image_url/format/categories/tags/province/ward) đã có audit qua Spatie Activitylog (`LogsActivity` trên `PostArticle`) — xem §3, không làm trùng. |
| **Diff v1 chỉ so theo field scalar + so block theo vị trí** (thêm/xoá/thay đổi), **không** LCS, **không** word-level diff, **không** thêm dependency composer mới | Review nội bộ đánh giá LCS + diff mức từ là over-engineering cho v1 — độ phức tạp không tương xứng giá trị mang lại ở bản đầu. Diff tinh vi hơn (nếu cần) để lại Phase sau, xem §12. |
| Xoá translation (`DeleteTranslationAction`) **không** kéo theo mất lịch sử trong luồng bình thường | `post_article_translations` có `SoftDeletes` — `$translation->delete()` chỉ là `UPDATE deleted_at`, FK `cascadeOnDelete()` chỉ kích hoạt khi có `DELETE` SQL thật (`forceDelete()`), hiện **không có** chỗ nào trong `Modules/Post` gọi `forceDelete()` trên translation. Xem §11 để biết rủi ro còn lại. |

---

## 1. Bối cảnh & mục tiêu

Hiện tại `Modules/Post` có 2 lớp "biết chuyện gì đã xảy ra" nhưng **không lớp nào cho xem lại nội dung cũ hay khôi phục**:

1. **Spatie Activitylog** (`LogsActivity` trait trên `PostArticle` và `PostArticleTranslation`, xem `Modules/Post/app/Models/PostArticle.php:29` và `PostArticleTranslation.php:27`) — ghi **field nào đổi, từ giá trị gì sang giá trị gì** (dirty attributes) mỗi lần `update()`. Không bắt được thay đổi ở `post_content_blocks`/`post_product_blocks` (2 model đó không dùng trait này), và không có khái niệm "phiên bản đầy đủ tại thời điểm X" hay nút khôi phục.
2. **`post_publishing_logs`** (`PublishingEngine_Technical_Specification.md` §3.4) — audit **hành động lifecycle** (publish/schedule/unpublish/takedown/archive/approve), không lưu nội dung.

Marketing biên tập bài viết qua block-composer (Jodit, xem `docs/post-module-spec.md` §9) — sửa nhầm, xoá nhầm 1 đoạn, hoặc muốn so sánh "bản trước khi Ops yêu cầu sửa" với "bản hiện tại" đều không có cách nào ngoài hỏi lại nhau hoặc tra `activity_log` thô (không đọc được thành bài hoàn chỉnh). Mục tiêu:

1. Tự động lưu lại **toàn bộ nội dung** (không chỉ field lẻ) mỗi lần lưu bài hoặc publish.
2. Cho xem danh sách lịch sử theo timeline, xem lại 1 phiên bản cũ (preview render như trang thật).
3. So sánh 2 phiên bản bất kỳ (kể cả "hiện tại" vs 1 bản cũ) — diff theo field và theo từng block nội dung.
4. Khôi phục 1 phiên bản cũ về làm nội dung hiện hành, an toàn (không mất version nào, không đổi trạng thái xuất bản).

---

## 2. Phạm vi (Scope Boundary)

### 2.1 Trong phạm vi

1. Bảng lưu snapshot `post_article_versions` (1 dòng = 1 phiên bản của 1 `PostArticleTranslation`).
2. Tự động tạo version khi: lưu bài (`UpdateTranslationAction`), publish (`PublishArticleAction`/`PublishAllTranslationsAction`), khôi phục (chính nó cũng tạo 1 version mới) — ghi bất đồng bộ qua queue, xem §9.
3. API/UI: danh sách lịch sử, xem preview 1 phiên bản, so sánh 2 phiên bản (field diff + block diff đơn giản), khôi phục.
4. Diff tại 2 mức, **đơn giản ở v1**: **field scalar** (title/slug/excerpt/seo_title/seo_description/disclosure_text/cta_text/cta_url — so "trước/sau") và **block nội dung theo vị trí** (thêm/xoá/thay đổi — không diff mức từ, xem §12).
5. Giới hạn số version lưu trữ (config, tắt mặc định) — §10.
6. Cảnh báo "đã bị sửa bởi người khác kể từ khi mở trang" khi mở form soạn thảo — §13.4.

### 2.2 Ngoài phạm vi (cố ý không làm ở đây)

| Nghiệp vụ | Vì sao không làm ở đây |
|---|---|
| Version hoá field cấp `PostArticle` (cover_image_url, format, categories, tags, province/ward, sponsorship) | Đã có Spatie Activitylog audit từng field (§1); các field này không thuộc "nội dung bài viết theo locale" — tách riêng khỏi mối quan tâm chính (lịch sử **soạn thảo**). Có thể bổ sung sau nếu nhu cầu phát sinh, không phá schema `post_article_versions` (thêm 1 bảng `post_article_shell_versions` riêng nếu cần, không nhét chung). |
| Khoá bản ghi khi chỉnh sửa đồng thời (optimistic/pessimistic locking), chặn ghi đè | Không nằm trong yêu cầu; mỗi lần lưu vẫn tạo version riêng nên **không mất dữ liệu** dù 2 người ghi đè nhau. v1 chỉ **cảnh báo** (không chặn) — xem §13.4 và Edge Cases §11. |
| Diff mức từ (word-level highlight) trong HTML | Cố tình để lại Phase sau — xem §12 (quyết định đã chốt: không thêm dependency diff library ở v1). |
| Diff nhị phân/ảnh (cover_image_url, ảnh trong content) | Content dùng URL tham chiếu, không lưu file nhị phân ở đây; so sánh ảnh (thay ảnh khác) hiển thị đơn giản là "ảnh cũ → ảnh mới" (2 thumbnail cạnh nhau), không dựng diff pixel. |
| Version hoá `post_publishing_logs`/comment/review-note | Đã có bảng riêng, không thuộc "nội dung". |
| `parent_version_id` (lineage khôi phục), "Restore as Draft", export lịch sử ra PDF | Cải tiến dài hạn, không cần cho v1 — `restored_from_version_id`/lineage khôi phục xem §18.1, "Restore as Draft"/export xem §17. |

---

## 3. Quan hệ với các cơ chế audit đã có

| Cơ chế | Ghi gì | Khi nào | Đọc lại thành bài hoàn chỉnh? | Khôi phục được? |
|---|---|---|---|---|
| Spatie Activitylog (`activity_log` table) | Field lẻ đổi giá trị (`logOnlyDirty`) trên `PostArticle`/`PostArticleTranslation` | Mọi `update()` | Không (chỉ list "field: cũ → mới") | Không |
| `post_publishing_logs` | Hành động lifecycle (publish/schedule/unpublish...) + người thực hiện + lý do | Mỗi lần gọi Action lifecycle tương ứng | Không (không có nội dung) | Không |
| **`post_article_versions` (mới, spec này)** | Snapshot đầy đủ title/slug/excerpt/seo/content blocks/product blocks | Mỗi lần `UpdateTranslationAction` / publish / restore | **Có** — render lại y hệt trang thật | **Có** |

3 cơ chế **bổ sung cho nhau, không thay thế**: màn "Lịch sử phiên bản" (spec này) là nơi editor xem/so sánh/khôi phục *nội dung*; Activitylog và publishing-log vẫn giữ nguyên vai trò audit tuân thủ (ai bấm publish lúc nào, ai đổi SEO title...).

---

## 4. Nguyên tắc kiến trúc & biện minh lệch nguyên tắc "No JSON storage"

`docs/post-module-spec.md` §4 quy định "No JSON storage" cho dữ liệu **sống** (cấu hình nhiều sản phẩm/nhiều nút CTA phải là bảng quan hệ để JOIN/lọc/báo cáo hiệu quả — lý do nêu ở `docs/post-module-spec.md` §7.6). `post_article_versions.snapshot` (JSON) **không vi phạm tinh thần đó** vì:

1. **Không bao giờ JOIN/lọc theo field bên trong JSON** — snapshot chỉ đọc nguyên khối để render preview hoặc diff ở tầng ứng dụng, không truy vấn "tìm mọi version có sản phẩm X" (nếu sau này cần, đã có nguồn sự thật hiện hành ở `post_product_block_items` — không cần query ngược từ lịch sử).
2. **Bất biến sau khi ghi** (append-only, giống `post_publishing_logs` không có `updated_at`) — không có rủi ro JSON "lệch" so với bảng quan hệ theo thời gian như dữ liệu sống.
3. Tái tạo lại đầy đủ quan hệ (product blocks/items/buttons) thành các bảng con phiên bản hoá riêng (`post_content_block_versions`, `post_product_block_versions`...) sẽ nhân 3-4 bảng cho 1 tính năng phụ trợ, tăng độ phức tạp restore (phải JOIN dựng lại cây quan hệ) **không đổi lại lợi ích thực tế** nào so với JSON — snapshot chỉ cần "đọc lại nguyên trạng" và "đưa nguyên trạng đó cho `SyncContentBlocksAction` xử lý lại" (xem §9.3).

**Cấu trúc `snapshot` JSON** — cố tình dùng **đúng shape** mà `SyncContentBlocksAction::handle()` đã nhận làm tham số `$blocks` (xem `Modules/Post/app/Features/ArticleAuthoring/Actions/SyncContentBlocksAction.php:41`), để restore không cần viết thêm tầng chuyển đổi:

```json
{
  "translation": {
    "title": "...",
    "slug": "...",
    "excerpt": "...",
    "seo_title": "...",
    "seo_description": "...",
    "disclosure_text": "...",
    "cta_text": "...",
    "cta_url": "..."
  },
  "blocks": [
    { "type": "text", "text_html": "<p>...</p>" },
    {
      "type": "product",
      "block_uuid": "…",
      "template": "single_card",
      "heading": "…",
      "items": [
        {
          "item_key": "…", "product_id": 12,
          "title_override": null, "price_label_override": null,
          "description_override": null, "image_url_override": null,
          "buttons": [
            { "button_key": "…", "url_type": "use_product_link", "product_link_type": "shopee", "target": "_blank", "style": "primary" }
          ]
        }
      ],
      "block_buttons": []
    }
  ]
}
```
Ghi chú: **không** lưu `status`/`published_at`/`view_count` trong `translation` — đúng quyết định §0 (restore không đụng lifecycle). `sort_order` không cần lưu tường minh trong `blocks` vì thứ tự phần tử trong mảng JSON **chính là** `sort_order` (giống input gốc `SyncContentBlocksAction` nhận).

---

## 5. Data Model — ERD

```
PostArticleTranslation
      │ (1:n)
      └──< PostArticleVersion  [version_number, trigger, snapshot(json), content_hash, block_count, char_count, created_by, created_at]
```

Không bảng con nào khác — mọi thứ nằm trong `snapshot`.

---

## 6. Migration

### 6.1 `post_article_versions`

```php
Schema::create('post_article_versions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('translation_id')->constrained('post_article_translations')->cascadeOnDelete();
    $table->unsignedInteger('version_number'); // tăng dần RIÊNG theo từng translation (không phải id toàn cục)
    $table->string('trigger', 20);             // VersionTrigger: save|publish|restore
    $table->json('snapshot');                  // cấu trúc §4
    $table->string('title_snapshot', 300);     // denormalize title để render danh sách không cần decode JSON
    $table->char('content_hash', 64);           // sha256(snapshot) — so trùng lặp trước khi ghi, xem §9
    $table->unsignedInteger('char_count')->default(0);   // tổng độ dài text (strip_tags) các block text
    $table->unsignedSmallInteger('block_count')->default(0); // tổng số block (text + product) — để hiển thị delta cả khi chỉ đổi product block (không đổi ký tự text), xem §9.1/§13.3
    $table->foreignId('restored_from_version_id')->nullable()->constrained('post_article_versions')->nullOnDelete();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('created_at')->useCurrent();

    $table->unique(['translation_id', 'version_number'], 'uq_post_version_translation_number');
    $table->index(['translation_id', 'created_at'], 'idx_post_version_translation_created');
    $table->index(['translation_id', 'content_hash'], 'idx_post_version_translation_hash');
});
```

Không có `updated_at` — append-only, đúng tiền lệ `post_publishing_logs` (`Modules/Post/app/Models/PostPublishingLog.php:14`). Không soft-delete — version không tự xoá được qua UI ở v1 (chỉ prune tự động nếu bật, xem §10).

`restored_from_version_id` (self-reference, nullable, `nullOnDelete()`) — chỉ set khi `trigger=restore`, trỏ tới version nguồn đã được khôi phục (lineage). Quyết định checknote v1.1 (§18.1): thêm ngay từ migration v1 vì chi phí gần như bằng 0 (1 cột nullable, không ảnh hưởng dữ liệu/luồng ghi hiện có) trong khi lợi ích tra cứu lineage về sau là rõ ràng — không phải chờ 1 migration riêng thêm cột sau này. `nullOnDelete()` (thay vì `cascadeOnDelete()`) vì xoá version nguồn (do prune §10) không nên kéo theo xoá version `restore` đang tồn tại — chỉ mất liên kết lineage, không mất version.

### 6.2 Vì sao `version_number` riêng theo translation thay vì dùng `id`

UI hiển thị "Phiên bản #12" — số thứ tự phải **liên tục theo từng bài/locale** (dễ hiểu, dễ nói chuyện với đồng nghiệp: "so bản #8 với #12"), không phải id toàn cục nhảy lộn xộn giữa nhiều bài viết khác nhau đang được sửa song song. Tính bằng:
```php
$next = $translation->versions()->lockForUpdate()->max('version_number') + 1;
```
trong transaction riêng của job ghi version (khoá `SELECT ... FOR UPDATE` ở mức translation tránh 2 job ghi trùng số hiếm khi xảy ra đồng thời, xem §11) — **không** phải transaction của request lưu bài (xem §9).

### 6.3 Ước lượng dung lượng

Bài viết trung bình (theo demo seeder hiện có, `docs/post-module-spec.md` §13) ~2-5KB HTML/content-block. Giả định mỗi bài sửa trung bình 15 lần trước khi ổn định → ~50-75KB/bài/locale cho toàn bộ lịch sử. Không cần nén (`gzcompress`) ở v1 — chỉ cân nhắc nếu dung lượng trung bình vượt ngưỡng lớn hơn nhiều so với thực tế hiện tại. Nếu tổ chức lo phình bảng, dùng `max_versions_per_translation`/`retention_days` ở §10.

---

## 7. Enum

```php
// Modules/Post/app/Enums/VersionTrigger.php
enum VersionTrigger: string
{
    case Save    = 'save';     // UpdateTranslationAction — mỗi lần bấm "Cập nhật bài viết"
    case Publish = 'publish';  // PublishArticleAction / PublishAllTranslationsAction — chốt "bản đã lên sóng"
    case Restore = 'restore';  // RestoreArticleVersionAction — khôi phục 1 bản cũ

    public function label(): string
    {
        return match ($this) {
            self::Save    => 'Lưu chỉnh sửa',
            self::Publish => 'Xuất bản',
            self::Restore => 'Khôi phục từ phiên bản cũ',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Save    => 'badge-ghost',
            self::Publish => 'badge-success',
            self::Restore => 'badge-warning',
        };
    }

    /** Version thuộc trigger này KHÔNG bao giờ bị auto-prune xoá (§10) — mốc tuân thủ/audit. */
    public function isProtectedFromPruning(): bool
    {
        return $this !== self::Save;
    }
}
```

---

## 8. Model

```php
// Modules/Post/app/Models/PostArticleVersion.php
namespace Modules\Post\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Post\Enums\VersionTrigger;

/** Append-only — không sửa, không soft-delete, không có updated_at. */
class PostArticleVersion extends Model
{
    const UPDATED_AT = null;

    protected $table = 'post_article_versions';

    protected $fillable = [
        'translation_id', 'version_number', 'trigger',
        'snapshot', 'title_snapshot', 'content_hash', 'char_count', 'block_count',
        'restored_from_version_id', 'created_by',
    ];

    protected $casts = [
        'trigger'  => VersionTrigger::class,
        'snapshot' => 'array',
    ];

    public function translation(): BelongsTo
    {
        return $this->belongsTo(PostArticleTranslation::class, 'translation_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /** Chỉ non-null khi trigger=restore — version nguồn đã được khôi phục (§6.1, §18.1). */
    public function restoredFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'restored_from_version_id');
    }

    public function textBlocks(): array
    {
        return array_values(array_filter($this->snapshot['blocks'] ?? [], fn ($b) => ($b['type'] ?? null) === 'text'));
    }

    public function productBlocks(): array
    {
        return array_values(array_filter($this->snapshot['blocks'] ?? [], fn ($b) => ($b['type'] ?? null) === 'product'));
    }
}
```

`PostArticleTranslation` bổ sung quan hệ:
```php
public function versions(): HasMany
{
    return $this->hasMany(PostArticleVersion::class, 'translation_id')->orderByDesc('version_number');
}

public function latestVersion(): ?PostArticleVersion
{
    return $this->versions()->first();
}
```

---

## 9. Snapshot capture — khi nào tạo version mới, và vì sao chạy bất đồng bộ

### 9.1 Vấn đề với cách làm đồng bộ hoàn toàn (bản v1.0 của spec này)

Bản đầu của spec này gọi toàn bộ "đọc content blocks → hash → khoá `max(version_number)` → insert" **trong cùng transaction** với `UpdateTranslationAction`. Review nội bộ chỉ ra 2 vấn đề:

1. Với bài có nhiều product block (tối đa 3 khối × 7 item × 5 nút, `docs/post-module-spec.md` §7.6/§7.7), phần build snapshot + `json_encode` + `hash()` + `INSERT` cộng thêm vào **thời gian giữ lock** trên `post_article_translations`/`post_content_blocks` trong transaction chính — không cần thiết cho việc lưu bài thành công, chỉ phục vụ tính năng phụ trợ (lịch sử).
2. Việc này chạy trên request thread → cộng thêm latency phản hồi cho editor mỗi lần bấm "Cập nhật bài viết".

**→ Quyết định:** tách làm 2 bước rõ ràng:

| Bước | Chạy khi nào | Vì sao |
|---|---|---|
| **(a) Đóng gói snapshot** (đọc `contentBlocks`/`productBlocks.items.buttons` vừa ghi, dựng mảng `snapshot`) | **Đồng bộ**, ngay trong transaction của `UpdateTranslationAction`/`PublishArticleAction`, ngay sau khi ghi xong | Bắt buộc đồng bộ — nếu trì hoãn đọc dữ liệu ra khỏi transaction, 1 request lưu bài **khác** có thể ghi đè trước khi ta kịp đọc, khiến snapshot bị **sai dữ liệu** (chụp nhầm nội dung của lần lưu sau gán cho lần lưu trước — race condition thật sự, không phải lý thuyết, vì `post_content_blocks` bị xoá-tạo-lại toàn bộ mỗi lần lưu). Việc đọc lại vài chục dòng đã có sẵn trong cùng transaction (đã commit ghi, đọc lại rẻ, có index theo `translation_id`) không đáng kể so với I/O của chính request lưu bài. |
| **(b) Hash + khoá version_number + INSERT `post_article_versions`** | **Bất đồng bộ**, dispatch 1 Job (`ShouldQueue`) mang theo **snapshot đã đóng gói sẵn ở bước (a)** (không phải chỉ `translation_id`), gọi qua `DB::afterCommit()` — đúng pattern đã dùng ở `TakeDownArticleTranslationAction.php:44` | Đây là phần có thể trì hoãn an toàn: dữ liệu cần ghi đã "đóng băng" từ bước (a) nên **không có race** dù job chạy trễ (không đọc lại state hiện tại của translation tại thời điểm job chạy). Chỉ trì hoãn phần sổ sách (hash/lock-số-thứ-tự/insert), không trì hoãn phần cần đúng ngay lúc lưu. |

### 9.2 `CreateArticleVersionAction` — build snapshot (đồng bộ, gọi trong transaction chính)

```php
// Modules/Post/app/Features/VersionHistory/Actions/CreateArticleVersionAction.php
namespace Modules\Post\Features\VersionHistory\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\ContentBlockType;
use Modules\Post\Enums\VersionTrigger;
use Modules\Post\Jobs\PersistArticleVersionJob;
use Modules\Post\Models\PostArticleTranslation;

class CreateArticleVersionAction
{
    use AsAction;

    /** Đóng gói snapshot NGAY (đồng bộ) rồi dispatch job ghi DB sau khi transaction hiện tại commit. */
    public function handle(
        PostArticleTranslation $translation,
        VersionTrigger $trigger,
        ?int $userId,
        ?int $restoredFromVersionId = null, // chỉ truyền khi $trigger === Restore (§9.5, §18.1)
    ): void {
        $snapshot = $this->buildSnapshot($translation);

        \Illuminate\Support\Facades\DB::afterCommit(
            fn () => PersistArticleVersionJob::dispatch(
                $translation->id, $trigger, $userId, $snapshot,
                $translation->title, // title_snapshot — đóng băng cùng lúc, tránh đọc lại translation trong job
                $restoredFromVersionId,
            )
        );
    }

    private function buildSnapshot(PostArticleTranslation $translation): array
    {
        $translation->loadMissing(['contentBlocks', 'productBlocks.items.buttons', 'productBlocks.buttons']);

        return [
            'translation' => $translation->only([
                'title', 'slug', 'excerpt', 'seo_title', 'seo_description',
                'disclosure_text', 'cta_text', 'cta_url',
            ]),
            'blocks' => $translation->contentBlocks->map(function ($block) {
                if ($block->type === ContentBlockType::Text) {
                    return ['type' => 'text', 'text_html' => $block->text_html];
                }

                $pb = $block->productBlock;

                return [
                    'type'          => 'product',
                    'block_uuid'    => $pb->uuid,
                    'template'      => $pb->template->value,
                    'heading'       => $pb->heading,
                    'items'         => $pb->items->map(fn ($item) => [
                        'item_key'              => $item->item_key,
                        'product_id'            => $item->product_id,
                        'title_override'        => $item->title_override,
                        'price_label_override'  => $item->price_label_override,
                        'description_override'  => $item->description_override,
                        'image_url_override'    => $item->image_url_override,
                        'buttons'               => $item->buttons->map(fn ($b) => $this->buttonSnapshot($b))->all(),
                    ])->all(),
                    'block_buttons' => $pb->buttons->whereNull('block_item_id')
                        ->map(fn ($b) => $this->buttonSnapshot($b))->values()->all(),
                ];
            })->all(),
        ];
    }

    private function buttonSnapshot($button): array
    {
        return [
            'button_key'        => $button->button_key,
            'label'             => $button->label,
            'url_type'          => $button->url_type->value,
            'url'               => $button->url,
            'product_link_type' => $button->product_link_type,
            'target'            => $button->target->value,
            'style'             => $button->style->value,
        ];
    }
}
```

### 9.3 `PersistArticleVersionJob` — hash + khoá số thứ tự + insert (bất đồng bộ)

```php
// Modules/Post/app/Jobs/PersistArticleVersionJob.php
namespace Modules\Post\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Modules\Post\Enums\ContentBlockType;
use Modules\Post\Enums\VersionTrigger;
use Modules\Post\Models\PostArticleVersion;

class PersistArticleVersionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $translationId,
        private readonly VersionTrigger $trigger,
        private readonly ?int $userId,
        private readonly array $snapshot,
        private readonly string $titleSnapshot,
        private readonly ?int $restoredFromVersionId = null,
    ) {}

    public function handle(): void
    {
        DB::transaction(function () {
            $hash = hash('sha256', json_encode($this->snapshot));

            $latest = PostArticleVersion::where('translation_id', $this->translationId)
                ->lockForUpdate()
                ->orderByDesc('version_number')
                ->first();

            // Bỏ qua nếu nội dung y hệt version gần nhất VÀ đây là 1 lần "save" thường —
            // tránh version rác khi editor bấm Cập nhật mà không đổi gì. Publish/Restore
            // LUÔN ghi (đánh dấu 1 mốc lifecycle quan trọng dù trùng nội dung).
            if ($this->trigger === VersionTrigger::Save && $latest?->content_hash === $hash) {
                return;
            }

            PostArticleVersion::create([
                'translation_id'  => $this->translationId,
                'version_number'  => ($latest?->version_number ?? 0) + 1,
                'trigger'         => $this->trigger,
                'snapshot'        => $this->snapshot,
                'title_snapshot'  => $this->titleSnapshot,
                'content_hash'    => $hash,
                'char_count'      => $this->charCount(),
                'block_count'     => count($this->snapshot['blocks']),
                'restored_from_version_id' => $this->restoredFromVersionId,
                'created_by'      => $this->userId,
            ]);
        });

        app(\Modules\Post\Features\VersionHistory\Actions\PruneVersionsAction::class)
            ->handle($this->translationId); // §10 — no-op nếu chưa cấu hình giới hạn
    }

    /**
     * §9.4 — tính cả "trọng lượng" product block (không chỉ text), vì đổi sản phẩm/nút
     * trong 1 khối không nhất thiết đổi độ dài text nào (vd chỉ đổi product_id, override để
     * null) nhưng vẫn là 1 thay đổi nội dung đáng kể cần phản ánh ở chỉ số hiển thị "+N/-M".
     */
    private function charCount(): int
    {
        return array_sum(array_map(function ($b) {
            if ($b['type'] === 'text') {
                return mb_strlen(strip_tags($b['text_html']));
            }

            // product block: quy đổi độ phức tạp cấu hình thành "trọng lượng" tương đương ký
            // tự, đủ để +N/-M không luôn hiển thị 0 khi chỉ có product block thay đổi.
            $itemWeight   = count($b['items']) * 50;
            $buttonWeight = (array_sum(array_map(fn ($i) => count($i['buttons']), $b['items']))
                + count($b['block_buttons'])) * 10;

            return $itemWeight + $buttonWeight;
        }, $this->snapshot['blocks']));
    }
}
```

### 9.4 Điểm hook (2 chỗ, không hơn)

| Action đã có | Sửa gì |
|---|---|
| `Modules/Post/app/Features/ArticleAuthoring/Actions/UpdateTranslationAction.php:19` | Sau khi `$this->syncContentBlocks->handle(...)` thành công (vẫn trong `DB::transaction` bao ngoài, dòng 21), gọi `CreateArticleVersionAction::run($translation, VersionTrigger::Save, auth()->id())` — hàm này build snapshot ngay (đồng bộ) rồi tự `DB::afterCommit()` dispatch job, không cần `UpdateTranslationAction` biết chi tiết. |
| `Modules/Post/app/Features/ArticleAuthoring/Actions/PublishArticleAction.php` | Sau khi publish thành công, gọi `CreateArticleVersionAction::run($translation, VersionTrigger::Publish, auth()->id())` — chốt lại "đây chính xác là bản đang public", kể cả khi trùng nội dung với version Save gần nhất (dedup chỉ áp dụng cho `Save`, xem §9.3). |

Không hook vào `SubmitArticleForReviewAction`/`ApproveArticleTranslationAction`/`ScheduleArticleAction`/`CancelScheduleAction`/`UnpublishArticleTranslationAction`/`TakeDownArticleTranslationAction`/`ArchiveArticleAction` — các action này **không đổi nội dung** (chỉ đổi `status`/`published_at`/`approved_*`), version mới sẽ trùng hệt version gần nhất → không tạo giá trị, đã có `post_publishing_logs` ghi lại đúng các mốc này rồi (xem §3).

**Hệ quả cần biết:** vì bước ghi DB chạy qua queue, `version_number`/id của version mới **không có sẵn ngay trong response HTTP** của request lưu bài — UI hiển thị "Đã lưu bài viết" như bình thường, danh sách lịch sử sẽ có version mới sau khi queue worker xử lý xong (thường dưới 1 giây với `queue:listen` đang chạy theo `CLAUDE.md`). Nếu 2 lần lưu xảy ra rất sát nhau (trong lúc job của lần đầu chưa kịp chạy), job không đọc lại state hiện tại (đã đóng băng snapshot ở bước build) nên **không có rủi ro chụp nhầm nội dung** — chỉ có thể xảy ra là 2 job chèn 2 version liên tiếp đúng nội dung đã lưu ở từng thời điểm, hoặc job thứ nhất bị dedup-skip nếu 2 nội dung tình cờ giống hệt nhau.

### 9.5 Restore tái dùng nguyên `SyncContentBlocksAction` — không viết logic ghi mới

```php
// Modules/Post/app/Features/VersionHistory/Actions/RestoreArticleVersionAction.php
class RestoreArticleVersionAction
{
    use AsAction;

    public function __construct(
        private readonly SyncContentBlocksAction $syncContentBlocks,
        private readonly CreateArticleVersionAction $createVersion,
    ) {}

    public function handle(PostArticleVersion $version, int $userId): PostArticleTranslation
    {
        $translation = $version->translation;
        $snapshot    = $version->snapshot;

        $this->assertProductsStillExist($snapshot['blocks']); // §11 — fail fast, không restore 1 phần

        return DB::transaction(function () use ($translation, $version, $snapshot, $userId) {
            $translation->update($snapshot['translation']); // KHÔNG đụng status/published_at (§0)
            $this->syncContentBlocks->handle($translation, $snapshot['blocks']);
            $this->createVersion->handle(
                $translation->fresh(['contentBlocks', 'productBlocks']),
                VersionTrigger::Restore,
                $userId,
                $version->id, // restored_from_version_id — lineage (§6.1, §18.1)
            );

            return $translation;
        });
    }

    private function assertProductsStillExist(array $blocks): void
    {
        $productIds = collect($blocks)
            ->where('type', 'product')
            ->flatMap(fn ($b) => collect($b['items'])->pluck('product_id'))
            ->unique();

        $missing = $productIds->diff(Product::whereIn('id', $productIds)->pluck('id'));

        if ($missing->isNotEmpty()) {
            throw new VersionRestoreException(
                "Không thể khôi phục: sản phẩm #{$missing->implode(', #')} trong phiên bản này không còn tồn tại."
            );
        }
    }
}
```

Vì `snapshot['blocks']` **đúng shape** mà `SyncContentBlocksAction::handle()` vốn nhận (§4), restore tự động thừa hưởng toàn bộ hành vi **upsert-by-key** đã có (`Modules/Post/app/Features/ArticleAuthoring/Actions/SyncContentBlocksAction.php:222` — `firstOrNew(['uuid' => ...])`, dòng 248 `firstOrNew(['item_key' => ...])`, dòng 274 `firstOrNew(['block_id' => ..., 'button_key' => ...])`): nếu khối/sản phẩm/nút cùng key vẫn còn tồn tại ở bản hiện hành, **`click_count` được bảo toàn** — không reset về 0 chỉ vì restore. Nếu key đó đã bị xoá khỏi bản hiện hành trước đó, restore tạo lại như 1 dòng mới (`click_count` bắt đầu lại từ 0 — không thể có lượt click nào cho nội dung không hề tồn tại ở khoảng thời gian đó, đây là hành vi đúng, không phải bug).

`assertProductsStillExist` chỉ áp dụng cho **restore** (đường ghi) — **preview** (đường chỉ đọc) không được phép fail cứng khi gặp sản phẩm đã xoá, xem §13.3.

---

## 10. Retention & giới hạn số version (tuỳ chọn, tắt mặc định)

```php
// Modules/Post/config/config.php bổ sung
'version_history' => [
    'retention_days'              => null, // null = giữ vĩnh viễn theo thời gian. Đặt số nguyên để bật prune theo tuổi.
    'max_versions_per_translation' => null, // null = không giới hạn số lượng. Đặt vd 50-100 để tự dọn version cũ ngay sau mỗi lần ghi.
],
```

### 10.1 `PruneVersionsAction` — dọn ngay sau mỗi lần ghi (nếu bật `max_versions_per_translation`)

Chạy cuối `PersistArticleVersionJob::handle()` (§9.3), **không** cần command/scheduler riêng cho trường hợp này (tự dọn dần, không để phình to rồi mới dọn 1 lần):

```php
class PruneVersionsAction
{
    use AsAction;

    public function handle(int $translationId): void
    {
        $max = config('post.version_history.max_versions_per_translation');

        if (! $max) {
            return;
        }

        $prunableIds = PostArticleVersion::where('translation_id', $translationId)
            ->where('trigger', VersionTrigger::Save) // không bao giờ xoá publish/restore (§7 isProtectedFromPruning)
            ->orderByDesc('version_number')
            ->skip($max)
            ->pluck('id');

        PostArticleVersion::whereIn('id', $prunableIds)->delete();
    }
}
```

### 10.2 Prune theo thời gian (`retention_days`) — vẫn qua command tuỳ chọn

```
php artisan post:prune-article-versions
```
Xoá version `trigger=save` cũ hơn `retention_days`, **ngoại trừ** version mới nhất của mỗi translation (luôn giữ ít nhất 1 bản) và mọi version `trigger=publish`/`restore` (`isProtectedFromPruning()`, §7). Không lên lịch chạy mặc định (`Console\Kernel`/scheduler) — chỉ chạy thủ công/cron nếu tổ chức chủ động bật `retention_days`.

---

## 11. Edge cases & rủi ro

| Tình huống | Xử lý |
|---|---|
| 2 editor sửa cùng 1 translation gần như đồng thời | Mỗi lần lưu vẫn tạo version riêng (không mất bản nào). v1 **cảnh báo mềm** khi mở form nếu có version mới hơn version lúc mở trang (§13.4) — không chặn ghi đè (last-write-wins vẫn là hành vi cuối cùng, người lưu sau có thể xem lịch sử để khôi phục bản bị ghi đè nếu cần). |
| Khôi phục 1 version có sản phẩm đã bị xoá khỏi `Modules/Product` | Chặn cứng trước khi restore (§9.5 `assertProductsStillExist`), báo lỗi rõ danh sách sản phẩm thiếu — không restore 1 phần rồi lỗi FK giữa chừng. **Preview** (đọc, không ghi) vẫn hiển thị được bình thường với fallback placeholder (§13.3) — không dùng chung guard với restore. |
| Khôi phục làm slug trùng với slug đang dùng bởi translation khác (đã đổi slug ở giữa 2 thời điểm) | Đi qua đúng validate `TranslationData`/unique constraint hiện có (giống lưu bài thường) — nếu trùng, action ném lỗi validation, editor phải sửa slug thủ công trước khi khôi phục lại. |
| Translation đang ở trạng thái `published`, khôi phục nội dung cũ | Nội dung công khai đổi **ngay lập tức** (đúng hành vi hiện tại của `UpdateTranslationAction` với bài đã publish — không có khái niệm "bản nháp riêng khi đang live"). UI **bắt buộc** hiển thị cảnh báo rõ ở modal xác nhận khi `translation->status === Published`. |
| Translation bị xoá (`DeleteTranslationAction::handle()` → `$translation->delete()`) | Đây là **soft delete** (`PostArticleTranslation` có `SoftDeletes`) — chỉ set `deleted_at`, KHÔNG phát sinh `DELETE` SQL nên FK `cascadeOnDelete()` trên `post_article_versions.translation_id` **không** bị kích hoạt. Lịch sử vẫn còn nguyên trong DB, chỉ không truy cập được qua UI thường (translation đã soft-delete không hiện trong danh sách admin) — chấp nhận được, đúng với cách `PostArticle`/`PostArticleTranslation` xử lý xoá "mềm" ở mọi nơi khác trong module. |
| Ai đó gọi `forceDelete()` trên translation trong tương lai (hiện KHÔNG có chỗ nào gọi) | Sẽ xoá cứng luôn lịch sử theo `cascadeOnDelete()` — chấp nhận được vì `forceDelete()` là "xoá vĩnh viễn thật sự", nội dung không còn nghĩa gì để giữ lịch sử riêng. Nếu sau này có nhu cầu "xoá cứng translation nhưng giữ lịch sử để audit", cần đổi `cascadeOnDelete()` thành `nullOnDelete()` + denormalize thêm `article_id`/`locale` vào `post_article_versions` — chưa cần ở v1, ghi nhận ở §17. |
| Version có `char_count`/`block_count`/snapshot rỗng (bài chưa có content block nào) | Vẫn tạo version bình thường (`blocks: []`), UI hiển thị "Không có nội dung" thay vì lỗi. |
| Ghi 2 version cùng lúc → trùng `version_number` | `lockForUpdate()` trong transaction riêng của `PersistArticleVersionJob` (§6.2/§9.3) — race hiếm gặp với quy mô 1 vài editor/bài, chấp nhận được; job thứ 2 chờ lock của job thứ 1 nên không trùng số. |
| JSON snapshot chứa HTML chưa sanitize | Không xảy ra — `text_html` được đọc lại từ `post_content_blocks.text_html`, vốn đã qua `ArticleContentRenderer->sanitizeTextHtml()` lúc lưu (`SyncContentBlocksAction.php:82`), snapshot chỉ lưu lại đúng bản đã sanitize, không sanitize lại (không cần, không có input mới). |
| Queue worker down/chậm | Bài viết vẫn lưu thành công bình thường (job version chỉ là sổ sách phụ trợ, không nằm trên đường lưu bài chính) — khi worker sống lại, các job tồn đọng chạy tuần tự, lịch sử vẫn đầy đủ (chỉ trễ hiển thị). Theo `CLAUDE.md`, `php artisan queue:listen` là 1 trong các process dev chuẩn cần chạy — không phải rủi ro mới riêng cho tính năng này. |

---

## 12. So sánh phiên bản (Diff) — v1 cố tình đơn giản

### 12.1 Field diff (scalar)

So từng field trong `snapshot['translation']` giữa 2 version (mặc định: version đang chọn vs. version mới nhất/hiện tại) — chỉ hiển thị field **có thay đổi** dạng bảng "Trước / Sau", 2 cột. Không đổi so với bản trước.

### 12.2 Block diff — so theo vị trí (positional), không LCS, không word-level

> **Đổi so với bản v1.0 của spec này:** bản trước dùng LCS (Longest Common Subsequence) trên "chữ ký block" + heuristic `similar_text()` để phát hiện "sửa" và diff tiếp ở mức từ. Review nội bộ đánh giá đây là **over-engineering cho v1** — độ phức tạp cài đặt/bảo trì không tương xứng giá trị UX tăng thêm ở bản đầu tiên. v1 dùng thuật toán tối giản dưới đây; nếu thực tế sử dụng cho thấy cần chính xác hơn (vd bài dài, chèn 1 đoạn ở giữa làm lệch hết vị trí phía sau), nâng cấp lên LCS ở phase sau (§17) — không đổi schema, chỉ đổi tầng hiển thị.

Thuật toán v1 (thuần PHP, không thêm dependency composer):

1. Lấy 2 mảng `blocks` (cũ, mới) theo đúng thứ tự trong snapshot.
2. Duyệt tuần tự theo **chỉ số** `i = 0..max(count(cũ), count(mới)) - 1`:
   - Nếu cả 2 vị trí đều có block: so `type` + nội dung (text: so `text_html` sau `strip_tags`+`trim`; product: so `block_uuid` + toàn bộ cấu hình item/button) → giống hệt = **"Giữ nguyên"**; khác nhau = **"Thay đổi"** (hiển thị cả 2 bản để editor tự đọc, không tô màu theo từ).
   - Nếu chỉ bản mới có (mảng mới dài hơn) → **"Thêm mới"**.
   - Nếu chỉ bản cũ có (mảng cũ dài hơn) → **"Đã xoá"**.
3. Với block `type=product` bị đánh dấu "Thay đổi", hiển thị thêm 1 dòng tóm tắt đơn giản (không phải diff văn bản): so `template`, so danh sách `product_id` (liệt kê "thêm sản phẩm #X"/"bớt sản phẩm #Y" bằng phép `array_diff` đơn giản giữa 2 danh sách `product_id`), không cố diff chi tiết từng override field.

**Giới hạn đã biết (chấp nhận ở v1, không phải bug):** nếu 1 block được chèn vào **giữa** danh sách (không phải cuối), toàn bộ block từ vị trí đó trở về sau sẽ lệch chỉ số và bị hiển thị nhầm thành "Thay đổi" hàng loạt thay vì đúng 1 "Thêm mới" — do so theo vị trí thay vì so theo nội dung/alignment thật. Editor vẫn xem được nội dung "trước/sau" đúng ở mọi trường hợp (không sai dữ liệu, chỉ sai *nhãn phân loại* thay đổi), chấp nhận được cho v1; nâng cấp lên LCS (§17) giải quyết đúng vấn đề này khi cần.

### 12.3 Không thêm dependency composer ở v1

**Chốt lại (thay quyết định "cần xác nhận" ở bản trước):** v1 **không** dùng `jfcherng/php-diff`, `caxy/php-htmldiff`, hay bất kỳ thư viện diff nào — thuật toán §12.2 chỉ dùng string so sánh (`===`, `strip_tags`, `array_diff`) sẵn có trong PHP. Việc chọn thư viện cho diff mức từ (nếu làm ở phase sau) để lại thành quyết định riêng lúc đó (§17), không chặn Phase 1-5 của spec này.

---

## 13. Permissions, Routes, API response, UI/UX

### 13.1 Permissions — không thêm permission mới (§0)

`PostArticlePolicy` bổ sung 2 method mới, tái dùng permission Spatie đã seed sẵn (`Modules/Post/database/seeders/PostPermissionSeeder.php`):

```php
public function viewHistory(User $user, PostArticle $article): bool
{
    return $user->can('post_article.view');
}

public function restoreVersion(User $user, PostArticle $article): bool
{
    return $user->can('post_article.edit');
}
```

### 13.2 Routes (đặt cạnh nhóm `translations/{translation}/...` hiện có, `Modules/Post/routes/web.php:39-50`)

```php
Route::get('translations/{translation}/versions', [ArticleVersionController::class, 'index'])->name('translations.versions.index');
Route::get('translations/{translation}/versions/{version}', [ArticleVersionController::class, 'show'])->name('translations.versions.show');
Route::get('translations/{translation}/versions/compare', [ArticleVersionController::class, 'compare'])->name('translations.versions.compare');
Route::post('translations/{translation}/versions/{version}/restore', [ArticleVersionController::class, 'restore'])->name('translations.versions.restore');
```

`{version}` route-model-binding theo `id` nội bộ là đủ (không cần `uuid` public — lịch sử không có route công khai, chỉ dùng trong admin).

### 13.3 Cấu trúc response API (phục vụ Alpine.js ở `edit.blade.php`)

**`GET translations/{translation}/versions` (index)**
```json
{
  "data": [
    {
      "id": 42, "version_number": 12, "trigger": "save", "trigger_label": "Lưu chỉnh sửa",
      "title_snapshot": "10 mẹo chăm sóc trẻ sơ sinh",
      "char_count": 1820, "block_count": 6,
      "char_delta": 45, "block_delta": 0,
      "restored_from_version_number": null,
      "created_by": { "id": 3, "name": "Nguyễn Thị A" },
      "created_at": "2026-07-18T09:12:00+07:00",
      "created_at_human": "5 phút trước"
    }
  ],
  "meta": { "current_page": 1, "last_page": 3, "total": 57 }
}
```
`char_delta`/`block_delta` = so với version liền trước (`version_number - 1`), tính ở tầng Query, không lưu sẵn trong DB (tránh phải update lại version cũ khi có version mới chèn vào). `restored_from_version_number` chỉ khác `null` khi `trigger=restore` — map từ `restored_from_version_id` (§6.1/§18.1) sang `version_number` của version nguồn (dùng `version_number`, không phải `id`, vì đây là số hiển thị cho editor — "khôi phục từ #8") để UI hiện badge "Khôi phục từ #8" ngay trong danh sách, không cần bấm vào mới biết.

**`GET translations/{translation}/versions/{version}` (show — preview)**
```json
{
  "version": { "id": 42, "version_number": 12, "trigger": "save", "created_by": {...}, "created_at": "..." },
  "translation_snapshot": { "title": "...", "slug": "...", "excerpt": "...", "...": "..." },
  "rendered_html": "<div class=\"post-content\">...</div>",
  "missing_products": [ { "product_id": 99, "referenced_in_block": "block_uuid-xyz" } ]
}
```
`rendered_html` render qua `ArticleContentRenderer` (đọc, không ghi DB). `missing_products` liệt kê sản phẩm trong snapshot không còn tồn tại — dùng để UI hiện cảnh báo nhẹ, **không** chặn xem preview (khác với restore, §9.5/§11).

**Quy tắc bắt buộc cho renderer khi gặp `missing_products` (chốt ở checknote §18.1 — làm ngay Phase 1, không để "ngầm hiểu"):** `ArticleContentRenderer` khi render 1 `PostArticleVersion::snapshot` (khác với render translation hiện hành) **không được** query `Product` model theo `product_id` để lấy title/ảnh/giá — snapshot đã đóng băng `title_override`/`price_label_override`/`description_override`/`image_url_override` tại đúng thời điểm lưu (§4/§9.2), đó mới là nguồn đúng cho preview lịch sử. Cụ thể:

- Nếu item có `*_override` không null → dùng thẳng override đã lưu (hành vi giống render translation hiện hành, không đổi gì).
- Nếu `*_override` null (item lúc đó lấy dữ liệu trực tiếp từ `Product`) **và** `product_id` nằm trong `missing_products` → renderer dùng placeholder cố định (vd tên "Sản phẩm không còn tồn tại", ảnh placeholder chung của module), **tuyệt đối không** gọi `Product::find($product_id)` (vì đã biết chắc không còn) và cũng không để trống/lỗi layout.
- Nếu `*_override` null nhưng `product_id` **không** nằm trong `missing_products` (sản phẩm vẫn còn) → được phép đọc `Product` hiện hành để hiển thị đúng dữ liệu mới nhất, giống hành vi render bài đang publish bình thường — chỉ riêng sản phẩm đã xoá mới bắt buộc dùng override/placeholder đã lưu.

Lý do bắt buộc: nếu renderer lỡ query `Product` cho sản phẩm đã xoá, kết quả sẽ là `null`/exception thay vì fallback — đúng lỗi mà `missing_products` được sinh ra để tránh.

**`GET translations/{translation}/versions/compare?from={id}&to={id}`**
```json
{
  "from": { "id": 40, "version_number": 10 },
  "to":   { "id": 42, "version_number": 12 },
  "field_changes": [
    { "field": "title", "before": "10 mẹo chăm con", "after": "10 mẹo chăm sóc trẻ sơ sinh" }
  ],
  "block_changes": [
    { "index": 0, "status": "unchanged" },
    { "index": 1, "status": "changed", "type": "text", "before_html": "<p>...</p>", "after_html": "<p>...</p>" },
    { "index": 2, "status": "changed", "type": "product", "product_ids_added": [15], "product_ids_removed": [9] },
    { "index": 3, "status": "added", "type": "text", "after_html": "<p>...</p>" }
  ]
}
```

**`POST translations/{translation}/versions/{version}/restore`**
```json
// 200
{ "message": "Đã khôi phục nội dung từ phiên bản #10.", "translation_uuid": "..." }
// 422 — sản phẩm không còn tồn tại
{ "message": "Không thể khôi phục: sản phẩm #99 trong phiên bản này không còn tồn tại." }
```
Response **không** trả về `version_number` của version `restore` vừa tạo (job ghi bất đồng bộ, §9.4) — UI điều hướng lại danh sách lịch sử (`index`) để thấy version mới, không cần chờ đồng bộ.

### 13.4 UI/UX (trong `Modules/Post/resources/views/admin/articles/edit.blade.php`)

- Thêm 1 tab/nút "Lịch sử phiên bản" cạnh khu vực trạng thái publish hiện có (`edit.blade.php:453` khu vực đã có `x-data="{ showSchedule, showUnpublish, showTakedown }"` — thêm `showHistory` theo đúng pattern Alpine.js sẵn có).
- Danh sách: mỗi dòng = `#version_number`, badge `trigger` (màu theo `badgeClass()`), tên người sửa, thời gian tương đối, `+N/-M` (dùng `char_delta`/`block_delta` từ API, §13.3). Nếu `restored_from_version_number` khác `null` (§13.3), hiện thêm chip nhỏ "← khôi phục từ #N" cạnh badge `trigger`.
- Bấm 1 dòng → xem preview read-only (dùng `rendered_html` từ API `show`) — nếu `missing_products` không rỗng, hiện banner nhỏ "N sản phẩm trong bản này đã bị xoá khỏi hệ thống, hiển thị tạm bằng dữ liệu đã lưu tại thời điểm đó" thay vì vỡ layout hoặc lỗi 500.
- Chọn 2 dòng (checkbox) → "So sánh" → trang diff: bảng field thay đổi (§12.1) ở trên, danh sách block thay đổi (§12.2) bên dưới — block "Thêm mới" nền xanh, "Đã xoá" nền đỏ gạch ngang, "Thay đổi" hiển thị cả 2 bản cạnh nhau (không tô màu theo từ, §12.2) — dùng class DaisyUI theme-aware, không hardcode màu light-only.
- Nút "Khôi phục phiên bản này" trên mọi version không phải version mới nhất → modal xác nhận, nội dung cảnh báo động theo `translation->status` (§11).
- **Cảnh báo concurrent-edit**: khi mở form soạn thảo, server render kèm `data-known-latest-version-id="{{ $translation->latestVersion()?->id }}"` vào form. Alpine polling nhẹ (vd mỗi 30s, hoặc chỉ check lại đúng lúc submit) gọi `GET .../versions?limit=1` — nếu `id` mới nhất khác `known-latest-version-id`, hiện banner không chặn: *"Bài viết đã được {tên người sửa} cập nhật lúc {giờ} kể từ khi bạn mở trang này — nội dung bạn đang sửa có thể đã cũ. Xem lịch sử trước khi lưu."* Không chặn submit (đúng quyết định §2.2 — không làm optimistic locking ở v1), chỉ cảnh báo.

---

## 14. Testing & Acceptance Criteria

1. Lưu bài 2 lần với nội dung khác nhau, chạy hết queue (`php artisan queue:work --once` x2 trong test) → có đúng 2 `PostArticleVersion` (`trigger=save`), `version_number` = 1, 2.
2. Lưu bài mà **không đổi gì** (bấm Cập nhật ngay sau khi vừa lưu) → job chạy xong nhưng **không** tạo version mới (dedup theo `content_hash`, §9.3).
3. Publish → luôn tạo 1 version `trigger=publish`, kể cả khi trùng nội dung với version `save` gần nhất.
4. `CreateArticleVersionAction::handle()` build snapshot đúng **ngay tại thời điểm gọi** (đồng bộ) — test bằng cách gọi action, đổi tiếp nội dung translation ngay sau đó (trước khi job chạy), rồi chạy job → version được ghi phải khớp nội dung **lúc gọi**, không phải nội dung mới nhất tại thời điểm job chạy (xác nhận không có race điều kiện đã nêu ở §9.1).
5. Khôi phục version cũ (giả sử version #8) → tạo version mới `trigger=restore` có `restored_from_version_id` = id của version #8 (`version_number` tương ứng hiển thị đúng ở API `index`, §13.3); `status`/`published_at` của translation **không đổi**; `click_count` của product block/button có `item_key`/`button_key` trùng với bản hiện hành được giữ nguyên.
6. Khôi phục version có `product_id` đã bị xoá khỏi `products` → ném `VersionRestoreException`, không sửa DB (transaction rollback, không có version `restore` rác được tạo ra).
7. Xem preview 1 version có `product_id` đã bị xoá → **không** ném lỗi, trả `missing_products` khác rỗng, `rendered_html` vẫn dựng được (dùng dữ liệu override đã lưu trong snapshot làm fallback hiển thị) — assert bằng mock/spy rằng `Product::find()`/`Product::whereIn()` **không** được gọi cho các `product_id` nằm trong `missing_products` (đúng quy tắc renderer §13.3), chỉ được gọi (nếu có) cho sản phẩm còn tồn tại.
8. `DeleteTranslationAction` (soft-delete) một translation có version → sau đó `PostArticleVersion::where('translation_id', ...)->count()` vẫn giữ nguyên số lượng (không bị cascade xoá) — xác nhận đúng hành vi §11.
9. Diff 2 version giống hệt nhau → field diff rỗng, mọi block đánh dấu "Giữ nguyên".
10. Diff 2 version chỉ thêm 1 block ở **cuối** danh sách → đúng 1 block "Thêm mới", còn lại "Giữ nguyên" (trường hợp thêm ở giữa chấp nhận nhãn sai theo giới hạn đã ghi ở §12.2, không cần test pass cho trường hợp đó).
11. Bật `max_versions_per_translation = 5`, lưu bài 10 lần (10 version `save`, không trùng nội dung) → chỉ còn tối đa 5 version `save` gần nhất, mọi version `publish`/`restore` xen giữa vẫn còn nguyên.
12. User chỉ có `post_article.view` (không có `edit`) → xem được lịch sử/diff, **không** thấy/gọi được nút khôi phục (403 nếu cố gọi route trực tiếp).
13. Policy `restoreVersion` false → route `restore` trả 403.

---

## 15. Phased Implementation Plan

| Phase | Nội dung |
|---|---|
| 1 | Migration `post_article_versions`, enum `VersionTrigger`, model `PostArticleVersion`, `CreateArticleVersionAction` (build snapshot đồng bộ) + `PersistArticleVersionJob` (ghi qua queue) + hook vào `UpdateTranslationAction`/`PublishArticleAction` (§9). |
| 2 | `RestoreArticleVersionAction` + `VersionRestoreException` + guard sản phẩm không còn tồn tại (chỉ áp dụng đường restore, không áp dụng preview). |
| 3 | Routes + `ArticleVersionController` (index/show/restore) trả đúng cấu trúc JSON §13.3, `PostArticlePolicy::viewHistory/restoreVersion`. |
| 4 | Diff v1 đơn giản (§12.2 — positional, không dependency mới), route `compare`. |
| 5 | UI: tab lịch sử trong `edit.blade.php`, preview view (kèm fallback sản phẩm thiếu), trang so sánh, modal khôi phục, banner cảnh báo concurrent-edit (§13.4). |
| 6 | `PruneVersionsAction` (inline sau mỗi lần ghi, §10.1) + config `max_versions_per_translation`/`retention_days` + command `post:prune-article-versions` cho prune theo thời gian — tất cả tắt mặc định. |
| 7 (sau, không cam kết) | Nếu thực tế cần: nâng cấp diff lên LCS + word-level highlight (§12.2/§17), chọn dependency composer lúc đó. |

---

## 16. Open Questions

1. Có cần version hoá riêng cho field cấp `PostArticle` (cover/format/categories...) ở phase sau không, hay Activitylog hiện tại là đủ cho stakeholder? (§2.2 — mặc định spec này giả định **đủ**, cần xác nhận với Marketing/CEO trước khi coi là "xong").
2. Ngưỡng mặc định hợp lý cho `max_versions_per_translation` nếu tổ chức muốn bật ngay từ đầu (thay vì để `null`) — **cập nhật checknote v1.1 (§18.2):** thu hẹp về khoảng **80 hoặc 100** (thay vì 50 đề xuất ban đầu); chốt số chính xác + xác nhận với Marketing để lại tới **Phase 6** (lúc thật sự bật `max_versions_per_translation`), không chặn Phase 1-5.
3. Tần suất polling cho banner cảnh báo concurrent-edit (§13.4) — **cập nhật checknote v1.1 (§18.2):** 30s được coi là **chấp nhận được** cho quy mô nhỏ-trung bình (ít editor/bài) và giữ nguyên cho v1; chỉ xem xét lại (rút ngắn, hoặc đổi sang WebSocket/Pusher, hoặc chỉ check lúc bấm "Lưu") ở **Phase 6** nếu thực tế phát sinh nhiều editor cùng sửa 1 bài.

---

## 17. Cải tiến tương lai (không làm ở v1, ghi nhận để không quên)

| Cải tiến | Vì sao để sau |
|---|---|
| "Restore as Draft" — tạo 1 bản nháp mới từ version cũ thay vì ghi đè thẳng bản đang live | Cần thêm khái niệm "bản nháp song song" (translation thứ 2 cùng locale, hoặc trạng thái riêng) — đổi model đáng kể, không cần thiết nếu workflow thực tế chấp nhận restore ghi đè trực tiếp + cảnh báo (§11) như v1. |
| Export toàn bộ lịch sử 1 bài ra JSON/PDF phục vụ audit nội bộ | Không có yêu cầu cụ thể; dữ liệu đã có sẵn trong `snapshot` nên làm sau không tốn công refactor, chỉ thêm 1 endpoint export khi có nhu cầu thật. |
| Diff mức từ (word-level) trong text block + thuật toán LCS đúng nghĩa cho block diff | Cố tình bỏ qua ở v1 (§12) — nâng cấp khi có phản hồi thực tế rằng diff theo vị trí (§12.2) gây hiểu nhầm thường xuyên. Lúc đó mới chọn dependency composer cụ thể (`jfcherng/php-diff` cho diff chữ, hoặc tự viết Myers diff tối giản). |
| Index bổ sung `(translation_id, trigger, created_at)` | Chỉ cần nếu UI lọc theo `trigger` (vd "chỉ xem các lần publish") trở thành thao tác thường xuyên — 3 index hiện có (§6.1) đã đủ cho truy vấn danh sách/dedup-hash của v1. |

---

## 18. Checknote v1.1 — Các điểm còn có thể tối ưu thêm

Dù đã rất tốt, vẫn còn một số điểm nhỏ. Sau đánh giá thêm, các điểm này được xếp theo **thời điểm xử lý** thay vì chỉ theo mức độ:

### 18.1 Đã xử lý ngay trong spec (trước/trong Phase 1)

Chi phí gần như bằng 0 để làm ngay từ đầu, để lại sau sẽ tốn thêm 1 migration/refactor không cần thiết:

| Vấn đề | Mức độ | Đã cập nhật ở |
|---|---|---|
| Không có `restored_from_version_id` | Trung bình thấp | Cột nullable, self-reference `nullOnDelete()` thêm vào migration Phase 1 (§6.1), model + relation `restoredFrom()` (§8), truyền xuyên suốt `CreateArticleVersionAction` → `PersistArticleVersionJob` → `RestoreArticleVersionAction` (§9.2/§9.3/§9.5), lộ ra API dưới dạng `restored_from_version_number` (§13.3), hiện chip "← khôi phục từ #N" ở UI (§13.4), có test riêng (§14 mục 5). |
| Preview sản phẩm đã xóa — renderer có thể lỡ query `Product` model | Trung bình | Quy định tường minh trong §13.3: renderer bắt buộc dùng `*_override` đã đóng băng trong snapshot cho sản phẩm nằm trong `missing_products`, **không** được gọi `Product::find()`/`whereIn()` cho các `product_id` đó; chỉ đọc `Product` hiện hành cho sản phẩm còn tồn tại. Có test riêng xác nhận không gọi model (§14 mục 7). |

### 18.2 Để Phase 6 hoặc sau — không chặn triển khai Phase 1-5

Đã quyết định lịch trình, không còn là "cân nhắc mở":

| Vấn đề | Mức độ | Quyết định |
|---|---|---|
| Job thất bại vĩnh viễn | Trung bình | `PersistArticleVersionJob` fail nhiều lần → vào `failed_jobs`, lịch sử thiếu 1 version. Xử lý ở Phase 6 cùng lúc với retention/prune (§10): (a) tăng số lần retry hợp lý cho job này, và/hoặc (b) log rõ + command thủ công re-create version từ `activity_log` nếu cần — không bắt buộc đầy đủ ngay ở v1, nhưng phải làm trước khi coi tính năng "ổn định lâu dài". |
| Chốt số mặc định `max_versions_per_translation` | Thấp | Thu hẹp còn **80 hoặc 100** (§16 mục 2) — chọn số cuối cùng khi thực sự bật ở Phase 6, không chặn Phase 1-5. |
| Polling concurrent-edit 30s | Thấp | Giữ nguyên 30s cho v1 (đủ tốt ở quy mô nhỏ-trung bình, §16 mục 3) — chỉ xem xét WebSocket/Pusher hoặc đổi sang "chỉ check lúc bấm Lưu" ở Phase 6 nếu số editor cùng sửa 1 bài tăng lên đáng kể trong thực tế. |

### 18.3 Không cần làm ở v1 (đã chốt, không mở lại trừ khi có yêu cầu mới)

| Cải tiến | Đã chốt ở |
|---|---|
| Diff mức từ (word-level) + LCS đúng nghĩa cho block diff | §12.3, §17 |
| "Restore as Draft" | §17 |
| Optimistic/pessimistic locking cứng khi chỉnh sửa đồng thời | §2.2, §11 |

### 18.4 Kết luận

Spec đã đủ chi tiết để triển khai **Phase 1-5** (§15) mà không bị chặn bởi bất kỳ hạng mục nào ở checknote này. 3 hạng mục còn lại thuộc **Phase 6**, không chặn triển khai:

1. Xử lý job thất bại vĩnh viễn (retry cao hơn / command re-create) — §18.2.
2. Chốt số mặc định `max_versions_per_translation` (80 hoặc 100) — §18.2, §16 mục 2.
3. Tối ưu polling concurrent-edit nếu thực tế cần (WebSocket/Pusher hoặc chỉ check lúc "Lưu") — §18.2, §16 mục 3.
