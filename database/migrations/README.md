# database/migrations — 3 vùng tách biệt

## vendor/
Chứa migration của package (Spatie Permission, ActivityLog, MediaLibrary, Laravel default).
**KHÔNG chỉnh sửa, KHÔNG xóa.**
Cách publish: `php artisan vendor:publish --provider="..."`

## generated/
Tự sinh bởi `php artisan migration:generate`.
**Xóa + tạo lại mỗi lần chạy lệnh.**
Nguồn dữ liệu: `render_migration_file.json`

## extensions/
Viết tay để mở rộng bảng vendor hoặc thêm migration đặc biệt.
**KHÔNG xóa bao giờ.**
Dùng `Schema::table()` (ALTER), không dùng `Schema::create()`.

---

## Quy trình dev hàng ngày

```bash
# Sửa JSON → generate + fresh DB (local)
php artisan migration:generate --fresh

# Sửa JSON → generate + fresh + seed (local)
php artisan migration:generate --fresh --seed

# Thêm cột vào bảng vendor → tạo file extensions/ → chạy migrate
php artisan make:migration add_dept_to_users_table --path=database/migrations/extensions
php artisan migrate

# Production: chỉ chạy file mới
php artisan migrate
```

## Quy tắc đặt tên extensions/
- Thêm cột : `add_{column}_to_{table}_table.php`
- Xóa cột  : `drop_{column}_from_{table}_table.php`
- Sửa cột  : `change_{column}_in_{table}_table.php`
- Data seed: `seed_{table}_initial_data.php`