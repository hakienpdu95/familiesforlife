<?php

// spec/PromptFrameworkStudio_Technical_Specification.md §2 — nguồn DUY NHẤT cho danh mục
// framework (fields/template/example). KHÔNG DB, KHÔNG CRUD admin (§0) — thêm/sửa framework là
// việc của dev (thêm 1 phần tử + deploy), không cần màn hình quản trị cho việc hiếm khi xảy ra.
//
// Mỗi framework: name, description, best_for, fields (thứ tự hiển thị đúng theo mảng), template
// (chuỗi strtr có placeholder {{field_key}} — xem RenderPromptFromFrameworkAction), example (giá
// trị mẫu tự biên soạn, KHÔNG sao chép nguyên văn từ nguồn tham khảo — xem §0 dòng "Ví dụ mẫu").
return [
    'frameworks' => [

        'costar' => [
            'name' => 'CO-STAR',
            'description' => 'Ghép 6 khối: bối cảnh, mục tiêu, phong cách, giọng điệu, đối tượng, định dạng phản hồi (+ Rules tuỳ chọn) — do GovTech Singapore phát triển, vô địch cuộc thi prompt engineering GPT-4 năm 2023.',
            'best_for' => 'Viết nội dung (blog, marketing, email, mạng xã hội, tài liệu) khi hiểu đối tượng đọc và giọng văn là yếu tố quan trọng nhất. Vượt trội hơn RACE khi cần chú trọng phong cách giao tiếp; vượt trội hơn CRAFT khi nhắm đúng đối tượng cụ thể là ưu tiên hàng đầu.',
            'fields' => [
                ['key' => 'context', 'label' => 'Context (Bối cảnh — tình huống/sản phẩm/chủ đề AI cần hiểu trước khi viết)', 'type' => 'textarea', 'required' => true],
                ['key' => 'objective', 'label' => 'Objective (Mục tiêu — người đọc nên nghĩ/cảm nhận/làm gì sau khi đọc xong)', 'type' => 'textarea', 'required' => true],
                ['key' => 'style', 'label' => 'Style (Phong cách viết: phân tích, kể chuyện, thuyết phục, hướng dẫn... có thể tham chiếu 1 tác giả/ấn phẩm cụ thể)', 'type' => 'text', 'required' => false],
                ['key' => 'tone', 'label' => 'Tone (Giọng điệu — sắc thái cảm xúc: chuyên nghiệp, thân thiện, khẩn cấp, đồng cảm... chi tiết hơn Style)', 'type' => 'text', 'required' => false],
                ['key' => 'audience', 'label' => 'Audience (Đối tượng đọc — càng cụ thể càng tốt, tránh chung chung. VD: không viết "lập trình viên" mà "kỹ sư backend cấp cao, hoài nghi công cụ mới")', 'type' => 'text', 'required' => true],
                ['key' => 'response_format', 'label' => 'Response (Định dạng đầu ra: độ dài, cấu trúc, tiêu đề, gạch đầu dòng, số từ, ngôn ngữ)', 'type' => 'text', 'required' => false],
                ['key' => 'rules', 'label' => 'Rules (Ràng buộc áp dụng chung cho toàn bộ yêu cầu — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Context: {{context}}\nObjective: {{objective}}\nStyle: {{style}}\nTone: {{tone}}\nAudience: {{audience}}\nResponse format: {{response_format}}\nRules: {{rules}}",
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
                ['key' => 'role', 'label' => 'Role (Vai trò AI đóng)', 'type' => 'text', 'required' => true],
                ['key' => 'instructions', 'label' => 'Instructions (Yêu cầu tổng quát, KHÔNG cần nêu cách làm cụ thể — để dành cho Steps)', 'type' => 'textarea', 'required' => true],
                ['key' => 'steps', 'label' => 'Steps (Trình tự các bước — nên đánh số 3-7 bước, mỗi bước 1 hành động rõ ràng)', 'type' => 'textarea', 'required' => true],
                ['key' => 'end_goal', 'label' => 'End Goal (Kết quả cuối cùng thế nào là xong)', 'type' => 'text', 'required' => true],
                ['key' => 'narrowing', 'label' => 'Narrowing (Phạm vi, loại trừ, quy tắc định dạng — nêu rõ AI KHÔNG được làm gì)', 'type' => 'textarea', 'required' => false],
                ['key' => 'rules', 'label' => 'Rules (Ràng buộc áp dụng chung cho toàn bộ yêu cầu — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Role: {{role}}\nInstructions: {{instructions}}\nSteps:\n{{steps}}\nEnd goal: {{end_goal}}\nNarrowing/constraints: {{narrowing}}\nRules: {{rules}}",
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
                ['key' => 'context', 'label' => 'Context (Bối cảnh — sản phẩm/đối tượng/tình huống AI cần hiểu)', 'type' => 'textarea', 'required' => true],
                ['key' => 'role', 'label' => 'Role (Vai trò chuyên môn AI đóng)', 'type' => 'text', 'required' => true],
                ['key' => 'action', 'label' => 'Action (Việc cụ thể cần làm)', 'type' => 'textarea', 'required' => true],
                ['key' => 'format', 'label' => 'Format (Định dạng CẤU TRÚC: độ dài, số đoạn, gạch đầu dòng, số từ)', 'type' => 'text', 'required' => false],
                ['key' => 'tone', 'label' => 'Tone (Giọng điệu CẢM XÚC — khác Format: ấm áp, đáng tin cậy, vui vẻ, khẩn cấp...)', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Ràng buộc áp dụng chung — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Context: {{context}}\nRole: {{role}}\nAction: {{action}}\nFormat: {{format}}\nTone: {{tone}}\nRules: {{rules}}",
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
                ['key' => 'role', 'label' => 'Role (Vai trò AI đóng)', 'type' => 'text', 'required' => true],
                ['key' => 'action', 'label' => 'Action (Việc cần làm, dùng động từ rõ nghĩa: Viết/Phân tích/Rà soát/Tóm tắt)', 'type' => 'textarea', 'required' => true],
                ['key' => 'context', 'label' => 'Context (Bối cảnh — hay bị bỏ qua nhất nhưng ảnh hưởng kết quả nhiều nhất: đối tượng đọc, ràng buộc, cái gì đã thử mà chưa hiệu quả)', 'type' => 'textarea', 'required' => false],
                ['key' => 'expectation', 'label' => 'Expectation (Kết quả thế nào là đạt: định dạng, độ dài, giọng văn, phải có/phải tránh gì)', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Ràng buộc áp dụng chung cho toàn bộ yêu cầu — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Role: {{role}}\nAction: {{action}}\nContext: {{context}}\nExpectation: {{expectation}}\nRules: {{rules}}",
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
                ['key' => 'role', 'label' => 'Role (Vai trò AI đóng — viết gọn trong 1 câu)', 'type' => 'text', 'required' => true],
                ['key' => 'task', 'label' => 'Task (Nhiệm vụ — càng cụ thể càng tốt, tránh mơ hồ)', 'type' => 'textarea', 'required' => true],
                ['key' => 'format', 'label' => 'Format (Định dạng đầu ra chính xác — có thể yêu cầu "chỉ trả về kết quả, không giải thích thêm")', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Ràng buộc áp dụng chung — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Role: {{role}}\nTask: {{task}}\nFormat: {{format}}\nRules: {{rules}}",
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
                ['key' => 'action', 'label' => 'Action (Việc cần làm)', 'type' => 'textarea', 'required' => true],
                ['key' => 'purpose', 'label' => 'Purpose (Vì sao cần làm — điểm khác biệt với RTF, giúp AI phán đoán đúng ý khi việc còn mơ hồ)', 'type' => 'text', 'required' => true],
                ['key' => 'expectation', 'label' => 'Expectation (Kỳ vọng kết quả: định dạng, độ dài, giọng điệu, ràng buộc)', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Ràng buộc áp dụng chung — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Action: {{action}}\nPurpose: {{purpose}}\nExpectation: {{expectation}}\nRules: {{rules}}",
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
                ['key' => 'task', 'label' => 'Task (Chủ đề/tình huống AI cần xử lý)', 'type' => 'text', 'required' => true],
                ['key' => 'action', 'label' => 'Action (Hành động cụ thể cần thực hiện trên chủ đề đó)', 'type' => 'textarea', 'required' => true],
                ['key' => 'goal', 'label' => 'Goal (Kết quả mong muốn — tiêu chí để biết đã thành công)', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Ràng buộc áp dụng chung — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Task: {{task}}\nAction: {{action}}\nGoal: {{goal}}\nRules: {{rules}}",
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
                ['key' => 'context', 'label' => 'Context (Bối cảnh — lĩnh vực, đối tượng, tình huống, ràng buộc)', 'type' => 'textarea', 'required' => true],
                ['key' => 'action', 'label' => 'Action (Việc cần làm)', 'type' => 'textarea', 'required' => true],
                ['key' => 'result', 'label' => 'Result (Mô tả đầu ra mong muốn: nội dung, độ dài, cấu trúc)', 'type' => 'text', 'required' => true],
                ['key' => 'example', 'label' => 'Example (Ví dụ mẫu THẬT — dán 1 kết quả tốt đã có để AI bắt chước đúng phong cách)', 'type' => 'textarea', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Ràng buộc áp dụng chung — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Context: {{context}}\nAction: {{action}}\nDesired result: {{result}}\nExample to follow:\n{{example}}\nRules: {{rules}}",
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
                ['key' => 'context', 'label' => 'Context (Tình huống/bối cảnh/vấn đề cần giải quyết)', 'type' => 'textarea', 'required' => true],
                ['key' => 'role', 'label' => 'Role (Vai trò AI đóng — quyết định loại câu hỏi AI sẽ đặt ra)', 'type' => 'text', 'required' => true],
                ['key' => 'interview_questions', 'label' => 'Interview (Số lượng câu hỏi AI cần hỏi lại — nêu khoảng 3-5 câu + hướng cần hỏi, KHÔNG cần viết sẵn câu hỏi cụ thể)', 'type' => 'textarea', 'required' => true],
                ['key' => 'task', 'label' => 'Task (Việc AI cần tạo ra SAU KHI đã hỏi xong)', 'type' => 'textarea', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Ràng buộc áp dụng chung — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Context: {{context}}\nRole: {{role}}\nBefore starting, ask me these questions:\n{{interview_questions}}\nThen: {{task}}\nRules: {{rules}}",
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
                ['key' => 'problem', 'label' => 'Problem (Vấn đề/nỗi đau thực tế bài viết giải quyết — neo nội dung vào tình huống cụ thể)', 'type' => 'textarea', 'required' => true],
                ['key' => 'approach', 'label' => 'Approach (Phương pháp/kỹ thuật/giải pháp cụ thể cần giải thích hoặc dạy)', 'type' => 'textarea', 'required' => true],
                ['key' => 'result', 'label' => 'Result (Yêu cầu đầu ra: độ dài, định dạng, các phần bắt buộc, có cần ví dụ minh hoạ không)', 'type' => 'text', 'required' => true],
                ['key' => 'application', 'label' => 'Application (Đối tượng đọc + bối cảnh sử dụng — field ảnh hưởng nhiều nhất đến từ ngữ, độ sâu, cách chọn ví dụ)', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Ràng buộc riêng cho bài viết này — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Problem: {{problem}}\nApproach: {{approach}}\nDesired result: {{result}}\nApplication: {{application}}\nRules: {{rules}}",
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
                ['key' => 'situation', 'label' => 'Situation (Tình huống/thách thức hiện tại cần giải quyết)', 'type' => 'textarea', 'required' => true],
                ['key' => 'purpose', 'label' => 'Purpose (Vì sao việc này quan trọng — mục tiêu phía sau)', 'type' => 'textarea', 'required' => true],
                ['key' => 'expected_output', 'label' => 'Expected Output (Đầu ra CHÍNH XÁC cần có — điểm khác biệt cốt lõi của SPECS, loại bỏ mơ hồ, tránh câu trả lời chung chung)', 'type' => 'text', 'required' => true],
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
            'description' => 'Task · Request · Action · Context · Example — Example là field MẠNH NHẤT của TRACE, dạy AI bằng minh hoạ thay vì mô tả ("show, don\'t tell").',
            'best_for' => 'Có sẵn 1 ví dụ đầu ra ưng ý muốn AI học theo — nhân bản phong cách/định dạng cụ thể, sinh dữ liệu có cấu trúc theo khuôn mẫu thấy được. Thiếu ví dụ tốt, việc sáng tạo cần độc đáo (ví dụ dễ giới hạn sự mới mẻ), hoặc câu hỏi thực tế đơn giản thì dùng APE thay vì TRACE.',
            'fields' => [
                ['key' => 'task', 'label' => 'Task (Loại/nhóm công việc tổng quát)', 'type' => 'text', 'required' => true],
                ['key' => 'request', 'label' => 'Request (Yêu cầu cụ thể, chính xác cần gì)', 'type' => 'textarea', 'required' => true],
                ['key' => 'action', 'label' => 'Action (Hành động cụ thể AI cần thực hiện để đáp ứng yêu cầu)', 'type' => 'text', 'required' => false],
                ['key' => 'context', 'label' => 'Context (Bối cảnh, ràng buộc liên quan)', 'type' => 'textarea', 'required' => false],
                ['key' => 'example', 'label' => 'Example (Ví dụ mẫu — field MẠNH NHẤT của TRACE, dạy AI chính xác điều bạn muốn tốt hơn mọi mô tả bằng lời)', 'type' => 'textarea', 'required' => true],
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
            'description' => 'Role · Instructions · Tools · Reasoning · Action · Observation (+ Rules tuỳ chọn) — dành cho agent AI lặp vòng nhiều bước, KHÔNG phải yêu cầu 1 lần.',
            'best_for' => 'Xây dựng AI agent có dùng công cụ ngoài (tìm kiếm web, chạy code, gọi API), cần suy luận nhiều bước + gọi công cụ tuần tự. Việc 1 lần không cần công cụ thì dùng framework khác đơn giản hơn — kể cả RISEN (RISEN hợp việc có thứ tự bước rõ nhưng không lặp vòng thật qua công cụ; ReAct hợp agent thực sự tương tác vòng lặp).',
            'fields' => [
                ['key' => 'role', 'label' => 'Role (Vai trò AI đóng)', 'type' => 'text', 'required' => true],
                ['key' => 'instructions', 'label' => 'Instructions (Mục tiêu/nhiệm vụ tổng thể agent cần hoàn thành)', 'type' => 'textarea', 'required' => true],
                ['key' => 'tools', 'label' => 'Tools (Danh sách công cụ khả dụng + mô tả từng công cụ)', 'type' => 'text', 'required' => true],
                ['key' => 'reasoning', 'label' => 'Reasoning (Yêu cầu suy nghĩ thành lời trước mỗi hành động — vd bắt đầu bằng "Thought:")', 'type' => 'textarea', 'required' => false],
                ['key' => 'action', 'label' => 'Action (Định dạng gọi công cụ — vd "Action: [tên công cụ]" rồi "Action Input: [tham số]")', 'type' => 'textarea', 'required' => true],
                ['key' => 'observation', 'label' => 'Observation (Cách xử lý kết quả công cụ trả về trước khi suy nghĩ bước tiếp theo)', 'type' => 'text', 'required' => false],
                ['key' => 'rules', 'label' => 'Rules (Điều kiện dừng, định dạng câu trả lời cuối, ràng buộc an toàn — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Role: {{role}}\nInstructions: {{instructions}}\nTools available: {{tools}}\nReasoning: {{reasoning}}\nAction: {{action}}\nObservation: {{observation}}\nRules: {{rules}}",
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
                ['key' => 'topic', 'label' => 'Topic (Chủ đề, đối tượng đọc, mục đích — để định hướng khung sườn)', 'type' => 'text', 'required' => true],
                ['key' => 'skeleton_points', 'label' => 'Skeleton Points (Các phần/mục chính — tự liệt kê hoặc để AI đề xuất trước khi viết chi tiết)', 'type' => 'textarea', 'required' => true],
                ['key' => 'expand_instructions', 'label' => 'Expand Instructions (Mỗi phần viết bao nhiêu đoạn, có ví dụ không, độ sâu ra sao)', 'type' => 'textarea', 'required' => true],
                ['key' => 'output_format', 'label' => 'Output Format (Định dạng cuối: Markdown/heading, số từ, cách trình bày nếu có code)', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Ràng buộc đảm bảo đi đúng 2 giai đoạn — vd "luôn hiện khung trước khi viết chi tiết" — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Topic: {{topic}}\nSkeleton points:\n{{skeleton_points}}\nExpand instructions: {{expand_instructions}}\nOutput format: {{output_format}}\nRules: {{rules}}",
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
                ['key' => 'problem', 'label' => 'Problem (Vấn đề/quyết định cần giải quyết, kèm ràng buộc loại trừ sẵn nếu có)', 'type' => 'textarea', 'required' => true],
                ['key' => 'branches_count', 'label' => 'Number of Branches (Số phương án cần so sánh — thường 3 là vừa đủ đa dạng, không quá rối)', 'type' => 'text', 'required' => true],
                ['key' => 'evaluation_criteria', 'label' => 'Evaluation Criteria (3-4 tiêu chí chấm điểm từng phương án, định nghĩa rõ từng tiêu chí)', 'type' => 'textarea', 'required' => true],
                ['key' => 'selection', 'label' => 'Selection (Cách chọn phương án thắng: bảng điểm, trọng số, hay khuyến nghị kèm lý do)', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Đảm bảo các phương án THẬT SỰ khác nhau; hiện bảng điểm trước khi khuyến nghị — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Problem: {{problem}}\nNumber of branches: {{branches_count}}\nEvaluation criteria: {{evaluation_criteria}}\nSelection: {{selection}}\nRules: {{rules}}",
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
                ['key' => 'task', 'label' => 'Task (Mục tiêu cụ thể cần hoàn thành — càng cụ thể, kế hoạch AI lập càng sát)', 'type' => 'textarea', 'required' => true],
                ['key' => 'plan_instructions', 'label' => 'Plan Instructions (Yêu cầu AI liệt kê từng bước TRƯỚC khi bắt tay vào làm)', 'type' => 'textarea', 'required' => true],
                ['key' => 'solve_instructions', 'label' => 'Solve Instructions (Yêu cầu AI thực hiện đúng theo kế hoạch đã lập, có thể hiện quá trình làm)', 'type' => 'textarea', 'required' => true],
                ['key' => 'output_format', 'label' => 'Output Format (Cấu trúc/định dạng của kết quả cuối cùng)', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Đảm bảo hiện kế hoạch trước khi bắt đầu, không bỏ qua bước nào đã lập — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Task: {{task}}\nPlan instructions: {{plan_instructions}}\nSolve instructions: {{solve_instructions}}\nOutput format: {{output_format}}\nRules: {{rules}}",
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
                ['key' => 'role', 'label' => 'Role (Vai trò chuyên môn AI đóng — đặt kỳ vọng chất lượng ngay từ đầu)', 'type' => 'text', 'required' => true],
                ['key' => 'task', 'label' => 'Task (Việc cần tạo ra, kèm phạm vi/ràng buộc)', 'type' => 'textarea', 'required' => true],
                ['key' => 'criteria', 'label' => 'Criteria (3-5 tiêu chí chất lượng CỤ THỂ, ĐO ĐƯỢC — không dùng tiêu chí mơ hồ như "hay", "rõ ràng")', 'type' => 'textarea', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Yêu cầu hiện đủ 3 bước: Bản nháp → Tự phê bình → Bản hoàn chỉnh — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Role: {{role}}\nTask: {{task}}\nCriteria:\n{{criteria}}\nRules: {{rules}}",
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
                ['key' => 'text', 'label' => 'Nội dung prompt (dán nguyên văn — không cần theo cấu trúc nào)', 'type' => 'textarea', 'required' => true],
            ],
            'template' => '{{text}}',
            'example' => [
                'text' => "Bạn là trợ lý chăm sóc khách hàng của nền tảng khoá học trực tuyến cho gia đình. Nhiệm vụ của bạn là trả lời câu hỏi về cách sử dụng nền tảng, cách đăng ký khoá học, và các vấn đề thanh toán.\n\nQuy tắc:\n- Luôn trả lời bằng tiếng Việt, giọng thân thiện.\n- Không được bịa ra tính năng mà nền tảng chưa có.\n- Nếu không chắc câu trả lời, hướng dẫn người dùng liên hệ tổng đài thay vì đoán.\n- Giữ câu trả lời dưới 100 từ trừ khi người dùng yêu cầu chi tiết hơn.",
            ],
        ],

    ],
];
