<?php

// spec/PromptFrameworkStudio_Technical_Specification.md §2 — nguồn DUY NHẤT cho danh mục
// framework (fields/template/example). KHÔNG DB, KHÔNG CRUD admin (§0) — thêm/sửa framework là
// việc của dev (thêm 1 phần tử + deploy), không cần màn hình quản trị cho việc hiếm khi xảy ra.
//
// Mỗi framework: name, description, best_for, fields (THỨ TỰ TRONG MẢNG CHÍNH LÀ thứ tự canon của
// framework — dùng cho cả form nhập lẫn thứ tự khối trong prompt sinh ra, KHÔNG sắp xếp lại),
// example (giá trị mẫu tự biên soạn, KHÔNG sao chép nguyên văn từ nguồn tham khảo — xem §0 dòng
// "Ví dụ mẫu").
//
// KHÔNG CÒN key `template` (gỡ ở v2.7): trước đây là 1 chuỗi phẳng `"Context: {{context}}\n..."`
// ghép bằng strtr, kéo theo lỗi in nhãn cụt khi field optional để trống và không có chỗ chèn ngữ
// cảnh biên tập. Nay RenderPromptFromFrameworkAction dựng khối Markdown từ `fields` — xem docblock
// Action đó. Giữ lại `template` sẽ thành cấu hình chết mà nhìn như còn sống (sửa nó không có tác
// dụng gì), nên đã gỡ hẳn.
//
// Mỗi field: key, label (TÊN NGẮN — thuật ngữ framework, hiển thị trên ô nhập, KHÔNG nhồi giải
// thích vào đây), hint (câu giải thích/gợi ý — hiển thị THƯỜNG TRỰC dưới label, KHÔNG dùng
// placeholder cho việc này vì placeholder biến mất ngay khi gõ ký tự đầu tiên), tip (optional, chỉ
// 1 field/framework — field ảnh hưởng nhiều nhất tới chất lượng: hệ quả nếu điền mơ hồ + 1 phép
// thử pass/fail), prompt_heading (nhãn khối `## ` trong prompt SINH RA — khác `label` dùng cho UI;
// suy ra từ chính chuỗi `template` cũ nên giữ nguyên ngữ nghĩa gốc, vd 'Narrowing/constraints'
// thay vì nhãn UI rút gọn 'Narrowing'. Vắng mặt = in nguyên văn không bọc `## `, hiện chỉ
// `freeform.text`), type, required. Ví dụ mẫu cụ thể nằm ở `example[field.key]`, dùng làm placeholder.
//
// (2026-08-11, nguồn spec/giadinh.md — "ChatGPT Prompts for Content Marketing", thư viện 30
// prompt content-marketing dạng <context>/<task>) — 2 key MỚI, chỉ dùng cho nhóm "Chiến lược nội
// dung" bên dưới, KHÔNG áp cho 18 framework prompt-engineering tổng quát ở trên (khác biệt có chủ
// đích, xem §0 mục "Kết luận" của giadinh.md gap-analysis): framework tổng quát không có nhiệm vụ
// cố định — model tự suy luận việc cần làm từ chính nội dung field; 5 mục "Chiến lược nội dung"
// dưới đây LÀ nhiệm vụ cố định có sẵn (dịch/biên tập lại từ nguồn, KHÔNG sao chép nguyên văn — cùng
// nguyên tắc "Ví dụ mẫu" ở trên), người dùng chỉ cung cấp tham số tình huống qua `fields`.
//   - group: nhãn nhóm hiển thị ở trang Thư viện (mặc định coi là 'Framework prompt engineering
//     tổng quát' khi vắng mặt — xem FrameworkLibraryController).
//   - task_instructions: mảng các nhiệm vụ đánh số cố định, in ở khối `## Nhiệm vụ` SAU field
//     blocks — xem RenderPromptFromFrameworkAction. Câu cuối cùng trong mảng luôn là chỉ dẫn định
//     dạng đầu ra (tương đương "Return as..." của nguồn).
//
// spec/AIIdeaMatrixGenerator.md §2.1 — field `type` mới: `select` (thêm cùng preset
// `heritage_idea_matrix`, nhóm "Ý tưởng theo Ma trận"). Field `select` PHẢI có thêm key `options`
// (mảng `khoá => nhãn`) — giá trị NGƯỜI DÙNG chọn/lưu là KHOÁ, nhưng `RenderPromptFromFrameworkAction`
// dịch sang NHÃN trước khi chèn vào prompt (khoá thô vô nghĩa với AI đọc prompt). `Store/
// UpdateGeneratedPromptRequest` validate khoá gửi lên PHẢI nằm trong `array_keys($field['options'])`
// (`Rule::in`) — chặn giá trị rác ở tầng input, không chỉ dựa vào fallback ở tầng render.
//
// spec/AIIdeaMatrixGenerator.md §2.5 (v2.1) — key phụ `allow_custom` (chỉ cho field `select`):
// cho phép NHẬP TỰ DO ngoài `options` — UI hiện thêm lựa chọn "✏️ Khác (tự nhập)…" (sentinel
// `__custom__`, KHÔNG đặt khoá option nào trùng tên này) mở ô text; `Rule::in` KHÔNG áp dụng cho
// field có `allow_custom`; giá trị tự nhập render NGUYÊN VĂN qua fallback `?? $value` của
// RenderPromptFromFrameworkAction (với field này, fallback là ĐƯỜNG CHÍNH, không chỉ phòng thủ).
// Dùng cho field mang bản chất BIẾN SỐ biên tập (danh sách chỉ là gợi ý + nguồn Randomize, không
// phải biên giới) — KHÔNG dùng cho field mang bản chất HẰNG SỐ cấu trúc (VD `format` của
// heritage_idea_matrix: mỗi khoá ngầm định 1 khung kịch bản, mở tự do sẽ phá vai trò hằng số).
return [
    'frameworks' => [

        'costar' => [
            'name' => 'CO-STAR',
            'description' => 'Ghép 6 khối: bối cảnh, mục tiêu, phong cách, giọng điệu, đối tượng, định dạng phản hồi (+ Rules tuỳ chọn) — do GovTech Singapore phát triển, vô địch cuộc thi prompt engineering GPT-4 năm 2023.',
            'best_for' => 'Viết nội dung (blog, marketing, email, mạng xã hội, tài liệu) khi hiểu đối tượng đọc và giọng văn là yếu tố quan trọng nhất. Vượt trội hơn RACE khi cần chú trọng phong cách giao tiếp; vượt trội hơn CRAFT khi nhắm đúng đối tượng cụ thể là ưu tiên hàng đầu.',
            'fields' => [
                ['key' => 'context', 'label' => 'Context', 'hint' => 'Bối cảnh — tình huống/sản phẩm/chủ đề AI cần hiểu trước khi viết.', 'prompt_heading' => 'Context', 'type' => 'textarea', 'required' => true],
                ['key' => 'objective', 'label' => 'Objective', 'hint' => 'Mục tiêu — người đọc nên nghĩ/cảm nhận/làm gì sau khi đọc xong.', 'prompt_heading' => 'Objective', 'type' => 'textarea', 'required' => true],
                ['key' => 'style', 'label' => 'Style', 'hint' => 'Phong cách viết: phân tích, kể chuyện, thuyết phục, hướng dẫn... có thể tham chiếu 1 tác giả/ấn phẩm cụ thể.', 'prompt_heading' => 'Style', 'type' => 'text', 'required' => false],
                ['key' => 'tone', 'label' => 'Tone', 'hint' => 'Giọng điệu — sắc thái cảm xúc: chuyên nghiệp, thân thiện, khẩn cấp, đồng cảm... chi tiết hơn Style.', 'prompt_heading' => 'Tone', 'type' => 'text', 'required' => false],
                ['key' => 'audience', 'label' => 'Audience', 'hint' => 'Đối tượng đọc — càng cụ thể càng tốt, tránh chung chung. VD: không viết "lập trình viên" mà "kỹ sư backend cấp cao, hoài nghi công cụ mới".', 'tip' => 'Đây là field quyết định CO-STAR có vượt trội hơn framework khác hay không (xem best_for phía trên). Phép thử: nếu mô tả này vẫn đúng khi đổi sang 1 bài viết hoàn toàn khác chủ đề, nghĩa là chưa đủ cụ thể — nêu thêm độ tuổi, mức hiểu biết, nỗi lo/mong muốn cụ thể của họ.', 'prompt_heading' => 'Audience', 'type' => 'text', 'required' => true],
                ['key' => 'response_format', 'label' => 'Response', 'hint' => 'Định dạng đầu ra: độ dài, cấu trúc, tiêu đề, gạch đầu dòng, số từ, ngôn ngữ.', 'prompt_heading' => 'Response format', 'type' => 'text', 'required' => false],
                ['key' => 'rules', 'label' => 'Rules', 'hint' => 'Ràng buộc áp dụng chung cho toàn bộ yêu cầu — tuỳ chọn.', 'prompt_heading' => 'Rules', 'type' => 'textarea', 'required' => false],
            ],
            'example' => [
                'context' => 'Chúng tôi vận hành 1 trang tin gia đình, sắp ra bài về quản lý tài chính cho cha mẹ có con nhỏ.',
                'objective' => 'Viết đoạn mở bài (100-150 từ) thu hút phụ huynh đọc tiếp.',
                'style' => 'Gần gũi, dễ hiểu, tránh thuật ngữ tài chính phức tạp.',
                'tone' => 'Ấm áp, đồng cảm, không giáo điều.',
                'audience' => 'Cha mẹ 28-40 tuổi, thu nhập trung bình, đang lo lắng về khoản tiết kiệm học phí cho con — chưa am hiểu đầu tư tài chính, không phải nhóm đọc báo kinh tế thường xuyên.',
                'response_format' => 'Đoạn văn liền mạch, không gạch đầu dòng.',
                'rules' => 'Không dùng thuật ngữ tài chính chuyên sâu (trái phiếu, danh mục đầu tư...); không đưa lời khuyên đầu tư cụ thể, chỉ mang tính tham khảo.',
            ],
        ],

        'risen' => [
            'name' => 'RISEN',
            'description' => 'Role · Instructions · Steps · End Goal · Narrowing (+ Rules tuỳ chọn) — giao việc nhiều bước, kiểm soát chặt cả quy trình lẫn kết quả.',
            'best_for' => 'Việc nhiều giai đoạn cần đúng trình tự, cần kiểm soát chặt cả cách làm lẫn kết quả (kế hoạch, quy trình nhiều bước). Việc đơn giản, tự đứng một mình thì dùng RACE cho nhanh.',
            'fields' => [
                ['key' => 'role', 'label' => 'Role', 'hint' => 'Vai trò AI đóng.', 'prompt_heading' => 'Role', 'type' => 'text', 'required' => true],
                ['key' => 'instructions', 'label' => 'Instructions', 'hint' => 'Yêu cầu tổng quát, KHÔNG cần nêu cách làm cụ thể — để dành cho Steps.', 'prompt_heading' => 'Instructions', 'type' => 'textarea', 'required' => true],
                ['key' => 'steps', 'label' => 'Steps', 'hint' => 'Trình tự các bước — nên đánh số 3-7 bước, mỗi bước 1 hành động rõ ràng.', 'tip' => 'Field quyết định RISEN có kiểm soát được cả quy trình hay chỉ kết quả cuối. Mỗi bước nên là 1 hành động AI thực hiện xong là biết ngay — nếu 1 bước cần chữ "và" để nối 2 việc (vd "liệt kê rồi phân loại"), tách thành 2 bước riêng để AI không bỏ sót việc thứ hai.', 'prompt_heading' => 'Steps', 'type' => 'textarea', 'required' => true],
                ['key' => 'end_goal', 'label' => 'End Goal', 'hint' => 'Kết quả cuối cùng thế nào là xong.', 'prompt_heading' => 'End goal', 'type' => 'text', 'required' => true],
                ['key' => 'narrowing', 'label' => 'Narrowing', 'hint' => 'Phạm vi, loại trừ, quy tắc định dạng — nêu rõ AI KHÔNG được làm gì.', 'prompt_heading' => 'Narrowing/constraints', 'type' => 'textarea', 'required' => false],
                ['key' => 'rules', 'label' => 'Rules', 'hint' => 'Ràng buộc áp dụng chung cho toàn bộ yêu cầu — tuỳ chọn.', 'prompt_heading' => 'Rules', 'type' => 'textarea', 'required' => false],
            ],
            'example' => [
                'role' => 'Bạn là chuyên gia lập kế hoạch nội dung cho website gia đình.',
                'instructions' => 'Lập lịch đăng bài 4 tuần cho chuyên mục "Nuôi dạy con", mỗi tuần 3 bài.',
                'steps' => "1) Liệt kê 12 chủ đề theo độ tuổi con (0-3, 3-6, 6-12 tuổi)\n2) Gắn mỗi chủ đề với 1 ngày đăng cụ thể\n3) Gợi ý tiêu đề chuẩn SEO cho từng bài\n4) Kiểm tra không trùng chủ đề đã đăng trong 2 tháng gần nhất",
                'end_goal' => 'Một bảng lịch đăng bài đầy đủ, sẵn sàng giao cho đội viết — không cần chỉnh sửa thêm.',
                'narrowing' => 'Không đề xuất chủ đề trùng 2 tháng gần nhất; không dùng ngôn ngữ hàn lâm; không vượt quá 12 chủ đề.',
                'rules' => 'Giữ văn phong gần gũi, phù hợp phụ huynh Việt Nam; ưu tiên chủ đề lồng ghép được kỹ năng số an toàn cho trẻ.',
            ],
        ],

        'craft' => [
            'name' => 'CRAFT',
            'description' => 'Context · Role · Action · Format · Tone (+ Rules tuỳ chọn) — Tone (giọng điệu cảm xúc) là điểm khác biệt cốt lõi, tách bạch với Format (cấu trúc).',
            'best_for' => 'Nội dung cần giữ giọng điệu thương hiệu nhất quán — giao tiếp với khách hàng, mạng xã hội, nội dung có bản sắc riêng. Giọng điệu đã ngầm hiểu qua vai trò thì dùng RACE; cần thêm cả phong cách + đối tượng + định dạng chi tiết thì dùng CO-STAR.',
            'fields' => [
                ['key' => 'context', 'label' => 'Context', 'hint' => 'Bối cảnh — sản phẩm/đối tượng/tình huống AI cần hiểu.', 'prompt_heading' => 'Context', 'type' => 'textarea', 'required' => true],
                ['key' => 'role', 'label' => 'Role', 'hint' => 'Vai trò chuyên môn AI đóng.', 'prompt_heading' => 'Role', 'type' => 'text', 'required' => true],
                ['key' => 'action', 'label' => 'Action', 'hint' => 'Việc cụ thể cần làm.', 'prompt_heading' => 'Action', 'type' => 'textarea', 'required' => true],
                ['key' => 'format', 'label' => 'Format', 'hint' => 'Định dạng CẤU TRÚC: độ dài, số đoạn, gạch đầu dòng, số từ.', 'prompt_heading' => 'Format', 'type' => 'text', 'required' => false],
                ['key' => 'tone', 'label' => 'Tone', 'hint' => 'Giọng điệu CẢM XÚC — khác Format: ấm áp, đáng tin cậy, vui vẻ, khẩn cấp...', 'tip' => 'Đây là điểm khác CRAFT với 4 framework còn lại (xem description). Phép thử: đọc lại Tone và Format — nếu đổi chỗ 2 câu trả lời cho nhau vẫn nghe hợp lý, nghĩa là 1 trong 2 đang mô tả sai thứ (Format là cấu trúc trình bày, Tone là cảm xúc truyền tải).', 'prompt_heading' => 'Tone', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules', 'hint' => 'Ràng buộc áp dụng chung — tuỳ chọn.', 'prompt_heading' => 'Rules', 'type' => 'textarea', 'required' => false],
            ],
            'example' => [
                'context' => 'Trang đích quảng bá khoá học kỹ năng số miễn phí, hướng tới phụ huynh đang lo lắng về việc con dùng mạng xã hội an toàn.',
                'role' => 'Bạn là copywriter chuyên viết landing page chuyển đổi cao.',
                'action' => 'Viết 1 headline và 1 sub-headline thu hút phụ huynh đăng ký.',
                'format' => 'Headline tối đa 12 từ, sub-headline tối đa 25 từ.',
                'tone' => 'Ấm áp, đáng tin cậy — trấn an chứ không doạ dẫm; tránh khiến phụ huynh cảm thấy mình đang thất bại trong việc bảo vệ con.',
                'rules' => 'Không dùng số liệu giật gân về rủi ro mạng xã hội; không nhắc tên nền tảng mạng xã hội cụ thể.',
            ],
        ],

        'race' => [
            'name' => 'RACE',
            'description' => 'Role · Action · Context · Expectation (+ Rules tuỳ chọn) — yêu cầu nhanh gọn, có vai trò rõ.',
            'best_for' => 'Việc "tự đứng một mình" (không cần nhiều bước tuần tự): viết chuyên nghiệp, review, phân tích, tóm tắt. Cần nhiều bước có thứ tự bắt buộc thì dùng RISEN thay vì RACE.',
            'fields' => [
                ['key' => 'role', 'label' => 'Role', 'hint' => 'Vai trò AI đóng.', 'prompt_heading' => 'Role', 'type' => 'text', 'required' => true],
                ['key' => 'action', 'label' => 'Action', 'hint' => 'Việc cần làm, dùng động từ rõ nghĩa: Viết/Phân tích/Rà soát/Tóm tắt.', 'prompt_heading' => 'Action', 'type' => 'textarea', 'required' => true],
                ['key' => 'context', 'label' => 'Context', 'hint' => 'Bối cảnh — hay bị bỏ qua nhất nhưng ảnh hưởng kết quả nhiều nhất: đối tượng đọc, ràng buộc, cái gì đã thử mà chưa hiệu quả.', 'tip' => 'Nếu để trống, AI phải tự đoán đối tượng đọc và ràng buộc — kết quả gần như chắc chắn chung chung. Phép thử: tự hỏi "đã thử cách nào mà chưa hiệu quả chưa?" — nếu có, đây chính là thứ nên thêm vào Context.', 'prompt_heading' => 'Context', 'type' => 'textarea', 'required' => false],
                ['key' => 'expectation', 'label' => 'Expectation', 'hint' => 'Kết quả thế nào là đạt: định dạng, độ dài, giọng văn, phải có/phải tránh gì.', 'prompt_heading' => 'Expectation', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules', 'hint' => 'Ràng buộc áp dụng chung cho toàn bộ yêu cầu — tuỳ chọn.', 'prompt_heading' => 'Rules', 'type' => 'textarea', 'required' => false],
            ],
            'example' => [
                'role' => 'Bạn là biên tập viên SEO có 5 năm kinh nghiệm viết nội dung sức khoẻ - giáo dục gia đình.',
                'action' => 'Viết lại tiêu đề bài viết sau cho chuẩn SEO.',
                'context' => 'Tiêu đề gốc: "5 mẹo giúp con học tốt hơn". Từ khoá mục tiêu: "phương pháp học tập cho trẻ tiểu học". 2 tiêu đề tương tự đã đăng trước đây có tỷ lệ click thấp vì quá chung chung, không nêu lợi ích cụ thể.',
                'expectation' => '3 phương án tiêu đề, mỗi tiêu đề dưới 60 ký tự, chứa từ khoá chính, có ít nhất 1 con số hoặc lợi ích cụ thể.',
                'rules' => 'Không dùng dấu chấm than hoặc từ ngữ giật tít quá đà; giữ giọng văn đáng tin cậy, phù hợp trang thông tin gia đình.',
            ],
        ],

        'rtf' => [
            'name' => 'RTF',
            'description' => 'Role · Task · Format (+ Rules tuỳ chọn) — cực ngắn gọn, đủ 3 khối cốt lõi, điền xong trong chưa đầy 30 giây.',
            'best_for' => 'Tác vụ nhanh, rõ ràng, bối cảnh đã hiển nhiên: chuyển đổi định dạng, dịch thuật, chỉnh sửa đoạn văn bản/code ngắn. Nếu kết quả ra chung chung, thiếu chiều sâu — nâng cấp lên RACE (thêm Context) thay vì cố ép RTF.',
            'fields' => [
                ['key' => 'role', 'label' => 'Role', 'hint' => 'Vai trò AI đóng — viết gọn trong 1 câu.', 'prompt_heading' => 'Role', 'type' => 'text', 'required' => true],
                ['key' => 'task', 'label' => 'Task', 'hint' => 'Nhiệm vụ — càng cụ thể càng tốt, tránh mơ hồ.', 'prompt_heading' => 'Task', 'type' => 'textarea', 'required' => true],
                ['key' => 'format', 'label' => 'Format', 'hint' => 'Định dạng đầu ra chính xác — có thể yêu cầu "chỉ trả về kết quả, không giải thích thêm".', 'tip' => 'RTF chỉ có 3 field nên Format phải gánh gần hết việc kiểm soát đầu ra. Nêu rõ số lượng/định dạng cụ thể, và cân nhắc thêm câu "chỉ trả về kết quả, không giải thích thêm" — thiếu câu này AI hay chèn lời dẫn thừa trước kết quả.', 'prompt_heading' => 'Format', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules', 'hint' => 'Ràng buộc áp dụng chung — tuỳ chọn.', 'prompt_heading' => 'Rules', 'type' => 'textarea', 'required' => false],
            ],
            'example' => [
                'role' => 'Bạn là trợ lý biên tập, thành thạo cả tiếng Việt và tiếng Anh.',
                'task' => 'Dịch đoạn mô tả sản phẩm sau sang tiếng Anh, giữ nguyên tên riêng và số liệu.',
                'format' => 'Chỉ trả về bản dịch, không giải thích, không thêm ghi chú.',
                'rules' => 'Giữ nguyên định dạng đoạn văn gốc, không thêm gạch đầu dòng.',
            ],
        ],

        'ape' => [
            'name' => 'APE',
            'description' => 'Action · Purpose · Expectation (+ Rules tuỳ chọn) — Purpose là điểm khác biệt giữa APE và RTF.',
            'best_for' => 'Việc mà MỤC ĐÍCH phía sau không rõ ràng chỉ từ hành động — muốn AI tự phán đoán, ra quyết định hợp ý định thay vì làm máy móc theo nghĩa đen. Cần vai trò/bối cảnh chi tiết hơn thì dùng RACE (có thêm Role + Context).',
            'fields' => [
                ['key' => 'action', 'label' => 'Action', 'hint' => 'Việc cần làm.', 'prompt_heading' => 'Action', 'type' => 'textarea', 'required' => true],
                ['key' => 'purpose', 'label' => 'Purpose', 'hint' => 'Vì sao cần làm — điểm khác biệt với RTF, giúp AI phán đoán đúng ý khi việc còn mơ hồ.', 'tip' => 'Đây là field phân biệt APE với RTF (xem description). Phép thử: đọc lại Action rồi tự hỏi "vì sao?" — nếu Purpose chỉ lặp lại chính Action bằng từ khác thì chưa đạt; Purpose cần nêu lý do nằm ngoài hành động, giúp AI tự phán đoán khi Action còn mơ hồ.', 'prompt_heading' => 'Purpose', 'type' => 'text', 'required' => true],
                ['key' => 'expectation', 'label' => 'Expectation', 'hint' => 'Kỳ vọng kết quả: định dạng, độ dài, giọng điệu, ràng buộc.', 'prompt_heading' => 'Expectation', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules', 'hint' => 'Ràng buộc áp dụng chung — tuỳ chọn.', 'prompt_heading' => 'Rules', 'type' => 'textarea', 'required' => false],
            ],
            'example' => [
                'action' => 'Viết lại các thông báo lỗi kỹ thuật trên form đăng ký khoá học thành tiếng Việt dễ hiểu.',
                'purpose' => 'Người dùng chủ yếu là phụ huynh không rành công nghệ; mục tiêu là giảm số lượt gọi tổng đài hỏi vì không hiểu lỗi.',
                'expectation' => 'Mỗi thông báo 1 câu, dưới 20 từ, không dùng thuật ngữ kỹ thuật.',
                'rules' => 'Giữ giọng điệu thân thiện, không đổ lỗi cho người dùng.',
            ],
        ],

        'tag' => [
            'name' => 'TAG',
            'description' => 'Task · Action · Goal (+ Rules tuỳ chọn) — tối giản, hướng tới KẾT QUẢ đạt được hơn là định dạng trình bày (khác RTF).',
            'best_for' => 'Viết thuyết phục, lời kêu gọi hành động (CTA), giải thích ngắn gọn — khi ĐẠT ĐƯỢC MỤC TIÊU quan trọng hơn cấu trúc trình bày. Định dạng đầu ra là ràng buộc chính thì dùng RTF thay vì TAG.',
            'fields' => [
                ['key' => 'task', 'label' => 'Task', 'hint' => 'Chủ đề/tình huống AI cần xử lý.', 'prompt_heading' => 'Task', 'type' => 'text', 'required' => true],
                ['key' => 'action', 'label' => 'Action', 'hint' => 'Hành động cụ thể cần thực hiện trên chủ đề đó.', 'prompt_heading' => 'Action', 'type' => 'textarea', 'required' => true],
                ['key' => 'goal', 'label' => 'Goal', 'hint' => 'Kết quả mong muốn — tiêu chí để biết đã thành công.', 'tip' => 'Goal mô tả TRẠNG THÁI người đọc SAU KHI đọc xong (họ tin gì/làm gì), không phải mô tả lại nội dung bài viết. Phép thử: nếu câu trả lời nghe giống đang lặp lại Action, viết lại theo hướng kết quả mong muốn ở người đọc.', 'prompt_heading' => 'Goal', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules', 'hint' => 'Ràng buộc áp dụng chung — tuỳ chọn.', 'prompt_heading' => 'Rules', 'type' => 'textarea', 'required' => false],
            ],
            'example' => [
                'task' => 'Nút kêu gọi hành động (CTA) trên trang đăng ký nhận bản tin gia đình.',
                'action' => 'Viết lại chữ trên nút bấm để tăng tỷ lệ đăng ký, giảm cảm giác ngại/do dự.',
                'goal' => 'Người đọc bấm đăng ký ngay, không thấy đây là 1 cam kết nặng nề hay tốn thời gian.',
                'rules' => 'Chữ trên nút tối đa 4 từ; không dùng từ "Đăng ký" trần trụi, ưu tiên động từ gợi lợi ích.',
            ],
        ],

        'care' => [
            'name' => 'CARE',
            'description' => 'Context · Action · Result · Example (+ Rules tuỳ chọn) — few-shot: dạy AI bằng VÍ DỤ THẬT thay vì mô tả bằng lời.',
            'best_for' => 'Khi có sẵn 1 ví dụ/mẫu cụ thể thể hiện đúng phong cách/định dạng mong muốn — nhất là thứ "khó mô tả bằng lời nhưng dễ minh hoạ". Không có ví dụ tham chiếu cụ thể thì dùng RACE hoặc CO-STAR (mô tả bằng lời) thay vì CARE.',
            'fields' => [
                ['key' => 'context', 'label' => 'Context', 'hint' => 'Bối cảnh — lĩnh vực, đối tượng, tình huống, ràng buộc.', 'prompt_heading' => 'Context', 'type' => 'textarea', 'required' => true],
                ['key' => 'action', 'label' => 'Action', 'hint' => 'Việc cần làm.', 'prompt_heading' => 'Action', 'type' => 'textarea', 'required' => true],
                ['key' => 'result', 'label' => 'Result', 'hint' => 'Mô tả đầu ra mong muốn: nội dung, độ dài, cấu trúc.', 'prompt_heading' => 'Desired result', 'type' => 'text', 'required' => true],
                ['key' => 'example', 'label' => 'Example', 'hint' => 'Ví dụ mẫu THẬT — dán 1 kết quả tốt đã có để AI bắt chước đúng phong cách.', 'tip' => 'CARE chỉ nên dùng khi có sẵn ví dụ THẬT. Phép thử: nếu bạn phải tự nghĩ ra ví dụ thay vì dán 1 kết quả tốt đã có sẵn, framework này chưa phù hợp — chuyển sang RACE hoặc CO-STAR (mô tả bằng lời) sẽ đáng tin hơn là ép 1 ví dụ không đại diện.', 'prompt_heading' => 'Example to follow', 'type' => 'textarea', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules', 'hint' => 'Ràng buộc áp dụng chung — tuỳ chọn.', 'prompt_heading' => 'Rules', 'type' => 'textarea', 'required' => false],
            ],
            'example' => [
                'context' => 'Chúng tôi muốn viết caption Facebook quảng bá bài viết mới.',
                'action' => 'Viết 1 caption theo đúng phong cách của ví dụ bên dưới.',
                'result' => 'Caption dưới 200 ký tự, có 1 câu hỏi mở ở cuối để tăng tương tác.',
                'example' => 'Con lười ăn rau? Đừng lo, đây là 3 cách biến rau thành món khoái khẩu của bé! 👉 Đọc ngay: [link]. Mẹ đã thử cách nào trong 3 cách này chưa?',
                'rules' => 'Không dùng quá 2 biểu tượng cảm xúc trong 1 caption; giữ giọng gần gũi, không quảng cáo lộ liễu.',
            ],
        ],

        'crit' => [
            'name' => 'CRIT',
            'description' => 'Context · Role · Interview · Task (+ Rules tuỳ chọn) — đặc trưng là AI tự hỏi lại làm rõ trước khi làm, hợp khi bạn chưa biết hết thông tin cần thiết.',
            'best_for' => 'Việc mở, mang tính sáng tạo/tư vấn chiến lược — khi chất lượng phụ thuộc nhiều vào chi tiết bạn chưa lường trước được (xây chiến lược thương hiệu, tư vấn định hướng...). Đã biết chính xác mình muốn gì thì RACE nhanh hơn — CRIT cần nhiều lượt qua lại, không hợp việc tự động hoá 1 lần.',
            'fields' => [
                ['key' => 'context', 'label' => 'Context', 'hint' => 'Tình huống/bối cảnh/vấn đề cần giải quyết.', 'prompt_heading' => 'Context', 'type' => 'textarea', 'required' => true],
                ['key' => 'role', 'label' => 'Role', 'hint' => 'Vai trò AI đóng — quyết định loại câu hỏi AI sẽ đặt ra.', 'prompt_heading' => 'Role', 'type' => 'text', 'required' => true],
                ['key' => 'interview_questions', 'label' => 'Interview', 'hint' => 'Số lượng câu hỏi AI cần hỏi lại — nêu khoảng 3-5 câu + hướng cần hỏi, KHÔNG cần viết sẵn câu hỏi cụ thể.', 'tip' => 'Chỉ nêu SỐ LƯỢNG và HƯỚNG cần hỏi — đừng tự viết sẵn câu hỏi cụ thể. Viết sẵn câu hỏi sẽ triệt tiêu đúng lý do dùng CRIT: để AI tự khai thác thông tin bạn chưa lường trước, chứ không phải trả lời câu hỏi bạn đã biết sẵn.', 'prompt_heading' => 'Interview — hỏi tôi những câu sau TRƯỚC khi bắt đầu', 'type' => 'textarea', 'required' => true],
                ['key' => 'task', 'label' => 'Task', 'hint' => 'Việc AI cần tạo ra SAU KHI đã hỏi xong.', 'prompt_heading' => 'Task — làm sau khi đã hỏi xong', 'type' => 'textarea', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules', 'hint' => 'Ràng buộc áp dụng chung — tuỳ chọn.', 'prompt_heading' => 'Rules', 'type' => 'textarea', 'required' => false],
            ],
            'example' => [
                'context' => 'Chúng tôi muốn viết 1 bài so sánh 2 loại bảo hiểm nhân thọ cho gia đình trẻ.',
                'role' => 'Bạn là chuyên gia tư vấn tài chính gia đình.',
                'interview_questions' => 'Hỏi tôi khoảng 3-4 câu trước khi bắt đầu, tập trung vào: (1) 2 sản phẩm cụ thể cần so sánh, (2) đối tượng đọc ưu tiên độ tuổi nào, (3) mức độ trung lập mong muốn của bài viết.',
                'task' => 'Sau khi có câu trả lời, viết dàn ý bài so sánh khoảng 800 từ.',
                'rules' => 'Không đề xuất tên sản phẩm cụ thể nào nếu tôi chưa cung cấp; giữ giọng văn trung lập trong toàn bộ câu hỏi.',
            ],
        ],

        'para' => [
            'name' => 'PARA',
            'description' => 'Problem · Approach · Result · Application (+ Rules tuỳ chọn) — viết nội dung giáo dục/kỹ thuật cần cả "vì sao" lẫn "làm thế nào".',
            'best_for' => 'Bài viết kỹ thuật, tài liệu hướng dẫn, case study, nội dung giáo dục — khi người đọc cần hiểu cả nguyên nhân lẫn cách làm. Tone/đối tượng là mối quan tâm chính (nội dung thuyết phục/thương hiệu) thì dùng CO-STAR thay vì PARA.',
            'fields' => [
                ['key' => 'problem', 'label' => 'Problem', 'hint' => 'Vấn đề/nỗi đau thực tế bài viết giải quyết — neo nội dung vào tình huống cụ thể.', 'prompt_heading' => 'Problem', 'type' => 'textarea', 'required' => true],
                ['key' => 'approach', 'label' => 'Approach', 'hint' => 'Phương pháp/kỹ thuật/giải pháp cụ thể cần giải thích hoặc dạy.', 'prompt_heading' => 'Approach', 'type' => 'textarea', 'required' => true],
                ['key' => 'result', 'label' => 'Result', 'hint' => 'Yêu cầu đầu ra: độ dài, định dạng, các phần bắt buộc, có cần ví dụ minh hoạ không.', 'prompt_heading' => 'Desired result', 'type' => 'text', 'required' => true],
                ['key' => 'application', 'label' => 'Application', 'hint' => 'Đối tượng đọc + bối cảnh sử dụng — field ảnh hưởng nhiều nhất đến từ ngữ, độ sâu, cách chọn ví dụ.', 'tip' => 'Field ảnh hưởng nhiều nhất đến từ ngữ, độ sâu và cách chọn ví dụ AI dùng. Phép thử: nếu mô tả này đổi được sang "độc giả quan tâm chủ đề X" chung chung mà vẫn đúng, nên thêm bối cảnh cụ thể hơn — đã thử gì, thất bại ra sao, đang ở giai đoạn nào.', 'prompt_heading' => 'Application', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules', 'hint' => 'Ràng buộc riêng cho bài viết này — tuỳ chọn.', 'prompt_heading' => 'Rules', 'type' => 'textarea', 'required' => false],
            ],
            'example' => [
                'problem' => 'Nhiều phụ huynh không biết cách thiết lập giới hạn thời gian dùng điện thoại cho con mà không gây xung đột.',
                'approach' => 'Giải thích phương pháp "thoả thuận cùng con" thay vì áp đặt 1 chiều, kèm các bước thực hiện cụ thể.',
                'result' => 'Bài viết khoảng 600 từ, có 1 đoạn hội thoại mẫu minh hoạ giữa cha mẹ và con.',
                'application' => 'Phụ huynh có con 8-14 tuổi, đã từng thử áp đặt giờ giấc nhưng thất bại, đang tìm cách khác.',
                'rules' => 'Không phán xét cách nuôi dạy con trước đây của người đọc; tránh ngôn ngữ mang tính chỉ trích.',
            ],
        ],

        'specs' => [
            'name' => 'SPECS',
            'description' => 'Situation · Purpose · Expected Output · Context · Style — Expected Output là điểm khác biệt cốt lõi, loại bỏ sự mơ hồ trong yêu cầu.',
            'best_for' => 'Phân tích/tài liệu kỹ thuật phức tạp, yêu cầu đầu ra chặt chẽ, dự án cần nhiều bối cảnh. Việc nhanh/thường lệ thì dùng APE hoặc RTF; việc sáng tạo cần linh hoạt hoặc có trình tự tự nhiên thì dùng RISEN. So với CO-STAR (chú trọng giọng điệu/đối tượng), SPECS hợp việc kỹ thuật hơn nhờ định nghĩa đầu ra chặt chẽ.',
            'fields' => [
                ['key' => 'situation', 'label' => 'Situation', 'hint' => 'Tình huống/thách thức hiện tại cần giải quyết.', 'prompt_heading' => 'Situation', 'type' => 'textarea', 'required' => true],
                ['key' => 'purpose', 'label' => 'Purpose', 'hint' => 'Vì sao việc này quan trọng — mục tiêu phía sau.', 'prompt_heading' => 'Purpose', 'type' => 'textarea', 'required' => true],
                ['key' => 'expected_output', 'label' => 'Expected Output', 'hint' => 'Đầu ra CHÍNH XÁC cần có — điểm khác biệt cốt lõi của SPECS, loại bỏ mơ hồ, tránh câu trả lời chung chung.', 'tip' => 'Đây là điểm khác biệt cốt lõi của SPECS (xem description) — mô tả CHÍNH XÁC hình dạng đầu ra: định dạng file, các mục bắt buộc, độ dài. Câu như "một tài liệu hướng dẫn" chưa đạt độ chính xác SPECS yêu cầu, cần nêu rõ cấu trúc bên trong tài liệu đó.', 'prompt_heading' => 'Expected output', 'type' => 'text', 'required' => true],
                ['key' => 'context', 'label' => 'Context', 'hint' => 'Ràng buộc/ngữ cảnh thêm.', 'prompt_heading' => 'Context', 'type' => 'textarea', 'required' => false],
                ['key' => 'style', 'label' => 'Style', 'hint' => 'Văn phong.', 'prompt_heading' => 'Style', 'type' => 'text', 'required' => false],
            ],
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
            'description' => 'Task · Request · Action · Context · Example — Example là field MẠNH NHẤT của TRACE, dạy AI bằng minh hoạ thay vì mô tả ("show, don\'t tell").',
            'best_for' => 'Có sẵn 1 ví dụ đầu ra ưng ý muốn AI học theo — nhân bản phong cách/định dạng cụ thể, sinh dữ liệu có cấu trúc theo khuôn mẫu thấy được. Thiếu ví dụ tốt, việc sáng tạo cần độc đáo (ví dụ dễ giới hạn sự mới mẻ), hoặc câu hỏi thực tế đơn giản thì dùng APE thay vì TRACE.',
            'fields' => [
                ['key' => 'task', 'label' => 'Task', 'hint' => 'Loại/nhóm công việc tổng quát.', 'prompt_heading' => 'Task', 'type' => 'text', 'required' => true],
                ['key' => 'request', 'label' => 'Request', 'hint' => 'Yêu cầu cụ thể, chính xác cần gì.', 'prompt_heading' => 'Request', 'type' => 'textarea', 'required' => true],
                ['key' => 'action', 'label' => 'Action', 'hint' => 'Hành động cụ thể AI cần thực hiện để đáp ứng yêu cầu.', 'prompt_heading' => 'Action', 'type' => 'text', 'required' => false],
                ['key' => 'context', 'label' => 'Context', 'hint' => 'Bối cảnh, ràng buộc liên quan.', 'prompt_heading' => 'Context', 'type' => 'textarea', 'required' => false],
                ['key' => 'example', 'label' => 'Example', 'hint' => 'Ví dụ mẫu — field MẠNH NHẤT của TRACE, dạy AI chính xác điều bạn muốn tốt hơn mọi mô tả bằng lời.', 'tip' => 'Ví dụ mẫu là field MẠNH NHẤT của TRACE (xem description). Phép thử: nếu xoá Example mà phần còn lại vẫn đủ rõ ràng, Example chưa phát huy tác dụng — nó nên minh hoạ chính xác văn phong/độ dài/cấu trúc mong muốn, không chỉ là 1 câu ví dụ chung chung.', 'prompt_heading' => 'Example', 'type' => 'textarea', 'required' => true],
            ],
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
            'description' => 'Role · Instructions · Tools · Reasoning · Action · Observation (+ Rules tuỳ chọn) — dành cho agent AI lặp vòng nhiều bước, KHÔNG phải yêu cầu 1 lần.',
            'best_for' => 'Xây dựng AI agent có dùng công cụ ngoài (tìm kiếm web, chạy code, gọi API), cần suy luận nhiều bước + gọi công cụ tuần tự. Việc 1 lần không cần công cụ thì dùng framework khác đơn giản hơn — kể cả RISEN (RISEN hợp việc có thứ tự bước rõ nhưng không lặp vòng thật qua công cụ; ReAct hợp agent thực sự tương tác vòng lặp).',
            'fields' => [
                ['key' => 'role', 'label' => 'Role', 'hint' => 'Vai trò AI đóng.', 'prompt_heading' => 'Role', 'type' => 'text', 'required' => true],
                ['key' => 'instructions', 'label' => 'Instructions', 'hint' => 'Mục tiêu/nhiệm vụ tổng thể agent cần hoàn thành.', 'prompt_heading' => 'Instructions', 'type' => 'textarea', 'required' => true],
                ['key' => 'tools', 'label' => 'Tools', 'hint' => 'Danh sách công cụ khả dụng + mô tả từng công cụ.', 'prompt_heading' => 'Tools available', 'type' => 'text', 'required' => true],
                ['key' => 'reasoning', 'label' => 'Reasoning', 'hint' => 'Yêu cầu suy nghĩ thành lời trước mỗi hành động — vd bắt đầu bằng "Thought:".', 'prompt_heading' => 'Reasoning', 'type' => 'textarea', 'required' => false],
                ['key' => 'action', 'label' => 'Action', 'hint' => 'Định dạng gọi công cụ — vd "Action: [tên công cụ]" rồi "Action Input: [tham số]".', 'tip' => 'Định dạng gọi công cụ càng cố định, AI càng ít bịa sai cú pháp giữa vòng lặp. Nêu rõ mẫu câu chính xác (vd luôn bắt đầu "Action:" rồi "Action Input:") thay vì mô tả chung chung "gọi công cụ khi cần".', 'prompt_heading' => 'Action', 'type' => 'textarea', 'required' => true],
                ['key' => 'observation', 'label' => 'Observation', 'hint' => 'Cách xử lý kết quả công cụ trả về trước khi suy nghĩ bước tiếp theo.', 'prompt_heading' => 'Observation', 'type' => 'text', 'required' => false],
                ['key' => 'rules', 'label' => 'Rules', 'hint' => 'Điều kiện dừng, định dạng câu trả lời cuối, ràng buộc an toàn — tuỳ chọn.', 'prompt_heading' => 'Rules', 'type' => 'textarea', 'required' => false],
            ],
            'example' => [
                'role' => 'Bạn là trợ lý nghiên cứu nội dung, có thể dùng công cụ tìm kiếm.',
                'instructions' => 'Tìm 3 nguồn số liệu uy tín về tỷ lệ trẻ em Việt Nam dùng mạng xã hội trước 13 tuổi.',
                'tools' => 'Công cụ tìm kiếm web, công cụ đọc PDF.',
                'reasoning' => 'Trước mỗi lần tìm kiếm, bắt đầu bằng "Thought:" rồi nêu rõ đang tìm gì và vì sao.',
                'action' => 'Ghi rõ "Action: tìm kiếm web" kèm "Action Input: [từ khoá]"; sau khi có kết quả, trích dẫn nguồn kèm link.',
                'observation' => 'Sau mỗi kết quả, đánh giá độ tin cậy nguồn trước khi dùng tiếp.',
                'rules' => 'Dừng lại và báo cáo nếu tìm 3 lần liên tiếp không ra nguồn đáng tin cậy; câu trả lời cuối phải liệt kê đủ 3 nguồn kèm link.',
            ],
        ],

        // v2.4 — 5 framework mới thêm theo yêu cầu người dùng, đối chiếu promptary.dev. Đây là
        // nhóm "suy luận/chất lượng" khác hẳn 13 framework trên (chủ yếu viết nội dung) — quy ước
        // required giữ nhất quán: các chữ cái CỐT LÕI của tên framework đều required, riêng
        // Rules (nếu có) luôn optional để đồng bộ trải nghiệm với 13 framework kia.
        'skeleton' => [
            'name' => 'Skeleton-of-Thought',
            'description' => 'Topic · Skeleton Points · Expand Instructions · Output Format · Rules — chia viết nội dung dài thành 2 giai đoạn: lên khung trước, viết chi tiết sau, tránh AI lạc cấu trúc giữa chừng.',
            'best_for' => 'Nội dung dài trên 500 từ cần mạch lạc xuyên suốt (bài hướng dẫn, tài liệu kỹ thuật, blog nhiều phần). Nội dung ngắn thì dùng RTF hoặc CRAFT — lên khung trước không đáng công.',
            'fields' => [
                ['key' => 'topic', 'label' => 'Topic', 'hint' => 'Chủ đề, đối tượng đọc, mục đích — để định hướng khung sườn.', 'prompt_heading' => 'Topic', 'type' => 'text', 'required' => true],
                ['key' => 'skeleton_points', 'label' => 'Skeleton Points', 'hint' => 'Các phần/mục chính — tự liệt kê hoặc để AI đề xuất trước khi viết chi tiết.', 'tip' => 'Nếu chưa chắc cấu trúc, có thể để AI tự đề xuất khung — nhưng nên ghép với Rules yêu cầu "hiện khung trước, chờ xác nhận rồi mới viết chi tiết", tránh trường hợp viết xong hết mới phát hiện khung sai hướng.', 'prompt_heading' => 'Skeleton points', 'type' => 'textarea', 'required' => true],
                ['key' => 'expand_instructions', 'label' => 'Expand Instructions', 'hint' => 'Mỗi phần viết bao nhiêu đoạn, có ví dụ không, độ sâu ra sao.', 'prompt_heading' => 'Expand instructions', 'type' => 'textarea', 'required' => true],
                ['key' => 'output_format', 'label' => 'Output Format', 'hint' => 'Định dạng cuối: Markdown/heading, số từ, cách trình bày nếu có code.', 'prompt_heading' => 'Output format', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules', 'hint' => 'Ràng buộc đảm bảo đi đúng 2 giai đoạn — vd "luôn hiện khung trước khi viết chi tiết" — tuỳ chọn.', 'prompt_heading' => 'Rules', 'type' => 'textarea', 'required' => false],
            ],
            'example' => [
                'topic' => 'Bài hướng dẫn "Chuẩn bị đồ dùng học tập cho con vào lớp 1", hướng tới phụ huynh lần đầu có con đi học.',
                'skeleton_points' => "1) Vì sao cần chuẩn bị sớm\n2) Danh sách đồ dùng bắt buộc\n3) Đồ dùng nên có nhưng không bắt buộc\n4) Cách rèn con tự sắp xếp đồ dùng\n5) Sai lầm thường gặp khi mua sắm",
                'expand_instructions' => 'Mỗi phần viết 2-3 đoạn, có ít nhất 1 ví dụ cụ thể; phần 2 và 3 trình bày dạng danh sách.',
                'output_format' => 'Markdown, dùng heading H2 cho từng phần, khoảng 800 từ.',
                'rules' => 'Luôn hiện khung 5 phần trước, chờ xác nhận rồi mới viết chi tiết từng phần; không bỏ qua phần nào.',
            ],
        ],

        'tot' => [
            'name' => 'Tree-of-Thought',
            'description' => 'Problem · Number of Branches · Evaluation Criteria · Selection · Rules — AI đưa ra NHIỀU phương án song song, chấm điểm rồi mới chọn, tránh chốt phương án đầu tiên quá sớm.',
            'best_for' => 'Quyết định chiến lược, việc quan trọng có nhiều lựa chọn hợp lý (chọn hướng nội dung, chọn cách triển khai). Việc đơn giản/hướng đã rõ thì dùng RACE hoặc RTF.',
            'fields' => [
                ['key' => 'problem', 'label' => 'Problem', 'hint' => 'Vấn đề/quyết định cần giải quyết, kèm ràng buộc loại trừ sẵn nếu có.', 'prompt_heading' => 'Problem', 'type' => 'textarea', 'required' => true],
                ['key' => 'branches_count', 'label' => 'Number of Branches', 'hint' => 'Số phương án cần so sánh — thường 3 là vừa đủ đa dạng, không quá rối.', 'prompt_heading' => 'Number of branches', 'type' => 'text', 'required' => true],
                ['key' => 'evaluation_criteria', 'label' => 'Evaluation Criteria', 'hint' => '3-4 tiêu chí chấm điểm từng phương án, định nghĩa rõ từng tiêu chí.', 'tip' => 'Tiêu chí mơ hồ (vd "tốt hơn", "phù hợp hơn") khiến AI tự chấm theo cảm tính, mất hết giá trị so sánh khách quan mà Tree-of-Thought hướng tới. Mỗi tiêu chí nên đo được — chi phí, thời gian, mức độ cạnh tranh — thay vì tính từ chung chung.', 'prompt_heading' => 'Evaluation criteria', 'type' => 'textarea', 'required' => true],
                ['key' => 'selection', 'label' => 'Selection', 'hint' => 'Cách chọn phương án thắng: bảng điểm, trọng số, hay khuyến nghị kèm lý do.', 'prompt_heading' => 'Selection', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules', 'hint' => 'Đảm bảo các phương án THẬT SỰ khác nhau; hiện bảng điểm trước khi khuyến nghị — tuỳ chọn.', 'prompt_heading' => 'Rules', 'type' => 'textarea', 'required' => false],
            ],
            'example' => [
                'problem' => 'Chọn hướng phát triển chuyên mục mới cho website gia đình trong quý tới, ngân sách nội dung có hạn.',
                'branches_count' => '3 phương án',
                'evaluation_criteria' => 'Tiềm năng traffic (dựa xu hướng tìm kiếm), chi phí sản xuất nội dung, mức độ cạnh tranh với đối thủ.',
                'selection' => 'Lập bảng điểm 3 phương án theo 3 tiêu chí, sau đó đưa ra khuyến nghị kèm lý do.',
                'rules' => '3 phương án phải thực sự khác hướng nhau (không phải biến thể của cùng 1 ý); hiện bảng điểm trước khi khuyến nghị.',
            ],
        ],

        'plansolve' => [
            'name' => 'Plan-and-Solve',
            'description' => 'Task · Plan Instructions · Solve Instructions · Output Format · Rules — bắt AI LẬP KẾ HOẠCH trước, rồi mới thực thi đúng theo kế hoạch đó, tránh làm ẩu/bỏ bước.',
            'best_for' => 'Nghiên cứu, phân tích cạnh tranh, debug, báo cáo nhiều bước — việc cần thu thập/cân nhắc nhiều thông tin theo trình tự. Việc đơn giản thì dùng RTF hoặc RACE.',
            'fields' => [
                ['key' => 'task', 'label' => 'Task', 'hint' => 'Mục tiêu cụ thể cần hoàn thành — càng cụ thể, kế hoạch AI lập càng sát.', 'prompt_heading' => 'Task', 'type' => 'textarea', 'required' => true],
                ['key' => 'plan_instructions', 'label' => 'Plan Instructions', 'hint' => 'Yêu cầu AI liệt kê từng bước TRƯỚC khi bắt tay vào làm.', 'tip' => 'Nên ghép với Rules yêu cầu "hiện kế hoạch đầy đủ trước khi bắt đầu" — nếu bỏ qua, nhiều AI sẽ nhảy thẳng sang Solve mà bỏ qua bước lập kế hoạch dù bạn đã mô tả nó ở đây.', 'prompt_heading' => 'Plan instructions', 'type' => 'textarea', 'required' => true],
                ['key' => 'solve_instructions', 'label' => 'Solve Instructions', 'hint' => 'Yêu cầu AI thực hiện đúng theo kế hoạch đã lập, có thể hiện quá trình làm.', 'prompt_heading' => 'Solve instructions', 'type' => 'textarea', 'required' => true],
                ['key' => 'output_format', 'label' => 'Output Format', 'hint' => 'Cấu trúc/định dạng của kết quả cuối cùng.', 'prompt_heading' => 'Output format', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules', 'hint' => 'Đảm bảo hiện kế hoạch trước khi bắt đầu, không bỏ qua bước nào đã lập — tuỳ chọn.', 'prompt_heading' => 'Rules', 'type' => 'textarea', 'required' => false],
            ],
            'example' => [
                'task' => 'Phân tích 4 website gia đình đối thủ đang làm nội dung nuôi dạy con, tìm khoảng trống nội dung có thể khai thác.',
                'plan_instructions' => 'Trước tiên, liệt kê từng bước sẽ làm để phân tích (thu thập chuyên mục, đếm tần suất đăng bài, xác định chủ đề còn thiếu...).',
                'solve_instructions' => 'Sau đó thực hiện lần lượt từng bước đã liệt kê, cho thấy quá trình làm ở mỗi bước.',
                'output_format' => 'Bảng so sánh 4 đối thủ + đoạn khuyến nghị 3 câu về hướng khai thác.',
                'rules' => 'Hiện kế hoạch đầy đủ trước khi bắt đầu phân tích; không bỏ qua bước nào đã lập ra.',
            ],
        ],

        'selfrefine' => [
            'name' => 'Self-Refine',
            'description' => 'Role · Task · Criteria · Rules — AI tự đóng vai vừa là người viết vừa là người phê bình: viết nháp, tự chấm theo tiêu chí, rồi mới đưa bản hoàn chỉnh.',
            'best_for' => 'Chất lượng quan trọng hơn tốc độ, có tiêu chí đo được rõ ràng (tài liệu kỹ thuật, email chuyên nghiệp, code cần đúng). Việc cần nhanh/đơn giản thì dùng RTF — thêm bước tự phê bình sẽ chậm hơn.',
            'fields' => [
                ['key' => 'role', 'label' => 'Role', 'hint' => 'Vai trò chuyên môn AI đóng — đặt kỳ vọng chất lượng ngay từ đầu.', 'prompt_heading' => 'Role', 'type' => 'text', 'required' => true],
                ['key' => 'task', 'label' => 'Task', 'hint' => 'Việc cần tạo ra, kèm phạm vi/ràng buộc.', 'prompt_heading' => 'Task', 'type' => 'textarea', 'required' => true],
                ['key' => 'criteria', 'label' => 'Criteria', 'hint' => '3-5 tiêu chí chất lượng CỤ THỂ, ĐO ĐƯỢC — không dùng tiêu chí mơ hồ như "hay", "rõ ràng".', 'tip' => 'Tiêu chí mơ hồ như "hay", "chuyên nghiệp" khiến bước tự phê bình chỉ mang tính hình thức — AI tự khen bản nháp của chính mình mà không thực sự sửa gì. Mỗi tiêu chí nên trả lời được Đạt/Không đạt rõ ràng, vd "dưới 120 từ" thay vì "ngắn gọn".', 'prompt_heading' => 'Criteria', 'type' => 'textarea', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules', 'hint' => 'Yêu cầu hiện đủ 3 bước: Bản nháp → Tự phê bình → Bản hoàn chỉnh — tuỳ chọn.', 'prompt_heading' => 'Rules', 'type' => 'textarea', 'required' => false],
            ],
            'example' => [
                'role' => 'Bạn là biên tập viên kỳ cựu chuyên viết email chăm sóc khách hàng.',
                'task' => 'Viết email nhắc phụ huynh hoàn tất hồ sơ đăng ký khoá học đang bỏ dở.',
                'criteria' => 'Dưới 120 từ; có đúng 1 lời kêu gọi hành động (CTA) duy nhất; nhắc đúng tên khoá học đã đăng ký dở; giọng văn nhắc nhở nhẹ nhàng, không tạo áp lực; không dùng từ "vui lòng" quá 1 lần.',
                'rules' => 'Hiện đủ 3 phần: Bản nháp → Tự phê bình theo từng tiêu chí trên → Bản hoàn chỉnh đã sửa hết các điểm bị chê.',
            ],
        ],

        // (2026-08-11, đối chiếu "A Guide to Prompting with LLMs: 2026 Edition" — IAB Australia)
        // — 3 kỹ thuật suy luận CÒN THIẾU sau khi rà soát: Chain-of-Thought, Step-Back Prompting,
        // Few-Shot (nhiều ví dụ). Cả 3 dùng `task_instructions` (cùng cơ chế nhóm "Chiến lược nội
        // dung" bên dưới) vì bản chất kỹ thuật của chúng LÀ 1 chỉ dẫn suy luận cố định ("suy nghĩ
        // từng bước", "trả lời tổng quát trước"), không phải cấu trúc field người dùng tự định
        // nghĩa như 18 framework viết-nội-dung ở trên — nhưng KHÔNG gắn `group` (mặc định vào
        // "Framework prompt engineering tổng quát" cùng skeleton/tot/plansolve/selfrefine/react ở
        // trên, đúng bản chất: đây là kỹ thuật suy luận đa dụng, không riêng cho content marketing).
        //
        // ĐÃ CÂN NHẮC nhưng KHÔNG thêm — Self-Consistency Sampling: trùng chức năng đáng kể với
        // `tot` đã có (nhiều phương án + tiêu chí + chọn) cho mục đích thực tế của toà soạn nội
        // dung này; Structured Prompting (nhãn ROLE/CONTEXT/DATA...): chính là cơ chế nền của TOÀN
        // BỘ module (`RenderPromptFromFrameworkAction`), không phải khoảng trống; Multimodal: kiến
        // trúc module chỉ ghép chuỗi text (`field_values` string, không upload file) — người dùng
        // tự đính kèm file khi dán prompt sang ChatGPT/Claude, không phải việc của module này.

        'cot' => [
            'name' => 'Chain-of-Thought',
            'description' => 'Problem · Known Info · Reasoning Examples (tuỳ chọn) · Output Format — buộc AI suy luận tuần tự qua từng bước trước khi trả lời, thay vì trả lời ngay theo trực giác.',
            'best_for' => 'Câu hỏi cần cân nhắc nhiều yếu tố mới ra kết luận đúng (so sánh lựa chọn, tính toán nhiều bước, phân tích nguyên nhân). Chỉ cần 1 câu trả lời trực tiếp không cần lý luận thì dùng RTF; cần NHIỀU phương án song song có chấm điểm thì dùng Tree-of-Thought — CoT chỉ đi theo 1 mạch suy luận duy nhất, không rẽ nhánh.',
            'fields' => [
                ['key' => 'problem', 'label' => 'Problem', 'hint' => 'Vấn đề/câu hỏi cần AI suy luận qua nhiều bước trước khi trả lời.', 'prompt_heading' => 'Problem', 'type' => 'textarea', 'required' => true],
                ['key' => 'known_info', 'label' => 'Known Info', 'hint' => 'Thông tin/ràng buộc đã biết cần tuân theo khi suy luận.', 'prompt_heading' => 'Known information / constraints', 'type' => 'textarea', 'required' => false],
                ['key' => 'reasoning_examples', 'label' => 'Reasoning Examples', 'hint' => '1-2 ví dụ suy luận mẫu cho câu hỏi TƯƠNG TỰ (nêu cả các bước, không chỉ đáp án) — để trống thì dùng Zero-Shot (chỉ yêu cầu suy nghĩ từng bước), điền vào thì dùng Few-Shot CoT (AI học theo CÁCH suy luận trong ví dụ).', 'tip' => 'Zero-Shot (để trống) đủ dùng cho hầu hết câu hỏi thông thường. Chỉ điền Few-Shot khi Zero-Shot đã thử mà AI suy luận sai hướng hoặc bỏ sót bước quan trọng — ví dụ mẫu lúc đó chỉ rõ ĐÚNG bước hay bị bỏ sót là bước nào, không cần thiết cho câu hỏi đơn giản.', 'prompt_heading' => 'Reasoning example (Few-Shot)', 'type' => 'textarea', 'required' => false],
                ['key' => 'output_format', 'label' => 'Output Format', 'hint' => 'Câu trả lời cuối cần trình bày thế nào — chỉ đáp án, hay cả tóm tắt lý do.', 'prompt_heading' => 'Output format', 'type' => 'text', 'required' => true],
            ],
            'task_instructions' => [
                'Suy nghĩ tuần tự qua từng bước trước khi đưa ra câu trả lời cuối cùng — không nhảy thẳng tới kết luận.',
                'Với mỗi bước, nêu rõ đang xét yếu tố nào và kết quả xét yếu tố đó.',
                'Nếu có ví dụ suy luận mẫu ở trên, áp dụng đúng CÁCH suy luận trong ví dụ đó cho vấn đề mới, không chỉ chép lại đáp án.',
                'Tách riêng phần "Câu trả lời cuối cùng" rõ ràng, dễ tìm, KHÔNG lẫn vào các bước suy luận phía trên.',
                'Trả về theo đúng định dạng đầu ra đã nêu.',
            ],
            'example' => [
                'problem' => 'Gia đình chị H có thu nhập 20 triệu/tháng, con chuẩn bị vào lớp 1, đang phân vân giữa 1 trường công gần nhà (miễn học phí, sĩ số 45 học sinh/lớp) và 1 trường tư cách nhà 8km (học phí 6 triệu/tháng, sĩ số 25 học sinh/lớp). Nên chọn trường nào?',
                'known_info' => 'Ưu tiên: học phí không vượt quá 25% thu nhập; sĩ số lớp ảnh hưởng mức độ được giáo viên quan tâm; thời gian di chuyển không quá 30 phút/lượt.',
                'reasoning_examples' => 'Câu hỏi tương tự: 1 gia đình khác chọn giữa trường công cách 15 phút và trường tư gần nhà nhưng học phí 8 triệu (32% thu nhập) → bước đầu tiên đã loại trường tư vì vượt ngưỡng 25%, dù gần hơn; kết luận chọn trường công dù xa hơn.',
                'output_format' => 'Nêu rõ từng bước cân nhắc theo 3 tiêu chí đã nêu, sau đó 1 câu kết luận chọn trường nào và vì sao.',
            ],
        ],

        'stepback' => [
            'name' => 'Step-Back Prompting',
            'description' => 'Specific Question · Step-Back Question · Context · Output Format — trả lời câu hỏi TỔNG QUÁT hơn trước để rút ra nguyên tắc, rồi mới áp dụng nguyên tắc đó vào tình huống cụ thể.',
            'best_for' => 'Tình huống cụ thể dễ khiến AI trả lời theo cảm tính/thiếu nhất quán nếu hỏi thẳng — cần neo vào 1 nguyên tắc chung trước khi áp dụng. Câu hỏi đã đơn giản, rõ ràng, không cần nguyên tắc nền thì dùng RTF hoặc RACE.',
            'fields' => [
                ['key' => 'specific_question', 'label' => 'Specific Question', 'hint' => 'Câu hỏi/tình huống cụ thể thực sự cần trả lời.', 'prompt_heading' => 'Specific question', 'type' => 'textarea', 'required' => true],
                ['key' => 'step_back_question', 'label' => 'Step-Back Question', 'hint' => 'Câu hỏi TỔNG QUÁT hơn đứng sau tình huống này — nguyên tắc/quy luật chung là gì?', 'tip' => 'Phép thử: nếu câu hỏi tổng quát này đổi được sang 1 tình huống hoàn toàn khác mà vẫn hỏi đúng, nghĩa là đủ tổng quát. Nếu vẫn còn nhắc chi tiết riêng của tình huống cụ thể (tên người, con số riêng), nó chưa lùi đủ xa — hãy trừu tượng hoá thêm 1 bậc nữa.', 'prompt_heading' => 'Step-back question (trả lời TRƯỚC)', 'type' => 'textarea', 'required' => true],
                ['key' => 'context', 'label' => 'Context', 'hint' => 'Bối cảnh thêm cho tình huống cụ thể — tuỳ chọn.', 'prompt_heading' => 'Context', 'type' => 'textarea', 'required' => false],
                ['key' => 'output_format', 'label' => 'Output Format', 'hint' => 'Câu trả lời cuối cần trình bày thế nào.', 'prompt_heading' => 'Output format', 'type' => 'text', 'required' => true],
            ],
            'task_instructions' => [
                'Trả lời câu hỏi tổng quát (step-back question) trước — nêu rõ nguyên tắc/quy luật chung, CHƯA áp dụng vào tình huống cụ thể.',
                'Sau đó mới áp dụng nguyên tắc vừa nêu vào tình huống cụ thể để trả lời.',
                'Dùng 1 câu chuyển ý rõ ràng khi chuyển từ "nguyên tắc chung" sang "áp dụng cụ thể" — không gộp lẫn 2 phần.',
                'Nếu nguyên tắc chung và tình huống cụ thể mâu thuẫn nhau ở điểm nào, nêu rõ ngoại lệ đó thay vì bỏ qua.',
                'Trả về theo đúng định dạng đầu ra đã nêu.',
            ],
            'example' => [
                'specific_question' => 'Con 5 tuổi nhà em cứ đòi mua đồ chơi mới mỗi lần đi siêu thị, không mua là ăn vạ giữa siêu thị. Em nên làm gì?',
                'step_back_question' => 'Nguyên tắc chung khi xử lý hành vi ăn vạ ở trẻ mầm non để đòi được thứ mình muốn là gì?',
                'context' => 'Con đã ăn vạ kiểu này khoảng 2 tháng nay, tần suất tăng dần.',
                'output_format' => 'Đoạn văn 2 phần rõ ràng: (1) nguyên tắc chung, (2) áp dụng cụ thể cho tình huống ở siêu thị.',
            ],
        ],

        'fewshot' => [
            'name' => 'Few-Shot Prompting',
            'description' => 'Task · Examples (2-5 cặp mẫu) · Đầu vào mới · Rules — dạy AI 1 khuôn/định dạng qua NHIỀU ví dụ input→output, để áp dụng nhất quán cho dữ liệu mới.',
            'best_for' => 'Việc cần lặp lại đúng 1 khuôn nhiều lần (chuẩn hoá mô tả sự kiện/sản phẩm, viết caption theo mẫu cố định, phân loại bình luận độc giả). Chỉ có ĐÚNG 1 ví dụ để học theo thì dùng TRACE/CARE — Few-Shot cần ít nhất 2 ví dụ mới lộ ra khuôn chung (cái gì cố định, cái gì thay đổi theo dữ liệu).',
            'fields' => [
                ['key' => 'task', 'label' => 'Task', 'hint' => 'Việc cần làm — mô tả ngắn gọn.', 'prompt_heading' => 'Task', 'type' => 'text', 'required' => true],
                ['key' => 'examples', 'label' => 'Examples', 'hint' => '2-5 ví dụ mẫu, mỗi ví dụ gồm "Đầu vào: ..." rồi "Đầu ra: ...", cách nhau 1 dòng trống.', 'tip' => 'Lỗi thường gặp: cho 2 ví dụ gần giống hệt nhau khiến AI không phân biệt được đâu là khuôn cố định, đâu là nội dung thay đổi theo dữ liệu. Ví dụ nên KHÁC NHAU về nội dung nhưng GIỐNG NHAU về cấu trúc/định dạng — sự khác biệt đó chính là thứ dạy AI nhận ra đâu là khuôn thật.', 'prompt_heading' => 'Examples', 'type' => 'textarea', 'required' => true],
                ['key' => 'new_input', 'label' => 'Đầu vào mới', 'hint' => 'Dữ liệu MỚI cần AI áp dụng đúng khuôn đã học từ các ví dụ.', 'prompt_heading' => 'Đầu vào mới cần xử lý', 'type' => 'textarea', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules', 'hint' => 'Ràng buộc thêm (độ dài, từ cấm...) — tuỳ chọn.', 'prompt_heading' => 'Rules', 'type' => 'textarea', 'required' => false],
            ],
            'task_instructions' => [
                'Học đúng KHUÔN/ĐỊNH DẠNG chung từ các ví dụ đã cho — không chỉ học nội dung riêng của từng ví dụ.',
                'Áp dụng đúng khuôn đó cho đầu vào mới, giữ nguyên cấu trúc/độ dài/giọng văn như các ví dụ mẫu.',
                'Không thêm phần giải thích hay lời dẫn ngoài khuôn đã học, trừ khi chính ví dụ mẫu có phần đó.',
                'Nếu đầu vào mới có chi tiết KHÔNG khớp khuôn nào trong ví dụ, nêu rõ đang linh hoạt điều chỉnh ở đâu.',
                'Trả về đúng khuôn đã học, áp dụng cho đầu vào mới.',
            ],
            'example' => [
                'task' => 'Viết mô tả ngắn 1 câu cho sự kiện gia đình, dùng hiển thị trên thẻ (card) danh sách sự kiện.',
                'examples' => "Đầu vào: Hội thảo nuôi dạy con không nước mắt, diễn ra 20/8/2026 tại Hà Nội, miễn phí.\nĐầu ra: Hội thảo miễn phí giúp cha mẹ nuôi dạy con không cần quát mắng — 20/8 tại Hà Nội.\n\nĐầu vào: Trại hè kỹ năng sinh tồn cho trẻ 8-12 tuổi, 3 ngày 2 đêm, giá 2.500.000đ, khởi hành từ TP.HCM.\nĐầu ra: Trại hè 3 ngày 2 đêm rèn kỹ năng sinh tồn cho trẻ 8-12 tuổi, khởi hành từ TP.HCM.",
                'new_input' => 'Khoá học online Kỷ luật tích cực trong 30 ngày, học phí 890.000đ, có video bài giảng và bài tập hằng ngày.',
                'rules' => 'Luôn dưới 20 từ; luôn có 1 chi tiết cụ thể (giá/địa điểm/thời lượng); không dùng dấu chấm than.',
            ],
        ],

        // ── Nhóm "Chiến lược nội dung" (2026-08-11, nguồn spec/giadinh.md) — 5 mẫu CÓ NHIỆM VỤ CỐ
        // ĐỊNH (`task_instructions`), khác 18 framework tổng quát ở trên (KHÔNG có nhiệm vụ cố định
        // — xem docblock đầu file). Ví dụ mẫu dùng chung bối cảnh "Vì Gia Đình" (`config('app.
        // site_name')` — xem ArticleStructuredDataBuilder::buildPublisher()) để nhất quán với phần
        // còn lại của hệ thống, KHÔNG dùng ví dụ agency/khách hàng như nguồn gốc (nguồn viết cho
        // marketing agency, không khớp mô hình toà soạn nội dung của dự án này).

        'contentstrategy' => [
            'name' => 'Chiến lược nội dung theo quý',
            'group' => 'Chiến lược nội dung',
            'description' => 'Thương hiệu · Đối tượng · Mục tiêu · Kênh · Mức độ trưởng thành → bản kế hoạch nội dung 1 quý (mục tiêu, trụ cột, kênh, tần suất, rủi ro).',
            'best_for' => 'Đầu quý, khi cần 1 bản định hướng nội dung tổng thể trước khi lên lịch bài chi tiết ở Content Calendar. Đã có sẵn danh sách ý tưởng, chỉ cần sắp lịch thì dùng thẳng module Lịch nội dung, không cần mẫu này.',
            'fields' => [
                ['key' => 'brand', 'label' => 'Thương hiệu', 'hint' => 'Tên trang/mảng nội dung — càng cụ thể AI càng bám sát đúng định vị hiện có.', 'prompt_heading' => 'Thương hiệu', 'type' => 'text', 'required' => true],
                ['key' => 'audience', 'label' => 'Đối tượng độc giả', 'hint' => 'Càng cụ thể càng tốt — độ tuổi con, khu vực, mối quan tâm chính.', 'prompt_heading' => 'Đối tượng độc giả', 'type' => 'textarea', 'required' => true],
                ['key' => 'goal', 'label' => 'Mục tiêu quý này', 'hint' => 'Mục tiêu biên tập/kinh doanh cụ thể — VD tăng lượt đọc, tăng đăng ký bản tin, tăng chuyển đổi khoá học.', 'prompt_heading' => 'Mục tiêu quý này', 'type' => 'text', 'required' => true],
                ['key' => 'channels', 'label' => 'Kênh chính', 'hint' => 'VD: bài viết trên site, Facebook, Zalo OA, TikTok.', 'prompt_heading' => 'Kênh chính', 'type' => 'text', 'required' => true],
                ['key' => 'maturity', 'label' => 'Mức độ hiện tại', 'hint' => 'Nội dung hiện đang: chưa có / thất thường / đã đều đặn.', 'tip' => 'Mức độ trưởng thành quyết định tần suất AI đề xuất ở nhiệm vụ 5 — điền sai (VD ghi "đều đặn" khi thực ra hay đứt quãng) sẽ ra tần suất phi thực tế, không theo nổi.', 'prompt_heading' => 'Mức độ trưởng thành hiện tại', 'type' => 'text', 'required' => false],
            ],
            'task_instructions' => [
                'Xác định 1 mục tiêu nội dung đo lường được, gắn với mục tiêu quý này, kèm chỉ số cụ thể và mốc thời gian.',
                'Xác định 3 nhóm độc giả trong đối tượng đã nêu và nhu cầu chính (việc họ cần được giải quyết) của từng nhóm.',
                'Đề xuất 4 trụ cột nội dung bám sát mục tiêu; giải thích lý do chọn mỗi trụ cột trong 1 câu.',
                'Gắn mỗi trụ cột với 1 kênh chính và 1 giai đoạn: mới biết đến / đang tìm hiểu / sẵn sàng hành động.',
                'Đề xuất tần suất đăng bài hằng tuần hợp lý với mức độ trưởng thành hiện tại.',
                'Liệt kê 3 rủi ro lớn nhất khi triển khai và 1 cách giảm thiểu cho mỗi rủi ro.',
                'Trả về dưới dạng bản kế hoạch có tiêu đề rõ ràng cho từng phần (mục tiêu, trụ cột, kênh & tần suất, rủi ro).',
            ],
            'example' => [
                'brand' => 'Vì Gia Đình — cẩm nang nuôi dạy con, trường học và hoạt động gia đình',
                'audience' => 'Cha mẹ có con 0-12 tuổi ở thành phố, quan tâm nuôi dạy con tích cực và tìm hoạt động cuối tuần cho cả nhà',
                'goal' => 'Tăng 30% lượt đăng ký nhận bản tin trong quý, ưu tiên nội dung nuôi dạy con và sự kiện gia đình',
                'channels' => 'Bài viết trên site, Facebook, Zalo OA, TikTok',
                'maturity' => 'Đã đăng đều 3 bài/tuần nhưng chưa có chủ đề xuyên suốt',
            ],
        ],

        'contentpillars' => [
            'name' => 'Trụ cột nội dung từ nỗi đau độc giả',
            'group' => 'Chiến lược nội dung',
            'description' => 'Thương hiệu · Đối tượng · Nỗi đau/băn khoăn · Chuyên mục hiện có → 3-5 trụ cột nội dung kèm chủ đề bài con.',
            'best_for' => 'Khi đã có sẵn nỗi đau/câu hỏi thật của độc giả (từ bình luận, khảo sát, câu hỏi gửi về) và cần biến chúng thành cấu trúc chuyên mục/trụ cột, thay vì đoán chủ đề từ đầu.',
            'fields' => [
                ['key' => 'brand', 'label' => 'Thương hiệu', 'hint' => 'Tên trang/mảng nội dung.', 'prompt_heading' => 'Thương hiệu', 'type' => 'text', 'required' => true],
                ['key' => 'audience', 'label' => 'Đối tượng độc giả', 'hint' => 'Độc giả chính của mảng nội dung này.', 'prompt_heading' => 'Đối tượng độc giả', 'type' => 'textarea', 'required' => true],
                ['key' => 'pain_points', 'label' => 'Nỗi đau/băn khoăn', 'hint' => 'Dán nguyên văn câu hỏi/bình luận/băn khoăn thật của độc giả — càng nguyên văn càng tốt.', 'tip' => 'Dán CÂU CHỮ THẬT của độc giả (bình luận, câu hỏi gửi về), không tự diễn giải lại thành câu văn trang trọng — trụ cột dựng từ ngôn ngữ thật của độc giả luôn sát nhu cầu hơn trụ cột dựng từ suy đoán của người viết.', 'prompt_heading' => 'Nỗi đau/băn khoăn của độc giả', 'type' => 'textarea', 'required' => true],
                ['key' => 'categories', 'label' => 'Chuyên mục hiện có', 'hint' => 'Các chuyên mục đang có trên site (nếu có) — giúp AI biết trụ cột nào đã phủ, trụ cột nào còn thiếu.', 'prompt_heading' => 'Chuyên mục nội dung hiện có', 'type' => 'text', 'required' => false],
            ],
            'task_instructions' => [
                'Gom nhóm các nỗi đau/băn khoăn thành 3-5 chủ đề; đặt tên mỗi chủ đề thành 1 trụ cột nội dung.',
                'Viết 1 câu định vị cho mỗi trụ cột, kết nối thương hiệu với đúng nỗi đau đó.',
                'Liệt kê 6 chủ đề bài viết con cho mỗi trụ cột, phù hợp viết bài dài.',
                'Đánh dấu trụ cột nào khác biệt, trụ cột nào chuyên mục nào cũng có (me-too).',
                'Đề xuất 1 trụ cột nên đầu tư mạnh nhất trong quý này và lý do.',
                'Trả về dưới dạng bảng trụ cột (tên trụ cột, câu định vị, 6 chủ đề con, mức độ khác biệt).',
            ],
            'example' => [
                'brand' => 'Vì Gia Đình',
                'audience' => 'Cha mẹ có con chuẩn bị vào lớp 1',
                'pain_points' => '"Con em nhút nhát quá, sợ không theo kịp lớp 1"; "Không biết chọn trường công hay tư"; "Con hay ăn vạ, la mắng mãi không hết"; "Không có thời gian chơi cùng con vì đi làm cả ngày"; "Sợ con nghiện điện thoại/iPad"',
                'categories' => 'Nuôi dạy con, Chọn trường cho con, Sản phẩm & dịch vụ',
            ],
        ],

        'readerjourney' => [
            'name' => 'Hành trình người đọc',
            'group' => 'Chiến lược nội dung',
            'description' => 'Thương hiệu · Đối tượng · Sản phẩm/Dịch vụ chính · Thời gian ra quyết định → bản đồ nội dung theo từng giai đoạn hành trình đọc.',
            'best_for' => 'Khi thư viện nội dung đang lệch hẳn về 1 giai đoạn (VD toàn bài giới thiệu chung, thiếu bài giúp người đọc "chốt" hành động) và cần nhìn ra khoảng trống đó.',
            'fields' => [
                ['key' => 'brand', 'label' => 'Thương hiệu', 'hint' => 'Tên trang/mảng nội dung.', 'prompt_heading' => 'Thương hiệu', 'type' => 'text', 'required' => true],
                ['key' => 'audience', 'label' => 'Đối tượng độc giả', 'hint' => 'Độc giả chính.', 'prompt_heading' => 'Đối tượng độc giả', 'type' => 'textarea', 'required' => true],
                ['key' => 'offering', 'label' => 'Sản phẩm/Dịch vụ chính', 'hint' => 'Khoá học, sản phẩm gắn trong bài, sự kiện, tư vấn... Nếu chỉ có nội dung miễn phí, ghi rõ "chỉ có nội dung, không bán gì".', 'prompt_heading' => 'Sản phẩm/dịch vụ chính', 'type' => 'text', 'required' => true],
                ['key' => 'decision_time', 'label' => 'Thời gian ra quyết định', 'hint' => 'Đọc xong quyết định ngay / cân nhắc vài ngày / cân nhắc vài tuần.', 'prompt_heading' => 'Thời gian ra quyết định', 'type' => 'text', 'required' => false],
            ],
            'task_instructions' => [
                'Xác định các giai đoạn hành trình của đối tượng độc giả: mới biết đến, đang tìm hiểu, sẵn sàng hành động, tiếp tục theo dõi.',
                'Với mỗi giai đoạn, mô tả câu hỏi chính đang nằm trong đầu người đọc.',
                'Đề xuất 2 định dạng nội dung phù hợp nhất cho mỗi giai đoạn để trả lời đúng câu hỏi đó.',
                'Cho 1 ví dụ tiêu đề bài cụ thể cho mỗi định dạng.',
                'Chỉ ra giai đoạn đang bị bỏ ngỏ nhiều nhất trong thư viện nội dung hiện có và giải thích cơ hội ở đó.',
                'Trả về dưới dạng bảng hành trình (giai đoạn, câu hỏi, định dạng, ví dụ tiêu đề).',
            ],
            'example' => [
                'brand' => 'Vì Gia Đình',
                'audience' => 'Cha mẹ lần đầu tìm khoá học kỷ luật tích cực cho con',
                'offering' => 'Khoá học online "Kỷ luật tích cực trong 30 ngày"',
                'decision_time' => 'Cân nhắc vài ngày, thường đọc 2-3 bài trước khi đăng ký',
            ],
        ],

        'competitivegap' => [
            'name' => 'Phân tích khoảng trống so với đối thủ',
            'group' => 'Chiến lược nội dung',
            'description' => 'Thương hiệu · Đối thủ · Chủ đề · Đối tượng → danh sách khoảng trống nội dung đối thủ đang bỏ ngỏ, xếp theo độ khó/đáng làm.',
            'best_for' => 'Trước khi lên kế hoạch cho 1 chuyên mục mới hoặc khi cảm giác nội dung đang trùng lặp với các trang cùng mảng, cần biết nên viết khác đi ở đâu.',
            'fields' => [
                ['key' => 'brand', 'label' => 'Thương hiệu', 'hint' => 'Tên trang/mảng nội dung.', 'prompt_heading' => 'Thương hiệu', 'type' => 'text', 'required' => true],
                ['key' => 'competitors', 'label' => 'Đối thủ', 'hint' => 'Tên 2-3 trang/kênh cùng mảng nội dung.', 'prompt_heading' => 'Đối thủ chính', 'type' => 'text', 'required' => true],
                ['key' => 'topic', 'label' => 'Chủ đề cần phân tích', 'hint' => 'Mảng nội dung/chuyên mục cụ thể muốn soi khoảng trống.', 'prompt_heading' => 'Chủ đề', 'type' => 'text', 'required' => true],
                ['key' => 'audience', 'label' => 'Đối tượng độc giả', 'hint' => 'Độc giả chính của chủ đề này.', 'tip' => 'Khoảng trống chỉ có ý nghĩa khi gắn với 1 đối tượng cụ thể — cùng 1 chủ đề nhưng đối tượng khác nhau sẽ ra khoảng trống khác nhau hoàn toàn. Mô tả càng chung chung, danh sách khoảng trống AI đưa ra càng generic và khó dùng.', 'prompt_heading' => 'Đối tượng độc giả', 'type' => 'textarea', 'required' => true],
            ],
            'task_instructions' => [
                'Liệt kê các góc nội dung nhiều khả năng đối thủ đang làm tốt quanh chủ đề đã nêu.',
                'Xác định 5 góc nội dung đối thủ đang bỏ ngỏ hoặc làm hời hợt với đối tượng độc giả đã nêu.',
                'Với mỗi khoảng trống, đánh giá độ khó để làm tốt hơn (thấp/vừa/cao) và mức độ đáng làm.',
                'Đề xuất 3 khoảng trống nên khai thác trước.',
                'Gợi ý 1 góc nhìn riêng khiến nội dung của thương hiệu khó bị sao chép.',
                'Trả về dưới dạng bảng khoảng trống kèm 2 câu khuyến nghị về góc nhìn riêng.',
            ],
            'example' => [
                'brand' => 'Vì Gia Đình',
                'competitors' => 'Marrybaby, WebTreTho, Eva.vn (mục Gia đình)',
                'topic' => 'Nuôi dạy con tuổi dậy thì',
                'audience' => 'Cha mẹ có con 11-15 tuổi, lo lắng con thay đổi tính cách, ngại chia sẻ với bố mẹ',
            ],
        ],

        'contentkpi' => [
            'name' => 'Bộ chỉ số & mục tiêu nội dung',
            'group' => 'Chiến lược nội dung',
            'description' => 'Thương hiệu · Mục tiêu · Kênh · Số liệu nền → cây chỉ số (KPI tree) tách bạch chỉ số dẫn dắt/kết quả, kèm mục tiêu 90 ngày.',
            'best_for' => 'Khi cần chốt "đo bằng gì" trước khi bắt đầu 1 giai đoạn nội dung mới, tránh báo cáo hằng tuần chỉ toàn số liệu không nói lên điều gì (lượt xem, follow) mà bỏ qua chỉ số thật sự phản ánh hiệu quả.',
            'fields' => [
                ['key' => 'brand', 'label' => 'Thương hiệu', 'hint' => 'Tên trang/mảng nội dung.', 'prompt_heading' => 'Thương hiệu', 'type' => 'text', 'required' => true],
                ['key' => 'objective', 'label' => 'Mục tiêu nội dung', 'hint' => 'Mục tiêu đang theo đuổi — VD tăng đăng ký bản tin, tăng chuyển đổi khoá học, tăng người đọc quay lại.', 'prompt_heading' => 'Mục tiêu nội dung', 'type' => 'text', 'required' => true],
                ['key' => 'channels', 'label' => 'Kênh', 'hint' => 'VD: bài viết trên site, Facebook, Zalo OA, TikTok.', 'prompt_heading' => 'Kênh', 'type' => 'text', 'required' => true],
                ['key' => 'baseline', 'label' => 'Số liệu nền', 'hint' => 'Số liệu hiện có (nếu có) — để trống nếu chưa từng đo.', 'prompt_heading' => 'Số liệu nền hiện có', 'type' => 'textarea', 'required' => false],
            ],
            'task_instructions' => [
                'Đề xuất 1 chỉ số "ngôi sao dẫn đường" (north-star) gắn trực tiếp với mục tiêu nội dung đã nêu.',
                'Liệt kê 3 chỉ số dẫn dắt (leading indicator) có thể dự báo chỉ số ngôi sao đó.',
                'Liệt kê 3 chỉ số kết quả (lagging indicator) chứng minh tác động thật tới mục tiêu kinh doanh.',
                'Đề xuất mục tiêu cụ thể cho 90 ngày tới dựa trên số liệu nền (hoặc theo mặt bằng chung ngành nếu chưa có số liệu).',
                'Thiết kế 1 mẫu báo cáo hằng tuần gọn gàng bao quát tất cả chỉ số trên.',
                'Trả về dưới dạng cây chỉ số (KPI tree) kèm mục tiêu 90 ngày.',
            ],
            'example' => [
                'brand' => 'Vì Gia Đình',
                'objective' => 'Tăng số người đăng ký nhận bản tin hằng tuần',
                'channels' => 'Bài viết trên site, Facebook, Zalo OA',
                'baseline' => 'Hiện ~1.200 lượt đọc/bài, tỷ lệ đăng ký bản tin từ bài viết khoảng 1,5%',
            ],
        ],

        // spec/AIIdeaMatrixGenerator.md §2.3 — "Ma trận Ý tưởng Di sản": ghép Format cố định + 2 trục
        // biến số (Di sản/Sản phẩm + Tình huống gia đình) thành 1 preset. Nhóm RIÊNG "Ý tưởng theo Ma
        // trận" (khác "Chiến lược nội dung" ở trên) — 5 preset trên là tài liệu CHIẾN LƯỢC theo quý,
        // preset này là Ý TƯỞNG 1 video/bài đơn lẻ, tần suất dùng khác hẳn. Dùng field type MỚI
        // `select` (options: khoá => nhãn) — xem RenderPromptFromFrameworkAction (map khoá→nhãn khi
        // render) + StoreGeneratedPromptRequest/UpdateGeneratedPromptRequest (Rule::in theo options).
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
                // spec/AIIdeaMatrixGenerator.md §2.5 (v2.1) — 2 field dưới đây là BIẾN SỐ biên tập
                // (đúng thuật ngữ "Hằng số + Biến số" của tài liệu gốc): `allow_custom` cho nhập tự
                // do — danh sách options chỉ là GỢI Ý + nguồn cho nút Randomize, không phải biên
                // giới. `format` ở trên cố ý KHÔNG có allow_custom (hằng số cấu trúc thật).
                //
                // spec §2.6 (v2.2) — `custom_max_length`: giới hạn RIÊNG (ngắn hơn hẳn `max:5000`
                // mặc định của field text/textarea) cho phần tự nhập của field `allow_custom`. Lý
                // do: cả 2 field này về BẢN CHẤT là 1 CỤM TỪ NGẮN neo cảm xúc/văn hoá (xem 21 nhãn
                // có sẵn, dài nhất ~33 ký tự) — dùng chung `max:5000` với field textarea tự do (VD
                // `audience`) để hở cửa cho việc dán nguyên khối nội dung quảng cáo/thông cáo báo
                // chí vào đây, phá đúng mục đích "Hằng số + Biến số" (mô hình cần 1 cụm từ ngắn để
                // ghép, không phải 1 đoạn văn dài đã tự nó là nội dung hoàn chỉnh) — xem ví dụ thật
                // đã xảy ra ở docblock `RenderPromptFromFrameworkAction`.
                ['key' => 'heritage_variable', 'label' => 'Yếu tố Di sản/Sản phẩm', 'hint' => 'Yếu tố văn hoá/vật lý sẽ lồng ghép — chọn từ gợi ý hoặc "Khác (tự nhập)". Tự nhập PHẢI là 1 CỤM TỪ NGẮN (VD "Bánh chưng ngày Tết", "Gốm Chu Đậu") — không dán cả đoạn mô tả/quảng cáo dài vào đây.', 'custom_placeholder' => 'VD: "Bánh chưng ngày Tết", "Gốm Chu Đậu" — 1 cụm từ, không phải đoạn mô tả', 'prompt_heading' => 'Yếu tố Di sản/Sản phẩm', 'type' => 'select', 'allow_custom' => true, 'custom_max_length' => 120, 'options' => [
                    // Di sản
                    'lang_co' => 'Không gian làng cổ', 'le_hoi' => 'Lễ hội dân gian', 'di_tich' => 'Di tích lịch sử', 'dinh_chua' => 'Kiến trúc đình chùa',
                    // Sản phẩm
                    'gom_bat_trang' => 'Gốm sứ Bát Tràng', 'lua_van_phuc' => 'Lụa Vạn Phúc', 'to_he' => 'Đồ chơi Tò he', 'nuoc_mam' => 'Nước mắm truyền thống', 'tra_sen' => 'Trà sen',
                    // Dịch vụ
                    'combo_da_ngoai' => 'Combo vé dã ngoại gia đình', 'workshop_gom' => 'Workshop nặn gốm cho bé',
                ], 'required' => true],
                // spec/AIIdeaMatrixGenerator.md §2.8 (v2.3) — `tip` (KHÁC `hint`, callout nổi bật
                // hơn hẳn, xem field-form.blade.php) gắn vào field NÀY vì đây là nơi ví dụ thật đã
                // xảy ra lỗi (§2.6) — dù đã có cảnh báo ở `hint`, người dùng vẫn dán cả đoạn quảng
                // cáo vào field này ngay sau đó, chứng tỏ hint (chữ nhỏ) không đủ sức cản. Chỉ gắn
                // `tip` cho 1 trong 2 field allow_custom (đúng convention "1 tip/framework" của cả
                // config — xem docblock đầu file) nhưng nội dung tip nói về QUAN HỆ giữa CẢ 2 field,
                // vì `heritage_variable` đứng ngay phía trên trong thứ tự canon.
                ['key' => 'situation_variable', 'label' => 'Tình huống Gia đình', 'hint' => 'Nỗi đau/sự kiện sinh hoạt đời thường làm điểm neo cảm xúc — chọn từ gợi ý hoặc "Khác (tự nhập)". Tự nhập PHẢI là 1 CỤM TỪ NGẮN mô tả 1 TÌNH HUỐNG (VD "Trẻ sợ đi khám răng") — KHÔNG phải nội dung/thông điệp quảng cáo của chiến dịch (cái đó thuộc ô "Yếu tố Di sản/Sản phẩm" hoặc "Thông điệp cốt lõi").', 'tip' => 'Field này và "Yếu tố Di sản/Sản phẩm" PHẢI ĐỘC LẬP với nhau — nếu cả 2 cùng mô tả lại sản phẩm/sự kiện, kịch bản sinh ra sẽ chỉ đọc lại thông cáo quảng cáo, mất hẳn mạch "vấn đề → giải pháp" mà Nhiệm vụ 1 yêu cầu. Phép thử: xoá tên sản phẩm/sự kiện khỏi câu bạn vừa viết — câu đó có còn ĐÚNG và CÓ NGHĨA không? Có → đúng là 1 tình huống thật. Không → bạn đang mô tả lại sản phẩm, viết lại. Chi tiết dài (ngày giờ, địa điểm, danh sách...) đưa vào "Ghi chú thêm", không nhét vào đây.', 'custom_placeholder' => 'VD: "Trẻ sợ đi khám răng" — 1 tình huống vẫn đúng dù không có sản phẩm này', 'prompt_heading' => 'Tình huống Gia đình', 'type' => 'select', 'allow_custom' => true, 'custom_max_length' => 120, 'options' => [
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
            // spec/AIIdeaMatrixGenerator.md §2.10 (v2.5) — ví dụ chuẩn, tách từ 1 thông cáo hội chợ
            // OCOP thật (Hội chợ Xúc tiến thương mại nông nghiệp, sản phẩm OCOP – HaNoi Agriculture
            // Fair 2026, AEON MALL Long Biên, 13-16/8/2026) theo ĐÚNG 7 bước ở §2.7 — thay ví dụ cũ
            // (Gốm sứ Bát Tràng, v2.0-v2.4). Cố ý dùng làm ví dụ MẪU cho cách áp dụng công thức:
            // `heritage_variable`/`situation_variable` CHỌN TỪ DANH SÁCH CÓ SẴN (không allow_custom)
            // — minh hoạ bước 1 "kiểm tra danh sách gợi ý trước khi tự nhập"; nguyên liệu gốc liệt
            // kê ~10 sản phẩm (trà, cà phê, mật ong, đông trùng hạ thảo...) + 3 nghề (tò he, thêu
            // tay, nặn hoa đất) — CHỈ chọn 1 ("tò he", đã có sẵn trong options) làm trọng tâm video
            // này (bước 2), phần còn lại dồn vào `custom_context` (bước 7) chứ không nhồi vào 2 field
            // ngắn. `situation_variable` ("Trẻ chỉ thích xem iPad") KHÔNG có trong nguyên liệu gốc —
            // tự chọn theo đúng bước 3, đối lập tự nhiên với hoạt động tay chân của nghề tò he (phép
            // thử: câu này vẫn đúng dù không có hội chợ này).
            'example' => [
                'red_thread' => 'Di sản Sống - Gắn kết Gia đình Hiện đại',
                'audience' => 'Gia đình có con nhỏ ở Hà Nội, muốn con trải nghiệm nghề truyền thống thay vì suốt ngày cầm điện thoại',
                'format' => 'time_capsule',
                'heritage_variable' => 'to_he',
                'situation_variable' => 'thich_ipad',
                'custom_context' => 'Hội chợ Xúc tiến thương mại nông nghiệp, sản phẩm OCOP – HaNoi Agriculture Fair 2026, 13-16/8/2026 tại AEON MALL Long Biên. Có khu ẩm thực đặc sản (trà, cà phê, mật ong, đông trùng hạ thảo, gạo, miến, mì, gia vị, tổ yến) và trải nghiệm nghề truyền thống (tò he, thêu tay, nặn hoa đất).',
            ],
        ],

        // Freeform khác biệt kiến trúc so với 17 framework còn lại: CHỈ 1 field, không cấu trúc,
        // đúng bản chất "container lưu nguyên văn prompt đã có sẵn" — KHÔNG áp field 'required'
        // theo nghĩa "field cốt lõi của framework" như các mục trên vì bản thân Freeform không có
        // khái niệm "cốt lõi", chỉ có 1 field duy nhất và nó bắt buộc vì để trống thì vô nghĩa.
        'freeform' => [
            'name' => 'Freeform',
            'description' => 'Không cấu trúc — dùng để LƯU LẠI nguyên văn 1 prompt đã viết sẵn/đã dùng tốt ở nơi khác, không ép vào khuôn nào.',
            'best_for' => 'Di chuyển prompt đã có sẵn (từ Notion, file text, ChatGPT...) vào đây để quản lý/tìm lại — không viết prompt mới từ đầu. Viết mới từ đầu hoặc muốn tinh chỉnh có hệ thống thì dùng RACE/RISEN.',
            'fields' => [
                ['key' => 'text', 'label' => 'Nội dung prompt', 'hint' => 'Dán nguyên văn — không cần theo cấu trúc nào.', 'type' => 'textarea', 'required' => true],
            ],
            'example' => [
                'text' => "Bạn là trợ lý chăm sóc khách hàng của nền tảng khoá học trực tuyến cho gia đình. Nhiệm vụ của bạn là trả lời câu hỏi về cách sử dụng nền tảng, cách đăng ký khoá học, và các vấn đề thanh toán.\n\nQuy tắc:\n- Luôn trả lời bằng tiếng Việt, giọng thân thiện.\n- Không được bịa ra tính năng mà nền tảng chưa có.\n- Nếu không chắc câu trả lời, hướng dẫn người dùng liên hệ tổng đài thay vì đoán.\n- Giữ câu trả lời dưới 100 từ trừ khi người dùng yêu cầu chi tiết hơn.",
            ],
        ],

    ],
];
