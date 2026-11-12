# Đặc tả kỹ thuật — Phân hệ Ngôn ngữ (i18n)

Trạng thái: Giai đoạn 0, 1, 2 đã triển khai (nhánh `feature/i18n-phase-2`, chờ review) (Giai đoạn 2: bóc tách xong, `lang/en.json` đã dịch đủ — `i18n:missing` báo 0 thiếu/0 chưa dịch). Giai đoạn 3: đã chốt thiết kế (§5), chưa triển khai.

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
| Hằng số chỉ có 1 phần là nhãn (VD `ProductFormulaCatalog::PRODUCTS` chứa cả kịch bản mẫu) | khai báo kèm đường dẫn: `X::class.'::PRODUCTS|*.name,*.note,*.warning'` — bộ quét chỉ lấy các trường đó |
| Cùng 1 từ tiếng Việt cần nghĩa tiếng Anh khác nhau theo ngữ cảnh (VD "Bài viết" = menu "Articles" / loại đối tượng "Article") | key có hậu tố ngữ cảnh `__('Bài viết_Single')`; `lang/en.json` dịch theo ngữ cảnh, `lang/vi.json` **bắt buộc** có `"Bài viết_Single": "Bài viết"` (thiếu thì giao diện tiếng Việt hiện nguyên hậu tố). `i18n:missing` báo thiếu và `--write` tự thêm vào `vi.json` |
| Nhãn vừa hiển thị vừa ghép vào prompt AI (catalog AI Video Studio, `AiVideoStudioProject::VIDEO_TYPES`…) | model/catalog trả bản gốc (dùng cho prompt), **view** mới bọc `__()` — không bao giờ gọi `__()` trong code dựng prompt |
| File JS (`resources/js`, `Modules/*/resources/assets/js`) | `import { t } from '…/shared/i18n.js'` rồi `t('…')` / `t('Tối đa :max ký tự', { max })` |

Không dịch: prompt gửi cho AI (CoreIdeaExtractor, VideoIdeaExtractor, các `Build*PromptAction`, nội dung `ProductFormulaCatalog`/`PersonaCatalog` khi ghép vào prompt (bản hiển thị thì dịch), kịch bản mẫu `sample_script`, phần `lines.push` trong `prompt-framework-studio.js`), từ khoá dùng để phân tích nội dung (`ExtractRawContentAction`), log kỹ thuật, dữ liệu seed.

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

- Chuỗi tiếng Việt **không dấu** (VD "Trang", "Sinh Master Prompt") không được tính là key nguồn — `i18n:missing` liệt kê riêng các key không dấu đã bọc `__()`/`t()` mà chưa có trong `en.json` (cảnh báo, không fail); thêm tay bản dịch. Chuỗi không dấu **chưa bọc** `__()` thì không công cụ nào bắt được — rà tay.
- Mô tả activity log lưu sẵn chuỗi lúc ghi; trang log dịch lúc hiển thị bằng `__($description)` nên chỉ dịch được mô tả tĩnh.
- Thông báo database (notification `toArray`) lưu chuỗi theo locale lúc gửi.

## 5. Giai đoạn 3 — portal song ngữ (3a, 3b đã triển khai)

Quyết định đã chốt (2026-11-12): portal phục vụ **song song** 2 ngôn ngữ bằng tiền tố URL + `hreflang`; bản dịch dữ liệu lưu ở **bảng `*_translations` riêng** (cùng mô hình `post_article_translations`), không dùng `spatie/laravel-translatable`.

### 5.1 Locale theo request

| Khu vực | Locale lấy từ |
|---|---|
| Portal (route gắn middleware `localized`) | Tiền tố URL: không tiền tố = `vi`, `/en/...` = `en` |
| Admin, API, email/notification, queue, console | `system_settings.locale` như Giai đoạn 1 |

`vi` là ngôn ngữ gốc, URL tiếng Việt giữ nguyên như hiện tại (không đổi URL đã index). Danh sách locale portal: `config('i18n.portal_locales')` = `['vi', 'en']`, `config('i18n.portal_default_locale')` = `'vi'`.

### 5.2 Định tuyến — không nhân đôi route

- Middleware toàn cục `StripLocalePrefix` (chạy trước khi router so khớp): request `/en/...` → bỏ `/en` khỏi path, ghi `request()->attributes['portal_locale'] = 'en'`. `/en` → `/`.
- Middleware nhóm `localized` (gắn vào các nhóm route công khai): `App::setLocale(portal_locale ?? 'vi')`, `Carbon::setLocale`, `URL::defaults` không cần.
- `LocalizedUrlGenerator` (thay `url` binding): khi sinh URL cho route có middleware `localized` và locale hiện tại ≠ `vi` → chèn `/en`. Route admin/API/asset không bị đụng. Nhờ vậy 93 lời gọi `route()` trong view công khai giữ nguyên.
- Helper `localized_url(string $locale, ?string $url = null)`: URL của trang hiện tại (hoặc `$url`) ở locale khác — dùng cho `hreflang` và nút đổi ngôn ngữ.
- Route có slug dịch được (`{category:slug}`, trang tĩnh, OCOP…): binding tra slug theo locale hiện tại, không có thì thử slug `vi` rồi 301 sang slug đúng của locale.

### 5.3 Lưu trữ bản dịch

- Bảng gốc giữ nguyên = bản `vi`. Bảng `{bảng}_translations`: `id`, `{model}_id` (FK cascade), `locale`, các cột dịch được (nullable), `timestamps`, `unique({model}_id, locale)`; cột `slug` (nếu có) `unique(locale, slug)`.
- Trait `App\Foundation\Concerns\HasTranslations`: khai báo `$translatable = ['name', 'slug', ...]`; quan hệ `translations()`, `translation(?string $locale)`; `translated('name')` trả bản theo `App::getLocale()`, rỗng thì fallback cột gốc. Eager load `translations` theo locale hiện tại để tránh N+1.
- Bỏ cột lẻ `ocop_subjects.name_en` → migration chuyển giá trị sang `ocop_subject_translations(locale='en', name)`, sau đó drop cột.

### 5.4 Phạm vi dữ liệu

| Đợt | Entity | Cột dịch |
|---|---|---|
| 3b | `PostCategory`, `Page`, `MenuItem` | name/title, slug, description/excerpt/content, SEO title/description; label menu |
| 3c | `EventCategory`, `Banner`, `OcopCategory`, `OcopProduct`, `OcopSubject` | name, slug, mô tả, câu chuyện, SEO; banner: title, alt, nhãn CTA |
| Ngoài phạm vi (giữ dữ liệu `vi`) | Heritage, Province, Event, RealEstate, Playlist, Video, Tag, AuthorProfile | — |

Trang `/en/...` của entity ngoài phạm vi vẫn render (giao diện tiếng Anh, dữ liệu tiếng Việt) kèm banner "Bản tiếng Anh chưa sẵn sàng", `noindex` và không phát `hreflang="en"` — cùng cơ chế fallback §5.5.

### 5.5 Bài viết

- `post.default_locale` không còn quyết định ngôn ngữ phục vụ: portal dùng locale theo URL.
- `/en/{slug}-d{id}.html` khi bài **chưa có** bản `en` đã xuất bản: **không redirect** (302 làm hỏng tín hiệu SEO của URL `/en/`). Giữ nguyên URL, render bản `main_locale`, hiện banner "Bản tiếng Anh chưa sẵn sàng" (kèm link sang bản gốc) và thêm `<meta name="robots" content="noindex">`; trang này không phát `hreflang="en"`. Slug sai (khác slug của bản đang render) → 301 về slug đúng, vẫn trong `/en/`.
- Cùng quy tắc cho entity dịch được ở §5.4 (chuyên mục, trang tĩnh, OCOP…): thiếu bản `en` → render bản `vi` + banner + `noindex`, không redirect.
- Danh sách (trang chủ, chuyên mục, tác giả, tải thêm, tin nóng) ở `/en` chỉ lấy bài có bản `en` đã xuất bản.

### 5.6 SEO

- `<html lang>` theo locale portal. `<link rel="alternate" hreflang>` cho mọi locale **thực sự có** nội dung của trang + `x-default` = bản `vi`. Canonical trỏ chính locale đang xem.
- Sitemap: mỗi URL kèm `xhtml:link` alternate; `llms.txt` và Markdown negotiation phục vụ theo locale của URL.
- Nút đổi ngôn ngữ ở header portal: trỏ bản tương ứng nếu có, không có thì về trang chủ của locale đó.

### 5.7 Admin

Form của entity trong phạm vi có tab theo locale (`Tiếng Việt` = cột gốc, `English` = dòng `_translations`). Lưu tab `en` rỗng toàn bộ = xoá dòng dịch. Danh sách admin hiển thị cột "EN" (có/chưa có bản dịch).

### 5.7b Hiện thực 3a

- `App\Support\I18n\PortalLocale` (scoped): locale portal, `using()` để sinh URL theo locale khác, `markTranslated()`, `setAlternates()`, `hreflangs()`, `switcherLinks()`, `localizeInternalUrl()` (URL nội bộ dạng chuỗi — menu `link_type=url`/`page`).
- Route thuộc portal khai báo bằng **mẫu tên** trong `config('i18n.portal_routes')` (trừ `portal_routes_except`: sitemap/robots/llms) — không sửa file route từng module. Route công khai mới phải có tên khớp mẫu.
- `StripPortalLocalePrefix` (middleware toàn cục) bỏ `/en` và ghi attribute; listener `RouteMatched` kích hoạt locale cho route portal, route không thuộc portal mà có tiền tố → 404 (`/en/dashboard`). `ApplySystemLocale` nhường cho locale portal khi đã kích hoạt.
- `App\Routing\LocalizedUrlGenerator` (thay binding `url`): chèn `/en` cho route portal khi đang ở `/en`; `url()->current()`/`full()` và path của paginator cũng giữ tiền tố. Ngoài portal (admin, email, queue) luôn sinh URL `vi`.
- Layout `frontend`: `<html lang>` theo locale portal, partial `portal-locale-head` (`hreflang` + `noindex`), `portal-fallback-notice` (banner), `portal-locale-switcher` (header). Mặc định mọi trang portal ở `/en` là **fallback**; controller gọi `markTranslated()` khi trang thực sự có bản `en` (hiện: trang chủ) hoặc `setAlternates()` khi slug khác nhau theo locale (bài viết).

### 5.7c Hiện thực 3b

- Bảng `post_category_translations` (name, description), `page_translations` (title, slug, excerpt, content, seo_title, seo_description — `unique(locale, slug)`), `menu_item_translations` (label). FK cascade, `unique({model}_id, locale)`.
- Trait `App\Foundation\Concerns\HasTranslations`: model khai báo `translatable()` + `translationModel()`. Override `getAttributeValue()` — **chỉ khi portal đang ở locale khác `vi`** mới trả giá trị bản dịch (rỗng thì fallback cột gốc); admin/API/console và lúc lưu luôn đọc cột gốc, nên mọi view portal đang in `$category->name`/`$page->title`/`$item->label` tự hiển thị đúng. Global scope eager-load `translations` của đúng locale (bắt buộc vì `Model::shouldBeStrict()` chặn lazy load). `originalAttribute()` lấy cột gốc khi cần (VD URL bản `vi`).
- **Lệch so với §5.4:** slug chuyên mục **không** dịch ở 3b — hàng chục nơi truyền `->slug` (chuỗi) vào `route('post.public.category')`, dịch slug sẽ thêm 1 bước 301 cho mọi link; URL `/en/danh-muc/{slug-vi}` vẫn hợp lệ. Slug trang tĩnh **có** dịch (URL gốc `/{slug}` là nội dung chính của trang).
- Admin: `<x-admin.translation-fields :model :fields>` (khối "Bản dịch English" trong form) + `App\Actions\I18n\SaveModelTranslationsAction` (`rules()` sinh rule `translations.{locale}.{field}`, `run()` lưu/xoá). Controller validate bản dịch **trước** khi gọi Action chính để không lưu dở. Slug trang `en` để trống thì tự sinh từ tiêu đề.
- Portal: trang tĩnh ở `/en` tra slug bản dịch trước, vào bằng slug `vi` khi đã có slug `en` → 301; chuyên mục/trang dùng `PortalLocale::availableIn()` để chỉ khai báo `hreflang` cho locale thực sự có bản dịch.

### 5.8 Thứ tự triển khai

1. **3a** — hạ tầng định tuyến (§5.1, §5.2), `<html lang>`, nút đổi ngôn ngữ, `hreflang` cho trang chủ/bài viết; bài viết theo §5.5.
2. **3b** — trait + bảng dịch + admin cho `PostCategory`, `Page`, `MenuItem`.
3. **3c** — `EventCategory`, `Banner`, OCOP (kèm migrate `name_en`).
4. **3d** — sitemap, `llms.txt`, Markdown negotiation, `noindex` cho trang ngoài phạm vi.
