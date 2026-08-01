<?php

return [
    'name' => 'CoreIdeaExtractor',

    // Ngưỡng extraction_confidence — spec/CoreIdeaExtractor.md §5.4 (v1.2: mốc DUY NHẤT
    // 200 từ, không còn vùng xám 150-199) + §9 (< 150 từ => error=true).
    'confidence' => [
        'high_min_words'     => 400,
        'high_min_headings'  => 2,
        'medium_min_words'   => 200,
        'error_max_words'    => 150,
    ],

    // Thin content trigger — spec §6.1.4 (v1.3), chỉ dùng để trả thêm gợi ý trong `notes`
    // (Layer 2 không chạy trong module này nên không có core_ideas để giới hạn số lượng thật).
    'thin_content' => [
        'max_words'    => 300,
        'max_headings' => 1,
    ],

    // Fetch HTML — timeout tính bằng giây, User-Agent minh bạch (không giả mạo browser thật),
    // cap kích thước response tránh trang quá nặng/độc hại làm nghẽn worker.
    'fetch' => [
        'timeout_seconds'   => 15,
        'max_redirects'     => 3,
        'max_content_bytes' => 5 * 1024 * 1024, // 5MB
        'user_agent'        => 'CoreIdeaExtractorBot/1.0 (+internal content research tool)',
    ],

    // Mã HTML người dùng dán tay trực tiếp (khi trang chặn crawl tự động — 403/bot protection)
    // — chặn ký tự tối đa để tránh payload quá lớn (không đi qua HTTP nên không dùng chung cap
    // 'fetch.max_content_bytes' theo byte; đây tính theo ký tự vì là input dạng string từ form).
    'paste' => [
        'max_chars' => 2_000_000,
    ],

    // Cắt main_content trả về JSON để tránh payload quá lớn khi bài viết rất dài — 20000 từng
    // cắt cụt cả bài viết bình thường (VD 1 bài ~3000 từ, 21596 ký tự đã bị cắt mất đoạn cuối),
    // nên nới lên mức chỉ chặn được trường hợp cực đoan (content root chọn nhầm nguyên trang).
    'max_main_content_chars' => 100000,

    // Batch nhiều URL cùng lúc (Http::pool) — kết quả dùng để copy nguyên JSON dán vào chat AI
    // (claude.ai), nên main_content mỗi nguồn phải cắt ngắn hơn NHIỀU so với mode 1-URL: 7 nguồn
    // x 100000 ký tự sẽ quá lớn để paste. 12000 ký tự (~2000+ từ) đủ cho phần lớn bài viết bình
    // thường, chỉ cắt bớt các bài cực dài.
    'batch' => [
        'max_urls'                          => 7,
        'max_main_content_chars_per_source' => 12000,
    ],

    // Cache HTML thô theo URL (Cache facade — dùng cache store mặc định của app, KHÔNG phải
    // bảng DB riêng, đúng tinh thần "module KHÔNG có Eloquent Model nào") — 2 batch khác nhau
    // vô tình trùng URL sẽ không fetch/tải mạng lại trong TTL này. Cache RAW HTML (trước parse)
    // chứ không cache kết quả extract đã xong, vì main_content_selector có thể khác nhau giữa
    // 2 lần gọi cùng URL — parse luôn chạy lại, chỉ bước fetch mạng là được tái sử dụng.
    // content_hash_ttl_seconds dùng cho index dedup (content_hash => url đầu tiên thấy nội dung
    // này) — TTL dài hơn vì chỉ lưu 1 chuỗi url, không tốn nhiều bộ nhớ như cache HTML.
    'cache' => [
        'enabled'                  => true,
        'fetch_ttl_seconds'        => 3600,
        'content_hash_ttl_seconds' => 86400,
    ],

    // 2026-07-28 — tự động hoá "Layer 2" (§6/§12.3) qua nút bấm thủ công (KHÔNG tự động sau
    // Layer 1 — kiểm soát chi phí, xem RunLayer2ExtractionAction). max_prompt_chars chặn payload
    // quá lớn gửi lên endpoint (khác max_main_content_chars — đó là cắt bớt NỘI DUNG trích xuất,
    // đây là trần cho toàn bộ prompt TOP+MIDDLE+BOTTOM đã ghép). max_output_tokens đủ cho 2 bảng
    // Markdown tới ~25 ý tưởng kèm lý do mỗi ý (BƯỚC 1-3 của prompt).
    'layer2' => [
        'max_prompt_chars'  => 300000,
        'max_output_tokens' => 4096,
    ],

    // 2026-07-30 — 2 tính năng mở rộng (spec/content.md mục A+B): "Tóm tắt nội dung" và "Tái cấu
    // trúc nội dung", dùng chung `layer2.max_prompt_chars` làm trần payload prompt (cùng bản chất
    // "1 prompt build sẵn ở client" — không cần trần riêng). max_output_tokens thấp hơn Layer 2
    // vì output ngắn hơn nhiều: tóm tắt chỉ 1 đoạn <100 từ + vài gạch đầu dòng, tái cấu trúc chỉ
    // 3 đoạn ngắn theo nền tảng (Facebook/LinkedIn/Twitter), không phải bảng tới ~25 dòng ý tưởng.
    'summarization' => [
        'max_output_tokens' => 800,
    ],

    'rewrite' => [
        'max_output_tokens' => 2000,
    ],

    // spec/CoreIdeaExtractor.md §12.8 (v1.11) — danh sách bài ĐÃ publish trong 1 category, kéo
    // vào prompt để tránh AI đề xuất trùng ý tưởng đã viết. Chỉ tiêu đề (chuỗi ngắn) nên không có
    // rủi ro "phình" prompt như main_content — cap vẫn đặt ra theo đúng thói quen của module (mọi
    // danh sách không giới hạn đều có trần, xem batch.max_urls) để tránh truy vấn/payload runaway
    // với category có hàng nghìn bài.
    'existing_articles' => [
        'db_fetch_limit' => 100, // số bài fetch thô từ DB trước khi lọc theo status=published + sort — cần dư ra vì không phải bài nào cũng published
        'max_titles'     => 30,  // số tiêu đề tối đa đưa vào prompt sau khi đã lọc/sort
    ],

    // 2026-08-01 — Hệ giá trị gia đình Việt Nam (spec/giadinh.md), 4 trụ cột theo Quyết định
    // 1189/QĐ-TTg ngày 02/07/2026. Đây là CHUẨN NỀN TẢNG của toàn platform (familiesforlife),
    // không phải nội dung editor tự viết — nguồn SỰ THẬT DUY NHẤT ở đây (KHÔNG hardcode lặp lại
    // ở blade/JS), luôn được đọc qua config('core_idea_extractor.family_values...') để tránh 2
    // nơi định nghĩa lệch nhau. `key` khớp giá trị lưu trong `family_values_focus` (cột JSON trên
    // cie_category_foundations) và rule validate 'in:...' ở CategoryFoundationController::upsert().
    // Khác các field ad-hoc khác (core_focus/pain_points/...): nội dung 4 giá trị này CỐ ĐỊNH,
    // không sửa qua UI — editor chỉ TICK giá trị nào chuyên mục ưu tiên phục vụ (family_values_focus),
    // không tự viết lại định nghĩa.
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
    // ý ôn lại ngữ cảnh định kỳ, tránh "set và quên" mãi mãi — ngữ cảnh biên tập là tài sản SỐNG, cần
    // cập nhật theo thời gian, không phải cấu hình tĩnh viết 1 lần rồi thôi.
    'foundation' => [
        'stale_after_days' => 180,
    ],
];
