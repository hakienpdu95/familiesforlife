# Ma trận Ý tưởng Di sản — bổ sung vào `Modules/PromptFrameworkStudio`

**Đặc tả Kỹ thuật — v2.6 (v2.0 viết lại toàn bộ sau đánh giá đối chiếu codebase; v2.1 sửa lỗi thiết kế "biến số bị đóng cứng thành hằng số" — thêm `allow_custom`, §2.5; v2.2 sửa lỗi phát sinh từ chính v2.1 — mở tự do không giới hạn độ dài, thêm `custom_max_length`, §2.6; v2.3 đưa hướng dẫn dùng đúng field (§2.7) vào trong form qua `tip`/`custom_placeholder`, §2.8; v2.4 thêm khối "Ví dụ tham khảo" toàn cảnh trên field đầu tiên, §2.9; v2.5 đổi `example` chuẩn sang thông cáo hội chợ OCOP thật theo yêu cầu người dùng, §2.10; v2.6 thêm "Trợ lý tách nội dung thô" — sinh prompt cho AI ngoài tự đề xuất giá trị field, §2.11)**

**Ngày:** 2026-08-17
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions
**Module đích:** `Modules/PromptFrameworkStudio` (KHÔNG còn là module riêng `AIIdeaMatrixGenerator`)

> **Trạng thái: ĐÃ TRIỂN KHAI (2026-08-17, gồm cả v2.1-v2.6).** Toàn bộ §2 (field `select` + nút
> Randomize + preset `heritage_idea_matrix` + validate + §3 "Dùng lại giá trị từ prompt trước" +
> §2.5 `allow_custom` + §2.6 `custom_max_length` + §2.8 `tip`/`custom_placeholder` + §2.9 khối "Ví
> dụ tham khảo" + §2.10 ví dụ chuẩn OCOP + §2.11 "Trợ lý tách nội dung thô") đã code đúng theo đặc tả.
> Đã kiểm chứng bằng: (1) `RenderPromptFromFrameworkActionTest` — 6 test pure-logic (không chạm DB)
> chạy PASS thật, gồm 3 test mới khoá lại hành vi `select`; (2) tinker end-to-end — config 27
> framework, `RenderPromptFromFrameworkAction::handle('heritage_idea_matrix', ...)` render đúng NHÃN
> (không phải khoá thô) + fallback đúng khi khoá lạ; render trang `create`/`library` qua Controller
> thật (không giả lập); route `last-prompt/{frameworkKey}` có trong route list. **Chưa kiểm chứng
> được** 4 Feature test HTTP mới (`test_store_rejects_select_value_not_in_options`,
> `test_store_succeeds_with_heritage_idea_matrix_and_renders_labels`,
> `test_last_prompt_endpoint_returns_field_values_of_most_recent_prompt`,
> `test_last_prompt_endpoint_returns_not_found_for_unknown_or_unused_framework`) — DB test (`minhan`)
> thiếu cột `users.department` dù `migrations` báo "Nothing to migrate" (lệch giữa bảng `migrations`
> và schema thật), lỗi này XÁC NHẬN tồn tại từ TRƯỚC khi đụng vào code này (`git stash` rồi chạy lại
> test cũ vẫn lỗi y hệt) và ảnh hưởng TOÀN BỘ Feature test của module (100% test tạo `User` đều lỗi,
> không riêng test mới) — không phải regression, không sửa vì đây là vấn đề hạ tầng test dùng chung,
> ngoài phạm vi việc triển khai đặc tả này. 4 test trên viết đúng theo pattern các test cùng file đã
> có, sẽ chạy được ngay khi môi trường test được đồng bộ lại schema.

> **Vì sao viết lại toàn bộ, không sửa vá bản v1.0:** bản v1.0 (module riêng, bảng DB riêng,
> Catalog class riêng) được viết KHÔNG đối chiếu codebase hiện có. Rà soát phát hiện `Modules/
> PromptFrameworkStudio` đã làm đúng — không phải tương tự — việc "ghép Hằng số cố định + Biến số
> người dùng điền → 1 Master Prompt copy-paste cho ChatGPT/Claude": cơ chế `task_instructions` (5
> preset nhóm "Chiến lược nội dung" trong `config/prompt_framework_studio.php`) + `RenderPromptFrom
> FrameworkAction` (khối field theo thứ tự canon + nhiệm vụ cố định + ngữ cảnh biên tập từ
> `ContentFoundation`) chính là bộ khung mà bản v1.0 định dựng lại từ đầu. Toàn bộ phần dưới đây viết
> lại theo hướng: dùng cơ chế đã có, chỉ bổ sung phần THẬT SỰ còn thiếu.

---

## 0. Quyết định kiến trúc

| Chủ đề | Đề xuất gốc (v1.0) | Quyết định v2.0 | Lý do |
|---|---|---|---|
| **Module** | `Modules/AIIdeaMatrixGenerator` mới, tự `module:make` | KHÔNG module mới — bổ sung vào `Modules/PromptFrameworkStudio` | `PromptFrameworkStudio` đã có đúng data shape (`generated_prompts`), đúng Action (`RenderPromptFromFrameworkAction`), đúng UI (form field động theo config), đúng permission/sidebar/route đã hoạt động. Tách module mới sẽ là module thứ 4 trong repo có toàn bộ mục đích là "sinh prompt copy-paste cho AI ngoài" (cùng `ContentOutlines`, `AIVideoStudioTemplate`) — trùng lặp không có lý do nghiệp vụ nào bù đắp |
| **Bảng DB** | 2 bảng mới `ai_idea_campaigns`/`ai_idea_prompts` | KHÔNG bảng mới — dùng `generated_prompts` đã có (`framework_key`, `field_values` JSON, `rendered_prompt`) | `field_values` (JSON, đã có sẵn) lưu được `format_constant`/`heritage_variable`/`situation_variable`/`custom_context` mà không cần cột riêng; `post_category_id` (đã có) đóng vai trò tương đương "campaign context" qua `ContentFoundation` (xem §3) |
| **Catalog Hằng/Biến số** | 3 class PHP riêng trong `Features/IdeaMatrix/Support/Catalogs` | Khai báo NGAY trong `config/prompt_framework_studio.php`, dạng `options` của field `type: 'select'` (field type MỚI cần thêm — xem §2.1) | Đúng nguyên tắc "Catalog = PHP const, không DB" mà cả 2 bên đều đồng ý — chỉ khác NƠI đặt: config framework đã có sẵn là nguồn DUY NHẤT cho field, thêm 1 thư mục Catalog song song sẽ tạo 2 nguồn cấu hình cho cùng 1 khái niệm |
| **Render Master Prompt** | Cú pháp `{{ $campaign->red_thread_message }}` kiểu Blade trong văn bản đặc tả | String PHP thuần (mảng dòng + `implode`), qua `RenderPromptFromFrameworkAction` đã có | `{{ }}` là cú pháp Blade auto-escape HTML — nếu implementer hiểu là "hãy dùng Blade view" sẽ escape `&`/`"`/`'` trong giá trị người dùng nhập, làm hỏng prompt text thuần. `RenderPromptFromFrameworkAction` đã cố ý KHÔNG dùng Blade (xem docblock class đó) — tái dùng thay vì lặp lại rủi ro |
| **"Campaign" nhiều-prompt** | Bảng `ai_idea_campaigns` riêng, `IdeaPrompt` FK tới đó | KHÔNG bảng riêng — chấp nhận giới hạn, bù bằng "Dùng lại giá trị từ prompt trước" (xem §3) | Đây là phần duy nhất `PromptFrameworkStudio` thật sự CHƯA có tương đương — ghi nhận trung thực thay vì giả vờ đã giải quyết bằng 1 bảng DB mới |
| **AI Provider** | KHÔNG gọi trực tiếp | KHÔNG đổi — đúng nguyên tắc gốc, khớp `PromptFrameworkStudio` §0 | Không có gì để tranh luận lại — cả 2 bản đều đúng ở điểm này |

---

## 1. Mục tiêu

Cho phép đội content ghép **Format cố định** (góc quay/khung nội dung) với **2 trục biến số** (yếu
tố Di sản/Sản phẩm + Tình huống gia đình đời thường) thành 1 preset framework mới trong Thư viện
Prompt (`/dashboard/prompt-studio/library`), sinh Master Prompt hoàn chỉnh để dán sang ChatGPT/Claude
viết kịch bản Video ngắn + caption — không cần khái niệm/module riêng.

---

## 2. Thay đổi cụ thể trong `Modules/PromptFrameworkStudio`

### 2.1 Field type mới: `select` (bắt buộc, phần việc kỹ thuật thật sự mới)

Hiện `field-form.blade.php` chỉ xử lý `field.type === 'text'` và `'textarea'` (2 nhánh `x-show`).
Cần thêm nhánh thứ 3 `field.type === 'select'`, render `<select>` từ `field['options']` (mảng
`value => label`, khai báo NGAY trong config, cùng chỗ với `key`/`label`/`hint`). Đây là bổ sung
DÙNG CHUNG cho toàn bộ 18+5 framework hiện có, không riêng cho preset mới — bất kỳ framework nào sau
này cần 1 field giới hạn lựa chọn (thay vì text tự do) đều hưởng lợi, tránh phải làm riêng UI cho
từng preset.

> **Sửa lại điểm sai ở bản trước:** câu "RenderPromptFromFrameworkAction KHÔNG cần đổi gì" là SAI —
> phát hiện khi rà lại theo góp ý edge-case. Vòng lặp hiện tại của Action đó lấy thẳng
> `$fieldValues[$field['key']]` làm nội dung khối — với field `text`/`textarea` giá trị này ĐÃ LÀ
> text người đọc hiểu được, nhưng với field `select` giá trị lưu là KHOÁ (`pov_parent`), không phải
> nhãn (`"POV Bố/Mẹ — góc nhìn thứ nhất..."`). Nếu không đổi gì, prompt sinh ra sẽ in thẳng khoá thô
> `pov_parent` thay vì mô tả — vô nghĩa với AI đọc prompt, hỏng đúng mục đích của cột `options`.
>
> **Việc cần đổi thật sự** trong `RenderPromptFromFrameworkAction::handle()`: khi `$field['type'] ===
> 'select'`, thay vì dùng thẳng `$value`, tra `$field['options'][$value] ?? $value` để lấy NHÃN (fallback
> về chính `$value` nếu khoá không khớp `options` — không để render vỡ nếu dữ liệu cũ/dữ liệu lạ lọt
> qua, xem lý do ở khối cảnh báo bảo mật ngay dưới).
>
> **Cảnh báo bảo mật (góp ý xác nhận đúng, bổ sung vào đây):** nếu KHÔNG có fallback `?? $value` ở
> trên, và người dùng bằng cách nào đó gửi lên 1 khoá không tồn tại trong `options` (VD sửa tay HTML
> qua DevTools rồi submit `heritage_variable = "hack_script"`), `$field['options'][$value]` sẽ truy
> cập 1 phần tử mảng không tồn tại — PHP 8 phát ra **`E_WARNING: Undefined array key`** (KHÔNG phải
> lỗi fatal/crash 500 — request vẫn chạy tiếp, giá trị trả về là `null`), kết quả là khối đó bị RỖNG
> trong prompt sinh ra (VD `## Yếu tố Di sản/Sản phẩm` không có nội dung theo sau) — lặng lẽ hỏng
> prompt chứ không báo lỗi rõ ràng cho người dùng biết. Cần chặn ở 2 lớp, không chỉ 1:
> 1. **Validate ở input** (lớp chính) — `StoreGeneratedPromptRequest::rules()` hiện gán cứng
>    `['required'|'nullable', 'string', 'max:5000']` cho MỌI field bất kể `type` (xem file thật).
>    Với field `type === 'select'`, thêm `Rule::in(array_keys($field['options']))` vào mảng rule của
>    đúng field đó — dữ liệu rác bị chặn ở tầng 422 trước khi lưu vào `field_values`, đúng góp ý.
>    **v2.1 — điều kiện thêm:** `Rule::in` CHỈ áp cho field select KHÔNG có `allow_custom` (§2.5) —
>    field `allow_custom` là tập MỞ theo thiết kế, text tự do đi qua rule `string|max:5000` chung.
>    Điều này KHÔNG mâu thuẫn với lý do bảo mật ở trên: `Rule::in` bảo vệ *tính toàn vẹn của tập
>    đóng*, không phải bảo mật hệ thống — khi tập được thiết kế mở, text tự do an toàn ngang mọi
>    field `text`/`textarea` khác của module (biên tập viên nội bộ, prompt copy-paste).
> 2. **Fallback ở render** (lớp phòng thủ thứ 2, KHÔNG thay thế lớp 1) — `?? $value` đã nêu ở trên.
>    Lý do cần cả 2 lớp chứ không chỉ validate: `field_values` là cột `json` không ràng buộc CHECK ở
>    DB, dữ liệu có thể tới từ đường khác ngoài `StoreGeneratedPromptRequest` (seeder, import, sửa
>    tay qua Tinker, hoặc 1 preset sau này đổi `options` làm khoá cũ đã lưu không còn hợp lệ) —
>    validate chỉ chặn được đường ĐI VÀO qua đúng request đó, không chặn được dữ liệu đã tồn tại từ
>    trước hoặc tới từ đường khác. Render vẫn phải tự an toàn với dữ liệu không như kỳ vọng.
>
> **Góp ý riêng "escape khi hiển thị `rendered_prompt`" — đã kiểm tra: KHÔNG phải việc cần làm, vì đã
> đúng sẵn.** Đã grep thực tế 2 nơi hiển thị prompt sinh ra trong `<textarea readonly>`:
> `Modules/PromptFrameworkStudio/resources/views/prompts/show.blade.php:67` và
> `Modules/AIVideoStudioTemplate/resources/views/formula-advisor.blade.php:79` — cả 2 đều dùng
> `{{ $prompt->rendered_prompt }}`/`{{ $masterPrompt }}` (2 dấu ngoặc nhọn, Blade auto-escape), không
> phải `{!! !!}` (raw, không escape). Đây LÀ best practice đúng như góp ý — chỉ là đã được tuân thủ từ
> trước, không phải khoảng trống cần vá. Cần phân biệt rõ 2 NGỮ CẢNH khác nhau để không nhầm lẫn với
> quyết định "không dùng Blade" ở §0:
> - **DỰNG chuỗi prompt** (`RenderPromptFromFrameworkAction`) — PHẢI string PHP thuần, KHÔNG qua
>   `view()->render()`/cú pháp Blade nào — vì đây là bước tạo ra GIÁ TRỊ text thuần sẽ được copy sang
>   nơi khác (ChatGPT/Claude); nếu dùng Blade ở bước này, `{{ }}` sẽ escape `&`/`"`/`'` NGAY TRONG nội
>   dung prompt, làm sai lệch prompt thật trước khi tới tay AI ngoài.
> - **HIỂN THỊ chuỗi đã dựng xong lên trang HTML của chính app** (`show.blade.php`/`formula-
>   advisor.blade.php`) — PHẢI dùng `{{ }}` (Blade auto-escape) như hiện tại — vì đây là bước render
>   text đó (vốn chứa input tự do của người dùng: `custom_context`, `audience`...) vào DOM của trang,
>   đúng bối cảnh cần chống XSS kinh điển. `{{ }}` không làm hỏng nội dung copy ra ngoài — trình duyệt
>   giải mã HTML entity về lại ký tự gốc khi người dùng bôi đen/copy từ `<textarea>`, chỉ mã HTML
>   nguồn mới chứa entity, không phải nội dung thực tế được copy.

### 2.2 Nút "🎲 Randomize" (generic, không riêng cho preset này)

Thêm 1 nút trên form tạo prompt: nếu framework đang chọn có ≥1 field `type: 'select'`, hiện nút
"🎲 Ngẫu nhiên" — bấm sẽ chọn ngẫu nhiên 1 giá trị cho MỖI field `select` (JS phía client, đọc thẳng
`options` đã render trong DOM, không cần endpoint mới). Cùng lý do ở §2.1: generic hoá thay vì làm
riêng cho 1 preset.

### 2.3 Preset mới: `heritage_idea_matrix`

Thêm vào `config/prompt_framework_studio.php`, nhóm MỚI `'group' => 'Ý tưởng theo Ma trận'` (khác
nhóm "Chiến lược nội dung" đã có — 5 preset đó là tài liệu CHIẾN LƯỢC theo quý, preset này là Ý TƯỞNG
1 video/bài đơn lẻ, tần suất dùng khác hẳn):

```php
'heritage_idea_matrix' => [
    'name' => 'Ma trận Ý tưởng Di sản',
    'group' => 'Ý tưởng theo Ma trận',
    'description' => 'Format cố định + Yếu tố Di sản/Sản phẩm + Tình huống gia đình → ý tưởng kịch bản Video ngắn lồng ghép văn hoá vào đời sống thường ngày, không bị lan man.',
    'best_for' => 'Bí ý tưởng khi cần sản xuất đều đặn nội dung quảng bá di sản/sản phẩm văn hoá gắn với gia đình hiện đại — mỗi lần chọn/random 1 tổ hợp là 1 góc kịch bản khác nhau.',
    'fields' => [
        ['key' => 'red_thread', 'label' => 'Thông điệp cốt lõi', 'hint' => 'Sợi chỉ đỏ xuyên suốt — mặc định gợi ý "Di sản Sống - Gắn kết Gia đình Hiện đại", có thể đổi theo chiến dịch.', 'prompt_heading' => 'Thông điệp cốt lõi (bám sát tuyệt đối)', 'type' => 'text', 'required' => true],
        ['key' => 'audience', 'label' => 'Khán giả mục tiêu', 'hint' => 'VD: Mẹ bỉm sữa 25-35 tuổi, quan tâm giá trị truyền thống cho con.', 'prompt_heading' => 'Khán giả mục tiêu', 'type' => 'textarea', 'required' => true],
        ['key' => 'format', 'label' => 'Format nội dung', 'hint' => 'Khung xương kịch bản.', 'prompt_heading' => 'Định dạng kịch bản (Format)', 'type' => 'select', 'options' => [
            'pov_parent' => 'POV Bố/Mẹ — góc nhìn thứ nhất, kinh nghiệm thực chiến/xử lý sự cố',
            'time_capsule' => 'Chiếc hộp thời gian — hoạt động giáo dục con, tương tác vật lý với sản phẩm',
            'walking_family' => 'Gia đình đi bộ — review trải nghiệm sự kiện/du lịch chậm/checklist',
            'weekend_kitchen' => 'Nếp nhà cuối tuần — gắn kết đa thế hệ, sinh hoạt ẩm thực, không gian hoài cổ',
            'behind_the_scenes' => 'Hậu trường — sự lộn xộn chân thực của làm cha mẹ kết hợp văn hoá',
        ], 'required' => true],
        ['key' => 'heritage_variable', 'label' => 'Yếu tố Di sản/Sản phẩm', 'hint' => 'Yếu tố văn hoá/vật lý sẽ lồng ghép — chọn từ gợi ý hoặc "Khác (tự nhập)" cho yếu tố ngoài danh sách.', 'prompt_heading' => 'Yếu tố Di sản/Sản phẩm', 'type' => 'select', 'allow_custom' => true, 'options' => [ // v2.1 — §2.5
            // Di sản
            'lang_co' => 'Không gian làng cổ', 'le_hoi' => 'Lễ hội dân gian', 'di_tich' => 'Di tích lịch sử', 'dinh_chua' => 'Kiến trúc đình chùa',
            // Sản phẩm
            'gom_bat_trang' => 'Gốm sứ Bát Tràng', 'lua_van_phuc' => 'Lụa Vạn Phúc', 'to_he' => 'Đồ chơi Tò he', 'nuoc_mam' => 'Nước mắm truyền thống', 'tra_sen' => 'Trà sen',
            // Dịch vụ
            'combo_da_ngoai' => 'Combo vé dã ngoại gia đình', 'workshop_gom' => 'Workshop nặn gốm cho bé',
        ], 'required' => true],
        ['key' => 'situation_variable', 'label' => 'Tình huống Gia đình', 'hint' => 'Nỗi đau/sự kiện sinh hoạt đời thường làm điểm neo cảm xúc — chọn từ gợi ý hoặc "Khác (tự nhập)".', 'prompt_heading' => 'Tình huống Gia đình', 'type' => 'select', 'allow_custom' => true, 'options' => [ // v2.1 — §2.5
            // Khủng hoảng nhỏ
            'an_va' => 'Trẻ ăn vạ chốn đông người', 'lam_ban' => 'Con làm bẩn đồ mới', 'troi_mua' => 'Trời mưa hỏng kế hoạch đi chơi', 'thich_ipad' => 'Trẻ chỉ thích xem iPad',
            // Gắn kết
            'bo_vung_ve' => 'Bố vụng về chơi cùng con', 'ba_the_he' => 'Ba thế hệ chung mâm cơm', 'day_tien' => 'Dạy con về tiền bạc/tiết kiệm',
            // Áp lực
            'me_thieu_ngu' => 'Mẹ bỉm thiếu ngủ', 'ngan_sach' => 'Ngân sách cuối tháng eo hẹp', 'hanh_ly' => 'Chuẩn bị hành lý đi chơi quá tải',
        ], 'required' => true],
        ['key' => 'custom_context', 'label' => 'Ghi chú thêm', 'hint' => 'Chi tiết riêng cho lần này — bỏ trống nếu không có.', 'prompt_heading' => 'Ghi chú thêm từ biên tập viên', 'type' => 'textarea', 'required' => false],
    ],
    'task_instructions' => [
        'Mở đầu bằng Tình huống Gia đình đã chọn, sau đó giải quyết bằng Yếu tố Di sản/Sản phẩm, theo đúng góc nhìn của Format đã chọn.',
        'Tuyệt đối KHÔNG viết văn phong sáo rỗng, quảng cáo lộ liễu — mô tả sự lộn xộn, chân thực của đời sống gia đình (quy tắc "Authenticity").',
        'KHÔNG để nhân vật nói thẳng ra giá trị văn hoá/quảng cáo (VD "sản phẩm này rất có tính văn hoá") — để nhân vật HÀNH ĐỘNG cùng sản phẩm/bối cảnh đó thay vì kể lại (quy tắc "Show, Don\'t Tell").',
        'Nếu bối cảnh có trẻ em: không dàn dựng tình huống nguy hiểm/gây khó chịu thật cho trẻ chỉ để quay hình; không dùng hình ảnh trẻ khóc/hoảng loạn làm điểm nhấn giật gân.',
        'Nếu bối cảnh diễn ra tại di tích/đình chùa/không gian tín ngưỡng: giữ thái độ tôn trọng, không dàn dựng hành vi phản cảm/thiếu tôn nghiêm tại nơi đó dù chỉ trong kịch bản.',
        'Nếu nội dung gắn với sản phẩm/dịch vụ tài trợ: đưa 1 câu công khai đây là nội dung có yếu tố quảng bá (VD lồng trong caption), không che giấu quan hệ hợp tác.',
        'Trả về đúng cấu trúc: (1) TIÊU ĐỀ HOOK — 1 câu giật gân, đồng cảm; (2) KỊCH BẢN CHI TIẾT — bảng 2 cột [Hình ảnh/Góc máy] và [Âm thanh/Lời thoại]; (3) CAPTION ĐĂNG BÀI — tối đa 150 chữ kèm hashtag.',
    ],
    'example' => [
        'red_thread' => 'Di sản Sống - Gắn kết Gia đình Hiện đại',
        'audience' => 'Mẹ 28-38 tuổi ở thành phố, có con 3-8 tuổi, quan tâm giá trị truyền thống nhưng bận rộn, ít thời gian',
        'format' => 'pov_parent',
        'heritage_variable' => 'gom_bat_trang',
        'situation_variable' => 'lam_ban',
        'custom_context' => 'Video cho dịp cuối tuần, ưu tiên quay tại nhà, không cần đi xa.',
    ],
],
```

4 quy tắc nội dung/pháp lý bổ sung ở `task_instructions` (dòng 4-6) là phần **KHÔNG có** trong bản
v1.0 — thêm dựa trên cùng mức độ cẩn trọng mà `spec/chan-dung-nguoi-review.md`/`spec/ma-tran-cong-
thuc-kich-ban.md` (dùng cho `AIVideoStudioTemplate`) đã áp dụng cho nội dung có trẻ em/tài trợ, dù
ngách sản phẩm ở đây (di sản văn hoá) không có ràng buộc pháp lý nặng như Mẹ & Bé (Nghị định
100/2014/NĐ-CP) — mức độ cẩn trọng vẫn nên tương xứng, không phải vì đổi ngách mà bỏ hẳn.

### 2.4 KHÔNG cần Route/Controller mới

`PromptFrameworkStudio` đã có sẵn `GET prompts/create`, `POST prompts`, `GET/PUT prompts/{prompt}`,
`GET library` — preset mới TỰ ĐỘNG xuất hiện trong dropdown chọn framework qua `@foreach` đã có
(đúng convention "thêm 1 phần tử config, không sửa view" mà `FrameworkLibraryController`/`PromptGene
rationController` đã theo cho 18 framework hiện tại). KHÔNG cần permission mới (`prompt_framework_
studio.use` đã cấp cho đúng nhóm role content), KHÔNG cần sidebar entry mới.

### 2.5 `allow_custom` — mở lại 2 trục biến số (v2.1, sửa lỗi thiết kế của chính v2.0)

**Lỗi thiết kế bị phát hiện (người dùng chỉ ra khi review trang `create?framework=heritage_idea_
matrix`):** tài liệu gốc gọi mô hình là **"Hằng số + Biến số"** — Format là *hằng số* (khung xương),
Yếu tố Di sản và Tình huống Gia đình là *biến số* (trục tạo đa dạng tổ hợp); bản nháp v1.0 thậm chí
khai báo `heritage_variable` là `string(255)` TỰ DO kèm câu "hỗ trợ mở rộng không giới hạn". Bản
v2.0 đóng cứng cả 3 trục thành enum + `Rule::in` — tức đã **biến biến số thành hằng số**, cứng hơn
cả bản nháp mà nó thay thế. Nguyên nhân gốc: áp convention "Catalog = config, sửa là việc của dev,
việc hiếm khi xảy ra" (đúng với CẤU TRÚC framework — RACE/CO-STAR đổi 1 lần/năm) lên DỮ LIỆU biên
tập (sản phẩm di sản/tình huống gia đình — đổi theo mùa lễ hội/chiến dịch). Với công cụ chống bí ý
tưởng, khoá từ vựng ý tưởng trong 21 lựa chọn là tự phản mục đích: chiến dịch về "Áo dài Huế", "Múa
rối nước"… người dùng kẹt hoàn toàn.

**Cơ chế sửa — key `allow_custom` trên field `select`** (đã cân nhắc và chọn thay cho 2 hướng khác,
xem §5 mục "nạp động từ Heritage/OCOP"):

- **Config**: `'allow_custom' => true` trên `heritage_variable`/`situation_variable`. `format` CỐ Ý
  KHÔNG có — nó là hằng số cấu trúc thật (mỗi khoá ngầm định 1 khung kịch bản), mở tự do sẽ phá vai
  trò hằng số. Danh sách `options` của 2 field mở đổi vai trò: từ *biên giới* thành *gợi ý + nguồn
  cho nút Randomize*; mỗi giá trị tự nhập của người dùng còn là tín hiệu cho biết nên bổ sung gì vào
  danh sách gợi ý sau này.
- **UI** (`field-form.blade.php` + `prompt-framework-studio.js`): field `allow_custom` thêm lựa chọn
  cuối "✏️ Khác (tự nhập)…" (sentinel `__custom__` — không đặt khoá option nào trùng tên này) mở 1 ô
  text bên dưới. `<select>` KHÔNG còn `x-model` trực tiếp — giá trị hiển thị (`selectValueFor`) tách
  khỏi giá trị thật (`values[field.key]` = khoá HOẶC text tự do), mọi ghi qua `onSelectChange`. Cờ
  `customSelect[field.key]` phân biệt "đang gõ custom nhưng ô còn rỗng" với "— Chưa chọn —" (không
  phân biệt được chỉ bằng giá trị); trang edit/reuse với giá trị custom đã lưu tự nhận diện qua
  heuristic "giá trị không phải khoá nào trong options". Cờ được RESET ở 3 chỗ đổi giá trị hàng
  loạt: `select()` (đổi framework), `randomizeSelectFields()` (random luôn trả khoá đã biết),
  `reuseLastPromptValues()` (giá trị nạp về tự quyết định chế độ hiển thị).
- **Validate**: `Rule::in` CHỈ áp khi field select KHÔNG có `allow_custom` (cả Store lẫn Update
  request) — xem ghi chú bảo mật đã cập nhật ở §2.1.
- **Render**: KHÔNG đổi code — fallback `?? $value` của `RenderPromptFromFrameworkAction` (vốn xây
  làm lớp phòng thủ) trở thành ĐƯỜNG CHÍNH cho giá trị tự nhập: text không khớp `options` được chèn
  nguyên văn vào prompt. `assembledPreview` (JS) đã map cùng logic nên bản xem trước khớp server.
- **`isFieldThin`** giữ nguyên loại trừ field select — giá trị tự nhập ngắn hợp lệ ("Áo dài") không
  đáng bị cảnh báo "còn ngắn".

### 2.6 `custom_max_length` — chặn lạm dụng `allow_custom` (v2.2, sửa lỗi phát sinh từ v2.1)

**Lỗi phát hiện qua sử dụng thật:** người dùng dán NGUYÊN VĂN 1 đoạn thông cáo quảng cáo dài (~258
ký tự, đủ tên thương hiệu/địa điểm/emoji) vào ô "Tình huống Gia đình" — field vốn chỉ cần 1 CỤM TỪ
NGẮN neo cảm xúc (VD "Trẻ ăn vạ chốn đông người", dài nhất trong 21 nhãn có sẵn ~33 ký tự). Hệ quả
kép: (1) nội dung đó gần như trùng lặp hoàn toàn với ô "Yếu tố Di sản/Sản phẩm" và "Thông điệp cốt
lõi" đã điền — 3 field cùng nói 1 việc; (2) `task_instructions[0]` ("Mở đầu bằng Tình huống Gia đình
… sau đó giải quyết bằng Yếu tố Di sản") mất tác dụng vì cả 2 khối đầu vào giờ chứa cùng nội dung,
không còn cấu trúc "vấn đề → giải pháp" mà mô hình "Hằng số + Biến số" dựa vào. Nguyên nhân: `allow_
custom` (§2.5, v2.1) mở text tự do nhưng dùng CHUNG rule `max:5000` với field textarea tự do (VD
`audience`) — không có gì cản việc dán cả bài PR vào 1 field vốn là 1 câu ngắn.

**Sửa — key `custom_max_length` trên field `select` (chỉ có ý nghĩa cùng `allow_custom`):**

- **Config**: `'custom_max_length' => 120` trên `heritage_variable`/`situation_variable` (so với
  nhãn dài nhất ~33 ký tự — đủ rộng cho 1 câu ngắn tự nhập, không đủ cho 1 đoạn quảng cáo). Hint text
  viết lại nêu rõ "PHẢI là 1 CỤM TỪ NGẮN... KHÔNG dán cả đoạn mô tả/quảng cáo dài".
- **Validate** (`Store/UpdateGeneratedPromptRequest`): field select có `allow_custom` dùng
  `max:{$field['custom_max_length'] ?? 150}` thay vì `max:5000` mặc định — field không khai
  `custom_max_length` (tương lai, framework khác) fallback về 150. Field select KHÔNG allow_custom
  (VD `format`) không đổi, vẫn `max:5000` (không ảnh hưởng vì đã bị `Rule::in` chặn theo khoá).
- **UI**: `<input>` tự nhập thêm `maxlength` HTML (chặn cứng phía client, khớp giá trị validate) +
  placeholder nêu ví dụ cụ thể ("Trẻ sợ đi khám răng") + cảnh báo MỀM (không chặn, cùng tinh thần
  `isFieldThin`) khi gõ đạt 70% giới hạn — nhắc lại đây là cụm từ, không phải đoạn văn.
- **KHÔNG sửa `RenderPromptFromFrameworkAction`** — Action chỉ ghép chuỗi, việc chặn độ dài thuộc về
  tầng validate, không phải tầng render (giữ đúng phân tầng trách nhiệm đã có).

**Test**: `test_store_rejects_long_promotional_text_pasted_into_allow_custom_field` (mức rules() +
`Validator::make`, không cần DB) — dùng chính đoạn quảng cáo AEON MALL thật (258 ký tự) làm dữ liệu
test, xác nhận bị 422 tại đúng field, và cụm từ ngắn thay thế vẫn pass (không khoá lại toàn bộ field).

### 2.7 Hướng dẫn tách nội dung thô thành 6 field (dành cho người dùng, không phải dev)

Rút ra sau 2 lần thử thật (AEON MALL §2.6, OCOP 2026) — quy tắc chung để tách BẤT KỲ tài liệu quảng
cáo/thông cáo nào (không riêng ngách di sản) thành 6 field, không lặp lại lỗi "dán nguyên văn PR vào
field ngắn":

> **Ghi chú thêm (dài, tự do) = Ai + Cái gì + Khi nào/Ở đâu — LẤY TỪ tài liệu gốc.**
> **2 field biến số ngắn (≤120 ký tự) = Ai đó đang gặp CHUYỆN GÌ, và MÓN GÌ giải quyết chuyện đó —
> "Chuyện gì" KHÔNG có sẵn trong tài liệu quảng cáo, người dùng LUÔN phải tự viết thêm.**

**7 bước:** (1) đọc tài liệu gốc, xác định "đây LÀ CÁI GÌ" trong ≤15 từ → `heritage_variable`; nếu
tài liệu liệt kê nhiều thứ, chọn 1 trọng tâm/video, phần còn lại để dành video khác (nút "Dùng lại
giá trị từ prompt trước", §3). (2) tự hỏi "trước khi biết tới [1], khán giả đang gặp phiền toái/mong
muốn đời thường gì" — **KHÔNG lấy từ tài liệu gốc** — chọn theo 1 trong 3 nhóm cảm xúc của
`SituationVariableCatalog` gốc (Khủng hoảng nhỏ/Gắn kết/Áp lực) rồi cụ thể hoá → `situation_
variable`. Phép thử: tình huống này còn ĐÚNG nếu sản phẩm/sự kiện này không tồn tại không? Không →
đang mô tả lại sản phẩm, viết lại. (3) chọn `format` theo bối cảnh quay (ghé địa điểm → `walking_
family`; thao tác tay cùng con → `time_capsule`; 1 tình huống bố/mẹ tự xử lý → `pov_parent`; sinh
hoạt tại nhà nhiều thế hệ → `weekend_kitchen`; muốn thô/chân thực → `behind_the_scenes`). (4) viết
`red_thread` = câu triết lý ĐỨNG TRÊN sản phẩm này — phép thử: dùng lại được cho 1 sản phẩm hoàn
toàn khác không? Không (nhắc thẳng tên riêng) → hạ xuống field khác. (5) `audience` = nhân khẩu học +
1 chi tiết tâm lý, field textarea không giới hạn ngắn. (6) mọi thứ còn thừa (ngày giờ, địa chỉ, danh
sách tên riêng, số liệu) → dán NGUYÊN VĂN vào `custom_context` — đúng vai trò của field này.

**Tự kiểm trước khi Sinh Prompt:** Thông điệp cốt lõi không nhắc tên riêng của sản phẩm/sự kiện này ·
Tình huống Gia đình vẫn đúng dù đổi sang sản phẩm khác hẳn · Yếu tố Di sản/Sản phẩm nói được trong 1
hơi, không liệt kê 3-4 thứ bằng dấu phẩy · không có câu nào lặp lại y hệt giữa 2 field ngắn và Ghi
chú thêm · cảnh báo "Gần chạm giới hạn" (§2.6) hiện lên = dấu hiệu đang nhét sai chỗ, dừng lại.

### 2.8 Đưa hướng dẫn §2.7 VÀO TRONG form (v2.3, sửa lỗi phát hiện ngay sau khi viết §2.7)

**Lỗi phát hiện:** §2.7 viết xong nhưng chỉ nằm trong tài liệu spec + hội thoại chat — **không hiển
thị ở đâu trong form thật**. Đáng chú ý hơn: `hint` cảnh báo "không dán quảng cáo dài" đã có sẵn từ
§2.6 (v2.2) TRƯỚC KHI ví dụ AEON MALL xảy ra, nhưng người dùng vẫn dán nguyên đoạn quảng cáo — chứng
minh bằng thực tế rằng `hint` (chữ nhỏ, dưới label, dễ lướt qua) không đủ sức cản, cần 1 cơ chế nổi
bật hơn.

**Sửa — dùng lại đúng 2 cơ chế UI đã có sẵn trong `PromptFrameworkStudio` thay vì phát minh mới:**

- **`tip`** (callout `text-info` viền trái, khác hẳn `hint` phẳng — cơ chế đã có từ trước, dùng cho
  "field ảnh hưởng nhiều nhất tới chất lượng", xem `costar.audience`/`risen.steps` làm mẫu) — gắn vào
  `situation_variable` (đúng field đã xảy ra lỗi thật): nêu phép thử "xoá tên sản phẩm khỏi câu vừa
  viết, còn đúng và có nghĩa không?" — chính là phép thử ở bước 3 của §2.7, rút gọn để vừa 1 callout.
  Chỉ gắn 1 tip (đúng convention "1 tip/framework" đã có), nội dung nói về QUAN HỆ giữa cả 2 field
  biến số vì `heritage_variable` đứng ngay phía trên trong thứ tự canon.
- **`custom_placeholder`** (key MỚI, tuỳ chọn trên field `select`) — trước v2.3, ô tự nhập của MỌI
  field `allow_custom` dùng chung 1 placeholder cứng ví dụ theo "Tình huống Gia đình" ("Trẻ sợ đi
  khám răng") — sai ngữ cảnh khi hiện y hệt cho "Yếu tố Di sản/Sản phẩm". `field-form.blade.php` đổi
  sang `field.custom_placeholder || (chuỗi generic fallback)` — mỗi field allow_custom nay có ví dụ
  placeholder ĐÚNG bản chất của nó.

**Nhắc lại (§2.7 vẫn còn giá trị):** đây là 2 tầng khác nhau — `tip`/`custom_placeholder`/`hint`
(sửa ở đây) là gợi ý TỨC THỜI ngay lúc gõ; §2.7 là tài liệu THAM KHẢO ĐẦY ĐỦ (7 bước + ví dụ) cho
người mới — không thay thế nhau, cả 2 cùng cần.

### 2.9 Khối "Ví dụ tham khảo" — toàn cảnh 1 ví dụ, không phải gợi ý rời rạc (v2.4)

**Lý do:** `tip`/`hint`/`custom_placeholder` (§2.8) là gợi ý RỜI RẠC — mỗi cái gắn vào 1 field, người
dùng phải tự lướt qua từng ô rồi tự ghép lại trong đầu thành 1 bức tranh hoàn chỉnh. Người dùng yêu
cầu trực tiếp: cần 1 khối hiển thị SẴN toàn bộ ví dụ, đặt NGAY TRÊN field đầu tiên, để thấy được toàn
cảnh TRƯỚC khi bắt đầu điền — không phải tự lắp ráp.

**Cơ chế — tái dùng dữ liệu đã có, không thêm config mới:** `framework['example']` đã tồn tại từ
trước (dùng làm placeholder rời rạc từng field `text`/`textarea`, và dựng `rendered_example` đầy đủ
ở trang Thư viện) — GIỜ CÒN dùng thêm để dựng khối liệt kê `label: giá trị mẫu` theo đúng thứ tự
canon field, đặt ngay trên lưới field trong `field-form.blade.php` (chung với `create`/`edit`).
Alpine getter `exampleRows()` (mới) map field `select` sang NHÃN (cùng logic `RenderPromptFrom
FrameworkAction` phía server — không hiện khoá thô "pov_parent"), bỏ qua field không có `example`.
**Generic cho MỌI framework** — xác nhận bằng tinker: khối này hiện đúng cho cả `heritage_idea_
matrix` LẪN `costar` (framework tổng quát có sẵn từ trước), không phải hack riêng cho preset di sản.

Mở sẵn (không phải `<details>` đóng như khối "Xem trước prompt" ở cuối trang) — đây là thứ CẦN thấy
TRƯỚC khi điền, khác khối xem trước vốn chỉ có ích SAU khi đã gõ vài field.

### 2.10 Đổi ví dụ chuẩn (`example`) sang thông cáo hội chợ OCOP thật (v2.5)

**Yêu cầu:** người dùng đưa 1 thông cáo hội chợ thật ("Hội chợ Xúc tiến thương mại nông nghiệp, sản
phẩm OCOP – HaNoi Agriculture Fair 2026", AEON MALL Long Biên, 13-16/8/2026) và yêu cầu lấy làm **ví
dụ chuẩn** — tức thay `heritage_idea_matrix.example` (hiển thị ở khối "Ví dụ tham khảo" §2.9 VÀ
trang Thư viện), không phải chỉ demo trong hội thoại.

**Áp đúng 7 bước của §2.7** vào nguyên liệu (liệt kê ~10 sản phẩm + 3 nghề truyền thống, không có
tình huống gia đình nào):

| Field | Giá trị mới | Áp bước |
|---|---|---|
| `heritage_variable` | `to_he` (Đồ chơi Tò he) | Bước 1-2 — nguyên liệu liệt kê ~10 sản phẩm + 3 nghề, CHỈ chọn 1 ("tò he") làm trọng tâm; **dùng option CÓ SẴN trong danh sách** (không `allow_custom`) — minh hoạ "kiểm tra danh sách gợi ý trước khi tự nhập" |
| `situation_variable` | `thich_ipad` (Trẻ chỉ thích xem iPad) | Bước 3 — KHÔNG có trong nguyên liệu gốc, tự chọn vì đối lập tự nhiên với hoạt động tay chân của nghề tò he; phép thử: câu này vẫn đúng dù không có hội chợ này |
| `format` | `time_capsule` | Bước 4 — nguyên liệu mô tả "tự mình trải nghiệm nghề truyền thống" = tương tác vật lý, đúng mô tả của `time_capsule`, không phải chỉ ghé thăm (`walking_family`) |
| `red_thread` | giữ nguyên `Di sản Sống - Gắn kết Gia đình Hiện đại` | Bước 5 — không đổi, vẫn là "sợi chỉ đỏ" toàn chiến dịch |
| `audience` | `Gia đình có con nhỏ ở Hà Nội, muốn con trải nghiệm nghề truyền thống thay vì suốt ngày cầm điện thoại` | Bước 6 |
| `custom_context` | nguyên văn phần thời gian/địa điểm/danh sách sản phẩm còn lại | Bước 7 — mọi thứ KHÔNG dùng ở 2 field ngắn dồn hết vào đây |

Ví dụ CŨ (Gốm sứ Bát Tràng/Con làm bẩn đồ mới, v2.0-v2.4) bị THAY, không giữ song song — 1 preset chỉ
có 1 `example`. Đã kiểm chứng: render qua `RenderPromptFromFrameworkAction` ra đúng 6 khối + 7 nhiệm
vụ (406 từ, dưới xa ngưỡng cảnh báo 6.000 từ); trang `create?framework=heritage_idea_matrix` và trang
Thư viện đều phản ánh ví dụ mới, không còn dấu vết "Gốm sứ Bát Tràng" ở đâu; `test_all_13_frameworks_
render_with_full_example_values` (test CHUNG cho mọi framework, không sửa gì) vẫn pass — xác nhận ví
dụ mới tự nhất quán, không cần sửa test riêng.

### 2.11 "Trợ lý tách nội dung thô" — sinh prompt cho AI ngoài tự đề xuất giá trị field (v2.6)

**Yêu cầu:** người dùng cần 1 prompt copy-paste để dán vào Claude/ChatGPT CÙNG với 1 đoạn nội dung
thô (thông cáo/quảng cáo) — AI ngoài đọc xong tự đề xuất giá trị cho từng field, thay vì người dùng
phải tự tay áp 7 bước của §2.7 mỗi lần.

**Cơ chế — reuse hoàn toàn metadata field đã có, KHÔNG thêm config mới:** `fieldSplittingPrompt`
(getter Alpine mới, thuần client-side JS — không endpoint, không gọi AI Provider, đúng nguyên tắc
§0) đọc `selectedFramework.fields` rồi với mỗi field ghép `label` + `hint` + (nếu `type === 'select'`)
liệt kê nguyên văn `options` + ghi chú `custom_max_length` khi có `allow_custom` + (nếu field có
`tip`) nhúng NGUYÊN VĂN `tip` vào chỉ dẫn. Đây là điểm quan trọng nhất: hướng dẫn "Tình huống Gia
đình PHẢI ĐỘC LẬP với Yếu tố Di sản/Sản phẩm" (viết ở `tip`, §2.8) tự động lọt vào prompt tách field
mà KHÔNG cần viết lại — nếu không có cơ chế tái dùng `tip` này, AI tách field rất dễ mắc lại đúng lỗi
gốc (§2.6): sao chép nguyên nội dung quảng cáo vào field "Tình huống Gia đình".

**Generic cho MỌI framework** — đã kiểm chứng bằng tinker: prompt sinh đúng cho cả `heritage_idea_
matrix` lẫn `costar`, không phải cơ chế riêng cho preset di sản.

**UI:** `<details>` ĐÓNG mặc định (khác khối "Ví dụ tham khảo" §2.9 luôn mở) — đặt NGAY TRÊN khối đó,
vì đây là bước làm TRƯỚC nếu có sẵn nội dung thô. Gồm: 1 textarea dán nội dung thô
(`rawContentToSplit`, reactive) + nút Copy (đọc trực tiếp giá trị Alpine, KHÔNG qua DOM `.value` của
`<textarea readonly x-text>` — tránh đúng vấn đề kỹ thuật đã biết: gán `textContent` bằng `x-text`
không đồng bộ ngược lại thuộc tính `.value` của `<textarea>` sau lần render đầu) + 1 textarea readonly
hiển thị prompt đã ghép.

**CỐ Ý KHÔNG tự parse kết quả AI trả về để tự điền form** — người dùng đọc câu trả lời AI rồi tự gõ
vào từng field, đúng nguyên tắc "gợi ý không quyết định thay" xuyên suốt cả module (rủi ro parse sai
lặng lẽ điền nhầm field không đáng đánh đổi lấy tiện lợi tự động điền).

---

## 3. Giới hạn thật khi gộp vào `PromptFrameworkStudio` — không né tránh

`GeneratedPrompt` hiện tại là **1 dòng độc lập**, không có khái niệm "Campaign" chứa nhiều prompt
dùng chung `red_thread_message`/`audience`/`brand_voice`. Nhu cầu thật của bản v1.0 ("1 chiến dịch
sinh 30 prompt cho 30 ngày") **không được giải quyết triệt để** bằng cách gộp vào preset đơn thuần —
mỗi lần tạo prompt mới, biên tập viên phải gõ lại `red_thread`/`audience` (dù `format`/`heritage_
variable`/`situation_variable` đổi mỗi lần).

**Giải pháp đủ dùng, không cần bảng DB mới:** thêm nút "Dùng lại giá trị từ prompt trước" ở trang tạo
prompt — prefill toàn bộ `field_values` từ `GeneratedPrompt` gần nhất có cùng `framework_key` (query
đơn giản, không cần quan hệ Campaign→Prompt). Biên tập viên chỉ cần đổi `format`/`heritage_variable`/
`situation_variable`, giữ nguyên `red_thread`/`audience`. Đây là đánh đổi CÓ CHỦ ĐÍCH — nếu sau này
nhu cầu "1 campaign nhiều prompt" trở nên phổ biến cho MỌI framework (không riêng preset này), nên
cân nhắc thêm khái niệm Campaign vào chính `PromptFrameworkStudio` (ảnh hưởng toàn bộ 19+ preset),
không phải giải pháp riêng cho 1 preset.

---

## 4. Testing

Tái dùng đúng pattern `tests/Feature/RenderPromptFromFrameworkActionTest.php` đã có — thêm test case
cho `heritage_idea_matrix`:

- `RenderPromptFromFrameworkAction::handle('heritage_idea_matrix', [...])` với đủ field bắt buộc →
  chuỗi trả về chứa đúng 7 dòng `task_instructions` đánh số 1-7, đúng heading `## Thông điệp cốt lõi
  (bám sát tuyệt đối)`.
- **`select` render đúng NHÃN, không phải khoá** (test mới, khoá lại điểm sai đã sửa ở §2.1):
  `handle('heritage_idea_matrix', ['format' => 'pov_parent', ...])` → chuỗi trả về chứa
  `"POV Bố/Mẹ — góc nhìn thứ nhất..."`, KHÔNG chứa chuỗi thô `"pov_parent"`.
- **Khoá `select` không hợp lệ không làm vỡ render** (test mới, cùng mạch góp ý bảo mật §2.1):
  `handle('heritage_idea_matrix', ['format' => 'khong_ton_tai', ...])` → KHÔNG phát sinh
  `E_WARNING`/exception nào, chuỗi trả về chứa fallback `"khong_ton_tai"` (giá trị thô, do
  `?? $value`) thay vì rỗng/lỗi — test này giả lập đúng tình huống dữ liệu lọt qua validate (seeder/
  Tinker/khoá cũ hết hạn khi `options` đổi), không phải tình huống thường gặp qua UI.
- **`StoreGeneratedPromptRequest` chặn khoá rác ở tầng validate** (test mới, Feature test — theo đúng
  góp ý): `POST prompts` với `field_values.format = 'hack_script'` (không có trong `options` của
  `heritage_idea_matrix`) → 422, lỗi tại đúng field `field_values.format`; giá trị hợp lệ bất kỳ
  trong `options` → pass validate.
- **v2.1 — `allow_custom` mở đúng field, đóng đúng field** (`test_validation_rules_allow_free_text_
  only_for_allow_custom_select_fields`, mức `rules()` + `Validator::make` — KHÔNG cần HTTP/DB nên
  chạy được ngay cả khi môi trường test DB đang lệch schema, ĐÃ CHẠY PASS): text tự do ("Áo dài
  Huế"/"Trẻ sợ đi khám răng") trên `heritage_variable`/`situation_variable` → pass; khoá lạ trên
  `format` (không `allow_custom`) → fail đúng tại `field_values.format`. Kèm kiểm chứng tinker
  end-to-end: text tự nhập render NGUYÊN VĂN vào prompt, khoá đã biết vẫn render NHÃN.
- `RenderPromptFromFrameworkAction::handle('heritage_idea_matrix', [...])` với đủ field bắt buộc →
  chuỗi trả về chứa đúng 7 dòng `task_instructions` đánh số 1-7, đúng heading `## Thông điệp cốt lõi
  (bám sát tuyệt đối)`.
- `custom_context` rỗng → dòng "Ghi chú thêm từ biên tập viên" KHÔNG xuất hiện trong `rendered_
  prompt` (đúng hành vi field rỗng bị bỏ hẳn, không in nhãn cụt — đã có sẵn ở `RenderPromptFrom
  FrameworkAction`, không cần code riêng cho preset này).
- Field-form UI: `field.type === 'select'` render đúng `<select>` với `options`; nút "🎲 Ngẫu nhiên"
  chọn được giá trị hợp lệ cho cả 3 field select cùng lúc.
- **Hiển thị `rendered_prompt` trên trang** — đã đúng sẵn, không cần test mới: `show.blade.php`/
  `formula-advisor.blade.php` dùng `{{ }}` (xem xác nhận ở §2.1), hành vi auto-escape của Blade đã có
  test bao phủ gián tiếp qua các Feature test hiện có của 2 view đó (không lặp lại ở đây).

---

## 5. Ngoài phạm vi (cố ý không làm)

- **Bảng DB `ai_idea_campaigns`/`ai_idea_prompts`** — xem §0/§3, thay bằng "dùng lại giá trị từ prompt trước".
- **Module/Catalog class riêng** — xem §0.
- **Bảng cấm ghép Format × Di sản × Tình huống** (kiểu `ma-tran-cong-thuc-kich-ban.md` Phần 8) — rủi
  ro tổ hợp phản cảm khi Random tồn tại thật (§2.3 dòng task_instructions 4-6 giảm nhẹ bằng chỉ dẫn
  chung), nhưng chưa đủ dữ liệu thực tế để liệt kê tổ hợp cấm cụ thể như ngách Mẹ & Bé — để dành nếu
  sau vài tuần dùng thật phát hiện tổ hợp nào hay bị chê.
- **Tổng quát hoá field `select` sang có phân nhóm `<optgroup>`** (Di sản/Sản phẩm/Dịch vụ, Khủng
  hoảng nhỏ/Gắn kết/Áp lực) — bản trên gộp phẳng để tối giản lần đầu; nếu danh sách dài thêm, nên bổ
  sung `optgroup` cho dễ chọn, không phải ưu tiên ở lần triển khai đầu.
- **Nạp `options` động từ dữ liệu thật của nền tảng** (v2.1 — "Hướng 2" đã cân nhắc khi sửa lỗi biến
  số, người dùng chọn Hướng 1/`allow_custom` thay thế): `heritage_variable` có thể lấy từ bảng
  `heritage_sites` (`Modules/Heritage` — tên/loại/niên đại/tỉnh thật) + sản phẩm OCOP
  (`Modules/Ocop`) thay vì list config — đúng nhất dài hạn (list tự lớn theo nội dung nền tảng,
  không cần deploy) nhưng scope lớn hơn hẳn: cần cơ chế `options_source` động, autocomplete khi list
  hàng trăm dòng, xử lý bản ghi bị xoá; và trục Tình huống không có nguồn DB nào nên vẫn cần
  `allow_custom` song song. Nếu nhu cầu quay lại, làm như 1 nâng cấp của cơ chế `select` chung,
  không phải hack riêng cho preset này.
