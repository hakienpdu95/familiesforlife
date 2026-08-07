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
                'context' => 'Tiêu đề gốc: "5 mẹo giúp con học tốt hơn". Từ khoá mục tiêu: "phương pháp học tập cho trẻ tiểu học".',
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
