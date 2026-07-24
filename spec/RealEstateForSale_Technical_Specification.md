# Real Estate — Bất động sản BÁN (`Modules/RealEstate`)

**Đặc tả Kỹ thuật Chi tiết — Sẵn sàng Triển khai**

**Phiên bản:** 1.4
**Ngày:** 24/07/2026
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions + Spatie Media Library + Laravel Scout/Meilisearch
**Vị trí:** Module NWIDART **mới** — `Modules/RealEstate` (tài liệu này là spec **chính/đầy đủ**; spec song sinh `spec/RealEstateForRent_Technical_Specification.md` mô tả biến thể "cho thuê", dùng CHUNG toàn bộ hạ tầng ở đây — bảng, model, RBAC, workflow duyệt, gallery ảnh, chỉ khác `listing_type` + tập field)
**Nguồn gốc yêu cầu:** `spec/PropertyForSaleMetabox.md` (custom post type + custom field WordPress/Meta Box `property-for-sale`, đã port sang kiến trúc Laravel của hệ thống này)
**Module liên quan:** `Modules/Approval` (workflow duyệt nội dung dùng chung), `Modules/Organization` (chủ sở hữu tin đăng), `app/Enums/PermissionEnum.php` + `config/permissions.php` (RBAC Lớp B), `resources/views/components/address-picker.blade.php` (tỉnh/phường)

> **Lịch sử phiên bản**
> - **v1.0** — port `PropertyForSaleMetabox.md` (WordPress) sang module Laravel `Modules/RealEstate`: 1 bảng `real_estate_listings` dùng chung cho cả bán/thuê (cột `listing_type` phân biệt, tiền lệ `ArticleFormat`), tái dùng `Modules\Approval\Concerns\HasApproval` cho workflow duyệt (KHÔNG viết lại state machine riêng), gallery ảnh đa file qua Spatie Media Library (`order_column` có sẵn, không bảng phụ). KHÔNG tự đếm/log lượt xem — dùng Google Analytics (đúng quyết định đã áp dụng cho Author Hub).
> - **v1.1** — vá 3 lỗ hổng phát hiện qua review (chi tiết đầy đủ ở `spec/RealEstateForRent_Technical_Specification.md` §0.1, áp dụng chung cho cả 2 loại vì cùng 1 bảng): (a) `province_code`/`ward_code` đổi từ nullable → **bắt buộc** (khu vực là tiêu chí lọc cốt lõi, không thể để trống — §3.2); (b) thêm cột `is_price_negotiable` (boolean) — "giá thoả thuận" áp dụng cho CẢ bán và thuê, không riêng thuê; (c) ghi rõ **1 nguồn ghi ảnh duy nhất** (Media Library) — tránh đúng lỗi "hook lưu ảnh thủ công thừa" của bản WordPress gốc (`rwmb_after_save_post` tự `update_post_meta` trong khi Meta Box đã tự lưu `image_advanced`, §4.4).
> - **v1.2** — **bỏ hẳn cột JSON `attributes`** (yêu cầu trực tiếp: không lưu trữ dữ liệu dạng JSON) — toàn bộ field trước đây gộp vào `attributes` (pháp lý, hướng, subtype, diện tích chi tiết, dự án, tình trạng sử dụng...) chuyển thành **cột SQL thật riêng biệt**, nullable theo `property_type`/`listing_type` (§0, §3.2, §4.1, §5.2). Đây thực ra là cách làm ĐÚNG CHUẨN của mọi model khác trong codebase này (`Product` cũng có hàng chục cột nullable cho field tuỳ theo `type`, không dùng JSON cho field nghiệp vụ) — bản v1.1 dùng JSON là lựa chọn sai, đã sửa.
> - **v1.3** — review lần 2 (§0.1) — rà soát lại TOÀN BỘ khuyến nghị ưu tiên (harden JS, required giá+diện tích, bỏ hook lưu ảnh thừa, thống nhất naming, tách địa chỉ) và xác nhận **cả 5 điều đã được xử lý từ v1.0-v1.2** (bảng ánh xạ chi tiết ở §0.1 — không phải phát hiện mới). Bổ sung 3 field nâng cao mức "Thấp" (năm xây dựng, thang máy, hỗ trợ vay ngân hàng) vào §9 "Ngoài phạm vi" theo đúng mức ưu tiên user đã đánh giá (có thể bổ sung sau, không chặn v1).
> - **v1.4** — review lần 3 (tổng hợp chung Sale+Rent) — xác nhận lại toàn bộ 6 điểm đã được xử lý (không phát hiện mới); làm rõ thêm 1 điểm quan trọng: `province_code`/`ward_code` (§2.5) áp dụng đúng cấu trúc hành chính 2 CẤP hiện hành (Tỉnh/Thành → Phường/Xã/Đặc khu, đã bỏ cấp huyện sau sáp nhập 2025) — 2 field này + `address_detail` là ĐỦ 3 field cấu trúc hoá, không thiếu field "quận/huyện" nào cả.

---

## 0. Quyết định đã chốt

| Chủ đề | Hiện trạng codebase | Quyết định spec này | Lý do |
|---|---|---|---|
| **Module mới hay Feature trong module có sẵn?** | Không có bất kỳ dấu vết "property"/"bất động sản"/"real estate" nào trong codebase (đã grep toàn bộ `app/`, `Modules/`, `database/`, `routes/`). Module gần nhất về bản chất "listing của doanh nghiệp" là `Modules/Product` (`organization_id` bắt buộc, `Modules/Product/database/migrations/2026_07_06_000002_create_products_table.php:6`) | Tạo **module NWIDART mới `Modules/RealEstate`**, KHÔNG nhồi vào `Modules/Product` | Field set bất động sản (30+ thuộc tính: pháp lý, hướng, diện tích đất/thông thuỷ/tim tường, phòng ngủ/tắm...) khác hẳn bản chất `Product` (sản phẩm/dịch vụ chung, giá + mô tả ngắn) — nhồi vào `products` sẽ phình bảng đó cho MỌI domain khác đang dùng Product (không liên quan BĐS). Tách module riêng giữ `Product` gọn, đúng nguyên tắc single-responsibility đã áp dụng nhất quán (`post_author_profiles` tách khỏi `users` cùng lý do) |
| **1 bảng hay 2 bảng cho Bán/Thuê?** | Tiền lệ `Modules/Post/app/Enums/ArticleFormat.php:5-14` — enum `Article/Video/Activity/Tip/StepByStep/Redirect` dùng CHUNG 1 bảng `post_articles`, cột `format` rẽ nhánh ý nghĩa field khác (`redirect_url` chỉ có nghĩa khi `format=Redirect`) | **1 bảng `real_estate_listings`**, cột `listing_type` (`sale`/`rent`) phân biệt — KHÔNG tạo 2 bảng riêng như 2 file nguồn WordPress (`property-for-sale`/`property-for-rent` là 2 custom post type tách biệt) | Đúng tiền lệ `ArticleFormat` — 2 loại chia sẻ ~70% field (vị trí, diện tích, phòng ngủ/tắm, tầng, nội thất, ảnh); tách 2 bảng sẽ trùng lặp schema + trùng lặp toàn bộ code CRUD/duyệt/tìm kiếm. Sau khi map hết field Rent (spec song sinh §2), Rent hoá ra hoàn toàn phẳng vào cột chung — không cần bảng riêng |
| **Field đặc thù/ít lọc để ở đâu?** *(sửa ở v1.2 — KHÔNG dùng JSON)* | `Modules/Product/app/Models/Product.php:14-38` — `Product` có hàng chục cột nullable riêng theo `type` (`sku`, `shopee_url`, `tiktok_url`, `supplier_url`, `supplier_homepage_url`...) ngay trên bảng chính, KHÔNG dùng JSON cho field nghiệp vụ nào | Toàn bộ field đặc thù/ít lọc (`legal_status`, `direction`, `balcony_direction`, `house_subtype`, `apartment_subtype`, `project_name`, `apartment_address`, `usage_status`, `front_road_width`, `current_rental_income`, `width`, `length`, `land_area`, `usable_area`, `net_area`) là **cột SQL thật, nullable** — KHÔNG có cột JSON nào trên bảng này | Yêu cầu trực tiếp: không lưu trữ dữ liệu dạng JSON. Đúng chuẩn Laravel/Eloquent của chính codebase này (`Product` là ví dụ, không riêng BĐS) — cột thật cho phép filter/sort/index SQL trực tiếp mọi field mà không cần đẩy qua Meilisearch mới lọc được, và tránh việc phải nhớ "field nào nằm trong JSON, field nào là cột" khi đọc code |
| **Diện tích — 1 field WP có tới 4 tên khác nhau theo loại** (`land_area`/`usable_area`/`net_area` bên bán, `rent_usable_area` bên thuê) | — | Cột **`area` (decimal, m²)** — diện tích chính dùng để hiển thị card danh sách + lọc/sort chung mọi loại; giá trị điền tuỳ `property_type` (nhà riêng/đất → copy từ `land_area`, căn hộ → ưu tiên `net_area` nếu có, fallback `usable_area`). Breakdown chi tiết (`width`, `length`, `land_area`, `usable_area`, `net_area`) vẫn là **cột SQL thật riêng**, không mất thông tin gốc | 1 cột lọc/sort duy nhất cho UX tìm kiếm ("Diện tích 50-80m²"), tránh người dùng phải hiểu sự khác biệt land_area/usable_area/net_area khi lọc; các cột chi tiết vẫn tồn tại thật để trang chi tiết hiển thị đúng số liệu chuyên môn |
| **Giấy tờ pháp lý — 3 field riêng theo loại nhà** (`legal_house`/`legal_apartment`/`legal_land`, mỗi field 1 tập option khác) | — | Gộp thành **1 cột SQL thật `legal_status`** — bản chất đều là "loại giấy tờ pháp lý", chỉ options hợp lệ đổi theo `property_type` (validate ở Request, không tách field DB) | 3 field DB riêng cho cùng 1 khái niệm là dư thừa (WordPress tách vì mỗi field cần `options` array riêng cho Meta Box UI, Laravel validate theo `property_type` linh động hơn, không cần tách cột) |
| **Đơn vị tiền: "giá bán (tỷ VND)" của WP** | Field gốc `price` ghi chú đơn vị "tỷ VND" — nhập số thập phân nhỏ (vd 3.5) | Lưu cột `price` theo **VNĐ đầy đủ** (giống `Product.price`, `monthly_rent`), quy đổi hiển thị "x,x tỷ" ở tầng view | Nhất quán đơn vị tiền toàn hệ thống (Product/OcopProduct đều lưu VNĐ đầy đủ) — tránh 2 chuẩn đơn vị tiền khác nhau giữa các module, dễ gây lỗi khi tổng hợp báo cáo/tìm kiếm liên module sau này |
| **"Giá cho thuê mỗi tháng" xuất hiện CẢ trong metabox Bán** (field phụ nếu nhà đang cho thuê trong khi rao bán) VÀ là field CHÍNH của metabox Thuê | — | Đổi tên field phụ bên Bán thành cột SQL thật **`current_rental_income`** — KHÁC với cột `monthly_rent` (giá thuê CHÍNH, chỉ có ý nghĩa khi `listing_type=rent`) | Tránh 2 khái niệm khác nhau (giá thuê chính thức vs thông tin phụ "đang cho thuê bao nhiêu") trùng tên `monthly_rent`, gây nhầm lẫn khi đọc code/dữ liệu |
| **v1.1 — Khu vực (`province_code`/`ward_code`) để nullable có ổn không?** | Review phát hiện (`spec/RealEstateForRent_Technical_Specification.md` §0.1): 2 cột này đang `nullable()` ở migration §3.2 — 1 tin có thể lưu mà KHÔNG có khu vực, phá vỡ chức năng lọc/tìm theo khu vực (`idx_real_estate_location`, §2.6) | Đổi `province_code`/`ward_code` thành **`NOT NULL`, bắt buộc chọn** (migration §3.2 đã cập nhật) — cùng mức bắt buộc với `x-address-picker` khi dùng cho Organization (khác Post/OcopProduct để tuỳ chọn vì đó là nội dung biên tập, không phải listing cần định vị) | Khu vực là tiêu chí lọc CỐT LÕI nhất của mọi trang rao vặt BĐS (người tìm nhà luôn bắt đầu bằng "chọn tỉnh/phường") — để trống được thì lọc theo khu vực vô nghĩa với những tin đó, tốt hơn chặn ngay từ lúc tạo tin |
| **v1.1 — "Giá thoả thuận" (không có số cố định)** | Review phát hiện: metabox Thuê không có cách nào đăng tin "giá thoả thuận" — `monthly_rent` chỉ có 2 trạng thái (có số hoặc trống mơ hồ). `getDisplayPriceAttribute()` (§4.1) đã tự fallback "Thoả thuận" khi `monthly_rent=null`, NHƯNG nếu validation bắt buộc phải có giá (dòng dưới) thì sẽ không còn cách nào để `null` hợp lệ nữa — 2 nhu cầu (bắt buộc điền giá + cho phép thoả thuận) mâu thuẫn nếu chỉ dựa vào `null` | Thêm cột **`is_price_negotiable` (boolean, default `false`)** — áp dụng CẢ bán và thuê (không riêng thuê, người bán cũng hay để "giá thoả thuận"). Validate: `price`/`monthly_rent` bắt buộc CÓ giá trị TRỪ KHI `is_price_negotiable=true` (§5.4) | Tách rõ "chưa điền giá" (lỗi validate, tin thiếu thông tin) khỏi "chủ đích để giá thoả thuận" (hợp lệ, business quyết định không niêm yết số cố định) — 1 cột boolean rõ nghĩa hơn suy diễn từ `null`, và không mâu thuẫn với yêu cầu validate giá bắt buộc |
| **Ai đăng tin?** | 2 file WP không có khái niệm "ai đăng" (WordPress single-site, ai có quyền `edit_post` đều đăng được). Hệ thống này multi-tenant — tiền lệ gần nhất là `Modules/Product` (`organization_id` bắt buộc, `Product::create()` gán `created_by`) | **Organization member** đăng — `real_estate_listings.organization_id` bắt buộc (extends `TenantAwareModel`, giống `Product`) | Tin BĐS bán/thuê về bản chất là 1 dạng "listing của doanh nghiệp" (môi giới/công ty BĐS) — đúng vai trò Organization đã có trong hệ thống (CEO/Sales/Marketing/Ops tự quản lý catalog Product), không phải nội dung do tòa soạn (Platform) biên soạn như Post |
| **Ai duyệt tin trước khi hiển thị công khai?** | `Modules\Approval\Concerns\HasApproval` (`Modules/Approval/app/Concerns/HasApproval.php`) — trait DÙNG CHUNG xuyên-domain (Product + Organization đã dùng, đăng ký qua `config('approval.subjects')`), state machine Draft→Pending→Approved→Published→Archived, tự động bắt duyệt lại khi sửa nội dung đã publish (`approvalWatchedAttributes()`). `ProductPolicy::approve/reject/publishApproval/archiveApproval` (`Modules/Product/app/Policies/ProductPolicy.php:61-79`) đều check `$user->isPlatformContentModerator()` — role Lớp A dùng chung cho MỌI domain nội dung Organization, không riêng Product | **Dùng `HasApproval`** (KHÔNG viết lại workflow riêng) — đăng ký `real_estate_listing` vào `config/approval.php['subjects']`. Organization tự `submitForApproval` (gửi duyệt); `platform_content_moderator` (role có sẵn) `approve/reject/publishApproval/archiveApproval` — Organization KHÔNG tự publish | Đây là hạ tầng xuyên-domain (KHÔNG phải "dùng lại Product") — Approval module thiết kế đúng để nhiều domain khác nhau cùng dùng, tránh viết lại state machine + tự động HIỆN trên dashboard "Chờ duyệt" chung (`ApprovalDashboardController`) sẵn có, không cần xây dashboard duyệt riêng cho BĐS |
| **RBAC — ai trong Organization tạo/sửa/xoá được?** | `config/permissions.php:38-43` — Product cấp `PRODUCT_VIEW/CREATE/EDIT` cho CEO (dòng 41-43), SALES (68-70), OPS (99-101), MARKETING (122-124); chỉ role Admin (System_Admin, Lớp B) có thêm `PRODUCT_DELETE`. Comment đầu file: *"Thêm permission mới: chỉ sửa file này + chạy `php artisan permissions:sync`"* | Permission Lớp B mới `real_estate.view/create/edit/delete` — gán **CEO/Sales/Marketing/Ops: view+create+edit**; **System_Admin: thêm delete** — copy chính xác mẫu cấp quyền của `PRODUCT_*` | Tin BĐS là 1 dạng catalog khác của Organization, đúng nhóm role đã quản lý Product (người kinh doanh, không phải kỹ thuật/nhân sự) — tái dùng mẫu phân quyền đã kiểm chứng, không phát minh mô hình mới |
| **Ảnh — nhiều ảnh, kéo-thả sắp thứ tự (`image_advanced` max 6, WP)** | `config/media.php` mới chỉ có collection ĐƠN ảnh (`avatar`/`logo`/`cover`/`banner`...). Bảng `media` (`database/migrations/vendor/2026_05_13_020107_create_media_table.php:31`) đã có cột `order_column` (chuẩn Spatie) — `InteractsWithMedia::getMedia()` tự `sortBy('order_column')` (`vendor/spatie/laravel-medialibrary/.../InteractsWithMedia.php` dòng ~653), tự tăng `order_column` mỗi lần `addMedia()` (dòng ~509) | Collection Media Library mới **`real_estate_gallery`** (multi-file, KHÔNG nằm trong `SINGLE_FILE_COLLECTIONS`), max 6 ảnh validate ở Action (không phải DB constraint) + 1 endpoint reorder mới cập nhật `order_column` theo thứ tự kéo-thả | Spatie Media Library đã hỗ trợ sẵn multi-ảnh có thứ tự — không cần bảng pivot riêng, không phát minh cơ chế lưu ảnh mới, đúng tinh thần "tái dùng Media Library" đã áp dụng cho `avatar`/`cover` |
| **Thống kê/đếm lượt xem tin đăng?** | User đã yêu cầu RÕ ở Author Hub: *"cái vấn đề thống kê bài viết thì mình sẽ dùng google analytics, bạn không nên tích hợp tính năng đếm bài viết vào làm gì, để tránh phình bảng CSDL"* | **KHÔNG** có cột `view_count`, KHÔNG bảng log lượt xem nào cho `real_estate_listings` — dùng Google Analytics (lọc theo URL path `/nha-dat-ban/*`) | Áp dụng nhất quán quyết định đã chốt cho Author Hub — tránh lặp lại đúng vấn đề đã được yêu cầu tránh (phình CSDL bằng cơ chế tự đếm) |
| **URL công khai** | Route Post: `danh-muc/{slug}`, `{slug}-d{id}.html` (`Modules/Post/routes/web.php:134,147`). Route Product: chỉ có backend, KHÔNG có trang public (`Modules/Product/routes/web.php` toàn bộ `middleware(['auth','tenant'])`) | `/nha-dat-ban` (danh sách) + `/nha-dat-ban/{slug}-r{id}.html` (chi tiết) — `-r{id}.html` cùng cơ chế phân biệt path bằng regex như `-d{id}.html` của Post, KHÔNG dùng để tra cứu | BĐS bán/thuê CẦN trang public (khác Product) — người mua/thuê phải xem được từ ngoài. Giữ nguyên convention URL đã có (hậu tố `-x{id}.html` để tránh đụng route khác), đổi ký tự phân biệt `-r` (realestate) thay `-d` (đã dùng cho Post) |
| **Multi-tenancy** | `Modules/Product/app/Models/Product.php:9` `extends TenantAwareModel` — `organization_id` bắt buộc, route `middleware(['auth','tenant'])` (`Modules/Product/routes/web.php:10`) | `RealEstateListing extends TenantAwareModel`, route admin `middleware(['auth','tenant'])` — copy đúng mẫu Product | Cùng bản chất "tài sản của 1 Organization cụ thể" như Product, cần TenantContext resolve đúng tổ chức khi CRUD |
| **Tìm kiếm/lọc công khai** | `config/scout.php:143-154` — `PostArticleTranslation` (`filterableAttributes: locale/status/category_slugs/province_code/format/is_featured`), `OcopProduct` (`filterableAttributes: status/province_code/category_id/is_featured`) — chưa có mẫu filter khoảng giá/số phòng | Scout/Meilisearch, `filterableAttributes: listing_type, property_type, province_code, ward_code, price, bedrooms, bathrooms, area, is_featured`; `sortableAttributes: price, area, published_at` — thêm range filter theo giá/diện tích (Meilisearch hỗ trợ range trên field numeric) | Tái dùng đúng hạ tầng Scout đã tích hợp, mở rộng thêm range filter (chưa có tiền lệ) vì đây là nhu cầu lọc cốt lõi của BĐS (khoảng giá, khoảng diện tích) |

### 0.1 Đánh giá tồn đọng & cải thiện — review lần 2 (quy về chuẩn Laravel, KHÔNG dùng khái niệm WordPress)

Review lần 2 nhắm vào chính bản gốc `PropertyForSaleMetabox.md` (jQuery, native `visible`, 3 field pháp lý trùng label...). Đối chiếu với thiết kế Laravel đã có (v1.0-v1.2), **cả 5 khuyến nghị ưu tiên đều đã được xử lý từ trước** — không phải phát hiện mới, chỉ có 2 điểm thực sự mới (naming — hoá ra đã đúng sẵn; field nâng cao — bổ sung ở §9).

| Vấn đề (review gốc) | Mức độ | Đánh giá / Xử lý trong bản Laravel này |
|---|---|---|
| **`toggleAll()` selector quá dài, dễ bỏ sót** — hàm JS gốc (dòng 335-394 `PropertyForSaleMetabox.md`) liệt kê ~25 selector CSS thủ công trong 1 hàm, thêm field mới dễ quên thêm vào danh sách ẩn/hiện | Trung bình – Cao | **Không tồn tại trong thiết kế Laravel** — §5.3 dùng Alpine `x-show="property_type === 'house'"` khai báo NGAY TẠI field đó trong Blade, không có 1 hàm trung tâm liệt kê selector của mọi field. Thêm field mới = thêm 1 dòng HTML tự mang điều kiện hiển thị của chính nó, không đụng tới field khác — không có danh sách nào để "quên" |
| **Phụ thuộc jQuery ẩn/hiện `.rwmb-field`** — nên chuyển Alpine, bỏ hẳn jQuery | Trung bình – Cao | **Đã đúng từ §5.3 (v1.0)** — toàn bộ admin UI của hệ thống này (kể cả các trang khác như `admin/breaking-news/index.blade.php`) đã dùng Alpine.js làm chuẩn, KHÔNG dùng jQuery cho việc ẩn/hiện field. Form RealEstate không có dòng jQuery nào |
| **Field vừa dùng native `visible` vừa bị JS điều khiển → xung đột** | Trung bình | **Không áp dụng** — Laravel không có khái niệm "native visible của Meta Box" song song 1 cơ chế JS khác; chỉ có ĐÚNG 1 cơ chế hiển thị (Alpine `x-show`) + ĐÚNG 1 cơ chế validate (Request rule, §5.4) — không có 2 nguồn điều khiển nào để xung đột |
| **3 field "Giấy tờ pháp lý" cùng label, UX rối** (`legal_house`/`legal_apartment`/`legal_land`) | Thấp – Trung bình | **Đã gộp thành 1 cột `legal_status`** từ §0 (v1.0, siết thành cột SQL thật ở v1.2) — chỉ 1 field trên form, options đổi theo `property_type` đã chọn, không còn 3 field cùng label gây rối |
| **Validation yếu — chỉ `property_type` required, nên bắt buộc giá bán + diện tích chính** | Trung bình | **Đã vá ở §5.4** — `area` bắt buộc, `price` bắt buộc TRỪ KHI `is_price_negotiable=true` |
| **Hook lưu ảnh thủ công có thể thừa** | Trung bình | **Đã xác nhận không tồn tại** ở §4.4 — 1 nguồn ghi ảnh duy nhất qua Media Library |
| **Naming không nhất quán với Rent** — Sale không có prefix thống nhất, khó maintain | Trung bình | **Hoá ra đã đúng sẵn, không cần sửa**: bản WordPress gốc BẤT NHẤT thật (Sale không prefix — `price`/`bedrooms`; Rent prefix `rent_` — `rent_price`/`rent_bedrooms`), nhưng bản Laravel **KHÔNG PORT prefix của bên nào cả** — mọi cột (dùng chung hay riêng từng loại) đều KHÔNG có prefix `sale_`/`rent_`, phân biệt bằng `listing_type` + Request rule, không bằng tên cột (xem migration §3.2 — `price`/`is_urgent` và `monthly_rent`/`deposit` đều trần, không tiền tố). Naming đã nhất quán 100% giữa 2 spec ngay từ v1.0, review này giúp xác nhận lại rõ ràng |
| **Thiếu field nâng cao** (năm xây dựng, thang máy, hỗ trợ vay ngân hàng) | Thấp | Đúng mức "Thấp" user đã đánh giá — KHÔNG nằm trong 5 khuyến nghị ưu tiên, đưa vào §9 "Ngoài phạm vi" làm điểm mở rộng rõ ràng cho phase sau, không chặn triển khai v1 |

### 0.2 Review lần 3 (tổng hợp chung Sale + Rent) — v1.4

Bảng review lần 3 lặp lại đúng 6 vấn đề đã có ở §0.1 (địa chỉ, JS conditional, validation, hook ảnh, naming, field Rent thiếu) — không có phát hiện mới về kiến trúc, chỉ 1 điểm cần LÀM RÕ THÊM (đã cập nhật §2.5):

- **Địa chỉ "chỉ 1 field text, cần bổ sung 3 field cấu trúc hoá"**: ĐÃ ĐỦ 3 field từ v1.0/v1.1 — `address_detail` (số nhà/đường) + `province_code` + `ward_code` (BẮT BUỘC từ v1.1, §0). Điểm mới làm rõ ở v1.4: 2 field tỉnh/phường này khớp ĐÚNG cấu trúc hành chính 2 cấp hiện hành của Việt Nam (Tỉnh/Thành → Phường/Xã/Đặc khu, bỏ cấp huyện sau sáp nhập 2025) — xem `wards` FK trực tiếp `provinces`, không qua bảng quận/huyện nào (§2.5). Không thiếu field nào, không cần thêm "quận/huyện".
- **Naming — "thống nhất prefix `sale_`/`rent_` HOẶC chiến lược chung"**: bản Laravel đã chọn đúng nhánh "chiến lược chung" mà review liệt kê như 1 lựa chọn hợp lệ (§0.1 v1.3) — không cần đổi sang prefix.
- 4 điểm còn lại (JS conditional, validation, hook ảnh, Rent thiếu field) — xem đầy đủ ở §0.1, không lặp lại.

---

## 1. Giới thiệu & Mục tiêu

Port tính năng "Thông tin chi tiết nhà đất bán" từ plugin WordPress Meta Box (`spec/PropertyForSaleMetabox.md` — custom post type `property-for-sale`, ~30 custom field có logic ẩn/hiện lồng nhau theo loại hình nhà) sang kiến trúc Laravel Module của hệ thống hiện tại, để 1 doanh nghiệp BĐS (Organization) tự đăng — sau khi qua duyệt của tòa soạn — 1 tin rao bán nhà/căn hộ/đất, hiển thị công khai tại `/nha-dat-ban`.

**Nguyên tắc thiết kế cốt lõi:**
- Dùng CHUNG 1 bảng + hạ tầng với biến thể "cho thuê" (spec song sinh) — chỉ khác `listing_type`.
- KHÔNG viết lại workflow duyệt — tái dùng `Modules\Approval\Concerns\HasApproval` xuyên-domain.
- KHÔNG tự đếm lượt xem — dùng Google Analytics.
- Mọi field, kể cả field ít lọc/đặc thù theo loại nhà, là **cột SQL thật** (nullable theo `property_type`) — KHÔNG dùng JSON để lưu trữ dữ liệu (§0 v1.2).

---

## 2. Khảo sát hiện trạng

### 2.1 Nguồn field gốc — `spec/PropertyForSaleMetabox.md`

File WordPress Meta Box định nghĩa `PropertyForSaleMetabox extends BaseMetabox`, `post_types = ['property-for-sale']`, ~30 field chia 4 nhóm (heading): **Vị trí**, **Giá bán**, **Loại hình bất động sản**, **Thông tin nhà**, **Thông tin khác**, **Hình ảnh**. Có logic JS fallback (`jsFallback()`, dòng 335-394) ẩn/hiện field lồng nhau: chọn `property_type` → hiện field tương ứng (nhà riêng/căn hộ/đất); với apartment, chọn `usage_status=dang_cho_thue` → hiện thêm `monthly_rent`.

### 2.2 Tiền lệ 1 bảng + cột phân loại — `ArticleFormat`

`Modules/Post/app/Enums/ArticleFormat.php:5-14`:
```php
enum ArticleFormat: string
{
    case Article = 'article';
    case Video = 'video';
    // ...
    case Redirect = 'redirect';
}
```
`PostArticle::isRedirect(): bool { return $this->format === ArticleFormat::Redirect; }` (`Modules/Post/app/Models/PostArticle.php:202-205`) — field `redirect_url` chỉ có nghĩa khi `format=Redirect`, vẫn tồn tại (NULL) cho mọi format khác trên CÙNG bảng `post_articles`. Đây là mẫu trực tiếp cho `real_estate_listings.listing_type`.

### 2.3 Workflow duyệt dùng chung — `Modules\Approval`

`Modules/Approval/app/Concerns/HasApproval.php` — trait cắm vào bất kỳ model nào cần "nội dung Organization phải qua duyệt của Platform trước khi công khai":
- `bootHasApproval()` (dòng 37-50): tự tạo `ApprovalSubject` (`status=Draft`) ngay khi tạo entity; tự động chuyển `Pending` khi 1 trong `approvalWatchedAttributes()` bị sửa SAU KHI đã Approved/Published (không cần code tiêu thụ tự gọi).
- `scopePubliclyVisible()` (dòng 149-154) — điều kiện public: có `public_snapshot` VÀ chưa `Archived`. Trang công khai PHẢI dùng scope này, không tự lọc theo cột `status` nào trên chính entity.
- `publicContent()` (dòng 164-169) — trả field "nội dung" từ snapshot đã duyệt (đóng băng), field "vận hành" (giá, tồn kho...) đọc trực tiếp từ entity (hiệu lực ngay, không chờ duyệt).
- Đăng ký qua `config('approval.subjects')` (`Modules/Approval/config/approval.php:11-31`) — mỗi entry: `model`, `label`, (tuỳ chọn) `initial_status_resolver` (chỉ cần khi backfill dữ liệu ĐÃ CÓ trước khi tích hợp — `real_estate_listings` là bảng mới, không cần resolver).

`Modules/Product/app/Policies/ProductPolicy.php:48-79` — mẫu Policy tham khảo: `submitForApproval()` check `$user->can('product.edit')` (Organization); `approve/reject/publishApproval/archiveApproval` đều check `$user->isPlatformContentModerator()` (Lớp A, role dùng chung).

### 2.4 Gallery đa ảnh có thứ tự — Spatie Media Library

Bảng `media` (`database/migrations/vendor/2026_05_13_020107_create_media_table.php:31`) đã có cột `order_column` (`unsignedInteger nullable index`) — chuẩn Spatie, KHÔNG cần migration thêm. `InteractsWithMedia::getMedia()` (`vendor/spatie/laravel-medialibrary/src/InteractsWithMedia.php` dòng ~653) tự `sortBy('order_column')`; `addMedia()` tự gán `order_column` tăng dần (dòng ~509). `MediaUploadController::SINGLE_FILE_COLLECTIONS` (`app/Http/Controllers/Api/MediaUploadController.php:47`) hiện chỉ gồm collection 1-ảnh (`avatar/logo/thumbnail/cover/banner`) — collection multi-ảnh mới KHÔNG thêm vào danh sách này, FilePond tự cho phép `allowMultiple` (xem `resources/js/modules/filepond.js:158,181` — `isSingle = SINGLE_FILE_COLLECTIONS.has(collection)`, `allowMultiple: !isSingle`).

### 2.5 Địa chỉ tỉnh/phường

`resources/views/components/address-picker.blade.php:9-33` — 2 `<select>` (`ts-prov-{instanceId}`/`ts-ward-{instanceId}`), field tên tuỳ biến qua prop `nameProvince`/`nameWard`. Bảng `provinces` (`province_code` char(2) unique) + `wards` (`ward_code` char(5) unique, FK `province_code` — `database/migrations/generated/2026_07_24_014853_000121_create_wards_table.php:23-26`). `PostArticle` đã dùng field này (`province_code`/`ward_code` — xem `x-address-picker` trong `admin/articles/create.blade.php`).

**Lưu ý quan trọng — cấu trúc hành chính 2 CẤP, KHÔNG có quận/huyện:** bảng `wards` FK trực tiếp tới `provinces` (`province_code`), KHÔNG qua bảng `districts`/quận-huyện nào — đúng cấu trúc hành chính Việt Nam SAU sáp nhập (2025): Tỉnh/Thành phố → Phường/Xã/Đặc khu (`place_type` enum `phuong`/`xa`/`dac-khu`, dòng 24), bỏ hẳn cấp huyện. Vì vậy **`province_code` + `ward_code` (2 field) đã là đủ, đúng chuẩn hành chính hiện hành** — cùng với `address_detail` (số nhà/đường, tự do) là ĐỦ 3 field cấu trúc hoá cần thiết cho địa chỉ (§0, §5.4), KHÔNG cần/KHÔNG nên thêm field "quận/huyện" nào (không còn tồn tại trong thực tế để chọn).

### 2.6 Meilisearch — mẫu filterable/sortable

`config/scout.php:143-154`:
```php
'index-settings' => [
    PostArticleTranslation::class => [
        'filterableAttributes' => ['locale', 'status', 'category_slugs', 'province_code', 'format', 'is_featured'],
        'sortableAttributes'   => ['published_at'],
    ],
    OcopProduct::class => [
        'filterableAttributes' => ['status', 'province_code', 'category_id', 'is_featured'],
        'sortableAttributes'   => ['star_rating'],
    ],
],
```
Chưa có mẫu range-filter theo giá/diện tích — Meilisearch hỗ trợ range trực tiếp trên field numeric (`price >= 1000000000 AND price <= 3000000000`), chỉ cần khai field đó trong `filterableAttributes`.

---

## 3. Kiến trúc dữ liệu

### 3.1 ERD

```
RealEstateListing (real_estate_listings)
  ├─ organization_id (FK organizations, bắt buộc)          — chủ tin đăng (§0)
  ├─ listing_type ('sale' | 'rent')                          — discriminator (§0)
  ├─ property_type ('house' | 'apartment' | 'land' | 'layout') — 'land' chỉ hợp lệ khi sale, 'layout' chỉ hợp lệ khi rent
  ├─ title, slug, description
  ├─ address_detail, province_code (FK), ward_code (FK)
  ├─ area (m², diện tích chính — §0)
  ├─ bedrooms, bathrooms, floors
  ├─ interior_status ('day_du' | 'co_ban' | 'ban_giao_tho')
  ├─ is_price_negotiable (bool — "Giá thoả thuận", §0)
  ├─ price, is_urgent, urgent_days (CHỈ sale)             ├─ monthly_rent, deposit, rental_period_months (CHỈ rent)
  ├─ house_subtype, apartment_subtype, width, length      — CHỈ sale, cột SQL thật (§0 v1.2, KHÔNG dùng JSON)
  ├─ land_area, usable_area, net_area                     — breakdown chi tiết của `area`
  ├─ legal_status, direction, balcony_direction           — dùng chung house/apartment/land
  ├─ project_name, apartment_address, usage_status        — CHỈ apartment
  ├─ front_road_width                                      — CHỈ land + sale
  ├─ current_rental_income (CHỈ sale)  ├─ management_fee (apartment + rent, spec Thuê §2.2)
  ├─ is_featured, sort_order
  ├─ created_by, updated_by (FK users)
  ├─ timestamps, soft deletes
  (gallery ảnh qua Media Library, collection "real_estate_gallery" — không phải cột riêng)
  (trạng thái duyệt qua ApprovalSubject — polymorphic, KHÔNG cột status riêng trên bảng này)
```

### 3.2 Migration

`Modules/RealEstate/database/migrations/2026_07_25_000001_create_real_estate_listings_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/RealEstateForSale_Technical_Specification.md §3 — 1 bảng dùng chung cho cả bán/thuê
 * (listing_type phân biệt, tiền lệ ArticleFormat). Trạng thái duyệt qua ApprovalSubject
 * (Modules\Approval, polymorphic) — KHÔNG có cột status trên bảng này.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('real_estate_listings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();

            $table->string('listing_type', 10);   // ListingType enum
            $table->string('property_type', 20);  // PropertyType enum

            $table->string('title', 250);
            $table->string('slug', 270);
            $table->text('description')->nullable();

            $table->string('address_detail', 255)->nullable(); // số nhà/đường — tự do, chỉ province/ward mới bắt buộc (§0 v1.1)
            $table->char('province_code', 2);  // BẮT BUỘC — §0 v1.1
            $table->char('ward_code', 5);      // BẮT BUỘC — §0 v1.1

            $table->decimal('area', 10, 2)->nullable();
            $table->unsignedSmallInteger('bedrooms')->nullable();
            $table->unsignedSmallInteger('bathrooms')->nullable();
            $table->unsignedSmallInteger('floors')->nullable();
            $table->string('interior_status', 20)->nullable();

            $table->boolean('is_price_negotiable')->default(false); // "Giá thoả thuận" — cả sale + rent, §0 v1.1

            // CHỈ sale
            $table->decimal('price', 15, 0)->nullable();
            $table->boolean('is_urgent')->default(false);
            $table->unsignedSmallInteger('urgent_days')->nullable();

            // CHỈ rent (spec song sinh RealEstateForRent §3)
            $table->decimal('monthly_rent', 15, 0)->nullable();
            $table->decimal('deposit', 15, 0)->nullable();
            $table->unsignedSmallInteger('rental_period_months')->nullable();

            // ── Field đặc thù theo property_type — CỘT SQL THẬT, KHÔNG dùng JSON (§0 v1.2) ──
            $table->string('house_subtype', 20)->nullable();      // CHỈ house + sale
            $table->string('apartment_subtype', 20)->nullable();  // CHỈ apartment + sale
            $table->decimal('width', 8, 2)->nullable();           // CHỈ house/land + sale
            $table->decimal('length', 8, 2)->nullable();          // CHỈ house/land + sale
            $table->decimal('land_area', 10, 2)->nullable();      // CHỈ house/land + sale — breakdown chi tiết của `area`
            $table->decimal('usable_area', 10, 2)->nullable();    // CHỈ apartment — breakdown chi tiết của `area`
            $table->decimal('net_area', 10, 2)->nullable();       // CHỈ apartment + sale — breakdown chi tiết của `area`
            $table->string('legal_status', 20)->nullable();       // house/apartment/land + sale, apartment + rent (§0 v1.1 RealEstateForRent)
            $table->string('direction', 15)->nullable();          // house/apartment/land (dùng chung 1 cột — §0)
            $table->string('balcony_direction', 15)->nullable();  // CHỈ apartment
            $table->string('project_name', 150)->nullable();      // CHỈ apartment
            $table->string('apartment_address', 150)->nullable(); // CHỈ apartment
            $table->string('usage_status', 20)->nullable();       // apartment + sale, apartment + rent
            $table->decimal('front_road_width', 8, 2)->nullable(); // CHỈ land + sale
            $table->decimal('current_rental_income', 15, 0)->nullable(); // CHỈ sale — khác monthly_rent (chỉ rent)
            $table->decimal('management_fee', 15, 0)->nullable();  // apartment + rent (spec song sinh §2.2), khả dụng cho apartment + sale nếu cần

            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'slug'], 'uq_real_estate_org_slug');
            $table->index(['listing_type', 'property_type', 'is_featured'], 'idx_real_estate_type_featured');
            $table->index(['province_code', 'ward_code'], 'idx_real_estate_location');
            $table->index(['organization_id', 'listing_type'], 'idx_real_estate_org_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('real_estate_listings');
    }
};
```

---

## 4. Model & Enums

### 4.1 `RealEstateListing` model

`Modules/RealEstate/app/Models/RealEstateListing.php`:
```php
<?php

namespace Modules\RealEstate\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Approval\Concerns\HasApproval;
use Modules\RealEstate\Enums\ApartmentSubtype;
use Modules\RealEstate\Enums\CompassDirection;
use Modules\RealEstate\Enums\HouseSubtype;
use Modules\RealEstate\Enums\LegalStatus;
use Modules\RealEstate\Enums\ListingType;
use Modules\RealEstate\Enums\PropertyType;
use Modules\RealEstate\Enums\UsageStatus;
use Spatie\MediaLibrary\HasMedia;

class RealEstateListing extends TenantAwareModel implements HasMedia
{
    use HasApproval;
    use HasTenantMedia;

    protected $table = 'real_estate_listings';

    protected $fillable = [
        'organization_id', 'listing_type', 'property_type', 'title', 'slug', 'description',
        'address_detail', 'province_code', 'ward_code', 'area', 'bedrooms', 'bathrooms',
        'floors', 'interior_status', 'is_price_negotiable', 'price', 'is_urgent', 'urgent_days',
        'monthly_rent', 'deposit', 'rental_period_months',
        // Field đặc thù theo property_type — cột SQL thật, KHÔNG dùng JSON (§0 v1.2)
        'house_subtype', 'apartment_subtype', 'width', 'length', 'land_area', 'usable_area',
        'net_area', 'legal_status', 'direction', 'balcony_direction', 'project_name',
        'apartment_address', 'usage_status', 'front_road_width', 'current_rental_income',
        'management_fee',
        'is_featured', 'sort_order', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'listing_type'           => ListingType::class,
        'property_type'          => PropertyType::class,
        'house_subtype'          => HouseSubtype::class,
        'apartment_subtype'      => ApartmentSubtype::class,
        'legal_status'           => LegalStatus::class,
        'direction'              => CompassDirection::class,
        'balcony_direction'      => CompassDirection::class,
        'usage_status'           => UsageStatus::class,
        'area'                   => 'decimal:2',
        'width'                  => 'decimal:2',
        'length'                 => 'decimal:2',
        'land_area'              => 'decimal:2',
        'usable_area'            => 'decimal:2',
        'net_area'               => 'decimal:2',
        'front_road_width'       => 'decimal:2',
        'price'                  => 'decimal:0',
        'monthly_rent'           => 'decimal:0',
        'deposit'                => 'decimal:0',
        'current_rental_income'  => 'decimal:0',
        'management_fee'         => 'decimal:0',
        'is_urgent'              => 'boolean',
        'is_price_negotiable'    => 'boolean',
        'is_featured'            => 'boolean',
        'sort_order'             => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ── Relationships ────────────────────────────────────────────────

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    // ── HasApproval contract ─────────────────────────────────────────

    /**
     * Sửa field này khi đã Approved/Published sẽ tự chuyển Pending (§2.3) — KHÔNG gồm giá/
     * is_featured/sort_order (trục vận hành, đổi giá không cần duyệt lại nội dung).
     */
    public function approvalWatchedAttributes(): array
    {
        return [
            'title', 'description', 'address_detail', 'property_type',
            'house_subtype', 'apartment_subtype', 'legal_status', 'direction',
            'balcony_direction', 'project_name', 'apartment_address', 'usage_status',
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function isSale(): bool
    {
        return $this->listing_type === ListingType::Sale;
    }

    public function isRent(): bool
    {
        return $this->listing_type === ListingType::Rent;
    }

    /**
     * Giá hiển thị — ưu tiên `is_price_negotiable` (§0 v1.1) trước khi xét có số hay không,
     * KHÔNG suy diễn "thoả thuận" chỉ từ giá trị null (null mà is_price_negotiable=false là
     * dữ liệu thiếu/lỗi, phải chặn ở validate §5.4, không phải hiển thị "Thoả thuận" cho qua).
     */
    public function getDisplayPriceAttribute(): string
    {
        if ($this->is_price_negotiable) {
            return 'Thoả thuận';
        }

        if ($this->isSale()) {
            return $this->price ? number_format((float) $this->price / 1_000_000_000, 1) . ' tỷ' : '—';
        }

        return $this->monthly_rent ? number_format((float) $this->monthly_rent) . ' đ/tháng' : '—';
    }

    /** Ảnh gallery, đã sort theo order_column (§2.4) — max 6, validate ở Action, không phải DB constraint. */
    public function galleryUrls(string $conversion = 'medium'): array
    {
        return $this->getMedia('real_estate_gallery')->map(
            fn ($media) => $media->getUrl($conversion)
        )->all();
    }
}
```

### 4.2 Enums (`Modules/RealEstate/app/Enums/`)

| Enum | Giá trị | Ghi chú |
|---|---|---|
| `ListingType` | `sale`, `rent` | Discriminator chính (§0) |
| `PropertyType` | `house`, `apartment`, `land`, `layout` | `land` chỉ hợp lệ `listing_type=sale`; `layout` chỉ hợp lệ `listing_type=rent`; `house`/`apartment` dùng chung cả 2. Validate bằng `PropertyType::validFor(ListingType $type): array` |
| `InteriorStatus` | `day_du`, `co_ban`, `ban_giao_tho` | Dùng chung sale + rent (cột SQL thật) |
| `CompassDirection` | `tay_bac`, `bac`, `dong_bac`, `tay`, `dong`, `tay_nam`, `nam`, `dong_nam` | Cast cho cột `direction` — dùng chung cho house/apartment/land (WP gốc tách 3 field `direction`/`apartment_direction`/`land_direction` trùng lặp y hệt, gộp lại DRY). Cột `balcony_direction` cast CÙNG enum này, dùng subset qua `CompassDirection::balconyOptions()` (4/8 hướng, đúng field gốc dòng 271-278 `PropertyForSaleMetabox.md`) |
| `LegalStatus` | `so_hong_rieng`, `so_hong_chung`, `dang_hoan_cong`, `hop_dong`, `dang_cho_cap_so` | Cast cho cột `legal_status` — gộp 3 field WP `legal_house`/`legal_apartment`/`legal_land` (§0), Request validate option hợp lệ theo `property_type` |
| `UsageStatus` | `dang_o`, `dang_cho_thue`, `nha_trong` | Cast cho cột `usage_status` — chỉ hiện field khi `property_type=apartment` (sale hoặc rent, xem spec Thuê §2.2) |
| `HouseSubtype` | `alley`, `street`, `adjacent`, `villa` | Cast cho cột `house_subtype` — chỉ `property_type=house` + sale |
| `ApartmentSubtype` | `apartment`, `officetel`, `duplex`, `penthouse`, `shophouse` | Cast cho cột `apartment_subtype` — chỉ `property_type=apartment` + sale |

### 4.3 `config/real-estate.php` (`Modules/RealEstate/config/config.php`)

```php
return [
    'gallery' => [
        'collection'      => 'real_estate_gallery',
        'max_files'       => 6,   // validate ở Action + client FilePond maxFiles
    ],
    'listings_per_page' => 12,
];
```

### 4.4 `config/media.php` — thêm collection `real_estate_gallery`

```php
'real_estate_gallery' => [
    'max_size_kb'  => 10240,
    'allowed_mime' => ['image/jpeg', 'image/png', 'image/webp'],
    'is_public'    => true,
    'conversions'  => ['thumb', 'medium'],
],
```
KHÔNG thêm vào `MediaUploadController::SINGLE_FILE_COLLECTIONS` (§2.4) — multi-file mặc định.

**v1.1 — 1 nguồn ghi ảnh duy nhất (vá lỗi "hook lưu ảnh thủ công thừa" của bản gốc):** bản WordPress gốc có `add_action('rwmb_after_save_post', ...)` tự `update_post_meta('property_images', $images)` (dòng 401-413 `PropertyForSaleMetabox.md`) — DƯ THỪA vì plugin Meta Box đã tự lưu field `image_advanced` qua cơ chế riêng của nó, tạo ra 2 nguồn ghi cùng 1 dữ liệu ảnh (rủi ro lệch nhau nếu 1 trong 2 đường ghi thất bại giữa đường). Ở Laravel, **Media Library là NGUỒN DUY NHẤT**: `CreateRealEstateListingAction`/`UpdateRealEstateListingAction` CHỈ gọi `MediaUploadService::reassociateFilePondDrafts()` (khi tạo mới, ảnh đến từ FilePondDraft) hoặc để nguyên (khi sửa, ảnh đã gắn trực tiếp qua `X-Context-Type/Id` — xem `Modules/Post/app/Features/AuthorHub/Actions/UpsertAuthorProfileAction.php` cho mẫu 2 nhánh y hệt) — KHÔNG bao giờ ghi thêm mảng ID/URL ảnh vào bất kỳ cột nào khác trên `real_estate_listings`. `galleryUrls()` (§4.1) luôn đọc trực tiếp từ `getMedia()`, không đọc từ 1 "cache" nào tự lưu.

---

## 5. Business rules

### 5.1 Điều kiện hiển thị công khai

`RealEstateListing::scopePubliclyVisible()` (kế thừa từ `HasApproval`, §2.3) — có `public_snapshot` VÀ chưa `Archived`. Trang `/nha-dat-ban` và `/nha-dat-ban/{slug}-r{id}.html` LUÔN filter thêm `listing_type = 'sale'`.

### 5.2 Mapping field gốc (`PropertyForSaleMetabox.md`) → cột SQL

| Field gốc WP (`id`) | Đích Laravel | Ghi chú |
|---|---|---|
| `address_detail` | cột `address_detail` | |
| `price` | cột `price` (VNĐ đầy đủ) | Đổi đơn vị từ "tỷ" — §0 |
| `urgent_sale` | cột `is_urgent` | |
| `urgent_days` | cột `urgent_days` | Hiện khi `is_urgent=true` |
| `property_type` | cột `property_type` | `house`/`apartment`/`land` |
| `house_subtype` | cột `house_subtype` | Hiện khi `property_type=house` |
| `apartment_subtype` | cột `apartment_subtype` | Hiện khi `property_type=apartment` |
| `width`, `length` | cột `width`, cột `length` | Chỉ house/land |
| `land_area` | cột `land_area` (+ copy sang `area` nếu house/land, §0) | |
| `usable_area` | cột `usable_area` (+ dự phòng copy sang `area` nếu `net_area` trống) | Chỉ apartment |
| `legal_house`/`legal_apartment`/`legal_land` | cột `legal_status` | Gộp 1 cột — §0 |
| `direction` | cột `direction` | Chỉ house, `CompassDirection` |
| `floors` | cột `floors` | |
| `bedrooms` | cột `bedrooms` | |
| `bathrooms` | cột `bathrooms` | |
| `project_name` | cột `project_name` | Chỉ apartment |
| `apartment_address` | cột `apartment_address` | Chỉ apartment |
| `net_area` | cột `net_area` (+ copy sang `area` — ưu tiên hơn `usable_area`) | Chỉ apartment |
| `usage_status` | cột `usage_status` | Chỉ apartment |
| `interior_status` | cột `interior_status` | Dùng chung |
| `apartment_direction` | cột `direction` | Gộp CHUNG với `direction` (house) — cùng 1 cột, §0 |
| `balcony_direction` | cột `balcony_direction` | Chỉ apartment, subset `CompassDirection` |
| `front_road_width` | cột `front_road_width` | Chỉ land |
| `land_direction` | cột `direction` | Gộp CHUNG với `direction` (house/apartment) — cùng 1 cột, §0 |
| `monthly_rent` (field phụ "đang cho thuê") | cột `current_rental_income` | Đổi tên tránh đụng cột `monthly_rent` chính của Rent — §0 |
| `property_images` (max 6) | Media Library collection `real_estate_gallery` | §2.4 |

### 5.3 Validate theo `property_type` (Action/Request — thay JS fallback WP §2.1)

Toàn bộ logic ẩn/hiện lồng nhau ở `PropertyForSaleMetabox::jsFallback()` (dòng 335-394) được chuyển thành:
- **Client-side**: Alpine `x-show` theo `property_type`/`is_urgent`/`usage_status` (UX, không phải validate thật).
- **Server-side (bắt buộc, không chỉ dựa JS)**: `StoreRealEstateListingRequest` — rule `required_if`/`prohibited_unless` theo `property_type`; ví dụ `house_subtype` chỉ `required_if:property_type,house`, `usage_status`/`balcony_direction` chỉ hợp lệ khi `property_type,apartment`. **Field không thuộc `property_type` hiện tại bị Action SET VỀ `NULL`** khi lưu (không lưu rác nếu user đổi loại hình qua lại) — vì giờ là cột SQL thật, không phải strip key khỏi JSON như bản v1.1.

### 5.4 Validation bắt buộc (v1.1 — vá lỗ hổng "chỉ 1 field required" của bản WordPress gốc)

Bản gốc `PropertyForSaleMetabox.md` chỉ có ĐÚNG 1 field `required => true` (`property_type`, dòng 54) — không đủ để đảm bảo 1 tin đăng có thông tin tối thiểu hữu ích. `StoreRealEstateListingRequest`/`UpdateRealEstateListingRequest` bắt buộc thêm:

| Field | Rule | Lý do |
|---|---|---|
| `listing_type` | `required` | Discriminator — không thể suy ra mặc định |
| `property_type` | `required`, giá trị hợp lệ theo `listing_type` (`PropertyType::validFor()`) | Giữ nguyên field required duy nhất của bản gốc |
| `province_code`, `ward_code` | `required` | §0 v1.1 — khu vực là tiêu chí lọc cốt lõi |
| `area` | `required`, `numeric`, `min:1` | Diện tích 0 hoặc trống không có ý nghĩa cho 1 tin BĐS |
| `price` (sale) / `monthly_rent` (rent) | `required_if:is_price_negotiable,false`, `numeric`, `min:0` | §0 v1.1 — bắt buộc CÓ giá TRỪ KHI chủ đích để "Giá thoả thuận" |
| `is_price_negotiable` | `boolean` | Cho phép bỏ qua rule giá bắt buộc ở trên |
| `deposit`, `rental_period_months` (rent) | `nullable` — KHÔNG bắt buộc | Thông tin phụ, nhiều tin ghi "thoả thuận"/"tuỳ thương lượng" — ép buộc sẽ chặn cả tin hợp lệ |

### 5.5 Quyền xem/sửa

- Organization (CEO/Sales/Marketing/Ops, `real_estate.edit`): CRUD tin của TỔ CHỨC MÌNH (tenant-scoped tự động qua `TenantAwareModel`), tự `submitForApproval`.
- `platform_content_moderator`: `approve/reject/publishApproval/archiveApproval` — xem trong dashboard "Chờ duyệt" chung (không riêng cho BĐS).
- Độc giả công khai: chỉ thấy tin `publiclyVisible()` + `listing_type=sale`.

---

## 6. RBAC & Permission

`app/Enums/PermissionEnum.php` — thêm 4 case (Lớp B, KHÔNG qua seeder riêng — sync qua `config/permissions.php` + `php artisan permissions:sync`, đúng nguyên tắc Lớp B đã áp dụng cho `PRODUCT_*`):
```php
case REAL_ESTATE_VIEW   = 'real_estate.view';
case REAL_ESTATE_CREATE = 'real_estate.create';
case REAL_ESTATE_EDIT   = 'real_estate.edit';
case REAL_ESTATE_DELETE = 'real_estate.delete';
```

`config/permissions.php` — thêm vào đúng khối role đã cấp `PRODUCT_*` (CEO dòng ~41, SALES ~68, OPS ~99, MARKETING ~122):
```php
// Real Estate: View + Create + Edit (CEO/Sales/Marketing/Ops tự đăng tin BĐS — cùng nhóm quản lý Product)
P::REAL_ESTATE_VIEW->value,
P::REAL_ESTATE_CREATE->value,
P::REAL_ESTATE_EDIT->value,
```
Role System_Admin (Lớp B, Admin tổ chức) thêm `REAL_ESTATE_DELETE->value` — copy đúng vị trí `PRODUCT_DELETE` hiện có.

`Modules/Approval/config/approval.php` — thêm entry:
```php
'real_estate_listing' => [
    'model' => \Modules\RealEstate\Models\RealEstateListing::class,
    'label' => 'Tin bất động sản',
],
```
(Không cần `initial_status_resolver` — bảng mới, không backfill.)

`Modules/RealEstate/app/Policies/RealEstateListingPolicy.php` — copy đúng cấu trúc `ProductPolicy` (`viewAny/view/create/update/delete` check `real_estate.*`; `submitForApproval` check `real_estate.edit`; `approve/reject/publishApproval/archiveApproval` check `isPlatformContentModerator()`).

---

## 7. Render công khai

### 7.1 Routes

`Modules/RealEstate/routes/web.php`:
```php
// Admin (Organization) — middleware ['auth', 'tenant'] giống Product
Route::middleware(['auth', 'tenant'])->prefix('dashboard/real-estate')->name('backend.real-estate.')
    ->group(function (): void {
        Route::get('/', [RealEstateListingAdminController::class, 'index'])->name('index');
        Route::get('create', [RealEstateListingAdminController::class, 'create'])->name('create');
        Route::post('/', [RealEstateListingAdminController::class, 'store'])->name('store');
        Route::get('{listing}/edit', [RealEstateListingAdminController::class, 'edit'])->name('edit');
        Route::put('{listing}', [RealEstateListingAdminController::class, 'update'])->name('update');
        Route::delete('{listing}', [RealEstateListingAdminController::class, 'destroy'])->name('destroy');
        Route::post('{listing}/gallery/reorder', [RealEstateListingAdminController::class, 'reorderGallery'])->name('gallery.reorder');
        Route::post('{listing}/submit-approval', [RealEstateListingAdminController::class, 'submitApproval'])->name('submit-approval');
    });

// Public — không middleware auth
Route::get('nha-dat-ban', [PublicRealEstateController::class, 'saleIndex'])->name('real-estate.public.sale.index');
Route::get('nha-dat-ban/{slug}-r{id}.html', [PublicRealEstateController::class, 'saleShow'])
    ->where(['slug' => '[a-z0-9\-]+', 'id' => '[0-9]+'])->name('real-estate.public.sale.show');
```
(`approve/reject/publishApproval/archiveApproval` xử lý qua UI chung của `Modules/Approval` — `ApprovalDashboardController`, không cần route riêng ở đây, đúng §0/§2.3.)

### 7.2 Trang danh sách `/nha-dat-ban`

Lưới card: ảnh đầu gallery, giá (`display_price`), diện tích, phòng ngủ/tắm, khu vực (tên phường/tỉnh). Filter query params: `property_type`, `province_code`, `price_min/price_max`, `bedrooms`, `area_min/area_max` — build Meilisearch filter string từ query, fallback LIKE/where nếu Meilisearch lỗi (tiền lệ `ListPublishedArticlesHandler::handleViaDatabase()`).

### 7.3 Trang chi tiết `/nha-dat-ban/{slug}-r{id}.html`

Gallery ảnh (carousel, `galleryUrls()`), giá + badge "Bán gấp" nếu `is_urgent`, bảng thông tin chi tiết (property_type-dependent — chỉ hiển thị đúng cột áp dụng cho loại đó, các cột NULL không hiện dòng), bản đồ theo `province_code`/`ward_code`, nút liên hệ (số điện thoại Organization — KHÔNG public form riêng, ngoài phạm vi §9). 404 nếu không `publiclyVisible()` hoặc `listing_type != sale`.

---

## 8. Kế hoạch triển khai

1. Migration `real_estate_listings` (§3.2) + model `RealEstateListing` (§4.1) + Enums (§4.2) + config (§4.3, §4.4 — thêm collection `real_estate_gallery` vào `config/media.php` + `MediaUploadController::ALLOWED_COLLECTIONS`/`ENTITY_MAP`).
2. Đăng ký `config('approval.subjects')` (§6) + `RealEstateListingPolicy` (§6) + `Gate::policy()` trong `RealEstateServiceProvider`.
3. Permission `REAL_ESTATE_*` (§6) + cập nhật `config/permissions.php` + chạy `php artisan permissions:sync`.
4. `Modules/RealEstate/app/Features/ListingManagement/` — Data/Action (Create/Update/Delete/ReorderGallery) + `RealEstateListingAdminController` + views admin (form theo `property_type`, Alpine ẩn/hiện — thay JS fallback WP §5.3).
5. `Modules/RealEstate/app/Features/PublicReading/` — Query/Handler danh sách + chi tiết + `PublicRealEstateController` + routes (§7.1) + views public (`/nha-dat-ban`).
6. Scout: đăng ký `RealEstateListing` searchable, `toSearchableArray()` + `config/scout.php` filterable/sortable (§0, §2.6).
7. Test: tạo tin `listing_type=sale`, `property_type` đổi qua lại → cột đúng loại được lưu, cột không thuộc loại tự set về `NULL` (§5.3); submit → duyệt (`platform_content_moderator`) → publish → xuất hiện `/nha-dat-ban`; sửa nội dung sau publish → tự về `Pending`, vẫn hiển thị bản cũ (snapshot) tới khi duyệt lại; gallery upload 6 ảnh + reorder → thứ tự đúng; ảnh thứ 7 bị chặn.

---

## 9. Ngoài phạm vi (v1)

- Form liên hệ/nhắn tin trực tiếp với người đăng tin (chỉ hiển thị SĐT/thông tin Organization).
- Bản đồ tương tác chọn vị trí chính xác (chỉ dùng `province_code`/`ward_code`, không toạ độ GPS).
- Gói tin VIP/đẩy tin (chỉ có `is_featured` đơn giản, không có cơ chế thanh toán đẩy tin).
- Tin hết hạn tự ẩn theo ngày (WP gốc không có field này — nếu cần, bổ sung `expires_at` ở phase sau).
- So sánh nhiều tin cùng lúc, lưu tin yêu thích (độc giả).
- **Field nâng cao chưa có trong WP gốc** (review lần 2, §0.1 — mức "Thấp", có thể bổ sung sau, KHÔNG chặn v1): năm xây dựng (`construction_year`, integer), thang máy (`has_elevator`, boolean — chỉ apartment/house cao tầng), hỗ trợ vay ngân hàng (`bank_loan_support`, boolean hoặc text mô tả %). Nếu bổ sung sau, thêm đúng cột SQL thật trên `real_estate_listings` (§0 v1.2 — KHÔNG dùng JSON), không cần đổi cấu trúc bảng.

---

## 10. Rủi ro & Đánh giá thực tiễn

| Rủi ro | Mức độ | Đánh giá |
|---|---|---|
| Nhồi cả field sale+rent+mọi property_type vào 1 bảng khiến nhiều cột NULL (~20 cột đặc thù, §0 v1.2) | Thấp | Đúng tinh thần thiết kế `Product` trong CHÍNH codebase này (nhiều cột nullable theo `type`) — cột NULL không tốn chi phí đáng kể ở PostgreSQL/MySQL hiện đại, và đổi lại filter/sort/index được TRỰC TIẾP ở SQL mọi field, không cần biết field nào "chôn" trong JSON |
| Tái dùng `HasApproval` — nếu `Modules/Approval` có breaking change ảnh hưởng chéo Product/Organization/RealEstate | Thấp | Đây là rủi ro chấp nhận được của MỌI module dùng chung hạ tầng (đã tồn tại với Product) — không phải rủi ro riêng của RealEstate |
| `platform_content_moderator` phải duyệt thêm 1 domain mới (BĐS) ngoài Product/Organization — tăng tải công việc | Trung bình | Không có giải pháp kỹ thuật ở v1 — nếu tải quá lớn, cân nhắc thêm role duyệt riêng cho BĐS ở phase sau (đổi `RealEstateListingPolicy::approve()` sang permission riêng, không ảnh hưởng Product) |
| Đơn vị tiền đổi từ "tỷ" (WP) sang VNĐ đầy đủ — sai lệch khi người dùng quen nhập theo tỷ | Thấp | Giao diện nhập vẫn có thể hiển thị input dạng "tỷ" (step 0.1) và convert sang VNĐ khi lưu — chỉ đổi ở tầng LƯU TRỮ, không bắt buộc đổi UX nhập liệu |
