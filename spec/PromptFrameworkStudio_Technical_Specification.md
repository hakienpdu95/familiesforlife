# Module Thư viện & Sinh Prompt theo Framework (PromptFrameworkStudio)

**Đặc tả Kỹ thuật Chi tiết — Sẵn sàng Triển khai**

**Phiên bản:** 1.3
**Ngày:** 07/08/2026
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions
**Module mới:** `Modules/PromptFrameworkStudio`
**Module liên quan:** Không có (module độc lập, không phụ thuộc/không bị phụ thuộc bởi module nghiệp vụ nào — tương tự `Modules/ContentOutlines`, xem §0)

> **v1.1 (review round — chốt các điểm phải làm rõ trước khi code):** (1) Hoàn thiện đầy đủ
> `fields`/`template`/`example` cho **cả 13 framework** trong §2 — v1.0 chỉ viết đầy đủ `costar`,
> 12 framework còn lại ghi "cùng cấu trúc" là chưa đủ để code không đoán mò. (2) Thêm §5.4 — quyết
> định hành vi khi `framework_key` của 1 bản ghi cũ bị gỡ khỏi config (orphaned). (3) Thêm §4.2 —
> cách cụ thể truyền dữ liệu framework từ config xuống Alpine (`@json(...)`). (4) Thêm
> `index('label')` ở migration (§3.1) và danh sách cột Tabulator cụ thể (§4.3). (5) Bổ sung 4 test
> case còn thiếu ở §8. (6) Ghi rõ "live preview khi gõ" và "nút Dùng framework này từ Library" vào
> §7 là việc **để sau** (bản phát hành tính năng kế tiếp), không thuộc phạm vi triển khai lần đầu.

> **v1.2 (polish round — defense-in-depth):** (1) §4.1 — `RegenerateGeneratedPromptAction` tự kiểm
> tra framework tồn tại thay vì chỉ dựa vào controller đã lọc trước (§5.4), phòng trường hợp Action
> được gọi từ chỗ khác không đi qua `edit()`. (2) §4.2 — ghi rõ cách pre-fill `values` từ
> `$prompt->field_values` khi `x-data` khởi tạo ở trang `edit`, thay vì chỉ có ví dụ cho `create`.

> **v1.3 (BUG FIX must-fix + UI/UX polish cho người dùng không rành kỹ thuật):**
> (1) **Sửa lỗi thật**: §4.2 ghi `@json(config(...))` nhúng vào `x-data="..."` — SAI, vì JSON tự nó
> dùng `"` làm cú pháp bắt buộc (không phải nội dung cần escape), nên `@json()` không bao giờ an
> toàn khi nhúng vào 1 thuộc tính HTML cũng bọc bằng `"`; dấu `"` đầu tiên trong JSON cắt đứt
> attribute ngay lập tức → Alpine nhận biểu thức vỡ cú pháp → toàn bộ scope (`frameworks`,
> `selectedKey`, `selectedFramework`) undefined. Đã đổi toàn bộ sang `@js()` (biên dịch ra
> `Illuminate\Support\Js::from(...)->toHtml()`, bọc trong `JSON.parse('...')` bằng nháy đơn +
> `"`) — đúng cơ chế `Js::from()` đã dùng cho 2 tham số còn lại của trang `edit`, và đúng
> convention `Modules/ContentOutlines` đã áp dụng trước đó. (2) **UI/UX cho người không rành kỹ
> thuật** (§4.2/§7): thăng cấp "Dùng framework này" từ mục để-sau lên **đã làm** —
> `PromptGenerationController::create()` nhận `?framework=key` (validate theo config keys, bỏ qua
> nếu không hợp lệ), Thư viện (`library/index`) có nút "Dùng mẫu này" đi thẳng vào `create` với
> mẫu đã chọn sẵn; đổi ngôn ngữ hiển thị bớt thuật ngữ ("framework" → "mẫu", thêm mô tả "Phù hợp
> khi..." nổi bật); mỗi field trong form giờ có `placeholder` lấy trực tiếp từ `example` đã có sẵn
> trong config (không thêm dữ liệu mới) — người dùng thấy ngay ví dụ cụ thể phải điền gì, không
> phải đoán; thêm nút "Đổi mẫu khác" ngay tại bước 2 (không cần rời trang); thêm thanh bước 1→2.

---

## 0. Quyết định đã chốt

| Chủ đề | Quyết định | Lý do |
|---|---|---|
| **Có gọi AI Provider trong app không?** | **KHÔNG.** "Sinh prompt" là ghép chuỗi thuần (template có placeholder `{{field_key}}`), không gọi `app/Services/AI/*` | Đúng tinh thần `Modules/ContentOutlines` (`module.json`: "soạn prompt... để dán sang AI ngoài — không gọi AI Provider trong app"). Đây là công cụ giúp NGƯỜI DÙNG tự viết prompt tốt hơn, không phải 1 tính năng AI của hệ thống — không tốn chi phí AI, không có bề mặt tấn công prompt-injection nào cần phòng ở tầng backend (không có gì được gửi tới LLM nội bộ nào) |
| **Danh mục framework (13 loại) lưu ở đâu?** | **Config PHP** (`config/prompt_framework_studio.php`), KHÔNG phải bảng DB, KHÔNG có CRUD admin cho framework | Cùng nguyên tắc `config('banner.placements')` (`spec/Banner_Management_Technical_Specification.md` §0) — danh mục framework thay đổi hiếm, do dev thêm khi cần (thêm 1 framework mới = thêm 1 phần tử config + deploy, không cần màn hình quản trị riêng cho việc hiếm khi xảy ra này) |
| **"Quản lý" (trong yêu cầu) áp dụng cho cái gì?** | Áp dụng cho **prompt người dùng đã sinh ra** (`generated_prompts` — bảng DB, có CRUD/lịch sử/tìm lại), KHÔNG áp dụng cho danh mục framework (đó là config tĩnh, xem trên) | Nhu cầu thật là "lưu lại prompt tôi đã tạo để dùng lại/sửa lại", không phải "tự thêm framework mới qua UI" |
| **Có `organization_id`/`TenantAwareModel` không?** | **KHÔNG** — `GeneratedPrompt` là `Illuminate\Database\Eloquent\Model` trần, không tenant-scope | Cùng nhóm `ContentOutline`, `content_foundations` — công cụ nội bộ đội content/AI dùng chung, không phải dữ liệu khách hàng theo tổ chức |
| **Có soft-delete / activity log không?** | **KHÔNG** cả hai | Cùng lý do `ContentOutline` (§2.1 spec đó): không phải credential, không phải tài sản nghiệp vụ cần audit — xoá là xoá thật, đơn giản hoá |
| **Ai được dùng?** | Permission mới `prompt_framework_studio.use`, seed trực tiếp cho 3 role: `platform_content_editor`, `platform_content_head`, `platform_section_editor` | Giống hệt `ContentOutlinesPermissionSeeder` — cùng nhóm người dùng (đội biên tập/AI content), không phải 1 trong 8 `RoleEnum` core |
| **Có validate nội dung field người dùng nhập (chống prompt injection) không?** | **KHÔNG cần validate/lọc đặc biệt** — nội dung field là dữ liệu của chính người dùng, ghép ra rồi hiển thị lại cho CHÍNH họ đọc/copy, không có bước nào tự động đưa nó cho AI/người khác | Khác hẳn CoreIdeaExtractor/VideoIdeaExtractor (nội dung ở đó được AI trong app tiêu thụ tự động — cần bọc delimiter theo CLAUDE.md); ở đây output chỉ là text hiển thị lại cho tác giả của chính nó |
| **Bài học ("để học") thể hiện ở đâu?** | 1 trang **Thư viện framework** (`index`, đọc từ config, không cần đăng nhập vào tính năng sinh prompt để xem) — mỗi framework có mô tả, khi nào dùng, và **1 ví dụ đã điền sẵn** | Tách riêng khỏi form sinh prompt thật — cho phép đọc để học trước khi thao tác |
| **Ví dụ mẫu trong config lấy từ đâu?** | **Tự biên soạn**, không sao chép nguyên văn từ 3 bài blog đã tham khảo (`promptquorum.com`, `promptary.dev/frameworks` — 2 nguồn duy nhất đọc được nội dung đầy đủ; `talentgroglobal.com` và `gptpromptmaker.com` chỉ lấy được tiêu đề, không lấy được nội dung do trang render bằng JS) | Cấu trúc/tên field của mỗi framework tham khảo đúng theo các nguồn đọc được; câu ví dụ minh hoạ cụ thể là nội dung mới, tránh gán nhầm là trích dẫn |
| **Bản ghi cũ có `framework_key` đã bị gỡ khỏi config (orphaned) thì xử lý sao?** | **Suy biến an toàn (graceful degrade)**: vẫn cho xem `rendered_prompt` (readonly) và xoá, **không** cho sửa/sinh lại (không còn `fields`/`template` để dựng lại form) — xem §5.4 | Dữ liệu quá khứ (`rendered_prompt` đã lưu) không phụ thuộc config tại thời điểm xem lại — chỉ riêng thao tác CẦN đọc lại config (sửa, sinh lại) mới bị chặn có thông báo rõ ràng, thay vì lỗi 500/trang trắng |
| **Alpine lấy dữ liệu 13 framework (fields/template) từ đâu?** | Nhúng thẳng bằng `@json(config('prompt_framework_studio.frameworks'))` vào `x-data` của trang `create` — KHÔNG gọi thêm 1 API JSON riêng | Dữ liệu tĩnh (config, không đổi theo user/request), nhúng thẳng vào HTML tránh 1 round-trip network không cần thiết — cùng tinh thần "không tạo route mới cho mỗi nhu cầu nhỏ" đã dùng ở nhiều module khác |

---

## 1. Giới thiệu & Mục tiêu

Đội biên tập/AI content hiện không có nơi tra cứu nhanh "framework nào phù hợp cho việc mình đang cần viết prompt", và mỗi lần soạn prompt (dù cho ChatGPT/Claude bên ngoài hay cho các tính năng AI nội bộ như `CoreIdeaExtractor`) đều gõ tự do, dễ thiếu ý (quên nêu đối tượng, quên nêu định dạng đầu ra...).

Module **PromptFrameworkStudio** giải quyết bằng:
1. **Thư viện học** — 13 framework prompt phổ biến, mỗi cái có mô tả, khi nào dùng, và ví dụ điền sẵn.
2. **Form sinh prompt có cấu trúc** — chọn 1 framework, điền lần lượt từng trường đúng theo cấu trúc framework đó, hệ thống ghép thành 1 đoạn prompt hoàn chỉnh, sẵn sàng copy.
3. **Lịch sử/quản lý** — lưu lại các prompt đã sinh, đặt tên, tìm lại, sửa và sinh lại.

**Phi mục tiêu:** không gọi AI để "chấm điểm"/"cải thiện" prompt (v1); không cho tự thêm framework mới qua UI; không tích hợp gửi thẳng prompt đã sinh sang `CoreIdeaExtractor`/`VideoIdeaExtractor` (người dùng tự copy-paste — xem §8).

---

## 2. Danh mục 13 framework (nội dung `config/prompt_framework_studio.php`)

Mỗi framework gồm: `key`, `name`, `description` (mô tả ngắn), `best_for` (khi nào dùng), `fields` (danh sách trường theo đúng thứ tự), `template` (chuỗi ghép có placeholder `{{field_key}}`), `example` (mảng field_key => giá trị mẫu, tự biên soạn).

| Key | Tên đầy đủ | Trường (thứ tự) | Khi nào dùng |
|---|---|---|---|
| `costar` | Context · Objective · Style · Tone · Audience · Response | context, objective, style, tone, audience, response_format | Giao tiếp nghiệp vụ, viết chuyên nghiệp, ra quyết định |
| `risen` | Role · Instructions · Steps · End Goal · Narrowing | role, instructions, steps, end_goal, narrowing | Tác vụ nhiều bước, quy trình, workflow |
| `craft` | Context · Role · Action · Format · Target | context, role, action, format, target | Nội dung marketing, copywriting, sáng tạo |
| `race` | Role · Action · Context · Expectation | role, action, context, expectation | Yêu cầu nhanh gọn, có vai trò rõ ràng |
| `rtf` | Role · Task · Format | role, task, format | Đào tạo nội bộ, nội dung chuẩn hoá, tài liệu giảng dạy |
| `ape` | Action · Purpose · Expectation | action, purpose, expectation | Yêu cầu đơn giản, tác vụ nhanh |
| `tag` | Task · Action · Goal | task, action, goal | Giao việc ngắn gọn, mục tiêu rõ |
| `care` | Context · Action · Result · Example | context, action, result, example | Cần AI học theo 1 ví dụ mẫu cụ thể |
| `crit` | Context · Role · Interview · Task | context, role, interview_questions, task | Muốn AI hỏi lại làm rõ trước khi làm, giảm đoán mò |
| `para` | Problem · Approach · Result · Application | problem, approach, result, application | Phân tích vấn đề, đề xuất giải pháp |
| `specs` | Situation · Purpose · Expected Output · Context · Style | situation, purpose, expected_output, context, style | Dự án phức tạp, phân tích kỹ thuật chi tiết |
| `trace` | Task · Request · Action · Context · Example | task, request, action, context, example | Yêu cầu dựa trên ví dụ, dạy AI theo mẫu |
| `react` | Role · Instructions · Tools · Reasoning · Action · Observation | role, instructions, tools, reasoning, action, observation | Agent nhiều bước, có công cụ, cần AI tự suy luận-hành động-quan sát lặp lại |

Đầy đủ cả 13 framework — **must-fix trước khi code**, không được để dev tự viết `fields`/`template`/`example` (dễ lệch chuẩn, chất lượng ví dụ không đồng đều):

```php
// config/prompt_framework_studio.php
return [
    'frameworks' => [

        'costar' => [
            'name' => 'CO-STAR',
            'description' => 'Ghép 6 khối: bối cảnh, mục tiêu, phong cách, giọng điệu, đối tượng, định dạng phản hồi.',
            'best_for' => 'Giao tiếp nghiệp vụ, viết chuyên nghiệp, hỗ trợ ra quyết định.',
            'fields' => [
                ['key' => 'context', 'label' => 'Context (Bối cảnh)', 'type' => 'textarea', 'required' => true],
                ['key' => 'objective', 'label' => 'Objective (Mục tiêu)', 'type' => 'textarea', 'required' => true],
                ['key' => 'style', 'label' => 'Style (Phong cách viết)', 'type' => 'text', 'required' => false],
                ['key' => 'tone', 'label' => 'Tone (Giọng điệu)', 'type' => 'text', 'required' => false],
                ['key' => 'audience', 'label' => 'Audience (Đối tượng đọc)', 'type' => 'text', 'required' => true],
                ['key' => 'response_format', 'label' => 'Response (Định dạng đầu ra mong muốn)', 'type' => 'text', 'required' => false],
            ],
            'template' => "Context: {{context}}\nObjective: {{objective}}\nStyle: {{style}}\nTone: {{tone}}\nAudience: {{audience}}\nResponse format: {{response_format}}",
            'example' => [
                'context' => 'Chúng tôi vận hành 1 trang tin gia đình, sắp ra bài về quản lý tài chính cho cha mẹ có con nhỏ.',
                'objective' => 'Viết đoạn mở bài (100-150 từ) thu hút phụ huynh đọc tiếp.',
                'style' => 'Gần gũi, dễ hiểu, tránh thuật ngữ tài chính phức tạp.',
                'tone' => 'Ấm áp, đồng cảm, không giáo điều.',
                'audience' => 'Cha mẹ 28-40 tuổi, thu nhập trung bình, ở Việt Nam.',
                'response_format' => 'Đoạn văn liền mạch, không gạch đầu dòng.',
            ],
        ],

        'risen' => [
            'name' => 'RISEN',
            'description' => 'Role · Instructions · Steps · End Goal · Narrowing — giao việc nhiều bước có ràng buộc rõ.',
            'best_for' => 'Tác vụ nhiều bước, quy trình, lập kế hoạch.',
            'fields' => [
                ['key' => 'role', 'label' => 'Role (Vai trò AI đóng)', 'type' => 'text', 'required' => true],
                ['key' => 'instructions', 'label' => 'Instructions (Yêu cầu tổng quát)', 'type' => 'textarea', 'required' => true],
                ['key' => 'steps', 'label' => 'Steps (Các bước cần làm)', 'type' => 'textarea', 'required' => true],
                ['key' => 'end_goal', 'label' => 'End Goal (Kết quả cuối cùng)', 'type' => 'text', 'required' => true],
                ['key' => 'narrowing', 'label' => 'Narrowing (Giới hạn/ràng buộc)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Role: {{role}}\nInstructions: {{instructions}}\nSteps:\n{{steps}}\nEnd goal: {{end_goal}}\nNarrowing/constraints: {{narrowing}}",
            'example' => [
                'role' => 'Bạn là chuyên gia lập kế hoạch nội dung cho website gia đình.',
                'instructions' => 'Lập lịch đăng bài 4 tuần cho chuyên mục "Nuôi dạy con", mỗi tuần 3 bài.',
                'steps' => "1) Liệt kê 12 chủ đề theo độ tuổi con (0-3, 3-6, 6-12 tuổi)\n2) Gắn mỗi chủ đề với 1 ngày đăng cụ thể\n3) Gợi ý tiêu đề chuẩn SEO cho từng bài",
                'end_goal' => 'Một bảng lịch đăng bài đầy đủ, sẵn sàng giao cho đội viết.',
                'narrowing' => 'Không đề xuất chủ đề trùng 2 tháng gần nhất; tránh ngôn ngữ hàn lâm.',
            ],
        ],

        'craft' => [
            'name' => 'CRAFT',
            'description' => 'Context · Role · Action · Format · Target — sáng tạo nội dung, copywriting.',
            'best_for' => 'Nội dung marketing, copywriting, landing page.',
            'fields' => [
                ['key' => 'context', 'label' => 'Context (Bối cảnh)', 'type' => 'textarea', 'required' => true],
                ['key' => 'role', 'label' => 'Role (Vai trò AI đóng)', 'type' => 'text', 'required' => true],
                ['key' => 'action', 'label' => 'Action (Việc cần làm)', 'type' => 'textarea', 'required' => true],
                ['key' => 'format', 'label' => 'Format (Định dạng đầu ra)', 'type' => 'text', 'required' => false],
                ['key' => 'target', 'label' => 'Target (Đối tượng mục tiêu)', 'type' => 'text', 'required' => true],
            ],
            'template' => "Context: {{context}}\nRole: {{role}}\nAction: {{action}}\nFormat: {{format}}\nTarget audience: {{target}}",
            'example' => [
                'context' => 'Trang đích quảng bá khoá học kỹ năng số miễn phí cho phụ huynh.',
                'role' => 'Bạn là copywriter chuyên viết landing page chuyển đổi cao.',
                'action' => 'Viết 1 headline và 1 sub-headline thu hút phụ huynh đăng ký.',
                'format' => 'Headline tối đa 12 từ, sub-headline tối đa 25 từ.',
                'target' => 'Phụ huynh 30-45 tuổi, lo lắng về việc con dùng mạng xã hội an toàn.',
            ],
        ],

        'race' => [
            'name' => 'RACE',
            'description' => 'Role · Action · Context · Expectation — yêu cầu nhanh gọn, có vai trò rõ.',
            'best_for' => 'Yêu cầu ngắn, cần vai trò rõ ràng để AI trả lời đúng góc nhìn.',
            'fields' => [
                ['key' => 'role', 'label' => 'Role (Vai trò AI đóng)', 'type' => 'text', 'required' => true],
                ['key' => 'action', 'label' => 'Action (Việc cần làm)', 'type' => 'textarea', 'required' => true],
                ['key' => 'context', 'label' => 'Context (Bối cảnh)', 'type' => 'textarea', 'required' => false],
                ['key' => 'expectation', 'label' => 'Expectation (Kỳ vọng kết quả)', 'type' => 'text', 'required' => true],
            ],
            'template' => "Role: {{role}}\nAction: {{action}}\nContext: {{context}}\nExpectation: {{expectation}}",
            'example' => [
                'role' => 'Bạn là biên tập viên SEO.',
                'action' => 'Viết lại tiêu đề bài viết sau cho chuẩn SEO.',
                'context' => "Tiêu đề gốc: \"5 mẹo giúp con học tốt hơn\". Từ khoá mục tiêu: \"phương pháp học tập cho trẻ tiểu học\".",
                'expectation' => '3 phương án tiêu đề, mỗi tiêu đề dưới 60 ký tự, chứa từ khoá chính.',
            ],
        ],

        'rtf' => [
            'name' => 'RTF',
            'description' => 'Role · Task · Format — cực ngắn gọn, đủ 3 khối cốt lõi.',
            'best_for' => 'Đào tạo nội bộ, nội dung chuẩn hoá, tài liệu giảng dạy.',
            'fields' => [
                ['key' => 'role', 'label' => 'Role (Vai trò AI đóng)', 'type' => 'text', 'required' => true],
                ['key' => 'task', 'label' => 'Task (Nhiệm vụ)', 'type' => 'textarea', 'required' => true],
                ['key' => 'format', 'label' => 'Format (Định dạng đầu ra)', 'type' => 'text', 'required' => true],
            ],
            'template' => "Role: {{role}}\nTask: {{task}}\nFormat: {{format}}",
            'example' => [
                'role' => 'Bạn là giảng viên đào tạo nội bộ.',
                'task' => 'Soạn tài liệu hướng dẫn quy trình duyệt bài viết cho biên tập viên mới.',
                'format' => 'Dạng checklist đánh số, tối đa 10 bước, mỗi bước 1 câu.',
            ],
        ],

        'ape' => [
            'name' => 'APE',
            'description' => 'Action · Purpose · Expectation — yêu cầu đơn giản, tác vụ nhanh.',
            'best_for' => 'Yêu cầu đơn giản, việc lặt vặt hằng ngày.',
            'fields' => [
                ['key' => 'action', 'label' => 'Action (Việc cần làm)', 'type' => 'textarea', 'required' => true],
                ['key' => 'purpose', 'label' => 'Purpose (Mục đích dùng để làm gì)', 'type' => 'text', 'required' => true],
                ['key' => 'expectation', 'label' => 'Expectation (Kỳ vọng kết quả)', 'type' => 'text', 'required' => false],
            ],
            'template' => "Action: {{action}}\nPurpose: {{purpose}}\nExpectation: {{expectation}}",
            'example' => [
                'action' => 'Tóm tắt bản báo cáo khảo sát phụ huynh đính kèm.',
                'purpose' => 'Dùng cho slide trình bày trong họp giao ban.',
                'expectation' => 'Tối đa 5 gạch đầu dòng, mỗi dòng nêu 1 số liệu quan trọng.',
            ],
        ],

        'tag' => [
            'name' => 'TAG',
            'description' => 'Task · Action · Goal — giao việc ngắn gọn, mục tiêu rõ.',
            'best_for' => 'Giao việc ngắn, không cần nhiều ngữ cảnh.',
            'fields' => [
                ['key' => 'task', 'label' => 'Task (Nhiệm vụ)', 'type' => 'text', 'required' => true],
                ['key' => 'action', 'label' => 'Action (Hành động cụ thể)', 'type' => 'textarea', 'required' => true],
                ['key' => 'goal', 'label' => 'Goal (Mục tiêu hướng tới)', 'type' => 'text', 'required' => true],
            ],
            'template' => "Task: {{task}}\nAction: {{action}}\nGoal: {{goal}}",
            'example' => [
                'task' => 'Trả lời bình luận độc giả trên bài viết về dinh dưỡng trẻ em.',
                'action' => 'Soạn 1 câu trả lời ngắn gọn, thân thiện, trích dẫn 1 nguồn uy tín.',
                'goal' => 'Giữ chân độc giả tương tác thêm, không gây tranh cãi.',
            ],
        ],

        'care' => [
            'name' => 'CARE',
            'description' => 'Context · Action · Result · Example — cần AI học theo 1 ví dụ mẫu cụ thể.',
            'best_for' => 'Muốn AI bắt chước đúng phong cách/mẫu có sẵn.',
            'fields' => [
                ['key' => 'context', 'label' => 'Context (Bối cảnh)', 'type' => 'textarea', 'required' => true],
                ['key' => 'action', 'label' => 'Action (Việc cần làm)', 'type' => 'textarea', 'required' => true],
                ['key' => 'result', 'label' => 'Result (Kết quả mong muốn)', 'type' => 'text', 'required' => true],
                ['key' => 'example', 'label' => 'Example (Ví dụ mẫu để bắt chước theo)', 'type' => 'textarea', 'required' => true],
            ],
            'template' => "Context: {{context}}\nAction: {{action}}\nDesired result: {{result}}\nExample to follow:\n{{example}}",
            'example' => [
                'context' => 'Chúng tôi muốn viết caption Facebook quảng bá bài viết mới.',
                'action' => 'Viết 1 caption theo đúng phong cách của ví dụ bên dưới.',
                'result' => 'Caption dưới 200 ký tự, có 1 câu hỏi mở ở cuối để tăng tương tác.',
                'example' => 'Con lười ăn rau? Đừng lo, đây là 3 cách biến rau thành món khoái khẩu của bé! 👉 Đọc ngay: [link]. Mẹ đã thử cách nào trong 3 cách này chưa?',
            ],
        ],

        'crit' => [
            'name' => 'CRIT',
            'description' => 'Context · Role · Interview · Task — AI hỏi lại làm rõ trước khi làm, giảm đoán mò.',
            'best_for' => 'Yêu cầu còn mơ hồ, cần AI hỏi lại trước khi bắt tay vào việc.',
            'fields' => [
                ['key' => 'context', 'label' => 'Context (Bối cảnh)', 'type' => 'textarea', 'required' => true],
                ['key' => 'role', 'label' => 'Role (Vai trò AI đóng)', 'type' => 'text', 'required' => true],
                ['key' => 'interview_questions', 'label' => 'Interview (Câu hỏi AI cần hỏi lại trước khi làm)', 'type' => 'textarea', 'required' => true],
                ['key' => 'task', 'label' => 'Task (Việc làm sau khi có câu trả lời)', 'type' => 'textarea', 'required' => true],
            ],
            'template' => "Context: {{context}}\nRole: {{role}}\nBefore starting, ask me these questions:\n{{interview_questions}}\nThen: {{task}}",
            'example' => [
                'context' => 'Chúng tôi muốn viết 1 bài so sánh 2 loại bảo hiểm nhân thọ cho gia đình trẻ.',
                'role' => 'Bạn là chuyên gia tư vấn tài chính gia đình.',
                'interview_questions' => "1) 2 sản phẩm bảo hiểm cụ thể cần so sánh là gì?\n2) Đối tượng đọc ưu tiên độ tuổi nào?\n3) Bài viết cần trung lập tuyệt đối hay được nghiêng về 1 sản phẩm?",
                'task' => 'Sau khi có câu trả lời, viết dàn ý bài so sánh khoảng 800 từ.',
            ],
        ],

        'para' => [
            'name' => 'PARA',
            'description' => 'Problem · Approach · Result · Application — phân tích vấn đề, đề xuất giải pháp.',
            'best_for' => 'Phân tích dữ liệu/vấn đề, tìm nguyên nhân, đề xuất hành động.',
            'fields' => [
                ['key' => 'problem', 'label' => 'Problem (Vấn đề đang gặp)', 'type' => 'textarea', 'required' => true],
                ['key' => 'approach', 'label' => 'Approach (Cách tiếp cận phân tích)', 'type' => 'textarea', 'required' => true],
                ['key' => 'result', 'label' => 'Result (Kết quả mong muốn nhận được)', 'type' => 'text', 'required' => true],
                ['key' => 'application', 'label' => 'Application (Áp dụng kết quả vào đâu)', 'type' => 'text', 'required' => false],
            ],
            'template' => "Problem: {{problem}}\nApproach: {{approach}}\nDesired result: {{result}}\nApplication: {{application}}",
            'example' => [
                'problem' => 'Tỷ lệ đọc hết bài (dưới 40%) đang thấp ở chuyên mục Sức khoẻ gia đình.',
                'approach' => 'Phân tích 5 bài có tỷ lệ đọc hết cao nhất và 5 bài thấp nhất để tìm điểm khác biệt.',
                'result' => 'Danh sách 3-5 nguyên nhân khả dĩ, sắp theo mức độ ảnh hưởng.',
                'application' => 'Áp dụng ngay cho 3 bài sắp xuất bản tuần này.',
            ],
        ],

        'specs' => [
            'name' => 'SPECS',
            'description' => 'Situation · Purpose · Expected Output · Context · Style — dự án phức tạp, tài liệu kỹ thuật.',
            'best_for' => 'Phân tích/viết tài liệu kỹ thuật chi tiết, dự án nhiều ràng buộc.',
            'fields' => [
                ['key' => 'situation', 'label' => 'Situation (Tình huống hiện tại)', 'type' => 'textarea', 'required' => true],
                ['key' => 'purpose', 'label' => 'Purpose (Mục đích)', 'type' => 'textarea', 'required' => true],
                ['key' => 'expected_output', 'label' => 'Expected Output (Đầu ra kỳ vọng)', 'type' => 'text', 'required' => true],
                ['key' => 'context', 'label' => 'Context (Ràng buộc/ngữ cảnh thêm)', 'type' => 'textarea', 'required' => false],
                ['key' => 'style', 'label' => 'Style (Văn phong)', 'type' => 'text', 'required' => false],
            ],
            'template' => "Situation: {{situation}}\nPurpose: {{purpose}}\nExpected output: {{expected_output}}\nContext: {{context}}\nStyle: {{style}}",
            'example' => [
                'situation' => 'Website đang chuyển đổi hệ thống quản lý bài viết sang module mới.',
                'purpose' => 'Cần tài liệu hướng dẫn nội bộ cho đội biên tập làm quen hệ thống mới.',
                'expected_output' => '1 tài liệu Markdown có mục lục, ảnh minh hoạ placeholder.',
                'context' => 'Đội biên tập không rành kỹ thuật, đã quen giao diện cũ trong 2 năm.',
                'style' => 'Ngôn ngữ đơn giản, từng bước, tránh thuật ngữ lập trình.',
            ],
        ],

        'trace' => [
            'name' => 'TRACE',
            'description' => 'Task · Request · Action · Context · Example — yêu cầu dựa trên ví dụ, dạy AI theo mẫu.',
            'best_for' => 'Muốn AI học theo 1 ví dụ đầu ra cụ thể trước khi làm việc thật.',
            'fields' => [
                ['key' => 'task', 'label' => 'Task (Nhiệm vụ tổng quát)', 'type' => 'text', 'required' => true],
                ['key' => 'request', 'label' => 'Request (Yêu cầu cụ thể)', 'type' => 'textarea', 'required' => true],
                ['key' => 'action', 'label' => 'Action (Cách thực hiện)', 'type' => 'text', 'required' => false],
                ['key' => 'context', 'label' => 'Context (Bối cảnh)', 'type' => 'textarea', 'required' => false],
                ['key' => 'example', 'label' => 'Example (Ví dụ mẫu)', 'type' => 'textarea', 'required' => true],
            ],
            'template' => "Task: {{task}}\nRequest: {{request}}\nAction: {{action}}\nContext: {{context}}\nExample:\n{{example}}",
            'example' => [
                'task' => 'Viết mô tả ngắn (meta description) cho bài viết.',
                'request' => 'Viết 1 meta description 150-160 ký tự cho bài viết chủ đề "dạy con quản lý tiền tiêu vặt".',
                'action' => 'Nêu bật lợi ích cụ thể, có lời kêu gọi đọc tiếp.',
                'context' => 'Bài viết nhắm phụ huynh có con 8-12 tuổi.',
                'example' => 'Dạy con quản lý tiền tiêu vặt từ 8 tuổi: 5 bước đơn giản giúp con hình thành thói quen tiết kiệm suốt đời. Xem ngay!',
            ],
        ],

        'react' => [
            'name' => 'ReAct',
            'description' => 'Role · Instructions · Tools · Reasoning · Action · Observation — agent nhiều bước, có công cụ.',
            'best_for' => 'Nhiệm vụ cần AI tự suy luận-hành động-quan sát lặp lại, có dùng công cụ ngoài.',
            'fields' => [
                ['key' => 'role', 'label' => 'Role (Vai trò AI đóng)', 'type' => 'text', 'required' => true],
                ['key' => 'instructions', 'label' => 'Instructions (Nhiệm vụ tổng quát)', 'type' => 'textarea', 'required' => true],
                ['key' => 'tools', 'label' => 'Tools (Công cụ được phép dùng)', 'type' => 'text', 'required' => true],
                ['key' => 'reasoning', 'label' => 'Reasoning (Yêu cầu về cách suy luận)', 'type' => 'textarea', 'required' => false],
                ['key' => 'action', 'label' => 'Action (Hành động cụ thể cần thực hiện)', 'type' => 'textarea', 'required' => true],
                ['key' => 'observation', 'label' => 'Observation (Cách đánh giá kết quả sau mỗi bước)', 'type' => 'text', 'required' => false],
            ],
            'template' => "Role: {{role}}\nInstructions: {{instructions}}\nTools available: {{tools}}\nReasoning: {{reasoning}}\nAction: {{action}}\nObservation: {{observation}}",
            'example' => [
                'role' => 'Bạn là trợ lý nghiên cứu nội dung, có thể dùng công cụ tìm kiếm.',
                'instructions' => 'Tìm 3 nguồn số liệu uy tín về tỷ lệ trẻ em Việt Nam dùng mạng xã hội trước 13 tuổi.',
                'tools' => 'Công cụ tìm kiếm web, công cụ đọc PDF.',
                'reasoning' => 'Trước mỗi lần tìm kiếm, nêu rõ đang tìm gì và vì sao.',
                'action' => 'Thực hiện tìm kiếm, trích dẫn nguồn kèm link.',
                'observation' => 'Sau mỗi kết quả, đánh giá độ tin cậy nguồn trước khi dùng tiếp.',
            ],
        ],

    ],
];
```

`type` mỗi field chỉ nhận `text` (input 1 dòng) hoặc `textarea` — không cần loại field phức tạp hơn cho v1. Field không `required` mà người dùng để trống sẽ được thay bằng chuỗi rỗng khi ghép (§4.1) — chấp nhận dòng nhãn không có nội dung theo sau (vd `Style: `) ở v1, không tự động lược bỏ dòng trống (xem §7 — để v1.1 kế tiếp nếu cần polish).

---

## 3. Kiến trúc dữ liệu

### 3.1 Migration

```php
Schema::create('generated_prompts', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique(); // route key — cùng quy ước PostCategory/ContentOutline

    $table->string('framework_key', 30); // khớp key trong config('prompt_framework_studio.frameworks') — validate ở FormRequest (§5.1), KHÔNG FK vì nguồn là config chứ không phải bảng
    $table->string('label', 150); // tên người dùng tự đặt để nhận diện trong danh sách quản lý (vd "Mở bài blog tài chính gia đình")
    $table->json('field_values'); // {field_key: giá trị} — dùng để tải lại form khi sửa/sinh lại
    $table->longText('rendered_prompt'); // kết quả ghép cuối cùng — ghi đè khi "Sinh lại" (không versioning, cùng quyết định ContentOutline.generated_prompt)

    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();

    $table->index('framework_key');
    $table->index('created_at');
    $table->index('label'); // trang quản lý (§4.3) tìm/sắp theo tên người dùng tự đặt — tăng dần theo số lượng prompt đã lưu
});
```

Không soft-delete, không activity log (xem §0).

### 3.2 Model

```php
namespace Modules\PromptFrameworkStudio\Models;

class GeneratedPrompt extends Model
{
    protected $table = 'generated_prompts';

    protected $fillable = [
        'uuid', 'framework_key', 'label', 'field_values', 'rendered_prompt',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'field_values' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
}
```

---

## 4. Cấu trúc module (Features/)

```
Modules/PromptFrameworkStudio/
├── app/
│   ├── Features/
│   │   ├── FrameworkLibrary/
│   │   │   └── Http/FrameworkLibraryController.php      — GET /dashboard/prompt-studio/library (đọc config, render danh sách + ví dụ)
│   │   └── PromptGeneration/
│   │       ├── Actions/
│   │       │   ├── RenderPromptFromFrameworkAction.php  — nhận framework_key + field_values, trả rendered_prompt (strtr template, thiếu field = chuỗi rỗng)
│   │       │   ├── CreateGeneratedPromptAction.php       — gọi Action trên rồi lưu bản ghi mới
│   │       │   └── RegenerateGeneratedPromptAction.php   — cập nhật field_values + ghi đè rendered_prompt của bản ghi có sẵn
│   │       └── Http/
│   │           ├── PromptGenerationController.php        — index (form chọn framework), create (form field động), store, edit, update, destroy
│   │           └── Requests/StoreGeneratedPromptRequest.php — validate framework_key ∈ config keys; field_values theo đúng field khai báo của framework đó (required fields bắt buộc)
│   ├── Models/GeneratedPrompt.php
│   └── Providers/{PromptFrameworkStudioServiceProvider,RouteServiceProvider}.php
├── config/prompt_framework_studio.php
├── database/
│   ├── migrations/..._create_generated_prompts_table.php
│   └── seeders/PromptFrameworkStudioPermissionSeeder.php
├── resources/views/
│   ├── library/index.blade.php     — thư viện học (đọc config)
│   ├── prompts/index.blade.php     — danh sách đã sinh (quản lý) — Tabulator, cùng pattern các module khác
│   ├── prompts/create.blade.php    — bước 1: chọn framework (grid 13 thẻ) → bước 2: form field động (Alpine, hiện field theo framework chọn)
│   └── prompts/edit.blade.php
├── routes/web.php
└── module.json
```

### 4.1 `RenderPromptFromFrameworkAction` — logic ghép chuỗi

```php
class RenderPromptFromFrameworkAction
{
    use AsAction;

    public function handle(string $frameworkKey, array $fieldValues): string
    {
        $framework = config("prompt_framework_studio.frameworks.{$frameworkKey}");
        abort_if(! $framework, 422, 'Framework không tồn tại.');

        $replacements = [];
        foreach ($framework['fields'] as $field) {
            $replacements['{{'.$field['key'].'}}'] = trim((string) ($fieldValues[$field['key']] ?? ''));
        }

        return strtr($framework['template'], $replacements);
    }
}
```

Không dùng Blade template engine cho bước này (không cần logic điều kiện trong template, `strtr` đủ và tránh rủi ro injection cú pháp Blade từ dữ liệu người dùng).

`abort_if(! $framework, 422, ...)` bên trong `RenderPromptFromFrameworkAction::handle()` (đã có ở trên) là nơi kiểm tra **duy nhất và bắt buộc** cho việc framework có tồn tại hay không — `CreateGeneratedPromptAction` và `RegenerateGeneratedPromptAction` đều gọi xuyên qua `RenderPromptFromFrameworkAction` để lấy `rendered_prompt` (không tự ghép chuỗi riêng), nên cả 2 **tự động thừa hưởng** guard này mà không cần lặp lại kiểm tra ở từng Action. Đây là lý do §5.4 khẳng định `update`/`RegenerateGeneratedPromptAction` "từ chối với lỗi 422" kể cả khi bị gọi thẳng, không đi qua `edit()`: bản thân `RegenerateGeneratedPromptAction::handle()` luôn gọi `RenderPromptFromFrameworkAction::run($prompt->framework_key, $newFieldValues)` trước khi lưu, nên orphaned framework tự nhiên bị chặn ở đúng 1 chỗ, không phải nhớ thêm `if` riêng ở Controller lẫn Action (defense-in-depth mà không trùng lặp logic).

### 4.2 Truyền dữ liệu framework xuống Alpine (`resources/views/prompts/create.blade.php` và `edit.blade.php`)

```blade
<div
    x-data="promptGenerator(@json(config('prompt_framework_studio.frameworks')))"
    x-init="init()"
>
    {{-- Bước 1: lưới 13 thẻ chọn framework --}}
    <template x-for="(fw, key) in frameworks" :key="key">
        <button type="button" @click="select(key)" x-text="fw.name"></button>
    </template>

    {{-- Bước 2: form field động theo framework đã chọn --}}
    <template x-if="selectedKey">
        <div>
            <template x-for="field in frameworks[selectedKey].fields" :key="field.key">
                <div>
                    <label x-text="field.label"></label>
                    <textarea x-show="field.type === 'textarea'" x-model="values[field.key]"></textarea>
                    <input x-show="field.type === 'text'" x-model="values[field.key]" type="text" />
                </div>
            </template>
        </div>
    </template>

    {{-- input ẩn để submit form Laravel bình thường (không AJAX) --}}
    <input type="hidden" name="framework_key" :value="selectedKey">
    <template x-for="field in (frameworks[selectedKey]?.fields ?? [])" :key="field.key">
        <input type="hidden" :name="`field_values[${field.key}]`" :value="values[field.key]">
    </template>
</div>
```

```js
// resources/assets/js/prompt-framework-studio.js
// initialKey/initialValues: null ở trang create; ở trang edit truyền
// promptGenerator(@json($frameworks), @json($prompt->framework_key), @json($prompt->field_values))
function promptGenerator(frameworks, initialKey = null, initialValues = null) {
    return {
        frameworks,
        selectedKey: initialKey,
        values: initialValues ?? {},
        init() {
            // Trang edit: framework đã biết trước (không đổi được — §5.3), chỉ cần đảm bảo mọi field
            // của framework đó đều có key trong `values` (kể cả field optional chưa từng điền trước
            // đây) để x-model không bị "undefined" khi field mới được thêm vào framework sau này.
            if (this.selectedKey && this.frameworks[this.selectedKey]) {
                for (const field of this.frameworks[this.selectedKey].fields) {
                    if (!(field.key in this.values)) this.values[field.key] = '';
                }
            }
        },
        select(key) {
            this.selectedKey = key;
            this.values = Object.fromEntries(frameworks[key].fields.map(f => [f.key, '']));
        },
    };
}
```

Ở `edit.blade.php`, KHÔNG render lưới chọn framework (bước 1) — chỉ render thẳng bước 2 (form field) vì `selectedKey` đã cố định từ `initialKey`, đúng quyết định "framework không đổi được sau khi tạo" (§5.3). Trang `edit` chỉ dựng được khi `config("prompt_framework_studio.frameworks.{$prompt->framework_key}")` tồn tại — trường hợp orphaned rẽ sang view read-only khác hẳn (§5.4), không tái sử dụng `x-data="promptGenerator(...)"` này.

`@json()` nhúng thẳng toàn bộ config vào HTML lúc render (server-side, không phải AJAX) — dữ liệu tĩnh, không đổi theo request nên không cần endpoint JSON riêng (§0). Form submit theo kiểu POST thường (không AJAX) qua các input ẩn được Alpine đồng bộ — giữ `StoreGeneratedPromptRequest` (§5.1) xử lý y hệt 1 form HTML thường, không cần thêm code nhận JSON riêng.

### 4.3 Cột Tabulator — `prompts/index.blade.php` (danh sách quản lý)

| Cột | Nguồn dữ liệu | Ghi chú |
|---|---|---|
| Tên prompt | `label` | Link tới `edit` (hoặc `show` nếu orphaned — §5.4) |
| Framework | `config("prompt_framework_studio.frameworks.{$framework_key}.name")` | Nếu key không còn trong config: hiện `framework_key` thô kèm badge "Đã gỡ" (§5.4) |
| Người tạo | `createdBy.name` | |
| Cập nhật lần cuối | `updated_at` (format `d/m/Y H:i`) | Sort mặc định giảm dần |
| Thao tác | Xem / Sửa / Xoá | Nút "Sửa" ẩn nếu orphaned (§5.4) |

Endpoint JSON cho Tabulator: `GET backend/api/prompt-studio/prompts` (cùng pattern `N8nLogApiController`/`backend/api/n8n/logs/*`), phân trang server-side, filter theo `framework_key`/`label` (tìm kiếm chuỗi con qua `label`, tận dụng `index('label')` ở §3.1).

---

## 5. Validate & luồng nghiệp vụ

### 5.1 `StoreGeneratedPromptRequest`

```php
public function rules(): array
{
    $frameworkKey = $this->input('framework_key');
    $framework = config("prompt_framework_studio.frameworks.{$frameworkKey}");

    $rules = [
        'framework_key' => ['required', 'string', Rule::in(array_keys(config('prompt_framework_studio.frameworks')))],
        'label' => ['required', 'string', 'max:150'],
        'field_values' => ['required', 'array'],
    ];

    if ($framework) {
        foreach ($framework['fields'] as $field) {
            $rules["field_values.{$field['key']}"] = $field['required']
                ? ['required', 'string', 'max:5000']
                : ['nullable', 'string', 'max:5000'];
        }
    }

    return $rules;
}
```

### 5.2 Luồng tạo mới

1. Người dùng vào `/dashboard/prompt-studio/prompts/create` → chọn 1 trong 13 thẻ framework (mỗi thẻ hiện `name` + `description` ngắn, đọc từ config).
2. Alpine.js hiện form field động theo đúng `fields` của framework chọn (không reload trang).
3. Submit → `StoreGeneratedPromptRequest` validate → `CreateGeneratedPromptAction` gọi `RenderPromptFromFrameworkAction` → lưu `GeneratedPrompt` với `rendered_prompt` đã ghép, `created_by = auth()->id()`.
4. Trang kết quả hiện `rendered_prompt` trong `<textarea readonly>` + nút "Copy" (JS `navigator.clipboard`).

### 5.3 Luồng sửa/sinh lại

`edit` tải lại `field_values` cũ vào đúng form của `framework_key` đã lưu (framework không đổi được sau khi tạo — muốn dùng framework khác thì tạo bản ghi mới) → `RegenerateGeneratedPromptAction` ghi đè `rendered_prompt` + `updated_by`.

### 5.4 Framework bị gỡ khỏi config (orphaned `framework_key`)

Config là nguồn duy nhất cho `fields`/`template` (§0) — nếu dev xoá 1 key khỏi `config/prompt_framework_studio.php` sau khi đã có `GeneratedPrompt` dùng key đó, các bản ghi cũ **không được để vỡ trang** (lỗi 500/trang trắng khi bấm "Sửa"). Quyết định:

- `PromptGenerationController::edit()` kiểm tra `config("prompt_framework_studio.frameworks.{$prompt->framework_key}")` **trước** khi render form. Nếu `null` (orphaned):
  - Chuyển hướng sang view **read-only** (`prompts/show.blade.php`, không phải `edit.blade.php`) — hiện `label`, `rendered_prompt` (readonly, vẫn copy được), và 1 banner cảnh báo: *"Framework '{{ $prompt->framework_key }}' đã bị gỡ khỏi hệ thống — không thể sửa hoặc sinh lại. Bạn vẫn có thể xem và sao chép nội dung đã lưu, hoặc xoá bản ghi này."*
  - Route `update`/`RegenerateGeneratedPromptAction` **từ chối** với lỗi 422 rõ ràng nếu vẫn có request gọi tới (phòng trường hợp gọi thẳng API, không chỉ qua UI) — không âm thầm bỏ qua.
  - `destroy` (xoá) **vẫn hoạt động bình thường** — orphaned không cản việc dọn dữ liệu cũ.
- Danh sách quản lý (§4.3) đánh dấu các dòng orphaned bằng badge "Đã gỡ" cạnh tên framework, giúp nhận diện ngay từ danh sách mà không cần mở từng bản ghi.
- **Không** tự động xoá bản ghi khi framework bị gỡ khỏi config — quyết định xoá hay giữ lại là của người dùng, hệ thống không tự ý mất dữ liệu.

---

## 6. RBAC

```php
// database/seeders/PromptFrameworkStudioPermissionSeeder.php — cùng khuôn ContentOutlinesPermissionSeeder
private const PERMISSIONS = ['prompt_framework_studio.use'];
private const ROLES_WITH_ACCESS = [
    'platform_content_editor',
    'platform_content_head',
    'platform_section_editor',
];
```

`super-admin` luôn full quyền qua `syncPermissions(Permission::all())` (cùng mẫu mọi seeder khác). Route group `middleware(['auth', 'permission:prompt_framework_studio.use'])`, `prefix('dashboard/prompt-studio')`.

Trang **Thư viện** (`library/index`) có thể mở rộng quyền xem sau (vd cho mọi role đăng nhập) nếu cần dùng làm tài liệu đào tạo chung — v1 dùng chung 1 permission cho cả thư viện lẫn form sinh prompt để đơn giản.

---

## 7. Ngoài phạm vi (v1)

- Không có nút "AI gợi ý framework phù hợp nhất" — cần mô tả mục tiêu bằng ngôn ngữ tự nhiên rồi AI chấm, đây là tính năng AI thật (Layer 2), để lại cho spec version sau nếu có nhu cầu.
- Không tích hợp 1-click "gửi prompt này sang CoreIdeaExtractor/VideoIdeaExtractor" — copy-paste thủ công ở v1.
- Không cho người dùng tự định nghĩa framework mới qua UI (chỉ dev sửa config).
- Không có chấm điểm/so sánh chất lượng giữa các phiên bản prompt đã sinh.
- **Live preview khi gõ** (xem `rendered_prompt` cập nhật real-time trong lúc điền form, trước khi submit) — để bản kế tiếp; v1 chỉ hiện kết quả sau khi submit.
- ~~Nút "Dùng framework này" ngay tại trang Thư viện~~ — **đã làm ở v1.3** (xem changelog v1.3), không còn là việc để sau.
- **Tự động lược bỏ dòng nhãn trống** khi field optional không điền (vd ẩn hẳn dòng `Style: ` nếu để trống thay vì hiện nhãn không có nội dung) — để bản kế tiếp, v1 chấp nhận hiện nguyên nhãn trống (§2).
- **Lưu ý dài hạn (không phải việc phải làm ở v1):** giả định nền tảng "chỉ chính người tạo nhìn thấy `rendered_prompt`" (§0, dòng chống prompt-injection) sẽ **không còn đúng** nếu sau này thêm tính năng chia sẻ prompt giữa các thành viên hoặc gửi thẳng sang 1 module AI khác — lúc đó phải xét lại việc bọc delimiter theo quy ước CLAUDE.md, vì nội dung field lúc đó có thể bị 1 người dùng khác/1 AI khác tiêu thụ.

---

## 8. Testing

- `RenderPromptFromFrameworkActionTest`: mỗi 1 trong 13 framework — field đủ, field thiếu (thay bằng rỗng), framework_key không tồn tại (abort 422).
- `PromptGenerationControllerTest`:
  - Tạo mới; validate required field theo đúng framework (kể cả framework có field optional như `costar.style`).
  - Field optional để trống → `rendered_prompt` vẫn sinh ra, dòng nhãn tương ứng để trống (không lỗi).
  - Sửa + sinh lại (`RegenerateGeneratedPromptAction`) ghi đè đúng `rendered_prompt` và `updated_by`, **`uuid` và `framework_key` không đổi**, các field không được gửi lại trong request giữ nguyên giá trị cũ (không bị xoá về rỗng).
  - Truy cập `edit`/`update` một bản ghi có `framework_key` không còn trong config → chuyển hướng view read-only (§5.4), `update` trả 422 nếu gọi trực tiếp.
  - `destroy`: xoá thành công kể cả khi orphaned; phân quyền — role không có `prompt_framework_studio.use` bị chặn ở mọi action (index/create/store/edit/update/destroy).
- `FrameworkLibraryControllerTest`: render đủ 13 framework từ config, không lỗi khi thiếu `example`.
