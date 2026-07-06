**Đường dẫn dự án:** `/var/www/html/minhan`
**Module**: `Modules/Organization`

## NÂNG CAO CHỨC NĂNG DANH SÁCH TỔ CHỨC (ADVANCED LIST & FILTERING)

Bây giờ hãy nâng cấp lên mức **cao cấp và tối ưu nhất**.

### Yêu cầu nâng cao:

1. **Filtering & Search (Rất quan trọng)**
   - Hỗ trợ **tìm kiếm linh hoạt theo nhiều điều kiện**:
     - Tìm theo chuỗi (string) trên nhiều cột (name, short_name, province name…)
     - Lọc theo **Tỉnh/Thành phố** (`province_code`)
     - Lọc theo **Phường/Xã** (`ward_code`)
     - Lọc theo **ngày** (created_at, updated_at) - có cài "flatpickr": "^4.6.13" rồi: 
       - Hôm nay, Tuần này, Tháng này, Năm nay
       - Khoảng ngày tùy chọn (date range picker)
     - Có thể kết hợp nhiều điều kiện cùng lúc (AND)

2. **Select Fields - BẮT BUỘC**
   - Tất cả các `<select>` trong bộ lọc **phải sử dụng Tom Select** (không dùng select HTML thông thường).
   - Tom Select phải được cấu hình tối ưu: searchable, clearable, placeholder đẹp, hỗ trợ AJAX load data nếu cần (ví dụ: load danh sách tỉnh/phường động).

3. **Phân trang & Performance**
   - Phân trang server-side (Tabulator).
   - Hỗ trợ thay đổi số lượng records mỗi trang (10, 25, 50, 100…).
   - Đảm bảo hiệu suất cực tốt ngay cả khi có >500.000 records.
   - Tối ưu query backend (indexing, caching query result nếu cần).

4. **UX & Tính năng nâng cao**
   - Global search + Column filters kết hợp mượt mà.
   - Lưu trạng thái filter/pagination/sort khi refresh trang (nếu khả thi).
   - Giao diện sạch, responsive, có thể ẩn/hiện cột.
   - Dễ dàng mở rộng thêm filter mới trong tương lai mà không phải sửa nhiều code.

### Nhiệm vụ của bạn:
- Review code danh sách hiện tại.
- Nâng cấp toàn diện filtering, search, và Tom Select.
- Đảm bảo toàn bộ logic filter/search/pagination được xử lý server-side theo đúng AVSA + CQRS-lite.
- Code frontend (AlpineJS + Tabulator + Tom Select) phải sạch sẽ, tái sử dụng được cho các module khác sau này.

Bạn đã hiểu rõ yêu cầu nâng cao chức năng danh sách tổ chức với advanced filtering, Tom Select và hiệu suất cao chưa? Hãy xác nhận và tóm tắt cách bạn sẽ triển khai trước khi bắt đầu.