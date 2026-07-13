# Vướng mắc — Kiểm duyệt tập trung & Vai trò cổng thông tin tin tức

**Mục đích:** liệt kê các khó khăn/vướng mắc **hiện còn tồn tại** (chưa xử lý) khi (1) vận hành luồng kiểm duyệt tập trung (Platform Approval Gateway) cho Organization & Product, và (2) định nghĩa vai trò/phân quyền cho một cổng thông tin truyền thông/tin tức xã hội, đối chiếu với kiến trúc RBAC hiện tại của hệ thống.

**Ngày tổng hợp:** 13/07/2026
**Tài liệu liên quan:** `spec/Workflow_Approval_Technical_Specification.md`, `spec/table/table.csv`, `spec/table/table(1).csv`

---

## 1. Vướng mắc trong luồng kiểm duyệt tập trung (Platform Approval Gateway)

### 1.1 Cần rà soát thêm các trang GET khác có cùng lớp bug "TenantContext không set cho role platform-level"

- **Bối cảnh:** đã phát hiện và sửa việc `ProductAdminController::edit()` và `OrganizationController::show()/edit()` không bọc `TenantContext::runForOrganization()` khi render GET, khiến `content_moderator` (role `organization_id=null`) không thấy được badge/nút duyệt dù trang vẫn tải được.
- **Vướng mắc còn lại:** đây là 1 lớp bug có thể lặp lại ở **bất kỳ trang GET nào khác** mà các role platform-level (`content_moderator`, `content_editor`, `content_head`) cần xem dữ liệu thuộc về 1 tổ chức cụ thể — chưa rà soát/kiểm thử hết các trang khác trong hệ thống (vd trang `TranslationController` mà `content_editor`/`content_head` thao tác trên `PostArticleTranslation`) để xác nhận không còn dính bug tương tự.

---

## 2. Vướng mắc trong định nghĩa vai trò cho cổng thông tin tin tức

### 2.1 Vai trò hiện tại mang màu sắc CRM/SaaS, không phải báo chí

8 role cố định dùng chung cho mọi tổ chức khách hàng (`app/Enums/RoleEnum.php`): `ceo, sales, ops, marketing, hr, ai_operator, system_admin, viewer`. Không role nào đặt tên/khái niệm theo nghiệp vụ toà soạn (Phóng viên, Biên tập viên...). `marketing` hiện đang gánh luôn vai trò "người viết bài" (`post_article.create/edit/delete`) lẫn các việc marketing khác không liên quan bài viết — không tách bạch.

### 2.2 Vai trò kiểm duyệt bài viết bị khoá cứng ở tầng platform, không gán được cho nhân sự từng tổ chức

`content_editor`, `content_head`, `content_moderator`, `super-admin` chỉ hoạt động đúng khi tài khoản có `organization_id = null` (kiểm tra cứng trong `User::hasGlobalRole()`). Đây là lựa chọn kiến trúc có chủ đích (đội biên tập trung ương của Hà Kiên, xuyên mọi tổ chức góp nội dung), nhưng hệ quả là **không có khái niệm "biên tập viên riêng của từng tổ chức"** trong kiến trúc hiện tại.

### 2.3 `content_moderator` hiện chỉ duyệt hồ sơ Organization/Product, KHÔNG duyệt bài viết

Theo bảng đề xuất (`spec/table/table.csv`, `table(1).csv`), "Kiểm duyệt viên" phải **duyệt/từ chối bài** + **kiểm duyệt pháp lý** — tức tham gia trực tiếp vào luồng xét duyệt Post. Thực tế `content_moderator` (`Modules/Product/app/Policies/ProductPolicy.php`, `Modules/Organization/app/Policies/OrganizationPolicy.php`) chưa hề chạm tới `PostArticleTranslation`. Việc duyệt bài viết hiện hoàn toàn do `content_editor`/`content_head` đảm nhiệm, không có tầng pháp lý riêng.

### 2.4 "Chuyên mục" hiện là theo từng tổ chức, không phải danh mục dùng chung toàn nền tảng — chặn 3/10 vai trò đề xuất

Bảng đề xuất giả định biên tập viên được gán phụ trách theo **chuyên mục xuyên suốt toàn bộ nội dung** (Biên tập viên trưởng chuyên mục quản lý 1–2 chuyên mục, Biên tập viên duyệt bài "trong chuyên mục mình phụ trách"). Thực tế:

- `post_categories` có cột `organization_id` — **mỗi tổ chức có bộ chuyên mục riêng**, không dùng chung.
- Không có cột/bảng nào gán "người phụ trách" cho 1 chuyên mục.
- `Modules/Post/app/Policies/PostArticlePolicy.php` — 0 dòng nhắc tới category.

→ 2 trục scope (category theo tổ chức vs biên tập viên theo platform xuyên tổ chức) không khớp nhau. Muốn có đúng "Biên tập viên trưởng chuyên mục"/"Phó Tổng biên tập giới hạn theo lĩnh vực" như bảng đề xuất, cần xây thêm 1 tầng hạ tầng category-scoping hoàn toàn mới (bảng gán biên tập viên ↔ chuyên mục, sửa Policy để check scope). Nếu bỏ qua, 3/10 vai trò trong bảng (Phó Tổng biên tập, Biên tập viên trưởng chuyên mục, Biên tập viên bản "trong chuyên mục") sẽ không có gì khác biệt thật so với Tổng biên tập/content_editor hiện có.

### 2.5 Vai trò "Nhiếp ảnh/Video" chưa có

Cần permission riêng "chỉ upload & quản lý media" tách khỏi `post_article.create` — hiện `PermissionEnum` chưa có permission dạng này, upload media hiện gắn liền với quyền tạo/sửa bài viết.

### 2.6 "Độc giả VIP" chưa có, chưa có subscription cá nhân cho độc giả

- Nền gần nhất là `AccountType::Free` (`app/Enums/AccountType.php`) — dùng cho user đăng nhập qua social login không thuộc tổ chức nào, nhưng chưa có case `Vip` và chưa có gate nội dung trả phí nào.
- `Modules/Subscription` hiện **chỉ bán gói cho `Organization`** (mô hình B2B), **chưa hỗ trợ subscription cá nhân cho từng độc giả** (mô hình B2C). Đây là 2 mô hình kinh doanh khác nhau, cần thiết kế thêm.

---

## Câu hỏi cần quyết định trước khi lên kế hoạch code

1. Có thực sự cần phân quyền theo chuyên mục (category-scoping) ngay bây giờ, hay quy mô đội biên tập hiện tại (1–3 người) chưa cần tới độ chi tiết 4 tầng như bảng đề xuất?
2. `content_moderator` (Kiểm duyệt pháp lý) có nên tham gia duyệt bài viết Post hay giữ tách biệt hoàn toàn với Organization/Product như hiện tại?
3. Độc giả VIP có phải nhu cầu ngắn hạn (cần subscription cá nhân B2C thật) hay chỉ là placeholder cho lộ trình dài hạn?
