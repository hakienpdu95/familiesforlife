# Đặc tả kỹ thuật — Phân hệ Ngôn ngữ (i18n)

Trạng thái: Giai đoạn 0 + 1 đã triển khai. Giai đoạn 2, 3 chưa làm.

## 1. Hai khái niệm tách biệt

| Khái niệm | Nguồn | Phạm vi |
|---|---|---|
| **UI locale** (ngôn ngữ giao diện) | `system_settings.locale` (vi \| en) | Toàn bộ chữ của admin, portal, email, notification, thông báo validate, `diffForHumans()` |
| **Content locale** (ngôn ngữ nội dung) | `post_articles.main_locale`, `post_article_translations.locale`, `config('post.default_locale')` | Nội dung bài viết, ngôn ngữ đầu ra của AI |

Đổi UI locale **không** đổi ngôn ngữ nội dung. Portal vẫn chỉ phục vụ bản dịch `post.default_locale` cho đến Giai đoạn 3.

## 2. Giai đoạn 0 — cấu hình nền

- `APP_LOCALE=vi`, `APP_FALLBACK_LOCALE=vi`, `APP_FAKER_LOCALE=vi_VN` (`config/app.php` + `.env.example`).
- `laravel-lang/common` (dev) — bản dịch `lang/{vi,en}/{auth,validation,pagination,passwords,http-statuses,actions}.php` và `lang/{vi,en}.json`. Cập nhật bằng `php artisan lang:update`.
- `lang/vi/auth.php` giữ câu chữ riêng của dự án (trước đây nằm nhầm trong `lang/en/auth.php`).
- Carbon tự đi theo `App::setLocale()` qua `LocaleUpdated` (Carbon Laravel ServiceProvider) — **không** gọi `Carbon::setLocale()` cứng ở đâu cả.
- Mọi layout dùng `<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">`; riêng `layouts.frontend` ưu tiên biến `$locale` (content locale) khi trang là nội dung bài viết.

## 3. Giai đoạn 1 — hạ tầng

### 3.1 Bảng `system_settings` (1 dòng)

Khai báo trong `render_migration_file.json`. Cột: `locale` (vi), `timezone` (Asia/Ho_Chi_Minh), `date_format` (d/m/Y), `time_format` (H:i).

`timezone` chỉ dùng khi **hiển thị** (helper format), không đổi `config('app.timezone')` — dữ liệu trong DB vẫn lưu theo múi giờ ứng dụng.

### 3.2 Model `App\Models\SystemSetting`

- `current()` — cache vĩnh viễn key `system_settings.current.v2`, giá trị cache là **mảng thuộc tính** (cache Redis đặt `serializable_classes = false`, không unserialize được object). Chưa có dòng nào hoặc bảng chưa tồn tại → `defaults()`.
- `saved`/`deleted` tự xoá cache.
- `activeLocale()` — locale hợp lệ (thuộc `LOCALES`), sai thì về `app.fallback_locale`.
- Danh sách cho phép: `LOCALES`, `DATE_FORMATS`, `TIME_FORMATS`.

### 3.3 Áp dụng locale — `App\Support\SystemLocale::apply()`

| Ngữ cảnh | Điểm gọi |
|---|---|
| HTTP `web` + `api` | `App\Http\Middleware\ApplySystemLocale` (`bootstrap/app.php`) |
| Queue job (mail, notification, job thường) | `Queue::before` trong `AppServiceProvider` — mỗi job đọc lại setting, nên worker chạy lâu vẫn nhận thay đổi |
| Artisan / scheduler | `AppServiceProvider::boot()` khi `runningInConsole()` |

### 3.4 Helper (`app/Helpers/datetime.php`, autoload `files`)

- `format_date($value)` → theo `date_format` + `timezone`.
- `format_datetime($value, bool $withSeconds = false)` → `date_format` + `time_format`.
- Trả `null` khi giá trị rỗng (thay thế trực tiếp cho `?->format(...)`).
- `system_setting()`, `app_locale()`.

Quy ước: **không** viết `->format('d/m/Y')` / `'d/m/Y H:i'` mới để hiển thị — dùng helper. Định dạng dạng khoá kỹ thuật (`Y-m-d`, `Y-m`, `d/m` cho trục biểu đồ…) không thuộc phạm vi này.

### 3.5 Trang cài đặt

`GET|PUT /dashboard/settings` (`backend.settings.index|update`), `SystemSettingController` + `UpdateSystemSettingsAction`. Gate `manageSystemSettings` = chỉ super-admin. Mục "Cài đặt hệ thống" trong nhóm sidebar "Hệ thống".

## 4. Giai đoạn 2 — bóc tách chuỗi (chưa làm)

- Key = câu tiếng Việt gốc, bản tiếng Anh trong `lang/en.json` (`__('Lưu cài đặt')`). Tiếng Việt không cần file; câu chưa dịch tự hiện tiếng Việt.
- Enum `label()` trả `__()`.
- JS: layout xuất `window.APP_I18N` + helper `t()`; không đẩy toàn bộ `en.json` xuống trình duyệt.
- Activity log lưu key + tham số, dịch lúc hiển thị.
- Lệnh `i18n:missing` (quét `__()` → báo key thiếu trong `en.json`) chạy bắt buộc trước khi merge.

## 5. Giai đoạn 3 — dữ liệu danh mục song ngữ (chưa làm)

`spatie/laravel-translatable` cho category, menu, banner, page, OCOP; bỏ cột lẻ kiểu `name_en`. `post.default_locale` chuyển sang đọc theo UI locale, có fallback `main_locale`. Cân nhắc route có tiền tố locale + `hreflang`.
