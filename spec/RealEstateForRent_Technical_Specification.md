# Real Estate — Bất động sản THUÊ (`Modules/RealEstate`)

**Đặc tả Kỹ thuật Chi tiết — Sẵn sàng Triển khai**

**Phiên bản:** 1.3
**Ngày:** 24/07/2026
**Vị trí:** **CÙNG module** `Modules/RealEstate` với biến thể "bán" — spec này là bản **song sinh, rút gọn** của `spec/RealEstateForSale_Technical_Specification.md` (đọc file đó trước — mọi quyết định kiến trúc nền: bảng, model, RBAC, workflow duyệt, gallery ảnh, Meilisearch đều dùng CHUNG, không lặp lại ở đây). Tài liệu này chỉ mô tả phần **khác biệt**: field gốc riêng của `property-for-rent`, route public riêng, và các quyết định chỉ áp dụng cho `listing_type=rent`.
**Nguồn gốc yêu cầu:** `spec/PropertyForRentMetabox.md` (custom post type WordPress `property-for-rent`)

> **Lịch sử phiên bản**
> - **v1.0** — port `PropertyForRentMetabox.md` sang `Modules/RealEstate`, dùng CHUNG bảng `real_estate_listings`/model `RealEstateListing`/RBAC/workflow duyệt đã định nghĩa ở spec song sinh (Bán), chỉ khác `listing_type=rent` + tập field hẹp hơn nhiều (11 field).
> - **v1.1** — review tồn đọng (§0.1): vá 3 lỗ hổng chung với spec Bán (validation yếu, khu vực để trống được, không có "giá thoả thuận" — đã sửa ở `RealEstateForSale_Technical_Specification.md` §0/§3.2/§5.4, áp dụng chung vì cùng 1 bảng), xác nhận 2 điểm đã tự động đúng chuẩn Laravel (không có hook lưu ảnh trùng lặp; điều kiện hiển thị dùng Alpine + validate server thay JS jQuery dễ vỡ), và bổ sung 4 field còn thiếu so với thị trường thực tế (Hướng, Pháp lý, Tình trạng, Phí quản lý).
> - **v1.2** — **bỏ hẳn cột JSON `attributes`** (yêu cầu trực tiếp: không lưu trữ dữ liệu dạng JSON, đồng bộ với spec Bán §0 v1.2) — 4 field bổ sung ở v1.1 (`direction`, `legal_status`, `usage_status`, `management_fee`) chuyển thành **cột SQL thật** trên bảng chung `real_estate_listings` (`direction`/`legal_status`/`usage_status` vốn đã là cột thật dùng chung với Bán từ v1.2 của spec Bán; `management_fee` là cột mới, xem §2.2).
> - **v1.3** — review lần 3 (tổng hợp chung Sale+Rent) — xác nhận lại 6 điểm đều đã xử lý (không phát hiện mới); làm rõ thêm: `province_code`/`ward_code` (dòng "Địa chỉ..." ở §0.1) khớp ĐÚNG cấu trúc hành chính 2 CẤP hiện hành (Tỉnh/Thành → Phường/Xã/Đặc khu, đã bỏ cấp huyện sau sáp nhập 2025, xem chi tiết ở spec Bán §2.5) — không thiếu field "quận/huyện" nào.

---

## 0. Quyết định đã chốt (chỉ phần khác biệt so với spec Bán)

| Chủ đề | Hiện trạng codebase | Quyết định spec này | Lý do |
|---|---|---|---|
| **Có cần bảng/model riêng cho Thuê không?** | Xem §0 spec Bán ("1 bảng hay 2 bảng") | **KHÔNG** — dùng đúng `real_estate_listings` + `RealEstateListing`, `listing_type=rent` | Field Thuê (kể cả 4 field bổ sung ở v1.1) vẫn map hết vào cột đã có sẵn trên bảng chung — không phát sinh nhu cầu bảng riêng |
| **`property_type` cho Thuê** | `PropertyForRentMetabox.md` dòng 25-30: `house`/`apartment`/`layout` (KHÔNG có `land`, thay bằng "Mặt bằng") | Dùng chung enum `PropertyType` (đã định nghĩa ở spec Bán §4.2), `layout` chỉ hợp lệ khi `listing_type=rent` | `PropertyType::validFor(ListingType::Rent)` trả `[house, apartment, layout]` — validate ở Request, không cần enum riêng |
| **Field Thuê có cần thêm cột nào ngoài 11 field gốc không?** *(đổi so với v1.0)* | v1.0 kết luận KHÔNG cần thêm gì vì 11 field gốc map hết vào cột chung. Review §0.1 dòng 4 chỉ ra 4 field THỊ TRƯỜNG THỰC TẾ cần mà bản gốc WordPress không có: Hướng, Pháp lý, Tình trạng sử dụng, Phí quản lý | **CÓ** — 4 field mới (chỉ áp dụng khi `property_type=apartment`, xem §2.2), dùng ĐÚNG cột SQL thật đã có sẵn trên bảng chung cho Bán (`direction`, `legal_status`, `usage_status`) + 1 cột mới `management_fee` — KHÔNG có cột/bảng JSON nào (§0 v1.2) | Đây là field NGOÀI phạm vi 2 file WordPress gốc (không phải lỗi port thiếu) — nhưng cần thiết để tin thuê cạnh tranh được với các trang rao vặt BĐS thực tế (Hướng/Pháp lý/Phí quản lý là 3 tiêu chí người thuê thường hỏi đầu tiên). Tái dùng CỘT có sẵn từ Bán — không tạo cột trùng, không tăng độ phức tạp kiến trúc |
| **URL công khai riêng** | Xem §0/§7.1 spec Bán (`/nha-dat-ban`) | `/nha-dat-thue` (danh sách) + `/nha-dat-thue/{slug}-r{id}.html` (chi tiết) — CÙNG controller `PublicRealEstateController`, chỉ khác action (`rentIndex`/`rentShow`) và filter `listing_type=rent` | 2 URL riêng biệt đúng nhu cầu UX (menu "Nhà đất bán" / "Nhà đất thuê" tách biệt cho người dùng cuối), dùng lại 100% Query/Handler đã viết ở spec Bán (chỉ đổi tham số `listingType`) |

### 0.1 Đánh giá tồn đọng & cải thiện (review, quy về chuẩn Laravel — KHÔNG dùng khái niệm WordPress)

| Vấn đề (review gốc) | Mức độ | Đánh giá / Xử lý trong bản Laravel này |
|---|---|---|
| **Validation còn yếu** — bản WordPress chỉ `required` đúng 1 field (`rent_property_type`) | Trung bình | **Đã vá ở spec Bán §5.4** (dùng chung `StoreRealEstateListingRequest`/`UpdateRealEstateListingRequest` cho cả 2 loại) — bắt buộc thêm `listing_type`, `property_type`, `province_code`/`ward_code`, `area`, và giá (`price` hoặc `monthly_rent`) TRỪ KHI `is_price_negotiable=true`. Riêng Thuê: `rental_period_months` giữ `min:3` đúng field gốc, KHÔNG bắt buộc `deposit` (nhiều tin ghi "thoả thuận") |
| **"Hook lưu ảnh thủ công" có thể thừa** — bản WordPress tự `update_post_meta('rent_property_images', ...)` trong `rwmb_after_save_post` (dòng 164-176 `PropertyForRentMetabox.md`) song song với cơ chế tự lưu `image_advanced` của Meta Box plugin — 2 nguồn ghi cùng 1 dữ liệu | Trung bình → **Không áp dụng ở Laravel** | Kiến trúc Laravel (spec Bán §4.4) chỉ có **1 nguồn ghi ảnh duy nhất**: Spatie Media Library qua `MediaUploadService`, gọi từ Action lúc tạo/sửa tin — không có bước "tự lưu lại mảng ID ảnh" nào khác, không có rủi ro 2 nguồn lệch nhau. Đây là vấn đề đặc thù kiến trúc plugin WordPress (Meta Box tự có cơ chế lưu riêng, code tuỳ biến dễ vô tình lưu trùng) — không tồn tại trong thiết kế Laravel này |
| **Địa chỉ chỉ 1 field text, cần bổ sung field cấu trúc hoá** — nên tách Tỉnh/Phường-Xã để filter/search tốt hơn | Cao | **Đã ĐỦ 3 field từ v1.0/v1.1** (không phải lỗi cần vá): `address_detail` (số nhà/đường, tự do) + `province_code` + `ward_code` (FK có cấu trúc, dùng `x-address-picker`) — xem §2.1 dòng `rent_address_detail`. **v1.1 siết thêm**: `province_code`/`ward_code` đổi thành BẮT BUỘC (spec Bán §0/§3.2, áp dụng chung cho Thuê vì cùng bảng). **v1.3 làm rõ thêm**: 2 field tỉnh/phường này khớp ĐÚNG cấu trúc hành chính 2 CẤP hiện hành (Tỉnh/Thành → Phường/Xã/Đặc khu, bỏ cấp huyện sau sáp nhập 2025 — chi tiết `wards` FK trực tiếp `provinces` ở spec Bán §2.5) — KHÔNG thiếu field "quận/huyện" nào, vì cấp đó không còn tồn tại trong thực tế |
| **Thiếu field quan trọng** — nên bổ sung Hướng, Pháp lý, Tình trạng, Phí quản lý | Trung bình – Cao | **Đã bổ sung** — xem dòng "Field Thuê có cần thêm cột nào..." ở trên + mapping đầy đủ tại §2.2. Chỉ áp dụng khi `property_type=apartment` (nhà riêng/mặt bằng cho thuê ít khi cần "hướng ban công"/"phí quản lý chung cư") |
| **JS conditional dễ vỡ** — nên chuyển sang "conditional native" của Meta Box nếu có thể | Thấp – Trung bình | **Đã tốt hơn đề xuất gốc, không cần đổi thêm**: `toggleRentFields()` gốc (dòng 121-153) là jQuery chọn DOM thủ công (`$('#rent_interior_status, ...').closest('.rwmb-field').hide()`) — dễ vỡ vì phụ thuộc cấu trúc HTML render ra. Bản Laravel dùng Alpine `x-show` binding trực tiếp vào state reactive (không dò DOM) CHO UX, cộng với **validate bắt buộc ở server** (`required_if` theo `property_type`, §3.1) làm nguồn sự thật — ngay cả "conditional native" của Meta Box (option `visible` trong field config, xem field `urgent_days` ở `PropertyForSaleMetabox.md` dòng 38) cũng CHỈ là ẩn/hiện client-side, không tự validate server — nên hướng Laravel ở đây (Alpine + Request rule) đã vượt qua đề xuất gốc, không cần làm thêm gì |
| **Không có "Giá thoả thuận"** — nhiều tin thuê dùng "Thoả thuận" thay vì số cố định | Thấp | **Đã vá ở spec Bán §0/§4.1** (áp dụng chung, không riêng Thuê): cột `is_price_negotiable` (boolean) — khi `true`, `monthly_rent` được phép `null` mà không vi phạm rule "giá bắt buộc" ở trên; `getDisplayPriceAttribute()` trả `"Thoả thuận"` |

### 0.2 Review lần 3 (tổng hợp chung Sale + Rent) — v1.3

Không có phát hiện mới về kiến trúc cho riêng Thuê — 2 điểm đáng nói đều đã xử lý ở nơi khác:
- **"Naming không nhất quán — thống nhất prefix `sale_`/`rent_` hoặc chiến lược chung"**: bản Laravel chọn nhánh "chiến lược chung" — KHÔNG cột nào có prefix `sale_`/`rent_`, phân biệt hoàn toàn bằng `listing_type` (xem spec Bán §0.1/§0.2) — Thuê không có gì cần đổi riêng.
- **"Địa chỉ cần bổ sung field cấu trúc hoá"**: đã cập nhật dòng tương ứng ở §0.1 trên — xác nhận đã ĐỦ, không thiếu.

---

## 1. Giới thiệu

Port "Thông tin chi tiết nhà đất thuê" (`spec/PropertyForRentMetabox.md`) — 11 field gốc + 4 field bổ sung (§0.1), ít nested-conditional hơn hẳn bản Bán (`jsFallback()` gốc chỉ rẽ nhánh theo 1 field `rent_property_type`, không có tầng thứ 2 như `usage_status` bên Bán). Tái dùng 100% hạ tầng đã thiết kế ở spec Bán (§1-§8 của file đó) — xem file đó cho: kiến trúc bảng, model, enum, RBAC, permission, workflow duyệt, gallery ảnh, Meilisearch, kế hoạch triển khai tổng thể. **Toàn bộ field, kể cả field bổ sung ngoài phạm vi WordPress gốc, là cột SQL thật — không có cột JSON nào trên `real_estate_listings` (§0 v1.2).**

---

## 2. Mapping field → cột SQL

### 2.1 Field gốc (`PropertyForRentMetabox.md`) — map vào cột SQL dùng chung với Bán

| Field gốc WP (`id`) | Đích Laravel | Ghi chú |
|---|---|---|
| `rent_address_detail` | cột `address_detail` | Cột dùng chung với Bán |
| `rent_property_type` | cột `property_type` | `house`/`apartment`/`layout` |
| `rent_usable_area` | cột `area` | Diện tích chính — cùng cột dùng để lọc với Bán |
| `rent_rental_period` | cột `rental_period_months` | Min 3 (validate ở Request, đúng `'min' => 3` gốc dòng 44) |
| `rent_monthly_rent` | cột `monthly_rent` | Giá thuê CHÍNH — khác cột `current_rental_income` (field phụ bên Bán, xem §0 spec Bán). Bắt buộc TRỪ KHI `is_price_negotiable=true` (§0.1, §3.2) |
| `rent_deposit` | cột `deposit` | Không bắt buộc (§0.1) |
| `rent_interior_status` | cột `interior_status` | Cột dùng chung với Bán |
| `rent_floors` | cột `floors` | Ẩn khi `property_type=apartment` (giống JS gốc dòng 136-140, chỉ validate ở Request — §3 dưới) |
| `rent_bedrooms` | cột `bedrooms` | |
| `rent_bathrooms` | cột `bathrooms` | |
| `rent_property_images` (max 6) | Media Library collection `real_estate_gallery` | Collection DÙNG CHUNG với Bán — 1 tin chỉ gắn ảnh của chính nó qua `model_id`, không đụng tin khác. 1 nguồn ghi duy nhất — §0.1 |

### 2.2 Field bổ sung — cột SQL thật, chỉ khi `property_type=apartment` (§0.1)

| Cột SQL (đã có trên bảng chung, migration spec Bán §3.2) | Cast enum (tái dùng từ spec Bán §4.2) | Options |
|---|---|---|
| `direction` | `CompassDirection` | 8 hướng — CHÍNH cột đã dùng cho house/apartment/land bên Bán, KHÔNG tạo cột mới |
| `legal_status` | `LegalStatus` | Với căn hộ cho thuê, options thực tế thường chỉ `so_hong_rieng`/`hop_dong`/`dang_cho_cap_so` (đúng tập options `legal_apartment` bên Bán, §4.2 spec Bán) — validate ở Request giới hạn subset này khi `listing_type=rent` |
| `usage_status` | `UsageStatus` | Với thuê, ý nghĩa hơi khác Bán: `dang_o` = chủ đang ở tới hạn trả nhà, `nha_trong` = trống sẵn sàng vào ở ngay, KHÔNG có lựa chọn `dang_cho_thue` (vô nghĩa với tin thuê) — Request giới hạn option còn lại `[dang_o, nha_trong]` khi `listing_type=rent` |
| `management_fee` (**cột MỚI** — thêm vào migration §3.2 spec Bán) | — (không có enum, decimal) | VNĐ/tháng — phí quản lý/dịch vụ chung cư, tách riêng khỏi `monthly_rent` (giá thuê) vì đây là 2 khoản thu khác nhau trên thực tế (chủ nhà vs. ban quản lý thu). Khả dụng cho CẢ apartment-sale nếu cần (người mua căn hộ cũng thường hỏi phí quản lý) |

Model bổ sung helper (đặt cạnh `getDisplayPriceAttribute()`, spec Bán §4.1):
```php
/** Tổng chi phí/tháng thực tế — giá thuê + phí quản lý (nếu có). Chỉ có nghĩa khi isRent(). */
public function getTotalMonthlyCostAttribute(): ?float
{
    if (! $this->isRent() || $this->monthly_rent === null) {
        return null;
    }

    return (float) $this->monthly_rent + (float) ($this->management_fee ?? 0);
}
```

---

## 3. Business rules riêng cho Thuê

### 3.1 Ẩn/hiện field theo `property_type` (thay `toggleRentFields()` gốc, dòng 121-145 `PropertyForRentMetabox.md`)

| `property_type` | Field hiện (validate `required_if` ở Request) |
|---|---|
| `house` | `interior_status`, `floors`, `bedrooms`, `bathrooms` |
| `apartment` | `interior_status`, `bedrooms`, `bathrooms` (KHÔNG `floors` — đúng comment gốc dòng 137 "không có số tầng") + 4 cột bổ sung `direction`/`legal_status`/`usage_status`/`management_fee` (§2.2) |
| `layout` (mặt bằng) | Không field nào trong 2 nhóm trên — chỉ giữ 4 field cơ bản (địa chỉ, diện tích, thời hạn thuê, giá thuê) đúng comment gốc dòng 141 |

### 3.2 Validation bắt buộc (§0.1 — dùng chung rule đã định nghĩa ở spec Bán §5.4)

`province_code`/`ward_code`/`area` bắt buộc; `monthly_rent` bắt buộc TRỪ KHI `is_price_negotiable=true`; `rental_period_months` giữ `min:3` (đúng field gốc); `deposit` KHÔNG bắt buộc. Xem đầy đủ bảng rule tại spec Bán §5.4 — không định nghĩa lại ở đây vì dùng chung 1 Form Request cho cả 2 loại (`listing_type` là 1 field trong cùng Request, rule khác nhau theo giá trị đó).

### 3.3 Điều kiện hiển thị công khai

`RealEstateListing::scopePubliclyVisible()` (kế thừa `HasApproval`, xem spec Bán §2.3) + filter `listing_type = 'rent'`.

---

## 4. Render công khai

### 4.1 Routes — bổ sung vào `Modules/RealEstate/routes/web.php` (cùng file đã khai báo route Bán, spec Bán §7.1)

```php
Route::get('nha-dat-thue', [PublicRealEstateController::class, 'rentIndex'])->name('real-estate.public.rent.index');
Route::get('nha-dat-thue/{slug}-r{id}.html', [PublicRealEstateController::class, 'rentShow'])
    ->where(['slug' => '[a-z0-9\-]+', 'id' => '[0-9]+'])->name('real-estate.public.rent.show');
```

`PublicRealEstateController::rentIndex()`/`rentShow()` gọi ĐÚNG Query/Handler đã viết cho Bán (spec Bán §7.1-§7.3), chỉ truyền `listingType: ListingType::Rent` — không viết Handler riêng.

### 4.2 Trang danh sách `/nha-dat-thue`

Cùng bố cục lưới card như `/nha-dat-ban` (spec Bán §7.2), đổi hiển thị giá thành `X đ/tháng` hoặc `"Thoả thuận"` (`getDisplayPriceAttribute()`, §0.1) + hiển thị thêm `rental_period_months`/`deposit` trên card (2 field không có ở Bán). Filter query params: `property_type` (options `house/apartment/layout`), `province_code` (bắt buộc chọn 1 khu vực), `monthly_rent_min/max`, `bedrooms` — tất cả filter được TRỰC TIẾP ở SQL/Meilisearch vì mọi field đều là cột thật (§0 v1.2, không còn hạn chế "field trong JSON không filter được" của bản v1.1).

### 4.3 Trang chi tiết `/nha-dat-thue/{slug}-r{id}.html`

Cùng bố cục spec Bán §7.3: cột chuẩn (địa chỉ, diện tích, phòng ngủ/tắm, tầng, nội thất, giá thuê, đặt cọc, thời hạn thuê) + **nếu `property_type=apartment`**, thêm khối "Thông tin căn hộ" đọc trực tiếp từ 4 cột §2.2 (Hướng, Pháp lý, Tình trạng, Phí quản lý) + hiển thị `total_monthly_cost` (giá thuê + phí quản lý) làm số nổi bật nếu có phí quản lý. 404 nếu không `publiclyVisible()` hoặc `listing_type != rent`.

---

## 5. Kế hoạch triển khai

Toàn bộ hạ tầng (migration, model, RBAC, Approval, gallery, Scout) đã triển khai theo spec Bán §8 bước 1-3 — KHÔNG lặp lại. Chỉ cần thêm riêng cho Thuê:

1. `RentListingRequest` (Store/Update) — validate §3.1/§3.2 (`required_if` theo `property_type`, `rental_period_months` min 3, 4 cột bổ sung §2.2 chỉ `required_if:property_type,apartment`).
2. Routes `nha-dat-thue*` (§4.1) + 2 action `rentIndex`/`rentShow` trên `PublicRealEstateController` đã có (thêm method, không tạo controller mới).
3. Views public `/nha-dat-thue` (tái dùng component Blade đã viết cho Bán, đổi label giá + field hiển thị theo §4.2/§4.3, thêm khối "Thông tin căn hộ" §2.2).
4. Form admin tạo/sửa tin thuê — tái dùng layout form Bán, ẩn hết field chỉ-Bán (giá bán/pháp lý-bán riêng biệt/hướng-nhà-riêng/subtype), hiện đúng 11 field §2.1 + 4 cột §2.2 (Alpine `x-show="property_type === 'apartment'"`) + logic §3.1.
5. Test: tạo tin `listing_type=rent`, đổi `property_type=apartment` → `floors` bị ẩn/set `NULL`, 4 cột bổ sung HIỆN + lưu đúng giá trị; đổi sang `layout` → 4 cột bổ sung tự set về `NULL` (đúng cơ chế Action đã định nghĩa ở spec Bán §5.3, không phải "loại khỏi JSON" như bản v1.1); `rental_period_months < 3` → lỗi validate; để trống `monthly_rent` mà KHÔNG tick `is_price_negotiable` → lỗi validate; tin xuất hiện `/nha-dat-thue`, KHÔNG xuất hiện `/nha-dat-ban` và ngược lại (kiểm tra chéo với tin Bán); filter `/nha-dat-thue?direction=dong_nam` trả đúng kết quả (xác nhận cột SQL filter được, không phải JSON).

---

## 6. Ngoài phạm vi (v1)

Giống spec Bán §9 — không lặp lại. Riêng cho Thuê: không có tính năng "gia hạn hợp đồng thuê tự động", không có nhắc lịch thanh toán tiền thuê hàng tháng (đây là công cụ đăng TIN, không phải hệ thống quản lý hợp đồng thuê). `management_fee` (§2.2) chỉ 1 số cố định/tháng — không hỗ trợ biểu phí theo m²/theo dịch vụ chi tiết.

---

## 7. Rủi ro riêng cho Thuê

| Rủi ro | Mức độ | Đánh giá |
|---|---|---|
| `rental_period_months` tối thiểu 3 nhưng không có trần tối đa | Thấp | Đúng field gốc (WP chỉ có `'min' => 3`, không có max, dòng 44 `PropertyForRentMetabox.md`) — giữ nguyên hành vi gốc |
| Nhầm lẫn `monthly_rent` (cột chính, Thuê) với `current_rental_income` (cột riêng, chỉ Bán) khi đọc code | Thấp | Đã tách tên cột rõ ràng ở spec Bán §0 — mọi query/view PHẢI dùng đúng cột theo `listing_type`, không có cột nào tên giống nhau giữa 2 khái niệm |
| `management_fee` tách khỏi `monthly_rent` có thể gây hiểu nhầm "giá rẻ" nếu UI không hiển thị rõ tổng chi phí | Trung bình | `getTotalMonthlyCostAttribute()` (§2.2) PHẢI hiển thị nổi bật cạnh giá thuê ở cả card danh sách và trang chi tiết (§4.2/§4.3) — không chỉ hiện phí quản lý ẩn trong chi tiết, tránh tin "giá rẻ bất thường" gây hiểu nhầm khi có phí quản lý cao |
| Cột `direction`/`legal_status`/`usage_status` dùng CHUNG giữa Bán và Thuê — sửa Request validate cho Bán vô tình ảnh hưởng Thuê (hoặc ngược lại) | Thấp | Rule validate PHẢI luôn rẽ nhánh theo CẢ `listing_type` VÀ `property_type` (không chỉ `property_type`) — test plan §5 bước 5 đã bao gồm kiểm tra chéo 2 loại để phát hiện sớm nếu rule bị áp nhầm |
