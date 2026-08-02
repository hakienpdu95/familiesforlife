<?php

return [
    'name' => 'ContentFoundation',

    // spec/CoreIdeaExtractor.md §12.8 — danh sách bài ĐÃ publish trong 1 category, kéo vào prompt
    // để tránh AI đề xuất trùng ý tưởng đã viết. Chỉ tiêu đề (chuỗi ngắn) nên không có rủi ro
    // "phình" prompt như main_content — cap vẫn đặt ra để tránh truy vấn/payload runaway với
    // category có hàng nghìn bài.
    'existing_articles' => [
        'db_fetch_limit' => 100, // số bài fetch thô từ DB trước khi lọc theo status=published + sort
        'max_titles'     => 30,  // số tiêu đề tối đa đưa vào prompt sau khi đã lọc/sort
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
                'key'         => 'am_no',
                'label'       => 'Ấm no',
                'description' => 'Đảm bảo đời sống vật chất, thu nhập ổn định và nhu cầu sinh hoạt cơ bản cho các thành viên.',
            ],
            [
                'key'         => 'hanh_phuc',
                'label'       => 'Hạnh phúc',
                'description' => 'Tạo dựng bầu không khí yêu thương, sự gắn kết, chia sẻ và tôn trọng lẫn nhau.',
            ],
            [
                'key'         => 'tien_bo',
                'label'       => 'Tiến bộ',
                'description' => 'Thực hiện bình đẳng giới, loại bỏ bạo lực gia đình và hủ tục lạc hậu.',
            ],
            [
                'key'         => 'van_minh',
                'label'       => 'Văn minh',
                'description' => 'Xây dựng lối sống ứng xử chuẩn mực, văn hóa giữa các thế hệ trong mái ấm.',
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
