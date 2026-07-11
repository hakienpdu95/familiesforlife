> **Cập nhật 2026-07-11**: Phần **publishing lifecycle** (`ArticleStatus`, cột `status`/`published_at`/`approved_*` trên `post_articles`, các Action `Publish/Schedule/Submit/Archive`) và toàn bộ **Multi-language** đã được đặc tả lại chi tiết, thay thế, tại `spec/PublishingEngine_Technical_Specification.md` (v2.0) — bao gồm bảng `post_article_translations` mới, đổi FK `post_content_blocks`/`post_product_blocks` sang `translation_id`, enum `TranslationStatus` (7 trạng thái, per-locale) thay cho `ArticleStatus`. Các phần còn lại của tài liệu này (Category tree, Product CTA Box, sanitize, click-tracking) vẫn là nguồn tham khảo đúng, không đổi.

# Post Module — Quản lý Bài viết theo Danh mục & Product CTA Box (Đặc tả kỹ thuật)

> **Pattern stack:** AVSA + CQRS-lite + Laravel Modules (NWIDART 13) + Laravel Actions (lorisleiva 2.x)
> **Module tham chiếu kiến trúc:** `Modules/OcopRubric`, `Modules/Subscription`, `Modules/Academy` (xem `docs/academy-spec.md`) — Feature-first, **không** theo layer-first cũ (`Actions/Backend` + `Data/Requests`) của `Modules/KcItem`.
> **Phụ thuộc module:** `Modules/Product` (`docs/product-catalog-spec.md`) — Post **phụ thuộc** Product (1 chiều) để lấy dữ liệu sản phẩm/dịch vụ hiển thị trong Product CTA Box. Product phải được scaffold **trước** Post (xem `docs/product-catalog-spec.md` §16 Phase 0).
> **Spec version:** 1.6 — 2026-07-07 (v1.1: tách bảng `products` độc lập, xem `docs/product-catalog-spec.md`. v1.2: bổ sung §9.8 an toàn bắt buộc (XSS/tenant/URL validation/volume cap), giới hạn 2-7 sản phẩm/khối, UX chống quá tải form, sửa/xoá khối qua Jodit popup + `item_key`/`button_key` bảo toàn `click_count`. v1.3: §9.8.5 — phân tích hiệu năng sửa/xoá khối, thêm endpoint batch-fetch sản phẩm, dirty-check trước khi UPDATE, xác nhận 2 bước trước khi xoá khối. v1.4: **tối đa 3 khối/bài** (đổi từ cap chống-abuse 20 → quy tắc biên tập thật); gộp "thêm mới"/"sửa" vào 1 dialog "Quản lý khối sản phẩm" dùng chung chế độ Form, gated bởi đếm khối hiện có — xem §9.1/§9.8.6. v1.5: sửa lỗi thiết kế — chèn khối phải đúng **vị trí con trỏ**, không phải luôn cuối bài; dùng `editor.s.save()`/`restore()` (API thật của Jodit) để bảo toàn vị trí qua suốt thời gian mở dialog, kèm dọn marker khi huỷ dialog — xem §9.1 bước 0/6/7 và §9.8.7. v1.6: thay CTA `use_product_default` (1 link chung chung) bằng `use_product_link` + `product_link_type` — mỗi nút chọn dùng **1 trong tối đa 4 link affiliate cố định** (Shopee/TikTok/NCC/homepage NCC) đã cấu hình sẵn ở tầng `Product`, khớp `docs/product-catalog-spec.md` v1.2 §6.2/§7/§9 — xem §7.7/§8/§9.1 bước 5/§9.5/§9.6)
> **Nguồn tham khảo nghiệp vụ:** ảnh chụp màn hình tại `docs/tintuc/` (site Families for Life — cây danh mục theo chủ đề/độ tuổi, trang danh mục đa định dạng nội dung, sidebar điều hướng cây; site Motherly/Babylist — box sản phẩm nhúng inline trong bài viết làm CTA)
> **Quyết định đã chốt với stakeholder:** module độc lập (`Modules/Post`), không tách `PostCategory`/`PostArticle` thành 2 module riêng như tiền lệ `KcCategory`/`KcItem`. Product CTA Box tham chiếu catalog sản phẩm qua module `Product` riêng (không nhập tay/không soft-link `OcopProduct` như bản v1.0).

---

## 1. Bối cảnh & mục tiêu

`docs/tintuc/` cho thấy 2 mảng nghiệp vụ cần kết hợp:

1. **Bài viết theo danh mục** — cây danh mục không giới hạn cấp (Marriage/Newborn/Toddlers.../ Development→Behaviour→bài viết), mỗi bài có thể thuộc nhiều danh mục, có "định dạng nội dung" riêng (Article/Video/Activity/Tip/Step-by-step) độc lập với cây danh mục.
2. **Product CTA Box** — bài viết dạng Motherly/Babylist nhúng inline 1 hoặc nhiều box sản phẩm (ảnh, tên, giá, nút hành động) ngay trong nội dung để tăng tỉ lệ chuyển đổi, không phải chỉ 1 banner cuối bài.

Mục tiêu module: cho phép Marketing của từng tổ chức tự biên tập bài viết, chèn box sản phẩm vào **bất kỳ vị trí nào** trong bài bằng thao tác kéo-thả/chọn trong editor sẵn có (Jodit), và trang chi tiết render ra đúng những gì đã thấy ở ảnh mẫu.

### ⚠️ Phát hiện quan trọng — đã giải quyết bằng module `Product` riêng (v1.1)

Model `OcopProduct` (`Modules/OcopRubric/app/Models/OcopProduct.php`) — **chỉ có** `name`, `product_code`, `status`, các cột điểm chấm (`best_practice_score`...). **Không có `price`, `image`, `description`.** Đây là 1 sổ đăng ký/chấm điểm OCOP tuân thủ pháp lý, không phải danh mục bán hàng — không thể dùng làm nguồn dữ liệu "sống" cho Product Box.

Bản v1.0 của spec này từng chọn hướng "nhập tay toàn bộ dữ liệu hiển thị tại thời điểm chèn" để né vấn đề trên — nhưng ở quy mô hàng nghìn bài viết × hàng chục nghìn sản phẩm, cách này buộc phải sửa giá ở **từng vị trí đã chèn** khi giá thay đổi, không scale được.

→ **Quyết định v1.1**: tách 1 module `Product` độc lập (`docs/product-catalog-spec.md`) làm catalog sản phẩm/dịch vụ thật (có `price`/`cover_image_url`/`description`/`status`). `post_product_block_items` giờ tham chiếu **FK cứng** `product_id` tới bảng `products`, chỉ giữ các cột `*_override` (nullable) để tuỳ biến riêng từng vị trí khi cần — xem §7.6 và §9 bản cập nhật bên dưới.

---

## 2. Đặt tên & vị trí module

**Tên module: `Post`** (`Modules/Post`) — module độc lập, không tách theo tiền lệ `KcCategory`/`KcItem`.

| Lý do gộp 1 module (đã chốt) |
|---|
| Category + Article + Product-Block luôn đi cùng nhau trong 1 bounded context "biên tập nội dung marketing", không có lý do tách deploy/versioning độc lập. |
| Giảm số `module.json`/`ServiceProvider`/permission-seeder phải bảo trì song song cho 1 tính năng duy nhất. |
| Khác với `KcCategory`/`KcItem`: Kc là kho tri thức nội bộ dùng chung nhiều loại "item" (document/sop/video/form...), category ở đó có lý do tồn tại độc lập với item hơn. |

Thuộc **Platform Core**, ngang hàng `Academy`/`KcItem` (theo `docs/PLATFORM_DESIGN.md` §2.1) — mỗi tổ chức tự biên tập bài viết riêng, không phải tính năng riêng của 1 vertical.

---

## 3. Phạm vi (Scope Boundary)

### 3.1 Trong phạm vi (đợt này)

1. Quản trị **cây danh mục** bài viết (CRUD, không giới hạn cấp, sắp xếp thứ tự).
2. Quản trị **bài viết**: soạn thảo (Jodit), gán 1-nhiều danh mục + tag, chọn định dạng nội dung (Article/Video/Activity/Tip/Step-by-step), workflow duyệt trước khi publish, lên lịch xuất bản.
3. **Product CTA Box** nhúng inline trong nội dung: chèn 1 hoặc nhiều sản phẩm/nhóm sản phẩm cùng lúc, chọn template hiển thị, tự định nghĩa nhãn + đường dẫn cho từng nút CTA.
4. Trang công khai: danh sách theo danh mục (có breadcrumb cây), trang chi tiết bài viết render đầy đủ product box, đếm view/click.

### 3.2 Ngoài phạm vi (cố ý không làm ở đây)

| Nghiệp vụ | Vì sao không làm ở đây |
|---|---|
| Giỏ hàng/thanh toán trong box sản phẩm | Box chỉ là CTA điều hướng (link ngoài/trang liên hệ/Zalo/điện thoại), không phải checkout — hệ thống hiện chưa có catalog bán hàng thật (xem cảnh báo §1) |
| A/B testing nhãn CTA | Không có yêu cầu cụ thể, có thể thêm sau bằng cách cho phép nhiều `PostProductBlockButton` cùng vị trí + đo `click_count` so sánh thủ công trước |
| Bình luận/đánh giá bài viết | Chưa có trong ảnh mẫu, không nằm trong yêu cầu |
| Đa ngôn ngữ nội dung | MVP tiếng Việt; có thể thêm cột `language` sau theo đúng tiền lệ `KcItem` mà không phá schema |
| Log click chi tiết theo user/IP (chỉ đếm tổng) | MVP dùng `click_count` tăng dần đủ cho nhu cầu đo hiệu quả; nếu cần phễu chi tiết theo thời gian, thêm bảng `post_product_block_click_logs` ở phase sau (xem §16 Open Questions) |

---

## 4. Nguyên tắc kiến trúc

| Nguyên tắc | Áp dụng trong `Post` |
|---|---|
| **AVSA + CQRS-lite** | `Features/{Slice}/Actions` (`AsAction`) + `Features/{Slice}/Queries` (`*Query`+`*Handler`) — không business logic trong Controller |
| **Tenant-scoped toàn bộ** | Mọi bảng top-level (`post_categories`, `post_articles`, `post_tags`, `post_product_blocks`) extend `App\Foundation\Models\TenantAwareModel` |
| **No JSON storage** | Không cột JSON ở bất kỳ đâu. Cấu hình nhiều sản phẩm/nhiều nút CTA trong 1 block là **bảng quan hệ** (`post_product_block_items`, `post_product_block_buttons`), không phải mảng JSON |
| **Placeholder trong content KHÔNG phải nguồn sự thật cuối** | `content` (HTML) chỉ chứa placeholder tham chiếu tới `post_product_blocks.uuid` — dữ liệu hiển thị thật nằm ở bảng quan hệ, đồng bộ 2 chiều lúc lưu bài (xem §9.5) |
| **Soft delete** | `post_categories`, `post_articles` (nội dung có thể phục hồi). **Không** soft-delete `post_product_blocks`/`*_items`/`*_buttons` (bảng con, vòng đời gắn chặt bài viết, xoá cứng theo cascade — giống `academy_quiz_options`) |
| **UUID public** | `post_categories.uuid`, `post_articles.uuid` expose qua route công khai. `post_product_blocks.uuid` không lộ qua route riêng nhưng **được nhúng trực tiếp vào content HTML** làm placeholder key — xem §9.2 |
| **Tiền tố bảng `post_`** | Theo tiền lệ `academy_*`, `ocop_*`, `kc_*` |
| **Enum** | PHP backed enum (`string`), cột DB là `string` thường (không dùng enum native của MySQL), có `label()` |

---

## 5. Directory Structure (Feature-first)

```
Modules/Post/
├── app/
│   ├── Features/
│   │   ├── CategoryManagement/         ← Slice: quản trị cây danh mục
│   │   │   ├── Actions/    (CreateCategoryAction, UpdateCategoryAction, DeleteCategoryAction, ReorderCategoriesAction)
│   │   │   ├── Queries/    (GetCategoryTreeQuery/Handler, ListCategoriesForAdminQuery/Handler)
│   │   │   └── Http/       (CategoryAdminController)
│   │   │
│   │   ├── ArticleAuthoring/           ← Slice: soạn thảo bài viết + đồng bộ product block
│   │   │   ├── Actions/    (CreateArticleAction, UpdateArticleAction, SubmitArticleForReviewAction,
│   │   │   │                PublishArticleAction, ScheduleArticleAction, ArchiveArticleAction, DeleteArticleAction)
│   │   │   ├── Data/       (ArticleData, ProductBlockData, ProductBlockItemData, ProductBlockButtonData)
│   │   │   ├── Events/     (ArticlePublished)
│   │   │   ├── Queries/    (ListArticlesForAdminQuery/Handler, GetArticleDetailForAdminQuery/Handler)
│   │   │   └── Http/       (ArticleAdminController)
│   │   │
│   │   ├── ProductBlockPicker/          ← Slice: API phục vụ dialog Jodit (tìm danh mục/sản phẩm để chèn)
│   │   │   ├── Queries/    (ListProductCategoriesForPickerQuery/Handler, SearchProductsForPickerQuery/Handler)
│   │   │   └── Http/       (ProductPickerApiController)
│   │   │
│   │   └── PublicReading/               ← Slice: hiển thị công khai + tracking
│   │       ├── Actions/    (RecordArticleViewAction, RecordProductBlockClickAction)
│   │       ├── Queries/    (GetPublishedArticleQuery/Handler, ListArticlesByCategoryQuery/Handler,
│   │       │                ListArticlesReferencingProductQuery/Handler)
│   │       └── Http/       (PublicArticleController, PublicCategoryController, ProductBlockClickController)
│   │
│   ├── Support/
│   │   └── ArticleContentParser.php    ← Dùng chung: parse content HTML ⇄ danh sách block placeholder (xem §9.5/9.6)
│   │
│   ├── Models/          (PostCategory, PostArticle, PostTag, PostProductBlock,
│   │                      PostProductBlockItem, PostProductBlockButton)
│   ├── Enums/           (ArticleStatus, ArticleFormat, ProductBlockTemplate, ButtonUrlType, ButtonTarget, ButtonStyle)
│   ├── Policies/        (PostCategoryPolicy, PostArticlePolicy)
│   └── Providers/       (PostServiceProvider, EventServiceProvider, RouteServiceProvider)
│
├── config/config.php     → ['name' => 'Post']
├── database/
│   ├── migrations/       (8 file, xem §7)
│   └── seeders/          (PostPermissionSeeder, PostDemoContentSeeder, PostDatabaseSeeder)
├── resources/
│   ├── views/            (admin/categories, admin/articles, public/category, public/article,
│   │                       components/product-block/{single_card,multi_grid,banner,compact_list}.blade.php)
│   └── assets/js/jodit-post-product.js   ← plugin Jodit (xem §9.1)
├── routes/{web.php, api.php}
├── module.json, composer.json
```

---

## 6. Data Model — ERD tổng quan

```
PostCategory (self-ref parent_id, cây không giới hạn cấp)
      │ (n:n qua post_article_categories, is_primary)
PostArticle ──< (n:n qua post_article_tag) >── PostTag
      │
      │ content (HTML) chứa placeholder <div data-block-uuid="...">
      │
      └──< (1:n) PostProductBlock  [template, heading, sort_order]
                       │
                       ├──< (1:n) PostProductBlockItem  [product_id (FK cứng → Modules\Product\products),
                       │           │                     title/price_label/description/image_url *_override (nullable)]
                       │           └──< (1:n) PostProductBlockButton  [label, url_type, url, target, style, click_count]
                       │
                       └──< (1:n) PostProductBlockButton  [button gắn cấp khối, block_item_id = null,
                                                            vd "Xem tất cả sản phẩm"]

Modules\Product\Product (org catalog)  ──< (1:n, restrictOnDelete) PostProductBlockItem.product_id
```

---

## 7. Data Model — Chi tiết từng bảng

### 7.1 `post_categories` — Danh mục (cây)

```php
Schema::create('post_categories', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();
    $table->foreignId('parent_id')->nullable()->constrained('post_categories')->restrictOnDelete();
    $table->string('name', 150);
    $table->string('slug', 160);
    $table->text('description')->nullable();
    $table->string('icon', 80)->nullable();
    $table->char('color_hex', 7)->nullable();
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->unique(['organization_id', 'slug'], 'uq_post_cat_org_slug');
    $table->index(['organization_id', 'parent_id', 'sort_order'], 'idx_post_cat_sort');
    $table->index(['organization_id', 'is_active'], 'idx_post_cat_active');
});
```
Mô hình y hệt `kc_categories` (đã kiểm chứng qua production) — `parent_id` tự tham chiếu, không giới hạn cấp, khớp cây "Marriage/Newborn/.../Development→Behaviour" ở ảnh mẫu.

### 7.2 `post_articles` — Bài viết

```php
Schema::create('post_articles', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();
    $table->string('title', 300);
    $table->string('slug', 320);
    $table->string('excerpt', 500)->nullable();
    $table->longText('content')->nullable();               // HTML từ Jodit, chứa placeholder product-block
    $table->string('format', 20)->default('article');       // ArticleFormat: article|video|activity|tip|step_by_step
    $table->string('status', 20)->default('draft');         // ArticleStatus: draft|pending_review|published|scheduled|archived
    $table->string('cover_image_url', 500)->nullable();
    $table->timestamp('published_at')->nullable();          // set khi publish; > now() khi scheduled
    $table->string('seo_title', 200)->nullable();
    $table->string('seo_description', 300)->nullable();
    $table->unsignedBigInteger('view_count')->default(0);
    $table->boolean('is_featured')->default(false);
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('approved_at')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->unique(['organization_id', 'slug'], 'uq_post_article_org_slug');
    $table->index(['organization_id', 'status', 'published_at'], 'idx_post_article_org_status_pub');
    $table->index(['organization_id', 'format'], 'idx_post_article_org_format');
});
```
`cover_image_url` là string đơn giản (upload qua FilePond có sẵn, giống `resources/views/backend/products/*.blade.php`) — không dùng Spatie MediaLibrary vì chỉ cần 1 ảnh đại diện, tránh over-engineering.

### 7.3 `post_article_categories` — pivot Bài viết ↔ Danh mục (n:n, có "danh mục chính")

```php
Schema::create('post_article_categories', function (Blueprint $table) {
    $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
    $table->foreignId('category_id')->constrained('post_categories')->cascadeOnDelete();
    $table->boolean('is_primary')->default(false);   // dùng cho breadcrumb + URL canonical
    $table->primary(['article_id', 'category_id']);
});
```
1 bài có thể lên nhiều danh mục (vd vừa "Babies" vừa "Sleep") mà không nhân bản nội dung; `is_primary` quyết định breadcrumb hiển thị ở trang chi tiết (ảnh mẫu 3).

### 7.4 `post_tags` / `post_article_tag` — Tag phẳng (cross-cutting)

```php
Schema::create('post_tags', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();
    $table->string('name', 120);
    $table->string('slug', 140);
    $table->timestamps();

    $table->unique(['organization_id', 'slug'], 'uq_post_tag_org_slug');
});

Schema::create('post_article_tag', function (Blueprint $table) {
    $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
    $table->foreignId('tag_id')->constrained('post_tags')->cascadeOnDelete();
    $table->primary(['article_id', 'tag_id']);
});
```

### 7.5 `post_product_blocks` — Khối sản phẩm nhúng trong bài (1 khối = 1 vị trí chèn trong content)

```php
Schema::create('post_product_blocks', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();      // = placeholder key nhúng trong content HTML, xem §9.2
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();
    $table->foreignId('article_id')->constrained('post_articles')->cascadeOnDelete();
    $table->string('template', 30)->default('single_card'); // ProductBlockTemplate
    $table->string('heading', 200)->nullable();   // tiêu đề khối, vd "Sản phẩm gợi ý cho mẹ và bé"
    $table->unsignedSmallInteger('sort_order')->default(0); // vị trí xuất hiện trong content, đồng bộ lại mỗi lần save
    $table->timestamps();

    $table->index(['organization_id', 'article_id'], 'idx_post_pb_org_article');
});
```

### 7.6 `post_product_block_items` — 1-nhiều sản phẩm trong 1 khối (v1.1 — FK cứng tới `Modules/Product`)

```php
Schema::create('post_product_block_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('block_id')->constrained('post_product_blocks')->cascadeOnDelete();
    $table->string('item_key', 20);   // sinh 1 lần ở client lúc thêm item (nanoid ngắn), giữ nguyên qua các lần sửa — xem §9.4/§9.8
    $table->foreignId('product_id')->constrained('products')->restrictOnDelete(); // nguồn sự thật — xem docs/product-catalog-spec.md §6.2

    // Override tuỳ chọn — null = fallback "sống" từ bảng products (xem §9.5 cơ chế fallback)
    $table->string('title_override', 200)->nullable();
    $table->string('price_label_override', 100)->nullable();
    $table->text('description_override')->nullable();
    $table->string('image_url_override', 500)->nullable();

    $table->unsignedSmallInteger('sort_order')->default(0); // thứ tự trong khối (template multi_grid/compact_list)
    $table->timestamps();

    $table->unique(['block_id', 'item_key'], 'uq_post_pbi_block_key');
    $table->index(['block_id', 'sort_order'], 'idx_post_pbi_block_order');
    $table->index('product_id', 'idx_post_pbi_product');
});
```
`product_id` là **FK cứng** (`restrictOnDelete`) — không phải soft-link string như bản v1.0. Lý do đổi: ở quy mô hàng chục nghìn sản phẩm × hàng nghìn bài viết, soft-link (`product_ref_type`/`product_ref_id` kiểu string) không cho phép JOIN hiệu quả để render hàng loạt hay tổng hợp báo cáo ("sản phẩm nào được nhắc nhiều nhất"), và không có ràng buộc toàn vẹn (dễ trỏ tới sản phẩm đã xoá). `restrictOnDelete` buộc `Product` phải dùng vòng đời `status` (`discontinued`...) thay vì xoá cứng khi còn tham chiếu — xem `docs/product-catalog-spec.md` §9.

Mọi cột `*_override` để **null** trong đa số trường hợp — chỉ set khi Marketing muốn 1 câu mô tả/giá đặc biệt riêng cho vị trí chèn đó (vd khuyến mãi chỉ áp dụng trong bài này). Cột `title`/`price_label`/`description`/`image_url` (không override) của bản v1.0 **không còn tồn tại** — dữ liệu hiển thị mặc định luôn lấy từ `products` qua `product_id`.

**Giới hạn số lượng (bắt buộc validate ở `SyncProductBlocksAction`, xem §9.8.2)**: `single_card`/`banner` = đúng 1 item; `multi_grid`/`compact_list` = tối thiểu 2, **tối đa 7** item/khối.

### 7.7 `post_product_block_buttons` — Nút CTA tuỳ biến (1-nhiều nút/item hoặc 1-nhiều nút/khối)

```php
Schema::create('post_product_block_buttons', function (Blueprint $table) {
    $table->id();
    $table->foreignId('block_id')->constrained('post_product_blocks')->cascadeOnDelete();
    $table->foreignId('block_item_id')->nullable()->constrained('post_product_block_items')->cascadeOnDelete();
    // null = nút áp dụng cho cả khối (vd "Xem tất cả sản phẩm"), có giá trị = nút riêng của 1 sản phẩm (vd "Mua ngay")
    $table->string('button_key', 20); // sinh 1 lần ở client, giữ nguyên qua các lần sửa — bảo toàn click_count, xem §9.4/§9.8
    $table->string('label', 60)->nullable();        // null khi url_type=use_product_link → lấy ProductLinkType::label()
    $table->string('url_type', 20);                  // ButtonUrlType: use_product_link|custom_url|phone|zalo|email
    $table->string('url', 500)->nullable();          // null khi url_type=use_product_link → lấy product->{ProductLinkType::urlColumn()}
    $table->string('product_link_type', 30)->nullable(); // ProductLinkType: shopee|tiktok|supplier_product|supplier_homepage — chỉ set khi url_type=use_product_link
    $table->string('target', 10)->default('_blank'); // ButtonTarget: _self|_blank
    $table->string('style', 20)->default('primary'); // ButtonStyle: primary|secondary|outline|ghost (map DaisyUI btn-*)
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->unsignedInteger('click_count')->default(0);
    $table->timestamps();

    $table->unique(['block_id', 'button_key'], 'uq_post_pbb_block_key');
    $table->index(['block_id', 'block_item_id'], 'idx_post_pbb_block_item');
});
```
Đây là phần trả lời trực tiếp yêu cầu "định nghĩa nhập các nút button cho phép custom điều hướng link": mỗi item (hoặc cả khối) có thể có **nhiều nút**, mỗi nút tự chọn `url_type` + nhập `url` riêng — không giới hạn 1 nút "Mua ngay" cố định. Tối đa **5 nút/item** (validate ở `SyncProductBlocksAction`, xem §9.8.2) — đủ cho các kịch bản thực tế (Mua ngay + Zalo + Gọi điện...) mà không phá layout template.

> **v1.1**: thêm `url_type = use_product_default` (thay bằng `use_product_link` từ v1.6, xem dưới) — khi 1 sản phẩm phổ biến được chèn ở hàng nghìn bài, Marketing không cần nhập lại `label`/`url` mỗi lần chèn; vẫn có thể override riêng khi bài viết cần CTA đặc biệt (`custom_url`/`phone`/`zalo`/`email` như trước).
>
> **v1.6**: `use_product_default` (1 link chung chung) đổi thành `use_product_link` + cột `product_link_type` — vì nghiệp vụ thật là **affiliate đa kênh**: 1 sản phẩm thường bán song song trên Shopee/TikTok/qua NCC, cần chọn đúng kênh cho từng vị trí chèn chứ không phải 1 "Mua ngay" duy nhất. `Modules/Product` cấu hình sẵn tối đa 4 link cố định (`shopee_url`/`tiktok_url`/`supplier_url`/`supplier_homepage_url`, xem `docs/product-catalog-spec.md` §6.2/§7) — Post chỉ **chọn dùng link nào** khi định nghĩa nút, không nhập tay URL. Dialog Jodit (§9.1 bước 5) chỉ liệt kê các `ProductLinkType` mà sản phẩm đã cấu hình URL (không hiện lựa chọn dẫn tới link rỗng). Sản phẩm đổi link (đổi shop, đổi domain) → sửa 1 lần ở `Product`, mọi nút `use_product_link` ở mọi bài đã chèn tự động trỏ đúng.

---

## 8. Enums (`Modules/Post/app/Enums/`)

```php
enum ArticleStatus: string {
    case Draft         = 'draft';
    case PendingReview = 'pending_review';
    case Published     = 'published';
    case Scheduled     = 'scheduled';
    case Archived      = 'archived';
}

enum ArticleFormat: string {
    case Article     = 'article';
    case Video        = 'video';
    case Activity     = 'activity';
    case Tip          = 'tip';
    case StepByStep   = 'step_by_step';
}

enum ProductBlockTemplate: string {
    case SingleCard  = 'single_card';   // 1 sản phẩm, ảnh lớn bên trái + nội dung bên phải
    case MultiGrid   = 'multi_grid';    // lưới 2-4 sản phẩm, mỗi ô 1 ảnh/tên/giá/nút
    case Banner      = 'banner';        // dải ngang full-width, nhấn mạnh 1 sản phẩm chủ đạo
    case CompactList = 'compact_list';  // danh sách gọn (ảnh nhỏ + tên + 1 nút), nhiều sản phẩm liên tiếp
}

enum ButtonUrlType: string {
    case UseProductLink = 'use_product_link'; // resolve qua product_link_type + Modules\Product\Enums\ProductLinkType::urlColumn()
    case CustomUrl      = 'custom_url';
    case Phone          = 'phone';
    case Zalo           = 'zalo';
    case Email          = 'email';
}

enum ButtonTarget: string {
    case SelfTab  = '_self';
    case NewTab   = '_blank';
}

enum ButtonStyle: string {
    case Primary   = 'primary';
    case Secondary = 'secondary';
    case Outline   = 'outline';
    case Ghost     = 'ghost';
}
```
Mỗi enum có thêm `label(): string` theo checklist `docs/PLATFORM_DESIGN.md` §12.6. Thêm template mới về sau = thêm 1 case + 1 file Blade component tương ứng (§9.3) — không đổi schema.

---

## 9. Content Editor & Product Box Plugin (trọng tâm module)

### 9.1 Kiến trúc plugin Jodit

Dự án đang dùng **Jodit 4.12.2 (MIT, free — không phải bản Pro)** qua wrapper có sẵn `resources/js/modules/jodit.js`, đã có sẵn 2 viên gạch tái dùng được:
- Cơ chế **custom toolbar button** (xem `popup.img` trong `jodit.js`, hoặc plugin gốc `hr`/`link` của Jodit dùng `pluginSystem.add()` + `Config.prototype.controls` + `editor.registerButton()`/`editor.registerCommand()`).
- Cơ chế **dialog**: `editor.dlg({...})` → `.setTitle()/.setContent(html)/.open()/.close()`.

> **v1.4 — ràng buộc nghiệp vụ mới**: **tối đa 3 `PostProductBlock`/bài viết** (không phải giới hạn chống-abuse chung chung như bản v1.2 nói "20 khối/bài" — đây là quy tắc biên tập thật: 1 bài chi tiết chỉ nên có tối đa 3 vị trí chèn CTA sản phẩm). Con số nhỏ này đổi lại cách thiết kế dialog — xem lại toàn bộ luồng bên dưới.

File mới `Modules/Post/resources/assets/js/jodit-post-product.js` đăng ký 1 nút **"Sản phẩm"** vào toolbar (thêm preset `post` trong `jodit.js`). Vì mỗi bài chỉ có tối đa 3 khối, **1 nút duy nhất** phục vụ cả 3 việc — thêm mới, sửa, xoá — thay vì tách "chèn mới" và "sửa" thành 2 luồng riêng như bản v1.2/v1.3:

**Click nút "Sản phẩm" → dialog mở ở chế độ Danh sách (list-view), không phải form nhập liệu ngay:**

0. **Lưu vị trí con trỏ trước khi mở dialog**: gọi `editor.s.save()` — Jodit chèn 1 marker `<span>` vô hình vào đúng vị trí con trỏ hiện tại (API thật, đã xác nhận trong `node_modules/jodit/esm/core/selection/selection.js`). Bắt buộc làm bước này **trước** khi mở dialog vì mở modal khiến editor mất focus/selection — không lưu lại thì lúc xác nhận form sẽ không còn biết chèn vào đâu, dễ rơi vào tình trạng luôn chèn ở vị trí sai (vd cuối bài) bất kể con trỏ gốc ở đâu. Đây chính là cơ chế xử lý đúng ví dụ thực tế: gõ 200 ký tự → đặt con trỏ → bấm "Sản phẩm" → dù thao tác trong dialog mất bao lâu, khối vẫn chèn đúng ngay sau 200 ký tự đó.
1. Đếm số khối hiện có bằng `editor.editor.querySelectorAll('.post-product-block').length` — thao tác DOM thuần, tức thời (0 khối tới tối đa 3, không đáng để đo hiệu năng). Hiển thị tiêu đề "Khối sản phẩm trong bài (N/3)".
2. Liệt kê tối đa 3 card, mỗi card ứng với 1 khối đã chèn: heading (nếu có) hoặc nhãn tự sinh ("Khối 1 — 3 sản phẩm"), badge template, số sản phẩm — kèm 2 nút "Sửa" / "Xoá" (xoá vẫn qua xác nhận 2 bước, §9.8.5).
3. Nút **"+ Thêm khối mới"**: nếu N < 3 → chuyển dialog sang **chế độ Form** (luồng chọn danh mục/sản phẩm/template/override/CTA mô tả bên dưới); nếu N = 3 → nút bị vô hiệu hoá kèm tooltip "Bài viết đã đạt tối đa 3 khối sản phẩm — hãy sửa hoặc xoá khối hiện có trước".
4. Bấm "Sửa" trên 1 card → chuyển dialog sang **chế độ Form**, tiền điền dữ liệu qua `parseExistingBlock()` (giống popup double-click ở §9.8.4 — **cùng 1 hàm dùng chung**, không viết 2 lần logic điền form).

**Chế độ Form (dùng chung cho cả "thêm mới" lẫn "sửa")**:

1. **Chọn danh mục sản phẩm** (select cascading, tái dùng đúng pattern `initOrgAddress` tỉnh→phường đã có trong `tom-select.js`) → lọc danh sách sản phẩm bên dưới. Danh mục + danh sách sản phẩm lấy từ **`Modules/Product`** qua `GET /api/v1/products/search` (`docs/product-catalog-spec.md` §10.3/§12) — **không** gọi trực tiếp `OcopProduct` như bản v1.0.
2. **Danh sách sản phẩm dạng checkbox** (không phải click-chọn-1-rồi-đóng như thiết kế cũ) — cho phép **tick nhiều sản phẩm cùng lúc** để chèn thành 1 khối `multi_grid`/`compact_list` (2-7 sản phẩm, §9.8.2), hoặc tick đúng 1 sản phẩm để chèn `single_card`/`banner`. Kết quả tìm kiếm luôn phân trang (catalog có thể tới hàng chục nghìn dòng) — dialog không bao giờ load hết danh sách vào 1 lần.
3. **Chọn template hiển thị** (radio 4 lựa chọn ở §7.5/§8, kèm ảnh minh hoạ mini) — mặc định `single_card` nếu chọn 1 sản phẩm, `multi_grid` nếu chọn ≥2.
4. **Với mỗi sản phẩm đã chọn**: hiển thị sẵn `name`/`price_label`/`cover_image_url` lấy thẳng từ `Product` (không cần nhập tay) — chỉ cho sửa nếu muốn **override riêng vị trí này** (`title_override`/`price_label_override`/`description_override`/`image_url_override`, để trống = dùng dữ liệu gốc; ẩn sau toggle "Tuỳ chỉnh", xem §9.8.3).
5. **Định nghĩa nút CTA**: với mỗi sản phẩm, dialog liệt kê **các link đã cấu hình sẵn** ở `Product` (chỉ hiện những `ProductLinkType` sản phẩm này có URL — vd chỉ có Shopee + NCC thì không hiện lựa chọn "TikTok") dưới dạng nút bấm nhanh "+ Mua trên Shopee" / "+ Mua trên TikTok" / "+ Xem tại nhà cung cấp" / "+ Website nhà cung cấp" — bấm 1 cái là thêm ngay 1 `PostProductBlockButton` với `url_type=use_product_link` + `product_link_type` tương ứng, **không cần nhập gì thêm** (label/url tự resolve lúc render, §9.5). Muốn CTA khác (số điện thoại riêng của bài, Zalo tư vấn, link ngoài đặc biệt...) thì "+ Thêm nút tuỳ biến" để override: `label`, `url_type` (Link tuỳ ý/Số điện thoại/Zalo/Email), `url`, `target`, `style`. Cho phép thêm nút cấp-khối riêng (vd "Xem tất cả") không gắn với sản phẩm nào. Sản phẩm chưa cấu hình link nào ở `Product` → không có nút nhanh nào hiện ra, chỉ còn lựa chọn "+ Thêm nút tuỳ biến".
6. Xác nhận:
   - Nếu đang **thêm mới** → gọi `editor.s.restore()` **trước tiên** để dựng lại đúng con trỏ đã lưu ở bước 0 (marker bị xoá trong quá trình này), rồi mới build HTML lồng nhau (§9.2) và `editor.s.insertHTML(...)` — khối chèn đúng tại vị trí con trỏ gốc, không phải cuối bài. Mặc định `insertCursorAfter=true` của `insertHTML` (API thật, xem `selection.js`) tự đặt con trỏ ngay sau khối vừa chèn, nên gõ tiếp nội dung sau đó nối liền đúng chỗ.
   - Nếu đang **sửa** → build lại HTML với **cùng `block_uuid`**, giữ nguyên `item_key`/`button_key` của phần không đổi (§9.8.4), thay thế đúng node cũ trong editor bằng chính node đó (không cần cơ chế con trỏ vì đang thao tác trên tham chiếu DOM có sẵn, không phải chèn mới), đóng dialog.
7. **Huỷ dialog** (Esc, click ra ngoài, nút Huỷ) ở bất kỳ bước nào của luồng "thêm mới": vẫn phải gọi `editor.s.restore()` (bỏ qua `insertHTML`) — nếu bỏ sót bước này, marker vô hình chèn ở bước 0 sẽ **kẹt lại vĩnh viễn** trong nội dung bài viết như 1 rò rỉ dữ liệu. Luồng "sửa" không tạo marker nào ở bước 0 nên không cần bước dọn này.

Việc gộp "thêm mới"/"sửa" vào **cùng 1 chế độ Form** (chỉ khác action lúc xác nhận: insert-tại-con-trỏ-đã-lưu vs replace-node-tại-chỗ) là điểm tối ưu kỹ thuật chính rút ra từ ràng buộc "tối đa 3 khối" — không cần 2 implementation song song, giảm chỗ dễ lệch logic giữa 2 luồng.

### 9.2 Cấu trúc placeholder trong content — data carrier, KHÔNG dùng JSON

Toàn bộ cấu hình (nhiều item, nhiều nút/item) được nhét vào **thuộc tính `data-*` của 1 cây DOM lồng nhau**, bọc trong `contenteditable="false"` để Jodit coi là 1 khối nguyên vẹn (không bị gõ đè/xoá lẻ):

```html
<div class="post-product-block" contenteditable="false"
     data-block-uuid="8f3a1c2e-..." data-template="multi_grid" data-heading="Sản phẩm gợi ý cho mẹ và bé">

  <div class="ppb-item" data-item-key="k7f2a" data-product-id="42"
       data-title-override="" data-price-label-override="" data-image-url-override="" data-description-override="">
    <div class="ppb-btn" data-btn-key="b1c9d" data-label="" data-url-type="use_product_link" data-product-link-type="shopee" data-url="" data-target="_blank" data-style="primary"></div>
    <div class="ppb-btn" data-btn-key="b2e01" data-label="Tư vấn qua Zalo" data-url-type="zalo"
         data-url="https://zalo.me/0912345678" data-target="_blank" data-style="outline"></div>
  </div>

  <div class="ppb-item" data-item-key="k9d31" data-product-id="108" data-title-override="Ưu đãi riêng cho bạn đọc" ...>
    ...
  </div>

</div><p><br></p>
```
`data-product-id` tham chiếu thẳng `products.id` (`Modules/Product`) — không còn `data-product-ref-type`/`data-product-ref-id` kiểu soft-link của bản v1.0. Các `data-*-override` để rỗng nếu dùng thẳng dữ liệu gốc từ catalog (trường hợp phổ biến nhất). `data-item-key`/`data-btn-key` là chuỗi ngắn (nanoid ~8 ký tự) sinh **1 lần duy nhất** lúc thêm item/nút trong dialog, giữ nguyên qua mọi lần sửa khối sau này — mục đích: cho phép `SyncProductBlocksAction` **upsert theo key thay vì xoá-tạo-lại toàn bộ**, bảo toàn `click_count` của các nút không đổi khi Marketing chỉ sửa 1 chi tiết nhỏ trong khối (xem §9.4 v1.2).

> ⚠️ **Toàn bộ giá trị `data-*` ở trên phải được HTML-escape trước khi build chuỗi** (client) — xem §9.8.1. Ví dụ `data-title-override` chứa dấu `"` hoặc `<` chưa escape sẽ phá vỡ ranh giới attribute, mở đường XSS.

Đây **không phải lưu JSON** — chỉ là 1 mini-DOM làm phương tiện truyền dữ liệu từ client, được `Modules/Post/app/Support/ArticleContentParser.php` (dùng `Symfony\Component\DomCrawler\Crawler` — đã có sẵn trong `composer.lock` qua dependency gián tiếp, cần khai báo trực tiếp trong `composer.json` nếu import trực tiếp trong code PHP) đọc ra và **ghi thành các dòng quan hệ thật** (`post_product_blocks`/`*_items`/`*_buttons`) khi lưu bài — xem §9.5. Trong editor, nội dung bên trong `.ppb-item`/`.ppb-btn` chỉ để hiển thị preview tĩnh (ảnh nhỏ + tên + giá lấy từ `Product` qua API picker), không phải nơi lưu trữ chính thức.

### 9.3 Template hiển thị & cách mở rộng

Mỗi giá trị `ProductBlockTemplate` map 1 file Blade component:

```
Modules/Post/resources/views/components/product-block/
├── single-card.blade.php    (nhận 1 PostProductBlockItem, layout ảnh lớn trái + nội dung phải)
├── multi-grid.blade.php     (nhận collection items, grid 2-4 cột, mỗi ô đủ ảnh/tên/giá/nút)
├── banner.blade.php         (nhận 1 item chủ đạo, full-width, nhấn mạnh price_label lớn)
└── compact-list.blade.php   (nhận collection items, danh sách dọc gọn, 1 nút/dòng)
```

`Modules/Post/app/Support/ArticleContentParser::render(PostArticle $article): string` thay từng placeholder trong `content` bằng `Blade::render("post::components.product-block.{$block->template->value}", ['block' => $block])`. Thêm template mới về sau: thêm 1 case enum + 1 file Blade — không đổi migration, không đổi Action nào khác.

### 9.4 Đồng bộ lúc lưu bài (Sync-on-save)

Trong `CreateArticleAction`/`UpdateArticleAction`, sau khi ghi `content`:

1. `ArticleContentParser::extractBlocks(string $html): array` — quét mọi `.post-product-block`, trả về mảng theo **đúng thứ tự xuất hiện** (dùng làm `sort_order`), mỗi phần tử gồm `block_uuid` + `template` + `heading` + danh sách item (`item_key`, `product_id`, override, danh sách button `button_key`...).
2. **Validate trước khi ghi DB** (bắt buộc — xem §9.8.1 lý do): số item/khối theo đúng giới hạn template (§7.6), số button/item ≤ 5 (§7.7), `product_id` resolve được qua `Product::find()` có tenant-scope (reject nếu null), `url`/`url_type` hợp lệ theo format tương ứng — riêng `url_type = use_product_link` thì validate `product_link_type` thuộc đúng enum `ProductLinkType` thay vì validate `url` (URL không lưu, luôn resolve động từ `Product` lúc render/click) (§9.8.1). Bài viết **không được lưu** nếu bất kỳ block nào vi phạm — trả lỗi validation rõ ràng theo từng block, không âm thầm cắt bớt dữ liệu.
3. **Trước khi mutate**: đọc `productIdsBefore` = distinct `product_id` hiện có trong `post_product_block_items` của bài (join qua `post_product_blocks.article_id`).
4. Trong `DB::transaction`, với mỗi `block_uuid`:
   - Đã tồn tại → cập nhật `template`/`heading`/`sort_order`, rồi **upsert `items` theo `item_key`** (giữ nguyên `id` nếu key đã có, tạo mới nếu key chưa có, xoá row nào có `item_key` không còn xuất hiện) và **upsert `buttons` theo `button_key`** cùng logic — **không xoá-tạo-lại toàn bộ** như thiết kế ban đầu, vì làm vậy sẽ reset `click_count` của những nút không hề đổi mỗi lần Marketing chỉ sửa 1 chi tiết nhỏ trong khối.
   - `block_uuid` mới (chưa có trong DB) → tạo `PostProductBlock` mới cùng `items`/`buttons` (mọi `item_key`/`button_key` đều mới).
   - `block_uuid` cũ trong DB nhưng **không còn xuất hiện** trong content mới → xoá (cascade xoá luôn `items`/`buttons`) — người dùng đã xoá khối đó khỏi bài.
5. **Sau khi mutate**: tính `productIdsAfter` = distinct `product_id` parse được từ content mới, rồi gọi rollup usage-count sang `Modules/Product` — xem §9.7.
6. Không cần cơ chế "dọn rác" kiểu orphan-tracking ảnh (`_cleanRemovedImages` trong `jodit.js`) vì **không có gì được ghi xuống DB cho tới khi form bài viết được submit thật** — khác với ảnh (phải upload file lên storage ngay lúc chọn).

### 9.5 Render lúc hiển thị

`PublicReading::GetPublishedArticleQuery/Handler` trả `PostArticle` kèm `productBlocks.items.product` + `productBlocks.items.buttons` (eager load qua FK thật `product_id` — 1 JOIN, không N+1 dù bài có nhiều block). Controller gọi `ArticleContentParser::render($article)` để có HTML cuối cùng đưa ra view — **luôn build từ dữ liệu DB hiện tại**, không cache HTML thô có product-box bên trong.

**Cơ chế fallback** (giải quyết bài toán scale nêu ở §1): với mỗi item, giá trị hiển thị = `$item->title_override ?? $item->product->name`, tương tự cho `price_label`/`description`/`image_url`; nút CTA khi `url_type = use_product_link` lấy `$button->label ?? ProductLinkType::from($button->product_link_type)->label()` cho nhãn, và `$item->product->{ProductLinkType::from($button->product_link_type)->urlColumn()}` cho URL đích (`docs/product-catalog-spec.md` §7). Nhờ vậy sửa giá/link 1 lần ở `Modules/Product` phản ánh ngay tại mọi vị trí đã chèn, không cần sửa lại từng bài viết. Nếu cột link tương ứng đang `null` (sản phẩm đã gỡ link đó sau khi nút được chèn) → Blade component ẩn hẳn nút này, không render nút dẫn tới URL rỗng. Nếu `product->status` là `discontinued`/`out_of_stock` (`docs/product-catalog-spec.md` §7), Blade component ẩn toàn bộ nút `use_product_link` và hiện nhãn "Sản phẩm hiện không khả dụng" thay vào đó — nút CTA tuỳ biến (`custom_url`/`phone`/`zalo`/`email`) vẫn hiển thị bình thường vì không phụ thuộc trạng thái/link sản phẩm.

### 9.6 Click tracking

Mỗi nút CTA render ra trỏ tới route trung gian, không trỏ thẳng URL đích:

```
GET /posts/cta/{button}  →  ProductBlockClickController::redirect
```
Action `RecordProductBlockClickAction`: trong 1 transaction — `increment('click_count')` trên `PostProductBlockButton`, resolve URL đích (`use_product_link` → lấy `product->{ProductLinkType::from($button->product_link_type)->urlColumn()}`, vd `product->shopee_url`; nếu cột đó `null` — sản phẩm đã gỡ link sau khi nút được chèn — trả 404 thay vì redirect tới URL rỗng; ngược lại tự thêm `tel:`/`https://zalo.me/`.../`mailto:` tuỳ `url_type`) rồi `redirect()->away(...)`, **và** gọi `ProductCatalogContract::incrementClickCount($productId)` (`docs/product-catalog-spec.md` §11.2) để cộng dồn `products.total_cta_click_count` — rollup phục vụ báo cáo "top sản phẩm CTA hiệu quả nhất" mà không phải `SUM()` qua bảng click có thể lên hàng triệu dòng theo thời gian. Không cần JS beacon, hoạt động cả khi tắt JS, cho Marketing số liệu conversion theo từng nút/từng vị trí/**từng kênh affiliate** (biết rõ click đến từ nút "Shopee" hay "TikTok" của cùng 1 sản phẩm).

### 9.7 Usage-count rollup — "sản phẩm này đang được bao nhiêu bài viết dùng" & xử lý khi xoá sản phẩm

Đối xứng với cơ chế mô tả ở `docs/product-catalog-spec.md` §9.1 — chi tiết hoá phần thuộc trách nhiệm của `Post`:

- Sau bước 4 ở §9.4, so `$productIdsAfter` với `$productIdsBefore`:
  - `$added = productIdsAfter − productIdsBefore` → gọi `ProductCatalogContract::incrementArticleUsageCount($productId)` cho từng id (sản phẩm **lần đầu** được bài này nhắc tới).
  - `$removed = productIdsBefore − productIdsAfter` → gọi `decrementArticleUsageCount($productId)` (bài **không còn** nhắc sản phẩm đó ở vị trí nào nữa — phân biệt với việc chỉ gỡ bớt 1 trong 2 vị trí cùng trỏ 1 sản phẩm, trường hợp đó không gọi gì vì bài vẫn còn dùng).
  - Sản phẩm nằm trong cả 2 tập → không gọi gì, giữ nguyên counter.
- **Khi nào 1 sản phẩm không xoá được**: `Modules/Product::DeleteProductAction` chặn xoá cứng nếu `products.used_in_articles_count > 0` (đọc trực tiếp cột đã được `Post` duy trì ở trên, không cần query chéo module). Admin chỉ có thể chuyển `status → discontinued` (hoặc `inactive`/`out_of_stock`) — dữ liệu ở các bài viết cũ **không bị ảnh hưởng gì cả**: tên/ảnh/giá vẫn hiển thị nguyên vẹn (vì `product_id` vẫn còn tồn tại, chỉ đổi `status`), chỉ riêng các nút `use_product_link` bị ẩn/thay bằng nhãn "Sản phẩm hiện không khả dụng" — xem §9.5. Nút CTA tuỳ biến (`custom_url`/`phone`/`zalo`/`email`) không bị ảnh hưởng vì không phụ thuộc `status`.
- **Drill-down cho admin**: `ArticleAuthoring::ListArticlesReferencingProductQuery/Handler(productId, ?onlyPublished = false)` — trả danh sách bài viết (mọi status khi gọi từ trang quản trị) đang tham chiếu 1 `product_id`, dùng bởi route `GET dashboard/posts/articles?product_id=` (§12) mà màn hình danh sách sản phẩm bên `Modules/Product` trỏ link sang (`docs/product-catalog-spec.md` §9.1) để admin chủ động rà soát/thay thế CTA trước khi ngừng kinh doanh 1 sản phẩm. Cùng 1 Query này, gọi với `onlyPublished = true`, phục vụ luôn khối "Bài viết liên quan" công khai ở `PublicReading` (§11.4) — không cần viết 2 lần logic.
- **Đối soát định kỳ**: artisan command `post:recalculate-product-usage-counts` (đăng ký Laravel Scheduler, chạy hàng đêm) — với mỗi `product_id` xuất hiện trong `post_product_block_items`, tính `COUNT(DISTINCT post_product_blocks.article_id)` thật rồi gọi `ProductCatalogContract::setArticleUsageCount($productId, $trueCount)` để sửa lệch nếu có (rollup tăng/giảm dần có rủi ro lệch nếu 1 request giữa chừng lỗi).

### 9.8 An toàn & Trải nghiệm chỉnh sửa (bổ sung sau rà soát bảo mật/UX)

Product Box đưa 1 bề mặt input mới (HTML do editor tự build, chèn qua `insertHTML`) vào 1 field vốn đã render thẳng ra trang công khai (`content`) — cần xử lý nghiêm túc như input không đáng tin, **kể cả khi người tạo là tài khoản nội bộ có quyền**, vì: (a) Jodit có nút "source" cho sửa tay HTML thô — không có gì đảm bảo HTML luôn đúng cấu trúc do JS của ta sinh ra, (b) 1 tài khoản Marketing bị lộ mật khẩu vẫn là kịch bản phải phòng.

#### 9.8.1 An toàn bắt buộc (không phải tuỳ chọn, phải có trước khi lên Production)

| Rủi ro | Biện pháp |
|---|---|
| XSS qua thuộc tính `data-*` khi build chuỗi HTML phía client | HTML-escape mọi giá trị text (`title_override`, `label`, tên sản phẩm hiển thị preview...) trước khi chèn vào template string trong `jodit-post-product.js`. Khi render ra trang công khai, Blade dùng `{{ }}` cho mọi field text — tuyệt đối không `{!! !!}` ngoài chính khối HTML `content` đã qua sanitize (dòng dưới) |
| Stored XSS qua `content` nói chung (không riêng product-block) | Sanitize `content` bằng bộ lọc allowlist (thẻ/thuộc tính được phép) ngay trong `CreateArticleAction`/`UpdateArticleAction` trước khi lưu DB — allowlist phải khai báo rõ `div.post-product-block` + toàn bộ `data-*` mong đợi của nó, mọi thẻ/thuộc tính khác (`<script>`, `on*=`, `javascript:`) bị strip |
| Tin nhầm `product_id` đọc từ HTML, kể cả xuyên tenant | `SyncProductBlocksAction` luôn resolve qua `Product::find($id)` (có global scope `TenantAwareModel`, tự lọc theo `organization_id` hiện tại) — không insert thẳng ID thô vào cột FK. Không tìm thấy (sai tenant/không tồn tại) → chặn lưu, báo lỗi rõ theo block |
| Open redirect / `javascript:` href trong nút CTA | Validate `url` theo đúng `url_type` trước khi lưu: `custom_url` → `filter_var(FILTER_VALIDATE_URL)` + bắt buộc scheme `http`/`https`; `phone` → regex số điện thoại; `email` → `filter_var(FILTER_VALIDATE_EMAIL)`; `zalo` → bắt buộc dạng `https://zalo.me/...` hoặc số điện thoại thuần; `use_product_link` → không nhận `url`/`label` thô từ client (bỏ qua nếu có), chỉ validate `product_link_type` thuộc đúng enum `ProductLinkType` — URL thật luôn resolve từ cột đã được validate sẵn ở tầng `Product` lúc lưu sản phẩm (`docs/product-catalog-spec.md` §10.2), không tin giá trị `url` client tự chèn vào `data-url` của placeholder |
| 1 bài viết bị nhồi HTML rác tạo hàng nghìn dòng con, hoặc vi phạm quy tắc biên tập | Cap cứng ở `SyncProductBlocksAction` (§9.4 bước 2): **tối đa 3 khối/bài** (quy tắc biên tập, không chỉ chống-abuse — xem §9.1), số item/khối theo §7.6 (1 hoặc 2-7 tuỳ template), tối đa 5 nút/item — vượt bất kỳ giới hạn nào thì chặn lưu, không âm thầm cắt bớt |

#### 9.8.2 Giới hạn số sản phẩm mỗi khối — 4-7 sản phẩm

Áp dụng cho template nhiều sản phẩm (`multi_grid`/`compact_list`): **tối thiểu 2, tối đa 7 sản phẩm/khối**, tối ưu trải nghiệm dựng sẵn cho khoảng **4-7 sản phẩm** (đây là vùng dùng phổ biến nhất trong thực tế — ít hơn 4 thì `single_card`/`banner` thường hợp lý hơn). `single_card`/`banner` giữ nguyên đúng 1 sản phẩm/khối.

Lý do giới hạn:
1. **UX** — dialog chọn sản phẩm phải hiển thị gọn danh sách đã chọn dạng card thu nhỏ; quá 7 sản phẩm bắt đầu phải cuộn dài, khó rà soát trước khi chèn.
2. **Thẩm mỹ layout** — `multi_grid` (2-4 cột) và `compact_list` được thiết kế cho một cụm sản phẩm gọn, không phải danh sách dài; nhồi 20 sản phẩm vào 1 khối phá bố cục và làm trang chậm.
3. **Vẫn linh hoạt cho nhu cầu lớn hơn** — nếu Marketing cần giới thiệu nhiều hơn 7 sản phẩm trong 1 bài, giải pháp đúng là **chèn thêm 1 khối thứ 2** (khác `heading`, vd "Sản phẩm cho mẹ" / "Sản phẩm cho bé") thay vì nhồi vào 1 khối — điều này bài viết đã hỗ trợ sẵn (nhiều `PostProductBlock`/bài không giới hạn).

Validate ở **cả 2 lớp**: client (dialog vô hiệu hoá checkbox khi đã chọn đủ 7, hiện "Đã chọn 5/7 sản phẩm") để phản hồi tức thì, và server (`SyncProductBlocksAction`, §9.8.1) làm lớp chặn thật sự — không tin client-side validation là đủ.

#### 9.8.3 UX chống quá tải form khi chọn nhiều sản phẩm

Form gốc (mỗi sản phẩm hiện đủ override + nhiều nút ngay khi chọn) quá tải khi chọn 5-7 sản phẩm cùng lúc. Thiết kế lại dialog:

- Mỗi sản phẩm đã chọn hiện dạng **card thu gọn 1 dòng** (ảnh nhỏ + tên + giá lấy sẵn từ catalog) trong danh sách bên phải dialog — không expand field nào theo mặc định.
- Toàn bộ override (`title`/`price_label`/`description`/`image`) + cấu hình nút CTA nằm sau 1 toggle **"Tuỳ chỉnh"** trên từng card — đóng mặc định. Không chạm gì thì sản phẩm dùng nguyên dữ liệu catalog + các nút CTA nhanh `use_product_link` đã cấu hình sẵn — **0 field bắt buộc điền** cho trường hợp phổ biến nhất.
- Input nút CTA đổi theo `url_type` đã chọn: chọn "Số điện thoại" → ô nhập chuyển `type="tel"` kèm placeholder mẫu; chọn "Zalo" → tự điền sẵn tiền tố `https://zalo.me/`; chọn "Email" → `type="email"` — giảm lỗi format ngay từ lúc gõ, không phải đợi submit thất bại.
- Cho sắp xếp lại thứ tự sản phẩm đã chọn ngay trong dialog (nút mũi tên lên/xuống trên mỗi card) để set `sort_order` trực quan, không phải sửa lại sau khi chèn.

#### 9.8.4 Sửa & xoá khối đã chèn — tái dùng cơ chế `popup` có sẵn của Jodit

Bản thiết kế trước chỉ có luồng "chèn mới", chưa có cách sửa/xoá 1 khối đã tồn tại trong bài. Bổ sung bằng cách tái dùng **chính cơ chế `popup` mà `jodit.js` đã dùng cho ảnh** (`popup.img` — xem `resources/js/modules/jodit.js`), áp cho selector `div.post-product-block`:

```js
popup: {
    'div.post-product-block': [
        { name: 'pencil', tooltip: 'Sửa khối sản phẩm', exec: (editor, block) => openProductPicker(editor, parseExistingBlock(block)) },
        { name: 'bin',    tooltip: 'Xoá khối',           exec: (editor, block) => editor.s.removeNode(block) },
    ],
}
```
`parseExistingBlock(block)` đọc lại toàn bộ `data-*` (kể cả `item_key`/`button_key`) từ DOM node đã chọn, đổ vào state dialog để hiển thị đúng dữ liệu hiện có. Khi xác nhận sửa, dialog build lại HTML với **cùng `block_uuid`** (không sinh mới) và **giữ nguyên `item_key`/`button_key`** của những sản phẩm/nút không bị xoá — đây chính là điều kiện để `SyncProductBlocksAction` (§9.4 v1.2) upsert đúng thay vì xoá-tạo-lại, bảo toàn `click_count` lịch sử. Nhất quán hoàn toàn với pattern `popup.img` đã có, không cần cơ chế mới trong Jodit.

> **v1.4**: đây là **1 trong 2 lối vào** cùng gọi chung `openProductPicker(editor, parseExistingBlock(block))` — lối kia là nút "Sửa" trong dialog "Quản lý khối sản phẩm" (§9.1). Popup double-click phù hợp khi đang thao tác trực tiếp trong bài (thấy khối, sửa ngay); dialog quản lý phù hợp khi cần cái nhìn tổng quan cả 3 khối (vd bài dài, khối nằm rải rác khó tìm bằng mắt). Cả 2 dùng chung 1 hàm `parseExistingBlock` + 1 hàm build HTML lúc xác nhận — không nhân đôi logic.

#### 9.8.5 Phân tích hiệu năng & độ đúng — Sửa khối

**Vấn đề nếu làm cẩu thả**: mở dialog sửa 1 khối có 7 sản phẩm mà gọi API lấy dữ liệu từng sản phẩm 1 (7 request tuần tự/song song riêng lẻ) — đây chính là kiểu N+1 phía client, gây lag rõ rệt khi mạng chậm hoặc catalog lớn.

**Thiết kế tối ưu (sửa lại so với bản phác thảo ban đầu)**:
1. **Mở dialog tức thì, không chờ mạng**: `parseExistingBlock()` chỉ đọc DOM (đồng bộ, không I/O) — dialog hiện ngay lập tức với dữ liệu đã có sẵn trong `content` (tên/ảnh/giá được cache dưới dạng preview tĩnh lúc chèn ban đầu, xem §9.2). Người dùng thấy khối cũ hiện ra **không có độ trễ nào**.
2. **Refresh dữ liệu sống ở nền**: ngay sau khi dialog mở, gọi **1 lần duy nhất** `GET /api/v1/products/batch?ids=42,108,...` (`docs/product-catalog-spec.md` §10.3 — tra theo primary key, tốc độ không phụ thuộc catalog lớn hay nhỏ) để lấy tên/giá/ảnh/`status` **hiện tại** của toàn bộ sản phẩm trong khối cùng lúc, rồi cập nhật lại các card đã hiển thị (thường chỉ mất vài trăm ms, không chặn thao tác của người dùng trong lúc chờ).
3. **Đúng data ngay cả khi có thay đổi từ lúc chèn tới lúc sửa**: nếu 1 sản phẩm trong khối đã bị đổi `status → discontinued` hoặc (hiếm) không còn resolve được, card tương ứng hiện rõ cảnh báo "Sản phẩm này đã ngừng kinh doanh / không còn tồn tại" kèm nút "Gỡ khỏi khối" — không để dialog vỡ layout hay âm thầm giữ dữ liệu cũ sai lệch.
4. **Lưu bài sau khi sửa không re-sync toàn bộ nếu không đổi gì**: `SyncProductBlocksAction` (§9.4) khi upsert, so sánh giá trị parse được với dòng hiện có trong DB trước khi issue `UPDATE` — item/button nào không đổi giá trị thì **bỏ qua, không ghi DB** (tránh `updated_at` bị đổi vô cớ và giảm số query cho những bài có nhiều khối nhưng chỉ sửa 1 chi tiết nhỏ).

**Xoá khối — đã tối ưu, xác nhận lại vì sao**:
- `editor.s.removeNode(block)` xoá **nguyên 1 subtree DOM trong 1 lệnh gọi** — vì toàn bộ `item`/`button` đều nằm lồng bên trong node `contenteditable="false"` ngoài cùng (§9.2), không có phần tử con nào "mồ côi" cần dọn riêng. Đây là thao tác thuần client, O(1), không có network call, không lag dù khối có bao nhiêu sản phẩm/nút.
- Việc xoá **chỉ ảnh hưởng state đang soạn thảo trong trình duyệt** — dòng DB thật (`post_product_blocks`/`*_items`/`*_buttons`) chỉ mất đi khi bài viết được **lưu** thật sự (§9.4 bước 4: `block_uuid` không còn xuất hiện → xoá cascade). Đây là chủ đích: đóng tab/huỷ chỉnh sửa mà chưa lưu thì không mất gì ở DB — nhất quán với lý do "không cần dọn rác" đã nêu ở §9.4.
- **Bổ sung 1 bước còn thiếu**: xoá 1 khối đã cấu hình nhiều sản phẩm + nút CTA tuỳ biến là mất công sức biên tập thật, nên nút "🗑 Xoá" trong popup cần **xác nhận 2 bước** (click lần 1 đổi label thành "Bấm lần nữa để xác nhận" trong ~3 giây, hoặc `editor.confirm(...)` có sẵn của Jodit — xem `Dlgs::confirm()` đã dùng ở plugin `link`) thay vì xoá ngay sau 1 click — tránh mất dữ liệu do bấm nhầm, vì Jodit undo/redo không đảm bảo phục hồi đúng 1 atomic node đã bị `removeNode`.

#### 9.8.6 Phân tích kỹ thuật — Thêm mới / Sửa khối dưới ràng buộc tối đa 3 khối/bài

Con số 3 nhỏ tới mức phần lớn "tối ưu hiệu năng" không nằm ở tốc độ xử lý (3 phần tử DOM là chi phí không đáng kể ở bất kỳ máy nào) mà nằm ở **giảm số bước thao tác và tránh trạng thái nhầm lẫn** cho người biên tập. Cụ thể:

1. **Đếm khối hiện có — không cần cơ chế theo dõi (tracking) phức tạp**: `editor.editor.querySelectorAll('.post-product-block').length` chạy lại **mỗi lần** người dùng click nút "Sản phẩm" là đủ — không cần đăng ký thêm `editor.events.on('change', ...)` để duy trì 1 biến đếm phản ứng theo thời gian thực. Lý do: tần suất click nút này thấp (vài lần/lượt soạn bài), chi phí query DOM cho tối đa 3 phần tử là microseconds — thêm state tracking ở đây là over-engineering không cần thiết, chỉ tăng bề mặt lỗi (biến đếm bị lệch nếu người dùng xoá khối bằng cách khác, vd bôi đen + Delete thay vì qua popup).
2. **Gộp "thêm mới" và "sửa" vào cùng 1 chế độ Form (§9.1)** thay vì 2 hàm/2 luồng riêng: giảm chỗ dễ lệch (vd sửa `single_card` quên đồng bộ validate giới hạn item giống hệt lúc thêm mới). Điểm khác biệt duy nhất giữa 2 luồng là hành động lúc xác nhận — `insertHTML` (thêm) hay thay thế node cũ theo `block_uuid` (sửa) — toàn bộ bước chọn sản phẩm/template/override/CTA và toàn bộ validate (giới hạn item/nút, format URL...) dùng chung 100% code.
3. **Chặn sớm ở phía client, xác nhận lại ở server**: nút "+ Thêm khối mới" bị vô hiệu hoá ngay khi đếm được 3 khối — người dùng không mất công điền cả form rồi mới bị từ chối lúc lưu bài. Nhưng `SyncProductBlocksAction` vẫn phải đếm lại và chặn ở server (§9.4/§9.8.1) — không tin đếm phía client là đủ, vì nội dung vẫn có thể bị sửa tay qua "source" view của Jodit (đúng nguyên tắc "không tin dữ liệu client" đã nêu ở §9.8.1) để chèn thêm khối thứ 4 bằng tay.
4. **Giới hạn 3 khối × tối đa 7 item × tối đa 5 nút giữ toàn bộ hệ thống nằm trong vùng dữ liệu nhỏ, có thể dự đoán được**: trường hợp xấu nhất 1 bài chỉ tạo ra tối đa 3×7 = 21 dòng `post_product_block_items` + 21×5 = 105 dòng `post_product_block_buttons`. Nhân với "hàng nghìn bài viết" ở quy mô mục tiêu (§1), tổng số dòng vẫn nằm gọn trong hàng chục nghìn — đúng tầm index/JOIN đã thiết kế ở §7, không cần điều chỉnh gì thêm ở tầng schema vì giới hạn nghiệp vụ mới này.

#### 9.8.7 Chèn khối đúng vị trí con trỏ giữa nội dung dài — ví dụ cụ thể

**Kịch bản kiểm chứng**: gõ 200 ký tự → đặt con trỏ ngay sau → bấm "Sản phẩm" → chèn khối 1 → gõ tiếp 500 ký tự → bấm "Sản phẩm" → chèn khối 2. Đây là bài test thực tế cho đúng 3 thứ: (a) chèn có đúng vị trí con trỏ hay luôn rơi về 1 chỗ cố định, (b) 2 khối có giữ đúng thứ tự vật lý khi lưu/hiển thị, (c) HTML sinh ra có hợp lệ khi khối bị chèn giữa 1 đoạn văn bản (không phải đầu/cuối bài).

**(a) Đúng vị trí con trỏ** — giải quyết bằng `editor.s.save()`/`restore()` ở bước 0/6 phía trên (đã là điểm sửa chính của bản v1.4 này). Không có bước này, cách làm ngầm định "luôn `insertHTML` vào vị trí selection hiện tại lúc bấm nút" sẽ sai ngay khi dialog list-view/form-view (mất vài chục giây thao tác) làm mất selection gốc.

**(b) Thứ tự vật lý được bảo toàn tự nhiên, không cần xử lý thêm**: `ArticleContentParser::extractBlocks()` (§9.4) duyệt `content` theo đúng thứ tự DOM (`querySelectorAll` trả về node theo document order), gán `sort_order` theo thứ tự đó — nên dù giữa 2 khối có 500 ký tự hay 5.000 ký tự văn bản, thứ tự khối trong DB vẫn luôn khớp thứ tự xuất hiện thật trong bài. Không có logic nào cần biết "khối cách nhau bao nhiêu ký tự" — chỉ cần biết thứ tự trước/sau.

**(c) HTML hợp lệ khi chèn giữa đoạn văn**: nếu con trỏ đang nằm giữa 1 thẻ `<p>` (trường hợp phổ biến sau khi gõ 200 ký tự liên tục, chưa xuống dòng), chèn 1 `<div class="post-product-block">` (block-level) vào giữa `<p>` về nguyên tắc HTML không hợp lệ (`<p>` không được chứa phần tử block). Trình duyệt/Jodit tự tách `<p>` thành 2 đoạn quanh phần tử chèn (hành vi chuẩn của contenteditable khi insertNode 1 block-level giữa inline content — cùng cơ chế mà chính plugin `hr` gốc của Jodit đã xử lý, xem `Dom.closest(..., Dom.isBlock, ...)` trong `hr.js`), nhưng đây là hành vi của trình duyệt/thư viện, không phải thứ tự tự viết ra — **bắt buộc kiểm thử thủ công thật trên UI** (không chỉ tin theo lý thuyết) để xác nhận không sinh ra `<p><div>...</div></p>` lồng sai. Ghi rõ thành acceptance criterion riêng (§18) thay vì giả định suông.

**Ở phía render công khai**: `ArticleContentParser::render()` (§9.5) chỉ thay thế đúng node placeholder bằng partial Blade tương ứng, toàn bộ text bao quanh (200 ký tự, 500 ký tự...) giữ nguyên không đụng tới — không có rủi ro nào riêng cho trường hợp nhiều khối xen giữa nhiều đoạn văn.

---

## 10. Permissions (RBAC)

Thêm vào `app/Enums/PermissionEnum.php`:
```php
// ══ POST (Bài viết theo danh mục + Product CTA Box) ═════════════
// Marketing=Soạn thảo | CEO/Ops=Duyệt & publish | System_Admin=Full + quản lý danh mục | còn lại=View (bài đã published)
case POST_CATEGORY_MANAGE = 'post_category.manage';
case POST_ARTICLE_VIEW    = 'post_article.view';
case POST_ARTICLE_CREATE  = 'post_article.create';
case POST_ARTICLE_EDIT    = 'post_article.edit';
case POST_ARTICLE_DELETE  = 'post_article.delete';
case POST_ARTICLE_PUBLISH = 'post_article.publish';
```

`config/permissions.php`: `POST_ARTICLE_VIEW` vào **cả 8 role block** (ai cũng xem được bài đã publish); `POST_ARTICLE_CREATE/EDIT/DELETE` vào `R::MARKETING` + `R::ADMIN`; `POST_ARTICLE_PUBLISH` vào `R::CEO`, `R::OPS`, `R::ADMIN`; `POST_CATEGORY_MANAGE` chỉ `R::ADMIN`.

`Modules/Post/database/seeders/PostPermissionSeeder.php` clone cấu trúc `AcademyPermissionSeeder` (`docs/academy-spec.md` §9) — dùng tên role lowercase thật (`system_admin`, `ceo`, `marketing`...), **không** dùng Title-Case sai như policy cũ của `KcItem`.

**Policy:**
- `PostCategoryPolicy`: `viewAny/view` → `can('post_article.view')`; `create/update/delete` → `can('post_category.manage')`.
- `PostArticlePolicy`: `viewAny/view` → `can('post_article.view')` (chỉ bài `published` nếu không có quyền edit); `create` → `can('post_article.create')`; `update/delete` → `can('post_article.edit')`/`can('post_article.delete')` **và** (`$article->created_by === $user->id` hoặc có `post_article.publish`); `submitForReview` → `can('post_article.edit')`; `publish/schedule/archive` → `can('post_article.publish')`.

**Sidebar**: gate bằng `@can(\App\Enums\PermissionEnum::POST_ARTICLE_VIEW->value)`, sub-link "Danh mục" chỉ hiện khi `@can(POST_CATEGORY_MANAGE)`.

---

## 11. Feature Slices — Chi tiết

### 11.1 Slice `CategoryManagement` (quyền `post_category.manage`)
- **Actions**: `CreateCategoryAction`/`UpdateCategoryAction`/`DeleteCategoryAction` (chặn xoá nếu còn bài viết gán trực tiếp — kiểm `post_article_categories`), `ReorderCategoriesAction` (nhận `[category_id => sort_order]`).
- **Queries**: `GetCategoryTreeQuery/Handler` (dựng cây đệ quy từ `parent_id`, dùng cho sidebar quản trị + menu công khai), `ListCategoriesForAdminQuery/Handler` (phẳng, kèm đếm số bài viết).
- **Http**: `CategoryAdminController` — resource controller mỏng.

### 11.2 Slice `ArticleAuthoring` (quyền `post_article.create/edit/delete/publish`)
- **Actions**:
  - `CreateArticleAction`/`UpdateArticleAction` — nhận `ArticleData` (title/excerpt/content/format/category_ids/is_primary_category_id/tag_ids), trong `DB::transaction`: lưu `PostArticle` → sync `post_article_categories`/`post_article_tag` → gọi `ArticleContentParser` để sync product blocks (§9.4).
  - `SubmitArticleForReviewAction` — `status: draft → pending_review`.
  - `PublishArticleAction` — `status → published`, set `published_at = now()` (nếu chưa có), `approved_by/approved_at`; bắn `ArticlePublished`.
  - `ScheduleArticleAction` — `status → scheduled`, `published_at = future date`; 1 scheduled command (`post:publish-due`, chạy qua Laravel Scheduler) quét `scheduled` có `published_at <= now()` → tự chuyển `published`.
  - `ArchiveArticleAction`/`DeleteArticleAction`.
- **Queries**: `ListArticlesForAdminQuery/Handler` (mọi status, filter theo category/format/status, **hỗ trợ filter `product_id`** — dùng chung implementation với `ListArticlesReferencingProductQuery/Handler` bên dưới, xem §9.7), `GetArticleDetailForAdminQuery/Handler` (kèm product blocks đầy đủ để render lại trong editor), `ListArticlesReferencingProductQuery/Handler(productId, ?onlyPublished = false)` (§9.7 — phục vụ cả drill-down quản trị lẫn "bài viết liên quan" công khai ở §11.4).
- **Http**: `ArticleAdminController` (action `index` nhận query string `?product_id=` để phục vụ drill-down từ `Modules/Product`, xem §12).

### 11.3 Slice `ProductBlockPicker` (API nội bộ phục vụ dialog Jodit, quyền `post_article.create` hoặc `edit`)
- **v1.1**: slice này **không tự query catalog** — chỉ là 1 lớp mỏng proxy sang `Modules/Product`, giữ đúng ranh giới module (`docs/product-catalog-spec.md` §11).
- **Queries**: `ListProductCategoriesForPickerQuery/Handler` và `SearchProductsForPickerQuery/Handler` gọi `ProductCatalogContract::search(...)` (hoặc gọi thẳng API `GET /api/v1/products/search` nếu picker chạy phía client, xem §12) — trả kèm đầy đủ `name`/`price_label`/`cover_image_url`/`shopee_url`/`tiktok_url`/`supplier_url`/`supplier_homepage_url` thật từ catalog, không còn thiếu `price`/`image` như khi còn dựa vào `OcopProduct`.
- **Http**: `ProductPickerApiController@categories`, `@search` trong `Modules/Post` có thể bị loại bỏ hoàn toàn nếu client (`jodit-post-product.js`) gọi thẳng `/api/v1/products/search` của `Modules/Product` — quyết định cụ thể để ở Phase 6 lúc code (xem §17), không ảnh hưởng schema.

### 11.4 Slice `PublicReading` (quyền `post_article.view`, hoặc không cần đăng nhập nếu public — xem §16 Open Questions)
- **Actions**: `RecordArticleViewAction` (increment `view_count`, debounce theo session để tránh spam F5), `RecordProductBlockClickAction` (§9.6).
- **Queries**: `GetPublishedArticleQuery/Handler`, `ListArticlesByCategoryQuery/Handler` (kèm cây danh mục con để render sidebar giống ảnh mẫu 3). Khối "Bài viết liên quan" (nếu cần) tái dùng `ArticleAuthoring::ListArticlesReferencingProductQuery/Handler(productId, onlyPublished: true)` — không viết lại logic riêng.
- **Http**: `PublicArticleController@show`, `PublicCategoryController@show`, `ProductBlockClickController@redirect`.

---

## 12. Routes

```php
// routes/web.php
Route::middleware(['auth'])->prefix('dashboard/posts')->name('backend.post.')->group(function () {
    Route::resource('categories', CategoryAdminController::class);
    Route::post('categories/reorder', [CategoryAdminController::class, 'reorder'])->name('categories.reorder');

    Route::resource('articles', ArticleAdminController::class);
    Route::post('articles/{article:uuid}/submit', [ArticleAdminController::class, 'submit'])->name('articles.submit');
    Route::post('articles/{article:uuid}/publish', [ArticleAdminController::class, 'publish'])->name('articles.publish');
    Route::post('articles/{article:uuid}/schedule', [ArticleAdminController::class, 'schedule'])->name('articles.schedule');
    Route::post('articles/{article:uuid}/archive', [ArticleAdminController::class, 'archive'])->name('articles.archive');
});

// Public (không middleware auth — xem §16 Open Questions về phạm vi public)
Route::prefix('bai-viet')->name('post.public.')->group(function () {
    Route::get('/', [PublicCategoryController::class, 'index'])->name('home');
    Route::get('danh-muc/{category:slug}', [PublicCategoryController::class, 'show'])->name('category');
    Route::get('{article:slug}', [PublicArticleController::class, 'show'])->name('article');
});
Route::get('posts/cta/{button}', [ProductBlockClickController::class, 'redirect'])->name('post.cta.redirect');

// routes/api.php — nội bộ, dùng bởi dialog Jodit
Route::middleware(['auth:sanctum'])->prefix('api/v1/posts')->group(function () {
    Route::get('product-categories', [ProductPickerApiController::class, 'categories']);
    Route::get('products', [ProductPickerApiController::class, 'search']);
});
```
`GET dashboard/posts/articles?product_id={id}` (drill-down từ `Modules/Product`, xem §9.7) dùng chung route `backend.post.articles.index` có sẵn ở trên — không cần route riêng, chỉ thêm filter `product_id` vào `ListArticlesForAdminQuery`.

**Console command** (`routes/console.php` hoặc `app/Console/Kernel.php` của `Modules/Post`):
```php
Schedule::command('post:recalculate-product-usage-counts')->dailyAt('02:00');
```
Route path/tên theo `docs/PLATFORM_DESIGN.md` §12.2 (`backend.{noun}.{action}`, `/dashboard/{resource}`), route-model-binding theo `uuid`/`slug` — không lộ `id` số nguyên.

---

## 13. Seed Data

`Modules/Post/database/seeders/PostDemoContentSeeder.php` — chỉ chạy nếu `post_categories` rỗng trong org đang seed:
- 4-5 `PostCategory` gốc + 2-3 cấp con (lấy cảm hứng từ ảnh 1: Marriage/Newborn/Toddlers/Babies).
- 3-4 `PostArticle` `published`, mỗi bài có tối thiểu 1 `PostProductBlock` (đủ cả 4 template) để test render ngay không cần soạn tay.

---

## 14. Ánh xạ UI ↔ ảnh mẫu

| Ảnh mẫu (`docs/tintuc/`) | Route | Ghi chú |
|---|---|---|
| `1.png` (menu Resources, cây theo độ tuổi) | `post.public.home` | Menu công khai build từ `GetCategoryTreeQuery` |
| `2.png` (trang danh mục nhiều định dạng: Articles/Videos/Activities/Tips) | `post.public.category` | Lọc theo `format` (query string `?format=video`), không phải sub-category riêng — đúng quyết định §7.6/§8 |
| `3.png` (sidebar cây Development→Behaviour, bài chi tiết) | `post.public.article` | Sidebar = cây `PostCategory` quanh danh mục chính (`is_primary`) của bài |
| `4.png`/`5.png` (listing filter, activities theo độ tuổi) | `post.public.category` | Filter theo category/format qua query string |
| `2026-07-06_19-59/22-05/22-09.png` (Motherly/Babylist — box sản phẩm inline, nút Shop) | `post.public.article` → render qua `ArticleContentParser` | Chính là `PostProductBlock` — xem toàn bộ §9 |

---

## 15. Key Design Decisions

| Quyết định | Lý do | Đánh đổi |
|---|---|---|
| Module gộp `Post` duy nhất, không tách `PostCategory`/`PostArticle` | Bounded context nhỏ, luôn đi cùng nhau (quyết định của stakeholder) | Nếu sau này category cần dùng chung cho nhiều loại content khác ngoài Article, phải tách lại — chấp nhận được vì chưa có nhu cầu đó |
| **(v1.1)** Product box lấy dữ liệu qua **FK cứng tới module `Product` riêng**, không còn nhập tay/soft-link `OcopProduct` như v1.0 | Ở quy mô hàng chục nghìn sản phẩm × hàng nghìn bài viết, nhập tay từng vị trí không scale (sửa giá phải touch từng dòng); soft-link string không JOIN hiệu quả, không ràng buộc toàn vẹn | Thêm 1 module phụ thuộc (`Modules/Product`) phải scaffold trước — đổi lại sửa giá 1 lần phản ánh khắp nơi, JOIN hiệu quả cho báo cáo (xem `docs/product-catalog-spec.md`) |
| Cấu hình block/item/button là bảng quan hệ, placeholder trong content chỉ là data-carrier tạm thời | Tuân "No JSON storage"; cho phép query "sản phẩm nào được nhắc ở bài nào", đếm click theo từng nút | Phải viết `ArticleContentParser` (parse + re-sync mỗi lần save) thay vì đọc thẳng content — độ phức tạp cao hơn nhưng đổi lại truy vấn được structured data |
| **(v1.1→v1.6)** Thêm `url_type = use_product_link` (đổi tên từ `use_product_default` ở v1.1), không còn "mọi nút đều bắt buộc nhập URL" như v1.0 | Sản phẩm có sẵn tối đa 4 link affiliate cố định ở tầng `Product` (`docs/product-catalog-spec.md` §6.2/§7) — không cần Marketing nhập lại URL giống nhau ở hàng nghìn vị trí chèn cùng 1 sản phẩm, và chọn được đúng kênh (Shopee/TikTok/NCC) cho từng vị trí thay vì chỉ 1 CTA chung | Vẫn giữ `custom_url`/`phone`/`zalo`/`email` cho trường hợp cần CTA đặc biệt riêng từng bài — không mất tính linh hoạt của v1.0 |
| **(v1.6)** `use_product_link` cần thêm cột `product_link_type` (chọn 1 trong 4 kênh) thay vì fallback về đúng 1 URL mặc định | Thực tế nghiệp vụ: 1 sản phẩm bán song song nhiều kênh (Shopee + TikTok + NCC) cùng lúc, mỗi bài/vị trí chèn có thể muốn dẫn tới kênh khác nhau — 1 slot `default_cta_url` duy nhất không đủ biểu diễn | Thêm 1 cột + 1 bước chọn kênh trong dialog — đổi lại linh hoạt đúng với mô hình affiliate đa sàn thay vì phải tự dựng qua `custom_url` gõ tay (mất tính "sửa 1 nơi phản ánh khắp nơi") |
| Multi-select sản phẩm trong 1 lần chèn (thay vì chèn từng cái) | Khớp yêu cầu "chọn 1 hoặc nhiều sản phẩm", khớp template `multi_grid`/`compact_list` | Dialog phức tạp hơn (checkbox + form lặp lại theo từng item đã chọn) |
| Không dọn "rác" cho product-block kiểu orphan-tracking ảnh | Không có gì ghi DB cho tới khi submit form bài viết thật (khác ảnh phải upload file ngay) | Không cần — không phải đánh đổi |
| **(v1.1)** `used_in_articles_count` đếm theo **bài viết** (distinct), không theo số vị trí chèn; tính diff before/after mỗi lần save thay vì query lại từ đầu | Đúng ngữ nghĩa "bao nhiêu bài dùng sản phẩm này"; tính incremental rẻ hơn `COUNT(DISTINCT)` chạy lại mỗi lần hiển thị danh sách sản phẩm | Rollup có thể lệch nếu có lỗi giữa chừng — cần lệnh đối soát `post:recalculate-product-usage-counts` chạy định kỳ (§9.7) |
| `ListArticlesReferencingProductQuery` dùng chung cho cả drill-down quản trị (mọi status) lẫn "bài viết liên quan" công khai (chỉ published), phân biệt qua tham số `onlyPublished` | Tránh viết trùng 2 lần cùng 1 logic lọc theo `product_id` | Query cần 1 nhánh điều kiện thêm, không đáng kể |
| **(v1.2)** Giới hạn cứng 2-7 sản phẩm/khối (`multi_grid`/`compact_list`), 1 sản phẩm/khối (`single_card`/`banner`), tối đa 5 nút/item, validate ở cả client lẫn server | Chống quá tải UI dialog, giữ layout template đẹp, chặn abuse (nhồi HTML tạo hàng nghìn dòng con) | Cần nhiều hơn 7 sản phẩm → phải tách 2 khối riêng trong bài (đã hỗ trợ sẵn multi-block/bài), không phá được giới hạn |
| **(v1.2)** `item_key`/`button_key` sinh ở client, giữ nguyên qua các lần sửa; sync theo cơ chế **upsert-by-key** thay vì xoá-tạo-lại toàn bộ | Bảo toàn `click_count` của nút không đổi khi Marketing chỉ sửa 1 chi tiết nhỏ — xoá-tạo-lại (thiết kế v1.1) sẽ reset counter mỗi lần save, sai lệch số liệu báo cáo | `ArticleContentParser`/`SyncProductBlocksAction` phức tạp hơn 1 chút (upsert theo key thay vì delete+insert đơn giản) — chấp nhận được vì đổi lấy đúng đắn dữ liệu |
| **(v1.2)** Sanitize `content` + validate `product_id`/`url` ở tầng server là bắt buộc, không tin dữ liệu client dù đúng cấu trúc mong đợi | Jodit có chế độ "source" cho sửa tay HTML thô — content về bản chất luôn là input không đáng tin, kể cả từ tài khoản nội bộ (rủi ro tài khoản bị lộ) | Thêm 1 bước validate/sanitize mỗi lần lưu bài — chi phí nhỏ so với rủi ro XSS/rò rỉ xuyên tenant |
| **(v1.2)** Sửa/xoá khối qua cơ chế `popup` có sẵn của Jodit (giống `popup.img`), không xây UI riêng | Nhất quán với pattern đã có trong `jodit.js`, không cần học thêm API mới của Jodit | Cần viết `parseExistingBlock()` để đọc lại state từ DOM — công sức nhỏ, 1 lần |
| **(v1.3)** Mở dialog sửa khối tức thì từ cache DOM, refresh dữ liệu sống bằng **1 request batch** (`/api/v1/products/batch`) thay vì N request riêng lẻ | N request riêng cho N sản phẩm trong khối là N+1 phía client, gây lag rõ khi mạng chậm hoặc khối có 5-7 sản phẩm | Cần thêm 1 endpoint batch ở `Modules/Product` (§10.3 `docs/product-catalog-spec.md`) — chi phí nhỏ, tra theo primary key nên luôn nhanh bất kể catalog lớn nhỏ |
| **(v1.3)** `SyncProductBlocksAction` dirty-check trước khi `UPDATE` (bỏ qua item/button không đổi giá trị) | Tránh `updated_at` bị đổi vô cớ, giảm số query khi bài có nhiều khối nhưng chỉ sửa 1 chi tiết nhỏ | Cần so sánh giá trị parse được với dòng hiện có trước khi ghi — thêm vài phép so sánh rẻ, không đáng kể |
| **(v1.3)** Xoá khối yêu cầu xác nhận 2 bước (click lần 2 mới xoá thật), thay vì xoá ngay sau 1 click | 1 khối có nhiều sản phẩm + nút CTA tuỳ biến là công sức biên tập thật; Jodit undo/redo không đảm bảo phục hồi đúng 1 atomic node đã bị `removeNode` | Thêm 1 bước thao tác — chấp nhận được, đổi lấy chống mất dữ liệu do bấm nhầm |
| **(v1.4)** Tối đa **3 khối/bài** — quy tắc biên tập thật, không chỉ chống-abuse | Giữ trang chi tiết gọn, CTA không loãng; con số nhỏ đủ để thiết kế lại UX theo hướng "quản lý danh sách" thay vì "chèn rồi quên" | Không hỗ trợ bài cần giới thiệu nhiều cụm sản phẩm hơn — chấp nhận được vì đây là chủ đích biên tập, không phải giới hạn kỹ thuật |
| **(v1.4)** 1 nút toolbar "Sản phẩm" duy nhất mở dialog **list-view** (xem 0-3 khối hiện có + Sửa/Xoá) thay vì luôn mở thẳng form "chèn mới" | Với chỉ tối đa 3 khối, list-view cho cái nhìn tổng quan ngay, giảm việc phải tự tìm khối nằm rải rác trong bài dài; nút "+ Thêm khối" tự vô hiệu hoá kèm giải thích khi đã đủ 3 — rõ ràng hơn nút bị xám không lý do | Thêm 1 bước dialog (list → form) so với mở thẳng form như trước — chấp nhận được vì đổi lấy khả năng quản lý tổng quan |
| **(v1.4)** "Thêm mới" và "Sửa" dùng chung 1 chế độ Form, chỉ khác hành động lúc xác nhận (insert vs replace-by-`block_uuid`) | Tránh 2 implementation song song dễ lệch validate/giới hạn item-nút giữa 2 luồng | Không có — thuần lợi, không đánh đổi |
| **(v1.4)** Đếm số khối bằng query DOM trực tiếp mỗi lần mở dialog, không duy trì biến đếm phản ứng theo `change` event | Chi phí đếm 0-3 phần tử là không đáng kể; state tracking thêm vào chỉ tăng bề mặt lỗi (lệch đếm nếu khối bị xoá bằng cách khác ngoài popup) mà không đổi lại lợi ích đo được | Không có — đây là quyết định tránh over-engineering, không phải đánh đổi hiệu năng |
| **(v1.5)** Chèn khối bằng `editor.s.save()` (trước khi mở dialog) + `editor.s.restore()` (ngay trước `insertHTML` lúc xác nhận, hoặc lúc huỷ dialog) — thay vì giả định selection vẫn còn nguyên sau khi đóng/mở modal | Mở dialog (list-view → form-view, có thể mất hàng chục giây thao tác) làm editor mất focus/selection; không lưu lại vị trí con trỏ thì khối sẽ chèn sai chỗ (vd luôn rơi về cuối bài) thay vì đúng ngay sau đoạn text user đang gõ dở | Thêm 2 lời gọi API + 1 nhánh dọn dẹp khi huỷ dialog — chi phí rất nhỏ so với rủi ro chèn sai vị trí liên tục |

---

## 16. Open Questions (cần xác nhận trước khi triển khai)

1. Trang công khai (`post.public.*`) có cần **không đăng nhập** (SEO, chia sẻ ra ngoài) hay chỉ hiển thị nội bộ cho user đã login trong tổ chức? Ảnh hưởng tới middleware group + có cần sitemap/meta OG hay không.
2. ~~`ListProductCategoriesForPickerQuery` lấy danh mục sản phẩm từ đâu~~ — **đã giải quyết (v1.1)**: module `Product` riêng với `product_categories` độc lập, không phụ thuộc `OcopProductGroup` (xem `docs/product-catalog-spec.md` §2).
3. Cần **log click chi tiết theo thời điểm/IP** (không chỉ tổng `click_count`) để vẽ biểu đồ hiệu quả CTA theo thời gian không? Nếu có, thêm bảng `post_product_block_click_logs` ở phase sau — lưu ý rollup `products.total_cta_click_count` (`docs/product-catalog-spec.md` §9) vẫn giữ nguyên dù có thêm log chi tiết, 2 cơ chế phục vụ 2 mục đích khác nhau (tổng nhanh vs biểu đồ theo thời gian).
4. Multi-tenant: bài viết có cần công khai theo **subdomain riêng của từng tổ chức** (`{org}.domain.com/bai-viet`) hay dùng chung 1 domain có filter theo `organization_id` trong route?
5. `PostArticlePolicy::update/delete` có nên giới hạn "chỉ tác giả hoặc người có quyền publish" hay mọi người có `post_article.edit` đều sửa được bài của người khác trong cùng tổ chức?
6. **(mới, v1.1)** Khi `ProductCatalogContract::search()` chạy phía server (Blade admin) thì gọi trực tiếp qua Contract; nhưng dialog Jodit chạy phía **client** (JS `fetch()`) — có nên expose thẳng API `/api/v1/products/search` của `Modules/Product` cho client gọi (đơn giản hơn), hay bắt buộc đi qua 1 endpoint proxy trong `Modules/Post` để giữ ranh giới module chặt hơn (thêm 1 lớp, chậm hơn 1 hop)? Khuyến nghị: gọi thẳng API `Product` (đơn giản, giảm latency), vì đây vốn đã là API công khai nội bộ (`auth:sanctum`), không phải chi tiết triển khai riêng tư.

---

## 17. Phased Implementation Plan

> **Tiền điều kiện**: `Modules/Product` (`docs/product-catalog-spec.md`) phải hoàn thành tối thiểu Phase 0-3 (scaffold + data model + permissions + CRUD catalog) **trước khi** bắt đầu Phase 5 của Post dưới đây — Phase 5 cần bảng `products` đã tồn tại để tạo FK.

| Phase | Nội dung | Output kiểm tra được |
|---|---|---|
| **Phase 0 — Scaffold module** | `module.json`, `composer.json`, `PostServiceProvider`/`RouteServiceProvider`/`EventServiceProvider`, `config/config.php`, thêm `"Post": true` vào `modules_statuses.json` | `php artisan module:list` thấy `Post` enabled |
| **Phase 1 — Data model** | 7 migration (§7), 6 model (`Models/`), 6 enum (§8) | `php artisan migrate` chạy sạch; tạo thử 1 `PostCategory`→`PostArticle` qua tinker không lỗi |
| **Phase 2 — Permissions** | `PermissionEnum` +6 case, `config/permissions.php`, `PostPermissionSeeder`, `PostDatabaseSeeder`, đăng ký vào `SystemDataSeeder` | `php artisan db:seed` không lỗi; bảng `permissions` có `post_*` |
| **Phase 3 — Slice `CategoryManagement`** | Actions/Queries/Http/Policy + Blade admin (cây danh mục kéo-thả sắp xếp) | Tạo được cây danh mục 3 cấp qua UI |
| **Phase 4 — Slice `ArticleAuthoring` (chưa có product block)** | Actions/Data/Queries/Http/Policy + Blade admin (form Jodit preset `post`, chọn category/tag/format, workflow submit/publish/schedule/archive) | Soạn + publish 1 bài viết đầy đủ, không có box sản phẩm, hiển thị đúng ở trang công khai |
| **Phase 5 — `ArticleContentParser` + Product Block schema** | `post_product_blocks`/`*_items`/`*_buttons` migration (FK `product_id` tới `products`, cột `item_key`/`button_key`, cột `product_link_type` trên `*_buttons` — xem §7.7), `ArticleContentParser::extractBlocks/render`, sync **upsert-by-key** trong `CreateArticleAction`/`UpdateArticleAction` kèm validate giới hạn số lượng + tenant-scope + diff usage-count (§9.4, §9.7, §9.8.1/§9.8.2) | Chèn thủ công đoạn HTML placeholder qua "source code" view của Jodit → save → kiểm tra DB có đúng dòng tương ứng + `products.used_in_articles_count` tăng đúng; xoá placeholder → save → dòng DB bị xoá theo + counter giảm đúng; cố tình chèn > 7 item hoặc `product_id` sai tenant → bị chặn lưu với lỗi rõ ràng; chèn nút `url_type=use_product_link` kèm `product_link_type=shopee` → save → dòng `post_product_block_buttons` lưu đúng `product_link_type`, cột `url`/`label` để `null` (không lưu URL thô từ client) |
| **Phase 6 — Sanitization & validation server-side** | Bộ lọc allowlist HTML cho `content` (bao gồm cấu trúc `.post-product-block`/`.ppb-item`/`.ppb-btn`), validate `url`/`url_type` theo format tương ứng cho `custom_url`/`phone`/`zalo`/`email`; riêng `use_product_link` validate `product_link_type` thuộc enum `ProductLinkType` và **bỏ qua mọi `url`/`label` client gửi kèm** (không ghi xuống DB), escape output ở mọi Blade component product-block (§9.8.1) | Cố tình lưu `content` chứa `<script>`/`onerror=`/`javascript:` href → bị strip/chặn trước khi ghi DB; `url_type=phone` với giá trị không phải số điện thoại → bị từ chối validate; cố tình chèn tay `url_type=use_product_link` kèm `data-url="https://evil.example"` → save → cột `url` trong DB vẫn là `null`, không lưu giá trị độc hại đó; `product_link_type` không thuộc 4 giá trị hợp lệ → bị từ chối validate |
| **Phase 7 — Jodit plugin `jodit-post-product.js`** | Nút toolbar "Sản phẩm" mở dialog **list-view** (đếm khối hiện có, gate "+ Thêm khối mới" ở N=3) + **chế độ Form dùng chung** cho thêm mới/sửa (multi-select tối đa 7, form thu gọn theo §9.8.3, input đổi theo `url_type`, sinh `item_key`/`button_key`, build placeholder HTML đã escape) + popup double-click sửa/xoá nhanh (§9.1/§9.8.4/§9.8.5/§9.8.6) | Bài chưa có khối nào → mở dialog thấy list-view rỗng + nút "+ Thêm khối" hoạt động; thêm đủ 3 khối → nút "+ Thêm khối" tự vô hiệu hoá kèm tooltip; xoá 1 khối → nút kích hoạt lại; double-click khối đã chèn → dialog hiện ngay không chờ mạng, dữ liệu refresh qua đúng 1 request batch; sửa 1 nút rồi lưu → `click_count` của các nút khác không bị reset; click "Xoá khối" 1 lần → chưa xoá, phải xác nhận lần 2 |
| **Phase 8 — Template rendering** | 4 Blade component (§9.3), route `post.cta.redirect` + `RecordProductBlockClickAction` | Trang công khai render đủ 4 kiểu template, bấm nút CTA tăng đúng `click_count` và chuyển hướng đúng `url_type` |
| **Phase 9 — Drill-down & đối soát** | `ListArticlesReferencingProductQuery/Handler` + filter `product_id` trên `ArticleAdminController@index`, command `post:recalculate-product-usage-counts` + đăng ký Scheduler | Từ danh sách sản phẩm bên `Modules/Product`, click số "N bài viết" → thấy đúng danh sách bài viết trong `Post`; cố tình làm lệch counter rồi chạy command → số được sửa đúng |
| **Phase 10 — Seed & Sidebar** | `PostDemoContentSeeder`, sidebar entry gate theo `post_article.view`/`post_category.manage` | Org mới seed có sẵn danh mục + bài demo đủ 4 template dùng được ngay |
| **Phase 11 — Kiểm thử & nghiệm thu** | Xem §18 | Tất cả kịch bản chấp nhận pass |

---

## 18. Testing & Acceptance Criteria

**`CategoryManagement`**
- Given danh mục còn bài viết gán trực tiếp → When xoá → Then bị chặn hoặc yêu cầu gỡ bài trước (tuỳ quyết định UX, ghi rõ trong PR).

**`ArticleAuthoring`**
- Given user role `viewer` (không có `post_article.create`) gọi route tạo bài → Then 403.
- Given bài `draft` → When `SubmitArticleForReviewAction` → Then `status = pending_review`.
- Given user không có `post_article.publish` → When gọi action publish → Then 403 dù là tác giả.
- Given `ScheduleArticleAction` với `published_at` trong tương lai → When command `post:publish-due` chạy trước thời điểm đó → Then bài vẫn `scheduled`, chưa hiện công khai.

**`ArticleContentParser` / Product Block**
- Given content có 2 placeholder `.post-product-block` với `block-uuid` mới, mỗi cái trỏ 1 `product_id` khác nhau → When `UpdateArticleAction` → Then tạo đúng 2 dòng `post_product_blocks` + đúng số `items`/`buttons` tương ứng, `sort_order` khớp thứ tự xuất hiện trong HTML, và `products.used_in_articles_count` của cả 2 sản phẩm tăng đúng 1.
- Given bài đã có 1 block → When user xoá placeholder đó khỏi content rồi save → Then dòng `post_product_blocks` cùng `items`/`buttons` bị xoá cứng (cascade), không còn sót; `products.used_in_articles_count` của sản phẩm đó giảm đúng 1.
- Given 1 `PostProductBlockButton` với `url_type=phone` → When gọi `post.cta.redirect` → Then redirect tới `tel:{url}`, `click_count` tăng đúng 1.
- Given 1 sản phẩm có sẵn `shopee_url` và `tiktok_url` (nhưng `supplier_url`/`supplier_homepage_url` để trống) → When mở dialog Form chọn CTA cho sản phẩm này → Then chỉ hiện 2 nút nhanh "Mua trên Shopee"/"Mua trên TikTok", không hiện lựa chọn NCC.
- Given 1 nút `url_type=use_product_link`, `product_link_type=shopee` → When admin sửa `shopee_url` của sản phẩm đó ở `Modules/Product` (không đụng gì tới bài viết) → Then click nút trên trang công khai chuyển hướng đúng tới `shopee_url` **mới**.
- Given 1 nút `url_type=use_product_link`, `product_link_type=tiktok`, nhưng sau đó admin xoá trắng `tiktok_url` của sản phẩm → Then nút này bị ẩn khi render trang công khai; nếu cố truy cập trực tiếp `post.cta.redirect` của nút đó → Then trả 404, không redirect tới URL rỗng.
- Given content chèn tay (qua "source" view) 1 nút với `url_type=use_product_link` kèm `data-url="https://evil.example/phish"` → When lưu bài → Then giá trị `data-url` client gửi lên bị bỏ qua hoàn toàn, `SyncProductBlocksAction` không ghi `url` đó vào DB — URL thật luôn resolve lại từ `product->{urlColumn()}` lúc render/click, không tin dữ liệu client.
- Given trang chi tiết bài viết đã publish, `PostProductBlockItem` không set `price_label_override` → When admin sửa `price`/`price_label` của `Product` gốc trong trang quản trị `Modules/Product` (không sửa content bài viết) → Then trang công khai hiển thị giá mới ngay lần load sau.
- Given cố xoá 1 `Product` đang có `used_in_articles_count = 2` → When `DeleteProductAction` → Then bị chặn; đổi `status → discontinued` thay vào đó → Then thành công, box cũ vẫn hiển thị tên/ảnh/giá nhưng nút CTA mặc định thay bằng "Sản phẩm hiện không khả dụng".
- Given click vào cột "N bài viết" ở danh sách sản phẩm (`Modules/Product`) → Then điều hướng sang `dashboard/posts/articles?product_id={id}` và thấy đúng danh sách bài viết đang tham chiếu sản phẩm đó (mọi status).

**An toàn (§9.8.1)**
- Given content chèn tay (qua "source" view của Jodit) 1 placeholder với `data-title-override='"><script>alert(1)</script>'` → When lưu bài → Then giá trị bị escape/strip, không có `<script>` nào xuất hiện trong `content` đã lưu DB lẫn HTML render ra trang công khai.
- Given `data-product-id` trỏ tới 1 `product_id` thuộc tổ chức khác (không cùng tenant với bài viết đang sửa) → When `SyncProductBlocksAction` chạy → Then bị chặn lưu với lỗi rõ ràng, **không** tạo `PostProductBlockItem` nào trỏ sai tenant.
- Given 1 nút CTA với `url_type=custom_url` và `url="javascript:alert(1)"` → When lưu bài → Then bị từ chối validate (scheme không thuộc `http`/`https`).
- Given 1 nút CTA với `url_type=phone` và `url="not-a-phone-number"` → When lưu bài → Then bị từ chối validate.
- Given content chứa 4 placeholder `.post-product-block` (vượt cap 3/bài, chèn tay qua "source" view) → When lưu bài → Then bị chặn, báo lỗi vượt giới hạn, không tạo dòng nào.

**Giới hạn số lượng & UX (§9.8.2/§9.8.3/§9.8.4)**
- Given template `multi_grid`, chọn 8 sản phẩm trong dialog → Then checkbox sản phẩm thứ 8 bị vô hiệu hoá phía client (giới hạn 7); nếu vẫn cố lưu content đã bị sửa tay vượt 7 item → Then server chặn lưu.
- Given template `multi_grid` chỉ chọn 1 sản phẩm → Then bị chặn ở dialog (tối thiểu 2), gợi ý đổi sang `single_card`.
- Given 1 khối đã chèn có 2 nút CTA, 1 nút đã có `click_count = 15` → When double-click khối → sửa `label` của nút còn lại → lưu bài → Then nút có `click_count = 15` giữ nguyên giá trị (không bị reset về 0), xác nhận cơ chế upsert-by-key (`item_key`/`button_key`) hoạt động đúng, không phải xoá-tạo-lại.
- Given double-click 1 khối đã chèn → Then dialog mở lại đúng danh sách sản phẩm + override + nút CTA hiện có, sinh từ `parseExistingBlock()`, không mất dữ liệu.

**Hiệu năng & độ đúng khi sửa/xoá khối (§9.8.5)**
- Given double-click 1 khối có 7 sản phẩm → Then dialog hiển thị (từ cache DOM) trong < 100ms, không chờ bất kỳ network request nào để mở.
- Given dialog sửa khối vừa mở → Then đúng **1** request `GET /api/v1/products/batch?ids=...` được gửi đi (không phải 7 request riêng lẻ) để refresh dữ liệu sống.
- Given 1 sản phẩm trong khối đã bị đổi `status → discontinued` từ lúc chèn tới lúc mở sửa → When dialog refresh xong → Then card sản phẩm đó hiện cảnh báo rõ ràng + nút "Gỡ khỏi khối", không vỡ layout, không giữ im lặng dữ liệu cũ.
- Given lưu lại bài viết mà không thực sự đổi field nào của 1 item/button cụ thể → Then `UPDATE` **không** được issue cho dòng đó (`updated_at` không đổi) — kiểm bằng query log.
- Given click "🗑 Xoá khối" đúng 1 lần → Then khối **chưa** bị xoá, nút chuyển sang trạng thái "Bấm lần nữa để xác nhận"; click lần 2 trong thời hạn → Then khối bị xoá khỏi editor ngay (thao tác thuần client, không network call).

**Giới hạn 3 khối/bài & dialog quản lý (§9.1/§9.8.6)**
- Given bài viết chưa có khối sản phẩm nào → When click nút toolbar "Sản phẩm" → Then dialog mở ở list-view, hiện "Khối sản phẩm trong bài (0/3)", nút "+ Thêm khối mới" đang bật.
- Given bài đã có đúng 3 khối → When click nút toolbar "Sản phẩm" → Then list-view hiện đủ 3 card, nút "+ Thêm khối mới" bị vô hiệu hoá kèm tooltip giải thích rõ lý do (không phải xám im lặng).
- Given đang ở trạng thái 3/3 khối → When xoá 1 khối (xác nhận 2 bước) → Then nút "+ Thêm khối mới" được kích hoạt lại ngay, không cần đóng/mở lại dialog.
- Given click "Sửa" trên 1 card ở list-view → Then dialog chuyển sang chế độ Form với dữ liệu tiền điền đúng qua `parseExistingBlock()` — kiểm tra dùng **chung hàm** với luồng popup double-click (không có 2 bản implementation khác nhau cho cùng 1 việc).
- Given cố tình chèn tay (qua "source" view) khối thứ 4 rồi lưu bài, bỏ qua giới hạn phía client → Then server (`SyncProductBlocksAction`) vẫn chặn lưu — xác nhận validate phía server độc lập với validate phía client, không tin client là đủ.

**Chèn khối đúng vị trí con trỏ giữa nội dung dài (§9.8.7)**
- Given gõ đúng 200 ký tự, đặt con trỏ ngay sau ký tự cuối, mở dialog và mất >10 giây thao tác chọn sản phẩm trước khi xác nhận → When khối được chèn → Then khối nằm ngay sau 200 ký tự đó trong DOM, **không** rơi xuống cuối bài hay bất kỳ vị trí nào khác.
- Given tiếp tục gõ 500 ký tự ngay sau khối 1 vừa chèn → Then 500 ký tự này nối liền ngay sau khối 1 (xác nhận `insertCursorAfter` đặt đúng con trỏ), không bị chèn lẫn vào giữa nội dung cũ.
- Given lặp lại thao tác chèn khối 2 ngay sau 500 ký tự đó → When lưu bài → Then thứ tự `sort_order` của 2 khối trong DB đúng bằng thứ tự xuất hiện thật trong `content` (khối 1 trước, khối 2 sau), bất kể khoảng cách văn bản giữa chúng.
- Given trang công khai hiển thị bài viết trên → Then thứ tự hiển thị đúng: 200 ký tự → box sản phẩm 1 → 500 ký tự → box sản phẩm 2, toàn bộ text 2 đoạn giữ nguyên không bị cắt/lặp.
- Given mở dialog "Sản phẩm" giữa đoạn văn bản (con trỏ nằm giữa 1 thẻ `<p>` chưa xuống dòng) rồi huỷ dialog (không xác nhận) → Then nội dung bài viết **không** còn sót lại bất kỳ thẻ `<span data-jodit-selection-marker>` nào (kiểm tra qua "source" view của Jodit).
- Given chèn khối vào giữa 1 đoạn `<p>` đang có chữ trước và sau vị trí con trỏ → Then HTML sinh ra hợp lệ (đoạn `<p>` được tách đúng thành 2 phần quanh khối, không có `<div>` lồng bên trong `<p>`) — kiểm tra bằng validator HTML hoặc xem trực tiếp DOM đã render.

**`PublicReading`**
- Given bài `status != published` → When truy cập `post.public.article` không có quyền `post_article.edit` → Then 404 (không lộ nội dung chưa duyệt).
- Given F5 liên tục trang chi tiết trong cùng session → Then `view_count` không tăng liên tục (debounce theo session).
