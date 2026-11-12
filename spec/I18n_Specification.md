# Đặc tả kỹ thuật — Phân hệ Ngôn ngữ (i18n)

Trạng thái: Giai đoạn 0, 1, 2 đã triển khai (Giai đoạn 2: bóc tách xong, `lang/en.json` đã dịch đủ — `i18n:missing` báo 0 thiếu/0 chưa dịch). Giai đoạn 3 chưa làm.

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

## 4. Giai đoạn 2 — bóc tách chuỗi giao diện

### 4.1 Nguồn dữ liệu ngôn ngữ

- **Key = câu tiếng Việt gốc.** `lang/en.json` là bản dịch tiếng Anh duy nhất cho chuỗi giao diện. Tiếng Việt không cần file riêng — key chưa có bản dịch tự hiện câu gốc.
- `lang/vi.json` chỉ chứa bản dịch Việt của chuỗi framework tiếng Anh (laravel-lang), **không** thêm key tiếng Việt vào đó.
- `lang/{vi,en}/*.php` chỉ dùng cho nhóm của framework (`validation`, `auth`, `pagination`, `passwords`).

### 4.2 Quy ước viết code

| Nơi | Cách viết |
|---|---|
| Blade — chữ | `{{ __('Lưu cài đặt') }}`; có biến → `{{ __('Đã xoá :name', ['name' => $x]) }}` (không cắt câu thành nhiều mảnh) |
| Blade — thuộc tính | `placeholder="{{ __('…') }}"`; trên component `<x-…>` dùng `:title="__('…')"` |
| Blade — Alpine / `<script>` | `@js(__('…'))`; template literal có biến → `` `${@js(__('Còn :n ngày')).replace(':n', () => n)}` `` |
| PHP (controller, action, request, notification, exception) | `__('…')`, tham số dùng `:placeholder` |
| Enum | `label()` trả `__('…')` |
| Hằng số mảng (PHP không cho gọi `__()` trong `const`) | giữ câu gốc trong hằng, dịch tại chỗ hiển thị (`__(self::LABELS[$k])`, `array_map('__', X::LABELS)`) và **khai báo hằng trong `config/i18n.php` → `translatable_constants`** để lệnh quét nhận ra |
| Nhãn nằm trong file config (`config/breadcrumbs.php`, `approval.subjects.*.label`, `prompt_framework_studio.frameworks…`) | giữ câu gốc trong config, dịch tại chỗ hiển thị (`__($config['label'])`) và **khai báo đường dẫn (cho phép `*`) trong `config/i18n.php` → `translatable_config`**. Config vừa hiển thị vừa ghép vào prompt AI (Prompt Studio) thì chỉ dịch bản hiển thị — `LocalizeFrameworksForDisplayAction` thêm khoá `ui` cho view, prompt vẫn dùng bản gốc |
| File JS (`resources/js`, `Modules/*/resources/assets/js`) | `import { t } from '…/shared/i18n.js'` rồi `t('…')` / `t('Tối đa :max ký tự', { max })` |

Không dịch: prompt gửi cho AI (CoreIdeaExtractor, VideoIdeaExtractor, các `Build*PromptAction`, `ProductFormulaCatalog`, `PersonaCatalog`, phần `lines.push` trong `prompt-framework-studio.js`), từ khoá dùng để phân tích nội dung (`ExtractRawContentAction`), log kỹ thuật, dữ liệu seed.

### 4.3 JS — `window.TRANSLATIONS`

`layouts.partials.i18n-script` (đặt trước `@vite` trong mọi layout) xuất `window.APP_LOCALE` và `window.TRANSLATIONS`. `TRANSLATIONS` **không** phải toàn bộ `en.json`: `App\Support\I18n\JsTranslations` chỉ lấy các key được gọi bằng `t()` trong file JS và đã có bản dịch, cache theo `locale + filemtime(en.json)`. Locale `vi` → `{}`.

### 4.4 Lệnh `i18n:missing`

```bash
php artisan i18n:missing              # báo key thiếu trong lang/en.json, exit 1 nếu thiếu (dùng cho CI)
php artisan i18n:missing --write      # thêm key thiếu, giá trị tạm = câu tiếng Việt (chờ team Content dịch)
php artisan i18n:missing --untranslated   # liệt kê key chưa dịch (giá trị vẫn là tiếng Việt)
php artisan i18n:missing --unused --prune # liệt kê / xoá key không còn dùng
```

Quét `__()`, `trans()`, `trans_choice()`, `@lang`, `t()` trong `config('i18n.scan_paths' | 'js_paths')` các hằng trong `translatable_constants` và các đường dẫn config trong `translatable_config`. Chạy `--write` trước khi merge; việc dịch giá trị trong `en.json` do team Content/BA làm.

### 4.5 Còn lại sau Giai đoạn 2

- Chuỗi tiếng Việt **không dấu** (VD "Trang", "Tin") không bị công cụ tự động bắt — rà tay khi gặp.
- Mô tả activity log lưu sẵn chuỗi lúc ghi; trang log dịch lúc hiển thị bằng `__($description)` nên chỉ dịch được mô tả tĩnh.
- Thông báo database (notification `toArray`) lưu chuỗi theo locale lúc gửi.

## 5. Giai đoạn 3 — dữ liệu danh mục song ngữ (chưa làm)

`spatie/laravel-translatable` cho category, menu, banner, page, OCOP; bỏ cột lẻ kiểu `name_en`. `post.default_locale` chuyển sang đọc theo UI locale, có fallback `main_locale`. Cân nhắc route có tiền tố locale + `hreflang`.
