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
