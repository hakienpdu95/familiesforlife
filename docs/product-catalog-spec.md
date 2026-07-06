# Product Module — Danh mục Sản phẩm & Dịch vụ (Đặc tả kỹ thuật)

> **Pattern stack:** AVSA + CQRS-lite + Laravel Modules (NWIDART 13) + Laravel Actions (lorisleiva 2.x)
> **Module tham chiếu kiến trúc:** `Modules/OcopRubric`, `Modules/Academy`, `Modules/News` (`docs/news-module-spec.md`) — Feature-first.
> **Spec version:** 1.1 — 2026-07-06 (bổ sung §9.1 rollup `used_in_articles_count` + drill-down bài viết đang dùng sản phẩm, đơn giản hoá Contract §11.2 sau khi có counter)
> **Bối cảnh phát sinh:** `docs/news-module-spec.md` §1 phát hiện `OcopProduct` không có `price`/`image`/`description` (chỉ là sổ đăng ký chấm điểm), nên thiết kế v1 của Product CTA Box phải nhập tay dữ liệu hiển thị ở từng vị trí chèn — không tối ưu khi scale tới hàng chục nghìn sản phẩm × hàng nghìn bài viết. Module này tách catalog sản phẩm/dịch vụ thành 1 nguồn sự thật độc lập, dùng chung cho `News` (và bất kỳ tính năng nào khác cần "sản phẩm có giá/ảnh/mô tả" sau này).
> **Route đã có sẵn (placeholder)**: `routes/web.php` dòng 57-58 đã đặt sẵn `backend.products.index`/`backend.products.create` → `abort(503, 'Module đang phát triển')` — đúng chỗ dành cho module này, không đặt tên route mới.

---

## 1. Bối cảnh & mục tiêu

Yêu cầu: tách 1 bảng `products` độc lập làm "tiền đề tối ưu hoá" cho Product CTA Box trong `News`, thiết kế DB **tối ưu, linh hoạt, chịu được scale hàng nghìn bài viết × hàng chục nghìn sản phẩm được gọi ra trong box**.

3 vấn đề scale cụ thể mà thiết kế phải giải quyết:

1. **Cập nhật giá/ảnh 1 lần, phản ánh khắp nơi** — nếu dữ liệu hiển thị (tên/giá/ảnh) bị copy vào từng vị trí chèn (thiết kế v1 của News), 1 sản phẩm được nhắc ở 5.000 bài thì sửa giá phải touch 5.000 dòng. → Cần 1 bảng `products` làm nguồn sự thật, `news_product_block_items` chỉ giữ `product_id` (FK) + override tuỳ chọn.
2. **Tìm kiếm sản phẩm trong dialog Jodit khi catalog có hàng chục nghìn dòng** — cần index đúng cột lọc (`organization_id, category_id, status, name`), phân trang, không load hết vào dropdown.
3. **Không xoá cứng sản phẩm đã được hàng trăm bài viết cũ tham chiếu** — cần vòng đời theo `status` (active/inactive/discontinued/out_of_stock) thay vì xoá, và **rollup counter** (`total_cta_click_count`) để không phải `SUM()` qua hàng triệu dòng click log khi cần báo cáo "top sản phẩm hiệu quả nhất".

---

## 2. Đặt tên & vị trí module

**Tên module: `Product`** (`Modules/Product`) — module độc lập, **không** thuộc `News` và **không** thuộc `OcopRubric`.

| Vì sao tách riêng, không nhét vào `News` hay `OcopRubric` |
|---|
| `News` là module biên tập nội dung (bounded context "content"), `Product` là module quản lý thực thể kinh doanh (bounded context "catalog") — 2 domain khác nhau, `News` **phụ thuộc** `Product` (một chiều), không nên gộp ngược. |
| `OcopRubric::OcopProduct` là sổ đăng ký/chấm điểm tuân thủ pháp lý OCOP (không có giá/ảnh, vòng đời gắn với quy trình chấm điểm) — khác hẳn mục đích "catalog trưng bày để bán/giới thiệu" của module này. Giữ 2 khái niệm tách biệt, liên kết qua soft-reference tuỳ chọn (`products.source_ref_type/id`) khi 1 sản phẩm OCOP muốn có mặt trong catalog trưng bày. |
| Tái dùng được cho các tính năng tương lai ngoài `News` (vd trang catalog công khai, giỏ hàng, Marketplace bán hàng thật) mà không phải sửa `News`. |

Thuộc **Platform Core**, ngang hàng `News`/`Academy`. Route quản trị lấp vào chỗ stub có sẵn: `dashboard/products` (`backend.products.*`).

---

## 3. Phạm vi (Scope Boundary)

### 3.1 Trong phạm vi (đợt này)

1. Quản trị **cây danh mục sản phẩm/dịch vụ** (`product_categories`, không giới hạn cấp).
2. Quản trị **catalog sản phẩm/dịch vụ**: CRUD, giá/nhãn giá, ảnh đại diện, CTA mặc định, vòng đời trạng thái, soft-link tuỳ chọn tới `OcopProduct`.
3. **API tìm kiếm/phân trang** phục vụ dialog chọn sản phẩm trong Jodit (`News` gọi qua module boundary, không query thẳng DB của `Product`).
4. **Rollup counter** view/click phục vụ báo cáo "sản phẩm được nhắc nhiều nhất" / "CTA hiệu quả nhất" — số liệu tổng hợp từ `News`, nhưng cột lưu ở `Product` để không phải aggregate lại mỗi lần xem báo cáo.

### 3.2 Ngoài phạm vi (cố ý không làm ở đây)

| Nghiệp vụ | Vì sao không làm ở đây |
|---|---|
| Giỏ hàng, thanh toán, tồn kho thật (số lượng, kho hàng) | Đây là catalog trưng bày phục vụ CTA marketing, không phải hệ thống bán hàng/kho vận — nếu cần sau, thêm module `Order`/`Inventory` riêng, `Product` chỉ là bảng tham chiếu |
| Trang chi tiết sản phẩm công khai (PDP) | MVP: sản phẩm chỉ hiển thị **thông qua** Product CTA Box trong bài viết `News`, chưa có route `products/{slug}` công khai riêng — để ở Open Questions §16, thêm dễ dàng vì đã có đủ field (`slug`, `description`) |
| Biến thể sản phẩm (size/màu/SKU con) | Không có yêu cầu; nếu cần, thêm bảng `product_variants` sau, không phá schema `products` hiện tại |
| Full-text search nâng cao (Laravel Scout/Meilisearch) | MVP dùng index B-tree thường (`LIKE` theo tiền tố) đủ cho quy mô chục nghìn dòng/tổ chức; nâng cấp Scout khi có bằng chứng cần relevance-ranking tốt hơn (Open Questions §16) |

---

## 4. Nguyên tắc kiến trúc

| Nguyên tắc | Áp dụng |
|---|---|
| **Tenant-scoped** | `product_categories`, `products` extend `TenantAwareModel` |
| **No JSON storage** | Không cột JSON |
| **Không xoá cứng nếu còn tham chiếu** | `DeleteProductAction` kiểm `$product->newsBlockItems()->doesntExist()` (quan hệ ngược từ `News`, khai báo qua Facade/contract — xem §11.2) trước khi cho xoá; nếu còn tham chiếu → chỉ cho đổi `status = discontinued`, chặn xoá cứng với thông báo rõ ràng |
| **Denormalized counter cho báo cáo tại scale** | `products.total_view_count`/`products.total_cta_click_count` cập nhật tăng dần cùng lúc với counter chi tiết bên `News` (§9), tránh `SUM()`/`COUNT()` qua bảng lớn dần theo thời gian |
| **Soft delete** | `products`, `product_categories` — chỉ áp dụng khi **không còn tham chiếu** (xem trên); nếu còn tham chiếu, dùng `status` thay vì xoá |
| **UUID public** | `products.uuid`, `product_categories.uuid` — dự phòng cho route công khai ở phase sau (§16 Q1) |
| **Tiền tố bảng `products`/`product_categories`** | Không thêm tiền tố module (khác `news_*`/`academy_*`) vì đây là danh từ nghiệp vụ trung tâm, không mơ hồ, và trùng khớp route stub có sẵn `backend.products.*` |
| **Enum** | PHP backed enum (`string`), cột DB `string` thường, có `label()` |

---

## 5. Directory Structure (Feature-first)

```
Modules/Product/
├── app/
│   ├── Features/
│   │   ├── CategoryManagement/
│   │   │   ├── Actions/    (CreateCategoryAction, UpdateCategoryAction, DeleteCategoryAction, ReorderCategoriesAction)
│   │   │   ├── Queries/    (GetCategoryTreeQuery/Handler, ListCategoriesForAdminQuery/Handler)
│   │   │   └── Http/       (CategoryAdminController)
│   │   │
│   │   ├── CatalogManagement/          ← Slice: CRUD sản phẩm/dịch vụ
│   │   │   ├── Actions/    (CreateProductAction, UpdateProductAction, DeleteProductAction,
│   │   │   │                ChangeProductStatusAction)
│   │   │   ├── Data/       (ProductData)
│   │   │   ├── Queries/    (ListProductsForAdminQuery/Handler, GetProductDetailQuery/Handler)
│   │   │   └── Http/       (ProductAdminController)
│   │   │
│   │   ├── CatalogPicker/              ← Slice: API tìm kiếm cho module khác gọi (News, ...)
│   │   │   ├── Queries/    (SearchProductsQuery/Handler, ListFeaturedProductsQuery/Handler)
│   │   │   └── Http/       (ProductPickerApiController)
│   │   │
│   │   └── Analytics/                   ← Slice: rollup counter
│   │       ├── Actions/    (IncrementProductViewCountAction, IncrementProductClickCountAction)
│   │       └── Queries/    (ListTopReferencedProductsQuery/Handler, ListTopClickedProductsQuery/Handler)
│   │
│   ├── Models/          (ProductCategory, Product)
│   ├── Enums/           (ProductType, ProductStatus)
│   ├── Contracts/       (ProductCatalogContract — interface public cho module khác gọi, xem §11.2)
│   ├── Policies/        (ProductCategoryPolicy, ProductPolicy)
│   └── Providers/       (ProductServiceProvider, RouteServiceProvider)
│
├── config/config.php
├── database/
│   ├── migrations/       (2 file, xem §7)
│   └── seeders/          (ProductPermissionSeeder, ProductDemoCatalogSeeder, ProductDatabaseSeeder)
├── resources/views/      (admin/categories, admin/products)
├── routes/{web.php, api.php}
├── module.json, composer.json   ← require "nwidart/laravel-modules", News module composer.json sẽ require Product
```

---

## 6. Data Model

### 6.1 `product_categories` — Danh mục (cây)

```php
Schema::create('product_categories', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();
    $table->foreignId('parent_id')->nullable()->constrained('product_categories')->restrictOnDelete();
    $table->string('name', 150);
    $table->string('slug', 160);
    $table->text('description')->nullable();
    $table->string('icon', 80)->nullable();
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->unique(['organization_id', 'slug'], 'uq_product_cat_org_slug');
    $table->index(['organization_id', 'parent_id', 'sort_order'], 'idx_product_cat_sort');
    $table->index(['organization_id', 'is_active'], 'idx_product_cat_active');
});
```
Mô hình giống hệt `news_categories`/`kc_categories` — cùng 1 pattern cây đã kiểm chứng, tách instance riêng vì vòng đời danh mục sản phẩm độc lập với danh mục tin tức.

### 6.2 `products` — Sản phẩm / Dịch vụ

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();
    $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
    $table->string('name', 250);
    $table->string('slug', 270);
    $table->string('sku', 60)->nullable();
    $table->string('type', 20)->default('physical');       // ProductType: physical|service
    $table->string('short_description', 300)->nullable();  // hiển thị trong picker + product-box compact
    $table->text('description')->nullable();                // mô tả đầy đủ, dự phòng trang chi tiết tương lai
    $table->decimal('price', 12, 2)->nullable();
    $table->string('price_label', 100)->nullable();         // override hiển thị: "Liên hệ báo giá", "Từ 590.000đ"
    $table->char('currency', 3)->default('VND');
    $table->string('cover_image_url', 500)->nullable();
    $table->string('status', 20)->default('active');        // ProductStatus: active|inactive|discontinued|out_of_stock
    $table->string('default_cta_label', 60)->nullable();    // "Mua ngay" mặc định khi block không tự định nghĩa nút
    $table->string('default_cta_url', 500)->nullable();
    $table->string('source_ref_type', 100)->nullable();     // soft-link tuỳ chọn, vd Modules\OcopRubric\Models\OcopProduct
    $table->unsignedBigInteger('source_ref_id')->nullable();
    $table->boolean('is_featured')->default(false);
    $table->unsignedBigInteger('view_count')->default(0);
    $table->unsignedBigInteger('total_cta_click_count')->default(0);  // rollup — xem §9
    $table->unsignedInteger('used_in_articles_count')->default(0);    // rollup — số bài viết (DISTINCT) đang tham chiếu, xem §9.1
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->unique(['organization_id', 'slug'], 'uq_product_org_slug');
    $table->index(['organization_id', 'sku'], 'idx_product_org_sku');
    $table->index(['organization_id', 'category_id', 'status'], 'idx_product_org_cat_status');
    $table->index(['organization_id', 'status', 'is_featured', 'sort_order'], 'idx_product_org_status_featured');
    $table->index(['organization_id', 'name'], 'idx_product_org_name');   // hỗ trợ LIKE 'query%' cho picker
    $table->index(['source_ref_type', 'source_ref_id'], 'idx_product_source_ref');
});
```

**Vì sao `category_id` là `nullOnDelete()` nhưng `product_id` bên `News` lại là `restrictOnDelete()`** (xem `docs/news-module-spec.md` §7.6 bản cập nhật): xoá 1 danh mục sản phẩm không nên kéo theo hệ luỵ gì tới sản phẩm (chỉ cần re-categorize), nhưng xoá 1 **sản phẩm** đang được hàng trăm bài viết tham chiếu là một sự kiện nghiêm trọng hơn nhiều — phải chặn cứng ở tầng DB, không chỉ ở tầng Action (defense in depth).

---

## 7. Enums (`Modules/Product/app/Enums/`)

```php
enum ProductType: string {
    case Physical = 'physical';
    case Service  = 'service';
}

enum ProductStatus: string {
    case Active       = 'active';
    case Inactive     = 'inactive';       // tạm ẩn khỏi picker, chưa xoá
    case Discontinued = 'discontinued';   // ngừng kinh doanh vĩnh viễn, vẫn hiển thị được trong box cũ (đọc-only)
    case OutOfStock   = 'out_of_stock';   // vẫn hiển thị, ẩn nút "Mua ngay" mặc định
}
```
`ProductStatus::Discontinued`/`OutOfStock` không ẩn hoàn toàn sản phẩm khỏi các bài viết cũ đã nhắc tới nó — Blade component product-box (`Modules/News`) đọc `status` để quyết định có hiện nút CTA mặc định hay thay bằng nhãn "Sản phẩm hiện không khả dụng" (xem `docs/news-module-spec.md` §9.3 bản cập nhật).

---

## 8. Permissions (RBAC)

```php
// ══ PRODUCT (Danh mục Sản phẩm & Dịch vụ) ═══════════════════════
// Marketing/Sales=Soạn thảo | CEO/Ops=Full | System_Admin=Full + quản lý danh mục | còn lại=View
case PRODUCT_CATEGORY_MANAGE = 'product_category.manage';
case PRODUCT_VIEW    = 'product.view';
case PRODUCT_CREATE  = 'product.create';
case PRODUCT_EDIT    = 'product.edit';
case PRODUCT_DELETE  = 'product.delete';
```
`config/permissions.php`: `PRODUCT_VIEW` vào cả 8 role; `PRODUCT_CREATE/EDIT` vào `R::MARKETING`, `R::SALES`, `R::CEO`, `R::OPS`, `R::ADMIN`; `PRODUCT_DELETE`/`PRODUCT_CATEGORY_MANAGE` chỉ `R::ADMIN`. Seeder clone `AcademyPermissionSeeder`.

---

## 9. Tích hợp với `News` (Product CTA Box) — bảng thay đổi ở `news_product_block_items`

Thay thế thiết kế soft-link cũ (`product_ref_type`/`product_ref_id` string, §7.6 bản v1 của `docs/news-module-spec.md`) bằng FK cứng:

```php
// migration sửa đổi trong Modules/News (chạy sau khi Modules/Product đã tồn tại)
Schema::table('news_product_block_items', function (Blueprint $table) {
    $table->dropColumn(['product_ref_type', 'product_ref_id']);
    $table->foreignId('product_id')->nullable()->after('block_id')
        ->constrained('products')->restrictOnDelete();

    // Override tuỳ chọn — null = lấy "sống" từ bảng products (xem cơ chế fallback bên dưới)
    $table->string('title_override', 200)->nullable()->change();       // đổi 'title' cũ thành nullable
    $table->string('price_label_override', 100)->nullable()->change(); // đổi 'price_label' cũ
    $table->text('description_override')->nullable()->change();
    $table->string('image_url_override', 500)->nullable()->change();
});
```

**Cơ chế fallback khi render** (`ArticleContentParser::render`, xem `docs/news-module-spec.md` §9.5): với mỗi `NewsProductBlockItem`, giá trị hiển thị = `$item->title_override ?? $item->product?->name`, tương tự cho `price_label`/`description`/`image_url`. Đây chính là điểm giải quyết bài toán scale nêu ở §1: **đa số item chỉ set `product_id`, để trống mọi override** → sửa giá 1 lần ở `products.price`, mọi vị trí đã chèn tự động cập nhật, không cần touch từng dòng `news_product_block_items`.

`news_product_block_buttons.url_type` thêm case `use_product_default` (bên cạnh `custom_url|phone|zalo|email` đã có) — khi chọn, `label`/`url` fallback về `product->default_cta_label`/`product->default_cta_url`, giảm thao tác nhập lại CTA giống nhau ở hàng nghìn vị trí chèn cùng 1 sản phẩm phổ biến.

**Rollup counter tại thời điểm click** (`RecordProductBlockClickAction` trong `News`): trong cùng transaction — `increment('click_count')` trên `NewsProductBlockButton` **và** gọi `Product\Facades\ProductCatalog::incrementClickCount($productId)` (qua Contract §11.2) để cộng dồn `products.total_cta_click_count`. Nhờ vậy báo cáo "Top 10 sản phẩm CTA hiệu quả nhất" chỉ cần `ORDER BY total_cta_click_count DESC LIMIT 10` trên bảng `products` (tối đa vài chục nghìn dòng), không phải `JOIN + SUM` qua bảng `news_product_block_buttons`/click-log có thể lên tới hàng triệu dòng theo thời gian.

### 9.1 `used_in_articles_count` — rollup "bao nhiêu bài viết đang dùng sản phẩm này"

Hiển thị ở danh sách quản trị sản phẩm (§10.2) và là **điều kiện chặn xoá cứng** (§10.2 `DeleteProductAction`) — cả 2 nhu cầu dùng chung 1 cột duy nhất, đếm **DISTINCT article** (không phải tổng số vị trí chèn — 1 bài có thể chèn cùng 1 sản phẩm ở 2 khối khác nhau nhưng vẫn tính là 1 bài), và đếm trên **mọi trạng thái bài viết** (kể cả `draft`) — vì lý do chặn xoá là toàn vẹn dữ liệu (còn dòng FK trỏ tới), không phải "đã publish hay chưa".

**Ai ghi vào cột này?** `Product` sở hữu cột nhưng **không tự tính** (không được phép query bảng của `News`, giữ đúng ranh giới 1 chiều ở §11). `News` tính và ghi qua Contract:

- Trong `SyncProductBlocksAction` (`docs/news-module-spec.md` §9.4), mỗi lần bài viết được lưu: so `productIdsBefore` (distinct `product_id` của bài, đọc từ DB trước khi sync) với `productIdsAfter` (distinct `product_id` parse được từ content mới).
  - `$added = productIdsAfter − productIdsBefore` → gọi `ProductCatalogContract::incrementArticleUsageCount($productId)` cho từng id.
  - `$removed = productIdsBefore − productIdsAfter` → gọi `decrementArticleUsageCount($productId)` cho từng id.
  - Sản phẩm đã có trong cả 2 tập (vẫn được dùng, chỉ đổi override/nút CTA) → không gọi gì, giữ nguyên count.
- Nếu sau này có `ForceDeleteArticleAction` (xoá cứng vĩnh viễn, hiện chưa trong phạm vi §3.2) — bắt buộc decrement cho mọi `product_id` còn lại của bài trước khi cascade xoá, nếu không counter sẽ bị "rò rỉ" (đếm dư).

**Contract methods tương ứng** (thay cho `countReferencesFromExternalTable` ở bản trước — xem §11.2 đã đơn giản hoá): `incrementArticleUsageCount`, `decrementArticleUsageCount`, và `setArticleUsageCount` (ghi đè trực tiếp, dùng bởi lệnh đối soát định kỳ bên dưới).

**Đối soát định kỳ (chống lệch rollup)**: vì đây là counter tăng/giảm dần (không phải luôn tính lại từ đầu), có rủi ro lệch nếu 1 request giữa chừng lỗi (transaction rollback ở `News` nhưng đã gọi Contract trước đó, hoặc ngược lại). `Modules/News` cung cấp artisan command `news:recalculate-product-usage-counts` — chạy định kỳ (vd hàng đêm qua Laravel Scheduler): với mỗi `product_id` xuất hiện trong `news_product_block_items`, tính `COUNT(DISTINCT article_id)` thật, rồi gọi `ProductCatalogContract::setArticleUsageCount($productId, $trueCount)` để ghi đè số đúng. Lệnh này **phải nằm trong `News`** (chỉ `News` có quyền query bảng của chính nó), không nằm trong `Product`.

**Drill-down "xem bài viết nào đang dùng"**: cột số trong danh sách sản phẩm (`ListProductsForAdminQuery`, §10.2) là 1 link trỏ sang route thuộc **`News`** — `GET dashboard/news/articles?product_id={id}` (xem `docs/news-module-spec.md` §11.2/§12, dùng `ListArticlesReferencingProductQuery/Handler` đã có sẵn) — chứ **không** phải `Product` tự dựng màn hình danh sách bài viết (`Product` không có model `NewsArticle` để hiển thị). Điều hướng chéo module qua URL là hợp lệ (không phải code-level coupling), giữ đúng ranh giới 1 chiều.

---

## 10. Feature Slices — Chi tiết

### 10.1 Slice `CategoryManagement` (quyền `product_category.manage`)
Giống hệt `News::CategoryManagement` (`docs/news-module-spec.md` §11.1) — cùng pattern cây, chỉ đổi bảng.

### 10.2 Slice `CatalogManagement` (quyền `product.create/edit/delete`)
- **Actions**:
  - `CreateProductAction`/`UpdateProductAction` — nhận `ProductData` (name/category_id/type/price/price_label/cover_image_url/status/default_cta_label/default_cta_url/source_ref).
  - `DeleteProductAction` — **guard bắt buộc**: kiểm tra `$product->used_in_articles_count > 0` (đọc cột của **chính bảng `products`**, không cần cross-module query nào — counter đã được `News` duy trì sẵn qua Contract, xem §9.1); nếu > 0 → ném `ProductStillReferencedException` kèm link drill-down (§9.1) gợi ý rà soát/thay thế trước, chặn xoá cứng; nếu = 0 mới cho `$product->delete()` (soft delete).
  - `ChangeProductStatusAction` — chuyển `active ⇄ inactive ⇄ discontinued ⇄ out_of_stock`, không cần guard tham chiếu (đây là cách "xoá mềm nghiệp vụ" đúng đắn khi còn tham chiếu — không giới hạn bởi `used_in_articles_count`, luôn cho phép đổi status).
- **Queries**: `ListProductsForAdminQuery/Handler` (filter category/status/type, phân trang, kèm cột `used_in_articles_count` + link drill-down sang `News`), `GetProductDetailQuery/Handler`.
- **Http**: `ProductAdminController` — lấp route stub `backend.products.index`/`backend.products.create` có sẵn.

### 10.3 Slice `CatalogPicker` (quyền `product.view`, gọi bởi `News::ProductBlockPicker`)
- **Queries**:
  - `SearchProductsQuery/Handler(organizationId, ?categoryId, ?keyword, page, perPage)` — `WHERE organization_id = ? AND status IN (active, out_of_stock) AND ($categoryId ? category_id = ? : true) AND ($keyword ? name LIKE ?%  : true) ORDER BY is_featured DESC, sort_order LIMIT/OFFSET` — dùng đúng index `idx_product_org_cat_status`/`idx_product_org_name`, phân trang bắt buộc (không bao giờ trả hết chục nghìn dòng 1 lần).
  - `ListFeaturedProductsQuery/Handler` — top sản phẩm `is_featured=true`, hiện đầu danh sách trong dialog Jodit trước khi user gõ tìm kiếm.
  - `BatchGetProductsQuery/Handler(organizationId, int[] $ids)` — `WHERE organization_id = ? AND id IN (...)`, tra bằng **primary key** (không qua index lọc như `search`) nên tốc độ **không phụ thuộc kích thước catalog** (chục nghìn hay chục dòng đều nhanh như nhau). Dùng khi `News` cần refresh dữ liệu sống (tên/giá/ảnh/status hiện tại) cho 1 danh sách `product_id` đã biết sẵn — điển hình là lúc mở lại 1 khối đã chèn để sửa (`docs/news-module-spec.md` §9.8.5). **Không** dùng N lần gọi `find(id)` riêng lẻ cho từng sản phẩm — luôn gộp thành 1 request duy nhất.
- **Http**: `ProductPickerApiController@search`, `@featured`, `@batch` — JSON, `News` gọi endpoint này thay vì trước đây gọi thẳng `OcopProduct`.

### 10.4 Slice `Analytics`
- **Actions**: `IncrementProductViewCountAction`, `IncrementProductClickCountAction`, `IncrementArticleUsageCountAction`/`DecrementArticleUsageCountAction`/`SetArticleUsageCountAction` — action nhỏ, atomic `increment()`/`decrement()`/gán trực tiếp, được gọi từ Contract (§11.2) bởi module khác (`News`), không tự query bảng của `News`.
- **Queries**: `ListTopClickedProductsQuery/Handler` (`ORDER BY total_cta_click_count DESC`), `ListMostUsedProductsQuery/Handler` (`ORDER BY used_in_articles_count DESC`) — cả 2 đều **không JOIN**, tận dụng rollup đã có sẵn trên `products`.

---

## 11. Ranh giới module & tích hợp cross-module

### 11.1 Composer dependency
`Modules/News/composer.json` khai báo phụ thuộc `Modules/Product` (require trong `module.json` hoặc đơn giản là load-order trong `modules_statuses.json` — `Product` phải bật trước `News`). Chiều phụ thuộc **1 chiều**: `News → Product`. `Product` không bao giờ import bất kỳ class nào từ `News`.

### 11.2 `ProductCatalogContract` — interface công khai cho module khác gọi

Thay vì `News` query thẳng bảng `products`/model `Product` (rò rỉ chi tiết nội bộ của `Product` ra ngoài, khó đổi schema sau này), `Product` expose 1 interface:

```php
namespace Modules\Product\Contracts;

interface ProductCatalogContract
{
    public function find(int $productId): ?ProductSummaryDTO;
    public function search(int $organizationId, ?int $categoryId, ?string $keyword, int $page = 1): PaginatedResult;
    public function incrementViewCount(int $productId): void;
    public function incrementClickCount(int $productId): void;

    // Usage-count rollup (§9.1) — News là bên duy nhất gọi các method này, Product không tự tính
    public function incrementArticleUsageCount(int $productId): void;
    public function decrementArticleUsageCount(int $productId): void;
    public function setArticleUsageCount(int $productId, int $count): void; // dùng bởi lệnh đối soát định kỳ trong News
}
```
Binding trong `ProductServiceProvider`. `News` (và bất kỳ module nào khác cần "hiển thị sản phẩm") chỉ phụ thuộc `ProductCatalogContract`, không phụ thuộc trực tiếp `Modules\Product\Models\Product` — giữ khớp nối lỏng, cho phép `Product` đổi cấu trúc bảng nội bộ mà không phá `News`.

> **Đổi so với bản trước**: đã bỏ `countReferencesFromExternalTable()` (thiết kế cũ yêu cầu `Product` nhận tên bảng/cột từ `News` để tự query — rò rỉ chi tiết nội bộ của `News` sang `Product`, ngược hướng phụ thuộc). Thay bằng cột `used_in_articles_count` do chính `News` duy trì qua 3 method trên — `DeleteProductAction` giờ chỉ đọc cột của chính `products`, **không cần gọi cross-module** để kiểm tra điều kiện xoá.

> Ràng buộc FK thật (`news_product_block_items.product_id → products.id`) vẫn tồn tại ở tầng DB (bắt buộc, để đảm bảo toàn vẹn dữ liệu — Eloquent Contract không thay được FK constraint). Contract chỉ áp dụng cho **code path** (Query/Action), không thay cho migration.

---

## 12. Routes

```php
// routes/web.php — thay thế 2 dòng stub trong routes/web.php gốc (xoá dòng 57-58 ở đó, chuyển vào đây)
Route::middleware(['auth', 'tenant'])->prefix('dashboard/products')->name('backend.products.')->group(function () {
    Route::resource('categories', CategoryAdminController::class)->except(['show']);
    Route::resource('/', ProductAdminController::class)->parameters(['' => 'product']); // xem ghi chú route-model-binding rỗng
    Route::post('{product:uuid}/status', [ProductAdminController::class, 'changeStatus'])->name('change-status');
});

// routes/api.php — nội bộ, gọi bởi News::ProductBlockPicker và bất kỳ module nào khác
Route::middleware(['auth:sanctum'])->prefix('api/v1/products')->group(function () {
    Route::get('search', [ProductPickerApiController::class, 'search']);
    Route::get('featured', [ProductPickerApiController::class, 'featured']);
    Route::get('batch', [ProductPickerApiController::class, 'batch']); // ?ids=1,2,3 — xem §10.3
});
```
> Ghi chú triển khai: `Route::resource('/', ...)` với prefix rỗng cần viết tường minh bằng `Route::get/post/put/delete` từng route thay vì `resource()` (Laravel không hỗ trợ resource path rỗng gọn gàng) — chi tiết cụ thể hoá ở Phase 0 khi viết `routes/web.php` thật.

---

## 13. Seed Data

`ProductDemoCatalogSeeder` — chỉ chạy nếu `products` rỗng trong org đang seed: 2-3 `ProductCategory` + 6-8 `Product` mẫu (đủ physical/service, có/không `price`, 1-2 sản phẩm `is_featured`) — đủ dữ liệu để test picker trong Jodit ngay.

---

## 14. Key Design Decisions

| Quyết định | Lý do | Đánh đổi |
|---|---|---|
| Module `Product` tách hoàn toàn khỏi `News`/`OcopRubric` | Domain "catalog sản phẩm" khác domain "biên tập nội dung" và khác domain "chấm điểm tuân thủ OCOP"; tái dùng được cho tính năng khác sau này | Thêm 1 module + 1 lượt cross-module contract, phức tạp hơn so với nhét thẳng vào `News` — chấp nhận được vì đổi lại khớp nối lỏng và tái dùng được |
| `product_id` trên `news_product_block_items` là FK cứng `restrictOnDelete`, không phải soft-link string | Đảm bảo toàn vẹn tham chiếu ở tầng DB (không thể xoá nhầm sản phẩm đang được hàng nghìn bài dùng), cho phép JOIN hiệu quả khi render/báo cáo ở scale lớn | Không xoá cứng được sản phẩm còn tham chiếu — bắt buộc dùng `status=discontinued`, đúng ý định thiết kế |
| Override columns (`title_override`...) nullable, fallback về `products` khi null | Sửa giá 1 lần phản ánh khắp nơi — giải quyết trực tiếp yêu cầu scale hàng chục nghìn sản phẩm × hàng nghìn bài | Cần join thêm 1 bảng lúc render (chi phí thấp, có index) |
| Rollup counter (`total_cta_click_count`) thay vì aggregate on-the-fly | Dashboard "top sản phẩm" không bị chậm dần khi lịch sử click tích luỹ theo năm | Counter có thể lệch nhẹ nếu increment thất bại giữa chừng (chấp nhận được cho mục đích thống kê tương đối, không phải sổ sách kế toán) |
| Cross-module qua `ProductCatalogContract`, không cho `News` query thẳng model `Product` | Giữ khớp nối lỏng, `Product` tự do đổi schema nội bộ | Thêm 1 lớp interface — chi phí nhỏ, đổi lại kiến trúc bền hơn khi cả 2 module cùng lớn dần |
| `used_in_articles_count` là rollup do `News` ghi qua Contract (increment/decrement/set), `Product` không tự tính | `DeleteProductAction` chỉ cần đọc cột của chính `products`, không cần cross-module query nào để kiểm tra điều kiện xoá; thay thế cách cũ (`countReferencesFromExternalTable`) vốn buộc `News` phải khai báo tên bảng/cột cho `Product` — rò rỉ chi tiết nội bộ ngược hướng phụ thuộc | Rollup có thể lệch nếu request giữa chừng lỗi — cần lệnh đối soát định kỳ `news:recalculate-product-usage-counts` (§9.1) chạy trong `News` |
| Drill-down "xem bài viết nào đang dùng sản phẩm" điều hướng sang route của `News`, không tự dựng ở `Product` | `Product` không có (và không nên có) model `NewsArticle` để hiển thị danh sách — điều hướng qua URL giữ đúng ranh giới 1 chiều, khác với code-level coupling | Người dùng phải chuyển màn hình (rời trang quản trị sản phẩm sang trang quản trị tin tức) thay vì xem inline — chấp nhận được, đây là 2 domain khác nhau |
| Không có trang chi tiết sản phẩm công khai (PDP) ở MVP | Chưa có yêu cầu cụ thể, tránh xây nửa vời | Product chỉ "sống" thông qua News CTA Box; thêm PDP sau không cần đổi schema (đã có `slug`/`description`) |

---

## 15. Open Questions

1. Có cần trang catalog công khai (`/san-pham/{slug}`) để Product Box trỏ về, thay vì luôn trỏ ra link ngoài? Ảnh hưởng route + `default_cta_url` có thể tự suy ra nội bộ (`product_link` type quay trở lại được, như đã dự trù ở `docs/news-module-spec.md` §15).
2. Catalog có cần đồng bộ 2 chiều với `OcopProduct` (vd khi 1 sản phẩm OCOP được "Duyệt" tự động tạo `Product` tương ứng) hay `source_ref_type/id` chỉ là liên kết thủ công 1 chiều lúc tạo?
3. Khi tổ chức có > 50.000 sản phẩm, index B-tree thường có đủ nhanh cho picker, hay cần Laravel Scout (driver `database` trước, `meilisearch` nếu cần) ngay từ Phase 1?
4. `total_cta_click_count`/`view_count` có cần tách theo khoảng thời gian (theo tháng) để vẽ biểu đồ xu hướng, hay chỉ cần con số luỹ kế tổng như hiện tại?

---

## 16. Phased Implementation Plan

| Phase | Nội dung | Output kiểm tra được |
|---|---|---|
| **Phase 0 — Scaffold module** | `module.json`, `ProductServiceProvider`, `config/config.php`, bật trong `modules_statuses.json` **trước** `News` | `php artisan module:list` thấy `Product` enabled, thứ tự load trước `News` |
| **Phase 1 — Data model** | 2 migration (§6), 2 model, 2 enum (§7) | `php artisan migrate` sạch; tạo `ProductCategory`→`Product` qua tinker |
| **Phase 2 — Permissions** | `PermissionEnum` +5 case, `config/permissions.php`, `ProductPermissionSeeder` | `php artisan db:seed` có `product.*`/`product_category.*` |
| **Phase 3 — Slice `CategoryManagement` + `CatalogManagement`** | Actions/Queries/Http/Policy + Blade admin (kèm cột `used_in_articles_count` trong danh sách — hiện `0`/rỗng cho tới khi Phase 5 của `News` chạy), lấp route stub `backend.products.*` | Tạo được danh mục + sản phẩm đầy đủ qua UI |
| **Phase 4 — `ProductCatalogContract` + Slice `CatalogPicker`** | Interface + binding (đủ 7 method §11.2), `SearchProductsQuery`, `ProductPickerApiController` | `News` (sau khi có Phase tương ứng) gọi API tìm sản phẩm, trả kết quả phân trang đúng |
| **Phase 5 — Migration sửa `news_product_block_items`** (thực hiện trong `Modules/News`, phối hợp) | Đổi `product_ref_type/id` → `product_id` FK, thêm `*_override` columns | Chèn product-box mới trỏ `product_id` thật; sửa giá ở `Product` → box cũ tự cập nhật khi F5 |
| **Phase 6 — Slice `Analytics` + rollup counter (click + usage)** | `IncrementProductClickCountAction`, `IncrementArticleUsageCountAction`/`Decrement`/`Set`, `ListTopClickedProductsQuery`, `ListMostUsedProductsQuery` | Bấm CTA trong 1 bài → `total_cta_click_count` tăng đúng 1; chèn/gỡ product-box trong `News` → `used_in_articles_count` tăng/giảm đúng theo §9.1; xoá sản phẩm đang được ≥1 bài dùng → bị chặn, thông báo kèm link drill-down sang `News`; đổi status → `discontinued` luôn thành công dù còn tham chiếu |
| **Phase 7 — Đối soát định kỳ** | Command `news:recalculate-product-usage-counts` (trong `Modules/News`) + đăng ký Laravel Scheduler chạy hàng đêm | Cố tình làm lệch `used_in_articles_count` (update tay trong DB) → chạy lệnh → số được sửa về đúng `COUNT(DISTINCT article_id)` thật |
| **Phase 7 — Seed & kiểm thử tải (scale test)** | `ProductDemoCatalogSeeder` sinh thử 20.000 sản phẩm (factory) trong 1 org test | `SearchProductsQuery` trả kết quả < 200ms trên tập 20k dòng (đo bằng `DB::listen` hoặc Telescope nếu có) |

---

## 17. Testing & Acceptance Criteria

- Given `Product` có `used_in_articles_count = 3` → When `DeleteProductAction` → Then chặn, ném `ProductStillReferencedException` kèm link drill-down sang `dashboard/news/articles?product_id=`, không query cross-module nào phát sinh (kiểm tra qua query log — chỉ 1 câu `SELECT` trên `products`).
- Given `Product` có `used_in_articles_count = 0` → When `DeleteProductAction` → Then xoá mềm thành công.
- Given `ChangeProductStatusAction` chuyển `active → discontinued` dù `used_in_articles_count > 0` → Then luôn thành công (không bị guard nào chặn); các Product CTA Box cũ vẫn hiển thị tên/ảnh/giá đã lưu, nhưng nút CTA mặc định (`use_product_default`) ẩn/thay bằng nhãn "Ngừng kinh doanh" — nút tuỳ biến (`custom_url`/`phone`/`zalo`/`email`) vẫn hiển thị bình thường (kiểm tra ở `News`, không phải ở `Product`).
- Given `SearchProductsQuery` với `keyword` + `categoryId` trên tập ≥ 10.000 sản phẩm → Then trả đúng trang đầu tiên, tổng số trang chính xác, thời gian truy vấn nằm trong ngưỡng chấp nhận (Phase 7).
- Given 1 `NewsProductBlockItem` không set bất kỳ override nào, chỉ set `product_id` → When `Product.price_label` được sửa qua trang quản trị → Then trang bài viết công khai hiển thị `price_label` mới ngay, không cần sửa lại bài viết.
- Given `RecordProductBlockClickAction` được gọi 100 lần cho cùng 1 `product_id` (qua các bài viết khác nhau) → Then `products.total_cta_click_count = 100`, khớp tổng `SUM(news_product_block_buttons.click_count)` của các nút trỏ tới sản phẩm đó (kiểm tra định kỳ để phát hiện lệch rollup nếu có).
- Given 1 bài viết mới chèn 2 vị trí cùng trỏ tới `product_id = 42` (2 khối khác nhau) → When lưu bài → Then `products.used_in_articles_count` của sản phẩm 42 chỉ tăng **đúng 1** (đếm theo bài, không theo số vị trí chèn).
- Given bài viết đó sau đó gỡ bớt 1 trong 2 vị trí (vẫn còn 1 vị trí trỏ `product_id = 42`) → When lưu lại → Then `used_in_articles_count` **không đổi** (bài vẫn đang dùng sản phẩm, chỉ giảm số vị trí).
- Given bài viết gỡ nốt vị trí còn lại (không còn chèn `product_id = 42` ở đâu trong bài) → When lưu lại → Then `used_in_articles_count` giảm đúng 1.
- Given cố tình chỉnh tay `products.used_in_articles_count` sai lệch so với thực tế → When chạy `php artisan news:recalculate-product-usage-counts` → Then cột được ghi đè lại đúng bằng `COUNT(DISTINCT article_id)` thật từ `news_product_block_items`.
