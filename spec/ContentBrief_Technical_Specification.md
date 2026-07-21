# Module Gói Nghiên Cứu & Chỉ Dẫn Viết Bài (Content Brief)
**Đặc tả Kỹ thuật Chi tiết — Sẵn sàng Triển khai**

**Phiên bản:** 1.1 — vá lỗ hổng: đồng bộ `current_version_id`/`status` khi Reject (§3.9), canonical hash cho snapshot (§3.5), `schema_version` (§0/§2.3), generic hoá `related_references` (bỏ `product_tie_in_ids`), cơ chế hoàn tất Generation thủ công khi chưa có AI thật (§6.0.1), khoá đồng thời cho Submit/Approve/Reject (§3.10), domain events khuyến nghị (§3.11), và bổ sung AC cho Reject/Restore/concurrency/tenant isolation/submit-guard (§8)
**Ngày:** 21/07/2026
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions
**Module liên quan:** Không có — `Modules/ContentBrief` là module **độc lập hoàn toàn**, không tạo, không sửa, không tham chiếu tới bất kỳ bảng nào của `Modules/Post` hay module viết bài nào khác. Đầu ra cuối cùng của module là 1 **JSON đã chuẩn hoá** (xem §6) — module/hệ thống nào muốn dùng JSON đó để tạo bài viết là việc **hoàn toàn ngoài phạm vi** tài liệu này (xem §9)

---

## 0. Quyết định đã chốt

| Chủ đề | Hiện trạng codebase | Quyết định spec này | Lý do |
|---|---|---|---|
| **Vị trí trong pipeline** | Không có nơi nào trong codebase tập hợp từ khoá mục tiêu/outline/đối tượng độc giả/tông giọng... thành 1 gói input có cấu trúc **trước khi** đưa cho bất kỳ cơ chế sinh nội dung nào | Content Brief là lớp **đứng trước** bước sinh nội dung: `Brief → Version (JSON) → Generation → Output JSON chuẩn hoá`. Module này **không viết bài, không sửa bài, không quản lý bài viết, không tạo ra bất kỳ bản ghi nào ở module khác** — phạm vi trách nhiệm của nó **kết thúc** ngay khi `ContentBriefGeneration.output` được ghi nhận `completed` theo đúng cấu trúc chuẩn (§6) | Tách bạch rõ ràng: "lên kế hoạch nội dung + chuẩn hoá output" (Content Brief) ≠ "biên tập/xuất bản bài viết" (việc của bất kỳ hệ thống nào tiêu thụ JSON output sau này). Nhập chung sẽ lặp lại đúng lỗi mà `Menu_Navigation_Technical_Specification.md` từng sửa cho `PostCategory` (trộn 2 mục đích khác nhau vào 1 bảng). Việc **không đụng tới Post** giữ module này tái sử dụng được cho bất kỳ đích đến nào trong tương lai (không riêng gì bài viết Post) |
| **Phạm vi tenant** | `PostArticle`/`PostArticleTranslation` **CÓ** `organization_id` thật (tenant-scoped) — khác `PostCategory`/`MenuItem`/`Banner`/`Page` đã chuyển platform-wide. Taxonomy (category/tag) dùng chung toàn nền tảng, nhưng **nội dung bài viết là của từng tổ chức** | `content_briefs`/`content_brief_versions` **CÓ** `organization_id`, extend `TenantAwareModel` — cùng mô hình `PostArticle`, KHÔNG theo mô hình platform-wide của Page/Menu/Banner | Content Brief là kế hoạch biên tập **của 1 tổ chức cụ thể** (Marketing của tổ chức A lên kế hoạch bài viết cho tổ chức A) — không phải tài sản site dùng chung như banner/trang tĩnh |
| **Document-oriented — "current state" nằm ở đâu** | `post_article_versions` là **audit trail phụ**: bản ghi "đang sửa" nằm ở `post_articles`/`post_article_translations` (mutable), version table chỉ chụp lại lịch sử, tạo bất đồng bộ qua Job (`spec/Post_VersionHistory_Technical_Specification.md:147-399`) | Content Brief **không có bảng "current" mutable riêng** — `content_briefs` chỉ là bản ghi định danh (title, từ khoá, người phụ trách, trạng thái tổng quát), còn **toàn bộ nội dung brief nằm trong `content_brief_versions.snapshot` (json)**; "hiện tại" = version mới nhất theo `version_number`. Tạo version **đồng bộ trong request** (không cần Job) | Đây chính là yêu cầu "Document-oriented + Versioning" của đề bài — brief không có "bản chính" nào khác ngoài chuỗi version (giống lịch sử phiên bản Google Docs/Notion), khác Post vì Post còn phải phục vụ hiển thị public tức thời (cần 1 bản mutable nhanh), còn Brief chỉ phục vụ nội bộ biên tập, không có áp lực đó. Không cần Job bất đồng bộ vì payload JSON của brief nhỏ hơn nhiều so với `content_blocks` của bài viết (không có ảnh/HTML dài) |
| **Khoá version tăng dần** | `post_article_versions.version_number` tính theo `lockForUpdate()` + `max()+1` **riêng theo từng parent**, không dùng `id` toàn cục (`:200-230`) | Áp dụng nguyên xi cơ chế này cho `content_brief_versions.version_number` | Tiền lệ đã kiểm chứng, tránh race-condition khi 2 người cùng lưu 1 brief đồng thời |
| **Chặn ghi trùng** | `post_article_versions.content_hash` (sha256) — không tạo version mới nếu nội dung y hệt version gần nhất (`:230-250`) | Áp dụng `content_hash` cho `content_brief_versions`, nhưng **hash trên bản JSON đã canonical hoá** (đệ quy `ksort` toàn bộ key trước khi `json_encode`, xem §3.5) | Snapshot là object JSON lồng nhau nhiều tầng (outline, key_facts...) — nếu chỉ `json_encode` trực tiếp như Post (vốn hash trên các cột scalar/HTML phẳng), thứ tự key không đảm bảo ổn định giữa 2 lần encode cùng 1 mảng PHP (đặc biệt sau khi decode từ DB rồi encode lại) → hash sai khác dù nội dung logic giống hệt, gây tạo version rác. Canonical hoá bằng ksort đệ quy loại bỏ rủi ro này |
| **Diff giữa 2 version** | `Post_VersionHistory` **chủ động không dùng thư viện diff** — so sánh scalar field bằng `===`, block bằng vị trí (positional), không cần cài thêm package (`:620-647`). Dự án hiện không có package JSON-diff nào (`composer.json` chỉ có `spatie/laravel-data`) | Diff giữa 2 `content_brief_versions.snapshot` theo đúng nguyên tắc: so sánh từng key JSON bằng `===`/`array_diff` thuần PHP, KHÔNG thêm composer package mới | Nhất quán với quyết định đã có của Post, đúng nguyên tắc "không thêm dependency khi chưa cần" |
| **Trạng thái duyệt** | `Post` có `TranslationStatus` 7 trạng thái đầy đủ (`draft/submitted/approved/scheduled/published/unpublished/archived`) qua Publishing Engine — phù hợp cho 1 bài viết công khai có lịch xuất bản, không phù hợp cho 1 tài liệu kế hoạch nội bộ | Content Brief dùng state machine **đơn giản 5 trạng thái**: `draft → in_review → approved → archived`, cộng nhánh `rejected` (quay lại draft). **Không** tái sử dụng `Modules/WorkflowAutomation` cho việc duyệt | Brief là tài liệu kế hoạch, không cần lịch xuất bản/scheduled/unpublished như bài viết thật — chỉ cần 1 cổng duyệt trước khi cho phép chuyển sang giai đoạn sinh nội dung. Thêm state phức tạp hơn hoặc kéo cả 1 workflow engine tổng quát vào là over-engineering khi chưa có nhu cầu thật |
| **Ai được duyệt** | `Post` dùng **Lớp A** (`config/permissions.php`, map thẳng 8 role gốc: Marketing=soạn thảo `post_article.*`, CEO/Ops=duyệt/publish, System_Admin=full) | Content Brief cũng dùng **Lớp A**, cùng pattern Post: Marketing tạo/sửa brief, CEO/Ops duyệt, System_Admin toàn quyền | Content Brief gắn chặt với quy trình biên tập **nội bộ 1 tổ chức** (không phải tài sản nền tảng dùng chung) — đúng bản chất Lớp A, khác Banner/Ocop/Page/Menu (Lớp B hoặc Lớp A tuỳ nhưng đều platform-wide) |
| **Kích hoạt "Generation"** | Không có tiền lệ nào trong codebase cho việc "gửi 1 JSON đi sinh nội dung rồi nhận lại output" — đây là khoảng trống hoàn toàn mới | `ContentBriefGeneration` là 1 bản ghi trạng thái **tự-đứng-độc-lập** (`pending → processing → completed/failed`) — Content Brief chỉ định nghĩa: version nào được gửi đi, `output` (kết quả) được ghi vào đâu, và khi `completed` thì tự động làm gì tiếp (§6). **Cơ chế thực sự sinh ra `output`** (gọi AI, nhà cung cấp nào, prompt ra sao) **nằm ngoài phạm vi tài liệu này** — có thể là 1 module AI nội bộ, 1 dịch vụ ngoài, hoặc thậm chí nhập tay ở giai đoạn đầu | Content Brief **không tự viết logic gọi LLM/tính chi phí/theo dõi token** — việc đó thuộc về bất kỳ hệ thống nào đứng sau đảm nhiệm "Generation". Tách rõ ranh giới giúp module này có thể triển khai và dùng được (lên kế hoạch + duyệt brief) **trước khi** quyết định hệ thống sinh nội dung cụ thể là gì |
| **Đầu ra cuối cùng của pipeline** | Không có tiền lệ "tự động tạo `PostArticle`" ở bất kỳ đâu trong codebase hiện tại — mọi luồng ghi dữ liệu vào Post hiện nay đều qua thao tác biên tập trực tiếp của con người | **Không tạo `PostArticle`, không tạo bất kỳ bản ghi nào ở module khác.** Khi `ContentBriefGeneration` được ghi nhận `completed`, module chỉ **tự động chuẩn hoá và lưu `output`** (JSON đúng cấu trúc quy ước, §6) — đó là **điểm kết thúc** của pipeline xét trong phạm vi tài liệu này | Loại bỏ hoàn toàn rủi ro/giả định về việc phải biết cấu trúc `PostArticle`/`PostArticleTranslation`/`PostContentBlock` bên trong module này. Content Brief trở thành 1 nguồn JSON chuẩn hoá, độc lập — bất kỳ hệ thống nào (Post hay không) muốn tiêu thụ `output` đều tự đọc JSON theo đúng schema đã công bố (§6), không cần Content Brief "biết" về sự tồn tại của hệ thống đó |
| **Cấu trúc trường trong brief** (từ khoá, outline, tone...) | Grep toàn bộ codebase: **không có** khái niệm outline/search-intent/tone-of-voice/word-count-target/competitor-research ở bất kỳ module nào | Thiết kế mới hoàn toàn (§2.3) — lưu dưới dạng `json` trong `content_brief_versions.snapshot`, không tách thành nhiều cột riêng | Giữ đúng tinh thần "Document-oriented": schema nội dung brief có thể tiến hoá (thêm/bớt field) mà không cần migration mỗi lần — chỉ vài field thật sự cần lọc/hiển thị danh sách mới denormalize ra cột riêng ở `content_briefs` (§2.2) |
| **Snapshot tiến hoá theo thời gian** | Không có tiền lệ "schema JSON có version riêng" ở bất kỳ module nào trong codebase (Post/Menu đều dùng cột DB cố định, không phải JSON tự do) | Mỗi `snapshot` **bắt buộc** có field `"schema_version"` (vd `"1.0"`) — do `CreateBriefVersionAction` **tự stamp**, KHÔNG cho phép client gửi lên (giống nguyên tắc `published_at` tự set ở Page) | Khi sau này cần đổi cấu trúc `outline`/thêm field bắt buộc mới, code đọc `snapshot` phải biết đang đọc phiên bản schema nào để xử lý đúng (branch theo `schema_version` hoặc chạy 1 script backfill) — nếu không có field này, mọi snapshot cũ đều trông giống snapshot mới, không cách nào phân biệt để migrate an toàn |
| **Tham chiếu tới module khác trong snapshot** | Bản nháp đầu có `product_tie_in_ids: [123, 456]` — comment ghi rõ `// Modules/Product`, mâu thuẫn với tuyên bố "độc lập hoàn toàn" ở §0/§9 dù không có FK cứng | Đổi thành `related_references: [{type, id, label}]` — **generic hoàn toàn**, `type` là chuỗi tự do (`"product"`, `"article"`, `"internal_page"`...), Content Brief không biết và không quan tâm `type` đó ứng với model nào | Giữ đúng cam kết độc lập: ngay cả *khái niệm* trong tên field cũng không được gợi ý phụ thuộc vào 1 module cụ thể — nếu mai sau không còn `Modules/Product`, hoặc cần tham chiếu sang 1 loại thực thể khác, không phải đổi tên field/migration |
| **Đồng bộ `current_version_id`/`status` khi Reject** | Không nêu rõ ở bản nháp trước — rủi ro implement sai cao nhất (nếu không update, UI vẫn hiện `rejected` dù đã có bản draft mới) | **Chốt rõ**: `RejectBriefVersionAction` PHẢI cập nhật `content_briefs.current_version_id` trỏ sang version draft mới tạo, VÀ `content_briefs.status = draft` — trong CÙNG 1 transaction với việc tạo version mới (§3.3, §3.9) | Đây là nguồn lỗi dễ implement sai nhất nếu không viết tường minh — danh sách/`currentVersion` phải phản ánh đúng "brief này đang cần sửa lại", không phải "brief này đang ở trạng thái bị từ chối" (2 ý nghĩa khác nhau) |
| **"Generation" quá trừu tượng khi chưa có hệ thống AI thật** | Không có tiền lệ | Bổ sung: (1) `StartBriefGenerationAction` (`pending → processing`) để hệ thống đứng sau báo "đã nhận việc"; (2) UI cho phép **dán JSON thủ công** để hoàn tất 1 generation đang treo, dùng chung `CompleteBriefGenerationAction` (validate y hệt, không có đường tắt) — xem §6, §4.2 | Nếu không có 2 điểm này, ở Phase 1-5 (chưa nối AI thật, §7) mọi generation sẽ **treo ở `pending` vĩnh viễn** — trải nghiệm tệ và không cách nào kiểm chứng pipeline hoạt động đúng trước khi có AI thật |
| **Khoá đồng thời khi đổi trạng thái** | `version_number` đã có `lockForUpdate()` (xem trên), nhưng bản nháp trước **chưa** áp dụng khoá tương tự cho Submit/Approve/Reject | Áp dụng `lockForUpdate()` trên `ContentBriefVersion` (không phải `ContentBrief`) trong `SubmitBriefForReviewAction`/`ApproveBriefVersionAction`/`RejectBriefVersionAction` trước khi kiểm tra/đổi `status` (§3.9) | 2 người bấm Duyệt/Từ chối gần như đồng thời trên cùng 1 version (race condition) có thể dẫn tới trạng thái cuối cùng mâu thuẫn (vd vừa `approved` vừa bị ghi đè thành `rejected` ngay sau đó) nếu không khoá — cùng nguyên tắc đã áp dụng cho `version_number` |

---

## 1. Giới thiệu & Mục tiêu

Hiện tại, quy trình từ "ý tưởng bài viết" tới "bài viết được AI hỗ trợ soạn" chưa có lớp kiểm soát đầu vào: không có nơi nào tập hợp từ khoá mục tiêu, outline, đối tượng độc giả, tông giọng, dữ kiện cần trích dẫn... **trước khi** đưa cho bất kỳ cơ chế sinh nội dung nào. Hệ quả: chất lượng bài do AI hỗ trợ phụ thuộc hoàn toàn vào prompt tự do của người dùng, không ai kiểm soát/duyệt được "input" trước khi sinh nội dung.

Module **Content Brief** lấp khoảng trống này bằng mô hình tài liệu có phiên bản:

```
ContentBrief (định danh)
   └─< ContentBriefVersion (json, append-only, có version_number)
          └─ [đã duyệt] → Generation (bản ghi trạng thái — cơ chế sinh nội dung thật nằm ngoài phạm vi)
                              └─ [tự động khi completed] → Output JSON chuẩn hoá (KẾT THÚC phạm vi module)
```

**Không đổi:** Content Brief **không đụng tới bất kỳ module nào khác** — không tạo, không sửa, không tham chiếu bảng của `Modules/Post` hay bất kỳ module viết bài nào. Việc biến JSON output thành 1 bài viết thật (ở Post hay bất kỳ đâu) là quyết định và trách nhiệm của hệ thống tiêu thụ JSON đó, nằm hoàn toàn ngoài phạm vi tài liệu này (§9).

---

## 2. Kiến trúc dữ liệu

### 2.1 ERD

```
ContentBrief (định danh — mutable, KHÔNG chứa nội dung brief)
  ├─ uuid, organization_id, title, target_keyword (denormalize từ version mới nhất)
  ├─ category_label (string, tự do — KHÔNG FK, chỉ là gợi ý phân loại nội bộ, xem §0)
  ├─ assigned_to  → nullable FK users (writer/marketing phụ trách)
  ├─ status ('draft'|'in_review'|'approved'|'archived') — denormalize từ version mới nhất
  ├─ current_version_id → FK content_brief_versions (version mới nhất)
  ├─ created_by, updated_by, timestamps, soft delete
  └─< versions (hasMany ContentBriefVersion)

ContentBriefVersion (append-only, MỖI thay đổi nội dung = 1 dòng mới)
  ├─ uuid, content_brief_id, organization_id (denormalize)
  ├─ version_number (tăng dần riêng theo content_brief_id)
  ├─ status ('draft'|'in_review'|'approved'|'rejected'|'archived')
  ├─ snapshot (json — toàn bộ nội dung brief, xem §2.3)
  ├─ content_hash (sha256 — chặn ghi trùng)
  ├─ trigger ('created'|'edited'|'submitted'|'approved'|'rejected'|'restored')
  ├─ restored_from_version_id → nullable self-FK (lineage khi revert)
  ├─ submitted_by/submitted_at, approved_by/approved_at, rejected_reason
  ├─ created_by, timestamps (KHÔNG soft delete — audit trail bất biến)

ContentBriefGeneration (bản ghi trạng thái "đã yêu cầu sinh nội dung từ version nào")
  ├─ uuid, content_brief_version_id → FK (bắt buộc version phải approved)
  ├─ organization_id (denormalize)
  ├─ status ('pending'|'processing'|'completed'|'failed')
  ├─ output (json, nullable — JSON đã chuẩn hoá theo GenerationOutputData, xem §6 — ĐÂY LÀ
  │  ĐIỂM KẾT THÚC của module, không có cột nào tham chiếu sang bài viết/module khác)
  ├─ error_message (nullable — lý do thất bại nếu status=failed)
  ├─ requested_at, completed_at (nullable)
  ├─ created_by, timestamps
```

Không có bảng/cột nào của module này tham chiếu tới `post_articles`/`post_article_translations`/`post_categories` — `Modules/Post` **không xuất hiện** trong schema của `Modules/ContentBrief`.

### 2.2 Migration — `content_briefs`

```php
Schema::create('content_briefs', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();

    $table->string('title', 200);              // tên nội bộ của brief, KHÔNG phải tiêu đề bài viết cuối
    $table->string('target_keyword', 150);      // denormalize từ snapshot version mới nhất — lọc/tìm nhanh
    $table->string('category_label', 100)->nullable(); // gợi ý phân loại tự do — KHÔNG FK (§0)
    $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

    $table->string('status', 20)->default('draft'); // denormalize từ version mới nhất — xem §3.3
    $table->foreignId('current_version_id')->nullable(); // FK thêm sau (§2.2.1) — tránh phụ thuộc vòng

    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['organization_id', 'status'], 'idx_brief_org_status');
    $table->index(['organization_id', 'target_keyword'], 'idx_brief_org_keyword');
});
```

### 2.2.1 Migration — `content_brief_versions` (+ ALTER thêm FK `current_version_id`)

```php
Schema::create('content_brief_versions', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('content_brief_id')->constrained('content_briefs')->cascadeOnDelete();
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();

    $table->unsignedInteger('version_number');
    $table->string('status', 20)->default('draft'); // BriefVersionStatus — xem §3.1

    $table->json('snapshot');                  // toàn bộ nội dung brief — xem §2.3
    $table->string('content_hash', 64);         // sha256(canonical json) — chặn ghi trùng

    $table->string('trigger', 20);              // BriefVersionTrigger — xem §3.2
    $table->foreignId('restored_from_version_id')->nullable()
        ->constrained('content_brief_versions')->nullOnDelete();

    $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('submitted_at')->nullable();
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('approved_at')->nullable();
    $table->string('rejected_reason', 500)->nullable();

    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->timestamps();

    $table->unique(['content_brief_id', 'version_number'], 'uq_brief_version_number');
    $table->index(['content_brief_id', 'status'], 'idx_brief_version_status');
});

Schema::table('content_briefs', function (Blueprint $table) {
    $table->foreign('current_version_id')->references('id')->on('content_brief_versions')->nullOnDelete();
});
```

Không `softDeletes()` trên `content_brief_versions` — cùng nguyên tắc `post_article_versions`: đây là audit trail, không xoá từng dòng lẻ (xoá cả `ContentBrief` thì cascade xoá luôn toàn bộ version qua FK `cascadeOnDelete`).

### 2.2.2 Migration — `content_brief_generations`

```php
Schema::create('content_brief_generations', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('content_brief_version_id')->constrained('content_brief_versions')->restrictOnDelete();
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();

    $table->string('status', 20)->default('pending'); // GenerationStatus — xem §6
    $table->json('output')->nullable();          // nội dung trả về — cấu trúc do bước Generation quyết định
    $table->string('error_message', 500)->nullable();

    $table->timestamp('requested_at')->nullable();
    $table->timestamp('completed_at')->nullable();

    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->timestamps();

    $table->index(['content_brief_version_id'], 'idx_brief_gen_version');
    $table->index(['status'], 'idx_brief_gen_status');
});
```

Không có migration ALTER nào lên `Modules/Post` — bảng này là điểm dừng cuối cùng của dữ liệu trong phạm vi module (xem §6).

### 2.3 Cấu trúc `snapshot` (json) — schema đề xuất

```jsonc
{
  "schema_version": "1.0",                       // do CreateBriefVersionAction tự stamp, KHÔNG nhận từ client (§0)
  "target_keyword": "sữa công thức cho trẻ sơ sinh",
  "secondary_keywords": ["sữa bột trẻ em", "chọn sữa cho bé"],
  "suggested_category": "Dinh dưỡng",            // tự do, KHÔNG phải category_id của Post (§0)
  "search_intent": "informational",            // enum: informational|transactional|navigational|commercial
  "audience_persona": "Mẹ bỉm sữa lần đầu, con dưới 6 tháng tuổi, quan tâm dinh dưỡng",
  "tone_of_voice": "Ấm áp, đáng tin cậy, tránh thuật ngữ y khoa khó hiểu",
  "word_count_min": 1200,
  "word_count_max": 1800,
  "outline": [
    {"level": 2, "heading": "Sữa công thức là gì?", "notes": "Giải thích ngắn gọn, không sa đà kỹ thuật"},
    {"level": 2, "heading": "Tiêu chí chọn sữa phù hợp", "notes": "Nhắc tới độ tuổi, thành phần DHA/ARA"},
    {"level": 3, "heading": "Dấu hiệu bé không hợp sữa", "notes": ""}
  ],
  "key_facts": [
    {"fact": "WHO khuyến nghị bú mẹ hoàn toàn 6 tháng đầu", "source_url": "https://who.int/..."}
  ],
  "competitor_references": [
    {"url": "https://vd-doi-thu.com/bai-viet", "notes": "Đối thủ thiếu phần dấu hiệu dị ứng — có thể là điểm khác biệt"}
  ],
  "related_references": [                        // generic — KHÔNG gắn với module cụ thể nào (§0)
    {"type": "product", "id": 123, "label": "Sữa ABC Số 1"}
  ],
  "internal_linking_notes": "Link tới bài 'Dinh dưỡng 0-6 tháng' nếu có",
  "seo_title_suggestion": "Sữa Công Thức Cho Trẻ Sơ Sinh: Cách Chọn Đúng Từ A-Z",
  "seo_description_suggestion": "Hướng dẫn chọn sữa công thức phù hợp cho bé sơ sinh...",
  "additional_instructions": "Không nhắc tên thương hiệu cụ thể ngoài related_references."
}
```

Đây là **quy ước ở tầng ứng dụng** (validate bằng `spatie/laravel-data` DTO lồng nhau — xem §3.6), không phải JSON Schema DB-level — thêm/bớt field trong tương lai chỉ cần sửa DTO, không cần migration. `schema_version` (§0) là field duy nhất **bắt buộc do hệ thống ghi**, không thuộc phần Admin tự nhập.

---

## 3. Model & Business rules

### 3.1 `BriefVersionStatus` (enum)

```php
enum BriefVersionStatus: string
{
    case Draft    = 'draft';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Archived = 'archived';
}
```

### 3.2 `BriefVersionTrigger` (enum) — lý do version này được tạo ra

```php
enum BriefVersionTrigger: string
{
    case Created   = 'created';    // tạo brief lần đầu
    case Edited    = 'edited';     // sửa nội dung khi đang draft
    case Submitted = 'submitted';  // gửi duyệt (KHÔNG đổi snapshot, chỉ đổi status — xem §3.3)
    case Approved  = 'approved';   // được duyệt
    case Rejected  = 'rejected';   // bị từ chối, quay lại draft
    case Restored  = 'restored';   // phục hồi từ 1 version cũ hơn
}
```

### 3.3 Quy tắc "khi nào tạo version mới, khi nào chỉ đổi status"

| Hành động | Có tạo version_number mới không? | Lý do |
|---|---|---|
| Tạo brief lần đầu | Có (version 1, trigger=`created`) | |
| Sửa nội dung snapshot khi đang `draft` | **Có, NẾU `content_hash` khác** version hiện tại (trigger=`edited`); nếu hash giống hệt thì không tạo gì (no-op) | Cùng nguyên tắc content_hash của Post — chặn version rác khi bấm Lưu nhiều lần không đổi gì |
| Gửi duyệt (`draft → in_review`) | **Không** — chỉ `UPDATE` field `status`/`submitted_by`/`submitted_at` trên version hiện tại | Nội dung không đổi, không cần thêm 1 bản snapshot y hệt |
| Duyệt (`in_review → approved`) | **Không** — chỉ update `status`/`approved_by`/`approved_at` | Cùng lý do trên. Sau khi `approved`, version này **bị khoá** — mọi sửa đổi tiếp theo bắt buộc đi qua nhánh "Restored" bên dưới |
| Từ chối (`in_review → rejected`) | **Không tạo version mới cho bản bị từ chối** — chỉ update `status = rejected`/`rejected_reason` trên chính version đó (giữ nguyên trong lịch sử). Đồng thời Action tạo **1 version khác** (`version_number` kế tiếp, trigger=`edited`, snapshot **giữ nguyên** nội dung bị từ chối) ở `draft`, và **BẮT BUỘC** cập nhật `content_briefs.current_version_id` trỏ sang version `draft` mới này + `content_briefs.status = draft` (không phải `rejected`) — xem code đầy đủ ở §3.9 | Nếu không cập nhật `current_version_id`/`status` trên `content_briefs`, danh sách brief và mọi query hiển thị vẫn hiện brief này ở trạng thái `rejected` dù thực chất đã có bản draft mới sẵn sàng sửa tiếp — đây là lỗi implement dễ mắc nhất (§0) |
| Sửa lại 1 version đã `approved` | **Bắt buộc tạo version mới** (trigger=`restored`, `restored_from_version_id` trỏ về version approved gốc), version mới ở trạng thái `draft` | Version đã duyệt là **bất biến** — nếu 1 Generation đã chạy dựa trên nó, sửa "âm thầm" sẽ khiến lịch sử generation trỏ về dữ liệu sai lệch với những gì AI thực sự đã đọc |
| Phục hồi 1 version cũ hơn (không phải bản mới nhất) | Tạo version mới (trigger=`restored`, snapshot copy từ version cũ, `restored_from_version_id` trỏ về version đó) | Cùng cơ chế `RestoreVersionAction` của Post — không "tua ngược" số version, luôn cộng thêm 1 bản mới ở đầu chuỗi |

### 3.4 Model `Modules/ContentBrief/app/Models/ContentBrief.php`

```php
class ContentBrief extends TenantAwareModel
{
    protected $fillable = [
        'uuid', 'title', 'target_keyword', 'category_label', 'assigned_to',
        'status', 'current_version_id', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'status' => BriefVersionStatus::class,
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(ContentBriefVersion::class)->orderByDesc('version_number');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ContentBriefVersion::class, 'current_version_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
```

`ContentBrief` extend `TenantAwareModel` (§0) — tự động có `organization_id` global scope + `SoftDeletes` + `LogsActivity` qua base class, không cần khai báo lại (đúng convention `app/Foundation/Models/TenantAwareModel.php`).

`ContentBriefVersion` là **model riêng, KHÔNG extend `TenantAwareModel`** dù có `organization_id` — lý do: đây là bảng audit trail append-only, không cần global scope tự động lọc theo tenant ở mọi query (nhiều thao tác đọc lịch sử cần xuyên suốt, tự lọc tường minh bằng `where('content_brief_id', ...)` là đủ, cùng cách `PostArticleVersion` xử lý theo `Post_VersionHistory_Technical_Specification.md`). `ContentBriefVersion` khai báo hằng số `public const CURRENT_SCHEMA_VERSION = '1.0';` — nguồn duy nhất được dùng khi stamp `schema_version` vào snapshot (§3.5), tăng thủ công (`'1.1'`, `'2.0'`...) khi cấu trúc `BriefSnapshotData` thay đổi không tương thích ngược.

### 3.5 Sinh `version_number` (áp dụng nguyên xi cơ chế Post) + canonical hash

```php
// trong CreateBriefVersionAction
DB::transaction(function () use ($brief, $snapshotArray, $trigger, $userId) {
    $brief = ContentBrief::whereKey($brief->id)->lockForUpdate()->first();

    $snapshotArray['schema_version'] = ContentBriefVersion::CURRENT_SCHEMA_VERSION; // '1.0' — stamp, KHÔNG nhận từ client (§0)

    $canonical = self::canonicalize($snapshotArray);
    $hash = hash('sha256', json_encode($canonical, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $latest = $brief->versions()->first(); // orderByDesc('version_number') đã có sẵn ở relation

    if ($latest && $latest->content_hash === $hash) {
        return $latest; // no-op — nội dung không đổi
    }

    $nextNumber = ($brief->versions()->max('version_number') ?? 0) + 1;

    $version = ContentBriefVersion::create([
        'content_brief_id' => $brief->id,
        'organization_id'  => $brief->organization_id,
        'version_number'   => $nextNumber,
        'status'           => BriefVersionStatus::Draft,
        'snapshot'         => $snapshotArray,
        'content_hash'     => $hash,
        'trigger'          => $trigger,
        'created_by'       => $userId,
    ]);

    $brief->update([
        'current_version_id' => $version->id,
        'target_keyword'      => $snapshotArray['target_keyword'],
        'category_label'      => $snapshotArray['suggested_category'] ?? null,
        'status'               => BriefVersionStatus::Draft,
        'updated_by'           => $userId,
    ]);

    return $version;
});

/**
 * Đệ quy ksort mọi mảng con — đảm bảo json_encode ra cùng 1 chuỗi byte cho cùng 1 nội dung
 * logic, bất kể thứ tự key khi build mảng PHP (vd sau khi decode từ DB rồi encode lại).
 * An toàn với mảng dạng list (outline, key_facts...): ksort trên key số nguyên tuần tự
 * 0,1,2... không đổi thứ tự phần tử — chỉ ổn định lại key của các mảng dạng object/dict.
 */
private static function canonicalize(array $data): array
{
    ksort($data);

    foreach ($data as &$value) {
        if (is_array($value)) {
            $value = self::canonicalize($value);
        }
    }

    return $data;
}
```

### 3.6 Validate `snapshot` — DTO lồng nhau (spatie/laravel-data)

```php
class BriefSnapshotData extends Data
{
    public function __construct(
        #[Required, Max(150)]
        public readonly string $target_keyword,

        /** @var string[] */
        public readonly array $secondary_keywords = [],
        public readonly ?string $suggested_category = null,   // tự do, KHÔNG FK (§0)
        public readonly SearchIntent $search_intent = SearchIntent::Informational,
        public readonly ?string $audience_persona = null,
        public readonly ?string $tone_of_voice = null,
        public readonly ?int $word_count_min = null,
        public readonly ?int $word_count_max = null,

        /** @var BriefOutlineItemData[] */
        #[DataCollectionOf(BriefOutlineItemData::class)]
        public readonly array $outline = [],

        /** @var BriefKeyFactData[] */
        #[DataCollectionOf(BriefKeyFactData::class)]
        public readonly array $key_facts = [],

        /** @var BriefCompetitorReferenceData[] */
        #[DataCollectionOf(BriefCompetitorReferenceData::class)]
        public readonly array $competitor_references = [],

        /** @var BriefRelatedReferenceData[] — generic, KHÔNG gắn với module cụ thể (§0) */
        #[DataCollectionOf(BriefRelatedReferenceData::class)]
        public readonly array $related_references = [],
        public readonly ?string $internal_linking_notes = null,
        public readonly ?string $seo_title_suggestion = null,
        public readonly ?string $seo_description_suggestion = null,
        public readonly ?string $additional_instructions = null,
    ) {}
}

class BriefRelatedReferenceData extends Data
{
    public function __construct(
        #[Required]
        public readonly string $type,     // tự do: "product", "article", "internal_page"... — không validate theo model cụ thể
        #[Required]
        public readonly int|string $id,
        public readonly ?string $label = null,
    ) {}
}
```

Business rule bắt buộc: `word_count_max` phải `>= word_count_min` khi cả 2 cùng có giá trị — validate ở Form Request/Controller (`gt:word_count_min` hoặc custom rule), không đặt trong DTO. `schema_version` **không** nằm trong `BriefSnapshotData` (client không gửi trường này) — được `CreateBriefVersionAction`/`UpdateBriefContentAction` tự thêm vào mảng snapshot **sau** khi validate DTO, **trước** khi canonical hoá + hash (§3.5).

### 3.7 Khoá sửa khi đã duyệt

```php
// UpdateBriefContentAction::handle()
throw_if(
    $brief->currentVersion->status === BriefVersionStatus::Approved,
    ValidationException::withMessages([
        'snapshot' => 'Version này đã được duyệt — không thể sửa trực tiếp. Hãy tạo bản nháp mới từ version này.',
    ])
);
```

UI xử lý: khi `currentVersion->status === Approved`, form sửa **chuyển thành read-only** kèm nút "Tạo bản nháp mới từ đây" (gọi `RestoreBriefVersionAction` với chính version hiện tại làm nguồn).

### 3.8 Chặn xoá khi đã có kết quả sinh nội dung

```php
// DeleteBriefAction::handle()
throw_if(
    $brief->versions()->whereHas('generations', fn ($q) => $q->where('status', GenerationStatus::Completed))->exists(),
    ValidationException::withMessages([
        'brief' => 'Brief này đã có kết quả sinh nội dung hoàn tất — hãy Lưu trữ (archive) thay vì xoá.',
    ])
);
```

Bảo vệ `output` JSON đã tốn công sinh ra khỏi bị xoá nhầm cùng brief cha — `ArchiveBriefAction` (chuyển `status = archived`, không xoá) là lựa chọn thay thế phù hợp trong trường hợp này.

### 3.9 Hành vi đầy đủ khi Reject (đồng bộ `current_version_id`/`status` trên `content_briefs`)

```php
// RejectBriefVersionAction::handle()
DB::transaction(function () use ($rejectedVersion, $reason, $userId) {
    $brief = ContentBrief::whereKey($rejectedVersion->content_brief_id)->lockForUpdate()->first();

    // 1. Version bị từ chối GIỮ NGUYÊN trong lịch sử, chỉ đổi status/rejected_reason.
    $rejectedVersion->update([
        'status'          => BriefVersionStatus::Rejected,
        'rejected_reason' => $reason,
    ]);

    // 2. Tạo 1 version MỚI (không phải sửa lại version vừa reject) — snapshot giữ nguyên
    //    nội dung bị từ chối để người soạn sửa tiếp từ đó, không phải gõ lại từ đầu.
    $nextNumber = $brief->versions()->max('version_number') + 1;
    $newDraft = ContentBriefVersion::create([
        'content_brief_id' => $brief->id,
        'organization_id'  => $brief->organization_id,
        'version_number'   => $nextNumber,
        'status'           => BriefVersionStatus::Draft,
        'snapshot'         => $rejectedVersion->snapshot,
        'content_hash'     => $rejectedVersion->content_hash,
        'trigger'          => BriefVersionTrigger::Edited,
        'created_by'       => $userId,
    ]);

    // 3. BẮT BUỘC — đây là bước hay bị bỏ sót nhất (§0): nếu thiếu bước này, danh sách/
    //    currentVersion vẫn hiện "rejected" dù thực chất đã có bản draft sẵn sàng sửa tiếp.
    $brief->update([
        'current_version_id' => $newDraft->id,
        'status'              => BriefVersionStatus::Draft,
        'updated_by'          => $userId,
    ]);
});
```

### 3.10 Khoá đồng thời khi đổi trạng thái (Submit/Approve/Reject)

`SubmitBriefForReviewAction`/`ApproveBriefVersionAction`/`RejectBriefVersionAction` đều mở đầu bằng:

```php
$version = ContentBriefVersion::whereKey($versionId)->lockForUpdate()->first();
```

trước khi kiểm tra `status` hiện tại và ghi trạng thái mới — cùng nguyên tắc khoá đã áp dụng cho `version_number` (§0). Ngăn 2 request đổi trạng thái gần như đồng thời trên cùng 1 version (vd 2 người có quyền duyệt cùng bấm Duyệt/Từ chối) dẫn tới trạng thái cuối mâu thuẫn hoặc ghi đè lẫn nhau.

`SubmitBriefForReviewAction` **thêm 1 guard riêng**: chỉ cho phép khi `version->status === Draft` (chặn gửi duyệt 1 version đang `in_review`/`approved`/`rejected`/`archived` — ném `ValidationException` nếu không đúng trạng thái nguồn).

### 3.11 Domain events (khuyến nghị, không bắt buộc ở v1)

Để hệ thống khác (kể cả 1 hệ thống Generation thật ở Phase 6) có thể "hook" vào các mốc quan trọng mà không cần polling database, các Action nên bắn Laravel event chuẩn (đơn giản, không cần queue/broadcast):

```php
BriefSubmittedForReview::class     // sau SubmitBriefForReviewAction
BriefVersionApproved::class        // sau ApproveBriefVersionAction
BriefVersionRejected::class        // sau RejectBriefVersionAction
BriefGenerationRequested::class    // sau RequestBriefGenerationAction
BriefGenerationCompleted::class    // sau CompleteBriefGenerationAction
BriefGenerationFailed::class       // sau FailBriefGenerationAction
```

Đây là các Laravel event nội bộ đơn giản (`event(new BriefVersionApproved($version))`), **không** phải hàng đợi/webhook ra ngoài — hệ thống nào cần biết sự kiện xảy ra ở module khác (vd 1 dịch vụ Generation thật muốn tự động nhận job ngay khi `BriefGenerationRequested` bắn ra thay vì poll `status = pending` định kỳ) tự đăng ký listener. Không bắt buộc implement ở v1 (module vẫn hoạt động đúng nếu không ai lắng nghe), nhưng nên khai báo event **ngay từ đầu** để không phải sửa lại Action khi có nhu cầu tích hợp thật.

---

## 4. Admin CRUD (`Modules/ContentBrief`)

```
Modules/ContentBrief/
  app/
    Models/{ContentBrief,ContentBriefVersion,ContentBriefGeneration}.php
    Enums/{BriefVersionStatus,BriefVersionTrigger,SearchIntent,GenerationStatus}.php
    Features/BriefManagement/
      Data/{BriefSnapshotData,BriefOutlineItemData,BriefKeyFactData,BriefCompetitorReferenceData,BriefRelatedReferenceData}.php
      Actions/
        CreateBriefAction.php            // tạo ContentBrief + version 1 (trigger=created), stamp schema_version
        UpdateBriefContentAction.php     // tạo version mới nếu hash đổi (trigger=edited) — chặn khi approved (§3.7)
        SubmitBriefForReviewAction.php   // draft → in_review, lockForUpdate + guard status=draft (§3.10)
        ApproveBriefVersionAction.php    // in_review → approved, lockForUpdate (§3.10)
        RejectBriefVersionAction.php     // in_review → rejected + tự tạo version draft mới + đồng bộ ContentBrief (§3.9)
        RestoreBriefVersionAction.php    // tạo version mới từ 1 version cũ (trigger=restored)
        ArchiveBriefAction.php           // draft/approved → archived (không xoá)
        DeleteBriefAction.php            // soft-delete ContentBrief (chặn nếu có ContentBriefGeneration đã completed — §3.8)
      Queries/
        ListBriefsForAdminQuery(+Handler).php
        GetBriefVersionHistoryQuery(+Handler).php   // danh sách version + diff tóm tắt (§3 Post-style)
      Http/BriefAdminController.php
    Features/Generation/                   // "Generation" trong pipeline — xem §6
      Data/GenerationOutputData.php              // schema chuẩn cho output (§6)
      Actions/RequestBriefGenerationAction.php   // tạo ContentBriefGeneration(status=pending)
      Actions/StartBriefGenerationAction.php     // pending → processing (hệ thống đứng sau báo "đã nhận việc")
      Actions/CompleteBriefGenerationAction.php  // validate output theo GenerationOutputData, lưu, status=completed
      Actions/FailBriefGenerationAction.php      // ghi error_message, status=failed
      Http/BriefGenerationController.php         // bao gồm action "Nhập output thủ công" (paste JSON, §4.2)
    Policies/ContentBriefPolicy.php
    Providers/ContentBriefServiceProvider.php
  database/{migrations,seeders}/
  routes/web.php
```

### 4.1 Routes

```php
Route::middleware(['auth', 'tenant'])->prefix('dashboard/content-briefs')->name('backend.content_brief.')->group(function (): void {
    Route::resource('items', BriefAdminController::class)->except(['show'])->parameters(['items' => 'brief']);

    Route::get('items/{brief}/versions', [BriefAdminController::class, 'versions'])->name('items.versions');
    Route::post('items/{brief}/submit', [BriefAdminController::class, 'submit'])->name('items.submit');
    Route::post('items/{brief}/approve', [BriefAdminController::class, 'approve'])->name('items.approve');
    Route::post('items/{brief}/reject', [BriefAdminController::class, 'reject'])->name('items.reject');
    Route::post('items/{brief}/restore/{version}', [BriefAdminController::class, 'restore'])->name('items.restore');
    Route::post('items/{brief}/archive', [BriefAdminController::class, 'archive'])->name('items.archive');

    // Yêu cầu sinh nội dung — chỉ cho phép khi currentVersion đã approved (§6). Việc "hoàn tất"
    // (validate + lưu output chuẩn hoá) do CompleteBriefGenerationAction xử lý, được gọi bởi bất
    // kỳ cơ chế nào đứng sau đảm nhiệm sinh nội dung (ngoài phạm vi tài liệu này) — không có route
    // nào ghi dữ liệu sang Post, module này dừng lại ở việc trả về/lưu JSON output.
    Route::post('items/{brief}/generate', [BriefGenerationController::class, 'request'])->name('items.generate');
});
```

### 4.2 Giao diện quản trị

- **Danh sách** (`dashboard/content-briefs`): dạng bảng hoặc bảng Kanban theo `status` (draft/in_review/approved/archived) — tham khảo cảm giác UI board đã quen thuộc với người dùng nội bộ, không bắt buộc dùng chung code với module nào khác. Cột: tiêu đề, từ khoá mục tiêu, người phụ trách, `category_label` (gợi ý phân loại tự do), badge trạng thái, số version, cập nhật lúc.
- **Form tạo/sửa**: chia 2 khu — (1) thông tin định danh (title, gợi ý phân loại, người phụ trách), (2) toàn bộ field trong `snapshot` (§2.3) theo từng nhóm: SEO & từ khoá, Outline (danh sách heading kéo-thả thêm/bớt dòng — dùng Alpine `x-for` client-side trước khi submit dạng mảng), Dữ kiện & nguồn tham khảo, Đối thủ cạnh tranh, Ghi chú bổ sung.
- **Trang lịch sử version** (`items/{brief}/versions`): liệt kê version mới → cũ, mỗi dòng hiện `trigger`, người tạo, thời điểm, badge trạng thái; bấm vào 1 version cũ hơn hiện diff tóm tắt so với version hiện tại (field nào đổi giá trị, outline thêm/bớt heading nào) — diff kiểu scalar-compare + positional, không dùng thư viện diff chuyên dụng (§0).
- **Nút hành động theo trạng thái**: `draft` → "Gửi duyệt"; `in_review` (chỉ hiện cho người có quyền duyệt) → "Duyệt" / "Từ chối" (kèm textarea lý do); `approved` → "Yêu cầu sinh nội dung" (gọi `RequestBriefGenerationAction`, tạo 1 `ContentBriefGeneration` đang `pending`) + "Tạo bản nháp mới từ đây".
- **Trạng thái Generation**: `pending`/`processing` → hiện badge kèm thời gian đã trôi qua từ `requested_at` (vd "Đang chờ · 42 phút") để Admin nhận biết generation có vẻ bị "treo", cùng nút **"Nhập kết quả thủ công"** (mở modal dán JSON, gọi thẳng `CompleteBriefGenerationAction` — §6.0.1) và nút "Báo lỗi" (gọi `FailBriefGenerationAction`, nhập lý do). `completed` → link "Xem/Sao chép JSON output" (modal xem raw JSON theo đúng `GenerationOutputData`, §6.1) — module dừng lại ở đây, không có màn hình "tạo bài viết" nào cả. `failed` → hiện `error_message` + nút "Yêu cầu lại" (tạo 1 `ContentBriefGeneration` mới, không sửa lại dòng đã `failed`).

---

## 5. Permission

Thêm vào `app/Enums/PermissionEnum.php` (Lớp A — mirror `POST_ARTICLE_*`):

```php
// ══ CONTENT BRIEF (Gói nghiên cứu + chỉ dẫn viết bài — input có kiểm soát trước khi sinh nội dung) ═
// Marketing=Soạn thảo/Gửi duyệt/Yêu cầu sinh nội dung | CEO/Ops=Duyệt | System_Admin=Full
// | còn lại=không truy cập
case CONTENT_BRIEF_VIEW    = 'content_brief.view';
case CONTENT_BRIEF_MANAGE  = 'content_brief.manage';   // create/edit/submit/archive/delete/generate
case CONTENT_BRIEF_APPROVE = 'content_brief.approve';  // approve/reject
```

`config/permissions.php` (Lớp A, cùng khối với `POST_ARTICLE_*`):

```php
R::MARKETING->value => [
    // ...
    P::CONTENT_BRIEF_MANAGE->value,
    P::CONTENT_BRIEF_VIEW->value,
],
R::CEO->value => [
    // ...
    P::CONTENT_BRIEF_APPROVE->value,
    P::CONTENT_BRIEF_VIEW->value,
],
R::OPS->value => [
    // ...
    P::CONTENT_BRIEF_APPROVE->value,
    P::CONTENT_BRIEF_VIEW->value,
],
R::ADMIN->value => [
    // ... (System_Admin luôn full — syncPermissions ở seeder)
],
```

`RequestBriefGenerationAction` (§6) chỉ cần `content_brief.manage` — không phụ thuộc permission của module nào khác, vì cơ chế sinh nội dung thật sự nằm ngoài phạm vi tài liệu này (nếu hệ thống đứng sau đó có yêu cầu permission riêng, đó là việc của hệ thống đó tự kiểm tra khi nhận yêu cầu).

---

## 6. "Generation" — ranh giới của module (cơ chế sinh nội dung nằm ngoài phạm vi)

### 6.0 `GenerationStatus` (enum)

```php
enum GenerationStatus: string
{
    case Pending    = 'pending';
    case Processing = 'processing';
    case Completed  = 'completed';
    case Failed     = 'failed';
}
```

Content Brief định nghĩa **hợp đồng dữ liệu** (data contract) cho giai đoạn Generation, nhưng **không tự sinh nội dung, và không tạo/ghi bất kỳ dữ liệu nào ở module khác**. Trách nhiệm của module dừng lại đúng ở việc trả về 1 JSON `output` **đúng cấu trúc chuẩn**. Cụ thể chỉ có 4 việc:

1. **`RequestBriefGenerationAction`** — validate `currentVersion->status === Approved` (chặn nếu chưa duyệt, xem AC §8), sau đó tạo 1 dòng `content_brief_generations` (`status = pending`, `requested_at = now()`). Đây là toàn bộ việc module này làm khi "yêu cầu sinh nội dung" — **không gọi bất kỳ AI provider nào**.
2. **`StartBriefGenerationAction`** (tuỳ chọn) — cơ chế đứng sau gọi để báo "đã nhận việc, đang xử lý" (`pending → processing`). Không bắt buộc phải gọi bước này — `CompleteBriefGenerationAction`/`FailBriefGenerationAction` chấp nhận cả 2 trạng thái nguồn `pending` **và** `processing`, nên 1 hệ thống đơn giản có thể bỏ qua bước "processing" và đi thẳng từ `pending` sang `completed`.
3. **`CompleteBriefGenerationAction`** — được gọi bởi bất kỳ cơ chế nào đứng sau đảm nhiệm việc sinh nội dung thật (nằm ngoài phạm vi tài liệu này), nhận vào `output` thô rồi **validate theo đúng `GenerationOutputData`** (DTO chuẩn — xem cấu trúc bên dưới). Nếu hợp lệ: ghi `output` (dạng json đã chuẩn hoá), `status = completed`, `completed_at = now()`. Nếu **không** khớp schema: từ chối lưu (`ValidationException`), `status` giữ nguyên `pending`/`processing` — **không** có bước nào khác được thực hiện tiếp theo (không tạo, không gọi, không ghi sang bất kỳ đâu).
4. **`FailBriefGenerationAction`** — cơ chế sinh nội dung báo lỗi (timeout, từ chối nội dung...) → ghi `status = failed`, `error_message`.

### 6.0.1 Hoàn tất thủ công khi chưa có hệ thống Generation thật (Phase 1-5, xem §7)

Ở các phase chưa nối 1 hệ thống sinh nội dung thật, `CompleteBriefGenerationAction` **vẫn gọi được trực tiếp từ UI quản trị** — trang chi tiết 1 `ContentBriefGeneration` đang `pending`/`processing` có nút **"Nhập kết quả thủ công"**, mở modal cho phép dán 1 khối JSON rồi submit. Modal này gọi **đúng** `CompleteBriefGenerationAction` (validate y hệt theo `GenerationOutputData`, §6.1) — không có đường tắt riêng cho thao tác thủ công so với thao tác tự động. Nhờ vậy: (a) pipeline không bị "treo `pending` vĩnh viễn" khi chưa có AI thật đứng sau, và (b) toàn bộ luồng (kể cả validate schema, kể cả AC §8) được kiểm chứng đầy đủ ngay từ Phase 5, không phải chờ tới khi có hệ thống AI thật.

### 6.1 `GenerationOutputData` — cấu trúc chuẩn của `output`

```php
class GenerationOutputData extends Data
{
    public function __construct(
        #[Required, Max(300)]
        public readonly string $title,

        public readonly ?string $meta_description = null,

        /** @var GenerationSectionData[] — ánh xạ theo đúng outline đã duyệt trong snapshot */
        #[DataCollectionOf(GenerationSectionData::class)]
        public readonly array $sections = [],

        public readonly ?int $word_count = null,

        /** @var string[] */
        public readonly array $seo_keywords_used = [],
    ) {}
}

class GenerationSectionData extends Data
{
    public function __construct(
        #[Required]
        public readonly string $heading,
        public readonly int $level = 2,
        #[Required]
        public readonly string $content_html,
    ) {}
}
```

```jsonc
// Ví dụ output hợp lệ — đây là ĐIỂM DỪNG CUỐI CÙNG của module, không có bước nào sau đây
{
  "title": "Sữa Công Thức Cho Trẻ Sơ Sinh: Cách Chọn Đúng Từ A-Z",
  "meta_description": "Hướng dẫn chọn sữa công thức phù hợp cho bé sơ sinh...",
  "sections": [
    {"heading": "Sữa công thức là gì?", "level": 2, "content_html": "<p>...</p>"},
    {"heading": "Tiêu chí chọn sữa phù hợp", "level": 2, "content_html": "<p>...</p>"}
  ],
  "word_count": 1450,
  "seo_keywords_used": ["sữa công thức cho trẻ sơ sinh", "sữa bột trẻ em"]
}
```

**Không thiết kế trong tài liệu này**: hệ thống nào thực sự sinh ra nội dung khớp schema trên (gọi AI, prompt ra sao, chi phí/token theo dõi ở đâu, hàng đợi/retry hoạt động thế nào), và **việc dùng JSON output này để tạo bài viết thật ở đâu đó (Post hay hệ thống khác)** — hoàn toàn ngoài phạm vi (§9). Bất kỳ hệ thống nào đáp ứng đúng schema `GenerationOutputData` khi gọi `CompleteBriefGenerationAction` đều dùng được pipeline này — kể cả 1 người vận hành nhập tay `output` trong giai đoạn đầu khi chưa có hệ thống AI nào sẵn sàng.

---

## 7. Kế hoạch triển khai (phases)

| Phase | Nội dung | Phụ thuộc |
|---|---|---|
| 1 | Migration `content_briefs`/`content_brief_versions` + Model + Enum + Policy + Permission (Lớp A) | Không |
| 2 | Admin CRUD: tạo/sửa brief, versioning (§3.5), lịch sử version + diff tóm tắt | Phase 1 |
| 3 | Luồng duyệt: submit/approve/reject + khoá sửa khi approved (§3.7) | Phase 2 |
| 4 | Migration `content_brief_generations` + `RequestBriefGenerationAction` | Phase 3 |
| 5 | `GenerationOutputData`/`GenerationSectionData` + `CompleteBriefGenerationAction` (validate + lưu `output`) + `FailBriefGenerationAction` (§6) | Phase 4 |
| 6 (tương lai, chưa lên lịch) | Nối 1 cơ chế sinh nội dung thật (AI hay dịch vụ khác) gọi vào `CompleteBriefGenerationAction`/`FailBriefGenerationAction`, **và** 1 tích hợp riêng (ở module khác, KHÔNG phải Content Brief) để tiêu thụ `output` JSON và tạo bài viết thật — cả 2 việc này **ngoài phạm vi tài liệu này** (§9) | Phase 5 |

Phase 1-5 hoàn toàn dùng được **mà không cần bất kỳ hệ thống AI nào và không đụng tới Post** — Marketing đã có thể lên kế hoạch/duyệt brief, yêu cầu generation (ở trạng thái `pending`), và (ở giai đoạn đầu) 1 người vận hành có thể tự nhập `output` thủ công qua `CompleteBriefGenerationAction` để kiểm chứng toàn bộ pipeline sinh JSON chuẩn hoá hoạt động đúng — module coi như **hoàn chỉnh** ngay sau Phase 5, không phụ thuộc Phase 6 nào để có giá trị sử dụng.

---

## 8. Acceptance Criteria

1. Tạo 1 `ContentBrief` mới → tự động sinh `ContentBriefVersion` #1 (`trigger=created`, `status=draft`), `content_briefs.current_version_id` trỏ đúng version này.
2. Sửa nội dung snapshot 2 lần liên tiếp với **cùng 1 giá trị** (không đổi gì) → chỉ có đúng 1 version tồn tại (không sinh version rác nhờ `content_hash`).
3. Sửa nội dung snapshot với giá trị **khác** → sinh version mới (`version_number` tăng đúng 1, `trigger=edited`), version cũ vẫn còn nguyên trong lịch sử (không bị ghi đè).
4. Gửi duyệt (`draft → in_review`) rồi Duyệt (`in_review → approved`) → **không** sinh thêm version mới ở cả 2 bước này (chỉ đổi `status`/`approved_by`/`approved_at` trên version hiện tại) — tổng số version trước/sau không đổi.
5. Sửa nội dung của 1 version đang `approved` → bị chặn (`ValidationException`), phải qua `RestoreBriefVersionAction` để tạo version mới ở `draft` (`trigger=restored`, `restored_from_version_id` trỏ đúng version approved gốc).
6. Từ chối (`in_review → rejected`) → version bị từ chối giữ nguyên `status=rejected` (không đổi snapshot), đồng thời tự động có 1 version mới ở `draft` với snapshot giữ nguyên để người soạn sửa tiếp. **Đồng thời** (§3.9): `content_briefs.current_version_id` phải trỏ đúng sang version `draft` mới này (KHÔNG còn trỏ vào version vừa `rejected`), và `content_briefs.status` phải là `draft` (KHÔNG phải `rejected`) — kiểm tra cả 2 field này ngay sau khi reject, không chỉ kiểm tra bản thân version.
7. Phục hồi (`RestoreBriefVersionAction`) 1 version **cũ hơn** (không phải version mới nhất, không phải version đang `approved`) → tạo version mới với `restored_from_version_id` trỏ **đúng version cũ đó** (không trỏ vào version hiện tại/mới nhất), `version_number` cộng thêm ở cuối chuỗi (không "chèn" vào giữa hay đổi số của các version đã tồn tại), snapshot copy đúng nội dung của version được phục hồi.
8. `RequestBriefGenerationAction` bị chặn nếu `currentVersion->status !== Approved` (kể cả khi gọi thẳng Action, không qua UI) — không tạo dòng `content_brief_generations` nào trong trường hợp này.
9. Gọi `CompleteBriefGenerationAction` với `output` **khớp đúng schema `GenerationOutputData`** trên 1 `ContentBriefGeneration` đang `pending`/`processing` → ghi `status = completed`, `completed_at`, lưu `output` — **không** tạo/gọi/ghi bất kỳ dữ liệu nào ở bảng khác ngoài `content_brief_generations` (kiểm chứng bằng cách xác nhận không có bản ghi mới nào phát sinh ở `post_articles`/`post_article_translations` sau lệnh gọi này).
10. Gọi `CompleteBriefGenerationAction` với `output` **thiếu field bắt buộc** (vd thiếu `title`, hoặc 1 phần tử `sections` thiếu `content_html`) → bị từ chối (`ValidationException`), `content_brief_generations.status` giữ nguyên `pending`/`processing`, không lưu `output` một phần.
11. `SubmitBriefForReviewAction` bị chặn nếu `currentVersion->status !== Draft` (vd version đang `in_review`, `approved`, hoặc `archived`) — không gửi duyệt trùng lặp được, kể cả gọi thẳng Action.
12. Gọi đồng thời (giả lập bằng 2 request/process song song) `ApproveBriefVersionAction` và `RejectBriefVersionAction` trên **cùng 1 version** đang `in_review` → nhờ `lockForUpdate()` (§3.10), chỉ đúng 1 lệnh gọi thắng và ghi trạng thái cuối cùng nhất quán (`approved` **hoặc** `rejected`, không phải cả 2/không phải trạng thái trung gian mâu thuẫn); lệnh gọi thua phải nhận lỗi rõ ràng (vd "version đã đổi trạng thái") thay vì âm thầm ghi đè.
13. `content_brief.approve` chỉ cấp cho CEO/Ops/System_Admin (qua `config/permissions.php`); user chỉ có `content_brief.manage` (Marketing) không gọi được route `items.approve`/`items.reject` (403).
14. Toàn bộ query `ContentBrief`/`ContentBriefVersion` **và** `ContentBriefGeneration` (kể cả trang lịch sử version, kể cả chi tiết 1 generation) tự động lọc theo `organization_id` hiện tại (qua `TenantAwareModel`/global scope hoặc điều kiện tường minh tương đương) — user tổ chức B không xem/thao tác được brief, version, hay generation nào thuộc tổ chức A (404/403, không rò rỉ qua URL đoán ID).
15. Xoá (`soft delete`) 1 `ContentBrief` **có ít nhất 1 version** sở hữu 1 `ContentBriefGeneration` đang `completed` → bị chặn ở `DeleteBriefAction` (§3.8), thông báo rõ lý do và gợi ý dùng `ArchiveBriefAction` thay thế; brief không có generation nào `completed` (kể cả có generation `pending`/`failed`) vẫn xoá được bình thường.
16. Mọi `snapshot` được lưu (ở bất kỳ `trigger` nào tạo version mới) đều có field `schema_version` khớp đúng `ContentBriefVersion::CURRENT_SCHEMA_VERSION` tại thời điểm lưu — kể cả khi client cố tình gửi kèm 1 giá trị `schema_version` khác trong payload (bị Action ghi đè, không tin theo client).

---

## 9. Ngoài phạm vi (out of scope)

- **Tích hợp với `Modules/Post` (hay bất kỳ module viết bài/xuất bản nào khác)** — Content Brief **không tạo, không sửa, không tham chiếu** `PostArticle`/`PostArticleTranslation`/`PostContentBlock`/`PostCategory` ở bất kỳ đâu. Việc dùng `output` JSON (§6) để tạo ra 1 bài viết thật, ở hệ thống nào, theo cơ chế nào — hoàn toàn thuộc về module/hệ thống khác, không thiết kế trong tài liệu này.
- **Cơ chế sinh nội dung thật** (soạn prompt, gọi LLM/nhà cung cấp nào, theo dõi chi phí/token, hàng đợi/retry) — **hoàn toàn ngoài phạm vi tài liệu này** (§6). Content Brief chỉ định nghĩa hợp đồng dữ liệu (input JSON đã duyệt, `output` phải khớp `GenerationOutputData`) — không quan tâm `output` đó tới từ đâu.
- **Quy trình duyệt nhiều bước có thể cấu hình** (vd 2-3 cấp duyệt tuỳ tổ chức) — v1 chỉ có đúng 1 cổng duyệt (`in_review → approved`), không tái sử dụng `Modules/WorkflowAutomation` (§0). Nếu cần duyệt nhiều cấp, đó là 1 tích hợp riêng với `WorkflowAutomation`, không thiết kế trong tài liệu này.
- **Tự động nghiên cứu đối thủ/crawl dữ liệu web** — `competitor_references`/`key_facts` ở v1 là nhập tay, không có tính năng tự động tìm kiếm/crawl.
- **Đa ngôn ngữ cho brief** — brief phục vụ 1 nội dung ở 1 locale chính của tổ chức; nếu đích đến cuối cùng cần đa ngôn ngữ, đó là việc của hệ thống tiêu thụ `output`, không phải Content Brief tạo brief riêng cho từng locale.
- **So sánh diff bằng thư viện chuyên dụng** (vd LCS thật cho outline) — diff v1 chỉ là scalar-compare + positional (§0), không thêm composer package mới. **Giới hạn đã biết**: diff positional sẽ hiển thị sai lệch gây hiểu nhầm khi Admin chỉ **chèn/xoá** 1 phần tử ở giữa `outline`/`key_facts` (mọi phần tử phía sau bị lệch vị trí nên bị diff báo "đổi" dù nội dung thực chất chỉ dịch chuyển) — chấp nhận được ở v1 vì brief thường có outline ngắn (dễ nhìn bằng mắt), không phải điểm chặn triển khai.
- **Chấm điểm/tự động đánh giá chất lượng brief bằng AI** (vd tự động chấm "brief này đã đủ chi tiết chưa") — có thể là 1 tính năng riêng sau này, không thuộc v1.
- **Kanban kéo-thả đổi trạng thái trực tiếp trên danh sách** — v1 chỉ có nút hành động rõ ràng theo từng trạng thái (§4.2), chưa làm drag-and-drop.
