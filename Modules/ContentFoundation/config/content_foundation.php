<?php

return [
    'name' => 'ContentFoundation',

    // spec/CoreIdeaExtractor.md §12.8 — danh sách bài ĐÃ publish trong 1 category, kéo vào prompt
    // để tránh AI đề xuất trùng ý tưởng đã viết. Chỉ tiêu đề (chuỗi ngắn) nên không có rủi ro
    // "phình" prompt như main_content — cap vẫn đặt ra để tránh truy vấn/payload runaway với
    // category có hàng nghìn bài.
    'existing_articles' => [
        'db_fetch_limit' => 100, // số bài fetch thô từ DB trước khi lọc theo status=published + sort
        'max_titles' => 30,  // số tiêu đề tối đa đưa vào prompt sau khi đã lọc/sort
    ],

    // spec/CoreIdeaExtractor.md §12.10 — Hệ giá trị gia đình Việt Nam (spec/giadinh.md), 4 trụ cột
    // theo Quyết định 1189/QĐ-TTg ngày 02/07/2026. Đây là CHUẨN NỀN TẢNG của toàn platform
    // (familiesforlife), không phải nội dung editor tự viết — nguồn SỰ THẬT DUY NHẤT ở đây (KHÔNG
    // hardcode lặp lại ở blade/JS của bất kỳ module nào dùng chung config này), luôn đọc qua
    // config('content_foundation.family_values...'). `key` khớp giá trị lưu trong
    // `family_values_focus` (cột JSON trên content_foundations) và rule validate 'in:...' ở
    // CategoryFoundationController::upsert().
    'family_values' => [
        'decision_ref' => 'Quyết định 1189/QĐ-TTg ngày 02/07/2026',
        'items' => [
            [
                'key' => 'am_no',
                'label' => 'Ấm no',
                'description' => 'Đảm bảo đời sống vật chất, thu nhập ổn định và nhu cầu sinh hoạt cơ bản cho các thành viên.',
            ],
            [
                'key' => 'hanh_phuc',
                'label' => 'Hạnh phúc',
                'description' => 'Tạo dựng bầu không khí yêu thương, sự gắn kết, chia sẻ và tôn trọng lẫn nhau.',
            ],
            [
                'key' => 'tien_bo',
                'label' => 'Tiến bộ',
                'description' => 'Thực hiện bình đẳng giới, loại bỏ bạo lực gia đình và hủ tục lạc hậu.',
            ],
            [
                'key' => 'van_minh',
                'label' => 'Văn minh',
                'description' => 'Xây dựng lối sống ứng xử chuẩn mực, văn hóa giữa các thế hệ trong mái ấm.',
            ],
        ],
    ],

    // spec/CoreIdeaExtractor.md §12.11 — Bộ tiêu chí ứng xử trong gia đình (spec/giadinh.md), 4 cặp
    // quan hệ. KHÁC `family_values` ở trên: đây không phải 1 văn bản có số hiệu cố định — được ban
    // hành/cập nhật theo từng địa phương/năm — nên `decision_ref` chỉ mô tả đây là khung chuẩn
    // chung, KHÔNG trích số hiệu/ngày ban hành cụ thể (tránh đúng lỗi đã né ở `family_values`: bịa
    // chi tiết 1 văn bản pháp quy). Cũng là CHUẨN NỀN TẢNG platform, không phải nội dung editor tự
    // viết — nguồn SỰ THẬT DUY NHẤT ở đây, luôn đọc qua config('content_foundation.family_conduct_
    // standards...'). `key` khớp giá trị lưu trong `family_conduct_focus` (cột JSON trên
    // content_foundations) và rule validate 'in:...' ở CategoryFoundationController::upsert().
    // Mục "Tiêu chí ứng xử chung" (Tôn trọng/Bình đẳng/Yêu thương/Chia sẻ) của văn bản gốc KHÔNG lặp
    // lại ở đây — trùng vai trò với 4 giá trị nền ở `family_values` phía trên.
    'family_conduct_standards' => [
        'decision_ref' => 'Bộ tiêu chí ứng xử trong gia đình (khung chuẩn chung, các địa phương ban hành/cập nhật theo từng năm)',
        'items' => [
            [
                'key' => 'vo_chong',
                'relationship' => 'Vợ, chồng',
                'label' => 'Chung thủy, nghĩa tình',
                'principles' => [
                    'Cùng nhau xây dựng hôn nhân bền vững, không vi phạm chế độ hôn nhân một vợ một chồng.',
                    'Yêu thương, quan tâm, chăm sóc, giúp đỡ nhau; cùng chia sẻ công việc gia đình, cùng có trách nhiệm nuôi dạy con, làm việc nhà, đóng góp tài chính.',
                    'Tạo điều kiện giúp nhau chọn nghề nghiệp, học tập, nâng cao trình độ, tham gia hoạt động chính trị, kinh tế, văn hóa, xã hội.',
                    'Lắng nghe, cùng thảo luận, thống nhất và quyết định vấn đề chung của gia đình; hòa nhã với nhau.',
                ],
            ],
            [
                'key' => 'cha_me_ong_ba_voi_con_chau',
                'relationship' => 'Cha mẹ với con, ông bà với cháu',
                'label' => 'Gương mẫu, yêu thương',
                'principles' => [
                    'Làm gương tốt cho con cháu trong cử chỉ, hành động, lời nói; có tình cảm gắn bó gần gũi với con cháu.',
                    'Quan tâm, nuôi dưỡng, chăm sóc, dạy bảo con cháu khi còn nhỏ hoặc khi con cháu không có khả năng tự nuôi sống, chăm sóc bản thân.',
                    'Trao truyền giá trị truyền thống, kinh nghiệm sống cho con cháu; giáo dục, động viên con cháu lối sống văn hóa, ý thức công dân, giữ gìn nền nếp, gia phong.',
                ],
            ],
            [
                'key' => 'con_voi_cha_me_ong_ba',
                'relationship' => 'Con với cha mẹ, ông bà',
                'label' => 'Hiếu thảo, lễ phép',
                'principles' => [
                    'Kính trọng, lễ phép, hiếu thảo với ông bà, cha mẹ; yêu thương, quan tâm, chia sẻ tình cảm, nguyện vọng với cha mẹ và các thành viên trong gia đình.',
                    'Học tập, rèn luyện, giữ gìn nền nếp gia đình, phụ giúp cha mẹ và các thành viên những công việc phù hợp với độ tuổi, giới tính.',
                    'Thăm hỏi, chăm sóc, động viên, nuôi dưỡng cha mẹ, ông bà khi ốm đau, già yếu, gặp khó khăn trong cuộc sống.',
                ],
            ],
            [
                'key' => 'anh_chi_em',
                'relationship' => 'Anh, chị, em',
                'label' => 'Hòa thuận, chia sẻ',
                'principles' => [
                    'Tôn trọng, bảo nhau điều hay, lẽ phải.',
                    'Anh chị bao dung đối với em, em kính trọng anh chị.',
                    'Cùng chia sẻ công việc chung trong gia đình, giúp đỡ nhau khi khó khăn, hoạn nạn.',
                ],
            ],
        ],
    ],

    // Ngưỡng "có thể đã cũ" cho Category Content Foundation (core_focus/pain_points/rejected_ideas...)
    // — CHỈ hiển thị nhắc nhở trực quan ở trang quản lý (KHÔNG chặn/xoá/tự động làm gì) để editor để
    // ý ôn lại ngữ cảnh định kỳ, tránh "set và quên" mãi mãi.
    'foundation' => [
        'stale_after_days' => 180,
    ],
];
