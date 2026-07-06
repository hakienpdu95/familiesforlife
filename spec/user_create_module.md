**Đường dẫn dự án:** `/var/www/html/minhan`

**Module**: `Modules/User`

## CHỨC NĂNG THÊM MỚI TÀI KHOẢN & PHÂN QUYỀN (CREATE USER + ASSIGN ROLE/PERMISSION)

Module Organization đã triển khai hoàn chỉnh danh sách theo **Module List Pattern**.  
Bây giờ hãy triển khai **chức năng thêm mới tài khoản người dùng và phân quyền** theo đúng tinh thần phân tích User & Role trong tài liệu FILE 03 — USER & ROLE ANALYSIS (/var/www/html/minhan/docs/File-03-User-Role-Analysis.pdf).

### Yêu cầu đọc trước khi thực hiện:
- Tài liệu **FILE 03 — USER & ROLE ANALYSIS** (đã đính kèm ở đường dẫn /var/www/html/minhan/docs/File-03-User-Role-Analysis.pdf)
- Hai hình ảnh permission matrix (`/var/www/html/minhan/docs/per1.png` và `/var/www/html/minhan/docs/per2.png`) — đây là ma trận phân quyền chi tiết theo từng role và module.
- File `/var/www/html/minhan/docs/module-list-pattern.md` (Module List Pattern)

### Mục tiêu chính:
- Tạo form **thêm mới tài khoản người dùng** (Create User).
- Cho phép **phân quyền chi tiết** ngay khi tạo hoặc chỉnh sửa user.
- Tuân thủ **toàn bộ ma trận phân quyền** trong per1.png & per2.png (Full, Assigned, Limited, Source view, Config, Use, Monitor, Approve/View…).
- Hỗ trợ **multi-tenant**: user thuộc Organization hiện tại (hoặc admin có thể chọn organization).

### Yêu cầu kỹ thuật chi tiết:

1. **Backend (AVSA + CQRS-lite)**
   - Tạo `CreateUserData` (Spatie Laravel Data) với validation phù hợp.
   - Tạo `StoreUserAction` (Lorisleiva AsAction) — xử lý tạo user + gán role/permission.
   - Tạo `UpdateUserAction` để chỉnh sửa sau này.
   - Tích hợp **Spatie Laravel Permission + Teams** (team_id = organization_id).
   - Khi tạo user, cho phép chọn nhiều Role (theo RoleEnum) và gán Permission theo ma trận.
   - Bắn Event `UserCreated`, `UserRoleAssigned`… (không log activity trực tiếp trong Action).

2. **Frontend**
   - Form tạo user sử dụng **Tom Select** cho:
     - Chọn Organization (nếu admin)
     - Chọn Role (có thể multiple)
     - Chọn Province / Ward (nếu cần thông tin địa chỉ)
   - Giao diện phân quyền rõ ràng, trực quan (có thể dùng checkbox group hoặc card theo module như ma trận).
   - Hiển thị label giải thích cho từng mức quyền (Full, Assigned, Limited, Config…) theo per2.png.

3. **Quy tắc quan trọng**
   - Phải tuân thủ **đầy đủ Module List Pattern** đã áp dụng cho danh sách User.
   - Phân quyền phải chính xác theo ma trận trong 2 file ảnh (đặc biệt là sidebar visibility, module access, action permission).
   - User mới phải có password mặc định hoặc gửi email set password.
   - Hỗ trợ gửi email thông báo khi tạo user thành công (nếu có).
   - Code phải sạch, theo AVSA, dễ mở rộng cho các role khác nhau.

Bạn đã hiểu rõ yêu cầu triển khai chức năng **Thêm mới tài khoản + Phân quyền** theo đúng FILE 03 User & Role Analysis và permission matrix chưa?

Hãy xác nhận và tóm tắt những file chính bạn sẽ tạo/sửa trước khi bắt đầu.