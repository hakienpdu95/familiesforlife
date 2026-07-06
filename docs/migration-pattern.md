## Mục tiêu
Phân tích các file migration hiện có và đồng bộ dữ liệu vào 2 file JSON cấu hình.

## Phạm vi quét migration
- `database/migrations/generated/` (bắt buộc)
- `Modules/*/database/migrations/` (tất cả module)
- Bỏ qua: `database/migrations/vendor/`

## Quy trình thực hiện (theo thứ tự)
1. Đọc nội dung hiện tại của `render_migration_file.json` và `render_extension_file.json`
2. Quét toàn bộ file migration trong các thư mục trên
3. Phân loại:
   - Migration tạo bảng mới (`Schema::create`) → cập nhật vào `render_migration_file.json`
   - Migration thêm/sửa cột (`Schema::table`) → cập nhật vào `render_extension_file.json`
4. Với mỗi bảng/cột chưa có trong JSON: thêm vào đúng file
5. Nếu có conflict (đã tồn tại nhưng khác cấu trúc): liệt kê ra để developer xem xét, KHÔNG tự ghi đè

## Tham khảo format JSON
Đọc file `render_migration_file.json` và `render_extension_file.json` để hiểu schema hiện tại trước khi cập nhật.

## Tham khảo logic xử lý
- `app/Console/Commands/Concerns/MigrationHelpers.php`
- `app/Console/Commands/GenerateMigration.php`

## Output mong đợi
- Cập nhật trực tiếp 2 file JSON
- In ra danh sách các bảng/cột đã được thêm mới
- In ra danh sách conflict (nếu có) để developer review