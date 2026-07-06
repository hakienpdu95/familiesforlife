# KC Module — Danh sách tính năng còn thiếu (theo thứ tự triển khai)

> Cập nhật: 2026-06-03  
> Dựa trên đặc tả: `spec/kc.md` v2.0.0  
> Trạng thái: `[ ]` chưa làm · `[x]` hoàn thành · `[~]` đang làm

---

## Phase 1 — MVP (ưu tiên cao nhất)

### 1.1 Tag Management
**Mục đích:** Cho phép tạo/sửa/xóa tag của org, gắn tag vào tài liệu.  
**Liên quan:** `kc_tags`, `kc_item_tags` (tables + models đã có sẵn).

- [x] `StoreKcTagAction` / `UpdateKcTagAction` / `DestroyKcTagAction`
- [x] `KcTagController` — CRUD routes dưới `dashboard/kc-tags`
- [x] API endpoint `GET backend/api/kc-tags` (for TomSelect trong form KcItem)
- [x] UI: trang danh sách tag (index + inline edit/delete)
- [x] UI: thêm tag picker vào form create/edit KcItem (TomSelect multi, tạo tag mới inline)
- [x] Sidebar: thêm mục "Tags KC" dưới section "Kho tri thức"
- [x] Filter tag trong `ListKcItemsQuery` + filter bar trang index KcItem

### 1.2 Attachment Upload
**Mục đích:** Đính kèm nhiều file vào tài liệu (PDF, DOCX, video...).  
**Liên quan:** `kc_item_attachments` (table + model đã có sẵn).

- [x] Config storage: `config/kc.php` — max file size (50MB), allowed types, storage driver
- [x] `StoreKcAttachmentAction` — validate + upload + ghi `kc_item_attachments`
- [x] `DestroyKcAttachmentAction` — xóa file trên storage + xóa record
- [x] API routes: `POST /backend/api/kc-items/{kc_item}/attachments` + `DELETE .../attachments/{attachment}`
- [x] UI: component upload file (drag & drop hoặc button) trong form edit KcItem
- [x] UI: danh sách file đính kèm trong trang show KcItem (tên, size, download link, xóa)
- [x] Validation: max 50MB/file, max 200MB/item, whitelist mime types

---

## Phase 2 — Mở rộng

### 2.1 Rollback phiên bản
**Mục đích:** Phục hồi nội dung tài liệu về một version cũ trong `kc_version_histories`.  
**Business rule:** Rollback tạo version mới từ snapshot, không xóa lịch sử (BR-KC-003).

- [x] `RollbackKcItemAction` — đọc snapshot từ `kc_version_histories`, cập nhật `kc_items.content/title`, tạo version mới, reset status về `draft`
- [x] Route: `POST dashboard/kc-items/{kc_item}/rollback/{version_number}`
- [x] Policy: thêm ability `rollback` vào `KcItemPolicy` (chỉ owner / System_Admin / Ops)
- [x] UI: nút "Rollback về version này" trong bảng lịch sử phiên bản (trang show KcItem)
- [x] UI: trang xem nội dung version cụ thể `GET /kc-items/{id}/versions/{v}`

### 2.2 Access Control Enforcement
**Mục đích:** Áp dụng `visibility=restricted` — chỉ user được cấp quyền mới xem được.  
**Business rule:** Logic kiểm tra 6 bước theo mục 6.7 trong spec.

- [x] `KcItemAccessService` — hàm `canView(User, KcItem): bool` theo 6 bước logic
- [x] Middleware / scope: inject kiểm tra access vào `ListKcItemsHandler` (filter query theo visibility)
- [x] `KcItemController@show` — throw 403 nếu không có quyền xem
- [x] `StoreKcAccessControlAction` / `DestroyKcAccessControlAction`
- [x] API routes: `GET|POST /backend/api/kc-items/{id}/permissions` + `DELETE .../permissions/{uuid}`
- [x] UI: tab "Phân quyền" trong trang show KcItem — danh sách quyền + form cấp quyền (user/role/dept picker)

### 2.3 Feedback & Rating
**Mục đích:** User đánh giá tài liệu (1–5 sao + ghi chú + helpful vote).  
**Business rule:** Upsert — mỗi user chỉ có 1 feedback/tài liệu (BR-KC-005).

- [x] `UpsertKcFeedbackAction` — upsert record `kc_feedbacks`
- [x] API routes: `POST|PUT /backend/api/kc-items/{id}/feedback`
- [x] API route: `GET /backend/api/kc-items/{id}/feedback/summary` (avg rating, count, helpful %)
- [x] UI: widget rating/feedback ở cuối trang show KcItem (star picker + textarea + "hữu ích không?")
- [x] UI: hiển thị avg rating + số lượt đánh giá trong trang show và danh sách

### 2.4 Auto-expiry Cron
**Mục đích:** Tự động chuyển `approved → archived` khi quá `expired_date`.  
**Business rule:** Cron chạy 01:00 hàng ngày, cảnh báo trước 30 ngày cho SOP/Policy (BR-KC-004).

- [x] Artisan command: `php artisan kc:expire-items`
  - Tìm `status=approved AND expired_date <= NOW()`
  - UPDATE `status = archived`
  - (Optional) cảnh báo SOP/Policy sắp hết hạn 30 ngày
- [x] Đăng ký cron trong `routes/console.php` — `->dailyAt('01:00')`

---

## Phase 3 — Analytics & Tối ưu

### 3.1 View Tracking
**Mục đích:** Ghi log lượt xem, dedup 1 lần/user/session/24h, cập nhật `view_count` async.  
**Business rule:** BR-KC-006.

- [x] `LogKcViewAction` — insert `kc_view_logs`, dedup bằng `user_id + item_id + date`
- [x] Gọi `LogKcViewAction` trong `KcItemController@show` (dispatch job async)
- [x] Job `UpdateKcViewCountJob` — cập nhật `kc_items.view_count` từ log
- [x] Thêm `viewed_at` index (đã có trong migration)

### 3.2 Analytics Dashboard
**Mục đích:** Trang báo cáo tổng quan kho tri thức.

- [x] `KcAnalyticsController` với các action:
  - [x] `topViewed` — top 10 tài liệu xem nhiều nhất (7 ngày / 30 ngày)
  - [x] `byType` — thống kê số lượng và avg rating theo từng type
  - [x] `expiringSoon` — tài liệu `approved` có `expired_date` trong 30 ngày
  - [x] `unread` — tài liệu `approved` chưa có bất kỳ `kc_view_logs` nào
- [x] Routes: `GET backend/api/kc/analytics/top-viewed|by-type|expiring-soon|unread`
- [x] UI: trang Analytics tại `dashboard/kc/analytics` với charts (ApexCharts hoặc đơn giản dùng bảng)
- [x] Sidebar: thêm mục "Analytics KC" dưới section "Kho tri thức"

### 3.3 Notifications
**Mục đích:** Thông báo cho các bên liên quan khi có sự kiện quan trọng.

- [x] Notification khi tài liệu bị **reject** → gửi cho `owner_id` (có lý do từ chối)
- [x] Notification khi tài liệu được **approve** → gửi cho `owner_id`
- [x] Notification **sắp hết hạn** (30 ngày) → gửi cho `owner_id` (từ cron `kc:expire-items`)
- [x] Notification khi tài liệu hết hạn bị **auto-archive** → gửi cho `owner_id`
- [x] Kênh: in-app notification (database channel) hoặc email tùy config

---

## Ghi chú triển khai

- **Thứ tự ưu tiên:** 1.1 → 1.2 → 2.1 → 2.4 → 2.2 → 2.3 → 3.1 → 3.2 → 3.3
- **Pattern:** Tuân theo AVSA + CQRS-lite như các module đã có (Branch, Employee, KcCategory)
- **Storage:** Attachment dùng `local` driver trước, cấu hình S3 sau khi production
- **Access control (2.2):** Phần phức tạp nhất — cần test kỹ với nhiều combination visibility/role/dept
- **Analytics (3.2):** Chỉ dùng MySQL FULLTEXT + aggregation cho SME nhỏ (<50 người), Elasticsearch nếu scale lớn
