**Đường dẫn dự án:** `/var/www/html/minhan`

**Module**: `Modules/User`

## NÂNG CAO & TỐI ƯU CHỨC NĂNG THÊM MỚI TÀI KHOẢN & PHÂN QUYỀN (ADVANCED USER CREATION + PERMISSION SYSTEM)

Claude đã triển khai chức năng thêm mới tài khoản và phân quyền.  

Bây giờ hãy **review sâu, chuyên sâu và tối ưu toàn diện** chức năng này theo đúng tinh thần FILE 03 — USER & ROLE ANALYSIS đã đính kèm ở đường dẫn /var/www/html/minhan/docs/File-03-User-Role-Analysis.pdf + permission matrix (/var/www/html/minhan/docs/per1.png & /var/www/html/minhan/docs/per2.png).

### Yêu cầu review & cải tiến:

1. **Review Code Hiện Tại**
   - Đánh giá toàn bộ code Create User + Assign Role/Permission đã làm.
   - Chỉ ra những điểm mạnh và những **nhược điểm tiềm ẩn** (security, UX, maintainability, scalability, edge cases…).

2. **Tối ưu UX & Workflow**
   - Đề xuất cải tiến form tạo user để **thân thiện và chuyên nghiệp** hơn (wizard, preview quyền trước khi lưu, role template/preset…).
   - Làm cho việc phân quyền trở nên **linh hoạt và dễ sử dụng** (không chỉ checkbox thô, mà có thể dùng template role + chỉnh sửa chi tiết).

3. **Security & Best Practices (Rất quan trọng)**
   - Password policy, email verification, account lock, 2FA option.
   - Audit log cho mọi thay đổi role/permission.
   - Ngăn chặn xung đột quyền (ví dụ: user không được tự nâng quyền của mình).

4. **Linh hoạt & Scalability**
   - Thiết kế **Role Template System** (ví dụ: preset “Sales Full”, “Ops Limited”, “AI Operator Config”…) để admin chọn nhanh.
   - Hỗ trợ **dynamic permission** theo module mới sau này mà không phải sửa code nhiều.
   - Tích hợp tốt với multi-tenant (assign user vào organization + scoped permission).

5. **Edge Cases & Reliability**
   - Xử lý duplicate email, user đã tồn tại trong organization khác, conflict role.
   - Error handling, validation message rõ ràng.
   - Event-driven (UserCreated, UserRoleChanged…) để dễ mở rộng sau này.

6. **Reusability**
   - Làm cho component form + permission UI có thể tái sử dụng cho chức năng Edit User và các module khác sau này.

### Nhiệm vụ của bạn:
- Review kỹ code hiện tại.
- Đưa ra **các ý tưởng cải tiến tốt hơn**, giải pháp tối ưu, và code mẫu nếu cần.
- Triển khai các cải tiến để chức năng này trở nên **chuyên nghiệp, linh hoạt và production-ready** nhất có thể.

Bạn đã hiểu rõ yêu cầu **nâng cao & tối ưu sâu** chức năng Thêm mới tài khoản & Phân quyền chưa?  

Hãy xác nhận và tóm tắt những điểm chính bạn sẽ review + cải tiến trước khi bắt đầu.